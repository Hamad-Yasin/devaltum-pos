<?php

class AuthController extends Controller {

    public function login() {
        if (Auth::check()) {
            $this->redirectByRole();
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $error = "Invalid security token.";
            } else {
                $identifier = trim((string) ($_POST['identifier'] ?? ''));
                $password = $_POST['password'] ?? '';

                $db = Database::getInstance();
                $user = $db->fetch("
                    SELECT
                        u.*,
                        s.store_name,
                        COALESCE(parent_s.status, s.status) AS store_status,
                        COALESCE(parent_s.subscription_expires_at, s.subscription_expires_at) AS subscription_expires_at
                    FROM users u
                    LEFT JOIN stores s ON s.id = u.store_id
                    LEFT JOIN stores parent_s ON parent_s.id = s.parent_store_id
                    WHERE (u.email = ? OR u.username = ?)
                    LIMIT 1
                ", [$identifier, $identifier]);

                $status = strtolower((string) ($user['status'] ?? 'active'));
                $storeStatus = strtolower((string) ($user['store_status'] ?? 'active'));
                $isExpired = false;
                if (!empty($user['subscription_expires_at'])) {
                    $isExpired = strtotime((string) $user['subscription_expires_at']) < time();
                }
                if (
                    $user
                    && password_verify($password, $user['password'])
                    && $status === 'active'
                ) {
                    Auth::login($user);
                    
                    // Log activity
                    $db->query(
                        "INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)",
                        [Tenant::currentStoreId(), $user['id'], 'Login', 'User logged in to the system']
                    );
                    
                    if ($user['role'] !== Tenant::ROLE_SUPER_ADMIN && ($isExpired || $storeStatus === 'expired')) {
                        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                        header('Location: ' . $baseUrl . '/plans/pay');
                        exit;
                    }

                    $this->redirectByRole();
                } else {
                    if ($user && $status !== 'active') {
                        $error = "Your account is inactive. Please contact support.";
                    } else {
                        $error = "Invalid credentials.";
                    }
                }
            }
        }

        $db = Database::getInstance();
        $isBiometricEnabled = App::setting('enable_biometric_login', '1') === '1';
        $hasBiometrics = false;
        if ($isBiometricEnabled) {
            try {
                $bioRow = $db->fetch("SELECT COUNT(*) AS total FROM user_biometrics");
                $hasBiometrics = !empty($bioRow['total']) && (int) $bioRow['total'] > 0;
            } catch (\Throwable $e) {
                $hasBiometrics = false;
            }
        }

        $hasExpiredStore = false;
        try {
            $expRow = $db->fetch("SELECT COUNT(*) AS total FROM stores WHERE status = 'expired' OR (subscription_expires_at IS NOT NULL AND subscription_expires_at < NOW())");
            $hasExpiredStore = !empty($expRow['total']) && (int) $expRow['total'] > 0;
        } catch (\Throwable $e) {
            $hasExpiredStore = false;
        }

        $this->view('auth/login', [
            'error' => $error,
            'showBiometricLogin' => ($isBiometricEnabled && $hasBiometrics),
            'showExpiredPayButton' => $hasExpiredStore || !empty($isExpired)
        ]);
    }

    public function profile() {
        AuthMiddleware::handle();
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE id = ?", [Auth::id()]);
        $isProtectedPrimarySuperAdmin = $this->isProtectedPrimarySuperAdmin((int) Auth::id(), $db);

        if (!$user) {
            Auth::logout();
            $this->redirect('auth/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->flash('error', 'Security token mismatch. Please try again.');
                $this->redirect('auth/profile');
            }

            if ($isProtectedPrimarySuperAdmin) {
                $this->flash('error', 'Primary Super Admin account is read-only and cannot be edited.');
                $this->redirect('auth/profile');
            }

            if (($_POST['action'] ?? '') === 'password') {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if (!password_verify($currentPassword, $user['password'])) {
                    $this->flash('error', 'Current password is incorrect.');
                } elseif (strlen($newPassword) < 6) {
                    $this->flash('error', 'New password must be at least 6 characters.');
                } elseif ($newPassword !== $confirmPassword) {
                    $this->flash('error', 'New password confirmation does not match.');
                } else {
                    $db->query("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?", [
                        password_hash($newPassword, PASSWORD_BCRYPT),
                        Auth::id()
                    ]);
                    $this->flash('success', 'Password updated successfully.');
                }

                $this->redirect('auth/profile');
            }

            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if ($name === '' || $email === '') {
                $this->flash('error', 'Name and email are required.');
            } else {
                $existing = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, Auth::id()]);
                if ($existing) {
                    $this->flash('error', 'This email is already being used by another account.');
                } else {
                    $db->query("UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?", [$name, $email, Auth::id()]);
                    Auth::login([
                        'id' => Auth::id(),
                        'name' => $name,
                        'email' => $email,
                        'role' => $user['role'],
                        'permissions' => $user['permissions'] ?? '',
                        'store_id' => $user['store_id'] ?? null
                    ]);
                    $this->flash('success', 'Profile updated successfully.');
                }
            }

            $this->redirect('auth/profile');
        }

        $this->view('auth/profile', [
            'pageTitle' => 'My Profile',
            'activeMenu' => 'profile',
            'user' => $user,
            'isProtectedPrimarySuperAdmin' => $isProtectedPrimarySuperAdmin
        ]);
    }

    public function logout() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        if (Auth::check()) {
            $db = Database::getInstance();
            try {
                $db->query(
                    "INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)",
                    [Tenant::currentStoreId(), Auth::id(), 'Logout', 'User logged out']
                );
            } catch (Throwable $e) {
                // Prevent database logging exceptions (e.g. stale sessions or foreign key constraints) from breaking the logout process
            }
                
            Auth::logout();
        }
        $this->redirect('auth/login');
    }

    public function returnToSuperAdmin() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $returnId = (int) ($_SESSION['superadmin_return_id'] ?? 0);
        if ($returnId <= 0) {
            $this->flash('error', 'No super admin session found to return.');
            $this->redirect('home/index');
        }

        $db = Database::getInstance();
        $superAdmin = $db->fetch("
            SELECT *
            FROM users
            WHERE id = ? AND role = 'super_admin' AND status = 'active'
            LIMIT 1
        ", [$returnId]);
        if (!$superAdmin) {
            unset($_SESSION['superadmin_return_id']);
            $this->flash('error', 'Super admin account not found.');
            $this->redirect('auth/login');
        }

        unset($_SESSION['superadmin_return_id']);
        Auth::login($superAdmin);
        $this->flash('success', 'Returned to Super Admin session.');
        $this->redirect('superadmin/index');
    }

    public function returnToParentOwner() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $returnId = (int) ($_SESSION['parent_owner_return_id'] ?? 0);
        if ($returnId <= 0) {
            $this->flash('error', 'No main branch session found to return.');
            $this->redirect('home/index');
        }

        $db = Database::getInstance();
        $owner = $db->fetch("
            SELECT
                u.*,
                s.store_name,
                s.status AS store_status,
                s.subscription_expires_at
            FROM users u
            LEFT JOIN stores s ON s.id = u.store_id
            WHERE u.id = ?
                AND u.role IN ('store_owner', 'admin')
                AND u.status = 'active'
            LIMIT 1
        ", [$returnId]);

        if (!$owner) {
            unset($_SESSION['parent_owner_return_id']);
            $this->flash('error', 'Main branch owner account not found.');
            $this->redirect('auth/login');
        }

        unset($_SESSION['parent_owner_return_id']);
        Auth::login($owner);
        $this->flash('success', 'Returned to main branch panel.');
        $this->redirect('users/index?tab=branch_list');
    }

    public function biometricLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid method'], 400);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $credId = trim((string) ($input['credential_id'] ?? ''));

        if (empty($credId)) {
            $this->jsonResponse(['error' => 'Missing biometric credential ID.'], 400);
        }

        $db = Database::getInstance();
        $bio = $db->fetch("SELECT * FROM user_biometrics WHERE credential_id = ? LIMIT 1", [$credId]);

        if (!$bio) {
            $this->jsonResponse(['error' => 'Biometric credential not recognized on this device. Please log in with password first to register your fingerprint.'], 404);
        }

        $user = $db->fetch("
            SELECT
                u.*,
                s.store_name,
                COALESCE(parent_s.status, s.status) AS store_status,
                COALESCE(parent_s.subscription_expires_at, s.subscription_expires_at) AS subscription_expires_at
            FROM users u
            LEFT JOIN stores s ON s.id = u.store_id
            LEFT JOIN stores parent_s ON parent_s.id = s.parent_store_id
            WHERE u.id = ?
            LIMIT 1
        ", [(int)$bio['user_id']]);

        if (!$user) {
            $this->jsonResponse(['error' => 'Associated user account not found.'], 404);
        }

        $status = strtolower((string) ($user['status'] ?? 'active'));
        $storeStatus = strtolower((string) ($user['store_status'] ?? 'active'));
        $isExpired = false;
        if (!empty($user['subscription_expires_at'])) {
            $isExpired = strtotime((string) $user['subscription_expires_at']) < time();
        }

        if ($status !== 'active') {
            $this->jsonResponse(['error' => 'Account is inactive. Please contact administrator.'], 403);
        }
        if ($user['role'] !== Tenant::ROLE_SUPER_ADMIN && ($storeStatus !== 'active' || $isExpired)) {
            $this->jsonResponse(['error' => 'Store subscription is inactive or expired.'], 403);
        }

        // Login user
        Auth::login($user);

        // Update biometrics usage stats
        $db->query("UPDATE user_biometrics SET last_used_at = NOW(), sign_count = sign_count + 1 WHERE id = ?", [(int)$bio['id']]);

        // Log Activity
        $db->query(
            "INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)",
            [Tenant::currentStoreId(), $user['id'], 'Biometric Login', "User logged in via {$bio['device_name']} fingerprint biometric"]
        );

        $redirectUrl = (defined('BASE_URL') ? BASE_URL : '') . (Auth::isSuperAdmin() ? '/superadmin/index' : '/home/index');

        $this->jsonResponse([
            'success' => true,
            'message' => 'Fingerprint authenticated successfully!',
            'redirect' => $redirectUrl
        ]);
    }

    public function biometricRegister() {
        AuthMiddleware::handle();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid method'], 400);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $credId = trim((string) ($input['credential_id'] ?? ''));
        $pubKey = trim((string) ($input['public_key'] ?? ''));
        $deviceName = trim((string) ($input['device_name'] ?? 'Mobile / PWA Device'));

        if (empty($credId)) {
            $this->jsonResponse(['error' => 'Invalid biometric credential data.'], 400);
        }

        $db = Database::getInstance();
        $userId = (int) Auth::id();
        $storeId = (int) Tenant::currentStoreId();

        // Check if already registered
        $existing = $db->fetch("SELECT id FROM user_biometrics WHERE credential_id = ? LIMIT 1", [$credId]);
        if ($existing) {
            $db->query("UPDATE user_biometrics SET device_name = ?, last_used_at = NOW() WHERE id = ?", [$deviceName, (int)$existing['id']]);
        } else {
            $db->query("
                INSERT INTO user_biometrics (user_id, store_id, credential_id, public_key, device_name, created_at, last_used_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ", [$userId, $storeId > 0 ? $storeId : null, $credId, $pubKey ?: 'webauthn_key', $deviceName]);
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Fingerprint / Biometric device successfully registered!'
        ]);
    }

    public function biometricDelete($id) {
        AuthMiddleware::handle();
        $id = (int) $id;
        $db = Database::getInstance();
        $userId = (int) Auth::id();

        $db->query("DELETE FROM user_biometrics WHERE id = ? AND user_id = ?", [$id, $userId]);
        $this->flash('success', 'Biometric device removed.');
        $this->redirect('updates/index?tab=security');
    }

    private function redirectByRole() {
        if (Auth::isSuperAdmin()) {
            $this->redirect('superadmin/index');
        }
        $this->redirect('home/index');
    }

    private function isProtectedPrimarySuperAdmin($userId, $db = null) {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return false;
        }
        if ((string) ($_SESSION['user_role'] ?? '') !== Tenant::ROLE_SUPER_ADMIN) {
            return false;
        }

        $db = $db ?: Database::getInstance();
        $primary = $db->fetch("
            SELECT id
            FROM users
            WHERE role = 'super_admin'
            ORDER BY id ASC
            LIMIT 1
        ");

        return (int) ($primary['id'] ?? 0) === $userId;
    }
}
