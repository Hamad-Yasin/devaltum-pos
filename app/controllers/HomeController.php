<?php

class HomeController extends Controller {

    public function __construct() {
        if (Auth::check() && Auth::isSuperAdmin()) {
            $baseUrl = defined('BASE_URL') ? BASE_URL : '';
            header('Location: ' . $baseUrl . '/superadmin/index');
            exit;
        }
        AuthMiddleware::handlePermission('dashboard');
    }

    public function index() {
        $db = Database::getInstance();
        [$storeWhere, $storeParams] = Tenant::storeFilter();
        [$storeWhereI, $storeParamsI] = Tenant::storeFilter('i');
        $isOwnerView = Auth::isAdmin();
        $selectedStaffId = $isOwnerView ? 0 : (int) Auth::id();

        $invoiceScope = $selectedStaffId > 0 ? ' AND user_id = ?' : '';
        $invoiceScopeParams = $selectedStaffId > 0 ? [$selectedStaffId] : [];
        $invoiceScopeI = $selectedStaffId > 0 ? ' AND i.user_id = ?' : '';
        $invoiceScopeIParams = $selectedStaffId > 0 ? [$selectedStaffId] : [];
        $expenseScope = $selectedStaffId > 0 ? ' AND created_by = ?' : '';
        $expenseScopeParams = $selectedStaffId > 0 ? [$selectedStaffId] : [];

        $filter = $_GET['date_filter'] ?? 'today';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $dateCondition = "1=1";
        $dateParams = [];

        if ($filter === 'today') {
            $dateCondition = "DATE(created_at) = ?";
            $dateParams = [date('Y-m-d')];
        } elseif ($filter === 'yesterday') {
            $dateCondition = "DATE(created_at) = ?";
            $dateParams = [date('Y-m-d', strtotime('-1 day'))];
        } elseif ($filter === 'this_week') {
            $weekStart = new DateTime();
            $weekStart->setISODate((int) date('o'), (int) date('W'));
            $dateCondition = "DATE(created_at) BETWEEN ? AND ?";
            $dateParams = [$weekStart->format('Y-m-d'), date('Y-m-d')];
        } elseif ($filter === 'this_month') {
            $dateCondition = "DATE(created_at) BETWEEN ? AND ?";
            $dateParams = [date('Y-m-01'), date('Y-m-t')];
        } elseif ($filter === 'custom' && $startDate && $endDate) {
            $dateCondition = "DATE(created_at) BETWEEN ? AND ?";
            $dateParams = [$startDate, $endDate];
        }

        $dateConditionExp = str_replace('created_at', 'expense_date', $dateCondition);
        $dateParamsExp = $dateParams;

        $getSum = function($sql, $p = []) use ($db) {
            $row = $db->fetch($sql, $p);
            return $row ? (float) ($row['sum'] ?? 0) : 0.0;
        };

        $getCount = function($sql, $p = []) use ($db) {
            $row = $db->fetch($sql, $p);
            return $row ? (int) ($row['count'] ?? 0) : 0;
        };

        $creditCondition = "
            transaction_type IN ('Sale','Exchange')
            AND COALESCE(status, 'paid') <> 'draft'
            AND (
                LOWER(COALESCE(payment_method, '')) = 'credit'
                OR credit_due_date IS NOT NULL
                OR COALESCE(balance_due, 0) > 0
                OR COALESCE(is_credit_settled, 0) = 0
            )
        ";
        $creditTotals = $db->fetch("
            SELECT
                SUM(COALESCE(total, 0)) AS credit_total,
                SUM(GREATEST((COALESCE(total, 0) - COALESCE(paid_amount, 0)), COALESCE(balance_due, 0), 0)) AS credit_remaining
            FROM invoices
            WHERE $storeWhere $invoiceScope AND $creditCondition AND $dateCondition
        ", array_merge($storeParams, $invoiceScopeParams, $dateParams)) ?: [];
        $creditTotal = (float) ($creditTotals['credit_total'] ?? 0);
        $creditRemaining = (float) ($creditTotals['credit_remaining'] ?? 0);
        $creditPaid = max(0, $creditTotal - $creditRemaining);
        $dateConditionCreditInvoice = str_replace('created_at', 'i.created_at', $dateCondition);

        // 1. Consolidated Invoices Metrics
        $invoiceMetrics = $db->fetch("
            SELECT 
                COUNT(CASE WHEN transaction_type IN ('Sale','Exchange') THEN id END) AS total_orders,
                SUM(CASE WHEN transaction_type IN ('Sale','Exchange') THEN COALESCE(total, 0) ELSE 0 END) AS total_sales,
                SUM(CASE WHEN transaction_type IN ('Return','Refund') THEN COALESCE(total, 0) ELSE 0 END) AS total_returns,
                SUM(CASE WHEN transaction_type IN ('Sale','Exchange') AND LOWER(COALESCE(payment_method, '')) = 'cash' THEN COALESCE(total, 0) ELSE 0 END) AS cash_sales_direct,
                SUM(CASE WHEN transaction_type IN ('Sale','Exchange') AND LOWER(COALESCE(payment_method, '')) = 'online' THEN COALESCE(total, 0) ELSE 0 END) AS online_sales_direct,
                SUM(CASE WHEN transaction_type IN ('Sale','Exchange') AND LOWER(COALESCE(payment_method, '')) = 'credit' THEN COALESCE(total, 0) ELSE 0 END) AS credit_sales,
                SUM(CASE WHEN transaction_type IN ('Sale','Exchange') AND COALESCE(sale_channel, 'retail') = 'retail' THEN COALESCE(total, 0) ELSE 0 END) AS retail_sales,
                SUM(CASE WHEN transaction_type IN ('Sale','Exchange') AND COALESCE(sale_channel, 'retail') = 'wholesale' THEN COALESCE(total, 0) ELSE 0 END) AS wholesale_sales
            FROM invoices
            WHERE $storeWhere $invoiceScope AND COALESCE(status, 'paid') <> 'draft' AND $dateCondition
        ", array_merge($storeParams, $invoiceScopeParams, $dateParams)) ?: [];

        // 2. Consolidated Credit Payments
        $creditCollections = $db->fetch("
            SELECT 
                SUM(CASE WHEN LOWER(COALESCE(cp.payment_method, '')) = 'cash' THEN COALESCE(cp.amount, 0) ELSE 0 END) AS cash_collection,
                SUM(CASE WHEN LOWER(COALESCE(cp.payment_method, '')) = 'online' THEN COALESCE(cp.amount, 0) ELSE 0 END) AS online_collection
            FROM credit_payments cp
            INNER JOIN invoices i ON i.id = cp.invoice_id
            WHERE
                $storeWhereI
                AND
                i.transaction_type IN ('Sale','Exchange')
                $invoiceScopeI
                AND COALESCE(i.status, 'paid') <> 'draft'
                AND COALESCE(cp.amount, 0) > 0
                AND $dateConditionCreditInvoice
        ", array_merge($storeParamsI, $invoiceScopeIParams, $dateParams)) ?: [];

        $cashSales = (float) ($invoiceMetrics['cash_sales_direct'] ?? 0) + (float) ($creditCollections['cash_collection'] ?? 0);
        $onlineSales = (float) ($invoiceMetrics['online_sales_direct'] ?? 0) + (float) ($creditCollections['online_collection'] ?? 0);

        // 3. Consolidated Expenses Metrics
        $expenseTotals = $db->fetch("
            SELECT 
                SUM(COALESCE(amount, 0)) AS total_expenses,
                SUM(CASE WHEN LOWER(COALESCE(payment_method, '')) = 'cash' THEN COALESCE(amount, 0) ELSE 0 END) AS cash_expenses,
                SUM(CASE WHEN LOWER(COALESCE(payment_method, '')) = 'online' THEN COALESCE(amount, 0) ELSE 0 END) AS online_expenses
            FROM expenses
            WHERE $storeWhere $expenseScope AND $dateConditionExp
        ", array_merge($storeParams, $expenseScopeParams, $dateParamsExp)) ?: [];

        $stats = [
            'total_sales' => (float) ($invoiceMetrics['total_sales'] ?? 0),
            'total_returns' => (float) ($invoiceMetrics['total_returns'] ?? 0),
            'total_orders' => (int) ($invoiceMetrics['total_orders'] ?? 0),
            'total_customers' => $getCount("SELECT COUNT(id) as count FROM customers WHERE $storeWhere", $storeParams),
            'today_sales' => $getSum("SELECT SUM(total) as sum FROM invoices WHERE $storeWhere $invoiceScope AND status = 'paid' AND transaction_type IN ('Sale','Exchange') AND DATE(created_at) = ?", array_merge($storeParams, $invoiceScopeParams, [date('Y-m-d')])),
            'cash_sales' => $cashSales,
            'online_sales' => $onlineSales,
            'credit_sales' => (float) ($invoiceMetrics['credit_sales'] ?? 0),
            'retail_sales' => (float) ($invoiceMetrics['retail_sales'] ?? 0),
            'wholesale_sales' => (float) ($invoiceMetrics['wholesale_sales'] ?? 0),
            'total_inventory_products' => $getCount("SELECT COUNT(id) as count FROM products WHERE $storeWhere", $storeParams),
            'total_stock_units' => $getSum("SELECT SUM(stock) as sum FROM products WHERE $storeWhere", $storeParams),
            'total_inventory_value' => $getSum("SELECT SUM(stock * cost) as sum FROM products WHERE $storeWhere", $storeParams),
            'total_expenses' => (float) ($expenseTotals['total_expenses'] ?? 0),
            'cash_expenses' => (float) ($expenseTotals['cash_expenses'] ?? 0),
            'online_expenses' => (float) ($expenseTotals['online_expenses'] ?? 0),
            'last_week_sales' => $getSum("SELECT SUM(total) as sum FROM invoices WHERE $storeWhere $invoiceScope AND status = 'paid' AND transaction_type IN ('Sale','Exchange') AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)", array_merge($storeParams, $invoiceScopeParams)),
            'last_month_sales' => $getSum("SELECT SUM(total) as sum FROM invoices WHERE $storeWhere $invoiceScope AND status = 'paid' AND transaction_type IN ('Sale','Exchange') AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", array_merge($storeParams, $invoiceScopeParams)),
            'remaining_amount' => $creditRemaining,
            'credit_remaining' => $creditRemaining,
            'credit_paid' => $creditPaid
        ];

        // 3b. Live Financial Drawer & Digital Accounts Metrics
        $currentStoreId = Tenant::currentStoreId();
        $cashDrawerRow = $db->fetch("SELECT SUM(current_balance) AS bal FROM financial_accounts WHERE store_id = ? AND (is_default = 1 OR account_type = 'cash') AND status = 'active'", [$currentStoreId]);
        $onlineAccRow = $db->fetch("SELECT SUM(current_balance) AS bal FROM financial_accounts WHERE store_id = ? AND account_type IN ('bank', 'wallet', 'other') AND is_default = 0 AND status = 'active'", [$currentStoreId]);
        $stats['cash_in_drawer'] = (float)($cashDrawerRow['bal'] ?? 0);
        $stats['digital_accounts'] = (float)($onlineAccRow['bal'] ?? 0);

        $stats['net_amount'] = (float) $stats['total_sales'] - (float) $stats['total_expenses'] - (float) $stats['total_returns'] - (float) $stats['credit_remaining'];

        // 4. Grouped 7-Day Chart Queries (replaces 28 queries inside loop)
        $sevenDaysAgo = date('Y-m-d 00:00:00', strtotime('-6 days'));
        $invoiceChartData = $db->fetchAll("
            SELECT 
                DATE(created_at) AS event_date,
                transaction_type,
                SUM(COALESCE(total, 0)) AS sum_total,
                SUM(GREATEST((COALESCE(total, 0) - COALESCE(paid_amount, 0)), COALESCE(balance_due, 0), 0)) AS sum_credit_remaining
            FROM invoices
            WHERE $storeWhere $invoiceScope AND created_at >= ? AND COALESCE(status, 'paid') <> 'draft'
            GROUP BY DATE(created_at), transaction_type
        ", array_merge($storeParams, $invoiceScopeParams, [$sevenDaysAgo]));

        $invoiceMap = [];
        foreach ($invoiceChartData as $row) {
            $dateKey = $row['event_date'];
            $typeKey = $row['transaction_type'];
            if (!isset($invoiceMap[$dateKey])) {
                $invoiceMap[$dateKey] = [];
            }
            $invoiceMap[$dateKey][$typeKey] = [
                'total' => (float) $row['sum_total'],
                'credit_remaining' => (float) $row['sum_credit_remaining']
            ];
        }

        $expenseChartData = $db->fetchAll("
            SELECT 
                DATE(expense_date) AS event_date,
                SUM(COALESCE(amount, 0)) AS sum_total
            FROM expenses
            WHERE $storeWhere $expenseScope AND expense_date >= ?
            GROUP BY DATE(expense_date)
        ", array_merge($storeParams, $expenseScopeParams, [date('Y-m-d', strtotime('-6 days'))]));

        $expenseMap = [];
        foreach ($expenseChartData as $row) {
            $expenseMap[$row['event_date']] = (float) $row['sum_total'];
        }

        $dailySales = [];
        $dailyExpenses = [];
        $dailyNet = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            
            $saleTotal = 0.0;
            $creditRemainingTotal = 0.0;
            if (isset($invoiceMap[$date]['Sale'])) {
                $saleTotal += $invoiceMap[$date]['Sale']['total'];
                $creditRemainingTotal += $invoiceMap[$date]['Sale']['credit_remaining'];
            }
            if (isset($invoiceMap[$date]['Exchange'])) {
                $saleTotal += $invoiceMap[$date]['Exchange']['total'];
                $creditRemainingTotal += $invoiceMap[$date]['Exchange']['credit_remaining'];
            }

            $returnTotal = 0.0;
            if (isset($invoiceMap[$date]['Return'])) {
                $returnTotal += $invoiceMap[$date]['Return']['total'];
            }
            if (isset($invoiceMap[$date]['Refund'])) {
                $returnTotal += $invoiceMap[$date]['Refund']['total'];
            }

            $expenseTotal = $expenseMap[$date] ?? 0.0;

            $dailySales[] = [
                'label' => date('D', strtotime($date)),
                'total' => $saleTotal
            ];
            $dailyExpenses[] = [
                'label' => date('D', strtotime($date)),
                'total' => $expenseTotal
            ];
            $dailyNet[] = [
                'label' => date('D', strtotime($date)),
                'total' => $saleTotal - $expenseTotal - $returnTotal - $creditRemainingTotal
            ];
        }

        $paymentTotal = max(1, (float) $stats['cash_sales'] + (float) $stats['online_sales'] + (float) $stats['credit_sales']);
        $paymentMix = [
            ['label' => 'Cash', 'amount' => (float) $stats['cash_sales'], 'percent' => ((float) $stats['cash_sales'] / $paymentTotal) * 100, 'color' => '#0f6dff'],
            ['label' => 'Online', 'amount' => (float) $stats['online_sales'], 'percent' => ((float) $stats['online_sales'] / $paymentTotal) * 100, 'color' => '#10b981'],
            ['label' => 'Credit', 'amount' => (float) $stats['credit_sales'], 'percent' => ((float) $stats['credit_sales'] / $paymentTotal) * 100, 'color' => '#f59e0b'],
        ];

        $dateConditionI = str_replace('created_at', 'i.created_at', $dateCondition);
        $dateParamsI = $dateParams;

        $topProducts = $db->fetchAll("
            SELECT ii.product_name, COALESCE(ii.model_number, '') AS model_number, SUM(ii.quantity) AS qty_sold, SUM(ii.total) AS total_amount
            FROM invoice_items ii
            JOIN invoices i ON i.id = ii.invoice_id
            WHERE $storeWhereI $invoiceScopeI AND i.status = 'paid' AND i.transaction_type IN ('Sale','Exchange') AND $dateConditionI
            GROUP BY ii.product_name, ii.model_number
            ORDER BY qty_sold DESC
            LIMIT 10
        ", array_merge($storeParamsI, $invoiceScopeIParams, $dateParamsI));

        $this->view('home/index', [
            'pageTitle' => 'Dashboard - DevAltum POS',
            'activeMenu' => 'dashboard',
            'stats' => $stats,
            'daily_sales' => $dailySales,
            'daily_expenses' => $dailyExpenses,
            'daily_net' => $dailyNet,
            'payment_mix' => $paymentMix,
            'top_products' => $topProducts,
            'low_stock_products' => App::lowStockProducts(12),
            'isOwnerView' => $isOwnerView,
            'dateFilter' => $filter,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
}
