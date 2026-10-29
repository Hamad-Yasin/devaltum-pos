<?php ob_start(); ?>

<div style="margin-bottom:22px;">
    <div class="muted" style="font-size:13px; font-weight:700; margin-bottom:8px;">Step 3 of 4</div>
    <h2 style="font-size:28px; margin-bottom:10px;">Database Setup</h2>
    <p class="muted">Hosting ke MySQL credentials yahan enter karein. Installer config file aur tables create kar dega.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/installer/step3" method="POST">
    <div class="field">
        <label>Database Host</label>
        <input type="text" name="host" value="localhost" required>
    </div>
    <div class="field">
        <label>Database Name</label>
        <input type="text" name="dbname" placeholder="your_database_name" required>
    </div>
    <div class="field">
        <label>Database User</label>
        <input type="text" name="user" value="root" required>
    </div>
    <div class="field">
        <label>Database Password</label>
        <input type="password" name="pass" placeholder="Database password">
    </div>
    <button class="btn btn-primary" type="submit">Create Config & Tables</button>
</form>

<?php $content = ob_get_clean(); require __DIR__ . '/layout.php'; ?>
