<?php ob_start(); ?>

<div class="page-header">
    <div>
        <h1>Convert Quotation to Sale Invoice</h1>
        <p class="page-subtitle">Finalize customer agreement, choose payment settlement, and deduct inventory stock automatically.</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/view/<?= (int)$quotation['id'] ?>" class="btn btn-secondary" data-no-ajax>
            <span class="material-symbols-rounded">arrow_back</span>Back to Quotation
        </a>
    </div>
</div>

<!-- Quotation Reference Hero Banner -->
<div class="card" style="margin-bottom: 22px; padding: 20px 24px; background: linear-gradient(135deg, rgba(15, 109, 255, 0.06), rgba(5, 150, 105, 0.08)); border-color: rgba(15, 109, 255, 0.2);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 50px; height: 50px; border-radius: 14px; background: var(--primary); color: #fff; display: grid; place-items: center;">
                <span class="material-symbols-rounded" style="font-size: 28px;">request_quote</span>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Quotation Reference</div>
                <div style="font-size: 20px; font-weight: 800; color: var(--text-main);"><?= htmlspecialchars($quotation['quotation_number']) ?></div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    Customer: <strong style="color: var(--text-main);"><?= htmlspecialchars($quotation['customer_name']) ?></strong>
                    <?= !empty($quotation['customer_phone']) ? ' (' . htmlspecialchars($quotation['customer_phone']) . ')' : '' ?>
                </div>
            </div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Quotation Total Amount</div>
            <div style="font-size: 26px; font-weight: 900; color: var(--success, #059669);"><?= App::money((float)$quotation['total']) ?></div>
            <div style="font-size: 11px; color: var(--text-muted);">Valid Until <?= date('M d, Y', strtotime($quotation['valid_until'])) ?></div>
        </div>
    </div>
</div>

<form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/convert/<?= (int)$quotation['id'] ?>" onsubmit="submitConvertForm(event, this)" data-no-ajax>
    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

    <div style="display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(340px, 0.75fr); gap: 24px; align-items: start;">
        
        <!-- Left Column: Line Items & Stock Availability -->
        <div>
            <!-- Line Items Card -->
            <div class="card" style="margin-bottom: 22px; padding: 24px;">
                <div style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-rounded" style="color: var(--primary);">inventory_2</span>
                        Line Items & Stock Verification
                    </div>
                    <span class="badge badge-primary"><?= count($items) ?> Items</span>
                </div>

                <div class="table-responsive">
                    <table class="table" style="min-width: 620px; width: 100%;">
                        <thead>
                            <tr>
                                <th style="min-width: 170px;">Item Description</th>
                                <th style="min-width: 80px; text-align: center; white-space: nowrap;">Qty</th>
                                <th style="min-width: 110px; text-align: right; white-space: nowrap;">Unit Price</th>
                                <th style="min-width: 110px; text-align: right; white-space: nowrap;">Total</th>
                                <th style="min-width: 140px; text-align: center; white-space: nowrap;">Stock Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                                <?php 
                                    $qtyNeeded = (float)$it['quantity'];
                                    $hasProduct = !empty($it['product_id']);
                                    $currentStock = (float)($it['current_stock'] ?? 0);
                                    $isStockLow = $hasProduct && ($currentStock < $qtyNeeded);
                                ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 800; color: var(--text-main);"><?= htmlspecialchars($it['product_name']) ?></div>
                                        <?php if (!empty($it['model_number'])): ?>
                                            <div style="font-size: 11px; color: var(--text-muted);">Model: <?= htmlspecialchars($it['model_number']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center; font-weight: 800; white-space: nowrap;">
                                        <?= (float)$it['quantity'] ?> <?= htmlspecialchars($it['unit_type'] ?? 'pcs') ?>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; white-space: nowrap;">
                                        <?= App::money((float)$it['price']) ?>
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: var(--text-main); white-space: nowrap;">
                                        <?= App::money((float)$it['total']) ?>
                                    </td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <?php if (!$hasProduct): ?>
                                            <span class="badge" style="background: rgba(100, 116, 139, 0.1); color: #64748b; white-space: nowrap;">Service / Custom</span>
                                        <?php elseif ($isStockLow): ?>
                                            <span class="badge badge-danger" title="Available: <?= $currentStock ?>" style="white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;">
                                                <span class="material-symbols-rounded" style="font-size: 14px;">warning</span> Low: <?= $currentStock ?> left
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-success" style="white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;">
                                                <span class="material-symbols-rounded" style="font-size: 14px;">check</span> In Stock (<?= $currentStock ?>)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Customer & Delivery Snapshot -->
            <div class="card" style="padding: 24px;">
                <div style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="color: var(--primary);">person_pin</span>
                    Customer Snapshot & Quotation Terms
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; font-size: 13px;">
                    <div style="background: var(--bg-soft); padding: 12px 16px; border-radius: 12px;">
                        <span style="color: var(--text-muted); display: block; font-size: 11px; font-weight: 700;">CUSTOMER NAME</span>
                        <strong style="color: var(--text-main); font-size: 14px;"><?= htmlspecialchars($quotation['customer_name']) ?></strong>
                    </div>
                    <div style="background: var(--bg-soft); padding: 12px 16px; border-radius: 12px;">
                        <span style="color: var(--text-muted); display: block; font-size: 11px; font-weight: 700;">PHONE NUMBER</span>
                        <strong style="color: var(--text-main); font-size: 14px;"><?= !empty($quotation['customer_phone']) ? htmlspecialchars($quotation['customer_phone']) : 'Not specified' ?></strong>
                    </div>
                    <div style="background: var(--bg-soft); padding: 12px 16px; border-radius: 12px; grid-column: 1 / -1;">
                        <span style="color: var(--text-muted); display: block; font-size: 11px; font-weight: 700;">DELIVERY ADDRESS</span>
                        <strong style="color: var(--text-main); font-size: 13px;"><?= !empty($quotation['customer_address']) ? htmlspecialchars($quotation['customer_address']) : 'Standard counter pickup' ?></strong>
                    </div>
                </div>

                <?php if (!empty($quotation['notes'])): ?>
                    <div style="margin-top: 14px; padding: 12px 16px; background: rgba(245, 158, 11, 0.06); border: 1px dashed rgba(245, 158, 11, 0.3); border-radius: 12px;">
                        <span style="color: var(--warning); font-weight: 700; font-size: 12px; display: block; margin-bottom: 2px;">Terms / Notes on Quote:</span>
                        <div style="font-size: 12px; color: var(--text-main); white-space: pre-line;"><?= htmlspecialchars($quotation['notes']) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Payment Settlement Studio -->
        <div>
            <div class="card" style="background: var(--bg-sidebar); color: #ffffff; padding: 24px; border: none; box-shadow: var(--shadow-md);">
                <div style="font-size: 18px; font-weight: 800; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid rgba(255,255,255,0.12); padding-bottom: 14px;">
                    <span class="material-symbols-rounded" style="color: var(--primary);">payments</span>
                    Payment Settlement
                </div>

                <!-- Generated Invoice Custom ID -->
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; font-weight: 700; color: var(--text-light); margin-bottom: 4px; display: block;">Official Sale Invoice #</label>
                    <input type="text" value="<?= htmlspecialchars($nextInvoiceNumber) ?>" class="form-input" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); color: #38bdf8; font-weight: 800; font-size: 15px;" readonly>
                </div>

                <!-- Payment Method -->
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; font-weight: 700; color: var(--text-light); margin-bottom: 6px; display: block;">Payment Method *</label>
                    <select name="payment_method" id="payment_method" class="form-select" onchange="handlePaymentMethodChange(this.value)" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); color: #ffffff; font-weight: 800;">
                        <option value="cash" style="background: #1e293b; color: #ffffff;" selected>Cash Payment</option>
                        <option value="online" style="background: #1e293b; color: #ffffff;">Bank / Online Transfer</option>
                        <option value="credit" style="background: #1e293b; color: #ffffff;">Customer Credit / Khata</option>
                        <option value="cheque" style="background: #1e293b; color: #ffffff;">Cheque</option>
                    </select>
                </div>

                <!-- Amount Received Input -->
                <div style="margin-bottom: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <label style="font-size: 12px; font-weight: 700; color: var(--text-light);">Amount Received (Rs) *</label>
                        <button type="button" class="btn btn-sm btn-outline" onclick="setFullAmount()" style="padding: 2px 8px; font-size: 11px; color: #38bdf8; border-color: rgba(56, 189, 248, 0.4);">
                            Full Amount
                        </button>
                    </div>
                    <input type="number" step="0.01" min="0" name="paid_amount" id="paid_amount" value="<?= (float)$quotation['total'] ?>" class="form-input" placeholder="0.00" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); color: #ffffff; font-weight: 900; font-size: 18px;" oninput="calculateBalanceDue()">
                </div>

                <!-- Credit Promised Due Date (conditional) -->
                <div id="credit_due_box" style="margin-bottom: 16px; display: none; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; padding: 12px;">
                    <label style="font-size: 12px; font-weight: 700; color: #fbbf24; margin-bottom: 4px; display: block;">Promised Due Date</label>
                    <input type="date" name="credit_due_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" class="form-input" style="background: rgba(255,255,255,0.1); border-color: rgba(245, 158, 11, 0.4); color: #ffffff; font-weight: 700;">
                </div>

                <!-- Balance Breakdown -->
                <div style="background: rgba(0,0,0,0.25); border-radius: 14px; padding: 16px; margin-top: 18px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; color: var(--text-light);">
                        <span>Subtotal</span>
                        <span><?= App::money((float)$quotation['subtotal']) ?></span>
                    </div>
                    <?php if ((float)$quotation['discount'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; color: #f87171;">
                            <span>Discount</span>
                            <span>- <?= App::money((float)$quotation['discount']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ((float)$quotation['tax_amount'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; color: var(--text-light);">
                            <span>Tax (<?= (float)$quotation['tax_rate'] ?>%)</span>
                            <span>+ <?= App::money((float)$quotation['tax_amount']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed rgba(255,255,255,0.15); font-size: 15px; font-weight: 800; color: #ffffff;">
                        <span>Invoice Total</span>
                        <span style="color: #38bdf8; font-size: 18px;"><?= App::money((float)$quotation['total']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.15); font-size: 14px; font-weight: 800;">
                        <span id="balance_label" style="color: var(--text-light);">Balance Due:</span>
                        <span id="balance_value" style="color: #ffffff; font-size: 16px;">Rs 0.00</span>
                    </div>
                </div>

                <!-- Submit Action -->
                <button type="submit" id="submitConvertBtn" class="btn btn-success" style="width: 100%; padding: 15px; font-size: 15px; font-weight: 900; justify-content: center; background: #059669; border-color: #059669; box-shadow: 0 4px 18px rgba(5, 150, 105, 0.4);">
                    <span class="material-symbols-rounded">check_circle</span> Complete Sale & Create Bill
                </button>
            </div>
        </div>

    </div>
</form>

<?php ob_start(); ?>
<script>
    window.grandTotal = <?= (float)$quotation['total'] ?>;
    window.currencySym = <?= json_encode(App::currencySymbol()) ?>;

    window.handlePaymentMethodChange = function(val) {
        const creditBox = document.getElementById('credit_due_box');
        if (creditBox) {
            if (val === 'credit') {
                creditBox.style.display = 'block';
                const paidInput = document.getElementById('paid_amount');
                if (paidInput) paidInput.value = '0';
            } else {
                creditBox.style.display = 'none';
                const paidInput = document.getElementById('paid_amount');
                if (paidInput && parseFloat(paidInput.value) === 0) {
                    paidInput.value = window.grandTotal;
                }
            }
        }
        window.calculateBalanceDue();
    };

    window.setFullAmount = function() {
        const paidInput = document.getElementById('paid_amount');
        if (paidInput) paidInput.value = window.grandTotal;
        window.calculateBalanceDue();
    };

    window.calculateBalanceDue = function() {
        const paidInput = document.getElementById('paid_amount');
        const paid = Math.max(0, parseFloat(paidInput ? paidInput.value : 0) || 0);
        const diff = (window.grandTotal || 0) - paid;
        const balanceVal = document.getElementById('balance_value');
        const balanceLbl = document.getElementById('balance_label');

        if (!balanceVal || !balanceLbl) return;

        if (diff > 0) {
            balanceLbl.textContent = 'Balance Due (Unpaid):';
            balanceLbl.style.color = '#fbbf24';
            balanceVal.textContent = (window.currencySym || 'Rs') + ' ' + diff.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            balanceVal.style.color = '#fbbf24';
        } else if (diff < 0) {
            balanceLbl.textContent = 'Change to Return:';
            balanceLbl.style.color = '#34d399';
            balanceVal.textContent = (window.currencySym || 'Rs') + ' ' + Math.abs(diff).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            balanceVal.style.color = '#34d399';
        } else {
            balanceLbl.textContent = 'Status:';
            balanceLbl.style.color = '#38bdf8';
            balanceVal.textContent = 'Fully Paid (Rs 0.00 Due)';
            balanceVal.style.color = '#38bdf8';
        }
    };

    window.submitConvertForm = async function(e, form) {
        e.preventDefault();
        const paidInput = document.getElementById('paid_amount');
        const methodSelect = document.getElementById('payment_method');
        const paid = Math.max(0, parseFloat(paidInput ? paidInput.value : 0) || 0);
        const method = methodSelect ? methodSelect.value : 'cash';

        if (method !== 'credit' && paid < (window.grandTotal || 0)) {
            const sym = window.currencySym || 'Rs';
            const ok = await window.appConfirm(
                `The amount paid (${sym} ${paid.toLocaleString()}) is less than the invoice total (${sym} ${(window.grandTotal || 0).toLocaleString()}). Do you want to record the remaining ${sym} ${((window.grandTotal || 0) - paid).toLocaleString()} as Customer Khata/Credit?`,
                'Partial Payment Confirmation',
                { confirmText: 'Yes, Record as Khata', icon: 'credit_card' }
            );
            if (!ok) return;
        }

        const btn = document.getElementById('submitConvertBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-rounded" style="animation:spin 1s infinite linear;">sync</span> Processing Sale & Updating Stock...';
        }
        
        form.removeAttribute('onsubmit');
        form.submit();
    };

    window.calculateBalanceDue();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
