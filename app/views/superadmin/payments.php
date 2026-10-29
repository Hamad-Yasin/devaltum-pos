<?php ob_start(); ?>
<?php
$paymentsTab = strtolower(trim((string) ($_GET['tab'] ?? 'form')));
if (!in_array($paymentsTab, ['form', 'table', 'summary'], true)) {
    $paymentsTab = 'form';
}
?>

<div class="page-header">
    <div>
        <h1>Payment History</h1>
        <p class="page-subtitle">Track all store payments and monitor platform revenue.</p>
    </div>
</div>

<div class="tab-strip" style="margin-bottom:18px;">
    <a class="tab-pill <?php echo $paymentsTab === 'form'    ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/payments?tab=form">Record Payment</a>
    <a class="tab-pill <?php echo $paymentsTab === 'table'   ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/payments?tab=table">Payments Table</a>
    <a class="tab-pill <?php echo $paymentsTab === 'summary' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/payments?tab=summary">Revenue Summary</a>
</div>

<?php if ($paymentsTab === 'summary'): ?>
<div class="grid-cards">
    <article class="card metric-card">
        <small>Total Revenue</small>
        <strong><?php echo App::money((float) ($summary['total_revenue'] ?? 0)); ?></strong>
    </article>
    <article class="card metric-card">
        <small>Monthly Revenue</small>
        <strong><?php echo App::money((float) ($summary['monthly_revenue'] ?? 0)); ?></strong>
    </article>
    <article class="card metric-card">
        <small>Active Subscriptions</small>
        <strong><?php echo number_format((int) ($summary['active_subscriptions'] ?? 0)); ?></strong>
    </article>
</div>

<?php elseif ($paymentsTab === 'table'): ?>
<?php
$payLogoMap = [
    'easypaisa' => '/assets/img/payment/easypaisa.png',
    'jazzcash'  => '/assets/img/payment/jazzcash.png',
    'nayapay'   => '/assets/img/payment/nayapay.png',
    'meezan'    => '/assets/img/payment/meezan.webp',
    'bank'      => '/assets/img/bank.svg',
];
?>
<section class="card" style="border-radius:18px; overflow:hidden; padding:0; box-shadow:0 4px 20px rgba(15,23,42,0.04);">
    <div style="width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; scrollbar-width:thin;">
        <table style="width:100%; min-width:980px; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                    <th style="padding:14px 18px; text-align:left; font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; white-space:nowrap;">Store Name</th>
                    <th style="padding:14px 18px; text-align:left; font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; white-space:nowrap;">Plan Name</th>
                    <th style="padding:14px 18px; text-align:left; font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; white-space:nowrap;">Amount Paid</th>
                    <th style="padding:14px 18px; text-align:left; font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; white-space:nowrap;">Payment Method / Trx ID</th>
                    <th style="padding:14px 18px; text-align:left; font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; white-space:nowrap;">Proof Screenshot</th>
                    <th style="padding:14px 18px; text-align:left; font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; white-space:nowrap;">Payment Date</th>
                    <th style="padding:14px 18px; text-align:left; font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; white-space:nowrap;">Status</th>
                    <th style="padding:14px 18px; text-align:right; font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; white-space:nowrap;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="8" style="padding:32px; text-align:center; color:#94a3b8; font-weight:600;">No payment records available.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <?php
                        $methodName = (string) ($payment['method_name'] ?: $payment['method']);
                        $methodSlug = strtolower($methodName);
                        $logoSrc = '';
                        foreach ($payLogoMap as $k => $p) {
                            if (str_contains($methodSlug, $k)) {
                                $logoSrc = (defined('BASE_URL') ? BASE_URL : '') . $p;
                                break;
                            }
                        }
                        $st = strtolower(trim((string) ($payment['status'] ?? 'paid')));
                        ?>
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:16px 18px; white-space:nowrap;"><strong style="color:#0f172a; font-size:14px;"><?php echo Security::escape((string) $payment['store_name']); ?></strong></td>
                            <td style="padding:16px 18px; white-space:nowrap; color:#334155; font-weight:600;"><?php echo Security::escape((string) $payment['plan_name']); ?></td>
                            <td style="padding:16px 18px; white-space:nowrap;"><strong style="color:#0f172a; font-size:14.5px;"><?php echo App::money((float) $payment['amount']); ?></strong></td>
                            <td style="padding:16px 18px; white-space:nowrap;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <?php if ($logoSrc): ?>
                                        <img src="<?= $logoSrc ?>" alt="<?= htmlspecialchars($methodName) ?>" style="width:22px; height:22px; object-fit:contain;">
                                    <?php else: ?>
                                        <span class="material-symbols-rounded" style="font-size:18px; color:#64748b;">account_balance</span>
                                    <?php endif; ?>
                                    <span style="font-weight:700; color:#1e293b;"><?php echo Security::escape($methodName); ?></span>
                                </div>
                                <?php if (!empty($payment['gateway_ref'])): ?>
                                    <small style="color:#94a3b8; font-size:11px; font-family:monospace; display:block; margin-top:2px;"><?php echo Security::escape((string) $payment['gateway_ref']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="padding:16px 18px; white-space:nowrap;">
                                <?php if (!empty($payment['receipt_screenshot'])): ?>
                                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/<?= htmlspecialchars(ltrim($payment['receipt_screenshot'], '/')) ?>" target="_blank" title="View Full Payment Screenshot">
                                        <img src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/<?= htmlspecialchars(ltrim($payment['receipt_screenshot'], '/')) ?>" alt="Proof" style="width:38px; height:38px; object-fit:cover; border-radius:8px; border:1px solid #cbd5e1;">
                                    </a>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:12px; font-weight:600;">None</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:16px 18px; white-space:nowrap; color:#475569; font-weight:600;">
                                <?php echo !empty($payment['paid_at']) ? date('d M, Y', strtotime($payment['paid_at'])) : date('d M, Y', strtotime($payment['created_at'])); ?>
                            </td>
                            <td style="padding:16px 18px; white-space:nowrap;">
                                <?php if (in_array($st, ['paid', 'approved', 'completed'], true)): ?>
                                    <span style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; font-size:11.5px; font-weight:800; padding:4px 12px; border-radius:999px; display:inline-flex; align-items:center; gap:4px;">
                                        <span class="material-symbols-rounded" style="font-size:14px;">check_circle</span> Approved
                                    </span>
                                <?php elseif ($st === 'pending'): ?>
                                    <span style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; font-size:11.5px; font-weight:800; padding:4px 12px; border-radius:999px; display:inline-flex; align-items:center; gap:4px;">
                                        <span class="material-symbols-rounded" style="font-size:14px;">hourglass_empty</span> Pending
                                    </span>
                                <?php else: ?>
                                    <span style="background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; font-size:11.5px; font-weight:800; padding:4px 12px; border-radius:999px; display:inline-flex; align-items:center; gap:4px;">
                                        <span class="material-symbols-rounded" style="font-size:14px;">cancel</span> Rejected
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:16px 18px; text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; align-items:center; gap:8px; justify-content:flex-end;">
                                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/editPayment/<?php echo (int) $payment['id']; ?>"
                                       class="btn btn-secondary"
                                       style="padding:4px 12px; font-size:12px; height:32px; border-radius:8px; font-weight:700; display:inline-flex; align-items:center; gap:4px;"
                                       title="Edit & Verify">
                                        <span class="material-symbols-rounded" style="font-size:16px;">edit</span> Edit
                                    </a>
                                    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/deletePaymentRecord/<?php echo (int) $payment['id']; ?>"
                                        method="POST" data-no-ajax
                                        onsubmit="return confirm('Delete this payment record? This will NOT undo the subscription extension.');"
                                        style="display:inline; margin:0;">
                                        <?php echo Security::csrfField(); ?>
                                        <button class="icon-btn" type="submit" title="Delete" style="color:#ef4444; width:32px; height:32px;">
                                            <span class="material-symbols-rounded" style="font-size:18px;">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php else: ?>
<section class="card" style="margin-bottom:18px; padding:26px; border-radius:18px;">
    <h3 style="margin-bottom:18px; display:flex; align-items:center; gap:8px;">
        <span class="material-symbols-rounded" style="color:var(--primary);">add_card</span> Record Payment & Subscription Renewal
    </h3>
    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/recordPayment" method="POST" data-no-ajax>
        <?php echo Security::csrfField(); ?>
        <div class="split-grid" style="grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:18px 20px;">
            <div class="form-group">
                <label class="form-label" style="font-weight:700;">Select Store</label>
                <select class="form-select" name="store_id" id="storeSelect" required>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?php echo (int) $store['id']; ?>">
                            <?php echo Security::escape($store['store_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" style="font-weight:700;">Subscription Plan</label>
                <select class="form-select" name="plan_id" id="planSelect" onchange="autoCalculateBilling()" required>
                    <?php foreach ($plans as $plan): ?>
                        <option value="<?php echo (int) $plan['id']; ?>"
                                data-monthly="<?php echo (float) $plan['monthly_price']; ?>"
                                data-yearly="<?php echo (float) ($plan['yearly_price'] ?? ($plan['monthly_price'] * 12)); ?>">
                            <?php echo Security::escape($plan['plan_name']); ?> (Rs. <?php echo number_format($plan['monthly_price']); ?>/mo)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" style="font-weight:700;">Billing Cycle</label>
                <select class="form-select" name="billing_cycle" id="cycleSelect" onchange="autoCalculateBilling()">
                    <option value="monthly" selected>Monthly Plan</option>
                    <option value="yearly">Yearly Plan</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" style="font-weight:700;">Payment Method</label>
                <select class="form-select" name="payment_method_id" required>
                    <?php foreach ($methods as $method): ?>
                        <option value="<?php echo (int) $method['id']; ?>"><?php echo Security::escape($method['method_name']); ?></option>
                    <?php endforeach; ?>
                    <?php if (empty($methods)): ?>
                        <option value="1">EasyPaisa</option>
                        <option value="2">JazzCash</option>
                        <option value="3">Meezan Bank</option>
                        <option value="4">Bank Transfer</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" style="font-weight:700;">Amount Paid (Rs)</label>
                <input class="form-input" type="number" name="amount_paid" id="amountPaidInput" step="0.01" value="<?php echo (float) ($plans[0]['monthly_price'] ?? 2500); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" style="font-weight:700;">Payment Date</label>
                <input class="form-input" type="date" name="payment_date" id="paymentDateInput" onchange="autoCalculateBilling()" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" style="font-weight:700;">Next Installment / Expiry Date</label>
                <input class="form-input" type="date" name="next_monthly_charge_date" id="nextChargeDateInput" value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" style="font-weight:700;">Installment Amount (Rs)</label>
                <input class="form-input" type="number" name="monthly_charge_amount" id="installmentAmountInput" step="0.01" value="<?php echo (float) ($plans[0]['monthly_price'] ?? 2500); ?>">
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label" style="font-weight:700;">Notes</label>
                <textarea class="form-input" name="notes" rows="2" placeholder="Optional transaction notes..."></textarea>
            </div>
        </div>
        <button class="btn btn-primary" type="submit" style="margin-top:14px; font-weight:800; border-radius:12px; height:42px; padding:0 24px; display:inline-flex; align-items:center; gap:6px;">
            <span class="material-symbols-rounded">check_circle</span> Record & Renew Subscription
        </button>
    </form>
</section>

<script>
function autoCalculateBilling() {
    const planSelect = document.getElementById('planSelect');
    const cycleSelect = document.getElementById('cycleSelect');
    const paymentDateInput = document.getElementById('paymentDateInput');
    const amountPaidInput = document.getElementById('amountPaidInput');
    const nextChargeDateInput = document.getElementById('nextChargeDateInput');
    const installmentAmountInput = document.getElementById('installmentAmountInput');

    if (!planSelect) return;
    const opt = planSelect.options[planSelect.selectedIndex];
    if (!opt) return;

    const monthly = parseFloat(opt.dataset.monthly || 0);
    const yearly = parseFloat(opt.dataset.yearly || (monthly * 12));
    const cycle = cycleSelect ? cycleSelect.value : 'monthly';

    const chargeAmount = cycle === 'yearly' ? yearly : monthly;
    if (amountPaidInput) amountPaidInput.value = chargeAmount;
    if (installmentAmountInput) installmentAmountInput.value = monthly;

    const baseDateStr = paymentDateInput && paymentDateInput.value ? paymentDateInput.value : new Date().toISOString().split('T')[0];
    const baseDate = new Date(baseDateStr);
    if (!isNaN(baseDate.getTime())) {
        const nextDate = new Date(baseDate);
        if (cycle === 'yearly') {
            nextDate.setFullYear(nextDate.getFullYear() + 1);
        } else {
            nextDate.setMonth(nextDate.getMonth() + 1);
        }
        const yyyy = nextDate.getFullYear();
        const mm = String(nextDate.getMonth() + 1).padStart(2, '0');
        const dd = String(nextDate.getDate()).padStart(2, '0');
        if (nextChargeDateInput) {
            nextChargeDateInput.value = `${yyyy}-${mm}-${dd}`;
        }
    }
}
document.addEventListener('DOMContentLoaded', autoCalculateBilling);
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
