<?php ob_start(); ?>
<style>
.report-wrap {
    width: 100%;
    padding-bottom: 12px;
}

.premium-metric-card {
    background: #ffffff;
    border-radius: 22px;
    padding: 22px 20px;
    border: 1px solid rgba(0,0,0,0.04);
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    display: flex;
    align-items: center;
    gap: 18px;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
    min-width: 0;
}
.premium-metric-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 4px;
    background: transparent;
    transition: background 0.3s ease;
}
.premium-metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border-color: rgba(0,0,0,0.08);
}
.premium-metric-card:hover::before {
    background: var(--primary);
}
.premium-metric-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    flex-shrink: 0;
}
.premium-metric-content {
    flex: 1;
    min-width: 0;
}
.premium-metric-content small {
    display: block;
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.premium-metric-content strong {
    display: block;
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    letter-spacing: -0.5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.icon-blue { background: #eff6ff; color: #3b82f6; }
.icon-emerald { background: #ecfdf5; color: #10b981; }
.icon-rose { background: #fff1f2; color: #f43f5e; border: 1px solid #ffe4e6; }
.icon-amber { background: #fffbeb; color: #f59e0b; }
.icon-purple { background: #faf5ff; color: #a855f7; }
.icon-indigo { background: #eef2ff; color: #6366f1; }
.icon-cyan { background: #ecfeff; color: #06b6d4; }
.icon-slate { background: #f1f5f9; color: #64748b; }

.grid-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.report-head-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.report-head-actions .btn {
    white-space: nowrap;
}

.report-block {
    margin-top: 16px;
}


.product-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.product-card {
    padding: 10px 11px;
    border: 1px solid var(--border);
    border-radius: 14px;
    background: linear-gradient(130deg, rgba(245,158,11,.14), rgba(15,109,255,.06));
}

.product-meta {
    margin-top: 3px;
    color: var(--text-muted);
    font-size: 11px;
}

.product-foot {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-top: 6px;
    font-size: 12px;
}

.mini-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 10px;
}

.mini-item {
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 14px;
    background: #fff;
}

.mini-item .meta {
    color: var(--text-muted);
    font-size: 12px;
    margin-top: 4px;
}

.activity-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.activity-item {
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 12px;
    background: #fff;
}

.activity-item .title {
    font-weight: 800;
}

.activity-item .meta {
    margin-top: 6px;
    color: var(--text-muted);
    font-size: 12px;
}

@media (max-width: 940px) {
    .report-head-actions {
        width: 100%;
    }

    .report-head-actions .btn {
        flex: 1 1 calc(50% - 5px);
        justify-content: center;
    }
}

@media (max-width: 700px) {
    .report-wrap .page-header {
        gap: 12px;
        margin-bottom: 14px;
    }

    .report-wrap .page-header > div {
        width: 100%;
    }

    .report-wrap .card {
        padding: 14px;
    }
}

@media (max-width: 420px) {
    .report-wrap .card {
        padding: 12px;
    }

    .report-head-actions {
        flex-direction: column;
        gap: 8px;
    }

    .report-head-actions .btn {
        width: 100%;
        min-height: 44px;
        flex: 1 1 100%;
    }

    .product-list {
        gap: 8px;
    }

    .product-card {
        padding: 9px 10px;
        border-radius: 12px;
    }

    .product-card strong {
        font-size: 15px;
        line-height: 1.15;
    }

    .product-foot {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }

    .mini-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="report-wrap">
    <div class="page-header">
        <div>
            <h1>Reports & Analytics</h1>
            <p class="page-subtitle">Sales trends, stock value, profit estimate and top products.</p>
        </div>
        <div class="report-head-actions">
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/index" class="btn btn-secondary"><span class="material-symbols-rounded">receipt_long</span>Open Sales</a>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/reports/export?date_filter=<?= urlencode($dateFilter ?? 'today') ?>&start_date=<?= urlencode($startDate ?? '') ?>&end_date=<?= urlencode($endDate ?? '') ?>" class="btn btn-primary"><span class="material-symbols-rounded">download</span>Export Sales CSV</a>
        </div>
    </div>

    <div class="report-filters" style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; align-items:center; background:#fff; padding:12px 16px; border-radius:18px; border:1px solid var(--border); box-shadow:var(--shadow-sm);">
        <form method="GET" action="" class="date-filter-strip" style="display:flex; gap:10px; flex-wrap:wrap; width:100%; align-items:center;">
            <input type="hidden" name="url" value="reports/index">
            <button type="submit" name="date_filter" value="today" class="btn <?= ($dateFilter ?? 'today') === 'today' ? 'btn-primary' : 'btn-secondary' ?>" style="padding:8px 16px; min-height:36px;">Today</button>
            <button type="submit" name="date_filter" value="yesterday" class="btn <?= ($dateFilter ?? 'today') === 'yesterday' ? 'btn-primary' : 'btn-secondary' ?>" style="padding:8px 16px; min-height:36px;">Yesterday</button>
            <button type="submit" name="date_filter" value="this_week" class="btn <?= ($dateFilter ?? 'today') === 'this_week' ? 'btn-primary' : 'btn-secondary' ?>" style="padding:8px 16px; min-height:36px;">This Week</button>
            <button type="submit" name="date_filter" value="this_month" class="btn <?= ($dateFilter ?? 'today') === 'this_month' ? 'btn-primary' : 'btn-secondary' ?>" style="padding:8px 16px; min-height:36px;">This Month</button>
            <button type="button" data-custom-date-toggle class="btn <?= ($dateFilter ?? 'today') === 'custom' ? 'btn-primary' : 'btn-secondary' ?>" style="padding:8px 16px; min-height:36px;">Custom Date</button>
            
            <div style="width:1px; height:24px; background:var(--border); margin:0 8px;" class="hidden-mob"></div>
            
            <div data-custom-date-range style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;" <?= ($dateFilter ?? 'today') === 'custom' ? '' : 'hidden' ?>>
                <input type="date" name="start_date" class="form-input" style="padding:6px 12px; height:36px; width:auto; font-size:13px;" value="<?= Security::escape($startDate ?? '') ?>">
                <span class="page-subtitle">to</span>
                <input type="date" name="end_date" class="form-input" style="padding:6px 12px; height:36px; width:auto; font-size:13px;" value="<?= Security::escape($endDate ?? '') ?>">
                <button type="submit" name="date_filter" value="custom" class="btn btn-secondary" style="padding:8px 16px; min-height:36px;">Apply Date</button>
            </div>
        </form>
    </div>

    <!-- PREMIUM REPORTS METRICS OVERHAUL -->
    <div class="dash-hero" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff; border-radius: 22px; padding: 28px 32px; margin-bottom: 24px; box-shadow: 0 14px 35px rgba(15,23,42,0.2); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; transition: transform 0.3s ease;">
        <div>
            <h2 style="margin:0 0 8px 0; font-size: 26px; font-weight: 800; display:flex; align-items:center; gap:10px; letter-spacing:-0.5px;">
                <span class="material-symbols-rounded" style="color:#38bdf8; font-size:28px;">insights</span>
                Reports Pulse
            </h2>
            <p style="margin:0; color:#cbd5e1; font-size:15px; font-weight:500;">Complete overview of generated revenue and margins.</p>
        </div>
        <div style="text-align: right; background: rgba(255,255,255,0.06); padding: 18px 24px; border-radius: 18px; border: 1px solid rgba(255,255,255,0.12); backdrop-filter: blur(10px); min-width: 200px;">
            <div style="color:#94a3b8; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Gross Profit</div>
            <div style="font-size:36px; font-weight:900; color:<?php echo $monthly_profit >= 0 ? '#34d399' : '#f87171'; ?>; filter: drop-shadow(0 2px 10px rgba(0,0,0,0.3)); letter-spacing:-1px;">
                <?php echo App::money($monthly_profit); ?>
            </div>
            <div style="color:#64748b; font-size:12px; font-weight:600; margin-top:6px;">Net Revenue - Cost of Goods</div>
        </div>
    </div>

    <div class="grid-cards">
        <div class="premium-metric-card">
            <div class="premium-metric-icon icon-blue"><span class="material-symbols-rounded">point_of_sale</span></div>
            <div class="premium-metric-content">
                <small>Total Revenue</small>
                <strong><?php echo App::money($monthly_sales); ?></strong>
            </div>
        </div>
        <div class="premium-metric-card" style="border-left: 4px solid #f43f5e;">
            <div class="premium-metric-icon icon-rose"><span class="material-symbols-rounded">settings_backup_restore</span></div>
            <div class="premium-metric-content">
                <small>Returns & Refunds</small>
                <strong style="color: #f43f5e;"><?php echo App::money($returns); ?></strong>
            </div>
        </div>
        <div class="premium-metric-card">
            <div class="premium-metric-icon icon-emerald"><span class="material-symbols-rounded">payments</span></div>
            <div class="premium-metric-content">
                <small>Cash Collected</small>
                <strong><?php echo App::money($paymentBreakdown['cash']); ?></strong>
            </div>
        </div>
        <div class="premium-metric-card">
            <div class="premium-metric-icon icon-cyan"><span class="material-symbols-rounded">account_balance</span></div>
            <div class="premium-metric-content">
                <small>Online Received</small>
                <strong><?php echo App::money($paymentBreakdown['online']); ?></strong>
            </div>
        </div>
        <div class="premium-metric-card" style="border-left: 4px solid #f59e0b;">
            <div class="premium-metric-icon icon-amber"><span class="material-symbols-rounded">credit_score</span></div>
            <div class="premium-metric-content">
                <small>Credit Debt (Udhar)</small>
                <strong style="color: #d97706;"><?php echo App::money($paymentBreakdown['credit']); ?></strong>
            </div>
        </div>
        <div class="premium-metric-card">
            <div class="premium-metric-icon icon-rose"><span class="material-symbols-rounded">receipt_long</span></div>
            <div class="premium-metric-content">
                <small>Total Expenses</small>
                <strong><?php echo App::money($expenseBreakdown['total']); ?></strong>
            </div>
        </div>
    </div>

    <h3 style="margin-bottom:14px; margin-top:30px; font-size:18px; color:var(--text-dark);">Inventory Status</h3>
    <div class="grid-cards">
        <div class="premium-metric-card">
            <div class="premium-metric-icon icon-indigo"><span class="material-symbols-rounded">category</span></div>
            <div class="premium-metric-content">
                <small>Total Catalog Items</small>
                <strong><?php echo number_format($inventoryTotals['products']); ?></strong>
            </div>
        </div>
        <div class="premium-metric-card">
            <div class="premium-metric-icon icon-purple"><span class="material-symbols-rounded">inventory_2</span></div>
            <div class="premium-metric-content">
                <small>Overall Stock Units</small>
                <strong><?php echo number_format($inventoryTotals['units']); ?></strong>
            </div>
        </div>
        <div class="premium-metric-card">
            <div class="premium-metric-icon icon-emerald"><span class="material-symbols-rounded">savings</span></div>
            <div class="premium-metric-content">
                <small>Est. Stock Value</small>
                <strong><?php echo App::money($inventoryTotals['value']); ?></strong>
            </div>
        </div>
        <div class="premium-metric-card" style="border-left: 4px solid #f43f5e;">
            <div class="premium-metric-icon icon-rose"><span class="material-symbols-rounded">warning</span></div>
            <div class="premium-metric-content">
                <small>Low Stock Alerts</small>
                <strong style="color: #f43f5e;"><?php echo number_format($lowStockCount); ?></strong>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap:24px; margin-top:30px;">
        <div class="card report-block" style="padding:0; overflow:hidden; border:none; box-shadow:0 10px 30px rgba(15,23,42,0.04); border-radius:24px;">
            <div style="padding:22px 24px; border-bottom:1px solid #f1f5f9; background:#fff; display:flex; align-items:center; justify-content:space-between;">
                <h3 style="margin:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-rounded" style="color:#f59e0b;">star</span> Top Products
                </h3>
                <span class="badge" style="background:#fef3c7; color:#d97706;">Top 5</span>
            </div>
            <div style="background:#fff; padding:12px;">
                <?php if (empty($topProducts)): ?>
                    <div class="empty-state">No sales data available yet.</div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <?php foreach ($topProducts as $idx => $product): ?>
                            <div style="display:flex; align-items:center; gap:16px; padding:16px; border-radius:16px; background:linear-gradient(to right, #f8fafc, #ffffff); border:1px solid #f1f5f9; transition:all 0.2s;">
                                <div style="width:40px; height:40px; border-radius:12px; background:#eff6ff; color:#3b82f6; display:grid; place-items:center; font-weight:900; font-size:16px; flex-shrink:0;">
                                    #<?php echo $idx + 1; ?>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <h4 style="margin:0 0 4px; font-size:15px; font-weight:800; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?php echo Security::escape($product['product_name']); ?>
                                    </h4>
                                    <div style="display:flex; gap:12px; font-size:12px; color:#64748b; font-weight:600;">
                                        <span><?php echo Security::escape($product['model_number'] ?: 'No Model'); ?></span>
                                        <span style="display:flex; align-items:center; gap:4px;"><span class="material-symbols-rounded" style="font-size:14px;">sell</span> <?php echo (int) $product['qty_sold']; ?> sold</span>
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <strong style="display:block; font-size:16px; color:#10b981;"><?php echo App::money($product['revenue']); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card report-block" style="padding:0; overflow:hidden; border:none; box-shadow:0 10px 30px rgba(15,23,42,0.04); border-radius:24px;">
            <div style="padding:22px 24px; border-bottom:1px solid #f1f5f9; background:#fff; display:flex; align-items:center; justify-content:space-between;">
                <h3 style="margin:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-rounded" style="color:#ef4444;">trending_down</span> Low Performers
                </h3>
            </div>
            <div style="background:#fff; padding:12px;">
                <?php if (empty($lowProducts)): ?>
                    <div class="empty-state">No low-selling data available.</div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <?php foreach ($lowProducts as $product): ?>
                            <div style="display:flex; align-items:center; gap:16px; padding:14px 16px; border-radius:16px; background:#fff; border:1px solid #f1f5f9;">
                                <div style="width:36px; height:36px; border-radius:10px; background:#fef2f2; color:#ef4444; display:grid; place-items:center; flex-shrink:0;">
                                    <span class="material-symbols-rounded" style="font-size:20px;">production_quantity_limits</span>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <h4 style="margin:0 0 4px; font-size:14px; font-weight:700; color:#334155; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?php echo Security::escape($product['product_name']); ?>
                                    </h4>
                                    <div style="font-size:12px; color:#94a3b8;"><?php echo Security::escape($product['model_number'] ?: ''); ?></div>
                                </div>
                                <div style="text-align:right; font-weight:800; font-size:14px; color:#ef4444;">
                                    <?php echo (int) $product['qty_sold']; ?> sold
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card report-block" style="padding:0; overflow:hidden; border:none; box-shadow:0 10px 30px rgba(15,23,42,0.04); border-radius:24px;">
            <div style="padding:22px 24px; border-bottom:1px solid #f1f5f9; background:#fff; display:flex; align-items:center; justify-content:space-between;">
                <h3 style="margin:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-rounded" style="color:#3b82f6;">history</span> Global Activity
                </h3>
            </div>
            <div style="background:#fff; padding:12px; max-height:480px; overflow-y:auto;">
                <?php if (empty($recentActivity)): ?>
                    <div class="empty-state">No activity recorded yet.</div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <?php foreach ($recentActivity as $activity): ?>
                            <div style="display:flex; gap:16px; padding:16px; border-radius:16px; background:#f8fafc; border:1px solid #e2e8f0;">
                                <div style="width:36px; height:36px; border-radius:10px; background:#e0e7ff; color:#6366f1; display:grid; place-items:center; flex-shrink:0;">
                                    <span class="material-symbols-rounded" style="font-size:20px;">info</span>
                                </div>
                                <div>
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px;">
                                        <strong style="font-size:13px; color:#1e293b;"><?php echo Security::escape($activity['action']); ?></strong>
                                    </div>
                                    <div style="font-size:13px; color:#475569; margin-bottom:8px; line-height:1.4;">
                                        <?php echo Security::escape($activity['details']); ?>
                                    </div>
                                    <div style="font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px;">
                                        <?php echo date('M d, H:i', strtotime($activity['created_at'])); ?> • <?php echo Security::escape($activity['user_name'] ?: 'System'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
