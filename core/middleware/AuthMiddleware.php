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
            header('HTTP/1.0 403 Forbidden');
            echo "403 Forbidden - Admin Access Required";
            exit;
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
            header('HTTP/1.0 403 Forbidden');
            echo "403 Forbidden - You do not have access to this section.";
            exit;
        }
    }
}
