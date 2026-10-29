<?php

class WhatsappController extends Controller {

    public function __construct() {
        AuthMiddleware::handleAdmin();
    }

    public function index() {
        $db = Database::getInstance();
        $storeId = (int) Tenant::currentStoreId();

        $customers = $db->fetchAll("
            SELECT c.id, c.name, c.phone
            FROM customers c
            WHERE c.store_id = ? AND COALESCE(c.phone, '') != ''
            ORDER BY c.name ASC
            LIMIT 500
        ", [$storeId]);

        $dueCustomers = $db->fetchAll("
            SELECT
                i.id AS invoice_id,
                i.custom_id,
                COALESCE(c.id, 0) AS customer_id,
                COALESCE(c.name, i.customer_name_snapshot, 'Customer') AS customer_name,
                COALESCE(c.phone, i.customer_phone_snapshot, '') AS customer_phone,
                GREATEST((COALESCE(i.total, 0) - COALESCE(i.paid_amount, 0)), COALESCE(i.balance_due, 0), 0) AS open_due,
                i.credit_due_date
            FROM invoices i
            LEFT JOIN customers c ON c.id = i.customer_id
            WHERE i.store_id = ?
              AND (
                    LOWER(COALESCE(i.payment_method, '')) = 'credit'
                    OR i.credit_due_date IS NOT NULL
                  )
              AND GREATEST((COALESCE(i.total, 0) - COALESCE(i.paid_amount, 0)), COALESCE(i.balance_due, 0), 0) > 0
              AND COALESCE(c.phone, i.customer_phone_snapshot, '') != ''
            ORDER BY i.credit_due_date IS NULL, i.credit_due_date ASC, i.created_at DESC
            LIMIT 200
        ", [$storeId]);

        $history = $db->fetchAll("
            SELECT wm.*, COALESCE(c.name, '') AS customer_name
            FROM whatsapp_messages wm
            LEFT JOIN customers c ON c.id = wm.customer_id
            WHERE wm.store_id = ?
            ORDER BY wm.id DESC
            LIMIT 100
        ", [$storeId]);

        $prefill = $this->buildPrefill($db, $storeId);

        $this->view('../../modules/whatsapp/views/index', [
            'pageTitle' => 'WhatsApp Center',
            'activeMenu' => 'whatsapp_center',
            'customers' => $customers,
            'dueCustomers' => $dueCustomers,
            'history' => $history,
            'prefill' => $prefill
        ]);
    }

    public function send() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $storeId = (int) Tenant::currentStoreId();
        $userId = (int) Auth::id();

        $customerId = max(0, (int) ($_POST['customer_id'] ?? 0));
        $invoiceId = max(0, (int) ($_POST['invoice_id'] ?? 0));
        $recipientPhone = trim((string) ($_POST['recipient_phone'] ?? ''));
        $messageBody = trim((string) ($_POST['message_body'] ?? ''));
        $messageType = trim((string) ($_POST['message_type'] ?? 'custom'));
        if (!in_array($messageType, ['custom', 'credit_due', 'follow_up'], true)) {
            $messageType = 'custom';
        }

        if ($recipientPhone === '' || $messageBody === '') {
            $this->flash('error', 'Recipient phone and message are required.');
            $this->redirect('whatsapp/index');
        }
        if (mb_strlen($messageBody) > 2000) {
            $this->flash('error', 'Message is too long. Please keep it under 2000 characters.');
            $this->redirect('whatsapp/index');
        }

        $sent = Billing::sendWhatsapp($recipientPhone, $messageBody);
        $status = $sent ? 'sent' : 'failed';
        $provider = (string) App::setting('whatsapp_provider', 'none');

        $db->query("
            INSERT INTO whatsapp_messages (store_id, user_id, customer_id, invoice_id, recipient_phone, message_type, message_body, status, provider, provider_response, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ", [
            $storeId,
            $userId > 0 ? $userId : null,
            $customerId > 0 ? $customerId : null,
            $invoiceId > 0 ? $invoiceId : null,
            $recipientPhone,
            $messageType,
            $messageBody,
            $status,
            $provider !== '' ? $provider : 'none',
            $sent ? 'Message dispatched successfully.' : 'Message could not be sent. Check Meta provider configuration.'
        ]);

        if ($sent) {
            $this->flash('success', 'WhatsApp message sent successfully.');
        } else {
            $this->flash('error', 'WhatsApp send failed. Please check Meta WhatsApp settings.');
        }
        $this->redirect('whatsapp/index?tab=history');
    }

    public function sendDueReminder($invoiceId) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $invoiceId = (int) $invoiceId;
        $db = Database::getInstance();
        $storeId = (int) Tenant::currentStoreId();

        $invoice = $db->fetch("
            SELECT
                i.id,
                i.custom_id,
                i.credit_due_date,
                GREATEST((COALESCE(i.total, 0) - COALESCE(i.paid_amount, 0)), COALESCE(i.balance_due, 0), 0) AS open_due,
                COALESCE(c.id, 0) AS customer_id,
                COALESCE(c.name, i.customer_name_snapshot, 'Customer') AS customer_name,
                COALESCE(c.phone, i.customer_phone_snapshot, '') AS customer_phone
            FROM invoices i
            LEFT JOIN customers c ON c.id = i.customer_id
            WHERE i.id = ? AND i.store_id = ?
            LIMIT 1
        ", [$invoiceId, $storeId]);

        if (!$invoice) {
            $this->flash('error', 'Invoice not found for WhatsApp reminder.');
            $this->redirect('credits/index');
        }
        if (trim((string) ($invoice['customer_phone'] ?? '')) === '') {
            $this->flash('error', 'Customer phone number is missing.');
            $this->redirect('credits/action/' . $invoiceId);
        }

        $company = (string) App::setting('company_name', (string) ($_SESSION['store_name'] ?? 'Our Store'));
        $dueDate = !empty($invoice['credit_due_date']) ? date('Y-m-d', strtotime((string) $invoice['credit_due_date'])) : 'N/A';
        $message = "Assalam o Alaikum {$invoice['customer_name']},\n\n"
            . "Aap ki payment due hai.\n"
            . "Invoice: {$invoice['custom_id']}\n"
            . "Remaining: " . App::money((float) $invoice['open_due']) . "\n"
            . "Due Date: {$dueDate}\n\n"
            . "Meharbani kar ke payment clear kar dein.\n"
            . "{$company}";

        $_POST = [
            'customer_id' => (int) ($invoice['customer_id'] ?? 0),
            'invoice_id' => $invoiceId,
            'recipient_phone' => (string) $invoice['customer_phone'],
            'message_body' => $message,
            'message_type' => 'credit_due',
            'csrf_token' => Security::csrfTokenFromRequest()
        ];
        $this->send();
    }

    private function buildPrefill($db, $storeId) {
        $prefill = [
            'customer_id' => 0,
            'invoice_id' => 0,
            'phone' => '',
            'message' => '',
            'message_type' => 'custom'
        ];

        $invoiceId = max(0, (int) ($_GET['invoice_id'] ?? 0));
        if ($invoiceId <= 0) {
            return $prefill;
        }

        $invoice = $db->fetch("
            SELECT
                i.id,
                i.custom_id,
                i.credit_due_date,
                GREATEST((COALESCE(i.total, 0) - COALESCE(i.paid_amount, 0)), COALESCE(i.balance_due, 0), 0) AS open_due,
                COALESCE(c.id, 0) AS customer_id,
                COALESCE(c.name, i.customer_name_snapshot, 'Customer') AS customer_name,
                COALESCE(c.phone, i.customer_phone_snapshot, '') AS customer_phone
            FROM invoices i
            LEFT JOIN customers c ON c.id = i.customer_id
            WHERE i.id = ? AND i.store_id = ?
            LIMIT 1
        ", [$invoiceId, $storeId]);
        if (!$invoice) {
            return $prefill;
        }

        $company = (string) App::setting('company_name', (string) ($_SESSION['store_name'] ?? 'Our Store'));
        $dueDate = !empty($invoice['credit_due_date']) ? date('Y-m-d', strtotime((string) $invoice['credit_due_date'])) : 'N/A';
        $message = "Assalam o Alaikum {$invoice['customer_name']},\n\n"
            . "Aap ki payment due hai.\n"
            . "Invoice: {$invoice['custom_id']}\n"
            . "Remaining: " . App::money((float) $invoice['open_due']) . "\n"
            . "Due Date: {$dueDate}\n\n"
            . "Meharbani kar ke payment clear kar dein.\n"
            . "{$company}";

        $prefill['customer_id'] = (int) ($invoice['customer_id'] ?? 0);
        $prefill['invoice_id'] = (int) ($invoice['id'] ?? 0);
        $prefill['phone'] = (string) ($invoice['customer_phone'] ?? '');
        $prefill['message'] = $message;
        $prefill['message_type'] = 'credit_due';
        return $prefill;
    }

    public function eodReport() {
        $db = Database::getInstance();
        $storeId = (int) Tenant::currentStoreId();
        $today = date('Y-m-d');
        $storeName = (string) App::setting('company_name', 'DevAltum POS');
        $ownerPhone = (string) App::setting('whatsapp_number', '');

        // 1. Total Invoices & Sales Today
        $salesData = $db->fetch("
            SELECT
                COUNT(*) as total_bills,
                COALESCE(SUM(total), 0) as total_sales,
                COALESCE(SUM(paid_amount), 0) as total_collected,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = 'cash' THEN paid_amount ELSE 0 END), 0) as cash_sales,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) IN ('online', 'card', 'bank') THEN paid_amount ELSE 0 END), 0) as online_sales,
                COALESCE(SUM(balance_due), 0) as new_credit_given
            FROM invoices
            WHERE store_id = ? AND DATE(created_at) = ?
        ", [$storeId, $today]) ?: [
            'total_bills' => 0,
            'total_sales' => 0,
            'total_collected' => 0,
            'cash_sales' => 0,
            'online_sales' => 0,
            'new_credit_given' => 0
        ];

        // 2. Expenses Today
        $expensesData = $db->fetch("
            SELECT COALESCE(SUM(amount), 0) as total_expenses, COUNT(*) as exp_count
            FROM expenses
            WHERE store_id = ? AND expense_date = ?
        ", [$storeId, $today]) ?: ['total_expenses' => 0, 'exp_count' => 0];

        // 3. Top 3 Selling Items Today
        $topItems = $db->fetchAll("
            SELECT ii.product_name, SUM(ii.quantity) as total_sold
            FROM invoice_items ii
            JOIN invoices i ON i.id = ii.invoice_id
            WHERE i.store_id = ? AND DATE(i.created_at) = ?
            GROUP BY ii.product_name
            ORDER BY total_sold DESC
            LIMIT 3
        ", [$storeId, $today]);

        // Estimated profit calculation
        $costData = $db->fetch("
            SELECT COALESCE(SUM(p.cost * ii.quantity), 0) as total_cost
            FROM invoice_items ii
            JOIN invoices i ON i.id = ii.invoice_id
            LEFT JOIN products p ON p.id = ii.product_id
            WHERE i.store_id = ? AND DATE(i.created_at) = ?
        ", [$storeId, $today]);
        $totalCost = (float)($costData['total_cost'] ?? 0);
        $grossProfit = max(0, (float)$salesData['total_sales'] - $totalCost);
        $netProfit = max(0, $grossProfit - (float)$expensesData['total_expenses']);

        // Build WhatsApp Message
        $msg = "📊 *DAILY SALES & CASH CLOSING REPORT*\n";
        $msg .= "🏪 *{$storeName}*\n";
        $msg .= "📅 Date: *" . date('d M Y') . "*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "💰 *Total Revenue:* " . App::money((float)$salesData['total_sales']) . " (" . (int)$salesData['total_bills'] . " Bills)\n";
        $msg .= "💵 *Cash Collected:* " . App::money((float)$salesData['cash_sales']) . "\n";
        $msg .= "💳 *Online/Bank:* " . App::money((float)$salesData['online_sales']) . "\n";
        $msg .= "📝 *New Credit/Udhaar:* " . App::money((float)$salesData['new_credit_given']) . "\n";
        $msg .= "📉 *Total Expenses:* " . App::money((float)$expensesData['total_expenses']) . "\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "📈 *Estimated Net Profit:* *" . App::money($netProfit) . "*\n\n";

        if (!empty($topItems)) {
            $msg .= "🏆 *Top Selling Items Today:*\n";
            $r = 1;
            foreach ($topItems as $item) {
                $msg .= "{$r}. {$item['product_name']} ({$item['total_sold']} units)\n";
                $r++;
            }
            $msg .= "\n";
        }

        $msg .= "Generated automatically by DevAltum POS SaaS Pro";

        $cleanPhone = preg_replace('/[^0-9]/', '', $ownerPhone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '92' . substr($cleanPhone, 1);
        }

        $waLink = !empty($cleanPhone) ? "https://wa.me/{$cleanPhone}?text=" . urlencode($msg) : "https://wa.me/?text=" . urlencode($msg);

        $this->view('../../modules/whatsapp/views/eod_report', [
            'pageTitle' => 'Daily End-of-Day (EOD) Summary',
            'activeMenu' => 'reports',
            'salesData' => $salesData,
            'expensesData' => $expensesData,
            'topItems' => $topItems,
            'netProfit' => $netProfit,
            'reportText' => $msg,
            'waLink' => $waLink,
            'storeName' => $storeName,
            'ownerPhone' => $ownerPhone
        ]);
    }
}
