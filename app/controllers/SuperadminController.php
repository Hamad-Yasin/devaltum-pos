<?php

class SuperadminController extends Controller {

    public function __construct() {
        AuthMiddleware::handleSuperAdmin();
    }

    public function index() {
        $db = Database::getInstance();
        Billing::runDailyAutomation();

        $stats = [
            'total_stores' => (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores")['total'] ?? 0)),
            'active_stores' => (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores WHERE status = 'active'")['total'] ?? 0)),
            'suspended_stores' => (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores WHERE status = 'suspended'")['total'] ?? 0)),
            'expiring_soon' => (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores WHERE status = 'active' AND subscription_expires_at IS NOT NULL AND DATE(subscription_expires_at) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")['total'] ?? 0)),
            'expired_stores' => (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores WHERE status = 'expired' OR (subscription_expires_at IS NOT NULL AND subscription_expires_at < NOW())")['total'] ?? 0)),
            'monthly_revenue' => (float) (($db->fetch("SELECT SUM(amount) AS total FROM payment_transactions WHERE status = 'paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")['total'] ?? 0)),
            'total_revenue' => (float) (($db->fetch("SELECT SUM(amount) AS total FROM payment_transactions WHERE status = 'paid'")['total'] ?? 0)),
            'active_subscriptions' => (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores WHERE status = 'active' AND payment_status = 'paid'")['total'] ?? 0)),
        ];
        $stats['platform_health'] = $stats['total_stores'] > 0 ? round(($stats['active_stores'] / max(1, $stats['total_stores'])) * 100) : 0;
        $stats['risk_stores'] = (int) $stats['suspended_stores'] + (int) $stats['expired_stores'];

        $recentStores = $db->fetchAll("
            SELECT s.id, s.store_uid, s.store_name, s.owner_name, s.email, s.phone, s.status, s.subscription_expires_at, COALESCE(p.plan_name, s.plan_type) AS plan_name, s.created_at
            FROM stores s
            LEFT JOIN subscription_plans p ON p.id = s.current_plan_id
            ORDER BY s.id DESC
            LIMIT 12
        ");
        $recentPayments = $db->fetchAll("
            SELECT pt.amount, pt.status, pt.paid_at, pt.created_at, s.store_name, COALESCE(pm.method_name, pt.method) AS method_name
            FROM payment_transactions pt
            LEFT JOIN stores s ON s.id = pt.store_id
            LEFT JOIN payment_methods pm ON pm.id = CAST(pt.method AS UNSIGNED)
            ORDER BY COALESCE(pt.paid_at, pt.created_at) DESC, pt.id DESC
            LIMIT 6
        ");
        $expiringStores = $db->fetchAll("
            SELECT s.id, s.store_name, s.owner_name, s.subscription_expires_at, COALESCE(p.plan_name, s.plan_type) AS plan_name
            FROM stores s
            LEFT JOIN subscription_plans p ON p.id = s.current_plan_id
            WHERE s.status = 'active'
              AND s.subscription_expires_at IS NOT NULL
              AND DATE(s.subscription_expires_at) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY s.subscription_expires_at ASC
            LIMIT 5
        ");

        $this->view('superadmin/index', [
            'pageTitle' => 'Super Admin Dashboard',
            'activeMenu' => 'sa_dashboard',
            'stats' => $stats,
            'recentStores' => $recentStores,
            'recentPayments' => $recentPayments,
            'expiringStores' => $expiringStores
        ]);
    }

    public function stores() {
        $db = Database::getInstance();
        Billing::runDailyAutomation();
        $stores = $db->fetchAll("
            SELECT
                s.*,
                u.email AS owner_login_email,
                u.username AS owner_login_username,
                COALESCE(p.plan_name, s.plan_type) AS plan_name,
                parent_s.store_name AS parent_store_name,
                parent_s.owner_name AS parent_owner_name,
                parent_s.email AS parent_email,
                (SELECT COUNT(*) FROM stores child_s WHERE child_s.parent_store_id = s.id) AS branch_count
            FROM stores s
            LEFT JOIN users u ON u.store_id = s.id AND u.role = 'store_owner'
            LEFT JOIN subscription_plans p ON p.id = s.current_plan_id
            LEFT JOIN stores parent_s ON parent_s.id = s.parent_store_id
            ORDER BY s.id DESC
        ");
        $plans = $db->fetchAll("SELECT * FROM subscription_plans ORDER BY monthly_price ASC, id ASC");

        $stats = [
            'total_stores' => (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores")['total'] ?? 0)),
            'main_accounts' => (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores WHERE parent_store_id IS NULL OR parent_store_id = 0")['total'] ?? 0)),
            'branch_accounts' => (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores WHERE parent_store_id IS NOT NULL AND parent_store_id > 0")['total'] ?? 0)),
            'active_stores' => (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores WHERE status = 'active'")['total'] ?? 0)),
            'suspended_stores' => (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores WHERE status = 'suspended'")['total'] ?? 0)),
            'expired_stores' => (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores WHERE status = 'expired' OR (subscription_expires_at IS NOT NULL AND subscription_expires_at < NOW())")['total'] ?? 0)),
        ];

        $this->view('superadmin/stores', [
            'pageTitle' => 'Store Management',
            'activeMenu' => 'sa_stores',
            'stores' => $stores,
            'plans' => $plans,
            'stats' => $stats,
            'activeTab' => strtolower(trim((string) ($_GET['tab'] ?? 'stores'))) === 'stats' ? 'stats' : 'stores'
        ]);
    }

    public function storeProfile($id) {
        $id = (int) $id;
        $db = Database::getInstance();
        Billing::runDailyAutomation();

        $store = $db->fetch("
            SELECT
                s.*,
                u.id AS owner_user_id,
                u.email AS owner_login_email,
                u.username AS owner_login_username,
                u.password AS owner_password_hash,
                COALESCE(p.plan_name, s.plan_type) AS plan_name,
                parent_s.id AS parent_account_id,
                parent_s.store_name AS parent_store_name,
                parent_s.owner_name AS parent_owner_name,
                parent_s.email AS parent_email,
                parent_s.phone AS parent_phone,
                (SELECT COUNT(*) FROM stores child_s WHERE child_s.parent_store_id = s.id) AS branch_count
            FROM stores s
            LEFT JOIN users u ON u.store_id = s.id AND u.role = 'store_owner'
            LEFT JOIN subscription_plans p ON p.id = s.current_plan_id
            LEFT JOIN stores parent_s ON parent_s.id = s.parent_store_id
            WHERE s.id = ?
            LIMIT 1
        ", [$id]);
        if (!$store) {
            $this->flash('error', 'Store not found.');
            $this->redirect('superadmin/stores');
        }

        $plans = $db->fetchAll("SELECT * FROM subscription_plans ORDER BY monthly_price ASC, id ASC");
        $recentPayments = $db->fetchAll("
            SELECT pt.amount, pt.status, pt.paid_at, pt.next_expiry_date, COALESCE(pm.method_name, pt.method) AS method_name
            FROM payment_transactions pt
            LEFT JOIN payment_methods pm ON pm.id = CAST(pt.method AS UNSIGNED)
            WHERE pt.store_id = ?
            ORDER BY COALESCE(pt.paid_at, pt.created_at) DESC, pt.id DESC
            LIMIT 10
        ", [$id]);

        $this->view('superadmin/store_profile', [
            'pageTitle' => 'Store Profile',
            'activeMenu' => 'sa_stores',
            'store' => $store,
            'plans' => $plans,
            'recentPayments' => $recentPayments
        ]);
    }

    public function createStore() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $db = Database::getInstance();
        $connection = $db->getConnection();

        $storeName = trim((string) ($_POST['store_name'] ?? ''));
        $ownerName = trim((string) ($_POST['owner_name'] ?? ''));
        $storeEmail = trim((string) ($_POST['store_email'] ?? ($_POST['email'] ?? '')));
        $loginEmail = $storeEmail;
        $loginPassword = (string) ($_POST['password'] ?? '');
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $status = strtolower(trim((string) ($_POST['status'] ?? 'active')));
        $planId = (int) ($_POST['plan_id'] ?? 0);
        $startDate = trim((string) ($_POST['subscription_start_date'] ?? date('Y-m-d')));
        $expiryDate = trim((string) ($_POST['expiry_date'] ?? ''));
        $paymentStatus = strtolower(trim((string) ($_POST['payment_status'] ?? 'pending')));

        if ($storeName === '' || $ownerName === '' || $storeEmail === '') {
            $this->flash('error', 'Store name, owner name and store email are required.');
            $this->redirect('superadmin/stores');
        }
        if (trim($loginPassword) === '' || strlen($loginPassword) < 6) {
            $this->flash('error', 'Password is required and must be at least 6 characters.');
            $this->redirect('superadmin/stores');
        }
        if (!in_array($status, ['active', 'suspended', 'inactive', 'expired'], true)) {
            $status = 'active';
        }
        if (!in_array($paymentStatus, ['paid', 'pending'], true)) {
            $paymentStatus = 'pending';
        }
        if ($db->fetch("SELECT id FROM stores WHERE email = ? LIMIT 1", [$storeEmail])) {
            $this->flash('error', 'A store with this email already exists.');
            $this->redirect('superadmin/stores');
        }
        if ($db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$loginEmail])) {
            $this->flash('error', 'A user with this login email already exists.');
            $this->redirect('superadmin/stores');
        }

        $plan = $planId > 0 ? $db->fetch("SELECT * FROM subscription_plans WHERE id = ? LIMIT 1", [$planId]) : null;
        $planType = strtolower((string) ($plan['plan_name'] ?? 'free'));
        $duration = (int) ($plan['billing_duration_days'] ?? 30);
        $startAt = date('Y-m-d H:i:s', strtotime($startDate) ?: time());
        $expiry = $expiryDate !== ''
            ? date('Y-m-d H:i:s', strtotime($expiryDate . ' 23:59:59') ?: strtotime(Billing::calculateExpiryDate($startAt, $duration)))
            : Billing::calculateExpiryDate($startAt, $duration);

        $plainPassword = $loginPassword;
        $storeUid = 'STR-' . strtoupper(bin2hex(random_bytes(4)));
        $usernameSeed = strtolower(preg_replace('/[^a-z0-9]+/i', '', strstr($loginEmail, '@', true) ?: $ownerName));
        $username = ($usernameSeed !== '' ? $usernameSeed : 'store') . random_int(10, 99);

        try {
            $connection->beginTransaction();
            $db->query("
                INSERT INTO stores (store_uid, store_name, owner_name, email, phone, address, plan_type, status, current_plan_id, subscription_started_at, subscription_expires_at, payment_status, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [$storeUid, $storeName, $ownerName, $storeEmail, $phone, $address, $planType, $status, $planId ?: null, $startAt, $expiry, $paymentStatus]);
            $storeId = (int) $db->lastInsertId();

            $db->query("
                INSERT INTO users (store_id, name, username, email, password, role, status, permissions)
                VALUES (?, ?, ?, ?, ?, 'store_owner', 'active', '')
            ", [$storeId, $ownerName, $username, $loginEmail, password_hash($plainPassword, PASSWORD_BCRYPT)]);

            $defaultStoreSettings = [
                'company_name' => $storeName,
                'company_tagline' => 'Official point of sale system',
                'company_address' => $address,
                'whatsapp_number' => $phone,
                'whatsapp_message' => 'Thank you for shopping with us. Please contact our store for support or order assistance.',
                'currency_symbol' => (string) App::setting('currency_symbol', 'Rs'),
                'currency_code' => (string) App::setting('currency_code', 'PKR'),
                'receipt_footer' => (string) App::setting('receipt_footer', 'Thank you for choosing us. For support, please contact our store team.'),
            ];
            foreach ($defaultStoreSettings as $settingKey => $settingValue) {
                $db->query("
                    INSERT INTO store_settings (store_id, setting_key, setting_value)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ", [$storeId, $settingKey, $settingValue]);
            }

            $db->query("
                INSERT INTO subscriptions (store_id, plan_name, amount, billing_cycle, starts_at, expires_at, payment_method, payment_status)
                VALUES (?, ?, ?, 'monthly', ?, ?, 'manual', ?)
            ", [
                $storeId,
                (string) ($plan['plan_name'] ?? 'Free'),
                (float) ($plan['monthly_price'] ?? 0),
                $startAt,
                $expiry,
                $paymentStatus
            ]);

            $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [
                null,
                Auth::id(),
                'Store',
                "Created store {$storeName} ({$storeUid})"
            ]);
            $connection->commit();
        } catch (Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            $this->flash('error', 'Unable to create store: ' . $e->getMessage());
            $this->redirect('superadmin/stores');
        }

        if ($plan) {
            Billing::sendPlanActivationMessage($storeId, (string) $plan['plan_name'], $expiry);
        }

        $this->flash('success', 'Store and login credentials created successfully.');
        $this->redirect('superadmin/stores');
    }

    public function updateStore($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $id = (int) $id;
        $db = Database::getInstance();
        $store = $db->fetch("SELECT * FROM stores WHERE id = ? LIMIT 1", [$id]);
        if (!$store) {
            $this->flash('error', 'Store not found.');
            $this->redirect('superadmin/stores');
        }

        $storeName = trim((string) ($_POST['store_name'] ?? $store['store_name']));
        $ownerName = trim((string) ($_POST['owner_name'] ?? $store['owner_name']));
        $storeEmail = trim((string) ($_POST['store_email'] ?? ($_POST['email'] ?? $store['email'])));
        $phone = trim((string) ($_POST['phone'] ?? $store['phone']));
        $address = trim((string) ($_POST['address'] ?? $store['address']));
        $status = strtolower(trim((string) ($_POST['status'] ?? $store['status'])));
        $planId = (int) ($_POST['plan_id'] ?? (int) ($store['current_plan_id'] ?? 0));
        $paymentStatus = strtolower(trim((string) ($_POST['payment_status'] ?? $store['payment_status'])));
        $startDateRaw = trim((string) ($_POST['subscription_start_date'] ?? $store['subscription_started_at']));
        $expiryDateRaw = trim((string) ($_POST['expiry_date'] ?? ''));
        if (!in_array($status, ['active', 'suspended', 'inactive', 'expired'], true)) {
            $status = 'active';
        }
        if (!in_array($paymentStatus, ['paid', 'pending'], true)) {
            $paymentStatus = 'pending';
        }

        $existingStore = $db->fetch("SELECT id FROM stores WHERE email = ? AND id != ? LIMIT 1", [$storeEmail, $id]);
        if ($existingStore) {
            $this->flash('error', 'Store email already exists.');
            $this->redirectAfterStoreAction($id);
        }

        $plan = $planId > 0 ? $db->fetch("SELECT * FROM subscription_plans WHERE id = ? LIMIT 1", [$planId]) : null;
        $planType = strtolower((string) ($plan['plan_name'] ?? $store['plan_type']));
        $startAt = date('Y-m-d H:i:s', strtotime($startDateRaw) ?: time());
        $duration = (int) ($plan['billing_duration_days'] ?? 30);
        $expiry = $expiryDateRaw !== ''
            ? date('Y-m-d H:i:s', strtotime($expiryDateRaw . ' 23:59:59') ?: strtotime(Billing::calculateExpiryDate($startAt, $duration)))
            : Billing::calculateExpiryDate($startAt, $duration);

        $db->query("
            UPDATE stores
            SET store_name = ?, owner_name = ?, email = ?, phone = ?, address = ?, status = ?, current_plan_id = ?, plan_type = ?, subscription_started_at = ?, subscription_expires_at = ?, payment_status = ?, updated_at = NOW()
            WHERE id = ?
        ", [$storeName, $ownerName, $storeEmail, $phone, $address, $status, $planId ?: null, $planType, $startAt, $expiry, $paymentStatus, $id]);

        $db->query("
            UPDATE users
            SET name = ?, email = ?
            WHERE store_id = ? AND role = 'store_owner'
        ", [$ownerName, $storeEmail, $id]);

        $db->query("
            INSERT INTO store_settings (store_id, setting_key, setting_value)
            VALUES (?, 'company_name', ?), (?, 'company_address', ?), (?, 'whatsapp_number', ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ", [$id, $storeName, $id, $address, $id, $phone]);

        $this->flash('success', 'Store updated successfully.');
        $this->redirectAfterStoreAction($id);
    }

    public function updateStoreCredentials($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $id = (int) $id;
        $db = Database::getInstance();
        $owner = $db->fetch("
            SELECT u.id, s.email AS store_email
            FROM users u
            JOIN stores s ON s.id = u.store_id
            WHERE u.store_id = ? AND u.role = 'store_owner'
            LIMIT 1
        ", [$id]);
        if (!$owner) {
            $this->flash('error', 'Store owner account not found.');
            $this->redirectAfterStoreAction($id);
        }

        $password = trim((string) ($_POST['new_password'] ?? ''));
        if ($password === '') {
            $this->flash('error', 'Please enter a new password.');
            $this->redirectAfterStoreAction($id);
        }
        if (strlen($password) < 6) {
            $this->flash('error', 'New password must be at least 6 characters.');
            $this->redirectAfterStoreAction($id);
        }

        $db->query("UPDATE users SET email = ?, password = ?, updated_at = NOW() WHERE id = ?", [
            (string) $owner['store_email'],
            password_hash($password, PASSWORD_BCRYPT),
            (int) $owner['id']
        ]);
        $this->flash('success', 'Store credentials updated successfully.');
        $this->redirectAfterStoreAction($id);
    }

    public function resetStorePassword($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $id = (int) $id;
        $db = Database::getInstance();
        $owner = $db->fetch("SELECT id FROM users WHERE store_id = ? AND role = 'store_owner' LIMIT 1", [$id]);
        if (!$owner) {
            $this->flash('error', 'Store owner account not found.');
            $this->redirectAfterStoreAction($id);
        }

        $password = trim((string) ($_POST['new_password'] ?? ''));
        if ($password === '') {
            $this->flash('error', 'Please provide a new password.');
            $this->redirectAfterStoreAction($id);
        }
        if (strlen($password) < 6) {
            $this->flash('error', 'Password must be at least 6 characters.');
            $this->redirectAfterStoreAction($id);
        }

        $db->query("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?", [
            password_hash($password, PASSWORD_BCRYPT),
            (int) $owner['id']
        ]);

        $this->flash('success', 'Password reset successfully.');
        $this->redirectAfterStoreAction($id);
    }

    public function changeStoreStatus($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $id = (int) $id;
        $status = strtolower(trim((string) ($_POST['status'] ?? 'active')));
        if (!in_array($status, ['active', 'suspended', 'inactive', 'expired'], true)) {
            $status = 'active';
        }

        $db = Database::getInstance();
        $db->query("UPDATE stores SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $id]);
        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'success' => true,
                'status' => $status,
                'label' => ucfirst($status),
                'message' => 'Store status updated.'
            ]);
        }
        $this->flash('success', 'Store status updated.');
        $this->redirectAfterStoreAction($id);
    }

    public function deleteStore($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $id = (int) $id;
        $db = Database::getInstance();
        $store = $db->fetch("SELECT store_name FROM stores WHERE id = ?", [$id]);
        if (!$store) {
            $this->flash('error', 'Store not found.');
            $this->redirect('superadmin/stores');
        }

        $db->query("DELETE FROM stores WHERE id = ?", [$id]);
        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [null, Auth::id(), 'Store', "Deleted store {$store['store_name']}"]);
        $this->flash('success', 'Store deleted.');
        $this->redirect('superadmin/stores');
    }

    public function plans() {
        $db = Database::getInstance();
        $plans = $db->fetchAll("SELECT * FROM subscription_plans ORDER BY monthly_price ASC, id ASC");
        $planUsage = $db->fetchAll("
            SELECT
                s.store_name,
                COALESCE(p.plan_name, s.plan_type) AS plan_name,
                s.subscription_started_at,
                s.subscription_expires_at,
                CASE
                    WHEN s.subscription_expires_at IS NOT NULL AND s.subscription_expires_at < NOW() THEN 'expired'
                    ELSE s.status
                END AS subscription_status
            FROM stores s
            LEFT JOIN subscription_plans p ON p.id = s.current_plan_id
            ORDER BY s.store_name ASC
        ");

        $this->view('superadmin/plans', [
            'pageTitle' => 'Subscription Plans',
            'activeMenu' => 'sa_plans',
            'plans' => $plans,
            'planUsage' => $planUsage,
            'activeTab' => strtolower(trim((string) ($_GET['tab'] ?? 'plans'))) === 'usage' ? 'usage' : 'plans'
        ]);
    }

    public function createPlan() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $db = Database::getInstance();
        $planName = trim((string) ($_POST['plan_name'] ?? ''));
        $monthly = (float) ($_POST['monthly_price'] ?? 0);
        $duration = max(1, (int) ($_POST['billing_duration_days'] ?? 30));
        $staffLimit = max(0, (int) ($_POST['max_staff_limit'] ?? 0));
        $productLimit = max(0, (int) ($_POST['max_products_limit'] ?? 0));
        $status = strtolower(trim((string) ($_POST['status'] ?? 'active')));
        if ($planName === '') {
            $this->flash('error', 'Plan name is required.');
            $this->redirect('superadmin/plans');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $db->query("
            INSERT INTO subscription_plans (plan_name, monthly_price, yearly_price, billing_duration_days, max_staff_limit, max_products_limit, status, updated_at)
            VALUES (?, ?, 0, ?, ?, ?, ?, NOW())
        ", [$planName, $monthly, $duration, $staffLimit, $productLimit, $status]);
        $this->flash('success', 'Plan created.');
        $this->redirect('superadmin/plans');
    }

    public function updatePlan($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $id = (int) $id;
        $db = Database::getInstance();
        $planName = trim((string) ($_POST['plan_name'] ?? ''));
        $monthly = (float) ($_POST['monthly_price'] ?? 0);
        $duration = max(1, (int) ($_POST['billing_duration_days'] ?? 30));
        $staffLimit = max(0, (int) ($_POST['max_staff_limit'] ?? 0));
        $productLimit = max(0, (int) ($_POST['max_products_limit'] ?? 0));
        $status = strtolower(trim((string) ($_POST['status'] ?? 'active')));
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }
        $db->query("
            UPDATE subscription_plans
            SET plan_name = ?, monthly_price = ?, billing_duration_days = ?, max_staff_limit = ?, max_products_limit = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ", [$planName, $monthly, $duration, $staffLimit, $productLimit, $status, $id]);
        $this->flash('success', 'Plan updated.');
        $this->redirect('superadmin/plans');
    }

    public function deletePlan($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $db = Database::getInstance();
        $db->query("DELETE FROM subscription_plans WHERE id = ?", [(int) $id]);
        $this->flash('success', 'Plan deleted.');
        $this->redirect('superadmin/plans');
    }

    public function templates() {
        $db = Database::getInstance();
        ReceiptTemplates::syncDefaults($db);
        $templates = ReceiptTemplates::all(true);

        $this->view('superadmin/templates', [
            'pageTitle' => 'Templates',
            'activeMenu' => 'sa_templates',
            'templates' => $templates,
            'systemTemplates' => $templates
        ]);
    }

    public function saveTemplate() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['template_name'] ?? ''));
        $key = strtolower(trim((string) ($_POST['template_key'] ?? '')));
        $type = strtolower(trim((string) ($_POST['template_type'] ?? 'thermal')));
        if (!in_array($type, ['thermal', 'document', 'credit'], true)) {
            $type = 'thermal';
        }
        $defaultWidth = ($type === 'document') ? 148 : 80;
        $width = max(40, min(300, (int) ($_POST['paper_width_mm'] ?? $defaultWidth)));
        $accent = trim((string) ($_POST['accent_color'] ?? '#0f6dff'));
        $font = trim((string) ($_POST['font_family'] ?? 'Segoe UI'));
        $notes = trim((string) ($_POST['layout_notes'] ?? ''));
        $htmlBlock = trim((string) ($_POST['html_block'] ?? ''));
        $baseTemplate = ReceiptTemplates::sanitizeBase((string) ($_POST['base_template'] ?? ''));
        $status = strtolower(trim((string) ($_POST['status'] ?? 'active')));

        if ($name === '' && $htmlBlock !== '') {
            $name = ($type === 'thermal' ? 'Thermal Receipt ' : 'Document Invoice ') . date('M d h:i A');
        }
        if ($name === '') {
            $this->flash('error', 'Template name is required.');
            $this->redirect('superadmin/templates');
        }
        if ($key === '') {
            $key = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $key = trim($key, '-');
        }
        if ($key === '') {
            $key = ($type === 'thermal' ? 'thermal-' : 'doc-') . time();
        }
        $key = preg_replace('/[^a-z0-9_-]+/', '-', $key);
        if ((string) ($_POST['base_template'] ?? '') === '') {
            $baseTemplate = ReceiptTemplates::suggestBaseTemplate($notes . ' ' . $name, $type);
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
            $accent = '#0f6dff';
        }
        if (!in_array($status, ['draft', 'active', 'archived'], true)) {
            $status = 'active';
        }

        if ($id > 0) {
            $exists = $db->fetch("SELECT id FROM receipt_templates WHERE template_key = ? AND id != ? LIMIT 1", [$key, $id]);
            if ($exists) {
                $this->flash('error', 'Template key already exists.');
                $this->redirect('superadmin/templates');
            }
            $db->query("
                UPDATE receipt_templates
                SET template_key = ?, template_name = ?, template_type = ?, paper_width_mm = ?, accent_color = ?, font_family = ?, base_template = ?, html_block = ?, layout_notes = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ", [$key, $name, $type, $width, $accent, $font, $baseTemplate, $htmlBlock, $notes, $status, $id]);
            $this->flash('success', 'Template updated successfully.');
        } else {
            $exists = $db->fetch("SELECT id FROM receipt_templates WHERE template_key = ? LIMIT 1", [$key]);
            if ($exists) {
                $key .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            }
            $db->query("
                INSERT INTO receipt_templates (template_key, template_name, template_type, paper_width_mm, accent_color, font_family, base_template, html_block, is_system, layout_notes, status, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, NOW())
            ", [$key, $name, $type, $width, $accent, $font, $baseTemplate, $htmlBlock, $notes, $status]);
            $this->flash('success', 'Template created successfully.');
        }

        $this->redirect('superadmin/templates');
    }

    public function deleteTemplate($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $db = Database::getInstance();
        $template = $db->fetch("SELECT is_system FROM receipt_templates WHERE id = ? LIMIT 1", [(int) $id]);
        if (!empty($template['is_system'])) {
            $db->query("UPDATE receipt_templates SET status = 'archived', updated_at = NOW() WHERE id = ?", [(int) $id]);
            $this->flash('success', 'System template archived.');
            $this->redirect('superadmin/templates');
        }
        $db->query("DELETE FROM receipt_templates WHERE id = ?", [(int) $id]);
        $this->flash('success', 'Template deleted.');
        $this->redirect('superadmin/templates');
    }

    public function editTemplate($id = null) {
        $db = Database::getInstance();
        $template = null;
        if (is_numeric($id) && (int) $id > 0) {
            $template = $db->fetch("SELECT * FROM receipt_templates WHERE id = ? LIMIT 1", [(int) $id]);
        }
        if (!$template && is_string($id) && trim($id) !== '') {
            $cleanKey = ReceiptTemplates::sanitizeKey($id);
            $template = $db->fetch("SELECT * FROM receipt_templates WHERE template_key = ? LIMIT 1", [$cleanKey]);
        }
        if (!$template) {
            $cleanKey = ReceiptTemplates::sanitizeKey((string) $id);
            $defaults = ReceiptTemplates::defaults();
            if (isset($defaults[$cleanKey])) {
                $template = $defaults[$cleanKey];
            } else {
                $this->flash('error', 'Template not found.');
                $this->redirect('superadmin/templates');
            }
        }

        $this->view('superadmin/template_edit', [
            'pageTitle' => 'Edit ' . ($template['template_name'] ?? 'Template'),
            'activeMenu' => 'sa_templates',
            'template' => $template,
            'defaultMonoHtml' => ReceiptTemplates::defaultMonoHtml()
        ]);
    }

    public function saveBranding() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $showLogo = isset($_POST['devaltum_credit_show_logo']) && $_POST['devaltum_credit_show_logo'] === '1' ? '1' : '0';
        $brandPrefix = trim((string) ($_POST['devaltum_brand_prefix'] ?? 'Software By'));
        $brandName = trim((string) ($_POST['devaltum_brand_name'] ?? 'DevAltum'));
        $brandColor = trim((string) ($_POST['devaltum_brand_color'] ?? '#0f6dff'));
        $phoneText = trim((string) ($_POST['devaltum_phone_text'] ?? '+92 3101648600'));
        $fontFamily = trim((string) ($_POST['devaltum_credit_font_family'] ?? 'Segoe UI'));
        $fontWeight = trim((string) ($_POST['devaltum_credit_font_weight'] ?? '800'));
        $fontStyle = trim((string) ($_POST['devaltum_credit_font_style'] ?? 'normal'));
        $fontSize = max(8, min(24, (float) ($_POST['devaltum_credit_font_size'] ?? 12)));
        $logoSize = max(20, min(120, (int) ($_POST['devaltum_credit_logo_size'] ?? 44)));
        $alignment = in_array($_POST['devaltum_credit_alignment'] ?? '', ['left', 'center', 'right'], true) ? $_POST['devaltum_credit_alignment'] : 'center';
        $customLogo = trim((string) ($_POST['devaltum_custom_logo'] ?? ''));

        if (!empty($_FILES['branding_logo_file']['tmp_name']) && is_uploaded_file($_FILES['branding_logo_file']['tmp_name'])) {
            $mime = mime_content_type($_FILES['branding_logo_file']['tmp_name']);
            if (str_starts_with((string) $mime, 'image/')) {
                $data = file_get_contents($_FILES['branding_logo_file']['tmp_name']);
                $customLogo = 'data:' . $mime . ';base64,' . base64_encode($data);
            }
        } elseif (isset($_POST['remove_custom_logo']) && $_POST['remove_custom_logo'] === '1') {
            $customLogo = '';
        }

        App::saveSettings([
            'devaltum_credit_show_logo' => $showLogo,
            'devaltum_brand_prefix' => $brandPrefix !== '' ? $brandPrefix : 'Software By',
            'devaltum_brand_name' => $brandName !== '' ? $brandName : 'DevAltum',
            'devaltum_brand_color' => $brandColor !== '' ? $brandColor : '#0f6dff',
            'devaltum_phone_text' => $phoneText,
            'devaltum_credit_font_family' => $fontFamily,
            'devaltum_credit_font_weight' => $fontWeight,
            'devaltum_credit_font_style' => $fontStyle,
            'devaltum_credit_font_size' => (string) $fontSize,
            'devaltum_credit_logo_size' => (string) $logoSize,
            'devaltum_credit_alignment' => $alignment,
            'devaltum_custom_logo' => $customLogo,
        ]);

        $this->flash('success', 'Footer Branding customization saved successfully!');
        $this->redirect('superadmin/templates?tab=branding');
    }

    public function paymentMethods() {
        $db = Database::getInstance();
        $methods = $db->fetchAll("SELECT * FROM payment_methods ORDER BY method_type ASC, method_name ASC");
        $manualMethods = array_values(array_filter($methods, function ($method) {
            return ($method['method_type'] ?? '') === 'manual';
        }));
        $gatewayMethods = array_values(array_filter($methods, function ($method) {
            return ($method['method_type'] ?? '') === 'gateway';
        }));

        $settings = App::settings([
            'whatsapp_provider' => 'none',
            'meta_access_token' => '',
            'meta_phone_number_id' => '',
            'stripe_public_key' => '',
            'stripe_secret_key' => '',
            'paypal_client_id' => '',
            'paypal_secret' => ''
        ]);

        $this->view('superadmin/payment_methods', [
            'pageTitle' => 'Payment Methods',
            'activeMenu' => 'sa_methods',
            'methods' => $methods,
            'manualMethods' => $manualMethods,
            'gatewayMethods' => $gatewayMethods,
            'settings' => $settings,
            'activeTab' => strtolower(trim((string) ($_GET['tab'] ?? 'manual'))) === 'gateway' ? 'gateway' : 'manual'
        ]);
    }

    public function savePaymentMethod() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $db = Database::getInstance();
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['method_name'] ?? ''));
        $type = strtolower(trim((string) ($_POST['method_type'] ?? 'manual')));
        $account = trim((string) ($_POST['account_number'] ?? ''));
        $holder = trim((string) ($_POST['account_holder'] ?? ''));
        $logo = trim((string) ($_POST['method_logo'] ?? ''));
        $instructions = trim((string) ($_POST['instructions'] ?? ''));
        $status = strtolower(trim((string) ($_POST['status'] ?? 'active')));
        $removeQr = !empty($_POST['remove_qr']);

        if ($name === '') {
            $this->flash('error', 'Method name is required.');
            $this->redirect('superadmin/paymentMethods');
        }
        if (!in_array($type, ['manual', 'gateway'], true)) {
            $type = 'manual';
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        /* ── QR Code Upload ────────────────────────────── */
        $qrCodeFilename = null;  // null = no change
        $uploadDir = ROOT_PATH . '/public/uploads/qr_codes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!empty($_FILES['qr_code_file']['tmp_name']) && $_FILES['qr_code_file']['error'] === UPLOAD_ERR_OK) {
            $file     = $_FILES['qr_code_file'];
            $allowedMimes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'];
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mime     = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedMimes, true)) {
                $this->flash('error', 'Invalid QR image type. Allowed: PNG, JPG, WEBP, GIF.');
                $this->redirect('superadmin/paymentMethods');
            }
            if ($file['size'] > 2 * 1024 * 1024) {
                $this->flash('error', 'QR image must be under 2 MB.');
                $this->redirect('superadmin/paymentMethods');
            }

            // Delete old QR if updating
            if ($id > 0) {
                $old = $db->fetch("SELECT qr_code FROM payment_methods WHERE id = ? LIMIT 1", [$id]);
                if (!empty($old['qr_code']) && file_exists($uploadDir . $old['qr_code'])) {
                    @unlink($uploadDir . $old['qr_code']);
                }
            }

            $ext = match($mime) {
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
                default      => 'jpg',
            };
            $qrCodeFilename = 'qr_' . ($id > 0 ? $id : uniqid()) . '_' . time() . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $uploadDir . $qrCodeFilename);
        } elseif ($removeQr && $id > 0) {
            // Remove existing QR without uploading new
            $old = $db->fetch("SELECT qr_code FROM payment_methods WHERE id = ? LIMIT 1", [$id]);
            if (!empty($old['qr_code']) && file_exists($uploadDir . $old['qr_code'])) {
                @unlink($uploadDir . $old['qr_code']);
            }
            $qrCodeFilename = ''; // set to empty string in DB
        }

        if ($id > 0) {
            if ($qrCodeFilename !== null) {
                $db->query("
                    UPDATE payment_methods
                    SET method_name=?, method_type=?, account_number=?, account_holder=?, method_logo=?, qr_code=?, instructions=?, status=?, updated_at=NOW()
                    WHERE id=?
                ", [$name, $type, $account, $holder, $logo, $qrCodeFilename, $instructions, $status, $id]);
            } else {
                $db->query("
                    UPDATE payment_methods
                    SET method_name=?, method_type=?, account_number=?, account_holder=?, method_logo=?, instructions=?, status=?, updated_at=NOW()
                    WHERE id=?
                ", [$name, $type, $account, $holder, $logo, $instructions, $status, $id]);
            }
            $this->flash('success', 'Payment method updated.');
        } else {
            $db->query("
                INSERT INTO payment_methods (method_name, method_type, account_number, account_holder, method_logo, qr_code, instructions, status, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [$name, $type, $account, $holder, $logo, $qrCodeFilename ?? '', $instructions, $status]);
            $this->flash('success', 'Payment method created.');
        }

        $targetTab = $type === 'gateway' ? 'gateway' : 'manual';
        $this->redirect('superadmin/paymentMethods?tab=' . $targetTab . '_list');
    }

    public function deletePaymentMethod($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $db = Database::getInstance();
        $method = $db->fetch("SELECT method_type FROM payment_methods WHERE id = ? LIMIT 1", [(int) $id]);
        $db->query("DELETE FROM payment_methods WHERE id = ?", [(int) $id]);
        $this->flash('success', 'Payment method deleted.');
        $targetTab = (($method['method_type'] ?? '') === 'gateway') ? 'gateway' : 'manual';
        $this->redirect('superadmin/paymentMethods?tab=' . $targetTab);
    }

    public function saveGatewaySettings() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        App::saveSettings([
            'stripe_public_key' => trim((string) ($_POST['stripe_public_key'] ?? '')),
            'stripe_secret_key' => trim((string) ($_POST['stripe_secret_key'] ?? '')),
            'paypal_client_id' => trim((string) ($_POST['paypal_client_id'] ?? '')),
            'paypal_secret' => trim((string) ($_POST['paypal_secret'] ?? ''))
        ]);
        $this->flash('success', 'Gateway settings saved.');
        $this->redirect('superadmin/paymentMethods?tab=gateway');
    }

    public function saveWhatsappSettings() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $provider = strtolower(trim((string) ($_POST['whatsapp_provider'] ?? 'none')));
        if (!in_array($provider, ['none', 'meta'], true)) {
            $provider = 'none';
        }
        App::saveSettings([
            'whatsapp_provider' => $provider,
            'meta_access_token' => trim((string) ($_POST['meta_access_token'] ?? '')),
            'meta_phone_number_id' => trim((string) ($_POST['meta_phone_number_id'] ?? '')),
        ]);
        $this->flash('success', 'WhatsApp settings saved.');
        $this->redirect('superadmin/paymentMethods?tab=manual');
    }

    public function payments() {
        $db = Database::getInstance();
        $payments = $db->fetchAll("
            SELECT
                pt.*,
                s.store_name,
                s.next_monthly_charge_date,
                s.monthly_charge_amount,
                COALESCE(sp.plan_name, sub.plan_name, s.plan_type) AS plan_name,
                pm.method_name
            FROM payment_transactions pt
            JOIN stores s ON s.id = pt.store_id
            LEFT JOIN subscription_plans sp ON sp.id = pt.plan_id
            LEFT JOIN subscriptions sub ON sub.id = pt.subscription_id
            LEFT JOIN payment_methods pm ON pm.id = CAST(pt.method AS UNSIGNED)
            ORDER BY COALESCE(pt.paid_at, pt.created_at) DESC, pt.id DESC
            LIMIT 500
        ");
        $stores = $db->fetchAll("SELECT id, store_name FROM stores ORDER BY store_name ASC");
        $plans = $db->fetchAll("SELECT * FROM subscription_plans WHERE status = 'active' ORDER BY monthly_price ASC");
        $methods = $db->fetchAll("SELECT * FROM payment_methods WHERE status = 'active' ORDER BY method_name ASC");

        $summary = [
            'total_revenue' => (float) (($db->fetch("SELECT SUM(amount) AS total FROM payment_transactions WHERE status = 'paid'")['total'] ?? 0)),
            'monthly_revenue' => (float) (($db->fetch("SELECT SUM(amount) AS total FROM payment_transactions WHERE status = 'paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")['total'] ?? 0)),
            'active_subscriptions' => (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores WHERE status = 'active' AND payment_status = 'paid'")['total'] ?? 0)),
        ];

        $this->view('superadmin/payments', [
            'pageTitle' => 'Payment History',
            'activeMenu' => 'sa_payments',
            'payments' => $payments,
            'stores' => $stores,
            'plans' => $plans,
            'methods' => $methods,
            'summary' => $summary,
            'activeTab' => strtolower(trim((string) ($_GET['tab'] ?? 'history'))) === 'summary' ? 'summary' : 'history'
        ]);
    }

    public function recordPayment() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $db = Database::getInstance();
        $connection = $db->getConnection();

        $storeId = (int) ($_POST['store_id'] ?? 0);
        $planId = (int) ($_POST['plan_id'] ?? 0);
        $methodId = (int) ($_POST['payment_method_id'] ?? 0);
        $billingCycle = strtolower(trim((string) ($_POST['billing_cycle'] ?? 'monthly')));
        $paidDate = trim((string) ($_POST['payment_date'] ?? date('Y-m-d')));
        $amount = (float) ($_POST['amount_paid'] ?? 0);
        $notes = trim((string) ($_POST['notes'] ?? ''));

        $nextInstallmentDate = trim((string) ($_POST['next_monthly_charge_date'] ?? ''));
        $installmentAmount = (float) ($_POST['monthly_charge_amount'] ?? 0);

        $store = $db->fetch("SELECT * FROM stores WHERE id = ? LIMIT 1", [$storeId]);
        $plan = $db->fetch("SELECT * FROM subscription_plans WHERE id = ? LIMIT 1", [$planId]);
        $method = $db->fetch("SELECT * FROM payment_methods WHERE id = ? LIMIT 1", [$methodId]);
        if (!$store || !$plan || !$method) {
            $this->flash('error', 'Invalid store, plan, or payment method.');
            $this->redirect('superadmin/payments');
        }

        if (!in_array($billingCycle, ['monthly', 'yearly'], true)) {
            $billingCycle = 'monthly';
        }
        $paidAt = date('Y-m-d H:i:s', strtotime($paidDate) ?: time());
        if ($amount <= 0) {
            $amount = $billingCycle === 'yearly' ? (float) $plan['yearly_price'] : (float) $plan['monthly_price'];
        }

        $durationDays = $billingCycle === 'yearly'
            ? max(365, (int) $plan['billing_duration_days'] * 12)
            : max(1, (int) $plan['billing_duration_days']);

        $base = $paidAt;
        if (!empty($store['subscription_expires_at']) && strtotime((string) $store['subscription_expires_at']) > strtotime($paidAt)) {
            $base = (string) $store['subscription_expires_at'];
        }
        $newExpiry = Billing::calculateExpiryDate($base, $durationDays);

        try {
            $connection->beginTransaction();
            $db->query("
                INSERT INTO subscriptions (store_id, plan_name, amount, billing_cycle, starts_at, expires_at, payment_method, payment_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'paid')
            ", [$storeId, $plan['plan_name'], $amount, $billingCycle, $paidAt, $newExpiry, $method['method_name']]);
            $subscriptionId = (int) $db->lastInsertId();

            $db->query("
                INSERT INTO payment_transactions (store_id, subscription_id, plan_id, method, gateway_ref, amount, status, paid_at, next_expiry_date, notes, created_at)
                VALUES (?, ?, ?, ?, NULL, ?, 'paid', ?, ?, ?, NOW())
            ", [$storeId, $subscriptionId, $planId, (string) $methodId, $amount, $paidAt, $newExpiry, $notes ?: null]);

            $nextInstallmentDateSql = $nextInstallmentDate !== '' ? $nextInstallmentDate : null;
            $installmentAmountSql = $installmentAmount > 0 ? $installmentAmount : null;

            $db->query("
                UPDATE stores
                SET current_plan_id = ?, plan_type = ?, payment_status = 'paid', status = 'active', 
                    subscription_started_at = ?, subscription_expires_at = ?, 
                    next_monthly_charge_date = ?, monthly_charge_amount = ?, updated_at = NOW()
                WHERE id = ?
            ", [$planId, strtolower((string) $plan['plan_name']), $paidAt, $newExpiry, $nextInstallmentDateSql, $installmentAmountSql, $storeId]);

            $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [
                null,
                Auth::id(),
                'Billing',
                "Payment recorded for {$store['store_name']} | " . App::money($amount)
            ]);
            $connection->commit();
        } catch (Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            $this->flash('error', 'Payment record failed: ' . $e->getMessage());
            $this->redirect('superadmin/payments');
        }

        // 1. Send automatic professional HTML email receipt
        $receiptSubject = "Payment Receipt: Subscription Installment Paid for {$store['store_name']}";
        $receiptBody = Billing::buildReceiptEmailTemplate($store['owner_name'], $store['store_name'], $amount, date('Y-m-d', strtotime($paidAt)), $nextInstallmentDateSql);
        Billing::sendEmail($store['email'], $receiptSubject, $receiptBody);

        $this->redirect('superadmin/payments?tab=table');
    }

    public function editPayment($id = 0) {
        $db = Database::getInstance();
        $id = (int) $id;
        if ($id <= 0) {
            $this->flash('error', 'Invalid payment ID.');
            $this->redirect('superadmin/payments?tab=table');
        }

        $payment = $db->fetch("
            SELECT
                pt.*,
                s.store_name,
                s.status as store_status,
                s.current_plan_id,
                s.subscription_expires_at,
                s.next_monthly_charge_date,
                s.monthly_charge_amount,
                COALESCE(sp.plan_name, sub.plan_name, s.plan_type, 'Standard Plan') AS plan_name,
                COALESCE(sp.monthly_price, s.monthly_charge_amount, 2500) as plan_monthly_price,
                COALESCE(sp.yearly_price, 25000) as plan_yearly_price,
                pm.method_name,
                pm.method_logo
            FROM payment_transactions pt
            JOIN stores s ON s.id = pt.store_id
            LEFT JOIN subscription_plans sp ON sp.id = COALESCE(pt.plan_id, s.current_plan_id)
            LEFT JOIN subscriptions sub ON sub.id = pt.subscription_id
            LEFT JOIN payment_methods pm ON (pm.id = CAST(pt.method AS UNSIGNED) OR LOWER(pm.method_name) = LOWER(pt.method))
            WHERE pt.id = ?
            LIMIT 1
        ", [$id]);

        if (!$payment) {
            $this->flash('error', 'Payment record not found.');
            $this->redirect('superadmin/payments?tab=table');
        }

        $plans = $db->fetchAll("SELECT * FROM subscription_plans WHERE status = 'active' ORDER BY monthly_price ASC");
        $methods = $db->fetchAll("SELECT * FROM payment_methods WHERE status = 'active' ORDER BY method_type ASC, method_name ASC");

        $this->view('superadmin/edit_payment', [
            'pageTitle' => 'Edit Payment Record — DevAltum POS',
            'activeMenu' => 'sa_payments',
            'payment' => $payment,
            'plans' => $plans,
            'methods' => $methods
        ]);
    }

    public function updatePaymentRecord() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $db = Database::getInstance();
        $id     = (int) ($_POST['id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $status = strtolower(trim((string) ($_POST['status'] ?? 'paid')));
        $method = trim((string) ($_POST['method'] ?? ''));
        $planId = (int) ($_POST['plan_id'] ?? 0);
        $notes  = trim((string) ($_POST['notes'] ?? ''));
        $paidAt = trim((string) ($_POST['paid_at'] ?? ''));
        $expiry = trim((string) ($_POST['next_expiry_date'] ?? ''));

        $nextInstallmentDate = trim((string) ($_POST['next_monthly_charge_date'] ?? ''));
        $installmentAmount = (float) ($_POST['monthly_charge_amount'] ?? 0);

        if ($id <= 0) {
            $this->flash('error', 'Invalid payment record.');
            $this->redirect('superadmin/payments?tab=table');
        }
        if (!in_array($status, ['paid', 'approved', 'completed', 'pending', 'failed', 'rejected'], true)) {
            $status = 'paid';
        }

        $paidAtFull = $paidAt ? date('Y-m-d H:i:s', strtotime($paidAt)) : date('Y-m-d H:i:s');
        $expiryFull = $expiry ? date('Y-m-d 23:59:59', strtotime($expiry)) : null;

        $db->query("
            UPDATE payment_transactions
            SET amount=?, status=?, method=COALESCE(NULLIF(?, ''), method), plan_id=COALESCE(NULLIF(?, 0), plan_id), paid_at=?, next_expiry_date=?, notes=?
            WHERE id=?
        ", [$amount, $status, $method, $planId, $paidAtFull, $expiryFull, $notes ?: null, $id]);

        $tx = $db->fetch("SELECT pt.*, s.store_name, s.owner_name, s.email, s.phone FROM payment_transactions pt JOIN stores s ON s.id = pt.store_id WHERE pt.id=? LIMIT 1", [$id]);

        // Sync store status and subscription dates if payment is approved/paid
        if (in_array($status, ['paid', 'approved', 'completed'], true) && $tx) {
            $nextInstallmentDateSql = $nextInstallmentDate !== '' ? $nextInstallmentDate : null;
            $installmentAmountSql = $installmentAmount > 0 ? $installmentAmount : null;
            
            $db->query("
                UPDATE stores 
                SET status = 'active',
                    payment_status = 'paid',
                    current_plan_id = COALESCE(NULLIF(?, 0), current_plan_id),
                    subscription_expires_at = COALESCE(?, subscription_expires_at), 
                    next_monthly_charge_date = ?,
                    monthly_charge_amount = ?,
                    updated_at=NOW() 
                WHERE id=?
            ", [$planId, $expiryFull, $nextInstallmentDateSql, $installmentAmountSql, $tx['store_id']]);

            // Notify store owner
            if ($expiryFull) {
                Billing::sendPaymentConfirmationMessage((int)$tx['store_id'], $amount, (string)$expiryFull);
            }
        }

        $this->flash('success', 'Payment record updated successfully.');
        $this->redirect('superadmin/payments?tab=table');
    }

    public function deletePaymentRecord($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $db = Database::getInstance();
        $id = (int) $id;
        if ($id <= 0) {
            $this->flash('error', 'Invalid payment ID.');
            $this->redirect('superadmin/payments?tab=table');
        }
        $db->query("DELETE FROM payment_transactions WHERE id=?", [$id]);
        $this->flash('success', 'Payment record deleted.');
        $this->redirect('superadmin/payments?tab=table');
    }

    public function sendManualReceipt($paymentId) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        
        $db = Database::getInstance();
        $paymentId = (int) $paymentId;
        $channel = trim((string) ($_GET['channel'] ?? 'email'));

        $payment = $db->fetch("
            SELECT pt.*, s.owner_name, s.store_name, s.email, s.phone, s.next_monthly_charge_date
            FROM payment_transactions pt
            JOIN stores s ON s.id = pt.store_id
            WHERE pt.id = ?
            LIMIT 1
        ", [$paymentId]);

        if (!$payment) {
            $this->flash('error', 'Payment transaction not found.');
            $this->redirect('superadmin/payments?tab=table');
        }

        if ($channel === 'whatsapp') {
            $message = "Assalam o Alaikum {$payment['owner_name']},\n\nPayment received: " . App::money($payment['amount']) . ".\n\nThank you for using our POS system.";
            if ($payment['next_monthly_charge_date']) {
                $message .= "\nNext Installment Due: " . date('Y-m-d', strtotime($payment['next_monthly_charge_date']));
            }
            $targetPhone = $payment['phone'];
            $setting = $db->fetch("SELECT setting_value FROM store_settings WHERE store_id = ? AND setting_key = 'whatsapp_number' LIMIT 1", [$payment['store_id']]);
            if (!empty($setting['setting_value'])) {
                $targetPhone = $setting['setting_value'];
            }

            $sent = Billing::sendWhatsapp($targetPhone, $message);
            if ($sent) {
                $this->flash('success', 'WhatsApp confirmation sent successfully.');
            } else {
                // If Meta WhatsApp cloud is not configured, fallback to direct web WhatsApp link!
                $directUrl = "https://wa.me/" . preg_replace('/[^0-9]/', '', $targetPhone) . "?text=" . urlencode($message);
                $this->redirectUrl($directUrl);
                return;
            }
        } else {
            // Email channel
            $subject = "Payment Receipt: Subscription Installment Paid for {$payment['store_name']}";
            $body = Billing::buildReceiptEmailTemplate($payment['owner_name'], $payment['store_name'], $payment['amount'], date('Y-m-d', strtotime($payment['paid_at'])), $payment['next_monthly_charge_date']);
            $sent = Billing::sendEmail($payment['email'], $subject, $body);
            if ($sent) {
                $this->flash('success', 'Email receipt sent successfully.');
            } else {
                $this->flash('error', 'Could not send email receipt. Please check mail configuration.');
            }
        }
        $this->redirect('superadmin/payments?tab=table');
    }

    public function sendManualReminder($paymentId) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $paymentId = (int) $paymentId;
        $channel = trim((string) ($_GET['channel'] ?? 'email'));

        $payment = $db->fetch("
            SELECT pt.*, s.owner_name, s.store_name, s.email, s.phone, s.next_monthly_charge_date, s.monthly_charge_amount
            FROM payment_transactions pt
            JOIN stores s ON s.id = pt.store_id
            WHERE pt.id = ?
            LIMIT 1
        ", [$paymentId]);

        if (!$payment) {
            $this->flash('error', 'Payment transaction not found.');
            $this->redirect('superadmin/payments?tab=table');
        }

        $dueDate = !empty($payment['next_monthly_charge_date']) ? date('M d, Y', strtotime($payment['next_monthly_charge_date'])) : 'Not set';
        $amount = (float) ($payment['monthly_charge_amount'] ?? $payment['amount']);

        if ($channel === 'whatsapp') {
            $message = "Assalam o Alaikum {$payment['owner_name']},\n\nThis is a friendly reminder that your monthly POS installment of " . App::money($amount) . " is due on {$dueDate} for your store: {$payment['store_name']}.\n\nPlease clear your payment to keep your POS active.\n\nThank you,\nDevAltum POS";
            $targetPhone = $payment['phone'];
            $setting = $db->fetch("SELECT setting_value FROM store_settings WHERE store_id = ? AND setting_key = 'whatsapp_number' LIMIT 1", [$payment['store_id']]);
            if (!empty($setting['setting_value'])) {
                $targetPhone = $setting['setting_value'];
            }

            $sent = Billing::sendWhatsapp($targetPhone, $message);
            if ($sent) {
                $this->flash('success', 'WhatsApp reminder sent successfully.');
            } else {
                // If Meta WhatsApp cloud is not configured, fallback to direct web WhatsApp link!
                $directUrl = "https://wa.me/" . preg_replace('/[^0-9]/', '', $targetPhone) . "?text=" . urlencode($message);
                $this->redirectUrl($directUrl);
                return;
            }
        } else {
            // Email channel
            $subject = "Payment Reminder: Upcoming Installment for {$payment['store_name']}";
            $body = Billing::buildReminderEmailTemplate($payment['owner_name'], $payment['store_name'], $amount, $dueDate);
            $sent = Billing::sendEmail($payment['email'], $subject, $body);
            if ($sent) {
                $this->flash('success', 'Email reminder sent successfully.');
            } else {
                $this->flash('error', 'Could not send email reminder. Please check mail configuration.');
            }
        }
        $this->redirect('superadmin/payments?tab=table');
    }

    private function redirectUrl($url) {
        header('Location: ' . $url);
        exit;
    }

    public function runAutomation() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        Billing::runDailyAutomation();
        $this->flash('success', 'Billing automation executed successfully.');
        $this->redirect('superadmin/index');
    }

    public function userAccess() {
        $db = Database::getInstance();
        $selectedStoreId = max(0, (int) ($_GET['store_id'] ?? 0));

        $stores = $db->fetchAll("
            SELECT id, store_name
            FROM stores
            ORDER BY store_name ASC
        ");

        $where = "u.role != 'super_admin'";
        $params = [];
        if ($selectedStoreId > 0) {
            $where .= " AND u.store_id = ?";
            $params[] = $selectedStoreId;
        }

        $users = $db->fetchAll("
            SELECT u.id, u.store_id, u.name, u.email, u.role, u.permissions, u.status, u.created_at, s.store_name
            FROM users u
            LEFT JOIN stores s ON s.id = u.store_id
            WHERE $where
            ORDER BY u.id DESC
        ", $params);

        $permissions = $this->permissionLabels();

        $this->view('superadmin/user_access', [
            'pageTitle' => 'User Access',
            'activeMenu' => 'sa_users',
            'stores' => $stores,
            'users' => $users,
            'permissions' => $permissions,
            'selectedStoreId' => $selectedStoreId
        ]);
    }

    public function createUserAccess() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $storeId = max(0, (int) ($_POST['store_id'] ?? 0));
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));
        $role = strtolower(trim((string) ($_POST['role'] ?? 'staff')));
        $status = strtolower(trim((string) ($_POST['status'] ?? 'active')));
        $permissions = $_POST['permissions'] ?? [];

        if ($storeId <= 0 || $name === '' || $email === '' || $password === '') {
            $this->flash('error', 'Store, name, email and password are required.');
            $this->redirect('superadmin/userAccess');
        }
        if (strlen($password) < 6) {
            $this->flash('error', 'Password must be at least 6 characters.');
            $this->redirect('superadmin/userAccess');
        }
        if (!in_array($role, ['store_owner', 'staff'], true)) {
            $role = 'staff';
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $store = $db->fetch("SELECT id FROM stores WHERE id = ? LIMIT 1", [$storeId]);
        if (!$store) {
            $this->flash('error', 'Selected store not found.');
            $this->redirect('superadmin/userAccess');
        }

        $exists = $db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$email]);
        if ($exists) {
            $this->flash('error', 'Email already exists.');
            $this->redirect('superadmin/userAccess?store_id=' . $storeId);
        }

        $permissionString = $role === 'store_owner' ? '' : $this->sanitizePermissions($permissions);
        $db->query("
            INSERT INTO users (store_id, name, email, password, role, status, permissions, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ", [$storeId, $name, $email, password_hash($password, PASSWORD_BCRYPT), $role, $status, $permissionString]);

        $this->flash('success', 'User access created successfully.');
        $this->redirect('superadmin/userAccess?store_id=' . $storeId);
    }

    public function updateUserAccess($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $id = (int) $id;
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE id = ? AND role != 'super_admin' LIMIT 1", [$id]);
        if (!$user) {
            $this->flash('error', 'User not found.');
            $this->redirect('superadmin/userAccess');
        }

        $storeId = max(0, (int) ($_POST['store_id'] ?? (int) $user['store_id']));
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));
        $role = strtolower(trim((string) ($_POST['role'] ?? (string) $user['role'])));
        $status = strtolower(trim((string) ($_POST['status'] ?? (string) $user['status'])));
        $permissions = $_POST['permissions'] ?? [];

        if ($storeId <= 0 || $name === '' || $email === '') {
            $this->flash('error', 'Store, name and email are required.');
            $this->redirect('superadmin/userAccess?store_id=' . (int) $user['store_id']);
        }
        if (!in_array($role, ['store_owner', 'staff'], true)) {
            $role = 'staff';
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }
        if ($password !== '' && strlen($password) < 6) {
            $this->flash('error', 'Password must be at least 6 characters.');
            $this->redirect('superadmin/userAccess?store_id=' . $storeId);
        }

        $store = $db->fetch("SELECT id FROM stores WHERE id = ? LIMIT 1", [$storeId]);
        if (!$store) {
            $this->flash('error', 'Selected store not found.');
            $this->redirect('superadmin/userAccess');
        }

        $exists = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1", [$email, $id]);
        if ($exists) {
            $this->flash('error', 'Email already exists.');
            $this->redirect('superadmin/userAccess?store_id=' . $storeId);
        }

        $permissionString = $role === 'store_owner' ? '' : $this->sanitizePermissions($permissions);
        if ($password !== '') {
            $db->query("
                UPDATE users
                SET store_id = ?, name = ?, email = ?, password = ?, role = ?, status = ?, permissions = ?, updated_at = NOW()
                WHERE id = ?
            ", [$storeId, $name, $email, password_hash($password, PASSWORD_BCRYPT), $role, $status, $permissionString, $id]);
        } else {
            $db->query("
                UPDATE users
                SET store_id = ?, name = ?, email = ?, role = ?, status = ?, permissions = ?, updated_at = NOW()
                WHERE id = ?
            ", [$storeId, $name, $email, $role, $status, $permissionString, $id]);
        }

        $this->flash('success', 'User access updated successfully.');
        $this->redirect('superadmin/userAccess?store_id=' . $storeId);
    }

    public function deleteUserAccess($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $id = (int) $id;
        $db = Database::getInstance();
        $user = $db->fetch("SELECT id, store_id, role FROM users WHERE id = ? AND role != 'super_admin' LIMIT 1", [$id]);
        if (!$user) {
            $this->flash('error', 'User not found.');
            $this->redirect('superadmin/userAccess');
        }

        if ((string) $user['role'] === 'store_owner') {
            $ownerCount = (int) (($db->fetch("SELECT COUNT(*) AS total FROM users WHERE store_id = ? AND role = 'store_owner'", [(int) $user['store_id']])['total'] ?? 0));
            if ($ownerCount <= 1) {
                $this->flash('error', 'At least one admin is required for each store.');
                $this->redirect('superadmin/userAccess?store_id=' . (int) $user['store_id']);
            }
        }

        $db->query("DELETE FROM users WHERE id = ?", [$id]);
        $this->flash('success', 'User access deleted.');
        $this->redirect('superadmin/userAccess?store_id=' . (int) $user['store_id']);
    }

    public function loginAsStore($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $id = (int) $id;
        $db = Database::getInstance();
        $user = $db->fetch("
            SELECT u.*, s.store_name
            FROM users u
            JOIN stores s ON s.id = u.store_id
            WHERE u.store_id = ? AND u.role = 'store_owner' AND u.status = 'active'
            LIMIT 1
        ", [$id]);
        if (!$user) {
            $this->flash('error', 'Store owner account not found.');
            $this->redirect('superadmin/storeProfile/' . $id);
        }

        $returnId = Auth::id();
        $tabSessionId = bin2hex(random_bytes(24));
        session_write_close();
        ini_set('session.use_cookies', '0');
        ini_set('session.use_only_cookies', '0');
        session_id($tabSessionId);
        session_start();
        $_SESSION['superadmin_return_id'] = $returnId;
        Auth::login($user);
        $this->flash('success', 'Logged in as store owner.');
        $this->redirect('home/index?tab_session=' . rawurlencode(session_id()));
    }

    private function redirectAfterStoreAction($storeId) {
        if ((string) ($_POST['return_to_profile'] ?? '') === '1') {
            $this->redirect('superadmin/storeProfile/' . (int) $storeId);
        }
        $this->redirect('superadmin/stores');
    }

    private function permissionLabels() {
        return [
            'dashboard' => 'Dashboard',
            'pos' => 'POS Checkout',
            'sales' => 'Sales',
            'quotations' => 'Quotations & Estimates',
            'inventory' => 'Inventory',
            'transfers' => 'Stock Transfers',
            'suppliers' => 'Suppliers & Khata',
            'customers' => 'Customers',
            'reports' => 'Reports',
            'expenses' => 'Expenses',
            'credits' => 'Credit Dues',
            'settings' => 'Settings'
        ];
    }

    private function sanitizePermissions($permissions) {
        $allowed = array_keys($this->permissionLabels());
        $selected = array_values(array_intersect($allowed, is_array($permissions) ? $permissions : []));
        return implode(',', $selected);
    }
}
