<?php ob_start(); ?>

<div style="margin-bottom:22px;">
    <div class="muted" style="font-size:13px; font-weight:700; margin-bottom:8px;">Step 2 of 4</div>
    <h2 style="font-size:28px; margin-bottom:10px;">Server Check</h2>
    <p class="muted">Installer aapke hosting environment ko verify karega before moving to database setup.</p>
</div>

<div class="req-list" style="margin-bottom:22px;">
    <?php foreach ($requirements as $label => $status): ?>
        <div class="req-item">
            <span><?php echo htmlspecialchars($label); ?></span>
            <span class="<?php echo $status ? 'ok' : 'fail'; ?>"><?php echo $status ? 'Passed' : 'Failed'; ?></span>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($allGood): ?>
    <a class="btn btn-primary" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/installer/step3">Continue to Database Setup</a>
<?php else: ?>
    <div class="alert">Please fix the failed requirements before continuing.</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require __DIR__ . '/layout.php'; ?>
