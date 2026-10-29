<?php

class PosController extends Controller {
    private $columnCache = [];

    public function __construct() {
        AuthMiddleware::handlePermission('pos');
    }

    public function index() {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        
        // Product Pagination Logic (20 items per page)
        $limit = 20;
        $page = max(1, (int)($_GET['page'] ?? 1));
        
        $countResult = $db->fetch("SELECT COUNT(*) as count FROM products WHERE $where", $whereParams);
        $totalProducts = (int) ($countResult['count'] ?? 0);
        $totalPages = max(1, (int) ceil($totalProducts / $limit));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;
        
        $products = $db->fetchAll("SELECT * FROM products WHERE $where ORDER BY stock DESC, name ASC LIMIT $limit OFFSET $offset", $whereParams);

        $categoriesRaw = $db->fetchAll("SELECT DISTINCT unit_type FROM products WHERE $where AND unit_type IS NOT NULL AND unit_type != '' ORDER BY unit_type ASC", $whereParams);
        $categories = array_map(function($r) { return strtoupper(trim($r['unit_type'])); }, $categoriesRaw);
        $categories = array_values(array_unique(array_filter($categories)));

        $autoAddProduct = null;
        if (!empty($_GET['add'])) {
            $autoAddProduct = $db->fetch("SELECT id, name, model_number, barcode, price, wholesale_price, cost, stock, unit_type, 1 AS scan_quantity FROM products WHERE id = ? AND $where", array_merge([$_GET['add']], $whereParams));
        } elseif (!empty($_GET['scan'])) {
            $rawScan = trim((string) $_GET['scan']);
            $normScan = App::normalizeBarcode($rawScan);
            $numericId = ctype_digit($rawScan) ? (int) $rawScan : 0;
            [$storeWhere, $storeParams] = Tenant::storeFilter('p');

            $autoAddProduct = $db->fetch(
                "SELECT 
                    p.id, p.parent_product_id, p.name, p.model_number, p.barcode, p.price, p.wholesale_price, p.cost, p.stock,
                    p.unit_value, p.unit_type, p.variant_label,
                    COALESCE(psc.quantity, 1) AS scan_quantity,
                    COALESCE(psc.scan_type, 'single') AS scan_type,
                    COALESCE(psc.label, '') AS scan_label
                 FROM products p
                 LEFT JOIN product_scan_codes psc
                     ON psc.product_id = p.id
                     AND psc.store_id = p.store_id
                     AND (psc.code = ? OR psc.code = ?)
                 WHERE $storeWhere
                     AND (
                         psc.code = ?
                         OR psc.code = ?
                         OR p.barcode = ?
                         OR p.barcode = ?
                         OR p.model_number = ?
                         OR p.model_number = ?
                         OR p.name = ?
                         OR (p.id = ? AND ? > 0)
                     )
                 ORDER BY p.parent_product_id IS NULL DESC, p.price ASC, p.id ASC
                 LIMIT 1",
                array_merge(
                    [$normScan, $rawScan],
                    $storeParams,
                    [$normScan, $rawScan, $normScan, $rawScan, $normScan, $rawScan, $rawScan, $numericId, $numericId]
                )
            );
        }
        $this->view('../../modules/pos/views/index', [
            'pageTitle' => 'Point of Sale',
            'activeMenu' => 'pos',
            'products' => $products,
            'categories' => $categories,
            'autoAddProduct' => $autoAddProduct,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalProducts' => $totalProducts,
            'taxSettings' => [
                'enabled' => App::setting('tax_enabled', '0') === '1',
                'fbr_enabled' => App::setting('fbr_pos_enabled', '0') === '1',
                'label' => App::setting('tax_label', 'Sales Tax'),
                'rate' => (float) App::setting('tax_rate', '0'),
                'fbr_pos_id' => App::setting('fbr_pos_id', '')
            ]
        ]);
    }

    public function search() {
        $query = trim($_GET['q'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        
        $extractedCode = '';
        if (preg_match('/(\d{6,20})/', $query, $matches)) {
            $extractedCode = $matches[1];
        }

        $sql = "SELECT * FROM products WHERE $where";
        $params = $whereParams;

        if ($query !== '') {
            $sql .= " AND (name LIKE ? OR model_number LIKE ? OR barcode LIKE ?";
            $params = array_merge($params, ["%$query%", "%$query%", "%$query%"]);

            if ($extractedCode !== '') {
                $sql .= " OR barcode = ?";
                $params[] = $extractedCode;
            }
            $sql .= ")";
        }

        if ($category !== '') {
            $sql .= " AND UPPER(unit_type) = ?";
            $params[] = strtoupper($category);
        }

        $sql .= " ORDER BY stock DESC, name ASC LIMIT 500";

        $products = $db->fetchAll($sql, $params);
        $this->jsonResponse($products);
    }

    public function barcode() {
        if (!class_exists('SearchController')) {
            require_once APP_PATH . '/controllers/SearchController.php';
        }
        $searchCtrl = new SearchController();
        $searchCtrl->barcode();
    }

    public function customers() {
        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 1) {
            $this->jsonResponse(['results' => []]);
        }

        $db = Database::getInstance();
        $connection = $db->getConnection();
        $this->ensureAddressColumns($connection);
        [$where, $whereParams] = Tenant::storeFilter();
        $hasAddress = $this->hasColumn($connection, 'customers', 'address');
        $selectAddress = $hasAddress ? ", COALESCE(address, '') AS address" : ", '' AS address";
        $rows = $db->fetchAll(
            "SELECT id, name, phone, COALESCE(loyalty_points, 0) AS loyalty_points $selectAddress FROM customers WHERE $where AND (name LIKE ? OR phone LIKE ?) ORDER BY created_at DESC LIMIT 10",
            array_merge($whereParams, ["%$query%", "%$query%"])
        );
        $this->jsonResponse(['results' => $rows]);
    }

    public function csrfToken() {
        $this->jsonResponse(['success' => true, 'csrf_token' => Security::generateCsrfToken()]);
    }

    public function heldSales() {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter('i');
        $sql = "
            SELECT i.id, 
                   MAX(i.custom_id) AS custom_id, 
                   COALESCE(NULLIF(TRIM(MAX(i.customer_name_snapshot)), ''), NULLIF(TRIM(MAX(i.notes)), ''), 'Walk-in Customer') AS customer_name,
                   MAX(i.customer_phone_snapshot) AS customer_phone, 
                   MAX(i.customer_address_snapshot) AS customer_address, 
                   MAX(i.total) AS total, 
                   MAX(i.created_at) AS created_at, 
                   COUNT(ii.id) AS item_count
            FROM invoices i
            LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
            WHERE i.status = 'draft' AND $where
            GROUP BY i.id
            ORDER BY MAX(i.created_at) DESC
            LIMIT 50
        ";
        $drafts = $db->fetchAll($sql, $whereParams);
        $this->jsonResponse(['success' => true, 'drafts' => $drafts]);
    }

    public function recallDraft() {
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'Invalid draft ID'], 400);
        }

        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $invoice = $db->fetch("SELECT * FROM invoices WHERE id = ? AND status = 'draft' AND $where LIMIT 1", array_merge([$id], $whereParams));
        if (!$invoice) {
            $this->jsonResponse(['error' => 'Held draft sale not found.'], 404);
        }

        $items = $db->fetchAll("SELECT * FROM invoice_items WHERE invoice_id = ?", [$id]);
        $formattedItems = [];
        foreach ($items as $item) {
            $formattedItems[] = [
                'id' => (int) $item['product_id'],
                'name' => $item['product_name'],
                'model_number' => $item['model_number'],
                'price' => (float) $item['price'],
                'quantity' => (float) $item['quantity'],
                'discount' => (float) $item['discount'],
                'unit_label' => $item['product_unit_label'] ?? 'pcs',
                'cost_price' => (float) ($item['cost_price'] ?? 0),
            ];
        }

        $db->query("DELETE FROM invoice_items WHERE invoice_id = ?", [$id]);
        $db->query("DELETE FROM invoices WHERE id = ?", [$id]);

        $this->jsonResponse([
            'success' => true,
            'draft' => [
                'id' => (int) $invoice['id'],
                'customer_name' => (string) ($invoice['customer_name_snapshot'] ?: ''),
                'customer_phone' => (string) ($invoice['customer_phone_snapshot'] ?: ''),
                'customer_address' => (string) ($invoice['customer_address_snapshot'] ?: ''),
                'notes' => (string) ($invoice['notes'] ?: ''),
                'discount' => (float) ($invoice['discount'] ?? 0),
                'items' => $formattedItems,
            ]
        ]);
    }

    public function deleteDraft() {
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'Invalid draft ID'], 400);
        }

        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $invoice = $db->fetch("SELECT id FROM invoices WHERE id = ? AND status = 'draft' AND $where LIMIT 1", array_merge([$id], $whereParams));
        if ($invoice) {
            $db->query("DELETE FROM invoice_items WHERE invoice_id = ?", [$id]);
            $db->query("DELETE FROM invoices WHERE id = ?", [$id]);
        }
        $this->jsonResponse(['success' => true]);
    }

    public function clearAllDrafts() {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $drafts = $db->fetchAll("SELECT id FROM invoices WHERE status = 'draft' AND $where", $whereParams);
        if (!empty($drafts)) {
            $ids = array_column($drafts, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $db->query("DELETE FROM invoice_items WHERE invoice_id IN ($placeholders)", $ids);
            $db->query("DELETE FROM invoices WHERE id IN ($placeholders)", $ids);
        }
        $this->jsonResponse(['success' => true]);
    }

    public function checkout() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method'], 400);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data) || empty($data['items'])) {
            $this->jsonResponse(['error' => 'Cart is empty. Add products to checkout.'], 400);
        }
        $requestToken = (string) ($data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (!Security::verifyCsrfToken($requestToken)) {
            $this->jsonResponse(['error' => 'Security token mismatch. Refresh and try again.'], 419);
        }

        $db = Database::getInstance();
        $connection = $db->getConnection();
        $storeId = Tenant::currentStoreId();
        [$where, $whereParams] = Tenant::storeFilter();

        try {
            $connection->beginTransaction();

            $subtotal = 0;
            $mainDiscount = max(0, round((float) ($data['discount'] ?? 0)));
            $status = ($data['status'] ?? 'paid') === 'draft' ? 'draft' : 'paid';
            $transactionType = $this->sanitizeTransactionType($data['transaction_type'] ?? 'Sale');
            $customerName = trim($data['customer_name'] ?? '');
            $customerPhone = $this->normalizePhone($data['customer_phone'] ?? '');
            $customerAddress = trim((string) ($data['customer_address'] ?? ''));
            $notes = trim($data['notes'] ?? '');
            $paymentMethod = $this->sanitizePaymentMethod($data['payment_method'] ?? 'cash');
            $saleChannel = $this->sanitizeSaleChannel($data['sale_channel'] ?? 'retail');
            $creditDueDate = $this->sanitizeCreditDueDate($data['credit_due_date'] ?? null);
            $creditPrivateNote = trim((string) ($data['credit_private_note'] ?? ''));
            $creditInitialPayment = max(0, round((float) ($data['credit_initial_payment'] ?? 0)));
            $creditInitialPaymentMethod = $this->sanitizeCreditPaymentMethod($data['credit_initial_payment_method'] ?? 'cash');
            $saleCreatedAt = $this->sanitizeSaleDate($data['sale_date'] ?? '');
            // Temporary value for insert; replaced with formatted invoice id after row creation.
            $customId = 'INV-TMP-' . strtoupper(substr(uniqid('', true), -8));
            $this->ensureAddressColumns($connection);
            $hasInvoiceAddressSnapshot = $this->hasColumn($connection, 'invoices', 'customer_address_snapshot');
            if (!$hasInvoiceAddressSnapshot && $customerAddress !== '') {
                $notes = trim($notes . ($notes !== '' ? "\n" : '') . 'Address: ' . $customerAddress);
            }

            $customerId = $this->resolveCustomer($connection, $customerName, $customerPhone, $customerAddress);

            $taxSettings = [
                'enabled' => App::setting('tax_enabled', '0') === '1',
                'label' => trim((string) App::setting('tax_label', 'Sales Tax')),
                'rate' => max(0, (float) App::setting('tax_rate', '0')),
                'fbr_pos_id' => App::setting('fbr_pos_enabled', '0') === '1' ? trim((string) App::setting('fbr_pos_id', '')) : ''
            ];

            $stmt = $connection->prepare("
                INSERT INTO invoices
                (store_id, custom_id, user_id, customer_id, subtotal, discount, total, status, transaction_type, payment_method, sale_channel, credit_due_date, credit_private_note, paid_amount, balance_due, is_credit_settled, tax_amount, tax_rate, tax_label, fbr_pos_id, notes, customer_name_snapshot, customer_phone_snapshot, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $storeId,
                $customId,
                Auth::id(),
                $customerId,
                0,
                $mainDiscount,
                0,
                $status,
                $transactionType,
                $paymentMethod,
                $saleChannel,
                $paymentMethod === 'credit' ? $creditDueDate : null,
                $paymentMethod === 'credit' ? ($creditPrivateNote ?: null) : null,
                0,
                0,
                0,
                0,
                $taxSettings['enabled'] ? $taxSettings['rate'] : 0,
                $taxSettings['enabled'] ? ($taxSettings['label'] ?: 'Sales Tax') : null,
                $taxSettings['enabled'] ? ($taxSettings['fbr_pos_id'] ?: null) : null,
                $notes,
                $customerName ?: null,
                $customerPhone ?: null,
                $saleCreatedAt
            ]);

            $invoiceId = $connection->lastInsertId();
            $customId = $this->generateInvoiceCustomId($db, $saleCreatedAt, $storeId, $where, $whereParams);
            $connection->prepare("UPDATE invoices SET custom_id = ? WHERE id = ?")->execute([$customId, $invoiceId]);
            if ($hasInvoiceAddressSnapshot && $customerAddress !== '') {
                $connection->prepare("UPDATE invoices SET customer_address_snapshot = ? WHERE id = ?")->execute([$customerAddress, $invoiceId]);
            }

            foreach ($data['items'] as $item) {
                $itemName = trim($item['name'] ?? '');
                if ($itemName === '') {
                    continue;
                }

                $itemQty = max(0.001, (float) ($item['quantity'] ?? 1));
                $itemPrice = max(0, (float) ($item['price'] ?? 0));
                $itemDiscount = max(0, (float) ($item['discount'] ?? 0));
                $modelNumber = trim($item['model_number'] ?? '');
                $productId = empty($item['id']) ? null : (int) $item['id'];

                $itemCost = 0.0;
                if (!$productId) {
                    $customProduct = $connection->prepare("
                        INSERT INTO products (store_id, name, model_number, price, cost, stock, min_stock, is_custom)
                        VALUES (?, ?, ?, ?, ?, 0, 0, 1)
                    ");
                    $customProduct->execute([$storeId, $itemName, $modelNumber ?: null, $itemPrice, $itemPrice]);
                    $productId = (int) $connection->lastInsertId();
                    $itemCost = $itemPrice;
                } else {
                    $costStmt = $connection->prepare("SELECT cost FROM products WHERE id = ? AND store_id = ? LIMIT 1");
                    $costStmt->execute([$productId, $storeId]);
                    $itemCost = (float) ($costStmt->fetchColumn() ?: 0);
                }

                $itemSubtotal = round(($itemPrice * $itemQty) - $itemDiscount, 2);
                $subtotal += $itemSubtotal;

                $itemStmt = $connection->prepare("
                    INSERT INTO invoice_items (store_id, invoice_id, product_id, product_name, model_number, quantity, price, price_tier, discount, total, cost_price)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $itemStmt->execute([
                    $storeId,
                    $invoiceId,
                    $productId,
                    $itemName,
                    $modelNumber ?: null,
                    $itemQty,
                    $itemPrice,
                    $saleChannel,
                    $itemDiscount,
                    $itemSubtotal,
                    $itemCost
                ]);

                if ($status === 'paid') {
                    if (in_array($transactionType, ['Sale', 'Exchange'], true)) {
                        $connection->prepare("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ? AND store_id = ?")->execute([$itemQty, $productId, $storeId]);
                    } elseif (in_array($transactionType, ['Return', 'Refund'], true)) {
                        $connection->prepare("UPDATE products SET stock = stock + ? WHERE id = ? AND store_id = ?")->execute([$itemQty, $productId, $storeId]);
                    }
                }
            }

            $taxBase = max(0, $subtotal - $mainDiscount);
            $taxAmount = $taxSettings['enabled'] ? round($taxBase * ($taxSettings['rate'] / 100)) : 0;
            $finalTotal = max(0, $taxBase + $taxAmount);
            if ($status === 'draft') {
                $paidAmount = 0;
            } elseif ($paymentMethod === 'credit') {
                $paidAmount = min($finalTotal, $creditInitialPayment);
            } else {
                $paidAmount = $finalTotal;
            }
            $balanceDue = max(0, $finalTotal - $paidAmount);
            $isCreditSettled = $balanceDue <= 0 ? 1 : 0;

            $pointsRedeemed = max(0, (int) ($data['points_redeemed'] ?? 0));
            $pointsEarned = ($status === 'paid' && in_array($transactionType, ['Sale', 'Exchange'], true)) ? max(0, (int) floor($finalTotal / 100)) : 0;

            $connection->prepare("UPDATE invoices SET subtotal = ?, total = ?, paid_amount = ?, balance_due = ?, is_credit_settled = ?, tax_amount = ?, tax_rate = ?, tax_label = ?, fbr_pos_id = ?, points_earned = ?, points_redeemed = ? WHERE id = ?")
                ->execute([$subtotal, $finalTotal, $paidAmount, $balanceDue, $isCreditSettled, $taxAmount, $taxSettings['enabled'] ? $taxSettings['rate'] : 0, $taxSettings['enabled'] ? ($taxSettings['label'] ?: 'Sales Tax') : null, $taxSettings['enabled'] ? ($taxSettings['fbr_pos_id'] ?: null) : null, $pointsEarned, $pointsRedeemed, $invoiceId]);

            if ($paymentMethod === 'credit') {
                $connection->prepare("
                    INSERT INTO credit_payments (store_id, invoice_id, amount, payment_method, note, next_due_date, private_message, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([
                    $storeId,
                    $invoiceId,
                    0,
                    'credit',
                    'Credit invoice created',
                    $creditDueDate,
                    $creditPrivateNote ?: null,
                    Auth::id()
                ]);

                if ($paidAmount > 0) {
                    $connection->prepare("
                        INSERT INTO credit_payments (store_id, invoice_id, amount, payment_method, note, next_due_date, private_message, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ")->execute([
                        $storeId,
                        $invoiceId,
                        $paidAmount,
                        $creditInitialPaymentMethod,
                        'Initial payment at checkout',
                        $balanceDue > 0 ? $creditDueDate : null,
                        null,
                        Auth::id()
                    ]);
                }
            }

            if ($customerId && $status === 'paid') {
                if (in_array($transactionType, ['Sale', 'Exchange'], true)) {
                    $connection->prepare("UPDATE customers SET total_spending = total_spending + ?, loyalty_points = GREATEST(0, loyalty_points - ?) + ? WHERE id = ? AND store_id = ?")->execute([$finalTotal, $pointsRedeemed, $pointsEarned, $customerId, $storeId]);
                } elseif (in_array($transactionType, ['Return', 'Refund'], true)) {
                    $connection->prepare("UPDATE customers SET total_spending = GREATEST(total_spending - ?, 0), loyalty_points = GREATEST(0, loyalty_points - ?) WHERE id = ? AND store_id = ?")->execute([$finalTotal, $pointsEarned, $customerId, $storeId]);
                }
            }

            $detail = ($status === 'draft' ? 'Saved draft' : 'Completed ' . strtolower($transactionType)) . " $customId via " . ucfirst($paymentMethod) . " for " . App::money($finalTotal);
            $connection->prepare("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)")->execute([$storeId, Auth::id(), 'Sale', $detail]);

            $connection->commit();
            $this->jsonResponse(['success' => true, 'invoice_id' => $invoiceId, 'custom_id' => $customId]);
        } catch (Exception $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            $this->logCheckoutFailure($e, $data ?? [], $storeId ?? null);
            $this->jsonResponse(['error' => 'Checkout failed. Please try again.'], 500);
        }
    }

    public function receipt($id) {
        $db = Database::getInstance();
        [$whereI, $whereIParams] = Tenant::storeFilter('i');
        [$whereItem, $whereItemParams] = Tenant::storeFilter();
        $invoice = $db->fetch("
            SELECT i.*, u.name as cashier, c.name as customer_name, c.phone as customer_phone
            FROM invoices i
            LEFT JOIN users u ON i.user_id = u.id
            LEFT JOIN customers c ON i.customer_id = c.id
            WHERE (i.id = ? OR i.custom_id = ?) AND $whereI
        ", array_merge([$id, $id], $whereIParams));

        if (!$invoice) {
            die("Invoice not found");
        }

        $actualInvoiceId = (int) $invoice['id'];
        $items = $this->attachProductImages($db->fetchAll("SELECT * FROM invoice_items WHERE invoice_id = ? AND $whereItem", array_merge([$actualInvoiceId], $whereItemParams)));
        if (($_GET['print_image'] ?? '') === '1') {
            $src = (defined('BASE_URL') ? BASE_URL : '') . '/pos/receipt/' . $actualInvoiceId . '?download_image=1&inline=1';
            $title = 'Thermal Receipt ' . Security::escape($invoice['custom_id']);
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . $title . '</title><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>
                * { box-sizing: border-box; }
                html, body { margin: 0; padding: 0; background: #e2e8f0; width: 100%; height: auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
                body { display: flex; flex-direction: column; align-items: center; justify-content: flex-start; min-height: 100vh; }
                .thermal-toolbar { width: 100%; max-width: 440px; background: #0f172a; color: #fff; padding: 10px 14px; margin: 12px auto 8px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 14px rgba(0,0,0,0.15); font-size: 13px; font-weight: 700; }
                .thermal-toolbar-actions { display: flex; gap: 8px; }
                .thermal-btn { border: none; border-radius: 8px; padding: 7px 14px; font-weight: 700; font-size: 12px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
                .thermal-btn-print { background: #0f6dff; color: #fff; }
                .thermal-btn-back { background: #334155; color: #fff; }
                .receipt-print-wrapper { background: #fff; padding: 0; margin: 0 auto 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.12); width: fit-content; }
                .receipt-print-img { display: block; width: 100%; max-width: 320px; height: auto; image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges; }
                @media print {
                    @page { size: auto; margin: 0mm !important; }
                    .thermal-toolbar, .no-print { display: none !important; }
                    html, body { width: 100% !important; height: auto !important; margin: 0 !important; padding: 0 !important; background: #fff !important; }
                    .receipt-print-wrapper { box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
                    .receipt-print-img { width: 100% !important; max-width: 72mm !important; margin: 0 auto !important; height: auto !important; }
                }
            </style></head><body>
            <div class="thermal-toolbar no-print">
                <span>⚡ Direct Thermal Mode (Zero Waste)</span>
                <div class="thermal-toolbar-actions">
                    <button class="thermal-btn thermal-btn-print" onclick="window.print()">🖨️ Print</button>
                    <a class="thermal-btn thermal-btn-back" href="' . (defined('BASE_URL') ? BASE_URL : '') . '/pos/receipt/' . $actualInvoiceId . '">Web Slip</a>
                </div>
            </div>
            <div class="receipt-print-wrapper">
                <img class="receipt-print-img" src="' . Security::escape($src) . '" alt="' . $title . '" onload="setTimeout(function(){window.print()},220)">
            </div>
            </body></html>';
            exit;
        }

        if (($_GET['download_image'] ?? '') === '1') {
            $image = ReceiptMedia::salesImage($invoice, $items);
            $png = ReceiptMedia::pngBytes($image);
            imagedestroy($image);
            $filename = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) $invoice['custom_id']) . '_receipt.png';
            header('Content-Type: image/png');
            header('Content-Disposition: ' . (($_GET['inline'] ?? '') === '1' ? 'inline' : 'attachment') . '; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($png));
            echo $png;
            exit;
        }

        if (($_GET['download_pdf'] ?? '') === '1') {
            $image = ReceiptMedia::salesImage($invoice, $items);
            $jpeg = ReceiptMedia::jpegBytes($image);
            $pdfContent = ReceiptMedia::imagePdf($jpeg, imagesx($image), imagesy($image));
            imagedestroy($image);
            $filename = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) $invoice['custom_id']) . '_receipt.pdf';
            header('Content-Type: application/pdf');
            $disposition = (($_GET['inline'] ?? '') === '1') ? 'inline' : 'attachment';
            header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdfContent));
            echo $pdfContent;
            exit;
        }
        require APP_PATH . '/../modules/pos/views/receipt.php';
    }

    public function receiptPdf($id) {
        $db = Database::getInstance();
        [$whereI, $whereIParams] = Tenant::storeFilter('i');
        [$whereItem, $whereItemParams] = Tenant::storeFilter();
        $invoice = $db->fetch("
            SELECT i.*, u.name as cashier, c.name as customer_name, c.phone as customer_phone
            FROM invoices i
            LEFT JOIN users u ON i.user_id = u.id
            LEFT JOIN customers c ON i.customer_id = c.id
            WHERE i.id = ? AND $whereI
        ", array_merge([$id], $whereIParams));
        if (!$invoice) {
            die('Invoice not found');
        }

        $items = $this->attachProductImages($db->fetchAll("SELECT * FROM invoice_items WHERE invoice_id = ? AND $whereItem", array_merge([$id], $whereItemParams)));
        $image = ReceiptMedia::salesImage($invoice, $items);
        $jpeg = ReceiptMedia::jpegBytes($image);
        $pdfContent = ReceiptMedia::imagePdf($jpeg, imagesx($image), imagesy($image));
        imagedestroy($image);
        $filename = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) $invoice['custom_id']) . '_receipt.pdf';

        header('Content-Type: application/pdf');
        $disposition = (($_GET['inline'] ?? '') === '1') ? 'inline' : 'attachment';
        header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        echo $pdfContent;
        exit;
    }

    private function buildSalesReceiptPdf($invoice, $items) {
        $companyName = App::setting('company_name', 'DevAltum POS');
        $companyAddress = trim((string) App::setting('company_address', ''));
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
            return $text === '' ? [''] : explode("\n", wordwrap($text, $width, "\n", true));
        };
        $pair = function ($label, $value) use ($clean) {
            $label = substr($clean($label), 0, 14);
            $value = $clean($value);
            $width = 32;
            $rightWidth = max(8, $width - strlen($label));
            if (strlen($value) > $rightWidth) {
                return $label . "\n" . str_pad('', max(0, $width - strlen($value))) . $value;
            }
            return $label . str_pad('', max(1, $width - strlen($label) - strlen($value))) . $value;
        };

        $invoiceTotal = (float) ($invoice['total'] ?? 0);
        $taxAmount = (float) ($invoice['tax_amount'] ?? 0);
        $taxRate = (float) ($invoice['tax_rate'] ?? 0);
        $taxLabel = trim((string) ($invoice['tax_label'] ?? App::setting('tax_label', 'Tax'))) ?: 'Tax';
        $openDue = max(0, max(($invoiceTotal - (float) ($invoice['paid_amount'] ?? 0)), (float) ($invoice['balance_due'] ?? 0)));
        $displayPaid = max(0, $invoiceTotal - $openDue);
        $rawStatus = strtolower(trim((string) ($invoice['status'] ?? '')));
        if (!in_array($rawStatus, ['paid', 'unpaid', 'draft'], true)) {
            $rawStatus = $openDue > 0 ? 'unpaid' : 'paid';
        }
        $sep = str_repeat('-', 32);
        $lines = [];
        foreach ($wrap(strtoupper($companyName), 30) as $line) {
            $lines[] = str_pad($line, 32, ' ', STR_PAD_BOTH);
        }
        if ($companyNtn !== '') {
            $lines[] = str_pad('NTN : ' . $companyNtn, 32, ' ', STR_PAD_BOTH);
        }
        $lines[] = $sep;
        $lines[] = $pair('Receipt ID :', $invoice['custom_id'] ?? '-');
        $lines[] = $pair('Date :', date('M d, Y h:i A', strtotime($invoice['created_at'] ?? 'now')));
        $lines[] = $pair('Cashier :', $invoice['cashier'] ?? 'System');
        $lines[] = $pair('Customer :', ($invoice['customer_name_snapshot'] ?: $invoice['customer_name'] ?: 'Walk-in'));
        $lines[] = $pair('Phone :', ($invoice['customer_phone_snapshot'] ?: $invoice['customer_phone'] ?: '-'));
        $lines[] = $pair('Status :', strtoupper($rawStatus));
        $lines[] = $pair('Payment :', ucfirst((string) ($invoice['payment_method'] ?? 'cash')));
        $lines[] = $pair('Channel :', ucfirst((string) ($invoice['sale_channel'] ?? 'retail')));
        if (!empty($invoice['fbr_pos_id'])) {
            $lines[] = $pair('FBR POS :', (string) $invoice['fbr_pos_id']);
        }
        $lines[] = $sep;
        $lines[] = 'Item            Qty  Unit   Total';
        $lines[] = $sep;
        $totalItems = count($items);
        foreach ($items as $item) {
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
        $lines[] = $pair('Subtotal', $money($invoice['subtotal'] ?? 0));
        $lines[] = $pair('Discount', $money($invoice['discount'] ?? 0));
        if ($taxAmount > 0) {
            $rateText = rtrim(rtrim(number_format($taxRate, 3), '0'), '.');
            $lines[] = $pair($taxLabel . ($rateText !== '' ? " ({$rateText}%)" : ''), $money($taxAmount));
        }
        $lines[] = $pair('Total', $money($invoice['total'] ?? 0));
        $lines[] = $pair('Total Items', (string) $totalItems);
        $lines[] = $pair('Paid', $money($displayPaid));
        $lines[] = $pair('Remaining', $money($openDue));
        if (strtolower((string) ($invoice['payment_method'] ?? '')) === 'credit' || $openDue > 0) {
            $lines[] = $pair('Due Date', !empty($invoice['credit_due_date']) ? date('M d, Y h:i A', strtotime($invoice['credit_due_date'])) : '-');
        }
        if (!empty($invoice['notes'])) {
            $lines[] = $sep;
            $lines[] = 'Note';
            foreach ($wrap($invoice['notes'], 32) as $noteLine) {
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

    private function resolveCustomer($connection, $name, $phone, $address = '') {
        $name = trim($name);
        $phone = $this->normalizePhone($phone);
        $address = trim((string) $address);
        $hasAddress = $this->hasColumn($connection, 'customers', 'address');
        $storeId = (int) Tenant::currentStoreId();

        if ($name === '' && $phone === '') {
            $walkInStmt = $connection->prepare("SELECT id FROM customers WHERE store_id = ? AND name = 'Walk-in Customer' LIMIT 1");
            $walkInStmt->execute([$storeId]);
            $walkIn = $walkInStmt->fetch(PDO::FETCH_ASSOC);
            if ($walkIn) {
                return (int) $walkIn['id'];
            }
            if ($hasAddress) {
                $stmt = $connection->prepare("INSERT INTO customers (store_id, name, phone, address, total_spending) VALUES (?, 'Walk-in Customer', NULL, NULL, 0)");
                $stmt->execute([$storeId]);
            } else {
                $stmt = $connection->prepare("INSERT INTO customers (store_id, name, phone, total_spending) VALUES (?, 'Walk-in Customer', NULL, 0)");
                $stmt->execute([$storeId]);
            }
            return (int) $connection->lastInsertId();
        }

        if ($phone !== '') {
            $existing = $connection->prepare("SELECT id FROM customers WHERE store_id = ? AND phone = ? LIMIT 1");
            $existing->execute([$storeId, $phone]);
            $customer = $existing->fetch(PDO::FETCH_ASSOC);

            if ($customer) {
                if ($name !== '') {
                    $connection->prepare("UPDATE customers SET name = ? WHERE id = ? AND store_id = ?")->execute([$name, $customer['id'], $storeId]);
                }
                if ($hasAddress && $address !== '') {
                    $connection->prepare("UPDATE customers SET address = ? WHERE id = ? AND store_id = ?")->execute([$address, $customer['id'], $storeId]);
                }
                return (int) $customer['id'];
            }
        }

        if ($name !== '') {
            $existingByName = $connection->prepare("SELECT id, phone FROM customers WHERE store_id = ? AND name = ? ORDER BY id DESC LIMIT 1");
            $existingByName->execute([$storeId, $name]);
            $customerByName = $existingByName->fetch(PDO::FETCH_ASSOC);

            if ($customerByName) {
                if ($phone !== '' && empty($customerByName['phone'])) {
                    $connection->prepare("UPDATE customers SET phone = ? WHERE id = ? AND store_id = ?")->execute([$phone, $customerByName['id'], $storeId]);
                }
                if ($hasAddress && $address !== '') {
                    $connection->prepare("UPDATE customers SET address = ? WHERE id = ? AND store_id = ?")->execute([$address, $customerByName['id'], $storeId]);
                }
                return (int) $customerByName['id'];
            }
        }

        if ($name !== '' || $phone !== '') {
            if ($hasAddress) {
                $insert = $connection->prepare("INSERT INTO customers (store_id, name, phone, address, total_spending) VALUES (?, ?, ?, ?, 0)");
                $insert->execute([$storeId, $name !== '' ? $name : 'Walk-in Customer', $phone !== '' ? $phone : null, $address !== '' ? $address : null]);
            } else {
                $insert = $connection->prepare("INSERT INTO customers (store_id, name, phone, total_spending) VALUES (?, ?, ?, 0)");
                $insert->execute([$storeId, $name !== '' ? $name : 'Walk-in Customer', $phone !== '' ? $phone : null]);
            }
            return (int) $connection->lastInsertId();
        }

        return null;
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
                if (!preg_match('~^(?:https?:)?//~i', $value)) {
                    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
                    return $base . '/' . ltrim($value, '/');
                }
                return $value;
            }
        }

        return '';
    }

    private function ensureAddressColumns($connection) {
        try {
            if (!$this->hasColumn($connection, 'customers', 'address')) {
                $connection->exec("ALTER TABLE customers ADD COLUMN address VARCHAR(255) NULL AFTER phone");
                $this->columnCache['customers.address'] = true;
            }
        } catch (Exception $e) {
            // Keep checkout working even if schema update is not allowed.
        }

        try {
            if (!$this->hasColumn($connection, 'invoices', 'customer_address_snapshot')) {
                $connection->exec("ALTER TABLE invoices ADD COLUMN customer_address_snapshot VARCHAR(255) NULL AFTER customer_phone_snapshot");
                $this->columnCache['invoices.customer_address_snapshot'] = true;
            }
        } catch (Exception $e) {
            // Keep checkout working even if schema update is not allowed.
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

    private function generateInvoiceCustomId($db, $saleCreatedAt, $storeId, $where, $whereParams) {
        $saleTimestamp = strtotime((string) $saleCreatedAt) ?: time();
        $monthCode = strtoupper(substr(date('M', $saleTimestamp), 0, 2));
        $month = (int) date('m', $saleTimestamp);
        $year = (int) date('Y', $saleTimestamp);
        $prefix = 'INV-' . $monthCode . '-';

        $store = Tenant::currentStore();
        if ((int) ($store['parent_store_id'] ?? 0) > 0) {
            $branchCode = strtoupper(preg_replace('/[^A-Z0-9]+/', '', (string) ($store['branch_code'] ?? '')));
            $prefix .= ($branchCode !== '' ? substr($branchCode, 0, 10) : 'B' . (int) $storeId) . '-';
        }

        $seqRow = $db->fetch(
            "SELECT MAX(CAST(SUBSTRING_INDEX(custom_id, '-', -1) AS UNSIGNED)) AS max_seq
             FROM invoices
             WHERE $where AND custom_id LIKE ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?",
            array_merge($whereParams, [$prefix . '%', $year, $month])
        );
        $nextSeq = (int) ($seqRow['max_seq'] ?? 0) + 1;

        do {
            $customId = $prefix . $nextSeq;
            $exists = $db->fetch("SELECT id FROM invoices WHERE custom_id = ? LIMIT 1", [$customId]);
            $nextSeq++;
        } while ($exists);

        return $customId;
    }

    private function logCheckoutFailure($exception, $payload, $storeId) {
        $dir = defined('STORAGE_PATH') ? STORAGE_PATH . '/logs' : dirname(__DIR__, 3) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $summary = [
            'time' => date('Y-m-d H:i:s'),
            'store_id' => $storeId,
            'user_id' => Auth::id(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'items_count' => count($items),
            'payment_method' => $payload['payment_method'] ?? null,
            'status' => $payload['status'] ?? null,
        ];

        @file_put_contents($dir . '/pos_checkout.log', json_encode($summary, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }

    private function normalizePhone($phone) {
        $phone = trim((string) $phone);
        return preg_replace('/\s+/', '', $phone) ?: '';
    }

    private function sanitizeTransactionType($type) {
        $allowed = ['Sale', 'Exchange', 'Return', 'Refund'];
        return in_array($type, $allowed, true) ? $type : 'Sale';
    }

    private function sanitizePaymentMethod($method) {
        $allowed = ['cash', 'online', 'credit'];
        $method = strtolower(trim((string) $method));
        return in_array($method, $allowed, true) ? $method : 'cash';
    }

    private function sanitizeCreditPaymentMethod($method) {
        $allowed = ['cash', 'online'];
        $method = strtolower(trim((string) $method));
        return in_array($method, $allowed, true) ? $method : 'cash';
    }

    private function sanitizeSaleChannel($channel) {
        $allowed = ['retail', 'wholesale'];
        $channel = strtolower(trim((string) $channel));
        return in_array($channel, $allowed, true) ? $channel : 'retail';
    }

    private function sanitizeCreditDueDate($dateTime) {
        $raw = trim((string) $dateTime);
        if ($raw === '') {
            return null;
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function sanitizeSaleDate($dateValue) {
        $raw = trim((string) $dateValue);
        if ($raw === '') {
            return date('Y-m-d H:i:s');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            $raw .= ' ' . date('H:i:s');
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return date('Y-m-d H:i:s');
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function buildSimplePdf($lines) {
        $safeLines = array_map(function ($line) {
            $text = preg_replace('/[\r\n\t]+/', ' ', (string) $line);
            $text = preg_replace('/[^ -~]/', '', $text);
            return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $text);
        }, $lines);

        $y = 800;
        $body = "BT\n/F1 10 Tf\n50 820 Td\n";
        foreach ($safeLines as $line) {
            $body .= "1 0 0 1 50 $y Tm ($line) Tj\n";
            $y -= 14;
            if ($y < 60) {
                break;
            }
        }
        $body .= "ET";

        $objects = [];
        $objects[] = "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj";
        $objects[] = "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj";
        $objects[] = "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>endobj";
        $objects[] = "4 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>endobj";
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
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF";

        return $pdf;
    }

    private function buildReceiptWidthPdf($lines) {
        $safeLines = array_map(function ($line) {
            $text = preg_replace('/[\r\n\t]+/', ' ', (string) $line);
            $text = preg_replace('/[^ -~]/', '', $text);
            return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $text);
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
        $body .= "ET";

        $objects = [];
        $objects[] = "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj";
        $objects[] = "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj";
        $objects[] = "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageWidth $pageHeight] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>endobj";
        $objects[] = "4 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>endobj";
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
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF";

        return $pdf;
    }
}
