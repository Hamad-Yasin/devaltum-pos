<?php

class App {
    private static $booted = false;
    private static $settings = null;
    private static $storeSettingsCache = null;
    private static $storeSettingsStoreId = null;

    public static function isInstalled() {
        $storageLock = defined('STORAGE_PATH') ? STORAGE_PATH . '/installed.lock' : dirname(__DIR__, 2) . '/storage/installed.lock';
        $configLock = defined('CONFIG_PATH') ? CONFIG_PATH . '/installed.lock' : dirname(__DIR__, 2) . '/config/installed.lock';
        $rootLock = defined('ROOT_PATH') ? ROOT_PATH . '/installed.lock' : dirname(__DIR__, 2) . '/installed.lock';
        $installerLock = defined('ROOT_PATH') ? ROOT_PATH . '/installer.lock' : dirname(__DIR__, 2) . '/installer.lock';
        $envFile = defined('ROOT_PATH') ? ROOT_PATH . '/.env' : dirname(__DIR__, 2) . '/.env';

        $hasLock = file_exists($storageLock) || file_exists($configLock) || file_exists($rootLock) || file_exists($installerLock);
        $hasEnv = file_exists($envFile);

        // Strict Installation Condition:
        // System is considered installed ONLY when installed.lock AND .env both exist.
        // If either is missing, strictly return false so the installer launches automatically.
        if ($hasLock && $hasEnv) {
            return true;
        }

        return false;
    }

    public static function markAsInstalled() {
        $storageDir = defined('STORAGE_PATH') ? STORAGE_PATH : dirname(__DIR__, 2) . '/storage';
        $configDir = defined('CONFIG_PATH') ? CONFIG_PATH : dirname(__DIR__, 2) . '/config';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }
        if (!is_dir($configDir)) {
            @mkdir($configDir, 0755, true);
        }
        $timestamp = 'Installed on ' . date('Y-m-d H:i:s');
        @file_put_contents($storageDir . '/installed.lock', $timestamp);
        @file_put_contents($configDir . '/installed.lock', $timestamp);
    }

    public static function devAltumLogoUrl() {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        return $baseUrl . '/assets/img/devaltum-receipt-logo.png';
    }

    public static function devAltumReceiptLogoDataUri() {
        static $cached = null;
        if ($cached !== null) return $cached;
        $file = defined('ROOT_PATH') ? ROOT_PATH . '/public/assets/img/devaltum-receipt-logo.png' : dirname(__DIR__, 2) . '/public/assets/img/devaltum-receipt-logo.png';
        if (file_exists($file)) {
            $cached = 'data:image/png;base64,' . base64_encode((string) @file_get_contents($file));
            return $cached;
        }
        return self::devAltumLogoUrl();
    }

    public static function devAltumReceiptFooterHtml($logoHeight = null, $fontSize = null) {
        if ($logoHeight === null) {
            $logoHeight = (int) self::setting('devaltum_credit_logo_size', 44);
        }
        if ($fontSize === null) {
            $fontSize = (float) self::setting('devaltum_credit_font_size', 12);
        }
        $logoHeight = max(20, min(120, (int) $logoHeight));
        $fontSize = max(8, min(24, (float) $fontSize));
        $phoneFontSize = round($fontSize * 1.12, 1);
        $logoMaxWidth = round($logoHeight * 2.2);

        $showLogo = self::setting('devaltum_credit_show_logo', '1') !== '0';
        $customLogo = trim((string) self::setting('devaltum_custom_logo', ''));
        $logoSrc = ($customLogo !== '') ? $customLogo : self::devAltumReceiptLogoDataUri();

        $brandPrefix = htmlspecialchars((string) self::setting('devaltum_brand_prefix', 'Software By'), ENT_QUOTES, 'UTF-8');
        $brandName = htmlspecialchars((string) self::setting('devaltum_brand_name', 'DevAltum'), ENT_QUOTES, 'UTF-8');
        $brandColor = htmlspecialchars((string) self::setting('devaltum_brand_color', '#0f6dff'), ENT_QUOTES, 'UTF-8');
        $phoneText = htmlspecialchars((string) self::setting('devaltum_phone_text', '+92 3101648600'), ENT_QUOTES, 'UTF-8');
        $fontFamily = htmlspecialchars((string) self::setting('devaltum_credit_font_family', 'Segoe UI'), ENT_QUOTES, 'UTF-8');
        $fontWeight = htmlspecialchars((string) self::setting('devaltum_credit_font_weight', '800'), ENT_QUOTES, 'UTF-8');
        $fontStyle = htmlspecialchars((string) self::setting('devaltum_credit_font_style', 'normal'), ENT_QUOTES, 'UTF-8');
        $alignment = strtolower(trim((string) self::setting('devaltum_credit_alignment', 'center')));
        $justify = ($alignment === 'left') ? 'flex-start' : (($alignment === 'right') ? 'flex-end' : 'center');

        $logoHtml = '';
        if ($showLogo && $logoSrc !== '') {
            $logoHtml = '<img src="' . htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8') . '" alt="' . $brandName . '" class="devaltum-receipt-logo" style="height:' . $logoHeight . 'px; max-height:' . ($logoHeight + 6) . 'px; width:auto; max-width:' . $logoMaxWidth . 'px; object-fit:contain; flex-shrink:0; display:inline-block; vertical-align:middle;" />';
        }

        $fontStack = "'" . str_replace("'", "", $fontFamily) . "', 'Segoe UI', Arial, -apple-system, BlinkMacSystemFont, Roboto, sans-serif";

        return '<div class="devaltum-receipt-branding" style="display:inline-flex; align-items:center; justify-content:' . $justify . '; gap:10px; margin:8px auto 4px; text-align:' . ($alignment === 'center' ? 'left' : $alignment) . '; vertical-align:middle; text-decoration:none;">'
            . $logoHtml
            . '<div class="devaltum-receipt-text" style="display:inline-flex; flex-direction:column; justify-content:center; line-height:1.25; font-family:' . $fontStack . ';">'
            . '<span class="devaltum-brand-line" style="font-size:' . $fontSize . 'px; font-weight:' . $fontWeight . '; font-style:' . $fontStyle . '; color:#0b1528; letter-spacing:0.4px; text-transform:uppercase; white-space:nowrap;">' . $brandPrefix . ' <strong style="font-weight:900; color:' . $brandColor . ';">' . $brandName . '</strong></span>'
            . ($phoneText !== '' ? '<span class="devaltum-phone-line" style="font-size:' . $phoneFontSize . 'px; font-weight:' . $fontWeight . '; font-style:' . $fontStyle . '; color:#0b1528; letter-spacing:0.6px; white-space:nowrap; margin-top:2px;">' . $phoneText . '</span>' : '')
            . '</div>'
            . '</div>';
    }

    public static function boot() {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
            return;
        }

        $db = Database::getInstance();
        if (!$db->getConnection()) {
            return;
        }

        // 1. Enterprise Schema Verification & Dynamic Auto-Sync
        if (class_exists('SchemaManager') || file_exists(CORE_PATH . '/database/SchemaManager.php')) {
            require_once CORE_PATH . '/database/SchemaManager.php';
            SchemaManager::autoSyncIfNeeded($db);
        } else {
            self::ensureSchema($db);
        }

        self::loadSettings($db);

        // 2. Daily Automation & Credit Sync - Run only once a day globally
        $today = date('Y-m-d');
        $lastDailyAutomation = self::$settings['last_daily_automation_date'] ?? '';
        if ($today !== $lastDailyAutomation) {
            self::syncDueCreditNotifications();
            Billing::runDailyAutomation();

            $db->query("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$today, 'last_daily_automation_date']);
            $exists = $db->fetch("SELECT setting_key FROM settings WHERE setting_key = ? LIMIT 1", ['last_daily_automation_date']);
            if (!$exists) {
                $db->query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)", ['last_daily_automation_date', $today]);
            }
            self::$settings['last_daily_automation_date'] = $today;
        }

        // 3. Daily backups
        self::runDailyModuleBackups($db);
    }

    public static function ensureSchema($db) {
        if (class_exists('SchemaManager') || file_exists(CORE_PATH . '/database/SchemaManager.php')) {
            require_once CORE_PATH . '/database/SchemaManager.php';
            return SchemaManager::sync($db);
        }

        $db->query("
            CREATE TABLE IF NOT EXISTS `settings` (
                `setting_key` VARCHAR(100) PRIMARY KEY,
                `setting_value` TEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `updates_log` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `stores` (
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
                `subscription_started_at` DATETIME NULL,
                `subscription_expires_at` DATETIME NULL,
                `payment_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `next_monthly_charge_date` DATE NULL,
                `monthly_charge_amount` DECIMAL(10,2) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `subscription_plans` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `subscriptions` (
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
                FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `store_settings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `setting_key` VARCHAR(100) NOT NULL,
                `setting_value` TEXT NULL,
                UNIQUE KEY `uniq_store_setting` (`store_id`, `setting_key`),
                FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `payment_transactions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `subscription_id` INT NULL,
                `plan_id` INT NULL,
                `method` VARCHAR(30) NOT NULL,
                `gateway_ref` VARCHAR(120) NULL,
                `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `paid_at` DATETIME NULL,
                `next_expiry_date` DATETIME NULL,
                `notes` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `payment_methods` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `subscription_reminders` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `reminder_type` VARCHAR(40) NOT NULL,
                `reminder_for_date` DATE NOT NULL,
                `channel` VARCHAR(20) NOT NULL DEFAULT 'whatsapp',
                `is_sent` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uniq_store_reminder` (`store_id`, `reminder_type`, `reminder_for_date`),
                FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `superadmin_notifications` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `receipt_templates` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `whatsapp_messages` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `expense_categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(120) NOT NULL UNIQUE,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `expenses` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `category_id` INT NULL,
                `category_name_snapshot` VARCHAR(120) NULL,
                `custom_category` VARCHAR(120) NULL,
                `title` VARCHAR(255) NOT NULL,
                `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `payment_method` VARCHAR(20) NOT NULL DEFAULT 'cash',
                `notes` TEXT NULL,
                `expense_date` DATE NOT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (`expense_date`),
                FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `credit_payments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `invoice_id` INT NOT NULL,
                `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `payment_method` VARCHAR(20) NOT NULL DEFAULT 'cash',
                `note` TEXT NULL,
                `next_due_date` DATETIME NULL,
                `private_message` TEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `credit_notifications` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `invoice_id` INT NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `message` TEXT NULL,
                `due_at` DATETIME NOT NULL,
                `is_seen` TINYINT(1) NOT NULL DEFAULT 0,
                `seen_at` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uniq_invoice_due` (`invoice_id`, `due_at`),
                INDEX `idx_seen_due` (`is_seen`, `due_at`),
                FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `inventory_purchases` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `supplier_name` VARCHAR(180) NOT NULL,
                `item_name` VARCHAR(180) NOT NULL,
                `quantity` DECIMAL(10,3) NOT NULL DEFAULT 0,
                `unit_type` VARCHAR(20) NOT NULL DEFAULT 'pcs',
                `purchase_date` DATE NOT NULL,
                `bill_total` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `balance_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `bill_reference` VARCHAR(120) NULL,
                `notes` TEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `idx_inventory_purchases_store_date` (`store_id`, `purchase_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `inventory_purchase_payments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `purchase_id` INT NOT NULL,
                `payment_date` DATE NOT NULL,
                `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `payment_method` VARCHAR(30) NOT NULL DEFAULT 'cash',
                `notes` TEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_purchase_payments_purchase_date` (`purchase_id`, `payment_date`),
                FOREIGN KEY (`purchase_id`) REFERENCES `inventory_purchases`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `inventory_purchase_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `purchase_id` INT NOT NULL,
                `product_id` INT NULL,
                `item_name` VARCHAR(255) NOT NULL,
                `unit_type` VARCHAR(30) NOT NULL DEFAULT 'pcs',
                `quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000,
                `unit_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `old_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `cost_method` VARCHAR(30) NOT NULL DEFAULT 'weighted_average',
                `new_sale_price` DECIMAL(12,2) NULL,
                `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `batch_number` VARCHAR(80) NULL,
                `expiry_date` DATE NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_purchase_items_pid` (`purchase_id`),
                INDEX `idx_purchase_items_prod` (`product_id`),
                INDEX `idx_purchase_items_store` (`store_id`),
                FOREIGN KEY (`purchase_id`) REFERENCES `inventory_purchases`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `mobile_scanner_events` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `token` VARCHAR(80) NOT NULL,
                `barcode` VARCHAR(120) NOT NULL,
                `scan_context` VARCHAR(30) NOT NULL DEFAULT 'checkout',
                `device_name` VARCHAR(120) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_mobile_scanner_token_id` (`token`, `id`),
                INDEX `idx_mobile_scanner_store_created` (`store_id`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `product_scan_codes` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `suppliers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `name` VARCHAR(180) NOT NULL,
                `company_name` VARCHAR(180) NULL,
                `phone` VARCHAR(50) NULL,
                `email` VARCHAR(120) NULL,
                `address` VARCHAR(255) NULL,
                `ntn` VARCHAR(50) NULL,
                `opening_balance` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `current_balance` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `notes` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `idx_suppliers_store_name` (`store_id`, `name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `supplier_payments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `supplier_id` INT NOT NULL,
                `purchase_id` INT NULL,
                `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `payment_method` VARCHAR(30) NOT NULL DEFAULT 'cash',
                `payment_date` DATE NOT NULL,
                `reference_number` VARCHAR(100) NULL,
                `notes` TEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_supplier_payments_store_sup` (`store_id`, `supplier_id`),
                FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `quotations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `quotation_number` VARCHAR(50) NOT NULL,
                `customer_id` INT NULL,
                `customer_name` VARCHAR(180) NOT NULL,
                `customer_phone` VARCHAR(50) NULL,
                `customer_address` VARCHAR(255) NULL,
                `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `discount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `tax_rate` DECIMAL(6,3) NOT NULL DEFAULT 0,
                `total` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `status` ENUM('pending', 'converted', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
                `valid_until` DATE NULL,
                `converted_invoice_id` INT NULL,
                `notes` TEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `idx_quotations_store_num` (`store_id`, `quotation_number`),
                INDEX `idx_quotations_status` (`store_id`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `quotation_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `quotation_id` INT NOT NULL,
                `product_id` INT NULL,
                `product_name` VARCHAR(255) NOT NULL,
                `model_number` VARCHAR(100) NULL,
                `quantity` DECIMAL(10,3) NOT NULL DEFAULT 1,
                `unit_type` VARCHAR(20) NOT NULL DEFAULT 'pcs',
                `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `discount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `total` DECIMAL(12,2) NOT NULL DEFAULT 0,
                INDEX `idx_quotation_items_qid` (`quotation_id`),
                FOREIGN KEY (`quotation_id`) REFERENCES `quotations`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `product_batches` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `product_id` INT NOT NULL,
                `purchase_id` INT NULL,
                `batch_number` VARCHAR(80) NOT NULL,
                `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `sale_price` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `initial_quantity` DECIMAL(10,3) NOT NULL DEFAULT 0,
                `current_stock` DECIMAL(10,3) NOT NULL DEFAULT 0,
                `expiry_date` DATE NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_prod_batches_store_prod` (`store_id`, `product_id`),
                INDEX `idx_prod_batches_exp` (`store_id`, `expiry_date`),
                FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `stock_transfers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `transfer_number` VARCHAR(50) NOT NULL,
                `source_store_id` INT NOT NULL,
                `destination_store_id` INT NOT NULL,
                `status` ENUM('in_transit', 'received', 'rejected', 'cancelled') NOT NULL DEFAULT 'in_transit',
                `total_items` INT NOT NULL DEFAULT 0,
                `total_qty` DECIMAL(10,3) NOT NULL DEFAULT 0,
                `notes` TEXT NULL,
                `created_by` INT NULL,
                `received_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `received_at` TIMESTAMP NULL DEFAULT NULL,
                INDEX `idx_transfers_src` (`source_store_id`),
                INDEX `idx_transfers_dst` (`destination_store_id`),
                INDEX `idx_transfers_num` (`transfer_number`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `stock_transfer_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `transfer_id` INT NOT NULL,
                `product_id` INT NULL,
                `product_name` VARCHAR(255) NOT NULL,
                `barcode` VARCHAR(80) NULL,
                `model_number` VARCHAR(100) NULL,
                `unit_type` VARCHAR(20) NOT NULL DEFAULT 'pcs',
                `quantity` DECIMAL(10,3) NOT NULL DEFAULT 1,
                `received_quantity` DECIMAL(10,3) NOT NULL DEFAULT 0,
                `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0,
                INDEX `idx_transfer_items_tid` (`transfer_id`),
                FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->query("
            CREATE TABLE IF NOT EXISTS `user_biometrics` (
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
                INDEX `idx_biometrics_cred` (`credential_id`(191)),
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        self::addColumnIfMissing($db, 'users', 'two_factor_enabled', "TINYINT(1) NOT NULL DEFAULT 0");
        self::addColumnIfMissing($db, 'users', 'two_factor_secret', "VARCHAR(100) NULL");

        self::addColumnIfMissing($db, 'inventory_purchases', 'supplier_id', "INT NULL AFTER `store_id`");
        self::addColumnIfMissing($db, 'inventory_purchases', 'product_id', "INT NULL AFTER `supplier_id`");
        self::addColumnIfMissing($db, 'inventory_purchases', 'unit_cost', "DECIMAL(12,2) NULL AFTER `quantity`");
        self::addColumnIfMissing($db, 'inventory_purchases', 'old_cost', "DECIMAL(12,2) NULL AFTER `unit_cost`");
        self::addColumnIfMissing($db, 'inventory_purchases', 'cost_method', "VARCHAR(30) NOT NULL DEFAULT 'weighted_average' AFTER `old_cost`");
        self::addColumnIfMissing($db, 'inventory_purchases', 'new_sale_price', "DECIMAL(12,2) NULL AFTER `cost_method`");
        self::addColumnIfMissing($db, 'inventory_purchases', 'batch_number', "VARCHAR(80) NULL AFTER `new_sale_price`");
        self::addColumnIfMissing($db, 'inventory_purchases', 'expiry_date', "DATE NULL AFTER `batch_number`");
        self::addColumnIfMissing($db, 'inventory_purchases', 'items_count', "INT NOT NULL DEFAULT 1 AFTER `item_name`");
        self::addColumnIfMissing($db, 'inventory_purchases', 'total_quantity', "DECIMAL(12,3) NOT NULL DEFAULT 0 AFTER `items_count`");
        self::addColumnIfMissing($db, 'invoice_items', 'cost_price', "DECIMAL(10,2) NULL AFTER `price`");
        self::addColumnIfMissing($db, 'products', 'batch_number', "VARCHAR(80) NULL AFTER `min_stock`");
        self::addColumnIfMissing($db, 'products', 'expiry_date', "DATE NULL AFTER `batch_number`");
        self::addColumnIfMissing($db, 'products', 'is_visible_online', "TINYINT(1) NOT NULL DEFAULT 1");
        self::addColumnIfMissing($db, 'products', 'is_custom', "TINYINT(1) NOT NULL DEFAULT 0");
        self::addColumnIfMissing($db, 'products', 'wholesale_price', "DECIMAL(10,2) NULL AFTER `price`");
        self::addColumnIfMissing($db, 'products', 'barcode', "VARCHAR(80) NULL AFTER `model_number`");
        self::addColumnIfMissing($db, 'products', 'unit_value', "DECIMAL(10,3) NULL AFTER `barcode`");
        self::addColumnIfMissing($db, 'products', 'unit_type', "VARCHAR(20) NULL AFTER `unit_value`");
        self::addColumnIfMissing($db, 'products', 'image_path', "VARCHAR(255) NULL AFTER `unit_type`");
        self::addColumnIfMissing($db, 'products', 'parent_product_id', "INT NULL AFTER `id`");
        self::addColumnIfMissing($db, 'products', 'variant_label', "VARCHAR(120) NULL AFTER `parent_product_id`");
        self::addColumnIfMissing($db, 'invoices', 'transaction_type', "VARCHAR(20) NOT NULL DEFAULT 'Sale'");
        self::addColumnIfMissing($db, 'invoices', 'notes', "TEXT NULL");
        self::addColumnIfMissing($db, 'invoices', 'customer_name_snapshot', "VARCHAR(255) NULL");
        self::addColumnIfMissing($db, 'invoices', 'customer_phone_snapshot', "VARCHAR(50) NULL");
        self::addColumnIfMissing($db, 'invoices', 'payment_method', "VARCHAR(20) NOT NULL DEFAULT 'cash'");
        self::addColumnIfMissing($db, 'invoices', 'sale_channel', "VARCHAR(20) NOT NULL DEFAULT 'retail'");
        self::addColumnIfMissing($db, 'invoices', 'credit_due_date', "DATETIME NULL");
        self::addColumnIfMissing($db, 'invoices', 'credit_private_note', "TEXT NULL");
        self::addColumnIfMissing($db, 'invoices', 'paid_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0");
        self::addColumnIfMissing($db, 'invoices', 'balance_due', "DECIMAL(10,2) NOT NULL DEFAULT 0");
        self::addColumnIfMissing($db, 'invoices', 'is_credit_settled', "TINYINT(1) NOT NULL DEFAULT 0");
        self::addColumnIfMissing($db, 'invoices', 'last_credit_reminder_at', "DATETIME NULL");
        self::addColumnIfMissing($db, 'invoices', 'tax_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0");
        self::addColumnIfMissing($db, 'invoices', 'tax_rate', "DECIMAL(6,3) NOT NULL DEFAULT 0");
        self::addColumnIfMissing($db, 'invoices', 'tax_label', "VARCHAR(80) NULL");
        self::addColumnIfMissing($db, 'invoices', 'fbr_pos_id', "VARCHAR(80) NULL");
        self::addColumnIfMissing($db, 'invoices', 'points_earned', "INT NOT NULL DEFAULT 0");
        self::addColumnIfMissing($db, 'invoices', 'points_redeemed', "INT NOT NULL DEFAULT 0");
        self::addColumnIfMissing($db, 'customers', 'loyalty_points', "INT NOT NULL DEFAULT 0 AFTER `total_spending`");
        self::addColumnIfMissing($db, 'payment_transactions', 'receipt_screenshot', "VARCHAR(255) NULL AFTER `gateway_ref`");
        self::addColumnIfMissing($db, 'invoice_items', 'model_number', "VARCHAR(100) NULL");
        self::addColumnIfMissing($db, 'invoice_items', 'price_tier', "VARCHAR(20) NOT NULL DEFAULT 'retail'");
        self::addColumnIfMissing($db, 'users', 'updated_at', "TIMESTAMP NULL DEFAULT NULL");
        self::addColumnIfMissing($db, 'users', 'permissions', "TEXT NULL");
        self::addColumnIfMissing($db, 'users', 'username', "VARCHAR(100) NULL");
        self::addColumnIfMissing($db, 'users', 'store_id', "INT NULL");
        self::addColumnIfMissing($db, 'users', 'status', "ENUM('active','inactive') NOT NULL DEFAULT 'active'");
        self::ensureUserRoleColumn($db);
        self::addColumnIfMissing($db, 'updates_log', 'validation_method', "VARCHAR(100) NULL");
        self::addColumnIfMissing($db, 'updates_log', 'backup_sql_path', "VARCHAR(255) NULL");
        self::addColumnIfMissing($db, 'updates_log', 'backup_zip_path', "VARCHAR(255) NULL");
        self::addColumnIfMissing($db, 'updates_log', 'rollback_available', "TINYINT(1) NOT NULL DEFAULT 0");
        self::addColumnIfMissing($db, 'updates_log', 'rolled_back_at', "TIMESTAMP NULL DEFAULT NULL");
        self::addColumnIfMissing($db, 'products', 'store_id', "INT NULL");
        self::addColumnIfMissing($db, 'products', 'updated_at', "TIMESTAMP NULL DEFAULT NULL");
        self::addColumnIfMissing($db, 'customers', 'address', "VARCHAR(255) NULL");
        self::addColumnIfMissing($db, 'customers', 'store_id', "INT NULL");
        self::addColumnIfMissing($db, 'invoices', 'store_id', "INT NULL");
        self::addColumnIfMissing($db, 'invoices', 'customer_address_snapshot', "VARCHAR(255) NULL");
        self::addColumnIfMissing($db, 'invoice_items', 'store_id', "INT NULL");
        self::addColumnIfMissing($db, 'expense_categories', 'store_id', "INT NULL");
        self::addColumnIfMissing($db, 'expenses', 'store_id', "INT NULL");
        self::addColumnIfMissing($db, 'credit_payments', 'store_id', "INT NULL");
        self::addColumnIfMissing($db, 'credit_notifications', 'store_id', "INT NULL");
        self::addColumnIfMissing($db, 'activity_logs', 'store_id', "INT NULL");
        self::addColumnIfMissing($db, 'mobile_scanner_events', 'scan_context', "VARCHAR(30) NOT NULL DEFAULT 'checkout' AFTER `barcode`");
        self::addColumnIfMissing($db, 'stores', 'current_plan_id', "INT NULL");
        self::addColumnIfMissing($db, 'stores', 'subscription_started_at', "DATETIME NULL");
        self::addColumnIfMissing($db, 'stores', 'payment_status', "VARCHAR(20) NOT NULL DEFAULT 'pending'");
        self::addColumnIfMissing($db, 'stores', 'parent_store_id', "INT NULL");
        self::addColumnIfMissing($db, 'stores', 'branch_code', "VARCHAR(40) NULL");
        self::addColumnIfMissing($db, 'stores', 'scanner_pair_code', "VARCHAR(6) NULL");
        self::addColumnIfMissing($db, 'stores', 'next_monthly_charge_date', "DATE NULL");
        self::addColumnIfMissing($db, 'stores', 'monthly_charge_amount', "DECIMAL(10,2) NULL");
        self::ensureStoreScannerPairCodes($db);
        self::addColumnIfMissing($db, 'receipt_templates', 'base_template', "VARCHAR(80) NOT NULL DEFAULT 'invoice'");
        self::addColumnIfMissing($db, 'receipt_templates', 'html_block', "LONGTEXT NULL");
        self::addColumnIfMissing($db, 'receipt_templates', 'is_system', "TINYINT(1) NOT NULL DEFAULT 0");
        self::addColumnIfMissing($db, 'superadmin_notifications', 'is_seen', "TINYINT(1) NOT NULL DEFAULT 0");
        self::addColumnIfMissing($db, 'superadmin_notifications', 'store_id', "INT NULL");

        // Ensure fractional/decimal stock and quantity support across inventory, checkout, and batches
        try {
            $db->query("ALTER TABLE `products` MODIFY `stock` DECIMAL(12,3) NOT NULL DEFAULT 0.000, MODIFY `min_stock` DECIMAL(12,3) NOT NULL DEFAULT 5.000, MODIFY `reserved_stock` DECIMAL(12,3) NOT NULL DEFAULT 0.000");
        } catch (\Throwable $e) {}
        try {
            $db->query("ALTER TABLE `invoice_items` MODIFY `quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000");
        } catch (\Throwable $e) {}
        try {
            $db->query("ALTER TABLE `product_batches` MODIFY `current_stock` DECIMAL(12,3) NOT NULL DEFAULT 0.000, MODIFY `initial_quantity` DECIMAL(12,3) NOT NULL DEFAULT 0.000");
        } catch (\Throwable $e) {}
        try {
            $db->query("ALTER TABLE `inventory_purchases` MODIFY `quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000");
        } catch (\Throwable $e) {}
        try {
            $db->query("ALTER TABLE `stock_transfer_items` MODIFY `quantity` DECIMAL(12,3) NOT NULL DEFAULT 1.000");
        } catch (\Throwable $e) {}

        self::addColumnIfMissing($db, 'payment_transactions', 'plan_id', "INT NULL");
        self::addColumnIfMissing($db, 'payment_transactions', 'paid_at', "DATETIME NULL");
        self::addColumnIfMissing($db, 'payment_transactions', 'next_expiry_date', "DATETIME NULL");
        self::addColumnIfMissing($db, 'payment_methods', 'account_holder', "VARCHAR(150) NULL");
        self::addColumnIfMissing($db, 'payment_methods', 'method_logo', "VARCHAR(50) NULL");
        self::addColumnIfMissing($db, 'payment_methods', 'qr_code', "VARCHAR(255) NULL");
        self::ensureCustomerPhoneNullable($db);
        self::ensureCustomerPhoneStoreIndex($db);
        self::ensureExpenseCategoryStoreIndex($db);
        self::ensureStoreStatusColumn($db);
        self::migrateLegacyRecordsToDefaultStore($db);
        self::syncStoreSubscriptionStatus($db);
        self::ensureIndexIfMissing($db, 'invoices', 'idx_invoice_created_status_type', ['created_at', 'status', 'transaction_type']);
        self::ensureIndexIfMissing($db, 'invoices', 'idx_invoice_payment_due_settled', ['payment_method', 'credit_due_date', 'is_credit_settled']);
        self::ensureIndexIfMissing($db, 'invoices', 'idx_invoice_customer_created', ['customer_id', 'created_at']);
        self::ensureIndexIfMissing($db, 'invoice_items', 'idx_invoice_items_invoice_product', ['invoice_id', 'product_id']);
        self::ensureIndexIfMissing($db, 'products', 'idx_products_name_model', ['name', 'model_number']);
        self::ensureIndexIfMissing($db, 'products', 'idx_products_store_barcode', ['store_id', 'barcode']);
        self::ensureIndexIfMissing($db, 'products', 'idx_products_parent', ['parent_product_id']);
        self::ensureIndexIfMissing($db, 'product_scan_codes', 'idx_product_scan_codes_store_code', ['store_id', 'code']);
        self::ensureIndexIfMissing($db, 'product_scan_codes', 'idx_product_scan_codes_product', ['product_id']);
        self::ensureIndexIfMissing($db, 'expenses', 'idx_expenses_method_date', ['payment_method', 'expense_date']);
        self::ensureIndexIfMissing($db, 'expense_categories', 'idx_expense_categories_store_name', ['store_id', 'name']);
        self::ensureIndexIfMissing($db, 'credit_payments', 'idx_credit_payments_invoice_created', ['invoice_id', 'created_at']);
        self::ensureIndexIfMissing($db, 'customers', 'idx_customers_phone', ['phone']);
        self::ensureIndexIfMissing($db, 'customers', 'idx_customers_store_phone', ['store_id', 'phone']);
        self::ensureIndexIfMissing($db, 'users', 'idx_users_store_role', ['store_id', 'role']);
        self::ensureIndexIfMissing($db, 'products', 'idx_products_store', ['store_id']);
        self::ensureIndexIfMissing($db, 'customers', 'idx_customers_store', ['store_id']);
        self::ensureIndexIfMissing($db, 'invoices', 'idx_invoices_store', ['store_id']);
        self::ensureIndexIfMissing($db, 'invoices', 'idx_invoices_store_created', ['store_id', 'created_at']);
        self::ensureIndexIfMissing($db, 'expenses', 'idx_expenses_store', ['store_id']);
        self::ensureIndexIfMissing($db, 'expenses', 'idx_expenses_store_date', ['store_id', 'expense_date']);
        self::ensureIndexIfMissing($db, 'stores', 'idx_stores_expiry_status', ['subscription_expires_at', 'status']);
        self::ensureIndexIfMissing($db, 'stores', 'idx_stores_parent', ['parent_store_id']);
        self::ensureIndexIfMissing($db, 'payment_transactions', 'idx_payments_store_date', ['store_id', 'paid_at']);
        self::ensureIndexIfMissing($db, 'whatsapp_messages', 'idx_whatsapp_store_created', ['store_id', 'created_at']);
        self::ensureIndexIfMissing($db, 'superadmin_notifications', 'idx_sa_notifications_store_seen', ['store_id', 'is_seen']);
        self::ensureIndexIfMissing($db, 'receipt_templates', 'idx_receipt_templates_status_type', ['status', 'template_type']);
        self::ensureIndexIfMissing($db, 'invoice_items', 'idx_invoice_items_store', ['store_id']);
        self::ensureIndexIfMissing($db, 'credit_payments', 'idx_credit_payments_store', ['store_id']);
        self::ensureIndexIfMissing($db, 'users', 'idx_users_store', ['store_id']);
        self::ensureIndexIfMissing($db, 'expense_categories', 'idx_expense_categories_store', ['store_id']);
        self::ensureIndexIfMissing($db, 'activity_logs', 'idx_activity_logs_store', ['store_id']);
        if (class_exists('ReceiptTemplates') || file_exists(__DIR__ . '/ReceiptTemplates.php')) {
            require_once __DIR__ . '/ReceiptTemplates.php';
            ReceiptTemplates::syncDefaults($db);
        }

        $db->query("UPDATE products SET wholesale_price = price WHERE wholesale_price IS NULL");
        $db->query("UPDATE invoices SET payment_method = 'cash' WHERE payment_method IS NULL OR payment_method = ''");
        $db->query("UPDATE invoices SET sale_channel = 'retail' WHERE sale_channel IS NULL OR sale_channel = ''");
        $db->query("
            UPDATE invoices i
            LEFT JOIN (
                SELECT
                    invoice_id,
                    SUM(CASE WHEN LOWER(COALESCE(payment_method, '')) IN ('cash', 'online') THEN amount ELSE 0 END) AS collected_amount,
                    MAX(CASE WHEN LOWER(COALESCE(payment_method, '')) = 'credit' THEN 1 ELSE 0 END) AS has_credit_marker
                FROM credit_payments
                GROUP BY invoice_id
            ) cp ON cp.invoice_id = i.id
            SET i.paid_amount = CASE
                WHEN (
                    LOWER(COALESCE(i.payment_method, '')) = 'credit'
                    OR i.credit_due_date IS NOT NULL
                    OR COALESCE(cp.has_credit_marker, 0) = 1
                ) THEN LEAST(COALESCE(i.total, 0), GREATEST(COALESCE(cp.collected_amount, 0), 0))
                WHEN COALESCE(i.paid_amount, 0) = 0 AND LOWER(COALESCE(i.status, '')) = 'paid'
                    THEN COALESCE(i.total, 0)
                ELSE GREATEST(COALESCE(i.paid_amount, 0), 0)
            END
        ");
        $db->query("UPDATE invoices SET balance_due = GREATEST(COALESCE(total, 0) - COALESCE(paid_amount, 0), 0)");
        $db->query("
            UPDATE invoices
            SET is_credit_settled = CASE
                WHEN (
                    LOWER(COALESCE(payment_method, '')) = 'credit'
                    OR credit_due_date IS NOT NULL
                )
                THEN CASE WHEN COALESCE(balance_due, 0) <= 0 THEN 1 ELSE 0 END
                ELSE COALESCE(is_credit_settled, 0)
            END
        ");
        $db->query("UPDATE users SET permissions = 'dashboard,pos,sales,inventory,customers,reports,expenses,credits,settings' WHERE role = 'staff' AND (permissions IS NULL OR permissions = '')");
        $db->query("UPDATE users SET role = 'store_owner' WHERE role = 'admin'");

        self::seedSetting($db, 'company_name', 'DevAltum POS');
        self::seedSetting($db, 'company_address', '');
        self::seedSetting($db, 'company_ntn', '');
        self::seedSetting($db, 'company_ntn_number', '');
        self::seedSetting($db, 'ntn', '');
        self::seedSetting($db, 'company_tagline', 'Official point of sale system');
        self::seedSetting($db, 'currency_symbol', 'Rs');
        self::seedSetting($db, 'currency_code', 'PKR');
        self::seedSetting($db, 'receipt_footer', 'Thank you for choosing us. For support, please contact our store team.');
        self::seedSetting($db, 'receipt_template', 'classic');
        self::seedSetting($db, 'whatsapp_number', '+923101648600');
        self::seedSetting($db, 'whatsapp_message', 'Thank you for shopping with us. Please contact our store for support or order assistance.');
        self::seedSetting($db, 'credit_whatsapp_template', "Assalam o Alaikum {customer_name},\n\nBhai apki remaining amount {remaining} reh gayi hai. Barah-e-karam apni payment clear kar dein.\n\nThank you,\n{company_name}");
        self::seedSetting($db, 'project_slug', 'devaltum-pos');
        self::seedSetting($db, 'website_logo', '');
        self::seedSetting($db, 'receipt_logo', '');
        self::seedSetting($db, 'show_product_images', '1');
        self::seedSetting($db, 'printer_default_mode', 'document');
        self::seedSetting($db, 'printer_paper_size', 'auto');
        self::seedSetting($db, 'printer_margin_mm', '4');
        self::seedSetting($db, 'scanner_beep_enabled', '1');
        self::seedSetting($db, 'mobile_scanner_enabled', '0');
        self::seedSetting($db, 'mobile_scanner_token', bin2hex(random_bytes(18)));
        self::seedSetting($db, 'hardware_scanner_enabled', '1');
        self::seedSetting($db, 'bluetooth_scanner_enabled', '1');
        self::seedSetting($db, 'printer_status_tracking', '1');
        self::seedSetting($db, 'scanner_poll_interval_ms', '900');
        self::seedSetting($db, 'last_auto_backup_date', '');
        self::seedSetting($db, 'whatsapp_provider', 'none');
        self::seedSetting($db, 'meta_access_token', '');
        self::seedSetting($db, 'meta_phone_number_id', '');
        self::seedSetting($db, 'stripe_public_key', '');
        self::seedSetting($db, 'stripe_secret_key', '');
        self::seedSetting($db, 'paypal_client_id', '');
        self::seedSetting($db, 'paypal_secret', '');
        self::seedSetting($db, 'cron_secret_token', bin2hex(random_bytes(16)));

        self::seedDefaultPlans($db);
        self::seedDefaultPaymentMethods($db);
        self::migrateLegacyBranding($db);

        $defaultExpenseCategories = ['Rent', 'Salary', 'Utilities', 'Internet', 'Transport', 'Maintenance', 'Packaging'];
        foreach ($defaultExpenseCategories as $category) {
            $exists = $db->fetch("SELECT id FROM expense_categories WHERE name = ? LIMIT 1", [$category]);
            if (!$exists) {
                $db->query("INSERT INTO expense_categories (name, created_by) VALUES (?, NULL)", [$category]);
            }
        }
    }

    public static function addColumnIfMissing($db, $table, $column, $definition) {
        $table = str_replace('`', '``', $table);
        $column = str_replace(['`', '\\'], ['``', '\\\\'], $column);
        $exists = $db->fetch("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if (!$exists) {
            $db->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    }

    private static function ensureCustomerPhoneNullable($db) {
        $column = $db->fetch("SHOW COLUMNS FROM `customers` LIKE 'phone'");
        if ($column && strtoupper($column['Null'] ?? 'NO') === 'NO') {
            $db->query("ALTER TABLE `customers` MODIFY `phone` VARCHAR(50) NULL");
        }
    }

    private static function ensureCustomerPhoneStoreIndex($db) {
        $uniqueIndexes = $db->fetchAll("SHOW INDEX FROM `customers` WHERE Column_name = 'phone' AND Non_unique = 0");
        foreach ($uniqueIndexes as $index) {
            $indexName = (string) ($index['Key_name'] ?? '');
            if ($indexName !== '' && $indexName !== 'PRIMARY') {
                $db->query("ALTER TABLE `customers` DROP INDEX `" . str_replace('`', '``', $indexName) . "`");
            }
        }
    }

    private static function ensureExpenseCategoryStoreIndex($db) {
        $uniqueIndexes = $db->fetchAll("SHOW INDEX FROM `expense_categories` WHERE Column_name = 'name' AND Non_unique = 0");
        foreach ($uniqueIndexes as $index) {
            $indexName = (string) ($index['Key_name'] ?? '');
            if ($indexName !== '' && $indexName !== 'PRIMARY') {
                $db->query("ALTER TABLE `expense_categories` DROP INDEX `" . str_replace('`', '``', $indexName) . "`");
            }
        }
    }

    private static function ensureStoreStatusColumn($db) {
        $column = $db->fetch("SHOW COLUMNS FROM `stores` LIKE 'status'");
        if (!$column) {
            return;
        }
        $type = strtolower((string) ($column['Type'] ?? ''));
        if (strpos($type, 'enum(') === 0) {
            $db->query("ALTER TABLE `stores` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'active'");
        }
    }

    private static function ensureUserRoleColumn($db) {
        $column = $db->fetch("SHOW COLUMNS FROM `users` LIKE 'role'");
        if (!$column) {
            return;
        }

        $type = strtolower((string) ($column['Type'] ?? ''));
        if (strpos($type, 'enum(') === 0 || strpos($type, 'varchar(30)') !== 0) {
            $db->query("ALTER TABLE `users` MODIFY `role` VARCHAR(30) NOT NULL DEFAULT 'staff'");
        }
    }

    private static function ensureIndexIfMissing($db, $table, $indexName, $columns) {
        $table = str_replace('`', '``', $table);
        $indexName = str_replace('`', '``', $indexName);
        $exists = $db->fetch("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$indexName]);
        if ($exists) {
            return;
        }

        $quotedColumns = [];
        foreach ($columns as $column) {
            $quotedColumns[] = '`' . str_replace('`', '``', $column) . '`';
        }

        $db->query("ALTER TABLE `$table` ADD INDEX `$indexName` (" . implode(', ', $quotedColumns) . ")");
    }

    private static function seedSetting($db, $key, $value) {
        $row = $db->fetch("SELECT setting_key FROM settings WHERE setting_key = ?", [$key]);
        if (!$row) {
            $db->query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
        }
    }

    private static function migrateLegacyBranding($db) {
        $replacements = [
            'company_name' => [
                'Al Jannat Watches' => 'DevAltum POS',
                'Al Jannat Watches POS' => 'DevAltum POS',
            ],
            'company_tagline' => [
                'Premium watches and point of sale' => 'Official point of sale system',
            ],
            'project_slug' => [
                'al-jannat-watches-pos' => 'devaltum-pos',
            ],
            'whatsapp_message' => [
                'I am come form Al jannat watches POS' => 'I am coming from DevAltum POS',
                'I am come from Al jannat watches POS' => 'I am coming from DevAltum POS',
            ],
            'credit_whatsapp_template' => [
                "Assalam o Alaikum {customer_name},\n\nThis is a payment reminder from {company_name}.\n\nInvoice: {invoice_no}\nTotal Bill: {total}\nPaid: {paid}\nRemaining Balance: {remaining}\nDue Date: {due_date}\n\nPlease clear the remaining balance at your earliest convenience.\n\nThank you,\n{company_name}\n{company_phone}" => "Assalam o Alaikum {customer_name},\n\nBhai apki remaining amount {remaining} reh gayi hai. Barah-e-karam apni payment clear kar dein.\n\nThank you,\n{company_name}",
            ],
        ];

        foreach ($replacements as $key => $pairs) {
            foreach ($pairs as $oldValue => $newValue) {
                $db->query(
                    "UPDATE settings SET setting_value = ? WHERE setting_key = ? AND setting_value = ?",
                    [$newValue, $key, $oldValue]
                );
            }
        }
    }

    private static function migrateLegacyRecordsToDefaultStore($db) {
        $defaultStore = $db->fetch("SELECT id FROM stores ORDER BY id ASC LIMIT 1");
        $defaultStoreId = (int) ($defaultStore['id'] ?? 0);
        if ($defaultStoreId <= 0) {
            return;
        }

        $db->query("UPDATE users SET store_id = ? WHERE role IN ('store_owner', 'staff') AND (store_id IS NULL OR store_id = 0)", [$defaultStoreId]);
        $db->query("UPDATE products SET store_id = ? WHERE store_id IS NULL OR store_id = 0", [$defaultStoreId]);
        $db->query("UPDATE customers SET store_id = ? WHERE store_id IS NULL OR store_id = 0", [$defaultStoreId]);
        $db->query("UPDATE invoices SET store_id = ? WHERE store_id IS NULL OR store_id = 0", [$defaultStoreId]);
        $db->query("
            UPDATE invoice_items ii
            JOIN invoices i ON i.id = ii.invoice_id
            SET ii.store_id = i.store_id
            WHERE ii.store_id IS NULL OR ii.store_id = 0
        ");
        $db->query("UPDATE expense_categories SET store_id = ? WHERE store_id IS NULL OR store_id = 0", [$defaultStoreId]);
        $db->query("UPDATE expenses SET store_id = ? WHERE store_id IS NULL OR store_id = 0", [$defaultStoreId]);
        $db->query("
            UPDATE credit_payments cp
            JOIN invoices i ON i.id = cp.invoice_id
            SET cp.store_id = i.store_id
            WHERE cp.store_id IS NULL OR cp.store_id = 0
        ");
        $db->query("
            UPDATE credit_notifications n
            JOIN invoices i ON i.id = n.invoice_id
            SET n.store_id = i.store_id
            WHERE n.store_id IS NULL OR n.store_id = 0
        ");
        $db->query("UPDATE activity_logs SET store_id = ? WHERE store_id IS NULL OR store_id = 0", [$defaultStoreId]);
    }

    private static function syncStoreSubscriptionStatus($db) {
        $db->query("
            UPDATE stores
            SET status = 'expired'
            WHERE subscription_expires_at IS NOT NULL
              AND subscription_expires_at < NOW()
              AND status = 'active'
              AND (parent_store_id IS NULL OR parent_store_id = 0)
        ");
    }

    public static function ensureScannerPairCodeColumn($db = null) {
        $db = $db ?: Database::getInstance();
        try {
            $exists = $db->fetch("SHOW COLUMNS FROM `stores` LIKE 'scanner_pair_code'");
            if (!$exists) {
                $db->query("ALTER TABLE `stores` ADD COLUMN `scanner_pair_code` VARCHAR(6) NULL");
                $stores = $db->fetchAll("SELECT id FROM stores");
                foreach ($stores as $s) {
                    $pin = str_pad((string) mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                    $db->query("UPDATE stores SET scanner_pair_code = ? WHERE id = ?", [$pin, (int) $s['id']]);
                }
            }
        } catch (Throwable $e) {}
    }

    private static function ensureStoreScannerPairCodes($db) {
        self::ensureScannerPairCodeColumn($db);
        try {
            $stores = $db->fetchAll("SELECT id, scanner_pair_code FROM stores WHERE scanner_pair_code IS NULL OR scanner_pair_code = ''");
            foreach ($stores as $s) {
                $storeId = (int) $s['id'];
                $pin = str_pad((string) mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                $db->query("UPDATE stores SET scanner_pair_code = ? WHERE id = ?", [$pin, $storeId]);
            }
        } catch (Throwable $e) {}
    }

    public static function getStoreScannerPairCode($storeId = 0) {
        $db = Database::getInstance();
        self::ensureScannerPairCodeColumn($db);

        $storeId = (int) $storeId ?: (int) Tenant::currentStoreId();
        if ($storeId <= 0) {
            $defaultStore = $db->fetch("SELECT id FROM stores ORDER BY id ASC LIMIT 1");
            $storeId = (int) ($defaultStore['id'] ?? 1);
        }

        try {
            $store = $db->fetch("SELECT id, scanner_pair_code FROM stores WHERE id = ? LIMIT 1", [$storeId]);
            if ($store && !empty($store['scanner_pair_code']) && strlen(trim($store['scanner_pair_code'])) === 6) {
                return trim($store['scanner_pair_code']);
            }

            // Generate clean unique 6-digit PIN
            for ($i = 0; $i < 20; $i++) {
                $pin = (string) mt_rand(100000, 999999);
                $exists = $db->fetch("SELECT id FROM stores WHERE scanner_pair_code = ? LIMIT 1", [$pin]);
                if (!$exists) {
                    $db->query("UPDATE stores SET scanner_pair_code = ? WHERE id = ?", [$pin, $storeId]);
                    return $pin;
                }
            }

            $fallbackPin = str_pad((string) ($storeId * 13579 % 900000 + 100000), 6, '0', STR_PAD_LEFT);
            $db->query("UPDATE stores SET scanner_pair_code = ? WHERE id = ?", [$fallbackPin, $storeId]);
            return $fallbackPin;
        } catch (Throwable $e) {
            return '123456';
        }
    }

    public static function resetStoreScannerPairCode($storeId = 0) {
        $db = Database::getInstance();
        self::ensureScannerPairCodeColumn($db);

        $storeId = (int) $storeId ?: (int) Tenant::currentStoreId();
        if ($storeId <= 0) return '';

        try {
            $pin = str_pad((string) mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            $db->query("UPDATE stores SET scanner_pair_code = ? WHERE id = ?", [$pin, $storeId]);
            return $pin;
        } catch (Throwable $e) {
            return '';
        }
    }

    private static function seedDefaultPlans($db) {
        $defaults = [
            ['Free', 0, 0, 30, 2, 200, 'active'],
            ['Basic', 29, 290, 30, 5, 1000, 'active'],
            ['Pro', 79, 790, 30, 15, 5000, 'active'],
            ['Enterprise', 199, 1990, 30, 100, 50000, 'active'],
        ];
        foreach ($defaults as $plan) {
            $exists = $db->fetch("SELECT id FROM subscription_plans WHERE plan_name = ? LIMIT 1", [$plan[0]]);
            if ($exists) {
                continue;
            }
            $db->query("
                INSERT INTO subscription_plans (plan_name, monthly_price, yearly_price, billing_duration_days, max_staff_limit, max_products_limit, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", $plan);
        }
    }

    private static function seedDefaultPaymentMethods($db) {
        $defaults = [
            ['Bank Transfer', 'manual', '', 'Send transfer receipt after payment.', 'active'],
            ['JazzCash', 'manual', '', 'Pay via JazzCash and share transaction ID.', 'active'],
            ['EasyPaisa', 'manual', '', 'Pay via EasyPaisa and share transaction ID.', 'active'],
            ['Stripe', 'gateway', '', 'Configure API keys from settings.', 'inactive'],
            ['PayPal', 'gateway', '', 'Configure API keys from settings.', 'inactive'],
        ];
        foreach ($defaults as $method) {
            $exists = $db->fetch("SELECT id FROM payment_methods WHERE method_name = ? LIMIT 1", [$method[0]]);
            if ($exists) {
                continue;
            }
            $db->query("
                INSERT INTO payment_methods (method_name, method_type, account_number, instructions, status)
                VALUES (?, ?, ?, ?, ?)
            ", $method);
        }
    }

    private static function loadSettings($db) {
        $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
        self::$settings = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['setting_key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $value = $row['setting_value'] ?? '';
            // Prefer non-empty values when duplicate keys exist in legacy databases.
            if (!array_key_exists($key, self::$settings) || (trim((string) self::$settings[$key]) === '' && trim((string) $value) !== '')) {
                self::$settings[$key] = $value;
            }
        }
    }

    public static function reloadSettings() {
        self::$settings = null;
        self::loadSettings(Database::getInstance());
    }

    public static function setting($key, $default = '') {
        if (self::$settings === null) {
            self::loadSettings(Database::getInstance());
        }

        $storeId = Tenant::currentStoreId();
        if ($storeId !== null && $storeId > 0) {
            if ($key === 'company_name' && Tenant::isBranchAccount()) {
                $parentStoreId = Tenant::parentStoreId();
                if ($parentStoreId !== null) {
                    $parent = Database::getInstance()->fetch(
                        "
                        SELECT s.store_name, ss.setting_value AS company_name
                        FROM stores s
                        LEFT JOIN store_settings ss ON ss.store_id = s.id AND ss.setting_key = 'company_name'
                        WHERE s.id = ?
                        LIMIT 1
                        ",
                        [$parentStoreId]
                    );
                    $parentName = trim((string) ($parent['company_name'] ?? ''));
                    if ($parentName !== '') {
                        return $parentName;
                    }
                    if (!empty($parent['store_name'])) {
                        return (string) $parent['store_name'];
                    }
                }
            }

            if ($key === 'company_address' && Tenant::isBranchAccount()) {
                $store = Tenant::currentStore();
                $branchAddress = trim((string) ($store['address'] ?? ''));
                if ($branchAddress !== '') {
                    return $branchAddress;
                }
            }

            if (in_array($key, ['website_logo', 'receipt_logo', 'app_icon'], true) && Tenant::isBranchAccount()) {
                $parentValue = self::parentStoreSetting($key);
                if ($parentValue !== null) {
                    return $parentValue;
                }
            }

            self::loadStoreSettings(Database::getInstance(), $storeId);
            if (is_array(self::$storeSettingsCache) && array_key_exists($key, self::$storeSettingsCache)) {
                return self::$storeSettingsCache[$key];
            }

            if ($key === 'company_name') {
                $store = Database::getInstance()->fetch("SELECT store_name FROM stores WHERE id = ? LIMIT 1", [$storeId]);
                if (!empty($store['store_name'])) {
                    return (string) $store['store_name'];
                }
            }
        }

        return self::$settings[$key] ?? $default;
    }

    public static function settings($keys = []) {
        $data = [];
        if (empty($keys)) {
            return self::$settings ?? [];
        }
        foreach ($keys as $key => $default) {
            if (is_int($key)) {
                $key = $default;
                $default = '';
            }
            $data[$key] = self::setting($key, $default);
        }
        return $data;
    }

    public static function currencySymbol() {
        return self::setting('currency_symbol', 'Rs');
    }

    public static function currencyCode() {
        return self::setting('currency_code', 'PKR');
    }

    public static function saveSettings($pairs) {
        $db = Database::getInstance();
        $storeId = Tenant::currentStoreId();
        if ($storeId !== null && $storeId > 0) {
            if (Tenant::isBranchAccount()) {
                unset($pairs['company_name']);
                unset($pairs['company_address']);
                unset($pairs['website_logo']);
                unset($pairs['receipt_logo']);
                unset($pairs['app_icon']);
            }
            foreach ($pairs as $key => $value) {
                $key = trim((string) $key);
                if ($key === '') {
                    continue;
                }
                $db->query("UPDATE store_settings SET setting_value = ? WHERE store_id = ? AND setting_key = ?", [$value, $storeId, $key]);
                $existing = $db->fetch("SELECT id FROM store_settings WHERE store_id = ? AND setting_key = ? LIMIT 1", [$storeId, $key]);
                if (!$existing) {
                    $db->query("INSERT INTO store_settings (store_id, setting_key, setting_value) VALUES (?, ?, ?)", [$storeId, $key, $value]);
                }
            }
            self::storeReloadSettings($storeId);
            return;
        }

        foreach ($pairs as $key => $value) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }
            // Live deployments may have legacy settings schema without a unique key on setting_key.
            // Update first so existing rows (including accidental duplicates) stay in sync.
            $db->query("UPDATE settings SET setting_value = ? WHERE setting_key = ? OR TRIM(setting_key) = ?", [$value, $key, $key]);
            $existing = $db->fetch("SELECT setting_key FROM settings WHERE setting_key = ? LIMIT 1", [$key]);
            if (!$existing) {
                $db->query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
            }
        }
        self::reloadSettings();
    }

    private static function loadStoreSettings($db, $storeId) {
        $storeId = (int) $storeId;
        if ($storeId <= 0) {
            self::$storeSettingsCache = [];
            self::$storeSettingsStoreId = null;
            return;
        }
        if (self::$storeSettingsStoreId === $storeId && is_array(self::$storeSettingsCache)) {
            return;
        }

        $rows = $db->fetchAll("SELECT setting_key, setting_value FROM store_settings WHERE store_id = ?", [$storeId]);
        self::$storeSettingsCache = [];
        self::$storeSettingsStoreId = $storeId;
        foreach ($rows as $row) {
            $k = trim((string) ($row['setting_key'] ?? ''));
            if ($k === '') {
                continue;
            }
            self::$storeSettingsCache[$k] = $row['setting_value'] ?? '';
        }
    }

    private static function storeReloadSettings($storeId) {
        self::$storeSettingsCache = null;
        self::$storeSettingsStoreId = null;
        self::loadStoreSettings(Database::getInstance(), (int) $storeId);
    }

    private static function parentStoreSetting($key) {
        $parentStoreId = Tenant::parentStoreId();
        if ($parentStoreId === null) {
            return null;
        }

        $row = Database::getInstance()->fetch(
            "SELECT setting_value FROM store_settings WHERE store_id = ? AND setting_key = ? LIMIT 1",
            [$parentStoreId, $key]
        );
        if (!$row) {
            return null;
        }

        return $row['setting_value'] ?? '';
    }

    public static function money($amount) {
        $symbol = self::setting('currency_symbol', 'Rs');
        $formatted = number_format((float) $amount, 2);
        if (substr($formatted, -3) === '.00') {
            $formatted = substr($formatted, 0, -3);
        }
        return $symbol . ' ' . $formatted;
    }

    public static function numberToWordsRupees($amount) {
        $amount = round((float) $amount, 2);
        if ($amount < 0) {
            return 'NEGATIVE ' . self::numberToWordsRupees(abs($amount));
        }
        $rupees = (int) floor($amount);
        $paisa = (int) round(($amount - $rupees) * 100);

        if ($rupees === 0 && $paisa === 0) {
            return 'RUPEES ZERO ONLY';
        }

        $rupeesWords = $rupees > 0 ? self::convertIntegerToWords($rupees) : 'ZERO';
        $result = 'RUPEES ' . $rupeesWords;

        if ($paisa > 0) {
            $paisaWords = self::convertIntegerToWords($paisa);
            $result .= ' AND ' . $paisaWords . ' PAISA';
        }

        $result .= ' ONLY';
        return strtoupper(preg_replace('/\s+/', ' ', trim($result)));
    }

    public static function convertIntegerToWords($number) {
        $number = (int) $number;
        if ($number === 0) {
            return 'ZERO';
        }

        $ones = [
            0 => '', 1 => 'ONE', 2 => 'TWO', 3 => 'THREE', 4 => 'FOUR', 5 => 'FIVE',
            6 => 'SIX', 7 => 'SEVEN', 8 => 'EIGHT', 9 => 'NINE', 10 => 'TEN',
            11 => 'ELEVEN', 12 => 'TWELVE', 13 => 'THIRTEEN', 14 => 'FOURTEEN', 15 => 'FIFTEEN',
            16 => 'SIXTEEN', 17 => 'SEVENTEEN', 18 => 'EIGHTEEN', 19 => 'NINETEEN'
        ];
        $tens = [
            2 => 'TWENTY', 3 => 'THIRTY', 4 => 'FORTY', 5 => 'FIFTY',
            6 => 'SIXTY', 7 => 'SEVENTY', 8 => 'EIGHTY', 9 => 'NINETY'
        ];

        if ($number < 20) {
            return $ones[$number];
        }
        if ($number < 100) {
            $t = (int) ($number / 10);
            $r = $number % 10;
            return trim($tens[$t] . ($r > 0 ? ' ' . $ones[$r] : ''));
        }
        if ($number < 1000) {
            $h = (int) ($number / 100);
            $r = $number % 100;
            return trim($ones[$h] . ' HUNDRED ' . ($r > 0 ? self::convertIntegerToWords($r) : ''));
        }
        if ($number < 100000) {
            $th = (int) ($number / 1000);
            $r = $number % 1000;
            return trim(self::convertIntegerToWords($th) . ' THOUSAND ' . ($r > 0 ? self::convertIntegerToWords($r) : ''));
        }
        if ($number < 10000000) {
            $lakh = (int) ($number / 100000);
            $r = $number % 100000;
            return trim(self::convertIntegerToWords($lakh) . ' LAKH ' . ($r > 0 ? self::convertIntegerToWords($r) : ''));
        }
        $crore = (int) ($number / 10000000);
        $r = $number % 10000000;
        return trim(self::convertIntegerToWords($crore) . ' CRORE ' . ($r > 0 ? self::convertIntegerToWords($r) : ''));
    }

    public static function defaultReceiptVisibility() {
        return [
            'show_logo' => 1,
            'show_store_name' => 1,
            'show_tagline' => 1,
            'show_address' => 1,
            'show_phone' => 1,
            'show_ntn' => 1,
            'show_ribbon' => 1,
            'show_invoice_no' => 1,
            'show_inv_no' => 1,
            'show_customer' => 1,
            'show_cashier' => 1,
            'show_counter' => 1,
            'show_terminal' => 1,
            'show_unit_label' => 1,
            'show_item_counters' => 1,
            'show_summary_counters' => 1,
            'show_subtotal' => 1,
            'show_discount' => 1,
            'show_tax' => 1,
            'show_paid' => 1,
            'show_due' => 1,
            'show_net_payable' => 1,
            'show_words' => 1,
            'show_barcode' => 1,
            'show_greetings' => 1,
            'show_thanks' => 1,
            'show_developer_credit' => 1,
            'show_dev_credit' => 1,
        ];
    }

    public static function defaultReceiptLabels() {
        return [
            'title' => 'INVOICE',
            'store_name_override' => '',
            'invoice_prefix' => 'Inv # :',
            'inv_prefix' => 'Inv # :',
            'customer_label' => 'Name :',
            'cashier_label' => 'Cashier:',
            'counter_label' => 'Counter 1',
            'net_payable_label' => 'Net Payable:',
            'words_prefix' => 'Rupees',
            'greetings' => 'Thanks For Shopping With Us!',
            'thanks_text' => 'Thanks For Shopping With Us!',
            'return_policy' => 'Exchange possible within 7 days with original receipt.',
            'exchange_policy' => 'Exchange possible within 7 days with original receipt.',
            'font_size_preset' => 'normal',
            'custom_footer' => 'Software By DevAltum | +92 3101648600',
            'devaltum_credit_logo_size' => '48',
            'devaltum_credit_font_size' => '12'
        ];
    }

    public static function defaultReceiptSectionOrder() {
        return [
            'header',
            'ribbon',
            'meta',
            'items',
            'summary',
            'totals',
            'net_payable',
            'words',
            'barcode',
            'footer'
        ];
    }

    public static function receiptVisibility() {
        $raw = self::setting('receipt_visibility', '');
        $data = $raw ? json_decode((string) $raw, true) : null;
        if (!is_array($data)) {
            $defaults = self::defaultReceiptVisibility();
            $defaults['show_developer_credit'] = 1;
            $defaults['show_dev_credit'] = 1;
            return $defaults;
        }
        $defaults = self::defaultReceiptVisibility();
        foreach ($defaults as $k => $defVal) {
            if (array_key_exists($k, $data)) {
                $defaults[$k] = !empty($data[$k]) ? 1 : 0;
            }
        }
        $canonicalMap = [
            'show_inv_no' => 'show_invoice_no',
            'show_terminal' => 'show_counter',
            'show_summary_counters' => 'show_item_counters',
            'show_thanks' => 'show_greetings',
            'show_dev_credit' => 'show_developer_credit',
        ];
        foreach ($canonicalMap as $alias => $canonical) {
            if (isset($defaults[$canonical])) {
                $defaults[$alias] = $defaults[$canonical];
            }
        }
        // DevAltum developer credit signature is permanently enabled across all templates
        $defaults['show_developer_credit'] = 1;
        $defaults['show_dev_credit'] = 1;
        return $defaults;
    }

    public static function receiptLabels() {
        $raw = self::setting('receipt_labels', '');
        $data = $raw ? json_decode((string) $raw, true) : null;
        if (!is_array($data)) {
            return self::defaultReceiptLabels();
        }
        $defaults = self::defaultReceiptLabels();
        foreach ($defaults as $k => $defVal) {
            if (array_key_exists($k, $data)) {
                $defaults[$k] = (string) $data[$k];
            }
        }
        if (isset($defaults['invoice_prefix'])) $defaults['inv_prefix'] = $defaults['invoice_prefix'];
        if (isset($defaults['greetings'])) $defaults['thanks_text'] = $defaults['greetings'];
        if (isset($defaults['return_policy'])) $defaults['exchange_policy'] = $defaults['return_policy'];
        return $defaults;
    }

    public static function receiptSectionOrder() {
        $raw = self::setting('receipt_section_order', '');
        $data = $raw ? json_decode((string) $raw, true) : null;
        if (!is_array($data) || empty($data)) {
            return self::defaultReceiptSectionOrder();
        }
        $defaults = self::defaultReceiptSectionOrder();
        $filtered = array_values(array_intersect($data, $defaults));
        foreach ($defaults as $sec) {
            if (!in_array($sec, $filtered, true)) {
                $filtered[] = $sec;
            }
        }
        return $filtered;
    }

    public static function normalizeBarcode($value) {
        $value = strtoupper(trim((string) $value));
        $value = preg_replace('/[^A-Z0-9\-\.\ \$\/\+\%]/', '', $value);
        return substr((string) $value, 0, 80);
    }

    public static function generateProductBarcode($storeId, $productId) {
        $storeId = max(0, (int) $storeId);
        $productId = max(0, (int) $productId);
        return self::normalizeBarcode('DA' . str_pad((string) $storeId, 3, '0', STR_PAD_LEFT) . str_pad((string) $productId, 7, '0', STR_PAD_LEFT));
    }

    public static function generateBoxBarcode($storeId, $productId) {
        $storeId = max(0, (int) $storeId);
        $productId = max(0, (int) $productId);
        return self::normalizeBarcode('DAB' . str_pad((string) $storeId, 3, '0', STR_PAD_LEFT) . str_pad((string) $productId, 7, '0', STR_PAD_LEFT));
    }

    public static function generateBarcode($storeId = 0) {
        if (class_exists('BarcodeHelper')) {
            return BarcodeHelper::generateEan13($storeId);
        }
        $storeId = max(0, (int) $storeId);
        return '20' . str_pad((string) ($storeId % 100), 2, '0', STR_PAD_LEFT) . mt_rand(10000000, 99999999);
    }

    public static function allowedProductUnits() {
        return ['pcs', 'g', 'kg', 'mg', 'lb', 'oz', 'ml', 'l', 'meter', 'cm', 'pack', 'box', 'bag', 'dozen'];
    }

    public static function normalizeProductUnit($unit) {
        $unit = strtolower(trim((string) $unit));
        return in_array($unit, self::allowedProductUnits(), true) ? $unit : 'pcs';
    }

    public static function productUnitLabel($unit) {
        $unit = self::normalizeProductUnit($unit ?: 'pcs');
        $labels = [
            'pcs' => 'PCS',
            'g' => 'Gram',
            'kg' => 'Kilogram',
            'mg' => 'Milligram',
            'lb' => 'Pound',
            'oz' => 'Ounce',
            'ml' => 'Milliliter',
            'l' => 'Liter',
            'meter' => 'Meter',
            'cm' => 'Centimeter',
            'pack' => 'Pack',
            'box' => 'Box',
            'bag' => 'Bag',
            'dozen' => 'Dozen'
        ];

        return $labels[$unit] ?? strtoupper($unit);
    }

    public static function formatProductUnit($value, $unit) {
        $unit = self::normalizeProductUnit($unit ?: 'pcs');
        $value = (float) ($value ?? 0);
        if ($value <= 0) {
            $value = 1;
        }
        $formatted = rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
        return $formatted . ' ' . self::productUnitLabel($unit);
    }

    public static function formatSoldUnit($quantity, $unitType = 'pcs', $unitValue = 1) {
        $unit = self::normalizeProductUnit($unitType ?: 'pcs');
        $qty = (float) ($quantity ?? 1);
        $baseUnitVal = max(0.001, (float) ($unitValue ?? 1));

        // Weight units: KG / Gram / Milligram / Pound / Ounce
        if (in_array($unit, ['kg', 'g', 'mg', 'lb', 'oz'], true)) {
            if ($unit === 'kg') {
                if ($qty < 1 && $qty > 0) {
                    $grams = round($qty * 1000, 2);
                    $gramsFormatted = rtrim(rtrim(number_format($grams, 2, '.', ''), '0'), '.');
                    return $gramsFormatted . ' Gram';
                }
                return self::formatQuantity($qty) . ' KG';
            }
            if ($unit === 'g') {
                if ($qty >= 1000) {
                    $kg = round($qty / 1000, 3);
                    return self::formatQuantity($kg) . ' KG';
                }
                return self::formatQuantity($qty) . ' Gram';
            }
            if ($unit === 'mg') {
                return self::formatQuantity($qty) . ' mg';
            }
            if ($unit === 'lb') {
                return self::formatQuantity($qty) . ' lb';
            }
            if ($unit === 'oz') {
                return self::formatQuantity($qty) . ' oz';
            }
        }

        // Volume units: Liter / Milliliter
        if (in_array($unit, ['l', 'ml'], true)) {
            if ($unit === 'l') {
                if ($qty < 1 && $qty > 0) {
                    $ml = round($qty * 1000, 2);
                    $mlFormatted = rtrim(rtrim(number_format($ml, 2, '.', ''), '0'), '.');
                    return $mlFormatted . ' ml';
                }
                return self::formatQuantity($qty) . ' Ltr';
            }
            if ($unit === 'ml') {
                if ($qty >= 1000) {
                    $l = round($qty / 1000, 3);
                    return self::formatQuantity($l) . ' Ltr';
                }
                return self::formatQuantity($qty) . ' ml';
            }
        }

        // Other discrete units (pcs, box, pack, bag, meter, dozen, etc.)
        $unitLabel = self::productUnitLabel($unit);
        $formattedQty = self::formatQuantity($qty);
        return $formattedQty . ' ' . $unitLabel;
    }

    public static function barcodeSvg($value, $height = 54, $module = 1.6) {
        $value = self::normalizeBarcode($value);
        if ($value === '') {
            return '';
        }

        $patterns = [
            '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
            '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
            '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
            'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
            'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
            'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
            'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
            'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
            'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
            '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
            '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
        ];

        $encoded = '*' . $value . '*';
        $x = 8.0;
        $bars = [];
        foreach (str_split($encoded) as $char) {
            if (!isset($patterns[$char])) {
                continue;
            }
            $pattern = $patterns[$char];
            for ($i = 0; $i < 9; $i++) {
                $width = ($pattern[$i] === 'w' ? 3 : 1) * $module;
                if ($i % 2 === 0) {
                    $bars[] = '<rect x="' . round($x, 2) . '" y="8" width="' . round($width, 2) . '" height="' . (int) $height . '" rx="0.4" fill="#0f172a"/>';
                }
                $x += $width;
            }
            $x += $module;
        }

        $width = max(120, (int) ceil($x + 8));
        $label = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return '<svg class="barcode-svg" viewBox="0 0 ' . $width . ' ' . ((int) $height + 16) . '" role="img" aria-label="Barcode ' . $label . '" xmlns="http://www.w3.org/2000/svg">' . implode('', $bars) . '</svg>';
    }

    public static function qrSvg($value, $pixelSize = 64) {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $version = 5;
        $size = 21 + ($version - 1) * 4;
        $dataCodewords = 108;
        $eccCodewords = 26;
        $bytes = array_values(unpack('C*', substr($value, 0, 100)));
        $bits = [];
        $appendBits = static function ($val, $len) use (&$bits) {
            for ($i = $len - 1; $i >= 0; $i--) {
                $bits[] = ($val >> $i) & 1;
            }
        };

        $appendBits(4, 4);
        $appendBits(count($bytes), 8);
        foreach ($bytes as $byte) {
            $appendBits($byte, 8);
        }
        $capacity = $dataCodewords * 8;
        $appendBits(0, min(4, max(0, $capacity - count($bits))));
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $data = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $byte = 0;
            for ($j = 0; $j < 8; $j++) {
                $byte = ($byte << 1) | ($bits[$i + $j] ?? 0);
            }
            $data[] = $byte;
        }
        for ($pad = 0; count($data) < $dataCodewords; $pad ^= 1) {
            $data[] = $pad ? 0x11 : 0xEC;
        }

        $ecc = self::qrReedSolomon($data, $eccCodewords);
        $codewords = array_merge($data, $ecc);
        $allBits = [];
        foreach ($codewords as $byte) {
            for ($i = 7; $i >= 0; $i--) {
                $allBits[] = ($byte >> $i) & 1;
            }
        }

        $modules = array_fill(0, $size, array_fill(0, $size, null));
        $function = array_fill(0, $size, array_fill(0, $size, false));
        $set = static function ($x, $y, $dark, $isFunction = true) use (&$modules, &$function, $size) {
            if ($x < 0 || $y < 0 || $x >= $size || $y >= $size) {
                return;
            }
            $modules[$y][$x] = (bool) $dark;
            if ($isFunction) {
                $function[$y][$x] = true;
            }
        };
        $finder = static function ($x, $y) use ($set) {
            for ($dy = -1; $dy <= 7; $dy++) {
                for ($dx = -1; $dx <= 7; $dx++) {
                    $xx = $x + $dx;
                    $yy = $y + $dy;
                    $dark = ($dx >= 0 && $dx <= 6 && $dy >= 0 && $dy <= 6 && ($dx === 0 || $dx === 6 || $dy === 0 || $dy === 6 || ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4)));
                    $set($xx, $yy, $dark, true);
                }
            }
        };
        $finder(0, 0);
        $finder($size - 7, 0);
        $finder(0, $size - 7);
        for ($i = 8; $i < $size - 8; $i++) {
            $set($i, 6, $i % 2 === 0, true);
            $set(6, $i, $i % 2 === 0, true);
        }
        foreach ([6, 30] as $ax) {
            foreach ([6, 30] as $ay) {
                if (($ax === 6 && $ay === 6) || ($ax === 30 && $ay === 6) || ($ax === 6 && $ay === 30)) {
                    continue;
                }
                for ($dy = -2; $dy <= 2; $dy++) {
                    for ($dx = -2; $dx <= 2; $dx++) {
                        $dist = max(abs($dx), abs($dy));
                        $set($ax + $dx, $ay + $dy, $dist !== 1, true);
                    }
                }
            }
        }
        $set(8, 29, true, true);

        for ($i = 0; $i < 9; $i++) {
            if ($i !== 6) {
                $function[8][$i] = true;
                $function[$i][8] = true;
            }
        }
        for ($i = 0; $i < 8; $i++) {
            $function[8][$size - 1 - $i] = true;
            $function[$size - 1 - $i][8] = true;
        }

        $bitIndex = 0;
        $dir = -1;
        for ($x = $size - 1; $x >= 1; $x -= 2) {
            if ($x === 6) {
                $x--;
            }
            for ($i = 0; $i < $size; $i++) {
                $y = $dir === -1 ? $size - 1 - $i : $i;
                for ($col = 0; $col < 2; $col++) {
                    $xx = $x - $col;
                    if ($function[$y][$xx]) {
                        continue;
                    }
                    $bit = $allBits[$bitIndex++] ?? 0;
                    $modules[$y][$xx] = (bool) ($bit ^ ((($xx + $y) % 2) === 0 ? 1 : 0));
                }
            }
            $dir *= -1;
        }

        self::qrDrawFormat($modules, $function, 1, 0);

        $quiet = 4;
        $viewSize = $size + ($quiet * 2);
        $rects = [];
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($modules[$y][$x]) {
                    $rects[] = '<rect x="' . ($x + $quiet) . '" y="' . ($y + $quiet) . '" width="1" height="1"/>';
                }
            }
        }
        $label = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return '<svg class="receipt-qr-svg" width="' . (int) $pixelSize . '" height="' . (int) $pixelSize . '" viewBox="0 0 ' . $viewSize . ' ' . $viewSize . '" role="img" aria-label="Verify invoice QR ' . $label . '" xmlns="http://www.w3.org/2000/svg"><rect width="' . $viewSize . '" height="' . $viewSize . '" fill="#fff"/><g fill="#0f172a" shape-rendering="crispEdges">' . implode('', $rects) . '</g></svg>';
    }

    public static function qrImageUrl($value, $pixelSize = 180, $margin = 10) {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $pixelSize = max(80, min(600, (int) $pixelSize));
        $margin = max(0, min(30, (int) $margin));

        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $pixelSize . 'x' . $pixelSize
            . '&margin=' . $margin
            . '&format=png&ecc=Q&color=0f172a&bgcolor=ffffff&data=' . rawurlencode($value);
    }

    private static function qrDrawFormat(&$modules, &$function, $eccLevelBits, $mask) {
        $size = count($modules);
        $data = (($eccLevelBits & 3) << 3) | ($mask & 7);
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $bits = (($data << 10) | $rem) ^ 0x5412;
        $pos1 = [[0,8],[1,8],[2,8],[3,8],[4,8],[5,8],[7,8],[8,8],[8,7],[8,5],[8,4],[8,3],[8,2],[8,1],[8,0]];
        $pos2 = [];
        for ($i = 0; $i < 8; $i++) $pos2[] = [$size - 1 - $i, 8];
        for ($i = 0; $i < 7; $i++) $pos2[] = [8, $size - 7 + $i];
        for ($i = 0; $i < 15; $i++) {
            $dark = (($bits >> $i) & 1) !== 0;
            [$x1, $y1] = $pos1[$i];
            [$x2, $y2] = $pos2[$i];
            $modules[$y1][$x1] = $dark;
            $modules[$y2][$x2] = $dark;
            $function[$y1][$x1] = true;
            $function[$y2][$x2] = true;
        }
    }

    private static function qrReedSolomon($data, $degree) {
        $gen = [1];
        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($gen) + 1, 0);
            for ($j = 0; $j < count($gen); $j++) {
                $next[$j] ^= self::qrGfMul($gen[$j], self::qrGfPow(2, $i));
                $next[$j + 1] ^= $gen[$j];
            }
            $gen = $next;
        }
        $ecc = array_fill(0, $degree, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ $ecc[0];
            array_shift($ecc);
            $ecc[] = 0;
            for ($i = 0; $i < $degree; $i++) {
                $ecc[$i] ^= self::qrGfMul($gen[$i + 1], $factor);
            }
        }
        return $ecc;
    }

    private static function qrGfPow($x, $power) {
        $result = 1;
        for ($i = 0; $i < $power; $i++) {
            $result = self::qrGfMul($result, $x);
        }
        return $result;
    }

    private static function qrGfMul($x, $y) {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = (($z << 1) ^ (($z >> 7) * 0x11D)) & 0xFF;
            if ((($y >> $i) & 1) !== 0) {
                $z ^= $x;
            }
        }
        return $z;
    }

    public static function flash($type, $message) {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    public static function pullFlash() {
        if (empty($_SESSION['flash_message'])) {
            return null;
        }

        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        $_SESSION['flash_message'] = null;
        unset($_SESSION['flash_message']);
        return $flash;
    }

    public static function lowStockProducts($limit = 6) {
        [$where, $params] = Tenant::storeFilter();
        return Database::getInstance()->fetchAll(
            "SELECT id, name, model_number, stock, min_stock FROM products WHERE stock <= min_stock AND $where ORDER BY stock ASC, name ASC LIMIT " . intval($limit),
            $params
        );
    }

    public static function dueCreditNotifications($limit = 10) {
        [$where, $params] = Tenant::storeFilter();
        return Database::getInstance()->fetchAll(
            "SELECT id, custom_id, customer_name_snapshot, customer_phone_snapshot, total, paid_amount, balance_due, credit_due_date, credit_private_note
             FROM invoices
             WHERE payment_method = 'credit' AND balance_due > 0 AND credit_due_date IS NOT NULL AND credit_due_date <= DATE_ADD(NOW(), INTERVAL 1 DAY) AND $where
             ORDER BY credit_due_date ASC
             LIMIT " . intval($limit),
            $params
        );
    }

    public static function latestNotifications($limit = 25) {
        if (Auth::isSuperAdmin()) {
            return self::latestSuperadminNotifications($limit);
        }
        $db = Database::getInstance();
        [$where, $params] = Tenant::storeFilter('n');
        return $db->fetchAll(
            "SELECT
                n.id,
                'Credit Due' AS type,
                n.title,
                n.message,
                CONCAT('/notifications/open/', n.id) AS link,
                n.due_at AS created_at,
                'danger' AS severity
             FROM credit_notifications n
             WHERE n.is_seen = 0 AND $where
             ORDER BY n.due_at ASC, n.id DESC
             LIMIT " . intval(max(1, (int) $limit)),
            $params
        );
    }

    public static function unreadCreditNotificationsCount() {
        if (Auth::isSuperAdmin()) {
            return self::unreadSuperadminNotificationsCount();
        }
        [$where, $params] = Tenant::storeFilter();
        $row = Database::getInstance()->fetch("SELECT COUNT(*) AS total FROM credit_notifications WHERE is_seen = 0 AND $where", $params);
        return (int) ($row['total'] ?? 0);
    }

    public static function notificationsByState($state = 'new', $limit = 250) {
        if (Auth::isSuperAdmin()) {
            return self::superadminNotificationsByState($state, $limit);
        }
        $isSeen = $state === 'seen' ? 1 : 0;
        [$where, $params] = Tenant::storeFilter('n');
        return Database::getInstance()->fetchAll(
            "SELECT
                n.id,
                n.invoice_id,
                n.title,
                n.message,
                n.due_at,
                n.is_seen,
                n.seen_at,
                n.created_at,
                i.custom_id,
                COALESCE(c.name, i.customer_name_snapshot, 'Walk-in Customer') AS customer_name,
                COALESCE(c.phone, i.customer_phone_snapshot, '') AS customer_phone
             FROM credit_notifications n
             JOIN invoices i ON i.id = n.invoice_id
             LEFT JOIN customers c ON c.id = i.customer_id
             WHERE n.is_seen = ? AND $where
             ORDER BY n.due_at DESC, n.id DESC
             LIMIT " . intval(max(1, (int) $limit)),
            array_merge([$isSeen], $params)
        );
    }

    public static function markNotificationSeen($id) {
        if (Auth::isSuperAdmin()) {
            self::markSuperadminNotificationSeen($id);
            return;
        }
        [$where, $params] = Tenant::storeFilter();
        Database::getInstance()->query(
            "UPDATE credit_notifications SET is_seen = 1, seen_at = NOW() WHERE id = ? AND $where",
            array_merge([(int) $id], $params)
        );
    }

    public static function markAllNotificationsSeen() {
        if (Auth::isSuperAdmin()) {
            self::markAllSuperadminNotificationsSeen();
            return;
        }
        [$where, $params] = Tenant::storeFilter();
        Database::getInstance()->query(
            "UPDATE credit_notifications SET is_seen = 1, seen_at = NOW() WHERE is_seen = 0 AND $where",
            $params
        );
    }

    public static function notificationById($id) {
        if (Auth::isSuperAdmin()) {
            return self::superadminNotificationById($id);
        }
        [$where, $params] = Tenant::storeFilter('n');
        return Database::getInstance()->fetch(
            "SELECT n.*, i.id AS invoice_id
             FROM credit_notifications n
             JOIN invoices i ON i.id = n.invoice_id
             WHERE n.id = ? AND $where
             LIMIT 1",
            array_merge([(int) $id], $params)
        );
    }

    public static function latestSuperadminNotifications($limit = 25) {
        self::syncSuperadminNotifications();
        return Database::getInstance()->fetchAll(
            "SELECT
                n.id,
                'Plan Expiry' AS type,
                n.title,
                n.message,
                CONCAT('/notifications/open/', n.id) AS link,
                n.due_at AS created_at,
                'warning' AS severity
             FROM superadmin_notifications n
             WHERE n.is_seen = 0
             ORDER BY n.due_at ASC, n.id DESC
             LIMIT " . intval(max(1, (int) $limit))
        );
    }

    public static function unreadSuperadminNotificationsCount() {
        self::syncSuperadminNotifications();
        $row = Database::getInstance()->fetch("SELECT COUNT(*) AS total FROM superadmin_notifications WHERE is_seen = 0");
        return (int) ($row['total'] ?? 0);
    }

    public static function superadminNotificationsByState($state = 'new', $limit = 250) {
        self::syncSuperadminNotifications();
        $isSeen = $state === 'seen' ? 1 : 0;
        return Database::getInstance()->fetchAll(
            "SELECT
                n.id,
                n.store_id,
                n.type,
                n.title,
                n.message,
                n.due_at,
                n.is_seen,
                n.seen_at,
                n.created_at,
                s.store_name,
                s.owner_name,
                s.email,
                s.phone,
                COALESCE(p.plan_name, s.plan_type) AS plan_name
             FROM superadmin_notifications n
             LEFT JOIN stores s ON s.id = n.store_id
             LEFT JOIN subscription_plans p ON p.id = s.current_plan_id
             WHERE n.is_seen = ?
             ORDER BY n.due_at ASC, n.id DESC
             LIMIT " . intval(max(1, (int) $limit)),
            [$isSeen]
        );
    }

    public static function markSuperadminNotificationSeen($id) {
        Database::getInstance()->query(
            "UPDATE superadmin_notifications SET is_seen = 1, seen_at = NOW() WHERE id = ?",
            [(int) $id]
        );
    }

    public static function markAllSuperadminNotificationsSeen() {
        Database::getInstance()->query(
            "UPDATE superadmin_notifications SET is_seen = 1, seen_at = NOW() WHERE is_seen = 0"
        );
    }

    public static function superadminNotificationById($id) {
        return Database::getInstance()->fetch(
            "SELECT n.*, s.id AS related_store_id
             FROM superadmin_notifications n
             LEFT JOIN stores s ON s.id = n.store_id
             WHERE n.id = ?
             LIMIT 1",
            [(int) $id]
        );
    }

    private static function syncSuperadminNotifications() {
        $db = Database::getInstance();
        if (!$db || !$db->getConnection()) {
            return;
        }

        $stores = $db->fetchAll("
            SELECT s.id, s.store_name, s.owner_name, s.subscription_expires_at, COALESCE(p.plan_name, s.plan_type) AS plan_name
            FROM stores s
            LEFT JOIN subscription_plans p ON p.id = s.current_plan_id
            WHERE (s.parent_store_id IS NULL OR s.parent_store_id = 0)
              AND s.subscription_expires_at IS NOT NULL
              AND s.subscription_expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)
              AND s.status IN ('active', 'inactive')
            ORDER BY s.subscription_expires_at ASC
        ");

        foreach ($stores as $store) {
            $dueAt = (string) ($store['subscription_expires_at'] ?? '');
            if ($dueAt === '') {
                continue;
            }
            $title = (string) ($store['store_name'] ?? 'Store') . ' plan expires soon';
            $message = trim((string) ($store['plan_name'] ?? 'Plan')) . ' plan will expire on ' . date('M d, Y h:i A', strtotime($dueAt));
            $db->query(
                "INSERT INTO superadmin_notifications (store_id, type, title, message, due_at, is_seen)
                 VALUES (?, 'plan_expiry', ?, ?, ?, 0)
                 ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    message = VALUES(message)",
                [(int) $store['id'], $title, $message, $dueAt]
            );
        }

        $db->query("
            DELETE n
            FROM superadmin_notifications n
            LEFT JOIN stores s ON s.id = n.store_id
            WHERE n.type = 'plan_expiry'
              AND (
                s.id IS NULL
                OR s.subscription_expires_at IS NULL
                OR s.subscription_expires_at > DATE_ADD(NOW(), INTERVAL 1 DAY)
                OR s.subscription_expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
                OR (s.parent_store_id IS NOT NULL AND s.parent_store_id > 0)
              )
        ");
    }

    private static function syncDueCreditNotifications() {
        $db = Database::getInstance();
        if (!$db || !$db->getConnection()) {
            return;
        }

        [$whereInvoices, $whereInvoiceParams] = Tenant::storeFilter();
        $dueRows = $db->fetchAll("
            SELECT id, custom_id, balance_due, credit_due_date, credit_private_note
            FROM invoices
            WHERE payment_method = 'credit'
              AND COALESCE(balance_due, 0) > 0
              AND credit_due_date IS NOT NULL
              AND credit_due_date <= NOW()
              AND $whereInvoices
        ", $whereInvoiceParams);

        foreach ($dueRows as $row) {
            $title = ($row['custom_id'] ?: 'Invoice') . ' | ' . self::money($row['balance_due'] ?? 0);
            $message = trim((string) ($row['credit_private_note'] ?? '')) ?: 'Payment due date reached.';
            $db->query(
                "INSERT INTO credit_notifications (store_id, invoice_id, title, message, due_at, is_seen)
                 VALUES (?, ?, ?, ?, ?, 0)
                 ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    message = VALUES(message)",
                [
                    Tenant::currentStoreId(),
                    (int) $row['id'],
                    $title,
                    $message,
                    $row['credit_due_date'],
                ]
            );
        }

        [$whereN, $whereNParams] = Tenant::storeFilter('n');
        $db->query("
            DELETE n
            FROM credit_notifications n
            LEFT JOIN invoices i ON i.id = n.invoice_id
            WHERE ($whereN)
              AND (
               i.id IS NULL
               OR COALESCE(i.balance_due, 0) <= 0
               OR i.credit_due_date IS NULL
               OR i.credit_due_date > NOW()
              )
        ", $whereNParams);
    }

    public static function uploadFile($file, $targetDir, $prefix = 'file', $allowedExtensions = [], $allowedMimes = [], $maxSizeBytes = 5242880) {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxSizeBytes) {
            return null;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!empty($allowedExtensions) && !in_array($ext, $allowedExtensions, true)) {
            return null;
        }

        if (!empty($allowedMimes) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            if (!in_array((string) $mime, $allowedMimes, true)) {
                return null;
            }
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $safeName = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return null;
        }

        return $targetPath;
    }

    public static function publicPathToUrl($path) {
        $publicPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ROOT_PATH . DIRECTORY_SEPARATOR . 'public');
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (strpos($normalized, $publicPath) !== 0) {
            return '';
        }
        return str_replace(DIRECTORY_SEPARATOR, '/', substr($normalized, strlen($publicPath)));
    }

    /**
     * Check if a local media file physically exists on disk under public/ (or is external URL)
     */
    public static function mediaExists(?string $path): bool {
        $path = trim((string) $path);
        if ($path === '') {
            return false;
        }
        if (preg_match('~^(?:https?:)?//~i', $path) || str_starts_with($path, 'data:')) {
            return true;
        }
        $rel = ltrim(str_replace(['../', '..\\'], '', $path), '/');
        if (defined('BASE_URL') && BASE_URL !== '') {
            $trimmedBase = ltrim(BASE_URL, '/');
            if ($trimmedBase !== '' && str_starts_with($rel, $trimmedBase . '/')) {
                $rel = substr($rel, strlen($trimmedBase . '/'));
            }
        }
        $physical = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
        return file_exists($physical) && is_file($physical);
    }

    /**
     * Resolve media URL cleanly with BASE_URL support for subfolders and fallback
     */
    public static function mediaUrl(?string $path, string $default = ''): string {
        $path = trim((string) $path);
        if ($path === '') {
            return $default !== '' ? self::mediaUrl($default, '') : '';
        }
        if (preg_match('~^(?:https?:)?//~i', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }
        $cleanPath = '/' . ltrim($path, '/');
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        if ($base !== '' && str_starts_with($cleanPath, $base . '/')) {
            return $cleanPath;
        }
        return $base . $cleanPath;
    }

    /**
     * Get the official or custom brand logo URL with automatic disk check fallback
     */
    public static function brandLogoUrl(?string $path = null, string $fallback = '/assets/img/icon-192x192.png'): string {
        if ($path === null) {
            $path = self::setting('website_logo', '');
        }
        $path = trim((string) $path);
        $fallbackUrl = self::mediaUrl($fallback);

        if ($path === '') {
            return $fallbackUrl;
        }
        if (!self::mediaExists($path)) {
            return $fallbackUrl;
        }
        return self::mediaUrl($path, $fallback);
    }

    /**
     * Get the receipt logo URL with fallback
     */
    public static function receiptLogoUrl(?string $path = null): string {
        if ($path === null) {
            $path = self::setting('receipt_logo', '');
        }
        $path = trim((string) $path);
        if ($path !== '' && self::mediaExists($path)) {
            return self::mediaUrl($path);
        }
        if (self::mediaExists('/assets/img/devaltum-receipt-logo.png')) {
            return self::mediaUrl('/assets/img/devaltum-receipt-logo.png');
        }
        return self::brandLogoUrl();
    }

    public static function formatQuantity($qty) {
        $val = (float) ($qty ?? 0);
        if (fmod($val, 1.0) !== 0.0) {
            return rtrim(rtrim(number_format($val, 3, '.', ''), '0'), '.');
        }
        return (string) ((int) $val);
    }

    private static function runDailyModuleBackups($db) {
        $today = date('Y-m-d');
        $last = self::setting('last_auto_backup_date', '');
        if ($today === $last) {
            return;
        }

        $dir = STORAGE_PATH . '/auto_backups/' . date('Ymd');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        [$where, $params] = Tenant::storeFilter();
        self::writeCsvBackup($dir . '/inventory.csv', ['id', 'name', 'model_number', 'barcode', 'unit_value', 'unit_type', 'price', 'wholesale_price', 'cost', 'stock', 'min_stock'], $db->fetchAll("SELECT id, name, model_number, barcode, unit_value, unit_type, price, wholesale_price, cost, stock, min_stock FROM products WHERE $where ORDER BY id ASC", $params));
        self::writeCsvBackup($dir . '/sales.csv', ['id', 'custom_id', 'customer_id', 'customer_name_snapshot', 'customer_phone_snapshot', 'payment_method', 'sale_channel', 'subtotal', 'discount', 'total', 'paid_amount', 'balance_due', 'status', 'transaction_type', 'created_at'], $db->fetchAll("SELECT id, custom_id, customer_id, customer_name_snapshot, customer_phone_snapshot, payment_method, sale_channel, subtotal, discount, total, paid_amount, balance_due, status, transaction_type, created_at FROM invoices WHERE $where ORDER BY id ASC", $params));
        self::writeCsvBackup($dir . '/expenses.csv', ['id', 'category_name_snapshot', 'custom_category', 'title', 'amount', 'payment_method', 'notes', 'expense_date', 'created_at'], $db->fetchAll("SELECT id, category_name_snapshot, custom_category, title, amount, payment_method, notes, expense_date, created_at FROM expenses WHERE $where ORDER BY id ASC", $params));
        self::writeCsvBackup($dir . '/customers.csv', ['id', 'name', 'phone', 'total_spending', 'created_at'], $db->fetchAll("SELECT id, name, phone, total_spending, created_at FROM customers WHERE $where ORDER BY id ASC", $params));

        // Write directly to global settings table - do NOT use saveSettings() here.
        // saveSettings() reads store_id from $_SESSION which is not available at boot time,
        // and may cause foreign key violations if the session carries a stale store_id.
        $db->query("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$today, 'last_auto_backup_date']);
        $existsBackup = $db->fetch("SELECT setting_key FROM settings WHERE setting_key = ? LIMIT 1", ['last_auto_backup_date']);
        if (!$existsBackup) {
            $db->query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)", ['last_auto_backup_date', $today]);
        }
    }

    private static function writeCsvBackup($path, $headers, $rows) {
        $fp = fopen($path, 'w');
        if (!$fp) {
            return;
        }
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? '';
            }
            fputcsv($fp, $line);
        }
        fclose($fp);
    }
}
