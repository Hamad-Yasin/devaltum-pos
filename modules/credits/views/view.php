<?php ob_start(); ?>

<?php
$openDue = max(0, (float) ($invoice['open_due'] ?? 0));
$isSettled = $openDue <= 0;
$companyName = App::setting('company_name', 'DevAltum POS');
$companyPhone = App::setting('whatsapp_number', '');
$creditTemplate = App::setting('credit_whatsapp_template', "Assalam o Alaikum {customer_name},\n\nBhai apki remaining amount {remaining} reh gayi hai. Barah-e-karam apni payment clear kar dein.\n\nThank you,\n{company_name}");
$customerPhoneRaw = (string) ($invoice['customer_phone'] ?? '');
$customerWaNumber = preg_replace('/[^0-9]/', '', $customerPhoneRaw);
if (strpos($customerWaNumber, '0') === 0) {
    $customerWaNumber = '92' . substr($customerWaNumber, 1);
}
$dueDateText = !empty($invoice['credit_due_date']) ? date('M d, Y h:i A', strtotime($invoice['credit_due_date'])) : 'Not set';
$creditWhatsappMessage = strtr($creditTemplate, [
    '{customer_name}' => (string) ($invoice['customer_name'] ?: 'Customer'),
    '{invoice_no}' => (string) ($invoice['custom_id'] ?? '-'),
    '{total}' => App::money($invoice['total'] ?? 0),
    '{paid}' => App::money($invoice['paid_amount'] ?? 0),
    '{remaining}' => App::money($openDue),
    '{due_date}' => $dueDateText,
    '{company_name}' => $companyName,
    '{company_phone}' => $companyPhone
]);
$creditWhatsappUrl = $customerWaNumber !== '' ? 'https://wa.me/' . $customerWaNumber . '?text=' . urlencode($creditWhatsappMessage) : '';
?>

<style>
.credit-profile-hero {
    border: 1px solid rgba(15, 109, 255, .2);
    background: linear-gradient(140deg, rgba(15, 109, 255, .08), rgba(16, 185, 129, .08), rgba(249, 115, 22, .08));
    border-radius: 18px;
    padding: 16px;
    margin-bottom: 18px;
}
.credit-profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}
.credit-profile-card {
    border: 1px solid var(--border);
    border-radius: 14px;
    background: linear-gradient(160deg, #fff, #f8fafc);
    padding: 14px;
}
.credit-profile-card small {
    color: var(--text-muted);
    display: block;
    margin-bottom: 8px;
}
.credit-profile-card strong {
    font-size: 24px;
}
.btn-whatsapp {
    background: linear-gradient(135deg, #16a34a, #22c55e);
    color: #fff;
    border: 1px solid rgba(22, 163, 74, .45);
    box-shadow: 0 14px 28px rgba(22, 163, 74, .22);
}
.btn-whatsapp:hover {
    box-shadow: 0 18px 34px rgba(22, 163, 74, .28);
}
.whatsapp-icon {
    width: 19px;
    height: 19px;
    display: inline-block;
}
.whatsapp-icon svg {
    width: 100%;
    height: 100%;
    display: block;
    fill: currentColor;
}
</style>

<div class="page-header">
    <div>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/credits/index" class="page-subtitle">Back to Credit Dues</a>
        <h1>Credit Invoice Profile</h1>
        <p class="page-subtitle">Invoice <?php echo Security::escape($invoice['custom_id']); ?> payment tracking and collection panel.</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn btn-secondary" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/action/<?php echo (int) $invoice['id']; ?>"><span class="material-symbols-rounded">receipt_long</span>Sale Profile</a>
        <a class="btn btn-secondary" target="_blank" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/pos/receipt/<?php echo (int) $invoice['id']; ?>"><span class="material-symbols-rounded">print</span>Receipt</a>
        <?php if ($creditWhatsappUrl !== ''): ?>
            <a class="btn btn-whatsapp" target="_blank" rel="noopener" href="<?php echo Security::escape($creditWhatsappUrl); ?>">
                <span class="whatsapp-icon" aria-hidden="true">
                    <svg viewBox="0 0 32 32" focusable="false"><path d="M16.04 3.2c-7.04 0-12.76 5.7-12.76 12.72 0 2.24.6 4.42 1.72 6.34L3.18 28.8l6.7-1.76a12.76 12.76 0 0 0 6.16 1.56c7.04 0 12.76-5.7 12.76-12.72S23.08 3.2 16.04 3.2Zm0 23.22c-1.9 0-3.76-.5-5.38-1.46l-.38-.22-3.98 1.04 1.06-3.86-.25-.4a10.42 10.42 0 0 1-1.6-5.6c0-5.8 4.72-10.52 10.54-10.52 5.8 0 10.54 4.72 10.54 10.52S21.84 26.42 16.04 26.42Zm5.78-7.88c-.32-.16-1.88-.92-2.16-1.02-.3-.12-.5-.16-.72.16-.2.32-.82 1.02-1 1.22-.18.22-.38.24-.7.08-.32-.16-1.34-.5-2.56-1.58a9.58 9.58 0 0 1-1.76-2.18c-.18-.32-.02-.5.14-.66.14-.14.32-.38.48-.56.16-.2.2-.32.32-.54.1-.22.06-.4-.02-.56-.08-.16-.72-1.72-.98-2.36-.26-.62-.52-.54-.72-.54h-.62c-.22 0-.56.08-.86.4-.3.32-1.12 1.1-1.12 2.68s1.14 3.1 1.3 3.32c.16.22 2.24 3.42 5.44 4.8.76.32 1.36.52 1.82.66.76.24 1.46.2 2 .12.62-.1 1.88-.76 2.14-1.5.26-.74.26-1.38.18-1.5-.08-.14-.3-.22-.62-.38Z"/></svg>
                </span>
                Send WhatsApp Message
            </a>
        <?php else: ?>
            <button class="btn btn-whatsapp" type="button" disabled>
                <span class="whatsapp-icon" aria-hidden="true">
                    <svg viewBox="0 0 32 32" focusable="false"><path d="M16.04 3.2c-7.04 0-12.76 5.7-12.76 12.72 0 2.24.6 4.42 1.72 6.34L3.18 28.8l6.7-1.76a12.76 12.76 0 0 0 6.16 1.56c7.04 0 12.76-5.7 12.76-12.72S23.08 3.2 16.04 3.2Z"/></svg>
                </span>
                No WhatsApp Number
            </button>
        <?php endif; ?>
    </div>
</div>

<section class="credit-profile-hero">
    <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:space-between; align-items:center;">
        <div>
            <strong style="font-size:19px;"><?php echo Security::escape($invoice['customer_name']); ?></strong>
            <div class="page-subtitle" style="margin-top:6px;"><?php echo Security::escape($invoice['customer_phone'] ?: '-'); ?></div>
        </div>
        <span class="badge <?php echo $isSettled ? 'badge-success' : 'badge-danger'; ?>"><?php echo $isSettled ? 'Settled' : 'Unpaid'; ?></span>
    </div>
</section>

<div class="credit-profile-grid">
    <div class="credit-profile-card">
        <small>Total Amount</small>
        <strong><?php echo App::money($invoice['total']); ?></strong>
    </div>
    <div class="credit-profile-card">
        <small>Paid Amount</small>
        <strong style="color:var(--success);"><?php echo App::money($invoice['paid_amount']); ?></strong>
    </div>
    <div class="credit-profile-card">
        <small>Remaining Amount</small>
        <strong style="color:var(--error);"><?php echo App::money($openDue); ?></strong>
    </div>
    <div class="credit-profile-card">
        <small>Current Due Date</small>
        <strong style="font-size:18px;"><?php echo !empty($invoice['credit_due_date']) ? date('M d, Y h:i A', strtotime($invoice['credit_due_date'])) : '-'; ?></strong>
    </div>
</div>

<div class="split-grid">
    <section class="card">
        <h3 style="margin-bottom:14px;">Collect Credit Payment</h3>
        <?php if ($isSettled): ?>
            <div class="app-alert alert-success" style="margin-bottom:0;">This invoice is fully settled. No remaining dues.</div>
        <?php else: ?>
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/credits/collect/<?php echo (int) $invoice['id']; ?>" method="POST" data-no-ajax>
                <?php echo Security::csrfField(); ?>
                <div class="form-group">
                    <label class="form-label">Amount Received</label>
                    <input class="form-input" type="number" min="0.01" max="<?php echo (float) $openDue; ?>" step="0.01" name="amount" required data-credit-amount data-credit-total="<?php echo (float) $openDue; ?>">
                    <div class="page-subtitle" data-credit-preview>Remaining after payment: <?php echo App::money($openDue); ?></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <select class="form-select" name="payment_method">
                        <option value="cash">Cash</option>
                        <option value="online">Online</option>
                    </select>
                </div>
                <div class="split-grid">
                    <div class="form-group">
                        <label class="form-label">Next Due Date</label>
                        <input class="form-input" type="datetime-local" name="next_due_date">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Private Message</label>
                        <input class="form-input" type="text" name="private_message" placeholder="Private reminder">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Note</label>
                    <input class="form-input" type="text" name="note" placeholder="Optional note">
                </div>
                <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">payments</span>Save Payment</button>
            </form>
        <?php endif; ?>
    </section>

    <section class="card table-wrap">
        <h3 style="margin-bottom:14px;">Credit Payment History</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Next Due</th>
                    <th style="text-align:right;">Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="5" class="empty-state">No credit payments recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo date('M d, Y h:i A', strtotime($payment['created_at'])); ?></td>
                            <td><?php echo App::money($payment['amount']); ?></td>
                            <td><?php echo ucfirst(Security::escape($payment['payment_method'])); ?></td>
                            <td><?php echo $payment['next_due_date'] ? date('M d, Y h:i A', strtotime($payment['next_due_date'])) : '-'; ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="icon-btn" target="_blank" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/credits/receipt/<?php echo (int) $payment['id']; ?>" title="Open receipt">
                                        <span class="material-symbols-rounded">visibility</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>

<?php ob_start(); ?>
<script>
    (() => {
        const amountInput = document.querySelector('[data-credit-amount]');
        if (!amountInput) return;

        const update = () => {
            const total = Number(amountInput.dataset.creditTotal || '0');
            const paid = Math.max(0, Number(amountInput.value || '0'));
            const remaining = Math.max(0, total - paid);
            const preview = document.querySelector('[data-credit-preview]');
            if (preview && typeof window.appFormatMoney === 'function') {
                preview.textContent = `Remaining after payment: ${window.appFormatMoney(remaining)}`;
            }
        };

        amountInput.addEventListener('input', update);
        update();
    })();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
