<?php ob_start(); ?>
<?php
$storeTab = strtolower(trim((string) ($_GET['tab'] ?? 'create')));
if (!in_array($storeTab, ['create', 'list', 'stats'], true)) {
    $storeTab = 'create';
}
$mainStores = array_values(array_filter($stores ?? [], static fn($store) => (int) ($store['parent_store_id'] ?? 0) <= 0));
$branchStores = array_values(array_filter($stores ?? [], static fn($store) => (int) ($store['parent_store_id'] ?? 0) > 0));
?>

<style>
    .sa-store-list-shell {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .sa-store-section {
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.07);
        overflow: hidden;
    }

    .sa-store-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 18px 20px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        background: linear-gradient(135deg, rgba(239, 246, 255, 0.94), rgba(240, 253, 250, 0.78));
    }

    .sa-store-section-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .sa-store-section-icon {
        display: grid;
        place-items: center;
        width: 44px;
        height: 44px;
        border-radius: 16px;
        background: linear-gradient(135deg, #1d70ff, #14b981);
        color: #fff;
        box-shadow: 0 14px 28px rgba(29, 112, 255, 0.18);
    }

    .sa-store-section-title h3 {
        margin: 0;
        color: #0f172a;
        font-size: 20px;
    }

    .sa-store-section-title p {
        margin: 4px 0 0;
        color: #64748b;
        font-weight: 700;
    }

    .sa-store-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 0 13px;
        border-radius: 999px;
        background: #e0f2fe;
        color: #075985;
        font-weight: 900;
        white-space: nowrap;
    }

    .sa-store-name-cell {
        display: block;
        min-width: 210px;
    }

    .sa-store-name-cell strong,
    .sa-store-name-cell span {
        display: block;
    }

    .sa-store-name-cell span {
        margin-top: 3px;
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
    }

    .sa-directory {
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 26px;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .sa-directory-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 20px;
        background: linear-gradient(135deg, rgba(239, 246, 255, 0.95), rgba(240, 253, 250, 0.82));
        border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    }

    .sa-directory-title {
        display: flex;
        align-items: center;
        gap: 13px;
    }

    .sa-directory-title-icon {
        display: grid;
        place-items: center;
        width: 48px;
        height: 48px;
        border-radius: 17px;
        color: #fff;
        background: linear-gradient(135deg, #1d70ff, #14b981);
        box-shadow: 0 14px 28px rgba(29, 112, 255, 0.18);
    }

    .sa-directory-title h3 {
        margin: 0;
        color: #0f172a;
        font-size: 22px;
        letter-spacing: 0;
    }

    .sa-directory-title p {
        margin: 5px 0 0;
        color: #64748b;
        font-weight: 800;
    }

    .sa-directory-stats {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .sa-directory-stat {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 36px;
        padding: 0 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.78);
        color: #0f172a;
        font-weight: 900;
        border: 1px solid rgba(148, 163, 184, 0.18);
        white-space: nowrap;
    }

    .sa-directory-tools {
        display: grid;
        grid-template-columns: minmax(260px, 1fr) 180px 180px;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        background: #fff;
    }

    .sa-directory-search {
        position: relative;
    }

    .sa-directory-search .material-symbols-rounded {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: 22px;
    }

    .sa-directory-search input {
        width: 100%;
        min-height: 50px;
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 16px;
        padding: 0 16px 0 48px;
        font-weight: 800;
        outline: none;
        background: #f8fafc;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .sa-directory-tools select {
        width: 100%;
        min-height: 50px;
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 16px;
        padding: 0 14px;
        font-weight: 900;
        outline: none;
        background: #f8fafc;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .sa-directory-search input:focus,
    .sa-directory-tools select:focus {
        border-color: rgba(29, 112, 255, 0.5);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(29, 112, 255, 0.09);
    }

    .sa-directory-tools select.is-filter-active,
    .sa-directory-search input.is-filter-active {
        border-color: rgba(20, 185, 129, 0.55);
        background: #f0fdfa;
        box-shadow: 0 0 0 4px rgba(20, 185, 129, 0.08);
    }

    .sa-directory-resultbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 20px;
        color: #64748b;
        font-weight: 900;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
    }

    .sa-directory-filter-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: #047857;
        font-weight: 900;
    }

    .sa-directory-filter-status .material-symbols-rounded {
        font-size: 18px;
    }

    .sa-directory-table-wrap {
        overflow-x: auto;
    }

    .sa-directory-table {
        min-width: 1180px;
    }

    .sa-directory-type {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 7px 10px;
        font-size: 12px;
        font-weight: 900;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .sa-directory-type.branch {
        background: #ecfdf5;
        color: #047857;
    }

    .sa-directory-empty {
        display: none;
        padding: 34px 18px;
        text-align: center;
        color: #64748b;
        font-weight: 900;
    }

    .sa-directory-pagination {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 16px 20px;
        border-top: 1px solid rgba(148, 163, 184, 0.16);
        background: #fff;
    }

    .sa-directory-pagination button {
        min-height: 40px;
        border-radius: 13px;
        border: 1px solid rgba(29, 112, 255, 0.26);
        background: #fff;
        color: #0f4ba8;
        padding: 0 14px;
        font-weight: 900;
        cursor: pointer;
    }

    .sa-directory-pagination button:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .sa-directory-page-info {
        color: #64748b;
        font-weight: 900;
        min-width: 112px;
        text-align: center;
    }

    @media (max-width: 640px) {
        .sa-store-section-head {
            align-items: flex-start;
            flex-direction: column;
            padding: 16px;
        }

        .sa-store-section-title h3 {
            font-size: 18px;
        }

        .sa-store-section-title p {
            font-size: 13px;
        }

        .sa-directory {
            border-radius: 22px;
        }

        .sa-directory-head {
            flex-direction: column;
            padding: 16px;
        }

        .sa-directory-title h3 {
            font-size: 19px;
        }

        .sa-directory-title p {
            font-size: 13px;
        }

        .sa-directory-stats {
            display: flex;
            flex-wrap: nowrap;
            justify-content: flex-start;
            align-items: center;
            width: 100%;
            gap: 7px;
            overflow-x: auto;
            padding-bottom: 2px;
            scrollbar-width: none;
        }

        .sa-directory-stats::-webkit-scrollbar {
            display: none;
        }

        .sa-directory-stat {
            flex: 0 0 auto;
            min-height: 30px;
            padding: 0 9px;
            border-radius: 999px;
            font-size: 11px;
            line-height: 1;
            gap: 5px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        }

        .sa-directory-stat .material-symbols-rounded {
            font-size: 17px;
        }

        .sa-directory-tools {
            grid-template-columns: 1fr 1fr;
            padding: 14px;
        }

        .sa-directory-search {
            grid-column: 1 / -1;
        }

        .sa-directory-search input,
        .sa-directory-tools select {
            min-height: 44px;
            border-radius: 14px;
            font-size: 13px;
        }

        .sa-directory-resultbar,
        .sa-directory-pagination {
            padding: 12px 14px;
        }

        .sa-directory-resultbar {
            align-items: flex-start;
            flex-direction: column;
            font-size: 12px;
        }

        .sa-directory-pagination {
            justify-content: space-between;
        }

        .sa-directory-pagination button {
            min-height: 36px;
            padding: 0 11px;
            font-size: 12px;
        }

        .sa-store-name-cell {
            min-width: 190px;
        }
    }

    @media (max-width: 380px) {
        .sa-directory-tools {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header">
    <div>
        <h1>Store Management</h1>
        <p class="page-subtitle">Create stores with owner login, assign plans, and control access.</p>
    </div>
</div>

<div class="tab-strip" style="margin-bottom:18px;">
    <a class="tab-pill <?php echo $storeTab === 'create' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/stores?tab=create">Create Store</a>
    <a class="tab-pill <?php echo $storeTab === 'list' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/stores?tab=list">Stores List</a>
    <a class="tab-pill <?php echo $storeTab === 'stats' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/stores?tab=stats">Store Statistics</a>
</div>

<?php if ($storeTab === 'stats'): ?>
<div class="grid-cards" style="margin-bottom:18px;">
    <article class="card metric-card"><small>Total Stores</small><strong><?php echo number_format((int) ($stats['total_stores'] ?? 0)); ?></strong></article>
    <article class="card metric-card"><small>Main Accounts</small><strong><?php echo number_format((int) ($stats['main_accounts'] ?? 0)); ?></strong></article>
    <article class="card metric-card"><small>Branch Accounts</small><strong><?php echo number_format((int) ($stats['branch_accounts'] ?? 0)); ?></strong></article>
    <article class="card metric-card"><small>Active Stores</small><strong><?php echo number_format((int) ($stats['active_stores'] ?? 0)); ?></strong></article>
    <article class="card metric-card"><small>Suspended Stores</small><strong><?php echo number_format((int) ($stats['suspended_stores'] ?? 0)); ?></strong></article>
    <article class="card metric-card"><small>Expired Stores</small><strong><?php echo number_format((int) ($stats['expired_stores'] ?? 0)); ?></strong></article>
</div>
<?php elseif ($storeTab === 'list'): ?>
<section class="sa-directory" data-store-directory data-page-size="10">
    <div class="sa-directory-head">
        <div class="sa-directory-title">
            <span class="sa-directory-title-icon"><span class="material-symbols-rounded">storefront</span></span>
            <div>
                <h3>Store Directory</h3>
                <p>Main accounts and branch accounts in one clean, searchable workspace.</p>
            </div>
        </div>
        <div class="sa-directory-stats">
            <span class="sa-directory-stat"><span class="material-symbols-rounded">domain</span><?php echo count($mainStores); ?> Main</span>
            <span class="sa-directory-stat"><span class="material-symbols-rounded">account_tree</span><?php echo count($branchStores); ?> Branches</span>
            <span class="sa-directory-stat"><span class="material-symbols-rounded">inventory_2</span><?php echo count($stores ?? []); ?> Total</span>
        </div>
    </div>

    <div class="sa-directory-tools">
        <label class="sa-directory-search">
            <span class="material-symbols-rounded">search</span>
            <input type="search" placeholder="Search store, owner, email, phone, branch code..." data-store-directory-search>
        </label>
        <select data-store-directory-type>
            <option value="all">All Accounts</option>
            <option value="main">Main Accounts</option>
            <option value="branch">Branch Accounts</option>
        </select>
        <select data-store-directory-status>
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>
            <option value="inactive">Inactive</option>
            <option value="expired">Expired</option>
        </select>
    </div>

    <div class="sa-directory-resultbar">
        <span data-store-directory-count><?php echo count($stores ?? []); ?> records</span>
        <span class="sa-directory-filter-status" data-store-directory-filter-status><span class="material-symbols-rounded">bolt</span>Live filters ready</span>
    </div>

    <div class="sa-directory-table-wrap">
        <table class="sa-directory-table" data-no-pagination>
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Type</th>
                    <th>Parent / Branches</th>
                    <th>Owner / Admin</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stores)): ?>
                    <tr><td colspan="10" class="empty-state">No stores found.</td></tr>
                <?php else: ?>
                    <?php foreach ($stores as $store): ?>
                        <?php
                        $isBranch = (int) ($store['parent_store_id'] ?? 0) > 0;
                        $rowStatus = strtolower((string) ($store['status'] ?? 'inactive'));
                        $searchText = strtolower(trim(implode(' ', [
                            (string) ($store['store_name'] ?? ''),
                            (string) ($store['owner_name'] ?? ''),
                            (string) ($store['email'] ?? ''),
                            (string) ($store['phone'] ?? ''),
                            (string) ($store['branch_code'] ?? ''),
                            (string) ($store['parent_store_name'] ?? ''),
                            (string) ($store['plan_name'] ?? ''),
                            $isBranch ? 'branch account' : 'main account',
                            $rowStatus
                        ])));
                        ?>
                        <tr data-store-row data-type="<?php echo $isBranch ? 'branch' : 'main'; ?>" data-status="<?php echo Security::escape($rowStatus); ?>" data-search="<?php echo Security::escape($searchText); ?>">
                            <td>
                                <div class="sa-store-name-cell">
                                    <strong><?php echo Security::escape($store['store_name']); ?></strong>
                                    <span><?php echo $isBranch ? Security::escape((string) ($store['branch_code'] ?: 'Branch Account')) : 'Main Account'; ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="sa-directory-type <?php echo $isBranch ? 'branch' : ''; ?>">
                                    <span class="material-symbols-rounded" style="font-size:16px;"><?php echo $isBranch ? 'account_tree' : 'domain'; ?></span>
                                    <?php echo $isBranch ? 'Branch' : 'Main'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($isBranch): ?>
                                    <strong><?php echo Security::escape((string) ($store['parent_store_name'] ?? 'Unknown Parent')); ?></strong>
                                    <div class="page-subtitle" style="font-size:12px;"><?php echo Security::escape((string) ($store['parent_email'] ?? '-')); ?></div>
                                <?php else: ?>
                                    <span class="badge badge-soft"><?php echo (int) ($store['branch_count'] ?? 0); ?> Branches</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo Security::escape($store['owner_name']); ?></td>
                            <td><?php echo Security::escape((string) $store['email']); ?></td>
                            <td><?php echo Security::escape((string) ($store['phone'] ?? '-')); ?></td>
                            <td><?php echo Security::escape(ucfirst((string) ($store['plan_name'] ?? 'free'))); ?></td>
                            <td><span class="badge <?php echo $rowStatus === 'active' ? 'badge-success' : ($rowStatus === 'suspended' ? 'badge-warn' : 'badge-danger'); ?>"><?php echo Security::escape(ucfirst($rowStatus)); ?></span></td>
                            <td><?php echo !empty($store['created_at']) ? date('M d, Y', strtotime($store['created_at'])) : '-'; ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="icon-btn" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/storeProfile/<?php echo (int) $store['id']; ?>" title="View Profile">
                                        <span class="material-symbols-rounded">visibility</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="sa-directory-empty" data-store-directory-empty>No matching stores found.</div>
    </div>

    <div class="sa-directory-pagination" data-store-directory-pagination>
        <button type="button" data-store-directory-prev>Previous</button>
        <span class="sa-directory-page-info" data-store-directory-page>Page 1 of 1</span>
        <button type="button" data-store-directory-next>Next</button>
    </div>
</section>
<?php else: ?>
<section class="card create-store-shell" style="margin-bottom:18px;">
    <div class="create-store-head">
        <h3>Create New Store</h3>
        <p class="page-subtitle">Setup store profile, owner access, and subscription from one clean form.</p>
    </div>
    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/createStore" method="POST" data-no-ajax>
        <?php echo Security::csrfField(); ?>

        <div class="create-store-layout">
            <div class="create-store-main">
                <div class="create-store-section">
                    <div class="section-title">
                        <span class="material-symbols-rounded">storefront</span>
                        <h4>Store Information</h4>
                    </div>
                    <div class="split-grid">
                        <div class="form-group"><label class="form-label">Store Name</label><input class="form-input" type="text" name="store_name" placeholder="e.g. Usman Mobile Center" required></div>
                        <div class="form-group"><label class="form-label">Owner Name</label><input class="form-input" type="text" name="owner_name" placeholder="Owner full name" required></div>
                        <div class="form-group"><label class="form-label">Store Email</label><input class="form-input" type="email" name="store_email" placeholder="store@email.com" required></div>
                        <div class="form-group"><label class="form-label">Phone Number</label><input class="form-input" type="text" name="phone" placeholder="+92XXXXXXXXXX"></div>
                        <div class="form-group create-store-wide"><label class="form-label">Store Address</label><input class="form-input" type="text" name="address" placeholder="Street, area, city"></div>
                    </div>
                </div>

                <div class="create-store-section">
                    <div class="section-title">
                        <span class="material-symbols-rounded">admin_panel_settings</span>
                        <h4>Account Access</h4>
                    </div>
                    <div class="split-grid">
                        <div class="form-group">
                            <label class="form-label">Login Email</label>
                            <input class="form-input" type="text" value="Auto set from Store Email" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input class="form-input" type="password" name="password" placeholder="Enter secure password (min 6 chars)" required>
                        </div>
                    </div>
                </div>

                <div class="create-store-section">
                    <div class="section-title">
                        <span class="material-symbols-rounded">workspace_premium</span>
                        <h4>Subscription & Status</h4>
                    </div>
                    <div class="split-grid">
                        <div class="form-group">
                            <label class="form-label">Select Subscription Plan</label>
                            <select class="form-select" name="plan_id">
                                <?php foreach ($plans as $plan): ?>
                                    <option value="<?php echo (int) $plan['id']; ?>"><?php echo Security::escape($plan['plan_name']); ?> (<?php echo App::money($plan['monthly_price']); ?>/mo)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label class="form-label">Subscription Start Date</label><input class="form-input" type="date" name="subscription_start_date" value="<?php echo date('Y-m-d'); ?>"></div>
                        <div class="form-group"><label class="form-label">Expiry Date</label><input class="form-input" type="date" name="expiry_date"></div>
                        <div class="form-group"><label class="form-label">Payment Status</label><select class="form-select" name="payment_status"><option value="paid">Paid</option><option value="pending">Pending</option></select></div>
                        <div class="form-group"><label class="form-label">Store Status</label><select class="form-select" name="status"><option value="active">Active</option><option value="suspended">Suspended</option><option value="inactive">Inactive</option></select></div>
                    </div>
                </div>
            </div>

            <aside class="create-store-aside">
                <h5>Before Creating Store</h5>
                <p>Store Email will be used as login email automatically.</p>
                <p>Choose plan and dates carefully to avoid instant expiry.</p>
                <p>Set strong password so owner can login safely.</p>
                <button class="btn btn-primary create-store-submit" type="submit">Create Store + Credentials</button>
            </aside>
        </div>
    </form>
</section>
<?php endif; ?>
<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
