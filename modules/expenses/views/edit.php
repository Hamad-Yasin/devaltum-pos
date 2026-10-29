<?php ob_start(); ?>

<div class="page-header">
    <div>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/expenses/index" class="btn btn-ghost" style="margin-bottom:10px; display:inline-flex; align-items:center; gap:6px;">
            <span class="material-symbols-rounded">arrow_back</span> Back to Expenses
        </a>
        <h1>Edit Expense</h1>
        <p class="page-subtitle">Update the details of your expense.</p>
    </div>
</div>

<div class="card" style="max-width: 800px;">
    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/expenses/update/<?php echo (int) $expense['id']; ?>" method="POST" data-no-ajax>
        <?php echo Security::csrfField(); ?>
        <div class="form-group">
            <label class="form-label">Expense Title</label>
            <input class="form-input" type="text" name="title" value="<?php echo Security::escape($expense['title']); ?>" required>
        </div>
        <div class="split-grid">
            <div class="form-group">
                <label class="form-label">Select Category</label>
                <select class="form-select" name="category_id">
                    <option value="0">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int) $category['id']; ?>" <?php echo $expense['category_id'] == $category['id'] ? 'selected' : ''; ?>><?php echo Security::escape($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Custom Category (optional)</label>
                <input class="form-input" type="text" name="custom_category" value="<?php echo Security::escape($expense['custom_category']); ?>" placeholder="e.g. Courier">
            </div>
        </div>
        <div class="split-grid">
            <div class="form-group">
                <label class="form-label">Amount</label>
                <input class="form-input" type="number" step="0.01" min="0" name="amount" value="<?php echo Security::escape($expense['amount']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Payment Method</label>
                <select class="form-select" name="payment_method">
                    <option value="cash" <?php echo $expense['payment_method'] === 'cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="online" <?php echo $expense['payment_method'] === 'online' ? 'selected' : ''; ?>>Online</option>
                </select>
            </div>
        </div>
        <div class="split-grid">
            <div class="form-group">
                <label class="form-label">Expense Date</label>
                <?php $dateValue = $expense['expense_date'] ? date('Y-m-d', strtotime($expense['expense_date'])) : date('Y-m-d'); ?>
                <input class="form-input" type="date" name="expense_date" value="<?php echo Security::escape($dateValue); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <input class="form-input" type="text" name="notes" value="<?php echo Security::escape($expense['notes']); ?>" placeholder="Optional note">
            </div>
        </div>
        
        <div style="margin-top: 20px;">
            <button class="btn btn-primary" type="submit">Update Expense</button>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/expenses/index" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
