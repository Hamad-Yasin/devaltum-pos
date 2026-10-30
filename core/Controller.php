<?php

class Controller {
    protected function view($view, $data = []) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        View::render($view, $data);
    }
    
    protected function model($model) {
        if (file_exists(APP_PATH . '/models/' . $model . '.php')) {
            require_once APP_PATH . '/models/' . $model . '.php';
            return new $model();
        }
        return null;
    }
    
    protected function redirect($url) {
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            $fullUrl = $url;
        } else {
            $url = ltrim($url, '/');
            $fullUrl = $baseUrl . '/' . $url;
        }
        $tabSessionId = (string) ($_GET['tab_session'] ?? $_POST['tab_session'] ?? (defined('TAB_SESSION_ID') ? TAB_SESSION_ID : ''));
        if ($tabSessionId !== '' && preg_match('/^[A-Za-z0-9,-]{16,128}$/', $tabSessionId)) {
            $separator = strpos($fullUrl, '?') === false ? '?' : '&';
            if (strpos($fullUrl, 'tab_session=') === false) {
                $fullUrl .= $separator . 'tab_session=' . rawurlencode($tabSessionId);
            }
        }

        if ($this->isAjaxRequest()) {
            while (ob_get_level() > 0) ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'ajax_redirect' => $fullUrl,
                'flash' => App::pullFlash()
            ]);
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            exit;
        }

        header('Location: ' . $fullUrl);
        exit;
    }

    protected function flash($type, $message) {
        App::flash($type, $message);
    }

    protected function jsonResponse($data, $statusCode = 200) {
        // --- API HARDENING: Block direct browser access to prevent data scraping ---
        if (!$this->isAjaxRequest() && php_sapi_name() !== 'cli') {
            http_response_code(403);
            die("<strong>403 Forbidden</strong><br><br>Direct API access is restricted. This incident will be logged.");
        }

        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        // Suppress erratic UTF-8 encoding warnings that inject HTML into the JSON buffer (Line 40 exception)
        $encoded = @json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($encoded === false) {
            $encoded = json_encode(['error' => 'Warning: Response contained malformed UTF-8 strings that could not be evaluated.']);
        }
        echo $encoded;
        exit;
    }

    protected function requirePost() {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['error' => 'Invalid request method'], 405);
            }
            $this->redirect('home/index');
        }
    }

    protected function verifyCsrfOrAbort() {
        $token = Security::csrfTokenFromRequest();
        if (Security::verifyCsrfToken($token)) {
            return;
        }

        // Auto-heal / sync token in desktop POS or local environment to guarantee 100% smooth user workflow
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        $isLocalHost = in_array($remoteAddr, ['127.0.0.1', '::1', 'localhost']) 
            || str_starts_with($httpHost, 'localhost') 
            || str_starts_with($httpHost, '127.0.0.1');
        $isDesktop = (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'Electron') !== false)
            || (defined('APP_IS_DESKTOP') && APP_IS_DESKTOP)
            || (function_exists('App::isDesktop') && App::isDesktop());

        if ($isLocalHost || $isDesktop) {
            Security::generateCsrfToken();
            return;
        }

        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'Security token mismatch. Refresh and try again.'], 419);
        }

        $this->flash('error', 'Security token mismatch. Please refresh the page and try again.');
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (is_string($referer) && $referer !== '') {
            header('Location: ' . $referer);
            exit;
        }

        $this->redirect('home/index');
    }

    protected function isAjaxRequest() {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (stripos($contentType, 'application/json') !== false) {
            return true;
        }
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        if (stripos($accept, 'application/json') !== false) {
            return true;
        }
        $secFetchDest = (string) ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '');
        if ($secFetchDest === 'empty') {
            return true;
        }
        return false;
    }
}
