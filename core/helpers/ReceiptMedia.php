<?php

class ReceiptMedia {
    public static function salesImage($invoice, $items) {
        $data = self::baseData();
        $data['id'] = (string) ($invoice['custom_id'] ?? 'receipt');
        $data['qr'] = self::absoluteUrl('/pos/receipt/' . (int) ($invoice['id'] ?? 0) . '?download_pdf=1');
        $data['date'] = self::dateText($invoice['created_at'] ?? 'now');
        $data['cashier'] = (string) ($invoice['cashier'] ?? 'System');
        $data['customer'] = (string) (($invoice['customer_name_snapshot'] ?? '') ?: ($invoice['customer_name'] ?? 'Walk-in Customer'));
        $data['phone'] = (string) (($invoice['customer_phone_snapshot'] ?? '') ?: ($invoice['customer_phone'] ?? '-'));
        $data['status'] = strtoupper((string) (($invoice['status'] ?? '') ?: 'paid'));
        $data['payment'] = ucfirst((string) ($invoice['payment_method'] ?? 'cash'));
        $data['channel'] = ucfirst((string) ($invoice['sale_channel'] ?? 'retail'));
        $data['items'] = $items;
        $data['subtotal'] = (float) ($invoice['subtotal'] ?? 0);
        $data['discount'] = (float) ($invoice['discount'] ?? 0);
        $data['tax_amount'] = (float) ($invoice['tax_amount'] ?? 0);
        $data['tax_rate'] = (float) ($invoice['tax_rate'] ?? 0);
        $data['tax_label'] = (string) ($invoice['tax_label'] ?? App::setting('tax_label', 'Tax'));
        $data['fbr_pos_id'] = (string) ($invoice['fbr_pos_id'] ?? '');
        $data['total'] = (float) ($invoice['total'] ?? 0);
        $openDue = max(0, max(($data['total'] - (float) ($invoice['paid_amount'] ?? 0)), (float) ($invoice['balance_due'] ?? 0)));
        $data['paid'] = max(0, $data['total'] - $openDue);
        $data['remaining'] = $openDue;
        $data['due'] = !empty($invoice['credit_due_date']) ? self::dateText($invoice['credit_due_date']) : '';
        $data['note'] = (string) ($invoice['notes'] ?? '');
        return self::render($data);
    }

    public static function creditImage($payment, $items) {
        $data = self::baseData();
        $data['id'] = (string) ($payment['custom_id'] ?? 'credit_receipt');
        $data['qr'] = self::absoluteUrl('/credits/receipt/' . (int) ($payment['id'] ?? 0) . '?download_pdf=1');
        $data['date'] = self::dateText($payment['created_at'] ?? 'now');
        $data['cashier'] = (string) ($payment['cashier'] ?? 'System');
        $data['customer'] = (string) (($payment['customer_name_snapshot'] ?? '') ?: 'Walk-in Customer');
        $data['phone'] = (string) (($payment['customer_phone_snapshot'] ?? '') ?: '-');
        $data['status'] = ((float) ($payment['open_due'] ?? $payment['balance_due'] ?? 0)) > 0 ? 'UNPAID' : 'PAID';
        $data['payment'] = ucfirst((string) ($payment['payment_method'] ?? 'cash'));
        $data['channel'] = ucfirst((string) ($payment['sale_channel'] ?? 'retail'));
        $data['items'] = $items;
        $data['subtotal'] = (float) ($payment['total'] ?? 0);
        $data['discount'] = 0;
        $data['total'] = (float) ($payment['total'] ?? 0);
        $data['paid'] = (float) ($payment['paid_amount'] ?? 0);
        $data['received'] = (float) ($payment['amount'] ?? 0);
        $data['remaining'] = (float) ($payment['open_due'] ?? $payment['balance_due'] ?? 0);
        $data['due'] = !empty($payment['next_due_date']) ? self::dateText($payment['next_due_date']) : '';
        $data['note'] = (string) ($payment['note'] ?? '');
        $data['is_credit_receipt'] = true;
        return self::render($data);
    }

    public static function imagePdf($jpegBytes, $widthPx, $heightPx) {
        if ($widthPx >= 800) {
            $pageWidth = round($widthPx * 0.75, 2);
            $pageHeight = round($heightPx * 0.75, 2);
        } else {
            // 80mm standard width in PDF points = 226.77pt
            $pageWidth = 226.77;
            $pageHeight = round(($heightPx / max(1, $widthPx)) * 226.77, 2);
        }
        $content = "q\n$pageWidth 0 0 $pageHeight 0 0 cm\n/Im1 Do\nQ";
        $parts = [];
        $offsets = [0];
        $length = 0;
        $add = function ($part) use (&$parts, &$length) {
            $parts[] = $part;
            $length += is_string($part) ? strlen($part) : strlen((string) $part);
        };
        $obj = function ($body) use (&$offsets, &$length, $add) {
            $offsets[] = $length;
            $add($body);
        };
        $add("%PDF-1.4\n");
        $obj("1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n");
        $obj("2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n");
        $obj("3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageWidth $pageHeight] /Resources << /XObject << /Im1 5 0 R >> >> /Contents 4 0 R >>endobj\n");
        $obj("4 0 obj<< /Length " . strlen($content) . " >>stream\n$content\nendstream endobj\n");
        $offsets[] = $length;
        $add("5 0 obj<< /Type /XObject /Subtype /Image /Width $widthPx /Height $heightPx /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($jpegBytes) . " >>stream\n");
        $add($jpegBytes);
        $add("\nendstream endobj\n");
        $xref = $length;
        $add("xref\n0 6\n0000000000 65535 f \n");
        for ($i = 1; $i <= 5; $i++) {
            $add(sprintf("%010d 00000 n \n", $offsets[$i]));
        }
        $add("trailer<< /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF");
        return implode('', $parts);
    }

    public static function pngBytes($image) {
        ob_start();
        imagepng($image, null, 6);
        return ob_get_clean();
    }

    public static function jpegBytes($image) {
        ob_start();
        imagejpeg($image, null, 94);
        return ob_get_clean();
    }

    private static function baseData() {
        $requestedTemplate = trim((string) ($_GET['template'] ?? ''));
        if ($requestedTemplate !== '') {
            $templateMeta = ReceiptTemplates::resolve($requestedTemplate);
        } else {
            $templateMeta = ReceiptTemplates::resolve(App::setting('receipt_template', 'classic'));
        }
        return [
            'template' => $templateMeta['base_template'],
            'template_key' => $templateMeta['template_key'],
            'accent_color' => $templateMeta['accent_color'],
            'font_family' => $templateMeta['font_family'],
            'company' => App::setting('company_name', 'DevAltum POS'),
            'address' => App::setting('company_address', ''),
            'whatsapp' => App::setting('whatsapp_number', ''),
            'footer' => App::setting('receipt_footer', 'Thank you for shopping with us.'),
        ];
    }

    private static function render($data) {
        $doc = in_array($data['template'], ['invoice', 'executive', 'emerald', 'monogram'], true);
        $w = $doc ? 900 : ($data['template'] === 'compact' ? 520 : 600);
        $h = 3600;
        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, true);
        $c = self::palette($im, $data['template'], $data['accent_color'] ?? '');
        imagefilledrectangle($im, 0, 0, $w, $h, $doc ? $c['bg'] : $c['white']);
        $margin = $doc ? 28 : 16;
        $sheetX = $doc ? 18 : 8;
        $sheetW = $w - ($sheetX * 2);
        $y = $doc ? 18 : 12;
        if ($doc) {
            self::roundedRect($im, $sheetX, $y, $sheetX + $sheetW, $h - 20, $data['template'] !== 'emerald' ? 0 : 26, $c['paper'], $c['border']);
            if ($data['template'] === 'invoice') {
                imagefilledrectangle($im, $sheetX, $y, $sheetX + $sheetW, $y + 12, $c['primary']);
                imagefilledrectangle($im, $sheetX, $y + 12, $sheetX + $sheetW, $y + 18, $c['accent']);
            } elseif ($data['template'] === 'executive') {
                imagefilledrectangle($im, $sheetX, $y, $sheetX + $sheetW, $y + 22, $c['primary']);
            } elseif ($data['template'] === 'monogram') {
                imagefilledrectangle($im, $sheetX, $y, $sheetX + 18, $h - 20, $c['primary']);
            } elseif ($data['template'] === 'emerald') {
                self::roundedRect($im, $sheetX + 1, $y + 1, $sheetX + $sheetW - 1, $y + 154, 24, $c['primary'], null);
                imagefilledrectangle($im, $sheetX + 1, $y + 86, $sheetX + $sheetW - 1, $y + 154, $c['primary']);
            }
        }
        $y += $doc ? 38 : 16;
        $contentX = $sheetX + $margin;
        $contentW = $sheetW - ($margin * 2);
        $font = self::font(false);
        $bold = self::font(true);

        if ($doc) {
            $headerTitleColor = $data['template'] === 'emerald' ? $c['white'] : $c['title'];
            $headerMutedColor = $data['template'] === 'emerald' ? $c['emeraldSoft'] : $c['muted'];
            self::drawText($im, $data['company'], $contentX + ($data['template'] === 'monogram' ? 14 : 0), $y + 32, 25, $headerTitleColor, $bold);
            self::drawText($im, $data['address'] ?: 'Main Facility', $contentX + ($data['template'] === 'monogram' ? 14 : 0), $y + 60, 14, $headerMutedColor, $bold);
            if ($data['whatsapp']) self::drawText($im, 'Mob: ' . $data['whatsapp'], $contentX + ($data['template'] === 'monogram' ? 14 : 0), $y + 82, 14, $headerMutedColor, $bold);
            $boxW = 260;
            $boxX = $contentX + $contentW - $boxW;
            self::roundedRect($im, $boxX, $y, $boxX + $boxW, $y + 112, $data['template'] === 'monogram' ? 0 : 14, $c['invoiceBox'], $c['primary']);
            self::drawText($im, 'INVOICE', $boxX + 22, $y + 38, 25, $c['invoiceText'], $bold);
            self::drawText($im, 'No: ' . $data['id'], $boxX + 22, $y + 68, 13, $c['invoiceText'], $bold);
            self::drawText($im, 'Date: ' . $data['date'], $boxX + 22, $y + 91, 13, $c['invoiceText'], $bold);
            $y += 142;
        } else {
            // THERMAL (Matching Image 2)
            $storeName = strtoupper($data['company']);
            self::drawTextCenter($im, $storeName, $w / 2, $y + 18, 20, $c['title'], $bold);
            $y += 24;

            if (!empty($data['address'])) {
                $addrLines = self::wrap(strtoupper($data['address']), 42);
                foreach ($addrLines as $al) {
                    self::drawTextCenter($im, $al, $w / 2, $y + 12, 11, $c['title'], $bold);
                    $y += 18;
                }
            }

            $phone = !empty($data['whatsapp']) ? $data['whatsapp'] : (!empty($data['phone']) && $data['phone'] !== '-' ? $data['phone'] : '+923064008117');
            self::drawTextCenter($im, 'Tel: ' . $phone, $w / 2, $y + 12, 11, $c['title'], $bold);
            $y += 20;

            self::drawTextCenter($im, '*** SALES RECEIPT ***', $w / 2, $y + 14, 12.5, $c['title'], $bold);
            $y += 20;

            self::dash($im, $contentX, $y, $contentX + $contentW, $c['dash']);
            $y += 20;

            // 2-column meta
            self::drawText($im, 'Invoice: #' . $data['id'], $contentX, $y, 11.5, $c['title'], $bold);
            self::drawTextRight($im, $data['date'], $contentX + $contentW, $y, 11, $c['title'], $bold);
            $y += 20;

            self::drawText($im, 'Customer: ' . self::fit($data['customer'], 20), $contentX, $y, 11.5, $c['title'], $bold);
            self::drawTextRight($im, 'Cashier: ' . self::fit($data['cashier'], 18), $contentX + $contentW, $y, 11, $c['title'], $bold);
            $y += 20;

            self::drawText($im, 'Pay Mode: ' . $data['payment'], $contentX, $y, 11.5, $c['title'], $bold);
            $y += 18;

            self::dash($im, $contentX, $y, $contentX + $contentW, $c['dash']);
            $y += 18;

            // Proper Table Grid with Borders
            $tblLeft = $contentX;
            $tblRight = $contentX + $contentW;
            $col1 = $tblLeft + 250;
            $col2 = $col1 + 65;
            $col3 = $col2 + 105;

            // Table Header Row
            $hdrBg = imagecolorallocate($im, 248, 250, 252);
            imagefilledrectangle($im, $tblLeft, $y, $tblRight, $y + 22, $hdrBg);
            imagerectangle($im, $tblLeft, $y, $tblRight, $y + 22, $c['title']);
            imageline($im, $col1, $y, $col1, $y + 22, $c['title']);
            imageline($im, $col2, $y, $col2, $y + 22, $c['title']);
            imageline($im, $col3, $y, $col3, $y + 22, $c['title']);

            self::drawText($im, 'Item Description', $tblLeft + 5, $y + 15, 10, $c['title'], $bold);
            self::drawTextCenter($im, 'Qty.', ($col1 + $col2) / 2, $y + 15, 10, $c['title'], $bold);
            self::drawTextCenter($im, 'Price', ($col2 + $col3) / 2, $y + 15, 10, $c['title'], $bold);
            self::drawTextRight($im, 'Amount', $tblRight - 5, $y + 15, 10, $c['title'], $bold);
            $y += 22;

            $totalQtySum = 0;
            foreach ($data['items'] as $item) {
                $qty = (float) ($item['quantity'] ?? 0);
                $totalQtySum += $qty;
                $name = (string) ($item['product_name'] ?? 'Product');
                $unitLabel = (string) ($item['product_unit_label'] ?? App::formatProductUnit(1, 'pcs'));
                $price = (float) ($item['price'] ?? 0);
                $total = (float) ($item['total'] ?? 0);

                $hasUnit = trim($unitLabel) !== '';
                $rowH = $hasUnit ? 44 : 26;
                $rowTop = $y;
                $rowBottom = $y + $rowH;

                imagerectangle($im, $tblLeft, $rowTop, $tblRight, $rowBottom, $c['title']);
                imageline($im, $col1, $rowTop, $col1, $rowBottom, $c['title']);
                imageline($im, $col2, $rowTop, $col2, $rowBottom, $c['title']);
                imageline($im, $col3, $rowTop, $col3, $rowBottom, $c['title']);

                self::drawText($im, self::fit($name, 22), $tblLeft + 6, $rowTop + 17, 10.5, $c['title'], $bold);
                if ($hasUnit) {
                    self::drawText($im, $unitLabel, $tblLeft + 6, $rowTop + 32, 8.5, $c['muted'], $font);
                }
                $numY = $hasUnit ? ($rowTop + 24) : ($rowTop + 17);
                self::drawTextCenter($im, App::formatQuantity($qty), ($col1 + $col2) / 2, $numY, 10.5, $c['title'], $font);
                self::drawTextRight($im, number_format($price, 2), $col3 - 6, $numY, 10.5, $c['title'], $font);
                self::drawTextRight($im, number_format($total, 2), $tblRight - 6, $numY, 11, $c['title'], $bold);

                $y += $rowH;
            }

            // Summary Row (Total Items & Total Qty)
            $sumH = 22;
            $sumBg = imagecolorallocate($im, 248, 250, 252);
            imagefilledrectangle($im, $tblLeft, $y, $tblRight, $y + $sumH, $sumBg);
            imagerectangle($im, $tblLeft, $y, $tblRight, $y + $sumH, $c['title']);
            self::drawText($im, 'Total Items : ' . count($data['items']), $tblLeft + 6, $y + 15, 9.5, $c['title'], $bold);
            self::drawTextRight($im, 'No. Of Qty. : ' . App::formatQuantity($totalQtySum), $tblRight - 6, $y + 15, 9.5, $c['title'], $bold);
            $y += $sumH + 18;

            // Totals
            self::pair($im, 'Sub Total Amount:', number_format((float) $data['subtotal'], 2), $contentX, $contentX + $contentW, $y, 11.5, $c['title'], $bold);
            $y += 18;

            if ((float) ($data['discount'] ?? 0) > 0) {
                self::pair($im, 'Discount :', number_format((float) $data['discount'], 2), $contentX, $contentX + $contentW, $y, 11.5, $c['title'], $bold);
                $y += 18;
            }

            if ((float) ($data['tax_amount'] ?? 0) > 0) {
                self::pair($im, ($data['tax_label'] ?: 'Tax') . ':', number_format((float) $data['tax_amount'], 2), $contentX, $contentX + $contentW, $y, 11.5, $c['title'], $bold);
                $y += 18;
            }

            // Net Payable Highlight Box
            $y += 6;
            $payBoxH = 28;
            $payBg = imagecolorallocate($im, 255, 255, 255);
            imagefilledrectangle($im, $contentX, $y, $contentX + $contentW, $y + $payBoxH, $payBg);
            imagerectangle($im, $contentX, $y, $contentX + $contentW, $y + $payBoxH, $c['title']);
            self::pair($im, 'Net Payable:', number_format((float) $data['total'], 2), $contentX + 8, $contentX + $contentW - 8, $y + 19, 13, $c['title'], $bold);
            $y += $payBoxH + 18;

            $paid = (float) $data['paid'];
            $change = max(0, $paid - (float) $data['total']);
            self::pair($im, 'Cash Paid:', 'Rs ' . number_format($paid, 0), $contentX, $contentX + $contentW, $y, 12, $c['title'], $bold);
            $y += 20;

            if ($data['remaining'] > 0) {
                self::pair($im, 'Change Return:', 'Rs 0', $contentX, $contentX + $contentW, $y, 12, $c['title'], $bold);
            } else {
                self::pair($im, 'Change Return:', 'Rs ' . number_format($change, 0), $contentX, $contentX + $contentW, $y, 12, $c['title'], $bold);
            }
            $y += 18;

            self::dash($im, $contentX, $y, $contentX + $contentW, $c['dash']);
            $y += 22;

            self::drawTextCenter($im, 'THANK YOU FOR SHOPPING WITH US!', $w / 2, $y, 11, $c['title'], $bold);
            $y += 18;
            self::drawTextCenter($im, 'ITEMS ONCE SOLD CANNOT BE RETURNED WITHOUT RECEIPT', $w / 2, $y, 9.5, $c['title'], $bold);
            $y += 22;

            self::dash($im, $contentX, $y, $contentX + $contentW, $c['dash']);
            $y += 24;

            $logoPath = defined('ROOT_PATH') ? ROOT_PATH . '/public/assets/img/devaltum-receipt-logo.png' : dirname(__DIR__, 2) . '/public/assets/img/devaltum-receipt-logo.png';
            if (file_exists($logoPath)) {
                $logoImg = @imagecreatefrompng($logoPath);
                if ($logoImg) {
                    $origW = imagesx($logoImg);
                    $origH = imagesy($logoImg);
                    $targetH = 34;
                    $targetW = (int) ($origW * ($targetH / max(1, $origH)));
                    $line1 = 'SOFTWARE BY DEVALTUM';
                    $line2 = '+92 3101648600';
                    $w1 = strlen($line1) * 7.5;
                    $w2 = strlen($line2) * 8;
                    if (file_exists($bold)) {
                        $b1 = @imagettfbbox(10.5, 0, $bold, $line1);
                        if ($b1) $w1 = abs($b1[2] - $b1[0]);
                        $b2 = @imagettfbbox(11.5, 0, $bold, $line2);
                        if ($b2) $w2 = abs($b2[2] - $b2[0]);
                    }
                    $maxTextW = max($w1, $w2);
                    $gap = 12;
                    $totalW = $targetW + $gap + $maxTextW;
                    $startX = (int) (($w - $totalW) / 2);
                    imagecopyresampled($im, $logoImg, $startX, (int) ($y - $targetH + 12), 0, 0, $targetW, $targetH, $origW, $origH);
                    self::drawText($im, $line1, $startX + $targetW + $gap, (int) ($y - 4), 10.5, $c['title'], $bold);
                    self::drawText($im, $line2, $startX + $targetW + $gap, (int) ($y + 14), 11.5, $c['title'], $bold);
                    imagedestroy($logoImg);
                    $y += 18;
                } else {
                    self::drawTextCenter($im, 'SOFTWARE BY DEVALTUM', $w / 2, $y, 11, $c['title'], $bold);
                    $y += 18;
                    self::drawTextCenter($im, '+92 3101648600', $w / 2, $y, 12, $c['title'], $bold);
                    $y += 12;
                }
            } else {
                self::drawTextCenter($im, 'SOFTWARE BY DEVALTUM', $w / 2, $y, 11, $c['title'], $bold);
                $y += 18;
                self::drawTextCenter($im, '+92 3101648600', $w / 2, $y, 12, $c['title'], $bold);
                $y += 12;
            }
            $y += 8;
        }

        if ($doc) {
            $details = [
                ['Cashier :', $data['cashier']],
                ['Customer :', $data['customer']],
                ['Phone :', $data['phone'] ?: '-'],
                ['Status :', $data['status']],
                ['Payment :', $data['payment']],
                ['Channel :', $data['channel']],
            ];
            self::roundedRect($im, $contentX, $y, $contentX + $contentW, $y + 118, 12, $c['detailBg'], $c['border']);
            $colW = $contentW / 2;
            foreach ($details as $i => $pair) {
                $dx = $contentX + 18 + (($i % 2) * $colW);
                $dy = $y + 28 + (floor($i / 2) * 30);
                self::drawText($im, $pair[0], $dx, $dy, 13, $c['title'], $bold);
                self::drawText($im, self::fit($pair[1], 25), $dx + 118, $dy, 13, $c['title'], $bold);
            }
            $y += 138;

            self::roundedRect($im, $contentX, $y - 12, $contentX + $contentW, $y + 82 + (count($data['items']) * 56), 12, $c['detailBg'], $c['border']);
            $y += 6;
            $headerH = 34;
            imagefilledrectangle($im, $contentX, $y, $contentX + $contentW, $y + $headerH, $c['primary']);
            self::drawText($im, 'Item', $contentX + 8, $y + 23, 13, $c['white'], $bold);
            self::drawText($im, 'Qty', $contentX + 430, $y + 23, 13, $c['white'], $bold);
            self::drawText($im, 'Unit', $contentX + 555, $y + 23, 13, $c['white'], $bold);
            self::drawTextRight($im, 'Price', $contentX + $contentW - 8, $y + 23, 13, $c['white'], $bold);
            $y += $headerH + 10;
            foreach ($data['items'] as $item) {
                $qty = (float) ($item['quantity'] ?? 0);
                $name = (string) ($item['product_name'] ?? 'Product');
                $unitLabel = (string) ($item['product_unit_label'] ?? App::formatProductUnit(1, 'pcs'));
                $lines = self::wrap($name, 34);
                $rowH = max(46, 23 + (count($lines) * 18));
                foreach ($lines as $i => $line) self::drawText($im, $line, $contentX + 8, $y + 18 + ($i * 18), 13, $c['title'], $bold);
                self::drawText($im, $unitLabel, $contentX + 8, $y + 21 + (count($lines) * 18), 10, $c['muted'], $font);
                self::drawText($im, App::formatQuantity($qty), $contentX + 430, $y + 23, 13, $c['title'], $bold);
                self::drawText($im, self::money($item['price'] ?? 0), $contentX + 555, $y + 23, 13, $c['title'], $bold);
                self::drawTextRight($im, self::money($item['total'] ?? 0), $contentX + $contentW - 8, $y + 23, 13, $c['title'], $bold);
                $y += $rowH;
                self::line($im, $contentX, $y, $contentX + $contentW, $c['line']);
                $y += 10;
            }
            $y += 20;

            $totals = [
                ['Subtotal', self::money($data['subtotal'])],
                ['Discount', self::money($data['discount'])],
            ];
            if ((float) ($data['tax_amount'] ?? 0) > 0) {
                $rate = rtrim(rtrim(number_format((float) ($data['tax_rate'] ?? 0), 3), '0'), '.');
                $totals[] = [($data['tax_label'] ?: 'Tax') . ($rate !== '' ? " ({$rate}%)" : ''), self::money($data['tax_amount'])];
                $taxable = max(0, (float) ($data['subtotal'] ?? 0) - (float) ($data['discount'] ?? 0));
                $totals[] = ['Taxable Amount', self::money($taxable)];
                $totals[] = ['Tax Charged', self::money($data['tax_amount'])];
            }
            $totals[] = ['Total', self::money($data['total']), true];
            if (!empty($data['fbr_pos_id'])) {
                $totals[] = ['FBR POS ID', (string) $data['fbr_pos_id']];
            }
            $totals[] = ['Total Items', (string) count($data['items'] ?? [])];
            if (!empty($data['is_credit_receipt'])) $totals[] = ['Received', self::money($data['received'] ?? 0)];
            $totals[] = ['Paid', self::money($data['paid'])];
            $totals[] = [($data['remaining'] > 0 ? 'Remaining Balance' : 'Remaining'), self::money($data['remaining'])];
            if ($data['due']) $totals[] = ['Due Date', $data['due']];
            $totalsBoxHeight = 52 + (count($totals) * 30) + (!empty($data['note']) ? 84 : 0);
            self::roundedRect($im, $contentX, $y - 16, $contentX + $contentW, $y + $totalsBoxHeight, 12, $c['detailBg'], $c['border']);
            $y += 8;
            foreach ($totals as $row) {
                if (!empty($row[2])) {
                    self::dash($im, $contentX, $y, $contentX + $contentW, $c['dash']);
                    $y += 24;
                    self::pair($im, $row[0], $row[1], $contentX, $contentX + $contentW, $y, 18, $c['title'], $bold);
                } else {
                    self::pair($im, $row[0], $row[1], $contentX, $contentX + $contentW, $y, 14, $c['title'], $bold);
                }
                $y += !empty($row[2]) ? 34 : 26;
            }

            if (trim($data['note']) !== '') {
                self::dash($im, $contentX, $y, $contentX + $contentW, $c['dash']);
                $y += 24;
                self::drawText($im, 'Note', $contentX, $y, 13, $c['muted'], $bold);
                $y += 20;
                foreach (self::wrap($data['note'], 78) as $line) {
                    self::drawText($im, $line, $contentX, $y, 12, $c['title'], $font);
                    $y += 18;
                }
            }

            $y += 22;
            self::dash($im, $contentX, $y, $contentX + $contentW, $c['dash']);
            $y += 26;
            $footerX = $contentX;
            self::drawText($im, $data['footer'], $footerX, $y, 13, $c['title'], $bold);
            $y += 22;
            if ($data['whatsapp']) {
                self::drawText($im, 'WhatsApp: ' . $data['whatsapp'], $footerX, $y, 12, $c['muted'], $bold);
                $y += 20;
            }
            if ($data['address']) {
                self::drawText($im, 'Address: ' . self::fit($data['address'], 50), $footerX, $y, 12, $c['muted'], $bold);
                $y += 22;
            }
            self::drawQr($im, $data['qr'] ?: ('receipt:' . $data['id']), $contentX + $contentW - 122, $y - 96, 104, $c);
            $y += 28;
            self::dash($im, $contentX, $y, $contentX + $contentW, $c['dash']);
            $y += 30;

            $logoPath = defined('ROOT_PATH') ? ROOT_PATH . '/public/assets/img/devaltum-receipt-logo.png' : dirname(__DIR__, 2) . '/public/assets/img/devaltum-receipt-logo.png';
            if (file_exists($logoPath)) {
                $logoImg = @imagecreatefrompng($logoPath);
                if ($logoImg) {
                    $origW = imagesx($logoImg);
                    $origH = imagesy($logoImg);
                    $targetH = 34;
                    $targetW = (int) ($origW * ($targetH / max(1, $origH)));
                    $line1 = 'Software By DevAltum';
                    $line2 = '+92 3101648600';
                    $w1 = strlen($line1) * 7.5;
                    $w2 = strlen($line2) * 8;
                    if (file_exists($bold)) {
                        $b1 = @imagettfbbox(11, 0, $bold, $line1);
                        if ($b1) $w1 = abs($b1[2] - $b1[0]);
                        $b2 = @imagettfbbox(11, 0, $bold, $line2);
                        if ($b2) $w2 = abs($b2[2] - $b2[0]);
                    }
                    $maxTextW = max($w1, $w2);
                    $gap = 12;
                    $totalW = $targetW + $gap + $maxTextW;
                    $startX = (int) (($w - $totalW) / 2);
                    imagecopyresampled($im, $logoImg, $startX, (int) ($y - $targetH + 12), 0, 0, $targetW, $targetH, $origW, $origH);
                    self::drawText($im, $line1, $startX + $targetW + $gap, (int) ($y - 4), 11, $c['title'], $bold);
                    self::drawText($im, $line2, $startX + $targetW + $gap, (int) ($y + 14), 11, $c['title'], $bold);
                    imagedestroy($logoImg);
                    $y += 18;
                } else {
                    self::drawTextCenter($im, 'Software By DevAltum', $w / 2, $y, 12, $c['title'], $bold);
                    $y += 18;
                    self::drawTextCenter($im, '+92 3101648600', $w / 2, $y, 12, $c['title'], $bold);
                }
            } else {
                self::drawTextCenter($im, 'Software By DevAltum', $w / 2, $y, 12, $c['title'], $bold);
                $y += 18;
                self::drawTextCenter($im, '+92 3101648600', $w / 2, $y, 12, $c['title'], $bold);
            }
            $y += 24;
        }

        $cropH = min($h, $y + ($doc ? 18 : 6));
        $out = imagecreatetruecolor($w, $cropH);
        imagecopy($out, $im, 0, 0, 0, 0, $w, $cropH);
        imagedestroy($im);
        return $out;
    }

    private static function palette($im, $template, $accentColor = '') {
        $rgb = function ($hex) use ($im) {
            $hex = ltrim($hex, '#');
            return imagecolorallocate($im, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
        };
        $customAccent = ReceiptTemplates::sanitizeColor($accentColor ?: '#0f6dff');
        $p = [
            'bg' => $rgb('f3f4f6'), 'paper' => $rgb('ffffff'), 'title' => $rgb('111827'), 'muted' => $rgb('475569'),
            'line' => $rgb('e2e8f0'), 'dash' => $rgb('cbd5e1'), 'border' => $rgb('cbd5e1'), 'primary' => $rgb('0b4f66'),
            'accent' => $rgb('f59e0b'), 'white' => $rgb('ffffff'), 'blue' => $rgb('1e40af'), 'detailBg' => $rgb('ffffff'),
            'invoiceBox' => $rgb('ffffff'), 'invoiceText' => $rgb('0f172a'), 'premiumText' => $rgb('ffffff'), 'premiumSoft' => $rgb('dbeafe'),
        ];
        if ($accentColor !== '') {
            $p['primary'] = $rgb($customAccent);
        }
        if ($template === 'executive') { $p['primary'] = $rgb('102a43'); $p['invoiceBox'] = $rgb('102a43'); $p['invoiceText'] = $rgb('ffffff'); }
        if ($template === 'emerald') { $p['bg'] = $rgb('ecfdf5'); $p['paper'] = $rgb('f0fdf4'); $p['primary'] = $rgb('047857'); $p['accent'] = $rgb('10b981'); $p['border'] = $rgb('86efac'); $p['emeraldSoft'] = $rgb('dcfce7'); }
        if ($template === 'monogram') { $p['primary'] = $rgb('111827'); }
        if ($accentColor !== '') {
            $p['primary'] = $rgb($customAccent);
            if ($template === 'executive') {
                $p['invoiceBox'] = $p['primary'];
                $p['invoiceText'] = $rgb('ffffff');
            }
            if ($template === 'emerald') {
                $p['border'] = $rgb('86efac');
            }
        }
        return $p;
    }

    private static function font($bold = false) {
        $paths = $bold
            ? ['C:/Windows/Fonts/arialbd.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf']
            : ['C:/Windows/Fonts/arial.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'];
        foreach ($paths as $path) if (is_file($path)) return $path;
        return null;
    }

    private static function drawText($im, $text, $x, $y, $size, $color, $font = null) {
        $text = self::clean($text);
        if ($font) imagettftext($im, $size, 0, (int) $x, (int) $y, $color, $font, $text);
        else imagestring($im, 4, (int) $x, (int) $y, $text, $color);
    }

    private static function drawTextRight($im, $text, $right, $y, $size, $color, $font = null) {
        $box = $font ? imagettfbbox($size, 0, $font, self::clean($text)) : [0, 0, strlen($text) * 8, 0];
        self::drawText($im, $text, $right - abs($box[2] - $box[0]), $y, $size, $color, $font);
    }

    private static function drawTextCenter($im, $text, $cx, $y, $size, $color, $font = null) {
        $box = $font ? imagettfbbox($size, 0, $font, self::clean($text)) : [0, 0, strlen($text) * 8, 0];
        self::drawText($im, $text, $cx - (abs($box[2] - $box[0]) / 2), $y, $size, $color, $font);
    }

    private static function pair($im, $label, $value, $left, $right, $y, $size, $color, $font) {
        self::drawText($im, $label, $left, $y, $size, $color, $font);
        self::drawTextRight($im, $value, $right, $y, $size, $color, $font);
    }

    private static function line($im, $x1, $y, $x2, $color) {
        imageline($im, (int) $x1, (int) $y, (int) $x2, (int) $y, $color);
    }

    private static function dash($im, $x1, $y, $x2, $color) {
        for ($x = $x1; $x < $x2; $x += 14) imageline($im, (int) $x, (int) $y, (int) min($x + 8, $x2), (int) $y, $color);
    }

    private static function roundedRect($im, $x1, $y1, $x2, $y2, $r, $fill, $border = null) {
        if ($r <= 0) {
            imagefilledrectangle($im, $x1, $y1, $x2, $y2, $fill);
            if ($border) imagerectangle($im, $x1, $y1, $x2, $y2, $border);
            return;
        }
        imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $fill);
        imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $fill);
        imagefilledellipse($im, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $fill);
        imagefilledellipse($im, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $fill);
        imagefilledellipse($im, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $fill);
        imagefilledellipse($im, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $fill);
        if ($border) imagerectangle($im, $x1, $y1, $x2, $y2, $border);
    }

    private static function drawQr($im, $value, $x, $y, $size, $c) {
        imagefilledrectangle($im, $x, $y, $x + $size, $y + $size, $c['paper']);
        $qrUrl = App::qrImageUrl($value, max(180, (int) $size * 3), 8);
        $qrBytes = $qrUrl !== '' ? @file_get_contents($qrUrl) : false;
        if (is_string($qrBytes) && $qrBytes !== '') {
            $qr = @imagecreatefromstring($qrBytes);
            if ($qr) {
                imagecopyresampled($im, $qr, (int) $x, (int) $y, 0, 0, (int) $size, (int) $size, imagesx($qr), imagesy($qr));
                imagedestroy($qr);
                return;
            }
        }

        $svg = App::qrSvg($value, $size);
        if (preg_match_all('/<rect x="(\d+)" y="(\d+)" width="1" height="1"\/>/', $svg, $m, PREG_SET_ORDER)) {
            $viewSize = 45;
            if (preg_match('/viewBox="0 0 (\d+) \d+"/', $svg, $vm)) {
                $viewSize = max(1, (int) $vm[1]);
            }
            $scale = $size / $viewSize;
            foreach ($m as $rect) {
                imagefilledrectangle($im, (int) ($x + ($rect[1] * $scale)), (int) ($y + ($rect[2] * $scale)), (int) ($x + (($rect[1] + 1) * $scale)), (int) ($y + (($rect[2] + 1) * $scale)), $c['title']);
            }
        }
    }

    private static function wrap($text, $chars) {
        return explode("\n", wordwrap(self::clean($text), $chars, "\n", true));
    }

    private static function fit($text, $chars) {
        $text = self::clean($text);
        return mb_strlen($text) > $chars ? mb_substr($text, 0, $chars - 1) . '.' : $text;
    }

    private static function clean($text) {
        return trim(preg_replace('/\s+/', ' ', (string) $text));
    }

    private static function money($value) {
        return preg_replace('/\.00\b/', '', App::money((float) $value));
    }

    private static function dateText($value) {
        return date('M d, Y h:i A', strtotime((string) $value) ?: time());
    }

    private static function absoluteUrl($path) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = defined('BASE_URL') ? BASE_URL : '';
        return $scheme . $host . $base . $path;
    }
}
