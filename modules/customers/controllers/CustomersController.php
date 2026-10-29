<?php

class CustomersController extends Controller {
    private $columnCache = [];

    public function __construct() {
        AuthMiddleware::handlePermission('customers');
    }

    public function index() {
        $db = Database::getInstance();
        $connection = $db->getConnection();
        $this->ensureAddressColumns($connection);
        $q = trim($_GET['q'] ?? '');

        $hasAddress = $this->hasColumn($connection, 'customers', 'address');
        $addressSelect = $hasAddress ? "COALESCE(c.address, '') AS customer_address" : "'' AS customer_address";
        [$whereC, $paramsC] = Tenant::storeFilter('c');
        [$whereI, $paramsI] = Tenant::storeFilter();

        $sql = "
            SELECT
                c.*,
                $addressSelect,
                COALESCE(d.total_due, 0) AS outstanding_due
            FROM customers c
            LEFT JOIN (
                SELECT customer_id, SUM(GREATEST((COALESCE(total,0)-COALESCE(paid_amount,0)), COALESCE(balance_due,0), 0)) AS total_due
                FROM invoices
                WHERE customer_id IS NOT NULL AND $whereI
                GROUP BY customer_id
            ) d ON d.customer_id = c.id
            WHERE $whereC
        ";
        $params = array_merge($paramsI, $paramsC);

        if ($q !== '') {
            $sql .= " AND (c.name LIKE ? OR c.phone LIKE ?)";
            $params = array_merge($params, ["%$q%", "%$q%"]);
        }

        $sql .= " ORDER BY c.created_at DESC";
        $customers = $db->fetchAll($sql, $params);

        $this->view('../../modules/customers/views/index', [
            'pageTitle' => 'Customers',
            'activeMenu' => 'customers',
            'customers' => $customers,
            'query' => $q,
            'hasAddress' => $hasAddress
        ]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('customers/index');
        }
        $this->verifyCsrfOrAbort();

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($name === '' || $phone === '') {
            $this->flash('error', 'Customer name and phone are required.');
            $this->redirect('customers/index');
        }

        $db = Database::getInstance();
        $connection = $db->getConnection();
        $this->ensureAddressColumns($connection);
        $hasAddress = $this->hasColumn($connection, 'customers', 'address');

        try {
            if ($hasAddress) {
                $db->query("INSERT INTO customers (store_id, name, phone, address) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), $name, $phone, $address !== '' ? $address : null]);
            } else {
                $db->query("INSERT INTO customers (store_id, name, phone) VALUES (?, ?, ?)", [Tenant::currentStoreId(), $name, $phone]);
            }
            $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), Auth::id(), 'Customer', "Added customer: $name"]);
            $this->flash('success', 'Customer saved successfully.');
        } catch (Exception $e) {
            $this->flash('error', 'Customer could not be saved. Phone number may already exist.');
        }

        $this->redirect('customers/index');
    }

    public function edit($id) {
        $db = Database::getInstance();
        $connection = $db->getConnection();
        $this->ensureAddressColumns($connection);

        [$where, $whereParams] = Tenant::storeFilter();
        $customer = $db->fetch("SELECT * FROM customers WHERE id = ? AND $where", array_merge([$id], $whereParams));
        if (!$customer) {
            die("Not found");
        }

        $hasAddress = $this->hasColumn($connection, 'customers', 'address');
        $hasInvoiceAddressSnapshot = $this->hasColumn($connection, 'invoices', 'customer_address_snapshot');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrfOrAbort();
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if ($name === '' || $phone === '') {
                $this->flash('error', 'Customer name and phone are required.');
                $this->redirect('customers/edit/' . $id);
            }

            try {
                if ($hasAddress) {
                    $db->query("UPDATE customers SET name = ?, phone = ?, address = ? WHERE id = ? AND $where", array_merge([$name, $phone, $address !== '' ? $address : null, $id], $whereParams));
                } else {
                    $db->query("UPDATE customers SET name = ?, phone = ? WHERE id = ? AND $where", array_merge([$name, $phone, $id], $whereParams));
                }

                $db->query("UPDATE invoices SET customer_name_snapshot = ?, customer_phone_snapshot = ? WHERE customer_id = ? AND $where", array_merge([$name, $phone, $id], $whereParams));
                if ($hasInvoiceAddressSnapshot) {
                    $db->query("UPDATE invoices SET customer_address_snapshot = ? WHERE customer_id = ? AND $where", array_merge([$address !== '' ? $address : null, $id], $whereParams));
                }

                $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), Auth::id(), 'Customer', "Updated customer: $name"]);
                $this->flash('success', 'Customer updated successfully.');
            } catch (Exception $e) {
                $this->flash('error', 'Customer could not be updated. Phone number may already exist.');
            }

            $this->redirect('customers/edit/' . $id);
        }

        $invoices = $db->fetchAll("SELECT *, GREATEST((COALESCE(total,0)-COALESCE(paid_amount,0)), COALESCE(balance_due,0), 0) AS open_due FROM invoices WHERE customer_id = ? AND $where ORDER BY created_at DESC", array_merge([$id], $whereParams));

        $this->view('../../modules/customers/views/edit', [
            'pageTitle' => 'Edit Customer',
            'activeMenu' => 'customers',
            'customer' => $customer,
            'invoices' => $invoices,
            'hasAddress' => $hasAddress
        ]);
    }

    public function viewProfile($id) {
        $db = Database::getInstance();
        $connection = $db->getConnection();
        $this->ensureAddressColumns($connection);
        [$where, $whereParams] = Tenant::storeFilter();
        $customer = $db->fetch("SELECT * FROM customers WHERE id = ? AND $where", array_merge([$id], $whereParams));
        if (!$customer) {
            die("Not found");
        }

        $invoices = $db->fetchAll("SELECT *, GREATEST((COALESCE(total,0)-COALESCE(paid_amount,0)), COALESCE(balance_due,0), 0) AS open_due FROM invoices WHERE customer_id = ? AND $where ORDER BY created_at DESC", array_merge([$id], $whereParams));
        $paidAmount = 0.0;
        $remainingAmount = 0.0;
        $invoiceTotal = 0.0;
        foreach ($invoices as $invoice) {
            $total = (float) ($invoice['total'] ?? 0);
            $openDue = (float) ($invoice['open_due'] ?? 0);
            $paidAmount += max(0, $total - $openDue);
            $remainingAmount += max(0, $openDue);
            $invoiceTotal += max(0, $total);
        }

        $this->view('../../modules/customers/views/view', [
            'pageTitle' => $customer['name'] . ' - Profile',
            'activeMenu' => 'customers',
            'customer' => $customer,
            'invoices' => $invoices,
            'hasAddress' => $this->hasColumn($connection, 'customers', 'address'),
            'summary' => [
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'invoice_total' => $invoiceTotal,
                'order_count' => count($invoices),
            ]
        ]);
    }

    public function delete($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $customer = $db->fetch("SELECT * FROM customers WHERE id = ? AND $where", array_merge([$id], $whereParams));
        if ($customer) {
            $db->query("UPDATE invoices SET customer_id = NULL WHERE customer_id = ? AND $where", array_merge([$id], $whereParams));
            $db->query("DELETE FROM customers WHERE id = ? AND $where", array_merge([$id], $whereParams));
            $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), Auth::id(), 'Customer', "Deleted customer: " . $customer['name']]);
            $this->flash('success', 'Customer deleted successfully.');
        }

        $this->redirect('customers/index');
    }

    public function sampleTemplate() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="customers_sample_template.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['Name', 'Phone', 'Address', 'Total_Spending']);
        fputcsv($output, ['Muhammad Ali', '03001234567', 'Shop # 12, Main Market', '0.00']);
        fputcsv($output, ['Usman Khan', '03129876543', 'House 45, Street 8, Lake City', '0.00']);
        
        fclose($output);
        exit;
    }

    public function import() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file']['tmp_name'])) {
            $this->flash('error', 'Please choose a valid CSV file to upload.');
            $this->redirect('customers/index?tab=import');
        }
        $this->verifyCsrfOrAbort();

        $updateExisting = !empty($_POST['update_existing']);
        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        [$where, $params] = Tenant::storeFilter();

        $handle = fopen($_FILES['file']['tmp_name'], "r");
        if (!$handle) {
            $this->flash('error', 'Unable to read the uploaded CSV file.');
            $this->redirect('customers/index?tab=import');
        }

        $rawHeader = fgetcsv($handle);
        if (!is_array($rawHeader)) {
            fclose($handle);
            $this->flash('error', 'The CSV file appears to be empty.');
            $this->redirect('customers/index?tab=import');
        }

        $map = [];
        foreach ($rawHeader as $idx => $colName) {
            $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', (string)$colName)));
            $map[$clean] = $idx;
        }

        $idxName = $map['name'] ?? 0;
        $idxPhone = $map['phone'] ?? ($map['mobile'] ?? 1);
        $idxAddress = $map['address'] ?? 2;
        $idxSpending = $map['total_spending'] ?? ($map['spending'] ?? 3);

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle, 2000, ",")) !== false) {
            $name = trim((string) ($row[$idxName] ?? ''));
            $phone = trim((string) ($row[$idxPhone] ?? ''));
            $address = trim((string) ($row[$idxAddress] ?? ''));
            $spending = max(0, (float) ($row[$idxSpending] ?? 0));

            if ($name === '' && $phone === '') {
                $skipped++;
                continue;
            }
            if ($name === '') {
                $name = 'Customer ' . $phone;
            }

            // Check if customer exists by phone
            $existing = null;
            if ($phone !== '') {
                $existing = $db->fetch("SELECT id FROM customers WHERE phone = ? AND $where LIMIT 1", array_merge([$phone], $params));
            }

            if ($existing) {
                if ($updateExisting) {
                    $db->query("
                        UPDATE customers 
                        SET name = ?, address = COALESCE(?, address)
                        WHERE id = ? AND $where
                    ", array_merge([$name, $address ?: null, (int)$existing['id']], $params));
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                $db->query("
                    INSERT INTO customers (store_id, name, phone, address, total_spending, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ", [
                    $storeId,
                    $name,
                    $phone ?: null,
                    $address ?: null,
                    $spending
                ]);
                $imported++;
            }
        }

        fclose($handle);

        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [
            $storeId,
            Auth::id(),
            'Customer Bulk Import',
            "Imported: $imported new customers, Updated: $updated, Skipped: $skipped"
        ]);

        $this->flash('success', "Bulk Import Complete: $imported new customers added, $updated updated, $skipped skipped.");
        $this->redirect('customers/index?tab=list');
    }

    public function export() {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $customers = $db->fetchAll("SELECT name, phone, address, total_spending, created_at FROM customers WHERE $where ORDER BY name ASC", $whereParams);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="customers_export_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['Name', 'Phone', 'Address', 'Total Spending', 'Joined Date']);
        foreach ($customers as $row) {
            fputcsv($output, [
                $row['name'],
                $row['phone'] ?? '',
                $row['address'] ?? '',
                $row['total_spending'] ?? 0,
                $row['created_at'] ?? ''
            ]);
        }
        fclose($output);
        exit;
    }

    private function ensureAddressColumns($connection) {
        try {
            if (!$this->hasColumn($connection, 'customers', 'address')) {
                $connection->exec("ALTER TABLE customers ADD COLUMN address VARCHAR(255) NULL AFTER phone");
                $this->columnCache['customers.address'] = true;
            }
        } catch (Exception $e) {
            // Keep customer pages working if schema update is restricted.
        }

        try {
            if (!$this->hasColumn($connection, 'invoices', 'customer_address_snapshot')) {
                $connection->exec("ALTER TABLE invoices ADD COLUMN customer_address_snapshot VARCHAR(255) NULL AFTER customer_phone_snapshot");
                $this->columnCache['invoices.customer_address_snapshot'] = true;
            }
        } catch (Exception $e) {
            // Keep customer pages working if schema update is restricted.
        }
    }

    private function hasColumn($connection, $table, $column) {
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->columnCache)) {
            return $this->columnCache[$cacheKey];
        }

        try {
            $stmt = $connection->query("SHOW COLUMNS FROM `$table` LIKE " . $connection->quote($column));
            $exists = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $exists = false;
        }

        $this->columnCache[$cacheKey] = $exists;
        return $exists;
    }
}

