<?php
$thermalTemplates = array_filter($templates, fn($template) => ($template['group'] ?? 'thermal') === 'thermal');
$documentTemplates = array_filter($templates, fn($template) => ($template['group'] ?? 'thermal') === 'document');
$activeGroup = isset($templates[$selectedTemplate]) && (($templates[$selectedTemplate]['group'] ?? 'thermal') === 'document') ? 'document' : 'thermal';
ob_start();
?>

<style>
    .templates-page { display:grid; gap:18px; }
    .templates-hero {
        border:1px solid rgba(15,109,255,.18);
        border-radius:20px;
        padding:18px;
        background:linear-gradient(135deg, rgba(15,109,255,.08), rgba(16,185,129,.08));
    }
    .templates-grid {
        display:grid;
        grid-template-columns:minmax(0, 1fr) minmax(330px, .78fr);
        gap:18px;
        align-items:start;
    }
    .template-card-list { display:grid; gap:14px; }
    .template-tabs {
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:10px;
        padding:8px;
        border:1px solid var(--border);
        border-radius:18px;
        background:#fff;
        box-shadow:0 12px 28px rgba(15,23,42,.05);
        margin-bottom:14px;
    }
    .template-tab {
        border:1px solid transparent;
        border-radius:14px;
        background:#f8fafc;
        color:#475569;
        min-height:58px;
        padding:10px 12px;
        font-weight:900;
        cursor:pointer;
        display:flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        text-align:center;
    }
    .template-tab .material-symbols-rounded { font-size:21px; }
    .template-tab.active {
        background:linear-gradient(135deg, #0f6dff, #16a34a);
        border-color:rgba(15,109,255,.35);
        color:#fff;
        box-shadow:0 14px 28px rgba(15,109,255,.18);
    }
    .template-group { display:none; gap:14px; }
    .template-group.active { display:grid; }
    .template-card {
        border:1px solid var(--border);
        border-radius:18px;
        background:linear-gradient(150deg, #fff, #f8fbff);
        padding:16px;
        box-shadow:0 14px 30px rgba(15,23,42,.06);
        display:grid;
        grid-template-columns:58px minmax(0, 1fr) auto;
        gap:14px;
        align-items:center;
    }
    .template-icon {
        width:52px;
        height:52px;
        border-radius:16px;
        display:grid;
        place-items:center;
        background:rgba(15,109,255,.1);
        color:var(--primary);
    }
    .template-icon .material-symbols-rounded { font-size:26px; }
    .template-card h3 { margin-bottom:5px; font-size:17px; }
    .template-card p { margin:0; color:var(--text-muted); font-size:13px; font-weight:700; line-height:1.5; }
    .template-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
    .template-select-form { margin:0; }
    .template-active-btn {
        background:linear-gradient(135deg, #10b981, #059669) !important;
        color:#fff !important;
        border-color:#059669 !important;
        box-shadow:0 12px 24px rgba(5,150,105,.18);
    }
    .preview-panel {
        position:sticky;
        top:calc(var(--header-height) + 18px);
        border:1px solid var(--border);
        border-radius:20px;
        background:#fff;
        padding:18px;
        box-shadow:0 16px 34px rgba(15,23,42,.07);
    }
    .preview-picker {
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        margin:12px 0 16px;
    }
    .preview-picker-wrap { position:relative; }
    .preview-pill {
        border:1px solid var(--border);
        border-radius:999px;
        background:#fff;
        padding:8px 12px;
        font-weight:900;
        cursor:pointer;
    }
    .preview-pill.active { background:#0f6dff; color:#fff; border-color:#0f6dff; }
    .receipt-preview-stage { overflow:auto; padding:12px; background:#eef4ff; border-radius:18px; }
    .receipt-mini {
        width:300px;
        margin:0 auto;
        background:#fff;
        color:#111827;
        box-shadow:0 18px 38px rgba(15,23,42,.14);
        overflow:hidden;
        font-family:'Segoe UI', Arial, sans-serif;
    }
    .receipt-mini, .receipt-mini * { font-weight:700; }
    .receipt-mini .top { padding:14px 14px 10px; text-align:center; border-bottom:1px dashed #d1d5db; }
    .receipt-mini .title { font-size:17px; font-weight:800; }
    .receipt-mini .muted { color:#64748b; font-size:11px; }
    .receipt-mini .block { padding:10px 14px; border-bottom:1px dashed #d1d5db; }
    .receipt-mini .row { display:flex; justify-content:space-between; gap:8px; font-size:11px; margin-bottom:5px; }
    .receipt-mini table { width:100%; border-collapse:collapse; font-size:11px; }
    .receipt-mini th, .receipt-mini td { padding:5px 0; text-align:left; }
    .receipt-mini th:last-child, .receipt-mini td:last-child { text-align:right; }
    .receipt-mini .grand { font-size:14px; padding-top:7px; border-top:1px dashed #d1d5db; }
    .receipt-mini .footer { padding:12px 14px; font-size:10px; color:#475569; }
    .receipt-mini .footer-main { display:flex; align-items:center; justify-content:space-between; gap:8px; }
    .receipt-mini .qr { width:54px; height:54px; border:6px solid #fff; outline:1px solid #d1d5db; background:repeating-linear-gradient(45deg,#111827 0 3px,#fff 3px 6px); flex:0 0 54px; }
    .receipt-mini.template-classic { border-radius:18px; }
    .receipt-mini.template-compact { width:260px; border-radius:4px; font-family:'Courier New', monospace; }
    .receipt-mini.template-compact .top,
    .receipt-mini.template-compact .block,
    .receipt-mini.template-compact .footer { padding-left:10px; padding-right:10px; }
    .receipt-mini.template-compact .title { text-transform:uppercase; font-size:14px; }
    .receipt-mini.template-premium { border-radius:24px; }
    .receipt-mini.template-premium .top { background:linear-gradient(135deg, #0f172a, #1d4ed8); color:#fff; border-bottom:0; }
    .receipt-mini.template-premium .top .muted { color:#dbeafe; }
    .receipt-mini.template-premium .block { border-bottom:1px solid #e2e8f0; }
    .receipt-mini.template-premium .footer { background:#f8fbff; }
    .receipt-mini.template-classic .invoice-head-left,
    .receipt-mini.template-compact .invoice-head-left,
    .receipt-mini.template-premium .invoice-head-left { display:block; }
    .receipt-mini.template-classic .invoice-logo,
    .receipt-mini.template-compact .invoice-logo,
    .receipt-mini.template-premium .invoice-logo { display:none; }
    .receipt-mini.template-classic .invoice-box,
    .receipt-mini.template-compact .invoice-box,
    .receipt-mini.template-premium .invoice-box,
    .receipt-mini.template-classic .signature-row,
    .receipt-mini.template-compact .signature-row,
    .receipt-mini.template-premium .signature-row { display:none; }
    .receipt-mini .thermal-invoice-number { display:none; }
    .receipt-mini.template-classic .company-header-meta,
    .receipt-mini.template-compact .company-header-meta,
    .receipt-mini.template-premium .company-header-meta { display:none; }
    .receipt-mini.template-classic .thermal-invoice-number,
    .receipt-mini.template-compact .thermal-invoice-number,
    .receipt-mini.template-premium .thermal-invoice-number { display:flex; align-items:center; justify-content:center; gap:7px; flex-wrap:wrap; margin-top:4px; color:#1e40af; font-size:11px; font-weight:900; }
    .receipt-mini .thermal-invoice-number .thermal-date { color:#475569; font-size:10px; }
    .receipt-mini.template-premium .thermal-invoice-number,
    .receipt-mini.template-premium .thermal-invoice-number .thermal-date { color:#fff; }
    .receipt-mini.template-invoice {
        width:520px;
        border-radius:0;
        border:2px solid #0b4f66;
        border-top:8px solid #0b4f66;
        border-bottom:8px solid #0b4f66;
        box-shadow:0 18px 38px rgba(15,23,42,.14);
        position:relative;
    }
    .receipt-mini.template-invoice::before,
    .receipt-mini.template-invoice::after {
        content:'';
        position:absolute;
        left:0;
        right:0;
        height:4px;
        background:linear-gradient(90deg, #f59e0b, #0b4f66, #f59e0b);
    }
    .receipt-mini.template-invoice::before { top:-8px; }
    .receipt-mini.template-invoice::after { bottom:-8px; }
    .receipt-mini.template-invoice .top {
        display:grid;
        grid-template-columns:1fr 190px;
        gap:16px;
        text-align:left;
        border-bottom:0;
        padding:20px;
    }
    .receipt-mini.template-invoice .invoice-head-left {
        display:flex;
        align-items:center;
        gap:12px;
    }
    .receipt-mini.template-invoice .invoice-logo {
        width:48px;
        height:48px;
        border:1px solid #cbd5e1;
        border-radius:8px;
        display:grid;
        place-items:center;
        color:#f59e0b;
        font-size:10px;
        font-weight:900;
    }
    .receipt-mini.template-invoice .invoice-box {
        border:1.5px solid #0b4f66;
        border-radius:10px;
        padding:10px 12px;
        color:#0f172a;
    }
    .receipt-mini.template-invoice .invoice-box h4 {
        margin:0 0 6px;
        color:#0b4f66;
        font-size:20px;
        letter-spacing:3px;
    }
    .receipt-mini.template-invoice .block {
        margin:0 20px 8px;
        padding:10px;
        border:1.5px solid #0b4f66;
    }
    .receipt-mini.template-invoice .invoice-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px 18px; }
    .receipt-mini.template-invoice .invoice-info-grid .row { display:grid; grid-template-columns:auto minmax(0,1fr); gap:6px; margin:0; font-size:11px; align-items:center; }
    .receipt-mini.template-invoice .invoice-info-grid .row strong { text-align:left; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .receipt-mini.template-invoice table {
        border:1.5px solid #0b4f66;
    }
    .receipt-mini.template-invoice th {
        background:#0b4f66;
        color:#fff;
        padding:7px 8px;
    }
    .receipt-mini.template-invoice td {
        border:1px solid #0b4f66;
        padding:7px 8px;
    }
    .receipt-mini.template-invoice .footer { padding:12px 20px 22px; }
    .receipt-mini.template-invoice .signature-row {
        display:block;
        margin-top:14px;
        color:#111827;
        text-align:center;
    }
    .receipt-mini.template-invoice .signature-row span {
        display:none;
    }
    .receipt-mini.template-invoice .signature-row .signature-software { display:inline-flex; align-items:center; justify-content:center; border-top:1px dashed #cbd5e1; padding-top:8px; text-align:center; font-weight:800; font-size:11px; white-space:nowrap; width:100%; }
    .receipt-mini.template-retail_standard {
        width:320px;
        border:1px solid #c8cbcf;
        border-radius:4px;
        font-family:Arial, sans-serif;
        color:#000;
        background:#fff;
        padding:12px 10px;
    }
    .receipt-mini.template-retail_standard .invoice-head-left { display:block; text-align:center; }
    .receipt-mini.template-retail_standard .invoice-logo {
        display:inline-flex;
        width:76px;
        height:32px;
        border:1px solid #c8cbcf;
        background:#fbfbfb;
        color:#8c939d;
        font-size:8.5px;
        font-weight:700;
        margin:0 auto 6px;
        align-items:center;
        justify-content:center;
    }
    .receipt-mini.template-retail_standard .title {
        font-family:'Times New Roman', serif;
        font-style:italic;
        font-size:17px;
        font-weight:800;
        color:#000;
        margin-bottom:2px;
    }
    .receipt-mini.template-retail_standard .company-header-meta {
        display:block;
        font-size:10.5px;
        color:#222;
        margin-bottom:2px;
    }
    .receipt-mini.template-retail_standard .invoice-box { display:none; }
    .receipt-mini.template-retail_standard .thermal-invoice-number {
        display:flex;
        justify-content:space-between;
        align-items:center;
        border-top:1px solid #000;
        border-bottom:1px solid #000;
        padding:3px 2px;
        margin:6px 0;
        font-size:10px;
        color:#000;
    }
    .receipt-mini.template-retail_standard table {
        border-collapse:collapse;
        border:1px solid #000;
        font-size:10px;
        width:100%;
    }
    .receipt-mini.template-retail_standard th,
    .receipt-mini.template-retail_standard td {
        border:1px solid #000;
        padding:3px 4px;
        color:#000;
    }
    .receipt-mini.template-retail_standard th {
        background:#fff;
        font-weight:800;
    }
    .receipt-mini.template-retail_standard .grand {
        border:1.5px solid #000;
        padding:3px 5px;
        font-size:11.5px;
        font-weight:900;
        margin:4px 0 2px;
    }
    .receipt-mini.template-executive,
    .receipt-mini.template-emerald,
    .receipt-mini.template-monogram {
        width:520px;
        border-radius:0;
        box-shadow:0 18px 38px rgba(15,23,42,.14);
    }
    .receipt-mini.template-executive {
        border:2px solid #102a43;
        border-top:14px solid #102a43;
    }
    .receipt-mini.template-executive .top {
        display:grid;
        grid-template-columns:1fr 180px;
        gap:16px;
        text-align:left;
        padding:20px;
        background:#f8fafc;
        border-bottom:2px solid #102a43;
    }
    .receipt-mini.template-executive .invoice-box {
        border:0;
        background:#102a43;
        color:#fff;
        border-radius:4px;
        padding:12px;
    }
    .receipt-mini.template-executive .invoice-box h4 { color:#fff; }
    .receipt-mini.template-executive .invoice-box .muted { color:#fff; }
    .receipt-mini.template-executive th { background:#102a43; color:#fff; }
    .receipt-mini.template-executive td { border-bottom:1px solid #cbd5e1; }
    .receipt-mini.template-executive .block { border:0; border-bottom:1px solid #cbd5e1; margin:0 20px; }

    .receipt-mini.template-emerald {
        border:2px solid #047857;
        background:#f0fdf4;
        border-radius:18px;
    }
    .receipt-mini.template-emerald .top {
        display:grid;
        grid-template-columns:1fr 180px;
        gap:16px;
        text-align:left;
        padding:20px;
        background:linear-gradient(135deg, #047857, #10b981);
        color:#fff;
        border-bottom:0;
    }
    .receipt-mini.template-emerald .top .muted { color:#dcfce7; }
    .receipt-mini.template-emerald .invoice-box {
        background:#fff;
        color:#111827;
        border:0;
        border-radius:14px;
        padding:12px;
    }
    .receipt-mini.template-emerald .invoice-box h4 { color:#047857; }
    .receipt-mini.template-emerald .invoice-box .muted { color:#111827; }
    .receipt-mini.template-emerald .block { margin:10px 18px; background:#fff; border:1px solid #bbf7d0; border-radius:14px; }
    .receipt-mini.template-emerald th { background:#047857; color:#fff; }
    .receipt-mini.template-emerald td { border-bottom:1px solid #d1fae5; }

    .receipt-mini.template-monogram {
        border:1px solid #cbd5e1;
        border-left:12px solid #111827;
        background:#fff;
    }
    .receipt-mini.template-monogram .top {
        display:grid;
        grid-template-columns:1fr 170px;
        gap:16px;
        text-align:left;
        padding:20px;
        border-bottom:1px solid #111827;
    }
    .receipt-mini.template-monogram .invoice-box {
        border:1px solid #111827;
        border-radius:0;
        padding:12px;
    }
    .receipt-mini.template-monogram .invoice-box h4 { color:#111827; letter-spacing:2px; }
    .receipt-mini.template-monogram .block { margin:0 20px 8px; border:0; border-bottom:1px solid #e2e8f0; }
    .receipt-mini.template-monogram th { background:#111827; color:#fff; }
    .receipt-mini.template-monogram td { border-bottom:1px solid #e2e8f0; }
    .receipt-mini.template-executive .invoice-head-left,
    .receipt-mini.template-emerald .invoice-head-left,
    .receipt-mini.template-monogram .invoice-head-left { display:flex; align-items:center; gap:12px; }
    .receipt-mini.template-executive .invoice-logo,
    .receipt-mini.template-emerald .invoice-logo,
    .receipt-mini.template-monogram .invoice-logo { width:48px; height:48px; border:1px solid #cbd5e1; border-radius:8px; display:grid; place-items:center; font-size:10px; font-weight:900; background:#fff; color:#111827; }
    .receipt-mini.template-executive .invoice-info-grid,
    .receipt-mini.template-emerald .invoice-info-grid,
    .receipt-mini.template-monogram .invoice-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px 18px; }
    .receipt-mini.template-executive .invoice-info-grid .row,
    .receipt-mini.template-emerald .invoice-info-grid .row,
    .receipt-mini.template-monogram .invoice-info-grid .row { display:grid; grid-template-columns:auto minmax(0,1fr); gap:6px; margin:0; font-size:11px; align-items:center; }
    .receipt-mini.template-executive .signature-row,
    .receipt-mini.template-emerald .signature-row,
    .receipt-mini.template-monogram .signature-row { display:block; margin-top:14px; color:#111827; text-align:center; }
    .receipt-mini.template-executive .signature-row span,
    .receipt-mini.template-emerald .signature-row span,
    .receipt-mini.template-monogram .signature-row span { display:none; }
    .receipt-mini.template-executive .signature-row .signature-software,
    .receipt-mini.template-emerald .signature-row .signature-software,
    .receipt-mini.template-monogram .signature-row .signature-software { display:inline-flex; align-items:center; justify-content:center; border-top:1px dashed #cbd5e1; padding-top:8px; text-align:center; align-self:center; font-weight:800; font-size:11px; white-space:nowrap; width:100%; }
    .receipt-mini.template-invoice,
    .receipt-mini.template-executive,
    .receipt-mini.template-monogram { border-color:var(--receipt-accent, #0b4f66); }
    .receipt-mini.template-invoice::before,
    .receipt-mini.template-invoice::after { background:linear-gradient(90deg, #f59e0b, var(--receipt-accent, #0b4f66), #f59e0b); }
    .receipt-mini.template-invoice .invoice-box,
    .receipt-mini.template-invoice .block,
    .receipt-mini.template-invoice table,
    .receipt-mini.template-invoice td { border-color:var(--receipt-accent, #0b4f66); }
    .receipt-mini.template-invoice .invoice-box h4,
    .receipt-mini.template-emerald .invoice-box h4 { color:var(--receipt-accent, #0b4f66); }
    .receipt-mini.template-invoice th,
    .receipt-mini.template-executive .invoice-box,
    .receipt-mini.template-executive th,
    .receipt-mini.template-monogram th { background:var(--receipt-accent, #0b4f66); }
    .receipt-mini.template-executive .top { border-bottom-color:var(--receipt-accent, #0b4f66); }
    .receipt-mini.template-emerald { border-color:var(--receipt-accent, #047857); }
    .receipt-mini.template-emerald .top { background:linear-gradient(135deg, var(--receipt-accent, #047857), #10b981); }
    .receipt-mini.template-emerald th { background:var(--receipt-accent, #047857); }

    @media (max-width: 980px) {
        .templates-grid { grid-template-columns:1fr; }
        .preview-panel { position:static; }
        .template-card { grid-template-columns:48px minmax(0, 1fr); }
        .template-tabs { grid-template-columns:1fr 1fr; }
        .template-tab { min-height:48px; }
        .template-actions { grid-column:1 / -1; justify-content:flex-end; flex-wrap:nowrap; }
        .template-actions .btn,
        .template-select-form { flex:1 1 0; min-width:0; }
        .template-actions .btn { width:100%; justify-content:center; min-height:42px; padding:0 10px; }
    }
    @media (max-width: 520px) {
        .template-tabs {
            gap: 6px;
            padding: 6px;
            border-radius: 16px;
            max-width: 360px;
            margin-left: auto;
            margin-right: auto;
            justify-self: center;
        }
        .template-tab {
            min-height: 38px;
            padding: 7px 9px;
            border-radius: 12px;
            gap: 5px;
            font-size: 11.5px;
            line-height: 1.15;
        }
        .template-tab .material-symbols-rounded {
            font-size: 17px;
        }
        .template-card { padding:14px; border-radius:16px; }
        .receipt-preview-stage { padding:8px; }
        .receipt-mini { width:260px; }
        .receipt-mini.template-invoice,
        .receipt-mini.template-executive,
        .receipt-mini.template-emerald,
        .receipt-mini.template-monogram { width:500px; transform:scale(.52); transform-origin:top left; margin-right:-240px; margin-bottom:-230px; }
        .template-actions {
            justify-content:center;
            gap:8px;
        }
        .template-actions .btn,
        .template-select-form {
            flex:0 0 auto;
            width:auto;
        }
        .template-actions .btn {
            min-height:36px;
            padding:0 14px;
            font-size:12px;
            border-radius:12px;
        }
        .template-actions .btn .material-symbols-rounded {
            font-size:18px;
        }
        .preview-panel {
            padding:14px;
            border-radius:16px;
            overflow:hidden;
        }
        .preview-panel h3 {
            font-size:18px;
            line-height:1.2;
            margin-bottom:4px;
        }
        .preview-panel .page-subtitle {
            font-size:12px;
            line-height:1.35;
            max-width:100%;
            white-space:normal;
            overflow-wrap:anywhere;
        }
        .preview-picker-wrap {
            margin-left:-14px;
            margin-right:-14px;
            overflow:hidden;
        }
        .preview-picker {
            flex-wrap:nowrap;
            overflow-x:auto;
            gap:6px;
            padding:0 14px 5px;
            margin:10px 0 12px;
            scroll-snap-type:x proximity;
            scroll-behavior:smooth;
            scrollbar-width:none;
            -webkit-overflow-scrolling:touch;
        }
        .preview-picker::-webkit-scrollbar { display:none; }
        .preview-picker-wrap::before,
        .preview-picker-wrap::after {
            content:"";
            position:absolute;
            top:0;
            bottom:6px;
            width:26px;
            pointer-events:none;
            z-index:2;
        }
        .preview-picker-wrap::before {
            left:0;
            background:linear-gradient(90deg, #fff, rgba(255,255,255,0));
        }
        .preview-picker-wrap::after {
            right:0;
            background:linear-gradient(270deg, #fff, rgba(255,255,255,0));
        }
        .preview-pill {
            flex:0 0 auto;
            padding:6px 10px;
            font-size:11.5px;
            border-radius:999px;
            white-space:nowrap;
            scroll-snap-align:start;
            box-shadow:0 6px 14px rgba(15,23,42,.04);
        }
        .receipt-preview-stage {
            max-width:100%;
            overflow:auto;
            border-radius:14px;
        }
        .receipt-mini.template-invoice table,
        .receipt-mini.template-executive table,
        .receipt-mini.template-emerald table,
        .receipt-mini.template-monogram table {
            table-layout:fixed;
        }
        .receipt-mini.template-invoice th,
        .receipt-mini.template-invoice td,
        .receipt-mini.template-executive th,
        .receipt-mini.template-executive td,
        .receipt-mini.template-emerald th,
        .receipt-mini.template-emerald td,
        .receipt-mini.template-monogram th,
        .receipt-mini.template-monogram td {
            font-size:9px;
            line-height:1.2;
            padding:5px 4px;
            word-break:normal;
            overflow-wrap:anywhere;
        }
        .receipt-mini.template-invoice th:nth-child(1),
        .receipt-mini.template-invoice td:nth-child(1),
        .receipt-mini.template-executive th:nth-child(1),
        .receipt-mini.template-executive td:nth-child(1),
        .receipt-mini.template-emerald th:nth-child(1),
        .receipt-mini.template-emerald td:nth-child(1),
        .receipt-mini.template-monogram th:nth-child(1),
        .receipt-mini.template-monogram td:nth-child(1) { width:10%; text-align:center; }
        .receipt-mini.template-invoice th:nth-child(2),
        .receipt-mini.template-invoice td:nth-child(2),
        .receipt-mini.template-executive th:nth-child(2),
        .receipt-mini.template-executive td:nth-child(2),
        .receipt-mini.template-emerald th:nth-child(2),
        .receipt-mini.template-emerald td:nth-child(2),
        .receipt-mini.template-monogram th:nth-child(2),
        .receipt-mini.template-monogram td:nth-child(2) { width:40%; }
        .receipt-mini.template-invoice th:nth-child(3),
        .receipt-mini.template-invoice td:nth-child(3),
        .receipt-mini.template-executive th:nth-child(3),
        .receipt-mini.template-executive td:nth-child(3),
        .receipt-mini.template-emerald th:nth-child(3),
        .receipt-mini.template-emerald td:nth-child(3),
        .receipt-mini.template-monogram th:nth-child(3),
        .receipt-mini.template-monogram td:nth-child(3) { width:14%; text-align:center; }
        .receipt-mini.template-invoice th:nth-child(4),
        .receipt-mini.template-invoice td:nth-child(4),
        .receipt-mini.template-invoice th:nth-child(5),
        .receipt-mini.template-invoice td:nth-child(5),
        .receipt-mini.template-executive th:nth-child(4),
        .receipt-mini.template-executive td:nth-child(4),
        .receipt-mini.template-executive th:nth-child(5),
        .receipt-mini.template-executive td:nth-child(5),
        .receipt-mini.template-emerald th:nth-child(4),
        .receipt-mini.template-emerald td:nth-child(4),
        .receipt-mini.template-emerald th:nth-child(5),
        .receipt-mini.template-emerald td:nth-child(5),
        .receipt-mini.template-monogram th:nth-child(4),
        .receipt-mini.template-monogram td:nth-child(4),
        .receipt-mini.template-monogram th:nth-child(5),
        .receipt-mini.template-monogram td:nth-child(5) { width:18%; text-align:right; }
    }
    @media (max-width: 380px) {
        .template-tab {
            min-height: 36px;
            padding: 6px 7px;
            font-size: 10.5px;
            gap: 4px;
        }
        .template-tab .material-symbols-rounded {
            font-size: 16px;
        }
        .template-actions .btn {
            min-height:34px;
            padding:0 12px;
            font-size:11.5px;
        }
        .preview-panel { padding:12px; }
        .preview-picker-wrap {
            margin-left:-12px;
            margin-right:-12px;
        }
        .preview-picker { padding-left:12px; padding-right:12px; }
        .preview-pill {
            padding:5px 8px;
            font-size:10.5px;
        }
    }
</style>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <div>
        <h1>Receipt Templates Studio</h1>
        <p class="page-subtitle">Preview, customize, and edit thermal & document receipt templates for your store.</p>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/templates/edit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; font-weight:800; border-radius:12px; background:linear-gradient(135deg, #0f6dff, #0052cc); box-shadow:0 4px 14px rgba(15,109,255,0.25);">
            <span class="material-symbols-rounded">add_circle</span> New Custom Template
        </a>
    </div>
</div>

<div class="templates-page">
    <div class="templates-hero" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div>
            <strong style="font-size:16px;">Receipt Template Center</strong>
            <div class="page-subtitle">Selected template controls the receipt look for printing, image sharing and screen preview.</div>
        </div>
        <div style="background:#fff;border:1px solid rgba(15,109,255,0.25);border-radius:12px;padding:8px 14px;display:flex;align-items:center;gap:8px;font-size:12px;color:#1e3a8a;box-shadow:0 4px 12px rgba(15,109,255,0.08);">
            <span class="material-symbols-rounded" style="color:#0f6dff;font-size:18px;">print</span>
            <span><strong>80mm Thermal Optimization:</strong> Zero paper waste engine active (Standard 72mm width & Auto-Cut support).</span>
        </div>
    </div>

    <div class="templates-grid">
        <section class="template-card-list">
            <div class="template-tabs" role="tablist" aria-label="Receipt template categories">
                <button class="template-tab <?php echo $activeGroup === 'thermal' ? 'active' : ''; ?>" type="button" data-template-tab="thermal">
                    <span class="material-symbols-rounded">receipt_long</span>Thermal Printer
                </button>
                <button class="template-tab <?php echo $activeGroup === 'document' ? 'active' : ''; ?>" type="button" data-template-tab="document">
                    <span class="material-symbols-rounded">article</span>Invoice Templates
                </button>
            </div>
            <?php foreach (['thermal' => $thermalTemplates, 'document' => $documentTemplates] as $groupKey => $groupTemplates): ?>
                <div class="template-group <?php echo $activeGroup === $groupKey ? 'active' : ''; ?>" data-template-group="<?php echo Security::escape($groupKey); ?>">
                    <?php foreach ($groupTemplates as $key => $template): ?>
                        <?php $isActive = $selectedTemplate === $key; ?>
                        <article class="template-card" style="--receipt-accent:<?php echo Security::escape((string) ($template['accent_color'] ?? '#0f6dff')); ?>">
                            <div class="template-icon"><span class="material-symbols-rounded"><?php echo $groupKey === 'thermal' ? 'receipt_long' : 'article'; ?></span></div>
                            <div>
                                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:5px;">
                                    <h3><?php echo Security::escape($template['name']); ?></h3>
                                    <span class="badge <?php echo $isActive ? 'badge-success' : 'badge-soft'; ?>"><?php echo $isActive ? 'Selected' : Security::escape($template['badge']); ?></span>
                                </div>
                                <p><?php echo Security::escape($template['tagline']); ?></p>
                            </div>
                            <div class="template-actions">
                                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/templates/edit/<?php echo Security::escape($key); ?>" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:4px;" title="Customize & Edit Template">
                                    <span class="material-symbols-rounded">design_services</span>Edit
                                </a>
                                <button class="btn btn-secondary" type="button" data-preview-template="<?php echo Security::escape($key); ?>" data-preview-base="<?php echo Security::escape((string) ($template['base_template'] ?? $key)); ?>" data-preview-accent="<?php echo Security::escape((string) ($template['accent_color'] ?? '#0f6dff')); ?>">
                                    <span class="material-symbols-rounded">visibility</span>Preview
                                </button>
                                <form class="template-select-form" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/templates/select" method="POST" data-no-ajax>
                                    <?php echo Security::csrfField(); ?>
                                    <input type="hidden" name="receipt_template" value="<?php echo Security::escape($key); ?>">
                                    <button class="btn <?php echo $isActive ? 'template-active-btn' : 'btn-primary'; ?>" type="submit" <?php echo $isActive ? 'disabled' : ''; ?>>
                                        <span class="material-symbols-rounded"><?php echo $isActive ? 'check_circle' : 'task_alt'; ?></span><?php echo $isActive ? 'Active' : 'Select'; ?>
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <aside class="preview-panel">
            <div>
                <h3>Live Preview</h3>
                <p class="page-subtitle">Preview uses sample receipt data and your current business settings.</p>
            </div>
            <div class="preview-picker-wrap">
                <div class="preview-picker">
                    <?php foreach ($templates as $key => $template): ?>
                        <button class="preview-pill <?php echo $selectedTemplate === $key ? 'active' : ''; ?>" type="button" data-preview-template="<?php echo Security::escape($key); ?>" data-preview-base="<?php echo Security::escape((string) ($template['base_template'] ?? $key)); ?>" data-preview-accent="<?php echo Security::escape((string) ($template['accent_color'] ?? '#0f6dff')); ?>">
                            <?php echo Security::escape($template['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="receipt-preview-stage">
                <?php $selectedTemplateMeta = $templates[$selectedTemplate] ?? ReceiptTemplates::resolve($selectedTemplate); ?>
                <div class="receipt-mini template-<?php echo Security::escape((string) ($selectedTemplateMeta['base_template'] ?? $selectedTemplate)); ?>" id="receipt-template-preview" style="--receipt-accent:<?php echo Security::escape((string) ($selectedTemplateMeta['accent_color'] ?? '#0f6dff')); ?>">
                    <div class="top">
                        <div class="invoice-head-left">
                            <div class="invoice-logo">LOGO</div>
                            <div>
                                <div class="title"><?php echo Security::escape($previewData['company']); ?></div>
                                <div class="muted thermal-invoice-number">
                                    <span>Invoice: <?php echo Security::escape($previewData['receipt_id']); ?></span>
                                    <span class="thermal-date"><?php echo Security::escape($previewData['date']); ?></span>
                                </div>
                                <div class="muted company-header-meta">Main Facility</div>
                                <div class="muted company-header-meta">Mob: <?php echo Security::escape($previewData['whatsapp']); ?></div>
                                <div class="muted company-header-meta">Email: sales@example.com</div>
                            </div>
                        </div>
                        <div class="invoice-box">
                            <h4>INVOICE</h4>
                            <div class="muted"><strong>No:</strong> <?php echo Security::escape($previewData['receipt_id']); ?></div>
                            <div class="muted"><strong>Date:</strong> <?php echo Security::escape($previewData['date']); ?></div>
                        </div>
                    </div>
                    <div class="block invoice-info-grid">
                        <div class="row"><span>Cashier :</span><strong><?php echo Security::escape($previewData['cashier']); ?></strong></div>
                        <div class="row"><span>Customer :</span><strong><?php echo Security::escape($previewData['customer']); ?></strong></div>
                        <div class="row"><span>Status :</span><strong>PAID</strong></div>
                    </div>
                    <div class="block">
                        <table>
                            <thead><tr><th>SL.NO</th><th>Description</th><th>Quantity</th><th>Rate</th><th>Amount</th></tr></thead>
                            <tbody>
                                <?php foreach ($previewData['items'] as $idx => $item): ?>
                                    <tr>
                                        <td><?php echo $idx + 1; ?></td>
                                        <td><?php echo Security::escape($item['name']); ?><div class="muted"><?php echo Security::escape($item['unit']); ?></div></td>
                                        <td><?php echo (int) $item['qty']; ?></td>
                                        <td><?php echo App::money($item['price']); ?></td>
                                        <td><?php echo App::money($item['total']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="block">
                        <div class="row"><span>Subtotal</span><strong><?php echo App::money($previewData['subtotal']); ?></strong></div>
                        <div class="row grand"><span>Total</span><strong><?php echo App::money($previewData['total']); ?></strong></div>
                        <div class="row"><span>Paid</span><strong><?php echo App::money($previewData['paid']); ?></strong></div>
                        <div class="row"><span>Remaining</span><strong><?php echo App::money($previewData['remaining']); ?></strong></div>
                    </div>
                    <div class="footer">
                        <div class="footer-main">
                            <div>
                                <strong><?php echo Security::escape($previewData['footer']); ?></strong><br>
                                WhatsApp: <?php echo Security::escape($previewData['whatsapp']); ?><br>
                                Address: <?php echo Security::escape($previewData['address']); ?>
                            </div>
                            <div class="qr"></div>
                        </div>
                        <div class="signature-row">
                            <span class="signature-software"><?php echo App::devAltumReceiptFooterHtml(); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php ob_start(); ?>
<script>
(() => {
    const preview = document.getElementById('receipt-template-preview');
    const buttons = Array.from(document.querySelectorAll('[data-preview-template]'));
    const tabs = Array.from(document.querySelectorAll('[data-template-tab]'));
    const groups = Array.from(document.querySelectorAll('[data-template-group]'));
    if (!preview) return;

    const setPreview = (template) => {
        const source = buttons.find((btn) => btn.dataset.previewTemplate === template);
        const base = source?.dataset.previewBase || template;
        const accent = source?.dataset.previewAccent || '#0f6dff';
        preview.classList.remove('template-classic', 'template-compact', 'template-premium', 'template-invoice', 'template-executive', 'template-emerald', 'template-monogram', 'template-retail_standard');
        preview.classList.add('template-' + base);
        preview.style.setProperty('--receipt-accent', accent);
        buttons.forEach((btn) => btn.classList.toggle('active', btn.dataset.previewTemplate === template && btn.classList.contains('preview-pill')));
    };

    buttons.forEach((btn) => btn.addEventListener('click', () => setPreview(btn.dataset.previewTemplate || 'classic')));
    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const group = tab.dataset.templateTab || 'thermal';
            tabs.forEach((item) => item.classList.toggle('active', item === tab));
            groups.forEach((section) => section.classList.toggle('active', section.dataset.templateGroup === group));
            const firstPreview = document.querySelector(`[data-template-group="${group}"] [data-preview-template]`);
            if (firstPreview) setPreview(firstPreview.dataset.previewTemplate || 'classic');
        });
    });
})();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
