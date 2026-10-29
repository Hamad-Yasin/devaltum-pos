<?php

class ReportsController extends Controller {

    public function __construct() {
        AuthMiddleware::handlePermission('reports');
    }

    public function index() {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        [$whereI, $whereIParams] = Tenant::storeFilter('i');
        [$whereA, $whereAParams] = Tenant::storeFilter('a');

        $filter = $_GET['date_filter'] ?? 'today';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $dateCondition = "1=1";
        $dateParams = [];

        if ($filter === 'today') {
            $dateCondition = "DATE(created_at) = CURRENT_DATE()";
        } elseif ($filter === 'yesterday') {
            $dateCondition = "DATE(created_at) = DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)";
        } elseif ($filter === 'this_week') {
            $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURRENT_DATE(), 1)";
        } elseif ($filter === 'this_month') {
            $dateCondition = "MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        } elseif ($filter === 'custom' && $startDate && $endDate) {
            $dateCondition = "DATE(created_at) BETWEEN ? AND ?";
            $dateParams = [$startDate, $endDate];
        }

        $dateConditionI = str_replace('created_at', 'i.created_at', $dateCondition);
        $dateParamsI = $dateParams;

        $dateConditionExp = str_replace('created_at', 'expense_date', $dateCondition);
        $dateParamsExp = $dateParams;

        $getSum = function($sql, $p = []) use ($db) {
            $row = $db->fetch($sql, $p);
            return $row ? (float) ($row['sum'] ?? $row['cnt'] ?? $row['rev'] ?? 0) : 0.0;
        };
        
        $getProfit = function($sql, $p = []) use ($db) {
            $row = $db->fetch($sql, $p);
            return $row ? [
                'rev' => (float)($row['rev'] ?? 0),
                'cost' => (float)($row['cost'] ?? 0)
            ] : ['rev'=>0, 'cost'=>0];
        };

        $dailySales = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dailyTotal = $getSum("SELECT SUM(total) as sum FROM invoices WHERE $where AND status = 'paid' AND transaction_type IN ('Sale','Exchange') AND DATE(created_at) = ?", array_merge($whereParams, [$date]));
            $dailySales[] = [
                'date' => date('M d', strtotime($date)),
                'total' => $dailyTotal
            ];
        }

        $topProducts = $db->fetchAll("
            SELECT ii.product_name, COALESCE(ii.model_number, '') AS model_number, SUM(ii.quantity) as qty_sold, SUM(ii.total) as revenue
            FROM invoice_items ii
            JOIN invoices i ON ii.invoice_id = i.id
            WHERE $whereI AND i.status = 'paid' AND i.transaction_type IN ('Sale','Exchange') AND $dateConditionI
            GROUP BY ii.product_name, ii.model_number
            ORDER BY qty_sold DESC
            LIMIT 5
        ", array_merge($whereIParams, $dateParamsI));

        $lowProducts = $db->fetchAll("
            SELECT ii.product_name, COALESCE(ii.model_number, '') AS model_number, SUM(ii.quantity) as qty_sold, SUM(ii.total) as revenue
            FROM invoice_items ii
            JOIN invoices i ON ii.invoice_id = i.id
            WHERE $whereI AND i.status = 'paid' AND i.transaction_type IN ('Sale','Exchange') AND $dateConditionI
            GROUP BY ii.product_name, ii.model_number
            ORDER BY qty_sold ASC
            LIMIT 5
        ", array_merge($whereIParams, $dateParamsI));

        $monthlySales = $getSum("SELECT SUM(total) as sum FROM invoices WHERE $where AND status = 'paid' AND transaction_type IN ('Sale','Exchange') AND $dateCondition", array_merge($whereParams, $dateParams));
        $profitData = $getProfit("
            SELECT SUM(ii.total) as rev, SUM(ii.quantity * p.cost) as cost
            FROM invoice_items ii
            JOIN invoices i ON ii.invoice_id = i.id
            LEFT JOIN products p ON ii.product_id = p.id
            WHERE $whereI AND i.status = 'paid' AND i.transaction_type IN ('Sale','Exchange') AND $dateConditionI
        ", array_merge($whereIParams, $dateParamsI));
        
        $monthlyProfit = $profitData['rev'] - $profitData['cost'];
        $itemsAdded = $getSum("SELECT COUNT(*) as sum FROM products WHERE $where AND $dateCondition", array_merge($whereParams, $dateParams));
        
        $stockDataObj = $db->fetch("SELECT SUM(stock) as total_items, SUM(stock * cost) as total_value FROM products WHERE $where", $whereParams);
        
        $returns = $getSum("SELECT SUM(total) as sum FROM invoices WHERE $where AND transaction_type IN ('Return', 'Refund') AND $dateCondition", array_merge($whereParams, $dateParams));
        $paymentBreakdown = [
            'cash' => $getSum("SELECT SUM(total) as sum FROM invoices WHERE $where AND status = 'paid' AND payment_method = 'cash' AND $dateCondition", array_merge($whereParams, $dateParams)),
            'online' => $getSum("SELECT SUM(total) as sum FROM invoices WHERE $where AND status = 'paid' AND payment_method = 'online' AND $dateCondition", array_merge($whereParams, $dateParams)),
            'credit' => $getSum("SELECT SUM(total) as sum FROM invoices WHERE $where AND payment_method = 'credit' AND $dateCondition", array_merge($whereParams, $dateParams))
        ];
        $expenseBreakdown = [
            'cash' => $getSum("SELECT SUM(amount) as sum FROM expenses WHERE $where AND payment_method = 'cash' AND $dateConditionExp", array_merge($whereParams, $dateParamsExp)),
            'online' => $getSum("SELECT SUM(amount) as sum FROM expenses WHERE $where AND payment_method = 'online' AND $dateConditionExp", array_merge($whereParams, $dateParamsExp)),
            'total' => $getSum("SELECT SUM(amount) as sum FROM expenses WHERE $where AND $dateConditionExp", array_merge($whereParams, $dateParamsExp))
        ];
        $inventoryTotals = [
            'products' => (int) $getSum("SELECT COUNT(*) as sum FROM products WHERE $where", $whereParams),
            'units' => (int) ($stockDataObj ? ($stockDataObj['total_items'] ?? 0) : 0),
            'value' => (float) ($stockDataObj ? ($stockDataObj['total_value'] ?? 0) : 0)
        ];
        $lowStockCount = (int) $getSum("SELECT COUNT(*) as sum FROM products WHERE $where AND stock <= min_stock", $whereParams);
        $lowStockProducts = App::lowStockProducts(12);
        $recentActivity = $db->fetchAll("
            SELECT a.*, u.name as user_name
            FROM activity_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE $whereA
            ORDER BY a.created_at DESC
            LIMIT 12
        ", $whereAParams);

        $this->view('../../modules/reports/views/index', [
            'pageTitle' => 'Reports & Analytics',
            'activeMenu' => 'reports',
            'dailySales' => $dailySales,
            'topProducts' => $topProducts,
            'lowProducts' => $lowProducts,
            'monthly_sales' => $monthlySales,
            'monthly_profit' => $monthlyProfit,
            'itemsAdded' => $itemsAdded,
            'stockData' => $stockDataObj,
            'paymentBreakdown' => $paymentBreakdown,
            'expenseBreakdown' => $expenseBreakdown,
            'inventoryTotals' => $inventoryTotals,
            'lowStockCount' => $lowStockCount,
            'lowStockProducts' => $lowStockProducts,
            'returns' => $returns,
            'recentActivity' => $recentActivity,
            'dateFilter' => $filter,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    public function export() {
        $db = Database::getInstance();
        [$whereI, $whereIParams] = Tenant::storeFilter('i');

        $filter = $_GET['date_filter'] ?? 'today';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $dateCondition = "1=1";
        $dateParams = [];

        if ($filter === 'today') {
            $dateCondition = "DATE(i.created_at) = CURRENT_DATE()";
        } elseif ($filter === 'yesterday') {
            $dateCondition = "DATE(i.created_at) = DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)";
        } elseif ($filter === 'this_week') {
            $dateCondition = "YEARWEEK(i.created_at, 1) = YEARWEEK(CURRENT_DATE(), 1)";
        } elseif ($filter === 'this_month') {
            $dateCondition = "MONTH(i.created_at) = MONTH(CURRENT_DATE()) AND YEAR(i.created_at) = YEAR(CURRENT_DATE())";
        } elseif ($filter === 'custom' && $startDate && $endDate) {
            $dateCondition = "DATE(i.created_at) BETWEEN ? AND ?";
            $dateParams = [$startDate, $endDate];
        }

        $sales = $db->fetchAll("
            SELECT i.custom_id as invoice, COALESCE(c.name, i.customer_name_snapshot, 'Walk-in Customer') as customer, i.transaction_type, i.subtotal, i.discount, i.total, i.status, i.created_at
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            WHERE $whereI AND $dateCondition
            ORDER BY i.created_at DESC
        ", array_merge($whereIParams, $dateParams));

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sales_export_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Invoice ID', 'Customer', 'Type', 'Subtotal', 'Discount', 'Total', 'Status', 'Date']);
        foreach ($sales as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}
