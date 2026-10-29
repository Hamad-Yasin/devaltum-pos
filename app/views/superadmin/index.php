<?php ob_start(); ?>

<style>
.sa-shell { display:grid; gap:18px; }
.sa-hero {
    display:grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(280px, .9fr);
    gap:18px;
    align-items:stretch;
}
.sa-hero-main {
    position:relative;
    overflow:hidden;
    border:1px solid rgba(15, 109, 255, .22);
    border-radius:22px;
    background:
        linear-gradient(135deg, rgba(15, 109, 255, .16), rgba(16, 185, 129, .10)),
        #fff;
    padding:22px;
    box-shadow:0 18px 42px rgba(15, 23, 42, .08);
}
.sa-hero-main::after {
    content:'';
    position:absolute;
    inset:auto -10% -70px 35%;
    height:180px;
    background:radial-gradient(circle, rgba(15, 109, 255, .16), transparent 65%);
    pointer-events:none;
}
.sa-kicker {
    display:inline-flex;
    align-items:center;
    gap:8px;
    border:1px solid rgba(15, 109, 255, .18);
    border-radius:999px;
    padding:7px 10px;
    background:#fff;
    color:#0f3f8f;
    font-weight:900;
    font-size:12px;
}
.sa-hero-title { margin:18px 0 8px; font-size:34px; line-height:1.05; letter-spacing:0; }
.sa-hero-copy { color:var(--text-muted); max-width:620px; font-weight:700; line-height:1.55; }
.sa-hero-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:20px; }
.sa-revenue-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; margin-top:22px; }
.sa-revenue-card {
    border:1px solid rgba(148, 163, 184, .24);
    border-radius:18px;
    background:rgba(255,255,255,.82);
    padding:15px;
}
.sa-revenue-card small,
.sa-metric small { display:block; color:var(--text-muted); font-weight:900; font-size:11px; text-transform:uppercase; letter-spacing:.04em; }
.sa-revenue-card strong { display:block; margin-top:8px; font-size:27px; line-height:1.1; }
.sa-health-card {
    border-radius:22px;
    border:1px solid rgba(16, 185, 129, .24);
    background:linear-gradient(160deg, #061629, #0f2d4e);
    color:#fff;
    padding:20px;
    box-shadow:0 18px 42px rgba(15, 23, 42, .14);
}
.sa-health-card small { color:#b7d9ff; font-weight:900; text-transform:uppercase; font-size:11px; }
.sa-health-score { font-size:46px; font-weight:900; line-height:1; margin:12px 0; }
.sa-health-bar { height:10px; border-radius:999px; background:rgba(255,255,255,.12); overflow:hidden; margin:14px 0; }
.sa-health-bar span { display:block; height:100%; border-radius:999px; background:linear-gradient(90deg,#22c55e,#38bdf8); }
.sa-health-list { display:grid; gap:9px; margin-top:16px; }
.sa-health-row { display:flex; align-items:center; justify-content:space-between; gap:12px; color:#dbeafe; font-weight:800; font-size:13px; }
.sa-metrics { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:14px; }
.sa-metric {
    position:relative;
    overflow:hidden;
    border:1px solid rgba(148, 163, 184, .24);
    border-radius:18px;
    background:#fff;
    padding:16px;
    box-shadow:0 10px 24px rgba(15,23,42,.05);
}
.sa-metric::before { content:''; position:absolute; inset:0 0 auto 0; height:4px; background:linear-gradient(90deg,#0f6dff,#22c55e); }
.sa-metric strong { display:block; margin-top:9px; font-size:28px; line-height:1.1; }
.sa-grid { display:grid; grid-template-columns: minmax(0, 1.35fr) minmax(310px, .85fr); gap:18px; align-items:start; }
.sa-card-head { display:flex; justify-content:space-between; gap:12px; align-items:center; margin-bottom:14px; }
.sa-card-head h3 { margin:0; }
.sa-link { color:#0f6dff; font-weight:900; font-size:13px; }
.sa-store-cell { display:flex; flex-direction:column; gap:3px; }
.sa-store-cell strong { font-size:14px; }
.sa-store-cell span { color:var(--text-muted); font-size:12px; font-weight:800; }
.sa-side-list { display:grid; gap:10px; }
.sa-side-item {
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
    border:1px solid rgba(148, 163, 184, .22);
    border-radius:15px;
    padding:12px;
    background:linear-gradient(135deg,#fff,#f8fbff);
}
.sa-side-item strong { display:block; font-size:13px; }
.sa-side-item span { display:block; color:var(--text-muted); font-size:11px; font-weight:800; margin-top:3px; }
.sa-empty { padding:16px; border-radius:15px; background:#f8fafc; color:var(--text-muted); font-weight:800; text-align:center; }
@media (max-width: 1100px) {
    .sa-hero,
    .sa-grid { grid-template-columns:1fr; }
    .sa-metrics { grid-template-columns:repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 620px) {
    .sa-hero-main,
    .sa-health-card { border-radius:18px; padding:16px; }
    .sa-hero-title { font-size:26px; }
    .sa-revenue-grid,
    .sa-metrics { grid-template-columns:1fr 1fr; gap:10px; }
    .sa-revenue-card,
    .sa-metric { padding:13px; border-radius:15px; }
    .sa-revenue-card strong,
    .sa-metric strong { font-size:21px; }
    .sa-health-score { font-size:38px; }
    .sa-hero-actions .btn { flex:1 1 100%; justify-content:center; }
}
@media (max-width: 380px) {
    .sa-revenue-grid,
    .sa-metrics { grid-template-columns:1fr; }
}
</style>

<div class="page-header">
    <div>
        <h1>Super Admin Dashboard</h1>
        <p class="page-subtitle">Control stores, plans, renewals and platform revenue from one clean command center.</p>
    </div>
</div>

<div class="sa-shell">
    <section class="sa-hero">
        <article class="sa-hero-main">
            <span class="sa-kicker"><span class="material-symbols-rounded">admin_panel_settings</span> SaaS Control Center</span>
            <h2 class="sa-hero-title">Platform overview built for fast decisions.</h2>
            <p class="sa-hero-copy">Track revenue, store health, renewals and access without digging through separate screens.</p>
            <div class="sa-hero-actions">
                <a class="btn btn-primary" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/stores?tab=create"><span class="material-symbols-rounded">add_business</span>Create Store</a>
                <a class="btn btn-secondary" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/plans?tab=list"><span class="material-symbols-rounded">workspace_premium</span>Manage Plans</a>
                <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/runAutomation" method="POST" data-no-ajax>
                    <?php echo Security::csrfField(); ?>
                    <button class="btn btn-secondary" type="submit"><span class="material-symbols-rounded">sync</span>Run Billing</button>
                </form>
            </div>
            <div class="sa-revenue-grid">
                <div class="sa-revenue-card"><small>All-Time Revenue</small><strong><?php echo App::money((float) ($stats['total_revenue'] ?? 0)); ?></strong></div>
                <div class="sa-revenue-card"><small>Last 30 Days</small><strong><?php echo App::money((float) ($stats['monthly_revenue'] ?? 0)); ?></strong></div>
            </div>
        </article>

        <aside class="sa-health-card">
            <small>Platform Health</small>
            <div class="sa-health-score"><?php echo (int) ($stats['platform_health'] ?? 0); ?>%</div>
            <div class="sa-health-bar"><span style="width:<?php echo min(100, max(0, (int) ($stats['platform_health'] ?? 0))); ?>%;"></span></div>
            <div class="sa-health-list">
                <div class="sa-health-row"><span>Active Stores</span><strong><?php echo number_format((int) ($stats['active_stores'] ?? 0)); ?></strong></div>
                <div class="sa-health-row"><span>At Risk</span><strong><?php echo number_format((int) ($stats['risk_stores'] ?? 0)); ?></strong></div>
                <div class="sa-health-row"><span>Expiring Soon</span><strong><?php echo number_format((int) ($stats['expiring_soon'] ?? 0)); ?></strong></div>
            </div>
        </aside>
    </section>

    <section class="sa-metrics">
        <article class="sa-metric"><small>Total Stores</small><strong><?php echo number_format((int) ($stats['total_stores'] ?? 0)); ?></strong></article>
        <article class="sa-metric"><small>Active Subs</small><strong><?php echo number_format((int) ($stats['active_subscriptions'] ?? 0)); ?></strong></article>
        <article class="sa-metric"><small>Suspended</small><strong><?php echo number_format((int) ($stats['suspended_stores'] ?? 0)); ?></strong></article>
        <article class="sa-metric"><small>Expired</small><strong><?php echo number_format((int) ($stats['expired_stores'] ?? 0)); ?></strong></article>
    </section>

    <section class="sa-grid">
        <article class="card table-wrap">
            <div class="sa-card-head">
                <h3>Recent Stores</h3>
                <a class="sa-link" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/stores?tab=list">View all</a>
            </div>
            <table>
                <thead><tr><th>Store</th><th>Plan</th><th>Expiry</th><th>Status</th><th>Open</th></tr></thead>
                <tbody>
                    <?php if (empty($recentStores)): ?>
                        <tr><td colspan="5" class="empty-state">No stores available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentStores as $store): ?>
                            <?php $status = strtolower((string) ($store['status'] ?? 'inactive')); ?>
                            <tr>
                                <td>
                                    <div class="sa-store-cell">
                                        <strong><?php echo Security::escape($store['store_name']); ?></strong>
                                        <span><?php echo Security::escape($store['owner_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo Security::escape(ucfirst((string) ($store['plan_name'] ?? 'free'))); ?></td>
                                <td><?php echo !empty($store['subscription_expires_at']) ? date('M d, Y', strtotime($store['subscription_expires_at'])) : '-'; ?></td>
                                <td><span class="badge <?php echo $status === 'active' ? 'badge-success' : ($status === 'suspended' ? 'badge-warn' : 'badge-danger'); ?>"><?php echo Security::escape(ucfirst($status)); ?></span></td>
                                <td><a class="icon-btn" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/storeProfile/<?php echo (int) $store['id']; ?>" title="View Store"><span class="material-symbols-rounded">visibility</span></a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </article>

        <aside class="sa-side-list">
            <section class="card">
                <div class="sa-card-head">
                    <h3>Recent Payments</h3>
                    <a class="sa-link" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/payments">Open</a>
                </div>
                <div class="sa-side-list">
                    <?php if (empty($recentPayments)): ?>
                        <div class="sa-empty">No payment activity yet.</div>
                    <?php else: ?>
                        <?php foreach ($recentPayments as $payment): ?>
                            <div class="sa-side-item">
                                <div>
                                    <strong><?php echo Security::escape((string) ($payment['store_name'] ?? 'Store')); ?></strong>
                                    <span><?php echo Security::escape((string) ($payment['method_name'] ?? '-')); ?> | <?php echo !empty($payment['paid_at']) ? date('M d, h:i A', strtotime($payment['paid_at'])) : date('M d, h:i A', strtotime($payment['created_at'] ?? 'now')); ?></span>
                                </div>
                                <strong><?php echo App::money((float) ($payment['amount'] ?? 0)); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card">
                <div class="sa-card-head"><h3>Renewal Watch</h3></div>
                <div class="sa-side-list">
                    <?php if (empty($expiringStores)): ?>
                        <div class="sa-empty">No stores expiring in 7 days.</div>
                    <?php else: ?>
                        <?php foreach ($expiringStores as $store): ?>
                            <a class="sa-side-item" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/storeProfile/<?php echo (int) $store['id']; ?>">
                                <div>
                                    <strong><?php echo Security::escape((string) $store['store_name']); ?></strong>
                                    <span><?php echo Security::escape((string) ($store['plan_name'] ?? 'Plan')); ?></span>
                                </div>
                                <strong><?php echo date('M d', strtotime($store['subscription_expires_at'])); ?></strong>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </aside>
    </section>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
