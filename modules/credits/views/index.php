<?php ob_start(); ?>

<?php $filters = $filters ?? []; ?>
<?php
$showDueDateRange = ($filters['duePreset'] ?? '') === 'custom';
$showPaymentDateRange = ($filters['paymentPreset'] ?? '') === 'custom';
?>

<style>
    .page-header-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .page-header-actions .btn {
        padding: 6px 12px;
        font-size: 13px;
        height: 36px;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .page-header-actions .btn span.material-symbols-rounded {
        font-size: 18px;
    }
    @media (max-width: 768px) {
        .page-header-actions {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 4px;
            -webkit-overflow-scrolling: touch;
            justify-content: flex-start;
            flex-wrap: nowrap;
        }
        .page-header-actions::-webkit-scrollbar {
            height: 0px;
        }
    }
</style>

<div class="page-header">
    <div>
        <h1>Credit Dues</h1>
        <p class="page-subtitle">Track pending credit invoices and open complete credit profile for collection actions.</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-secondary" onclick="openImportModal()"><span class="material-symbols-rounded">upload</span>Import CSV</button>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/credits/export?<?php echo http_build_query($_GET); ?>" class="btn btn-secondary"><span class="material-symbols-rounded">download</span>Export CSV</a>
    </div>
</div>

<div class="card" style="margin-bottom:18px;">
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button type="button" class="btn btn-primary" data-credit-tab="dues">Pending Dues</button>
        <button type="button" class="btn btn-secondary" data-credit-tab="history">Payment History</button>
    </div>
</div>

<section data-credit-panel="dues">
    <form method="GET" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/" class="card filter-card" style="margin-bottom:18px;">
        <input type="hidden" name="url" value="credits/index">
        <div class="filter-grid">
            <div class="field-icon-wrap">
                <span class="material-symbols-rounded">search</span>
                <input class="form-input" type="text" name="q" value="<?php echo Security::escape($filters['q'] ?? ''); ?>" placeholder="Search invoice, customer, phone, note" data-live-search>
            </div>
            <select class="form-select" name="due_state" data-live-search>
                <option value="">All Due States</option>
                <option value="overdue" <?php echo ($filters['dueState'] ?? '') === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                <option value="today" <?php echo ($filters['dueState'] ?? '') === 'today' ? 'selected' : ''; ?>>Due Today</option>
                <option value="upcoming" <?php echo ($filters['dueState'] ?? '') === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                <option value="no_date" <?php echo ($filters['dueState'] ?? '') === 'no_date' ? 'selected' : ''; ?>>No Due Date</option>
            </select>
            <select class="form-select" name="due_preset" data-live-search data-custom-date-select data-custom-date-target="due">
                <option value="" <?php echo ($filters['duePreset'] ?? '') === '' ? 'selected' : ''; ?>>All Dates</option>
                <option value="custom" <?php echo ($filters['duePreset'] ?? '') === 'custom' ? 'selected' : ''; ?>>Custom Dates</option>
                <option value="today" <?php echo ($filters['duePreset'] ?? '') === 'today' ? 'selected' : ''; ?>>Today</option>
                <option value="this_month" <?php echo ($filters['duePreset'] ?? '') === 'this_month' ? 'selected' : ''; ?>>This Month</option>
            </select>
            <input class="form-input" type="date" name="due_from" value="<?php echo Security::escape($filters['dueFrom'] ?? ''); ?>" data-live-search data-custom-date-field="due" <?php echo $showDueDateRange ? '' : 'hidden disabled'; ?>>
            <input class="form-input" type="date" name="due_to" value="<?php echo Security::escape($filters['dueTo'] ?? ''); ?>" data-live-search data-custom-date-field="due" <?php echo $showDueDateRange ? '' : 'hidden disabled'; ?>>
            <select class="form-select" name="sort" data-live-search>
                <option value="due_asc" <?php echo ($filters['sort'] ?? 'due_asc') === 'due_asc' ? 'selected' : ''; ?>>Due Date Asc</option>
                <option value="due_desc" <?php echo ($filters['sort'] ?? '') === 'due_desc' ? 'selected' : ''; ?>>Due Date Desc</option>
                <option value="latest" <?php echo ($filters['sort'] ?? '') === 'latest' ? 'selected' : ''; ?>>Latest Invoice</option>
            </select>
            <button class="btn btn-ghost" type="button" data-clear-filters>Reset</button>
        </div>
    </form>

    <div class="card table-wrap" style="margin-bottom:20px;">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Remaining</th>
                    <th>Next Due</th>
                    <th style="text-align:right;">View</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($credits)): ?>
                    <tr><td colspan="7" class="empty-state">No pending credit invoices for selected filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($credits as $credit): ?>
                        <tr>
                            <td><strong><?php echo Security::escape($credit['custom_id']); ?></strong></td>
                            <td>
                                <?php echo Security::escape($credit['customer_name']); ?>
                                <div class="page-subtitle"><?php echo Security::escape($credit['customer_phone']); ?></div>
                            </td>
                            <td><?php echo App::money($credit['total']); ?></td>
                            <td><?php echo App::money($credit['paid_amount']); ?></td>
                            <td><strong style="color:var(--error);"><?php echo App::money($credit['open_due'] ?? $credit['balance_due']); ?></strong></td>
                            <td>
                                <?php echo $credit['credit_due_date'] ? date('M d, Y h:i A', strtotime($credit['credit_due_date'])) : '-'; ?>
                                <?php if (!empty($credit['credit_private_note'])): ?>
                                    <div class="page-subtitle"><?php echo Security::escape($credit['credit_private_note']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="icon-btn" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/credits/action/<?php echo (int) $credit['id']; ?>" title="Open Credit Profile"><span class="material-symbols-rounded">visibility</span></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section data-credit-panel="history" style="display:none;">
    <form method="GET" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/" class="card filter-card" style="margin-bottom:18px;">
        <input type="hidden" name="url" value="credits/index">
        <div class="filter-grid">
            <div class="field-icon-wrap">
                <span class="material-symbols-rounded">search</span>
                <input class="form-input" type="text" name="payment_q" value="<?php echo Security::escape($filters['paymentQ'] ?? ''); ?>" placeholder="Search invoice or note" data-live-search>
            </div>
            <select class="form-select" name="payment_method" data-live-search>
                <option value="">All Methods</option>
                <option value="cash" <?php echo ($filters['paymentMethodFilter'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash</option>
                <option value="online" <?php echo ($filters['paymentMethodFilter'] ?? '') === 'online' ? 'selected' : ''; ?>>Online</option>
                <option value="credit" <?php echo ($filters['paymentMethodFilter'] ?? '') === 'credit' ? 'selected' : ''; ?>>Credit Entry</option>
            </select>
            <select class="form-select" name="payment_preset" data-live-search data-custom-date-select data-custom-date-target="payment">
                <option value="" <?php echo ($filters['paymentPreset'] ?? '') === '' ? 'selected' : ''; ?>>All Dates</option>
                <option value="custom" <?php echo ($filters['paymentPreset'] ?? '') === 'custom' ? 'selected' : ''; ?>>Custom Dates</option>
                <option value="today" <?php echo ($filters['paymentPreset'] ?? '') === 'today' ? 'selected' : ''; ?>>Today</option>
                <option value="this_month" <?php echo ($filters['paymentPreset'] ?? '') === 'this_month' ? 'selected' : ''; ?>>This Month</option>
            </select>
            <input class="form-input" type="date" name="payment_from" value="<?php echo Security::escape($filters['paymentFrom'] ?? ''); ?>" data-live-search data-custom-date-field="payment" <?php echo $showPaymentDateRange ? '' : 'hidden disabled'; ?>>
            <input class="form-input" type="date" name="payment_to" value="<?php echo Security::escape($filters['paymentTo'] ?? ''); ?>" data-live-search data-custom-date-field="payment" <?php echo $showPaymentDateRange ? '' : 'hidden disabled'; ?>>
            <button class="btn btn-ghost" type="button" data-clear-filters>Reset</button>
        </div>
    </form>

    <div class="card table-wrap">
        <h3 style="margin-bottom:14px;">Recent Credit Payments</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Next Due</th>
                    <th style="text-align:right;">Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="6" class="empty-state">No payments found for selected filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo date('M d, Y h:i A', strtotime($payment['created_at'])); ?></td>
                            <td><?php echo Security::escape($payment['custom_id']); ?></td>
                            <td><?php echo App::money($payment['amount']); ?></td>
                            <td><?php echo ucfirst(Security::escape($payment['payment_method'])); ?></td>
                            <td><?php echo $payment['next_due_date'] ? date('M d, Y h:i A', strtotime($payment['next_due_date'])) : '-'; ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="icon-btn" target="_blank" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/credits/receipt/<?php echo (int) $payment['id']; ?>" title="Open receipt">
                                        <span class="material-symbols-rounded">visibility</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php ob_start(); ?>
<script>
    (() => {
        const tabButtons = document.querySelectorAll('[data-credit-tab]');
        const panels = document.querySelectorAll('[data-credit-panel]');
        const tabKey = 'tab_' + (new URLSearchParams(window.location.search).get('url') || 'credits/index');

        const activate = (tab) => {
            sessionStorage.setItem(tabKey, tab);
            tabButtons.forEach((btn) => {
                const active = btn.dataset.creditTab === tab;
                btn.classList.toggle('btn-primary', active);
                btn.classList.toggle('btn-secondary', !active);
            });
            panels.forEach((panel) => {
                panel.style.display = panel.dataset.creditPanel === tab ? '' : 'none';
            });
        };

        tabButtons.forEach((btn) => btn.addEventListener('click', () => activate(btn.dataset.creditTab)));

        const params = new URLSearchParams(window.location.search);
        const saved = sessionStorage.getItem(tabKey);
        if (saved) {
            activate(saved);
        } else if (params.get('payment_q') || params.get('payment_method') || params.get('payment_from') || params.get('payment_to') || params.get('payment_preset')) {
            activate('history');
        } else {
            activate('dues');
        }
    })();

    window.openImportModal = () => {
        window.appModal.open({
            title: 'Import Credit Dues from CSV',
            body: `
                <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/credits/import" method="POST" enctype="multipart/form-data" id="csv-import-form" data-no-ajax style="padding: 10px 0;">
                    <?php echo Security::csrfField(); ?>
                    <div class="csv-modal-info" style="margin-bottom:15px; font-size:13px; line-height:1.5; color:rgba(15,23,42,.8);">
                        Import multiple credit records using our standard CSV format. Make sure the headers match the sample template.
                        <br><br>
                        <a href="javascript:void(0)" id="download-sample-btn" style="color:var(--primary); font-weight:bold; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">
                            <span class="material-symbols-rounded" style="font-size:18px;">download</span> Download Sample CSV Template
                        </a>
                    </div>
                    <label class="csv-dropzone" id="csv-modal-dropzone" style="display:flex; flex-direction:column; align-items:center; justify-content:center; border:2px dashed #cbd5e1; border-radius:12px; padding:30px 20px; cursor:pointer; transition:all 0.2s ease-in-out; text-align:center; background:#fafafa;">
                        <input type="file" name="file" accept=".csv" required style="display:none;" id="csv-modal-file-input">
                        <span class="material-symbols-rounded" style="font-size:42px; color:var(--primary); margin-bottom:10px;">upload_file</span>
                        <strong style="display:block; color:#334155; margin-bottom:4px;" id="dropzone-text-title">Drag & Drop CSV file here</strong>
                        <span class="page-subtitle" id="dropzone-text-subtitle">or click to browse files</span>
                    </label>
                    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                        <button class="btn btn-secondary" type="button" data-modal-close>Cancel</button>
                        <button class="btn btn-primary" type="submit" id="submit-import-btn" disabled>
                            <span class="material-symbols-rounded">upload</span>Upload & Import
                        </button>
                    </div>
                </form>
            `
        });

        const form = document.getElementById('csv-import-form');
        const dropzone = document.getElementById('csv-modal-dropzone');
        const fileInput = document.getElementById('csv-modal-file-input');
        const submitBtn = document.getElementById('submit-import-btn');
        const titleText = document.getElementById('dropzone-text-title');
        const subtext = document.getElementById('dropzone-text-subtitle');
        const sampleBtn = document.getElementById('download-sample-btn');

        if (!dropzone || !fileInput || !submitBtn) return;

        sampleBtn.addEventListener('click', () => {
            const csvContent = "Invoice,Customer,Phone,Total,Paid,Credit Due,Due Date,Date,Notes\\nINV-2026-0002,Ahmad Yasin,03009876543,12000,4000,8000,2026-08-16,2026-07-16,Pending partial payment";
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.setAttribute("download", "credits_import_template.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        fileInput.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            if (file) {
                titleText.textContent = file.name;
                subtext.textContent = (file.size / 1024).toFixed(2) + " KB";
                submitBtn.removeAttribute('disabled');
                dropzone.style.borderColor = "var(--primary)";
                dropzone.style.background = "rgba(15,109,255,.03)";
            }
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzone.style.borderColor = "var(--primary)";
                dropzone.style.background = "rgba(15,109,255,.05)";
                titleText.textContent = "Drop the CSV file here";
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                if (eventName === 'dragleave') {
                    titleText.textContent = fileInput.files?.[0] ? fileInput.files[0].name : "Drag & Drop CSV file here";
                    dropzone.style.borderColor = "#cbd5e1";
                    dropzone.style.background = "#fafafa";
                }
            });
        });

        dropzone.addEventListener('drop', (e) => {
            const file = e.dataTransfer?.files?.[0];
            if (file && file.name.endsWith('.csv')) {
                const transfer = new DataTransfer();
                transfer.items.add(file);
                fileInput.files = transfer.files;
                fileInput.dispatchEvent(new Event('change'));
            } else {
                window.appToast?.('Only CSV files are accepted.', 'error');
            }
        });
    };
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
