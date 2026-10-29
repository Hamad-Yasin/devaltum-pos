<?php ob_start(); ?>
<?php
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
$template = $template ?? [];
$id = (int) ($template['id'] ?? 0);
$name = (string) ($template['template_name'] ?? 'Custom Template');
$key = (string) ($template['template_key'] ?? 'custom-template');
$type = strtolower((string) ($template['template_type'] ?? 'thermal'));
$width = (int) ($template['paper_width_mm'] ?? ($type === 'document' ? 148 : 80));
$accent = (string) ($template['accent_color'] ?? '#0f6dff');
$fontFamily = (string) ($template['font_family'] ?? 'Segoe UI');
$baseTemplate = (string) ($template['base_template'] ?? 'classic');
$status = (string) ($template['status'] ?? 'active');
$notes = (string) ($template['layout_notes'] ?? '');
$isSystem = !empty($template['is_system']);
$htmlBlock = (string) ($template['html_block'] ?? '');

if (trim($htmlBlock) === '') {
    if ($type === 'thermal') {
        $htmlBlock = <<<'HTML'
<div class="thermal-receipt" style="font-family:'Segoe UI',Arial,sans-serif; width:100%; max-width:300px; margin:0 auto; padding:8px 0; color:#000;">
  <div style="text-align:center; margin-bottom:8px;">
    <h3 style="margin:0 0 2px; font-size:16px; font-weight:800; text-transform:uppercase;">{{company_name}}</h3>
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
    } else {
        $htmlBlock = <<<'HTML'
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
    }
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Fira+Code:wght@500;600;700&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700;800&family=Montserrat:wght@500;600;700;800;900&family=Oswald:wght@500;600;700&family=Outfit:wght@500;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Poppins:wght@500;600;700;800;900&family=Roboto:wght@400;500;700;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

<style>
.sa-edit-page { display:grid; gap:14px; }
.sa-edit-topbar {
    display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;
    padding:12px 18px; border:1px solid rgba(15,109,255,.16); border-radius:16px;
    background:#fff; box-shadow:0 8px 24px rgba(15,23,42,.04);
}
.sa-edit-left { display:flex; align-items:center; gap:10px; }
.sa-edit-left h1 { margin:0; font-size:18px; font-weight:900; color:#0f172a; display:flex; align-items:center; gap:8px; }
.sa-edit-actions { display:flex; gap:8px; align-items:center; }

.sa-studio-grid { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(320px,.85fr); gap:14px; align-items:start; }
.sa-panel { border:1px solid rgba(148,163,184,.2); border-radius:16px; background:#fff; box-shadow:0 6px 20px rgba(15,23,42,.04); padding:16px; }
.sa-panel-title { font-size:14px; font-weight:900; color:#0f172a; margin:0 0 10px; display:flex; align-items:center; gap:6px; }

/* Control sections */
.sa-section-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; margin-bottom:14px; }
.sa-section-title { font-size:11.5px; font-weight:900; color:#334155; text-transform:uppercase; letter-spacing:0.5px; margin:0 0 12px; display:flex; align-items:center; gap:6px; }

.sa-form-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px 14px; }
@media (max-width:960px) {
    .sa-form-grid { grid-template-columns:1fr 1fr; }
}
@media (max-width:600px) {
    .sa-form-grid { grid-template-columns:1fr; }
}
.sa-field-group { display:flex; flex-direction:column; gap:5px; min-width:0; }
.sa-field-label { 
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
.sa-field-label .req { color:#ef4444; font-weight:800; }

/* Ultra-Premium Luxury Box Input & Select System */
.sa-luxury-box {
    position: relative;
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), inset 0 1px 1px rgba(255, 255, 255, 0.8);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-sizing: border-box;
    width: 100%;
    overflow: hidden;
}
.sa-luxury-box:focus-within {
    border-color: #0f6dff;
    box-shadow: 0 0 0 3.5px rgba(15, 109, 255, 0.15), 0 4px 12px rgba(15, 109, 255, 0.08);
    background: #ffffff;
}
.sa-box-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding-left: 10px;
    padding-right: 4px;
    color: #64748b;
    pointer-events: none;
    user-select: none;
    flex-shrink: 0;
}
.sa-box-icon .material-symbols-rounded {
    font-size: 18px;
}
.sa-luxury-box:focus-within .sa-box-icon {
    color: #0f6dff;
}
.sa-luxury-input, .sa-luxury-select {
    width: 100%;
    height: 38px;
    border: none;
    outline: none;
    background: transparent;
    padding: 0 10px 0 6px;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    box-sizing: border-box;
    font-family: inherit;
}
.sa-luxury-input::placeholder {
    color: #94a3b8;
    font-weight: 500;
}
.sa-luxury-select {
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    padding-right: 32px;
}
.sa-select-chevron {
    position: absolute;
    right: 10px;
    pointer-events: none;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    transition: transform 0.15s ease;
}
.sa-luxury-box:focus-within .sa-select-chevron {
    color: #0f6dff;
}

/* Color control */
.sa-color-control-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}
.sa-color-preview-disc {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    border: 2px solid #ffffff;
    box-shadow: 0 0 0 1px #cbd5e1, 0 2px 6px rgba(15, 23, 42, 0.12);
    position: relative;
    cursor: pointer;
    flex-shrink: 0;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.sa-color-preview-disc:hover {
    transform: scale(1.05);
    box-shadow: 0 0 0 1.5px #0f6dff, 0 3px 8px rgba(15, 109, 255, 0.25);
}
.sa-color-native-input {
    position: absolute;
    top: -10px;
    left: -10px;
    width: 60px;
    height: 60px;
    opacity: 0;
    cursor: pointer;
}

/* Type Segmented Control */
.sa-type-toggle { 
    display:grid; 
    grid-template-columns:1fr 1fr; 
    background:#e2e8f0; 
    padding:3px; 
    border-radius:10px; 
    height:38px; 
    border:1px solid #cbd5e1; 
    box-sizing:border-box; 
    gap:3px; 
}
.sa-type-btn { 
    border:none; 
    background:transparent; 
    border-radius:7px; 
    font-size:12px; 
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
    box-shadow:0 1px 4px rgba(15,23,42,.12); 
}
.sa-type-btn.active.is-thermal { color:#0f6dff; font-weight:800; }
.sa-type-btn.active.is-document { color:#059669; font-weight:800; }

/* Code Editor */
.sa-code-toolbar { display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; padding:8px 10px; border:1px solid #cbd5e1; border-bottom:none; border-radius:10px 10px 0 0; background:#f1f5f9; }
.sa-chip { border:1px solid #cbd5e1; background:#fff; color:#334155; font-size:11px; font-weight:800; padding:4px 8px; border-radius:6px; cursor:pointer; display:inline-flex; align-items:center; gap:4px; }
.sa-chip:hover { background:#e2e8f0; }

.sa-code-textarea {
    width:100%; min-height:420px; border:1px solid #cbd5e1; border-radius:0 0 10px 10px;
    padding:12px; resize:vertical; background:#0b1324; color:#dbeafe;
    font-family:'Cascadia Code','Courier New',monospace; font-size:12px; line-height:1.55; outline:none; box-sizing:border-box;
}
.sa-code-textarea:focus { border-color:#0f6dff; box-shadow:0 0 0 3px rgba(15,109,255,.15); }

/* Token chips */
.sa-tokens-bar { display:flex; gap:5px; flex-wrap:wrap; margin-top:8px; align-items:center; }
.sa-token-chip { padding:3px 7px; border-radius:6px; background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:800; cursor:pointer; border:1px solid #bfdbfe; font-family:monospace; }
.sa-token-chip:hover { background:#dbeafe; }

/* Preview stage */
.sa-preview-container { border:1px solid rgba(148,163,184,.22); border-radius:14px; background:radial-gradient(circle at 50% 0%,#f8fafc,#e2e8f0); padding:16px 10px; min-height:500px; display:flex; justify-content:center; align-items:flex-start; overflow:auto; }
.sa-live-paper { background:#fff; border:1px solid #cbd5e1; box-shadow:0 14px 34px rgba(15,23,42,.12); overflow:hidden; transition:all .2s ease; box-sizing:border-box; }
.sa-live-paper.is-thermal-80 { width:100%; max-width:320px; border-radius:4px; }
.sa-live-paper.is-thermal-58 { width:100%; max-width:260px; border-radius:4px; }
.sa-live-paper.is-document-a5 { width:100%; max-width:520px; border-radius:10px; }
.sa-live-paper.is-document-a4 { width:100%; max-width:620px; border-radius:12px; }

@media (max-width:1050px) {
    .sa-studio-grid { grid-template-columns:1fr; }
}
</style>

<div class="sa-edit-page">
    <form action="<?php echo $baseUrl; ?>/superadmin/saveTemplate" method="POST" data-no-ajax>
        <?php echo Security::csrfField(); ?>
        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
        <input type="hidden" name="template_type" id="edit_type" value="<?php echo Security::escape($type); ?>">

        <!-- Top Bar -->
        <header class="sa-edit-topbar">
            <div class="sa-edit-left">
                <a href="<?php echo $baseUrl; ?>/superadmin/templates" class="btn btn-secondary btn-sm" style="display:inline-flex; align-items:center; gap:4px;">
                    <span class="material-symbols-rounded" style="font-size:16px;">arrow_back</span> Templates
                </a>
                <h1>
                    <span class="material-symbols-rounded" style="font-size:22px; color:#0f6dff;">edit_square</span>
                    Editing: <?php echo Security::escape($name); ?>
                </h1>
                <span class="badge badge-soft" id="type_badge_indicator"><?php echo ($type === 'thermal') ? 'Thermal 80mm' : 'Document Invoice'; ?></span>
            </div>
            <div class="sa-edit-actions">
                <button type="button" class="btn btn-secondary btn-sm" data-preview-mode-toggle>
                    <span class="material-symbols-rounded" style="font-size:16px;">print</span> Print Contrast View
                </button>
                <button type="submit" class="btn btn-primary btn-sm" style="font-weight:900;">
                    <span class="material-symbols-rounded" style="font-size:16px;">save</span> Save Template Changes
                </button>
            </div>
        </header>

        <!-- Main Studio Split Grid -->
        <div class="sa-studio-grid">
            <!-- Left Panel: Controls + Code -->
            <div class="sa-panel">
                <!-- Section 1: General Settings -->
                <div class="sa-section-box">
                    <div class="sa-section-title"><span class="material-symbols-rounded" style="font-size:16px; color:#0f6dff;">tune</span> General Configuration</div>
                    <div class="sa-form-grid">
                        <div class="sa-field-group">
                            <label class="sa-field-label">Template Name <span class="req">*</span></label>
                            <div class="sa-luxury-box">
                                <span class="sa-box-icon"><span class="material-symbols-rounded">badge</span></span>
                                <input class="sa-luxury-input" type="text" name="template_name" id="edit_name" value="<?php echo Security::escape($name); ?>" placeholder="e.g. Minimalist Thermal POS" required>
                            </div>
                        </div>
                        <div class="sa-field-group">
                            <label class="sa-field-label">Template Key (Slug) <span class="req">*</span></label>
                            <div class="sa-luxury-box">
                                <span class="sa-box-icon"><span class="material-symbols-rounded">tag</span></span>
                                <input class="sa-luxury-input" type="text" name="template_key" id="edit_key" value="<?php echo Security::escape($key); ?>" placeholder="e.g. minimalist-thermal" required>
                            </div>
                        </div>
                        <div class="sa-field-group">
                            <label class="sa-field-label">Type (Thermal / Doc)</label>
                            <div class="sa-type-toggle">
                                <button type="button" class="sa-type-btn <?php echo ($type === 'thermal') ? 'active is-thermal' : ''; ?>" data-toggle-type="thermal"><span class="material-symbols-rounded" style="font-size:14px;">print</span> Thermal</button>
                                <button type="button" class="sa-type-btn <?php echo ($type !== 'thermal') ? 'active is-document' : ''; ?>" data-toggle-type="document"><span class="material-symbols-rounded" style="font-size:14px;">article</span> Document</button>
                            </div>
                        </div>
                        <div class="sa-field-group">
                            <label class="sa-field-label">Paper Width</label>
                            <div class="sa-luxury-box">
                                <span class="sa-box-icon"><span class="material-symbols-rounded">straighten</span></span>
                                <select class="sa-luxury-select" name="paper_width_mm" id="edit_width">
                                    <option value="80" <?php echo ($width == 80) ? 'selected' : ''; ?>>80mm POS (Standard)</option>
                                    <option value="58" <?php echo ($width == 58) ? 'selected' : ''; ?>>58mm POS (Compact)</option>
                                    <option value="148" <?php echo ($width == 148) ? 'selected' : ''; ?>>148mm (A5 Invoice)</option>
                                    <option value="210" <?php echo ($width == 210) ? 'selected' : ''; ?>>210mm (A4 Document)</option>
                                </select>
                                <span class="sa-select-chevron"><span class="material-symbols-rounded" style="font-size:18px;">expand_more</span></span>
                            </div>
                        </div>
                        <div class="sa-field-group">
                            <label class="sa-field-label">Fallback Engine</label>
                            <div class="sa-luxury-box">
                                <span class="sa-box-icon"><span class="material-symbols-rounded">layers</span></span>
                                <select class="sa-luxury-select" name="base_template" id="edit_base">
                                    <option value="classic" <?php echo ($baseTemplate === 'classic') ? 'selected' : ''; ?>>Classic Receipt</option>
                                    <option value="retail_standard" <?php echo ($baseTemplate === 'retail_standard') ? 'selected' : ''; ?>>Master Retail Standard</option>
                                    <option value="compact" <?php echo ($baseTemplate === 'compact') ? 'selected' : ''; ?>>Compact Thermal (58mm)</option>
                                    <option value="thermal_mono" <?php echo ($baseTemplate === 'thermal_mono') ? 'selected' : ''; ?>>Monospace Thermal</option>
                                    <option value="premium" <?php echo ($baseTemplate === 'premium') ? 'selected' : ''; ?>>Premium Soft</option>
                                    <option value="invoice" <?php echo ($baseTemplate === 'invoice') ? 'selected' : ''; ?>>Invoice Pro (Document)</option>
                                    <option value="executive" <?php echo ($baseTemplate === 'executive') ? 'selected' : ''; ?>>Executive Ledger</option>
                                    <option value="emerald" <?php echo ($baseTemplate === 'emerald') ? 'selected' : ''; ?>>Emerald Statement</option>
                                    <option value="monogram" <?php echo ($baseTemplate === 'monogram') ? 'selected' : ''; ?>>Monogram Bill</option>
                                </select>
                                <span class="sa-select-chevron"><span class="material-symbols-rounded" style="font-size:18px;">expand_more</span></span>
                            </div>
                        </div>
                        <div class="sa-field-group">
                            <label class="sa-field-label">Status</label>
                            <div class="sa-luxury-box">
                                <span class="sa-box-icon"><span class="material-symbols-rounded">toggle_on</span></span>
                                <select class="sa-luxury-select" name="status">
                                    <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active (Live in Stores)</option>
                                    <option value="draft" <?php echo ($status === 'draft') ? 'selected' : ''; ?>>Draft (Testing)</option>
                                    <option value="archived" <?php echo ($status === 'archived') ? 'selected' : ''; ?>>Archived</option>
                                </select>
                                <span class="sa-select-chevron"><span class="material-symbols-rounded" style="font-size:18px;">expand_more</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Full Typography & Styling Studio -->
                <div class="sa-section-box">
                    <div class="sa-section-title"><span class="material-symbols-rounded" style="font-size:16px; color:#10b981;">format_paint</span> Typography & Styling Studio (A to Z Customization)</div>
                    <div class="sa-form-grid">
                        <!-- Font Family (16 Curated Fonts) -->
                        <div class="sa-field-group">
                            <label class="sa-field-label">Font Family (10+ Pro Designs)</label>
                            <div class="sa-luxury-box">
                                <span class="sa-box-icon"><span class="material-symbols-rounded">font_download</span></span>
                                <select class="sa-luxury-select" name="font_family" id="edit_font">
                                    <optgroup label="🌟 Modern & Crisp Sans">
                                        <option value="Inter" style="font-family:'Inter', sans-serif;" <?php echo ($fontFamily === 'Inter') ? 'selected' : ''; ?>>Inter (Modern Sans)</option>
                                        <option value="Outfit" style="font-family:'Outfit', sans-serif;" <?php echo ($fontFamily === 'Outfit') ? 'selected' : ''; ?>>Outfit (Contemporary UI)</option>
                                        <option value="Poppins" style="font-family:'Poppins', sans-serif;" <?php echo ($fontFamily === 'Poppins') ? 'selected' : ''; ?>>Poppins (Geometric Pro)</option>
                                        <option value="Segoe UI" style="font-family:'Segoe UI', sans-serif;" <?php echo ($fontFamily === 'Segoe UI') ? 'selected' : ''; ?>>Segoe UI (Ultra Crisp)</option>
                                        <option value="Roboto" style="font-family:'Roboto', sans-serif;" <?php echo ($fontFamily === 'Roboto') ? 'selected' : ''; ?>>Roboto (Google High-DPI)</option>
                                        <option value="Montserrat" style="font-family:'Montserrat', sans-serif;" <?php echo ($fontFamily === 'Montserrat') ? 'selected' : ''; ?>>Montserrat (Branded Bold)</option>
                                        <option value="Arial" style="font-family:Arial, sans-serif;" <?php echo ($fontFamily === 'Arial') ? 'selected' : ''; ?>>Arial (Standard Clean)</option>
                                    </optgroup>
                                    <optgroup label="🧾 Thermal POS Monospace">
                                        <option value="Courier New" style="font-family:'Courier New', monospace;" <?php echo ($fontFamily === 'Courier New') ? 'selected' : ''; ?>>Courier New (Classic Retro)</option>
                                        <option value="Space Mono" style="font-family:'Space Mono', monospace;" <?php echo ($fontFamily === 'Space Mono') ? 'selected' : ''; ?>>Space Mono (Tech Clean)</option>
                                        <option value="JetBrains Mono" style="font-family:'JetBrains Mono', monospace;" <?php echo ($fontFamily === 'JetBrains Mono') ? 'selected' : ''; ?>>JetBrains Mono (Developer Legibility)</option>
                                        <option value="Cascadia Code" style="font-family:'Cascadia Code', monospace;" <?php echo ($fontFamily === 'Cascadia Code') ? 'selected' : ''; ?>>Cascadia Code (Crisp Terminal)</option>
                                        <option value="Fira Code" style="font-family:'Fira Code', monospace;" <?php echo ($fontFamily === 'Fira Code') ? 'selected' : ''; ?>>Fira Code (Modern Monospace)</option>
                                    </optgroup>
                                    <optgroup label="💎 Luxury Boutique & Editorial">
                                        <option value="Cinzel" style="font-family:'Cinzel', serif;" <?php echo ($fontFamily === 'Cinzel') ? 'selected' : ''; ?>>Cinzel (Haute Couture / Jewelry)</option>
                                        <option value="Playfair Display" style="font-family:'Playfair Display', serif;" <?php echo ($fontFamily === 'Playfair Display') ? 'selected' : ''; ?>>Playfair Display (Luxury Serif)</option>
                                        <option value="Oswald" style="font-family:'Oswald', sans-serif;" <?php echo ($fontFamily === 'Oswald') ? 'selected' : ''; ?>>Oswald (Condensed Impact)</option>
                                        <option value="Times New Roman" style="font-family:'Times New Roman', serif;" <?php echo ($fontFamily === 'Times New Roman') ? 'selected' : ''; ?>>Times New Roman (Classic Heritage)</option>
                                    </optgroup>
                                </select>
                                <span class="sa-select-chevron"><span class="material-symbols-rounded" style="font-size:18px;">expand_more</span></span>
                            </div>
                        </div>

                        <!-- Font Weight -->
                        <div class="sa-field-group">
                            <label class="sa-field-label">Font Weight</label>
                            <div class="sa-luxury-box">
                                <span class="sa-box-icon"><span class="material-symbols-rounded">format_bold</span></span>
                                <select class="sa-luxury-select" id="custom_font_weight">
                                    <option value="400">Regular (400)</option>
                                    <option value="500">Medium (500)</option>
                                    <option value="600">Semi-Bold (600)</option>
                                    <option value="700" selected>Bold (700) [Recommended]</option>
                                    <option value="800">Extra-Bold (800) [203 DPI Crisp]</option>
                                    <option value="900">Black (900)</option>
                                </select>
                                <span class="sa-select-chevron"><span class="material-symbols-rounded" style="font-size:18px;">expand_more</span></span>
                            </div>
                        </div>

                        <!-- Font Style -->
                        <div class="sa-field-group">
                            <label class="sa-field-label">Font Style</label>
                            <div class="sa-luxury-box">
                                <span class="sa-box-icon"><span class="material-symbols-rounded">format_italic</span></span>
                                <select class="sa-luxury-select" id="custom_font_style">
                                    <option value="normal" selected>Normal</option>
                                    <option value="italic">Italic (Emphasis)</option>
                                </select>
                                <span class="sa-select-chevron"><span class="material-symbols-rounded" style="font-size:18px;">expand_more</span></span>
                            </div>
                        </div>

                        <!-- Letter Spacing -->
                        <div class="sa-field-group">
                            <label class="sa-field-label">Letter Spacing</label>
                            <div class="sa-luxury-box">
                                <span class="sa-box-icon"><span class="material-symbols-rounded">space_bar</span></span>
                                <select class="sa-luxury-select" id="custom_letter_spacing">
                                    <option value="-0.3px">Compact (-0.3px)</option>
                                    <option value="normal" selected>Normal (0px)</option>
                                    <option value="0.4px">Subtle (+0.4px)</option>
                                    <option value="0.8px">Spaced (+0.8px)</option>
                                    <option value="1.2px">Wide (+1.2px)</option>
                                </select>
                                <span class="sa-select-chevron"><span class="material-symbols-rounded" style="font-size:18px;">expand_more</span></span>
                            </div>
                        </div>

                        <!-- Line Height -->
                        <div class="sa-field-group">
                            <label class="sa-field-label">Line Height</label>
                            <div class="sa-luxury-box">
                                <span class="sa-box-icon"><span class="material-symbols-rounded">format_line_spacing</span></span>
                                <select class="sa-luxury-select" id="custom_line_height">
                                    <option value="1.25">Compact (1.25)</option>
                                    <option value="1.45" selected>Normal (1.45)</option>
                                    <option value="1.65">Relaxed (1.65)</option>
                                </select>
                                <span class="sa-select-chevron"><span class="material-symbols-rounded" style="font-size:18px;">expand_more</span></span>
                            </div>
                        </div>

                        <!-- Accent Color -->
                        <div class="sa-field-group">
                            <label class="sa-field-label">Accent Color</label>
                            <div class="sa-color-control-wrap">
                                <div class="sa-color-preview-disc" id="accent_color_disc" style="background-color:<?php echo Security::escape($accent); ?>;" title="Click to choose accent color">
                                    <input type="color" name="accent_color" id="edit_accent" value="<?php echo Security::escape($accent); ?>" class="sa-color-native-input">
                                </div>
                                <div class="sa-luxury-box" style="flex:1;">
                                    <span class="sa-box-icon"><span class="material-symbols-rounded">palette</span></span>
                                    <input class="sa-luxury-input" type="text" id="edit_accent_hex" value="<?php echo Security::escape($accent); ?>" placeholder="#0F6DFF" style="font-family:monospace; text-transform:uppercase; letter-spacing:0.5px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: HTML Code Editor -->
                <div>
                    <div class="sa-code-toolbar">
                        <div style="display:flex; gap:6px; align-items:center;">
                            <span style="font-size:11.5px; font-weight:900; color:#0f172a;"><span class="material-symbols-rounded" style="font-size:16px; color:#0f6dff; vertical-align:middle;">code</span> Template HTML Block</span>
                        </div>
                        <div style="display:flex; gap:5px; align-items:center;">
                            <button type="button" class="sa-chip" id="btn_apply_typography"><span class="material-symbols-rounded" style="font-size:14px; color:#10b981;">sync</span> Apply Typography to Code</button>
                            <button type="button" class="sa-chip" id="btn_format_code"><span class="material-symbols-rounded" style="font-size:14px;">auto_fix_high</span> Clean</button>
                        </div>
                    </div>

                    <textarea class="sa-code-textarea" name="html_block" id="template_code_editor" spellcheck="false"><?php echo Security::escape($htmlBlock); ?></textarea>

                    <div class="sa-tokens-bar">
                        <span style="font-size:10.5px; font-weight:800; color:#64748b;">Insert Token:</span>
                        <span class="sa-token-chip" data-tok="{{company_name}}">{{company_name}}</span>
                        <span class="sa-token-chip" data-tok="{{invoice_no}}">{{invoice_no}}</span>
                        <span class="sa-token-chip" data-tok="{{date}}">{{date}}</span>
                        <span class="sa-token-chip" data-tok="{{items}}">{{items}}</span>
                        <span class="sa-token-chip" data-tok="{{subtotal}}">{{subtotal}}</span>
                        <span class="sa-token-chip" data-tok="{{total}}">{{total}}</span>
                        <span class="sa-token-chip" data-tok="{{paid}}">{{paid}}</span>
                        <span class="sa-token-chip" data-tok="{{remaining}}">{{remaining}}</span>
                        <span class="sa-token-chip" data-tok="{{qr}}">{{qr}}</span>
                        <span class="sa-token-chip" data-tok="{{developer_credit}}">{{developer_credit}}</span>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Real-Time Interactive Live Preview -->
            <div class="sa-panel">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <div>
                        <div class="sa-panel-title"><span class="material-symbols-rounded" style="font-size:18px; color:#0f6dff;">preview</span> Live Paper Preview</div>
                        <div style="font-size:11px; color:#64748b; font-weight:600;" id="preview_info_line">80mm Thermal POS simulation with realistic dummy data.</div>
                    </div>
                    <span class="badge badge-soft" id="preview_badge_width"><?php echo (int) $width; ?>mm</span>
                </div>

                <div class="sa-preview-container">
                    <div class="sa-live-paper" id="paper_preview_box" style="font-family:'<?php echo Security::escape($fontFamily); ?>', sans-serif;"></div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php ob_start(); ?>
<script>
(() => {
    const editor = document.getElementById('template_code_editor');
    const paper = document.getElementById('paper_preview_box');
    const typeInput = document.getElementById('edit_type');
    const widthSelect = document.getElementById('edit_width');
    const fontSelect = document.getElementById('edit_font');
    const weightSelect = document.getElementById('custom_font_weight');
    const styleSelect = document.getElementById('custom_font_style');
    const spacingSelect = document.getElementById('custom_letter_spacing');
    const lineHeightSelect = document.getElementById('custom_line_height');
    const accentColor = document.getElementById('edit_accent');
    const accentHex = document.getElementById('edit_accent_hex');
    const badgeWidth = document.getElementById('preview_badge_width');
    const infoLine = document.getElementById('preview_info_line');
    const indicatorBadge = document.getElementById('type_badge_indicator');
    const toggleButtons = Array.from(document.querySelectorAll('[data-toggle-type]'));

    // Realistic store dummy data
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
        if (!editor || !paper) return;
        let html = editor.value || '';
        Object.entries(tokens).forEach(([token, value]) => {
            html = html.split(token).join(value);
        });
        paper.innerHTML = html;
    };

    const updatePaperDimensions = () => {
        const type = typeInput.value;
        const width = parseInt(widthSelect.value, 10);
        paper.classList.remove('is-thermal-80', 'is-thermal-58', 'is-document-a5', 'is-document-a4');

        if (type === 'thermal') {
            if (width <= 58) {
                paper.classList.add('is-thermal-58');
                badgeWidth.textContent = '58mm Roll';
                infoLine.textContent = '58mm Compact thermal roll simulation.';
            } else {
                paper.classList.add('is-thermal-80');
                badgeWidth.textContent = '80mm Roll';
                infoLine.textContent = '80mm Standard POS thermal roll simulation.';
            }
            if (indicatorBadge) indicatorBadge.textContent = 'Thermal POS (' + width + 'mm)';
        } else {
            if (width >= 210) {
                paper.classList.add('is-document-a4');
                badgeWidth.textContent = '210mm A4';
                infoLine.textContent = '210mm A4 full invoice document simulation.';
            } else {
                paper.classList.add('is-document-a5');
                badgeWidth.textContent = '148mm A5';
                infoLine.textContent = '148mm A5 standard invoice document simulation.';
            }
            if (indicatorBadge) indicatorBadge.textContent = 'Document Invoice (' + width + 'mm)';
        }

        // Apply typography directly to paper
        paper.style.fontFamily = "'" + fontSelect.value.replace(/'/g, '') + "', 'Segoe UI', Arial, sans-serif";
        paper.style.fontWeight = weightSelect.value;
        paper.style.fontStyle = styleSelect.value;
        paper.style.letterSpacing = spacingSelect.value;
        if (lineHeightSelect) paper.style.lineHeight = lineHeightSelect.value;
        paper.style.setProperty('--receipt-accent', accentColor.value);
    };

    toggleButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const chosenType = btn.dataset.toggleType;
            typeInput.value = chosenType;
            toggleButtons.forEach((b) => b.classList.toggle('active', b === btn));
            if (chosenType === 'thermal' && (widthSelect.value === '148' || widthSelect.value === '210')) {
                widthSelect.value = '80';
            } else if (chosenType === 'document' && (widthSelect.value === '80' || widthSelect.value === '58')) {
                widthSelect.value = '148';
            }
            updatePaperDimensions();
            render();
        });
    });

    // Accent color sync
    const accentDisc = document.getElementById('accent_color_disc');
    const updateAccent = (hex) => {
        if (accentDisc) accentDisc.style.backgroundColor = hex;
        updatePaperDimensions();
    };

    accentColor.addEventListener('input', () => {
        accentHex.value = accentColor.value;
        updateAccent(accentColor.value);
    });
    accentHex.addEventListener('input', () => {
        let val = accentHex.value.trim();
        if (!val.startsWith('#') && /^[0-9A-Fa-f]{6}$/.test(val)) {
            val = '#' + val;
            accentHex.value = val;
        }
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            accentColor.value = val;
            updateAccent(val);
        }
    });

    // Font and styling live triggers
    [fontSelect, weightSelect, styleSelect, spacingSelect, lineHeightSelect, widthSelect].filter(Boolean).forEach((el) => {
        el.addEventListener('change', () => {
            updatePaperDimensions();
            render();
        });
    });

    // Insert token
    document.querySelectorAll('[data-tok]').forEach((chip) => {
        chip.addEventListener('click', () => {
            const tok = chip.dataset.tok;
            const start = editor.selectionStart || 0;
            const end = editor.selectionEnd || 0;
            const val = editor.value;
            editor.value = val.substring(0, start) + tok + val.substring(end);
            editor.selectionStart = editor.selectionEnd = start + tok.length;
            editor.focus();
            render();
        });
    });

    // Apply Typography to Code directly
    document.getElementById('btn_apply_typography')?.addEventListener('click', () => {
        const font = fontSelect.value;
        const weight = weightSelect.value;
        const style = styleSelect.value;
        const spacing = spacingSelect.value;
        let code = editor.value;
        // Inject or update root styling
        code = code.replace(/font-family:[^;"]+/i, "font-family:'" + font + "', sans-serif");
        code = code.replace(/font-weight:[^;"]+/i, "font-weight:" + weight);
        editor.value = code;
        render();
        alert('Typography rules applied to HTML code!');
    });

    // Toggle Print High-Contrast View
    let isPrintContrast = false;
    document.querySelector('[data-preview-mode-toggle]')?.addEventListener('click', function() {
        isPrintContrast = !isPrintContrast;
        if (isPrintContrast) {
            paper.style.filter = 'grayscale(100%) contrast(150%)';
            this.classList.add('btn-primary');
            this.classList.remove('btn-secondary');
            this.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px;">visibility</span> Screen Color View';
        } else {
            paper.style.filter = 'none';
            this.classList.remove('btn-primary');
            this.classList.add('btn-secondary');
            this.innerHTML = '<span class="material-symbols-rounded" style="font-size:16px;">print</span> Print Contrast View';
        }
    });

    editor.addEventListener('input', render);
    updatePaperDimensions();
    render();
})();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
