<?php ob_start(); ?>
<?php
$barcode = App::normalizeBarcode($product['barcode'] ?? '');
$displayBarcode = $barcode ?: ('200' . str_pad((string)$product['id'], 6, '0', STR_PAD_LEFT) . '123');
$unitLabel = App::formatProductUnit($product['unit_value'] ?? 1, $product['unit_type'] ?? 'pcs');
$rawProductImage = trim((string) ($product['image_path'] ?? ''));
$hasProductImage = $rawProductImage !== '' && App::mediaExists($rawProductImage);
$productImageUrl = $hasProductImage ? App::mediaUrl($rawProductImage) : '';
$storeName = App::setting('company_name', 'DevAltum POS');
$currency = App::setting('currency_symbol', 'Rs');
?>

<style>
    .product-profile-grid {
        display: grid;
        grid-template-columns: 360px 1fr;
        gap: 22px;
        align-items: start;
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }

    .product-profile-card {
        border: 1px solid var(--border);
        border-radius: 22px;
        background: #fff;
        padding: 22px;
        box-shadow: 0 14px 35px rgba(15, 23, 42, 0.06);
    }

    .product-barcode-card {
        margin-top: 20px;
        border: 1px solid #dbe4f0;
        border-radius: 20px;
        background: linear-gradient(150deg, #f8fbff, #ffffff);
        padding: 18px;
        box-shadow: 0 10px 24px rgba(15,23,42,.05);
    }

    .barcode-sticker-preview {
        background: #fff;
        border: 1.5px solid #cbd5e1;
        border-radius: 12px;
        padding: 10px 14px;
        text-align: center;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
        margin: 10px auto;
        width: fit-content;
        min-width: 180px;
        max-width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 5px;
        position: relative;
        box-sizing: border-box;
        transition: border-radius 0.2s ease;
    }


    .barcode-sticker-header {
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        color: #0f172a;
        margin: 0;
        line-height: 1.15;
    }

    .barcode-sticker-title {
        font-size: 12px;
        font-weight: 800;
        color: #334155;
        margin: 0;
        line-height: 1.15;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    .barcode-sticker-svg {
        display: block;
        width: 100%;
        max-width: 215px;
        max-height: 46px;
        margin: 0 auto;
    }

    .barcode-sticker-price {
        font-size: 17px;
        font-weight: 900;
        color: #0f172a;
        margin: 0;
        line-height: 1.15;
        letter-spacing: -0.2px;
    }


    .product-profile-image {
        width: 100%;
        max-width: 320px;
        aspect-ratio: 16 / 11;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, .32);
        background: linear-gradient(135deg, #ffffff, #eef6ff);
        display: grid;
        place-items: center;
        color: var(--primary);
        box-shadow: 0 16px 34px rgba(15,23,42,.10);
        margin: 0 auto 18px;
    }
    .product-profile-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }
    .product-profile-image .material-symbols-rounded {
        font-size: 48px;
    }

    .product-profile-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap;
        overflow-x: auto;
        padding-bottom: 4px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .product-profile-actions::-webkit-scrollbar {
        display: none;
    }
    .product-profile-actions .btn {
        flex-shrink: 0;
        white-space: nowrap;
    }
    .ppa-text-short {
        display: none;
    }
    .ppa-text-full {
        display: inline;
    }

    .product-profile-metrics-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
        margin-top: 14px;
    }

    .product-metric-pill {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 12px 14px;
        display: flex;
        flex-direction: column;
        gap: 3px;
        transition: transform 0.15s ease, border-color 0.15s ease;
    }
    .product-metric-pill:hover {
        border-color: #cbd5e1;
    }
    .product-metric-pill small {
        font-size: 11px;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
    }
    .product-metric-pill strong {
        font-size: 18px;
        font-weight: 850;
        color: #0f172a;
    }

    @media (max-width: 900px) {
        .product-profile-grid {
            grid-template-columns: 1fr !important;
            gap: 16px !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }
        .product-profile-card {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            padding: 16px 14px !important;
            border-radius: 18px !important;
            min-width: 0 !important;
        }
        .product-profile-image {
            width: 120px !important;
            height: 120px !important;
            max-width: 120px !important;
            aspect-ratio: 1 / 1 !important;
            border-radius: 18px !important;
            margin: 0 auto 14px !important;
            box-shadow: 0 8px 20px rgba(15,23,42,0.08) !important;
        }
        .product-profile-image .material-symbols-rounded {
            font-size: 36px !important;
        }
        .product-profile-right-col {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            overflow: hidden !important;
        }
        .product-profile-metrics-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
            margin-top: 12px !important;
            width: 100% !important;
        }
        .product-metric-pill {
            padding: 10px 12px !important;
            border-radius: 12px !important;
            min-width: 0 !important;
        }
        .product-metric-pill small {
            font-size: 10.5px !important;
        }
        .product-metric-pill strong {
            font-size: 15px !important;
        }
    }

    @media (max-width: 640px) {
        .page-header {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 10px !important;
            margin-bottom: 14px !important;
            width: 100% !important;
            min-width: 0 !important;
        }
        .page-header h1 {
            font-size: 18px !important;
            word-break: break-word !important;
            line-height: 1.25 !important;
        }
        .page-subtitle {
            font-size: 11.5px !important;
        }
        .product-profile-card {
            padding: 14px 12px !important;
            border-radius: 16px !important;
        }
        .product-profile-image {
            width: 105px !important;
            height: 105px !important;
            max-width: 105px !important;
            aspect-ratio: 1 / 1 !important;
            border-radius: 16px !important;
            margin: 0 auto 12px !important;
            box-shadow: 0 6px 16px rgba(15,23,42,0.07) !important;
        }
        .product-profile-image .material-symbols-rounded {
            font-size: 30px !important;
        }
        .ppa-text-full {
            display: none !important;
        }
        .ppa-text-short {
            display: inline !important;
        }
        .product-profile-actions {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            justify-content: stretch !important;
            gap: 6px !important;
            width: 100% !important;
            margin-top: 4px !important;
            overflow-x: visible !important;
        }
        .product-profile-actions .btn {
            flex: 1 1 0 !important;
            min-width: 0 !important;
            width: auto !important;
            min-height: 32px !important;
            height: 32px !important;
            padding: 4px 6px !important;
            font-size: 11px !important;
            font-weight: 750 !important;
            border-radius: 9px !important;
            gap: 4px !important;
            justify-content: center !important;
            white-space: nowrap !important;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06) !important;
        }
        .product-profile-actions .btn:first-child {
            flex: 1 1 0 !important;
        }
        .product-profile-actions .btn .material-symbols-rounded {
            font-size: 14px !important;
        }
        .product-profile-metrics-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 6px !important;
            margin-top: 10px !important;
        }
        .product-metric-pill {
            padding: 8px 10px !important;
            border-radius: 12px !important;
        }
        .product-metric-pill small {
            font-size: 10px !important;
        }
        .product-metric-pill strong {
            font-size: 14px !important;
        }
        .product-barcode-card {
            padding: 12px 10px !important;
            border-radius: 14px !important;
            margin-top: 10px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .barcode-sticker-preview {
            width: 100% !important;
            max-width: 215px !important;
            margin: 0 auto 6px !important;
            padding: 8px 6px !important;
            border-radius: 6px !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
            box-sizing: border-box !important;
        }
        .barcode-sticker-header {
            font-size: 9.5px !important;
        }
        .barcode-sticker-title {
            font-size: 10.5px !important;
        }
        .barcode-sticker-svg {
            max-width: 165px !important;
            height: 32px !important;
            margin: 2px auto !important;
        }
        .barcode-sticker-price {
            font-size: 14px !important;
        }
        .table-wrap {
            padding: 12px 10px !important;
            border-radius: 14px !important;
            box-sizing: border-box !important;
        }
        .table-wrap h3 {
            font-size: 14px !important;
        }
        .table-wrap .btn-sm {
            height: 30px !important;
            padding: 4px 8px !important;
            font-size: 11px !important;
        }
    }

    /* Smooth Horizontally Scrollable Tables */
    .table-scroll-wrap,
    .table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f8fafc;
    }

    .table-scroll-wrap::-webkit-scrollbar,
    .table-wrap::-webkit-scrollbar {
        height: 7px;
    }

    .table-scroll-wrap::-webkit-scrollbar-track,
    .table-wrap::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 999px;
    }

    .table-scroll-wrap::-webkit-scrollbar-thumb,
    .table-wrap::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
        transition: background 0.2s ease;
    }

    .table-scroll-wrap::-webkit-scrollbar-thumb:hover,
    .table-wrap::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
    <div>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/index" class="page-subtitle">&larr; Back to Inventory</a>
        <h1><?php echo Security::escape($product['name']); ?></h1>
        <p class="page-subtitle">Product specifications, barcode sticker preview, stock health, and recent sales.</p>
    </div>
    <div class="product-profile-actions">
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/barcodePrint/<?php echo (int)$product['id']; ?>" class="btn btn-secondary ppa-btn ppa-btn-print" style="font-weight:850; color:#1d4ed8; border-color:#bfdbfe; background:#eff6ff;">
            <span class="material-symbols-rounded">print</span>
            <span class="ppa-text-full">Print Barcode Labels</span>
            <span class="ppa-text-short">Labels</span>
        </a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/pos/index?add=<?php echo (int)$product['id']; ?>" class="btn btn-primary ppa-btn ppa-btn-pos" style="font-weight:850;">
            <span class="material-symbols-rounded">shopping_cart_checkout</span>
            <span class="ppa-text-full">Add to POS</span>
            <span class="ppa-text-short">POS</span>
        </a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/edit/<?php echo (int)$product['id']; ?>" class="btn btn-secondary ppa-btn ppa-btn-edit">
            <span class="material-symbols-rounded">edit</span>
            <span class="ppa-text-full">Edit Product</span>
            <span class="ppa-text-short">Edit</span>
        </a>
    </div>
</div>

<div class="product-profile-grid">
    <!-- Left Column: Specs & Barcode Sticker -->
    <section class="product-profile-card">
        <div class="product-profile-image">
            <?php if ($hasProductImage): ?>
                <img src="<?php echo Security::escape($productImageUrl); ?>" alt="" onerror="this.onerror=null; this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                <span class="material-symbols-rounded" style="display:none;">inventory_2</span>
            <?php else: ?>
                <span class="material-symbols-rounded">inventory_2</span>
            <?php endif; ?>
        </div>

        <div class="product-profile-metrics-grid">
            <div class="product-metric-pill">
                <small>Model / SKU</small>
                <strong><?php echo Security::escape($product['model_number'] ?: 'N/A'); ?></strong>
            </div>
            <div class="product-metric-pill">
                <small>Variant Unit</small>
                <strong><?php echo Security::escape($unitLabel); ?></strong>
            </div>
            <div class="product-metric-pill">
                <small>Selling Price</small>
                <strong style="color:#16a34a;"><?php echo App::money($product['price']); ?></strong>
            </div>
            <div class="product-metric-pill">
                <small>Current Stock</small>
                <div style="display:flex; align-items:baseline; gap:4px; flex-wrap:wrap;">
                    <strong><?php echo Security::escape(App::formatQuantity($product['stock'])); ?></strong>
                    <span style="font-size:11px; font-weight:800; color:#64748b;"><?php echo Security::escape(strtoupper($product['unit_type'] ?? 'PCS')); ?></span>
                </div>
                <div style="font-size:10.5px; color:#94a3b8; font-weight:600; margin-top:2px;">Alert: <?php echo Security::escape(App::formatQuantity($product['min_stock'])); ?> <?php echo Security::escape(strtoupper($product['unit_type'] ?? 'PCS')); ?></div>
            </div>
        </div>

        <!-- Supermart Barcode Sticker Display -->
        <div class="product-barcode-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <div style="font-size:13px; font-weight:900; color:#0f172a; display:flex; align-items:center; gap:6px;">
                    <span class="material-symbols-rounded" style="color:var(--primary); font-size:18px;">qr_code_2</span>
                    Supermart Barcode Sticker
                </div>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/barcodePrint/<?php echo (int)$product['id']; ?>" class="btn btn-secondary" style="font-size:11.5px; padding:4px 10px; font-weight:800; color:#1d4ed8; border-color:#bfdbfe;">
                    <span class="material-symbols-rounded" style="font-size:15px;">print</span> Print Sticker
                </a>
            </div>

            <div class="barcode-sticker-preview" id="view-sticker-preview">
                <div class="barcode-sticker-header"><?php echo Security::escape($storeName); ?></div>
                <div class="barcode-sticker-title"><?php echo Security::escape($product['name']); ?></div>
                <svg id="profile-barcode-svg" class="barcode-sticker-svg"></svg>
                <div class="barcode-sticker-price"><?php echo App::money($product['price']); ?></div>
            </div>

            <div style="font-size:11.5px; color:#64748b; font-weight:700; text-align:center;">
                Barcode: <span id="view-barcode-text" style="font-family:monospace; color:#0f172a; font-weight:850;"><?php echo Security::escape($displayBarcode); ?></span>
            </div>
        </div>
    </section>

    <!-- Right Column: Cost History & Sales History -->
    <div class="product-profile-right-col" style="display:flex; flex-direction:column; gap:20px; min-width:0; max-width:100%; width:100%; overflow:hidden;">
        <section class="card table-wrap table-scroll-wrap">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:10px;">
                <div>
                    <h3 style="margin:0; font-size:16px; font-weight:900;">Purchase & Cost History</h3>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">Track fluctuating purchase prices & margin impact</div>
                </div>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/index?tab=purchase_add&product_id=<?= (int)$product['id'] ?>" class="btn btn-secondary btn-sm" style="font-size:12px; font-weight:800;">
                    <span class="material-symbols-rounded" style="font-size:16px;">add_shopping_cart</span> New Purchase
                </a>
            </div>
            <table style="min-width:580px; width:100%;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Qty</th>
                        <th>Unit Cost</th>
                        <th>Cost Impact</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($purchases)): ?>
                        <tr><td colspan="6" class="empty-state">No purchase history recorded for this product yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($purchases as $p): ?>
                            <?php 
                                $uCost = (float)($p['unit_cost'] ?? 0);
                                $oCost = (float)($p['old_cost'] ?? 0);
                                $diff = $uCost - $oCost;
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight:750; color:#0f172a;"><?= date('M d, Y', strtotime(!empty($p['purchase_date']) ? $p['purchase_date'] : $p['created_at'])) ?></div>
                                    <div style="font-size:11px; color:#64748b;"><?= date('h:i A', strtotime($p['created_at'] ?? $p['purchase_date'])) ?></div>
                                </td>
                                <td>
                                    <span style="font-weight:700; color:#334155;"><?= Security::escape($p['supplier_name'] ?: 'General Vendor') ?></span>
                                    <?php if (!empty($p['purchase_id'])): ?>
                                        <div><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/purchaseView/<?= (int)$p['purchase_id'] ?>" style="font-size:11px; color:#2563eb; font-weight:700;">Bill #<?= (int)$p['purchase_id'] ?> &rarr;</a></div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= (float)$p['quantity'] ?> <?= Security::escape(strtoupper($p['unit_type'] ?? 'PCS')) ?></strong></td>
                                <td><strong><?= App::money($uCost) ?></strong></td>
                                <td>
                                    <?php if ($oCost <= 0): ?>
                                        <span class="badge" style="background:#f1f5f9; color:#475569; font-size:11px;">Initial Cost</span>
                                    <?php elseif ($diff > 0): ?>
                                        <span class="badge" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca; font-size:11px; font-weight:800;">
                                             +<?= App::money($diff) ?> (+<?= round(($diff / $oCost) * 100, 1) ?>%)
                                        </span>
                                    <?php elseif ($diff < 0): ?>
                                        <span class="badge" style="background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; font-size:11px; font-weight:800;">
                                            ▼ -<?= App::money(abs($diff)) ?> (<?= round(($diff / $oCost) * 100, 1) ?>%)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#f8fafc; color:#64748b; font-size:11px;">Same Cost</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= App::money($p['bill_total']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="card table-wrap table-scroll-wrap">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
                <h3 style="margin:0; font-size:16px; font-weight:900;">Recent Sales History</h3>
                <span class="badge badge-soft">Last 10 Invoices</span>
            </div>
            <table style="min-width:500px; width:100%;">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr><td colspan="5" class="empty-state">No sales recorded for this product yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/action/<?php echo $sale['id']; ?>"><strong><?php echo Security::escape($sale['custom_id']); ?></strong></a></td>
                                <td><?php echo Security::escape($sale['transaction_type']); ?></td>
                                <td><?php echo (int) $sale['quantity']; ?></td>
                                <td><?php echo App::money($sale['total']); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($sale['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</div>

<?php ob_start(); ?>
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/JsBarcode.all.min.js"></script>
<script>
    (function() {
        const renderBarcode = () => {
            const svgEl = document.getElementById('profile-barcode-svg');
            const code = <?php echo json_encode($displayBarcode); ?>;
            if (svgEl && window.JsBarcode && code) {
                try {
                    JsBarcode(svgEl, String(code).trim(), {
                        format: "CODE128",
                        width: 1.8,
                        height: 38,
                        displayValue: true,
                        fontSize: 11,
                        font: 'monospace',
                        textAlign: 'center',
                        textMargin: 2,
                        margin: 0
                    });
                } catch(e) {
                    console.warn(e);
                }
            }
        };

        renderBarcode();
        document.addEventListener('DOMContentLoaded', renderBarcode);
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            setTimeout(renderBarcode, 30);
        }
    })();
</script>

<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/views/layouts/main.php';
?>

