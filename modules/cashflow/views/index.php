<?php ob_start(); ?>
<?php
$filters = $filters ?? [];
$activeDateFilter = $filters['dateFilter'] ?? 'this_month';
$activeTab = $filters['activeTab'] ?? 'roznamcha';
$currency = $settings['currency_symbol'] ?? 'Rs';
$gateways = $gateways ?? [];
?>

<style>
    /* ========================================================
       PREMIUM CASHFLOW & EXCHANGE SUITE
       100% Solid Buttons with White Text - No Horizontal Overflow
       ======================================================== */
    .cf-wrapper {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Inter", "Manrope", sans-serif;
        color: #0f172a;
        max-width: 1400px;
        margin: 0 auto;
        padding-bottom: 40px;
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

    /* STRICT SOLID BUTTONS WITH 100% WHITE TEXT */
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
    .btn-solid-emerald *,
    .btn-solid-dark *,
    .btn-solid-blue *,
    .btn-solid-rose *,
    .btn-solid-amber *,
    .btn-solid-gray * {
        color: #ffffff !important;
    }

    .btn-solid-emerald {
        background: #059669 !important;
        box-shadow: 0 2px 6px rgba(5, 150, 105, 0.3) !important;
    }
    .btn-solid-emerald:hover {
        background: #047857 !important;
        transform: translateY(-1px);
    }
    .btn-solid-dark {
        background: #0f172a !important;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.25) !important;
    }
    .btn-solid-dark:hover {
        background: #1e293b !important;
        transform: translateY(-1px);
    }
    .btn-solid-blue {
        background: #2563eb !important;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3) !important;
    }
    .btn-solid-blue:hover {
        background: #1d4ed8 !important;
        transform: translateY(-1px);
    }
    .btn-solid-rose {
        background: #e11d48 !important;
        box-shadow: 0 2px 6px rgba(225, 29, 72, 0.3) !important;
    }
    .btn-solid-rose:hover {
        background: #be123c !important;
        transform: translateY(-1px);
    }
    .btn-solid-gray {
        background: #475569 !important;
        box-shadow: 0 2px 4px rgba(71, 85, 105, 0.25) !important;
    }
    .btn-solid-gray:hover {
        background: #334155 !important;
        transform: translateY(-1px);
    }

    .btn-md {
        padding: 10px 18px !important;
        font-size: 13px !important;
    }
    .btn-sm {
        padding: 6px 12px !important;
        font-size: 12px !important;
        border-radius: 6px !important;
    }
    .btn-xs {
        padding: 5px 10px !important;
        font-size: 11px !important;
        border-radius: 5px !important;
    }

    /* 4 High-Impact KPI Cards */
    .cf-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    @media (max-width: 1080px) {
        .cf-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .cf-kpi-grid { grid-template-columns: 1fr; }
    }

    .cf-kpi-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
        border-top: 4px solid #cbd5e1;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .cf-kpi-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
    }
    .kpi-cash { border-top-color: #059669; }
    .kpi-digital { border-top-color: #2563eb; }
    .kpi-recv { border-top-color: #d97706; }
    .kpi-pay { border-top-color: #e11d48; }

    .cf-kpi-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    .cf-kpi-title {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #475569;
    }
    .cf-kpi-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        color: #ffffff !important;
    }
    .cf-kpi-icon * {
        color: #ffffff !important;
        font-size: 20px;
    }
    .icon-cash { background: #059669; }
    .icon-digital { background: #2563eb; }
    .icon-recv { background: #d97706; }
    .icon-pay { background: #e11d48; }

    .cf-kpi-number {
        font-size: 24px;
        font-weight: 900;
        letter-spacing: -0.03em;
        line-height: 1.1;
        font-feature-settings: "tnum";
        margin-bottom: 4px;
    }
    .kpi-cash .cf-kpi-number { color: #047857; }
    .kpi-digital .cf-kpi-number { color: #1d4ed8; }
    .kpi-recv .cf-kpi-number { color: #b45309; }
    .kpi-pay .cf-kpi-number { color: #be123c; }

    .cf-kpi-footer {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
    }

    /* ZERO-OVERFLOW EXECUTIVE MONEY EXCHANGE PANEL */
    .cf-exchange-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        padding: 22px 24px;
        margin-bottom: 26px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
        border-left: 5px solid #059669;
        overflow: visible !important;
    }
    .cf-exchange-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .cf-exchange-heading {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .cf-exchange-icon-badge {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #ecfdf5;
        color: #059669;
        display: grid;
        place-items: center;
    }
    .cf-exchange-icon-badge .material-symbols-rounded {
        font-size: 20px;
        color: #059669 !important;
    }
    .cf-exchange-heading h2 {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .cf-exchange-heading span {
        font-size: 12px;
        color: #64748b;
        margin-left: 4px;
        font-weight: 500;
    }

    /* 2-Row Form Structure to avoid squeezing and overflow */
    .cf-form-rows {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .cf-form-row-1 {
        display: grid;
        grid-template-columns: 1.5fr 1.3fr 1fr;
        gap: 14px;
    }
    .cf-form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1.3fr 1.3fr 180px;
        gap: 14px;
        align-items: flex-end;
    }
    @media (max-width: 992px) {
        .cf-form-row-1 { grid-template-columns: 1fr 1fr; }
        .cf-form-row-2 { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 640px) {
        .cf-form-row-1 { grid-template-columns: 1fr; }
        .cf-form-row-2 { grid-template-columns: 1fr; }
    }

    .cf-field-group {
        display: flex;
        flex-direction: column;
        position: relative;
    }
    .cf-field-label {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #334155;
        margin-bottom: 6px;
    }
    .cf-input, .cf-select {
        height: 40px;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #0f172a;
        outline: none;
        box-sizing: border-box;
        width: 100%;
        transition: all 0.15s ease;
    }
    .cf-input:focus, .cf-select:focus {
        background: #ffffff;
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
    }

    /* SEARCHABLE GATEWAY COMBOBOX */
    .cf-gw-combobox {
        position: relative;
        width: 100%;
    }
    .cf-gw-trigger {
        height: 40px;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 700;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #0f172a;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        box-sizing: border-box;
        transition: all 0.15s ease;
        user-select: none;
    }
    .cf-gw-trigger:hover {
        border-color: #94a3b8;
    }
    .cf-gw-trigger.active {
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
    }
    .cf-gw-selected {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .cf-gw-selected .material-symbols-rounded {
        color: #2563eb;
        font-size: 18px;
    }

    .cf-gw-dropdown {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.14);
        z-index: 1000;
        padding: 8px;
        display: none;
    }
    .cf-gw-dropdown.open {
        display: block;
        animation: fadeIn 0.15s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .cf-gw-search-box {
        position: relative;
        margin-bottom: 8px;
    }
    .cf-gw-search-input {
        width: 100%;
        height: 34px;
        padding: 6px 10px 6px 30px;
        font-size: 12px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        box-sizing: border-box;
        outline: none;
    }
    .cf-gw-search-input:focus {
        border-color: #2563eb;
        background: #ffffff;
    }
    .cf-gw-search-icon {
        position: absolute;
        left: 8px;
        top: 8px;
        font-size: 17px;
        color: #94a3b8;
        pointer-events: none;
    }

    .cf-gw-list {
        max-height: 180px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .cf-gw-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 10px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
        cursor: pointer;
        transition: background 0.12s ease;
    }
    .cf-gw-item:hover {
        background: #f1f5f9;
    }
    .cf-gw-item.selected {
        background: #e0f2fe;
        color: #0369a1;
        font-weight: 700;
    }
    .cf-gw-item-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .cf-gw-item-left .material-symbols-rounded {
        font-size: 16px;
        color: #64748b;
    }
    .cf-gw-del-btn {
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        display: grid;
        place-items: center;
        transition: all 0.12s ease;
    }
    .cf-gw-del-btn:hover {
        background: #fee2e2;
        color: #ef4444;
    }
    .cf-gw-del-btn .material-symbols-rounded {
        font-size: 16px;
    }

    .cf-gw-add-prompt {
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px solid #f1f5f9;
    }

    /* Tabs Bar */
    .cf-nav-tabs {
        display: flex;
        gap: 8px;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 20px;
    }
    .cf-nav-btn {
        background: transparent;
        border: none;
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 800;
        color: #64748b;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: -2px;
        transition: all 0.15s ease;
    }
    .cf-nav-btn:hover {
        color: #0f172a;
    }
    .cf-nav-btn.active {
        color: #0f172a;
        border-bottom-color: #0f172a;
    }
    .cf-nav-btn .material-symbols-rounded {
        font-size: 20px;
    }

    /* Filter Bar */
    .cf-filter-bar {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 18px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }
    .cf-preset-pills {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }
    .pill-btn {
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 800;
        border-radius: 6px;
        text-decoration: none !important;
        display: inline-block;
        transition: all 0.12s ease;
        color: #ffffff !important;
    }
    .pill-btn.active {
        background: #2563eb !important;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.35);
    }
    .pill-btn.inactive {
        background: #475569 !important;
    }
    .pill-btn.inactive:hover {
        background: #334155 !important;
    }

    /* Data Table */
    .cf-table-container {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
    }
    .cf-data-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    .cf-data-table th {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 800;
        letter-spacing: 0.04em;
        color: #475569;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 16px;
    }
    .cf-data-table td {
        padding: 13px 16px;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .cf-data-table tr:hover td {
        background: #f8fafc;
    }

    /* Tiny Micro Badges */
    .cf-micro-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .badge-exchange { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .badge-transfer { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .badge-adj-in { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
    .badge-adj-out { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    /* Split Panels: Receivables & Payables */
    .cf-split-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    @media (max-width: 900px) {
        .cf-split-grid { grid-template-columns: 1fr; }
    }
    .cf-ledger-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 22px 24px;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
    }
    .panel-recv { border-top: 4px solid #d97706; }
    .panel-pay { border-top: 4px solid #e11d48; }

    .cf-ledger-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .cf-ledger-title {
        font-size: 15px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .cf-ledger-sub {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
    }
    .cf-ledger-total {
        font-size: 18px;
        font-weight: 900;
        font-feature-settings: 'tnum';
    }

    .cf-ledger-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .cf-ledger-row:last-child {
        border-bottom: none;
    }
</style>

<div class="cf-wrapper">
    <!-- Topbar -->
    <div class="cf-topbar">
        <div class="cf-title-area">
            <div class="cf-main-icon">
                <span class="material-symbols-rounded">account_balance_wallet</span>
            </div>
            <div>
                <h1>Cashflow & Counter Disbursal</h1>
                <p>Live reconciliation of physical cash drawer, digital banking, and business obligations.</p>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <!-- Dedicated Balances Manager Button -->
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/balances" class="btn-solid-emerald btn-md" style="text-decoration:none;">
                <span class="material-symbols-rounded">tune</span>
                Manage Cash & Account Balances
            </a>

            <!-- Solid Blue Export Button with White Text -->
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/export?<?= http_build_query($filters) ?>" class="btn-solid-blue btn-md">
                <span class="material-symbols-rounded">download</span>
                Export Ledger Statement
            </a>
        </div>
    </div>

    <!-- 4 High Impact Colorful KPI Cards -->
    <div class="cf-kpi-grid">
        <!-- 1. Physical Cash in Drawer -->
        <div class="cf-kpi-box kpi-cash">
            <div class="cf-kpi-header">
                <span class="cf-kpi-title">Cash in Drawer</span>
                <div class="cf-kpi-icon icon-cash">
                    <span class="material-symbols-rounded">point_of_sale</span>
                </div>
            </div>
            <div class="cf-kpi-number"><?= $currency ?> <?= number_format($kpis['cash_in_hand'], 2) ?></div>
            <div class="cf-kpi-footer">Physical Currency in Counter Till</div>
        </div>

        <!-- 2. Digital Accounts & Banking -->
        <div class="cf-kpi-box kpi-digital">
            <div class="cf-kpi-header">
                <span class="cf-kpi-title">Digital Accounts</span>
                <div class="cf-kpi-icon icon-digital">
                    <span class="material-symbols-rounded">account_balance</span>
                </div>
            </div>
            <div class="cf-kpi-number"><?= $currency ?> <?= number_format($kpis['online_balance'], 2) ?></div>
            <div class="cf-kpi-footer">JazzCash, EasyPaisa & Bank Funds Total</div>
        </div>

        <!-- 3. Accounts Receivable -->
        <div class="cf-kpi-box kpi-recv">
            <div class="cf-kpi-header">
                <span class="cf-kpi-title">Accounts Receivable</span>
                <div class="cf-kpi-icon icon-recv">
                    <span class="material-symbols-rounded">call_received</span>
                </div>
            </div>
            <div class="cf-kpi-number"><?= $currency ?> <?= number_format($kpis['receivables'], 2) ?></div>
            <div class="cf-kpi-footer">Customer Credit Balances Awaiting Collection</div>
        </div>

        <!-- 4. Accounts Payable -->
        <div class="cf-kpi-box kpi-pay">
            <div class="cf-kpi-header">
                <span class="cf-kpi-title">Accounts Payable</span>
                <div class="cf-kpi-icon icon-pay">
                    <span class="material-symbols-rounded">call_made</span>
                </div>
            </div>
            <div class="cf-kpi-number"><?= $currency ?> <?= number_format($kpis['payables'], 2) ?></div>
            <div class="cf-kpi-footer">Supplier Invoices Due for Settlement</div>
        </div>
    </div>

    <!-- DIRECT SINGLE FORM: MONEY EXCHANGE (2-Row Zero Overflow Layout) -->
    <div class="cf-exchange-card">
        <div class="cf-exchange-top">
            <div class="cf-exchange-heading">
                <div class="cf-exchange-icon-badge">
                    <span class="material-symbols-rounded">currency_exchange</span>
                </div>
                <div>
                    <h2>Digital Currency & Cash Disbursal (Money Exchange)</h2>
                    <span>Instant counter entry: customer paid online & took cash, or customer gave cash & took online</span>
                </div>
            </div>
        </div>

        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/exchange" method="POST" id="cf-exchange-form">
            <?= Security::csrfField() ?>
            <div class="cf-form-rows">
                <!-- ROW 1: Flow, Gateway, Amount -->
                <div class="cf-form-row-1">
                    <!-- 1. Flow Direction (Clean Text, No Emoji Ball) -->
                    <div class="cf-field-group">
                        <label class="cf-field-label">Transaction Flow *</label>
                        <select name="direction" class="cf-select" required style="font-weight:700;">
                            <option value="online_to_cash">Online Received ➔ Cash Given to Customer</option>
                            <option value="cash_to_online">Cash Received ➔ Online Sent to Customer</option>
                        </select>
                    </div>

                    <!-- 2. Searchable Digital Gateway Combobox (Add / Remove Enabled) -->
                    <div class="cf-field-group">
                        <label class="cf-field-label">Digital Gateway *</label>
                        
                        <div class="cf-gw-combobox" id="cf_gw_combobox">
                            <!-- Selected Trigger Box -->
                            <div class="cf-gw-trigger" id="cf_gw_trigger" onclick="toggleGwDropdown()">
                                <div class="cf-gw-selected">
                                    <span class="material-symbols-rounded">account_balance_wallet</span>
                                    <span id="cf_gw_label">JazzCash</span>
                                </div>
                                <span class="material-symbols-rounded" style="color:#64748b; font-size:18px;">expand_more</span>
                            </div>

                            <!-- Hidden Form Fields -->
                            <input type="hidden" name="channel" id="cf_input_channel" value="JazzCash" required>
                            <input type="hidden" name="online_account_id" id="cf_input_account_id" value="">

                            <!-- Floating Dropdown Panel -->
                            <div class="cf-gw-dropdown" id="cf_gw_dropdown">
                                <div class="cf-gw-search-box">
                                    <span class="material-symbols-rounded cf-gw-search-icon">search</span>
                                    <input type="text" id="cf_gw_search" class="cf-gw-search-input" placeholder="Search or type new gateway..." oninput="filterGateways(this.value)" onkeydown="onGwSearchKey(event)">
                                </div>

                                <div class="cf-gw-list" id="cf_gw_list">
                                    <?php 
                                    $firstGwSelected = false;
                                    foreach ($gateways as $gw): 
                                    ?>
                                        <div class="cf-gw-item <?= !$firstGwSelected ? 'selected' : '' ?>" 
                                             data-id="<?= (int)$gw['id'] ?>" 
                                             data-name="<?= Security::escape($gw['account_name']) ?>" 
                                             onclick="selectGateway(<?= (int)$gw['id'] ?>, '<?= Security::escape($gw['account_name']) ?>')">
                                            <div class="cf-gw-item-left">
                                                <span class="material-symbols-rounded">account_balance_wallet</span>
                                                <span class="cf-gw-item-name"><?= Security::escape($gw['account_name']) ?></span>
                                            </div>
                                            <button type="button" class="cf-gw-del-btn" onclick="deleteGateway(event, <?= (int)$gw['id'] ?>, '<?= Security::escape($gw['account_name']) ?>')" title="Remove this gateway">
                                                <span class="material-symbols-rounded">delete</span>
                                            </button>
                                        </div>
                                    <?php 
                                        $firstGwSelected = true;
                                    endforeach; 
                                    ?>
                                </div>

                                <div class="cf-gw-add-prompt" id="cf_gw_add_prompt" style="display:none;">
                                    <button type="button" class="btn-solid-emerald btn-xs" id="cf_gw_add_btn" onclick="addNewGateway()" style="width:100%; height:32px;">
                                        <span class="material-symbols-rounded" style="font-size:16px;">add</span>
                                        <span id="cf_gw_add_text">Add New Gateway</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Amount -->
                    <div class="cf-field-group">
                        <label class="cf-field-label">Amount (<?= $currency ?>) *</label>
                        <input type="number" step="0.01" name="amount" class="cf-input" placeholder="10000.00" required style="font-weight:800; font-size:14px;">
                    </div>
                </div>

                <!-- ROW 2: Fee, Customer Name, TID, Commit Button -->
                <div class="cf-form-row-2">
                    <!-- 4. Fee / Commission -->
                    <div class="cf-field-group">
                        <label class="cf-field-label">Fee / Profit (<?= $currency ?>)</label>
                        <input type="number" step="0.01" name="fee_amount" class="cf-input" placeholder="0.00" value="0.00">
                    </div>

                    <!-- 5. Customer Name -->
                    <div class="cf-field-group">
                        <label class="cf-field-label">Customer Name</label>
                        <input type="text" name="party_name" class="cf-input" placeholder="e.g. Ali Ahmed">
                    </div>

                    <!-- 6. Account Number / IBAN Number / TID -->
                    <div class="cf-field-group">
                        <label class="cf-field-label">Account # / IBAN Number / TID (Optional)</label>
                        <input type="text" name="reference_no" class="cf-input" placeholder="e.g. PK36MEZN... / TID #984729">
                    </div>

                    <!-- 7. Solid Emerald Submit Button with White Text -->
                    <div>
                        <button type="submit" class="btn-solid-emerald btn-md" style="height:40px; width:100%;">
                            <span class="material-symbols-rounded">check</span>
                            Commit Exchange
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Navigation Tabs -->
    <div class="cf-nav-tabs">
        <button class="cf-nav-btn <?= $activeTab === 'roznamcha' ? 'active' : '' ?>" onclick="switchTab('roznamcha')">
            <span class="material-symbols-rounded">receipt_long</span>
            Transaction Journal
        </button>
        <button class="cf-nav-btn <?= $activeTab === 'dues' ? 'active' : '' ?>" onclick="switchTab('dues')">
            <span class="material-symbols-rounded">balance</span>
            Receivables & Payables Ledger
        </button>
    </div>

    <!-- TAB 1: Transaction Journal (Cashflow Stream) -->
    <div id="tab-content-roznamcha" style="<?= $activeTab === 'roznamcha' ? '' : 'display:none;' ?>">
        <!-- Filter Bar -->
        <div class="cf-filter-bar">
            <form method="GET" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/index" id="cf-filter-form" style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; width:100%;">
                <input type="hidden" name="tab" value="roznamcha">

                <!-- Solid Date Presets with 100% White Text -->
                <div class="cf-preset-pills">
                    <?php
                    $presets = [
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        'this_week' => 'This Week',
                        'this_month' => 'This Month',
                        'all' => 'All Time'
                    ];
                    foreach ($presets as $pKey => $pLabel): ?>
                        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/index?date_filter=<?= $pKey ?>&tab=roznamcha" 
                           class="pill-btn <?= $activeDateFilter === $pKey ? 'active' : 'inactive' ?>">
                            <?= $pLabel ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Custom Range -->
                <div style="display:flex; align-items:center; gap:5px;">
                    <input type="date" name="start_date" value="<?= Security::escape($filters['from']) ?>" class="cf-input" style="width:130px; height:34px; font-size:12px;">
                    <span style="color:#94a3b8; font-size:12px; font-weight:700;">to</span>
                    <input type="date" name="end_date" value="<?= Security::escape($filters['to']) ?>" class="cf-input" style="width:130px; height:34px; font-size:12px;">
                    <input type="hidden" name="date_filter" value="custom">
                </div>

                <!-- Type Filter -->
                <select name="type" class="cf-select" style="width:auto; height:34px; font-size:12px;" onchange="this.form.submit()">
                    <option value="">All Transactions</option>
                    <option value="money_exchange" <?= ($filters['typeFilter'] ?? '') === 'money_exchange' ? 'selected' : '' ?>>Money Exchange</option>
                    <option value="transfer" <?= ($filters['typeFilter'] ?? '') === 'transfer' ? 'selected' : '' ?>>Fund Transfer</option>
                    <option value="adjustment_in" <?= ($filters['typeFilter'] ?? '') === 'adjustment_in' ? 'selected' : '' ?>>Capital Inflow</option>
                    <option value="adjustment_out" <?= ($filters['typeFilter'] ?? '') === 'adjustment_out' ? 'selected' : '' ?>>Capital Outflow</option>
                </select>

                <!-- Search Input -->
                <div style="flex:1; min-width:180px;">
                    <input type="text" name="q" value="<?= Security::escape($filters['q']) ?>" placeholder="Search Account #, IBAN, TID, customer..." class="cf-input" style="height:34px; font-size:12px; width:100%;">
                </div>

                <!-- Solid Dark Filter Button with White Text -->
                <button type="submit" class="btn-solid-dark btn-sm" style="height:34px;">
                    <span class="material-symbols-rounded" style="font-size:16px;">filter_alt</span>
                    Filter
                </button>

                <?php if (!empty($filters['q']) || $activeDateFilter !== 'this_month' || !empty($filters['typeFilter'])): ?>
                    <!-- Solid Gray Reset Button with White Text -->
                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/index?tab=roznamcha" class="btn-solid-gray btn-sm" style="height:34px;">
                        <span class="material-symbols-rounded" style="font-size:16px;">restart_alt</span>
                        Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Transactions Stream Table -->
        <div class="cf-table-container">
            <div style="overflow-x:auto;">
                <table class="cf-data-table">
                    <thead>
                        <tr>
                            <th style="width:130px;">Timestamp</th>
                            <th style="width:130px;">Type</th>
                            <th>Pathway</th>
                            <th>Counterparty & Reference</th>
                            <th style="text-align:right;">Amount</th>
                            <th style="text-align:right;">Fee</th>
                            <th>Handler</th>
                            <th style="width:120px; text-align:center;">Voucher & Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:50px 20px; color:#94a3b8;">
                                    <span class="material-symbols-rounded" style="font-size:40px; color:#cbd5e1; display:block; margin-bottom:10px;">receipt_long</span>
                                    No cashflow journal entries recorded for the selected period.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                                <tr>
                                    <td style="font-size:12px; color:#475569; font-feature-settings: 'tnum';">
                                        <strong><?= date('d M Y', strtotime($tx['transaction_date'])) ?></strong><br>
                                        <small style="color:#94a3b8;"><?= date('h:i A', strtotime($tx['transaction_date'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($tx['transaction_type'] === 'money_exchange'): ?>
                                            <span class="cf-micro-badge badge-exchange">
                                                <span class="material-symbols-rounded" style="font-size:12px;">currency_exchange</span>
                                                Exchange
                                            </span>
                                        <?php elseif ($tx['transaction_type'] === 'transfer'): ?>
                                            <span class="cf-micro-badge badge-transfer">
                                                <span class="material-symbols-rounded" style="font-size:12px;">sync_alt</span>
                                                Transfer
                                            </span>
                                        <?php elseif ($tx['transaction_type'] === 'adjustment_in'): ?>
                                            <span class="cf-micro-badge badge-adj-in">
                                                <span class="material-symbols-rounded" style="font-size:12px;">arrow_downward</span>
                                                Inflow
                                            </span>
                                        <?php elseif ($tx['transaction_type'] === 'adjustment_out'): ?>
                                            <span class="cf-micro-badge badge-adj-out">
                                                <span class="material-symbols-rounded" style="font-size:12px;">arrow_upward</span>
                                                Outflow
                                            </span>
                                        <?php else: ?>
                                            <span class="cf-micro-badge badge-transfer">
                                                <?= Security::escape(ucfirst(str_replace('_', ' ', $tx['transaction_type']))) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size:12px; font-weight:700; display:flex; align-items:center; gap:6px;">
                                            <?php if ($tx['from_account_name']): ?>
                                                <span style="color:#be123c;"><?= Security::escape($tx['from_account_name']) ?></span>
                                            <?php else: ?>
                                                <span style="color:#64748b;">External</span>
                                            <?php endif; ?>

                                            <span class="material-symbols-rounded" style="font-size:13px; color:#94a3b8;">arrow_forward</span>

                                            <?php if ($tx['to_account_name']): ?>
                                                <span style="color:#047857;"><?= Security::escape($tx['to_account_name']) ?></span>
                                            <?php else: ?>
                                                <span style="color:#64748b;">External</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($tx['description'])): ?>
                                            <div style="font-size:11px; color:#64748b; margin-top:2px;"><?= Security::escape($tx['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($tx['party_name'])): ?>
                                            <strong style="color:#0f172a; font-size:12px;"><?= Security::escape($tx['party_name']) ?></strong>
                                            <?php if (!empty($tx['party_phone'])): ?>
                                                <span style="font-size:11px; color:#64748b;">(<?= Security::escape($tx['party_phone']) ?>)</span>
                                            <?php endif; ?>
                                            <br>
                                        <?php endif; ?>
                                        <?php if (!empty($tx['reference_no'])): ?>
                                            <span style="font-size:10px; padding:2px 6px; background:#f1f5f9; border-radius:4px; color:#475569; font-family:monospace; font-weight:700;">
                                                TID: <?= Security::escape($tx['reference_no']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right; font-weight:800; font-size:13px; color:#0f172a; font-feature-settings: 'tnum';">
                                        <?= $currency ?> <?= number_format($tx['amount'], 2) ?>
                                    </td>
                                    <td style="text-align:right; font-size:12px; color:<?= $tx['fee_amount'] > 0 ? '#047857' : '#94a3b8' ?>; font-feature-settings: 'tnum';">
                                        <?= $tx['fee_amount'] > 0 ? '+ ' . $currency . ' ' . number_format($tx['fee_amount'], 2) : '-' ?>
                                    </td>
                                    <td style="font-size:12px; color:#64748b;">
                                        <?= Security::escape($tx['cashier_name'] ?: 'Staff') ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <div style="display:inline-flex; align-items:center; gap:4px;">
                                            <!-- View Slip Voucher Button -->
                                            <button type="button" class="btn-solid-emerald btn-xs" onclick='viewVoucherModal(<?= json_encode($tx) ?>)' title="View Slip Voucher" style="padding:0 6px;">
                                                <span class="material-symbols-rounded" style="font-size:15px;">visibility</span>
                                            </button>
                                            <!-- Print Slip Voucher Button -->
                                            <button type="button" class="btn-solid-blue btn-xs" onclick='printVoucherSlip(<?= json_encode($tx) ?>)' title="Print Slip Voucher" style="padding:0 6px;">
                                                <span class="material-symbols-rounded" style="font-size:15px;">print</span>
                                            </button>
                                            <!-- Delete / Reverse Transaction Button -->
                                            <button type="button" class="btn-solid-rose btn-xs" onclick='confirmDeleteTx(<?= (int)$tx['id'] ?>, <?= (float)$tx['amount'] ?>, "<?= Security::escape($tx['transaction_type']) ?>")' title="Reverse Transaction" style="padding:0 6px;">
                                                <span class="material-symbols-rounded" style="font-size:15px;">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: Receivables & Payables Ledger -->
    <div id="tab-content-dues" style="<?= $activeTab === 'dues' ? '' : 'display:none;' ?>">
        <div class="cf-split-grid">
            <!-- Left: Accounts Receivable -->
            <div class="cf-ledger-panel panel-recv">
                <div class="cf-ledger-header">
                    <div>
                        <div class="cf-ledger-title" style="color:#b45309;">
                            <span class="material-symbols-rounded">call_received</span>
                            Accounts Receivable (Customer Dues)
                        </div>
                        <div class="cf-ledger-sub">Outstanding client balances awaiting collection</div>
                    </div>
                    <div class="cf-ledger-total" style="color:#b45309;">
                        <?= $currency ?> <?= number_format($kpis['receivables'], 2) ?>
                    </div>
                </div>

                <?php if (empty($receivablesList)): ?>
                    <div style="text-align:center; padding:40px 20px; color:#94a3b8; font-size:13px;">
                        <span class="material-symbols-rounded" style="font-size:36px; color:#cbd5e1; display:block; margin-bottom:8px;">check_circle</span>
                        All customer credit accounts are fully reconciled.
                    </div>
                <?php else: ?>
                    <?php foreach ($receivablesList as $rec): ?>
                        <div class="cf-ledger-row">
                            <div>
                                <strong style="font-size:13px; color:#0f172a; display:block;"><?= Security::escape($rec['name'] ?: 'Walk-in Customer') ?></strong>
                                <span style="font-size:11px; color:#64748b;"><?= Security::escape($rec['phone'] ?: 'No Phone') ?> &bull; <?= (int)$rec['pending_invoices_count'] ?> Invoices</span>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:14px; font-weight:800; color:#b45309; font-feature-settings: 'tnum';">
                                    <?= $currency ?> <?= number_format($rec['total_due'], 2) ?>
                                </div>
                                <div style="margin-top:6px; display:flex; gap:6px; justify-content:flex-end;">
                                    <?php if (!empty($rec['phone'])): ?>
                                        <!-- Solid WhatsApp Button with White Text -->
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $rec['phone']) ?>" target="_blank" class="btn-solid-emerald btn-xs" title="Send WhatsApp Reminder">
                                            <span class="material-symbols-rounded" style="font-size:14px;">chat</span>
                                            WhatsApp
                                        </a>
                                        <!-- Solid Dark Call Button with White Text -->
                                        <a href="tel:<?= Security::escape($rec['phone']) ?>" class="btn-solid-dark btn-xs" title="Call Customer">
                                            <span class="material-symbols-rounded" style="font-size:14px;">call</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div style="margin-top:20px; text-align:center;">
                        <!-- Solid Blue Action Button with White Text -->
                        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/credits/index" class="btn-solid-blue btn-md" style="width:100%;">
                            <span class="material-symbols-rounded">menu_book</span>
                            Open Credit Register &rarr;
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Accounts Payable -->
            <div class="cf-ledger-panel panel-pay">
                <div class="cf-ledger-header">
                    <div>
                        <div class="cf-ledger-title" style="color:#be123c;">
                            <span class="material-symbols-rounded">call_made</span>
                            Accounts Payable (Supplier Obligations)
                        </div>
                        <div class="cf-ledger-sub">Pending vendor balances awaiting settlement</div>
                    </div>
                    <div class="cf-ledger-total" style="color:#be123c;">
                        <?= $currency ?> <?= number_format($kpis['payables'], 2) ?>
                    </div>
                </div>

                <?php if (empty($payablesList)): ?>
                    <div style="text-align:center; padding:40px 20px; color:#94a3b8; font-size:13px;">
                        <span class="material-symbols-rounded" style="font-size:36px; color:#cbd5e1; display:block; margin-bottom:8px;">check_circle</span>
                        All supplier procurement obligations are cleared.
                    </div>
                <?php else: ?>
                    <?php foreach ($payablesList as $pay): ?>
                        <div class="cf-ledger-row">
                            <div>
                                <strong style="font-size:13px; color:#0f172a; display:block;"><?= Security::escape($pay['name']) ?></strong>
                                <span style="font-size:11px; color:#64748b;"><?= Security::escape($pay['company_name'] ?: $pay['phone']) ?></span>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:14px; font-weight:800; color:#be123c; font-feature-settings: 'tnum';">
                                    <?= $currency ?> <?= number_format($pay['current_balance'], 2) ?>
                                </div>
                                <div style="margin-top:6px; display:flex; gap:6px; justify-content:flex-end;">
                                    <?php if (!empty($pay['phone'])): ?>
                                        <a href="tel:<?= Security::escape($pay['phone']) ?>" class="btn-solid-dark btn-xs" title="Call Supplier">
                                            <span class="material-symbols-rounded" style="font-size:14px;">call</span>
                                        </a>
                                    <?php endif; ?>
                                    <!-- Solid Rose Settle Button with White Text -->
                                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchase" class="btn-solid-rose btn-xs" title="Settle Invoice Balance">
                                        <span class="material-symbols-rounded" style="font-size:14px;">payments</span>
                                        Settle
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div style="margin-top:20px; text-align:center;">
                        <!-- Solid Dark Action Button with White Text -->
                        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchase" class="btn-solid-dark btn-md" style="width:100%;">
                            <span class="material-symbols-rounded">receipt_long</span>
                            Manage Supplier Invoices &rarr;
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const baseUrl = '<?= defined('BASE_URL') ? BASE_URL : '' ?>';
    const csrfToken = '<?= Security::generateCsrfToken() ?>';

    // Tab switching
    function switchTab(tabName) {
        document.querySelectorAll('.cf-nav-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('[id^="tab-content-"]').forEach(el => el.style.display = 'none');

        const activeContent = document.getElementById('tab-content-' + tabName);
        if (activeContent) {
            activeContent.style.display = 'block';
        }
        event.currentTarget?.classList.add('active');

        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);
    }

    // Custom Gateway Dropdown Controller
    function toggleGwDropdown() {
        const dropdown = document.getElementById('cf_gw_dropdown');
        const trigger = document.getElementById('cf_gw_trigger');
        const isOpen = dropdown.classList.contains('open');

        if (isOpen) {
            closeGwDropdown();
        } else {
            dropdown.classList.add('open');
            trigger.classList.add('active');
            const searchInput = document.getElementById('cf_gw_search');
            searchInput.value = '';
            filterGateways('');
            setTimeout(() => searchInput.focus(), 50);
        }
    }

    function closeGwDropdown() {
        const dropdown = document.getElementById('cf_gw_dropdown');
        const trigger = document.getElementById('cf_gw_trigger');
        if (dropdown) dropdown.classList.remove('open');
        if (trigger) trigger.classList.remove('active');
    }

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        const combobox = document.getElementById('cf_gw_combobox');
        if (combobox && !combobox.contains(e.target)) {
            closeGwDropdown();
        }
    });

    // Select Gateway
    function selectGateway(id, name) {
        document.getElementById('cf_gw_label').textContent = name;
        document.getElementById('cf_input_channel').value = name;
        document.getElementById('cf_input_account_id').value = id || '';

        document.querySelectorAll('.cf-gw-item').forEach(el => {
            el.classList.remove('selected');
            if (el.dataset.name.toLowerCase() === name.toLowerCase()) {
                el.classList.add('selected');
            }
        });

        closeGwDropdown();
    }

    // Filter gateways or show "+ Add"
    function filterGateways(term) {
        const q = term.trim().toLowerCase();
        const items = document.querySelectorAll('.cf-gw-item');
        let exactMatch = false;
        let visibleCount = 0;

        items.forEach(item => {
            const name = item.dataset.name.toLowerCase();
            if (name.includes(q)) {
                item.style.display = 'flex';
                visibleCount++;
                if (name === q) exactMatch = true;
            } else {
                item.style.display = 'none';
            }
        });

        const addPrompt = document.getElementById('cf_gw_add_prompt');
        const addBtnText = document.getElementById('cf_gw_add_text');

        if (q.length > 0 && !exactMatch) {
            addPrompt.style.display = 'block';
            addBtnText.textContent = `+ Add "${term.trim()}"`;
        } else {
            addPrompt.style.display = 'none';
        }
    }

    function onGwSearchKey(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const addPrompt = document.getElementById('cf_gw_add_prompt');
            if (addPrompt && addPrompt.style.display !== 'none') {
                addNewGateway();
            } else {
                const firstVisible = Array.from(document.querySelectorAll('.cf-gw-item')).find(el => el.style.display !== 'none');
                if (firstVisible) {
                    firstVisible.click();
                }
            }
        } else if (e.key === 'Escape') {
            closeGwDropdown();
        }
    }

    // Add New Gateway to Database via AJAX
    function addNewGateway() {
        const searchInput = document.getElementById('cf_gw_search');
        const name = searchInput.value.trim();
        if (!name) return;

        const formData = new FormData();
        formData.append('name', name);
        formData.append('csrf_token', csrfToken);

        fetch(`${baseUrl}/cashflow/addGateway`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Add to DOM list
                const list = document.getElementById('cf_gw_list');
                const newItem = document.createElement('div');
                newItem.className = 'cf-gw-item selected';
                newItem.dataset.id = data.id;
                newItem.dataset.name = data.name;
                newItem.onclick = function() { selectGateway(data.id, data.name); };
                newItem.innerHTML = `
                    <div class="cf-gw-item-left">
                        <span class="material-symbols-rounded">account_balance_wallet</span>
                        <span class="cf-gw-item-name">${data.name}</span>
                    </div>
                    <button type="button" class="cf-gw-del-btn" onclick="deleteGateway(event, ${data.id}, '${data.name}')" title="Remove this gateway">
                        <span class="material-symbols-rounded">delete</span>
                    </button>
                `;
                list.appendChild(newItem);

                // Select it
                selectGateway(data.id, data.name);
            } else {
                alert(data.message || 'Could not add gateway.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Failed to save gateway. Please try again.');
        });
    }

    // Delete / Remove Gateway from Database via AJAX
    function deleteGateway(event, id, name) {
        event.stopPropagation();
        if (!confirm(`Are you sure you want to remove "${name}" from gateways?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', csrfToken);

        fetch(`${baseUrl}/cashflow/deleteGateway`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Remove from DOM
                const targetItem = Array.from(document.querySelectorAll('.cf-gw-item')).find(el => el.dataset.id == id);
                if (targetItem) targetItem.remove();

                // If currently selected was deleted, select first available
                const currentChannel = document.getElementById('cf_input_channel').value;
                if (currentChannel.toLowerCase() === name.toLowerCase()) {
                    const firstRemaining = document.querySelector('.cf-gw-item');
                    if (firstRemaining) {
                        selectGateway(firstRemaining.dataset.id, firstRemaining.dataset.name);
                    } else {
                        document.getElementById('cf_gw_label').textContent = 'Select Gateway';
                        document.getElementById('cf_input_channel').value = '';
                        document.getElementById('cf_input_account_id').value = '';
                    }
                }
            } else {
                alert(data.message || 'Could not remove gateway.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Failed to remove gateway.');
        });
    }

    // Initialize first gateway selection
    document.addEventListener('DOMContentLoaded', function() {
        const first = document.querySelector('.cf-gw-item');
        if (first) {
            selectGateway(first.dataset.id, first.dataset.name);
        }
    });

    // Print Voucher Slip
    function printVoucherSlip(tx) {
        const storeName = <?= json_encode($settings['company_name'] ?? 'DevAltum POS') ?>;
        const currency = '<?= $currency ?>';

        const printWindow = window.open('', '_blank', 'width=420,height=600');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Transaction Voucher #${tx.id}</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", monospace; padding: 20px; font-size: 13px; color: #000; }
                        .text-center { text-align: center; }
                        .border-top { border-top: 1px dashed #000; padding-top: 8px; margin-top: 8px; }
                        .row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 12px; }
                        h2 { margin: 0 0 4px 0; font-size: 17px; }
                        @media print { body { padding: 0; } }
                    </style>
                </head>
                <body onload="window.print(); window.close();">
                    <div class="text-center">
                        <h2>${storeName}</h2>
                        <div style="font-weight:bold; letter-spacing:1px;">MONEY EXCHANGE VOUCHER</div>
                        <small>Ref: #TX-${tx.id}</small>
                    </div>
                    <div class="border-top">
                        <div class="row"><span>Timestamp:</span><span>${tx.transaction_date}</span></div>
                        <div class="row"><span>Type:</span><span>${tx.transaction_type.toUpperCase()}</span></div>
                        <div class="row"><span>Cashier:</span><span>${tx.cashier_name || 'Staff'}</span></div>
                    </div>
                    <div class="border-top">
                        <div class="row"><span>Customer:</span><span><strong>${tx.party_name || '-'}</strong></span></div>
                        <div class="row"><span>Phone:</span><span>${tx.party_phone || '-'}</span></div>
                        <div class="row"><span>Ref TID:</span><span>${tx.reference_no || '-'}</span></div>
                    </div>
                    <div class="border-top">
                        <div class="row"><span>From:</span><span>${tx.from_account_name || 'Cash Till'}</span></div>
                        <div class="row"><span>To:</span><span>${tx.to_account_name || 'Online'}</span></div>
                        <div class="row" style="font-size:14px; font-weight:bold;"><span>Amount:</span><span>${currency} ${parseFloat(tx.amount).toFixed(2)}</span></div>
                        ${parseFloat(tx.fee_amount) > 0 ? `<div class="row"><span>Fee:</span><span>+ ${currency} ${parseFloat(tx.fee_amount).toFixed(2)}</span></div>` : ''}
                    </div>
                    <div class="border-top" style="margin-top:24px; text-align:center;">
                        <small>Authorized Signature: ______________________</small>
                        <br><br>
                        <small>Thank you for choosing us!</small>
                    </div>
                </body>
            </html>
        `);
        printWindow.document.close();
    }

    // View Voucher Modal
    let activeVoucherTx = null;
    function viewVoucherModal(tx) {
        if (!tx) return;
        activeVoucherTx = tx;
        const modal = document.getElementById('modal-voucher-preview');
        const currency = '<?= $currency ?>';
        
        document.getElementById('vp-id').textContent = `#TX-${tx.id}`;
        document.getElementById('vp-date').textContent = tx.transaction_date;
        document.getElementById('vp-type').textContent = (tx.transaction_type || '').toUpperCase().replace('_', ' ');
        document.getElementById('vp-from').textContent = tx.from_account_name || 'External / Till';
        document.getElementById('vp-to').textContent = tx.to_account_name || 'External / Online';
        document.getElementById('vp-amount').textContent = `${currency} ${Number(tx.amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        
        const fee = Number(tx.fee_amount || 0);
        const feeRow = document.getElementById('vp-fee-row');
        if (fee > 0) {
            feeRow.style.display = 'flex';
            document.getElementById('vp-fee').textContent = `+ ${currency} ${fee.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        } else {
            feeRow.style.display = 'none';
        }

        document.getElementById('vp-party').textContent = tx.party_name || 'Walk-in Customer';
        document.getElementById('vp-phone').textContent = tx.party_phone || 'N/A';
        document.getElementById('vp-ref').textContent = tx.reference_no || 'N/A';
        document.getElementById('vp-desc').textContent = tx.description || 'No description provided.';
        document.getElementById('vp-staff').textContent = tx.cashier_name || 'Staff';

        modal.style.display = 'grid';
    }

    function closeVoucherModal() {
        document.getElementById('modal-voucher-preview').style.display = 'none';
    }

    function printActiveVoucher() {
        if (activeVoucherTx) {
            printVoucherSlip(activeVoucherTx);
        }
    }

    // Confirm Delete Transaction
    function confirmDeleteTx(id, amount, type) {
        const currency = '<?= $currency ?>';
        const msg = `Are you sure you want to reverse and delete Transaction #TX-${id}?\n\nAmount: ${currency} ${Number(amount).toFixed(2)}\nType: ${type}\n\nThe amount will be safely restored back to the respective accounts.`;
        if (confirm(msg)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `${baseUrl}/cashflow/deleteTransaction`;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = 'csrf_token';
            csrf.value = '<?= Security::csrfToken() ?>';
            form.appendChild(csrf);

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            form.appendChild(idInput);

            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<!-- ========================================================
     MODAL: VIEW SLIP VOUCHER PREVIEW
     ======================================================== -->
<div id="modal-voucher-preview" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:99999; place-items:center; padding:16px;">
    <div style="background:#ffffff; border-radius:16px; width:100%; max-width:480px; box-shadow:0 25px 50px -12px rgba(15,23,42,0.25); overflow:hidden;">
        <!-- Modal Top -->
        <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="width:34px; height:34px; border-radius:8px; background:#dcfce7; color:#15803d; display:grid; place-items:center;">
                    <span class="material-symbols-rounded" style="font-size:18px;">receipt_long</span>
                </div>
                <div>
                    <h3 style="font-size:15px; font-weight:800; margin:0; color:#0f172a;">Transaction Slip Voucher</h3>
                    <div style="font-size:11px; color:#64748b;" id="vp-id">#TX-000</div>
                </div>
            </div>
            <button type="button" onclick="closeVoucherModal()" style="border:none; background:none; color:#94a3b8; cursor:pointer;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Voucher Body -->
        <div style="padding:22px;">
            <!-- Amount & Pathway Banner -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; text-align:center; margin-bottom:18px;">
                <div style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; margin-bottom:4px;" id="vp-type">MONEY EXCHANGE</div>
                <div style="font-size:26px; font-weight:900; color:#0f172a; letter-spacing:-0.03em; margin-bottom:8px;" id="vp-amount">Rs 0.00</div>
                
                <div style="display:flex; align-items:center; justify-content:center; gap:8px; font-size:12px; font-weight:700;">
                    <span style="color:#be123c;" id="vp-from">Drawer</span>
                    <span class="material-symbols-rounded" style="font-size:14px; color:#94a3b8;">arrow_forward</span>
                    <span style="color:#047857;" id="vp-to">Online</span>
                </div>
            </div>

            <!-- Detail Rows -->
            <div style="display:flex; flex-direction:column; gap:8px; font-size:12.5px;">
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                    <span style="color:#64748b; font-weight:600;">Timestamp:</span>
                    <strong style="color:#0f172a;" id="vp-date">-</strong>
                </div>
                <div id="vp-fee-row" style="display:none; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                    <span style="color:#64748b; font-weight:600;">Fee / Commission:</span>
                    <strong style="color:#059669;" id="vp-fee">-</strong>
                </div>
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                    <span style="color:#64748b; font-weight:600;">Customer / Party:</span>
                    <strong style="color:#0f172a;" id="vp-party">-</strong>
                </div>
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                    <span style="color:#64748b; font-weight:600;">Phone Number:</span>
                    <strong style="color:#0f172a;" id="vp-phone">-</strong>
                </div>
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                    <span style="color:#64748b; font-weight:600;">Account / IBAN / TID:</span>
                    <strong style="color:#0f172a; font-family:monospace; font-size:12px; background:#f1f5f9; padding:1px 6px; border-radius:4px;" id="vp-ref">-</strong>
                </div>
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                    <span style="color:#64748b; font-weight:600;">Description:</span>
                    <span style="color:#334155; font-size:11.5px; text-align:right; max-width:260px;" id="vp-desc">-</span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#64748b; font-weight:600;">Handled By Staff:</span>
                    <strong style="color:#0f172a;" id="vp-staff">-</strong>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" class="btn-solid-gray btn-md" onclick="closeVoucherModal()">Close</button>
                <button type="button" class="btn-solid-blue btn-md" onclick="printActiveVoucher()">
                    <span class="material-symbols-rounded">print</span>
                    Print Thermal Slip
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
