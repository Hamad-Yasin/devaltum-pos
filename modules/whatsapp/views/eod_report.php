<?php ob_start(); ?>

<style>
    .eod-grid {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 1024px) {
        .eod-grid { grid-template-columns: 1fr; }
    }

    .eod-card {
        background: #ffffff;
        border: 1px solid var(--border, #e2e8f0);
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.04);
        margin-bottom: 20px;
    }

    .kpi-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    .kpi-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px;
    }
    .kpi-title { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
    .kpi-val { font-size: 20px; font-weight: 900; color: #0f172a; }

    .wa-preview-card {
        background: #075e54;
        color: #ffffff;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(7, 94, 84, 0.25);
        position: sticky;
        top: 20px;
    }
    .wa-bubble {
        background: #dcf8c6;
        color: #111b21;
        border-radius: 12px;
        padding: 16px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        font-size: 13px;
        line-height: 1.5;
        white-space: pre-wrap;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
</style>

<?php ob_start(); ?>

<div class="page-header">
    <div>
        <h1>Daily End-of-Day (EOD) WhatsApp Report</h1>
        <p class="page-subtitle">Complete evening financial audit, cash drawer summary, and automated WhatsApp closing dispatch (<?= date('d M Y') ?>).</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/reports/index" class="btn btn-secondary">
            <span class="material-symbols-rounded">arrow_back</span>Back to Reports
        </a>
    </div>
</div>

    <div class="eod-grid">

        <!-- Left Column: Metrics & Breakdowns -->
        <div>
            <div class="eod-card">
                <div style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="color: #2563eb;">point_of_sale</span>
                    Today's Financial Summary
                </div>

                <div class="kpi-row">
                    <div class="kpi-box">
                        <div class="kpi-title">Total Sales Revenue</div>
                        <div class="kpi-val" style="color: #2563eb;"><?= App::money((float)$salesData['total_sales']) ?></div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;"><?= (int)$salesData['total_bills'] ?> Bills / Receipts</div>
                    </div>

                    <div class="kpi-box">
                        <div class="kpi-title">Cash in Drawer</div>
                        <div class="kpi-val" style="color: #059669;"><?= App::money((float)$salesData['cash_sales']) ?></div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Physical Cash Collected</div>
                    </div>

                    <div class="kpi-box">
                        <div class="kpi-title">Bank / Card / Online</div>
                        <div class="kpi-val" style="color: #0891b2;"><?= App::money((float)$salesData['online_sales']) ?></div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Digital receipts</div>
                    </div>

                    <div class="kpi-box">
                        <div class="kpi-title">New Credit / Udhaar</div>
                        <div class="kpi-val" style="color: #d97706;"><?= App::money((float)$salesData['new_credit_given']) ?></div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Pending from customers</div>
                    </div>
                </div>

                <div style="background: #f1f5f9; border-radius: 14px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <div style="font-size: 12px; font-weight: 700; color: #64748b;">Daily Expenses Deducted</div>
                        <div style="font-size: 18px; font-weight: 800; color: #dc2626;">- <?= App::money((float)$expensesData['total_expenses']) ?></div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 12px; font-weight: 700; color: #64748b;">Estimated Net Profit Today</div>
                        <div style="font-size: 22px; font-weight: 900; color: #059669;">+ <?= App::money($netProfit) ?></div>
                    </div>
                </div>
            </div>

            <!-- Top Items Card -->
            <div class="eod-card">
                <div style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="color: #f59e0b;">military_tech</span>
                    Top Selling Products Today
                </div>

                <?php if (empty($topItems)): ?>
                    <div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 13px;">No items sold today yet.</div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php $rank = 1; foreach ($topItems as $item): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="background: #0f172a; color: #fff; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800;"><?= $rank++ ?></span>
                                    <strong style="color: #0f172a; font-size: 14px;"><?= htmlspecialchars($item['product_name']) ?></strong>
                                </div>
                                <span style="font-weight: 800; color: #2563eb; font-size: 14px;"><?= (float)$item['total_sold'] ?> Sold</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: WhatsApp Live Dispatch -->
        <div>
            <div class="wa-preview-card">
                <div style="font-size: 16px; font-weight: 800; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="color: #25D366;">chat</span>
                    WhatsApp Message Preview
                </div>

                <div class="wa-bubble">
                    <?= htmlspecialchars($reportText) ?>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.8); margin-bottom: 4px; display: block;">Recipient WhatsApp Number</label>
                    <input type="text" id="target_phone" value="<?= htmlspecialchars($ownerPhone) ?>" placeholder="e.g. 03001234567" class="form-control" style="background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.25); color: #fff; font-weight: 700;">
                </div>

                <button type="button" onclick="sendWhatsAppEOD()" class="btn" style="width: 100%; background: #25D366; color: #fff; font-weight: 800; font-size: 15px; padding: 12px; border-radius: 12px; box-shadow: 0 4px 16px rgba(37,211,102,0.4); display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span class="material-symbols-rounded">send</span> Send EOD Report via WhatsApp
                </button>
            </div>
        </div>

    </div>

</div>

<script>
    function sendWhatsAppEOD() {
        let phone = document.getElementById('target_phone').value.trim().replace(/[^0-9]/g, '');
        if (phone.startsWith('0')) {
            phone = '92' + phone.substring(1);
        }
        const text = <?= json_encode($reportText) ?>;
        const link = phone ? `https://wa.me/${phone}?text=${encodeURIComponent(text)}` : `https://wa.me/?text=${encodeURIComponent(text)}`;
        window.open(link, '_blank');
    }
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
