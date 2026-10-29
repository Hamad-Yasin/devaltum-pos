<?php

class Auth {
    
    public static function login($user) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $role = strtolower((string) ($user['role'] ?? ''));
        if ($role === Tenant::ROLE_ADMIN_LEGACY) {
            $role = Tenant::ROLE_STORE_OWNER;
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'] ?? '';
        $_SESSION['user_role'] = $role;
        $_SESSION['user_permissions'] = $user['permissions'] ?? '';
        $_SESSION['store_id'] = isset($user['store_id']) ? (int) $user['store_id'] : null;
        $_SESSION['store_name'] = $user['store_name'] ?? '';
    }

    public static function logout() {
        session_unset();
        session_destroy();
    }

    public static function check() {
        return isset($_SESSION['user_id']);
    }

    public static function user() {
        if (self::check()) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'] ?? '',
                'role' => $_SESSION['user_role'],
                'permissions' => $_SESSION['user_permissions'] ?? '',
                'store_id' => $_SESSION['store_id'] ?? null,
                'store_name' => $_SESSION['store_name'] ?? ''
            ];
        }
        return null;
    }

    public static function id() {
        return $_SESSION['user_id'] ?? null;
    }

    public static function isAdmin() {
        $role = $_SESSION['user_role'] ?? '';
        return self::check() && (
            $role === Tenant::ROLE_SUPER_ADMIN
            || $role === Tenant::ROLE_STORE_OWNER
            || $role === Tenant::ROLE_ADMIN_LEGACY
        );
    }

    public static function isSuperAdmin() {
        return self::check() && ($_SESSION['user_role'] ?? '') === Tenant::ROLE_SUPER_ADMIN;
    }

    public static function can($permission) {
        if (self::isAdmin()) {
            return true;
        }

        $raw = (string) ($_SESSION['user_permissions'] ?? '');
        if ($raw === '') {
            return false;
        }

        $permissions = array_filter(array_map('trim', explode(',', $raw)));
        return in_array($permission, $permissions, true);
    }
}
