<?php
if (!class_exists('ReceiptTemplates')) {
    $receiptTemplatesHelper = (defined('CORE_PATH') ? CORE_PATH : dirname(__DIR__, 2) . '/core') . '/helpers/ReceiptTemplates.php';
    if (file_exists($receiptTemplatesHelper)) {
        require_once $receiptTemplatesHelper;
    }
}
if (!class_exists('BarcodeHelper')) {
    $barcodeHelper = (defined('CORE_PATH') ? CORE_PATH : dirname(__DIR__, 2) . '/core') . '/helpers/BarcodeHelper.php';
    if (file_exists($barcodeHelper)) {
        require_once $barcodeHelper;
    }
}
$rawReceiptLogo = App::setting('receipt_logo', '');
$receiptLogo = ($rawReceiptLogo !== '' && App::mediaExists($rawReceiptLogo)) ? App::mediaUrl($rawReceiptLogo) : '';
$companyName = App::setting('company_name', 'DevAltum POS');
$companyAddress = App::setting('company_address', '');
$companyNtn = trim((string) App::setting('company_ntn', ''));
if ($companyNtn === '') {
    $companyNtn = trim((string) App::setting('company_ntn_number', App::setting('ntn', '')));
}
$companyWhatsapp = App::setting('whatsapp_number', '');
$footerText = App::setting('receipt_footer', 'Thank you for choosing us. For support, please contact our store team.');
$requestedTemplate = trim((string) ($_GET['template'] ?? ''));
if ($requestedTemplate !== '') {
    $receiptTemplateMeta = ReceiptTemplates::resolve($requestedTemplate);
} else {
    $receiptTemplateMeta = ReceiptTemplates::resolve(App::setting('receipt_template', 'classic'));
}
$receiptTemplateKey = $receiptTemplateMeta['template_key'];
$receiptTemplate = $receiptTemplateMeta['base_template'];
$receiptTemplateVars = ReceiptTemplates::cssVariables($receiptTemplateMeta);
$isDocumentTemplate = in_array($receiptTemplateKey, ['invoice', 'executive', 'emerald', 'monogram'], true);
$forcedPaper = trim((string) ($_GET['paper'] ?? ''));
$templatePaperWidthMm = max(40, min(300, (int) ($receiptTemplateMeta['paper_width_mm'] ?? 80)));
$is58mm = ($forcedPaper === '58' || $templatePaperWidthMm === 58 || $receiptTemplateKey === 'compact');
$paperWidthMm = $isDocumentTemplate ? ($templatePaperWidthMm ?: 148) : ($is58mm ? 58 : 80);
$printableWidthMm = $isDocumentTemplate ? ($templatePaperWidthMm ?: 148) : ($is58mm ? 48 : 72);
$customerName = $invoice['customer_name_snapshot'] ?: $invoice['customer_name'];
$customerPhone = $invoice['customer_phone_snapshot'] ?: $invoice['customer_phone'];
$storePhone = App::setting('company_phone', App::setting('whatsapp_number', '0344 66 99 477'));
if ($storePhone === '') {
    $storePhone = $companyWhatsapp;
}
$terminalName = trim((string) ($invoice['terminal_name'] ?? $invoice['register_name'] ?? ''));
if ($terminalName === '') {
    $terminalName = !empty($_SERVER['COMPUTERNAME']) ? $_SERVER['COMPUTERNAME'] : (!empty(getenv('COMPUTERNAME')) ? getenv('COMPUTERNAME') : 'DESKTOP-P4CVISP');
}
$totalQtySum = 0;
foreach ($items as $itm) {
    $totalQtySum += (float) ($itm['quantity'] ?? 0);
}
$isCredit = strtolower((string) ($invoice['payment_method'] ?? '')) === 'credit';
$customerWa = preg_replace('/[^0-9]/', '', (string) $customerPhone);
if (strpos($customerWa, '0') === 0) {
    $customerWa = '92' . substr($customerWa, 1);
}
$baseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$basePath = defined('BASE_URL') ? BASE_URL : '';
$templateQueryParam = $requestedTemplate !== '' ? '&template=' . urlencode($requestedTemplate) : '';
$pdfUrl = $basePath . '/pos/receipt/' . (int) $invoice['id'] . '?download_pdf=1' . $templateQueryParam;
$imageUrl = $basePath . '/pos/receipt/' . (int) $invoice['id'] . '?download_image=1' . $templateQueryParam;
$autoPdfUrl = $baseUrl . $pdfUrl;
$verifyQrUrl = App::qrImageUrl($autoPdfUrl, 220, 8);
$receiptFileName = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) ($invoice['custom_id'] ?? 'receipt'));
$invoiceTotal = (float) ($invoice['total'] ?? 0);
$taxAmount = (float) ($invoice['tax_amount'] ?? 0);
$taxRate = (float) ($invoice['tax_rate'] ?? 0);
$taxLabel = trim((string) ($invoice['tax_label'] ?? App::setting('tax_label', 'Sales Tax')));
$taxableAmount = max(0, (float) ($invoice['subtotal'] ?? 0) - (float) ($invoice['discount'] ?? 0));
$fbrPosId = trim((string) ($invoice['fbr_pos_id'] ?? ''));
$taxRegistrationType = App::setting('tax_registration_type', 'STRN');
$taxNumber = trim((string) App::setting('tax_number', ''));
$fbrNote = trim((string) App::setting('fbr_invoice_note', 'FBR POS integrated tax invoice'));
$openDue = max(0, max(($invoiceTotal - (float) ($invoice['paid_amount'] ?? 0)), (float) ($invoice['balance_due'] ?? 0)));
$isCredit = strtolower((string) ($invoice['payment_method'] ?? '')) === 'credit' || $openDue > 0;
$rawStatus = strtolower(trim((string) ($invoice['status'] ?? '')));
if (!in_array($rawStatus, ['paid', 'unpaid', 'draft'], true)) {
    $rawStatus = $openDue > 0 ? 'unpaid' : 'paid';
}
$receiptStatus = strtoupper($rawStatus);
$displayPaid = max(0, $invoiceTotal - $openDue);
$totalItems = count($items);
$receiptVis = App::receiptVisibility();
$receiptLbl = App::receiptLabels();
$receiptOrder = App::receiptSectionOrder();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?php echo Security::escape($invoice['custom_id']); ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/fonts/fonts.css">
    <style>
        :root { --print-width-mm: <?php echo (int) $printableWidthMm; ?>; }
        body { font-family: var(--receipt-font), 'Trebuchet MS', Arial, sans-serif; background:#f3f4f6; margin:0; padding:24px; color:#111827; }
        .sheet { width: 380px; margin:0 auto; background:#fff; box-shadow:0 18px 40px rgba(17,24,39,.14); border-radius:18px; overflow:hidden; box-sizing:border-box; }
        .sheet, .sheet * { font-weight: 700; }
        .devaltum-receipt-branding { display:inline-flex; align-items:center; justify-content:center; gap:12px; margin:10px auto 4px; text-align:left; vertical-align:middle; text-decoration:none; }
        .devaltum-receipt-branding .devaltum-receipt-logo { height:48px; max-height:54px; width:auto; max-width:85px; object-fit:contain; flex-shrink:0; }
        .devaltum-receipt-branding .devaltum-receipt-text { display:inline-flex; flex-direction:column; justify-content:center; line-height:1.25; font-family:'Orbitron', 'Montserrat', 'Trebuchet MS', 'Segoe UI', Roboto, sans-serif; }
        .devaltum-receipt-branding .devaltum-brand-line { font-size:12px; font-weight:900; color:#0b1528; letter-spacing:0.6px; text-transform:uppercase; white-space:nowrap; }
        .devaltum-receipt-branding .devaltum-brand-line strong { font-weight:900; color:#0f6dff; }
        .devaltum-receipt-branding .devaltum-phone-line { font-size:13.5px; font-weight:800; color:#0b1528; letter-spacing:0.8px; white-space:nowrap; margin-top:2px; }
        .top { padding:18px 18px 12px; text-align:center; border-bottom:1px dashed #d1d5db; }
        .top img { width:52px; height:52px; object-fit:cover; border-radius:12px; margin-bottom:10px; }
        .title { font-size:18px; font-weight:700; margin-bottom:4px; }
        .muted { color:#475569; font-size:12px; font-weight:700; }
        .thermal-invoice-number { display:none; }
        .block { padding:12px 18px; border-bottom:1px dashed #d1d5db; }
        .row { display:flex; justify-content:space-between; gap:10px; margin-bottom:6px; font-size:13px; }
        .items { width:100%; border-collapse:collapse; font-size:13px; }
        .items th, .items td { padding:6px 0; text-align:left; }
        .items th:last-child, .items td:last-child { text-align:right; }
        .items th:nth-child(2), .items td:nth-child(2) { width:46px; text-align:center; }
        .items th:nth-child(3), .items td:nth-child(3) { width:96px; text-align:right; padding-left:12px; }
        .items th:nth-child(4), .items td:nth-child(4) { width:90px; text-align:right; padding-left:12px; }
        .receipt-item-name { min-width:0; }
        .receipt-item-title { display:block; color:#111827; line-height:1.25; }
        .receipt-item-unit { display:block; margin-top:2px; color:#64748b; font-size:10px; font-weight:700; line-height:1.2; }
        .totals .row { margin-bottom:8px; }
        .tax-detail-card { background:#f8fbff; border:1px solid #dbeafe; border-radius:14px; padding:10px 12px; margin-top:8px; }
        .tax-detail-title { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; color:#0f3b73; font-size:12px; font-weight:900; }
        .tax-detail-pill { border-radius:999px; padding:4px 8px; background:#e0f2fe; color:#075985; font-size:10px; font-weight:900; white-space:nowrap; }
        .tax-detail-card .row { font-size:12px; margin-bottom:5px; }
        .tax-detail-card .row:last-child { margin-bottom:0; }
        .grand { font-size:16px; font-weight:700; padding-top:8px; border-top:1px dashed #d1d5db; }
        .footer { padding:14px 18px 18px; font-size:12px; color:#4b5563; line-height:1.45; }
        .receipt-footer-main { display:flex; align-items:center; justify-content:space-between; gap:12px; text-align:left; }
        .receipt-footer-copy { min-width:0; flex:1; }
        .receipt-footer-copy strong { color:#1f2937; }
        .receipt-verify-qr { width:72px; flex:0 0 72px; text-align:center; }
        .receipt-verify-qr img { width:72px; height:72px; object-fit:contain; display:block; margin-left:auto; border:0; border-radius:0; padding:0; background:transparent; }
        .receipt-dev-footer { font-weight:600; font-size:11px; text-align:center; color:#111827; }
        .actions { display:flex; flex-direction:column; gap:10px; padding:0 18px 18px; }
        .actions a, .actions button { border:none; border-radius:12px; padding:10px 14px; text-decoration:none; cursor:pointer; font-size:13px; }
        .primary { background:#111827; color:#fff; }
        .secondary { background:#e5e7eb; color:#111827; }
        @media (max-width: 480px) {
            body { padding: 6px 4px !important; background: #fff !important; }
            .sheet { width: 100% !important; max-width: 100% !important; box-shadow: none !important; border-radius: 0 !important; }
            .top { padding: 14px 10px 10px !important; }
            .block { padding: 10px 10px !important; }
            .footer { padding: 12px 10px !important; }
            .actions { padding: 0 10px 16px !important; }
            .items th:nth-child(3), .items td:nth-child(3) { width: auto !important; padding-left: 6px !important; }
            .items th:nth-child(4), .items td:nth-child(4) { width: auto !important; padding-left: 6px !important; }
        }
        body.thermal .sheet { width: 300px; }
        body.receipt-template-compact .sheet { width: 320px; border-radius:6px; font-family:'Courier New', monospace; }
        body.receipt-template-compact .top { padding:14px 12px 8px; }
        body.receipt-template-compact .block { padding:10px 12px; }
        body.receipt-template-compact .footer { padding:12px; }
        body.receipt-template-compact .title { text-transform:uppercase; font-size:16px; }
        body.receipt-template-premium .sheet { border-radius:24px; box-shadow:0 22px 46px rgba(15,23,42,.18); }
        body.receipt-template-premium .top { background:linear-gradient(135deg, #0f172a, #1d4ed8); color:#fff; border-bottom:0; }
        body.receipt-template-premium .top .muted { color:#dbeafe; }
        body.receipt-template-premium .block { border-bottom:1px solid #e2e8f0; }
        body.receipt-template-premium .footer { background:#f8fbff; }
        body.receipt-template-classic .invoice-head-left,
        body.receipt-template-compact .invoice-head-left,
        body.receipt-template-premium .invoice-head-left { display:block; }
        body.receipt-template-classic .top img,
        body.receipt-template-compact .top img,
        body.receipt-template-premium .top img { display:block; margin:0 auto 10px; }
        body.receipt-template-classic .company-header-meta,
        body.receipt-template-compact .company-header-meta,
        body.receipt-template-premium .company-header-meta { display:none; }
        body.receipt-template-classic .thermal-invoice-number,
        body.receipt-template-compact .thermal-invoice-number,
        body.receipt-template-premium .thermal-invoice-number { display:flex; align-items:center; justify-content:center; gap:8px; flex-wrap:wrap; margin-top:4px; color:#1e40af; font-size:12px; font-weight:900; }
        .thermal-invoice-number .thermal-date { color:#475569; font-size:11px; }
        body.receipt-template-premium .thermal-invoice-number,
        body.receipt-template-premium .thermal-invoice-number .thermal-date { color:#fff; }
        body.receipt-template-invoice .sheet { width:540px; border-radius:0; border:2px solid #0b4f66; border-top:8px solid #0b4f66; border-bottom:8px solid #0b4f66; box-shadow:0 18px 40px rgba(17,24,39,.14); position:relative; }
        body.receipt-template-invoice .sheet::before,
        body.receipt-template-invoice .sheet::after { content:''; position:absolute; left:0; right:0; height:4px; background:linear-gradient(90deg,#f59e0b,#0b4f66,#f59e0b); }
        body.receipt-template-invoice .sheet::before { top:-8px; }
        body.receipt-template-invoice .sheet::after { bottom:-8px; }
        body.receipt-template-invoice .top { display:grid; grid-template-columns:1fr 190px; gap:16px; text-align:left; border-bottom:0; padding:20px; }
        body.receipt-template-invoice .top img { width:48px; height:48px; margin:0; }
        body.receipt-template-invoice .title { font-size:22px; color:#111827; }
        body.receipt-template-invoice .invoice-head-left { display:flex; align-items:center; gap:12px; }
        body.receipt-template-invoice .invoice-box { display:block; border:1.5px solid #0b4f66; border-radius:10px; padding:10px 12px; }
        body.receipt-template-invoice .invoice-box h4 { margin:0 0 6px; color:#0b4f66; font-size:20px; letter-spacing:3px; }
        body.receipt-template-invoice .block { margin:0 20px 8px; padding:10px; border:1.5px solid #0b4f66; }
        body.receipt-template-invoice .invoice-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px 18px; }
        body.receipt-template-invoice .invoice-info-grid .row { display:grid; grid-template-columns:auto minmax(0,1fr); gap:6px; margin:0; font-size:12px; align-items:center; }
        body.receipt-template-invoice .invoice-info-grid .row > span:first-child { color:#111827; white-space:nowrap; }
        body.receipt-template-invoice .invoice-info-grid .row > strong { text-align:left; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        body.receipt-template-invoice .items { border:1.5px solid #0b4f66; }
        body.receipt-template-invoice .items th { background:#0b4f66; color:#fff; padding:7px 8px; }
        body.receipt-template-invoice .items td { border:1px solid #0b4f66; padding:7px 8px; }
        body.receipt-template-invoice .footer { padding:12px 20px 22px; }
        body.receipt-template-invoice .receipt-dev-footer { display:none; }
        body.receipt-template-invoice .signature-row { display:block; margin-top:14px; color:#111827; text-align:center; }
        body.receipt-template-invoice .signature-row span { border-top:1px solid #111827; padding-top:5px; font-size:12px; }
        body.receipt-template-invoice .signature-row span:not(.signature-software) { display:none; }
        body.receipt-template-invoice .signature-row .signature-software,
        body.receipt-template-executive .signature-row .signature-software,
        body.receipt-template-emerald .signature-row .signature-software,
        body.receipt-template-monogram .signature-row .signature-software { display:inline-flex; align-items:center; justify-content:center; border-top:1px dashed #cbd5e1; padding-top:8px; text-align:center; font-weight:800; font-size:11px; white-space:nowrap; color:#111827; width:100%; }
        body.receipt-template-executive .sheet,
        body.receipt-template-emerald .sheet,
        body.receipt-template-monogram .sheet { width:540px; border-radius:0; box-shadow:0 18px 40px rgba(17,24,39,.14); }
        body.receipt-template-executive .sheet { border:2px solid #102a43; border-top:14px solid #102a43; }
        body.receipt-template-executive .top { display:grid; grid-template-columns:1fr 190px; gap:16px; text-align:left; padding:20px; background:#f8fafc; border-bottom:2px solid #102a43; }
        body.receipt-template-executive .invoice-box { display:block; border:0; background:#102a43; color:#fff; border-radius:4px; padding:12px; }
        body.receipt-template-executive .invoice-box h4 { margin:0 0 6px; color:#fff; font-size:20px; letter-spacing:3px; }
        body.receipt-template-executive .invoice-box .muted { color:#fff; }
        body.receipt-template-executive .block { margin:0 20px; padding:10px; border:0; border-bottom:1px solid #cbd5e1; }
        body.receipt-template-executive .items th { background:#102a43; color:#fff; padding:7px 8px; }
        body.receipt-template-executive .items td { border-bottom:1px solid #cbd5e1; padding:7px 8px; }
        body.receipt-template-emerald .sheet { border:2px solid #047857; border-radius:18px; background:#f0fdf4; }
        body.receipt-template-emerald .top { display:grid; grid-template-columns:1fr 190px; gap:16px; text-align:left; padding:20px; background:linear-gradient(135deg,#047857,#10b981); color:#fff; border-bottom:0; }
        body.receipt-template-emerald .top .muted { color:#dcfce7; }
        body.receipt-template-emerald .invoice-box { display:block; background:#fff; color:#111827; border:0; border-radius:14px; padding:12px; }
        body.receipt-template-emerald .invoice-box h4 { margin:0 0 6px; color:#047857; font-size:20px; letter-spacing:3px; }
        body.receipt-template-emerald .invoice-box .muted { color:#111827; }
        body.receipt-template-emerald .block { margin:10px 18px; padding:10px; background:#fff; border:1px solid #bbf7d0; border-radius:14px; }
        body.receipt-template-emerald .items th { background:#047857; color:#fff; padding:7px 8px; }
        body.receipt-template-emerald .items td { border-bottom:1px solid #d1fae5; padding:7px 8px; }
        body.receipt-template-monogram .sheet { border:1px solid #cbd5e1; border-left:12px solid #111827; }
        body.receipt-template-monogram .top { display:grid; grid-template-columns:1fr 180px; gap:16px; text-align:left; padding:20px; border-bottom:1px solid #111827; }
        body.receipt-template-monogram .invoice-box { display:block; border:1px solid #111827; border-radius:0; padding:12px; }
        body.receipt-template-monogram .invoice-box h4 { margin:0 0 6px; color:#111827; font-size:20px; letter-spacing:2px; }
        body.receipt-template-monogram .block { margin:0 20px 8px; padding:10px; border:0; border-bottom:1px solid #e2e8f0; }
        body.receipt-template-monogram .items th { background:#111827; color:#fff; padding:7px 8px; }
        body.receipt-template-monogram .items td { border-bottom:1px solid #e2e8f0; padding:7px 8px; }
        body.receipt-template-executive .top img,
        body.receipt-template-emerald .top img,
        body.receipt-template-monogram .top img { width:48px; height:48px; margin:0; }
        body.receipt-template-executive .invoice-head-left,
        body.receipt-template-emerald .invoice-head-left,
        body.receipt-template-monogram .invoice-head-left { display:flex; align-items:center; gap:12px; }
        body.receipt-template-executive .invoice-info-grid,
        body.receipt-template-emerald .invoice-info-grid,
        body.receipt-template-monogram .invoice-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px 18px; }
        body.receipt-template-executive .invoice-info-grid .row,
        body.receipt-template-emerald .invoice-info-grid .row,
        body.receipt-template-monogram .invoice-info-grid .row { display:grid; grid-template-columns:auto minmax(0,1fr); gap:6px; margin:0; font-size:12px; align-items:center; }
        body.receipt-template-executive .receipt-dev-footer,
        body.receipt-template-emerald .receipt-dev-footer,
        body.receipt-template-monogram .receipt-dev-footer { display:none; }
        body.receipt-template-executive .signature-row,
        body.receipt-template-emerald .signature-row,
        body.receipt-template-monogram .signature-row { display:block; margin-top:14px; color:#111827; text-align:center; }
        body.receipt-template-executive .signature-row span:not(.signature-software),
        body.receipt-template-emerald .signature-row span:not(.signature-software),
        body.receipt-template-monogram .signature-row span:not(.signature-software) { display:none; }
        body:not(.receipt-template-invoice):not(.receipt-template-executive):not(.receipt-template-emerald):not(.receipt-template-monogram) .invoice-box,
        body:not(.receipt-template-invoice):not(.receipt-template-executive):not(.receipt-template-emerald):not(.receipt-template-monogram) .signature-row { display:none; }
        body.receipt-template-invoice .sheet,
        body.receipt-template-executive .sheet,
        body.receipt-template-monogram .sheet { border-color:var(--receipt-accent); }
        body.receipt-template-invoice .sheet::before,
        body.receipt-template-invoice .sheet::after { background:linear-gradient(90deg,#f59e0b,var(--receipt-accent),#f59e0b); }
        body.receipt-template-invoice .invoice-box,
        body.receipt-template-invoice .block,
        body.receipt-template-invoice .items,
        body.receipt-template-invoice .items td { border-color:var(--receipt-accent); }
        body.receipt-template-invoice .invoice-box h4 { color:var(--receipt-accent); }
        body.receipt-template-invoice .items th,
        body.receipt-template-executive .invoice-box,
        body.receipt-template-executive .items th,
        body.receipt-template-monogram .items th { background:var(--receipt-accent); }
        body.receipt-template-executive .top { border-bottom-color:var(--receipt-accent); }
        body.receipt-template-emerald .sheet { border-color:var(--receipt-accent); }
        body.receipt-template-emerald .top { background:linear-gradient(135deg,var(--receipt-accent),#10b981); }
        body.receipt-template-emerald .items th { background:var(--receipt-accent); }
        body.receipt-template-emerald .invoice-box h4 { color:var(--receipt-accent); }
        <?php if ($isDocumentTemplate): ?>
        @page { size: auto; margin: 4mm; }
        <?php else: ?>
        @page { size: <?php echo (int) $paperWidthMm; ?>mm auto; margin: 0; }
        <?php endif; ?>
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; box-sizing: border-box !important; }
            html, body {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                display: block !important;
                text-align: left !important;
            }
            .actions, .no-print { display: none !important; }
            .sheet {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                width: calc(var(--print-width-mm) * 1mm) !important;
                max-width: calc(var(--print-width-mm) * 1mm) !important;
                height: auto !important;
                min-height: 0 !important;
                padding: 1.5mm 1mm 2mm 1mm !important;
                overflow: visible !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                <?php if ($isDocumentTemplate): ?>
                margin: 0 auto !important;
                <?php else: ?>
                margin: 0 !important;
                margin-right: auto !important;
                float: left !important;
                text-align: left !important;
                <?php endif; ?>
            }
            body.receipt-template-invoice .sheet,
            body.receipt-template-executive .sheet,
            body.receipt-template-emerald .sheet,
            body.receipt-template-monogram .sheet { width: calc(var(--print-width-mm) * 1mm) !important; max-width: calc(var(--print-width-mm) * 1mm) !important; border-radius: 0 !important; box-shadow: none !important; padding: 10px !important; margin: 0 auto !important; }
            .top { padding:10px 8px 8px; }
            .top img { width:38px; height:38px; margin-bottom:6px; }
            .title { font-size:16px; margin-bottom:2px; }
            .muted { font-size:10px; }
            .block { padding:8px; }
            .row { margin-bottom:4px; font-size:11px; }
            .row > span:first-child { max-width:48%; }
            .row > strong, .row > span:last-child { max-width:50%; text-align:right; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
            body.receipt-template-invoice .invoice-info-grid,
            body.receipt-template-executive .invoice-info-grid,
            body.receipt-template-emerald .invoice-info-grid,
            body.receipt-template-monogram .invoice-info-grid { display:grid !important; grid-template-columns:1fr 1fr !important; gap:8px 18px !important; }
            body.receipt-template-invoice .invoice-info-grid .row,
            body.receipt-template-executive .invoice-info-grid .row,
            body.receipt-template-emerald .invoice-info-grid .row,
            body.receipt-template-monogram .invoice-info-grid .row { display:grid !important; grid-template-columns:max-content minmax(0, 1fr) !important; gap:6px !important; align-items:center !important; margin:0 !important; }
            body.receipt-template-invoice .invoice-info-grid .row > span:first-child,
            body.receipt-template-executive .invoice-info-grid .row > span:first-child,
            body.receipt-template-emerald .invoice-info-grid .row > span:first-child,
            body.receipt-template-monogram .invoice-info-grid .row > span:first-child { max-width:none !important; white-space:nowrap !important; display:inline !important; }
            body.receipt-template-invoice .invoice-info-grid .row > strong,
            body.receipt-template-executive .invoice-info-grid .row > strong,
            body.receipt-template-emerald .invoice-info-grid .row > strong,
            body.receipt-template-monogram .invoice-info-grid .row > strong { max-width:none !important; text-align:left !important; white-space:nowrap !important; overflow:hidden !important; text-overflow:ellipsis !important; }
            .items { table-layout:fixed; font-size:11px; }
            .items th, .items td { padding:4px 0; }
            .items th:nth-child(1), .items td:nth-child(1) { width:38%; }
            .items th:nth-child(2), .items td:nth-child(2) { width:12%; text-align:center; }
            .items th:nth-child(3), .items td:nth-child(3) { width:25%; text-align:right; padding-left:6px; }
            .items th:nth-child(4), .items td:nth-child(4) { width:25%; text-align:right; padding-left:6px; }
            .items td:first-child { white-space:normal; word-break:break-word; }
            .grand { font-size:14px; }
            .tax-detail-card { border-radius:0; padding:8px; margin-top:6px; }
            .tax-detail-title { font-size:11px; }
            .tax-detail-pill { font-size:9px; padding:3px 6px; }
            .tax-detail-card .row { font-size:10px; }
            .footer { font-size:10px; padding:10px 8px 12px; }
            body.receipt-template-invoice .footer,
            body.receipt-template-executive .footer,
            body.receipt-template-emerald .footer,
            body.receipt-template-monogram .footer { padding-left:10px !important; padding-right:10px !important; }
            .receipt-footer-main { gap:8px; width:100%; box-sizing:border-box; }
            .receipt-verify-qr { width:60px; flex-basis:60px; }
            .receipt-verify-qr img { width:60px; height:60px; }
            .block, .row, table, tr { break-inside: avoid; page-break-inside: avoid; }
            .devaltum-receipt-branding { display:inline-flex !important; align-items:center !important; justify-content:center !important; gap:10px !important; margin:6px auto 2px !important; text-align:left !important; }
            .devaltum-receipt-branding img, .devaltum-receipt-logo { height:40px !important; max-height:45px !important; width:auto !important; max-width:70px !important; display:inline-block !important; object-fit:contain !important; -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }
            .devaltum-receipt-text { display:inline-flex !important; flex-direction:column !important; justify-content:center !important; font-family:'Segoe UI', Arial, -apple-system, BlinkMacSystemFont, Roboto, sans-serif !important; line-height:1.25 !important; }
            .devaltum-brand-line { font-size:11px !important; font-weight:800 !important; color:#000 !important; letter-spacing:0.3px !important; }
            .devaltum-brand-line strong { font-weight:900 !important; color:#000 !important; }
            .devaltum-phone-line { font-size:12.5px !important; font-weight:800 !important; color:#000 !important; margin-top:1px !important; letter-spacing:0.5px !important; }
        }

        /* ========================================================
           BASE MODULAR THERMAL RECEIPT ENGINE (All Thermal Templates)
           ======================================================== */
        body.receipt-template-retail_standard,
        body.receipt-template-classic,
        body.receipt-template-compact,
        body.receipt-template-premium,
        body.receipt-template-thermal_mono {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Inter', Roboto, Arial, sans-serif !important;
            background: #eef2f6;
            margin: 0;
            padding: 24px 12px;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }
        body.receipt-template-retail_standard .sheet,
        body.receipt-template-classic .sheet,
        body.receipt-template-compact .sheet,
        body.receipt-template-premium .sheet,
        body.receipt-template-thermal_mono .sheet {
            width: 320px !important;
            max-width: 320px !important;
            background: #ffffff !important;
            padding: 16px 14px 18px !important;
            margin: 0 auto !important;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08), 0 4px 12px -2px rgba(0, 0, 0, 0.04) !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            box-sizing: border-box !important;
            color: #0f172a !important;
            font-size: <?php echo (($receiptLbl['font_size_preset'] ?? '') === 'compact') ? '9.5px' : ((($receiptLbl['font_size_preset'] ?? '') === 'large') ? '11.5px' : '10.5px'); ?> !important;
        }
        body.receipt-template-retail_standard .sheet *,
        body.receipt-template-classic .sheet *,
        body.receipt-template-compact .sheet *,
        body.receipt-template-premium .sheet *,
        body.receipt-template-thermal_mono .sheet * {
            box-sizing: border-box !important;
            letter-spacing: normal;
        }
        .retail-header-box { text-align: center; margin-bottom: 6px; }
        .retail-no-logo-placeholder { display: inline-flex; flex-direction: column; align-items: center; justify-content: center; width: 86px; height: 36px; border: 1px solid #d1d5db; background: #f8fafc; color: #94a3b8; font-size: 8.5px; font-weight: 600 !important; line-height: 1.2; text-align: center; margin: 0 auto 6px; border-radius: 3px; letter-spacing: 0.3px; }
        .retail-logo-img { max-width: 120px; max-height: 44px; object-fit: contain; display: block; margin: 0 auto 6px; }
        .retail-store-title { font-size: 17px; font-weight: 800 !important; color: #0f172a; margin: 0 0 2px; letter-spacing: 0.2px; line-height: 1.2; text-align: center; }
        .retail-store-address { font-size: 10.5px; font-weight: 400 !important; color: #334155; margin: 0 0 2px; line-height: 1.3; text-align: center; }
        .retail-store-contact { font-size: 11.5px; font-weight: 600 !important; color: #0f172a; margin: 0 0 6px; text-align: center; }
        .retail-ribbon-bar { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #0f172a; border-bottom: 1px solid #0f172a; padding: 4px 2px; margin: 5px 0 7px; font-size: 10.5px; color: #0f172a; }
        .retail-ribbon-bar span { font-size: 10px; font-weight: 500 !important; }
        .retail-ribbon-bar .ribbon-center { font-weight: 800 !important; font-size: 12px; letter-spacing: 0.8px; }
        .retail-meta-box { font-size: 10.5px; line-height: 1.4; margin-bottom: 6px; color: #0f172a; }
        .retail-meta-inv-row { margin-bottom: 2px; display: flex; align-items: center; gap: 4px; font-size: 10.5px; }
        .retail-inv-badge { display: inline-block; border: 1px solid #475569; padding: 1px 6px; font-weight: 600 !important; font-size: 10.5px; border-radius: 2px; letter-spacing: 0.2px; }
        .retail-meta-name-row { margin-bottom: 2px; font-size: 10.5px; color: #0f172a; }
        .retail-cashier-terminal-row { display: flex; justify-content: space-between; align-items: center; font-size: 10px; color: #475569; margin-top: 2px; font-weight: 400 !important; }
        .retail-table-grid { width: 100%; border-collapse: collapse; border: 1px solid #0f172a; font-size: 10.5px; color: #0f172a; margin: 0; }
        .retail-table-grid th { border: 1px solid #0f172a; padding: 4px 4px; font-weight: 700 !important; font-size: 9.5px; text-align: left; background: #f8fafc; color: #0f172a; text-transform: none; }
        .retail-table-grid th.col-price { text-align: center; width: 56px; }
        .retail-table-grid th.col-qty { text-align: center; width: 44px; }
        .retail-table-grid th.col-amount { text-align: right; width: 62px; }
        .retail-table-grid td { border: 1px solid #0f172a; padding: 4px 4px; vertical-align: top; color: #0f172a; font-size: 10px; font-weight: 400 !important; }
        .retail-table-grid td.col-price { text-align: center; font-weight: 500 !important; }
        .retail-table-grid td.col-qty { text-align: center; font-weight: 500 !important; }
        .retail-table-grid td.col-amount { text-align: right; font-weight: 600 !important; }
        .retail-sub-unit { display: block; font-size: 8px; color: #64748b; font-weight: 400 !important; line-height: 1; margin-top: 1px; }
        .retail-summary-row { display: flex; justify-content: space-between; align-items: center; border: 1px solid #0f172a; border-top: none; padding: 3px 6px; font-size: 10px; font-weight: 600 !important; background: #f8fafc; margin-bottom: 7px; color: #0f172a; }
        .retail-totals-container { font-size: 10.5px; line-height: 1.45; color: #0f172a; }
        .retail-total-line { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px; font-size: 10.5px; font-weight: 400 !important; }
        .retail-total-line strong { font-weight: 600 !important; }
        .retail-net-payable-box { display: flex; justify-content: space-between; align-items: center; border: 1.5px solid #0f172a; padding: 4px 6px; margin: 5px 0 3px; font-size: 12px; font-weight: 800 !important; color: #0f172a; border-radius: 2px; background: #fff; }
        .retail-rupees-words { font-size: 8.5px; font-weight: 600 !important; color: #1e293b; text-transform: uppercase; line-height: 1.25; margin: 2px 0 6px; word-break: break-word; text-align: left; letter-spacing: 0.1px; }
        .retail-barcode-holder { margin: 6px auto 2px; text-align: center; width: 100%; }
        .retail-barcode-holder svg { margin: 0 auto; max-width: 230px; display: block; }
        .retail-thanks-line { text-align: center; font-size: 10px; font-weight: 600 !important; margin: 4px 0 6px; color: #0f172a; }
        .retail-dev-credit { text-align: center; border-top: 1px dashed #cbd5e1; padding-top: 5px; margin-top: 6px; line-height: 1.3; }

        /* THEME 1: RETAIL STANDARD (Master Superstore Classic) */
        body.receipt-template-retail_standard .retail-store-title { font-family: 'Times New Roman', Times, Georgia, serif; font-style: italic; }
        body.receipt-template-retail_standard .retail-ribbon-bar .ribbon-center { font-style: italic; text-decoration: underline; }

        /* THEME 2: CLASSIC PRO (Clean Modern Executive Slip) */
        body.receipt-template-classic .retail-ribbon-bar { background: #0f172a; color: #fff; border: none; border-radius: 4px; padding: 4px 6px; }
        body.receipt-template-classic .retail-ribbon-bar span { color: #cbd5e1; }
        body.receipt-template-classic .retail-ribbon-bar .ribbon-center { color: #fff; letter-spacing: 1px; }
        body.receipt-template-classic .retail-table-grid { border: 1px solid #cbd5e1; border-radius: 4px; border-collapse: separate; border-spacing: 0; overflow: hidden; }
        body.receipt-template-classic .retail-table-grid th { border-bottom: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; background: #f1f5f9; color: #0f172a; }
        body.receipt-template-classic .retail-table-grid th:last-child { border-right: none; }
        body.receipt-template-classic .retail-table-grid td { border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; }
        body.receipt-template-classic .retail-table-grid tr:last-child td { border-bottom: none; }
        body.receipt-template-classic .retail-table-grid td:last-child { border-right: none; }
        body.receipt-template-classic .retail-summary-row { border: 1px solid #cbd5e1; border-top: none; background: #f8fafc; border-radius: 0 0 4px 4px; }
        body.receipt-template-classic .retail-net-payable-box { border: 1.5px solid #0f6dff; background: #f8fbff; color: #0f6dff; border-radius: 4px; }

        /* THEME 3: COMPACT 58MM (High-Speed Thermal Slip) */
        body.receipt-template-compact .sheet { width: 280px !important; max-width: 280px !important; font-family: 'Courier New', Courier, monospace !important; border-radius: 4px !important; padding: 10px 8px 12px !important; }
        body.receipt-template-compact .retail-store-title { text-transform: uppercase; font-size: 15px; }
        body.receipt-template-compact .retail-ribbon-bar { border-top: 1px dashed #000; border-bottom: 1px dashed #000; }
        body.receipt-template-compact .retail-table-grid { border: 1px dashed #000; font-family: 'Courier New', Courier, monospace; }
        body.receipt-template-compact .retail-table-grid th, body.receipt-template-compact .retail-table-grid td { border: 1px dashed #000; padding: 3px 2px; }
        body.receipt-template-compact .retail-summary-row { border: 1px dashed #000; border-top: none; }
        body.receipt-template-compact .retail-net-payable-box { border: 1px dashed #000; }

        /* THEME 4: PREMIUM LUXURY (Soft Branded Receipt) */
        body.receipt-template-premium .sheet { border-radius: 14px !important; box-shadow: 0 18px 42px rgba(15, 23, 42, 0.12) !important; border: 1px solid #dbeafe !important; }
        body.receipt-template-premium .retail-ribbon-bar { background: linear-gradient(135deg, #0b1528, #1d4ed8); color: #fff; border: none; border-radius: 6px; padding: 5px 8px; }
        body.receipt-template-premium .retail-ribbon-bar span { color: #dbeafe; font-size: 9px; }
        body.receipt-template-premium .retail-ribbon-bar .ribbon-center { color: #fff; letter-spacing: 1px; }
        body.receipt-template-premium .retail-table-grid { border: 1px solid #bfdbfe; border-radius: 6px; border-collapse: separate; border-spacing: 0; overflow: hidden; }
        body.receipt-template-premium .retail-table-grid th { background: #eff6ff; color: #1e40af; border-bottom: 1px solid #bfdbfe; border-right: 1px solid #bfdbfe; }
        body.receipt-template-premium .retail-table-grid th:last-child { border-right: none; }
        body.receipt-template-premium .retail-table-grid td { border-bottom: 1px solid #dbeafe; border-right: 1px solid #dbeafe; }
        body.receipt-template-premium .retail-table-grid tr:last-child td { border-bottom: none; }
        body.receipt-template-premium .retail-table-grid td:last-child { border-right: none; }
        body.receipt-template-premium .retail-summary-row { background: #f8fafc; border: 1px solid #bfdbfe; border-top: none; border-radius: 0 0 6px 6px; }
        body.receipt-template-premium .retail-net-payable-box { background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1.5px solid #3b82f6; border-radius: 6px; color: #1e40af; }

        /* THEME 5: MONOSPACE VINTAGE */
        body.receipt-template-thermal_mono .sheet { font-family: 'Courier New', Courier, monospace !important; border-radius: 0 !important; }
        body.receipt-template-thermal_mono .retail-store-title { text-transform: uppercase; font-family: 'Courier New', Courier, monospace !important; }
        body.receipt-template-thermal_mono .retail-ribbon-bar { border-top: 1px dashed #000; border-bottom: 1px dashed #000; }
        body.receipt-template-thermal_mono .retail-table-grid { border: 1px dashed #000; }
        body.receipt-template-thermal_mono .retail-table-grid th, body.receipt-template-thermal_mono .retail-table-grid td { border: 1px dashed #000; }
        body.receipt-template-thermal_mono .retail-net-payable-box { border: 1px dashed #000; }

        @page {
            size: auto;
            margin: 0;
        }
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                box-sizing: border-box !important;
            }
            body.receipt-template-retail_standard,
            body.receipt-template-classic,
            body.receipt-template-compact,
            body.receipt-template-premium,
            body.receipt-template-thermal_mono {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #000000 !important;
            }
            body.receipt-template-retail_standard .sheet,
            body.receipt-template-classic .sheet,
            body.receipt-template-compact .sheet,
            body.receipt-template-premium .sheet,
            body.receipt-template-thermal_mono .sheet {
                width: 100% !important;
                max-width: calc(var(--print-width-mm) * 1mm) !important;
                border: none !important;
                box-shadow: none !important;
                padding: 1.5mm 1mm 2mm 1mm !important;
                border-radius: 0 !important;
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                color: #000000 !important;
            }
            /* Universal high contrast text for thermal heads (prevents halftone dot dithering) */
            .retail-store-title {
                color: #000000 !important;
                font-weight: 900 !important;
            }
            .retail-store-address,
            .retail-store-contact,
            .retail-meta-box,
            .retail-meta-box span,
            .retail-cashier-terminal-row,
            .retail-cashier-terminal-row span,
            .retail-thanks-line,
            .retail-rupees-words {
                color: #000000 !important;
                font-weight: 700 !important;
            }
            .retail-inv-badge {
                border: 1px solid #000000 !important;
                color: #000000 !important;
                font-weight: 800 !important;
            }
            /* High contrast table grid for thermal printers */
            .retail-table-grid {
                width: 100% !important;
                border: 1px solid #000000 !important;
                border-collapse: collapse !important;
                color: #000000 !important;
            }
            .retail-table-grid th {
                border: 1px solid #000000 !important;
                background: #f0f0f0 !important;
                color: #000000 !important;
                font-weight: 800 !important;
            }
            .retail-table-grid td {
                border: 1px solid #000000 !important;
                color: #000000 !important;
                font-weight: 600 !important;
            }
            .retail-table-grid td.col-price,
            .retail-table-grid td.col-qty,
            .retail-table-grid td.col-amount {
                font-weight: 700 !important;
                color: #000000 !important;
            }
            .retail-sub-unit {
                color: #000000 !important;
                font-size: 8.5px !important;
                font-weight: 700 !important;
            }
            /* Summary row below items */
            .retail-summary-row {
                border: 1px solid #000000 !important;
                border-top: none !important;
                background: #ffffff !important;
                color: #000000 !important;
                font-weight: 800 !important;
            }
            .retail-summary-row span {
                color: #000000 !important;
                font-weight: 800 !important;
            }
            /* Totals lines */
            .retail-totals-container .retail-total-line {
                color: #000000 !important;
            }
            .retail-totals-container .retail-total-line span,
            .retail-totals-container .retail-total-line strong {
                color: #000000 !important;
                font-weight: 800 !important;
            }
            /* Net payable box - bold solid border and text */
            .retail-net-payable-box {
                border: 2px solid #000000 !important;
                background: #ffffff !important;
                color: #000000 !important;
                font-weight: 900 !important;
            }
            .retail-net-payable-box span {
                color: #000000 !important;
                font-weight: 900 !important;
            }
            /* Ribbon bar styling across thermal themes */
            body.receipt-template-classic .retail-ribbon-bar,
            body.receipt-template-premium .retail-ribbon-bar {
                background: #000000 !important;
                color: #ffffff !important;
                border: none !important;
            }
            body.receipt-template-classic .retail-ribbon-bar span,
            body.receipt-template-premium .retail-ribbon-bar span {
                color: #ffffff !important;
                font-weight: 800 !important;
            }
            body.receipt-template-retail_standard .retail-ribbon-bar,
            body.receipt-template-compact .retail-ribbon-bar,
            body.receipt-template-thermal_mono .retail-ribbon-bar {
                background: transparent !important;
                border-top: 1px solid #000000 !important;
                border-bottom: 1px solid #000000 !important;
                color: #000000 !important;
            }
            body.receipt-template-retail_standard .retail-ribbon-bar span,
            body.receipt-template-compact .retail-ribbon-bar span,
            body.receipt-template-thermal_mono .retail-ribbon-bar span {
                color: #000000 !important;
                font-weight: 800 !important;
            }
            /* Barcode vector sharpness */
            .retail-barcode-holder svg {
                max-width: 240px !important;
                margin: 0 auto !important;
                display: block !important;
            }
            .retail-barcode-holder svg text {
                font-weight: 800 !important;
                fill: #000000 !important;
            }
            /* DevAltum branding high-contrast */
            .retail-dev-credit {
                border-top: 1px dashed #000000 !important;
            }
            .actions, .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="receipt-template-<?php echo Security::escape($receiptTemplate); ?> receipt-template-<?php echo Security::escape($receiptTemplateKey); ?>" data-template-key="<?php echo Security::escape($receiptTemplateKey); ?>" style="<?php echo Security::escape($receiptTemplateVars); ?>">
    <div class="sheet">
        <?php 
        $isDocTemplate = in_array($receiptTemplate, ['invoice', 'executive', 'emerald', 'monogram'], true) 
            || in_array($receiptTemplateKey, ['invoice', 'executive', 'emerald', 'monogram'], true)
            || (isset($receiptTemplateMeta['group']) && $receiptTemplateMeta['group'] === 'document');
        $hasCustomBlock = trim((string) ($receiptTemplateMeta['html_block'] ?? '')) !== '';
        ?>
        <?php if (!$isDocTemplate && !$hasCustomBlock): ?>
            <?php foreach ($receiptOrder as $secKey): ?>
                <?php if ($secKey === 'header'): ?>
                    <?php if (!empty($receiptVis['show_store_name']) || !empty($receiptVis['show_logo']) || !empty($receiptVis['show_address']) || !empty($receiptVis['show_phone']) || !empty($receiptVis['show_ntn']) || !empty($receiptVis['show_tagline'])): ?>
                        <div class="retail-header-box">
                            <?php if (!empty($receiptVis['show_logo'])): ?>
                                <?php if ($receiptLogo): ?>
                                    <img class="retail-logo-img" src="<?php echo Security::escape($receiptLogo); ?>" alt="Store Logo" crossorigin="anonymous" onerror="this.onerror=null; this.style.display='none';">
                                <?php else: ?>
                                    <div class="retail-no-logo-placeholder">NO LOGO<br>AVAILABLE</div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($receiptVis['show_store_name'])): ?>
                                <div class="retail-store-title"><?php echo Security::escape(!empty($receiptLbl['store_name_override']) ? $receiptLbl['store_name_override'] : $companyName); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($receiptVis['show_tagline']) && !empty($settings['company_tagline'])): ?>
                                <div style="font-size: 9.5px; color: #475569; margin: 0 0 2px; text-align: center;"><?php echo Security::escape($settings['company_tagline']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($receiptVis['show_address']) && $companyAddress): ?>
                                <div class="retail-store-address"><?php echo Security::escape($companyAddress); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($receiptVis['show_phone']) && $storePhone): ?>
                                <div class="retail-store-contact"><?php echo Security::escape($storePhone); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($receiptVis['show_ntn']) && $companyNtn): ?>
                                <div style="font-size: 9px; color: #64748b; margin-top: -3px; margin-bottom: 4px; text-align: center;">NTN : <?php echo Security::escape($companyNtn); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($secKey === 'ribbon'): ?>
                    <?php if (!empty($receiptVis['show_ribbon'])): ?>
                        <div class="retail-ribbon-bar">
                            <span><?php echo date('g:i:sA', strtotime($invoice['created_at'])); ?></span>
                            <span class="ribbon-center"><?php echo Security::escape($receiptLbl['title'] ?: 'INVOICE'); ?></span>
                            <span><?php echo date('d/m/Y', strtotime($invoice['created_at'])); ?></span>
                        </div>
                    <?php endif; ?>

                <?php elseif ($secKey === 'meta'): ?>
                    <?php if (!empty($receiptVis['show_invoice_no']) || !empty($receiptVis['show_customer']) || !empty($receiptVis['show_cashier']) || !empty($receiptVis['show_counter'])): ?>
                        <div class="retail-meta-box">
                            <?php if (!empty($receiptVis['show_invoice_no'])): ?>
                                <div class="retail-meta-inv-row">
                                    <span><?php echo Security::escape(rtrim($receiptLbl['invoice_prefix'] ?: 'Inv #:') . ' '); ?></span><span class="retail-inv-badge"><?php echo Security::escape($invoice['custom_id']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($receiptVis['show_customer'])): ?>
                                <div class="retail-meta-name-row">
                                    <span><?php echo Security::escape(rtrim($receiptLbl['customer_label'] ?: 'Name:') . ' '); ?></span><span><?php echo Security::escape($customerName ?: 'Cash Sale'); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($receiptVis['show_cashier']) || !empty($receiptVis['show_counter'])): ?>
                                <div class="retail-cashier-terminal-row">
                                    <span><?php echo !empty($receiptVis['show_cashier']) ? Security::escape(($receiptLbl['cashier_label'] ?: '') . ' ' . ($invoice['cashier'] ?: 'admin')) : ''; ?></span>
                                    <span><?php echo !empty($receiptVis['show_counter']) ? Security::escape($receiptLbl['counter_label'] ?: $terminalName) : ''; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($secKey === 'items'): ?>
                    <table class="retail-table-grid">
                        <thead>
                            <tr>
                                <th>Item Description</th>
                                <th class="col-price">Price</th>
                                <th class="col-qty">Qty.</th>
                                <th class="col-amount">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo Security::escape($item['product_name']); ?></td>
                                    <td class="col-price">
                                        <?php echo number_format((float) $item['price'], 2); ?>
                                        <?php if (!empty($receiptVis['show_unit_label'])): ?>
                                            <span class="retail-sub-unit"><?php echo Security::escape($item['product_unit_label'] ?? 'Sales'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-qty"><?php echo number_format((float) $item['quantity'], 2); ?></td>
                                    <td class="col-amount"><?php echo number_format((float) $item['total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif ($secKey === 'summary'): ?>
                    <?php if (!empty($receiptVis['show_item_counters'])): ?>
                        <div class="retail-summary-row">
                            <span>Total Items : <?php echo (int) count($items); ?></span>
                            <span>No. Of Qty. : <?php echo number_format((float) $totalQtySum, 2); ?></span>
                        </div>
                    <?php endif; ?>

                <?php elseif ($secKey === 'totals'): ?>
                    <div class="retail-totals-container">
                        <?php if (!empty($receiptVis['show_subtotal'])): ?>
                            <div class="retail-total-line">
                                <span>Sub Total Amount:</span>
                                <strong><?php echo number_format((float) ($invoice['subtotal'] ?? $invoiceTotal), 2); ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($receiptVis['show_discount']) && (float) ($invoice['discount'] ?? 0) > 0): ?>
                            <div class="retail-total-line">
                                <span>Discount :</span>
                                <strong><?php echo number_format((float) $invoice['discount'], 2); ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($receiptVis['show_tax']) && $taxAmount > 0): ?>
                            <div class="retail-total-line">
                                <span><?php echo Security::escape($taxLabel); ?> (<?php echo rtrim(rtrim(number_format($taxRate, 2), '0'), '.'); ?>%):</span>
                                <strong><?php echo number_format($taxAmount, 2); ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($receiptVis['show_paid'])): ?>
                            <div class="retail-total-line">
                                <span>Paid Amount :</span>
                                <strong><?php echo number_format((float) $displayPaid, 2); ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($receiptVis['show_due']) && $openDue > 0): ?>
                            <div class="retail-total-line">
                                <span>Due Amount :</span>
                                <strong><?php echo number_format((float) $openDue, 2); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($secKey === 'net_payable'): ?>
                    <?php if (!empty($receiptVis['show_net_payable'])): ?>
                        <div class="retail-net-payable-box">
                            <span><?php echo Security::escape($receiptLbl['net_payable_label'] ?: 'Net Payable:'); ?></span>
                            <span><?php echo number_format((float) $invoiceTotal, 2); ?></span>
                        </div>
                    <?php endif; ?>

                <?php elseif ($secKey === 'words'): ?>
                    <?php if (!empty($receiptVis['show_words'])): ?>
                        <div class="retail-rupees-words">
                            <?php 
                            $wordsPrefix = trim((string) ($receiptLbl['words_prefix'] ?? ''));
                            if ($wordsPrefix === '' || strcasecmp($wordsPrefix, 'Rupees') === 0) {
                                echo App::numberToWordsRupees($invoiceTotal);
                            } else {
                                echo Security::escape($wordsPrefix) . ': ' . App::convertIntegerToWords((int) $invoiceTotal) . ' Only';
                            }
                            ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($secKey === 'barcode'): ?>
                    <?php if (!empty($receiptVis['show_barcode'])): ?>
                        <div class="retail-barcode-holder">
                            <?php echo BarcodeHelper::renderSvg((string) ($invoice['custom_id'] ?? 'INV-1'), 1.3, 34, true); ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($secKey === 'footer'): ?>
                    <?php if (!empty($receiptVis['show_greetings'])): ?>
                        <div class="retail-thanks-line"><?php echo Security::escape($receiptLbl['greetings'] ?: 'Thanks For Shopping With Us!'); ?></div>
                        <?php if (!empty($receiptLbl['return_policy'])): ?>
                            <div style="text-align: center; font-size: 8.5px; color: #475569; margin: 2px 0 4px; line-height: 1.25;">
                                <?php echo nl2br(Security::escape($receiptLbl['return_policy'])); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="retail-dev-credit">
                        <?php echo App::devAltumReceiptFooterHtml(); ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php elseif (trim((string) ($receiptTemplateMeta['html_block'] ?? '')) !== ''): ?>
            <?php echo ReceiptTemplates::renderHtmlBlock((string) $receiptTemplateMeta['html_block'], [
                'company_name' => $companyName,
                'company_address' => $companyAddress,
                'whatsapp' => $companyWhatsapp,
                'invoice_no' => (string) ($invoice['custom_id'] ?? ''),
                'date' => date('M d, Y', strtotime($invoice['created_at'])),
                'time' => date('h:i A', strtotime($invoice['created_at'])),
                'cashier' => (string) ($invoice['cashier'] ?? ''),
                'customer' => (string) ($customerName ?: 'Walk-in Customer'),
                'phone' => (string) ($customerPhone ?: '-'),
                'status' => $receiptStatus,
                'payment' => ucfirst((string) ($invoice['payment_method'] ?? 'cash')),
                'channel' => ucfirst((string) ($invoice['sale_channel'] ?? 'retail')),
                'items' => $items,
                'subtotal' => (float) ($invoice['subtotal'] ?? 0),
                'discount' => (float) ($invoice['discount'] ?? 0),
                'tax_amount' => $taxAmount,
                'tax_rate' => $taxRate,
                'tax_label' => $taxLabel,
                'fbr_pos_id' => $fbrPosId,
                'total' => (float) ($invoice['total'] ?? 0),
                'paid' => (float) $displayPaid,
                'remaining' => (float) $openDue,
                'footer' => $footerText,
                'qr' => '<img src="' . Security::escape($verifyQrUrl) . '" alt="Invoice PDF QR" style="width:72px;height:72px;object-fit:contain;">',
            ]); ?>
        <?php else: ?>
        <div class="top">
            <div class="invoice-head-left">
                    <?php if ($receiptLogo): ?><img src="<?php echo Security::escape($receiptLogo); ?>" alt="Logo" onerror="this.onerror=null; this.style.display='none';"><?php endif; ?>
                <div>
                    <div class="title"><?php echo Security::escape($companyName); ?></div>
                    <div class="muted thermal-invoice-number">
                        <span>Invoice: <?php echo Security::escape($invoice['custom_id']); ?></span>
                        <span class="thermal-date"><?php echo date('M d, Y h:i A', strtotime($invoice['created_at'])); ?></span>
                    </div>
                    <?php if ($companyAddress): ?><div class="muted company-header-meta"><?php echo Security::escape($companyAddress); ?></div><?php endif; ?>
                    <?php if ($companyWhatsapp): ?><div class="muted company-header-meta">Mob: <?php echo Security::escape($companyWhatsapp); ?></div><?php endif; ?>
                    <?php if ($companyNtn): ?><div class="muted company-header-meta">NTN : <?php echo Security::escape($companyNtn); ?></div><?php endif; ?>
                </div>
            </div>
            <div class="invoice-box">
                <h4>INVOICE</h4>
                <div class="muted"><strong>No:</strong> <?php echo Security::escape($invoice['custom_id']); ?></div>
                <div class="muted"><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($invoice['created_at'])); ?></div>
            </div>
        </div>

        <div class="block invoice-info-grid">
            <div class="row"><span>Cashier :</span><strong><?php echo Security::escape($invoice['cashier']); ?></strong></div>
            <div class="row"><span>Customer :</span><strong><?php echo Security::escape($customerName ?: 'Walk-in'); ?></strong></div>
            <div class="row"><span>Phone :</span><strong><?php echo Security::escape($customerPhone ?: '-'); ?></strong></div>
            <div class="row"><span>Status :</span><strong><?php echo $receiptStatus; ?></strong></div>
            <div class="row"><span>Payment :</span><strong><?php echo ucfirst(Security::escape($invoice['payment_method'] ?? 'cash')); ?></strong></div>
            <div class="row"><span>Channel :</span><strong><?php echo ucfirst(Security::escape($invoice['sale_channel'] ?? 'retail')); ?></strong></div>
            <?php if ($fbrPosId !== ''): ?>
                <div class="row"><span>FBR POS :</span><strong><?php echo Security::escape($fbrPosId); ?></strong></div>
            <?php endif; ?>
            <?php if ($taxNumber !== ''): ?>
                <div class="row"><span><?php echo Security::escape($taxRegistrationType); ?> :</span><strong><?php echo Security::escape($taxNumber); ?></strong></div>
            <?php endif; ?>
        </div>

        <div class="block">
            <table class="items">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="receipt-item-name">
                                    <span class="receipt-item-title"><?php echo Security::escape($item['product_name']); ?></span>
                                    <span class="receipt-item-unit"><?php echo Security::escape($item['product_unit_label'] ?? App::formatProductUnit(1, 'pcs')); ?></span>
                                </div>
                            </td>
                            <td><?php echo Security::escape(App::formatQuantity($item['quantity'])); ?></td>
                            <td><?php echo App::money($item['price']); ?></td>
                            <td><?php echo App::money($item['total']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="block totals">
            <div class="row"><span>Subtotal</span><strong><?php echo App::money($invoice['subtotal']); ?></strong></div>
            <div class="row"><span>Discount</span><strong><?php echo App::money($invoice['discount']); ?></strong></div>
            <?php if ($taxAmount > 0): ?>
                <div class="row"><span><?php echo Security::escape($taxLabel ?: 'Tax'); ?> (<?php echo rtrim(rtrim(number_format($taxRate, 3), '0'), '.'); ?>%)</span><strong><?php echo App::money($taxAmount); ?></strong></div>
                <div class="tax-detail-card">
                    <div class="tax-detail-title">
                        <span>Tax Details</span>
                        <span class="tax-detail-pill"><?php echo Security::escape($taxLabel ?: 'Tax'); ?></span>
                    </div>
                    <div class="row"><span>Taxable Amount</span><strong><?php echo App::money($taxableAmount); ?></strong></div>
                    <div class="row"><span>Tax Rate</span><strong><?php echo rtrim(rtrim(number_format($taxRate, 3), '0'), '.'); ?>%</strong></div>
                    <div class="row"><span>Tax Charged</span><strong><?php echo App::money($taxAmount); ?></strong></div>
                    <?php if ($taxNumber !== ''): ?>
                        <div class="row"><span><?php echo Security::escape($taxRegistrationType); ?></span><strong><?php echo Security::escape($taxNumber); ?></strong></div>
                    <?php endif; ?>
                    <?php if ($fbrPosId !== ''): ?>
                        <div class="row"><span>FBR POS ID</span><strong><?php echo Security::escape($fbrPosId); ?></strong></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="row grand"><span>Total</span><span><?php echo App::money($invoice['total']); ?></span></div>
            <div class="row" style="padding-top:8px; margin-top:8px; border-top:1px dashed #d1d5db;"><span>Total Items</span><strong><?php echo (int) $totalItems; ?></strong></div>
            <?php if ($isCredit): ?>
                <div class="row"><span>Paid</span><strong><?php echo App::money($displayPaid); ?></strong></div>
                <div class="row"><span>Remaining Balance</span><strong><?php echo App::money($openDue); ?></strong></div>
                <div class="row"><span>Due Date</span><strong><?php echo !empty($invoice['credit_due_date']) ? date('M d, Y h:i A', strtotime($invoice['credit_due_date'])) : '-'; ?></strong></div>
            <?php else: ?>
                <div class="row"><span>Paid</span><strong><?php echo App::money($displayPaid); ?></strong></div>
                <div class="row"><span>Remaining</span><strong><?php echo App::money($openDue); ?></strong></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($invoice['notes'])): ?>
            <div class="block"><div class="muted">Note</div><div style="margin-top:6px; font-size:13px;"><?php echo Security::escape($invoice['notes']); ?></div></div>
        <?php endif; ?>
        <div class="footer">
            <div class="receipt-footer-main">
                <div class="receipt-footer-copy">
                    <?php if ($taxAmount > 0 && $fbrNote !== ''): ?>
                        <strong><?php echo Security::escape($fbrNote); ?></strong><br>
                    <?php endif; ?>
                    <strong><?php echo Security::escape($footerText); ?></strong><br>
                    <?php if ($companyWhatsapp): ?>WhatsApp: <?php echo Security::escape($companyWhatsapp); ?><?php endif; ?>
                    <?php if ($companyAddress): ?><br>Address: <?php echo Security::escape($companyAddress); ?><?php endif; ?>
                </div>
                <div class="receipt-verify-qr">
                    <img src="<?php echo Security::escape($verifyQrUrl); ?>" alt="Invoice PDF QR" crossorigin="anonymous">
                </div>
            </div>
            <hr style="border: none; border-top: 1px dashed #d1d5db; margin: 12px 0;">
            <div class="receipt-dev-footer">
                <?php echo App::devAltumReceiptFooterHtml(); ?>
            </div>
            <div class="signature-row">
                <span class="signature-software"><?php echo App::devAltumReceiptFooterHtml(); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="actions" data-html2canvas-ignore="true">
            <button class="primary" onclick="printReceipt()">Print Receipt</button>
            <?php 
                $isMobileView = !empty($_GET['mobile']) || !empty($_GET['mobile_app']) || !empty($_GET['code']) || !empty($_GET['token']);
                if (!$isMobileView): 
            ?>
                <button class="secondary" type="button" id="download-receipt-pdf" data-pdf-fallback="<?php echo Security::escape($pdfUrl); ?>">Download PDF</button>
                <button class="secondary" type="button" id="download-receipt-image" data-image-fallback="<?php echo Security::escape($imageUrl); ?>">Download Image</button>
                <?php if ($customerWa): ?>
                    <button class="secondary" type="button" id="share-receipt-image">Send receipt image on WhatsApp</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <script src="<?php echo $baseUrl; ?>/assets/vendor/html2canvas.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/assets/vendor/jspdf.umd.min.js"></script>
    <script>
        const query = new URLSearchParams(window.location.search);
        const forcedPrinter = query.get('printer');
        const isDocumentTemplate = ['receipt-template-invoice', 'receipt-template-executive', 'receipt-template-emerald', 'receipt-template-monogram'].some((className) => document.body.classList.contains(className));
        const likelyThermal = !isDocumentTemplate;
        const forcedPaper = (query.get('paper') || '').trim();
        const templatePaperWidthMm = <?php echo (int) $paperWidthMm; ?>;
        const is58mm = <?php echo $is58mm ? 'true' : 'false'; ?> || forcedPaper === '58';
        const printWidthMm = <?php echo (int) $printableWidthMm; ?>;
        document.documentElement.style.setProperty('--print-width-mm', String(printWidthMm));
        const applyDynamicPrintPageSize = () => {
            const sheet = document.querySelector('.sheet');
            if (!sheet) return;
            let styleTag = document.getElementById('dynamic-print-page-size');
            if (!styleTag) {
                styleTag = document.createElement('style');
                styleTag.id = 'dynamic-print-page-size';
                document.head.appendChild(styleTag);
            }

            const pxToMm = (px) => (px * 25.4) / 96;

            // Measure exact content height without action buttons
            const measuringHost = document.createElement('div');
            const clone = sheet.cloneNode(true);
            clone.querySelector('.actions')?.remove();
            clone.querySelector('.no-print')?.remove();
            measuringHost.style.cssText = `position:fixed;left:-10000px;top:0;width:${sheet.offsetWidth}px;visibility:hidden;pointer-events:none;`;
            clone.style.width = `${sheet.offsetWidth}px`;
            clone.style.maxWidth = `${sheet.offsetWidth}px`;
            clone.style.height = 'auto';
            measuringHost.appendChild(clone);
            document.body.appendChild(measuringHost);
            const exactHeightMm = Math.ceil(pxToMm(clone.getBoundingClientRect().height || clone.scrollHeight));
            measuringHost.remove();

            if (isDocumentTemplate) {
                const contentWidthMm = Math.ceil(pxToMm(sheet.getBoundingClientRect().width)) + 2;
                const sideMarginMm = 4;
                const topMarginMm = 4;
                const pageContentHeightMm = Math.max(45, Math.min(exactHeightMm, 700));
                const pageWidthMm = contentWidthMm + (sideMarginMm * 2);
                const pageHeightMm = pageContentHeightMm + (topMarginMm * 2);
                styleTag.textContent = `@page { size: ${pageWidthMm}mm ${pageHeightMm}mm; margin: 0; } @media print { *{box-sizing:border-box!important;} html,body{width:${pageWidthMm}mm!important;height:${pageHeightMm}mm!important;min-height:0!important;overflow:hidden!important;} body{display:flex!important;justify-content:center!important;align-items:flex-start!important;padding:${topMarginMm}mm ${sideMarginMm}mm!important;margin:0!important;background:#fff!important;} .sheet { width:${contentWidthMm}mm !important; max-width:${contentWidthMm}mm !important; height:auto!important; min-height:0!important; margin:0 auto!important; overflow:hidden!important; box-shadow:none!important; border-radius:0!important; } .actions,.no-print{display:none!important;} }`;
            } else {
                // THERMAL RECEIPTS (80mm & 58mm roll):
                // Set @page size cleanly without invalid !important, strictly left-aligned
                const paperWidthMm = is58mm ? 58 : 80;
                const contentWidthMm = is58mm ? 48 : 72;
                const pageHeightMm = Math.max(35, exactHeightMm + 2);

                styleTag.textContent = `@page { size: ${paperWidthMm}mm ${pageHeightMm}mm; margin: 0; } @media print { *{box-sizing:border-box!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;} html,body{width:100%!important;margin:0!important;padding:0!important;background:#fff!important;display:block!important;} .sheet{width:${contentWidthMm}mm!important;max-width:${contentWidthMm}mm!important;min-width:0!important;margin:0 auto!important;padding:1.5mm 1mm 2mm 1mm!important;height:auto!important;min-height:0!important;box-shadow:none!important;border:none!important;border-radius:0!important;page-break-after:avoid!important;break-after:avoid!important;page-break-inside:avoid!important;break-inside:avoid!important;overflow:visible!important;} .actions,.no-print{display:none!important;} }`;
            }
        };

        if (likelyThermal) {
            document.body.classList.add('thermal');
        }
        document.addEventListener('DOMContentLoaded', () => {
            try { applyDynamicPrintPageSize(); } catch (e) {}
        });
        window.addEventListener('load', () => {
            try { applyDynamicPrintPageSize(); } catch (e) {}
        });
        window.addEventListener('beforeprint', () => {
            try { applyDynamicPrintPageSize(); } catch (e) {}
        });
        window.printReceipt = () => {
            try { applyDynamicPrintPageSize(); } catch (e) {}
            setTimeout(() => {
                window.focus();
                window.print();
            }, 60);
        };

        const printQuery = new URLSearchParams(window.location.search);
        if (printQuery.get('direct_print') === '1') {
            window.addEventListener('load', () => {
                setTimeout(() => {
                    window.printReceipt();
                }, 150);
            });
        }

        const shareReceiptButton = document.getElementById('share-receipt-image');
        const downloadReceiptImageButton = document.getElementById('download-receipt-image');
        const downloadReceiptPdfButton = document.getElementById('download-receipt-pdf');
        const waitForReceiptAssets = async () => {
            const images = Array.from(document.querySelectorAll('.sheet img'));
            await Promise.all(images.map((img) => {
                if (img.complete && img.naturalWidth > 0) return Promise.resolve();
                return new Promise((resolve) => {
                    img.onload = resolve;
                    img.onerror = resolve;
                    setTimeout(resolve, 2500);
                });
            }));
        };

        const buildReceiptCanvas = async () => {
            await waitForReceiptAssets();
            if (!window.html2canvas) {
                throw new Error('html2canvas is not loaded.');
            }
            if (document.fonts?.ready) {
                await document.fonts.ready;
            }
            const sourceSheet = document.querySelector('.sheet');
            if (!sourceSheet) {
                throw new Error('Receipt sheet not found.');
            }

            const scale = Math.min(3, Math.max(2, window.devicePixelRatio || 2));
            const width = Math.ceil(sourceSheet.offsetWidth);
            const height = Math.ceil(sourceSheet.offsetHeight);

            const actions = sourceSheet.querySelector('.actions');
            const prevActionsDisplay = actions ? actions.style.display : '';
            const prevBoxShadow = sourceSheet.style.boxShadow;
            const prevBorderRadius = sourceSheet.style.borderRadius;

            // Clean borders & shadows for exact print/image representation
            sourceSheet.style.boxShadow = 'none';
            if (actions) actions.style.display = 'none';

            try {
                const canvas = await window.html2canvas(sourceSheet, {
                    scale,
                    backgroundColor: '#ffffff',
                    useCORS: true,
                    allowTaint: false,
                    logging: false,
                    scrollX: 0,
                    scrollY: 0,
                    width: width,
                    height: height
                });
                canvas.dataset.captureScale = String(scale);
                canvas.dataset.cssWidth = String(width);
                canvas.dataset.cssHeight = String(height);
                return canvas;
            } finally {
                sourceSheet.style.boxShadow = prevBoxShadow;
                sourceSheet.style.borderRadius = prevBorderRadius;
                if (actions) actions.style.display = prevActionsDisplay;
            }
        };

        const buildReceiptImageBlob = async () => {
            const canvas = await buildReceiptCanvas();
            return await new Promise((resolve) => canvas.toBlob(resolve, 'image/png', 0.98));
        };

        const downloadReceiptImage = async () => {
            const blob = await buildReceiptImageBlob();
            if (!blob) {
                throw new Error('Could not create receipt image.');
            }
            const imageUrl = URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');
            downloadLink.href = imageUrl;
            downloadLink.download = 'receipt-<?php echo Security::escape($receiptFileName); ?>.png';
            downloadLink.click();
            setTimeout(() => URL.revokeObjectURL(imageUrl), 60000);
        };

        const buildSnapshotPdfBlob = async (canvas) => {
            const jsPdfCtor = window.jspdf?.jsPDF;
            if (!jsPdfCtor) {
                throw new Error('jsPDF is not loaded.');
            }
            const scale = Number(canvas.dataset.captureScale || 1);
            const cssWidth = Math.ceil(Number(canvas.dataset.cssWidth) || (canvas.width / scale));
            const cssHeight = Math.ceil(Number(canvas.dataset.cssHeight) || (canvas.height / scale));
            const pdf = new jsPdfCtor({
                orientation: cssWidth >= cssHeight ? 'landscape' : 'portrait',
                unit: 'px',
                format: [cssWidth, cssHeight],
                compress: true,
                hotfixes: ['px_scaling']
            });
            pdf.addImage(canvas.toDataURL('image/png', 0.98), 'PNG', 0, 0, cssWidth, cssHeight, undefined, 'FAST');
            return pdf.output('blob');
        };

        const downloadSnapshotPdf = async (openAfterDownload = false) => {
            try {
                const canvas = await buildReceiptCanvas();
                const pdfBlob = await buildSnapshotPdfBlob(canvas);
                const pdfUrl = URL.createObjectURL(pdfBlob);
                const link = document.createElement('a');
                link.href = pdfUrl;
                link.download = 'receipt-<?php echo Security::escape($receiptFileName); ?>.pdf';
                link.click();
                if (openAfterDownload) {
                    setTimeout(() => {
                        try { window.location.href = pdfUrl; } catch (error) {}
                    }, 250);
                }
                setTimeout(() => URL.revokeObjectURL(pdfUrl), 60000);
            } catch (error) {
                console.warn('PDF render error, falling back:', error);
                const fallback = downloadReceiptPdfButton?.getAttribute('data-pdf-fallback');
                if (fallback) window.location.href = fallback;
            }
        };

        if (downloadReceiptPdfButton) {
            downloadReceiptPdfButton.addEventListener('click', (e) => {
                e.preventDefault();
                downloadSnapshotPdf(false);
            });
        }
        if (downloadReceiptImageButton) {
            downloadReceiptImageButton.addEventListener('click', async (e) => {
                e.preventDefault();
                try {
                    await downloadReceiptImage();
                } catch (error) {
                    console.warn('Image render error, falling back:', error);
                    const fallback = downloadReceiptImageButton?.getAttribute('data-image-fallback');
                    if (fallback) window.location.href = fallback;
                }
            });
        }

        if (query.get('auto_pdf') === '1') {
            setTimeout(() => downloadSnapshotPdf(true), 450);
        }

        shareReceiptButton?.addEventListener('click', async () => {
            try {
                const blob = await buildReceiptImageBlob();
                if (!blob) {
                    throw new Error('Could not create receipt image.');
                }
                const file = new File([blob], 'receipt-<?php echo Security::escape($receiptFileName); ?>.png', { type: 'image/png' });
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        files: [file],
                        title: 'Receipt <?php echo Security::escape($invoice['custom_id']); ?>',
                        text: 'Receipt from <?php echo Security::escape($companyName); ?>'
                    });
                    return;
                }
                await downloadReceiptImage();
            } catch (error) {
                const fallback = downloadReceiptImageButton?.getAttribute('data-image-fallback');
                if (fallback) window.location.href = fallback;
            }
        });
    </script>
</body>
</html>
