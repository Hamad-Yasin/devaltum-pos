<?php ob_start(); ?>
<?php
$plansTab = strtolower(trim((string) ($_GET['tab'] ?? 'create')));
if (!in_array($plansTab, ['create', 'list', 'usage'], true)) {
    $plansTab = 'create';
}
?>

<div class="page-header">
    <div>
        <h1>Subscription Plans</h1>
        <p class="page-subtitle">Create plans and track store-level usage.</p>
    </div>
</div>

<div class="tab-strip" style="margin-bottom:18px;">
    <a class="tab-pill <?php echo $plansTab === 'create' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/plans?tab=create">Create Plan</a>
    <a class="tab-pill <?php echo $plansTab === 'list' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/plans?tab=list">Plans Table</a>
    <a class="tab-pill <?php echo $plansTab === 'usage' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/plans?tab=usage">Plan Usage</a>
</div>

<?php if ($plansTab === 'usage'): ?>
<section class="card table-wrap">
    <table>
        <thead><tr><th>Store Name</th><th>Plan Name</th><th>Start Date</th><th>Expiry Date</th><th>Subscription Status</th></tr></thead>
        <tbody>
            <?php if (empty($planUsage)): ?>
                <tr><td colspan="5" class="empty-state">No plan usage records found.</td></tr>
            <?php else: ?>
                <?php foreach ($planUsage as $usage): ?>
                    <?php $status = strtolower((string) ($usage['subscription_status'] ?? 'inactive')); ?>
                    <tr>
                        <td><?php echo Security::escape((string) $usage['store_name']); ?></td>
                        <td><?php echo Security::escape(ucfirst((string) ($usage['plan_name'] ?? 'free'))); ?></td>
                        <td><?php echo !empty($usage['subscription_started_at']) ? date('Y-m-d', strtotime($usage['subscription_started_at'])) : '-'; ?></td>
                        <td><?php echo !empty($usage['subscription_expires_at']) ? date('Y-m-d', strtotime($usage['subscription_expires_at'])) : '-'; ?></td>
                        <td><span class="badge <?php echo $status === 'active' ? 'badge-success' : ($status === 'suspended' ? 'badge-warn' : 'badge-danger'); ?>"><?php echo Security::escape(ucfirst($status)); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
<?php elseif ($plansTab === 'list'): ?>
<section class="card table-wrap">
    <table>
        <thead><tr><th>Plan</th><th>Monthly Price</th><th>Duration</th><th>Limits</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if (empty($plans)): ?>
                <tr><td colspan="6" class="empty-state">No plans found.</td></tr>
            <?php else: ?>
                <?php foreach ($plans as $plan): ?>
                    <tr>
                        <td><strong><?php echo Security::escape($plan['plan_name']); ?></strong></td>
                        <td><?php echo App::money($plan['monthly_price']); ?></td>
                        <td><?php echo (int) $plan['billing_duration_days']; ?> days</td>
                        <td>Staff: <?php echo (int) $plan['max_staff_limit']; ?> | Products: <?php echo (int) $plan['max_products_limit']; ?></td>
                        <td><span class="badge <?php echo $plan['status'] === 'active' ? 'badge-success' : 'badge-soft'; ?>"><?php echo Security::escape(ucfirst($plan['status'])); ?></span></td>
                        <td>
                            <div class="table-actions">
                                <button class="icon-btn" type="button" data-open-modal="editPlanModal-<?php echo (int) $plan['id']; ?>"><span class="material-symbols-rounded">edit</span></button>
                                <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/deletePlan/<?php echo (int) $plan['id']; ?>" method="POST" data-no-ajax onsubmit="return confirm('Delete this plan?');">
                                    <?php echo Security::csrfField(); ?>
                                    <button class="icon-btn" type="submit"><span class="material-symbols-rounded">delete</span></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
<?php else: ?>
<section class="card" style="margin-bottom:18px;">
    <h3 style="margin-bottom:14px;">Create Plan</h3>
    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/createPlan" method="POST" data-no-ajax>
        <?php echo Security::csrfField(); ?>
        <div class="split-grid">
            <div class="form-group"><label class="form-label">Plan Name</label><input class="form-input" type="text" name="plan_name" required></div>
            <div class="form-group"><label class="form-label">Monthly Price</label><input class="form-input" type="number" name="monthly_price" step="0.01" value="0"></div>
            <div class="form-group"><label class="form-label">Duration (Days)</label><input class="form-input" type="number" name="billing_duration_days" value="30"></div>
            <div class="form-group"><label class="form-label">Staff Limit</label><input class="form-input" type="number" name="max_staff_limit" value="0"></div>
            <div class="form-group"><label class="form-label">Product Limit</label><input class="form-input" type="number" name="max_products_limit" value="0"></div>
            <div class="form-group"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
        </div>
        <button class="btn btn-primary" type="submit">Create Plan</button>
    </form>
</section>
<?php endif; ?>

<?php foreach ($plans as $plan): ?>
<div id="editPlanModal-<?php echo (int) $plan['id']; ?>" class="modal-backdrop" style="display:none;">
    <div class="modal-card" style="width:min(900px, 100%);">
        <div class="modal-header"><h3>Edit Plan</h3><button class="icon-btn" type="button" data-close-modal-target="editPlanModal-<?php echo (int) $plan['id']; ?>"><span class="material-symbols-rounded">close</span></button></div>
        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/updatePlan/<?php echo (int) $plan['id']; ?>" method="POST" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <div class="split-grid">
                <div class="form-group"><label class="form-label">Plan Name</label><input class="form-input" type="text" name="plan_name" value="<?php echo Security::escape($plan['plan_name']); ?>" required></div>
                <div class="form-group"><label class="form-label">Monthly Price</label><input class="form-input" type="number" name="monthly_price" step="0.01" value="<?php echo Security::escape($plan['monthly_price']); ?>"></div>
                <div class="form-group"><label class="form-label">Duration (Days)</label><input class="form-input" type="number" name="billing_duration_days" value="<?php echo (int) $plan['billing_duration_days']; ?>"></div>
                <div class="form-group"><label class="form-label">Staff Limit</label><input class="form-input" type="number" name="max_staff_limit" value="<?php echo (int) $plan['max_staff_limit']; ?>"></div>
                <div class="form-group"><label class="form-label">Product Limit</label><input class="form-input" type="number" name="max_products_limit" value="<?php echo (int) $plan['max_products_limit']; ?>"></div>
                <div class="form-group"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" <?php echo $plan['status'] === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo $plan['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option></select></div>
            </div>
            <button class="btn btn-primary" type="submit">Update Plan</button>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
