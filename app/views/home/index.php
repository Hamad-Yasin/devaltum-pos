<?php ob_start(); ?>

<style>
.dash-hero {
    background: linear-gradient(125deg, rgba(15, 109, 255, .14), rgba(16, 185, 129, .1), rgba(245, 158, 11, .12));
    border: 1px solid rgba(15, 109, 255, .2);
    border-radius: 20px;
    padding: 18px;
    margin-bottom: 18px;
}

.metric-card-danger {
    background: linear-gradient(140deg, #dc2626, #b91c1c);
    border: 1px solid rgba(127, 29, 29, .45);
    box-shadow: 0 12px 26px rgba(185, 28, 28, .24);
}

.metric-card-danger small {
    color: rgba(255, 255, 255, .86);
}

.metric-card-danger strong {
    color: #fff !important;
}

.ribbon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(128px, 1fr));
    gap: 12px;
    margin-top: 16px;
}

.ribbon-col {
    text-align: center;
    min-height: 148px;
    padding: 12px 8px;
    border-radius: 16px;
    background: linear-gradient(180deg, rgba(15, 23, 42, .04), rgba(148, 163, 184, .08));
    border: 1px solid rgba(148, 163, 184, .28);
    display: flex;
    flex-direction: column;
    justify-content: center;
    animation: ribbonTileIn .7s ease both;
    animation-delay: calc(var(--ri, 0) * 90ms);
}

.ring-wrap {
    position: relative;
    width: 98px;
    height: 98px;
    margin: 0 auto;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: radial-gradient(circle at 30% 25%, rgba(255, 255, 255, .92), rgba(241, 245, 249, .75) 48%, rgba(226, 232, 240, .9));
    box-shadow: inset 0 1px 6px rgba(15, 23, 42, .06), 0 10px 24px rgba(15, 23, 42, .12);
    animation: ringFloat 5s ease-in-out infinite;
    animation-delay: calc(var(--ri, 0) * 120ms);
    overflow: hidden;
}

.ring-track {
    width: 82px;
    height: 82px;
    border-radius: 50%;
    position: relative;
    background: conic-gradient(#0f6dff 0deg, #0f6dff var(--sale-angle), #ef4444 var(--sale-angle), #ef4444 calc(var(--sale-angle) + var(--expense-angle)), #e2e8f0 calc(var(--sale-angle) + var(--expense-angle)) 360deg);
    box-shadow: 0 0 0 1px rgba(148, 163, 184, .35), 0 0 18px rgba(15, 109, 255, .2), 0 0 16px rgba(239, 68, 68, .14);
    animation: ringPulse 2.8s ease-in-out infinite;
    animation-delay: calc(var(--ri, 0) * 110ms);
}

.ring-track::after {
    content: '';
    position: absolute;
    inset: 13px;
    border-radius: 50%;
    background: #ffffff;
    box-shadow: inset 0 1px 3px rgba(15, 23, 42, .08);
}

.ring-core {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    text-align: center;
    z-index: 2;
    color: #0f172a;
    pointer-events: none;
}

.ring-core-kicker {
    font-size: 9px;
    font-weight: 800;
    color: #334155;
    letter-spacing: .02em;
}

.ring-core-value {
    font-size: 11px;
    font-weight: 900;
    color: #0f6dff;
    line-height: 1.15;
}

.ring-core-label {
    font-size: 9px;
    font-weight: 800;
    color: #64748b;
}

.ring-hit {
    position: absolute;
    top: 8px;
    bottom: 8px;
    width: 50%;
    border: none;
    background: transparent;
    cursor: pointer;
    z-index: 4;
}

.ring-hit.sales { left: 0; border-radius: 999px 0 0 999px; }
.ring-hit.expense { right: 0; border-radius: 0 999px 999px 0; }
.ring-hit:focus { outline: 2px solid rgba(15, 109, 255, .35); outline-offset: -2px; }
.ring-hit:hover { background: rgba(255, 255, 255, .12); }
.ring-hit.active {
    background: linear-gradient(180deg, rgba(255, 255, 255, .24), rgba(255, 255, 255, .06));
}

.ring-wrap.is-sales .ring-core-value { color: #0f6dff; }
.ring-wrap.is-expense .ring-core-value { color: #ef4444; }

.ribbon-net {
    margin-top: 8px;
    font-size: 11px;
    font-weight: 700;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

.mix-meter {
    height: 14px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
}

.mix-fill {
    height: 100%;
    border-radius: 999px;
}

.sales-pro-shell {
    border: 1px solid rgba(15, 109, 255, .20);
    border-radius: 22px;
    padding: 16px;
    background: linear-gradient(160deg, rgba(15, 109, 255, .08), rgba(255, 255, 255, .95) 42%, rgba(16, 185, 129, .08));
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .65), 0 14px 32px rgba(30, 64, 175, .10);
}

.sales-pro-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}

.sales-kpi {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.sales-kpi span {
    font-size: 12px;
    font-weight: 700;
    padding: 6px 10px;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #dbeafe;
    color: #0f172a;
}

.sales-bars {
    display: grid;
    grid-template-columns: repeat(7, minmax(86px, 1fr));
    gap: 14px;
    align-items: end;
    min-height: 300px;
    position: relative;
    z-index: 2;
}

.sales-bars-wrap {
    position: relative;
    border-radius: 18px;
    border: 1px solid rgba(148, 163, 184, .25);
    background: linear-gradient(180deg, rgba(248, 250, 252, .90), rgba(241, 245, 249, .85));
    padding: 20px 12px 10px;
    overflow: hidden;
}

.sales-bars-wrap::before {
    content: '';
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(to top, rgba(148, 163, 184, .18) 0 1px, transparent 1px 48px);
    opacity: .7;
}

.sales-bars-wrap::after {
    content: '';
    position: absolute;
    width: 220px;
    height: 220px;
    right: -60px;
    top: -70px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(59, 130, 246, .24), rgba(59, 130, 246, 0));
    animation: orbitGlow 8s ease-in-out infinite;
}

.sales-day {
    text-align: center;
    position: relative;
    z-index: 2;
}

.sales-amt {
    font-size: 11px;
    font-weight: 700;
    color: #1e3a8a;
    margin-bottom: 7px;
}

.sales-pill {
    height: 210px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.sales-fill {
    width: 100%;
    max-width: 48px;
    border-radius: 18px;
    min-height: 14px;
    background: linear-gradient(180deg, #1e40af 0%, #2563eb 52%, #60a5fa 100%);
    position: relative;
    box-shadow: 0 12px 26px rgba(29, 78, 216, .30), 0 0 0 1px rgba(30, 64, 175, .20);
    overflow: hidden;
    transform-origin: bottom;
    animation: salesRise .9s cubic-bezier(.22, 1, .36, 1) both;
    animation-delay: calc(var(--i, 0) * 90ms);
}

.sales-fill::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(130deg, rgba(255, 255, 255, .26), transparent 42%);
    animation: salesShimmer 3.4s linear infinite;
}

.sales-fill::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    height: 36%;
    background: linear-gradient(180deg, rgba(255, 255, 255, .24), transparent);
}

.sales-day-label {
    margin-top: 8px;
    font-size: 12px;
    font-weight: 800;
    color: #0f172a;
}

.sales-wide {
    grid-column: 1 / -1;
}

.ribbon-shell {
    border: 1px solid rgba(59, 130, 246, .20);
    border-radius: 20px;
    padding: 14px;
    background: linear-gradient(165deg, rgba(15, 109, 255, .05), rgba(255, 255, 255, .95) 40%, rgba(239, 68, 68, .06));
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7), 0 10px 28px rgba(15, 23, 42, .08);
}

@keyframes salesRise {
    from {
        transform: scaleY(.08);
        opacity: .3;
    }

    to {
        transform: scaleY(1);
        opacity: 1;
    }
}

@keyframes salesShimmer {
    0% {
        transform: translateX(-110%);
    }

    100% {
        transform: translateX(140%);
    }
}

@keyframes orbitGlow {

    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }

    50% {
        transform: translate(-30px, 14px) scale(1.1);
    }
}

@keyframes ringPulse {

    0%,
    100% {
        box-shadow: 0 0 0 1px rgba(148, 163, 184, .35), 0 0 14px rgba(15, 109, 255, .16), 0 0 12px rgba(239, 68, 68, .12);
    }

    50% {
        box-shadow: 0 0 0 1px rgba(148, 163, 184, .42), 0 0 28px rgba(15, 109, 255, .32), 0 0 22px rgba(239, 68, 68, .24);
    }
}

@keyframes ringFloat {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-4px);
    }
}

@keyframes ribbonTileIn {
    from {
        opacity: .2;
        transform: translateY(10px) scale(.96);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 700px) {
    .ribbon-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .sales-bars {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        min-height: 250px;
    }
}
</style>

<?php
$dashMoney = function ($value) {
    return preg_replace('/\.00\b/', '', App::money((float) $value));
};
?>

<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p class="page-subtitle">Live overview with premium analytics and operational insights.</p>
    </div>
    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/pos/index" class="btn btn-primary"><span class="material-symbols-rounded">point_of_sale</span>New
        Sale</a>
</div>

<div class="report-filters" style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; align-items:center; background:#fff; padding:12px 16px; border-radius:18px; border:1px solid var(--border); box-shadow:var(--shadow-sm);">
    <form method="GET" action="" class="date-filter-strip" style="display:flex; gap:10px; flex-wrap:wrap; width:100%; align-items:center;">
        <input type="hidden" name="url" value="home/index">
        <button type="submit" name="date_filter" value="today" class="btn <?= ($dateFilter ?? 'today') === 'today' ? 'btn-primary' : 'btn-secondary' ?>" style="padding:8px 16px; min-height:36px;">Today</button>
        <button type="submit" name="date_filter" value="yesterday" class="btn <?= ($dateFilter ?? 'today') === 'yesterday' ? 'btn-primary' : 'btn-secondary' ?>" style="padding:8px 16px; min-height:36px;">Yesterday</button>
        <button type="submit" name="date_filter" value="this_week" class="btn <?= ($dateFilter ?? 'today') === 'this_week' ? 'btn-primary' : 'btn-secondary' ?>" style="padding:8px 16px; min-height:36px;">This Week</button>
        <button type="submit" name="date_filter" value="this_month" class="btn <?= ($dateFilter ?? 'today') === 'this_month' ? 'btn-primary' : 'btn-secondary' ?>" style="padding:8px 16px; min-height:36px;">This Month</button>
        <button type="button" data-custom-date-toggle class="btn <?= ($dateFilter ?? 'today') === 'custom' ? 'btn-primary' : 'btn-secondary' ?>" style="padding:8px 16px; min-height:36px;">Custom Date</button>
        
        <div style="width:1px; height:24px; background:var(--border); margin:0 8px;" class="hidden-mob"></div>
        
        <div data-custom-date-range style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;" <?= ($dateFilter ?? 'today') === 'custom' ? '' : 'hidden' ?>>
            <input type="date" name="start_date" class="form-input" style="padding:6px 12px; height:36px; width:auto; font-size:13px;" value="<?= Security::escape($startDate ?? '') ?>">
            <span class="page-subtitle">to</span>
            <input type="date" name="end_date" class="form-input" style="padding:6px 12px; height:36px; width:auto; font-size:13px;" value="<?= Security::escape($endDate ?? '') ?>">
            <button type="submit" name="date_filter" value="custom" class="btn btn-secondary" style="padding:8px 16px; min-height:36px;">Apply Date</button>
        </div>
    </form>
</div>

<!-- PREMIUM DASHBOARD METRICS OVERHAUL -->
<div class="dash-hero" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff; border-radius: 22px; padding: 28px 32px; margin-bottom: 24px; box-shadow: 0 14px 35px rgba(15,23,42,0.2); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; transition: transform 0.3s ease;">
    <div>
        <h2 style="margin:0 0 8px 0; font-size: 26px; font-weight: 800; display:flex; align-items:center; gap:10px; letter-spacing:-0.5px;">
            <span class="material-symbols-rounded" style="color:#38bdf8; font-size:28px;">monitoring</span>
            Business Pulse
        </h2>
        <p style="margin:0; color:#cbd5e1; font-size:15px; font-weight:500;">Live financial snapshot and active business trajectory.</p>
    </div>
    <div style="text-align: right; background: rgba(255,255,255,0.06); padding: 18px 24px; border-radius: 18px; border: 1px solid rgba(255,255,255,0.12); backdrop-filter: blur(10px);">
        <div style="color:#94a3b8; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Net Revenue</div>
        <div style="font-size:36px; font-weight:900; color:<?php echo $stats['net_amount'] >= 0 ? '#34d399' : '#f87171'; ?>; filter: drop-shadow(0 2px 10px rgba(0,0,0,0.3)); letter-spacing:-1px;">
            <?php echo $dashMoney($stats['net_amount']); ?>
        </div>
        <div style="color:#64748b; font-size:12px; font-weight:600; margin-top:6px;">Sales - Expenses - Returns - Credit Remaining</div>
    </div>
</div>

<style>
.premium-metric-card {
    background: #ffffff;
    border-radius: 22px;
    padding: 22px 20px;
    border: 1px solid rgba(0,0,0,0.04);
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    display: flex;
    align-items: center;
    gap: 18px;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
}
.premium-metric-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 4px;
    background: transparent;
    transition: background 0.3s ease;
}
.premium-metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border-color: rgba(0,0,0,0.08);
}
.premium-metric-card:hover::before {
    background: var(--primary);
}
.premium-metric-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    flex-shrink: 0;
}
.premium-metric-content {
    flex: 1;
}
.premium-metric-content small {
    display: block;
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}
.premium-metric-content strong {
    display: block;
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    letter-spacing: -0.5px;
}

.icon-blue { background: #eff6ff; color: #3b82f6; }
.icon-emerald { background: #ecfdf5; color: #10b981; }
.icon-rose { background: #fff1f2; color: #f43f5e; border: 1px solid #ffe4e6; }
.icon-amber { background: #fffbeb; color: #f59e0b; }
.icon-purple { background: #faf5ff; color: #a855f7; }
.icon-indigo { background: #eef2ff; color: #6366f1; }
.icon-cyan { background: #ecfeff; color: #06b6d4; }
</style>

<div class="grid-cards" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <!-- Sales -->
    <div class="premium-metric-card">
        <div class="premium-metric-icon icon-blue"><span class="material-symbols-rounded">point_of_sale</span></div>
        <div class="premium-metric-content">
            <small>Total Sales</small>
            <strong><?php echo $dashMoney($stats['total_sales']); ?></strong>
        </div>
    </div>
    
    <!-- Orders -->
    <div class="premium-metric-card">
        <div class="premium-metric-icon icon-indigo"><span class="material-symbols-rounded">shopping_bag</span></div>
        <div class="premium-metric-content">
            <small>Total Orders</small>
            <strong><?php echo number_format($stats['total_orders']); ?></strong>
        </div>
    </div>

    <!-- Retail -->
    <div class="premium-metric-card">
        <div class="premium-metric-icon icon-purple"><span class="material-symbols-rounded">storefront</span></div>
        <div class="premium-metric-content">
            <small>Retail Sales</small>
            <strong><?php echo $dashMoney($stats['retail_sales'] ?? 0); ?></strong>
        </div>
    </div>

    <!-- Wholesale -->
    <div class="premium-metric-card">
        <div class="premium-metric-icon icon-amber"><span class="material-symbols-rounded">local_shipping</span></div>
        <div class="premium-metric-content">
            <small>Wholesale Sales</small>
            <strong><?php echo $dashMoney($stats['wholesale_sales'] ?? 0); ?></strong>
        </div>
    </div>
</div>

<div class="grid-cards" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <!-- Live Cash in Drawer (Till) -->
    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/index" class="premium-metric-card" style="text-decoration:none; border-top: 4px solid #059669;">
        <div class="premium-metric-icon icon-emerald"><span class="material-symbols-rounded">point_of_sale</span></div>
        <div class="premium-metric-content">
            <small style="color:#059669;">Cash in Drawer</small>
            <strong style="color:#047857;"><?php echo $dashMoney($stats['cash_in_drawer'] ?? 0); ?></strong>
        </div>
    </a>

    <!-- Live Digital Accounts (Banks & Wallets) -->
    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/index" class="premium-metric-card" style="text-decoration:none; border-top: 4px solid #2563eb;">
        <div class="premium-metric-icon icon-blue"><span class="material-symbols-rounded">account_balance_wallet</span></div>
        <div class="premium-metric-content">
            <small style="color:#2563eb;">Digital Accounts</small>
            <strong style="color:#1d4ed8;"><?php echo $dashMoney($stats['digital_accounts'] ?? 0); ?></strong>
        </div>
    </a>

    <!-- Cash Payments -->
    <div class="premium-metric-card">
        <div class="premium-metric-icon icon-emerald"><span class="material-symbols-rounded">payments</span></div>
        <div class="premium-metric-content">
            <small>Cash Payments</small>
            <strong><?php echo $dashMoney($stats['cash_sales']); ?></strong>
        </div>
    </div>

    <!-- Online -->
    <div class="premium-metric-card">
        <div class="premium-metric-icon icon-cyan"><span class="material-symbols-rounded">account_balance</span></div>
        <div class="premium-metric-content">
            <small>Online Payments</small>
            <strong><?php echo $dashMoney($stats['online_sales']); ?></strong>
        </div>
    </div>

    <!-- Expenses -->
    <div class="premium-metric-card">
        <div class="premium-metric-icon icon-rose"><span class="material-symbols-rounded">receipt_long</span></div>
        <div class="premium-metric-content">
            <small>Total Expenses</small>
            <strong><?php echo $dashMoney($stats['total_expenses']); ?></strong>
        </div>
    </div>

    <!-- Returns -->
    <div class="premium-metric-card">
        <div class="premium-metric-icon icon-amber"><span class="material-symbols-rounded">assignment_return</span></div>
        <div class="premium-metric-content">
            <small>Total Returns</small>
            <strong><?php echo $dashMoney($stats['total_returns'] ?? 0); ?></strong>
        </div>
    </div>

    <!-- Credit -->
    <div class="premium-metric-card" style="border-left: 4px solid #f43f5e;">
        <div class="premium-metric-icon icon-rose"><span class="material-symbols-rounded">credit_score</span></div>
        <div class="premium-metric-content">
            <small>Credit (Udhar)</small>
            <strong style="color: #f43f5e;"><?php echo $dashMoney($stats['credit_sales']); ?></strong>
        </div>
    </div>

    <!-- Credit Paid -->
    <div class="premium-metric-card" style="border-left: 4px solid #10b981;">
        <div class="premium-metric-icon icon-emerald"><span class="material-symbols-rounded">payments</span></div>
        <div class="premium-metric-content">
            <small>Credit Paid</small>
            <strong style="color: #059669;"><?php echo $dashMoney($stats['credit_paid'] ?? 0); ?></strong>
        </div>
    </div>

    <!-- Credit Remaining -->
    <div class="premium-metric-card" style="border-left: 4px solid #ef4444;">
        <div class="premium-metric-icon icon-rose"><span class="material-symbols-rounded">pending_actions</span></div>
        <div class="premium-metric-content">
            <small>Credit Remaining</small>
            <strong style="color: #dc2626;"><?php echo $dashMoney($stats['credit_remaining'] ?? 0); ?></strong>
        </div>
    </div>
</div>

<div class="split-grid" style="margin-bottom:20px;">
    <section class="card sales-wide">
        <h3 style="margin-bottom:14px;">Unique Sales Graph (7 Days)</h3>
        <?php
            $maxSale = max(array_column($daily_sales, 'total')) ?: 1;
            $weekTotal = array_sum(array_map(function ($item) { return (float) ($item['total'] ?? 0); }, $daily_sales));
            $weekAvg = $weekTotal / max(1, count($daily_sales));
            $startSale = (float) ($daily_sales[0]['total'] ?? 0);
            $endSale = (float) ($daily_sales[count($daily_sales) - 1]['total'] ?? 0);
            $growthPercent = $startSale > 0 ? (($endSale - $startSale) / $startSale) * 100 : ($endSale > 0 ? 100 : 0);
        ?>
        <div class="sales-pro-shell">
            <div class="sales-pro-head">
                <div class="sales-kpi">
                    <span>Weekly Sales: <?php echo $dashMoney($weekTotal); ?></span>
                    <span>Daily Avg: <?php echo $dashMoney($weekAvg); ?></span>
                </div>
                <span class="badge <?php echo $growthPercent >= 0 ? 'badge-success' : 'badge-soft'; ?>">
                    Trend: <?php echo ($growthPercent >= 0 ? '+' : '') . number_format($growthPercent, 1); ?>%
                </span>
            </div>
            <div class="sales-bars-wrap">
                <div class="sales-bars">
                    <?php foreach ($daily_sales as $pointIndex => $point): ?>
                    <?php $height = max(14, (int) (((float) $point['total'] / $maxSale) * 200)); ?>
                    <div class="sales-day">
                        <div class="sales-amt"><?php echo $dashMoney($point['total']); ?></div>
                        <div class="sales-pill">
                            <div class="sales-fill"
                                style="height:<?php echo $height; ?>px; --i: <?php echo (int) $pointIndex; ?>;"></div>
                        </div>
                        <div class="sales-day-label"><?php echo Security::escape($point['label']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

</div>

<div class="split-grid" style="margin-bottom:20px;">
    <section class="card">
        <h3 style="margin-bottom:14px;">Payment Mix Meter</h3>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <?php foreach ($payment_mix as $mix): ?>
            <div>
                <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                    <strong><?php echo Security::escape($mix['label']); ?></strong>
                    <span><?php echo $dashMoney($mix['amount']); ?>
                        (<?php echo number_format($mix['percent'], 1); ?>%)</span>
                </div>
                <div class="mix-meter">
                    <div class="mix-fill"
                        style="width:<?php echo max(2, $mix['percent']); ?>%; background:<?php echo Security::escape($mix['color']); ?>;">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card">
        <h3 style="margin-bottom:14px;">Top Selling Items</h3>
        <?php if (empty($top_products)): ?>
        <div class="empty-state">No sales data available yet.</div>
        <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
            <?php foreach ($top_products as $index => $product): ?>
            <?php $rank = $index + 1; ?>
            <div
                style="padding:12px 14px; border:1px solid var(--border); border-radius:14px; background:<?php echo $rank <= 5 ? 'linear-gradient(135deg, rgba(245,158,11,0.14), rgba(59,130,246,0.08))' : '#fff'; ?>;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                    <div>
                        <strong><?php echo Security::escape($product['product_name']); ?></strong>
                        <div class="page-subtitle"><?php echo Security::escape($product['model_number'] ?: 'N/A'); ?>
                        </div>
                    </div>
                    <span class="badge <?php echo $rank <= 5 ? 'badge-success' : 'badge-soft'; ?>">#<?php echo $rank; ?>
                        Top Sale</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-top:8px;">
                    <span><?php echo (int) $product['qty_sold']; ?> units</span>
                    <strong><?php echo $dashMoney($product['total_amount']); ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
