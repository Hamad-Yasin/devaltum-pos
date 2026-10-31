<?php ob_start(); ?>
<?php
$product = $product ?? null;
$activeStore = Tenant::activeStore();
$defaultStoreName = $activeStore['name'] ?? App::setting('company_name', 'DevAltum Store');
$currency = App::setting('currency_symbol', 'Rs');
$productName = $product['name'] ?? 'Sample Product';
$productBarcode = App::normalizeBarcode($product['barcode'] ?? '');
$displayBarcode = $productBarcode ?: ('200' . str_pad((string)($product['id'] ?? 1), 6, '0', STR_PAD_LEFT) . '123');
$productPrice = (float)($product['price'] ?? 0);
$productModel = $product['model_number'] ?? '';
$productUnit = App::formatProductUnit($product['unit_value'] ?? 1, $product['unit_type'] ?? 'pcs');
?>

<style>
    /* ========================================================
       PRO BARCODE & MS WORD EXPORT STUDIO
       100% Offline, Pixel-Perfect Layout, Live Sheet Preview
       ======================================================== */
    .barcode-studio-wrapper {
        max-width: 1560px;
        margin: 0 auto;
        color: #0f172a;
    }

    /* Top Studio Header */
    .studio-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 20px;
    }
    .studio-header h1 {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 4px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .studio-header p {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }
    .studio-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* Two-Column Studio Layout */
    .studio-grid {
        display: grid;
        grid-template-columns: 390px minmax(0, 1fr);
        gap: 22px;
        align-items: start;
    }
    @media (max-width: 1024px) {
        .studio-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Left Settings Card */
    .studio-settings-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
        padding: 22px;
        position: sticky;
        top: 20px;
    }

    /* Right Preview Card */
    .studio-preview-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
        padding: 24px;
        display: flex;
        flex-direction: column;
        min-height: 700px;
    }

    /* Active Product Summary Box */
    .active-prod-box {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #ffffff;
        border-radius: 14px;
        padding: 14px 16px;
        margin-bottom: 18px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.15);
    }
    .active-prod-title {
        font-size: 15px;
        font-weight: 800;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 4px;
    }
    .active-prod-meta {
        font-size: 12px;
        color: #94a3b8;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .active-prod-price {
        font-size: 16px;
        font-weight: 900;
        color: #38bdf8;
    }

    /* Control Groups */
    .cfg-group-title {
        font-size: 11px;
        font-weight: 800;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin: 16px 0 8px;
        padding-bottom: 4px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .cfg-group-title .material-symbols-rounded {
        font-size: 16px;
        color: #0284c7;
    }

    .cfg-field {
        margin-bottom: 12px;
    }
    .cfg-label {
        font-size: 12px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 5px;
        display: block;
    }
    .cfg-input, .cfg-select {
        width: 100%;
        height: 38px;
        padding: 0 12px;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 600;
        color: #0f172a;
        background: #f8fafc;
        box-sizing: border-box;
        transition: all 0.15s ease;
    }
    .cfg-input:focus, .cfg-select:focus {
        border-color: #0284c7;
        background: #ffffff;
        outline: none;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }

    /* Quantity Stepper */
    .qty-stepper-box {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
    .qty-step-btn {
        width: 44px;
        height: 40px;
        border-radius: 9px;
        border: 1px solid #cbd5e1;
        background: #f1f5f9;
        color: #0f172a;
        font-size: 18px;
        font-weight: 900;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.12s ease;
    }
    .qty-step-btn:hover {
        background: #e2e8f0;
        border-color: #94a3b8;
    }
    .qty-input {
        flex: 1;
        height: 40px;
        text-align: center;
        font-size: 18px;
        font-weight: 900;
        border: 2px solid #0284c7;
        border-radius: 9px;
        color: #0f172a;
        background: #ffffff;
    }

    /* Quick Quantity Pills */
    .qty-pills {
        display: flex;
        gap: 6px;
        margin-bottom: 14px;
    }
    .qty-pill {
        flex: 1;
        padding: 5px 0;
        font-size: 11.5px;
        font-weight: 800;
        color: #475569;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        cursor: pointer;
        text-align: center;
        transition: all 0.12s ease;
    }
    .qty-pill:hover {
        background: #e0f2fe;
        color: #0284c7;
        border-color: #bae6fd;
    }

    /* Action Buttons */
    .btn-export-docx {
        width: 100%;
        height: 44px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff;
        font-size: 14px;
        font-weight: 800;
        border: none;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
        transition: all 0.15s ease;
    }
    .btn-export-docx:hover {
        background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(2, 132, 199, 0.45);
    }
    .btn-export-docx .material-symbols-rounded {
        font-size: 21px;
    }

    /* Preview Canvas Area */
    .preview-head-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 14px;
        margin-bottom: 18px;
        border-bottom: 1px solid #e2e8f0;
        flex-wrap: wrap;
        gap: 10px;
    }
    .preview-badge-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 750;
    }

    .sheet-canvas {
        background: #f8fafc;
        border: 1.5px dashed #cbd5e1;
        border-radius: 14px;
        padding: 24px;
        min-height: 580px;
        max-height: 720px;
        overflow-y: auto;
        box-shadow: inset 0 2px 6px rgba(0,0,0,0.03);
    }

    /* Grid of Stickers on Preview Sheet */
    .stickers-preview-grid {
        display: grid;
        gap: 10px;
        max-width: 900px;
        margin: 0 auto;
    }
    .stickers-preview-grid.cols-3 {
        grid-template-columns: repeat(3, 1fr);
    }
    .stickers-preview-grid.cols-2 {
        grid-template-columns: repeat(2, 1fr);
    }
    .stickers-preview-grid.cols-1 {
        grid-template-columns: 1fr;
    }

    /* Single Sticker Box (Realistic) */
    .sticker-item {
        background: #ffffff;
        border: 1px dashed #94a3b8;
        border-radius: 8px;
        padding: 10px 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        position: relative;
        box-sizing: border-box;
        min-height: 110px;
    }
    .sticker-store-name {
        font-size: 11px;
        font-weight: 900;
        color: #0b132b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.2;
        margin-bottom: 2px;
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sticker-product-name {
        font-size: 10.5px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
        margin-bottom: 4px;
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sticker-barcode-svg {
        max-width: 100%;
        height: 38px;
        display: block;
        margin: 0 auto 2px;
    }
    .sticker-barcode-text {
        font-family: 'Consolas', 'Courier New', monospace;
        font-size: 9.5px;
        font-weight: 700;
        color: #475569;
        letter-spacing: 1px;
        line-height: 1;
        margin-bottom: 4px;
    }
    .sticker-price {
        font-size: 13.5px;
        font-weight: 900;
        color: #059669;
        line-height: 1;
    }

    /* Print Style Overrides (If user does browser print) */
    @media print {
        body * {
            visibility: hidden;
        }
        #printable-sheet-area, #printable-sheet-area * {
            visibility: visible;
        }
        #printable-sheet-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            background: transparent !important;
            border: none !important;
        }
        .studio-settings-card, .studio-header, .preview-head-bar {
            display: none !important;
        }
    }
</style>

<div class="barcode-studio-wrapper">
    <!-- Top Header -->
    <div class="studio-header">
        <div>
            <h1>
                <span class="material-symbols-rounded" style="color:#0284c7; font-size:28px;">barcode_scanner</span>
                Barcode Label & MS Word Studio
            </h1>
            <p>Generate, preview, and export pixel-perfect barcode stickers into <strong>Microsoft Word (.docx)</strong> or print via thermal printer.</p>
        </div>
        <div class="studio-actions">
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/index?tab=list" class="btn btn-secondary" style="height:40px; border-radius:10px; display:inline-flex; align-items:center; gap:6px;">
                <span class="material-symbols-rounded">arrow_back</span>
                Back to Inventory
            </a>
            <button type="button" class="btn btn-primary" onclick="submitWordExportForm()" style="height:40px; border-radius:10px; display:inline-flex; align-items:center; gap:6px; background:#0284c7; border-color:#0284c7; font-weight:800;">
                <span class="material-symbols-rounded">download</span>
                Download MS Word (.docx)
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.print()" style="height:40px; border-radius:10px; display:inline-flex; align-items:center; gap:6px;">
                <span class="material-symbols-rounded">print</span>
                Quick Thermal Print
            </button>
        </div>
    </div>

    <!-- Main Studio Grid -->
    <div class="studio-grid">
        <!-- LEFT: Settings & Inputs Panel -->
        <div class="studio-settings-card">
            <!-- Active Product Info -->
            <div class="active-prod-box">
                <div class="active-prod-title" id="disp-top-name"><?= Security::escape($productName) ?></div>
                <div class="active-prod-meta">
                    <span>Barcode: <strong id="disp-top-barcode" style="color:#e2e8f0;"><?= Security::escape($displayBarcode) ?></strong></span>
                    <span class="active-prod-price"><?= $currency ?> <span id="disp-top-price"><?= number_format($productPrice, 2) ?></span></span>
                </div>
            </div>

            <!-- Hidden Export Form -->
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/exportBarcodeDocx" method="POST" id="word-export-form" target="_blank">
                <?= Security::csrfField() ?>
                <input type="hidden" name="product_id" value="<?= (int)($product['id'] ?? 0) ?>">

                <!-- 1. Content Customization -->
                <div class="cfg-group-title">
                    <span class="material-symbols-rounded">tune</span>
                    1. Sticker Content & Store Header
                </div>

                <div class="cfg-field">
                    <label class="cfg-label">Store Name (Printed on Top of Each Sticker) *</label>
                    <input type="text" name="store_name" id="in-store-name" class="cfg-input" value="<?= Security::escape($defaultStoreName) ?>" oninput="updateLivePreview()" required>
                </div>

                <div class="cfg-field">
                    <label class="cfg-label">Product Name *</label>
                    <input type="text" name="product_name" id="in-prod-name" class="cfg-input" value="<?= Security::escape($productName) ?>" oninput="updateLivePreview()" required>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div class="cfg-field">
                        <label class="cfg-label">Price (<?= $currency ?>) *</label>
                        <input type="number" step="0.01" min="0" name="price" id="in-price" class="cfg-input" value="<?= $productPrice ?>" oninput="updateLivePreview()" required>
                    </div>

                    <div class="cfg-field">
                        <label class="cfg-label">Barcode Number (Code128) *</label>
                        <input type="text" name="barcode" id="in-barcode" class="cfg-input" value="<?= Security::escape($displayBarcode) ?>" oninput="updateLivePreview()" required>
                    </div>
                </div>

                <!-- 2. Quantity & Grid Setup -->
                <div class="cfg-group-title">
                    <span class="material-symbols-rounded">format_list_numbered</span>
                    2. Quantity & Sticker Grid
                </div>

                <div class="cfg-field">
                    <label class="cfg-label">Total Stickers to Generate *</label>
                    <div class="qty-stepper-box">
                        <button type="button" class="qty-step-btn" onclick="adjustQty(-1)">−</button>
                        <input type="number" name="quantity" id="in-quantity" class="qty-input" value="30" min="1" max="1000" oninput="updateLivePreview()">
                        <button type="button" class="qty-step-btn" onclick="adjustQty(1)">+</button>
                    </div>
                    <div class="qty-pills">
                        <button type="button" class="qty-pill" onclick="setQty(3)">3 (1 Row)</button>
                        <button type="button" class="qty-pill" onclick="setQty(15)">15 (5 Rows)</button>
                        <button type="button" class="qty-pill" onclick="setQty(30)">30 (10 Rows)</button>
                        <button type="button" class="qty-pill" onclick="setQty(60)">60 (20 Rows)</button>
                        <button type="button" class="qty-pill" onclick="setQty(100)">100 Pcs</button>
                    </div>
                </div>

                <div class="cfg-field">
                    <label class="cfg-label">Sticker Layout Columns</label>
                    <select name="columns" id="in-columns" class="cfg-select" onchange="updateLivePreview()">
                        <option value="3" selected>3 Stickers per Row (Standard 3-Up 110x25mm / A4)</option>
                        <option value="2">2 Stickers per Row (50x30mm / A4)</option>
                        <option value="1">1 Sticker per Row (Single Roll)</option>
                    </select>
                </div>

                <!-- Action Button -->
                <div style="margin-top:20px;">
                    <button type="submit" class="btn-export-docx">
                        <span class="material-symbols-rounded">download_for_offline</span>
                        Export to MS Word (.docx)
                    </button>
                    <small style="display:block; text-align:center; color:#64748b; font-size:11px; margin-top:8px;">
                        Opens directly in Microsoft Word with pixel-perfect table dimensions.
                    </small>
                </div>
            </form>
        </div>

        <!-- RIGHT: Live Realistic Sheet Preview -->
        <div class="studio-preview-card">
            <div class="preview-head-bar">
                <div>
                    <h3 style="font-size:16px; font-weight:800; color:#0f172a; margin:0 0 2px;">Live Sheet Preview</h3>
                    <span style="font-size:12px; color:#64748b;" id="preview-meta-subtitle">Showing 30 Stickers (10 Rows × 3 Columns)</span>
                </div>
                <div class="preview-badge-status">
                    <span class="material-symbols-rounded" style="font-size:16px;">verified</span>
                    <span id="preview-badge-text">30 Stickers Ready</span>
                </div>
            </div>

            <!-- Scrollable Canvas -->
            <div class="sheet-canvas" id="printable-sheet-area">
                <div class="stickers-preview-grid cols-3" id="stickers-grid">
                    <!-- Dynamic Stickers Generated via JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Local JsBarcode Library for live offline SVG generation -->
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/JsBarcode.all.min.js"></script>

<script>
(() => {
    const currency = '<?= $currency ?>';

    // Quantity Adjustment Helpers
    window.adjustQty = (delta) => {
        const input = document.getElementById('in-quantity');
        let val = parseInt(input.value || '1', 10);
        val = Math.max(1, Math.min(1000, val + delta));
        input.value = val;
        window.updateLivePreview();
    };

    window.setQty = (qty) => {
        const input = document.getElementById('in-quantity');
        input.value = Math.max(1, Math.min(1000, qty));
        window.updateLivePreview();
    };

    window.submitWordExportForm = () => {
        const form = document.getElementById('word-export-form');
        if (form) form.submit();
    };

    // Live Preview Rendering
    window.updateLivePreview = () => {
        const storeName = document.getElementById('in-store-name')?.value?.trim() || 'Store Name';
        const prodName = document.getElementById('in-prod-name')?.value?.trim() || 'Product Name';
        const rawPrice = parseFloat(document.getElementById('in-price')?.value || '0') || 0;
        const barcode = document.getElementById('in-barcode')?.value?.trim() || '123456789012';
        const qty = Math.max(1, Math.min(1000, parseInt(document.getElementById('in-quantity')?.value || '30', 10)));
        const columns = parseInt(document.getElementById('in-columns')?.value || '3', 10);

        // Update Mini Header Box
        const topName = document.getElementById('disp-top-name');
        if (topName) topName.textContent = prodName;
        const topBarcode = document.getElementById('disp-top-barcode');
        if (topBarcode) topBarcode.textContent = barcode;
        const topPrice = document.getElementById('disp-top-price');
        if (topPrice) topPrice.textContent = rawPrice.toFixed(2);

        // Update Preview Subtitle
        const rows = Math.ceil(qty / columns);
        const subTitle = document.getElementById('preview-meta-subtitle');
        if (subTitle) subTitle.textContent = `Showing ${qty} Stickers (${rows} Rows × ${columns} Columns)`;
        const badge = document.getElementById('preview-badge-text');
        if (badge) badge.textContent = `${qty} Stickers Ready`;

        // Update Grid Columns Class
        const grid = document.getElementById('stickers-grid');
        if (!grid) return;

        grid.className = `stickers-preview-grid cols-${columns}`;

        // Build HTML for stickers
        let html = '';
        const formattedPrice = `${currency} ${rawPrice.toFixed(2)}`;

        for (let i = 0; i < qty; i++) {
            html += `
                <div class="sticker-item">
                    <div class="sticker-store-name">${escapeHtml(storeName)}</div>
                    <div class="sticker-product-name">${escapeHtml(prodName)}</div>
                    <svg class="sticker-barcode-svg" id="barcode-svg-${i}"></svg>
                    <div class="sticker-barcode-text">${escapeHtml(barcode)}</div>
                    <div class="sticker-price">${escapeHtml(formattedPrice)}</div>
                </div>
            `;
        }

        grid.innerHTML = html;

        // Render Barcodes via JsBarcode
        if (window.JsBarcode) {
            for (let i = 0; i < qty; i++) {
                const el = document.getElementById(`barcode-svg-${i}`);
                if (el) {
                    try {
                        JsBarcode(el, barcode, {
                            format: 'CODE128',
                            lineColor: '#000000',
                            width: 1.4,
                            height: 32,
                            displayValue: false,
                            margin: 0
                        });
                    } catch (e) {
                        // Fallback if barcode contains invalid Code128 chars
                        console.warn('JsBarcode render error:', e);
                    }
                }
            }
        }
    };

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Initialize on page load
    setTimeout(() => {
        window.updateLivePreview();
    }, 50);
})();
</script>

<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
