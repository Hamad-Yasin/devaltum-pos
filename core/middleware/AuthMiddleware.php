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
            header('HTTP/1.0 403 Forbidden');
            echo "403 Forbidden - Admin Access Required";
            exit;
        }
    }

    public static function handleSuperAdmin() {
        self::handle();
        if (!Auth::isSuperAdmin()) {
            header('HTTP/1.0 403 Forbidden');
            echo "403 Forbidden - Super Admin Access Required";
            exit;
        }
    }

    public static function handlePermission($permission) {
        self::handle();
        if (Auth::isSuperAdmin()) {
            return;
        }
        if (!Auth::can($permission)) {
            header('HTTP/1.0 403 Forbidden');
            echo "403 Forbidden - You do not have access to this section.";
            exit;
        }
    }
}
