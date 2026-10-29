<?php ob_start(); ?>

<div class="page-header">
    <div>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/index" class="page-subtitle">Back to Customers</a>
        <h1>Edit Customer</h1>
        <p class="page-subtitle">Update customer profile and review purchase history.</p>
    </div>
</div>

<div class="split-grid">
    <section class="card">
        <h3 style="margin-bottom:18px;">Profile Details</h3>
        <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/edit/<?php echo $customer['id']; ?>" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <div class="form-group">
                <label class="form-label">Customer Name</label>
                <input class="form-input" type="text" name="name" value="<?php echo Security::escape($customer['name']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input class="form-input" type="text" name="phone" value="<?php echo Security::escape($customer['phone']); ?>" required>
            </div>
            <?php if (!empty($hasAddress)): ?>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <input class="form-input" type="text" name="address" value="<?php echo Security::escape($customer['address'] ?? ''); ?>">
                </div>
            <?php endif; ?>
            <div class="metric-card" style="padding:0 0 18px;">
                <small>Total Spending</small>
                <strong style="font-size:26px;"><?php echo App::money($customer['total_spending']); ?></strong>
            </div>
            <button class="btn btn-primary" type="submit">Save Changes</button>
        </form>
    </section>

    <section class="card table-wrap">
        <h3 style="margin-bottom:18px;">Purchase History</h3>
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
                    <tr><td colspan="5" class="empty-state">No purchases found for this customer.</td></tr>
                <?php else: ?>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><strong><?php echo Security::escape($invoice['custom_id']); ?></strong></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($invoice['created_at'])); ?></td>
                            <td>
                                <span class="badge <?php echo $invoice['status'] === 'paid' ? 'badge-success' : 'badge-soft'; ?>"><?php echo strtoupper(Security::escape($invoice['status'])); ?></span>
                                <?php if ((float) ($invoice['open_due'] ?? 0) > 0): ?>
                                    <div class="page-subtitle" style="margin-top:6px; color:var(--error);">Remaining: <?php echo App::money($invoice['open_due']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo App::money($invoice['total']); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="icon-btn" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/action/<?php echo $invoice['id']; ?>" title="Sale Profile"><span class="material-symbols-rounded">visibility</span></a>
                                    <a class="icon-btn" target="_blank" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/pos/receipt/<?php echo $invoice['id']; ?>"><span class="material-symbols-rounded">receipt_long</span></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>

