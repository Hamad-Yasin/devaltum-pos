<?php
/**
 * DevAltum POS Desktop Internal Router
 * Simulates Nginx try_files $uri $uri/ /public/index.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$docRoot = $_SERVER['DOCUMENT_ROOT'];

// 1. Check if direct file exists in document root or public/
$directFile = $docRoot . $uri;
$publicFile = $docRoot . '/public' . $uri;

if ($uri !== '/' && file_exists($directFile) && !is_dir($directFile)) {
    return false; // Serve static file directly
}

if ($uri !== '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    // Send correct content-type for public static assets
    $ext = pathinfo($publicFile, PATHINFO_EXTENSION);
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'gif' => 'image/gif',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'json' => 'application/json'
    ];
    if (isset($mimes[$ext])) {
        header("Content-Type: {$mimes[$ext]}");
    }
    header("Cache-Control: public, max-age=604800, immutable");
    readfile($publicFile);
    exit;
}

// 2. Route to POS entrypoint
$_SERVER['SCRIPT_NAME'] = '/public/index.php';
$_SERVER['SCRIPT_FILENAME'] = $docRoot . '/public/index.php';

if (file_exists($docRoot . '/public/index.php')) {
    require $docRoot . '/public/index.php';
} elseif (file_exists($docRoot . '/index.php')) {
    require $docRoot . '/index.php';
} else {
    echo "<h1>DevAltum POS Engine Ready</h1><p>Application core loaded successfully.</p>";
}

