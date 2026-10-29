<?php

/**
 * Enterprise Environment Loader & Manager
 * Provides seamless .env reading, writing, and parsing with variable expansion.
 */
class Env {
    private static $loaded = false;
    private static $loadedFile = null;
    private static $variables = [];

    /**
     * Load environment variables from a .env file.
     *
     * @param string|null $filePath Path to .env file (defaults to ROOT_PATH . '/.env')
     * @param bool $override Whether to override existing environment variables
     * @return bool True if loaded successfully, false otherwise
     */
    public static function load($filePath = null, $override = false) {
        if ($filePath === null) {
            $filePath = defined('ROOT_PATH') ? ROOT_PATH . '/.env' : dirname(__DIR__, 2) . '/.env';
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
                continue;
            }

            // Find first '=' separator
            $equalPos = strpos($line, '=');
            if ($equalPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $equalPos));
            $val = trim(substr($line, $equalPos + 1));

            if ($key === '') {
                continue;
            }

            // Unquote value
            $val = self::stripQuotes($val);

            // Handle internal variable substitution like ${APP_NAME}
            $val = self::resolveNestedVariables($val);

            self::$variables[$key] = $val;

            if ($override || getenv($key) === false) {
                putenv("$key=$val");
            }
            if ($override || !isset($_ENV[$key])) {
                $_ENV[$key] = $val;
            }
            if ($override || !isset($_SERVER[$key])) {
                $_SERVER[$key] = $val;
            }
        }

        self::$loaded = true;
        self::$loadedFile = $filePath;
        return true;
    }

    /**
     * Get an environment variable with type conversion and default fallback.
     *
     * @param string $key Variable name
     * @param mixed $default Default value if variable not set
     * @return mixed
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }

        $value = null;

        if (array_key_exists($key, $_ENV)) {
            $value = $_ENV[$key];
        } elseif (array_key_exists($key, $_SERVER)) {
            $value = $_SERVER[$key];
        } else {
            $envVal = getenv($key);
            if ($envVal !== false) {
                $value = $envVal;
            } elseif (array_key_exists($key, self::$variables)) {
                $value = self::$variables[$key];
            }
        }

        if ($value === null) {
            return $default;
        }

        return self::castValue($value);
    }

    /**
     * Check if an environment variable exists.
     */
    public static function has($key) {
        if (!self::$loaded) {
            self::load();
        }
        return array_key_exists($key, $_ENV) || array_key_exists($key, $_SERVER) || getenv($key) !== false;
    }

    /**
     * Set a variable dynamically in memory.
     */
    public static function set($key, $value) {
        $strValue = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        self::$variables[$key] = $strValue;
        $_ENV[$key] = $strValue;
        $_SERVER[$key] = $strValue;
        putenv("$key=$strValue");
    }

    /**
     * Generate a cryptographically secure 32-byte APP_KEY.
     */
    public static function generateKey() {
        try {
            return 'base64:' . base64_encode(random_bytes(32));
        } catch (\Throwable $e) {
            return 'base64:' . base64_encode(openssl_random_pseudo_bytes(32));
        }
    }

    /**
     * Build and write a formatted .env file.
     *
     * @param string $filePath Destination path
     * @param array $config Configuration dictionary
     * @return bool
     */
    public static function writeEnvFile($filePath, array $config) {
        $appName = addcslashes($config['APP_NAME'] ?? 'SaaS Pro POS', '"');
        $appEnv = $config['APP_ENV'] ?? 'production';
        $appDebug = !empty($config['APP_DEBUG']) ? 'true' : 'false';
        $appUrl = rtrim($config['APP_URL'] ?? 'http://localhost', '/');
        $appKey = !empty($config['APP_KEY']) ? $config['APP_KEY'] : self::generateKey();
        $timezone = $config['APP_TIMEZONE'] ?? 'Asia/Karachi';

        $dbConnection = $config['DB_CONNECTION'] ?? 'mysql';
        $dbHost = $config['DB_HOST'] ?? '127.0.0.1';
        $dbPort = $config['DB_PORT'] ?? '3306';
        $dbName = $config['DB_DATABASE'] ?? ($config['DB_NAME'] ?? 'saas-pro-pos');
        $dbUser = $config['DB_USERNAME'] ?? ($config['DB_USER'] ?? 'root');
        $dbPass = $config['DB_PASSWORD'] ?? ($config['DB_PASS'] ?? '');

        $mailMailer = $config['MAIL_MAILER'] ?? 'smtp';
        $mailHost = $config['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mailPort = $config['MAIL_PORT'] ?? '587';
        $mailUser = $config['MAIL_USERNAME'] ?? '';
        $mailPass = $config['MAIL_PASSWORD'] ?? '';
        $mailEnc = $config['MAIL_ENCRYPTION'] ?? 'tls';
        $mailFrom = $config['MAIL_FROM_ADDRESS'] ?? 'noreply@saaspro.local';

        $generatedDate = date('Y-m-d H:i:s');

        $content = <<<ENV
# ==============================================================================
# SAAS PRO POS & INVENTORY ENTERPRISE ENVIRONMENT CONFIGURATION
# Generated automatically by installer on {$generatedDate}
# ==============================================================================

# ------------------------------------------------------------------------------
# APPLICATION SETTINGS
# ------------------------------------------------------------------------------
APP_NAME="{$appName}"
APP_ENV={$appEnv}
APP_DEBUG={$appDebug}
APP_URL={$appUrl}
APP_KEY={$appKey}
APP_TIMEZONE={$timezone}
APP_LOCALE=en

# ------------------------------------------------------------------------------
# DATABASE CONNECTION
# ------------------------------------------------------------------------------
DB_CONNECTION={$dbConnection}
DB_HOST={$dbHost}
DB_PORT={$dbPort}
DB_DATABASE={$dbName}
DB_USERNAME={$dbUser}
DB_PASSWORD="{$dbPass}"

# Compatibility Aliases for Legacy Modules
DB_NAME={$dbName}
DB_USER={$dbUser}
DB_PASS="{$dbPass}"

# ------------------------------------------------------------------------------
# SESSION & SECURITY
# ------------------------------------------------------------------------------
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false
SESSION_COOKIE_NAME=saas_pos_session

# ------------------------------------------------------------------------------
# MAIL / SMTP SERVER (TRANSACTIONAL EMAILS)
# ------------------------------------------------------------------------------
MAIL_MAILER={$mailMailer}
MAIL_HOST={$mailHost}
MAIL_PORT={$mailPort}
MAIL_USERNAME="{$mailUser}"
MAIL_PASSWORD="{$mailPass}"
MAIL_ENCRYPTION={$mailEnc}
MAIL_FROM_ADDRESS="{$mailFrom}"
MAIL_FROM_NAME="\${APP_NAME}"

# ------------------------------------------------------------------------------
# HARDWARE & POS TERMINAL PREFERENCES
# ------------------------------------------------------------------------------
POS_BARCODE_MODE=scanner
POS_WEIGHT_UNIT=kg
POS_DEFAULT_TAX_RATE=0
FBR_POS_ENABLED=false
FBR_POS_ID=""
ENV;

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $result = @file_put_contents($filePath, $content);
        if ($result !== false) {
            @chmod($filePath, 0644);
            return true;
        }
        return false;
    }

    /**
     * Strip quotes and outer whitespaces from value.
     */
    private static function stripQuotes($val) {
        $len = strlen($val);
        if ($len >= 2) {
            $first = $val[0];
            $last = $val[$len - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $unquoted = substr($val, 1, -1);
                if ($first === '"') {
                    $unquoted = str_replace(['\\n', '\\r', '\\t', '\\"'], ["\n", "\r", "\t", '"'], $unquoted);
                }
                return $unquoted;
            }
        }
        return $val;
    }

    /**
     * Resolve nested ${VARIABLE} placeholders.
     */
    private static function resolveNestedVariables($val) {
        if (!str_contains($val, '${')) {
            return $val;
        }

        return preg_replace_callback('/\$\{([a-zA-Z0-9_]+)\}/', function ($matches) {
            $varName = $matches[1];
            if (isset(self::$variables[$varName])) {
                return self::$variables[$varName];
            }
            $envVal = getenv($varName);
            return $envVal !== false ? $envVal : $matches[0];
        }, $val);
    }

    /**
     * Cast string representation to proper PHP data types.
     */
    private static function castValue($value) {
        if (!is_string($value)) {
            return $value;
        }

        $lower = strtolower(trim($value));
        return match ($lower) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => is_numeric($value) && !str_starts_with($value, '0') && !str_contains($value, '.')
                ? (int) $value
                : (is_numeric($value) && !str_starts_with($value, '0') ? (float) $value : $value)
        };
    }
}

// Global convenience helper function (matches Laravel/Symfony standard)
if (!function_exists('env')) {
    function env($key, $default = null) {
        return Env::get($key, $default);
    }
}
