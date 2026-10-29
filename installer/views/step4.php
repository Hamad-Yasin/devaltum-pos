<?php ob_start(); ?>

<div style="margin-bottom:22px;">
    <div class="muted" style="font-size:13px; font-weight:700; margin-bottom:8px;">Step 4 of 4</div>
    <h2 style="font-size:28px; margin-bottom:10px;">Create Super Admin Account</h2>
    <p class="muted">Final step. Create only the Super Admin account for platform control.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/installer/step4" method="POST">
    <div class="field">
        <label>Username</label>
        <input type="text" name="username" required>
    </div>
    <div class="field">
        <label>Email Address</label>
        <input type="email" name="email" required>
    </div>
    <div class="field">
        <label>Password</label>
        <input type="password" name="password" required>
    </div>
    <button class="btn btn-primary" type="submit">Finish Installation</button>
</form>

<?php $content = ob_get_clean(); require __DIR__ . '/layout.php'; ?>
