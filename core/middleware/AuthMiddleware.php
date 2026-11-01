<?php

class AuthMiddleware {
    
    public static function handle() {
        if (!Auth::check()) {
            $baseUrl = defined('BASE_URL') ? BASE_URL : '';
            header('Location: ' . $baseUrl . '/auth/login');
            exit;
        }
        Tenant::requireStoreContext();

        if (Tenant::isStoreExpired()) {
            $currentUrl = strtolower(trim((string) ($_GET['url'] ?? '')));
            $allowedPrefixes = ['plans/', 'plans', 'auth/logout', 'auth/profile'];
            $isAllowed = false;
            foreach ($allowedPrefixes as $ap) {
                if ($currentUrl === $ap || str_starts_with($currentUrl, $ap)) {
                    $isAllowed = true;
                    break;
                }
            }
            if (!$isAllowed) {
                $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                header('Location: ' . $baseUrl . '/plans/pay');
                exit;
            }
        }
    }

    public static function handleAdmin() {
        self::handle();
        if (!Auth::isAdmin() || Auth::isSuperAdmin()) {
            if (!Auth::isAdmin()) {
                $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                $landing = Auth::defaultStaffUrl();
                header('Location: ' . $baseUrl . '/' . $landing);
                exit;
            }
            App::renderErrorPage(403, 'Admin Access Required (403)', 'This section requires administrator privileges. Don\'t worry, your active sale session is completely safe. Click below to return to your previous page or open POS.');
        }
    }

    public static function handleSuperAdmin() {
        self::handle();
        if (!Auth::isSuperAdmin()) {
            $baseUrl = defined('BASE_URL') ? BASE_URL : '';
            header('Location: ' . $baseUrl . '/' . Auth::defaultLandingUrl());
            exit;
        }
    }

    public static function handlePermission($permission) {
        self::handle();
        if (Auth::isSuperAdmin()) {
            return;
        }
        if (!Auth::can($permission)) {
            $baseUrl = defined('BASE_URL') ? BASE_URL : '';
            $landing = Auth::defaultStaffUrl();
            $currentPath = trim((string) ($_GET['url'] ?? ''), '/');
            if ($landing && $landing !== $currentPath && !str_starts_with($currentPath, $landing)) {
                header('Location: ' . $baseUrl . '/' . $landing);
                exit;
            }
            App::renderErrorPage(403, 'Access Restricted (403)', 'Your account does not currently have permission to access this section. Don\'t worry, your active work is completely safe. Click below to return to your previous page or open POS.');
        }
    }
}
