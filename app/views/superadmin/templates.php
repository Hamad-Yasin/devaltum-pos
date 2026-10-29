<?php ob_start(); ?>
<?php
$templates = $templates ?? [];
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
$allTemplates = array_map(function ($row) {
    return [
        'id' => (int) ($row['id'] ?? 0),
        'template_key' => ReceiptTemplates::sanitizeKey($row['template_key'] ?? ''),
        'template_name' => (string) ($row['template_name'] ?? 'Template'),
        'template_type' => strtolower((string) ($row['template_type'] ?? 'thermal')),
        'paper_width_mm' => (int) ($row['paper_width_mm'] ?? 80),
        'accent_color' => ReceiptTemplates::sanitizeColor($row['accent_color'] ?? '#0f6dff'),
        'font_family' => (string) ($row['font_family'] ?? 'Segoe UI'),
        'base_template' => ReceiptTemplates::sanitizeBase($row['base_template'] ?? 'classic'),
        'html_block' => (string) ($row['html_block'] ?? ''),
        'layout_notes' => (string) ($row['layout_notes'] ?? ''),
        'status' => (string) ($row['status'] ?? 'active'),
        'is_system' => !empty($row['is_system']),
    ];
}, $templates);
$baseTemplates = array_values(array_filter($allTemplates, fn($template) => !empty($template['is_system'])));
$customTemplates = array_values(array_filter($allTemplates, fn($template) => empty($template['is_system'])));
$thermalTemplates = array_values(array_filter($allTemplates, fn($template) => ($template['template_type'] ?? 'thermal') === 'thermal'));
$documentTemplates = array_values(array_filter($allTemplates, fn($template) => ($template['template_type'] ?? 'thermal') !== 'thermal'));

// DevAltum Footer Branding Settings
$bShowLogo = App::setting('devaltum_credit_show_logo', '1') !== '0';
$bCustomLogo = (string) App::setting('devaltum_custom_logo', '');
$bBrandPrefix = (string) App::setting('devaltum_brand_prefix', 'Software By');
$bBrandName = (string) App::setting('devaltum_brand_name', 'DevAltum');
$bBrandColor = (string) App::setting('devaltum_brand_color', '#0f6dff');
$bPhoneText = (string) App::setting('devaltum_phone_text', '+92 3101648600');
$bFontFamily = (string) App::setting('devaltum_credit_font_family', 'Segoe UI');
$bFontWeight = (string) App::setting('devaltum_credit_font_weight', '800');
$bFontStyle = (string) App::setting('devaltum_credit_font_style', 'normal');
$bFontSize = (float) App::setting('devaltum_credit_font_size', 12);
$bLogoSize = (int) App::setting('devaltum_credit_logo_size', 44);
$bAlignment = (string) App::setting('devaltum_credit_alignment', 'center');
$defaultLogoDataUri = App::devAltumReceiptLogoDataUri();
$activeLogoSrc = ($bCustomLogo !== '') ? $bCustomLogo : $defaultLogoDataUri;

$thermalSampleHtml = <<<'HTML'
<div class="thermal-receipt" style="font-family:'Segoe UI',Arial,sans-serif; width:100%; max-width:300px; margin:0 auto; padding:8px 0; color:#000;">
  <div style="text-align:center; margin-bottom:8px;">
    <h3 style="margin:0 0 2px; font-size:16px; font-weight:800; text-transform:uppercase; letter-spacing:0.3px;">{{company_name}}</h3>
    <div style="font-size:10px; color:#000; line-height:1.3;">{{company_address}}</div>
    <div style="font-size:10.5px; font-weight:700; margin-top:2px;">Phone: {{whatsapp}}</div>
    <div style="border-top:1px dashed #000; margin:6px 0;"></div>
    <div style="font-size:11px; font-weight:800; letter-spacing:0.8px;">*** RETAIL SALE RECEIPT ***</div>
    <div style="border-bottom:1px dashed #000; margin:6px 0;"></div>
  </div>
  <div style="font-size:10.5px; line-height:1.45; margin-bottom:6px;">
    <div style="display:flex; justify-content:space-between;"><span>Inv #: <strong>{{invoice_no}}</strong></span><span>{{date}}</span></div>
    <div style="display:flex; justify-content:space-between;"><span>Cashier: <strong>{{cashier}}</strong></span><span>{{channel}}</span></div>
    <div style="display:flex; justify-content:space-between;"><span>Customer: <strong>{{customer}}</strong></span><span>{{phone}}</span></div>
  </div>
  <table style="width:100%; border-collapse:collapse; font-size:10.5px; border-top:1px solid #000; border-bottom:1px solid #000; margin:6px 0;">
    <thead>
      <tr style="background:#f1f5f9; font-weight:800; border-bottom:1px solid #000;">
        <th style="padding:4px 2px; text-align:left;">Item</th>
        <th style="padding:4px 2px; text-align:center; width:40px;">Qty</th>
        <th style="padding:4px 2px; text-align:right; width:55px;">Price</th>
        <th style="padding:4px 2px; text-align:right; width:65px;">Total</th>
      </tr>
    </thead>
    <tbody>
      {{items}}
    </tbody>
  </table>
  <div style="font-size:11px; line-height:1.45; margin:6px 0;">
    <div style="display:flex; justify-content:space-between;"><span>Subtotal:</span><strong>{{subtotal}}</strong></div>
    <div style="display:flex; justify-content:space-between;"><span>Discount:</span><strong>{{discount}}</strong></div>
    <div style="display:flex; justify-content:space-between; border:1.5px solid #000; padding:4px 6px; margin:5px 0; font-size:12.5px; font-weight:800; background:#fff;">
      <span>NET PAYABLE:</span><span>{{total}}</span>
    </div>
    <div style="display:flex; justify-content:space-between;"><span>Cash Paid:</span><span>{{paid}}</span></div>
    <div style="display:flex; justify-content:space-between;"><span>Change Due:</span><span>{{remaining}}</span></div>
  </div>
  <div style="text-align:center; margin:8px 0 4px; font-size:10px; font-weight:700;">
    <div>{{footer}}</div>
  </div>
  <div style="text-align:center; margin-top:8px; padding-top:6px; border-top:1px dashed #000;">
    {{developer_credit}}
  </div>
</div>
HTML;

$documentSampleHtml = <<<'HTML'
<div class="custom-invoice" style="font-family:'Segoe UI',Arial,sans-serif; color:#111827; padding:18px;">
  <div style="display:grid; grid-template-columns:1fr 170px; gap:14px; align-items:start; padding-bottom:14px; border-bottom:2px solid #0f6dff;">
    <div>
      <h2 style="margin:0 0 4px; font-size:22px; color:#0f6dff; font-weight:800;">{{company_name}}</h2>
      <p style="margin:0; font-size:12px; color:#475569; font-weight:600;">{{company_address}} | Mob: {{whatsapp}}</p>
    </div>
    <div style="border:1.5px solid #0f6dff; border-radius:10px; padding:10px; text-align:left; background:#f8fbff;">
      <strong style="display:block; color:#0f6dff; font-size:18px; letter-spacing:2px; font-weight:900;">INVOICE</strong>
      <span style="display:block; font-size:12px; font-weight:800; margin-top:3px;">No: {{invoice_no}}</span>
      <small style="display:block; font-size:10.5px; color:#64748b; font-weight:700;">{{date}}</small>
    </div>
  </div>
  <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px 14px; padding:12px 0; font-size:12px; border-bottom:1px solid #e2e8f0;">
    <div style="display:flex; justify-content:space-between;"><span>Cashier:</span><strong>{{cashier}}</strong></div>
    <div style="display:flex; justify-content:space-between;"><span>Customer:</span><strong>{{customer}}</strong></div>
    <div style="display:flex; justify-content:space-between;"><span>Status:</span><strong>{{status}}</strong></div>
    <div style="display:flex; justify-content:space-between;"><span>Payment:</span><strong>{{payment}}</strong></div>
  </div>
  <table style="width:100%; border-collapse:collapse; margin:12px 0; font-size:12px; border:1px solid #0f6dff;">
    <thead>
      <tr style="background:#0f6dff; color:#fff; font-weight:800;">
        <th style="padding:8px; text-align:left;">Item Description</th>
        <th style="padding:8px; text-align:center; width:60px;">Qty</th>
        <th style="padding:8px; text-align:right; width:80px;">Rate</th>
        <th style="padding:8px; text-align:right; width:90px;">Total</th>
      </tr>
    </thead>
    <tbody>
      {{items}}
    </tbody>
  </table>
  <div style="display:grid; grid-template-columns:1fr 240px; gap:16px; align-items:start; margin-top:10px;">
    <div style="font-size:11.5px; color:#475569; padding-top:6px;">
      <p style="margin:0 0 6px;">{{footer}}</p>
      <div>{{qr}}</div>
    </div>
    <div style="display:grid; gap:5px; font-size:12px; border:1px solid #e2e8f0; border-radius:8px; padding:10px; background:#f8fafc;">
      <div style="display:flex; justify-content:space-between;"><span>Subtotal:</span><strong>{{subtotal}}</strong></div>
      <div style="display:flex; justify-content:space-between;"><span>Discount:</span><strong>{{discount}}</strong></div>
      <div style="display:flex; justify-content:space-between; font-size:14px; font-weight:900; color:#0f6dff; padding-top:6px; border-top:1.5px solid #0f6dff;">
        <span>Grand Total:</span><span>{{total}}</span>
      </div>
      <div style="display:flex; justify-content:space-between;"><span>Paid:</span><strong>{{paid}}</strong></div>
      <div style="display:flex; justify-content:space-between;"><span>Balance Due:</span><strong>{{remaining}}</strong></div>
    </div>
  </div>
  <div style="border-top:1px dashed #cbd5e1; margin-top:14px; padding-top:6px; text-align:center;">
    {{developer_credit}}
  </div>
</div>
HTML;
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Fira+Code:wght@500;700&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700;800&family=Montserrat:wght@500;600;700;800;900&family=Oswald:wght@500;600;700&family=Outfit:wght@500;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,600&family=Poppins:wght@500;600;700;800;900&family=Roboto:wght@400;500;700;900&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

<style>
.sa-template-shell { display:grid; gap:14px; }
.sa-template-hero {
    display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap;
    padding:14px 18px; border:1px solid rgba(15,109,255,.16); border-radius:18px;
    background:linear-gradient(135deg,#fff,#eff6ff 60%,#ecfdf5);
    box-shadow:0 10px 28px rgba(15,23,42,.05); position:relative; overflow:hidden;
}
.sa-template-hero::before { content:''; position:absolute; inset:0 0 auto; height:3px; background:linear-gradient(90deg,#0f6dff,#10b981,#f59e0b); }
.sa-hero-title-group h1 { margin:0; font-size:22px; font-weight:900; letter-spacing:-0.2px; color:#0f172a; display:flex; align-items:center; gap:8px; }
.sa-hero-title-group p { margin:2px 0 0; color:#64748b; font-size:12.5px; font-weight:600; }
.sa-template-stats { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
.sa-template-pill { display:inline-flex; align-items:center; gap:6px; height:32px; padding:0 10px; border-radius:999px; background:#fff; border:1px solid rgba(148,163,184,.22); font-size:11.5px; font-weight:800; color:#0f172a; box-shadow:0 2px 6px rgba(0,0,0,.03); }
.sa-template-pill .material-symbols-rounded { font-size:16px; color:#0f6dff; }

/* Studio Tabs */
.sa-studio-tabs { display:flex; gap:6px; padding:5px; border:1px solid rgba(148,163,184,.2); border-radius:14px; background:#fff; box-shadow:0 4px 14px rgba(15,23,42,.03); flex-wrap:wrap; }
.sa-studio-tab { border:none; border-radius:10px; height:38px; padding:0 14px; background:transparent; color:#64748b; font-size:12.5px; font-weight:800; cursor:pointer; display:flex; align-items:center; gap:6px; transition:all .18s ease; }
.sa-studio-tab .material-symbols-rounded { font-size:18px; }
.sa-studio-tab:hover { background:#f1f5f9; color:#0f172a; }
.sa-studio-tab.active { background:linear-gradient(135deg,#0f6dff,#10b981); color:#fff; box-shadow:0 4px 12px rgba(15,109,255,.22); }
.sa-studio-section { display:none; }
.sa-studio-section.active { display:grid; gap:14px; }

/* Studio Builder Layout */
.sa-template-studio { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(320px,.85fr); gap:14px; align-items:start; }
.sa-template-panel { border:1px solid rgba(148,163,184,.2); border-radius:18px; background:#fff; box-shadow:0 8px 24px rgba(15,23,42,.04); padding:16px; }
.sa-panel-head { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:12px; }
.sa-panel-head h2 { margin:0; font-size:16px; font-weight:800; color:#0f172a; }
.sa-panel-head p { margin:2px 0 0; color:#64748b; font-size:11.5px; font-weight:600; }

/* Minimal & Pro-Level Builder Controls Bar */
.sa-builder-controls { 
    display:grid; 
    grid-template-columns:repeat(3, minmax(0, 1fr)); 
    gap:12px 14px; 
    padding:14px 16px; 
    background:#ffffff; 
    border:1px solid #e2e8f0; 
    border-radius:14px; 
    margin-bottom:14px; 
    box-shadow:0 1px 3px rgba(15,23,42,0.03); 
}
@media (max-width:960px) {
    .sa-builder-controls { grid-template-columns:1fr 1fr; }
}
@media (max-width:600px) {
    .sa-builder-controls { grid-template-columns:1fr; }
}
.sa-control-group { display:flex; flex-direction:column; gap:5px; min-width:0; }
.sa-control-label { 
    font-size:10.5px; 
    font-weight:700; 
    color:#475569; 
    text-transform:uppercase; 
    letter-spacing:0.4px; 
    display:flex; 
    align-items:center; 
    gap:4px; 
    margin:0; 
    line-height:1.2; 
}
.sa-control-label .req { color:#ef4444; font-weight:800; }
.sa-control-input, .sa-control-select { 
    height:36px; 
    border:1px solid #cbd5e1; 
    border-radius:8px; 
    padding:0 10px; 
    font-size:12.5px; 
    font-weight:600; 
    color:#0f172a; 
    background-color:#ffffff; 
    outline:none; 
    width:100%; 
    box-sizing:border-box; 
    transition:all .15s ease; 
}
.sa-control-input::placeholder { color:#94a3b8; font-weight:500; }
.sa-control-input:focus, .sa-control-select:focus { 
    border-color:#0f6dff; 
    box-shadow:0 0 0 3px rgba(15,109,255,.12); 
    background-color:#ffffff; 
}
.sa-control-select { 
    cursor:pointer; 
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); 
    background-repeat:no-repeat; 
    background-position:right 10px center; 
    padding-right:28px; 
    appearance:none; 
    -webkit-appearance:none; 
}

/* ==========================================================
   ULTRA-PREMIUM LUXURY INPUTS & SELECTS DESIGN SYSTEM
   ========================================================== */
.sa-field-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}
.sa-field-label {
    font-size: 11px;
    font-weight: 800;
    color: #475569;
    letter-spacing: 0.35px;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0;
    line-height: 1.2;
}
.sa-field-label .req {
    color: #ef4444;
    font-weight: 900;
    margin-left: 2px;
}
.sa-field-label .sa-field-hint {
    font-size: 10px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: none;
    letter-spacing: normal;
}

/* Luxury Input Wrapper Container */
.sa-luxury-box {
    position: relative;
    display: flex;
    align-items: center;
    background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
    border: 1.5px solid #cbd5e1;
    border-radius: 11px;
    height: 42px;
    padding: 0 12px;
    gap: 10px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), inset 0 1px 1px rgba(255, 255, 255, 0.9);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-sizing: border-box;
    width: 100%;
}
.sa-luxury-box:hover {
    border-color: #94a3b8;
    background: #ffffff;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
}
.sa-luxury-box:focus-within {
    border-color: #0f6dff;
    background: #ffffff;
    box-shadow: 0 0 0 3.5px rgba(15, 109, 255, 0.15), 0 3px 10px rgba(15, 109, 255, 0.08);
}
.sa-luxury-box .sa-box-icon {
    font-size: 19px;
    color: #94a3b8;
    flex-shrink: 0;
    transition: color 0.2s ease, transform 0.2s ease;
}
.sa-luxury-box:focus-within .sa-box-icon {
    color: #0f6dff;
    transform: scale(1.08);
}

/* Interior Input */
.sa-luxury-input {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    padding: 0;
    min-width: 0;
    height: 100%;
}
.sa-luxury-input::placeholder {
    color: #94a3b8;
    font-weight: 500;
}

/* Interior Select */
.sa-luxury-select {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    padding: 0;
    min-width: 0;
    height: 100%;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
}
.sa-luxury-select optgroup {
    font-weight: 800;
    color: #0f172a;
    background: #f8fafc;
    font-size: 11.5px;
    padding: 6px 4px;
}
.sa-luxury-select option {
    font-weight: 600;
    color: #1e293b;
    background: #ffffff;
    font-size: 12.5px;
    padding: 6px 10px;
}
.sa-select-chevron {
    font-size: 20px;
    color: #64748b;
    pointer-events: none;
    flex-shrink: 0;
    transition: transform 0.2s ease, color 0.2s ease;
}
.sa-luxury-box:focus-within .sa-select-chevron {
    color: #0f6dff;
    transform: rotate(180deg);
}

/* Luxury Color Picker Box */
.sa-color-control-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}
.sa-color-preview-disc {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    border: 1.5px solid #cbd5e1;
    cursor: pointer;
    overflow: hidden;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    flex-shrink: 0;
    transition: all 0.2s ease;
    padding: 2px;
    background: #fff;
    box-sizing: border-box;
}
.sa-color-preview-disc:hover {
    transform: scale(1.05);
    border-color: #0f6dff;
}
.sa-color-preview-disc input[type="color"] {
    position: absolute;
    inset: -10px;
    width: 200%;
    height: 200%;
    cursor: pointer;
    opacity: 0;
}
.sa-color-preview-inner {
    width: 100%;
    height: 100%;
    border-radius: 7px;
    transition: background-color 0.15s ease;
}

/* Minimal Segmented Type Selector */
.sa-type-toggle { 
    display:grid; 
    grid-template-columns:1fr 1fr; 
    background:#f1f5f9; 
    padding:3px; 
    border-radius:8px; 
    height:36px; 
    border:1px solid #e2e8f0; 
    box-sizing:border-box; 
    gap:3px; 
}
.sa-type-btn { 
    border:none; 
    background:transparent; 
    border-radius:6px; 
    font-size:11.5px; 
    font-weight:700; 
    color:#64748b; 
    cursor:pointer; 
    display:inline-flex; 
    align-items:center; 
    justify-content:center; 
    gap:5px; 
    padding:0 8px; 
    transition:all .15s ease; 
    white-space:nowrap; 
    user-select:none; 
}
.sa-type-btn:hover:not(.active) { color:#0f172a; }
.sa-type-btn.active { 
    background:#ffffff; 
    box-shadow:0 1px 3px rgba(15,23,42,.1); 
}
.sa-type-btn.active.is-thermal { color:#0f6dff; font-weight:800; }
.sa-type-btn.active.is-document { color:#059669; font-weight:800; }

/* Code Editor Toolbar */
.sa-code-toolbar { display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; padding:8px 10px; border:1px solid #cbd5e1; border-bottom:none; border-radius:12px 12px 0 0; background:#f1f5f9; }
.sa-toolbar-left, .sa-toolbar-right { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
.sa-chip-btn { border:1px solid #cbd5e1; background:#fff; color:#334155; font-size:11px; font-weight:800; padding:4px 9px; border-radius:7px; cursor:pointer; display:inline-flex; align-items:center; gap:4px; transition:all .15s ease; }
.sa-chip-btn:hover { background:#e2e8f0; color:#0f172a; }
.sa-chip-btn.active { background:#0f172a; color:#fff; border-color:#0f172a; }

.sa-code-editor {
    width:100%; min-height:380px; border:1px solid #cbd5e1; border-radius:0 0 12px 12px;
    padding:12px; resize:vertical; background:#0b1324; color:#dbeafe;
    font-family:'Cascadia Code','Courier New',monospace; font-size:12px; line-height:1.55; outline:none; box-sizing:border-box;
}
.sa-code-editor:focus { border-color:#0f6dff; box-shadow:0 0 0 3px rgba(15,109,255,.15); }

/* Quick Token Inserters */
.sa-token-row { display:flex; gap:5px; flex-wrap:wrap; margin-top:10px; align-items:center; }
.sa-token-label { font-size:10.5px; font-weight:800; color:#64748b; }
.sa-token-chip { padding:3px 7px; border-radius:6px; background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:800; cursor:pointer; border:1px solid #bfdbfe; font-family:monospace; }
.sa-token-chip:hover { background:#dbeafe; color:#1e40af; }

.sa-studio-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:12px; }
.sa-studio-actions .btn { height:36px; padding:0 14px; font-size:12.5px; font-weight:800; border-radius:10px; }

/* Live Preview Stage */
.sa-preview-stage { border:1px solid rgba(148,163,184,.22); border-radius:14px; background:radial-gradient(circle at 50% 0%,#f8fafc,#e2e8f0); padding:16px 10px; min-height:480px; overflow:auto; display:flex; justify-content:center; align-items:flex-start; }
.sa-live-paper { background:#fff; border:1px solid #cbd5e1; box-shadow:0 14px 34px rgba(15,23,42,.12); border-radius:6px; overflow:hidden; transition:max-width .2s ease; box-sizing:border-box; }
.sa-live-paper.is-thermal-preview { width:100%; max-width:320px; border-radius:4px; }
.sa-live-paper.is-thermal-58-preview { width:100%; max-width:260px; border-radius:4px; }
.sa-live-paper.is-document-preview { width:100%; max-width:520px; border-radius:10px; }

/* Template Cards Grid */
.sa-template-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:12px; }
.sa-template-card { border:1px solid rgba(148,163,184,.22); border-radius:16px; background:#fff; box-shadow:0 4px 14px rgba(15,23,42,.04); overflow:hidden; display:grid; transition:all .18s ease; }
.sa-template-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(15,23,42,.08); border-color:#93c5fd; }
.sa-card-preview { height:110px; display:grid; place-items:center; padding:10px; background:linear-gradient(135deg,#f8fafc,#eff6ff); border-bottom:1px solid #e2e8f0; position:relative; }
.sa-mini-receipt { width:130px; height:85px; background:#fff; border:1.5px solid var(--receipt-accent,#0f6dff); box-shadow:0 6px 14px rgba(15,23,42,.08); padding:8px; font-size:8.5px; color:#0f172a; font-weight:900; overflow:hidden; border-radius:3px; }
.sa-mini-receipt .mini-head { display:flex; justify-content:space-between; gap:4px; margin-bottom:6px; }
.sa-mini-receipt .mini-box { border:1px solid var(--receipt-accent,#0f6dff); padding:1px 3px; font-size:7.5px; border-radius:2px; color:var(--receipt-accent,#0f6dff); }
.sa-mini-receipt .mini-line { height:4px; background:#e2e8f0; border-radius:999px; margin:4px 0; }
.sa-mini-receipt .mini-line.short { width:60%; }
.sa-template-card-body { padding:12px; display:grid; gap:8px; }
.sa-template-card-title { display:flex; justify-content:space-between; gap:6px; align-items:flex-start; }
.sa-template-card h3 { margin:0; font-size:14px; font-weight:800; line-height:1.2; color:#0f172a; }
.sa-template-card p { margin:0; color:#64748b; font-size:11px; font-weight:600; line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.sa-template-status { display:inline-flex; border-radius:999px; padding:3px 7px; font-size:10px; font-weight:800; background:#e2e8f0; color:#334155; white-space:nowrap; }
.sa-template-status.active { background:#dcfce7; color:#166534; }
.sa-template-status.draft { background:#fef3c7; color:#92400e; }
.sa-template-status.archived { background:#fee2e2; color:#991b1b; }
.sa-card-actions { display:flex; justify-content:space-between; align-items:center; gap:6px; margin-top:4px; border-top:1px solid #f1f5f9; padding-top:8px; }
.sa-card-actions-left, .sa-card-actions-right { display:flex; gap:4px; align-items:center; }
.sa-card-actions .btn { height:28px; padding:0 8px; border-radius:7px; font-size:11px; font-weight:800; display:inline-flex; align-items:center; gap:3px; }
.sa-card-actions .btn .material-symbols-rounded { font-size:14px; }
.sa-danger-btn { border:1px solid #fecaca !important; background:#fff5f5 !important; color:#dc2626 !important; }
.sa-danger-btn:hover { background:#fee2e2 !important; color:#991b1b !important; }

/* Modals */
.sa-template-modal.modal { position:fixed; inset:0; z-index:260; display:none; align-items:center; justify-content:center; padding:16px; background:rgba(15,23,42,.55); backdrop-filter:blur(6px); }
.sa-template-modal .modal-content { width:min(760px,100%); max-height:min(88vh,720px); overflow:auto; border:1px solid rgba(148,163,184,.24); border-radius:18px; background:#fff; box-shadow:0 24px 60px rgba(15,23,42,.25); padding:16px; }
.sa-template-modal .modal-header { position:sticky; top:-16px; z-index:2; background:#fff; padding:0 0 10px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
.sa-template-modal .modal-header h3 { margin:0; font-size:16px; font-weight:800; }
.sa-confirm-card { width:min(420px,100%) !important; text-align:center; padding:20px !important; }
.sa-confirm-icon { width:46px; height:46px; border-radius:12px; display:grid; place-items:center; background:#fee2e2; color:#dc2626; margin:0 auto 10px; }
.sa-confirm-actions { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:14px; }

/* Pro-Level Branding Studio Styles */
.sa-branding-split { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(340px, .85fr); gap: 16px; align-items: start; }
.sa-pro-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    transition: all 0.2s ease;
}
.sa-pro-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.sa-card-header-clean {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}
.sa-card-title-group {
    display: flex;
    align-items: center;
    gap: 8px;
}
.sa-card-icon-badge {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: #eff6ff;
    color: #0f6dff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.sa-card-title-group h3 {
    margin: 0;
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.1px;
}
.sa-card-title-group p {
    margin: 1px 0 0;
    font-size: 11px;
    color: #64748b;
    font-weight: 500;
}
.sa-dropzone {
    border: 2px dashed #93c5fd;
    border-radius: 12px;
    background: #f8fbff;
    padding: 20px 14px;
    text-align: center;
    cursor: pointer;
    transition: all .2s ease;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.sa-dropzone:hover, .sa-dropzone.dragover {
    border-color: #0f6dff;
    background: #eff6ff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 109, 255, 0.08);
}
.sa-dropzone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; z-index: 2; }
.sa-logo-checkerboard {
    background-color: #ffffff;
    background-image: linear-gradient(45deg, #f1f5f9 25%, transparent 25%), 
                      linear-gradient(-45deg, #f1f5f9 25%, transparent 25%), 
                      linear-gradient(45deg, transparent 75%, #f1f5f9 75%), 
                      linear-gradient(-45deg, transparent 75%, #f1f5f9 75%);
    background-size: 12px 12px;
    background-position: 0 0, 0 6px, 6px -6px, -6px 0px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 140px;
    min-height: 100px;
}
.sa-color-swatches {
    display: flex;
    gap: 6px;
    align-items: center;
    margin-top: 6px;
}
.sa-color-swatch-dot {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid #ffffff;
    box-shadow: 0 0 0 1px #cbd5e1;
    transition: all 0.15s ease;
}
.sa-color-swatch-dot:hover {
    transform: scale(1.2);
    box-shadow: 0 0 0 2px #0f6dff;
}
.sa-color-swatch-dot.active {
    box-shadow: 0 0 0 2.5px #0f6dff;
    transform: scale(1.15);
}
.sa-slider-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 4px;
}
.sa-pro-slider {
    -webkit-appearance: none;
    appearance: none;
    height: 6px;
    border-radius: 999px;
    background: #e2e8f0;
    outline: none;
    flex: 1;
    cursor: pointer;
    transition: background 0.2s;
}
.sa-pro-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #0f6dff;
    cursor: pointer;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 6px rgba(15, 109, 255, 0.4);
    transition: transform 0.1s, box-shadow 0.1s;
}
.sa-pro-slider::-webkit-slider-thumb:hover {
    transform: scale(1.15);
    box-shadow: 0 3px 8px rgba(15, 109, 255, 0.6);
}
.sa-slider-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 48px;
    height: 26px;
    padding: 0 8px;
    border-radius: 6px;
    background: #eff6ff;
    color: #0f6dff;
    font-size: 11px;
    font-weight: 800;
    border: 1px solid #bfdbfe;
    font-family: monospace;
}
.sa-pill-segmented {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
    background: #f1f5f9;
    padding: 3px;
    border-radius: 8px;
    height: 36px;
    border: 1px solid #e2e8f0;
    box-sizing: border-box;
    gap: 3px;
}
.sa-pill-btn {
    border: none;
    background: transparent;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 0 6px;
    transition: all 0.15s ease;
    white-space: nowrap;
    user-select: none;
}
.sa-pill-btn:hover:not(.active) { color: #0f172a; }
.sa-pill-btn.active {
    background: #ffffff;
    color: #0f6dff;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1);
    font-weight: 800;
}

/* Realistic Thermal Receipt Paper Simulation */
.sa-thermal-roll-strip {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    position: relative;
    padding: 22px 14px;
    margin: 0 auto;
    width: 100%;
    max-width: 320px;
    box-sizing: border-box;
    font-smooth: never;
    -webkit-font-smoothing: none;
    filter: grayscale(100%) contrast(140%);
}
.sa-thermal-tear-top {
    position: absolute;
    top: -5px;
    left: 0;
    right: 0;
    height: 6px;
    background-image: radial-gradient(circle, transparent, transparent 50%, #fff 50%, #fff 100%);
    background-size: 10px 10px;
}
.sa-thermal-tear-bottom {
    position: absolute;
    bottom: -5px;
    left: 0;
    right: 0;
    height: 6px;
    background-image: radial-gradient(circle, transparent, transparent 50%, #fff 50%, #fff 100%);
    background-size: 10px 10px;
}
@media (max-width:640px) {
    .sa-template-hero { flex-direction:column; align-items:flex-start; }
    .sa-studio-tabs { flex-direction:column; }
    .sa-builder-controls { grid-template-columns:1fr; }
    .sa-template-grid { grid-template-columns:1fr; }
}
</style>

<div class="sa-template-shell">
    <!-- Compact Minimal Hero -->
    <section class="sa-template-hero">
        <div class="sa-hero-title-group">
            <h1><span class="material-symbols-rounded" style="font-size:24px; color:#0f6dff;">receipt_long</span> Templates Studio</h1>
            <p>Design, customize & manage Thermal POS Roll (80mm/58mm) and Document Invoice templates.</p>
        </div>
        <div class="sa-template-stats">
            <span class="sa-template-pill"><span class="material-symbols-rounded">layers</span><?php echo count($allTemplates); ?> Total</span>
            <span class="sa-template-pill"><span class="material-symbols-rounded">print</span><?php echo count($thermalTemplates); ?> Thermal</span>
            <span class="sa-template-pill"><span class="material-symbols-rounded">article</span><?php echo count($documentTemplates); ?> Document</span>
            <span class="sa-template-pill"><span class="material-symbols-rounded">verified</span>Footer Branding</span>
        </div>
    </section>

    <!-- Compact Studio Tabs -->
    <div class="sa-studio-tabs" role="tablist">
        <button class="sa-studio-tab active" type="button" data-studio-tab="builder"><span class="material-symbols-rounded">edit_document</span>HTML Template Builder</button>
        <button class="sa-studio-tab" type="button" data-studio-tab="thermal"><span class="material-symbols-rounded">print</span>Thermal POS Templates (<?php echo count($thermalTemplates); ?>)</button>
        <button class="sa-studio-tab" type="button" data-studio-tab="document"><span class="material-symbols-rounded">description</span>Document Invoices (<?php echo count($documentTemplates); ?>)</button>
        <button class="sa-studio-tab" type="button" data-studio-tab="branding"><span class="material-symbols-rounded">verified</span>DevAltum Branding Footer</button>
    </div>

    <!-- TAB 1: HTML BUILDER -->
    <section class="sa-studio-section active" data-studio-section="builder">
        <div class="sa-template-studio">
            <div class="sa-template-panel">
                <div class="sa-panel-head">
                    <div>
                        <h2>Create New Template</h2>
                        <p>Configure template name, type (Thermal vs Document), and paste custom HTML code with live dummy data.</p>
                    </div>
                    <span class="sa-template-status active" data-active-type-badge>Thermal POS</span>
                </div>

                <form action="<?php echo $baseUrl; ?>/superadmin/saveTemplate" method="POST" data-no-ajax data-template-builder>
                    <?php echo Security::csrfField(); ?>
                    <input type="hidden" name="template_type" id="form_template_type" value="thermal">
                    <input type="hidden" name="accent_color" value="#0f6dff">
                    <input type="hidden" name="font_family" value="Segoe UI">
                    <input type="hidden" name="layout_notes" value="Custom HTML receipt template created from Super Admin studio.">

                    <!-- Control Settings Bar -->
                    <div class="sa-builder-controls">
                        <div class="sa-control-group">
                            <label class="sa-control-label" for="builder_name">Template Name <span class="req">*</span></label>
                            <div class="sa-luxury-box">
                                <span class="material-symbols-rounded sa-box-icon">badge</span>
                                <input class="sa-luxury-input" type="text" id="builder_name" name="template_name" placeholder="e.g. Express 80mm Thermal Slip" value="Custom Thermal Slip" required>
                            </div>
                        </div>
                        <div class="sa-control-group">
                            <label class="sa-control-label" for="builder_key">Template Key (Slug)</label>
                            <div class="sa-luxury-box">
                                <span class="material-symbols-rounded sa-box-icon">tag</span>
                                <input class="sa-luxury-input" type="text" id="builder_key" name="template_key" placeholder="auto-generated" value="">
                            </div>
                        </div>
                        <div class="sa-control-group">
                            <label class="sa-control-label">Type (Thermal / Doc)</label>
                            <div class="sa-type-toggle">
                                <button type="button" class="sa-type-btn active is-thermal" data-type-select="thermal"><span class="material-symbols-rounded" style="font-size:15px;">print</span> Thermal</button>
                                <button type="button" class="sa-type-btn is-document" data-type-select="document"><span class="material-symbols-rounded" style="font-size:15px;">article</span> Document</button>
                            </div>
                        </div>
                        <div class="sa-control-group">
                            <label class="sa-control-label" for="builder_width">Paper Width</label>
                            <div class="sa-luxury-box">
                                <span class="material-symbols-rounded sa-box-icon">straighten</span>
                                <select class="sa-luxury-select" id="builder_width" name="paper_width_mm">
                                    <option value="80" selected>80mm POS (Standard)</option>
                                    <option value="58">58mm POS (Compact)</option>
                                    <option value="148">148mm (A5 Invoice)</option>
                                    <option value="210">210mm (A4 Document)</option>
                                </select>
                                <span class="material-symbols-rounded sa-select-chevron">expand_more</span>
                            </div>
                        </div>
                        <div class="sa-control-group">
                            <label class="sa-control-label" for="builder_base">Base Fallback Engine</label>
                            <div class="sa-luxury-box">
                                <span class="material-symbols-rounded sa-box-icon">layers</span>
                                <select class="sa-luxury-select" id="builder_base" name="base_template">
                                    <option value="classic" selected>Classic Receipt</option>
                                    <option value="retail_standard">Master Retail Standard</option>
                                    <option value="compact">Compact Thermal (58mm)</option>
                                    <option value="thermal_mono">Monospace Thermal</option>
                                    <option value="premium">Premium Soft</option>
                                    <option value="invoice">Invoice Pro (Document)</option>
                                    <option value="executive">Executive Ledger (Document)</option>
                                    <option value="emerald">Emerald Statement (Document)</option>
                                    <option value="monogram">Monogram Bill (Document)</option>
                                </select>
                                <span class="material-symbols-rounded sa-select-chevron">expand_more</span>
                            </div>
                        </div>
                        <div class="sa-control-group">
                            <label class="sa-control-label" for="builder_status">Status</label>
                            <div class="sa-luxury-box">
                                <span class="material-symbols-rounded sa-box-icon">toggle_on</span>
                                <select class="sa-luxury-select" id="builder_status" name="status">
                                    <option value="active" selected>Active (Live in Stores)</option>
                                    <option value="draft">Draft (Testing)</option>
                                </select>
                                <span class="material-symbols-rounded sa-select-chevron">expand_more</span>
                            </div>
                        </div>
                    </div>

                    <!-- Code Editor Toolbar -->
                    <div class="sa-code-toolbar">
                        <div class="sa-toolbar-left">
                            <button type="button" class="sa-chip-btn active" data-load-sample="thermal"><span class="material-symbols-rounded" style="font-size:14px; color:#0f6dff;">print</span>Load 80mm Thermal Code</button>
                            <button type="button" class="sa-chip-btn" data-load-sample="document"><span class="material-symbols-rounded" style="font-size:14px; color:#059669;">article</span>Load Document Invoice Code</button>
                            <button type="button" class="sa-chip-btn" data-clear-editor><span class="material-symbols-rounded" style="font-size:14px;">delete_sweep</span>Clear</button>
                        </div>
                        <div class="sa-toolbar-right">
                            <span style="font-size:11px; font-weight:800; color:#64748b;">Live Auto-Sync</span>
                        </div>
                    </div>

                    <textarea class="sa-code-editor" name="html_block" data-html-editor spellcheck="false"><?php echo Security::escape($thermalSampleHtml); ?></textarea>

                    <div class="sa-token-row">
                        <span class="sa-token-label">Insert Token:</span>
                        <span class="sa-token-chip" data-insert-token="{{company_name}}">{{company_name}}</span>
                        <span class="sa-token-chip" data-insert-token="{{invoice_no}}">{{invoice_no}}</span>
                        <span class="sa-token-chip" data-insert-token="{{date}}">{{date}}</span>
                        <span class="sa-token-chip" data-insert-token="{{items}}">{{items}}</span>
                        <span class="sa-token-chip" data-insert-token="{{total}}">{{total}}</span>
                        <span class="sa-token-chip" data-insert-token="{{paid}}">{{paid}}</span>
                        <span class="sa-token-chip" data-insert-token="{{remaining}}">{{remaining}}</span>
                        <span class="sa-token-chip" data-insert-token="{{qr}}">{{qr}}</span>
                        <span class="sa-token-chip" data-insert-token="{{developer_credit}}">{{developer_credit}}</span>
                    </div>

                    <div class="sa-studio-actions">
                        <button class="btn btn-secondary btn-sm" type="button" data-reset-sample><span class="material-symbols-rounded">restart_alt</span>Reset</button>
                        <button class="btn btn-primary btn-sm" type="submit"><span class="material-symbols-rounded">save</span>Save & Create Template</button>
                    </div>
                </form>
            </div>

            <aside class="sa-template-panel">
                <div class="sa-panel-head">
                    <div>
                        <h2>Live Preview</h2>
                        <p id="preview_subtitle">80mm Thermal POS preview with realistic store invoice data.</p>
                    </div>
                    <span class="sa-template-pill" id="preview_dimension_badge">80mm</span>
                </div>
                <div class="sa-preview-stage">
                    <div class="sa-live-paper is-thermal-preview" data-html-preview></div>
                </div>
            </aside>
        </div>
    </section>

    <!-- TAB 2: THERMAL POS TEMPLATES -->
    <section class="sa-studio-section" data-studio-section="thermal">
        <div class="sa-template-panel">
            <div class="sa-panel-head">
                <div>
                    <h2>Thermal POS Receipt Templates</h2>
                    <p>Compact, high-speed thermal roll layouts (80mm / 58mm) optimized for instant POS checkout.</p>
                </div>
                <span class="sa-template-pill"><span class="material-symbols-rounded">print</span><?php echo count($thermalTemplates); ?> Templates</span>
            </div>
            <div class="sa-template-grid">
                <?php foreach ($thermalTemplates as $template): ?>
                    <article class="sa-template-card">
                        <div class="sa-card-preview">
                            <div class="sa-mini-receipt" style="--receipt-accent:<?php echo Security::escape($template['accent_color']); ?>">
                                <div class="mini-head"><strong><?php echo Security::escape($template['template_name']); ?></strong><span class="mini-box">POS</span></div>
                                <div class="mini-line"></div><div class="mini-line short"></div><div class="mini-line"></div><div class="mini-line short"></div>
                            </div>
                        </div>
                        <div class="sa-template-card-body">
                            <div class="sa-template-card-title">
                                <h3><?php echo Security::escape($template['template_name']); ?></h3>
                                <span class="sa-template-status <?php echo Security::escape($template['status']); ?>"><?php echo Security::escape(ucfirst($template['status'])); ?></span>
                            </div>
                            <p><?php echo Security::escape($template['html_block'] !== '' ? 'Custom HTML thermal template.' : ($template['layout_notes'] ?: 'Standard thermal roll template.')); ?></p>
                            <div class="sa-card-actions">
                                <div class="sa-card-actions-left">
                                    <span class="badge badge-soft"><?php echo !empty($template['is_system']) ? 'System' : 'HTML'; ?></span>
                                    <span class="badge badge-soft"><?php echo (int) $template['paper_width_mm']; ?>mm</span>
                                </div>
                                <div class="sa-card-actions-right">
                                    <button class="btn btn-secondary btn-sm" type="button" data-open-modal="previewTemplate-<?php echo Security::escape($template['template_key']); ?>"><span class="material-symbols-rounded">visibility</span>Preview</button>
                                    <a href="<?php echo $baseUrl; ?>/superadmin/editTemplate/<?php echo (int) ($template['id'] ?: $template['template_key']); ?>" class="btn btn-primary btn-sm"><span class="material-symbols-rounded">edit</span>Edit</a>
                                    <button class="btn btn-secondary btn-sm sa-danger-btn" type="button" data-open-modal="deleteTemplate-<?php echo Security::escape($template['template_key']); ?>"><span class="material-symbols-rounded">delete</span></button>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- TAB 3: DOCUMENT INVOICE TEMPLATES -->
    <section class="sa-studio-section" data-studio-section="document">
        <div class="sa-template-panel">
            <div class="sa-panel-head">
                <div>
                    <h2>Document & A5/A4 Invoice Templates</h2>
                    <p>Formal invoice layouts for A5 / A4 standard document printing, PDF export, and WhatsApp sharing.</p>
                </div>
                <span class="sa-template-pill"><span class="material-symbols-rounded">description</span><?php echo count($documentTemplates); ?> Templates</span>
            </div>
            <div class="sa-template-grid">
                <?php foreach ($documentTemplates as $template): ?>
                    <article class="sa-template-card">
                        <div class="sa-card-preview">
                            <div class="sa-mini-receipt" style="--receipt-accent:<?php echo Security::escape($template['accent_color']); ?>">
                                <div class="mini-head"><strong><?php echo Security::escape($template['template_name']); ?></strong><span class="mini-box">DOC</span></div>
                                <div class="mini-line"></div><div class="mini-line short"></div><div class="mini-line"></div><div class="mini-line short"></div>
                            </div>
                        </div>
                        <div class="sa-template-card-body">
                            <div class="sa-template-card-title">
                                <h3><?php echo Security::escape($template['template_name']); ?></h3>
                                <span class="sa-template-status <?php echo Security::escape($template['status']); ?>"><?php echo Security::escape(ucfirst($template['status'])); ?></span>
                            </div>
                            <p><?php echo Security::escape($template['html_block'] !== '' ? 'Custom HTML document template.' : ($template['layout_notes'] ?: 'System document template.')); ?></p>
                            <div class="sa-card-actions">
                                <div class="sa-card-actions-left">
                                    <span class="badge badge-soft"><?php echo !empty($template['is_system']) ? 'System' : 'HTML'; ?></span>
                                    <span class="badge badge-soft"><?php echo (int) $template['paper_width_mm']; ?>mm</span>
                                </div>
                                <div class="sa-card-actions-right">
                                    <button class="btn btn-secondary btn-sm" type="button" data-open-modal="previewTemplate-<?php echo Security::escape($template['template_key']); ?>"><span class="material-symbols-rounded">visibility</span>Preview</button>
                                    <a href="<?php echo $baseUrl; ?>/superadmin/editTemplate/<?php echo (int) ($template['id'] ?: $template['template_key']); ?>" class="btn btn-primary btn-sm"><span class="material-symbols-rounded">edit</span>Edit</a>
                                    <button class="btn btn-secondary btn-sm sa-danger-btn" type="button" data-open-modal="deleteTemplate-<?php echo Security::escape($template['template_key']); ?>"><span class="material-symbols-rounded">delete</span></button>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- TAB 4: DEVALTUM FOOTER BRANDING CUSTOMIZER -->
    <section class="sa-studio-section" data-studio-section="branding">
        <form action="<?php echo $baseUrl; ?>/superadmin/saveBranding" method="POST" enctype="multipart/form-data" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <input type="hidden" name="devaltum_custom_logo" id="branding_custom_logo_val" value="<?php echo Security::escape($bCustomLogo); ?>">
            <input type="hidden" name="remove_custom_logo" id="remove_custom_logo_flag" value="0">
            <input type="hidden" name="devaltum_credit_show_logo" id="input_show_logo" value="<?php echo $bShowLogo ? '1' : '0'; ?>">
            <input type="hidden" name="devaltum_credit_alignment" id="input_alignment" value="<?php echo Security::escape($bAlignment); ?>">

            <div class="sa-branding-split">
                <!-- Left Column: Branding Controls -->
                <div>
                    <!-- Top Summary Card -->
                    <div class="sa-pro-card" style="background:linear-gradient(135deg, #ffffff, #f8fbff); border-color:rgba(15,109,255,0.2);">
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                            <div>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <h2 style="margin:0; font-size:16px; font-weight:900; color:#0f172a;">DevAltum Global Footer Signature</h2>
                                    <span class="badge" style="background:#eff6ff; color:#0f6dff; border:1px solid #bfdbfe; font-size:10px; font-weight:800;">Global System Setting</span>
                                </div>
                                <p style="margin:3px 0 0; color:#64748b; font-size:11.5px; font-weight:500;">
                                    Brand signature printed at the bottom of all customer thermal receipts (80mm/58mm) and A4/A5 invoices across all stores.
                                </p>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm" style="font-weight:900; padding:0 18px; height:36px; box-shadow:0 4px 12px rgba(15,109,255,0.25);">
                                <span class="material-symbols-rounded" style="font-size:16px;">save</span> Save Footer Branding
                            </button>
                        </div>
                    </div>

                    <!-- 1. Brand Identity & Visual Accents -->
                    <div class="sa-pro-card">
                        <div class="sa-card-header-clean">
                            <div class="sa-card-title-group">
                                <span class="sa-card-icon-badge"><span class="material-symbols-rounded" style="font-size:16px;">verified</span></span>
                                <div>
                                    <h3>Brand Identity & Visual Accents</h3>
                                    <p>Configure company name, signature prefix, WhatsApp phone and accent color.</p>
                                </div>
                            </div>
                        </div>

                        <div class="sa-form-grid" style="grid-template-columns:repeat(2, minmax(0, 1fr));">
                            <div class="sa-field-group">
                                <label class="sa-field-label">Prefix Text <span class="sa-field-hint">e.g. Software By</span></label>
                                <div class="sa-luxury-box">
                                    <span class="material-symbols-rounded sa-box-icon">edit_note</span>
                                    <input class="sa-luxury-input" type="text" name="devaltum_brand_prefix" id="input_brand_prefix" value="<?php echo Security::escape($bBrandPrefix); ?>" placeholder="Software By">
                                </div>
                            </div>

                            <div class="sa-field-group">
                                <label class="sa-field-label">Brand / Agency Name <span class="req">*</span></label>
                                <div class="sa-luxury-box">
                                    <span class="material-symbols-rounded sa-box-icon">verified</span>
                                    <input class="sa-luxury-input" type="text" name="devaltum_brand_name" id="input_brand_name" value="<?php echo Security::escape($bBrandName); ?>" placeholder="DevAltum" required>
                                </div>
                            </div>

                            <div class="sa-field-group">
                                <label class="sa-field-label">Phone / WhatsApp Support <span class="sa-field-hint">Customer Contact</span></label>
                                <div class="sa-luxury-box">
                                    <span class="material-symbols-rounded sa-box-icon">call</span>
                                    <input class="sa-luxury-input" type="text" name="devaltum_phone_text" id="input_phone_text" value="<?php echo Security::escape($bPhoneText); ?>" placeholder="+92 3101648600">
                                </div>
                            </div>

                            <div class="sa-field-group">
                                <label class="sa-field-label">Brand Accent Color <span class="sa-field-hint">Receipt Highlight</span></label>
                                <div class="sa-color-control-wrap">
                                    <div class="sa-color-preview-disc" title="Click to choose brand color">
                                        <input type="color" name="devaltum_brand_color" id="input_brand_color" value="<?php echo Security::escape($bBrandColor); ?>">
                                        <div class="sa-color-preview-inner" id="brand_color_disc" style="background:<?php echo Security::escape($bBrandColor); ?>;"></div>
                                    </div>
                                    <div class="sa-luxury-box" style="flex:1;">
                                        <span style="font-weight:800; color:#94a3b8; font-size:14px; font-family:monospace;">#</span>
                                        <input class="sa-luxury-input" type="text" id="input_brand_color_hex" value="<?php echo ltrim(Security::escape($bBrandColor), '#'); ?>" style="font-family:monospace; text-transform:uppercase; font-weight:800;" placeholder="0F6DFF">
                                    </div>
                                </div>
                                <div class="sa-color-swatches" id="brand_color_swatches">
                                    <span class="sa-color-swatch-dot <?php echo (strtoupper($bBrandColor) === '#0F6DFF') ? 'active' : ''; ?>" data-hex="#0F6DFF" style="background:#0F6DFF;" title="DevAltum Royal Blue"></span>
                                    <span class="sa-color-swatch-dot <?php echo (strtoupper($bBrandColor) === '#10B981') ? 'active' : ''; ?>" data-hex="#10B981" style="background:#10B981;" title="Emerald Green"></span>
                                    <span class="sa-color-swatch-dot <?php echo (strtoupper($bBrandColor) === '#6366F1') ? 'active' : ''; ?>" data-hex="#6366F1" style="background:#6366F1;" title="Indigo Modern"></span>
                                    <span class="sa-color-swatch-dot <?php echo (strtoupper($bBrandColor) === '#0F172A') ? 'active' : ''; ?>" data-hex="#0F172A" style="background:#0F172A;" title="Midnight Dark"></span>
                                    <span class="sa-color-swatch-dot <?php echo (strtoupper($bBrandColor) === '#E11D48') ? 'active' : ''; ?>" data-hex="#E11D48" style="background:#E11D48;" title="Ruby Red"></span>
                                    <span class="sa-color-swatch-dot <?php echo (strtoupper($bBrandColor) === '#F59E0B') ? 'active' : ''; ?>" data-hex="#F59E0B" style="background:#F59E0B;" title="Amber Gold"></span>
                                    <span class="sa-color-swatch-dot <?php echo (strtoupper($bBrandColor) === '#8B5CF6') ? 'active' : ''; ?>" data-hex="#8B5CF6" style="background:#8B5CF6;" title="Electric Violet"></span>
                                    <span class="sa-color-swatch-dot <?php echo (strtoupper($bBrandColor) === '#14B8A6') ? 'active' : ''; ?>" data-hex="#14B8A6" style="background:#14B8A6;" title="Teal Modern"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Logo Studio & Sizing -->
                    <div class="sa-pro-card">
                        <div class="sa-card-header-clean">
                            <div class="sa-card-title-group">
                                <span class="sa-card-icon-badge" style="background:#f0fdf4; color:#10b981;"><span class="material-symbols-rounded" style="font-size:16px;">image</span></span>
                                <div>
                                    <h3>Logo Studio & Display Rules</h3>
                                    <p>Upload a custom agency logo with transparent PNG preview and live size adjustment.</p>
                                </div>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr auto; gap:14px; align-items:stretch; margin-bottom:14px;">
                            <div class="sa-dropzone" id="branding_dropzone">
                                <input type="file" name="branding_logo_file" id="branding_file_input" accept="image/png,image/jpeg,image/svg+xml,image/webp">
                                <span class="material-symbols-rounded" style="font-size:32px; color:#0f6dff; margin-bottom:4px;">cloud_upload</span>
                                <div style="font-size:12px; font-weight:800; color:#0f172a;">Drag & Drop custom branding logo here, or <span style="color:#0f6dff; text-decoration:underline;">Browse</span></div>
                                <div style="font-size:10.5px; color:#64748b; margin-top:3px;">Supports PNG, JPG, WebP, SVG (Transparent PNG recommended)</div>
                            </div>

                            <div class="sa-logo-checkerboard">
                                <div style="font-size:10px; font-weight:800; color:#64748b; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.4px;">Active Logo:</div>
                                <img src="<?php echo Security::escape($activeLogoSrc); ?>" id="branding_logo_img_preview" alt="Branding Logo" style="height:<?php echo (int) $bLogoSize; ?>px; max-height:55px; max-width:110px; object-fit:contain; display:block; margin:auto; transition:height 0.15s ease;">
                                <button type="button" class="btn btn-secondary btn-sm sa-danger-btn" id="btn_remove_custom_logo" style="margin-top:8px; font-size:10.5px; padding:2px 8px; height:24px; width:100%;">
                                    <span class="material-symbols-rounded" style="font-size:13px;">restart_alt</span> Reset Default
                                </button>
                            </div>
                        </div>

                        <div class="sa-form-grid" style="grid-template-columns:repeat(2, minmax(0, 1fr)); align-items:center;">
                            <div class="sa-field-group">
                                <label class="sa-field-label">Display Mode</label>
                                <div class="sa-pill-segmented" id="toggle_display_mode">
                                    <button type="button" class="sa-pill-btn <?php echo $bShowLogo ? 'active' : ''; ?>" data-mode="1"><span class="material-symbols-rounded" style="font-size:14px;">image</span> Logo + Text</button>
                                    <button type="button" class="sa-pill-btn <?php echo !$bShowLogo ? 'active' : ''; ?>" data-mode="0"><span class="material-symbols-rounded" style="font-size:14px;">short_text</span> Text Only</button>
                                </div>
                            </div>

                            <div class="sa-field-group">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <label class="sa-field-label" style="margin:0;">Logo Height</label>
                                    <span class="sa-slider-badge" id="logo_size_label"><?php echo (int) $bLogoSize; ?>px</span>
                                </div>
                                <div class="sa-slider-row">
                                    <input type="range" class="sa-pro-slider" min="20" max="80" value="<?php echo (int) $bLogoSize; ?>" name="devaltum_credit_logo_size" id="input_logo_size">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Typography & Alignment -->
                    <div class="sa-pro-card">
                        <div class="sa-card-header-clean">
                            <div class="sa-card-title-group">
                                <span class="sa-card-icon-badge" style="background:#fef3c7; color:#d97706;"><span class="material-symbols-rounded" style="font-size:16px;">text_fields</span></span>
                                <div>
                                    <h3>Typography & Text Alignment</h3>
                                    <p>Select font family, font weight, style, alignment and font sizing for perfect thermal readability.</p>
                                </div>
                            </div>
                        </div>

                        <div class="sa-form-grid" style="grid-template-columns:repeat(2, minmax(0, 1fr));">
                            <div class="sa-field-group">
                                <label class="sa-field-label">Font Family <span class="sa-field-hint">16 Curated Styles</span></label>
                                <div class="sa-luxury-box">
                                    <span class="material-symbols-rounded sa-box-icon">font_download</span>
                                    <select class="sa-luxury-select" name="devaltum_credit_font_family" id="input_font_family">
                                        <optgroup label="🌟 Modern & Clean Sans (Silicon Valley / High-DPI)">
                                            <option value="Inter" <?php echo ($bFontFamily === 'Inter') ? 'selected' : ''; ?> style="font-family:'Inter', sans-serif;">Inter (Stripe & Vercel Modern Sans)</option>
                                            <option value="Outfit" <?php echo ($bFontFamily === 'Outfit') ? 'selected' : ''; ?> style="font-family:'Outfit', sans-serif;">Outfit (Tech Minimalist Sans)</option>
                                            <option value="Poppins" <?php echo ($bFontFamily === 'Poppins') ? 'selected' : ''; ?> style="font-family:'Poppins', sans-serif;">Poppins (Geometric Friendly Modern)</option>
                                            <option value="Segoe UI" <?php echo ($bFontFamily === 'Segoe UI') ? 'selected' : ''; ?> style="font-family:'Segoe UI', sans-serif;">Segoe UI (Windows Ultra-Crisp Thermal)</option>
                                            <option value="Roboto" <?php echo ($bFontFamily === 'Roboto') ? 'selected' : ''; ?> style="font-family:'Roboto', sans-serif;">Roboto (Google High-DPI Material)</option>
                                            <option value="Montserrat" <?php echo ($bFontFamily === 'Montserrat') ? 'selected' : ''; ?> style="font-family:'Montserrat', sans-serif;">Montserrat (Clean Luxury Sans)</option>
                                            <option value="Arial" <?php echo ($bFontFamily === 'Arial') ? 'selected' : ''; ?> style="font-family:Arial, sans-serif;">Arial (Standard Clean Universal)</option>
                                        </optgroup>
                                        <optgroup label="🧾 Thermal POS Monospace (Razor-Sharp Receipt Rolls)">
                                            <option value="Courier New" <?php echo ($bFontFamily === 'Courier New') ? 'selected' : ''; ?> style="font-family:'Courier New', monospace;">Courier New (Classic 80mm Thermal Receipt)</option>
                                            <option value="Space Mono" <?php echo ($bFontFamily === 'Space Mono') ? 'selected' : ''; ?> style="font-family:'Space Mono', monospace;">Space Mono (Cyber Retro High-Tech POS)</option>
                                            <option value="JetBrains Mono" <?php echo ($bFontFamily === 'JetBrains Mono') ? 'selected' : ''; ?> style="font-family:'JetBrains Mono', monospace;">JetBrains Mono (Developer High-Precision)</option>
                                            <option value="Cascadia Code" <?php echo ($bFontFamily === 'Cascadia Code') ? 'selected' : ''; ?> style="font-family:'Cascadia Code', monospace;">Cascadia Code (Clear High-DPI Terminal)</option>
                                            <option value="Fira Code" <?php echo ($bFontFamily === 'Fira Code') ? 'selected' : ''; ?> style="font-family:'Fira Code', monospace;">Fira Code (Modern High-Legibility Mono)</option>
                                        </optgroup>
                                        <optgroup label="💎 Luxury Boutique, Editorial & Condensed">
                                            <option value="Cinzel" <?php echo ($bFontFamily === 'Cinzel') ? 'selected' : ''; ?> style="font-family:'Cinzel', serif;">Cinzel (Royal Jewelry & Luxury Boutique)</option>
                                            <option value="Playfair Display" <?php echo ($bFontFamily === 'Playfair Display') ? 'selected' : ''; ?> style="font-family:'Playfair Display', serif;">Playfair Display (High-End Elegant Editorial)</option>
                                            <option value="Oswald" <?php echo ($bFontFamily === 'Oswald') ? 'selected' : ''; ?> style="font-family:'Oswald', sans-serif;">Oswald (High-Impact Condensed POS)</option>
                                            <option value="Times New Roman" <?php echo ($bFontFamily === 'Times New Roman') ? 'selected' : ''; ?> style="font-family:'Times New Roman', serif;">Times New Roman (Formal Ledger Invoice)</option>
                                        </optgroup>
                                    </select>
                                    <span class="material-symbols-rounded sa-select-chevron">expand_more</span>
                                </div>
                            </div>

                            <div class="sa-field-group">
                                <label class="sa-field-label">Font Weight <span class="sa-field-hint">Thermal Contrast</span></label>
                                <div class="sa-luxury-box">
                                    <span class="material-symbols-rounded sa-box-icon">format_bold</span>
                                    <select class="sa-luxury-select" name="devaltum_credit_font_weight" id="input_font_weight">
                                        <option value="500" <?php echo ($bFontWeight === '500') ? 'selected' : ''; ?>>Medium (500) — Soft Document</option>
                                        <option value="600" <?php echo ($bFontWeight === '600') ? 'selected' : ''; ?>>Semi-Bold (600) — Standard Legibility</option>
                                        <option value="700" <?php echo ($bFontWeight === '700') ? 'selected' : ''; ?>>Bold (700) — Strong Contrast</option>
                                        <option value="800" <?php echo ($bFontWeight === '800') ? 'selected' : ''; ?>>Extra-Bold (800) [Recommended for 203 DPI]</option>
                                        <option value="900" <?php echo ($bFontWeight === '900') ? 'selected' : ''; ?>>Black (900) — Maximum Ink Impact</option>
                                    </select>
                                    <span class="material-symbols-rounded sa-select-chevron">expand_more</span>
                                </div>
                            </div>

                            <div class="sa-field-group">
                                <label class="sa-field-label">Alignment</label>
                                <div class="sa-pill-segmented" id="toggle_alignment">
                                    <button type="button" class="sa-pill-btn <?php echo ($bAlignment === 'left') ? 'active' : ''; ?>" data-align="left"><span class="material-symbols-rounded" style="font-size:14px;">format_align_left</span> Left</button>
                                    <button type="button" class="sa-pill-btn <?php echo ($bAlignment === 'center') ? 'active' : ''; ?>" data-align="center"><span class="material-symbols-rounded" style="font-size:14px;">format_align_center</span> Center</button>
                                    <button type="button" class="sa-pill-btn <?php echo ($bAlignment === 'right') ? 'active' : ''; ?>" data-align="right"><span class="material-symbols-rounded" style="font-size:14px;">format_align_right</span> Right</button>
                                </div>
                            </div>

                            <div class="sa-field-group">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <label class="sa-field-label" style="margin:0;">Font Size</label>
                                    <span class="sa-slider-badge" id="font_size_label"><?php echo (float) $bFontSize; ?>px</span>
                                </div>
                                <div class="sa-slider-row">
                                    <input type="range" class="sa-pro-slider" min="9" max="16" step="0.5" value="<?php echo (float) $bFontSize; ?>" name="devaltum_credit_font_size" id="input_font_size">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                        <button type="submit" class="btn btn-primary" style="font-weight:900; padding:10px 24px; box-shadow:0 4px 14px rgba(15,109,255,0.25);">
                            <span class="material-symbols-rounded">save</span> Save Footer Branding Settings
                        </button>
                    </div>
                </div>

                <!-- Right Column: Real-Time Physical Simulation Previews -->
                <div>
                    <!-- Simulation 1: Digital Color Invoice -->
                    <div class="sa-pro-card" style="margin-bottom:16px;">
                        <div class="sa-card-header-clean">
                            <div class="sa-card-title-group">
                                <span class="sa-card-icon-badge"><span class="material-symbols-rounded" style="font-size:16px;">devices</span></span>
                                <div>
                                    <h3>Digital & Color Screen View</h3>
                                    <p>Vector rendering for customer screen, PDF & WhatsApp invoices.</p>
                                </div>
                            </div>
                            <span class="badge badge-soft" style="font-size:10px;">RGB Screen</span>
                        </div>

                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px; box-shadow:inset 0 1px 3px rgba(0,0,0,0.02);">
                            <div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; padding:18px 14px; box-shadow:0 4px 12px rgba(15,23,42,0.04);">
                                <div style="border-top:1.5px dashed #cbd5e1; margin-bottom:12px; position:relative;">
                                    <span style="position:absolute; top:-9px; left:50%; transform:translateX(-50%); background:#fff; padding:0 8px; font-size:9.5px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px;">Invoice Footer Divider</span>
                                </div>
                                <div id="live_branding_screen_box" style="text-align:center;">
                                    <?php echo App::devAltumReceiptFooterHtml(); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Simulation 2: Authentic 203 DPI Physical Thermal Roll -->
                    <div class="sa-pro-card">
                        <div class="sa-card-header-clean">
                            <div class="sa-card-title-group">
                                <span class="sa-card-icon-badge" style="background:#0f172a; color:#ffffff;"><span class="material-symbols-rounded" style="font-size:16px;">print</span></span>
                                <div>
                                    <h3>203 DPI Thermal POS Slip Output</h3>
                                    <p>Physical paper simulation of Epson/Xprinter thermal receipt output.</p>
                                </div>
                            </div>
                            <span class="badge" style="background:#0f172a; color:#fff; font-size:10px;">Solid Black (No Dither)</span>
                        </div>

                        <div style="background:radial-gradient(circle at 50% 0%, #f1f5f9, #e2e8f0); border:1px solid #cbd5e1; border-radius:10px; padding:22px 10px; display:flex; justify-content:center;">
                            <!-- Thermal Paper Strip -->
                            <div class="sa-thermal-roll-strip">
                                <div class="sa-thermal-tear-top"></div>
                                <div style="text-align:center; font-family:'Courier New', monospace; font-size:9.5px; color:#475569; margin-bottom:10px; border-bottom:1px dashed #000; padding-bottom:6px;">
                                    ====================================<br>
                                    <strong>*** END OF CUSTOMER RECEIPT ***</strong><br>
                                    ====================================
                                </div>
                                <div id="live_branding_thermal_box" style="text-align:center;">
                                    <?php echo App::devAltumReceiptFooterHtml(); ?>
                                </div>
                                <div class="sa-thermal-tear-bottom"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>

<!-- PREVIEW & DELETE MODALS -->
<?php foreach ($allTemplates as $template): ?>
<div class="modal sa-template-modal" id="previewTemplate-<?php echo Security::escape($template['template_key']); ?>" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h3><?php echo Security::escape($template['template_name']); ?></h3>
                <div style="font-size:11.5px; color:#64748b; font-weight:700; margin-top:2px;">
                    Type: <strong><?php echo ucfirst($template['template_type']); ?></strong> | Width: <strong><?php echo (int) $template['paper_width_mm']; ?>mm</strong> | <?php echo !empty($template['is_system']) ? 'System' : 'Custom HTML'; ?>
                </div>
            </div>
            <button class="icon-btn" type="button" data-close-modal-target="previewTemplate-<?php echo Security::escape($template['template_key']); ?>"><span class="material-symbols-rounded">close</span></button>
        </div>
        <div class="sa-preview-stage">
            <div class="sa-live-paper <?php echo ($template['template_type'] === 'thermal' ? ($template['paper_width_mm'] <= 58 ? 'is-thermal-58-preview' : 'is-thermal-preview') : 'is-document-preview'); ?>">
                <?php 
                $previewHtml = trim($template['html_block']) !== '' ? $template['html_block'] : ($template['template_type'] === 'thermal' ? $thermalSampleHtml : $documentSampleHtml);
                echo ReceiptTemplates::renderHtmlBlock($previewHtml, [
                    'company_name' => 'Usman Mobile Center',
                    'company_address' => 'Shop #12, Lake City Commercial Market, Lahore',
                    'whatsapp' => '+92 308 1207665',
                    'invoice_no' => 'INV-MA-5',
                    'date' => date('M d, Y h:i A'),
                    'cashier' => 'Fizzy Sole',
                    'customer' => 'Walk-in Customer',
                    'phone' => '0300-1234567',
                    'status' => 'PAID',
                    'payment' => 'Cash',
                    'channel' => 'Retail',
                    'items' => [
                        ['product_name' => 'Ottogi Jin Ramen Mild', 'product_unit_label' => '1 Pcs', 'quantity' => 1, 'price' => 1200, 'total' => 1200],
                        ['product_name' => 'Premium Soft Drink 250ml', 'product_unit_label' => '1 Can', 'quantity' => 2, 'price' => 150, 'total' => 300],
                    ],
                    'subtotal' => 1500,
                    'discount' => 0,
                    'total' => 1500,
                    'paid' => 1500,
                    'remaining' => 0,
                    'footer' => 'Thank you for shopping with us! Please come again.',
                    'qr' => '<div style="width:68px;height:68px;background:repeating-linear-gradient(45deg,#111827 0 4px,#fff 4px 8px);margin:0 auto;"></div>',
                ]); 
                ?>
            </div>
        </div>
    </div>
</div>

<div class="modal sa-template-modal" id="deleteTemplate-<?php echo Security::escape($template['template_key']); ?>" style="display:none;">
    <div class="modal-content sa-confirm-card">
        <div class="sa-confirm-icon"><span class="material-symbols-rounded"><?php echo !empty($template['is_system']) ? 'inventory_2' : 'delete'; ?></span></div>
        <h3 style="margin:0 0 4px; font-size:16px; font-weight:800;"><?php echo !empty($template['is_system']) ? 'Archive system template?' : 'Delete template?'; ?></h3>
        <p style="margin:0; font-size:12px; color:#64748b; font-weight:600;"><?php echo Security::escape($template['template_name']); ?> (<?php echo Security::escape(ucfirst($template['template_type'])); ?>)</p>
        <div class="sa-confirm-actions">
            <button class="btn btn-secondary btn-sm" type="button" data-close-modal-target="deleteTemplate-<?php echo Security::escape($template['template_key']); ?>">Cancel</button>
            <form action="<?php echo $baseUrl; ?>/superadmin/deleteTemplate/<?php echo (int) $template['id']; ?>" method="POST" data-no-ajax style="margin:0;">
                <?php echo Security::csrfField(); ?>
                <button class="btn sa-danger-btn btn-sm" style="width:100%;" type="submit"><?php echo !empty($template['is_system']) ? 'Archive' : 'Delete'; ?></button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php ob_start(); ?>
<script>
(() => {
    // Studio Tabs Navigation & URL Param Sync
    const tabs = Array.from(document.querySelectorAll('[data-studio-tab]'));
    const sections = Array.from(document.querySelectorAll('[data-studio-section]'));
    const switchTab = (target) => {
        tabs.forEach((item) => item.classList.toggle('active', item.dataset.studioTab === target));
        sections.forEach((section) => section.classList.toggle('active', section.dataset.studioSection === target));
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            switchTab(tab.dataset.studioTab || 'builder');
        });
    });

    // Check if ?tab=branding was passed in URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'branding') {
        switchTab('branding');
    }

    // Builder scripts
    const thermalSampleHtml = <?php echo json_encode($thermalSampleHtml); ?>;
    const documentSampleHtml = <?php echo json_encode($documentSampleHtml); ?>;
    const editor = document.querySelector('[data-html-editor]');
    const preview = document.querySelector('[data-html-preview]');
    const previewPaper = document.querySelector('.sa-live-paper');
    const previewSubtitle = document.getElementById('preview_subtitle');
    const previewBadge = document.getElementById('preview_dimension_badge');
    const formTypeInput = document.getElementById('form_template_type');
    const widthSelect = document.getElementById('builder_width');
    const nameInput = document.getElementById('builder_name');
    const keyInput = document.getElementById('builder_key');
    const activeTypeBadge = document.querySelector('[data-active-type-badge]');
    const typeButtons = Array.from(document.querySelectorAll('[data-type-select]'));
    const loadSampleButtons = Array.from(document.querySelectorAll('[data-load-sample]'));

    const sampleItems = `
        <tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:4px 2px;"><strong>Ottogi Jin Ramen Mild</strong><small style="display:block;font-size:9px;color:#555;">1 Gram</small></td><td style="padding:4px 2px;text-align:center;">1</td><td style="padding:4px 2px;text-align:right;">Rs 1,200</td><td style="padding:4px 2px;text-align:right;font-weight:700;">Rs 1,200</td></tr>
        <tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:4px 2px;"><strong>Standard Carton Box</strong><small style="display:block;font-size:9px;color:#555;">1 PCS</small></td><td style="padding:4px 2px;text-align:center;">2</td><td style="padding:4px 2px;text-align:right;">Rs 400</td><td style="padding:4px 2px;text-align:right;font-weight:700;">Rs 800</td></tr>
    `;
    const sampleQr = '<div style="width:68px;height:68px;background:repeating-linear-gradient(45deg,#111827 0 4px,#fff 4px 8px);margin:0 auto;"></div>';
    const tokens = {
        '{{company_name}}': 'Usman Mobile Center',
        '{{company_address}}': 'Shop #12, Lake City Commercial Market, Lahore',
        '{{whatsapp}}': '+92 308 1207665',
        '{{invoice_no}}': 'INV-MA-5',
        '{{date}}': new Date().toLocaleString('en-US', { month:'short', day:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }),
        '{{cashier}}': 'Fizzy Sole',
        '{{customer}}': 'Walk-in Customer',
        '{{phone}}': '0300-1234567',
        '{{status}}': 'PAID',
        '{{payment}}': 'Cash',
        '{{channel}}': 'Retail POS',
        '{{items}}': sampleItems,
        '{{subtotal}}': 'Rs 2,000',
        '{{discount}}': 'Rs 0',
        '{{total}}': 'Rs 2,000',
        '{{paid}}': 'Rs 2,000',
        '{{remaining}}': 'Rs 0',
        '{{footer}}': 'Thank you for choosing us!',
        '{{qr}}': sampleQr,
        '{{developer_credit}}': <?php echo json_encode(App::devAltumReceiptFooterHtml()); ?>
    };

    const render = () => {
        if (!editor || !preview) return;
        let html = editor.value || '';
        Object.entries(tokens).forEach(([token, value]) => {
            html = html.split(token).join(value);
        });
        preview.innerHTML = html;
    };

    const updatePaperSize = () => {
        const type = formTypeInput ? formTypeInput.value : 'thermal';
        const width = widthSelect ? parseInt(widthSelect.value, 10) : 80;
        if (!previewPaper) return;
        previewPaper.classList.remove('is-thermal-preview', 'is-thermal-58-preview', 'is-document-preview');

        if (type === 'thermal') {
            if (width <= 58) {
                previewPaper.classList.add('is-thermal-58-preview');
                if (previewBadge) previewBadge.textContent = '58mm Roll';
                if (previewSubtitle) previewSubtitle.textContent = '58mm Compact thermal roll preview.';
            } else {
                previewPaper.classList.add('is-thermal-preview');
                if (previewBadge) previewBadge.textContent = '80mm Roll';
                if (previewSubtitle) previewSubtitle.textContent = '80mm Standard POS thermal roll preview.';
            }
        } else {
            previewPaper.classList.add('is-document-preview');
            if (previewBadge) previewBadge.textContent = width >= 210 ? 'A4 Invoice' : 'A5 Invoice';
            if (previewSubtitle) previewSubtitle.textContent = (width >= 210 ? '210mm A4' : '148mm A5') + ' document invoice preview.';
        }
    };

    const setType = (type, autoLoadCode = false) => {
        if (formTypeInput) formTypeInput.value = type;
        typeButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.typeSelect === type));
        if (activeTypeBadge) {
            activeTypeBadge.textContent = (type === 'thermal') ? 'Thermal POS' : 'Document Invoice';
            activeTypeBadge.style.background = (type === 'thermal') ? '#dcfce7' : '#e0e7ff';
            activeTypeBadge.style.color = (type === 'thermal') ? '#166534' : '#3730a3';
        }
        if (widthSelect) {
            if (type === 'thermal' && (widthSelect.value === '148' || widthSelect.value === '210')) widthSelect.value = '80';
            else if (type === 'document' && (widthSelect.value === '80' || widthSelect.value === '58')) widthSelect.value = '148';
        }
        if (autoLoadCode && editor) {
            editor.value = (type === 'thermal') ? thermalSampleHtml : documentSampleHtml;
            loadSampleButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.loadSample === type));
        }
        updatePaperSize();
        render();
    };

    typeButtons.forEach((btn) => btn.addEventListener('click', () => setType(btn.dataset.typeSelect, false)));
    loadSampleButtons.forEach((btn) => btn.addEventListener('click', () => setType(btn.dataset.loadSample, true)));
    document.querySelector('[data-clear-editor]')?.addEventListener('click', () => { if (editor) { editor.value = ''; render(); } });
    document.querySelector('[data-reset-sample]')?.addEventListener('click', () => {
        const type = formTypeInput ? formTypeInput.value : 'thermal';
        if (editor) { editor.value = (type === 'thermal') ? thermalSampleHtml : documentSampleHtml; render(); }
    });

    document.querySelectorAll('[data-insert-token]').forEach((chip) => {
        chip.addEventListener('click', () => {
            if (!editor) return;
            const token = chip.dataset.insertToken;
            const start = editor.selectionStart || 0;
            const end = editor.selectionEnd || 0;
            const val = editor.value;
            editor.value = val.substring(0, start) + token + val.substring(end);
            editor.selectionStart = editor.selectionEnd = start + token.length;
            editor.focus();
            render();
        });
    });

    nameInput?.addEventListener('input', () => {
        if (keyInput && (!keyInput.value || keyInput.dataset.autoTouched !== 'true')) {
            keyInput.value = nameInput.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        }
    });
    keyInput?.addEventListener('input', () => { keyInput.dataset.autoTouched = 'true'; });
    widthSelect?.addEventListener('change', updatePaperSize);
    editor?.addEventListener('input', render);
    updatePaperSize();
    render();

    // ============================================
    // BRANDING CUSTOMIZER REAL-TIME LIVE ENGINE
    // ============================================
    const brandPrefix = document.getElementById('input_brand_prefix');
    const brandName = document.getElementById('input_brand_name');
    const brandColor = document.getElementById('input_brand_color');
    const brandColorHex = document.getElementById('input_brand_color_hex');
    const phoneText = document.getElementById('input_phone_text');
    const fontFamily = document.getElementById('input_font_family');
    const fontWeight = document.getElementById('input_font_weight');
    const alignmentInput = document.getElementById('input_alignment');
    const fontSize = document.getElementById('input_font_size');
    const fontSizeLabel = document.getElementById('font_size_label');
    const logoSize = document.getElementById('input_logo_size');
    const logoSizeLabel = document.getElementById('logo_size_label');
    const showLogoInput = document.getElementById('input_show_logo');
    const logoImgPreview = document.getElementById('branding_logo_img_preview');
    const fileInput = document.getElementById('branding_file_input');
    const dropzone = document.getElementById('branding_dropzone');
    const removeLogoFlag = document.getElementById('remove_custom_logo_flag');
    const customLogoVal = document.getElementById('branding_custom_logo_val');
    const defaultLogoUri = <?php echo json_encode($defaultLogoDataUri); ?>;

    const screenPreviewBox = document.getElementById('live_branding_screen_box');
    const thermalPreviewBox = document.getElementById('live_branding_thermal_box');

    let currentLogoSrc = <?php echo json_encode($activeLogoSrc); ?>;

    const syncSwatches = (hex) => {
        document.querySelectorAll('#brand_color_swatches .sa-color-swatch-dot').forEach(dot => {
            dot.classList.toggle('active', dot.dataset.hex.toUpperCase() === (hex || '').toUpperCase());
        });
    };

    const updateBrandingLive = () => {
        const prefix = (brandPrefix?.value || 'Software By').trim();
        const name = (brandName?.value || 'DevAltum').trim();
        const color = brandColor?.value || '#0f6dff';
        const phone = (phoneText?.value || '').trim();
        const font = fontFamily?.value || 'Segoe UI';
        const weight = fontWeight?.value || '800';
        const align = (alignmentInput?.value || 'center').toLowerCase();
        const fSize = parseFloat(fontSize?.value || 12);
        const lSize = parseInt(logoSize?.value || 40, 10);
        const isShowLogo = showLogoInput?.value === '1';

        if (fontSizeLabel) fontSizeLabel.textContent = fSize + 'px';
        if (logoSizeLabel) logoSizeLabel.textContent = lSize + 'px';
        if (logoImgPreview) logoImgPreview.style.height = lSize + 'px';

        const justify = (align === 'left') ? 'flex-start' : ((align === 'right') ? 'flex-end' : 'center');
        const phoneFSize = (fSize * 1.12).toFixed(1);

        let logoMarkup = '';
        if (isShowLogo && currentLogoSrc) {
            logoMarkup = `<img src="${currentLogoSrc}" alt="${name}" style="height:${lSize}px; max-height:${lSize + 6}px; width:auto; max-width:${lSize * 2.2}px; object-fit:contain; display:inline-block; vertical-align:middle; flex-shrink:0;">`;
        }

        const fontStack = `'${font}', Arial, -apple-system, BlinkMacSystemFont, Roboto, sans-serif`;

        const renderHtml = (isThermal = false) => `
            <div style="display:inline-flex; align-items:center; justify-content:${justify}; gap:10px; margin:4px auto; text-align:${align}; text-decoration:none;">
                ${logoMarkup}
                <div style="display:inline-flex; flex-direction:column; justify-content:center; line-height:1.25; font-family:${fontStack};">
                    <span style="font-size:${fSize}px; font-weight:${weight}; color:#000; letter-spacing:0.4px; text-transform:uppercase; white-space:nowrap;">
                        ${prefix} <strong style="font-weight:900; color:${isThermal ? '#000' : color};">${name}</strong>
                    </span>
                    ${phone ? `<span style="font-size:${phoneFSize}px; font-weight:${weight}; color:#000; letter-spacing:0.6px; white-space:nowrap; margin-top:2px;">${phone}</span>` : ''}
                </div>
            </div>
        `;

        if (screenPreviewBox) screenPreviewBox.innerHTML = renderHtml(false);
        if (thermalPreviewBox) thermalPreviewBox.innerHTML = renderHtml(true);
    };

    // Color sync
    const brandColorDisc = document.getElementById('brand_color_disc');
    const updateColorVisuals = (hex) => {
        if (brandColorDisc) brandColorDisc.style.backgroundColor = hex;
        syncSwatches(hex);
        updateBrandingLive();
    };

    brandColor?.addEventListener('input', () => {
        if (brandColorHex) brandColorHex.value = brandColor.value;
        updateColorVisuals(brandColor.value);
    });
    brandColorHex?.addEventListener('input', () => {
        let val = brandColorHex.value.trim();
        if (!val.startsWith('#') && /^[0-9A-Fa-f]{6}$/.test(val)) {
            val = '#' + val;
            brandColorHex.value = val;
        }
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            brandColor.value = val;
            updateColorVisuals(val);
        }
    });

    // Color swatches click
    document.querySelectorAll('#brand_color_swatches .sa-color-swatch-dot').forEach(dot => {
        dot.addEventListener('click', () => {
            const hex = dot.dataset.hex;
            if (brandColor) brandColor.value = hex;
            if (brandColorHex) brandColorHex.value = hex;
            updateColorVisuals(hex);
        });
    });

    // Segmented Display Mode buttons
    document.querySelectorAll('#toggle_display_mode .sa-pill-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#toggle_display_mode .sa-pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (showLogoInput) showLogoInput.value = btn.dataset.mode;
            updateBrandingLive();
        });
    });

    // Segmented Alignment buttons
    document.querySelectorAll('#toggle_alignment .sa-pill-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#toggle_alignment .sa-pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (alignmentInput) alignmentInput.value = btn.dataset.align;
            updateBrandingLive();
        });
    });

    [brandPrefix, brandName, phoneText, fontFamily, fontWeight, fontSize, logoSize].forEach((el) => {
        el?.addEventListener('input', updateBrandingLive);
        el?.addEventListener('change', updateBrandingLive);
    });

    // File Drag and drop handler
    const handleFiles = (files) => {
        if (!files || !files[0]) return;
        const file = files[0];
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file (PNG, JPG, SVG, WebP).');
            return;
        }
        const reader = new FileReader();
        reader.onload = (e) => {
            currentLogoSrc = e.target.result;
            if (logoImgPreview) logoImgPreview.src = currentLogoSrc;
            if (customLogoVal) customLogoVal.value = currentLogoSrc;
            if (removeLogoFlag) removeLogoFlag.value = '0';
            updateBrandingLive();
        };
        reader.readAsDataURL(file);
    };

    fileInput?.addEventListener('change', (e) => handleFiles(e.target.files));
    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone?.addEventListener(eventName, (e) => { e.preventDefault(); dropzone.classList.add('dragover'); });
    });
    ['dragleave', 'drop'].forEach((eventName) => {
        dropzone?.addEventListener(eventName, (e) => { e.preventDefault(); dropzone.classList.remove('dragover'); });
    });
    dropzone?.addEventListener('drop', (e) => handleFiles(e.dataTransfer.files));

    // Reset / Remove custom logo
    document.getElementById('btn_remove_custom_logo')?.addEventListener('click', () => {
        currentLogoSrc = defaultLogoUri;
        if (logoImgPreview) logoImgPreview.src = defaultLogoUri;
        if (customLogoVal) customLogoVal.value = '';
        if (removeLogoFlag) removeLogoFlag.value = '1';
        if (fileInput) fileInput.value = '';
        updateBrandingLive();
    });

    updateBrandingLive();
})();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
