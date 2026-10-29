<?php

class Tenant {
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_STORE_OWNER = 'store_owner';
    public const ROLE_STAFF = 'staff';
    public const ROLE_ADMIN_LEGACY = 'admin';
    private static $storeContext = null;
    private static $storeContextId = null;

    public static function userRole() {
        return strtolower((string) ($_SESSION['user_role'] ?? ''));
    }

    public static function isSuperAdmin() {
        return self::userRole() === self::ROLE_SUPER_ADMIN;
    }

    public static function isStoreOwner() {
        return self::userRole() === self::ROLE_STORE_OWNER || self::userRole() === self::ROLE_ADMIN_LEGACY;
    }

    public static function isStaff() {
        return self::userRole() === self::ROLE_STAFF;
    }

    public static function currentStoreId() {
        if (self::isSuperAdmin()) {
            return null;
        }
        $storeId = (int) ($_SESSION['store_id'] ?? 0);
        return $storeId > 0 ? $storeId : null;
    }

    public static function currentStore() {
        $storeId = self::currentStoreId();
        if ($storeId === null) {
            self::$storeContext = null;
            self::$storeContextId = null;
            return null;
        }
        if (self::$storeContextId === $storeId && is_array(self::$storeContext)) {
            return self::$storeContext;
        }

        self::$storeContext = Database::getInstance()->fetch("SELECT * FROM stores WHERE id = ? LIMIT 1", [$storeId]) ?: null;
        self::$storeContextId = $storeId;
        return self::$storeContext;
    }

    public static function isBranchAccount() {
        $store = self::currentStore();
        return (int) ($store['parent_store_id'] ?? 0) > 0;
    }

    public static function parentStoreId() {
        $store = self::currentStore();
        $parentId = (int) ($store['parent_store_id'] ?? 0);
        return $parentId > 0 ? $parentId : null;
    }

    public static function hasStoreContext() {
        return self::currentStoreId() !== null;
    }

    public static function storeFilter($alias = '') {
        $storeId = self::currentStoreId();
        if ($storeId === null) {
            return ['1=1', []];
        }

        $prefix = $alias !== '' ? rtrim($alias, '.') . '.' : '';
        return [$prefix . 'store_id = ?', [$storeId]];
    }

    public static function requireStoreContext() {
        if (!self::isSuperAdmin() && !self::hasStoreContext()) {
            Auth::logout();
            header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/auth/login');
            exit;
        }
    }

    public static function isStoreExpired() {
        if (self::isSuperAdmin()) {
            return false;
        }
        $store = self::currentStore();
        if (!$store) {
            return false;
        }
        $status = strtolower((string) ($store['status'] ?? 'active'));
        if ($status === 'expired' || $status === 'inactive') {
            return true;
        }
        if (!empty($store['subscription_expires_at'])) {
            return strtotime((string) $store['subscription_expires_at']) < time();
        }
        return false;
    }
}
