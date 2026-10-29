<?php

class BarcodeGenerator {

    /**
     * Generate Code128 barcode as pure scalable SVG string
     */
    public static function getBarcodeSVG(string $code, int $widthFactor = 2, int $totalHeight = 50, string $color = '#000000'): string {
        $code = trim($code);
        if (empty($code)) return '';

        $bars = self::encodeCode128($code);
        $totalBars = strlen($bars);
        $svgWidth = $totalBars * $widthFactor;

        $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='{$svgWidth}' height='{$totalHeight}' viewBox='0 0 {$svgWidth} {$totalHeight}'>";
        $svg .= "<rect width='{$svgWidth}' height='{$totalHeight}' fill='transparent'/>";

        $x = 0;
        for ($i = 0; $i < $totalBars; $i++) {
            $w = $widthFactor;
            if ($bars[$i] === '1') {
                $svg .= "<rect x='{$x}' y='0' width='{$w}' height='{$totalHeight}' fill='{$color}'/>";
            }
            $x += $w;
        }

        $svg .= "</svg>";
        return $svg;
    }

    /**
     * Generate Base64 Data URI of SVG Barcode
     */
    public static function getBarcodeDataUri(string $code, int $widthFactor = 2, int $totalHeight = 50): string {
        $svg = self::getBarcodeSVG($code, $widthFactor, $totalHeight);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Encode Code128-B Binary Pattern
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

        // Start B = 104
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

        // Check character
        $checkVal = $checkSum % 103;
        $binary .= self::patternToBinary($patterns[$checkVal]);

        // Stop character = 106
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
