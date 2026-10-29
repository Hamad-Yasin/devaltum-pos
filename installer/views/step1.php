<?php ob_start(); ?>

<?php $hasPendingCode = !empty($pendingEmail); ?>

<h2>Step 1: Email Activation</h2>
<p class="muted" style="margin-bottom:20px;">Enter your email to receive your free activation code for this installation.</p>

<div style="display:inline-flex; align-items:center; gap:8px; padding:8px 14px; margin-bottom:20px; border:1px solid #dbe4f0; border-radius:999px; background:#f8fafc; color:#334155; font-size:13px; font-weight:700;">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#0f6dff" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
    Target Installation: <span style="color:#0f6dff;"><?php echo Security::escape($domain ?? 'localhost'); ?></span>
</div>

<?php if (!empty($error)): ?>
<div class="alert">
    <strong>Error:</strong> <?php echo Security::escape($error); ?>
</div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert" style="background:#dcfce7; border-color:#bbf7d0; color:#047857;">
    <strong>Status:</strong> <?php echo Security::escape($success); ?>
</div>
<?php endif; ?>

<?php if (!empty($devCode)): ?>
<div class="alert" style="background:#eff6ff; border:2px solid #3b82f6; color:#1e40af; border-radius:14px; text-align:center; padding:18px;">
    <div style="font-size:11px; font-weight:800; text-transform:uppercase; color:#2563eb; margin-bottom:4px;">🧪 Instant Setup Activation Code</div>
    <div style="font-size:32px; font-weight:900; letter-spacing:8px; color:#0f172a; margin:6px 0;"><?php echo Security::escape($devCode); ?></div>
    <div style="font-size:12px; color:#64748b;">(Code auto-filled below — click Verify & Continue!)</div>
</div>
<?php endif; ?>

<form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/installer/index" method="POST">
    <input type="hidden" name="activation_action" value="request_code">
    <div class="card">
        <div class="field">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="you@example.com" required value="<?php echo Security::escape($pendingEmail ?: ($_POST['email'] ?? 'admin@pos.local')); ?>" autocomplete="email">
        </div>
        <p class="muted" style="font-size:13px; line-height:1.55; margin:0;">
            The activation code will be sent automatically from <strong>no-reply</strong> to this email address.
        </p>
    </div>
    
    <div style="margin-top:18px; text-align:right;">
        <button type="submit" class="btn btn-primary">
            Send Free Code
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"></path><path d="m22 2-7 20-4-9-9-4 20-7Z"></path></svg>
        </button>
    </div>
</form>

<?php if ($hasPendingCode): ?>
<form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/installer/index" method="POST" style="margin-top:22px;">
    <input type="hidden" name="activation_action" value="verify_code">
    <div class="card" style="border-color:#bfdbfe; background:#f8fbff;">
        <div class="field">
            <label>Activation Code</label>
            <input type="text" name="activation_code" value="<?php echo Security::escape($devCode ?? ''); ?>" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="6-digit code" required autocomplete="one-time-code" style="font-size:22px; font-weight:800; letter-spacing:8px; text-align:center;">
        </div>
        <p class="muted" style="font-size:13px; line-height:1.55; margin:0;">
            Code sent for <strong><?php echo Security::escape($pendingEmail); ?></strong>. Please check your Inbox and Spam folder.
        </p>
        <?php if (!empty($fallbackCode)): ?>
        <div style="margin-top:12px; font-size:12px; text-align:center; padding:8px; background:#eff6ff; border-radius:10px;">
            <a href="javascript:void(0)" onclick="document.querySelector('input[name=activation_code]').value='<?php echo Security::escape($fallbackCode); ?>'; this.parentElement.innerHTML='<span style=\'color:#059669; font-weight:700;\'>✓ Code auto-filled: <?php echo Security::escape($fallbackCode); ?> — Now click Verify & Continue below!</span>';" style="color:#0f6dff; font-weight:700; text-decoration:none;">
                ⚡ Email delayed by hosting server? Click here to auto-fill code
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div style="margin-top:18px; text-align:right;">
        <button type="submit" class="btn btn-primary" style="background:#10b981; border-color:#059669;">
            Verify & Continue
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
        </button>
    </div>
</form>
<?php endif; ?>

<?php $content = ob_get_clean(); require __DIR__ . '/layout.php'; ?>
