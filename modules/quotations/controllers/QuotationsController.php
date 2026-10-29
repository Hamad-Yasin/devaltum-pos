<?php

class QuotationsController extends Controller {

    public function __construct() {
        AuthMiddleware::handlePermission('quotations');
    }

    public function index() {
        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        [$where, $params] = Tenant::storeFilter('q');

        // Auto mark expired pending quotations
        $db->query("
            UPDATE quotations 
            SET status = 'expired', updated_at = NOW() 
            WHERE store_id = ? AND status = 'pending' AND valid_until IS NOT NULL AND valid_until < CURDATE()
        ", [$storeId]);

        $search = trim($_GET['q'] ?? '');
        $statusFilter = trim($_GET['status'] ?? 'all');

        $sql = "SELECT q.*, u.name as creator_name,
                (SELECT COUNT(*) FROM quotation_items qi WHERE qi.quotation_id = q.id) as total_items
                FROM quotations q
                LEFT JOIN users u ON u.id = q.created_by
                WHERE $where";
        $sqlParams = $params;

        if ($search !== '') {
            $sql .= " AND (q.quotation_number LIKE ? OR q.customer_name LIKE ? OR q.customer_phone LIKE ?)";
            array_push($sqlParams, "%$search%", "%$search%", "%$search%");
        }

        if (in_array($statusFilter, ['pending', 'converted', 'expired', 'cancelled'], true)) {
            $sql .= " AND q.status = ?";
            $sqlParams[] = $statusFilter;
        }

        $sql .= " ORDER BY q.id DESC";
        $quotations = $db->fetchAll($sql, $sqlParams);

        // Overall Quotation Stats
        [$whereS, $paramsS] = Tenant::storeFilter();
        $stats = $db->fetch("
            SELECT
                COUNT(*) as total_count,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN total ELSE 0 END), 0) as pending_value,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
                COALESCE(SUM(CASE WHEN status = 'converted' THEN total ELSE 0 END), 0) as converted_value,
                COALESCE(SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END), 0) as converted_count,
                COALESCE(SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END), 0) as expired_count
            FROM quotations
            WHERE $whereS
        ", $paramsS) ?: [
            'total_count' => 0,
            'pending_value' => 0,
            'pending_count' => 0,
            'converted_value' => 0,
            'converted_count' => 0,
            'expired_count' => 0
        ];

        // Fetch products & customers for seamless tabbed quotation creation
        $products = $db->fetchAll("
            SELECT id, name, model_number, barcode, price, wholesale_price, stock, unit_value, unit_type 
            FROM products 
            WHERE $whereS AND (parent_product_id IS NULL OR parent_product_id = 0)
            ORDER BY name ASC
        ", $paramsS);

        $customers = $db->fetchAll("
            SELECT id, name, phone, address 
            FROM customers 
            WHERE $whereS 
            ORDER BY name ASC 
            LIMIT 500
        ", $paramsS);

        $count = (int) ($db->fetch("SELECT COUNT(*) as cnt FROM quotations WHERE store_id = ?", [$storeId])['cnt'] ?? 0);
        $nextNumber = 'QT-' . date('Y') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);

        $taxSettings = [
            'enabled' => App::setting('tax_enabled', '0') === '1',
            'label' => App::setting('tax_label', 'Sales Tax'),
            'rate' => (float) App::setting('tax_rate', '0')
        ];

        $this->view('../../modules/quotations/views/index', [
            'pageTitle' => 'Quotations & Estimates',
            'activeMenu' => 'quotations',
            'quotations' => $quotations,
            'stats' => $stats,
            'query' => $search,
            'statusFilter' => $statusFilter,
            'products' => $products,
            'customers' => $customers,
            'nextNumber' => $nextNumber,
            'taxSettings' => $taxSettings,
            'currencySymbol' => App::currencySymbol()
        ]);
    }

    public function create() {
        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        [$where, $params] = Tenant::storeFilter();

        // Fetch products for quick dropdown/search
        $products = $db->fetchAll("
            SELECT id, name, model_number, barcode, price, wholesale_price, stock, unit_value, unit_type 
            FROM products 
            WHERE $where AND (parent_product_id IS NULL OR parent_product_id = 0)
            ORDER BY name ASC
        ", $params);

        // Fetch customers
        $customers = $db->fetchAll("
            SELECT id, name, phone, address 
            FROM customers 
            WHERE $where 
            ORDER BY name ASC 
            LIMIT 500
        ", $params);

        // Generate default quotation number
        $count = (int) ($db->fetch("SELECT COUNT(*) as cnt FROM quotations WHERE store_id = ?", [$storeId])['cnt'] ?? 0);
        $nextNumber = 'QT-' . date('Y') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);

        $taxSettings = [
            'enabled' => App::setting('tax_enabled', '0') === '1',
            'label' => App::setting('tax_label', 'Sales Tax'),
            'rate' => (float) App::setting('tax_rate', '0')
        ];

        $this->view('../../modules/quotations/views/create', [
            'pageTitle' => 'Create New Quotation',
            'activeMenu' => 'quotations',
            'nextNumber' => $nextNumber,
            'products' => $products,
            'customers' => $customers,
            'taxSettings' => $taxSettings,
            'currencySymbol' => App::currencySymbol()
        ]);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method'], 400);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $token = (string) ($input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (!Security::verifyCsrfToken($token)) {
            $this->jsonResponse(['error' => 'Security token mismatch. Refresh and try again.'], 419);
        }

        $items = $input['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            $this->jsonResponse(['error' => 'Please add at least one product item to the quotation.'], 400);
        }

        $customerName = trim((string) ($input['customer_name'] ?? ''));
        if ($customerName === '') {
            $customerName = 'Walk-in Customer';
        }

        $customerId = !empty($input['customer_id']) ? (int) $input['customer_id'] : null;
        $customerPhone = trim((string) ($input['customer_phone'] ?? ''));
        $customerAddress = trim((string) ($input['customer_address'] ?? ''));
        $quotationNumber = trim((string) ($input['quotation_number'] ?? ''));
        $validDays = max(1, (int) ($input['valid_days'] ?? 15));
        $validUntil = date('Y-m-d', strtotime("+{$validDays} days"));
        $notes = trim((string) ($input['notes'] ?? ''));

        $discount = max(0, (float) ($input['discount'] ?? 0));
        $taxRate = max(0, (float) ($input['tax_rate'] ?? 0));

        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $storeId = Tenant::currentStoreId();

        if (empty($quotationNumber)) {
            $count = (int) ($db->fetch("SELECT COUNT(*) as cnt FROM quotations WHERE store_id = ?", [$storeId])['cnt'] ?? 0);
            $quotationNumber = 'QT-' . date('Y') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
        }

        try {
            $pdo->beginTransaction();

            $subtotal = 0;
            $itemsProcessed = [];

            foreach ($items as $item) {
                $pName = trim((string) ($item['name'] ?? 'Item'));
                $pId = !empty($item['product_id']) ? (int) $item['product_id'] : null;
                $model = trim((string) ($item['model_number'] ?? ''));
                $qty = max(0.001, (float) ($item['quantity'] ?? 1));
                $unit = trim((string) ($item['unit_type'] ?? 'pcs'));
                $price = max(0, (float) ($item['price'] ?? 0));
                $itemDiscount = max(0, (float) ($item['discount'] ?? 0));
                $itemTotal = round(max(0, ($qty * $price) - $itemDiscount), 2);

                $subtotal += $itemTotal;
                $itemsProcessed[] = [
                    'product_id' => $pId,
                    'product_name' => $pName,
                    'model_number' => $model ?: null,
                    'quantity' => $qty,
                    'unit_type' => $unit,
                    'price' => $price,
                    'discount' => $itemDiscount,
                    'total' => $itemTotal
                ];
            }

            $afterDiscount = max(0, $subtotal - $discount);
            $taxAmount = round(($afterDiscount * $taxRate) / 100, 2);
            $grandTotal = round($afterDiscount + $taxAmount, 2);

            $stmtQ = $pdo->prepare("
                INSERT INTO quotations 
                    (store_id, quotation_number, customer_id, customer_name, customer_phone, customer_address, subtotal, discount, tax_amount, tax_rate, total, status, valid_until, notes, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())
            ");
            $stmtQ->execute([
                $storeId,
                $quotationNumber,
                $customerId,
                $customerName,
                $customerPhone ?: null,
                $customerAddress ?: null,
                $subtotal,
                $discount,
                $taxAmount,
                $taxRate,
                $grandTotal,
                $validUntil,
                $notes ?: null,
                Auth::id()
            ]);
            $quotationId = (int) $pdo->lastInsertId();

            $stmtItem = $pdo->prepare("
                INSERT INTO quotation_items 
                    (store_id, quotation_id, product_id, product_name, model_number, quantity, unit_type, price, discount, total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($itemsProcessed as $it) {
                $stmtItem->execute([
                    $storeId,
                    $quotationId,
                    $it['product_id'],
                    $it['product_name'],
                    $it['model_number'],
                    $it['quantity'],
                    $it['unit_type'],
                    $it['price'],
                    $it['discount'],
                    $it['total']
                ]);
            }

            $pdo->commit();

            $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [
                $storeId,
                Auth::id(),
                'Quotation Created',
                "Generated quotation {$quotationNumber} for {$customerName} | " . App::money($grandTotal)
            ]);

            $this->jsonResponse([
                'success' => true,
                'quotation_id' => $quotationId,
                'redirect' => (defined('BASE_URL') ? BASE_URL : '') . '/quotations/view/' . $quotationId
            ]);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->jsonResponse(['error' => 'Failed to save quotation: ' . $e->getMessage()], 500);
        }
    }

    public function edit($id) {
        $id = (int) $id;
        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        [$where, $params] = Tenant::storeFilter('q');

        $quotation = $db->fetch("SELECT * FROM quotations q WHERE q.id = ? AND $where LIMIT 1", array_merge([$id], $params));
        if (!$quotation) {
            $this->flash('error', 'Quotation not found.');
            $this->redirect('quotations/index');
        }

        if ($quotation['status'] === 'converted') {
            $this->flash('error', 'Converted quotations cannot be edited.');
            $this->redirect('quotations/view/' . $id);
        }

        $items = $db->fetchAll("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY id ASC", [$id]);

        [$whereP, $paramsP] = Tenant::storeFilter();
        $products = $db->fetchAll("
            SELECT id, name, model_number, barcode, price, wholesale_price, stock, unit_value, unit_type 
            FROM products 
            WHERE $whereP AND (parent_product_id IS NULL OR parent_product_id = 0)
            ORDER BY name ASC
        ", $paramsP);

        $customers = $db->fetchAll("
            SELECT id, name, phone, address 
            FROM customers 
            WHERE $whereP 
            ORDER BY name ASC 
            LIMIT 500
        ", $paramsP);

        $taxSettings = [
            'enabled' => App::setting('tax_enabled', '0') === '1',
            'label' => App::setting('tax_label', 'Sales Tax'),
            'rate' => (float) App::setting('tax_rate', '0')
        ];

        $this->view('../../modules/quotations/views/create', [
            'pageTitle' => 'Edit Quotation ' . $quotation['quotation_number'],
            'activeMenu' => 'quotations',
            'isEdit' => true,
            'quotation' => $quotation,
            'items' => $items,
            'products' => $products,
            'customers' => $customers,
            'taxSettings' => $taxSettings,
            'currencySymbol' => App::currencySymbol()
        ]);
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method'], 400);
        }

        $id = (int) $id;
        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        [$where, $params] = Tenant::storeFilter('q');

        $quotation = $db->fetch("SELECT * FROM quotations q WHERE q.id = ? AND $where LIMIT 1", array_merge([$id], $params));
        if (!$quotation) {
            $this->jsonResponse(['error' => 'Quotation not found.'], 404);
        }

        if ($quotation['status'] === 'converted') {
            $this->jsonResponse(['error' => 'Converted quotations cannot be modified.'], 400);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $token = (string) ($input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (!Security::verifyCsrfToken($token)) {
            $this->jsonResponse(['error' => 'Security token mismatch. Refresh and try again.'], 419);
        }

        $items = $input['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            $this->jsonResponse(['error' => 'Please add at least one product item.'], 400);
        }

        $customerName = trim((string) ($input['customer_name'] ?? 'Walk-in Customer'));
        $customerId = !empty($input['customer_id']) ? (int) $input['customer_id'] : null;
        $customerPhone = trim((string) ($input['customer_phone'] ?? ''));
        $customerAddress = trim((string) ($input['customer_address'] ?? ''));
        $validDays = max(1, (int) ($input['valid_days'] ?? 15));
        $validUntil = date('Y-m-d', strtotime("+{$validDays} days"));
        $notes = trim((string) ($input['notes'] ?? ''));
        $discount = max(0, (float) ($input['discount'] ?? 0));
        $taxRate = max(0, (float) ($input['tax_rate'] ?? 0));

        $pdo = $db->getConnection();
        try {
            $pdo->beginTransaction();

            $subtotal = 0;
            $itemsProcessed = [];

            foreach ($items as $item) {
                $pName = trim((string) ($item['name'] ?? 'Item'));
                $pId = !empty($item['product_id']) ? (int) $item['product_id'] : null;
                $model = trim((string) ($item['model_number'] ?? ''));
                $qty = max(0.001, (float) ($item['quantity'] ?? 1));
                $unit = trim((string) ($item['unit_type'] ?? 'pcs'));
                $price = max(0, (float) ($item['price'] ?? 0));
                $itemDiscount = max(0, (float) ($item['discount'] ?? 0));
                $itemTotal = round(max(0, ($qty * $price) - $itemDiscount), 2);

                $subtotal += $itemTotal;
                $itemsProcessed[] = [
                    'product_id' => $pId,
                    'product_name' => $pName,
                    'model_number' => $model ?: null,
                    'quantity' => $qty,
                    'unit_type' => $unit,
                    'price' => $price,
                    'discount' => $itemDiscount,
                    'total' => $itemTotal
                ];
            }

            $afterDiscount = max(0, $subtotal - $discount);
            $taxAmount = round(($afterDiscount * $taxRate) / 100, 2);
            $grandTotal = round($afterDiscount + $taxAmount, 2);

            $stmtQ = $pdo->prepare("
                UPDATE quotations SET
                    customer_id = ?, customer_name = ?, customer_phone = ?, customer_address = ?,
                    subtotal = ?, discount = ?, tax_amount = ?, tax_rate = ?, total = ?,
                    valid_until = ?, notes = ?, updated_at = NOW()
                WHERE id = ? AND store_id = ?
            ");
            $stmtQ->execute([
                $customerId,
                $customerName,
                $customerPhone ?: null,
                $customerAddress ?: null,
                $subtotal,
                $discount,
                $taxAmount,
                $taxRate,
                $grandTotal,
                $validUntil,
                $notes ?: null,
                $id,
                $storeId
            ]);

            // Delete old items and insert new
            $pdo->prepare("DELETE FROM quotation_items WHERE quotation_id = ?")->execute([$id]);

            $stmtItem = $pdo->prepare("
                INSERT INTO quotation_items 
                    (store_id, quotation_id, product_id, product_name, model_number, quantity, unit_type, price, discount, total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($itemsProcessed as $it) {
                $stmtItem->execute([
                    $storeId,
                    $id,
                    $it['product_id'],
                    $it['product_name'],
                    $it['model_number'],
                    $it['quantity'],
                    $it['unit_type'],
                    $it['price'],
                    $it['discount'],
                    $it['total']
                ]);
            }

            $pdo->commit();

            $this->jsonResponse([
                'success' => true,
                'quotation_id' => $id,
                'redirect' => (defined('BASE_URL') ? BASE_URL : '') . '/quotations/view/' . $id
            ]);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->jsonResponse(['error' => 'Failed to update quotation: ' . $e->getMessage()], 500);
        }
    }

    public function show($id) {
        $id = (int) $id;
        $db = Database::getInstance();
        [$where, $params] = Tenant::storeFilter('q');

        $quotation = $db->fetch("
            SELECT q.*, u.name as creator_name 
            FROM quotations q 
            LEFT JOIN users u ON u.id = q.created_by 
            WHERE q.id = ? AND $where 
            LIMIT 1
        ", array_merge([$id], $params));

        if (!$quotation) {
            $this->flash('error', 'Quotation not found.');
            $this->redirect('quotations/index');
        }

        $items = $db->fetchAll("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY id ASC", [$id]);

        $convertedInvoice = null;
        if (!empty($quotation['converted_invoice_id'])) {
            $convertedInvoice = $db->fetch("SELECT id, custom_id, created_at, status, total FROM invoices WHERE id = ? LIMIT 1", [(int) $quotation['converted_invoice_id']]);
        }

        $this->view('../../modules/quotations/views/view', [
            'pageTitle' => 'Quotation ' . $quotation['quotation_number'],
            'activeMenu' => 'quotations',
            'quotation' => $quotation,
            'items' => $items,
            'convertedInvoice' => $convertedInvoice,
            'storeName' => App::setting('company_name', 'DevAltum POS'),
            'storeAddress' => App::setting('company_address', ''),
            'storePhone' => App::setting('whatsapp_number', ''),
            'currencySymbol' => App::currencySymbol()
        ]);
    }

    public function convert($id) {
        $id = (int) $id;
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        [$where, $params] = Tenant::storeFilter('q');

        $quotation = $db->fetch("SELECT * FROM quotations q WHERE q.id = ? AND $where LIMIT 1", array_merge([$id], $params));
        if (!$quotation) {
            $this->flash('error', 'Quotation not found.');
            $this->redirect('quotations/index');
        }

        if ($quotation['status'] === 'converted') {
            $this->flash('error', 'This quotation has already been converted to an official sale invoice.');
            $this->redirect('quotations/view/' . $id);
        }

        $items = $db->fetchAll("
            SELECT qi.*, p.stock as current_stock, p.unit_type as product_unit
            FROM quotation_items qi
            LEFT JOIN products p ON p.id = qi.product_id
            WHERE qi.quotation_id = ?
        ", [$id]);

        if (empty($items)) {
            $this->flash('error', 'Quotation has no items to convert.');
            $this->redirect('quotations/view/' . $id);
        }

        // If GET request, render the dedicated Convert to Sale Studio Page
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $storeId = Tenant::currentStoreId();
            $invCount = (int) ($db->fetch("SELECT COUNT(*) as cnt FROM invoices WHERE store_id = ?", [$storeId])['cnt'] ?? 0);
            $nextInvoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad((string)($invCount + 1), 4, '0', STR_PAD_LEFT);

            $this->view('../../modules/quotations/views/convert', [
                'pageTitle' => 'Convert Quotation to Sale Invoice',
                'activeMenu' => 'quotations',
                'quotation' => $quotation,
                'items' => $items,
                'nextInvoiceNumber' => $nextInvoiceNumber,
                'currencySymbol' => App::currencySymbol()
            ]);
            return;
        }

        $this->verifyCsrfOrAbort();

        $paymentMethod = strtolower(trim((string) ($_POST['payment_method'] ?? 'cash')));
        if (!in_array($paymentMethod, ['cash', 'online', 'credit', 'cheque'], true)) {
            $paymentMethod = 'cash';
        }

        $paidAmount = max(0, (float) ($_POST['paid_amount'] ?? $quotation['total']));
        $balanceDue = round(max(0, (float) $quotation['total'] - $paidAmount), 2);
        $creditDueDate = !empty($_POST['credit_due_date']) ? trim((string) $_POST['credit_due_date']) : null;
        $isCreditSettled = ($balanceDue <= 0) ? 1 : 0;

        $storeId = Tenant::currentStoreId();

        try {
            $pdo->beginTransaction();

            // 1. Generate Invoice Custom ID
            $invCount = (int) ($db->fetch("SELECT COUNT(*) as cnt FROM invoices WHERE store_id = ?", [$storeId])['cnt'] ?? 0);
            $customId = 'INV-' . date('Ymd') . '-' . str_pad((string)($invCount + 1), 4, '0', STR_PAD_LEFT);

            // 2. Insert Official Invoice
            $stmtInv = $pdo->prepare("
                INSERT INTO invoices (
                    store_id, custom_id, user_id, customer_id, subtotal, discount, tax_amount, tax_rate, total,
                    status, transaction_type, notes, customer_name_snapshot, customer_phone_snapshot, customer_address_snapshot,
                    payment_method, sale_channel, paid_amount, balance_due, is_credit_settled, credit_due_date, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 'Sale', ?, ?, ?, ?, ?, 'retail', ?, ?, ?, ?, NOW())
            ");

            $stmtInv->execute([
                $storeId,
                $customId,
                Auth::id(),
                $quotation['customer_id'] ?: null,
                $quotation['subtotal'],
                $quotation['discount'],
                $quotation['tax_amount'],
                $quotation['tax_rate'],
                $quotation['total'],
                "Converted from Quotation " . $quotation['quotation_number'] . ($quotation['notes'] ? " | " . $quotation['notes'] : ''),
                $quotation['customer_name'],
                $quotation['customer_phone'] ?: null,
                $quotation['customer_address'] ?: null,
                $paymentMethod,
                $paidAmount,
                $balanceDue,
                $isCreditSettled,
                $creditDueDate
            ]);
            $invoiceId = (int) $pdo->lastInsertId();

            // 3. Insert Invoice Items & Deduct Stock
            $stmtInvItem = $pdo->prepare("
                INSERT INTO invoice_items 
                    (store_id, invoice_id, product_id, product_name, model_number, quantity, price, discount, total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtStock = $pdo->prepare("
                UPDATE products 
                SET stock = stock - ? 
                WHERE id = ? AND store_id = ?
            ");

            foreach ($items as $it) {
                $stmtInvItem->execute([
                    $storeId,
                    $invoiceId,
                    $it['product_id'] ?: null,
                    $it['product_name'],
                    $it['model_number'],
                    (int) ceil($it['quantity']),
                    $it['price'],
                    $it['discount'],
                    $it['total']
                ]);

                if (!empty($it['product_id'])) {
                    $stmtStock->execute([(int) ceil($it['quantity']), (int) $it['product_id'], $storeId]);
                }
            }

            // 4. Update Customer Spending
            if (!empty($quotation['customer_id'])) {
                $pdo->prepare("UPDATE customers SET total_spending = total_spending + ? WHERE id = ?")->execute([$quotation['total'], (int)$quotation['customer_id']]);
            }

            // 5. Update Quotation Status
            $pdo->prepare("
                UPDATE quotations 
                SET status = 'converted', converted_invoice_id = ?, updated_at = NOW() 
                WHERE id = ?
            ")->execute([$invoiceId, $id]);

            $pdo->commit();

            $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [
                $storeId,
                Auth::id(),
                'Quotation Converted',
                "Converted Quotation {$quotation['quotation_number']} into Invoice {$customId} | " . App::money($quotation['total'])
            ]);

            $this->flash('success', "Quotation successfully converted to Sale Invoice {$customId}! Stock deducted.");
            $this->redirect('sales/index');

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flash('error', 'Could not convert quotation: ' . $e->getMessage());
            $this->redirect('quotations/view/' . $id);
        }
    }

    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('quotations/index');
        }
        $this->verifyCsrfOrAbort();

        $id = (int) $id;
        $db = Database::getInstance();
        [$where, $params] = Tenant::storeFilter('q');

        $quotation = $db->fetch("SELECT * FROM quotations q WHERE q.id = ? AND $where LIMIT 1", array_merge([$id], $params));
        if (!$quotation) {
            $this->flash('error', 'Quotation not found.');
            $this->redirect('quotations/index');
        }

        $db->query("DELETE FROM quotations WHERE id = ?", [$id]);
        $this->flash('success', "Quotation {$quotation['quotation_number']} deleted.");
        $this->redirect('quotations/index');
    }
}
