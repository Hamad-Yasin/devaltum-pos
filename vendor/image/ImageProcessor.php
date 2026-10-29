<?php

class ImageProcessor {

    /**
     * Load image resource from path supporting PNG, JPEG, GIF, WEBP
     */
    private static function loadImage(string $filePath) {
        if (!file_exists($filePath)) {
            return false;
        }

        $info = getimagesize($filePath);
        if (!$info) return false;

        $mime = $info['mime'];
        switch ($mime) {
            case 'image/jpeg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                $img = imagecreatefrompng($filePath);
                imagealphablending($img, true);
                imagesavealpha($img, true);
                return $img;
            case 'image/gif':
                return imagecreatefromgif($filePath);
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($filePath);
                }
                return false;
            default:
                return false;
        }
    }

    /**
     * Resize image proportionally and save
     */
    public static function resize(string $srcPath, string $destPath, int $maxWidth, int $maxHeight, int $quality = 85): bool {
        $src = self::loadImage($srcPath);
        if (!$src) return false;

        $origW = imagesx($src);
        $origH = imagesy($src);

        $ratio = min($maxWidth / $origW, $maxHeight / $origH);
        if ($ratio >= 1.0) {
            // Already smaller than max dimensions, just copy/compress
            return self::compress($srcPath, $destPath, $quality);
        }

        $newW = (int) round($origW * $ratio);
        $newH = (int) round($origH * $ratio);

        $dst = imagecreatetruecolor($newW, $newH);

        // Preserve transparency
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        $ext = strtolower(pathinfo($destPath, PATHINFO_EXTENSION));
        $res = self::saveResource($dst, $destPath, $ext, $quality);

        imagedestroy($src);
        imagedestroy($dst);
        return $res;
    }

    /**
     * Crop image to exact square (e.g. for product avatar / user profile)
     */
    public static function cropSquare(string $srcPath, string $destPath, int $size = 300, int $quality = 85): bool {
        $src = self::loadImage($srcPath);
        if (!$src) return false;

        $origW = imagesx($src);
        $origH = imagesy($src);

        $cropSize = min($origW, $origH);
        $x = (int) (($origW - $cropSize) / 2);
        $y = (int) (($origH - $cropSize) / 2);

        $dst = imagecreatetruecolor($size, $size);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        imagecopyresampled($dst, $src, 0, 0, $x, $y, $size, $size, $cropSize, $cropSize);

        $ext = strtolower(pathinfo($destPath, PATHINFO_EXTENSION));
        $res = self::saveResource($dst, $destPath, $ext, $quality);

        imagedestroy($src);
        imagedestroy($dst);
        return $res;
    }

    /**
     * Convert any image to modern lightweight WebP format
     */
    public static function convertToWebP(string $srcPath, string $destWebpPath, int $quality = 80): bool {
        if (!function_exists('imagewebp')) {
            return false;
        }

        $src = self::loadImage($srcPath);
        if (!$src) return false;

        $dir = dirname($destWebpPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        imagepalettetotruecolor($src);
        imagealphablending($src, true);
        imagesavealpha($src, true);

        $res = imagewebp($src, $destWebpPath, $quality);
        imagedestroy($src);
        return $res;
    }

    /**
     * Compress image file in place or to new destination
     */
    public static function compress(string $srcPath, string $destPath, int $quality = 80): bool {
        $src = self::loadImage($srcPath);
        if (!$src) return false;

        $ext = strtolower(pathinfo($destPath, PATHINFO_EXTENSION));
        $res = self::saveResource($src, $destPath, $ext, $quality);
        imagedestroy($src);
        return $res;
    }

    private static function saveResource($resource, string $destPath, string $ext, int $quality): bool {
        $dir = dirname($destPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        switch ($ext) {
            case 'webp':
                return function_exists('imagewebp') ? imagewebp($resource, $destPath, $quality) : false;
            case 'png':
                $pngQuality = (int) max(0, min(9, round((100 - $quality) / 10)));
                return imagepng($resource, $destPath, $pngQuality);
            case 'gif':
                return imagegif($resource, $destPath);
            case 'jpg':
            case 'jpeg':
            default:
                return imagejpeg($resource, $destPath, $quality);
        }
    }
}
