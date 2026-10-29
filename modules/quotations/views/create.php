<?php ob_start(); ?>

<?php
$isEdit = !empty($isEdit);
$quoteId = (int)($quotation['id'] ?? 0);
$qNumber = $quotation['quotation_number'] ?? ($nextNumber ?? 'QT-0001');
$cName = $quotation['customer_name'] ?? '';
$cId = $quotation['customer_id'] ?? '';
$cPhone = $quotation['customer_phone'] ?? '';
$cAddress = $quotation['customer_address'] ?? '';
$qValidDays = !empty($quotation['valid_until']) ? max(1, (int)round((strtotime($quotation['valid_until']) - strtotime($quotation['quotation_date'] ?? date('Y-m-d'))) / 86400)) : 15;
$qDiscount = (float)($quotation['discount'] ?? 0);
$qTaxRate = (float)($quotation['tax_rate'] ?? ($taxSettings['rate'] ?? 0));
$qNotes = $quotation['notes'] ?? '';
$initialItems = !empty($items) ? $items : [];
?>

<div class="page-header">
    <div>
        <h1><?= $isEdit ? 'Edit Quotation ' . htmlspecialchars($qNumber) : 'Create New Quotation' ?></h1>
        <p class="page-subtitle"><?= $isEdit ? 'Update line items, pricing, or customer terms for this estimate.' : 'Guided 3-step studio to create professional price quotes with instant calculations & WhatsApp dispatch.' ?></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/index" class="btn btn-secondary">
            <span class="material-symbols-rounded">arrow_back</span>Back to List
        </a>
    </div>
</div>

<!-- Wizard Stepper Indicator -->
<div class="card" style="margin-bottom: 22px; padding: 14px 20px;">
    <div class="wizard-stepper">
        <div class="wizard-step active" id="step-indicator-1" onclick="goToQuoteStep(1)">
            <div class="step-badge" id="step-badge-1">1</div>
            <div class="step-info">
                <strong>Customer & Terms</strong>
                <small>Select customer & validity</small>
            </div>
        </div>
        <div class="step-connector"></div>
        <div class="wizard-step" id="step-indicator-2" onclick="goToQuoteStep(2)">
            <div class="step-badge" id="step-badge-2">2</div>
            <div class="step-info">
                <strong>Items & Pricing</strong>
                <small>Add products & discounts</small>
            </div>
        </div>
        <div class="step-connector"></div>
        <div class="wizard-step" id="step-indicator-3" onclick="goToQuoteStep(3)">
            <div class="step-badge" id="step-badge-3">3</div>
            <div class="step-info">
                <strong>Summary & Confirm</strong>
                <small>Taxes, notes & finalize</small>
            </div>
        </div>
    </div>
</div>

<form id="quotationForm" onsubmit="saveQuotation(event)">

    <!-- STEP 1: CUSTOMER & TERMS -->
    <div id="step-content-1" class="quote-step-panel">
        <div class="card" style="margin-bottom: 20px; padding: 24px;">
            <div style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
                <span class="material-symbols-rounded" style="color: var(--primary);">person</span> Step 1: Customer & Quotation Meta
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label">Quotation Number</label>
                    <input type="text" id="quotation_number" value="<?= htmlspecialchars($qNumber) ?>" class="form-input" style="font-weight: 800; color: var(--primary); background: var(--bg-soft);" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Quote Validity Period</label>
                    <select id="valid_days" class="form-select">
                        <option value="7" <?= $qValidDays == 7 ? 'selected' : '' ?>>7 Days (Fast Validity)</option>
                        <option value="15" <?= $qValidDays == 15 ? 'selected' : '' ?>>15 Days (Standard)</option>
                        <option value="30" <?= $qValidDays == 30 ? 'selected' : '' ?>>30 Days (Extended)</option>
                        <option value="60" <?= $qValidDays == 60 ? 'selected' : '' ?>>60 Days (Project Estimate)</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Customer Name *</label>
                    <input type="text" id="customer_name" value="<?= htmlspecialchars($cName) ?>" class="form-input" placeholder="e.g. Ali Ahmed" required list="customersList" oninput="matchCustomerDetails(this.value)">
                    <datalist id="customersList">
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= htmlspecialchars($c['name']) ?>" data-id="<?= (int)$c['id'] ?>" data-phone="<?= htmlspecialchars($c['phone'] ?? '') ?>" data-address="<?= htmlspecialchars($c['address'] ?? '') ?>">
                                <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['phone'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </datalist>
                    <input type="hidden" id="customer_id" value="<?= htmlspecialchars($cId) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Customer Phone (WhatsApp)</label>
                    <input type="text" id="customer_phone" value="<?= htmlspecialchars($cPhone) ?>" class="form-input" placeholder="0300-1234567">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Delivery / Office Address</label>
                    <input type="text" id="customer_address" value="<?= htmlspecialchars($cAddress) ?>" class="form-input" placeholder="e.g. Shop # 4, Main Market, Lahore">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
                <button type="button" class="btn btn-primary" onclick="goToQuoteStep(2)">
                    Next: Add Products & Pricing <span class="material-symbols-rounded">arrow_forward</span>
                </button>
            </div>
        </div>
    </div>

    <!-- STEP 2: PRODUCTS & PRICING -->
    <div id="step-content-2" class="quote-step-panel" style="display: none;">
        <div class="card" style="margin-bottom: 20px; padding: 24px;">
            <div style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="color: var(--primary);">inventory_2</span> Step 2: Line Items & Quotation Pricing
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addCustomRow()">
                    <span class="material-symbols-rounded">add</span> Add Custom Item
                </button>
            </div>

            <!-- Product Search Dropdown -->
            <div style="position: relative; margin-bottom: 18px;">
                <div class="field-icon-wrap">
                    <span class="material-symbols-rounded">search</span>
                    <input type="text" id="product_search_input" class="form-input" placeholder="Scan barcode, type model number, or product name to add..." oninput="filterProducts(this.value)" autocomplete="off">
                </div>
                <div id="search_results_dropdown" style="position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-surface); border: 1px solid var(--border); border-radius: 12px; max-height: 260px; overflow-y: auto; z-index: 100; box-shadow: var(--shadow-md); display: none;"></div>
            </div>

            <!-- Items Table -->
            <div class="table-responsive" style="margin-bottom: 16px;">
                <table class="table" id="quoteItemsTable" style="min-width: 680px; width: 100%;">
                    <thead>
                        <tr>
                            <th style="min-width: 180px;">Item / Description</th>
                            <th style="width: 110px; min-width: 100px; text-align: center;">Qty</th>
                            <th style="width: 140px; min-width: 120px; text-align: right;">Unit Price</th>
                            <th style="width: 120px; min-width: 110px; text-align: right;">Discount</th>
                            <th style="width: 140px; min-width: 120px; text-align: right;">Total</th>
                            <th style="width: 50px; text-align: center;"></th>
                        </tr>
                    </thead>
                    <tbody id="quoteItemsTbody">
                        <!-- Injected by JavaScript -->
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
                <button type="button" class="btn btn-secondary" onclick="goToQuoteStep(1)">
                    <span class="material-symbols-rounded">arrow_back</span> Back to Customer
                </button>
                <button type="button" class="btn btn-primary" onclick="goToQuoteStep(3)">
                    Next: Review Summary & Terms <span class="material-symbols-rounded">arrow_forward</span>
                </button>
            </div>
        </div>
    </div>

    <!-- STEP 3: SUMMARY & FINALIZE -->
    <div id="step-content-3" class="quote-step-panel" style="display: none;">
        <div style="display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr); gap: 20px; align-items: start;">
            
            <!-- Left Column: Terms Notes -->
            <div>
                <div class="card" style="padding: 24px; margin-bottom: 20px;">
                    <div style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-rounded" style="color: var(--primary);">description</span> Terms & Delivery Conditions
                    </div>
                    <textarea id="notes" class="form-textarea" rows="4" placeholder="e.g. 1. Delivery within 3 days. 2. Prices valid for 15 days. 3. 50% advance on order confirmation."><?= htmlspecialchars($qNotes) ?></textarea>
                </div>

                <div class="card" style="padding: 20px; background: var(--bg-soft); border-color: rgba(15, 109, 255, 0.2);">
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <span class="material-symbols-rounded" style="color: var(--primary); font-size: 28px;">verified_user</span>
                        <div>
                            <strong style="color: var(--text-main); font-size: 14px;">Instant Conversion Ready</strong>
                            <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Convert this estimate directly into an official sale bill with automatic inventory stock deduction.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Summary & Save Action -->
            <div class="card" style="background: var(--bg-sidebar); color: #ffffff; padding: 24px; border: none; box-shadow: var(--shadow-md);">
                <div style="font-size: 18px; font-weight: 800; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid rgba(255,255,255,0.12); padding-bottom: 14px;">
                    <span class="material-symbols-rounded" style="color: var(--primary);">calculate</span> Quotation Summary
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 14px; color: var(--text-light);">
                    <span>Items Subtotal</span>
                    <strong id="summary_subtotal" style="color: #ffffff; font-size: 16px;">Rs 0.00</strong>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 12px; font-weight: 700; color: var(--text-light); margin-bottom: 4px; display: block;">Overall Extra Discount (Rs)</label>
                    <input type="number" step="0.01" id="overall_discount" value="<?= $qDiscount > 0 ? $qDiscount : '' ?>" class="form-input" placeholder="0.00" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); color: #ffffff; font-weight: 700;" oninput="calculateTotals()">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="font-size: 12px; font-weight: 700; color: var(--text-light); margin-bottom: 4px; display: block;">Tax Rate (%)</label>
                    <input type="number" step="0.01" id="tax_rate" value="<?= $qTaxRate > 0 ? $qTaxRate : '' ?>" class="form-input" placeholder="0.00" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); color: #ffffff; font-weight: 700;" oninput="calculateTotals()">
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 18px; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.15);">
                    <span style="font-size: 16px; font-weight: 800; color: #ffffff;">Net Grand Total</span>
                    <strong id="summary_grand_total" style="font-size: 24px; font-weight: 900; color: #38bdf8;">Rs 0.00</strong>
                </div>

                <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 10px;">
                    <button type="submit" id="submitBtn" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 15px; font-weight: 800; justify-content: center;">
                        <span class="material-symbols-rounded">save</span> <?= $isEdit ? 'Update Quotation' : 'Save & Generate Quotation' ?>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="goToQuoteStep(2)" style="width: 100%; justify-content: center; background: rgba(255,255,255,0.1); color: #fff; border-color: rgba(255,255,255,0.2);">
                        <span class="material-symbols-rounded">arrow_back</span> Back to Items
                    </button>
                </div>
            </div>

        </div>
    </div>

</form>

<?php ob_start(); ?>
<script>
    window.allProducts = <?= json_encode($products ?? []) ?>;
    window.currencySym = <?= json_encode(App::currencySymbol()) ?>;
    window.isEditMode = <?= $isEdit ? 'true' : 'false' ?>;
    window.quoteId = <?= $quoteId ?>;
    window.quoteItems = <?= json_encode($initialItems) ?>.map(i => ({
        product_id: i.product_id ? parseInt(i.product_id) : null,
        name: i.product_name || i.name || '',
        model_number: i.model_number || '',
        quantity: parseFloat(i.quantity) || 1,
        unit_type: i.unit_type || 'pcs',
        price: parseFloat(i.unit_price || i.price) || 0,
        discount: parseFloat(i.discount) || 0
    }));

    window.currentQuoteStep = 1;

    window.goToQuoteStep = async function(stepNum) {
        if (stepNum === 2) {
            const custInput = document.getElementById('customer_name');
            const custName = custInput ? custInput.value.trim() : '';
            if (!custName) {
                await window.appAlert('Please enter or select Customer Name first before proceeding to items.', 'Customer Required', 'warning');
                if (custInput) custInput.focus();
                return;
            }
        }
        if (stepNum === 3) {
            if (!window.quoteItems || window.quoteItems.length === 0) {
                await window.appAlert('Please add at least one product item to the quotation before reviewing the summary.', 'No Items Added', 'warning');
                return;
            }
            window.calculateTotals();
        }

        window.currentQuoteStep = stepNum;

        for (let i = 1; i <= 3; i++) {
            const panel = document.getElementById('step-content-' + i);
            const indicator = document.getElementById('step-indicator-' + i);
            const badge = document.getElementById('step-badge-' + i);

            if (panel) {
                panel.style.display = (i === stepNum) ? 'block' : 'none';
            }

            if (indicator && badge) {
                if (i < stepNum) {
                    indicator.className = 'wizard-step completed';
                    badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:18px;">check</span>';
                } else if (i === stepNum) {
                    indicator.className = 'wizard-step active';
                    badge.textContent = i;
                } else {
                    indicator.className = 'wizard-step';
                    badge.textContent = i;
                }
            }
        }
    };

    window.filterProducts = function(query) {
        const dd = document.getElementById('search_results_dropdown');
        if (!dd) return;
        query = (query || '').trim().toLowerCase();
        if (!query) {
            dd.style.display = 'none';
            return;
        }

        const products = window.allProducts || [];
        const matches = products.filter(p => {
            return (p.name && p.name.toLowerCase().includes(query)) ||
                   (p.model_number && p.model_number.toLowerCase().includes(query)) ||
                   (p.barcode && p.barcode.toLowerCase().includes(query));
        }).slice(0, 15);

        if (matches.length === 0) {
            dd.innerHTML = '<div style="padding: 12px; color: var(--text-muted); font-size: 13px; text-align: center;">No matching products found. You can add a custom item.</div>';
            dd.style.display = 'block';
            return;
        }

        const sym = window.currencySym || 'Rs';
        dd.innerHTML = matches.map(p => `
            <div style="padding: 10px 14px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);" onclick="window.addProductToQuote(${p.id})">
                <div>
                    <strong style="color: var(--text-main); font-size: 14px;">${escapeHtml(p.name)}</strong>
                    <div style="font-size: 11px; color: var(--text-muted);">${p.model_number ? 'Model: ' + escapeHtml(p.model_number) + ' | ' : ''}Stock: ${p.stock} ${p.unit_type || 'pcs'}</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 800; color: var(--primary); font-size: 14px;">${sym} ${Number(p.price).toLocaleString()}</div>
                </div>
            </div>
        `).join('');
        dd.style.display = 'block';
    };

    window.addProductToQuote = function(pId) {
        const products = window.allProducts || [];
        const prod = products.find(p => p.id == pId);
        if (!prod) return;

        const existing = (window.quoteItems || []).find(i => i.product_id == prod.id);
        if (existing) {
            existing.quantity += 1;
        } else {
            window.quoteItems.push({
                product_id: prod.id,
                name: prod.name,
                model_number: prod.model_number || '',
                quantity: 1,
                unit_type: prod.unit_type || 'pcs',
                price: parseFloat(prod.price) || 0,
                discount: 0
            });
        }

        const searchInput = document.getElementById('product_search_input');
        if (searchInput) searchInput.value = '';
        const dd = document.getElementById('search_results_dropdown');
        if (dd) dd.style.display = 'none';
        window.renderItemsTable();
    };

    window.addCustomRow = function() {
        window.quoteItems.push({
            product_id: null,
            name: 'Custom Service / Product',
            model_number: '',
            quantity: 1,
            unit_type: 'pcs',
            price: 0,
            discount: 0
        });
        window.renderItemsTable();
    };

    window.renderItemsTable = function() {
        const tbody = document.getElementById('quoteItemsTbody');
        if (!tbody) return;
        if (!window.quoteItems || window.quoteItems.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 32px 14px; color: var(--text-muted);">
                        <span class="material-symbols-rounded" style="font-size: 36px; color: #cbd5e1; display: block; margin-bottom: 4px;">shopping_basket</span>
                        No items added yet. Search a product above or click "Add Custom Item".
                    </td>
                </tr>
            `;
            window.calculateTotals();
            return;
        }

        const sym = window.currencySym || 'Rs';
        tbody.innerHTML = window.quoteItems.map((item, idx) => {
            const lineTotal = Math.max(0, (item.quantity * item.price) - item.discount);
            return `
                <tr>
                    <td>
                        <input type="text" value="${escapeHtml(item.name)}" class="form-input" style="font-weight: 700;" placeholder="Item name..." oninput="window.updateItemName(${idx}, this.value)">
                        ${item.model_number ? `<div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Model: ${escapeHtml(item.model_number)}</div>` : ''}
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0.01" value="${item.quantity}" class="form-input" style="text-align: center; font-weight: 700;" oninput="window.updateItemQty(${idx}, this.value)">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" value="${item.price}" class="form-input" style="text-align: right; font-weight: 700;" oninput="window.updateItemPrice(${idx}, this.value)">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" value="${item.discount || ''}" class="form-input" style="text-align: right;" placeholder="0" oninput="window.updateItemDisc(${idx}, this.value)">
                    </td>
                    <td style="text-align: right; font-weight: 800; font-size: 15px; color: var(--text-main);">
                        ${sym} ${lineTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="btn btn-sm btn-outline" onclick="window.removeItem(${idx})" style="color: var(--error); padding: 4px 6px;">
                            <span class="material-symbols-rounded">delete</span>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        window.calculateTotals();
    };

    window.updateItemName = function(idx, val) { if (window.quoteItems[idx]) window.quoteItems[idx].name = val; };
    window.updateItemQty = function(idx, val) { if (window.quoteItems[idx]) { window.quoteItems[idx].quantity = Math.max(0, parseFloat(val) || 0); window.calculateTotals(); } };
    window.updateItemPrice = function(idx, val) { if (window.quoteItems[idx]) { window.quoteItems[idx].price = Math.max(0, parseFloat(val) || 0); window.renderItemsTable(); } };
    window.updateItemDisc = function(idx, val) { if (window.quoteItems[idx]) { window.quoteItems[idx].discount = Math.max(0, parseFloat(val) || 0); window.renderItemsTable(); } };
    window.removeItem = function(idx) { window.quoteItems.splice(idx, 1); window.renderItemsTable(); };

    window.calculateTotals = function() {
        let subtotal = 0;
        (window.quoteItems || []).forEach(i => {
            subtotal += (i.quantity * i.price) - (i.discount || 0);
        });

        const overallDiscInput = document.getElementById('overall_discount');
        const taxRateInput = document.getElementById('tax_rate');
        const extraDiscount = Math.max(0, parseFloat(overallDiscInput ? overallDiscInput.value : 0) || 0);
        const afterDiscount = Math.max(0, subtotal - extraDiscount);
        const taxRate = parseFloat(taxRateInput ? taxRateInput.value : 0) || 0;
        const taxAmount = (afterDiscount * taxRate) / 100;
        const grandTotal = afterDiscount + taxAmount;
        const sym = window.currencySym || 'Rs';

        const subtotalEl = document.getElementById('summary_subtotal');
        const grandTotalEl = document.getElementById('summary_grand_total');
        if (subtotalEl) subtotalEl.textContent = sym + ' ' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (grandTotalEl) grandTotalEl.textContent = sym + ' ' + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    };

    window.matchCustomerDetails = function(nameVal) {
        const option = document.querySelector(`#customersList option[value="${nameVal}"]`);
        if (option) {
            const idEl = document.getElementById('customer_id');
            const phoneEl = document.getElementById('customer_phone');
            const addrEl = document.getElementById('customer_address');
            if (idEl) idEl.value = option.dataset.id || '';
            if (phoneEl) phoneEl.value = option.dataset.phone || '';
            if (addrEl) addrEl.value = option.dataset.address || '';
        }
    };

    window.saveQuotation = async function(e) {
        e.preventDefault();
        if (!window.quoteItems || window.quoteItems.length === 0) {
            await window.appAlert('Please add at least one product item to the quotation.', 'Empty Quotation', 'warning');
            return;
        }

        const btn = document.getElementById('submitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-rounded" style="animation:spin 1s infinite linear;">sync</span> Saving...';
        }

        const payload = {
            csrf_token: <?= json_encode(Security::generateCsrfToken()) ?>,
            quotation_number: document.getElementById('quotation_number') ? document.getElementById('quotation_number').value : '',
            valid_days: document.getElementById('valid_days') ? document.getElementById('valid_days').value : '15',
            customer_name: document.getElementById('customer_name') ? document.getElementById('customer_name').value : '',
            customer_id: document.getElementById('customer_id') ? document.getElementById('customer_id').value : '',
            customer_phone: document.getElementById('customer_phone') ? document.getElementById('customer_phone').value : '',
            customer_address: document.getElementById('customer_address') ? document.getElementById('customer_address').value : '',
            discount: document.getElementById('overall_discount') ? document.getElementById('overall_discount').value : '0',
            tax_rate: document.getElementById('tax_rate') ? document.getElementById('tax_rate').value : '0',
            notes: document.getElementById('notes') ? document.getElementById('notes').value : '',
            items: window.quoteItems
        };

        const targetUrl = window.isEditMode
            ? '<?= defined("BASE_URL") ? BASE_URL : "" ?>/quotations/update/' + window.quoteId
            : '<?= defined("BASE_URL") ? BASE_URL : "" ?>/quotations/store';

        try {
            const resp = await fetch(targetUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await resp.json();
            if (data.success && data.redirect) {
                if (window.appToast) window.appToast(window.isEditMode ? 'Quotation updated successfully!' : 'Quotation generated successfully!', 'success');
                if (window.appLoadPage) {
                    window.appLoadPage(data.redirect);
                } else {
                    window.location.href = data.redirect;
                }
            } else {
                await window.appAlert(data.error || 'Failed to save quotation.', 'Save Error', 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<span class="material-symbols-rounded">save</span> ' + (window.isEditMode ? 'Update Quotation' : 'Save & Generate Quotation');
                }
            }
        } catch (err) {
            console.error(err);
            await window.appAlert('Network error while saving quotation. Please check your connection.', 'Network Error', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-rounded">save</span> ' + (window.isEditMode ? 'Update Quotation' : 'Save & Generate Quotation');
            }
        }
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    if (window.quoteItems.length === 0 && window.allProducts && window.allProducts.length > 0 && !window.isEditMode) {
        window.addProductToQuote(window.allProducts[0].id);
    } else {
        window.renderItemsTable();
    }
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
