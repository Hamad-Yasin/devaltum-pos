<?php ob_start(); ?>

<?php
$customerName = $invoice['customer_name_snapshot'] ?: '';
$customerPhone = $invoice['customer_phone_snapshot'] ?: '';
$invoiceDiscount = (float) ($invoice['discount'] ?? 0);
$invoiceOpenDue = max(
    0,
    max(
        ((float) ($invoice['total'] ?? 0) - (float) ($invoice['paid_amount'] ?? 0)),
        (float) ($invoice['balance_due'] ?? 0)
    )
);
$invoiceStatus = strtolower((string) ($invoice['status'] ?? 'paid'));
if (!in_array($invoiceStatus, ['paid', 'unpaid', 'draft'], true)) {
    $invoiceStatus = $invoiceOpenDue > 0 ? 'unpaid' : 'paid';
}
$invoicePaymentMethod = strtolower((string) ($invoice['payment_method'] ?? 'cash'));
if (!in_array($invoicePaymentMethod, ['cash', 'online', 'credit'], true)) {
    $invoicePaymentMethod = $invoiceOpenDue > 0 ? 'credit' : 'cash';
}
$invoicePaidAmount = (float) ($invoice['paid_amount'] ?? 0);
$invoiceCreditDueLocal = '';
if (!empty($invoice['credit_due_date'])) {
    $invoiceCreditDueLocal = date('Y-m-d\TH:i', strtotime($invoice['credit_due_date']));
}
$invoiceCreditPrivateNote = (string) ($invoice['credit_private_note'] ?? '');
$showProductImages = App::setting('show_product_images', '1') === '1';
?>

<style>
    .remove-item-toggle {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        border: 1px solid #fecaca;
        background: #fff1f2;
        color: var(--error);
        display: grid;
        place-items: center;
        cursor: pointer;
        transition: var(--transition);
    }
    .remove-item-toggle:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm);
        background: #ffe4e6;
    }
    .sale-item-row.pending-remove {
        background: #fff7f7;
    }
    .sale-item-row.pending-remove td {
        color: var(--text-muted);
    }
    .sale-remove-note {
        color: var(--error);
        font-size: 12px;
        font-weight: 700;
        margin-top: 6px;
    }
    .item-with-image {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 190px;
    }
    .item-thumb {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, .32);
        background: linear-gradient(135deg, #ffffff, #eef6ff);
        display: grid;
        place-items: center;
        color: var(--primary);
        box-shadow: 0 8px 18px rgba(15, 23, 42, .08);
    }
    .item-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }
    .item-thumb .material-symbols-rounded {
        font-size: 21px;
    }
    .sale-add-items {
        margin-top: 18px;
        padding: 16px;
        border: 1px solid rgba(148, 163, 184, .35);
        border-radius: 18px;
        background: linear-gradient(180deg, #f8fbff 0%, #f3f8ff 100%);
        box-shadow: var(--shadow-sm);
    }
    .sale-add-items-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 14px;
    }
    .sale-add-tools {
        margin-bottom: 14px;
        display: grid;
        gap: 8px;
    }
    .sale-add-row-btn {
        border: none;
        background: linear-gradient(135deg, #0f6dff, #22c55e);
        color: #fff;
        box-shadow: 0 10px 20px rgba(15, 109, 255, .25);
        transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
    }
    .sale-add-row-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 24px rgba(15, 109, 255, .30);
        filter: saturate(1.06);
    }
    .sale-add-row-btn .material-symbols-rounded {
        animation: sale-add-icon-pulse 1.5s ease-in-out infinite;
    }
    @keyframes sale-add-icon-pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.16); }
    }
    .sale-add-search-wrap {
        position: relative;
    }
    .sale-add-search-wrap .material-symbols-rounded {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 19px;
        color: var(--text-muted);
    }
    .sale-add-search-wrap .form-input {
        padding-left: 40px;
    }
    .sale-add-suggestions {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        z-index: 30;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: 0 18px 28px rgba(2, 6, 23, .12);
        max-height: 260px;
        overflow-y: auto;
        display: none;
    }
    .sale-add-suggestions.open {
        display: block;
    }
    .sale-add-suggestion-item {
        width: 100%;
        border: none;
        background: #fff;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        cursor: pointer;
        text-align: left;
        border-bottom: 1px solid #eef2f7;
    }
    .sale-add-suggestion-item:last-child {
        border-bottom: none;
    }
    .sale-add-suggestion-item:hover {
        background: #f8fbff;
    }
    .sale-add-suggestion-name {
        font-weight: 700;
        color: var(--text-main);
    }
    .sale-add-suggestion-meta {
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 600;
    }
    .sale-add-hint {
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 700;
    }
    .sale-add-form-card {
        --sale-add-grid-columns: 2.3fr 1.5fr .8fr 1fr 1fr 52px;
    }
    .sale-add-row-head {
        display: grid;
        grid-template-columns: var(--sale-add-grid-columns);
        gap: 12px;
        margin-bottom: 10px;
    }
    .sale-add-row-head span {
        font-size: 11px;
        font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .sale-add-row-head span:nth-child(3),
    .sale-add-row-head span:nth-child(4),
    .sale-add-row-head span:nth-child(5),
    .sale-add-row-head span:nth-child(6) {
        text-align: center;
    }
    .sale-add-item-row {
        display: grid;
        grid-template-columns: var(--sale-add-grid-columns);
        gap: 12px;
        margin-bottom: 10px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 12px;
    }
    .sale-add-form-card {
        border: 1px solid rgba(148, 163, 184, .35);
        border-radius: 16px;
        background: #fff;
        padding: 12px;
        overflow-x: auto;
    }
    .sale-add-item-rows {
        display: grid;
        gap: 10px;
    }
    .sale-add-empty {
        border: 1px dashed var(--border);
        border-radius: 12px;
        padding: 12px;
        font-size: 13px;
        color: var(--text-muted);
        text-align: center;
    }
    .sale-add-item-row .remove-item-toggle {
        align-self: center;
    }
    .sale-add-item-row > * {
        min-width: 0;
    }
    .sale-add-item-row .form-input,
    .sale-add-item-row .form-select {
        min-height: 42px;
        width: 100%;
    }
    .sale-add-item-row select[name="add_item_product_id[]"] {
        text-align: left;
    }
    .sale-add-item-row input[name="add_item_name[]"] {
        text-align: left;
    }
    .sale-add-item-row input[name="add_item_quantity[]"] {
        text-align: center;
    }
    .sale-add-item-row input[name="add_item_price[]"],
    .sale-add-item-row input[name="add_item_discount[]"] {
        text-align: center;
    }
    .sale-add-item-row .remove-item-toggle {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        justify-self: center;
    }
    .sale-add-field {
        min-width: 0;
    }
    .sale-add-mobile-label {
        display: none;
        margin-bottom: 6px;
        font-size: 11px;
        font-weight: 900;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .sale-edit-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 24px;
        padding-top: 18px;
        border-top: 1px solid var(--border);
    }
    @media (max-width: 1200px) {
        .sale-add-form-card {
            --sale-add-grid-columns: 2fr 1.3fr .75fr .95fr .95fr 48px;
        }
        .sale-add-row-head,
        .sale-add-item-row {
            gap: 10px;
        }
    }
    @media (max-width: 900px) {
        .sale-add-row-head {
            display: none;
        }
        .sale-add-item-row {
            grid-template-columns: 1fr;
            gap: 12px;
            border-radius: 18px;
            padding: 14px;
        }
        .sale-add-form-card {
            overflow-x: visible;
        }
        .sale-add-mobile-label {
            display: block;
        }
        .sale-add-item-row .remove-item-toggle {
            width: 100%;
            min-height: 42px;
        }
    }
    @media (max-width: 640px) {
        .sale-add-items-head {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        .sale-add-row-btn {
            align-self: flex-start;
            min-width: 132px;
        }
        .sale-edit-actions {
            margin-top: 24px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 10px;
            align-items: stretch;
        }
        .sale-edit-actions .btn {
            min-width: 0;
            width: 100%;
            min-height: 46px;
            padding: 8px 10px;
            border-radius: 14px;
            justify-content: center;
            gap: 7px;
            font-size: 13px;
            line-height: 1.15;
            text-align: center;
            white-space: normal;
        }
        .sale-edit-actions .btn .material-symbols-rounded {
            font-size: 20px;
            flex: 0 0 auto;
        }
    }
    @media (max-width: 380px) {
        .sale-edit-actions {
            gap: 8px;
        }
        .sale-edit-actions .btn {
            min-height: 42px;
            padding: 7px 8px;
            border-radius: 12px;
            font-size: 12px;
            gap: 5px;
        }
        .sale-edit-actions .btn .material-symbols-rounded {
            font-size: 18px;
        }
    }
    .sale-edit-layout {
        display: grid;
        gap: 18px;
    }
    .sale-edit-layout > .card {
        width: 100%;
    }
</style>

<div class="page-header">
    <div>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/index" class="page-subtitle">Back to Sales</a>
        <h1>Edit Sale <?php echo Security::escape($invoice['custom_id']); ?></h1>
        <p class="page-subtitle">Update customer details, transaction type and sale notes.</p>
    </div>
    <a class="btn btn-secondary" target="_blank" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/pos/receipt/<?php echo $invoice['id']; ?>"><span class="material-symbols-rounded">receipt_long</span>Open Receipt</a>
</div>

<div class="sale-edit-layout">
    <section class="card">
        <h3 style="margin-bottom:18px;">Sale Details</h3>
        <form id="sale-edit-form" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/edit/<?php echo $invoice['id']; ?>" method="POST" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <div id="sale-remove-hidden-inputs"></div>
            <div class="split-grid">
                <div class="form-group">
                    <label class="form-label">Customer Name</label>
                    <input class="form-input" type="text" name="customer_name" value="<?php echo Security::escape($customerName); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Customer Phone</label>
                    <input class="form-input" type="text" name="customer_phone" value="<?php echo Security::escape($customerPhone); ?>">
                </div>
            </div>
            <div class="split-grid">
                <div class="form-group">
                    <label class="form-label">Transaction Type</label>
                    <select class="form-select" name="transaction_type">
                        <?php foreach (['Sale', 'Exchange', 'Return', 'Refund'] as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $invoice['transaction_type'] === $type ? 'selected' : ''; ?>><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="paid" <?php echo $invoiceStatus === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="unpaid" <?php echo $invoiceStatus === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                        <option value="draft" <?php echo $invoiceStatus === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
            </div>
            <div class="split-grid">
                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <select class="form-select" id="invoice-payment-method" name="payment_method">
                        <option value="cash" <?php echo $invoicePaymentMethod === 'cash' ? 'selected' : ''; ?>>Cash</option>
                        <option value="online" <?php echo $invoicePaymentMethod === 'online' ? 'selected' : ''; ?>>Online</option>
                        <option value="credit" <?php echo $invoicePaymentMethod === 'credit' ? 'selected' : ''; ?>>Credit</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Current Remaining</label>
                    <input class="form-input" type="text" value="<?php echo App::money($invoiceOpenDue); ?>" readonly>
                </div>
            </div>

            <div id="invoice-credit-fields" style="display:<?php echo $invoicePaymentMethod === 'credit' ? 'block' : 'none'; ?>; margin-bottom:16px; padding:14px; border-radius:14px; border:1px solid #fde68a; background:#fffbeb;">
                <div style="color:#92400e; font-size:13px; font-weight:700; margin-bottom:12px;">Credit Details</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Paid Amount</label>
                        <input id="invoice-credit-paid" type="number" step="0.01" min="0" name="credit_partial_payment" class="form-input" value="<?php echo (float) $invoicePaidAmount; ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Paid Via</label>
                        <select name="credit_partial_payment_method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Due Date</label>
                        <input type="datetime-local" name="credit_due_date" class="form-input" value="<?php echo Security::escape($invoiceCreditDueLocal); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Private Message</label>
                        <input type="text" name="credit_private_note" class="form-input" placeholder="Private note for credit..." value="<?php echo Security::escape($invoiceCreditPrivateNote); ?>">
                    </div>
                </div>
                <div id="invoice-credit-remaining" style="margin-top:10px; font-size:12px; font-weight:700; color:#92400e;"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Invoice Discount</label>
                <input id="invoice-discount-input" class="form-input" type="number" step="0.01" min="0" name="discount" value="<?php echo $invoiceDiscount; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-textarea" name="notes"><?php echo Security::escape($invoice['notes']); ?></textarea>
            </div>
            <div id="invoice-live-badge" style="margin:-2px 0 14px; padding:10px 12px; border-radius:12px; font-size:12px; font-weight:800; border:1px solid #dbeafe; background:#eff6ff; color:#1d4ed8;"></div>

            <div class="sale-add-items">
                <div class="sale-add-items-head">
                    <div>
                        <strong>Add New Item(s)</strong>
                        <div class="page-subtitle">Add extra products and select how payment is handled.</div>
                    </div>
                    <button type="button" class="btn sale-add-row-btn" id="add-sale-item-row-btn">
                        <span class="material-symbols-rounded">add_circle</span>Add Row
                    </button>
                </div>

                <div style="margin-bottom:14px; padding:14px 16px; border-radius:14px; border:1px solid var(--border); background:#f8fbff;">
                    <label class="form-label" style="margin-bottom:8px; display:block; font-weight:700;">Payment Method for Added Items</label>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;" id="add-payment-method-options">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; padding:10px 18px; border-radius:12px; border:2px solid #22c55e; background:#f0fdf4; font-weight:600;">
                            <input type="radio" name="add_item_payment_method" value="cash" checked style="accent-color:#22c55e;">
                            <span class="material-symbols-rounded" style="color:#22c55e; font-size:20px;">payments</span>
                            Cash
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; padding:10px 18px; border-radius:12px; border:2px solid var(--border); background:#fff; font-weight:600;">
                            <input type="radio" name="add_item_payment_method" value="online" style="accent-color:#0f6dff;">
                            <span class="material-symbols-rounded" style="color:#0f6dff; font-size:20px;">credit_card</span>
                            Online
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; padding:10px 18px; border-radius:12px; border:2px solid var(--border); background:#fff; font-weight:600;">
                            <input type="radio" name="add_item_payment_method" value="credit" style="accent-color:#f59e0b;">
                            <span class="material-symbols-rounded" style="color:#f59e0b; font-size:20px;">account_balance</span>
                            Credit (Due)
                        </label>
                    </div>
                    <div id="add-payment-credit-note" style="display:none; margin-top:14px; padding:16px; border-radius:12px; background:#fff; border:1px solid #e2e8f0; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                        <div style="color:#92400e; font-size:13px; font-weight:600; margin-bottom:14px; padding:10px; background:#fef3c7; border-radius:8px; border:1px solid #fcd34d;">
                            <span class="material-symbols-rounded" style="font-size:16px; vertical-align:middle;">info</span>
                            Remaining balance goes to <strong>Credit Dues</strong>.
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Payment (Optional)</label>
                                <div style="position:relative;">
                                    <span class="material-symbols-rounded" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:20px;">payments</span>
                                    <input type="number" step="0.01" min="0" name="add_item_partial_payment" class="form-input" placeholder="0" style="padding-left:40px;">
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Payment Method</label>
                                <div style="position:relative;">
                                    <span class="material-symbols-rounded" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:20px;">edit_document</span>
                                    <select name="add_item_partial_payment_method" class="form-select" style="padding-left:40px;">
                                        <option value="cash">Cash</option>
                                        <option value="online">Online</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Next Due Date</label>
                                <div style="position:relative;">
                                    <span class="material-symbols-rounded" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:20px;">calendar_month</span>
                                    <input type="datetime-local" name="add_item_next_due_date" class="form-input" style="padding-left:40px;">
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Private Message</label>
                                <div style="position:relative;">
                                    <span class="material-symbols-rounded" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:20px;">description</span>
                                    <input type="text" name="add_item_private_message" class="form-input" placeholder="Private notes for remaining due..." style="padding-left:40px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sale-add-tools">
                    <div class="sale-add-search-wrap">
                        <span class="material-symbols-rounded">search</span>
                        <input class="form-input" type="text" id="sale-add-product-search" placeholder="Search products by name or model number">
                        <div class="sale-add-suggestions" id="sale-add-product-suggestions"></div>
                    </div>
                    <div class="sale-add-hint" id="sale-add-search-hint">Type product name/model, then click suggestion to auto add.</div>
                </div>
                <div class="sale-add-form-card">
                    <div class="sale-add-row-head">
                        <span>Product</span>
                        <span>Model / Name</span>
                        <span>Qty</span>
                        <span>Price</span>
                        <span>Discount</span>
                        <span>Remove</span>
                    </div>
                    <div id="sale-add-item-rows" class="sale-add-item-rows"></div>
                    <div class="sale-add-empty" id="sale-add-empty">Click <strong>Add Row</strong> to add a new item in this invoice.</div>
                </div>
            </div>

            <div class="sale-edit-actions">
                <button class="btn btn-primary" type="submit">Update Sale</button>
                <button class="btn btn-secondary" type="submit" name="print_after_update" value="1">
                    <span class="material-symbols-rounded">print</span>Update & Print
                </button>
            </div>
        </form>
    </section>

    <section class="card table-wrap">
        <h3 style="margin-bottom:18px;">Items</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Unit</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th>Remove</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr class="sale-item-row" data-item-id="<?php echo (int) $item['id']; ?>" data-base-qty="<?php echo Security::escape(App::formatQuantity($item['quantity'])); ?>" data-price="<?php echo (float) $item['price']; ?>" data-discount="<?php echo (float) $item['discount']; ?>" data-total="<?php echo (float) $item['total']; ?>">
                        <td>
                            <div class="item-with-image">
                                <?php if ($showProductImages): ?>
                                    <span class="item-thumb">
                                        <?php if (!empty($item['product_image_url'])): ?>
                                            <img src="<?php echo Security::escape($item['product_image_url']); ?>" alt="<?php echo Security::escape($item['product_name']); ?>">
                                        <?php else: ?>
                                            <span class="material-symbols-rounded">inventory_2</span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                <strong><?php echo Security::escape($item['product_name']); ?></strong>
                            </div>
                            <div class="sale-remove-note" data-remove-note style="display:none;"></div>
                        </td>
                        <td><?php echo Security::escape($item['product_unit_label'] ?? App::formatProductUnit(1, 'pcs')); ?></td>
                        <td data-qty-cell><?php echo Security::escape(App::formatQuantity($item['quantity'])); ?></td>
                        <td data-price-cell><?php echo App::money($item['price']); ?></td>
                        <td data-total-cell><?php echo App::money($item['total']); ?></td>
                        <td>
                            <button
                                class="remove-item-toggle"
                                type="button"
                                title="Remove item"
                                data-sale-remove-item
                                data-item-id="<?php echo (int) $item['id']; ?>"
                                data-quantity="<?php echo Security::escape(App::formatQuantity($item['quantity'])); ?>"
                                data-product-name="<?php echo Security::escape($item['product_name']); ?>">
                                <span class="material-symbols-rounded">delete</span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top:18px; display:flex; justify-content:space-between; font-weight:800;">
            <span>Updated Total</span>
            <span id="sale-preview-total"><?php echo App::money($invoice['total']); ?></span>
        </div>
    </section>
</div>

<?php ob_start(); ?>
<script>
    (() => {
        const products = <?php echo json_encode($products ?? []); ?>;
        const esc = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
        const hiddenContainer = document.getElementById('sale-remove-hidden-inputs');
        const removalMap = new Map();
        const invoiceDiscountInput = document.getElementById('invoice-discount-input');
        const previewTotalNode = document.getElementById('sale-preview-total');
        const addRowsContainer = document.getElementById('sale-add-item-rows');
        const addRowButton = document.getElementById('add-sale-item-row-btn');
        const addSearchInput = document.getElementById('sale-add-product-search');
        const addSearchHint = document.getElementById('sale-add-search-hint');
        const addSearchSuggestions = document.getElementById('sale-add-product-suggestions');
        const addEmptyState = document.getElementById('sale-add-empty');
        const statusSelect = document.querySelector('select[name="status"]');
        const invoicePaymentMethodSelect = document.getElementById('invoice-payment-method');
        const invoiceCreditFields = document.getElementById('invoice-credit-fields');
        const invoiceCreditPaidInput = document.getElementById('invoice-credit-paid');
        const invoiceCreditRemainingNode = document.getElementById('invoice-credit-remaining');
        const invoiceLiveBadge = document.getElementById('invoice-live-badge');
        let activeSuggestions = [];

        const getFilteredProducts = (query, limit = null) => {
            const q = String(query || '').trim().toLowerCase();
            if (!q) return products || [];
            const filtered = (products || []).filter((product) => {
                const name = String(product.name || '').toLowerCase();
                const model = String(product.model_number || '').toLowerCase();
                return name.includes(q) || model.includes(q);
            });
            return typeof limit === 'number' ? filtered.slice(0, limit) : filtered;
        };

        const buildProductOptionsHtml = (selectedValue = '') => {
            const list = products || [];
            const selectedId = String(selectedValue || '');
            let html = '<option value="">Select Product (Optional)</option>';
            html += list.map((product) => {
                const id = String(Number(product.id || 0));
                const selected = selectedId === id ? 'selected' : '';
                return `
                    <option
                        value="${id}"
                        ${selected}
                        data-name="${esc(product.name || '')}"
                        data-model="${esc(product.model_number || '')}"
                        data-price="${Number(product.price || 0)}">
                        ${esc(product.name || '')}${product.model_number ? ` (${esc(product.model_number)})` : ''}
                    </option>
                `;
            }).join('');
            return html;
        };

        const renderSearchSuggestions = (query) => {
            if (!addSearchSuggestions) return;
            const q = String(query || '').trim();
            if (!q) {
                addSearchSuggestions.classList.remove('open');
                addSearchSuggestions.innerHTML = '';
                activeSuggestions = [];
                if (addSearchHint) {
                    addSearchHint.textContent = 'Type product name/model, then click suggestion to auto add.';
                }
                return;
            }

            const matched = getFilteredProducts(q, 12);
            activeSuggestions = matched;
            if (!matched.length) {
                addSearchSuggestions.innerHTML = `<div class="sale-add-suggestion-item" style="cursor:default;"><div class="sale-add-suggestion-meta">No products found for "${esc(q)}".</div></div>`;
                addSearchSuggestions.classList.add('open');
                if (addSearchHint) addSearchHint.textContent = 'No matches found.';
                return;
            }

            addSearchSuggestions.innerHTML = matched.map((product) => `
                <button type="button" class="sale-add-suggestion-item" data-suggest-product-id="${Number(product.id || 0)}">
                    <div>
                        <div class="sale-add-suggestion-name">${esc(product.name || '')}</div>
                        <div class="sale-add-suggestion-meta">${esc(product.model_number || 'No model')}</div>
                    </div>
                    <div class="sale-add-suggestion-meta">${window.appFormatMoney(Number(product.price || 0))}</div>
                </button>
            `).join('');
            addSearchSuggestions.classList.add('open');

            if (addSearchHint) {
                const totalMatched = getFilteredProducts(q).length;
                addSearchHint.textContent = `${totalMatched} product(s) matched "${q}".`;
            }
        };

        const renderAddItemRow = (prefillProduct = null) => {
            if (!addRowsContainer) return;
            const row = document.createElement('div');
            row.className = 'sale-add-item-row';
            const selectedId = prefillProduct ? String(Number(prefillProduct.id || 0)) : '';
            const nameValue = prefillProduct ? esc(prefillProduct.name || '') : '';
            const modelValue = prefillProduct ? esc(prefillProduct.model_number || '') : '';
            const priceValue = prefillProduct ? Number(prefillProduct.price || 0) : '';
            row.innerHTML = `
                <div class="sale-add-field">
                    <label class="sale-add-mobile-label">Product</label>
                    <select class="form-select" name="add_item_product_id[]">
                        ${buildProductOptionsHtml(selectedId)}
                    </select>
                </div>
                <div class="sale-add-field">
                    <label class="sale-add-mobile-label">Product / Custom Name</label>
                    <input class="form-input" type="text" name="add_item_name[]" placeholder="Product / custom name" value="${nameValue}">
                </div>
                <div class="sale-add-field">
                    <label class="sale-add-mobile-label">Quantity</label>
                    <input class="form-input" type="number" name="add_item_quantity[]" min="1" step="1" value="1" placeholder="Qty">
                </div>
                <div class="sale-add-field">
                    <label class="sale-add-mobile-label">Unit Price</label>
                    <input class="form-input" type="number" name="add_item_price[]" min="0" step="0.01" placeholder="Unit Price" value="${priceValue}">
                </div>
                <div class="sale-add-field">
                    <label class="sale-add-mobile-label">Discount</label>
                    <input class="form-input" type="number" name="add_item_discount[]" min="0" step="0.01" value="0" placeholder="Disc">
                </div>
                <div class="sale-add-field">
                    <label class="sale-add-mobile-label">Remove</label>
                    <button class="remove-item-toggle" type="button" data-remove-add-row title="Remove row">
                        <span class="material-symbols-rounded">delete</span>
                    </button>
                </div>
                <input type="hidden" name="add_item_model[]" value="${modelValue}">
            `;
            addRowsContainer.appendChild(row);
            syncAddRowsState();
        };

        const addProductFromSuggestion = (productId) => {
            const product = (products || []).find((item) => Number(item.id || 0) === Number(productId || 0));
            if (!product) return;
            renderAddItemRow(product);
            if (addSearchInput) addSearchInput.value = '';
            renderSearchSuggestions('');
        };

        const syncAddRowsState = () => {
            if (!addRowsContainer || !addEmptyState) return;
            addEmptyState.style.display = addRowsContainer.children.length ? 'none' : 'block';
        };

        const syncRemovalInputs = () => {
            hiddenContainer.innerHTML = '';
            removalMap.forEach((qty, itemId) => {
                const removeFlag = document.createElement('input');
                removeFlag.type = 'hidden';
                removeFlag.name = 'remove_items[]';
                removeFlag.value = itemId;
                hiddenContainer.appendChild(removeFlag);

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `remove_item_quantities[${itemId}]`;
                input.value = qty;
                hiddenContainer.appendChild(input);
            });
        };

        const updateInvoiceCreditSection = (totalAmount) => {
            const status = statusSelect?.value || 'paid';
            const paymentMethod = invoicePaymentMethodSelect?.value || 'cash';
            const isCredit = paymentMethod === 'credit';

            if (invoiceCreditFields) {
                invoiceCreditFields.style.display = isCredit ? 'block' : 'none';
            }

            if (!isCredit) {
                if (invoiceCreditRemainingNode) {
                    invoiceCreditRemainingNode.textContent = '';
                }
                return;
            }

            let paid = Math.max(0, parseFloat(invoiceCreditPaidInput?.value || '0'));
            if (status === 'paid') {
                paid = totalAmount;
            } else if (status === 'draft') {
                paid = 0;
            }
            paid = Math.min(totalAmount, paid);
            const remaining = Math.max(0, totalAmount - paid);

            if (invoiceCreditPaidInput) {
                invoiceCreditPaidInput.value = paid.toFixed(2);
                invoiceCreditPaidInput.readOnly = status !== 'unpaid';
            }
            if (invoiceCreditRemainingNode) {
                invoiceCreditRemainingNode.textContent = `Remaining Balance: ${window.appFormatMoney(remaining)}`;
            }

            if (invoiceLiveBadge) {
                const method = paymentMethod.toUpperCase();
                let badgeType = 'PAID';
                let color = '#15803d';
                let bg = '#f0fdf4';
                let border = '#bbf7d0';

                if (status === 'draft') {
                    badgeType = 'DRAFT';
                    color = '#475569';
                    bg = '#f8fafc';
                    border = '#cbd5e1';
                } else if (remaining > 0) {
                    badgeType = 'UNPAID';
                    color = '#b45309';
                    bg = '#fffbeb';
                    border = '#fde68a';
                }

                invoiceLiveBadge.style.color = color;
                invoiceLiveBadge.style.background = bg;
                invoiceLiveBadge.style.borderColor = border;
                invoiceLiveBadge.textContent = `${badgeType} | Payment: ${method} | Remaining: ${window.appFormatMoney(remaining)}`;
            }
        };

        const updatePreview = () => {
            let subtotal = 0;
            document.querySelectorAll('.sale-item-row').forEach((row) => {
                const itemId = parseInt(row.dataset.itemId, 10);
                const baseQty = parseInt(row.dataset.baseQty, 10) || 0;
                const price = parseFloat(row.dataset.price || '0');
                const discount = parseFloat(row.dataset.discount || '0');
                const removeQty = Math.max(0, Math.min(baseQty, parseInt(removalMap.get(itemId) || '0', 10) || 0));
                const remainingQty = Math.max(0, baseQty - removeQty);
                const unitDiscount = baseQty > 0 ? discount / baseQty : 0;
                const newDiscount = unitDiscount * remainingQty;
                const newTotal = Math.max(0, (price * remainingQty) - newDiscount);

                row.classList.toggle('pending-remove', remainingQty === 0 && removeQty > 0);
                row.querySelector('[data-qty-cell]').textContent = remainingQty;
                row.querySelector('[data-total-cell]').textContent = window.appFormatMoney(newTotal);

                const note = row.querySelector('[data-remove-note]');
                if (removeQty > 0) {
                    note.style.display = 'block';
                    note.textContent = remainingQty === 0 ? 'This item will be removed on save.' : `${removeQty} unit(s) will be removed on save.`;
                } else {
                    note.style.display = 'none';
                    note.textContent = '';
                }

                subtotal += newTotal;
            });

            const invoiceDiscount = Math.max(0, parseFloat(invoiceDiscountInput?.value || '0'));
            const total = Math.max(0, subtotal - invoiceDiscount);
            if (previewTotalNode) {
                previewTotalNode.textContent = window.appFormatMoney(total);
            }
            updateInvoiceCreditSection(total);
        };

        window.promptSaleItemRemoval = (itemId, quantity, productName) => {
            if (quantity <= 1) {
                window.appModal.open({
                    title: 'Remove Item',
                    body: `
                        <div style="padding:6px 0 4px;">
                            <div style="font-weight:700; margin-bottom:8px;">Remove ${productName} from this sale?</div>
                            <div class="page-subtitle">Receipt, item quantities and invoice total will update when you save the sale.</div>
                        </div>
                    `,
                    actions: `
                        <button class="btn btn-secondary" type="button" data-modal-close>Cancel</button>
                        <button class="btn btn-danger" type="button" id="confirm-sale-item-remove">Remove</button>
                    `
                });
                document.getElementById('confirm-sale-item-remove').onclick = () => {
                    removalMap.set(itemId, quantity);
                    syncRemovalInputs();
                    updatePreview();
                    window.appModal.close();
                };
                return;
            }

            window.appModal.open({
                title: 'Remove Quantity',
                body: `
                    <div class="form-group">
                        <label class="form-label">How many units of ${productName} should be removed?</label>
                        <input id="sale-remove-qty-input" class="form-input" type="number" min="1" max="${quantity}" value="1">
                    </div>
                    <div class="page-subtitle">Available in invoice: ${quantity}</div>
                `,
                actions: `
                    <button class="btn btn-secondary" type="button" data-modal-close>Cancel</button>
                    <button class="btn btn-danger" type="button" id="confirm-sale-item-remove-qty">Apply</button>
                `
            });
            document.getElementById('confirm-sale-item-remove-qty').onclick = () => {
                const value = Math.max(1, Math.min(quantity, parseInt(document.getElementById('sale-remove-qty-input').value, 10) || 1));
                removalMap.set(itemId, value);
                syncRemovalInputs();
                updatePreview();
                window.appModal.close();
            };
        };

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-sale-remove-item]');
            if (!trigger) {
                const removeAddRowBtn = event.target.closest('[data-remove-add-row]');
                if (removeAddRowBtn) {
                    removeAddRowBtn.closest('.sale-add-item-row')?.remove();
                    syncAddRowsState();
                }
                return;
            }

            const itemId = parseInt(trigger.dataset.itemId || '0', 10);
            const quantity = parseInt(trigger.dataset.quantity || '0', 10);
            const productName = trigger.dataset.productName || 'this item';

            if (!itemId || !quantity) {
                return;
            }

            window.promptSaleItemRemoval(itemId, quantity, productName);
        });

        addRowsContainer?.addEventListener('change', (event) => {
            const select = event.target.closest('select[name="add_item_product_id[]"]');
            if (!select) return;
            const row = select.closest('.sale-add-item-row');
            if (!row) return;
            const selected = select.options[select.selectedIndex];
            const nameInput = row.querySelector('input[name="add_item_name[]"]');
            const modelInput = row.querySelector('input[name="add_item_model[]"]');
            const priceInput = row.querySelector('input[name="add_item_price[]"]');
            if (!selected || !selected.value) return;
            if (nameInput && !nameInput.value.trim()) nameInput.value = selected.dataset.name || '';
            if (modelInput && !modelInput.value.trim()) modelInput.value = selected.dataset.model || '';
            if (priceInput && (!priceInput.value || Number(priceInput.value) <= 0)) priceInput.value = selected.dataset.price || '';
        });

        addSearchSuggestions?.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-suggest-product-id]');
            if (!trigger) return;
            addProductFromSuggestion(Number(trigger.dataset.suggestProductId || 0));
        });

        addRowButton?.addEventListener('click', () => renderAddItemRow());
        addSearchInput?.addEventListener('input', () => {
            renderSearchSuggestions(addSearchInput.value || '');
        });
        addSearchInput?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            if (!activeSuggestions.length) return;
            event.preventDefault();
            addProductFromSuggestion(Number(activeSuggestions[0].id || 0));
        });
        document.addEventListener('click', (event) => {
            const insideSearch = addSearchInput?.contains(event.target) || addSearchSuggestions?.contains(event.target);
            if (!insideSearch && addSearchSuggestions) {
                addSearchSuggestions.classList.remove('open');
            }
        });

        invoiceDiscountInput?.addEventListener('input', updatePreview);
        statusSelect?.addEventListener('change', updatePreview);
        invoicePaymentMethodSelect?.addEventListener('change', updatePreview);
        invoiceCreditPaidInput?.addEventListener('input', updatePreview);
        updatePreview();
        renderAddItemRow();
        renderSearchSuggestions('');
        syncAddRowsState();

        // Payment method toggle styling
        const paymentOptions = document.querySelectorAll('#add-payment-method-options label');
        const creditNote = document.getElementById('add-payment-credit-note');
        const colors = { cash: '#22c55e', online: '#0f6dff', credit: '#f59e0b' };
        const syncAddPaymentMethodUI = () => {
            const selectedAddPayment = document.querySelector('[name="add_item_payment_method"]:checked');
            paymentOptions.forEach((lbl) => {
                const r = lbl.querySelector('input[name="add_item_payment_method"]');
                if (!r) return;
                lbl.style.borderColor = r.checked ? colors[r.value] : 'var(--border)';
                lbl.style.background = r.checked
                    ? (r.value === 'cash' ? '#f0fdf4' : r.value === 'online' ? '#eff6ff' : '#fffbeb')
                    : '#fff';
            });
            if (creditNote) {
                creditNote.style.display = (selectedAddPayment && selectedAddPayment.value === 'credit') ? '' : 'none';
            }
        };
        window.syncAddPaymentMethodUI = syncAddPaymentMethodUI;

        document.querySelectorAll('[name="add_item_payment_method"]').forEach((radio) => {
            radio.addEventListener('change', syncAddPaymentMethodUI);
            radio.addEventListener('click', syncAddPaymentMethodUI);
        });
        document.addEventListener('change', (event) => {
            if (event.target && event.target.matches('[name="add_item_payment_method"]')) {
                syncAddPaymentMethodUI();
            }
        });
        document.getElementById('add-payment-method-options')?.addEventListener('click', () => {
            setTimeout(syncAddPaymentMethodUI, 0);
        });
        document.getElementById('add-payment-method-options')?.addEventListener('touchend', () => {
            setTimeout(syncAddPaymentMethodUI, 0);
        });
        syncAddPaymentMethodUI();
    })();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>

