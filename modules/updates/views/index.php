<?php ob_start(); ?>
<?php
$settingsCompanyNtn = trim((string) ($settings['company_ntn'] ?? ''));
if ($settingsCompanyNtn === '') {
    $settingsCompanyNtn = trim((string) ($settings['company_ntn_number'] ?? ($settings['ntn'] ?? '')));
}
?>

<style>
    .settings-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: nowrap;
        overflow-x: auto;
        padding-bottom: 6px;
        margin-bottom: 20px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .settings-tabs::-webkit-scrollbar {
        display: none;
    }
    .settings-tab-btn {
        flex-shrink: 0;
        white-space: nowrap;
        border: 1px solid var(--border);
        background: #fff;
        color: var(--text-main);
        border-radius: 999px;
        padding: 10px 18px;
        font-size: 13.5px;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition);
    }
    .settings-tab-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
    .settings-tab-btn.active {
        background: linear-gradient(135deg, #0f6dff, #2b87ff);
        color: #fff;
        border-color: #0f6dff;
        box-shadow: 0 4px 14px rgba(15, 109, 255, 0.25);
    }
    .settings-panel {
        display: none;
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
        overflow-x: hidden !important;
    }
    .settings-panel.active { display: block !important; }
    .settings-surface {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
        overflow-x: hidden !important;
    }
    .settings-hero {
        display: grid;
        grid-template-columns: 1.15fr .85fr;
        gap: 20px;
        margin-bottom: 20px;
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .settings-kpis {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .settings-kpi {
        padding: 18px;
        border: 1px solid var(--border);
        border-radius: 20px;
        background: linear-gradient(180deg, #fff, #f8fbff);
        min-width: 0;
    }
    .settings-kpi small { display:block; color:var(--text-muted); margin-bottom:8px; }
    .settings-kpi strong { font-size:24px; }
    .drop-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:18px; }
    .drop-card { position:relative; border:1.5px dashed var(--border-strong); border-radius:24px; background:linear-gradient(180deg, #fff, #f8fbff); padding:22px; min-height:260px; overflow:hidden; }
    .logo-remove-form { position:absolute; top:14px; right:14px; z-index:3; }
    .logo-remove-btn { width:42px; height:42px; border:none; border-radius:14px; display:grid; place-items:center; background:rgba(255,255,255,0.96); color:var(--error); box-shadow:var(--shadow-sm); cursor:pointer; transition:var(--transition); }
    .logo-remove-btn:hover { transform:translateY(-1px); background:#fff1f2; }
    .drop-card.dragover { border-color:var(--primary); background:linear-gradient(180deg, #eff6ff, #ffffff); }
    .drop-card input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; }
    .drop-preview { display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; min-height:210px; gap:12px; pointer-events:none; }
    .drop-preview img { width:110px; height:110px; object-fit:cover; border-radius:24px; border:1px solid var(--border); background:#fff; box-shadow:var(--shadow-sm); }
    .drop-preview .icon { width:64px; height:64px; display:grid; place-items:center; border-radius:20px; background:#eff6ff; color:var(--primary); }
    .drop-preview .icon .material-symbols-rounded { font-size:30px; }
    .settings-stack { display:flex; flex-direction:column; gap:18px; }
    .settings-surface { padding:22px; border:1px solid var(--border); border-radius:24px; background:#fff; box-shadow:var(--shadow-sm); }
    .package-list, .history-list, .backup-list { display:flex; flex-direction:column; gap:14px; }
    .package-card, .history-card, .backup-card { padding:18px; border:1px solid var(--border); border-radius:20px; background:linear-gradient(180deg, #fff, #f8fbff); }
    .package-card-head, .history-card-head, .backup-card-head { display:flex; justify-content:space-between; gap:14px; align-items:flex-start; }
    .history-card-head > div:first-child { min-width:0; flex:1; }
    .history-actions { flex-shrink:0; }
    .package-actions, .history-actions { display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
    .package-meta, .history-meta { display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; color:var(--text-muted); font-size:13px; }
    .warning-chips { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
    .warning-chip { padding:6px 10px; border-radius:999px; background:#fff7ed; color:#c2410c; font-size:12px; font-weight:700; }
    .settings-form-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:16px; }
    .settings-form-grid .full { grid-column:1 / -1; }
    .template-token-row { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
    .template-token-row span { padding:6px 9px; border-radius:999px; background:#eef4ff; color:#1d4ed8; font-size:12px; font-weight:800; }
    .settings-inline-form { display:inline; }
    .support-note { padding:16px 18px; border-radius:18px; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; }
    .display-setting-card { display:grid; grid-template-columns:58px 1fr auto; gap:16px; align-items:center; padding:20px; border:1px solid var(--border); border-radius:22px; background:linear-gradient(135deg, #fff, #f8fbff); }
    .display-setting-icon { width:58px; height:58px; border-radius:18px; display:grid; place-items:center; background:#eff6ff; color:var(--primary); }
    .display-setting-icon .material-symbols-rounded { font-size:30px; }
    .tax-command { border:1px solid rgba(15,109,255,.18); border-radius:24px; padding:20px; background:radial-gradient(circle at top right, rgba(16,185,129,.18), transparent 34%), linear-gradient(135deg,#fff,#f7fbff); margin-bottom:18px; }
    .tax-status-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:12px; margin-top:16px; }
    .tax-status-card { border:1px solid var(--border); border-radius:18px; padding:14px; background:#fff; }
    .tax-status-card small { display:block; color:var(--text-muted); font-weight:900; margin-bottom:5px; }
    .tax-status-card strong { color:#0f172a; }

    .device-hub-shell { padding:0; overflow:hidden; }
    .device-hub-head {
        display:grid;
        grid-template-columns:54px minmax(0, 1fr) auto;
        gap:14px;
        align-items:center;
        padding:18px;
        border-bottom:1px solid var(--border);
        background:linear-gradient(135deg, #f8fbff, #ffffff);
    }
    .device-hub-icon { width:54px; height:54px; border-radius:18px; display:grid; place-items:center; background:#111827; color:#fff; box-shadow:0 12px 26px rgba(15,23,42,.16); }
    .device-hub-icon .material-symbols-rounded { font-size:28px; }
    .device-hub-head h3 { margin:0 0 4px; }
    .device-hub-head .page-subtitle { margin:0; max-width:720px; }
    .device-hub-badge { display:inline-flex; align-items:center; gap:8px; padding:9px 12px; border-radius:999px; background:#ecfdf5; color:#047857; border:1px solid #bbf7d0; font-size:12px; font-weight:900; white-space:nowrap; }
    .device-hub-body { padding:18px; }
    .scanner-qr-card { position:sticky; top:calc(var(--header-height) + 18px); border:1px solid rgba(15,23,42,.12); border-radius:24px; padding:20px; background:linear-gradient(160deg, #111827, #1e3a8a); color:#fff; box-shadow:0 18px 42px rgba(15,23,42,.18); overflow:hidden; }
    .scanner-qr-card::before { content:''; position:absolute; inset:0; background:linear-gradient(90deg, rgba(255,255,255,.12), transparent 38%); pointer-events:none; }
    .scanner-qr-card > * { position:relative; z-index:1; }
    .scanner-qr-card h3 { margin:0 0 6px; }
    .scanner-qr-title { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
    .scanner-qr-title .material-symbols-rounded { width:40px; height:40px; display:grid; place-items:center; border-radius:14px; background:rgba(255,255,255,.12); }
    .scanner-qr-box { display:grid; place-items:center; background:#fff; border-radius:20px; padding:14px; margin:16px auto; width:244px; min-height:244px; box-shadow:0 18px 34px rgba(2,6,23,.28); }
    .scanner-qr-box svg,
    .scanner-qr-box img { width:216px; height:216px; display:block; object-fit:contain; }
    .scanner-link-box { word-break:break-all; padding:12px; border-radius:16px; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.16); color:#dbeafe; font-size:12px; font-weight:800; line-height:1.5; }
    .scanner-feature-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-top:16px; }
    .scanner-feature { padding:14px; border:1px solid var(--border); border-radius:18px; background:#fff; }
    .scanner-feature .material-symbols-rounded { color:var(--primary); margin-bottom:8px; }
    .device-subtabs { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:8px; margin-bottom:16px; padding:8px; border:1px solid var(--border); border-radius:18px; background:#f8fafc; }
    .device-subtab { border:1px solid transparent; background:transparent; color:#475569; border-radius:13px; min-height:44px; padding:10px 12px; font-weight:900; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:7px; transition:var(--transition); }
    .device-subtab .material-symbols-rounded { font-size:20px; }
    .device-subtab.active { background:#111827; color:#fff; border-color:#111827; box-shadow:0 12px 24px rgba(15,23,42,.15); }
    .device-subpanel { display:none; }
    .device-subpanel.active { display:block; }
    .device-status-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; }
    .device-status-card { border:1px solid var(--border); border-radius:18px; padding:16px; background:linear-gradient(180deg,#fff,#f8fbff); min-height:92px; display:flex; flex-direction:column; justify-content:space-between; }
    .device-status-card small { display:block; color:var(--text-muted); font-weight:800; margin-bottom:8px; }
    .device-status-card strong { display:flex; align-items:center; gap:8px; font-size:15px; }
    .device-status-card p { margin:8px 0 0; color:var(--text-muted); font-size:12px; font-weight:700; line-height:1.45; }
    .device-mode-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px; margin-bottom:16px; }
    .device-mode-card { border:1px solid var(--border); border-radius:20px; padding:18px; background:linear-gradient(180deg,#fff,#f8fbff); display:flex; gap:14px; align-items:flex-start; }
    .device-mode-card .material-symbols-rounded { width:42px; height:42px; border-radius:14px; display:grid; place-items:center; background:#eff6ff; color:var(--primary); flex-shrink:0; }
    .device-mode-card strong { display:block; margin-bottom:5px; }
    .device-mode-card p { margin:0; color:var(--text-muted); font-size:13px; font-weight:700; line-height:1.5; }
    .status-dot { width:10px; height:10px; border-radius:50%; background:#94a3b8; box-shadow:0 0 0 4px rgba(148,163,184,.16); flex-shrink:0; }
    .status-dot.ok { background:#10b981; box-shadow:0 0 0 4px rgba(16,185,129,.16); }
    .status-dot.warn { background:#f59e0b; box-shadow:0 0 0 4px rgba(245,158,11,.16); }
    .status-dot.bad { background:#ef4444; box-shadow:0 0 0 4px rgba(239,68,68,.16); }
    .scanner-console { border:1px solid rgba(15,23,42,.18); border-radius:18px; background:#020617; color:#dbeafe; padding:14px; min-height:118px; font:800 12px/1.6 Consolas, monospace; overflow:auto; box-shadow:inset 0 1px 0 rgba(255,255,255,.06); }
    .scanner-test-input { font-size:18px; font-weight:900; letter-spacing:.04em; text-transform:uppercase; }
    .device-action-row { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }
    .device-action-row .btn { min-height:44px; }
    .device-save-row { margin-top:16px; display:flex; justify-content:flex-end; }
    .premium-toggle { position:relative; display:inline-flex; width:44px; height:24px; flex-shrink:0; cursor:pointer; }
    .premium-toggle input { opacity:0; width:0; height:0; }
    .premium-toggle span { position:absolute; inset:0; border-radius:999px; background:#cbd5e1; cursor:pointer; transition:var(--transition); box-shadow:inset 0 1px 2px rgba(15,23,42,.15); }
    .premium-toggle span::before { content:''; position:absolute; width:18px; height:18px; left:3px; top:3px; border-radius:50%; background:#fff; box-shadow:0 2px 5px rgba(15,23,42,.2); transition:var(--transition); }
    .premium-toggle input:checked + span { background:linear-gradient(135deg, #0f6dff, #2563eb); }
    .premium-toggle input:checked + span::before { transform:translateX(20px); }
    @media (max-width: 1100px) {
        .settings-hero, .drop-grid, .settings-form-grid, .settings-kpis, .printer-scanner-grid, .scanner-feature-grid, .device-status-grid, .device-mode-grid, .mobile-scanner-pro, .mobile-scanner-steps, .mobile-scanner-settings, .tax-status-grid { grid-template-columns:1fr; }
        .scanner-qr-card { position:static; }
        .device-hub-head { grid-template-columns:48px minmax(0, 1fr); }
        .device-hub-badge { grid-column:1 / -1; justify-self:start; }
        .device-subtabs { grid-template-columns:repeat(2, minmax(0,1fr)); }
        .display-setting-card { grid-template-columns:48px 1fr; }
        .display-setting-card .premium-toggle { grid-column:1 / -1; justify-self:start; }
        .history-card-head { flex-direction:column; align-items:stretch; }
        .history-actions { width:100%; }
        .history-actions .btn { width:100%; justify-content:center; }
    }
    @media (max-width: 560px) {
        .device-hub-body { padding:14px; }
        .device-subtabs { grid-template-columns:1fr; }
        .device-subtab { justify-content:flex-start; }
        .scanner-qr-box { width:100%; max-width:224px; }
        .device-action-row .btn,
        .device-save-row .btn { width:100%; justify-content:center; }
        .mobile-scanner-head { flex-direction:column; }
        .mobile-scanner-hero { padding:18px; border-radius:22px; }
        .mobile-scanner-qr { border-radius:22px; }
        .mobile-scanner-qr-box { width:100%; max-width:236px; min-height:236px; }
        .mobile-scanner-qr-box svg { width:208px; height:208px; }
    }

    /* ========================================================
       RECEIPT CUSTOMIZER & DESIGNER STUDIO STYLES
       ======================================================== */
    .designer-studio-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, 340px);
        gap: 22px;
        align-items: start;
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .designer-btn-short,
    .subtab-label-short {
        display: none;
    }
    .designer-btn-full,
    .subtab-label-full {
        display: inline;
    }
    .designer-subtabs {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        padding: 6px;
        border: 1px solid var(--border);
        border-radius: 16px;
        background: #f8fafc;
        margin-bottom: 18px;
    }
    .designer-subtab-btn {
        border: 1px solid transparent;
        background: transparent;
        color: #475569;
        border-radius: 12px;
        min-height: 42px;
        padding: 8px 12px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 13px;
        transition: var(--transition);
    }
    .designer-subtab-btn .material-symbols-rounded {
        font-size: 19px;
    }
    .designer-subtab-btn.active {
        background: #0f172a;
        color: #fff;
        border-color: #0f172a;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
    }
    .designer-subpanel {
        display: none;
    }
    .designer-subpanel.active {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .designer-group-card {
        border: 1px solid var(--border);
        border-radius: 18px;
        background: #ffffff;
        padding: 16px 18px;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
    }
    .designer-group-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13.5px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #f1f5f9;
    }
    .designer-group-title .material-symbols-rounded {
        font-size: 20px;
        color: var(--primary);
    }
    .designer-toggles-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 14px;
    }
    .designer-toggle-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #eef2f6;
        transition: var(--transition);
    }
    .designer-toggle-row:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    .designer-toggle-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #1e293b;
    }
    .designer-toggle-sub {
        font-size: 11px;
        color: #64748b;
        display: block;
        margin-top: 1px;
    }
    .designer-order-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .designer-order-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: 14px;
        background: #ffffff;
        cursor: grab;
        transition: all 0.18s ease;
        user-select: none;
    }
    .designer-order-item:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(15, 109, 255, 0.08);
        transform: translateY(-1px);
    }
    .designer-order-item.dragging {
        opacity: 0.45;
        border: 1.5px dashed var(--primary);
        background: #eff6ff;
    }
    .designer-order-item.drag-over {
        border-top: 2.5px solid var(--primary);
        transform: translateY(2px);
    }
    .designer-order-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .designer-order-handle {
        color: #94a3b8;
        cursor: grab;
        display: flex;
        align-items: center;
    }
    .designer-order-info strong {
        font-size: 13px;
        display: block;
        color: #0f172a;
    }
    .designer-order-info small {
        font-size: 11px;
        color: #64748b;
    }
    .designer-order-actions {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .designer-btn-icon {
        width: 30px;
        height: 30px;
        border: none;
        background: #f1f5f9;
        border-radius: 8px;
        cursor: pointer;
        display: grid;
        place-items: center;
        color: #475569;
        transition: var(--transition);
    }
    .designer-btn-icon:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .designer-preview-wrap {
        position: sticky;
        top: calc(var(--header-height, 70px) + 16px);
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .designer-preview-card {
        border: 1px solid var(--border);
        border-radius: 20px;
        background: #f8fafc;
        padding: 16px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }
    .designer-preview-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e2e8f0;
    }
    .designer-preview-header strong {
        font-size: 13px;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .designer-preview-badge {
        font-size: 10px;
        font-weight: 800;
        background: #dbeafe;
        color: #1e40af;
        padding: 3px 8px;
        border-radius: 999px;
        letter-spacing: 0.3px;
    }
    .virtual-thermal-slip {
        width: 100%;
        max-width: 300px;
        margin: 0 auto;
        background: #ffffff;
        border: 1px solid #c8cbcf;
        border-radius: 4px;
        padding: 14px 10px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        font-family: 'Segoe UI', Arial, sans-serif;
        color: #0f172a;
        font-size: 10px;
        line-height: 1.35;
    }
    .virtual-thermal-slip strong { font-weight: 600; }
    .virtual-thermal-slip .preview-sec { transition: opacity 0.2s ease, transform 0.2s ease; }
    .virtual-thermal-slip .preview-sec.is-hidden { display: none !important; }
    @media (max-width: 992px) {
        .designer-studio-grid {
            grid-template-columns: 1fr !important;
            gap: 18px !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }
        .designer-controls-column {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            overflow: hidden !important;
        }
        .designer-preview-wrap {
            position: static !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }
        .designer-toggles-list {
            grid-template-columns: 1fr !important;
        }
    }
    @media (max-width: 768px) {
        .settings-surface {
            padding: 14px 10px !important;
            border-radius: 18px !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            min-width: 0 !important;
            overflow-x: hidden !important;
        }
        .settings-hero {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
            margin-bottom: 14px !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }
        .settings-kpis {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 6px !important;
            width: 100% !important;
            min-width: 0 !important;
        }
        .settings-kpi {
            padding: 10px 8px !important;
            border-radius: 12px !important;
            text-align: center !important;
            min-width: 0 !important;
        }
        .settings-kpi small {
            font-size: 9.5px !important;
            margin-bottom: 2px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        .settings-kpi strong {
            font-size: 15px !important;
        }
        .settings-tabs {
            gap: 6px !important;
            margin-bottom: 14px !important;
            padding-bottom: 4px !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        .settings-tab-btn {
            padding: 7px 12px !important;
            font-size: 12px !important;
            border-radius: 999px !important;
        }
        .drop-grid {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
        }
        .drop-card {
            min-height: 140px !important;
            padding: 14px 12px !important;
            border-radius: 16px !important;
        }
        .drop-preview {
            min-height: 110px !important;
            gap: 6px !important;
        }
        .drop-preview img {
            width: 60px !important;
            height: 60px !important;
            border-radius: 14px !important;
        }
        .drop-preview .icon {
            width: 44px !important;
            height: 44px !important;
            border-radius: 12px !important;
        }
        .drop-preview .icon .material-symbols-rounded {
            font-size: 22px !important;
        }
        .drop-preview strong {
            font-size: 12.5px !important;
        }
        .drop-preview .page-subtitle {
            font-size: 11px !important;
        }
        .logo-remove-btn {
            width: 32px !important;
            height: 32px !important;
            border-radius: 10px !important;
            top: 10px !important;
            right: 10px !important;
        }

        .designer-btn-full,
        .subtab-label-full {
            display: none !important;
        }
        .designer-btn-short,
        .subtab-label-short {
            display: inline !important;
        }

        /* Receipt Designer Header & Buttons */
        .designer-header-row {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 12px !important;
            margin-bottom: 16px !important;
            padding-bottom: 14px !important;
            width: 100% !important;
        }
        .designer-header-left {
            align-items: flex-start !important;
            gap: 10px !important;
            width: 100% !important;
        }
        .designer-header-left > div:first-child {
            width: 38px !important;
            height: 38px !important;
            border-radius: 10px !important;
            flex-shrink: 0 !important;
        }
        .designer-header-left > div:first-child .material-symbols-rounded {
            font-size: 20px !important;
        }
        .designer-header-left h3 {
            font-size: 15px !important;
            line-height: 1.25 !important;
        }
        .designer-header-left p {
            font-size: 11px !important;
            line-height: 1.35 !important;
        }
        .designer-header-actions {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 8px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .designer-header-actions .btn {
            width: 100% !important;
            min-height: 38px !important;
            padding: 6px 8px !important;
            font-size: 12px !important;
            font-weight: 800 !important;
            border-radius: 10px !important;
            white-space: nowrap !important;
            justify-content: center !important;
            box-sizing: border-box !important;
        }
        .designer-header-actions .btn .material-symbols-rounded {
            font-size: 17px !important;
        }

        /* Subtabs */
        .designer-subtabs {
            display: flex !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            padding: 3px !important;
            gap: 4px !important;
            border-radius: 12px !important;
            margin-bottom: 12px !important;
        }
        .designer-subtab-btn {
            flex: 1 1 0 !important;
            min-width: 0 !important;
            white-space: nowrap !important;
            min-height: 36px !important;
            font-size: 11px !important;
            padding: 6px 4px !important;
            gap: 4px !important;
            border-radius: 9px !important;
            justify-content: center !important;
        }
        .designer-subtab-btn .material-symbols-rounded {
            font-size: 16px !important;
        }

        /* Field Toggles */
        .designer-group-card {
            padding: 10px 8px !important;
            border-radius: 14px !important;
        }
        .designer-group-title {
            font-size: 12px !important;
            margin-bottom: 8px !important;
            padding-bottom: 6px !important;
            gap: 6px !important;
        }
        .designer-group-title .material-symbols-rounded {
            font-size: 17px !important;
        }
        .designer-toggle-row {
            padding: 6px 8px !important;
            min-height: 36px !important;
            border-radius: 10px !important;
        }
        .designer-toggle-row > div:first-child {
            min-width: 0;
            flex: 1;
            padding-right: 6px;
        }
        .designer-toggle-label {
            font-size: 11.5px !important;
            font-weight: 700 !important;
            white-space: normal !important;
            line-height: 1.2 !important;
        }
        .designer-toggle-sub {
            font-size: 10px !important;
            color: #64748b !important;
            white-space: normal !important;
            line-height: 1.2 !important;
        }
        .premium-toggle {
            width: 36px !important;
            height: 20px !important;
        }
        .premium-toggle span::before {
            width: 14px !important;
            height: 14px !important;
            left: 3px !important;
            top: 3px !important;
        }
        .premium-toggle input:checked + span::before {
            transform: translateX(16px) !important;
        }

        /* DevAltum Controls */
        .devaltum-controls-grid {
            grid-template-columns: 1fr !important;
            gap: 8px !important;
        }
        .devaltum-controls-grid > div {
            padding: 8px 10px !important;
            border-radius: 10px !important;
        }
        .devaltum-controls-grid label {
            font-size: 11.5px !important;
        }
        .devaltum-controls-grid select {
            height: 34px !important;
            min-height: 34px !important;
            font-size: 11.5px !important;
            padding: 4px 8px !important;
        }

        /* Section Reorder */
        .designer-order-item {
            padding: 6px 8px !important;
            border-radius: 10px !important;
            gap: 6px !important;
        }
        .designer-order-left {
            min-width: 0;
            flex: 1;
            gap: 8px !important;
        }
        .designer-order-left .material-symbols-rounded {
            font-size: 18px !important;
        }
        .designer-order-info {
            min-width: 0;
            overflow: hidden;
        }
        .designer-order-info strong {
            font-size: 11.5px !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        .designer-order-info small {
            font-size: 10px !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        .designer-btn-icon {
            width: 26px !important;
            height: 26px !important;
            border-radius: 6px !important;
        }
        .designer-btn-icon .material-symbols-rounded {
            font-size: 15px !important;
        }

        /* 80mm Virtual Thermal Slip Preview (Realistic Proportions) */
        .designer-preview-card {
            padding: 10px 8px !important;
            border-radius: 14px !important;
        }
        .designer-preview-header {
            margin-bottom: 8px !important;
            padding-bottom: 6px !important;
        }
        .designer-preview-header strong {
            font-size: 12px !important;
        }
        .designer-preview-badge {
            font-size: 9px !important;
            padding: 2px 6px !important;
        }
        .virtual-thermal-slip {
            width: 100% !important;
            max-width: 270px !important;
            margin: 0 auto !important;
            padding: 10px 8px !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            border-radius: 6px !important;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06) !important;
        }
        .virtual-thermal-slip .preview-part-logo img {
            max-width: 72px !important;
            max-height: 28px !important;
            object-fit: contain !important;
            margin: 0 auto 3px !important;
        }
        .virtual-thermal-slip table {
            table-layout: fixed !important;
            width: 100% !important;
            word-break: break-word !important;
            font-size: 8.5px !important;
        }
        .virtual-thermal-slip th,
        .virtual-thermal-slip td {
            padding: 2px 3px !important;
        }
        .virtual-thermal-slip svg {
            max-width: 155px !important;
            height: 28px !important;
            margin: 2px auto !important;
            display: block !important;
        }
        #prev-credit-logo {
            max-height: 16px !important;
            max-width: 44px !important;
        }
    }
    /* Compact Premium Confirmation Modal */
    .premium-confirm-overlay {
        position: fixed;
        inset: 0;
        z-index: 99999;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.22s ease-out;
    }
    .premium-confirm-overlay.is-open {
        opacity: 1;
        visibility: visible;
    }
    .premium-confirm-card {
        width: 100%;
        max-width: 380px;
        background: #ffffff;
        border-radius: 22px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.28), 0 0 0 1px rgba(15, 23, 42, 0.05);
        padding: 24px 22px 20px;
        text-align: center;
        transform: scale(0.92) translateY(12px);
        transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .premium-confirm-overlay.is-open .premium-confirm-card {
        transform: scale(1) translateY(0);
    }
    .premium-confirm-icon-wrap {
        width: 54px;
        height: 54px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 14px;
        box-shadow: 0 8px 20px -4px rgba(15, 109, 255, 0.25);
    }
    .premium-confirm-icon-wrap.primary {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        color: #0f6dff;
    }
    .premium-confirm-icon-wrap.danger {
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        color: #ef4444;
        box-shadow: 0 8px 20px -4px rgba(239, 68, 68, 0.25);
    }
    .premium-confirm-icon-wrap.warning {
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        color: #d97706;
        box-shadow: 0 8px 20px -4px rgba(217, 119, 6, 0.25);
    }
    .premium-confirm-icon-wrap .material-symbols-rounded {
        font-size: 28px;
    }
    .premium-confirm-title {
        font-size: 16.5px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 6px;
        letter-spacing: -0.2px;
    }
    .premium-confirm-desc {
        font-size: 12.5px;
        color: #64748b;
        margin: 0 0 20px;
        line-height: 1.45;
        padding: 0 8px;
    }
    .premium-confirm-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    .premium-confirm-buttons .btn {
        flex: 1;
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 700;
        border-radius: 12px;
        min-height: 40px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    /* Display & POS Card Studio */
    .display-customizer-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(320px, 0.95fr);
        gap: 24px;
        align-items: start;
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    @media (max-width: 980px) {
        .display-customizer-shell {
            grid-template-columns: 1fr;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }
    }
    .display-config-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 20px 22px;
        margin-bottom: 18px;
        box-shadow: 0 2px 10px rgba(15,23,42,0.03);
        transition: border-color 0.15s ease;
    }
    .display-config-card:hover {
        border-color: #cbd5e1;
    }
    .display-config-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }
    .display-config-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: #eff6ff;
        color: #0f6dff;
        font-size: 20px;
        flex-shrink: 0;
    }
    .display-config-title {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .display-config-desc {
        font-size: 12.5px;
        color: #64748b;
        margin: 2px 0 0;
    }
    .layout-choice-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    @media (max-width: 600px) {
        .layout-choice-grid {
            grid-template-columns: 1fr;
        }
    }
    .layout-choice-card {
        position: relative;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        padding: 16px;
        cursor: pointer;
        background: #fff;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .layout-choice-card:hover {
        border-color: #93c5fd;
        background: #f8fbff;
    }
    .layout-choice-card input[type="radio"] {
        position: absolute;
        top: 14px;
        right: 14px;
        accent-color: #0f6dff;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    .layout-choice-card.active {
        border-color: #0f6dff;
        background: #eff6ff;
        box-shadow: 0 4px 14px rgba(15,109,255,0.12);
    }
    .layout-choice-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 800;
        padding: 3px 8px;
        border-radius: 6px;
        background: #e2e8f0;
        color: #475569;
        width: fit-content;
    }
    .layout-choice-card.active .layout-choice-badge {
        background: #0f6dff;
        color: #fff;
    }
    .visibility-options-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .visibility-option-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
        transition: all 0.15s ease;
    }
    .visibility-option-row:hover {
        background: #ffffff;
        border-color: #cbd5e1;
    }
    .visibility-option-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .visibility-option-icon {
        color: #64748b;
        font-size: 20px;
    }
    .visibility-option-title {
        font-size: 13.5px;
        font-weight: 750;
        color: #1e293b;
    }
    .visibility-option-hint {
        font-size: 11.5px;
        color: #64748b;
    }
    .preset-chips-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 6px;
    }
    .preset-chip-btn {
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
        padding: 7px 12px;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 750;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }
    .preset-chip-btn:hover {
        border-color: #0f6dff;
        color: #0f6dff;
        background: #eff6ff;
    }

    /* Live Preview Studio */
    .preview-sticky-wrap {
        position: sticky;
        top: calc(var(--header-height, 64px) + 20px);
    }
    .preview-studio-box {
        background: #0f172a;
        border-radius: 24px;
        border: 1px solid #1e293b;
        padding: 22px;
        color: #fff;
        box-shadow: 0 16px 36px rgba(15,23,42,0.22);
    }
    .preview-studio-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .preview-live-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        background: rgba(16,185,129,0.18);
        border: 1px solid rgba(16,185,129,0.35);
        color: #34d399;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .live-pulse-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.3);
        animation: livePulseDot 1.8s infinite;
    }
    @keyframes livePulseDot {
        0% { transform: scale(0.95); opacity: 0.8; }
        50% { transform: scale(1.3); opacity: 1; }
        100% { transform: scale(0.95); opacity: 0.8; }
    }
    .simulated-pos-shelf {
        background: #f1f5f9;
        border-radius: 18px;
        padding: 18px 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 230px;
        position: relative;
    }
    .preview-card-wrap {
        width: 100%;
        max-width: 180px;
    }

    /* Live Preview Card Standard Mode */
    .live-card-sim {
        position: relative;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 10px;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 110px;
        user-select: none;
        box-shadow: 0 2px 8px rgba(15,23,42,0.06);
    }
    .live-card-sim:hover {
        transform: translateY(-2px);
        border-color: #0f6dff;
        box-shadow: 0 8px 18px rgba(15,109,255,0.18);
    }
    .live-card-sim:active {
        transform: scale(0.96);
    }
    .live-card-sim-main {
        display: flex;
        gap: 8px;
        align-items: flex-start;
    }
    .live-card-sim-imgbox {
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        display: grid;
        place-items: center;
    }
    .live-card-sim-imgbox img {
        width: 100%;
        height: 100%;
        display: block;
    }
    .live-card-sim-info {
        min-width: 0;
        flex: 1;
    }
    .live-card-sim .stock-mini {
        display: inline-block;
        padding: 1px 5px;
        border-radius: 4px;
        background: #ecfdf5;
        color: #047857;
        font-size: 9.5px;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 2px;
    }
    .live-card-sim h3 {
        font-size: 12px;
        font-weight: 750;
        color: #0f172a;
        margin: 2px 0 0 0;
        line-height: 1.25;
    }
    .live-card-sim .product-unit-caption {
        font-size: 10.5px;
        color: #64748b;
        font-weight: 650;
        margin-top: 2px;
    }
    .live-card-sim-foot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 8px;
        padding-top: 6px;
        border-top: 1px dashed #f1f5f9;
    }
    .live-card-sim .product-price-display {
        font-size: 13.5px;
        font-weight: 850;
        color: #0f6dff;
    }
    .live-card-sim .product-add-btn {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        background: #eff6ff;
        color: #0f6dff;
        border: 1px solid #bfdbfe;
        display: grid;
        place-items: center;
        padding: 0;
    }

    /* Live Preview Card 1:1 Visual Square Grid Mode */
    .live-card-sim.mode-visual-1x1 {
        aspect-ratio: 1 / 1;
        min-height: 0;
        padding: 0;
        overflow: hidden;
        display: block;
        border-radius: 14px;
        border: 1px solid #cbd5e1;
        box-shadow: 0 4px 14px rgba(15,23,42,0.08);
    }
    .live-card-sim.mode-visual-1x1:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 10px 24px rgba(15,109,255,0.22);
    }
    .live-card-sim.mode-visual-1x1 .live-card-sim-main {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        display: block;
        position: relative;
    }
    .live-card-sim.mode-visual-1x1 .live-card-sim-imgbox {
        width: 100%;
        height: 100%;
        flex: none;
        border-radius: 0;
        border: none;
        background: #ffffff;
        position: absolute;
        inset: 0;
    }
    .live-card-sim.mode-visual-1x1 .live-card-sim-imgbox img {
        width: 100%;
        height: 100%;
        display: block;
    }
    .live-card-sim.mode-visual-1x1 .live-card-sim-info {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        padding: 8px 10px;
        background: linear-gradient(180deg, rgba(15,23,42,0.78) 0%, rgba(15,23,42,0.3) 70%, transparent 100%);
        color: #fff;
        z-index: 2;
        pointer-events: none;
    }
    .live-card-sim.mode-visual-1x1 .live-card-sim-info h3 {
        color: #ffffff;
        text-shadow: 0 1px 3px rgba(0,0,0,0.6);
        font-size: 11.5px;
        margin: 0;
    }
    .live-card-sim.mode-visual-1x1 .live-card-sim-info .product-unit-caption {
        color: rgba(255,255,255,0.85);
        font-size: 9.5px;
    }
    .live-card-sim.mode-visual-1x1 .live-card-sim-info .stock-mini {
        position: absolute;
        top: 8px;
        right: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    }
    .live-card-sim.mode-visual-1x1 .live-card-sim-foot {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 8px 10px;
        margin: 0;
        border-top: none;
        background: linear-gradient(0deg, rgba(15,23,42,0.85) 0%, rgba(15,23,42,0.4) 60%, transparent 100%);
        z-index: 2;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .live-card-sim.mode-visual-1x1 .product-price-display {
        color: #ffffff;
        font-size: 12.5px;
        font-weight: 800;
        text-shadow: 0 1px 3px rgba(0,0,0,0.8);
        background: rgba(15,23,42,0.72);
        backdrop-filter: blur(4px);
        padding: 3px 8px;
        border-radius: 6px;
        border: 1px solid rgba(255,255,255,0.22);
    }
    .live-card-sim.mode-visual-1x1 .product-add-btn {
        background: #0f6dff;
        color: #fff;
        border-color: #0f6dff;
        box-shadow: 0 2px 8px rgba(15,109,255,0.4);
    }
</style>

<div class="page-header">
    <div>
        <h1>Settings</h1>
        <p class="page-subtitle">Professional control center for branding, business preferences and checkout display options.</p>
    </div>
</div>

<div class="settings-hero">
    <section class="settings-surface">
        <div class="mobile-stack-flex" style="display:flex; justify-content:space-between; gap:18px; align-items:flex-start; margin-bottom:18px;">
            <div>
                <h3 style="margin-bottom:8px;">Control Overview</h3>
                <p class="page-subtitle">Everything is grouped into focused tabs so branding, business details and display preferences stay clean and manageable.</p>
            </div>
            <div class="support-note">Product images can be enabled or hidden from checkout with one clean display switch.</div>
        </div>
        <div class="settings-kpis">
            <div class="settings-kpi"><small>Product Images</small><strong><?php echo (($settings['show_product_images'] ?? '1') === '1') ? 'On' : 'Off'; ?></strong></div>
            <div class="settings-kpi"><small>Brand Assets</small><strong><?php echo (int) !empty($settings['website_logo']) + (int) !empty($settings['receipt_logo']) + (int) !empty($settings['app_icon']); ?></strong></div>
            <div class="settings-kpi"><small>Backups Ready</small><strong><?php echo count($backupFiles); ?></strong></div>
        </div>
    </section>

    <section class="settings-surface">
        <h3 style="margin-bottom:14px;">Quick Summary</h3>
        <div class="page-subtitle" style="margin-bottom:10px;">Business: <?php echo Security::escape($settings['company_name']); ?></div>
        <div class="page-subtitle" style="margin-bottom:10px;">NTN: <?php echo Security::escape($settingsCompanyNtn !== '' ? $settingsCompanyNtn : 'Not set'); ?></div>
        <div class="page-subtitle" style="margin-bottom:10px;">Address: <?php echo Security::escape($settings['company_address'] ?: 'Not set'); ?></div>
        <div class="page-subtitle" style="margin-bottom:10px;">Currency: <?php echo Security::escape($settings['currency_symbol']); ?> / <?php echo Security::escape($settings['currency_code']); ?></div>
        <div class="page-subtitle" style="margin-bottom:10px;">Website Logo: <?php echo $settings['website_logo'] ? 'Uploaded' : 'Not uploaded'; ?></div>
        <div class="page-subtitle" style="margin-bottom:10px;">Receipt Logo: <?php echo $settings['receipt_logo'] ? 'Uploaded' : 'Not uploaded'; ?></div>
        <div class="page-subtitle">Product Images: <?php echo (($settings['show_product_images'] ?? '1') === '1') ? 'Enabled' : 'Hidden'; ?></div>
    </section>
</div>

<div class="settings-tabs">
    <button class="settings-tab-btn active" type="button" data-settings-tab="business">Business</button>
    <?php if (empty($isBranchAccount)): ?>
    <button class="settings-tab-btn" type="button" data-settings-tab="branding">Branding</button>
    <?php endif; ?>
    <button class="settings-tab-btn" type="button" data-settings-tab="whatsapp">WhatsApp</button>
    <button class="settings-tab-btn" type="button" data-settings-tab="tax">Tax / FBR POS</button>
    <button class="settings-tab-btn" type="button" data-settings-tab="expiry">Inventory & Expiry</button>
    <button class="settings-tab-btn" type="button" data-settings-tab="display"><span class="material-symbols-rounded" style="font-size:18px; vertical-align:middle; margin-right:4px;">grid_view</span>Product Cards & Images</button>
    <button class="settings-tab-btn" type="button" data-settings-tab="receipt_designer"><span class="material-symbols-rounded" style="font-size:18px; vertical-align:middle; margin-right:4px;">receipt_long</span>Receipt Designer</button>
    <button class="settings-tab-btn" type="button" data-settings-tab="security">Security & Biometrics</button>
    <button class="settings-tab-btn" type="button" data-settings-tab="migration"><span class="material-symbols-rounded" style="font-size:18px; vertical-align:middle; margin-right:4px;">database</span>Database & Sync</button>
    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/software" class="settings-tab-btn" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; background:#eff6ff; color:#2563eb; border-color:#bfdbfe;"><span class="material-symbols-rounded" style="font-size:18px;">cloud_sync</span>Software Updates (Live)</a>
</div>

<section class="settings-panel active" data-settings-panel="business">
    <div class="settings-surface">
        <h3 style="margin-bottom:18px;">Business Settings</h3>
        <form id="business-settings-form" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/saveSettings" method="POST" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <input type="hidden" name="settings_payload" id="settings-payload" value="">
            <div class="settings-form-grid">
                <div class="form-group">
                    <label class="form-label">Company Name</label>
                    <input class="form-input" type="text" name="company_name" value="<?php echo Security::escape($settings['company_name']); ?>" <?php echo !empty($isBranchAccount) ? 'disabled' : ''; ?>>
                    <?php if (!empty($isBranchAccount)): ?>
                        <div class="page-subtitle" style="margin-top:6px;">Company name is controlled by the parent account.</div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Tagline</label>
                    <input class="form-input" type="text" name="company_tagline" value="<?php echo Security::escape($settings['company_tagline']); ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">Company Address</label>
                    <input class="form-input" type="text" name="company_address" value="<?php echo Security::escape($settings['company_address']); ?>" placeholder="e.g. Main Bazar, Lahore" <?php echo !empty($isBranchAccount) ? 'disabled' : ''; ?>>
                    <div class="page-subtitle" style="margin-top:6px;">
                        <?php echo !empty($isBranchAccount) ? 'Branch address is assigned from the parent account and appears on receipts.' : 'This address will appear in receipt header info block.'; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">NTN ID</label>
                    <input class="form-input" id="business-tax-id" type="text" name="tax_id" value="<?php echo Security::escape($settingsCompanyNtn); ?>" placeholder="e.g. 1234567-8">
                </div>
                <div class="form-group">
                    <label class="form-label">Currency Symbol</label>
                    <input class="form-input" type="text" name="currency_symbol" value="<?php echo Security::escape($settings['currency_symbol']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Currency Code</label>
                    <input class="form-input" type="text" name="currency_code" value="<?php echo Security::escape($settings['currency_code']); ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">Receipt Footer</label>
                    <textarea class="form-textarea" name="receipt_footer"><?php echo Security::escape($settings['receipt_footer']); ?></textarea>
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Save Business Settings</button>
        </form>
    </div>
</section>

<?php if (empty($isBranchAccount)): ?>
<section class="settings-panel" data-settings-panel="branding">
    <div class="settings-surface">
        <h3 style="margin-bottom:10px;">Branding Assets</h3>
        <p class="page-subtitle" style="margin-bottom:18px;">Drag and drop your logos into the upload areas below. Both cards support click-to-browse as well.</p>
        <div class="drop-grid">
            <div class="drop-card" data-drop-card>
                <?php if ($settings['website_logo']): ?>
                    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/removeLogo" method="POST" class="logo-remove-form" data-no-ajax>
                        <?php echo Security::csrfField(); ?>
                        <input type="hidden" name="field" value="website_logo">
                        <button class="logo-remove-btn" type="submit" title="Remove website logo">
                            <span class="material-symbols-rounded">delete</span>
                        </button>
                    </form>
                <?php endif; ?>
                <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/uploadBranding" method="POST" enctype="multipart/form-data" data-no-ajax>
                    <?php echo Security::csrfField(); ?>
                    <input type="file" name="website_logo" accept="image/*" data-preview-target="website-logo-preview">
                    <div class="drop-preview" id="website-logo-preview">
                        <?php 
                            $brandLogoPreview = !empty($settings['website_logo']) && App::mediaExists($settings['website_logo']) ? App::mediaUrl($settings['website_logo']) : '';
                        ?>
                        <?php if ($brandLogoPreview !== ''): ?>
                            <img src="<?php echo Security::escape($brandLogoPreview); ?>" alt="Website Logo" onerror="this.onerror=null; this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='block';">
                            <div class="icon" style="display:none;"><span class="material-symbols-rounded">image</span></div>
                        <?php else: ?>
                            <div class="icon"><span class="material-symbols-rounded">image</span></div>
                        <?php endif; ?>
                        <strong>Website Logo</strong>
                        <span class="page-subtitle">Drop logo here or click to browse</span>
                    </div>
                </form>
            </div>
            <div class="drop-card" data-drop-card>
                <?php if ($settings['receipt_logo']): ?>
                    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/removeLogo" method="POST" class="logo-remove-form" data-no-ajax>
                        <?php echo Security::csrfField(); ?>
                        <input type="hidden" name="field" value="receipt_logo">
                        <button class="logo-remove-btn" type="submit" title="Remove receipt logo">
                            <span class="material-symbols-rounded">delete</span>
                        </button>
                    </form>
                <?php endif; ?>
                <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/uploadBranding" method="POST" enctype="multipart/form-data" data-no-ajax>
                    <?php echo Security::csrfField(); ?>
                    <input type="file" name="receipt_logo" accept="image/*" data-preview-target="receipt-logo-preview">
                    <div class="drop-preview" id="receipt-logo-preview">
                        <?php 
                            $receiptLogoPreview = !empty($settings['receipt_logo']) && App::mediaExists($settings['receipt_logo']) ? App::mediaUrl($settings['receipt_logo']) : '';
                        ?>
                        <?php if ($receiptLogoPreview !== ''): ?>
                            <img src="<?php echo Security::escape($receiptLogoPreview); ?>" alt="Receipt Logo" onerror="this.onerror=null; this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='block';">
                            <div class="icon" style="display:none;"><span class="material-symbols-rounded">receipt_long</span></div>
                        <?php else: ?>
                            <div class="icon"><span class="material-symbols-rounded">receipt_long</span></div>
                        <?php endif; ?>
                        <strong>Receipt Logo</strong>
                        <span class="page-subtitle">Drop logo here or click to browse</span>
                    </div>
                </form>
            </div>
            
            <div class="drop-card" data-drop-card>
                <?php if ($settings['app_icon'] ?? false): ?>
                    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/removeLogo" method="POST" class="logo-remove-form" data-no-ajax>
                        <?php echo Security::csrfField(); ?>
                        <input type="hidden" name="field" value="app_icon">
                        <button class="logo-remove-btn" type="submit" title="Remove App Icon">
                            <span class="material-symbols-rounded">delete</span>
                        </button>
                    </form>
                <?php endif; ?>
                <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/uploadBranding" method="POST" enctype="multipart/form-data" data-no-ajax>
                    <?php echo Security::csrfField(); ?>
                    <input type="file" name="app_icon" accept="image/*" data-preview-target="app-icon-preview">
                    <div class="drop-preview" id="app-icon-preview">
                        <?php 
                            $appIconPreview = !empty($settings['app_icon']) && App::mediaExists($settings['app_icon']) ? App::mediaUrl($settings['app_icon']) : '';
                        ?>
                        <?php if ($appIconPreview !== ''): ?>
                            <img src="<?php echo Security::escape($appIconPreview); ?>" alt="App Icon" onerror="this.onerror=null; this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='block';">
                            <div class="icon" style="display:none;"><span class="material-symbols-rounded">install_mobile</span></div>
                        <?php else: ?>
                            <div class="icon"><span class="material-symbols-rounded">install_mobile</span></div>
                        <?php endif; ?>
                        <strong>PWA App Icon</strong>
                        <span class="page-subtitle">Drop icon here (Square 512x512 recommended)</span>
                    </div>
                </form>
            </div>
            
        </div>
        <div style="margin-top:18px; display:flex; gap:10px; flex-wrap:wrap;">
            <div class="page-subtitle">Drop a logo card or click it to upload instantly. Remove icons are available on uploaded logos.</div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="settings-panel" data-settings-panel="whatsapp">
    <div class="settings-surface">
        <h3 style="margin-bottom:10px;">WhatsApp Settings</h3>
        <p class="page-subtitle" style="margin-bottom:18px;">Configure customer WhatsApp messages and credit reminder template.</p>
        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/saveSettings" method="POST" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <div class="settings-form-grid">
                <div class="form-group">
                    <label class="form-label">Company WhatsApp Number</label>
                    <input class="form-input" type="text" name="whatsapp_number" value="<?php echo Security::escape($settings['whatsapp_number']); ?>" placeholder="+923001234567">
                </div>
                <div class="form-group">
                    <label class="form-label">Receipt WhatsApp Message</label>
                    <input class="form-input" type="text" name="whatsapp_message" value="<?php echo Security::escape($settings['whatsapp_message']); ?>">
                </div>
                <div class="form-group full">
                    <label class="form-label">Credit WhatsApp Template</label>
                    <textarea class="form-textarea" name="credit_whatsapp_template" style="min-height:190px;"><?php echo Security::escape($settings['credit_whatsapp_template'] ?? ''); ?></textarea>
                    <div class="page-subtitle" style="margin-top:6px;">Keep it simple. The credit button fills this template using customer and balance details.</div>
                    <div class="template-token-row">
                        <span>{customer_name}</span>
                        <span>{invoice_no}</span>
                        <span>{total}</span>
                        <span>{paid}</span>
                        <span>{remaining}</span>
                        <span>{due_date}</span>
                        <span>{company_name}</span>
                        <span>{company_phone}</span>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>Save WhatsApp Settings</button>
        </form>
    </div>
</section>

<section class="settings-panel" data-settings-panel="tax">
    <div class="settings-surface">
        <div class="tax-command">
            <div style="display:flex; justify-content:space-between; gap:16px; align-items:flex-start; flex-wrap:wrap;">
                <div>
                    <h3 style="margin-bottom:8px;">Tax & FBR POS Settings</h3>
                    <p class="page-subtitle" style="margin:0;">Enable automatic checkout tax and print the saved tax snapshot on receipts.</p>
                </div>
                <span class="scanner-token-pill"><span class="material-symbols-rounded">receipt_long</span><?php echo (($settings['tax_enabled'] ?? '0') === '1') ? 'Tax Active' : 'Tax Off'; ?></span>
            </div>
            <div class="tax-status-grid">
                <div class="tax-status-card"><small>Tax Rate</small><strong><?php echo Security::escape($settings['tax_rate'] ?? '18'); ?>%</strong></div>
                <div class="tax-status-card"><small>Tax Label</small><strong><?php echo Security::escape($settings['tax_label'] ?? 'Sales Tax'); ?></strong></div>
                <div class="tax-status-card"><small>FBR POS</small><strong><?php echo (($settings['fbr_pos_enabled'] ?? '0') === '1') ? 'Enabled' : 'Disabled'; ?></strong></div>
            </div>
        </div>

        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/saveSettings" method="POST" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <div class="display-setting-card" style="margin-bottom:16px;">
                <div class="display-setting-icon"><span class="material-symbols-rounded">percent</span></div>
                <div>
                    <strong>Auto Apply Tax on POS Checkout</strong>
                    <div class="page-subtitle" style="margin-top:6px;">When enabled, POS calculates tax after invoice discount and adds it to the final total.</div>
                </div>
                <label class="premium-toggle" title="Toggle tax">
                    <input type="hidden" name="tax_enabled" value="0">
                    <input type="checkbox" name="tax_enabled" value="1" <?php echo (($settings['tax_enabled'] ?? '0') === '1') ? 'checked' : ''; ?>>
                    <span></span>
                </label>
            </div>
            <div class="display-setting-card" style="margin-bottom:16px;">
                <div class="display-setting-icon"><span class="material-symbols-rounded">verified</span></div>
                <div>
                    <strong>FBR Based POS Mode</strong>
                    <div class="page-subtitle" style="margin-top:6px;">Show FBR POS ID and tax registration details on receipts when tax is enabled.</div>
                </div>
                <label class="premium-toggle" title="Toggle FBR POS mode">
                    <input type="hidden" name="fbr_pos_enabled" value="0">
                    <input type="checkbox" name="fbr_pos_enabled" value="1" <?php echo (($settings['fbr_pos_enabled'] ?? '0') === '1') ? 'checked' : ''; ?>>
                    <span></span>
                </label>
            </div>

            <div class="settings-form-grid">
                <div class="form-group">
                    <label class="form-label">Tax Label</label>
                    <input class="form-input" type="text" name="tax_label" value="<?php echo Security::escape($settings['tax_label'] ?? 'Sales Tax'); ?>" placeholder="Sales Tax">
                </div>
                <div class="form-group">
                    <label class="form-label">Tax Rate (%)</label>
                    <input class="form-input" type="number" min="0" max="100" step="0.001" name="tax_rate" value="<?php echo Security::escape($settings['tax_rate'] ?? '18'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Registration Type</label>
                    <select class="form-select" name="tax_registration_type">
                        <?php $taxType = (string) ($settings['tax_registration_type'] ?? 'STRN'); ?>
                        <option value="STRN" <?php echo $taxType === 'STRN' ? 'selected' : ''; ?>>STRN</option>
                        <option value="NTN" <?php echo $taxType === 'NTN' ? 'selected' : ''; ?>>NTN</option>
                        <option value="GST" <?php echo $taxType === 'GST' ? 'selected' : ''; ?>>GST</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tax Registration Number</label>
                    <input class="form-input" type="text" name="tax_number" value="<?php echo Security::escape($settings['tax_number'] ?? ''); ?>" placeholder="e.g. 1234567-8">
                </div>
                <div class="form-group">
                    <label class="form-label">FBR POS ID</label>
                    <input class="form-input" type="text" name="fbr_pos_id" value="<?php echo Security::escape($settings['fbr_pos_id'] ?? ''); ?>" placeholder="POS terminal ID">
                </div>
                <div class="form-group">
                    <label class="form-label">Receipt Note</label>
                    <input class="form-input" type="text" name="fbr_invoice_note" value="<?php echo Security::escape($settings['fbr_invoice_note'] ?? 'FBR POS integrated tax invoice'); ?>">
                </div>
            </div>
            <button class="btn btn-primary" type="submit" style="margin-top:16px;"><span class="material-symbols-rounded">save</span>Save Tax Settings</button>
        </form>
    </div>
</section>

<section class="settings-panel" data-settings-panel="expiry">
    <div class="settings-surface" style="background:#fff; border-radius:18px; border:1px solid #e2e8f0; padding:26px; box-shadow:0 2px 10px rgba(15,23,42,0.03);">
        
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; margin-bottom:22px; padding-bottom:18px; border-bottom:1px solid #f1f5f9;">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg, #fee2e2, #fecaca); color:#dc2626; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <span class="material-symbols-rounded" style="font-size:26px;">alarm_off</span>
                </div>
                <div>
                    <h3 style="margin:0; font-size:18px; font-weight:800; color:#0f172a;">Batch Numbers & Expiry Date Management</h3>
                    <p class="page-subtitle" style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
                        Configure expiration monitoring, warning thresholds, and automatic FIFO tracking for perishable inventory.
                    </p>
                </div>
            </div>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/expiryAlerts" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; font-weight:700; border-radius:10px;">
                <span class="material-symbols-rounded" style="font-size:18px; color:#dc2626;">notifications_active</span> View Expiry Alerts
            </a>
        </div>

        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/saveSettings" method="POST" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <input type="hidden" name="settings_payload" value="">

            <?php
            $expiryEnabled = (App::setting('enable_expiry_tracking', '0') === '1');
            $alertDays = (int) App::setting('expiry_alert_days', '30');
            $blockSale = (App::setting('block_expired_pos_sale', '0') === '1');
            ?>

            <div style="display:flex; flex-direction:column; gap:18px; margin-bottom:24px;">
                <!-- Master Enable / Disable Toggle -->
                <div class="display-setting-card" style="margin:0;">
                    <div class="display-setting-icon" style="background:rgba(220,38,38,0.1); color:#dc2626;">
                        <span class="material-symbols-rounded">event_available</span>
                    </div>
                    <div>
                        <strong style="font-size:14.5px; color:#0f172a;">Enable Expiry & Batch Tracking</strong>
                        <div class="page-subtitle" style="margin-top:4px;">
                            Enables batch numbers, manufacturing dates, and expiration date fields across products and purchases.
                        </div>
                    </div>
                    <label class="premium-toggle" title="Toggle Expiry Tracking">
                        <input type="checkbox" name="enable_expiry_tracking" value="1" <?php echo $expiryEnabled ? 'checked' : ''; ?> onchange="document.getElementById('expiry-config-sub').style.opacity = this.checked ? '1' : '0.5'; document.getElementById('expiry-config-sub').style.pointerEvents = this.checked ? 'auto' : 'none';">
                        <span></span>
                    </label>
                </div>

                <!-- Sub-options container -->
                <div id="expiry-config-sub" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px; transition:opacity 0.2s; <?php echo !$expiryEnabled ? 'opacity:0.5; pointer-events:none;' : ''; ?>">
                    
                    <!-- Alert Threshold Days -->
                    <div class="card" style="padding:18px; border-radius:14px; border:1px solid #e2e8f0; background:#f8fafc; margin:0;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                            <span class="material-symbols-rounded" style="color:#d97706; font-size:20px;">hourglass_top</span>
                            <strong style="font-size:13.5px; color:#0f172a;">Early Warning Alert Threshold</strong>
                        </div>
                        <p style="font-size:12px; color:#64748b; margin:0 0 12px 0;">
                            Notify in dashboard before this many days of product expiration.
                        </p>
                        <select class="form-select" name="expiry_alert_days" style="font-weight:700;">
                            <option value="15" <?php echo $alertDays === 15 ? 'selected' : ''; ?>>15 Days (Urgent Alert)</option>
                            <option value="30" <?php echo $alertDays === 30 ? 'selected' : ''; ?>>30 Days (Recommended / Standard)</option>
                            <option value="45" <?php echo $alertDays === 45 ? 'selected' : ''; ?>>45 Days</option>
                            <option value="60" <?php echo $alertDays === 60 ? 'selected' : ''; ?>>60 Days (2 Months)</option>
                            <option value="90" <?php echo $alertDays === 90 ? 'selected' : ''; ?>>90 Days (Quarterly Alert)</option>
                        </select>
                    </div>

                    <!-- Block Expired POS Sale -->
                    <div class="card" style="padding:18px; border-radius:14px; border:1px solid #e2e8f0; background:#f8fafc; margin:0; display:flex; flex-direction:column; justify-content:space-between;">
                        <div>
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                                <span class="material-symbols-rounded" style="color:#dc2626; font-size:20px;">block</span>
                                <strong style="font-size:13.5px; color:#0f172a;">Block Expired Items in POS</strong>
                            </div>
                            <p style="font-size:12px; color:#64748b; margin:0;">
                                Automatically prevent cashiers from scanning or adding expired stock to customer sales invoices.
                            </p>
                        </div>
                        <div style="display:flex; justify-content:flex-end; margin-top:14px;">
                            <label class="premium-toggle" title="Block Expired Sale">
                                <input type="checkbox" name="block_expired_pos_sale" value="1" <?php echo $blockSale ? 'checked' : ''; ?>>
                                <span></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary" type="submit" style="display:inline-flex; align-items:center; gap:8px; padding:10px 24px; font-weight:800; border-radius:10px;">
                <span class="material-symbols-rounded">save</span> Save Expiry Settings
            </button>
        </form>
    </div>
</section>

<section class="settings-panel" data-settings-panel="display">
    <div class="settings-surface" style="padding:26px; border-radius:24px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; margin-bottom:24px; padding-bottom:18px; border-bottom:1px solid #f1f5f9;">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg, #0f6dff, #38bdf8); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 6px 16px rgba(15,109,255,0.25);">
                    <span class="material-symbols-rounded" style="font-size:26px;">grid_view</span>
                </div>
                <div>
                    <h3 style="margin:0; font-size:18px; font-weight:800; color:#0f172a;">Product Cards & Display Studio</h3>
                    <p class="page-subtitle" style="margin:4px 0 0 0; font-size:13px; color:#64748b;">Configure 1:1 full-fit image mode, card layout, and information visibility on the POS checkout screen with live interactive preview.</p>
                </div>
            </div>
        </div>

        <?php
            $showImagesVal = ($settings['show_product_images'] ?? '1') === '1';
            $posLayoutVal = $settings['pos_card_layout'] ?? 'standard';
            $posFitVal = $settings['pos_image_fit'] ?? 'cover';
            $posShowNameVal = ($settings['pos_show_product_name'] ?? '1') === '1';
            $posShowStockVal = ($settings['pos_show_product_stock'] ?? '1') === '1';
            $posShowUnitVal = ($settings['pos_show_product_unit'] ?? '1') === '1';
            $posShowPriceVal = ($settings['pos_show_product_price'] ?? '1') === '1';
        ?>

        <div class="display-customizer-shell">
            <!-- LEFT: CONFIG CONTROLS FORM -->
            <form id="posDisplayForm" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/saveDisplaySettings" method="POST" data-no-ajax>
                <?php echo Security::csrfField(); ?>

                <!-- CARD 1: MASTER PRODUCT IMAGES TOGGLE -->
                <div class="display-config-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:16px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div class="display-config-icon"><span class="material-symbols-rounded">image</span></div>
                            <div>
                                <h4 class="display-config-title">Show Product Images</h4>
                                <div class="display-config-desc">Enable to display visual photos on POS cards. Turn off for pure high-speed text cards.</div>
                            </div>
                        </div>
                        <label class="premium-toggle" title="Toggle product images">
                            <input type="checkbox" id="ctrl_show_images" name="show_product_images" value="1" <?= $showImagesVal ? 'checked' : '' ?>>
                            <span></span>
                        </label>
                    </div>
                </div>

                <!-- CARD 2: CARD LAYOUT STYLE -->
                <div class="display-config-card">
                    <div class="display-config-header">
                        <div class="display-config-icon"><span class="material-symbols-rounded">dashboard</span></div>
                        <div>
                            <h4 class="display-config-title">Card Layout Style</h4>
                            <div class="display-config-desc">Choose between standard checkout cards and 1:1 square visual tiles.</div>
                        </div>
                    </div>
                    <div class="layout-choice-grid">
                        <label class="layout-choice-card <?= $posLayoutVal === 'standard' ? 'active' : '' ?>" id="label_layout_standard">
                            <input type="radio" name="pos_card_layout" value="standard" <?= $posLayoutVal === 'standard' ? 'checked' : '' ?>>
                            <span class="layout-choice-badge"><span class="material-symbols-rounded" style="font-size:13px;">view_agenda</span> Standard</span>
                            <div class="layout-choice-name">Standard Card</div>
                            <p class="layout-choice-desc">Balanced card with thumbnail, stock chip, title, unit and bottom price bar.</p>
                        </label>

                        <label class="layout-choice-card <?= $posLayoutVal === 'visual_1x1' ? 'active' : '' ?>" id="label_layout_visual">
                            <input type="radio" name="pos_card_layout" value="visual_1x1" <?= $posLayoutVal === 'visual_1x1' ? 'checked' : '' ?>>
                            <span class="layout-choice-badge"><span class="material-symbols-rounded" style="font-size:13px;">crop_square</span> 1:1 Visual</span>
                            <div class="layout-choice-name">1:1 Visual Square Grid</div>
                            <p class="layout-choice-desc">Edge-to-edge square tile with optional floating price tag. Touch-friendly & sleek.</p>
                        </label>
                    </div>
                </div>

                <!-- CARD 3: IMAGE FIT & SCALING -->
                <div class="display-config-card" id="imageFitGroup" style="<?= !$showImagesVal ? 'opacity:0.5; pointer-events:none;' : '' ?>">
                    <div class="display-config-header">
                        <div class="display-config-icon"><span class="material-symbols-rounded">aspect_ratio</span></div>
                        <div>
                            <h4 class="display-config-title">Image Scaling & Fit (Aspect Ratio)</h4>
                            <div class="display-config-desc">Ensure product images display crisp and professional without stretching.</div>
                        </div>
                    </div>
                    <div class="layout-choice-grid">
                        <label class="layout-choice-card <?= $posFitVal === 'cover' ? 'active' : '' ?>" id="label_fit_cover">
                            <input type="radio" name="pos_image_fit" value="cover" <?= $posFitVal === 'cover' ? 'checked' : '' ?>>
                            <span class="layout-choice-badge"><span class="material-symbols-rounded" style="font-size:13px;">fullscreen</span> Recommended</span>
                            <div class="layout-choice-name">Cover (Full Fit, No Stretch)</div>
                            <p class="layout-choice-desc">Fills the entire 1:1 container seamlessly with zero distortion or stretching.</p>
                        </label>

                        <label class="layout-choice-card <?= $posFitVal === 'contain' ? 'active' : '' ?>" id="label_fit_contain">
                            <input type="radio" name="pos_image_fit" value="contain" <?= $posFitVal === 'contain' ? 'checked' : '' ?>>
                            <span class="layout-choice-badge"><span class="material-symbols-rounded" style="font-size:13px;">fit_screen</span> Frame Fit</span>
                            <div class="layout-choice-name">Contain (Show Entire Photo)</div>
                            <p class="layout-choice-desc">Fits the complete photo inside the frame without clipping edges.</p>
                        </label>
                    </div>
                </div>

                <!-- CARD 4: INFORMATION ELEMENTS VISIBILITY -->
                <div class="display-config-card">
                    <div class="display-config-header">
                        <div class="display-config-icon"><span class="material-symbols-rounded">tune</span></div>
                        <div>
                            <h4 class="display-config-title">Card Information Visibility</h4>
                            <div class="display-config-desc">Select which elements appear on checkout cards. Uncheck title & stock for pure image catalog.</div>
                        </div>
                    </div>

                    <div class="visibility-options-list">
                        <!-- Product Name -->
                        <div class="visibility-option-row">
                            <div class="visibility-option-info">
                                <span class="material-symbols-rounded visibility-option-icon">title</span>
                                <div>
                                    <div class="visibility-option-title">Product Title / Name</div>
                                    <div class="visibility-option-hint">Shows product title on the card</div>
                                </div>
                            </div>
                            <label class="premium-toggle">
                                <input type="checkbox" id="ctrl_show_name" name="pos_show_product_name" value="1" <?= $posShowNameVal ? 'checked' : '' ?>>
                                <span></span>
                            </label>
                        </div>

                        <!-- Stock Badge -->
                        <div class="visibility-option-row">
                            <div class="visibility-option-info">
                                <span class="material-symbols-rounded visibility-option-icon">inventory</span>
                                <div>
                                    <div class="visibility-option-title">Stock Level Badge</div>
                                    <div class="visibility-option-hint">Displays live available stock count (e.g. Stock: 24 PCS)</div>
                                </div>
                            </div>
                            <label class="premium-toggle">
                                <input type="checkbox" id="ctrl_show_stock" name="pos_show_product_stock" value="1" <?= $posShowStockVal ? 'checked' : '' ?>>
                                <span></span>
                            </label>
                        </div>

                        <!-- Unit Caption -->
                        <div class="visibility-option-row">
                            <div class="visibility-option-info">
                                <span class="material-symbols-rounded visibility-option-icon">straighten</span>
                                <div>
                                    <div class="visibility-option-title">Measurement Unit</div>
                                    <div class="visibility-option-hint">Shows product unit packaging (e.g. 1 PCS, 1 KG)</div>
                                </div>
                            </div>
                            <label class="premium-toggle">
                                <input type="checkbox" id="ctrl_show_unit" name="pos_show_product_unit" value="1" <?= $posShowUnitVal ? 'checked' : '' ?>>
                                <span></span>
                            </label>
                        </div>

                        <!-- Price Badge -->
                        <div class="visibility-option-row">
                            <div class="visibility-option-info">
                                <span class="material-symbols-rounded visibility-option-icon">payments</span>
                                <div>
                                    <div class="visibility-option-title">Price Display</div>
                                    <div class="visibility-option-hint">Shows retail price badge (floats at bottom in 1:1 mode)</div>
                                </div>
                            </div>
                            <label class="premium-toggle">
                                <input type="checkbox" id="ctrl_show_price" name="pos_show_product_price" value="1" <?= $posShowPriceVal ? 'checked' : '' ?>>
                                <span></span>
                            </label>
                        </div>
                    </div>

                    <!-- QUICK PRESETS -->
                    <div style="margin-top:18px; padding-top:14px; border-top:1px dashed #e2e8f0;">
                        <small style="font-size:11.5px; font-weight:800; text-transform:uppercase; color:#64748b; letter-spacing:0.04em; display:block; margin-bottom:8px;">Quick One-Click Presets</small>
                        <div class="preset-chips-row">
                            <button type="button" class="preset-chip-btn" id="btnPresetImageOnly">
                                <span class="material-symbols-rounded" style="font-size:16px; color:#0f6dff;">crop_square</span>
                                1:1 Image Only + Price (Clean)
                            </button>
                            <button type="button" class="preset-chip-btn" id="btnPresetStandard">
                                <span class="material-symbols-rounded" style="font-size:16px; color:#0f6dff;">view_agenda</span>
                                Complete Standard Retail
                            </button>
                            <button type="button" class="preset-chip-btn" id="btnPresetMinimal">
                                <span class="material-symbols-rounded" style="font-size:16px; color:#0f6dff;">photo_camera</span>
                                1:1 Image + Title + Price
                            </button>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit" style="display:inline-flex; align-items:center; gap:8px; padding:12px 28px; font-size:14px; font-weight:800; border-radius:12px; box-shadow:0 6px 18px rgba(15,109,255,0.25);">
                    <span class="material-symbols-rounded">save</span> Save Display Settings
                </button>
            </form>

            <!-- RIGHT: STICKY LIVE INTERACTIVE PREVIEW STUDIO -->
            <div class="preview-sticky-wrap">
                <div class="preview-studio-box">
                    <div class="preview-studio-header">
                        <div>
                            <span class="preview-live-tag"><span class="live-pulse-dot"></span> Live Preview</span>
                            <div style="font-size:13px; font-weight:800; margin-top:4px; color:#f8fafc;">POS Checkout Simulation</div>
                        </div>
                        <div style="font-size:11px; color:#94a3b8; font-weight:700;">Updates in real time</div>
                    </div>

                    <!-- SAMPLE PRODUCT SWITCHER -->
                    <div style="display:flex; gap:6px; margin-bottom:14px; overflow-x:auto; padding-bottom:4px;">
                        <button type="button" class="sample-select-btn active" data-sample="headset" style="padding:4px 10px; font-size:11px; font-weight:800; border-radius:6px; border:1px solid #334155; background:#1e293b; color:#fff; cursor:pointer;"><span class="material-symbols-rounded" style="font-size:14px; vertical-align:middle; margin-right:3px;">headphones</span>Tech Item</button>
                        <button type="button" class="sample-select-btn" data-sample="coffee" style="padding:4px 10px; font-size:11px; font-weight:800; border-radius:6px; border:1px solid #334155; background:#1e293b; color:#94a3b8; cursor:pointer;"><span class="material-symbols-rounded" style="font-size:14px; vertical-align:middle; margin-right:3px;">coffee</span>Cafe / Drink</button>
                        <button type="button" class="sample-select-btn" data-sample="apparel" style="padding:4px 10px; font-size:11px; font-weight:800; border-radius:6px; border:1px solid #334155; background:#1e293b; color:#94a3b8; cursor:pointer;"><span class="material-symbols-rounded" style="font-size:14px; vertical-align:middle; margin-right:3px;">apparel</span>Fashion / Shoe</button>
                    </div>

                    <!-- SIMULATED POS SHELF -->
                    <div class="simulated-pos-shelf" id="previewPosShelf">
                        <div class="preview-card-wrap">
                            <article class="live-card-sim" id="livePreviewCard" title="Click to test simulated checkout click!">
                                <div class="live-card-sim-main">
                                    <div class="live-card-sim-imgbox" id="previewImgBox">
                                        <img id="previewImgEl" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 400'><rect width='400' height='400' fill='%23f1f5f9'/><circle cx='200' cy='200' r='140' fill='%230f6dff' opacity='0.15'/><path d='M140 210c0-33 27-60 60-60s60 27 60 60v35c0 14-11 25-25 25h-5c-6 0-10-4-10-10v-30c0-6 4-10 10-10h10v-10c0-22-18-40-40-40s-40 18-40 40v10h10c6 0 10 4 10 10v30c0 6-4 10-10 10h-5c-14 0-25-11-25-25v-35z' fill='%230f6dff'/></svg>" alt="Sample Product">
                                    </div>
                                    <div class="live-card-sim-info" id="previewInfoBox">
                                        <span class="stock-mini" id="previewStockEl">Stock: 24 PCS</span>
                                        <h3 id="previewTitleEl">Wireless Studio Headphones</h3>
                                        <div class="product-unit-caption" id="previewUnitEl">1 PCS</div>
                                    </div>
                                </div>
                                <div class="live-card-sim-foot" id="previewFootBox">
                                    <div class="product-price-display" id="previewPriceEl">Rs 4,850</div>
                                    <button type="button" class="product-add-btn" id="previewAddBtn" title="Add to Cart">
                                        <span class="material-symbols-rounded" style="font-size:17px;">add</span>
                                    </button>
                                </div>
                            </article>
                        </div>

                        <!-- CLICK TO TEST HINT -->
                        <div id="previewClickToast" style="position:absolute; bottom:8px; font-size:11px; font-weight:750; color:#047857; background:#dcfce7; padding:3px 10px; border-radius:999px; opacity:0; transition:opacity 0.2s ease; pointer-events:none; display:inline-flex; align-items:center; gap:4px;">
                            <span class="material-symbols-rounded" style="font-size:14px;">check_circle</span> Added (Simulated Cart)
                        </div>
                    </div>

                    <!-- LIVE EXPLANATION BANNER -->
                    <div style="margin-top:16px; padding:12px 14px; border-radius:14px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.08);">
                        <div style="display:flex; align-items:center; gap:8px; font-size:12px; font-weight:800; color:#38bdf8;">
                            <span class="material-symbols-rounded" style="font-size:16px;">info</span>
                            <span id="previewModeTitle">Standard Card Layout</span>
                        </div>
                        <p id="previewModeDesc" style="margin:4px 0 0 0; font-size:11.5px; color:#cbd5e1; line-height:1.45;">
                            Thumbnail and title visible with full pricing bar. Click the card above to test interaction.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="settings-panel" data-settings-panel="security">
    <div class="settings-surface" style="background:#fff; border-radius:18px; border:1px solid #e2e8f0; padding:26px; box-shadow:0 2px 10px rgba(15,23,42,0.03);">
        
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; margin-bottom:22px; padding-bottom:18px; border-bottom:1px solid #f1f5f9;">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg, #eff6ff, #dbeafe); color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <span class="material-symbols-rounded" style="font-size:26px;">fingerprint</span>
                </div>
                <div>
                    <h3 style="margin:0; font-size:18px; font-weight:800; color:#0f172a;">Biometric & Passkey Authentication</h3>
                    <p class="page-subtitle" style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
                        Sign in instantly with your Laptop Fingerprint (Windows Hello), Touch ID, or Smartphone Biometrics.
                    </p>
                </div>
            </div>
            <div id="device_compat_badge" style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:999px; font-size:12px; font-weight:700; background:#f8fafc; color:#64748b; border:1px solid #e2e8f0;">
                <span class="material-symbols-rounded" style="font-size:16px;">sync</span> Checking device sensor...
            </div>
        </div>

        <?php $bioEnabled = (App::setting('enable_biometric_login', '1') === '1'); ?>
        <!-- Master Enable / Disable Toggle -->
        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 18px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="material-symbols-rounded" style="color:#2563eb; font-size:22px;">fingerprint</span>
                <strong style="font-size:14px; font-weight:700; color:#0f172a;">Enable Biometric & Fingerprint Sign-In</strong>
            </div>
            <label class="premium-toggle" title="Toggle Biometric Login" style="margin:0;">
                <input type="checkbox" id="toggle_enable_biometrics" onchange="toggleBiometricSetting(this.checked)" value="1" <?php echo $bioEnabled ? 'checked' : ''; ?>>
                <span></span>
            </label>
        </div>

        <!-- Register Device Card -->
        <div id="register_device_card_container" style="background:linear-gradient(145deg, #f8fafc, #f1f5f9); border:1px solid #e2e8f0; border-radius:14px; padding:18px 20px; margin-bottom:24px; transition:opacity 0.2s ease; <?php echo !$bioEnabled ? 'opacity:0.55; pointer-events:none;' : ''; ?>">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px;">
                <div>
                    <div style="font-weight:800; font-size:14px; color:#0f172a; display:flex; align-items:center; gap:6px;">
                        <span class="material-symbols-rounded" style="font-size:18px; color:#2563eb;">add_circle</span>
                        Register Current Device
                    </div>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;" id="device_detected_note">
                        Detecting hardware biometric sensor...
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <div style="position:relative; flex:1 1 280px; min-width:220px; max-width:440px;">
                    <span class="material-symbols-rounded" id="bio_device_icon" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:18px; color:#2563eb; pointer-events:none;">devices</span>
                    <input type="text" id="bio_device_name" class="form-input" readonly style="height:40px; padding-left:38px; padding-right:85px; font-size:13px; font-weight:700; color:#1e293b; background:#fff; border:1px solid #cbd5e1; border-radius:10px; cursor:default; user-select:none;" value="Detecting Device...">
                    <span style="position:absolute; right:8px; top:50%; transform:translateY(-50%); font-size:10.5px; font-weight:800; color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0; padding:2px 8px; border-radius:6px; pointer-events:none; display:flex; align-items:center; gap:3px;">
                        <span class="material-symbols-rounded" style="font-size:12px;">lock</span> Auto
                    </span>
                </div>
                <button type="button" onclick="registerBiometricDevice()" id="bioRegBtn" class="btn btn-primary" style="height:40px; padding:0 18px; border-radius:10px; font-size:13px; font-weight:700; display:inline-flex; align-items:center; gap:8px;">
                    <span class="material-symbols-rounded" style="font-size:18px;">fingerprint</span>
                    <span>Register Sensor</span>
                </button>
            </div>
            
            <div id="bio_status_msg" style="margin-top:12px; font-size:13px; font-weight:600; padding:10px 14px; border-radius:8px; display:none;"></div>
        </div>

        <!-- Registered Devices List -->
        <div style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
            <h4 style="margin:0; color:#0f172a; font-size:14px; font-weight:800; display:flex; align-items:center; gap:6px;">
                <span class="material-symbols-rounded" style="font-size:18px; color:#64748b;">phonelink_lock</span>
                Registered Biometric Devices
            </h4>
            <span style="font-size:12px; color:#64748b; font-weight:600;"><?= count($biometrics) ?> Registered</span>
        </div>

        <div style="border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; background:#fff;">
            <?php if (empty($biometrics)): ?>
                <div style="text-align:center; padding:36px 20px; color:#64748b;">
                    <div style="width:48px; height:48px; border-radius:50%; background:#f1f5f9; color:#94a3b8; display:flex; align-items:center; justify-content:center; margin:0 auto 10px;">
                        <span class="material-symbols-rounded" style="font-size:24px;">fingerprint</span>
                    </div>
                    <div style="font-weight:700; font-size:14px; color:#334155; margin-bottom:4px;">No Biometric Keys Enrolled</div>
                    <div style="font-size:12px; color:#94a3b8; max-width:360px; margin:0 auto;">Click "Register Sensor" above on your laptop or smartphone to enable instant passwordless login.</div>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column;">
                    <?php foreach ($biometrics as $b): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 18px; border-bottom:1px solid #f1f5f9; gap:12px; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="width:38px; height:38px; border-radius:10px; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <span class="material-symbols-rounded" style="font-size:20px;">
                                        <?= (stripos($b['device_name'], 'phone') !== false || stripos($b['device_name'], 'android') !== false || stripos($b['device_name'], 'iphone') !== false) ? 'smartphone' : 'laptop_mac' ?>
                                    </span>
                                </div>
                                <div>
                                    <div style="font-weight:700; font-size:13.5px; color:#0f172a;"><?= htmlspecialchars($b['device_name']) ?></div>
                                    <div style="font-size:11.5px; color:#64748b; margin-top:2px;">
                                        Added: <?= date('M d, Y', strtotime($b['created_at'])) ?> &bull; 
                                        Last Used: <span style="color:#059669; font-weight:600;"><?= !empty($b['last_used_at']) ? date('M d, h:i A', strtotime($b['last_used_at'])) : 'Never' ?></span>
                                    </div>
                                </div>
                            </div>
                            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/auth/biometricDelete/<?= (int)$b['id'] ?>" 
                               class="btn btn-sm btn-ghost" 
                               style="color:#ef4444; height:32px; padding:0 10px; font-size:12px; font-weight:600; border-radius:8px; border:1px solid #fee2e2; background:#fef2f2; text-decoration:none;" 
                               onclick="return confirm('Remove this biometric key?');">
                                <span class="material-symbols-rounded" style="font-size:15px;">delete</span> Remove
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
function detectCurrentDevice() {
    const ua = navigator.userAgent;
    const isWindows = /Windows/i.test(ua);
    const isMac = /Macintosh|Mac OS/i.test(ua) && !('ontouchend' in document);
    const isIpad = /iPad/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isIphone = /iPhone/i.test(ua);
    const isAndroid = /Android/i.test(ua);
    const isLinux = /Linux/i.test(ua) && !isAndroid;

    let deviceTitle = 'Desktop / Laptop Device';
    let icon = 'devices';
    let hardwareLabel = 'Biometric Sensor / Windows Hello';

    if (isWindows) {
        deviceTitle = 'Windows Laptop / PC (Windows Hello)';
        icon = 'laptop_windows';
        hardwareLabel = 'Windows Hello / Fingerprint Sensor';
    } else if (isMac) {
        deviceTitle = 'Apple MacBook (Touch ID)';
        icon = 'laptop_mac';
        hardwareLabel = 'Touch ID / Apple Biometrics';
    } else if (isIphone) {
        deviceTitle = 'Apple iPhone (Touch ID / Face ID)';
        icon = 'smartphone';
        hardwareLabel = 'Face ID / Touch ID';
    } else if (isIpad) {
        deviceTitle = 'Apple iPad (Touch ID / Face ID)';
        icon = 'tablet_mac';
        hardwareLabel = 'Touch ID / Face ID';
    } else if (isAndroid) {
        deviceTitle = 'Android Smartphone (Fingerprint)';
        icon = 'smartphone';
        hardwareLabel = 'Mobile Fingerprint Sensor';
    } else if (isLinux) {
        deviceTitle = 'Linux Laptop / PC';
        icon = 'laptop';
        hardwareLabel = 'Biometric / Passkey Sensor';
    }

    return { deviceTitle, icon, hardwareLabel };
}

(function initBiometricDetector() {
    const badge = document.getElementById('device_compat_badge');
    const note = document.getElementById('device_detected_note');
    const nameInput = document.getElementById('bio_device_name');
    const iconEl = document.getElementById('bio_device_icon');
    const regBtn = document.getElementById('bioRegBtn');

    const info = detectCurrentDevice();

    if (nameInput) {
        nameInput.value = info.deviceTitle;
    }
    if (iconEl) {
        iconEl.textContent = info.icon;
    }

    const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const isSecureOrigin = window.isSecureContext || isLocal;

    if (!isSecureOrigin) {
        if (badge) {
            badge.style.background = '#fffbeb';
            badge.style.color = '#d97706';
            badge.style.borderColor = '#fde68a';
            badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px;">lock_open</span> Localhost or HTTPS Required';
        }
        if (note) {
            note.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px; vertical-align:middle; color:#d97706; margin-right:4px;">warning</span> Device: <strong>' + info.deviceTitle + '</strong>. Note: Biometric hardware integration requires localhost or HTTPS connection.';
        }
        return;
    }

    if (!window.PublicKeyCredential) {
        if (badge) {
            badge.style.background = '#fef2f2';
            badge.style.color = '#dc2626';
            badge.style.borderColor = '#fecaca';
            badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px;">error</span> Browser Not Supported';
        }
        if (note) {
            note.innerHTML = 'Device: <strong>' + info.deviceTitle + '</strong>. Please use Chrome, Edge, Safari, or Brave.';
        }
        return;
    }

    if (typeof PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable === 'function') {
        PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then((isAvailable) => {
            if (isAvailable) {
                if (badge) {
                    badge.style.background = '#ecfdf5';
                    badge.style.color = '#059669';
                    badge.style.borderColor = '#a7f3d0';
                    badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px;">verified</span> ' + info.hardwareLabel + ' Ready';
                }
                if (note) {
                    note.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px; vertical-align:middle; color:#059669; margin-right:4px;">check_circle</span> <strong>' + info.deviceTitle + '</strong> detected. ' + info.hardwareLabel + ' is ready to enroll.';
                }
            } else {
                if (badge) {
                    badge.style.background = '#eff6ff';
                    badge.style.color = '#2563eb';
                    badge.style.borderColor = '#bfdbfe';
                    badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px;">key</span> Passkey / Windows Hello Ready';
                }
                if (note) {
                    note.innerHTML = 'Device: <strong>' + info.deviceTitle + '</strong>. Ready to register using device Passkey or Windows Hello PIN.';
                }
            }
        }).catch(() => {
            if (badge) {
                badge.style.background = '#ecfdf5';
                badge.style.color = '#059669';
                badge.style.borderColor = '#a7f3d0';
                badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px;">verified</span> Passkey Ready';
            }
            if (note) {
                note.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px; vertical-align:middle; color:#059669; margin-right:4px;">check_circle</span> <strong>' + info.deviceTitle + '</strong> detected. Ready to register.';
            }
        });
    } else {
        if (badge) {
            badge.style.background = '#ecfdf5';
            badge.style.color = '#059669';
            badge.style.borderColor = '#a7f3d0';
            badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px;">verified</span> Device Ready';
        }
        if (note) {
            note.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px; vertical-align:middle; color:#059669; margin-right:4px;">check_circle</span> <strong>' + info.deviceTitle + '</strong> detected.';
        }
    }
})();

async function registerBiometricDevice() {
    const msg = document.getElementById('bio_status_msg');
    const btn = document.getElementById('bioRegBtn');
    const devName = document.getElementById('bio_device_name').value.trim() || 'My Device';

    msg.style.display = 'block';
    msg.style.background = '#eff6ff';
    msg.style.color = '#1d4ed8';
    msg.style.border = '1px solid #bfdbfe';
    msg.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px; vertical-align:middle;">touch_app</span> Touch your laptop fingerprint sensor, scan Face ID, or verify with Windows Hello now...';

    const isSecure = window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    if (!window.PublicKeyCredential || !isSecure) {
        msg.style.background = '#fef2f2';
        msg.style.color = '#dc2626';
        msg.style.border = '1px solid #fecaca';
        if (!isSecure) {
            msg.innerHTML = '<span class="material-symbols-rounded" style="font-size:18px; vertical-align:middle; margin-right:4px; color:#dc2626;">warning</span> <strong>Security Requirement:</strong> WebAuthn Fingerprint requires <strong>HTTPS</strong> or <strong>localhost</strong>. Please open the app via <code>http://localhost</code> or enable SSL/HTTPS in Laragon.';
        } else {
            msg.textContent = 'WebAuthn is not supported in this browser. Please use Chrome, Edge, Safari, or Brave.';
        }
        return;
    }

    try {
        const challenge = new Uint8Array(32);
        window.crypto.getRandomValues(challenge);
        const userId = new Uint8Array(16);
        window.crypto.getRandomValues(userId);

        btn.disabled = true;

        const credential = await navigator.credentials.create({
            publicKey: {
                challenge: challenge,
                rp: {
                    id: (window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost') ? 'localhost' : window.location.hostname,
                    name: "DevAltum POS SaaS Pro"
                },
                user: {
                    id: userId,
                    name: "user_<?= (int)Auth::id() ?>",
                    displayName: "<?= htmlspecialchars(Auth::user()['name'] ?? 'User') ?>"
                },
                pubKeyCredParams: [
                    { alg: -7, type: "public-key" },
                    { alg: -257, type: "public-key" }
                ],
                authenticatorSelection: {
                    authenticatorAttachment: "platform",
                    userVerification: "preferred",
                    residentKey: "preferred"
                },
                timeout: 60000
            }
        });

        if (!credential) {
            btn.disabled = false;
            msg.style.background = '#fef2f2';
            msg.style.color = '#dc2626';
            msg.style.border = '1px solid #fecaca';
            msg.textContent = 'Registration cancelled by user.';
            return;
        }

        const credId = btoa(String.fromCharCode(...new Uint8Array(credential.rawId)));
        try {
            localStorage.setItem('pos_biometric_cred_id', credId);
        } catch(e) {}
        
        const resp = await fetch('<?= defined('BASE_URL') ? BASE_URL : '' ?>/auth/biometricRegister', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                credential_id: credId,
                device_name: devName,
                public_key: credId
            })
        });

        const data = await resp.json();
        if (data.success) {
            msg.style.background = '#ecfdf5';
            msg.style.color = '#059669';
            msg.style.border = '1px solid #a7f3d0';
            msg.innerHTML = '<span class="material-symbols-rounded" style="font-size:18px; vertical-align:middle; margin-right:6px; color:#059669;">check_circle</span> <strong>Fingerprint / Passkey successfully registered!</strong> Refreshing...';
            setTimeout(() => { window.location.href = '<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/index?tab=security'; }, 1000);
        } else {
            btn.disabled = false;
            msg.style.background = '#fef2f2';
            msg.style.color = '#dc2626';
            msg.style.border = '1px solid #fecaca';
            msg.textContent = data.error || 'Failed to save biometric credential.';
        }

    } catch (err) {
        console.error(err);
        btn.disabled = false;
        msg.style.background = '#fef2f2';
        msg.style.color = '#dc2626';
        msg.style.border = '1px solid #fecaca';
        msg.textContent = 'Sensor verification cancelled or error: ' + (err.message || 'Cancelled');
    }
}

async function toggleBiometricSetting(isEnabled) {
    const card = document.getElementById('register_device_card_container');
    const msg = document.getElementById('bio_status_msg');
    
    if (card) {
        card.style.opacity = isEnabled ? '1' : '0.55';
        card.style.pointerEvents = isEnabled ? 'auto' : 'none';
    }

    try {
        const formData = new FormData();
        formData.append('enabled', isEnabled ? '1' : '0');
        
        const resp = await fetch('<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/toggleBiometric', {
            method: 'POST',
            body: formData
        });
        const res = await resp.json();
        if (res.success && msg) {
            msg.style.display = 'block';
            msg.style.background = isEnabled ? '#ecfdf5' : '#f8fafc';
            msg.style.color = isEnabled ? '#059669' : '#64748b';
            msg.style.border = isEnabled ? '1px solid #a7f3d0' : '1px solid #e2e8f0';
            msg.innerHTML = isEnabled 
                ? '<span class="material-symbols-rounded" style="font-size:18px; vertical-align:middle; margin-right:6px; color:#059669;">check_circle</span> <strong>Biometric Sign-In Enabled!</strong> Users can register their devices and sign in with fingerprint.' 
                : '<span class="material-symbols-rounded" style="font-size:18px; vertical-align:middle; margin-right:6px; color:#64748b;">lock</span> <strong>Biometric Sign-In Disabled.</strong> Fingerprint button is now hidden on the login screen.';
            setTimeout(() => { msg.style.display = 'none'; }, 4000);
        }
    } catch(e) {
        console.error(e);
    }
}
</script>

<section class="settings-panel" data-settings-panel="migration">
    <div class="settings-surface" style="margin-bottom: 24px;">
        <div style="display:flex; justify-content:space-between; gap:16px; align-items:flex-start; flex-wrap:wrap; margin-bottom:18px;">
            <div>
                <h3 style="margin-bottom:8px;">Database Schema Auto-Sync & Repair</h3>
                <p class="page-subtitle" style="margin:0;">Automatically creates new tables, repairs missing columns, and ensures indexes whenever new code is replaced or updated.</p>
            </div>
            <span class="scanner-token-pill" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;"><span class="material-symbols-rounded">verified</span>Auto-Sync Active</span>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-bottom:18px;">
            <div style="padding:16px; border:1px solid var(--border); border-radius:16px; background:#f8fafc;">
                <small style="color:var(--text-muted); display:block; margin-bottom:4px;">Schema Version</small>
                <strong style="font-size:18px; color:var(--text-main);"><?php echo htmlspecialchars($settings['schema_version'] ?? '2.5.0 Enterprise'); ?></strong>
            </div>
            <div style="padding:16px; border:1px solid var(--border); border-radius:16px; background:#f8fafc;">
                <small style="color:var(--text-muted); display:block; margin-bottom:4px;">Last Synchronized</small>
                <strong style="font-size:15px; color:var(--text-main);"><?php echo htmlspecialchars($settings['last_schema_sync_at'] ?? date('Y-m-d H:i:s')); ?></strong>
            </div>
            <div style="padding:16px; border:1px solid var(--border); border-radius:16px; background:#f8fafc;">
                <small style="color:var(--text-muted); display:block; margin-bottom:4px;">Auto-Sync Status</small>
                <strong style="font-size:15px; color:#10b981;">Protected & Up-to-Date</strong>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; padding:16px; background:#f1f5f9; border-radius:14px;">
            <div style="font-size:13px; color:#475569;">
                Need to manually force sync tables or added new columns manually in code?
            </div>
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/syncDatabaseSchema" method="POST" data-no-ajax style="margin:0;">
                <?php echo Security::csrfField(); ?>
                <button type="submit" class="btn btn-primary" style="padding:10px 18px; font-size:13px; font-weight:700;">
                    <span class="material-symbols-rounded" style="font-size:18px; vertical-align:middle; margin-right:4px;">sync</span>Force Sync Database Schema
                </button>
            </form>
        </div>
    </div>

    <div class="settings-surface">
        <div style="display:flex; justify-content:space-between; gap:16px; align-items:flex-start; flex-wrap:wrap; margin-bottom:18px;">
            <div>
                <h3 style="margin-bottom:8px;">Database Migration (Single-Tenant to SaaS)</h3>
                <p class="page-subtitle" style="margin:0;">Safely import all of your sales, products, expenses and customers from your single-tenant SQL database backup.</p>
            </div>
            <span class="scanner-token-pill" style="background:#fef3c7; color:#d97706; border:1px solid #fde68a;"><span class="material-symbols-rounded">warning</span>Action Required</span>
        </div>
        
        <div class="support-note" style="margin-bottom: 20px; line-height: 1.5; background:#fff7ed; border-color:#ffedd5; color:#c2410c;">
            <strong>Critical Warning:</strong> This operation is irreversible. It will wipe all of the currently logged-in store owner's existing SaaS records (if any) and overwrite them with clean data imported from the SQL file. We highly recommend taking a full database backup before running this tool.
        </div>

        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/migrateDb" method="POST" enctype="multipart/form-data" id="db-migration-form" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <label class="csv-dropzone" id="db-migration-dropzone" style="display:flex; flex-direction:column; align-items:center; justify-content:center; border:2px dashed #cbd5e1; border-radius:24px; padding:45px 30px; cursor:pointer; transition:all 0.2s ease-in-out; text-align:center; background:#fafafa; min-height: 250px;">
                <input type="file" name="file" accept=".sql" required style="display:none;" id="db-migration-file-input">
                <span class="material-symbols-rounded" style="font-size:52px; color:var(--primary); margin-bottom:12px;">database</span>
                <strong style="display:block; color:#334155; margin-bottom:6px; font-size:16px;" id="migration-text-title">Drag & Drop Database SQL Dump here</strong>
                <span class="page-subtitle" id="migration-text-subtitle">or click to browse files (accepts .sql files)</span>
            </label>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button class="btn btn-primary" type="submit" id="submit-migration-btn" disabled>
                    <span class="material-symbols-rounded">cloud_upload</span>Start Database Migration
                </button>
            </div>
        </form>
    </div>
</section>

<?php
$receiptVis = !empty($receiptVisibility) ? $receiptVisibility : App::receiptVisibility();
$receiptLbl = !empty($receiptLabels) ? $receiptLabels : App::receiptLabels();
$receiptSecOrder = !empty($receiptSectionOrder) ? $receiptSectionOrder : App::receiptSectionOrder();

$designerSecMeta = [
    'header' => ['name' => 'Store Header & Branding', 'desc' => 'Logo, store name, address, phone & NTN', 'icon' => 'storefront'],
    'ribbon' => ['name' => 'Time, Date & Title Ribbon', 'desc' => 'Time, INVOICE badge & date ribbon line', 'icon' => 'schedule'],
    'meta' => ['name' => 'Transaction & Customer Meta', 'desc' => 'Invoice #, Customer name & Cashier/Terminal', 'icon' => 'badge'],
    'items' => ['name' => 'Itemized Sales Table', 'desc' => 'Grid table with items, prices, qty & amount', 'icon' => 'table_chart'],
    'summary' => ['name' => 'Item Counters Summary', 'desc' => 'Total Items count & Total Quantity sum', 'icon' => 'calculate'],
    'totals' => ['name' => 'Subtotal, Tax & Paid Rows', 'desc' => 'Subtotal, discounts, tax, paid & change', 'icon' => 'receipt'],
    'net_payable' => ['name' => 'Net Payable Highlight Box', 'desc' => 'High-contrast boxed net payable amount', 'icon' => 'payments'],
    'words' => ['name' => 'Total Amount in Words', 'desc' => 'Rupees in English words (e.g. Seven Thousand...)', 'icon' => 'spellcheck'],
    'barcode' => ['name' => 'Code128 Barcode Strip', 'desc' => 'Scannable barcode for quick receipt lookup', 'icon' => 'barcode'],
    'footer' => ['name' => 'Greetings & Policies', 'desc' => 'Thank you note, exchange policy & developer credit', 'icon' => 'favorite'],
];
?>

<section class="settings-panel" data-settings-panel="receipt_designer">
    <div class="settings-surface">
        <form id="receipt-designer-form" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/saveSettings" method="POST" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <input type="hidden" name="active_tab" value="receipt_designer">

            <!-- Designer Top Bar -->
            <div class="designer-header-row" style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; margin-bottom:22px; padding-bottom:18px; border-bottom:1px solid #f1f5f9;">
                <div class="designer-header-left" style="display:flex; align-items:center; gap:14px;">
                    <div style="width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg, #0f6dff, #2563eb); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 6px 16px rgba(15,109,255,0.25);">
                        <span class="material-symbols-rounded" style="font-size:26px;">receipt_long</span>
                    </div>
                    <div>
                        <h3 style="margin:0; font-size:18px; font-weight:800; color:#0f172a;">Thermal Receipt Designer & Customizer</h3>
                        <p class="page-subtitle" style="margin:4px 0 0 0; font-size:13px; color:#64748b;">
                            Toggle fields on/off, customize headings & text in real time, and drag & drop sections to print exactly the way you want.
                        </p>
                    </div>
                </div>
                <div class="designer-header-actions">
                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/templates/index" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; font-weight:700;">
                        <span class="material-symbols-rounded" style="color:#0f6dff;">design_services</span>
                        <span>Templates Studio</span>
                    </a>
                    <button type="button" class="btn btn-secondary" id="reset-designer-btn">
                        <span class="material-symbols-rounded">restart_alt</span>
                        <span class="designer-btn-full">Reset Defaults</span>
                        <span class="designer-btn-short">Reset</span>
                    </button>
                    <button type="button" class="btn btn-primary" id="save-designer-btn" style="background:linear-gradient(135deg, #0f6dff, #0052cc); box-shadow:0 4px 14px rgba(15,109,255,0.3);">
                        <span class="material-symbols-rounded">save</span>
                        <span class="designer-btn-full">Save Layout & Design</span>
                        <span class="designer-btn-short">Save Layout</span>
                    </button>
                </div>
            </div>

            <!-- Studio Grid: Left Controls + Right Live Preview -->
            <div class="designer-studio-grid">
                <!-- Left Controls Column -->
                <div class="designer-controls-column">
                    <!-- 3 Subtabs Navigation -->
                    <div class="designer-subtabs">
                        <button type="button" class="designer-subtab-btn active" data-designer-subtab="toggles">
                            <span class="material-symbols-rounded">toggle_on</span>
                            <span class="subtab-label-full">Field Toggles</span>
                            <span class="subtab-label-short">Toggles</span>
                        </button>
                        <button type="button" class="designer-subtab-btn" data-designer-subtab="content">
                            <span class="material-symbols-rounded">edit_note</span>
                            <span class="subtab-label-full">Headings & Content</span>
                            <span class="subtab-label-short">Headings</span>
                        </button>
                        <button type="button" class="designer-subtab-btn" data-designer-subtab="ordering">
                            <span class="material-symbols-rounded">drag_indicator</span>
                            <span class="subtab-label-full">Section Reorder</span>
                            <span class="subtab-label-short">Reorder</span>
                        </button>
                    </div>

                    <!-- SUB-PANEL 1: FIELD TOGGLES -->
                    <div class="designer-subpanel active" data-designer-subpanel="toggles">
                        <!-- Group 1: Header & Branding -->
                        <div class="designer-group-card">
                            <div class="designer-group-title">
                                <span class="material-symbols-rounded">storefront</span>
                                <span>Store Header & Branding</span>
                            </div>
                            <div class="designer-toggles-list">
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Store Logo</span>
                                        <span class="designer-toggle-sub">Display brand logo on top</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Logo">
                                        <input type="checkbox" name="receipt_visibility[show_logo]" value="1" <?= !empty($receiptVis['show_logo']) ? 'checked' : '' ?> data-designer-toggle="logo">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Store Name</span>
                                        <span class="designer-toggle-sub">Display business name</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Store Name">
                                        <input type="checkbox" name="receipt_visibility[show_store_name]" value="1" <?= !empty($receiptVis['show_store_name']) ? 'checked' : '' ?> data-designer-toggle="store_name">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Tagline / Slogan</span>
                                        <span class="designer-toggle-sub">Show store motto line</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Tagline">
                                        <input type="checkbox" name="receipt_visibility[show_tagline]" value="1" <?= !empty($receiptVis['show_tagline']) ? 'checked' : '' ?> data-designer-toggle="tagline">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Physical Address</span>
                                        <span class="designer-toggle-sub">Shop street / city location</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Address">
                                        <input type="checkbox" name="receipt_visibility[show_address]" value="1" <?= !empty($receiptVis['show_address']) ? 'checked' : '' ?> data-designer-toggle="address">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Contact / Phone</span>
                                        <span class="designer-toggle-sub">Mobile / WhatsApp number</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Phone">
                                        <input type="checkbox" name="receipt_visibility[show_phone]" value="1" <?= !empty($receiptVis['show_phone']) ? 'checked' : '' ?> data-designer-toggle="phone">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">NTN / Tax ID</span>
                                        <span class="designer-toggle-sub">National Tax Number</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle NTN">
                                        <input type="checkbox" name="receipt_visibility[show_ntn]" value="1" <?= !empty($receiptVis['show_ntn']) ? 'checked' : '' ?> data-designer-toggle="ntn">
                                        <span></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Group 2: Ribbon & Transaction Meta -->
                        <div class="designer-group-card">
                            <div class="designer-group-title">
                                <span class="material-symbols-rounded">badge</span>
                                <span>Ribbon & Transaction Info</span>
                            </div>
                            <div class="designer-toggles-list">
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Time & Date Ribbon</span>
                                        <span class="designer-toggle-sub">Upper divider with time/date</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Ribbon">
                                        <input type="checkbox" name="receipt_visibility[show_ribbon]" value="1" <?= !empty($receiptVis['show_ribbon']) ? 'checked' : '' ?> data-designer-toggle="ribbon">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Invoice / Bill Number</span>
                                        <span class="designer-toggle-sub">Bordered invoice identifier</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Invoice No">
                                        <input type="checkbox" name="receipt_visibility[show_invoice_no]" value="1" <?= !empty($receiptVis['show_invoice_no']) ? 'checked' : '' ?> data-designer-toggle="invoice_no">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Customer Name</span>
                                        <span class="designer-toggle-sub">Name of buyer / Cash Sale</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Customer">
                                        <input type="checkbox" name="receipt_visibility[show_customer]" value="1" <?= !empty($receiptVis['show_customer']) ? 'checked' : '' ?> data-designer-toggle="customer">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Cashier / Operator</span>
                                        <span class="designer-toggle-sub">Staff member who billed</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Cashier">
                                        <input type="checkbox" name="receipt_visibility[show_cashier]" value="1" <?= !empty($receiptVis['show_cashier']) ? 'checked' : '' ?> data-designer-toggle="cashier">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Counter / Terminal ID</span>
                                        <span class="designer-toggle-sub">POS workstation designation</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Counter">
                                        <input type="checkbox" name="receipt_visibility[show_counter]" value="1" <?= !empty($receiptVis['show_counter']) ? 'checked' : '' ?> data-designer-toggle="counter">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Unit Sub-label</span>
                                        <span class="designer-toggle-sub">e.g. Sales [KG/PCS/PACK]</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Unit Label">
                                        <input type="checkbox" name="receipt_visibility[show_unit_label]" value="1" <?= !empty($receiptVis['show_unit_label']) ? 'checked' : '' ?> data-designer-toggle="unit_label">
                                        <span></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Group 3: Financials & Totals -->
                        <div class="designer-group-card">
                            <div class="designer-group-title">
                                <span class="material-symbols-rounded">payments</span>
                                <span>Totals, Financials & Counters</span>
                            </div>
                            <div class="designer-toggles-list">
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Item Counters</span>
                                        <span class="designer-toggle-sub">Total Items & Total Qty</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Counters">
                                        <input type="checkbox" name="receipt_visibility[show_item_counters]" value="1" <?= !empty($receiptVis['show_item_counters']) ? 'checked' : '' ?> data-designer-toggle="item_counters">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Subtotal Row</span>
                                        <span class="designer-toggle-sub">Total before discounts/tax</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Subtotal">
                                        <input type="checkbox" name="receipt_visibility[show_subtotal]" value="1" <?= !empty($receiptVis['show_subtotal']) ? 'checked' : '' ?> data-designer-toggle="subtotal">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Discount Row</span>
                                        <span class="designer-toggle-sub">Amount discounted (if any)</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Discount">
                                        <input type="checkbox" name="receipt_visibility[show_discount]" value="1" <?= !empty($receiptVis['show_discount']) ? 'checked' : '' ?> data-designer-toggle="discount">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Tax / GST Row</span>
                                        <span class="designer-toggle-sub">Sales tax breakdown</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Tax">
                                        <input type="checkbox" name="receipt_visibility[show_tax]" value="1" <?= !empty($receiptVis['show_tax']) ? 'checked' : '' ?> data-designer-toggle="tax">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Paid Amount</span>
                                        <span class="designer-toggle-sub">Tendered cash / card amount</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Paid">
                                        <input type="checkbox" name="receipt_visibility[show_paid]" value="1" <?= !empty($receiptVis['show_paid']) ? 'checked' : '' ?> data-designer-toggle="paid">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Change Return / Due</span>
                                        <span class="designer-toggle-sub">Remaining balance or change</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Due">
                                        <input type="checkbox" name="receipt_visibility[show_due]" value="1" <?= !empty($receiptVis['show_due']) ? 'checked' : '' ?> data-designer-toggle="due">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Net Payable Box</span>
                                        <span class="designer-toggle-sub">High-contrast grand total box</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Net Payable">
                                        <input type="checkbox" name="receipt_visibility[show_net_payable]" value="1" <?= !empty($receiptVis['show_net_payable']) ? 'checked' : '' ?> data-designer-toggle="net_payable">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Rupees in Words</span>
                                        <span class="designer-toggle-sub">Written word representation</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Words">
                                        <input type="checkbox" name="receipt_visibility[show_words]" value="1" <?= !empty($receiptVis['show_words']) ? 'checked' : '' ?> data-designer-toggle="words">
                                        <span></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Group 4: Barcode & Footer -->
                        <div class="designer-group-card">
                            <div class="designer-group-title">
                                <span class="material-symbols-rounded">barcode</span>
                                <span>Barcode & Footer Elements</span>
                            </div>
                            <div class="designer-toggles-list">
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Scannable Barcode</span>
                                        <span class="designer-toggle-sub">Code128 barcode of bill number</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Barcode">
                                        <input type="checkbox" name="receipt_visibility[show_barcode]" value="1" <?= !empty($receiptVis['show_barcode']) ? 'checked' : '' ?> data-designer-toggle="barcode">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row">
                                    <div>
                                        <span class="designer-toggle-label">Greetings & Return Policy</span>
                                        <span class="designer-toggle-sub">Thank you note & return terms</span>
                                    </div>
                                    <label class="premium-toggle" title="Toggle Greetings">
                                        <input type="checkbox" name="receipt_visibility[show_greetings]" value="1" <?= !empty($receiptVis['show_greetings']) ? 'checked' : '' ?> data-designer-toggle="greetings">
                                        <span></span>
                                    </label>
                                </div>
                                <div class="designer-toggle-row" style="background:rgba(15,109,255,.05); border:1px solid rgba(15,109,255,.18); border-radius:12px; padding:10px 12px;">
                                    <div>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <span class="designer-toggle-label" style="font-weight:800; color:#0f172a;">Software Developer Credit</span>
                                            <span style="font-size:10px; font-weight:900; background:#dbeafe; color:#1d4ed8; padding:2px 8px; border-radius:999px; text-transform:uppercase; letter-spacing:0.5px; display:inline-flex; align-items:center; gap:4px;">
                                                <span class="material-symbols-rounded" style="font-size:12px;">lock</span> Permanent
                                            </span>
                                        </div>
                                        <span class="designer-toggle-sub">DevAltum signature is permanently enabled on all receipts & templates.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Group 5: DevAltum Signature Sizing Controls -->
                        <div class="designer-group-card" style="margin-top:16px; border:1px solid #bfdbfe; border-radius:16px; background:linear-gradient(145deg, #ffffff 0%, #f0f7ff 100%); padding:16px 18px; box-shadow:0 3px 12px rgba(15,109,255,0.05);">
                            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:12px; padding-bottom:10px; border-bottom:1px solid rgba(191,219,254,0.6);">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span class="material-symbols-rounded" style="color:#0f6dff; font-size:20px;">verified</span>
                                    <strong style="font-size:13.5px; font-weight:800; color:#0f172a;">DevAltum Developer Signature Sizing</strong>
                                </div>
                                <span style="font-size:11px; font-weight:800; background:#dbeafe; color:#1d4ed8; padding:3px 9px; border-radius:999px; display:inline-flex; align-items:center; gap:4px;">
                                    <span class="material-symbols-rounded" style="font-size:13px;">tune</span> 58mm / 80mm Fit
                                </span>
                            </div>

                            <?php 
                            $currentLogoSize = (int) App::setting('devaltum_credit_logo_size', 48);
                            $currentFontSize = (int) App::setting('devaltum_credit_font_size', 12);
                            ?>

                            <div class="devaltum-controls-grid" style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; align-items:stretch;">
                                <!-- Logo Size Control -->
                                <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:10px 12px; display:flex; flex-direction:column; justify-content:space-between; gap:6px;">
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:6px;">
                                        <label for="devaltum-logo-size-select" style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; font-weight:700; color:#334155; margin:0; cursor:pointer;">
                                            <span class="material-symbols-rounded" style="font-size:16px; color:#0f6dff;">image</span>
                                            Logo Height
                                        </label>
                                        <span id="current-logo-badge" style="font-size:11px; font-weight:800; color:#0f172a; background:#f1f5f9; border:1px solid #e2e8f0; padding:1px 6px; border-radius:6px;"><?= $currentLogoSize ?>px</span>
                                    </div>
                                    <select class="form-select" name="devaltum_credit_logo_size" id="devaltum-logo-size-select" style="min-height:38px; height:38px; padding:6px 10px; font-size:12.5px; font-weight:700; border-radius:8px; border:1.5px solid #cbd5e1; background-color:#fff; color:#0f172a; cursor:pointer;">
                                        <option value="32" <?= $currentLogoSize === 32 ? 'selected' : '' ?>>32px - Compact</option>
                                        <option value="40" <?= $currentLogoSize === 40 ? 'selected' : '' ?>>40px - Small</option>
                                        <option value="48" <?= ($currentLogoSize === 48 || empty($currentLogoSize)) ? 'selected' : '' ?>>48px - Standard</option>
                                        <option value="56" <?= $currentLogoSize === 56 ? 'selected' : '' ?>>56px - Medium</option>
                                        <option value="64" <?= $currentLogoSize === 64 ? 'selected' : '' ?>>64px - Large</option>
                                    </select>
                                </div>

                                <!-- Text Size Control -->
                                <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:10px 12px; display:flex; flex-direction:column; justify-content:space-between; gap:6px;">
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:6px;">
                                        <label for="devaltum-font-size-select" style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; font-weight:700; color:#334155; margin:0; cursor:pointer;">
                                            <span class="material-symbols-rounded" style="font-size:16px; color:#0f6dff;">format_size</span>
                                            Text Size
                                        </label>
                                        <span id="current-font-badge" style="font-size:11px; font-weight:800; color:#0f172a; background:#f1f5f9; border:1px solid #e2e8f0; padding:1px 6px; border-radius:6px;"><?= $currentFontSize ?>px</span>
                                    </div>
                                    <select class="form-select" name="devaltum_credit_font_size" id="devaltum-font-size-select" style="min-height:38px; height:38px; padding:6px 10px; font-size:12.5px; font-weight:700; border-radius:8px; border:1.5px solid #cbd5e1; background-color:#fff; color:#0f172a; cursor:pointer;">
                                        <option value="10" <?= $currentFontSize === 10 ? 'selected' : '' ?>>10px - Small</option>
                                        <option value="11" <?= $currentFontSize === 11 ? 'selected' : '' ?>>11px - Compact</option>
                                        <option value="12" <?= ($currentFontSize === 12 || empty($currentFontSize)) ? 'selected' : '' ?>>12px - Standard</option>
                                        <option value="14" <?= $currentFontSize === 14 ? 'selected' : '' ?>>14px - Medium</option>
                                        <option value="16" <?= $currentFontSize === 16 ? 'selected' : '' ?>>16px - Large</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SUB-PANEL 2: HEADINGS & CONTENT EDITOR -->
                    <div class="designer-subpanel" data-designer-subpanel="content">
                        <div class="designer-group-card">
                            <div class="designer-group-title">
                                <span class="material-symbols-rounded">title</span>
                                <span>Custom Labels & Headings (Live Edit)</span>
                            </div>
                            <p class="page-subtitle" style="margin:-4px 0 16px 0; font-size:12px; color:#64748b;">
                                Change any label or phrase below and watch the live thermal slip preview update instantly as you type.
                            </p>

                            <div class="settings-form-grid">
                                <div class="form-group">
                                    <label class="form-label">Receipt Ribbon Heading</label>
                                    <input class="form-input" type="text" name="receipt_labels[title]" value="<?= Security::escape($receiptLbl['title'] ?: 'INVOICE') ?>" data-preview-bind="prev-ribbon-title" placeholder="e.g. INVOICE, CASH MEMO, BILL">
                                    <div class="page-subtitle" style="margin-top:4px;">Centered heading inside ribbon bar</div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Store Name Override (Optional)</label>
                                    <input class="form-input" type="text" name="receipt_labels[store_name_override]" value="<?= Security::escape($receiptLbl['store_name_override'] ?? '') ?>" data-preview-bind="prev-store-name" placeholder="<?= Security::escape($settings['company_name']) ?>">
                                    <div class="page-subtitle" style="margin-top:4px;">Leave blank to use standard business name</div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Invoice Prefix Label</label>
                                    <input class="form-input" type="text" name="receipt_labels[invoice_prefix]" value="<?= Security::escape($receiptLbl['invoice_prefix'] ?: 'Inv # :') ?>" data-preview-bind="prev-inv-prefix" placeholder="e.g. Inv # :, Bill No:">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Customer Label</label>
                                    <input class="form-input" type="text" name="receipt_labels[customer_label]" value="<?= Security::escape($receiptLbl['customer_label'] ?: 'Name :') ?>" data-preview-bind="prev-cust-label" placeholder="e.g. Name :, Customer :, M/S :">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Cashier Label Prefix</label>
                                    <input class="form-input" type="text" name="receipt_labels[cashier_label]" value="<?= Security::escape($receiptLbl['cashier_label'] ?: 'Cashier:') ?>" data-preview-bind="prev-cashier-label" placeholder="e.g. Cashier:, Staff:, Operator:">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Terminal / Counter Label</label>
                                    <input class="form-input" type="text" name="receipt_labels[counter_label]" value="<?= Security::escape($receiptLbl['counter_label'] ?: 'Counter 1') ?>" data-preview-bind="prev-counter-label" placeholder="e.g. Counter 1, Terminal-01, POS-A">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Net Payable Box Label</label>
                                    <input class="form-input" type="text" name="receipt_labels[net_payable_label]" value="<?= Security::escape($receiptLbl['net_payable_label'] ?: 'Net Payable:') ?>" data-preview-bind="prev-net-payable-label" placeholder="e.g. Net Payable:, Total Amount:, Grand Total:">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Amount in Words Prefix</label>
                                    <input class="form-input" type="text" name="receipt_labels[words_prefix]" value="<?= Security::escape($receiptLbl['words_prefix'] ?: 'Rupees') ?>" data-preview-bind="prev-words-prefix" placeholder="e.g. Rupees, PKR, Total:">
                                </div>
                                <div class="form-group full">
                                    <label class="form-label">Greetings / Thank You Note</label>
                                    <input class="form-input" type="text" name="receipt_labels[greetings]" value="<?= Security::escape($receiptLbl['greetings'] ?: 'Thanks For Shopping With Us!') ?>" data-preview-bind="prev-greetings" placeholder="e.g. Thanks For Shopping With Us!">
                                </div>
                                <div class="form-group full">
                                    <label class="form-label">Exchange & Return Policy Message</label>
                                    <textarea class="form-textarea" rows="2" name="receipt_labels[return_policy]" data-preview-bind="prev-return-policy" placeholder="e.g. Items can be exchanged within 7 days with original invoice. No cash refund."><?= Security::escape($receiptLbl['return_policy'] ?? 'Exchange possible within 7 days with original receipt.') ?></textarea>
                                </div>
                                <div class="form-group full">
                                    <label class="form-label">Receipt Font Size Preset</label>
                                    <select class="form-select" name="receipt_labels[font_size_preset]" id="font-size-preset-select">
                                        <option value="compact" <?= ($receiptLbl['font_size_preset'] ?? '') === 'compact' ? 'selected' : '' ?>>Compact & Ultra Fine (9.5px - Max items per roll)</option>
                                        <option value="normal" <?= ($receiptLbl['font_size_preset'] ?? 'normal') === 'normal' ? 'selected' : '' ?>>Standard Retail Crisp (10.5px - Recommended)</option>
                                        <option value="large" <?= ($receiptLbl['font_size_preset'] ?? '') === 'large' ? 'selected' : '' ?>>Enhanced Legibility (11.5px - Larger characters)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SUB-PANEL 3: DRAG & DROP SECTION ORDERING -->
                    <div class="designer-subpanel" data-designer-subpanel="ordering">
                        <div class="designer-group-card">
                            <div class="designer-group-title">
                                <span class="material-symbols-rounded">sort</span>
                                <span>Receipt Section Ordering (Drag & Drop)</span>
                            </div>
                            <p class="page-subtitle" style="margin:-4px 0 16px 0; font-size:12px; color:#64748b;">
                                Drag sections up or down with your mouse or finger, or click the <span class="material-symbols-rounded" style="font-size:14px; vertical-align:middle;">arrow_upward</span> / <span class="material-symbols-rounded" style="font-size:14px; vertical-align:middle;">arrow_downward</span> buttons. The print order and preview sync automatically!
                            </p>

                            <div class="designer-order-list" id="designerOrderList">
                                <?php foreach ($receiptSecOrder as $secKey): ?>
                                    <?php 
                                    $meta = $designerSecMeta[$secKey] ?? ['name' => ucfirst($secKey), 'desc' => 'Section block', 'icon' => 'widgets'];
                                    ?>
                                    <div class="designer-order-item" draggable="true" data-sec-key="<?= Security::escape($secKey) ?>">
                                        <input type="hidden" name="receipt_section_order[]" value="<?= Security::escape($secKey) ?>" class="sec-order-input">
                                        <div class="designer-order-left">
                                            <div class="designer-order-handle" title="Drag to reorder">
                                                <span class="material-symbols-rounded">drag_indicator</span>
                                            </div>
                                            <span class="material-symbols-rounded" style="font-size:22px; color:var(--primary);"><?= $meta['icon'] ?></span>
                                            <div class="designer-order-info">
                                                <strong><?= Security::escape($meta['name']) ?></strong>
                                                <small><?= Security::escape($meta['desc']) ?></small>
                                            </div>
                                        </div>
                                        <div class="designer-order-actions">
                                            <button type="button" class="designer-btn-icon btn-move-up" title="Move Up">
                                                <span class="material-symbols-rounded" style="font-size:18px;">arrow_upward</span>
                                            </button>
                                            <button type="button" class="designer-btn-icon btn-move-down" title="Move Down">
                                                <span class="material-symbols-rounded" style="font-size:18px;">arrow_downward</span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Live Preview Column -->
                <div class="designer-preview-wrap">
                    <div class="designer-preview-card">
                        <div class="designer-preview-header">
                            <strong><span class="material-symbols-rounded" style="color:var(--primary); font-size:18px;">receipt_long</span> 80mm Live Thermal Preview</strong>
                            <span class="designer-preview-badge">LIVE SYNC</span>
                        </div>
                        <p style="font-size:11px; color:#64748b; margin:0 0 12px; line-height:1.4;">
                            Real-time simulation of standard 80mm thermal receipt paper.
                        </p>

                        <!-- Virtual Thermal Slip Container -->
                        <div class="virtual-thermal-slip" id="virtualThermalSlip">
                            <?php foreach ($receiptSecOrder as $secKey): ?>
                                <?php if ($secKey === 'header'): ?>
                                    <!-- SECTION: HEADER -->
                                    <div class="preview-sec <?= empty($receiptVis['show_store_name']) && empty($receiptVis['show_logo']) && empty($receiptVis['show_address']) && empty($receiptVis['show_phone']) && empty($receiptVis['show_ntn']) && empty($receiptVis['show_tagline']) ? 'is-hidden' : '' ?>" data-sec="header" style="text-align:center; margin-bottom:6px;">
                                        <div class="preview-part-logo <?= empty($receiptVis['show_logo']) ? 'is-hidden' : '' ?>" style="margin-bottom:4px;">
                                            <?php 
                                                $prevReceiptLogo = (!empty($settings['receipt_logo']) && App::mediaExists($settings['receipt_logo'])) ? App::mediaUrl($settings['receipt_logo']) : '';
                                            ?>
                                            <?php if ($prevReceiptLogo !== ''): ?>
                                                <img src="<?= Security::escape($prevReceiptLogo) ?>" alt="Logo" style="max-width:100px; max-height:40px; object-fit:contain; display:block; margin:0 auto 4px;" onerror="this.onerror=null; this.style.display='none';">
                                            <?php else: ?>
                                                <div style="display:inline-flex; flex-direction:column; align-items:center; justify-content:center; width:76px; height:32px; border:1px solid #d1d5db; background:#f8fafc; color:#94a3b8; font-size:8px; font-weight:600; line-height:1.2; text-align:center; margin:0 auto 4px; border-radius:3px;">NO LOGO<br>AVAILABLE</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="preview-part-store_name <?= empty($receiptVis['show_store_name']) ? 'is-hidden' : '' ?>" id="prev-store-name" style="font-family:'Times New Roman', serif; font-style:italic; font-size:16px; font-weight:700; color:#0f172a; margin:0 0 2px; text-align:center; line-height:1.15;">
                                            <?= Security::escape(!empty($receiptLbl['store_name_override']) ? $receiptLbl['store_name_override'] : $settings['company_name']) ?>
                                        </div>
                                        <div class="preview-part-tagline <?= empty($receiptVis['show_tagline']) ? 'is-hidden' : '' ?>" id="prev-tagline" style="font-size:9px; color:#475569; margin:0 0 2px; text-align:center;">
                                            <?= Security::escape(!empty($settings['company_tagline']) ? $settings['company_tagline'] : 'Quality & Trust Everyday') ?>
                                        </div>
                                        <div class="preview-part-address <?= empty($receiptVis['show_address']) ? 'is-hidden' : '' ?>" id="prev-address" style="font-size:9.5px; color:#334155; margin:0 0 2px; text-align:center; line-height:1.25;">
                                            <?= Security::escape(!empty($settings['company_address']) ? $settings['company_address'] : 'Shop # 12, Main Commercial Market') ?>
                                        </div>
                                        <div class="preview-part-phone <?= empty($receiptVis['show_phone']) ? 'is-hidden' : '' ?>" id="prev-phone" style="font-size:10px; font-weight:600; color:#0f172a; margin:0 0 4px; text-align:center;">
                                            <?= Security::escape(!empty($settings['whatsapp_number']) ? $settings['whatsapp_number'] : '0344 66 99 477') ?>
                                        </div>
                                        <div class="preview-part-ntn <?= empty($receiptVis['show_ntn']) ? 'is-hidden' : '' ?>" id="prev-ntn" style="font-size:9px; color:#64748b; text-align:center;">
                                            NTN: <?= Security::escape(!empty($settingsCompanyNtn) ? $settingsCompanyNtn : '1234567-8') ?>
                                        </div>
                                    </div>

                                <?php elseif ($secKey === 'ribbon'): ?>
                                    <!-- SECTION: RIBBON -->
                                    <div class="preview-sec <?= empty($receiptVis['show_ribbon']) ? 'is-hidden' : '' ?>" data-sec="ribbon" style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #0f172a; border-bottom:1px solid #0f172a; padding:3px 2px; margin:4px 0 6px; font-size:9.5px; color:#0f172a;">
                                        <span id="prev-ribbon-time"><?= date('g:i:sA') ?></span>
                                        <span class="ribbon-center" id="prev-ribbon-title" style="font-style:italic; text-decoration:underline; font-weight:800; font-size:11px; letter-spacing:0.8px;"><?= Security::escape($receiptLbl['title'] ?: 'INVOICE') ?></span>
                                        <span id="prev-ribbon-date"><?= date('d/m/Y') ?></span>
                                    </div>

                                <?php elseif ($secKey === 'meta'): ?>
                                    <!-- SECTION: META -->
                                    <div class="preview-sec <?= empty($receiptVis['show_invoice_no']) && empty($receiptVis['show_customer']) && empty($receiptVis['show_cashier']) && empty($receiptVis['show_counter']) ? 'is-hidden' : '' ?>" data-sec="meta" style="font-size:9.5px; line-height:1.35; margin-bottom:5px; color:#0f172a;">
                                        <div class="preview-part-invoice_no <?= empty($receiptVis['show_invoice_no']) ? 'is-hidden' : '' ?>" style="margin-bottom:2px; display:flex; align-items:center; gap:4px;">
                                            <span id="prev-inv-prefix"><?= Security::escape($receiptLbl['invoice_prefix'] ?: 'Inv # :') ?></span>
                                            <span style="display:inline-block; border:1px solid #475569; padding:0 4px; font-weight:600; font-size:9.5px; border-radius:2px;">INV-1082</span>
                                        </div>
                                        <div class="preview-part-customer <?= empty($receiptVis['show_customer']) ? 'is-hidden' : '' ?>" style="margin-bottom:2px;">
                                            <span id="prev-cust-label"><?= Security::escape($receiptLbl['customer_label'] ?: 'Name :') ?></span>
                                            <span>Muhammad Usman</span>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:9px; color:#475569; margin-top:2px;">
                                            <span class="preview-part-cashier <?= empty($receiptVis['show_cashier']) ? 'is-hidden' : '' ?>"><span id="prev-cashier-label"><?= Security::escape($receiptLbl['cashier_label'] ?: 'Cashier:') ?></span> admin</span>
                                            <span class="preview-part-counter <?= empty($receiptVis['show_counter']) ? 'is-hidden' : '' ?>" id="prev-counter-label"><?= Security::escape($receiptLbl['counter_label'] ?: 'Counter 1') ?></span>
                                        </div>
                                    </div>

                                <?php elseif ($secKey === 'items'): ?>
                                    <!-- SECTION: ITEMS TABLE -->
                                    <div class="preview-sec" data-sec="items" style="margin-bottom:4px;">
                                        <table style="width:100%; border-collapse:collapse; border:1px solid #0f172a; font-size:9.5px; color:#0f172a;">
                                            <thead>
                                                <tr style="border-bottom:1px solid #0f172a; background:#f8fafc;">
                                                    <th style="padding:3px 4px; text-align:left; border-right:1px solid #0f172a; font-weight:600;">Item Description</th>
                                                    <th style="padding:3px 4px; text-align:right; border-right:1px solid #0f172a; font-weight:600; width:52px;">Price</th>
                                                    <th style="padding:3px 4px; text-align:center; border-right:1px solid #0f172a; font-weight:600; width:34px;">Qty.</th>
                                                    <th style="padding:3px 4px; text-align:right; font-weight:600; width:55px;">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr style="border-bottom:1px solid #0f172a;">
                                                    <td style="padding:2px 4px; border-right:1px solid #0f172a;">Milk Pack 1Ltr</td>
                                                    <td style="padding:2px 4px; text-align:right; border-right:1px solid #0f172a;">
                                                        280.00
                                                        <span class="preview-part-unit_label <?= empty($receiptVis['show_unit_label']) ? 'is-hidden' : '' ?>" style="display:block; font-size:7.5px; color:#64748b;">Sales [PKT]</span>
                                                    </td>
                                                    <td style="padding:2px 4px; text-align:center; border-right:1px solid #0f172a;">3.00</td>
                                                    <td style="padding:2px 4px; text-align:right;">840.00</td>
                                                </tr>
                                                <tr style="border-bottom:1px solid #0f172a;">
                                                    <td style="padding:2px 4px; border-right:1px solid #0f172a;">Cooking Oil 5L</td>
                                                    <td style="padding:2px 4px; text-align:right; border-right:1px solid #0f172a;">
                                                        2,450.00
                                                        <span class="preview-part-unit_label <?= empty($receiptVis['show_unit_label']) ? 'is-hidden' : '' ?>" style="display:block; font-size:7.5px; color:#64748b;">Sales [CAN]</span>
                                                    </td>
                                                    <td style="padding:2px 4px; text-align:center; border-right:1px solid #0f172a;">2.00</td>
                                                    <td style="padding:2px 4px; text-align:right;">4,900.00</td>
                                                </tr>
                                                <tr style="border-bottom:1px solid #0f172a;">
                                                    <td style="padding:2px 4px; border-right:1px solid #0f172a;">Premium Rice 5KG</td>
                                                    <td style="padding:2px 4px; text-align:right; border-right:1px solid #0f172a;">
                                                        1,650.00
                                                        <span class="preview-part-unit_label <?= empty($receiptVis['show_unit_label']) ? 'is-hidden' : '' ?>" style="display:block; font-size:7.5px; color:#64748b;">Sales [BAG]</span>
                                                    </td>
                                                    <td style="padding:2px 4px; text-align:center; border-right:1px solid #0f172a;">1.00</td>
                                                    <td style="padding:2px 4px; text-align:right;">1,650.00</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                <?php elseif ($secKey === 'summary'): ?>
                                    <!-- SECTION: SUMMARY COUNTERS -->
                                    <div class="preview-sec <?= empty($receiptVis['show_item_counters']) ? 'is-hidden' : '' ?>" data-sec="summary" style="display:flex; justify-content:space-between; align-items:center; border-left:1px solid #0f172a; border-right:1px solid #0f172a; border-bottom:1px solid #0f172a; padding:3px 6px; margin-top:-4px; margin-bottom:5px; font-size:9.5px; font-weight:600; color:#0f172a; background:#fafafa;">
                                        <span>Total Items : 3.00</span>
                                        <span>No. Of Qty. : 6.00</span>
                                    </div>

                                <?php elseif ($secKey === 'totals'): ?>
                                    <!-- SECTION: TOTALS -->
                                    <div class="preview-sec" data-sec="totals" style="margin:4px 0; font-size:9.5px; color:#0f172a;">
                                        <div class="preview-part-subtotal <?= empty($receiptVis['show_subtotal']) ? 'is-hidden' : '' ?>" style="display:flex; justify-content:space-between; padding:1px 0;">
                                            <span>Sub Total Amount:</span>
                                            <strong>7,390.00</strong>
                                        </div>
                                        <div class="preview-part-discount <?= empty($receiptVis['show_discount']) ? 'is-hidden' : '' ?>" style="display:flex; justify-content:space-between; padding:1px 0;">
                                            <span>Discount :</span>
                                            <strong>90.00</strong>
                                        </div>
                                        <div class="preview-part-tax <?= empty($receiptVis['show_tax']) ? 'is-hidden' : '' ?>" style="display:flex; justify-content:space-between; padding:1px 0;">
                                            <span>Sales Tax (0%):</span>
                                            <strong>0.00</strong>
                                        </div>
                                        <div class="preview-part-paid <?= empty($receiptVis['show_paid']) ? 'is-hidden' : '' ?>" style="display:flex; justify-content:space-between; padding:1px 0;">
                                            <span>Paid Amount :</span>
                                            <strong>8,000.00</strong>
                                        </div>
                                        <div class="preview-part-due <?= empty($receiptVis['show_due']) ? 'is-hidden' : '' ?>" style="display:flex; justify-content:space-between; padding:1px 0;">
                                            <span>Change Return :</span>
                                            <strong>700.00</strong>
                                        </div>
                                    </div>

                                <?php elseif ($secKey === 'net_payable'): ?>
                                    <!-- SECTION: NET PAYABLE -->
                                    <div class="preview-sec <?= empty($receiptVis['show_net_payable']) ? 'is-hidden' : '' ?>" data-sec="net_payable" style="border:1.5px solid #0f172a; padding:4px 8px; display:flex; justify-content:space-between; align-items:center; font-size:11.5px; font-weight:700; margin:5px 0; color:#0f172a; background:#f8fafc;">
                                        <span id="prev-net-payable-label"><?= Security::escape($receiptLbl['net_payable_label'] ?: 'Net Payable:') ?></span>
                                        <span>7,300.00</span>
                                    </div>

                                <?php elseif ($secKey === 'words'): ?>
                                    <!-- SECTION: WORDS -->
                                    <div class="preview-sec <?= empty($receiptVis['show_words']) ? 'is-hidden' : '' ?>" data-sec="words" style="font-size:9.5px; font-weight:600; text-align:center; padding:3px 2px; margin:4px 0; border-top:1px dashed #cbd5e1; border-bottom:1px dashed #cbd5e1; color:#0f172a;">
                                        <span id="prev-words-prefix"><?= Security::escape($receiptLbl['words_prefix'] ?: 'Rupees') ?></span>: Seven Thousand Three Hundred Only
                                    </div>

                                <?php elseif ($secKey === 'barcode'): ?>
                                    <!-- SECTION: BARCODE -->
                                    <div class="preview-sec <?= empty($receiptVis['show_barcode']) ? 'is-hidden' : '' ?>" data-sec="barcode" style="text-align:center; margin:6px 0 4px;">
                                        <?= BarcodeHelper::renderSvg('INV-1082', 1.3, 34, false); ?>
                                    </div>

                                <?php elseif ($secKey === 'footer'): ?>
                                    <!-- SECTION: FOOTER -->
                                    <div class="preview-sec <?= empty($receiptVis['show_greetings']) ? '' : '' ?>" data-sec="footer">
                                        <div class="preview-part-greetings <?= empty($receiptVis['show_greetings']) ? 'is-hidden' : '' ?>" id="prev-greetings" style="text-align:center; font-size:10px; font-weight:600; margin:4px 0 2px; color:#0f172a;">
                                            <?= Security::escape($receiptLbl['greetings'] ?: 'Thanks For Shopping With Us!') ?>
                                        </div>
                                        <div class="preview-part-return_policy <?= empty($receiptVis['show_greetings']) || empty($receiptLbl['return_policy']) ? 'is-hidden' : '' ?>" id="prev-return-policy" style="text-align:center; font-size:8.5px; color:#475569; margin:2px 0 4px; line-height:1.25;">
                                            <?= nl2br(Security::escape($receiptLbl['return_policy'] ?: 'Exchange possible within 7 days with original receipt.')) ?>
                                        </div>
                                        <?php 
                                        $prevLogoSize = max(18, min(45, (int) round((int)App::setting('devaltum_credit_logo_size', 48) * 0.52)));
                                        $prevFontSize = max(7, min(14, (float) round((float)App::setting('devaltum_credit_font_size', 12) * 0.70, 1)));
                                        $prevPhoneSize = max(7.5, min(16, (float) round($prevFontSize * 1.12, 1)));
                                        ?>
                                        <div class="preview-part-developer_credit" id="prev-developer-credit" style="display:flex; align-items:center; justify-content:center; gap:8px; color:#0f172a; border-top:1px dashed #cbd5e1; padding-top:6px; margin-top:6px; line-height:1.2; font-family:'Orbitron', sans-serif;">
                                            <img id="prev-credit-logo" src="<?= App::devAltumReceiptLogoDataUri() ?>" alt="DevAltum" style="height:<?= $prevLogoSize ?>px; width:auto; max-width:60px; object-fit:contain; flex-shrink:0;">
                                            <div id="prev-credit-text" style="display:flex; flex-direction:column; text-align:left; font-size:<?= $prevFontSize ?>px; font-weight:800;">
                                                <span id="prev-credit-brand" style="font-weight:900;">Software By <strong style="font-weight:900; color:#0f6dff;">DevAltum</strong></span>
                                                <span id="prev-credit-phone" style="font-weight:800; color:#0f172a; margin-top:1px; font-size:<?= $prevPhoneSize ?>px;">+92 3101648600</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Compact Premium Confirmation Dialog -->
<div id="premium-confirm-modal" class="premium-confirm-overlay" aria-hidden="true">
    <div class="premium-confirm-card">
        <div class="premium-confirm-icon-wrap warning" id="premium-confirm-icon-wrap">
            <span class="material-symbols-rounded" id="premium-confirm-icon">help_outline</span>
        </div>
        <h4 class="premium-confirm-title" id="premium-confirm-title">Are you sure?</h4>
        <p class="premium-confirm-desc" id="premium-confirm-desc">Do you want to proceed with this action?</p>
        <div class="premium-confirm-buttons">
            <button type="button" class="btn btn-secondary" id="premium-confirm-cancel">Cancel</button>
            <button type="button" class="btn btn-primary" id="premium-confirm-ok">Confirm</button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
    (() => {
        const settingsTabKey = 'settings_active_tab';
        const deviceTabKey = 'settings_device_active_tab';

        window.switchSettingsTab = function(key) {
            const currentTabButtons = document.querySelectorAll('[data-settings-tab]');
            const currentPanels = document.querySelectorAll('[data-settings-panel]');
            if (!currentTabButtons.length) return;
            const exists = Array.from(currentTabButtons).some((btn) => btn.dataset.settingsTab === key);
            const finalKey = exists ? key : (currentTabButtons[0]?.dataset.settingsTab || 'business');
            currentTabButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.settingsTab === finalKey));
            currentPanels.forEach((panel) => panel.classList.toggle('active', panel.dataset.settingsPanel === finalKey));
            sessionStorage.setItem(settingsTabKey, finalKey);
            sessionStorage.setItem('tab_updates/index', finalKey);
        };

        window.switchDeviceTab = function(key) {
            const currentDeviceSubtabs = document.querySelectorAll('[data-device-subtab]');
            const currentDevicePanels = document.querySelectorAll('[data-device-subpanel]');
            if (!currentDeviceSubtabs.length) return;
            const exists = Array.from(currentDeviceSubtabs).some((btn) => btn.dataset.deviceSubtab === key);
            const finalKey = exists ? key : (currentDeviceSubtabs[0]?.dataset.deviceSubtab || 'live');
            currentDeviceSubtabs.forEach((item) => item.classList.toggle('active', item.dataset.deviceSubtab === finalKey));
            currentDevicePanels.forEach((panel) => panel.classList.toggle('active', panel.dataset.deviceSubpanel === finalKey));
            sessionStorage.setItem(deviceTabKey, finalKey);
        };

        // Delegated Global Event Handler - Never loses binding after form save or DOM replacement
        if (!window.__settingsTabDelegationBound) {
            window.__settingsTabDelegationBound = true;
            document.addEventListener('click', function(e) {
                const settingsBtn = e.target.closest('[data-settings-tab]');
                if (settingsBtn) {
                    e.preventDefault();
                    window.switchSettingsTab(settingsBtn.dataset.settingsTab);
                    return;
                }
                const deviceBtn = e.target.closest('[data-device-subtab]');
                if (deviceBtn) {
                    e.preventDefault();
                    window.switchDeviceTab(deviceBtn.dataset.deviceSubtab);
                    return;
                }
                const openDeviceBtn = e.target.closest('[data-open-device-tab]');
                if (openDeviceBtn) {
                    e.preventDefault();
                    window.switchDeviceTab(openDeviceBtn.dataset.openDeviceTab || 'live');
                    return;
                }
            });
        }

        const urlParams = new URLSearchParams(window.location.search);
        const requestedTab = urlParams.get('tab') || sessionStorage.getItem(settingsTabKey) || 'business';
        window.switchSettingsTab(requestedTab);
        window.switchDeviceTab(sessionStorage.getItem(deviceTabKey) || 'live');

        const deviceConsole = document.querySelector('[data-device-console]');
        const setDevice = (key, status, tone = 'ok') => {
            const text = document.querySelector(`[data-device-status="${key}"]`);
            const dot = document.querySelector(`[data-device-dot="${key}"]`);
            if (text) text.textContent = status;
            if (dot) {
                dot.classList.remove('ok', 'warn', 'bad');
                dot.classList.add(tone);
            }
            if (deviceConsole) {
                const line = `[${new Date().toLocaleTimeString()}] ${key.toUpperCase()}: ${status}`;
                deviceConsole.textContent = `${line}\n${deviceConsole.textContent}`.slice(0, 900);
            }
        };
        document.querySelector('[data-test-print]')?.addEventListener('click', () => {
            const win = window.open('', '_blank', 'width=420,height=520');
            if (!win) {
                setDevice('print', 'Popup blocked', 'bad');
                return;
            }
            win.document.write('<!doctype html><title>Printer Test</title><style>body{font-family:Arial;padding:18px}.box{border:2px solid #111827;padding:16px;width:260px}h2{margin:0 0 8px}</style><body onload="window.print();setTimeout(()=>window.close(),1200)"><div class="box"><h2>DevAltum POS</h2><strong>Printer Test</strong><p>Printed at ' + new Date().toLocaleString() + '</p></div></body>');
            win.document.close();
            setDevice('print', 'Test sent to print dialog', 'ok');
        });

        const bluetoothOutput = document.querySelector('[data-bluetooth-output]');
        document.querySelector('[data-bluetooth-check]')?.addEventListener('click', async () => {
            if (!bluetoothOutput) return;
            if (!navigator.bluetooth?.requestDevice) {
                bluetoothOutput.textContent = `[${new Date().toLocaleTimeString()}] Web Bluetooth is not available in this browser. Pair the scanner from Windows Bluetooth settings in HID / Keyboard mode.`;
                return;
            }
            bluetoothOutput.textContent = `[${new Date().toLocaleTimeString()}] Bluetooth is supported. Select your scanner if it appears, or pair it from Windows settings for keyboard mode.`;
            try {
                await navigator.bluetooth.requestDevice({ acceptAllDevices: true });
                bluetoothOutput.textContent = `[${new Date().toLocaleTimeString()}] Bluetooth device selected. If your scanner uses keyboard mode, test it in POS search or USB Scanner test field.`;
            } catch (error) {
                bluetoothOutput.textContent = `[${new Date().toLocaleTimeString()}] Bluetooth pairing was cancelled. Existing OS-paired scanners can still work as keyboard input.`;
            }
        });

        const scannerTest = document.querySelector('[data-hardware-scanner-test]');
        const scannerOutput = document.querySelector('[data-scanner-test-output]');
        scannerTest?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const code = scannerTest.value.trim();
            if (!code) return;
            scannerOutput.textContent = `[${new Date().toLocaleTimeString()}] Scanned: ${code}\n${scannerOutput.textContent}`.slice(0, 700);
            scannerTest.value = '';
            setDevice('usb', 'Scanner input received', 'ok');
        });

        const businessForm = document.getElementById('business-settings-form');
        const taxField = document.getElementById('business-tax-id');
        const payloadInput = document.getElementById('settings-payload');
        if (businessForm && taxField && payloadInput) {
            businessForm.addEventListener('submit', () => {
                payloadInput.value = JSON.stringify({
                    tax_id: taxField.value || ''
                });
            });
        }

        document.querySelectorAll('[data-drop-card]').forEach((card) => {
            const input = card.querySelector('input[type=file]');
            const previewId = input?.dataset.previewTarget;
            const preview = previewId ? document.getElementById(previewId) : null;
            if (!input || !preview) return;

            const renderFilePreview = (file) => {
                if (!file) return;
                const isImage = file.type.startsWith('image/');
                if (isImage) {
                    const reader = new FileReader();
                    reader.onload = () => {
                        preview.innerHTML = `<img src="${reader.result}" alt="Preview"><strong>${file.name}</strong><span class="page-subtitle">Ready to upload</span>`;
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = `<div class="icon"><span class="material-symbols-rounded">folder_zip</span></div><strong>${file.name}</strong><span class="page-subtitle">ZIP package ready for validation</span>`;
                }
            };



            const submitBrandingForm = () => {
                const form = input.closest('form');
                if (!form || !input.files?.length) return;
                window.setTimeout(() => form.submit(), 120);
            };

            input.addEventListener('change', () => {
                renderFilePreview(input.files[0]);
                submitBrandingForm();
            });
            ['dragenter', 'dragover'].forEach((eventName) => card.addEventListener(eventName, (event) => {
                event.preventDefault();
                card.classList.add('dragover');
            }));
            ['dragleave', 'drop'].forEach((eventName) => card.addEventListener(eventName, (event) => {
                event.preventDefault();
                card.classList.remove('dragover');
            }));
            card.addEventListener('drop', (event) => {
                const file = event.dataTransfer?.files?.[0];
                if (!file) return;
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
                renderFilePreview(file);
                submitBrandingForm();
            });
        });

        const migDropzone = document.getElementById('db-migration-dropzone');
        const migInput = document.getElementById('db-migration-file-input');
        const migSubmit = document.getElementById('submit-migration-btn');
        const migTitle = document.getElementById('migration-text-title');
        const migSub = document.getElementById('migration-text-subtitle');

        if (migDropzone && migInput && migSubmit) {
            migInput.addEventListener('change', () => {
                const file = migInput.files?.[0];
                if (file) {
                    migTitle.textContent = file.name;
                    migSub.textContent = (file.size / 1024).toFixed(2) + " KB";
                    migSubmit.removeAttribute('disabled');
                    migDropzone.style.borderColor = "var(--primary)";
                    migDropzone.style.background = "rgba(15,109,255,.03)";
                }
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                migDropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    migDropzone.style.borderColor = "var(--primary)";
                    migDropzone.style.background = "rgba(15,109,255,.05)";
                    migTitle.textContent = "Drop the SQL file here";
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                migDropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    if (eventName === 'dragleave') {
                        migTitle.textContent = migInput.files?.[0] ? migInput.files[0].name : "Drag & Drop Database SQL Dump here";
                        migDropzone.style.borderColor = "#cbd5e1";
                        migDropzone.style.background = "#fafafa";
                    }
                });
            });

            migDropzone.addEventListener('drop', (e) => {
                const file = e.dataTransfer?.files?.[0];
                if (file && file.name.endsWith('.sql')) {
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    migInput.files = transfer.files;
                    migInput.dispatchEvent(new Event('change'));
                }
            });

            const migForm = document.getElementById('db-migration-form');
            migForm?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const ok = await window.premiumConfirm({
                    title: 'Start Database Migration?',
                    message: 'Critical Warning: This will wipe the current store data and migrate everything from the uploaded SQL file. This action cannot be undone.',
                    icon: 'warning',
                    type: 'danger',
                    okText: 'Yes, Start Migration',
                    cancelText: 'Cancel'
                });
                if (ok) {
                    migForm.submit();
                }
            });
        }

        /* ========================================================
           COMPACT PREMIUM CONFIRMATION MODAL HELPER
           ======================================================== */
        window.premiumConfirm = function(options = {}) {
            return new Promise((resolve) => {
                const overlay = document.getElementById('premium-confirm-modal');
                if (!overlay) {
                    resolve(confirm(options.message || 'Are you sure?'));
                    return;
                }

                const iconWrap = document.getElementById('premium-confirm-icon-wrap');
                const iconEl = document.getElementById('premium-confirm-icon');
                const titleEl = document.getElementById('premium-confirm-title');
                const descEl = document.getElementById('premium-confirm-desc');
                const okBtn = document.getElementById('premium-confirm-ok');
                const cancelBtn = document.getElementById('premium-confirm-cancel');

                const type = options.type || 'primary';
                if (iconWrap) {
                    iconWrap.className = 'premium-confirm-icon-wrap ' + type;
                }
                if (iconEl) {
                    iconEl.textContent = options.icon || (type === 'danger' ? 'warning' : (type === 'warning' ? 'help_outline' : 'info'));
                }
                if (titleEl) {
                    titleEl.textContent = options.title || 'Are you sure?';
                }
                if (descEl) {
                    descEl.textContent = options.message || 'Do you want to proceed with this action?';
                }
                if (okBtn) {
                    okBtn.textContent = options.okText || 'Confirm';
                    okBtn.className = 'btn ' + (type === 'danger' ? 'btn-danger' : 'btn-primary');
                    if (type === 'danger') {
                        okBtn.style.background = '#ef4444';
                        okBtn.style.borderColor = '#ef4444';
                        okBtn.style.color = '#fff';
                    } else {
                        okBtn.style.background = '';
                        okBtn.style.borderColor = '';
                        okBtn.style.color = '';
                    }
                }
                if (cancelBtn) {
                    cancelBtn.textContent = options.cancelText || 'Cancel';
                }

                const closeModal = (result) => {
                    overlay.classList.remove('is-open');
                    overlay.setAttribute('aria-hidden', 'true');
                    cleanup();
                    resolve(result);
                };

                const onOk = () => closeModal(true);
                const onCancel = () => closeModal(false);
                const onKey = (e) => {
                    if (e.key === 'Escape') closeModal(false);
                };
                const onBackdrop = (e) => {
                    if (e.target === overlay) closeModal(false);
                };

                const cleanup = () => {
                    okBtn?.removeEventListener('click', onOk);
                    cancelBtn?.removeEventListener('click', onCancel);
                    document.removeEventListener('keydown', onKey);
                    overlay?.removeEventListener('click', onBackdrop);
                };

                okBtn?.addEventListener('click', onOk);
                cancelBtn?.addEventListener('click', onCancel);
                document.addEventListener('keydown', onKey);
                overlay?.addEventListener('click', onBackdrop);

                overlay.classList.add('is-open');
                overlay.setAttribute('aria-hidden', 'false');
            });
        };

        /* ========================================================
           RECEIPT DESIGNER STUDIO INTERACTIVITY & LIVE SYNC
           ======================================================== */
        const initReceiptDesigner = () => {
            const form = document.getElementById('receipt-designer-form');
            if (!form) return;

            const slip = document.getElementById('virtualThermalSlip');
            const orderList = document.getElementById('designerOrderList');
            const subtabBtns = form.querySelectorAll('[data-designer-subtab]');
            const subpanels = form.querySelectorAll('[data-designer-subpanel]');

            // 1. Sub-tab Navigation
            subtabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetKey = btn.dataset.designerSubtab;
                    subtabBtns.forEach(b => b.classList.toggle('active', b.dataset.designerSubtab === targetKey));
                    subpanels.forEach(p => p.classList.toggle('active', p.dataset.designerSubpanel === targetKey));
                });
            });

            // 2. Field Visibility Toggles
            const updateFieldVisibility = (toggleInput) => {
                const key = toggleInput.dataset.designerToggle;
                const isChecked = toggleInput.checked;

                if (key === 'ribbon') {
                    slip?.querySelector('.preview-sec[data-sec="ribbon"]')?.classList.toggle('is-hidden', !isChecked);
                } else if (key === 'item_counters') {
                    slip?.querySelector('.preview-sec[data-sec="summary"]')?.classList.toggle('is-hidden', !isChecked);
                } else if (key === 'net_payable') {
                    slip?.querySelector('.preview-sec[data-sec="net_payable"]')?.classList.toggle('is-hidden', !isChecked);
                } else if (key === 'words') {
                    slip?.querySelector('.preview-sec[data-sec="words"]')?.classList.toggle('is-hidden', !isChecked);
                } else if (key === 'barcode') {
                    slip?.querySelector('.preview-sec[data-sec="barcode"]')?.classList.toggle('is-hidden', !isChecked);
                } else if (key === 'unit_label') {
                    slip?.querySelectorAll('.preview-part-unit_label')?.forEach(el => el.classList.toggle('is-hidden', !isChecked));
                } else if (key === 'greetings') {
                    slip?.querySelector('#prev-greetings')?.classList.toggle('is-hidden', !isChecked);
                    slip?.querySelector('#prev-return-policy')?.classList.toggle('is-hidden', !isChecked);
                } else {
                    const targetEl = slip?.querySelector('.preview-part-' + key);
                    if (targetEl) targetEl.classList.toggle('is-hidden', !isChecked);
                }

                // Header container auto check
                const headerSec = slip?.querySelector('.preview-sec[data-sec="header"]');
                if (headerSec) {
                    const anyHeaderPart = headerSec.querySelector('.preview-part-logo:not(.is-hidden), .preview-part-store_name:not(.is-hidden), .preview-part-tagline:not(.is-hidden), .preview-part-address:not(.is-hidden), .preview-part-phone:not(.is-hidden), .preview-part-ntn:not(.is-hidden)');
                    headerSec.classList.toggle('is-hidden', !anyHeaderPart);
                }

                // Meta container auto check
                const metaSec = slip?.querySelector('.preview-sec[data-sec="meta"]');
                if (metaSec) {
                    const anyMetaPart = metaSec.querySelector('.preview-part-invoice_no:not(.is-hidden), .preview-part-customer:not(.is-hidden), .preview-part-cashier:not(.is-hidden), .preview-part-counter:not(.is-hidden)');
                    metaSec.classList.toggle('is-hidden', !anyMetaPart);
                }

                // Footer container auto check
                const footerSec = slip?.querySelector('.preview-sec[data-sec="footer"]');
                if (footerSec) {
                    const anyFooterPart = footerSec.querySelector('.preview-part-greetings:not(.is-hidden), .preview-part-return_policy:not(.is-hidden), .preview-part-developer_credit:not(.is-hidden)');
                    footerSec.classList.toggle('is-hidden', !anyFooterPart);
                }
            };

            form.querySelectorAll('[data-designer-toggle]').forEach(input => {
                input.addEventListener('change', () => updateFieldVisibility(input));
            });

            // 3. Live Headings & Content Text Input Sync
            form.querySelectorAll('[data-preview-bind]').forEach(input => {
                input.addEventListener('input', () => {
                    const targetId = input.dataset.previewBind;
                    const previewEl = document.getElementById(targetId);
                    if (!previewEl) return;

                    if (input.tagName === 'TEXTAREA') {
                        previewEl.innerHTML = input.value.trim() ? input.value.replace(/\n/g, '<br>') : '';
                        previewEl.classList.toggle('is-hidden', !input.value.trim());
                    } else if (targetId === 'prev-store-name') {
                        const fallback = input.placeholder || 'DevAltum POS';
                        previewEl.textContent = input.value.trim() || fallback;
                    } else {
                        previewEl.textContent = input.value;
                    }
                });
            });

            // Font Preset
            const fontSelect = document.getElementById('font-size-preset-select');
            if (fontSelect && slip) {
                const applyFontPreset = () => {
                    const val = fontSelect.value;
                    if (val === 'compact') {
                        slip.style.fontSize = '9px';
                    } else if (val === 'large') {
                        slip.style.fontSize = '11.5px';
                    } else {
                        slip.style.fontSize = '10px';
                    }
                };
                fontSelect.addEventListener('change', applyFontPreset);
                applyFontPreset();
            }

            // DevAltum Credit Sizing Live Sync
            const devLogoSelect = document.getElementById('devaltum-logo-size-select');
            const devFontSelect = document.getElementById('devaltum-font-size-select');
            const prevCreditLogo = document.getElementById('prev-credit-logo');
            const prevCreditBrand = document.getElementById('prev-credit-brand');
            const prevCreditPhone = document.getElementById('prev-credit-phone');

            const applyCreditSizing = () => {
                if (devLogoSelect && prevCreditLogo) {
                    const rawH = parseInt(devLogoSelect.value, 10) || 48;
                    const scaledH = Math.max(16, Math.min(48, Math.round(rawH * 0.52)));
                    prevCreditLogo.style.height = scaledH + 'px';
                    const logoBadge = document.getElementById('current-logo-badge');
                    if (logoBadge) logoBadge.textContent = rawH + 'px';
                }
                if (devFontSelect && prevCreditBrand && prevCreditPhone) {
                    const rawF = parseFloat(devFontSelect.value) || 12;
                    const scaledF = Math.max(7, Math.min(14, Math.round(rawF * 0.70 * 10) / 10));
                    const scaledPhone = Math.max(7.5, Math.min(16, Math.round(scaledF * 1.12 * 10) / 10));
                    prevCreditBrand.style.fontSize = scaledF + 'px';
                    prevCreditPhone.style.fontSize = scaledPhone + 'px';
                    const fontBadge = document.getElementById('current-font-badge');
                    if (fontBadge) fontBadge.textContent = rawF + 'px';
                }
            };
            devLogoSelect?.addEventListener('change', applyCreditSizing);
            devFontSelect?.addEventListener('change', applyCreditSizing);
            applyCreditSizing();

            // 4. Section Reordering (Drag & Drop + Up/Down buttons)
            const syncVirtualSlipOrder = () => {
                if (!orderList || !slip) return;
                const items = orderList.querySelectorAll('.designer-order-item');
                items.forEach(item => {
                    const secKey = item.dataset.secKey;
                    const previewSec = slip.querySelector(`.preview-sec[data-sec="${secKey}"]`);
                    if (previewSec) {
                        slip.appendChild(previewSec);
                    }
                });
            };

            let draggedItem = null;

            if (orderList) {
                orderList.addEventListener('dragstart', (e) => {
                    const item = e.target.closest('.designer-order-item');
                    if (!item) return;
                    draggedItem = item;
                    item.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', item.dataset.secKey);
                });

                orderList.addEventListener('dragend', (e) => {
                    if (draggedItem) {
                        draggedItem.classList.remove('dragging');
                        draggedItem = null;
                    }
                    orderList.querySelectorAll('.designer-order-item').forEach(el => el.classList.remove('drag-over'));
                    syncVirtualSlipOrder();
                });

                orderList.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    const targetItem = e.target.closest('.designer-order-item');
                    if (!targetItem || targetItem === draggedItem) return;

                    const bounding = targetItem.getBoundingClientRect();
                    const offset = e.clientY - bounding.top;
                    if (offset > bounding.height / 2) {
                        targetItem.after(draggedItem);
                    } else {
                        targetItem.before(draggedItem);
                    }
                });

                // Up / Down click handlers
                orderList.querySelectorAll('.btn-move-up').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const item = btn.closest('.designer-order-item');
                        const prev = item?.previousElementSibling;
                        if (prev) {
                            item.parentNode.insertBefore(item, prev);
                            syncVirtualSlipOrder();
                        }
                    });
                });

                orderList.querySelectorAll('.btn-move-down').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const item = btn.closest('.designer-order-item');
                        const next = item?.nextElementSibling;
                        if (next) {
                            item.parentNode.insertBefore(next, item);
                            syncVirtualSlipOrder();
                        }
                    });
                });
            }

            // 5. AJAX Saving
            const saveBtn = document.getElementById('save-designer-btn');
            if (saveBtn) {
                saveBtn.addEventListener('click', async () => {
                    const origContent = saveBtn.innerHTML;
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<span class="material-symbols-rounded" style="font-size:18px; animation:spin 1s linear infinite;">sync</span> Saving...';

                    try {
                        const formData = new FormData(form);

                        // Explicitly serialize all toggles (checked = 1, unchecked = 0)
                        const visibilityObj = {};
                        form.querySelectorAll('[data-designer-toggle]').forEach(cb => {
                            const key = cb.name.replace(/^receipt_visibility\[/, '').replace(/\]$/, '');
                            visibilityObj[key] = cb.checked ? 1 : 0;
                        });
                        formData.set('receipt_visibility_json', JSON.stringify(visibilityObj));

                        // Explicitly serialize section order from DOM
                        const currentOrder = [];
                        orderList?.querySelectorAll('.designer-order-item')?.forEach(item => {
                            if (item.dataset.secKey) currentOrder.push(item.dataset.secKey);
                        });
                        if (currentOrder.length) {
                            formData.set('receipt_section_order_json', JSON.stringify(currentOrder));
                        }

                        const baseUrl = '<?= defined("BASE_URL") ? BASE_URL : "" ?>';
                        const res = await fetch(baseUrl + '/updates/saveReceiptDesigner', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await res.json();

                        if (data.success) {
                            if (window.appToast) {
                                window.appToast(data.message || 'Receipt design saved!', 'success');
                            }
                            saveBtn.innerHTML = '<span class="material-symbols-rounded" style="font-size:18px;">check_circle</span> Saved!';
                            setTimeout(() => {
                                saveBtn.innerHTML = origContent;
                                saveBtn.disabled = false;
                            }, 1800);
                        } else {
                            alert(data.error || 'Failed to save receipt design.');
                            saveBtn.innerHTML = origContent;
                            saveBtn.disabled = false;
                        }
                    } catch (err) {
                        console.error(err);
                        // Fallback to normal form submit
                        form.submit();
                    }
                });
            }

            // 6. Reset Defaults
            const resetBtn = document.getElementById('reset-designer-btn');
            if (resetBtn) {
                resetBtn.addEventListener('click', async () => {
                    const ok = await window.premiumConfirm({
                        title: 'Reset Receipt Layout?',
                        message: 'Reset all receipt layout toggles, headings, and section ordering back to default retail standards?',
                        icon: 'restart_alt',
                        type: 'primary',
                        okText: 'Yes, Reset Defaults',
                        cancelText: 'Cancel'
                    });
                    if (!ok) return;
                    
                    form.querySelectorAll('[data-designer-toggle]').forEach(cb => {
                        cb.checked = true;
                        updateFieldVisibility(cb);
                    });

                    const defaultLabels = {
                        'receipt_labels[title]': 'INVOICE',
                        'receipt_labels[store_name_override]': '',
                        'receipt_labels[invoice_prefix]': 'Inv # :',
                        'receipt_labels[customer_label]': 'Name :',
                        'receipt_labels[cashier_label]': 'Cashier:',
                        'receipt_labels[counter_label]': 'Counter 1',
                        'receipt_labels[net_payable_label]': 'Net Payable:',
                        'receipt_labels[words_prefix]': 'Rupees',
                        'receipt_labels[greetings]': 'Thanks For Shopping With Us!',
                        'receipt_labels[return_policy]': 'Exchange possible within 7 days with original receipt.'
                    };

                    for (const [name, val] of Object.entries(defaultLabels)) {
                        const input = form.querySelector(`[name="${name}"]`);
                        if (input) {
                            input.value = val;
                            input.dispatchEvent(new Event('input'));
                        }
                    }

                    if (fontSelect) {
                        fontSelect.value = 'normal';
                        fontSelect.dispatchEvent(new Event('change'));
                    }

                    if (devLogoSelect) {
                        devLogoSelect.value = '48';
                    }
                    if (devFontSelect) {
                        devFontSelect.value = '12';
                    }
                    applyCreditSizing();

                    // Reset section order in DOM back to default
                    const defaultOrder = ['header', 'ribbon', 'meta', 'items', 'summary', 'totals', 'net_payable', 'words', 'barcode', 'footer'];
                    if (orderList) {
                        defaultOrder.forEach(secKey => {
                            const item = orderList.querySelector(`.designer-order-item[data-sec-key="${secKey}"]`);
                            if (item) orderList.appendChild(item);
                        });
                        syncVirtualSlipOrder();
                    }

                    if (window.appToast) {
                        window.appToast('Reset to default receipt configuration. Click Save to keep these settings.', 'info');
                    }
                });
            }
        };

        initReceiptDesigner();

        // POS Display Settings Live Interactive Customizer Studio
        const initDisplayCustomizer = () => {
            const ctrlShowImages = document.getElementById('ctrl_show_images');
            const layoutRadios = document.querySelectorAll('input[name="pos_card_layout"]');
            const fitRadios = document.querySelectorAll('input[name="pos_image_fit"]');
            const ctrlShowName = document.getElementById('ctrl_show_name');
            const ctrlShowStock = document.getElementById('ctrl_show_stock');
            const ctrlShowUnit = document.getElementById('ctrl_show_unit');
            const ctrlShowPrice = document.getElementById('ctrl_show_price');

            const livePreviewCard = document.getElementById('livePreviewCard');
            const previewImgBox = document.getElementById('previewImgBox');
            const previewImgEl = document.getElementById('previewImgEl');
            const previewInfoBox = document.getElementById('previewInfoBox');
            const previewStockEl = document.getElementById('previewStockEl');
            const previewTitleEl = document.getElementById('previewTitleEl');
            const previewUnitEl = document.getElementById('previewUnitEl');
            const previewFootBox = document.getElementById('previewFootBox');
            const previewPriceEl = document.getElementById('previewPriceEl');
            const previewModeTitle = document.getElementById('previewModeTitle');
            const previewModeDesc = document.getElementById('previewModeDesc');
            const previewClickToast = document.getElementById('previewClickToast');
            const imageFitGroup = document.getElementById('imageFitGroup');

            const labelLayoutStandard = document.getElementById('label_layout_standard');
            const labelLayoutVisual = document.getElementById('label_layout_visual');
            const labelFitCover = document.getElementById('label_fit_cover');
            const labelFitContain = document.getElementById('label_fit_contain');

            const samples = {
                headset: {
                    title: 'Wireless Studio Headphones',
                    stock: 'Stock: 24 PCS',
                    unit: '1 PCS',
                    price: 'Rs 4,850',
                    svg: "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 400'><rect width='400' height='400' fill='%23f8fafc'/><circle cx='200' cy='200' r='140' fill='%230f6dff' opacity='0.12'/><path d='M140 210c0-33 27-60 60-60s60 27 60 60v35c0 14-11 25-25 25h-5c-6 0-10-4-10-10v-30c0-6 4-10 10-10h10v-10c0-22-18-40-40-40s-40 18-40 40v10h10c6 0 10 4 10 10v30c0 6-4 10-10 10h-5c-14 0-25-11-25-25v-35z' fill='%230f6dff'/></svg>"
                },
                coffee: {
                    title: 'Iced Caramel Macchiato',
                    stock: 'Stock: 48 CUPS',
                    unit: '1 CUP',
                    price: 'Rs 750',
                    svg: "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 400'><rect width='400' height='400' fill='%23fef3c7'/><circle cx='200' cy='200' r='130' fill='%23f59e0b' opacity='0.18'/><path d='M160 140h80l-12 140h-56z' fill='%23d97706'/><rect x='150' y='125' width='100' height='16' rx='8' fill='%23b45309'/><path d='M215 110l15-30' stroke='%23ef4444' stroke-width='8' stroke-linecap='round'/></svg>"
                },
                apparel: {
                    title: 'Air Pulse Retro Sneakers',
                    stock: 'Stock: 12 PAIRS',
                    unit: '1 PAIR',
                    price: 'Rs 8,900',
                    svg: "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 400'><rect width='400' height='400' fill='%23ecfdf5'/><circle cx='200' cy='200' r='135' fill='%2310b981' opacity='0.15'/><path d='M130 240l20-50 40 10 70-30 20 40-10 30-140 0z' fill='%23059669'/><path d='M120 240h160v16H120z' fill='%23047857'/></svg>"
                }
            };
            let currentSample = 'headset';

            const updateLivePreview = () => {
                if (!livePreviewCard) return;

                const showImages = ctrlShowImages?.checked ?? true;
                const layout = document.querySelector('input[name="pos_card_layout"]:checked')?.value || 'standard';
                const fit = document.querySelector('input[name="pos_image_fit"]:checked')?.value || 'cover';
                const showName = ctrlShowName?.checked ?? true;
                const showStock = ctrlShowStock?.checked ?? true;
                const showUnit = ctrlShowUnit?.checked ?? true;
                const showPrice = ctrlShowPrice?.checked ?? true;

                // Sync radio active visual cards
                labelLayoutStandard?.classList.toggle('active', layout === 'standard');
                labelLayoutVisual?.classList.toggle('active', layout === 'visual_1x1');
                labelFitCover?.classList.toggle('active', fit === 'cover');
                labelFitContain?.classList.toggle('active', fit === 'contain');

                if (imageFitGroup) {
                    imageFitGroup.style.opacity = showImages ? '1' : '0.45';
                    imageFitGroup.style.pointerEvents = showImages ? 'auto' : 'none';
                }

                // Layout Class
                if (layout === 'visual_1x1') {
                    livePreviewCard.className = 'live-card-sim mode-visual-1x1';
                } else {
                    livePreviewCard.className = 'live-card-sim';
                }

                // Image Visibility and Fit
                if (previewImgBox && previewImgEl) {
                    previewImgBox.style.display = showImages ? '' : 'none';
                    previewImgEl.style.objectFit = fit;
                    previewImgEl.style.width = '100%';
                    previewImgEl.style.height = '100%';
                }

                // Name, Stock, Unit elements
                if (previewStockEl) previewStockEl.style.display = showStock ? '' : 'none';
                if (previewTitleEl) previewTitleEl.style.display = showName ? '' : 'none';
                if (previewUnitEl) previewUnitEl.style.display = showUnit ? '' : 'none';

                const hasInfo = showName || showStock || showUnit;
                if (previewInfoBox) {
                    previewInfoBox.style.display = hasInfo ? '' : 'none';
                }

                // Price & Footer
                if (previewPriceEl) previewPriceEl.style.display = showPrice ? '' : 'none';
                if (previewFootBox) {
                    if (layout === 'visual_1x1') {
                        previewFootBox.style.display = showPrice ? '' : 'none';
                    } else {
                        previewFootBox.style.display = '';
                    }
                }

                // Explanations
                if (previewModeTitle && previewModeDesc) {
                    if (layout === 'visual_1x1') {
                        if (!showName && !showStock && showPrice) {
                            previewModeTitle.textContent = '1:1 Visual Grid: Full-Fit Image + Floating Price';
                            previewModeDesc.textContent = 'Image occupies 100% of the square tile without stretching (' + fit + '). Name and stock are hidden for an ultra-clean touch catalog.';
                        } else {
                            previewModeTitle.textContent = '1:1 Visual Grid (Square Tile)';
                            previewModeDesc.textContent = 'Cards render as modern 1:1 square tiles. Overlays display smoothly on hover/touch.';
                        }
                    } else {
                        if (!showImages) {
                            previewModeTitle.textContent = 'Standard Card (Text Only)';
                            previewModeDesc.textContent = 'High-density checkout cards without product photos for maximum vertical compact efficiency.';
                        } else {
                            previewModeTitle.textContent = 'Standard Retail Card Layout';
                            previewModeDesc.textContent = 'Balanced card with photo thumbnail, stock status, product name, and bottom price bar.';
                        }
                    }
                }
            };

            // Event Listeners
            ctrlShowImages?.addEventListener('change', updateLivePreview);
            ctrlShowName?.addEventListener('change', updateLivePreview);
            ctrlShowStock?.addEventListener('change', updateLivePreview);
            ctrlShowUnit?.addEventListener('change', updateLivePreview);
            ctrlShowPrice?.addEventListener('change', updateLivePreview);

            layoutRadios.forEach(radio => radio.addEventListener('change', updateLivePreview));
            fitRadios.forEach(radio => radio.addEventListener('change', updateLivePreview));

            // Sample Selectors
            document.querySelectorAll('.sample-select-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.sample-select-btn').forEach(b => {
                        b.classList.remove('active');
                        b.style.color = '#94a3b8';
                    });
                    btn.classList.add('active');
                    btn.style.color = '#ffffff';

                    const sampleKey = btn.dataset.sample;
                    const sample = samples[sampleKey];
                    if (sample && previewImgEl && previewTitleEl && previewStockEl && previewUnitEl && previewPriceEl) {
                        previewImgEl.src = `data:image/svg+xml;utf8,${encodeURIComponent(sample.svg)}`;
                        previewTitleEl.textContent = sample.title;
                        previewStockEl.textContent = sample.stock;
                        previewUnitEl.textContent = sample.unit;
                        previewPriceEl.textContent = sample.price;
                    }
                });
            });

            // Preset Handlers
            document.getElementById('btnPresetImageOnly')?.addEventListener('click', () => {
                const visualRadio = document.querySelector('input[name="pos_card_layout"][value="visual_1x1"]');
                const coverRadio = document.querySelector('input[name="pos_image_fit"][value="cover"]');
                if (visualRadio) visualRadio.checked = true;
                if (coverRadio) coverRadio.checked = true;
                if (ctrlShowImages) ctrlShowImages.checked = true;
                if (ctrlShowName) ctrlShowName.checked = false;
                if (ctrlShowStock) ctrlShowStock.checked = false;
                if (ctrlShowUnit) ctrlShowUnit.checked = false;
                if (ctrlShowPrice) ctrlShowPrice.checked = true;
                updateLivePreview();
                if (window.appToast) window.appToast('Applied: 1:1 Full Image + Price Only Preset', 'success');
            });

            document.getElementById('btnPresetStandard')?.addEventListener('click', () => {
                const stdRadio = document.querySelector('input[name="pos_card_layout"][value="standard"]');
                const coverRadio = document.querySelector('input[name="pos_image_fit"][value="cover"]');
                if (stdRadio) stdRadio.checked = true;
                if (coverRadio) coverRadio.checked = true;
                if (ctrlShowImages) ctrlShowImages.checked = true;
                if (ctrlShowName) ctrlShowName.checked = true;
                if (ctrlShowStock) ctrlShowStock.checked = true;
                if (ctrlShowUnit) ctrlShowUnit.checked = true;
                if (ctrlShowPrice) ctrlShowPrice.checked = true;
                updateLivePreview();
                if (window.appToast) window.appToast('Applied: Complete Standard Retail Preset', 'success');
            });

            document.getElementById('btnPresetMinimal')?.addEventListener('click', () => {
                const visualRadio = document.querySelector('input[name="pos_card_layout"][value="visual_1x1"]');
                const coverRadio = document.querySelector('input[name="pos_image_fit"][value="cover"]');
                if (visualRadio) visualRadio.checked = true;
                if (coverRadio) coverRadio.checked = true;
                if (ctrlShowImages) ctrlShowImages.checked = true;
                if (ctrlShowName) ctrlShowName.checked = true;
                if (ctrlShowStock) ctrlShowStock.checked = false;
                if (ctrlShowUnit) ctrlShowUnit.checked = false;
                if (ctrlShowPrice) ctrlShowPrice.checked = true;
                updateLivePreview();
                if (window.appToast) window.appToast('Applied: 1:1 Image + Title + Price Preset', 'success');
            });

            // Simulated Card Click Feedback
            if (livePreviewCard && previewClickToast) {
                livePreviewCard.addEventListener('click', () => {
                    previewClickToast.style.opacity = '1';
                    livePreviewCard.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        livePreviewCard.style.transform = '';
                    }, 150);
                    setTimeout(() => {
                        previewClickToast.style.opacity = '0';
                    }, 1200);
                });
            }

            updateLivePreview();
        };

        initDisplayCustomizer();
    })();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
