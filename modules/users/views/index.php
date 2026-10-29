<?php ob_start(); ?>

<style>
    .users-hero { background:linear-gradient(135deg, rgba(15,109,255,.12), rgba(245,158,11,.12)); border:1px solid rgba(15,109,255,.2); border-radius:18px; padding:16px 18px; margin-bottom:16px; }
    .user-pill { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:5px 10px; font-size:12px; background:#e2e8f0; }

    .users-tabs {
        display:flex;
        gap:10px;
        margin-bottom:14px;
        flex-wrap:wrap;
    }

    .users-tab-btn {
        border:1px solid var(--border);
        background:#fff;
        color:#0f172a;
        border-radius:12px;
        padding:10px 14px;
        font-weight:700;
        cursor:pointer;
        transition:all .2s ease;
    }

    .users-tab-btn:hover {
        border-color:#bfdbfe;
        box-shadow:0 8px 18px rgba(15,109,255,.10);
    }

    .users-tab-btn.active {
        background:linear-gradient(135deg, #1d4ed8, #0f6dff);
        color:#fff;
        border-color:#1d4ed8;
    }

    .users-tab-pane { display:none; }
    .users-tab-pane.active { display:block; }

    .users-list-wrap {
        overflow-x: auto;
    }

    .users-list-table {
        min-width: 920px;
    }

    .users-list-table th,
    .users-list-table td {
        white-space: nowrap;
        vertical-align: middle;
    }

    .users-list-table td:first-child {
        white-space: normal;
        min-width: 220px;
    }

    .protected-badge {
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        word-break: keep-all;
        width: max-content;
    }

    .user-form-shell {
        display:grid;
        grid-template-columns:minmax(0, 1.2fr) minmax(280px, .8fr);
        gap:14px;
    }

    .user-col {
        border:1px solid var(--border);
        border-radius:16px;
        background:linear-gradient(150deg, #fff, #f8fbff);
        padding:14px;
    }

    .user-col-title {
        font-size:13px;
        font-weight:800;
        color:#334155;
        margin-bottom:10px;
    }

    .user-col .form-group:last-child { margin-bottom:0; }

    .user-col-accent {
        border-color:rgba(15,109,255,.25);
        background:linear-gradient(150deg, rgba(15,109,255,.05), rgba(16,185,129,.06));
    }

    .role-lock-note {
        margin-top:8px;
        font-size:12px;
        color:var(--warning);
        font-weight:700;
    }

    .perm-grid {
        display:grid;
        grid-template-columns:1fr;
        gap:10px;
        max-height:300px;
        overflow-y:auto;
        padding-right:6px;
        scrollbar-width: thin;
        scrollbar-color: rgba(59, 130, 246, .55) rgba(148, 163, 184, .16);
    }

    .perm-grid::-webkit-scrollbar { width: 8px; }
    .perm-grid::-webkit-scrollbar-track {
        background: rgba(148, 163, 184, .16);
        border-radius: 999px;
    }
    .perm-grid::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, rgba(59, 130, 246, .72), rgba(29, 78, 216, .9));
        border-radius: 999px;
    }
    .perm-grid::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(180deg, rgba(96, 165, 250, .88), rgba(37, 99, 235, 1));
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
        background:linear-gradient(140deg, #fff, #f8fbff);
        transition:all .22s ease;
        cursor:pointer;
    }

    .perm-switch:hover {
        border-color:#bfdbfe;
        box-shadow:0 8px 20px rgba(15,109,255,.10);
    }

    .perm-switch input {
        position:absolute;
        opacity:0;
        pointer-events:none;
    }

    .perm-switch-name {
        font-size:13px;
        font-weight:700;
        color:#0f172a;
    }

    .perm-toggle-ui {
        width:44px;
        height:24px;
        border-radius:999px;
        background:#dbe4f0;
        position:relative;
        transition:all .22s ease;
        flex-shrink:0;
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
        border-color:rgba(15,109,255,.42);
        background:linear-gradient(145deg, rgba(15,109,255,.08), rgba(16,185,129,.08));
    }

    .perm-switch:has(input:checked) .perm-toggle-ui {
        background:linear-gradient(135deg, #1d4ed8, #0f6dff);
    }

    .perm-switch:has(input:checked) .perm-toggle-ui::after {
        left:23px;
    }

    .user-form-actions {
        margin-top:16px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }

    .form-hint {
        font-size:12px;
        color:var(--text-muted);
        font-weight:600;
    }

    .branch-shell {
        display:grid;
        grid-template-columns:minmax(0, 1fr) minmax(320px, .75fr);
        gap:16px;
        align-items:start;
    }

    .branch-form-grid {
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:14px;
    }

    .branch-form-grid .full {
        grid-column:1 / -1;
    }

    .branch-note-card {
        border:1px solid rgba(15,109,255,.18);
        border-radius:18px;
        padding:18px;
        background:linear-gradient(145deg, rgba(15,109,255,.08), rgba(16,185,129,.08));
    }

    .branch-list {
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));
        gap:14px;
    }

    .branch-card {
        border:1px solid var(--border);
        border-radius:18px;
        background:linear-gradient(150deg, #fff, #f8fbff);
        padding:16px;
        box-shadow:0 14px 30px rgba(15,23,42,.07);
    }

    .branch-card-head {
        display:flex;
        justify-content:space-between;
        gap:12px;
        align-items:flex-start;
        margin-bottom:12px;
    }

    .branch-card h3 {
        margin:0;
        font-size:16px;
    }

    .branch-meta {
        display:grid;
        gap:8px;
        color:var(--text-muted);
        font-size:13px;
        font-weight:700;
    }

    .branch-meta span {
        display:flex;
        align-items:center;
        gap:8px;
    }

    .branch-meta .material-symbols-rounded {
        font-size:18px;
        color:var(--primary);
    }

    .branch-card-actions {
        margin-top:14px;
        display:flex;
        justify-content:flex-end;
        align-items:center;
    }

    .branch-view-btn {
        min-height:38px;
        padding:0 13px;
        border-radius:12px;
        font-size:13px;
        font-weight:900;
        gap:7px;
        box-shadow:0 10px 22px rgba(15,109,255,.08);
    }

    .branch-view-btn .material-symbols-rounded {
        font-size:18px;
    }

    @media (max-width: 860px) {
        .user-form-shell { grid-template-columns:1fr; }
        .branch-shell,
        .branch-form-grid { grid-template-columns:1fr; }
        .perm-grid { max-height:240px; }
        .branch-card {
            padding:14px;
            border-radius:16px;
        }
        .branch-card-head {
            align-items:center;
        }
        .branch-card-actions {
            justify-content:flex-end;
            margin-top:12px;
        }
        .branch-view-btn {
            min-height:36px;
            padding:0 12px;
            border-radius:11px;
            font-size:12px;
        }
    }
</style>

<div class="page-header">
    <div>
        <h1>Users</h1>
        <p class="page-subtitle">Manage users, roles and module-level permissions.</p>
    </div>
</div>

<div class="users-hero">
    <strong>Permission Based Access Control</strong>
    <div class="page-subtitle">Admin has full store access. Staff users can be limited by module permissions.</div>
</div>

<div class="users-tabs" role="tablist" aria-label="Users Tabs">
    <button type="button" class="users-tab-btn active" data-users-tab="add">Add User</button>
    <button type="button" class="users-tab-btn" data-users-tab="list">User List</button>
    <?php if (empty($isBranchAccount)): ?>
    <button type="button" class="users-tab-btn" data-users-tab="branch_add">Branch</button>
    <button type="button" class="users-tab-btn" data-users-tab="branch_list">Branches List</button>
    <?php endif; ?>
</div>

<section class="users-tab-pane active" data-users-pane="add">
    <div class="card">
        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/users/create" method="POST" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <div class="user-form-shell">
                <div class="user-col">
                    <div class="user-col-title">User Details</div>
                    <div class="form-group"><label class="form-label">Name</label><input class="form-input" type="text" name="name" required></div>
                    <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" required></div>
                    <div class="form-group"><label class="form-label">Password</label><input class="form-input" type="password" name="password" required></div>
                </div>

                <div class="user-col user-col-accent">
                    <div class="user-col-title">Role & Access</div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select class="form-select user-role-select" name="role">
                            <option value="staff">Staff</option>
                            <option value="store_owner">Admin (Full Access)</option>
                        </select>
                    </div>
                    <div class="form-group permission-block" style="margin-bottom:0;">
                        <label class="form-label">Permissions (Slide toggles)</label>
                        <div class="perm-grid">
                            <?php foreach ($permissions as $key => $label): ?>
                                <label class="perm-switch">
                                    <input type="checkbox" name="permissions[]" value="<?php echo Security::escape($key); ?>">
                                    <span class="perm-switch-name"><?php echo Security::escape($label); ?></span>
                                    <span class="perm-toggle-ui"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="user-form-actions">
                <span class="form-hint">Professional inline add-user flow.</span>
                <button class="btn btn-primary" type="submit">Create User</button>
            </div>
        </form>
    </div>
</section>

<section class="users-tab-pane" data-users-pane="list">
    <div class="card table-wrap users-list-wrap">
        <table class="users-list-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Permissions</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="5" class="empty-state">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <?php
                            $permissionList = array_filter(array_map('trim', explode(',', (string) $user['permissions'])));
                            $isProtectedAdmin = (int) $user['id'] === (int) ($protectedAdminId ?? 0);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo Security::escape($user['name']); ?></strong>
                                <div class="page-subtitle" style="margin-top:6px;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                                <?php if ($isProtectedAdmin): ?>
                                    <div class="badge badge-warn protected-badge" style="margin-top:8px;">Store Owner (Protected)</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo Security::escape($user['email']); ?></td>
                            <td><span class="badge <?php echo $user['role'] === 'store_owner' ? 'badge-success' : 'badge-soft'; ?>"><?php echo ucfirst(Security::escape(str_replace('_', ' ', $user['role']))); ?></span></td>
                            <td>
                                <?php if ($user['role'] === 'store_owner'): ?>
                                    <span class="badge badge-success">All Access</span>
                                <?php elseif (empty($permissionList)): ?>
                                    <span class="badge badge-danger">No Access</span>
                                <?php else: ?>
                                    <?php foreach ($permissionList as $permission): ?>
                                        <span class="user-pill"><?php echo Security::escape($permissions[$permission] ?? $permission); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="icon-btn" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/users/userProfile/<?php echo (int) $user['id']; ?>" title="View / Edit User"><span class="material-symbols-rounded">visibility</span></a>
                                    <?php if ((int) $user['id'] !== (int) Auth::id() && !$isProtectedAdmin): ?>
                                        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/users/delete/<?php echo (int) $user['id']; ?>" method="POST" data-no-ajax>
                                            <?php echo Security::csrfField(); ?>
                                            <button class="icon-btn" type="submit"><span class="material-symbols-rounded">delete_forever</span></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if (empty($isBranchAccount)): ?>
<section class="users-tab-pane" data-users-pane="branch_add">
    <div class="branch-shell" style="margin-bottom:18px;">
        <div class="card">
            <h3 style="margin-bottom:14px;">Create Branch Account</h3>
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/users/createBranch" method="POST" data-no-ajax>
                <?php echo Security::csrfField(); ?>
                <div class="branch-form-grid">
                    <div class="form-group">
                        <label class="form-label">Branch Name</label>
                        <input class="form-input" type="text" name="branch_name" placeholder="e.g. Lake City Branch" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch Code</label>
                        <input class="form-input" type="text" name="branch_code" placeholder="Auto if empty">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch Admin Name</label>
                        <input class="form-input" type="text" name="owner_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch Admin Email</label>
                        <input class="form-input" type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input class="form-input" type="password" name="password" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input class="form-input" type="text" name="phone" placeholder="+92...">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Address</label>
                        <input class="form-input" type="text" name="address" placeholder="Branch address">
                    </div>
                </div>
                <div class="user-form-actions">
                    <span class="form-hint">Branch has its own dashboard, products, sales, customers and reports data.</span>
                    <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">add_business</span>Create Branch</button>
                </div>
            </form>
        </div>

        <aside class="branch-note-card">
            <h3 style="margin-bottom:10px;">Branch Dashboard Isolation</h3>
            <p class="page-subtitle" style="line-height:1.6;">New branch gets a separate account and store context. When branch admin logs in, dashboard and all modules show only that branch's own records.</p>
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:14px;">
                <span class="badge badge-success">Separate Data</span>
                <span class="badge badge-soft">Own Login</span>
                <span class="badge badge-soft">Branding Copied</span>
            </div>
        </aside>
    </div>

    </section>

<section class="users-tab-pane" data-users-pane="branch_list">
    <div class="card">
        <div style="display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:14px;">
            <h3>Branch Accounts</h3>
            <span class="badge badge-soft"><?php echo count($branches ?? []); ?> Branches</span>
        </div>

        <?php if (empty($branches)): ?>
            <div class="empty-state">No branch accounts created yet.</div>
        <?php else: ?>
            <div class="branch-list">
                <?php foreach ($branches as $branch): ?>
                    <article class="branch-card">
                        <div class="branch-card-head">
                            <div>
                                <h3><?php echo Security::escape($branch['store_name']); ?></h3>
                                <div class="page-subtitle" style="margin-top:5px;"><?php echo Security::escape($branch['branch_code'] ?: 'Branch'); ?></div>
                            </div>
                            <span class="badge <?php echo ($branch['status'] ?? '') === 'active' ? 'badge-success' : 'badge-danger'; ?>"><?php echo Security::escape(ucfirst((string) ($branch['status'] ?? 'active'))); ?></span>
                        </div>
                        <div class="branch-meta">
                            <span><span class="material-symbols-rounded">admin_panel_settings</span><?php echo Security::escape($branch['admin_name'] ?: $branch['owner_name']); ?></span>
                            <span><span class="material-symbols-rounded">mail</span><?php echo Security::escape($branch['admin_email'] ?: $branch['email']); ?></span>
                            <span><span class="material-symbols-rounded">call</span><?php echo Security::escape($branch['phone'] ?: '-'); ?></span>
                            <span><span class="material-symbols-rounded">dashboard</span>Branch Dashboard</span>
                        </div>
                        <div class="branch-card-actions">
                            <a class="btn btn-secondary branch-view-btn" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/users/branch/<?php echo (int) $branch['id']; ?>">
                                <span class="material-symbols-rounded">visibility</span>View Branch
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php foreach ($users as $user): ?>
    <?php
        $permissionList = array_filter(array_map('trim', explode(',', (string) $user['permissions'])));
        $isProtectedAdmin = (int) $user['id'] === (int) ($protectedAdminId ?? 0);
    ?>
    <div id="editUserModal-<?php echo (int) $user['id']; ?>" class="modal-backdrop" style="display:none;">
        <div class="modal-card" style="width:min(860px, 100%);">
            <div class="modal-header">
                <h3>Edit User</h3>
                <button class="icon-btn" type="button" data-close-modal-target="editUserModal-<?php echo (int) $user['id']; ?>"><span class="material-symbols-rounded">close</span></button>
            </div>
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/users/update/<?php echo (int) $user['id']; ?>" method="POST" data-no-ajax>
                <?php echo Security::csrfField(); ?>
                <div class="user-form-shell">
                    <div class="user-col">
                        <div class="user-col-title">User Details</div>
                        <div class="form-group"><label class="form-label">Name</label><input class="form-input" type="text" name="name" value="<?php echo Security::escape($user['name']); ?>" required></div>
                        <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" value="<?php echo Security::escape($user['email']); ?>" required></div>
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
                                    <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                    <option value="store_owner" <?php echo $user['role'] === 'store_owner' ? 'selected' : ''; ?>>Admin (Full Access)</option>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="form-group permission-block" style="margin-bottom:0; <?php echo $user['role'] === 'store_owner' ? 'display:none;' : ''; ?>">
                            <label class="form-label">Permissions (Slide toggles)</label>
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
                <div class="user-form-actions">
                    <span class="form-hint">Role aur permissions ke mutabiq access auto update hota hai.</span>
                    <button class="btn btn-primary" type="submit">Update User</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php ob_start(); ?>
<script>
    (() => {
        const tabButtons = Array.from(document.querySelectorAll('[data-users-tab]'));
        const panes = Array.from(document.querySelectorAll('[data-users-pane]'));

        const setTab = (tab) => {
            tabButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.usersTab === tab));
            panes.forEach((pane) => pane.classList.toggle('active', pane.dataset.usersPane === tab));
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url.toString());
        };

        tabButtons.forEach((btn) => {
            btn.addEventListener('click', () => setTab(btn.dataset.usersTab));
        });

        const initialTab = new URLSearchParams(window.location.search).get('tab');
        if (initialTab === 'add' || initialTab === 'list' || initialTab === 'branch_add' || initialTab === 'branch_list') {
            const targetButton = tabButtons.find((btn) => btn.dataset.usersTab === initialTab);
            if (targetButton) setTab(initialTab);
        }

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
