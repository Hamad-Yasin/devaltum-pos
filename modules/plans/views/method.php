<?php ob_start(); ?>
<?php
$lk = strtolower((string) ($method['method_logo'] ?? ''));

$payLogoMap = [
    'easypaisa' => '/assets/img/payment/easypaisa.png',
    'jazzcash'  => '/assets/img/payment/jazzcash.png',
    'nayapay'   => '/assets/img/payment/nayapay.png',
    'meezan'    => '/assets/img/payment/meezan.webp',
    'bank'      => '/assets/img/bank.svg',
];
/* [bg-from, bg-to, accent, glow, grid-opacity] */
$payThemeMap = [
    'easypaisa' => ['#0b1f10', '#0e2a16', '#22c55e', 'rgba(34,197,94,.55)',   '.04'],
    'jazzcash'  => ['#1a0905', '#2a0f08', '#ef4444', 'rgba(239,68,68,.55)',    '.04'],
    'nayapay'   => ['#060e1c', '#091528', '#3b82f6', 'rgba(59,130,246,.55)',   '.04'],
    'meezan'    => ['#061419', '#091e24', '#06b6d4', 'rgba(6,182,212,.50)',    '.04'],
    'bank'      => ['#0b0d18', '#111827', '#a78bfa', 'rgba(167,139,250,.48)', '.04'],
];
$defaultTheme = ['#0d0f1a', '#111827', '#64748b', 'rgba(100,116,139,.35)', '.03'];

$thm     = $payThemeMap[$lk] ?? $defaultTheme;
$logoUrl = isset($payLogoMap[$lk]) ? ((defined('BASE_URL') ? BASE_URL : '') . $payLogoMap[$lk]) : '';

$planName   = (string) ($store['plan_name']    ?? 'Subscription');
$amount     = App::money((float) ($store['monthly_price'] ?? 0));
$expiryDate = !empty($store['subscription_expires_at'])
    ? date('M d, Y', strtotime((string) $store['subscription_expires_at']))
    : 'Not set';
$hasHolder = !empty($method['account_holder']);
$hasNumber = !empty($method['account_number']);
$hasInstructions = !empty($method['instructions']);

$cleanAdminWa = preg_replace('/[^0-9]/', '', (string) ($adminWhatsapp ?: '923101648600'));
if (str_starts_with($cleanAdminWa, '0')) {
    $cleanAdminWa = '92' . substr($cleanAdminWa, 1);
}

$storeTitle = trim((string) ($store['store_name'] ?? ''));
if (empty($storeTitle) && Auth::check()) {
    $u = Auth::user();
    $storeTitle = trim((string) ($u['store_name'] ?? ''));
}
if (empty($storeTitle)) {
    $db = Database::getInstance();
    $sRow = $db->fetch("SELECT store_name FROM stores ORDER BY id ASC LIMIT 1");
    $storeTitle = trim((string) ($sRow['store_name'] ?? 'Shaheen Trader'));
}
$dateTimeStr = date('d M Y, h:i A');

$waPaymentText = "Assalam o Alaikum Super Admin,\n\n"
    . "Maine POS subscription payment complete kar di hai.\n\n"
    . "*Store:* " . $storeTitle . "\n"
    . "*Plan:* " . $planName . " Plan\n"
    . "*Amount Paid:* " . $amount . "\n"
    . "*Payment Method:* " . ($method['method_name'] ?? 'Payment') . "\n"
    . "*Date & Time:* " . $dateTimeStr . "\n\n"
    . "Barah-e-karam payment verify karke mera account activate kar dein.\n"
    . "Shukriya!";

$waPaymentUrl = "https://wa.me/" . $cleanAdminWa . "?text=" . urlencode($waPaymentText);
?>

<style>
/* ── Full-screen dark payment page ─────────── */
.pm-outer {
    min-height: calc(100vh - 120px);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 10px 0 40px;
}
.pm-page {
    width: min(520px, 100%);
    display: flex;
    flex-direction: column;
    gap: 0;
}

/* ── Back bar ──────────────────────────────── */
.pm-back-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}
.pm-back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    text-decoration: none;
    padding: 7px 14px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: #fff;
    transition: .18s;
}
.pm-back-link:hover { background: #f1f5f9; color: #0f172a; }
.pm-back-link .material-symbols-rounded { font-size: 16px; }
.pm-plan-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 800;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid var(--border);
    border-radius: 999px;
    padding: 5px 14px;
}
.pm-plan-chip .material-symbols-rounded { font-size: 14px; color: #f59e0b; }

/* ── Dark payment card ─────────────────────── */
.pm-card {
    border-radius: 28px;
    padding: 30px 28px 26px;
    background: linear-gradient(160deg, var(--pm-from), var(--pm-to));
    box-shadow:
        0 40px 90px var(--pm-glow),
        0 0 0 1px rgba(255,255,255,.07);
    position: relative;
    overflow: hidden;
    animation: pmIn .38s cubic-bezier(.34,1.46,.64,1) both;
}
@keyframes pmIn { from { opacity:0; transform:translateY(20px) scale(.97); } to { opacity:1; transform:none; } }

/* mesh grid texture */
.pm-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(var(--pm-accent) 1px, transparent 1px),
        linear-gradient(90deg, var(--pm-accent) 1px, transparent 1px);
    background-size: 36px 36px;
    opacity: var(--pm-grid-opacity, .04);
    pointer-events: none;
}
/* glow blob top-right */
.pm-card::after {
    content: '';
    position: absolute;
    width: 280px; height: 280px;
    right: -90px; top: -90px;
    border-radius: 50%;
    background: radial-gradient(circle, var(--pm-accent), transparent 65%);
    opacity: .16;
    pointer-events: none;
}
/* glow blob bottom-left */
.pm-blob2 {
    position: absolute;
    width: 200px; height: 200px;
    left: -60px; bottom: -70px;
    border-radius: 50%;
    background: radial-gradient(circle, var(--pm-accent), transparent 65%);
    opacity: .10;
    pointer-events: none;
}

/* ── Card top row ──────────────────────────── */
.pm-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    position: relative; z-index: 2;
}
.pm-logo { height: 32px; max-width: 140px; object-fit: contain; filter: brightness(1.1); }
.pm-logo-text { font-size: 20px; font-weight: 900; color: var(--pm-accent); }
.pm-secure {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .8px;
    text-transform: uppercase;
    color: var(--pm-accent);
    background: rgba(255,255,255,.07);
    border: 1px solid var(--pm-accent);
    border-radius: 999px;
    padding: 4px 10px;
}
.pm-secure .material-symbols-rounded { font-size: 12px; }

/* ── Headline ──────────────────────────────── */
.pm-headline {
    position: relative; z-index: 2;
    margin-bottom: 8px;
}
.pm-headline .light { font-size: 28px; font-weight: 300; color: rgba(255,255,255,.75); line-height: 1.2; display: block; }
.pm-headline .bold  { font-size: 34px; font-weight: 900; color: var(--pm-accent); line-height: 1.1; display: block; }
.pm-sub { font-size: 13px; color: rgba(255,255,255,.45); margin: 0 0 22px 0; position: relative; z-index: 2; }

/* ── Amount badge ─────────────────────────── */
.pm-amount-badge {
    display: inline-flex;
    align-items: center;
    gap: 14px;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.13);
    border-radius: 16px;
    padding: 13px 18px;
    margin-bottom: 22px;
    position: relative; z-index: 2;
}
.pm-amount-badge .alabel { font-size: 11px; font-weight: 800; color: rgba(255,255,255,.45); letter-spacing: .5px; text-transform: uppercase; display: block; margin-bottom: 3px; }
.pm-amount-badge .avalue { font-size: 26px; font-weight: 900; color: #fff; line-height: 1; display: block; }
.pm-amount-divider { width: 1px; height: 36px; background: rgba(255,255,255,.12); }

/* ── Data fields ───────────────────────────── */
.pm-field {
    display: flex;
    align-items: center;
    gap: 14px;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 16px;
    padding: 14px 16px;
    margin-bottom: 12px;
    position: relative; z-index: 2;
    transition: background .18s;
}
.pm-field:hover { background: rgba(255,255,255,.10); }
.pm-field-icon {
    width: 40px; height: 40px;
    border-radius: 12px;
    background: rgba(255,255,255,.10);
    display: grid;
    place-items: center;
    flex-shrink: 0;
}
.pm-field-icon .material-symbols-rounded { font-size: 20px; color: var(--pm-accent); }
.pm-field-info { flex: 1; min-width: 0; }
.pm-field-info small { font-size: 11px; font-weight: 700; color: rgba(255,255,255,.42); display: block; margin-bottom: 4px; letter-spacing: .3px; }
.pm-field-info strong { font-size: 16px; font-weight: 800; color: #fff; display: block; letter-spacing: .4px; word-break: break-all; }
.pm-copy-btn {
    background: rgba(255,255,255,.09);
    border: 1px solid rgba(255,255,255,.13);
    border-radius: 10px;
    padding: 8px;
    cursor: pointer;
    color: rgba(255,255,255,.65);
    flex-shrink: 0;
    transition: .18s;
    display: grid;
    place-items: center;
    line-height: 0;
}
.pm-copy-btn:hover { background: rgba(255,255,255,.18); color: #fff; }
.pm-copy-btn .material-symbols-rounded { font-size: 18px; }

/* ── Instruction row ───────────────────────── */
.pm-instruction {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.14);
    border-radius: 16px;
    padding: 14px 18px;
    margin-top: 6px;
    margin-bottom: 16px;
    position: relative; z-index: 2;
}
.pm-instruction .shield { font-size: 22px; color: var(--pm-accent); flex-shrink: 0; }
.pm-instruction p,
.pm-instruction span,
.pm-instruction .pm-quill-block,
.pm-instruction .pm-quill-block p,
.pm-instruction .pm-quill-block span,
.pm-instruction .pm-quill-block strong {
    color: #ffffff !important;
    font-size: 13.5px;
    line-height: 1.5;
    font-weight: 600;
    margin: 0;
}
.pm-instruction .wa-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #25D366;
    border-radius: 50%;
    width: 36px; height: 36px;
    flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(37,211,102,.45);
}
.pm-instruction .wa-chip svg { width: 20px; height: 20px; fill: #fff; display: block; }

/* ── Primary Payment Completed WhatsApp Button ─ */
.pm-wa-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    height: 52px;
    background: linear-gradient(135deg, #25D366, #128C7E, #10b981, #25D366);
    background-size: 250% 250%;
    color: #ffffff !important;
    font-size: 15.5px;
    font-weight: 900;
    letter-spacing: 0.3px;
    text-decoration: none;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.25);
    position: relative;
    z-index: 2;
    margin-top: 8px;
    overflow: hidden;
    animation: waGradient 4s ease infinite, waPulse 2.2s infinite;
    transition: transform 0.2s ease;
}
.pm-wa-btn:hover {
    transform: translateY(-2px) scale(1.01);
    color: #ffffff !important;
}
.pm-wa-btn::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(60deg, transparent, rgba(255,255,255,0.28), transparent);
    animation: waShimmer 3s infinite;
    pointer-events: none;
}
.pm-wa-btn svg {
    width: 22px;
    height: 22px;
    fill: #ffffff;
    flex-shrink: 0;
}
@keyframes waGradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
@keyframes waPulse {
    0% {
        box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.65), 0 8px 24px rgba(18, 140, 126, 0.35);
    }
    70% {
        box-shadow: 0 0 0 14px rgba(37, 211, 102, 0), 0 12px 28px rgba(18, 140, 126, 0.45);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(37, 211, 102, 0), 0 8px 24px rgba(18, 140, 126, 0.35);
    }
}
@keyframes waShimmer {
    0% { transform: translateX(-100%) rotate(25deg); }
    100% { transform: translateX(200%) rotate(25deg); }
}

/* ── Divider ──────────────────────────────── */
.pm-divider {
    height: 1px;
    background: rgba(255,255,255,.07);
    margin: 18px 0;
    position: relative; z-index: 2;
}

/* ── Quill rendered instructions ────────────── */
.pm-quill-block { position: relative; z-index: 2; }
.pm-quill-block p  { margin: 0 0 6px; font-size: 13px; color: rgba(255,255,255,.92); font-weight: 500; }
.pm-quill-block ul, .pm-quill-block ol { margin: 0 0 6px; padding-left: 18px; color: rgba(255,255,255,.92); font-size: 13px; font-weight: 500; }
.pm-quill-block strong { color: #fff; }

/* ── Responsive ────────────────────────────── */
@media (max-width: 560px) {
    .pm-card { padding: 22px 18px 20px; border-radius: 22px; }
    .pm-headline .light { font-size: 22px; }
    .pm-headline .bold  { font-size: 28px; }
    .pm-amount-badge .avalue { font-size: 22px; }
    .pm-outer { padding: 6px 0 30px; }
}
</style>

<!-- Page header -->
<div class="page-header">
    <div>
        <h1><?php echo Security::escape($method['method_name']); ?></h1>
        <p class="page-subtitle">Payment account details — copy and complete your transfer.</p>
    </div>
    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/pay" class="btn btn-secondary">
        <span class="material-symbols-rounded">arrow_back</span> All Methods
    </a>
</div>

<div class="pm-outer">
<div class="pm-page">

    <!-- Nav row -->
    <div class="pm-back-bar">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/pay" class="pm-back-link">
            <span class="material-symbols-rounded">arrow_back</span> Choose another method
        </a>
        <div class="pm-plan-chip">
            <span class="material-symbols-rounded">workspace_premium</span>
            <?php echo Security::escape($planName); ?> Plan
        </div>
    </div>

    <!-- The dark premium card -->
    <div class="pm-card" style="
        --pm-from: <?php echo $thm[0]; ?>;
        --pm-to:   <?php echo $thm[1]; ?>;
        --pm-accent: <?php echo $thm[2]; ?>;
        --pm-glow:   <?php echo $thm[3]; ?>;
        --pm-grid-opacity: <?php echo $thm[4]; ?>;
    ">
        <div class="pm-blob2"></div>

        <!-- Top row: logo + secure badge -->
        <div class="pm-top">
            <?php if ($logoUrl): ?>
                <img class="pm-logo" src="<?php echo $logoUrl; ?>" alt="<?php echo Security::escape($method['method_name']); ?>">
            <?php else: ?>
                <span class="pm-logo-text"><?php echo Security::escape($method['method_name']); ?></span>
            <?php endif; ?>
            <span class="pm-secure">
                <span class="material-symbols-rounded">verified_user</span> SECURE PAYMENT
            </span>
        </div>

        <!-- Headline -->
        <div class="pm-headline">
            <span class="light">Pay with</span>
            <span class="bold"><?php echo Security::escape($method['method_name']); ?></span>
        </div>
        <p class="pm-sub">
            <?php if ($method['method_type'] === 'manual'): ?>
                Use the account details below or scan QR to complete payment.
            <?php else: ?>
                Proceed through the gateway to securely complete your payment.
            <?php endif; ?>
        </p>

        <!-- Amount -->
        <div class="pm-amount-badge">
            <div>
                <span class="alabel">Amount Due</span>
                <span class="avalue"><?php echo $amount; ?></span>
            </div>
            <div class="pm-amount-divider"></div>
            <div>
                <span class="alabel">Expires</span>
                <span class="avalue" style="font-size:15px; font-weight:700; color:rgba(255,255,255,.7);"><?php echo Security::escape($expiryDate); ?></span>
            </div>
        </div>

        <!-- Account Holder -->
        <?php if ($hasHolder): ?>
        <div class="pm-field">
            <div class="pm-field-icon"><span class="material-symbols-rounded">person</span></div>
            <div class="pm-field-info">
                <small>Account Holder Name</small>
                <strong id="val-holder"><?php echo Security::escape($method['account_holder']); ?></strong>
            </div>
            <button class="pm-copy-btn" onclick="pmCopy('val-holder', this)" title="Copy name">
                <span class="material-symbols-rounded">content_copy</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Account Number -->
        <?php if ($hasNumber): ?>
        <div class="pm-field">
            <div class="pm-field-icon"><span class="material-symbols-rounded">credit_card</span></div>
            <div class="pm-field-info">
                <small>
                    <?php
                    if ($lk === 'bank' || $lk === 'meezan') echo 'IBAN / Account Number';
                    elseif ($lk === 'easypaisa' || $lk === 'jazzcash' || $lk === 'nayapay') echo Security::escape($method['method_name']) . ' Account Number';
                    else echo 'Account Number';
                    ?>
                </small>
                <strong id="val-number"><?php echo Security::escape($method['account_number']); ?></strong>
            </div>
            <button class="pm-copy-btn" onclick="pmCopy('val-number', this)" title="Copy number">
                <span class="material-symbols-rounded">content_copy</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if ($hasHolder || $hasNumber): ?>
            <div class="pm-divider"></div>
        <?php endif; ?>

        <!-- QR Code -->
        <?php if (!empty($method['qr_code'])): ?>
        <div style="text-align:center; margin-bottom:16px; position:relative; z-index:2;">
            <p style="font-size:11px; font-weight:800; color:rgba(255,255,255,.4); letter-spacing:.8px; text-transform:uppercase; margin:0 0 10px 0;">Scan QR to Pay</p>
            <img src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/uploads/qr_codes/<?php echo Security::escape($method['qr_code']); ?>"
                 alt="Payment QR Code"
                 style="width:180px; height:180px; aspect-ratio:1/1; object-fit:contain; border-radius:16px; border:3px solid rgba(255,255,255,.18); background:#fff; padding:10px; box-shadow:0 12px 36px rgba(0,0,0,.45); transition:transform 0.3s;"
                 onmouseover="this.style.transform='scale(1.04)'"
                 onmouseout="this.style.transform='scale(1)'">
        </div>
        <?php if ($hasInstructions || !($hasHolder || $hasNumber)): ?>
            <div class="pm-divider"></div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Instructions (rich text from Quill) -->
        <?php if ($hasInstructions): ?>
        <div class="pm-instruction">
            <span class="material-symbols-rounded shield">shield</span>
            <div class="pm-quill-block" style="flex:1;">
                <?php echo $method['instructions']; ?>
            </div>
            <div class="wa-chip">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </div>
        </div>
        <?php else: ?>
        <div class="pm-instruction">
            <span class="material-symbols-rounded shield">shield</span>
            <p style="flex:1;">Pay via <?php echo Security::escape($method['method_name']); ?> and click below to confirm on WhatsApp.</p>
            <div class="wa-chip">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </div>
        </div>
        <?php endif; ?>

        <!-- Primary Payment Completed WhatsApp Button -->
        <a href="<?php echo $waPaymentUrl; ?>" target="_blank" class="pm-wa-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            <span>Completed Payment</span>
        </a>
    </div>

</div><!-- /.pm-page -->
</div><!-- /.pm-outer -->

<?php ob_start(); ?>
<script>
function pmCopy(elId, btn) {
    const text = document.getElementById(elId)?.textContent?.trim() || '';
    if (!text) return;
    
    const onSuccess = () => {
        const ic = btn.querySelector('.material-symbols-rounded');
        if (ic) {
            ic.textContent = 'check';
            ic.style.color  = '<?php echo $thm[2]; ?>';
            setTimeout(() => { ic.textContent = 'content_copy'; ic.style.color = ''; }, 2000);
        }
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(onSuccess).catch(() => fallbackCopy(text, onSuccess));
    } else {
        fallbackCopy(text, onSuccess);
    }
}

function fallbackCopy(text, cb) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.opacity = "0";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
        cb();
    } catch (err) {
        console.error('Fallback copy failed', err);
    }
    document.body.removeChild(textArea);
}

document.addEventListener('DOMContentLoaded', () => {
    const waBtn = document.querySelector('.pm-wa-btn');
    if (waBtn) {
        waBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const month = months[now.getMonth()];
            const year = now.getFullYear();
            
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? String(hours).padStart(2, '0') : '12';
            
            const deviceDateTime = `${day} ${month} ${year}, ${hours}:${minutes} ${ampm}`;
            
            const adminWa = "<?= $cleanAdminWa ?>";
            const storeTitle = <?= json_encode($storeTitle) ?>;
            const planName = <?= json_encode($planName) ?>;
            const amount = <?= json_encode($amount) ?>;
            const methodName = <?= json_encode($method['method_name'] ?? 'Payment') ?>;

            const msg = `Assalam o Alaikum Super Admin,\n\nMaine POS subscription payment complete kar di hai.\n\n*Store:* ${storeTitle}\n*Plan:* ${planName} Plan\n*Amount Paid:* ${amount}\n*Payment Method:* ${methodName}\n*Date & Time:* ${deviceDateTime}\n\nBarah-e-karam payment verify karke mera account activate kar dein.\nShukriya!`;

            const finalUrl = `https://wa.me/${adminWa}?text=${encodeURIComponent(msg)}`;
            window.open(finalUrl, '_blank');
        });
    }
});
</script>
<?php $extraJs = ob_get_clean(); ?>

<!-- Floating Draggable WhatsApp Button -->
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
