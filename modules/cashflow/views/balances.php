<?php ob_start(); ?>
<?php
$currency = $settings['currency_symbol'] ?? 'Rs';
$accounts = $accounts ?? [];
$defaultCashAcc = $defaultCashAcc ?? null;
$liveCashInHand = (float)($liveCashInHand ?? 0);
$totalOnlineBalance = (float)($totalOnlineBalance ?? 0);
$totalLiquid = (float)($totalLiquid ?? 0);
$recentAdjustments = $recentAdjustments ?? [];
$activeAccountsCount = count(array_filter($accounts, fn($a) => $a['status'] === 'active'));
?>

<style>
    /* ========================================================
       PREMIUM CASH & ACCOUNT BALANCES SUITE
       ======================================================== */
    .cf-balances-wrapper {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Inter", "Manrope", sans-serif;
        color: #0f172a;
        max-width: 1400px;
        margin: 0 auto;
        padding-bottom: 50px;
        box-sizing: border-box;
    }

    /* Top Bar */
    .cf-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e2e8f0;
    }
    .cf-title-area {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .cf-main-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #ffffff !important;
        display: grid;
        place-items: center;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.2);
    }
    .cf-main-icon .material-symbols-rounded {
        font-size: 26px;
        color: #ffffff !important;
    }
    .cf-topbar h1 {
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.02em;
    }
    .cf-topbar p {
        font-size: 13px;
        color: #64748b;
        margin: 2px 0 0;
    }

    /* STRICT SOLID BUTTONS */
    .btn-solid-emerald,
    .btn-solid-dark,
    .btn-solid-blue,
    .btn-solid-rose,
    .btn-solid-amber,
    .btn-solid-gray {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 7px !important;
        font-weight: 700 !important;
        color: #ffffff !important;
        border: none !important;
        border-radius: 8px !important;
        cursor: pointer !important;
        text-decoration: none !important;
        transition: all 0.15s ease-in-out !important;
        line-height: 1 !important;
        box-sizing: border-box !important;
    }
    .btn-solid-emerald *, .btn-solid-dark *, .btn-solid-blue *, .btn-solid-rose *, .btn-solid-amber *, .btn-solid-gray * {
        color: #ffffff !important;
    }
    .btn-solid-emerald { background: #059669 !important; box-shadow: 0 2px 6px rgba(5, 150, 105, 0.3) !important; }
    .btn-solid-emerald:hover { background: #047857 !important; }
    .btn-solid-dark { background: #0f172a !important; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.3) !important; }
    .btn-solid-dark:hover { background: #1e293b !important; }
    .btn-solid-blue { background: #0284c7 !important; box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3) !important; }
    .btn-solid-blue:hover { background: #0369a1 !important; }
    .btn-solid-rose { background: #e11d48 !important; box-shadow: 0 2px 6px rgba(225, 29, 72, 0.3) !important; }
    .btn-solid-rose:hover { background: #be123c !important; }
    .btn-solid-gray { background: #475569 !important; }
    .btn-solid-gray:hover { background: #334155 !important; }

    .btn-md { height: 40px; padding: 0 16px; font-size: 13px; }
    .btn-sm { height: 32px; padding: 0 12px; font-size: 12px; }
    .btn-xs { height: 26px; padding: 0 8px; font-size: 11px; }

    /* Top KPI Grid */
    .bal-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }
    .bal-kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px 22px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.05);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .bal-kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }
    .kpi-cash::before { background: linear-gradient(90deg, #10b981, #059669); }
    .kpi-online::before { background: linear-gradient(90deg, #0284c7, #2563eb); }
    .kpi-liquid::before { background: linear-gradient(90deg, #8b5cf6, #6366f1); }

    .bal-kpi-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    .bal-kpi-title {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
    }
    .bal-kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: grid;
        place-items: center;
    }
    .icon-cash { background: #d1fae5; color: #059669; }
    .icon-online { background: #e0f2fe; color: #0284c7; }
    .icon-liquid { background: #ede9fe; color: #7c3aed; }
    .bal-kpi-icon .material-symbols-rounded { font-size: 20px; }

    .bal-kpi-value {
        font-size: 26px;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -0.03em;
        font-feature-settings: 'tnum';
        margin-bottom: 6px;
    }
    .bal-kpi-sub {
        font-size: 11.5px;
        color: #64748b;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Accounts Grid */
    .bal-section-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 16px;
    }
    .bal-section-title {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .bal-accounts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
        margin-bottom: 34px;
    }

    .account-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.04);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .account-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.08);
        transform: translateY(-2px);
    }
    .account-card.is-drawer {
        border-color: #a7f3d0;
        background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);
    }

    .acc-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 14px;
    }
    .acc-title-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .acc-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        font-size: 18px;
        font-weight: 800;
    }
    .avatar-cash { background: #dcfce7; color: #15803d; }
    .avatar-bank { background: #e0f2fe; color: #0369a1; }
    .avatar-wallet { background: #fef3c7; color: #b45309; }

    .acc-name {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        line-height: 1.3;
    }
    .acc-inst {
        font-size: 11.5px;
        color: #64748b;
        margin-top: 2px;
    }
    .acc-badge {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 6px;
        letter-spacing: 0.3px;
    }
    .badge-cash { background: #dcfce7; color: #166534; }
    .badge-bank { background: #e0f2fe; color: #075985; }
    .badge-wallet { background: #fef3c7; color: #92400e; }

    .acc-info-box {
        background: #f8fafc;
        border: 1px solid #edf2f7;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 14px;
    }
    .acc-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 11.5px;
        margin-bottom: 4px;
    }
    .acc-info-row:last-child { margin-bottom: 0; }
    .acc-info-lbl { color: #64748b; font-weight: 600; }
    .acc-info-val {
        color: #0f172a;
        font-weight: 700;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .acc-balance-block {
        margin-bottom: 16px;
    }
    .acc-balance-lbl {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.4px;
    }
    .acc-balance-amount {
        font-size: 22px;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -0.02em;
        font-feature-settings: 'tnum';
    }

    .acc-actions {
        display: flex;
        gap: 8px;
        border-top: 1px solid #f1f5f9;
        padding-top: 14px;
    }
    .acc-actions .btn-sm { flex: 1; }

    /* Adjustments Table */
    .bal-table-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 15px -3px rgba(15, 23, 42, 0.05);
    }
    .bal-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
    }
    .bal-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    .bal-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    .bal-table tr:last-child td { border-bottom: none; }
    .bal-table tr:hover td { background: #f8fafc; }

    /* Modal Form Styles */
    .cf-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
    .cf-form-full { grid-column: 1 / -1; }
    .cf-field-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .cf-field-label {
        font-size: 12px;
        font-weight: 700;
        color: #334155;
    }
    .cf-input, .cf-select, .cf-textarea {
        height: 40px;
        padding: 0 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 13px;
        color: #0f172a;
        background: #ffffff;
        box-sizing: border-box;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        width: 100%;
    }
    .cf-textarea {
        height: 68px;
        padding: 8px 12px;
        resize: vertical;
    }
    .cf-input:focus, .cf-select:focus, .cf-textarea:focus {
        outline: none;
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }
</style>

<div class="cf-balances-wrapper">
    <!-- Top Bar -->
    <div class="cf-topbar">
        <div class="cf-title-area">
            <div class="cf-main-icon">
                <span class="material-symbols-rounded">tune</span>
            </div>
            <div>
                <h1>Cash & Account Balances Manager</h1>
                <p>Live reconciliation of physical cash drawer, digital banking, and manual balance corrections.</p>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/index" class="btn-solid-gray btn-md">
                <span class="material-symbols-rounded">arrow_back</span>
                Back to Cashflow Journal
            </a>
            <button type="button" class="btn-solid-emerald btn-md" onclick="openSetBalanceModal()">
                <span class="material-symbols-rounded">sync</span>
                Set / Adjust Cash & Balances
            </button>
            <button type="button" class="btn-solid-dark btn-md" onclick="openAddAccountModal()">
                <span class="material-symbols-rounded">add_circle</span>
                + Add Bank / Wallet Account
            </button>
        </div>
    </div>

    <!-- 3 High Impact Summary KPIs -->
    <div class="bal-kpi-grid">
        <!-- 1. Physical Drawer Cash -->
        <div class="bal-kpi-card kpi-cash">
            <div class="bal-kpi-header">
                <span class="bal-kpi-title">Physical Cash in Drawer</span>
                <div class="bal-kpi-icon icon-cash">
                    <span class="material-symbols-rounded">point_of_sale</span>
                </div>
            </div>
            <div class="bal-kpi-value"><?= $currency ?> <?= number_format($liveCashInHand, 2) ?></div>
            <div class="bal-kpi-sub">
                <span>Current Till Cash Available</span>
                <button type="button" class="btn-solid-emerald btn-xs" onclick="openSetBalanceModal(<?= (int)($defaultCashAcc['id'] ?? 0) ?>, 'Cash in Hand (Counter Drawer)', <?= $liveCashInHand ?>)">
                    Count & Set
                </button>
            </div>
        </div>

        <!-- 2. Online & Bank Balances -->
        <div class="bal-kpi-card kpi-online">
            <div class="bal-kpi-header">
                <span class="bal-kpi-title">Digital & Bank Funds</span>
                <div class="bal-kpi-icon icon-online">
                    <span class="material-symbols-rounded">account_balance</span>
                </div>
            </div>
            <div class="bal-kpi-value"><?= $currency ?> <?= number_format($totalOnlineBalance, 2) ?></div>
            <div class="bal-kpi-sub">
                <span>Sum of <?= $activeAccountsCount - 1 ?> Online / Bank Accounts</span>
                <span style="font-weight:700; color:#0284c7;">Active Wallets</span>
            </div>
        </div>

        <!-- 3. Total Liquid Capital -->
        <div class="bal-kpi-card kpi-liquid">
            <div class="bal-kpi-header">
                <span class="bal-kpi-title">Total Liquid Capital</span>
                <div class="bal-kpi-icon icon-liquid">
                    <span class="material-symbols-rounded">payments</span>
                </div>
            </div>
            <div class="bal-kpi-value"><?= $currency ?> <?= number_format($totalLiquid, 2) ?></div>
            <div class="bal-kpi-sub">
                <span>Drawer Cash + All Bank Funds</span>
                <span style="font-weight:700; color:#7c3aed;">100% Reconciled</span>
            </div>
        </div>
    </div>

    <!-- SECTION: Account-by-Account Breakdown -->
    <div class="bal-section-head">
        <div class="bal-section-title">
            <span class="material-symbols-rounded" style="color:#0f172a;">wallet</span>
            Registered Accounts & Balances Breakdown
        </div>
        <div style="font-size:12px; color:#64748b; font-weight:700;">
            Total Accounts: <?= count($accounts) ?> (<?= $activeAccountsCount ?> Active)
        </div>
    </div>

    <div class="bal-accounts-grid">
        <?php foreach ($accounts as $acc): ?>
            <?php 
                $isCash = ($acc['account_type'] === 'cash' || (int)$acc['is_default'] === 1);
                $isBank = ($acc['account_type'] === 'bank');
                $isWallet = ($acc['account_type'] === 'wallet');
                $balance = (float)$acc['current_balance'];
            ?>
            <div class="account-card <?= $isCash ? 'is-drawer' : '' ?>">
                <div>
                    <!-- Header -->
                    <div class="acc-header">
                        <div class="acc-title-wrap">
                            <div class="acc-avatar <?= $isCash ? 'avatar-cash' : ($isBank ? 'avatar-bank' : 'avatar-wallet') ?>">
                                <span class="material-symbols-rounded">
                                    <?= $isCash ? 'point_of_sale' : ($isBank ? 'account_balance' : 'smartphone') ?>
                                </span>
                            </div>
                            <div>
                                <h3 class="acc-name"><?= Security::escape($acc['account_name']) ?></h3>
                                <div class="acc-inst"><?= Security::escape($acc['bank_name'] ?: ($isCash ? 'Cash Till' : 'Digital Channel')) ?></div>
                            </div>
                        </div>
                        <span class="acc-badge <?= $isCash ? 'badge-cash' : ($isBank ? 'badge-bank' : 'badge-wallet') ?>">
                            <?= $isCash ? 'DRAWER' : ($isBank ? 'BANK' : 'WALLET') ?>
                        </span>
                    </div>

                    <!-- Details Box -->
                    <div class="acc-info-box">
                        <div class="acc-info-row">
                            <span class="acc-info-lbl">Account / IBAN:</span>
                            <span class="acc-info-val">
                                <?= Security::escape($acc['account_number'] ?: 'N/A') ?>
                                <?php if (!empty($acc['account_number'])): ?>
                                    <button type="button" onclick="navigator.clipboard.writeText('<?= Security::escape($acc['account_number']) ?>'); window.appToast('Account number copied!', 'success');" style="background:none; border:none; padding:0; cursor:pointer; color:#64748b;" title="Copy">
                                        <span class="material-symbols-rounded" style="font-size:14px;">content_copy</span>
                                    </button>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="acc-info-row">
                            <span class="acc-info-lbl">Status:</span>
                            <span class="acc-info-val" style="color:<?= $acc['status'] === 'active' ? '#059669' : '#dc2626' ?>;">
                                <?= ucfirst(Security::escape($acc['status'])) ?>
                            </span>
                        </div>
                        <?php if (!empty($acc['notes'])): ?>
                            <div class="acc-info-row" style="margin-top:4px;">
                                <span class="acc-info-lbl">Branch / Notes:</span>
                                <span style="font-size:11px; color:#64748b;"><?= Security::escape($acc['notes']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Balance Display -->
                    <div class="acc-balance-block">
                        <div class="acc-balance-lbl">Available Balance</div>
                        <div class="acc-balance-amount" style="color:<?= $balance >= 0 ? '#0f172a' : '#dc2626' ?>;">
                            <?= $currency ?> <?= number_format($balance, 2) ?>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="acc-actions">
                    <button type="button" class="btn-solid-emerald btn-sm" onclick="openSetBalanceModal(<?= (int)$acc['id'] ?>, '<?= Security::escape($acc['account_name']) ?>', <?= $balance ?>)" title="Set or Adjust Balance">
                        <span class="material-symbols-rounded" style="font-size:15px;">tune</span>
                        Adjust
                    </button>
                    <button type="button" class="btn-solid-blue btn-sm" onclick='openEditAccountModal(<?= json_encode($acc) ?>)' title="Edit Account Details">
                        <span class="material-symbols-rounded" style="font-size:15px;">edit</span>
                        Edit
                    </button>
                    <?php if (!$isCash): ?>
                        <button type="button" class="btn-solid-rose btn-sm" onclick="confirmDeactivateAccount(<?= (int)$acc['id'] ?>, '<?= Security::escape($acc['account_name']) ?>')" title="Deactivate Account" style="max-width:36px; padding:0;">
                            <span class="material-symbols-rounded" style="font-size:15px;">delete</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- SECTION: Recent Adjustments Log -->
    <div class="bal-section-head">
        <div class="bal-section-title">
            <span class="material-symbols-rounded" style="color:#0f172a;">history</span>
            Recent Balance Adjustments & Reconciliations
        </div>
    </div>

    <div class="bal-table-card">
        <table class="bal-table">
            <thead>
                <tr>
                    <th style="width:140px;">Date & Time</th>
                    <th>Account</th>
                    <th>Adjustment Type</th>
                    <th>Reason / Details</th>
                    <th style="text-align:right;">Amount</th>
                    <th>Staff / Handler</th>
                    <th style="width:80px; text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentAdjustments)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:35px 20px; color:#94a3b8;">
                            <span class="material-symbols-rounded" style="font-size:32px; color:#cbd5e1; display:block; margin-bottom:8px;">check_circle</span>
                            No manual adjustments recorded. All accounts are in standard ledger sync.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentAdjustments as $adj): ?>
                        <tr>
                            <td style="font-size:12px; font-weight:700; color:#475569;">
                                <?= date('d M Y, h:i A', strtotime($adj['transaction_date'])) ?>
                            </td>
                            <td>
                                <strong style="color:#0f172a;"><?= Security::escape($adj['account_name']) ?></strong>
                            </td>
                            <td>
                                <?php if ($adj['transaction_type'] === 'adjustment_in'): ?>
                                    <span style="font-size:10.5px; font-weight:800; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                                        <span class="material-symbols-rounded" style="font-size:12px;">arrow_downward</span> INFLOW
                                    </span>
                                <?php else: ?>
                                    <span style="font-size:10.5px; font-weight:800; background:#fee2e2; color:#b91c1c; padding:2px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                                        <span class="material-symbols-rounded" style="font-size:12px;">arrow_upward</span> OUTFLOW
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:12px; color:#334155;">
                                <?= Security::escape($adj['description'] ?: 'Manual Balance Adjustment') ?>
                                <?php if (!empty($adj['notes'])): ?>
                                    <span style="color:#94a3b8; font-size:11px;">(<?= Security::escape($adj['notes']) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right; font-weight:800; font-feature-settings: 'tnum'; font-size:13px; color:<?= $adj['transaction_type'] === 'adjustment_in' ? '#047857' : '#be123c' ?>;">
                                <?= $adj['transaction_type'] === 'adjustment_in' ? '+' : '-' ?> <?= $currency ?> <?= number_format($adj['amount'], 2) ?>
                            </td>
                            <td style="font-size:12px; color:#64748b;">
                                <?= Security::escape($adj['staff_name'] ?: 'Admin') ?>
                            </td>
                            <td style="text-align:center;">
                                <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/deleteTransaction" onsubmit="return confirm('Reverse this adjustment transaction and restore previous balance?');" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$adj['id'] ?>">
                                    <button type="submit" class="btn-solid-rose btn-xs" title="Reverse / Delete Adjustment">
                                        <span class="material-symbols-rounded" style="font-size:14px;">undo</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================================
     MODAL 1: SET / ADJUST BALANCE MODAL
     ======================================================== -->
<div id="modal-set-balance" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:99999; place-items:center; padding:16px;">
    <div style="background:#ffffff; border-radius:16px; width:100%; max-width:500px; box-shadow:0 25px 50px -12px rgba(15,23,42,0.25); overflow:hidden;">
        <div style="padding:18px 22px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:36px; height:36px; border-radius:8px; background:#dcfce7; color:#15803d; display:grid; place-items:center;">
                    <span class="material-symbols-rounded" style="font-size:20px;">tune</span>
                </div>
                <div>
                    <h3 style="font-size:16px; font-weight:800; margin:0; color:#0f172a;">Set / Adjust Account Balance</h3>
                    <div style="font-size:11.5px; color:#64748b;">Directly reconcile drawer cash or bank accounts</div>
                </div>
            </div>
            <button type="button" onclick="closeSetBalanceModal()" style="border:none; background:none; color:#94a3b8; cursor:pointer;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/setBalance" style="padding:22px;">
            <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
            <div style="display:flex; flex-direction:column; gap:14px;">
                <!-- Account Selector -->
                <div class="cf-field-group">
                    <label class="cf-field-label">Target Financial Account</label>
                    <select name="account_id" id="set-bal-account-id" class="cf-select" required onchange="handleAccountSelectChange()">
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= (int)$acc['id'] ?>" data-balance="<?= (float)$acc['current_balance'] ?>" data-name="<?= Security::escape($acc['account_name']) ?>">
                                <?= Security::escape($acc['account_name']) ?> (Current: <?= $currency ?> <?= number_format($acc['current_balance'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Adjustment Mode Switcher -->
                <div class="cf-field-group">
                    <label class="cf-field-label">Adjustment Mode</label>
                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:6px;">
                        <label style="border:1px solid #cbd5e1; border-radius:8px; padding:8px 6px; text-align:center; font-size:11.5px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:4px;" id="mode-label-exact">
                            <input type="radio" name="mode" value="set_exact" checked onchange="updateModeUI()" style="display:none;">
                            <span class="material-symbols-rounded" style="font-size:16px; color:#0284c7;">pin</span>
                            Set Exact Total
                        </label>
                        <label style="border:1px solid #cbd5e1; border-radius:8px; padding:8px 6px; text-align:center; font-size:11.5px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:4px;" id="mode-label-add">
                            <input type="radio" name="mode" value="add_cash" onchange="updateModeUI()" style="display:none;">
                            <span class="material-symbols-rounded" style="font-size:16px; color:#059669;">add_circle</span>
                            Add Inflow (+)
                        </label>
                        <label style="border:1px solid #cbd5e1; border-radius:8px; padding:8px 6px; text-align:center; font-size:11.5px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:4px;" id="mode-label-withdraw">
                            <input type="radio" name="mode" value="withdraw_cash" onchange="updateModeUI()" style="display:none;">
                            <span class="material-symbols-rounded" style="font-size:16px; color:#e11d48;">remove_circle</span>
                            Withdraw (-)
                        </label>
                    </div>
                </div>

                <!-- Amount Input -->
                <div class="cf-field-group">
                    <label class="cf-field-label" id="set-bal-amount-label">New Exact Total Balance (<?= $currency ?>)</label>
                    <input type="number" step="0.01" min="0" name="amount" id="set-bal-amount-input" class="cf-input" placeholder="0.00" required style="font-size:16px; font-weight:800; font-feature-settings:'tnum';">
                    <div id="set-bal-calc-hint" style="font-size:11.5px; color:#64748b; margin-top:2px;"></div>
                </div>

                <!-- Reason / Description -->
                <div class="cf-field-group">
                    <label class="cf-field-label">Reason / Reference</label>
                    <input type="text" name="reason" class="cf-input" placeholder="e.g. Daily Physical Cash Reconciliation / Bank Statement Sync" value="Cash Count Reconciliation">
                </div>

                <!-- Notes (Optional) -->
                <div class="cf-field-group">
                    <label class="cf-field-label">Notes (Optional)</label>
                    <textarea name="notes" class="cf-textarea" placeholder="Additional audit notes..."></textarea>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" class="btn-solid-gray btn-md" onclick="closeSetBalanceModal()">Cancel</button>
                <button type="submit" class="btn-solid-emerald btn-md">
                    <span class="material-symbols-rounded">check_circle</span>
                    Save Balance Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================
     MODAL 2: ADD BANK / WALLET ACCOUNT MODAL
     ======================================================== -->
<div id="modal-add-account" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:99999; place-items:center; padding:16px;">
    <div style="background:#ffffff; border-radius:16px; width:100%; max-width:540px; box-shadow:0 25px 50px -12px rgba(15,23,42,0.25); overflow:hidden;">
        <div style="padding:18px 22px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:36px; height:36px; border-radius:8px; background:#e0f2fe; color:#0284c7; display:grid; place-items:center;">
                    <span class="material-symbols-rounded" style="font-size:20px;">account_balance</span>
                </div>
                <div>
                    <h3 style="font-size:16px; font-weight:800; margin:0; color:#0f172a;">Add New Financial Account</h3>
                    <div style="font-size:11.5px; color:#64748b;">Add bank account, digital wallet or till account</div>
                </div>
            </div>
            <button type="button" onclick="closeAddAccountModal()" style="border:none; background:none; color:#94a3b8; cursor:pointer;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/createAccount" style="padding:22px;">
            <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
            <div class="cf-form-grid">
                <!-- Account Name -->
                <div class="cf-field-group cf-form-full">
                    <label class="cf-field-label">Account Name (Required)</label>
                    <input type="text" name="account_name" class="cf-input" placeholder="e.g. Meezan Bank Current / JazzCash Merchant" required>
                </div>

                <!-- Account Type -->
                <div class="cf-field-group">
                    <label class="cf-field-label">Account Type</label>
                    <select name="account_type" class="cf-select">
                        <option value="bank" selected>Bank Account</option>
                        <option value="wallet">Digital Wallet (JazzCash/EasyPaisa/SadaPay)</option>
                        <option value="cash">Cash Till / Cash Point</option>
                        <option value="other">Other Instrument</option>
                    </select>
                </div>

                <!-- Bank Name -->
                <div class="cf-field-group">
                    <label class="cf-field-label">Bank / Institution Name</label>
                    <input type="text" name="bank_name" class="cf-input" placeholder="e.g. Meezan Bank / Mobilink Bank">
                </div>

                <!-- Account / IBAN Number -->
                <div class="cf-field-group cf-form-full">
                    <label class="cf-field-label">Account Number / IBAN Number</label>
                    <input type="text" name="account_number" class="cf-input" placeholder="e.g. PK36MEZN0001234567890101 / 0300-1234567">
                </div>

                <!-- Opening / Starting Cash -->
                <div class="cf-field-group cf-form-full">
                    <label class="cf-field-label">Initial Opening Balance (<?= $currency ?>)</label>
                    <input type="number" step="0.01" min="0" name="opening_balance" class="cf-input" placeholder="0.00" value="0.00" style="font-size:15px; font-weight:800;">
                </div>

                <!-- Notes -->
                <div class="cf-field-group cf-form-full">
                    <label class="cf-field-label">Branch / Description / Notes (Optional)</label>
                    <textarea name="notes" class="cf-textarea" placeholder="Branch code, manager contact, or purpose..."></textarea>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" class="btn-solid-gray btn-md" onclick="closeAddAccountModal()">Cancel</button>
                <button type="submit" class="btn-solid-dark btn-md">
                    <span class="material-symbols-rounded">check</span>
                    Save Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================
     MODAL 3: EDIT ACCOUNT MODAL
     ======================================================== -->
<div id="modal-edit-account" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:99999; place-items:center; padding:16px;">
    <div style="background:#ffffff; border-radius:16px; width:100%; max-width:540px; box-shadow:0 25px 50px -12px rgba(15,23,42,0.25); overflow:hidden;">
        <div style="padding:18px 22px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:36px; height:36px; border-radius:8px; background:#e0f2fe; color:#0284c7; display:grid; place-items:center;">
                    <span class="material-symbols-rounded" style="font-size:20px;">edit</span>
                </div>
                <div>
                    <h3 style="font-size:16px; font-weight:800; margin:0; color:#0f172a;">Edit Account Details</h3>
                    <div style="font-size:11.5px; color:#64748b;" id="edit-acc-subtitle">Update account identifiers</div>
                </div>
            </div>
            <button type="button" onclick="closeEditAccountModal()" style="border:none; background:none; color:#94a3b8; cursor:pointer;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <form method="POST" id="form-edit-account" action="" style="padding:22px;">
            <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
            <div class="cf-form-grid">
                <!-- Account Name -->
                <div class="cf-field-group cf-form-full">
                    <label class="cf-field-label">Account Name (Required)</label>
                    <input type="text" name="account_name" id="edit-acc-name" class="cf-input" required>
                </div>

                <!-- Account Type -->
                <div class="cf-field-group">
                    <label class="cf-field-label">Account Type</label>
                    <select name="account_type" id="edit-acc-type" class="cf-select">
                        <option value="bank">Bank Account</option>
                        <option value="wallet">Digital Wallet</option>
                        <option value="cash">Cash Till / Drawer</option>
                        <option value="other">Other Instrument</option>
                    </select>
                </div>

                <!-- Bank Name -->
                <div class="cf-field-group">
                    <label class="cf-field-label">Bank / Institution Name</label>
                    <input type="text" name="bank_name" id="edit-acc-bank" class="cf-input">
                </div>

                <!-- Account / IBAN Number -->
                <div class="cf-field-group cf-form-full">
                    <label class="cf-field-label">Account Number / IBAN Number</label>
                    <input type="text" name="account_number" id="edit-acc-number" class="cf-input">
                </div>

                <!-- Opening Balance -->
                <div class="cf-field-group cf-form-full">
                    <label class="cf-field-label">Opening Base Balance (<?= $currency ?>)</label>
                    <input type="number" step="0.01" min="0" name="opening_balance" id="edit-acc-opening" class="cf-input" style="font-size:15px; font-weight:800;">
                </div>

                <!-- Notes -->
                <div class="cf-field-group cf-form-full">
                    <label class="cf-field-label">Branch / Description / Notes (Optional)</label>
                    <textarea name="notes" id="edit-acc-notes" class="cf-textarea"></textarea>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" class="btn-solid-gray btn-md" onclick="closeEditAccountModal()">Cancel</button>
                <button type="submit" class="btn-solid-blue btn-md">
                    <span class="material-symbols-rounded">save</span>
                    Update Account
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const baseUrl = '<?= defined('BASE_URL') ? BASE_URL : '' ?>';
    const currency = '<?= $currency ?>';

    // 1. Set Balance Modal Functions
    function openSetBalanceModal(accountId = null, accountName = '', currentBal = 0) {
        const modal = document.getElementById('modal-set-balance');
        const sel = document.getElementById('set-bal-account-id');
        const amtInput = document.getElementById('set-bal-amount-input');
        
        if (accountId && sel) {
            sel.value = accountId;
        }
        
        handleAccountSelectChange();
        modal.style.display = 'grid';
        amtInput.focus();
    }

    function closeSetBalanceModal() {
        document.getElementById('modal-set-balance').style.display = 'none';
    }

    function handleAccountSelectChange() {
        const sel = document.getElementById('set-bal-account-id');
        const opt = sel.options[sel.selectedIndex];
        const bal = parseFloat(opt.getAttribute('data-balance') || '0');
        const mode = document.querySelector('input[name="mode"]:checked').value;
        const amtInput = document.getElementById('set-bal-amount-input');
        const hint = document.getElementById('set-bal-calc-hint');
        
        if (mode === 'set_exact') {
            amtInput.value = bal.toFixed(2);
            hint.textContent = `Current registered balance: ${currency} ${bal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        } else {
            hint.textContent = `Current balance: ${currency} ${bal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        }
    }

    function updateModeUI() {
        const mode = document.querySelector('input[name="mode"]:checked').value;
        const lbl = document.getElementById('set-bal-amount-label');
        const amtInput = document.getElementById('set-bal-amount-input');
        
        document.getElementById('mode-label-exact').style.borderColor = (mode === 'set_exact') ? '#0284c7' : '#cbd5e1';
        document.getElementById('mode-label-exact').style.background = (mode === 'set_exact') ? '#f0f9ff' : '#ffffff';
        document.getElementById('mode-label-add').style.borderColor = (mode === 'add_cash') ? '#059669' : '#cbd5e1';
        document.getElementById('mode-label-add').style.background = (mode === 'add_cash') ? '#f0fdf4' : '#ffffff';
        document.getElementById('mode-label-withdraw').style.borderColor = (mode === 'withdraw_cash') ? '#e11d48' : '#cbd5e1';
        document.getElementById('mode-label-withdraw').style.background = (mode === 'withdraw_cash') ? '#fff1f2' : '#ffffff';

        if (mode === 'set_exact') {
            lbl.textContent = `New Exact Total Balance (${currency})`;
            handleAccountSelectChange();
        } else if (mode === 'add_cash') {
            lbl.textContent = `Amount to Add / Inflow (${currency})`;
            amtInput.value = '';
            amtInput.placeholder = 'e.g. 50000';
        } else {
            lbl.textContent = `Amount to Withdraw / Outflow (${currency})`;
            amtInput.value = '';
            amtInput.placeholder = 'e.g. 10000';
        }
    }
    updateModeUI();

    // 2. Add Account Modal
    function openAddAccountModal() {
        document.getElementById('modal-add-account').style.display = 'grid';
    }
    function closeAddAccountModal() {
        document.getElementById('modal-add-account').style.display = 'none';
    }

    // 3. Edit Account Modal
    function openEditAccountModal(acc) {
        if (!acc) return;
        const form = document.getElementById('form-edit-account');
        form.action = `${baseUrl}/cashflow/editAccount/${acc.id}`;
        document.getElementById('edit-acc-name').value = acc.account_name || '';
        document.getElementById('edit-acc-type').value = acc.account_type || 'bank';
        document.getElementById('edit-acc-bank').value = acc.bank_name || '';
        document.getElementById('edit-acc-number').value = acc.account_number || '';
        document.getElementById('edit-acc-opening').value = Number(acc.opening_balance || 0).toFixed(2);
        document.getElementById('edit-acc-notes').value = acc.notes || '';
        document.getElementById('edit-acc-subtitle').textContent = `Editing: ${acc.account_name}`;
        document.getElementById('modal-edit-account').style.display = 'grid';
    }
    function closeEditAccountModal() {
        document.getElementById('modal-edit-account').style.display = 'none';
    }

    // 4. Confirm Deactivate Account
    function confirmDeactivateAccount(accId, accName) {
        if (confirm(`Are you sure you want to deactivate account "${accName}"?\nPrevious transactions will remain safe in your ledger.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `${baseUrl}/cashflow/deleteAccount`;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = 'csrf_token';
            csrf.value = '<?= Security::csrfToken() ?>';
            form.appendChild(csrf);

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = accId;
            form.appendChild(idInput);

            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>

