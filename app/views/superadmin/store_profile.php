<?php ob_start(); ?>
<?php
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
$profileTab = strtolower(trim((string) ($_GET['tab'] ?? 'overview')));
if (!in_array($profileTab, ['overview', 'credentials', 'billing'], true)) {
    $profileTab = 'overview';
}

$storeId = (int) ($store['id'] ?? 0);
$storeName = (string) ($store['store_name'] ?? 'Store');
$ownerName = (string) ($store['owner_name'] ?? 'Owner');
$loginEmail = (string) ($store['email'] ?? '-');
$planName = (string) ($store['plan_name'] ?? 'Free');
$status = strtolower((string) ($store['status'] ?? 'inactive'));
$paymentStatus = strtolower((string) ($store['payment_status'] ?? 'pending'));
$createdAt = (string) ($store['created_at'] ?? '');
$expiresAt = (string) ($store['subscription_expires_at'] ?? '');
$startedAt = (string) ($store['subscription_started_at'] ?? '');
$parentAccountId = (int) ($store['parent_account_id'] ?? $store['parent_store_id'] ?? 0);
$isBranchAccount = $parentAccountId > 0;
$branchCount = (int) ($store['branch_count'] ?? 0);

$formatDateTime = static function (?string $value): string {
    if (empty($value)) {
        return '-';
    }
    $time = strtotime($value);
    return $time ? date('M d, Y h:i A', $time) : '-';
};
$formatDate = static function (?string $value): string {
    if (empty($value)) {
        return '-';
    }
    $time = strtotime($value);
    return $time ? date('M d, Y', $time) : '-';
};
$statusClass = in_array($status, ['active', 'paid'], true) ? 'is-good' : (in_array($status, ['suspended', 'expired'], true) ? 'is-danger' : 'is-warn');
$paymentClass = $paymentStatus === 'paid' ? 'is-good' : 'is-warn';
?>

<style>
    .store-profile-shell {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .store-profile-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(30, 99, 255, 0.14);
        border-radius: 28px;
        background:
            radial-gradient(circle at top right, rgba(24, 184, 139, 0.16), transparent 32%),
            linear-gradient(135deg, #0f172a 0%, #153d82 53%, #0e9f6e 100%);
        color: #fff;
        padding: 24px;
        box-shadow: 0 22px 48px rgba(15, 23, 42, 0.18);
    }

    .store-profile-hero::after {
        content: "";
        position: absolute;
        inset: auto -80px -120px auto;
        width: 280px;
        height: 280px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.13);
        pointer-events: none;
    }

    .store-profile-hero-top {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
    }

    .store-profile-title {
        display: flex;
        align-items: center;
        gap: 15px;
        min-width: 0;
    }

    .store-profile-logo {
        display: grid;
        place-items: center;
        width: 58px;
        height: 58px;
        flex: 0 0 auto;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.24);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
    }

    .store-profile-logo .material-symbols-rounded {
        font-size: 30px;
    }

    .store-profile-title h1 {
        margin: 0;
        color: #fff;
        font-size: clamp(28px, 4vw, 42px);
        line-height: 1.05;
        letter-spacing: 0;
    }

    .store-profile-title p {
        margin: 8px 0 0;
        color: rgba(255, 255, 255, 0.78);
        font-weight: 800;
    }

    .store-profile-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }

    .store-profile-action,
    .store-profile-actions .store-profile-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 46px;
        padding: 0 16px;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.25);
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-weight: 900;
        text-decoration: none;
        backdrop-filter: blur(10px);
        cursor: pointer;
    }

    .store-profile-action .material-symbols-rounded {
        font-size: 21px;
    }

    .store-profile-action.is-primary {
        background: #fff;
        color: #0f4ba8;
        border-color: #fff;
    }

    .store-profile-action.is-danger {
        color: #fff;
        border-color: rgba(255, 255, 255, 0.24);
        background: rgba(239, 68, 68, 0.22);
    }

    .store-profile-status-toggle {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        min-height: 54px;
        padding: 7px 9px 7px 15px;
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, 0.25);
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-weight: 900;
        backdrop-filter: blur(10px);
    }

    .store-profile-status-toggle.is-loading {
        opacity: 0.7;
        pointer-events: none;
    }

    .store-profile-status-toggle input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .store-profile-status-text {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    .store-profile-status-text .material-symbols-rounded {
        font-size: 21px;
    }

    .store-profile-switch-track {
        position: relative;
        width: 62px;
        height: 36px;
        border-radius: 999px;
        background: rgba(248, 113, 113, 0.9);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.18);
        transition: background 0.2s ease, box-shadow 0.2s ease;
    }

    .store-profile-switch-track::after {
        content: "";
        position: absolute;
        top: 5px;
        left: 5px;
        width: 26px;
        height: 26px;
        border-radius: 999px;
        background: #fff;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.2);
        transition: transform 0.2s ease;
    }

    .store-profile-status-toggle input:checked + .store-profile-switch-track {
        background: linear-gradient(135deg, #16a34a, #22c55e);
        box-shadow: 0 10px 22px rgba(34, 197, 94, 0.2);
    }

    .store-profile-status-toggle input:checked + .store-profile-switch-track::after {
        transform: translateX(26px);
    }

    .store-profile-badges {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 22px;
    }

    .store-profile-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 9px 13px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: rgba(255, 255, 255, 0.88);
        font-weight: 900;
    }

    .store-profile-badge.is-good {
        background: rgba(22, 163, 74, 0.2);
        color: #dcfce7;
    }

    .store-profile-badge.is-warn {
        background: rgba(245, 158, 11, 0.2);
        color: #fef3c7;
    }

    .store-profile-badge.is-danger {
        background: rgba(239, 68, 68, 0.2);
        color: #fee2e2;
    }

    .store-profile-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .store-profile-kpi,
    .store-profile-panel {
        border: 1px solid rgba(148, 163, 184, 0.24);
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
    }

    .store-profile-kpi {
        border-radius: 22px;
        padding: 18px;
    }

    .store-profile-kpi small {
        display: block;
        color: #64748b;
        font-weight: 900;
        margin-bottom: 9px;
    }

    .store-profile-kpi strong {
        display: block;
        color: #0f172a;
        font-size: 22px;
        line-height: 1.15;
        overflow-wrap: anywhere;
    }

    .store-profile-kpi span {
        display: block;
        margin-top: 8px;
        color: #64748b;
        font-weight: 800;
        font-size: 13px;
    }

    .store-profile-relation {
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
        gap: 14px;
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 24px;
        padding: 18px;
        background: linear-gradient(135deg, rgba(239, 246, 255, 0.92), rgba(240, 253, 250, 0.82));
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.07);
    }

    .store-profile-relation-card {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
        border-radius: 20px;
        padding: 16px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(148, 163, 184, 0.2);
    }

    .store-profile-relation-icon {
        display: grid;
        place-items: center;
        width: 48px;
        height: 48px;
        flex: 0 0 auto;
        border-radius: 17px;
        background: linear-gradient(135deg, #1d70ff, #14b981);
        color: #fff;
    }

    .store-profile-relation-card small {
        display: block;
        color: #64748b;
        font-weight: 900;
        margin-bottom: 5px;
    }

    .store-profile-relation-card strong {
        display: block;
        color: #0f172a;
        font-size: 18px;
        overflow-wrap: anywhere;
    }

    .store-profile-relation-card span {
        display: block;
        margin-top: 4px;
        color: #64748b;
        font-weight: 800;
        font-size: 13px;
    }

    .store-profile-relation-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 9px;
        color: #0f6dff;
        font-weight: 900;
        text-decoration: none;
    }

    .store-profile-relation-link .material-symbols-rounded {
        display: inline-flex;
        margin: 0;
        color: inherit;
        font-size: 18px;
    }

    .store-profile-tabs {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.86);
        width: fit-content;
        max-width: 100%;
        overflow-x: auto;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
    }

    .store-profile-tab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        padding: 0 16px;
        border-radius: 16px;
        color: #475569;
        font-weight: 900;
        text-decoration: none;
        white-space: nowrap;
    }

    .store-profile-tab.active {
        background: linear-gradient(135deg, #1d70ff, #14b981);
        color: #fff;
        box-shadow: 0 14px 28px rgba(29, 112, 255, 0.2);
    }

    .store-profile-panel {
        border-radius: 26px;
        padding: 22px;
    }

    .store-profile-panel-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 18px;
    }

    .store-profile-panel-head h2 {
        margin: 0;
        color: #0f172a;
        font-size: 24px;
        letter-spacing: 0;
    }

    .store-profile-panel-head p {
        margin: 6px 0 0;
        color: #64748b;
        font-weight: 700;
    }

    .store-profile-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .store-profile-form-grid .form-group {
        margin: 0;
    }

    .store-profile-form-grid .is-wide {
        grid-column: 1 / -1;
    }

    .store-profile-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .store-profile-summary {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 20px;
        padding: 16px;
        background: linear-gradient(135deg, rgba(239, 246, 255, 0.95), rgba(255, 255, 255, 0.95));
    }

    .store-profile-summary small {
        display: block;
        color: #64748b;
        font-weight: 900;
        margin-bottom: 7px;
    }

    .store-profile-summary strong {
        color: #0f172a;
        overflow-wrap: anywhere;
    }

    .store-profile-save-row {
        display: flex;
        justify-content: flex-end;
        margin-top: 18px;
    }

    .store-profile-table {
        overflow-x: auto;
        border-radius: 20px;
        border: 1px solid rgba(148, 163, 184, 0.22);
    }

    .store-profile-table table {
        margin: 0;
    }

    .store-profile-table th {
        background: #0f172a;
        color: #fff;
        white-space: nowrap;
    }

    .store-profile-table td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .store-profile-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 7px 11px;
        font-size: 12px;
        font-weight: 900;
        background: #e2e8f0;
        color: #334155;
    }

    .store-profile-pill.is-good {
        background: #dcfce7;
        color: #166534;
    }

    .store-profile-pill.is-warn {
        background: #fef3c7;
        color: #92400e;
    }

    .store-profile-pill.is-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .store-profile-empty {
        padding: 34px 18px;
        text-align: center;
        color: #64748b;
        font-weight: 900;
    }

    @media (max-width: 1024px) {
        .store-profile-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .store-profile-hero-top {
            flex-direction: column;
        }

        .store-profile-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 640px) {
        .store-profile-shell {
            gap: 14px;
        }

        .store-profile-hero {
            padding: 18px;
            border-radius: 22px;
        }

        .store-profile-title {
            align-items: flex-start;
        }

        .store-profile-logo {
            width: 48px;
            height: 48px;
            border-radius: 16px;
        }

        .store-profile-title h1 {
            font-size: 27px;
        }

        .store-profile-title p {
            font-size: 13px;
        }

        .store-profile-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
        }

        .store-profile-action {
            min-height: 42px;
            padding: 0 12px;
            font-size: 13px;
            border-radius: 14px;
        }

        .store-profile-actions form,
        .store-profile-actions form button,
        .store-profile-status-toggle {
            width: 100%;
        }

        .store-profile-status-toggle {
            justify-content: space-between;
            min-height: 42px;
            border-radius: 14px;
            padding: 5px 7px 5px 12px;
            font-size: 13px;
        }

        .store-profile-switch-track {
            width: 50px;
            height: 30px;
        }

        .store-profile-switch-track::after {
            width: 22px;
            height: 22px;
            top: 4px;
            left: 4px;
        }

        .store-profile-status-toggle input:checked + .store-profile-switch-track::after {
            transform: translateX(20px);
        }

        .store-profile-kpis,
        .store-profile-relation,
        .store-profile-form-grid,
        .store-profile-summary-grid {
            grid-template-columns: 1fr;
        }

        .store-profile-kpi,
        .store-profile-panel {
            border-radius: 20px;
            padding: 16px;
        }

        .store-profile-tabs {
            width: 100%;
        }

        .store-profile-tab {
            flex: 1 0 auto;
            min-height: 40px;
            padding: 0 12px;
            font-size: 13px;
        }

        .store-profile-panel-head {
            flex-direction: column;
        }

        .store-profile-save-row .btn {
            width: 100%;
        }
    }
</style>

<div class="store-profile-shell">
    <section class="store-profile-hero">
        <div class="store-profile-hero-top">
            <div class="store-profile-title">
                <div class="store-profile-logo">
                    <span class="material-symbols-rounded">storefront</span>
                </div>
                <div>
                    <h1><?php echo Security::escape($storeName); ?></h1>
                    <p><?php echo Security::escape($ownerName); ?> · <?php echo Security::escape($loginEmail); ?></p>
                </div>
            </div>

            <div class="store-profile-actions">
                <a class="store-profile-action" href="<?php echo $baseUrl; ?>/superadmin/stores?tab=list">
                    <span class="material-symbols-rounded">arrow_back</span>
                    Stores
                </a>
                <form action="<?php echo $baseUrl; ?>/superadmin/loginAsStore/<?php echo $storeId; ?>" method="POST" target="_blank" data-no-ajax>
                    <?php echo Security::csrfField(); ?>
                    <button class="store-profile-action is-primary" type="submit">
                        <span class="material-symbols-rounded">login</span>
                        Login as Store
                    </button>
                </form>
                <form class="store-profile-status-form" action="<?php echo $baseUrl; ?>/superadmin/changeStoreStatus/<?php echo $storeId; ?>" method="POST" data-no-ajax data-store-status-form>
                    <?php echo Security::csrfField(); ?>
                    <input type="hidden" name="return_to_profile" value="1">
                    <input type="hidden" name="status" value="<?php echo $status === 'active' ? 'active' : 'suspended'; ?>" data-store-status-input>
                    <label class="store-profile-status-toggle" data-store-status-toggle>
                        <span class="store-profile-status-text">
                            <span class="material-symbols-rounded" data-store-status-icon><?php echo $status === 'active' ? 'toggle_on' : 'toggle_off'; ?></span>
                            <span data-store-status-text><?php echo $status === 'active' ? 'Active' : 'Suspended'; ?></span>
                        </span>
                        <input type="checkbox" <?php echo $status === 'active' ? 'checked' : ''; ?> data-last-status="<?php echo $status === 'active' ? 'active' : 'suspended'; ?>" data-store-status-checkbox>
                        <span class="store-profile-switch-track" aria-hidden="true"></span>
                    </label>
                </form>
                <form action="<?php echo $baseUrl; ?>/superadmin/deleteStore/<?php echo $storeId; ?>" method="POST" data-no-ajax onsubmit="return confirm('Delete store and all related data?');">
                    <?php echo Security::csrfField(); ?>
                    <button class="store-profile-action is-danger" type="submit">
                        <span class="material-symbols-rounded">delete</span>
                        Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="store-profile-badges">
            <span class="store-profile-badge <?php echo $statusClass; ?>" data-store-status-badge><span class="material-symbols-rounded">verified</span><span data-store-status-label><?php echo Security::escape(ucfirst($status)); ?></span></span>
            <span class="store-profile-badge"><span class="material-symbols-rounded"><?php echo $isBranchAccount ? 'account_tree' : 'domain'; ?></span><?php echo $isBranchAccount ? 'Branch Account' : 'Main Account'; ?></span>
            <span class="store-profile-badge"><span class="material-symbols-rounded">workspace_premium</span><?php echo Security::escape(ucfirst($planName)); ?> Plan</span>
            <span class="store-profile-badge <?php echo $paymentClass; ?>"><span class="material-symbols-rounded">payments</span><?php echo Security::escape(ucfirst($paymentStatus)); ?></span>
            <span class="store-profile-badge"><span class="material-symbols-rounded">event</span>Expires <?php echo Security::escape($formatDate($expiresAt)); ?></span>
        </div>
    </section>

    <section class="store-profile-kpis">
        <article class="store-profile-kpi">
            <small>Store ID</small>
            <strong>#<?php echo $storeId; ?></strong>
            <span>Platform account reference</span>
        </article>
        <article class="store-profile-kpi">
            <small>Created</small>
            <strong><?php echo Security::escape($formatDate($createdAt)); ?></strong>
            <span><?php echo Security::escape($formatDateTime($createdAt)); ?></span>
        </article>
        <article class="store-profile-kpi">
            <small>Subscription Start</small>
            <strong><?php echo Security::escape($formatDate($startedAt)); ?></strong>
            <span>Active billing cycle start</span>
        </article>
        <article class="store-profile-kpi">
            <small>Plan Expiry</small>
            <strong><?php echo Security::escape($formatDate($expiresAt)); ?></strong>
            <span><?php echo Security::escape($formatDateTime($expiresAt)); ?></span>
        </article>
    </section>

    <section class="store-profile-relation">
        <article class="store-profile-relation-card">
            <span class="store-profile-relation-icon"><span class="material-symbols-rounded"><?php echo $isBranchAccount ? 'account_tree' : 'domain'; ?></span></span>
            <div>
                <small>Account Type</small>
                <strong><?php echo $isBranchAccount ? 'Branch Account' : 'Main Parent Account'; ?></strong>
                <span><?php echo $isBranchAccount ? 'This panel keeps branch data separate from parent records.' : 'This account owns billing, plan access and connected branches.'; ?></span>
            </div>
        </article>
        <article class="store-profile-relation-card">
            <span class="store-profile-relation-icon"><span class="material-symbols-rounded"><?php echo $isBranchAccount ? 'hub' : 'account_tree'; ?></span></span>
            <div>
                <?php if ($isBranchAccount): ?>
                    <small>Parent Main Account</small>
                    <strong><?php echo Security::escape((string) ($store['parent_store_name'] ?? 'Unknown Parent')); ?></strong>
                    <span><?php echo Security::escape((string) ($store['parent_email'] ?? '-')); ?></span>
                    <?php if ($parentAccountId > 0): ?>
                        <a class="store-profile-relation-link" href="<?php echo $baseUrl; ?>/superadmin/storeProfile/<?php echo $parentAccountId; ?>">
                            <span class="material-symbols-rounded">open_in_new</span>
                            View Parent Profile
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <small>Connected Branches</small>
                    <strong><?php echo number_format($branchCount); ?> Branch<?php echo $branchCount === 1 ? '' : 'es'; ?></strong>
                    <span>Branches created under this main account use this parent plan context.</span>
                <?php endif; ?>
            </div>
        </article>
    </section>

    <nav class="store-profile-tabs" aria-label="Store profile tabs">
        <a class="store-profile-tab <?php echo $profileTab === 'overview' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/superadmin/storeProfile/<?php echo $storeId; ?>?tab=overview">
            <span class="material-symbols-rounded">dashboard_customize</span>
            Overview
        </a>
        <a class="store-profile-tab <?php echo $profileTab === 'credentials' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/superadmin/storeProfile/<?php echo $storeId; ?>?tab=credentials">
            <span class="material-symbols-rounded">admin_panel_settings</span>
            Credentials
        </a>
        <a class="store-profile-tab <?php echo $profileTab === 'billing' ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/superadmin/storeProfile/<?php echo $storeId; ?>?tab=billing">
            <span class="material-symbols-rounded">receipt_long</span>
            Billing
        </a>
    </nav>

    <?php if ($profileTab === 'credentials'): ?>
        <section class="store-profile-panel">
            <div class="store-profile-panel-head">
                <div>
                    <h2>Login Credentials</h2>
                    <p>Control store owner access without leaving this profile.</p>
                </div>
                <span class="store-profile-pill <?php echo $statusClass; ?>" data-store-status-pill><?php echo Security::escape(ucfirst($status)); ?></span>
            </div>

            <div class="store-profile-summary-grid">
                <div class="store-profile-summary">
                    <small>Login Email</small>
                    <strong><?php echo Security::escape($loginEmail); ?></strong>
                </div>
                <div class="store-profile-summary">
                    <small>Username</small>
                    <strong><?php echo Security::escape((string) ($store['owner_login_username'] ?? '-')); ?></strong>
                </div>
            </div>

            <form action="<?php echo $baseUrl; ?>/superadmin/updateStoreCredentials/<?php echo $storeId; ?>" method="POST" data-no-ajax>
                <?php echo Security::csrfField(); ?>
                <input type="hidden" name="return_to_profile" value="1">
                <div class="store-profile-form-grid">
                    <div class="form-group">
                        <label class="form-label">Login Email</label>
                        <input class="form-input" type="text" value="<?php echo Security::escape($loginEmail); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Set New Password</label>
                        <input class="form-input" type="password" name="new_password" placeholder="Enter new password" required>
                    </div>
                </div>
                <div class="store-profile-save-row">
                    <button class="btn btn-primary" type="submit">
                        <span class="material-symbols-rounded">lock_reset</span>
                        Update Password
                    </button>
                </div>
            </form>
        </section>
    <?php elseif ($profileTab === 'billing'): ?>
        <section class="store-profile-panel">
            <div class="store-profile-panel-head">
                <div>
                    <h2>Billing Timeline</h2>
                    <p>Latest payments, renewals and expiry details for this store.</p>
                </div>
                <span class="store-profile-pill <?php echo $paymentClass; ?>"><?php echo Security::escape(ucfirst($paymentStatus)); ?></span>
            </div>

            <div class="store-profile-table">
                <table>
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Paid At</th>
                            <th>Next Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentPayments)): ?>
                            <tr>
                                <td colspan="5" class="store-profile-empty">No payment history available for this store.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentPayments as $payment): ?>
                                <?php
                                $rowStatus = strtolower((string) ($payment['status'] ?? 'pending'));
                                $rowClass = $rowStatus === 'paid' ? 'is-good' : ($rowStatus === 'failed' ? 'is-danger' : 'is-warn');
                                ?>
                                <tr>
                                    <td><strong><?php echo App::money((float) ($payment['amount'] ?? 0)); ?></strong></td>
                                    <td><?php echo Security::escape((string) ($payment['method_name'] ?? '-')); ?></td>
                                    <td><span class="store-profile-pill <?php echo $rowClass; ?>"><?php echo Security::escape(ucfirst($rowStatus)); ?></span></td>
                                    <td><?php echo Security::escape($formatDateTime((string) ($payment['paid_at'] ?? ''))); ?></td>
                                    <td><?php echo Security::escape($formatDate((string) ($payment['next_expiry_date'] ?? ''))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php else: ?>
        <section class="store-profile-panel">
            <div class="store-profile-panel-head">
                <div>
                    <h2>Store Information</h2>
                    <p>Update business details, plan assignment and account state from one clean workspace.</p>
                </div>
                <span class="store-profile-pill <?php echo $statusClass; ?>" data-store-status-pill><?php echo Security::escape(ucfirst($status)); ?></span>
            </div>

            <form action="<?php echo $baseUrl; ?>/superadmin/updateStore/<?php echo $storeId; ?>" method="POST" data-no-ajax>
                <?php echo Security::csrfField(); ?>
                <input type="hidden" name="return_to_profile" value="1">
                <div class="store-profile-form-grid">
                    <div class="form-group">
                        <label class="form-label">Store Name</label>
                        <input class="form-input" type="text" name="store_name" value="<?php echo Security::escape($storeName); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Owner Name</label>
                        <input class="form-input" type="text" name="owner_name" value="<?php echo Security::escape($ownerName); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Store Email and Login Email</label>
                        <input class="form-input" type="email" name="store_email" value="<?php echo Security::escape($loginEmail); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input class="form-input" type="text" name="phone" value="<?php echo Security::escape((string) ($store['phone'] ?? '')); ?>">
                    </div>
                    <div class="form-group is-wide">
                        <label class="form-label">Address</label>
                        <input class="form-input" type="text" name="address" value="<?php echo Security::escape((string) ($store['address'] ?? '')); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Plan</label>
                        <select class="form-select" name="plan_id">
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?php echo (int) $plan['id']; ?>" <?php echo (int) ($store['current_plan_id'] ?? 0) === (int) $plan['id'] ? 'selected' : ''; ?>>
                                    <?php echo Security::escape((string) $plan['plan_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Status</label>
                        <select class="form-select" name="payment_status">
                            <option value="paid" <?php echo $paymentStatus === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="pending" <?php echo $paymentStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subscription Start Date</label>
                        <input class="form-input" type="date" name="subscription_start_date" value="<?php echo !empty($startedAt) ? date('Y-m-d', strtotime($startedAt)) : date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expiry Date</label>
                        <input class="form-input" type="date" name="expiry_date" value="<?php echo !empty($expiresAt) ? date('Y-m-d', strtotime($expiresAt)) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                        </select>
                    </div>
                </div>
                <div class="store-profile-save-row">
                    <button class="btn btn-primary" type="submit">
                        <span class="material-symbols-rounded">save</span>
                        Save Store Info
                    </button>
                </div>
            </form>
        </section>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
