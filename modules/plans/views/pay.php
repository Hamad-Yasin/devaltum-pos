<?php ob_start(); ?>
<?php
$planName   = (string) ($store['plan_name'] ?? 'Pro');
$amount     = number_format((float) ($store['monthly_price'] ?? 500));
$expiryDate = !empty($store['subscription_expires_at'])
    ? date('M d, Y', strtotime((string) $store['subscription_expires_at']))
    : 'Sep 17, 2026';

$payLogoMap = [
    'easypaisa' => '/assets/img/payment/easypaisa.png',
    'jazzcash'  => '/assets/img/payment/jazzcash.png',
    'nayapay'   => '/assets/img/payment/nayapay.png',
    'meezan'    => '/assets/img/payment/meezan.webp',
    'bank'      => '/assets/img/bank.svg',
];
?>

<style>
.pay-here-page {
    display: flex;
    flex-direction: column;
    gap: 24px;
    padding-bottom: 40px;
}

/* ── Top Hero Card ─────────────────────────── */
.pay-hero-card {
    background: linear-gradient(135deg, #0d1527 0%, #152238 100%);
    border-radius: 20px;
    padding: 26px 30px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    box-shadow: 0 12px 32px rgba(13, 21, 39, 0.18);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.pay-hero-card::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.25), transparent 70%);
    pointer-events: none;
}
.pay-hero-label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.06em;
    color: #94a3b8;
    text-transform: uppercase;
    margin-bottom: 6px;
}
.pay-hero-amount {
    font-size: 36px;
    font-weight: 900;
    color: #ffffff;
    line-height: 1.1;
    margin-bottom: 8px;
}
.pay-hero-meta {
    font-size: 13.5px;
    color: #94a3b8;
    font-weight: 600;
}
.pay-hero-meta strong {
    color: #ffffff;
    font-weight: 700;
}
.pay-hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.16);
    color: #ffffff;
    padding: 9px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    backdrop-filter: blur(8px);
    transition: all 0.2s ease;
}
.pay-hero-btn:hover {
    background: rgba(255, 255, 255, 0.16);
    border-color: rgba(255, 255, 255, 0.28);
    transform: translateY(-1px);
    color: #fff;
}

/* ── Section Title ─────────────────────────── */
.pay-section-header {
    margin-bottom: 16px;
}
.pay-section-title {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 4px;
}
.pay-section-sub {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

/* ── Payment Methods Grid ──────────────────── */
.pay-methods-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 16px;
}
.pay-method-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 24px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 14px;
    text-decoration: none;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    position: relative;
    cursor: pointer;
}
.pay-method-card:hover {
    border-color: #93c5fd;
    transform: translateY(-3px);
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.08);
}
.pay-method-logo-box {
    width: 72px;
    height: 72px;
    background: transparent;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: transform 0.2s ease;
}
.pay-method-card:hover .pay-method-logo-box {
    transform: scale(1.08);
}
.pay-method-logo-box img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.pay-method-name {
    font-size: 14.5px;
    font-weight: 800;
    color: #0f172a;
}

/* ── Floating WhatsApp Button ──────────────── */
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

<div class="page-header">
    <div>
        <h1>Pay Here</h1>
        <p class="page-subtitle">Choose a payment method to view account details.</p>
    </div>
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/history" class="btn btn-secondary" style="border-radius:12px; font-weight:700; display:inline-flex; align-items:center; gap:6px; height:40px; padding:0 16px; font-size:13px;">
            <span class="material-symbols-rounded" style="font-size:18px;">receipt_long</span> History
        </a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/index" class="btn btn-secondary" style="border-radius:12px; font-weight:700; display:inline-flex; align-items:center; gap:6px; height:40px; padding:0 16px; font-size:13px;">
            <span class="material-symbols-rounded" style="font-size:18px;">arrow_back</span>Back to Plan
        </a>
    </div>
</div>

<div class="pay-here-page">
    <!-- Dark Amount Due Banner -->
    <div class="pay-hero-card">
        <div>
            <div class="pay-hero-label">Amount Due This Month</div>
            <div class="pay-hero-amount">Rs <?= $amount ?></div>
            <div class="pay-hero-meta">
                Plan: <strong><?= htmlspecialchars($planName) ?></strong> • Expires: <strong><?= $expiryDate ?></strong>
            </div>
        </div>
        <div>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/index" class="pay-hero-btn">
                <span class="material-symbols-rounded" style="font-size:18px; color:#60a5fa;">workspace_premium</span>Plan Status
            </a>
        </div>
    </div>

    <!-- Choose Payment Method Section -->
    <div>
        <div class="pay-section-header">
            <h2 class="pay-section-title">Choose Payment Method</h2>
            <p class="pay-section-sub">Tap any method to view full account details.</p>
        </div>

        <div class="pay-methods-grid">
            <?php foreach ($paymentMethods as $m): ?>
                <?php
                $slug = strtolower(trim((string) ($m['method_logo'] ?: $m['method_name'])));
                $logoSrc = '';
                foreach ($payLogoMap as $k => $path) {
                    if (str_contains($slug, $k)) {
                        $logoSrc = (defined('BASE_URL') ? BASE_URL : '') . $path;
                        break;
                    }
                }
                ?>
                <?php $storeParam = !empty($store['id']) ? ('?store_id=' . (int)$store['id']) : ''; ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/method/<?= (int) $m['id'] ?><?= $storeParam ?>" class="pay-method-card">
                    <div class="pay-method-logo-box">
                        <?php if ($logoSrc): ?>
                            <img src="<?= $logoSrc ?>" alt="<?= htmlspecialchars($m['method_name']) ?>">
                        <?php else: ?>
                            <span class="material-symbols-rounded" style="font-size:32px; color:#2563eb;">account_balance</span>
                        <?php endif; ?>
                    </div>
                    <span class="pay-method-name"><?= htmlspecialchars($m['method_name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if (!empty($adminWhatsapp)): ?>
    <?php
    $cleanWa = preg_replace('/[^0-9]/', '', (string) $adminWhatsapp);
    if (str_starts_with($cleanWa, '0')) {
        $cleanWa = '92' . substr($cleanWa, 1);
    }
    $storeNameRaw = $store['store_name'] ?? 'My Store';
    $waText = "Hello DevAltum Team, I need assistance regarding subscription payment for: " . $storeNameRaw . ". Please assist. Thank you!";
    $waUrl = "https://wa.me/" . $cleanWa . "?text=" . urlencode($waText);
    ?>
    <!-- Floating Draggable WhatsApp Button -->
    <div id="draggable-wa-btn" class="draggable-wa-btn" style="position: fixed; bottom: 80px; right: 24px; z-index: 10000; cursor: move; touch-action: none;">
        <a id="draggable-wa-link" href="<?php echo $waUrl; ?>" target="_blank" class="wa-float-link" onclick="preventClickIfDragged(event)">
            <img src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/img/whatsapp.png" alt="WhatsApp Support" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
            <span class="wa-float-glow"></span>
        </a>
    </div>

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
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
