<?php

class License {
    private static $secretKey = 'NEXUS_ARMY_SEC_!@#2026_xYz987'; // Master Key
    private static $salt = 'H@M@D_Y@$1N_T0K3N';
    private static $lastError = '';
    private static $mailError = '';

    /**
     * Verifies the installed license key against the current domain.
     * @param string|null $overrideToken Optional token to verify directly instead of reading from DB.
     * @return bool True if valid, false if invalid/expired/domain mismatch
     */
    public static function isValid($overrideToken = null) {
        self::$lastError = '';
        if ($overrideToken === null && self::isLocalhost()) {
            return true;
        }
        $key = $overrideToken !== null ? $overrideToken : App::setting('system_license_key', '');
        if (empty($key)) {
            self::$lastError = 'License key is missing.';
            return false;
        }

        $payload = self::decrypt($key);
        if (!$payload) {
            self::$lastError = 'License key could not be decoded. Please paste the complete activation token.';
            return false;
        }

        $data = json_decode($payload, true);
        if (!is_array($data) || empty($data['domain'])) {
            self::$lastError = 'License payload is invalid or missing its domain.';
            return false;
        }

        // Domain verification (supports both root domains and subfolder hosting)
        $currentDomain = self::detectCurrentDomain();
        $licensedDomain = self::normalizeDomain($data['domain']);

        $currentLower = strtolower($currentDomain);
        $licensedLower = strtolower($licensedDomain);

        $isMatch = ($currentLower === $licensedLower)
            || (strpos($currentLower, $licensedLower . '/') === 0)
            || (strpos($licensedLower, $currentLower . '/') === 0);

        if (!$isMatch) {
            self::$lastError = 'Domain mismatch. This server is "' . $currentDomain . '", but the token is for "' . $licensedDomain . '".';
            return false;
        }

        // OTP / Activation Window Verification (5-minutes)
        if (isset($data['activation_window']) && isset($data['generated_at'])) {
            if (time() > ($data['generated_at'] + $data['activation_window'])) {
                self::$lastError = 'Activation token expired. Please generate a fresh token and verify it within 5 minutes.';
                return false; // The 5-minute activation window has expired!
            }
        }

        // Software Expiry verification
        $expiryDate = $data['software_expiry'] ?? ($data['expiry'] ?? 'lifetime');
        if ($expiryDate !== 'lifetime') {
            $expiryTimestamp = strtotime($expiryDate);
            if (!$expiryTimestamp || time() > $expiryTimestamp) {
                self::$lastError = 'Software license expired on ' . $expiryDate . '.';
                return false; // Software License Expired
            }
        }

        return true; // Valid
    }

    public static function getLastError() {
        return self::$lastError ?: 'Invalid license key, domain mismatch, or token expired.';
    }

    /**
     * Detects the full current domain including any subfolder path.
     */
    public static function detectCurrentDomain(): string {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = defined('BASE_URL') ? BASE_URL : '';
        if ($base === '') {
            $rawScript = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            if ($rawScript !== '/' && $rawScript !== '.' && $rawScript !== '') {
                $reqUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
                if (str_ends_with($rawScript, '/public') && !str_contains($reqUri, '/public')) {
                    $rawScript = substr($rawScript, 0, -7);
                }
                $base = rtrim($rawScript, '/');
            }
        }
        return self::normalizeDomain($host . $base);
    }

    public static function normalizeDomain($domain) {
        $domain = trim((string) $domain);
        if ($domain === '') {
            return 'localhost';
        }

        $domain = preg_replace('/^\s*https?:\/\//i', '', $domain);
        $domain = preg_replace('/^www\./i', '', $domain);

        if (strpos($domain, '@') !== false) {
            $domain = substr($domain, strrpos($domain, '@') + 1);
        }

        // Split host from subfolder path
        $parts = explode('/', $domain, 2);
        $hostPart = $parts[0];
        $pathPart = isset($parts[1]) ? trim($parts[1], '/') : '';

        // Handle IPv6 bracket notation
        if (strpos($hostPart, '[') === 0 && preg_match('/^\[([^\]]+)\](?::\d+)?$/', $hostPart, $matches)) {
            $hostPart = $matches[1];
        } else {
            // Strip port from host
            $hostPart = preg_replace('/:\d+$/', '', $hostPart);
        }

        // Clean subfolder path: remove router endpoints if passed full URL
        if ($pathPart !== '') {
            $pathPart = preg_replace('#\b(installer|licenseactivation|auth|home|dashboard|api|public|index\.php).*$#i', '', $pathPart);
            $pathPart = trim($pathPart, '/');
        }

        $clean = strtolower($hostPart . ($pathPart !== '' ? '/' . $pathPart : ''));
        return trim($clean, " \t\n\r\0\x0B.");
    }

    /**
     * Converts a 5-minute OTP activation token into a permanent Machine Signature.
     */
    public static function createMachineSignature($otpToken) {
        $payload = self::decrypt($otpToken);
        if (!$payload) return false;
        
        $data = json_decode($payload, true);
        if (!is_array($data) || empty($data['domain'])) return false;

        $machinePayload = json_encode([
            'domain' => self::normalizeDomain($data['domain']),
            'software_expiry' => $data['software_expiry'] ?? ($data['expiry'] ?? 'lifetime'),
            'license_email' => strtolower(trim((string) ($data['license_email'] ?? ''))),
            'type' => 'machine_signature',
            'created_at' => time()
        ]);

        return self::encrypt($machinePayload);
    }

    public static function createEmailActivationToken($domain, $email, $expiryDate = 'lifetime') {
        $payload = json_encode([
            'domain' => self::normalizeDomain($domain),
            'license_email' => strtolower(trim((string) $email)),
            'software_expiry' => $expiryDate,
            'activation_window' => 900,
            'generated_at' => time(),
            'type' => 'email_activation'
        ]);

        return self::encrypt($payload);
    }

    public static function isLocalhost(): bool {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
        return str_contains($host, 'localhost') 
            || str_contains($host, '127.0.0.1') 
            || str_ends_with($host, '.test') 
            || str_ends_with($host, '.local');
    }

    public static function generateEmailCode() {
        return (string) random_int(100000, 999999);
    }

    public static function sendActivationEmail($email, $code, $domain, array $smtpConfig = []) {
        self::$mailError = '';
        $email = trim((string) $email);
        $domain = self::normalizeDomain($domain);
        $subject = 'Your DevAltum POS activation code';
        $plainBody = "Your DevAltum POS activation code is: {$code}\n\nDomain: {$domain}\nThis code expires in 15 minutes.\n\nIf you did not request this code, you can ignore this email.";
        $htmlBody = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;padding:24px;border:1px solid #e2e8f0;border-radius:16px;">'
            . '<h2 style="margin:0 0 10px;color:#0f172a;">DevAltum POS Activation</h2>'
            . '<p style="color:#475569;">Use this code to activate your POS installation.</p>'
            . '<div style="font-size:32px;letter-spacing:8px;font-weight:800;color:#0f6dff;background:#f1f5f9;padding:18px;border-radius:14px;text-align:center;">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</div>'
            . '<p style="color:#475569;margin-top:18px;"><strong>Domain:</strong> ' . htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p style="color:#64748b;font-size:13px;">This code expires in 15 minutes.</p>'
            . '</div>';

        // Load vendor packages if not already loaded
        $autoload = defined('ROOT_PATH') ? ROOT_PATH . '/vendor/autoload.php' : dirname(__DIR__) . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        // Load Env helper if available
        if (class_exists('Env')) {
            $envFile = defined('ROOT_PATH') ? ROOT_PATH . '/.env' : dirname(__DIR__) . '/.env';
            if (file_exists($envFile)) {
                Env::load($envFile);
            }
        }

        // Clean hostname for mail header
        $cleanHost = explode('/', $domain)[0];
        $cleanHost = preg_replace('/:\d+$/', '', $cleanHost);

        // Gather SMTP credentials from params, .env, or App::setting
        $smtpHost = $smtpConfig['host'] ?? (class_exists('Env') ? (string) Env::get('MAIL_HOST', '') : '');
        $smtpPort = (int) ($smtpConfig['port'] ?? (class_exists('Env') ? Env::get('MAIL_PORT', 587) : 587));
        $smtpUser = $smtpConfig['username'] ?? (class_exists('Env') ? (string) Env::get('MAIL_USERNAME', '') : '');
        $smtpPass = $smtpConfig['password'] ?? (class_exists('Env') ? (string) Env::get('MAIL_PASSWORD', '') : '');
        $smtpSec  = $smtpConfig['encryption'] ?? (class_exists('Env') ? (string) Env::get('MAIL_ENCRYPTION', 'tls') : 'tls');
        $fromMail = $smtpConfig['from_email'] ?? (class_exists('Env') ? (string) Env::get('MAIL_FROM_ADDRESS', '') : '');
        $fromName = $smtpConfig['from_name'] ?? (class_exists('Env') ? (string) Env::get('MAIL_FROM_NAME', 'DevAltum POS') : 'DevAltum POS');

        if (class_exists('App') && method_exists('App', 'setting')) {
            if (empty($smtpHost)) $smtpHost = (string) App::setting('smtp_host', '');
            if (empty($smtpUser)) $smtpUser = (string) App::setting('smtp_username', '');
            if (empty($smtpPass)) $smtpPass = (string) App::setting('smtp_password', '');
            if (empty($fromMail)) $fromMail = (string) App::setting('smtp_from_email', '');
        }

        // Validate or fallback From address
        if (empty($fromMail) || !filter_var($fromMail, FILTER_VALIDATE_EMAIL)) {
            if (!empty($smtpUser) && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
                $fromMail = $smtpUser;
            } elseif ($cleanHost !== 'localhost' && $cleanHost !== '127.0.0.1' && filter_var('no-reply@' . $cleanHost, FILTER_VALIDATE_EMAIL)) {
                $fromMail = 'no-reply@' . $cleanHost;
            } else {
                $fromMail = 'no-reply@devaltum.com';
            }
        }

        // 1. Try PHPMailer (uses custom SMTP if configured, or default localhost:25 cPanel mailer)
        if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->CharSet = 'UTF-8';

                if (!empty($smtpHost) && !empty($smtpUser) && !empty($smtpPass)) {
                    $mail->isSMTP();
                    $mail->Host       = $smtpHost;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $smtpUser;
                    $mail->Password   = $smtpPass;
                    $mail->SMTPSecure = (strtolower($smtpSec) === 'ssl') ? 'ssl' : 'tls';
                    $mail->Port       = $smtpPort ?: 587;
                    $mail->Timeout    = 12;
                }
                // When empty, PHPMailer defaults to localhost:25 SMTP which is standard on cPanel / Linux hosting!

                $mail->Sender   = $fromMail;
                $mail->setFrom($fromMail, $fromName);
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject  = $subject;
                $mail->Body     = $htmlBody;
                $mail->AltBody  = $plainBody;
                
                if ($mail->send()) {
                    return true;
                } else {
                    self::$mailError = method_exists($mail, 'getErrorInfo') ? $mail->getErrorInfo() : 'Failed to dispatch email';
                }
            } catch (\Throwable $e) {
                self::$mailError = $e->getMessage();
            }

            // Fallback 1b: Try PHPMailer via mail transport if localhost SMTP didn't send
            try {
                $mailFallback = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mailFallback->Mailer  = 'mail';
                $mailFallback->CharSet = 'UTF-8';
                $mailFallback->Sender  = $fromMail;
                $mailFallback->setFrom($fromMail, $fromName);
                $mailFallback->addAddress($email);
                $mailFallback->isHTML(true);
                $mailFallback->Subject = $subject;
                $mailFallback->Body    = $htmlBody;
                $mailFallback->AltBody = $plainBody;
                if ($mailFallback->send()) {
                    return true;
                }
            } catch (\Throwable $e2) {
                self::$mailError = $e2->getMessage();
            }
        }

        // 2. Fallback to PHP native mail() with proper Envelope Sender (-f)
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromMail . '>',
            'Reply-To: ' . $fromMail,
            'Return-Path: ' . $fromMail,
            'X-Mailer: PHP/' . phpversion()
        ];

        if (@mail($email, $subject, $htmlBody, implode("\r\n", $headers), "-f" . $fromMail)) {
            return true;
        }

        if (empty(self::$mailError)) {
            self::$mailError = 'Mail server could not deliver the email. Please verify SMTP credentials or check hosting mail logs.';
        }
        return false;
    }

    public static function getMailError() {
        return self::$mailError;
    }

    private static function encrypt($payload) {
        $method = 'aes-256-cbc';
        $ivLength = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $key = hash('sha256', self::$secretKey . self::$salt, true);
        
        $encrypted = openssl_encrypt($payload, $method, $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    private static function decrypt($encryptedText) {
        $encryptedText = base64_decode($encryptedText);
        if ($encryptedText === false) return false;

        $method = 'aes-256-cbc';
        $ivLength = openssl_cipher_iv_length($method);
        
        if (strlen($encryptedText) < $ivLength) return false;

        $iv = substr($encryptedText, 0, $ivLength);
        $ciphertext = substr($encryptedText, $ivLength);
        
        $key = hash('sha256', self::$secretKey . self::$salt, true);
        
        $decrypted = openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted !== false ? $decrypted : false;
    }

    /**
     * Legacy internal generator.
     */
    public static function generate($domain, $expiryDate = 'lifetime') {
        $payload = json_encode([
            'domain' => self::normalizeDomain($domain),
            'software_expiry' => $expiryDate,
            'activation_window' => 300, // 5 minutes OTP
            'generated_at' => time(),
            'type' => 'otp_activation'
        ]);
        return self::encrypt($payload);
    }
}
