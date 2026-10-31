<?php

/**
 * DevAltum POS - Barcode MS Word (.docx) Generator
 * High-precision, zero-external-dependency DOCX generator for barcode sticker sheets.
 */
class BarcodeDocxHelper {

    /**
     * Generate Code128 PNG image data using PHP GD
     */
    public static function generateBarcodePng(string $code, int $width = 280, int $height = 80): string {
        $code = trim($code);
        if (empty($code)) {
            $code = '00000000';
        }

        // Get Code128 binary pattern
        $bars = self::encodeCode128($code);
        $totalBars = strlen($bars);
        
        $imgWidth = max($width, $totalBars * 2);
        $imgHeight = $height;

        $im = imagecreate($imgWidth, $imgHeight);
        $bg = imagecolorallocate($im, 255, 255, 255); // White background
        $fg = imagecolorallocate($im, 0, 0, 0);       // Black bars

        // Calculate horizontal centering
        $barWidth = (int) floor($imgWidth / $totalBars);
        if ($barWidth < 1) $barWidth = 1;
        $actualBarsWidth = $totalBars * $barWidth;
        $startX = (int) max(0, floor(($imgWidth - $actualBarsWidth) / 2));

        for ($i = 0; $i < $totalBars; $i++) {
            if ($bars[$i] === '1') {
                $x1 = $startX + ($i * $barWidth);
                $x2 = $x1 + $barWidth - 1;
                imagefilledrectangle($im, $x1, 0, $x2, $imgHeight - 1, $fg);
            }
        }

        ob_start();
        imagepng($im);
        $pngData = ob_get_clean();
        imagedestroy($im);

        return $pngData;
    }

    /**
     * Generate standalone MS Word (.docx) package binary
     */
    public static function createDocx(array $params): string {
        $storeName = trim($params['store_name'] ?? 'DevAltum Store');
        $productName = trim($params['product_name'] ?? 'Product Name');
        $barcode = trim($params['barcode'] ?? '123456789012');
        $price = (float) ($params['price'] ?? 0);
        $currency = trim($params['currency'] ?? 'Rs');
        $quantity = max(1, min(1000, (int) ($params['quantity'] ?? 30)));
        $columns = max(1, min(6, (int) ($params['columns'] ?? 3)));
        $formattedPrice = $currency . ' ' . number_format($price, 2);

        // 1. Generate crisp Barcode PNG
        $barcodePng = self::generateBarcodePng($barcode, 260, 65);

        // 2. Build WordprocessingML table rows
        $rowsXml = '';
        $totalRows = (int) ceil($quantity / $columns);
        $stickerCount = 0;

        // Dimensions (in twips/dxa: 1mm = 56.7 dxa. Standard 3-column cell ~1900 dxa)
        $cellWidthDxa = $columns === 3 ? 2800 : ($columns === 2 ? 4200 : 2100);

        for ($r = 0; $r < $totalRows; $r++) {
            $rowCells = '';
            for ($c = 0; $c < $columns; $c++) {
                if ($stickerCount < $quantity) {
                    $rowCells .= self::buildCellXml($storeName, $productName, $barcode, $formattedPrice, $cellWidthDxa);
                    $stickerCount++;
                } else {
                    // Empty spacer cell to preserve table structure
                    $rowCells .= "<w:tc><w:tcPr><w:tcW w:w=\"{$cellWidthDxa}\" w:type=\"dxa\"/><w:tcBorders><w:top w:val=\"none\"/><w:left w:val=\"none\"/><w:bottom w:val=\"none\"/><w:right w:val=\"none\"/></w:tcBorders></w:tcPr><w:p/></w:tc>";
                }
            }
            $rowsXml .= "<w:tr><w:trPr><w:cantSplit/></w:trPr>{$rowCells}</w:tr>";
        }

        // 3. Document XML
        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" ' .
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" ' .
            'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" ' .
            'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">' .
            '<w:body>' .
            '<w:tbl>' .
            '<w:tblPr>' .
            '<w:tblW w:w="0" w:type="auto"/>' .
            '<w:tblCellMar>' .
            '<w:top w:w="120" w:type="dxa"/>' .
            '<w:bottom w:w="120" w:type="dxa"/>' .
            '<w:left w:w="120" w:type="dxa"/>' .
            '<w:right w:w="120" w:type="dxa"/>' .
            '</w:tblCellMar>' .
            '<w:jc w:val="center"/>' .
            '</w:tblPr>' .
            $rowsXml .
            '</w:tbl>' .
            '<w:sectPr>' .
            '<w:pgSz w:w="11906" w:h="16838"/>' . // Standard A4 page size
            '<w:pgMar w:top="360" w:right="360" w:bottom="360" w:left="360" w:header="0" w:footer="0" w:gutter="0"/>' .
            '</w:sectPr>' .
            '</w:body>' .
            '</w:document>';

        // 4. Supporting OpenXML files
        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Default Extension="png" ContentType="image/png"/>' .
            '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>' .
            '</Types>';

        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>' .
            '</Relationships>';

        $docRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rIdBarcode" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/barcode.png"/>' .
            '</Relationships>';

        // 5. Package as ZIP (.docx) in memory
        $tempZip = tempnam(sys_get_temp_dir(), 'docx_');
        $zip = new ZipArchive();
        if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Unable to create DOCX temporary file");
        }

        $zip->addFromString('[Content_Types].xml', $contentTypesXml);
        $zip->addFromString('_rels/.rels', $relsXml);
        $zip->addFromString('word/_rels/document.xml.rels', $docRelsXml);
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->addFromString('word/media/barcode.png', $barcodePng);
        $zip->close();

        $docxBinary = file_get_contents($tempZip);
        @unlink($tempZip);

        return $docxBinary;
    }

    /**
     * XML for an individual sticker cell in Word
     */
    private static function buildCellXml(string $storeName, string $productName, string $barcode, string $price, int $cellWidthDxa): string {
        $cleanStore = htmlspecialchars(mb_strtoupper($storeName), ENT_XML1, 'UTF-8');
        $cleanProduct = htmlspecialchars($productName, ENT_XML1, 'UTF-8');
        $cleanBarcode = htmlspecialchars($barcode, ENT_XML1, 'UTF-8');
        $cleanPrice = htmlspecialchars($price, ENT_XML1, 'UTF-8');

        // Dimensions: barcode image width: ~30mm = 1,080,000 EMUs, height: ~8mm = 288,000 EMUs
        $cx = 1080000;
        $cy = 288000;

        return '<w:tc>' .
            '<w:tcPr>' .
            "<w:tcW w:w=\"{$cellWidthDxa}\" w:type=\"dxa\"/>" .
            '<w:tcBorders>' .
            '<w:top w:val="dashed" w:sz="4" w:space="0" w:color="CCCCCC"/>' .
            '<w:left w:val="dashed" w:sz="4" w:space="0" w:color="CCCCCC"/>' .
            '<w:bottom w:val="dashed" w:sz="4" w:space="0" w:color="CCCCCC"/>' .
            '<w:right w:val="dashed" w:sz="4" w:space="0" w:color="CCCCCC"/>' .
            '</w:tcBorders>' .
            '<w:tcMar>' .
            '<w:top w:w="80" w:type="dxa"/>' .
            '<w:bottom w:w="80" w:type="dxa"/>' .
            '<w:left w:w="80" w:type="dxa"/>' .
            '<w:right w:w="80" w:type="dxa"/>' .
            '</w:tcMar>' .
            '<w:vAlign w:val="center"/>' .
            '</w:tcPr>' .

            // 1. Store Name Header
            '<w:p>' .
            '<w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="0" w:line="200" w:lineRule="auto"/></w:pPr>' .
            '<w:r>' .
            '<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="15"/><w:color w:val="0B132B"/></w:rPr>' .
            "<w:t>{$cleanStore}</w:t>" .
            '</w:r>' .
            '</w:p>' .

            // 2. Product Name
            '<w:p>' .
            '<w:pPr><w:jc w:val="center"/><w:spacing w:before="20" w:after="70" w:line="200" w:lineRule="auto"/></w:pPr>' .
            '<w:r>' .
            '<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="14"/><w:color w:val="1E293B"/></w:rPr>' .
            "<w:t>{$cleanProduct}</w:t>" .
            '</w:r>' .
            '</w:p>' .

            // 3. Barcode Image (Added vertical clearance above barcode to prevent touching text)
            '<w:p>' .
            '<w:pPr><w:jc w:val="center"/><w:spacing w:before="50" w:after="20" w:line="200" w:lineRule="auto"/></w:pPr>' .
            '<w:r>' .
            '<w:drawing>' .
            '<wp:inline distT="0" distB="0" distL="0" distR="0">' .
            "<wp:extent cx=\"{$cx}\" cy=\"{$cy}\"/>" .
            '<wp:docPr id="' . rand(100, 999999) . '" name="Barcode"/>' .
            '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">' .
            '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">' .
            '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">' .
            '<pic:nvPicPr>' .
            '<pic:cNvPr id="0" name="Barcode.png"/>' .
            '<pic:cNvPicPr/>' .
            '</pic:nvPicPr>' .
            '<pic:blipFill>' .
            '<a:blip r:embed="rIdBarcode"/>' .
            '<a:stretch><a:fillRect/></a:stretch>' .
            '</pic:blipFill>' .
            '<pic:spPr>' .
            '<a:xfrm>' .
            '<a:off x="0" y="0"/>' .
            "<a:ext cx=\"{$cx}\" cy=\"{$cy}\"/>" .
            '</a:xfrm>' .
            '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>' .
            '</pic:spPr>' .
            '</pic:pic>' .
            '</a:graphicData>' .
            '</a:graphic>' .
            '</wp:inline>' .
            '</w:drawing>' .
            '</w:r>' .
            '</w:p>' .

            // 4. Barcode Numeric String
            '<w:p>' .
            '<w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="20" w:line="180" w:lineRule="auto"/></w:pPr>' .
            '<w:r>' .
            '<w:rPr><w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/><w:sz w:val="13"/><w:color w:val="475569"/></w:rPr>' .
            "<w:t>{$cleanBarcode}</w:t>" .
            '</w:r>' .
            '</w:p>' .

            // 5. Formatted Price
            '<w:p>' .
            '<w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="0" w:line="200" w:lineRule="auto"/></w:pPr>' .
            '<w:r>' .
            '<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:b/><w:sz w:val="18"/><w:color w:val="059669"/></w:rPr>' .
            "<w:t>{$cleanPrice}</w:t>" .
            '</w:r>' .
            '</w:p>' .

            '</w:tc>';
    }

    /**
     * Encode Code128 Binary Pattern
     */
    private static function encodeCode128(string $text): string {
        $patterns = [
            '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
            '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
            '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
            '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
            '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
            '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
            '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
            '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
            '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
            '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
            '114131', '311141', '411131', '211412', '211214', '211232', '2331112'
        ];

        $checkSum = 104;
        $binary = self::patternToBinary($patterns[104]);

        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $charVal = ord($text[$i]) - 32;
            if ($charVal >= 0 && $charVal <= 95) {
                $binary .= self::patternToBinary($patterns[$charVal]);
                $checkSum += $charVal * ($i + 1);
            }
        }

        $checkVal = $checkSum % 103;
        $binary .= self::patternToBinary($patterns[$checkVal]);
        $binary .= self::patternToBinary($patterns[106]);

        return $binary;
    }

    private static function patternToBinary(string $pattern): string {
        $bin = '';
        $len = strlen($pattern);
        $bar = true;
        for ($i = 0; $i < $len; $i++) {
            $count = (int)$pattern[$i];
            $bin .= str_repeat($bar ? '1' : '0', $count);
            $bar = !$bar;
        }
        return $bin;
    }
}

