<?php
/**
 * DevAltum POS SaaS Pro — Universal Vendor Autoloader
 */

spl_autoload_register(function ($class) {
    // 1. Check PSR-4 for PHPMailer
    $prefix = 'PHPMailer\\PHPMailer\\';
    $baseDir = __DIR__ . '/phpmailer/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) === 0) {
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // 2. Check vendor subdirectories for class files
    $vendorDirs = [
        __DIR__ . '/phpmailer',
        __DIR__ . '/whatsapp',
        __DIR__ . '/image',
        __DIR__ . '/barcode',
        __DIR__ . '/pdf',
    ];

    foreach ($vendorDirs as $dir) {
        $file = $dir . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
