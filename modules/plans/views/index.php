<?php ob_start(); ?>
<?php
$expiryDate = !empty($store['subscription_expires_at']) ? date('M d, Y h:i A', strtotime((string) $store['subscription_expires_at'])) : 'Not set';
$startDate  = !empty($store['subscription_started_at'])  ? date('M d, Y h:i A', strtotime((string) $store['subscription_started_at']))  : 'Not set';
$planStatus     = strtolower((string) ($store['status'] ?? 'active'));
$planName       = (string) ($store['plan_name'] ?? 'Free Plan');
$staffLimit     = (int) ($store['max_staff_limit'] ?? 0);
$productLimit   = (int) ($store['max_products_limit'] ?? 0);
$durationDays   = (int) ($store['billing_duration_days'] ?? 30);
$staffUsed      = (int) ($usage['staff'] ?? 0);
$productsUsed   = (int) ($usage['products'] ?? 0);
$branchesUsed   = (int) ($usage['branches'] ?? 0);
$staffPercent   = $staffLimit > 0 ? min(100, (int) round(($staffUsed   / $staffLimit)   * 100)) : 0;
$productPercent = $productLimit > 0 ? min(100, (int) round(($productsUsed / $productLimit) * 100)) : 0;
$statusClass    = $planStatus === 'active' ? 'badge-success' : ($planStatus === 'expired' ? 'badge-danger' : 'badge-warn');
$limitText      = fn($v) => $v > 0 ? number_format($v) : 'Unlimited';
$features = [
    ['icon' => 'point_of_sale',  'title' => 'POS Checkout',       'text' => 'Fast sales, returns, receipts, barcode scan and WhatsApp sharing.'],
    ['icon' => 'inventory_2',    'title' => 'Inventory Control',   'text' => $productLimit > 0 ? 'Manage up to '.number_format($productLimit).' products with stock, units and barcode labels.' : 'Manage unlimited products with stock, units and barcode labels.'],
    ['icon' => 'groups',         'title' => 'Staff Access',        'text' => $staffLimit > 0 ? 'Create up to '.number_format($staffLimit).' staff users with module permissions.' : 'Create unlimited staff users with module permissions.'],
    ['icon' => 'account_tree',   'title' => 'Branch Ready',        'text' => 'Create branch accounts that use this parent plan and keep branch data separate.'],
    ['icon' => 'credit_card',    'title' => 'Credit & Payments',   'text' => 'Track customer dues, reminders, partial payments and payment history.'],
    ['icon' => 'bar_chart',      'title' => 'Reports',             'text' => 'Review sales, profit, expenses and business performance reports.'],
];

/* ── Payment logo config ────────────────────────────────────── */
$payLogoMap = [
    'easypaisa' => '/assets/img/payment/easypaisa.png',
    'jazzcash'  => '/assets/img/payment/jazzcash.png',
    'nayapay'   => '/assets/img/payment/nayapay.png',
    'meezan'    => '/assets/img/payment/meezan.webp',
    'bank'      => '/assets/img/bank.svg',
];
/* Theme per logo key: [bg-from, bg-to, accent, glow] */
$payThemeMap = [
    'easypaisa' => ['#0d1f12', '#0e2a15', '#22c55e', 'rgba(34,197,94,.45)'],
    'jazzcash'  => ['#1a0a07', '#2a0e0a', '#ef4444', 'rgba(239,68,68,.45)'],
    'nayapay'   => ['#07101d', '#0a1428', '#3b82f6', 'rgba(59,130,246,.45)'],
    'meezan'    => ['#08161a', '#0a1e24', '#06b6d4', 'rgba(6,182,212,.42)'],
    'bank'      => ['#0d0f1a', '#111827', '#a78bfa', 'rgba(167,139,250,.40)'],
];
$defaultTheme = ['#0d0f1a', '#111827', '#64748b', 'rgba(100,116,139,.30)'];
?>

<style>
/* ── Plan page layout ───────────────────────── */
.plan-page  { display:grid; gap:18px; }
.plan-hero  { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(310px,.8fr); gap:18px; align-items:stretch; }
.plan-surface { border:1px solid var(--border); border-radius:20px; background:linear-gradient(150deg,#fff,#f8fbff); box-shadow:0 16px 34px rgba(15,23,42,.07); padding:20px; }
.plan-main-card { position:relative; overflow:hidden; min-height:300px; }
.plan-main-card::before { content:''; position:absolute; inset:0 0 auto 0; height:5px; background:linear-gradient(90deg,#0f6dff,#10b981,#f59e0b); }
.plan-head  { display:flex; justify-content:space-between; gap:14px; align-items:flex-start; flex-wrap:wrap; margin-bottom:18px; }
.plan-eyebrow { display:inline-flex; align-items:center; gap:7px; color:var(--primary); font-weight:900; font-size:13px; margin-bottom:10px; }
.plan-title { margin:0; font-size:34px; letter-spacing:0; line-height:1.1; }
.plan-price { margin-top:12px; display:flex; align-items:flex-end; gap:8px; flex-wrap:wrap; }
.plan-price strong { font-size:30px; color:#0f172a; line-height:1; }
.plan-price span   { color:var(--text-muted); font-weight:800; }
.plan-summary-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-top:18px; }
.plan-summary-card { border:1px solid var(--border); border-radius:16px; background:#fff; padding:14px; }
.plan-summary-card small,.plan-kpi small { display:block; color:var(--text-muted); font-size:12px; font-weight:900; margin-bottom:7px; }
.plan-summary-card strong,.plan-kpi strong { font-size:18px; color:#0f172a; }
.plan-kpi-grid { display:grid; gap:12px; }
.plan-kpi  { border:1px solid var(--border); border-radius:16px; background:#fff; padding:14px; }
.plan-progress { height:9px; border-radius:999px; background:#e2e8f0; overflow:hidden; margin-top:10px; }
.plan-progress span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#0f6dff,#10b981); }
.countdown-shell { border:1px solid rgba(15,109,255,.2); border-radius:18px; background:linear-gradient(135deg,rgba(15,109,255,.07),rgba(16,185,129,.08)); padding:16px; margin-top:18px; }
.countdown-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-top:12px; }
.countdown-box  { border:1px solid var(--border); border-radius:14px; background:#fff; padding:12px 8px; text-align:center; }
.countdown-box strong { display:block; font-size:24px; color:#0f172a; }
.countdown-box small  { color:var(--text-muted); font-size:12px; font-weight:800; }
.plan-section-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap; margin-bottom:14px; }
.feature-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
.feature-card { border:1px solid var(--border); border-radius:16px; background:#fff; padding:16px; }
.feature-icon { width:42px; height:42px; border-radius:14px; display:grid; place-items:center; background:rgba(15,109,255,.1); color:var(--primary); margin-bottom:12px; }
.feature-icon .material-symbols-rounded { font-size:22px; }
.feature-card h3 { font-size:16px; margin-bottom:7px; }
.feature-card p  { color:var(--text-muted); font-size:13px; font-weight:700; line-height:1.55; margin:0; }
.billing-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }

/* ── Pay Here button in header ──────────────── */
.pay-here-header-btn {
    display:inline-flex; align-items:center; gap:7px;
    background:linear-gradient(135deg,#0f6dff,#10b981);
    color:#fff; font-weight:800; font-size:14px; letter-spacing:.3px;
    border:none; border-radius:12px; padding:10px 20px; cursor:pointer;
    box-shadow:0 4px 16px rgba(15,109,255,.35);
    transition:transform .18s,box-shadow .18s;
    white-space:nowrap;
}
.pay-here-header-btn:hover { transform:translateY(-2px); box-shadow:0 8px 22px rgba(15,109,255,.50); }
.pay-here-header-btn .material-symbols-rounded { font-size:20px; }

/* ── Payment method picker modal ─────────────── */
.pay-methods-grid {
    display:grid; grid-template-columns:repeat(auto-fill, minmax(150px,1fr));
    gap:14px; padding:6px 0;
}
.pay-method-tile {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:10px; padding:18px 12px;
    border:2px solid var(--border); border-radius:18px; background:#fff;
    cursor:pointer; transition:all .22s; position:relative; overflow:hidden;
    text-align:center;
}
.pay-method-tile:hover { border-color:var(--tile-accent,#0f6dff); transform:translateY(-3px); box-shadow:0 8px 24px var(--tile-glow,rgba(15,109,255,.25)); }
.pay-method-tile img  { height:38px; width:auto; max-width:110px; object-fit:contain; }
.pay-method-tile .tile-name { font-size:13px; font-weight:800; color:#334155; }
.pay-method-tile .tile-type { font-size:11px; font-weight:700; color:#94a3b8; margin-top:-4px; }

/* ── Premium Payment Detail Card (dark) ─────── */
.pay-card-overlay {
    position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,.72); backdrop-filter:blur(6px);
    display:flex; align-items:center; justify-content:center;
    padding:20px; opacity:0; pointer-events:none;
    transition:opacity .28s;
}
.pay-card-overlay.visible { opacity:1; pointer-events:all; }
.pay-card-dark {
    width:min(460px,100%); border-radius:28px; padding:28px 26px 24px;
    position:relative; overflow:hidden;
    background:linear-gradient(160deg, var(--pc-from,#0d1f12), var(--pc-to,#0e2a15));
    box-shadow:0 32px 80px var(--pc-glow,rgba(34,197,94,.35)),
               0 0 0 1px rgba(255,255,255,.08);
    animation:pcSlideIn .32s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes pcSlideIn { from{ transform:translateY(22px) scale(.97); opacity:0; } to{ transform:none; opacity:1; } }

/* mesh grid bg */
.pay-card-dark::before {
    content:''; position:absolute; inset:0;
    background-image:
        linear-gradient(var(--pc-accent,#22c55e) 1px, transparent 1px),
        linear-gradient(90deg, var(--pc-accent,#22c55e) 1px, transparent 1px);
    background-size:40px 40px;
    opacity:.035; pointer-events:none;
}
/* glow blob */
.pay-card-dark::after {
    content:''; position:absolute;
    width:260px; height:260px; top:-80px; right:-80px;
    border-radius:50%;
    background:radial-gradient(circle, var(--pc-accent,#22c55e), transparent 70%);
    opacity:.18; pointer-events:none;
}

.pc-close {
    position:absolute; top:16px; right:16px; z-index:2;
    background:rgba(255,255,255,.1); border:none; border-radius:50%;
    width:36px; height:36px; cursor:pointer; display:grid; place-items:center;
    color:#fff; transition:.18s;
}
.pc-close:hover { background:rgba(255,255,255,.2); transform:scale(1.12); }
.pc-close .material-symbols-rounded { font-size:20px; }

.pc-logo-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; position:relative; z-index:2; }
.pc-logo { height:30px; max-width:130px; object-fit:contain; filter:brightness(1.1); }
.pc-secure { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:800; letter-spacing:.8px; color:var(--pc-accent,#22c55e); background:rgba(255,255,255,.08); padding:4px 10px; border-radius:999px; border:1px solid var(--pc-accent,#22c55e); }

.pc-headline { position:relative; z-index:2; margin-bottom:6px; }
.pc-headline h2 { font-size:28px; font-weight:400; color:rgba(255,255,255,.82); margin:0 0 2px 0; }
.pc-headline h2 span { font-weight:900; color:var(--pc-accent,#22c55e); display:block; }
.pc-sub { font-size:13px; color:rgba(255,255,255,.5); margin:0 0 18px 0; position:relative; z-index:2; }

.pc-amount-badge {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.14);
    border-radius:12px; padding:10px 16px; margin-bottom:20px;
    position:relative; z-index:2;
}
.pc-amount-badge .label { font-size:12px; font-weight:700; color:rgba(255,255,255,.55); display:block; }
.pc-amount-badge .value { font-size:22px; font-weight:900; color:#fff; display:block; line-height:1.1; }

.pc-field {
    display:flex; align-items:center; gap:12px;
    background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.1);
    border-radius:14px; padding:12px 14px; margin-bottom:10px;
    position:relative; z-index:2;
}
.pc-field-icon {
    width:36px; height:36px; border-radius:10px;
    background:rgba(255,255,255,.1); display:grid; place-items:center;
    flex-shrink:0;
}
.pc-field-icon .material-symbols-rounded { font-size:18px; color:var(--pc-accent,#22c55e); }
.pc-field-info { flex:1; min-width:0; }
.pc-field-info small { font-size:11px; font-weight:700; color:rgba(255,255,255,.45); display:block; margin-bottom:3px; }
.pc-field-info strong { font-size:15px; font-weight:800; color:#fff; display:block; letter-spacing:.3px; }
.pc-copy-btn {
    background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.14);
    border-radius:8px; padding:6px; cursor:pointer; color:rgba(255,255,255,.7);
    flex-shrink:0; transition:.18s; display:grid; place-items:center;
}
.pc-copy-btn:hover { background:rgba(255,255,255,.2); color:#fff; }
.pc-copy-btn .material-symbols-rounded { font-size:16px; }

.pc-instruction {
    display:flex; align-items:center; gap:12px;
    background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.1);
    border-radius:14px; padding:12px 14px;
    position:relative; z-index:2; margin-top:4px;
}
.pc-instruction .material-symbols-rounded { font-size:20px; color:var(--pc-accent,#22c55e); flex-shrink:0; }
.pc-instruction p { margin:0; font-size:13px; color:rgba(255,255,255,.65); line-height:1.5; flex:1; }
.pc-instruction .wa-icon { width:28px; height:28px; border-radius:50%; background:var(--pc-accent,#22c55e); display:grid; place-items:center; flex-shrink:0; }
.pc-instruction .wa-icon .material-symbols-rounded { font-size:16px; color:#fff; }

/* ── Responsive ─────────────────────────────── */
@media (max-width:1100px) {
    .plan-hero, .feature-grid, .billing-grid { grid-template-columns:1fr; }
    .plan-summary-grid, .countdown-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
}
@media (max-width:560px) {
    .plan-title { font-size:28px; }
    .plan-summary-grid, .countdown-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:9px; }
    .plan-surface { padding:16px; border-radius:18px; }
    .pay-card-dark { padding:22px 18px 20px; }
    .pc-headline h2 { font-size:24px; }
    .pay-methods-grid { grid-template-columns:repeat(2,1fr); }
}

.quill-instructions-rendered p  { margin:0 0 5px 0; }
.quill-instructions-rendered ul,.quill-instructions-rendered ol { margin:0 0 5px 0; padding-left:18px; }
</style>

<!-- PAGE HEADER with Pay Here and Payment History Buttons -->
<div class="page-header">
    <div>
        <h1>Plan Status</h1>
        <p class="page-subtitle">Your active plan, usage limits, features and billing timeline.</p>
    </div>
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/history" class="btn btn-secondary" style="border-radius:12px; font-weight:700; height:42px; padding:0 16px; display:inline-flex; align-items:center; gap:6px; font-size:13.5px;">
            <span class="material-symbols-rounded" style="font-size:19px;">receipt_long</span> History
        </a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/pay" class="pay-here-header-btn">
            <span class="material-symbols-rounded">payments</span> Pay Here
        </a>
    </div>
</div>

<div class="plan-page">
    <?php
    $showInstallmentWarning = false;
    $daysLeftInstallment = 0;
    if (!empty($store['next_monthly_charge_date'])) {
        $due = strtotime($store['next_monthly_charge_date'] . ' 23:59:59');
        $now = time();
        $diff = $due - $now;
        $daysLeftInstallment = (int) ceil($diff / 86400);
        if ($daysLeftInstallment >= 0 && $daysLeftInstallment <= 2) {
            $showInstallmentWarning = true;
        }
    }
    ?>
    <?php if ($showInstallmentWarning): ?>
    <div style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.12), rgba(245, 158, 11, 0.08)); border: 1px solid rgba(239, 68, 68, 0.22); border-radius: 16px; padding: 16px 20px; display: flex; align-items: center; gap: 14px; margin-bottom: 20px; animation: bannerPulse 2.5s infinite ease-in-out;">
        <span class="material-symbols-rounded" style="color: #ef4444; font-size: 26px;">warning</span>
        <div style="flex: 1;">
            <strong style="color: #ef4444; font-size: 15px; display: block;">Upcoming Installment Due!</strong>
            <span style="color: var(--text-muted); font-size: 13px; font-weight: 700;">
                Your monthly subscription installment is due in <strong><?php echo $daysLeftInstallment == 0 ? 'today' : ($daysLeftInstallment == 1 ? '1 day' : $daysLeftInstallment . ' days'); ?></strong> on <strong><?php echo date('M d, Y', strtotime($store['next_monthly_charge_date'])); ?></strong>. Please complete the payment to avoid interruption.
            </span>
        </div>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/pay" class="btn btn-primary" style="background: #ef4444; border-color: #ef4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2); font-size: 13px; padding: 8px 16px; display: inline-flex; align-items: center; justify-content: center; height: 36px; text-decoration: none;">Pay Now</a>
    </div>
    <style>
    @keyframes bannerPulse {
        0% { transform: scale(1); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.05); }
        50% { transform: scale(1.005); box-shadow: 0 8px 20px rgba(239, 68, 68, 0.12); }
        100% { transform: scale(1); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.05); }
    }
    </style>
    <?php endif; ?>

    <div class="plan-hero">
        <!-- Main Plan Card -->
        <section class="plan-surface plan-main-card">
            <div class="plan-head">
                <div>
                    <div class="plan-eyebrow"><span class="material-symbols-rounded">workspace_premium</span>Current Subscription</div>
                    <h2 class="plan-title"><?php echo Security::escape($planName); ?></h2>
                    <div class="plan-price">
                        <strong><?php echo App::money((float) ($store['monthly_price'] ?? 0)); ?></strong>
                        <span>/ month</span>
                    </div>
                </div>
                <span class="badge <?php echo $statusClass; ?>"><?php echo Security::escape(ucfirst($planStatus)); ?></span>
            </div>

            <div class="plan-summary-grid">
                <div class="plan-summary-card"><small>Billing Cycle</small><strong><?php echo $durationDays; ?> Days</strong></div>
                <div class="plan-summary-card"><small>Staff Limit</small><strong><?php echo Security::escape($limitText($staffLimit)); ?></strong></div>
                <div class="plan-summary-card"><small>Product Limit</small><strong><?php echo Security::escape($limitText($productLimit)); ?></strong></div>
            </div>

            <?php if (!empty($store['next_monthly_charge_date'])): ?>
            <div style="margin-top:16px; padding:14px 18px; border-radius:16px; background:linear-gradient(135deg, rgba(15, 109, 255, 0.05), rgba(16, 185, 129, 0.05)); border:1px dashed rgba(15, 109, 255, 0.2); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                    <span style="display:block; font-size:11px; font-weight:900; color:var(--primary); text-transform:uppercase; letter-spacing:0.5px;">Next Monthly Installment</span>
                    <strong style="font-size:18px; color:#0f172a; display:block; margin-top:3px;"><?php echo date('M d, Y', strtotime($store['next_monthly_charge_date'])); ?></strong>
                </div>
                <div style="text-align:right;">
                    <span style="display:block; font-size:11px; font-weight:900; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Installment Amount</span>
                    <strong style="font-size:18px; color:#10b981; display:block; margin-top:3px;"><?php echo App::money((float) ($store['monthly_charge_amount'] ?? $store['monthly_price'] ?? 0)); ?></strong>
                </div>
            </div>
            <?php endif; ?>

            <div class="countdown-shell" data-plan-expiry="<?php echo Security::escape((string) ($store['subscription_expires_at'] ?? '')); ?>">
                <strong>Live Expiry Countdown</strong>
                <div class="page-subtitle">Your plan expires on <?php echo Security::escape($expiryDate); ?></div>
                <div class="countdown-grid">
                    <div class="countdown-box"><strong data-time-days><?php echo (int) $remainingDays; ?></strong><small>Days</small></div>
                    <div class="countdown-box"><strong data-time-hours>00</strong><small>Hours</small></div>
                    <div class="countdown-box"><strong data-time-mins>00</strong><small>Minutes</small></div>
                    <div class="countdown-box"><strong data-time-secs>00</strong><small>Seconds</small></div>
                </div>
            </div>
        </section>

        <!-- Usage -->
        <aside class="plan-surface">
            <div class="plan-section-head">
                <div>
                    <h3>Usage Overview</h3>
                    <p class="page-subtitle">Live usage against your current plan.</p>
                </div>
            </div>
            <div class="plan-kpi-grid">
                <div class="plan-kpi"><small>Cycle Consumed</small><strong><?php echo (int) $progressPercent; ?>%</strong><div class="plan-progress"><span style="width:<?php echo (int) $progressPercent; ?>%;"></span></div></div>
                <div class="plan-kpi"><small>Staff Used</small><strong><?php echo number_format($staffUsed); ?> / <?php echo Security::escape($limitText($staffLimit)); ?></strong><div class="plan-progress"><span style="width:<?php echo $staffLimit > 0 ? $staffPercent : 100; ?>%;"></span></div></div>
                <div class="plan-kpi"><small>Products Used</small><strong><?php echo number_format($productsUsed); ?> / <?php echo Security::escape($limitText($productLimit)); ?></strong><div class="plan-progress"><span style="width:<?php echo $productLimit > 0 ? $productPercent : 100; ?>%;"></span></div></div>
                <div class="plan-kpi"><small>Branch Accounts</small><strong><?php echo number_format($branchesUsed); ?></strong><div class="page-subtitle" style="margin-top:6px;">Branches inherit this parent plan.</div></div>
            </div>
        </aside>
    </div>

    <!-- Features -->
    <section class="plan-surface">
        <div class="plan-section-head"><div><h3>What This Plan Includes</h3><p class="page-subtitle">Features available in your current subscription.</p></div></div>
        <div class="feature-grid">
            <?php foreach ($features as $feature): ?>
                <article class="feature-card">
                    <div class="feature-icon"><span class="material-symbols-rounded"><?php echo Security::escape($feature['icon']); ?></span></div>
                    <h3><?php echo Security::escape($feature['title']); ?></h3>
                    <p><?php echo Security::escape($feature['text']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Billing Details -->
    <section class="plan-surface">
        <div class="plan-section-head"><div><h3>Billing Details</h3><p class="page-subtitle">Important dates and last payment information.</p></div></div>
        <div class="billing-grid">
            <div class="plan-summary-card"><small>Store Name</small><strong><?php echo Security::escape((string) ($store['store_name'] ?? '-')); ?></strong></div>
            <div class="plan-summary-card"><small>Start Date</small><strong><?php echo Security::escape($startDate); ?></strong></div>
            <div class="plan-summary-card"><small>Expiry Date</small><strong><?php echo Security::escape($expiryDate); ?></strong></div>
            <div class="plan-summary-card"><small>Last Payment</small><strong><?php echo !empty($latestPayment['amount']) ? App::money((float) $latestPayment['amount']) : 'No payment yet'; ?></strong></div>
            <?php if (!empty($store['next_monthly_charge_date'])): ?>
            <div class="plan-summary-card"><small>Next Installment Date</small><strong><?php echo date('Y-m-d', strtotime($store['next_monthly_charge_date'])); ?></strong></div>
            <div class="plan-summary-card"><small>Installment Amount</small><strong><?php echo App::money((float) ($store['monthly_charge_amount'] ?? $store['monthly_price'] ?? 0)); ?></strong></div>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- ══════════════════════════════════════════════════════════════
     PAY METHODS PICKER MODAL
══════════════════════════════════════════════════════════════ -->
<div id="payMethodsPicker" class="modal-backdrop" style="display:none;">
    <div class="modal-card" style="width:min(580px,100%); max-height:85vh; overflow-y:auto;">
        <div class="modal-header">
            <h3>Choose Payment Method</h3>
            <button class="icon-btn" type="button" data-close-modal-target="payMethodsPicker"><span class="material-symbols-rounded">close</span></button>
        </div>

        <div style="background:linear-gradient(135deg,rgba(15,109,255,.06),rgba(16,185,129,.06)); border:1px solid rgba(15,109,255,.14); border-radius:14px; padding:14px 16px; margin-bottom:20px;">
            <div style="font-size:11px; font-weight:800; color:var(--primary); letter-spacing:.8px; text-transform:uppercase; margin-bottom:4px;">Amount Due This Month</div>
            <div style="font-size:26px; font-weight:900; color:#0f172a;"><?php echo App::money((float) ($store['monthly_price'] ?? 0)); ?> <span style="font-size:13px; font-weight:600; color:#64748b;">/ month</span></div>
            <div style="font-size:12px; color:#94a3b8; margin-top:3px;">Plan expires: <?php echo Security::escape($expiryDate); ?></div>
        </div>

        <p style="font-size:13px; font-weight:700; color:#64748b; margin:0 0 14px 0;">Select a payment method below to view complete details:</p>

        <?php if (empty($paymentMethods)): ?>
            <div class="empty-state">No payment methods configured. Please contact the administrator.</div>
        <?php else: ?>
            <div class="pay-methods-grid">
                <?php foreach ($paymentMethods as $m):
                    $lk  = strtolower((string) ($m['method_logo'] ?? ''));
                    $thm = $payThemeMap[$lk] ?? $defaultTheme;
                    $logoUrl = isset($payLogoMap[$lk]) ? ((defined('BASE_URL') ? BASE_URL : '') . $payLogoMap[$lk]) : '';
                    $dataTheme = htmlspecialchars(json_encode([
                        'from'   => $thm[0], 'to'     => $thm[1],
                        'accent' => $thm[2], 'glow'   => $thm[3],
                        'name'   => $m['method_name'],
                        'type'   => $m['method_type'],
                        'holder' => $m['account_holder'] ?? '',
                        'number' => $m['account_number'] ?? '',
                        'instructions' => strip_tags((string)($m['instructions'] ?? '')),
                        'logo'   => $logoUrl,
                        'key'    => $lk,
                        'amount' => App::money((float) ($store['monthly_price'] ?? 0)),
                    ]), ENT_QUOTES);
                ?>
                    <button class="pay-method-tile" type="button"
                            style="--tile-accent:<?php echo $thm[2]; ?>; --tile-glow:<?php echo $thm[3]; ?>;"
                            onclick="openPayCard(this)" data-pay='<?php echo $dataTheme; ?>'>
                        <?php if ($logoUrl): ?>
                            <img src="<?php echo $logoUrl; ?>" alt="<?php echo Security::escape($m['method_name']); ?>">
                        <?php else: ?>
                            <span class="material-symbols-rounded" style="font-size:32px; color:<?php echo $thm[2]; ?>;">account_balance</span>
                        <?php endif; ?>
                        <div>
                            <div class="tile-name"><?php echo Security::escape($m['method_name']); ?></div>
                            <div class="tile-type"><?php echo Security::escape(ucfirst($m['method_type'])); ?></div>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     PREMIUM PAY CARD OVERLAY (dark themed)
══════════════════════════════════════════════════════════════ -->
<div id="payCardOverlay" class="pay-card-overlay" onclick="handleOverlayClick(event)">
    <div class="pay-card-dark" id="payCardDark" onclick="event.stopPropagation()">
        <button class="pc-close" onclick="closePayCard()"><span class="material-symbols-rounded">close</span></button>

        <!-- Logo row -->
        <div class="pc-logo-row">
            <img id="pc-logo" class="pc-logo" src="" alt="" style="display:none;">
            <span id="pc-logo-text" style="font-size:18px; font-weight:900; color:#fff; display:none;"></span>
            <span class="pc-secure"><span class="material-symbols-rounded" style="font-size:13px;">verified_user</span> SECURE PAYMENT</span>
        </div>

        <!-- Headline -->
        <div class="pc-headline">
            <h2>Pay with <span id="pc-name-accent">Method</span></h2>
        </div>
        <p class="pc-sub" id="pc-sub-text">Use the account details below to complete your payment.</p>

        <!-- Amount -->
        <div class="pc-amount-badge">
            <div><span class="label">Amount Due</span><span class="value" id="pc-amount">—</span></div>
        </div>

        <!-- Account Holder -->
        <div class="pc-field" id="pc-holder-row" style="display:none;">
            <div class="pc-field-icon"><span class="material-symbols-rounded">person</span></div>
            <div class="pc-field-info">
                <small>Account Holder Name</small>
                <strong id="pc-holder-val"></strong>
            </div>
            <button class="pc-copy-btn" onclick="copyText('pc-holder-val', this)" title="Copy">
                <span class="material-symbols-rounded">content_copy</span>
            </button>
        </div>

        <!-- Account Number -->
        <div class="pc-field" id="pc-number-row" style="display:none;">
            <div class="pc-field-icon"><span class="material-symbols-rounded">credit_card</span></div>
            <div class="pc-field-info">
                <small id="pc-number-label">Account Number</small>
                <strong id="pc-number-val"></strong>
            </div>
            <button class="pc-copy-btn" onclick="copyText('pc-number-val', this)" title="Copy">
                <span class="material-symbols-rounded">content_copy</span>
            </button>
        </div>

        <!-- Instruction -->
        <div class="pc-instruction" id="pc-instructions-row" style="display:none;">
            <span class="material-symbols-rounded">verified_user</span>
            <p id="pc-instructions-val"></p>
            <div class="wa-icon"><span class="material-symbols-rounded">chat</span></div>
        </div>

    </div>
</div>

<?php ob_start(); ?>
<script>
/* ── Pay Methods Picker ───────────────────────────────── */
function openPayMethodsPicker() {
    const el = document.getElementById('payMethodsPicker');
    if (el) { el.style.display = 'flex'; document.body.classList.add('modal-open'); }
}

/* ── Premium Pay Card ────────────────────────────────── */
function openPayCard(btn) {
    const d = JSON.parse(btn.getAttribute('data-pay'));

    // Close picker
    const picker = document.getElementById('payMethodsPicker');
    if (picker) picker.style.display = 'none';

    const card    = document.getElementById('payCardDark');
    const overlay = document.getElementById('payCardOverlay');

    // Apply CSS vars
    card.style.setProperty('--pc-from',   d.from);
    card.style.setProperty('--pc-to',     d.to);
    card.style.setProperty('--pc-accent', d.accent);
    card.style.setProperty('--pc-glow',   d.glow);

    // Logo
    const logoImg  = document.getElementById('pc-logo');
    const logoText = document.getElementById('pc-logo-text');
    if (d.logo) {
        logoImg.src = d.logo; logoImg.alt = d.name;
        logoImg.style.display = 'block'; logoText.style.display = 'none';
    } else {
        logoImg.style.display = 'none';
        logoText.textContent  = d.name; logoText.style.display = 'block';
        logoText.style.color  = d.accent;
    }

    // Name / headline accent
    document.getElementById('pc-name-accent').textContent = d.name;
    document.getElementById('pc-name-accent').style.color  = d.accent;

    // Sub text
    document.getElementById('pc-sub-text').textContent =
        d.type === 'manual'
            ? 'Use the account details below to complete your payment.'
            : 'Proceed through the gateway to complete your payment.';

    // Amount
    document.getElementById('pc-amount').textContent = d.amount;

    // Holder
    const holderRow = document.getElementById('pc-holder-row');
    if (d.holder) { document.getElementById('pc-holder-val').textContent = d.holder; holderRow.style.display = 'flex'; }
    else { holderRow.style.display = 'none'; }

    // Number
    const numRow = document.getElementById('pc-number-row');
    if (d.number) {
        document.getElementById('pc-number-val').textContent = d.number;
        const lk = d.key || '';
        document.getElementById('pc-number-label').textContent =
            lk === 'bank'    ? 'IBAN / Account Number' :
            lk === 'meezan'  ? 'Meezan Account Number' :
            (d.name + ' Account Number');
        numRow.style.display = 'flex';
    } else { numRow.style.display = 'none'; }

    // Instructions
    const instRow = document.getElementById('pc-instructions-row');
    if (d.instructions) { document.getElementById('pc-instructions-val').textContent = d.instructions; instRow.style.display = 'flex'; }
    else { instRow.style.display = 'none'; }

    // Field icon colors
    card.querySelectorAll('.pc-field-icon .material-symbols-rounded').forEach(ic => ic.style.color = d.accent);
    card.querySelector('#pc-instructions-row > .material-symbols-rounded').style.color = d.accent;
    card.querySelector('.wa-icon').style.background = d.accent;
    card.querySelector('.pc-secure').style.color       = d.accent;
    card.querySelector('.pc-secure').style.borderColor = d.accent;

    overlay.classList.add('visible');
    document.body.classList.add('modal-open');
}

function closePayCard() {
    document.getElementById('payCardOverlay').classList.remove('visible');
    document.body.classList.remove('modal-open');
}
function handleOverlayClick(e) { if (e.target === e.currentTarget) closePayCard(); }

/* Copy to clipboard */
function copyText(elId, btn) {
    const text = document.getElementById(elId)?.textContent?.trim() || '';
    navigator.clipboard.writeText(text).then(() => {
        const ic = btn.querySelector('.material-symbols-rounded');
        ic.textContent = 'check'; ic.style.color = '#22c55e';
        setTimeout(() => { ic.textContent = 'content_copy'; ic.style.color = ''; }, 1800);
    });
}

/* ── Countdown timer ────────────────────────── */
(() => {
    const shell = document.querySelector('[data-plan-expiry]');
    if (!shell) return;
    const expiryRaw = shell.getAttribute('data-plan-expiry') || '';
    if (!expiryRaw) return;
    const daysEl  = shell.querySelector('[data-time-days]');
    const hoursEl = shell.querySelector('[data-time-hours]');
    const minsEl  = shell.querySelector('[data-time-mins]');
    const secsEl  = shell.querySelector('[data-time-secs]');
    if (!daysEl || !hoursEl || !minsEl || !secsEl) return;
    const toTwo   = n => String(Math.max(0, n)).padStart(2, '0');
    const expMs   = new Date(expiryRaw.replace(' ','T')).getTime();
    if (!Number.isFinite(expMs)) return;
    const tick = () => {
        let diff = Math.floor((expMs - Date.now()) / 1000);
        if (diff < 0) diff = 0;
        daysEl.textContent  = Math.floor(diff / 86400);
        hoursEl.textContent = toTwo(Math.floor((diff % 86400) / 3600));
        minsEl.textContent  = toTwo(Math.floor((diff % 3600) / 60));
        secsEl.textContent  = toTwo(diff % 60);
    };
    tick();
    window.setInterval(tick, 1000);
})();
</script>
<?php $extraJs = ob_get_clean(); ?>

<!-- Floating Draggable WhatsApp Button (Only on Plan Status Page) -->
<div id="draggable-wa-btn" class="draggable-wa-btn" style="position: fixed; bottom: 80px; right: 24px; z-index: 10000; cursor: move; touch-action: none;">
    <?php
    $storeNameRaw = $store['store_name'] ?? 'My Store';
    $waText = "Hello DevAltum Team, I would like to request plan activation/payment confirmation for my store: " . $storeNameRaw . ". Please assist. Thank you!";
    $waUrl = "https://wa.me/923101648600?text=" . urlencode($waText);
    ?>
    <a id="draggable-wa-link" href="<?php echo $waUrl; ?>" target="_blank" class="wa-float-link" onclick="preventClickIfDragged(event)">
        <img src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/img/whatsapp.png" alt="WhatsApp Support" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
        <span class="wa-float-glow"></span>
    </a>
</div>

<style>
.wa-float-link {
    width: 74px;
    height: 74px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 32px rgba(37, 211, 102, 0.45);
    transition: transform 0.2s;
    animation: waFloatPulse 2.5s infinite ease-in-out;
    position: relative;
    background: transparent;
}
.wa-float-link:hover {
    transform: scale(1.08);
}
.wa-float-glow {
    position: absolute;
    inset: -7px;
    border-radius: 50%;
    border: 2px solid #25d366;
    opacity: 0;
    animation: waFloatGlowEffect 2.5s infinite ease-in-out;
    pointer-events: none;
}
@keyframes waFloatPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.06); }
    100% { transform: scale(1); }
}
@keyframes waFloatGlowEffect {
    0% { transform: scale(0.95); opacity: 0.8; }
    100% { transform: scale(1.3); opacity: 0; }
}
</style>

<script>
(() => {
    const btn = document.getElementById('draggable-wa-btn');
    if (!btn) return;

    let isDragging = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let buttonStartX = 0;
    let buttonStartY = 0;
    let hasMoved = false;

    window.preventClickIfDragged = (e) => {
        if (hasMoved) {
            e.preventDefault();
        }
    };

    const onStart = (clientX, clientY) => {
        isDragging = true;
        hasMoved = false;
        dragStartX = clientX;
        dragStartY = clientY;
        const rect = btn.getBoundingClientRect();
        buttonStartX = rect.left;
        buttonStartY = rect.top;
        btn.style.bottom = 'auto';
        btn.style.right = 'auto';
        btn.style.left = buttonStartX + 'px';
        btn.style.top = buttonStartY + 'px';
    };

    const onMove = (clientX, clientY) => {
        if (!isDragging) return;
        const dx = clientX - dragStartX;
        const dy = clientY - dragStartY;
        if (Math.abs(dx) > 5 || Math.abs(dy) > 5) {
            hasMoved = true;
        }
        let newX = buttonStartX + dx;
        let newY = buttonStartY + dy;

        const maxW = window.innerWidth - btn.offsetWidth - 10;
        const maxH = window.innerHeight - btn.offsetHeight - 10;
        newX = Math.max(10, Math.min(newX, maxW));
        newY = Math.max(10, Math.min(newY, maxH));

        btn.style.left = newX + 'px';
        btn.style.top = newY + 'px';
    };

    const onEnd = () => {
        isDragging = false;
    };

    btn.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        onStart(e.clientX, e.clientY);
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    });

    const onMouseMove = (e) => {
        onMove(e.clientX, e.clientY);
    };

    const onMouseUp = () => {
        onEnd();
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    };

    btn.addEventListener('touchstart', (e) => {
        if (e.touches.length === 0) return;
        const touch = e.touches[0];
        onStart(touch.clientX, touch.clientY);
    }, { passive: true });

    btn.addEventListener('touchmove', (e) => {
        if (e.touches.length === 0) return;
        const touch = e.touches[0];
        onMove(touch.clientX, touch.clientY);
    }, { passive: true });

    btn.addEventListener('touchend', () => {
        onEnd();
    });
})();
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
