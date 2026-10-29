<?php ob_start(); ?>
<?php
$payLogoMap = [
    'easypaisa' => '/assets/img/payment/easypaisa.png',
    'jazzcash'  => '/assets/img/payment/jazzcash.png',
    'nayapay'   => '/assets/img/payment/nayapay.png',
    'meezan'    => '/assets/img/payment/meezan.webp',
    'bank'      => '/assets/img/bank.svg',
];
$currStatus = strtolower(trim((string)($payment['status'] ?? 'paid')));
if (in_array($currStatus, ['approved', 'completed', 'active'], true)) {
    $currStatus = 'paid';
} elseif (in_array($currStatus, ['rejected', 'declined'], true)) {
    $currStatus = 'failed';
}
?>

<style>
/* ── Premium Status Selector Cards ────────────────── */
.status-segmented-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}
.status-option-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
    position: relative;
}
.status-option-card:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}
.status-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
}
.status-card-icon .material-symbols-rounded {
    font-size: 20px;
}
.status-card-info {
    flex: 1;
    min-width: 0;
}
.status-card-info strong {
    display: block;
    font-size: 13.5px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.2;
}
.status-card-info span {
    display: block;
    font-size: 11px;
    color: #64748b;
    margin-top: 3px;
}
.status-card-check {
    font-size: 18px;
    opacity: 0;
    transition: opacity 0.2s;
}

/* ── Approved Active State ────────────────── */
.status-option-card.active.approved {
    border-color: #10b981;
    background: #f0fdf4;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.12);
}
.approved-icon {
    background: #dcfce7;
    color: #15803d;
}
.status-option-card.active.approved .status-card-info strong {
    color: #15803d;
}
.status-option-card.active.approved .status-card-check {
    color: #15803d;
    opacity: 1;
}

/* ── Pending Active State ─────────────────── */
.status-option-card.active.pending {
    border-color: #f59e0b;
    background: #fffbeb;
    box-shadow: 0 4px 14px rgba(245, 158, 11, 0.12);
}
.pending-icon {
    background: #fef3c7;
    color: #b45309;
}
.status-option-card.active.pending .status-card-info strong {
    color: #b45309;
}
.status-option-card.active.pending .status-card-check {
    color: #b45309;
    opacity: 1;
}

/* ── Rejected Active State ────────────────── */
.status-option-card.active.rejected {
    border-color: #ef4444;
    background: #fef2f2;
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.12);
}
.rejected-icon {
    background: #fee2e2;
    color: #b91c1c;
}
.status-option-card.active.rejected .status-card-info strong {
    color: #b91c1c;
}
.status-option-card.active.rejected .status-card-check {
    color: #b91c1c;
    opacity: 1;
}
</style>

<div class="page-header">
    <div>
        <h1>Edit Payment & Approval Record</h1>
        <p class="page-subtitle">Verify transaction, update plan dates, and approve subscription for <?php echo Security::escape((string) $payment['store_name']); ?>.</p>
    </div>
    <div>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/payments?tab=table" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px;">
            <span class="material-symbols-rounded">arrow_back</span> Back to Table
        </a>
    </div>
</div>

<div class="split-grid" style="display:grid; grid-template-columns:minmax(0,1.4fr) minmax(320px,.6fr); gap:24px; align-items:start;">
    <!-- Edit Form Panel -->
    <section class="card" style="padding:26px; border-radius:18px;">
        <h3 style="margin-bottom:20px; display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-rounded" style="color:var(--primary);">tune</span> Payment & Approval Configurations
        </h3>

        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/updatePaymentRecord" method="POST" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <input type="hidden" name="id" value="<?php echo (int) $payment['id']; ?>">
            
            <!-- Hidden status input updated by segmented selector -->
            <input type="hidden" name="status" id="selectedPaymentStatus" value="<?php echo $currStatus; ?>">

            <div style="display:flex; flex-direction:column; gap:20px; margin-bottom:24px;">
                
                <!-- Status Segmented Selection -->
                <div class="form-group">
                    <label class="form-label" style="font-weight:700; margin-bottom:8px;">Verification & Approval Status</label>
                    <div class="status-segmented-grid">
                        <!-- Approved Card -->
                        <div class="status-option-card <?php echo $currStatus === 'paid' ? 'active approved' : ''; ?>" onclick="selectStatus('paid', this, 'approved')">
                            <div class="status-card-icon approved-icon">
                                <span class="material-symbols-rounded">check_circle</span>
                            </div>
                            <div class="status-card-info">
                                <strong>Approved & Active</strong>
                                <span>Account activated & renewed</span>
                            </div>
                            <span class="status-card-check material-symbols-rounded">done</span>
                        </div>

                        <!-- Pending Card -->
                        <div class="status-option-card <?php echo $currStatus === 'pending' ? 'active pending' : ''; ?>" onclick="selectStatus('pending', this, 'pending')">
                            <div class="status-card-icon pending-icon">
                                <span class="material-symbols-rounded">hourglass_top</span>
                            </div>
                            <div class="status-card-info">
                                <strong>Pending Verification</strong>
                                <span>Awaiting manual verification</span>
                            </div>
                            <span class="status-card-check material-symbols-rounded">done</span>
                        </div>

                        <!-- Rejected Card -->
                        <div class="status-option-card <?php echo $currStatus === 'failed' ? 'active rejected' : ''; ?>" onclick="selectStatus('failed', this, 'rejected')">
                            <div class="status-card-icon rejected-icon">
                                <span class="material-symbols-rounded">cancel</span>
                            </div>
                            <div class="status-card-info">
                                <strong>Rejected / Declined</strong>
                                <span>Mark payment invalid</span>
                            </div>
                            <span class="status-card-check material-symbols-rounded">done</span>
                        </div>
                    </div>
                </div>

                <!-- Form Fields Grid -->
                <div class="split-grid" style="grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px 20px;">
                    
                    <!-- Plan Selector -->
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">Subscription Plan</label>
                        <select class="form-select" id="editPlanSelect" name="plan_id" onchange="onPlanChanged()">
                            <?php foreach ($plans as $p): ?>
                                <?php $selected = ((int)$payment['plan_id'] === (int)$p['id'] || (empty($payment['plan_id']) && (int)$payment['current_plan_id'] === (int)$p['id'])); ?>
                                <option value="<?php echo (int) $p['id']; ?>" 
                                        data-monthly="<?php echo (float) $p['monthly_price']; ?>"
                                        data-yearly="<?php echo (float) ($p['yearly_price'] ?? ($p['monthly_price'] * 12)); ?>"
                                        <?php echo $selected ? 'selected' : ''; ?>>
                                    <?php echo Security::escape($p['plan_name']); ?> (Rs. <?php echo number_format($p['monthly_price']); ?>/mo)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Payment Method Selector -->
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">Payment Method</label>
                        <select class="form-select" name="method">
                            <?php foreach ($methods as $m): ?>
                                <?php 
                                $currMethod = strtolower(trim((string)($payment['method_name'] ?: $payment['method'])));
                                $mName = strtolower(trim((string)$m['method_name']));
                                $selected = ($currMethod === $mName || (int)$payment['method'] === (int)$m['id']);
                                ?>
                                <option value="<?php echo Security::escape($m['method_name']); ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                    <?php echo Security::escape($m['method_name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (empty($methods)): ?>
                                <option value="EasyPaisa" selected>EasyPaisa</option>
                                <option value="JazzCash">JazzCash</option>
                                <option value="Meezan Bank">Meezan Bank</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Amount Paid -->
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">Amount Paid (Rs)</label>
                        <input class="form-input" type="number" step="0.01" id="editAmountPaid" name="amount" value="<?php echo (float) $payment['amount']; ?>" required>
                    </div>

                    <!-- Installment Amount -->
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">Installment Amount (Rs)</label>
                        <input class="form-input" type="number" step="0.01" id="editInstallmentAmount" name="monthly_charge_amount" value="<?php echo !empty($payment['monthly_charge_amount']) ? (float)$payment['monthly_charge_amount'] : (float)($payment['plan_monthly_price'] ?? 2500); ?>">
                    </div>

                    <!-- Payment Date -->
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">Payment Date</label>
                        <input class="form-input" type="date" id="editPaidAt" name="paid_at" onchange="onPaymentDateChanged()" value="<?php echo !empty($payment['paid_at']) ? date('Y-m-d', strtotime($payment['paid_at'])) : (!empty($payment['created_at']) ? date('Y-m-d', strtotime($payment['created_at'])) : date('Y-m-d')); ?>">
                    </div>

                    <!-- Expiry Date -->
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">Subscription Expiry Date</label>
                        <input class="form-input" type="date" id="editExpiryDate" name="next_expiry_date" value="<?php echo !empty($payment['next_expiry_date']) ? date('Y-m-d', strtotime($payment['next_expiry_date'])) : (!empty($payment['subscription_expires_at']) ? date('Y-m-d', strtotime($payment['subscription_expires_at'])) : date('Y-m-d', strtotime('+1 month'))); ?>">
                    </div>

                    <!-- Next Installment Date -->
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700;">Next Installment Date</label>
                        <input class="form-input" type="date" id="editNextChargeDate" name="next_monthly_charge_date" value="<?php echo !empty($payment['next_monthly_charge_date']) ? date('Y-m-d', strtotime($payment['next_monthly_charge_date'])) : date('Y-m-d', strtotime('+1 month')); ?>">
                    </div>

                    <!-- Notes -->
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label" style="font-weight:700;">Admin Notes / Remarks</label>
                        <textarea class="form-input" name="notes" rows="3" placeholder="Add approval remarks or transaction reference notes..."><?php echo Security::escape((string) ($payment['notes'] ?? '')); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div style="display:flex; align-items:center; gap:12px;">
                <button class="btn btn-primary" type="submit" style="display:inline-flex; align-items:center; gap:8px; padding:12px 28px; font-weight:800; border-radius:12px;">
                    <span class="material-symbols-rounded">save</span> Save & Update Record
                </button>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/payments?tab=table" class="btn btn-secondary" style="border-radius:12px; font-weight:700; height:42px; display:inline-flex; align-items:center;">Cancel</a>
            </div>
        </form>
    </section>

    <!-- Details & Proof Sidebar -->
    <aside style="display:flex; flex-direction:column; gap:20px;">
        <!-- Store Profile Card -->
        <div class="card" style="padding:20px; border-radius:18px;">
            <h3 style="margin-bottom:14px; display:flex; align-items:center; gap:6px;">
                <span class="material-symbols-rounded" style="color:var(--primary);">store</span> Store Profile
            </h3>
            
            <div style="display:flex; flex-direction:column; gap:12px;">
                <div style="border-bottom:1px solid #f1f5f9; padding-bottom:8px;">
                    <small style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Store Name</small>
                    <strong style="font-size:15px; color:#0f172a;"><?php echo Security::escape((string) $payment['store_name']); ?></strong>
                </div>
                <div style="border-bottom:1px solid #f1f5f9; padding-bottom:8px;">
                    <small style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Plan Subscribed</small>
                    <strong style="font-size:15px; color:#0f172a;"><?php echo Security::escape((string) $payment['plan_name']); ?></strong>
                </div>
                <div style="border-bottom:1px solid #f1f5f9; padding-bottom:8px;">
                    <small style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Payment Channel / Ref</small>
                    <strong style="font-size:14px; color:#0f172a;"><?php echo Security::escape((string) ($payment['method_name'] ?: $payment['method'])); ?></strong>
                    <?php if (!empty($payment['gateway_ref'])): ?>
                        <small style="display:block; font-family:monospace; color:#64748b; font-size:12px;"><?php echo Security::escape($payment['gateway_ref']); ?></small>
                    <?php endif; ?>
                </div>
                <div>
                    <small style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Submitted At</small>
                    <strong style="font-size:13.5px; color:#0f172a;"><?php echo date('d M, Y - h:i A', strtotime($payment['created_at'])); ?></strong>
                </div>
            </div>
        </div>

        <!-- Receipt Proof Preview Box -->
        <?php if (!empty($payment['receipt_screenshot'])): ?>
        <div class="card" style="padding:20px; border-radius:18px;">
            <h3 style="margin-bottom:12px; display:flex; align-items:center; gap:6px; font-size:15px;">
                <span class="material-symbols-rounded" style="color:#2563eb;">image</span> Payment Screenshot Proof
            </h3>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:10px; text-align:center;">
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/<?php echo htmlspecialchars(ltrim($payment['receipt_screenshot'], '/')); ?>" target="_blank" title="Click to open full screenshot">
                    <img src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/<?php echo htmlspecialchars(ltrim($payment['receipt_screenshot'], '/')); ?>" alt="Receipt Screenshot" style="max-width:100%; max-height:220px; object-fit:contain; border-radius:8px;">
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Tip -->
        <div class="card" style="padding:18px; border-radius:18px; background:linear-gradient(135deg,rgba(15,109,255,.04),rgba(16,185,129,.04)); border:1px solid rgba(15,109,255,.15);">
            <div style="display:flex; align-items:center; gap:8px; color:var(--primary); font-weight:800; font-size:13.5px; margin-bottom:8px;">
                <span class="material-symbols-rounded">verified</span> Automatic Approval Sync
            </div>
            <p style="font-size:12.5px; color:#475569; line-height:1.5; margin:0;">
                Status ko <strong>"Approved & Active"</strong> select karne par system automatically store ko activate kar dega aur user ke <strong>Payment History</strong> mein green verified badge show ho jaye ga.
            </p>
        </div>
    </aside>
</div>

<script>
function selectStatus(val, el, typeClass) {
    document.getElementById('selectedPaymentStatus').value = val;
    document.querySelectorAll('.status-option-card').forEach(card => {
        card.classList.remove('active', 'approved', 'pending', 'rejected');
    });
    el.classList.add('active', typeClass);
}

function onPlanChanged() {
    const planSelect = document.getElementById('editPlanSelect');
    if (!planSelect) return;
    const opt = planSelect.options[planSelect.selectedIndex];
    if (!opt) return;

    const monthly = parseFloat(opt.dataset.monthly || 0);
    const amountInput = document.getElementById('editAmountPaid');
    const installmentInput = document.getElementById('editInstallmentAmount');

    if (installmentInput && monthly > 0) {
        installmentInput.value = monthly;
    }
    if (amountInput && (parseFloat(amountInput.value) <= 0 || isNaN(parseFloat(amountInput.value)))) {
        amountInput.value = monthly;
    }
}

function onPaymentDateChanged() {
    const paidAtInput = document.getElementById('editPaidAt');
    const expiryInput = document.getElementById('editExpiryDate');
    const nextDateInput = document.getElementById('editNextChargeDate');
    if (!paidAtInput || !paidAtInput.value) return;

    const dt = new Date(paidAtInput.value);
    if (!isNaN(dt.getTime())) {
        const nextMonth = new Date(dt);
        nextMonth.setMonth(nextMonth.getMonth() + 1);
        const yyyy = nextMonth.getFullYear();
        const mm = String(nextMonth.getMonth() + 1).padStart(2, '0');
        const dd = String(nextMonth.getDate()).padStart(2, '0');
        const formatted = `${yyyy}-${mm}-${dd}`;

        if (expiryInput) expiryInput.value = formatted;
        if (nextDateInput) nextDateInput.value = formatted;
    }
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
