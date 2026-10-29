<?php

class PlansController extends Controller {

    public function __construct() {
        // We handle authentication per-action to allow expired stores and public payment submissions
    }

    /* ─────────────────────────────────────────
       Plan Status Page (Logged-in admin only)
    ───────────────────────────────────────── */
    public function index() {
        AuthMiddleware::handleAdmin();

        if (Tenant::isBranchAccount()) {
            $this->flash('error', 'Plan status is managed from the parent account.');
            $this->redirect('home/index');
        }

        $db      = Database::getInstance();
        $storeId = (int) Tenant::currentStoreId();

        $store = $db->fetch("
            SELECT
                s.*,
                COALESCE(p.plan_name, s.plan_type, 'Free') AS plan_name,
                COALESCE(p.monthly_price, 0)               AS monthly_price,
                COALESCE(p.billing_duration_days, 30)      AS billing_duration_days,
                COALESCE(p.max_staff_limit, 0)             AS max_staff_limit,
                COALESCE(p.max_products_limit, 0)          AS max_products_limit
            FROM stores s
            LEFT JOIN subscription_plans p ON p.id = s.current_plan_id
            WHERE s.id = ?
            LIMIT 1
        ", [$storeId]);

        if (!$store) {
            $this->flash('error', 'Store subscription data not found.');
            $this->redirect('home/index');
        }

        $latestPayment = $db->fetch("
            SELECT amount, status, paid_at, next_expiry_date
            FROM payment_transactions
            WHERE store_id = ?
            ORDER BY COALESCE(paid_at, created_at) DESC, id DESC
            LIMIT 1
        ", [$storeId]);

        $staffUsed    = (int) (($db->fetch("SELECT COUNT(*) AS total FROM users WHERE store_id = ? AND role = 'staff'", [$storeId])['total'] ?? 0));
        $productsUsed = (int) (($db->fetch("SELECT COUNT(*) AS total FROM products WHERE store_id = ?", [$storeId])['total'] ?? 0));
        $branchesUsed = (int) (($db->fetch("SELECT COUNT(*) AS total FROM stores WHERE parent_store_id = ?", [$storeId])['total'] ?? 0));

        $nowTs    = time();
        $expiryTs = !empty($store['subscription_expires_at']) ? strtotime((string) $store['subscription_expires_at']) : null;
        $startTs  = !empty($store['subscription_started_at'])  ? strtotime((string) $store['subscription_started_at'])  : null;

        $secondsRemaining = ($expiryTs && $expiryTs > $nowTs) ? ($expiryTs - $nowTs) : 0;
        $remainingDays    = (int) floor($secondsRemaining / 86400);

        $progressPercent = 0;
        if ($startTs && $expiryTs && $expiryTs > $startTs) {
            $totalDuration   = $expiryTs - $startTs;
            $elapsed         = max(0, min($nowTs - $startTs, $totalDuration));
            $progressPercent = (int) round(($elapsed / $totalDuration) * 100);
        }

        $paymentMethods = $db->fetchAll("SELECT * FROM payment_methods WHERE status = 'active' ORDER BY method_type ASC, method_name ASC");

        $this->view('../../modules/plans/views/index', [
            'pageTitle'        => 'Plan Status',
            'activeMenu'       => 'plan_status',
            'store'            => $store,
            'latestPayment'    => $latestPayment,
            'paymentMethods'   => $paymentMethods,
            'secondsRemaining' => $secondsRemaining,
            'remainingDays'    => $remainingDays,
            'progressPercent'  => max(0, min(100, $progressPercent)),
            'usage'            => [
                'staff'    => $staffUsed,
                'products' => $productsUsed,
                'branches' => $branchesUsed,
            ],
        ]);
    }

    /* ─────────────────────────────────────────
       Pay Here / Renew Page (Public & Logged-in)
    ───────────────────────────────────────── */
    public function pay() {
        $db = Database::getInstance();
        $store = $this->resolveStoreContext($db);
        $identifier = trim((string) ($_GET['identifier'] ?? $_GET['email'] ?? ''));

        // All active plans and payment methods
        $plans = $db->fetchAll("SELECT * FROM subscription_plans WHERE status = 'active' ORDER BY monthly_price ASC");
        $paymentMethods = $db->fetchAll("SELECT * FROM payment_methods WHERE status = 'active' ORDER BY method_type ASC, method_name ASC");
        $adminWhatsapp = (string) App::setting('whatsapp_number', '+923101648600');

        $this->view('../../modules/plans/views/pay', [
            'pageTitle'      => 'Renew Plan & Pay Here — DevAltum POS',
            'activeMenu'     => 'plan_status',
            'store'          => $store,
            'plans'          => $plans,
            'paymentMethods' => $paymentMethods,
            'adminWhatsapp'  => $adminWhatsapp,
            'identifier'     => $identifier,
            'isLoggedIn'     => Auth::check()
        ]);
    }

    /* ─────────────────────────────────────────
       Single Payment Method View
     ───────────────────────────────────────── */
    public function method($id = null) {
        $db = Database::getInstance();
        $methodId = (int) ($id ?? ($_GET['id'] ?? 0));

        $method = $db->fetch("SELECT * FROM payment_methods WHERE id = ? AND status = 'active' LIMIT 1", [$methodId]);
        if (!$method) {
            $this->flash('error', 'Payment method not found or inactive.');
            $this->redirect('plans/pay');
        }

        $store = $this->resolveStoreContext($db);

        $this->view('../../modules/plans/views/method', [
            'pageTitle'     => $method['method_name'] . ' — DevAltum POS',
            'activeMenu'    => 'plan_status',
            'method'        => $method,
            'store'         => $store,
            'adminWhatsapp' => (string) App::setting('whatsapp_number', '+923101648600'),
            'isLoggedIn'    => Auth::check()
        ]);
    }

    /* ─────────────────────────────────────────
       Subscription Payment History Page
     ───────────────────────────────────────── */
    public function history() {
        if (!Auth::check()) {
            $this->redirect('auth/login');
        }

        $db = Database::getInstance();
        $store = $this->resolveStoreContext($db);
        $storeId = (int) ($store['id'] ?? Tenant::currentStoreId() ?? 0);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $countRow = $db->fetch("SELECT COUNT(*) as total FROM payment_transactions WHERE store_id = ?", [$storeId]);
        $totalRows = (int) ($countRow['total'] ?? 0);
        $totalPages = max(1, (int) ceil($totalRows / $limit));

        $transactions = $db->fetchAll("
            SELECT pt.*, 
                   COALESCE(sp.plan_name, pt.notes, 'Plan Renewal') as plan_title,
                   COALESCE(pm.method_name, pt.method, 'Bank Transfer') as resolved_method_name
            FROM payment_transactions pt
            LEFT JOIN subscription_plans sp ON sp.id = pt.plan_id
            LEFT JOIN payment_methods pm ON (pm.id = CAST(pt.method AS UNSIGNED) OR LOWER(pm.method_name) = LOWER(pt.method))
            WHERE pt.store_id = ?
            ORDER BY pt.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ", [$storeId]);

        // Summary stats
        $stats = [
            'total_count' => $totalRows,
            'approved_sum' => (float) ($db->fetch("SELECT SUM(amount) as s FROM payment_transactions WHERE store_id = ? AND status IN ('approved', 'completed', 'paid', 'active')", [$storeId])['s'] ?? 0),
            'pending_count' => (int) ($db->fetch("SELECT COUNT(*) as c FROM payment_transactions WHERE store_id = ? AND status = 'pending'", [$storeId])['c'] ?? 0),
            'rejected_count' => (int) ($db->fetch("SELECT COUNT(*) as c FROM payment_transactions WHERE store_id = ? AND status IN ('rejected', 'declined', 'failed')", [$storeId])['c'] ?? 0),
        ];

        $this->view('../../modules/plans/views/history', [
            'pageTitle'    => 'Subscription Payment History — DevAltum POS',
            'activeMenu'   => 'plan_status',
            'store'        => $store,
            'transactions' => $transactions,
            'stats'        => $stats,
            'currentPage'  => $page,
            'totalPages'   => $totalPages,
            'totalRows'    => $totalRows,
            'limit'        => $limit
        ]);
    }

    /* ─────────────────────────────────────────
       Store Auto-Lookup via AJAX
    ───────────────────────────────────────── */
    public function storeLookup() {
        $q = trim((string) ($_GET['q'] ?? ''));
        if (strlen($q) < 1) {
            $this->jsonResponse(['results' => []]);
        }

        $db = Database::getInstance();
        $results = $db->fetchAll("
            SELECT s.id, s.store_name, s.subscription_expires_at, s.status,
                   u.email as owner_email, s.phone as owner_phone,
                   COALESCE(p.plan_name, s.plan_type, 'Standard') as plan_name,
                   COALESCE(p.monthly_price, 2500) as monthly_price
            FROM stores s
            LEFT JOIN users u ON u.store_id = s.id AND u.role IN ('store_owner', 'admin')
            LEFT JOIN subscription_plans p ON p.id = s.current_plan_id
            WHERE (s.store_name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR s.phone LIKE ?)
              AND s.parent_store_id IS NULL
            ORDER BY s.id DESC
            LIMIT 8
        ", ["%$q%", "%$q%", "%$q%", "%$q%"]);

        $this->jsonResponse(['results' => $results]);
    }

    /* ─────────────────────────────────────────
       Submit Payment Transaction with Screenshot
    ───────────────────────────────────────── */
    public function submitPayment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid method'], 400);
        }

        $db = Database::getInstance();
        $storeId = (int) ($_POST['store_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $method = trim((string) ($_POST['method'] ?? 'Bank Transfer'));
        $trxId = trim((string) ($_POST['trx_id'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $planName = trim((string) ($_POST['plan_name'] ?? 'Pro Plan'));

        if ($storeId <= 0) {
            $this->jsonResponse(['error' => 'Please select a valid store.'], 400);
        }
        if ($amount <= 0) {
            $this->jsonResponse(['error' => 'Please enter a valid paid amount.'], 400);
        }

        $store = $db->fetch("SELECT * FROM stores WHERE id = ? LIMIT 1", [$storeId]);
        if (!$store) {
            $this->jsonResponse(['error' => 'Store not found.'], 404);
        }

        // Handle Screenshot Upload
        $screenshotPath = null;
        if (!empty($_FILES['screenshot']['name'])) {
            $file = $_FILES['screenshot'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowedExts, true)) {
                $this->jsonResponse(['error' => 'Invalid file format. Please upload JPG, PNG or WEBP screenshot.'], 400);
            }
            if ($file['size'] > 10 * 1024 * 1024) {
                $this->jsonResponse(['error' => 'File size exceeds 10MB limit.'], 400);
            }

            $uploadDir = ROOT_PATH . '/public/uploads/payments';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            $filename = 'pay_' . $storeId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $uploadDir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $screenshotPath = 'public/uploads/payments/' . $filename;
            }
        }

        // Insert Payment Transaction
        $db->query("
            INSERT INTO payment_transactions 
                (store_id, method, gateway_ref, receipt_screenshot, amount, status, notes, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())
        ", [
            $storeId,
            $method,
            $trxId ?: 'MANUAL-' . date('Ymd-His'),
            $screenshotPath,
            $amount,
            $notes ?: "Plan renewal submission for {$planName}"
        ]);
        $txId = (int) $db->lastInsertId();

        // Build WhatsApp notification link for Super Admin
        $adminWhatsapp = preg_replace('/[^0-9]/', '', (string)App::setting('whatsapp_number', '923101648600'));
        if (str_starts_with($adminWhatsapp, '0')) {
            $adminWhatsapp = '92' . substr($adminWhatsapp, 1);
        }

        $waText = "Assalam o Alaikum Super Admin,\n\n"
            . "I have submitted a subscription payment for:\n"
            . "🏪 *Store:* " . $store['store_name'] . "\n"
            . "📦 *Plan:* " . $planName . "\n"
            . "💰 *Amount:* Rs. " . number_format($amount) . "\n"
            . "💳 *Method:* " . $method . "\n"
            . "🔢 *Trx ID:* " . ($trxId ?: 'N/A') . "\n\n"
            . "Please verify the screenshot and approve my account.\n"
            . "Thank you!";

        $waLink = "https://wa.me/{$adminWhatsapp}?text=" . urlencode($waText);

        $this->jsonResponse([
            'success' => true,
            'message' => 'Payment proof successfully submitted! Super admin will verify and activate your store.',
            'trx_id' => $txId,
            'wa_link' => $waLink
        ]);
    }

    private function getStoreWithPlan($db, $storeId) {
        return $db->fetch("
            SELECT s.*, 
                   COALESCE(p.plan_name, s.plan_type, 'Standard') AS plan_name,
                   COALESCE(p.monthly_price, 2500) AS monthly_price,
                   u.email as owner_email, s.phone as owner_phone
            FROM stores s
            LEFT JOIN subscription_plans p ON p.id = s.current_plan_id
            LEFT JOIN users u ON u.store_id = s.id AND u.role IN ('store_owner', 'admin')
            WHERE s.id = ?
            LIMIT 1
        ", [(int)$storeId]);
    }

    private function resolveStoreContext($db) {
        $storeId = 0;
        $reqStoreId = (int) ($_GET['store_id'] ?? 0);
        $identifier = trim((string) ($_GET['identifier'] ?? $_GET['email'] ?? ''));

        if ($reqStoreId > 0) {
            $storeId = $reqStoreId;
        } elseif (!empty($identifier)) {
            $u = $db->fetch("SELECT store_id FROM users WHERE email = ? OR username = ? LIMIT 1", [$identifier, $identifier]);
            if ($u && !empty($u['store_id'])) {
                $storeId = (int) $u['store_id'];
            }
        } elseif (Auth::check()) {
            $u = Auth::user();
            $storeId = (int) ($u['store_id'] ?? 0);
            if ($storeId <= 0 && Tenant::currentStoreId()) {
                $storeId = (int) Tenant::currentStoreId();
            }
        }

        if ($storeId <= 0) {
            $first = $db->fetch("SELECT id FROM stores ORDER BY id ASC LIMIT 1");
            if ($first) {
                $storeId = (int) $first['id'];
            }
        }

        return $storeId > 0 ? $this->getStoreWithPlan($db, $storeId) : null;
    }
}
