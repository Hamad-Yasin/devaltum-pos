<?php ob_start(); ?>

<?php
$summary = $summary ?? ['paid_amount' => 0, 'remaining_amount' => 0, 'invoice_total' => 0, 'order_count' => 0];
$remaining = (float) ($summary['remaining_amount'] ?? 0);
$companyName = App::setting('company_name', 'DevAltum POS');
$companyPhone = App::setting('whatsapp_number', '');
$creditTemplate = App::setting('credit_whatsapp_template', "Assalam o Alaikum {customer_name},\n\nBhai apki remaining amount {remaining} reh gayi hai. Barah-e-karam apni payment clear kar dein.\n\nThank you,\n{company_name}");
$customerWaNumber = preg_replace('/[^0-9]/', '', (string) ($customer['phone'] ?? ''));
if (strpos($customerWaNumber, '0') === 0) {
    $customerWaNumber = '92' . substr($customerWaNumber, 1);
}
$lastDueDate = '';
foreach ($invoices ?? [] as $invoiceForDue) {
    if ((float) ($invoiceForDue['open_due'] ?? 0) > 0 && !empty($invoiceForDue['credit_due_date'])) {
        $lastDueDate = date('M d, Y h:i A', strtotime($invoiceForDue['credit_due_date']));
        break;
    }
}
$customerWhatsappMessage = strtr($creditTemplate, [
    '{customer_name}' => (string) ($customer['name'] ?: 'Customer'),
    '{invoice_no}' => 'Overall Balance',
    '{total}' => App::money($summary['invoice_total'] ?? 0),
    '{paid}' => App::money($summary['paid_amount'] ?? 0),
    '{remaining}' => App::money($remaining),
    '{due_date}' => $lastDueDate !== '' ? $lastDueDate : 'Not set',
    '{company_name}' => $companyName,
    '{company_phone}' => $companyPhone
]);
$customerWhatsappUrl = ($remaining > 0 && $customerWaNumber !== '') ? 'https://wa.me/' . $customerWaNumber . '?text=' . urlencode($customerWhatsappMessage) : '';
?>

<style>
.customer-hero {
    border: 1px solid rgba(15, 109, 255, .2);
    background: linear-gradient(140deg, rgba(15, 109, 255, .08), rgba(16, 185, 129, .08), rgba(249, 115, 22, .08));
    border-radius: 18px;
    padding: 16px;
    margin-bottom: 18px;
}
.customer-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}
.customer-stat-card {
    border: 1px solid var(--border);
    border-radius: 14px;
    background: linear-gradient(160deg, #fff, #f8fafc);
    padding: 14px;
    transition: transform .2s ease, box-shadow .2s ease;
}
.customer-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 26px rgba(2, 6, 23, .08);
}
.customer-stat-card small {
    color: var(--text-muted);
    display: block;
    margin-bottom: 8px;
}
.customer-stat-card strong {
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
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/index" class="page-subtitle">Back to Customers</a>
        <h1><?php echo Security::escape($customer['name']); ?></h1>
        <p class="page-subtitle">Customer profile with payment progress and order history.</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <?php if ($customerWhatsappUrl !== ''): ?>
            <a class="btn btn-whatsapp" target="_blank" rel="noopener" href="<?php echo Security::escape($customerWhatsappUrl); ?>">
                <span class="whatsapp-icon" aria-hidden="true">
                    <svg viewBox="0 0 32 32" focusable="false"><path d="M16.04 3.2c-7.04 0-12.76 5.7-12.76 12.72 0 2.24.6 4.42 1.72 6.34L3.18 28.8l6.7-1.76a12.76 12.76 0 0 0 6.16 1.56c7.04 0 12.76-5.7 12.76-12.72S23.08 3.2 16.04 3.2Zm0 23.22c-1.9 0-3.76-.5-5.38-1.46l-.38-.22-3.98 1.04 1.06-3.86-.25-.4a10.42 10.42 0 0 1-1.6-5.6c0-5.8 4.72-10.52 10.54-10.52 5.8 0 10.54 4.72 10.54 10.52S21.84 26.42 16.04 26.42Zm5.78-7.88c-.32-.16-1.88-.92-2.16-1.02-.3-.12-.5-.16-.72.16-.2.32-.82 1.02-1 1.22-.18.22-.38.24-.7.08-.32-.16-1.34-.5-2.56-1.58a9.58 9.58 0 0 1-1.76-2.18c-.18-.32-.02-.5.14-.66.14-.14.32-.38.48-.56.16-.2.2-.32.32-.54.1-.22.06-.4-.02-.56-.08-.16-.72-1.72-.98-2.36-.26-.62-.52-.54-.72-.54h-.62c-.22 0-.56.08-.86.4-.3.32-1.12 1.1-1.12 2.68s1.14 3.1 1.3 3.32c.16.22 2.24 3.42 5.44 4.8.76.32 1.36.52 1.82.66.76.24 1.46.2 2 .12.62-.1 1.88-.76 2.14-1.5.26-.74.26-1.38.18-1.5-.08-.14-.3-.22-.62-.38Z"/></svg>
                </span>
                Send WhatsApp Message
            </a>
        <?php endif; ?>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/edit/<?php echo (int) $customer['id']; ?>" class="btn btn-secondary"><span class="material-symbols-rounded">edit</span>Edit Profile</a>
    </div>
</div>

<section class="customer-hero">
    <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:space-between; align-items:center;">
        <div>
            <strong style="font-size:19px;"><?php echo Security::escape($customer['phone'] ?: '-'); ?></strong>
            <?php if (!empty($hasAddress) && !empty($customer['address'])): ?>
                <div class="page-subtitle" style="margin-top:6px;"><?php echo Security::escape($customer['address']); ?></div>
            <?php endif; ?>
        </div>
        <span class="badge <?php echo $remaining > 0 ? 'badge-danger' : 'badge-success'; ?>">
            <?php echo $remaining > 0 ? 'Has Pending Dues' : 'All Settled'; ?>
        </span>
    </div>
    <?php if ($remaining > 0): ?>
        <div style="margin-top:14px; padding:14px; border-radius:14px; background:#fff; border:1px solid rgba(220,38,38,.18); display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <span class="page-subtitle" style="margin:0;">Overall remaining balance</span>
            <strong style="font-size:20px; color:var(--error);"><?php echo App::money($remaining); ?></strong>
        </div>
    <?php endif; ?>
</section>

<div class="customer-stats">
    <div class="customer-stat-card">
        <small>Total Orders</small>
        <strong><?php echo (int) ($summary['order_count'] ?? 0); ?></strong>
    </div>
    <div class="customer-stat-card">
        <small>Total Invoice Amount</small>
        <strong><?php echo App::money($summary['invoice_total'] ?? 0); ?></strong>
    </div>
    <div class="customer-stat-card">
        <small>Paid Amount</small>
        <strong style="color:var(--success);"><?php echo App::money($summary['paid_amount'] ?? 0); ?></strong>
    </div>
    <div class="customer-stat-card">
        <small>Remaining Amount</small>
        <strong style="color:var(--error);"><?php echo App::money($summary['remaining_amount'] ?? 0); ?></strong>
    </div>
</div>

<section class="card table-wrap">
    <h3 style="margin-bottom:16px;">Purchase History</h3>
    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="5" class="empty-state">No purchases recorded yet.</td></tr>
            <?php else: ?>
                <?php foreach ($invoices as $invoice): ?>
                    <?php
                        $invoiceOpenDue = (float) ($invoice['open_due'] ?? 0);
                        $statusText = (strtolower((string) ($invoice['payment_method'] ?? '')) === 'credit' && $invoiceOpenDue > 0)
                            ? 'UNPAID'
                            : strtoupper((string) ($invoice['status'] ?? 'paid'));
                    ?>
                    <tr>
                        <td><strong><?php echo Security::escape($invoice['custom_id']); ?></strong></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($invoice['created_at'])); ?></td>
                        <td>
                            <span class="badge <?php echo $invoiceOpenDue > 0 ? 'badge-danger' : 'badge-success'; ?>"><?php echo Security::escape($statusText); ?></span>
                            <?php if ($invoiceOpenDue > 0): ?>
                                <div class="page-subtitle" style="margin-top:6px; color:var(--error);">Remaining: <?php echo App::money($invoiceOpenDue); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo App::money($invoice['total']); ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="icon-btn" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/action/<?php echo (int) $invoice['id']; ?>" title="Sale Profile"><span class="material-symbols-rounded">visibility</span></a>
                                <a class="icon-btn" target="_blank" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/pos/receipt/<?php echo (int) $invoice['id']; ?>" title="Receipt"><span class="material-symbols-rounded">receipt_long</span></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
