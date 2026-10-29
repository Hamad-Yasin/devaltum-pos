<?php ob_start(); ?>
<?php
$methodsTab = strtolower(trim((string) ($_GET['tab'] ?? 'manual_form')));
$allowedTabs = ['manual_form', 'manual_list', 'whatsapp', 'gateway_settings', 'gateway_form', 'gateway_list'];
if (!in_array($methodsTab, $allowedTabs, true)) {
    $methodsTab = 'manual_form';
}

$logoUrls = [
    'easypaisa' => '/assets/img/payment/easypaisa.png',
    'jazzcash'  => '/assets/img/payment/jazzcash.png',
    'nayapay'   => '/assets/img/payment/nayapay.png',
    'meezan'    => '/assets/img/payment/meezan.webp',
    'bank'      => '/assets/img/bank.svg',
];
?>

<!-- Quill Rich Text Editor Assets -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

<style>
    .rich-editor-wrapper {
        border: 1px solid var(--border); border-radius: 8px; background: #fff; overflow: hidden;
    }
    .ql-toolbar.ql-snow { border: none !important; border-bottom: 1px solid var(--border) !important; background: #f8fafc; }
    .ql-container.ql-snow { border: none !important; min-height: 150px; font-family: inherit; font-size: 14px; }
    .method-logo-img {
        height: 32px; width: auto; max-width: 90px; vertical-align: middle; object-fit: contain;
        border-radius: 6px; background: #fff; padding: 3px 6px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.10); border: 1px solid #e2e8f0;
    }
    .logo-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:6px; background:#f1f5f9; font-size:12px; font-weight:600; color:#94a3b8; }
    .logo-picker-wrap { display:flex; align-items:center; gap:10px; }
    .logo-picker-preview { height:32px; width:70px; object-fit:contain; background:#fff; border-radius:6px; box-shadow:0 1px 4px rgba(0,0,0,.10); border:1px solid #e2e8f0; padding:3px 6px; display:none; }
    .logo-picker-preview.visible { display:block; }

    /* ── QR Code Drag & Drop ───────────────── */
    .qr-upload-zone {
        border: 2px dashed #cbd5e1;
        border-radius: 14px;
        background: #f8fafc;
        padding: 22px 16px;
        text-align: center;
        cursor: pointer;
        transition: border-color .2s, background .2s;
        position: relative;
    }
    .qr-upload-zone.drag-over {
        border-color: var(--primary);
        background: rgba(15,109,255,.05);
    }
    .qr-upload-zone input[type=file] {
        position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .qr-upload-icon { font-size: 36px; color: #94a3b8; display: block; margin-bottom: 6px; }
    .qr-upload-label { font-size: 13px; font-weight: 700; color: #64748b; display: block; margin-bottom: 3px; }
    .qr-upload-hint  { font-size: 11px; color: #94a3b8; display: block; }
    .qr-preview-wrap {
        display: none; margin-top: 12px; text-align: center; position: relative;
    }
    .qr-preview-wrap.visible { display: block; }
    .qr-preview-img {
        max-width: 140px; max-height: 140px; border-radius: 12px;
        border: 3px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,.1);
        object-fit: contain; background: #fff;
    }
    .qr-remove-btn {
        position: absolute; top: -6px; right: calc(50% - 76px);
        background: #ef4444; color: #fff; border: none; border-radius: 50%;
        width: 22px; height: 22px; cursor: pointer; font-size: 13px; font-weight: 700;
        display: grid; place-items: center; line-height: 1;
    }
    .qr-existing { display:flex; align-items:center; gap:12px; padding:10px 14px; background:#f1f5f9; border-radius:10px; margin-bottom:10px; }
    .qr-existing img { width:56px; height:56px; object-fit:contain; border-radius:8px; border:1px solid #e2e8f0; background:#fff; }
    .qr-existing-info { flex:1; font-size:12px; color:#64748b; font-weight:600; }
    .qr-existing-info strong { display:block; font-size:13px; color:#0f172a; margin-bottom:2px; }
</style>

<div class="page-header">
    <div>
        <h1>Payment Methods</h1>
        <p class="page-subtitle">Configure manual methods, gateway options, and WhatsApp reminders.</p>
    </div>
</div>

<div class="tab-strip" style="margin-bottom:18px;">
    <a class="tab-pill <?php echo $methodsTab === 'manual_form' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/paymentMethods?tab=manual_form">Manual Form</a>
    <a class="tab-pill <?php echo $methodsTab === 'manual_list' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/paymentMethods?tab=manual_list">Manual List</a>
    <a class="tab-pill <?php echo $methodsTab === 'whatsapp' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/paymentMethods?tab=whatsapp">WhatsApp Reminder</a>
    <a class="tab-pill <?php echo $methodsTab === 'gateway_settings' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/paymentMethods?tab=gateway_settings">Gateway Settings</a>
    <a class="tab-pill <?php echo $methodsTab === 'gateway_form' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/paymentMethods?tab=gateway_form">Gateway Form</a>
    <a class="tab-pill <?php echo $methodsTab === 'gateway_list' ? 'active' : ''; ?>" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/paymentMethods?tab=gateway_list">Gateway List</a>
</div>

<?php if ($methodsTab === 'manual_list'): ?>
<section class="card table-wrap">
    <table>
        <thead><tr><th>Logo</th><th>Method Name</th><th>Account Holder</th><th>Account Number</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if (empty($manualMethods)): ?>
                <tr><td colspan="6" class="empty-state">No manual methods configured.</td></tr>
            <?php else: ?>
                <?php foreach ($manualMethods as $method): ?>
                    <tr>
                        <td>
                            <?php if (!empty($method['method_logo']) && isset($logoUrls[$method['method_logo']])): ?>
                                <img src="<?= defined('BASE_URL') ? BASE_URL : '' ?><?php echo $logoUrls[$method['method_logo']]; ?>" class="method-logo-img" alt="<?php echo Security::escape($method['method_logo']); ?>">
                            <?php else: ?>
                                <span class="logo-badge">None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo Security::escape($method['method_name']); ?></strong>
                            <div class="page-subtitle" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo strip_tags((string) $method['instructions']); ?>
                            </div>
                        </td>
                        <td><?php echo Security::escape((string) ($method['account_holder'] ?? '-')); ?></td>
                        <td><?php echo Security::escape((string) ($method['account_number'] ?? '-')); ?></td>
                        <td><span class="badge <?php echo $method['status'] === 'active' ? 'badge-success' : 'badge-soft'; ?>"><?php echo Security::escape(ucfirst($method['status'])); ?></span></td>
                        <td>
                            <div class="table-actions">
                                <button class="icon-btn" type="button" data-open-modal="editMethodModal-<?php echo (int) $method['id']; ?>"><span class="material-symbols-rounded">edit</span></button>
                                <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/deletePaymentMethod/<?php echo (int) $method['id']; ?>" method="POST" data-no-ajax onsubmit="return confirm('Delete this method?');">
                                    <?php echo Security::csrfField(); ?>
                                    <button class="icon-btn" type="submit"><span class="material-symbols-rounded">delete</span></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
<?php elseif ($methodsTab === 'whatsapp'): ?>
<section class="card">
    <h3 style="margin-bottom:14px;">WhatsApp Reminder System</h3>
    <p class="page-subtitle" style="margin-bottom:14px;">Reminders are automated for 7 days before expiry, 3 days before expiry, and on the expiry date.</p>
    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/saveWhatsappSettings" method="POST" data-no-ajax>
        <?php echo Security::csrfField(); ?>
        <div class="split-grid">
            <div class="form-group"><label class="form-label">Provider</label><select class="form-select" name="whatsapp_provider"><option value="none" <?php echo ($settings['whatsapp_provider'] ?? 'none') === 'none' ? 'selected' : ''; ?>>Disabled</option><option value="meta" <?php echo ($settings['whatsapp_provider'] ?? '') === 'meta' ? 'selected' : ''; ?>>Meta Cloud API</option></select></div>
            <div class="form-group"><label class="form-label">Meta Access Token</label><input class="form-input" type="text" name="meta_access_token" value="<?php echo Security::escape($settings['meta_access_token'] ?? ''); ?>"></div>
            <div class="form-group"><label class="form-label">Meta Phone Number ID</label><input class="form-input" type="text" name="meta_phone_number_id" value="<?php echo Security::escape($settings['meta_phone_number_id'] ?? ''); ?>"></div>
        </div>
        <button class="btn btn-primary" type="submit">Save WhatsApp Settings</button>
    </form>
</section>
<?php elseif ($methodsTab === 'gateway_settings'): ?>
<section class="card">
    <h3 style="margin-bottom:14px;">Gateway Credentials (Optional)</h3>
    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/saveGatewaySettings" method="POST" data-no-ajax>
        <?php echo Security::csrfField(); ?>
        <div class="split-grid">
            <div class="form-group"><label class="form-label">Stripe Public Key</label><input class="form-input" type="text" name="stripe_public_key" value="<?php echo Security::escape((string) ($settings['stripe_public_key'] ?? '')); ?>"></div>
            <div class="form-group"><label class="form-label">Stripe Secret Key</label><input class="form-input" type="text" name="stripe_secret_key" value="<?php echo Security::escape((string) ($settings['stripe_secret_key'] ?? '')); ?>"></div>
            <div class="form-group"><label class="form-label">PayPal Client ID</label><input class="form-input" type="text" name="paypal_client_id" value="<?php echo Security::escape((string) ($settings['paypal_client_id'] ?? '')); ?>"></div>
            <div class="form-group"><label class="form-label">PayPal Secret</label><input class="form-input" type="text" name="paypal_secret" value="<?php echo Security::escape((string) ($settings['paypal_secret'] ?? '')); ?>"></div>
        </div>
        <button class="btn btn-primary" type="submit">Save Gateway Settings</button>
    </form>
</section>
<?php elseif ($methodsTab === 'gateway_list'): ?>
<section class="card table-wrap">
    <table>
        <thead><tr><th>Name</th><th>Type</th><th>Account / Ref</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if (empty($gatewayMethods)): ?>
                <tr><td colspan="5" class="empty-state">No gateway methods configured.</td></tr>
            <?php else: ?>
                <?php foreach ($gatewayMethods as $method): ?>
                    <tr>
                        <td><strong><?php echo Security::escape($method['method_name']); ?></strong><div class="page-subtitle"><?php echo Security::escape((string) ($method['instructions'] ?? '')); ?></div></td>
                        <td><?php echo Security::escape(ucfirst((string) $method['method_type'])); ?></td>
                        <td><?php echo Security::escape((string) ($method['account_number'] ?: '-')); ?></td>
                        <td><span class="badge <?php echo $method['status'] === 'active' ? 'badge-success' : 'badge-soft'; ?>"><?php echo Security::escape(ucfirst((string) $method['status'])); ?></span></td>
                        <td>
                            <div class="table-actions">
                                <button class="icon-btn" type="button" data-open-modal="editMethodModal-<?php echo (int) $method['id']; ?>"><span class="material-symbols-rounded">edit</span></button>
                                <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/deletePaymentMethod/<?php echo (int) $method['id']; ?>" method="POST" data-no-ajax onsubmit="return confirm('Delete this method?');">
                                    <?php echo Security::csrfField(); ?>
                                    <button class="icon-btn" type="submit"><span class="material-symbols-rounded">delete</span></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
<?php elseif ($methodsTab === 'gateway_form'): ?>
<section class="card">
    <h3 style="margin-bottom:14px;">Add / Edit Gateway Method</h3>
    <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/savePaymentMethod" method="POST" data-no-ajax>
        <?php echo Security::csrfField(); ?>
        <input type="hidden" name="method_type" value="gateway">
        <div class="split-grid">
            <div class="form-group"><label class="form-label">Method Name</label><input class="form-input" type="text" name="method_name" placeholder="Stripe / PayPal" required></div>
            <div class="form-group"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            <div class="form-group"><label class="form-label">Account / Key Reference</label><input class="form-input" type="text" name="account_number"></div>
            <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Configuration Notes</label><textarea class="form-input" name="instructions" rows="3"></textarea></div>
        </div>
        <button class="btn btn-primary" type="submit">Save Gateway Method</button>
    </form>
</section>
<?php else: ?>
<section class="card">
    <h3 style="margin-bottom:14px;">Add Manual Payment Method</h3>
    <form id="add-manual-method-form" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/savePaymentMethod" method="POST" enctype="multipart/form-data" data-no-ajax>
        <?php echo Security::csrfField(); ?>
        <input type="hidden" name="method_type" value="manual">
        <input type="hidden" name="instructions" id="instructions-input">
        <div class="split-grid">
            <div class="form-group"><label class="form-label">Method Name</label><input class="form-input" type="text" name="method_name" placeholder="e.g. Bank Alfalah / EasyPaisa Store" required></div>
            <div class="form-group"><label class="form-label">Account Holder Name</label><input class="form-input" type="text" name="account_holder" placeholder="e.g. John Doe"></div>
            <div class="form-group"><label class="form-label">Account Number / IBAN</label><input class="form-input" type="text" name="account_number" placeholder="e.g. 03001234567 / PK12ALFH..."></div>
            <div class="form-group"><label class="form-label">Official Logo Icon</label>
                <div class="logo-picker-wrap">
                    <select class="form-select logo-picker-select" name="method_logo" id="logo-picker-add" onchange="previewLogo(this,'logo-preview-add')" style="flex:1;">
                        <option value="">None / Custom</option>
                        <option value="easypaisa">EasyPaisa</option>
                        <option value="jazzcash">JazzCash</option>
                        <option value="nayapay">NayaPay</option>
                        <option value="meezan">Meezan Bank</option>
                        <option value="bank">Other Bank</option>
                    </select>
                    <img id="logo-preview-add" class="logo-picker-preview" src="" alt="Logo preview">
                </div>
            </div>
            <div class="form-group"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>

            <!-- QR Code Upload -->
            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">QR Code Image <span style="font-weight:500;color:#94a3b8;">(optional — drag &amp; drop or click)</span></label>
                <div class="qr-upload-zone" id="qr-zone-add"
                     ondragover="qrDragOver(event,'qr-zone-add')" ondragleave="qrDragLeave('qr-zone-add')" ondrop="qrDrop(event,'qr-zone-add','qr-preview-add','qr-file-add')">
                    <input type="file" name="qr_code_file" id="qr-file-add" accept="image/*" onchange="qrFileChange(this,'qr-preview-add')">
                    <span class="material-symbols-rounded qr-upload-icon">qr_code_2</span>
                    <span class="qr-upload-label">Drag &amp; drop QR code here</span>
                    <span class="qr-upload-hint">or click to browse &nbsp;·&nbsp; PNG, JPG, WEBP up to 2 MB</span>
                </div>
                <div class="qr-preview-wrap" id="qr-preview-add">
                    <img class="qr-preview-img" src="" alt="QR Preview">
                    <button type="button" class="qr-remove-btn" onclick="qrRemove('qr-preview-add','qr-file-add')" title="Remove">✕</button>
                </div>
            </div>

            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Payment Instructions</label>
                <div class="rich-editor-wrapper">
                    <div id="quill-main-editor"></div>
                </div>
            </div>
        </div>
        <button class="btn btn-primary" type="submit" style="margin-top: 15px;">Save Method</button>
    </form>
</section>
<?php endif; ?>

<?php foreach ($methods as $method): ?>
<div id="editMethodModal-<?php echo (int) $method['id']; ?>" class="modal-backdrop" style="display:none;">
    <div class="modal-card" style="width:min(900px, 100%); max-height:90vh; overflow-y:auto;">
        <div class="modal-header"><h3>Edit Payment Method</h3><button class="icon-btn" type="button" data-close-modal-target="editMethodModal-<?php echo (int) $method['id']; ?>"><span class="material-symbols-rounded">close</span></button></div>
        <form class="edit-method-form" data-id="<?php echo (int) $method['id']; ?>" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/savePaymentMethod" method="POST" enctype="multipart/form-data" data-no-ajax>
            <?php echo Security::csrfField(); ?>
            <input type="hidden" name="id" value="<?php echo (int) $method['id']; ?>">
            <input type="hidden" name="method_type" value="<?php echo Security::escape($method['method_type']); ?>">
            <input type="hidden" name="instructions" id="instructions-input-<?php echo (int) $method['id']; ?>" value="<?php echo Security::escape((string) $method['instructions']); ?>">
            <div class="split-grid">
                <div class="form-group"><label class="form-label">Name</label><input class="form-input" type="text" name="method_name" value="<?php echo Security::escape($method['method_name']); ?>" required></div>
                <?php if ($method['method_type'] === 'manual'): ?>
                    <div class="form-group"><label class="form-label">Account Holder Name</label><input class="form-input" type="text" name="account_holder" value="<?php echo Security::escape((string) ($method['account_holder'] ?? '')); ?>"></div>
                <?php endif; ?>
                <div class="form-group"><label class="form-label">Account Number</label><input class="form-input" type="text" name="account_number" value="<?php echo Security::escape((string) $method['account_number']); ?>"></div>
                <?php if ($method['method_type'] === 'manual'): ?>
                    <div class="form-group"><label class="form-label">Official Logo Icon</label>
                        <div class="logo-picker-wrap">
                            <select class="form-select logo-picker-select" name="method_logo" id="logo-picker-<?php echo (int)$method['id']; ?>" onchange="previewLogo(this,'logo-preview-<?php echo (int)$method['id']; ?>')" style="flex:1;">
                                <option value="" <?php echo empty($method['method_logo']) ? 'selected' : ''; ?>>None / Custom</option>
                                <option value="easypaisa" <?php echo ($method['method_logo'] ?? '') === 'easypaisa' ? 'selected' : ''; ?>>EasyPaisa</option>
                                <option value="jazzcash" <?php echo ($method['method_logo'] ?? '') === 'jazzcash' ? 'selected' : ''; ?>>JazzCash</option>
                                <option value="nayapay" <?php echo ($method['method_logo'] ?? '') === 'nayapay' ? 'selected' : ''; ?>>NayaPay</option>
                                <option value="meezan" <?php echo ($method['method_logo'] ?? '') === 'meezan' ? 'selected' : ''; ?>>Meezan Bank</option>
                                <option value="bank" <?php echo ($method['method_logo'] ?? '') === 'bank' ? 'selected' : ''; ?>>Other Bank</option>
                            </select>
                            <img id="logo-preview-<?php echo (int)$method['id']; ?>" class="logo-picker-preview <?php echo !empty($method['method_logo']) ? 'visible' : ''; ?>" src="<?php echo !empty($method['method_logo']) && isset($logoUrls[$method['method_logo']]) ? (defined('BASE_URL') ? BASE_URL : '') . $logoUrls[$method['method_logo']] : ''; ?>" alt="Logo preview">
                        </div>
                    </div>
                <?php endif; ?>
                <div class="form-group"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" <?php echo $method['status'] === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo $method['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option></select></div>

                <?php if ($method['method_type'] === 'manual'): ?>
                <!-- QR Code Upload -->
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">QR Code Image <span style="font-weight:500;color:#94a3b8;">(drag &amp; drop or click to replace)</span></label>
                    <?php if (!empty($method['qr_code'])): ?>
                    <div class="qr-existing">
                        <img src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/uploads/qr_codes/<?php echo Security::escape($method['qr_code']); ?>" alt="Current QR">
                        <div class="qr-existing-info">
                            <strong>Current QR Code</strong>
                            Upload a new image below to replace it.
                        </div>
                        <label style="cursor:pointer;">
                            <input type="checkbox" name="remove_qr" value="1"> Remove QR
                        </label>
                    </div>
                    <?php endif; ?>
                    <div class="qr-upload-zone" id="qr-zone-<?php echo (int)$method['id']; ?>"
                         ondragover="qrDragOver(event,'qr-zone-<?php echo (int)$method['id']; ?>')" ondragleave="qrDragLeave('qr-zone-<?php echo (int)$method['id']; ?>')" ondrop="qrDrop(event,'qr-zone-<?php echo (int)$method['id']; ?>','qr-preview-<?php echo (int)$method['id']; ?>','qr-file-<?php echo (int)$method['id']; ?>')">
                        <input type="file" name="qr_code_file" id="qr-file-<?php echo (int)$method['id']; ?>" accept="image/*" onchange="qrFileChange(this,'qr-preview-<?php echo (int)$method['id']; ?>')">
                        <span class="material-symbols-rounded qr-upload-icon">qr_code_2</span>
                        <span class="qr-upload-label">Drag &amp; drop new QR code here</span>
                        <span class="qr-upload-hint">or click to browse &nbsp;·&nbsp; PNG, JPG, WEBP up to 2 MB</span>
                    </div>
                    <div class="qr-preview-wrap" id="qr-preview-<?php echo (int)$method['id']; ?>">
                        <img class="qr-preview-img" src="" alt="QR Preview">
                        <button type="button" class="qr-remove-btn" onclick="qrRemove('qr-preview-<?php echo (int)$method['id']; ?>','qr-file-<?php echo (int)$method['id']; ?>')" title="Remove">✕</button>
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Instructions / Configuration</label>
                    <?php if ($method['method_type'] === 'manual'): ?>
                        <div class="rich-editor-wrapper">
                            <div id="quill-modal-editor-<?php echo (int) $method['id']; ?>" data-id="<?php echo (int) $method['id']; ?>"></div>
                        </div>
                    <?php else: ?>
                        <textarea class="form-input" name="instructions" rows="3"><?php echo Security::escape((string) $method['instructions']); ?></textarea>
                    <?php endif; ?>
                </div>
            </div>
            <button class="btn btn-primary" type="submit" style="margin-top: 15px;">Update Method</button>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php ob_start(); ?>
<script>
const LOGO_URLS = {
    'easypaisa': '/assets/img/payment/easypaisa.png',
    'jazzcash':  '/assets/img/payment/jazzcash.png',
    'nayapay':   '/assets/img/payment/nayapay.png',
    'meezan':    '/assets/img/payment/meezan.webp',
    'bank':      '/assets/img/bank.svg',
};
function previewLogo(select, previewId) {
    const img = document.getElementById(previewId);
    if (!img) return;
    const val = select.value;
    if (val && LOGO_URLS[val]) {
        const base = (typeof BASE_URL !== 'undefined' ? BASE_URL : '');
        img.src = base + LOGO_URLS[val];
        img.classList.add('visible');
    } else { img.src = ''; img.classList.remove('visible'); }
}

/* ── QR Drag & Drop helpers ─────────────────── */
function qrDragOver(e, zoneId) {
    e.preventDefault();
    document.getElementById(zoneId)?.classList.add('drag-over');
}
function qrDragLeave(zoneId) {
    document.getElementById(zoneId)?.classList.remove('drag-over');
}
function qrDrop(e, zoneId, previewId, fileInputId) {
    e.preventDefault();
    document.getElementById(zoneId)?.classList.remove('drag-over');
    const file = e.dataTransfer?.files?.[0];
    if (!file || !file.type.startsWith('image/')) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    const input = document.getElementById(fileInputId);
    if (input) { input.files = dt.files; qrFileChange(input, previewId); }
}
function qrFileChange(input, previewId) {
    const file = input.files?.[0];
    if (!file) return;
    const wrap = document.getElementById(previewId);
    const img  = wrap?.querySelector('.qr-preview-img');
    if (!wrap || !img) return;
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; wrap.classList.add('visible'); };
    reader.readAsDataURL(file);
}
function qrRemove(previewId, fileInputId) {
    const wrap = document.getElementById(previewId);
    const img  = wrap?.querySelector('.qr-preview-img');
    if (wrap) wrap.classList.remove('visible');
    if (img)  img.src = '';
    const input = document.getElementById(fileInputId);
    if (input) { input.value = ''; }
}

document.addEventListener('DOMContentLoaded', () => {
    const isQuillLoaded = typeof Quill !== 'undefined';

    // Add form Quill
    const mainEditorEl = document.querySelector('#quill-main-editor');
    if (mainEditorEl) {
        if (isQuillLoaded) {
            const quill = new Quill(mainEditorEl, { theme:'snow', modules:{ toolbar:[['bold','italic','underline'],[{'list':'ordered'},{'list':'bullet'}],['clean']] } });
            const form = document.querySelector('#add-manual-method-form');
            if (form) form.addEventListener('submit', () => { document.querySelector('#instructions-input').value = quill.root.innerHTML; });
        } else {
            // Self-healing fallback: replace with a textarea inside the wrapper
            const parent = mainEditorEl.parentNode;
            const textarea = document.createElement('textarea');
            textarea.className = 'form-input';
            textarea.rows = 6;
            textarea.placeholder = 'Enter payment instructions here...';
            textarea.style.width = '100%';
            textarea.style.fontFamily = 'inherit';
            textarea.style.fontSize = '14px';
            textarea.style.padding = '12px';
            textarea.style.border = 'none';
            textarea.style.outline = 'none';
            textarea.style.boxShadow = 'none';
            textarea.style.resize = 'vertical';
            
            textarea.addEventListener('input', () => {
                document.querySelector('#instructions-input').value = textarea.value;
            });
            
            parent.innerHTML = '';
            parent.appendChild(textarea);
        }
    }
    // Edit modals Quill
    document.querySelectorAll('.edit-method-form').forEach(form => {
        const id = form.getAttribute('data-id');
        const modalEditorEl = document.querySelector('#quill-modal-editor-' + id);
        if (modalEditorEl) {
            const hiddenInput = document.querySelector('#instructions-input-' + id);
            if (isQuillLoaded) {
                const quill = new Quill(modalEditorEl, { theme:'snow', modules:{ toolbar:[['bold','italic','underline'],[{'list':'ordered'},{'list':'bullet'}],['clean']] } });
                if (hiddenInput) quill.root.innerHTML = hiddenInput.value;
                form.addEventListener('submit', () => { if (hiddenInput) hiddenInput.value = quill.root.innerHTML; });
            } else {
                // Self-healing fallback textarea
                const parent = modalEditorEl.parentNode;
                const textarea = document.createElement('textarea');
                textarea.className = 'form-input';
                textarea.rows = 6;
                textarea.style.width = '100%';
                textarea.style.fontFamily = 'inherit';
                textarea.style.fontSize = '14px';
                textarea.style.padding = '12px';
                textarea.style.border = 'none';
                textarea.style.outline = 'none';
                textarea.style.boxShadow = 'none';
                textarea.style.resize = 'vertical';
                if (hiddenInput) textarea.value = hiddenInput.value;
                
                textarea.addEventListener('input', () => {
                    if (hiddenInput) hiddenInput.value = textarea.value;
                });
                
                parent.innerHTML = '';
                parent.appendChild(textarea);
            }
        }
    });
});
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
