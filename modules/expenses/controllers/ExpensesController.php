<?php

class ExpensesController extends Controller {

    public function __construct() {
        AuthMiddleware::handlePermission('expenses');
    }

    public function index() {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        [$whereE, $whereEParams] = Tenant::storeFilter('e');
        $dateFilter = trim($_GET['date_filter'] ?? '');
        $from = trim($_GET['start_date'] ?? ($_GET['from'] ?? ''));
        $to = trim($_GET['end_date'] ?? ($_GET['to'] ?? ''));
        $method = trim($_GET['method'] ?? '');
        $q = trim($_GET['q'] ?? '');
        $categoryId = (int) ($_GET['category_id'] ?? 0);

        if ($dateFilter === '') {
            $dateFilter = 'today';
        }
        if (!in_array($dateFilter, ['today', 'this_week', 'this_month', 'custom'], true)) {
            $dateFilter = 'today';
        }
        if (!in_array($method, ['', 'cash', 'online'], true)) {
            $method = '';
        }

        $conditions = [$whereE];
        $params = $whereEParams;
        if ($dateFilter === 'today') {
            $conditions[] = "DATE(e.expense_date) = CURRENT_DATE()";
            $from = date('Y-m-d');
            $to = date('Y-m-d');
        } elseif ($dateFilter === 'this_week') {
            $conditions[] = "YEARWEEK(e.expense_date, 1) = YEARWEEK(CURRENT_DATE(), 1)";
            $weekStart = new DateTime();
            $weekStart->setISODate((int) date('o'), (int) date('W'));
            $weekEnd = clone $weekStart;
            $weekEnd->modify('+6 days');
            $from = $weekStart->format('Y-m-d');
            $to = $weekEnd->format('Y-m-d');
        } elseif ($dateFilter === 'this_month') {
            $conditions[] = "MONTH(e.expense_date) = MONTH(CURRENT_DATE()) AND YEAR(e.expense_date) = YEAR(CURRENT_DATE())";
            $from = date('Y-m-01');
            $to = date('Y-m-t');
        } elseif ($dateFilter === 'custom' && $from !== '' && $to !== '') {
            $conditions[] = "DATE(e.expense_date) BETWEEN ? AND ?";
            $params[] = $from;
            $params[] = $to;
        }

        if ($method !== '') {
            $conditions[] = "e.payment_method = ?";
            $params[] = $method;
        }

        if ($categoryId > 0) {
            $conditions[] = "e.category_id = ?";
            $params[] = $categoryId;
        }

        if ($q !== '') {
            $conditions[] = "(
                e.title LIKE ?
                OR COALESCE(e.notes, '') LIKE ?
                OR COALESCE(e.custom_category, '') LIKE ?
                OR COALESCE(e.category_name_snapshot, '') LIKE ?
            )";
            for ($i = 0; $i < 4; $i++) {
                $params[] = "%$q%";
            }
        }

        $filterWhere = implode(' AND ', $conditions);

        $sql = "
            SELECT e.*, COALESCE(ec.name, e.category_name_snapshot, e.custom_category, 'Uncategorized') AS category_name, u.name AS created_by_name
            FROM expenses e
            LEFT JOIN expense_categories ec ON ec.id = e.category_id
            LEFT JOIN users u ON u.id = e.created_by
            WHERE $filterWhere
        ";

        $sql .= " ORDER BY e.expense_date DESC, e.id DESC";
        $expenses = $db->fetchAll($sql, $params);

        $categories = $db->fetchAll("SELECT * FROM expense_categories WHERE $where ORDER BY name ASC", $whereParams);

        $summary = [
            'total' => (float) (($db->fetch("SELECT SUM(e.amount) AS sum FROM expenses e WHERE $filterWhere", $params)['sum'] ?? 0)),
            'cash' => (float) (($db->fetch("SELECT SUM(e.amount) AS sum FROM expenses e WHERE $filterWhere AND e.payment_method = 'cash'", $params)['sum'] ?? 0)),
            'online' => (float) (($db->fetch("SELECT SUM(e.amount) AS sum FROM expenses e WHERE $filterWhere AND e.payment_method = 'online'", $params)['sum'] ?? 0)),
            'month' => (float) (($db->fetch("SELECT SUM(e.amount) AS sum FROM expenses e WHERE $whereE AND MONTH(e.expense_date)=MONTH(CURRENT_DATE()) AND YEAR(e.expense_date)=YEAR(CURRENT_DATE())", $whereEParams)['sum'] ?? 0)),
        ];

        $this->view('../../modules/expenses/views/index', [
            'pageTitle' => 'Expenses',
            'activeMenu' => 'expenses',
            'expenses' => $expenses,
            'categories' => $categories,
            'summary' => $summary,
            'filters' => compact('from', 'to', 'method', 'dateFilter', 'q', 'categoryId')
        ]);
    }

    public function createCategory() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('expenses/index');
        }
        $this->verifyCsrfOrAbort();

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $this->flash('error', 'Category name is required.');
            $this->redirect('expenses/index');
        }

        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $exists = $db->fetch("SELECT id FROM expense_categories WHERE name = ? AND $where LIMIT 1", array_merge([$name], $whereParams));
        if ($exists) {
            $this->flash('error', 'Expense category already exists.');
            $this->redirect('expenses/index');
        }

        $db->query("INSERT INTO expense_categories (store_id, name, created_by) VALUES (?, ?, ?)", [Tenant::currentStoreId(), $name, Auth::id()]);
        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), Auth::id(), 'Expense', "Added expense category: $name"]);
        $this->flash('success', 'Category added.');
        $this->redirect('expenses/index');
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('expenses/index');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $title = trim($_POST['title'] ?? '');
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $customCategory = trim($_POST['custom_category'] ?? '');
        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        $paymentMethod = strtolower(trim($_POST['payment_method'] ?? 'cash'));
        $notes = trim($_POST['notes'] ?? '');
        $expenseDate = trim($_POST['expense_date'] ?? date('Y-m-d'));

        if ($title === '' || $amount <= 0) {
            $this->flash('error', 'Expense title and valid amount are required.');
            $this->redirect('expenses/index');
        }

        if (!in_array($paymentMethod, ['cash', 'online'], true)) {
            $paymentMethod = 'cash';
        }

        $categoryName = '';
        if ($categoryId > 0) {
            $row = $db->fetch("SELECT name FROM expense_categories WHERE id = ? AND $where", array_merge([$categoryId], $whereParams));
            $categoryName = $row['name'] ?? '';
        }

        if ($customCategory !== '' && $categoryName === '') {
            $exists = $db->fetch("SELECT id FROM expense_categories WHERE name = ? AND $where LIMIT 1", array_merge([$customCategory], $whereParams));
            if (!$exists) {
                $db->query("INSERT INTO expense_categories (store_id, name, created_by) VALUES (?, ?, ?)", [Tenant::currentStoreId(), $customCategory, Auth::id()]);
                $categoryId = (int) $db->lastInsertId();
                $categoryName = $customCategory;
            } else {
                $categoryId = (int) $exists['id'];
                $categoryName = $customCategory;
            }
        }

        $db->query(
            "INSERT INTO expenses (store_id, category_id, category_name_snapshot, custom_category, title, amount, payment_method, notes, expense_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [Tenant::currentStoreId(), $categoryId ?: null, $categoryName ?: null, $customCategory ?: null, $title, $amount, $paymentMethod, $notes ?: null, $expenseDate, Auth::id()]
        );

        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), Auth::id(), 'Expense', "Added expense $title for " . App::money($amount)]);
        $this->flash('success', 'Expense added successfully.');
        $this->redirect('expenses/index');
    }

    public function edit($id) {
        $id = (int)$id;
        if ($id <= 0) {
            $this->flash('error', 'Invalid expense ID.');
            $this->redirect('expenses/index');
        }

        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $expense = $db->fetch("SELECT * FROM expenses WHERE id = ? AND $where", array_merge([$id], $whereParams));

        if (!$expense) {
            $this->flash('error', 'Expense not found.');
            $this->redirect('expenses/index');
        }

        $categories = $db->fetchAll("SELECT * FROM expense_categories WHERE $where ORDER BY name ASC", $whereParams);

        $this->view('../../modules/expenses/views/edit', [
            'pageTitle' => 'Edit Expense',
            'activeMenu' => 'expenses',
            'expense' => $expense,
            'categories' => $categories
        ]);
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('expenses/index');
        }
        $this->verifyCsrfOrAbort();

        $id = (int)$id;
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $expense = $db->fetch("SELECT * FROM expenses WHERE id = ? AND $where", array_merge([$id], $whereParams));

        if (!$expense) {
            $this->flash('error', 'Expense not found.');
            $this->redirect('expenses/index');
        }

        $title = trim($_POST['title'] ?? '');
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $customCategory = trim($_POST['custom_category'] ?? '');
        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        $paymentMethod = strtolower(trim($_POST['payment_method'] ?? 'cash'));
        $notes = trim($_POST['notes'] ?? '');
        $expenseDate = trim($_POST['expense_date'] ?? date('Y-m-d'));

        if ($title === '' || $amount <= 0) {
            $this->flash('error', 'Expense title and valid amount are required.');
            $this->redirect('expenses/edit/' . $id);
        }

        if (!in_array($paymentMethod, ['cash', 'online'], true)) {
            $paymentMethod = 'cash';
        }

        $categoryName = '';
        if ($categoryId > 0) {
            $row = $db->fetch("SELECT name FROM expense_categories WHERE id = ? AND $where", array_merge([$categoryId], $whereParams));
            $categoryName = $row['name'] ?? '';
        }

        if ($customCategory !== '' && $categoryName === '') {
            $exists = $db->fetch("SELECT id FROM expense_categories WHERE name = ? AND $where LIMIT 1", array_merge([$customCategory], $whereParams));
            if (!$exists) {
                $db->query("INSERT INTO expense_categories (store_id, name, created_by) VALUES (?, ?, ?)", [Tenant::currentStoreId(), $customCategory, Auth::id()]);
                $categoryId = (int) $db->lastInsertId();
                $categoryName = $customCategory;
            } else {
                $categoryId = (int) $exists['id'];
                $categoryName = $customCategory;
            }
        }

        $db->query(
            "UPDATE expenses SET category_id = ?, category_name_snapshot = ?, custom_category = ?, title = ?, amount = ?, payment_method = ?, notes = ?, expense_date = ? WHERE id = ? AND $where",
            array_merge([$categoryId ?: null, $categoryName ?: null, $customCategory ?: null, $title, $amount, $paymentMethod, $notes ?: null, $expenseDate, $id], $whereParams)
        );

        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), Auth::id(), 'Expense', "Updated expense $title for " . App::money($amount)]);
        $this->flash('success', 'Expense updated successfully.');
        $this->redirect('expenses/index');
    }

    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('expenses/index');
        }
        $this->verifyCsrfOrAbort();

        $id = (int)$id;
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        $expense = $db->fetch("SELECT id, title, amount FROM expenses WHERE id = ? AND $where", array_merge([$id], $whereParams));

        if (!$expense) {
            $this->flash('error', 'Expense not found.');
            $this->redirect('expenses/index');
        }

        $db->query("DELETE FROM expenses WHERE id = ? AND $where", array_merge([$id], $whereParams));
        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [Tenant::currentStoreId(), Auth::id(), 'Expense', "Deleted expense {$expense['title']} for " . App::money($expense['amount'])]);
        
        $this->flash('success', 'Expense deleted successfully.');
        $this->redirect('expenses/index');
    }

    public function export() {
        $db = Database::getInstance();
        [$where, $whereParams] = Tenant::storeFilter();
        [$whereE, $whereEParams] = Tenant::storeFilter('e');
        $dateFilter = trim($_GET['date_filter'] ?? '');
        $from = trim($_GET['start_date'] ?? ($_GET['from'] ?? ''));
        $to = trim($_GET['end_date'] ?? ($_GET['to'] ?? ''));
        $method = trim($_GET['method'] ?? '');
        $q = trim($_GET['q'] ?? '');
        $categoryId = (int) ($_GET['category_id'] ?? 0);

        if ($dateFilter === '') {
            $dateFilter = 'today';
        }
        if (!in_array($dateFilter, ['today', 'this_week', 'this_month', 'custom'], true)) {
            $dateFilter = 'today';
        }
        if (!in_array($method, ['', 'cash', 'online'], true)) {
            $method = '';
        }

        $conditions = [$whereE];
        $params = $whereEParams;
        if ($dateFilter === 'today') {
            $conditions[] = "DATE(e.expense_date) = CURRENT_DATE()";
        } elseif ($dateFilter === 'this_week') {
            $conditions[] = "YEARWEEK(e.expense_date, 1) = YEARWEEK(CURRENT_DATE(), 1)";
        } elseif ($dateFilter === 'this_month') {
            $conditions[] = "MONTH(e.expense_date) = MONTH(CURRENT_DATE()) AND YEAR(e.expense_date) = YEAR(CURRENT_DATE())";
        } elseif ($dateFilter === 'custom' && $from !== '' && $to !== '') {
            $conditions[] = "DATE(e.expense_date) BETWEEN ? AND ?";
            $params[] = $from;
            $params[] = $to;
        }

        if ($method !== '') {
            $conditions[] = "e.payment_method = ?";
            $params[] = $method;
        }

        if ($categoryId > 0) {
            $conditions[] = "e.category_id = ?";
            $params[] = $categoryId;
        }

        if ($q !== '') {
            $conditions[] = "(
                e.title LIKE ?
                OR COALESCE(e.notes, '') LIKE ?
                OR COALESCE(e.custom_category, '') LIKE ?
                OR COALESCE(e.category_name_snapshot, '') LIKE ?
            )";
            for ($i = 0; $i < 4; $i++) {
                $params[] = "%$q%";
            }
        }

        $filterWhere = implode(' AND ', $conditions);

        $sql = "
            SELECT e.*, COALESCE(ec.name, e.category_name_snapshot, e.custom_category, 'Uncategorized') AS category_name
            FROM expenses e
            LEFT JOIN expense_categories ec ON ec.id = e.category_id
            WHERE $filterWhere
            ORDER BY e.expense_date DESC, e.id DESC
        ";
        $expenses = $db->fetchAll($sql, $params);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="expenses_export_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Title', 'Category', 'Amount', 'Method', 'Date', 'Notes']);
        foreach ($expenses as $row) {
            fputcsv($output, [
                $row['title'], $row['category_name'], $row['amount'],
                $row['payment_method'], $row['expense_date'], $row['notes']
            ]);
        }
        fclose($output);
        exit;
    }

    public function import() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file']['tmp_name'])) {
            $this->redirect('expenses/index');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $handle = fopen($_FILES['file']['tmp_name'], "r");
        $header = fgetcsv($handle);
        if (!$header) {
            $this->flash('error', 'CSV file is empty or invalid.');
            $this->redirect('expenses/index');
        }

        $colMap = [];
        foreach ($header as $idx => $colName) {
            $colMap[strtolower(trim($colName))] = $idx;
        }

        $imported = 0;
        $storeId = Tenant::currentStoreId();
        $userId = Auth::id();

        [$whereCat, $whereCatParams] = Tenant::storeFilter();
        $categoriesList = $db->fetchAll("SELECT id, name FROM expense_categories WHERE $whereCat", $whereCatParams);
        $categoriesMap = [];
        foreach ($categoriesList as $cat) {
            $categoriesMap[strtolower(trim($cat['name']))] = (int) $cat['id'];
        }

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $title = isset($colMap['title']) ? trim($data[$colMap['title']] ?? '') : '';
            $amount = isset($colMap['amount']) ? max(0, (float) ($data[$colMap['amount']] ?? 0)) : 0;
            if ($title === '' && $amount <= 0) {
                continue;
            }

            $categoryName = isset($colMap['category']) ? trim($data[$colMap['category']] ?? 'Uncategorized') : 'Uncategorized';
            $method = isset($colMap['method']) ? strtolower(trim($data[$colMap['method']] ?? 'cash')) : 'cash';
            if (!in_array($method, ['cash', 'online'], true)) {
                $method = 'cash';
            }
            $date = isset($colMap['date']) ? trim($data[$colMap['date']] ?? date('Y-m-d')) : date('Y-m-d');
            $notes = isset($colMap['notes']) ? trim($data[$colMap['notes']] ?? '') : '';

            if (strtotime($date) === false) {
                $date = date('Y-m-d');
            } else {
                $date = date('Y-m-d', strtotime($date));
            }

            $catKey = strtolower(trim($categoryName));
            $categoryId = null;
            if ($catKey !== '' && $catKey !== 'uncategorized') {
                if (isset($categoriesMap[$catKey])) {
                    $categoryId = $categoriesMap[$catKey];
                } else {
                    $db->query("INSERT INTO expense_categories (store_id, name, created_by) VALUES (?, ?, ?)", [$storeId, $categoryName, $userId]);
                    $categoryId = (int) $db->lastInsertId();
                    $categoriesMap[$catKey] = $categoryId;
                }
            }

            $db->query("
                INSERT INTO expenses
                (store_id, category_id, category_name_snapshot, custom_category, title, amount, payment_method, notes, expense_date, created_by)
                VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)
            ", [
                $storeId,
                $categoryId ?: null,
                $categoryName,
                $title,
                $amount,
                $method,
                $notes ?: null,
                $date,
                $userId
            ]);

            $imported++;
        }

        fclose($handle);
        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)", [$storeId, $userId, 'Expense', "Imported $imported expenses from CSV"]);
        $this->flash('success', $imported . ' expenses imported successfully.');
        $this->redirect('expenses/index');
    }
}
