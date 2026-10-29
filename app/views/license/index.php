<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Security::escape($page_title ?? 'Software License Verification'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" />
    <style>
        :root {
            --primary: #0f172a;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --bg-body: #020617;
            --bg-card: #0f172a;
            --border: rgba(255,255,255,0.1);
            --danger: #ef4444;
            --success: #10b981;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Manrope', sans-serif;
            background: radial-gradient(circle at top right, rgba(37, 99, 235, 0.16), transparent 38%), var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .auth-container {
            width: 100%;
            max-width: 500px;
            background: var(--bg-card);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border);
            overflow: hidden;
        }
        .auth-header { padding: 38px 38px 18px; text-align: center; }
        .shield-icon {
            width: 76px;
            height: 76px;
            background: rgba(37, 99, 235, 0.12);
            color: #60a5fa;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 22px;
            border: 1px solid rgba(96, 165, 250, 0.22);
        }
        .shield-icon .material-symbols-rounded { font-size: 38px; }
        .auth-header h1 { font-size: 25px; font-weight: 800; margin-bottom: 8px; }
        .auth-header p { color: var(--text-muted); font-size: 14px; line-height: 1.6; }
        .domain-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.05);
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            margin-top: 16px;
            border: 1px solid var(--border);
        }
        .auth-body { padding: 20px 38px 38px; }
        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-input {
            width: 100%;
            background: rgba(0,0,0,0.22);
            border: 1px solid var(--border);
            color: #fff;
            padding: 15px 18px;
            border-radius: 14px;
            font-family: inherit;
            font-size: 15px;
        }
        .form-input:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.14);
        }
        .code-input {
            text-align: center;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 9px;
        }
        .btn-submit {
            width: 100%;
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 15px;
            border-radius: 14px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:hover { background: var(--accent-hover); transform: translateY(-1px); }
        .app-alert {
            padding: 14px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.45;
        }
        .app-alert-error { background: rgba(239, 68, 68, 0.1); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.22); }
        .app-alert-success { background: rgba(16, 185, 129, 0.1); color: #86efac; border: 1px solid rgba(16, 185, 129, 0.22); }
        .dev-code { background: rgba(245, 158, 11, 0.12); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.28); }
        .divider { height: 1px; background: var(--border); margin: 24px 0; }
        .footer-note { text-align: center; margin-top: 20px; font-size: 13px; color: var(--text-muted); }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <div class="shield-icon">
                <span class="material-symbols-rounded">verified_user</span>
            </div>
            <h1>Email Activation</h1>
            <p>Enter your email to receive a free activation code for this POS installation.</p>
            <div class="domain-tag">
                <span class="material-symbols-rounded" style="font-size:16px;">public</span>
                <?php echo Security::escape($domain ?? 'Unknown'); ?>
            </div>
        </div>

        <div class="auth-body">
            <?php $flash = App::pullFlash(); ?>
            <?php if ($flash): ?>
            <div class="app-alert app-alert-<?php echo Security::escape($flash['type']); ?>">
                <span class="material-symbols-rounded"><?php echo $flash['type'] === 'success' ? 'check_circle' : 'error'; ?></span>
                <span><?php echo Security::escape($flash['message']); ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($devCode)): ?>
            <div style="background:rgba(37,99,235,0.12); border:2px solid #3b82f6; border-radius:16px; padding:18px; text-align:center; margin-bottom:20px;">
                <div style="font-size:11px; font-weight:800; text-transform:uppercase; color:#60a5fa; margin-bottom:4px;">🧪 Localhost Instant Testing Code</div>
                <div style="font-size:32px; font-weight:900; letter-spacing:8px; color:#fff; margin:6px 0;"><?php echo Security::escape($devCode); ?></div>
                <div style="font-size:12px; color:#94a3b8;">(Code auto-filled below — click Verify & Activate!)</div>
            </div>
            <?php endif; ?>

            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/licenseactivation/requestCode" method="POST">
                <?php echo Security::csrfField(); ?>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="you@example.com" required autocomplete="email" value="<?php echo Security::escape($pendingEmail ?? ($_POST['email'] ?? 'admin@pos.local')); ?>">
                </div>
                <button type="submit" class="btn-submit">
                    <span class="material-symbols-rounded">send</span>
                    Send Free Code
                </button>
            </form>

            <?php if (!empty($pendingEmail)): ?>
            <div class="divider"></div>
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/licenseactivation/verify" method="POST">
                <?php echo Security::csrfField(); ?>
                <div class="form-group">
                    <label class="form-label">Activation Code</label>
                    <input type="text" name="activation_code" value="<?php echo Security::escape($devCode ?? ''); ?>" class="form-input code-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autocomplete="one-time-code">
                </div>
                <button type="submit" class="btn-submit" style="background:#10b981;">
                    <span class="material-symbols-rounded">task_alt</span>
                    Verify & Activate
                </button>
            </form>
            <?php endif; ?>

            <div class="footer-note">
                Code expires in 15 minutes and is locked to the displayed domain.
            </div>
        </div>
    </div>
</body>
</html>
