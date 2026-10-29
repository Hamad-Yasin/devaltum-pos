<?php ob_start(); ?>
<?php
$waTab = strtolower(trim((string) ($_GET['tab'] ?? 'compose')));
if (!in_array($waTab, ['compose', 'history'], true)) {
    $waTab = 'compose';
}
?>

<div class="page-header">
    <div>
        <h1>WhatsApp Center</h1>
        <p class="page-subtitle">Send customer reminders, custom messages, and track delivery log from one place.</p>
    </div>
</div>

<div class="tab-strip" style="margin-bottom:18px;">
    <a class="tab-pill <?php echo $waTab === 'compose' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/whatsapp/index?tab=compose">Compose Message</a>
    <a class="tab-pill <?php echo $waTab === 'history' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/whatsapp/index?tab=history">Sent History</a>
</div>

<?php if ($waTab === 'history'): ?>
<section class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Recipient</th>
                <th>Type</th>
                <th>Status</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($history)): ?>
                <tr><td colspan="6" class="empty-state">No WhatsApp messages found.</td></tr>
            <?php else: ?>
                <?php foreach ($history as $row): ?>
                    <tr>
                        <td><?php echo !empty($row['created_at']) ? date('Y-m-d H:i', strtotime((string) $row['created_at'])) : '-'; ?></td>
                        <td><?php echo Security::escape((string) ($row['customer_name'] ?: '-')); ?></td>
                        <td><?php echo Security::escape((string) $row['recipient_phone']); ?></td>
                        <td><span class="badge badge-soft"><?php echo Security::escape(ucfirst(str_replace('_', ' ', (string) $row['message_type']))); ?></span></td>
                        <td><span class="badge <?php echo (string) $row['status'] === 'sent' ? 'badge-success' : 'badge-danger'; ?>"><?php echo Security::escape(ucfirst((string) $row['status'])); ?></span></td>
                        <td style="max-width:380px; white-space:normal;"><?php echo nl2br(Security::escape((string) $row['message_body'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
<?php else: ?>
<div class="split-grid">
    <section class="card">
        <h3 style="margin-bottom:14px;">Send Message</h3>
        <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/whatsapp/send" method="POST" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <input type="hidden" name="invoice_id" id="wa_invoice_id" value="<?php echo (int) ($prefill['invoice_id'] ?? 0); ?>">

            <div class="form-group">
                <label class="form-label">Customer</label>
                <select class="form-select" id="wa_customer_id" name="customer_id">
                    <option value="0">Select Customer (optional)</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo (int) $customer['id']; ?>" data-phone="<?php echo Security::escape((string) ($customer['phone'] ?? '')); ?>" <?php echo (int) ($prefill['customer_id'] ?? 0) === (int) $customer['id'] ? 'selected' : ''; ?>>
                            <?php echo Security::escape((string) $customer['name']); ?> (<?php echo Security::escape((string) ($customer['phone'] ?? '-')); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Recipient WhatsApp Number</label>
                <input class="form-input" type="text" id="wa_recipient_phone" name="recipient_phone" value="<?php echo Security::escape((string) ($prefill['phone'] ?? '')); ?>" placeholder="+92XXXXXXXXXX" required>
            </div>

            <div class="form-group">
                <label class="form-label">Message Type</label>
                <select class="form-select" name="message_type" id="wa_message_type">
                    <option value="custom" <?php echo (string) ($prefill['message_type'] ?? 'custom') === 'custom' ? 'selected' : ''; ?>>Custom</option>
                    <option value="credit_due" <?php echo (string) ($prefill['message_type'] ?? '') === 'credit_due' ? 'selected' : ''; ?>>Credit Due Reminder</option>
                    <option value="follow_up" <?php echo (string) ($prefill['message_type'] ?? '') === 'follow_up' ? 'selected' : ''; ?>>Follow-up</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Message</label>
                <textarea class="form-textarea" name="message_body" id="wa_message_body" required><?php echo Security::escape((string) ($prefill['message'] ?? '')); ?></textarea>
                <div class="page-subtitle" id="wa_count_info" style="margin-top:6px;">0 / 2000 characters</div>
            </div>

            <button class="btn btn-primary" type="submit"><span class="material-symbols-rounded">send</span>Send WhatsApp Message</button>
        </form>
    </section>

    <section class="card">
        <h3 style="margin-bottom:14px;">Due Reminder Quick Send</h3>
        <p class="page-subtitle" style="margin-bottom:12px;">One click due reminder for pending credit invoices.</p>
        <div style="display:grid; gap:10px; max-height:460px; overflow:auto;">
            <?php if (empty($dueCustomers)): ?>
                <div class="empty-state" style="padding:14px;">No due invoices with customer phone available.</div>
            <?php else: ?>
                <?php foreach ($dueCustomers as $due): ?>
                    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/whatsapp/sendDueReminder/<?php echo (int) $due['invoice_id']; ?>" method="POST" data-no-ajax style="border:1px solid var(--border); border-radius:14px; padding:12px;">
                        <?php echo Security::csrfField(); ?>
                        <div style="display:flex; justify-content:space-between; gap:8px; align-items:flex-start;">
                            <div>
                                <strong><?php echo Security::escape((string) $due['customer_name']); ?></strong>
                                <div class="page-subtitle"><?php echo Security::escape((string) $due['customer_phone']); ?></div>
                                <div class="page-subtitle">Invoice: <?php echo Security::escape((string) $due['custom_id']); ?></div>
                                <div class="page-subtitle">Due: <?php echo App::money((float) $due['open_due']); ?></div>
                            </div>
                            <button class="btn btn-secondary" type="submit">Send</button>
                        </div>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php endif; ?>

<?php ob_start(); ?>
<script>
(() => {
    const customerSelect = document.getElementById('wa_customer_id');
    const phoneInput = document.getElementById('wa_recipient_phone');
    const messageType = document.getElementById('wa_message_type');
    const messageBody = document.getElementById('wa_message_body');
    const countInfo = document.getElementById('wa_count_info');
    if (!messageBody || !countInfo) return;

    const updateCount = () => {
        const len = messageBody.value.length;
        countInfo.textContent = `${len} / 2000 characters`;
    };

    customerSelect?.addEventListener('change', () => {
        const selected = customerSelect.options[customerSelect.selectedIndex];
        const phone = selected?.dataset?.phone || '';
        if (phone && phoneInput && phoneInput.value.trim() === '') {
            phoneInput.value = phone;
        }
    });

    messageType?.addEventListener('change', () => {
        if (!messageBody.value.trim() && messageType.value === 'follow_up') {
            messageBody.value = "Assalam o Alaikum,\n\nKindly update regarding your pending payment.\nShukriya.";
            updateCount();
        }
    });

    messageBody.addEventListener('input', updateCount);
    updateCount();
})();
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
