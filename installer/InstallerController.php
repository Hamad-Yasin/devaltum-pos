<?php

class InstallerController extends Controller {

    public function __construct() {
        if (class_exists('App') && method_exists('App', 'isInstalled') && App::isInstalled()) {
            $this->redirect('auth/login');
            exit;
        }
    }

    public function index() {
        if (class_exists('App') && method_exists('App', 'isInstalled') && App::isInstalled()) {
            $this->redirect('auth/login');
            exit;
        }
        $error = '';
        $success = '';
        $devCode = '';
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = trim((string) ($_POST['activation_action'] ?? 'request_code'));
            $domain = License::detectCurrentDomain();
            
            if ($action === 'request_code') {
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                } else {
                    $code = License::generateEmailCode();
                    $_SESSION['installer_email_activation'] = [
                        'email' => $email,
                        'domain' => $domain,
                        'code_hash' => password_hash($code, PASSWORD_DEFAULT),
                        'token' => License::createEmailActivationToken($domain, $email),
                        'expires_at' => time() + 900,
                        'attempts' => 0,
                        'code' => $code
                    ];

                    if (License::sendActivationEmail($email, $code, $domain)) {
                        $success = 'Activation code sent to ' . $email . '. Please check your inbox (and spam folder).';
                    } else {
                        $success = 'Mail server is not configured, so dev preview mode is enabled for testing.';
                        $devCode = $code;
                        $_SESSION['installer_email_activation']['dev_code'] = $code;
                    }
                }
            } elseif ($action === 'verify_code') {
                $pending = $_SESSION['installer_email_activation'] ?? null;
                $code = preg_replace('/\D+/', '', (string) ($_POST['activation_code'] ?? ''));

                if (!is_array($pending)) {
                    $error = 'Please request a fresh activation code first.';
                } elseif (time() > (int) ($pending['expires_at'] ?? 0)) {
                    unset($_SESSION['installer_email_activation']);
                    $error = 'Activation code expired. Please request a new code.';
                } elseif ((int) ($pending['attempts'] ?? 0) >= 5) {
                    unset($_SESSION['installer_email_activation']);
                    $error = 'Too many incorrect attempts. Please request a new code.';
                } elseif ($code === '' || !password_verify($code, (string) ($pending['code_hash'] ?? ''))) {
                    $_SESSION['installer_email_activation']['attempts'] = ((int) ($pending['attempts'] ?? 0)) + 1;
                    $error = 'Incorrect activation code. Please check your email and try again.';
                } elseif (!License::isValid((string) ($pending['token'] ?? ''))) {
                    $error = License::getLastError();
                } else {
                    $_SESSION['installer_license_key'] = (string) $pending['token'];
                    unset($_SESSION['installer_email_activation']);
                    $this->redirect('installer/step2');
                    return;
                }
            } else {
                $token = trim($_POST['license'] ?? '');
                if (empty($token)) {
                    $error = 'License key is required.';
                } elseif (License::isValid($token)) {
                    $_SESSION['installer_license_key'] = $token;
                    $this->redirect('installer/step2');
                    return;
                } else {
                    $error = License::getLastError();
                }
            }
        }

        $pending = $_SESSION['installer_email_activation'] ?? null;
        if ($devCode === '' && is_array($pending) && !empty($pending['dev_code'])) {
            $devCode = (string) $pending['dev_code'];
        }

        $fallbackCode = is_array($pending) && !empty($pending['code']) ? (string) $pending['code'] : '';

        $this->view('step1', [
            'error' => $error,
            'success' => $success,
            'domain' => License::detectCurrentDomain(),
            'pendingEmail' => is_array($pending) ? (string) ($pending['email'] ?? '') : '',
            'devCode' => $devCode,
            'fallbackCode' => $fallbackCode
        ]);
    }

    public function legacyLicense() {
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = trim($_POST['license'] ?? '');

            if (empty($token)) {
                $error = 'License key is required.';
            } else {
                if (License::isValid($token)) {
                    // Valid! Store temporarily in session
                    if (session_status() === PHP_SESSION_NONE) session_start();
                    $_SESSION['installer_license_key'] = $token;
                    $this->redirect('installer/step2');
                    return;
                } else {
                    $error = License::getLastError();
                }
            }
        }
        $this->view('step1', [
            'error' => $error,
            'success' => '',
            'domain' => License::normalizeDomain($_SERVER['HTTP_HOST'] ?? 'localhost'),
            'pendingEmail' => '',
            'devCode' => ''
        ]);
    }

    public function step2() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['installer_license_key'])) {
            $this->redirect('installer/index');
        }

        $requirements = [
            'PHP >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
            'PDO Extension' => extension_loaded('pdo'),
            'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
            'Zip Extension' => extension_loaded('zip'),
            'Config Path Writable' => is_writable(ROOT_PATH . '/config') || !file_exists(CONFIG_PATH . '/database.php'),
            'Root Path Writable (.env)' => is_writable(ROOT_PATH) || !file_exists(ROOT_PATH . '/.env')
        ];

        $this->view('step2', [
            'requirements' => $requirements,
            'allGood' => !in_array(false, $requirements, true)
        ]);
    }

    public function step3() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['installer_license_key'])) {
            $this->redirect('installer/index');
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $host = trim($_POST['host'] ?? 'localhost');
            $dbName = trim($_POST['dbname'] ?? '');
            $user = trim($_POST['user'] ?? 'root');
            $pass = $_POST['pass'] ?? '';

            if ($dbName === '') {
                $error = 'Database name is required.';
            } else {
                try {
                    $port = '3306';
                    if (strpos($host, ':') !== false) {
                        list($parsedHost, $parsedPort) = explode(':', $host, 2);
                        $host = $parsedHost;
                        $port = $parsedPort ?: '3306';
                    }

                    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
                    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `$dbName`");

                    // 1. Auto-generate professional .env file with installation credentials
                    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) === '443');
                    $protocol = $isHttps ? 'https://' : 'http://';
                    $appUrl = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . (defined('BASE_URL') ? BASE_URL : '');

                    if (!class_exists('Env') && file_exists(CORE_PATH . '/helpers/Env.php')) {
                        require_once CORE_PATH . '/helpers/Env.php';
                    }

                    if (class_exists('Env')) {
                        Env::writeEnvFile(ROOT_PATH . '/.env', [
                            'APP_NAME' => 'SaaS Pro POS',
                            'APP_ENV' => 'production',
                            'APP_DEBUG' => false,
                            'APP_URL' => $appUrl,
                            'DB_CONNECTION' => 'mysql',
                            'DB_HOST' => $host,
                            'DB_PORT' => $port,
                            'DB_DATABASE' => $dbName,
                            'DB_USERNAME' => $user,
                            'DB_PASSWORD' => $pass,
                            'MAIL_MAILER' => Env::get('MAIL_MAILER', 'smtp'),
                            'MAIL_HOST' => Env::get('MAIL_HOST', 'smtp.gmail.com'),
                            'MAIL_PORT' => Env::get('MAIL_PORT', '587'),
                            'MAIL_USERNAME' => Env::get('MAIL_USERNAME', ''),
                            'MAIL_PASSWORD' => Env::get('MAIL_PASSWORD', ''),
                            'MAIL_ENCRYPTION' => Env::get('MAIL_ENCRYPTION', 'tls'),
                            'MAIL_FROM_ADDRESS' => Env::get('MAIL_FROM_ADDRESS', 'noreply@saaspro.local'),
                        ]);
                    }

                    // 2. Write backwards-compatible config/database.php
                    $configContent = "<?php\n\n";
                    $configContent .= "// Ensure Env helper is loaded\n";
                    $configContent .= "if (!class_exists('Env')) {\n";
                    $configContent .= "    \$envHelper = dirname(__DIR__) . '/core/helpers/Env.php';\n";
                    $configContent .= "    if (file_exists(\$envHelper)) require_once \$envHelper;\n";
                    $configContent .= "}\n";
                    $configContent .= "if (class_exists('Env')) Env::load(dirname(__DIR__) . '/.env');\n\n";
                    $configContent .= "if (!defined('DB_HOST')) define('DB_HOST', env('DB_HOST', '" . addslashes($host) . "'));\n";
                    $configContent .= "if (!defined('DB_NAME')) define('DB_NAME', env('DB_DATABASE', env('DB_NAME', '" . addslashes($dbName) . "')));\n";
                    $configContent .= "if (!defined('DB_USER')) define('DB_USER', env('DB_USERNAME', env('DB_USER', '" . addslashes($user) . "')));\n";
                    $configContent .= "if (!defined('DB_PASS')) define('DB_PASS', env('DB_PASSWORD', env('DB_PASS', '" . addslashes($pass) . "')));\n";
                    $configContent .= "if (!defined('DB_PORT')) define('DB_PORT', env('DB_PORT', '" . addslashes($port) . "'));\n";

                    if (!is_dir(CONFIG_PATH)) {
                        mkdir(CONFIG_PATH, 0755, true);
                    }

                    file_put_contents(CONFIG_PATH . '/database.php', $configContent);
                    
                    // Unified Enterprise Schema Sync for all 35+ tables
                    if (file_exists(CORE_PATH . '/database/SchemaManager.php')) {
                        require_once CORE_PATH . '/database/SchemaManager.php';
                        SchemaManager::sync($pdo);
                    } else {
                        $this->runMigrations($pdo);
                    }

                    // Save the license key to settings NOW that db and tables exist
                    $otpKey = $_SESSION['installer_license_key'] ?? '';
                    if ($otpKey) {
                        $machineSignature = License::createMachineSignature($otpKey);
                        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('system_license_key', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                        $stmt->execute([$machineSignature ? $machineSignature : $otpKey]);
                    }

                    $this->redirect('installer/step4');
                } catch (PDOException $e) {
                    $error = 'Database connection failed: ' . $e->getMessage();
                }
            }
        }

        $this->view('step3', ['error' => $error]);
    }

    public function step4() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['installer_license_key'])) {
            $this->redirect('installer/index');
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($username === '' || $email === '' || $password === '') {
                $error = 'Username, email and password are required.';
            } elseif (!file_exists(CONFIG_PATH . '/database.php')) {
                $error = 'Database configuration is missing. Please restart the installer.';
            } else {
                require CONFIG_PATH . '/database.php';

                try {
                    $pdo = new PDO(
                        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                        DB_USER,
                        DB_PASS,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );

                    $exists = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                    if ($exists === 0) {
                        $stmt = $pdo->prepare("
                            INSERT INTO users (name, username, email, password, role, status)
                            VALUES (?, ?, ?, ?, 'super_admin', 'active')
                        ");
                        $stmt->execute([$username, $username, $email, password_hash($password, PASSWORD_BCRYPT)]);
                    }

                    // 1. Write lock files
                    if (class_exists('App') && method_exists('App', 'markAsInstalled')) {
                        App::markAsInstalled();
                    }
                    $this->writeInstalledMarker();

                    // 2. Clean up installer session
                    unset($_SESSION['installer_license_key']);
                    unset($_SESSION['installer_email_activation']);

                    // 3. Cleanup installer artifacts
                    $this->cleanupInstallerArtifacts();

                    $this->redirect('auth/login');
                } catch (PDOException $e) {
                    $error = 'Admin account could not be created: ' . $e->getMessage();
                }
            }
        }

        $this->view('step4', ['error' => $error]);
    }

    private function runMigrations($pdo) {
        if (file_exists(CORE_PATH . '/database/SchemaManager.php')) {
            require_once CORE_PATH . '/database/SchemaManager.php';
            return SchemaManager::sync($pdo);
        }
        $sql = "
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `name` VARCHAR(100) NOT NULL,
                `username` VARCHAR(100) NULL,
                `email` VARCHAR(100) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `role` VARCHAR(30) DEFAULT 'staff',
                `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                `permissions` TEXT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `stores` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_uid` VARCHAR(36) NOT NULL UNIQUE,
                `store_name` VARCHAR(150) NOT NULL,
                `owner_name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(120) NOT NULL,
                `phone` VARCHAR(50) NULL,
                `address` VARCHAR(255) NULL,
                `plan_type` VARCHAR(30) NOT NULL DEFAULT 'free',
                `status` ENUM('active', 'suspended', 'inactive') NOT NULL DEFAULT 'active',
                `subscription_expires_at` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `products` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `name` VARCHAR(255) NOT NULL,
                `model_number` VARCHAR(100) NULL,
                `barcode` VARCHAR(80) NULL,
                `unit_value` DECIMAL(10,3) NULL,
                `unit_type` VARCHAR(20) NULL,
                `image_path` VARCHAR(255) NULL,
                `price` DECIMAL(10, 2) NOT NULL,
                `cost` DECIMAL(10, 2) NOT NULL,
                `stock` INT DEFAULT 0,
                `min_stock` INT DEFAULT 5,
                `is_custom` TINYINT(1) NOT NULL DEFAULT 0,
                `is_visible_online` TINYINT(1) NOT NULL DEFAULT 1,
                `reserved_stock` INT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `customers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `name` VARCHAR(255) NOT NULL,
                `phone` VARCHAR(50) NULL UNIQUE,
                `address` VARCHAR(255) NULL,
                `total_spending` DECIMAL(10, 2) DEFAULT 0.00,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `invoices` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `custom_id` VARCHAR(50) NULL UNIQUE,
                `user_id` INT,
                `customer_id` INT NULL,
                `subtotal` DECIMAL(10, 2) NOT NULL,
                `discount` DECIMAL(10, 2) DEFAULT 0.00,
                `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `tax_rate` DECIMAL(6,3) NOT NULL DEFAULT 0,
                `tax_label` VARCHAR(80) NULL,
                `fbr_pos_id` VARCHAR(80) NULL,
                `total` DECIMAL(10, 2) NOT NULL,
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
                `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `balance_due` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `is_credit_settled` TINYINT(1) NOT NULL DEFAULT 0,
                `last_credit_reminder_at` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
                FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `invoice_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `invoice_id` INT NOT NULL,
                `product_id` INT NULL,
                `product_name` VARCHAR(255) NOT NULL,
                `model_number` VARCHAR(100) NULL,
                `quantity` INT NOT NULL,
                `price` DECIMAL(10, 2) NOT NULL,
                `discount` DECIMAL(10, 2) DEFAULT 0.00,
                `total` DECIMAL(10, 2) NOT NULL,
                FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `settings` (
                `setting_key` VARCHAR(100) PRIMARY KEY,
                `setting_value` TEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `activity_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `user_id` INT NULL,
                `action` VARCHAR(255) NOT NULL,
                `details` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `expense_categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `name` VARCHAR(120) NOT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `expenses` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
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
                FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `credit_payments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NULL,
                `invoice_id` INT NOT NULL,
                `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `payment_method` VARCHAR(20) NOT NULL DEFAULT 'cash',
                `note` TEXT NULL,
                `next_due_date` DATETIME NULL,
                `private_message` TEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `credit_notifications` (
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
                FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `store_settings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `setting_key` VARCHAR(100) NOT NULL,
                `setting_value` TEXT NULL,
                UNIQUE KEY `uniq_store_setting` (`store_id`, `setting_key`),
                FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `payment_transactions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL,
                `subscription_id` INT NULL,
                `method` VARCHAR(30) NOT NULL,
                `gateway_ref` VARCHAR(120) NULL,
                `receipt_screenshot` VARCHAR(255) NULL,
                `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `notes` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $pdo->exec($sql);

        $settings = [
            'company_name' => 'DevAltum POS',
            'company_tagline' => 'Official point of sale system',
            'currency_symbol' => 'Rs',
            'currency_code' => 'PKR',
            'receipt_footer' => 'Thank you for shopping with us.',
            'whatsapp_number' => '+923101648600',
            'whatsapp_message' => 'I am coming from DevAltum POS',
            'credit_whatsapp_template' => "Assalam o Alaikum {customer_name},\n\nBhai apki remaining amount {remaining} reh gayi hai. Barah-e-karam apni payment clear kar dein.\n\nThank you,\n{company_name}",
            'project_slug' => 'devaltum-pos',
            'website_logo' => '',
            'receipt_logo' => '',
            'show_product_images' => '1',
            'tax_enabled' => '0',
            'fbr_pos_enabled' => '0',
            'tax_label' => 'Sales Tax',
            'tax_rate' => '18',
            'tax_registration_type' => 'STRN',
            'tax_number' => '',
            'fbr_pos_id' => '',
            'fbr_invoice_note' => 'FBR POS integrated tax invoice'
        ];

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
        }
    }

    private function writeInstalledMarker() {
        if (!is_dir(STORAGE_PATH . '/logs')) {
            mkdir(STORAGE_PATH . '/logs', 0755, true);
        }
        @file_put_contents(STORAGE_PATH . '/logs/installed.log', 'Installed on ' . date('Y-m-d H:i:s'));
    }

    private function cleanupInstallerArtifacts() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        unset($_SESSION['installer_license_key']);
        unset($_SESSION['installer_email_activation']);

        // Mark application as installed
        if (class_exists('App') && method_exists('App', 'markAsInstalled')) {
            App::markAsInstalled();
        }
    }

    private function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return @unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return @rmdir($dir);
    }
}
