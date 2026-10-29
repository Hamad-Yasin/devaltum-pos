<?php ob_start(); ?>

<?php
$tab = $tab ?? 'new';
$newNotifications = $newNotifications ?? [];
$seenNotifications = $seenNotifications ?? [];
$isSuperadminNotifications = !empty($isSuperadminNotifications);
?>

<div class="page-header">
    <div>
        <h1><?php echo $isSuperadminNotifications ? 'Platform Notifications' : 'Notifications'; ?></h1>
        <p class="page-subtitle"><?php echo $isSuperadminNotifications ? 'Plan expiry alerts for store accounts, separated from admin panel notifications.' : 'Due-date credit reminders with New and Seen state tracking.'; ?></p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button type="button" class="btn <?php echo $tab === 'new' ? 'btn-primary' : 'btn-secondary'; ?>" data-notify-tab-trigger="new">New (<?php echo count($newNotifications); ?>)</button>
        <button type="button" class="btn <?php echo $tab === 'seen' ? 'btn-primary' : 'btn-secondary'; ?>" data-notify-tab-trigger="seen">Seen (<?php echo count($seenNotifications); ?>)</button>
        <?php if (!empty($newNotifications)): ?>
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/notifications/markAllSeen" method="POST" data-no-ajax>
                <?php echo Security::csrfField(); ?>
                <button class="btn btn-secondary" type="submit">Mark All Seen</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card" data-notify-panel="new" style="display:<?php echo $tab === 'seen' ? 'none' : 'block'; ?>;">
    <?php if (empty($newNotifications)): ?>
        <div class="empty-state">No new notifications right now.</div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <?php foreach ($newNotifications as $notification): ?>
                <div style="padding:14px 16px; border:1px solid var(--border); border-radius:14px; background:#fff;">
                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                        <div>
                            <strong><?php echo $isSuperadminNotifications ? 'Plan Alert: ' : 'Credit Due: '; ?><?php echo Security::escape($notification['title']); ?></strong>
                            <?php if ($isSuperadminNotifications): ?>
                                <div class="page-subtitle" style="margin-top:6px;">Store: <?php echo Security::escape($notification['store_name'] ?? '-'); ?> | Plan: <?php echo Security::escape($notification['plan_name'] ?? '-'); ?></div>
                            <?php else: ?>
                                <div class="page-subtitle" style="margin-top:6px;">Invoice: <?php echo Security::escape($notification['custom_id'] ?? '-'); ?> | Customer: <?php echo Security::escape($notification['customer_name'] ?? '-'); ?></div>
                            <?php endif; ?>
                            <div class="page-subtitle" style="margin-top:4px;"><?php echo Security::escape($notification['message'] ?: 'Payment due date reached.'); ?></div>
                        </div>
                        <span class="badge badge-danger">
                            <?php echo date('M d, h:i A', strtotime($notification['due_at'] ?? $notification['created_at'] ?? 'now')); ?>
                        </span>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
                        <a class="btn btn-secondary" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/notifications/open/<?php echo (int) $notification['id']; ?>"><span class="material-symbols-rounded">visibility</span>Open</a>
                        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/notifications/markSeen/<?php echo (int) $notification['id']; ?>" method="POST" data-no-ajax>
                            <?php echo Security::csrfField(); ?>
                            <button class="btn btn-secondary" type="submit"><span class="material-symbols-rounded">done</span>Mark Seen</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card" data-notify-panel="seen" style="display:<?php echo $tab === 'seen' ? 'block' : 'none'; ?>;">
    <?php if (empty($seenNotifications)): ?>
        <div class="empty-state">No seen notifications right now.</div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <?php foreach ($seenNotifications as $notification): ?>
                <div style="padding:14px 16px; border:1px solid var(--border); border-radius:14px; background:#fff;">
                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                        <div>
                            <strong><?php echo $isSuperadminNotifications ? 'Plan Alert: ' : 'Credit Due: '; ?><?php echo Security::escape($notification['title']); ?></strong>
                            <?php if ($isSuperadminNotifications): ?>
                                <div class="page-subtitle" style="margin-top:6px;">Store: <?php echo Security::escape($notification['store_name'] ?? '-'); ?> | Plan: <?php echo Security::escape($notification['plan_name'] ?? '-'); ?></div>
                            <?php else: ?>
                                <div class="page-subtitle" style="margin-top:6px;">Invoice: <?php echo Security::escape($notification['custom_id'] ?? '-'); ?> | Customer: <?php echo Security::escape($notification['customer_name'] ?? '-'); ?></div>
                            <?php endif; ?>
                            <div class="page-subtitle" style="margin-top:4px;"><?php echo Security::escape($notification['message'] ?: 'Payment due date reached.'); ?></div>
                        </div>
                        <span class="badge badge-soft">
                            <?php echo date('M d, h:i A', strtotime($notification['due_at'] ?? $notification['created_at'] ?? 'now')); ?>
                        </span>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
                        <a class="btn btn-secondary" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/notifications/open/<?php echo (int) $notification['id']; ?>"><span class="material-symbols-rounded">visibility</span>Open</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php ob_start(); ?>
<script>
    (() => {
        const buttons = document.querySelectorAll('[data-notify-tab-trigger]');
        const panels = document.querySelectorAll('[data-notify-panel]');

        const activate = (tab) => {
            sessionStorage.setItem('tab_' + new URLSearchParams(window.location.search).get('url'), tab);
            buttons.forEach((btn) => {
                const active = btn.dataset.notifyTabTrigger === tab;
                btn.classList.toggle('btn-primary', active);
                btn.classList.toggle('btn-secondary', !active);
            });
            panels.forEach((panel) => {
                panel.style.display = panel.dataset.notifyPanel === tab ? 'block' : 'none';
            });
        };

        buttons.forEach((btn) => {
            btn.addEventListener('click', () => activate(btn.dataset.notifyTabTrigger));
        });
    })();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
