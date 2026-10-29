<?php ob_start(); ?>

<style>
    .scanner-hub-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
    }

    .scanner-hero-grid {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 18px;
        margin-bottom: 22px;
        align-items: stretch;
    }

    .scanner-pin-card {
        background: linear-gradient(145deg, #0b132b 0%, #1c2541 60%, #1e293b 100%);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 24px;
        padding: 20px 22px;
        color: #ffffff;
        box-shadow: 0 16px 36px rgba(11, 19, 43, 0.22);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 12px;
        position: relative;
        overflow: hidden;
    }

    .scanner-pin-card::before {
        content: '';
        position: absolute;
        top: -30%;
        right: -20%;
        width: 240px;
        height: 240px;
        background: radial-gradient(circle, rgba(15, 109, 255, 0.3), transparent 70%);
        pointer-events: none;
    }

    .pin-digits-row {
        display: flex;
        gap: 8px;
        justify-content: center;
        margin: 12px 0 8px 0;
    }

    .pin-digit-box {
        width: 48px;
        height: 56px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.08);
        border: 1.5px solid rgba(255, 255, 255, 0.22);
        backdrop-filter: blur(12px);
        display: grid;
        place-items: center;
        font-size: 26px;
        font-weight: 900;
        font-family: monospace;
        color: #ffffff;
        box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.1), 0 6px 14px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
    }

    .pin-digit-box:hover {
        border-color: #60a5fa;
        transform: translateY(-2px);
    }

    .scanner-qr-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        justify-content: center;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.04);
    }

    .scanner-qr-frame {
        background: #ffffff;
        padding: 8px;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(15, 109, 255, 0.10);
        border: 2px solid #bfdbfe;
        margin: 8px 0;
        display: grid;
        place-items: center;
    }

    .steps-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-bottom: 26px;
    }

    .step-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 22px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s ease, border-color 0.2s ease;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .step-card:hover {
        transform: translateY(-3px);
        border-color: #93c5fd;
        box-shadow: 0 10px 24px rgba(15, 109, 255, 0.08);
    }

    .step-badge {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        background: #0f6dff;
        color: #fff;
        font-size: 15px;
        font-weight: 900;
        display: grid;
        place-items: center;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 26px;
    }

    .feature-card {
        background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 20px;
    }

    .pulse-live {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
        animation: pulseLiveAnim 1.8s infinite cubic-bezier(0.4, 0, 0.6, 1);
    }

    @keyframes pulseLiveAnim {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    @media (max-width: 992px) {
        .scanner-hero-grid, .steps-grid, .features-grid {
            grid-template-columns: 1fr;
        }
        .pin-digit-box {
            width: 44px;
            height: 56px;
            font-size: 24px;
        }
    }
</style>

<div class="scanner-hub-header">
    <div>
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
            <div style="width:42px; height:42px; border-radius:14px; background:linear-gradient(135deg, #0f6dff, #0284c7); display:grid; place-items:center; color:#fff; box-shadow:0 6px 16px rgba(15,109,255,0.25);">
                <span class="material-symbols-rounded" style="font-size:24px;">qr_code_scanner</span>
            </div>
            <div>
                <h1 style="margin:0; font-size:22px; font-weight:900; color:#0f172a;">Wireless Mobile Scanner</h1>
                <div style="font-size:12.5px; color:#64748b; font-weight:700;">Store: <?= Security::escape($storeTitle) ?></div>
            </div>
        </div>
        <p class="page-subtitle" style="margin:0;">Turn any smartphone camera into a wireless barcode scanner for your POS checkout and inventory.</p>
    </div>

    <!-- Top Action Buttons -->
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <a href="<?= Security::escape($apkUrl) ?>" download class="btn btn-primary" style="font-weight:800; border-radius:12px; padding:10px 18px; display:inline-flex; align-items:center; gap:6px; box-shadow:0 4px 14px rgba(15,109,255,0.25);">
            <span class="material-symbols-rounded" style="font-size:18px;">download</span> Download Mobile APK
        </a>
    </div>
</div>

<?php if (empty($isEnabled)): ?>
<div style="background:linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border:1.5px solid #fde68a; border-radius:18px; padding:18px 22px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; box-shadow:0 6px 18px rgba(217,119,6,0.08);">
    <div style="display:flex; align-items:center; gap:14px;">
        <div style="width:44px; height:44px; border-radius:12px; background:#f59e0b; color:#fff; display:grid; place-items:center; flex-shrink:0; box-shadow:0 4px 12px rgba(245,158,11,0.3);">
            <span class="material-symbols-rounded" style="font-size:24px;">lock</span>
        </div>
        <div>
            <div style="font-size:15px; font-weight:800; color:#92400e;">Wireless Mobile Scanner is Currently Disabled</div>
            <div style="font-size:13px; color:#b45309; margin-top:2px;">
                Mobile scanning is turned OFF in system settings. POS background polling and inventory syncing are suspended.
            </div>
        </div>
    </div>
    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/index?tab=scanner" class="btn btn-primary" style="background:#d97706; border-color:#b45309; font-weight:800; padding:10px 18px; border-radius:12px; display:inline-flex; align-items:center; gap:6px;">
        <span class="material-symbols-rounded" style="font-size:18px;">tune</span> Enable in Settings
    </a>
</div>
<?php endif; ?>

<!-- ==========================================
     HERO SECTION: PIN CARD & QR CARD
     ========================================== -->
<div class="scanner-hero-grid">
    <!-- Left: 6-Digit Store PIN Card -->
    <div class="scanner-pin-card">
        <div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:11.5px; font-weight:800; text-transform:uppercase; letter-spacing:1.2px; color:#93c5fd;">Store Connection PIN</span>
                <?php if (!empty($isEnabled)): ?>
                    <span style="font-size:11px; background:rgba(34,197,94,0.18); color:#4ade80; border:1px solid rgba(74,222,128,0.3); border-radius:999px; padding:4px 10px; font-weight:800; display:inline-flex; align-items:center; gap:5px;">
                        <span class="pulse-live"></span> Active & Ready
                    </span>
                <?php else: ?>
                    <span style="font-size:11px; background:rgba(239,68,68,0.18); color:#fca5a5; border:1px solid rgba(248,113,113,0.3); border-radius:999px; padding:4px 10px; font-weight:800; display:inline-flex; align-items:center; gap:5px;">
                        <span style="width:8px; height:8px; border-radius:50%; background:#ef4444; display:inline-block;"></span> Disabled in Settings
                    </span>
                <?php endif; ?>
            </div>

            <p style="font-size:13px; color:#cbd5e1; margin-top:8px; line-height:1.4;">
                Enter this permanent 6-digit code on the mobile app to pair this register instantly:
            </p>

            <!-- 6 Segmented PIN Boxes -->
            <?php $pinDigits = str_split(str_pad((string)$scannerPin, 6, '0')); ?>
            <div class="pin-digits-row">
                <?php foreach ($pinDigits as $d): ?>
                    <div class="pin-digit-box"><?= htmlspecialchars($d) ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:16px; padding:12px 16px; text-align:center;">
            <div style="font-size:12.5px; color:#94a3b8; font-weight:700;">Permanent PIN &bull; Never expires &bull; Zero latency cloud connection</div>
        </div>
    </div>

    <!-- Right: Real-Time Pairing QR Card -->
    <div class="scanner-qr-card">
        <div style="display:flex; align-items:center; gap:8px; font-size:15px; font-weight:900; color:#0f172a;">
            <span class="material-symbols-rounded" style="color:#0f6dff; font-size:22px;">qr_code_2</span>
            Store Pairing QR Code
        </div>
        <p style="font-size:12px; color:#64748b; margin:4px 0 6px;">Scan or share to connect companion devices seamlessly:</p>

        <div class="scanner-qr-frame">
            <img src="<?= Security::escape($qrImg) ?>" alt="Store Pairing QR" style="width:140px; height:140px; display:block; border-radius:10px; object-fit:contain;">
        </div>

        <div style="font-size:12px; font-weight:800; color:#0f6dff; background:#eff6ff; border:1px solid #bfdbfe; padding:6px 14px; border-radius:999px;">
            PIN: <?= Security::escape($scannerPin) ?>
        </div>
    </div>
</div>

<!-- ==========================================
     STEP-BY-STEP SETUP GUIDE
     ========================================== -->
<div style="font-size:15px; font-weight:900; color:#0f172a; margin-bottom:14px; display:flex; align-items:center; gap:8px;">
    <span class="material-symbols-rounded" style="color:#0f6dff; font-size:20px;">checklist</span>
    Quick 3-Step Mobile Pairing Guide
</div>

<div class="steps-grid">
    <div class="step-card">
        <div class="step-badge">1</div>
        <strong style="font-size:14px; color:#0f172a;">Install Companion App</strong>
        <p style="font-size:12.5px; color:#64748b; margin:0; line-height:1.45;">
            Click <strong>Download Mobile APK</strong> button above and install the companion app on your Android phone.
        </p>
    </div>

    <div class="step-card">
        <div class="step-badge">2</div>
        <strong style="font-size:14px; color:#0f172a;">Enter Store PIN</strong>
        <p style="font-size:12.5px; color:#64748b; margin:0; line-height:1.45;">
            Input the 6-digit Store PIN (<strong style="color:#0f6dff;"><?= Security::escape($scannerPin) ?></strong>). The app verifies and pairs automatically on the 6th digit.
        </p>
    </div>

    <div class="step-card">
        <div class="step-badge" style="background:#16a34a;">3</div>
        <strong style="font-size:14px; color:#0f172a;">Scan Barcodes Anywhere</strong>
        <p style="font-size:12.5px; color:#64748b; margin:0; line-height:1.45;">
            Point your camera at barcodes. Scans fly instantly into the POS active checkout cart or inventory over 4G/5G/Wi-Fi!
        </p>
    </div>
</div>

<!-- ==========================================
     FEATURES OVERVIEW
     ========================================== -->
<div class="features-grid">
    <div class="feature-card">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
            <div style="width:34px; height:34px; border-radius:10px; background:#eff6ff; color:#0f6dff; display:grid; place-items:center;">
                <span class="material-symbols-rounded" style="font-size:20px;">cloud_sync</span>
            </div>
            <strong style="font-size:13.5px; color:#0f172a;">Works on 4G / 5G / Wi-Fi</strong>
        </div>
        <p style="font-size:12px; color:#64748b; margin:0; line-height:1.45;">
            No need to be on the same local Wi-Fi network. Scan from the warehouse or across branches seamlessly.
        </p>
    </div>

    <div class="feature-card">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
            <div style="width:34px; height:34px; border-radius:10px; background:#ecfdf5; color:#16a34a; display:grid; place-items:center;">
                <span class="material-symbols-rounded" style="font-size:20px;">volume_up</span>
            </div>
            <strong style="font-size:13.5px; color:#0f172a;">Instant Audio Feedback</strong>
        </div>
        <p style="font-size:12px; color:#64748b; margin:0; line-height:1.45;">
            Plays immediate POS cash-register beeps upon successful scan detection for high-volume billing speed.
        </p>
    </div>

    <div class="feature-card">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
            <div style="width:34px; height:34px; border-radius:10px; background:#fef3c7; color:#d97706; display:grid; place-items:center;">
                <span class="material-symbols-rounded" style="font-size:20px;">receipt_long</span>
            </div>
            <strong style="font-size:13.5px; color:#0f172a;">Mobile Sales & Invoices</strong>
        </div>
        <p style="font-size:12px; color:#64748b; margin:0; line-height:1.45;">
            Cashiers and store managers can review sales history, daily totals, and print receipts directly from mobile.
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/views/layouts/main.php';
?>
