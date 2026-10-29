<?php

class CreditsController extends Controller {

    public function __construct() {
        AuthMiddleware::handlePermission('credits');
    }

    public function index() {
        $db = Database::getInstance();
        [$whereI, $whereIParams] = Tenant::storeFilter('i');
        [$whereCp, $whereCpParams] = Tenant::storeFilter('cp');

        $q = trim($_GET['q'] ?? '');
        $dueState = trim($_GET['due_state'] ?? '');
        $duePreset = trim($_GET['due_preset'] ?? '');
        $dueFrom = trim($_GET['due_from'] ?? '');
        $dueTo = trim($_GET['due_to'] ?? '');
        $sort = trim($_GET['sort'] ?? 'due_asc');

        $paymentQ = trim($_GET['payment_q'] ?? '');
        $paymentMethodFilter = trim($_GET['payment_method'] ?? '');
        $paymentPreset = trim($_GET['payment_preset'] ?? '');
        $paymentFrom = trim($_GET['payment_from'] ?? '');
        $paymentTo = trim($_GET['payment_to'] ?? '');

        if (!in_array($dueState, ['overdue', 'today', 'upcoming', 'no_date'], true)) {
            $dueState = '';
        }
        if (!in_array($sort, ['due_asc', 'due_desc', 'latest'], true)) {
            $sort = 'due_asc';
        }
        if (!in_array($duePreset, ['', 'custom', 'today', 'this_month'], true)) {
            $duePreset = '';
        }
        if (!in_array($paymentMethodFilter, ['', 'cash', 'online', 'credit'], true)) {
            $paymentMethodFilter = '';
        }
        if (!in_array($paymentPreset, ['', 'custom', 'today', 'this_month'], true)) {
            $paymentPreset = '';
        }

        if ($duePreset === 'today') {
            $dueFrom = date('Y-m-d');
            $dueTo = date('Y-m-d');
        } elseif ($duePreset === 'this_month') {
            $dueFrom = date('Y-m-01');
            $dueTo = date('Y-m-t');
        }

        if ($paymentPreset === 'today') {
            $paymentFrom = date('Y-m-d');
            $paymentTo = date('Y-m-d');
        } elseif ($paymentPreset === 'this_month') {
            $paymentFrom = date('Y-m-01');
            $paymentTo = date('Y-m-t');
        }

        $creditWhere = "
            WHERE (
                    LOWER(COALESCE(i.payment_method, '')) = 'credit'
                    OR i.credit_due_date IS NOT NULL
                    OR COALESCE(i.is_credit_settled, 0) = 0
                    OR i.balance_due > 0
                    OR EXISTS (
                        SELECT 1
                        FROM credit_payments cp0
                        WHERE cp0.invoice_id = i.id
                          AND LOWER(COALESCE(cp0.payment_method, '')) = 'credit'
                    )
                  )
              AND GREATEST((COALESCE(i.total, 0) - COALESCE(i.paid_amount, 0)), COALESCE(i.balance_due, 0), 0) > 0
              AND $whereI
        ";
        $creditParams = $whereIParams;

        if ($q !== '') {
            $creditWhere .= " AND (
                i.custom_id LIKE ?
                OR COALESCE(i.customer_name_snapshot, '') LIKE ?
                OR COALESCE(i.customer_phone_snapshot, '') LIKE ?
                OR COALESCE(i.credit_private_note, '') LIKE ?
                OR COALESCE(c.name, '') LIKE ?
                OR COALESCE(c.phone, '') LIKE ?
            )";
            for ($i = 0; $i < 6; $i++) {
                $creditParams[] = "%$q%";
            }
        }

        if ($dueState === 'overdue') {
            $creditWhere .= " AND i.credit_due_date IS NOT NULL AND DATE(i.credit_due_date) < CURDATE()";
        } elseif ($dueState === 'today') {
            $creditWhere .= " AND i.credit_due_date IS NOT NULL AND DATE(i.credit_due_date) = CURDATE()";
        } elseif ($dueState === 'upcoming') {
            $creditWhere .= " AND i.credit_due_date IS NOT NULL AND DATE(i.credit_due_date) > CURDATE()";
        } elseif ($dueState === 'no_date') {
            $creditWhere .= " AND i.credit_due_date IS NULL";
        }

        if ($dueFrom !== '') {
            $creditWhere .= " AND i.credit_due_date IS NOT NULL AND DATE(i.credit_due_date) >= ?";
            $creditParams[] = $dueFrom;
        }
        if ($dueTo !== '') {
            $creditWhere .= " AND i.credit_due_date IS NOT NULL AND DATE(i.credit_due_date) <= ?";
            $creditParams[] = $dueTo;
        }

        $orderBy = "ORDER BY i.credit_due_date IS NULL, i.credit_due_date ASC, i.created_at DESC";
        if ($sort === 'due_desc') {
            $orderBy = "ORDER BY i.credit_due_date IS NULL, i.credit_due_date DESC, i.created_at DESC";
        } elseif ($sort === 'latest') {
            $orderBy = "ORDER BY i.created_at DESC";
        }

        $credits = $db->fetchAll(
            "
            SELECT
                i.*,
                GREATEST((COALESCE(i.total, 0) - COALESCE(i.paid_amount, 0)), COALESCE(i.balance_due, 0), 0) AS open_due,
                COALESCE(c.name, i.customer_name_snapshot, 'Walk-in Customer') AS customer_name,
                COALESCE(c.phone, i.customer_phone_snapshot, '') AS customer_phone
            FROM invoices i
            LEFT JOIN customers c ON c.id = i.customer_id
            $creditWhere
            $orderBy
            ",
            $creditParams
        );

        $paymentWhere = " WHERE $whereCp";
        $paymentParams = $whereCpParams;

        if ($paymentQ !== '') {
            $paymentWhere .= " AND (
                i.custom_id LIKE ?
                OR COALESCE(cp.note, '') LIKE ?
                OR COALESCE(cp.private_message, '') LIKE ?
            )";
            $paymentParams[] = "%$paymentQ%";
            $paymentParams[] = "%$paymentQ%";
            $paymentParams[] = "%$paymentQ%";
        }

        if ($paymentMethodFilter !== '') {
            $paymentWhere .= " AND cp.payment_method = ?";
            $paymentParams[] = $paymentMethodFilter;
        }

        if ($paymentFrom !== '') {
            $paymentWhere .= " AND DATE(cp.created_at) >= ?";
            $paymentParams[] = $paymentFrom;
        }

        if ($paymentTo !== '') {
            $paymentWhere .= " AND DATE(cp.created_at) <= ?";
            $paymentParams[] = $paymentTo;
        }

        $payments = $db->fetchAll(
            "
            SELECT cp.*, i.custom_id
            FROM credit_payments cp
            JOIN invoices i ON i.id = cp.invoice_id
            $paymentWhere
            ORDER BY cp.created_at DESC
            LIMIT 25
            ",
            $paymentParams
        );

        $this->view('../../modules/credits/views/index', [
            'pageTitle' => 'Credit Dues',
            'activeMenu' => 'credits',
            'credits' => $credits,
            'payments' => $payments,
            'filters' => compact('q', 'dueState', 'duePreset', 'dueFrom', 'dueTo', 'sort', 'paymentQ', 'paymentMethodFilter', 'paymentPreset', 'paymentFrom', 'paymentTo')
        ]);
    }

    public function action($invoiceId) {
        $db = Database::getInstance();
        [$whereI, $whereIParams] = Tenant::storeFilter('i');
        [$whereCp, $whereCpParams] = Tenant::storeFilter();
        $invoice = $db->fetch(
            "
            SELECT
                i.*,
                GREATEST((COALESCE(i.total, 0) - COALESCE(i.paid_amount, 0)), COALESCE(i.balance_due, 0), 0) AS open_due,
                COALESCE(c.name, i.customer_name_snapshot, 'Walk-in Customer') AS customer_name,
                COALESCE(c.phone, i.customer_phone_snapshot, '') AS customer_phone
            FROM invoices i
            LEFT JOIN customers c ON c.id = i.customer_id
            WHERE i.id = ? AND $whereI
            LIMIT 1
            ",
            array_merge([$invoiceId], $whereIParams)
        );

        if (!$invoice) {
            $this->flash('error', 'Credit invoice not found.');
            $this->redirect('credits/index');
        }

        $isCreditInvoice = (
            strtolower((string) ($invoice['payment_method'] ?? '')) === 'credit'
            || !empty($invoice['credit_due_date'])
            || (int) ($invoice['is_credit_settled'] ?? 0) === 0
            || (float) ($invoice['open_due'] ?? 0) > 0
            || (float) ($invoice['balance_due'] ?? 0) > 0
            || (bool) $db->fetch("SELECT 1 FROM credit_payments WHERE invoice_id = ? AND $whereCp LIMIT 1", array_merge([$invoiceId], $whereCpParams))
        );
        if (!$isCreditInvoice) {
            $this->flash('error', 'Selected invoice is not a credit invoice.');
            $this->redirect('credits/index');
        }

        $payments = $db->fetchAll(
            "
            SELECT *
            FROM credit_payments
            WHERE invoice_id = ? AND $whereCp
            ORDER BY created_at DESC
            ",
            array_merge([$invoiceId], $whereCpParams)
        );

        $this->view('../../modules/credits/views/view', [
            'pageTitle' => 'Credit Invoice Profile',
            'activeMenu' => 'credits',
            'invoice' => $invoice,
            'payments' => $payments
        ]);
    }

    public function collect($invoiceId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('credits/index');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $invoice = $db->fetch(
            "
            SELECT *,
                GREATEST((COALESCE(total, 0) - COALESCE(paid_amount, 0)), COALESCE(balance_due, 0), 0) AS open_due
            FROM invoices
            WHERE id = ? AND $where
            ",
            array_merge([$invoiceId], $whereParams)
        );

        if (!$invoice) {
            $this->flash('error', 'Credit invoice not found.');
            $this->redirect('credits/index');
        }

        $isCreditInvoice = (
            strtolower((string) ($invoice['payment_method'] ?? '')) === 'credit'
            || !empty($invoice['credit_due_date'])
            || (int) ($invoice['is_credit_settled'] ?? 0) === 0
            || (float) ($invoice['open_due'] ?? 0) > 0
            || (float) ($invoice['balance_due'] ?? 0) > 0
            || (bool) $db->fetch("SELECT 1 FROM credit_payments WHERE invoice_id = ? AND $where AND LOWER(COALESCE(payment_method, '')) = 'credit' LIMIT 1", array_merge([$invoiceId], $whereParams))
        );

        if (!$isCreditInvoice) {
            $this->flash('error', 'Credit invoice not found.');
            $this->redirect('credits/index');
        }

        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        $method = strtolower(trim($_POST['payment_method'] ?? 'cash'));
        $nextDue = trim($_POST['next_due_date'] ?? '');
        $message = trim($_POST['private_message'] ?? '');
        $note = trim($_POST['note'] ?? '');

        if ($amount <= 0) {
            $this->flash('error', 'Payment amount must be greater than zero.');
            $this->redirect('credits/action/' . (int) $invoiceId);
        }

        $openDue = (float) ($invoice['open_due'] ?? 0);
        if ($openDue <= 0) {
            $this->flash('error', 'This invoice has no pending due.');
            $this->redirect('credits/action/' . (int) $invoiceId);
        }

        if ($amount > $openDue) {
            $this->flash('error', 'Payment amount is greater than remaining due.');
            $this->redirect('credits/action/' . (int) $invoiceId);
        }

        if (!in_array($method, ['cash', 'online'], true)) {
            $method = 'cash';
        }

        $nextDueSql = null;
        if ($nextDue !== '') {
            $timestamp = strtotime($nextDue);
            if ($timestamp !== false) {
                $nextDueSql = date('Y-m-d H:i:s', $timestamp);
            }
        }

        $connection = $db->getConnection();
        try {
            $connection->beginTransaction();

            $newPaid = min((float) $invoice['total'], (float) $invoice['paid_amount'] + $amount);
            $newBalance = max(0, (float) $invoice['total'] - $newPaid);
            $settled = $newBalance <= 0 ? 1 : 0;

            $creditDueToSave = $settled ? null : ($nextDueSql ?: ($invoice['credit_due_date'] ?? null));
            $messageToSave = $message !== '' ? $message : ($invoice['credit_private_note'] ?? null);

            $connection->prepare(
                "
                UPDATE invoices
                SET paid_amount = ?, balance_due = ?, is_credit_settled = ?, credit_due_date = ?, credit_private_note = ?
                WHERE id = ? AND store_id = ?
                "
            )->execute([
                $newPaid,
                $newBalance,
                $settled,
                $creditDueToSave,
                $messageToSave,
                $invoiceId,
                Tenant::currentStoreId()
            ]);

            $connection->prepare(
                "
                INSERT INTO credit_payments (store_id, invoice_id, amount, payment_method, note, next_due_date, private_message, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                "
            )->execute([
                Tenant::currentStoreId(),
                $invoiceId,
                $amount,
                $method,
                $note !== '' ? $note : null,
                $nextDueSql,
                $message !== '' ? $message : null,
                Auth::id()
            ]);

            $paymentId = (int) $connection->lastInsertId();

            $connection->prepare("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)")->execute([
                Tenant::currentStoreId(),
                Auth::id(),
                'Credit',
                'Collected ' . App::money($amount) . ' against ' . $invoice['custom_id']
            ]);

            $connection->commit();
            $this->flash('success', 'Credit payment recorded successfully.');
            $this->redirect('credits/receipt/' . $paymentId);
        } catch (Exception $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            $this->flash('error', 'Unable to record payment.');
            $this->redirect('credits/action/' . (int) $invoiceId);
        }
    }

    public function receipt($paymentId) {
        $db = Database::getInstance();
        [$whereCp, $whereCpParams] = Tenant::storeFilter('cp');
        [$whereItem, $whereItemParams] = Tenant::storeFilter();
        $payment = $db->fetch(
            "
            SELECT
                cp.*,
                i.custom_id,
                i.total,
                i.paid_amount,
                i.balance_due,
                GREATEST((COALESCE(i.total, 0) - COALESCE(i.paid_amount, 0)), COALESCE(i.balance_due, 0), 0) AS open_due,
                i.customer_name_snapshot,
                i.customer_phone_snapshot,
                i.payment_method AS invoice_payment_method,
                i.sale_channel,
                i.credit_due_date,
                i.created_at AS invoice_created_at,
                COALESCE(u.name, 'System') AS cashier
            FROM credit_payments cp
            JOIN invoices i ON i.id = cp.invoice_id
            LEFT JOIN users u ON u.id = cp.created_by
            WHERE cp.id = ? AND $whereCp
            ",
            array_merge([$paymentId], $whereCpParams)
        );

        if (!$payment) {
            die('Credit payment receipt not found.');
        }

        $invoiceItems = $this->attachProductImages($db->fetchAll(
            "SELECT * FROM invoice_items WHERE invoice_id = ? AND $whereItem ORDER BY id ASC",
            array_merge([$payment['invoice_id']], $whereItemParams)
        ));

        if (($_GET['print_image'] ?? '') === '1') {
            $src = (defined('BASE_URL') ? BASE_URL : '') . '/credits/receipt/' . (int) $paymentId . '?download_image=1&inline=1';
            $title = 'Credit Receipt ' . Security::escape($payment['custom_id'] ?? $paymentId);
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . $title . '</title><style>html,body{margin:0;background:#fff;}body{display:grid;place-items:start center;padding:0}.receipt-print-img{display:block;max-width:100%;width:auto;height:auto}@media print{@page{size:auto;margin:0}html,body{width:auto;height:auto}.receipt-print-img{max-width:none;width:100%;height:auto}}</style></head><body><img class="receipt-print-img" src="' . Security::escape($src) . '" alt="' . $title . '" onload="setTimeout(function(){window.print()},250)"></body></html>';
            exit;
        }

        if (($_GET['download_image'] ?? '') === '1') {
            $image = ReceiptMedia::creditImage($payment, $invoiceItems);
            $png = ReceiptMedia::pngBytes($image);
            imagedestroy($image);
            $fileName = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) ($payment['custom_id'] ?? 'credit_receipt'));
            header('Content-Type: image/png');
            header('Content-Disposition: ' . (($_GET['inline'] ?? '') === '1' ? 'inline' : 'attachment') . '; filename="' . $fileName . '_credit_receipt.png"');
            header('Content-Length: ' . strlen($png));
            echo $png;
            exit;
        }

        if (($_GET['download_pdf'] ?? '') === '1') {
            $image = ReceiptMedia::creditImage($payment, $invoiceItems);
            $jpeg = ReceiptMedia::jpegBytes($image);
            $pdf = ReceiptMedia::imagePdf($jpeg, imagesx($image), imagesy($image));
            imagedestroy($image);
            $fileName = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) ($payment['custom_id'] ?? 'credit_receipt'));
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $fileName . '_credit_receipt.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }

        require APP_PATH . '/../modules/credits/views/receipt.php';
    }

    public function receiptPdf($paymentId) {
        $db = Database::getInstance();
        [$whereCp, $whereCpParams] = Tenant::storeFilter('cp');
        $payment = $db->fetch(
            "
            SELECT
                cp.*,
                i.custom_id,
                i.total,
                i.paid_amount,
                i.balance_due,
                GREATEST((COALESCE(i.total, 0) - COALESCE(i.paid_amount, 0)), COALESCE(i.balance_due, 0), 0) AS open_due,
                i.customer_name_snapshot,
                i.customer_phone_snapshot
            FROM credit_payments cp
            JOIN invoices i ON i.id = cp.invoice_id
            WHERE cp.id = ? AND $whereCp
            ",
            array_merge([$paymentId], $whereCpParams)
        );

        if (!$payment) {
            die('Credit payment receipt not found.');
        }

        [$whereItem, $whereItemParams] = Tenant::storeFilter();
        $invoiceItems = $this->attachProductImages($db->fetchAll(
            "SELECT * FROM invoice_items WHERE invoice_id = ? AND $whereItem ORDER BY id ASC",
            array_merge([$payment['invoice_id']], $whereItemParams)
        ));

        $image = ReceiptMedia::creditImage($payment, $invoiceItems);
        $jpeg = ReceiptMedia::jpegBytes($image);
        $pdf = ReceiptMedia::imagePdf($jpeg, imagesx($image), imagesy($image));
        imagedestroy($image);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="credit_receipt_' . (int) $paymentId . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function attachProductImages($items) {
        if (empty($items)) {
            return $items;
        }

        $productIds = array_values(array_unique(array_filter(array_map(function ($item) {
            return (int) ($item['product_id'] ?? 0);
        }, $items))));
        if (empty($productIds)) {
            return $items;
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        [$where, $whereParams] = Tenant::storeFilter();
        $products = Database::getInstance()->fetchAll("SELECT * FROM products WHERE id IN ($placeholders) AND $where", array_merge($productIds, $whereParams));
        $productMeta = [];
        foreach ($products as $product) {
            $productMeta[(int) $product['id']] = [
                'image' => $this->resolveProductImage($product),
                'unit_type' => $product['unit_type'] ?? 'pcs',
                'unit_value' => $product['unit_value'] ?? 1,
            ];
        }

        foreach ($items as &$item) {
            $meta = $productMeta[(int) ($item['product_id'] ?? 0)] ?? [];
            $item['product_image_url'] = $meta['image'] ?? '';
            $unitType = $meta['unit_type'] ?? ($item['unit_type'] ?? 'pcs');
            $unitValue = $meta['unit_value'] ?? 1;
            $qty = (float) ($item['quantity'] ?? 1);
            $item['product_unit_label'] = App::formatSoldUnit($qty, $unitType, $unitValue);
        }
        unset($item);

        return $items;
    }

    private function resolveProductImage($product) {
        foreach (['image_url', 'image_path', 'product_image', 'photo', 'thumbnail'] as $field) {
            $value = trim((string) ($product[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function buildCreditReceiptPdf($payment, $invoiceItems) {
        $companyName = App::setting('company_name', 'DevAltum POS');
        $companyAddress = App::setting('company_address', '');
        $companyNtn = trim((string) App::setting('company_ntn', ''));
        if ($companyNtn === '') {
            $companyNtn = trim((string) App::setting('company_ntn_number', App::setting('ntn', '')));
        }
        $companyWhatsapp = App::setting('whatsapp_number', '');
        $footerText = App::setting('receipt_footer', 'Thank you for choosing us. For support, please contact our store team.');

        $money = function ($value) {
            return preg_replace('/\.00\b/', '', App::money((float) $value));
        };
        $clean = function ($text) {
            $text = preg_replace('/[\r\n\t]+/', ' ', (string) $text);
            $text = preg_replace('/[^ -~]/', '', $text);
            return trim($text);
        };
        $wrap = function ($text, $width) use ($clean) {
            $text = $clean($text);
            if ($text === '') {
                return [''];
            }
            return explode("\n", wordwrap($text, $width, "\n", true));
        };
        $pair = function ($label, $value) use ($clean) {
            $label = $clean($label);
            $value = $clean($value);
            $width = 32;
            $left = substr($label, 0, 14);
            $rightWidth = max(8, $width - strlen($left));
            if (strlen($value) > $rightWidth) {
                return $left . "\n" . str_pad('', max(0, $width - strlen($value))) . $value;
            }
            return $left . str_pad('', max(1, $width - strlen($left) - strlen($value))) . $value;
        };
        $sep = str_repeat('-', 32);

        $lines = [];
        foreach ($wrap(strtoupper($companyName), 30) as $line) {
            $lines[] = str_pad($line, 32, ' ', STR_PAD_BOTH);
        }
        if ($companyNtn !== '') {
            $lines[] = str_pad('NTN : ' . $companyNtn, 32, ' ', STR_PAD_BOTH);
        }
        $lines[] = $sep;
        $lines[] = $pair('Receipt ID :', $payment['custom_id'] ?? '-');
        $lines[] = $pair('Date :', date('M d, Y h:i A', strtotime($payment['created_at'] ?? 'now')));
        $lines[] = $pair('Cashier :', $payment['cashier'] ?? 'System');
        $lines[] = $pair('Customer :', $payment['customer_name_snapshot'] ?: 'Walk-in');
        $lines[] = $pair('Phone :', $payment['customer_phone_snapshot'] ?: '-');
        $lines[] = $pair('Payment :', ucfirst((string) ($payment['payment_method'] ?? 'cash')));
        $lines[] = $pair('Channel :', ucfirst((string) ($payment['sale_channel'] ?? 'retail')));
        $lines[] = $sep;
        $lines[] = 'Item            Qty  Unit   Total';
        $lines[] = $sep;
        $totalItems = count($invoiceItems);
        foreach ($invoiceItems as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $nameLines = $wrap($item['product_name'] ?? 'Product', 14);
            $unit = $money($item['price'] ?? 0);
            $total = $money($item['total'] ?? 0);
            $lines[] = str_pad(substr($nameLines[0], 0, 14), 14)
                . str_pad(substr(App::formatQuantity($qty), 0, 5), 5, ' ', STR_PAD_LEFT)
                . str_pad(substr($unit, 0, 7), 7, ' ', STR_PAD_LEFT)
                . str_pad(substr($total, 0, 6), 6, ' ', STR_PAD_LEFT);
            foreach (array_slice($nameLines, 1) as $extraName) {
                $lines[] = '  ' . substr($extraName, 0, 28);
            }
            $unitLabel = $clean($item['product_unit_label'] ?? App::formatProductUnit(1, 'pcs'));
            if ($unitLabel !== '') {
                $lines[] = '  ' . substr($unitLabel, 0, 28);
            }
        }
        $lines[] = $sep;
        $lines[] = $pair('Invoice Total', $money($payment['total'] ?? 0));
        $lines[] = $pair('Received', $money($payment['amount'] ?? 0));
        $lines[] = $pair('Total Paid', $money($payment['paid_amount'] ?? 0));
        $lines[] = $pair('Remaining', $money($payment['open_due'] ?? $payment['balance_due'] ?? 0));
        $lines[] = $pair('Total Items', (string) $totalItems);
        $lines[] = $pair('Next Due', !empty($payment['next_due_date']) ? date('M d, Y h:i A', strtotime($payment['next_due_date'])) : '-');
        if (!empty($payment['note'])) {
            $lines[] = $sep;
            $lines[] = 'Note';
            foreach ($wrap($payment['note'], 32) as $noteLine) {
                $lines[] = $noteLine;
            }
        }
        $lines[] = $sep;
        foreach ($wrap($footerText, 32) as $footerLine) {
            $lines[] = str_pad($footerLine, 32, ' ', STR_PAD_BOTH);
        }
        if ($companyWhatsapp) {
            $lines[] = str_pad('WhatsApp: ' . $companyWhatsapp, 32, ' ', STR_PAD_BOTH);
        }
        if ($companyAddress) {
            foreach ($wrap('Address: ' . $companyAddress, 32) as $addressLine) {
                $lines[] = str_pad($addressLine, 32, ' ', STR_PAD_BOTH);
            }
        }
        $lines[] = $sep;
        $lines[] = str_pad('Software By DevAltum', 32, ' ', STR_PAD_BOTH);
        $lines[] = str_pad('+92 3101648600', 32, ' ', STR_PAD_BOTH);

        return $this->buildReceiptWidthPdf($lines);
    }

    private function buildReceiptWidthPdf($lines) {
        $safeLines = array_map(function ($line) {
            $text = preg_replace('/[\r\n\t]+/', ' ', (string) $line);
            $text = preg_replace('/[^ -~]/', '', $text);
            return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        }, $lines);

        $pageWidth = 164;
        $lineHeight = 9;
        $topMargin = 18;
        $bottomMargin = 18;
        $pageHeight = max(220, $topMargin + $bottomMargin + (count($safeLines) * $lineHeight));
        $y = $pageHeight - $topMargin;
        $body = "BT\n/F1 7.2 Tf\n9 TL\n10 $y Td\n";
        foreach ($safeLines as $index => $line) {
            if ($index > 0) {
                $body .= "T*\n";
            }
            $body .= "($line) Tj\n";
        }
        $body .= 'ET';

        $objects = [];
        $objects[] = '1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj';
        $objects[] = '2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj';
        $objects[] = '3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $pageWidth . ' ' . $pageHeight . '] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>endobj';
        $objects[] = '4 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>endobj';
        $objects[] = "5 0 obj<< /Length " . strlen($body) . " >>stream\n$body\nendstream endobj";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj . "\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d 00000 n \n', $offsets[$i]);
        }
        $pdf .= "trailer<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF";

        return $pdf;
    }
    public function export() {
        $db = Database::getInstance();
        [$whereI, $whereIParams] = Tenant::storeFilter('i');
        $q = trim($_GET['q'] ?? '');
        $dueState = trim($_GET['due_state'] ?? '');
        $duePreset = trim($_GET['due_preset'] ?? '');
        $dueFrom = trim($_GET['due_from'] ?? '');
        $dueTo = trim($_GET['due_to'] ?? '');
        $sort = trim($_GET['sort'] ?? 'due_asc');

        if (!in_array($dueState, ['overdue', 'today', 'upcoming', 'no_date'], true)) {
            $dueState = '';
        }
        if (!in_array($sort, ['due_asc', 'due_desc', 'latest'], true)) {
            $sort = 'due_asc';
        }

        $creditWhere = "
            WHERE (
                    LOWER(COALESCE(i.payment_method, '')) = 'credit'
                    OR i.credit_due_date IS NOT NULL
                    OR COALESCE(i.is_credit_settled, 0) = 0
                    OR i.balance_due > 0
                  )
              AND GREATEST((COALESCE(i.total, 0) - COALESCE(i.paid_amount, 0)), COALESCE(i.balance_due, 0), 0) > 0
              AND $whereI
        ";
        $creditParams = $whereIParams;

        if ($q !== '') {
            $creditWhere .= " AND (
                i.custom_id LIKE ?
                OR COALESCE(i.customer_name_snapshot, '') LIKE ?
                OR COALESCE(i.customer_phone_snapshot, '') LIKE ?
                OR COALESCE(i.credit_private_note, '') LIKE ?
                OR COALESCE(c.name, '') LIKE ?
                OR COALESCE(c.phone, '') LIKE ?
            )";
            for ($i = 0; $i < 6; $i++) {
                $creditParams[] = "%$q%";
            }
        }

        if ($dueState === 'overdue') {
            $creditWhere .= " AND i.credit_due_date IS NOT NULL AND DATE(i.credit_due_date) < CURDATE()";
        } elseif ($dueState === 'today') {
            $creditWhere .= " AND i.credit_due_date IS NOT NULL AND DATE(i.credit_due_date) = CURDATE()";
        } elseif ($dueState === 'upcoming') {
            $creditWhere .= " AND i.credit_due_date IS NOT NULL AND DATE(i.credit_due_date) > CURDATE()";
        } elseif ($dueState === 'no_date') {
            $creditWhere .= " AND i.credit_due_date IS NULL";
        }

        if ($dueFrom !== '') {
            $creditWhere .= " AND i.credit_due_date IS NOT NULL AND DATE(i.credit_due_date) >= ?";
            $creditParams[] = $dueFrom;
        }
        if ($dueTo !== '') {
            $creditWhere .= " AND i.credit_due_date IS NOT NULL AND DATE(i.credit_due_date) <= ?";
            $creditParams[] = $dueTo;
        }

        $orderBy = "ORDER BY i.credit_due_date IS NULL, i.credit_due_date ASC, i.created_at DESC";
        if ($sort === 'due_desc') {
            $orderBy = "ORDER BY i.credit_due_date IS NULL, i.credit_due_date DESC, i.created_at DESC";
        } elseif ($sort === 'latest') {
            $orderBy = "ORDER BY i.created_at DESC";
        }

        $credits = $db->fetchAll("
            SELECT
                i.*,
                COALESCE(c.name, i.customer_name_snapshot, 'Walk-in Customer') AS customer_name,
                COALESCE(c.phone, i.customer_phone_snapshot, '') AS customer_phone,
                GREATEST((COALESCE(i.total, 0) - COALESCE(i.paid_amount, 0)), COALESCE(i.balance_due, 0), 0) AS open_due
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            $creditWhere
            $orderBy
        ", $creditParams);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="credit_dues_export_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Invoice', 'Customer', 'Phone', 'Total', 'Paid', 'Credit Due', 'Due Date', 'Date', 'Notes']);
        foreach ($credits as $row) {
            fputcsv($output, [
                $row['custom_id'], $row['customer_name'], $row['customer_phone'], $row['total'],
                $row['paid_amount'], $row['open_due'], $row['credit_due_date'] ? date('Y-m-d', strtotime($row['credit_due_date'])) : '',
                $row['created_at'], $row['credit_private_note']
            ]);
        }
        fclose($output);
        exit;
    }

    public function import() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file']['tmp_name'])) {
            $this->redirect('credits/index');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $handle = fopen($_FILES['file']['tmp_name'], "r");
        $header = fgetcsv($handle);
        if (!$header) {
            $this->flash('error', 'CSV file is empty or invalid.');
            $this->redirect('credits/index');
        }

        $colMap = [];
        foreach ($header as $idx => $colName) {
            $colMap[strtolower(trim($colName))] = $idx;
        }

        $imported = 0;
        $storeId = Tenant::currentStoreId();
        $userId = Auth::id();

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $invoice = isset($colMap['invoice']) ? trim($data[$colMap['invoice']] ?? '') : '';
            $due = isset($colMap['credit due']) ? max(0, (float) ($data[$colMap['credit due']] ?? 0)) : (isset($colMap['remaining']) ? max(0, (float) ($data[$colMap['remaining']] ?? 0)) : 0);
            if ($invoice === '' && $due <= 0) {
                continue;
            }

            $customer = isset($colMap['customer']) ? trim($data[$colMap['customer']] ?? 'Walk-in Customer') : 'Walk-in Customer';
            $phone = isset($colMap['phone']) ? trim($data[$colMap['phone']] ?? '') : '';
            $total = isset($colMap['total']) ? max(0, (float) ($data[$colMap['total']] ?? $due)) : $due;
            $paid = isset($colMap['paid']) ? max(0, (float) ($data[$colMap['paid']] ?? 0)) : 0;
            $dueDate = isset($colMap['due date']) ? trim($data[$colMap['due date']] ?? '') : '';
            $date = isset($colMap['date']) ? trim($data[$colMap['date']] ?? date('Y-m-d H:i:s')) : date('Y-m-d H:i:s');
            $notes = isset($colMap['notes']) ? trim($data[$colMap['notes']] ?? '') : '';

            if (strtotime($date) === false) {
                $date = date('Y-m-d H:i:s');
            } else {
                $date = date('Y-m-d H:i:s', strtotime($date));
            }

            if ($dueDate !== '' && strtotime($dueDate) !== false) {
                $dueDate = date('Y-m-d 17:00:00', strtotime($dueDate));
            } else {
                $dueDate = null;
            }

            if ($invoice !== '') {
                $exists = $db->fetch("SELECT id FROM invoices WHERE custom_id = ? AND store_id = ? LIMIT 1", [$invoice, $storeId]);
                if ($exists) {
                    continue;
                }
            } else {
                $invoice = 'CRD-' . date('Ymd-His', strtotime($date)) . '-' . rand(100, 999);
            }

            $db->query("
                INSERT INTO invoices
                (store_id, custom_id, user_id, customer_id, subtotal, discount, total, status, transaction_type, payment_method, sale_channel, paid_amount, balance_due, is_credit_settled, credit_due_date, credit_private_note, customer_name_snapshot, customer_phone_snapshot, created_at)
                VALUES (?, ?, ?, NULL, ?, 0, ?, 'unpaid', 'Sale', 'credit', 'retail', ?, ?, 0, ?, ?, ?, ?, ?)
            ", [
                $storeId,
                $invoice,
                $userId,
                $total,
                $total,
                $paid,
                $due,
                $dueDate,
                $notes ?: null,
                $customer,
                $phone ?: null,
                $date
            ]);

            $invoiceId = $db->lastInsertId();

            $db->query("
                INSERT INTO invoice_items
                (store_id, invoice_id, product_id, product_name, model_number, quantity, price, price_tier, discount, total)
                VALUES (?, ?, NULL, 'Imported Credit Sale', 'Imported', 1, ?, 'retail', 0, ?)
            ", [
                $storeId,
                $invoiceId,
                $total,
                $total
            ]);

            $imported++;
        }

        fclose($handle);
        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [$storeId, $userId, 'Credits', "Imported $imported credit invoices from CSV"]);
        $this->flash('success', $imported . ' credit invoices imported successfully.');
        $this->redirect('credits/index');
    }
}






