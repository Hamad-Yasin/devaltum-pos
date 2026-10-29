<?php

class ReturnsController extends Controller {

    public function __construct() {
        AuthMiddleware::handlePermission('sales'); // Using sales permission as fallback for now, or add 'returns' permission
    }

    public function index() {
        $db = Database::getInstance();
        [$whereI, $whereIParams] = Tenant::storeFilter('i');
        $q = trim($_GET['q'] ?? '');
        $datePreset = trim($_GET['date_preset'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');
        if ($datePreset === '' && ($dateFrom !== '' || $dateTo !== '')) {
            $datePreset = 'custom';
        }
        if ($datePreset !== 'custom') {
            $today = date('Y-m-d');
            if ($datePreset === 'today') {
                $dateFrom = $today;
                $dateTo = $today;
            } elseif ($datePreset === 'yesterday') {
                $dateFrom = date('Y-m-d', strtotime('-1 day'));
                $dateTo = $dateFrom;
            } elseif ($datePreset === 'this_week') {
                $dateFrom = date('Y-m-d', strtotime('monday this week'));
                $dateTo = $today;
            } elseif ($datePreset === 'this_month') {
                $dateFrom = date('Y-m-01');
                $dateTo = $today;
            }
        }

        $sql = "
            SELECT
                i.*,
                COALESCE(c.name, i.customer_name_snapshot, 'Walk-in Customer') AS customer_display,
                COALESCE(c.phone, i.customer_phone_snapshot, '') AS customer_phone_display,
                GROUP_CONCAT(DISTINCT ii.product_name ORDER BY ii.product_name SEPARATOR ', ') AS product_names,
                GROUP_CONCAT(DISTINCT COALESCE(ii.model_number, '-') ORDER BY ii.model_number SEPARATOR ', ') AS model_names
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
            WHERE $whereI AND i.transaction_type IN ('Return', 'Refund')
        ";
        $params = $whereIParams;

        if ($q !== '') {
            $sql .= " AND (i.custom_id LIKE ? OR c.name LIKE ? OR i.customer_name_snapshot LIKE ? OR ii.product_name LIKE ? OR ii.model_number LIKE ?)";
            array_push($params, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%");
        }
        if ($dateFrom !== '') {
            $sql .= " AND DATE(i.created_at) >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $sql .= " AND DATE(i.created_at) <= ?";
            $params[] = $dateTo;
        }

        $sql .= " GROUP BY i.id ORDER BY i.created_at DESC";
        $returns = $db->fetchAll($sql, $params);

        $this->view('../../modules/returns/views/index', [
            'pageTitle' => 'Returns & Refunds',
            'activeMenu' => 'returns',
            'returns' => $returns,
            'filters' => compact('q', 'datePreset', 'dateFrom', 'dateTo')
        ]);
    }

    public function action($id) {
        // Just redirect to sales view for simplicity, as returns are invoices
        $this->redirect('sales/action/' . $id);
    }

    public function export() {
        $db = Database::getInstance();
        [$whereI, $whereIParams] = Tenant::storeFilter('i');
        $q = trim($_GET['q'] ?? '');
        $datePreset = trim($_GET['date_preset'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');
        if ($datePreset === '' && ($dateFrom !== '' || $dateTo !== '')) {
            $datePreset = 'custom';
        }
        if ($datePreset !== 'custom') {
            $today = date('Y-m-d');
            if ($datePreset === 'today') {
                $dateFrom = $today;
                $dateTo = $today;
            } elseif ($datePreset === 'yesterday') {
                $dateFrom = date('Y-m-d', strtotime('-1 day'));
                $dateTo = $dateFrom;
            } elseif ($datePreset === 'this_week') {
                $dateFrom = date('Y-m-d', strtotime('monday this week'));
                $dateTo = $today;
            } elseif ($datePreset === 'this_month') {
                $dateFrom = date('Y-m-01');
                $dateTo = $today;
            }
        }

        $sql = "
            SELECT
                i.custom_id AS Invoice,
                i.transaction_type AS Type,
                COALESCE(c.name, i.customer_name_snapshot, 'Walk-in Customer') AS Customer,
                COALESCE(c.phone, i.customer_phone_snapshot, '') AS Phone,
                i.total AS Total,
                i.created_at AS Date,
                i.notes AS Notes
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
            WHERE $whereI AND i.transaction_type IN ('Return', 'Refund')
        ";
        $params = $whereIParams;

        if ($q !== '') {
            $sql .= " AND (i.custom_id LIKE ? OR c.name LIKE ? OR i.customer_name_snapshot LIKE ? OR ii.product_name LIKE ? OR ii.model_number LIKE ?)";
            array_push($params, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%");
        }
        if ($dateFrom !== '') {
            $sql .= " AND DATE(i.created_at) >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $sql .= " AND DATE(i.created_at) <= ?";
            $params[] = $dateTo;
        }

        $sql .= " GROUP BY i.id ORDER BY i.created_at DESC";
        $returns = $db->fetchAll($sql, $params);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="returns_export_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Invoice', 'Type', 'Customer', 'Phone', 'Amount', 'Date', 'Notes']);
        foreach ($returns as $row) {
            fputcsv($output, [
                $row['Invoice'], $row['Type'], $row['Customer'], $row['Phone'],
                abs((float) $row['Total']), $row['Date'], $row['Notes']
            ]);
        }
        fclose($output);
        exit;
    }

    public function import() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file']['tmp_name'])) {
            $this->redirect('returns/index');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $handle = fopen($_FILES['file']['tmp_name'], "r");
        $header = fgetcsv($handle);
        if (!$header) {
            $this->flash('error', 'CSV file is empty or invalid.');
            $this->redirect('returns/index');
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
            $amount = isset($colMap['amount']) ? abs((float) ($data[$colMap['amount']] ?? 0)) : 0;
            if ($invoice === '' && $amount <= 0) {
                continue;
            }

            $type = isset($colMap['type']) ? trim($data[$colMap['type']] ?? 'Return') : 'Return';
            $customer = isset($colMap['customer']) ? trim($data[$colMap['customer']] ?? 'Walk-in Customer') : 'Walk-in Customer';
            $phone = isset($colMap['phone']) ? trim($data[$colMap['phone']] ?? '') : '';
            $date = isset($colMap['date']) ? trim($data[$colMap['date']] ?? date('Y-m-d H:i:s')) : date('Y-m-d H:i:s');
            $notes = isset($colMap['notes']) ? trim($data[$colMap['notes']] ?? '') : '';

            if (strtotime($date) === false) {
                $date = date('Y-m-d H:i:s');
            } else {
                $date = date('Y-m-d H:i:s', strtotime($date));
            }

            if ($invoice !== '') {
                $exists = $db->fetch("SELECT id FROM invoices WHERE custom_id = ? AND store_id = ? LIMIT 1", [$invoice, $storeId]);
                if ($exists) {
                    continue;
                }
            } else {
                $invoice = 'RET-' . date('Ymd-His', strtotime($date)) . '-' . rand(100, 999);
            }

            $db->query("
                INSERT INTO invoices
                (store_id, custom_id, user_id, customer_id, subtotal, discount, total, status, transaction_type, payment_method, sale_channel, paid_amount, balance_due, is_credit_settled, customer_name_snapshot, customer_phone_snapshot, notes, created_at)
                VALUES (?, ?, ?, NULL, ?, 0, ?, 'paid', ?, 'cash', 'retail', ?, 0, 1, ?, ?, ?, ?)
            ", [
                $storeId,
                $invoice,
                $userId,
                $amount,
                $amount,
                $type,
                $amount,
                $customer,
                $phone ?: null,
                $notes ?: null,
                $date
            ]);

            $invoiceId = $db->lastInsertId();

            $db->query("
                INSERT INTO invoice_items
                (store_id, invoice_id, product_id, product_name, model_number, quantity, price, price_tier, discount, total)
                VALUES (?, ?, NULL, ?, 'Imported', 1, ?, 'retail', 0, ?)
            ", [
                $storeId,
                $invoiceId,
                'Imported Returned Item (' . $type . ')',
                $amount,
                $amount
            ]);

            $imported++;
        }

        fclose($handle);
        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [$storeId, $userId, 'Sales', "Imported $imported returned invoices from CSV"]);
        $this->flash('success', $imported . ' returned invoices imported successfully.');
        $this->redirect('returns/index');
    }
}
