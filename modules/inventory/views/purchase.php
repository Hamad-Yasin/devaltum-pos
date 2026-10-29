<?php ob_start(); ?>
<?php
$balance = (float) ($purchase['balance_amount'] ?? 0);
$billTotal = (float) ($purchase['bill_total'] ?? 0);
$paidAmount = (float) ($purchase['paid_amount'] ?? 0);

$totalQty = (float) array_sum(array_map(fn($it) => (float)($it['quantity'] ?? 0), $items ?? []));
if ($totalQty <= 0) {
    $totalQty = (float) ($purchase['total_quantity'] ?? $purchase['quantity'] ?? 0);
}

$payments = $payments ?? [];
$supplier = $supplier ?? null;
$store = Tenant::currentStore();
$storeSettings = App::settings([
    'company_name' => 'DevAltum POS',
    'company_tagline' => 'Official Point of Sale & Inventory',
    'currency_symbol' => 'Rs',
    'address' => '',
    'phone' => ''
]);

$supplierPhone = trim((string)($supplier['phone'] ?? ''));
$supplierAddress = trim((string)($supplier['address'] ?? ''));
$supplierCurrentBalance = isset($supplier['current_balance']) ? (float)$supplier['current_balance'] : null;
?>

<style>
    /* Main Grid Layout */
    .purchase-profile-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
        gap: 20px;
        align-items: start;
    }

    .purchase-profile-card {
        border: 1px solid var(--border, #e2e8f0);
        border-radius: 20px;
        background: linear-gradient(150deg, #ffffff, #f8fbff);
        box-shadow: 0 12px 36px rgba(15, 23, 42, 0.06);
        padding: 24px;
        transition: box-shadow 0.2s ease;
    }

    /* Hero Banner */
    .purchase-hero {
        background: linear-gradient(145deg, #0f172a 0%, #1e293b 50%, #172554 100%);
        color: #fff;
        border-radius: 22px;
        padding: 26px;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
        position: relative;
        overflow: hidden;
    }

    .purchase-hero::after {
        content: '';
        position: absolute;
        right: -50px;
        top: -60px;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.28) 0%, rgba(59, 130, 246, 0) 70%);
        pointer-events: none;
    }

    .purchase-hero small {
        color: rgba(255, 255, 255, 0.72);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 11px;
    }

    .purchase-hero h2 {
        margin: 8px 0 6px;
        font-size: 28px;
        font-weight: 900;
        line-height: 1.2;
        letter-spacing: -0.02em;
    }

    .purchase-hero-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        color: rgba(255, 255, 255, 0.85);
        font-size: 13px;
        font-weight: 700;
    }

    .purchase-money-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 22px;
    }

    .purchase-money-box {
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 16px;
        padding: 14px 16px;
        background: rgba(255, 255, 255, 0.07);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    .purchase-money-box span {
        display: block;
        color: rgba(255, 255, 255, 0.7);
        font-size: 11px;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 6px;
    }

    .purchase-money-box strong {
        font-size: 19px;
        font-weight: 900;
        letter-spacing: -0.01em;
    }

    .purchase-card-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
    }

    .purchase-mini-card {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #ffffff;
        padding: 16px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
    }

    .purchase-mini-card span {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
    }

    .purchase-mini-card span .material-symbols-rounded {
        font-size: 16px;
    }

    .purchase-mini-card strong {
        display: block;
        color: #0f172a;
        font-size: 18px;
        font-weight: 900;
        line-height: 1.2;
    }

    /* Supplier Info Card in Hero */
    .supplier-quick-bar {
        margin-top: 18px;
        padding: 12px 16px;
        background: rgba(255, 255, 255, 0.09);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }

    .supplier-quick-info {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12.5px;
    }

    .supplier-quick-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .supplier-chip-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 10px;
        border-radius: 8px;
        font-size: 11.5px;
        font-weight: 800;
        color: #fff;
        text-decoration: none;
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.22);
        transition: all 0.15s ease;
    }

    .supplier-chip-btn:hover {
        background: rgba(255, 255, 255, 0.26);
        transform: translateY(-1px);
        color: #fff;
    }

    .supplier-chip-btn.whatsapp {
        background: #16a34a;
        border-color: #15803d;
    }

    .supplier-chip-btn.whatsapp:hover {
        background: #15803d;
    }

    /* Details Rows */
    .purchase-detail-list {
        display: grid;
        gap: 0;
    }

    .purchase-detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        border-bottom: 1px solid #f1f5f9;
        padding: 13px 0;
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
    }

    .purchase-detail-row:last-child {
        border-bottom: none;
    }

    .purchase-detail-row strong {
        color: #0f172a;
        text-align: right;
        font-weight: 850;
    }

    /* Table styling */
    .table-scroll-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #ffffff;
    }

    .table-scroll-wrap::-webkit-scrollbar {
        height: 7px;
    }

    .table-scroll-wrap::-webkit-scrollbar-track {
        background: #f8fafc;
    }

    .table-scroll-wrap::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }

    .purchase-item-link {
        color: #0f172a;
        text-decoration: none;
        font-weight: 850;
        font-size: 13.5px;
        transition: color 0.15s ease;
    }

    .purchase-item-link:hover {
        color: #2563eb;
        text-decoration: underline;
    }

    /* Payment Form */
    .purchase-payment-form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .purchase-payment-form .full {
        grid-column: 1 / -1;
    }

    .quick-amount-buttons {
        display: flex;
        gap: 6px;
        margin-top: 6px;
        flex-wrap: wrap;
    }

    .quick-amount-btn {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #334155;
        border-radius: 8px;
        padding: 3px 9px;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .quick-amount-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .quick-amount-btn.active {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
    }

    /* Custom Payment Method Select */
    .premium-select-wrap {
        position: relative;
        width: 100%;
        user-select: none;
    }

    .premium-select-trigger {
        width: 100%;
        min-height: 40px;
        height: 40px;
        padding: 4px 12px;
        background: #ffffff;
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
    }

    .premium-select-wrap.is-open .premium-select-trigger {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
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
        font-size: 16px;
        flex-shrink: 0;
    }

    .premium-select-trigger-text {
        font-size: 12.5px;
        font-weight: 800;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .premium-select-chevron {
        color: #64748b;
        font-size: 18px;
        transition: transform 0.2s ease;
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
        padding: 5px;
        max-height: 240px;
        overflow-y: auto;
    }

    .premium-select-wrap.is-open .premium-select-menu {
        display: block;
    }

    .premium-select-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.12s ease;
    }

    .premium-select-item:hover {
        background: #f8fafc;
    }

    .premium-select-item.is-selected {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .premium-select-item-lead {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .premium-select-item-icon {
        font-size: 16px;
        color: #64748b;
    }

    .premium-select-item.is-selected .premium-select-item-icon {
        color: #2563eb;
    }

    .premium-select-item-info strong {
        font-size: 12.5px;
        font-weight: 800;
        color: #0f172a;
    }

    .premium-select-item.is-selected .premium-select-item-info strong {
        color: #1d4ed8;
    }

    .premium-select-item-check {
        font-size: 16px;
        color: #2563eb;
        display: none;
    }

    .premium-select-item.is-selected .premium-select-item-check {
        display: block;
    }

    /* Payment Timeline */
    .payment-timeline {
        display: grid;
        gap: 12px;
    }

    .payment-row {
        display: grid;
        grid-template-columns: 46px minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        padding: 13px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #ffffff;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .payment-row:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
    }

    .payment-row-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: #ecfdf5;
        color: #059669;
    }

    /* Confirmation Modal */
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
        width: min(92vw, 440px);
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24), 0 4px 16px rgba(15, 23, 42, 0.08);
        padding: 24px;
        overflow: hidden;
        animation: modalPopIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        text-align: left;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes modalPopIn {
        from { opacity: 0; transform: scale(0.94) translateY(8px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .compact-modal-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }

    .compact-modal-icon-badge {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fee2e2;
        flex-shrink: 0;
    }

    .compact-modal-icon-badge .material-symbols-rounded {
        font-size: 24px;
    }

    .compact-modal-title {
        font-size: 17px;
        font-weight: 900;
        color: #0f172a;
        margin: 0;
        line-height: 1.25;
    }

    .compact-modal-sub {
        font-size: 12px;
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
        margin: 0 0 14px;
    }

    .compact-modal-info-pill {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 14px;
        display: flex;
        flex-direction: column;
        gap: 8px;
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
        height: 40px;
        padding: 0 18px;
        border-radius: 10px;
        font-size: 13px;
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

    /* Responsive adjustments */
    @media (max-width: 960px) {
        .purchase-profile-grid,
        .purchase-money-grid,
        .purchase-card-grid,
        .purchase-payment-form,
        .payment-row {
            grid-template-columns: 1fr;
        }
    }

    /* Print Template Container - Hidden by default on screen */
    #printablePurchaseInvoice {
        display: none;
    }

    /* Print Mode Styles */
    @media print {
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }

        body * {
            visibility: hidden;
        }

        #printablePurchaseInvoice,
        #printablePurchaseInvoice * {
            visibility: visible;
        }

        #printablePurchaseInvoice {
            display: block !important;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            background: #ffffff;
            color: #000000;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }

        /* Hide all screen components */
        header, .header, .top-header, .sidebar, aside,
        .mobile-bottom-nav, .mobile-floating-bell, .floating-pay-now-btn,
        .return-switcher, .mobile-powered-footer,
        #interactivePurchaseView, .compact-modal-backdrop {
            display: none !important;
        }
    }
</style>

<!-- ========================================== -->
<!-- 1. INTERACTIVE ON-SCREEN PURCHASE VIEW     -->
<!-- ========================================== -->
<div id="interactivePurchaseView">
    <!-- Page Header & Action Bar -->
    <div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:14px; margin-bottom:20px;">
        <div>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/index?tab=purchase_list" class="btn btn-secondary" style="margin-bottom:10px; display:inline-flex; align-items:center; gap:6px; font-weight:800; font-size:12.5px;">
                <span class="material-symbols-rounded">arrow_back</span>Back to Purchase History
            </a>
            <h1 style="margin:0 0 4px; display:flex; align-items:center; gap:8px;">
                <span>Purchase Bill #<?php echo (int)$purchase['id']; ?></span>
                <?php if ($balance <= 0): ?>
                    <span class="badge" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; font-size:12px; padding:3px 8px;">Fully Paid</span>
                <?php elseif ($paidAmount > 0): ?>
                    <span class="badge" style="background:#fffbeb; color:#d97706; border:1px solid #fde68a; font-size:12px; padding:3px 8px;">Partially Paid</span>
                <?php else: ?>
                    <span class="badge" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca; font-size:12px; padding:3px 8px;">Full Udhar</span>
                <?php endif; ?>
            </h1>
            <p class="page-subtitle" style="margin:0;">Detailed stock entry, unit purchase costs, supplier ledger & payment records.</p>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <button type="button" class="btn btn-secondary" onclick="window.print()" style="font-weight:850; display:inline-flex; align-items:center; gap:6px; padding:9px 16px;">
                <span class="material-symbols-rounded" style="font-size:19px;">print</span>Print Bill Voucher
            </button>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchaseEdit/<?php echo (int) $purchase['id']; ?>" class="btn btn-primary" style="font-weight:850; display:inline-flex; align-items:center; gap:6px; padding:9px 16px;">
                <span class="material-symbols-rounded" style="font-size:19px;">edit_document</span>Edit Purchase Bill
            </a>
            <button type="button" class="btn btn-danger btn-delete-purchase" 
                data-id="<?php echo (int)$purchase['id']; ?>" 
                data-supplier="<?php echo Security::escape($purchase['supplier_name']); ?>" 
                data-total="<?php echo Security::escape(App::money($purchase['bill_total'])); ?>" 
                data-date="<?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?>" 
                style="font-weight:850; display:inline-flex; align-items:center; gap:6px; background:#ef4444; color:#fff; border:none; padding:9px 16px; border-radius:12px; cursor:pointer;">
                <span class="material-symbols-rounded" style="font-size:19px;">delete</span>Delete Bill
            </button>
        </div>
    </div>

    <!-- Top Grid: Hero & Summary Cards -->
    <div class="purchase-profile-grid">
        <!-- Hero Banner Section -->
        <section class="purchase-hero">
            <div style="position:relative; z-index:1;">
                <small><?php echo date('l, F d, Y', strtotime($purchase['purchase_date'])); ?> &bull; <?php echo date('h:i A', strtotime($purchase['created_at'] ?? $purchase['purchase_date'])); ?></small>
                <h2><?php echo Security::escape($purchase['supplier_name']); ?></h2>
                <div class="purchase-hero-meta">
                    <span><span class="material-symbols-rounded" style="font-size:16px; vertical-align:middle;">inventory_2</span> <?php echo count($items ?? []); ?> Line Item(s)</span>
                    <span>&bull;</span>
                    <span><span class="material-symbols-rounded" style="font-size:16px; vertical-align:middle;">scale</span> Total Qty: <?php echo number_format($totalQty, 2); ?></span>
                    <?php if (!empty($purchase['bill_reference'])): ?>
                        <span>&bull;</span>
                        <span><span class="material-symbols-rounded" style="font-size:16px; vertical-align:middle;">tag</span> Ref: <?php echo Security::escape($purchase['bill_reference']); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Money Highlights -->
            <div class="purchase-money-grid">
                <div class="purchase-money-box">
                    <span>Gross Bill Total</span>
                    <strong><?php echo App::money($billTotal); ?></strong>
                </div>
                <div class="purchase-money-box">
                    <span>Paid Amount</span>
                    <strong style="color:#86efac;"><?php echo App::money($paidAmount); ?></strong>
                </div>
                <div class="purchase-money-box">
                    <span>Remaining (Udhar)</span>
                    <strong style="color:<?php echo $balance > 0 ? '#fca5a5' : '#86efac'; ?>;">
                        <?php echo App::money($balance); ?>
                    </strong>
                </div>
            </div>

            <!-- Mini Cards -->
            <div class="purchase-card-grid">
                <div class="purchase-mini-card">
                    <span><span class="material-symbols-rounded">receipt_long</span>Items Count</span>
                    <strong><?php echo count($items ?? []); ?> Product<?php echo count($items ?? []) > 1 ? 's' : ''; ?></strong>
                </div>
                <div class="purchase-mini-card">
                    <span><span class="material-symbols-rounded">scale</span>Total Units</span>
                    <strong><?php echo number_format($totalQty, 2); ?></strong>
                </div>
                <div class="purchase-mini-card">
                    <span><span class="material-symbols-rounded">calendar_today</span>Purchase Date</span>
                    <strong><?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?></strong>
                </div>
                <div class="purchase-mini-card">
                    <span><span class="material-symbols-rounded">verified</span>Payment Status</span>
                    <strong>
                        <?php if ($balance <= 0): ?>
                            <span style="color:#16a34a;">Fully Settled</span>
                        <?php elseif ($paidAmount > 0): ?>
                            <span style="color:#d97706;">Partially Paid</span>
                        <?php else: ?>
                            <span style="color:#dc2626;">Full Udhar (Due)</span>
                        <?php endif; ?>
                    </strong>
                </div>
            </div>

            <!-- Supplier Quick Bar (Contact & Overall Balance) -->
            <?php if (!empty($supplierPhone) || $supplierCurrentBalance !== null): ?>
            <div class="supplier-quick-bar">
                <div class="supplier-quick-info">
                    <span class="material-symbols-rounded" style="color:rgba(255,255,255,0.7); font-size:18px;">contacts</span>
                    <span>
                        <?php if (!empty($supplierPhone)): ?>
                            <strong>Phone:</strong> <?php echo Security::escape($supplierPhone); ?>
                        <?php endif; ?>
                        <?php if ($supplierCurrentBalance !== null): ?>
                            <span style="margin-left:8px; padding-left:8px; border-left:1px solid rgba(255,255,255,0.2);">
                                <strong>Total Supplier Khata:</strong> 
                                <span style="color:<?php echo $supplierCurrentBalance > 0 ? '#fca5a5' : '#86efac'; ?>; font-weight:900;">
                                    <?php echo App::money($supplierCurrentBalance); ?>
                                </span>
                            </span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="supplier-quick-actions">
                    <?php if (!empty($supplierPhone)): ?>
                        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $supplierPhone); ?>" class="supplier-chip-btn">
                            <span class="material-symbols-rounded" style="font-size:14px;">call</span>Call
                        </a>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $supplierPhone); ?>?text=<?php echo urlencode('Salam, regarding purchase bill #' . $purchase['id'] . ' of total ' . App::money($billTotal)); ?>" target="_blank" class="supplier-chip-btn whatsapp">
                            <span class="material-symbols-rounded" style="font-size:14px;">chat</span>WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <!-- Purchase Summary Breakdown Card -->
        <section class="purchase-profile-card">
            <h3 style="margin:0 0 14px; display:flex; align-items:center; gap:8px;">
                <span class="material-symbols-rounded" style="color:#2563eb;">fact_check</span>
                Purchase Overview
            </h3>
            <div class="purchase-detail-list">
                <div class="purchase-detail-row">
                    <span>Supplier / Vendor</span>
                    <strong><?php echo Security::escape($purchase['supplier_name']); ?></strong>
                </div>
                <div class="purchase-detail-row">
                    <span>Bill Reference</span>
                    <strong><?php echo Security::escape($purchase['bill_reference'] ?: 'N/A'); ?></strong>
                </div>
                <div class="purchase-detail-row">
                    <span>Purchase Date & Time</span>
                    <strong>
                        <?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?><br>
                        <span class="page-subtitle" style="font-weight:600; font-size:11.5px;"><?php echo date('h:i A', strtotime($purchase['created_at'] ?? $purchase['purchase_date'])); ?></span>
                    </strong>
                </div>
                <div class="purchase-detail-row">
                    <span>Total Line Items</span>
                    <strong><?php echo count($items ?? []); ?> items (<?php echo number_format($totalQty, 2); ?> units)</strong>
                </div>
                <div class="purchase-detail-row">
                    <span>Gross Bill Amount</span>
                    <strong style="font-size:15px; color:#0f172a;"><?php echo App::money($billTotal); ?></strong>
                </div>
                <div class="purchase-detail-row">
                    <span>Settled / Paid</span>
                    <strong style="color:#059669; font-size:14px;"><?php echo App::money($paidAmount); ?></strong>
                </div>
                <div class="purchase-detail-row">
                    <span>Pending Udhar Balance</span>
                    <strong style="font-size:16px; color:<?php echo $balance > 0 ? '#dc2626' : '#059669'; ?>;">
                        <?php echo App::money($balance); ?>
                    </strong>
                </div>
            </div>
        </section>
    </div>

    <!-- ITEM BREAKDOWN TABLE -->
    <section class="purchase-profile-card" style="margin-top:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
            <div>
                <h3 style="margin:0 0 4px; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-rounded" style="color:#2563eb;">list_alt</span>
                    Purchased Items Breakdown
                </h3>
                <p class="page-subtitle" style="margin:0;">List of all individual items, purchase costs, quantities, and selling prices.</p>
            </div>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchaseEdit/<?php echo (int) $purchase['id']; ?>" class="btn btn-secondary" style="font-size:12.5px; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
                <span class="material-symbols-rounded">edit</span>Edit Items & Bill
            </a>
        </div>

        <div class="table-scroll-wrap">
            <table class="data-table" style="margin:0; width:100%; min-width:860px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="width:45px; text-align:center;">#</th>
                        <th style="min-width:280px;">Item / Product Name</th>
                        <th style="width:110px;">Unit</th>
                        <th style="width:110px; text-align:right;">Quantity</th>
                        <th style="width:125px; text-align:right;">Unit Cost</th>
                        <th style="width:125px; text-align:right;">Retail Price</th>
                        <th style="width:135px; text-align:right;">Line Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="7" class="empty-state" style="padding:30px; text-align:center;">No items recorded for this purchase bill.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $idx => $it): ?>
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="text-align:center; font-weight:800; color:#94a3b8;"><?php echo $idx + 1; ?></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="width:34px; height:34px; border-radius:8px; background:#eff6ff; color:#2563eb; display:grid; place-items:center; flex-shrink:0;">
                                            <span class="material-symbols-rounded" style="font-size:18px;">inventory_2</span>
                                        </div>
                                        <div>
                                            <?php if (!empty($it['product_id'])): ?>
                                                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/view/<?php echo (int)$it['product_id']; ?>" class="purchase-item-link" title="Click to view product details">
                                                    <?php echo Security::escape($it['item_name']); ?>
                                                </a>
                                            <?php else: ?>
                                                <strong style="color:#0f172a; font-size:13.5px;"><?php echo Security::escape($it['item_name']); ?></strong>
                                            <?php endif; ?>

                                            <div style="display:flex; align-items:center; gap:6px; margin-top:3px; flex-wrap:wrap;">
                                                <?php if (!empty($it['product_id'])): ?>
                                                    <span class="badge" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-size:10px; padding:1px 6px;">Catalog Item</span>
                                                <?php else: ?>
                                                    <span class="badge" style="background:#f1f5f9; color:#475569; font-size:10px; padding:1px 6px;">Ad-hoc Entry</span>
                                                <?php endif; ?>

                                                <?php if (!empty($it['batch_number'])): ?>
                                                    <span style="font-size:11px; color:#64748b; font-weight:600;">Batch: <?php echo Security::escape($it['batch_number']); ?></span>
                                                <?php endif; ?>

                                                <?php if (!empty($it['expiry_date'])): ?>
                                                    <span style="font-size:11px; color:#d97706; font-weight:600;">Exp: <?php echo date('M Y', strtotime($it['expiry_date'])); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" style="background:#f8fafc; border:1px solid #e2e8f0; color:#334155; font-size:11px; font-weight:700;">
                                        <?php echo Security::escape(App::productUnitLabel($it['unit_type'] ?? 'pcs')); ?>
                                    </span>
                                </td>
                                <td style="text-align:right; font-weight:850; color:#0f172a; font-size:13.5px;">
                                    <?php echo number_format((float)$it['quantity'], 2); ?>
                                </td>
                                <td style="text-align:right; font-weight:800; color:#475569; font-size:13.5px;">
                                    <?php echo App::money($it['unit_cost']); ?>
                                </td>
                                <td style="text-align:right; font-weight:800; color:#059669; font-size:13.5px;">
                                    <?php echo !empty($it['new_sale_price']) && (float)$it['new_sale_price'] > 0 ? App::money($it['new_sale_price']) : '<span style="color:#94a3b8; font-size:12px;">&mdash;</span>'; ?>
                                </td>
                                <td style="text-align:right; font-weight:900; color:#0f172a; font-size:14px;">
                                    <?php echo App::money($it['subtotal'] ?? ($it['quantity'] * $it['unit_cost'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#f8fafc; font-weight:900; border-top:2px solid #cbd5e1;">
                        <td colspan="3" style="text-align:right; font-size:13px; color:#475569; padding:12px 16px;">Total Quantities & Grand Total:</td>
                        <td style="text-align:right; font-size:14px; color:#0f172a; padding:12px 16px;">
                            <?php echo number_format($totalQty, 2); ?>
                        </td>
                        <td colspan="2"></td>
                        <td style="text-align:right; font-size:16px; color:#1d4ed8; padding:12px 16px;">
                            <?php echo App::money($billTotal); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <!-- Bottom Grid: Add Payment & Payment History -->
    <div class="purchase-profile-grid" style="margin-top:20px;">
        <!-- Add Remaining Payment Card -->
        <section class="purchase-profile-card">
            <h3 style="margin:0 0 14px; display:flex; align-items:center; gap:8px;">
                <span class="material-symbols-rounded" style="color:#2563eb;">add_card</span>
                Add Remaining Payment
            </h3>

            <?php if ($balance <= 0): ?>
                <div class="app-alert alert-success" style="margin:0; border-radius:14px;">
                    <span class="material-symbols-rounded" style="color:#16a34a; font-size:24px;">check_circle</span>
                    <div>
                        <strong style="display:block; color:#065f46;">Full Bill Paid!</strong>
                        <span style="font-size:12px; color:#047857;">All dues for this purchase have been settled. No pending balance remains.</span>
                    </div>
                </div>
            <?php else: ?>
                <form class="purchase-payment-form" id="purchasePaymentForm" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchasePayment/<?php echo (int) $purchase['id']; ?>" method="POST" data-no-ajax>
                    <?php echo Security::csrfField(); ?>
                    
                    <div class="form-group">
                        <label class="form-label">Payment Date <span style="color:#dc2626;">*</span></label>
                        <input class="form-input" type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
                            <span>Payment Amount <span style="color:#dc2626;">*</span></span>
                            <small id="paymentFeedbackRemaining" style="color:#dc2626; font-weight:800; font-size:11px;">
                                Bal After Pay: <?php echo App::money(0); ?>
                            </small>
                        </label>
                        <input class="form-input" type="number" step="0.01" min="0.01" max="<?php echo number_format($balance, 2, '.', ''); ?>" id="paymentAmountInput" name="amount" value="<?php echo number_format($balance, 2, '.', ''); ?>" required>
                        <div class="quick-amount-buttons">
                            <button type="button" class="quick-amount-btn active" id="btnPayFull" data-amount="<?php echo number_format($balance, 2, '.', ''); ?>">
                                Full (<?php echo App::money($balance); ?>)
                            </button>
                            <?php if ($balance > 10): ?>
                            <button type="button" class="quick-amount-btn" id="btnPayHalf" data-amount="<?php echo number_format(round($balance / 2, 2), 2, '.', ''); ?>">
                                50% (<?php echo App::money(round($balance / 2, 2)); ?>)
                            </button>
                            <?php endif; ?>
                            <button type="button" class="quick-amount-btn" id="btnPayReset">
                                Reset
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <div class="premium-select-wrap" id="view_payment_method_wrap">
                            <input type="hidden" name="payment_method" id="view_payment_method" value="cash">
                            <div class="premium-select-trigger" tabindex="0">
                                <div class="premium-select-trigger-content">
                                    <span class="premium-select-trigger-icon material-symbols-rounded">payments</span>
                                    <span class="premium-select-trigger-text">Cash</span>
                                </div>
                                <span class="material-symbols-rounded premium-select-chevron">expand_more</span>
                            </div>
                            <div class="premium-select-menu">
                                <div class="premium-select-item is-selected" data-value="cash" data-icon="payments" data-title="Cash">
                                    <div class="premium-select-item-lead">
                                        <span class="premium-select-item-icon material-symbols-rounded">payments</span>
                                        <div class="premium-select-item-info"><strong>Cash</strong></div>
                                    </div>
                                    <span class="material-symbols-rounded premium-select-item-check">check_circle</span>
                                </div>
                                <div class="premium-select-item" data-value="online" data-icon="account_balance" data-title="Online Transfer">
                                    <div class="premium-select-item-lead">
                                        <span class="premium-select-item-icon material-symbols-rounded">account_balance</span>
                                        <div class="premium-select-item-info"><strong>Online Transfer</strong></div>
                                    </div>
                                    <span class="material-symbols-rounded premium-select-item-check">check_circle</span>
                                </div>
                                <div class="premium-select-item" data-value="bank" data-icon="account_balance" data-title="Bank Transfer">
                                    <div class="premium-select-item-lead">
                                        <span class="premium-select-item-icon material-symbols-rounded">account_balance</span>
                                        <div class="premium-select-item-info"><strong>Bank Transfer</strong></div>
                                    </div>
                                    <span class="material-symbols-rounded premium-select-item-check">check_circle</span>
                                </div>
                                <div class="premium-select-item" data-value="cheque" data-icon="receipt_long" data-title="Cheque">
                                    <div class="premium-select-item-lead">
                                        <span class="premium-select-item-icon material-symbols-rounded">receipt_long</span>
                                        <div class="premium-select-item-info"><strong>Cheque</strong></div>
                                    </div>
                                    <span class="material-symbols-rounded premium-select-item-check">check_circle</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Reference / Slip # (Optional)</label>
                        <input class="form-input" type="text" name="reference_number" placeholder="Cheque #, Bank receipt or transaction ID">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Payment Note</label>
                        <textarea class="form-textarea" name="notes" rows="2" placeholder="Optional notes for this payment (e.g. Paid via mobile app, receipt handed over)"></textarea>
                    </div>

                    <div class="full" style="display:flex; justify-content:flex-end;">
                        <button class="btn btn-primary" type="submit" style="font-weight:850; padding:10px 22px; display:inline-flex; align-items:center; gap:8px;">
                            <span class="material-symbols-rounded">payments</span>Record Payment
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <!-- Payment History Timeline Card -->
        <section class="purchase-profile-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                <h3 style="margin:0; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-rounded" style="color:#059669;">history</span>
                    Payment History
                </h3>
                <span class="badge" style="background:#f8fafc; border:1px solid #e2e8f0; color:#475569; font-weight:800; font-size:11px;">
                    <?php echo count($payments); ?> Entry<?php echo count($payments) === 1 ? '' : 'ies'; ?>
                </span>
            </div>

            <div class="payment-timeline">
                <?php if (empty($payments)): ?>
                    <div class="empty-state" style="padding:28px 16px; text-align:center; color:#64748b;">
                        <span class="material-symbols-rounded" style="font-size:36px; color:#cbd5e1; display:block; margin-bottom:6px;">payments</span>
                        No payment entries recorded yet.
                    </div>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <div class="payment-row">
                            <div class="payment-row-icon">
                                <span class="material-symbols-rounded">payments</span>
                            </div>
                            <div>
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <strong><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></strong>
                                    <span class="badge" style="background:#f1f5f9; color:#334155; font-size:10px; font-weight:800; text-transform:uppercase;">
                                        <?php echo Security::escape($payment['payment_method'] ?? 'cash'); ?>
                                    </span>
                                </div>
                                <div class="page-subtitle" style="font-size:11.5px; margin-top:2px;">
                                    <?php echo !empty($payment['created_at']) ? date('h:i A', strtotime($payment['created_at'])) : 'Recorded'; ?>
                                    <?php echo !empty($payment['notes']) ? ' &bull; ' . Security::escape($payment['notes']) : ''; ?>
                                </div>
                            </div>
                            <strong style="color:#059669; font-size:14.5px;"><?php echo App::money($payment['amount']); ?></strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php if (!empty($purchase['notes'])): ?>
    <section class="purchase-profile-card" style="margin-top:20px;">
        <h3 style="margin:0 0 10px; display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-rounded" style="color:#64748b;">note</span>
            Bill Notes & Instructions
        </h3>
        <p style="color:#475569; line-height:1.7; margin:0; font-size:13.5px;"><?php echo nl2br(Security::escape($purchase['notes'])); ?></p>
    </section>
    <?php endif; ?>
</div>

<!-- ========================================== -->
<!-- 2. EXECUTIVE PRINTABLE PURCHASE BILL/VOUCHER -->
<!-- ========================================== -->
<div id="printablePurchaseInvoice">
    <div style="border-bottom:2px solid #0f172a; padding-bottom:12px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
            <h1 style="margin:0; font-size:24px; font-weight:900; color:#0f172a; letter-spacing:-0.02em;">
                <?php echo Security::escape($store['name'] ?? $storeSettings['company_name'] ?? 'DevAltum POS'); ?>
            </h1>
            <p style="margin:2px 0 0; font-size:12px; color:#475569;">
                <?php echo Security::escape($storeSettings['company_tagline'] ?? 'Official Purchase Bill / Goods Receiving Voucher'); ?>
            </p>
            <?php if (!empty($storeSettings['address'])): ?>
                <div style="font-size:11px; color:#64748b;"><?php echo Security::escape($storeSettings['address']); ?></div>
            <?php endif; ?>
            <?php if (!empty($storeSettings['phone'])): ?>
                <div style="font-size:11px; color:#64748b;">Phone: <?php echo Security::escape($storeSettings['phone']); ?></div>
            <?php endif; ?>
        </div>
        <div style="text-align:right;">
            <div style="display:inline-block; border:2px solid #0f172a; padding:6px 14px; border-radius:6px; font-weight:900; font-size:14px; text-transform:uppercase; letter-spacing:0.05em; background:#f8fafc;">
                PURCHASE VOUCHER
            </div>
            <div style="margin-top:6px; font-size:12px;"><strong>Bill #:</strong> <?php echo (int)$purchase['id']; ?></div>
            <div style="font-size:12px;"><strong>Date:</strong> <?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?></div>
            <?php if (!empty($purchase['bill_reference'])): ?>
                <div style="font-size:11.5px;"><strong>Supplier Ref:</strong> <?php echo Security::escape($purchase['bill_reference']); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Supplier & Info Box -->
    <div style="border:1px solid #cbd5e1; border-radius:6px; padding:10px 14px; margin-bottom:15px; display:flex; justify-content:space-between; background:#f8fafc;">
        <div>
            <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Vendor / Supplier Details</div>
            <div style="font-size:15px; font-weight:900; color:#0f172a; margin-top:2px;">
                <?php echo Security::escape($purchase['supplier_name']); ?>
            </div>
            <?php if (!empty($supplierPhone)): ?>
                <div style="font-size:11px; color:#334155; margin-top:2px;">Phone: <?php echo Security::escape($supplierPhone); ?></div>
            <?php endif; ?>
            <?php if (!empty($supplierAddress)): ?>
                <div style="font-size:11px; color:#334155;">Address: <?php echo Security::escape($supplierAddress); ?></div>
            <?php endif; ?>
        </div>
        <div style="text-align:right;">
            <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Bill Payment Status</div>
            <div style="font-size:14px; font-weight:900; margin-top:2px; color:<?php echo $balance <= 0 ? '#16a34a' : ($paidAmount > 0 ? '#d97706' : '#dc2626'); ?>;">
                <?php if ($balance <= 0): ?>
                    [ FULLY PAID ]
                <?php elseif ($paidAmount > 0): ?>
                    [ PARTIALLY PAID ]
                <?php else: ?>
                    [ FULL UDHAR / CREDIT ]
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Printable Items Table -->
    <table style="width:100%; border-collapse:collapse; margin-bottom:15px; font-size:12px;">
        <thead>
            <tr style="background:#f1f5f9; border-top:1.5px solid #0f172a; border-bottom:1.5px solid #0f172a;">
                <th style="padding:6px 8px; text-align:center; width:35px;">#</th>
                <th style="padding:6px 8px; text-align:left;">Item Description</th>
                <th style="padding:6px 8px; text-align:center; width:65px;">Unit</th>
                <th style="padding:6px 8px; text-align:right; width:70px;">Quantity</th>
                <th style="padding:6px 8px; text-align:right; width:95px;">Unit Cost</th>
                <th style="padding:6px 8px; text-align:right; width:95px;">Retail Price</th>
                <th style="padding:6px 8px; text-align:right; width:110px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $idx => $it): ?>
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:6px 8px; text-align:center;"><?php echo $idx + 1; ?></td>
                    <td style="padding:6px 8px;">
                        <strong><?php echo Security::escape($it['item_name']); ?></strong>
                        <?php if (!empty($it['batch_number'])): ?>
                            <span style="font-size:10px; color:#475569;"> (Batch: <?php echo Security::escape($it['batch_number']); ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:6px 8px; text-align:center; text-transform:uppercase;">
                        <?php echo Security::escape($it['unit_type'] ?? 'pcs'); ?>
                    </td>
                    <td style="padding:6px 8px; text-align:right; font-weight:800;">
                        <?php echo number_format((float)$it['quantity'], 2); ?>
                    </td>
                    <td style="padding:6px 8px; text-align:right;">
                        <?php echo App::money($it['unit_cost']); ?>
                    </td>
                    <td style="padding:6px 8px; text-align:right;">
                        <?php echo !empty($it['new_sale_price']) && (float)$it['new_sale_price'] > 0 ? App::money($it['new_sale_price']) : '&mdash;'; ?>
                    </td>
                    <td style="padding:6px 8px; text-align:right; font-weight:800;">
                        <?php echo App::money($it['subtotal'] ?? ($it['quantity'] * $it['unit_cost'])); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="border-top:1.5px solid #0f172a; border-bottom:1.5px solid #0f172a; font-weight:900; background:#f8fafc;">
                <td colspan="3" style="padding:6px 8px; text-align:right;">Grand Totals:</td>
                <td style="padding:6px 8px; text-align:right;"><?php echo number_format($totalQty, 2); ?></td>
                <td colspan="2"></td>
                <td style="padding:6px 8px; text-align:right;"><?php echo App::money($billTotal); ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Totals Summary & Notes Box -->
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:40px;">
        <div style="flex:1;">
            <?php if (!empty($purchase['notes'])): ?>
                <div style="border:1px solid #e2e8f0; border-radius:6px; padding:8px 12px; font-size:11px;">
                    <strong>Notes:</strong><br>
                    <?php echo nl2br(Security::escape($purchase['notes'])); ?>
                </div>
            <?php endif; ?>
        </div>
        <div style="width:260px;">
            <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                <tr>
                    <td style="padding:4px 0; color:#475569;">Gross Bill:</td>
                    <td style="padding:4px 0; text-align:right; font-weight:800;"><?php echo App::money($billTotal); ?></td>
                </tr>
                <tr>
                    <td style="padding:4px 0; color:#059669;">Paid Amount:</td>
                    <td style="padding:4px 0; text-align:right; font-weight:800; color:#059669;"><?php echo App::money($paidAmount); ?></td>
                </tr>
                <tr style="border-top:1.5px solid #0f172a;">
                    <td style="padding:6px 0; font-weight:900;">Balance Due:</td>
                    <td style="padding:6px 0; text-align:right; font-weight:900; font-size:14px; color:<?php echo $balance > 0 ? '#dc2626' : '#059669'; ?>;">
                        <?php echo App::money($balance); ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Official Signatures Area -->
    <div style="display:flex; justify-content:space-between; margin-top:50px; padding:0 30px;">
        <div style="text-align:center; width:200px;">
            <div style="border-top:1.5px dashed #64748b; padding-top:6px; font-weight:800; font-size:11px;">
                Prepared / Received By
            </div>
        </div>
        <div style="text-align:center; width:200px;">
            <div style="border-top:1.5px dashed #64748b; padding-top:6px; font-weight:800; font-size:11px;">
                Vendor / Authorized Signature
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 3. COMPACT DELETE CONFIRMATION MODAL       -->
<!-- ========================================== -->
<div id="deletePurchaseModal" class="compact-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <div class="compact-modal-card">
        <div class="compact-modal-header">
            <div class="compact-modal-icon-badge">
                <span class="material-symbols-rounded">delete_forever</span>
            </div>
            <div>
                <h4 id="deleteModalTitle" class="compact-modal-title">Delete Purchase Bill #<?php echo (int)$purchase['id']; ?></h4>
                <p class="compact-modal-sub">Permanently revert product stock & ledger balance</p>
            </div>
        </div>
        <div class="compact-modal-body">
            <p class="compact-modal-desc">
                Are you sure you want to delete this purchase bill? This will <strong>deduct the purchased quantities</strong> from inventory stock and <strong>re-sync the supplier ledger</strong>.
            </p>
            <div class="compact-modal-info-pill">
                <div class="compact-modal-info-row">
                    <span>Purchase Bill:</span>
                    <strong id="modalBillRef">Bill #<?php echo (int) $purchase['id']; ?></strong>
                </div>
                <div class="compact-modal-info-row">
                    <span>Supplier / Vendor:</span>
                    <strong id="modalSupplierName"><?php echo Security::escape($purchase['supplier_name']); ?></strong>
                </div>
                <div class="compact-modal-info-row">
                    <span>Bill Total:</span>
                    <strong id="modalBillTotal" style="color:#1d4ed8;"><?php echo App::money($purchase['bill_total']); ?></strong>
                </div>
                <div class="compact-modal-info-row">
                    <span>Purchase Date:</span>
                    <strong id="modalBillDate"><?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?></strong>
                </div>
            </div>
        </div>
        <form id="deletePurchaseForm" method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchaseDelete/<?php echo (int) $purchase['id']; ?>" data-no-ajax>
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

<!-- ========================================== -->
<!-- 4. JAVASCRIPT LOGIC                        -->
<!-- ========================================== -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Payment Method Dropdown
    const wrap = document.getElementById('view_payment_method_wrap');
    if (wrap) {
        const trigger = wrap.querySelector('.premium-select-trigger');
        const hidden = document.getElementById('view_payment_method');
        const triggerIcon = wrap.querySelector('.premium-select-trigger-icon');
        const triggerText = wrap.querySelector('.premium-select-trigger-text');
        const items = wrap.querySelectorAll('.premium-select-item');

        trigger?.addEventListener('click', (e) => {
            e.stopPropagation();
            wrap.classList.toggle('is-open');
        });

        items.forEach(item => {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                if (hidden) hidden.value = item.dataset.value;
                if (triggerIcon) triggerIcon.textContent = item.dataset.icon;
                if (triggerText) triggerText.textContent = item.dataset.title;
                items.forEach(it => it.classList.toggle('is-selected', it === item));
                wrap.classList.remove('is-open');
            });
        });

        document.addEventListener('click', (e) => {
            if (!wrap.contains(e.target)) wrap.classList.remove('is-open');
        });
    }

    // 2. Dynamic Live Remaining Balance Calculation & Quick Shortcut Buttons
    const maxBalance = <?php echo json_encode((float)$balance); ?>;
    const paymentAmountInput = document.getElementById('paymentAmountInput');
    const paymentFeedbackRemaining = document.getElementById('paymentFeedbackRemaining');
    const btnPayFull = document.getElementById('btnPayFull');
    const btnPayHalf = document.getElementById('btnPayHalf');
    const btnPayReset = document.getElementById('btnPayReset');

    function updateRemainingFeedback() {
        if (!paymentAmountInput || !paymentFeedbackRemaining) return;
        const entered = parseFloat(paymentAmountInput.value) || 0;
        const rem = Math.max(0, maxBalance - entered);
        paymentFeedbackRemaining.textContent = 'Bal After Pay: ' + '<?php echo Security::escape($storeSettings['currency_symbol'] ?? 'Rs'); ?> ' + rem.toFixed(2);
        
        if (rem <= 0.001) {
            paymentFeedbackRemaining.style.color = '#059669';
        } else {
            paymentFeedbackRemaining.style.color = '#dc2626';
        }
    }

    if (paymentAmountInput) {
        paymentAmountInput.addEventListener('input', () => {
            updateRemainingFeedback();
            document.querySelectorAll('.quick-amount-btn').forEach(btn => btn.classList.remove('active'));
        });
        updateRemainingFeedback();
    }

    btnPayFull?.addEventListener('click', () => {
        if (!paymentAmountInput) return;
        paymentAmountInput.value = btnPayFull.dataset.amount;
        document.querySelectorAll('.quick-amount-btn').forEach(btn => btn.classList.remove('active'));
        btnPayFull.classList.add('active');
        updateRemainingFeedback();
    });

    btnPayHalf?.addEventListener('click', () => {
        if (!paymentAmountInput) return;
        paymentAmountInput.value = btnPayHalf.dataset.amount;
        document.querySelectorAll('.quick-amount-btn').forEach(btn => btn.classList.remove('active'));
        btnPayHalf.classList.add('active');
        updateRemainingFeedback();
    });

    btnPayReset?.addEventListener('click', () => {
        if (!paymentAmountInput) return;
        paymentAmountInput.value = '';
        document.querySelectorAll('.quick-amount-btn').forEach(btn => btn.classList.remove('active'));
        updateRemainingFeedback();
    });

    // 3. Compact Delete Modal Interactivity
    const deleteModal = document.getElementById('deletePurchaseModal');

    document.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('.btn-delete-purchase');
        if (deleteBtn) {
            e.preventDefault();
            deleteModal?.classList.add('is-open');
        }

        if (e.target === deleteModal || e.target.closest('#cancelDeleteBtn')) {
            deleteModal?.classList.remove('is-open');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && deleteModal?.classList.contains('is-open')) {
            deleteModal.classList.remove('is-open');
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
