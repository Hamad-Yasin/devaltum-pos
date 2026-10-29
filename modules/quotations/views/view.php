<?php ob_start(); ?>

<style>
    .quote-view-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .quote-action-toolbar {
        background: #ffffff;
        border: 1px solid var(--border, #e2e8f0);
        border-radius: 16px;
        padding: 14px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 24px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
    }

    .quote-sheet {
        background: #ffffff;
        border: 1px solid var(--border, #e2e8f0);
        border-radius: 18px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        position: relative;
    }

    .quote-items-table th {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        padding: 12px 14px;
        font-size: 13px;
        font-weight: 800;
        color: #334155;
    }
    .quote-items-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
        color: #1e293b;
    }

    @media print {
        body { background: #fff !important; }
        .quote-action-toolbar, .main-header, .sidebar, .btn, .no-print { display: none !important; }
        .quote-sheet { border: none !important; box-shadow: none !important; padding: 0 !important; }
        .quote-view-container { max-width: 100% !important; margin: 0 !important; }
        .table-responsive { overflow: visible !important; }
    }
</style>

<div class="quote-view-container">

    <!-- Top Action Toolbar -->
    <div class="quote-action-toolbar">
        <div>
            <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/index" style="color: #2563eb; text-decoration: none; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
                <span class="material-symbols-rounded" style="font-size: 16px;">arrow_back</span> Back to List
            </a>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <button type="button" class="btn btn-secondary" onclick="window.print()">
                <span class="material-symbols-rounded">print</span> Print Quotation
            </button>

            <?php 
                $cleanPhone = preg_replace('/[^0-9]/', '', $quotation['customer_phone'] ?? '');
                if (str_starts_with($cleanPhone, '0')) {
                    $cleanPhone = '92' . substr($cleanPhone, 1);
                }
                $waText = "Hello " . $quotation['customer_name'] . ",\nHere is your official Quotation (*" . $quotation['quotation_number'] . "*)\n";
                $waText .= "Total Amount: *" . App::money($quotation['total']) . "*\n";
                $waText .= "Valid Until: " . date('d M Y', strtotime($quotation['valid_until'])) . "\n\n";
                $waText .= "Thank you for your inquiry!\n" . ($storeName ?? 'DevAltum POS');
                $waLink = "https://wa.me/{$cleanPhone}?text=" . urlencode($waText);
            ?>

            <?php if (!empty($cleanPhone)): ?>
                <a href="<?= $waLink ?>" target="_blank" class="btn btn-success" style="background: #25D366; border-color: #25D366; color: #fff;">
                    <span class="material-symbols-rounded">chat</span> Send WhatsApp
                </a>
            <?php endif; ?>

            <?php if ($quotation['status'] !== 'converted'): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/edit/<?= (int)$quotation['id'] ?>" class="btn btn-outline" data-no-ajax>
                    <span class="material-symbols-rounded">edit</span> Edit
                </a>

                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/convert/<?= (int)$quotation['id'] ?>" class="btn btn-primary" data-no-ajax style="background: #059669; border-color: #059669;">
                    <span class="material-symbols-rounded">shopping_cart_checkout</span> Convert to Sale Invoice
                </a>

                <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/delete/<?= (int)$quotation['id'] ?>" onsubmit="return handleViewQuoteDelete(event, this, '<?= htmlspecialchars($quotation['quotation_number']) ?>');" style="display: inline;" data-no-ajax>
                    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                    <button type="submit" class="btn btn-outline" style="color: var(--error);" title="Delete Quotation">
                        <span class="material-symbols-rounded">delete</span>
                    </button>
                </form>
            <?php else: ?>
                <span class="badge badge-success" style="font-size: 13px; font-weight: 800; padding: 8px 14px;">
                    <span class="material-symbols-rounded" style="font-size: 18px;">check_circle</span>
                    Converted to Sale
                    <?php if (!empty($convertedInvoice)): ?>
                        (<?= htmlspecialchars($convertedInvoice['custom_id']) ?>)
                    <?php endif; ?>
                </span>
                <?php if (!empty($convertedInvoice)): ?>
                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/index?q=<?= urlencode($convertedInvoice['custom_id']) ?>" class="btn btn-sm btn-secondary" data-no-ajax>
                        <span class="material-symbols-rounded">receipt</span> View Sale Bill
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quotation Sheet Document -->
    <div class="quote-sheet">
        <?php
            $validDays = !empty($quotation['valid_until']) 
                ? max(1, (int)round((strtotime($quotation['valid_until']) - strtotime($quotation['created_at'] ?? date('Y-m-d'))) / 86400)) 
                : (int)($quotation['valid_days'] ?? 15);
        ?>

        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 24px; margin-bottom: 28px;">
            <div>
                <h2 style="margin: 0; font-size: 26px; font-weight: 900; color: #0f172a; letter-spacing: -0.5px;">
                    <?= htmlspecialchars($storeName ?? 'DevAltum POS') ?>
                </h2>
                <div style="font-size: 13px; color: #64748b; margin-top: 4px;"><?= htmlspecialchars($storeAddress ?? 'Main Commercial Center') ?></div>
                <div style="font-size: 13px; color: #64748b; margin-top: 2px;">Phone: <?= htmlspecialchars($storePhone ?? '+92 300 0000000') ?></div>
            </div>
            <div style="text-align: right;">
                <div style="display: inline-block; border: 1.5px dashed #94a3b8; color: #475569; padding: 4px 12px; border-radius: 8px; font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; background: #f8fafc;">
                    QUOTATION / ESTIMATE
                </div>
                <div style="font-size: 22px; font-weight: 900; color: #2563eb; letter-spacing: -0.5px;">
                    <?= htmlspecialchars($quotation['quotation_number']) ?>
                </div>
                <div style="font-size: 13px; font-weight: 700; color: #334155; margin-top: 4px;">
                    Date: <?= date('M d, Y', strtotime($quotation['created_at'])) ?>
                </div>
                <div style="font-size: 12px; color: #dc2626; font-weight: 700; margin-top: 2px;">
                    Valid Until: <?= date('M d, Y', strtotime($quotation['valid_until'])) ?> (<?= $validDays ?> Days)
                </div>
            </div>
        </div>

        <!-- Customer & Estimate Meta -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div>
                <span style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">QUOTATION PREPARED FOR</span>
                <div style="font-size: 17px; font-weight: 800; color: #0f172a;"><?= htmlspecialchars($quotation['customer_name']) ?></div>
                <?php if (!empty($quotation['customer_phone'])): ?>
                    <div style="font-size: 13px; color: #475569; margin-top: 3px;">Phone: <?= htmlspecialchars($quotation['customer_phone']) ?></div>
                <?php endif; ?>
                <?php if (!empty($quotation['customer_address'])): ?>
                    <div style="font-size: 13px; color: #475569; margin-top: 2px;">Address: <?= htmlspecialchars($quotation['customer_address']) ?></div>
                <?php endif; ?>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">ESTIMATE STATUS</span>
                <div>
                    <?php if ($quotation['status'] === 'converted'): ?>
                        <span class="badge badge-success" style="font-size: 13px; font-weight: 800; padding: 6px 12px;">Converted to Sale</span>
                    <?php elseif ($quotation['status'] === 'expired'): ?>
                        <span class="badge badge-danger" style="font-size: 13px; font-weight: 800; padding: 6px 12px;">Expired</span>
                    <?php else: ?>
                        <span class="badge badge-warning" style="font-size: 13px; font-weight: 800; padding: 6px 12px;">Pending Customer Approval</span>
                    <?php endif; ?>
                </div>
                <div style="font-size: 12px; color: #64748b; margin-top: 8px;">
                    Created by: <?= htmlspecialchars($quotation['creator_name'] ?? 'Staff') ?>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive" style="margin-bottom: 24px;">
            <table class="quote-items-table" style="width: 100%; border-collapse: collapse; min-width: 600px;">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">#</th>
                        <th style="text-align: left; min-width: 180px;">Item Description</th>
                        <th style="width: 90px; text-align: center; white-space: nowrap;">Qty</th>
                        <th style="width: 130px; text-align: right; white-space: nowrap;">Unit Price</th>
                        <th style="width: 110px; text-align: right; white-space: nowrap;">Discount</th>
                        <th style="width: 140px; text-align: right; white-space: nowrap;">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $idx = 1; foreach ($items as $item): ?>
                        <tr>
                            <td style="text-align: center; color: #94a3b8; font-weight: 700;"><?= $idx++ ?></td>
                            <td>
                                <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($item['product_name']) ?></div>
                                <?php if (!empty($item['model_number'])): ?>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Model: <?= htmlspecialchars($item['model_number']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; font-weight: 800; white-space: nowrap;">
                                <?= (float)$item['quantity'] ?> <?= htmlspecialchars($item['unit_type'] ?? 'pcs') ?>
                            </td>
                            <td style="text-align: right; font-weight: 700; white-space: nowrap;">
                                <?= App::money((float)$item['price']) ?>
                            </td>
                            <td style="text-align: right; color: #64748b; white-space: nowrap;">
                                <?= (float)$item['discount'] > 0 ? App::money((float)$item['discount']) : '-' ?>
                            </td>
                            <td style="text-align: right; font-weight: 800; color: #0f172a; font-size: 14px; white-space: nowrap;">
                                <?= App::money((float)$item['total']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals Summary -->
        <div style="display: flex; justify-content: flex-end; margin-bottom: 30px;">
            <div style="width: 320px;">
                <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; color: #475569;">
                    <span>Subtotal:</span>
                    <strong style="color: #0f172a;"><?= App::money((float)$quotation['subtotal']) ?></strong>
                </div>
                <?php if ((float)$quotation['discount'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; color: #dc2626;">
                        <span>Extra Discount:</span>
                        <strong>- <?= App::money((float)$quotation['discount']) ?></strong>
                    </div>
                <?php endif; ?>
                <?php if ((float)$quotation['tax_amount'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; color: #475569;">
                        <span>Tax (<?= (float)$quotation['tax_rate'] ?>%):</span>
                        <strong style="color: #0f172a;">+ <?= App::money((float)$quotation['tax_amount']) ?></strong>
                    </div>
                <?php endif; ?>
                <div style="display: flex; justify-content: space-between; padding: 12px 0; margin-top: 6px; border-top: 2px solid #0f172a; font-size: 18px; font-weight: 900; color: #0f172a;">
                    <span>Grand Total:</span>
                    <span style="color: #2563eb; font-size: 20px;"><?= App::money((float)$quotation['total']) ?></span>
                </div>
            </div>
        </div>

        <?php if (!empty($quotation['notes'])): ?>
            <div style="border-top: 1px solid #f1f5f9; padding-top: 20px; font-size: 13px; color: #64748b;">
                <strong style="color: #334155; display: block; margin-bottom: 4px;">Terms & Conditions / Notes:</strong>
                <div style="white-space: pre-line; line-height: 1.5;"><?= htmlspecialchars($quotation['notes']) ?></div>
            </div>
        <?php endif; ?>

        <div style="margin-top: 40px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px dashed #e2e8f0; padding-top: 16px;">
            This is a computer-generated quotation / estimate. It is not an official tax invoice until converted into a confirmed sale.
        </div>
    </div>

</div>

<script>
    window.handleViewQuoteDelete = async function(e, form, qNum) {
        e.preventDefault();
        const confirmed = await window.appConfirm(
            `Are you sure you want to permanently delete quotation <strong>${qNum}</strong>? This action cannot be undone.`,
            'Delete Quotation',
            { isDanger: true, confirmText: 'Delete Quotation', icon: 'delete' }
        );
        if (confirmed) {
            form.removeAttribute('onsubmit');
            form.submit();
        }
        return false;
    };
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>
