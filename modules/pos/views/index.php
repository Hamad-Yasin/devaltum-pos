<?php ob_start(); ?>
<?php
$showProductImages = App::setting('show_product_images', '1') === '1';
$posCardLayout = App::setting('pos_card_layout', 'standard');
$posImageFit = App::setting('pos_image_fit', 'cover');
$posShowName = App::setting('pos_show_product_name', '1') === '1';
$posShowStock = App::setting('pos_show_product_stock', '1') === '1';
$posShowUnit = App::setting('pos_show_product_unit', '1') === '1';
$posShowPrice = App::setting('pos_show_product_price', '1') === '1';
$productImageFields = ['image_url', 'image_path', 'product_image', 'photo', 'thumbnail'];
$resolveProductImage = static function ($product) use ($productImageFields) {
    foreach ($productImageFields as $field) {
        $value = trim((string) ($product[$field] ?? ''));
        if ($value !== '') {
            if (!App::mediaExists($value)) {
                continue;
            }
            return App::mediaUrl($value);
        }
    }
    return '';
};
$formatProductUnit = static function ($product) {
    $value = isset($product['unit_value']) && (float) $product['unit_value'] > 0 ? (float) $product['unit_value'] : 1;
    $unit = trim((string) ($product['unit_type'] ?? ''));
    if ($unit === '') {
        $unit = 'PCS';
    }
    $valueText = rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    return $valueText . ' ' . strtoupper($unit);
};
$storePairCode = App::getStoreScannerPairCode();
$categoriesList = $categories ?? [];
?>

<div class="pos-top-header">
    <div class="pos-top-left">
        <div class="pos-app-brand">
            <div class="pos-brand-icon-box">
                <span class="material-symbols-rounded">point_of_sale</span>
            </div>
            <div class="pos-brand-text">
                <div class="pos-brand-title-row">
                    <h1 class="pos-main-title">POS Checkout</h1>
                    <span class="pos-mode-badge <?php echo ($_GET['mode'] ?? '') === 'return' ? 'mode-return' : 'mode-sale'; ?>">
                        <span class="material-symbols-rounded" style="font-size:13px;"><?php echo ($_GET['mode'] ?? '') === 'return' ? 'assignment_return' : 'bolt'; ?></span>
                        <?php echo ($_GET['mode'] ?? '') === 'return' ? 'Return Mode' : 'Sale Terminal'; ?>
                    </span>
                </div>
                <div class="pos-meta-pills">
                    <span class="pos-cashier-pill" title="Current Active Cashier">
                        <span class="pos-status-pulse"></span>
                        <span class="material-symbols-rounded" style="font-size:14px; color:#0f6dff;">person</span>
                        <strong><?php echo Security::escape(Auth::user()['name'] ?? 'Cashier'); ?></strong>
                    </span>
                    <span class="pos-shortcut-hints-bar">
                        <span class="pos-key-chip"><kbd>F4</kbd> Search</span>
                        <span class="pos-key-chip"><kbd>F2</kbd> / <kbd>Ctrl+↵</kbd> Quick Pay</span>
                        <span class="pos-key-chip"><kbd>F9</kbd> / <kbd>Alt+P</kbd> Print</span>
                        <span class="pos-key-chip"><kbd>F10</kbd> / <kbd>Alt+H</kbd> Hold</span>
                        <span class="pos-key-chip"><kbd>F8</kbd> / <kbd>Alt+R</kbd> Recall</span>
                        <span class="pos-key-chip"><kbd>F6</kbd> / <kbd>Ctrl+M</kbd> Margin</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<iframe id="pos-direct-print-frame" style="display:none; position:fixed; right:-1000px; bottom:-1000px; width:0; height:0; border:none;" src="about:blank"></iframe>

<style>
/* ==========================================================================
   STATE-OF-THE-ART COMPACT SAAS POS DESIGN SYSTEM
   ========================================================================== */
:root {
    --pos-primary: #0f6dff;
    --pos-primary-hover: #0256d0;
    --pos-success: #10b981;
    --pos-success-hover: #059669;
    --pos-border: #e2e8f0;
    --pos-border-focus: #93c5fd;
    --pos-bg-card: #ffffff;
    --pos-bg-subtle: #f8fafc;
    --pos-text-main: #0f172a;
    --pos-text-muted: #64748b;
    --pos-radius-sm: 8px;
    --pos-radius-md: 12px;
    --pos-radius-lg: 16px;
    --pos-shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.05);
    --pos-shadow-card: 0 4px 14px rgba(15, 23, 42, 0.035);
    --pos-font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

/* Base Top Header Bar */
.pos-top-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 12px;
    padding: 10px 14px;
    background: #ffffff;
    border: 1px solid var(--pos-border);
    border-radius: var(--pos-radius-md);
    box-shadow: var(--pos-shadow-sm);
}

.pos-top-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.pos-app-brand {
    display: flex;
    align-items: center;
    gap: 10px;
}

.pos-brand-icon-box {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    color: var(--pos-primary);
    display: grid;
    place-items: center;
    flex-shrink: 0;
    box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.8);
}

.pos-brand-icon-box .material-symbols-rounded {
    font-size: 22px;
}

.pos-brand-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pos-main-title {
    font-size: 16.5px;
    font-weight: 850;
    color: var(--pos-text-main);
    margin: 0;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.pos-mode-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 7px;
    border-radius: 99px;
    font-size: 10.5px;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.pos-mode-badge.mode-sale {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.pos-mode-badge.mode-return {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.pos-meta-pills {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 3px;
    flex-wrap: wrap;
}

.pos-cashier-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 8px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #334155;
    font-size: 11.5px;
    font-weight: 700;
}

.pos-status-pulse {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
}

.pos-shortcut-hints-bar {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.pos-key-chip {
    font-size: 10.5px;
    font-weight: 750;
    color: var(--pos-text-muted);
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 2px 6px;
    border-radius: 5px;
}

.pos-key-chip kbd {
    background: #e2e8f0;
    color: #1e293b;
    padding: 1px 4px;
    border-radius: 3px;
    font-family: inherit;
    font-size: 9.5px;
    font-weight: 800;
}

/* Top Action Buttons (Equalized, Balanced Heights) */
.pos-top-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pos-top-action-btn {
    height: 38px;
    padding: 0 16px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
    white-space: nowrap;
    border: none;
}

.pos-top-action-btn:active {
    transform: scale(0.97);
}

.pos-btn-quick-pay {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.26);
}

.pos-btn-quick-pay:hover {
    background: linear-gradient(135deg, #059669, #047857);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.32);
}

.pos-btn-std-checkout {
    background: #ffffff;
    color: #1e293b;
    border: 1.5px solid var(--pos-border);
}

.pos-btn-std-checkout:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.pos-action-kbd {
    display: inline-block;
    padding: 1px 5px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 900;
    background: rgba(0, 0, 0, 0.12);
    color: inherit;
    letter-spacing: 0.5px;
}

.pos-btn-std-checkout .pos-action-kbd {
    background: #e2e8f0;
    color: #475569;
}

/* ==========================================================================
   TOP COMMAND STRIP (CUSTOMER & CONTROLS GRID)
   ========================================================================== */
/* ==========================================================================
   TOP COMMAND STRIP (PIXEL-PERFECT INLINE SPREAD & SOLID ACTIVE BUTTONS)
   ========================================================================== */
.pos-command-center {
    background: #ffffff;
    border: 1px solid var(--pos-border);
    border-radius: var(--pos-radius-md);
    box-shadow: var(--pos-shadow-card);
    padding: 5px 12px;
    margin-bottom: 8px;
}

.pos-single-command-strip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px 12px;
    width: 100%;
    flex-wrap: nowrap;
}

.pos-strip-customer-group {
    display: flex;
    align-items: center;
    min-width: 0;
    flex-shrink: 0;
}

.pos-strip-controls-group {
    display: flex;
    align-items: center;
    gap: 6px 10px;
    flex-wrap: nowrap;
    flex-shrink: 0;
    margin-left: auto;
    justify-content: flex-end;
}

.pos-strip-customer {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    flex-shrink: 0;
    cursor: pointer;
}

.pos-strip-avatar {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #eff6ff;
    color: var(--pos-primary);
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border: 1px solid #dbeafe;
}

.pos-strip-avatar .material-symbols-rounded {
    font-size: 17px;
}

.pos-strip-cust-text {
    min-width: 0;
}

.pos-strip-cust-name {
    display: flex;
    align-items: center;
    gap: 4px;
    line-height: 1.15;
}

.pos-strip-cust-name strong {
    font-size: 12px;
    font-weight: 850;
    color: var(--pos-text-main);
    white-space: nowrap;
}

.pos-badge-status {
    font-size: 9px;
    font-weight: 800;
    padding: 1px 5px;
    border-radius: 99px;
    background: #f1f5f9;
    color: var(--pos-text-muted);
    border: 1px solid #e2e8f0;
    white-space: nowrap;
}

.pos-customer-sub {
    font-size: 9.5px;
    color: var(--pos-text-muted);
    font-weight: 650;
    white-space: nowrap;
    display: block;
    margin-top: 0px;
    line-height: 1.1;
}

.pos-btn-compact-secondary {
    height: 23px;
    padding: 0 7px;
    font-size: 10.5px;
    font-weight: 750;
    border-radius: var(--pos-radius-sm);
    display: inline-flex;
    align-items: center;
    gap: 3px;
    white-space: nowrap;
    background: #f8fafc;
    color: #334155;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
    margin-left: 5px;
}

.pos-btn-compact-secondary:hover {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #93c5fd;
}

/* Identical Vertical Dividers */
.pos-strip-divider {
    width: 1px;
    height: 18px;
    background: #e2e8f0;
    flex-shrink: 0;
    align-self: center;
    margin: 0 1px;
}

/* Symmetrical Control Blocks with Unified Label + Pill Stack */
.pos-strip-block {
    display: flex;
    flex-direction: column;
    gap: 1.5px;
    flex-shrink: 0;
    justify-content: center;
}

.pos-strip-label {
    font-size: 8.5px;
    font-weight: 800;
    color: var(--pos-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    line-height: 1;
}

.pill-group {
    display: flex;
    background: #f1f5f9;
    padding: 2px;
    border-radius: var(--pos-radius-sm);
    gap: 2px;
    height: 24px;
    box-sizing: border-box;
    align-items: center;
}

.pill-btn {
    height: 20px;
    padding: 0 7px;
    border-radius: 4px;
    border: none;
    background: transparent;
    color: #475569;
    font-size: 10.5px;
    font-weight: 750;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.pill-btn:hover {
    color: #0f172a;
    background: #e2e8f0;
}

/* SOLID COLOR BACKGROUND WITH PURE WHITE TEXT (RETAIL / WHOLESALE / CASH / ONLINE / CREDIT) */
.pill-btn.active,
.pos-pill-opt.active,
.payment-method-btn.active {
    background: #0f6dff !important;
    color: #ffffff !important;
    font-weight: 850 !important;
    box-shadow: 0 2px 6px rgba(15, 109, 255, 0.35) !important;
}

.pill-btn.active:hover,
.pos-pill-opt.active:hover,
.payment-method-btn.active:hover {
    background: #0256d0 !important;
    color: #ffffff !important;
}

/* Live Stats Summary at the End - EXACT SAME HEIGHT & 100% INLINE */
.pos-stats-summary-inline {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border: 1px solid #e2e8f0;
    border-radius: var(--pos-radius-sm);
    padding: 0 7px;
    height: 24px;
    box-sizing: border-box;
}

.stat-box-inline {
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.stat-label-inline {
    font-size: 8.5px;
    font-weight: 800;
    color: var(--pos-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.stat-val-inline {
    font-size: 11px;
    font-weight: 850;
    color: var(--pos-text-main);
    line-height: 1;
}

.stat-val-inline.stat-amount-highlight {
    color: var(--pos-primary);
    font-weight: 900;
    font-size: 12.5px;
}

.stat-sep-inline {
    color: #cbd5e1;
    font-size: 11px;
    user-select: none;
}

.pos-customer-collapsible {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
}

.customer-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.customer-form-grid .full-width {
    grid-column: 1 / -1;
}

.pos-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.pos-input-group .material-symbols-rounded {
    position: absolute;
    left: 8px;
    font-size: 16px;
    color: #94a3b8;
    pointer-events: none;
}

.pos-input-group .form-input {
    width: 100%;
    height: 32px;
    padding-left: 30px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 7px;
    border: 1px solid #cbd5e1;
    transition: all 0.15s ease;
}

.pos-input-group .form-input:focus {
    border-color: var(--pos-primary);
    box-shadow: 0 0 0 2px rgba(15, 109, 255, 0.12);
}

/* Drawer & Extra Controls */
.pos-credit-drawer {
    padding: 8px 10px;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: var(--pos-radius-sm);
}

/* Compact Professional Icon Buttons in Cart Totals */
.pos-discount-icon-btn,
.pos-margin-icon-btn {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    padding: 0;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.pos-discount-icon-btn:hover {
    background: #eff6ff;
    border-color: #3b82f6;
    color: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(59, 130, 246, 0.15);
}

.pos-margin-icon-btn {
    border-color: #a7f3d0;
    background: #f0fdf4;
    color: #059669;
}

.pos-margin-icon-btn:hover {
    background: #dcfce7;
    border-color: #10b981;
    color: #047857;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2);
}

.pos-discount-icon-btn .material-symbols-rounded,
.pos-margin-icon-btn .material-symbols-rounded {
    font-size: 15px;
}

/* ==========================================================================
   MAIN POS WORKSPACE GRID
   ========================================================================== */
.pos-layout {
    display: grid;
    grid-template-columns: 1.35fr 1fr;
    gap: 12px;
    align-items: flex-start;
}

/* LEFT COLUMN: PRODUCTS SHELL */
.products-shell {
    background: #ffffff;
    border: 1px solid var(--pos-border);
    border-radius: var(--pos-radius-md);
    box-shadow: var(--pos-shadow-card);
    display: flex;
    flex-direction: column;
}

.products-head {
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}

.products-head-info {
    display: flex;
    flex-direction: column;
}

.products-title-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
}

.pos-section-icon {
    font-size: 18px;
    color: var(--pos-primary);
}

.products-title {
    font-size: 13.5px;
    font-weight: 850;
    color: var(--pos-text-main);
    margin: 0;
}

.products-subtitle {
    font-size: 11px;
    color: var(--pos-text-muted);
    font-weight: 650;
}

.pos-btn-custom-product {
    height: 32px;
    padding: 0 12px;
    font-size: 11.5px;
    font-weight: 800;
    border-radius: var(--pos-radius-sm);
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #eff6ff;
    color: var(--pos-primary);
    border: 1px solid #bfdbfe;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.pos-btn-custom-product:hover {
    background: var(--pos-primary);
    color: #ffffff;
    border-color: var(--pos-primary);
}

.products-head-tools {
    display: flex;
    gap: 8px;
    align-items: center;
    padding: 8px 12px;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}

.pos-search-box-wrap {
    position: relative;
    flex: 1;
    display: flex;
    align-items: center;
}

.pos-search-ico {
    position: absolute;
    left: 10px;
    font-size: 18px;
    color: #94a3b8;
    pointer-events: none;
}

.pos-search-input {
    width: 100%;
    height: 36px;
    padding: 0 38px 0 34px !important;
    line-height: 34px !important;
    font-size: 12.5px;
    font-weight: 700;
    border-radius: var(--pos-radius-sm);
    border: 1px solid #cbd5e1;
    background: #ffffff;
    box-sizing: border-box;
}

.pos-search-input:focus {
    border-color: var(--pos-primary);
    box-shadow: 0 0 0 2px rgba(15, 109, 255, 0.15);
    outline: none;
}

.pos-search-kbd {
    position: absolute;
    right: 8px;
    font-size: 10px;
    font-weight: 800;
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    padding: 1px 5px;
    border-radius: 4px;
    pointer-events: none;
}

.pos-category-select-wrap {
    width: 160px;
    flex-shrink: 0;
    position: relative;
}

.pos-category-select {
    width: 100%;
    height: 36px;
    padding: 0 28px 0 12px !important;
    font-size: 12px;
    font-weight: 750;
    line-height: 34px !important;
    border-radius: var(--pos-radius-sm);
    border: 1px solid #cbd5e1;
    background-color: #ffffff !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 10px center !important;
    background-size: 14px !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    color: #0f172a;
    cursor: pointer;
    box-sizing: border-box;
    display: block;
}

.pos-category-select:focus {
    border-color: var(--pos-primary);
    box-shadow: 0 0 0 2px rgba(15, 109, 255, 0.15);
    outline: none;
}

/* Products Cards Grid */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
    gap: 10px;
    padding: 12px;
    align-content: start;
    min-height: 420px;
    max-height: calc(100vh - 270px);
    overflow-y: auto;
}

.product-card {
    position: relative;
    border: 1px solid var(--pos-border);
    border-radius: var(--pos-radius-sm);
    padding: 10px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 110px;
}

.product-card:hover {
    transform: translateY(-2px);
    border-color: var(--pos-primary);
    box-shadow: 0 6px 16px rgba(15, 109, 255, 0.1);
}

.product-card:active {
    transform: scale(0.98);
}

.product-card-main {
    display: flex;
    gap: 8px;
    align-items: flex-start;
}

.product-image-box {
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.product-image-box img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 2px;
    box-sizing: border-box;
    display: block;
}

.product-image-placeholder {
    color: var(--pos-primary);
}

.product-card-info {
    min-width: 0;
    flex: 1;
}

.product-card .stock-mini {
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

.product-card .stock-mini.low {
    background: #fff7ed;
    color: #c2410c;
}

.product-card .stock-mini.out {
    background: #fff1f2;
    color: #be123c;
}

.product-card h3 {
    font-size: 12px;
    font-weight: 750;
    color: var(--pos-text-main);
    margin: 2px 0 0 0;
    line-height: 1.25;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
}

.product-unit-caption {
    font-size: 10.5px;
    color: var(--pos-text-muted);
    font-weight: 650;
    margin-top: 2px;
}

.product-card-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
    padding-top: 6px;
    border-top: 1px dashed #f1f5f9;
}

.product-price-display {
    font-size: 13.5px;
    font-weight: 850;
    color: var(--pos-primary);
}

.product-add-btn {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: #eff6ff;
    color: var(--pos-primary);
    border: 1px solid #bfdbfe;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
}

.product-card:hover .product-add-btn {
    background: var(--pos-primary);
    color: #ffffff;
    border-color: var(--pos-primary);
}

/* 1:1 Visual Square Grid Layout */
.products-grid.layout-visual-1x1 {
    grid-template-columns: repeat(auto-fill, minmax(136px, 1fr));
    gap: 12px;
}
.products-grid.layout-visual-1x1 .product-card {
    aspect-ratio: 1 / 1;
    min-height: 0;
    padding: 0;
    overflow: hidden;
    display: block;
    position: relative;
    border-radius: 14px;
    border: 1px solid var(--pos-border);
    box-shadow: 0 2px 8px rgba(15,23,42,0.06);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.products-grid.layout-visual-1x1 .product-card:hover {
    transform: translateY(-3px) scale(1.02);
    border-color: var(--pos-primary);
    box-shadow: 0 10px 24px rgba(15,109,255,0.2);
}
.products-grid.layout-visual-1x1 .product-card-main {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    display: block;
    position: relative;
}
.products-grid.layout-visual-1x1 .product-image-box {
    width: 100%;
    height: 100%;
    flex: none;
    border-radius: 0;
    border: none;
    background: #ffffff;
    position: absolute;
    inset: 0;
}
.products-grid.layout-visual-1x1 .product-image-box img {
    width: 100%;
    height: 100%;
    display: block;
    transition: transform 0.25s ease;
}
.products-grid.layout-visual-1x1 .product-card:hover .product-image-box img {
    transform: scale(1.05);
}
.products-grid.layout-visual-1x1 .product-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8fafc, #eff6ff);
}
.products-grid.layout-visual-1x1 .product-card-info {
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
.products-grid.layout-visual-1x1 .product-card-info h3 {
    color: #ffffff;
    text-shadow: 0 1px 3px rgba(0,0,0,0.6);
    font-size: 11.5px;
    margin: 0;
}
.products-grid.layout-visual-1x1 .product-card-info .product-unit-caption {
    color: rgba(255,255,255,0.85);
    font-size: 9.5px;
}
.products-grid.layout-visual-1x1 .product-card-info .stock-mini {
    position: absolute;
    top: 8px;
    right: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}
.products-grid.layout-visual-1x1 .product-card-foot {
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
.products-grid.layout-visual-1x1 .product-price-display {
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
.products-grid.layout-visual-1x1 .product-add-btn {
    background: #0f6dff;
    color: #fff;
    border-color: #0f6dff;
    box-shadow: 0 2px 8px rgba(15,109,255,0.4);
}

.products-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    border-top: 1px solid #f1f5f9;
    background: #f8fafc;
    border-bottom-left-radius: var(--pos-radius-md);
    border-bottom-right-radius: var(--pos-radius-md);
}

/* ==========================================================================
   RIGHT COLUMN: CART & CHECKOUT ACTION CENTER
   ========================================================================== */
.cart-shell {
    background: #ffffff;
    border: 1px solid var(--pos-border);
    border-radius: var(--pos-radius-md);
    box-shadow: var(--pos-shadow-card);
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 12px;
}

.cart-head {
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 6px;
}

/* Segmented Tab Switcher */
.pos-tab-switcher {
    display: flex;
    background: #f1f5f9;
    border-radius: var(--pos-radius-sm);
    padding: 2px;
    gap: 2px;
}

.pos-tab-btn {
    height: 30px;
    padding: 0 10px;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: var(--pos-text-muted);
    font-size: 11.5px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.pos-tab-btn:hover {
    color: var(--pos-text-main);
}

.pos-tab-btn.active {
    background: #ffffff;
    color: var(--pos-text-main);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.pos-tab-badge {
    background: var(--pos-primary);
    color: #ffffff;
    font-size: 9.5px;
    font-weight: 900;
    padding: 1px 5px;
    border-radius: 99px;
}

.pos-tab-profit-pill {
    font-size: 9.5px;
    font-weight: 850;
    padding: 1px 5px;
    border-radius: 99px;
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

/* Equal Cart Utilities (Hold, Recall, Clear) */
.pos-cart-actions {
    display: flex;
    gap: 4px;
    align-items: center;
}

.pos-util-btn {
    height: 30px;
    padding: 0 8px;
    font-size: 11px;
    font-weight: 800;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.pos-btn-hold {
    background: #fff7ed;
    color: #c2410c;
    border-color: #ffedd5;
}

.pos-btn-hold:hover {
    background: #ffedd5;
}

.pos-btn-recall {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #dbeafe;
    position: relative;
}

.pos-btn-recall:hover {
    background: #dbeafe;
}

.pos-btn-clear {
    background: #fff1f2;
    color: #be123c;
    border-color: #ffe4e6;
    padding: 0 7px;
}

.pos-btn-clear:hover {
    background: #ffe4e6;
}

.held-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: #ffffff;
    font-size: 9px;
    font-weight: 900;
    padding: 1px 5px;
    border-radius: 999px;
}

/* Cart Items Table */
.cart-scroll-zone {
    min-height: 240px;
    max-height: calc(100vh - 430px);
    overflow-y: auto;
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
}

.cart-table th {
    background: #f8fafc;
    padding: 7px 10px;
    font-size: 10.5px;
    font-weight: 800;
    color: var(--pos-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-bottom: 1px solid var(--pos-border);
    position: sticky;
    top: 0;
    z-index: 1;
}

.cart-table td {
    padding: 8px 10px;
    font-size: 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.qty-btn {
    width: 24px;
    height: 24px;
    border-radius: 5px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    cursor: pointer;
    font-weight: 850;
    font-size: 13px;
    color: #334155;
    display: inline-grid;
    place-items: center;
    padding: 0;
    transition: all 0.15s ease;
}

.qty-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.qty-input {
    width: 44px;
    height: 24px;
    text-align: center;
    border: 1px solid #cbd5e1;
    border-radius: 5px;
    padding: 0;
    font-weight: 800;
    font-size: 12px;
}

.cart-remove-btn {
    width: 24px;
    height: 24px;
    border-radius: 5px;
    border: 1px solid #fecaca;
    background: #fff1f2;
    color: #be123c;
    display: inline-grid;
    place-items: center;
    cursor: pointer;
    padding: 0;
    transition: all 0.15s ease;
}

.cart-remove-btn:hover {
    background: #ffe4e6;
    transform: scale(1.05);
}

.cart-empty-illustration {
    padding: 36px 16px;
    text-align: center;
    color: #94a3b8;
}

/* Cart Totals Box */
.cart-total-box {
    padding: 12px 14px;
    border-top: 1px solid var(--pos-border);
    background: linear-gradient(180deg, #ffffff, #f8fbff);
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
    font-size: 12.5px;
    font-weight: 700;
}

.total-row-main {
    font-size: 18px;
    font-weight: 900;
    color: var(--pos-text-main);
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px dashed #cbd5e1;
}

.pos-footer-profit-strip {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f0fdf4;
    margin: 8px -14px -12px;
    padding: 7px 14px;
    border-radius: 0 0 var(--pos-radius-md) var(--pos-radius-md);
    border-top: 1px dashed #bbf7d0;
}

/* Equal Symmetrical Checkout Buttons */
.checkout-buttons {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 8px;
    padding: 10px 14px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    border-bottom-left-radius: var(--pos-radius-md);
    border-bottom-right-radius: var(--pos-radius-md);
}

.btn-primary-checkout {
    height: 40px;
    font-size: 13.5px;
    font-weight: 850;
    background: linear-gradient(135deg, #0f6dff, #0052cc);
    color: #ffffff;
    border: none;
    box-shadow: 0 4px 14px rgba(15, 109, 255, 0.25);
    border-radius: var(--pos-radius-sm);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-primary-checkout:hover {
    background: linear-gradient(135deg, #005ce6, #0047b3);
    box-shadow: 0 6px 18px rgba(15, 109, 255, 0.35);
}

.btn-primary-checkout:active {
    transform: scale(0.98);
}

.btn-secondary-checkout {
    height: 40px;
    font-weight: 800;
    font-size: 12px;
    border-radius: var(--pos-radius-sm);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-secondary-checkout:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #94a3b8;
}

.btn-secondary-checkout:active {
    transform: scale(0.98);
}

/* ==========================================================================
   RESPONSIVE LAYOUT FOR ALL DEVICES (MOBILE OPTIMIZED & ZERO OVERFLOW)
   ========================================================================== */
@media (max-width: 1200px) {
    .pos-layout {
        grid-template-columns: 1.2fr 1fr;
    }
}

/* 1. HIDE PHYSICAL KEYBOARD SHORTCUTS SPECIFICALLY ON MOBILE / TOUCH DEVICES */
@media (max-width: 900px) {
    .pos-shortcut-hints-bar,
    .pos-action-kbd,
    .pos-search-kbd {
        display: none !important;
    }
}

/* 2. TABLET & COMPACT SCREENS (<= 980px) */
@media (max-width: 980px) {
    .pos-layout {
        display: flex;
        flex-direction: column;
        grid-template-columns: 1fr;
        gap: 12px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        overflow-x: hidden;
        padding-bottom: 110px; /* Ample breathing room above mobile-bottom-nav */
    }
    .cart-shell {
        position: static;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    .products-shell {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    .products-grid {
        max-height: 440px;
    }
}

/* 3. MOBILE PHONES (<= 768px) - ULTRA POLISHED & ZERO HORIZONTAL OVERFLOW */
@media (max-width: 768px) {
    /* Container hygiene */
    .content-area {
        padding: 8px 8px 110px !important;
    }
    .pos-top-header,
    .pos-command-center,
    .products-shell,
    .cart-shell {
        max-width: 100% !important;
        box-sizing: border-box !important;
        overflow-x: hidden;
    }

    /* Top Brand Header */
    .pos-top-header {
        padding: 8px 10px;
        margin-bottom: 8px;
        gap: 8px;
        border-radius: var(--pos-radius-sm);
    }
    .pos-top-left {
        width: 100%;
    }
    .pos-app-brand {
        gap: 8px;
        width: 100%;
    }
    .pos-brand-icon-box {
        width: 32px;
        height: 32px;
        border-radius: 8px;
    }
    .pos-brand-icon-box .material-symbols-rounded {
        font-size: 19px;
    }
    .pos-brand-text {
        flex: 1;
        min-width: 0;
    }
    .pos-brand-title-row {
        gap: 6px;
        display: flex;
        align-items: center;
        width: 100%;
    }
    .pos-main-title {
        font-size: 15px !important;
        line-height: 1.2;
    }
    .pos-mode-badge {
        font-size: 9.5px;
        padding: 2px 7px;
        margin-left: auto !important; /* Mobile only: positions Sale Terminal badge cleanly on the right side */
    }
    .pos-meta-pills {
        margin-top: 2px;
        gap: 6px;
    }
    .pos-cashier-pill {
        font-size: 11px;
        padding: 2px 7px;
    }

    /* Command Center: Customer & Controls */
    .pos-command-center {
        padding: 8px 10px;
        margin-bottom: 8px;
        border-radius: var(--pos-radius-sm);
    }
    .pos-single-command-strip {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        width: 100%;
    }
    .pos-strip-customer-group {
        width: 100%;
    }
    .pos-strip-customer {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .pos-strip-avatar {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        flex-shrink: 0;
    }
    .pos-strip-cust-text {
        flex: 1;
        min-width: 0;
    }
    .pos-strip-cust-name strong {
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
        max-width: 160px;
    }
    .pos-customer-sub {
        font-size: 10px;
    }
    .pos-btn-compact-secondary {
        height: 28px;
        padding: 0 10px;
        font-size: 11px;
        margin-left: auto;
        flex-shrink: 0;
        border-radius: 6px;
    }
    .pos-strip-divider {
        display: none !important;
    }

    /* Controls Group (Price Mode, Payment Method, Order Summary) */
    .pos-strip-controls-group {
        width: 100%;
        margin-left: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding-top: 8px;
        border-top: 1px dashed #e2e8f0;
        justify-content: space-between;
    }
    /* Price Mode Pill Block */
    .pos-strip-controls-group > .pos-strip-block:nth-child(1) {
        flex: 1 1 calc(42% - 4px);
        min-width: 120px;
    }
    /* Payment Method Pill Block */
    .pos-strip-controls-group > .pos-strip-block:nth-child(3) {
        flex: 1 1 calc(58% - 4px);
        min-width: 160px;
    }
    /* Order Summary Block - Spans Full Width Cleanly */
    .pos-strip-controls-group > .pos-strip-block:last-child {
        flex: 1 1 100%;
        width: 100%;
    }
    .pos-strip-label {
        font-size: 8.5px;
        margin-bottom: 2px;
    }
    .pill-group {
        width: 100%;
        display: flex;
        height: 28px;
        box-sizing: border-box;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .pill-btn {
        flex: 1 1 auto;
        height: 24px;
        padding: 0 6px;
        font-size: 10px;
        text-align: center;
        justify-content: center;
        white-space: nowrap;
    }

    /* Order Summary Inline Stats - Never Cuts Off on Mobile */
    .pos-stats-summary-inline {
        width: 100%;
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 5px 10px;
        height: auto;
        min-height: 30px;
        background: linear-gradient(135deg, #f8fafc, #edf2f7);
        border: 1px solid #e2e8f0;
        border-radius: var(--pos-radius-sm);
        box-sizing: border-box;
    }
    .stat-box-inline {
        gap: 4px;
    }
    .stat-label-inline {
        font-size: 9px;
        font-weight: 800;
    }
    .stat-val-inline {
        font-size: 11.5px;
        font-weight: 850;
    }
    .stat-val-inline.stat-amount-highlight {
        font-size: 13px;
        color: var(--pos-primary);
    }

    /* Collapsible Customer Form */
    .customer-form-grid {
        grid-template-columns: 1fr !important;
        gap: 6px;
    }
    .pos-input-group .form-input {
        height: 36px;
        font-size: 12.5px;
    }

    /* Products Shell */
    .products-head {
        padding: 8px 10px;
        flex-wrap: wrap;
        gap: 6px;
    }
    .products-title {
        font-size: 13px;
    }
    .products-subtitle {
        font-size: 10px;
    }
    .pos-btn-custom-product {
        height: 28px;
        padding: 0 8px;
        font-size: 10.5px;
    }
    .products-head-tools {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 8px 10px;
    }
    .pos-search-box-wrap {
        width: 100%;
    }
    .pos-search-input {
        height: 38px !important;
        line-height: 38px !important;
        font-size: 13px;
        padding: 0 12px 0 34px !important;
    }
    .pos-category-select-wrap {
        width: 100%;
    }
    .pos-category-select {
        height: 38px !important;
        line-height: 38px !important;
        font-size: 12.5px;
    }

    /* Product Cards Grid: 2 clean columns on mobile */
    .products-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 8px;
        padding: 8px;
        min-height: auto;
        max-height: 420px;
        -webkit-overflow-scrolling: touch;
    }
    .products-grid.layout-visual-1x1 {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 8px;
    }
    .product-card {
        padding: 8px;
        min-height: 85px;
        border-radius: var(--pos-radius-sm);
    }
    .product-image-box {
        width: 32px;
        height: 32px;
        flex: 0 0 32px;
    }
    .product-card h3 {
        font-size: 11px;
    }
    .product-price-display {
        font-size: 12px;
    }
    .product-add-btn {
        width: 26px;
        height: 26px;
    }

    /* Cart Shell */
    .cart-head {
        padding: 8px 10px;
        flex-wrap: wrap;
        gap: 6px;
    }
    .pos-tab-switcher {
        gap: 4px;
    }
    .pos-tab-btn {
        padding: 4px 8px;
        font-size: 11px;
    }
    .pos-cart-actions {
        gap: 4px;
    }
    .pos-util-btn {
        height: 28px;
        padding: 0 7px;
        font-size: 10.5px;
    }

    /* Cart Items Table & Scroll */
    .cart-scroll-zone {
        min-height: 160px;
        max-height: 320px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        max-width: 100%;
    }
    .cart-table {
        min-width: 320px;
        width: 100%;
    }
    .cart-table th {
        padding: 6px 6px;
        font-size: 9.5px;
    }
    .cart-table td {
        padding: 6px 6px;
        font-size: 11px;
    }
    .qty-input {
        width: 36px;
        height: 24px;
        font-size: 11px;
    }
    .qty-btn {
        width: 24px;
        height: 24px;
        font-size: 12px;
    }
    .cart-remove-btn {
        width: 24px;
        height: 24px;
    }

    /* Cart Totals & Checkout Buttons */
    .cart-total-box {
        padding: 10px 12px;
    }
    .total-row {
        font-size: 11.5px;
        margin-bottom: 4px;
    }
    .total-row-main {
        font-size: 15.5px;
        margin-top: 6px;
        padding-top: 6px;
    }
    .checkout-buttons {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 10px 12px 14px;
    }
    .btn-primary-checkout {
        width: 100%;
        height: 44px;
        font-size: 13.5px;
        font-weight: 800;
        border-radius: var(--pos-radius-sm);
        justify-content: center;
    }
    .btn-secondary-checkout {
        width: 100%;
        height: 36px;
        font-size: 11.5px;
        border-radius: var(--pos-radius-sm);
        justify-content: center;
    }
}

/* 4. MOBILE FLOATING QUICK CART SUMMARY BAR */
.pos-mobile-cart-bar {
    display: none;
}
@media (max-width: 768px) {
    .pos-mobile-cart-bar.has-items {
        display: flex;
        position: fixed;
        left: 12px;
        right: 12px;
        bottom: 74px; /* Sits right above fixed .mobile-bottom-nav */
        height: 46px;
        background: linear-gradient(135deg, #0f6dff, #0256d0);
        color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(15, 109, 255, 0.42);
        z-index: 215;
        align-items: center;
        justify-content: space-between;
        padding: 0 14px;
        cursor: pointer;
        animation: posMobileBarIn 0.22s ease-out;
    }
    @keyframes posMobileBarIn {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .pos-mobile-cart-bar-left {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 750;
    }
    .pos-mobile-cart-bar-left .material-symbols-rounded {
        font-size: 18px;
    }
    .pos-mobile-cart-bar-left strong {
        font-size: 13px;
        font-weight: 900;
        color: #ffffff;
    }
    .pos-mobile-cart-divider {
        opacity: 0.6;
        font-size: 10px;
    }
    .pos-mobile-cart-bar-right {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 11.5px;
        font-weight: 800;
        background: rgba(255, 255, 255, 0.18);
        padding: 4px 10px;
        border-radius: 99px;
    }
    .pos-mobile-cart-bar-right .material-symbols-rounded {
        font-size: 14px;
    }
}
</style>

<!-- TOP COMMAND COCKPIT (100% INLINE SPREAD - ZERO OVERFLOW) -->
<div class="pos-command-center" id="customer-card-panel">
    <div class="pos-single-command-strip">
        <!-- 1. Customer Section (Left Cluster) -->
        <div class="pos-strip-customer-group">
            <div class="pos-strip-customer" onclick="window.toggleCustomerForm(event)" title="Click to view/edit customer details (Alt + C)">
                <div class="pos-strip-avatar">
                    <span class="material-symbols-rounded">person</span>
                </div>
                <div class="pos-strip-cust-text">
                    <div class="pos-strip-cust-name">
                        <strong id="customer-summary-title">Walk-in Customer</strong>
                        <span class="pos-badge-status" id="customer-summary-badge" style="display:none;"></span>
                    </div>
                    <span class="pos-customer-sub" id="customer-summary-sub">Cash Sale</span>
                </div>
                <button type="button" class="pos-btn-compact-secondary" id="toggle-customer-form-btn" onclick="window.toggleCustomerForm(event)" title="Toggle Customer Info (Alt + C)">
                    <span class="material-symbols-rounded" style="font-size:14px;">person_add</span>
                    <span id="toggle-customer-label">+ Customer</span>
                </button>
            </div>
        </div>

        <!-- 2. Controls & Summary Section (Right Cluster) -->
        <div class="pos-strip-controls-group">
            <!-- Price Mode -->
            <div class="pos-strip-block">
                <span class="pos-strip-label">Price Mode</span>
                <div class="pill-group">
                    <button type="button" class="pill-btn active" id="sale-channel-retail" onclick="window.switchSaleChannel('retail')">
                        Retail
                    </button>
                    <button type="button" class="pill-btn" id="sale-channel-wholesale" onclick="window.switchSaleChannel('wholesale')">
                        Wholesale
                    </button>
                </div>
            </div>

            <div class="pos-strip-divider"></div>

            <!-- Payment Method -->
            <div class="pos-strip-block">
                <span class="pos-strip-label">Payment Method</span>
                <div class="pill-group">
                    <button type="button" class="pill-btn payment-method-btn active" data-payment-method="cash" id="pay-method-cash" onclick="window.switchPaymentMethod('cash')">
                        Cash
                    </button>
                    <button type="button" class="pill-btn payment-method-btn" data-payment-method="online" id="pay-method-online" onclick="window.switchPaymentMethod('online')">
                        Online
                    </button>
                    <button type="button" class="pill-btn payment-method-btn" data-payment-method="credit" id="pay-method-credit" onclick="window.switchPaymentMethod('credit')">
                        Credit (Udhar)
                    </button>
                </div>
            </div>

            <div class="pos-strip-divider"></div>

            <!-- Order Summary -->
            <div class="pos-strip-block">
                <span class="pos-strip-label">Order Summary</span>
                <div class="pos-stats-summary-inline">
                    <div class="stat-box-inline">
                        <span class="stat-label-inline">Items:</span>
                        <strong class="stat-val-inline" id="stat-items-count">0</strong>
                    </div>
                    <span class="stat-sep-inline">|</span>
                    <div class="stat-box-inline">
                        <span class="stat-label-inline">Qty:</span>
                        <strong class="stat-val-inline" id="stat-total-qty">0.00</strong>
                    </div>
                    <span class="stat-sep-inline">|</span>
                    <div class="stat-box-inline">
                        <span class="stat-label-inline">Total:</span>
                        <strong class="stat-val-inline stat-amount-highlight" id="stat-total-amount"><?php echo App::money(0); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Collapsible Customer Form Fields (Expands below when Add Customer is clicked) -->
    <div id="customer-form-collapsible" style="display:none;" class="pos-customer-collapsible">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">
            <div style="font-size:11.5px; font-weight:800; color:#475569; display:flex; align-items:center; gap:5px;">
                <span class="material-symbols-rounded" style="font-size:16px; color:#0f6dff;">person</span>
                <span>Customer Profile &amp; Billing Info</span>
            </div>
            <button type="button" class="btn btn-secondary" onclick="window.resetWalkInCustomer(event)" style="height:24px; font-size:11px; font-weight:750; padding:0 8px; border-radius:5px; display:inline-flex; align-items:center; gap:3px; cursor:pointer;" title="Reset back to standard Walk-in Customer">
                <span class="material-symbols-rounded" style="font-size:14px;">person_off</span>
                <span>Reset Walk-in</span>
            </button>
        </div>
        <div class="customer-form-grid">
            <div class="pos-input-group">
                <span class="material-symbols-rounded">badge</span>
                <input class="form-input" id="customer-name" type="text" placeholder="Search or enter customer name..." autocomplete="off">
            </div>
            <div class="pos-input-group">
                <span class="material-symbols-rounded">call</span>
                <input class="form-input" id="customer-phone" type="text" placeholder="Phone number" autocomplete="off">
            </div>
            <div class="pos-input-group full-width">
                <span class="material-symbols-rounded">home</span>
                <input class="form-input" id="customer-address" type="text" placeholder="Customer address">
            </div>
            <div class="pos-input-group">
                <span class="material-symbols-rounded">note</span>
                <input class="form-input" id="sale-note" type="text" placeholder="Invoice notes / Reference">
            </div>
            <div class="pos-input-group">
                <span class="material-symbols-rounded">calendar_today</span>
                <input class="form-input" id="sale-date" type="date" title="Sale Date">
            </div>
        </div>
    </div>

    <!-- Dynamic Credit Fields (Expands below only when Credit is chosen) -->
    <div id="credit-extra-fields" style="display:none; margin-top:8px;" class="pos-credit-drawer">
        <div class="customer-form-grid">
            <div>
                <label class="form-label" style="font-size:10.5px; font-weight:750; margin-bottom:2px;">Initial Payment</label>
                <input class="form-input" id="credit-initial-payment" type="number" min="0" step="0.01" placeholder="0.00" style="height:30px; font-size:11.5px;">
            </div>
            <div>
                <label class="form-label" style="font-size:10.5px; font-weight:750; margin-bottom:2px;">Initial Method</label>
                <select class="form-select" id="credit-initial-payment-method" style="height:30px; font-size:11.5px;">
                    <option value="cash">Cash</option>
                    <option value="online">Online</option>
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size:10.5px; font-weight:750; margin-bottom:2px;">Next Due Date <span style="font-weight:400; color:var(--text-muted);">(Optional)</span></label>
                <input class="form-input" id="credit-due-date" type="datetime-local" style="height:30px; font-size:11.5px;">
            </div>
            <div>
                <label class="form-label" style="font-size:10.5px; font-weight:750; margin-bottom:2px;">Private Note</label>
                <input class="form-input" id="credit-private-note" type="text" placeholder="Private notes" style="height:30px; font-size:11.5px;">
            </div>
        </div>
    </div>
</div>

<!-- MAIN POS WORKSPACE GRID -->
<div class="pos-layout">
    <!-- LEFT COLUMN: PRODUCTS WORKSPACE -->
    <section class="products-shell">
        <div class="products-head">
            <div class="products-head-info">
                <div class="products-title-wrap">
                    <span class="material-symbols-rounded pos-section-icon">inventory_2</span>
                    <h3 class="products-title">Products Workspace</h3>
                </div>
                <span class="products-subtitle">Click card to instantly add to cart</span>
            </div>
            <button type="button" class="pos-btn-custom-product" id="custom-item-trigger" title="Add custom product">
                <span class="material-symbols-rounded" style="font-size:16px;">add_circle</span> + Custom Product
            </button>
        </div>

        <div class="products-head-tools">
            <div class="pos-search-box-wrap">
                <span class="material-symbols-rounded pos-search-ico">search</span>
                <input id="pos-search" class="form-input pos-search-input" type="text" placeholder="Search by name, model or barcode (Ctrl + B)..." autocomplete="off">
                <span class="pos-search-kbd">Ctrl+B</span>
            </div>
            <div class="pos-category-select-wrap">
                <select id="pos-category-select" class="form-select pos-category-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categoriesList as $cat): ?>
                        <option value="<?php echo Security::escape($cat); ?>"><?php echo Security::escape($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="products-grid <?= $posCardLayout === 'visual_1x1' ? 'layout-visual-1x1' : '' ?>" id="products-grid">
            <?php foreach ($products as $p): ?>
            <article class="product-card" data-name="<?php echo Security::escape(strtolower($p['name'])); ?>"
                data-model="<?php echo Security::escape(strtolower($p['model_number'] ?? '')); ?>"
                data-barcode="<?php echo Security::escape(strtolower($p['barcode'] ?? '')); ?>"
                data-id="<?php echo (int) $p['id']; ?>" data-product-name="<?php echo Security::escape($p['name']); ?>"
                data-model-number="<?php echo Security::escape($p['model_number'] ?? ''); ?>"
                data-product-barcode="<?php echo Security::escape($p['barcode'] ?? ''); ?>"
                data-unit-label="<?php echo Security::escape($formatProductUnit($p)); ?>"
                data-unit-type="<?php echo Security::escape(strtoupper(trim((string) ($p['unit_type'] ?? 'PCS')))); ?>"
                data-retail-price="<?php echo (float) $p['price']; ?>"
                data-wholesale-price="<?php echo (float) ($p['wholesale_price'] ?? $p['price']); ?>"
                data-cost-price="<?php echo (float) ($p['cost'] ?? 0); ?>"
                data-min-stock="<?php echo (float) ($p['min_stock'] ?? 5); ?>"
                data-stock="<?php echo (float) $p['stock']; ?>">
                <div class="product-card-main">
                    <?php if ($showProductImages): ?>
                        <?php $productImage = $resolveProductImage($p); ?>
                        <div class="product-image-box">
                            <?php if ($productImage !== ''): ?>
                                <img src="<?php echo Security::escape($productImage); ?>" alt="" loading="lazy" style="object-fit:<?php echo $posImageFit === 'cover' ? 'contain' : $posImageFit; ?>;" onerror="this.onerror=null; this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                                <div class="product-image-placeholder" style="display:none;"><span class="material-symbols-rounded" style="color:#0f6dff; font-size:20px;">inventory_2</span></div>
                            <?php else: ?>
                                <div class="product-image-placeholder"><span class="material-symbols-rounded" style="color:#0f6dff; font-size:20px;">inventory_2</span></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($posShowName || $posShowStock || $posShowUnit): ?>
                    <div class="product-card-info">
                        <?php if ($posShowStock): ?>
                            <?php 
                                $cardStock = (float) ($p['stock'] ?? 0);
                                $cardMinStock = (float) ($p['min_stock'] ?? 5);
                                $cardUnit = strtoupper(trim((string) ($p['unit_type'] ?? 'PCS')));
                                $cardStockFormatted = App::formatQuantity($cardStock);
                            ?>
                            <span class="stock-mini <?php echo $cardStock <= 0 ? 'out' : ($cardStock <= $cardMinStock ? 'low' : ''); ?>">
                                Stock: <?php echo $cardStock <= 0 ? 'Out' : (Security::escape($cardStockFormatted) . ($cardUnit !== '' ? ' ' . Security::escape($cardUnit) : '')); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($posShowName): ?>
                            <h3><?php echo Security::escape($p['name']); ?></h3>
                        <?php endif; ?>

                        <?php if ($posShowUnit): ?>
                            <div class="product-unit-caption"><?php echo Security::escape($formatProductUnit($p)); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($posShowPrice || $posCardLayout === 'standard'): ?>
                <div class="product-card-foot" <?php echo (!$posShowPrice && $posCardLayout === 'visual_1x1') ? 'style="display:none;"' : ''; ?>>
                    <?php if ($posShowPrice): ?>
                        <div class="product-price-display"><?php echo App::money($p['price']); ?></div>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                    <button type="button" class="product-add-btn" title="Add to Cart">
                        <span class="material-symbols-rounded" style="font-size:17px;">add</span>
                    </button>
                </div>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="products-empty" id="products-empty" style="display:none; padding:36px 20px; text-align:center; color:#64748b; font-weight:700;">No products matched your search.</div>

        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="products-pagination" id="products-pagination">
                <div class="page-info" style="font-size:11.5px; font-weight:750; color:#64748b;">
                    Showing page <?php echo (int) ($currentPage ?? 1); ?> of <?php echo (int) ($totalPages ?? 1); ?>
                    <?php if (!empty($totalProducts)): ?>
                        (<?php echo (int) $totalProducts; ?> products)
                    <?php endif; ?>
                </div>
                <div class="page-links" style="display:flex; gap:6px;">
                    <?php $basePosUrl = (defined('BASE_URL') ? BASE_URL : '') . '/pos'; ?>
                    <?php if (($currentPage ?? 1) > 1): ?>
                        <a class="btn btn-secondary" href="<?php echo $basePosUrl; ?>?page=<?php echo (int) (($currentPage ?? 1) - 1); ?>" style="padding:3px 9px; font-size:11.5px; font-weight:800; border-radius:6px;">Prev</a>
                    <?php endif; ?>
                    <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
                        <a class="btn btn-secondary" href="<?php echo $basePosUrl; ?>?page=<?php echo (int) (($currentPage ?? 1) + 1); ?>" style="padding:3px 9px; font-size:11.5px; font-weight:800; border-radius:6px;">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- RIGHT COLUMN: CURRENT CART & ACTION CENTER -->
    <section class="cart-shell">
        <div class="cart-head">
            <!-- DUAL TABS: CART ITEMS vs GROSS PROFIT ADVISOR -->
            <div class="pos-tab-switcher">
                <button type="button" id="tab-nav-cart" class="pos-tab-btn active" onclick="switchPosTab('cart')">
                    <span class="material-symbols-rounded" style="font-size:15px; color:#0f6dff;">shopping_cart</span>
                    <span>Cart</span>
                    <span id="tab-cart-count-badge" class="pos-tab-badge">0</span>
                </button>
                <button type="button" id="tab-nav-profit" class="pos-tab-btn" onclick="switchPosTab('profit')" title="Gross Profit & Margin Advisor (Ctrl + M)">
                    <span class="material-symbols-rounded" style="font-size:15px; color:#059669;">query_stats</span>
                    <span>Gross Profit</span>
                    <span id="tab-profit-pill" class="pos-tab-profit-pill" style="display:none;"></span>
                    <span class="pos-action-kbd" style="font-size:9px; padding:0 3px; background:#dcfce7; color:#15803d; border-radius:3px;">Ctrl+M</span>
                </button>
            </div>

            <!-- ACTION BUTTONS: HOLD, RECALL, CLEAR (Equalized) -->
            <div class="pos-cart-actions">
                <button type="button" class="pos-util-btn pos-btn-hold" onclick="holdSale()" title="Hold current sale as draft (Alt + H)">
                    <span class="material-symbols-rounded" style="font-size:15px;">pause_circle</span>
                    <span>Hold</span>
                </button>
                <button type="button" class="pos-util-btn pos-btn-recall" onclick="openHeldSalesModal()" title="Recall held sale (Alt + R)">
                    <span class="material-symbols-rounded" style="font-size:15px;">restore</span>
                    <span>Recall</span>
                    <span class="held-badge" id="held-sales-count-badge" style="display:none;">0</span>
                </button>
                <button type="button" class="pos-util-btn pos-btn-clear" onclick="clearCart()" title="Clear cart" aria-label="Clear cart">
                    <span class="material-symbols-rounded" style="font-size:15px;">delete_sweep</span>
                </button>
            </div>
        </div>

        <div class="offline-bar" id="offline-bar" style="margin:8px 12px 0; padding:8px 12px; border-radius:8px; background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; display:none; font-size:11.5px; font-weight:750;">
            Offline mode active. Sales will be saved on this device and auto-synced when internet returns.
        </div>

        <!-- TAB 1: CART ITEMS VIEW -->
        <div id="pos-cart-view" style="display:flex; flex-direction:column; flex:1;">
            <div class="cart-scroll-zone">
                <table class="cart-table" id="cart-table" style="display:none;">
                    <thead>
                        <tr>
                            <th style="width:24px;">#</th>
                            <th>Item</th>
                            <th style="width:66px; text-align:right;">Price</th>
                            <th style="width:96px; text-align:center;">Qty</th>
                            <th style="width:72px; text-align:right;">Total</th>
                            <th style="width:30px; text-align:center;"></th>
                        </tr>
                    </thead>
                    <tbody id="cart-table-body">
                    </tbody>
                </table>

                <div class="cart-empty-illustration" id="cart-empty-illustration">
                    <svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:8px;">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <div style="font-weight:800; font-size:13.5px; color:#64748b;">Cart is empty</div>
                    <div style="font-size:11.5px; color:#94a3b8; margin-top:3px;">Add products from the left workspace to begin checkout.</div>
                </div>
            </div>

            <!-- CART TOTALS PANEL -->
            <div class="cart-total-box">
                <div class="total-row">
                    <span style="color:#64748b;">Subtotal</span>
                    <strong id="subtotal-val"><?php echo App::money(0); ?></strong>
                </div>
                <div class="total-row">
                    <span style="color:#64748b;">Discount</span>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <button type="button" class="pos-discount-icon-btn" onclick="window.openDiscountModal()" title="Set Discount (Ctrl + D / F7)">
                            <span class="material-symbols-rounded">percent</span>
                        </button>
                        <button type="button" class="pos-margin-icon-btn" onclick="window.switchPosTab('profit')" title="Gross Profit & Safe Margin Advisor (Ctrl + M)">
                            <span class="material-symbols-rounded">analytics</span>
                        </button>
                    </div>
                </div>
                <div class="total-row">
                    <span style="color:#be123c;">Discount Applied</span>
                    <strong id="discount-val" style="color:#be123c;"><?php echo App::money(0); ?></strong>
                </div>
                <div class="total-row" id="tax-row" style="display:none;">
                    <span id="tax-label-val" style="color:#64748b;">Tax</span>
                    <strong id="tax-val"><?php echo App::money(0); ?></strong>
                </div>
                <div class="total-row total-row-main">
                    <span>Total</span>
                    <span id="total-val" style="color:#0f6dff;"><?php echo App::money(0); ?></span>
                </div>
                <!-- LIVE GROSS PROFIT STRIP IN CART TOTALS -->
                <div class="pos-footer-profit-strip" id="cart-profit-strip" style="cursor:pointer; display:flex; justify-content:space-between; align-items:center; background:#f0fdf4; margin:10px -14px -12px; padding:8px 14px; border-radius:0 0 var(--pos-radius-md) var(--pos-radius-md); border-top:1px dashed #bbf7d0;" onclick="window.switchPosTab('profit')" title="Click for Detailed Profit Breakdown (Ctrl + M)">
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span class="material-symbols-rounded" style="font-size:16px; color:#059669;">analytics</span>
                        <span style="font-size:11.5px; font-weight:800; color:#15803d; text-transform:uppercase; letter-spacing:0.3px;">Gross Profit</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span id="cart-gross-profit-val" style="font-size:13.5px; font-weight:900; color:#047857;"><?php echo App::money(0); ?></span>
                        <span id="cart-margin-pct-val" style="font-size:11px; font-weight:850; padding:2px 7px; border-radius:6px; background:#dcfce7; color:#15803d;">0.0%</span>
                    </div>
                </div>
            </div>

            <!-- ACTION BUTTONS (CLEAN CHECKOUT & CLEAR CART) -->
            <div class="checkout-buttons">
                <button class="btn-primary-checkout" type="button" onclick="window.processCheckout ? window.processCheckout('paid') : processCheckout('paid')" title="Checkout & Print Receipt (Alt + P / Ctrl + Enter)">
                    <span class="material-symbols-rounded" style="font-size:18px;">receipt_long</span>
                    <span>Checkout &amp; Print</span>
                    <span class="pos-action-kbd" style="background:rgba(255,255,255,0.22); color:#fff; font-size:10px; margin-left:4px; padding:1px 5px; border-radius:3px;">Alt+P</span>
                </button>
                <button class="btn-secondary-checkout" type="button" onclick="window.clearCart ? window.clearCart() : clearCart()" style="color:#ef4444;" title="Clear current cart">
                    <span class="material-symbols-rounded" style="font-size:16px;">delete_sweep</span>
                    <span>Clear Cart</span>
                </button>
            </div>
        </div>

        <!-- TAB 2: GROSS PROFIT & SAFE MARGIN ADVISOR VIEW (NATIVE ON-PAGE TAB - NO POPUP) -->
        <div id="pos-profit-tab-view" style="display:none; padding:12px; flex:1; overflow-y:auto;">
            <!-- Dynamically populated by renderProfitAdvisorTab() -->
        </div>
    </section>
</div>

<!-- MOBILE FLOATING QUICK CART SUMMARY BAR -->
<div id="pos-mobile-cart-bar" class="pos-mobile-cart-bar" onclick="window.scrollToPosCart ? window.scrollToPosCart() : scrollToPosCart()">
    <div class="pos-mobile-cart-bar-left">
        <span class="material-symbols-rounded">shopping_cart</span>
        <span id="pos-mobile-cart-count">0 items</span>
        <span class="pos-mobile-cart-divider">&bull;</span>
        <strong id="pos-mobile-cart-total"><?php echo App::money(0); ?></strong>
    </div>
    <div class="pos-mobile-cart-bar-right">
        <span>View Cart &amp; Pay</span>
        <span class="material-symbols-rounded">arrow_forward</span>
    </div>
</div>

<?php ob_start(); ?>
<script>
(() => {
    let cart = [];
    let globalDiscount = 0;
    let currentPaymentMethod = 'cash';
    let currentSaleChannel = 'retail';
    let currentTransactionType = '<?php echo ($_GET['mode'] ?? '') === 'return' ? 'Return' : 'Sale'; ?>';
    let customerSuggestTimer = null;
    let csrfToken = document.body?.dataset?.csrfToken || '';
    const baseUrl = <?php echo json_encode(defined('BASE_URL') ? BASE_URL : ''); ?>;
    const scopeUrl = (url) => window.appScopeTabSessionUrl ? window.appScopeTabSessionUrl(url) : url;
    const checkoutStorageKey = 'pos_checkout_state_v1_store_<?php echo (int) Tenant::currentStoreId(); ?>_user_<?php echo (int) Auth::id(); ?>';
    const showProductImages = <?php echo json_encode($showProductImages); ?>;
    const posCardLayout = <?php echo json_encode($posCardLayout); ?>;
    const posImageFit = <?php echo json_encode($posImageFit); ?>;
    const posShowName = <?php echo json_encode($posShowName); ?>;
    const posShowStock = <?php echo json_encode($posShowStock); ?>;
    const posShowUnit = <?php echo json_encode($posShowUnit); ?>;
    const posShowPrice = <?php echo json_encode($posShowPrice); ?>;
    const productImageFields = <?php echo json_encode($productImageFields); ?>;
    const taxSettings = <?php echo json_encode($taxSettings ?? ['enabled' => false, 'label' => 'Sales Tax', 'rate' => 0, 'fbr_enabled' => false, 'fbr_pos_id' => '']); ?>;
    const roundMoney = (value) => Math.max(0, Math.round(Number(value || 0)));

    const offlineBar = document.getElementById('offline-bar');
    const creditExtraFields = document.getElementById('credit-extra-fields');
    const customerNameInput = document.getElementById('customer-name');
    const customerPhoneInput = document.getElementById('customer-phone');
    const customerAddressInput = document.getElementById('customer-address');
    const saleDateInput = document.getElementById('sale-date');
    const todayIso = new Date().toISOString().slice(0, 10);
    if (saleDateInput) {
        saleDateInput.value = todayIso;
    }

    const customerCollapsible = document.getElementById('customer-form-collapsible');
    const toggleCustomerBtn = document.getElementById('toggle-customer-form-btn');
    const customerSummaryTitle = document.getElementById('customer-summary-title');
    const customerSummarySub = document.getElementById('customer-summary-sub');
    const customerSummaryBadge = document.getElementById('customer-summary-badge');

    const updateCustomerSummaryBadge = () => {
        const name = (customerNameInput?.value || '').trim();
        const phone = (customerPhoneInput?.value || '').trim();
        if (name || phone) {
            if (customerSummaryTitle) customerSummaryTitle.textContent = name || 'Customer Attached';
            if (customerSummarySub) customerSummarySub.textContent = phone ? phone : 'Customer details attached';
            if (customerSummaryBadge) {
                customerSummaryBadge.style.display = 'inline-block';
                customerSummaryBadge.textContent = 'Active Customer';
                customerSummaryBadge.style.background = '#e0f2fe';
                customerSummaryBadge.style.color = '#0284c7';
                customerSummaryBadge.style.borderColor = '#bae6fd';
            }
        } else {
            if (customerSummaryTitle) customerSummaryTitle.textContent = 'Walk-in Customer';
            if (customerSummarySub) customerSummarySub.textContent = 'Cash Sale';
            if (customerSummaryBadge) {
                customerSummaryBadge.style.display = 'none';
            }
        }
    };

    window.toggleCustomerForm = (event) => {
        if (event) {
            if (event.stopPropagation) event.stopPropagation();
            if (event.preventDefault && event.type === 'click') event.preventDefault();
        }
        const coll = document.getElementById('customer-form-collapsible');
        const btn = document.getElementById('toggle-customer-form-btn');
        if (!coll) return;
        const isHidden = (coll.style.display === 'none' || window.getComputedStyle(coll).display === 'none');
        coll.style.display = isHidden ? 'block' : 'none';
        if (btn) {
            btn.innerHTML = isHidden
                ? '<span class="material-symbols-rounded" style="font-size:14px;">expand_less</span> Hide'
                : '<span class="material-symbols-rounded" style="font-size:14px;">person_add</span> + Customer';
        }
        if (isHidden) {
            setTimeout(() => {
                const nameInput = document.getElementById('customer-name');
                if (nameInput) {
                    nameInput.focus();
                    nameInput.select?.();
                }
            }, 60);
        }
    };

    window.resetWalkInCustomer = (event) => {
        if (event && event.stopPropagation) event.stopPropagation();
        if (customerNameInput) customerNameInput.value = '';
        if (customerPhoneInput) customerPhoneInput.value = '';
        if (customerAddressInput) customerAddressInput.value = '';
        updateCustomerSummaryBadge();
        window.appToast?.('Customer reset to Walk-in Customer.', 'info');
    };

    const setCustomerFormExpanded = (expanded) => {
        const coll = document.getElementById('customer-form-collapsible');
        const btn = document.getElementById('toggle-customer-form-btn');
        if (!coll) return;
        coll.style.display = expanded ? 'block' : 'none';
        if (btn) {
            btn.innerHTML = expanded
                ? '<span class="material-symbols-rounded" style="font-size:16px;">expand_less</span> Hide <span class="pos-action-kbd" style="font-size:9.5px; padding:1px 4px; margin-left:3px; background:#e2e8f0; color:#475569;">Alt+C</span>'
                : '<span class="material-symbols-rounded" style="font-size:16px;">person_add</span> + Add Customer <span class="pos-action-kbd" style="font-size:9.5px; padding:1px 4px; margin-left:3px; background:#e2e8f0; color:#475569;">Alt+C</span>';
        }
    };
    window.setCustomerFormExpanded = setCustomerFormExpanded;

    customerNameInput?.addEventListener('input', updateCustomerSummaryBadge);
    customerPhoneInput?.addEventListener('input', updateCustomerSummaryBadge);

    const updateOfflineBar = () => {
        const hasQueue = window.appOfflineQueue?.all()?.length > 0;
        if (offlineBar) offlineBar.style.display = (!navigator.onLine || hasQueue) ? 'block' : 'none';
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    let posAudioContext = null;
    const playPosTone = (frequency, startOffset, duration, volume = 0.08) => {
        try {
            const startAt = posAudioContext.currentTime + startOffset;
            const oscillator = posAudioContext.createOscillator();
            const gain = posAudioContext.createGain();
            oscillator.type = 'triangle';
            oscillator.frequency.setValueAtTime(frequency, startAt);
            oscillator.frequency.exponentialRampToValueAtTime(frequency * 1.025, startAt + duration * 0.55);
            gain.gain.setValueAtTime(0.0001, startAt);
            gain.gain.exponentialRampToValueAtTime(volume, startAt + 0.012);
            gain.gain.exponentialRampToValueAtTime(0.0001, startAt + duration);
            oscillator.connect(gain);
            gain.connect(posAudioContext.destination);
            oscillator.start(startAt);
            oscillator.stop(startAt + duration + 0.02);
        } catch (e) {}
    };

    const playAddItemBeep = () => {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            posAudioContext = posAudioContext || new AudioContext();
            if (posAudioContext.state === 'suspended') {
                posAudioContext.resume();
            }
            playPosTone(1046.5, 0, 0.055, 0.075);
            playPosTone(1568, 0.052, 0.075, 0.06);
        } catch (error) {}
    };

    const notifyItemAdded = (product) => {
        playAddItemBeep();
        const scanQty = Math.max(1, Number(product.scan_quantity || 1));
        const suffix = product.scan_type === 'box' && scanQty > 1 ? ` (${scanQty} items)` : '';
        window.appToast?.(`${product.name || 'Item'} added to cart${suffix}`, 'success');
    };

    const refreshCsrfToken = async () => {
        const response = await fetch(scopeUrl(`${baseUrl}/pos/csrfToken`), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        if (!data.success || !data.csrf_token) {
            throw new Error('Could not refresh security token.');
        }
        csrfToken = data.csrf_token;
        if (document.body) {
            document.body.dataset.csrfToken = csrfToken;
        }
        return csrfToken;
    };

    const sendCheckoutRequest = async (payload) => {
        payload.csrf_token = csrfToken;
        return await fetch(scopeUrl(`${baseUrl}/pos/checkout`), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
            },
            body: JSON.stringify(payload)
        });
    };

    const persistCheckoutState = () => {
        try {
            localStorage.setItem(checkoutStorageKey, JSON.stringify({
                cart,
                globalDiscount,
                currentSaleChannel,
                currentTransactionType
            }));
        } catch (error) {}
    };

    const restoreCheckoutState = () => {
        try {
            const raw = localStorage.getItem(checkoutStorageKey);
            if (!raw) return;
            const parsed = JSON.parse(raw);
            const loadedCart = Array.isArray(parsed?.cart) ? parsed.cart : [];
            cart = loadedCart
                .map((item) => ({
                    id: item?.id ?? null,
                    name: String(item?.name ?? '').trim(),
                    model_number: String(item?.model_number ?? ''),
                    barcode: String(item?.barcode ?? ''),
                    unit_label: String(item?.unit_label || item?.unit_type || 'pcs'),
                    matched_code: String(item?.matched_code ?? item?.barcode ?? ''),
                    cost_price: roundMoney(item?.cost_price ?? item?.cost ?? 0),
                    retail_price: roundMoney(item?.retail_price ?? item?.price ?? 0),
                    wholesale_price: roundMoney(item?.wholesale_price ?? item?.price ?? 0),
                    price: roundMoney(item?.price ?? 0),
                    quantity: Math.max(0.001, parseFloat(item?.quantity) || 1),
                    discount: roundMoney(item?.discount ?? 0),
                    max_stock: Number(item?.max_stock ?? 0),
                    scan_type: String(item?.scan_type ?? 'single'),
                    scan_label: String(item?.scan_label ?? ''),
                    scan_quantity: Math.max(1, Number(item?.scan_quantity ?? 1))
                }))
                .filter((item) => item.name !== '');
            globalDiscount = Math.max(0, Number(parsed?.globalDiscount ?? 0));
            currentSaleChannel = ['retail', 'wholesale'].includes(parsed?.currentSaleChannel) ? parsed.currentSaleChannel : currentSaleChannel;
            currentTransactionType = ['Sale', 'Return', 'Exchange', 'Refund'].includes(parsed?.currentTransactionType) ? parsed.currentTransactionType : currentTransactionType;
        } catch (error) {}
    };

    const isWeightedItem = (item) => {
        const unit = String(item?.unit_label || item?.unit_type || '').toLowerCase();
        const weightedKeywords = ['kg', 'g', 'gram', 'grams', 'kilo', 'kilogram', 'ltr', 'liter', 'litre', 'ml', 'meter', 'm', 'weight', 'weighted'];
        return weightedKeywords.some(kw => unit.includes(kw)) || ((parseFloat(item?.quantity || 1) % 1) !== 0);
    };

    const formatHeldDate = (dateStr) => {
        if (!dateStr) return 'Recently';
        try {
            const d = new Date(dateStr.replace(/-/g, '/'));
            if (isNaN(d.getTime())) return dateStr;
            const dayMonth = d.toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
            const time = d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: true });
            return `${dayMonth}, ${time}`;
        } catch (e) {
            return dateStr;
        }
    };

    const fetchHeldSalesCount = async () => {
        try {
            let res = await fetch(scopeUrl(`${baseUrl}/pos/heldSales`), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) {
                res = await fetch(scopeUrl(`${baseUrl}/index.php?url=pos/heldSales`), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
            }
            const data = await res.json();
            const badge = document.getElementById('held-sales-count-badge');
            if (badge && data.success && Array.isArray(data.drafts)) {
                const count = data.drafts.length;
                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline-block' : 'none';
            }
        } catch (e) {}
    };

    window.openHeldSalesModal = async () => {
        window.appModal.open({
            title: 'Held Sales & Saved Drafts',
            maxWidth: '840px',
            body: `
                <div style="text-align:center; padding:48px 24px; color:#64748b;">
                    <div style="width:52px; height:52px; border-radius:50%; background:#eff6ff; display:grid; place-items:center; margin:0 auto 14px; color:#0f6dff;">
                        <span class="material-symbols-rounded" style="font-size:30px; animation:spin 1s linear infinite;">sync</span>
                    </div>
                    <div style="font-weight:800; font-size:15px; color:#0f172a;">Retrieving Active Drafts Queue...</div>
                    <div style="font-size:12.5px; color:#64748b; margin-top:4px;">Please wait while we check paused checkout carts.</div>
                </div>
            `,
            actions: `
                <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                    <div style="font-size:11.5px; color:#64748b; display:flex; align-items:center; gap:5px;">
                        <span class="material-symbols-rounded" style="font-size:16px; color:#0f6dff;">tips_and_updates</span>
                        <span>Shortcuts: <strong>Alt + R</strong> to open queue &bull; <strong>Alt + H</strong> to park cart</span>
                    </div>
                    <button class="btn" type="button" data-modal-close style="background:#475569 !important; color:#ffffff !important; font-weight:700; border:none; padding:8px 20px; border-radius:8px; cursor:pointer;">Close</button>
                </div>
            `
        });

        try {
            let res = await fetch(scopeUrl(`${baseUrl}/pos/heldSales`), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) {
                res = await fetch(scopeUrl(`${baseUrl}/index.php?url=pos/heldSales`), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
            }
            const data = await res.json();
            if (!data.success || !Array.isArray(data.drafts) || data.drafts.length === 0) {
                window.appModal.updateBody(`
                    <div style="text-align:center; padding:48px 20px;">
                        <div style="width:64px; height:64px; border-radius:50%; background:#f8fafc; border:2px dashed #cbd5e1; display:grid; place-items:center; margin:0 auto 16px; color:#94a3b8;">
                            <span class="material-symbols-rounded" style="font-size:34px;">pause_circle</span>
                        </div>
                        <div style="font-weight:900; font-size:17px; color:#0f172a;">No Held Sales in Queue</div>
                        <div style="font-size:13px; color:#64748b; margin-top:6px; max-width:420px; margin-left:auto; margin-right:auto; line-height:1.5;">
                            All customer checkouts are clear! When a customer needs extra time during billing, click <strong>Hold (Alt + H)</strong> to park their cart and resume anytime here.
                        </div>
                    </div>
                `);
                const badge = document.getElementById('held-sales-count-badge');
                if (badge) badge.style.display = 'none';
                return;
            }

            const drafts = data.drafts;
            const badge = document.getElementById('held-sales-count-badge');
            if (badge) {
                badge.textContent = drafts.length;
                badge.style.display = 'inline-block';
            }

            const totalQueueAmount = drafts.reduce((sum, d) => sum + Number(d.total || 0), 0);
            const totalQueueFormatted = window.appFormatMoney ? window.appFormatMoney(totalQueueAmount) : ('Rs ' + totalQueueAmount.toLocaleString());

            const renderDraftRows = (items) => {
                if (!items.length) {
                    return `
                        <tr>
                            <td colspan="6" style="padding:36px 16px; text-align:center; color:#94a3b8;">
                                <span class="material-symbols-rounded" style="font-size:32px; display:block; margin:0 auto 8px; color:#cbd5e1;">search_off</span>
                                <div style="font-weight:800; font-size:13px; color:#475569;">No held sales matched your search</div>
                                <div style="font-size:11.5px; color:#94a3b8; margin-top:3px;">Try searching with a different invoice #, customer name or phone.</div>
                            </td>
                        </tr>
                    `;
                }
                return items.map(draft => `
                    <tr style="border-bottom:1px solid #f1f5f9; font-size:12px; transition:background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <td style="padding:12px 14px; font-weight:850; color:#0f172a; white-space:nowrap;">
                            <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; background:#eff6ff; color:#0f6dff; border:1px solid #bfdbfe; font-size:11.5px; font-weight:850; letter-spacing:0.02em;">
                                <span class="material-symbols-rounded" style="font-size:14px;">tag</span>
                                ${escapeHtml(draft.custom_id || ('#INV-' + draft.id))}
                            </span>
                        </td>
                        <td style="padding:12px 14px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:28px; height:28px; border-radius:50%; background:#f1f5f9; display:grid; place-items:center; color:#64748b; font-size:14px; flex-shrink:0;">
                                    <span class="material-symbols-rounded" style="font-size:17px;">person</span>
                                </div>
                                <div>
                                    <strong style="color:#0f172a; display:block; font-size:12.5px; font-weight:800;">
                                        ${escapeHtml(draft.customer_name || 'Walk-in Customer')}
                                    </strong>
                                    ${draft.customer_phone ? `
                                        <span style="font-size:11px; color:#64748b; font-weight:600; display:inline-flex; align-items:center; gap:3px; margin-top:2px;">
                                            <span class="material-symbols-rounded" style="font-size:13px; color:#94a3b8;">call</span>
                                            ${escapeHtml(draft.customer_phone)}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                        </td>
                        <td style="padding:12px 14px; text-align:center; font-weight:800; color:#475569;">
                            <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 9px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:99px; font-size:11px; font-weight:750; color:#334155;">
                                <span class="material-symbols-rounded" style="font-size:13px; color:#64748b;">shopping_bag</span>
                                ${draft.item_count} item${draft.item_count == 1 ? '' : 's'}
                            </span>
                        </td>
                        <td style="padding:12px 14px; text-align:right; font-weight:900; color:#0f6dff; font-size:14px; white-space:nowrap;">
                            ${window.appFormatMoney ? window.appFormatMoney(Number(draft.total || 0)) : ('Rs ' + Number(draft.total || 0).toLocaleString())}
                        </td>
                        <td style="padding:12px 14px; text-align:center; font-size:11.5px; color:#64748b; white-space:nowrap;">
                            <span style="display:inline-flex; align-items:center; gap:4px; font-weight:600;">
                                <span class="material-symbols-rounded" style="font-size:14px; color:#94a3b8;">schedule</span>
                                ${formatHeldDate(draft.created_at)}
                            </span>
                        </td>
                        <td style="padding:12px 14px; text-align:right; white-space:nowrap;">
                            <button type="button" class="btn" onclick="recallHeldDraft(${draft.id})" style="padding:6px 14px; font-size:12px; font-weight:800; border-radius:8px; background:#0f6dff !important; color:#ffffff !important; display:inline-flex; align-items:center; gap:5px; cursor:pointer; border:none; box-shadow:0 2px 6px rgba(15,109,255,0.28); transition:all 0.15s ease;">
                                <span class="material-symbols-rounded" style="font-size:16px; color:#ffffff !important;">restore</span> Recall
                            </button>
                            <button type="button" class="btn" onclick="deleteHeldDraft(${draft.id})" style="padding:6px 10px; font-size:12px; font-weight:800; border-radius:8px; margin-left:6px; background:#ef4444 !important; color:#ffffff !important; display:inline-flex; align-items:center; gap:4px; cursor:pointer; border:none; box-shadow:0 2px 6px rgba(239,68,68,0.25); transition:all 0.15s ease;" title="Delete this draft">
                                <span class="material-symbols-rounded" style="font-size:16px; color:#ffffff !important;">delete</span>
                            </button>
                        </td>
                    </tr>
                `).join('');
            };

            window.appModal.updateBody(`
                <div style="margin-bottom:14px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span style="display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:99px; background:#eff6ff; color:#0f6dff; border:1px solid #bfdbfe; font-size:12px; font-weight:850;">
                            <span class="material-symbols-rounded" style="font-size:16px;">receipt_long</span>
                            <span id="draft-queue-count">${drafts.length}</span> Active Draft${drafts.length === 1 ? '' : 's'}
                        </span>
                        <span style="display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:99px; background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; font-size:12px; font-weight:850;">
                            <span class="material-symbols-rounded" style="font-size:16px;">payments</span>
                            Total: <span id="draft-queue-val">${totalQueueFormatted}</span>
                        </span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="position:relative; width:220px;">
                            <input id="held-drafts-filter" class="form-input" type="text" placeholder="Search drafts or customer..." style="height:32px; font-size:12px; padding-left:30px; border-radius:8px; border:1px solid #cbd5e1; background:#ffffff; width:100%; box-sizing:border-box;">
                            <span class="material-symbols-rounded" style="position:absolute; left:8px; top:8px; font-size:16px; color:#94a3b8; pointer-events:none;">search</span>
                        </div>
                        <button type="button" onclick="openHeldSalesModal()" style="padding:6px 10px; font-size:12px; font-weight:700; color:#334155; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; gap:4px; height:32px;" title="Refresh Queue">
                            <span class="material-symbols-rounded" style="font-size:16px;">refresh</span>
                        </button>
                        ${drafts.length > 1 ? `
                            <button type="button" onclick="clearAllHeldDrafts()" style="padding:6px 12px; font-size:12px; font-weight:800; color:#ffffff !important; background:#ef4444 !important; border:none; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; gap:4px; height:32px; box-shadow:0 2px 5px rgba(239,68,68,0.25);" title="Discard all held sales">
                                <span class="material-symbols-rounded" style="font-size:16px; color:#ffffff !important;">delete_sweep</span> Discard All
                            </button>
                        ` : ''}
                    </div>
                </div>

                <div style="max-height:400px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:12px; box-shadow:inset 0 1px 2px rgba(0,0,0,0.03);">
                    <table style="width:100%; border-collapse:collapse;" id="held-drafts-table">
                        <thead style="background:#f8fafc; border-bottom:1px solid #e2e8f0; position:sticky; top:0; z-index:2;">
                            <tr style="font-size:11px; text-transform:uppercase; letter-spacing:0.04em;">
                                <th style="padding:10px 14px; text-align:left; font-weight:800; color:#475569;">Draft #</th>
                                <th style="padding:10px 14px; text-align:left; font-weight:800; color:#475569;">Customer</th>
                                <th style="padding:10px 14px; text-align:center; font-weight:800; color:#475569;">Items</th>
                                <th style="padding:10px 14px; text-align:right; font-weight:800; color:#475569;">Total Amount</th>
                                <th style="padding:10px 14px; text-align:center; font-weight:800; color:#475569;">Held Time</th>
                                <th style="padding:10px 14px; text-align:right; font-weight:800; color:#475569;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="held-drafts-tbody">${renderDraftRows(drafts)}</tbody>
                    </table>
                </div>
            `);

            const filterInput = document.getElementById('held-drafts-filter');
            const tbody = document.getElementById('held-drafts-tbody');
            if (filterInput && tbody) {
                filterInput.addEventListener('input', () => {
                    const q = filterInput.value.toLowerCase().trim();
                    const filtered = drafts.filter(d => 
                        String(d.custom_id || '').toLowerCase().includes(q) ||
                        String(d.id || '').toLowerCase().includes(q) ||
                        String(d.customer_name || '').toLowerCase().includes(q) ||
                        String(d.customer_phone || '').toLowerCase().includes(q) ||
                        String(d.total || '').toLowerCase().includes(q)
                    );
                    tbody.innerHTML = renderDraftRows(filtered);
                });
            }
        } catch (e) {
            window.appModal.updateBody(`
                <div style="padding:36px 20px; text-align:center;">
                    <div style="width:48px; height:48px; border-radius:50%; background:#fef2f2; display:grid; place-items:center; margin:0 auto 12px; color:#ef4444;">
                        <span class="material-symbols-rounded" style="font-size:28px;">error</span>
                    </div>
                    <div style="font-weight:850; font-size:15px; color:#991b1b; margin-bottom:6px;">Unable to Retrieve Held Sales</div>
                    <div style="font-size:12.5px; color:#64748b; max-width:380px; margin:0 auto 16px;">A network or server communication error occurred while loading your saved drafts queue.</div>
                    <button type="button" onclick="openHeldSalesModal()" style="padding:8px 20px; font-size:12.5px; font-weight:800; background:#0f6dff !important; color:#ffffff !important; border:none; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; box-shadow:0 2px 6px rgba(15,109,255,0.25);">
                        <span class="material-symbols-rounded" style="font-size:16px; color:#ffffff !important;">refresh</span> Try Again
                    </button>
                </div>
            `);
        }
    };

    window.recallHeldDraft = async (id) => {
        if (cart && cart.length > 0) {
            const confirmed = typeof window.appConfirm === 'function'
                ? await window.appConfirm(
                    `Your active register currently has <strong>${cart.length} item(s)</strong>. Recalling this held draft will replace your current cart.<br><br>Would you like to proceed?`,
                    'Replace Active Cart?',
                    {
                        confirmText: 'Yes, Recall Draft',
                        cancelText: 'Keep Current Cart',
                        icon: 'sync_alt',
                        isDanger: false
                    }
                )
                : confirm(`Your active cart already contains ${cart.length} item(s). Recalling this draft will replace your current cart. Continue?`);
            if (!confirmed) return;
        }
        try {
            let res = await fetch(scopeUrl(`${baseUrl}/pos/recallDraft?id=${id}`), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) {
                res = await fetch(scopeUrl(`${baseUrl}/index.php?url=pos/recallDraft&id=${id}`), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
            }
            const data = await res.json();
            if (!data.success || !data.draft) {
                window.appToast(data.error || 'Failed to recall draft sale.', 'error');
                return;
            }

            const draft = data.draft;
            cart = Array.isArray(draft.items) ? draft.items.map(item => ({
                id: Number(item.id || 0),
                name: item.name || 'Product',
                model_number: item.model_number || '',
                barcode: '',
                unit_label: item.unit_label || 'pcs',
                retail_price: Number(item.price || 0),
                wholesale_price: Number(item.price || 0),
                cost_price: Number(item.cost_price || item.cost || 0),
                price: Number(item.price || 0),
                quantity: Number(item.quantity || 1),
                discount: Number(item.discount || 0),
                max_stock: 9999
            })) : [];

            globalDiscount = Number(draft.discount || 0);

            const nameEl = document.getElementById('customer-name');
            const phoneEl = document.getElementById('customer-phone');
            const addrEl = document.getElementById('customer-address');
            const noteEl = document.getElementById('sale-note');

            if (nameEl) nameEl.value = draft.customer_name || '';
            if (phoneEl) phoneEl.value = draft.customer_phone || '';
            if (addrEl && draft.customer_address) addrEl.value = draft.customer_address || '';
            if (noteEl) noteEl.value = draft.notes || '';

            if (typeof window.setCustomerFormExpanded === 'function') {
                window.setCustomerFormExpanded(Boolean(draft.customer_name || draft.customer_phone));
            }
            if (typeof updateCustomerSummaryBadge === 'function') {
                updateCustomerSummaryBadge();
            }

            renderCart();
            fetchHeldSalesCount();
            window.appModal.close();
            window.appToast('Draft sale recalled into active checkout!', 'success');
        } catch (e) {
            window.appToast('Error recalling draft sale.', 'error');
        }
    };

    window.deleteHeldDraft = async (id) => {
        const confirmed = typeof window.appConfirm === 'function'
            ? await window.appConfirm(
                'Are you sure you want to permanently delete this held sale draft? This action cannot be reversed.',
                'Delete Held Sale Draft?',
                {
                    confirmText: 'Yes, Delete Draft',
                    cancelText: 'Keep Draft',
                    icon: 'delete_forever',
                    isDanger: true
                }
            )
            : confirm('Are you sure you want to permanently delete this held sale draft?');
        if (!confirmed) return;

        try {
            let res = await fetch(scopeUrl(`${baseUrl}/pos/deleteDraft?id=${id}`), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) {
                res = await fetch(scopeUrl(`${baseUrl}/index.php?url=pos/deleteDraft&id=${id}`), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
            }
            const data = await res.json();
            if (data.success) {
                window.appToast('Draft sale deleted successfully.', 'success');
                openHeldSalesModal();
                fetchHeldSalesCount();
            } else {
                window.appToast(data.error || 'Failed to delete draft.', 'error');
            }
        } catch (e) {
            window.appToast('Error deleting draft sale.', 'error');
        }
    };

    window.clearAllHeldDrafts = async () => {
        const confirmed = typeof window.appConfirm === 'function'
            ? await window.appConfirm(
                'Are you sure you want to permanently clear <strong>all held sale drafts</strong> in the queue? This will wipe all paused carts.',
                'Clear All Held Sales?',
                {
                    confirmText: 'Yes, Clear All',
                    cancelText: 'Keep Drafts',
                    icon: 'auto_delete',
                    isDanger: true
                }
            )
            : confirm('Are you sure you want to permanently clear all held sale drafts in queue?');
        if (!confirmed) return;

        try {
            let res = await fetch(scopeUrl(`${baseUrl}/pos/clearAllDrafts`), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) {
                res = await fetch(scopeUrl(`${baseUrl}/index.php?url=pos/clearAllDrafts`), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
            }
            const data = await res.json();
            if (data.success) {
                window.appToast('All held sale drafts cleared.', 'success');
                openHeldSalesModal();
                fetchHeldSalesCount();
            } else {
                window.appToast(data.error || 'Failed to clear drafts.', 'error');
            }
        } catch (e) {
            window.appToast('Error clearing drafts queue.', 'error');
        }
    };

    let currentPosRightTab = 'cart';

    window.switchPosTab = (tabName) => {
        currentPosRightTab = tabName;
        const cartView = document.getElementById('pos-cart-view');
        const profitView = document.getElementById('pos-profit-tab-view');
        const btnCart = document.getElementById('tab-nav-cart');
        const btnProfit = document.getElementById('tab-nav-profit');

        if (tabName === 'profit') {
            if (cartView) cartView.style.display = 'none';
            if (profitView) {
                profitView.style.display = 'block';
                renderProfitAdvisorTab();
            }
            if (btnCart) {
                btnCart.style.background = 'transparent';
                btnCart.style.color = '#64748b';
                btnCart.style.boxShadow = 'none';
            }
            if (btnProfit) {
                btnProfit.style.background = '#ffffff';
                btnProfit.style.color = '#047857';
                btnProfit.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06)';
            }
        } else {
            if (profitView) profitView.style.display = 'none';
            if (cartView) cartView.style.display = 'flex';
            if (btnCart) {
                btnCart.style.background = '#ffffff';
                btnCart.style.color = '#0f172a';
                btnCart.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06)';
            }
            if (btnProfit) {
                btnProfit.style.background = 'transparent';
                btnProfit.style.color = '#64748b';
                btnProfit.style.boxShadow = 'none';
            }
        }
    };

    window.openGrossProfitModal = () => {
        window.switchPosTab('profit');
    };

    window.renderProfitAdvisorTab = (testedDiscount = null) => {
        const container = document.getElementById('pos-profit-tab-view');
        if (!container) return;

        if (!cart.length) {
            container.innerHTML = `
                <div style="text-align:center; padding:36px 12px;">
                    <span class="material-symbols-rounded" style="font-size:48px; color:#cbd5e1;">query_stats</span>
                    <div style="font-weight:800; font-size:14px; color:#475569; margin-top:8px;">Cart is Empty</div>
                    <div style="font-size:12px; color:#94a3b8; margin-top:4px;">Add items to your cart from the left workspace to view real-time gross profit calculations and safe discount margins.</div>
                    <button type="button" class="btn btn-secondary" onclick="switchPosTab('cart')" style="margin-top:14px; font-weight:800; font-size:12px; border-radius:8px; display:inline-flex; align-items:center; gap:4px;">
                        <span class="material-symbols-rounded" style="font-size:15px;">arrow_back</span> Return to Cart
                    </button>
                </div>
            `;
            return;
        }

        const subtotal = cart.reduce((sum, item) => sum + roundMoney((Number(item.price || 0) * Number(item.quantity || 0)) - Number(item.discount || 0)), 0);
        const totalCost = cart.reduce((sum, item) => sum + roundMoney((Number(item.cost_price || 0) * Number(item.quantity || 0))), 0);
        const initialDiscount = testedDiscount !== null ? Number(testedDiscount) : roundMoney(globalDiscount);
        const maxSafeDiscount = Math.max(0, subtotal - totalCost);

        const disc = Math.max(0, Math.min(subtotal, roundMoney(initialDiscount)));
        const netRev = Math.max(0, subtotal - disc);
        const netProfit = netRev - totalCost;
        const marginPct = netRev > 0 ? ((netProfit / netRev) * 100).toFixed(1) : '0.0';
        const isLoss = netProfit < 0;
        const isBreakEven = netProfit === 0;
        const isLowMargin = !isLoss && !isBreakEven && Number(marginPct) < 15;

        let statusBg = '#f0fdf4';
        let statusBorder = '#bbf7d0';
        let statusColor = '#15803d';
        let statusIcon = 'verified';
        let statusHeadline = 'Healthy & Safe Margin';
        let statusDesc = `You retain a strong ${marginPct}% margin (${window.appFormatMoney(netProfit)}) after this discount.`;

        if (isLoss) {
            statusBg = '#fef2f2';
            statusBorder = '#fecaca';
            statusColor = '#dc2626';
            statusIcon = 'error';
            statusHeadline = 'Loss Alert: Below Purchase Cost!';
            statusDesc = `Warning! This discount exceeds your profit margin by ${window.appFormatMoney(Math.abs(netProfit))}. You will sell below cost.`;
        } else if (isBreakEven) {
            statusBg = '#fffbeb';
            statusBorder = '#fde68a';
            statusColor = '#d97706';
            statusIcon = 'info';
            statusHeadline = 'Break-Even Sale (Zero Profit)';
            statusDesc = `At this discount, you recover the exact purchase cost with zero net profit.`;
        } else if (isLowMargin) {
            statusBg = '#eff6ff';
            statusBorder = '#bfdbfe';
            statusColor = '#0284c7';
            statusIcon = 'warning';
            statusHeadline = 'Slim Margin Warning';
            statusDesc = `Remaining margin is ${marginPct}% (${window.appFormatMoney(netProfit)}). Apply with cashier discretion.`;
        }

        const buildItemRows = () => {
            return cart.map((item) => {
                const qty = Number(item.quantity || 1);
                const price = Number(item.price || 0);
                const cost = Number(item.cost_price || 0);
                const itemDisc = Number(item.discount || 0);
                const lineRevenue = roundMoney((price * qty) - itemDisc);
                const lineCost = roundMoney(cost * qty);
                const lineProfit = lineRevenue - lineCost;
                const lineMargin = lineRevenue > 0 ? ((lineProfit / lineRevenue) * 100).toFixed(1) : '0.0';
                const itemIsLoss = lineProfit < 0;

                return `
                    <tr style="border-bottom:1px solid #f1f5f9; font-size:11.5px;">
                        <td style="padding:6px 8px; font-weight:700; color:#0f172a;">
                            ${escapeHtml(item.name)}
                            ${item.model_number ? `<span style="font-size:10px; color:#64748b; margin-left:3px;">(${escapeHtml(item.model_number)})</span>` : ''}
                        </td>
                        <td style="padding:6px 4px; text-align:center; font-weight:700;">${qty}</td>
                        <td style="padding:6px 6px; text-align:right;">${window.appFormatMoney(price)}</td>
                        <td style="padding:6px 6px; text-align:right; color:#64748b;">${window.appFormatMoney(cost)}</td>
                        <td style="padding:6px 6px; text-align:right; font-weight:800; color:${itemIsLoss ? '#dc2626' : '#059669'};">
                            ${window.appFormatMoney(lineProfit)}
                        </td>
                        <td style="padding:6px 8px; text-align:right;">
                            <span style="display:inline-block; padding:1px 5px; border-radius:5px; font-weight:800; font-size:10.5px; background:${itemIsLoss ? '#fef2f2' : (Number(lineMargin) >= 20 ? '#ecfdf5' : '#eff6ff')}; color:${itemIsLoss ? '#dc2626' : (Number(lineMargin) >= 20 ? '#059669' : '#0284c7')};">
                                ${lineMargin}%
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');
        };

        container.innerHTML = `
            <div style="display:flex; flex-direction:column; gap:12px;">
                <!-- TAB TOP BAR -->
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span class="material-symbols-rounded" style="color:#059669; font-size:18px;">query_stats</span>
                        <strong style="font-size:13px; color:#0f172a;">Gross Profit Intelligence</strong>
                        <span style="font-size:10px; font-weight:800; background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0; border-radius:6px; padding:1px 6px;">Cashier Confidential</span>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="switchPosTab('cart')" style="padding:3px 9px; font-size:11px; font-weight:800; border-radius:7px; display:inline-flex; align-items:center; gap:3px;">
                        <span class="material-symbols-rounded" style="font-size:14px;">arrow_back</span> Cart
                    </button>
                </div>

                <!-- 4 KPI TILES (2x2 GRID) -->
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:8px 10px;">
                        <div style="font-size:10.5px; font-weight:800; color:#64748b; text-transform:uppercase; display:flex; align-items:center; gap:3px;">
                            <span class="material-symbols-rounded" style="font-size:13px; color:#0f6dff;">receipt_long</span> Net Bill
                        </div>
                        <div style="font-size:16px; font-weight:900; color:#0f172a; margin-top:2px;">${window.appFormatMoney(netRev)}</div>
                        <div style="font-size:10px; color:#94a3b8;">Subtotal: ${window.appFormatMoney(subtotal)}</div>
                    </div>

                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:8px 10px;">
                        <div style="font-size:10.5px; font-weight:800; color:#64748b; text-transform:uppercase; display:flex; align-items:center; gap:3px;">
                            <span class="material-symbols-rounded" style="font-size:13px; color:#64748b;">shopping_bag</span> Cost Basis
                        </div>
                        <div style="font-size:16px; font-weight:900; color:#475569; margin-top:2px;">${window.appFormatMoney(totalCost)}</div>
                        <div style="font-size:10px; color:#94a3b8;">Purchase Total</div>
                    </div>

                    <div style="background:${isLoss ? '#fef2f2' : '#ecfdf5'}; border:1px solid ${isLoss ? '#fecaca' : '#a7f3d0'}; border-radius:10px; padding:8px 10px;">
                        <div style="font-size:10.5px; font-weight:800; color:${isLoss ? '#dc2626' : '#047857'}; text-transform:uppercase; display:flex; align-items:center; gap:3px;">
                            <span class="material-symbols-rounded" style="font-size:13px; color:${isLoss ? '#dc2626' : '#059669'};">${isLoss ? 'trending_down' : 'trending_up'}</span> Gross Profit
                        </div>
                        <div style="font-size:16px; font-weight:900; color:${isLoss ? '#dc2626' : '#047857'}; margin-top:2px;">${window.appFormatMoney(netProfit)}</div>
                        <div style="font-size:10px; color:${isLoss ? '#b91c1c' : '#059669'};">Net Margin Gain</div>
                    </div>

                    <div style="background:${isLoss ? '#fef2f2' : (Number(marginPct) >= 20 ? '#ecfdf5' : '#eff6ff')}; border:1px solid ${isLoss ? '#fecaca' : (Number(marginPct) >= 20 ? '#a7f3d0' : '#bfdbfe')}; border-radius:10px; padding:8px 10px;">
                        <div style="font-size:10.5px; font-weight:800; color:${isLoss ? '#dc2626' : (Number(marginPct) >= 20 ? '#047857' : '#0284c7')}; text-transform:uppercase; display:flex; align-items:center; gap:3px;">
                            <span class="material-symbols-rounded" style="font-size:13px; color:${isLoss ? '#dc2626' : (Number(marginPct) >= 20 ? '#059669' : '#0284c7')};">query_stats</span> Margin Ratio
                        </div>
                        <div style="font-size:16px; font-weight:900; color:${isLoss ? '#dc2626' : (Number(marginPct) >= 20 ? '#047857' : '#0284c7')}; margin-top:2px;">${marginPct}%</div>
                        <div style="font-size:10px; color:#64748b;">Profit Percentage</div>
                    </div>
                </div>

                <!-- DISCOUNT SIMULATOR & SAFE MARGIN ADVISOR -->
                <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px; box-shadow:0 1px 4px rgba(0,0,0,0.03);">
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px; margin-bottom:8px;">
                        <div style="display:flex; align-items:center; gap:4px;">
                            <span class="material-symbols-rounded" style="color:#0f6dff; font-size:16px;">tune</span>
                            <strong style="font-size:12px; color:#0f172a;">Discount & Safe Margin Simulator</strong>
                        </div>
                        <span style="font-size:10.5px; font-weight:800; color:#059669; background:#ecfdf5; padding:2px 7px; border-radius:6px; border:1px solid #a7f3d0;">
                            Max Safe Discount: ${window.appFormatMoney(maxSafeDiscount)}
                        </span>
                    </div>

                    <!-- INPUT & APPLY BUTTON -->
                    <div style="display:flex; gap:6px; margin-bottom:8px;">
                        <input id="tab-sim-discount-input" type="number" min="0" step="1" inputmode="numeric" value="${disc}" class="form-input" style="font-size:14px; font-weight:800; padding:6px 10px; flex:1;" placeholder="Enter discount to test">
                        <button type="button" id="tab-apply-sim-discount-btn" class="btn btn-primary" style="background:linear-gradient(135deg, #10b981, #059669); border:none; font-weight:800; font-size:12px; border-radius:8px; padding:6px 12px; display:inline-flex; align-items:center; gap:4px; white-space:nowrap; cursor:pointer;">
                            <span class="material-symbols-rounded" style="font-size:16px;">price_check</span> Apply
                        </button>
                    </div>

                    <!-- QUICK PRESETS -->
                    <div style="display:flex; gap:4px; flex-wrap:wrap; align-items:center; margin-bottom:8px;">
                        <button type="button" class="tab-chip-btn" data-val="0" style="padding:2px 7px; font-size:10.5px; font-weight:800; border:1px solid #cbd5e1; background:#f8fafc; border-radius:5px; cursor:pointer;">Rs 0</button>
                        <button type="button" class="tab-chip-btn" data-val="50" style="padding:2px 7px; font-size:10.5px; font-weight:800; border:1px solid #cbd5e1; background:#f8fafc; border-radius:5px; cursor:pointer;">+50</button>
                        <button type="button" class="tab-chip-btn" data-val="100" style="padding:2px 7px; font-size:10.5px; font-weight:800; border:1px solid #cbd5e1; background:#f8fafc; border-radius:5px; cursor:pointer;">+100</button>
                        <button type="button" class="tab-chip-btn" data-val="200" style="padding:2px 7px; font-size:10.5px; font-weight:800; border:1px solid #cbd5e1; background:#f8fafc; border-radius:5px; cursor:pointer;">+200</button>
                        <button type="button" class="tab-chip-btn" data-val="${Math.round(subtotal * 0.05)}" style="padding:2px 7px; font-size:10.5px; font-weight:800; border:1px solid #cbd5e1; background:#f8fafc; border-radius:5px; cursor:pointer;">5% (${window.appFormatMoney(Math.round(subtotal * 0.05))})</button>
                        <button type="button" class="tab-chip-btn" data-val="${Math.round(subtotal * 0.10)}" style="padding:2px 7px; font-size:10.5px; font-weight:800; border:1px solid #cbd5e1; background:#f8fafc; border-radius:5px; cursor:pointer;">10% (${window.appFormatMoney(Math.round(subtotal * 0.10))})</button>
                        <button type="button" class="tab-chip-btn" data-val="${maxSafeDiscount}" style="padding:2px 7px; font-size:10.5px; font-weight:800; border:1px solid #a7f3d0; background:#ecfdf5; color:#047857; border-radius:5px; cursor:pointer;">Max Safe</button>
                    </div>

                    <!-- LIVE STATUS ALERT -->
                    <div style="background:${statusBg}; border:1px solid ${statusBorder}; border-radius:8px; padding:8px 10px; display:flex; align-items:center; gap:8px;">
                        <span class="material-symbols-rounded" style="color:${statusColor}; font-size:20px; flex-shrink:0;">${statusIcon}</span>
                        <div style="flex:1;">
                            <strong style="color:${statusColor}; font-size:11.5px; display:block;">${statusHeadline}</strong>
                            <span style="font-size:10.5px; color:#475569;">${statusDesc}</span>
                        </div>
                    </div>
                </div>

                <!-- ITEM PROFIT BREAKDOWN TABLE -->
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <div style="font-size:11.5px; font-weight:800; color:#334155; text-transform:uppercase; display:flex; align-items:center; gap:4px;">
                            <span class="material-symbols-rounded" style="font-size:15px; color:#0f6dff;">format_list_bulleted</span>
                            Item Profit & Cost Breakdown
                        </div>
                        <span style="font-size:10.5px; color:#64748b;">${cart.length} item(s)</span>
                    </div>

                    <div style="max-height:160px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px;">
                        <table style="width:100%; border-collapse:collapse; font-size:11px;">
                            <thead style="background:#f8fafc; border-bottom:1px solid #e2e8f0; position:sticky; top:0; z-index:2;">
                                <tr>
                                    <th style="padding:5px 8px; text-align:left; font-weight:800; color:#475569;">Item</th>
                                    <th style="padding:5px 4px; text-align:center; font-weight:800; color:#475569;">Qty</th>
                                    <th style="padding:5px 6px; text-align:right; font-weight:800; color:#475569;">Sale</th>
                                    <th style="padding:5px 6px; text-align:right; font-weight:800; color:#475569;">Cost</th>
                                    <th style="padding:5px 6px; text-align:right; font-weight:800; color:#475569;">Profit</th>
                                    <th style="padding:5px 8px; text-align:right; font-weight:800; color:#475569;">Margin</th>
                                </tr>
                            </thead>
                            <tbody>${buildItemRows()}</tbody>
                        </table>
                    </div>
                </div>

                <!-- BOTTOM RETURN TO CART BUTTON -->
                <button type="button" class="btn btn-secondary" onclick="switchPosTab('cart')" style="width:100%; padding:10px; font-weight:800; font-size:12.5px; border-radius:10px; display:flex; align-items:center; justify-content:center; gap:6px; cursor:pointer;">
                    <span class="material-symbols-rounded" style="font-size:17px;">arrow_back</span> Return to Cart Checkout
                </button>
            </div>
        `;

        // Event listeners for discount testing
        const inputEl = document.getElementById('tab-sim-discount-input');
        const applyBtn = document.getElementById('tab-apply-sim-discount-btn');

        inputEl?.addEventListener('change', () => {
            renderProfitAdvisorTab(Number(inputEl.value || 0));
        });

        container.querySelectorAll('.tab-chip-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const val = Number(btn.dataset.val || 0);
                renderProfitAdvisorTab(val);
            });
        });

        applyBtn?.addEventListener('click', () => {
            const finalDiscount = Number(document.getElementById('tab-sim-discount-input')?.value || 0);
            globalDiscount = Math.max(0, Math.min(subtotal, roundMoney(finalDiscount)));
            renderCart();
            switchPosTab('cart');
            window.appToast?.(`Discount of ${window.appFormatMoney(globalDiscount)} applied to invoice!`, 'success');
        });
    };

    window.holdSale = async () => {
        if (!cart.length) {
            window.appToast('Cart is empty. Add products before holding sale.', 'error');
            return;
        }
        await processCheckout('draft');
        fetchHeldSalesCount();
    };

    const renderCart = () => {
        const table = document.getElementById('cart-table');
        const tbody = document.getElementById('cart-table-body');
        const emptyIllu = document.getElementById('cart-empty-illustration');

        if (!cart.length) {
            if (table) table.style.display = 'none';
            if (emptyIllu) emptyIllu.style.display = 'block';
            updateTotals();
            persistCheckoutState();
            return;
        }

        if (emptyIllu) emptyIllu.style.display = 'none';
        if (table) table.style.display = 'table';

        if (tbody) {
            tbody.innerHTML = cart.map((item, index) => {
                const total = roundMoney((item.price * item.quantity) - item.discount);
                const isWeighted = isWeightedItem(item);

                return `
                    <tr>
                        <td style="font-weight:700; color:#94a3b8; font-size:11.5px;">${index + 1}</td>
                        <td>
                            <strong style="color:#0f172a;">${escapeHtml(item.name)}</strong>
                            <div style="font-size:11px; color:#64748b; font-weight:700;">
                                Unit: ${escapeHtml(item.unit_label || item.unit_type || 'pcs')}
                                ${isWeighted ? `
                                    <button type="button" onclick="openPosWeightModal(${index})" title="Calculate Weight & Price" style="border:1px solid #bae6fd; background:#f0f9ff; color:#0284c7; font-size:10.5px; font-weight:800; border-radius:6px; padding:2px 6px; display:inline-flex; align-items:center; gap:2px; cursor:pointer; margin-left:4px;">
                                        <span class="material-symbols-rounded" style="font-size:13px;">scale</span> Weight
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                        <td style="text-align:right;">
                            <input class="form-input" type="number" min="0" step="any" inputmode="numeric" value="${item.price}" onchange="updatePrice(${index}, this.value)" style="width:68px; padding:4px 6px; font-size:12px; font-weight:800; text-align:right;">
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; align-items:center; justify-content:center; gap:3px;">
                                <button class="qty-btn" type="button" onclick="updateQty(${index}, -1)">-</button>
                                <input class="qty-input" type="number" min="0.001" step="any" value="${item.quantity}" onchange="setQty(${index}, this.value)">
                                <button class="qty-btn" type="button" onclick="updateQty(${index}, 1)">+</button>
                            </div>
                        </td>
                        <td style="text-align:right; font-weight:900; color:#0f172a;">
                            ${window.appFormatMoney(total)}
                            ${item.discount > 0 ? `<div style="font-size:10.5px; color:#be123c;">-${window.appFormatMoney(item.discount)}</div>` : ''}
                        </td>
                        <td style="text-align:center;">
                            <button class="cart-remove-btn" type="button" onclick="promptRemoveFromCart(${index})" title="Remove item">
                                <span class="material-symbols-rounded" style="font-size:16px;">delete</span>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        updateTotals();
        persistCheckoutState();
    };

    const updateTotals = () => {
        let subtotal = 0;
        let totalItemsCount = cart.length;
        let totalQuantitySum = 0;
        let totalCostSum = 0;

        cart.forEach((item) => {
            subtotal += roundMoney((item.price * item.quantity) - item.discount);
            totalQuantitySum += (parseFloat(item.quantity) || 0);
            totalCostSum += roundMoney((Number(item.cost_price || 0) * (parseFloat(item.quantity) || 0)));
        });

        if (globalDiscount > subtotal) globalDiscount = subtotal;
        const taxBase = roundMoney(subtotal - globalDiscount);
        const taxAmount = taxSettings.enabled ? roundMoney(taxBase * (Number(taxSettings.rate || 0) / 100)) : 0;
        const total = roundMoney(taxBase + taxAmount);

        // Update Top Stats Bar
        const statItemsCount = document.getElementById('stat-items-count');
        const statTotalQty = document.getElementById('stat-total-qty');
        const statTotalAmount = document.getElementById('stat-total-amount');

        if (statItemsCount) statItemsCount.textContent = totalItemsCount;
        if (statTotalQty) statTotalQty.textContent = Number.isInteger(totalQuantitySum) ? String(totalQuantitySum) : String(Math.round(totalQuantitySum * 1000) / 1000).replace(/0+$/, '').replace(/\.$/, '');
        if (statTotalAmount) statTotalAmount.textContent = window.appFormatMoney(total);

        // Update Cart Summary Panel
        const subtotalVal = document.getElementById('subtotal-val');
        const discountVal = document.getElementById('discount-val');
        const totalVal = document.getElementById('total-val');

        if (subtotalVal) subtotalVal.innerText = window.appFormatMoney(subtotal);
        if (discountVal) discountVal.innerText = window.appFormatMoney(globalDiscount);

        const taxRow = document.getElementById('tax-row');
        if (taxRow) {
            taxRow.style.display = taxSettings.enabled ? 'flex' : 'none';
            document.getElementById('tax-label-val').innerText = `${taxSettings.label || 'Tax'} (${Number(taxSettings.rate || 0)}%)`;
            document.getElementById('tax-val').innerText = window.appFormatMoney(taxAmount);
        }
        if (totalVal) totalVal.innerText = window.appFormatMoney(total);

        // Update Tab Badges & Gross Profit Indicators
        const cartBadge = document.getElementById('tab-cart-count-badge');
        if (cartBadge) cartBadge.textContent = totalItemsCount;

        // Update Mobile Floating Quick Cart Bar
        const mobileCartBar = document.getElementById('pos-mobile-cart-bar');
        if (mobileCartBar) {
            if (totalItemsCount > 0) {
                mobileCartBar.classList.add('has-items');
                const countEl = document.getElementById('pos-mobile-cart-count');
                const totalEl = document.getElementById('pos-mobile-cart-total');
                if (countEl) countEl.textContent = `${totalItemsCount} ${totalItemsCount === 1 ? 'item' : 'items'}`;
                if (totalEl) totalEl.textContent = window.appFormatMoney(total);
            } else {
                mobileCartBar.classList.remove('has-items');
            }
        }

        const currentNetRev = Math.max(0, subtotal - globalDiscount);
        const currentGrossProfit = roundMoney(currentNetRev - totalCostSum);
        const currentMarginPct = currentNetRev > 0 ? ((currentGrossProfit / currentNetRev) * 100).toFixed(1) : '0.0';

        // Update Gross Profit Live Strip in Cart Totals Box
        const profitValEl = document.getElementById('cart-gross-profit-val');
        const marginValEl = document.getElementById('cart-margin-pct-val');
        const tabProfitPill = document.getElementById('tab-profit-pill');

        if (profitValEl) {
            profitValEl.textContent = window.appFormatMoney(currentGrossProfit);
            if (currentGrossProfit < 0) {
                profitValEl.style.color = '#dc2626';
            } else if (currentGrossProfit === 0 && totalItemsCount > 0) {
                profitValEl.style.color = '#d97706';
            } else {
                profitValEl.style.color = '#047857';
            }
        }

        if (marginValEl) {
            marginValEl.textContent = `${currentMarginPct}%`;
            if (currentGrossProfit < 0) {
                marginValEl.style.background = '#fef2f2';
                marginValEl.style.color = '#dc2626';
            } else if (Number(currentMarginPct) < 15 && totalItemsCount > 0) {
                marginValEl.style.background = '#eff6ff';
                marginValEl.style.color = '#0284c7';
            } else {
                marginValEl.style.background = '#dcfce7';
                marginValEl.style.color = '#15803d';
            }
        }

        // Update Live Pill on Gross Profit Tab Switcher Button
        if (tabProfitPill) {
            if (totalItemsCount > 0) {
                tabProfitPill.style.display = 'inline-block';
                tabProfitPill.textContent = `${window.appFormatMoney(currentGrossProfit)} (${currentMarginPct}%)`;
                if (currentGrossProfit < 0) {
                    tabProfitPill.style.background = '#fef2f2';
                    tabProfitPill.style.color = '#dc2626';
                } else {
                    tabProfitPill.style.background = '#ecfdf5';
                    tabProfitPill.style.color = '#047857';
                }
            } else {
                tabProfitPill.style.display = 'none';
            }
        }

        if (currentPosRightTab === 'profit') {
            renderProfitAdvisorTab();
        }
    };

    window.scrollToPosCart = () => {
        const cartEl = document.querySelector('.cart-shell');
        if (cartEl) {
            cartEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    window.openPosWeightModal = (index) => {
        const item = cart[index];
        if (!item) return;

        const basePrice = Number(item.price || 0);
        const currency = window.appCurrencySymbol || 'Rs';
        let currentUnit = 'g';

        const renderModalBody = () => {
            return `
                <div style="padding: 4px 0;">
                    <div style="display:flex; background:#f1f5f9; border-radius:12px; padding:4px; margin-bottom:14px;">
                        <button type="button" id="pos-weight-tab-g" style="flex:1; padding:8px; text-align:center; border-radius:9px; border:none; font-size:13px; font-weight:800; cursor:pointer; transition:all 0.15s ease; background:#ffffff; color:#0f6dff; box-shadow:0 2px 6px rgba(0,0,0,0.08);">Grams (g)</button>
                        <button type="button" id="pos-weight-tab-kg" style="flex:1; padding:8px; text-align:center; border-radius:9px; border:none; font-size:13px; font-weight:800; cursor:pointer; transition:all 0.15s ease; background:transparent; color:#64748b;">Kilograms (kg)</button>
                    </div>

                    <div class="form-group" style="margin-bottom:14px;">
                        <label class="form-label" id="pos-weight-input-label" style="font-size:12px; font-weight:800; color:#334155;">Enter Weight in Grams (e.g. 200)</label>
                        <input id="pos-weight-input" class="form-input" type="number" step="any" min="0.001" placeholder="e.g. 200" style="font-size:18px; font-weight:800; padding:10px 14px;">
                    </div>

                    <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:16px; padding:14px; text-align:center; margin-bottom:10px;">
                        <div style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase;">Calculated Item Price</div>
                        <div id="pos-weight-calc-price" style="font-size:28px; font-weight:900; color:#10b981; margin:4px 0;">${currency} 0.00</div>
                        <div id="pos-weight-calc-formula" style="font-size:11px; color:#64748b; font-family:monospace;">0g ÷ 1000 = 0.000 kg × ${basePrice} = ${currency} 0.00</div>
                    </div>
                </div>
            `;
        };

        window.appModal.open({
            title: `Weight Calculator: ${escapeHtml(item.name)}`,
            body: renderModalBody(),
            actions: `
                <button class="btn btn-secondary" type="button" data-modal-close>Cancel</button>
                <button class="btn btn-primary" type="button" id="pos-weight-apply-btn" style="background:#10b981; border-color:#059669; font-weight:800;">Apply Weight & Price</button>
            `
        });

        const inputEl = document.getElementById('pos-weight-input');
        const priceEl = document.getElementById('pos-weight-calc-price');
        const formulaEl = document.getElementById('pos-weight-calc-formula');
        const tabG = document.getElementById('pos-weight-tab-g');
        const tabKg = document.getElementById('pos-weight-tab-kg');
        const labelEl = document.getElementById('pos-weight-input-label');

        const recalculate = () => {
            const rawVal = parseFloat(inputEl?.value || '0');
            if (isNaN(rawVal) || rawVal <= 0) {
                if (priceEl) priceEl.textContent = `${currency} 0.00`;
                if (formulaEl) formulaEl.textContent = `0 ${currentUnit} × ${basePrice} = ${currency} 0.00`;
                return;
            }

            let decimalQty = (currentUnit === 'g') ? (rawVal / 1000) : rawVal;
            decimalQty = Math.round(decimalQty * 1000) / 1000;
            const linePrice = Math.round(decimalQty * basePrice * 100) / 100;

            if (priceEl) priceEl.textContent = `${currency} ${linePrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            if (formulaEl) {
                if (currentUnit === 'g') {
                    formulaEl.textContent = `${rawVal}g ÷ 1000 = ${decimalQty} kg × ${basePrice} = ${currency} ${linePrice.toFixed(2)}`;
                } else {
                    formulaEl.textContent = `${decimalQty} kg × ${basePrice} = ${currency} ${linePrice.toFixed(2)}`;
                }
            }
        };

        tabG.onclick = () => {
            currentUnit = 'g';
            tabG.style.background = '#ffffff';
            tabG.style.color = '#0f6dff';
            tabG.style.boxShadow = '0 2px 6px rgba(0,0,0,0.08)';
            tabKg.style.background = 'transparent';
            tabKg.style.color = '#64748b';
            tabKg.style.boxShadow = 'none';
            if (labelEl) labelEl.textContent = 'Enter Weight in Grams (e.g. 200)';
            if (inputEl) inputEl.placeholder = 'e.g. 200';
            recalculate();
            inputEl?.focus();
        };

        tabKg.onclick = () => {
            currentUnit = 'kg';
            tabKg.style.background = '#ffffff';
            tabKg.style.color = '#0f6dff';
            tabKg.style.boxShadow = '0 2px 6px rgba(0,0,0,0.08)';
            tabG.style.background = 'transparent';
            tabG.style.color = '#64748b';
            tabG.style.boxShadow = 'none';
            if (labelEl) labelEl.textContent = 'Enter Weight in Kilograms (e.g. 1.5)';
            if (inputEl) inputEl.placeholder = 'e.g. 1.5';
            recalculate();
            inputEl?.focus();
        };

        inputEl?.addEventListener('input', recalculate);

        if (item.quantity > 0) {
            if (item.quantity < 1) {
                tabG.click();
                if (inputEl) inputEl.value = Math.round(item.quantity * 1000);
            } else {
                tabKg.click();
                if (inputEl) inputEl.value = item.quantity;
            }
            recalculate();
        }

        setTimeout(() => inputEl?.focus(), 150);

        document.getElementById('pos-weight-apply-btn').onclick = () => {
            const rawVal = parseFloat(inputEl?.value || '0');
            if (isNaN(rawVal) || rawVal <= 0) {
                window.appToast?.('Please enter a valid weight.', 'error');
                return;
            }

            let decimalQty = (currentUnit === 'g') ? (rawVal / 1000) : rawVal;
            decimalQty = Math.round(decimalQty * 1000) / 1000;

            if (item.id && currentTransactionType !== 'Return' && currentTransactionType !== 'Refund') {
                if (decimalQty > (item.max_stock || 0)) {
                    window.appToast?.(`Only ${item.max_stock} unit(s) available in stock.`, 'error');
                    return;
                }
            }

            item.quantity = decimalQty;
            renderCart();
            window.appModal.close();
            window.appToast?.(`Weight applied: ${decimalQty} kg`, 'success');
        };
    };

    window.addToCart = (product, quantityToAdd = 1) => {
        const addQty = Math.max(0.001, parseFloat(quantityToAdd) || 1);
        const costPrice = roundMoney(product.cost_price ?? product.cost ?? 0);
        const retailPrice = roundMoney(product.retail_price ?? product.price ?? 0);
        const wholesalePrice = roundMoney(product.wholesale_price ?? product.price ?? retailPrice);
        const selectedPrice = currentSaleChannel === 'wholesale' ? wholesalePrice : retailPrice;
        const existing = cart.find((item) => item.id === product.id && item.model_number === product.model_number);
        const currentQty = existing ? existing.quantity : 0;

        if (product.id && currentTransactionType !== 'Return' && currentTransactionType !== 'Refund') {
            const stockLimit = Number(product.max_stock ?? product.stock ?? 0);
            if (stockLimit <= 0) {
                if (typeof playScannerBeep === 'function') {
                    playScannerBeep('error');
                } else if (typeof window.playScannerBeep === 'function') {
                    window.playScannerBeep('error');
                }
                window.appToast?.(`"${product.name || 'Item'}" is Out of Stock! (Stock: 0)`, 'error');
                return false;
            }
            if (currentQty + addQty > stockLimit) {
                if (typeof playScannerBeep === 'function') {
                    playScannerBeep('error');
                } else if (typeof window.playScannerBeep === 'function') {
                    window.playScannerBeep('error');
                }
                window.appToast?.(`Only ${stockLimit} unit(s) available in stock for "${product.name || 'Item'}".`, 'error');
                return false;
            }
        }

        if (existing) {
            existing.quantity = Math.round((existing.quantity + addQty) * 1000) / 1000;
            existing.scan_type = product.scan_type || existing.scan_type || 'single';
            existing.scan_label = product.scan_label || existing.scan_label || '';
            existing.scan_quantity = Math.max(1, Number(product.scan_quantity || addQty || existing.scan_quantity || 1));
            existing.matched_code = product.matched_code || product.barcode || existing.matched_code || '';
            if (costPrice > 0 && !existing.cost_price) {
                existing.cost_price = costPrice;
            }
        } else {
            cart.push({
                ...product,
                cost_price: costPrice,
                retail_price: retailPrice,
                wholesale_price: wholesalePrice,
                price: selectedPrice,
                quantity: addQty,
                discount: 0,
                scan_type: product.scan_type || 'single',
                scan_label: product.scan_label || '',
                scan_quantity: Math.max(1, Number(product.scan_quantity || addQty || 1)),
                matched_code: product.matched_code || product.barcode || ''
            });
        }
        renderCart();
        notifyItemAdded(product);
        return true;
    };

    window.updateQty = (index, diff) => {
        const item = cart[index];
        const newQty = Math.round((item.quantity + diff) * 1000) / 1000;

        if (newQty > 0 && item.id && currentTransactionType !== 'Return' && currentTransactionType !== 'Refund') {
            if (newQty > (item.max_stock || 0)) {
                window.appToast(`Only ${item.max_stock} unit(s) available in stock.`, 'error');
                return;
            }
        }

        item.quantity = newQty;
        if (item.quantity <= 0) cart.splice(index, 1);
        renderCart();
    };

    window.setQty = (index, value) => {
        const qty = parseFloat(value);
        const item = cart[index];

        if (qty > 0 && item.id && currentTransactionType !== 'Return' && currentTransactionType !== 'Refund') {
            if (qty > (item.max_stock || 0)) {
                window.appToast(`Only ${item.max_stock} unit(s) available in stock.`, 'error');
                item.quantity = Number(item.max_stock || 0);
                renderCart();
                return;
            }
        }

        if (!qty || qty <= 0 || isNaN(qty)) {
            cart.splice(index, 1);
        } else {
            item.quantity = Math.round(qty * 1000) / 1000;
        }
        renderCart();
    };

    window.updatePrice = (index, value) => {
        const price = roundMoney(value);
        cart[index].price = isNaN(price) ? cart[index].price : price;
        renderCart();
    };

    window.removeFromCart = (index, quantityToRemove = null) => {
        const item = cart[index];
        if (!item) return;

        const removeQty = quantityToRemove === null ? item.quantity : Math.max(0.001, parseFloat(quantityToRemove) || 1);
        if (removeQty >= item.quantity) {
            cart.splice(index, 1);
        } else {
            item.quantity = Math.round((item.quantity - removeQty) * 1000) / 1000;
        }
        renderCart();
    };

    window.promptRemoveFromCart = (index) => {
        const item = cart[index];
        if (!item) return;

        if (item.quantity <= 1) {
            window.appModal.open({
                title: 'Remove Item',
                maxWidth: '420px',
                body: `
                    <div style="padding:4px 0;">
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px;">
                            <div style="width:40px; height:40px; border-radius:10px; background:#fff1f2; color:#ef4444; display:grid; place-items:center; flex-shrink:0;">
                                <span class="material-symbols-rounded" style="font-size:22px;">delete</span>
                            </div>
                            <div>
                                <div style="font-weight:850; font-size:14px; color:#0f172a;">Remove ${escapeHtml(item.name)}?</div>
                                <div style="font-size:12px; color:#64748b; margin-top:2px;">This item will be removed from checkout.</div>
                            </div>
                        </div>
                    </div>
                `,
                actions: `
                    <button class="btn btn-secondary" type="button" data-modal-close>Cancel</button>
                    <button class="btn btn-danger" type="button" id="confirm-remove-cart-item" style="background:#dc2626; display:inline-flex; align-items:center; gap:4px;">
                        <span class="material-symbols-rounded" style="font-size:16px;">delete</span> Remove
                    </button>
                `
            });
            document.getElementById('confirm-remove-cart-item').onclick = () => {
                window.appModal.close();
                removeFromCart(index, item.quantity);
            };
            return;
        }

        window.appModal.open({
            title: 'Remove Quantity',
            maxWidth: '460px',
            body: `
                <div style="padding:4px 0;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
                        <div style="width:38px; height:38px; border-radius:8px; background:#fee2e2; color:#dc2626; display:grid; place-items:center; flex-shrink:0;">
                            <span class="material-symbols-rounded" style="font-size:20px;">remove_shopping_cart</span>
                        </div>
                        <div style="min-width:0; flex:1;">
                            <div style="font-weight:850; font-size:13.5px; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(item.name)}</div>
                            <div style="font-size:11.5px; color:#64748b;">Available in cart: <strong style="color:#0f172a;">${item.quantity} ${escapeHtml(item.unit_label || 'units')}</strong></div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="form-label" style="font-size:11.5px; font-weight:800; color:#334155; margin-bottom:4px; display:flex; justify-content:space-between; align-items:center;">
                            <span>Quantity to remove:</span>
                            <button type="button" id="btn-quick-fill-all" style="background:#eff6ff; color:#0f6dff; border:1px solid #bfdbfe; border-radius:6px; padding:2px 8px; font-size:11px; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:3px;">
                                <span class="material-symbols-rounded" style="font-size:14px;">done_all</span> All (${item.quantity})
                            </button>
                        </label>
                        <div style="position:relative; display:flex; align-items:center;">
                            <input id="remove-cart-qty-input" class="form-input" type="number" step="any" min="0.001" max="${item.quantity}" value="1" style="height:38px; font-size:14px; font-weight:800; padding-right:70px;">
                            <button type="button" id="btn-set-all-inside" title="Set to entire quantity (${item.quantity})" style="position:absolute; right:6px; height:26px; padding:0 8px; font-size:11px; font-weight:850; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:5px; cursor:pointer; display:flex; align-items:center; gap:2px;">
                                <span class="material-symbols-rounded" style="font-size:14px;">delete_sweep</span> All
                            </button>
                        </div>
                        <div id="remove-qty-validation-msg" style="display:none; color:#dc2626; font-size:11.5px; font-weight:800; margin-top:6px; align-items:center; gap:4px;">
                            <span class="material-symbols-rounded" style="font-size:15px;">error</span>
                            <span id="remove-qty-validation-text"></span>
                        </div>
                    </div>
                </div>
            `,
            actions: `
                <button class="btn btn-secondary" type="button" data-modal-close>Cancel</button>
                <button class="btn btn-danger" type="button" id="btn-remove-all-direct" style="background:#dc2626; display:inline-flex; align-items:center; gap:4px;" title="Remove entire item from cart in 1 click">
                    <span class="material-symbols-rounded" style="font-size:15px;">delete_forever</span> All (${item.quantity})
                </button>
                <button class="btn btn-primary" type="button" id="confirm-remove-cart-qty" style="background:#0f6dff; display:inline-flex; align-items:center; gap:4px;">
                    <span class="material-symbols-rounded" style="font-size:15px;">check</span> Remove
                </button>
            `
        });

        const input = document.getElementById('remove-cart-qty-input');
        const errorBox = document.getElementById('remove-qty-validation-msg');
        const errorText = document.getElementById('remove-qty-validation-text');
        const confirmBtn = document.getElementById('confirm-remove-cart-qty');
        const btnFillAll = document.getElementById('btn-quick-fill-all');
        const btnSetAllInside = document.getElementById('btn-set-all-inside');
        const btnRemoveAllDirect = document.getElementById('btn-remove-all-direct');

        const validate = () => {
            if (!input) return false;
            const val = parseFloat(input.value);
            if (isNaN(val) || val <= 0) {
                if (errorBox) {
                    errorText.textContent = 'Please enter a valid quantity greater than 0.';
                    errorBox.style.display = 'flex';
                }
                input.style.borderColor = '#ef4444';
                if (confirmBtn) confirmBtn.disabled = true;
                return false;
            }
            if (val > item.quantity) {
                if (errorBox) {
                    errorText.textContent = `Cannot remove ${val} units. Only ${item.quantity} available in cart.`;
                    errorBox.style.display = 'flex';
                }
                input.style.borderColor = '#ef4444';
                if (confirmBtn) confirmBtn.disabled = true;
                return false;
            }
            if (errorBox) errorBox.style.display = 'none';
            input.style.borderColor = '';
            if (confirmBtn) confirmBtn.disabled = false;
            return true;
        };

        if (input) {
            input.focus();
            input.select?.();
            input.addEventListener('input', validate);
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    confirmBtn?.click();
                }
            });
        }

        const setAll = () => {
            if (input) {
                input.value = item.quantity;
                validate();
                input.focus();
            }
        };

        if (btnFillAll) btnFillAll.onclick = setAll;
        if (btnSetAllInside) btnSetAllInside.onclick = setAll;

        if (btnRemoveAllDirect) {
            btnRemoveAllDirect.onclick = () => {
                window.appModal.close();
                removeFromCart(index, item.quantity);
                window.appToast(`Removed all ${item.quantity} units of ${item.name}`, 'warning');
            };
        }

        if (confirmBtn) {
            confirmBtn.onclick = () => {
                if (!validate()) return;
                const val = parseFloat(input.value);
                window.appModal.close();
                removeFromCart(index, val);
            };
        }
    };

    window.clearCart = async (skipConfirm = false) => {
        if (!cart.length) return;
        if (!skipConfirm) {
            const confirmed = typeof window.appConfirm === 'function'
                ? await window.appConfirm(
                    'Are you sure you want to clear all items from the current active register?',
                    'Clear Active Cart?',
                    {
                        confirmText: 'Yes, Clear Cart',
                        cancelText: 'Keep Cart',
                        icon: 'remove_shopping_cart',
                        isDanger: true
                    }
                )
                : true;
            if (!confirmed) return;
        }
        cart = [];
        globalDiscount = 0;
        renderCart();
        persistCheckoutState();
    };

    window.openDiscountModal = () => {
        const subtotal = cart.reduce((sum, item) => sum + roundMoney((Number(item.price || 0) * Number(item.quantity || 0)) - Number(item.discount || 0)), 0);
        const totalCost = cart.reduce((sum, item) => sum + roundMoney((Number(item.cost_price || 0) * Number(item.quantity || 0))), 0);
        const maxSafeDiscount = Math.max(0, subtotal - totalCost);

        const computeModalStats = (testDisc) => {
            const discVal = Math.max(0, Math.min(subtotal, roundMoney(testDisc || 0)));
            const netRev = Math.max(0, subtotal - discVal);
            const netProfit = roundMoney(netRev - totalCost);
            const marginPct = netRev > 0 ? ((netProfit / netRev) * 100).toFixed(1) : '0.0';
            return { discVal, netRev, netProfit, marginPct, isLoss: netProfit < 0 };
        };

        const initialStats = computeModalStats(globalDiscount);

        window.appModal.open({
            title: 'Invoice Discount & Safe Margin',
            body: `
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:8px 12px;">
                            <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Subtotal</div>
                            <div style="font-size:16px; font-weight:900; color:#0f172a; margin-top:2px;">${window.appFormatMoney(subtotal)}</div>
                        </div>
                        <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:10px; padding:8px 12px;">
                            <div style="font-size:11px; font-weight:800; color:#047857; text-transform:uppercase;">Max Safe Discount</div>
                            <div style="font-size:16px; font-weight:900; color:#047857; margin-top:2px;">${window.appFormatMoney(maxSafeDiscount)}</div>
                        </div>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-weight:800;">Discount Amount (${typeof currencySymbol !== 'undefined' ? currencySymbol : 'Rs'})</label>
                        <input id="invoice-discount-input" class="form-input" type="number" min="0" max="${subtotal}" step="1" inputmode="numeric" value="${globalDiscount}" style="font-size:16px; font-weight:800; padding:10px 14px;" placeholder="0" autofocus>
                    </div>

                    <!-- PRESETS -->
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <button type="button" class="disc-preset-chip" data-val="0" style="padding:3px 9px; font-size:11px; font-weight:800; border:1px solid #cbd5e1; background:#fff; border-radius:6px; cursor:pointer;">0</button>
                        <button type="button" class="disc-preset-chip" data-val="50" style="padding:3px 9px; font-size:11px; font-weight:800; border:1px solid #cbd5e1; background:#fff; border-radius:6px; cursor:pointer;">+50</button>
                        <button type="button" class="disc-preset-chip" data-val="100" style="padding:3px 9px; font-size:11px; font-weight:800; border:1px solid #cbd5e1; background:#fff; border-radius:6px; cursor:pointer;">+100</button>
                        <button type="button" class="disc-preset-chip" data-val="${Math.round(subtotal * 0.05)}" style="padding:3px 9px; font-size:11px; font-weight:800; border:1px solid #cbd5e1; background:#fff; border-radius:6px; cursor:pointer;">5%</button>
                        <button type="button" class="disc-preset-chip" data-val="${Math.round(subtotal * 0.10)}" style="padding:3px 9px; font-size:11px; font-weight:800; border:1px solid #cbd5e1; background:#fff; border-radius:6px; cursor:pointer;">10%</button>
                        <button type="button" class="disc-preset-chip" data-val="${maxSafeDiscount}" style="padding:3px 9px; font-size:11px; font-weight:800; border:1px solid #a7f3d0; background:#f0fdf4; color:#047857; border-radius:6px; cursor:pointer;">Max Safe (${window.appFormatMoney(maxSafeDiscount)})</button>
                    </div>

                    <!-- LIVE PROFIT PREVIEW -->
                    <div id="modal-profit-preview-box" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <span style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Estimated Gross Profit</span>
                            <div id="modal-live-profit" style="font-size:16px; font-weight:900; color:#047857; margin-top:1px;">${window.appFormatMoney(initialStats.netProfit)}</div>
                        </div>
                        <div style="text-align:right;">
                            <span style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Profit Margin</span>
                            <div id="modal-live-margin" style="font-size:16px; font-weight:900; color:#047857; margin-top:1px;">${initialStats.marginPct}%</div>
                        </div>
                    </div>
                </div>
            `,
            actions: `<button class="btn btn-secondary" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="button" id="save-invoice-discount" style="font-weight:800; padding:8px 20px;">Apply Discount</button>`
        });

        const inputEl = document.getElementById('invoice-discount-input');
        const profitEl = document.getElementById('modal-live-profit');
        const marginEl = document.getElementById('modal-live-margin');
        const boxEl = document.getElementById('modal-profit-preview-box');

        const refreshPreview = () => {
            const val = Number(inputEl ? inputEl.value : 0);
            const stats = computeModalStats(val);
            if (profitEl) {
                profitEl.textContent = window.appFormatMoney(stats.netProfit);
                profitEl.style.color = stats.isLoss ? '#dc2626' : '#047857';
            }
            if (marginEl) {
                marginEl.textContent = `${stats.marginPct}%`;
                marginEl.style.color = stats.isLoss ? '#dc2626' : '#047857';
            }
            if (boxEl) {
                boxEl.style.background = stats.isLoss ? '#fef2f2' : '#f8fafc';
                boxEl.style.borderColor = stats.isLoss ? '#fecaca' : '#e2e8f0';
            }
        };

        inputEl?.addEventListener('input', refreshPreview);

        document.querySelectorAll('.disc-preset-chip').forEach(btn => {
            btn.addEventListener('click', () => {
                if (inputEl) {
                    inputEl.value = btn.dataset.val;
                    refreshPreview();
                }
            });
        });

        document.getElementById('save-invoice-discount').onclick = () => {
            const rawVal = Number(document.getElementById('invoice-discount-input')?.value || 0);
            globalDiscount = roundMoney(Math.max(0, Math.min(subtotal, rawVal)));
            window.appModal.close();
            renderCart();
            persistCheckoutState();
        };
    };

    document.getElementById('custom-item-trigger')?.addEventListener('click', () => {
        window.appModal.open({
            title: 'Add Custom Product',
            body: `
                <div class="form-group"><label class="form-label">Product Name</label><input id="custom-name" class="form-input" type="text" placeholder="Product name"></div>
                <div class="form-group"><label class="form-label">Model Number</label><input id="custom-model" class="form-input" type="text" placeholder="Model number"></div>
                <div class="form-group"><label class="form-label">Price</label><input id="custom-price" class="form-input" type="number" min="0" step="1" inputmode="numeric" placeholder="0"></div>
            `,
            actions: `<button class="btn btn-secondary" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="button" id="save-custom-product">Add Product</button>`
        });

        document.getElementById('save-custom-product').onclick = () => {
            const name = document.getElementById('custom-name').value.trim();
            const model = document.getElementById('custom-model').value.trim();
            const price = roundMoney(document.getElementById('custom-price').value || 0);
            if (!name || isNaN(price) || price < 0) return;
            addToCart({
                id: null,
                name,
                model_number: model,
                cost_price: 0,
                price
            });
            window.appModal.close();
        };
    });

    const posSearchInput = document.getElementById('pos-search');
    const posCategorySelect = document.getElementById('pos-category-select');
    const productsEmptyState = document.getElementById('products-empty');
    const productsGridEl = document.getElementById('products-grid');
    const productsPagination = document.getElementById('products-pagination');
    const originalProductsHtml = productsGridEl ? productsGridEl.innerHTML : '';
    let productSearchTimer = null;
    let productSearchAbortController = null;

    const renderProductCard = (product) => {
        const productId = Number(product.id || 0);
        const productName = escapeHtml(product.name || '');
        const modelNumber = escapeHtml(product.model_number || '');
        const barcode = escapeHtml(product.barcode || '');
        const nameLc = escapeHtml(String(product.name || '').toLowerCase());
        const modelLc = escapeHtml(String(product.model_number || '').toLowerCase());
        const barcodeLc = escapeHtml(String(product.barcode || '').toLowerCase());
        const retailPrice = roundMoney(product.price || 0);
        const wholesalePrice = roundMoney(product.wholesale_price ?? product.price ?? 0);
        const costPrice = roundMoney(product.cost ?? product.cost_price ?? 0);
        const stock = Number(product.stock || 0);
        const minStock = Number(product.min_stock || 0);
        const unitType = String(product.unit_type || 'PCS').trim().toUpperCase() || 'PCS';
        const stockFormatted = Number.isInteger(stock) ? String(stock) : String(Math.round(stock * 1000) / 1000).replace(/0+$/, '').replace(/\.$/, '');
        const stockLabel = stock <= 0 ? 'Out' : `${stockFormatted} ${unitType}`;
        const unitValueRaw = Number(product.unit_value || 1);
        const unitValue = unitValueRaw > 0 ? unitValueRaw : 1;
        const unitValueText = Number.isInteger(unitValue) ? String(unitValue) : String(unitValue).replace(/0+$/, '').replace(/\.$/, '');
        const unitLabel = `${unitValueText} ${unitType}`;
        let imageSrc = productImageFields.map((field) => String(product[field] || '').trim()).find(Boolean) || '';
        if (imageSrc && !imageSrc.match(/^(?:https?:)?\/\//i)) {
            const b = (typeof window.BASE_URL !== 'undefined' ? window.BASE_URL : (typeof baseUrl !== 'undefined' ? baseUrl : '')) || '';
            imageSrc = (b ? b.replace(/\/+$/, '') : '') + '/' + imageSrc.replace(/^\/+/, '');
        }
        const imageFitChoice = (posImageFit === 'cover') ? 'contain' : posImageFit;
        const imageHtml = !showProductImages ? '' : `
            <div class="product-image-box">
                ${imageSrc
                    ? `<img src="${escapeHtml(imageSrc)}" alt="" style="object-fit:${imageFitChoice};" onerror="this.onerror=null; this.style.display=\'none\'; if (this.nextElementSibling) this.nextElementSibling.style.display=\'flex\';"><div class="product-image-placeholder" style="display:none;"><span class="material-symbols-rounded" style="color:#0f6dff; font-size:20px;">inventory_2</span></div>`
                    : `<div class="product-image-placeholder"><span class="material-symbols-rounded" style="color:#0f6dff; font-size:20px;">inventory_2</span></div>`}
            </div>
        `;

        const hasInfo = posShowName || posShowStock || posShowUnit;
        const infoHtml = !hasInfo ? '' : `
            <div class="product-card-info" style="min-width:0; flex:1;">
                ${posShowStock ? `<span class="stock-mini ${stock <= 0 ? 'out' : (stock <= minStock ? 'low' : '')}">Stock: ${stockLabel}</span>` : ''}
                ${posShowName ? `<h3>${productName}</h3>` : ''}
                ${posShowUnit ? `<div style="font-size:11.5px; color:#64748b; font-weight:700;">Unit: ${escapeHtml(unitLabel)}</div>` : ''}
            </div>
        `;

        const showFoot = posShowPrice || posCardLayout === 'standard';
        const footHtml = !showFoot ? '' : `
            <div class="product-card-foot" ${(!posShowPrice && posCardLayout === 'visual_1x1') ? 'style="display:none;"' : ''}>
                ${posShowPrice ? `<div class="product-price-display">${window.appFormatMoney(retailPrice)}</div>` : '<div></div>'}
                <button type="button" class="product-add-btn" title="Add to Cart">
                    <span class="material-symbols-rounded" style="font-size:18px;">add</span>
                </button>
            </div>
        `;

        return `
            <article class="product-card" data-name="${nameLc}" data-model="${modelLc}" data-barcode="${barcodeLc}" data-id="${productId}"
                data-product-name="${productName}" data-model-number="${modelNumber}" data-product-barcode="${barcode}"
                data-unit-label="${escapeHtml(unitLabel)}"
                data-unit-type="${escapeHtml(unitType)}"
                data-min-stock="${minStock}"
                data-cost-price="${costPrice}"
                data-retail-price="${retailPrice}" data-wholesale-price="${wholesalePrice}" data-stock="${stock}">
                <div class="product-card-main">
                    ${imageHtml}
                    ${infoHtml}
                </div>
                ${footHtml}
            </article>
        `;
    };

    const restorePaginatedProducts = () => {
        if (productsGridEl) {
            productsGridEl.innerHTML = originalProductsHtml;
        }
        if (productsPagination) {
            productsPagination.style.display = '';
        }
        if (productsEmptyState) {
            productsEmptyState.style.display = 'none';
        }
    };

    const searchProductsFromServer = async (query, category) => {
        if (!productsGridEl) return;
        if (productSearchAbortController) {
            productSearchAbortController.abort();
        }
        productSearchAbortController = new AbortController();

        const url = `${baseUrl}/pos/search?q=${encodeURIComponent(query)}&category=${encodeURIComponent(category)}`;
        const response = await fetch(scopeUrl(url), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: productSearchAbortController.signal
        });
        const results = await response.json();
        const list = Array.isArray(results) ? results : [];

        if (!list.length) {
            productsGridEl.innerHTML = '';
            if (productsEmptyState) {
                productsEmptyState.style.display = 'block';
            }
        } else {
            productsGridEl.innerHTML = list.map(renderProductCard).join('');
            if (productsEmptyState) {
                productsEmptyState.style.display = 'none';
            }
        }
        if (productsPagination) {
            productsPagination.style.display = 'none';
        }
    };

    const filterProducts = () => {
        const query = posSearchInput ? posSearchInput.value.trim() : '';
        const category = posCategorySelect ? posCategorySelect.value.trim() : '';

        clearTimeout(productSearchTimer);
        if (query.length < 1 && category === '') {
            restorePaginatedProducts();
            return;
        }

        productSearchTimer = setTimeout(async () => {
            try {
                await searchProductsFromServer(query, category);
            } catch (error) {
                if (error?.name !== 'AbortError') {
                    window.appToast('Search failed. Please try again.', 'error');
                }
            }
        }, 220);
    };

    if (posSearchInput) {
        posSearchInput.addEventListener('input', filterProducts);
        posSearchInput.addEventListener('search', filterProducts);
    }
    if (posCategorySelect) {
        posCategorySelect.addEventListener('change', filterProducts);
    }

    const renderPaymentMethodUI = () => {
        document.querySelectorAll('.payment-method-btn').forEach((button) => {
            const active = button.dataset.paymentMethod === currentPaymentMethod;
            button.classList.toggle('active', active);
        });
        if (creditExtraFields) {
            creditExtraFields.style.display = currentPaymentMethod === 'credit' ? 'block' : 'none';
        }
        if (currentPaymentMethod === 'credit') {
            setCustomerFormExpanded(true);
        }
    };

    const renderSaleChannelUI = () => {
        const retailBtn = document.getElementById('sale-channel-retail');
        const wholesaleBtn = document.getElementById('sale-channel-wholesale');
        retailBtn?.classList.toggle('active', currentSaleChannel === 'retail');
        wholesaleBtn?.classList.toggle('active', currentSaleChannel === 'wholesale');
    };

    const applySaleChannelToCart = () => {
        cart = cart.map((item) => {
            if (!item.id) {
                return item;
            }
            const nextPrice = currentSaleChannel === 'wholesale' ?
                Number(item.wholesale_price ?? item.price ?? 0) :
                Number(item.retail_price ?? item.price ?? 0);
            return {
                ...item,
                price: nextPrice
            };
        });
        renderCart();
    };

    document.querySelectorAll('.payment-method-btn').forEach((button) => {
        button.addEventListener('click', () => {
            currentPaymentMethod = button.dataset.paymentMethod || 'cash';
            renderPaymentMethodUI();
        });
    });

    document.getElementById('sale-channel-retail')?.addEventListener('click', () => {
        currentSaleChannel = 'retail';
        renderSaleChannelUI();
        applySaleChannelToCart();
        persistCheckoutState();
    });

    document.getElementById('sale-channel-wholesale')?.addEventListener('click', () => {
        currentSaleChannel = 'wholesale';
        renderSaleChannelUI();
        applySaleChannelToCart();
        persistCheckoutState();
    });

    window.switchSaleChannel = (channel) => {
        currentSaleChannel = channel;
        renderSaleChannelUI();
        applySaleChannelToCart();
        persistCheckoutState();
    };

    window.switchPaymentMethod = (method) => {
        currentPaymentMethod = method;
        renderPaymentMethodUI();
    };

    const productsGrid = document.getElementById('products-grid');
    if (productsGrid) {
        productsGrid.addEventListener('click', (event) => {
            const card = event.target.closest('.product-card');
            if (!card) return;
            addToCart({
                id: Number(card.dataset.id || 0),
                name: card.dataset.productName || '',
                model_number: card.dataset.modelNumber || '',
                barcode: card.dataset.productBarcode || '',
                unit_label: card.dataset.unitLabel || 'pcs',
                retail_price: Number(card.dataset.retailPrice || 0),
                wholesale_price: Number(card.dataset.wholesalePrice || 0),
                cost_price: Number(card.dataset.costPrice || 0),
                price: Number(card.dataset.retailPrice || 0),
                max_stock: Number(card.dataset.stock || 0)
            });
        });
    }

    const customerSuggestionBox = document.createElement('div');
    customerSuggestionBox.className = 'search-results';
    customerSuggestionBox.style.cssText = 'position:absolute; top:calc(100% + 4px); left:0; right:0; background:#ffffff; border:1px solid #cbd5e1; border-radius:10px; box-shadow:0 12px 30px -4px rgba(15,23,42,0.18), 0 0 0 1px rgba(15,23,42,0.06); max-height:240px; overflow-y:auto; z-index:1000; display:none; padding:4px;';
    if (customerNameInput?.parentNode) {
        customerNameInput.parentNode.style.position = 'relative';
        customerNameInput.parentNode.appendChild(customerSuggestionBox);
    }

    customerNameInput?.addEventListener('input', () => {
        const query = customerNameInput.value.trim();
        clearTimeout(customerSuggestTimer);
        if (query.length < 1) {
            customerSuggestionBox.style.display = 'none';
            return;
        }

        customerSuggestTimer = setTimeout(async () => {
            try {
                const res = await fetch(scopeUrl(`${baseUrl}/pos/customers?q=${encodeURIComponent(query)}`), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                const list = data.results || [];
                if (!list.length) {
                    customerSuggestionBox.style.display = 'none';
                    return;
                }

                customerSuggestionBox.innerHTML = list.map((customer) => (
                    `<button type="button" class="search-item" data-customer-pick="${Number(customer.id || 0)}" data-customer-name="${escapeHtml(customer.name || '')}" data-customer-phone="${escapeHtml(customer.phone || '')}" data-customer-address="${escapeHtml(customer.address || '')}" style="width:100%; text-align:left; border:none; background:#ffffff; border-radius:7px; padding:8px 10px; margin-bottom:2px; display:flex; align-items:center; justify-content:space-between; gap:8px; cursor:pointer; transition:background 0.15s ease;" onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background='#ffffff'">
                        <div style="min-width:0; flex:1;">
                            <strong style="display:block; font-size:12.5px; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(customer.name || '')}</strong>
                            <div style="font-size:11px; color:#64748b; margin-top:1px;">
                                ${customer.phone ? `<span>📞 ${escapeHtml(customer.phone)}</span>` : ''}
                                ${customer.address ? `<span style="margin-left:6px; color:#94a3b8;">📍 ${escapeHtml(customer.address)}</span>` : ''}
                            </div>
                        </div>
                        <span class="material-symbols-rounded" style="font-size:16px; color:#0f6dff; flex-shrink:0;">arrow_forward</span>
                    </button>`
                )).join('');
                customerSuggestionBox.style.display = 'block';
            } catch (error) {
                customerSuggestionBox.style.display = 'none';
            }
        }, 220);
    });

    document.addEventListener('click', (event) => {
        const picked = event.target.closest('[data-customer-pick]');
        if (picked) {
            if (customerNameInput) customerNameInput.value = picked.dataset.customerName || '';
            if (customerPhoneInput) customerPhoneInput.value = picked.dataset.customerPhone || '';
            if (customerAddressInput) customerAddressInput.value = picked.dataset.customerAddress || '';
            customerSuggestionBox.style.display = 'none';
            updateCustomerSummaryBadge();
            window.appToast?.(`Customer attached: ${picked.dataset.customerName}`, 'success');
            return;
        }

        if (!customerNameInput?.contains(event.target) && !customerSuggestionBox.contains(event.target)) {
            customerSuggestionBox.style.display = 'none';
        }
    });

    const deductLocalStock = (payload) => {
        const isReturn = payload.transaction_type === 'Return' || payload.transaction_type === 'Refund';
        payload.items.forEach(item => {
            const card = document.querySelector(`.product-card[data-id="${item.id}"]`);
            if (card) {
                const currentStock = Number(card.dataset.stock || 0);
                const minStock = Number(card.dataset.minStock || 5);
                const unitType = String(card.dataset.unitType || 'PCS').trim().toUpperCase() || 'PCS';
                const qtyDelta = parseFloat(item.quantity) || 0;
                const newStock = isReturn ? Math.round((currentStock + qtyDelta) * 1000) / 1000 : Math.max(0, Math.round((currentStock - qtyDelta) * 1000) / 1000);

                card.dataset.stock = newStock;
                const badge = card.querySelector('.stock-mini');
                if (badge) {
                    const formatted = Number.isInteger(newStock) ? String(newStock) : String(newStock).replace(/0+$/, '').replace(/\.$/, '');
                    badge.textContent = newStock <= 0 ? 'Stock: Out' : `Stock: ${formatted} ${unitType}`;
                    badge.className = `stock-mini ${newStock <= 0 ? 'out' : (newStock <= minStock ? 'low' : '')}`;
                }
            }
        });
    };

    const printOfflineReceipt = (payload) => {
        const subtotal = payload.items.reduce((sum, item) => sum + roundMoney((Number(item.price || 0) * Number(item.quantity || 0)) - Number(item.discount || 0)), 0);
        const taxBase = roundMoney(subtotal - Number(payload.discount || 0));
        const checkoutTax = taxSettings.enabled ? roundMoney(taxBase * (Number(taxSettings.rate || 0) / 100)) : 0;
        const checkoutTotal = roundMoney(taxBase + checkoutTax);
        const offlineDate = (payload.sale_date || '').trim();
        const dateObj = offlineDate ? new Date(`${offlineDate}T12:00:00`) : new Date();
        const dateStr = isNaN(dateObj.getTime()) ? new Date().toLocaleString() : dateObj.toLocaleDateString();

        let itemsHtml = '';
        payload.items.forEach(item => {
            const rawSub = roundMoney(Number(item.price || 0) * Number(item.quantity || 0));
            itemsHtml += `
                <div style="display:flex; justify-content:space-between; margin-bottom:4px; font-size:12px;">
                    <div>${item.name} x${item.quantity}<br><small style="color:#666;">${window.appFormatMoney(item.price)} each</small></div>
                    <div style="font-weight:bold;">${window.appFormatMoney(rawSub - Number(item.discount || 0))}</div>
                </div>
                ${item.discount > 0 ? `<div style="text-align:right; font-size:11px; color:#666; margin-top:-4px;">Discount: -${window.appFormatMoney(item.discount)}</div>` : ''}
                <div style="border-bottom:1px dashed #ccc; margin:6px 0;"></div>
            `;
        });

        const receiptHtml = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Offline Receipt</title>
                <style>
                    body { font-family: 'Courier New', Courier, monospace; width: 300px; margin: 0 auto; padding: 20px; color: #000; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .header h2 { margin: 0 0 5px; font-size: 18px; }
                    .row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size:13px; }
                    .total-row { display: flex; justify-content: space-between; margin-top: 10px; font-size: 16px; font-weight: bold; padding-top:10px; border-top: 2px solid #000; }
                    .footer { text-align: center; margin-top: 30px; font-size: 12px; border-top: 1px dashed #ccc; padding-top: 20px; }
                    @media print { body { width: 100%; margin: 0; padding: 0; } }
                </style>
            </head>
            <body onload="window.print(); setTimeout(() => window.close(), 1500);">
                <div class="header">
                    <h2>OFFLINE RECEIPT</h2>
                    <div>${dateStr}</div>
                    ${payload.customer_name ? `<div>Billed To: ${payload.customer_name}</div>` : ''}
                </div>
                <div style="border-bottom:2px solid #000; margin-bottom:10px;"></div>
                ${itemsHtml}
                <div class="row" style="margin-top:10px;">
                    <span>Subtotal</span>
                    <span>${window.appFormatMoney(subtotal)}</span>
                </div>
                <div class="row">
                    <span>Discount</span>
                    <span>-${window.appFormatMoney(payload.discount || 0)}</span>
                </div>
                ${taxSettings.enabled ? `<div class="row"><span>${taxSettings.label || 'Tax'} (${Number(taxSettings.rate || 0)}%)</span><span>${window.appFormatMoney(checkoutTax)}</span></div>` : ''}
                <div class="total-row">
                    <span>Total</span>
                    <span>${window.appFormatMoney(checkoutTotal)}</span>
                </div>
                <div class="row" style="margin-top:5px;">
                    <span>Method</span>
                    <span style="text-transform:capitalize;">${payload.payment_method}</span>
                </div>
                <div class="footer">
                    <p>Thank you for your business!</p>
                    <p><em>(Offline Mode Syncing in Background)</em></p>
                </div>
            </body>
            </html>
        `;

        const printWin = window.open('', '_blank');
        if (printWin) {
            printWin.document.write(receiptHtml);
            printWin.document.close();
        } else {
            alert("Pop-up blocked! Unable to print offline receipt.");
        }
    };

    window.directFastPrint = () => {
        if (!cart.length) {
            window.appToast?.('Cart is empty. Please add items before checking out.', 'error');
            return;
        }
        window.processCheckout('paid', true);
    };

    window.processCheckout = async (status, isDirectPrint = false) => {
        if (!cart.length) return;
        cart = cart.map((item) => ({
            ...item,
            retail_price: roundMoney(item.retail_price ?? item.price),
            wholesale_price: roundMoney(item.wholesale_price ?? item.price),
            cost_price: roundMoney(item.cost_price ?? item.cost ?? 0),
            price: roundMoney(item.price),
            discount: roundMoney(item.discount)
        }));
        globalDiscount = roundMoney(globalDiscount);
        const subtotal = cart.reduce((sum, item) => sum + roundMoney((Number(item.price || 0) * Number(item.quantity || 0)) - Number(item.discount || 0)), 0);
        const taxBase = roundMoney(subtotal - Number(globalDiscount || 0));
        const checkoutTax = taxSettings.enabled ? roundMoney(taxBase * (Number(taxSettings.rate || 0) / 100)) : 0;
        const checkoutTotal = roundMoney(taxBase + checkoutTax);
        const creditInitialPaymentInput = document.getElementById('credit-initial-payment');
        const creditInitialPaymentRaw = Number(creditInitialPaymentInput?.value || 0);
        const creditInitialPayment = Math.max(0, Math.min(checkoutTotal, isNaN(creditInitialPaymentRaw) ? 0 : creditInitialPaymentRaw));

        const payload = {
            items: cart,
            csrf_token: csrfToken,
            discount: globalDiscount,
            tax_amount: checkoutTax,
            customer_name: document.getElementById('customer-name').value,
            customer_phone: document.getElementById('customer-phone').value,
            customer_address: document.getElementById('customer-address').value,
            notes: document.getElementById('sale-note').value,
            sale_date: document.getElementById('sale-date')?.value || '',
            payment_method: currentPaymentMethod,
            sale_channel: currentSaleChannel,
            credit_due_date: document.getElementById('credit-due-date')?.value || '',
            credit_private_note: document.getElementById('credit-private-note')?.value || '',
            credit_initial_payment: currentPaymentMethod === 'credit' ? creditInitialPayment : 0,
            credit_initial_payment_method: document.getElementById('credit-initial-payment-method')?.value || 'cash',
            transaction_type: currentTransactionType,
            status
        };

        try {
            if (!navigator.onLine) throw new Error("offline");
            let response = await sendCheckoutRequest(payload);
            let data = await response.json();
            if (response.status === 419 || /security token mismatch/i.test(String(data.error || ''))) {
                await refreshCsrfToken();
                response = await sendCheckoutRequest(payload);
                data = await response.json();
            }
            if (!data.success) throw new Error(data.error || 'Checkout failed');

            if (status === 'draft') {
                window.appToast('Sale held successfully as draft!', 'success');
            } else if (status === 'paid') {
                if (isDirectPrint) {
                    const printFrame = document.getElementById('pos-direct-print-frame');
                    if (printFrame) {
                        printFrame.src = scopeUrl(`${baseUrl}/pos/receipt/` + data.invoice_id + '?direct_print=1');
                    } else {
                        window.open(scopeUrl(`${baseUrl}/pos/receipt/` + data.invoice_id), '_blank');
                    }
                    window.appToast('Sale completed & sent to printer directly!', 'success');
                } else {
                    window.open(scopeUrl(`${baseUrl}/pos/receipt/` + data.invoice_id), '_blank');
                    window.appToast('Sale completed successfully!', 'success');
                }
            }

            deductLocalStock(payload);
            clearCheckoutState();
            fetchHeldSalesCount();
        } catch (error) {
            if (error.message !== 'offline' && error.message !== 'Failed to fetch') {
                window.appToast(error.message, 'error');
                return;
            }
            window.appOfflineQueue.add({
                url: scopeUrl(`${baseUrl}/pos/checkout`),
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            if (status === 'paid') printOfflineReceipt(payload);

            deductLocalStock(payload);
            clearCheckoutState();
            updateOfflineBar();
            window.appToast('Sale added to offline queue and receipt generated.', 'success');
        }
    };

    const clearCheckoutState = () => {
        if (customerNameInput) customerNameInput.value = '';
        if (customerPhoneInput) customerPhoneInput.value = '';
        if (customerAddressInput) customerAddressInput.value = '';
        if (document.getElementById('sale-note')) document.getElementById('sale-note').value = '';
        if (saleDateInput) saleDateInput.value = todayIso;
        if (document.getElementById('credit-due-date')) document.getElementById('credit-due-date').value = '';
        if (document.getElementById('credit-private-note')) document.getElementById('credit-private-note').value = '';
        if (document.getElementById('credit-initial-payment')) document.getElementById('credit-initial-payment').value = '';
        if (document.getElementById('credit-initial-payment-method')) document.getElementById('credit-initial-payment-method').value = 'cash';
        setCustomerFormExpanded(false);
        updateCustomerSummaryBadge();
        currentPaymentMethod = 'cash';
        currentSaleChannel = 'retail';
        renderPaymentMethodUI();
        renderSaleChannelUI();
        cart = [];
        globalDiscount = 0;
        renderCart();
        persistCheckoutState();
    };

    window.addEventListener('online', async () => {
        await window.appOfflineQueue?.sync();
        updateOfflineBar();
    });
    window.addEventListener('offline', updateOfflineBar);

    restoreCheckoutState();

    <?php if (!empty($autoAddProduct)): ?>
        (() => {
            const autoProd = <?php echo json_encode([
                'id' => (int) $autoAddProduct['id'],
                'name' => $autoAddProduct['name'],
                'model_number' => $autoAddProduct['model_number'],
                'barcode' => $autoAddProduct['barcode'] ?? '',
                'retail_price' => (float) $autoAddProduct['price'],
                'wholesale_price' => (float) ($autoAddProduct['wholesale_price'] ?? $autoAddProduct['price']),
                'cost_price' => (float) ($autoAddProduct['cost'] ?? 0),
                'price' => (float) $autoAddProduct['price'],
                'max_stock' => (int) ($autoAddProduct['stock'] ?? 0),
                'unit_label' => $autoAddProduct['unit_type'] ?? 'pcs',
                'scan_quantity' => (int) ($autoAddProduct['scan_quantity'] ?? 1)
            ]); ?>;
            if (window.history && window.history.replaceState) {
                const cleanPosUrl = (typeof baseUrl !== 'undefined' ? baseUrl : '') + '/pos';
                window.history.replaceState({}, document.title, cleanPosUrl);
            }
            addToCart(autoProd, autoProd.scan_quantity || 1);
        })();
    <?php endif; ?>

    renderCart();
    filterProducts();
    renderPaymentMethodUI();
    renderSaleChannelUI();
    updateOfflineBar();
    fetchHeldSalesCount();

    // Mobile scanner disabled for Desktop standalone edition
    const storePairCode = <?php echo json_encode($storePairCode); ?>;
    const mobileScannerEnabled = false;
    const hardwareScannerEnabled = <?php echo json_encode(App::setting('hardware_scanner_enabled', '1') === '1'); ?>;
    const keyboardScannerEnabled = hardwareScannerEnabled;
    const mobileScannerPollMs = 600;
    let mobileScannerLastId = Number(sessionStorage.getItem('mobile_scanner_last_id_checkout') || '0');

    window.openScannerPairModal = () => {
        const connectUrl = `${window.location.origin}${baseUrl}/scanner?code=${storePairCode}`;
        const qrPayload = JSON.stringify({
            app: 'DevAltumScanner',
            server: `${window.location.origin}${baseUrl}`,
            code: storePairCode
        });

        window.appModal.open({
            title: 'Wireless Mobile Scanner',
            body: `
                <div style="text-align:center; padding: 6px 0;">
                    <div style="background: linear-gradient(135deg, #0f172a, #1e293b); color:#fff; border-radius:18px; padding: 18px 14px; margin-bottom: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 800;">Permanent Store Code</div>
                        <div style="font-size: 34px; font-weight: 900; letter-spacing: 10px; color: #38bdf8; margin: 8px 0; font-family: monospace;">${escapeHtml(storePairCode)}</div>
                        <div style="display:flex; justify-content:center; gap:8px;">
                            <button type="button" class="btn btn-secondary" onclick="navigator.clipboard?.writeText('${storePairCode}'); window.appToast('Store PIN copied to clipboard!', 'success');" style="padding: 5px 14px; font-size: 11.5px; font-weight: 800; border-radius: 999px; background: rgba(255,255,255,0.12); color:#fff; border:none;">
                                <span class="material-symbols-rounded" style="font-size:15px;">content_copy</span> Copy Code
                            </button>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:center; align-items:center; flex-direction:column; margin-bottom: 14px;">
                        <div id="modal-qr-container" style="background:#fff; padding:10px; border-radius:14px; border:1px solid #cbd5e1; box-shadow:0 4px 12px rgba(0,0,0,0.06); width:150px; height:150px; display:flex; align-items:center; justify-content:center;"></div>
                        <div style="font-size: 11.5px; font-weight: 750; color: #64748b; margin-top: 8px;">Scan with Mobile Scanner App to Connect</div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 12px; font-size: 12px; color: #334155; display:flex; align-items:center; justify-content:space-between; text-align:left;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="width:9px; height:9px; border-radius:50%; background:#22c55e; box-shadow:0 0 8px #22c55e; display:inline-block;"></span>
                            <span style="font-weight:750;">Live Listener Active</span>
                        </div>
                        <a href="${connectUrl}" target="_blank" class="btn btn-primary" style="padding: 4px 10px; font-size: 11px; font-weight: 800; text-decoration:none; border-radius: 8px;">Open Web Scanner</a>
                    </div>
                </div>
            `,
            actions: `<button class="btn btn-secondary" type="button" data-modal-close>Close</button>`
        });

        setTimeout(() => {
            const qrBox = document.getElementById('modal-qr-container');
            if (qrBox && typeof QRCode !== 'undefined') {
                qrBox.innerHTML = '';
                new QRCode(qrBox, {
                    text: qrPayload,
                    width: 130,
                    height: 130,
                    colorDark: "#0f172a",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
            }
        }, 60);
    };

    const normalizeScannedProduct = (product) => ({
        id: Number(product.id || 0),
        name: product.name || '',
        model_number: product.model_number || '',
        barcode: product.barcode || '',
        unit_label: product.unit_label || product.unit_type || 'pcs',
        matched_code: product.matched_code || product.barcode || '',
        retail_price: Number(product.price || product.retail_price || 0),
        wholesale_price: Number(product.wholesale_price || product.price || product.retail_price || 0),
        cost_price: Number(product.cost || product.cost_price || 0),
        price: Number(product.price || product.retail_price || 0),
        max_stock: Number(product.stock || product.max_stock || 0),
        scan_type: product.scan_type || 'single',
        scan_label: product.scan_label || '',
        scan_quantity: Math.max(1, Number(product.scan_quantity || 1))
    });

    let lastAddedProductId = null;
    let lastAddedProductTime = 0;
    const addScannedProduct = (product) => {
        if (!product) return;
        const now = Date.now();
        const pId = Number(product.id || 0);

        // STRICT HARDWARE SCANNER GUARD:
        // Ignore rapid duplicate trigger (<850ms) for the exact same product from scanner gun bounces/multiple Enters
        if (pId && pId === lastAddedProductId && (now - lastAddedProductTime < 850)) {
            return;
        }
        lastAddedProductId = pId;
        lastAddedProductTime = now;

        const normalized = normalizeScannedProduct(product);
        // STRICT MODE: Strictly add exactly 1 quantity per single physical scan
        if (typeof window.addToCart === 'function') {
            window.addToCart(normalized, 1);
        } else if (typeof addToCart === 'function') {
            addToCart(normalized, 1);
        }
    };

    const openBarcodeVariantPicker = (products, barcode) => {
        const rows = products.map((product, index) => {
            const label = product.variant_label || product.model_number || product.scan_label || 'Variant';
            const qty = Number(product.scan_quantity || 1);
            return `
                <button type="button" class="search-item" data-scan-choice="${index}" style="width:100%; text-align:left; border:none; background:#fff;">
                    <strong>${escapeHtml(product.name || '')}</strong>
                    <span>${escapeHtml(label)} | ${window.appFormatMoney(Number(product.price || 0))}${qty > 1 ? ` | Adds ${qty}` : ''}</span>
                </button>
            `;
        }).join('');
        window.appModal.open({
            title: 'Select Variant',
            body: `<div class="page-subtitle" style="margin-bottom:12px;">Barcode ${escapeHtml(barcode)} matches multiple variants.</div><div class="search-results open" style="position:static; display:grid; gap:8px;">${rows}</div>`,
            actions: `<button class="btn btn-secondary" type="button" data-modal-close>Cancel</button>`
        });
        document.querySelectorAll('[data-scan-choice]').forEach((button) => {
            button.addEventListener('click', () => {
                const product = products[Number(button.dataset.scanChoice || 0)];
                if (product) addScannedProduct(product);
                window.appModal.close();
            });
        });
    };

    let isRemoteBarcodeLookupBusy = false;
    let lastRemoteScannedBarcode = '';
    let lastRemoteScannedTime = 0;
    const handleRemoteBarcode = async (barcode, preloadedProduct = null) => {
        if (preloadedProduct && (preloadedProduct.id || preloadedProduct.name)) {
            addScannedProduct(preloadedProduct);
            return;
        }

        let scanned = String(barcode || '').trim();
        if (!scanned) return;

        // Auto-correct doubled keystrokes from fast scanner bounce (e.g. 88990088000011115588331122 -> 8908001158312)
        if (scanned.length >= 6 && scanned.length % 2 === 0) {
            let isDoubled = true;
            for (let i = 0; i < scanned.length; i += 2) {
                if (scanned[i] !== scanned[i + 1]) { isDoubled = false; break; }
            }
            if (isDoubled) {
                let single = '';
                for (let i = 0; i < scanned.length; i += 2) single += scanned[i];
                scanned = single;
            }
        }

        const now = Date.now();
        if (isRemoteBarcodeLookupBusy || (scanned === lastRemoteScannedBarcode && (now - lastRemoteScannedTime < 850))) {
            return;
        }
        lastRemoteScannedBarcode = scanned;
        lastRemoteScannedTime = now;
        isRemoteBarcodeLookupBusy = true;

        try {
            // First attempt: /pos/barcode, fallback to /search/barcode or /?url=search/barcode
            let searchUrl = scopeUrl(`${baseUrl}/pos/barcode?q=${encodeURIComponent(scanned)}`);
            let response = await fetch(searchUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });

            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                searchUrl = scopeUrl(`${baseUrl}/search/barcode?q=${encodeURIComponent(scanned)}`);
                response = await fetch(searchUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
            }

            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                searchUrl = scopeUrl(`${baseUrl}/?url=search/barcode&q=${encodeURIComponent(scanned)}`);
                response = await fetch(searchUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
            }

            const data = await response.json();
            if (!data || !data.found) {
                if (typeof playScannerBeep === 'function') playScannerBeep('error');
                window.appToast?.(data?.message || `Barcode not found: ${scanned}`, 'error');
                return;
            }
            if (data.requires_choice && Array.isArray(data.products)) {
                openBarcodeVariantPicker(data.products, scanned);
                return;
            }
            if (data.product) {
                addScannedProduct(data.product);
            }
        } catch (error) {
            console.error('Barcode scanner lookup exception:', error);
            if (typeof playScannerBeep === 'function') playScannerBeep('error');
            window.appToast?.(`Barcode not found in catalog: ${scanned}`, 'error');
        } finally {
            setTimeout(() => { isRemoteBarcodeLookupBusy = false; }, 600);
        }
    };

    window.handleRemoteBarcode = handleRemoteBarcode;
    window.addScannedProduct = addScannedProduct;

    const pollMobileScanner = async () => {
        if (!mobileScannerEnabled || document.hidden || !storePairCode) return;
        try {
            const response = await fetch(scopeUrl(`${baseUrl}/scanner/poll?code=${encodeURIComponent(storePairCode)}&context=checkout&last_id=${mobileScannerLastId}`), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (!data.success) return;
            (data.events || []).forEach((event) => {
                if (event.barcode === 'ACTION_CLEAR_CART' || event.scan_context === 'cart_cleared') {
                    cart = [];
                    renderCart();
                    return;
                }
                if (event.product && event.product.id > 0) {
                    addScannedProduct(event.product);
                } else {
                    handleRemoteBarcode(event.barcode);
                }
            });
            if (Number(data.last_id || 0) > mobileScannerLastId) {
                mobileScannerLastId = Number(data.last_id || 0);
                sessionStorage.setItem('mobile_scanner_last_id_checkout', String(mobileScannerLastId));
            }
        } catch (error) {}
    };

    if (mobileScannerEnabled) {
        pollMobileScanner();
        setInterval(pollMobileScanner, mobileScannerPollMs);
    }

    // Keyboard Shortcuts (Ultra-Fast Professional Cashier Ergonomics - Bulletproof Window & Document Capture)
    let barcodeBuffer = '';
    let barcodeTimer = null;
    let lastPosEnterTime = 0;

    const handlePosKeyShortcuts = (e) => {
        const key = (e.key || '').toLowerCase();
        const code = e.code || '';
        const isCtrl = e.ctrlKey || e.metaKey;
        const isAlt = e.altKey;
        const isShift = e.shiftKey;
        const targetTag = (e.target && e.target.tagName) || '';
        const isInputField = ['INPUT', 'TEXTAREA', 'SELECT'].includes(targetTag);
        const searchEl = document.getElementById('pos-search');

        // 1. Checkout & Print (F2 OR F9 OR Alt+P OR Ctrl+Enter OR Shift+Enter)
        if (code === 'F2' || key === 'f2' || code === 'F9' || key === 'f9' || (isAlt && (key === 'p' || code === 'KeyP')) || (isCtrl && (key === 'enter' || code === 'Enter' || code === 'NumpadEnter')) || (isShift && (key === 'enter' || code === 'Enter' || code === 'NumpadEnter') && e.target !== searchEl)) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.processCheckout === 'function') {
                window.processCheckout('paid');
            } else if (typeof processCheckout === 'function') {
                processCheckout('paid');
            }
            return;
        }

        // 2. Focus Search / Barcode Input (F4 OR F3 OR Ctrl+B OR Alt+S OR '/' when not inside an input)
        if (code === 'F4' || key === 'f4' || code === 'F3' || key === 'f3' || (isCtrl && (key === 'b' || code === 'KeyB')) || (isAlt && (key === 's' || code === 'KeyS')) || (!isInputField && key === '/')) {
            e.preventDefault();
            e.stopPropagation();
            if (searchEl) {
                searchEl.focus();
                searchEl.select();
                searchEl.style.transition = 'box-shadow 0.2s ease, border-color 0.2s ease';
                searchEl.style.borderColor = '#0f6dff';
                searchEl.style.boxShadow = '0 0 0 4px rgba(15, 109, 255, 0.35)';
                setTimeout(() => { searchEl.style.boxShadow = ''; }, 700);
            }
            return;
        }

        // 3. Toggle Gross Profit & Safe Margin Advisor Tab (F6 OR Ctrl+M OR Alt+M)
        if (code === 'F6' || key === 'f6' || ((isCtrl || isAlt) && (key === 'm' || code === 'KeyM'))) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.switchPosTab === 'function') {
                if (currentPosRightTab === 'profit') {
                    window.switchPosTab('cart');
                } else {
                    window.switchPosTab('profit');
                }
            }
            return;
        }

        // 4. Open Set Discount Modal (F7 OR Ctrl+D OR Alt+D)
        if (code === 'F7' || key === 'f7' || ((isCtrl || isAlt) && (key === 'd' || code === 'KeyD'))) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.openDiscountModal === 'function') {
                window.openDiscountModal();
            }
            return;
        }

        // 6. Hold Sale as Draft (F10 OR Alt+H)
        if (code === 'F10' || key === 'f10' || (isAlt && (key === 'h' || code === 'KeyH'))) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.holdSale === 'function') {
                window.holdSale();
            } else if (typeof holdSale === 'function') {
                holdSale();
            }
            return;
        }

        // 7. Recall Drafts (F8 OR Alt+R)
        if (code === 'F8' || key === 'f8' || (isAlt && (key === 'r' || code === 'KeyR'))) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.openHeldSalesModal === 'function') {
                window.openHeldSalesModal();
            } else if (typeof openHeldSalesModal === 'function') {
                openHeldSalesModal();
            }
            return;
        }

        // 8. Alt + C: Toggle Customer Details Panel
        if (isAlt && (key === 'c' || code === 'KeyC')) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.toggleCustomerForm === 'function') {
                window.toggleCustomerForm(e);
            }
            return;
        }

        // 9. Escape: Close search / return to cart / close open modal
        if (key === 'escape' || code === 'Escape') {
            if (document.activeElement === searchEl && searchEl) {
                searchEl.value = '';
                if (typeof filterProducts === 'function') filterProducts();
                searchEl.blur();
                return;
            }
            if (currentPosRightTab === 'profit') {
                if (typeof window.switchPosTab === 'function') window.switchPosTab('cart');
                return;
            }
            if (window.appModal && typeof window.appModal.close === 'function') {
                window.appModal.close();
            }
            return;
        }

        // 10. Enter on pos-search input or USB Hardware Barcode Scanner Enter
        if (key === 'enter' || code === 'Enter' || code === 'NumpadEnter') {
            const now = Date.now();
            if (now - lastPosEnterTime < 500) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation?.();
                return;
            }
            lastPosEnterTime = now;

            let scannedFromBuffer = (barcodeBuffer && barcodeBuffer.length > 2) ? barcodeBuffer : '';
            const searchVal = searchEl ? searchEl.value.trim() : '';
            barcodeBuffer = '';

            // Auto-correct doubled keystrokes if buffer caught duplicates
            if (scannedFromBuffer.length >= 6 && scannedFromBuffer.length % 2 === 0) {
                let isDouble = true;
                for (let i = 0; i < scannedFromBuffer.length; i += 2) {
                    if (scannedFromBuffer[i] !== scannedFromBuffer[i + 1]) { isDouble = false; break; }
                }
                if (isDouble) {
                    let single = '';
                    for (let i = 0; i < scannedFromBuffer.length; i += 2) single += scannedFromBuffer[i];
                    scannedFromBuffer = single;
                }
            }

            // If a barcode was scanned into the buffer, or the search input contains a barcode-like code:
            if (scannedFromBuffer || (document.activeElement === searchEl && searchVal.length > 2)) {
                const codeToLookup = scannedFromBuffer || searchVal;
                e.preventDefault();
                e.stopPropagation();
                if (searchEl) {
                    searchEl.value = '';
                    searchEl.blur(); // Blur immediately to prevent duplicate keyup/enter from clicking cards
                    if (typeof filterProducts === 'function') filterProducts();
                }
                handleRemoteBarcode(codeToLookup);
                return;
            }

            // Normal manual enter on search input: click first visible card
            if (document.activeElement === searchEl) {
                e.preventDefault();
                const visibleCards = Array.from(document.querySelectorAll('#products-grid .product-card')).filter(c => c.style.display !== 'none');
                if (visibleCards.length > 0) {
                    visibleCards[0].click();
                    if (searchEl) searchEl.value = '';
                    if (typeof filterProducts === 'function') filterProducts();
                }
                return;
            }
        }

        // 11. USB Hardware Barcode Scanner Buffer (Captures fast keystrokes from anywhere on screen)
        const isOtherInputField = isInputField && e.target !== searchEl;
        if (keyboardScannerEnabled && !isOtherInputField && (e.key || '').length === 1 && !isCtrl && !isAlt) {
            barcodeBuffer += e.key;
            clearTimeout(barcodeTimer);
            barcodeTimer = setTimeout(() => { barcodeBuffer = ''; }, 75);
        }
    };

    // Attach ONLY to window to avoid duplicate key capture (window + document was doubling every character)
    window.addEventListener('keydown', handlePosKeyShortcuts, true);

})();
</script>
<?php
$extraJs = ob_get_clean();
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
