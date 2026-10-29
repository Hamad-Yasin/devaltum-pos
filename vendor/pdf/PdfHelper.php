<?php

class PdfHelper {

    /**
     * Render an HTML receipt into a direct printable thermal / browser window
     */
    public static function renderThermalReceipt(array $invoice, array $items, array $storeInfo): string {
        $storeName = htmlspecialchars($storeInfo['company_name'] ?? 'DevAltum POS');
        $phone     = htmlspecialchars($storeInfo['whatsapp_number'] ?? '');
        $invNum    = htmlspecialchars($invoice['invoice_number'] ?? 'N/A');
        $date      = date('d/m/Y h:i A', strtotime($invoice['created_at'] ?? 'now'));
        $total     = number_format((float)($invoice['grand_total'] ?? 0), 2);

        $itemsHtml = '';
        foreach ($items as $item) {
            $name  = htmlspecialchars($item['product_name'] ?? '');
            $qty   = (int)$item['quantity'];
            $price = number_format((float)$item['unit_price'], 2);
            $sub   = number_format((float)$item['total'], 2);
            $itemsHtml .= "
            <tr>
                <td colspan='3' style='font-weight:bold; padding-top:4px;'>{$name}</td>
            </tr>
            <tr>
                <td>{$qty} x {$price}</td>
                <td></td>
                <td style='text-align:right;'>{$sub}</td>
            </tr>";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Receipt #{$invNum}</title>
            <style>
                @page { margin: 0; size: 80mm auto; }
                body { width: 72mm; margin: 0 auto; font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; padding: 4mm 0; }
                .center { text-align: center; }
                .bold { font-weight: bold; }
                .line { border-top: 1px dashed #000; margin: 6px 0; }
                table { width: 100%; border-collapse: collapse; }
                @media print { .no-print { display: none; } }
            </style>
        </head>
        <body onload='window.print()'>
            <div class='center bold' style='font-size:15px;'>{$storeName}</div>
            <div class='center'>Phone: {$phone}</div>
            <div class='line'></div>
            <div>Inv #: {$invNum}</div>
            <div>Date: {$date}</div>
            <div class='line'></div>
            <table>
                <tbody>{$itemsHtml}</tbody>
            </table>
            <div class='line'></div>
            <div style='display:flex; justify-content:space-between; font-weight:bold; font-size:14px;'>
                <span>TOTAL:</span>
                <span>Rs. {$total}</span>
            </div>
            <div class='line'></div>
            <div class='center' style='font-size:11px; margin-top:8px;'>
                Thank you for your visit!<br>
                Powered by DevAltum POS
            </div>
        </body>
        </html>";
    }

    /**
     * Send standard PDF download / stream headers
     */
    public static function setPdfHeaders(string $filename = 'document.pdf', bool $forceDownload = false): void {
        header('Content-Type: application/pdf');
        $disposition = $forceDownload ? 'attachment' : 'inline';
        header("Content-Disposition: {$disposition}; filename=\"{$filename}\"");
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
    }
}
