<?php ob_start(); ?>

<style>
    .customers-tabs {
        display:flex;
        gap:10px;
        margin-bottom:18px;
        flex-wrap:wrap;
    }

    .customers-tab-btn {
        border:1px solid var(--border);
        background:#fff;
        color:#0f172a;
        border-radius:12px;
        padding:10px 14px;
        font-weight:700;
        cursor:pointer;
        transition:all .2s ease;
    }

    .customers-tab-btn:hover {
        border-color:#bfdbfe;
        box-shadow:0 8px 18px rgba(15,109,255,.10);
    }

    .customers-tab-btn.active {
        background:linear-gradient(135deg, #1d4ed8, #0f6dff);
        color:#fff;
        border-color:#1d4ed8;
    }

    .customers-tab-pane { display:none; }
    .customers-tab-pane.active { display:block; }

    .customer-add-card {
        padding: 0;
        overflow: hidden;
        border-radius: 22px;
    }

    .customer-form-shell {
        display:grid;
        grid-template-columns:1fr;
        gap:14px;
    }

    .customer-col {
        border:1px solid var(--border);
        border-radius:16px;
        background:linear-gradient(150deg, #fff, #f8fbff);
        padding:22px;
    }

    .customer-col-title {
        display:flex;
        align-items:center;
        gap:10px;
        font-size:16px;
        font-weight:800;
        color:#0f172a;
        margin-bottom:16px;
    }

    .customer-col-title .material-symbols-rounded {
        width:42px;
        height:42px;
        border-radius:14px;
        display:grid;
        place-items:center;
        background:#eef4ff;
        color:var(--primary);
    }

    .customer-col .form-group { margin-bottom:14px; }
    .customer-col .form-group:last-child { margin-bottom:0; }

    .form-hint {
        font-size:12px;
        color:var(--text-muted);
        font-weight:600;
    }

    .customer-add-aside {
        display:none;
    }

    .customer-add-submit {
        margin-top:16px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        flex-wrap:wrap;
        gap:10px;
    }

    @media (min-width: 920px) {
        .customer-add-card {
            background:
                linear-gradient(90deg, #ffffff 0%, #ffffff 64%, transparent 64%),
                linear-gradient(145deg, #0f172a, #172554);
            box-shadow: 0 22px 52px rgba(15,23,42,.10);
        }

        .customer-form-shell {
            grid-template-columns: minmax(0, 1.35fr) minmax(310px, .75fr);
            gap: 0;
            align-items: stretch;
        }

        .customer-col {
            border: 0;
            border-radius: 0;
            background:
                radial-gradient(circle at top right, rgba(15,109,255,.08), transparent 34%),
                #fff;
            padding: 30px;
        }

        .customer-add-form-grid {
            display:grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap:16px;
        }

        .customer-add-form-grid .full {
            grid-column:1 / -1;
        }

        .customer-add-aside {
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            gap:22px;
            padding:30px;
            color:#fff;
            background:
                radial-gradient(circle at top right, rgba(34,197,94,.24), transparent 35%),
                linear-gradient(145deg, #0f172a, #172554);
        }

        .customer-add-aside-icon {
            width:58px;
            height:58px;
            border-radius:18px;
            display:grid;
            place-items:center;
            background:rgba(255,255,255,.12);
            box-shadow:0 18px 34px rgba(2,6,23,.22);
            margin-bottom:16px;
        }

        .customer-add-aside-icon .material-symbols-rounded {
            font-size:31px;
        }

        .customer-add-aside h3 {
            font-size:22px;
            margin-bottom:8px;
        }

        .customer-add-aside p {
            color:rgba(255,255,255,.72);
            line-height:1.65;
            margin:0;
        }

        .customer-add-points {
            display:grid;
            gap:10px;
        }

        .customer-add-points span {
            display:flex;
            align-items:center;
            gap:9px;
            color:rgba(255,255,255,.86);
            font-size:13px;
            font-weight:800;
        }

        .customer-add-points .material-symbols-rounded {
            color:#86efac;
            font-size:18px;
        }

        .customer-add-submit {
            margin-top:22px;
            padding-top:18px;
            border-top:1px solid #e2e8f0;
        }

        .customer-add-submit .btn {
            min-width:170px;
        }
    }

    @media (max-width: 720px) {
        .customer-add-card {
            border-radius: 18px;
        }

        .customer-col {
            padding: 18px;
            border-radius: 18px;
        }

        .customer-add-submit {
            display: grid !important;
            grid-template-columns: 1fr !important;
            justify-items: end !important;
            align-items: start !important;
            width: 100% !important;
            gap: 12px !important;
            overflow: visible !important;
            padding-bottom: 0 !important;
        }

        .customer-add-submit .form-hint {
            justify-self: stretch !important;
            max-width: none;
            line-height: 1.45;
        }

        .customer-add-submit .btn {
            flex: 0 0 auto !important;
            width: auto !important;
            min-width: 146px !important;
            max-width: 190px !important;
            min-height: 42px !important;
            padding: 9px 14px !important;
            white-space: nowrap !important;
        }
    }

    @media (max-width: 380px) {
        .customer-add-submit .btn {
            min-width: 136px !important;
            font-size: 12px !important;
            padding: 8px 12px !important;
        }
    }
</style>

<div class="page-header">
    <div>
        <h1>Customers</h1>
        <p class="page-subtitle">Manage customer records, purchase history, pending dues and receipt access.</p>
    </div>
    <div class="mobile-stack-flex" style="display:flex; gap:10px;">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/export" class="btn btn-secondary compact-single-action"><span class="material-symbols-rounded">download</span>Export CSV</a>
    </div>
</div>

<div class="customers-tabs" role="tablist" aria-label="Customers Tabs">
    <button type="button" class="customers-tab-btn active" data-customers-tab="add">Add Customer</button>
    <button type="button" class="customers-tab-btn" data-customers-tab="list">Customer List</button>
    <button type="button" class="customers-tab-btn" data-customers-tab="import">Import CSV</button>
</div>

<section class="customers-tab-pane active" data-customers-pane="add">
    <div class="card customer-add-card">
        <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/create" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <div class="customer-form-shell">
                <div class="customer-col">
                    <div class="customer-col-title"><span class="material-symbols-rounded">person_add</span>Customer Details</div>
                    <div class="customer-add-form-grid">
                        <div class="form-group">
                            <label class="form-label">Customer Name</label>
                            <div class="field-icon-wrap">
                                <span class="material-symbols-rounded">person</span>
                                <input class="form-input" type="text" name="name" required placeholder="Enter full name">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <div class="field-icon-wrap">
                                <span class="material-symbols-rounded">call</span>
                                <input class="form-input" type="text" name="phone" required placeholder="Enter phone number">
                            </div>
                        </div>
                        <?php if (!empty($hasAddress)): ?>
                        <div class="form-group full">
                            <label class="form-label">Address</label>
                            <div class="field-icon-wrap">
                                <span class="material-symbols-rounded">home</span>
                                <input class="form-input" type="text" name="address" placeholder="Customer address">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="customer-add-submit">
                        <span class="form-hint">Customer will be available instantly in POS checkout and credit tracking.</span>
                        <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>Save Customer</button>
                    </div>
                </div>
                <div class="customer-add-aside">
                    <div>
                        <div class="customer-add-aside-icon"><span class="material-symbols-rounded">verified_user</span></div>
                        <h3>Build a clean customer profile</h3>
                        <p>Save contact details once, then track receipts, pending balances, and WhatsApp reminders from the customer profile.</p>
                    </div>
                    <div class="customer-add-points">
                        <span><span class="material-symbols-rounded">receipt_long</span>Purchase history stays linked</span>
                        <span><span class="material-symbols-rounded">payments</span>Credit dues stay visible</span>
                        <span><span class="material-symbols-rounded">chat</span>WhatsApp reminders stay ready</span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<section class="customers-tab-pane" data-customers-pane="list">
    <form method="GET" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/" class="card" style="margin-bottom:18px;">
        <input type="hidden" name="url" value="customers/index">
        <div class="filter-grid" style="grid-template-columns:2fr auto;">
            <div class="field-icon-wrap">
                <span class="material-symbols-rounded">search</span>
                <input class="form-input" type="text" name="q" value="<?php echo Security::escape($query ?? ''); ?>" placeholder="Search customer by name or phone" data-live-search>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn btn-secondary" type="submit">Search</button>
                <button class="btn btn-ghost" type="button" data-clear-filters>Reset</button>
            </div>
        </div>
    </form>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone / Address</th>
                    <th>Total Spending</th>
                    <th>Remaining Due</th>
                    <th>Joined</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="6" class="empty-state">No customers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/viewProfile/<?php echo $customer['id']; ?>"><strong><?php echo Security::escape($customer['name']); ?></strong></a></td>
                            <td>
                                <?php echo Security::escape($customer['phone']); ?>
                                <?php if (!empty($customer['customer_address'])): ?>
                                    <div class="page-subtitle" style="margin-top:6px;"><?php echo Security::escape($customer['customer_address']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo App::money($customer['total_spending']); ?></td>
                            <td>
                                <?php if ((float) ($customer['outstanding_due'] ?? 0) > 0): ?>
                                    <span style="color:var(--error); font-weight:700;"><?php echo App::money($customer['outstanding_due']); ?></span>
                                <?php else: ?>
                                    <span style="color:var(--success); font-weight:700;">0</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="icon-btn" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/viewProfile/<?php echo $customer['id']; ?>" title="Profile"><span class="material-symbols-rounded">visibility</span></a>
                                    <a class="icon-btn" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/edit/<?php echo $customer['id']; ?>" title="Edit"><span class="material-symbols-rounded">edit</span></a>
                                    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/delete/<?php echo (int) $customer['id']; ?>" method="POST" data-no-ajax>
                                        <?php echo Security::csrfField(); ?>
                                        <button class="icon-btn" type="submit" title="Delete"><span class="material-symbols-rounded">delete_forever</span></button>
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

<style>
/* ── Dropzone & Import Styling ────────────────── */
.import-dropzone {
    border: 2px dashed #cbd5e1;
    border-radius: 18px;
    padding: 38px 24px;
    text-align: center;
    background: #f8fafc;
    margin-bottom: 22px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}
.import-dropzone:hover, .import-dropzone.dragover {
    border-color: #2563eb;
    background: #eff6ff;
}
.dropzone-icon-box {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    display: grid;
    place-items: center;
    margin: 0 auto 14px auto;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
}
.selected-file-card {
    background: #ffffff;
    border: 1.5px solid #10b981;
    border-radius: 14px;
    padding: 14px 18px;
    display: inline-flex;
    align-items: center;
    gap: 14px;
    text-align: left;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.08);
}
.selected-file-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: #ecfdf5;
    display: grid;
    place-items: center;
    flex-shrink: 0;
}
.selected-file-details {
    flex: 1;
    min-width: 0;
}
.btn-remove-file {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-remove-file:hover {
    background: #fee2e2;
    color: #ef4444;
    border-color: #fecaca;
}

/* ── Toggle Switch Card ─────────────────────── */
.import-toggle-card {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}
.toggle-card-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.toggle-card-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #eff6ff;
    display: grid;
    place-items: center;
    flex-shrink: 0;
}
.toggle-badge {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 6px;
    letter-spacing: 0.5px;
}
.toggle-badge.enabled {
    background: #dcfce7;
    color: #15803d;
}
.toggle-badge.disabled {
    background: #f1f5f9;
    color: #64748b;
}

/* ── Switch Slider Control ───────────────────── */
.switch-control {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
    flex-shrink: 0;
}
.switch-control input {
    opacity: 0;
    width: 0;
    height: 0;
}
.slider-round {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 34px;
}
.slider-round:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.switch-control input:checked + .slider-round {
    background-color: #2563eb;
}
.switch-control input:checked + .slider-round:before {
    transform: translateX(22px);
}
</style>

<section class="customers-tab-pane" data-customers-pane="import">
    <div class="card" style="padding:30px; border-radius:20px; box-shadow:0 4px 20px rgba(15,23,42,0.04);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px; padding-bottom:18px; border-bottom:1px solid #f1f5f9;">
            <div>
                <h3 style="font-size:19px; font-weight:800; margin:0 0 6px 0; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-rounded" style="color:#2563eb; font-size:24px;">upload_file</span> Bulk Customer Import
                </h3>
                <p style="color:#64748b; margin:0; font-size:13px;">Upload customer contacts, names, addresses and opening balances from a CSV file.</p>
            </div>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/sampleTemplate" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; font-weight:700; height:40px; padding:0 16px; border-radius:10px;">
                <span class="material-symbols-rounded" style="font-size:18px; color:#2563eb;">download</span> Download Sample Template (.csv)
            </a>
        </div>

        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/import" method="POST" enctype="multipart/form-data" data-no-ajax id="customerImportForm">
            <?php echo Security::csrfField(); ?>
            
            <!-- Hidden Native File Input -->
            <input type="file" name="file" id="customerCsvFileInput" accept=".csv" required style="display: none;" onchange="handleCustomerFileSelect(this)">

            <!-- Custom Drag-and-Drop Dropzone -->
            <div id="customerDropzone" class="import-dropzone" onclick="document.getElementById('customerCsvFileInput').click()" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
                
                <div id="dropzoneEmptyState">
                    <div class="dropzone-icon-box">
                        <span class="material-symbols-rounded" style="font-size:32px; color:#2563eb;">cloud_upload</span>
                    </div>
                    <div style="font-size:15.5px; font-weight:800; color:#0f172a; margin-bottom:4px;">
                        Click to browse or drag & drop CSV file
                    </div>
                    <p style="font-size:13px; color:#64748b; margin:0 0 14px 0;">
                        Support standard CSV format with UTF-8 encoding
                    </p>
                    <div style="display:inline-flex; align-items:center; gap:8px; background:#eff6ff; border:1px solid #dbeafe; color:#1d4ed8; padding:6px 14px; border-radius:999px; font-size:12px; font-weight:700;">
                        <span class="material-symbols-rounded" style="font-size:15px;">view_column</span> Expected Columns: Name, Phone, Address, Total_Spending
                    </div>
                </div>

                <!-- Selected File State -->
                <div id="dropzoneSelectedState" style="display: none;">
                    <div class="selected-file-card" onclick="event.stopPropagation()">
                        <div class="selected-file-icon">
                            <span class="material-symbols-rounded" style="font-size:26px; color:#10b981;">description</span>
                        </div>
                        <div class="selected-file-details">
                            <strong id="selectedFileName" style="display:block; font-size:14px; color:#0f172a; font-weight:800; word-break:break-all;">customer_data.csv</strong>
                            <small id="selectedFileSize" style="display:block; font-size:12px; color:#64748b; margin-top:2px;">Ready for upload</small>
                        </div>
                        <button type="button" class="btn-remove-file" onclick="removeCustomerSelectedFile(event)" title="Remove and choose different file">
                            <span class="material-symbols-rounded" style="font-size:18px;">close</span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- Modern Toggle Switch Card -->
            <div class="import-toggle-card">
                <div class="toggle-card-left">
                    <div class="toggle-card-icon">
                        <span class="material-symbols-rounded" style="color:#2563eb; font-size:22px;">sync</span>
                    </div>
                    <div>
                        <strong style="display:block; font-size:13.5px; color:#0f172a; font-weight:800;">
                            Auto-Update Existing Customers
                        </strong>
                        <span style="display:block; font-size:12px; color:#64748b; margin-top:2px;">
                            If a customer with the same phone number already exists, update their name and address instead of creating duplicate records.
                        </span>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span id="toggleStatusLabel" class="toggle-badge enabled">Enabled</span>
                    <label class="switch-control">
                        <input type="checkbox" name="update_existing" id="updateExistingToggle" value="1" checked onchange="updateToggleStatus(this)">
                        <span class="slider-round"></span>
                    </label>
                </div>
            </div>

            <!-- Action Button -->
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px;">
                <button type="submit" class="btn btn-primary" style="font-weight:800; height:44px; padding:0 24px; border-radius:12px; display:inline-flex; align-items:center; gap:8px; font-size:13.5px; box-shadow:0 4px 14px rgba(37,99,235,0.2);">
                    <span class="material-symbols-rounded" style="font-size:18px;">upload</span> Upload & Import Customers
                </button>
            </div>
        </form>
    </div>
</section>

<?php ob_start(); ?>
<script>
    function handleCustomerFileSelect(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            document.getElementById('selectedFileName').textContent = file.name;
            const sizeInKb = (file.size / 1024).toFixed(1);
            document.getElementById('selectedFileSize').textContent = `${sizeInKb} KB • Ready for import`;
            document.getElementById('dropzoneEmptyState').style.display = 'none';
            document.getElementById('dropzoneSelectedState').style.display = 'block';
        }
    }

    function removeCustomerSelectedFile(e) {
        if (e) e.stopPropagation();
        const input = document.getElementById('customerCsvFileInput');
        input.value = '';
        document.getElementById('dropzoneSelectedState').style.display = 'none';
        document.getElementById('dropzoneEmptyState').style.display = 'block';
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('customerDropzone').classList.add('dragover');
    }

    function handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('customerDropzone').classList.remove('dragover');
    }

    function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        const dropzone = document.getElementById('customerDropzone');
        dropzone.classList.remove('dragover');
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
            const input = document.getElementById('customerCsvFileInput');
            input.files = e.dataTransfer.files;
            handleCustomerFileSelect(input);
        }
    }

    function updateToggleStatus(checkbox) {
        const label = document.getElementById('toggleStatusLabel');
        if (checkbox.checked) {
            label.textContent = 'Enabled';
            label.className = 'toggle-badge enabled';
        } else {
            label.textContent = 'Disabled';
            label.className = 'toggle-badge disabled';
        }
    }

    (() => {
        const tabButtons = Array.from(document.querySelectorAll('[data-customers-tab]'));
        const panes = Array.from(document.querySelectorAll('[data-customers-pane]'));
        const tabKey = 'tab_' + (new URLSearchParams(window.location.search).get('url') || 'customers/index');

        const setTab = (tab) => {
            tabButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.customersTab === tab));
            panes.forEach((pane) => pane.classList.toggle('active', pane.dataset.customersPane === tab));
            sessionStorage.setItem(tabKey, tab);
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url.toString());
        };

        tabButtons.forEach((btn) => {
            btn.addEventListener('click', () => setTab(btn.dataset.customersTab));
        });

        const initialTab = sessionStorage.getItem(tabKey) || new URLSearchParams(window.location.search).get('tab');
        if (initialTab === 'add' || initialTab === 'list' || initialTab === 'import') {
            setTab(initialTab);
        }
    })();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>

