<?php ob_start(); ?>
<?php
$balance = (float) ($purchase['balance_amount'] ?? 0);
$billTotal = (float) ($purchase['bill_total'] ?? 0);
$paidAmount = (float) ($purchase['paid_amount'] ?? 0);
?>

<style>
    .purchase-edit-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 330px;
        gap: 18px;
        align-items: start;
    }

    .purchase-form-card {
        border: 1px solid var(--border);
        border-radius: 22px;
        background: linear-gradient(150deg, #ffffff, #f8fbff);
        box-shadow: 0 20px 48px rgba(15,23,42,.09);
        padding: 24px;
    }

    .purchase-form-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
        padding-bottom: 18px;
        border-bottom: 1px solid #e2e8f0;
    }

    .purchase-form-icon {
        width: 54px;
        height: 54px;
        border-radius: 18px;
        display: grid;
        place-items: center;
        background: #0f172a;
        color: #fff;
        box-shadow: 0 16px 30px rgba(15,23,42,.18);
        flex: 0 0 auto;
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

    @keyframes selectMenuFadeIn {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
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
        top: calc(var(--header-height, 64px) + 18px);
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

    .purchase-back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        width: fit-content;
        padding: 8px 14px;
        border-radius: 12px;
        background: #fff;
        border: 1px solid var(--border);
        color: #0f172a;
        font-weight: 800;
        box-shadow: 0 8px 18px rgba(15,23,42,.05);
        margin-bottom: 12px;
    }

    @media (max-width: 1024px) {
        .purchase-edit-shell {
            grid-template-columns: 1fr;
        }
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
</style>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
    <div>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchaseView/<?php echo (int) $purchase['id']; ?>" class="purchase-back-link">
            <span class="material-symbols-rounded">arrow_back</span>Back to Purchase View
        </a>
        <h1>Edit Purchase Bill #<?php echo (int) $purchase['id']; ?></h1>
        <p class="page-subtitle">Modify items, quantities, costs, and settlement amounts for this purchase bill.</p>
    </div>
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchaseView/<?php echo (int) $purchase['id']; ?>" class="btn btn-secondary" style="font-weight:800; display:inline-flex; align-items:center; gap:6px;">
            <span class="material-symbols-rounded">visibility</span>View Bill
        </a>
        <button type="button" class="btn btn-danger btn-delete-purchase" 
            data-id="<?php echo (int)$purchase['id']; ?>" 
            data-supplier="<?php echo Security::escape($purchase['supplier_name']); ?>" 
            data-total="<?php echo Security::escape(App::money($purchase['bill_total'])); ?>" 
            data-date="<?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?>" 
            style="font-weight:800; display:inline-flex; align-items:center; gap:6px; background:#ef4444; color:#fff; border:none; padding:8px 14px; border-radius:12px; cursor:pointer;">
            <span class="material-symbols-rounded">delete</span>Delete Bill
        </button>
    </div>
</div>

<div class="purchase-edit-shell">
    <form class="purchase-form-card" id="purchaseEditForm" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchaseUpdate/<?php echo (int) $purchase['id']; ?>" method="POST" data-no-ajax>
        <?php echo Security::csrfField(); ?>
        
        <div class="purchase-form-head">
            <div>
                <h3 style="margin-bottom:6px; display:flex; align-items:center; gap:8px;">
                    <span>Modify Inward Bill & Items</span>
                    <span class="badge" style="background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:800; border:1px solid #bfdbfe;">Bill #<?php echo (int) $purchase['id']; ?></span>
                </h3>
                <p class="page-subtitle" style="margin:0;">Updating this bill will adjust inventory stock levels and re-sync the supplier Udhar balance.</p>
            </div>
            <div class="purchase-form-icon"><span class="material-symbols-rounded">edit_document</span></div>
        </div>

        <!-- Top Row: Supplier, Date, Bill Ref -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; margin-bottom:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:18px; padding:16px;">
            <div class="form-group" style="position:relative; margin:0;">
                <label class="form-label" style="font-weight:850; font-size:12.5px;">Supplier / Vendor *</label>
                <div class="searchable-dropdown-wrap" id="supplier_dropdown_wrap" style="position:relative;">
                    <div class="field-icon-wrap" style="position:relative;">
                        <span class="material-symbols-rounded" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#64748b; font-size:20px; z-index:2; pointer-events:none;">store</span>
                        <input class="form-input" type="text" name="supplier_name" id="purchase_supplier_input" value="<?php echo Security::escape($purchase['supplier_name']); ?>" required placeholder="Search or type supplier..." autocomplete="off" style="padding-left:44px; padding-right:40px; font-weight:800;">
                        <span class="material-symbols-rounded" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:20px; pointer-events:none;">expand_more</span>
                    </div>
                    <div id="supplier_search_dropdown_menu" class="searchable-dropdown-menu" style="display:none; position:absolute; top:calc(100% + 4px); left:0; right:0; max-height:240px; overflow-y:auto; background:#ffffff; border:1px solid #cbd5e1; border-radius:14px; box-shadow:0 16px 36px rgba(15,23,42,.14); z-index:1050; padding:6px;">
                        <div id="supplier_dropdown_items_list"></div>
                    </div>
                </div>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-weight:850; font-size:12.5px;">Purchase Date *</label>
                <input class="form-input" type="date" name="purchase_date" value="<?php echo Security::escape($purchase['purchase_date']); ?>" required style="font-weight:700;">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label" style="font-weight:850; font-size:12.5px;">Bill / Invoice Ref #</label>
                <input class="form-input" type="text" name="bill_reference" value="<?php echo Security::escape($purchase['bill_reference'] ?? ''); ?>" placeholder="e.g. INV-8920, Challan #" style="font-weight:700;">
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
                    <span style="font-size:11.5px; color:#64748b;">Add or edit products, costs, and quantities in this bill.</span>
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
                <span style="font-size:11px; color:#64748b;">Subtotals auto-calculate as you edit quantity or cost.</span>
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
                    <input class="form-input" type="number" step="0.01" min="0" name="bill_total" id="purchase_bill_total_input" value="<?php echo number_format($billTotal, 2, '.', ''); ?>" readonly required style="background:#f1f5f9; font-weight:900; font-size:15px; color:#1e40af;">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-weight:850; font-size:12.5px;">Amount Paid Now</label>
                    <input class="form-input" type="number" step="0.01" min="0" name="paid_amount" id="purchase_paid_amount_input" value="<?php echo number_format($paidAmount, 2, '.', ''); ?>" placeholder="0.00" style="font-weight:900; font-size:15px; color:#059669; border-color:#a7f3d0;">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-weight:850; font-size:12.5px;">Remaining Udhar (Payable)</label>
                    <input class="form-input" type="text" id="purchase_balance_display" readonly value="<?php echo App::money($balance); ?>" style="background:#f1f5f9; font-weight:900; font-size:15px; color:#dc2626;">
                </div>
            </div>

            <!-- Udhar Alert Banner -->
            <div id="udhar_alert_box" style="display:<?php echo $balance > 0 ? 'flex' : 'none'; ?>; padding:12px 14px; border-radius:12px; background:#fffbeb; border:1px solid #fde68a; color:#92400e; font-size:12.5px; align-items:center; gap:10px;">
                <span class="material-symbols-rounded" style="color:#d97706; font-size:22px; flex-shrink:0;">account_balance_wallet</span>
                <div>
                    <strong id="udhar_alert_title">Partial Payment / Udhar: <?php echo App::money($balance); ?> Remaining</strong>
                    <div id="udhar_alert_desc" style="font-size:11.5px; color:#b45309; margin-top:2px;">
                        Remaining balance will be reflected in the supplier ledger.
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes & Submission -->
        <div class="form-group" style="margin-bottom:18px;">
            <label class="form-label" style="font-weight:850; font-size:12.5px;">Bill Notes / Private Memo</label>
            <textarea class="form-textarea" name="notes" rows="2" placeholder="Delivery challan details, payment promise date, batch notes..."><?php echo Security::escape($purchase['notes'] ?? ''); ?></textarea>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; padding-top:14px; border-top:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:10px;">
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchaseView/<?php echo (int) $purchase['id']; ?>" class="btn btn-secondary" style="font-weight:800;">
                    Cancel
                </a>
                <button type="button" class="btn btn-danger btn-delete-purchase" 
                    data-id="<?php echo (int)$purchase['id']; ?>" 
                    data-supplier="<?php echo Security::escape($purchase['supplier_name']); ?>" 
                    data-total="<?php echo Security::escape(App::money($purchase['bill_total'])); ?>" 
                    data-date="<?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?>" 
                    style="font-weight:800; display:inline-flex; align-items:center; gap:6px; background:#ef4444; color:#fff; border:none; padding:10px 16px; border-radius:12px; cursor:pointer;">
                    <span class="material-symbols-rounded">delete</span>Delete Bill
                </button>
            </div>
            <button class="btn btn-primary" type="submit" style="font-weight:900; font-size:14px; padding:12px 28px; box-shadow:0 12px 28px rgba(37,99,235,.25);">
                <span class="material-symbols-rounded">save</span>
                Update Purchase Bill & Sync Stock
            </button>
        </div>
    </form>

    <aside class="purchase-summary-card">
        <h3 style="margin-bottom:8px;">Bill Status</h3>
        <p style="color:rgba(255,255,255,.72); line-height:1.6; margin:0 0 14px;">Live summary of this purchase order and supplier payment ledger.</p>
        
        <div class="purchase-summary-row"><span>Total Items</span><strong id="side_items_count"><?php echo count($items ?? []); ?> items</strong></div>
        <div class="purchase-summary-row"><span>Total Quantity</span><strong id="side_total_qty"><?php echo number_format((float)array_sum(array_map(fn($it)=>(float)$it['quantity'], $items ?? [])), 2); ?> units</strong></div>
        <div class="purchase-summary-row"><span>Gross Bill Total</span><strong id="side_bill_total" style="font-size:15px; color:#60a5fa;"><?php echo App::money($billTotal); ?></strong></div>
        <div class="purchase-summary-row"><span>Paid Amount</span><strong id="side_paid_amount" style="color:#4ade80;"><?php echo App::money($paidAmount); ?></strong></div>
        <div class="purchase-summary-row"><span>Remaining Udhar</span><strong id="side_balance_amount" style="color:#f87171;"><?php echo App::money($balance); ?></strong></div>
        <div class="purchase-summary-row"><span>Original Bill Date</span><strong><?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?></strong></div>
        <div class="purchase-summary-row"><span>Supplier</span><strong><?php echo Security::escape($purchase['supplier_name']); ?></strong></div>
    </aside>
</div>

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
                    <span>Date:</span>
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

<script>
(() => {
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

    const initialItemsData = <?php echo json_encode($items ?? []); ?>;

    const currencySymbol = 'Rs ';
    const formatMoney = (amount) => {
        return currencySymbol + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const escapeHtml = (str) => {
        return String(str || '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
    };

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

    const tableTotalQty = document.getElementById('table_total_qty');
    const tableGrandTotal = document.getElementById('table_grand_total');

    const sideItemsCount = document.getElementById('side_items_count');
    const sideTotalQty = document.getElementById('side_total_qty');
    const sideBillTotal = document.getElementById('side_bill_total');
    const sidePaidAmount = document.getElementById('side_paid_amount');
    const sideBalanceAmount = document.getElementById('side_balance_amount');

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

        // Side preview
        if (sideItemsCount) sideItemsCount.textContent = `${validItemsCount} item${validItemsCount === 1 ? '' : 's'}`;
        if (sideTotalQty) sideTotalQty.textContent = `${totalQty.toFixed(2)} units`;
        if (sideBillTotal) sideBillTotal.textContent = formatMoney(grandTotal);

        // Udhar
        const paid = parseFloat(purchasePaidAmountInput?.value) || 0;
        const udharBalance = Math.max(0, Math.round((grandTotal - paid) * 100) / 100);

        if (purchaseBalanceDisplay) {
            purchaseBalanceDisplay.value = formatMoney(udharBalance);
        }
        if (sidePaidAmount) {
            sidePaidAmount.textContent = formatMoney(paid);
        }
        if (sideBalanceAmount) {
            sideBalanceAmount.textContent = formatMoney(udharBalance);
        }

        const supName = document.getElementById('purchase_supplier_input')?.value.trim() || 'Supplier';
        if (udharBalance <= 0) {
            if (udharStatusPill) {
                udharStatusPill.innerHTML = '<span class="material-symbols-rounded" style="color:#059669; font-size:17px;">verified</span><span class="pill-text">Fully Paid (No Udhar)</span>';
                udharStatusPill.style.background = '#ecfdf5';
                udharStatusPill.style.color = '#059669';
                udharStatusPill.style.borderColor = '#a7f3d0';
            }
            if (udharAlertBox) udharAlertBox.style.display = 'none';
        } else if (paid > 0) {
            if (udharStatusPill) {
                udharStatusPill.innerHTML = `<span class="material-symbols-rounded" style="color:#b45309; font-size:17px;">pending</span><span class="pill-text">Partial Udhar: ${formatMoney(udharBalance)}</span>`;
                udharStatusPill.style.background = '#fffbeb';
                udharStatusPill.style.color = '#b45309';
                udharStatusPill.style.borderColor = '#fde68a';
            }
            if (udharAlertBox) {
                udharAlertBox.style.display = 'flex';
                if (udharAlertTitle) udharAlertTitle.textContent = `Partial Payment & Udhar: ${formatMoney(udharBalance)} Remaining`;
                if (udharAlertDesc) udharAlertDesc.innerHTML = `Paid ${formatMoney(paid)}. Remaining <strong>${formatMoney(udharBalance)}</strong> recorded in <strong>${escapeHtml(supName)}</strong>'s ledger account.`;
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
                if (udharAlertDesc) udharAlertDesc.innerHTML = `Total bill of <strong>${formatMoney(udharBalance)}</strong> recorded as 100% Udhar in <strong>${escapeHtml(supName)}</strong>'s ledger.`;
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

        const initName = initial?.item_name || initial?.name || '';
        const initProdId = initial?.product_id || '';
        const initQty = initial?.quantity !== undefined ? initial.quantity : '1';
        const initCost = initial?.unit_cost !== undefined ? initial.unit_cost : (initial?.cost !== undefined ? initial.cost : '0.00');
        const initPrice = initial?.new_sale_price !== undefined && initial.new_sale_price !== null ? initial.new_sale_price : (initial?.price !== undefined ? initial.price : '');

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
                    <input type="hidden" name="items[${rId}][product_id]" class="row-product-id" value="${initProdId}">
                    <div style="position:relative;">
                        <input type="text" name="items[${rId}][item_name]" class="form-input row-item-name" required placeholder="Type product name or barcode..." autocomplete="off" value="${escapeHtml(initName)}" style="font-weight:800; font-size:13px; min-height:38px; padding-right:26px;">
                        <span class="row-matched-indicator" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); display:${initProdId ? 'inline-block' : 'none'}; color:#16a34a; font-size:16px;" title="Linked to catalog product">✓</span>
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
                <input type="number" step="0.001" min="0.001" name="items[${rId}][quantity]" class="form-input row-qty" required value="${initQty}" style="text-align:right; font-weight:850; min-height:38px; padding:4px 8px;">
            </td>
            <td style="padding:6px 6px; text-align:right;">
                <input type="number" step="0.01" min="0" name="items[${rId}][unit_cost]" class="form-input row-cost" required value="${initCost}" style="text-align:right; font-weight:850; min-height:38px; padding:4px 8px;">
            </td>
            <td style="padding:6px 6px; text-align:right;">
                <input type="number" step="0.01" min="0" name="items[${rId}][new_sale_price]" class="form-input row-price" placeholder="Optional" value="${initPrice}" style="text-align:right; font-weight:800; min-height:38px; padding:4px 8px;">
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
                                    <div style="font-size:9.5px; color:#3b82f6;">Auto-saves to product catalog on bill submit</div>
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

    btnQuickFullPay?.addEventListener('click', () => {
        if (purchasePaidAmountInput && purchaseBillTotalInput) {
            purchasePaidAmountInput.value = purchaseBillTotalInput.value;
            recalcBill();
        }
    });

    btnQuickFullUdhar?.addEventListener('click', () => {
        if (purchasePaidAmountInput) {
            purchasePaidAmountInput.value = '0.00';
            recalcBill();
        }
    });

    purchasePaidAmountInput?.addEventListener('input', recalcBill);

    // Supplier dropdown
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
    document.addEventListener('click', (e) => {
        document.querySelectorAll('.row-unit-picker.is-open').forEach(p => {
            if (!p.contains(e.target)) {
                p.classList.remove('is-open');
                const um = p.querySelector('.row-unit-menu');
                if (um) um.style.display = 'none';
            }
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

    // Populate initial items
    if (initialItemsData && initialItemsData.length > 0) {
        initialItemsData.forEach(it => addPurchaseRow(it));
    } else {
        addPurchaseRow();
    }

    // Compact Delete Modal Handler
    const deleteModal = document.getElementById('deletePurchaseModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

    document.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('.btn-delete-purchase');
        if (deleteBtn) {
            e.preventDefault();
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
})();
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
