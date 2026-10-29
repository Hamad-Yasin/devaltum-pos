<?php ob_start(); ?>
<?php
$filters = $filters ?? [];
$activeDateFilter = $filters['dateFilter'] ?? 'today';
?>

<style>
    .expense-filter-card {
        padding: 14px 18px;
        margin-bottom: 18px;
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }

    .expense-filter-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .expense-filter-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }

    .expense-filter-title .material-symbols-rounded {
        width: 30px;
        height: 30px;
        display: grid;
        place-items: center;
        border-radius: 8px;
        background: #eff6ff;
        color: var(--primary);
        font-size: 18px;
    }

    .expense-filter-grid {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px 10px;
        width: 100%;
    }

    .expense-filter-grid .field-icon-wrap {
        position: relative;
        flex: 1 1 280px;
        min-width: 220px;
    }

    .expense-filter-grid .field-icon-wrap .material-symbols-rounded {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 18px;
        pointer-events: none;
    }

    .expense-filter-grid .field-icon-wrap .form-input {
        padding-left: 36px !important;
        width: 100% !important;
    }

    .expense-filter-grid .form-input,
    .expense-filter-grid .form-select {
        height: 38px !important;
        min-height: 38px !important;
        padding: 0 12px !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #334155 !important;
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        outline: none !important;
        box-shadow: none !important;
        transition: all 0.2s ease !important;
        flex: 0 1 auto;
        width: auto;
        min-width: 130px;
    }

    .expense-filter-grid .form-select {
        padding-right: 28px !important;
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 8px center !important;
        background-size: 14px 14px !important;
        appearance: none !important;
        -webkit-appearance: none !important;
    }

    .expense-filter-grid .form-input:focus,
    .expense-filter-grid .form-select:focus {
        background: #ffffff !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12) !important;
    }

    .expense-filter-grid .form-input:hover,
    .expense-filter-grid .form-select:hover {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
    }

    .expense-filter-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-left: 0;
    }

    .expense-filter-actions .btn-primary {
        height: 38px;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .expense-filter-actions .btn-primary .material-symbols-rounded {
        font-size: 16px;
    }

    .expense-filter-actions .btn-ghost {
        height: 38px;
        min-height: 38px;
        padding: 0 12px;
        font-size: 12.5px;
        font-weight: 600;
        color: #64748b;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
    }

    .expense-filter-actions .btn-ghost:hover {
        background: #fee2e2;
        color: #ef4444;
        border-color: #fca5a5;
    }

    .expense-preset-row {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        padding-top: 8px;
        border-top: 1px dashed #e2e8f0;
        margin-top: 4px;
    }

    .expense-preset-row .btn {
        height: 32px;
        min-height: 32px;
        padding: 0 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .expense-custom-range {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 3px 8px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
    }

    .expense-custom-range .form-input {
        height: 28px !important;
        min-height: 28px !important;
        padding: 0 6px !important;
        font-size: 11.5px !important;
        border-radius: 6px !important;
    }

    .expense-custom-range .btn {
        height: 28px;
        min-height: 28px;
        padding: 0 8px;
        font-size: 11.5px;
        border-radius: 6px;
    }

    .expense-custom-range[hidden] {
        display: none !important;
    }

    .expense-loading {
        pointer-events: none;
        opacity: .68;
        transition: opacity .18s ease;
    }

    @media (max-width: 768px) {
        .expense-filter-grid .field-icon-wrap {
            max-width: 100%;
            flex: 1 1 100%;
        }
        .expense-filter-grid .form-select,
        .expense-filter-grid .form-input {
            flex: 1 1 calc(50% - 6px);
            min-width: 110px;
        }
        .expense-filter-actions {
            margin-left: 0;
            width: 100%;
            justify-content: flex-end;
        }
    }

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
</style>

<div class="page-header">
    <div>
        <h1>Expenses</h1>
        <p class="page-subtitle">Add and track business expenses with clean tab workflow.</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-secondary" onclick="openImportModal()"><span class="material-symbols-rounded">upload</span>Import CSV</button>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/expenses/export?<?php echo http_build_query($_GET); ?>" class="btn btn-secondary"><span class="material-symbols-rounded">download</span>Export CSV</a>
    </div>
</div>

<form id="expenses-filter-form" method="GET" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/" class="card expense-filter-card">
    <input type="hidden" name="url" value="expenses/index">
    <input type="hidden" name="date_filter" value="<?php echo Security::escape($activeDateFilter); ?>">

    <div class="expense-filter-head">
        <div>
            <div class="expense-filter-title">
                <span class="material-symbols-rounded">tune</span>
                Smart Expense Filters
            </div>
            <p class="page-subtitle" style="margin-top:6px;">Filter results instantly without refreshing the page.</p>
        </div>
        <span class="badge badge-soft" data-expense-count><?php echo count($expenses); ?> records</span>
    </div>

    <div class="expense-filter-grid">
        <div class="field-icon-wrap">
            <span class="material-symbols-rounded">search</span>
            <input class="form-input" type="search" name="q" value="<?php echo Security::escape($filters['q'] ?? ''); ?>" placeholder="Search title, category or notes" data-expense-live>
        </div>

        <select class="form-select" name="category_id" data-expense-auto>
            <option value="0">All Categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo (int) $category['id']; ?>" <?php echo (int) ($filters['categoryId'] ?? 0) === (int) $category['id'] ? 'selected' : ''; ?>>
                    <?php echo Security::escape($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select class="form-select" name="method" data-expense-auto>
            <option value="">All Methods</option>
            <option value="cash" <?php echo ($filters['method'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash</option>
            <option value="online" <?php echo ($filters['method'] ?? '') === 'online' ? 'selected' : ''; ?>>Online</option>
        </select>

        <div class="expense-filter-actions">
            <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">filter_alt</span>Apply</button>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/expenses/index?url=expenses/index&date_filter=today" class="btn btn-ghost" data-expense-filter-reset>
                <span class="material-symbols-rounded" style="font-size:16px;">restart_alt</span>
                <span>Reset</span>
            </a>
        </div>

        <div class="expense-preset-row">
            <button type="button" data-date-filter="today" class="btn <?php echo $activeDateFilter === 'today' ? 'btn-primary' : 'btn-secondary'; ?>">Today</button>
            <button type="button" data-date-filter="this_week" class="btn <?php echo $activeDateFilter === 'this_week' ? 'btn-primary' : 'btn-secondary'; ?>">This Week</button>
            <button type="button" data-date-filter="this_month" class="btn <?php echo $activeDateFilter === 'this_month' ? 'btn-primary' : 'btn-secondary'; ?>">This Month</button>
            <button type="button" data-date-filter="custom" data-custom-date-toggle class="btn <?php echo $activeDateFilter === 'custom' ? 'btn-primary' : 'btn-secondary'; ?>">Custom Date</button>
            <div class="expense-custom-range" data-custom-date-range <?php echo $activeDateFilter === 'custom' ? '' : 'hidden'; ?>>
                <input class="form-input" type="date" name="start_date" value="<?php echo Security::escape($filters['from'] ?? ''); ?>" style="max-width:160px;">
                <span class="page-subtitle">to</span>
                <input class="form-input" type="date" name="end_date" value="<?php echo Security::escape($filters['to'] ?? ''); ?>" style="max-width:160px;">
                <button class="btn btn-secondary" type="submit">Apply Date</button>
            </div>
        </div>
    </div>
</form>

<div id="expenses-summary-cards" class="grid-cards" style="margin-bottom:20px;">
    <div class="card metric-card"><small>Total Expenses</small><strong><?php echo App::money($summary['total']); ?></strong></div>
    <div class="card metric-card"><small>Cash Paid</small><strong><?php echo App::money($summary['cash']); ?></strong></div>
    <div class="card metric-card"><small>Online Paid</small><strong><?php echo App::money($summary['online']); ?></strong></div>
    <div class="card metric-card"><small>This Month</small><strong><?php echo App::money($summary['month']); ?></strong></div>
</div>

<div class="card" style="margin-bottom:18px;">
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button type="button" class="btn btn-primary" data-expense-tab="add">Add Expense</button>
        <button type="button" class="btn btn-secondary" data-expense-tab="history">Expense History</button>
    </div>
</div>

<section class="expense-tab-panel" data-expense-panel="add">
    <div class="split-grid" style="margin-bottom:20px;">
        <section class="card">
            <h3 style="margin-bottom:16px;">Add Expense</h3>
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/expenses/create" method="POST" data-no-ajax>
                <?php echo Security::csrfField(); ?>
                <div class="form-group">
                    <label class="form-label">Expense Title</label>
                    <input class="form-input" type="text" name="title" required>
                </div>
                <div class="split-grid">
                    <div class="form-group">
                        <label class="form-label">Select Category</label>
                        <select class="form-select" name="category_id">
                            <option value="0">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int) $category['id']; ?>"><?php echo Security::escape($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Custom Category (optional)</label>
                        <input class="form-input" type="text" name="custom_category" placeholder="e.g. Courier">
                    </div>
                </div>
                <div class="split-grid">
                    <div class="form-group">
                        <label class="form-label">Amount</label>
                        <input class="form-input" type="number" step="0.01" min="0" name="amount" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method">
                            <option value="cash">Cash</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                </div>
                <div class="split-grid">
                    <div class="form-group">
                        <label class="form-label">Expense Date</label>
                        <input class="form-input" type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input class="form-input" type="text" name="notes" placeholder="Optional note">
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Save Expense</button>
            </form>
        </section>

        <section class="card">
            <h3 style="margin-bottom:16px;">Add Category</h3>
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/expenses/createCategory" method="POST" data-no-ajax style="margin-bottom:18px;">
                <?php echo Security::csrfField(); ?>
                <div class="form-group">
                    <label class="form-label">Category Name</label>
                    <input class="form-input" type="text" name="name" required>
                </div>
                <button class="btn btn-secondary" type="submit">Add Category</button>
            </form>

            <h4 style="margin-bottom:10px;">Current Categories</h4>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <?php foreach ($categories as $category): ?>
                    <span class="badge badge-soft"><?php echo Security::escape($category['name']); ?></span>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>

<section class="expense-tab-panel" data-expense-panel="history" style="display:none;">
    <div id="expenses-history-wrap" class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Payment</th>
                    <th>Amount</th>
                    <th>Notes</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                    <tr><td colspan="7" class="empty-state">No expense records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                            <td>
                                <strong><?php echo Security::escape($expense['title']); ?></strong>
                                <div class="page-subtitle" style="margin-top:4px;">By <?php echo Security::escape($expense['created_by_name'] ?: 'System'); ?></div>
                            </td>
                            <td><?php echo Security::escape($expense['category_name'] ?: 'Uncategorized'); ?></td>
                            <td><span class="badge <?php echo $expense['payment_method'] === 'cash' ? 'badge-success' : 'badge-soft'; ?>"><?php echo ucfirst(Security::escape($expense['payment_method'])); ?></span></td>
                            <td><?php echo App::money($expense['amount']); ?></td>
                            <td><?php echo Security::escape($expense['notes'] ?: '-'); ?></td>
                            <td style="text-align:right;">
                                <div class="table-actions" style="justify-content: flex-end;">
                                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/expenses/edit/<?php echo (int) $expense['id']; ?>" class="icon-btn" title="Edit Expense"><span class="material-symbols-rounded">edit</span></a>
                                    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/expenses/delete/<?php echo (int) $expense['id']; ?>" method="POST" data-no-ajax style="display:inline-block;">
                                        <?php echo Security::csrfField(); ?>
                                        <button class="icon-btn" type="submit" title="Delete Expense"><span class="material-symbols-rounded">delete_forever</span></button>
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

<?php ob_start(); ?>
<script>
    (() => {
        const tabButtons = document.querySelectorAll('[data-expense-tab]');
        const panels = document.querySelectorAll('[data-expense-panel]');
        const searchParams = new URLSearchParams(window.location.search);
        const tabKey = 'tab_' + (searchParams.get('url') || 'expenses/index');
        const hasFilters = searchParams.has('date_filter') || searchParams.has('method');
        const activate = (tab) => {
            sessionStorage.setItem(tabKey, tab);
            tabButtons.forEach((btn) => {
                const active = btn.dataset.expenseTab === tab;
                btn.classList.toggle('btn-primary', active);
                btn.classList.toggle('btn-secondary', !active);
            });
            panels.forEach((panel) => {
                panel.style.display = panel.dataset.expensePanel === tab ? '' : 'none';
            });
        };
        tabButtons.forEach((btn) => btn.addEventListener('click', () => activate(btn.dataset.expenseTab)));
        const saved = sessionStorage.getItem(tabKey);
        activate(hasFilters ? 'history' : (saved || 'add'));

        const filterForm = document.getElementById('expenses-filter-form');
        const summaryNode = document.getElementById('expenses-summary-cards');
        const historyNode = document.getElementById('expenses-history-wrap');
        if (!filterForm || !summaryNode || !historyNode) {
            return;
        }
        let filterTimer = null;

        const buildQueryString = (submitterValue = null) => {
            const formData = new FormData(filterForm);
            if (submitterValue) {
                formData.set('date_filter', submitterValue);
            }
            return new URLSearchParams(formData).toString();
        };

        const refreshByAjax = async (queryString, pushState = true) => {
            const requestUrl = `${filterForm.action}?${queryString}`;
            filterForm.classList.add('expense-loading');
            historyNode.classList.add('expense-loading');
            summaryNode.classList.add('expense-loading');
            try {
                const response = await fetch(requestUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) {
                    throw new Error('request failed');
                }
                const contentType = response.headers.get('content-type') || '';
                let html = '';
                if (contentType.includes('application/json')) {
                    const data = await response.json();
                    if (data.error) {
                        throw new Error(data.message || 'request failed');
                    }
                    html = data.html || '';
                } else {
                    html = await response.text();
                }
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const newForm = doc.getElementById('expenses-filter-form');
                const newSummary = doc.getElementById('expenses-summary-cards');
                const newHistory = doc.getElementById('expenses-history-wrap');
                if (!newForm || !newSummary || !newHistory) {
                    throw new Error('partial nodes missing');
                }
                filterForm.innerHTML = newForm.innerHTML;
                summaryNode.innerHTML = newSummary.innerHTML;
                historyNode.innerHTML = newHistory.innerHTML;
                if (pushState) {
                    window.history.replaceState({}, '', requestUrl);
                }
                activate('history');
                if (window.appInitTablePagination) {
                    window.appInitTablePagination(historyNode);
                }
            } catch (error) {
                window.location.href = requestUrl;
            } finally {
                filterForm.classList.remove('expense-loading');
                historyNode.classList.remove('expense-loading');
                summaryNode.classList.remove('expense-loading');
            }
        };

        filterForm.addEventListener('click', (event) => {
            const filterBtn = event.target.closest('[data-date-filter]');
            if (filterBtn) {
                event.preventDefault();
                event.stopPropagation();
                const filterValue = filterBtn.getAttribute('data-date-filter') || 'today';
                const range = filterForm.querySelector('[data-custom-date-range]');
                if (filterValue === 'custom' && range && range.hidden) {
                    range.hidden = false;
                    filterBtn.classList.add('btn-primary');
                    filterBtn.classList.remove('btn-secondary');
                    const hiddenFilter = filterForm.querySelector('input[name="date_filter"]');
                    if (hiddenFilter) {
                        hiddenFilter.value = 'custom';
                    }
                    range.querySelector('input[type="date"]')?.focus();
                    return;
                }
                const hiddenFilter = filterForm.querySelector('input[name="date_filter"]');
                if (hiddenFilter) {
                    hiddenFilter.value = filterValue;
                }
                refreshByAjax(buildQueryString(filterValue));
                return;
            }

            const reset = event.target.closest('[data-expense-filter-reset]');
            if (!reset) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            const href = reset.getAttribute('href') || `${filterForm.action}?url=expenses/index&date_filter=today`;
            const query = href.includes('?') ? href.split('?')[1] : 'url=expenses/index&date_filter=today';
            refreshByAjax(query);
        });

        filterForm.addEventListener('input', (event) => {
            if (!event.target.matches('[data-expense-live]')) {
                return;
            }
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => refreshByAjax(buildQueryString()), 300);
        });

        filterForm.addEventListener('change', (event) => {
            if (!event.target.matches('[data-expense-auto], [data-custom-date-range] input')) {
                return;
            }
            const range = filterForm.querySelector('[data-custom-date-range]');
            const hiddenFilter = filterForm.querySelector('input[name="date_filter"]');
            if (range && range.contains(event.target) && hiddenFilter) {
                hiddenFilter.value = 'custom';
                const dates = Array.from(range.querySelectorAll('input[type="date"]'));
                if (dates.some((input) => !input.value)) {
                    return;
                }
            }
            refreshByAjax(buildQueryString());
        });

        filterForm.addEventListener('submit', (event) => {
            event.preventDefault();
            event.stopImmediatePropagation();
            refreshByAjax(buildQueryString());
        }, true);

    })();

    window.openImportModal = () => {
        window.appModal.open({
            title: 'Import Expenses from CSV',
            body: `
                <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/expenses/import" method="POST" enctype="multipart/form-data" id="csv-import-form" data-no-ajax style="padding: 10px 0;">
                    <?php echo Security::csrfField(); ?>
                    <div class="csv-modal-info" style="margin-bottom:15px; font-size:13px; line-height:1.5; color:rgba(15,23,42,.8);">
                        Import multiple expense records using our standard CSV format. Make sure the headers match the sample template.
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
            const csvContent = "Title,Category,Amount,Method,Date,Notes\\nOffice Utilities,Utilities,4500,cash,2026-07-16,Electricity bill paid";
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.setAttribute("download", "expenses_import_template.csv");
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
