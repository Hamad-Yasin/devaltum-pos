<?php ob_start(); ?>

<style>
    .inventory-tabs {
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

    .inventory-tab-btn {
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

    .inventory-tab-btn .tab-icon {
        font-size: 18px;
        transition: transform .2s ease;
    }

    .inventory-tab-btn:hover {
        color: #0f172a;
        background: rgba(255,255,255,0.7);
    }

    .inventory-tab-btn.active {
        background: linear-gradient(135deg, #1d4ed8, #0f6dff);
        color: #ffffff;
        box-shadow: 0 4px 14px rgba(15,109,255,0.35), 0 1px 3px rgba(0,0,0,0.1);
    }

    .inventory-tab-btn.active .tab-icon {
        color: #ffffff;
        transform: scale(1.08);
    }

    .inventory-tab-btn .tab-count-badge {
        font-size: 11px;
        font-weight: 850;
        padding: 2px 7px;
        border-radius: 999px;
        background: #e2e8f0;
        color: #475569;
    }

    .inventory-tab-btn.active .tab-count-badge {
        background: rgba(255,255,255,0.25);
        color: #ffffff;
    }

    .inventory-tab-pane { display:none; }
    .inventory-tab-pane.active { display:block; }

    .add-product-card {
        padding: 24px;
        overflow: visible;
    }

    .inventory-form-shell {
        display:grid;
        grid-template-columns:minmax(0, 1.05fr) minmax(0, .95fr);
        gap:18px;
        width: 100%;
        max-width: 1280px;
    }

    .inventory-col {
        border:1px solid var(--border);
        border-radius:18px;
        background:#fff;
        padding:22px;
        box-shadow:0 14px 34px rgba(15,23,42,.06);
        min-width:0;
    }

    .inventory-col-title {
        font-size:15px;
        font-weight:900;
        color:#0f172a;
        margin-bottom:18px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom:12px;
        border-bottom:1px solid #e8eef6;
    }

    .inventory-col .form-group { margin-bottom:15px; min-width:0; }
    .inventory-col .form-group:last-child { margin-bottom:0; }

    .inventory-col .form-label {
        display:block;
        margin-bottom:7px;
        color:#0f172a;
        font-size:13px;
        font-weight:850;
    }

    .inventory-col .form-input,
    .inventory-col .form-select,
    .inventory-col .form-textarea {
        width:100%;
        min-height:44px;
    }

    .add-product-actions {
        margin-top:20px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        flex-wrap:wrap;
        gap:12px;
        width:100%;
        max-width:1280px;
    }

    .add-product-actions .btn {
        min-height:46px;
        padding-inline:22px;
    }

    .form-hint {
        font-size:12px;
        color:var(--text-muted);
        font-weight:600;
    }

    .add-product-workspace {
        max-width: 1320px;
        width: 100%;
    }

    .add-product-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.08fr) minmax(360px, .92fr);
        gap: 18px;
        align-items: start;
    }

    .add-product-main,
    .add-product-side {
        display: grid;
        gap: 18px;
        min-width: 0;
    }

    .add-product-panel {
        border: 1px solid #dbe4f0;
        border-radius: 20px;
        background: linear-gradient(180deg, #ffffff, #fbfdff);
        box-shadow: 0 18px 45px rgba(15, 23, 42, .07);
        padding: 20px;
        min-width: 0;
        overflow: visible;
    }

    .add-product-panel-head {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 14px;
        margin-bottom: 16px;
        border-bottom: 1px solid #e6eef8;
    }

    .add-product-panel-head h3 {
        margin: 0;
        color: #0f172a;
        font-size: 16px;
        line-height: 1.2;
        font-weight: 900;
    }

    .box-barcode-head {
        justify-content: space-between;
    }

    .box-barcode-title {
        display: flex;
        align-items: center;
        gap: 12px;
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

    .add-product-panel-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        color: #fff;
        background: linear-gradient(135deg, #2563eb, #0891b2);
        box-shadow: 0 12px 24px rgba(37, 99, 235, .20);
        flex: 0 0 auto;
    }

    .add-product-panel-icon.green {
        background: linear-gradient(135deg, #059669, #0d9488);
        box-shadow: 0 12px 24px rgba(5, 150, 105, .18);
    }

    .add-product-panel-icon.amber {
        background: linear-gradient(135deg, #d97706, #e11d48);
        box-shadow: 0 12px 24px rgba(217, 119, 6, .17);
    }

    .add-product-panel-icon .material-symbols-rounded {
        font-size: 23px;
    }

    .add-product-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        min-width: 0;
    }

    .add-product-grid.three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .add-product-grid .form-group,
    .add-product-panel .form-group {
        margin: 0;
        min-width: 0;
    }

    .add-product-grid .wide,
    .add-product-panel .wide {
        grid-column: 1 / -1;
    }

    .add-product-panel .form-label {
        display: block;
        margin-bottom: 7px;
        color: #0f172a;
        font-size: 12px;
        font-weight: 900;
    }

    .add-product-panel .form-input,
    .add-product-panel .form-select,
    .add-product-panel .form-textarea {
        width: 100%;
        min-height: 46px;
        border-radius: 14px;
    }

    .add-product-panel .field-icon-wrap .form-input {
        padding-left: 44px;
    }

    .add-product-panel .field-icon-wrap > .material-symbols-rounded {
        left: 14px;
        color: #64748b;
    }

    .add-product-panel .scan-config-card {
        margin-top: 0;
        border-radius: 18px;
        background: #f8fbff;
        box-shadow: none;
    }

    .add-product-panel .scan-config-title {
        justify-content: space-between;
        border-bottom: 1px solid #e6eef8;
        padding-bottom: 10px;
    }

    .add-product-panel .scan-config-title .material-symbols-rounded {
        color: #2563eb;
    }

    .add-product-panel .variant-config-row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        padding: 14px;
        border-radius: 16px;
        background: #fff;
    }

    .add-product-panel .product-image-dropzone {
        min-height: 248px;
        border-radius: 20px;
        background: linear-gradient(145deg, #f8fbff, #ffffff);
    }

    .add-product-panel .product-image-preview-box {
        width: 112px;
        height: 112px;
        border-radius: 26px;
    }

    .add-product-panel .product-image-remove {
        left: calc(50% + 58px);
    }

    .add-product-actions {
        position: sticky;
        bottom: 14px;
        z-index: 20;
        margin-top: 18px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border: 1px solid rgba(191, 219, 254, .9);
        border-radius: 18px;
        background: rgba(255, 255, 255, .92);
        box-shadow: 0 18px 42px rgba(15, 23, 42, .12);
        backdrop-filter: blur(12px);
    }

    .add-product-actions .btn {
        min-height: 48px;
        padding-inline: 24px;
        border-radius: 14px;
    }

    .inventory-head-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .inventory-head-actions .btn {
        white-space: nowrap;
    }

    .inventory-filter-actions {
        display: flex;
        gap: 10px;
    }

    .barcode-mini {
        width: 172px;
        min-height: 74px;
        border: 1px solid #dbe4f0;
        border-radius: 8px;
        background: #fff;
        padding: 7px 8px 6px;
        box-shadow: 0 8px 18px rgba(15,23,42,.06);
    }

    .barcode-mini-title {
        display: block;
        color: #0f172a;
        font-size: 10px;
        font-weight: 800;
        line-height: 1.15;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .barcode-mini .barcode-svg {
        display: block;
        width: 100%;
        height: 34px;
        margin: 2px 0;
    }

    .barcode-mini-code {
        display: block;
        color: #475569;
        font: 700 10px/1.1 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        letter-spacing: .6px;
        text-align: center;
    }

    .barcode-preview-card {
        border: 1px dashed #bfdbfe;
        border-radius: 14px;
        background: linear-gradient(150deg, #f8fbff, #fff);
        padding: 12px;
    }

    .product-image-dropzone {
        position: relative;
        min-height: 190px;
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
        pointer-events: none;
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
        width: 92px;
        height: 92px;
        border-radius: 22px;
        display: grid;
        place-items: center;
        overflow: hidden;
        background: #eef6ff;
        color: var(--primary);
        box-shadow: 0 14px 28px rgba(15,23,42,.10);
        border: 1px solid rgba(148, 163, 184, .28);
    }

    .product-image-preview-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }

    .product-image-preview-box .material-symbols-rounded {
        font-size: 36px;
    }

    .product-image-remove {
        pointer-events: auto;
        position: absolute;
        top: -8px;
        left: calc(50% + 48px);
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

    .product-image-preview strong {
        color: #0f172a;
    }

    .product-image-actions {
        pointer-events: auto;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 2px;
    }

    .product-image-action {
        border: 1px solid #bfdbfe;
        background: #fff;
        color: #1d4ed8;
        border-radius: 999px;
        padding: 7px 10px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 900;
        cursor: pointer;
        box-shadow: 0 8px 16px rgba(15,23,42,.06);
    }

    .product-image-action .material-symbols-rounded {
        font-size: 17px;
    }

    /* Modern Inventory Barcode Camera Scanner (Scoped) */
    .inventory-barcode-scanner-modal {
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

    .inventory-barcode-scanner-modal.open {
        display: flex !important;
    }

    .inventory-scanner-card {
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
        animation: invScannerPop 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    .inventory-scanner-head {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 14px 18px !important;
        background: #070d1e !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .inventory-scanner-head-info {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
    }

    .inventory-scanner-head-icon {
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

    .inventory-scanner-head-icon .material-symbols-rounded {
        font-size: 20px !important;
    }

    .inventory-scanner-head-text strong {
        display: block !important;
        font-size: 14.5px !important;
        font-weight: 800 !important;
        color: #ffffff !important;
        letter-spacing: -0.2px !important;
        line-height: 1.2 !important;
    }

    .inventory-scanner-head-text span {
        display: block !important;
        font-size: 11px !important;
        color: #94a3b8 !important;
        margin-top: 2px !important;
    }

    .inventory-scanner-close {
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

    .inventory-scanner-close:hover {
        background: rgba(239, 68, 68, 0.8) !important;
        border-color: #ef4444 !important;
        color: #fff !important;
        transform: scale(1.05) !important;
    }

    .inventory-scanner-stage {
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

    .inventory-scanner-stage video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block !important;
        background: #020617 !important;
    }

    .inventory-scanner-reticle {
        position: absolute !important;
        inset: 14% 10% !important;
        border-radius: 18px !important;
        box-shadow: 0 0 0 9999px rgba(2, 6, 23, 0.58) !important;
        pointer-events: none !important;
        border: 1.5px solid rgba(255, 255, 255, 0.15) !important;
    }

    .inv-reticle-corner {
        position: absolute !important;
        width: 22px !important;
        height: 22px !important;
        border-color: #22c55e !important;
        border-style: solid !important;
        pointer-events: none !important;
    }

    .inv-reticle-corner.tl { top: -2px !important; left: -2px !important; border-width: 3.5px 0 0 3.5px !important; border-top-left-radius: 16px !important; }
    .inv-reticle-corner.tr { top: -2px !important; right: -2px !important; border-width: 3.5px 3.5px 0 0 !important; border-top-right-radius: 16px !important; }
    .inv-reticle-corner.bl { bottom: -2px !important; left: -2px !important; border-width: 0 0 3.5px 3.5px !important; border-bottom-left-radius: 16px !important; }
    .inv-reticle-corner.br { bottom: -2px !important; right: -2px !important; border-width: 0 3.5px 3.5px 0 !important; border-bottom-right-radius: 16px !important; }

    .inv-reticle-laser {
        position: absolute !important;
        left: 4% !important;
        right: 4% !important;
        height: 2px !important;
        background: linear-gradient(90deg, transparent, #22c55e, #86efac, #22c55e, transparent) !important;
        box-shadow: 0 0 10px #22c55e, 0 0 20px rgba(34, 197, 94, 0.8) !important;
        animation: invLaserScan 1.8s ease-in-out infinite alternate !important;
    }

    .inv-reticle-caption {
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

    .inventory-scanner-foot {
        padding: 12px 18px !important;
        background: #070d1e !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .inv-camera-status-pill {
        display: inline-flex !important;
        align-items: center !important;
        gap: 7px !important;
        font-size: 11.5px !important;
        font-weight: 700 !important;
        color: #cbd5e1 !important;
    }

    .inv-camera-status-dot {
        width: 8px !important;
        height: 8px !important;
        border-radius: 50% !important;
        background: #22c55e !important;
        box-shadow: 0 0 8px #22c55e !important;
        animation: invCameraPulseDot 1.4s infinite ease-in-out !important;
    }

    .inventory-scanner-cancel {
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

    .inventory-scanner-cancel:hover {
        background: rgba(255, 255, 255, 0.15) !important;
        color: #fff !important;
    }

    @keyframes invLaserScan {
        0% { top: 8%; opacity: 0.35; }
        50% { opacity: 1; }
        100% { top: 92%; opacity: 0.35; }
    }

    @keyframes invCameraPulseDot {
        0%, 100% { transform: scale(0.9); opacity: 0.7; }
        50% { transform: scale(1.2); opacity: 1; }
    }

    @keyframes invScannerPop {
        from { opacity: 0; transform: scale(0.92) translateY(12px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* Professional Compact Barcode Feedback Badge */
    .barcode-preview-live {
        margin-top: 8px;
        padding: 8px 12px;
        border: 1px dashed #bfdbfe;
        border-radius: 12px;
        background: #f8fbff;
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
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

    .barcode-feedback-badge a.badge-edit-link {
        color: #1d4ed8 !important;
        text-decoration: underline !important;
        font-weight: 850 !important;
        margin-left: 6px !important;
    }

    .product-camera-modal {
        position: fixed;
        inset: 0;
        z-index: 10020;
        display: none;
        place-items: center;
        padding: 18px;
        background: rgba(2, 6, 23, .76);
        backdrop-filter: blur(12px);
    }

    .product-camera-modal.open {
        display: grid;
    }

    .product-camera-card {
        width: min(94vw, 520px);
        overflow: hidden;
        border-radius: 24px;
        background: #07111f;
        border: 1px solid rgba(255,255,255,.14);
        box-shadow: 0 28px 70px rgba(0,0,0,.42);
        color: #fff;
    }

    .product-camera-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid rgba(255,255,255,.12);
    }

    .product-camera-head span {
        color: #cbd5e1;
        font-size: 12px;
        font-weight: 800;
    }

    .product-camera-close {
        width: 38px;
        height: 38px;
        border: 1px solid rgba(255,255,255,.14);
        border-radius: 13px;
        background: rgba(255,255,255,.08);
        color: #fff;
        cursor: pointer;
        font-size: 24px;
    }

    .product-camera-stage {
        position: relative;
        aspect-ratio: 1 / 1;
        background: #020617;
        overflow: hidden;
    }

    .product-camera-stage video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .object-tracker-frame {
        position: absolute;
        inset: 9%;
        border: 1px solid rgba(255,255,255,.52);
        border-radius: 26px;
        box-shadow: inset 0 0 0 1px rgba(34,197,94,.25), 0 0 34px rgba(34,197,94,.18);
        pointer-events: none;
    }

    .object-tracker-frame::before,
    .object-tracker-frame::after {
        content: '';
        position: absolute;
        left: 12%;
        right: 12%;
        height: 2px;
        background: linear-gradient(90deg, transparent, #22c55e, transparent);
        opacity: .9;
    }

    .object-tracker-frame::before { top: 36%; }
    .object-tracker-frame::after { bottom: 36%; }

    .product-camera-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        padding: 14px;
        background: #0f172a;
    }

    .product-camera-actions .btn {
        justify-content: center;
    }

    .unit-selector-grid {
        display:grid;
        grid-template-columns: minmax(0, 1fr) minmax(120px, .8fr);
        gap:10px;
    }

    .scan-config-card {
        border: 1px solid #dbe4f0;
        border-radius: 18px;
        background: linear-gradient(150deg, #f8fbff, #ffffff);
        padding: 16px;
        display: grid;
        gap: 12px;
        overflow: visible;
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
        min-width:0;
        max-width:100%;
    }

    .variant-config-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        align-items: end;
        min-width:0;
        max-width:100%;
        padding: 12px;
        border: 1px solid #e6eef8;
        border-radius: 14px;
        background: #fff;
    }

    .variant-config-row + .variant-config-row {
        margin-top: 2px;
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
        position: fixed;
        inset: 0;
        z-index: 10030;
        display: none;
        place-items: center;
        padding: 16px;
        background: rgba(2,6,23,.72);
        backdrop-filter: blur(12px);
    }

    .barcode-camera-modal.open {
        display: grid;
    }

    .barcode-camera-card {
        width: min(94vw, 640px);
        overflow: hidden;
        border-radius: 22px;
        background: #07111f;
        border: 1px solid rgba(255,255,255,.16);
        box-shadow: 0 30px 80px rgba(0,0,0,.35);
        color: #fff;
    }

    .barcode-camera-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid rgba(255,255,255,.12);
    }

    .barcode-camera-head span {
        display: block;
        margin-top: 2px;
        color: #cbd5e1;
        font-size: 12px;
        font-weight: 700;
    }

    .barcode-camera-close {
        position: relative;
        z-index: 3;
        width: 38px;
        height: 38px;
        border: 1px solid rgba(255,255,255,.16);
        border-radius: 13px;
        background: rgba(255,255,255,.08);
        color: #fff;
        cursor: pointer;
    }

    .barcode-camera-stage {
        position: relative;
        aspect-ratio: 16 / 10;
        min-height: 280px;
        background: #020617;
        overflow: hidden;
    }

    .barcode-camera-stage video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        background: #020617;
        opacity: 1;
    }

    .barcode-scan-frame {
        position: absolute;
        left: 50%;
        top: 50%;
        width: min(82%, 430px);
        aspect-ratio: 2.6 / 1;
        transform: translate(-50%, -50%);
        border: 2px solid rgba(226,232,240,.92);
        border-radius: 18px;
        box-shadow: 0 0 0 999px rgba(2,6,23,.36), 0 0 28px rgba(34,197,94,.28);
        pointer-events: none;
    }

    .barcode-scan-frame::after {
        content: '';
        position: absolute;
        left: 10%;
        right: 10%;
        top: 8%;
        height: 2px;
        background: linear-gradient(90deg, transparent, #22c55e, transparent);
        box-shadow: 0 0 18px rgba(34,197,94,.9);
        animation: barcodeScannerLine 1.4s ease-in-out infinite;
    }

    @keyframes barcodeScannerLine {
        0%, 100% { top: 8%; opacity: 0.35; }
        50% { top: 92%; opacity: 1; }
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

    @media (max-width: 900px) {
        .scan-config-row,
        .variant-config-row {
            grid-template-columns: 1fr;
        }

        .scan-config-card {
            padding: 14px;
        }
    }

    @media (max-width: 1120px) and (min-width: 861px) {
        .inventory-form-shell {
            grid-template-columns: 1fr;
        }

        .add-product-shell {
            grid-template-columns: 1fr;
        }
    }

    .csv-import-shell {
        max-width: 1040px;
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(300px, .85fr);
        gap: 18px;
        align-items: stretch;
    }

    .csv-dropzone {
        min-height: 260px;
        border: 1.5px dashed #bfd0e4;
        border-radius: 20px;
        background:
            linear-gradient(150deg, rgba(248,251,255,.92), rgba(255,255,255,.98)),
            radial-gradient(circle at top right, rgba(15,109,255,.10), transparent 30%);
        display: grid;
        place-items: center;
        padding: 28px;
        text-align: center;
        cursor: pointer;
        transition: all .2s ease;
        position: relative;
        overflow: hidden;
    }

    .csv-dropzone:hover,
    .csv-dropzone.is-dragover {
        border-color: var(--primary);
        box-shadow: 0 18px 44px rgba(15,109,255,.12);
        transform: translateY(-1px);
    }

    .csv-dropzone-icon {
        width: 68px;
        height: 68px;
        margin: 0 auto 14px;
        border-radius: 20px;
        display: grid;
        place-items: center;
        background: #0f172a;
        color: #fff;
        box-shadow: 0 16px 30px rgba(15,23,42,.18);
    }

    .csv-dropzone-icon .material-symbols-rounded {
        font-size: 34px;
    }

    .csv-dropzone-title {
        font-size: 20px;
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 6px;
    }

    .csv-file-name {
        margin-top: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: #eef4ff;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 900;
        max-width: 100%;
    }

    .csv-file-name span:last-child {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .csv-import-side {
        border: 1px solid var(--border);
        border-radius: 20px;
        background: linear-gradient(160deg, #0f172a, #172554);
        color: #fff;
        padding: 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 18px;
        box-shadow: 0 18px 42px rgba(15,23,42,.16);
    }

    .csv-import-side h3 {
        margin: 0 0 8px;
        font-size: 20px;
    }

    .csv-format-list {
        display: grid;
        gap: 9px;
        margin-top: 16px;
    }

    .csv-format-list span {
        display: flex;
        align-items: center;
        gap: 8px;
        color: rgba(255,255,255,.82);
        font-size: 13px;
        font-weight: 700;
    }

    .csv-format-list .material-symbols-rounded {
        color: #93c5fd;
        font-size: 18px;
    }

    .csv-import-actions {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-top: 18px;
    }

    .purchase-ledger-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(320px, .85fr);
        gap: 18px;
        align-items: start;
    }

    .purchase-form-card {
        border: 1px solid var(--border);
        border-radius: 22px;
        background:
            linear-gradient(150deg, #ffffff, #f8fbff),
            radial-gradient(circle at top right, rgba(15,109,255,.10), transparent 35%);
        box-shadow: 0 20px 48px rgba(15,23,42,.09);
        padding: 24px;
    }

    .purchase-form-head {
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:16px;
        margin-bottom:18px;
        padding-bottom:18px;
        border-bottom:1px solid #e2e8f0;
    }

    .purchase-form-icon {
        width:54px;
        height:54px;
        border-radius:18px;
        display:grid;
        place-items:center;
        background:#0f172a;
        color:#fff;
        box-shadow:0 16px 30px rgba(15,23,42,.18);
        flex:0 0 auto;
    }

    .purchase-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .purchase-form-grid .full {
        grid-column: 1 / -1;
    }

    .searchable-dropdown-menu {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }
    .searchable-dropdown-menu::-webkit-scrollbar {
        width: 6px;
    }
    .searchable-dropdown-menu::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 999px;
    }
    .searchable-dropdown-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 9px 12px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.15s ease;
        border-bottom: 1px solid #f8fafc;
        gap: 10px;
    }
    .searchable-dropdown-item:last-child {
        border-bottom: none;
    }
    .searchable-dropdown-item:hover,
    .searchable-dropdown-item.is-selected {
        background: #f0f7ff;
    }

    /* =========================================
       PREMIUM CUSTOM SELECTOR SYSTEM (COMPACT & SLEEK)
       ========================================= */
    .premium-select-wrap {
        position: relative;
        width: 100%;
        user-select: none;
    }

    .premium-select-trigger {
        width: 100%;
        min-height: 38px;
        height: 38px;
        padding: 4px 10px;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
    }

    .premium-select-trigger:hover {
        border-color: #94a3b8;
        background: #ffffff;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.05);
    }

    .premium-select-wrap.is-open .premium-select-trigger {
        border-color: #2563eb;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12), 0 4px 12px rgba(37, 99, 235, 0.06);
    }

    .premium-select-trigger-content {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex: 1;
    }

    .premium-select-trigger-icon {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: #eff6ff;
        color: #2563eb;
        display: grid;
        place-items: center;
        font-size: 15px;
        flex-shrink: 0;
    }

    .premium-select-trigger-text {
        font-size: 12px;
        font-weight: 800;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .premium-select-chevron {
        color: #64748b;
        font-size: 18px;
        transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        flex-shrink: 0;
    }

    .premium-select-wrap.is-open .premium-select-chevron {
        transform: rotate(180deg);
        color: #2563eb;
    }

    .premium-select-menu {
        display: none;
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 12px;
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.14), 0 3px 10px rgba(15, 23, 42, 0.06);
        z-index: 1080;
        padding: 4px;
        max-height: 240px;
        overflow-y: auto;
        animation: selectMenuFadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1);
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    .premium-select-wrap.is-open .premium-select-menu {
        display: block;
    }

    @keyframes selectMenuFadeIn {
        from { opacity: 0; transform: translateY(-3px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .premium-select-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 6px 8px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.12s ease;
        border: 1px solid transparent;
        margin-bottom: 2px;
    }

    .premium-select-item:last-child {
        margin-bottom: 0;
    }

    .premium-select-item:hover {
        background: #f8fafc;
        border-color: #e2e8f0;
    }

    .premium-select-item.is-selected {
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    .premium-select-item-lead {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .premium-select-item-icon {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: #f1f5f9;
        color: #475569;
        display: grid;
        place-items: center;
        font-size: 15px;
        flex-shrink: 0;
        transition: all 0.15s ease;
    }

    .premium-select-item.is-selected .premium-select-item-icon {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .premium-select-item-info strong {
        display: block;
        font-size: 12px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }

    .premium-select-item.is-selected .premium-select-item-info strong {
        color: #1d4ed8;
    }

    .premium-select-item-info span {
        display: block;
        font-size: 10.5px;
        color: #64748b;
        margin-top: 1px;
        line-height: 1.15;
    }

    .premium-select-item-check {
        color: #2563eb;
        font-size: 15px;
        display: none;
        flex-shrink: 0;
    }

    .premium-select-item.is-selected .premium-select-item-check {
        display: block;
    }

    /* Compact Unit Picker for Table Rows */
    .row-unit-picker {
        position: relative;
        width: 100%;
        user-select: none;
    }

    .row-unit-btn {
        width: 100%;
        min-height: 38px;
        padding: 5px 8px;
        background: #f8fafc;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        cursor: pointer;
        font-weight: 800;
        font-size: 12px;
        color: #0f172a;
        transition: all 0.15s ease;
    }

    .row-unit-btn:hover {
        background: #ffffff;
        border-color: #94a3b8;
    }

    .row-unit-picker.is-open .row-unit-btn {
        background: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .row-unit-menu {
        display: none;
        position: fixed;
        min-width: 150px;
        max-width: 190px;
        max-height: 220px;
        overflow-y: auto;
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.18);
        z-index: 99999;
        padding: 3px;
        scrollbar-width: thin;
        animation: selectMenuFadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .row-unit-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 7px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 750;
        color: #334155;
        cursor: pointer;
        transition: all 0.12s ease;
        margin-bottom: 2px;
    }

    .row-unit-item:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .row-unit-item.is-selected {
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 850;
    }

    .row-unit-item-check {
        font-size: 16px;
        color: #2563eb;
        display: none;
    }

    .row-unit-item.is-selected .row-unit-item-check {
        display: block;
    }

    /* Settlement Action Buttons & Status Badge (Equal Sizing & Icons) */
    .settlement-actions-group {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .settlement-pill-btn {
        height: 36px;
        min-height: 36px;
        padding: 0 14px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 850;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        border: 1.5px solid transparent;
        user-select: none;
        line-height: 1;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
    }

    .settlement-pill-btn .material-symbols-rounded {
        font-size: 17px;
        line-height: 1;
    }

    .settlement-pill-btn.pay-full {
        background: #ecfdf5;
        color: #059669;
        border-color: #a7f3d0;
    }
    .settlement-pill-btn.pay-full:hover {
        background: #059669;
        color: #ffffff;
        border-color: #059669;
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        transform: translateY(-1px);
    }

    .settlement-pill-btn.udhar-full {
        background: #fef2f2;
        color: #dc2626;
        border-color: #fecaca;
    }
    .settlement-pill-btn.udhar-full:hover {
        background: #dc2626;
        color: #ffffff;
        border-color: #dc2626;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
        transform: translateY(-1px);
    }

    .settlement-status-badge {
        height: 36px;
        min-height: 36px;
        padding: 0 14px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 850;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1.5px solid #a7f3d0;
        background: #ecfdf5;
        color: #059669;
        line-height: 1;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
        transition: all 0.2s ease;
    }

    .settlement-status-badge .material-symbols-rounded {
        font-size: 17px;
        line-height: 1;
    }

    /* Floating Item Dropdown Menu & 2-Column Catalog Cards */
    .row-dropdown-wrap {
        position: relative;
        width: 100%;
    }

    .row-dropdown-menu {
        display: none;
        position: fixed;
        width: 350px;
        min-width: 300px;
        max-width: 380px;
        max-height: 220px;
        overflow-y: auto;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        box-shadow: 0 16px 38px rgba(15, 23, 42, 0.18), 0 3px 10px rgba(15, 23, 42, 0.06);
        z-index: 99999;
        padding: 3px;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        animation: selectMenuFadeIn 0.14s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .catalog-select-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        padding: 4px 6px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.12s ease;
        border: 1px solid transparent;
        margin-bottom: 1px;
    }

    .catalog-select-item:hover {
        background: #f0f7ff;
        border-color: #bfdbfe;
    }

    .catalog-select-item-lead {
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
        flex: 1;
    }

    .catalog-select-item-icon {
        width: 22px;
        height: 22px;
        border-radius: 5px;
        background: #eff6ff;
        color: #2563eb;
        display: grid;
        place-items: center;
        font-size: 13px;
        flex-shrink: 0;
    }

    .catalog-select-item-info {
        min-width: 0;
        flex: 1;
    }

    .catalog-select-item-info strong {
        display: block;
        font-size: 11.5px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .catalog-select-item-meta {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 1px;
        font-size: 9.5px;
        color: #64748b;
        line-height: 1;
    }

    .catalog-select-item-meta .meta-tag {
        display: none;
    }

    .catalog-select-item-prices {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 2px 6px;
        text-align: right;
        flex-shrink: 0;
        min-width: 82px;
    }

    .catalog-select-item-prices .cost-tag {
        font-size: 9.5px;
        font-weight: 800;
        color: #1e40af;
        display: block;
        line-height: 1.15;
    }

    .catalog-select-item-prices .price-tag {
        font-size: 9px;
        font-weight: 750;
        color: #059669;
        display: block;
        margin-top: 1px;
        line-height: 1.15;
    }

    /* Smooth Horizontally Scrollable Tables */
    .table-scroll-wrap,
    .table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f8fafc;
    }

    .table-scroll-wrap::-webkit-scrollbar,
    .table-wrap::-webkit-scrollbar {
        height: 7px;
    }

    .table-scroll-wrap::-webkit-scrollbar-track,
    .table-wrap::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 999px;
    }

    .table-scroll-wrap::-webkit-scrollbar-thumb,
    .table-wrap::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
        transition: background 0.2s ease;
    }

    .table-scroll-wrap::-webkit-scrollbar-thumb:hover,
    .table-wrap::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .purchase-summary-card {
        border: 1px solid var(--border);
        border-radius: 18px;
        background: linear-gradient(160deg, #0f172a, #172554);
        color: #fff;
        padding: 22px;
        box-shadow: 0 18px 42px rgba(15,23,42,.16);
        position: sticky;
        top: calc(var(--header-height) + 18px);
    }

    .purchase-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255,255,255,.12);
        color: rgba(255,255,255,.75);
        font-size: 13px;
        font-weight: 700;
    }

    .purchase-summary-row strong {
        color: #fff;
        text-align: right;
    }

    /* =========================================
       PURCHASE HISTORY TABLE (PRO-LEVEL & COMPACT)
       ========================================= */
    .purchase-history-card {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .purchase-list-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid #eef2f6;
        background: linear-gradient(180deg, #ffffff, #fafcff);
    }

    .purchase-table-pro {
        width: 100%;
        min-width: 860px;
        border-collapse: collapse;
    }

    .purchase-table-pro thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: 11px;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        padding: 11px 16px;
        border-bottom: 1.5px solid #e2e8f0;
        white-space: nowrap;
        text-align: left;
    }

    .purchase-table-pro tbody tr {
        transition: background 0.15s ease;
        border-bottom: 1px solid #f1f5f9;
    }

    .purchase-table-pro tbody tr:last-child {
        border-bottom: none;
    }

    .purchase-table-pro tbody tr:hover {
        background: #f8fbff;
    }

    .purchase-table-pro tbody td {
        padding: 11px 16px;
        vertical-align: middle;
        font-size: 13px;
    }

    /* Date Cell */
    .ph-date-cell {
        display: flex;
        flex-direction: column;
        gap: 2px;
        white-space: nowrap;
    }

    .ph-date-day {
        font-size: 12.5px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }

    .ph-date-time {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        line-height: 1.2;
    }

    /* Supplier Cell */
    .ph-supplier-cell {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        white-space: nowrap;
    }

    .ph-supplier-icon {
        font-size: 16px;
        color: #2563eb;
        background: #eff6ff;
        border-radius: 6px;
        padding: 3px;
        flex-shrink: 0;
    }

    .ph-supplier-name {
        font-size: 13px;
        font-weight: 800;
        color: #0f172a;
    }

    /* Item & Cost Cell (Inline & Compact) */
    .ph-item-cell {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 260px;
    }

    .ph-item-header {
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .ph-item-name {
        font-size: 13px;
        font-weight: 850;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 280px;
    }

    .ph-multi-badge {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        font-size: 10px;
        font-weight: 850;
        padding: 1px 6px;
        border-radius: 999px;
        white-space: nowrap;
        line-height: 1.2;
    }

    .ph-ref-badge {
        background: #f1f5f9;
        color: #475569;
        font-size: 10px;
        font-weight: 750;
        padding: 1px 5px;
        border-radius: 4px;
        white-space: nowrap;
        line-height: 1.2;
    }

    .ph-item-meta-inline {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        font-size: 11.5px;
        color: #475569;
        line-height: 1.2;
    }

    .ph-meta-item {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        white-space: nowrap;
    }

    .ph-meta-item .ph-lbl {
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
    }

    .ph-meta-item strong {
        color: #1e293b;
        font-weight: 800;
    }

    .ph-bullet {
        color: #cbd5e1;
        font-size: 10px;
        user-select: none;
    }

    .ph-trend-badge {
        display: inline-flex;
        align-items: center;
        padding: 1px 5px;
        border-radius: 4px;
        font-size: 9.5px;
        font-weight: 850;
        white-space: nowrap;
        line-height: 1.2;
    }

    .ph-trend-badge.up {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .ph-trend-badge.down {
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }

    /* Bill / Balance Cell */
    .ph-bill-cell {
        display: flex;
        flex-direction: column;
        gap: 3px;
        white-space: nowrap;
    }

    .ph-bill-total {
        font-size: 13.5px;
        font-weight: 900;
        color: #0f172a;
        line-height: 1.2;
    }

    .ph-balance-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
        font-weight: 850;
        padding: 2px 7px;
        border-radius: 999px;
        width: fit-content;
        line-height: 1;
    }

    .ph-balance-pill .material-symbols-rounded {
        font-size: 12px;
        line-height: 1;
    }

    .ph-balance-pill.udhar {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .ph-balance-pill.paid {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }

    /* Action Buttons */
    .ph-actions-cell {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
    }

    .ph-action-btn {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #475569;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
    }

    .ph-action-btn .material-symbols-rounded {
        font-size: 16px;
    }

    .ph-action-btn.view:hover {
        background: #eff6ff;
        color: #2563eb;
        border-color: #bfdbfe;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.12);
    }

    .ph-action-btn.edit:hover {
        background: #f0fdf4;
        color: #16a34a;
        border-color: #bbf7d0;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(22, 163, 74, 0.12);
    }

    .ph-action-btn.delete:hover {
        background: #fef2f2;
        color: #dc2626;
        border-color: #fecaca;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.14);
    }

    /* Custom Compact Confirmation Modal */
    .compact-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 10050;
        display: none;
        place-items: center;
        padding: 16px;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        animation: modalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .compact-modal-backdrop.is-open {
        display: grid;
    }

    .compact-modal-card {
        width: min(92vw, 420px);
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24), 0 4px 16px rgba(15, 23, 42, 0.08);
        padding: 22px;
        overflow: hidden;
        animation: modalPopIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        text-align: left;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes modalPopIn {
        from { opacity: 0; transform: scale(0.94) translateY(6px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .compact-modal-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }

    .compact-modal-icon-badge {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fee2e2;
        flex-shrink: 0;
    }

    .compact-modal-icon-badge .material-symbols-rounded {
        font-size: 22px;
    }

    .compact-modal-title {
        font-size: 16px;
        font-weight: 900;
        color: #0f172a;
        margin: 0;
        line-height: 1.25;
    }

    .compact-modal-sub {
        font-size: 11.5px;
        color: #64748b;
        margin: 2px 0 0;
    }

    .compact-modal-body {
        margin-bottom: 18px;
    }

    .compact-modal-desc {
        font-size: 13px;
        color: #334155;
        line-height: 1.5;
        margin: 0 0 12px;
    }

    .compact-modal-info-pill {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .compact-modal-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
    }

    .compact-modal-info-row span {
        color: #64748b;
        font-weight: 600;
    }

    .compact-modal-info-row strong {
        color: #0f172a;
        font-weight: 800;
    }

    .compact-modal-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    .compact-modal-btn {
        height: 38px;
        padding: 0 16px;
        border-radius: 10px;
        font-size: 12.5px;
        font-weight: 850;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1.5px solid transparent;
        user-select: none;
    }

    .compact-modal-btn.cancel {
        background: #f1f5f9;
        color: #475569;
        border-color: #e2e8f0;
    }

    .compact-modal-btn.cancel:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .compact-modal-btn.danger {
        background: #dc2626;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
    }

    .compact-modal-btn.danger:hover {
        background: #b91c1c;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(220, 38, 38, 0.35);
    }

    .inventory-product-cell {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 220px;
    }

    .inventory-product-thumb {
        width: 48px;
        height: 48px;
        flex: 0 0 48px;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, .32);
        background: linear-gradient(135deg, #ffffff, #eef6ff);
        display: grid;
        place-items: center;
        color: var(--primary);
        box-shadow: 0 10px 20px rgba(15,23,42,.08);
    }

    .inventory-product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }

    .inventory-product-thumb .material-symbols-rounded {
        font-size: 24px;
    }

    .inventory-product-info {
        min-width: 0;
    }

    .inventory-product-info strong {
        display: block;
        color: #0f172a;
        line-height: 1.25;
    }

    .inventory-product-unit {
        margin-top: 4px;
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 700;
    }

    @media (max-width: 860px) {
        .csv-import-shell {
            grid-template-columns: 1fr;
        }

        .purchase-ledger-shell,
        .purchase-form-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 860px) {
        .add-product-shell {
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .add-product-main,
        .add-product-side {
            gap: 14px;
        }

        .add-product-panel {
            padding: 16px;
            border-radius: 18px;
        }

        .add-product-grid,
        .add-product-grid.three {
            grid-template-columns: 1fr;
        }

        .add-product-panel .scan-config-row,
        .add-product-panel .variant-config-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .add-product-actions {
            align-items: stretch;
        }

        .add-product-actions .btn {
            width: 100%;
            justify-content: center;
        }

        .add-product-card {
            padding: 18px;
        }

        .inventory-form-shell {
            grid-template-columns:1fr;
            gap: 14px;
        }

        .inventory-col {
            padding: 18px;
            border-radius: 16px;
        }

        .add-product-actions {
            align-items:stretch;
        }

        .add-product-actions .btn {
            width:100%;
            justify-content:center;
        }

        .inventory-head-actions {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .inventory-head-actions .btn {
            width: 100%;
            justify-content: center;
            white-space: normal;
            line-height: 1.2;
            min-height: 46px;
            padding: 10px 12px;
        }

        .inventory-head-actions .btn:last-child {
            grid-column: 1 / -1;
        }

        .inventory-filter-actions {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .inventory-filter-actions .btn {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 640px) {
        .inventory-tabs {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .inventory-tab-btn {
            width: 100%;
            justify-content: center;
            min-height: 44px;
            white-space: normal;
        }

        .add-product-card {
            padding: 14px;
            border-radius: 16px;
        }

        .add-product-workspace {
            max-width: 100%;
        }

        .add-product-panel {
            padding: 14px;
            border-radius: 16px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
        }

        .add-product-panel-head {
            gap: 10px;
            margin-bottom: 14px;
        }

        .add-product-panel-head h3 {
            font-size: 15px;
        }

        .add-product-panel-icon {
            width: 38px;
            height: 38px;
            border-radius: 13px;
        }

        .add-product-panel .scan-config-row,
        .add-product-panel .variant-config-row {
            grid-template-columns: 1fr;
            padding: 12px;
        }

        .add-product-panel .product-image-dropzone {
            min-height: 220px;
        }

        .add-product-actions {
            position: static;
            padding: 0;
            border: 0;
            box-shadow: none;
            background: transparent;
            backdrop-filter: none;
        }

        .inventory-col {
            padding: 14px;
            box-shadow: none;
        }

        .inventory-col-title {
            font-size: 14px;
            margin-bottom: 14px;
        }

        .unit-selector-grid,
        .split-grid {
            grid-template-columns: 1fr !important;
        }

        .barcode-input-wrap {
            display: block;
        }

        .barcode-scan-btn {
            width: 32px;
            height: 32px;
        }

        .barcode-camera-modal {
            padding: 10px;
            align-items: end;
        }

        .barcode-camera-card {
            width: 100%;
            border-radius: 20px;
        }

        .barcode-camera-stage {
            min-height: 340px;
            aspect-ratio: 3 / 4;
        }

        .inventory-head-actions {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .inventory-head-actions .btn {
            justify-content: flex-start;
            padding: 12px 14px;
            min-height: 50px;
        }

        .inventory-head-actions .btn:last-child {
            grid-column: auto;
        }
    }
</style>

<div id="inventory-index-page">
<div class="page-header">
    <div>
        <h1>Inventory</h1>
        <p class="page-subtitle">Manage products, stock alerts, and bulk operations.</p>
    </div>
    <div class="inventory-head-actions">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/expiryAlerts" class="btn btn-secondary compact-single-action" style="font-weight: 700; color: #dc2626;"><span class="material-symbols-rounded">alarm_off</span>Expiry Alerts</a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/export" class="btn btn-secondary compact-single-action"><span class="material-symbols-rounded">download</span>Export CSV</a>
    </div>
</div>

<div class="inventory-tabs" role="tablist" aria-label="Inventory Tabs">
    <button type="button" class="inventory-tab-btn active" data-inventory-tab="add">
        <span class="material-symbols-rounded tab-icon">add_box</span>
        <span>Add Product</span>
    </button>
    <button type="button" class="inventory-tab-btn" data-inventory-tab="list">
        <span class="material-symbols-rounded tab-icon">inventory_2</span>
        <span>Inventory List</span>
        <span class="tab-count-badge"><?php echo count($products ?? []); ?></span>
    </button>
    <button type="button" class="inventory-tab-btn" data-inventory-tab="purchase_add">
        <span class="material-symbols-rounded tab-icon">post_add</span>
        <span>Add Purchase</span>
    </button>
    <button type="button" class="inventory-tab-btn" data-inventory-tab="purchase_list">
        <span class="material-symbols-rounded tab-icon">receipt_long</span>
        <span>Purchase History</span>
        <span class="tab-count-badge"><?php echo count($purchases ?? []); ?></span>
    </button>
    <button type="button" class="inventory-tab-btn" data-inventory-tab="import">
        <span class="material-symbols-rounded tab-icon">upload_file</span>
        <span>Import CSV</span>
    </button>
</div>

<section class="inventory-tab-pane active" data-inventory-pane="add">
    <div class="add-product-workspace">
        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/create" method="POST" enctype="multipart/form-data" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <div class="add-product-shell">
                <div class="add-product-main">
                    <section class="add-product-panel">
                        <div class="add-product-panel-head">
                            <span class="add-product-panel-icon"><span class="material-symbols-rounded">inventory_2</span></span>
                            <h3>Product Details</h3>
                        </div>
                        <div class="add-product-grid">
                            <div class="form-group wide">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:7px; flex-wrap:wrap; gap:6px;">
                                    <label class="form-label" style="margin:0;">Barcode / SKU (Scan or Generate)</label>
                                    <div style="display:flex; gap:6px;">
                                        <button type="button" class="btn btn-secondary" onclick="generateAddBarcode()" style="font-size:11px; padding:4px 9px; font-weight:800; color:#1d4ed8; border-color:#bfdbfe; line-height:1.2;">
                                            <span class="material-symbols-rounded" style="font-size:14px;">bolt</span> Auto-Generate
                                        </button>
                                    </div>
                                </div>
                                <div class="field-icon-wrap">
                                    <span class="material-symbols-rounded">qr_code_scanner</span>
                                    <input class="form-input" type="text" id="add_barcode_input" name="barcode" placeholder="Scan item with hardware scanner gun or click Auto-Generate..." autocomplete="off" oninput="onAddBarcodeInput(this.value)">
                                </div>
                                <div id="add_barcode_live_container" class="barcode-preview-live" style="margin-top:8px;">
                                    <svg id="add_barcode_live_svg"></svg>
                                    <div id="add_barcode_check_status"></div>
                                </div>
                            </div>
                            <div class="form-group wide">
                                <label class="form-label">Product Name</label>
                                <div class="field-icon-wrap">
                                    <span class="material-symbols-rounded">label</span>
                                    <input class="form-input" type="text" id="add_product_name_input" name="name" required placeholder="Enter product name">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Model / SKU</label>
                                <div class="field-icon-wrap">
                                    <span class="material-symbols-rounded">tag</span>
                                    <input class="form-input" type="text" name="model_number" placeholder="Enter model/SKU">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Unit Value</label>
                                <div class="field-icon-wrap">
                                    <span class="material-symbols-rounded">scale</span>
                                    <input class="form-input" type="number" name="unit_value" step="0.001" min="0.001" value="1" placeholder="250">
                                </div>
                            </div>
                            <div class="form-group wide">
                                <label class="form-label">Unit Type</label>
                                <select class="form-select" name="unit_type" data-unit-picker>
                                    <?php foreach (App::allowedProductUnits() as $unit): ?>
                                        <option value="<?php echo Security::escape($unit); ?>"><?php echo Security::escape(App::productUnitLabel($unit)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                    </section>

                    <section class="add-product-panel" id="add-product-expiry-panel">
                        <div class="add-product-panel-head" style="justify-content:space-between;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <span class="add-product-panel-icon amber"><span class="material-symbols-rounded">alarm_off</span></span>
                                <div>
                                    <h3 style="margin:0;">Batch & Expiry (Perishable Goods)</h3>
                                    <div style="font-size:11.5px; color:#64748b; margin-top:2px;">Track batch code & expiration dates</div>
                                </div>
                            </div>
                            <label class="box-auto-toggle" title="Enable or Disable Expiry Tracking for this product" style="margin:0;">
                                <input type="checkbox" id="toggle_product_expiry" onchange="toggleAddExpiryFields(this.checked)">
                                <span class="box-auto-switch"></span>
                                <span id="toggle_expiry_status_text" style="font-size:12px; font-weight:800; color:#64748b;">Disabled</span>
                            </label>
                        </div>
                        <div class="add-product-grid" id="add_expiry_fields_container" style="display:none; transition: all 0.2s ease;">
                            <div class="form-group">
                                <label class="form-label">Batch Number <span style="font-size:11px; color:#64748b; font-weight:normal;">(Optional)</span></label>
                                <div class="field-icon-wrap">
                                    <span class="material-symbols-rounded">qr_code</span>
                                    <input class="form-input" type="text" id="add_batch_number" name="batch_number" placeholder="e.g. BATCH-2026-A">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Expiry Date <span style="font-size:11px; color:#64748b; font-weight:normal;">(Optional)</span></label>
                                <div class="field-icon-wrap">
                                    <span class="material-symbols-rounded">event</span>
                                    <input class="form-input" type="date" id="add_expiry_date" name="expiry_date" placeholder="YYYY-MM-DD">
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="add-product-side">
                    <section class="add-product-panel">
                        <div class="add-product-panel-head">
                            <span class="add-product-panel-icon green"><span class="material-symbols-rounded">payments</span></span>
                            <h3>Pricing & Stock</h3>
                        </div>
                        <div class="add-product-grid">
                            <div class="form-group">
                                <label class="form-label">Retail Price</label>
                                <div class="field-icon-wrap">
                                    <span class="material-symbols-rounded">sell</span>
                                    <input class="form-input" type="number" name="price" step="0.01" required placeholder="0.00">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Wholesale <span style="font-size:11px; color:#64748b; font-weight:normal;">(Optional)</span></label>
                                <div class="field-icon-wrap">
                                    <span class="material-symbols-rounded">storefront</span>
                                    <input class="form-input" type="number" name="wholesale_price" step="0.01" placeholder="Optional (Defaults to Retail)">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Cost Price</label>
                                <div class="field-icon-wrap">
                                    <span class="material-symbols-rounded">inventory</span>
                                    <input class="form-input" type="number" name="cost" step="0.01" required placeholder="0.00">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Initial Stock</label>
                                <div class="field-icon-wrap">
                                    <span class="material-symbols-rounded">layers</span>
                                    <input class="form-input" type="number" step="any" min="0" name="stock" value="0" placeholder="Qty">
                                </div>
                            </div>
                            <div class="form-group wide">
                                <label class="form-label">Min Stock Alert</label>
                                <div class="field-icon-wrap">
                                    <span class="material-symbols-rounded">warning</span>
                                    <input class="form-input" type="number" step="any" min="0" name="min_stock" value="5" placeholder="Qty">
                                </div>
                            </div>
                            <div class="form-group wide" style="margin-top: 10px;">
                                <label class="box-auto-toggle" title="Show this product on your customer online store">
                                    <input type="checkbox" name="is_visible_online" value="1" checked>
                                    <span class="box-auto-switch"></span>
                                    <span style="font-size:13px; font-weight:850; color:#0f172a;">Show on Online Store</span>
                                </label>
                            </div>
                        </div>
                    </section>

                    <section class="add-product-panel">
                        <div class="add-product-panel-head">
                            <span class="add-product-panel-icon"><span class="material-symbols-rounded">image</span></span>
                            <h3>Product Image</h3>
                        </div>
                        <div class="product-image-dropzone" data-product-image-dropzone>
                            <input type="file" name="product_image" accept="image/png,image/jpeg,image/webp" data-product-image-input>
                            <div class="product-image-preview" data-product-image-preview>
                                <div class="product-image-preview-box"><span class="material-symbols-rounded">add_photo_alternate</span></div>
                                <strong>Product image</strong>
                                <span class="form-hint">PNG, JPG or WEBP</span>
                                <div class="product-image-actions">
                                    <button class="product-image-action" type="button" data-product-image-browse><span class="material-symbols-rounded">upload</span>Upload</button>
                                    <button class="product-image-action" type="button" data-product-image-camera><span class="material-symbols-rounded">photo_camera</span>Camera</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            <div class="add-product-actions">
                <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>Save Product</button>
            </div>
        </form>
    </div>
</section>

<div class="product-camera-modal" data-product-camera-modal aria-hidden="true">
    <div class="product-camera-card">
        <div class="product-camera-head">
            <div>
                <strong>Product Camera</strong>
                <span>Center object inside the square frame.</span>
            </div>
            <button class="product-camera-close" type="button" data-product-camera-close aria-label="Close camera">&times;</button>
        </div>
        <div class="product-camera-stage">
            <video data-product-camera-video autoplay muted playsinline></video>
            <div class="object-tracker-frame"></div>
        </div>
        <div class="product-camera-actions">
            <button class="btn btn-secondary" type="button" data-product-camera-close>Cancel</button>
            <button class="btn btn-primary" type="button" data-product-camera-capture><span class="material-symbols-rounded">photo_camera</span>Capture Image</button>
        </div>
    </div>
</div>

<section class="inventory-tab-pane" data-inventory-pane="list">
    <form id="inventory-filter-form" method="GET" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/" class="card" style="margin-bottom:18px;">
        <input type="hidden" name="url" value="inventory/index">
        <div class="filter-grid" style="grid-template-columns:2fr 1fr auto;">
            <div class="field-icon-wrap">
                <span class="material-symbols-rounded">search</span>
                <input class="form-input" type="text" name="q" value="<?php echo Security::escape($query ?? ''); ?>" placeholder="Search by product or model" data-live-search>
            </div>
            <select class="form-select" name="filter" data-live-search>
                <option value="">All Products</option>
                <option value="low" <?php echo ($filter ?? '') === 'low' ? 'selected' : ''; ?>>Low Stock Only</option>
            </select>
            <div class="inventory-filter-actions">
                <button class="btn btn-secondary" type="submit">Apply</button>
                <button class="btn btn-ghost" type="button" data-clear-filters>Reset</button>
            </div>
        </div>
    </form>

    <div class="card table-wrap table-scroll-wrap" id="inventory-results-card">
        <table style="min-width:820px;">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Model</th>
                    <th>Price</th>
                    <th>Wholesale</th>
                    <th>Stock</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="6" class="empty-state">No products found in inventory.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <?php $low = (float) $product['stock'] <= (float) $product['min_stock']; ?>
                        <?php 
                            $rawProductImage = trim((string) ($product['image_path'] ?? ''));
                            $hasProductImage = $rawProductImage !== '' && App::mediaExists($rawProductImage);
                            $productImageUrl = $hasProductImage ? App::mediaUrl($rawProductImage) : '';
                        ?>
                        <tr>
                            <td>
                                <a class="inventory-product-cell" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/viewProfile/<?php echo $product['id']; ?>">
                                    <span class="inventory-product-thumb">
                                        <?php if ($hasProductImage): ?>
                                            <img src="<?php echo Security::escape($productImageUrl); ?>" alt="" loading="lazy" onerror="this.onerror=null; this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='grid';">
                                            <span class="material-symbols-rounded" style="display:none;">inventory_2</span>
                                        <?php else: ?>
                                            <span class="material-symbols-rounded">inventory_2</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="inventory-product-info">
                                        <strong><?php echo Security::escape($product['name']); ?></strong>
                                        <span class="inventory-product-unit"><?php echo Security::escape(App::formatProductUnit($product['unit_value'] ?? 1, $product['unit_type'] ?? 'pcs')); ?></span>
                                    </span>
                                </a>
                            </td>
                            <td><?php echo Security::escape($product['model_number'] ?: 'N/A'); ?></td>
                            <td><?php echo App::money($product['price']); ?></td>
                            <td><?php echo App::money($product['wholesale_price'] ?? $product['price']); ?></td>
                            <td><span class="badge <?php echo $low ? 'badge-danger' : 'badge-success'; ?>"><?php echo Security::escape(App::formatQuantity($product['stock'])); ?> <?php echo Security::escape(strtoupper($product['unit_type'] ?? 'PCS')); ?></span></td>
                            <td>
                                <div class="table-actions">
                                    <a class="icon-btn" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/barcodePrint/<?php echo $product['id']; ?>" title="Print Barcode Labels" style="color: #1d4ed8;"><span class="material-symbols-rounded">print</span></a>
                                    <a class="icon-btn" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/viewProfile/<?php echo $product['id']; ?>" title="View Profile"><span class="material-symbols-rounded">visibility</span></a>
                                    <a class="icon-btn" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/edit/<?php echo $product['id']; ?>" title="Edit"><span class="material-symbols-rounded">edit</span></a>
                                    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/delete/<?php echo (int) $product['id']; ?>" method="POST" data-no-ajax style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this product (<?php echo Security::escape(addslashes($product['name'])); ?>)?');">
                                        <?php echo Security::csrfField(); ?>
                                        <button class="icon-btn icon-btn-danger" type="submit" title="Delete"><span class="material-symbols-rounded">delete</span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="inventory-tab-pane" data-inventory-pane="purchase_add">
    <div class="purchase-ledger-shell" style="grid-template-columns: minmax(0, 1fr) 330px;">
        <form class="purchase-form-card" id="purchaseAddForm" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchaseCreate" method="POST" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <div class="purchase-form-head">
                <div>
                    <h3 style="margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                        <span>Create Multi-Item Purchase Bill</span>
                        <span class="badge" style="background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:800; border:1px solid #bfdbfe;">Dynamic Costing & Udhar</span>
                    </h3>
                    <p class="page-subtitle" style="margin:0;">Add multiple items in one inward bill, auto-link or create products, record payments, and track supplier Udhar.</p>
                </div>
                <div class="purchase-form-icon"><span class="material-symbols-rounded">receipt_long</span></div>
            </div>

            <!-- Top Row: Supplier, Date, Bill Ref, Cost Method -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; margin-bottom:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:18px; padding:16px;">
                <div class="form-group" style="position:relative; margin:0;">
                    <label class="form-label" style="font-weight:850; font-size:12.5px;">Supplier / Vendor *</label>
                    <div class="searchable-dropdown-wrap" id="supplier_dropdown_wrap" style="position:relative;">
                        <div class="field-icon-wrap" style="position:relative;">
                            <span class="material-symbols-rounded" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#64748b; font-size:20px; z-index:2; pointer-events:none;">store</span>
                            <input class="form-input" type="text" name="supplier_name" id="purchase_supplier_input" required placeholder="Search or type supplier..." autocomplete="off" style="padding-left:44px; padding-right:40px; font-weight:800;">
                            <span class="material-symbols-rounded" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:20px; pointer-events:none;">expand_more</span>
                        </div>
                        <div id="supplier_search_dropdown_menu" class="searchable-dropdown-menu" style="display:none; position:absolute; top:calc(100% + 4px); left:0; right:0; max-height:240px; overflow-y:auto; background:#ffffff; border:1px solid #cbd5e1; border-radius:14px; box-shadow:0 16px 36px rgba(15,23,42,.14); z-index:1050; padding:6px;">
                            <div id="supplier_dropdown_items_list"></div>
                        </div>
                    </div>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-weight:850; font-size:12.5px;">Purchase Date *</label>
                    <input class="form-input" type="date" name="purchase_date" value="<?php echo date('Y-m-d'); ?>" required style="font-weight:700;">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-weight:850; font-size:12.5px;">Bill / Invoice Ref #</label>
                    <input class="form-input" type="text" name="bill_reference" placeholder="e.g. INV-8920, Challan #" style="font-weight:700;">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-weight:850; font-size:12.5px;">Cost Calculation Method</label>
                    <div class="premium-select-wrap" id="cost_method_select_wrap">
                        <input type="hidden" name="cost_method" id="global_cost_method" value="weighted_average">
                        <div class="premium-select-trigger" tabindex="0">
                            <div class="premium-select-trigger-content">
                                <span class="premium-select-trigger-icon material-symbols-rounded" id="cost_method_trigger_icon">calculate</span>
                                <span class="premium-select-trigger-text" id="cost_method_trigger_text">Weighted Average (WAC)</span>
                            </div>
                            <span class="material-symbols-rounded premium-select-chevron">expand_more</span>
                        </div>
                        <div class="premium-select-menu">
                            <div class="premium-select-item is-selected" data-value="weighted_average" data-icon="calculate" data-title="Weighted Average (WAC)" data-desc="Blends old stock & new purchase costs">
                                <div class="premium-select-item-lead">
                                    <span class="premium-select-item-icon material-symbols-rounded">calculate</span>
                                    <div class="premium-select-item-info">
                                        <strong>Weighted Average (WAC)</strong>
                                        <span>Blends old stock & new purchase costs</span>
                                    </div>
                                </div>
                                <span class="material-symbols-rounded premium-select-item-check">check_circle</span>
                            </div>
                            <div class="premium-select-item" data-value="latest_price" data-icon="price_change" data-title="Latest Price (LPP)" data-desc="Directly sets cost to new purchase price">
                                <div class="premium-select-item-lead">
                                    <span class="premium-select-item-icon material-symbols-rounded">price_change</span>
                                    <div class="premium-select-item-info">
                                        <strong>Latest Price (LPP)</strong>
                                        <span>Directly sets cost to new purchase price</span>
                                    </div>
                                </div>
                                <span class="material-symbols-rounded premium-select-item-check">check_circle</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Items Table Section -->
            <div style="margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                    <div>
                        <h4 style="margin:0; font-size:15px; font-weight:900; color:#0f172a; display:flex; align-items:center; gap:6px;">
                            <span class="material-symbols-rounded" style="color:#2563eb; font-size:20px;">shopping_bag</span>
                            Purchased Items List
                        </h4>
                        <span style="font-size:11.5px; color:#64748b;">Search products or type new item. Auto-adds to catalog and updates stock.</span>
                    </div>
                    <button type="button" class="btn btn-secondary" id="add_purchase_row_btn" style="font-size:12.5px; font-weight:850; padding:7px 14px; display:inline-flex; align-items:center; gap:6px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;">
                        <span class="material-symbols-rounded" style="font-size:18px;">add_circle</span>
                        + Add Another Item
                    </button>
                </div>

                <div class="table-wrap table-scroll-wrap" style="box-shadow:none; border:1px solid #cbd5e1; border-radius:16px; overflow-x:auto; background:#fff; min-height:180px;">
                    <table class="data-table" id="purchase_items_table" style="margin:0; width:100%; min-width:880px;">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th style="width:36px; text-align:center; padding:10px 6px;">#</th>
                                <th style="min-width:300px; padding:10px 10px;">Item / Product Name *</th>
                                <th style="width:130px; padding:10px 8px;">Unit</th>
                                <th style="width:105px; text-align:right; padding:10px 8px;">Qty *</th>
                                <th style="width:115px; text-align:right; padding:10px 8px;">Unit Cost *</th>
                                <th style="width:115px; text-align:right; padding:10px 8px;">Retail Price</th>
                                <th style="width:115px; text-align:right; padding:10px 10px;">Subtotal</th>
                                <th style="width:44px; text-align:center; padding:10px 6px;"></th>
                            </tr>
                        </thead>
                        <tbody id="purchase_items_tbody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                        <tfoot>
                            <tr style="background:#f8fafc; font-weight:900; border-top:2px solid #cbd5e1;">
                                <td colspan="3" style="text-align:right; font-size:13px; color:#475569; padding:10px 12px;">Grand Totals:</td>
                                <td style="text-align:right; font-size:13.5px; color:#0f172a; padding:10px 8px;" id="table_total_qty">0</td>
                                <td colspan="2"></td>
                                <td style="text-align:right; font-size:15px; color:#1d4ed8; font-weight:900; padding:10px 10px;" id="table_grand_total">Rs 0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div style="margin-top:8px; display:flex; justify-content:space-between; align-items:center;">
                    <button type="button" class="btn btn-secondary" id="add_purchase_row_btn_bottom" style="font-size:12.5px; font-weight:850; padding:6px 12px; display:inline-flex; align-items:center; gap:6px;">
                        <span class="material-symbols-rounded" style="font-size:17px;">add</span> Add Item Row
                    </button>
                    <span style="font-size:11px; color:#64748b;">Press <strong>Tab</strong> to move between fields. Subtotals auto-calculate in real time.</span>
                </div>
            </div>

            <!-- Payment & Udhar (Credit) Settlement Card -->
            <div style="background:linear-gradient(145deg, #ffffff, #f8fafc); border:1.5px solid #e2e8f0; border-radius:18px; padding:18px; margin-bottom:18px; box-shadow:0 8px 24px rgba(15,23,42,.04);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="material-symbols-rounded" style="color:#059669; font-size:22px;">payments</span>
                        <strong style="font-size:14px; color:#0f172a;">Payment & Udhar (Credit) Settlement</strong>
                    </div>
                    <div class="settlement-actions-group">
                        <button type="button" id="btn_quick_full_pay" class="settlement-pill-btn pay-full">
                            <span class="material-symbols-rounded">check_circle</span>
                            <span>Full Paid</span>
                        </button>
                        <button type="button" id="btn_quick_full_udhar" class="settlement-pill-btn udhar-full">
                            <span class="material-symbols-rounded">account_balance_wallet</span>
                            <span>Full Udhar</span>
                        </button>
                        <div id="udhar_status_pill" class="settlement-status-badge">
                            <span class="material-symbols-rounded">verified</span>
                            <span class="pill-text">Fully Paid</span>
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-bottom:14px;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-weight:850; font-size:12.5px;">Gross Bill Total</label>
                        <input class="form-input" type="number" step="0.01" min="0" name="bill_total" id="purchase_bill_total_input" readonly required style="background:#f1f5f9; font-weight:900; font-size:15px; color:#1e40af;">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-weight:850; font-size:12.5px;">Amount Paid Now</label>
                        <input class="form-input" type="number" step="0.01" min="0" name="paid_amount" id="purchase_paid_amount_input" placeholder="0.00" style="font-weight:900; font-size:15px; color:#059669; border-color:#a7f3d0;">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-weight:850; font-size:12.5px;">Remaining Udhar (Payable)</label>
                        <input class="form-input" type="text" id="purchase_balance_display" readonly value="Rs 0.00" style="background:#f1f5f9; font-weight:900; font-size:15px; color:#dc2626;">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-weight:850; font-size:12.5px;">Payment Method</label>
                        <div class="premium-select-wrap" id="payment_method_select_wrap">
                            <input type="hidden" name="payment_method" id="purchase_payment_method" value="cash">
                            <div class="premium-select-trigger" tabindex="0">
                                <div class="premium-select-trigger-content">
                                    <span class="premium-select-trigger-icon material-symbols-rounded" id="payment_method_trigger_icon">payments</span>
                                    <span class="premium-select-trigger-text" id="payment_method_trigger_text">Cash</span>
                                </div>
                                <span class="material-symbols-rounded premium-select-chevron">expand_more</span>
                            </div>
                            <div class="premium-select-menu">
                                <div class="premium-select-item is-selected" data-value="cash" data-icon="payments" data-title="Cash" data-desc="Immediate physical cash payment">
                                    <div class="premium-select-item-lead">
                                        <span class="premium-select-item-icon material-symbols-rounded">payments</span>
                                        <div class="premium-select-item-info">
                                            <strong>Cash</strong>
                                            <span>Immediate physical cash payment</span>
                                        </div>
                                    </div>
                                    <span class="material-symbols-rounded premium-select-item-check">check_circle</span>
                                </div>
                                <div class="premium-select-item" data-value="online" data-icon="account_balance" data-title="Online Transfer" data-desc="Raast, wire or digital transfer">
                                    <div class="premium-select-item-lead">
                                        <span class="premium-select-item-icon material-symbols-rounded">account_balance</span>
                                        <div class="premium-select-item-info">
                                            <strong>Online Transfer</strong>
                                            <span>Raast, wire or digital transfer</span>
                                        </div>
                                    </div>
                                    <span class="material-symbols-rounded premium-select-item-check">check_circle</span>
                                </div>
                                <div class="premium-select-item" data-value="cheque" data-icon="receipt_long" data-title="Cheque" data-desc="Bank cheque voucher">
                                    <div class="premium-select-item-lead">
                                        <span class="premium-select-item-icon material-symbols-rounded">receipt_long</span>
                                        <div class="premium-select-item-info">
                                            <strong>Cheque</strong>
                                            <span>Bank cheque voucher</span>
                                        </div>
                                    </div>
                                    <span class="material-symbols-rounded premium-select-item-check">check_circle</span>
                                </div>
                                <div class="premium-select-item" data-value="other" data-icon="more_horiz" data-title="Other" data-desc="Custom payment mode">
                                    <div class="premium-select-item-lead">
                                        <span class="premium-select-item-icon material-symbols-rounded">more_horiz</span>
                                        <div class="premium-select-item-info">
                                            <strong>Other</strong>
                                            <span>Custom payment mode</span>
                                        </div>
                                    </div>
                                    <span class="material-symbols-rounded premium-select-item-check">check_circle</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Udhar Alert Banner -->
                <div id="udhar_alert_box" style="display:none; padding:12px 14px; border-radius:12px; background:#fffbeb; border:1px solid #fde68a; color:#92400e; font-size:12.5px; align-items:center; gap:10px;">
                    <span class="material-symbols-rounded" style="color:#d97706; font-size:22px; flex-shrink:0;">account_balance_wallet</span>
                    <div>
                        <strong id="udhar_alert_title">Partial Payment / Udhar Detected:</strong>
                        <div id="udhar_alert_desc" style="font-size:11.5px; color:#b45309; margin-top:2px;">
                            Remaining amount will automatically be added to the supplier's payable balance in the Khata / Ledger.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes & Submission -->
            <div class="form-group" style="margin-bottom:18px;">
                <label class="form-label" style="font-weight:850; font-size:12.5px;">Bill Notes / Private Memo (Optional)</label>
                <textarea class="form-textarea" name="notes" rows="2" placeholder="Delivery challan details, payment promise date, batch notes, or supplier remarks..."></textarea>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; padding-top:14px; border-top:1px solid #e2e8f0;">
                <div style="color:#64748b; font-size:12px;">
                    <span class="material-symbols-rounded" style="font-size:16px; vertical-align:middle; color:#059669;">check_circle</span>
                    Stock auto-increments &bull; Dynamic cost calculation &bull; Udhar ledger updated
                </div>
                <button class="btn btn-primary" type="submit" style="font-weight:900; font-size:14px; padding:12px 28px; box-shadow:0 12px 28px rgba(37,99,235,.25);">
                    <span class="material-symbols-rounded">save</span>
                    Save Purchase Bill & Update Stock
                </button>
            </div>
        </form>

        <aside class="purchase-summary-card">
            <h3 style="margin-bottom:8px;">Live Bill Summary</h3>
            <p style="color:rgba(255,255,255,.72); line-height:1.6; margin:0 0 14px;">Instant snapshot of this inward purchase bill and its impact on inventory & vendor payables.</p>
            
            <div class="purchase-summary-row"><span>Total Items</span><strong id="side_items_count">0 items</strong></div>
            <div class="purchase-summary-row"><span>Total Quantity</span><strong id="side_total_qty">0 units</strong></div>
            <div class="purchase-summary-row"><span>Gross Bill Total</span><strong id="side_bill_total" style="font-size:15px; color:#60a5fa;">Rs 0.00</strong></div>
            <div class="purchase-summary-row"><span>Paid Now</span><strong id="side_paid_amount" style="color:#4ade80;">Rs 0.00</strong></div>
            <div class="purchase-summary-row"><span>Remaining Udhar</span><strong id="side_balance_amount" style="color:#f87171;">Rs 0.00</strong></div>
            <div class="purchase-summary-row"><span>Costing Mode</span><strong id="side_cost_mode">Weighted Average</strong></div>
            <div class="purchase-summary-row"><span>Total Purchases Recorded</span><strong><?php echo number_format(count($purchases ?? [])); ?> bills</strong></div>
            <div class="purchase-summary-row"><span>Total Unpaid Udhar in System</span><strong><?php echo App::money(array_sum(array_map(fn($p) => (float) ($p['balance_amount'] ?? 0), $purchases ?? []))); ?></strong></div>
        </aside>
    </div>
</section>

<section class="inventory-tab-pane" data-inventory-pane="purchase_list">
    <div class="purchase-history-card">
        <div class="purchase-list-title">
            <div>
                <h3 style="margin:0 0 3px; font-size:16px; font-weight:900; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-rounded" style="color:#2563eb; font-size:20px;">history</span>
                    Purchase History
                </h3>
                <p class="page-subtitle" style="margin:0; font-size:12px;">All inventory purchases ordered by latest date with cost fluctuation analysis.</p>
            </div>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/index?tab=purchase_add" class="btn btn-primary" style="font-size:12px; font-weight:800; padding:6px 14px; display:inline-flex; align-items:center; gap:6px;">
                <span class="material-symbols-rounded" style="font-size:16px;">add</span> + New Purchase Bill
            </a>
        </div>

        <!-- Instant Live Search & Status Filter Bar -->
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin:14px 0 16px; flex-wrap:wrap;">
            <div style="position:relative; flex:1; min-width:240px; max-width:420px;">
                <span class="material-symbols-rounded" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:18px; pointer-events:none;">search</span>
                <input type="text" id="purchase_history_search" placeholder="Search by supplier, item, or bill #..." style="width:100%; height:36px; padding:0 12px 0 38px; border:1px solid #cbd5e1; border-radius:10px; font-size:12px; font-weight:700; background:#f8fafc; box-sizing:border-box;">
            </div>
            <div style="display:inline-flex; align-items:center; gap:4px; background:#f1f5f9; padding:3px; border-radius:10px;" id="purchase_filter_pills">
                <button type="button" class="pill-btn active" data-ph-filter="all" style="height:26px; padding:0 12px; border-radius:7px; border:none; font-size:11.5px; font-weight:800; cursor:pointer;">All Bills (<?php echo count($purchases ?? []); ?>)</button>
                <button type="button" class="pill-btn" data-ph-filter="paid" style="height:26px; padding:0 12px; border-radius:7px; border:none; font-size:11.5px; font-weight:800; cursor:pointer;">Full Paid</button>
                <button type="button" class="pill-btn" data-ph-filter="udhar" style="height:26px; padding:0 12px; border-radius:7px; border:none; font-size:11.5px; font-weight:800; cursor:pointer; color:#dc2626;">Remaining Udhar</button>
            </div>
        </div>

        <div class="table-wrap table-scroll-wrap" style="border:none; box-shadow:none; border-radius:0;">
            <table class="purchase-table-pro" id="purchase_history_table">
                <thead>
                    <tr>
                        <th style="width:140px;">Date & Time</th>
                        <th style="width:180px;">Supplier / Vendor</th>
                        <th style="min-width:320px;">Item & Cost Details</th>
                        <th style="width:150px;">Bill / Settlement</th>
                        <th style="width:90px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="purchase_history_tbody">
                    <?php if (empty($purchases ?? [])): ?>
                        <tr><td colspan="5" class="empty-state" style="text-align:center; padding:36px; color:#64748b;">No inventory purchases recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($purchases as $purchase): ?>
                            <?php 
                                $pUnitCost = (float)($purchase['unit_cost'] ?? 0);
                                $pOldCost = (float)($purchase['old_cost'] ?? 0);
                                $costDiff = $pUnitCost - $pOldCost;
                                $itemsCount = (int)($purchase['items_count'] ?? 1);
                                $hasUdhar = ((float)($purchase['balance_amount'] ?? 0) > 0) ? '1' : '0';
                            ?>
                            <tr class="purchase-history-row" 
                                data-supplier="<?php echo Security::escape(strtolower($purchase['supplier_name'] ?? '')); ?>"
                                data-item="<?php echo Security::escape(strtolower($purchase['item_name'] ?? '')); ?>"
                                data-ref="<?php echo Security::escape(strtolower($purchase['bill_reference'] ?? '')); ?>"
                                data-id="<?php echo (int)$purchase['id']; ?>"
                                data-has-udhar="<?php echo $hasUdhar; ?>">
                                <td>
                                    <div class="ph-date-cell">
                                        <span class="ph-date-day"><?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?></span>
                                        <span class="ph-date-time"><?php echo date('h:i A', strtotime($purchase['created_at'] ?? $purchase['purchase_date'])); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="ph-supplier-cell">
                                        <span class="ph-supplier-icon material-symbols-rounded">store</span>
                                        <span class="ph-supplier-name"><?php echo Security::escape($purchase['supplier_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="ph-item-cell">
                                        <div class="ph-item-header">
                                            <span class="ph-item-name" title="<?php echo Security::escape($purchase['item_name']); ?>">
                                                <?php echo Security::escape($purchase['item_name']); ?>
                                            </span>
                                            <?php if ($itemsCount > 1): ?>
                                                <span class="ph-multi-badge">+<?php echo ($itemsCount - 1); ?> items</span>
                                            <?php endif; ?>
                                            <?php if (!empty($purchase['bill_reference'])): ?>
                                                <span class="ph-ref-badge">#<?php echo Security::escape($purchase['bill_reference']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ph-item-meta-inline">
                                            <span class="ph-meta-item">
                                                <span class="ph-lbl">Qty:</span>
                                                <strong><?php echo Security::escape(App::formatProductUnit($purchase['quantity'], $purchase['unit_type'])); ?></strong>
                                            </span>
                                            <?php if ($pUnitCost > 0): ?>
                                                <span class="ph-bullet">&bull;</span>
                                                <span class="ph-meta-item">
                                                    <span class="ph-lbl">Cost:</span>
                                                    <strong><?php echo App::money($pUnitCost); ?></strong>
                                                </span>
                                                <?php if ($pOldCost > 0 && abs($costDiff) > 0.01): ?>
                                                    <span class="ph-bullet">&bull;</span>
                                                    <?php if ($costDiff > 0): ?>
                                                        <span class="ph-trend-badge up">▲ +<?php echo App::money($costDiff); ?></span>
                                                    <?php else: ?>
                                                        <span class="ph-trend-badge down">▼ -<?php echo App::money(abs($costDiff)); ?></span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="ph-bill-cell">
                                        <div class="ph-bill-total"><?php echo App::money($purchase['bill_total']); ?></div>
                                        <?php if ((float) $purchase['balance_amount'] > 0): ?>
                                            <span class="ph-balance-pill udhar">
                                                <span class="material-symbols-rounded">account_balance_wallet</span>
                                                Bal: <?php echo App::money($purchase['balance_amount']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="ph-balance-pill paid">
                                                <span class="material-symbols-rounded">verified</span>
                                                Full Paid
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align:right;">
                                    <div class="ph-actions-cell">
                                        <a class="ph-action-btn view" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchaseView/<?php echo (int) $purchase['id']; ?>" title="View Bill Details">
                                            <span class="material-symbols-rounded">visibility</span>
                                        </a>
                                        <a class="ph-action-btn edit" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchaseEdit/<?php echo (int) $purchase['id']; ?>" title="Edit Purchase Bill">
                                            <span class="material-symbols-rounded">edit</span>
                                        </a>
                                        <button type="button" class="ph-action-btn delete btn-delete-purchase" 
                                            data-id="<?php echo (int)$purchase['id']; ?>" 
                                            data-supplier="<?php echo Security::escape($purchase['supplier_name']); ?>" 
                                            data-total="<?php echo Security::escape(App::money($purchase['bill_total'])); ?>" 
                                            data-date="<?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?>" 
                                            title="Delete Purchase Bill">
                                            <span class="material-symbols-rounded">delete</span>
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
</section>

<!-- Custom Compact Confirmation Modal for Purchase Deletion -->
<div id="deletePurchaseModal" class="compact-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <div class="compact-modal-card">
        <div class="compact-modal-header">
            <div class="compact-modal-icon-badge">
                <span class="material-symbols-rounded">delete_forever</span>
            </div>
            <div>
                <h4 id="deleteModalTitle" class="compact-modal-title">Delete Purchase Bill</h4>
                <p class="compact-modal-sub">Permanently revert stock & balance</p>
            </div>
        </div>
        <div class="compact-modal-body">
            <p class="compact-modal-desc">
                Are you sure you want to delete this purchase bill? This will <strong>deduct the purchased quantities</strong> from inventory stock and <strong>re-sync the supplier ledger</strong>.
            </p>
            <div class="compact-modal-info-pill">
                <div class="compact-modal-info-row">
                    <span>Purchase Bill:</span>
                    <strong id="modalBillRef">Bill #--</strong>
                </div>
                <div class="compact-modal-info-row">
                    <span>Supplier / Vendor:</span>
                    <strong id="modalSupplierName">--</strong>
                </div>
                <div class="compact-modal-info-row">
                    <span>Bill Total:</span>
                    <strong id="modalBillTotal" style="color:#1d4ed8;">Rs 0.00</strong>
                </div>
                <div class="compact-modal-info-row">
                    <span>Date:</span>
                    <strong id="modalBillDate">--</strong>
                </div>
            </div>
        </div>
        <form id="deletePurchaseForm" method="POST" action="" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <div class="compact-modal-actions">
                <button type="button" class="compact-modal-btn cancel" id="cancelDeleteBtn">
                    Cancel
                </button>
                <button type="submit" class="compact-modal-btn danger" id="confirmDeleteBtn">
                    <span class="material-symbols-rounded" style="font-size:18px;">delete</span>
                    Delete Bill
                </button>
            </div>
        </form>
    </div>
</div>

<section class="inventory-tab-pane" data-inventory-pane="import">
    <div class="csv-import-shell">
        <form class="card" style="padding:24px;" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/import" method="POST" enctype="multipart/form-data" data-no-ajax>
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
                <div>
                    <h3 style="margin-bottom:4px; font-weight:900; font-size:17px; color:#0f172a;">Bulk Inventory Import</h3>
                    <p class="page-subtitle" style="margin:0;">Upload your product catalog in CSV format to import thousands of items in seconds.</p>
                </div>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/sampleTemplate" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; font-weight:800; border-color:#bfdbfe; color:#0f6dff; background:#eff6ff;">
                    <span class="material-symbols-rounded">download</span> Download Sample Template (.csv)
                </a>
            </div>
            <?php echo Security::csrfField(); ?>
            <label class="csv-dropzone" id="inventory-csv-dropzone">
                <input id="inventory-csv-file" type="file" name="file" accept=".csv" required style="position:absolute; width:1px; height:1px; opacity:0; pointer-events:none;">
                <div>
                    <div class="csv-dropzone-icon"><span class="material-symbols-rounded">upload_file</span></div>
                    <div class="csv-dropzone-title">Drop CSV file here</div>
                    <div class="page-subtitle">or click to choose file from your computer</div>
                    <div class="csv-file-name" id="inventory-csv-file-name" style="display:none; margin-top:10px; background:#eff6ff; border:1px solid #bfdbfe; color:#0f6dff; padding:6px 12px; border-radius:999px; font-weight:800; font-size:12.5px;">
                        <span class="material-symbols-rounded" style="font-size:16px;">description</span>
                        <span></span>
                    </div>
                </div>
            </label>

            <!-- Dynamic Client-Side CSV Preview Container -->
            <div id="csv-live-preview-box" style="display:none; margin-top:18px; border:1px solid #e2e8f0; border-radius:14px; padding:14px 16px; background:#f8fafc;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <div style="font-size:13px; font-weight:900; color:#0f172a; display:flex; align-items:center; gap:6px;">
                        <span class="material-symbols-rounded" style="font-size:18px; color:#0f6dff;">preview</span> CSV Preview & Column Matching
                    </div>
                    <div id="csv-preview-stats" style="font-size:11.5px; font-weight:800; color:#0f6dff; background:#eff6ff; padding:3px 8px; border-radius:8px; border:1px solid #bfdbfe;">
                        --- Rows Found
                    </div>
                </div>
                <div style="overflow-x:auto; max-height:220px; border-radius:10px; border:1px solid #e2e8f0; background:#fff;">
                    <table style="width:100%; font-size:12px; border-collapse:collapse;" id="csv-preview-table">
                        <thead style="background:#f1f5f9; position:sticky; top:0;">
                            <tr id="csv-preview-thead-row"></tr>
                        </thead>
                        <tbody id="csv-preview-tbody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Modern Toggle Switch for Update Existing -->
            <div style="margin:16px 0; background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; justify-content:space-between; align-items:center; gap:12px; box-shadow:0 2px 6px rgba(0,0,0,0.02);">
                <div>
                    <div style="font-weight:900; font-size:13.5px; color:#0f172a;">Update Existing Products</div>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">Update price, cost and add quantity to stock if Barcode / Name already exists</div>
                </div>
                <label class="box-auto-toggle" style="margin:0;">
                    <input type="checkbox" name="update_existing" value="1" checked>
                    <span class="box-auto-switch"></span>
                </label>
            </div>

            <div class="csv-import-actions">
                <span class="form-hint">Supports CSV files encoded in UTF-8.</span>
                <button class="btn btn-primary" type="submit" style="font-weight:900; padding:12px 24px; font-size:14px;"><span class="material-symbols-rounded">upload</span>Upload & Start Import</button>
            </div>
        </form>
        <aside class="csv-import-side">
            <div>
                <h3>Supported CSV Columns</h3>
                <p style="color:rgba(255,255,255,.72); line-height:1.6; margin:0;">Standard column headers recognized automatically by the parser:</p>
                <div class="csv-format-list">
                    <span><span class="material-symbols-rounded">check_circle</span><strong>Name</strong> (Required product title)</span>
                    <span><span class="material-symbols-rounded">check_circle</span><strong>Barcode</strong> (Auto-generated if blank)</span>
                    <span><span class="material-symbols-rounded">check_circle</span><strong>Model_Number</strong>, <strong>Unit_Value</strong>, <strong>Unit_Type</strong></span>
                    <span><span class="material-symbols-rounded">check_circle</span><strong>Price</strong>, <strong>Wholesale_Price</strong>, <strong>Cost</strong></span>
                    <span><span class="material-symbols-rounded">check_circle</span><strong>Stock</strong>, <strong>Min_Stock</strong></span>
                    <span><span class="material-symbols-rounded">check_circle</span><strong>Batch_Number</strong>, <strong>Expiry_Date</strong></span>
                </div>
            </div>
            <div style="border-top:1px solid rgba(255,255,255,.12); padding-top:16px;">
                <strong style="display:block; margin-bottom:5px;">Barcode Auto-Creation</strong>
                <span style="color:rgba(255,255,255,.72); font-size:13px; line-height:1.6;">If you leave the barcode column empty, a unique retail barcode will be generated automatically for every product.</span>
            </div>
        </aside>
    </div>
</section>
</div>

<?php ob_start(); ?>
<script>
    (() => {
        const pageContainer = document.getElementById('inventory-index-page');
        if (!pageContainer) return;

        // Tab switching logic
        const tabButtons = Array.from(pageContainer.querySelectorAll('[data-inventory-tab]'));
        const panes = Array.from(pageContainer.querySelectorAll('[data-inventory-pane]'));
        const tabKey = 'tab_' + (new URLSearchParams(window.location.search).get('url') || 'inventory/index');

        const setTab = (tab) => {
            tabButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.inventoryTab === tab));
            panes.forEach((pane) => pane.classList.toggle('active', pane.dataset.inventoryPane === tab));
            sessionStorage.setItem(tabKey, tab);
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url.toString());
        };

        tabButtons.forEach((btn) => {
            btn.addEventListener('click', () => setTab(btn.dataset.inventoryTab));
        });

        const validTabs = ['add', 'list', 'purchase_add', 'purchase_list', 'import'];
        const urlParamsTab = new URLSearchParams(window.location.search).get('tab');
        const initialTab = (urlParamsTab && validTabs.includes(urlParamsTab)) ? urlParamsTab : (sessionStorage.getItem(tabKey) || 'add');
        if (validTabs.includes(initialTab)) {
            setTab(initialTab);
        }

        // --- PURCHASE HISTORY INSTANT LIVE SEARCH & STATUS FILTER ---
        const phSearchInput = document.getElementById('purchase_history_search');
        const phFilterPills = document.getElementById('purchase_filter_pills');
        const phRows = Array.from(document.querySelectorAll('.purchase-history-row'));
        let currentPhFilter = 'all';

        const filterPurchaseRows = () => {
            const query = (phSearchInput?.value || '').trim().toLowerCase();
            let visibleCount = 0;

            phRows.forEach(row => {
                const supplier = row.dataset.supplier || '';
                const item = row.dataset.item || '';
                const ref = row.dataset.ref || '';
                const id = row.dataset.id || '';
                const hasUdhar = row.dataset.hasUdhar === '1';

                const matchesQuery = !query || supplier.includes(query) || item.includes(query) || ref.includes(query) || id.includes(query);
                let matchesStatus = true;
                if (currentPhFilter === 'paid') {
                    matchesStatus = !hasUdhar;
                } else if (currentPhFilter === 'udhar') {
                    matchesStatus = hasUdhar;
                }

                const visible = matchesQuery && matchesStatus;
                row.style.display = visible ? '' : 'none';
                if (visible) visibleCount++;
            });

            const tbody = document.getElementById('purchase_history_tbody');
            let noMatchRow = document.getElementById('ph_no_matches_row');
            if (visibleCount === 0 && phRows.length > 0) {
                if (!noMatchRow && tbody) {
                    noMatchRow = document.createElement('tr');
                    noMatchRow.id = 'ph_no_matches_row';
                    noMatchRow.innerHTML = '<td colspan="5" class="empty-state" style="text-align:center; padding:32px; color:#64748b;">No purchases matched your search criteria.</td>';
                    tbody.appendChild(noMatchRow);
                } else if (noMatchRow) {
                    noMatchRow.style.display = '';
                }
            } else if (noMatchRow) {
                noMatchRow.style.display = 'none';
            }
        };

        phSearchInput?.addEventListener('input', filterPurchaseRows);

        phFilterPills?.querySelectorAll('[data-ph-filter]').forEach(btn => {
            btn.addEventListener('click', () => {
                phFilterPills.querySelectorAll('[data-ph-filter]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentPhFilter = btn.dataset.phFilter;
                filterPurchaseRows();
            });
        });

        // ==========================================
        // DYNAMIC MULTI-ITEM PURCHASE BILL SYSTEM
        // ==========================================
        const allProductsData = <?php echo json_encode(array_values(array_map(function($p) {
            return [
                'id' => (int) $p['id'],
                'name' => (string) $p['name'],
                'barcode' => (string) ($p['barcode'] ?? ''),
                'stock' => (float) ($p['stock'] ?? 0),
                'cost' => (float) ($p['cost'] ?? 0),
                'price' => (float) ($p['price'] ?? 0),
                'unit_type' => (string) ($p['unit_type'] ?? 'pcs'),
            ];
        }, $allProductsList ?? []))); ?>;

        const allSuppliersData = <?php echo json_encode(array_values(array_map(function($s) {
            return [
                'id' => (int) $s['id'],
                'name' => (string) $s['name'],
                'company_name' => (string) ($s['company_name'] ?? ''),
            ];
        }, $suppliersList ?? []))); ?>;

        const allowedUnits = <?php echo json_encode(array_values(array_map(fn($u) => [
            'id' => $u,
            'label' => App::productUnitLabel($u)
        ], App::allowedProductUnits()))); ?>;

        const currencySymbol = 'Rs ';
        const formatMoney = (amount) => {
            return currencySymbol + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        const escapeHtml = (str) => {
            return String(str || '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
        };

        // Table & Form Elements
        const purchaseItemsTbody = document.getElementById('purchase_items_tbody');
        const addPurchaseRowBtn = document.getElementById('add_purchase_row_btn');
        const addPurchaseRowBtnBottom = document.getElementById('add_purchase_row_btn_bottom');
        const purchaseBillTotalInput = document.getElementById('purchase_bill_total_input');
        const purchasePaidAmountInput = document.getElementById('purchase_paid_amount_input');
        const purchaseBalanceDisplay = document.getElementById('purchase_balance_display');
        const udharStatusPill = document.getElementById('udhar_status_pill');
        const udharAlertBox = document.getElementById('udhar_alert_box');
        const udharAlertTitle = document.getElementById('udhar_alert_title');
        const udharAlertDesc = document.getElementById('udhar_alert_desc');
        const btnQuickFullPay = document.getElementById('btn_quick_full_pay');
        const btnQuickFullUdhar = document.getElementById('btn_quick_full_udhar');
        const globalCostMethod = document.getElementById('global_cost_method');

        const tableTotalQty = document.getElementById('table_total_qty');
        const tableGrandTotal = document.getElementById('table_grand_total');

        const sideItemsCount = document.getElementById('side_items_count');
        const sideTotalQty = document.getElementById('side_total_qty');
        const sideBillTotal = document.getElementById('side_bill_total');
        const sidePaidAmount = document.getElementById('side_paid_amount');
        const sideBalanceAmount = document.getElementById('side_balance_amount');
        const sideCostMode = document.getElementById('side_cost_mode');

        let purchaseRowIndex = 0;

        const renumberPurchaseRows = () => {
            if (!purchaseItemsTbody) return;
            const rows = purchaseItemsTbody.querySelectorAll('tr.purchase-item-row');
            rows.forEach((row, i) => {
                const numCell = row.querySelector('.row-num-cell');
                if (numCell) numCell.textContent = String(i + 1);
            });
        };

        const recalcBill = () => {
            if (!purchaseItemsTbody) return;
            const rows = purchaseItemsTbody.querySelectorAll('tr.purchase-item-row');
            let grandTotal = 0;
            let totalQty = 0;
            let validItemsCount = 0;

            rows.forEach((row) => {
                const nameInput = row.querySelector('.row-item-name');
                const qtyInput = row.querySelector('.row-qty');
                const costInput = row.querySelector('.row-cost');
                const subtotalDisplay = row.querySelector('.row-subtotal');

                const qty = Math.max(0, parseFloat(qtyInput?.value || 0));
                const cost = Math.max(0, parseFloat(costInput?.value || 0));
                const subtotal = Math.round(qty * cost * 100) / 100;

                if (subtotalDisplay) {
                    subtotalDisplay.textContent = formatMoney(subtotal);
                }

                if (nameInput && nameInput.value.trim() !== '') {
                    validItemsCount++;
                }

                grandTotal += subtotal;
                totalQty += qty;
            });

            grandTotal = Math.round(grandTotal * 100) / 100;

            if (purchaseBillTotalInput) {
                purchaseBillTotalInput.value = grandTotal.toFixed(2);
            }
            if (tableGrandTotal) {
                tableGrandTotal.textContent = formatMoney(grandTotal);
            }
            if (tableTotalQty) {
                tableTotalQty.textContent = totalQty.toLocaleString('en-US', { maximumFractionDigits: 3 });
            }

            // Sync side cards
            if (sideItemsCount) sideItemsCount.textContent = `${validItemsCount} item${validItemsCount === 1 ? '' : 's'}`;
            if (sideTotalQty) sideTotalQty.textContent = `${totalQty.toFixed(2)} units`;
            if (sideBillTotal) sideBillTotal.textContent = formatMoney(grandTotal);

            // Calculate Udhar / Balance
            let paid = parseFloat(purchasePaidAmountInput?.value);
            if (isNaN(paid)) {
                paid = grandTotal; // Default to full pay if not touched
                if (purchasePaidAmountInput && document.activeElement !== purchasePaidAmountInput && purchasePaidAmountInput.dataset.manual !== '1') {
                    purchasePaidAmountInput.value = grandTotal > 0 ? grandTotal.toFixed(2) : '';
                }
            }

            const udharBalance = Math.max(0, Math.round((grandTotal - (isNaN(paid) ? 0 : paid)) * 100) / 100);

            if (purchaseBalanceDisplay) {
                purchaseBalanceDisplay.value = formatMoney(udharBalance);
            }
            if (sidePaidAmount) {
                sidePaidAmount.textContent = formatMoney(isNaN(paid) ? 0 : paid);
            }
            if (sideBalanceAmount) {
                sideBalanceAmount.textContent = formatMoney(udharBalance);
            }

            // Pill & Alert Banner Sync
            const supName = document.getElementById('purchase_supplier_input')?.value.trim() || 'Supplier';
            if (udharBalance <= 0) {
                if (udharStatusPill) {
                    udharStatusPill.innerHTML = '<span class="material-symbols-rounded" style="color:#059669; font-size:17px;">verified</span><span class="pill-text">Fully Paid (No Udhar)</span>';
                    udharStatusPill.style.background = '#ecfdf5';
                    udharStatusPill.style.color = '#059669';
                    udharStatusPill.style.borderColor = '#a7f3d0';
                }
                if (udharAlertBox) udharAlertBox.style.display = 'none';
            } else if ((isNaN(paid) ? 0 : paid) > 0) {
                if (udharStatusPill) {
                    udharStatusPill.innerHTML = `<span class="material-symbols-rounded" style="color:#b45309; font-size:17px;">pending</span><span class="pill-text">Partial Udhar: ${formatMoney(udharBalance)}</span>`;
                    udharStatusPill.style.background = '#fffbeb';
                    udharStatusPill.style.color = '#b45309';
                    udharStatusPill.style.borderColor = '#fde68a';
                }
                if (udharAlertBox) {
                    udharAlertBox.style.display = 'flex';
                    if (udharAlertTitle) udharAlertTitle.textContent = `Partial Payment & Udhar: ${formatMoney(udharBalance)} Remaining`;
                    if (udharAlertDesc) udharAlertDesc.innerHTML = `Paid ${formatMoney(paid)}. The remaining <strong>${formatMoney(udharBalance)}</strong> will automatically be credited to <strong>${escapeHtml(supName)}</strong>'s ledger account.`;
                }
            } else {
                if (udharStatusPill) {
                    udharStatusPill.innerHTML = `<span class="material-symbols-rounded" style="color:#dc2626; font-size:17px;">warning</span><span class="pill-text">Full Udhar: ${formatMoney(udharBalance)}</span>`;
                    udharStatusPill.style.background = '#fef2f2';
                    udharStatusPill.style.color = '#dc2626';
                    udharStatusPill.style.borderColor = '#fecaca';
                }
                if (udharAlertBox) {
                    udharAlertBox.style.display = 'flex';
                    if (udharAlertTitle) udharAlertTitle.textContent = `Full Credit (Udhar) Purchase: ${formatMoney(udharBalance)}`;
                    if (udharAlertDesc) udharAlertDesc.innerHTML = `Total bill of <strong>${formatMoney(udharBalance)}</strong> will be recorded as 100% Udhar payable in <strong>${escapeHtml(supName)}</strong>'s ledger.`;
                }
            }
        };

        const addPurchaseRow = (initial = null) => {
            if (!purchaseItemsTbody) return;
            const rId = purchaseRowIndex++;

            const tr = document.createElement('tr');
            tr.className = 'purchase-item-row';
            tr.dataset.rowId = String(rId);
            tr.style.borderBottom = '1px solid #f1f5f9';

            let currentUnitId = initial?.unit_type || 'pcs';
            let currentUnitObj = allowedUnits.find(u => u.id === currentUnitId) || allowedUnits[0] || { id: 'pcs', label: 'Pieces (pcs)' };
            let currentUnitLabel = currentUnitObj.label;

            let unitsHtml = allowedUnits.map(u => {
                const isSel = (u.id === currentUnitId) ? 'is-selected' : '';
                return `
                    <div class="row-unit-item ${isSel}" data-unit-id="${escapeHtml(u.id)}" data-unit-label="${escapeHtml(u.label)}">
                        <span>${escapeHtml(u.label)}</span>
                        <span class="material-symbols-rounded row-unit-item-check">check_circle</span>
                    </div>
                `;
            }).join('');

            tr.innerHTML = `
                <td class="row-num-cell" style="text-align:center; font-weight:800; color:#94a3b8; font-size:12px; padding:8px 6px;">1</td>
                <td style="padding:6px 10px; position:relative;">
                    <div class="row-dropdown-wrap">
                        <input type="hidden" name="items[${rId}][product_id]" class="row-product-id" value="${initial?.product_id || ''}">
                        <div style="position:relative;">
                            <input type="text" name="items[${rId}][item_name]" class="form-input row-item-name" required placeholder="Type product name or barcode..." autocomplete="off" value="${escapeHtml(initial?.name || initial?.item_name || '')}" style="font-weight:800; font-size:13px; min-height:38px; padding-right:26px;">
                            <span class="row-matched-indicator" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); display:${initial?.product_id ? 'inline-block' : 'none'}; color:#16a34a; font-size:16px;" title="Linked to catalog product">✓</span>
                        </div>
                        <div class="row-dropdown-menu"></div>
                    </div>
                </td>
                <td style="padding:6px 6px;">
                    <div class="row-unit-picker" id="unit_picker_${rId}">
                        <input type="hidden" name="items[${rId}][unit_type]" class="row-unit-input" value="${escapeHtml(currentUnitId)}">
                        <div class="row-unit-btn" tabindex="0">
                            <span class="row-unit-btn-text">${escapeHtml(currentUnitLabel)}</span>
                            <span class="material-symbols-rounded" style="font-size:16px; color:#64748b;">expand_more</span>
                        </div>
                        <div class="row-unit-menu">
                            ${unitsHtml}
                        </div>
                    </div>
                </td>
                <td style="padding:6px 6px; text-align:right;">
                    <input type="number" step="0.001" min="0.001" name="items[${rId}][quantity]" class="form-input row-qty" required value="${initial?.quantity !== undefined ? initial.quantity : '1'}" style="text-align:right; font-weight:850; min-height:38px; padding:4px 8px;">
                </td>
                <td style="padding:6px 6px; text-align:right;">
                    <input type="number" step="0.01" min="0" name="items[${rId}][unit_cost]" class="form-input row-cost" required value="${initial?.cost !== undefined ? initial.cost : (initial?.unit_cost !== undefined ? initial.unit_cost : '0.00')}" style="text-align:right; font-weight:850; min-height:38px; padding:4px 8px;">
                </td>
                <td style="padding:6px 6px; text-align:right;">
                    <input type="number" step="0.01" min="0" name="items[${rId}][new_sale_price]" class="form-input row-price" placeholder="Optional" value="${initial?.price !== undefined ? initial.price : (initial?.new_sale_price || '')}" style="text-align:right; font-weight:800; min-height:38px; padding:4px 8px;">
                </td>
                <td style="padding:6px 10px; text-align:right;">
                    <div class="row-subtotal" style="font-weight:900; font-size:13.5px; color:#0f172a; text-align:right;">Rs 0.00</div>
                </td>
                <td style="padding:6px 6px; text-align:center;">
                    <button type="button" class="icon-btn icon-btn-danger row-remove-btn" title="Remove Item" style="width:32px; height:32px; border-radius:8px;">
                        <span class="material-symbols-rounded" style="font-size:18px;">delete</span>
                    </button>
                </td>
            `;

            purchaseItemsTbody.appendChild(tr);

            // Wire Row Events
            const nameInput = tr.querySelector('.row-item-name');
            const prodIdInput = tr.querySelector('.row-product-id');
            const unitPicker = tr.querySelector('.row-unit-picker');
            const unitInput = tr.querySelector('.row-unit-input');
            const unitBtn = tr.querySelector('.row-unit-btn');
            const unitBtnText = tr.querySelector('.row-unit-btn-text');
            const unitMenu = tr.querySelector('.row-unit-menu');
            const qtyInput = tr.querySelector('.row-qty');
            const costInput = tr.querySelector('.row-cost');
            const priceInput = tr.querySelector('.row-price');
            const menu = tr.querySelector('.row-dropdown-menu');
            const indicator = tr.querySelector('.row-matched-indicator');
            const removeBtn = tr.querySelector('.row-remove-btn');

            // Unit Picker Dropdown Events (Fixed positioning to prevent table clipping)
            unitBtn?.addEventListener('click', (e) => {
                e.stopPropagation();
                const wasOpen = unitPicker.classList.contains('is-open');
                document.querySelectorAll('.row-unit-picker.is-open').forEach(p => {
                    p.classList.remove('is-open');
                    const um = p.querySelector('.row-unit-menu');
                    if (um) um.style.display = 'none';
                });
                document.querySelectorAll('.premium-select-wrap.is-open').forEach(w => w.classList.remove('is-open'));
                document.querySelectorAll('.row-dropdown-menu').forEach(m => m.style.display = 'none');

                if (!wasOpen) {
                    unitPicker.classList.add('is-open');
                    const rect = unitBtn.getBoundingClientRect();
                    unitMenu.style.position = 'fixed';
                    unitMenu.style.top = (rect.bottom + 4) + 'px';
                    unitMenu.style.left = rect.left + 'px';
                    unitMenu.style.width = Math.max(150, rect.width) + 'px';
                    unitMenu.style.zIndex = '99999';
                    unitMenu.style.display = 'block';
                }
            });

            unitMenu?.addEventListener('click', (e) => {
                const uItem = e.target.closest('.row-unit-item');
                if (!uItem) return;
                e.stopPropagation();
                const uId = uItem.dataset.unitId;
                const uLabel = uItem.dataset.unitLabel;
                if (unitInput) unitInput.value = uId;
                if (unitBtnText) unitBtnText.textContent = uLabel;
                unitMenu.querySelectorAll('.row-unit-item').forEach(it => it.classList.toggle('is-selected', it === uItem));
                unitPicker.classList.remove('is-open');
                unitMenu.style.display = 'none';
            });

            const positionRowMenu = () => {
                const rect = nameInput.getBoundingClientRect();
                menu.style.position = 'fixed';
                menu.style.top = (rect.bottom + 4) + 'px';
                menu.style.left = rect.left + 'px';
                menu.style.width = Math.min(360, Math.max(310, rect.width)) + 'px';
                menu.style.zIndex = '99999';
            };

            const renderRowDropdown = (filterText = '') => {
                const q = filterText.trim().toLowerCase();
                const matched = allProductsData.filter(p => {
                    if (!q) return true;
                    return p.name.toLowerCase().includes(q) || (p.barcode && p.barcode.toLowerCase().includes(q));
                }).slice(0, 30);

                let html = '';
                const exactMatch = allProductsData.find(p => p.name.toLowerCase() === q);
                if (q !== '' && !exactMatch) {
                    html += `
                        <div class="searchable-dropdown-item custom-row-item" data-custom-name="${escapeHtml(filterText)}" style="background:#eff6ff; border:1px dashed #bfdbfe; margin-bottom:4px; padding:6px 8px; border-radius:6px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span class="material-symbols-rounded" style="color:#2563eb; font-size:16px;">add_circle</span>
                                <div>
                                    <strong style="color:#1d4ed8; font-size:11.5px;">+ Create "${escapeHtml(filterText)}"</strong>
                                    <div style="font-size:9.5px; color:#3b82f6;">Auto-saves to product catalog on submit</div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                if (matched.length === 0 && q === '') {
                    html += '<div style="padding:12px; text-align:center; color:#64748b; font-size:11.5px;">Type product name to search...</div>';
                } else {
                    html += matched.map(p => `
                        <div class="searchable-dropdown-item catalog-select-item" data-id="${p.id}" data-name="${escapeHtml(p.name)}" data-unit="${escapeHtml(p.unit_type)}" data-cost="${p.cost}" data-price="${p.price}">
                            <div class="catalog-select-item-lead">
                                <div class="catalog-select-item-icon">
                                    <span class="material-symbols-rounded">inventory_2</span>
                                </div>
                                <div class="catalog-select-item-info">
                                    <strong title="${escapeHtml(p.name)}">${escapeHtml(p.name)}</strong>
                                    <div class="catalog-select-item-meta">
                                        <span>Stock: <strong style="color:#0f172a; font-weight:800;">${p.stock}</strong></span>
                                    </div>
                                </div>
                            </div>
                            <div class="catalog-select-item-prices">
                                <span class="cost-tag">Cost: ${formatMoney(p.cost)}</span>
                                <span class="price-tag">Price: ${formatMoney(p.price)}</span>
                            </div>
                        </div>
                    `).join('');
                }

                menu.innerHTML = html;
            };

            const openRowDropdown = () => {
                document.querySelectorAll('.row-dropdown-menu').forEach(other => {
                    if (other !== menu) other.style.display = 'none';
                });
                document.querySelectorAll('.row-unit-picker.is-open').forEach(p => {
                    p.classList.remove('is-open');
                    const um = p.querySelector('.row-unit-menu');
                    if (um) um.style.display = 'none';
                });
                renderRowDropdown(nameInput.value);
                positionRowMenu();
                menu.style.display = 'block';
            };

            nameInput.addEventListener('focus', openRowDropdown);

            nameInput.addEventListener('input', () => {
                openRowDropdown();
                const exact = allProductsData.find(p => p.name.toLowerCase() === nameInput.value.trim().toLowerCase());
                if (exact) {
                    prodIdInput.value = exact.id;
                    if (indicator) indicator.style.display = 'inline-block';
                } else {
                    prodIdInput.value = '';
                    if (indicator) indicator.style.display = 'none';
                }
                recalcBill();
            });

            menu.addEventListener('click', (e) => {
                const item = e.target.closest('.catalog-select-item');
                const custom = e.target.closest('.custom-row-item');

                if (item) {
                    prodIdInput.value = item.dataset.id;
                    nameInput.value = item.dataset.name;
                    if (item.dataset.unit) {
                        const matchingUnit = allowedUnits.find(u => u.id === item.dataset.unit);
                        if (matchingUnit) {
                            if (unitInput) unitInput.value = matchingUnit.id;
                            if (unitBtnText) unitBtnText.textContent = matchingUnit.label;
                            unitMenu?.querySelectorAll('.row-unit-item').forEach(it => it.classList.toggle('is-selected', it.dataset.unitId === matchingUnit.id));
                        }
                    }
                    if (item.dataset.cost && costInput) costInput.value = Number(item.dataset.cost).toFixed(2);
                    if (item.dataset.price && priceInput) priceInput.value = Number(item.dataset.price).toFixed(2);
                    if (indicator) indicator.style.display = 'inline-block';
                    menu.style.display = 'none';
                    qtyInput.focus();
                    recalcBill();
                } else if (custom) {
                    prodIdInput.value = '';
                    nameInput.value = custom.dataset.customName;
                    if (indicator) indicator.style.display = 'none';
                    menu.style.display = 'none';
                    qtyInput.focus();
                    recalcBill();
                }
            });

            qtyInput.addEventListener('input', recalcBill);
            costInput.addEventListener('input', recalcBill);

            removeBtn.addEventListener('click', () => {
                const totalRows = purchaseItemsTbody.querySelectorAll('tr.purchase-item-row').length;
                if (totalRows <= 1) {
                    nameInput.value = '';
                    prodIdInput.value = '';
                    qtyInput.value = '1';
                    costInput.value = '0.00';
                    priceInput.value = '';
                    if (indicator) indicator.style.display = 'none';
                } else {
                    tr.remove();
                }
                renumberPurchaseRows();
                recalcBill();
            });

            renumberPurchaseRows();
            recalcBill();
        };

        addPurchaseRowBtn?.addEventListener('click', () => addPurchaseRow());
        addPurchaseRowBtnBottom?.addEventListener('click', () => addPurchaseRow());

        // Quick Settlement buttons
        btnQuickFullPay?.addEventListener('click', () => {
            if (purchasePaidAmountInput && purchaseBillTotalInput) {
                purchasePaidAmountInput.value = purchaseBillTotalInput.value;
                purchasePaidAmountInput.dataset.manual = '1';
                recalcBill();
            }
        });

        btnQuickFullUdhar?.addEventListener('click', () => {
            if (purchasePaidAmountInput) {
                purchasePaidAmountInput.value = '0.00';
                purchasePaidAmountInput.dataset.manual = '1';
                recalcBill();
            }
        });

        purchasePaidAmountInput?.addEventListener('input', () => {
            purchasePaidAmountInput.dataset.manual = '1';
            recalcBill();
        });

        // Premium Custom Selector Wireup
        const initPremiumSelect = (wrapId, hiddenInputId, onSelectCallback = null) => {
            const wrap = document.getElementById(wrapId);
            if (!wrap) return;
            const trigger = wrap.querySelector('.premium-select-trigger');
            const hiddenInput = document.getElementById(hiddenInputId);
            const triggerIcon = wrap.querySelector('.premium-select-trigger-icon');
            const triggerText = wrap.querySelector('.premium-select-trigger-text');
            const items = wrap.querySelectorAll('.premium-select-item');

            trigger?.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('.premium-select-wrap.is-open').forEach(w => {
                    if (w !== wrap) w.classList.remove('is-open');
                });
                document.querySelectorAll('.row-unit-picker.is-open').forEach(p => p.classList.remove('is-open'));
                wrap.classList.toggle('is-open');
            });

            items.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const val = item.dataset.value;
                    const icon = item.dataset.icon;
                    const title = item.dataset.title;

                    if (hiddenInput) {
                        hiddenInput.value = val;
                        hiddenInput.dispatchEvent(new Event('change'));
                    }
                    if (triggerIcon && icon) triggerIcon.textContent = icon;
                    if (triggerText && title) triggerText.textContent = title;

                    items.forEach(it => it.classList.toggle('is-selected', it === item));
                    wrap.classList.remove('is-open');

                    if (typeof onSelectCallback === 'function') {
                        onSelectCallback(val);
                    }
                });
            });
        };

        initPremiumSelect('cost_method_select_wrap', 'global_cost_method', (val) => {
            if (sideCostMode) {
                sideCostMode.textContent = val === 'latest_price' ? 'Latest Price' : 'Weighted Average';
            }
        });

        initPremiumSelect('payment_method_select_wrap', 'purchase_payment_method');

        globalCostMethod?.addEventListener('change', () => {
            if (sideCostMode) {
                sideCostMode.textContent = globalCostMethod.value === 'latest_price' ? 'Latest Price' : 'Weighted Average';
            }
        });

        // Supplier Searchable Dropdown
        const purchaseSupplierInput = document.getElementById('purchase_supplier_input');
        const supplierSearchMenu = document.getElementById('supplier_search_dropdown_menu');
        const supplierItemsList = document.getElementById('supplier_dropdown_items_list');

        const renderSupplierDropdownItems = (filterText = '') => {
            if (!supplierItemsList) return;
            const q = filterText.trim().toLowerCase();
            const matched = allSuppliersData.filter(s => {
                if (!q) return true;
                return s.name.toLowerCase().includes(q) || (s.company_name && s.company_name.toLowerCase().includes(q));
            });

            let html = '';
            if (q !== '' && !allSuppliersData.find(s => s.name.toLowerCase() === q)) {
                html += `
                    <div class="searchable-dropdown-item custom-supplier-option" data-name="${escapeHtml(filterText)}" style="background:#eff6ff; border:1px dashed #bfdbfe; margin-bottom:4px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span class="material-symbols-rounded" style="color:#2563eb; font-size:18px;">add_business</span>
                            <span style="font-weight:800; font-size:12.5px; color:#1d4ed8;">Use "${escapeHtml(filterText)}" as Supplier</span>
                        </div>
                    </div>
                `;
            }

            if (matched.length === 0 && q === '') {
                html += '<div style="padding:12px; text-align:center; color:#64748b; font-size:12px;">No saved suppliers. Type supplier name directly.</div>';
            } else {
                html += matched.map(s => `
                    <div class="searchable-dropdown-item supplier-select-option" data-name="${escapeHtml(s.name)}" style="padding:8px 10px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span class="material-symbols-rounded" style="color:#64748b; font-size:18px;">store</span>
                            <div>
                                <strong style="font-size:13px; color:#0f172a;">${escapeHtml(s.name)}</strong>
                                ${s.company_name && s.company_name !== s.name ? `<div style="font-size:11px; color:#64748b;">${escapeHtml(s.company_name)}</div>` : ''}
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            supplierItemsList.innerHTML = html;
        };

        purchaseSupplierInput?.addEventListener('focus', () => {
            renderSupplierDropdownItems(purchaseSupplierInput.value);
            if (supplierSearchMenu) supplierSearchMenu.style.display = 'block';
        });

        purchaseSupplierInput?.addEventListener('input', () => {
            renderSupplierDropdownItems(purchaseSupplierInput.value);
            if (supplierSearchMenu) supplierSearchMenu.style.display = 'block';
            recalcBill();
        });

        supplierItemsList?.addEventListener('click', (e) => {
            const opt = e.target.closest('.supplier-select-option, .custom-supplier-option');
            if (opt && purchaseSupplierInput) {
                purchaseSupplierInput.value = opt.dataset.name;
                if (supplierSearchMenu) supplierSearchMenu.style.display = 'none';
                recalcBill();
            }
        });

        // Close dropdowns on outside click
        // Close dropdowns on outside click
        document.addEventListener('click', (e) => {
            document.querySelectorAll('.row-unit-picker.is-open').forEach(p => {
                if (!p.contains(e.target)) {
                    p.classList.remove('is-open');
                    const um = p.querySelector('.row-unit-menu');
                    if (um) um.style.display = 'none';
                }
            });
            document.querySelectorAll('.premium-select-wrap.is-open').forEach(w => {
                if (!w.contains(e.target)) w.classList.remove('is-open');
            });
            const rowMenus = document.querySelectorAll('.row-dropdown-menu');
            rowMenus.forEach(m => {
                const wrap = m.closest('.row-dropdown-wrap');
                if (!wrap || (!wrap.contains(e.target) && !m.contains(e.target))) {
                    m.style.display = 'none';
                }
            });
            const supplierWrap = document.getElementById('supplier_dropdown_wrap');
            if (supplierWrap && !supplierWrap.contains(e.target) && supplierSearchMenu) {
                supplierSearchMenu.style.display = 'none';
            }
        });

        // Reposition floating dropdown menus on scroll and resize
        const updateFloatingDropdowns = () => {
            document.querySelectorAll('.row-dropdown-menu').forEach(m => {
                if (m.style.display === 'block') {
                    const wrap = m.closest('.row-dropdown-wrap');
                    const inp = wrap?.querySelector('.row-item-name');
                    if (inp) {
                        const rect = inp.getBoundingClientRect();
                        if (rect.bottom < 40 || rect.top > window.innerHeight - 30) {
                            m.style.display = 'none';
                        } else {
                            m.style.top = (rect.bottom + 4) + 'px';
                            m.style.left = rect.left + 'px';
                        }
                    }
                }
            });
            document.querySelectorAll('.row-unit-picker.is-open').forEach(p => {
                const uBtn = p.querySelector('.row-unit-btn');
                const uMenu = p.querySelector('.row-unit-menu');
                if (uBtn && uMenu) {
                    const rect = uBtn.getBoundingClientRect();
                    if (rect.bottom < 40 || rect.top > window.innerHeight - 30) {
                        p.classList.remove('is-open');
                        uMenu.style.display = 'none';
                    } else {
                        uMenu.style.top = (rect.bottom + 4) + 'px';
                        uMenu.style.left = rect.left + 'px';
                    }
                }
            });
        };
        window.addEventListener('scroll', updateFloatingDropdowns, true);
        window.addEventListener('resize', updateFloatingDropdowns);

        // Compact Delete Purchase Modal Handler
        const deleteModal = document.getElementById('deletePurchaseModal');
        const deleteForm = document.getElementById('deletePurchaseForm');
        const modalBillRef = document.getElementById('modalBillRef');
        const modalSupplierName = document.getElementById('modalSupplierName');
        const modalBillTotal = document.getElementById('modalBillTotal');
        const modalBillDate = document.getElementById('modalBillDate');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        const inventoryBaseUrl = '<?= defined("BASE_URL") ? BASE_URL : "" ?>';

        document.addEventListener('click', (e) => {
            const deleteBtn = e.target.closest('.btn-delete-purchase');
            if (deleteBtn) {
                e.preventDefault();
                e.stopPropagation();
                const id = deleteBtn.dataset.id;
                const supplier = deleteBtn.dataset.supplier || 'N/A';
                const total = deleteBtn.dataset.total || 'Rs 0.00';
                const date = deleteBtn.dataset.date || '';

                if (modalBillRef) modalBillRef.textContent = `Bill #${id}`;
                if (modalSupplierName) modalSupplierName.textContent = supplier;
                if (modalBillTotal) modalBillTotal.textContent = total;
                if (modalBillDate) modalBillDate.textContent = date;
                if (deleteForm) deleteForm.action = `${inventoryBaseUrl}/inventory/purchaseDelete/${id}`;

                deleteModal?.classList.add('is-open');
            }

            if (e.target === deleteModal || e.target === cancelDeleteBtn) {
                deleteModal?.classList.remove('is-open');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && deleteModal?.classList.contains('is-open')) {
                deleteModal.classList.remove('is-open');
            }
        });

        // Initialize with 1 empty row or pre-selected product
        const urlParams = new URLSearchParams(window.location.search);
        const urlProductId = urlParams.get('product_id');
        if (urlProductId) {
            const foundProduct = allProductsData.find(p => p.id === Number(urlProductId));
            if (foundProduct) {
                addPurchaseRow(foundProduct);
                setTab('purchase_add');
            } else {
                addPurchaseRow();
            }
        } else {
            addPurchaseRow();
        }

        const csvDropzone = pageContainer.querySelector('#inventory-csv-dropzone');
        const csvInput = pageContainer.querySelector('#inventory-csv-file');
        const csvFileName = pageContainer.querySelector('#inventory-csv-file-name');
        const csvPreviewBox = pageContainer.querySelector('#csv-live-preview-box');
        const csvPreviewStats = pageContainer.querySelector('#csv-preview-stats');
        const csvTheadRow = pageContainer.querySelector('#csv-preview-thead-row');
        const csvTbody = pageContainer.querySelector('#csv-preview-tbody');

        const parseCSVText = (text) => {
            const lines = text.split(/\r\n|\n/).filter(line => line.trim() !== '');
            if (lines.length === 0) return { headers: [], rows: [] };
            
            const parseLine = (line) => {
                const result = [];
                let cur = '';
                let inQuote = false;
                for (let i = 0; i < line.length; i++) {
                    const c = line[i];
                    if (c === '"') {
                        inQuote = !inQuote;
                    } else if (c === ',' && !inQuote) {
                        result.push(cur.trim().replace(/^"|"$/g, ''));
                        cur = '';
                    } else {
                        cur += c;
                    }
                }
                result.push(cur.trim().replace(/^"|"$/g, ''));
                return result;
            };

            const headers = parseLine(lines[0]);
            const rows = lines.slice(1).map(parseLine);
            return { headers, rows };
        };

        const renderCsvPreview = (file) => {
            if (!file || !csvPreviewBox) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                const text = e.target.result || '';
                const { headers, rows } = parseCSVText(text);
                if (headers.length === 0) {
                    csvPreviewBox.style.display = 'none';
                    return;
                }

                if (csvPreviewStats) {
                    csvPreviewStats.textContent = `${rows.length} Total Products Found`;
                }

                if (csvTheadRow) {
                    csvTheadRow.innerHTML = headers.map(h => `<th style="padding:8px 10px; font-weight:800; color:#334155; text-align:left; border-bottom:1px solid #e2e8f0;">${h}</th>`).join('');
                }

                if (csvTbody) {
                    const previewRows = rows.slice(0, 5);
                    const barcodeIdx = headers.findIndex(h => /barcode|code|upc|ean/i.test(h));

                    csvTbody.innerHTML = previewRows.map(row => {
                        return '<tr style="border-bottom:1px solid #f1f5f9;">' + headers.map((h, i) => {
                            let val = row[i] !== undefined ? row[i] : '';
                            if (i === barcodeIdx && (!val || val.trim() === '')) {
                                val = '<span style="font-size:10px; background:#eff6ff; color:#0f6dff; font-weight:800; padding:2px 6px; border-radius:6px; border:1px solid #bfdbfe;">⚡ Auto-Generated</span>';
                            }
                            return `<td style="padding:7px 10px; color:#475569;">${val}</td>`;
                        }).join('') + '</tr>';
                    }).join('');
                }

                csvPreviewBox.style.display = 'block';
            };
            reader.readAsText(file.slice(0, 64 * 1024)); // Read first 64KB for instant snappy preview
        };

        const setCsvFile = (file) => {
            if (!file || !csvFileName) {
                return;
            }
            const label = csvFileName.querySelector('span:last-child');
            if (label) {
                label.textContent = file.name;
            }
            csvFileName.style.display = 'inline-flex';
            renderCsvPreview(file);
        };

        if (csvDropzone && csvInput) {
            csvInput.addEventListener('change', () => setCsvFile(csvInput.files?.[0]));
            ['dragenter', 'dragover'].forEach((eventName) => {
                csvDropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    csvDropzone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'drop'].forEach((eventName) => {
                csvDropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    csvDropzone.classList.remove('is-dragover');
                });
            });
            csvDropzone.addEventListener('drop', (event) => {
                const file = event.dataTransfer?.files?.[0];
                if (!file) {
                    return;
                }
                const transfer = new DataTransfer();
                transfer.items.add(file);
                csvInput.files = transfer.files;
                setCsvFile(file);
            });
        }

        const productImageDropzone = pageContainer.querySelector('[data-product-image-dropzone]');
        const productImageInput = pageContainer.querySelector('[data-product-image-input]');
        const productImagePreview = pageContainer.querySelector('[data-product-image-preview]');
        const productCameraModal = pageContainer.querySelector('[data-product-camera-modal]');
        const productCameraVideo = pageContainer.querySelector('[data-product-camera-video]');
        const productCameraButton = pageContainer.querySelector('[data-product-image-camera]');
        const productBrowseButton = pageContainer.querySelector('[data-product-image-browse]');
        const productCaptureButton = pageContainer.querySelector('[data-product-camera-capture]');

        window.__inventoryProductCameraStream = null;
        let productImageSetting = false;


        const productImageActionsHtml = `
            <div class="product-image-actions">
                <button class="product-image-action" type="button" data-product-image-browse><span class="material-symbols-rounded">upload</span>Upload</button>
                <button class="product-image-action" type="button" data-product-image-camera><span class="material-symbols-rounded">photo_camera</span>Camera</button>
            </div>
        `;
        const emptyProductImagePreviewHtml = `
            <div class="product-image-preview-box"><span class="material-symbols-rounded">add_photo_alternate</span></div>
            <strong>Product image</strong>
            <span class="form-hint">PNG, JPG or WEBP</span>
            ${productImageActionsHtml}
        `;

        const renderProductImagePreview = (file) => {
            if (!file || !productImagePreview) {
                return;
            }
            if (!file.type.startsWith('image/')) {
                window.appToast?.('Please choose a valid image file.', 'error');
                return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                productImagePreview.innerHTML = `
                    <div class="product-image-preview-box">
                        <img src="${reader.result}" alt="Product preview">
                    </div>
                    <button class="product-image-remove" type="button" data-product-image-remove aria-label="Remove product image">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                    <strong>Product image</strong>
                    <span class="form-hint">PNG, JPG or WEBP</span>
                    ${productImageActionsHtml}
                `;
            };
            reader.readAsDataURL(file);
        };

        const clearProductImage = () => {
            if (productImageInput) {
                productImageInput.value = '';
            }
            if (productImagePreview) {
                productImagePreview.innerHTML = emptyProductImagePreviewHtml;
            }
        };

        const optimizeSquareImage = (sourceFile, fileName = 'product-image.webp') => new Promise((resolve, reject) => {
            if (!sourceFile || !sourceFile.type.startsWith('image/')) {
                reject(new Error('Invalid image'));
                return;
            }
            const img = new Image();
            img.onload = async () => {
                const size = Math.min(img.naturalWidth || img.width, img.naturalHeight || img.height);
                const sx = Math.max(0, ((img.naturalWidth || img.width) - size) / 2);
                const sy = Math.max(0, ((img.naturalHeight || img.height) - size) / 2);
                const canvas = document.createElement('canvas');
                const target = 900;
                canvas.width = target;
                canvas.height = target;
                const ctx = canvas.getContext('2d', { alpha: false });
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, target, target);
                ctx.drawImage(img, sx, sy, size, size, 0, 0, target, target);
                const type = canvas.toDataURL('image/webp').startsWith('data:image/webp') ? 'image/webp' : 'image/jpeg';
                canvas.toBlob((blob) => {
                    URL.revokeObjectURL(img.src);
                    if (!blob) {
                        reject(new Error('Image optimization failed'));
                        return;
                    }
                    resolve(new File([blob], fileName.replace(/\.\w+$/, '') + (type === 'image/webp' ? '.webp' : '.jpg'), { type, lastModified: Date.now() }));
                }, type, .86);
            };
            img.onerror = () => reject(new Error('Image could not be loaded'));
            img.src = URL.createObjectURL(sourceFile);
        });

        const setProductImageFile = async (file) => {
            try {
                const optimized = await optimizeSquareImage(file);
                const transfer = new DataTransfer();
                transfer.items.add(optimized);
                productImageSetting = true;
                productImageInput.files = transfer.files;
                productImageSetting = false;
                renderProductImagePreview(optimized);
            } catch (error) {
                productImageSetting = false;
                try {
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    productImageSetting = true;
                    productImageInput.files = transfer.files;
                    productImageSetting = false;
                    renderProductImagePreview(file);
                    window.appToast?.('Image captured. Optimization was skipped on this device.', 'success');
                } catch (fallbackError) {
                    productImageSetting = false;
                    window.appToast?.('Product image could not be saved. Try Upload instead.', 'error');
                }
            }
        };

        const stopProductCamera = () => {
            if (window.__inventoryProductCameraStream) {
                window.__inventoryProductCameraStream.getTracks().forEach((track) => track.stop());
                window.__inventoryProductCameraStream = null;
            }
            if (productCameraVideo) productCameraVideo.srcObject = null;
            productCameraModal?.classList.remove('open');
            productCameraModal?.setAttribute('aria-hidden', 'true');
        };

        const openProductCamera = async () => {
            if (!productCameraModal || !productCameraVideo) return;
            const isLocalHost = ['localhost', '127.0.0.1', '[::1]'].includes(window.location.hostname);
            if (!navigator.mediaDevices?.getUserMedia) {
                window.appToast?.('Camera is not supported in this browser. Use Upload instead.', 'error');
                return;
            }
            if (!window.isSecureContext && !isLocalHost) {
                window.appToast?.('Camera needs HTTPS or localhost. Open this panel on localhost to use camera.', 'error');
                return;
            }

            try {
                productCameraModal.classList.add('open');
                productCameraModal.setAttribute('aria-hidden', 'false');
                productCameraVideo.muted = true;
                productCameraVideo.playsInline = true;

                const cameraOptions = [
                    { video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 1280 } }, audio: false },
                    { video: { width: { ideal: 1280 }, height: { ideal: 1280 } }, audio: false },
                    { video: true, audio: false }
                ];

                let lastError = null;
                for (const constraints of cameraOptions) {
                    try {
                        window.__inventoryProductCameraStream = await navigator.mediaDevices.getUserMedia(constraints);
                        break;
                    } catch (error) {
                        lastError = error;
                    }
                }
                if (!window.__inventoryProductCameraStream) {
                    throw lastError || new Error('Camera unavailable');
                }

                productCameraVideo.srcObject = window.__inventoryProductCameraStream;
                await productCameraVideo.play();
            } catch (error) {
                stopProductCamera();
                const denied = ['NotAllowedError', 'SecurityError', 'PermissionDeniedError'].includes(error?.name || '');
                window.appToast?.(denied ? 'Camera permission blocked. Allow camera access and try again.' : 'Camera unavailable on this device. Use Upload instead.', 'error');
            }
        };

        pageContainer.addEventListener('click', (event) => {
            if (event.target.closest('[data-product-image-remove]')) {
                event.preventDefault();
                clearProductImage();
                return;
            }
            if (event.target.closest('[data-product-image-browse]')) {
                event.preventDefault();
                productImageInput?.click();
            }
            if (event.target.closest('[data-product-image-camera]')) {
                event.preventDefault();
                openProductCamera();
            }
            if (event.target.closest('[data-product-camera-close]')) {
                event.preventDefault();
                stopProductCamera();
            }
        });

        if (productImageDropzone && productImageInput) {
            productImageDropzone.addEventListener('click', (event) => {
                if (event.target.closest('[data-product-image-camera], [data-product-image-browse], [data-product-image-remove]')) return;
                productImageInput.click();
            });
            productCaptureButton?.addEventListener('click', async () => {
                if (!productCameraVideo?.videoWidth) return;
                const canvas = document.createElement('canvas');
                const side = Math.min(productCameraVideo.videoWidth, productCameraVideo.videoHeight);
                canvas.width = 900;
                canvas.height = 900;
                const ctx = canvas.getContext('2d', { alpha: false });
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(productCameraVideo, (productCameraVideo.videoWidth - side) / 2, (productCameraVideo.videoHeight - side) / 2, side, side, 0, 0, 900, 900);
                const type = canvas.toDataURL('image/webp').startsWith('data:image/webp') ? 'image/webp' : 'image/jpeg';
                canvas.toBlob(async (blob) => {
                    if (!blob) {
                        window.appToast?.('Camera image could not be captured.', 'error');
                        return;
                    }
                    const file = new File([blob], type === 'image/webp' ? 'product-camera.webp' : 'product-camera.jpg', { type, lastModified: Date.now() });
                    await setProductImageFile(file);
                    stopProductCamera();
                }, type, .86);
            });
            productImageInput.addEventListener('change', () => {
                if (productImageSetting) return;
                const file = productImageInput.files?.[0];
                if (file) setProductImageFile(file);
            });
            ['dragenter', 'dragover'].forEach((eventName) => {
                productImageDropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    productImageDropzone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'drop'].forEach((eventName) => {
                productImageDropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    productImageDropzone.classList.remove('is-dragover');
                });
            });
            productImageDropzone.addEventListener('drop', (event) => {
                const file = event.dataTransfer?.files?.[0];
                if (!file) {
                    return;
                }
                setProductImageFile(file);
            });
        }



        pageContainer.addEventListener('click', (event) => {
            if (event.target.closest('[data-product-image-remove]')) {
                event.preventDefault();
                clearProductImage();
                return;
            }
            if (event.target.closest('[data-product-image-browse]')) {
                event.preventDefault();
                productImageInput?.click();
            }
            if (event.target.closest('[data-product-image-camera]')) {
                event.preventDefault();
                openProductCamera();
            }
            if (event.target.closest('[data-product-camera-close]')) {
                event.preventDefault();
                stopProductCamera();
            }
        });

        // Live refresh logic for list view
        const inventoryForm = document.getElementById('inventory-filter-form');
        if (!inventoryForm || typeof window.appLoadPage !== 'function') {
            return;
        }

        if (window.__inventoryRefreshTimer) {
            window.clearInterval(window.__inventoryRefreshTimer);
            window.__inventoryRefreshTimer = null;
        }

        const refreshInventoryList = () => {
            if (document.hidden) {
                return;
            }
            const onInventoryPage = window.location.href.includes('url=inventory/index');
            // Check if we are currently looking at the list tab to avoid disruptive updates while filling out form
            const isListActive = document.querySelector('[data-inventory-pane="list"]').classList.contains('active');

            if (!onInventoryPage || !isListActive) {
                if (!onInventoryPage && window.__inventoryRefreshTimer) {
                    window.clearInterval(window.__inventoryRefreshTimer);
                    window.__inventoryRefreshTimer = null;
                }
                return;
            }
            window.appLoadPage(`/?${new URLSearchParams(new FormData(inventoryForm)).toString()}`);
        };

        window.__inventoryRefreshTimer = window.setInterval(refreshInventoryList, 20000);
        window.addEventListener('beforeunload', () => {
            if (window.__inventoryRefreshTimer) {
                window.clearInterval(window.__inventoryRefreshTimer);
                window.__inventoryRefreshTimer = null;
            }
        }, { once: true });

        window.toggleAddExpiryFields = function(enabled) {
            const container = document.getElementById('add_expiry_fields_container');
            const statusText = document.getElementById('toggle_expiry_status_text');
            const batchInput = document.getElementById('add_batch_number');
            const expiryInput = document.getElementById('add_expiry_date');
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

        // --- BARCODE ECOSYSTEM FUNCTIONS ---
        let barcodeDebounceTimer = null;

        window.generateAddBarcode = async function() {
            try {
                const res = await fetch('<?= defined("BASE_URL") ? BASE_URL : "" ?>/inventory/generateBarcode');
                const data = await res.json();
                if (data && data.success && data.barcode) {
                    const input = document.getElementById('add_barcode_input');
                    if (input) {
                        input.value = data.barcode;
                        window.onAddBarcodeInput(data.barcode);
                        window.appToast?.('Unique Barcode generated!', 'success');
                    }
                }
            } catch(e) {
                console.error("Barcode generation error:", e);
            }
        };

        window.onAddBarcodeInput = function(val) {
            const raw = (val || '').trim();
            const container = document.getElementById('add_barcode_live_container');
            const svgEl = document.getElementById('add_barcode_live_svg');
            const statusEl = document.getElementById('add_barcode_check_status');

            if (!container || !svgEl) return;

            if (raw === '') {
                container.style.display = 'none';
                if (statusEl) statusEl.innerHTML = '';
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
                } catch(e) {
                    console.warn("JsBarcode error:", e);
                }
            }

            // Debounced duplicate check
            if (barcodeDebounceTimer) clearTimeout(barcodeDebounceTimer);
            barcodeDebounceTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`<?= defined("BASE_URL") ? BASE_URL : "" ?>/inventory/checkBarcode?barcode=${encodeURIComponent(raw)}`);
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
                                        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/edit/${data.product.id}" target="_blank" class="badge-edit-link">Edit &rarr;</a>
                                    </div>
                                `;
                            }
                        } else {
                            if (statusEl) {
                                statusEl.innerHTML = `
                                    <div class="barcode-feedback-badge badge-success">
                                        <svg viewBox="0 0 20 20" fill="currentColor" class="badge-icon"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                                        <span>Unique Barcode • Ready to add product</span>
                                    </div>
                                `;
                            }
                            // Auto focus product name if empty
                            const nameInput = document.getElementById('add_product_name_input');
                            if (nameInput && nameInput.value.trim() === '') {
                                nameInput.focus();
                            }
                        }
                    }
                } catch(e) {
                    console.error("Barcode check error:", e);
                }
            }, 300);
        };

        const addBarcodeInputEl = document.getElementById('add_barcode_input');
        if (addBarcodeInputEl) {
            addBarcodeInputEl.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    e.stopPropagation();
                    const nameInput = document.getElementById('add_product_name_input');
                    if (nameInput) {
                        nameInput.focus();
                        nameInput.select?.();
                    }
                }
            });
        }

        // --- CAMERA SCANNER MODAL ---
        window.openBarcodeCameraModal = async function(targetInputId) {
            // Stop any active streams safely first
            if (typeof window.stopAllActiveCameraStreams === 'function') {
                window.stopAllActiveCameraStreams();
            }

            const modalRoot = document.getElementById('app-modal-root') || document.body;

            const modalHtml = `
                <div class="inventory-barcode-scanner-modal open" id="inventory-barcode-modal-container">
                    <div class="inventory-scanner-card">
                        <div class="inventory-scanner-head">
                            <div class="inventory-scanner-head-info">
                                <div class="inventory-scanner-head-icon">
                                    <span class="material-symbols-rounded">photo_camera</span>
                                </div>
                                <div class="inventory-scanner-head-text">
                                    <strong>Camera Barcode Scanner</strong>
                                    <span>Align product barcode inside frame</span>
                                </div>
                            </div>
                            <button class="inventory-scanner-close" type="button" onclick="closeBarcodeCameraModal()" aria-label="Close scanner">
                                <span class="material-symbols-rounded">close</span>
                            </button>
                        </div>
                        <div class="inventory-scanner-stage">
                            <video id="inv-barcode-cam-video" autoplay muted playsinline></video>
                            <div class="inventory-scanner-reticle">
                                <span class="inv-reticle-corner tl"></span>
                                <span class="inv-reticle-corner tr"></span>
                                <span class="inv-reticle-corner bl"></span>
                                <span class="inv-reticle-corner br"></span>
                                <div class="inv-reticle-laser"></div>
                                <div class="inv-reticle-caption">Center barcode inside frame</div>
                            </div>
                        </div>
                        <div class="inventory-scanner-foot">
                            <div class="inv-camera-status-pill">
                                <span class="inv-camera-status-dot"></span>
                                <span id="inv-barcode-cam-status">Camera ready • Scanning...</span>
                            </div>
                            <button type="button" class="inventory-scanner-cancel" onclick="closeBarcodeCameraModal()">Cancel</button>
                        </div>
                    </div>
                </div>
            `;

            modalRoot.insertAdjacentHTML('beforeend', modalHtml);

            const videoEl = document.getElementById('inv-barcode-cam-video');
            const statusEl = document.getElementById('inv-barcode-cam-status');
            let isScanned = false;

            const onDetected = (detectedCode) => {
                if (isScanned) return;
                isScanned = true;
                const cleanCode = String(detectedCode || '').trim();
                if (!cleanCode) return;

                if (typeof playSuccessBeep === 'function') {
                    playSuccessBeep();
                }
                const targetInput = document.getElementById(targetInputId);
                if (targetInput) {
                    targetInput.value = cleanCode;
                    if (typeof window.onAddBarcodeInput === 'function') {
                        window.onAddBarcodeInput(cleanCode);
                    }
                    targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                window.appToast?.(`Barcode Scanned: ${cleanCode}`, 'success');
                closeBarcodeCameraModal();
            };

            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                window.__activeInventoryCameraStream = stream;
                if (videoEl) {
                    videoEl.srcObject = stream;
                    await videoEl.play().catch(() => {});
                }

                // 1. Native BarcodeDetector (fastest)
                if ('BarcodeDetector' in window) {
                    try {
                        const detector = new BarcodeDetector({
                            formats: ['code_128', 'ean_13', 'ean_8', 'qr_code', 'upc_a', 'upc_e', 'code_39']
                        });
                        const scanLoop = async () => {
                            if (!window.__activeInventoryCameraStream || isScanned) return;
                            try {
                                if (videoEl && videoEl.readyState >= 2) {
                                    const barcodes = await detector.detect(videoEl);
                                    if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
                                        onDetected(barcodes[0].rawValue);
                                        return;
                                    }
                                }
                            } catch(e) {}
                            requestAnimationFrame(scanLoop);
                        };
                        requestAnimationFrame(scanLoop);
                        return;
                    } catch(e) {}
                }

                // 2. ZXing fallback
                if (window.ZXing && videoEl) {
                    try {
                        const codeReader = new ZXing.BrowserMultiFormatReader();
                        window.__inventoryZxingReader = codeReader;
                        codeReader.decodeFromVideoElement(videoEl, (result, err) => {
                            if (result && result.getText() && !isScanned) {
                                onDetected(result.getText());
                            }
                        });
                    } catch(e) {}
                }
            } catch(err) {
                console.warn("Camera access error:", err);
                if (statusEl) statusEl.textContent = 'Camera busy, permission denied or unavailable';
            }
        };

        window.closeBarcodeCameraModal = function() {
            if (typeof window.stopAllActiveCameraStreams === 'function') {
                window.stopAllActiveCameraStreams();
            } else {
                const m = document.getElementById('inventory-barcode-modal-container');
                if (m) m.remove();
            }
        };

        // Synthesized Audio Beep for Scans
        function playSuccessBeep() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(1400, ctx.currentTime);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.08);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.09);
                setTimeout(() => ctx.close(), 120);
            } catch(e) {}
        }

        // --- WIRELESS MOBILE SCANNER REAL-TIME SYNC FOR INVENTORY ---
        // Mobile scanner polling removed for Desktop standalone edition

        window.openQuickStockModal = (id, name, currentStock) => {
            window.appModal.open({
                title: 'Quick Stock Adjustment',
                body: `
                    <div style="margin-bottom:12px;"><strong>Product:</strong> ${name}</div>
                    <div style="margin-bottom:12px;"><strong>Current Stock:</strong> ${currentStock}</div>
                    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/quickStock/${id}" method="POST" id="quick-stock-form">
                        <?php echo Security::csrfField(); ?>
                        <div class="split-grid">
                            <div class="form-group">
                                <label class="form-label">Action</label>
                                <select class="form-select" name="adjustment_type" required>
                                    <option value="add">Add Stock</option>
                                    <option value="deduct">Deduct Stock</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Quantity</label>
                                <input class="form-input" type="number" name="adjustment_qty" min="1" value="1" required>
                            </div>
                        </div>
                        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                            <button class="btn btn-secondary" type="button" data-modal-close>Cancel</button>
                            <button class="btn btn-primary" type="submit">Update Stock</button>
                        </div>
                    </form>
                `
            });
        };
    })();
</script>
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/JsBarcode.all.min.js"></script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>

