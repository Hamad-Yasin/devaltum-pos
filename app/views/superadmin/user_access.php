<?php ob_start(); ?>
<?php
$userTab = strtolower(trim((string) ($_GET['tab'] ?? 'create')));
if (!in_array($userTab, ['create', 'list'], true)) {
    $userTab = 'create';
}
?>

<div class="page-header">
    <div>
        <h1>User Access</h1>
        <p class="page-subtitle">Assign store-wise Admin or Staff access with page permissions.</p>
    </div>
</div>

<div class="tab-strip" style="margin-bottom:18px;">
    <a class="tab-pill <?php echo $userTab === 'create' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/userAccess?tab=create<?php echo $selectedStoreId > 0 ? '&store_id=' . (int) $selectedStoreId : ''; ?>">Create User</a>
    <a class="tab-pill <?php echo $userTab === 'list' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/userAccess?tab=list<?php echo $selectedStoreId > 0 ? '&store_id=' . (int) $selectedStoreId : ''; ?>">Users List</a>
</div>

<?php if ($userTab === 'list'): ?>
<section class="card" style="margin-bottom:16px;">
    <form method="GET" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/userAccess" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
        <input type="hidden" name="tab" value="list">
        <div class="form-group" style="margin-bottom:0; min-width:240px;">
            <label class="form-label">Filter by Store</label>
            <select class="form-select" name="store_id">
                <option value="0">All Stores</option>
                <?php foreach ($stores as $store): ?>
                    <option value="<?php echo (int) $store['id']; ?>" <?php echo (int) $selectedStoreId === (int) $store['id'] ? 'selected' : ''; ?>>
                        <?php echo Security::escape($store['store_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Apply Filter</button>
    </form>
</section>

<section class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Store</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Access Pages</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="8" class="empty-state">No users found.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <?php $permissionList = array_filter(array_map('trim', explode(',', (string) $user['permissions']))); ?>
                    <tr>
                        <td><?php echo Security::escape((string) ($user['store_name'] ?? '-')); ?></td>
                        <td><strong><?php echo Security::escape($user['name']); ?></strong></td>
                        <td><?php echo Security::escape($user['email']); ?></td>
                        <td><span class="badge <?php echo $user['role'] === 'store_owner' ? 'badge-success' : 'badge-soft'; ?>"><?php echo $user['role'] === 'store_owner' ? 'Admin' : 'Staff'; ?></span></td>
                        <td><span class="badge <?php echo $user['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>"><?php echo Security::escape(ucfirst((string) $user['status'])); ?></span></td>
                        <td>
                            <?php if ((string) $user['role'] === 'store_owner'): ?>
                                <span class="badge badge-success">All Access</span>
                            <?php elseif (empty($permissionList)): ?>
                                <span class="badge badge-danger">No Access</span>
                            <?php else: ?>
                                <?php foreach ($permissionList as $permission): ?>
                                    <span class="badge badge-soft"><?php echo Security::escape($permissions[$permission] ?? $permission); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo !empty($user['created_at']) ? date('Y-m-d', strtotime($user['created_at'])) : '-'; ?></td>
                        <td>
                            <div class="table-actions">
                                <button class="icon-btn" type="button" data-open-modal="editSaUser-<?php echo (int) $user['id']; ?>">
                                    <span class="material-symbols-rounded">edit</span>
                                </button>
                                <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/deleteUserAccess/<?php echo (int) $user['id']; ?>" method="POST" data-no-ajax onsubmit="return confirm('Delete this user access?');">
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
<section class="card">
    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/createUserAccess" method="POST" data-no-ajax>
        <?php echo Security::csrfField(); ?>
        <div class="split-grid">
            <div class="form-group">
                <label class="form-label">Store</label>
                <select class="form-select" name="store_id" required>
                    <option value="">Select Store</option>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?php echo (int) $store['id']; ?>" <?php echo (int) $selectedStoreId === (int) $store['id'] ? 'selected' : ''; ?>>
                            <?php echo Security::escape($store['store_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select class="form-select sa-role-select" name="role">
                    <option value="staff">Staff</option>
                    <option value="store_owner">Admin (Full Access)</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Name</label><input class="form-input" type="text" name="name" required></div>
            <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" required></div>
            <div class="form-group"><label class="form-label">Password</label><input class="form-input" type="password" name="password" required></div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="form-group sa-permission-block" style="grid-column:1/-1;">
                <label class="form-label">Page Access Permissions</label>
                <div class="grid-cards" style="grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));">
                    <?php foreach ($permissions as $key => $label): ?>
                        <label class="badge badge-soft" style="padding:10px 12px; justify-content:space-between; width:100%;">
                            <span><?php echo Security::escape($label); ?></span>
                            <input type="checkbox" name="permissions[]" value="<?php echo Security::escape($key); ?>">
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div style="display:flex; justify-content:flex-end;">
            <button class="btn btn-primary" type="submit">Create User Access</button>
        </div>
    </form>
</section>
<?php endif; ?>

<?php foreach ($users as $user): ?>
    <?php $permissionList = array_filter(array_map('trim', explode(',', (string) $user['permissions']))); ?>
    <div id="editSaUser-<?php echo (int) $user['id']; ?>" class="modal-backdrop" style="display:none;">
        <div class="modal-card" style="width:min(940px, 100%);">
            <div class="modal-header">
                <h3>Edit User Access</h3>
                <button class="icon-btn" type="button" data-close-modal-target="editSaUser-<?php echo (int) $user['id']; ?>"><span class="material-symbols-rounded">close</span></button>
            </div>
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/updateUserAccess/<?php echo (int) $user['id']; ?>" method="POST" data-no-ajax>
                <?php echo Security::csrfField(); ?>
                <div class="split-grid">
                    <div class="form-group">
                        <label class="form-label">Store</label>
                        <select class="form-select" name="store_id" required>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?php echo (int) $store['id']; ?>" <?php echo (int) $user['store_id'] === (int) $store['id'] ? 'selected' : ''; ?>>
                                    <?php echo Security::escape($store['store_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select class="form-select sa-role-select" name="role">
                            <option value="staff" <?php echo (string) $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                            <option value="store_owner" <?php echo (string) $user['role'] === 'store_owner' ? 'selected' : ''; ?>>Admin (Full Access)</option>
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label">Name</label><input class="form-input" type="text" name="name" value="<?php echo Security::escape($user['name']); ?>" required></div>
                    <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" value="<?php echo Security::escape($user['email']); ?>" required></div>
                    <div class="form-group"><label class="form-label">New Password (optional)</label><input class="form-input" type="password" name="password"></div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?php echo (string) $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (string) $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group sa-permission-block" style="grid-column:1/-1; <?php echo (string) $user['role'] === 'store_owner' ? 'display:none;' : ''; ?>">
                        <label class="form-label">Page Access Permissions</label>
                        <div class="grid-cards" style="grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));">
                            <?php foreach ($permissions as $key => $label): ?>
                                <label class="badge badge-soft" style="padding:10px 12px; justify-content:space-between; width:100%;">
                                    <span><?php echo Security::escape($label); ?></span>
                                    <input type="checkbox" name="permissions[]" value="<?php echo Security::escape($key); ?>" <?php echo in_array($key, $permissionList, true) ? 'checked' : ''; ?>>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex; justify-content:flex-end;">
                    <button class="btn btn-primary" type="submit">Update Access</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php ob_start(); ?>
<script>
(() => {
    const roleSelects = document.querySelectorAll('.sa-role-select');
    roleSelects.forEach((select) => {
        const togglePermissions = () => {
            const form = select.closest('form');
            const block = form ? form.querySelector('.sa-permission-block') : null;
            if (!block) return;
            block.style.display = select.value === 'store_owner' ? 'none' : '';
        };
        select.addEventListener('change', togglePermissions);
        togglePermissions();
    });
})();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
