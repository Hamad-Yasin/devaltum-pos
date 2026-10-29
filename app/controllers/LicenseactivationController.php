<?php

class LicenseactivationController extends Controller {
    public function index() {
        if (License::isValid()) {
            $this->redirect('home/index');
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $pending = $_SESSION['license_email_activation'] ?? null;
        $data = [
            'domain' => License::detectCurrentDomain(),
            'pendingEmail' => is_array($pending) ? (string) ($pending['email'] ?? '') : '',
            'devCode' => is_array($pending) ? (string) ($pending['dev_code'] ?? '') : '',
            'page_title' => 'Software License Verification'
        ];

        // Ensure bare minimum CSS structure exists by rendering a standalone layout
        $this->view('license/index', $data);
    }

    public function auto() {
        // Automatic server-ping checking endpoint (Placeholder for future SaaS expansion)
        $this->jsonResponse(['status' => 'manual_activation_required']);
    }

    public function generate_offline_token() {
        // To be used by the offline `encode_security.php` builder ONLY
        // In real deployments, this file inside `controllers` will literally be missing or encoded so
        // attackers cannot hit this route.
        if (php_sapi_name() !== 'cli' && !isset($_GET['dev_override'])) {
             $this->jsonResponse(['error' => 'Not authorized. Tool is offline-only.'], 403);
        }

        $domain = $_GET['domain'] ?? '';
        $expiry = $_GET['expiry'] ?? 'lifetime';

        if (empty($domain)) {
            $this->jsonResponse(['error' => 'Domain missing']);
        }

        $domain = License::normalizeDomain($domain);
        $token = License::generate($domain, $expiry);
        $this->jsonResponse([
            'domain' => $domain,
            'expiry' => $expiry,
            'token' => $token
        ]);
    }

    public function verify() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        if (session_status() === PHP_SESSION_NONE) session_start();
        $pending = $_SESSION['license_email_activation'] ?? null;
        $code = preg_replace('/\D+/', '', (string) ($_POST['activation_code'] ?? ''));

        if (!is_array($pending)) {
            $this->flash('error', 'Please request a fresh activation code first.');
            $this->redirect('licenseactivation/index');
            return;
        }

        if (time() > (int) ($pending['expires_at'] ?? 0)) {
            unset($_SESSION['license_email_activation']);
            $this->flash('error', 'Activation code expired. Please request a new code.');
            $this->redirect('licenseactivation/index');
            return;
        }

        if ((int) ($pending['attempts'] ?? 0) >= 5) {
            unset($_SESSION['license_email_activation']);
            $this->flash('error', 'Too many incorrect attempts. Please request a new code.');
            $this->redirect('licenseactivation/index');
            return;
        }

        if ($code === '' || !password_verify($code, (string) ($pending['code_hash'] ?? ''))) {
            $_SESSION['license_email_activation']['attempts'] = ((int) ($pending['attempts'] ?? 0)) + 1;
            $this->flash('error', 'Incorrect activation code. Please check your email and try again.');
            $this->redirect('licenseactivation/index');
            return;
        }

        $token = (string) ($pending['token'] ?? '');
        if (License::isValid($token)) {
            $machineSignature = License::createMachineSignature($token);
            if ($machineSignature) {
                App::saveSettings(['system_license_key' => $machineSignature]);
                unset($_SESSION['license_email_activation']);
                $this->flash('success', 'License activated perfectly. The machine signature is bound permanently.');
                $this->redirect('home/index');
            } else {
                $this->flash('error', 'Failed to forge machine signature from OTP.');
                $this->redirect('licenseactivation/index');
            }
        } else {
            $this->flash('error', License::getLastError());
            $this->redirect('licenseactivation/index');
        }
    }

    public function requestCode() {
        $this->requirePost();
        $this->verifyCsrfOrAbort();

        if (session_status() === PHP_SESSION_NONE) session_start();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $domain = License::detectCurrentDomain();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Please enter a valid email address.');
            $this->redirect('licenseactivation/index');
            return;
        }

        $code = License::generateEmailCode();
        $_SESSION['license_email_activation'] = [
            'email' => $email,
            'domain' => $domain,
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'token' => License::createEmailActivationToken($domain, $email),
            'expires_at' => time() + 900,
            'attempts' => 0
        ];

        if (License::sendActivationEmail($email, $code, $domain)) {
            $this->flash('success', 'Activation code sent to ' . $email . '. Please check your inbox.');
        } else {
            $_SESSION['license_email_activation']['dev_code'] = $code;
            $this->flash('success', 'Mail server is not configured, so dev preview mode is enabled for testing.');
        }

        $this->redirect('licenseactivation/index');
    }
}
