<?php ob_start(); ?>

<style>
    .dryrun-shell { display:grid; gap:20px; max-width:1100px; }
    .dryrun-grid { display:grid; grid-template-columns:1fr .9fr; gap:20px; }
    .dryrun-box { padding:22px; border:1px solid var(--border); border-radius:24px; background:#fff; box-shadow:var(--shadow-sm); }
    .dryrun-list { max-height:420px; overflow:auto; padding:16px; border:1px solid var(--border); border-radius:18px; background:#f8fbff; }
    .dryrun-item { padding:8px 0; border-bottom:1px solid rgba(219,228,240,.6); font-family:monospace; font-size:13px; }
    .dryrun-item:last-child { border-bottom:none; }
    .dryrun-chips { display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }
    .dryrun-chip { padding:7px 11px; border-radius:999px; font-size:12px; font-weight:800; background:#e2e8f0; color:#334155; }
    @media (max-width: 1100px) { .dryrun-grid { grid-template-columns:1fr; } }
</style>

<div class="page-header">
    <div>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/index" class="page-subtitle">Back to Settings</a>
        <h1>Update Dry Run</h1>
        <p class="page-subtitle">Review validation, warnings, file impact, SQL changes and rollback readiness before applying this package.</p>
    </div>
</div>

<div class="dryrun-shell">
    <section class="dryrun-box">
        <div style="display:flex; justify-content:space-between; gap:18px; align-items:flex-start; flex-wrap:wrap;">
            <div>
                <small style="display:block; color:var(--text-muted); margin-bottom:8px;">Version</small>
                <strong style="font-size:28px;"><?php echo Security::escape($info['version']); ?></strong>
                <div class="page-subtitle" style="margin-top:8px;"><?php echo Security::escape($info['description']); ?></div>
            </div>
            <div class="dryrun-chips">
                <span class="dryrun-chip" style="background:<?php echo $info['valid'] ? '#dcfce7' : '#fee2e2'; ?>; color:<?php echo $info['valid'] ? '#166534' : '#b91c1c'; ?>;"><?php echo $info['valid'] ? 'Validated' : 'Validation Failed'; ?></span>
                <span class="dryrun-chip"><?php echo Security::escape($info['validation_method']); ?></span>
                <span class="dryrun-chip"><?php echo $info['db_changes'] ? 'SQL Included' : 'No SQL'; ?></span>
            </div>
        </div>

        <div class="app-alert <?php echo $info['valid'] ? 'alert-success' : 'alert-error'; ?>" style="margin-top:18px; margin-bottom:0;">
            <span class="material-symbols-rounded"><?php echo $info['valid'] ? 'check_circle' : 'error'; ?></span>
            <span><?php echo Security::escape($info['message']); ?></span>
        </div>
    </section>

    <div class="dryrun-grid">
        <section class="dryrun-box">
            <h3 style="margin-bottom:16px;">Files to Change</h3>
            <div class="dryrun-list">
                <?php foreach ($info['files_to_change'] as $filePath): ?>
                    <div class="dryrun-item"><?php echo Security::escape($filePath); ?></div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dryrun-box">
            <h3 style="margin-bottom:16px;">Inspection Summary</h3>
            <div class="page-subtitle" style="margin-bottom:10px;">Validation Method: <?php echo Security::escape($info['validation_method']); ?></div>
            <div class="page-subtitle" style="margin-bottom:10px;">Package Type: <?php echo $info['db_changes'] ? 'Application + Database update' : 'Application-only update'; ?></div>
            <div class="page-subtitle" style="margin-bottom:18px;">Rollback Support: Automatic backup will be created before apply.</div>

            <h4 style="margin-bottom:12px;">Warnings</h4>
            <?php if (empty($info['warnings'])): ?>
                <div class="empty-state" style="padding:16px 0;">No warnings detected.</div>
            <?php else: ?>
                <div class="warning-chips" style="margin-bottom:18px;">
                    <?php foreach ($info['warnings'] as $warning): ?>
                        <span class="warning-chip"><?php echo Security::escape($warning); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h4 style="margin-bottom:12px;">SQL Files</h4>
            <?php if (empty($info['sql_files'])): ?>
                <div class="empty-state" style="padding:16px 0;">No SQL migration files included.</div>
            <?php else: ?>
                <div class="dryrun-list" style="max-height:220px;">
                    <?php foreach ($info['sql_files'] as $sqlFile): ?>
                        <div class="dryrun-item"><?php echo Security::escape($sqlFile); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($info['valid']): ?>
        <section class="dryrun-box">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
                <div>
                    <h3 style="margin-bottom:8px;">Ready to Apply</h3>
                    <div class="page-subtitle">The system will create a database backup and project snapshot before applying this update.</div>
                </div>
                <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/apply" method="POST" data-no-ajax>
                    <?php echo Security::csrfField(); ?>
                    <input type="hidden" name="file" value="<?php echo Security::escape($file); ?>">
                    <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">system_update</span>Apply Update</button>
                </form>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
