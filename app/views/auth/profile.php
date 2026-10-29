<?php ob_start(); ?>

<div class="page-header">
    <div>
        <h1>My Profile</h1>
        <p class="page-subtitle">Update your account details and change password from one place.</p>
        <?php if (!empty($isProtectedPrimarySuperAdmin)): ?>
            <p class="page-subtitle" style="color:#d97706; font-weight:700;">Primary Super Admin account is protected and read-only.</p>
        <?php endif; ?>
    </div>
</div>

<div class="split-grid">
    <section class="card">
        <h3 style="margin-bottom: 18px;">Account Details</h3>
        <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/auth/profile">
            <?php echo Security::csrfField(); ?>
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input class="form-input" type="text" name="name" value="<?php echo Security::escape($user['name']); ?>" required <?php echo !empty($isProtectedPrimarySuperAdmin) ? 'disabled' : ''; ?>>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input class="form-input" type="email" name="email" value="<?php echo Security::escape($user['email']); ?>" required <?php echo !empty($isProtectedPrimarySuperAdmin) ? 'disabled' : ''; ?>>
            </div>
            <?php if (empty($isProtectedPrimarySuperAdmin)): ?>
                <button class="btn btn-primary" type="submit">Save Profile</button>
            <?php else: ?>
                <button class="btn btn-secondary" type="button" disabled>Read Only Account</button>
            <?php endif; ?>
        </form>
    </section>

    <section class="card">
        <h3 style="margin-bottom: 18px;">Change Password</h3>
        <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/auth/profile">
            <?php echo Security::csrfField(); ?>
            <input type="hidden" name="action" value="password">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input class="form-input" type="password" name="current_password" required <?php echo !empty($isProtectedPrimarySuperAdmin) ? 'disabled' : ''; ?>>
            </div>
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input class="form-input" type="password" name="new_password" required <?php echo !empty($isProtectedPrimarySuperAdmin) ? 'disabled' : ''; ?>>
            </div>
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input class="form-input" type="password" name="confirm_password" required <?php echo !empty($isProtectedPrimarySuperAdmin) ? 'disabled' : ''; ?>>
            </div>
            <?php if (empty($isProtectedPrimarySuperAdmin)): ?>
                <button class="btn btn-secondary" type="submit">Update Password</button>
            <?php else: ?>
                <button class="btn btn-secondary" type="button" disabled>Password Locked</button>
            <?php endif; ?>
        </form>
    </section>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
