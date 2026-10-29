<?php ob_start(); ?>

<style>
    .exp-tab-nav {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .exp-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 12px;
        border: 1px solid var(--border, #e2e8f0);
        background: #fff;
        color: #334155;
        font-weight: 800;
        font-size: 13.5px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .exp-tab-btn:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }
    .exp-tab-btn.active {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: #fff;
        border-color: #0f172a;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.18);
    }
    .exp-tab-pane {
        display: none;
    }
    .exp-tab-pane.active {
        display: block;
    }
</style>

<div class="page-header">
    <div>
        <h1>Batch Numbers & Expiry Date Alerts</h1>
        <p class="page-subtitle">Batch expiration monitoring and early FIFO alerts for perishable pharmacy, grocery, and bakery inventory.</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/index" class="btn btn-secondary">
            <span class="material-symbols-rounded">arrow_back</span>Back to Inventory
        </a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/index?tab=expiry" class="btn btn-outline">
            <span class="material-symbols-rounded">settings</span>Expiry Settings
        </a>
    </div>
</div>

<?php if (!$isEnabled): ?>
    <div class="alert alert-warning" style="margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span class="material-symbols-rounded" style="font-size: 24px; color: var(--warning);">info</span>
            <div>
                <strong>Expiry Tracking is currently Optional / Inactive in Settings.</strong>
                <div style="font-size: 12px; margin-top: 2px;">You can still record batch numbers on purchases or enable store-wide tracking from Settings.</div>
            </div>
        </div>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/index?tab=expiry" class="btn btn-sm btn-primary">
            Turn ON in Settings
        </a>
    </div>
<?php endif; ?>

<!-- KPI Metrics Grid -->
<div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom: 18px;">
    <div class="kpi-card">
        <div class="kpi-icon" style="background: rgba(220, 38, 38, 0.1); color: var(--error);">
            <span class="material-symbols-rounded">dangerous</span>
        </div>
        <div>
            <div class="kpi-label">Expired Stock</div>
            <div class="kpi-value" style="color: var(--error);"><?= $expiredCount ?> Batches</div>
            <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; margin-top: 2px;">Worth <?= App::money($expiredValue) ?> at risk</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
            <span class="material-symbols-rounded">warning</span>
        </div>
        <div>
            <div class="kpi-label">Expiring in 30 Days</div>
            <div class="kpi-value" style="color: var(--warning);"><?= $expiring30Count ?> Batches</div>
            <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; margin-top: 2px;">Worth <?= App::money($expiring30Value) ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background: rgba(15, 109, 255, 0.1); color: var(--primary);">
            <span class="material-symbols-rounded">schedule</span>
        </div>
        <div>
            <div class="kpi-label">Expiring in 90 Days</div>
            <div class="kpi-value"><?= count($expiring90) ?> Batches</div>
            <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; margin-top: 2px;">Upcoming attention required</div>
        </div>
    </div>
</div>

    <!-- Tab Navigation -->
    <div class="exp-tab-nav">
        <button class="exp-tab-btn active" onclick="switchExpTab('expired', this)">
            🔴 Expired (<?= $expiredCount ?>)
        </button>
        <button class="exp-tab-btn" onclick="switchExpTab('urgent30', this)">
            🟡 Expiring in ≤ 30 Days (<?= $expiring30Count ?>)
        </button>
        <button class="exp-tab-btn" onclick="switchExpTab('future90', this)">
            🔵 Expiring in 31-90 Days (<?= count($expiring90) ?>)
        </button>
    </div>

    <!-- 1. Expired Items Pane -->
    <div class="exp-tab-pane active" id="pane-expired">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 18px;">
            <div class="table-responsive">
                <table class="table" style="margin: 0; width: 100%;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding: 14px 20px;">Product Name</th>
                            <th style="padding: 14px 16px;">Batch Number</th>
                            <th style="padding: 14px 16px; text-align: center;">Remaining Stock</th>
                            <th style="padding: 14px 16px;">Expiry Date</th>
                            <th style="padding: 14px 16px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expired)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">
                                    <span class="material-symbols-rounded" style="font-size: 44px; color: #10b981; display: block; margin-bottom: 6px;">check_circle</span>
                                    <div style="font-weight: 700; color: #334155;">Great! No expired products in inventory.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expired as $b): ?>
                                <tr>
                                    <td style="padding: 14px 20px;">
                                        <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($b['product_name']) ?></div>
                                        <div style="font-size: 11px; color: #64748b;">Code: <?= htmlspecialchars($b['product_barcode'] ?: 'N/A') ?></div>
                                    </td>
                                    <td style="padding: 14px 16px; font-weight: 700; font-family: monospace;">
                                        <?= htmlspecialchars($b['batch_number']) ?>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: center; font-weight: 800; color: #dc2626;">
                                        <?= (float)$b['current_stock'] ?> <?= htmlspecialchars($b['unit_type']) ?>
                                    </td>
                                    <td style="padding: 14px 16px; font-weight: 700; color: #dc2626;">
                                        <?= date('M d, Y', strtotime($b['expiry_date'])) ?>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: center;">
                                        <span style="background: rgba(239,68,68,0.12); color: #dc2626; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 11px;">
                                            Expired <?= (int)$b['days_expired'] ?> days ago
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 2. Expiring in 30 Days Pane -->
    <div class="exp-tab-pane" id="pane-urgent30">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 18px;">
            <div class="table-responsive">
                <table class="table" style="margin: 0; width: 100%;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding: 14px 20px;">Product Name</th>
                            <th style="padding: 14px 16px;">Batch Number</th>
                            <th style="padding: 14px 16px; text-align: center;">Current Stock</th>
                            <th style="padding: 14px 16px;">Expiry Date</th>
                            <th style="padding: 14px 16px; text-align: center;">Time Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expiring30)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">
                                    <div style="font-weight: 700; color: #334155;">No items expiring in the next 30 days.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expiring30 as $b): ?>
                                <tr>
                                    <td style="padding: 14px 20px;">
                                        <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($b['product_name']) ?></div>
                                        <div style="font-size: 11px; color: #64748b;">Code: <?= htmlspecialchars($b['product_barcode'] ?: 'N/A') ?></div>
                                    </td>
                                    <td style="padding: 14px 16px; font-weight: 700; font-family: monospace;">
                                        <?= htmlspecialchars($b['batch_number']) ?>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: center; font-weight: 800; color: #d97706;">
                                        <?= (float)$b['current_stock'] ?> <?= htmlspecialchars($b['unit_type']) ?>
                                    </td>
                                    <td style="padding: 14px 16px; font-weight: 700; color: #d97706;">
                                        <?= date('M d, Y', strtotime($b['expiry_date'])) ?>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: center;">
                                        <span style="background: rgba(245,158,11,0.12); color: #d97706; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 11px;">
                                            <?= (int)$b['days_left'] ?> days left
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. Expiring in 90 Days Pane -->
    <div class="exp-tab-pane" id="pane-future90">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 18px;">
            <div class="table-responsive">
                <table class="table" style="margin: 0; width: 100%;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding: 14px 20px;">Product Name</th>
                            <th style="padding: 14px 16px;">Batch Number</th>
                            <th style="padding: 14px 16px; text-align: center;">Current Stock</th>
                            <th style="padding: 14px 16px;">Expiry Date</th>
                            <th style="padding: 14px 16px; text-align: center;">Time Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expiring90)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">
                                    <div style="font-weight: 700; color: #334155;">No items expiring in 31 to 90 days.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expiring90 as $b): ?>
                                <tr>
                                    <td style="padding: 14px 20px;">
                                        <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($b['product_name']) ?></div>
                                        <div style="font-size: 11px; color: #64748b;">Code: <?= htmlspecialchars($b['product_barcode'] ?: 'N/A') ?></div>
                                    </td>
                                    <td style="padding: 14px 16px; font-weight: 700; font-family: monospace;">
                                        <?= htmlspecialchars($b['batch_number']) ?>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: center; font-weight: 800; color: #2563eb;">
                                        <?= (float)$b['current_stock'] ?> <?= htmlspecialchars($b['unit_type']) ?>
                                    </td>
                                    <td style="padding: 14px 16px; font-weight: 700;">
                                        <?= date('M d, Y', strtotime($b['expiry_date'])) ?>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: center;">
                                        <span style="background: rgba(59,130,246,0.12); color: #2563eb; padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: 11px;">
                                            <?= (int)$b['days_left'] ?> days left
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    function switchExpTab(tabName, btn) {
        document.querySelectorAll('.exp-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.exp-tab-pane').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        if (tabName === 'expired') document.getElementById('pane-expired').classList.add('active');
        else if (tabName === 'urgent30') document.getElementById('pane-urgent30').classList.add('active');
        else if (tabName === 'future90') document.getElementById('pane-future90').classList.add('active');
    }
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
