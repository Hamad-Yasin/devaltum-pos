<?php ob_start(); ?>
<?php
$product = $product ?? null;
$storeName = App::setting('company_name', 'DevAltum POS');
$currency = App::setting('currency_symbol', 'Rs');
$productName = $product['name'] ?? 'Sample Product';
$productBarcode = App::normalizeBarcode($product['barcode'] ?? '');
$displayBarcode = $productBarcode ?: ('200' . str_pad((string)($product['id'] ?? 1), 6, '0', STR_PAD_LEFT) . '123');
$productPrice = (float)($product['price'] ?? 0);
$productModel = $product['model_number'] ?? '';
$productExpiry = $product['expiry_date'] ?? '';
$productBatch = $product['batch_number'] ?? '';
$productUnit = App::formatProductUnit($product['unit_value'] ?? 1, $product['unit_type'] ?? 'pcs');
?>

<!-- Dynamic @page CSS injected via JavaScript -->
<style id="dynamic-page-size-style">
    @media print {
        @page {
            size: 110mm 25mm portrait !important;
            margin: 0mm !important;
        }
    }
</style>

<style>
    :root {
        --roll-width: 110mm;
        --sticker-width: 34.5mm;
        --sticker-height: 25mm;
        --col-gap: 2mm;
        --row-gap: 2mm;
        --side-margin: 1.25mm;
        --top-margin: 0mm;
        --left-margin: 0mm;
        --title-font-size: 8px;
        --price-font-size: 10px;
        --barcode-height: 22px;
        --barcode-bar-width: 1.15;
        --sticker-rotation: 0deg;
    }

    .barcode-studio-grid {
        display: grid;
        grid-template-columns: 410px minmax(0, 1fr);
        gap: 22px;
        align-items: start;
        max-width: 1600px;
        margin: 0 auto;
        width: 100%;
    }

    .settings-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        padding: 20px;
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 40px);
        overflow-y: auto;
    }

    .preview-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        padding: 20px;
        display: flex;
        flex-direction: column;
        min-height: 720px;
    }

    /* Target Product Card */
    .product-mini-card {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        border-radius: 12px;
        padding: 12px 14px;
        color: #ffffff;
        margin-bottom: 14px;
    }
    .product-mini-title {
        font-size: 14px;
        font-weight: 900;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 3px;
    }
    .product-mini-meta {
        font-size: 11px;
        color: #94a3b8;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .product-mini-price {
        font-size: 15px;
        font-weight: 900;
        color: #22c55e;
    }

    /* Section Control Header */
    .ctrl-group-title {
        font-size: 11px;
        font-weight: 900;
        color: #0f172a;
        margin-top: 14px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 4px;
    }
    .ctrl-group-title .title-left {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .ctrl-group-title .material-symbols-rounded {
        color: #16a34a;
        font-size: 17px;
    }

    /* Steppers & Quantity */
    .stepper-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
    .stepper-btn {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        width: 44px;
        height: 38px;
        font-size: 16px;
        font-weight: 900;
        color: #0f172a;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }
    .stepper-btn:hover { background: #e2e8f0; }
    .stepper-input {
        flex: 1;
        height: 38px;
        text-align: center;
        font-size: 16px;
        font-weight: 900;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        color: #0f172a;
    }
    .stepper-input:focus {
        border-color: #16a34a;
        outline: none;
    }

    .quick-chips-row {
        display: flex;
        gap: 5px;
        overflow-x: auto;
        padding: 2px 2px 8px 2px;
        margin-bottom: 6px;
    }
    .chip-btn {
        flex: 0 0 auto;
        padding: 5px 10px;
        border-radius: 999px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #334155;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .chip-btn:hover { background: #f1f5f9; }
    .chip-btn.active {
        background: #16a34a;
        color: #fff;
        border-color: #16a34a;
    }

    /* Dimension Presets */
    .dimension-presets-row {
        display: flex;
        gap: 5px;
        overflow-x: auto;
        margin-bottom: 8px;
        padding-bottom: 4px;
    }
    .dim-preset-chip {
        padding: 4px 8px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 800;
        color: #334155;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s ease;
    }
    .dim-preset-chip:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }
    .dim-preset-chip.active {
        background: #16a34a;
        color: #fff;
        border-color: #16a34a;
    }

    /* 2-Column Inputs Grid */
    .form-pair-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 8px;
    }
    .param-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 6px 10px;
    }
    .param-box label {
        display: block;
        font-size: 10px;
        font-weight: 800;
        color: #64748b;
        margin-bottom: 3px;
    }
    .param-input-wrap {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .param-input-wrap input {
        width: 100%;
        height: 28px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 850;
        text-align: center;
        color: #0f172a;
        background: #fff;
    }
    .param-input-wrap input:focus {
        border-color: #16a34a;
        outline: none;
    }
    .param-unit {
        font-size: 10px;
        font-weight: 800;
        color: #64748b;
    }

    /* Barcode Type Radio Tabs */
    .barcode-type-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        margin-bottom: 10px;
    }
    .type-btn {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 10px;
        font-size: 11.5px;
        font-weight: 850;
        color: #334155;
        cursor: pointer;
        text-align: center;
        transition: all 0.15s ease;
    }
    .type-btn:hover { background: #f1f5f9; border-color: #94a3b8; }
    .type-btn.active {
        background: #f0fdf4;
        border-color: #16a34a;
        color: #15803d;
    }

    /* Rotation Selector */
    .rotation-btn-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
        margin-bottom: 10px;
    }
    .rotate-btn {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 4px;
        font-size: 11.5px;
        font-weight: 850;
        color: #334155;
        cursor: pointer;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
        transition: all 0.15s ease;
    }
    .rotate-btn:hover { background: #f1f5f9; border-color: #94a3b8; }
    .rotate-btn.active {
        background: #f0fdf4;
        border-color: #16a34a;
        color: #15803d;
    }
    .rotate-btn .material-symbols-rounded { font-size: 16px; }

    /* Sliders */
    .slider-row {
        margin-bottom: 8px;
    }
    .slider-row-head {
        display: flex;
        justify-content: space-between;
        font-size: 10.5px;
        font-weight: 800;
        color: #334155;
        margin-bottom: 2px;
    }
    .slider-input {
        width: 100%;
        accent-color: #16a34a;
        cursor: pointer;
    }

    /* Switches */
    .switch-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 5px 0;
        font-size: 11px;
        font-weight: 800;
        color: #334155;
        border-bottom: 1px dashed #f1f5f9;
    }
    .switch-row:last-child { border-bottom: none; }
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 34px;
        height: 18px;
    }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
        position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1; transition: .2s; border-radius: 999px;
    }
    .toggle-slider:before {
        position: absolute; content: ""; height: 12px; width: 12px; left: 3px; bottom: 3px;
        background-color: white; transition: .2s; border-radius: 50%;
    }
    .toggle-switch input:checked + .toggle-slider { background-color: #16a34a; }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(16px); }

    /* Action Buttons */
    .btn-test-1row {
        background: #f0fdf4;
        color: #15803d;
        border: 1.5px solid #86efac;
        padding: 9px 12px;
        border-radius: 12px;
        font-weight: 850;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        width: 100%;
        margin-bottom: 8px;
        transition: all 0.15s ease;
    }
    .btn-test-1row:hover {
        background: #dcfce7;
        border-color: #4ade80;
    }

    .btn-main-print {
        background: #16a34a;
        color: #ffffff;
        border: none;
        padding: 12px 14px;
        border-radius: 12px;
        font-weight: 900;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        width: 100%;
        box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35);
        transition: all 0.15s ease;
    }
    .btn-main-print:hover {
        background: #15803d;
        box-shadow: 0 6px 18px rgba(22, 163, 74, 0.45);
    }

    /* Screen Preview Canvas */
    .preview-canvas-wrap {
        flex: 1;
        background: #f1f5f9;
        border: 1.5px dashed #cbd5e1;
        border-radius: 14px;
        padding: 25px 15px;
        display: flex;
        flex-direction: column;
        align-items: center;
        overflow: auto;
        min-height: 540px;
    }

    /* Simulated Continuous 110mm Roll */
    #print-mount-point {
        width: var(--roll-width, 110mm);
        min-height: 100mm;
        padding: 4mm var(--side-margin, 1.25mm);
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        box-sizing: border-box;
        margin: 0 auto;
        border: 1px solid #e2e8f0;
        position: relative;
    }

    /* Single Roll Row */
    .barcode-roll-row {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        height: var(--sticker-height, 25mm);
        max-height: var(--sticker-height, 25mm);
        box-sizing: border-box;
        margin-bottom: var(--row-gap, 2mm);
        position: relative;
    }

    /* Individual Sticker Cell */
    .barcode-sticker-cell {
        flex: 0 0 var(--sticker-width, 34.5mm);
        width: var(--sticker-width, 34.5mm);
        max-width: var(--sticker-width, 34.5mm);
        height: var(--sticker-height, 25mm);
        max-height: var(--sticker-height, 25mm);
        background: #ffffff;
        box-sizing: border-box;
        border: 1px dashed #cbd5e1;
        border-radius: 3px;
        overflow: hidden;
        position: relative;
    }
    .barcode-sticker-cell.spacer {
        visibility: hidden !important;
        border: none !important;
        background: transparent !important;
    }

    /* Rotatable Sticker Inner Container - Centered Dead Center */
    .sticker-inner-box {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(var(--sticker-rotation, 0deg));
        transform-origin: center center;
        width: 100%;
        height: 100%;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        justify-content: space-evenly;
        align-items: center;
        padding: 1mm 1mm;
    }

    /* When rotated 90° or 270°, swap width and height so content fits the physical boundary without stretching */
    .sticker-inner-box.rotated-vertical {
        width: var(--sticker-height, 25mm) !important;
        height: var(--sticker-width, 34.5mm) !important;
        transform: translate(-50%, -50%) rotate(var(--sticker-rotation, 90deg)) !important;
    }

    /* Typography inside Stickers */
    .cell-title {
        font-size: var(--title-font-size, 8px);
        font-weight: 850;
        color: #000000;
        text-transform: uppercase;
        letter-spacing: 0.2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 95%;
        line-height: 1.1;
        margin: 0;
        text-align: center;
    }

    .cell-svg-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        margin: 0 auto;
        overflow: hidden;
    }
    .cell-svg-wrap svg {
        display: block;
        max-width: 98% !important;
        height: auto;
        margin: 0 auto;
    }

    .cell-footer-row {
        display: flex;
        align-items: baseline;
        justify-content: center;
        gap: 4px;
        max-width: 95%;
        line-height: 1;
        margin: 0;
    }
    .cell-price {
        font-size: var(--price-font-size, 10px);
        font-weight: 900;
        color: #000000;
        line-height: 1;
    }
    .cell-meta {
        font-size: 7px;
        font-weight: 750;
        color: #334155;
        line-height: 1;
    }

    /* =========================================================================
       PRINT ENGINE (@media print)
       ========================================================================= */
    @media print {
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            width: 100% !important;
            height: auto !important;
        }

        body * {
            visibility: hidden !important;
        }

        .app-container, .main-content, .content-area,
        .barcode-studio-grid, .preview-card,
        .preview-canvas-wrap {
            position: static !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow: visible !important;
            display: block !important;
        }

        .sidebar, .top-bar, .page-header, .settings-card,
        .preview-head-row, .inventory-tabs, .mobile-bottom-nav,
        .custom-splash, .mobile-powered-footer, .floating-pay-now-btn {
            display: none !important;
        }

        #print-mount-point, #print-mount-point * {
            visibility: visible !important;
        }

        #print-mount-point {
            position: relative !important;
            left: 0 !important;
            top: 0 !important;
            width: var(--roll-width, 110mm) !important;
            max-width: var(--roll-width, 110mm) !important;
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
            margin: var(--top-margin, 0mm) auto 0 auto !important;
            padding: 0 var(--side-margin, 1.25mm) !important;
            border: none !important;
            box-shadow: none !important;
            background: #fff !important;
            box-sizing: border-box !important;
            overflow: visible !important;
        }

        .barcode-roll-row {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            justify-content: space-between !important;
            align-items: center !important;
            width: 100% !important;
            height: var(--sticker-height, 25mm) !important;
            max-height: var(--sticker-height, 25mm) !important;
            box-sizing: border-box !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            page-break-after: always !important;
            break-after: page !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 auto !important;
        }

        .barcode-roll-row:last-child {
            page-break-after: avoid !important;
            break-after: avoid !important;
            margin-bottom: 0 !important;
        }

        .barcode-sticker-cell {
            flex: 0 0 var(--sticker-width, 34.5mm) !important;
            width: var(--sticker-width, 34.5mm) !important;
            max-width: var(--sticker-width, 34.5mm) !important;
            height: var(--sticker-height, 25mm) !important;
            max-height: var(--sticker-height, 25mm) !important;
            position: relative !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            padding: 0 !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            background: #fff !important;
            box-shadow: none !important;
            border: none !important;
            outline: none !important;
        }

        .sticker-inner-box {
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) rotate(var(--sticker-rotation, 0deg)) !important;
            transform-origin: center center !important;
            width: 100% !important;
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-evenly !important;
            align-items: center !important;
            box-sizing: border-box !important;
            padding: 1mm 1mm !important;
        }

        .sticker-inner-box.rotated-vertical {
            width: var(--sticker-height, 25mm) !important;
            height: var(--sticker-width, 34.5mm) !important;
            transform: translate(-50%, -50%) rotate(var(--sticker-rotation, 90deg)) !important;
        }

        .cell-title {
            font-size: var(--title-font-size, 8px) !important;
            font-weight: 850 !important;
            color: #000 !important;
            margin: 0 !important;
            max-width: 95% !important;
        }

        .cell-price {
            font-size: var(--price-font-size, 10px) !important;
            font-weight: 900 !important;
            color: #000 !important;
        }

        .cell-svg-wrap svg {
            display: block !important;
            max-width: 98% !important;
            height: auto !important;
            margin: 0 auto !important;
        }
    }

    @media (max-width: 960px) {
        .barcode-studio-grid { grid-template-columns: 1fr; }
        .settings-card { position: static; max-height: none; }
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/viewProfile/<?php echo (int)($product['id'] ?? 0); ?>" class="page-subtitle">&larr; Back to Product Profile</a>
        <h1 id="page-main-heading">110mm Thermal Barcode Studio</h1>
        <p class="page-subtitle">Precision 3-Up Label Printing for <strong><?php echo Security::escape($productName); ?></strong></p>
    </div>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-secondary" onclick="testPrint1Row()" style="padding: 10px 16px; font-weight: 850; font-size: 13.5px; border-color:#16a34a; color:#15803d; background:#f0fdf4;">
            <span class="material-symbols-rounded">verified</span> Test 1 Row (3 Stickers)
        </button>
        <button type="button" class="btn btn-primary" onclick="triggerPrint()" style="padding: 10px 22px; font-weight: 850; font-size: 14px; background:#16a34a; border-color:#16a34a; box-shadow: 0 4px 14px rgba(22,163,74,0.3);">
            <span class="material-symbols-rounded">print</span> Print Now (Ctrl+P)
        </button>
    </div>
</div>

<div class="barcode-studio-grid">
    <!-- Left Controls Panel -->
    <aside class="settings-card">
        <!-- Target Product Card -->
        <div class="product-mini-card">
            <div class="product-mini-title"><?php echo Security::escape($productName); ?></div>
            <div class="product-mini-meta">
                <span>SKU: <?php echo Security::escape($productModel ?: 'N/A'); ?> &bull; <?php echo Security::escape($productUnit); ?></span>
                <span class="product-mini-price"><?php echo App::money($productPrice); ?></span>
            </div>
            <div style="font-size:11px; color:#cbd5e1; font-family:monospace; margin-top:4px;">
                Barcode: <?php echo Security::escape($displayBarcode); ?>
            </div>
        </div>

        <!-- 1. Print Quantity -->
        <div class="ctrl-group-title">
            <div class="title-left">
                <span class="material-symbols-rounded">layers</span>
                1. Quantity (3 Labels / Row)
            </div>
        </div>
        <div class="stepper-row">
            <button type="button" class="stepper-btn" onclick="adjustQty(-3)">&minus;3</button>
            <input class="stepper-input" type="number" id="qty-input" min="3" step="3" value="3" oninput="onQtyChange()">
            <button type="button" class="stepper-btn" onclick="adjustQty(+3)">+3</button>
        </div>
        <div class="quick-chips-row">
            <button type="button" class="chip-btn active" onclick="setChipQty(3)">3 (1 Row)</button>
            <button type="button" class="chip-btn" onclick="setChipQty(6)">6 (2 Rows)</button>
            <button type="button" class="chip-btn" onclick="setChipQty(15)">15 (5 Rows)</button>
            <button type="button" class="chip-btn" onclick="setChipQty(30)">30 (10 Rows)</button>
            <button type="button" class="chip-btn" onclick="setChipQty(60)">60 (20 Rows)</button>
        </div>

        <!-- 2. Barcode Type (Code 128 vs EAN-13) -->
        <div class="ctrl-group-title">
            <div class="title-left">
                <span class="material-symbols-rounded">qr_code_2</span>
                2. Barcode Standard
            </div>
        </div>
        <div class="barcode-type-grid">
            <button type="button" class="type-btn active" id="btn-type-code128" onclick="setBarcodeType('CODE128')">
                <strong>Code 128</strong>
                <div style="font-size:9.5px; color:#64748b;">(Universal / Letters &amp; Nos)</div>
            </button>
            <button type="button" class="type-btn" id="btn-type-ean13" onclick="setBarcodeType('EAN13')">
                <strong>EAN-13</strong>
                <div style="font-size:9.5px; color:#64748b;">(13-Digit Standard)</div>
            </button>
        </div>

        <!-- 3. Sticker Dimensions (110mm Roll Locked) -->
        <div class="ctrl-group-title">
            <div class="title-left">
                <span class="material-symbols-rounded">aspect_ratio</span>
                3. Dimensions (110 mm Roll Math)
            </div>
            <span style="font-size:10px; font-weight:800; color:#16a34a; cursor:pointer;" onclick="resetDefaults()">Reset</span>
        </div>
        <div class="dimension-presets-row">
            <button type="button" class="dim-preset-chip active" onclick="applyPreset(110, 34.5, 25, 2, 2)">110mm &bull; 34.5×25 (Active)</button>
            <button type="button" class="dim-preset-chip" onclick="applyPreset(110, 34.0, 25, 2.5, 2)">110mm &bull; 34×25</button>
            <button type="button" class="dim-preset-chip" onclick="applyPreset(110, 35.0, 25, 1.5, 2)">110mm &bull; 35×25</button>
        </div>
        <div class="form-pair-grid">
            <div class="param-box">
                <label>Roll / Page Width</label>
                <div class="param-input-wrap">
                    <input type="number" id="cfg-roll-w" min="80" max="120" step="0.5" value="110" oninput="applyDimensions()">
                    <span class="param-unit">mm</span>
                </div>
            </div>
            <div class="param-box">
                <label>Sticker Height</label>
                <div class="param-input-wrap">
                    <input type="number" id="cfg-sticker-h" min="15" max="50" step="0.5" value="25" oninput="applyDimensions()">
                    <span class="param-unit">mm</span>
                </div>
            </div>
        </div>
        <div class="form-pair-grid">
            <div class="param-box">
                <label>Sticker Width</label>
                <div class="param-input-wrap">
                    <input type="number" id="cfg-sticker-w" min="20" max="50" step="0.5" value="34.5" oninput="applyDimensions()">
                    <span class="param-unit">mm</span>
                </div>
            </div>
            <div class="param-box">
                <label>Sticker Gap (H)</label>
                <div class="param-input-wrap">
                    <input type="number" id="cfg-gap-h" min="0" max="10" step="0.5" value="2" oninput="applyDimensions()">
                    <span class="param-unit">mm</span>
                </div>
            </div>
        </div>
        <!-- Calculated Roll Summary Card -->
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:8px 12px; margin-bottom:12px; font-size:11px; color:#166534; font-weight:750; display:flex; justify-content:space-between; align-items:center;">
            <span>Current Page Size:</span>
            <strong id="label-roll-summary" style="font-size:12px; color:#14532d;">110 mm &times; 25 mm (4.33&Prime; &times; 0.98&Prime;)</strong>
        </div>

        <!-- 4. Sticker Rotation (Centered Math) -->
        <div class="ctrl-group-title">
            <div class="title-left">
                <span class="material-symbols-rounded">rotate_right</span>
                4. Rotate Sticker (Centered)
            </div>
        </div>
        <div class="rotation-btn-grid">
            <button type="button" class="rotate-btn active" id="btn-rot-0" onclick="setStickerRotation(0)">
                <span class="material-symbols-rounded">straight</span>
                0°
            </button>
            <button type="button" class="rotate-btn" id="btn-rot-90" onclick="setStickerRotation(90)">
                <span class="material-symbols-rounded">rotate_90_degrees_cw</span>
                90°
            </button>
            <button type="button" class="rotate-btn" id="btn-rot-180" onclick="setStickerRotation(180)">
                <span class="material-symbols-rounded">screen_rotation</span>
                180°
            </button>
            <button type="button" class="rotate-btn" id="btn-rot-270" onclick="setStickerRotation(270)">
                <span class="material-symbols-rounded">rotate_90_degrees_ccw</span>
                270°
            </button>
        </div>

        <!-- 5. Barcode & Font Sizes Customization -->
        <div class="ctrl-group-title">
            <div class="title-left">
                <span class="material-symbols-rounded">tune</span>
                5. Barcode &amp; Font Sizes
            </div>
        </div>
        <div class="slider-row">
            <div class="slider-row-head">
                <span>Barcode Width</span>
                <span id="val-bar-width">1.15</span>
            </div>
            <input type="range" class="slider-input" id="cfg-bar-width" min="0.7" max="2.4" step="0.05" value="1.15" oninput="applyVisualStyles()">
        </div>
        <div class="slider-row">
            <div class="slider-row-head">
                <span>Barcode Height</span>
                <span id="val-bar-height">22 px</span>
            </div>
            <input type="range" class="slider-input" id="cfg-bar-height" min="15" max="42" step="1" value="22" oninput="applyVisualStyles()">
        </div>
        <div class="slider-row">
            <div class="slider-row-head">
                <span>Product Name Font</span>
                <span id="val-title-size">8.0 px</span>
            </div>
            <input type="range" class="slider-input" id="cfg-title-size" min="6.5" max="13" step="0.5" value="8.0" oninput="applyVisualStyles()">
        </div>
        <div class="slider-row">
            <div class="slider-row-head">
                <span>Price Font Size</span>
                <span id="val-price-size">10.0 px</span>
            </div>
            <input type="range" class="slider-input" id="cfg-price-size" min="7.5" max="15" step="0.5" value="10.0" oninput="applyVisualStyles()">
        </div>
        <div class="slider-row">
            <div class="slider-row-head">
                <span>Top Margin Offset (Nudge)</span>
                <span id="val-top-margin">0 mm</span>
            </div>
            <input type="range" class="slider-input" id="cfg-top-margin" min="-2" max="6" step="0.5" value="0" oninput="applyVisualStyles()">
        </div>

        <!-- 6. Content Toggles -->
        <div class="ctrl-group-title">
            <div class="title-left">
                <span class="material-symbols-rounded">visibility</span>
                6. Show / Hide Content
            </div>
        </div>
        <div class="switch-row">
            <span>Product Name</span>
            <label class="toggle-switch">
                <input type="checkbox" id="toggle-name" checked onchange="renderStickers()">
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="switch-row">
            <span>Price Tag</span>
            <label class="toggle-switch">
                <input type="checkbox" id="toggle-price" checked onchange="renderStickers()">
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="switch-row">
            <span>SKU / Unit</span>
            <label class="toggle-switch">
                <input type="checkbox" id="toggle-sku" checked onchange="renderStickers()">
                <span class="toggle-slider"></span>
            </label>
        </div>

        <!-- Action Buttons -->
        <div style="margin-top: 16px;">
            <button type="button" class="btn-test-1row" onclick="testPrint1Row()">
                <span class="material-symbols-rounded">verified</span>
                <strong>Test 1 Row (3 Stickers) &mdash; Zero Waste</strong>
            </button>
            <button type="button" class="btn-main-print" id="btn-print-submit" onclick="triggerPrint()">
                <span class="material-symbols-rounded">print</span>
                <span>Print <span id="btn-qty-label">3</span> Barcodes</span>
            </button>
        </div>
    </aside>

    <!-- Right Live Preview Canvas -->
    <section class="preview-card">
        <div class="preview-head-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px; flex-wrap:wrap; gap:8px;">
            <div>
                <h3 style="margin:0; font-size: 16px; font-weight:900; color:#0f172a;">Live 110mm 3-Up Barcode Roll Preview</h3>
                <span id="preview-dim-subtitle" style="font-size: 12px; color:#64748b; font-weight:700;">Roll: 110mm &times; 25mm (4.33&Prime; &times; 0.98&Prime;) &bull; Sticker: 34.5mm &times; 25mm &bull; Code 128</span>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="badge badge-soft" id="badge-type-info" style="color:#0369a1; background:#e0f2fe; border-color:#7dd3fc;">Type: CODE 128</span>
                <span class="badge badge-soft" id="badge-rot-info" style="color:#15803d; background:#dcfce7; border-color:#86efac;">Rotation: 0°</span>
                <span class="badge badge-primary" id="badge-roll-info" style="background:#16a34a; border-color:#16a34a;">110mm &times; 25mm</span>
            </div>
        </div>

        <div class="preview-canvas-wrap">
            <div id="print-mount-point">
                <!-- Barcode stickers rendered here -->
            </div>
        </div>
    </section>
</div>

<?php ob_start(); ?>
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/JsBarcode.all.min.js"></script>
<script>
    const activeProduct = <?php echo json_encode([
        'id' => (int)($product['id'] ?? 0),
        'name' => $productName,
        'barcode' => $displayBarcode,
        'model' => $productModel,
        'price' => $productPrice,
        'expiry' => $productExpiry,
        'batch' => $productBatch,
        'unit' => $productUnit
    ]); ?>;

    const currency = <?php echo json_encode($currency); ?>;

    // Default configuration (110mm total roll width, 34.5x25mm per sticker)
    const DEFAULT_CONFIG = {
        barcodeType: 'CODE128',
        rollW: 110,       // mm (exact user requirement)
        stickerW: 34.5,   // mm
        stickerH: 25,     // mm
        gapH: 2,          // mm
        gapV: 2,          // mm
        sideMargin: 1.25, // mm
        topMargin: 0,     // mm offset
        rotation: 0,      // degrees (0, 90, 180, 270)
        barWidth: 1.15,
        barHeight: 22,
        titleSize: 8.0,
        priceSize: 10.0,
        qty: 3
    };

    let config = { ...DEFAULT_CONFIG };

    function loadSavedConfig() {
        try {
            const saved = localStorage.getItem('saas_barcode_user_config_110mm');
            if (saved) {
                const parsed = JSON.parse(saved);
                config = { ...config, ...parsed };
            }
        } catch(e) {
            console.warn('Could not load saved barcode config:', e);
        }

        // Apply to controls
        document.getElementById('cfg-roll-w').value = config.rollW || 110;
        document.getElementById('cfg-sticker-h').value = config.stickerH || 25;
        document.getElementById('cfg-sticker-w').value = config.stickerW || 34.5;
        document.getElementById('cfg-gap-h').value = config.gapH || 2;
        document.getElementById('cfg-bar-width').value = config.barWidth || 1.15;
        document.getElementById('cfg-bar-height').value = config.barHeight || 22;
        document.getElementById('cfg-title-size').value = config.titleSize || 8.0;
        document.getElementById('cfg-price-size').value = config.priceSize || 10.0;
        document.getElementById('cfg-top-margin').value = config.topMargin || 0;
        document.getElementById('qty-input').value = config.qty || 3;

        setBarcodeType(config.barcodeType || 'CODE128', false);
        setStickerRotation(config.rotation || 0, false);
    }

    function saveConfig() {
        try {
            localStorage.setItem('saas_barcode_user_config_110mm', JSON.stringify({
                barcodeType: config.barcodeType,
                rollW: config.rollW,
                stickerW: config.stickerW,
                stickerH: config.stickerH,
                gapH: config.gapH,
                gapV: config.gapV,
                topMargin: config.topMargin,
                rotation: config.rotation,
                barWidth: config.barWidth,
                barHeight: config.barHeight,
                titleSize: config.titleSize,
                priceSize: config.priceSize
            }));
        } catch(e) {}
    }

    function resetDefaults() {
        config = { ...DEFAULT_CONFIG, qty: parseInt(document.getElementById('qty-input').value || '3', 10) };
        saveConfig();
        loadSavedConfig();
        applyDimensions();
        applyVisualStyles();
        renderStickers();
    }

    function applyPreset(rollW, stickerW, stickerH, gapH, gapV) {
        document.getElementById('cfg-roll-w').value = rollW;
        document.getElementById('cfg-sticker-w').value = stickerW;
        document.getElementById('cfg-sticker-h').value = stickerH;
        document.getElementById('cfg-gap-h').value = gapH;

        document.querySelectorAll('.dim-preset-chip').forEach(btn => {
            const txt = btn.textContent;
            if (txt.includes(`${stickerW}×${stickerH}`)) btn.classList.add('active');
            else btn.classList.remove('active');
        });

        applyDimensions();
    }

    function setBarcodeType(type, doRender = true) {
        config.barcodeType = type;
        document.getElementById('btn-type-code128').classList.toggle('active', type === 'CODE128');
        document.getElementById('btn-type-ean13').classList.toggle('active', type === 'EAN13');
        document.getElementById('badge-type-info').textContent = `Type: ${type}`;

        saveConfig();
        if (doRender) renderStickers();
    }

    function applyDimensions() {
        config.rollW = parseFloat(document.getElementById('cfg-roll-w').value) || 110;
        config.stickerH = parseFloat(document.getElementById('cfg-sticker-h').value) || 25;
        config.stickerW = parseFloat(document.getElementById('cfg-sticker-w').value) || 34.5;
        config.gapH = parseFloat(document.getElementById('cfg-gap-h').value) || 2;

        // Auto-calculate side margin so 3 stickers + 2 gaps fill the roll width exactly
        const totalUsed = (3 * config.stickerW) + (2 * config.gapH);
        let sideMargin = (config.rollW - totalUsed) / 2;
        if (sideMargin < 0.5) sideMargin = 0.5;
        config.sideMargin = Math.round(sideMargin * 10) / 10;

        document.documentElement.style.setProperty('--roll-width', `${config.rollW}mm`);
        document.documentElement.style.setProperty('--sticker-width', `${config.stickerW}mm`);
        document.documentElement.style.setProperty('--sticker-height', `${config.stickerH}mm`);
        document.documentElement.style.setProperty('--col-gap', `${config.gapH}mm`);
        document.documentElement.style.setProperty('--side-margin', `${config.sideMargin}mm`);

        // Summary text & Badges
        const inchW = (config.rollW / 25.4).toFixed(2);
        const inchH = (config.stickerH / 25.4).toFixed(2);
        document.getElementById('label-roll-summary').innerHTML = `${config.rollW} mm &times; ${config.stickerH} mm (${inchW}&Prime; &times; ${inchH}&Prime;)`;
        document.getElementById('badge-roll-info').textContent = `${config.rollW}mm × ${config.stickerH}mm`;
        document.getElementById('preview-dim-subtitle').textContent = `Roll: ${config.rollW}mm × ${config.stickerH}mm (${inchW}" × ${inchH}") • Sticker: ${config.stickerW}mm × ${config.stickerH}mm • ${config.barcodeType}`;

        // Update @page CSS
        const dynamicStyle = document.getElementById('dynamic-page-size-style');
        if (dynamicStyle) {
            dynamicStyle.innerHTML = `@media print { @page { size: ${config.rollW}mm ${config.stickerH}mm portrait !important; margin: 0mm !important; } }`;
        }

        saveConfig();
        renderStickers();
    }

    function setStickerRotation(deg, doRender = true) {
        config.rotation = deg;
        document.querySelectorAll('.rotate-btn').forEach(b => b.classList.remove('active'));
        const btn = document.getElementById(`btn-rot-${deg}`);
        if (btn) btn.classList.add('active');

        document.documentElement.style.setProperty('--sticker-rotation', `${deg}deg`);
        document.getElementById('badge-rot-info').textContent = `Rotation: ${deg}°`;

        saveConfig();
        if (doRender) renderStickers();
    }

    function applyVisualStyles() {
        config.barWidth = parseFloat(document.getElementById('cfg-bar-width').value) || 1.15;
        config.barHeight = parseInt(document.getElementById('cfg-bar-height').value, 10) || 22;
        config.titleSize = parseFloat(document.getElementById('cfg-title-size').value) || 8.0;
        config.priceSize = parseFloat(document.getElementById('cfg-price-size').value) || 10.0;
        config.topMargin = parseFloat(document.getElementById('cfg-top-margin').value) || 0;

        document.getElementById('val-bar-width').textContent = config.barWidth.toFixed(2);
        document.getElementById('val-bar-height').textContent = `${config.barHeight} px`;
        document.getElementById('val-title-size').textContent = `${config.titleSize} px`;
        document.getElementById('val-price-size').textContent = `${config.priceSize} px`;
        document.getElementById('val-top-margin').textContent = `${config.topMargin} mm`;

        document.documentElement.style.setProperty('--title-font-size', `${config.titleSize}px`);
        document.documentElement.style.setProperty('--price-font-size', `${config.priceSize}px`);
        document.documentElement.style.setProperty('--barcode-height', `${config.barHeight}px`);
        document.documentElement.style.setProperty('--top-margin', `${config.topMargin}mm`);

        saveConfig();
        renderStickers();
    }

    function adjustQty(delta) {
        const input = document.getElementById('qty-input');
        let val = parseInt(input.value || '3', 10) + delta;
        if (val < 3) val = 3;
        if (val > 600) val = 600;
        input.value = val;
        onQtyChange();
    }

    function setChipQty(qty) {
        document.getElementById('qty-input').value = qty;
        onQtyChange();
    }

    function onQtyChange() {
        const input = document.getElementById('qty-input');
        let qty = parseInt(input.value || '3', 10);
        if (qty < 1) qty = 1;
        config.qty = qty;

        document.querySelectorAll('.quick-chips-row .chip-btn').forEach(btn => {
            const txt = parseInt(btn.textContent.trim(), 10);
            if (txt === qty) btn.classList.add('active');
            else btn.classList.remove('active');
        });

        document.getElementById('btn-qty-label').textContent = qty;
        renderStickers();
    }

    function getBarcodePayload() {
        let raw = (activeProduct.barcode && activeProduct.barcode.trim() !== '') 
            ? activeProduct.barcode.trim() 
            : '200' + String(activeProduct.id).padStart(6, '0') + '123';

        if (config.barcodeType === 'EAN13') {
            let digits = raw.replace(/\D/g, '');
            if (digits.length >= 12) {
                return digits.slice(0, 12);
            } else {
                return digits.padStart(12, '2');
            }
        }

        return raw;
    }

    function renderSingleSticker(index, payload, showName, showPrice, showSku) {
        const svgId = `barcode-svg-${index}`;
        const unitText = activeProduct.unit || activeProduct.model || '';
        const isVertical = (config.rotation === 90 || config.rotation === 270);

        return `
            <div class="barcode-sticker-cell">
                <div class="sticker-inner-box ${isVertical ? 'rotated-vertical' : ''}">
                    ${showName ? `<div class="cell-title">${escapeHtml(activeProduct.name)}</div>` : ''}
                    
                    <div class="cell-svg-wrap">
                        <svg id="${svgId}"></svg>
                    </div>

                    ${(showPrice || showSku) ? `
                        <div class="cell-footer-row">
                            ${showPrice ? `<span class="cell-price">${currency} ${activeProduct.price.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</span>` : ''}
                            ${(showSku && unitText) ? `<span class="cell-meta">${escapeHtml(unitText)}</span>` : ''}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    function renderStickers() {
        const container = document.getElementById('print-mount-point');
        if (!container) return;

        const cols = 3;
        const qty = Math.max(1, Math.min(600, parseInt(document.getElementById('qty-input').value || '3', 10)));
        const showName = document.getElementById('toggle-name').checked;
        const showPrice = document.getElementById('toggle-price').checked;
        const showSku = document.getElementById('toggle-sku').checked;

        const rowsCount = Math.ceil(qty / cols);
        const submitBtn = document.getElementById('btn-print-submit');
        if (submitBtn) {
            submitBtn.innerHTML = `<span class="material-symbols-rounded">print</span> Print ${qty} Barcodes (${rowsCount} ${rowsCount === 1 ? 'Row' : 'Rows'})`;
        }

        const payload = getBarcodePayload();
        let html = '';

        for (let r = 0; r < rowsCount; r++) {
            html += `<div class="barcode-roll-row">`;
            for (let c = 0; c < cols; c++) {
                const idx = r * cols + c;
                if (idx < qty) {
                    html += renderSingleSticker(idx, payload, showName, showPrice, showSku);
                } else {
                    html += `<div class="barcode-sticker-cell spacer"></div>`;
                }
            }
            html += `</div>`;
        }

        container.innerHTML = html;

        // Render barcodes via JsBarcode
        const barW = config.barWidth || 1.15;
        const barH = config.barHeight || 22;
        const bFormat = config.barcodeType || 'CODE128';

        for (let i = 0; i < qty; i++) {
            const svgEl = document.getElementById(`barcode-svg-${i}`);
            if (svgEl && window.JsBarcode) {
                try {
                    JsBarcode(svgEl, payload, {
                        format: bFormat,
                        width: barW,
                        height: barH,
                        displayValue: true,
                        fontSize: 8.0,
                        font: 'monospace',
                        textAlign: 'center',
                        textMargin: 1.2,
                        margin: 0
                    });
                } catch(e) {
                    try {
                        JsBarcode(svgEl, payload, {
                            format: "CODE128",
                            width: barW,
                            height: barH,
                            displayValue: true,
                            fontSize: 8.0,
                            font: 'monospace',
                            textAlign: 'center',
                            textMargin: 1.2,
                            margin: 0
                        });
                    } catch(err) {
                        console.error('Barcode render error:', err);
                    }
                }
            }
        }
    }

    function testPrint1Row() {
        const input = document.getElementById('qty-input');
        const prevQty = input ? input.value : '3';
        if (input) input.value = 3;
        onQtyChange();

        setTimeout(() => {
            triggerPrint();
            setTimeout(() => {
                if (input) input.value = prevQty;
                onQtyChange();
            }, 600);
        }, 80);
    }

    function triggerPrint() {
        applyDimensions();
        window.print();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadSavedConfig();
        applyDimensions();
        applyVisualStyles();
        renderStickers();
    });

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(() => {
            loadSavedConfig();
            applyDimensions();
            applyVisualStyles();
            renderStickers();
        }, 50);
    }
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>