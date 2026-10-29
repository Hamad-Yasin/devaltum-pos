<?php

class SchemaManager {

    const SCHEMA_VERSION = '2.6.0';
    const SCHEMA_BUILD = 20261016;

    /**
     * Get unique checksum representing current codebase schema definitions
     */
    public static function getSchemaChecksum() {
        return md5(self::SCHEMA_VERSION . '_' . self::SCHEMA_BUILD . '_pos_enterprise_v26');
    }

    /**
     * Check if auto-sync is needed and run it silently if required
     */
    public static function autoSyncIfNeeded($db) {
        if (!$db || !$db->getConnection()) {
            return false;
        }

        try {
            $currentChecksum = self::getSchemaChecksum();
            
            // Check if settings table exists
            $settingsExists = (bool) $db->fetch("SHOW TABLES LIKE 'settings'");
            if (!$settingsExists) {
                return self::sync($db);
            }

            // Check if users table exists
            $usersExists = (bool) $db->fetch("SHOW TABLES LIKE 'users'");
            if (!$usersExists) {
                return self::sync($db);
            }

            $savedChecksum = '';
            try {
                $row = $db->fetch("SELECT setting_value FROM settings WHERE setting_key = 'schema_checksum' LIMIT 1");
                $savedChecksum = (string) ($row['setting_value'] ?? '');
            } catch (\Throwable $e) {}

            // If checksum differs or missing, sync database automatically
            if ($savedChecksum !== $currentChecksum) {
                return self::sync($db);
            }

            return true;
        } catch (\Throwable $e) {
            error_log('SchemaManager::autoSyncIfNeeded error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Run full database schema synchronization (Tables, Columns, Indexes, Data Types, Defaults)
     */
    public static function sync($connection) {
        $pdo = null;
        $db = null;

        if ($connection instanceof PDO) {
            $pdo = $connection;
        } elseif ($connection instanceof Database) {
            $db = $connection;
            $pdo = $db->getConnection();
        } elseif (is_object($connection) && method_exists($connection, 'getConnection')) {
            $db = $connection;
            $pdo = $connection->getConnection();
        }

        if (!$pdo) {
            return false;
        }

        try {
            // 1. Create All System Tables
            self::createAllTables($pdo);

            // 2. Add / Verify Missing Columns across all tables
            self::ensureAllColumns($pdo);

            // 3. Ensure Fractional / Decimal Precision for Inventory & POS
            self::ensureDecimalDataTypes($pdo);

            // 4. Ensure Optimized Indexes
            self::ensureAllIndexes($pdo);

            // 5. Seed Missing System Settings, Plans, Payment Methods & Defaults
            self::seedSystemDefaults($pdo);

            // 6. Migrate Legacy Records & Normalize Roles
            self::normalizeAndMigrateData($pdo);

            // 7. Update Sync Metadata
            $checksum = self::getSchemaChecksum();
            $now = date('Y-m-d H:i:s');
            
            self::executeStatement($pdo, "
                INSERT INTO settings (setting_key, setting_value) 
                VALUES ('schema_version', ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ", [self::SCHEMA_VERSION]);

            self::executeStatement($pdo, "
                INSERT INTO settings (setting_key, setting_value) 
                VALUES ('schema_checksum', ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ", [$checksum]);

            self::executeStatement($pdo, "
                INSERT INTO settings (setting_key, setting_value) 
                VALUES ('last_schema_sync_at', ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ", [$now]);

            // Ensure installed lock exists if users exist
            try {
                $userCount = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                if ($userCount > 0) {
                    if (class_exists('App') && method_exists('App', 'markAsInstalled')) {
                        App::markAsInstalled();
                    }
                }
            } catch (\Throwable $e) {}

            return true;
        } catch (\Throwable $e) {
            error_log('SchemaManager::sync failure: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create All Required Database Tables
     */
    private static function createAllTables(PDO $pdo) {
        $tables = [
            "CREATE TABLE IF NOT EXISTS `settings` (
                `setting_key` VARCHAR(100) PRIMARY KEY,
                `setting_value` LONGTEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `name` VARCHAR(100) NOT NULL,
                `username` VARCHAR(100) NULL,
                `email` VARCHAR(100) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `role` VARCHAR(30) DEFAULT 'staff',
                `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                `permissions` TEXT NULL,
                `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
                `two_factor_secret` VARCHAR(100) NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `stores` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_uid` VARCHAR(36) NOT NULL UNIQUE,
                `store_name` VARCHAR(150) NOT NULL,
                `owner_name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(120) NOT NULL,
                `phone` VARCHAR(50) NULL,
                `address` VARCHAR(255) NULL,
                `plan_type` VARCHAR(30) NOT NULL DEFAULT 'free',
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `current_plan_id` INT NULL,
                `parent_store_id` INT NULL,
                `branch_code` VARCHAR(40) NULL,
                `scanner_pair_code` VARCHAR(6) NULL,
                `subscription_started_at` DATETIME NULL,
                `subscription_expires_at` DATETIME NULL,
                `payment_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `next_monthly_charge_date` DATE NULL,
                `monthly_charge_amount` DECIMAL(10,2) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `subscription_plans` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `plan_name` VARCHAR(80) NOT NULL,
                `monthly_price` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `yearly_price` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `billing_duration_days` INT NOT NULL DEFAULT 30,
                `max_staff_limit` INT NOT NULL DEFAULT 0,
                `max_products_limit` INT NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `subscriptions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `plan_name` VARCHAR(30) NOT NULL,
                `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `billing_cycle` VARCHAR(20) NOT NULL DEFAULT 'monthly',
                `starts_at` DATETIME NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `payment_method` VARCHAR(30) NULL,
                `payment_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `store_settings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `setting_key` VARCHAR(100) NOT NULL,
                `setting_value` TEXT NULL,
                UNIQUE KEY `uniq_store_setting` (`store_id`, `setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `payment_transactions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `subscription_id` INT NULL,
                `plan_id` INT NULL,
                `method` VARCHAR(30) NOT NULL,
                `gateway_ref` VARCHAR(120) NULL,
                `receipt_screenshot` VARCHAR(255) NULL,
                `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `paid_at` DATETIME NULL,
                `next_expiry_date` DATETIME NULL,
                `notes` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `payment_methods` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `method_name` VARCHAR(80) NOT NULL,
                `method_type` VARCHAR(20) NOT NULL DEFAULT 'manual',
                `account_number` VARCHAR(120) NULL,
                `instructions` TEXT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `account_holder` VARCHAR(150) NULL,
                `method_logo` VARCHAR(50) NULL,
                `qr_code` VARCHAR(255) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `subscription_reminders` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `reminder_type` VARCHAR(40) NOT NULL,
                `reminder_for_date` DATE NOT NULL,
                `channel` VARCHAR(20) NOT NULL DEFAULT 'whatsapp',
                `is_sent` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uniq_store_reminder` (`store_id`, `reminder_type`, `reminder_for_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `superadmin_notifications` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `type` VARCHAR(50) NOT NULL DEFAULT 'plan_expiry',
                `title` VARCHAR(255) NOT NULL,
                `message` TEXT NULL,
                `due_at` DATETIME NOT NULL,
                `is_seen` TINYINT(1) NOT NULL DEFAULT 0,
                `seen_at` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uniq_sa_store_type_due` (`store_id`, `type`, `due_at`),
                INDEX `idx_sa_seen_due` (`is_seen`, `due_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `receipt_templates` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `template_key` VARCHAR(80) NOT NULL UNIQUE,
                `template_name` VARCHAR(120) NOT NULL,
                `template_type` VARCHAR(30) NOT NULL DEFAULT 'document',
                `paper_width_mm` INT NOT NULL DEFAULT 80,
                `accent_color` VARCHAR(20) NOT NULL DEFAULT '#0f6dff',
                `font_family` VARCHAR(80) NOT NULL DEFAULT 'Inter',
                `base_template` VARCHAR(80) NOT NULL DEFAULT 'invoice',
                `html_block` LONGTEXT NULL,
                `is_system` TINYINT(1) NOT NULL DEFAULT 0,
                `layout_notes` TEXT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `whatsapp_messages` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `user_id` INT NULL,
                `customer_id` INT NULL,
                `invoice_id` INT NULL,
                `recipient_phone` VARCHAR(50) NOT NULL,
                `message_type` VARCHAR(40) NOT NULL DEFAULT 'custom',
                `message_body` TEXT NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `provider` VARCHAR(20) NOT NULL DEFAULT 'meta',
                `provider_response` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_whatsapp_store_created` (`store_id`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `customers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `name` VARCHAR(255) NOT NULL,
                `phone` VARCHAR(50) NULL,
                `address` VARCHAR(255) NULL,
                `loyalty_points` INT NOT NULL DEFAULT 0,
                `total_spending` DECIMAL(12, 2) DEFAULT 0.00,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_customers_store_phone` (`store_id`, `phone`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `products` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `parent_product_id` INT NULL,
                `variant_label` VARCHAR(120) NULL,
                `store_id` INT NULL,
                `name` VARCHAR(255) NOT NULL,
                `model_number` VARCHAR(100) NULL,
                `barcode` VARCHAR(80) NULL,
                `unit_value` DECIMAL(10,3) NULL,
                `unit_type` VARCHAR(20) NULL,
                `image_path` VARCHAR(255) NULL,
                `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `wholesale_price` DECIMAL(12, 2) NULL,
                `cost` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `stock` DECIMAL(12, 3) NOT NULL DEFAULT 0.000,
                `min_stock` DECIMAL(12, 3) NOT NULL DEFAULT 5.000,
                `reserved_stock` DECIMAL(12, 3) NOT NULL DEFAULT 0.000,
                `batch_number` VARCHAR(80) NULL,
                `expiry_date` DATE NULL,
                `is_custom` TINYINT(1) NOT NULL DEFAULT 0,
                `is_visible_online` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `idx_products_store_barcode` (`store_id`, `barcode`),
                INDEX `idx_products_store_name` (`store_id`, `name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `product_scan_codes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `product_id` INT NOT NULL,
                `code` VARCHAR(120) NOT NULL,
                `scan_type` VARCHAR(20) NOT NULL DEFAULT 'single',
                `quantity` INT NOT NULL DEFAULT 1,
                `label` VARCHAR(120) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `idx_product_scan_codes_store_code` (`store_id`, `code`),
                INDEX `idx_product_scan_codes_product` (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `invoices` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `custom_id` VARCHAR(50) NULL UNIQUE,
                `user_id` INT NULL,
                `customer_id` INT NULL,
                `subtotal` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `discount` DECIMAL(12, 2) DEFAULT 0.00,
                `tax_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `tax_rate` DECIMAL(6, 3) NOT NULL DEFAULT 0.000,
                `tax_label` VARCHAR(80) NULL,
                `fbr_pos_id` VARCHAR(80) NULL,
                `total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `paid_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `balance_due` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `is_credit_settled` TINYINT(1) NOT NULL DEFAULT 0,
                `status` ENUM('paid', 'draft') DEFAULT 'paid',
                `transaction_type` VARCHAR(20) NOT NULL DEFAULT 'Sale',
                `notes` TEXT NULL,
                `customer_name_snapshot` VARCHAR(255) NULL,
                `customer_phone_snapshot` VARCHAR(50) NULL,
                `customer_address_snapshot` VARCHAR(255) NULL,
                `payment_method` VARCHAR(20) NOT NULL DEFAULT 'cash',
                `sale_channel` VARCHAR(20) NOT NULL DEFAULT 'retail',
                `order_source` VARCHAR(50) NOT NULL DEFAULT 'pos',
                `online_order_status` VARCHAR(50) NULL,
                `credit_due_date` DATETIME NULL,
                `credit_private_note` TEXT NULL,
                `last_credit_reminder_at` DATETIME NULL,
                `points_earned` INT NOT NULL DEFAULT 0,
                `points_redeemed` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_invoices_store_created` (`store_id`, `created_at`),
                INDEX `idx_invoices_customer_id` (`customer_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `invoice_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `invoice_id` INT NOT NULL,
                `product_id` INT NULL,
                `product_name` VARCHAR(255) NOT NULL,
                `model_number` VARCHAR(100) NULL,
                `price_tier` VARCHAR(20) NOT NULL DEFAULT 'retail',
                `quantity` DECIMAL(12, 3) NOT NULL DEFAULT 1.000,
                `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `cost_price` DECIMAL(12, 2) NULL DEFAULT 0.00,
                `discount` DECIMAL(12, 2) DEFAULT 0.00,
                `total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                INDEX `idx_invoice_items_invoice_product` (`invoice_id`, `product_id`),
                INDEX `idx_invoice_items_store` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `activity_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `user_id` INT NULL,
                `action` VARCHAR(255) NOT NULL,
                `details` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_activity_logs_store` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `updates_log` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `version` VARCHAR(50) NOT NULL,
                `description` TEXT NULL,
                `source_file` VARCHAR(255) NULL,
                `validation_method` VARCHAR(100) NULL,
                `backup_sql_path` VARCHAR(255) NULL,
                `backup_zip_path` VARCHAR(255) NULL,
                `rollback_available` TINYINT(1) NOT NULL DEFAULT 0,
                `rolled_back_at` TIMESTAMP NULL DEFAULT NULL,
                `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `expense_categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `name` VARCHAR(120) NOT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_expense_categories_store_name` (`store_id`, `name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `expenses` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `category_id` INT NULL,
                `category_name_snapshot` VARCHAR(120) NULL,
                `custom_category` VARCHAR(120) NULL,
                `title` VARCHAR(255) NOT NULL,
                `amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `payment_method` VARCHAR(20) NOT NULL DEFAULT 'cash',
                `notes` TEXT NULL,
                `expense_date` DATE NOT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_expenses_store_date` (`store_id`, `expense_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `credit_payments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `invoice_id` INT NOT NULL,
                `amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `payment_method` VARCHAR(20) NOT NULL DEFAULT 'cash',
                `note` TEXT NULL,
                `next_due_date` DATETIME NULL,
                `private_message` TEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_credit_payments_invoice` (`invoice_id`),
                INDEX `idx_credit_payments_store` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `credit_notifications` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `invoice_id` INT NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `message` TEXT NULL,
                `due_at` DATETIME NOT NULL,
                `is_seen` TINYINT(1) NOT NULL DEFAULT 0,
                `seen_at` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uniq_invoice_due` (`invoice_id`, `due_at`),
                INDEX `idx_seen_due` (`is_seen`, `due_at`),
                INDEX `idx_credit_notifications_store` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `suppliers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `name` VARCHAR(180) NOT NULL,
                `company_name` VARCHAR(180) NULL,
                `phone` VARCHAR(50) NULL,
                `email` VARCHAR(120) NULL,
                `address` VARCHAR(255) NULL,
                `ntn` VARCHAR(50) NULL,
                `opening_balance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `current_balance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `notes` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `idx_suppliers_store_name` (`store_id`, `name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `supplier_payments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `supplier_id` INT NOT NULL,
                `purchase_id` INT NULL,
                `amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `payment_method` VARCHAR(30) NOT NULL DEFAULT 'cash',
                `payment_date` DATE NOT NULL,
                `reference_number` VARCHAR(100) NULL,
                `notes` TEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_supplier_payments_store_sup` (`store_id`, `supplier_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `inventory_purchases` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `supplier_id` INT NULL,
                `product_id` INT NULL,
                `supplier_name` VARCHAR(180) NOT NULL,
                `item_name` VARCHAR(180) NOT NULL,
                `items_count` INT NOT NULL DEFAULT 1,
                `quantity` DECIMAL(12, 3) NOT NULL DEFAULT 1.000,
                `total_quantity` DECIMAL(12, 3) NOT NULL DEFAULT 0.000,
                `unit_type` VARCHAR(20) NOT NULL DEFAULT 'pcs',
                `unit_cost` DECIMAL(12, 2) NULL,
                `old_cost` DECIMAL(12, 2) NULL,
                `cost_method` VARCHAR(30) NOT NULL DEFAULT 'weighted_average',
                `new_sale_price` DECIMAL(12, 2) NULL,
                `batch_number` VARCHAR(80) NULL,
                `expiry_date` DATE NULL,
                `purchase_date` DATE NOT NULL,
                `bill_total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `paid_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `balance_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `bill_reference` VARCHAR(120) NULL,
                `notes` TEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `idx_inventory_purchases_store_date` (`store_id`, `purchase_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `inventory_purchase_payments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `purchase_id` INT NOT NULL,
                `payment_date` DATE NOT NULL,
                `amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `payment_method` VARCHAR(30) NOT NULL DEFAULT 'cash',
                `notes` TEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_purchase_payments_purchase_date` (`purchase_id`, `payment_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `inventory_purchase_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `purchase_id` INT NOT NULL,
                `product_id` INT NULL,
                `item_name` VARCHAR(255) NOT NULL,
                `unit_type` VARCHAR(30) NOT NULL DEFAULT 'pcs',
                `quantity` DECIMAL(12, 3) NOT NULL DEFAULT 1.000,
                `unit_cost` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `old_cost` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `cost_method` VARCHAR(30) NOT NULL DEFAULT 'weighted_average',
                `new_sale_price` DECIMAL(12, 2) NULL,
                `subtotal` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `batch_number` VARCHAR(80) NULL,
                `expiry_date` DATE NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_purchase_items_pid` (`purchase_id`),
                INDEX `idx_purchase_items_prod` (`product_id`),
                INDEX `idx_purchase_items_store` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `product_batches` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `product_id` INT NOT NULL,
                `purchase_id` INT NULL,
                `batch_number` VARCHAR(80) NOT NULL,
                `cost_price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `sale_price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `initial_quantity` DECIMAL(12, 3) NOT NULL DEFAULT 0.000,
                `current_stock` DECIMAL(12, 3) NOT NULL DEFAULT 0.000,
                `expiry_date` DATE NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_prod_batches_store_prod` (`store_id`, `product_id`),
                INDEX `idx_prod_batches_exp` (`store_id`, `expiry_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `stock_transfers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `transfer_number` VARCHAR(50) NOT NULL,
                `source_store_id` INT NOT NULL,
                `destination_store_id` INT NOT NULL,
                `status` ENUM('in_transit', 'received', 'rejected', 'cancelled') NOT NULL DEFAULT 'in_transit',
                `total_items` INT NOT NULL DEFAULT 0,
                `total_qty` DECIMAL(12, 3) NOT NULL DEFAULT 0.000,
                `notes` TEXT NULL,
                `created_by` INT NULL,
                `received_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `received_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `idx_transfers_src` (`source_store_id`),
                INDEX `idx_transfers_dst` (`destination_store_id`),
                INDEX `idx_transfers_num` (`transfer_number`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `stock_transfer_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `transfer_id` INT NOT NULL,
                `product_id` INT NULL,
                `product_name` VARCHAR(255) NOT NULL,
                `barcode` VARCHAR(80) NULL,
                `model_number` VARCHAR(100) NULL,
                `unit_type` VARCHAR(20) NOT NULL DEFAULT 'pcs',
                `quantity` DECIMAL(12, 3) NOT NULL DEFAULT 1.000,
                `received_quantity` DECIMAL(12, 3) NOT NULL DEFAULT 0.000,
                `cost_price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                INDEX `idx_transfer_items_tid` (`transfer_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `user_biometrics` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `store_id` INT NULL,
                `credential_id` VARCHAR(255) NOT NULL,
                `public_key` TEXT NOT NULL,
                `device_name` VARCHAR(120) NOT NULL DEFAULT 'Mobile / Biometric Device',
                `sign_count` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `last_used_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `idx_biometrics_uid` (`user_id`),
                INDEX `idx_biometrics_cred` (`credential_id`(191))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `mobile_scanner_events` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `token` VARCHAR(80) NOT NULL,
                `barcode` VARCHAR(120) NOT NULL,
                `scan_context` VARCHAR(30) NOT NULL DEFAULT 'checkout',
                `device_name` VARCHAR(120) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_mobile_scanner_token_id` (`token`, `id`),
                INDEX `idx_mobile_scanner_store_created` (`store_id`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `quotations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `quotation_number` VARCHAR(50) NOT NULL,
                `customer_id` INT NULL,
                `customer_name` VARCHAR(180) NOT NULL,
                `customer_phone` VARCHAR(50) NULL,
                `customer_address` VARCHAR(255) NULL,
                `subtotal` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `discount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `tax_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `tax_rate` DECIMAL(6, 3) NOT NULL DEFAULT 0.000,
                `total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `status` ENUM('pending', 'converted', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
                `valid_until` DATE NULL,
                `converted_invoice_id` INT NULL,
                `notes` TEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `idx_quotations_store_num` (`store_id`, `quotation_number`),
                INDEX `idx_quotations_status` (`store_id`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `quotation_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `quotation_id` INT NOT NULL,
                `product_id` INT NULL,
                `product_name` VARCHAR(255) NOT NULL,
                `model_number` VARCHAR(100) NULL,
                `quantity` DECIMAL(12, 3) NOT NULL DEFAULT 1.000,
                `unit_type` VARCHAR(20) NOT NULL DEFAULT 'pcs',
                `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `discount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                INDEX `idx_quotation_items_qid` (`quotation_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `financial_accounts` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `account_name` VARCHAR(150) NOT NULL,
                `account_type` ENUM('cash', 'bank', 'wallet', 'other') NOT NULL DEFAULT 'cash',
                `account_number` VARCHAR(100) NULL,
                `bank_name` VARCHAR(120) NULL,
                `opening_balance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `current_balance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `is_default` TINYINT(1) NOT NULL DEFAULT 0,
                `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                `notes` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `idx_fin_acc_store` (`store_id`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `cashflow_transactions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `user_id` INT NULL,
                `transaction_type` VARCHAR(40) NOT NULL,
                `from_account_id` INT NULL,
                `to_account_id` INT NULL,
                `amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `fee_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                `net_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `party_type` ENUM('customer', 'supplier', 'staff', 'owner', 'other') NOT NULL DEFAULT 'other',
                `party_id` INT NULL,
                `party_name` VARCHAR(180) NULL,
                `party_phone` VARCHAR(50) NULL,
                `reference_no` VARCHAR(120) NULL,
                `description` VARCHAR(255) NULL,
                `notes` TEXT NULL,
                `transaction_date` DATETIME NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_cftx_store_date` (`store_id`, `transaction_date`),
                INDEX `idx_cftx_type` (`store_id`, `transaction_type`),
                INDEX `idx_cftx_from_acc` (`from_account_id`),
                INDEX `idx_cftx_to_acc` (`to_account_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];

        foreach ($tables as $sql) {
            try {
                $pdo->exec($sql);
            } catch (\Throwable $e) {
                error_log('SchemaManager table creation notice: ' . $e->getMessage());
            }
        }
    }

    /**
     * Add missing columns to existing tables dynamically
     */
    private static function ensureAllColumns(PDO $pdo) {
        $columns = [
            ['users', 'username', "VARCHAR(100) NULL"],
            ['users', 'permissions', "TEXT NULL"],
            ['users', 'two_factor_enabled', "TINYINT(1) NOT NULL DEFAULT 0"],
            ['users', 'two_factor_secret', "VARCHAR(100) NULL"],
            ['users', 'updated_at', "TIMESTAMP NULL DEFAULT NULL"],
            ['users', 'store_id', "INT NULL"],
            ['users', 'status', "ENUM('active','inactive') NOT NULL DEFAULT 'active'"],

            ['stores', 'current_plan_id', "INT NULL"],
            ['stores', 'parent_store_id', "INT NULL"],
            ['stores', 'branch_code', "VARCHAR(40) NULL"],
            ['stores', 'scanner_pair_code', "VARCHAR(6) NULL"],
            ['stores', 'subscription_started_at', "DATETIME NULL"],
            ['stores', 'payment_status', "VARCHAR(20) NOT NULL DEFAULT 'pending'"],
            ['stores', 'next_monthly_charge_date', "DATE NULL"],
            ['stores', 'monthly_charge_amount', "DECIMAL(10,2) NULL"],

            ['products', 'parent_product_id', "INT NULL AFTER `id`"],
            ['products', 'variant_label', "VARCHAR(120) NULL AFTER `parent_product_id`"],
            ['products', 'store_id', "INT NULL"],
            ['products', 'model_number', "VARCHAR(100) NULL"],
            ['products', 'barcode', "VARCHAR(80) NULL"],
            ['products', 'unit_value', "DECIMAL(10,3) NULL"],
            ['products', 'unit_type', "VARCHAR(20) NULL"],
            ['products', 'image_path', "VARCHAR(255) NULL"],
            ['products', 'wholesale_price', "DECIMAL(12,2) NULL"],
            ['products', 'batch_number', "VARCHAR(80) NULL"],
            ['products', 'expiry_date', "DATE NULL"],
            ['products', 'is_visible_online', "TINYINT(1) NOT NULL DEFAULT 1"],
            ['products', 'is_custom', "TINYINT(1) NOT NULL DEFAULT 0"],
            ['products', 'updated_at', "TIMESTAMP NULL DEFAULT NULL"],

            ['customers', 'address', "VARCHAR(255) NULL"],
            ['customers', 'store_id', "INT NULL"],
            ['customers', 'loyalty_points', "INT NOT NULL DEFAULT 0"],

            ['invoices', 'store_id', "INT NULL"],
            ['invoices', 'transaction_type', "VARCHAR(20) NOT NULL DEFAULT 'Sale'"],
            ['invoices', 'notes', "TEXT NULL"],
            ['invoices', 'customer_name_snapshot', "VARCHAR(255) NULL"],
            ['invoices', 'customer_phone_snapshot', "VARCHAR(50) NULL"],
            ['invoices', 'customer_address_snapshot', "VARCHAR(255) NULL"],
            ['invoices', 'payment_method', "VARCHAR(20) NOT NULL DEFAULT 'cash'"],
            ['invoices', 'sale_channel', "VARCHAR(20) NOT NULL DEFAULT 'retail'"],
            ['invoices', 'order_source', "VARCHAR(50) NOT NULL DEFAULT 'pos'"],
            ['invoices', 'online_order_status', "VARCHAR(50) NULL"],
            ['invoices', 'credit_due_date', "DATETIME NULL"],
            ['invoices', 'credit_private_note', "TEXT NULL"],
            ['invoices', 'paid_amount', "DECIMAL(12,2) NOT NULL DEFAULT 0.00"],
            ['balance_due', 'balance_due', "DECIMAL(12,2) NOT NULL DEFAULT 0.00"],
            ['invoices', 'balance_due', "DECIMAL(12,2) NOT NULL DEFAULT 0.00"],
            ['invoices', 'is_credit_settled', "TINYINT(1) NOT NULL DEFAULT 0"],
            ['invoices', 'last_credit_reminder_at', "DATETIME NULL"],
            ['invoices', 'tax_amount', "DECIMAL(12,2) NOT NULL DEFAULT 0.00"],
            ['invoices', 'tax_rate', "DECIMAL(6,3) NOT NULL DEFAULT 0.000"],
            ['invoices', 'tax_label', "VARCHAR(80) NULL"],
            ['invoices', 'fbr_pos_id', "VARCHAR(80) NULL"],
            ['invoices', 'points_earned', "INT NOT NULL DEFAULT 0"],
            ['invoices', 'points_redeemed', "INT NOT NULL DEFAULT 0"],

            ['invoice_items', 'store_id', "INT NULL"],
            ['invoice_items', 'model_number', "VARCHAR(100) NULL"],
            ['invoice_items', 'price_tier', "VARCHAR(20) NOT NULL DEFAULT 'retail'"],
            ['invoice_items', 'cost_price', "DECIMAL(12,2) NULL"],

            ['payment_transactions', 'plan_id', "INT NULL"],
            ['payment_transactions', 'receipt_screenshot', "VARCHAR(255) NULL"],
            ['payment_transactions', 'paid_at', "DATETIME NULL"],
            ['payment_transactions', 'next_expiry_date', "DATETIME NULL"],

            ['payment_methods', 'account_holder', "VARCHAR(150) NULL"],
            ['payment_methods', 'method_logo', "VARCHAR(50) NULL"],
            ['payment_methods', 'qr_code', "VARCHAR(255) NULL"],

            ['receipt_templates', 'base_template', "VARCHAR(80) NOT NULL DEFAULT 'invoice'"],
            ['receipt_templates', 'html_block', "LONGTEXT NULL"],
            ['receipt_templates', 'is_system', "TINYINT(1) NOT NULL DEFAULT 0"],

            ['superadmin_notifications', 'store_id', "INT NULL"],
            ['superadmin_notifications', 'is_seen', "TINYINT(1) NOT NULL DEFAULT 0"],

            ['inventory_purchases', 'store_id', "INT NULL"],
            ['inventory_purchases', 'supplier_id', "INT NULL AFTER `store_id`"],
            ['inventory_purchases', 'product_id', "INT NULL AFTER `supplier_id`"],
            ['inventory_purchases', 'items_count', "INT NOT NULL DEFAULT 1 AFTER `item_name`"],
            ['inventory_purchases', 'total_quantity', "DECIMAL(12,3) NOT NULL DEFAULT 0.000 AFTER `items_count`"],
            ['inventory_purchases', 'unit_cost', "DECIMAL(12,2) NULL AFTER `quantity`"],
            ['inventory_purchases', 'old_cost', "DECIMAL(12,2) NULL AFTER `unit_cost`"],
            ['inventory_purchases', 'cost_method', "VARCHAR(30) NOT NULL DEFAULT 'weighted_average' AFTER `old_cost`"],
            ['inventory_purchases', 'new_sale_price', "DECIMAL(12,2) NULL AFTER `cost_method`"],
            ['inventory_purchases', 'batch_number', "VARCHAR(80) NULL AFTER `new_sale_price`"],
            ['inventory_purchases', 'expiry_date', "DATE NULL AFTER `batch_number`"],

            ['expenses', 'store_id', "INT NULL"],
            ['expense_categories', 'store_id', "INT NULL"],
            ['credit_payments', 'store_id', "INT NULL"],
            ['credit_notifications', 'store_id', "INT NULL"],
            ['activity_logs', 'store_id', "INT NULL"],
            ['mobile_scanner_events', 'scan_context', "VARCHAR(30) NOT NULL DEFAULT 'checkout' AFTER `barcode`"]
        ];

        foreach ($columns as $item) {
            self::addColumnIfMissing($pdo, $item[0], $item[1], $item[2]);
        }
    }

    /**
     * Add column if not already present
     */
    public static function addColumnIfMissing(PDO $pdo, $table, $column, $definition) {
        $tableClean = str_replace('`', '', $table);
        $columnClean = str_replace(['`', '\\'], '', $column);

        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableClean` LIKE ?");
            $stmt->execute([$columnClean]);
            $exists = $stmt->fetch();
            if (!$exists) {
                $pdo->exec("ALTER TABLE `$tableClean` ADD COLUMN `$columnClean` $definition");
            }
        } catch (\Throwable $e) {
            // Ignore if table does not exist yet or duplicate
        }
    }

    /**
     * Ensure decimal precision for stock and quantity fields
     */
    private static function ensureDecimalDataTypes(PDO $pdo) {
        $modifications = [
            "ALTER TABLE `products` MODIFY `stock` DECIMAL(12,3) NOT NULL DEFAULT 0.000, MODIFY `min_stock` DECIMAL(12,3) NOT NULL DEFAULT 5.000, MODIFY `reserved_stock` DECIMAL(12,3) NOT NULL DEFAULT 0.000",
            "ALTER TABLE `invoice_items` MODIFY `quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000",
            "ALTER TABLE `product_batches` MODIFY `current_stock` DECIMAL(12,3) NOT NULL DEFAULT 0.000, MODIFY `initial_quantity` DECIMAL(12,3) NOT NULL DEFAULT 0.000",
            "ALTER TABLE `inventory_purchases` MODIFY `quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000",
            "ALTER TABLE `stock_transfer_items` MODIFY `quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000",
            "ALTER TABLE `customers` MODIFY `phone` VARCHAR(50) NULL"
        ];

        foreach ($modifications as $sql) {
            try {
                $pdo->exec($sql);
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Ensure necessary database indexes for fast POS queries
     */
    private static function ensureAllIndexes(PDO $pdo) {
        $indexes = [
            ['invoices', 'idx_invoice_created_status_type', ['created_at', 'status', 'transaction_type']],
            ['invoices', 'idx_invoice_payment_due_settled', ['payment_method', 'credit_due_date', 'is_credit_settled']],
            ['invoices', 'idx_invoice_customer_created', ['customer_id', 'created_at']],
            ['invoices', 'idx_invoices_store_created', ['store_id', 'created_at']],
            ['invoice_items', 'idx_invoice_items_invoice_product', ['invoice_id', 'product_id']],
            ['invoice_items', 'idx_invoice_items_store', ['store_id']],
            ['products', 'idx_products_name_model', ['name', 'model_number']],
            ['products', 'idx_products_store_barcode', ['store_id', 'barcode']],
            ['products', 'idx_products_store', ['store_id']],
            ['product_scan_codes', 'idx_product_scan_codes_store_code', ['store_id', 'code']],
            ['customers', 'idx_customers_store_phone', ['store_id', 'phone']],
            ['users', 'idx_users_store_role', ['store_id', 'role']],
            ['expenses', 'idx_expenses_store_date', ['store_id', 'expense_date']],
            ['stores', 'idx_stores_expiry_status', ['subscription_expires_at', 'status']],
            ['financial_accounts', 'idx_fin_acc_store', ['store_id', 'status']],
            ['cashflow_transactions', 'idx_cftx_store_date', ['store_id', 'transaction_date']],
            ['cashflow_transactions', 'idx_cftx_type', ['store_id', 'transaction_type']]
        ];

        foreach ($indexes as $item) {
            self::ensureIndexIfMissing($pdo, $item[0], $item[1], $item[2]);
        }
    }

    private static function ensureIndexIfMissing(PDO $pdo, $table, $indexName, array $columns) {
        $tableClean = str_replace('`', '', $table);
        $indexClean = str_replace('`', '', $indexName);

        try {
            $stmt = $pdo->prepare("SHOW INDEX FROM `$tableClean` WHERE Key_name = ?");
            $stmt->execute([$indexClean]);
            if ($stmt->fetch()) {
                return;
            }

            $colsList = implode(', ', array_map(function($c) {
                return '`' . str_replace('`', '', $c) . '`';
            }, $columns));

            $pdo->exec("ALTER TABLE `$tableClean` ADD INDEX `$indexClean` ($colsList)");
        } catch (\Throwable $e) {}
    }

    /**
     * Seed System Default Settings, Plans & Templates
     */
    private static function seedSystemDefaults(PDO $pdo) {
        $settings = [
            'company_name' => 'DevAltum POS',
            'company_address' => '',
            'company_tagline' => 'Official point of sale system',
            'currency_symbol' => 'Rs',
            'currency_code' => 'PKR',
            'receipt_footer' => 'Thank you for choosing us. For support, please contact our store team.',
            'receipt_template' => 'classic',
            'whatsapp_number' => '+923101648600',
            'whatsapp_message' => 'Thank you for shopping with us. Please contact our store for support or order assistance.',
            'credit_whatsapp_template' => "Assalam o Alaikum {customer_name},\n\nBhai apki remaining amount {remaining} reh gayi hai. Barah-e-karam apni payment clear kar dein.\n\nThank you,\n{company_name}",
            'project_slug' => 'devaltum-pos',
            'website_logo' => '',
            'receipt_logo' => '',
            'show_product_images' => '1',
            'printer_default_mode' => 'document',
            'printer_paper_size' => 'auto',
            'printer_margin_mm' => '4',
            'scanner_beep_enabled' => '1',
            'mobile_scanner_enabled' => '0',
            'hardware_scanner_enabled' => '1',
            'bluetooth_scanner_enabled' => '1',
            'printer_status_tracking' => '1',
            'scanner_poll_interval_ms' => '900',
            'whatsapp_provider' => 'none',
            'tax_enabled' => '0',
            'tax_rate' => '18',
            'tax_label' => 'Sales Tax',
            'system_installed' => '1'
        ];

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = IF(setting_value IS NULL OR setting_value = '', VALUES(setting_value), setting_value)");
        foreach ($settings as $key => $value) {
            try {
                $stmt->execute([$key, $value]);
            } catch (\Throwable $e) {}
        }

        // Seed default subscription plans
        try {
            $planCount = (int) $pdo->query("SELECT COUNT(*) FROM subscription_plans")->fetchColumn();
            if ($planCount === 0) {
                $plans = [
                    ['Free Plan', 0, 0, 30, 2, 200, 'active'],
                    ['Basic Plan', 29, 290, 30, 5, 1000, 'active'],
                    ['Pro Enterprise', 79, 790, 30, 25, 10000, 'active']
                ];
                $planStmt = $pdo->prepare("INSERT INTO subscription_plans (plan_name, monthly_price, yearly_price, billing_duration_days, max_staff_limit, max_products_limit, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($plans as $p) {
                    $planStmt->execute($p);
                }
            }
        } catch (\Throwable $e) {}

        // Seed default expense categories
        try {
            $categories = ['Rent', 'Salary', 'Utilities', 'Internet', 'Transport', 'Maintenance', 'Packaging', 'Marketing'];
            $catStmt = $pdo->prepare("INSERT IGNORE INTO expense_categories (name, created_by) VALUES (?, NULL)");
            foreach ($categories as $cat) {
                $catStmt->execute([$cat]);
            }
        } catch (\Throwable $e) {}

        // Seed default financial accounts for stores if missing
        try {
            $stores = $pdo->query("SELECT id FROM stores")->fetchAll(PDO::FETCH_COLUMN);
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM financial_accounts WHERE store_id = ?");
            $insertAcc = $pdo->prepare("INSERT INTO financial_accounts (store_id, account_name, account_type, account_number, bank_name, opening_balance, current_balance, is_default, status) VALUES (?, ?, ?, ?, ?, 0.00, 0.00, ?, 'active')");
            foreach ($stores as $sId) {
                $checkStmt->execute([$sId]);
                if ((int)$checkStmt->fetchColumn() === 0) {
                    $insertAcc->execute([$sId, 'Cash in Hand (Counter Drawer)', 'cash', 'DRAWER-01', 'Cash Till', 1]);
                    $insertAcc->execute([$sId, 'Main Online Account', 'wallet', 'WALLET-01', 'JazzCash / EasyPaisa / Bank', 0]);
                }
            }
        } catch (\Throwable $e) {}

        // Sync Receipt Templates if class exists
        if (class_exists('ReceiptTemplates')) {
            try {
                ReceiptTemplates::syncDefaults($pdo);
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Clean up legacy roles, unlinked store records, and calculate credit balances
     */
    private static function normalizeAndMigrateData(PDO $pdo) {
        try {
            $pdo->exec("UPDATE products SET wholesale_price = price WHERE wholesale_price IS NULL");
            $pdo->exec("UPDATE invoices SET payment_method = 'cash' WHERE payment_method IS NULL OR payment_method = ''");
            $pdo->exec("UPDATE invoices SET sale_channel = 'retail' WHERE sale_channel IS NULL OR sale_channel = ''");
            $pdo->exec("UPDATE users SET role = 'store_owner' WHERE role = 'admin'");
            $pdo->exec("UPDATE users SET permissions = 'dashboard,pos,sales,inventory,customers,reports,expenses,credits,cashflow,settings' WHERE role = 'staff' AND (permissions IS NULL OR permissions = '')");
            
            // Ensure first store gets unlinked records
            $defaultStoreId = (int) $pdo->query("SELECT id FROM stores ORDER BY id ASC LIMIT 1")->fetchColumn();
            if ($defaultStoreId > 0) {
                $pdo->exec("UPDATE users SET store_id = $defaultStoreId WHERE role IN ('store_owner', 'staff') AND (store_id IS NULL OR store_id = 0)");
                $pdo->exec("UPDATE products SET store_id = $defaultStoreId WHERE store_id IS NULL OR store_id = 0");
                $pdo->exec("UPDATE customers SET store_id = $defaultStoreId WHERE store_id IS NULL OR store_id = 0");
                $pdo->exec("UPDATE invoices SET store_id = $defaultStoreId WHERE store_id IS NULL OR store_id = 0");
            }
        } catch (\Throwable $e) {}
    }

    private static function executeStatement(PDO $pdo, $sql, array $params = []) {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
