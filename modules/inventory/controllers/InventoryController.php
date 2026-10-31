<?php

class InventoryController extends Controller {

    public function __construct() {
        AuthMiddleware::handlePermission('inventory');
    }

    public function index() {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $q = trim($_GET['q'] ?? '');
        $filter = trim($_GET['filter'] ?? '');

        $sql = "SELECT * FROM products WHERE $where AND (parent_product_id IS NULL OR parent_product_id = 0)";
        $params = $whereParams;

        if ($q !== '') {
            $sql .= " AND (name LIKE ? OR model_number LIKE ? OR barcode LIKE ?)";
            $params[] = "%$q%";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }

        if ($filter === 'low') {
            $sql .= " AND stock <= min_stock";
        }

        $sql .= " ORDER BY stock DESC, is_custom ASC, name ASC";
        $products = $db->fetchAll($sql, $params);
        [$purchaseWhere, $purchaseParams] = Tenant::storeFilter();
        $purchases = $db->fetchAll("
            SELECT *
            FROM inventory_purchases
            WHERE $purchaseWhere
            ORDER BY purchase_date DESC, id DESC
            LIMIT 250
        ", $purchaseParams);

        $suppliersList = $db->fetchAll("
            SELECT id, name, company_name 
            FROM suppliers 
            WHERE $purchaseWhere 
            ORDER BY name ASC
        ", $purchaseParams);

        $allProductsList = $db->fetchAll("
            SELECT id, name, model_number, barcode, stock, cost, price, unit_type 
            FROM products 
            WHERE $where AND (parent_product_id IS NULL OR parent_product_id = 0) 
            ORDER BY name ASC
        ", $whereParams);

        $this->view('../../modules/inventory/views/index', [
            'pageTitle' => 'Inventory',
            'activeMenu' => 'inventory',
            'products' => $products,
            'allProductsList' => $allProductsList,
            'purchases' => $purchases,
            'suppliersList' => $suppliersList,
            'query' => $q,
            'filter' => $filter
        ]);
    }



    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('inventory/index');
        }
        $this->verifyCsrfOrAbort();

        $name = trim($_POST['name'] ?? '');
        $model = trim($_POST['model_number'] ?? '');
        $barcode = App::normalizeBarcode($_POST['barcode'] ?? '');
        if ($barcode === '') {
            $barcode = BarcodeHelper::generateEan13();
        }
        $unitValue = max(0.001, (float) ($_POST['unit_value'] ?? 1));

        $unitType = App::normalizeProductUnit($_POST['unit_type'] ?? 'pcs');
        $price = (float) ($_POST['price'] ?? 0);
        $wholesaleRaw = trim((string) ($_POST['wholesale_price'] ?? ''));
        $wholesalePrice = ($wholesaleRaw !== '') ? (float) $wholesaleRaw : $price;
        $cost = (float) ($_POST['cost'] ?? 0);
        $stock = max(0, (float) ($_POST['stock'] ?? 0));
        $minStock = max(0, (float) ($_POST['min_stock'] ?? 5));
        $isVisibleOnline = isset($_POST['is_visible_online']) ? 1 : 0;

        if ($name === '') {
            $this->flash('error', 'Product name is required.');
            $this->redirect('inventory/index');
        }

        $batchNumber = trim($_POST['batch_number'] ?? '');
        $expiryDate = trim($_POST['expiry_date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) {
            $expiryDate = null;
        }

        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();

        App::addColumnIfMissing($db, 'products', 'batch_number', "VARCHAR(80) NULL AFTER `min_stock`");
        App::addColumnIfMissing($db, 'products', 'expiry_date', "DATE NULL AFTER `batch_number`");
        App::addColumnIfMissing($db, 'products', 'is_visible_online', "TINYINT(1) NOT NULL DEFAULT 1");

        $imagePath = $this->uploadProductImage('product_image');
        $db->query(
            "INSERT INTO products (store_id, name, model_number, barcode, unit_value, unit_type, image_path, price, wholesale_price, cost, stock, min_stock, expiry_date, batch_number, is_visible_online) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$storeId, $name, $model ?: null, $barcode ?: null, $unitValue, $unitType, $imagePath ?: null, $price, $wholesalePrice, $cost, $stock, $minStock, $expiryDate, $batchNumber ?: null, $isVisibleOnline]
        );
        $productId = (int) $db->lastInsertId();

        // If batch or expiry was provided and initial stock > 0, record in product_batches
        if (!empty($expiryDate) || !empty($batchNumber)) {
            $db->query("
                INSERT INTO product_batches 
                    (store_id, product_id, batch_number, cost_price, sale_price, initial_quantity, current_stock, expiry_date, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [
                $storeId,
                $productId,
                $batchNumber ?: ('BN-' . date('ymd') . '-' . $productId),
                $cost,
                $price,
                $stock,
                $stock,
                $expiryDate ?: date('Y-m-d', strtotime('+1 year'))
            ]);
        }

        $this->syncProductScanCodes($db, $productId, $barcode, $_POST);
        $this->syncProductVariants($db, $productId, [
            'name' => $name,
            'barcode' => $barcode,
            'unit_type' => $unitType,
            'image_path' => $imagePath,
            'cost' => $cost,
            'min_stock' => $minStock,
        ], $_POST);
        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [$storeId, Auth::id(), 'Inventory', "Added new product: $name"]);
        $this->flash('success', 'Product added successfully.');
        $this->redirect('inventory/index?tab=list');
    }

    public function edit($id) {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $product = $db->fetch("SELECT * FROM products WHERE id = ? AND $where", array_merge([$id], $whereParams));
        if (!$product) {
            die("Product not found");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrfOrAbort();
            $name = trim($_POST['name'] ?? '');
            $model = trim($_POST['model_number'] ?? '');
            $barcode = App::normalizeBarcode($_POST['barcode'] ?? '');
            $unitValue = max(0.001, (float) ($_POST['unit_value'] ?? 1));
            $unitType = App::normalizeProductUnit($_POST['unit_type'] ?? 'pcs');
            $price = (float) ($_POST['price'] ?? 0);
            $wholesaleRaw = trim((string) ($_POST['wholesale_price'] ?? ''));
            $wholesalePrice = ($wholesaleRaw !== '') ? (float) $wholesaleRaw : $price;
            $cost = (float) ($_POST['cost'] ?? 0);
            $stock = max(0, (float) ($_POST['stock'] ?? 0));
            $minStock = max(0, (float) ($_POST['min_stock'] ?? 5));
            $isVisibleOnline = isset($_POST['is_visible_online']) ? 1 : 0;
            $batchNumber = trim($_POST['batch_number'] ?? '');
            $expiryDate = trim($_POST['expiry_date'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) {
                $expiryDate = null;
            }

            $removeImage = ($_POST['remove_product_image'] ?? '') === '1';
            $imagePath = $this->uploadProductImage('product_image');
            if ($imagePath === '') {
                $imagePath = $removeImage ? '' : (string) ($product['image_path'] ?? '');
            }

            App::addColumnIfMissing($db, 'products', 'batch_number', "VARCHAR(80) NULL AFTER `min_stock`");
            App::addColumnIfMissing($db, 'products', 'expiry_date', "DATE NULL AFTER `batch_number`");
            App::addColumnIfMissing($db, 'products', 'is_visible_online', "TINYINT(1) NOT NULL DEFAULT 1");

            $db->query(
                "UPDATE products SET name=?, model_number=?, barcode=?, unit_value=?, unit_type=?, image_path=?, price=?, wholesale_price=?, cost=?, stock=?, min_stock=?, expiry_date=?, batch_number=?, is_visible_online=? WHERE id=? AND $where",
                array_merge([$name, $model ?: null, $barcode ?: null, $unitValue, $unitType, $imagePath ?: null, $price, $wholesalePrice, $cost, $stock, $minStock, $expiryDate, $batchNumber ?: null, $isVisibleOnline, $id], $whereParams)
            );
            $this->syncProductScanCodes($db, (int) $id, $barcode, $_POST);
            $this->syncProductVariants($db, (int) $id, [
                'name' => $name,
                'barcode' => $barcode ?: null,
                'unit_type' => $unitType,
                'image_path' => $imagePath,
                'cost' => $cost,
                'min_stock' => $minStock,
            ], $_POST);
            $db->query("UPDATE invoice_items SET product_name = ?, model_number = ? WHERE product_id = ? AND $where", array_merge([$name, $model ?: null, $id], $whereParams));
            $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), Auth::id(), 'Inventory', "Updated product: $name"]);
            $this->flash('success', 'Product updated successfully.');
            $this->redirect('inventory/edit/' . $id);
        }

        $latestPurchase = $db->fetch("
            SELECT * FROM inventory_purchases 
            WHERE (product_id = ? OR item_name = ?) AND $where 
            ORDER BY created_at DESC LIMIT 1
        ", array_merge([$id, $product['name']], $whereParams));

        $this->view('../../modules/inventory/views/edit', [
            'pageTitle' => 'Edit Product',
            'activeMenu' => 'inventory',
            'product' => $product,
            'latestPurchase' => $latestPurchase,
            'productCodes' => $this->productScanCodes($db, (int) $id),
            'productVariants' => $this->productVariants($db, (int) $id)
        ]);
    }

    public function viewProfile($id) {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        [$whereI, $whereIParams] = Tenant::storeFilter('i');
        $product = $db->fetch("SELECT * FROM products WHERE id = ? AND $where", array_merge([$id], $whereParams));
        if (!$product) {
            die("Product not found");
        }

        if (empty($product['barcode'])) {
            $autoBarcode = BarcodeHelper::generateEan13($product['store_id'] ?? 0);
            $db->query("UPDATE products SET barcode = ? WHERE id = ? AND $where", array_merge([$autoBarcode, $id], $whereParams));
            $product['barcode'] = $autoBarcode;
        }

        $sales = $db->fetchAll("
            SELECT i.id, i.custom_id, i.transaction_type, i.total, i.created_at, ii.quantity
            FROM invoice_items ii
            JOIN invoices i ON i.id = ii.invoice_id
            WHERE ii.product_id = ? AND $whereI
            ORDER BY i.created_at DESC
            LIMIT 10
        ", array_merge([$id], $whereIParams));

        // Fetch comprehensive purchase history (supporting both multi-item bills and legacy single-item records)
        $purchases = $db->fetchAll("
            SELECT 
                p.id as purchase_id,
                p.purchase_date,
                p.supplier_name,
                p.created_at,
                pi.quantity,
                pi.unit_cost,
                pi.old_cost,
                pi.subtotal as bill_total,
                pi.unit_type
            FROM inventory_purchase_items pi
            JOIN inventory_purchases p ON p.id = pi.purchase_id
            WHERE (pi.product_id = ? OR pi.item_name = ?) AND pi.store_id = ?
            ORDER BY p.purchase_date DESC, p.id DESC
            LIMIT 25
        ", array_merge([(int) $id, $product['name']], $whereParams));

        if (empty($purchases)) {
            $purchases = $db->fetchAll("
                SELECT 
                    p.id as purchase_id, 
                    p.purchase_date, 
                    p.supplier_name, 
                    p.created_at, 
                    p.quantity, 
                    p.unit_cost, 
                    p.old_cost, 
                    p.bill_total, 
                    p.unit_type
                FROM inventory_purchases p
                WHERE (p.product_id = ? OR p.item_name = ?) AND $where
                ORDER BY p.purchase_date DESC, p.id DESC
                LIMIT 25
            ", array_merge([(int) $id, $product['name']], $whereParams));
        }

        $batches = $db->fetchAll("
            SELECT *
            FROM product_batches
            WHERE product_id = ? AND $where
            ORDER BY expiry_date ASC, id DESC
            LIMIT 15
        ", array_merge([$id], $whereParams));

        $this->view('../../modules/inventory/views/view', [
            'pageTitle' => $product['name'],
            'activeMenu' => 'inventory',
            'product' => $product,
            'productCodes' => $this->productScanCodes($db, (int) $id),
            'sales' => $sales,
            'purchases' => $purchases,
            'batches' => $batches
        ]);
    }

    public function barcodePrint($id = 0) {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();

        $selectedProduct = null;
        $id = (int) $id;
        if ($id > 0) {
            $selectedProduct = $db->fetch("SELECT * FROM products WHERE id = ? AND $where LIMIT 1", array_merge([$id], $whereParams));
        }

        if (!$selectedProduct) {
            $selectedProduct = $db->fetch("SELECT * FROM products WHERE $where AND (parent_product_id IS NULL OR parent_product_id = 0) ORDER BY name ASC LIMIT 1", $whereParams);
        }

        if (!$selectedProduct) {
            $this->flash('error', 'No products available in inventory to print.');
            $this->redirect('inventory/index');
            return;
        }

        if (empty($selectedProduct['barcode'])) {
            $autoBarcode = BarcodeHelper::generateEan13($selectedProduct['store_id'] ?? 0);
            $db->query("UPDATE products SET barcode = ? WHERE id = ? AND $where", array_merge([$autoBarcode, $selectedProduct['id']], $whereParams));
            $selectedProduct['barcode'] = $autoBarcode;
        }

        $this->view('../../modules/inventory/views/barcode_print', [
            'pageTitle' => 'Print Barcode Labels - ' . $selectedProduct['name'],
            'activeMenu' => 'inventory',
            'product' => $selectedProduct
        ]);
    }

    public function exportBarcodeDocx() {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();

        $productId = (int) ($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        $product = null;
        if ($productId > 0) {
            $product = $db->fetch("SELECT * FROM products WHERE id = ? AND $where LIMIT 1", array_merge([$productId], $whereParams));
        }

        $activeStore = Tenant::currentStore();
        $defaultStoreName = $activeStore['name'] ?? $activeStore['store_name'] ?? App::setting('company_name', 'DevAltum Store');

        $storeName = trim((string) ($_POST['store_name'] ?? $_GET['store_name'] ?? $defaultStoreName));
        $productName = trim((string) ($_POST['product_name'] ?? $_GET['product_name'] ?? ($product['name'] ?? 'Product')));
        $barcode = trim((string) ($_POST['barcode'] ?? $_GET['barcode'] ?? ($product['barcode'] ?? '123456789012')));
        $barcodeFormat = strtolower(trim((string) ($_POST['barcode_format'] ?? $_GET['barcode_format'] ?? 'code128')));
        $price = (float) ($_POST['price'] ?? $_GET['price'] ?? ($product['price'] ?? 0));
        $quantity = max(1, min(1000, (int) ($_POST['quantity'] ?? $_GET['quantity'] ?? 30)));
        $columns = max(1, min(6, (int) ($_POST['columns'] ?? $_GET['columns'] ?? 3)));
        $currency = App::setting('currency_symbol', 'Rs');

        require_once ROOT_PATH . '/core/helpers/BarcodeDocxHelper.php';
        $docxBinary = BarcodeDocxHelper::createDocx([
            'store_name' => $storeName,
            'product_name' => $productName,
            'barcode' => $barcode,
            'barcode_format' => $barcodeFormat,
            'price' => $price,
            'currency' => $currency,
            'quantity' => $quantity,
            'columns' => $columns
        ]);

        $safeFileName = preg_replace('/[^A-Za-z0-9_-]/', '_', $productName);
        if (empty($safeFileName)) $safeFileName = 'Barcodes';
        $filename = "{$safeFileName}_Barcodes_{$quantity}pcs.docx";

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($docxBinary));
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        echo $docxBinary;
        exit;
    }


    public function generateBarcode() {
        $barcode = BarcodeHelper::generateEan13();
        $this->jsonResponse([
            'success' => true,
            'barcode' => $barcode
        ]);
    }

    public function assignBarcode($id) {
        $id = (int) $id;
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();

        $product = $db->fetch("SELECT id, name, barcode FROM products WHERE id = ? AND $where LIMIT 1", array_merge([$id], $whereParams));
        if (!$product) {
            $this->jsonResponse(['success' => false, 'message' => 'Product not found.'], 404);
            return;
        }

        $newBarcode = BarcodeHelper::generateEan13();
        $db->query("UPDATE products SET barcode = ? WHERE id = ? AND $where", array_merge([$newBarcode, $id], $whereParams));

        $this->jsonResponse([
            'success' => true,
            'barcode' => $newBarcode,
            'message' => 'Real standard barcode generated and saved to product successfully.'
        ]);
    }

    public function checkBarcode() {

        $barcode = App::normalizeBarcode($_GET['barcode'] ?? '');
        $excludeId = (int) ($_GET['exclude_id'] ?? 0);
        
        if ($barcode === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Empty barcode'], 400);
        }

        $existing = BarcodeHelper::checkBarcodeExists($barcode, $excludeId);
        $this->jsonResponse([
            'success' => true,
            'exists' => $existing !== null,
            'product' => $existing
        ]);
    }


    public function delete($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $product = $db->fetch("SELECT name FROM products WHERE id = ? AND $where", array_merge([$id], $whereParams));
        if ($product) {
            $db->query("UPDATE invoice_items SET product_id = NULL WHERE product_id = ? AND $where", array_merge([$id], $whereParams));
            $db->query("DELETE FROM products WHERE id = ? AND $where", array_merge([$id], $whereParams));
            $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), Auth::id(), 'Inventory', "Deleted product: " . $product['name']]);
            $this->flash('success', 'Product deleted successfully.');
        }
        $this->redirect('inventory/index');
    }

    public function quickStock($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $type = $_POST['adjustment_type'] ?? 'add';
        $qty = (int)($_POST['adjustment_qty'] ?? 0);
        if ($qty <= 0) {
            $this->flash('error', 'Quantity must be greater than zero.');
            $this->redirect('inventory/index');
            return;
        }

        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $product = $db->fetch("SELECT name, stock FROM products WHERE id = ? AND $where", array_merge([$id], $whereParams));
        if (!$product) {
            $this->flash('error', 'Product not found.');
            $this->redirect('inventory/index');
            return;
        }

        if ($type === 'deduct') {
            if ($product['stock'] < $qty) {
                $this->flash('error', 'Not enough stock to deduct.');
                $this->redirect('inventory/index');
                return;
            }
            $db->query("UPDATE products SET stock = stock - ? WHERE id = ? AND $where", array_merge([$qty, $id], $whereParams));
            $actionStr = "Deducted $qty units from";
        } else {
            $db->query("UPDATE products SET stock = stock + ? WHERE id = ? AND $where", array_merge([$qty, $id], $whereParams));
            $actionStr = "Added $qty units to";
        }

        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), Auth::id(), 'Inventory', "$actionStr " . $product['name']]);
        $this->flash('success', 'Stock updated successfully.');
        $this->redirect('inventory/index');
    }

    public function sampleTemplate() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="inventory_import_template.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'name',
            'barcode',
            'model_number',
            'unit_value',
            'unit_type',
            'price',
            'cost',
            'wholesale_price',
            'stock',
            'min_stock',
            'batch_number',
            'expiry_date'
        ]);

        fputcsv($output, ['Panadol Extra 500mg', '', 'MED-PAN-01', '1', 'pack', '450.00', '320.00', '400.00', '100', '10', 'BATCH-2026-A', '2027-12-31']);
        fputcsv($output, ['Nestle Pure Life 1.5L', '8964000102030', 'BEV-002', '1.5', 'litre', '120.00', '85.00', '105.00', '50', '8', '', '']);
        fputcsv($output, ['Basmati Super Rice 5Kg', '', 'GRN-005', '5', 'kg', '1850.00', '1500.00', '1700.00', '30', '5', '', '']);

        fclose($output);
        exit;
    }

    public function export() {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $products = $db->fetchAll("
            SELECT name, model_number, barcode, unit_value, unit_type, price, wholesale_price, cost, stock, min_stock 
            FROM products 
            WHERE $where AND (parent_product_id IS NULL OR parent_product_id = 0)
            ORDER BY name ASC
        ", $whereParams);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="inventory_export_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['Name', 'Model_Number', 'Barcode', 'Unit_Value', 'Unit_Type', 'Price', 'Wholesale_Price', 'Cost', 'Stock', 'Min_Stock']);
        foreach ($products as $row) {
            fputcsv($output, [
                $row['name'],
                $row['model_number'] ?? '',
                $row['barcode'] ?? '',
                $row['unit_value'] ?? '1',
                $row['unit_type'] ?? 'pcs',
                $row['price'],
                $row['wholesale_price'] ?? $row['price'],
                $row['cost'],
                $row['stock'],
                $row['min_stock']
            ]);
        }
        fclose($output);
        exit;
    }

    public function import() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file']['tmp_name'])) {
            $this->flash('error', 'Please choose a valid CSV file to upload.');
            $this->redirect('inventory/index?tab=import');
        }
        $this->verifyCsrfOrAbort();

        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '256M');

        $updateExisting = !empty($_POST['update_existing']);
        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        [$where, $params] = Tenant::storeFilter();

        // Ensure schema columns exist on products table
        App::addColumnIfMissing($db, 'products', 'batch_number', "VARCHAR(80) NULL AFTER `min_stock`");
        App::addColumnIfMissing($db, 'products', 'expiry_date', "DATE NULL AFTER `batch_number`");
        App::addColumnIfMissing($db, 'products', 'is_visible_online', "TINYINT(1) NOT NULL DEFAULT 1");
        App::addColumnIfMissing($db, 'products', 'wholesale_price', "DECIMAL(10,2) NULL AFTER `price`");
        App::addColumnIfMissing($db, 'products', 'unit_value', "DECIMAL(10,3) NULL AFTER `barcode`");
        App::addColumnIfMissing($db, 'products', 'unit_type', "VARCHAR(20) NULL AFTER `unit_value`");

        $handle = fopen($_FILES['file']['tmp_name'], "r");
        if (!$handle) {
            $this->flash('error', 'Unable to read the uploaded CSV file.');
            $this->redirect('inventory/index?tab=import');
        }

        // Read and sanitize header row
        $rawHeader = fgetcsv($handle);
        if (!is_array($rawHeader)) {
            fclose($handle);
            $this->flash('error', 'The CSV file appears to be empty.');
            $this->redirect('inventory/index?tab=import');
        }

        // Map column indices with extensive synonym detection
        $map = [];
        foreach ($rawHeader as $idx => $colName) {
            $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', (string)$colName)));
            $map[$clean] = $idx;
        }

        $findCol = function($synonyms, $default = null) use ($map) {
            foreach ($synonyms as $s) {
                if (isset($map[$s])) return $map[$s];
            }
            return $default;
        };

        $idxName      = $findCol(['name', 'product_name', 'title', 'product_title', 'item', 'item_name', 'product'], 0);
        $idxBarcode   = $findCol(['barcode', 'code', 'upc', 'ean', 'qr_code', 'bar_code'], null);
        $idxModel     = $findCol(['model_number', 'model', 'sku', 'item_code', 'article'], null);
        $idxUnitVal   = $findCol(['unit_value', 'unit_val', 'val', 'multiplier', 'size'], null);
        $idxUnitType  = $findCol(['unit_type', 'unit', 'uom', 'measurement'], null);
        $idxPrice     = $findCol(['price', 'sale_price', 'selling_price', 'retail_price', 'unit_price', 'rate'], null);
        $idxWholesale = $findCol(['wholesale_price', 'wholesale', 'bulk_price', 'ws_price'], null);
        $idxCost      = $findCol(['cost', 'cost_price', 'purchase_price', 'buy_price', 'buying_price'], null);
        $idxStock     = $findCol(['stock', 'quantity', 'qty', 'opening_stock', 'initial_stock', 'balance'], null);
        $idxMinStock  = $findCol(['min_stock', 'low_stock', 'alert_stock', 'threshold'], null);
        $idxBatch     = $findCol(['batch_number', 'batch', 'batch_no'], null);
        $idxExpiry    = $findCol(['expiry_date', 'expiry', 'exp_date', 'expiration_date'], null);

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $autoBarcodes = 0;

        while (($row = fgetcsv($handle, 4000, ",")) !== false) {
            $name = trim((string) ($row[$idxName] ?? ''));
            if ($name === '') {
                $skipped++;
                continue;
            }

            $model = $idxModel !== null ? trim((string) ($row[$idxModel] ?? '')) : '';
            $rawBarcode = $idxBarcode !== null ? ($row[$idxBarcode] ?? '') : '';
            $barcode = App::normalizeBarcode($rawBarcode);
            
            if ($barcode === '') {
                $barcode = BarcodeHelper::generateEan13($storeId);
                $autoBarcodes++;
            }

            $unitValue = $idxUnitVal !== null ? max(0.001, (float) ($row[$idxUnitVal] ?? 1)) : 1.0;
            $unitType = $idxUnitType !== null ? App::normalizeProductUnit($row[$idxUnitType] ?? 'pcs') : 'pcs';
            $price = $idxPrice !== null ? max(0, (float) ($row[$idxPrice] ?? 0)) : 0.0;
            $wholesale = $idxWholesale !== null ? (float) ($row[$idxWholesale] ?? $price) : $price;
            $cost = $idxCost !== null ? max(0, (float) ($row[$idxCost] ?? 0)) : 0.0;
            $stock = $idxStock !== null ? max(0, (float) ($row[$idxStock] ?? 0)) : 0.0;
            $minStock = $idxMinStock !== null ? max(0, (float) ($row[$idxMinStock] ?? 5)) : 5.0;
            $batchNumber = $idxBatch !== null ? trim((string) ($row[$idxBatch] ?? '')) : '';
            $expiryDate = $idxExpiry !== null ? trim((string) ($row[$idxExpiry] ?? '')) : '';
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) {
                $expiryDate = null;
            }

            // Check if product already exists by barcode or exact name
            $existing = $db->fetch("SELECT id, stock FROM products WHERE (barcode = ? OR name = ?) AND $where LIMIT 1", array_merge([$barcode, $name], $params));

            if ($existing) {
                if ($updateExisting) {
                    $db->query("
                        UPDATE products 
                        SET price = ?, wholesale_price = ?, cost = ?, stock = stock + ?, min_stock = ?, unit_value = ?, unit_type = ?, model_number = COALESCE(NULLIF(?, ''), model_number), batch_number = COALESCE(NULLIF(?, ''), batch_number), expiry_date = COALESCE(?, expiry_date)
                        WHERE id = ? AND $where
                    ", array_merge([$price, $wholesale, $cost, $stock, $minStock, $unitValue, $unitType, $model, $batchNumber, $expiryDate, (int)$existing['id']], $params));
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                $db->query("
                    INSERT INTO products (store_id, name, model_number, barcode, unit_value, unit_type, price, wholesale_price, cost, stock, min_stock, batch_number, expiry_date, is_custom, is_visible_online, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, NOW())
                ", [
                    $storeId,
                    $name,
                    $model ?: null,
                    $barcode,
                    $unitValue,
                    $unitType,
                    $price,
                    $wholesale,
                    $cost,
                    $stock,
                    $minStock,
                    $batchNumber ?: null,
                    $expiryDate
                ]);
                $imported++;
            }
        }

        fclose($handle);

        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [
            $storeId,
            Auth::id(),
            'Inventory Bulk Import',
            "Imported: $imported ($autoBarcodes auto barcodes), Updated: $updated, Skipped: $skipped"
        ]);

        $barcodeMsg = $autoBarcodes > 0 ? " ($autoBarcodes barcodes auto-generated)" : "";
        $this->flash('success', "Bulk Import Complete: $imported new products added{$barcodeMsg}, $updated updated, $skipped skipped.");
        $this->redirect('inventory/index?tab=list');
    }

    private function uploadProductImage($field) {
        if (empty($_FILES[$field]['tmp_name'])) {
            return '';
        }

        $targetDir = ROOT_PATH . '/public/uploads/products';
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp'];
        $allowedMimes = ['image/png', 'image/jpeg', 'image/webp'];
        $path = App::uploadFile($_FILES[$field], $targetDir, 'product', $allowedExtensions, $allowedMimes, 3 * 1024 * 1024);
        return $path ? App::publicPathToUrl($path) : '';
    }

    public function purchaseCreate() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $supplier = trim((string) ($_POST['supplier_name'] ?? ''));
        $item = trim((string) ($_POST['item_name'] ?? ''));
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(0, (float) ($_POST['quantity'] ?? 0));
        $unitType = App::normalizeProductUnit($_POST['unit_type'] ?? 'pcs');
        $purchaseDate = trim((string) ($_POST['purchase_date'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchaseDate)) {
            $purchaseDate = date('Y-m-d');
        }

        $billReference = trim((string) ($_POST['bill_reference'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $costMethod = trim((string) ($_POST['cost_method'] ?? 'weighted_average'));
        if (!in_array($costMethod, ['weighted_average', 'latest', 'latest_price'], true)) {
            $costMethod = 'weighted_average';
        }

        // Support both multi-item structure and legacy single-item fallback
        $rawItems = $_POST['items'] ?? [];
        if (empty($rawItems) && !empty($_POST['item_name'])) {
            $rawItems = [
                [
                    'product_id' => $_POST['product_id'] ?? null,
                    'item_name' => $_POST['item_name'] ?? '',
                    'unit_type' => $_POST['unit_type'] ?? 'pcs',
                    'quantity' => (float) ($_POST['quantity'] ?? 1),
                    'unit_cost' => (float) (($_POST['quantity'] ?? 0) > 0 ? (($_POST['bill_total'] ?? 0) / $_POST['quantity']) : 0),
                    'new_sale_price' => !empty($_POST['new_sale_price']) ? (float)$_POST['new_sale_price'] : null,
                    'batch_number' => $_POST['batch_number'] ?? null,
                    'expiry_date' => $_POST['expiry_date'] ?? null,
                ]
            ];
        }

        // Validate items
        $validItems = [];
        $calculatedBillTotal = 0;
        $totalQuantity = 0;

        foreach ($rawItems as $raw) {
            $name = trim((string) ($raw['item_name'] ?? ''));
            $qty = max(0.001, (float) ($raw['quantity'] ?? 0));
            $cost = max(0, (float) ($raw['unit_cost'] ?? 0));
            $salePrice = !empty($raw['new_sale_price']) ? max(0, (float) $raw['new_sale_price']) : null;
            $unit = App::normalizeProductUnit($raw['unit_type'] ?? 'pcs');
            $pId = !empty($raw['product_id']) ? (int) $raw['product_id'] : null;
            $bn = trim((string) ($raw['batch_number'] ?? ''));
            $exp = trim((string) ($raw['expiry_date'] ?? ''));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $exp)) {
                $exp = null;
            }

            if ($name === '' || $qty <= 0) {
                continue;
            }

            $lineSubtotal = round($qty * $cost, 2);
            $calculatedBillTotal += $lineSubtotal;
            $totalQuantity += $qty;

            $validItems[] = [
                'product_id' => $pId,
                'item_name' => $name,
                'unit_type' => $unit,
                'quantity' => $qty,
                'unit_cost' => $cost,
                'new_sale_price' => $salePrice,
                'subtotal' => $lineSubtotal,
                'batch_number' => $bn,
                'expiry_date' => $exp,
            ];
        }

        if ($supplier === '' || empty($validItems)) {
            $this->flash('error', 'Supplier name and at least one item with valid quantity are required.');
            $this->redirect('inventory/index?tab=purchase_add');
        }

        $db = Database::getInstance();
        $storeId = (int) Tenant::currentStoreId();

        // 1. Resolve or create supplier record
        $supplierId = (int) ($_POST['supplier_id'] ?? 0);
        if ($supplierId > 0) {
            $supRow = $db->fetch("SELECT id, name, current_balance FROM suppliers WHERE id = ? AND store_id = ? LIMIT 1", [$supplierId, $storeId]);
            if ($supRow) {
                $supplier = $supRow['name'];
            }
        } else {
            $supRow = $db->fetch("SELECT id, name, current_balance FROM suppliers WHERE name = ? AND store_id = ? LIMIT 1", [$supplier, $storeId]);
            if ($supRow) {
                $supplierId = (int) $supRow['id'];
            } else {
                $db->query("
                    INSERT INTO suppliers (store_id, name, opening_balance, current_balance, created_at)
                    VALUES (?, ?, 0.00, 0.00, NOW())
                ", [$storeId, $supplier]);
                $supplierId = (int) $db->lastInsertId();
            }
        }

        $billTotal = $calculatedBillTotal;
        $paidAmount = round(max(0, (float) ($_POST['paid_amount'] ?? 0)), 2);
        $paidAmount = min($paidAmount, $billTotal);
        $balance = round(max(0, $billTotal - $paidAmount), 2);
        $paymentMethod = trim((string) ($_POST['payment_method'] ?? 'cash'));
        if (!in_array($paymentMethod, ['cash', 'online', 'bank', 'cheque'], true)) {
            $paymentMethod = 'cash';
        }

        $itemsCount = count($validItems);
        $firstItem = $validItems[0];
        $summaryItemName = $itemsCount > 1 ? ($firstItem['item_name'] . ' (+ ' . ($itemsCount - 1) . ' items)') : $firstItem['item_name'];

        // 2. Insert master purchase record
        $db->query("
            INSERT INTO inventory_purchases
                (store_id, supplier_id, product_id, supplier_name, item_name, items_count, total_quantity, quantity, unit_cost, old_cost, cost_method, new_sale_price, unit_type, batch_number, expiry_date, purchase_date, bill_total, paid_amount, balance_amount, bill_reference, notes, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ", [
            $storeId,
            $supplierId > 0 ? $supplierId : null,
            $firstItem['product_id'] > 0 ? $firstItem['product_id'] : null,
            $supplier,
            $summaryItemName,
            $itemsCount,
            $totalQuantity,
            $firstItem['quantity'],
            $firstItem['unit_cost'],
            0.00,
            $costMethod,
            $firstItem['new_sale_price'],
            $firstItem['unit_type'],
            $firstItem['batch_number'] ?: null,
            $firstItem['expiry_date'],
            $purchaseDate,
            $billTotal,
            $paidAmount,
            $balance,
            $billReference ?: null,
            $notes ?: null,
            Auth::id()
        ]);
        $purchaseId = (int) $db->lastInsertId();

        // 3. Process each line item & update inventory stock + costing
        foreach ($validItems as $idx => $line) {
            $prodId = $line['product_id'];
            $itemName = $line['item_name'];
            $lineQty = $line['quantity'];
            $lineUnitCost = $line['unit_cost'];
            $lineUnit = $line['unit_type'];
            $lineSalePrice = $line['new_sale_price'];
            $lineBn = $line['batch_number'];
            $lineExp = $line['expiry_date'];
            $lineSubtotal = $line['subtotal'];

            $product = null;
            if ($prodId > 0) {
                $product = $db->fetch("SELECT * FROM products WHERE id = ? AND store_id = ? LIMIT 1", [$prodId, $storeId]);
            }
            if (!$product && $itemName !== '') {
                $product = $db->fetch("SELECT * FROM products WHERE name = ? AND store_id = ? LIMIT 1", [$itemName, $storeId]);
            }

            $oldCost = $product ? (float)$product['cost'] : $lineUnitCost;
            $oldStock = $product ? (float)$product['stock'] : 0;

            // Calculate cost
            if (in_array($costMethod, ['latest', 'latest_price'], true)) {
                $appliedCost = $lineUnitCost;
            } else {
                if ($product && $oldStock > 0) {
                    $appliedCost = round((($oldStock * $oldCost) + ($lineQty * $lineUnitCost)) / ($oldStock + $lineQty), 2);
                } else {
                    $appliedCost = $lineUnitCost;
                }
            }

            if ($product) {
                $prodId = (int) $product['id'];
                $itemName = $product['name'];
                $lineUnit = $product['unit_type'] ?: $lineUnit;

                $updateFields = [
                    "stock = stock + ?",
                    "cost = ?"
                ];
                $updateParams = [$lineQty, $appliedCost];

                if ($lineSalePrice !== null && $lineSalePrice > 0) {
                    $updateFields[] = "price = ?";
                    $updateParams[] = $lineSalePrice;
                }
                if (!empty($lineExp)) {
                    $updateFields[] = "expiry_date = ?";
                    $updateParams[] = $lineExp;
                }
                if (!empty($lineBn)) {
                    $updateFields[] = "batch_number = ?";
                    $updateParams[] = $lineBn;
                }
                $updateFields[] = "updated_at = NOW()";
                $updateParams[] = $prodId;

                $db->query("UPDATE products SET " . implode(", ", $updateFields) . " WHERE id = ?", $updateParams);
            } else {
                // Dynamically create product in database
                $autoPrice = ($lineSalePrice !== null && $lineSalePrice > 0) ? $lineSalePrice : round($lineUnitCost * 1.25, 2);
                $barcode = BarcodeHelper::generateEan13();
                $db->query("
                    INSERT INTO products 
                        (store_id, name, barcode, unit_type, price, wholesale_price, cost, stock, min_stock, expiry_date, batch_number, is_visible_online, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 5, ?, ?, 1, NOW())
                ", [
                    $storeId,
                    $itemName,
                    $barcode,
                    $lineUnit,
                    $autoPrice,
                    $autoPrice,
                    $appliedCost,
                    $lineQty,
                    $lineExp ?: null,
                    $lineBn ?: null
                ]);
                $prodId = (int) $db->lastInsertId();
            }

            // Insert child line item
            $db->query("
                INSERT INTO inventory_purchase_items
                    (store_id, purchase_id, product_id, item_name, unit_type, quantity, unit_cost, old_cost, cost_method, new_sale_price, subtotal, batch_number, expiry_date, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [
                $storeId,
                $purchaseId,
                $prodId,
                $itemName,
                $lineUnit,
                $lineQty,
                $lineUnitCost,
                $oldCost,
                $costMethod,
                $lineSalePrice,
                $lineSubtotal,
                $lineBn ?: null,
                $lineExp
            ]);

            // Record batch lot
            $effectiveSalePrice = ($lineSalePrice !== null && $lineSalePrice > 0) ? $lineSalePrice : ($product ? (float)$product['price'] : ($autoPrice ?? 0));
            $db->query("
                INSERT INTO product_batches 
                    (store_id, product_id, purchase_id, batch_number, cost_price, sale_price, initial_quantity, current_stock, expiry_date, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [
                $storeId,
                $prodId,
                $purchaseId,
                $lineBn ?: ('BN-' . date('ymd') . '-' . $purchaseId . '-' . ($idx + 1)),
                $lineUnitCost,
                $effectiveSalePrice,
                $lineQty,
                $lineQty,
                $lineExp ?: date('Y-m-d', strtotime('+1 year'))
            ]);
        }

        // 4. Initial Payment and Udhar Ledger update
        if ($paidAmount > 0) {
            $db->query("
                INSERT INTO inventory_purchase_payments
                    (store_id, purchase_id, payment_date, amount, payment_method, notes, created_by)
                VALUES (?, ?, ?, ?, ?, 'Initial payment at purchase entry', ?)
            ", [$storeId, $purchaseId, $purchaseDate, $paidAmount, $paymentMethod, Auth::id()]);

            if ($supplierId > 0) {
                $db->query("
                    INSERT INTO supplier_payments
                        (store_id, supplier_id, purchase_id, amount, payment_method, payment_date, reference_number, notes, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Initial payment at purchase entry', ?, NOW())
                ", [$storeId, $supplierId, $purchaseId, $paidAmount, $paymentMethod, $purchaseDate, $billReference ?: null, Auth::id()]);
            }
        }

        // 5. Update supplier balance (Khata / Ledger)
        if ($supplierId > 0) {
            $totalPurchases = (float) (($db->fetch("SELECT SUM(bill_total) as sum FROM inventory_purchases WHERE supplier_id = ?", [$supplierId])['sum'] ?? 0));
            $totalPaid = (float) (($db->fetch("SELECT SUM(amount) as sum FROM supplier_payments WHERE supplier_id = ?", [$supplierId])['sum'] ?? 0));
            $supInfo = $db->fetch("SELECT opening_balance FROM suppliers WHERE id = ?", [$supplierId]);
            $newBalance = round((float)($supInfo['opening_balance'] ?? 0) + $totalPurchases - $totalPaid, 2);
            $db->query("UPDATE suppliers SET current_balance = ?, updated_at = NOW() WHERE id = ?", [$newBalance, $supplierId]);
        }

        $logMsg = "Added purchase bill #$purchaseId ($itemsCount items, Total: " . App::money($billTotal) . ", Paid: " . App::money($paidAmount) . ", Balance: " . App::money($balance) . ") from $supplier";
        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [
            $storeId,
            Auth::id(),
            'Inventory Purchase',
            $logMsg
        ]);

        $this->flash('success', "Purchase bill recorded successfully! $itemsCount items added, stock auto-incremented, and supplier udhar ledger updated.");
        $this->redirect('inventory/purchaseView/' . $purchaseId);
    }

    public function purchaseView($id) {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $purchase = $db->fetch("SELECT * FROM inventory_purchases WHERE id = ? AND $where", array_merge([(int) $id], $whereParams));
        if (!$purchase) {
            $this->flash('error', 'Purchase record not found.');
            $this->redirect('inventory/index?tab=purchase_list');
        }

        $items = $db->fetchAll("SELECT * FROM inventory_purchase_items WHERE purchase_id = ? ORDER BY id ASC", [(int) $id]);
        if (empty($items)) {
            // Backward compatibility for legacy single-item purchases
            $items = [
                [
                    'id' => 0,
                    'purchase_id' => $purchase['id'],
                    'product_id' => $purchase['product_id'] ?? null,
                    'item_name' => $purchase['item_name'],
                    'unit_type' => $purchase['unit_type'] ?? 'pcs',
                    'quantity' => (float) ($purchase['quantity'] ?? 1),
                    'unit_cost' => (float) ($purchase['unit_cost'] ?? (($purchase['quantity'] ?? 0) > 0 ? $purchase['bill_total'] / $purchase['quantity'] : $purchase['bill_total'])),
                    'old_cost' => (float) ($purchase['old_cost'] ?? 0),
                    'new_sale_price' => !empty($purchase['new_sale_price']) ? (float)$purchase['new_sale_price'] : null,
                    'subtotal' => (float) $purchase['bill_total'],
                    'batch_number' => $purchase['batch_number'] ?? null,
                    'expiry_date' => $purchase['expiry_date'] ?? null,
                ]
            ];
        }

        $storeId = Tenant::currentStoreId();
        $paymentWhere = $storeId !== null ? "AND (store_id = ? OR store_id IS NULL)" : "";
        $paymentParams = $storeId !== null ? [(int) $id, $storeId] : [(int) $id];
        $payments = $db->fetchAll("
            SELECT *
            FROM inventory_purchase_payments
            WHERE purchase_id = ? $paymentWhere
            ORDER BY payment_date DESC, id DESC
        ", $paymentParams);

        $supplier = null;
        if (!empty($purchase['supplier_id'])) {
            $supplier = $db->fetch("SELECT * FROM suppliers WHERE id = ?", [(int)$purchase['supplier_id']]);
        }

        $this->view('../../modules/inventory/views/purchase', [
            'pageTitle' => 'Purchase - ' . $purchase['supplier_name'],
            'activeMenu' => 'inventory',
            'purchase' => $purchase,
            'supplier' => $supplier,
            'items' => $items,
            'payments' => $payments
        ]);
    }

    public function purchaseEdit($id) {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $purchase = $db->fetch("SELECT * FROM inventory_purchases WHERE id = ? AND $where", array_merge([(int) $id], $whereParams));
        if (!$purchase) {
            $this->flash('error', 'Purchase record not found.');
            $this->redirect('inventory/index?tab=purchase_list');
        }

        $items = $db->fetchAll("SELECT * FROM inventory_purchase_items WHERE purchase_id = ? ORDER BY id ASC", [(int) $id]);
        if (empty($items)) {
            $legacyQty = max(0.001, (float) ($purchase['quantity'] ?? 1));
            $legacyUnitCost = (float) (!empty($purchase['unit_cost']) ? $purchase['unit_cost'] : (($legacyQty > 0) ? ($purchase['bill_total'] / $legacyQty) : $purchase['bill_total']));
            $items = [
                [
                    'id' => 0,
                    'purchase_id' => $purchase['id'],
                    'product_id' => $purchase['product_id'] ?? null,
                    'item_name' => $purchase['item_name'],
                    'unit_type' => $purchase['unit_type'] ?? 'pcs',
                    'quantity' => $legacyQty,
                    'unit_cost' => $legacyUnitCost,
                    'new_sale_price' => !empty($purchase['new_sale_price']) ? (float)$purchase['new_sale_price'] : null,
                    'subtotal' => (float) $purchase['bill_total'],
                ]
            ];
        }

        [$supWhere, $supParams] = Tenant::storeFilter();
        $suppliersList = $db->fetchAll("SELECT id, name, company_name FROM suppliers WHERE $supWhere ORDER BY name ASC", $supParams);
        $allProductsList = $db->fetchAll("SELECT id, name, model_number, barcode, stock, cost, price, unit_type FROM products WHERE $where AND (parent_product_id IS NULL OR parent_product_id = 0) ORDER BY name ASC", $whereParams);

        $this->view('../../modules/inventory/views/purchase_edit', [
            'pageTitle' => 'Edit Purchase #' . $purchase['id'],
            'activeMenu' => 'inventory',
            'purchase' => $purchase,
            'items' => $items,
            'suppliersList' => $suppliersList,
            'allProductsList' => $allProductsList,
        ]);
    }

    public function purchaseUpdate($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $purchase = $db->fetch("SELECT * FROM inventory_purchases WHERE id = ? AND $where", array_merge([(int) $id], $whereParams));
        if (!$purchase) {
            $this->flash('error', 'Purchase record not found.');
            $this->redirect('inventory/index?tab=purchase_list');
        }

        $storeId = (int) Tenant::currentStoreId();
        $supplier = trim((string) ($_POST['supplier_name'] ?? $purchase['supplier_name']));
        $purchaseDate = trim((string) ($_POST['purchase_date'] ?? $purchase['purchase_date']));
        $billReference = trim((string) ($_POST['bill_reference'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $paidAmount = round(max(0, (float) ($_POST['paid_amount'] ?? $purchase['paid_amount'])), 2);

        // Resolve supplier
        $supplierId = (int) ($purchase['supplier_id'] ?? 0);
        $supRow = $db->fetch("SELECT id, name FROM suppliers WHERE (id = ? OR name = ?) AND store_id = ? LIMIT 1", [$supplierId, $supplier, $storeId]);
        if ($supRow) {
            $supplierId = (int) $supRow['id'];
            $supplier = $supRow['name'];
        } else if ($supplier !== '') {
            $db->query("
                INSERT INTO suppliers (store_id, name, opening_balance, current_balance, created_at)
                VALUES (?, ?, 0.00, 0.00, NOW())
            ", [$storeId, $supplier]);
            $supplierId = (int) $db->lastInsertId();
        }

        $rawItems = $_POST['items'] ?? [];
        if (empty($rawItems)) {
            $this->flash('error', 'Purchase must contain at least one valid item.');
            $this->redirect('inventory/purchaseEdit/' . (int) $id);
        }

        // Fetch old items to reverse stock difference
        $oldItems = $db->fetchAll("SELECT * FROM inventory_purchase_items WHERE purchase_id = ?", [(int) $id]);
        if (!empty($oldItems)) {
            foreach ($oldItems as $oldItem) {
                if (!empty($oldItem['product_id'])) {
                    $db->query("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?", [(float)$oldItem['quantity'], (int)$oldItem['product_id']]);
                }
            }
        } elseif (!empty($purchase['product_id'])) {
            $db->query("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?", [(float)$purchase['quantity'], (int)$purchase['product_id']]);
        }

        $db->query("DELETE FROM inventory_purchase_items WHERE purchase_id = ?", [(int) $id]);

        $totalBill = 0;
        $totalQty = 0;
        $firstItemName = '';
        $firstProdId = null;
        $firstQty = 1;
        $firstUnitCost = 0;
        $itemsCount = 0;

        foreach ($rawItems as $raw) {
            $itemName = trim((string) ($raw['item_name'] ?? ''));
            $qty = max(0.001, (float) ($raw['quantity'] ?? 1));
            $unitCost = max(0, (float) ($raw['unit_cost'] ?? 0));
            $newSalePrice = !empty($raw['new_sale_price']) ? max(0, (float) $raw['new_sale_price']) : null;
            $unitType = trim((string) ($raw['unit_type'] ?? 'pcs'));
            $prodId = !empty($raw['product_id']) ? (int) $raw['product_id'] : null;

            if ($itemName === '') continue;

            $subtotal = round($qty * $unitCost, 2);
            $totalBill += $subtotal;
            $totalQty += $qty;

            // Link to existing product or auto-create if new
            $product = null;
            if ($prodId > 0) {
                $product = $db->fetch("SELECT * FROM products WHERE id = ? AND store_id = ? LIMIT 1", [$prodId, $storeId]);
            }
            if (!$product && $itemName !== '') {
                $product = $db->fetch("SELECT * FROM products WHERE name = ? AND store_id = ? LIMIT 1", [$itemName, $storeId]);
            }

            if ($product) {
                $prodId = (int) $product['id'];
                $db->query("UPDATE products SET stock = stock + ? WHERE id = ?", [$qty, $prodId]);
                if ($newSalePrice !== null && $newSalePrice > 0) {
                    $db->query("UPDATE products SET price = ? WHERE id = ?", [$newSalePrice, $prodId]);
                }
            } else {
                $autoPrice = ($newSalePrice !== null && $newSalePrice > 0) ? $newSalePrice : round($unitCost * 1.25, 2);
                $barcode = BarcodeHelper::generateEan13();
                $db->query("
                    INSERT INTO products 
                        (store_id, name, barcode, unit_type, price, wholesale_price, cost, stock, min_stock, is_visible_online, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 5, 1, NOW())
                ", [
                    $storeId,
                    $itemName,
                    $barcode,
                    $unitType,
                    $autoPrice,
                    $autoPrice,
                    $unitCost,
                    $qty
                ]);
                $prodId = (int) $db->lastInsertId();
            }

            if ($firstItemName === '') {
                $firstItemName = $itemName;
                $firstProdId = $prodId;
                $firstQty = $qty;
                $firstUnitCost = $unitCost;
            }
            $itemsCount++;

            $db->query("
                INSERT INTO inventory_purchase_items
                    (store_id, purchase_id, product_id, item_name, unit_type, quantity, unit_cost, new_sale_price, subtotal, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [$storeId, (int) $id, $prodId, $itemName, $unitType, $qty, $unitCost, $newSalePrice, $subtotal]);
        }

        $paidAmount = min($paidAmount, $totalBill);
        $balance = round(max(0, $totalBill - $paidAmount), 2);
        $summaryItemName = $itemsCount > 1 ? "{$firstItemName} (+ " . ($itemsCount - 1) . " other items)" : $firstItemName;

        $db->query("
            UPDATE inventory_purchases
            SET product_id = ?, supplier_id = ?, supplier_name = ?, item_name = ?, items_count = ?, total_quantity = ?, 
                quantity = ?, unit_cost = ?, purchase_date = ?, bill_total = ?, paid_amount = ?, balance_amount = ?, bill_reference = ?, notes = ?, updated_at = NOW()
            WHERE id = ? AND $where
        ", array_merge([$firstProdId ?: null, $supplierId ?: null, $supplier, $summaryItemName, $itemsCount, $totalQty, $firstQty, $firstUnitCost, $purchaseDate, $totalBill, $paidAmount, $balance, $billReference ?: null, $notes ?: null, (int) $id], $whereParams));

        // Sync payment records
        $existingInitPay = $db->fetch("SELECT id FROM inventory_purchase_payments WHERE purchase_id = ? AND store_id = ? ORDER BY id ASC LIMIT 1", [(int) $id, $storeId]);
        if ($existingInitPay) {
            if ($paidAmount > 0) {
                $db->query("UPDATE inventory_purchase_payments SET amount = ?, payment_date = ? WHERE id = ?", [$paidAmount, $purchaseDate, (int)$existingInitPay['id']]);
                $db->query("UPDATE supplier_payments SET amount = ?, payment_date = ?, supplier_id = ? WHERE purchase_id = ? AND store_id = ?", [$paidAmount, $purchaseDate, $supplierId ?: null, (int)$id, $storeId]);
            } else {
                $db->query("DELETE FROM inventory_purchase_payments WHERE id = ?", [(int)$existingInitPay['id']]);
                $db->query("DELETE FROM supplier_payments WHERE purchase_id = ? AND store_id = ?", [(int)$id, $storeId]);
            }
        } elseif ($paidAmount > 0) {
            $db->query("
                INSERT INTO inventory_purchase_payments
                    (store_id, purchase_id, payment_date, amount, payment_method, notes, created_by)
                VALUES (?, ?, ?, ?, 'cash', 'Payment recorded on bill edit', ?)
            ", [$storeId, (int) $id, $purchaseDate, $paidAmount, Auth::id()]);

            if ($supplierId > 0) {
                $db->query("
                    INSERT INTO supplier_payments
                        (store_id, supplier_id, purchase_id, amount, payment_method, payment_date, reference_number, notes, created_by, created_at)
                    VALUES (?, ?, ?, ?, 'cash', ?, ?, 'Payment recorded on bill edit', ?, NOW())
                ", [$storeId, $supplierId, (int) $id, $paidAmount, $purchaseDate, $billReference ?: null, Auth::id()]);
            }
        }

        // Sync supplier balance
        if ($supplierId > 0) {
            $totalPurchases = (float) (($db->fetch("SELECT SUM(bill_total) as sum FROM inventory_purchases WHERE supplier_id = ?", [$supplierId])['sum'] ?? 0));
            $totalPaid = (float) (($db->fetch("SELECT SUM(amount) as sum FROM supplier_payments WHERE supplier_id = ?", [$supplierId])['sum'] ?? 0));
            $supInfo = $db->fetch("SELECT opening_balance FROM suppliers WHERE id = ?", [$supplierId]);
            $newSupBalance = round((float)($supInfo['opening_balance'] ?? 0) + $totalPurchases - $totalPaid, 2);
            $db->query("UPDATE suppliers SET current_balance = ?, updated_at = NOW() WHERE id = ?", [$newSupBalance, $supplierId]);
        }

        $this->flash('success', 'Purchase bill #' . (int) $id . ' updated successfully.');
        $this->redirect('inventory/purchaseView/' . (int) $id);
    }

    public function purchasePayment($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $purchase = $db->fetch("SELECT * FROM inventory_purchases WHERE id = ? AND $where", array_merge([(int) $id], $whereParams));
        if (!$purchase) {
            $this->flash('error', 'Purchase record not found.');
            $this->redirect('inventory/index?tab=purchase_list');
        }

        $amount = round(max(0, (float) ($_POST['amount'] ?? 0)), 2);
        $paymentDate = trim((string) ($_POST['payment_date'] ?? date('Y-m-d')));
        $method = trim((string) ($_POST['payment_method'] ?? 'cash'));
        $referenceNumber = trim((string) ($_POST['reference_number'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $remaining = round(max(0, (float) ($purchase['balance_amount'] ?? 0)), 2);

        if ($amount <= 0) {
            $this->flash('error', 'Payment amount must be greater than zero.');
            $this->redirect('inventory/purchaseView/' . (int) $id);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate)) {
            $paymentDate = date('Y-m-d');
        }

        $amount = min($amount, $remaining);
        $newPaid = round((float) $purchase['paid_amount'] + $amount, 2);
        $newBalance = round(max(0, (float) $purchase['bill_total'] - $newPaid), 2);

        $storeId = Tenant::currentStoreId();
        $combinedNotes = $notes;
        if ($referenceNumber !== '') {
            $combinedNotes = ($combinedNotes !== '' ? $combinedNotes . ' | ' : '') . 'Ref: ' . $referenceNumber;
        }

        $db->query("
            INSERT INTO inventory_purchase_payments
                (store_id, purchase_id, payment_date, amount, payment_method, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [
            $storeId,
            (int) $id,
            $paymentDate,
            $amount,
            $method ?: 'cash',
            $combinedNotes ?: null,
            Auth::id()
        ]);

        $supplierId = (int) ($purchase['supplier_id'] ?? 0);
        if ($supplierId > 0) {
            $db->query("
                INSERT INTO supplier_payments
                    (store_id, supplier_id, purchase_id, amount, payment_method, payment_date, reference_number, notes, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [
                $storeId, 
                $supplierId, 
                (int) $id, 
                $amount, 
                $method ?: 'cash', 
                $paymentDate, 
                $referenceNumber ?: ($purchase['bill_reference'] ?: null), 
                $notes ?: null, 
                Auth::id()
            ]);

            // Recalculate supplier balance
            $totalPurchases = (float) (($db->fetch("SELECT SUM(bill_total) as sum FROM inventory_purchases WHERE supplier_id = ?", [$supplierId])['sum'] ?? 0));
            $totalPaid = (float) (($db->fetch("SELECT SUM(amount) as sum FROM supplier_payments WHERE supplier_id = ?", [$supplierId])['sum'] ?? 0));
            $supInfo = $db->fetch("SELECT opening_balance FROM suppliers WHERE id = ?", [$supplierId]);
            $newSupBalance = round((float)($supInfo['opening_balance'] ?? 0) + $totalPurchases - $totalPaid, 2);
            $db->query("UPDATE suppliers SET current_balance = ?, updated_at = NOW() WHERE id = ?", [$newSupBalance, $supplierId]);
        }

        $db->query("
            UPDATE inventory_purchases
            SET paid_amount = ?, balance_amount = ?, updated_at = NOW()
            WHERE id = ? AND $where
        ", array_merge([$newPaid, $newBalance, (int) $id], $whereParams));

        $this->flash('success', 'Purchase payment of ' . App::money($amount) . ' recorded successfully.');
        $this->redirect('inventory/purchaseView/' . (int) $id);
    }

    public function purchaseDelete($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $purchase = $db->fetch("SELECT * FROM inventory_purchases WHERE id = ? AND $where", array_merge([(int) $id], $whereParams));
        if (!$purchase) {
            $this->flash('error', 'Purchase record not found.');
            $this->redirect('inventory/index?tab=purchase_list');
        }

        $storeId = (int) Tenant::currentStoreId();
        $supplierId = (int) ($purchase['supplier_id'] ?? 0);

        // 1. Fetch line items to reverse product stock
        $items = $db->fetchAll("SELECT product_id, quantity FROM inventory_purchase_items WHERE purchase_id = ?", [(int) $id]);
        if (!empty($items)) {
            foreach ($items as $it) {
                if (!empty($it['product_id'])) {
                    $qty = (float) $it['quantity'];
                    if ($storeId > 0) {
                        $db->query("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ? AND store_id = ?", [$qty, (int) $it['product_id'], $storeId]);
                    } else {
                        $db->query("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?", [$qty, (int) $it['product_id']]);
                    }
                }
            }
        } elseif (!empty($purchase['product_id'])) {
            // Fallback for single-item legacy purchase
            $qty = (float) ($purchase['quantity'] ?? 0);
            if ($storeId > 0) {
                $db->query("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ? AND store_id = ?", [$qty, (int) $purchase['product_id'], $storeId]);
            } else {
                $db->query("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?", [$qty, (int) $purchase['product_id']]);
            }
        }

        // 2. Delete purchase items
        $db->query("DELETE FROM inventory_purchase_items WHERE purchase_id = ?", [(int) $id]);

        // 3. Delete payments associated with this purchase
        $db->query("DELETE FROM inventory_purchase_payments WHERE purchase_id = ?", [(int) $id]);
        $db->query("DELETE FROM supplier_payments WHERE purchase_id = ?", [(int) $id]);

        // 4. Delete the purchase record
        $db->query("DELETE FROM inventory_purchases WHERE id = ? AND $where", array_merge([(int) $id], $whereParams));

        // 5. Re-sync supplier current_balance if supplier exists
        if ($supplierId > 0) {
            $totalPurchases = (float) (($db->fetch("SELECT SUM(bill_total) as sum FROM inventory_purchases WHERE supplier_id = ?", [$supplierId])['sum'] ?? 0));
            $totalPaid = (float) (($db->fetch("SELECT SUM(amount) as sum FROM supplier_payments WHERE supplier_id = ?", [$supplierId])['sum'] ?? 0));
            $supInfo = $db->fetch("SELECT opening_balance FROM suppliers WHERE id = ?", [$supplierId]);
            $newSupBalance = round((float)($supInfo['opening_balance'] ?? 0) + $totalPurchases - $totalPaid, 2);
            $db->query("UPDATE suppliers SET current_balance = ?, updated_at = NOW() WHERE id = ?", [$newSupBalance, $supplierId]);
        }

        $this->flash('success', 'Purchase bill #' . (int) $id . ' and its stock entries deleted successfully.');
        $this->redirect('inventory/index?tab=purchase_list');
    }


    private function productScanCodes($db, $productId) {
        [$where, $whereParams] = Tenant::storeFilter();
        return $db->fetchAll(
            "SELECT * FROM product_scan_codes WHERE product_id = ? AND $where ORDER BY scan_type = 'single' DESC, id ASC",
            array_merge([(int) $productId], $whereParams)
        );
    }

    private function productVariants($db, $productId) {
        [$where, $whereParams] = Tenant::storeFilter();
        return $db->fetchAll(
            "SELECT * FROM products WHERE parent_product_id = ? AND $where ORDER BY price ASC, id ASC",
            array_merge([(int) $productId], $whereParams)
        );
    }

    private function syncProductScanCodes($db, $productId, $barcode, $post) {
        $storeId = Tenant::currentStoreId();
        $productId = (int) $productId;
        $codes = [];
        $mainBarcode = App::normalizeBarcode($barcode);
        if ($mainBarcode !== '') {
            $codes[] = ['code' => $mainBarcode, 'type' => 'single', 'quantity' => 1, 'label' => 'Single Item'];
        }

        $extraCodes = is_array($post['scan_code'] ?? null) ? $post['scan_code'] : [];
        $extraTypes = is_array($post['scan_type'] ?? null) ? $post['scan_type'] : [];
        $extraQtys = is_array($post['scan_quantity'] ?? null) ? $post['scan_quantity'] : [];
        $extraLabels = is_array($post['scan_label'] ?? null) ? $post['scan_label'] : [];

        foreach ($extraCodes as $index => $rawCode) {
            $code = App::normalizeBarcode($rawCode);
            if ($code === '') {
                continue;
            }
            $type = ($extraTypes[$index] ?? '') === 'box' ? 'box' : 'single';
            $qty = max(1, (int) ($extraQtys[$index] ?? 1));
            if ($type === 'single') {
                $qty = 1;
            }
            $codes[] = [
                'code' => $code,
                'type' => $type,
                'quantity' => $qty,
                'label' => trim((string) ($extraLabels[$index] ?? '')) ?: ($type === 'box' ? 'Box / Carton' : 'Single Item'),
            ];
        }

        $seen = [];
        $db->query("DELETE FROM product_scan_codes WHERE product_id = ? AND store_id = ?", [$productId, $storeId]);
        foreach ($codes as $row) {
            $key = $row['code'] . '|' . $row['type'] . '|' . $row['quantity'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $db->query(
                "INSERT INTO product_scan_codes (store_id, product_id, code, scan_type, quantity, label) VALUES (?, ?, ?, ?, ?, ?)",
                [$storeId, $productId, $row['code'], $row['type'], $row['quantity'], $row['label']]
            );
        }
    }

    private function syncProductVariants($db, $parentId, $base, $post) {
        $ids = is_array($post['variant_id'] ?? null) ? $post['variant_id'] : [];
        $labels = is_array($post['variant_label'] ?? null) ? $post['variant_label'] : [];
        $prices = is_array($post['variant_price'] ?? null) ? $post['variant_price'] : [];
        $wholesalePrices = is_array($post['variant_wholesale_price'] ?? null) ? $post['variant_wholesale_price'] : [];
        $stocks = is_array($post['variant_stock'] ?? null) ? $post['variant_stock'] : [];
        $barcodes = is_array($post['variant_barcode'] ?? null) ? $post['variant_barcode'] : [];
        $storeId = Tenant::currentStoreId();
        $parentId = (int) $parentId;

        foreach ($labels as $index => $rawLabel) {
            $label = trim((string) $rawLabel);
            $price = (float) ($prices[$index] ?? 0);
            if ($label === '' || $price <= 0) {
                continue;
            }
            $variantId = max(0, (int) ($ids[$index] ?? 0));
            $variantBarcode = App::normalizeBarcode($barcodes[$index] ?? '') ?: App::normalizeBarcode($base['barcode'] ?? '');
            $name = trim((string) $base['name'] . ' - ' . $label);
            $model = $label;
            $wholesale = (float) ($wholesalePrices[$index] ?? $price);
            $stock = max(0, (int) ($stocks[$index] ?? 0));
            if ($variantId > 0) {
                $db->query(
                    "UPDATE products SET variant_label = ?, name = ?, model_number = ?, barcode = ?, unit_type = ?, image_path = ?, price = ?, wholesale_price = ?, cost = ?, stock = ?, min_stock = ? WHERE id = ? AND parent_product_id = ? AND store_id = ?",
                    [$label, $name, $model, $variantBarcode ?: null, $base['unit_type'] ?? 'pcs', $base['image_path'] ?: null, $price, $wholesale, (float) ($base['cost'] ?? 0), $stock, (int) ($base['min_stock'] ?? 5), $variantId, $parentId, $storeId]
                );
            } else {
                $db->query(
                    "INSERT INTO products (store_id, parent_product_id, variant_label, name, model_number, barcode, unit_value, unit_type, image_path, price, wholesale_price, cost, stock, min_stock)
                     VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?)",
                    [$storeId, $parentId, $label, $name, $model, $variantBarcode ?: null, $base['unit_type'] ?? 'pcs', $base['image_path'] ?: null, $price, $wholesale, (float) ($base['cost'] ?? 0), $stock, (int) ($base['min_stock'] ?? 5)]
                );
                $variantId = (int) $db->lastInsertId();
            }
            if ($variantBarcode !== '') {
                $this->syncProductScanCodes($db, $variantId, $variantBarcode, [
                    'scan_code' => [$variantBarcode],
                    'scan_type' => ['single'],
                    'scan_quantity' => [1],
                    'scan_label' => [$label],
                ]);
            }
        }
    }

    public function expiryAlerts() {
        $db = Database::getInstance();
        [$where, $params] = Tenant::storeFilter('pb');

        // 1. Expired Batches
        $expired = $db->fetchAll("
            SELECT pb.*, p.name as product_name, p.barcode as product_barcode, p.unit_type,
                   DATEDIFF(CURDATE(), pb.expiry_date) as days_expired
            FROM product_batches pb
            JOIN products p ON p.id = pb.product_id
            WHERE $where AND pb.expiry_date < CURDATE() AND pb.current_stock > 0
            ORDER BY pb.expiry_date ASC
        ", $params);

        // 2. Expiring in 30 Days (Urgent)
        $expiring30 = $db->fetchAll("
            SELECT pb.*, p.name as product_name, p.barcode as product_barcode, p.unit_type,
                   DATEDIFF(pb.expiry_date, CURDATE()) as days_left
            FROM product_batches pb
            JOIN products p ON p.id = pb.product_id
            WHERE $where AND pb.expiry_date >= CURDATE() AND pb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND pb.current_stock > 0
            ORDER BY pb.expiry_date ASC
        ", $params);

        // 3. Expiring in 31 - 90 Days
        $expiring90 = $db->fetchAll("
            SELECT pb.*, p.name as product_name, p.barcode as product_barcode, p.unit_type,
                   DATEDIFF(pb.expiry_date, CURDATE()) as days_left
            FROM product_batches pb
            JOIN products p ON p.id = pb.product_id
            WHERE $where AND pb.expiry_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND pb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND pb.current_stock > 0
            ORDER BY pb.expiry_date ASC
        ", $params);

        // Stats
        $expiredCount = count($expired);
        $expiredValue = 0;
        foreach ($expired as $b) {
            $expiredValue += ((float)$b['current_stock'] * (float)$b['cost_price']);
        }

        $expiring30Count = count($expiring30);
        $expiring30Value = 0;
        foreach ($expiring30 as $b) {
            $expiring30Value += ((float)$b['current_stock'] * (float)$b['cost_price']);
        }

        $this->view('../../modules/inventory/views/expiry_alerts', [
            'pageTitle' => 'Batch & Expiry Date Alerts',
            'activeMenu' => 'inventory',
            'expired' => $expired,
            'expiring30' => $expiring30,
            'expiring90' => $expiring90,
            'expiredCount' => $expiredCount,
            'expiredValue' => $expiredValue,
            'expiring30Count' => $expiring30Count,
            'expiring30Value' => $expiring30Value,
            'isEnabled' => App::setting('enable_expiry_tracking', '0') === '1'
        ]);
    }
}


