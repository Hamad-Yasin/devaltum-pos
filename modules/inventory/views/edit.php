<?php ob_start(); ?>
<?php
$unitType = App::normalizeProductUnit($product['unit_type'] ?? 'pcs');
$barcode = App::normalizeBarcode($product['barcode'] ?? '');
$rawProductImage = trim((string) ($product['image_path'] ?? ''));
$hasProductImage = $rawProductImage !== '' && App::mediaExists($rawProductImage);
$productImageUrl = $hasProductImage ? App::mediaUrl($rawProductImage) : '';
$productCodes = $productCodes ?? [];
?>

<style>
    .inventory-edit-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(320px, .85fr);
        gap: 18px;
        align-items: start;
    }

    .inventory-edit-card {
        border: 1px solid var(--border);
        border-radius: 18px;
        background: linear-gradient(150deg, #ffffff, #f8fbff);
        box-shadow: 0 18px 42px rgba(15,23,42,.08);
        padding: 22px;
    }

    .inventory-edit-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
        color: #0f172a;
        font-size: 15px;
        font-weight: 900;
    }

    .inventory-edit-section-title .material-symbols-rounded {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 13px;
        background: #eef4ff;
        color: var(--primary);
    }

    .box-barcode-title-row {
        justify-content: space-between;
    }

    .box-barcode-title {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .box-auto-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
        color: #475569;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .box-auto-toggle input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .box-auto-switch {
        position: relative;
        width: 38px;
        height: 22px;
        border-radius: 999px;
        background: #cbd5e1;
        transition: background .2s ease;
    }

    .box-auto-switch::after {
        content: '';
        position: absolute;
        width: 18px;
        height: 18px;
        left: 2px;
        top: 2px;
        border-radius: 999px;
        background: #fff;
        box-shadow: 0 2px 6px rgba(15,23,42,.25);
        transition: transform .2s ease;
    }

    .box-auto-toggle input:checked + .box-auto-switch {
        background: #0f6dff;
    }

    .box-auto-toggle input:checked + .box-auto-switch::after {
        transform: translateX(16px);
    }

    .box-barcode-disabled {
        opacity: .58;
    }

    .box-barcode-disabled .barcode-scan-btn,
    .box-barcode-disabled .form-input {
        cursor: not-allowed;
    }

    .inventory-edit-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .inventory-edit-grid.three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .inventory-edit-full {
        grid-column: 1 / -1;
    }

    .inventory-edit-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        flex-wrap: wrap;
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
    }

    .inventory-edit-preview {
        position: sticky;
        top: calc(var(--header-height) + 18px);
    }

    .inventory-edit-barcode {
        border: 1px solid #dbe4f0;
        border-radius: 10px;
        background: #fff;
        padding: 14px;
        text-align: center;
        box-shadow: 0 14px 28px rgba(15,23,42,.07);
    }

    .inventory-edit-barcode strong {
        display: block;
        margin-bottom: 4px;
        color: #0f172a;
        font-size: 14px;
        font-weight: 900;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .inventory-edit-barcode .barcode-svg {
        display: block;
        width: 100%;
        height: 56px;
    }

    .scan-config-card {
        border: 1px solid #dbe4f0;
        border-radius: 16px;
        background: #f8fbff;
        padding: 14px;
        display: grid;
        gap: 12px;
        max-width: 100%;
        min-width: 0;
    }

    .scan-config-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #0f172a;
        font-weight: 900;
    }

    .scan-config-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        align-items: end;
        max-width: 100%;
    }

    .barcode-input-wrap {
        position: relative;
        display: block;
        width: 100%;
        min-width: 0;
    }

    .barcode-field-icon {
        position: absolute !important;
        left: 14px;
        top: 50%;
        transform: translateY(-50%) !important;
        z-index: 2;
        width: 20px;
        height: 20px;
        display: grid;
        place-items: center;
        border: 0;
        border-radius: 0;
        background: transparent;
        color: #64748b;
        font-size: 20px;
        pointer-events: none;
    }

    .barcode-input-wrap .form-input {
        min-width: 0;
        width: 100%;
        padding-left: 44px;
        padding-right: 46px;
    }

    .barcode-input-wrap .form-input {
        background-image: none !important;
    }

    .barcode-scan-btn {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 30px;
        height: 30px;
        min-width: 30px;
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: #0f6dff;
        display: grid;
        place-items: center;
        cursor: pointer;
        transition: all .2s ease;
    }

    .barcode-scan-btn:hover {
        background: #eff6ff;
        box-shadow: none;
        transform: translateY(-50%);
    }

    .barcode-scan-btn .material-symbols-rounded {
        position: static !important;
        transform: none !important;
        pointer-events: none;
        font-size: 20px;
    }

    .select-icon-wrap {
        position: relative;
        width: 100%;
    }

    .select-icon-wrap .form-select {
        appearance: none;
        -webkit-appearance: none;
        min-height: 48px;
        padding-left: 46px;
        padding-right: 44px;
        border-radius: 16px;
        border: 1px solid #dbeafe;
        background: linear-gradient(180deg, #ffffff, #f8fbff);
        color: #0f172a;
        font-weight: 800;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .04);
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        cursor: pointer;
    }

    .select-icon-wrap .form-select:hover {
        border-color: #bfdbfe;
        background: #fff;
    }

    .select-icon-wrap .form-select:focus {
        border-color: #0f6dff;
        box-shadow: 0 0 0 4px rgba(15, 109, 255, .12);
    }

    .select-icon-wrap > .material-symbols-rounded {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: 22px;
        pointer-events: none;
        z-index: 2;
    }

    .select-icon-wrap::after {
        content: '';
        position: absolute;
        right: 16px;
        top: 50%;
        width: 9px;
        height: 9px;
        border-right: 2px solid #0f172a;
        border-bottom: 2px solid #0f172a;
        transform: translateY(-65%) rotate(45deg);
        pointer-events: none;
    }

    .barcode-camera-modal {
        position: fixed !important;
        inset: 0 !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 999999 !important;
        display: none !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 14px !important;
        background: rgba(11, 19, 43, 0.85) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        box-sizing: border-box !important;
    }

    .barcode-camera-modal.open {
        display: flex !important;
    }

    .barcode-camera-card {
        width: 100% !important;
        max-width: 440px !important;
        border-radius: 24px !important;
        background: #0b1329 !important;
        border: 1px solid rgba(255, 255, 255, 0.16) !important;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(255,255,255,0.06) !important;
        overflow: hidden !important;
        color: #fff !important;
        margin: auto !important;
        display: flex !important;
        flex-direction: column !important;
        animation: editScannerCardPop 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    .barcode-camera-head {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 14px 18px !important;
        background: #070d1e !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .edit-scanner-head-info {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
    }

    .edit-scanner-head-icon {
        width: 36px !important;
        height: 36px !important;
        border-radius: 10px !important;
        background: linear-gradient(135deg, #0f6dff, #0052cc) !important;
        color: #fff !important;
        display: grid !important;
        place-items: center !important;
        flex-shrink: 0 !important;
        box-shadow: 0 4px 12px rgba(15, 109, 255, 0.3) !important;
    }

    .edit-scanner-head-icon .material-symbols-rounded {
        font-size: 20px !important;
    }

    .edit-scanner-head-text strong {
        display: block !important;
        font-size: 14.5px !important;
        font-weight: 800 !important;
        color: #ffffff !important;
        letter-spacing: -0.2px !important;
        line-height: 1.2 !important;
    }

    .edit-scanner-head-text span {
        display: block !important;
        font-size: 11px !important;
        color: #94a3b8 !important;
        margin-top: 2px !important;
    }

    .barcode-camera-close {
        width: 34px !important;
        height: 34px !important;
        border-radius: 10px !important;
        background: rgba(255, 255, 255, 0.08) !important;
        border: 1px solid rgba(255, 255, 255, 0.14) !important;
        color: #cbd5e1 !important;
        display: grid !important;
        place-items: center !important;
        cursor: pointer !important;
        padding: 0 !important;
        transition: all 0.15s ease !important;
    }

    .barcode-camera-close:hover {
        background: rgba(239, 68, 68, 0.8) !important;
        border-color: #ef4444 !important;
        color: #fff !important;
        transform: scale(1.05) !important;
    }

    .barcode-camera-stage {
        position: relative !important;
        width: 100% !important;
        aspect-ratio: 4 / 3 !important;
        max-height: 52vh !important;
        background: #020617 !important;
        overflow: hidden !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .barcode-camera-stage video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block !important;
        background: #020617 !important;
    }

    .edit-scanner-reticle {
        position: absolute !important;
        inset: 14% 10% !important;
        border-radius: 18px !important;
        box-shadow: 0 0 0 9999px rgba(2, 6, 23, 0.58) !important;
        pointer-events: none !important;
        border: 1.5px solid rgba(255, 255, 255, 0.15) !important;
    }

    .edit-reticle-corner {
        position: absolute !important;
        width: 22px !important;
        height: 22px !important;
        border-color: #22c55e !important;
        border-style: solid !important;
        pointer-events: none !important;
    }

    .edit-reticle-corner.tl { top: -2px !important; left: -2px !important; border-width: 3.5px 0 0 3.5px !important; border-top-left-radius: 16px !important; }
    .edit-reticle-corner.tr { top: -2px !important; right: -2px !important; border-width: 3.5px 3.5px 0 0 !important; border-top-right-radius: 16px !important; }
    .edit-reticle-corner.bl { bottom: -2px !important; left: -2px !important; border-width: 0 0 3.5px 3.5px !important; border-bottom-left-radius: 16px !important; }
    .edit-reticle-corner.br { bottom: -2px !important; right: -2px !important; border-width: 0 3.5px 3.5px 0 !important; border-bottom-right-radius: 16px !important; }

    .edit-reticle-laser {
        position: absolute !important;
        left: 4% !important;
        right: 4% !important;
        height: 2px !important;
        background: linear-gradient(90deg, transparent, #22c55e, #86efac, #22c55e, transparent) !important;
        box-shadow: 0 0 10px #22c55e, 0 0 20px rgba(34, 197, 94, 0.8) !important;
        animation: editBarcodeLaser 1.8s ease-in-out infinite alternate !important;
    }

    .edit-reticle-caption {
        position: absolute !important;
        bottom: -32px !important;
        left: 0 !important;
        right: 0 !important;
        text-align: center !important;
        font-size: 11px !important;
        font-weight: 750 !important;
        color: rgba(255, 255, 255, 0.85) !important;
        text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8) !important;
    }

    .barcode-camera-foot {
        padding: 12px 18px !important;
        background: #070d1e !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .edit-camera-status-pill {
        display: inline-flex !important;
        align-items: center !important;
        gap: 7px !important;
        font-size: 11.5px !important;
        font-weight: 700 !important;
        color: #cbd5e1 !important;
    }

    .edit-camera-status-dot {
        width: 8px !important;
        height: 8px !important;
        border-radius: 50% !important;
        background: #22c55e !important;
        box-shadow: 0 0 8px #22c55e !important;
        animation: editCameraPulseDot 1.4s infinite ease-in-out !important;
    }

    .edit-scanner-cancel-btn {
        padding: 7px 14px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        border-radius: 10px !important;
        background: rgba(255, 255, 255, 0.08) !important;
        color: #e2e8f0 !important;
        border: 1px solid rgba(255, 255, 255, 0.16) !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
    }

    .edit-scanner-cancel-btn:hover {
        background: rgba(255, 255, 255, 0.15) !important;
        color: #fff !important;
    }

    @keyframes editBarcodeLaser {
        0% { top: 8%; opacity: 0.35; }
        50% { opacity: 1; }
        100% { top: 92%; opacity: 0.35; }
    }

    @keyframes editCameraPulseDot {
        0%, 100% { transform: scale(0.9); opacity: 0.7; }
        50% { transform: scale(1.2); opacity: 1; }
    }

    @keyframes editScannerCardPop {
        from { opacity: 0; transform: scale(0.92) translateY(12px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .barcode-feedback-badge {
        display: inline-flex !important;
        align-items: center !important;
        gap: 7px !important;
        padding: 6px 14px !important;
        border-radius: 999px !important;
        font-size: 12px !important;
        font-weight: 750 !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        line-height: 1.25 !important;
        letter-spacing: normal !important;
        text-transform: none !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
        margin-top: 4px !important;
    }

    .barcode-feedback-badge * {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        letter-spacing: normal !important;
    }

    .barcode-feedback-badge .badge-icon {
        width: 16px !important;
        height: 16px !important;
        flex-shrink: 0 !important;
        display: inline-block !important;
        vertical-align: middle !important;
    }

    .barcode-feedback-badge.badge-success {
        background: #ecfdf5 !important;
        border: 1px solid #a7f3d0 !important;
        color: #065f46 !important;
    }

    .barcode-feedback-badge.badge-warning {
        background: #fef2f2 !important;
        border: 1px solid #fecaca !important;
        color: #991b1b !important;
    }

    .barcode-preview-live {
        margin-top: 8px;
        padding: 8px 12px;
        border: 1px dashed #bfdbfe;
        border-radius: 12px;
        background: #f8fbff;
        display: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        transition: all 0.2s ease;
    }
    .barcode-preview-live svg {
        display: block;
        max-height: 38px;
        width: auto;
    }
    .barcode-preview-live > span.barcode-num-text {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        letter-spacing: 0.5px;
    }

    .barcode-camera-status {
        padding: 12px 16px 14px;
        color: #dbeafe;
        font-size: 12px;
        font-weight: 800;
        text-align: center;
    }

    .inventory-edit-meta {
        display: grid;
        gap: 10px;
        margin-top: 14px;
    }

    .inventory-edit-meta-row {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        padding: 12px 0;
        border-bottom: 1px solid #eef2f7;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
    }

    .inventory-edit-meta-row strong {
        color: #0f172a;
        text-align: right;
    }

    .product-image-dropzone {
        position: relative;
        min-height: 210px;
        border: 1.5px dashed #bfd0e4;
        border-radius: 18px;
        background: linear-gradient(150deg, #ffffff, #f8fbff);
        display: grid;
        place-items: center;
        padding: 18px;
        text-align: center;
        overflow: hidden;
        cursor: pointer;
        transition: all .2s ease;
    }

    .product-image-dropzone:hover,
    .product-image-dropzone.is-dragover {
        border-color: var(--primary);
        box-shadow: 0 14px 32px rgba(15,109,255,.12);
        transform: translateY(-1px);
    }

    .product-image-dropzone input[type=file] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        z-index: 1;
    }

    .product-image-preview {
        position: relative;
        z-index: 2;
        pointer-events: none;
        display: grid;
        place-items: center;
        gap: 10px;
    }

    .product-image-preview-box {
        position: relative;
        width: 112px;
        height: 112px;
        border-radius: 24px;
        display: grid;
        place-items: center;
        overflow: hidden;
        background: #eef6ff;
        color: var(--primary);
        box-shadow: 0 14px 28px rgba(15,23,42,.10);
        border: 1px solid rgba(148, 163, 184, .28);
    }

    .product-image-preview-box img,
    .inventory-current-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }

    .product-image-preview-box .material-symbols-rounded {
        font-size: 38px;
    }

    .product-image-remove {
        pointer-events: auto;
        position: absolute;
        top: -8px;
        left: calc(50% + 58px);
        width: 32px;
        height: 32px;
        border: 1px solid #fecaca;
        border-radius: 12px;
        background: #fff1f2;
        color: #dc2626;
        display: grid;
        place-items: center;
        cursor: pointer;
        box-shadow: 0 10px 22px rgba(220,38,38,.18);
        z-index: 2;
    }

    .product-image-remove .material-symbols-rounded {
        font-size: 18px;
    }

    .inventory-current-image {
        width: min(100%, 300px);
        aspect-ratio: 1 / 1;
        border-radius: 22px;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, .30);
        background: linear-gradient(135deg, #ffffff, #eef6ff);
        display: grid;
        place-items: center;
        color: var(--primary);
        box-shadow: 0 14px 28px rgba(15,23,42,.08);
        margin: 0 auto 14px;
    }

    .inventory-current-image .material-symbols-rounded {
        font-size: 42px;
    }

    @media (max-width: 1100px) {
        .inventory-edit-shell {
            grid-template-columns: 1fr;
        }

        .inventory-edit-preview {
            position: static;
        }
    }

    @media (max-width: 760px) {
        .inventory-edit-grid,
        .inventory-edit-grid.three {
            grid-template-columns: 1fr;
        }

        .inventory-edit-actions {
            justify-content: stretch;
        }

        .inventory-edit-actions .btn {
            width: 100%;
            justify-content: center;
        }

        .inventory-edit-preview {
            padding: 14px;
        }

        .inventory-edit-barcode {
            padding: 12px;
            border-radius: 18px;
        }

        .inventory-current-image {
            width: min(100%, 220px);
            border-radius: 20px;
            margin-bottom: 16px;
        }

        .inventory-edit-barcode .barcode-svg {
            height: 48px;
            max-width: 240px;
            margin: 0 auto;
        }

        .scan-config-row {
            grid-template-columns: 1fr;
        }

    }
</style>

<div id="inventory-edit-page">
<div class="page-header">
    <div>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/index" class="page-subtitle">&larr; Back to Inventory</a>
        <h1>Edit Product</h1>
        <p class="page-subtitle">Update product details, barcodes, pricing and stock levels.</p>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/barcodePrint/<?php echo (int) $product['id']; ?>" class="btn btn-secondary" style="color:#1d4ed8; border-color:#bfdbfe; font-weight:800;">
            <span class="material-symbols-rounded">print</span> Print Barcode Labels
        </a>
    </div>
</div>

<form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/edit/<?php echo (int) $product['id']; ?>" method="POST" enctype="multipart/form-data" data-no-ajax>
    <?php echo Security::csrfField(); ?>

    <div class="inventory-edit-shell">
        <div style="display:grid; gap:18px;">
            <section class="inventory-edit-card">
                <div class="inventory-edit-section-title">
                    <span class="material-symbols-rounded">inventory_2</span>
                    Product Identity
                </div>
                <div class="inventory-edit-grid">
                    <div class="form-group inventory-edit-full">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:7px; flex-wrap:wrap; gap:6px;">
                            <label class="form-label" style="margin:0;">Barcode / SKU Code</label>
                            <div style="display:flex; gap:6px;">
                                <button type="button" class="btn btn-secondary" onclick="generateEditBarcode()" style="font-size:11px; padding:4px 9px; font-weight:800; color:#1d4ed8; border-color:#bfdbfe;">
                                    <span class="material-symbols-rounded" style="font-size:14px;">bolt</span> Auto-Generate
                                </button>
                            </div>
                        </div>
                        <div class="field-icon-wrap">
                            <span class="material-symbols-rounded">qr_code_scanner</span>
                            <input class="form-input" type="text" id="edit_barcode_input" name="barcode" value="<?php echo Security::escape($product['barcode'] ?? ''); ?>" placeholder="Scan or enter barcode..." autocomplete="off" oninput="onEditBarcodeInput(this.value)">
                        </div>
                        <div id="edit_barcode_live_container" class="barcode-preview-live" style="margin-top:8px;">
                            <svg id="edit_barcode_live_svg"></svg>
                            <div id="edit_barcode_check_status"></div>
                        </div>
                    </div>
                    <div class="form-group inventory-edit-full">
                        <label class="form-label">Product Name</label>
                        <input class="form-input" type="text" name="name" value="<?php echo Security::escape($product['name']); ?>" required>
                    </div>
                    <div class="form-group inventory-edit-full">
                        <label class="form-label">Model / SKU Number</label>
                        <input class="form-input" type="text" name="model_number" value="<?php echo Security::escape($product['model_number']); ?>" placeholder="Enter model or SKU">
                    </div>
                </div>

            </section>


            <section class="inventory-edit-card">
                <div class="inventory-edit-section-title">
                    <span class="material-symbols-rounded">scale</span>
                    Unit & Stock
                </div>
                <div class="inventory-edit-grid">
                    <div class="form-group">
                        <label class="form-label">Unit Value</label>
                        <input class="form-input" type="number" step="0.001" min="0.001" name="unit_value" value="<?php echo (float) ($product['unit_value'] ?? 1); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit Type</label>
                        <select class="form-select" name="unit_type" data-unit-picker>
                            <?php foreach (App::allowedProductUnits() as $unit): ?>
                                <option value="<?php echo Security::escape($unit); ?>" <?php echo $unitType === $unit ? 'selected' : ''; ?>><?php echo Security::escape(App::productUnitLabel($unit)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Current Stock</label>
                        <input class="form-input" type="number" step="any" min="0" name="stock" value="<?php echo Security::escape(App::formatQuantity($product['stock'] ?? 0)); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Min Stock Alert</label>
                        <input class="form-input" type="number" step="any" min="0" name="min_stock" value="<?php echo Security::escape(App::formatQuantity($product['min_stock'] ?? 5)); ?>">
                    </div>
                    <div class="form-group inventory-edit-full" style="margin-top: 10px;">
                        <label class="box-auto-toggle" title="Show this product on your customer online store">
                            <input type="checkbox" name="is_visible_online" value="1" <?php echo !isset($product['is_visible_online']) || (int) $product['is_visible_online'] === 1 ? 'checked' : ''; ?>>
                            <span class="box-auto-switch"></span>
                            <span style="font-size:13px; font-weight:850; color:#0f172a;">Show on Online Store</span>
                        </label>
                    </div>
                </div>
            </section>

            <?php
            $hasExpiryData = !empty($product['batch_number']) || !empty($product['expiry_date']);
            ?>
            <section class="inventory-edit-card" id="edit-product-expiry-card">
                <div class="inventory-edit-section-title" style="justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="material-symbols-rounded" style="background:#fef2f2; color:#dc2626;">alarm_off</span>
                        <div>
                            <div>Batch & Expiry (Perishable Goods)</div>
                            <div style="font-size:11.5px; color:#64748b; font-weight:normal; margin-top:2px;">Track batch code & expiration dates</div>
                        </div>
                    </div>
                    <label class="box-auto-toggle" title="Enable or Disable Expiry Tracking for this product" style="margin:0;">
                        <input type="checkbox" id="toggle_edit_product_expiry" onchange="toggleEditExpiryFields(this.checked)" <?php echo $hasExpiryData ? 'checked' : ''; ?>>
                        <span class="box-auto-switch"></span>
                        <span id="toggle_edit_expiry_status_text" style="font-size:12px; font-weight:800; color:<?php echo $hasExpiryData ? '#2563eb' : '#64748b'; ?>;"><?php echo $hasExpiryData ? 'Enabled' : 'Disabled'; ?></span>
                    </label>
                </div>
                <div class="inventory-edit-grid" id="edit_expiry_fields_container" style="<?php echo $hasExpiryData ? 'display:grid;' : 'display:none;'; ?> transition:all 0.2s ease;">
                    <div class="form-group">
                        <label class="form-label">Batch Number <span style="font-size:11px; color:#64748b; font-weight:normal;">(Optional)</span></label>
                        <input class="form-input" type="text" id="edit_batch_number" name="batch_number" value="<?php echo Security::escape($product['batch_number'] ?? ''); ?>" placeholder="e.g. BATCH-2026-A">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expiry Date <span style="font-size:11px; color:#64748b; font-weight:normal;">(Optional)</span></label>
                        <input class="form-input" type="date" id="edit_expiry_date" name="expiry_date" value="<?php echo Security::escape($product['expiry_date'] ?? ''); ?>" placeholder="YYYY-MM-DD">
                    </div>
                </div>
            </section>

            <section class="inventory-edit-card">
                <div class="inventory-edit-section-title">
                    <span class="material-symbols-rounded">image</span>
                    Product Image
                </div>
                <div class="product-image-dropzone" data-product-image-dropzone>
                    <input type="file" name="product_image" accept="image/png,image/jpeg,image/webp" data-product-image-input>
                    <input type="hidden" name="remove_product_image" value="0" data-product-image-remove-field>
                    <div class="product-image-preview" data-product-image-preview>
                        <div class="product-image-preview-box">
                            <?php if ($hasProductImage): ?>
                                <img src="<?php echo Security::escape($productImageUrl); ?>" alt="" onerror="this.onerror=null; this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                                <span class="material-symbols-rounded" style="display:none;">add_photo_alternate</span>
                            <?php else: ?>
                                <span class="material-symbols-rounded">add_photo_alternate</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($hasProductImage): ?>
                            <button class="product-image-remove" type="button" data-product-image-remove aria-label="Remove product image">
                                <span class="material-symbols-rounded">close</span>
                            </button>
                        <?php endif; ?>
                        <strong><?php echo $hasProductImage ? 'Current product image' : 'Drop product image here'; ?></strong>
                        <span class="page-subtitle"><?php echo $hasProductImage ? 'Drop a new image to replace this preview.' : 'PNG, JPG or WEBP. Preview appears instantly.'; ?></span>
                    </div>
                </div>
            </section>

            <section class="inventory-edit-card">
                <div class="inventory-edit-section-title" style="justify-content:space-between; flex-wrap:wrap; gap:8px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="material-symbols-rounded">payments</span>
                        <div>
                            <div>Pricing & Profit Margin Setup</div>
                            <div style="font-size:11.5px; color:#64748b; font-weight:normal; margin-top:2px;">Adjust product retail, wholesale, and purchase cost prices</div>
                        </div>
                    </div>
                    <span id="edit_margin_badge" class="badge" style="font-size:12px; font-weight:850; padding:4px 10px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;">
                        Calculating Margin...
                    </span>
                </div>

                <div class="inventory-edit-grid three">
                    <div class="form-group">
                        <label class="form-label">Selling Price (Retail) *</label>
                        <input class="form-input" type="number" step="0.01" min="0" name="price" id="edit_selling_price" value="<?php echo (float) $product['price']; ?>" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Wholesale Price <span style="font-size:11px; color:#64748b; font-weight:normal;">(Optional)</span></label>
                        <input class="form-input" type="number" step="0.01" min="0" name="wholesale_price" id="edit_wholesale_price" value="<?php echo !empty($product['wholesale_price']) ? (float) $product['wholesale_price'] : ''; ?>" placeholder="Optional (Defaults to Selling Price)">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cost Price (Purchase) *</label>
                        <input class="form-input" type="number" step="0.01" min="0" name="cost" id="edit_cost_price" value="<?php echo (float) $product['cost']; ?>" required placeholder="0.00">
                    </div>
                </div>

                <!-- Live Profit & Margin Dashboard -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:14px; margin-top:14px;">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px;">
                        <div style="background:#fff; border:1px solid #f1f5f9; border-radius:10px; padding:10px; text-align:center;">
                            <div style="font-size:11px; color:#64748b; font-weight:700;">Gross Profit / Unit</div>
                            <div style="font-size:16px; font-weight:900; color:#0f172a;" id="edit_stat_profit">Rs 0.00</div>
                        </div>
                        <div style="background:#fff; border:1px solid #f1f5f9; border-radius:10px; padding:10px; text-align:center;">
                            <div style="font-size:11px; color:#64748b; font-weight:700;">Profit Margin</div>
                            <div style="font-size:16px; font-weight:900; color:#2563eb;" id="edit_stat_margin">0.0%</div>
                        </div>
                        <div style="background:#fff; border:1px solid #f1f5f9; border-radius:10px; padding:10px; text-align:center;">
                            <div style="font-size:11px; color:#64748b; font-weight:700;">Cost Markup</div>
                            <div style="font-size:16px; font-weight:900; color:#475569;" id="edit_stat_markup">0.0%</div>
                        </div>
                    </div>

                    <!-- Quick Margin Presets -->
                    <div style="margin-top:12px; padding-top:10px; border-top:1px dashed #e2e8f0; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                        <span style="font-size:11.5px; font-weight:800; color:#475569;">Quick Set Selling Price by Margin:</span>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="applyMarginPreset(15)" style="font-size:11px; padding:4px 8px; font-weight:800;">+15%</button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="applyMarginPreset(20)" style="font-size:11px; padding:4px 8px; font-weight:800;">+20%</button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="applyMarginPreset(25)" style="font-size:11px; padding:4px 8px; font-weight:800;">+25%</button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="applyMarginPreset(35)" style="font-size:11px; padding:4px 8px; font-weight:800;">+35%</button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="applyMarginPreset(50)" style="font-size:11px; padding:4px 8px; font-weight:800;">+50%</button>
                        </div>
                    </div>
                </div>

                <?php if (!empty($latestPurchase)): ?>
                    <?php 
                        $lpCost = (float)($latestPurchase['unit_cost'] ?? 0);
                        $currentCost = (float)($product['cost'] ?? 0);
                    ?>
                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:10px 14px; margin-top:14px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span class="material-symbols-rounded" style="color:#16a34a; font-size:20px;">local_shipping</span>
                            <div>
                                <strong style="font-size:12px; color:#14532d;">Latest Supplier Purchase Unit Cost: <?php echo App::money($lpCost); ?></strong>
                                <div style="font-size:11px; color:#166534;"><?php echo date('M d, Y', strtotime($latestPurchase['created_at'])); ?> &bull; Supplier: <?php echo Security::escape($latestPurchase['supplier_name'] ?: 'Vendor'); ?></div>
                            </div>
                        </div>
                        <?php if ($lpCost > 0 && abs($lpCost - $currentCost) > 0.01): ?>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="syncCostWithLastPurchase(<?php echo $lpCost; ?>)" style="font-size:11.5px; font-weight:800; color:#16a34a; border-color:#86efac; background:#fff;">
                                <span class="material-symbols-rounded" style="font-size:15px;">sync</span> Update Cost to <?php echo App::money($lpCost); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="inventory-edit-actions">
                    <button class="btn btn-primary" type="submit" style="font-weight:900;">
                        <span class="material-symbols-rounded">save</span>Save Changes
                    </button>
                </div>
            </section>
        </div>

        <aside class="inventory-edit-card inventory-edit-preview">
            <div class="inventory-edit-section-title">
                <span class="material-symbols-rounded">fact_check</span>
                Product Preview
            </div>
            <div class="inventory-edit-barcode" style="text-align:center;">
                <div class="inventory-current-image">
                    <?php if ($hasProductImage): ?>
                        <img src="<?php echo Security::escape($productImageUrl); ?>" alt="" onerror="this.onerror=null; this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                        <span class="material-symbols-rounded" style="display:none;">inventory_2</span>
                    <?php else: ?>
                        <span class="material-symbols-rounded">inventory_2</span>
                    <?php endif; ?>
                </div>
                <strong><?php echo Security::escape($product['name']); ?></strong>
                <div class="page-subtitle" style="margin-bottom:8px;"><?php echo Security::escape(App::formatProductUnit($product['unit_value'] ?? 1, $product['unit_type'] ?? 'pcs')); ?></div>
                
                <div style="background:#fff; border:1px solid #cbd5e1; border-radius:10px; padding:8px 10px; margin:8px 0;">
                    <svg id="edit_preview_barcode_svg" style="max-height:40px; width:100%;"></svg>
                </div>
            </div>
            <div class="inventory-edit-meta">
                <div class="inventory-edit-meta-row"><span>Selling Price</span><strong><?php echo App::money($product['price']); ?></strong></div>
                <div class="inventory-edit-meta-row"><span>Wholesale</span><strong><?php echo App::money($product['wholesale_price'] ?? $product['price']); ?></strong></div>
                <div class="inventory-edit-meta-row"><span>Stock</span><strong><?php echo (int) $product['stock']; ?> units</strong></div>
            </div>
        </aside>
    </div>
</form>

</div>

<?php ob_start(); ?>
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/JsBarcode.all.min.js"></script>
<script>
    (() => {
        const pageContainer = document.getElementById('inventory-edit-page');
        if (!pageContainer) return;

        const dropzone = pageContainer.querySelector('[data-product-image-dropzone]');
        const input = pageContainer.querySelector('[data-product-image-input]');
        const preview = pageContainer.querySelector('[data-product-image-preview]');
        const removeImageField = pageContainer.querySelector('[data-product-image-remove-field]');

        const emptyPreviewHtml = `
            <div class="product-image-preview-box"><span class="material-symbols-rounded">add_photo_alternate</span></div>
            <strong>Drop product image here</strong>
            <span class="page-subtitle">PNG, JPG or WEBP. Preview appears instantly.</span>
        `;

        const renderPreview = (file) => {
            if (!file || !preview) {
                return;
            }
            if (!file.type.startsWith('image/')) {
                window.appToast?.('Please choose a valid image file.', 'error');
                return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                if (removeImageField) removeImageField.value = '0';
                preview.innerHTML = `
                    <div class="product-image-preview-box">
                        <img src="${reader.result}" alt="Product preview">
                    </div>
                    <button class="product-image-remove" type="button" data-product-image-remove aria-label="Remove product image">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                    <strong>Drop product image here</strong>
                    <span class="page-subtitle">PNG, JPG or WEBP. Preview appears instantly.</span>
                `;
            };
            reader.readAsDataURL(file);
        };

        pageContainer.addEventListener('click', (event) => {
            if (event.target.closest('[data-product-image-remove]')) {
                event.preventDefault();
                if (input) input.value = '';
                if (removeImageField) removeImageField.value = '1';
                if (preview) preview.innerHTML = emptyPreviewHtml;
            }
        });

        if (dropzone && input) {
            input.addEventListener('change', () => renderPreview(input.files?.[0]));
            ['dragenter', 'dragover'].forEach((eventName) => {
                dropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    dropzone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'drop'].forEach((eventName) => {
                dropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    dropzone.classList.remove('is-dragover');
                });
            });
            dropzone.addEventListener('drop', (event) => {
                const file = event.dataTransfer?.files?.[0];
                if (!file) return;
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
                renderPreview(file);
            });
        }

        window.toggleEditExpiryFields = function(enabled) {
            const container = document.getElementById('edit_expiry_fields_container');
            const statusText = document.getElementById('toggle_edit_expiry_status_text');
            const batchInput = document.getElementById('edit_batch_number');
            const expiryInput = document.getElementById('edit_expiry_date');
            if (container) {
                container.style.display = enabled ? 'grid' : 'none';
            }
            if (statusText) {
                statusText.textContent = enabled ? 'Enabled' : 'Disabled';
                statusText.style.color = enabled ? '#2563eb' : '#64748b';
            }
            if (!enabled) {
                if (batchInput) batchInput.value = '';
                if (expiryInput) expiryInput.value = '';
            }
        };

        // --- EDIT BARCODE LOGIC ---
        const currentProductId = <?php echo (int) $product['id']; ?>;
        let barcodeDebounceTimer = null;

        window.generateEditBarcode = async function() {
            try {
                const res = await fetch('<?= defined("BASE_URL") ? BASE_URL : "" ?>/inventory/generateBarcode');
                const data = await res.json();
                if (data && data.success && data.barcode) {
                    const input = document.getElementById('edit_barcode_input');
                    if (input) {
                        input.value = data.barcode;
                        window.onEditBarcodeInput(data.barcode);
                        window.appToast?.('New Barcode generated!', 'success');
                    }
                }
            } catch(e) {
                console.error("Barcode generation error:", e);
            }
        };

        window.onEditBarcodeInput = function(val) {
            const raw = (val || '').trim();
            const container = document.getElementById('edit_barcode_live_container');
            const svgEl = document.getElementById('edit_barcode_live_svg');
            const previewSvg = document.getElementById('edit_preview_barcode_svg');
            const statusEl = document.getElementById('edit_barcode_check_status');

            if (!container || !svgEl) return;

            if (raw === '') {
                container.style.display = 'none';
                if (statusEl) statusEl.innerHTML = '';
                if (previewSvg) previewSvg.innerHTML = '';
                return;
            }

            container.style.display = 'flex';

            // Render live SVG barcode
            if (window.JsBarcode) {
                try {
                    JsBarcode(svgEl, raw, {
                        format: "CODE128",
                        width: 1.6,
                        height: 34,
                        displayValue: true,
                        fontSize: 11,
                        margin: 2
                    });
                    if (previewSvg) {
                        JsBarcode(previewSvg, raw, {
                            format: "CODE128",
                            width: 1.5,
                            height: 32,
                            displayValue: true,
                            fontSize: 10,
                            margin: 2
                        });
                    }
                } catch(e) {
                    console.warn("JsBarcode error:", e);
                }
            }

            // Debounced duplicate check
            if (barcodeDebounceTimer) clearTimeout(barcodeDebounceTimer);
            barcodeDebounceTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`<?= defined("BASE_URL") ? BASE_URL : "" ?>/inventory/checkBarcode?barcode=${encodeURIComponent(raw)}&exclude_id=${currentProductId}`);
                    const data = await res.json();
                    if (data && data.success) {
                        if (data.exists && data.product) {
                            if (statusEl) {
                                const stockVal = parseFloat(data.product.stock);
                                const cleanStock = !isNaN(stockVal) ? (Number.isInteger(stockVal) ? String(stockVal) : parseFloat(stockVal.toFixed(3)).toString()) : '0';
                                const unitStr = String(data.product.unit_type || 'pcs').toUpperCase();
                                const prodName = String(data.product.name || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

                                statusEl.innerHTML = `
                                    <div class="barcode-feedback-badge badge-warning">
                                        <svg viewBox="0 0 20 20" fill="currentColor" class="badge-icon"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                                        <span>Existing Item: <strong>"${prodName}"</strong> (${cleanStock} ${unitStr} in stock)</span>
                                    </div>
                                `;
                            }
                        } else {
                            if (statusEl) {
                                statusEl.innerHTML = `
                                    <div class="barcode-feedback-badge badge-success">
                                        <svg viewBox="0 0 20 20" fill="currentColor" class="badge-icon"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                                        <span>Barcode Available</span>
                                    </div>
                                `;
                            }
                        }
                    }
                } catch(e) {
                    console.error("Barcode check error:", e);
                }
            }, 300);
        };

        window.openEditCameraScanner = function() {
            // Clean up any existing active streams across pages
            if (typeof window.stopAllActiveCameraStreams === 'function') {
                window.stopAllActiveCameraStreams();
            }

            const modalRoot = document.getElementById('app-modal-root') || document.body;

            const modalHtml = `
                <div class="barcode-camera-modal open" id="barcode-cam-modal-container">
                    <div class="barcode-camera-card">
                        <div class="barcode-camera-head">
                            <div class="edit-scanner-head-info">
                                <div class="edit-scanner-head-icon">
                                    <span class="material-symbols-rounded">photo_camera</span>
                                </div>
                                <div class="edit-scanner-head-text">
                                    <strong>Camera Barcode Scanner</strong>
                                    <span>Align product barcode inside frame</span>
                                </div>
                            </div>
                            <button class="barcode-camera-close" type="button" onclick="closeEditCameraScanner()" aria-label="Close scanner">
                                <span class="material-symbols-rounded">close</span>
                            </button>
                        </div>
                        <div class="barcode-camera-stage">
                            <video id="edit-barcode-cam-video" autoplay muted playsinline></video>
                            <div class="edit-scanner-reticle">
                                <span class="edit-reticle-corner tl"></span>
                                <span class="edit-reticle-corner tr"></span>
                                <span class="edit-reticle-corner bl"></span>
                                <span class="edit-reticle-corner br"></span>
                                <div class="edit-reticle-laser"></div>
                                <div class="edit-reticle-caption">Center barcode inside frame</div>
                            </div>
                        </div>
                        <div class="barcode-camera-foot">
                            <div class="edit-camera-status-pill">
                                <span class="edit-camera-status-dot"></span>
                                <span id="edit-barcode-cam-status">Camera ready • Scanning...</span>
                            </div>
                            <button type="button" class="edit-scanner-cancel-btn" onclick="closeEditCameraScanner()">Cancel</button>
                        </div>
                    </div>
                </div>
            `;
            modalRoot.insertAdjacentHTML('beforeend', modalHtml);

            const videoEl = document.getElementById('edit-barcode-cam-video');
            const statusEl = document.getElementById('edit-barcode-cam-status');
            let isScanned = false;

            const onDetected = (code) => {
                if (isScanned) return;
                isScanned = true;
                const cleanCode = String(code || '').trim();
                if (!cleanCode) return;

                const input = document.getElementById('edit_barcode_input');
                if (input) {
                    input.value = cleanCode;
                    if (typeof window.onEditBarcodeInput === 'function') {
                        window.onEditBarcodeInput(cleanCode);
                    }
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }

                if (typeof window.playScannerBeep === 'function') {
                    window.playScannerBeep('success');
                }
                window.appToast?.(`Scanned: ${cleanCode}`, 'success');
                closeEditCameraScanner();
            };

            // Start camera with ideal back camera
            navigator.mediaDevices?.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            }).then(stream => {
                window.__editCamStream = stream;
                if (videoEl) {
                    videoEl.srcObject = stream;
                    videoEl.play().catch(() => {});
                }

                // 1. Native BarcodeDetector (fastest on mobile)
                if ('BarcodeDetector' in window) {
                    try {
                        const detector = new BarcodeDetector({ formats: ['code_128', 'ean_13', 'ean_8', 'qr_code', 'upc_a', 'upc_e', 'code_39'] });
                        const loop = async () => {
                            if (!window.__editCamStream || isScanned) return;
                            try {
                                if (videoEl && videoEl.readyState >= 2) {
                                    const codes = await detector.detect(videoEl);
                                    if (codes && codes.length > 0 && codes[0].rawValue) {
                                        onDetected(codes[0].rawValue);
                                        return;
                                    }
                                }
                            } catch(e) {}
                            requestAnimationFrame(loop);
                        };
                        requestAnimationFrame(loop);
                        return;
                    } catch(e) {}
                }

                // 2. ZXing fallback if BarcodeDetector not available
                if (window.ZXing && videoEl) {
                    try {
                        const codeReader = new ZXing.BrowserMultiFormatReader();
                        window.__editCamZxingReader = codeReader;
                        codeReader.decodeFromVideoElement(videoEl, (result, err) => {
                            if (result && result.getText() && !isScanned) {
                                onDetected(result.getText());
                            }
                        });
                    } catch(e) {}
                }
            }).catch(err => {
                console.warn('Camera stream error:', err);
                if (statusEl) statusEl.textContent = 'Camera permission denied or unavailable';
            });
        };

        window.closeEditCameraScanner = function() {
            if (typeof window.stopAllActiveCameraStreams === 'function') {
                window.stopAllActiveCameraStreams();
            } else {
                const modal = document.getElementById('barcode-cam-modal-container');
                if (modal) modal.remove();
            }
        };

        // Render initial barcode on load
        const initBarcode = document.getElementById('edit_barcode_input')?.value || '';
        if (initBarcode) {
            setTimeout(() => window.onEditBarcodeInput(initBarcode), 50);
        }

        // ==========================================
        // PRICING SETUP & PROFIT MARGIN CALCULATOR
        // ==========================================
        const editSellingPriceInput = document.getElementById('edit_selling_price');
        const editCostPriceInput = document.getElementById('edit_cost_price');
        const editStatProfit = document.getElementById('edit_stat_profit');
        const editStatMargin = document.getElementById('edit_stat_margin');
        const editStatMarkup = document.getElementById('edit_stat_markup');
        const editMarginBadge = document.getElementById('edit_margin_badge');

        const updatePricingMarginAnalysis = () => {
            const cost = Math.max(0, parseFloat(editCostPriceInput?.value || 0));
            const price = Math.max(0, parseFloat(editSellingPriceInput?.value || 0));
            const profit = price - cost;

            const marginPct = price > 0 ? ((profit / price) * 100) : 0;
            const markupPct = cost > 0 ? ((profit / cost) * 100) : 0;

            if (editStatProfit) {
                editStatProfit.textContent = (profit < 0 ? '-' : '') + 'Rs ' + Math.abs(profit).toFixed(2);
                editStatProfit.style.color = profit >= 0 ? '#0f172a' : '#dc2626';
            }
            if (editStatMargin) {
                editStatMargin.textContent = marginPct.toFixed(1) + '%';
                editStatMargin.style.color = marginPct >= 20 ? '#16a34a' : (marginPct > 0 ? '#b45309' : '#dc2626');
            }
            if (editStatMarkup) {
                editStatMarkup.textContent = markupPct.toFixed(1) + '%';
            }

            if (editMarginBadge) {
                if (price <= 0) {
                    editMarginBadge.textContent = 'Price Not Set';
                    editMarginBadge.style.background = '#f1f5f9';
                    editMarginBadge.style.color = '#64748b';
                    editMarginBadge.style.borderColor = '#e2e8f0';
                } else if (profit > 0) {
                    editMarginBadge.textContent = `✓ ${marginPct.toFixed(1)}% Profit Margin (+Rs ${profit.toFixed(2)})`;
                    editMarginBadge.style.background = marginPct >= 20 ? '#ecfdf5' : '#fffbeb';
                    editMarginBadge.style.color = marginPct >= 20 ? '#059669' : '#b45309';
                    editMarginBadge.style.borderColor = marginPct >= 20 ? '#a7f3d0' : '#fde68a';
                } else if (profit === 0) {
                    editMarginBadge.textContent = 'Break-even (0% Margin)';
                    editMarginBadge.style.background = '#f8fafc';
                    editMarginBadge.style.color = '#475569';
                    editMarginBadge.style.borderColor = '#cbd5e1';
                } else {
                    editMarginBadge.textContent = `⚠ Selling at Loss (-Rs ${Math.abs(profit).toFixed(2)})`;
                    editMarginBadge.style.background = '#fef2f2';
                    editMarginBadge.style.color = '#dc2626';
                    editMarginBadge.style.borderColor = '#fecaca';
                }
            }
        };

        window.applyMarginPreset = function(percent) {
            const cost = Math.max(0, parseFloat(editCostPriceInput?.value || 0));
            if (cost <= 0) {
                window.appToast?.('Please enter a Cost Price first.', 'info');
                return;
            }
            const marginFraction = percent / 100;
            const newPrice = Math.round((cost / (1 - marginFraction)) * 100) / 100;
            if (editSellingPriceInput) {
                editSellingPriceInput.value = newPrice.toFixed(2);
                updatePricingMarginAnalysis();
                window.appToast?.(`Selling price updated to Rs ${newPrice.toFixed(2)} (+${percent}% margin)`, 'success');
            }
        };

        window.syncCostWithLastPurchase = function(lastCost) {
            if (editCostPriceInput && lastCost > 0) {
                editCostPriceInput.value = Number(lastCost).toFixed(2);
                updatePricingMarginAnalysis();
                window.appToast?.(`Cost price synchronized to Rs ${Number(lastCost).toFixed(2)}`, 'success');
            }
        };

        editSellingPriceInput?.addEventListener('input', updatePricingMarginAnalysis);
        editCostPriceInput?.addEventListener('input', updatePricingMarginAnalysis);
        updatePricingMarginAnalysis();
    })();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>

