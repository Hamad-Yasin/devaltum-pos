<?php

class SearchController extends Controller {

    public function __construct() {
        AuthMiddleware::handle();
    }

    public function index() {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            $this->jsonResponse(['results' => []]);
        }

        $db = Database::getInstance();
        if (Auth::isSuperAdmin()) {
            $results = [];

            $stores = $db->fetchAll(
                "SELECT s.id, s.store_name, s.owner_name, s.email, s.phone, s.status, s.parent_store_id, s.branch_code, COALESCE(p.plan_name, s.plan_type) AS plan_name
                 FROM stores s
                 LEFT JOIN subscription_plans p ON p.id = s.current_plan_id
                 WHERE s.store_name LIKE ? OR s.owner_name LIKE ? OR s.email LIKE ? OR s.phone LIKE ? OR s.branch_code LIKE ?
                 ORDER BY s.id DESC
                 LIMIT 8",
                ["%$q%", "%$q%", "%$q%", "%$q%", "%$q%"]
            );
            foreach ($stores as $store) {
                $isBranch = (int) ($store['parent_store_id'] ?? 0) > 0;
                $results[] = [
                    'type' => $isBranch ? 'Branch' : 'Store',
                    'title' => (string) $store['store_name'],
                    'subtitle' => trim(($isBranch ? (($store['branch_code'] ?? '') ?: 'Branch Account') : 'Main Account') . ' | ' . ucfirst((string) ($store['status'] ?? 'inactive')) . ' | ' . ucfirst((string) ($store['plan_name'] ?? 'free'))),
                    'url' => '/superadmin/storeProfile/' . (int) $store['id']
                ];
            }

            $plans = $db->fetchAll(
                "SELECT id, plan_name, monthly_price, yearly_price, status FROM subscription_plans WHERE plan_name LIKE ? OR status LIKE ? ORDER BY monthly_price ASC LIMIT 5",
                ["%$q%", "%$q%"]
            );
            foreach ($plans as $plan) {
                $results[] = [
                    'type' => 'Plan',
                    'title' => (string) $plan['plan_name'],
                    'subtitle' => App::money((float) ($plan['monthly_price'] ?? 0)) . '/mo | ' . ucfirst((string) ($plan['status'] ?? 'active')),
                    'url' => '/superadmin/plans?tab=list'
                ];
            }

            $payments = $db->fetchAll(
                "SELECT pt.id, pt.amount, pt.status, pt.paid_at, s.store_name, COALESCE(pm.method_name, pt.method) AS method_name
                 FROM payment_transactions pt
                 LEFT JOIN stores s ON s.id = pt.store_id
                 LEFT JOIN payment_methods pm ON pm.id = CAST(pt.method AS UNSIGNED)
                 WHERE s.store_name LIKE ? OR pt.status LIKE ? OR pm.method_name LIKE ? OR pt.method LIKE ?
                 ORDER BY COALESCE(pt.paid_at, pt.created_at) DESC
                 LIMIT 5",
                ["%$q%", "%$q%", "%$q%", "%$q%"]
            );
            foreach ($payments as $payment) {
                $results[] = [
                    'type' => 'Payment',
                    'title' => (string) ($payment['store_name'] ?? 'Store Payment'),
                    'subtitle' => App::money((float) ($payment['amount'] ?? 0)) . ' | ' . ucfirst((string) ($payment['status'] ?? 'pending')) . ' | ' . (string) ($payment['method_name'] ?? '-'),
                    'url' => '/superadmin/payments?tab=table'
                ];
            }

            $users = $db->fetchAll(
                "SELECT u.id, u.name, u.email, u.role, u.status, s.store_name
                 FROM users u
                 LEFT JOIN stores s ON s.id = u.store_id
                 WHERE u.role != 'super_admin' AND (u.name LIKE ? OR u.email LIKE ? OR u.role LIKE ? OR s.store_name LIKE ?)
                 ORDER BY u.id DESC
                 LIMIT 5",
                ["%$q%", "%$q%", "%$q%", "%$q%"]
            );
            foreach ($users as $user) {
                $results[] = [
                    'type' => 'User',
                    'title' => (string) ($user['name'] ?? 'User'),
                    'subtitle' => (string) ($user['email'] ?? '-') . ' | ' . ucfirst(str_replace('_', ' ', (string) ($user['role'] ?? 'staff'))),
                    'url' => '/superadmin/userAccess?tab=list'
                ];
            }

            $this->jsonResponse(['results' => array_slice($results, 0, 15)]);
        }

        [$storeWhere, $storeParams] = Tenant::storeFilter();
        [$storeWhereI, $storeParamsI] = Tenant::storeFilter('i');
        $results = [];

        $products = $db->fetchAll(
            "SELECT id, name, model_number, barcode, price FROM products WHERE $storeWhere AND (name LIKE ? OR model_number LIKE ? OR barcode LIKE ?) ORDER BY stock <= min_stock DESC, name ASC LIMIT 6",
            array_merge($storeParams, ["%$q%", "%$q%", "%$q%"])
        );
        foreach ($products as $p) {
            $results[] = [
                'type' => 'Product',
                'title' => trim($p['name'] . ' ' . ($p['model_number'] ? '(' . $p['model_number'] . ')' : '')),
                'subtitle' => App::money($p['price']),
                'url' => '/inventory/viewProfile/' . $p['id']
            ];
        }

        $customers = $db->fetchAll(
            "SELECT id, name, phone FROM customers WHERE $storeWhere AND (name LIKE ? OR phone LIKE ?) ORDER BY created_at DESC LIMIT 6",
            array_merge($storeParams, ["%$q%", "%$q%"])
        );
        foreach ($customers as $c) {
            $results[] = [
                'type' => 'Customer',
                'title' => $c['name'],
                'subtitle' => $c['phone'],
                'url' => '/customers/viewProfile/' . $c['id']
            ];
        }

        $invoices = $db->fetchAll(
            "
                SELECT
                    i.id,
                    i.custom_id,
                    i.total,
                    i.status,
                    i.transaction_type,
                    COALESCE(c.name, i.customer_name_snapshot, 'Walk-in Customer') AS customer_name,
                    GROUP_CONCAT(DISTINCT ii.product_name ORDER BY ii.product_name SEPARATOR ', ') AS product_names
                FROM invoices i
                LEFT JOIN customers c ON c.id = i.customer_id
                LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
                WHERE
                    $storeWhereI
                    AND (
                        i.custom_id LIKE ?
                        OR c.name LIKE ?
                        OR i.customer_name_snapshot LIKE ?
                        OR i.customer_phone_snapshot LIKE ?
                        OR ii.product_name LIKE ?
                        OR ii.model_number LIKE ?
                    )
                GROUP BY i.id
                ORDER BY i.created_at DESC
                LIMIT 8
            ",
            array_merge($storeParamsI, ["%$q%", "%$q%", "%$q%", "%$q%", "%$q%", "%$q%"])
        );
        foreach ($invoices as $i) {
            $results[] = [
                'type' => 'Sale',
                'title' => $i['custom_id'] . ' - ' . $i['transaction_type'],
                'subtitle' => trim($i['customer_name'] . ' | ' . ($i['product_names'] ?: App::money($i['total']) . ' (' . strtoupper($i['status']) . ')')),
                'url' => '/sales/action/' . $i['id']
            ];
        }

        $this->jsonResponse(['results' => $results]);
    }

    public function barcode() {
        $rawQ = trim((string) ($_GET['q'] ?? ''));
        $q = App::normalizeBarcode($rawQ);
        if ($q === '' && $rawQ === '') {
            $this->jsonResponse(['found' => false, 'message' => 'Barcode is required.'], 200);
        }

        $db = Database::getInstance();
        [$storeWhere, $storeParams] = Tenant::storeFilter('p');
        $numericId = ctype_digit($rawQ) ? (int) $rawQ : 0;

        $matches = $db->fetchAll(
            "
                SELECT
                    p.id, p.parent_product_id, p.name, p.model_number, p.barcode, p.price, p.wholesale_price,
                    COALESCE(NULLIF(p.cost, 0), parent.cost, 0) AS cost,
                    p.stock, p.min_stock,
                    p.unit_value, p.unit_type, p.variant_label,
                    COALESCE(psc.quantity, 1) AS scan_quantity,
                    COALESCE(psc.scan_type, 'single') AS scan_type,
                    COALESCE(psc.label, '') AS scan_label,
                    COALESCE(psc.code, p.barcode, p.model_number, '') AS matched_code
                FROM products p
                LEFT JOIN products parent
                    ON parent.id = p.parent_product_id
                    AND parent.store_id = p.store_id
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
                LIMIT 20
            ",
            array_merge(
                [$q, $rawQ],
                $storeParams,
                [$q, $rawQ, $q, $rawQ, $q, $rawQ, $rawQ, $numericId, $numericId]
            )
        );

        if (!$matches) {
            $this->jsonResponse([
                'found' => false, 
                'message' => 'Product not found for barcode: ' . ($rawQ ?: $q)
            ], 200);
        }

        $products = array_map(function ($product) {
            $qty = max(1, (int) ($product['scan_quantity'] ?? 1));
            return [
                'id' => (int) $product['id'],
                'parent_product_id' => (int) ($product['parent_product_id'] ?? 0),
                'name' => $product['name'],
                'model_number' => $product['model_number'] ?? '',
                'barcode' => $product['barcode'] ?? '',
                'price' => (float) ($product['price'] ?? 0),
                'cost' => (float) ($product['cost'] ?? 0),
                'cost_price' => (float) ($product['cost'] ?? 0),
                'wholesale_price' => (float) ($product['wholesale_price'] ?? $product['price'] ?? 0),
                'stock' => (int) ($product['stock'] ?? 0),
                'min_stock' => (int) ($product['min_stock'] ?? 0),
                'unit_value' => (float) ($product['unit_value'] ?? 1),
                'unit_type' => $product['unit_type'] ?? 'pcs',
                'variant_label' => $product['variant_label'] ?? '',
                'scan_quantity' => $qty,
                'scan_type' => $product['scan_type'] ?? 'single',
                'scan_label' => $product['scan_label'] ?? '',
                'matched_code' => $product['matched_code'] ?? ($product['barcode'] ?? ''),
            ];
        }, $matches);

        $hasChildVariants = count(array_filter($products, function ($product) {
            return (int) ($product['parent_product_id'] ?? 0) > 0;
        })) > 0;
        if ($hasChildVariants) {
            $products = array_values(array_filter($products, function ($product) {
                return (int) ($product['parent_product_id'] ?? 0) > 0;
            }));
        }

        if (count($products) > 1) {
            $this->jsonResponse([
                'found' => true,
                'requires_choice' => true,
                'products' => $products,
                'message' => 'Multiple variants matched this barcode.'
            ], 200);
        }

        $product = $products[0];
        $this->jsonResponse([
            'found' => true,
            'requires_choice' => false,
            'product' => $product,
            'url' => '/pos/index&add=' . (int) $product['id']
        ], 200);
    }
}
