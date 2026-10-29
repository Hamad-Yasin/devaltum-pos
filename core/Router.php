<?php

class Router {
    protected $routes = [];

    public function dispatch($url) {
        $url = $this->removeQueryStringVariables($url);
        
        // 1. Strip BASE_URL prefix if hosted in a subfolder
        if (defined('BASE_URL') && BASE_URL !== '') {
            if (str_starts_with($url, BASE_URL)) {
                $url = substr($url, strlen(BASE_URL));
            }
        }

        // 2. Strip script directory if present
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptName !== '/' && $scriptName !== '') {
            if (str_starts_with($url, $scriptName)) {
                $url = substr($url, strlen($scriptName));
            } else {
                $url = str_replace($scriptName, '', $url);
            }
        }
        
        if (isset($_GET['url']) && !empty($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
        } else {
            $url = ltrim($url, '/');
        }

        // Strip leading "public/" or "index.php" if present from subfolder rewrite
        $url = preg_replace('#^(public/|public$|index\.php/|index\.php$)#i', '', $url);
        $url = trim($url, '/');

        if (empty($url)) {
            $controllerName = 'HomeController';
            $actionName = 'index';
            $params = [];
        } else {
            $urlParts = explode('/', filter_var($url, FILTER_SANITIZE_URL));
            $controllerName = !empty($urlParts[0]) ? ucfirst($urlParts[0]) . 'Controller' : 'HomeController';
            $actionName = !empty($urlParts[1]) ? $urlParts[1] : 'index';
            $params = array_slice($urlParts, 2);
        }

        $isInstalled = class_exists('App') && method_exists('App', 'isInstalled') ? App::isInstalled() : false;

        // 1. If application IS INSTALLED, permanently lock and block the installer
        if ($isInstalled) {
            if ($controllerName === 'InstallerController') {
                $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                header('Location: ' . $baseUrl . '/auth/login');
                exit;
            }

            // If app was installed, but database connection failed (e.g. MySQL down or config edited wrongly)
            if (defined('APP_INSTALL_REQUIRED') && APP_INSTALL_REQUIRED) {
                http_response_code(503);
                $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Database Connection Error - POS</title><meta name="viewport" content="width=device-width,initial-scale=1">';
                echo '<style>body{margin:0;padding:24px;background:#0b1329;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;}';
                echo '.db-card{background:#131f37;border:1px solid #1e293b;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);border-radius:16px;max-width:540px;width:100%;padding:36px;box-sizing:border-box;}';
                echo '.badge{display:inline-block;padding:4px 12px;background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3);border-radius:999px;font-size:12px;font-weight:600;margin-bottom:16px;}';
                echo 'h1{font-size:22px;margin:0 0 12px;font-weight:700;color:#fff;}p{color:#94a3b8;font-size:14px;line-height:1.6;margin:0 0 16px;}';
                echo 'code{background:#0b1329;border:1px solid #1e293b;padding:3px 8px;border-radius:6px;color:#38bdf8;font-size:13px;font-family:Consolas,monospace;}';
                echo '.btn{display:inline-flex;align-items:center;gap:8px;background:#2563eb;color:#fff;text-decoration:none;padding:12px 24px;border-radius:8px;font-size:14px;font-weight:600;margin-top:8px;transition:0.2s;}';
                echo '.btn:hover{background:#1d4ed8;}</style></head><body>';
                echo '<div class="db-card"><div class="badge">Connection Alert</div>';
                echo '<h1>Database Connection Error</h1>';
                echo '<p>The application is already installed, but could not connect to your MySQL database server.</p>';
                echo '<p>Please check your database credentials in <code>config/database.php</code> or verify that MySQL is active.</p>';
                echo '<a href="' . htmlspecialchars($baseUrl) . '/" class="btn">Retry Connection</a>';
                echo '</div></body></html>';
                exit;
            }

            // --- SECURE LICENSE CHECK ---
            if (!License::isValid()) {
                if ($controllerName !== 'LicenseactivationController') {
                    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                    header('Location: ' . $baseUrl . '/licenseactivation/index');
                    exit;
                }
            }
        } else {
            // 2. Application is NOT INSTALLED yet (Initial fresh install setup)
            if (!is_dir(ROOT_PATH . '/installer')) {
                http_response_code(404);
                echo "<h1>404 Not Found</h1><p>Please contact the development team.</p>";
                exit;
            }
            if ($controllerName !== 'InstallerController') {
                $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                header('Location: ' . $baseUrl . '/installer/index');
                exit;
            }
        }

        $dispatchController = function($controllerObject, $actionName, $params) {
            $target = $actionName;
            if (method_exists($controllerObject, $target)) {
                $ref = new ReflectionMethod($controllerObject, $target);
                if ($ref->isPublic()) {
                    call_user_func_array([$controllerObject, $target], $params);
                    return true;
                }
            }
            if ($actionName === 'view') {
                if (method_exists($controllerObject, 'show')) {
                    call_user_func_array([$controllerObject, 'show'], $params);
                    return true;
                }
                if (method_exists($controllerObject, 'details')) {
                    call_user_func_array([$controllerObject, 'details'], $params);
                    return true;
                }
            }
            if (method_exists($controllerObject, $actionName . 'Action')) {
                call_user_func_array([$controllerObject, $actionName . 'Action'], $params);
                return true;
            }
            return false;
        };

        // Standard App Controller
        $controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $controllerObject = new $controllerName();
            if ($dispatchController($controllerObject, $actionName, $params)) {
                return;
            }
        } 

        // Installer controller fallback
        if ($controllerName === 'InstallerController') {
            $installerControllerFile = ROOT_PATH . '/installer/InstallerController.php';
            if (file_exists($installerControllerFile)) {
                require_once $installerControllerFile;
                $controllerObject = new $controllerName();
                if ($dispatchController($controllerObject, $actionName, $params)) {
                    return;
                }
            }
        }
        
        // Modular loading
        $moduleName = strtolower(str_replace('Controller', '', $controllerName));
        $moduleControllerFile = MODULES_PATH . '/' . $moduleName . '/controllers/' . $controllerName . '.php';
        if (file_exists($moduleControllerFile)) {
            require_once $moduleControllerFile;
            $controllerObject = new $controllerName();
            if ($dispatchController($controllerObject, $actionName, $params)) {
                return;
            }
        }

        $this->sendNotFound();
    }

    protected function removeQueryStringVariables($url) {
        if ($url != '') {
            $parts = explode('?', $url, 2);
            $url = $parts[0];
        }
        return $url;
    }

    protected function sendNotFound() {
        header("HTTP/1.0 404 Not Found");
        echo "404 Not Found";
        exit;
    }
}
