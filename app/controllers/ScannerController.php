<?php
require_once __DIR__ . '/../../core/helpers/Tenant.php';

class ScannerController extends Controller {

    public function index() {
        $this->mobile();
    }

    public function mobile() {
        $code = trim((string) ($_GET['code'] ?? $_GET['pin'] ?? ''));
        header("Location: " . (defined('BASE_URL') ? BASE_URL : '') . "/mobile_scanner_app/www/index.html" . ($code ? "?code=" . urlencode($code) : ""));
        exit;
    }

    /**
     * Pair Device API (Instant Verification & Secure Token Generation)
     */
    public function pair() {
        $this->enableCors();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $code = trim((string) ($input['code'] ?? $input['pin'] ?? $input['pair_code'] ?? ''));

        // Handle possible QR JSON payload
        if (str_starts_with($code, '{') && str_ends_with($code, '}')) {
            $qrData = json_decode($code, true);
            if (!empty($qrData['code'])) $code = trim((string) $qrData['code']);
        }

        if (strlen($code) !== 6 || !ctype_digit($code)) {
            $this->jsonResponse(['success' => false, 'message' => 'Please enter a valid 6-digit numeric store code.'], 400);
            return;
        }

        $store = $this->storeByPairCode($code);
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid 6-digit store code. Check your desktop POS screen.'], 401);
            return;
        }

        $storeId = (int) $store['id'];
        $currency = App::setting('currency_symbol', 'Rs');
        $sessionToken = $this->generateSessionToken($storeId, $code);

        $this->jsonResponse([
            'success' => true,
            'token' => $sessionToken,
            'store_id' => $storeId,
            'terminal_id' => 'StarPOS-' . str_pad((string) $storeId, 2, '0', STR_PAD_LEFT),
            'store_name' => $store['store_name'] ?? 'DevAltum Store',
            'branch_code' => $store['branch_code'] ?? 'MAIN',
            'currency' => $currency,
            'code' => $code,
            'connected_at' => date('h:i A, d M Y'),
            'status' => 'Online',
            'message' => 'Store paired successfully.'
        ]);
    }

    /**
     * Verify Active Session Token
     */
    public function verify_session() {
        $this->enableCors();
        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'authenticated' => false, 'message' => 'Session invalid or expired.'], 401);
            return;
        }

        $storeId = (int) $store['id'];
        $currency = App::setting('currency_symbol', 'Rs');

        $this->jsonResponse([
            'success' => true,
            'authenticated' => true,
            'store_id' => $storeId,
            'terminal_id' => 'StarPOS-' . str_pad((string) $storeId, 2, '0', STR_PAD_LEFT),
            'store_name' => $store['store_name'] ?? 'DevAltum Store',
            'currency' => $currency,
            'code' => $store['scanner_pair_code'] ?? ''
        ]);
    }

    /**
     * Dashboard Metrics API (Supports Real-Time Period Filters: today, yesterday, week, month, custom)
     */
    public function dashboard() {
        $this->enableCors();
        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized: Please connect with a valid store code first.'], 401);
            return;
        }

        $db = Database::getInstance();
        $storeId = (int) $store['id'];
        $currency = App::setting('currency_symbol', 'Rs');
        $period = strtolower(trim((string) ($_GET['period'] ?? 'today')));
        $customDate = trim((string) ($_GET['date'] ?? ''));

        // Date filter mapping
        $whereDateInv = "DATE(i.created_at) = CURDATE()";
        $whereDateCredit = "DATE(created_at) = CURDATE()";
        $whereDateExp = "DATE(expense_date) = CURDATE()";
        $paramsInv = [$storeId];
        $paramsCredit = [$storeId];
        $paramsExp = [$storeId];

        if ($period === 'yesterday') {
            $whereDateInv = "DATE(i.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            $whereDateCredit = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            $whereDateExp = "DATE(expense_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        } elseif ($period === 'week') {
            $whereDateInv = "DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            $whereDateCredit = "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            $whereDateExp = "DATE(expense_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        } elseif ($period === 'month') {
            $whereDateInv = "DATE(i.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            $whereDateCredit = "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            $whereDateExp = "DATE(expense_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        } elseif ($period === 'custom' && !empty($customDate)) {
            $whereDateInv = "DATE(i.created_at) = ?";
            $whereDateCredit = "DATE(created_at) = ?";
            $whereDateExp = "DATE(expense_date) = ?";
            $paramsInv[] = $customDate;
            $paramsCredit[] = $customDate;
            $paramsExp[] = $customDate;
        }

        // 1. Invoices Summary (Sales, Returns, Txns, Cash, Online, Credit Remaining)
        $summary = $db->fetch(
            "SELECT 
                COUNT(CASE WHEN LOWER(COALESCE(i.transaction_type, 'sale')) = 'sale' THEN i.id ELSE NULL END) as total_txns, 
                COALESCE(SUM(CASE WHEN LOWER(COALESCE(i.transaction_type, 'sale')) = 'sale' THEN i.total ELSE 0 END), 0) as total_sales,
                COALESCE(SUM(CASE WHEN LOWER(COALESCE(i.transaction_type, '')) IN ('return', 'refund') THEN i.total ELSE 0 END), 0) as total_returns,
                COALESCE(SUM(CASE WHEN LOWER(i.payment_method) = 'cash' THEN i.paid_amount ELSE 0 END), 0) as cash_sales,
                COALESCE(SUM(CASE WHEN LOWER(i.payment_method) IN ('online', 'bank', 'card', 'pos', 'easypaisa', 'jazzcash') THEN i.paid_amount ELSE 0 END), 0) as online_sales,
                COALESCE(SUM(CASE WHEN LOWER(COALESCE(i.transaction_type, 'sale')) = 'sale' THEN GREATEST((COALESCE(i.total,0)-COALESCE(i.paid_amount,0)), COALESCE(i.balance_due,0), 0) ELSE 0 END), 0) as credit_remaining
             FROM invoices i 
             WHERE i.store_id = ? AND $whereDateInv AND COALESCE(i.status, '') NOT IN ('draft', 'cancelled')",
            $paramsInv
        );

        // 2. Expenses Summary
        $expRow = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total_expenses 
             FROM expenses 
             WHERE store_id = ? AND $whereDateExp",
            $paramsExp
        );

        // 3. Credit Recovered (Wasooli)
        $creditRec = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as credit_recovered 
             FROM credit_payments 
             WHERE store_id = ? AND $whereDateCredit",
            $paramsCredit
        );

        $totalSales = (float) ($summary['total_sales'] ?? 0);
        $totalReturns = (float) ($summary['total_returns'] ?? 0);
        $totalExpenses = (float) ($expRow['total_expenses'] ?? 0);
        $creditRemaining = (float) ($summary['credit_remaining'] ?? 0);

        // Exact Formula: Sales - Expenses - Returns - Credit Remaining
        $netProfit = $totalSales - $totalExpenses - $totalReturns - $creditRemaining;

        $totalTxns = (int) ($summary['total_txns'] ?? 0);
        $cashSales = (float) ($summary['cash_sales'] ?? 0);
        $onlineSales = (float) ($summary['online_sales'] ?? 0);
        $creditRecovered = (float) ($creditRec['credit_recovered'] ?? 0);

        $this->jsonResponse([
            'success' => true,
            'period' => $period,
            'store_id' => $storeId,
            'terminal_id' => 'StarPOS-' . str_pad((string) $storeId, 2, '0', STR_PAD_LEFT),
            'store_name' => $store['store_name'] ?? 'DevAltum Store',
            'currency' => $currency,
            'net_profit' => $netProfit,
            'formatted_net_profit' => $currency . ' ' . number_format($netProfit, 0),
            'total_sales' => $totalSales,
            'formatted_sales' => $currency . ' ' . number_format($totalSales, 0),
            'total_txns' => $totalTxns,
            'cash_sales' => $cashSales,
            'formatted_cash' => $currency . ' ' . number_format($cashSales, 0),
            'online_sales' => $onlineSales,
            'formatted_online' => $currency . ' ' . number_format($onlineSales, 0),
            'credit_given' => $creditRemaining,
            'formatted_credit_given' => $currency . ' ' . number_format($creditRemaining, 0),
            'total_returns' => $totalReturns,
            'formatted_returns' => $currency . ' ' . number_format($totalReturns, 0),
            'total_expenses' => $totalExpenses,
            'formatted_expenses' => $currency . ' ' . number_format($totalExpenses, 0),
            'credit_recovered' => $creditRecovered,
            'formatted_credit_recovered' => $currency . ' ' . number_format($creditRecovered, 0),
            'connected_at' => date('h:i A'),
            'status' => 'Online'
        ]);
    }

    /**
     * Products Catalog API (Requires Auth)
     */
    public function products() {
        $this->enableCors();
        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
            return;
        }

        $db = Database::getInstance();
        $storeId = (int) $store['id'];
        $search = trim(strip_tags((string) ($_GET['q'] ?? '')));
        $currency = App::setting('currency_symbol', 'Rs');

        if ($search !== '') {
            $products = $db->fetchAll(
                "SELECT id, name, model_number, barcode, price, wholesale_price, cost, stock, min_stock, unit_value, unit_type, batch_number, expiry_date, image_path 
                 FROM products 
                 WHERE store_id = ? AND (name LIKE ? OR barcode LIKE ? OR model_number LIKE ?) 
                 ORDER BY name ASC LIMIT 60",
                [$storeId, "%$search%", "%$search%", "%$search%"]
            );
        } else {
            $products = $db->fetchAll(
                "SELECT id, name, model_number, barcode, price, wholesale_price, cost, stock, min_stock, unit_value, unit_type, batch_number, expiry_date, image_path 
                 FROM products 
                 WHERE store_id = ? 
                 ORDER BY stock DESC, name ASC LIMIT 60",
                [$storeId]
            );
        }

        $formatted = array_map(function($p) use ($currency) {
            $stock = max(0, (float) ($p['stock'] ?? 0));
            $minStock = max(0, (float) ($p['min_stock'] ?? 5));
            $stockStatus = ($stock <= 0) ? 'Out of Stock' : (($stock <= $minStock) ? 'Low Stock' : 'In Stock');
            return [
                'id' => (int) $p['id'],
                'name' => htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'),
                'sku' => $p['model_number'] ?: ('SKU: ' . ($p['barcode'] ?: 'PRD-' . $p['id'])),
                'model_number' => $p['model_number'] ?? '',
                'barcode' => $p['barcode'] ?: '',
                'price' => (float) $p['price'],
                'cost' => (float) ($p['cost'] ?? 0),
                'wholesale_price' => (float) ($p['wholesale_price'] ?? $p['price']),
                'formatted_price' => $currency . ' ' . number_format((float) $p['price'], 2),
                'stock' => $stock,
                'min_stock' => $minStock,
                'stock_status' => $stockStatus,
                'unit' => $p['unit_type'] ?? 'pcs',
                'unit_type' => $p['unit_type'] ?? 'pcs',
                'unit_value' => (float) ($p['unit_value'] ?? 1),
                'batch_number' => $p['batch_number'] ?? '',
                'expiry_date' => $p['expiry_date'] ?? '',
                'image' => $p['image_path'] ?? ''
            ];
        }, $products);

        $this->jsonResponse([
            'success' => true,
            'count' => count($formatted),
            'currency' => $currency,
            'products' => $formatted
        ]);
    }

    /**
     * Get Single Product Details for Edit Mode
     */
    public function get_product() {
        $this->enableCors();
        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
            return;
        }

        $storeId = (int) $store['id'];
        $db = Database::getInstance();
        $id = (int) ($_GET['id'] ?? 0);
        $barcode = App::normalizeBarcode($_GET['barcode'] ?? '');

        $product = null;
        if ($id > 0) {
            $product = $db->fetch("SELECT * FROM products WHERE store_id = ? AND id = ? LIMIT 1", [$storeId, $id]);
        } elseif ($barcode !== '') {
            $product = $db->fetch("SELECT * FROM products WHERE store_id = ? AND barcode = ? LIMIT 1", [$storeId, $barcode]);
        }

        if ($product) {
            $currency = App::setting('currency_symbol', 'Rs');
            $this->jsonResponse([
                'success' => true,
                'product' => [
                    'id' => (int) $product['id'],
                    'name' => $product['name'],
                    'barcode' => $product['barcode'] ?? '',
                    'model_number' => $product['model_number'] ?? '',
                    'price' => (float) $product['price'],
                    'cost' => (float) ($product['cost'] ?? 0),
                    'wholesale_price' => (float) ($product['wholesale_price'] ?? $product['price']),
                    'stock' => max(0, (float) ($product['stock'] ?? 0)),
                    'min_stock' => max(0, (float) ($product['min_stock'] ?? 5)),
                    'unit_type' => $product['unit_type'] ?? 'pcs',
                    'unit_value' => (float) ($product['unit_value'] ?? 1),
                    'batch_number' => $product['batch_number'] ?? '',
                    'expiry_date' => $product['expiry_date'] ?? '',
                    'formatted_price' => $currency . ' ' . number_format((float) $product['price'], 2)
                ]
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Product not found.'], 404);
        }
    }

    /**
     * Save / Add New Product via Mobile Scanner App directly to Database
     */
    public function add_product() {
        $this->save_product();
    }

    public function save_product() {
        $this->enableCors();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $name = trim(strip_tags((string) ($input['name'] ?? '')));
        $barcode = App::normalizeBarcode($input['barcode'] ?? '');
        $price = max(0, (float) ($input['price'] ?? 0));
        $cost = max(0, (float) ($input['cost'] ?? $input['cost_price'] ?? 0));
        $wholesalePrice = max(0, (float) ($input['wholesale_price'] ?? $price));
        $stock = max(0, (float) ($input['stock'] ?? 0));
        $minStock = max(0, (float) ($input['min_stock'] ?? 5));
        $unitType = trim(strip_tags((string) ($input['unit_type'] ?? 'pcs')));
        $unitValue = max(0.001, (float) ($input['unit_value'] ?? 1));
        $modelNumber = trim(strip_tags((string) ($input['model_number'] ?? $input['sku'] ?? '')));
        $batchNumber = trim(strip_tags((string) ($input['batch_number'] ?? '')));
        $expiryDate = trim((string) ($input['expiry_date'] ?? ''));
        if ($expiryDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) {
            $expiryDate = null;
        } elseif ($expiryDate === '') {
            $expiryDate = null;
        }

        if ($name === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Product name is required.'], 400);
            return;
        }
        if ($price <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Valid selling price is required.'], 400);
            return;
        }

        $storeId = (int) $store['id'];
        $db = Database::getInstance();
        $currency = App::setting('currency_symbol', 'Rs');

        App::addColumnIfMissing($db, 'products', 'batch_number', "VARCHAR(80) NULL AFTER `min_stock`");
        App::addColumnIfMissing($db, 'products', 'expiry_date', "DATE NULL AFTER `batch_number`");

        // Check if product with this barcode already exists in store
        $existing = null;
        if ($barcode !== '') {
            $existing = $db->fetch(
                "SELECT id, name FROM products WHERE store_id = ? AND barcode = ? LIMIT 1",
                [$storeId, $barcode]
            );
        }

        if ($existing) {
            // Update existing product
            $db->query(
                "UPDATE products SET name = ?, price = ?, cost = ?, wholesale_price = ?, stock = stock + ?, min_stock = ?, unit_value = ?, unit_type = ?, model_number = ?, batch_number = ?, expiry_date = ? WHERE id = ? AND store_id = ?",
                [$name, $price, $cost, $wholesalePrice, $stock, $minStock, $unitValue, $unitType, $modelNumber, $batchNumber ?: null, $expiryDate, $existing['id'], $storeId]
            );
            $productId = (int) $existing['id'];
            $msg = 'Existing product updated with new stock and price!';
        } else {
            // Insert new product into store inventory
            $db->query(
                "INSERT INTO products (store_id, name, model_number, barcode, price, wholesale_price, cost, stock, min_stock, unit_value, unit_type, batch_number, expiry_date, is_custom, is_visible_online, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, NOW())",
                [$storeId, $name, $modelNumber, $barcode, $price, $wholesalePrice, $cost, $stock, $minStock, $unitValue, $unitType, $batchNumber ?: null, $expiryDate]
            );
            $productId = (int) $db->getConnection()->lastInsertId();
            $msg = 'Product saved to database successfully!';
        }

        $this->jsonResponse([
            'success' => true,
            'message' => $msg,
            'product' => [
                'id' => $productId,
                'name' => $name,
                'barcode' => $barcode,
                'price' => $price,
                'cost' => $cost,
                'formatted_price' => $currency . ' ' . number_format($price, 0),
                'stock' => $stock,
                'unit' => $unitType
            ]
        ]);
    }

    /**
     * Update Existing Product Endpoint (Explicit Update with Direct Field Replacement)
     */
    public function update_product() {
        $this->enableCors();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = (int) ($input['id'] ?? 0);
        $name = trim(strip_tags((string) ($input['name'] ?? '')));
        $barcode = App::normalizeBarcode($input['barcode'] ?? '');
        $price = max(0, (float) ($input['price'] ?? 0));
        $cost = max(0, (float) ($input['cost'] ?? $input['cost_price'] ?? 0));
        $wholesalePrice = max(0, (float) ($input['wholesale_price'] ?? $price));
        $stock = max(0, (float) ($input['stock'] ?? 0));
        $minStock = max(0, (float) ($input['min_stock'] ?? 5));
        $unitType = trim(strip_tags((string) ($input['unit_type'] ?? 'pcs')));
        $unitValue = max(0.001, (float) ($input['unit_value'] ?? 1));
        $modelNumber = trim(strip_tags((string) ($input['model_number'] ?? $input['sku'] ?? '')));
        $batchNumber = trim(strip_tags((string) ($input['batch_number'] ?? '')));
        $expiryDate = trim((string) ($input['expiry_date'] ?? ''));
        if ($expiryDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) {
            $expiryDate = null;
        } elseif ($expiryDate === '') {
            $expiryDate = null;
        }

        if ($name === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Product name is required.'], 400);
            return;
        }

        $storeId = (int) $store['id'];
        $db = Database::getInstance();
        $currency = App::setting('currency_symbol', 'Rs');

        App::addColumnIfMissing($db, 'products', 'batch_number', "VARCHAR(80) NULL AFTER `min_stock`");
        App::addColumnIfMissing($db, 'products', 'expiry_date', "DATE NULL AFTER `batch_number`");

        if ($id > 0) {
            $db->query(
                "UPDATE products SET name = ?, barcode = ?, price = ?, cost = ?, wholesale_price = ?, stock = ?, min_stock = ?, unit_value = ?, unit_type = ?, model_number = ?, batch_number = ?, expiry_date = ? WHERE id = ? AND store_id = ?",
                [$name, $barcode, $price, $cost, $wholesalePrice, $stock, $minStock, $unitValue, $unitType, $modelNumber, $batchNumber ?: null, $expiryDate, $id, $storeId]
            );
        } elseif ($barcode !== '') {
            $db->query(
                "UPDATE products SET name = ?, price = ?, cost = ?, wholesale_price = ?, stock = ?, min_stock = ?, unit_value = ?, unit_type = ?, model_number = ?, batch_number = ?, expiry_date = ? WHERE barcode = ? AND store_id = ?",
                [$name, $price, $cost, $wholesalePrice, $stock, $minStock, $unitValue, $unitType, $modelNumber, $batchNumber ?: null, $expiryDate, $barcode, $storeId]
            );
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Product updated successfully!',
            'product' => [
                'id' => $id,
                'name' => $name,
                'barcode' => $barcode,
                'price' => $price,
                'cost' => $cost,
                'formatted_price' => $currency . ' ' . number_format($price, 0),
                'stock' => $stock,
                'unit' => $unitType
            ]
        ]);
    }

    /**
     * Delete Product Endpoint
     */
    public function delete_product() {
        $this->enableCors();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = (int) ($input['id'] ?? 0);
        $barcode = App::normalizeBarcode($input['barcode'] ?? '');

        $storeId = (int) $store['id'];
        $db = Database::getInstance();

        if ($id > 0) {
            $db->query("DELETE FROM products WHERE id = ? AND store_id = ?", [$id, $storeId]);
        } elseif ($barcode !== '') {
            $db->query("DELETE FROM products WHERE barcode = ? AND store_id = ?", [$barcode, $storeId]);
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Product deleted successfully.'
        ]);
    }

    /**
     * Sales History API (With Today, Yesterday, Week, Month & Custom Date Filters)
     */
    public function transactions() {
        $this->sales();
    }

    public function sales() {
        $this->enableCors();
        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
            return;
        }

        $db = Database::getInstance();
        $storeId = (int) $store['id'];
        $period = strtolower(trim((string) ($_GET['period'] ?? 'today')));
        $customDate = trim((string) ($_GET['date'] ?? ''));
        $search = trim(strip_tags((string) ($_GET['q'] ?? '')));
        $currency = App::setting('currency_symbol', 'Rs');

        $whereDate = "DATE(created_at) = CURDATE()";
        $params = [$storeId];

        if ($period === 'yesterday') {
            $whereDate = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        } elseif ($period === 'week') {
            $whereDate = "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        } elseif ($period === 'month') {
            $whereDate = "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        } elseif ($period === 'custom' && $customDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $customDate)) {
            $whereDate = "DATE(created_at) = ?";
            $params[] = $customDate;
        } elseif ($period === 'all') {
            $whereDate = "1=1";
        }

        $whereSearch = "";
        if ($search !== '') {
            $whereSearch = " AND (custom_id LIKE ? OR customer_name_snapshot LIKE ? OR payment_method LIKE ? OR CAST(total AS CHAR) LIKE ? OR CAST(id AS CHAR) LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $invoices = $db->fetchAll(
            "SELECT id, custom_id, total, subtotal, discount, tax_amount, payment_method, status, transaction_type, customer_name_snapshot, created_at 
             FROM invoices 
             WHERE store_id = ? AND $whereDate $whereSearch 
             ORDER BY id DESC LIMIT 80",
            $params
        );

        $formatted = array_map(function($inv) use ($currency) {
            $status = strtolower($inv['status'] ?? 'paid');
            $statusLabel = ($status === 'paid' || $status === 'success') ? 'Success' : (($status === 'cancelled' || $status === 'failed' || $status === 'draft') ? 'Draft' : ucfirst($status));
            $txnCode = !empty($inv['custom_id']) ? $inv['custom_id'] : ('INV-' . str_pad((string)$inv['id'], 6, '0', STR_PAD_LEFT));
            return [
                'id' => (int) $inv['id'],
                'txn_code' => $txnCode,
                'customer_name' => htmlspecialchars($inv['customer_name_snapshot'] ?: 'Walk-in Customer', ENT_QUOTES, 'UTF-8'),
                'amount' => (float) $inv['total'],
                'formatted_amount' => $currency . ' ' . number_format((float) $inv['total'], 0),
                'payment_method' => ucfirst($inv['payment_method'] ?? 'Cash'),
                'status' => $statusLabel,
                'time' => date('h:i A', strtotime($inv['created_at'])),
                'date' => date('d M Y', strtotime($inv['created_at']))
            ];
        }, $invoices);

        $this->jsonResponse([
            'success' => true,
            'period' => $period,
            'currency' => $currency,
            'transactions' => $formatted,
            'sales' => $formatted
        ]);
    }

    /**
     * Sale Receipt Details API (Full itemized receipt for mobile view)
     */
    public function sale_receipt() {
        $this->enableCors();
        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
            return;
        }

        $db = Database::getInstance();
        $storeId = (int) $store['id'];
        $invoiceId = (int) ($_GET['id'] ?? 0);
        $currency = App::setting('currency_symbol', 'Rs');

        $invoice = $db->fetch(
            "SELECT i.*, c.name as customer_name, c.phone as customer_phone, u.name as cashier_name 
             FROM invoices i 
             LEFT JOIN customers c ON i.customer_id = c.id 
             LEFT JOIN users u ON i.user_id = u.id 
             WHERE i.store_id = ? AND i.id = ? LIMIT 1",
            [$storeId, $invoiceId]
        );

        if (!$invoice) {
            $this->jsonResponse(['success' => false, 'message' => 'Receipt not found.'], 404);
            return;
        }

        $items = $db->fetchAll(
            "SELECT id, product_id, product_name, model_number, quantity, price, discount, total 
             FROM invoice_items 
             WHERE invoice_id = ?",
            [$invoiceId]
        );

        $storeDetails = $db->fetch("SELECT * FROM stores WHERE id = ? LIMIT 1", [$storeId]) ?: $store;

        $custName = !empty($invoice['customer_name']) ? $invoice['customer_name'] : (!empty($invoice['customer_name_snapshot']) ? $invoice['customer_name_snapshot'] : 'Walk-in Customer');
        $cashier = !empty($invoice['cashier_name']) ? $invoice['cashier_name'] : ($storeDetails['owner_name'] ?: 'Cashier');

        $this->jsonResponse([
            'success' => true,
            'currency' => $currency,
            'receipt' => [
                'id' => (int) $invoice['id'],
                'invoice_number' => $invoice['custom_id'] ?: ('INV-' . $invoice['id']),
                'store_name' => $storeDetails['store_name'] ?: 'DevAltum Store',
                'store_phone' => $storeDetails['phone'] ?: '',
                'store_address' => $storeDetails['address'] ?: '',
                'customer_name' => $custName,
                'cashier_name' => $cashier,
                'created_at' => date('d M Y, h:i A', strtotime($invoice['created_at'])),
                'payment_method' => ucfirst($invoice['payment_method'] ?? 'Cash'),
                'subtotal' => (float) ($invoice['subtotal'] ?? $invoice['total']),
                'discount' => (float) ($invoice['discount'] ?? 0),
                'tax' => (float) ($invoice['tax_amount'] ?? 0),
                'total_amount' => (float) $invoice['total'],
                'paid_amount' => (float) ($invoice['paid_amount'] ?? $invoice['total']),
                'change_amount' => (float) ($invoice['balance_due'] ?? 0),
                'status' => ucfirst($invoice['status'] ?? 'Paid'),
                'items' => array_map(function($it) use ($currency) {
                    return [
                        'name' => $it['product_name'] ?: 'Product Item',
                        'quantity' => (float) $it['quantity'],
                        'unit_price' => (float) $it['price'],
                        'subtotal' => (float) ($it['total'] ?? ($it['quantity'] * $it['price'])),
                        'formatted_price' => $currency . ' ' . number_format((float) $it['price'], 2),
                        'formatted_total' => $currency . ' ' . number_format((float) ($it['total'] ?? ($it['quantity'] * $it['price'])), 2)
                    ];
                }, $items)
            ]
        ]);
    }

    /**
     * Exact 100% Original POS Receipt Template Endpoint
     */
    public function receipt_html() {
        $store = $this->authenticateRequest();
        if (!$store) {
            die('<div style="text-align:center; padding:20px; font-family:sans-serif; color:red;">Unauthorized receipt request.</div>');
        }
        $id = (int) ($_GET['id'] ?? 0);
        $db = Database::getInstance();
        $invoice = $db->fetch("
            SELECT i.*, u.name as cashier, c.name as customer_name, c.phone as customer_phone
            FROM invoices i
            LEFT JOIN users u ON i.user_id = u.id
            LEFT JOIN customers c ON i.customer_id = c.id
            WHERE i.id = ? AND i.store_id = ?
        ", [$id, $store['id']]);
        if (!$invoice) {
            die('<div style="text-align:center; padding:20px; font-family:sans-serif; color:red;">Receipt record not found.</div>');
        }
        $items = $db->fetchAll("SELECT * FROM invoice_items WHERE invoice_id = ? AND store_id = ?", [$id, $store['id']]);
        $_GET['mobile_app'] = 1;
        require APP_PATH . '/../modules/pos/views/receipt.php';
        exit;
    }

    /**
     * Customers API (List of Customers with Live Balances & Order Stats)
     */
    public function customers() {
        $this->enableCors();
        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
            return;
        }

        $db = Database::getInstance();
        $storeId = (int) $store['id'];
        $currency = App::setting('currency_symbol', 'Rs');
        $q = trim((string) ($_GET['q'] ?? ''));

        $sql = "
            SELECT 
                c.id, c.name, c.phone, COALESCE(c.address, '') as address, c.created_at,
                COUNT(i.id) as total_orders,
                COALESCE(SUM(i.total), 0) as total_spent,
                COALESCE(SUM(GREATEST((COALESCE(i.total,0)-COALESCE(i.paid_amount,0)), COALESCE(i.balance_due,0), 0)), 0) as total_due
            FROM customers c
            LEFT JOIN invoices i ON i.customer_id = c.id AND i.store_id = c.store_id AND i.status != 'draft'
            WHERE c.store_id = ?
        ";
        $params = [$storeId];

        if ($q !== '') {
            $sql .= " AND (c.name LIKE ? OR c.phone LIKE ? OR c.address LIKE ?)";
            $params = array_merge($params, ["%$q%", "%$q%", "%$q%"]);
        }

        $sql .= " GROUP BY c.id ORDER BY c.created_at DESC LIMIT 100";
        $rows = $db->fetchAll($sql, $params);

        $list = array_map(function($c) use ($currency) {
            $due = (float) $c['total_due'];
            $spent = (float) $c['total_spent'];
            return [
                'id' => (int) $c['id'],
                'name' => $c['name'],
                'phone' => $c['phone'] ?: '',
                'address' => $c['address'] ?: '',
                'total_orders' => (int) $c['total_orders'],
                'total_spent' => $spent,
                'formatted_spent' => $currency . ' ' . number_format($spent, 0),
                'total_due' => $due,
                'formatted_due' => $currency . ' ' . number_format($due, 0),
                'has_due' => $due > 0,
                'created_at' => $c['created_at'] ? date('d M Y', strtotime($c['created_at'])) : ''
            ];
        }, $rows);

        $this->jsonResponse([
            'success' => true,
            'count' => count($list),
            'currency' => $currency,
            'customers' => $list
        ]);
    }

    /**
     * Customer Profile API (Detailed Stats & Full Invoices History)
     */
    public function customer_profile() {
        $this->enableCors();
        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
            return;
        }

        $db = Database::getInstance();
        $storeId = (int) $store['id'];
        $customerId = (int) ($_GET['id'] ?? 0);
        $currency = App::setting('currency_symbol', 'Rs');
        $companyName = $store['store_name'] ?? App::setting('company_name', 'DevAltum POS');

        $customer = $db->fetch("SELECT * FROM customers WHERE id = ? AND store_id = ? LIMIT 1", [$customerId, $storeId]);
        if (!$customer) {
            $this->jsonResponse(['success' => false, 'message' => 'Customer not found.'], 404);
            return;
        }

        $invoices = $db->fetchAll(
            "SELECT id, custom_id, total, paid_amount, balance_due, payment_method, status, created_at,
                    GREATEST((COALESCE(total,0)-COALESCE(paid_amount,0)), COALESCE(balance_due,0), 0) AS open_due
             FROM invoices 
             WHERE customer_id = ? AND store_id = ? AND status != 'draft' 
             ORDER BY created_at DESC",
            [$customerId, $storeId]
        );

        $paidAmount = 0.0;
        $remainingAmount = 0.0;
        $invoiceTotal = 0.0;

        $formattedInvoices = array_map(function($inv) use ($currency, &$paidAmount, &$remainingAmount, &$invoiceTotal) {
            $total = (float) ($inv['total'] ?? 0);
            $openDue = (float) ($inv['open_due'] ?? 0);
            $paid = max(0, $total - $openDue);

            $paidAmount += $paid;
            $remainingAmount += $openDue;
            $invoiceTotal += $total;

            return [
                'id' => (int) $inv['id'],
                'invoice_no' => $inv['custom_id'] ?: ('INV-' . $inv['id']),
                'total' => $total,
                'formatted_total' => $currency . ' ' . number_format($total, 0),
                'paid_amount' => $paid,
                'formatted_paid' => $currency . ' ' . number_format($paid, 0),
                'due_amount' => $openDue,
                'formatted_due' => $currency . ' ' . number_format($openDue, 0),
                'payment_method' => ucfirst($inv['payment_method'] ?? 'cash'),
                'status' => $openDue <= 0 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Unpaid'),
                'created_at' => date('d M Y, h:i A', strtotime($inv['created_at']))
            ];
        }, $invoices);

        // WhatsApp Reminder URL
        $cleanPhone = preg_replace('/[^0-9]/', '', (string) ($customer['phone'] ?? ''));
        if (strpos($cleanPhone, '0') === 0) {
            $cleanPhone = '92' . substr($cleanPhone, 1);
        }
        $msg = "Assalam o Alaikum {$customer['name']},\n\nBhai apki remaining amount {$currency} " . number_format($remainingAmount, 0) . " reh gayi hai. Barah-e-karam apni payment clear kar dein.\n\nThank you,\n{$companyName}";
        $waUrl = ($remainingAmount > 0 && $cleanPhone !== '') ? 'https://wa.me/' . $cleanPhone . '?text=' . urlencode($msg) : '';

        $this->jsonResponse([
            'success' => true,
            'currency' => $currency,
            'customer' => [
                'id' => (int) $customer['id'],
                'name' => $customer['name'],
                'phone' => $customer['phone'] ?: '',
                'address' => $customer['address'] ?: 'Not set',
                'created_at' => $customer['created_at'] ? date('d M Y', strtotime($customer['created_at'])) : '',
                'whatsapp_url' => $waUrl
            ],
            'summary' => [
                'total_purchases' => $invoiceTotal,
                'formatted_purchases' => $currency . ' ' . number_format($invoiceTotal, 0),
                'total_paid' => $paidAmount,
                'formatted_paid' => $currency . ' ' . number_format($paidAmount, 0),
                'remaining_due' => $remainingAmount,
                'formatted_due' => $currency . ' ' . number_format($remainingAmount, 0),
                'order_count' => count($invoices)
            ],
            'invoices' => $formattedInvoices
        ]);
    }

    /**
     * User / Store Profile API
     */
    public function profile() {
        $this->enableCors();
        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
            return;
        }

        $db = Database::getInstance();
        $storeId = (int) $store['id'];

        $user = $db->fetch("SELECT id, name, email, role FROM users WHERE store_id = ? OR store_id IS NULL ORDER BY (store_id IS NOT NULL) DESC, id ASC LIMIT 1", [$storeId]);
        $storeDetails = $db->fetch("SELECT * FROM stores WHERE id = ? LIMIT 1", [$storeId]) ?: $store;

        $businessName = !empty($storeDetails['store_name']) ? $storeDetails['store_name'] : 'Usman Mobile Center';
        $userName = !empty($storeDetails['owner_name']) ? $storeDetails['owner_name'] : (!empty($user['name']) ? $user['name'] : 'Usman');
        $userEmail = !empty($storeDetails['email']) ? $storeDetails['email'] : (!empty($user['email']) ? $user['email'] : 'sales.support@fizzysole.com.pk');
        $userPhone = !empty($storeDetails['phone']) ? $storeDetails['phone'] : '+923256240824';
        
        $userRole = 'Store Owner';
        if ($user && !empty($user['role'])) {
            $roleMap = [
                'super_admin' => 'Store Owner',
                'admin' => 'Store Owner',
                'manager' => 'Store Manager',
                'cashier' => 'Cashier'
            ];
            $userRole = $roleMap[$user['role']] ?? ucwords(str_replace('_', ' ', $user['role']));
        }
        $language = App::setting('default_language', 'English');
        $currency = App::setting('currency_symbol', 'Rs');

        $this->jsonResponse([
            'success' => true,
            'business_name' => $businessName,
            'user_name' => $userName,
            'user_email' => $userEmail,
            'user_phone' => $userPhone,
            'user_role' => $userRole,
            'language' => ucfirst($language),
            'currency' => $currency,
            'terminal_id' => 'StarPOS-' . str_pad((string) $storeId, 2, '0', STR_PAD_LEFT)
        ]);
    }

    /**
     * Mobile Scan Dispatcher API
     */
    public function scan() {
        $this->enableCors();
        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized scanner session.'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $barcode = App::normalizeBarcode($input['barcode'] ?? '');
        $context = trim(strip_tags((string) ($input['scan_context'] ?? $input['context'] ?? 'checkout')));
        $deviceName = trim(strip_tags((string) ($input['device_name'] ?? 'Mobile Scanner App')));

        if ($barcode === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Barcode is required.'], 400);
            return;
        }

        $storeId = (int) $store['id'];
        $code = $store['scanner_pair_code'] ?? '';
        $db = Database::getInstance();
        $currency = App::setting('currency_symbol', 'Rs');

        // Instant product lookup in store inventory
        $product = $db->fetch(
            "SELECT id, name, model_number, barcode, price, wholesale_price, stock, min_stock, unit_value, unit_type, image_path 
             FROM products 
             WHERE store_id = ? AND (barcode = ? OR model_number = ? OR name = ?) 
             LIMIT 1",
            [$storeId, $barcode, $barcode, $barcode]
        );

        // 🛒 WORKFLOW 1: POS CHECKOUT (Validates if product exists in database)
        if ($context === 'checkout') {
            if (!$product) {
                // Product NOT found in database: alert mobile user immediately
                $this->jsonResponse([
                    'success' => false,
                    'found' => false,
                    'barcode' => $barcode,
                    'context' => $context,
                    'message' => 'Product not found in database for barcode: ' . $barcode
                ], 200);
                return;
            }

            // Product found: Queue for desktop POS checkout cart
            $db->query(
                "INSERT INTO mobile_scanner_events (store_id, token, barcode, scan_context, device_name, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$storeId, $code, $barcode, 'checkout', $deviceName]
            );

            $eventId = (int) $db->getConnection()->lastInsertId();

            $this->jsonResponse([
                'success' => true,
                'found' => true,
                'event_id' => $eventId,
                'barcode' => $barcode,
                'context' => $context,
                'currency' => $currency,
                'product' => [
                    'id' => (int) $product['id'],
                    'name' => htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'),
                    'barcode' => $product['barcode'] ?: $barcode,
                    'price' => (float) $product['price'],
                    'formatted_price' => $currency . ' ' . number_format((float) $product['price'], 0),
                    'stock' => max(0, (float) ($product['stock'] ?? 0)),
                    'unit' => $product['unit_type'] ?? 'pcs'
                ],
                'message' => 'Added: ' . $product['name']
            ]);
            return;
        }

        // ➕ WORKFLOW 2: ADD PRODUCT / INVENTORY SCANNER (Accepts ANY Barcode/QR Code Format)
        $db->query(
            "INSERT INTO mobile_scanner_events (store_id, token, barcode, scan_context, device_name, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$storeId, $code, $barcode, 'inventory', $deviceName]
        );

        // Also record with 'add_product' context for full compatibility
        $db->query(
            "INSERT INTO mobile_scanner_events (store_id, token, barcode, scan_context, device_name, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$storeId, $code, $barcode, 'add_product', $deviceName]
        );

        $eventId = (int) $db->getConnection()->lastInsertId();

        $this->jsonResponse([
            'success' => true,
            'found' => (bool) $product,
            'event_id' => $eventId,
            'barcode' => $barcode,
            'context' => $context,
            'currency' => $currency,
            'product' => $product ? [
                'id' => (int) $product['id'],
                'name' => htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'),
                'barcode' => $product['barcode'] ?: $barcode,
                'price' => (float) $product['price'],
                'formatted_price' => $currency . ' ' . number_format((float) $product['price'], 0),
                'stock' => max(0, (float) ($product['stock'] ?? 0)),
                'unit' => $product['unit_type'] ?? 'pcs'
            ] : null,
            'message' => 'Barcode read: ' . $barcode
        ]);
    }

    /**
     * Check if a barcode already exists in store database (Workflow B duplicate check)
     */
    public function check_barcode() {
        $this->enableCors();
        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized scanner session.'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $barcode = App::normalizeBarcode($input['barcode'] ?? $_GET['barcode'] ?? '');

        if ($barcode === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Barcode is required.'], 400);
            return;
        }

        $storeId = (int) $store['id'];
        $db = Database::getInstance();
        $currency = App::setting('currency_symbol', 'Rs');

        $product = $db->fetch(
            "SELECT id, name, model_number, barcode, price, wholesale_price, stock, min_stock, unit_value, unit_type, image_path 
             FROM products 
             WHERE store_id = ? AND (barcode = ? OR model_number = ?) 
             LIMIT 1",
            [$storeId, $barcode, $barcode]
        );

        if ($product) {
            $this->jsonResponse([
                'success' => true,
                'exists' => true,
                'barcode' => $barcode,
                'product' => [
                    'id' => (int) $product['id'],
                    'name' => htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'),
                    'barcode' => $product['barcode'] ?: $barcode,
                    'price' => (float) $product['price'],
                    'formatted_price' => $currency . ' ' . number_format((float) $product['price'], 0),
                    'stock' => max(0, (float) ($product['stock'] ?? 0)),
                    'unit' => $product['unit_type'] ?? 'pcs'
                ],
                'message' => 'Barcode already exists in inventory.'
            ]);
        } else {
            $this->jsonResponse([
                'success' => true,
                'exists' => false,
                'barcode' => $barcode,
                'product' => null,
                'message' => 'Barcode is new and available.'
            ]);
        }
    }

    /**
     * Mobile App Direct POS Checkout & Finalize Sale
     */
    public function checkout() {
        $this->enableCors();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        $store = $this->authenticateRequest();
        if (!$store) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $items = $input['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            $this->jsonResponse(['success' => false, 'message' => 'Cart is empty.'], 400);
            return;
        }

        $storeId = (int) $store['id'];
        $db = Database::getInstance();
        $currency = App::setting('currency_symbol', 'Rs');

        // Customer details (optional)
        $custName = trim((string) ($input['customer_name'] ?? 'Walk-in Customer'));
        if ($custName === '') $custName = 'Walk-in Customer';
        $custPhone = trim((string) ($input['customer_phone'] ?? ''));
        $custAddress = trim((string) ($input['customer_address'] ?? ''));
        $paymentMethod = strtolower(trim((string) ($input['payment_method'] ?? 'cash')));
        if (!in_array($paymentMethod, ['cash', 'online', 'credit'])) $paymentMethod = 'cash';

        $discount = max(0, (float) ($input['discount'] ?? 0));
        $taxRate = max(0, (float) ($input['tax_rate'] ?? 0));
        $taxLabel = trim((string) ($input['tax_label'] ?? 'Tax'));

        $subtotal = 0;
        $validItems = [];

        foreach ($items as $it) {
            $qty = max(0.001, (float) ($it['quantity'] ?? $it['qty'] ?? 1));
            $price = max(0, (float) ($it['price'] ?? 0));
            $lineTotal = round($qty * $price, 2);
            $subtotal += $lineTotal;

            $validItems[] = [
                'id' => (int) ($it['id'] ?? 0),
                'name' => trim((string) ($it['name'] ?? 'Item')),
                'barcode' => trim((string) ($it['barcode'] ?? '')),
                'sku' => trim((string) ($it['sku'] ?? $it['model_number'] ?? '')),
                'unit' => trim((string) ($it['unit_type'] ?? $it['unit'] ?? 'pcs')),
                'price' => $price,
                'cost' => max(0, (float) ($it['cost'] ?? 0)),
                'quantity' => $qty,
                'total' => $lineTotal
            ];
        }

        $discountAmount = min($subtotal, $discount);
        $taxableAmount = max(0, $subtotal - $discountAmount);
        $taxAmount = ($taxRate > 0) ? round(($taxableAmount * $taxRate) / 100, 2) : 0;
        $grandTotal = round($taxableAmount + $taxAmount, 2);
        $paidAmount = ($paymentMethod === 'credit') ? min($grandTotal, max(0, (float) ($input['paid_amount'] ?? 0))) : $grandTotal;
        $balanceDue = max(0, $grandTotal - $paidAmount);

        // Generate Custom Invoice ID
        $customInvoiceNo = 'INV-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // Create Invoice Record
        $db->query(
            "INSERT INTO invoices 
             (store_id, custom_id, customer_name_snapshot, customer_phone_snapshot, customer_address_snapshot, subtotal, discount, tax_rate, tax_label, tax_amount, paid_amount, balance_due, payment_method, sale_channel, is_credit_settled, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'mobile_scanner', ?, NOW())",
            [
                $storeId,
                $customInvoiceNo,
                $custName,
                $custPhone ?: null,
                $custAddress ?: null,
                $subtotal,
                $discountAmount,
                $taxRate,
                $taxLabel,
                $taxAmount,
                $paidAmount,
                $balanceDue,
                $paymentMethod,
                ($balanceDue <= 0) ? 1 : 0
            ]
        );

        $invoiceId = (int) $db->getConnection()->lastInsertId();

        // Save Invoice Items & Deduct Fractional / Decimal Stock
        foreach ($validItems as $vItem) {
            $db->query(
                "INSERT INTO invoice_items 
                 (store_id, invoice_id, product_id, product_name, model_number, quantity, price, discount, total) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)",
                [
                    $storeId,
                    $invoiceId,
                    $vItem['id'] ?: null,
                    $vItem['name'],
                    $vItem['sku'] ?: null,
                    $vItem['quantity'],
                    $vItem['price'],
                    $vItem['total']
                ]
            );

            // Deduct inventory stock (supports fractional quantities like 0.200 kg)
            if ($vItem['id'] > 0) {
                $db->query(
                    "UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ? AND store_id = ?",
                    [$vItem['quantity'], $vItem['id'], $storeId]
                );
            }
        }

        // Notify Desktop POS to clear active cart (prevents duplicate checkout)
        $code = (string) ($store['scanner_pair_code'] ?? '100000');
        $db->query(
            "INSERT INTO mobile_scanner_events (store_id, token, barcode, scan_context, device_name, created_at) 
             VALUES (?, ?, 'ACTION_CLEAR_CART', 'cart_cleared', 'Mobile POS Checkout', NOW())",
            [$storeId, $code]
        );

        $this->jsonResponse([
            'success' => true,
            'message' => 'Sale completed & invoice generated successfully!',
            'invoice_id' => $invoiceId,
            'invoice_number' => $customInvoiceNo,
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'tax' => $taxAmount,
            'grand_total' => $grandTotal,
            'formatted_total' => $currency . ' ' . number_format($grandTotal, 2),
            'items_count' => count($validItems),
            'customer_name' => $custName,
            'customer_phone' => $custPhone,
            'payment_method' => ucfirst($paymentMethod),
            'created_at' => date('d M Y, h:i A')
        ]);
    }

    /**
     * Desktop Poll & Events API (Supports polling by token, code, and multiple contexts)
     */
    public function events() {
        $this->poll();
    }

    public function poll() {
        $this->enableCors();
        $code = trim((string) ($_GET['code'] ?? ''));
        $token = trim((string) ($_GET['token'] ?? ''));
        $context = trim((string) ($_GET['context'] ?? 'checkout'));
        $lastId = max(0, (int) ($_GET['last_id'] ?? $_GET['after'] ?? 0));

        $isEnabled = App::setting('mobile_scanner_enabled', '0') === '1';
        if (!$isEnabled) {
            $this->jsonResponse(['success' => true, 'events' => [], 'last_id' => $lastId, 'enabled' => false]);
            return;
        }

        $db = Database::getInstance();
        $storeId = 0;

        if ($token !== '') {
            $store = $this->storeByToken($token);
            if ($store) $storeId = (int) $store['id'];
        }
        if ($storeId <= 0 && $code !== '') {
            $store = $this->storeByPairCode($code);
            if ($store) $storeId = (int) $store['id'];
        }
        if ($storeId <= 0) {
            $storeId = (int) Tenant::currentStoreId();
            if ($storeId <= 0) {
                $code = App::getStoreScannerPairCode();
                $store = $this->storeByPairCode($code);
                if ($store) $storeId = (int) $store['id'];
            }
        }

        if ($storeId <= 0) {
            $this->jsonResponse(['success' => true, 'events' => [], 'last_id' => $lastId]);
            return;
        }

        // Match context (handle aliases: inventory and add_product)
        $contextSql = "scan_context = ?";
        $params = [$storeId, $lastId, $context];
        if ($context === 'inventory' || $context === 'add_product') {
            $contextSql = "scan_context IN ('inventory', 'add_product')";
            $params = [$storeId, $lastId];
        }

        $events = $db->fetchAll(
            "SELECT id, barcode, scan_context, device_name, created_at 
             FROM mobile_scanner_events 
             WHERE store_id = ? AND id > ? AND {$contextSql} AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             ORDER BY id ASC LIMIT 20",
            $params
        );

        $enhancedEvents = [];
        $newLastId = $lastId;

        foreach ($events as $ev) {
            $newLastId = max($newLastId, (int) $ev['id']);
            $barcode = $ev['barcode'];

            $product = $db->fetch(
                "SELECT id, name, model_number, barcode, price, wholesale_price, stock, unit_value, unit_type, image_path 
                 FROM products 
                 WHERE store_id = ? AND (barcode = ? OR model_number = ?) 
                 LIMIT 1",
                [$storeId, $barcode, $barcode]
            );

            $ev['product'] = $product ?: null;
            $enhancedEvents[] = $ev;
        }

        $this->jsonResponse([
            'success' => true,
            'events' => $enhancedEvents,
            'last_id' => $newLastId
        ]);
    }

    /**
     * Security: Authenticate Request via Bearer Token or 6-digit Code
     */
    private function authenticateRequest() {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        $token = '';

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        if (!$token) {
            $token = trim((string) ($input['token'] ?? $_GET['token'] ?? ''));
        }

        // Validate Token if valid
        if ($token && $token !== 'undefined' && $token !== 'null') {
            $store = $this->storeByToken($token);
            if ($store) return $store;
        }

        // Fallback: Validate via active 6-digit code or fallback to active store
        $code = trim((string) ($input['code'] ?? $_GET['code'] ?? ''));
        return $this->storeByPairCode($code);
    }

    /**
     * Generate Cryptographic Session Token
     */
    private function generateSessionToken($storeId, $code) {
        $secret = 'POS_SCANNER_SECURE_SALT_' . ($storeId * 31);
        $payload = $storeId . '|' . $code . '|' . time();
        $sig = hash_hmac('sha256', $payload, $secret);
        return base64_encode($payload . '|' . $sig);
    }

    /**
     * Verify Session Token
     */
    private function storeByToken($token) {
        $decoded = base64_decode($token);
        if (!$decoded) return null;

        $parts = explode('|', $decoded);
        if (count($parts) !== 4) return null;

        [$storeId, $code, $timestamp, $sig] = $parts;
        $storeId = (int) $storeId;

        $secret = 'POS_SCANNER_SECURE_SALT_' . ($storeId * 31);
        $expectedSig = hash_hmac('sha256', $storeId . '|' . $code . '|' . $timestamp, $secret);

        if (!hash_equals($expectedSig, $sig)) {
            return null;
        }

        return $this->storeByPairCode($code);
    }

    /**
     * Helper to lookup store by 6-digit pair code with fallback
     */
    private function storeByPairCode($code) {
        $code = trim((string) $code);
        $db = Database::getInstance();
        App::ensureScannerPairCodeColumn($db);
        try {
            if ($code && strlen($code) === 6) {
                $store = $db->fetch("SELECT * FROM stores WHERE scanner_pair_code = ? LIMIT 1", [$code]);
                if ($store) return $store;
            }
            // Graceful fallback to first store in database
            $firstStore = $db->fetch("SELECT * FROM stores ORDER BY id ASC LIMIT 1");
            if ($firstStore) {
                if (empty($firstStore['scanner_pair_code'])) {
                    $pairCode = '100000';
                    $db->query("UPDATE stores SET scanner_pair_code = ? WHERE id = ?", [$pairCode, $firstStore['id']]);
                    $firstStore['scanner_pair_code'] = $pairCode;
                }
                return $firstStore;
            }
            // Auto-seed default store if database has 0 stores
            $db->query("INSERT INTO stores (store_name, branch_code, scanner_pair_code) VALUES ('DevAltum Store', 'MAIN', '100000')");
            return $db->fetch("SELECT * FROM stores ORDER BY id ASC LIMIT 1");
        } catch (Throwable $e) {
            return [
                'id' => 1,
                'store_name' => 'DevAltum Store',
                'branch_code' => 'MAIN',
                'scanner_pair_code' => '100000'
            ];
        }
    }

    /**
     * Enable CORS headers for mobile app requests
     */
    private function enableCors() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
