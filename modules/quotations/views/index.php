<?php ob_start(); ?>

<div class="page-header">
    <div>
        <h1>Quotations & Estimates</h1>
        <p class="page-subtitle">Create proforma estimates, track customer decisions, and convert to sales invoices in 1-click.</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-primary" onclick="switchQuoteTab('create')">
            <span class="material-symbols-rounded">add_circle</span>Create Quotation
        </button>
    </div>
</div>

<!-- Tab Navigation Bar -->
<div class="card" style="margin-bottom: 18px; padding: 12px 18px;">
    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <button type="button" class="btn btn-primary quote-tab-btn" data-quote-tab="history" onclick="switchQuoteTab('history')">
            <span class="material-symbols-rounded">list_alt</span> All Quotations (<?= number_format((int)($stats['total_count'] ?? 0)) ?>)
        </button>
        <button type="button" class="btn btn-secondary quote-tab-btn" data-quote-tab="create" onclick="switchQuoteTab('create')">
            <span class="material-symbols-rounded">note_add</span> + New Quotation Studio
        </button>
        <button type="button" class="btn btn-secondary quote-tab-btn" data-quote-tab="pending" onclick="switchQuoteTab('pending')">
            <span class="material-symbols-rounded">pending_actions</span> Pending (<?= number_format((int)($stats['pending_count'] ?? 0)) ?>)
        </button>
        <button type="button" class="btn btn-secondary quote-tab-btn" data-quote-tab="converted" onclick="switchQuoteTab('converted')">
            <span class="material-symbols-rounded">verified</span> Converted to Sale (<?= number_format((int)($stats['converted_count'] ?? 0)) ?>)
        </button>
    </div>
</div>

<!-- =========================================================================
     TAB 1: ALL QUOTATIONS / HISTORY
     ========================================================================= -->
<section id="quote-panel-history" class="quote-tab-panel">

    <!-- KPI Metrics Grid -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon" style="background: rgba(15, 109, 255, 0.1); color: var(--primary);">
                <span class="material-symbols-rounded">receipt_long</span>
            </div>
            <div>
                <div class="kpi-label">Total Quotations</div>
                <div class="kpi-value"><?= number_format((int)($stats['total_count'] ?? 0)) ?></div>
                <div class="kpi-subtext"><?= number_format((int)($stats['total_count'] ?? 0)) ?> estimates generated</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                <span class="material-symbols-rounded">pending_actions</span>
            </div>
            <div>
                <div class="kpi-label">Active / Pending Value</div>
                <div class="kpi-value" style="color: var(--warning);"><?= App::money((float)($stats['pending_value'] ?? 0)) ?></div>
                <div class="kpi-subtext"><?= (int)($stats['pending_count'] ?? 0) ?> active estimates awaiting conversion</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background: rgba(5, 150, 105, 0.1); color: var(--success);">
                <span class="material-symbols-rounded">shopping_cart_checkout</span>
            </div>
            <div>
                <div class="kpi-label">Converted to Sales</div>
                <div class="kpi-value" style="color: var(--success);"><?= App::money((float)($stats['converted_value'] ?? 0)) ?></div>
                <div class="kpi-subtext"><?= (int)($stats['converted_count'] ?? 0) ?> converted to official bills</div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <form method="GET" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/index" class="card filter-card" style="margin-bottom: 18px; padding: 14px 18px;">
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div class="field-icon-wrap" style="flex: 1; min-width: 240px; max-width: 440px; position: relative;">
                <span class="material-symbols-rounded" style="position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 20px; pointer-events: none;">search</span>
                <input type="text" name="q" value="<?= Security::escape($query ?? '') ?>" class="form-input" style="height: 42px; padding: 0 14px 0 42px; border-radius: 12px; font-size: 13.5px; border: 1.5px solid var(--border);" placeholder="Search quotation #, customer name or phone...">
            </div>
            <div style="min-width: 170px; max-width: 200px;">
                <select name="status" class="form-select" onchange="this.form.submit()" style="height: 42px; border-radius: 12px; font-size: 13.5px; font-weight: 600; border: 1.5px solid var(--border); padding: 0 34px 0 14px;">
                    <option value="all" <?= ($statusFilter ?? '') === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="pending" <?= ($statusFilter ?? '') === 'pending' ? 'selected' : '' ?>>Pending (Active)</option>
                    <option value="converted" <?= ($statusFilter ?? '') === 'converted' ? 'selected' : '' ?>>Converted to Sale</option>
                    <option value="expired" <?= ($statusFilter ?? '') === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px; padding: 0 20px; font-size: 13.5px; font-weight: 700; border-radius: 12px; display: inline-flex; align-items: center; gap: 7px; box-shadow: 0 4px 14px rgba(15, 109, 255, 0.25);">
                <span class="material-symbols-rounded" style="font-size: 18px;">tune</span>
                Filter
            </button>
            <?php if (!empty($query) || ($statusFilter ?? 'all') !== 'all'): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/index" class="btn btn-ghost" style="height: 42px; padding: 0 16px; font-size: 13px; font-weight: 700; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px;">
                    <span class="material-symbols-rounded" style="font-size: 18px;">restart_alt</span>
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Data Table Card -->
    <div class="card table-card" style="padding: 0; overflow: hidden; margin-bottom: 24px;">
        <div class="table-responsive">
            <table class="table" style="min-width: 950px; width: 100%;">
                <thead>
                    <tr>
                        <th style="min-width: 140px;">Quotation #</th>
                        <th style="min-width: 180px;">Customer</th>
                        <th style="min-width: 160px;">Date & Validity</th>
                        <th style="min-width: 100px; text-align: center;">Items</th>
                        <th style="min-width: 130px; text-align: right;">Total Amount</th>
                        <th style="min-width: 120px; text-align: center;">Status</th>
                        <th style="min-width: 160px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($quotations)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 48px 16px; color: var(--text-muted);">
                                <span class="material-symbols-rounded" style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 8px;">request_quote</span>
                                <div style="font-size: 16px; font-weight: 700; color: var(--text-main);">No quotations found</div>
                                <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Click the "+ New Quotation Studio" tab to generate an estimate.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($quotations as $q): ?>
                            <tr>
                                <td>
                                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/view/<?= (int)$q['id'] ?>" style="font-weight: 800; color: var(--primary); text-decoration: none;">
                                        <?= htmlspecialchars($q['quotation_number']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($q['customer_name']) ?></div>
                                    <?php if (!empty($q['customer_phone'])): ?>
                                        <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($q['customer_phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size: 13px; font-weight: 600; color: var(--text-main);"><?= date('M d, Y', strtotime($q['created_at'])) ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted);">Valid until <?= date('M d, Y', strtotime($q['valid_until'])) ?></div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge" style="background: rgba(15, 109, 255, 0.1); color: var(--primary); font-weight: 800;">
                                        <?= (int)($q['total_items'] ?? 0) ?> items
                                    </span>
                                </td>
                                <td style="text-align: right; font-weight: 800; font-size: 15px; color: var(--text-main);">
                                    <?= App::money((float)$q['total']) ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($q['status'] === 'converted'): ?>
                                        <span class="badge badge-success">Converted</span>
                                    <?php elseif ($q['status'] === 'expired'): ?>
                                        <span class="badge badge-danger">Expired</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 6px; align-items: center;">
                                        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/view/<?= (int)$q['id'] ?>" class="btn btn-sm btn-primary" title="View & Print" data-no-ajax>
                                            <span class="material-symbols-rounded">visibility</span> View
                                        </a>
                                        <?php if ($q['status'] === 'pending'): ?>
                                            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/edit/<?= (int)$q['id'] ?>" class="btn btn-sm btn-outline" title="Edit Quotation" data-no-ajax>
                                                <span class="material-symbols-rounded">edit</span>
                                            </a>
                                            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/convert/<?= (int)$q['id'] ?>" class="btn btn-sm btn-secondary" title="Convert to Sale Invoice" data-no-ajax style="color: var(--success); font-weight: 800;">
                                                <span class="material-symbols-rounded">shopping_cart_checkout</span> Convert
                                            </a>
                                            <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/delete/<?= (int)$q['id'] ?>" onsubmit="return handleQuoteDelete(event, this, '<?= htmlspecialchars($q['quotation_number']) ?>');" style="display: inline;" data-no-ajax>
                                                <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                                                <button type="submit" class="btn btn-sm btn-outline" style="color: var(--error);" title="Delete">
                                                    <span class="material-symbols-rounded">delete</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
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

<!-- =========================================================================
     TAB 2: INTEGRATED STEP-BY-STEP QUOTATION STUDIO
     ========================================================================= -->
<section id="quote-panel-create" class="quote-tab-panel" style="display: none;">

    <!-- Wizard Stepper Navigation Header -->
    <div class="card" style="margin-bottom: 20px; padding: 14px 20px;">
        <div class="wizard-stepper">
            <div class="wizard-step active" id="tab-step-indicator-1" onclick="goToTabQuoteStep(1)">
                <div class="step-badge" id="tab-step-badge-1">1</div>
                <div class="step-info">
                    <strong>Customer & Terms</strong>
                    <small>Customer, phone & validity</small>
                </div>
            </div>
            <div class="step-connector"></div>
            <div class="wizard-step" id="tab-step-indicator-2" onclick="goToTabQuoteStep(2)">
                <div class="step-badge" id="tab-step-badge-2">2</div>
                <div class="step-info">
                    <strong>Items & Pricing</strong>
                    <small>Search products & discounts</small>
                </div>
            </div>
            <div class="step-connector"></div>
            <div class="wizard-step" id="tab-step-indicator-3" onclick="goToTabQuoteStep(3)">
                <div class="step-badge" id="tab-step-badge-3">3</div>
                <div class="step-info">
                    <strong>Summary & Confirm</strong>
                    <small>Taxes, terms & finalize</small>
                </div>
            </div>
        </div>
    </div>

    <form id="tabQuotationForm" onsubmit="saveTabQuotation(event)">
        
        <!-- STEP 1: CUSTOMER & TERMS -->
        <div id="tab-step-content-1" class="tab-step-panel">
            <div class="card" style="margin-bottom: 20px; padding: 24px;">
                <div style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
                    <span class="material-symbols-rounded" style="color: var(--primary);">person</span> Step 1: Customer Information & Quote Validity
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Quotation Number</label>
                        <input type="text" id="tab_quotation_number" value="<?= htmlspecialchars($nextNumber ?? 'QT-0001') ?>" class="form-input" style="font-weight: 800; color: var(--primary); background: var(--bg-soft);" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quote Validity Period</label>
                        <select id="tab_valid_days" class="form-select">
                            <option value="7">7 Days (Fast Validity)</option>
                            <option value="15" selected>15 Days (Standard)</option>
                            <option value="30">30 Days (Extended)</option>
                            <option value="60">60 Days (Project Estimate)</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Customer Name *</label>
                        <input type="text" id="tab_customer_name" class="form-input" placeholder="e.g. Ali Ahmed" required list="tabCustomersList" oninput="matchTabCustomerDetails(this.value)">
                        <datalist id="tabCustomersList">
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= htmlspecialchars($c['name']) ?>" data-id="<?= (int)$c['id'] ?>" data-phone="<?= htmlspecialchars($c['phone'] ?? '') ?>" data-address="<?= htmlspecialchars($c['address'] ?? '') ?>">
                                    <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['phone'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                        <input type="hidden" id="tab_customer_id" value="">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Customer Phone (WhatsApp)</label>
                        <input type="text" id="tab_customer_phone" class="form-input" placeholder="0300-1234567">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Delivery / Office Address</label>
                        <input type="text" id="tab_customer_address" class="form-input" placeholder="e.g. Shop # 4, Main Commercial Market, Lahore">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
                    <button type="button" class="btn btn-primary" onclick="goToTabQuoteStep(2)">
                        Next: Add Products & Pricing <span class="material-symbols-rounded">arrow_forward</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- STEP 2: PRODUCTS & PRICING -->
        <div id="tab-step-content-2" class="tab-step-panel" style="display: none;">
            <div class="card" style="margin-bottom: 20px; padding: 24px;">
                <div style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-rounded" style="color: var(--primary);">inventory_2</span> Step 2: Line Items & Quotation Pricing
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="addTabCustomRow()">
                        <span class="material-symbols-rounded">add</span> Add Custom Item
                    </button>
                </div>

                <!-- Product Search & Scanner Input -->
                <div style="position: relative; margin-bottom: 18px;">
                    <div class="field-icon-wrap">
                        <span class="material-symbols-rounded">search</span>
                        <input type="text" id="tab_product_search_input" class="form-input" placeholder="Scan barcode, type model number, or product name to add..." oninput="filterTabProducts(this.value)" autocomplete="off">
                    </div>
                    <div id="tab_search_results_dropdown" style="position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-surface); border: 1px solid var(--border); border-radius: 12px; max-height: 260px; overflow-y: auto; z-index: 100; box-shadow: var(--shadow-md); display: none;"></div>
                </div>

                <!-- Items Responsive Table -->
                <div class="table-responsive" style="margin-bottom: 16px;">
                    <table class="table" style="min-width: 680px; width: 100%;">
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
                        <tbody id="tabQuoteItemsTbody">
                            <!-- Injected by JS -->
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
                    <button type="button" class="btn btn-secondary" onclick="goToTabQuoteStep(1)">
                        <span class="material-symbols-rounded">arrow_back</span> Back to Customer
                    </button>
                    <button type="button" class="btn btn-primary" onclick="goToTabQuoteStep(3)">
                        Next: Review Summary & Terms <span class="material-symbols-rounded">arrow_forward</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- STEP 3: SUMMARY, TERMS & CONFIRM -->
        <div id="tab-step-content-3" class="tab-step-panel" style="display: none;">
            <div style="display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr); gap: 20px; align-items: start;">
                
                <!-- Left: Terms & Delivery Notes -->
                <div>
                    <div class="card" style="padding: 24px; margin-bottom: 20px;">
                        <div style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                            <span class="material-symbols-rounded" style="color: var(--primary);">description</span> Terms & Delivery Conditions
                        </div>
                        <textarea id="tab_notes" class="form-textarea" rows="4" placeholder="e.g. 1. Delivery within 3 business days. 2. Prices valid for 15 days. 3. 50% advance on order placement."></textarea>
                    </div>

                    <div class="card" style="padding: 20px; background: var(--bg-soft); border-color: rgba(15, 109, 255, 0.2);">
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <span class="material-symbols-rounded" style="color: var(--primary); font-size: 28px;">verified_user</span>
                            <div>
                                <strong style="color: var(--text-main); font-size: 14px;">Instant Conversion Available</strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">This quotation can be converted into an official sale bill anytime with 1-click once approved by customer.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Final Summary & Save Button -->
                <div class="card" style="background: var(--bg-sidebar); color: #ffffff; padding: 24px; border: none; box-shadow: var(--shadow-md);">
                    <div style="font-size: 18px; font-weight: 800; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid rgba(255,255,255,0.12); padding-bottom: 14px;">
                        <span class="material-symbols-rounded" style="color: var(--primary);">calculate</span> Quotation Breakdown
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 14px; color: var(--text-light);">
                        <span>Items Subtotal</span>
                        <strong id="tab_summary_subtotal" style="color: #ffffff; font-size: 16px;">Rs 0.00</strong>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="font-size: 12px; font-weight: 700; color: var(--text-light); margin-bottom: 4px; display: block;">Overall Extra Discount (Rs)</label>
                        <input type="number" step="0.01" id="tab_overall_discount" value="0" class="form-input" placeholder="0.00" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); color: #ffffff; font-weight: 700;" oninput="calculateTabTotals()">
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 12px; font-weight: 700; color: var(--text-light); margin-bottom: 4px; display: block;">Tax Rate (%)</label>
                        <input type="number" step="0.01" id="tab_tax_rate" value="<?= (float)($taxSettings['rate'] ?? 0) ?>" class="form-input" placeholder="0.00" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); color: #ffffff; font-weight: 700;" oninput="calculateTabTotals()">
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 18px; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.15);">
                        <span style="font-size: 16px; font-weight: 800; color: #ffffff;">Net Grand Total</span>
                        <strong id="tab_summary_grand_total" style="font-size: 24px; font-weight: 900; color: #38bdf8;">Rs 0.00</strong>
                    </div>

                    <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 10px;">
                        <button type="submit" id="tabSubmitBtn" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 15px; font-weight: 800; justify-content: center;">
                            <span class="material-symbols-rounded">save</span> Save & Generate Quotation
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="goToTabQuoteStep(2)" style="width: 100%; justify-content: center; background: rgba(255,255,255,0.1); color: #fff; border-color: rgba(255,255,255,0.2);">
                            <span class="material-symbols-rounded">arrow_back</span> Back to Items
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </form>
</section>

<!-- =========================================================================
     TAB 3: PENDING ESTIMATES
     ========================================================================= -->
<section id="quote-panel-pending" class="quote-tab-panel" style="display: none;">
    <div class="card table-card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="table" style="min-width: 800px; width: 100%;">
                <thead>
                    <tr>
                        <th style="min-width: 140px;">Quotation #</th>
                        <th style="min-width: 180px;">Customer</th>
                        <th style="min-width: 160px;">Validity</th>
                        <th style="min-width: 130px; text-align: right;">Amount</th>
                        <th style="min-width: 150px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $pendingQuotes = array_filter($quotations, fn($i) => $i['status'] === 'pending');
                    if (empty($pendingQuotes)): 
                    ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                No pending quotations right now.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendingQuotes as $q): ?>
                            <tr>
                                <td>
                                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/view/<?= (int)$q['id'] ?>" style="font-weight: 800; color: var(--primary);" data-no-ajax>
                                        <?= htmlspecialchars($q['quotation_number']) ?>
                                    </a>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($q['customer_name']) ?></strong>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($q['customer_phone'] ?? '') ?></div>
                                </td>
                                <td>
                                    <span style="color: var(--warning); font-weight: 700;">Valid until <?= date('M d, Y', strtotime($q['valid_until'])) ?></span>
                                </td>
                                <td style="text-align: right; font-weight: 800; color: var(--text-main);">
                                    <?= App::money((float)$q['total']) ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/view/<?= (int)$q['id'] ?>" class="btn btn-sm btn-primary" data-no-ajax>
                                            <span class="material-symbols-rounded">visibility</span> View
                                        </a>
                                        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/convert/<?= (int)$q['id'] ?>" class="btn btn-sm btn-secondary" style="color: var(--success); font-weight: 800;" data-no-ajax>
                                            <span class="material-symbols-rounded">shopping_cart_checkout</span> Convert
                                        </a>
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

<!-- =========================================================================
     TAB 4: CONVERTED TO SALES
     ========================================================================= -->
<section id="quote-panel-converted" class="quote-tab-panel" style="display: none;">
    <div class="card table-card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="table" style="min-width: 850px; width: 100%;">
                <thead>
                    <tr>
                        <th style="min-width: 140px;">Quotation #</th>
                        <th style="min-width: 180px;">Customer</th>
                        <th style="min-width: 150px;">Created Date</th>
                        <th style="min-width: 130px; text-align: right;">Amount</th>
                        <th style="min-width: 120px; text-align: center;">Status</th>
                        <th style="min-width: 150px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $convertedQuotes = array_filter($quotations, fn($i) => $i['status'] === 'converted');
                    if (empty($convertedQuotes)): 
                    ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                No converted quotations yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($convertedQuotes as $q): ?>
                            <tr>
                                <td>
                                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/view/<?= (int)$q['id'] ?>" style="font-weight: 800; color: var(--primary);" data-no-ajax>
                                        <?= htmlspecialchars($q['quotation_number']) ?>
                                    </a>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($q['customer_name']) ?></strong>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($q['customer_phone'] ?? '') ?></div>
                                </td>
                                <td><?= date('M d, Y', strtotime($q['created_at'])) ?></td>
                                <td style="text-align: right; font-weight: 800; color: var(--success);">
                                    <?= App::money((float)$q['total']) ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge badge-success">Converted</span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/view/<?= (int)$q['id'] ?>" class="btn btn-sm btn-primary" data-no-ajax>
                                        <span class="material-symbols-rounded">visibility</span> View Bill
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php ob_start(); ?>
<script>
    window.allProducts = <?= json_encode($products ?? []) ?>;
    window.currencySym = <?= json_encode(App::currencySymbol()) ?>;
    window.tabQuoteItems = [];
    window.currentTabQuoteStep = 1;

    window.handleQuoteDelete = async function(e, form, qNum) {
        e.preventDefault();
        const confirmed = await window.appConfirm(
            `Are you sure you want to permanently delete quotation <strong>${qNum}</strong>? This action cannot be undone.`,
            'Delete Quotation',
            { isDanger: true, confirmText: 'Delete Quotation', icon: 'delete' }
        );
        if (confirmed) {
            form.removeAttribute('onsubmit');
            form.submit();
        }
        return false;
    };

    window.goToTabQuoteStep = async function(stepNum) {
        if (stepNum === 2) {
            const custInput = document.getElementById('tab_customer_name');
            const custName = custInput ? custInput.value.trim() : '';
            if (!custName) {
                await window.appAlert('Please enter or select Customer Name first before proceeding to items.', 'Customer Required', 'warning');
                if (custInput) custInput.focus();
                return;
            }
        }
        if (stepNum === 3) {
            if (!window.tabQuoteItems || window.tabQuoteItems.length === 0) {
                await window.appAlert('Please add at least one product item to the quotation before reviewing the summary.', 'No Items Added', 'warning');
                return;
            }
            window.calculateTabTotals();
        }

        window.currentTabQuoteStep = stepNum;

        for (let i = 1; i <= 3; i++) {
            const panel = document.getElementById('tab-step-content-' + i);
            const indicator = document.getElementById('tab-step-indicator-' + i);
            const badge = document.getElementById('tab-step-badge-' + i);

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

    window.switchQuoteTab = function(tabKey) {
        document.querySelectorAll('.quote-tab-btn').forEach(btn => {
            if (btn.dataset.quoteTab === tabKey) {
                btn.className = 'btn btn-primary quote-tab-btn';
            } else {
                btn.className = 'btn btn-secondary quote-tab-btn';
            }
        });

        document.querySelectorAll('.quote-tab-panel').forEach(panel => {
            panel.style.display = (panel.id === 'quote-panel-' + tabKey) ? 'block' : 'none';
        });

        if (tabKey === 'create') {
            window.goToTabQuoteStep(1);
            if (window.tabQuoteItems.length === 0 && window.allProducts && window.allProducts.length > 0) {
                window.selectTabProductToAdd(window.allProducts[0].id);
            }
        }
    };

    window.filterTabProducts = function(query) {
        const dd = document.getElementById('tab_search_results_dropdown');
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
            <div style="padding: 10px 14px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);" onclick="window.selectTabProductToAdd(${p.id})">
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

    window.selectTabProductToAdd = function(pId) {
        const products = window.allProducts || [];
        const prod = products.find(p => p.id == pId);
        if (!prod) return;

        const existing = window.tabQuoteItems.find(i => i.product_id == prod.id);
        if (existing) {
            existing.quantity += 1;
        } else {
            window.tabQuoteItems.push({
                product_id: prod.id,
                name: prod.name,
                model_number: prod.model_number || '',
                quantity: 1,
                unit_type: prod.unit_type || 'pcs',
                price: parseFloat(prod.price) || 0,
                discount: 0
            });
        }

        const searchInput = document.getElementById('tab_product_search_input');
        if (searchInput) searchInput.value = '';
        const dd = document.getElementById('tab_search_results_dropdown');
        if (dd) dd.style.display = 'none';
        window.renderTabItemsTable();
    };

    window.addTabCustomRow = function() {
        window.tabQuoteItems.push({
            product_id: null,
            name: 'Custom Service / Product',
            model_number: '',
            quantity: 1,
            unit_type: 'pcs',
            price: 0,
            discount: 0
        });
        window.renderTabItemsTable();
    };

    window.renderTabItemsTable = function() {
        const tbody = document.getElementById('tabQuoteItemsTbody');
        if (!tbody) return;
        if (window.tabQuoteItems.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 32px 14px; color: var(--text-muted);">
                        <span class="material-symbols-rounded" style="font-size: 36px; color: #cbd5e1; display: block; margin-bottom: 4px;">shopping_basket</span>
                        No items added yet. Search a product above or click "Add Custom Item".
                    </td>
                </tr>
            `;
            window.calculateTabTotals();
            return;
        }

        const sym = window.currencySym || 'Rs';
        tbody.innerHTML = window.tabQuoteItems.map((item, idx) => {
            const lineTotal = Math.max(0, (item.quantity * item.price) - item.discount);
            return `
                <tr>
                    <td>
                        <input type="text" value="${escapeHtml(item.name)}" class="form-input" style="font-weight: 700;" placeholder="Item name..." oninput="window.updateTabItemName(${idx}, this.value)">
                        ${item.model_number ? `<div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Model: ${escapeHtml(item.model_number)}</div>` : ''}
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0.01" value="${item.quantity}" class="form-input" style="text-align: center; font-weight: 700;" oninput="window.updateTabItemQty(${idx}, this.value)">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" value="${item.price}" class="form-input" style="text-align: right; font-weight: 700;" oninput="window.updateTabItemPrice(${idx}, this.value)">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" value="${item.discount || ''}" class="form-input" style="text-align: right;" placeholder="0" oninput="window.updateTabItemDisc(${idx}, this.value)">
                    </td>
                    <td style="text-align: right; font-weight: 800; font-size: 15px; color: var(--text-main);">
                        ${sym} ${lineTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="btn btn-sm btn-outline" onclick="window.removeTabItem(${idx})" style="color: var(--error); padding: 4px 6px;">
                            <span class="material-symbols-rounded">delete</span>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        window.calculateTabTotals();
    };

    window.updateTabItemName = function(idx, val) { if (window.tabQuoteItems[idx]) window.tabQuoteItems[idx].name = val; };
    window.updateTabItemQty = function(idx, val) { if (window.tabQuoteItems[idx]) { window.tabQuoteItems[idx].quantity = Math.max(0, parseFloat(val) || 0); window.calculateTabTotals(); } };
    window.updateTabItemPrice = function(idx, val) { if (window.tabQuoteItems[idx]) { window.tabQuoteItems[idx].price = Math.max(0, parseFloat(val) || 0); window.renderTabItemsTable(); } };
    window.updateTabItemDisc = function(idx, val) { if (window.tabQuoteItems[idx]) { window.tabQuoteItems[idx].discount = Math.max(0, parseFloat(val) || 0); window.renderTabItemsTable(); } };
    window.removeTabItem = function(idx) { window.tabQuoteItems.splice(idx, 1); window.renderTabItemsTable(); };

    window.calculateTabTotals = function() {
        let subtotal = 0;
        (window.tabQuoteItems || []).forEach(i => {
            subtotal += (i.quantity * i.price) - (i.discount || 0);
        });

        const overallDiscInput = document.getElementById('tab_overall_discount');
        const taxRateInput = document.getElementById('tab_tax_rate');
        const extraDiscount = Math.max(0, parseFloat(overallDiscInput ? overallDiscInput.value : 0) || 0);
        const afterDiscount = Math.max(0, subtotal - extraDiscount);
        const taxRate = parseFloat(taxRateInput ? taxRateInput.value : 0) || 0;
        const taxAmount = (afterDiscount * taxRate) / 100;
        const grandTotal = afterDiscount + taxAmount;
        const sym = window.currencySym || 'Rs';

        const subtotalEl = document.getElementById('tab_summary_subtotal');
        const grandTotalEl = document.getElementById('tab_summary_grand_total');
        if (subtotalEl) subtotalEl.textContent = sym + ' ' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (grandTotalEl) grandTotalEl.textContent = sym + ' ' + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    };

    window.matchTabCustomerDetails = function(nameVal) {
        const option = document.querySelector(`#tabCustomersList option[value="${nameVal}"]`);
        if (option) {
            const idEl = document.getElementById('tab_customer_id');
            const phoneEl = document.getElementById('tab_customer_phone');
            const addrEl = document.getElementById('tab_customer_address');
            if (idEl) idEl.value = option.dataset.id || '';
            if (phoneEl) phoneEl.value = option.dataset.phone || '';
            if (addrEl) addrEl.value = option.dataset.address || '';
        }
    };

    window.saveTabQuotation = async function(e) {
        e.preventDefault();
        if (!window.tabQuoteItems || window.tabQuoteItems.length === 0) {
            await window.appAlert('Please add at least one product item to the quotation before saving.', 'Empty Quotation', 'warning');
            return;
        }

        const btn = document.getElementById('tabSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-rounded" style="animation:spin 1s infinite linear;">sync</span> Saving...';
        }

        const payload = {
            csrf_token: <?= json_encode(Security::generateCsrfToken()) ?>,
            quotation_number: document.getElementById('tab_quotation_number') ? document.getElementById('tab_quotation_number').value : '',
            valid_days: document.getElementById('tab_valid_days') ? document.getElementById('tab_valid_days').value : '15',
            customer_name: document.getElementById('tab_customer_name') ? document.getElementById('tab_customer_name').value : '',
            customer_id: document.getElementById('tab_customer_id') ? document.getElementById('tab_customer_id').value : '',
            customer_phone: document.getElementById('tab_customer_phone') ? document.getElementById('tab_customer_phone').value : '',
            customer_address: document.getElementById('tab_customer_address') ? document.getElementById('tab_customer_address').value : '',
            discount: document.getElementById('tab_overall_discount') ? document.getElementById('tab_overall_discount').value : '0',
            tax_rate: document.getElementById('tab_tax_rate') ? document.getElementById('tab_tax_rate').value : '0',
            notes: document.getElementById('tab_notes') ? document.getElementById('tab_notes').value : '',
            items: window.tabQuoteItems
        };

        try {
            const resp = await fetch('<?= defined("BASE_URL") ? BASE_URL : "" ?>/quotations/store', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await resp.json();
            if (data.success && data.redirect) {
                if (window.appToast) window.appToast('Quotation generated successfully!', 'success');
                if (window.appLoadPage) {
                    window.appLoadPage(data.redirect);
                } else {
                    window.location.href = data.redirect;
                }
            } else {
                await window.appAlert(data.error || 'Failed to save quotation.', 'Save Error', 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<span class="material-symbols-rounded">save</span> Save & Generate Quotation';
                }
            }
        } catch (err) {
            console.error(err);
            await window.appAlert('Network error while saving quotation. Please check your connection.', 'Network Error', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-rounded">save</span> Save & Generate Quotation';
            }
        }
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    // Check URL hash immediately
    const hash = window.location.hash.replace('#', '');
    if (['create', 'pending', 'converted', 'history'].includes(hash)) {
        window.switchQuoteTab(hash);
    }
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
