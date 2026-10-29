<?php ob_start(); ?>
<?php $showSalesDateRange = ($filters['datePreset'] ?? '') === 'custom'; ?>

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
        <h1>Sales</h1>
        <p class="page-subtitle">Clean sales list. Open any sale in profile view for full details and actions.</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-secondary" onclick="openImportModal()"><span class="material-symbols-rounded">upload</span>Import CSV</button>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/export?<?php echo http_build_query($_GET); ?>"
            class="btn btn-secondary"><span class="material-symbols-rounded">download</span>Export CSV</a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/pos/index" class="btn btn-primary compact-single-action"><span
                class="material-symbols-rounded">add_shopping_cart</span>New Checkout</a>
    </div>
</div>

<form method="GET" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/" class="card filter-card" style="margin-bottom:18px;">
    <input type="hidden" name="url" value="sales/index">
    <div class="filter-grid">
        <div class="field-icon-wrap">
            <span class="material-symbols-rounded">search</span>
            <input class="form-input" type="text" name="q" value="<?php echo Security::escape($filters['q'] ?? ''); ?>"
                placeholder="Search invoice or customer" data-live-search>
        </div>
        <select class="form-select" name="type" data-live-search>
            <option value="">All Types</option>
            <?php foreach (['Sale', 'Exchange', 'Return', 'Refund'] as $type): ?>
            <option value="<?php echo $type; ?>" <?php echo ($filters['type'] ?? '') === $type ? 'selected' : ''; ?>>
                <?php echo $type; ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-select" name="status" data-live-search>
            <option value="">All Status</option>
            <option value="paid" <?php echo ($filters['status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
            <option value="unpaid" <?php echo ($filters['status'] ?? '') === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
            <option value="draft" <?php echo ($filters['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
        </select>
        <select class="form-select" name="payment_method" data-live-search>
            <option value="">All Payments</option>
            <option value="cash" <?php echo ($filters['paymentMethod'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash
            </option>
            <option value="online" <?php echo ($filters['paymentMethod'] ?? '') === 'online' ? 'selected' : ''; ?>>
                Online</option>
            <option value="credit" <?php echo ($filters['paymentMethod'] ?? '') === 'credit' ? 'selected' : ''; ?>>
                Credit</option>
        </select>
        <select class="form-select" name="sale_channel" data-live-search>
            <option value="">Retail + Wholesale</option>
            <option value="retail" <?php echo ($filters['saleChannel'] ?? '') === 'retail' ? 'selected' : ''; ?>>Retail
            </option>
            <option value="wholesale" <?php echo ($filters['saleChannel'] ?? '') === 'wholesale' ? 'selected' : ''; ?>>
                Wholesale</option>
        </select>
        <?php if (!empty($isOwnerView)): ?>
        <select class="form-select" name="staff_id" data-live-search>
            <option value="">All Staff</option>
            <?php foreach (($staffOptions ?? []) as $staff): ?>
                <option value="<?php echo (int) $staff['id']; ?>" <?php echo (int) ($filters['staffId'] ?? 0) === (int) $staff['id'] ? 'selected' : ''; ?>>
                    <?php echo Security::escape($staff['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select class="form-select" name="date_preset" data-live-search data-custom-date-select data-custom-date-target="sales">
            <option value="" <?php echo ($filters['datePreset'] ?? '') === '' ? 'selected' : ''; ?>>All Dates</option>
            <option value="today" <?php echo ($filters['datePreset'] ?? '') === 'today' ? 'selected' : ''; ?>>Today</option>
            <option value="yesterday" <?php echo ($filters['datePreset'] ?? '') === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
            <option value="this_week" <?php echo ($filters['datePreset'] ?? '') === 'this_week' ? 'selected' : ''; ?>>This Week</option>
            <option value="this_month" <?php echo ($filters['datePreset'] ?? '') === 'this_month' ? 'selected' : ''; ?>>This Month</option>
            <option value="custom" <?php echo ($filters['datePreset'] ?? '') === 'custom' ? 'selected' : ''; ?>>Custom Dates</option>
        </select>
        <input class="form-input" type="date" name="date_from"
            value="<?php echo Security::escape($filters['dateFrom'] ?? ''); ?>" data-live-search data-custom-date-field="sales" <?php echo $showSalesDateRange ? '' : 'hidden disabled'; ?>>
        <input class="form-input" type="date" name="date_to"
            value="<?php echo Security::escape($filters['dateTo'] ?? ''); ?>" data-live-search data-custom-date-field="sales" <?php echo $showSalesDateRange ? '' : 'hidden disabled'; ?>>
        <button class="btn btn-ghost" type="button" data-clear-filters>
            <span class="material-symbols-rounded" style="font-size:16px;">restart_alt</span>
            <span>Reset</span>
        </button>
    </div>
</form>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Total Bill</th>
                <th>Date & Time</th>
                <th style="text-align:right;">View</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sales)): ?>
            <tr>
                <td colspan="5" class="empty-state">No sales found for the selected filters.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($sales as $sale): ?>
            <?php
                        $openDue = max(0, max(((float) ($sale['total'] ?? 0) - (float) ($sale['paid_amount'] ?? 0)), (float) ($sale['balance_due'] ?? 0)));
                        $rawStatus = strtolower(trim((string) ($sale['status'] ?? '')));
                        if (!in_array($rawStatus, ['paid', 'unpaid', 'draft'], true)) {
                            $rawStatus = $openDue > 0 ? 'unpaid' : 'paid';
                        }
                        $displayStatus = $openDue > 0 ? 'UNPAID' : strtoupper($rawStatus);
                    ?>
            <tr>
                <td>
                    <strong><?php echo Security::escape($sale['custom_id']); ?></strong>
                    <div class="page-subtitle" style="margin-top:6px;">
                        <?php echo Security::escape($sale['transaction_type']); ?> | <?php echo $displayStatus; ?></div>
                </td>
                <td>
                    <?php echo Security::escape($sale['customer_display']); ?>
                    <div class="page-subtitle" style="margin-top:6px;">
                        <?php echo Security::escape($sale['customer_phone_display']); ?></div>
                </td>
                <td><strong><?php echo App::money((float) ($sale['total'] ?? 0)); ?></strong></td>
                <td><?php echo date('M d, Y h:i A', strtotime($sale['created_at'])); ?></td>
                <td style="text-align:right;">
                    <a class="icon-btn"
                        href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/action/<?php echo (int) $sale['id']; ?>"
                        title="Open Sale Profile"><span class="material-symbols-rounded">visibility</span></a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    (() => {
        window.openImportModal = () => {
            window.appModal.open({
                title: 'Import Sales from CSV',
                body: `
                    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/import" method="POST" enctype="multipart/form-data" id="csv-import-form" data-no-ajax style="padding: 10px 0;">
                        <?php echo Security::csrfField(); ?>
                        <div class="csv-modal-info" style="margin-bottom:15px; font-size:13px; line-height:1.5; color:rgba(15,23,42,.8);">
                            Import multiple sales records using our standard CSV format. 
                            <div style="margin-top:6px; padding:8px 12px; background:rgba(37,99,235,.07); border-left:3px solid var(--primary); border-radius:4px; font-size:12px; color:#1e293b;">
                                <strong>💡 Customer & Ledger Auto-Sync:</strong> Customer name, phone, aur address automatically match ya create ho jain ge. Customer ka total spending aur credit/khata balance bhi update ho jaye ga.
                            </div>
                            <br>
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
                const csvContent = "Invoice,Type,Status,Payment,Channel,Subtotal,Discount,Total,Customer,Phone,Address,Date\\nINV-2026-0001,Sale,paid,cash,retail,5000,200,4800,Ahmad Yasin,03001234567,Shop 12 Main Market Lahore,2026-07-16 12:00:00\\nINV-2026-0002,Sale,unpaid,credit,retail,3000,0,3000,Usman Ali,03219876543,Model Town Block C,2026-07-16 14:30:00";
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.setAttribute("download", "sales_import_template.csv");
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
    })();
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
