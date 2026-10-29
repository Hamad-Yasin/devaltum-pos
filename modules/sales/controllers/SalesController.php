<?php

class SalesController extends Controller {

    public function __construct() {
        AuthMiddleware::handlePermission('sales');
    }

    public function index() {
        $db = Database::getInstance();
        [$whereI, $whereIParams] = Tenant::storeFilter('i');
        $q = trim($_GET['q'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $paymentMethod = trim($_GET['payment_method'] ?? '');
        $saleChannel = trim($_GET['sale_channel'] ?? '');
        $isOwnerView = Auth::isAdmin();
        $staffId = $isOwnerView ? max(0, (int) ($_GET['staff_id'] ?? 0)) : (int) Auth::id();
        [$whereU, $paramsU] = Tenant::storeFilter();
        $staffOptions = $isOwnerView ? $db->fetchAll("SELECT id, name FROM users WHERE $whereU ORDER BY name ASC", $paramsU) : [];
        if ($isOwnerView && $staffId > 0 && !$db->fetch("SELECT id FROM users WHERE id = ? AND $whereU LIMIT 1", array_merge([$staffId], $paramsU))) {
            $staffId = 0;
        }
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
                COALESCE(i.customer_name_snapshot, c.name, 'Walk-in Customer') AS customer_display,
                COALESCE(i.customer_phone_snapshot, c.phone, '') AS customer_phone_display
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            WHERE $whereI
        ";
        $params = $whereIParams;

        if ($q !== '') {
            $sql .= " AND (i.custom_id LIKE ? OR c.name LIKE ? OR i.customer_name_snapshot LIKE ?)";
            array_push($params, "%$q%", "%$q%", "%$q%");
        }
        if ($type !== '') {
            $sql .= " AND i.transaction_type = ?";
            $params[] = $type;
        }
        if ($status !== '') {
            $sql .= " AND i.status = ?";
            $params[] = $status;
        }
        if ($paymentMethod !== '') {
            $sql .= " AND i.payment_method = ?";
            $params[] = $paymentMethod;
        }
        if ($saleChannel !== '') {
            $sql .= " AND i.sale_channel = ?";
            $params[] = $saleChannel;
        }
        if ($staffId > 0) {
            $sql .= " AND i.user_id = ?";
            $params[] = $staffId;
        }
        if ($dateFrom !== '') {
            $sql .= " AND DATE(i.created_at) >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $sql .= " AND DATE(i.created_at) <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY i.created_at DESC";
        $sales = $db->fetchAll($sql, $params);
        $this->view('../../modules/sales/views/index', [
            'pageTitle' => 'Sales',
            'activeMenu' => 'sales',
            'sales' => $sales,
            'staffOptions' => $staffOptions,
            'isOwnerView' => $isOwnerView,
            'filters' => compact('q', 'type', 'status', 'paymentMethod', 'saleChannel', 'staffId', 'datePreset', 'dateFrom', 'dateTo')
        ]);
    }

    public function export() {
        $db = Database::getInstance();
        [$whereI, $whereIParams] = Tenant::storeFilter('i');
        $q = trim($_GET['q'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $paymentMethod = trim($_GET['payment_method'] ?? '');
        $saleChannel = trim($_GET['sale_channel'] ?? '');
        $isOwnerView = Auth::isAdmin();
        $staffId = $isOwnerView ? max(0, (int) ($_GET['staff_id'] ?? 0)) : (int) Auth::id();
        if ($isOwnerView && $staffId > 0) {
            [$whereU, $paramsU] = Tenant::storeFilter();
            if (!$db->fetch("SELECT id FROM users WHERE id = ? AND $whereU LIMIT 1", array_merge([$staffId], $paramsU))) {
                $staffId = 0;
            }
        }
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
                i.custom_id as Invoice,
                i.transaction_type as Type,
                i.status as Status,
                i.payment_method as Payment,
                i.sale_channel as Channel,
                i.subtotal as Subtotal,
                i.discount as Discount,
                i.total as Total,
                COALESCE(i.customer_name_snapshot, c.name, 'Walk-in Customer') AS Customer,
                i.created_at as Date
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            WHERE $whereI
        ";
        $params = $whereIParams;

        if ($q !== '') {
            $sql .= " AND (i.custom_id LIKE ? OR c.name LIKE ? OR i.customer_name_snapshot LIKE ?)";
            array_push($params, "%$q%", "%$q%", "%$q%");
        }
        if ($type !== '') {
            $sql .= " AND i.transaction_type = ?";
            $params[] = $type;
        }
        if ($status !== '') {
            $sql .= " AND i.status = ?";
            $params[] = $status;
        }
        if ($paymentMethod !== '') {
            $sql .= " AND i.payment_method = ?";
            $params[] = $paymentMethod;
        }
        if ($saleChannel !== '') {
            $sql .= " AND i.sale_channel = ?";
            $params[] = $saleChannel;
        }
        if ($staffId > 0) {
            $sql .= " AND i.user_id = ?";
            $params[] = $staffId;
        }
        if ($dateFrom !== '') {
            $sql .= " AND DATE(i.created_at) >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $sql .= " AND DATE(i.created_at) <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY i.created_at DESC";
        $sales = $db->fetchAll($sql, $params);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sales_export_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Invoice', 'Type', 'Status', 'Payment', 'Channel', 'Subtotal', 'Discount', 'Total', 'Customer', 'Date']);
        foreach ($sales as $row) {
            fputcsv($output, [
                $row['Invoice'], $row['Type'], $row['Status'], $row['Payment'], $row['Channel'],
                $row['Subtotal'], $row['Discount'], $row['Total'], $row['Customer'], $row['Date']
            ]);
        }
        fclose($output);
        exit;
    }

    public function import() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file']['tmp_name'])) {
            $this->redirect('sales/index');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $handle = fopen($_FILES['file']['tmp_name'], "r");
        $header = fgetcsv($handle);
        if (!$header) {
            $this->flash('error', 'CSV file is empty or invalid.');
            $this->redirect('sales/index');
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
            $total = isset($colMap['total']) ? max(0, (float) ($data[$colMap['total']] ?? 0)) : 0;
            if ($invoice === '' && $total <= 0) {
                continue;
            }

            $type = isset($colMap['type']) ? trim($data[$colMap['type']] ?? 'Sale') : 'Sale';
            $status = isset($colMap['status']) ? strtolower(trim($data[$colMap['status']] ?? 'paid')) : 'paid';
            $payment = isset($colMap['payment']) ? strtolower(trim($data[$colMap['payment']] ?? 'cash')) : (isset($colMap['payment_method']) ? strtolower(trim($data[$colMap['payment_method']] ?? 'cash')) : 'cash');
            $channel = isset($colMap['channel']) ? strtolower(trim($data[$colMap['channel']] ?? 'retail')) : (isset($colMap['sale_channel']) ? strtolower(trim($data[$colMap['sale_channel']] ?? 'retail')) : 'retail');
            $subtotal = isset($colMap['subtotal']) ? max(0, (float) ($data[$colMap['subtotal']] ?? $total)) : $total;
            $discount = isset($colMap['discount']) ? max(0, (float) ($data[$colMap['discount']] ?? 0)) : 0;
            $date = isset($colMap['date']) ? trim($data[$colMap['date']] ?? date('Y-m-d H:i:s')) : date('Y-m-d H:i:s');

            if (strtotime($date) === false) {
                $date = date('Y-m-d H:i:s');
            } else {
                $date = date('Y-m-d H:i:s', strtotime($date));
            }

            // Customer details extraction
            $customer = '';
            foreach (['customer', 'customer_name', 'name', 'client'] as $ck) {
                if (isset($colMap[$ck]) && trim($data[$colMap[$ck]] ?? '') !== '') {
                    $customer = trim($data[$colMap[$ck]]);
                    break;
                }
            }

            $phone = '';
            foreach (['phone', 'customer_phone', 'mobile', 'customer_mobile', 'contact', 'cell'] as $pk) {
                if (isset($colMap[$pk]) && trim($data[$colMap[$pk]] ?? '') !== '') {
                    $phone = trim($data[$colMap[$pk]]);
                    break;
                }
            }

            $address = '';
            foreach (['address', 'customer_address', 'location', 'city'] as $ak) {
                if (isset($colMap[$ak]) && trim($data[$colMap[$ak]] ?? '') !== '') {
                    $address = trim($data[$colMap[$ak]]);
                    break;
                }
            }

            // Paid amount & balance due calculation
            $paid = null;
            foreach (['paid_amount', 'paid', 'amount_paid'] as $pdk) {
                if (isset($colMap[$pdk]) && trim($data[$colMap[$pdk]] ?? '') !== '') {
                    $paid = max(0, (float)$data[$colMap[$pdk]]);
                    break;
                }
            }

            $balance = null;
            foreach (['balance_due', 'balance', 'due'] as $bdk) {
                if (isset($colMap[$bdk]) && trim($data[$colMap[$bdk]] ?? '') !== '') {
                    $balance = max(0, (float)$data[$colMap[$bdk]]);
                    break;
                }
            }

            if ($paid === null && $balance === null) {
                if ($status === 'paid') {
                    $paid = $total;
                    $balance = 0.0;
                } elseif ($status === 'partial') {
                    $paid = round($total / 2, 2);
                    $balance = max(0, $total - $paid);
                } else { // unpaid / credit / pending
                    $paid = 0.0;
                    $balance = $total;
                }
            } elseif ($paid !== null && $balance === null) {
                $balance = max(0, $total - $paid);
            } elseif ($paid === null && $balance !== null) {
                $paid = max(0, $total - $balance);
            }

            if ($status === 'paid' && $balance > 0) {
                $status = ($paid > 0) ? 'partial' : 'unpaid';
            } elseif ($status !== 'paid' && $balance <= 0 && $total > 0) {
                $status = 'paid';
            }
            $isSettled = ($balance <= 0) ? 1 : 0;

            // Customer resolution, linking, and auto-creation
            $cleanCustomer = trim($customer);
            $isWalkIn = (
                $cleanCustomer === ''
                || in_array(strtolower($cleanCustomer), ['walk-in customer', 'walk in customer', 'walk-in', 'walk in', 'walkin', 'cash customer', 'cash sale', 'counter customer', 'counter sale'])
            );

            $customerId = null;
            $customerNameSnapshot = $cleanCustomer !== '' ? $cleanCustomer : 'Walk-in Customer';

            if ($isWalkIn && $phone === '') {
                $customerNameSnapshot = 'Walk-in Customer';
                $walkIn = $db->fetch("SELECT id FROM customers WHERE store_id = ? AND LOWER(name) = 'walk-in customer' LIMIT 1", [$storeId]);
                if ($walkIn) {
                    $customerId = (int) $walkIn['id'];
                }
            } else {
                if ($cleanCustomer === '' || $isWalkIn) {
                    $customerNameSnapshot = 'Customer ' . ($phone !== '' ? $phone : 'Direct');
                } else {
                    $customerNameSnapshot = $cleanCustomer;
                }

                $existing = null;
                if ($phone !== '') {
                    $existing = $db->fetch("SELECT id, name, phone, address, total_spending FROM customers WHERE phone = ? AND store_id = ? LIMIT 1", [$phone, $storeId]);
                    if (!$existing) {
                        $existing = $db->fetch("SELECT id, name, phone, address, total_spending FROM customers WHERE phone = ? LIMIT 1", [$phone]);
                    }
                }

                if (!$existing && !$isWalkIn && $customerNameSnapshot !== '') {
                    $existing = $db->fetch("SELECT id, name, phone, address, total_spending FROM customers WHERE LOWER(name) = LOWER(?) AND store_id = ? LIMIT 1", [$customerNameSnapshot, $storeId]);
                }

                if ($existing) {
                    $customerId = (int) $existing['id'];
                    $updateParts = ["total_spending = total_spending + ?"];
                    $updateVals = [$total];

                    if ($phone !== '' && empty($existing['phone'])) {
                        $updateParts[] = "phone = ?";
                        $updateVals[] = $phone;
                    }
                    if ($address !== '' && empty($existing['address'])) {
                        $updateParts[] = "address = ?";
                        $updateVals[] = $address;
                    }
                    $updateVals[] = $customerId;
                    $db->query("UPDATE customers SET " . implode(', ', $updateParts) . " WHERE id = ?", $updateVals);
                } else {
                    try {
                        $db->query("
                            INSERT INTO customers (store_id, name, phone, address, total_spending, created_at)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ", [
                            $storeId,
                            $customerNameSnapshot,
                            $phone !== '' ? $phone : null,
                            $address !== '' ? $address : null,
                            $total,
                            $date
                        ]);
                        $customerId = (int) $db->lastInsertId();
                    } catch (Throwable $e) {
                        if ($phone !== '') {
                            $fallback = $db->fetch("SELECT id FROM customers WHERE phone = ? LIMIT 1", [$phone]);
                            if ($fallback) {
                                $customerId = (int) $fallback['id'];
                                $db->query("UPDATE customers SET total_spending = total_spending + ? WHERE id = ?", [$total, $customerId]);
                            }
                        }
                    }
                }
            }

            if ($invoice !== '') {
                $exists = $db->fetch("SELECT id FROM invoices WHERE custom_id = ? AND store_id = ? LIMIT 1", [$invoice, $storeId]);
                if ($exists) {
                    continue;
                }
            } else {
                $invoice = 'INV-' . date('Ymd-His', strtotime($date)) . '-' . rand(100, 999);
            }

            try {
                $db->query("
                    INSERT INTO invoices
                    (store_id, custom_id, user_id, customer_id, subtotal, discount, total, status, transaction_type, payment_method, sale_channel, paid_amount, balance_due, is_credit_settled, customer_name_snapshot, customer_phone_snapshot, customer_address_snapshot, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $storeId,
                    $invoice,
                    $userId,
                    $customerId,
                    $subtotal,
                    $discount,
                    $total,
                    $status,
                    $type,
                    $payment,
                    $channel,
                    $paid,
                    $balance,
                    $isSettled,
                    $customerNameSnapshot,
                    $phone !== '' ? $phone : null,
                    $address !== '' ? $address : null,
                    $date
                ]);
            } catch (Throwable $e) {
                // Fallback if customer_address_snapshot column is absent in older schema
                $db->query("
                    INSERT INTO invoices
                    (store_id, custom_id, user_id, customer_id, subtotal, discount, total, status, transaction_type, payment_method, sale_channel, paid_amount, balance_due, is_credit_settled, customer_name_snapshot, customer_phone_snapshot, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $storeId,
                    $invoice,
                    $userId,
                    $customerId,
                    $subtotal,
                    $discount,
                    $total,
                    $status,
                    $type,
                    $payment,
                    $channel,
                    $paid,
                    $balance,
                    $isSettled,
                    $customerNameSnapshot,
                    $phone !== '' ? $phone : null,
                    $date
                ]);
            }

            $invoiceId = $db->lastInsertId();

            $itemProduct = '';
            foreach (['product', 'product_name', 'item', 'item_name'] as $ik) {
                if (isset($colMap[$ik]) && trim($data[$colMap[$ik]] ?? '') !== '') {
                    $itemProduct = trim($data[$colMap[$ik]]);
                    break;
                }
            }
            if ($itemProduct === '') {
                $itemProduct = 'Imported Sale (' . $type . ')';
            }

            $itemQty = 1;
            foreach (['quantity', 'qty'] as $qk) {
                if (isset($colMap[$qk]) && (float)($data[$colMap[$qk]] ?? 0) > 0) {
                    $itemQty = (float)$data[$colMap[$qk]];
                    break;
                }
            }

            $itemPrice = $itemQty > 0 ? round($subtotal / $itemQty, 2) : $subtotal;

            $db->query("
                INSERT INTO invoice_items
                (store_id, invoice_id, product_id, product_name, model_number, quantity, price, price_tier, discount, total)
                VALUES (?, ?, NULL, ?, 'Imported', ?, ?, 'retail', ?, ?)
            ", [
                $storeId,
                $invoiceId,
                $itemProduct,
                $itemQty,
                $itemPrice,
                $discount,
                $total
            ]);

            $imported++;
        }

        fclose($handle);
        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [$storeId, $userId, 'Sales', "Imported $imported sales from CSV with customer syncing"]);
        $this->flash('success', $imported . ' sales imported successfully. Customer records and ledger updated.');
        $this->redirect('sales/index');
    }


    public function action($id) {
        $db = Database::getInstance();
        [$whereI, $whereIParams] = Tenant::storeFilter('i');
        [$whereItem, $whereItemParams] = Tenant::storeFilter();
        $sale = $db->fetch(" 
            SELECT
                i.*,
                u.name AS cashier_name,
                COALESCE(i.customer_name_snapshot, c.name, 'Walk-in Customer') AS customer_display,
                COALESCE(i.customer_phone_snapshot, c.phone, '') AS customer_phone_display
            FROM invoices i
            LEFT JOIN users u ON u.id = i.user_id
            LEFT JOIN customers c ON c.id = i.customer_id
            WHERE i.id = ? AND $whereI
        ", array_merge([$id], $whereIParams));

        if (!$sale) {
            $this->flash('error', 'Sale not found.');
            $this->redirect('sales/index');
        }
        if (!Auth::isAdmin() && (int) ($sale['user_id'] ?? 0) !== (int) Auth::id()) {
            $this->flash('error', 'You can only open your own sales.');
            $this->redirect('sales/index');
        }

        $items = $this->attachProductImages($db->fetchAll("SELECT * FROM invoice_items WHERE invoice_id = ? AND $whereItem ORDER BY id ASC", array_merge([$id], $whereItemParams)));
        $sale['open_due'] = max(
            0,
            max(
                ((float) ($sale['total'] ?? 0) - (float) ($sale['paid_amount'] ?? 0)),
                (float) ($sale['balance_due'] ?? 0)
            )
        );

        $this->view('../../modules/sales/views/view', [
            'pageTitle' => 'Sale Profile',
            'activeMenu' => 'sales',
            'sale' => $sale,
            'items' => $items
        ]);
    }
    public function edit($id) {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $invoice = $db->fetch("SELECT * FROM invoices WHERE id = ? AND $where", array_merge([$id], $whereParams));
        if (!$invoice) {
            die("Sale not found");
        }
        if (!Auth::isAdmin() && (int) ($invoice['user_id'] ?? 0) !== (int) Auth::id()) {
            $this->flash('error', 'You can only edit your own sales.');
            $this->redirect('sales/index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrfOrAbort();
            $connection = $db->getConnection();
            $oldItems = $db->fetchAll("SELECT * FROM invoice_items WHERE invoice_id = ? AND $where", array_merge([$id], $whereParams));
            $productsList = $db->fetchAll("SELECT id, name, COALESCE(model_number, '') AS model_number, price FROM products WHERE $where ORDER BY name ASC", $whereParams);
            $productLookup = [];
            foreach ($productsList as $productRow) {
                $productLookup[(int) $productRow['id']] = $productRow;
            }
            $customerName = trim($_POST['customer_name'] ?? '');
            $customerPhone = trim($_POST['customer_phone'] ?? '');
            $transactionType = $this->sanitizeTransactionType($_POST['transaction_type'] ?? 'Sale');
            $status = $this->sanitizeSaleStatus($_POST['status'] ?? ($invoice['status'] ?? 'paid'));
            $paymentMethod = $this->sanitizePaymentMethod($_POST['payment_method'] ?? ($invoice['payment_method'] ?? 'cash'));
            $notes = trim($_POST['notes'] ?? '');
            $discount = max(0, (float) ($_POST['discount'] ?? 0));
            $creditPartialPayment = max(0, (float) ($_POST['credit_partial_payment'] ?? 0));
            $creditPartialPaymentMethod = $this->sanitizeCreditPaymentMethod($_POST['credit_partial_payment_method'] ?? 'cash');
            $creditDueDateInput = trim((string) ($_POST['credit_due_date'] ?? ''));
            $creditPrivateNoteInput = trim((string) ($_POST['credit_private_note'] ?? ''));
            $customerId = $invoice['customer_id'];
            $removeItems = array_map('intval', $_POST['remove_items'] ?? []);
            $removeQuantities = array_map('intval', $_POST['remove_item_quantities'] ?? []);
            $addProductIds = $_POST['add_item_product_id'] ?? [];
            $addProductNames = $_POST['add_item_name'] ?? [];
            $addProductModels = $_POST['add_item_model'] ?? [];
            $addQuantities = $_POST['add_item_quantity'] ?? [];
            $addPrices = $_POST['add_item_price'] ?? [];
            $addDiscounts = $_POST['add_item_discount'] ?? [];

            if ($customerName !== '' && $customerPhone !== '') {
                $existing = $db->fetch("SELECT id FROM customers WHERE phone = ? AND $where LIMIT 1", array_merge([$customerPhone], $whereParams));
                if ($existing) {
                    $customerId = $existing['id'];
                    $db->query("UPDATE customers SET name = ? WHERE id = ? AND $where", array_merge([$customerName, $customerId], $whereParams));
                } else {
                    $db->query("INSERT INTO customers (store_id, name, phone, total_spending) VALUES (?, ?, ?, 0)", [Tenant::currentStoreId(), $customerName, $customerPhone]);
                    $customerId = $db->lastInsertId();
                }
            }

            try {
                $connection->beginTransaction();
                $this->reverseInvoiceImpact($connection, $invoice, $oldItems);
                $this->applyItemRemovals($connection, $id, $oldItems, $removeItems, $removeQuantities);
                $this->applyItemAdditions(
                    $connection,
                    $id,
                    $invoice['sale_channel'] ?? 'retail',
                    $productLookup,
                    $addProductIds,
                    $addProductNames,
                    $addProductModels,
                    $addQuantities,
                    $addPrices,
                    $addDiscounts
                );

                $newItems = $db->fetchAll("SELECT * FROM invoice_items WHERE invoice_id = ? AND $where", array_merge([$id], $whereParams));
                if (empty($newItems)) {
                    throw new Exception('At least one item must remain in the sale.');
                }

                $subtotal = 0;
                foreach ($newItems as $item) {
                    $subtotal += (float) $item['total'];
                }
                $total = max(0, $subtotal - $discount);

                $newPaidAmount = 0.0;
                if ($status === 'draft') {
                    $newPaidAmount = 0.0;
                } elseif ($status === 'paid') {
                    $newPaidAmount = $total;
                } elseif ($status === 'unpaid') {
                    // For unpaid invoices, preserve manual partial payment only on credit.
                    $newPaidAmount = $paymentMethod === 'credit' ? min($total, $creditPartialPayment) : 0.0;
                }
                $newBalanceDue = max(0, $total - $newPaidAmount);
                $isCreditSettled = $newBalanceDue <= 0 ? 1 : 0;
                if ($status !== 'draft') {
                    // Keep status consistent with actual due state after recalculation.
                    $status = $newBalanceDue > 0 ? 'unpaid' : 'paid';
                }

                $creditDueDateValue = null;
                $creditPrivateNoteValue = null;
                if ($paymentMethod === 'credit') {
                    $creditDueDateValue = $this->sanitizeDateTimeInput($creditDueDateInput);
                    $creditPrivateNoteValue = $creditPrivateNoteInput !== '' ? $creditPrivateNoteInput : null;
                    if ($newBalanceDue <= 0) {
                        $creditDueDateValue = null;
                    }
                }

                $connection->prepare("
                    UPDATE invoices
                    SET customer_id = ?, subtotal = ?, discount = ?, total = ?, paid_amount = ?, balance_due = ?, is_credit_settled = ?, status = ?, transaction_type = ?, payment_method = ?, notes = ?, customer_name_snapshot = ?, customer_phone_snapshot = ?, credit_due_date = ?, credit_private_note = ?
                    WHERE id = ? AND $where
                ")->execute([
                    $customerId ?: null,
                    $subtotal,
                    $discount,
                    $total,
                    $newPaidAmount,
                    $newBalanceDue,
                    $isCreditSettled,
                    $status,
                    $transactionType,
                    $paymentMethod,
                    $notes ?: null,
                    $customerName ?: null,
                    $customerPhone ?: null,
                    $creditDueDateValue,
                    $creditPrivateNoteValue,
                    $id,
                    ...$whereParams
                ]);

                if ($paymentMethod === 'credit') {
                    $hasCreditMarker = (bool) $db->fetch(
                        "SELECT 1 FROM credit_payments WHERE invoice_id = ? AND LOWER(COALESCE(payment_method, '')) = 'credit' LIMIT 1",
                        array_merge([$id], [])
                    );
                    if (!$hasCreditMarker) {
                        $connection->prepare("
                            INSERT INTO credit_payments (invoice_id, amount, payment_method, note, next_due_date, private_message, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ")->execute([
                            $id,
                            0,
                            'credit',
                            'Credit updated from Edit Sale',
                            $creditDueDateValue,
                            $creditPrivateNoteValue,
                            Auth::id()
                        ]);
                    }

                    if ($status === 'unpaid' && $newPaidAmount > 0) {
                        $existingPartial = $db->fetch(
                            "SELECT id FROM credit_payments WHERE invoice_id = ? AND note = 'Initial payment updated from Edit Sale' LIMIT 1",
                            [$id]
                        );
                        if (!$existingPartial) {
                            $connection->prepare("
                                INSERT INTO credit_payments (invoice_id, amount, payment_method, note, next_due_date, private_message, created_by)
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ")->execute([
                                $id,
                                $newPaidAmount,
                                $creditPartialPaymentMethod,
                                'Initial payment updated from Edit Sale',
                                $newBalanceDue > 0 ? $creditDueDateValue : null,
                                null,
                                Auth::id()
                            ]);
                        }
                    }
                }

                $updatedInvoice = $db->fetch("SELECT * FROM invoices WHERE id = ? AND $where", array_merge([$id], $whereParams));
                $this->applyInvoiceImpact($connection, $updatedInvoice, $newItems, $invoice);
                $connection->prepare("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)")
                    ->execute([Tenant::currentStoreId(), Auth::id(), 'Sale', "Updated sale " . $invoice['custom_id']]);
                $connection->commit();
                $this->flash('success', 'Sale updated successfully.');
                if (!empty($_POST['print_after_update'])) {
                    $this->redirect('pos/receipt/' . $id);
                }
            } catch (Exception $e) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                $this->flash('error', 'Sale update failed. Please review data and try again.');
            }

            $this->redirect('sales/edit/' . $id);
        }

        $items = $this->attachProductImages($db->fetchAll("SELECT * FROM invoice_items WHERE invoice_id = ? AND $where", array_merge([$id], $whereParams)));
        $products = $db->fetchAll("SELECT id, name, COALESCE(model_number, '') AS model_number, price FROM products WHERE $where ORDER BY name ASC", $whereParams);
        $this->view('../../modules/sales/views/edit', [
            'pageTitle' => 'Edit Sale',
            'activeMenu' => 'sales',
            'invoice' => $invoice,
            'items' => $items,
            'products' => $products
        ]);
    }

    public function delete($id) {
        $this->requirePost();
        $this->verifyCsrfOrAbort();
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $invoice = $db->fetch("SELECT * FROM invoices WHERE id = ? AND $where", array_merge([$id], $whereParams));
        if (!$invoice) {
            $this->redirect('sales/index');
        }
        if (!Auth::isAdmin() && (int) ($invoice['user_id'] ?? 0) !== (int) Auth::id()) {
            $this->flash('error', 'You can only delete your own sales.');
            $this->redirect('sales/index');
        }

        $items = $db->fetchAll("SELECT * FROM invoice_items WHERE invoice_id = ? AND $where", array_merge([$id], $whereParams));
        $connection = $db->getConnection();

        try {
            $connection->beginTransaction();
            $this->reverseInvoiceImpact($connection, $invoice, $items);
            $connection->prepare("DELETE FROM invoices WHERE id = ? AND store_id = ?")->execute([$id, Tenant::currentStoreId()]);
            $connection->prepare("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)")
                ->execute([Tenant::currentStoreId(), Auth::id(), 'Sale', "Deleted sale " . $invoice['custom_id']]);
            $connection->commit();
            $this->flash('success', 'Sale deleted successfully.');
        } catch (Exception $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            $this->flash('error', 'Sale could not be deleted.');
        }

        $this->redirect('sales/index');
    }

    private function sanitizeTransactionType($type) {
        $allowed = ['Sale', 'Exchange', 'Return', 'Refund'];
        return in_array($type, $allowed, true) ? $type : 'Sale';
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

    private function sanitizeSaleStatus($status) {
        $allowed = ['paid', 'unpaid', 'draft'];
        $status = strtolower(trim((string) $status));
        return in_array($status, $allowed, true) ? $status : 'paid';
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

    private function sanitizeDateTimeInput($value) {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function reverseInvoiceImpact($connection, $invoice, $items) {
        if (($invoice['status'] ?? '') !== 'paid') {
            return;
        }
        $storeId = (int) ($invoice['store_id'] ?? Tenant::currentStoreId());

        foreach ($items as $item) {
            if (empty($item['product_id'])) {
                continue;
            }
            if (in_array($invoice['transaction_type'], ['Sale', 'Exchange'], true)) {
                $connection->prepare("UPDATE products SET stock = stock + ? WHERE id = ? AND store_id = ?")->execute([(float) $item['quantity'], (int) $item['product_id'], $storeId]);
            } elseif (in_array($invoice['transaction_type'], ['Return', 'Refund'], true)) {
                $connection->prepare("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ? AND store_id = ?")->execute([(float) $item['quantity'], (int) $item['product_id'], $storeId]);
            }
        }

        if (!empty($invoice['customer_id'])) {
            if (in_array($invoice['transaction_type'], ['Sale', 'Exchange'], true)) {
                $connection->prepare("UPDATE customers SET total_spending = GREATEST(total_spending - ?, 0) WHERE id = ? AND store_id = ?")->execute([(float) $invoice['total'], (int) $invoice['customer_id'], $storeId]);
            } elseif (in_array($invoice['transaction_type'], ['Return', 'Refund'], true)) {
                $connection->prepare("UPDATE customers SET total_spending = total_spending + ? WHERE id = ? AND store_id = ?")->execute([(float) $invoice['total'], (int) $invoice['customer_id'], $storeId]);
            }
        }
    }

    private function applyInvoiceImpact($connection, $invoice, $items, $previousInvoice = null) {
        if (($invoice['status'] ?? '') !== 'paid') {
            return;
        }
        $storeId = (int) ($invoice['store_id'] ?? Tenant::currentStoreId());

        if ($previousInvoice && !$this->shouldApplyEditedInvoiceImpact($previousInvoice, $invoice)) {
            return;
        }

        foreach ($items as $item) {
            if (empty($item['product_id'])) {
                continue;
            }
            if (in_array($invoice['transaction_type'], ['Sale', 'Exchange'], true)) {
                $connection->prepare("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ? AND store_id = ?")->execute([(float) $item['quantity'], (int) $item['product_id'], $storeId]);
            } elseif (in_array($invoice['transaction_type'], ['Return', 'Refund'], true)) {
                $connection->prepare("UPDATE products SET stock = stock + ? WHERE id = ? AND store_id = ?")->execute([(float) $item['quantity'], (int) $item['product_id'], $storeId]);
            }
        }

        if (!empty($invoice['customer_id'])) {
            if (in_array($invoice['transaction_type'], ['Sale', 'Exchange'], true)) {
                $connection->prepare("UPDATE customers SET total_spending = total_spending + ? WHERE id = ? AND store_id = ?")->execute([(float) $invoice['total'], (int) $invoice['customer_id'], $storeId]);
            } elseif (in_array($invoice['transaction_type'], ['Return', 'Refund'], true)) {
                $connection->prepare("UPDATE customers SET total_spending = GREATEST(total_spending - ?, 0) WHERE id = ? AND store_id = ?")->execute([(float) $invoice['total'], (int) $invoice['customer_id'], $storeId]);
            }
        }
    }

    private function shouldApplyEditedInvoiceImpact($previousInvoice, $updatedInvoice) {
        return $this->transactionGroup($previousInvoice['transaction_type'] ?? 'Sale') === $this->transactionGroup($updatedInvoice['transaction_type'] ?? 'Sale');
    }

    private function transactionGroup($type) {
        return in_array($type, ['Return', 'Refund'], true) ? 'return' : 'sale';
    }

    private function applyItemRemovals($connection, $invoiceId, $oldItems, $removeItems, $removeQuantities) {
        $storeId = (int) Tenant::currentStoreId();
        $removeLookup = array_fill_keys($removeItems, true);

        foreach ($oldItems as $item) {
            $itemId = (int) $item['id'];
            $currentQty = max(0.001, (float) $item['quantity']);
            $requestedQty = max(0, (float) ($removeQuantities[$itemId] ?? 0));

            if (isset($removeLookup[$itemId]) && $requestedQty === 0) {
                $requestedQty = $currentQty;
            }

            if ($requestedQty <= 0 && !isset($removeLookup[$itemId])) {
                continue;
            }

            if ($requestedQty <= 0) {
                $requestedQty = $currentQty;
            }

            if ($requestedQty >= $currentQty) {
                $connection->prepare("DELETE FROM invoice_items WHERE invoice_id = ? AND id = ? AND store_id = ?")->execute([$invoiceId, $itemId, $storeId]);
                continue;
            }

            $remainingQty = $currentQty - $requestedQty;
            $unitDiscount = ((float) $item['discount']) / $currentQty;
            $newDiscount = round($unitDiscount * $remainingQty, 2);
            $newTotal = round((((float) $item['price']) * $remainingQty) - $newDiscount, 2);

            $connection->prepare("
                UPDATE invoice_items
                SET quantity = ?, discount = ?, total = ?
                WHERE invoice_id = ? AND id = ? AND store_id = ?
            ")->execute([
                $remainingQty,
                $newDiscount,
                $newTotal,
                $invoiceId,
                $itemId,
                $storeId
            ]);
        }
    }

    private function applyItemAdditions($connection, $invoiceId, $saleChannel, $productLookup, $addProductIds, $addProductNames, $addProductModels, $addQuantities, $addPrices, $addDiscounts) {
        $storeId = (int) Tenant::currentStoreId();
        $maxRows = max(
            count((array) $addProductIds),
            count((array) $addProductNames),
            count((array) $addProductModels),
            count((array) $addQuantities),
            count((array) $addPrices),
            count((array) $addDiscounts)
        );

        for ($index = 0; $index < $maxRows; $index++) {
            $productId = (int) ($addProductIds[$index] ?? 0);
            $customName = trim((string) ($addProductNames[$index] ?? ''));
            $customModel = trim((string) ($addProductModels[$index] ?? ''));
            $qty = max(0, (float) ($addQuantities[$index] ?? 0));
            $price = max(0, (float) ($addPrices[$index] ?? 0));
            $discount = max(0, (float) ($addDiscounts[$index] ?? 0));

            if ($qty <= 0) {
                continue;
            }

            $finalProductId = null;
            $productName = '';
            $modelNumber = '';

            if ($productId > 0 && isset($productLookup[$productId])) {
                $source = $productLookup[$productId];
                $finalProductId = $productId;
                $productName = trim((string) ($source['name'] ?? ''));
                $modelNumber = trim((string) ($source['model_number'] ?? ''));
                if ($price <= 0) {
                    $price = max(0, (float) ($source['price'] ?? 0));
                }
            } else {
                $productName = $customName;
                $modelNumber = $customModel;
            }

            if ($productName === '' || $price <= 0) {
                continue;
            }

            $lineSubtotal = $price * $qty;
            if ($discount > $lineSubtotal) {
                $discount = $lineSubtotal;
            }
            $lineTotal = max(0, $lineSubtotal - $discount);

            $connection->prepare("
                INSERT INTO invoice_items (store_id, invoice_id, product_id, product_name, model_number, quantity, price, price_tier, discount, total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $storeId,
                $invoiceId,
                $finalProductId,
                $productName,
                $modelNumber ?: null,
                $qty,
                $price,
                $saleChannel ?: 'retail',
                $discount,
                $lineTotal
            ]);
        }
    }

}
