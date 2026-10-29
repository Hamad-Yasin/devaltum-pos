<?php
date_default_timezone_set('Asia/Karachi');

$sessionLifetime = 60 * 60 * 24 * 180;
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) === '443')
);

// Detect application base URL (supports both root and subfolder hosting smoothly)
$rawScript = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if ($rawScript === '/' || $rawScript === '.') {
    $rawScript = '';
}
$reqUriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
if (str_ends_with($rawScript, '/public') && !str_contains($reqUriPath, '/public')) {
    $rawScript = substr($rawScript, 0, -7);
}
$rawScript = rtrim($rawScript, '/');
define('BASE_URL', $rawScript);

ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');

$tabSessionId = (string) ($_GET['tab_session'] ?? $_POST['tab_session'] ?? '');
$hasTabSession = $tabSessionId !== '' && preg_match('/^[A-Za-z0-9,-]{16,128}$/', $tabSessionId);
if ($hasTabSession) {
    ini_set('session.use_cookies', '0');
    ini_set('session.use_only_cookies', '0');
    session_id($tabSessionId);
    define('TAB_SESSION_ID', $tabSessionId);
} else {
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => BASE_URL ?: '/',
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax'
    ]);
}
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=(self)');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https://api.qrserver.com; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
if ($isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('MODULES_PATH', ROOT_PATH . '/modules');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('VENDOR_PATH', ROOT_PATH . '/vendor');

// Immediate Static Asset Server for Subfolder Deployments (Guarantees CSS/JS/Images always load)
$rawReqUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$urlParam = $_GET['url'] ?? '';

$matchedAsset = null;
if (preg_match('#(?:^|/)(assets/.+|uploads/.+|sw\.js|manifest\.json|offline\.html|favicon\.ico)$#i', $rawReqUri, $m)) {
    $matchedAsset = $m[1];
} elseif (preg_match('#^(assets/.+|uploads/.+|sw\.js|manifest\.json|offline\.html|favicon\.ico)$#i', $urlParam, $m)) {
    $matchedAsset = $m[1];
}

if ($matchedAsset !== null) {
    $safeRelPath = ltrim(str_replace(['../', '..\\'], '', $matchedAsset), '/');
    $physicalFile = ROOT_PATH . '/public/' . $safeRelPath;
    if (file_exists($physicalFile) && is_file($physicalFile)) {
        $ext = strtolower(pathinfo($physicalFile, PATHINFO_EXTENSION));
        $mimes = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'json'  => 'application/json; charset=UTF-8',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'webp'  => 'image/webp',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'eot'   => 'application/vnd.ms-fontobject',
            'pdf'   => 'application/pdf',
            'html'  => 'text/html; charset=UTF-8'
        ];
        $contentType = $mimes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . filesize($physicalFile));
        header('Cache-Control: public, max-age=2592000');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($physicalFile)) . ' GMT');
        readfile($physicalFile);
        exit;
    }
}

// 1. Load Enterprise Environment (.env)
if (file_exists(CORE_PATH . '/helpers/Env.php')) {
    require_once CORE_PATH . '/helpers/Env.php';
    Env::load(ROOT_PATH . '/.env');
}

// 2. Load Vendor Packages Autoloader (PHPMailer, WhatsApp, Image, Barcode, PDF)
if (file_exists(VENDOR_PATH . '/autoload.php')) {
    require_once VENDOR_PATH . '/autoload.php';
}

// 3. Autoloader for core and app classes
spl_autoload_register(function ($className) {
    if (file_exists(CORE_PATH . '/' . $className . '.php')) {
        require_once CORE_PATH . '/' . $className . '.php';
        return;
    }
    
    // License inclusion
    if ($className === 'License' && file_exists(CORE_PATH . '/License.php')) {
        require_once CORE_PATH . '/License.php';
        return;
    }
    
    $paths = [
        CORE_PATH . '/database', 
        CORE_PATH . '/auth', 
        CORE_PATH . '/helpers', 
        CORE_PATH . '/middleware'
    ];
    
    foreach ($paths as $path) {
        $file = $path . '/' . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Check app folders (controllers, models)
    $appPaths = [APP_PATH . '/controllers', APP_PATH . '/models'];
    foreach ($appPaths as $path) {
        $file = $path . '/' . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load Configuration if installed
if (file_exists(CONFIG_PATH . '/database.php')) {
    require_once CONFIG_PATH . '/database.php';
    $db = Database::getInstance();
    if ($db->hasConnection()) {
        App::boot();
    } else {
        define('APP_INSTALL_REQUIRED', true);
    }
}

require_once CORE_PATH . '/Router.php';

$router = new Router();
$router->dispatch($_SERVER['REQUEST_URI']);
