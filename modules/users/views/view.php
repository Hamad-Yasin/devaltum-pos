<?php ob_start(); ?>

<?php
$permissionList = array_filter(array_map('trim', explode(',', (string) ($userRecord['permissions'] ?? ''))));
$isProtectedAdmin = (int) ($userRecord['id'] ?? 0) === (int) ($protectedAdminId ?? 0);
$roleLabel = ucfirst(str_replace('_', ' ', (string) ($userRecord['role'] ?? 'staff')));
?>

<style>
    .profile-top {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:14px;
        margin-bottom:16px;
        flex-wrap:wrap;
    }

    .profile-back {
        display:inline-flex;
        align-items:center;
        gap:8px;
        min-height:44px;
        padding:0 14px;
        border-radius:14px;
        border:1px solid rgba(15,109,255,.24);
        background:linear-gradient(135deg, #0f6dff, #1d4ed8);
        color:#fff;
        font-weight:800;
        text-decoration:none;
        box-shadow:0 14px 28px rgba(15,109,255,.18);
    }

    .profile-grid {
        display:grid;
        grid-template-columns:minmax(280px, .72fr) minmax(0, 1.28fr);
        gap:16px;
        align-items:start;
    }

    .profile-card,
    .edit-card {
        border:1px solid var(--border);
        border-radius:18px;
        background:linear-gradient(150deg, #fff, #f8fbff);
        box-shadow:0 16px 34px rgba(15,23,42,.07);
        padding:18px;
    }

    .profile-avatar-large {
        width:76px;
        height:76px;
        border-radius:22px;
        display:grid;
        place-items:center;
        background:linear-gradient(135deg, rgba(15,109,255,.13), rgba(16,185,129,.16));
        color:#0f172a;
        font-size:28px;
        font-weight:900;
        margin-bottom:14px;
    }

    .profile-name {
        margin:0;
        font-size:24px;
        letter-spacing:0;
    }

    .profile-kv {
        display:grid;
        gap:10px;
        margin-top:18px;
    }

    .profile-kv div {
        display:flex;
        justify-content:space-between;
        gap:12px;
        border-bottom:1px dashed var(--border);
        padding-bottom:10px;
        color:var(--text-muted);
        font-size:13px;
        font-weight:700;
    }

    .profile-kv strong {
        color:#0f172a;
        text-align:right;
    }

    .user-form-shell {
        display:grid;
        grid-template-columns:minmax(0, 1fr) minmax(280px, .9fr);
        gap:14px;
    }

    .user-col {
        border:1px solid var(--border);
        border-radius:16px;
        background:#fff;
        padding:14px;
    }

    .user-col-accent {
        background:linear-gradient(150deg, rgba(15,109,255,.05), rgba(16,185,129,.06));
        border-color:rgba(15,109,255,.22);
    }

    .user-col-title {
        font-size:13px;
        font-weight:900;
        color:#334155;
        margin-bottom:12px;
    }

    .perm-grid {
        display:grid;
        grid-template-columns:1fr;
        gap:10px;
        max-height:320px;
        overflow:auto;
        padding-right:6px;
    }

    .perm-switch {
        position:relative;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        border:1px solid var(--border);
        border-radius:14px;
        padding:11px 12px;
        background:#fff;
        cursor:pointer;
    }

    .perm-switch input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .perm-switch-name {
        font-size:13px;
        font-weight:800;
    }

    .perm-toggle-ui {
        width:44px;
        height:24px;
        border-radius:999px;
        background:#dbe4f0;
        position:relative;
        flex-shrink:0;
        transition:all .22s ease;
    }

    .perm-toggle-ui::after {
        content:'';
        position:absolute;
        top:3px;
        left:3px;
        width:18px;
        height:18px;
        border-radius:50%;
        background:#fff;
        box-shadow:0 2px 8px rgba(15,23,42,.18);
        transition:all .22s ease;
    }

    .perm-switch:has(input:checked) {
        border-color:rgba(15,109,255,.4);
        background:linear-gradient(145deg, rgba(15,109,255,.08), rgba(16,185,129,.08));
    }

    .perm-switch:has(input:checked) .perm-toggle-ui {
        background:linear-gradient(135deg, #1d4ed8, #0f6dff);
    }

    .perm-switch:has(input:checked) .perm-toggle-ui::after {
        left:23px;
    }

    .profile-actions {
        display:flex;
        justify-content:flex-end;
        gap:10px;
        margin-top:16px;
        flex-wrap:wrap;
    }

    .role-lock-note {
        margin-top:8px;
        font-size:12px;
        color:var(--warning);
        font-weight:800;
    }

    @media (max-width: 900px) {
        .profile-grid,
        .user-form-shell { grid-template-columns:1fr; }
    }
</style>

<div class="profile-top">
    <div>
        <h1>User Profile</h1>
        <p class="page-subtitle">View user details and update role permissions from a full page.</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <?php if (!$isProtectedAdmin && (int) ($userRecord['id'] ?? 0) !== (int) Auth::id()): ?>
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/users/loginUser/<?php echo (int) $userRecord['id']; ?>" method="POST" target="_blank" data-no-ajax>
                <?php echo Security::csrfField(); ?>
                <button class="btn btn-primary" type="submit">
                    <span class="material-symbols-rounded">login</span>Login as User
                </button>
            </form>
        <?php endif; ?>
        <a class="profile-back" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/users/index?tab=list">
            <span class="material-symbols-rounded">arrow_back</span>Back to Users
        </a>
    </div>
</div>

<div class="profile-grid">
    <aside class="profile-card">
        <div class="profile-avatar-large"><?php echo strtoupper(substr((string) ($userRecord['name'] ?? 'U'), 0, 1)); ?></div>
        <h2 class="profile-name"><?php echo Security::escape($userRecord['name']); ?></h2>
        <p class="page-subtitle" style="margin-top:6px;"><?php echo Security::escape($userRecord['email']); ?></p>
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:14px;">
            <span class="badge <?php echo ($userRecord['role'] ?? '') === 'store_owner' ? 'badge-success' : 'badge-soft'; ?>"><?php echo Security::escape($roleLabel); ?></span>
            <?php if ($isProtectedAdmin): ?>
                <span class="badge badge-warn">Protected</span>
            <?php endif; ?>
        </div>
        <div class="profile-kv">
            <div><span>User ID</span><strong>#<?php echo (int) $userRecord['id']; ?></strong></div>
            <div><span>Created</span><strong><?php echo !empty($userRecord['created_at']) ? date('M d, Y h:i A', strtotime($userRecord['created_at'])) : '-'; ?></strong></div>
            <div><span>Access</span><strong><?php echo ($userRecord['role'] ?? '') === 'store_owner' ? 'All Modules' : count($permissionList) . ' Modules'; ?></strong></div>
        </div>
    </aside>

    <section class="edit-card">
        <h3 style="margin-bottom:14px;">Edit User</h3>
        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/users/update/<?php echo (int) $userRecord['id']; ?>" method="POST" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <input type="hidden" name="redirect_to" value="view">
            <div class="user-form-shell">
                <div class="user-col">
                    <div class="user-col-title">User Details</div>
                    <div class="form-group"><label class="form-label">Name</label><input class="form-input" type="text" name="name" value="<?php echo Security::escape($userRecord['name']); ?>" required></div>
                    <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" value="<?php echo Security::escape($userRecord['email']); ?>" required></div>
                    <div class="form-group"><label class="form-label">New Password (optional)</label><input class="form-input" type="password" name="password"></div>
                </div>

                <div class="user-col user-col-accent">
                    <div class="user-col-title">Role & Access</div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <?php if ($isProtectedAdmin): ?>
                            <input class="form-input" type="text" value="Admin (Protected)" disabled>
                            <input type="hidden" name="role" value="store_owner">
                            <div class="role-lock-note">Default admin role is locked.</div>
                        <?php else: ?>
                            <select class="form-select user-role-select" name="role">
                                <option value="staff" <?php echo ($userRecord['role'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                <option value="store_owner" <?php echo ($userRecord['role'] ?? '') === 'store_owner' ? 'selected' : ''; ?>>Admin (Full Access)</option>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="form-group permission-block" style="margin-bottom:0; <?php echo ($userRecord['role'] ?? '') === 'store_owner' ? 'display:none;' : ''; ?>">
                        <label class="form-label">Permissions</label>
                        <div class="perm-grid">
                            <?php foreach ($permissions as $key => $label): ?>
                                <label class="perm-switch">
                                    <input type="checkbox" name="permissions[]" value="<?php echo Security::escape($key); ?>" <?php echo in_array($key, $permissionList, true) ? 'checked' : ''; ?>>
                                    <span class="perm-switch-name"><?php echo Security::escape($label); ?></span>
                                    <span class="perm-toggle-ui"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="profile-actions">
                <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>Update User</button>
            </div>
        </form>
    </section>
</div>

<?php ob_start(); ?>
<script>
    (() => {
        document.querySelectorAll('.user-role-select').forEach((select) => {
            const toggleBlock = () => {
                const form = select.closest('form');
                const block = form?.querySelector('.permission-block');
                if (!block) return;
                block.style.display = select.value === 'store_owner' ? 'none' : '';
            };
            select.addEventListener('change', toggleBlock);
            toggleBlock();
        });
    })();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
