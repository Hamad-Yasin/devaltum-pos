<?php ob_start(); ?>
<?php
$money = static function ($value) {
    return App::money((float) $value);
};
$selectedSet = array_flip(array_map('intval', $selectedStaffIds ?? []));
$selectedCount = count($selectedStaffIds ?? []);
$staffCount = count($staffOptions ?? []);
$roleCounts = ['store_owner' => 0, 'admin' => 0, 'staff' => 0];
foreach (($staffOptions ?? []) as $person) {
    $role = $person['role'] ?? 'staff';
    $roleCounts[$role] = ($roleCounts[$role] ?? 0) + 1;
}
$maxTrend = max(1, max(array_map(static function ($row) {
    return (float) ($row['total'] ?? 0);
}, $dailyTrend ?? [['total' => 0]])));
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
$filterLabel = ucfirst(str_replace('_', ' ', $dateFilter ?? 'today'));
?>

<style>
.staff-analytics-page { padding-bottom:16px; }
.staff-command {
    border:1px solid rgba(15,109,255,.18);
    border-radius:18px;
    padding:18px;
    background:linear-gradient(135deg, rgba(15,109,255,.10), rgba(16,185,129,.08), #fff);
    box-shadow:0 14px 34px rgba(15,23,42,.07);
    margin-bottom:16px;
}
.staff-command-top { display:flex; justify-content:space-between; align-items:flex-start; gap:14px; flex-wrap:wrap; }
.staff-badges { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.staff-badge {
    display:inline-flex;
    align-items:center;
    gap:6px;
    border-radius:999px;
    padding:7px 10px;
    font-size:12px;
    font-weight:800;
    border:1px solid #dbeafe;
    color:#1e40af;
    background:#eff6ff;
}
.staff-badge.success { border-color:#bbf7d0; color:#047857; background:#ecfdf5; }
.staff-badge.dark { border-color:#cbd5e1; color:#0f172a; background:#f8fafc; }
.staff-filter-card {
    background:#fff;
    border:1px solid var(--border);
    border-radius:16px;
    padding:12px;
    margin-bottom:16px;
    box-shadow:var(--shadow-sm);
}
.staff-filter-row { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.staff-filter-row .btn,
.staff-filter-row .form-input {
    min-height:42px;
    padding:9px 14px;
    border-radius:14px;
    font-size:14px;
    font-weight:800;
}
.staff-date-btn {
    border-color:#dbeafe;
    background:#fff;
    color:#0f3b73;
}
.staff-date-btn.btn-primary {
    color:#fff;
    background:linear-gradient(135deg, #0f6dff, #2563eb);
    border-color:#0f6dff;
    box-shadow:0 8px 18px rgba(15,109,255,.20);
}
.staff-custom-toggle {
    border-color:#bfdbfe;
    color:#075985;
    background:#f8fbff;
}
.staff-custom-fields {
    display:none;
    gap:8px;
    align-items:center;
    flex-wrap:wrap;
}
.staff-custom-fields.active { display:flex; }
.staff-custom-fields .form-input {
    width:150px;
    min-width:145px;
}
.staff-apply-btn {
    color:#fff;
    border-color:#047857;
    background:linear-gradient(135deg, #059669, #0f766e);
    box-shadow:0 10px 22px rgba(5,150,105,.20);
}
.staff-apply-btn:hover { filter:brightness(.98); }
.staff-filter-actions {
    display:flex;
    justify-content:flex-end;
    gap:8px;
    margin-top:12px;
}
.staff-picker {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(230px, 1fr));
    gap:10px;
    margin-top:12px;
}
.staff-toggle {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    border:1px solid #e2e8f0;
    border-radius:14px;
    padding:11px;
    background:#fff;
    cursor:pointer;
    min-width:0;
    transition:.18s ease;
}
.staff-toggle:hover { border-color:#93c5fd; box-shadow:0 8px 18px rgba(15,23,42,.05); }
.staff-toggle input { position:absolute; opacity:0; pointer-events:none; }
.staff-toggle-main { display:flex; align-items:center; gap:10px; min-width:0; }
.staff-avatar {
    width:38px;
    height:38px;
    border-radius:13px;
    display:grid;
    place-items:center;
    background:#dbeafe;
    color:#1d4ed8;
    font-weight:900;
    flex:0 0 38px;
}
.staff-toggle-text { min-width:0; }
.staff-toggle strong { display:block; font-size:13px; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.staff-toggle small { display:block; font-size:11px; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
.role-pill {
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:3px 8px;
    font-size:10px;
    font-weight:900;
    text-transform:uppercase;
    color:#475569;
    background:#f1f5f9;
    margin-top:6px;
}
.role-pill.owner { color:#7c2d12; background:#ffedd5; }
.role-pill.admin { color:#1e3a8a; background:#dbeafe; }
.switch-ui {
    width:42px;
    height:24px;
    border-radius:999px;
    background:#cbd5e1;
    padding:3px;
    flex:0 0 42px;
    transition:.18s ease;
}
.switch-ui::after {
    content:"";
    display:block;
    width:18px;
    height:18px;
    border-radius:50%;
    background:#fff;
    box-shadow:0 2px 5px rgba(15,23,42,.18);
    transition:.18s ease;
}
.staff-toggle input:checked + .staff-toggle-main + .switch-ui { background:#0f6dff; }
.staff-toggle input:checked + .staff-toggle-main + .switch-ui::after { transform:translateX(18px); }
.staff-toggle:has(input:checked) { border-color:#60a5fa; background:#f8fbff; }
.analytics-tabs { margin-top:4px; }
.analytics-tabs > input { position:absolute; opacity:0; pointer-events:none; }
.tab-nav {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-bottom:16px;
    padding:8px;
    border:1px solid #dbeafe;
    border-radius:16px;
    background:linear-gradient(135deg, #f8fbff, #eef6ff);
    box-shadow:0 10px 24px rgba(15,23,42,.05);
}
.tab-nav label {
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:10px 13px;
    border-radius:12px;
    color:#64748b;
    font-weight:900;
    cursor:pointer;
    border:1px solid transparent;
    background:rgba(255,255,255,.58);
    transition:.18s ease;
}
.tab-nav label:hover {
    color:#0f3b73;
    border-color:#bfdbfe;
    background:#fff;
}
#tab-overview:checked ~ .tab-nav label[for="tab-overview"],
#tab-staff:checked ~ .tab-nav label[for="tab-staff"],
#tab-products:checked ~ .tab-nav label[for="tab-products"],
#tab-recent:checked ~ .tab-nav label[for="tab-recent"] {
    color:#fff;
    border-color:#0f6dff;
    background:linear-gradient(135deg, #0f6dff, #2563eb);
    box-shadow:0 10px 22px rgba(15,109,255,.24);
}
.tab-panel { display:none; }
#tab-overview:checked ~ .tab-panels .panel-overview,
#tab-staff:checked ~ .tab-panels .panel-staff,
#tab-products:checked ~ .tab-panels .panel-products,
#tab-recent:checked ~ .tab-panels .panel-recent { display:block; }
.kpi-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(205px, 1fr)); gap:14px; margin-bottom:16px; }
.staff-kpi {
    background:#fff;
    border:1px solid rgba(148,163,184,.24);
    border-radius:16px;
    padding:15px;
    box-shadow:0 8px 20px rgba(15,23,42,.05);
}
.staff-kpi small { display:block; font-size:11px; color:#64748b; font-weight:900; text-transform:uppercase; margin-bottom:8px; }
.staff-kpi strong { display:block; font-size:23px; color:#0f172a; line-height:1.1; }
.staff-kpi-head { display:flex; justify-content:space-between; align-items:center; gap:10px; }
.staff-kpi .material-symbols-rounded { color:#0f6dff; font-size:24px; }
.staff-card-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(270px, 1fr)); gap:14px; margin-bottom:16px; }
.staff-detail-card {
    border:1px solid var(--border);
    border-radius:16px;
    padding:15px;
    background:linear-gradient(145deg, #fff, #f8fbff);
}
.staff-detail-head { display:flex; justify-content:space-between; gap:10px; align-items:flex-start; margin-bottom:12px; }
.mini-stats { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:8px; }
.mini-stats div { border:1px solid #e2e8f0; border-radius:12px; padding:9px; background:#fff; }
.mini-stats small { display:block; color:#64748b; font-size:10px; font-weight:900; text-transform:uppercase; margin-bottom:4px; }
.mini-stats strong { font-size:14px; color:#0f172a; }
.product-list { display:grid; gap:10px; }
.product-row {
    display:flex;
    justify-content:space-between;
    gap:12px;
    border:1px solid #e2e8f0;
    border-radius:14px;
    padding:12px;
    background:#fff;
}
.staff-table-wrap table { min-width:900px; }
@media (max-width: 900px) {
    .staff-filter-row {
        display:flex;
        flex-wrap:nowrap;
        overflow-x:auto;
        overflow-y:hidden;
        gap:6px;
        padding-bottom:4px;
        scrollbar-width:thin;
        -webkit-overflow-scrolling:touch;
    }
    .staff-filter-row::-webkit-scrollbar { height:4px; }
    .staff-filter-row::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:999px; }
    .staff-filter-row .btn,
    .staff-filter-row .form-input {
        width:auto;
        flex:0 0 auto;
        min-height:34px;
        padding:7px 13px;
        border-radius:12px;
        font-size:12px;
        justify-content:center;
        white-space:nowrap;
    }
    .staff-filter-row .material-symbols-rounded { font-size:18px; }
    .staff-command { padding:12px; }
    .staff-command-top { gap:10px; }
    .staff-badges {
        display:grid;
        grid-template-columns:repeat(3, minmax(0, 1fr));
        gap:6px;
        width:100%;
    }
    .staff-badge {
        justify-content:center;
        min-width:0;
        padding:6px 7px;
        font-size:11px;
        line-height:1;
        white-space:nowrap;
    }
    .staff-badge .material-symbols-rounded { font-size:16px; }
    .staff-custom-fields {
        flex:0 0 auto;
        display:none;
        gap:6px;
    }
    .staff-custom-fields.active { display:flex; }
    .staff-custom-fields .form-input,
    .staff-custom-fields .btn { width:auto; min-width:0; }
    .staff-custom-fields .form-input { width:136px; }
    .staff-filter-actions {
        justify-content:flex-end;
        margin-top:8px;
    }
    .staff-filter-actions .btn {
        flex:0 0 auto;
        min-height:34px;
        padding:8px 14px;
        border-radius:12px;
        font-size:12px;
        justify-content:center;
    }
    .tab-nav { overflow-x:auto; flex-wrap:nowrap; }
    .tab-nav label { white-space:nowrap; }
    .staff-picker {
        grid-template-columns:1fr;
        gap:7px;
        margin-top:10px;
    }
    .staff-toggle {
        min-height:54px;
        padding:8px 10px;
        border-radius:13px;
    }
    .staff-toggle-main { gap:8px; }
    .staff-avatar {
        width:34px;
        height:34px;
        border-radius:11px;
        flex-basis:34px;
        font-size:13px;
    }
    .staff-toggle strong {
        font-size:12px;
        line-height:1.15;
    }
    .staff-toggle small {
        font-size:10px;
        line-height:1.2;
        margin-top:1px;
    }
    .role-pill {
        padding:2px 7px;
        font-size:9px;
        margin-top:4px;
    }
    .switch-ui {
        width:36px;
        height:20px;
        padding:2px;
        flex-basis:36px;
    }
    .switch-ui::after {
        width:16px;
        height:16px;
    }
    .staff-toggle input:checked + .staff-toggle-main + .switch-ui::after { transform:translateX(16px); }
}
@media (max-width: 420px) {
    .staff-filter-card { padding:10px; }
    .staff-badge {
        padding:5px;
        font-size:10px;
    }
    .staff-badge .material-symbols-rounded { font-size:15px; }
    .staff-filter-row .btn {
        min-height:32px;
        padding:6px 11px;
        font-size:11px;
    }
    .staff-filter-actions { margin-top:8px; }
    .staff-filter-actions .btn {
        min-height:32px;
        font-size:12px;
        padding:7px 12px;
    }
}
</style>

<div class="staff-analytics-page">
    <div class="page-header">
        <div>
            <h1>Staff Analytics</h1>
            <p class="page-subtitle">Owner level sales, staff performance, payments, products and recent activity.</p>
        </div>
    </div>

    <section class="staff-command">
        <div class="staff-command-top">
            <div>
                <h2 style="margin:0 0 6px; font-size:22px;">Owner Staff Command Center</h2>
                <p class="page-subtitle" style="margin:0;"><?php echo $selectedCount > 0 ? number_format($selectedCount) . ' selected staff' : 'All staff combined'; ?> for <?php echo Security::escape($filterLabel); ?>.</p>
            </div>
            <div class="staff-badges">
                <span class="staff-badge dark"><span class="material-symbols-rounded">workspace_premium</span><?php echo number_format((int) ($roleCounts['store_owner'] ?? 0)); ?> Owner</span>
                <span class="staff-badge"><span class="material-symbols-rounded">admin_panel_settings</span><?php echo number_format((int) ($roleCounts['admin'] ?? 0)); ?> Admin</span>
                <span class="staff-badge success"><span class="material-symbols-rounded">groups</span><?php echo number_format($staffCount); ?> Total</span>
            </div>
        </div>
    </section>

    <form class="staff-filter-card" method="GET" action="<?= $baseUrl ?>/staffanalytics/index" data-staff-analytics-filter>
        <input type="hidden" name="date_filter" value="<?php echo Security::escape($dateFilter ?? 'today'); ?>" data-date-filter-value>
        <div class="staff-filter-row">
            <button class="btn staff-date-btn <?= ($dateFilter ?? 'today') === 'today' ? 'btn-primary' : '' ?>" data-date-preset="today" type="submit">Today</button>
            <button class="btn staff-date-btn <?= ($dateFilter ?? '') === 'yesterday' ? 'btn-primary' : '' ?>" data-date-preset="yesterday" type="submit">Yesterday</button>
            <button class="btn staff-date-btn <?= ($dateFilter ?? '') === 'this_week' ? 'btn-primary' : '' ?>" data-date-preset="this_week" type="submit">This Week</button>
            <button class="btn staff-date-btn <?= ($dateFilter ?? '') === 'this_month' ? 'btn-primary' : '' ?>" data-date-preset="this_month" type="submit">This Month</button>
            <button class="btn staff-custom-toggle <?= ($dateFilter ?? '') === 'custom' ? 'btn-primary' : '' ?>" type="button" data-custom-date-toggle>
                <span class="material-symbols-rounded">date_range</span>Custom Dates
            </button>
            <span class="staff-custom-fields <?= ($dateFilter ?? '') === 'custom' ? 'active' : '' ?>" data-custom-date-fields>
                <input class="form-input" type="date" name="start_date" value="<?php echo Security::escape($startDate ?? ''); ?>" <?php echo ($dateFilter ?? '') === 'custom' ? '' : 'disabled'; ?>>
                <input class="form-input" type="date" name="end_date" value="<?php echo Security::escape($endDate ?? ''); ?>" <?php echo ($dateFilter ?? '') === 'custom' ? '' : 'disabled'; ?>>
                <button class="btn staff-apply-btn" type="submit" data-apply-custom>
                    <span class="material-symbols-rounded">done</span>Apply Custom
                </button>
            </span>
            <a class="btn btn-ghost" href="<?= $baseUrl ?>/staffanalytics/index">Reset</a>
        </div>

        <div class="staff-picker">
            <?php foreach (($staffOptions ?? []) as $staff): ?>
                <?php
                $role = $staff['role'] ?? 'staff';
                $roleClass = $role === 'store_owner' ? 'owner' : ($role === 'admin' ? 'admin' : '');
                ?>
                <label class="staff-toggle">
                    <input type="checkbox" name="staff_ids[]" value="<?php echo (int) $staff['id']; ?>" <?php echo isset($selectedSet[(int) $staff['id']]) ? 'checked' : ''; ?>>
                    <span class="staff-toggle-main">
                        <span class="staff-avatar"><?php echo strtoupper(substr((string) ($staff['name'] ?? 'U'), 0, 1)); ?></span>
                        <span class="staff-toggle-text">
                            <strong><?php echo Security::escape($staff['name']); ?></strong>
                            <small><?php echo Security::escape($staff['email'] ?: ucfirst(str_replace('_', ' ', $role))); ?></small>
                            <span class="role-pill <?php echo $roleClass; ?>"><?php echo Security::escape(ucfirst(str_replace('_', ' ', $role))); ?></span>
                        </span>
                    </span>
                    <span class="switch-ui"></span>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="staff-filter-actions">
            <button class="btn staff-apply-btn" type="submit" data-apply-filter><span class="material-symbols-rounded">filter_alt</span>Apply Filter</button>
        </div>
    </form>

    <section class="analytics-tabs">
        <input type="radio" id="tab-overview" name="staff_analytics_tab" checked>
        <input type="radio" id="tab-staff" name="staff_analytics_tab">
        <input type="radio" id="tab-products" name="staff_analytics_tab">
        <input type="radio" id="tab-recent" name="staff_analytics_tab">

        <div class="tab-nav">
            <label for="tab-overview"><span class="material-symbols-rounded">monitoring</span>Overview</label>
            <label for="tab-staff"><span class="material-symbols-rounded">badge</span>Staff Details</label>
            <label for="tab-products"><span class="material-symbols-rounded">inventory_2</span>Products</label>
            <label for="tab-recent"><span class="material-symbols-rounded">history</span>Recent Sales</label>
        </div>

        <div class="tab-panels">
            <div class="tab-panel panel-overview">
                <div class="kpi-grid">
                    <div class="staff-kpi"><div class="staff-kpi-head"><small>Total Sales</small><span class="material-symbols-rounded">point_of_sale</span></div><strong><?php echo $money($summary['sales_total'] ?? 0); ?></strong></div>
                    <div class="staff-kpi"><div class="staff-kpi-head"><small>Net Total</small><span class="material-symbols-rounded">trending_up</span></div><strong><?php echo $money($summary['net_total'] ?? 0); ?></strong></div>
                    <div class="staff-kpi"><div class="staff-kpi-head"><small>Total Bills</small><span class="material-symbols-rounded">receipt_long</span></div><strong><?php echo number_format((int) ($summary['bills'] ?? 0)); ?></strong></div>
                    <div class="staff-kpi"><div class="staff-kpi-head"><small>Average Bill</small><span class="material-symbols-rounded">functions</span></div><strong><?php echo $money($summary['avg_bill'] ?? 0); ?></strong></div>
                    <div class="staff-kpi"><div class="staff-kpi-head"><small>Cash</small><span class="material-symbols-rounded">payments</span></div><strong><?php echo $money($summary['cash_total'] ?? 0); ?></strong></div>
                    <div class="staff-kpi"><div class="staff-kpi-head"><small>Online</small><span class="material-symbols-rounded">account_balance</span></div><strong><?php echo $money($summary['online_total'] ?? 0); ?></strong></div>
                    <div class="staff-kpi"><div class="staff-kpi-head"><small>Credit / Udhar</small><span class="material-symbols-rounded">credit_score</span></div><strong><?php echo $money($summary['credit_total'] ?? 0); ?></strong></div>
                    <div class="staff-kpi"><div class="staff-kpi-head"><small>Returns</small><span class="material-symbols-rounded">assignment_return</span></div><strong><?php echo $money($summary['returns_total'] ?? 0); ?></strong></div>
                </div>

            </div>

            <div class="tab-panel panel-staff">
                <section class="staff-card-grid">
                    <?php foreach (($staffRows ?? []) as $staff): ?>
                        <?php $role = $staff['role'] ?? 'staff'; ?>
                        <article class="staff-detail-card">
                            <div class="staff-detail-head">
                                <div style="display:flex; gap:10px; min-width:0;">
                                    <div class="staff-avatar"><?php echo strtoupper(substr((string) ($staff['name'] ?? 'U'), 0, 1)); ?></div>
                                    <div style="min-width:0;">
                                        <h3 style="margin:0; font-size:16px;"><?php echo Security::escape($staff['name']); ?></h3>
                                        <div class="page-subtitle" style="margin-top:4px;"><?php echo Security::escape($staff['email'] ?: ucfirst(str_replace('_', ' ', $role))); ?></div>
                                        <span class="role-pill <?php echo $role === 'store_owner' ? 'owner' : ($role === 'admin' ? 'admin' : ''); ?>"><?php echo Security::escape(ucfirst(str_replace('_', ' ', $role))); ?></span>
                                    </div>
                                </div>
                                <a class="icon-btn" href="<?= $baseUrl ?>/sales/index?staff_id=<?php echo (int) $staff['id']; ?>" title="Open staff sales"><span class="material-symbols-rounded">open_in_new</span></a>
                            </div>
                            <div class="mini-stats">
                                <div><small>Sales</small><strong><?php echo $money($staff['sales_total'] ?? 0); ?></strong></div>
                                <div><small>Bills</small><strong><?php echo number_format((int) ($staff['bills'] ?? 0)); ?></strong></div>
                                <div><small>Cash</small><strong><?php echo $money($staff['cash_total'] ?? 0); ?></strong></div>
                                <div><small>Online</small><strong><?php echo $money($staff['online_total'] ?? 0); ?></strong></div>
                                <div><small>Credit</small><strong><?php echo $money($staff['credit_total'] ?? 0); ?></strong></div>
                                <div><small>Due</small><strong><?php echo $money($staff['balance_due'] ?? 0); ?></strong></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>

                <section class="card table-wrap staff-table-wrap">
                    <h3 style="margin-bottom:14px;">Staff Comparison</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Staff</th><th>Bills</th><th>Sales</th><th>Cash</th><th>Online</th><th>Credit</th><th>Returns</th><th>Due</th><th>Last Sale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($staffRows)): ?>
                                <tr><td colspan="9" class="empty-state">No staff data found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($staffRows as $staff): ?>
                                    <tr>
                                        <td><strong><?php echo Security::escape($staff['name']); ?></strong><div class="page-subtitle"><?php echo Security::escape($staff['email']); ?></div></td>
                                        <td><?php echo number_format((int) ($staff['bills'] ?? 0)); ?></td>
                                        <td><strong><?php echo $money($staff['sales_total'] ?? 0); ?></strong></td>
                                        <td><?php echo $money($staff['cash_total'] ?? 0); ?></td>
                                        <td><?php echo $money($staff['online_total'] ?? 0); ?></td>
                                        <td><?php echo $money($staff['credit_total'] ?? 0); ?></td>
                                        <td><?php echo $money($staff['returns_total'] ?? 0); ?></td>
                                        <td><?php echo $money($staff['balance_due'] ?? 0); ?></td>
                                        <td><?php echo !empty($staff['last_sale_at']) ? date('M d, h:i A', strtotime($staff['last_sale_at'])) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>
            </div>

            <div class="tab-panel panel-products">
                <section class="card">
                    <h3 style="margin-bottom:14px;">Top Products</h3>
                    <?php if (empty($topProducts)): ?>
                        <div class="empty-state">No product sales in this period.</div>
                    <?php else: ?>
                        <div class="product-list">
                            <?php foreach ($topProducts as $idx => $product): ?>
                                <div class="product-row">
                                    <div style="min-width:0;">
                                        <strong>#<?php echo $idx + 1; ?> <?php echo Security::escape($product['product_name']); ?></strong>
                                        <div class="page-subtitle" style="margin-top:4px;"><?php echo Security::escape($product['model_number'] ?: 'No model'); ?> | <?php echo number_format((int) $product['qty_sold']); ?> sold</div>
                                    </div>
                                    <strong style="color:#059669;"><?php echo $money($product['revenue'] ?? 0); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <div class="tab-panel panel-recent">
                <section class="card table-wrap">
                    <h3 style="margin-bottom:14px;">Recent Staff Sales</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice</th><th>Staff</th><th>Customer</th><th>Type</th><th>Payment</th><th>Total</th><th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentSales)): ?>
                                <tr><td colspan="7" class="empty-state">No recent sales found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentSales as $sale): ?>
                                    <tr>
                                        <td><a href="<?= $baseUrl ?>/sales/action/<?php echo (int) $sale['id']; ?>"><strong><?php echo Security::escape($sale['custom_id']); ?></strong></a></td>
                                        <td><?php echo Security::escape($sale['staff_name'] ?: 'System'); ?></td>
                                        <td><?php echo Security::escape($sale['customer_name']); ?></td>
                                        <td><?php echo Security::escape($sale['transaction_type']); ?></td>
                                        <td><?php echo ucfirst(Security::escape($sale['payment_method'])); ?></td>
                                        <td><strong><?php echo $money($sale['total'] ?? 0); ?></strong></td>
                                        <td><?php echo date('M d, h:i A', strtotime($sale['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>
            </div>
        </div>
    </section>
</div>

<?php
$extraJs = <<<'JS'
<script>
const staffFilterForm = document.querySelector('[data-staff-analytics-filter]');
if (staffFilterForm) {
    const filterInput = staffFilterForm.querySelector('[data-date-filter-value]');
    const customToggle = staffFilterForm.querySelector('[data-custom-date-toggle]');
    const customFields = staffFilterForm.querySelector('[data-custom-date-fields]');
    const customInputs = customFields ? customFields.querySelectorAll('input') : [];
    const presetButtons = staffFilterForm.querySelectorAll('[data-date-preset]');

    const setCustomVisible = (visible) => {
        if (!customFields) return;
        customFields.classList.toggle('active', visible);
        customInputs.forEach((input) => {
            input.disabled = !visible;
        });
        if (customToggle) {
            customToggle.classList.toggle('btn-primary', visible);
        }
    };

    presetButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (filterInput) {
                filterInput.value = button.dataset.datePreset || 'today';
            }
            setCustomVisible(false);
        });
    });

    if (customToggle) {
        customToggle.addEventListener('click', () => {
            if (filterInput) {
                filterInput.value = 'custom';
            }
            setCustomVisible(true);
            const firstInput = customFields ? customFields.querySelector('input') : null;
            if (firstInput) {
                firstInput.focus();
            }
        });
    }

    staffFilterForm.querySelectorAll('[data-apply-custom]').forEach((button) => {
        button.addEventListener('click', () => {
            if (filterInput) {
                filterInput.value = 'custom';
            }
            setCustomVisible(true);
        });
    });

    staffFilterForm.addEventListener('submit', () => {
        if (customFields && customFields.classList.contains('active') && filterInput) {
            filterInput.value = 'custom';
        }
    });
}
</script>
JS;
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
