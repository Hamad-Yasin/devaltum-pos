<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DevAltum POS</title>
    <link rel="icon" type="image/png" sizes="192x192" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/img/icon-192x192.png">
    <link rel="apple-touch-icon" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/img/icon-192x192.png">
    <link rel="manifest" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/manifest.json">
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/fonts/fonts.css?v=<?= @filemtime(ROOT_PATH . '/public/assets/fonts/fonts.css') ?: time() ?>">
    <style>
        * { box-sizing:border-box; }
        body {
            margin:0;
            min-height:100vh;
            display:grid;
            place-items:center;
            font-family:'Manrope', sans-serif;
            color:#0f172a;
            background:
                radial-gradient(circle at top right, rgba(15,109,255,.18), transparent 30%),
                radial-gradient(circle at bottom left, rgba(245,158,11,.16), transparent 24%),
                linear-gradient(135deg, #eef4ff 0%, #f8fafc 55%, #fff7ed 100%);
            padding:24px;
        }
        .login-shell {
            width:min(1080px, 100%);
            min-height:680px;
            display:grid;
            grid-template-columns:1.05fr .95fr;
            background:rgba(255,255,255,.92);
            border:1px solid rgba(219,228,240,.9);
            border-radius:32px;
            overflow:hidden;
            box-shadow:0 24px 70px rgba(15,23,42,.14);
            backdrop-filter:blur(12px);
        }
        .brand-panel {
            padding:44px;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,.3), transparent 28%),
                linear-gradient(160deg, #0f172a 0%, #102347 55%, #0f6dff 100%);
            color:#fff;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            position:relative;
        }
        .brand-panel::after {
            content:'';
            position:absolute;
            inset:auto -80px -80px auto;
            width:240px;
            height:240px;
            border-radius:50%;
            background:rgba(245,158,11,.18);
            filter:blur(10px);
        }
        .brand-badge {
            width:64px;
            height:64px;
            border-radius:20px;
            display:grid;
            place-items:center;
            background:rgba(255,255,255,.14);
            border:1px solid rgba(255,255,255,.16);
            margin-bottom:22px;
        }
        .brand-badge .material-symbols-rounded { font-size:32px; }
        .brand-panel h1 { margin:0 0 14px; font-size:42px; line-height:1.05; }
        .brand-panel p { margin:0; color:rgba(255,255,255,.78); font-size:15px; line-height:1.7; max-width:440px; }
        .brand-points { display:grid; gap:14px; margin-top:34px; }
        .brand-point {
            display:flex;
            gap:12px;
            align-items:flex-start;
            padding:14px 16px;
            border-radius:18px;
            background:rgba(255,255,255,.08);
            border:1px solid rgba(255,255,255,.1);
        }
        .brand-point .material-symbols-rounded { font-size:20px; color:#fde68a; }
        .brand-point strong { display:block; margin-bottom:4px; font-size:14px; }
        .brand-foot { color:rgba(255,255,255,.64); font-size:13px; }
        .form-panel {
            padding:44px;
            display:flex;
            flex-direction:column;
            justify-content:center;
            background:linear-gradient(180deg, rgba(255,255,255,.94), rgba(248,250,252,.98));
        }
        .form-panel h2 { margin:0 0 10px; font-size:34px; }
        .form-panel p { margin:0 0 28px; color:#64748b; line-height:1.7; }
        .field { margin-bottom:16px; }
        .field label { display:block; font-size:14px; font-weight:700; margin-bottom:8px; }
        .input-wrap { position:relative; }
        .input-wrap .material-symbols-rounded {
            position:absolute;
            left:14px;
            top:50%;
            transform:translateY(-50%);
            color:#64748b;
            font-size:20px;
        }
        .input-wrap input {
            width:100%;
            border:1px solid #dbe4f0;
            border-radius:18px;
            padding:15px 48px 15px 46px;
            font:inherit;
            background:#fff;
            transition:.2s ease;
            box-shadow:0 8px 24px rgba(15,23,42,.04);
        }
        .input-wrap input:focus {
            outline:none;
            border-color:#0f6dff;
            box-shadow:0 0 0 4px rgba(15,109,255,.12);
        }
        .toggle-eye {
            position:absolute;
            right:12px;
            top:50%;
            transform:translateY(-50%);
            background:none;
            border:none;
            cursor:pointer;
            color:#64748b;
            width:36px;
            height:36px;
            border-radius:12px;
        }
        .toggle-eye:hover { background:#f8fafc; }
        .btn {
            width:100%;
            border:none;
            border-radius:18px;
            padding:15px;
            font:inherit;
            font-weight:800;
            cursor:pointer;
            background:linear-gradient(135deg, #0f6dff, #2b87ff);
            color:#fff;
            box-shadow:0 16px 34px rgba(15,109,255,.24);
        }
        .btn:hover { filter:brightness(.98); }
        .alert {
            margin-bottom:18px;
            padding:13px 14px;
            border-radius:16px;
            background:#fff1f2;
            color:#be123c;
            border:1px solid #fecdd3;
        }
        .helper-row {
            margin-top:16px;
            display:flex;
            justify-content:space-between;
            gap:12px;
            color:#64748b;
            font-size:13px;
        }
        @media (max-width: 920px) {
            .login-shell { grid-template-columns:1fr; min-height:auto; }
            .brand-panel, .form-panel { padding:30px 24px; }
            .brand-panel h1 { font-size:34px; }
            .form-panel h2 { font-size:28px; }
        }
        @media (max-width: 768px) {
            .brand-panel { display:none; }
            .login-shell { border-radius:24px; }
            .form-panel { padding:44px 24px; border-radius: 24px; }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <section class="brand-panel">
            <div>
                <div class="brand-badge" style="background:#ffffff; border-radius:20px; padding:6px; display:flex; align-items:center; justify-content:center; box-shadow:0 8px 24px rgba(0,0,0,0.18);">
                    <img src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/img/icon-192x192.png" alt="DevAltum POS" style="width:100%; height:100%; object-fit:contain; border-radius:14px;">
                </div>
                <h1>DevAltum POS</h1>
                <p>A polished point of sale workspace for fast checkout, clean reporting, and smoother daily operations.</p>

                <div class="brand-points">
                    <div class="brand-point">
                        <span class="material-symbols-rounded">bolt</span>
                        <div>
                            <strong>Fast daily workflow</strong>
                            <span>Quick checkout, customer tracking, and receipt-ready billing in one place.</span>
                        </div>
                    </div>
                    <div class="brand-point">
                        <span class="material-symbols-rounded">inventory_2</span>
                        <div>
                            <strong>Inventory visibility</strong>
                            <span>Keep stock levels, custom products, and sales history connected.</span>
                        </div>
                    </div>
                    <div class="brand-point">
                        <span class="material-symbols-rounded">verified_user</span>
                        <div>
                            <strong>Long session login</strong>
                            <span>Sign in once and stay available on this device for up to 6 months.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="brand-foot">Secure access for store admins and staff.</div>
        </section>

        <section class="form-panel">
            <div style="max-width:420px; width:100%; margin:0 auto;">
                <h2>Welcome Back</h2>
                <p>Sign in as Super Admin, Store Owner, or Staff to continue.</p>

                <!-- Quick Credentials Showcase Card (Small, High Quality, Readable) -->
                <div style="background: linear-gradient(135deg, rgba(15,109,255,0.06), rgba(16,185,129,0.06)); border: 1px solid rgba(15,109,255,0.2); border-radius: 16px; padding: 12px 14px; margin-bottom: 20px; box-shadow: 0 4px 16px rgba(15,109,255,0.06);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 12px; font-weight: 800; color: #0f6dff; display: inline-flex; align-items: center; gap: 5px;">
                            <span class="material-symbols-rounded" style="font-size: 16px;">key</span>
                            Default Store Login
                        </span>
                        <span style="font-size: 11px; font-weight: 700; color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 999px;">
                            Ready to Use
                        </span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 8px; background: #ffffff; border-radius: 10px; padding: 8px 10px; border: 1px solid #e2e8f0;">
                        <div>
                            <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Username / Email</div>
                            <div style="font-weight: 800; color: #0f172a; font-size: 12px; font-family: monospace;">store@devaltum.pos</div>
                        </div>
                        <div>
                            <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Password</div>
                            <div style="font-weight: 800; color: #0f172a; font-size: 12px; font-family: monospace;">Store@123</div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 10px;">
                        <button type="button" onclick="document.querySelector('input[name=identifier]').value='store@devaltum.pos'; document.getElementById('login-password').value='Store@123';" style="background: #0f6dff; color: #ffffff; border: none; border-radius: 8px; padding: 5px 12px; font-size: 11px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 2px 6px rgba(15,109,255,0.25); transition: 0.15s ease;">
                            <span class="material-symbols-rounded" style="font-size: 14px;">bolt</span> Auto-Fill Credentials
                        </button>
                        <span style="font-size: 11px; color: #059669; font-weight: 700;">❤️ Thank you for using DevAltum POS!</span>
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert"><?php echo Security::escape($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/auth/login" data-no-ajax>
                    <?php echo Security::csrfField(); ?>
                    <div class="field">
                        <label>Email Or Username</label>
                        <div class="input-wrap">
                            <span class="material-symbols-rounded">person</span>
                            <input type="text" name="identifier" required>
                        </div>
                    </div>
                    <div class="field">
                        <label>Password</label>
                        <div class="input-wrap">
                            <span class="material-symbols-rounded">lock</span>
                            <input id="login-password" type="password" name="password" required>
                            <button class="toggle-eye" type="button" onclick="const i=document.getElementById('login-password'); i.type=i.type==='password'?'text':'password'; this.querySelector('span').textContent=i.type==='password'?'visibility':'visibility_off';">
                                <span class="material-symbols-rounded">visibility</span>
                            </button>
                        </div>
                    </div>
                    <button class="btn" type="submit">Login to Dashboard</button>
                </form>

                <?php if (!empty($showBiometricLogin)): ?>
                <div style="display:flex; align-items:center; gap:10px; margin:20px 0 14px 0; color:#94a3b8; font-size:11px; font-weight:800; text-transform:uppercase;">
                    <div style="flex:1; height:1px; background:#e2e8f0;"></div>
                    <span>Or Quick Biometric Sign In</span>
                    <div style="flex:1; height:1px; background:#e2e8f0;"></div>
                </div>

                <button type="button" id="bioLoginBtn" onclick="loginWithBiometrics()" class="btn" style="background:linear-gradient(135deg, #0f172a, #1e293b); color:#fff; display:flex; align-items:center; justify-content:center; gap:8px; border:1px solid #334155; font-weight:800; box-shadow:0 4px 14px rgba(15,23,42,0.15); border-radius:12px; height:46px;">
                    <span class="material-symbols-rounded" style="color:#60a5fa; font-size:22px;">fingerprint</span>
                    <span id="bioLoginBtnText">Sign in with Fingerprint / Passkey</span>
                </button>
                <div id="bioLoginMsg" style="margin-top:10px; font-size:12px; text-align:center; font-weight:700; display:none; padding:8px 12px; border-radius:8px;"></div>
                <?php endif; ?>

                <div class="helper-row">
                    <span>Professional POS access</span>
                    <span>Secure session enabled</span>
                </div>
            </div>
        </section>
    </div>

    <script>
    (function initLoginBioDetector() {
        const btnText = document.getElementById('bioLoginBtnText');
        const isWindows = /Windows/i.test(navigator.userAgent);
        const isMac = /Macintosh|Mac OS/i.test(navigator.userAgent);
        const isAndroid = /Android/i.test(navigator.userAgent);
        const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);

        if (isWindows && btnText) btnText.textContent = 'Sign in with Windows Hello / Fingerprint';
        else if (isMac && btnText) btnText.textContent = 'Sign in with Touch ID';
        else if (isAndroid && btnText) btnText.textContent = 'Sign in with Fingerprint';
        else if (isIOS && btnText) btnText.textContent = 'Sign in with Face ID / Touch ID';
    })();

    async function loginWithBiometrics() {
        const msg = document.getElementById('bioLoginMsg');
        const btn = document.getElementById('bioLoginBtn');

        msg.style.display = 'block';
        msg.style.background = '#eff6ff';
        msg.style.color = '#1d4ed8';
        msg.style.border = '1px solid #bfdbfe';
        msg.textContent = 'Touch your laptop fingerprint sensor, scan Face ID, or verify with Windows Hello...';

        const isSecure = window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        if (!window.PublicKeyCredential) {
            msg.style.background = '#fef2f2';
            msg.style.color = '#dc2626';
            msg.style.border = '1px solid #fecaca';
            msg.textContent = 'Biometric sign-in hardware is not supported on this browser or platform.';
            return;
        }

        try {
            const challenge = new Uint8Array(32);
            window.crypto.getRandomValues(challenge);

            const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
            const savedCredId = localStorage.getItem('pos_biometric_cred_id');
            const publicKeyOpts = {
                challenge: challenge,
                timeout: 60000,
                userVerification: "preferred"
            };
            if (!isLocal) {
                publicKeyOpts.rpId = window.location.hostname;
            }

            if (savedCredId) {
                try {
                    const rawCred = Uint8Array.from(atob(savedCredId), c => c.charCodeAt(0));
                    publicKeyOpts.allowCredentials = [{
                        type: 'public-key',
                        id: rawCred
                    }];
                } catch (e) {}
            }

            const assertion = await navigator.credentials.get({
                publicKey: publicKeyOpts
            });

            if (!assertion) {
                msg.style.background = '#fef2f2';
                msg.style.color = '#dc2626';
                msg.style.border = '1px solid #fecaca';
                msg.textContent = 'Biometric authentication cancelled.';
                return;
            }

            const credId = btoa(String.fromCharCode(...new Uint8Array(assertion.rawId)));
            try {
                localStorage.setItem('pos_biometric_cred_id', credId);
            } catch(e) {}

            btn.disabled = true;
            msg.style.background = '#eff6ff';
            msg.style.color = '#1d4ed8';
            msg.style.border = '1px solid #bfdbfe';
            msg.textContent = 'Verifying fingerprint with secure server...';

            const resp = await fetch('<?= defined('BASE_URL') ? BASE_URL : '' ?>/auth/biometricLogin', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ credential_id: credId })
            });

            const data = await resp.json();
            if (data.success && data.redirect) {
                msg.style.background = '#ecfdf5';
                msg.style.color = '#059669';
                msg.style.border = '1px solid #a7f3d0';
                msg.innerHTML = '<span class="material-symbols-rounded" style="font-size:18px; vertical-align:middle; margin-right:6px; color:#059669;">check_circle</span><strong>Authenticated successfully!</strong> Redirecting to POS...';
                window.location.href = data.redirect;
            } else {
                btn.disabled = false;
                msg.style.background = '#fef2f2';
                msg.style.color = '#dc2626';
                msg.style.border = '1px solid #fecaca';
                msg.textContent = data.error || 'Fingerprint not recognized. Log in with password to register your sensor in Settings.';
            }

        } catch (err) {
            console.error(err);
            btn.disabled = false;
            msg.style.background = '#fef2f2';
            msg.style.color = '#dc2626';
            msg.style.border = '1px solid #fecaca';
            msg.textContent = 'Sensor verification cancelled or error: ' + (err.message || 'Cancelled');
        }
    }
    </script>

    <!-- Floating 3D Expired Plan Pay Button with Rotating Circular Text -->
    <?php if (!empty($showExpiredPayButton)): ?>
    <div id="draggable-pay-btn" class="draggable-pay-btn" style="position: fixed; bottom: 30px; left: 30px; z-index: 10000; cursor: move; touch-action: none;">
        <a id="draggable-pay-link" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/pay" class="pay-floating-3d" onclick="preventPayClickIfDragged(event)" title="Click Here to Pay / Renew Subscription">
            <!-- Rotating Text Ring -->
            <svg class="pay-rotating-ring" viewBox="0 0 130 130" width="130" height="130">
                <defs>
                    <path id="payTextCircle" d="M 65, 65 m -46, 0 a 46,46 0 1,1 92,0 a 46,46 0 1,1 -92,0" />
                </defs>
                <text font-size="9" font-weight="900" letter-spacing="2.6" fill="#38bdf8">
                    <textPath href="#payTextCircle" startOffset="0%">
                        • CLICK HERE TO PAY • RENEW PLAN •
                    </textPath>
                </text>
            </svg>

            <!-- Center 3D Glowing Core -->
            <div class="pay-3d-core">
                <span class="material-symbols-rounded">payments</span>
                <span class="pay-core-pulse"></span>
            </div>
        </a>
    </div>

    <style>
    .pay-floating-3d {
        width: 100px;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        text-decoration: none;
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .pay-floating-3d:hover {
        transform: scale(1.12);
    }
    .pay-rotating-ring {
        position: absolute;
        inset: -15px;
        width: 130px;
        height: 130px;
        animation: spinPayRing 9s linear infinite;
        pointer-events: none;
        filter: drop-shadow(0 2px 6px rgba(56, 189, 248, 0.4));
    }
    @keyframes spinPayRing {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .pay-3d-core {
        width: 66px;
        height: 66px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0284c7, #2563eb, #1e40af);
        display: grid;
        place-items: center;
        color: #ffffff;
        box-shadow: 
            0 12px 30px rgba(37, 99, 235, 0.6),
            inset 0 3px 6px rgba(255, 255, 255, 0.4),
            inset 0 -3px 6px rgba(0, 0, 0, 0.4);
        border: 2px solid rgba(255, 255, 255, 0.35);
        position: relative;
        z-index: 2;
    }
    .pay-3d-core .material-symbols-rounded {
        font-size: 30px;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.35));
    }
    .pay-core-pulse {
        position: absolute;
        inset: -7px;
        border-radius: 50%;
        border: 2px solid #38bdf8;
        opacity: 0;
        animation: payPulseGlow 2.2s infinite ease-in-out;
        pointer-events: none;
    }
    @keyframes payPulseGlow {
        0% { transform: scale(0.92); opacity: 0.85; }
        100% { transform: scale(1.35); opacity: 0; }
    }
    </style>
    <?php endif; ?>

    <!-- Floating Draggable 3D WhatsApp Button -->
    <?php
    $adminWa = preg_replace('/[^0-9]/', '', (string)App::setting('whatsapp_number', '923101648600'));
    if (str_starts_with($adminWa, '0')) { $adminWa = '92' . substr($adminWa, 1); }
    $waText = "Hello DevAltum Team, I need assistance with POS sign in. Please assist. Thank you!";
    $waUrl = "https://wa.me/" . $adminWa . "?text=" . urlencode($waText);
    ?>
    <div id="draggable-wa-btn" class="draggable-wa-btn" style="position: fixed; bottom: 30px; right: 30px; z-index: 10000; cursor: move; touch-action: none;">
        <a id="draggable-wa-link" href="<?php echo $waUrl; ?>" target="_blank" class="wa-float-link" onclick="preventClickIfDragged(event)" title="Chat on WhatsApp">
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

    (() => {
        const payBtn = document.getElementById('draggable-pay-btn');
        if (!payBtn) return;

        let isDragging = false;
        let dragStartX = 0;
        let dragStartY = 0;
        let buttonStartX = 0;
        let buttonStartY = 0;
        let hasMoved = false;

        window.preventPayClickIfDragged = (e) => {
            if (hasMoved) {
                e.preventDefault();
            }
        };

        const onStart = (clientX, clientY) => {
            isDragging = true;
            hasMoved = false;
            dragStartX = clientX;
            dragStartY = clientY;
            const rect = payBtn.getBoundingClientRect();
            buttonStartX = rect.left;
            buttonStartY = rect.top;
            payBtn.style.bottom = 'auto';
            payBtn.style.left = buttonStartX + 'px';
            payBtn.style.top = buttonStartY + 'px';
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

            const maxW = window.innerWidth - payBtn.offsetWidth - 10;
            const maxH = window.innerHeight - payBtn.offsetHeight - 10;
            newX = Math.max(10, Math.min(newX, maxW));
            newY = Math.max(10, Math.min(newY, maxH));

            payBtn.style.left = newX + 'px';
            payBtn.style.top = newY + 'px';
        };

        const onEnd = () => {
            isDragging = false;
        };

        payBtn.addEventListener('mousedown', (e) => {
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

        payBtn.addEventListener('touchstart', (e) => {
            if (e.touches.length === 0) return;
            const touch = e.touches[0];
            onStart(touch.clientX, touch.clientY);
        }, { passive: true });

        payBtn.addEventListener('touchmove', (e) => {
            if (e.touches.length === 0) return;
            const touch = e.touches[0];
            onMove(touch.clientX, touch.clientY);
        }, { passive: true });

        payBtn.addEventListener('touchend', () => {
            onEnd();
        });
    })();
    </script>
</body>
</html>
