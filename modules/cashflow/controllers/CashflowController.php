<?php

class CashflowController extends Controller {

    public function __construct() {
        AuthMiddleware::handle();
    }

    public function index() {
        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        [$whereStore, $whereStoreParams] = Tenant::storeFilter();

        // 1. Ensure accounts exist for this store
        $accounts = $db->fetchAll("SELECT * FROM financial_accounts WHERE store_id = ? AND status = 'active' ORDER BY is_default DESC, id ASC", [$storeId]);
        if (empty($accounts)) {
            $db->query("INSERT INTO financial_accounts (store_id, account_name, account_type, account_number, bank_name, opening_balance, current_balance, is_default, status) VALUES (?, 'Cash in Hand (Counter Drawer)', 'cash', 'DRAWER-01', 'Cash Till', 0.00, 0.00, 1, 'active')", [$storeId]);
            $db->query("INSERT INTO financial_accounts (store_id, account_name, account_type, account_number, bank_name, opening_balance, current_balance, is_default, status) VALUES (?, 'Main Online Account', 'wallet', 'WALLET-01', 'JazzCash / EasyPaisa / Bank', 0.00, 0.00, 0, 'active')", [$storeId]);
            $accounts = $db->fetchAll("SELECT * FROM financial_accounts WHERE store_id = ? AND status = 'active' ORDER BY is_default DESC, id ASC", [$storeId]);
        }

        // 1b. Fetch dynamic digital gateways (wallets/banks)
        $gateways = $db->fetchAll("
            SELECT id, account_name, account_type, current_balance 
            FROM financial_accounts 
            WHERE store_id = ? AND account_type IN ('wallet', 'bank', 'other') AND is_default = 0 AND status = 'active' 
            ORDER BY account_name ASC
        ", [$storeId]);

        if (empty($gateways)) {
            $defaultGateways = ['JazzCash', 'EasyPaisa', 'SadaPay / NayaPay', 'Meezan Bank', 'Bank Transfer'];
            foreach ($defaultGateways as $gw) {
                $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $gw), 0, 8)) . '-01';
                $db->query("INSERT INTO financial_accounts (store_id, account_name, account_type, account_number, is_default, status) VALUES (?, ?, 'wallet', ?, 0, 'active')", [
                    $storeId, $gw, $code
                ]);
            }
            $gateways = $db->fetchAll("
                SELECT id, account_name, account_type, current_balance 
                FROM financial_accounts 
                WHERE store_id = ? AND account_type IN ('wallet', 'bank', 'other') AND is_default = 0 AND status = 'active' 
                ORDER BY account_name ASC
            ", [$storeId]);
        }

        // 2. Filters
        $dateFilter = trim($_GET['date_filter'] ?? 'this_month');
        $from = trim($_GET['start_date'] ?? ($_GET['from'] ?? ''));
        $to = trim($_GET['end_date'] ?? ($_GET['to'] ?? ''));
        $accountFilter = (int) ($_GET['account_id'] ?? 0);
        $typeFilter = trim($_GET['type'] ?? '');
        $q = trim($_GET['q'] ?? '');
        $activeTab = trim($_GET['tab'] ?? 'balances');

        if (!in_array($dateFilter, ['today', 'yesterday', 'this_week', 'this_month', 'all', 'custom'], true)) {
            $dateFilter = 'this_month';
        }

        $txWhere = "c.store_id = ?";
        $txParams = [$storeId];

        if ($dateFilter === 'today') {
            $txWhere .= " AND DATE(c.transaction_date) = CURRENT_DATE()";
            $from = date('Y-m-d');
            $to = date('Y-m-d');
        } elseif ($dateFilter === 'yesterday') {
            $txWhere .= " AND DATE(c.transaction_date) = DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)";
            $from = date('Y-m-d', strtotime('-1 day'));
            $to = $from;
        } elseif ($dateFilter === 'this_week') {
            $txWhere .= " AND YEARWEEK(c.transaction_date, 1) = YEARWEEK(CURRENT_DATE(), 1)";
            $weekStart = new DateTime();
            $weekStart->setISODate((int) date('o'), (int) date('W'));
            $weekEnd = clone $weekStart;
            $weekEnd->modify('+6 days');
            $from = $weekStart->format('Y-m-d');
            $to = $weekEnd->format('Y-m-d');
        } elseif ($dateFilter === 'this_month') {
            $txWhere .= " AND MONTH(c.transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(c.transaction_date) = YEAR(CURRENT_DATE())";
            $from = date('Y-m-01');
            $to = date('Y-m-t');
        } elseif ($dateFilter === 'custom' && $from !== '' && $to !== '') {
            $txWhere .= " AND DATE(c.transaction_date) BETWEEN ? AND ?";
            $txParams[] = $from;
            $txParams[] = $to;
        }

        if ($accountFilter > 0) {
            $txWhere .= " AND (c.from_account_id = ? OR c.to_account_id = ?)";
            $txParams[] = $accountFilter;
            $txParams[] = $accountFilter;
        }

        if ($typeFilter !== '') {
            $txWhere .= " AND c.transaction_type = ?";
            $txParams[] = $typeFilter;
        }

        if ($q !== '') {
            $txWhere .= " AND (c.party_name LIKE ? OR c.reference_no LIKE ? OR c.description LIKE ? OR c.notes LIKE ?)";
            $term = '%' . $q . '%';
            $txParams[] = $term;
            $txParams[] = $term;
            $txParams[] = $term;
            $txParams[] = $term;
        }

        // Fetch Cashflow Transactions
        $transactions = $db->fetchAll("
            SELECT 
                c.*,
                fa_from.account_name AS from_account_name,
                fa_from.account_type AS from_account_type,
                fa_to.account_name AS to_account_name,
                fa_to.account_type AS to_account_type,
                u.name AS cashier_name
            FROM cashflow_transactions c
            LEFT JOIN financial_accounts fa_from ON fa_from.id = c.from_account_id
            LEFT JOIN financial_accounts fa_to ON fa_to.id = c.to_account_id
            LEFT JOIN users u ON u.id = c.user_id
            WHERE $txWhere
            ORDER BY c.transaction_date DESC, c.id DESC
        ", $txParams);

        // 3. Calculate Overall Financial KPIs (Live & Accurate)
        // Cash in Hand Account (Default cash drawer)
        $defaultCashAcc = null;
        foreach ($accounts as $acc) {
            if ((int)$acc['is_default'] === 1 || $acc['account_type'] === 'cash') {
                $defaultCashAcc = $acc;
                break;
            }
        }
        $cashAccId = $defaultCashAcc ? (int)$defaultCashAcc['id'] : 0;

        // Sales breakdown (Store wide)
        $cashSalesSum = (float) (($db->fetch("SELECT SUM(paid_amount) AS sum FROM invoices WHERE store_id = ? AND status = 'paid' AND payment_method = 'cash'", [$storeId])['sum'] ?? 0));
        $onlineSalesSum = (float) (($db->fetch("SELECT SUM(paid_amount) AS sum FROM invoices WHERE store_id = ? AND status = 'paid' AND LOWER(payment_method) IN ('online', 'card', 'bank', 'jazzcash', 'easypaisa')", [$storeId])['sum'] ?? 0));

        // Credit recoveries breakdown
        $cashCreditRecv = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM credit_payments WHERE store_id = ? AND payment_method = 'cash'", [$storeId])['sum'] ?? 0));
        $onlineCreditRecv = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM credit_payments WHERE store_id = ? AND LOWER(payment_method) IN ('online', 'card', 'bank', 'jazzcash', 'easypaisa')", [$storeId])['sum'] ?? 0));

        // Expenses breakdown
        $cashExpenses = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM expenses WHERE store_id = ? AND payment_method = 'cash'", [$storeId])['sum'] ?? 0));
        $onlineExpenses = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM expenses WHERE store_id = ? AND LOWER(payment_method) IN ('online', 'card', 'bank')", [$storeId])['sum'] ?? 0));

        // Supplier payments breakdown
        $cashSupplierPayments = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM supplier_payments WHERE store_id = ? AND payment_method = 'cash'", [$storeId])['sum'] ?? 0));
        $onlineSupplierPayments = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM supplier_payments WHERE store_id = ? AND LOWER(payment_method) IN ('online', 'bank')", [$storeId])['sum'] ?? 0));

        // Net Exchange impact on Cash Drawer:
        // Cash GIVEN out: to_account is online, from_account is cash
        $exchangeCashGiven = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND transaction_type = 'money_exchange' AND from_account_id = ?", [$storeId, $cashAccId])['sum'] ?? 0));
        // Cash RECEIVED in: from_account is online, to_account is cash
        $exchangeCashReceived = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND transaction_type = 'money_exchange' AND to_account_id = ?", [$storeId, $cashAccId])['sum'] ?? 0));
        // Commission earned from exchanges
        $exchangeFeesEarned = (float) (($db->fetch("SELECT SUM(fee_amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND transaction_type = 'money_exchange'", [$storeId])['sum'] ?? 0));

        // Internal Transfers impact on Cash Drawer:
        $transferInToCash = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND transaction_type = 'transfer' AND to_account_id = ?", [$storeId, $cashAccId])['sum'] ?? 0));
        $transferOutFromCash = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND transaction_type = 'transfer' AND from_account_id = ?", [$storeId, $cashAccId])['sum'] ?? 0));

        // Adjustments impact on Cash Drawer:
        $adjustInCash = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND transaction_type = 'adjustment_in' AND to_account_id = ?", [$storeId, $cashAccId])['sum'] ?? 0));
        $adjustOutCash = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND transaction_type = 'adjustment_out' AND from_account_id = ?", [$storeId, $cashAccId])['sum'] ?? 0));

        // Calculate LIVE Cash in Hand:
        $cashOpening = (float) ($defaultCashAcc['opening_balance'] ?? 0);
        $liveCashInHand = ($cashOpening + $cashSalesSum + $cashCreditRecv + $exchangeCashReceived + $transferInToCash + $adjustInCash + $exchangeFeesEarned)
                        - ($cashExpenses + $cashSupplierPayments + $exchangeCashGiven + $transferOutFromCash + $adjustOutCash);

        // Calculate LIVE Online / Bank Balance:
        $totalOnlineOpening = 0.0;
        foreach ($accounts as $acc) {
            if ($acc['account_type'] !== 'cash' && (int)$acc['id'] !== $cashAccId) {
                $totalOnlineOpening += (float) $acc['opening_balance'];
            }
        }
        $exchangeOnlineReceived = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND transaction_type = 'money_exchange' AND to_account_id != ? AND to_account_id IS NOT NULL", [$storeId, $cashAccId])['sum'] ?? 0));
        $exchangeOnlineGiven = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND transaction_type = 'money_exchange' AND from_account_id != ? AND from_account_id IS NOT NULL", [$storeId, $cashAccId])['sum'] ?? 0));

        $transferInToOnline = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND transaction_type = 'transfer' AND to_account_id != ? AND to_account_id IS NOT NULL", [$storeId, $cashAccId])['sum'] ?? 0));
        $transferOutFromOnline = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND transaction_type = 'transfer' AND from_account_id != ? AND from_account_id IS NOT NULL", [$storeId, $cashAccId])['sum'] ?? 0));

        $adjustInOnline = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND transaction_type = 'adjustment_in' AND to_account_id != ? AND to_account_id IS NOT NULL", [$storeId, $cashAccId])['sum'] ?? 0));
        $adjustOutOnline = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND transaction_type = 'adjustment_out' AND from_account_id != ? AND from_account_id IS NOT NULL", [$storeId, $cashAccId])['sum'] ?? 0));

        $liveOnlineBalance = ($totalOnlineOpening + $onlineSalesSum + $onlineCreditRecv + $exchangeOnlineReceived + $transferInToOnline + $adjustInOnline)
                           - ($onlineExpenses + $onlineSupplierPayments + $exchangeOnlineGiven + $transferOutFromOnline + $adjustOutOnline);

        // Update cached current_balance in financial_accounts table for quick access
        if ($defaultCashAcc) {
            $db->query("UPDATE financial_accounts SET current_balance = ? WHERE id = ?", [$liveCashInHand, $cashAccId]);
        }
        foreach ($accounts as $acc) {
            if ($acc['account_type'] !== 'cash') {
                $accId = (int)$acc['id'];
                $accOpening = (float)$acc['opening_balance'];
                $accIn = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND to_account_id = ?", [$storeId, $accId])['sum'] ?? 0));
                $accOut = (float) (($db->fetch("SELECT SUM(amount) AS sum FROM cashflow_transactions WHERE store_id = ? AND from_account_id = ?", [$storeId, $accId])['sum'] ?? 0));
                $accBal = $accOpening + $accIn - $accOut;
                $db->query("UPDATE financial_accounts SET current_balance = ? WHERE id = ?", [$accBal, $accId]);
            }
        }

        // Re-read accounts with updated live balances
        $accounts = $db->fetchAll("SELECT * FROM financial_accounts WHERE store_id = ? AND status = 'active' ORDER BY is_default DESC, id ASC", [$storeId]);

        // Total Liquid Funds
        $totalLiquid = $liveCashInHand + $liveOnlineBalance;

        // Market Receivables (Customers se lena hai)
        $receivables = (float) (($db->fetch("
            SELECT SUM(GREATEST((COALESCE(total,0)-COALESCE(paid_amount,0)), COALESCE(balance_due,0), 0)) AS sum 
            FROM invoices 
            WHERE store_id = ? AND is_credit_settled = 0 AND balance_due > 0
        ", [$storeId])['sum'] ?? 0));

        // Market Payables (Suppliers ko dena hai)
        $payables = (float) (($db->fetch("
            SELECT SUM(current_balance) AS sum 
            FROM suppliers 
            WHERE store_id = ? AND current_balance > 0
        ", [$storeId])['sum'] ?? 0));

        // Net Financial Position
        $netPosition = ($totalLiquid + $receivables) - $payables;

        // 4. Receivables List (Top customers with pending credit)
        $receivablesList = $db->fetchAll("
            SELECT 
                COALESCE(i.customer_id, 0) AS id,
                COALESCE(MAX(c.name), MAX(i.customer_name_snapshot), 'Walk-in Customer') AS name,
                COALESCE(MAX(c.phone), MAX(i.customer_phone_snapshot), '') AS phone,
                COALESCE(MAX(c.address), MAX(i.customer_address_snapshot), '') AS address,
                SUM(GREATEST((COALESCE(i.total,0)-COALESCE(i.paid_amount,0)), COALESCE(balance_due,0), 0)) AS total_due,
                COUNT(i.id) AS pending_invoices_count,
                MAX(i.created_at) AS last_invoice_date
            FROM invoices i
            LEFT JOIN customers c ON c.id = i.customer_id
            WHERE i.store_id = ? AND i.is_credit_settled = 0 AND i.balance_due > 0
            GROUP BY i.customer_id
            HAVING total_due > 0
            ORDER BY total_due DESC
            LIMIT 10
        ", [$storeId]);

        // 5. Payables List (Top suppliers with pending balance)
        $payablesList = $db->fetchAll("
            SELECT id, name, company_name, phone, current_balance, updated_at
            FROM suppliers
            WHERE store_id = ? AND current_balance > 0
            ORDER BY current_balance DESC
            LIMIT 10
        ", [$storeId]);

        // Summary metrics array for view
        $kpis = [
            'cash_in_hand' => $liveCashInHand,
            'online_balance' => $liveOnlineBalance,
            'total_liquid' => $totalLiquid,
            'receivables' => $receivables,
            'payables' => $payables,
            'net_position' => $netPosition,
            'exchange_fees_earned' => $exchangeFeesEarned
        ];

        // Recent balance audit adjustments
        $recentAdjustments = $db->fetchAll("
            SELECT c.*, 
                   COALESCE(fa_to.account_name, fa_from.account_name, 'Account') AS account_name, 
                   u.name AS staff_name
            FROM cashflow_transactions c
            LEFT JOIN financial_accounts fa_to ON fa_to.id = c.to_account_id
            LEFT JOIN financial_accounts fa_from ON fa_from.id = c.from_account_id
            LEFT JOIN users u ON u.id = c.user_id
            WHERE c.store_id = ? AND c.transaction_type IN ('adjustment_in', 'adjustment_out')
            ORDER BY c.transaction_date DESC, c.id DESC
            LIMIT 15
        ", [$storeId]);

        $this->view('../../modules/cashflow/views/index', [
            'pageTitle' => 'Cashflow & Financial Accounts',
            'activeMenu' => 'cashflow',
            'accounts' => $accounts,
            'transactions' => $transactions,
            'kpis' => $kpis,
            'receivablesList' => $receivablesList,
            'payablesList' => $payablesList,
            'filters' => compact('from', 'to', 'dateFilter', 'accountFilter', 'typeFilter', 'q', 'activeTab'),
            'gateways' => $gateways,
            'recentAdjustments' => $recentAdjustments,
            'defaultCashAcc' => $defaultCashAcc,
            'liveCashInHand' => $liveCashInHand,
            'totalOnlineBalance' => $liveOnlineBalance,
            'totalLiquid' => $totalLiquid
        ]);
    }

    /**
     * AJAX Action: Add new digital gateway to database
     */
    public function addGateway() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Gateway name cannot be empty']);
            exit;
        }

        // Check if existing record
        $existing = $db->fetch("SELECT id, status FROM financial_accounts WHERE store_id = ? AND LOWER(account_name) = LOWER(?) LIMIT 1", [$storeId, $name]);
        if ($existing) {
            if ($existing['status'] !== 'active') {
                $db->query("UPDATE financial_accounts SET status = 'active' WHERE id = ?", [$existing['id']]);
            }
            echo json_encode(['success' => true, 'id' => (int)$existing['id'], 'name' => $name]);
            exit;
        }

        $accNo = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 8)) . '-01';
        $db->query("
            INSERT INTO financial_accounts 
            (store_id, account_name, account_type, account_number, is_default, status) 
            VALUES (?, ?, 'wallet', ?, 0, 'active')
        ", [$storeId, $name, $accNo]);

        $newId = (int)$db->lastInsertId();
        echo json_encode(['success' => true, 'id' => $newId, 'name' => $name]);
        exit;
    }

    /**
     * AJAX Action: Remove/deactivate a gateway from database
     */
    public function deleteGateway() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid gateway ID']);
            exit;
        }

        // Soft delete / deactivate so previous transactions stay intact
        $db->query("UPDATE financial_accounts SET status = 'inactive' WHERE id = ? AND store_id = ? AND is_default = 0", [$id, $storeId]);

        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Action: Money Exchange (Online Received -> Cash Given / Cash Received -> Online Sent)
     */
    public function exchange() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('cashflow/index');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        $userId = Auth::id();

        $direction = trim($_POST['direction'] ?? 'online_to_cash'); // 'online_to_cash' (Online aya, Cash dya) OR 'cash_to_online' (Cash aya, Online bheja)
        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        $feeAmount = max(0, (float) ($_POST['fee_amount'] ?? 0));
        $onlineAccountId = (int) ($_POST['online_account_id'] ?? 0);
        $cashAccountId = (int) ($_POST['cash_account_id'] ?? 0);
        $partyName = trim($_POST['party_name'] ?? '');
        $partyPhone = trim($_POST['party_phone'] ?? '');
        $referenceNo = trim($_POST['reference_no'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($amount <= 0) {
            $this->flash('error', 'Please enter a valid exchange amount.');
            $this->redirect('cashflow/index');
        }

        $channel = trim($_POST['channel'] ?? 'JazzCash');
        if ($onlineAccountId <= 0) {
            $onlineAcc = $db->fetch("SELECT id FROM financial_accounts WHERE store_id = ? AND LOWER(account_name) = LOWER(?) LIMIT 1", [$storeId, $channel]);
            if ($onlineAcc) {
                $onlineAccountId = (int)$onlineAcc['id'];
            } else {
                $onlineAcc = $db->fetch("SELECT id FROM financial_accounts WHERE store_id = ? AND account_type != 'cash' LIMIT 1", [$storeId]);
                if ($onlineAcc) {
                    $onlineAccountId = (int)$onlineAcc['id'];
                } else {
                    $db->query("INSERT INTO financial_accounts (store_id, account_name, account_type, account_number, is_default, status) VALUES (?, ?, 'wallet', 'WALLET-01', 0, 'active')", [$storeId, $channel]);
                    $onlineAccountId = (int)$db->lastInsertId();
                }
            }
        }
        if ($cashAccountId <= 0) {
            $cashAcc = $db->fetch("SELECT id FROM financial_accounts WHERE store_id = ? AND (is_default = 1 OR account_type = 'cash') LIMIT 1", [$storeId]);
            if ($cashAcc) {
                $cashAccountId = (int)$cashAcc['id'];
            } else {
                $db->query("INSERT INTO financial_accounts (store_id, account_name, account_type, account_number, is_default, status) VALUES (?, 'Cash in Hand (Counter Drawer)', 'cash', 'DRAWER-01', 1, 'active')", [$storeId]);
                $cashAccountId = (int)$db->lastInsertId();
            }
        }

        $channelLabel = $channel ?: 'Digital Channel';
        if ($direction === 'online_to_cash') {
            // Customer sent money online, store hands over cash from drawer
            $fromAccountId = $cashAccountId;      // Cash Drawer sends money out
            $toAccountId = $onlineAccountId;     // Online Account receives money in
            $desc = "Exchange ({$channelLabel}): Received " . number_format($amount, 2) . " (" . ($referenceNo ?: 'No TID') . "), disbursed Cash from drawer";
        } else {
            // Customer gave cash, store transfers online
            $fromAccountId = $onlineAccountId;    // Online Account sends money out
            $toAccountId = $cashAccountId;       // Cash Drawer receives cash
            $desc = "Exchange ({$channelLabel}): Received Cash " . number_format($amount, 2) . ", sent via {$channelLabel} (" . ($referenceNo ?: 'No TID') . ")";
        }

        $now = date('Y-m-d H:i:s');
        $netAmount = $amount + $feeAmount;

        $db->query("
            INSERT INTO cashflow_transactions 
            (store_id, user_id, transaction_type, from_account_id, to_account_id, amount, fee_amount, net_amount, party_type, party_name, party_phone, reference_no, description, notes, transaction_date, created_at)
            VALUES (?, ?, 'money_exchange', ?, ?, ?, ?, ?, 'customer', ?, ?, ?, ?, ?, ?, ?)
        ", [
            $storeId,
            $userId,
            $fromAccountId,
            $toAccountId,
            $amount,
            $feeAmount,
            $netAmount,
            $partyName ?: 'Customer Exchange',
            $partyPhone ?: null,
            $referenceNo ?: null,
            $desc,
            $notes ?: null,
            $now,
            $now
        ]);

        $txId = $db->lastInsertId();

        // Update balances
        $db->query("UPDATE financial_accounts SET current_balance = current_balance - ? WHERE id = ? AND store_id = ?", [$amount, $fromAccountId, $storeId]);
        $db->query("UPDATE financial_accounts SET current_balance = current_balance + ? WHERE id = ? AND store_id = ?", [$amount + $feeAmount, $toAccountId, $storeId]);

        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, 'Cashflow Exchange', ?)", [
            $storeId, $userId, "$desc (Amount: Rs $amount, Fee: Rs $feeAmount, Ref: $referenceNo)"
        ]);

        $this->flash('success', "Money Exchange recorded successfully! Reference: " . ($referenceNo ?: "#TX-$txId"));
        $this->redirect('cashflow/index?tab=roznamcha');
    }

    /**
     * Action: Fund Transfer (e.g. Bank Deposit, ATM Cash Withdrawal)
     */
    public function transfer() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('cashflow/index');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        $userId = Auth::id();

        $fromAccountId = (int) ($_POST['from_account_id'] ?? 0);
        $toAccountId = (int) ($_POST['to_account_id'] ?? 0);
        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        $referenceNo = trim($_POST['reference_no'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($amount <= 0 || $fromAccountId <= 0 || $toAccountId <= 0 || $fromAccountId === $toAccountId) {
            $this->flash('error', 'Please select two different accounts and a valid transfer amount.');
            $this->redirect('cashflow/index');
        }

        $now = date('Y-m-d H:i:s');
        $desc = "Fund Transfer: " . number_format($amount, 2);

        $db->query("
            INSERT INTO cashflow_transactions 
            (store_id, user_id, transaction_type, from_account_id, to_account_id, amount, fee_amount, net_amount, party_type, reference_no, description, notes, transaction_date, created_at)
            VALUES (?, ?, 'transfer', ?, ?, ?, 0.00, ?, 'other', ?, ?, ?, ?, ?)
        ", [
            $storeId,
            $userId,
            $fromAccountId,
            $toAccountId,
            $amount,
            $amount,
            $referenceNo ?: null,
            $desc,
            $notes ?: null,
            $now,
            $now
        ]);

        $db->query("UPDATE financial_accounts SET current_balance = current_balance - ? WHERE id = ? AND store_id = ?", [$amount, $fromAccountId, $storeId]);
        $db->query("UPDATE financial_accounts SET current_balance = current_balance + ? WHERE id = ? AND store_id = ?", [$amount, $toAccountId, $storeId]);

        $this->flash('success', "Funds transferred successfully!");
        $this->redirect('cashflow/index?tab=roznamcha');
    }

    /**
     * Action: Cash In / Cash Out Adjustment (Owner Capital / Drawings / Float)
     */
    public function adjust() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('cashflow/index');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        $userId = Auth::id();

        $type = trim($_POST['type'] ?? 'in'); // 'in' (Cash In / Injection) or 'out' (Cash Out / Drawing)
        $accountId = (int) ($_POST['account_id'] ?? 0);
        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        $reason = trim($_POST['reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($amount <= 0 || $accountId <= 0) {
            $this->flash('error', 'Please enter a valid amount and account.');
            $this->redirect('cashflow/index');
        }

        $now = date('Y-m-d H:i:s');
        if ($type === 'in') {
            $txType = 'adjustment_in';
            $fromAcc = null;
            $toAcc = $accountId;
            $desc = "Cash In / Capital Injection: " . ($reason ?: 'Adjustment');
            $db->query("UPDATE financial_accounts SET current_balance = current_balance + ? WHERE id = ? AND store_id = ?", [$amount, $accountId, $storeId]);
        } else {
            $txType = 'adjustment_out';
            $fromAcc = $accountId;
            $toAcc = null;
            $desc = "Cash Out / Owner Drawing: " . ($reason ?: 'Adjustment');
            $db->query("UPDATE financial_accounts SET current_balance = current_balance - ? WHERE id = ? AND store_id = ?", [$amount, $accountId, $storeId]);
        }

        $db->query("
            INSERT INTO cashflow_transactions 
            (store_id, user_id, transaction_type, from_account_id, to_account_id, amount, fee_amount, net_amount, party_type, description, notes, transaction_date, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 0.00, ?, 'owner', ?, ?, ?, ?)
        ", [
            $storeId,
            $userId,
            $txType,
            $fromAcc,
            $toAcc,
            $amount,
            $amount,
            $desc,
            $notes ?: null,
            $now,
            $now
        ]);

        $this->flash('success', "Balance adjustment recorded successfully.");
        $this->redirect('cashflow/index?tab=roznamcha');
    }

    /**
     * Action: Add New Financial Account
     */
    public function createAccount() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('cashflow/index?tab=balances');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();

        $accountName = trim($_POST['account_name'] ?? '');
        $accountType = trim($_POST['account_type'] ?? 'bank');
        $accountNumber = trim($_POST['account_number'] ?? '');
        $bankName = trim($_POST['bank_name'] ?? '');
        $openingBalance = max(0, (float) ($_POST['opening_balance'] ?? 0));
        $notes = trim($_POST['notes'] ?? '');

        if ($accountName === '') {
            $this->flash('error', 'Account Name is required.');
            $this->redirect('cashflow/index?tab=balances');
        }

        if (!in_array($accountType, ['cash', 'bank', 'wallet', 'other'], true)) {
            $accountType = 'bank';
        }

        $db->query("
            INSERT INTO financial_accounts 
            (store_id, account_name, account_type, account_number, bank_name, opening_balance, current_balance, is_default, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'active', ?)
        ", [
            $storeId,
            $accountName,
            $accountType,
            $accountNumber ?: null,
            $bankName ?: null,
            $openingBalance,
            $openingBalance,
            $notes ?: null
        ]);

        $this->flash('success', "New Account '$accountName' added successfully.");
        $this->redirect('cashflow/index?tab=balances');
    }

    /**
     * Action: Edit Financial Account
     */
    public function editAccount($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('cashflow/index?tab=balances');
        }
        $this->verifyCsrfOrAbort();

        $id = (int)$id;
        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();

        $accountName = trim($_POST['account_name'] ?? '');
        $accountType = trim($_POST['account_type'] ?? 'bank');
        $accountNumber = trim($_POST['account_number'] ?? '');
        $bankName = trim($_POST['bank_name'] ?? '');
        $openingBalance = max(0, (float) ($_POST['opening_balance'] ?? 0));
        $notes = trim($_POST['notes'] ?? '');

        if ($accountName === '' || $id <= 0) {
            $this->flash('error', 'Invalid account data.');
            $this->redirect('cashflow/index?tab=balances');
        }

        $db->query("
            UPDATE financial_accounts 
            SET account_name = ?, account_type = ?, account_number = ?, bank_name = ?, opening_balance = ?, notes = ?
            WHERE id = ? AND store_id = ?
        ", [
            $accountName,
            $accountType,
            $accountNumber ?: null,
            $bankName ?: null,
            $openingBalance,
            $notes ?: null,
            $id,
            $storeId
        ]);

        $this->flash('success', "Account updated successfully.");
        $this->redirect('cashflow/index?tab=balances');
    }

    /**
     * Dedicated Page: Drawer & Account Balances Manager (Redirects smoothly to unified Cashflow Balances Tab)
     */
    public function balances() {
        $this->redirect('cashflow/index?tab=balances');
    }

    /**
     * Action: Set Exact Balance or Quick Adjustment
     */
    public function setBalance() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('cashflow/index?tab=balances');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        $userId = Auth::id();

        $accountId = (int)($_POST['account_id'] ?? 0);
        $mode = trim($_POST['mode'] ?? 'set_exact'); // 'set_exact', 'add_cash', 'withdraw_cash'
        $targetAmount = (float)($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Manual Balance Adjustment');
        $notes = trim($_POST['notes'] ?? '');

        if ($accountId <= 0) {
            $this->flash('error', 'Please select a valid account.');
            $this->redirect('cashflow/index?tab=balances');
        }

        $account = $db->fetch("SELECT * FROM financial_accounts WHERE id = ? AND store_id = ?", [$accountId, $storeId]);
        if (!$account) {
            $this->flash('error', 'Account not found.');
            $this->redirect('cashflow/index?tab=balances');
        }

        $currentBal = (float)$account['current_balance'];
        $now = date('Y-m-d H:i:s');

        if ($mode === 'set_exact') {
            $diff = $targetAmount - $currentBal;
            if (abs($diff) < 0.001) {
                $this->flash('info', 'Current balance is already ' . number_format($targetAmount, 2));
                $this->redirect('cashflow/index?tab=balances');
            }

            if ($diff > 0) {
                $adjAmount = $diff;
                $txType = 'adjustment_in';
                $fromAcc = null;
                $toAcc = $accountId;
                $desc = "Balance Reconciled (+): " . ($reason ?: 'Physical/Online Count Adjustment');
            } else {
                $adjAmount = abs($diff);
                $txType = 'adjustment_out';
                $fromAcc = $accountId;
                $toAcc = null;
                $desc = "Balance Reconciled (-): " . ($reason ?: 'Physical/Online Count Adjustment');
            }
        } elseif ($mode === 'add_cash') {
            if ($targetAmount <= 0) {
                $this->flash('error', 'Amount must be greater than 0.');
                $this->redirect('cashflow/index?tab=balances');
            }
            $adjAmount = $targetAmount;
            $txType = 'adjustment_in';
            $fromAcc = null;
            $toAcc = $accountId;
            $desc = "Cash In / Deposit: " . ($reason ?: 'Manual Addition');
        } else { // withdraw_cash
            if ($targetAmount <= 0) {
                $this->flash('error', 'Amount must be greater than 0.');
                $this->redirect('cashflow/index?tab=balances');
            }
            $adjAmount = $targetAmount;
            $txType = 'adjustment_out';
            $fromAcc = $accountId;
            $toAcc = null;
            $desc = "Cash Out / Drawing: " . ($reason ?: 'Manual Withdrawal');
        }

        $db->query("
            INSERT INTO cashflow_transactions 
            (store_id, user_id, transaction_type, from_account_id, to_account_id, amount, fee_amount, net_amount, party_type, description, notes, transaction_date, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 0.00, ?, 'owner', ?, ?, ?, ?)
        ", [
            $storeId,
            $userId,
            $txType,
            $fromAcc,
            $toAcc,
            $adjAmount,
            $adjAmount,
            $desc,
            $notes ?: null,
            $now,
            $now
        ]);

        if ($txType === 'adjustment_in') {
            $db->query("UPDATE financial_accounts SET current_balance = current_balance + ? WHERE id = ? AND store_id = ?", [$adjAmount, $accountId, $storeId]);
        } else {
            $db->query("UPDATE financial_accounts SET current_balance = current_balance - ? WHERE id = ? AND store_id = ?", [$adjAmount, $accountId, $storeId]);
        }

        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, 'Balance Adjustment', ?)", [
            $storeId, $userId, "$desc for account {$account['account_name']} (Amount: Rs $adjAmount)"
        ]);

        $this->flash('success', "Balance for '{$account['account_name']}' updated successfully!");
        $this->redirect('cashflow/index?tab=balances');
    }

    /**
     * Action: Deactivate Account
     */
    public function deleteAccount() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('cashflow/index?tab=balances');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('error', 'Invalid account ID.');
            $this->redirect('cashflow/index?tab=balances');
        }

        $acc = $db->fetch("SELECT * FROM financial_accounts WHERE id = ? AND store_id = ?", [$id, $storeId]);
        if (!$acc) {
            $this->flash('error', 'Account not found.');
            $this->redirect('cashflow/index?tab=balances');
        }
        if ((int)$acc['is_default'] === 1) {
            $this->flash('error', 'Default Cash Drawer account cannot be deactivated.');
            $this->redirect('cashflow/index?tab=balances');
        }

        $db->query("UPDATE financial_accounts SET status = 'inactive' WHERE id = ? AND store_id = ?", [$id, $storeId]);
        $this->flash('success', "Account '{$acc['account_name']}' deactivated.");
        $this->redirect('cashflow/index?tab=balances');
    }

    /**
     * Action: Delete & Reverse Cashflow Transaction
     */
    public function deleteTransaction() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('cashflow/index');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        $userId = Auth::id();
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('error', 'Invalid transaction ID.');
            $this->redirect('cashflow/index');
        }

        $tx = $db->fetch("SELECT * FROM cashflow_transactions WHERE id = ? AND store_id = ?", [$id, $storeId]);
        if (!$tx) {
            $this->flash('error', 'Transaction not found.');
            $this->redirect('cashflow/index');
        }

        $amount = (float)$tx['amount'];
        $fee = (float)$tx['fee_amount'];
        $fromAccId = (int)$tx['from_account_id'];
        $toAccId = (int)$tx['to_account_id'];
        $type = $tx['transaction_type'];

        if ($type === 'money_exchange') {
            if ($fromAccId > 0) {
                $db->query("UPDATE financial_accounts SET current_balance = current_balance + ? WHERE id = ? AND store_id = ?", [$amount, $fromAccId, $storeId]);
            }
            if ($toAccId > 0) {
                $db->query("UPDATE financial_accounts SET current_balance = current_balance - ? WHERE id = ? AND store_id = ?", [$amount + $fee, $toAccId, $storeId]);
            }
        } elseif ($type === 'transfer') {
            if ($fromAccId > 0) {
                $db->query("UPDATE financial_accounts SET current_balance = current_balance + ? WHERE id = ? AND store_id = ?", [$amount, $fromAccId, $storeId]);
            }
            if ($toAccId > 0) {
                $db->query("UPDATE financial_accounts SET current_balance = current_balance - ? WHERE id = ? AND store_id = ?", [$amount, $toAccId, $storeId]);
            }
        } elseif ($type === 'adjustment_in') {
            if ($toAccId > 0) {
                $db->query("UPDATE financial_accounts SET current_balance = current_balance - ? WHERE id = ? AND store_id = ?", [$amount, $toAccId, $storeId]);
            }
        } elseif ($type === 'adjustment_out') {
            if ($fromAccId > 0) {
                $db->query("UPDATE financial_accounts SET current_balance = current_balance + ? WHERE id = ? AND store_id = ?", [$amount, $fromAccId, $storeId]);
            }
        }

        $db->query("DELETE FROM cashflow_transactions WHERE id = ? AND store_id = ?", [$id, $storeId]);

        $db->query("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, 'Reversed Transaction', ?)", [
            $storeId, $userId, "Deleted transaction #$id (Amount: Rs $amount, Type: $type)"
        ]);

        $this->flash('success', "Transaction #$id reversed and account balances restored successfully.");
        $redirectUrl = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'cashflow/index';
        $this->redirect(str_replace(BASE_URL . '/', '', $redirectUrl));
    }

    /**
     * Action: Edit Transaction Details (Reference, Party, Notes)
     */
    public function editTransaction() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('cashflow/index');
        }
        $this->verifyCsrfOrAbort();

        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        $id = (int)($_POST['id'] ?? 0);
        $partyName = trim($_POST['party_name'] ?? '');
        $partyPhone = trim($_POST['party_phone'] ?? '');
        $referenceNo = trim($_POST['reference_no'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($id <= 0) {
            $this->flash('error', 'Invalid transaction ID.');
            $this->redirect('cashflow/index');
        }

        $db->query("
            UPDATE cashflow_transactions 
            SET party_name = ?, party_phone = ?, reference_no = ?, description = ?, notes = ?
            WHERE id = ? AND store_id = ?
        ", [
            $partyName ?: null,
            $partyPhone ?: null,
            $referenceNo ?: null,
            $description ?: null,
            $notes ?: null,
            $id,
            $storeId
        ]);

        $this->flash('success', "Transaction #$id updated successfully.");
        $redirectUrl = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'cashflow/index';
        $this->redirect(str_replace(BASE_URL . '/', '', $redirectUrl));
    }

    /**
     * Action: Export CSV
     */
    public function export() {
        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();

        $from = trim($_GET['start_date'] ?? ($_GET['from'] ?? ''));
        $to = trim($_GET['end_date'] ?? ($_GET['to'] ?? ''));
        $accountFilter = (int) ($_GET['account_id'] ?? 0);
        $typeFilter = trim($_GET['type'] ?? '');

        $txWhere = "c.store_id = ?";
        $txParams = [$storeId];

        if ($from !== '' && $to !== '') {
            $txWhere .= " AND DATE(c.transaction_date) BETWEEN ? AND ?";
            $txParams[] = $from;
            $txParams[] = $to;
        }
        if ($accountFilter > 0) {
            $txWhere .= " AND (c.from_account_id = ? OR c.to_account_id = ?)";
            $txParams[] = $accountFilter;
            $txParams[] = $accountFilter;
        }
        if ($typeFilter !== '') {
            $txWhere .= " AND c.transaction_type = ?";
            $txParams[] = $typeFilter;
        }

        $rows = $db->fetchAll("
            SELECT 
                c.id, c.transaction_date, c.transaction_type,
                fa_from.account_name AS from_account,
                fa_to.account_name AS to_account,
                c.amount, c.fee_amount, c.net_amount,
                c.party_name, c.party_phone, c.reference_no,
                c.description, c.notes,
                u.name AS cashier_name
            FROM cashflow_transactions c
            LEFT JOIN financial_accounts fa_from ON fa_from.id = c.from_account_id
            LEFT JOIN financial_accounts fa_to ON fa_to.id = c.to_account_id
            LEFT JOIN users u ON u.id = c.user_id
            WHERE $txWhere
            ORDER BY c.transaction_date DESC, c.id DESC
        ", $txParams);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="cashflow_statement_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Date & Time', 'Type', 'From Account', 'To Account', 'Amount', 'Fee', 'Net Total', 'Party Name', 'Phone', 'Ref TID', 'Description', 'Notes', 'Staff']);

        foreach ($rows as $r) {
            fputcsv($output, [
                $r['id'],
                $r['transaction_date'],
                strtoupper(str_replace('_', ' ', $r['transaction_type'])),
                $r['from_account'] ?: '-',
                $r['to_account'] ?: '-',
                $r['amount'],
                $r['fee_amount'],
                $r['net_amount'],
                $r['party_name'] ?: '-',
                $r['party_phone'] ?: '-',
                $r['reference_no'] ?: '-',
                $r['description'] ?: '-',
                $r['notes'] ?: '-',
                $r['cashier_name'] ?: '-'
            ]);
        }
        fclose($output);
        exit;
    }
}
