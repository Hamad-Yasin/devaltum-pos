<?php

class UsersController extends Controller {

    public function __construct() {
        AuthMiddleware::handleAdmin();
    }

    public function index() {
        $db = Database::getInstance();
        [$where, $params] = Tenant::storeFilter();
        $users = $db->fetchAll("SELECT id, name, email, role, permissions, created_at FROM users WHERE $where ORDER BY created_at DESC", $params);
        $isBranchAccount = Tenant::isBranchAccount();
        $branches = $isBranchAccount ? [] : $this->getBranches($db);
        $protectedAdminId = $this->getProtectedAdminId($db);

        $permissions = [
            'dashboard' => 'Dashboard',
            'pos' => 'POS Checkout',
            'sales' => 'Sales',
            'quotations' => 'Quotations & Estimates',
            'inventory' => 'Inventory',
            'customers' => 'Customers',
            'reports' => 'Reports',
            'expenses' => 'Expenses',
            'credits' => 'Credit Dues',
            'settings' => 'Settings'
        ];

        $this->view('../../modules/users/views/index', [
            'pageTitle' => 'Users',
            'activeMenu' => 'users',
            'users' => $users,
            'branches' => $branches,
            'isBranchAccount' => $isBranchAccount,
            'permissions' => $permissions,
            'protectedAdminId' => $protectedAdminId
        ]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('users/index');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = ($_POST['role'] ?? 'staff') === 'store_owner' ? 'store_owner' : 'staff';
        $permissions = $_POST['permissions'] ?? [];

        if ($name === '' || $email === '' || $password === '') {
            $this->flash('error', 'Name, email and password are required.');
            $this->redirect('users/index');
        }

        [$where, $params] = Tenant::storeFilter();
        if ($db->fetch("SELECT id FROM users WHERE email = ? AND $where LIMIT 1", array_merge([$email], $params))) {
            $this->flash('error', 'Email already exists.');
            $this->redirect('users/index');
        }

        $permissionString = $role === 'store_owner' ? '' : $this->sanitizePermissions($permissions);
        $db->query(
            "INSERT INTO users (store_id, name, email, password, role, status, permissions) VALUES (?, ?, ?, ?, ?, 'active', ?)",
            [Tenant::currentStoreId(), $name, $email, password_hash($password, PASSWORD_BCRYPT), $role, $permissionString]
        );
        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), Auth::id(), 'User', "Added user: $name"]);
        $this->flash('success', 'User created.');
        $this->redirect('users/index');
    }

    public function createBranch() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('users/index?tab=branch_add');
        }
        $this->verifyCsrfOrAbort();

        $parentStoreId = (int) Tenant::currentStoreId();
        if ($parentStoreId <= 0 || Tenant::isBranchAccount()) {
            $this->flash('error', 'Store context not found.');
            $this->redirect('users/index?tab=list');
        }

        $branchName = trim((string) ($_POST['branch_name'] ?? ''));
        $branchCode = strtoupper(trim((string) ($_POST['branch_code'] ?? '')));
        $ownerName = trim((string) ($_POST['owner_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));

        if ($branchName === '' || $ownerName === '' || $email === '' || $password === '') {
            $this->flash('error', 'Branch name, admin name, email and password are required.');
            $this->redirect('users/index?tab=branch_add');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Please enter a valid branch admin email.');
            $this->redirect('users/index?tab=branch_add');
        }
        if (strlen($password) < 6) {
            $this->flash('error', 'Branch admin password must be at least 6 characters.');
            $this->redirect('users/index?tab=branch_add');
        }

        $db = Database::getInstance();
        if ($db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$email])) {
            $this->flash('error', 'This email already exists in another account.');
            $this->redirect('users/index?tab=branch_add');
        }

        $parentStore = $db->fetch("SELECT * FROM stores WHERE id = ? LIMIT 1", [$parentStoreId]);
        if (!$parentStore) {
            $this->flash('error', 'Parent store not found.');
            $this->redirect('users/index?tab=branch_add');
        }

        if ($branchCode === '') {
            $branchCode = 'BR-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        }

        try {
            $connection = $db->getConnection();
            $connection->beginTransaction();

            $storeUid = $this->generateStoreUid($db);
            $connection->prepare(
                "
                INSERT INTO stores
                    (store_uid, store_name, owner_name, email, phone, address, plan_type, status, current_plan_id, subscription_started_at, subscription_expires_at, payment_status, parent_store_id, branch_code)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, ?)
                "
            )->execute([
                $storeUid,
                $branchName,
                $ownerName,
                $email,
                $phone !== '' ? $phone : null,
                $address !== '' ? $address : null,
                $parentStore['plan_type'] ?? 'free',
                $parentStore['current_plan_id'] ?? null,
                $parentStore['subscription_started_at'] ?? null,
                $parentStore['subscription_expires_at'] ?? null,
                $parentStore['payment_status'] ?? 'paid',
                $parentStoreId,
                $branchCode
            ]);

            $branchStoreId = (int) $connection->lastInsertId();
            $connection->prepare(
                "INSERT INTO users (store_id, name, email, password, role, status, permissions) VALUES (?, ?, ?, ?, 'store_owner', 'active', '')"
            )->execute([
                $branchStoreId,
                $ownerName,
                $email,
                password_hash($password, PASSWORD_BCRYPT)
            ]);

            $settings = $db->fetchAll("SELECT setting_key, setting_value FROM store_settings WHERE store_id = ?", [$parentStoreId]);
            $settingStmt = $connection->prepare("INSERT INTO store_settings (store_id, setting_key, setting_value) VALUES (?, ?, ?)");
            foreach ($settings as $setting) {
                $settingStmt->execute([$branchStoreId, $setting['setting_key'], $setting['setting_value']]);
            }

            $connection->prepare("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)")
                ->execute([$parentStoreId, Auth::id(), 'Branch', "Created branch account: $branchName"]);

            $connection->commit();
            $this->flash('success', 'Branch account created. Branch admin can now login and see isolated dashboard data.');
        } catch (Exception $e) {
            if (isset($connection) && $connection->inTransaction()) {
                $connection->rollBack();
            }
            $this->flash('error', 'Unable to create branch account.');
        }

        $this->redirect('users/index?tab=branch_list');
    }

    public function userProfile($id) {
        $db = Database::getInstance();
        $id = (int) $id;
        [$where, $params] = Tenant::storeFilter();
        $user = $db->fetch("SELECT * FROM users WHERE id = ? AND $where LIMIT 1", array_merge([$id], $params));
        if (!$user) {
            $this->flash('error', 'User not found.');
            $this->redirect('users/index?tab=list');
        }

        $permissions = [
            'dashboard' => 'Dashboard',
            'pos' => 'POS Checkout',
            'sales' => 'Sales',
            'inventory' => 'Inventory',
            'customers' => 'Customers',
            'reports' => 'Reports',
            'expenses' => 'Expenses',
            'credits' => 'Credit Dues',
            'settings' => 'Settings'
        ];

        $this->view('../../modules/users/views/view', [
            'pageTitle' => 'User Profile',
            'activeMenu' => 'users',
            'userRecord' => $user,
            'permissions' => $permissions,
            'protectedAdminId' => $this->getProtectedAdminId($db)
        ]);
    }

    public function loginUser($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $id = (int) $id;
        $currentUserId = (int) Auth::id();
        if ($id <= 0 || $id === $currentUserId) {
            $this->flash('error', 'Select another active user to login.');
            $this->redirect('users/userProfile/' . max(1, $id));
        }

        $protectedAdminId = $this->getProtectedAdminId($db);
        if ($id === $protectedAdminId) {
            $this->flash('error', 'Protected owner account cannot be opened this way.');
            $this->redirect('users/userProfile/' . $id);
        }

        [$where, $params] = Tenant::storeFilter();
        $targetUser = $db->fetch("SELECT * FROM users WHERE id = ? AND $where AND status = 'active' LIMIT 1", array_merge([$id], $params));
        if (!$targetUser) {
            $this->flash('error', 'Active user not found.');
            $this->redirect('users/index?tab=list');
        }

        $tabSessionId = bin2hex(random_bytes(24));
        session_write_close();
        ini_set('session.use_cookies', '0');
        ini_set('session.use_only_cookies', '0');
        session_id($tabSessionId);
        session_start();
        $_SESSION['parent_owner_return_id'] = $currentUserId;
        Auth::login($targetUser);

        $db->query(
            "INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)",
            [Tenant::currentStoreId(), $currentUserId, 'User Login', 'Logged in as user: ' . $targetUser['name']]
        );
        $this->flash('success', 'Logged in as ' . $targetUser['name'] . '.');
        $this->redirect('home/index?tab_session=' . rawurlencode(session_id()));
    }

    public function branch($id) {
        $db = Database::getInstance();
        if (Tenant::isBranchAccount()) {
            $this->flash('error', 'Branch profiles are available only in the parent account.');
            $this->redirect('users/index?tab=list');
        }
        $branch = $this->getBranchForCurrentStore($db, (int) $id);
        if (!$branch) {
            $this->flash('error', 'Branch account not found.');
            $this->redirect('users/index?tab=branch_list');
        }

        $this->view('../../modules/users/views/branch', [
            'pageTitle' => 'Branch Profile',
            'activeMenu' => 'users',
            'branch' => $branch
        ]);
    }

    public function loginBranch($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        if (Tenant::isBranchAccount()) {
            $this->flash('error', 'Branch login is available only in the parent account.');
            $this->redirect('users/index?tab=list');
        }
        $parentStoreId = (int) Tenant::currentStoreId();
        $branchStoreId = (int) $id;
        $branchUser = $db->fetch(
            "
            SELECT
                u.*,
                s.store_name,
                s.status AS store_status,
                s.subscription_expires_at
            FROM users u
            INNER JOIN stores s ON s.id = u.store_id
            WHERE s.id = ?
                AND s.parent_store_id = ?
                AND u.role = 'store_owner'
                AND u.status = 'active'
            ORDER BY u.id ASC
            LIMIT 1
            ",
            [$branchStoreId, $parentStoreId]
        );

        if (!$branchUser) {
            $this->flash('error', 'Active branch admin account not found.');
            $this->redirect('users/branch/' . $branchStoreId);
        }

        $returnId = Auth::id();
        $tabSessionId = bin2hex(random_bytes(24));
        session_write_close();
        ini_set('session.use_cookies', '0');
        ini_set('session.use_only_cookies', '0');
        session_id($tabSessionId);
        session_start();
        $_SESSION['parent_owner_return_id'] = $returnId;
        Auth::login($branchUser);
        $db->query(
            "INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)",
            [$parentStoreId, (int) $returnId, 'Branch Login', 'Logged into branch panel: ' . $branchUser['store_name']]
        );
        $this->flash('success', 'Logged into branch panel.');
        $this->redirect('home/index?tab_session=' . rawurlencode(session_id()));
    }

    public function updateBranch($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        if (Tenant::isBranchAccount()) {
            $this->flash('error', 'Branch details can only be edited from the parent account.');
            $this->redirect('users/index?tab=list');
        }

        $branchStoreId = (int) $id;
        $branch = $this->getBranchForCurrentStore($db, $branchStoreId);
        if (!$branch) {
            $this->flash('error', 'Branch account not found.');
            $this->redirect('users/index?tab=branch_list');
        }

        $branchName = trim((string) ($_POST['branch_name'] ?? ''));
        $branchCode = strtoupper(trim((string) ($_POST['branch_code'] ?? '')));
        $ownerName = trim((string) ($_POST['owner_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $status = ($_POST['status'] ?? 'active') === 'suspended' ? 'suspended' : 'active';
        $password = trim((string) ($_POST['password'] ?? ''));
        $adminUserId = (int) ($branch['admin_user_id'] ?? 0);

        if ($branchName === '' || $ownerName === '' || $email === '') {
            $this->flash('error', 'Branch name, admin name and admin email are required.');
            $this->redirect('users/branch/' . $branchStoreId);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Please enter a valid branch admin email.');
            $this->redirect('users/branch/' . $branchStoreId);
        }
        if ($password !== '' && strlen($password) < 6) {
            $this->flash('error', 'Password must be at least 6 characters.');
            $this->redirect('users/branch/' . $branchStoreId);
        }

        $existing = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1", [$email, $adminUserId]);
        if ($existing) {
            $this->flash('error', 'This email is already used by another account.');
            $this->redirect('users/branch/' . $branchStoreId);
        }

        try {
            $connection = $db->getConnection();
            $connection->beginTransaction();
            $connection->prepare(
                "UPDATE stores SET store_name = ?, owner_name = ?, email = ?, phone = ?, address = ?, status = ?, branch_code = ?, updated_at = NOW() WHERE id = ? AND parent_store_id = ?"
            )->execute([
                $branchName,
                $ownerName,
                $email,
                $phone !== '' ? $phone : null,
                $address !== '' ? $address : null,
                $status,
                $branchCode !== '' ? $branchCode : null,
                $branchStoreId,
                (int) Tenant::currentStoreId()
            ]);

            if ($adminUserId > 0) {
                if ($password !== '') {
                    $connection->prepare("UPDATE users SET name = ?, email = ?, status = ?, password = ?, updated_at = NOW() WHERE id = ? AND store_id = ?")
                        ->execute([$ownerName, $email, $status === 'active' ? 'active' : 'inactive', password_hash($password, PASSWORD_BCRYPT), $adminUserId, $branchStoreId]);
                } else {
                    $connection->prepare("UPDATE users SET name = ?, email = ?, status = ?, updated_at = NOW() WHERE id = ? AND store_id = ?")
                        ->execute([$ownerName, $email, $status === 'active' ? 'active' : 'inactive', $adminUserId, $branchStoreId]);
                }
            } else {
                if ($password === '') {
                    throw new Exception('Branch admin password required.');
                }
                $connection->prepare("INSERT INTO users (store_id, name, email, password, role, status, permissions) VALUES (?, ?, ?, ?, 'store_owner', ?, '')")
                    ->execute([$branchStoreId, $ownerName, $email, password_hash($password, PASSWORD_BCRYPT), $status === 'active' ? 'active' : 'inactive']);
            }

            $connection->prepare("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)")
                ->execute([(int) Tenant::currentStoreId(), Auth::id(), 'Branch', "Updated branch account: $branchName"]);

            $connection->commit();
            $this->flash('success', 'Branch details updated successfully.');
        } catch (Exception $e) {
            if (isset($connection) && $connection->inTransaction()) {
                $connection->rollBack();
            }
            $this->flash('error', $e->getMessage() === 'Branch admin password required.' ? 'Set a password to create the missing branch admin.' : 'Unable to update branch details.');
        }

        $this->redirect('users/branch/' . $branchStoreId);
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('users/index');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $id = (int) $id;
        $redirectTo = ($_POST['redirect_to'] ?? '') === 'view' ? 'users/userProfile/' . $id : 'users/index?tab=list';
        [$where, $params] = Tenant::storeFilter();
        $user = $db->fetch("SELECT * FROM users WHERE id = ? AND $where", array_merge([$id], $params));
        if (!$user) {
            $this->flash('error', 'User not found.');
            $this->redirect('users/index?tab=list');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = ($_POST['role'] ?? 'staff') === 'store_owner' ? 'store_owner' : 'staff';
        $permissions = $_POST['permissions'] ?? [];
        $password = trim($_POST['password'] ?? '');
        $protectedAdminId = $this->getProtectedAdminId($db);

        if ($name === '' || $email === '') {
            $this->flash('error', 'Name and email are required.');
            $this->redirect($redirectTo);
        }

        $existing = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ? AND $where", array_merge([$email, $id], $params));
        if ($existing) {
            $this->flash('error', 'Email already used by another user.');
            $this->redirect($redirectTo);
        }

        if ($id === $protectedAdminId) {
            $role = (string) $user['role'];
        }

        $permissionString = $role === 'store_owner' ? '' : $this->sanitizePermissions($permissions);
        if ($password !== '') {
            $db->query("UPDATE users SET name = ?, email = ?, role = ?, permissions = ?, password = ?, updated_at = NOW() WHERE id = ?", [
                $name, $email, $role, $permissionString, password_hash($password, PASSWORD_BCRYPT), $id
            ]);
        } else {
            $db->query("UPDATE users SET name = ?, email = ?, role = ?, permissions = ?, updated_at = NOW() WHERE id = ?", [
                $name, $email, $role, $permissionString, $id
            ]);
        }

        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), Auth::id(), 'User', "Updated user: $name"]);
        $this->flash('success', 'User updated.');
        $this->redirect($redirectTo);
    }

    public function delete($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $id = (int) $id;
        if ($id === (int) Auth::id()) {
            $this->flash('error', 'You cannot delete your own account.');
            $this->redirect('users/index');
        }

        $db = Database::getInstance();
        $protectedAdminId = $this->getProtectedAdminId($db);
        if ($id === $protectedAdminId) {
            $this->flash('error', 'Default admin role/account is protected.');
            $this->redirect('users/index');
        }

        [$where, $params] = Tenant::storeFilter();
        $user = $db->fetch("SELECT name FROM users WHERE id = ? AND $where", array_merge([$id], $params));
        if ($user) {
            $db->query("DELETE FROM users WHERE id = ? AND $where", array_merge([$id], $params));
            $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), Auth::id(), 'User', "Deleted user: " . $user['name']]);
            $this->flash('success', 'User deleted.');
        }
        $this->redirect('users/index');
    }

    private function sanitizePermissions($permissions) {
        $allowed = ['dashboard', 'pos', 'sales', 'inventory', 'customers', 'reports', 'expenses', 'credits', 'settings'];
        $selected = array_values(array_intersect($allowed, is_array($permissions) ? $permissions : []));
        return implode(',', $selected);
    }

    private function getProtectedAdminId($db) {
        [$where, $params] = Tenant::storeFilter();
        $admin = $db->fetch("SELECT id FROM users WHERE role IN ('store_owner') AND $where ORDER BY id ASC LIMIT 1", $params);
        return $admin ? (int) $admin['id'] : 0;
    }

    private function getBranches($db) {
        $storeId = (int) Tenant::currentStoreId();
        if ($storeId <= 0) {
            return [];
        }

        return $db->fetchAll(
            "
            SELECT
                s.*,
                u.name AS admin_name,
                u.email AS admin_email,
                u.created_at AS admin_created_at
            FROM stores s
            LEFT JOIN users u ON u.store_id = s.id AND u.role = 'store_owner'
            WHERE s.parent_store_id = ?
            ORDER BY s.created_at DESC, s.id DESC
            ",
            [$storeId]
        );
    }

    private function getBranchForCurrentStore($db, $branchStoreId) {
        $parentStoreId = (int) Tenant::currentStoreId();
        $branchStoreId = (int) $branchStoreId;
        if ($parentStoreId <= 0 || $branchStoreId <= 0) {
            return null;
        }

        return $db->fetch(
            "
            SELECT
                s.*,
                u.id AS admin_user_id,
                u.name AS admin_name,
                u.email AS admin_email,
                u.status AS admin_status,
                u.created_at AS admin_created_at
            FROM stores s
            LEFT JOIN users u ON u.store_id = s.id AND u.role = 'store_owner'
            WHERE s.id = ? AND s.parent_store_id = ?
            ORDER BY u.id ASC
            LIMIT 1
            ",
            [$branchStoreId, $parentStoreId]
        );
    }

    private function generateStoreUid($db) {
        do {
            $uid = 'store_' . bin2hex(random_bytes(8));
        } while ($db->fetch("SELECT id FROM stores WHERE store_uid = ? LIMIT 1", [$uid]));

        return $uid;
    }
}
