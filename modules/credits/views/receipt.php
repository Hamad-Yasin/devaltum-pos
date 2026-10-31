$rawReceiptLogo = trim((string) App::setting('receipt_logo', ''));
if ($rawReceiptLogo === '' || !App::mediaExists($rawReceiptLogo)) {
    $fallbackWebLogo = trim((string) App::setting('website_logo', ''));
    if ($fallbackWebLogo !== '' && App::mediaExists($fallbackWebLogo)) {
        $rawReceiptLogo = $fallbackWebLogo;
    }
}
if ($rawReceiptLogo === '' || !App::mediaExists($rawReceiptLogo)) {
    $fallbackCustomLogo = trim((string) App::setting('devaltum_custom_logo', ''));
    if ($fallbackCustomLogo !== '' && App::mediaExists($fallbackCustomLogo)) {
        $rawReceiptLogo = $fallbackCustomLogo;
    }
}
$receiptLogo = ($rawReceiptLogo !== '' && App::mediaExists($rawReceiptLogo)) ? App::mediaUrl($rawReceiptLogo) : '';
$companyName = App::setting('company_name', 'DevAltum POS');
$companyAddress = App::setting('company_address', '');
$companyNtn = trim((string) App::setting('company_ntn', ''));
if ($companyNtn === '') {
    $companyNtn = trim((string) App::setting('company_ntn_number', App::setting('ntn', '')));
}
$companyWhatsapp = App::setting('whatsapp_number', '');
$footerText = App::setting('receipt_footer', 'Thank you for choosing us. For support, please contact our store team.');
$receiptTemplateMeta = ReceiptTemplates::resolve(App::setting('receipt_template', 'classic'));
$receiptTemplateKey = $receiptTemplateMeta['template_key'];
$receiptTemplate = $receiptTemplateMeta['base_template'];
$receiptTemplateVars = ReceiptTemplates::cssVariables($receiptTemplateMeta);
$customerPhone = preg_replace('/[^0-9]/', '', (string) ($payment['customer_phone_snapshot'] ?? ''));
$customerWa = $customerPhone;
if (strpos($customerWa, '0') === 0) {
    $customerWa = '92' . substr($customerWa, 1);
}
$baseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$basePath = defined('BASE_URL') ? BASE_URL : '';
$pdfUrl = $basePath . '/credits/receipt/' . (int) $payment['id'] . '?download_pdf=1';
$imageUrl = $basePath . '/credits/receipt/' . (int) $payment['id'] . '?download_image=1';
$autoPdfUrl = $baseUrl . $pdfUrl;
$verifyQrUrl = App::qrImageUrl($autoPdfUrl, 220, 8);
$receiptFileName = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) ($payment['custom_id'] ?? 'credit_receipt'));
$invoiceItems = $invoiceItems ?? [];
$openDue = max(0, (float) ($payment['open_due'] ?? $payment['balance_due'] ?? 0));
$totalItems = count($invoiceItems);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Payment Receipt</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/fonts/fonts.css">
    <style>
        :root { --print-width-mm: 56; }
        body { font-family: var(--receipt-font), 'Trebuchet MS', Arial, sans-serif; background:#f3f4f6; margin:0; padding:24px; color:#111827; }
        .sheet { width: 380px; margin:0 auto; background:#fff; box-shadow:0 18px 40px rgba(17,24,39,.14); border-radius:18px; overflow:hidden; box-sizing:border-box; }
        .sheet, .sheet * { font-weight:700; }
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
        .grand { font-size:16px; font-weight:700; padding-top:8px; border-top:1px dashed #d1d5db; }
        .footer { padding:14px 18px 18px; font-size:12px; color:#4b5563; line-height:1.45; }
        .receipt-footer-main { display:flex; align-items:center; justify-content:space-between; gap:12px; text-align:left; }
        .receipt-footer-copy { min-width:0; flex:1; }
        .receipt-footer-copy strong { color:#1f2937; }
        .receipt-verify-qr { width:72px; flex:0 0 72px; text-align:center; }
        .receipt-verify-qr img { width:72px; height:72px; object-fit:contain; display:block; margin-left:auto; border:0; border-radius:0; padding:0; background:transparent; }
        .receipt-dev-footer { font-weight:600; font-size:11px; text-align:center; color:#111827; }
        .actions { display:flex; flex-direction:column; gap:10px; padding:0 18px 18px; }
        .actions a, .actions button { border:none; border-radius:12px; padding:10px 14px; text-decoration:none; cursor:pointer; font-size:13px; text-align:center; }
        .primary { background:#111827; color:#fff; }
        .secondary { background:#e5e7eb; color:#111827; }
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
        @page { size: 56mm auto; margin: 0; }
        @media print {
            html, body { width:auto !important; height:auto !important; }
            body { background:#fff; padding:0; margin:0; overflow:hidden; }
            .sheet { box-shadow:none !important; border-radius:0 !important; width:calc(var(--print-width-mm) * 1mm); max-width:calc(var(--print-width-mm) * 1mm); height:auto !important; min-height:0 !important; margin:0 auto; overflow:visible; }
            body.receipt-template-invoice .sheet,
            body.receipt-template-executive .sheet,
            body.receipt-template-emerald .sheet,
            body.receipt-template-monogram .sheet { width:calc(var(--print-width-mm) * 1mm) !important; max-width:calc(var(--print-width-mm) * 1mm) !important; border-radius:0 !important; box-shadow:none !important; }
            .top { padding:10px 8px 8px; }
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
            .devaltum-receipt-branding img, .devaltum-receipt-logo { height:38px !important; max-height:42px !important; width:auto !important; max-width:75px !important; display:inline-block !important; object-fit:contain !important; -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; filter:contrast(130%) brightness(90%) !important; }
            .devaltum-receipt-text { display:inline-flex !important; flex-direction:column !important; justify-content:center !important; font-family:'Segoe UI', Arial, -apple-system, BlinkMacSystemFont, Roboto, sans-serif !important; line-height:1.25 !important; }
            .devaltum-brand-line { font-size:11px !important; font-weight:800 !important; color:#000 !important; letter-spacing:0.3px !important; }
            .devaltum-brand-line strong { font-weight:900 !important; color:#000 !important; }
            .devaltum-phone-line { font-size:12.5px !important; font-weight:800 !important; color:#000 !important; margin-top:1px !important; letter-spacing:0.5px !important; }
        }
    </style>
</head>
<body class="receipt-template-<?php echo Security::escape($receiptTemplate); ?>" data-template-key="<?php echo Security::escape($receiptTemplateKey); ?>" style="<?php echo Security::escape($receiptTemplateVars); ?>">
    <div class="sheet">
        <?php if (trim((string) ($receiptTemplateMeta['html_block'] ?? '')) !== ''): ?>
            <?php echo ReceiptTemplates::renderHtmlBlock((string) $receiptTemplateMeta['html_block'], [
                'company_name' => $companyName,
                'company_address' => $companyAddress,
                'whatsapp' => $companyWhatsapp,
                'invoice_no' => (string) ($payment['custom_id'] ?? ''),
                'date' => date('M d, Y', strtotime($payment['created_at'] ?? 'now')),
                'time' => date('h:i A', strtotime($payment['created_at'] ?? 'now')),
                'cashier' => (string) ($payment['cashier'] ?? ''),
                'customer' => (string) (($payment['customer_name_snapshot'] ?? '') ?: 'Walk-in Customer'),
                'phone' => (string) (($payment['customer_phone_snapshot'] ?? '') ?: '-'),
                'status' => $openDue > 0 ? 'UNPAID' : 'PAID',
                'payment' => ucfirst((string) ($payment['payment_method'] ?? 'cash')),
                'channel' => ucfirst((string) ($payment['sale_channel'] ?? 'retail')),
                'items' => $invoiceItems,
                'subtotal' => (float) ($payment['total'] ?? 0),
                'discount' => 0,
                'total' => (float) ($payment['total'] ?? 0),
                'paid' => (float) ($payment['paid_amount'] ?? 0),
                'remaining' => (float) $openDue,
                'footer' => $footerText,
                'qr' => '<img src="' . Security::escape($verifyQrUrl) . '" alt="Invoice PDF QR" style="width:72px;height:72px;object-fit:contain;">',
            ]); ?>
        <?php else: ?>

        <!-- Header -->
        <div class="top">
            <div class="invoice-head-left">
                <?php if ($receiptLogo): ?><img src="<?php echo Security::escape($receiptLogo); ?>" alt="Logo" onerror="this.onerror=null; this.style.display='none';"><?php endif; ?>
                <div>
                    <div class="title"><?php echo Security::escape($companyName); ?></div>
                    <div class="muted thermal-invoice-number">
                        <span>Invoice: <?php echo Security::escape($payment['custom_id']); ?></span>
                        <span class="thermal-date"><?php echo date('M d, Y h:i A', strtotime($payment['created_at'])); ?></span>
                    </div>
                    <?php if ($companyAddress): ?><div class="muted company-header-meta"><?php echo Security::escape($companyAddress); ?></div><?php endif; ?>
                    <?php if ($companyWhatsapp): ?><div class="muted company-header-meta">Mob: <?php echo Security::escape($companyWhatsapp); ?></div><?php endif; ?>
                    <?php if ($companyNtn): ?><div class="muted company-header-meta">NTN : <?php echo Security::escape($companyNtn); ?></div><?php endif; ?>
                </div>
            </div>
            <div class="invoice-box">
                <h4>INVOICE</h4>
                <div class="muted"><strong>No:</strong> <?php echo Security::escape($payment['custom_id']); ?></div>
                <div class="muted"><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($payment['created_at'])); ?></div>
            </div>
        </div>

        <!-- Customer & Payment Info -->
        <div class="block invoice-info-grid">
            <div class="row"><span>Cashier :</span><strong><?php echo Security::escape($payment['cashier'] ?? 'System'); ?></strong></div>
            <div class="row"><span>Customer :</span><strong><?php echo Security::escape($payment['customer_name_snapshot'] ?: 'Walk-in'); ?></strong></div>
            <div class="row"><span>Phone :</span><strong><?php echo Security::escape($payment['customer_phone_snapshot'] ?: '-'); ?></strong></div>
            <div class="row"><span>Status :</span><strong><?php echo $openDue > 0 ? 'UNPAID' : 'PAID'; ?></strong></div>
            <div class="row"><span>Payment :</span><strong><?php echo ucfirst(Security::escape($payment['payment_method'])); ?></strong></div>
            <div class="row"><span>Channel :</span><strong><?php echo ucfirst(Security::escape($payment['sale_channel'] ?? 'retail')); ?></strong></div>
        </div>

        <!-- Invoice Items -->
        <?php if (!empty($invoiceItems)): ?>
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
                    <?php foreach ($invoiceItems as $item): ?>
                    <tr>
                        <td>
                            <div class="receipt-item-name">
                                <span class="receipt-item-title"><?php echo Security::escape($item['product_name']); ?></span>
                                <span class="receipt-item-unit"><?php echo Security::escape($item['product_unit_label'] ?? App::formatProductUnit(1, 'pcs')); ?></span>
                            </div>
                        </td>
                        <td><?php echo (int)$item['quantity']; ?></td>
                        <td><?php echo App::money($item['price']); ?></td>
                        <td><?php echo App::money($item['total']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Summary -->
        <div class="block totals">
            <div class="row grand"><span>Total</span><span><?php echo App::money($payment['total']); ?></span></div>
            <div class="row" style="padding-top:8px; margin-top:8px; border-top:1px dashed #d1d5db;"><span>Total Items</span><strong><?php echo (int) $totalItems; ?></strong></div>
            <div class="row"><span>Received</span><strong><?php echo App::money($payment['amount']); ?></strong></div>
            <div class="row"><span>Total Paid</span><strong><?php echo App::money($payment['paid_amount']); ?></strong></div>
            <div class="row"><span>Remaining Balance</span><strong><?php echo App::money($openDue); ?></strong></div>
            <div class="row"><span>Next Due</span><strong><?php echo $payment['next_due_date'] ? date('M d, Y h:i A', strtotime($payment['next_due_date'])) : '-'; ?></strong></div>
        </div>

        <?php if (!empty($payment['note'])): ?>
            <div class="block"><div class="muted">Note</div><div style="margin-top:6px; font-size:13px;"><?php echo Security::escape($payment['note']); ?></div></div>
        <?php endif; ?>

        <div class="footer">
            <div class="receipt-footer-main">
                <div class="receipt-footer-copy">
                    <strong><?php echo Security::escape($footerText); ?></strong><br>
                    <?php if ($companyWhatsapp): ?>WhatsApp: <?php echo Security::escape($companyWhatsapp); ?><?php endif; ?>
                    <?php if ($companyAddress): ?><br>Address: <?php echo Security::escape($companyAddress); ?><?php endif; ?>
                </div>
                <div class="receipt-verify-qr">
                    <img src="<?php echo Security::escape($verifyQrUrl); ?>" alt="Invoice PDF QR">
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

        <!-- Actions (screen only) -->
        <div class="actions" data-html2canvas-ignore="true">
            <button class="primary" onclick="printReceipt()">Print Receipt</button>
            <button class="secondary" type="button" id="download-receipt-pdf" data-pdf-fallback="<?php echo Security::escape($pdfUrl); ?>">Download PDF</button>
            <button class="secondary" type="button" id="download-receipt-image" data-image-fallback="<?php echo Security::escape($imageUrl); ?>">Download Image</button>
            <?php if ($customerWa): ?>
                <button class="secondary" type="button" id="share-receipt-image">Send receipt image on WhatsApp</button>
            <?php endif; ?>
        </div>

    </div>
    <script src="<?php echo $baseUrl; ?>/assets/vendor/html2canvas.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/assets/vendor/jspdf.umd.min.js"></script>
    <script>
        const query = new URLSearchParams(window.location.search);
        const forcedPrinter = query.get('printer');
        const isDocumentTemplate = ['receipt-template-invoice', 'receipt-template-executive', 'receipt-template-emerald', 'receipt-template-monogram'].some((className) => document.body.classList.contains(className));
        const likelyThermal = !isDocumentTemplate && (forcedPrinter === 'thermal' || (!forcedPrinter && Math.max(window.innerWidth || 0, window.screen?.width || 0) <= 420));
        const forcedPaper = (query.get('paper') || '').trim();
        const templatePaperWidthMm = <?php echo max(40, min(300, (int) ($receiptTemplateMeta['paper_width_mm'] ?? 148))); ?>;
        const defaultInvoiceWidthMm = templatePaperWidthMm || 148;
        const printWidthMm = isDocumentTemplate ? defaultInvoiceWidthMm : (forcedPaper === '80' ? 72 : (forcedPaper === '58' ? 56 : (likelyThermal ? 56 : 78)));
        document.documentElement.style.setProperty('--print-width-mm', String(printWidthMm));
        const applyDynamicPrintPageSize = () => {
            const sheet = document.querySelector('.sheet');
            if (!sheet) return;
            const pxToMm = (px) => (px * 25.4) / 96;
            const contentWidthMm = isDocumentTemplate ? Math.ceil(pxToMm(sheet.getBoundingClientRect().width)) + 2 : printWidthMm;
            const contentHeightMm = Math.ceil(pxToMm(sheet.scrollHeight));
            const safeHeightMm = Math.max(70, Math.min(contentHeightMm, 700));
            let styleTag = document.getElementById('dynamic-print-page-size');
            if (!styleTag) {
                styleTag = document.createElement('style');
                styleTag.id = 'dynamic-print-page-size';
                document.head.appendChild(styleTag);
            }
            const measuringHost = document.createElement('div');
            const clone = sheet.cloneNode(true);
            clone.querySelector('.actions')?.remove();
            measuringHost.style.cssText = `position:fixed;left:-10000px;top:0;width:${sheet.offsetWidth}px;visibility:hidden;pointer-events:none;`;
            clone.style.width = `${sheet.offsetWidth}px`;
            clone.style.maxWidth = `${sheet.offsetWidth}px`;
            clone.style.height = 'auto';
            measuringHost.appendChild(clone);
            document.body.appendChild(measuringHost);
            const exactHeightMm = Math.ceil(pxToMm(clone.getBoundingClientRect().height || clone.scrollHeight));
            measuringHost.remove();
            const sideMarginMm = isDocumentTemplate ? 4 : 2;
            const topMarginMm = isDocumentTemplate ? 4 : 2;
            const pageContentHeightMm = Math.max(45, Math.min(exactHeightMm, 700));
            const pageWidthMm = contentWidthMm + (sideMarginMm * 2);
            const pageHeightMm = pageContentHeightMm + (topMarginMm * 2);
            styleTag.textContent = `@page { size: ${pageWidthMm}mm ${pageHeightMm}mm; margin: 0; } @media print { *{box-sizing:border-box!important;} html,body{width:${pageWidthMm}mm!important;height:${pageHeightMm}mm!important;min-height:0!important;overflow:hidden!important;} body{display:flex!important;justify-content:center!important;align-items:flex-start!important;padding:${topMarginMm}mm ${sideMarginMm}mm!important;margin:0!important;background:#fff!important;} .sheet { width:${contentWidthMm}mm !important; max-width:${contentWidthMm}mm !important; height:auto!important; min-height:0!important; margin:0!important; overflow:hidden!important; box-shadow:none!important; border-radius:0!important; } .actions{display:none!important;} }`;
        };

        window.addEventListener('beforeprint', applyDynamicPrintPageSize);
        if (likelyThermal) {
            document.body.classList.add('thermal');
        }
        window.printReceipt = () => {
            applyDynamicPrintPageSize();
            setTimeout(() => window.print(), 60);
        };

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

        const createReceiptCaptureClone = () => {
            const sourceSheet = document.querySelector('.sheet');
            if (!sourceSheet) throw new Error('Receipt sheet not found.');
            const rect = sourceSheet.getBoundingClientRect();
            const width = Math.ceil(rect.width || sourceSheet.offsetWidth);
            const host = document.createElement('div');
            const clone = sourceSheet.cloneNode(true);
            clone.querySelector('.actions')?.remove();
            host.setAttribute('data-html2canvas-ignore', 'true');
            host.style.cssText = `position:fixed;left:-12000px;top:0;width:${width}px;background:#fff;z-index:-1;pointer-events:none;`;
            clone.style.width = `${width}px`;
            clone.style.maxWidth = `${width}px`;
            clone.style.margin = '0';
            clone.style.transform = 'none';
            clone.style.animation = 'none';
            host.appendChild(clone);
            document.body.appendChild(host);
            return { host, clone, width };
        };

        const buildReceiptCanvas = async () => {
            await waitForReceiptAssets();
            if (!window.html2canvas) {
                throw new Error('html2canvas is not loaded.');
            }
            if (document.fonts?.ready) {
                await document.fonts.ready;
            }
            const capture = createReceiptCaptureClone();
            const scale = Math.min(4, Math.max(3, window.devicePixelRatio || 3));
            try {
                const cloneRect = capture.clone.getBoundingClientRect();
                const canvas = await window.html2canvas(capture.clone, {
                    scale,
                    backgroundColor: '#ffffff',
                    useCORS: true,
                    allowTaint: false,
                    logging: false,
                    imageTimeout: 5000,
                    scrollX: 0,
                    scrollY: 0,
                    windowWidth: capture.width,
                    windowHeight: Math.ceil(cloneRect.height)
                });
                canvas.dataset.captureScale = String(scale);
                canvas.dataset.cssWidth = String(Math.ceil(cloneRect.width));
                canvas.dataset.cssHeight = String(Math.ceil(cloneRect.height));
                return canvas;
            } finally {
                capture.host.remove();
            }
        };

        const buildReceiptImageBlob = async () => {
            const canvas = await buildReceiptCanvas();
            return await new Promise((resolve) => canvas.toBlob(resolve, 'image/png', 0.96));
        };

        const downloadReceiptImage = async () => {
            const blob = await buildReceiptImageBlob();
            if (!blob) throw new Error('Could not create receipt image.');
            const imageUrl = URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');
            downloadLink.href = imageUrl;
            downloadLink.download = 'credit-receipt-<?php echo Security::escape($receiptFileName); ?>.png';
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
            pdf.addImage(canvas.toDataURL('image/png', 1), 'PNG', 0, 0, cssWidth, cssHeight, undefined, 'SLOW');
            return pdf.output('blob');
        };

        const downloadSnapshotPdf = async (openAfterDownload = false) => {
            try {
                const canvas = await buildReceiptCanvas();
                const pdfBlob = await buildSnapshotPdfBlob(canvas);
                const pdfUrl = URL.createObjectURL(pdfBlob);
                const link = document.createElement('a');
                link.href = pdfUrl;
                link.download = 'credit-receipt-<?php echo Security::escape($receiptFileName); ?>.pdf';
                link.click();
                if (openAfterDownload) {
                    setTimeout(() => {
                        try { window.location.href = pdfUrl; } catch (error) {}
                    }, 250);
                }
                setTimeout(() => URL.revokeObjectURL(pdfUrl), 60000);
            } catch (error) {
                const fallback = downloadReceiptPdfButton.getAttribute('data-pdf-fallback');
                if (fallback) window.location.href = fallback;
            }
        };

        downloadReceiptPdfButton?.addEventListener('click', () => downloadSnapshotPdf(false));
        downloadReceiptImageButton?.addEventListener('click', async () => {
            try {
                await downloadReceiptImage();
            } catch (error) {
                const fallback = downloadReceiptImageButton.getAttribute('data-image-fallback');
                if (fallback) window.location.href = fallback;
            }
        });

        if (query.get('auto_pdf') === '1') {
            setTimeout(() => downloadSnapshotPdf(true), 450);
        }

        shareReceiptButton?.addEventListener('click', async () => {
            try {
                const blob = await buildReceiptImageBlob();
                if (!blob) throw new Error('Could not create receipt image.');
                const file = new File([blob], 'credit-receipt-<?php echo Security::escape($receiptFileName); ?>.png', { type: 'image/png' });
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        files: [file],
                        title: 'Credit Receipt <?php echo Security::escape($payment['custom_id']); ?>',
                        text: 'Credit receipt from <?php echo Security::escape($companyName); ?>'
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
