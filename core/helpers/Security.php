<?php

class Security {
    
    // Generate CSRF token
    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Verify CSRF token
    public static function verifyCsrfToken($token) {
        if (!is_string($token) || $token === '') {
            return false;
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            return true;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfTokenFromRequest() {
        $token = $_POST['csrf_token'] ?? '';
        if ($token !== '') {
            return $token;
        }

        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if ($headerToken !== '') {
            return $headerToken;
        }

        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (strpos($contentType, 'application/json') === false) {
            return '';
        }

        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') {
            return '';
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return '';
        }

        return (string) ($decoded['csrf_token'] ?? '');
    }
    
    // Output safe HTML
    public static function escape($string) {
        return htmlspecialchars((string) ($string ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // HTML input field for CSRF
    public static function csrfField() {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}
