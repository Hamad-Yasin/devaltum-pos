<?php ob_start(); ?>

<?php
$openDue = max(0, (float) ($sale['open_due'] ?? 0));
$isCredit = strtolower((string) ($sale['payment_method'] ?? '')) === 'credit' || $openDue > 0;
$rawStatus = strtolower(trim((string) ($sale['status'] ?? '')));
if (!in_array($rawStatus, ['paid', 'unpaid', 'draft'], true)) {
    $rawStatus = $openDue > 0 ? 'unpaid' : 'paid';
}
$displayStatus = $openDue > 0 ? 'UNPAID' : strtoupper($rawStatus);
$showProductImages = App::setting('show_product_images', '1') === '1';
?>

<style>
.sale-profile-hero {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(15, 109, 255, .22);
    background: linear-gradient(130deg, rgba(15, 109, 255, .12), rgba(16, 185, 129, .08), rgba(249, 115, 22, .12));
    border-radius: 20px;
    padding: 16px;
    margin-bottom: 20px;
    box-shadow: 0 14px 34px rgba(15, 23, 42, .07);
}
.sale-profile-hero::after {
    content: '';
    position: absolute;
    inset: auto -10% -35px -10%;
    height: 80px;
    background: radial-gradient(circle at 30% 20%, rgba(255,255,255,.5), rgba(255,255,255,0));
    pointer-events: none;
}
.sale-profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.sale-profile-card {
    position: relative;
    border: 1px solid rgba(148, 163, 184, .25);
    border-radius: 16px;
    background: linear-gradient(165deg, #ffffff 0%, #f8fafc 100%);
    padding: 16px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
}
.sale-profile-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 30px rgba(15, 23, 42, .09);
    border-color: rgba(15, 109, 255, .35);
}
.sale-profile-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 4px;
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
    background: linear-gradient(90deg, #0f6dff, #22c55e);
}
.sale-profile-card small {
    color: #64748b;
    display: block;
    margin-bottom: 8px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-size: 11px;
}
.sale-profile-card strong {
    font-size: 24px;
    line-height: 1.2;
}
.sale-card-customer strong {
    font-size: 19px;
}
.sale-card-neutral::before {
    background: linear-gradient(90deg, #0ea5e9, #3b82f6);
}
.sale-card-success::before {
    background: linear-gradient(90deg, #16a34a, #22c55e);
}
.sale-card-danger::before {
    background: linear-gradient(90deg, #dc2626, #f97316);
}
.sale-card-warning::before {
    background: linear-gradient(90deg, #f59e0b, #f97316);
}
.item-with-image {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 190px;
}
.item-thumb {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(148, 163, 184, .32);
    background: linear-gradient(135deg, #ffffff, #eef6ff);
    display: grid;
    place-items: center;
    color: var(--primary);
    box-shadow: 0 8px 18px rgba(15, 23, 42, .08);
}
.item-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
}
.item-thumb .material-symbols-rounded {
    font-size: 21px;
}
@media (max-width: 640px) {
    .sale-profile-card strong {
        font-size: 20px;
    }
}
</style>

<div class="page-header">
    <div>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/index" class="page-subtitle">Back to Sales</a>
        <h1>Sale Profile</h1>
        <p class="page-subtitle">Invoice <?php echo Security::escape($sale['custom_id']); ?> complete detail view.</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn btn-secondary" target="_blank" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/pos/receipt/<?php echo (int) $sale['id']; ?>"><span class="material-symbols-rounded">receipt_long</span>Open Receipt</a>
        <?php if ($openDue > 0): ?>
            <a class="btn btn-primary" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/credits/action/<?php echo (int) $sale['id']; ?>">
                <span class="material-symbols-rounded">payments</span>Pay Remaining (<?php echo App::money($openDue); ?>)
            </a>
        <?php endif; ?>
        <a class="btn btn-secondary" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/edit/<?php echo (int) $sale['id']; ?>"><span class="material-symbols-rounded">edit</span>Edit</a>
                <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/delete/<?php echo (int) $sale['id']; ?>" method="POST" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <button class="btn btn-danger" type="submit"><span class="material-symbols-rounded">delete_forever</span>Delete</button>
        </form>
    </div>
</div>

<div class="sale-profile-hero">
    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
        <div>
            <strong style="font-size:18px;"><?php echo Security::escape($sale['transaction_type']); ?> / <?php echo Security::escape($displayStatus); ?></strong>
            <div class="page-subtitle" style="margin-top:6px;">Created: <?php echo date('M d, Y h:i A', strtotime($sale['created_at'])); ?></div>
        </div>
        <span class="badge <?php echo $isCredit ? 'badge-danger' : 'badge-soft'; ?>"><?php echo ucfirst(Security::escape($sale['payment_method'] ?? 'cash')); ?></span>
    </div>
</div>

<div class="sale-profile-grid">
    <div class="sale-profile-card sale-card-neutral sale-card-customer">
        <small>Customer</small>
        <strong><?php echo Security::escape($sale['customer_display'] ?: 'Walk-in Customer'); ?></strong>
        <div class="page-subtitle" style="margin-top:6px;"><?php echo Security::escape($sale['customer_phone_display'] ?: '-'); ?></div>
        <?php if (!empty($sale['customer_address_snapshot'])): ?>
            <div class="page-subtitle" style="margin-top:6px;"><?php echo Security::escape($sale['customer_address_snapshot']); ?></div>
        <?php endif; ?>
    </div>
    <div class="sale-profile-card sale-card-neutral">
        <small>Payment Channel</small>
        <strong><?php echo ucfirst(Security::escape($sale['sale_channel'] ?? 'retail')); ?></strong>
        <?php if (!empty($sale['credit_due_date'])): ?>
            <div class="page-subtitle" style="margin-top:6px;">Due: <?php echo date('M d, Y h:i A', strtotime($sale['credit_due_date'])); ?></div>
        <?php endif; ?>
    </div>
    <div class="sale-profile-card sale-card-neutral">
        <small>Cashier</small>
        <strong><?php echo Security::escape($sale['cashier_name'] ?: 'System'); ?></strong>
    </div>
    <div class="sale-profile-card sale-card-neutral">
        <small>Total</small>
        <strong><?php echo App::money($sale['total']); ?></strong>
    </div>
    <div class="sale-profile-card sale-card-success">
        <small>Paid Amount</small>
        <strong style="color:var(--success);"><?php echo App::money($sale['paid_amount'] ?? 0); ?></strong>
    </div>
    <div class="sale-profile-card <?php echo $openDue > 0 ? 'sale-card-danger' : 'sale-card-success'; ?>">
        <small>Remaining Amount</small>
        <strong style="color:<?php echo $openDue > 0 ? 'var(--error)' : 'var(--success)'; ?>;"><?php echo App::money($openDue); ?></strong>
    </div>
    <div class="sale-profile-card sale-card-neutral">
        <small>Subtotal</small>
        <strong><?php echo App::money($sale['subtotal']); ?></strong>
    </div>
    <div class="sale-profile-card sale-card-warning">
        <small>Discount</small>
        <strong style="color:var(--error);"><?php echo App::money($sale['discount']); ?></strong>
    </div>
</div>

<section class="card table-wrap" style="margin-bottom:18px;">
    <h3 style="margin-bottom:14px;">Items</h3>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Unit</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Discount</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="6" class="empty-state">No items found for this invoice.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="item-with-image">
                                <?php if ($showProductImages): ?>
                                    <span class="item-thumb">
                                        <?php if (!empty($item['product_image_url'])): ?>
                                            <img src="<?php echo Security::escape($item['product_image_url']); ?>" alt="<?php echo Security::escape($item['product_name']); ?>">
                                        <?php else: ?>
                                            <span class="material-symbols-rounded">inventory_2</span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                <strong><?php echo Security::escape($item['product_name']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo Security::escape($item['product_unit_label'] ?? App::formatProductUnit(1, 'pcs')); ?></td>
                        <td><?php echo Security::escape(App::formatQuantity($item['quantity'])); ?></td>
                        <td><?php echo App::money($item['price']); ?></td>
                        <td><?php echo App::money($item['discount']); ?></td>
                        <td><strong><?php echo App::money($item['total']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php if (!empty($sale['credit_private_note']) || !empty($sale['notes'])): ?>
<section class="card">
    <h3 style="margin-bottom:14px;">Additional Notes</h3>
    <?php if (!empty($sale['notes'])): ?>
        <div style="margin-bottom:10px;"><strong>Note:</strong> <?php echo Security::escape($sale['notes']); ?></div>
    <?php endif; ?>
    <?php if (!empty($sale['credit_private_note'])): ?>
        <div><strong>Private Credit Message:</strong> <?php echo Security::escape($sale['credit_private_note']); ?></div>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
