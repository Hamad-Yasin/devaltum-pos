<?php ob_start(); ?>

<style>
    .branch-top {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:14px;
        flex-wrap:wrap;
        margin-bottom:16px;
    }

    .branch-back {
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

    .branch-profile-grid {
        display:grid;
        grid-template-columns:minmax(280px, .78fr) minmax(0, 1.22fr);
        gap:16px;
        align-items:start;
    }

    .branch-panel {
        border:1px solid var(--border);
        border-radius:18px;
        background:linear-gradient(150deg, #fff, #f8fbff);
        box-shadow:0 16px 34px rgba(15,23,42,.07);
        padding:18px;
    }

    .branch-mark {
        width:76px;
        height:76px;
        border-radius:22px;
        display:grid;
        place-items:center;
        background:linear-gradient(135deg, rgba(15,109,255,.13), rgba(16,185,129,.16));
        color:#0f172a;
        margin-bottom:14px;
    }

    .branch-mark .material-symbols-rounded {
        font-size:34px;
    }

    .branch-title {
        margin:0;
        font-size:25px;
        letter-spacing:0;
    }

    .branch-kpi {
        display:grid;
        grid-template-columns:repeat(3, minmax(0, 1fr));
        gap:12px;
        margin-bottom:16px;
    }

    .branch-kpi-card {
        border:1px solid var(--border);
        border-radius:16px;
        background:#fff;
        padding:14px;
    }

    .branch-kpi-card span {
        display:block;
        color:var(--text-muted);
        font-size:12px;
        font-weight:800;
        margin-bottom:7px;
    }

    .branch-kpi-card strong {
        font-size:18px;
        color:#0f172a;
    }

    .detail-list {
        display:grid;
        gap:12px;
    }

    .detail-row {
        display:grid;
        grid-template-columns:170px minmax(0, 1fr);
        gap:12px;
        padding:13px 0;
        border-bottom:1px dashed var(--border);
        font-weight:700;
    }

    .detail-row span {
        color:var(--text-muted);
    }

    .detail-row strong {
        color:#0f172a;
        overflow-wrap:anywhere;
    }

    .login-box {
        border:1px solid rgba(15,109,255,.2);
        border-radius:18px;
        background:linear-gradient(135deg, rgba(15,109,255,.08), rgba(16,185,129,.08));
        padding:16px;
        margin-top:16px;
    }

    .login-box p {
        margin:8px 0 14px;
        color:var(--text-muted);
        font-weight:700;
        line-height:1.6;
    }

    .branch-login-btn {
        width:100%;
        min-height:50px;
        justify-content:center;
        border-radius:15px;
        font-size:15px;
        box-shadow:0 16px 30px rgba(15,109,255,.2);
    }

    .branch-edit-form {
        margin-top:16px;
        display:none;
    }

    .branch-edit-form.is-open {
        display:block;
    }

    .branch-edit-grid {
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:14px;
    }

    .branch-edit-grid .full {
        grid-column:1 / -1;
    }

    .branch-edit-head {
        display:flex;
        justify-content:space-between;
        gap:12px;
        align-items:flex-start;
        flex-wrap:wrap;
        margin-bottom:14px;
    }

    .branch-detail-head {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        flex-wrap:wrap;
        margin-bottom:12px;
    }

    .branch-edit-toggle {
        min-height:38px;
        border-radius:12px;
        padding:0 13px;
        font-size:13px;
        font-weight:900;
    }

    .branch-save-row {
        display:flex;
        justify-content:flex-end;
        gap:10px;
        margin-top:16px;
        flex-wrap:wrap;
    }

    @media (max-width: 900px) {
        .branch-profile-grid,
        .branch-kpi,
        .branch-edit-grid { grid-template-columns:1fr; }
        .branch-edit-grid .full { grid-column:auto; }
        .detail-row { grid-template-columns:1fr; gap:4px; }
    }
</style>

<div class="branch-top">
    <div>
        <h1>Branch Profile</h1>
        <p class="page-subtitle">Branch account details, admin login and dashboard access.</p>
    </div>
    <a class="branch-back" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/users/index?tab=branch_list">
        <span class="material-symbols-rounded">arrow_back</span>Back to Branches
    </a>
</div>

<div class="branch-profile-grid">
    <aside class="branch-panel">
        <div class="branch-mark"><span class="material-symbols-rounded">storefront</span></div>
        <h2 class="branch-title"><?php echo Security::escape($branch['store_name']); ?></h2>
        <p class="page-subtitle" style="margin-top:6px;"><?php echo Security::escape($branch['branch_code'] ?: 'Branch account'); ?></p>
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:14px;">
            <span class="badge <?php echo ($branch['status'] ?? '') === 'active' ? 'badge-success' : 'badge-danger'; ?>"><?php echo Security::escape(ucfirst((string) ($branch['status'] ?? 'active'))); ?></span>
            <span class="badge badge-soft">Branch Dashboard</span>
        </div>

        <div class="login-box">
            <strong>Branch Panel Access</strong>
            <p>Open this branch panel directly. You can return to the main branch from the header button.</p>
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/users/loginBranch/<?php echo (int) $branch['id']; ?>" method="POST" target="_blank" data-no-ajax>
                <?php echo Security::csrfField(); ?>
                <button class="btn btn-primary branch-login-btn" type="submit">
                    <span class="material-symbols-rounded">login</span>Login to Branch Panel
                </button>
            </form>
        </div>
    </aside>

    <section>
        <div class="branch-kpi">
            <div class="branch-kpi-card"><span>Branch ID</span><strong>#<?php echo (int) $branch['id']; ?></strong></div>
            <div class="branch-kpi-card"><span>Admin</span><strong><?php echo Security::escape($branch['admin_name'] ?: $branch['owner_name']); ?></strong></div>
            <div class="branch-kpi-card"><span>Created</span><strong><?php echo !empty($branch['created_at']) ? date('M d, Y h:i A', strtotime($branch['created_at'])) : '-'; ?></strong></div>
        </div>

        <div class="branch-panel">
            <div class="branch-detail-head">
                <h3>Branch Details</h3>
                <button class="btn btn-secondary branch-edit-toggle" type="button" data-branch-edit-toggle>
                    <span class="material-symbols-rounded">edit</span>Edit
                </button>
            </div>
            <div class="detail-list">
                <div class="detail-row"><span>Branch Name</span><strong><?php echo Security::escape($branch['store_name']); ?></strong></div>
                <div class="detail-row"><span>Branch Code</span><strong><?php echo Security::escape($branch['branch_code'] ?: '-'); ?></strong></div>
                <div class="detail-row"><span>Owner / Admin</span><strong><?php echo Security::escape($branch['admin_name'] ?: $branch['owner_name']); ?></strong></div>
                <div class="detail-row"><span>Admin Email</span><strong><?php echo Security::escape($branch['admin_email'] ?: $branch['email']); ?></strong></div>
                <div class="detail-row"><span>Admin Status</span><strong><?php echo Security::escape(ucfirst((string) ($branch['admin_status'] ?? 'active'))); ?></strong></div>
                <div class="detail-row"><span>Phone</span><strong><?php echo Security::escape($branch['phone'] ?: '-'); ?></strong></div>
                <div class="detail-row"><span>Address</span><strong><?php echo Security::escape($branch['address'] ?: '-'); ?></strong></div>
                <div class="detail-row"><span>Subscription</span><strong><?php echo Security::escape(ucfirst((string) ($branch['plan_type'] ?? 'free'))); ?></strong></div>
            </div>
        </div>

        <div class="branch-panel branch-edit-form" id="branch-edit-form" data-branch-edit-panel>
            <div class="branch-edit-head">
                <div>
                    <h3 style="margin-bottom:6px;">Edit Branch</h3>
                    <p class="page-subtitle">Update branch identity, assigned address and branch admin access.</p>
                </div>
                <span class="badge badge-soft">Parent Managed</span>
            </div>

            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/users/updateBranch/<?php echo (int) $branch['id']; ?>" method="POST" data-no-ajax>
                <?php echo Security::csrfField(); ?>
                <div class="branch-edit-grid">
                    <div class="form-group">
                        <label class="form-label">Branch Name</label>
                        <input class="form-input" type="text" name="branch_name" value="<?php echo Security::escape($branch['store_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch Code</label>
                        <input class="form-input" type="text" name="branch_code" value="<?php echo Security::escape($branch['branch_code'] ?? ''); ?>" placeholder="Auto / optional">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch Admin Name</label>
                        <input class="form-input" type="text" name="owner_name" value="<?php echo Security::escape($branch['admin_name'] ?: $branch['owner_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch Admin Email</label>
                        <input class="form-input" type="email" name="email" value="<?php echo Security::escape($branch['admin_email'] ?: $branch['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input class="form-input" type="text" name="phone" value="<?php echo Security::escape($branch['phone'] ?? ''); ?>" placeholder="+92...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?php echo ($branch['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="suspended" <?php echo ($branch['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Assigned Branch Address</label>
                        <input class="form-input" type="text" name="address" value="<?php echo Security::escape($branch['address'] ?? ''); ?>" placeholder="Branch address">
                        <div class="page-subtitle" style="margin-top:6px;">This address appears in the branch settings and receipts.</div>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">New Admin Password (optional)</label>
                        <input class="form-input" type="password" name="password" minlength="6" placeholder="Leave empty to keep existing password">
                    </div>
                </div>
                <div class="branch-save-row">
                    <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">save</span>Save Branch Details</button>
                </div>
            </form>
        </div>
    </section>
</div>

<?php ob_start(); ?>
<script>
    (() => {
        const toggle = document.querySelector('[data-branch-edit-toggle]');
        const panel = document.querySelector('[data-branch-edit-panel]');
        if (!toggle || !panel) return;

        toggle.addEventListener('click', () => {
            const isOpen = panel.classList.toggle('is-open');
            toggle.innerHTML = isOpen
                ? '<span class="material-symbols-rounded">close</span>Close Edit'
                : '<span class="material-symbols-rounded">edit</span>Edit';
            if (isOpen) {
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    })();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
