<?php

class StaffanalyticsController extends Controller {

    public function __construct() {
        AuthMiddleware::handleAdmin();
    }

    public function index() {
        $db = Database::getInstance();
        [$whereU, $paramsU] = Tenant::storeFilter('u');
        [$whereI, $paramsI] = Tenant::storeFilter('i');
        [$whereE, $paramsE] = Tenant::storeFilter('e');

        $dateFilter = $_GET['date_filter'] ?? 'today';
        $startDate = trim((string) ($_GET['start_date'] ?? ''));
        $endDate = trim((string) ($_GET['end_date'] ?? ''));
        [$dateConditionI, $dateParamsI, $dateConditionE, $dateParamsE] = $this->dateConditions($dateFilter, $startDate, $endDate);

        $staffOptions = $db->fetchAll("
            SELECT u.id, u.name, u.email, u.role, u.created_at
            FROM users u
            WHERE $whereU
            ORDER BY CASE WHEN u.role = 'store_owner' THEN 0 ELSE 1 END, u.name ASC
        ", $paramsU);

        $validStaffIds = array_map(static function ($row) {
            return (int) $row['id'];
        }, $staffOptions);
        $selectedStaffIds = $this->selectedStaffIds($_GET['staff_ids'] ?? [], $validStaffIds);
        $staffScopeI = '';
        $staffScopeE = '';
        $staffScopeParamsI = [];
        $staffScopeParamsE = [];
        if (!empty($selectedStaffIds)) {
            $placeholders = implode(',', array_fill(0, count($selectedStaffIds), '?'));
            $staffScopeI = " AND i.user_id IN ($placeholders)";
            $staffScopeE = " AND e.created_by IN ($placeholders)";
            $staffScopeParamsI = $selectedStaffIds;
            $staffScopeParamsE = $selectedStaffIds;
        }

        $summary = $db->fetch("
            SELECT
                COUNT(i.id) AS bills,
                COALESCE(SUM(CASE WHEN i.status = 'paid' AND i.transaction_type IN ('Sale','Exchange') THEN i.total ELSE 0 END), 0) AS sales_total,
                COALESCE(SUM(CASE WHEN i.transaction_type IN ('Return','Refund') THEN i.total ELSE 0 END), 0) AS returns_total,
                COALESCE(SUM(CASE WHEN i.status = 'paid' AND i.payment_method = 'cash' THEN i.total ELSE 0 END), 0) AS cash_total,
                COALESCE(SUM(CASE WHEN i.status = 'paid' AND i.payment_method = 'online' THEN i.total ELSE 0 END), 0) AS online_total,
                COALESCE(SUM(CASE WHEN i.payment_method = 'credit' THEN i.total ELSE 0 END), 0) AS credit_total,
                COALESCE(SUM(i.discount), 0) AS discount_total,
                COALESCE(SUM(i.balance_due), 0) AS balance_due
            FROM invoices i
            WHERE $whereI $staffScopeI AND $dateConditionI
        ", array_merge($paramsI, $staffScopeParamsI, $dateParamsI)) ?: [];

        $expenseRow = $db->fetch("
            SELECT COALESCE(SUM(e.amount), 0) AS total_expenses
            FROM expenses e
            WHERE $whereE $staffScopeE AND $dateConditionE
        ", array_merge($paramsE, $staffScopeParamsE, $dateParamsE)) ?: [];

        $summary = array_merge([
            'bills' => 0,
            'sales_total' => 0,
            'returns_total' => 0,
            'cash_total' => 0,
            'online_total' => 0,
            'credit_total' => 0,
            'discount_total' => 0,
            'balance_due' => 0,
        ], $summary);
        $summary['total_expenses'] = (float) ($expenseRow['total_expenses'] ?? 0);
        $summary['net_total'] = (float) $summary['sales_total'] - (float) $summary['returns_total'] - (float) $summary['total_expenses'];
        $summary['avg_bill'] = (int) ($summary['bills'] ?? 0) > 0 ? ((float) $summary['sales_total'] / (int) $summary['bills']) : 0;

        $staffRows = $db->fetchAll("
            SELECT
                u.id,
                u.name,
                u.email,
                u.role,
                COUNT(i.id) AS bills,
                COALESCE(SUM(CASE WHEN i.status = 'paid' AND i.transaction_type IN ('Sale','Exchange') THEN i.total ELSE 0 END), 0) AS sales_total,
                COALESCE(SUM(CASE WHEN i.transaction_type IN ('Return','Refund') THEN i.total ELSE 0 END), 0) AS returns_total,
                COALESCE(SUM(CASE WHEN i.status = 'paid' AND i.payment_method = 'cash' THEN i.total ELSE 0 END), 0) AS cash_total,
                COALESCE(SUM(CASE WHEN i.status = 'paid' AND i.payment_method = 'online' THEN i.total ELSE 0 END), 0) AS online_total,
                COALESCE(SUM(CASE WHEN i.payment_method = 'credit' THEN i.total ELSE 0 END), 0) AS credit_total,
                COALESCE(SUM(i.balance_due), 0) AS balance_due,
                MAX(i.created_at) AS last_sale_at
            FROM users u
            LEFT JOIN invoices i ON i.user_id = u.id AND i.store_id = u.store_id AND $dateConditionI
            WHERE $whereU " . (!empty($selectedStaffIds) ? "AND u.id IN (" . implode(',', array_fill(0, count($selectedStaffIds), '?')) . ")" : '') . "
            GROUP BY u.id, u.name, u.email, u.role
            ORDER BY sales_total DESC, bills DESC, u.name ASC
        ", array_merge($dateParamsI, $paramsU, $selectedStaffIds));

        $dailyTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $row = $db->fetch("
                SELECT COALESCE(SUM(i.total), 0) AS total, COUNT(i.id) AS bills
                FROM invoices i
                WHERE $whereI $staffScopeI
                    AND i.status = 'paid'
                    AND i.transaction_type IN ('Sale','Exchange')
                    AND DATE(i.created_at) = ?
            ", array_merge($paramsI, $staffScopeParamsI, [$date])) ?: [];
            $dailyTrend[] = [
                'label' => date('D', strtotime($date)),
                'date' => $date,
                'total' => (float) ($row['total'] ?? 0),
                'bills' => (int) ($row['bills'] ?? 0),
            ];
        }

        $topProducts = $db->fetchAll("
            SELECT
                ii.product_name,
                COALESCE(ii.model_number, '') AS model_number,
                SUM(ii.quantity) AS qty_sold,
                SUM(ii.total) AS revenue
            FROM invoice_items ii
            INNER JOIN invoices i ON i.id = ii.invoice_id
            WHERE $whereI $staffScopeI
                AND i.status = 'paid'
                AND i.transaction_type IN ('Sale','Exchange')
                AND $dateConditionI
            GROUP BY ii.product_name, ii.model_number
            ORDER BY qty_sold DESC, revenue DESC
            LIMIT 8
        ", array_merge($paramsI, $staffScopeParamsI, $dateParamsI));

        $recentSales = $db->fetchAll("
            SELECT
                i.id,
                i.custom_id,
                i.transaction_type,
                i.payment_method,
                i.total,
                i.status,
                i.created_at,
                u.name AS staff_name,
                COALESCE(i.customer_name_snapshot, c.name, 'Walk-in Customer') AS customer_name
            FROM invoices i
            LEFT JOIN users u ON u.id = i.user_id
            LEFT JOIN customers c ON c.id = i.customer_id
            WHERE $whereI $staffScopeI AND $dateConditionI
            ORDER BY i.created_at DESC
            LIMIT 12
        ", array_merge($paramsI, $staffScopeParamsI, $dateParamsI));

        $this->view('../../modules/staffanalytics/views/index', [
            'pageTitle' => 'Staff Analytics',
            'activeMenu' => 'staff_analytics',
            'staffOptions' => $staffOptions,
            'selectedStaffIds' => $selectedStaffIds,
            'summary' => $summary,
            'staffRows' => $staffRows,
            'dailyTrend' => $dailyTrend,
            'topProducts' => $topProducts,
            'recentSales' => $recentSales,
            'dateFilter' => $dateFilter,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    private function selectedStaffIds($raw, $validStaffIds) {
        $values = is_array($raw) ? $raw : [$raw];
        $ids = [];
        foreach ($values as $value) {
            $id = (int) $value;
            if ($id > 0 && in_array($id, $validStaffIds, true)) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function dateConditions($filter, $startDate, $endDate) {
        $conditionI = '1=1';
        $conditionE = '1=1';
        $paramsI = [];
        $paramsE = [];

        if ($filter === 'today') {
            $conditionI = "DATE(i.created_at) = CURRENT_DATE()";
            $conditionE = "DATE(e.expense_date) = CURRENT_DATE()";
        } elseif ($filter === 'yesterday') {
            $conditionI = "DATE(i.created_at) = DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)";
            $conditionE = "DATE(e.expense_date) = DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)";
        } elseif ($filter === 'this_week') {
            $conditionI = "YEARWEEK(i.created_at, 1) = YEARWEEK(CURRENT_DATE(), 1)";
            $conditionE = "YEARWEEK(e.expense_date, 1) = YEARWEEK(CURRENT_DATE(), 1)";
        } elseif ($filter === 'this_month') {
            $conditionI = "MONTH(i.created_at) = MONTH(CURRENT_DATE()) AND YEAR(i.created_at) = YEAR(CURRENT_DATE())";
            $conditionE = "MONTH(e.expense_date) = MONTH(CURRENT_DATE()) AND YEAR(e.expense_date) = YEAR(CURRENT_DATE())";
        } elseif ($filter === 'custom' && $startDate !== '' && $endDate !== '') {
            $conditionI = "DATE(i.created_at) BETWEEN ? AND ?";
            $conditionE = "DATE(e.expense_date) BETWEEN ? AND ?";
            $paramsI = [$startDate, $endDate];
            $paramsE = [$startDate, $endDate];
        }

        return [$conditionI, $paramsI, $conditionE, $paramsE];
    }
}
