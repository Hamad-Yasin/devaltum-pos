<?php ob_start(); ?>
<?php
$filters = $filters ?? [];
$activeDateFilter = $filters['dateFilter'] ?? 'this_month';
$activeTab = $filters['activeTab'] ?? 'balances';
$currency = $settings['currency_symbol'] ?? 'Rs';
$gateways = $gateways ?? [];
$accounts = $accounts ?? [];
$recentAdjustments = $recentAdjustments ?? [];
$defaultCashAcc = $defaultCashAcc ?? null;
$liveCashInHand = (float)($liveCashInHand ?? ($kpis['cash_in_hand'] ?? 0));
$totalOnlineBalance = (float)($totalOnlineBalance ?? ($kpis['online_balance'] ?? 0));
$totalLiquid = (float)($totalLiquid ?? ($kpis['total_liquid'] ?? 0));
$activeAccountsCount = count(array_filter($accounts, fn($a) => ($a['status'] ?? 'active') === 'active'));
?>

<style>
    /* ========================================================
       PREMIUM UNIFIED CASHFLOW & ACCOUNTS SUITE
       100% Solid Buttons, Standard POS Design, Zero Overflow
       ======================================================== */
    .cf-wrapper {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Inter", "Manrope", sans-serif;
        color: #0f172a;
        max-width: 1400px;
        margin: 0 auto;
        padding-bottom: 40px;
        box-sizing: border-box;
    }

    /* Page Header Actions */
    .page-header-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    .page-header-actions .btn {
        padding: 6px 14px;
        font-size: 13px;
        font-weight: 700;
        height: 38px;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 10px;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .page-header-actions .btn .material-symbols-rounded {
        font-size: 19px;
    }

    /* Tabs Strip (Matching Standard POS Tabs) */
    .cf-tabs-strip {
        display: flex;
        gap: 8px;
        margin-bottom: 22px;
        background: #f1f5f9;
        padding: 6px;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        width: fit-content;
        max-width: 100%;
        overflow-x: auto;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }
    .cf-tab-btn {
        border: none;
        background: transparent;
        color: #64748b;
        border-radius: 12px;
        padding: 9px 16px;
        font-size: 13.5px;
        font-weight: 800;
        cursor: pointer;
        transition: all .22s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }
    .cf-tab-btn .material-symbols-rounded {
        font-size: 19px;
    }
    .cf-tab-btn:hover {
        color: #0f172a;
        background: rgba(255,255,255,0.7);
    }
    .cf-tab-btn.active {
        background: linear-gradient(135deg, #1d4ed8, #0f6dff);
        color: #ffffff !important;
        box-shadow: 0 4px 14px rgba(15,109,255,0.35);
    }
    .cf-tab-btn.active .material-symbols-rounded {
        color: #ffffff !important;
    }

    .cf-tab-panel {
        display: none;
        animation: cfFadeIn 0.2s ease-in-out;
    }
    .cf-tab-panel.active {
        display: block;
    }
    @keyframes cfFadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
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
    .btn-solid-amber { background: #d97706 !important; box-shadow: 0 2px 6px rgba(217, 119, 6, 0.3) !important; }
    .btn-solid-amber:hover { background: #b45309 !important; }
    .btn-solid-gray { background: #475569 !important; box-shadow: 0 2px 6px rgba(71, 85, 105, 0.25) !important; }
    .btn-solid-gray:hover { background: #334155 !important; }

    .btn-xs { height: 28px; padding: 0 10px; font-size: 11.5px; border-radius: 6px !important; }
    .btn-sm { height: 34px; padding: 0 12px; font-size: 12.5px; border-radius: 7px !important; }
    .btn-md { height: 38px; padding: 0 16px; font-size: 13.5px; border-radius: 8px !important; }
    .btn-lg { height: 44px; padding: 0 20px; font-size: 14.5px; border-radius: 10px !important; }

    /* High-Impact Colorful KPI Summary Grid */
    .cf-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .cf-kpi-box {
        background: #ffffff;
        border-radius: 14px;
        padding: 18px 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        position: relative;
        overflow: hidden;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .cf-kpi-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
    }
    .cf-kpi-box::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
    }
    .kpi-cash::before { background: linear-gradient(90deg, #10b981, #059669); }
    .kpi-digital::before { background: linear-gradient(90deg, #0284c7, #0369a1); }
    .kpi-total::before { background: linear-gradient(90deg, #0f172a, #334155); }
    .kpi-recv::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
    .kpi-pay::before { background: linear-gradient(90deg, #f43f5e, #e11d48); }

    .cf-kpi-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .cf-kpi-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
    }
    .cf-kpi-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        display: grid;
        place-items: center;
    }
    .icon-cash { background: #dcfce7; color: #059669; }
    .icon-digital { background: #e0f2fe; color: #0284c7; }
    .icon-total { background: #f1f5f9; color: #0f172a; }
    .icon-recv { background: #fef3c7; color: #d97706; }
    .icon-pay { background: #ffe4e6; color: #e11d48; }

    .cf-kpi-number {
        font-size: 24px;
        font-weight: 900;
        color: #0f172a;
        font-feature-settings: 'tnum';
        line-height: 1.1;
        letter-spacing: -0.02em;
    }
    .cf-kpi-footer {
        font-size: 12px;
        color: #64748b;
        margin-top: 6px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Account Cards Grid */
    .accounts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }
    .account-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.2s ease;
        position: relative;
    }
    .account-card:hover {
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        border-color: #cbd5e1;
    }
    .account-card.is-drawer {
        border-color: #10b981;
        background: linear-gradient(to bottom, #ffffff 0%, #f0fdf4 100%);
    }
    .acc-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 14px;
    }
    .acc-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }
    .acc-avatar {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .avatar-cash { background: #dcfce7; color: #059669; }
    .avatar-bank { background: #e0f2fe; color: #0284c7; }
    .avatar-wallet { background: #fef3c7; color: #d97706; }
    .avatar-other { background: #f1f5f9; color: #475569; }

    .acc-name {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .acc-inst {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
    }
    .acc-badge {
        font-size: 10px;
        font-weight: 800;
        padding: 3px 8px;
        border-radius: 999px;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        flex-shrink: 0;
    }
    .badge-cash { background: #10b981; color: #ffffff; }
    .badge-bank { background: #0284c7; color: #ffffff; }
    .badge-wallet { background: #f59e0b; color: #ffffff; }
    .badge-other { background: #64748b; color: #ffffff; }

    .acc-balance-area {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 14px;
    }
    .acc-bal-label {
        font-size: 11px;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .acc-bal-value {
        font-size: 22px;
        font-weight: 900;
        color: #0f172a;
        font-feature-settings: 'tnum';
        margin-top: 2px;
    }

    .acc-details-list {
        font-size: 12px;
        color: #475569;
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 16px;
    }
    .acc-detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 4px;
        border-bottom: 1px dashed #f1f5f9;
    }
    .acc-detail-row:last-child {
        border-bottom: none;
    }
    .acc-iban-badge {
        background: #f1f5f9;
        color: #0f172a;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: monospace;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .acc-actions {
        display: flex;
        gap: 8px;
        padding-top: 12px;
        border-top: 1px solid #f1f5f9;
    }
    .acc-actions .btn-solid-emerald,
    .acc-actions .btn-solid-blue,
    .acc-actions .btn-solid-gray,
    .acc-actions .btn-solid-rose {
        flex: 1;
    }

    /* DIRECT SINGLE FORM: MONEY EXCHANGE (2-Row Zero Overflow Layout) */
    .cf-exchange-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 22px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .cf-exchange-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
    }
    .cf-exchange-heading {
        display: flex;
        align-items: center;
        gap: 12px;
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
        display: block;
        margin-top: 2px;
    }
    .cf-exchange-icon-badge {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #0284c7;
        color: #ffffff;
        display: grid;
        place-items: center;
    }

    .cf-form-rows {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .cf-form-grid-4 {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }
    .cf-form-grid-action {
        display: grid;
        grid-template-columns: minmax(0, 3fr) minmax(0, 1fr);
        gap: 10px;
        align-items: flex-end;
    }
    @media (max-width: 1024px) {
        .cf-form-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .cf-form-grid-action { grid-template-columns: 1fr; }
    }
    @media (max-width: 600px) {
        .cf-form-grid-4 { grid-template-columns: 1fr; }
    }

    .cf-field-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }
    .cf-field-label {
        font-size: 10.5px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cf-input, .cf-select, .cf-textarea {
        height: 34px;
        padding: 0 10px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        background: #ffffff;
        font-size: 12.5px;
        color: #0f172a;
        box-sizing: border-box;
        outline: none;
        width: 100%;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .cf-input:focus, .cf-select:focus, .cf-textarea:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.14);
    }

    /* Combobox for Gateway Search & Add */
    .cf-gw-combobox {
        position: relative;
    }
    .cf-gw-trigger {
        height: 34px;
        padding: 0 10px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
        box-sizing: border-box;
        transition: all 0.15s ease;
    }
    .cf-gw-trigger:hover, .cf-gw-trigger.active {
        border-color: #0284c7;
    }
    .cf-gw-selected {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12.5px;
        font-weight: 700;
        color: #0f172a;
        min-width: 0;
    }
    .cf-gw-selected .material-symbols-rounded {
        font-size: 18px;
        color: #0284c7;
        flex-shrink: 0;
    }
    .cf-gw-selected span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cf-gw-dropdown {
        position: absolute;
        top: calc(100% + 4px);
        left: 0; right: 0;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
        z-index: 1000;
        display: none;
        padding: 8px;
        box-sizing: border-box;
    }
    .cf-gw-dropdown.open {
        display: block;
    }
    .cf-gw-search-box {
        position: relative;
        margin-bottom: 8px;
    }
    .cf-gw-search-input {
        width: 100%;
        height: 34px;
        padding: 0 10px 0 32px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 12.5px;
        box-sizing: border-box;
        outline: none;
    }
    .cf-gw-search-icon {
        position: absolute;
        left: 9px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 16px;
        color: #94a3b8;
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
        align-items: center;
        justify-content: space-between;
        padding: 7px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        transition: background 0.1s ease;
    }
    .cf-gw-item:hover, .cf-gw-item.selected {
        background: #f1f5f9;
        color: #0f172a;
    }
    .cf-gw-item-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .cf-gw-del-btn {
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 2px 4px;
        border-radius: 4px;
    }
    .cf-gw-del-btn:hover {
        color: #e11d48;
        background: #ffe4e6;
    }
    .cf-gw-add-prompt {
        padding: 8px 10px;
        background: #f0fdf4;
        border: 1px dashed #10b981;
        border-radius: 6px;
        color: #059669;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        text-align: center;
        margin-top: 6px;
    }

    /* Live Impact Banner */
    .cf-impact-banner {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        color: #475569;
        margin-top: 10px;
    }

    /* Tables */
    .cf-table-container {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        margin-bottom: 24px;
    }
    .cf-data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        text-align: left;
    }
    .cf-data-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
    }
    .cf-data-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
    }
    .cf-data-table tr:last-child td {
        border-bottom: none;
    }
    .cf-data-table tr:hover td {
        background: #f8fafc;
    }

    .cf-micro-badge {
        font-size: 11px;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .badge-exchange { background: #e0f2fe; color: #0369a1; }
    .badge-transfer { background: #f3e8ff; color: #7e22ce; }
    .badge-adj-in { background: #dcfce7; color: #15803d; }
    .badge-adj-out { background: #ffe4e6; color: #be123c; }

    /* Filter Strip */
    .cf-filter-bar {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        padding: 12px 16px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    /* Receivables / Payables Split Grid */
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
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .panel-recv { border-top: 4px solid #f59e0b; }
    .panel-pay { border-top: 4px solid #f43f5e; }
    .cf-ledger-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
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
    .cf-ledger-sub { font-size: 11.5px; color: #64748b; margin-top: 2px; }
    .cf-ledger-total { font-size: 19px; font-weight: 900; font-feature-settings: 'tnum'; }
    .cf-ledger-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .cf-ledger-row:last-child { border-bottom: none; }

    /* Modals (Clean, Centered, Zero z-index conflict) */
    .cf-modal-backdrop {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 99999;
        display: none;
        place-items: center;
        padding: 20px;
        overflow-y: auto;
    }
    .cf-modal-box {
        background: #ffffff;
        border-radius: 16px;
        max-width: 540px;
        width: 100%;
        padding: 24px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.25);
        box-sizing: border-box;
        position: relative;
        animation: cfModalIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes cfModalIn {
        from { opacity: 0; transform: scale(0.96) translateY(8px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .cf-modal-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
    }
    .cf-modal-head h3 {
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .cf-modal-head p {
        font-size: 12px;
        color: #64748b;
        margin: 2px 0 0;
    }
    .cf-modal-close-btn {
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 4px;
        border-radius: 6px;
        display: grid;
        place-items: center;
    }
    .cf-modal-close-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
</style>

<div class="cf-wrapper">
    <!-- Standard POS Page Header -->
    <div class="page-header">
        <div>
            <h1>Cashflow & Financial Accounts</h1>
            <p class="page-subtitle">Track drawer cash till, bank balances, money exchange disbursals, and obligations.</p>
        </div>
        <div class="page-header-actions">
            <button type="button" class="btn btn-primary" onclick="window.cfApp.openSetBalanceModal()">
                <span class="material-symbols-rounded">tune</span>
                Set / Adjust Cash
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.cfApp.openAddAccountModal()">
                <span class="material-symbols-rounded">add_circle</span>
                Add Account
            </button>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/export?<?= http_build_query($filters) ?>" class="btn btn-secondary">
                <span class="material-symbols-rounded">download</span>
                Export CSV
            </a>
        </div>
    </div>

    <!-- 4 High-Impact KPI Metric Cards -->
    <div class="cf-kpi-grid">
        <!-- 1. Physical Cash in Drawer -->
        <div class="cf-kpi-box kpi-cash">
            <div class="cf-kpi-header">
                <span class="cf-kpi-title">Physical Cash Drawer</span>
                <div class="cf-kpi-icon icon-cash">
                    <span class="material-symbols-rounded">point_of_sale</span>
                </div>
            </div>
            <div class="cf-kpi-number"><?= $currency ?> <?= number_format($kpis['cash_in_hand'], 2) ?></div>
            <div class="cf-kpi-footer">
                <span>Counter Till (DRAWER-01)</span>
                <button type="button" class="btn-solid-emerald btn-xs" onclick="window.cfApp.openSetBalanceModal(<?= $defaultCashAcc ? (int)$defaultCashAcc['id'] : 0 ?>, 'Counter Till', <?= (float)$kpis['cash_in_hand'] ?>)">Adjust</button>
            </div>
        </div>

        <!-- 2. Digital Accounts & Banking -->
        <div class="cf-kpi-box kpi-digital">
            <div class="cf-kpi-header">
                <span class="cf-kpi-title">Online & Bank Balances</span>
                <div class="cf-kpi-icon icon-digital">
                    <span class="material-symbols-rounded">account_balance</span>
                </div>
            </div>
            <div class="cf-kpi-number"><?= $currency ?> <?= number_format($kpis['online_balance'], 2) ?></div>
            <div class="cf-kpi-footer">
                <span>JazzCash, EasyPaisa, Banks</span>
                <button type="button" class="btn-solid-blue btn-xs" onclick="window.cfApp.switchTab('balances')">View All</button>
            </div>
        </div>

        <!-- 3. Total Liquid Funds -->
        <div class="cf-kpi-box kpi-total">
            <div class="cf-kpi-header">
                <span class="cf-kpi-title">Total Liquid Capital</span>
                <div class="cf-kpi-icon icon-total">
                    <span class="material-symbols-rounded">wallet</span>
                </div>
            </div>
            <div class="cf-kpi-number"><?= $currency ?> <?= number_format($kpis['total_liquid'], 2) ?></div>
            <div class="cf-kpi-footer">
                <span>Total Instant Liquidity</span>
            </div>
        </div>

        <!-- 4. Net Market Position -->
        <div class="cf-kpi-box <?= $kpis['net_position'] >= 0 ? 'kpi-cash' : 'kpi-pay' ?>">
            <div class="cf-kpi-header">
                <span class="cf-kpi-title">Net Financial Position</span>
                <div class="cf-kpi-icon <?= $kpis['net_position'] >= 0 ? 'icon-cash' : 'icon-pay' ?>">
                    <span class="material-symbols-rounded">balance</span>
                </div>
            </div>
            <div class="cf-kpi-number" style="color:<?= $kpis['net_position'] >= 0 ? '#059669' : '#e11d48' ?>;">
                <?= $currency ?> <?= number_format($kpis['net_position'], 2) ?>
            </div>
            <div class="cf-kpi-footer">
                <span>(Liquid + Dues) - Payables</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs Strip -->
    <div class="cf-tabs-strip">
        <button type="button" class="cf-tab-btn <?= $activeTab === 'balances' ? 'active' : '' ?>" data-cf-tab="balances" onclick="window.cfApp.switchTab('balances')">
            <span class="material-symbols-rounded">account_balance_wallet</span>
            <span>Drawer & Bank Accounts</span>
        </button>
        <button type="button" class="cf-tab-btn <?= $activeTab === 'roznamcha' ? 'active' : '' ?>" data-cf-tab="roznamcha" onclick="window.cfApp.switchTab('roznamcha')">
            <span class="material-symbols-rounded">receipt_long</span>
            <span>Roznamcha (Daily Ledger)</span>
        </button>
        <button type="button" class="cf-tab-btn <?= $activeTab === 'exchange' ? 'active' : '' ?>" data-cf-tab="exchange" onclick="window.cfApp.switchTab('exchange')">
            <span class="material-symbols-rounded">currency_exchange</span>
            <span>Money Exchange Disbursal</span>
        </button>
        <button type="button" class="cf-tab-btn <?= $activeTab === 'dues' ? 'active' : '' ?>" data-cf-tab="dues" onclick="window.cfApp.switchTab('dues')">
            <span class="material-symbols-rounded">balance</span>
            <span>Receivables & Payables</span>
        </button>
    </div>

    <!-- ========================================================
         TAB 1: DRAWER & BANK ACCOUNTS MANAGER
         ======================================================== -->
    <div id="tab-content-balances" class="cf-tab-panel <?= $activeTab === 'balances' ? 'active' : '' ?>">
        <!-- Accounts Cards Grid -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <h3 style="font-size:16px; font-weight:800; color:#0f172a; margin:0;">
                All Financial Accounts (<?= count($accounts) ?>)
            </h3>
            <button type="button" class="btn-solid-dark btn-sm" onclick="window.cfApp.openAddAccountModal()">
                <span class="material-symbols-rounded" style="font-size:16px;">add</span>
                Add New Account / Gateway
            </button>
        </div>

        <div class="accounts-grid">
            <?php foreach ($accounts as $acc): ?>
                <?php
                $isCash = ($acc['account_type'] === 'cash' || (int)$acc['is_default'] === 1);
                $isBank = ($acc['account_type'] === 'bank');
                $isWallet = ($acc['account_type'] === 'wallet');
                $balance = (float)$acc['current_balance'];
                ?>
                <div class="account-card <?= $isCash ? 'is-drawer' : '' ?>">
                    <div>
                        <div class="acc-header">
                            <div class="acc-header-left">
                                <div class="acc-avatar <?= $isCash ? 'avatar-cash' : ($isBank ? 'avatar-bank' : ($isWallet ? 'avatar-wallet' : 'avatar-other')) ?>">
                                    <span class="material-symbols-rounded">
                                        <?= $isCash ? 'point_of_sale' : ($isBank ? 'account_balance' : ($isWallet ? 'account_balance_wallet' : 'credit_card')) ?>
                                    </span>
                                </div>
                                <div style="min-width:0;">
                                    <h3 class="acc-name" title="<?= Security::escape($acc['account_name']) ?>"><?= Security::escape($acc['account_name']) ?></h3>
                                    <div class="acc-inst"><?= Security::escape($acc['bank_name'] ?: ($isCash ? 'Counter Till' : 'Online Gateway')) ?></div>
                                </div>
                            </div>
                            <span class="acc-badge <?= $isCash ? 'badge-cash' : ($isBank ? 'badge-bank' : ($isWallet ? 'badge-wallet' : 'badge-other')) ?>">
                                <?= $isCash ? 'DRAWER' : ($isBank ? 'BANK' : ($isWallet ? 'WALLET' : 'OTHER')) ?>
                            </span>
                        </div>

                        <div class="acc-balance-area">
                            <div class="acc-bal-label">Live Balance</div>
                            <div class="acc-bal-value"><?= $currency ?> <?= number_format($balance, 2) ?></div>
                        </div>

                        <div class="acc-details-list">
                            <div class="acc-detail-row">
                                <span style="color:#64748b;">Account / IBAN:</span>
                                <div>
                                    <span class="acc-iban-badge"><?= Security::escape($acc['account_number'] ?: 'N/A') ?></span>
                                    <?php if (!empty($acc['account_number'])): ?>
                                        <button type="button" onclick="navigator.clipboard.writeText('<?= Security::escape($acc['account_number']) ?>'); window.appToast?.('Copied to clipboard!', 'success');" style="border:none; background:none; cursor:pointer; color:#0284c7; padding:0 2px;" title="Copy IBAN">
                                            <span class="material-symbols-rounded" style="font-size:14px; vertical-align:middle;">content_copy</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="acc-detail-row">
                                <span style="color:#64748b;">Opening Base:</span>
                                <span style="font-weight:700;"><?= $currency ?> <?= number_format((float)$acc['opening_balance'], 2) ?></span>
                            </div>
                            <div class="acc-detail-row">
                                <span style="color:#64748b;">Status:</span>
                                <span style="font-weight:700; color:<?= ($acc['status'] ?? 'active') === 'active' ? '#059669' : '#94a3b8' ?>;">
                                    <?= ucfirst($acc['status'] ?? 'active') ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="acc-actions">
                        <button type="button" class="btn-solid-emerald btn-xs" onclick='window.cfApp.openSetBalanceModal(<?= (int)$acc['id'] ?>, "<?= Security::escape($acc['account_name']) ?>", <?= (float)$balance ?>)'>
                            <span class="material-symbols-rounded" style="font-size:14px;">tune</span>
                            Set / Adjust
                        </button>
                        <button type="button" class="btn-solid-blue btn-xs" onclick='window.cfApp.openEditAccountModal(<?= json_encode($acc) ?>)'>
                            <span class="material-symbols-rounded" style="font-size:14px;">edit</span>
                            Edit
                        </button>
                        <?php if (!$isCash): ?>
                            <button type="button" class="btn-solid-rose btn-xs" onclick="window.cfApp.confirmDeactivateAccount(<?= (int)$acc['id'] ?>, '<?= Security::escape($acc['account_name']) ?>')" title="Deactivate">
                                <span class="material-symbols-rounded" style="font-size:14px;">block</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Adjustments Audit Log Table -->
        <div style="margin-top:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:0;">
                    Recent Cash Adjustments & Reconciliation Log
                </h3>
                <span style="font-size:12px; color:#64748b;">Last 15 manual till / account modifications</span>
            </div>

            <div class="cf-table-container">
                <table class="cf-data-table">
                    <thead>
                        <tr>
                            <th style="width:130px;">Date & Time</th>
                            <th style="width:110px;">Type</th>
                            <th>Target Account</th>
                            <th>Reason / Description</th>
                            <th style="text-align:right;">Amount Impact</th>
                            <th>Staff Member</th>
                            <th style="width:100px; text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentAdjustments)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:32px 20px; color:#94a3b8;">
                                    No manual balance adjustments recorded yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentAdjustments as $adj): ?>
                                <tr>
                                    <td style="font-size:12px; color:#475569;">
                                        <strong><?= date('d M Y', strtotime($adj['transaction_date'])) ?></strong><br>
                                        <small style="color:#94a3b8;"><?= date('h:i A', strtotime($adj['transaction_date'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($adj['transaction_type'] === 'adjustment_in'): ?>
                                            <span class="cf-micro-badge badge-adj-in">
                                                <span class="material-symbols-rounded" style="font-size:12px;">arrow_downward</span>
                                                Inflow (+)
                                            </span>
                                        <?php else: ?>
                                            <span class="cf-micro-badge badge-adj-out">
                                                <span class="material-symbols-rounded" style="font-size:12px;">arrow_upward</span>
                                                Outflow (-)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight:700; color:#0f172a;">
                                        <?= Security::escape($adj['account_name'] ?: 'Cash Till') ?>
                                    </td>
                                    <td>
                                        <div style="font-size:12.5px; color:#0f172a;"><?= Security::escape($adj['description']) ?></div>
                                        <?php if (!empty($adj['notes'])): ?>
                                            <small style="color:#64748b;"><?= Security::escape($adj['notes']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right; font-weight:800; font-size:13px; color:<?= $adj['transaction_type'] === 'adjustment_in' ? '#059669' : '#e11d48' ?>;">
                                        <?= $adj['transaction_type'] === 'adjustment_in' ? '+' : '-' ?> <?= $currency ?> <?= number_format((float)$adj['amount'], 2) ?>
                                    </td>
                                    <td style="font-size:12px; color:#64748b;">
                                        <?= Security::escape($adj['staff_name'] ?: 'Admin') ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn-solid-rose btn-xs" onclick='window.cfApp.confirmDeleteTx(<?= (int)$adj['id'] ?>, <?= (float)$adj['amount'] ?>, "<?= Security::escape($adj['transaction_type']) ?>")' title="Reverse this adjustment">
                                            <span class="material-symbols-rounded" style="font-size:14px;">undo</span>
                                            Reverse
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================
         TAB 2: ROZNAMCHA (DAILY CASHFLOW LEDGER STREAM)
         ======================================================== -->
    <div id="tab-content-roznamcha" class="cf-tab-panel <?= $activeTab === 'roznamcha' ? 'active' : '' ?>">
        <!-- Smart Filter Bar -->
        <div class="cf-filter-bar">
            <form method="GET" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/" id="cf-filter-form" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; width:100%;">
                <input type="hidden" name="url" value="cashflow/index">
                <input type="hidden" name="tab" value="roznamcha">

                <!-- Search Input -->
                <div style="position:relative; flex:1 1 200px; min-width:180px;">
                    <span class="material-symbols-rounded" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); font-size:18px; color:#94a3b8;">search</span>
                    <input type="text" name="q" value="<?= Security::escape($filters['q'] ?? '') ?>" placeholder="Search TID, name, phone, notes..." class="cf-input" style="padding-left:34px; width:100%;">
                </div>

                <!-- Date Filter Presets -->
                <select name="date_filter" class="cf-select" style="min-width:130px;" onchange="this.form.submit()">
                    <option value="today" <?= $activeDateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="yesterday" <?= $activeDateFilter === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                    <option value="this_week" <?= $activeDateFilter === 'this_week' ? 'selected' : '' ?>>This Week</option>
                    <option value="this_month" <?= $activeDateFilter === 'this_month' ? 'selected' : '' ?>>This Month</option>
                    <option value="all" <?= $activeDateFilter === 'all' ? 'selected' : '' ?>>All Time</option>
                    <option value="custom" <?= $activeDateFilter === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                </select>

                <!-- Account Filter -->
                <select name="account_id" class="cf-select" style="min-width:140px;" onchange="this.form.submit()">
                    <option value="0">All Accounts</option>
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?= (int)$acc['id'] ?>" <?= ((int)($filters['accountFilter'] ?? 0) === (int)$acc['id']) ? 'selected' : '' ?>>
                            <?= Security::escape($acc['account_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Type Filter -->
                <select name="type" class="cf-select" style="min-width:130px;" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="money_exchange" <?= ($filters['typeFilter'] ?? '') === 'money_exchange' ? 'selected' : '' ?>>Money Exchange</option>
                    <option value="transfer" <?= ($filters['typeFilter'] ?? '') === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                    <option value="adjustment_in" <?= ($filters['typeFilter'] ?? '') === 'adjustment_in' ? 'selected' : '' ?>>Inflow (+)</option>
                    <option value="adjustment_out" <?= ($filters['typeFilter'] ?? '') === 'adjustment_out' ? 'selected' : '' ?>>Outflow (-)</option>
                </select>

                <button type="submit" class="btn-solid-blue btn-sm">
                    <span class="material-symbols-rounded" style="font-size:16px;">filter_alt</span>
                    Filter
                </button>

                <?php if (!empty($filters['q']) || $activeDateFilter !== 'this_month' || !empty($filters['typeFilter']) || !empty($filters['accountFilter'])): ?>
                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/index?tab=roznamcha" class="btn-solid-gray btn-sm">
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
                            <th>Counterparty & Account / IBAN</th>
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
                                    <td style="font-size:12px; color:#475569;">
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
                                                Ref/IBAN: <?= Security::escape($tx['reference_no']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right; font-weight:800; font-size:13px; color:#0f172a; font-feature-settings: 'tnum';">
                                        <?= $currency ?> <?= number_format((float)$tx['amount'], 2) ?>
                                    </td>
                                    <td style="text-align:right; font-size:12px; color:<?= (float)$tx['fee_amount'] > 0 ? '#047857' : '#94a3b8' ?>; font-feature-settings: 'tnum';">
                                        <?= (float)$tx['fee_amount'] > 0 ? '+ ' . $currency . ' ' . number_format((float)$tx['fee_amount'], 2) : '-' ?>
                                    </td>
                                    <td style="font-size:12px; color:#64748b;">
                                        <?= Security::escape($tx['cashier_name'] ?: 'Staff') ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <div style="display:inline-flex; align-items:center; gap:4px;">
                                            <!-- View Slip Voucher Button -->
                                            <button type="button" class="btn-solid-emerald btn-xs" onclick='window.cfApp.viewVoucherModal(<?= json_encode($tx) ?>)' title="View Slip Voucher" style="padding:0 6px;">
                                                <span class="material-symbols-rounded" style="font-size:15px;">visibility</span>
                                            </button>
                                            <!-- Print Slip Voucher Button -->
                                            <button type="button" class="btn-solid-blue btn-xs" onclick='window.cfApp.printVoucherSlip(<?= json_encode($tx) ?>)' title="Print Slip Voucher" style="padding:0 6px;">
                                                <span class="material-symbols-rounded" style="font-size:15px;">print</span>
                                            </button>
                                            <!-- Delete / Reverse Transaction Button -->
                                            <button type="button" class="btn-solid-rose btn-xs" onclick='window.cfApp.confirmDeleteTx(<?= (int)$tx['id'] ?>, <?= (float)$tx['amount'] ?>, "<?= Security::escape($tx['transaction_type']) ?>")' title="Reverse Transaction" style="padding:0 6px;">
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

    <!-- ========================================================
         TAB 3: MONEY EXCHANGE & COUNTER DISBURSAL
         ======================================================== -->
    <div id="tab-content-exchange" class="cf-tab-panel <?= $activeTab === 'exchange' ? 'active' : '' ?>">
        <div class="cf-exchange-card">
            <div class="cf-exchange-top">
                <div class="cf-exchange-heading">
                    <div class="cf-exchange-icon-badge">
                        <span class="material-symbols-rounded">currency_exchange</span>
                    </div>
                    <div>
                        <h2>Instant Digital Currency & Cash Disbursal</h2>
                        <span>Counter operation: Customer pays online & receives cash from drawer, or pays cash & receives online transfer.</span>
                    </div>
                </div>
            </div>

            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/exchange" method="POST" id="cf-exchange-form">
                <?= Security::csrfField() ?>
                <div class="cf-form-rows">
                    <!-- ROW 1: Flow, Gateway, Amount, Fee (4 Symmetrical Columns) -->
                    <div class="cf-form-grid-4">
                        <!-- 1. Flow Direction -->
                        <div class="cf-field-group">
                            <label class="cf-field-label">Transaction Flow *</label>
                            <select name="direction" id="ex-direction" class="cf-select" required style="font-weight:700;" onchange="window.cfApp.updateExchangePreview()">
                                <option value="online_to_cash">Online Received ➔ Cash Given from Drawer</option>
                                <option value="cash_to_online">Cash Received ➔ Online Transferred to Customer</option>
                            </select>
                        </div>

                        <!-- 2. Searchable Digital Gateway Combobox -->
                        <div class="cf-field-group">
                            <label class="cf-field-label">Digital Gateway / Bank *</label>
                            <div class="cf-gw-combobox" id="cf_gw_combobox">
                                <div class="cf-gw-trigger" id="cf_gw_trigger" onclick="window.cfApp.toggleGwDropdown()">
                                    <div class="cf-gw-selected">
                                        <span class="material-symbols-rounded">account_balance_wallet</span>
                                        <span id="cf_gw_label">JazzCash</span>
                                    </div>
                                    <span class="material-symbols-rounded" style="color:#64748b; font-size:18px;">expand_more</span>
                                </div>

                                <input type="hidden" name="channel" id="cf_input_channel" value="JazzCash" required>
                                <input type="hidden" name="online_account_id" id="cf_input_account_id" value="">

                                <div class="cf-gw-dropdown" id="cf_gw_dropdown">
                                    <div class="cf-gw-search-box">
                                        <span class="material-symbols-rounded cf-gw-search-icon">search</span>
                                        <input type="text" id="cf_gw_search" class="cf-gw-search-input" placeholder="Search or type new gateway..." oninput="window.cfApp.filterGateways(this.value)" onkeydown="window.cfApp.onGwSearchKey(event)">
                                    </div>

                                    <div class="cf-gw-list" id="cf_gw_list">
                                        <?php 
                                        $firstGwSelected = false;
                                        foreach ($gateways as $gw): 
                                        ?>
                                            <div class="cf-gw-item <?= !$firstGwSelected ? 'selected' : '' ?>" 
                                                 data-id="<?= (int)$gw['id'] ?>" 
                                                 data-name="<?= Security::escape($gw['account_name']) ?>" 
                                                 onclick="window.cfApp.selectGateway(<?= (int)$gw['id'] ?>, '<?= Security::escape($gw['account_name']) ?>')">
                                                <div class="cf-gw-item-left">
                                                    <span class="material-symbols-rounded">account_balance_wallet</span>
                                                    <span class="cf-gw-item-name"><?= Security::escape($gw['account_name']) ?></span>
                                                </div>
                                                <button type="button" class="cf-gw-del-btn" onclick="window.cfApp.deleteGateway(event, <?= (int)$gw['id'] ?>, '<?= Security::escape($gw['account_name']) ?>')" title="Remove this gateway">
                                                    <span class="material-symbols-rounded">delete</span>
                                                </button>
                                            </div>
                                        <?php 
                                            $firstGwSelected = true;
                                        endforeach; 
                                        ?>
                                    </div>

                                    <div class="cf-gw-add-prompt" id="cf_gw_add_prompt" style="display:none;" onclick="window.cfApp.addNewGateway()">
                                        <span id="cf_gw_add_text">+ Add New Gateway</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Exchange Amount -->
                        <div class="cf-field-group">
                            <label class="cf-field-label">Disbursal Amount (<?= $currency ?>) *</label>
                            <input type="number" step="0.01" min="1" name="amount" id="ex-amount" class="cf-input" placeholder="e.g. 5000" required style="font-weight:800; font-size:13.5px; color:#0284c7;" oninput="window.cfApp.updateExchangePreview()">
                        </div>

                        <!-- 4. Fee / Commission -->
                        <div class="cf-field-group">
                            <label class="cf-field-label">Commission Fee (<?= $currency ?>)</label>
                            <input type="number" step="0.01" min="0" name="fee_amount" id="ex-fee" class="cf-input" placeholder="e.g. 50" value="0.00" oninput="window.cfApp.updateExchangePreview()">
                        </div>
                    </div>

                    <!-- ROW 2: Customer Name, Phone, Account/IBAN, TID/TRX ID (4 Symmetrical Columns Aligned with Row 1) -->
                    <div class="cf-form-grid-4">
                        <!-- 1. Customer Name -->
                        <div class="cf-field-group">
                            <label class="cf-field-label">Customer Name</label>
                            <input type="text" name="party_name" class="cf-input" placeholder="Walk-in Customer">
                        </div>

                        <!-- 2. Customer Phone -->
                        <div class="cf-field-group">
                            <label class="cf-field-label">Customer Phone</label>
                            <input type="text" name="party_phone" class="cf-input" placeholder="03001234567">
                        </div>

                        <!-- 3. Customer Account Number / IBAN Number -->
                        <div class="cf-field-group">
                            <label class="cf-field-label">Customer Account / IBAN</label>
                            <input type="text" name="account_number" class="cf-input" placeholder="e.g. PK36MEZN... / 0300...">
                        </div>

                        <!-- 4. TID / Reference Number -->
                        <div class="cf-field-group">
                            <label class="cf-field-label">TID / TRX ID</label>
                            <input type="text" name="reference_no" class="cf-input" placeholder="e.g. TRX-982341">
                        </div>
                    </div>

                    <!-- ROW 3: Notes (spans 3 columns) & Action Button (spans 1 column, aligned with column 4) -->
                    <div class="cf-form-grid-action">
                        <!-- Notes -->
                        <div class="cf-field-group">
                            <label class="cf-field-label">Notes (Optional)</label>
                            <input type="text" name="notes" class="cf-input" placeholder="Counter remarks or transaction note...">
                        </div>

                        <!-- Submit Button -->
                        <div class="cf-field-group">
                            <label class="cf-field-label" style="visibility:hidden;">Action</label>
                            <button type="submit" class="btn-solid-emerald" style="width:100%; height:34px; font-size:12.5px; font-weight:700; border-radius:7px; display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                                <span class="material-symbols-rounded" style="font-size:18px;">done_all</span>
                                Disburse & Save
                            </button>
                        </div>
                    </div>

                    <!-- Live Calculation Impact Banner -->
                    <div class="cf-impact-banner" id="cf-exchange-impact-banner">
                        <span><strong>Drawer Cash Impact:</strong> <span id="ex-preview-drawer" style="font-weight:700;">-Rs 0.00</span></span>
                        <span><strong>Online Balance Impact:</strong> <span id="ex-preview-online" style="font-weight:700;">+Rs 0.00</span></span>
                        <span><strong>Store Commission:</strong> <span id="ex-preview-fee" style="font-weight:700; color:#059669;">+Rs 0.00</span></span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================
         TAB 4: RECEIVABLES & PAYABLES LEDGER
         ======================================================== -->
    <div id="tab-content-dues" class="cf-tab-panel <?= $activeTab === 'dues' ? 'active' : '' ?>">
        <div class="cf-split-grid">
            <!-- Left: Accounts Receivable -->
            <div class="cf-ledger-panel panel-recv">
                <div class="cf-ledger-header">
                    <div>
                        <div class="cf-ledger-title" style="color:#b45309;">
                            <span class="material-symbols-rounded">call_received</span>
                            Accounts Receivable (Customer Dues)
                        </div>
                        <div class="cf-ledger-sub">Outstanding client credit balances awaiting collection</div>
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
                                    <?= $currency ?> <?= number_format((float)$rec['total_due'], 2) ?>
                                </div>
                                <div style="margin-top:6px; display:flex; gap:6px; justify-content:flex-end;">
                                    <?php if (!empty($rec['phone'])): ?>
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $rec['phone']) ?>" target="_blank" class="btn-solid-emerald btn-xs" title="Send WhatsApp Reminder">
                                            <span class="material-symbols-rounded" style="font-size:14px;">chat</span>
                                            WhatsApp
                                        </a>
                                        <a href="tel:<?= Security::escape($rec['phone']) ?>" class="btn-solid-dark btn-xs" title="Call Customer">
                                            <span class="material-symbols-rounded" style="font-size:14px;">call</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div style="margin-top:20px; text-align:center;">
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
                                    <?= $currency ?> <?= number_format((float)$pay['current_balance'], 2) ?>
                                </div>
                                <div style="margin-top:6px; display:flex; gap:6px; justify-content:flex-end;">
                                    <?php if (!empty($pay['phone'])): ?>
                                        <a href="tel:<?= Security::escape($pay['phone']) ?>" class="btn-solid-dark btn-xs" title="Call Supplier">
                                            <span class="material-symbols-rounded" style="font-size:14px;">call</span>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchase" class="btn-solid-rose btn-xs" title="Settle Invoice Balance">
                                        <span class="material-symbols-rounded" style="font-size:14px;">payments</span>
                                        Settle
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div style="margin-top:20px; text-align:center;">
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

<!-- ========================================================
     MODAL 1: SET / ADJUST BALANCE (Exact, Add, Withdraw)
     ======================================================== -->
<div class="cf-modal-backdrop" id="modal-set-balance">
    <div class="cf-modal-box">
        <div class="cf-modal-head">
            <div>
                <h3>Set or Reconcile Account Balance</h3>
                <p>Adjust physical counter till drawer or reconcile digital account balances.</p>
            </div>
            <button type="button" class="cf-modal-close-btn" onclick="window.cfApp.closeSetBalanceModal()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/setBalance" method="POST">
            <?= Security::csrfField() ?>
            <div style="display:flex; flex-direction:column; gap:14px;">
                <!-- Account Selector -->
                <div class="cf-field-group">
                    <label class="cf-field-label">Target Financial Account *</label>
                    <select name="account_id" id="set-bal-account-id" class="cf-select" required onchange="window.cfApp.handleAccountSelectChange()">
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= (int)$acc['id'] ?>" data-balance="<?= (float)$acc['current_balance'] ?>" data-name="<?= Security::escape($acc['account_name']) ?>" <?= ((int)$acc['is_default'] === 1 || $acc['account_type'] === 'cash') ? 'selected' : '' ?>>
                                <?= Security::escape($acc['account_name']) ?> (Current: <?= $currency ?> <?= number_format((float)$acc['current_balance'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Mode Selector -->
                <div class="cf-field-group">
                    <label class="cf-field-label">Adjustment Mode *</label>
                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:6px;">
                        <label style="border:1px solid #0284c7; background:#f0f9ff; border-radius:8px; padding:8px 6px; text-align:center; font-size:11.5px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:4px;" id="mode-label-exact">
                            <input type="radio" name="mode" value="set_exact" checked onchange="window.cfApp.updateModeUI()" style="display:none;">
                            <span class="material-symbols-rounded" style="font-size:16px; color:#0284c7;">pin</span>
                            Set Exact Total
                        </label>
                        <label style="border:1px solid #cbd5e1; border-radius:8px; padding:8px 6px; text-align:center; font-size:11.5px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:4px;" id="mode-label-add">
                            <input type="radio" name="mode" value="add_cash" onchange="window.cfApp.updateModeUI()" style="display:none;">
                            <span class="material-symbols-rounded" style="font-size:16px; color:#059669;">add_circle</span>
                            Add Inflow (+)
                        </label>
                        <label style="border:1px solid #cbd5e1; border-radius:8px; padding:8px 6px; text-align:center; font-size:11.5px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:4px;" id="mode-label-withdraw">
                            <input type="radio" name="mode" value="withdraw_cash" onchange="window.cfApp.updateModeUI()" style="display:none;">
                            <span class="material-symbols-rounded" style="font-size:16px; color:#e11d48;">remove_circle</span>
                            Withdraw (-)
                        </label>
                    </div>
                </div>

                <!-- Amount Input -->
                <div class="cf-field-group">
                    <label class="cf-field-label" id="set-bal-amount-label">New Exact Balance (<?= $currency ?>) *</label>
                    <input type="number" step="0.01" min="0" name="amount" id="set-bal-amount-input" class="cf-input" required style="font-size:16px; font-weight:800; color:#0f172a;">
                    <small style="font-size:11px; color:#64748b;" id="set-bal-calc-hint">Enter the audited total balance.</small>
                </div>

                <!-- Reason & Notes -->
                <div class="cf-field-group">
                    <label class="cf-field-label">Reason for Adjustment</label>
                    <input type="text" name="reason" class="cf-input" placeholder="e.g. Counter Till Count, Opening Cash Added">
                </div>

                <div class="cf-field-group">
                    <label class="cf-field-label">Audit Notes (Optional)</label>
                    <textarea name="notes" class="cf-textarea" placeholder="Remarks or audit notes..."></textarea>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" class="btn-solid-gray btn-md" onclick="window.cfApp.closeSetBalanceModal()">Cancel</button>
                <button type="submit" class="btn-solid-emerald btn-md">
                    <span class="material-symbols-rounded">check</span>
                    Save Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================
     MODAL 2: ADD NEW FINANCIAL ACCOUNT
     ======================================================== -->
<div class="cf-modal-backdrop" id="modal-add-account">
    <div class="cf-modal-box">
        <div class="cf-modal-head">
            <div>
                <h3>Add New Financial Account</h3>
                <p>Register a new bank account, digital wallet, or counter cash drawer till.</p>
            </div>
            <button type="button" class="cf-modal-close-btn" onclick="window.cfApp.closeAddAccountModal()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/createAccount" method="POST">
            <?= Security::csrfField() ?>
            <div style="display:flex; flex-direction:column; gap:14px;">
                <div class="cf-field-group">
                    <label class="cf-field-label">Account Name / Title *</label>
                    <input type="text" name="account_name" class="cf-input" placeholder="e.g. Meezan Bank Current, EasyPaisa Main Shop" required>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="cf-field-group">
                        <label class="cf-field-label">Account Type *</label>
                        <select name="account_type" class="cf-select" required>
                            <option value="bank">Bank Account</option>
                            <option value="wallet" selected>Digital Wallet</option>
                            <option value="cash">Cash Till / Drawer</option>
                            <option value="other">Other Instrument</option>
                        </select>
                    </div>

                    <div class="cf-field-group">
                        <label class="cf-field-label">Bank / Institution Name</label>
                        <input type="text" name="bank_name" class="cf-input" placeholder="e.g. Meezan Bank, JazzCash">
                    </div>
                </div>

                <div class="cf-field-group">
                    <label class="cf-field-label">Account Number / IBAN Number *</label>
                    <input type="text" name="account_number" class="cf-input" placeholder="e.g. PK36MEZN00012345678901 / 03001234567" required>
                </div>

                <div class="cf-field-group">
                    <label class="cf-field-label">Opening Base Balance (<?= $currency ?>)</label>
                    <input type="number" step="0.01" min="0" name="opening_balance" value="0.00" class="cf-input" style="font-weight:700;">
                </div>

                <div class="cf-field-group">
                    <label class="cf-field-label">Notes (Optional)</label>
                    <textarea name="notes" class="cf-textarea" placeholder="Branch code, manager details, or purpose..."></textarea>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" class="btn-solid-gray btn-md" onclick="window.cfApp.closeAddAccountModal()">Cancel</button>
                <button type="submit" class="btn-solid-dark btn-md">
                    <span class="material-symbols-rounded">add_circle</span>
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================
     MODAL 3: EDIT ACCOUNT MODAL
     ======================================================== -->
<div class="cf-modal-backdrop" id="modal-edit-account">
    <div class="cf-modal-box">
        <div class="cf-modal-head">
            <div>
                <h3>Edit Financial Account</h3>
                <p id="edit-acc-subtitle">Update account details, IBAN, or notes.</p>
            </div>
            <button type="button" class="cf-modal-close-btn" onclick="window.cfApp.closeEditAccountModal()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <form action="" method="POST" id="form-edit-account">
            <?= Security::csrfField() ?>
            <div style="display:flex; flex-direction:column; gap:14px;">
                <div class="cf-field-group">
                    <label class="cf-field-label">Account Name / Title *</label>
                    <input type="text" name="account_name" id="edit-acc-name" class="cf-input" required>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="cf-field-group">
                        <label class="cf-field-label">Account Type</label>
                        <select name="account_type" id="edit-acc-type" class="cf-select">
                            <option value="bank">Bank Account</option>
                            <option value="wallet">Digital Wallet</option>
                            <option value="cash">Cash Till / Drawer</option>
                            <option value="other">Other Instrument</option>
                        </select>
                    </div>

                    <div class="cf-field-group">
                        <label class="cf-field-label">Bank / Institution Name</label>
                        <input type="text" name="bank_name" id="edit-acc-bank" class="cf-input">
                    </div>
                </div>

                <div class="cf-field-group">
                    <label class="cf-field-label">Account Number / IBAN Number</label>
                    <input type="text" name="account_number" id="edit-acc-number" class="cf-input">
                </div>

                <div class="cf-field-group">
                    <label class="cf-field-label">Opening Base Balance (<?= $currency ?>)</label>
                    <input type="number" step="0.01" min="0" name="opening_balance" id="edit-acc-opening" class="cf-input" style="font-weight:700;">
                </div>

                <div class="cf-field-group">
                    <label class="cf-field-label">Notes (Optional)</label>
                    <textarea name="notes" id="edit-acc-notes" class="cf-textarea"></textarea>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" class="btn-solid-gray btn-md" onclick="window.cfApp.closeEditAccountModal()">Cancel</button>
                <button type="submit" class="btn-solid-blue btn-md">
                    <span class="material-symbols-rounded">save</span>
                    Update Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================
     MODAL 4: VOUCHER SLIP PREVIEW MODAL
     ======================================================== -->
<div class="cf-modal-backdrop" id="modal-voucher-preview">
    <div class="cf-modal-box" style="max-width:440px;">
        <div class="cf-modal-head">
            <div>
                <h3>Transaction Voucher Slip</h3>
                <p id="vp-id">#TX-000</p>
            </div>
            <button type="button" class="cf-modal-close-btn" onclick="window.cfApp.closeVoucherModal()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <div style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:12px; padding:18px; font-size:12.5px; display:flex; flex-direction:column; gap:10px;">
            <div style="text-align:center; padding-bottom:8px; border-bottom:1px dashed #cbd5e1;">
                <div style="font-size:16px; font-weight:800; color:#0f172a;"><?= Security::escape($settings['company_name'] ?? 'DevAltum POS') ?></div>
                <div style="font-size:11px; color:#64748b; margin-top:2px;" id="vp-date">-</div>
                <div style="margin-top:6px;"><span class="cf-micro-badge badge-exchange" id="vp-type">EXCHANGE</span></div>
            </div>

            <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                <span style="color:#64748b;">From (Source):</span>
                <strong style="color:#be123c;" id="vp-from">-</strong>
            </div>
            <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                <span style="color:#64748b;">To (Destination):</span>
                <strong style="color:#059669;" id="vp-to">-</strong>
            </div>
            <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                <span style="color:#64748b;">Amount:</span>
                <strong style="font-size:16px; color:#0f172a;" id="vp-amount">Rs 0.00</strong>
            </div>
            <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;" id="vp-fee-row">
                <span style="color:#64748b;">Commission Fee:</span>
                <strong style="color:#059669;" id="vp-fee">+Rs 0.00</strong>
            </div>
            <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                <span style="color:#64748b;">Customer:</span>
                <strong style="color:#0f172a;" id="vp-party">-</strong>
            </div>
            <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                <span style="color:#64748b;">Customer Phone:</span>
                <span style="color:#334155;" id="vp-phone">-</span>
            </div>
            <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                <span style="color:#64748b;">Reference / IBAN:</span>
                <strong style="color:#0f172a; font-family:monospace; font-size:12px; background:#e2e8f0; padding:1px 6px; border-radius:4px;" id="vp-ref">-</strong>
            </div>
            <div style="display:flex; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:6px;">
                <span style="color:#64748b;">Description:</span>
                <span style="color:#334155; font-size:11.5px; text-align:right; max-width:230px;" id="vp-desc">-</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:#64748b;">Cashier / Staff:</span>
                <strong style="color:#0f172a;" id="vp-staff">-</strong>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:14px; border-top:1px solid #f1f5f9;">
            <button type="button" class="btn-solid-gray btn-md" onclick="window.cfApp.closeVoucherModal()">Close</button>
            <button type="button" class="btn-solid-blue btn-md" onclick="window.cfApp.printActiveVoucher()">
                <span class="material-symbols-rounded">print</span>
                Print Thermal Slip
            </button>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL 5: CUSTOM CONFIRMATION ACTION DIALOG
     ======================================================== -->
<div class="cf-modal-backdrop" id="modal-custom-confirm">
    <div class="cf-modal-box" style="max-width:420px; text-align:center;">
        <div style="width:52px; height:52px; border-radius:50%; background:#ffe4e6; color:#e11d48; display:grid; place-items:center; margin:0 auto 14px;">
            <span class="material-symbols-rounded" style="font-size:28px;" id="cf-confirm-icon">warning</span>
        </div>
        <h3 style="font-size:17px; font-weight:800; color:#0f172a; margin:0 0 6px;" id="cf-confirm-title">Are you sure?</h3>
        <p style="font-size:13px; color:#64748b; margin:0 0 20px; line-height:1.5;" id="cf-confirm-message">This action will be processed immediately.</p>

        <div style="display:flex; justify-content:center; gap:10px;">
            <button type="button" class="btn-solid-gray btn-md" onclick="window.cfApp.closeConfirmModal()" style="min-width:110px;">Cancel</button>
            <button type="button" class="btn-solid-rose btn-md" id="cf-confirm-yes-btn" style="min-width:120px;">Proceed</button>
        </div>
    </div>
</div>

<script>
(() => {
    // Encapsulated safe Cashflow App object attached to window.cfApp
    const getBaseUrl = () => window.BASE_URL || '';
    const getCsrfToken = () => {
        return window.appCsrfToken || document.querySelector('input[name="csrf_token"]')?.value || '<?= Security::generateCsrfToken() ?>';
    };
    const currency = '<?= $currency ?>';
    let activeVoucherTx = null;

    window.cfApp = {
        // Tab switching
        switchTab(tabName) {
            document.querySelectorAll('.cf-tab-btn').forEach(btn => {
                if (btn.dataset.cfTab === tabName) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            document.querySelectorAll('.cf-tab-panel').forEach(panel => {
                panel.classList.remove('active');
            });

            const activePanel = document.getElementById('tab-content-' + tabName);
            if (activePanel) {
                activePanel.classList.add('active');
            }

            try {
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', url);
            } catch(e) {}
        },

        // 1. Set Balance Modal
        openSetBalanceModal(accountId = null, accountName = '', currentBal = 0) {
            const modal = document.getElementById('modal-set-balance');
            const sel = document.getElementById('set-bal-account-id');
            const amtInput = document.getElementById('set-bal-amount-input');
            
            if (accountId && sel) {
                sel.value = accountId;
            }
            
            this.handleAccountSelectChange();
            if (modal) modal.style.display = 'grid';
            if (amtInput) amtInput.focus();
        },

        closeSetBalanceModal() {
            const modal = document.getElementById('modal-set-balance');
            if (modal) modal.style.display = 'none';
        },

        handleAccountSelectChange() {
            const sel = document.getElementById('set-bal-account-id');
            if (!sel || !sel.options.length) return;
            const opt = sel.options[sel.selectedIndex];
            const bal = parseFloat(opt.getAttribute('data-balance') || '0');
            const modeInput = document.querySelector('input[name="mode"]:checked');
            const mode = modeInput ? modeInput.value : 'set_exact';
            const amtInput = document.getElementById('set-bal-amount-input');
            const hint = document.getElementById('set-bal-calc-hint');
            
            if (mode === 'set_exact') {
                if (amtInput) amtInput.value = bal.toFixed(2);
                if (hint) hint.textContent = `Current balance in record: ${currency} ${bal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
            } else {
                if (hint) hint.textContent = `Current balance in record: ${currency} ${bal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
            }
        },

        updateModeUI() {
            const modeInput = document.querySelector('input[name="mode"]:checked');
            const mode = modeInput ? modeInput.value : 'set_exact';
            const lbl = document.getElementById('set-bal-amount-label');
            const amtInput = document.getElementById('set-bal-amount-input');
            
            const exactEl = document.getElementById('mode-label-exact');
            const addEl = document.getElementById('mode-label-add');
            const withEl = document.getElementById('mode-label-withdraw');

            if (exactEl) {
                exactEl.style.borderColor = (mode === 'set_exact') ? '#0284c7' : '#cbd5e1';
                exactEl.style.background = (mode === 'set_exact') ? '#f0f9ff' : '#ffffff';
            }
            if (addEl) {
                addEl.style.borderColor = (mode === 'add_cash') ? '#059669' : '#cbd5e1';
                addEl.style.background = (mode === 'add_cash') ? '#f0fdf4' : '#ffffff';
            }
            if (withEl) {
                withEl.style.borderColor = (mode === 'withdraw_cash') ? '#e11d48' : '#cbd5e1';
                withEl.style.background = (mode === 'withdraw_cash') ? '#fff1f2' : '#ffffff';
            }

            if (mode === 'set_exact') {
                if (lbl) lbl.textContent = `New Exact Total Balance (${currency}) *`;
                this.handleAccountSelectChange();
            } else if (mode === 'add_cash') {
                if (lbl) lbl.textContent = `Amount to Add / Inflow (${currency}) *`;
                if (amtInput) { amtInput.value = ''; amtInput.placeholder = 'e.g. 50000'; }
            } else {
                if (lbl) lbl.textContent = `Amount to Withdraw / Outflow (${currency}) *`;
                if (amtInput) { amtInput.value = ''; amtInput.placeholder = 'e.g. 10000'; }
            }
        },

        // 2. Add Account Modal
        openAddAccountModal() {
            const modal = document.getElementById('modal-add-account');
            if (modal) modal.style.display = 'grid';
        },
        closeAddAccountModal() {
            const modal = document.getElementById('modal-add-account');
            if (modal) modal.style.display = 'none';
        },

        // 3. Edit Account Modal
        openEditAccountModal(acc) {
            if (!acc) return;
            const form = document.getElementById('form-edit-account');
            if (form) form.action = `${getBaseUrl()}/cashflow/editAccount/${acc.id}`;
            document.getElementById('edit-acc-name').value = acc.account_name || '';
            document.getElementById('edit-acc-type').value = acc.account_type || 'bank';
            document.getElementById('edit-acc-bank').value = acc.bank_name || '';
            document.getElementById('edit-acc-number').value = acc.account_number || '';
            document.getElementById('edit-acc-opening').value = Number(acc.opening_balance || 0).toFixed(2);
            document.getElementById('edit-acc-notes').value = acc.notes || '';
            document.getElementById('edit-acc-subtitle').textContent = `Editing: ${acc.account_name}`;
            document.getElementById('modal-edit-account').style.display = 'grid';
        },
        closeEditAccountModal() {
            const modal = document.getElementById('modal-edit-account');
            if (modal) modal.style.display = 'none';
        },

        // 4. Custom Confirm Modal
        showConfirm(title, message, icon, onConfirm) {
            const modal = document.getElementById('modal-custom-confirm');
            document.getElementById('cf-confirm-title').textContent = title;
            document.getElementById('cf-confirm-message').textContent = message;
            document.getElementById('cf-confirm-icon').textContent = icon || 'warning';
            
            const yesBtn = document.getElementById('cf-confirm-yes-btn');
            const clone = yesBtn.cloneNode(true);
            yesBtn.parentNode.replaceChild(clone, yesBtn);
            
            clone.onclick = () => {
                this.closeConfirmModal();
                if (typeof onConfirm === 'function') onConfirm();
            };
            
            modal.style.display = 'grid';
        },

        closeConfirmModal() {
            const modal = document.getElementById('modal-custom-confirm');
            if (modal) modal.style.display = 'none';
        },

        confirmDeactivateAccount(accId, accName) {
            this.showConfirm(
                'Deactivate Account',
                `Are you sure you want to deactivate "${accName}"? Previous transactions will remain safely in the ledger.`,
                'block',
                () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `${getBaseUrl()}/cashflow/deleteAccount`;
                    
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = 'csrf_token';
                    csrf.value = getCsrfToken();
                    form.appendChild(csrf);

                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = accId;
                    form.appendChild(idInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            );
        },

        confirmDeleteTx(id, amount, type) {
            this.showConfirm(
                'Reverse Transaction',
                `Are you sure you want to reverse Transaction #TX-${id}? Amount: ${currency} ${Number(amount).toFixed(2)}. The funds will be restored to original accounts.`,
                'undo',
                () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `${getBaseUrl()}/cashflow/deleteTransaction`;

                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = 'csrf_token';
                    csrf.value = getCsrfToken();
                    form.appendChild(csrf);

                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = id;
                    form.appendChild(idInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            );
        },

        // 5. Gateway Combobox
        toggleGwDropdown() {
            const dropdown = document.getElementById('cf_gw_dropdown');
            const trigger = document.getElementById('cf_gw_trigger');
            if (!dropdown) return;
            const isOpen = dropdown.classList.contains('open');

            if (isOpen) {
                this.closeGwDropdown();
            } else {
                dropdown.classList.add('open');
                if (trigger) trigger.classList.add('active');
                const searchInput = document.getElementById('cf_gw_search');
                if (searchInput) {
                    searchInput.value = '';
                    this.filterGateways('');
                    setTimeout(() => searchInput.focus(), 50);
                }
            }
        },

        closeGwDropdown() {
            const dropdown = document.getElementById('cf_gw_dropdown');
            const trigger = document.getElementById('cf_gw_trigger');
            if (dropdown) dropdown.classList.remove('open');
            if (trigger) trigger.classList.remove('active');
        },

        selectGateway(id, name) {
            document.getElementById('cf_gw_label').textContent = name;
            document.getElementById('cf_input_channel').value = name;
            document.getElementById('cf_input_account_id').value = id || '';

            document.querySelectorAll('.cf-gw-item').forEach(el => {
                el.classList.remove('selected');
                if ((el.dataset.name || '').toLowerCase() === name.toLowerCase()) {
                    el.classList.add('selected');
                }
            });

            this.closeGwDropdown();
            this.updateExchangePreview();
        },

        filterGateways(term) {
            const q = (term || '').trim().toLowerCase();
            const items = document.querySelectorAll('.cf-gw-item');
            let exactMatch = false;

            items.forEach(item => {
                const name = (item.dataset.name || '').toLowerCase();
                if (name.includes(q)) {
                    item.style.display = 'flex';
                    if (name === q) exactMatch = true;
                } else {
                    item.style.display = 'none';
                }
            });

            const addPrompt = document.getElementById('cf_gw_add_prompt');
            const addBtnText = document.getElementById('cf_gw_add_text');

            if (q.length > 0 && !exactMatch) {
                if (addPrompt) addPrompt.style.display = 'block';
                if (addBtnText) addBtnText.textContent = `+ Add "${term.trim()}"`;
            } else {
                if (addPrompt) addPrompt.style.display = 'none';
            }
        },

        onGwSearchKey(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const addPrompt = document.getElementById('cf_gw_add_prompt');
                if (addPrompt && addPrompt.style.display !== 'none') {
                    this.addNewGateway();
                } else {
                    const firstVisible = Array.from(document.querySelectorAll('.cf-gw-item')).find(el => el.style.display !== 'none');
                    if (firstVisible) firstVisible.click();
                }
            } else if (e.key === 'Escape') {
                this.closeGwDropdown();
            }
        },

        addNewGateway() {
            const searchInput = document.getElementById('cf_gw_search');
            const name = (searchInput?.value || '').trim();
            if (!name) return;

            const formData = new FormData();
            formData.append('name', name);
            formData.append('csrf_token', getCsrfToken());

            fetch(`${getBaseUrl()}/cashflow/addGateway`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const list = document.getElementById('cf_gw_list');
                    const newItem = document.createElement('div');
                    newItem.className = 'cf-gw-item selected';
                    newItem.dataset.id = data.id;
                    newItem.dataset.name = data.name;
                    newItem.onclick = () => { this.selectGateway(data.id, data.name); };
                    newItem.innerHTML = `
                        <div class="cf-gw-item-left">
                            <span class="material-symbols-rounded">account_balance_wallet</span>
                            <span class="cf-gw-item-name">${data.name}</span>
                        </div>
                        <button type="button" class="cf-gw-del-btn" onclick="window.cfApp.deleteGateway(event, ${data.id}, '${data.name}')" title="Remove this gateway">
                            <span class="material-symbols-rounded">delete</span>
                        </button>
                    `;
                    list.appendChild(newItem);
                    this.selectGateway(data.id, data.name);
                    window.appToast?.(`Gateway "${data.name}" added successfully!`, 'success');
                } else {
                    window.appToast?.(data.message || 'Could not add gateway.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                window.appToast?.('Failed to save gateway.', 'error');
            });
        },

        deleteGateway(event, id, name) {
            event.stopPropagation();
            this.showConfirm(
                'Delete Gateway',
                `Are you sure you want to remove "${name}" from gateways list?`,
                'delete',
                () => {
                    const formData = new FormData();
                    formData.append('id', id);
                    formData.append('csrf_token', getCsrfToken());

                    fetch(`${getBaseUrl()}/cashflow/deleteGateway`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const targetItem = Array.from(document.querySelectorAll('.cf-gw-item')).find(el => el.dataset.id == id);
                            if (targetItem) targetItem.remove();

                            const currentChannel = document.getElementById('cf_input_channel').value;
                            if (currentChannel.toLowerCase() === name.toLowerCase()) {
                                const firstRemaining = document.querySelector('.cf-gw-item');
                                if (firstRemaining) {
                                    this.selectGateway(firstRemaining.dataset.id, firstRemaining.dataset.name);
                                } else {
                                    document.getElementById('cf_gw_label').textContent = 'Select Gateway';
                                    document.getElementById('cf_input_channel').value = '';
                                    document.getElementById('cf_input_account_id').value = '';
                                }
                            }
                            window.appToast?.(`Gateway "${name}" removed.`, 'success');
                        } else {
                            window.appToast?.(data.message || 'Could not remove gateway.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        window.appToast?.('Failed to remove gateway.', 'error');
                    });
                }
            );
        },

        // 6. Update Exchange Live Preview
        updateExchangePreview() {
            const dir = document.getElementById('ex-direction')?.value || 'online_to_cash';
            const amt = parseFloat(document.getElementById('ex-amount')?.value || '0');
            const fee = parseFloat(document.getElementById('ex-fee')?.value || '0');

            const drawerEl = document.getElementById('ex-preview-drawer');
            const onlineEl = document.getElementById('ex-preview-online');
            const feeEl = document.getElementById('ex-preview-fee');

            if (dir === 'online_to_cash') {
                if (drawerEl) drawerEl.innerHTML = `<span style="color:#e11d48; font-weight:800;">-${currency} ${amt.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>`;
                if (onlineEl) onlineEl.innerHTML = `<span style="color:#059669; font-weight:800;">+${currency} ${(amt + fee).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>`;
            } else {
                if (drawerEl) drawerEl.innerHTML = `<span style="color:#059669; font-weight:800;">+${currency} ${amt.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>`;
                if (onlineEl) onlineEl.innerHTML = `<span style="color:#e11d48; font-weight:800;">-${currency} ${amt.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>`;
            }
            if (feeEl) feeEl.innerHTML = `<span style="color:#059669; font-weight:800;">+${currency} ${fee.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>`;
        },

        // 7. Thermal Voucher Modal & Slip Print
        viewVoucherModal(tx) {
            if (!tx) return;
            activeVoucherTx = tx;
            const modal = document.getElementById('modal-voucher-preview');
            
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
        },

        closeVoucherModal() {
            const modal = document.getElementById('modal-voucher-preview');
            if (modal) modal.style.display = 'none';
        },

        printActiveVoucher() {
            if (activeVoucherTx) {
                this.printVoucherSlip(activeVoucherTx);
            }
        },

        printVoucherSlip(tx) {
            const storeName = <?= json_encode($settings['company_name'] ?? 'DevAltum POS') ?>;
            const printWindow = window.open('', '_blank', 'width=420,height=600');
            printWindow.document.write(`
                <!DOCTYPE html>
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
                            <div class="row"><span>Type:</span><span>${(tx.transaction_type || '').toUpperCase()}</span></div>
                            <div class="row"><span>Cashier:</span><span>${tx.cashier_name || 'Staff'}</span></div>
                        </div>
                        <div class="border-top">
                            <div class="row"><span>Customer:</span><span><strong>${tx.party_name || '-'}</strong></span></div>
                            <div class="row"><span>Phone:</span><span>${tx.party_phone || '-'}</span></div>
                            <div class="row"><span>Ref / IBAN:</span><span>${tx.reference_no || '-'}</span></div>
                        </div>
                        <div class="border-top">
                            <div class="row"><span>From:</span><span>${tx.from_account_name || 'Cash Till'}</span></div>
                            <div class="row"><span>To:</span><span>${tx.to_account_name || 'Online'}</span></div>
                            <div class="row" style="font-size:14px; font-weight:bold;"><span>Amount:</span><span>${currency} ${parseFloat(tx.amount || 0).toFixed(2)}</span></div>
                            ${parseFloat(tx.fee_amount || 0) > 0 ? `<div class="row"><span>Fee:</span><span>+ ${currency} ${parseFloat(tx.fee_amount).toFixed(2)}</span></div>` : ''}
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
    };

    // Close combobox on outside click
    document.addEventListener('click', function(e) {
        const combobox = document.getElementById('cf_gw_combobox');
        if (combobox && !combobox.contains(e.target)) {
            window.cfApp.closeGwDropdown();
        }
    });

    // Close modals on Esc or backdrop click
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.cfApp.closeSetBalanceModal();
            window.cfApp.closeAddAccountModal();
            window.cfApp.closeEditAccountModal();
            window.cfApp.closeVoucherModal();
            window.cfApp.closeConfirmModal();
            window.cfApp.closeGwDropdown();
        }
    });

    // Close modal when backdrop clicked
    document.querySelectorAll('.cf-modal-backdrop').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });

    // Initial setup
    window.cfApp.updateModeUI();
    window.cfApp.updateExchangePreview();
})();
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
