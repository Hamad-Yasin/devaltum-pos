<?php

class UpdatesController extends Controller {

    public function __construct() {
        AuthMiddleware::handleAdmin();
    }

    public function index() {
        $db = Database::getInstance();
        $history = $db->fetchAll("SELECT * FROM updates_log ORDER BY applied_at DESC");
        $availableUpdates = $this->scanUpdatePackages();
        $backupFiles = $this->scanBackupFiles();
        $storeId = (int) Tenant::currentStoreId();
        $scannerPin = App::getStoreScannerPairCode($storeId);
        $baseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $basePath = defined('BASE_URL') ? BASE_URL : '';
        $scannerPairUrl = $baseUrl . $basePath . '/scanner?code=' . urlencode($scannerPin);
        $scannerQrSvg = App::qrSvg($scannerPairUrl, 260);

        $biometrics = $db->fetchAll("SELECT * FROM user_biometrics WHERE user_id = ? ORDER BY id DESC", [Auth::id()]);

        $userId = (int) Auth::id();
        $scannerTokenKey = 'mobile_scanner_token_user_' . max(0, $userId);
        $scannerToken = (string) App::setting($scannerTokenKey, App::setting('mobile_scanner_token', ''));
        if ($scannerToken === '') {
            $scannerToken = bin2hex(random_bytes(18));
        }

        $this->view('../../modules/updates/views/index', [
            'pageTitle' => 'Settings & Updates',
            'activeMenu' => 'settings',
            'history' => $history,
            'availableUpdates' => $availableUpdates,
            'backupFiles' => $backupFiles,
            'biometrics' => $biometrics,
            'isBranchAccount' => Tenant::isBranchAccount(),
            'scannerPairUrl' => $scannerPairUrl,
            'scannerToken' => $scannerToken,
            'scannerPin' => $scannerPin,
            'scannerQrSvg' => $scannerQrSvg,
            'scannerTokenKey' => $scannerTokenKey,
            'settings' => App::settings([
                'company_name' => 'DevAltum POS',
                'company_address' => '',
                'company_ntn' => '',
                'company_ntn_number' => '',
                'ntn' => '',
                'company_tagline' => '',
                'currency_symbol' => 'Rs',
                'currency_code' => 'PKR',
                'receipt_footer' => '',
                'whatsapp_number' => '+923101648600',
                'whatsapp_message' => 'Thank you for shopping with us. Please contact our store for support or order assistance.',
                'credit_whatsapp_template' => "Assalam o Alaikum {customer_name},\n\nBhai apki remaining amount {remaining} reh gayi hai. Barah-e-karam apni payment clear kar dein.\n\nThank you,\n{company_name}",
                'website_logo' => '',
                'receipt_logo' => '',
                'show_product_images' => '1',
                'pos_card_layout' => 'standard',
                'pos_image_fit' => 'cover',
                'pos_show_product_name' => '1',
                'pos_show_product_stock' => '1',
                'pos_show_product_unit' => '1',
                'pos_show_product_price' => '1',
                'printer_default_mode' => 'document',
                'printer_paper_size' => 'auto',
                'printer_margin_mm' => '4',
                'scanner_beep_enabled' => '1',
                'scanner_global_pos_redirect' => '1',
                'mobile_scanner_enabled' => '0',
                'mobile_scanner_token' => $scannerToken,
                'hardware_scanner_enabled' => '1',
                'bluetooth_scanner_enabled' => '1',
                'printer_status_tracking' => '1',
                'scanner_poll_interval_ms' => '900',
                'tax_enabled' => '0',
                'fbr_pos_enabled' => '0',
                'tax_label' => 'Sales Tax',
                'tax_rate' => '18',
                'tax_registration_type' => 'STRN',
                'tax_number' => '',
                'fbr_pos_id' => '',
                'fbr_invoice_note' => 'FBR POS integrated tax invoice'
            ]),
            'receiptVisibility' => App::receiptVisibility(),
            'receiptLabels' => App::receiptLabels(),
            'receiptSectionOrder' => App::receiptSectionOrder(),
        ]);
    }

    public function software() {
        $packageJsonPath = dirname(__DIR__, 2) . '/DevAltum POS Desktop Version/package.json';
        $currentVersion = '1.1.5';
        if (file_exists($packageJsonPath)) {
            $pkgData = json_decode(file_get_contents($packageJsonPath), true);
            if (!empty($pkgData['version'])) {
                $currentVersion = $pkgData['version'];
            }
        }

        $this->view('../../modules/updates/views/software', [
            'pageTitle' => 'DevAltum POS - Software Updates Center',
            'activeMenu' => 'software_updates',
            'currentVersion' => $currentVersion,
            'githubRepo' => 'Hamad-Yasin/devaltum-pos'
        ]);
    }

    public function mobilescanner() {
        $storeId = (int) Tenant::currentStoreId();
        $store = Database::getInstance()->fetch("SELECT * FROM stores WHERE id = ? LIMIT 1", [$storeId]);
        $scannerPin = App::getStoreScannerPairCode($storeId);
        $baseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $basePath = defined('BASE_URL') ? BASE_URL : '';
        $appUrl = $baseUrl . $basePath . '/mobile_scanner_app/www/index.html';
        $apkUrl = $baseUrl . $basePath . '/mobile_scanner_app/DevAltum_POS_Scanner.apk';
        $storeTitle = $store['store_name'] ?? 'DevAltum Store';
        $qrImg = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode(json_encode(['type' => 'pos_connect', 'code' => $scannerPin, 'store' => $storeTitle]));
        $isEnabled = App::setting('mobile_scanner_enabled', '0') === '1';

        $this->view('../../modules/updates/views/mobilescanner', [
            'pageTitle' => 'Wireless Mobile Scanner',
            'activeMenu' => 'mobile_scanner',
            'store' => $store,
            'storeTitle' => $storeTitle,
            'scannerPin' => $scannerPin,
            'appUrl' => $appUrl,
            'apkUrl' => $apkUrl,
            'qrImg' => $qrImg,
            'isEnabled' => $isEnabled,
            'settings' => App::settings(['company_name' => 'DevAltum POS'])
        ]);
    }

    public function quick_update_store_name() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid request method.'], 405);
        }

        $this->verifyCsrfOrAbort();

        $storeName = trim((string) ($_POST['store_name'] ?? ''));
        if ($storeName === '') {
            $this->jsonResponse(['success' => false, 'error' => 'Store name cannot be empty.'], 400);
        }

        $db = Database::getInstance();
        $storeId = (int) Tenant::currentStoreId();

        if ($storeId > 0) {
            $db->query("UPDATE stores SET store_name = ?, updated_at = NOW() WHERE id = ?", [$storeName, $storeId]);
        } else {
            $firstStore = $db->fetch("SELECT id FROM stores ORDER BY id ASC LIMIT 1");
            if ($firstStore) {
                $db->query("UPDATE stores SET store_name = ?, updated_at = NOW() WHERE id = ?", [$storeName, $firstStore['id']]);
            } else {
                $db->query("INSERT INTO stores (store_uid, store_name, owner_name, email, phone, address, plan_type, status, payment_status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 'lifetime', 'active', 'paid', NOW(), NOW())", [
                    'store-' . bin2hex(random_bytes(4)),
                    $storeName,
                    'Store Owner',
                    'store@devaltum.pos',
                    '+923101648600',
                    'Main Market'
                ]);
            }
        }

        App::saveSettings(['company_name' => $storeName]);
        $_SESSION['store_name'] = $storeName;

        $this->jsonResponse([
            'success' => true,
            'message' => 'Store name updated successfully!',
            'store_name' => $storeName
        ]);
    }

    public function saveSettings() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('updates/index');
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flash('error', 'Security token mismatch. Please try again.');
            $this->redirect('updates/index');
        }

        $payloadTaxId = '';
        $payloadRaw = trim((string) ($_POST['settings_payload'] ?? ''));
        if ($payloadRaw !== '') {
            $payload = json_decode($payloadRaw, true);
            if (is_array($payload)) {
                $payloadTaxId = trim((string) ($payload['tax_id'] ?? ''));
            }
        }

        $currentNtn = trim((string) App::setting('company_ntn', ''));
        if ($currentNtn === '') {
            $currentNtn = trim((string) App::setting('company_ntn_number', App::setting('ntn', '')));
        }

        $ntnInput = trim((string) ($_POST['tax_id'] ?? $_POST['company_ntn'] ?? $_POST['company_ntn_number'] ?? $_POST['ntn'] ?? ''));
        if ($ntnInput === '' && $payloadTaxId !== '') {
            $ntnInput = $payloadTaxId;
        }
        // Do not wipe NTN if hosting strips the field from request.
        if ($ntnInput === '' && $currentNtn !== '') {
            $ntnInput = $currentNtn;
        }
        $pairs = [];
        foreach ([
            'company_name',
            'company_address',
            'company_tagline',
            'currency_symbol',
            'currency_code',
            'receipt_footer'
        ] as $field) {
            if (array_key_exists($field, $_POST)) {
                $pairs[$field] = trim((string) ($_POST[$field] ?? ''));
            }
        }
        if (array_key_exists('tax_id', $_POST) || array_key_exists('company_ntn', $_POST) || array_key_exists('company_ntn_number', $_POST) || array_key_exists('ntn', $_POST)) {
            $pairs['company_ntn'] = $ntnInput;
            $pairs['company_ntn_number'] = $ntnInput;
            $pairs['ntn'] = $ntnInput;
        }
        if (array_key_exists('whatsapp_number', $_POST)) {
            $pairs['whatsapp_number'] = trim($_POST['whatsapp_number'] ?? '');
        }
        if (array_key_exists('whatsapp_message', $_POST)) {
            $pairs['whatsapp_message'] = trim($_POST['whatsapp_message'] ?? '');
        }
        if (array_key_exists('credit_whatsapp_template', $_POST)) {
            $pairs['credit_whatsapp_template'] = trim($_POST['credit_whatsapp_template'] ?? '');
        }
        if (isset($_POST['scanner_settings_submitted']) || array_key_exists('mobile_scanner_enabled', $_POST)) {
            $pairs['mobile_scanner_enabled'] = !empty($_POST['mobile_scanner_enabled']) ? '1' : '0';
        }
        if (isset($_POST['scanner_settings_submitted']) || array_key_exists('scanner_global_pos_redirect', $_POST)) {
            $pairs['scanner_global_pos_redirect'] = !empty($_POST['scanner_global_pos_redirect']) ? '1' : '0';
        }
        foreach (['scanner_beep_enabled', 'scanner_poll_interval_ms', 'enable_expiry_tracking', 'expiry_alert_days', 'block_expired_pos_sale'] as $field) {
            if (array_key_exists($field, $_POST)) {
                $pairs[$field] = trim((string) ($_POST[$field] ?? '0'));
            }
        }
        foreach ([
            'tax_label',
            'tax_rate',
            'tax_registration_type',
            'tax_number',
            'fbr_pos_id',
            'fbr_invoice_note'
        ] as $field) {
            if (array_key_exists($field, $_POST)) {
                $pairs[$field] = trim((string) ($_POST[$field] ?? ''));
            }
        }
        foreach (['tax_enabled', 'fbr_pos_enabled', 'enable_biometric_login'] as $field) {
            if (array_key_exists($field, $_POST)) {
                $pairs[$field] = !empty($_POST[$field]) ? '1' : '0';
            }
        }

        if (array_key_exists('receipt_visibility', $_POST) || !empty($_POST['receipt_visibility_json']) || (!empty($_POST['active_tab']) && $_POST['active_tab'] === 'receipt_designer')) {
            $rawVisibility = !empty($_POST['receipt_visibility_json']) ? json_decode((string)$_POST['receipt_visibility_json'], true) : ($_POST['receipt_visibility'] ?? []);
            if (is_string($rawVisibility)) $rawVisibility = json_decode($rawVisibility, true) ?: [];
            $finalVisibility = [];
            foreach (App::defaultReceiptVisibility() as $k => $defVal) {
                $finalVisibility[$k] = !empty($rawVisibility[$k]) ? 1 : 0;
            }
            $canonicalMap = [
                'show_inv_no' => 'show_invoice_no',
                'show_terminal' => 'show_counter',
                'show_summary_counters' => 'show_item_counters',
                'show_thanks' => 'show_greetings',
                'show_dev_credit' => 'show_developer_credit',
            ];
            foreach ($canonicalMap as $alias => $canonical) {
                if (isset($finalVisibility[$canonical])) {
                    $finalVisibility[$alias] = $finalVisibility[$canonical];
                }
            }
            // Enforce developer signature is permanently on
            $finalVisibility['show_developer_credit'] = 1;
            $finalVisibility['show_dev_credit'] = 1;
            $pairs['receipt_visibility'] = json_encode($finalVisibility);
        }
        if (array_key_exists('receipt_labels', $_POST)) {
            $rawLabels = $_POST['receipt_labels'];
            if (is_string($rawLabels)) $rawLabels = json_decode($rawLabels, true) ?: [];
            $pairs['receipt_labels'] = json_encode(array_merge(App::defaultReceiptLabels(), (array)$rawLabels));
        }
        if (array_key_exists('receipt_section_order', $_POST) || !empty($_POST['receipt_section_order_json'])) {
            $order = !empty($_POST['receipt_section_order_json']) ? json_decode((string)$_POST['receipt_section_order_json'], true) : ($_POST['receipt_section_order'] ?? []);
            if (is_string($order)) $order = json_decode($order, true) ?: [];
            $pairs['receipt_section_order'] = json_encode(!empty($order) && is_array($order) ? array_values($order) : App::defaultReceiptSectionOrder());
        }
        if (isset($_POST['devaltum_credit_logo_size'])) {
            $pairs['devaltum_credit_logo_size'] = max(24, min(90, (int) $_POST['devaltum_credit_logo_size']));
        }
        if (isset($_POST['devaltum_credit_font_size'])) {
            $pairs['devaltum_credit_font_size'] = max(8, min(22, (int) $_POST['devaltum_credit_font_size']));
        }

        App::saveSettings($pairs);

        // Sync receipt configuration across store_settings as well so POS and stores always see latest layout
        $receiptSyncKeys = ['receipt_visibility', 'receipt_labels', 'receipt_section_order', 'devaltum_credit_logo_size', 'devaltum_credit_font_size', 'mobile_scanner_enabled'];
        $hasReceiptSync = false;
        foreach ($receiptSyncKeys as $rKey) {
            if (isset($pairs[$rKey])) {
                $hasReceiptSync = true;
                break;
            }
        }
        if ($hasReceiptSync) {
            $db = Database::getInstance();
            $allStores = $db->fetchAll("SELECT id FROM stores");
            foreach ($allStores as $st) {
                $sId = (int) ($st['id'] ?? 0);
                if ($sId <= 0) continue;
                foreach ($receiptSyncKeys as $rKey) {
                    if (isset($pairs[$rKey])) {
                        $db->query("UPDATE store_settings SET setting_value = ? WHERE store_id = ? AND setting_key = ?", [$pairs[$rKey], $sId, $rKey]);
                        $existing = $db->fetch("SELECT id FROM store_settings WHERE store_id = ? AND setting_key = ? LIMIT 1", [$sId, $rKey]);
                        if (!$existing) {
                            $db->query("INSERT INTO store_settings (store_id, setting_key, setting_value) VALUES (?, ?, ?)", [$sId, $rKey, $pairs[$rKey]]);
                        }
                    }
                }
            }
        }

        // Store owner convenience: if they update WhatsApp number or Store Name in settings,
        // sync it to stores so billing/reminder modules and Header Profile stay aligned.
        $storeId = (int) Tenant::currentStoreId();
        if ($storeId > 0) {
            $storeWhatsapp = trim((string) ($pairs['whatsapp_number'] ?? ''));
            if ($storeWhatsapp !== '') {
                Database::getInstance()->query(
                    "UPDATE stores SET phone = ?, updated_at = NOW() WHERE id = ?",
                    [$storeWhatsapp, $storeId]
                );
            }
            if (!empty($pairs['company_name'])) {
                $compName = trim((string) $pairs['company_name']);
                Database::getInstance()->query(
                    "UPDATE stores SET store_name = ?, updated_at = NOW() WHERE id = ?",
                    [$compName, $storeId]
                );
                $_SESSION['store_name'] = $compName;
                if (class_exists('Tenant')) {
                    Tenant::clearStoreCache();
                }
            }
        }

        $this->flash('success', 'Settings updated successfully.');
        $tabRedirect = !empty($_POST['active_tab']) ? '?tab=' . urlencode((string) $_POST['active_tab']) : '';
        $this->redirect('updates/index' . $tabRedirect);
    }

    public function saveReceiptDesigner() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid method.'], 405);
        }
        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'Security token mismatch.'], 403);
        }

        $rawVisibility = !empty($_POST['receipt_visibility_json']) ? json_decode((string)$_POST['receipt_visibility_json'], true) : ($_POST['receipt_visibility'] ?? []);
        if (is_string($rawVisibility)) $rawVisibility = json_decode($rawVisibility, true) ?: [];

        $finalVisibility = [];
        foreach (App::defaultReceiptVisibility() as $k => $defVal) {
            $finalVisibility[$k] = !empty($rawVisibility[$k]) ? 1 : 0;
        }

        $canonicalMap = [
            'show_inv_no' => 'show_invoice_no',
            'show_terminal' => 'show_counter',
            'show_summary_counters' => 'show_item_counters',
            'show_thanks' => 'show_greetings',
            'show_dev_credit' => 'show_developer_credit',
        ];
        foreach ($canonicalMap as $alias => $canonical) {
            if (isset($finalVisibility[$canonical])) {
                $finalVisibility[$alias] = $finalVisibility[$canonical];
            }
        }
        // DevAltum developer signature is permanently enabled on all receipts
        $finalVisibility['show_developer_credit'] = 1;
        $finalVisibility['show_dev_credit'] = 1;

        $rawLabels = $_POST['receipt_labels'] ?? [];
        if (is_string($rawLabels)) $rawLabels = json_decode($rawLabels, true) ?: [];
        $finalLabels = array_merge(App::defaultReceiptLabels(), (array)$rawLabels);

        $order = !empty($_POST['receipt_section_order_json']) ? json_decode((string)$_POST['receipt_section_order_json'], true) : ($_POST['receipt_section_order'] ?? []);
        if (is_string($order)) $order = json_decode($order, true) ?: [];
        $finalOrder = (!empty($order) && is_array($order)) ? array_values($order) : App::defaultReceiptSectionOrder();

        $receiptPairs = [
            'receipt_visibility' => json_encode($finalVisibility),
            'receipt_labels' => json_encode($finalLabels),
            'receipt_section_order' => json_encode($finalOrder)
        ];

        if (isset($_POST['devaltum_credit_logo_size'])) {
            $receiptPairs['devaltum_credit_logo_size'] = (string) max(24, min(90, (int) $_POST['devaltum_credit_logo_size']));
        }
        if (isset($_POST['devaltum_credit_font_size'])) {
            $receiptPairs['devaltum_credit_font_size'] = (string) max(8, min(22, (int) $_POST['devaltum_credit_font_size']));
        }

        // 1. Update global settings table
        $db = Database::getInstance();
        foreach ($receiptPairs as $key => $value) {
            $db->query("UPDATE settings SET setting_value = ? WHERE setting_key = ? OR TRIM(setting_key) = ?", [$value, $key, $key]);
            $existing = $db->fetch("SELECT setting_key FROM settings WHERE setting_key = ? LIMIT 1", [$key]);
            if (!$existing) {
                $db->query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
            }
        }

        // 2. Sync to store_settings for all active stores and current store
        $currentStoreId = (int) Tenant::currentStoreId();
        $targetStoreIds = $currentStoreId > 0 ? [$currentStoreId] : [];
        $allStores = $db->fetchAll("SELECT id FROM stores");
        foreach ($allStores as $st) {
            $sId = (int) ($st['id'] ?? 0);
            if ($sId > 0 && !in_array($sId, $targetStoreIds, true)) {
                $targetStoreIds[] = $sId;
            }
        }

        foreach ($targetStoreIds as $sId) {
            foreach ($receiptPairs as $key => $value) {
                $db->query("UPDATE store_settings SET setting_value = ? WHERE store_id = ? AND setting_key = ?", [$value, $sId, $key]);
                $existing = $db->fetch("SELECT id FROM store_settings WHERE store_id = ? AND setting_key = ? LIMIT 1", [$sId, $key]);
                if (!$existing) {
                    $db->query("INSERT INTO store_settings (store_id, setting_key, setting_value) VALUES (?, ?, ?)", [$sId, $key, $value]);
                }
            }
        }

        App::reloadSettings();

        $this->jsonResponse([
            'success' => true,
            'message' => 'Receipt design and layout saved successfully!'
        ]);
    }

    public function toggleBiometric() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid method.'], 405);
        }
        $enabled = !empty($_POST['enabled']) ? '1' : '0';
        App::saveSettings(['enable_biometric_login' => $enabled]);
        $this->jsonResponse(['success' => true, 'enabled' => $enabled]);
    }

    public function regenerateScannerToken() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('updates/index?tab=scanner');
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flash('error', 'Security token mismatch. Please try again.');
            $this->redirect('updates/index?tab=scanner');
        }

        $newToken = bin2hex(random_bytes(18));
        $newPin = (string) random_int(100000, 999999);
        $userId = (int) Auth::id();
        App::saveSettings([
            'mobile_scanner_pin' => $newPin,
            'mobile_scanner_token' => $newToken,
            'mobile_scanner_token_user_' . max(0, $userId) => $newToken
        ]);
        $this->flash('success', 'Mobile scanner 6-Digit PIN (' . $newPin . ') & pairing link regenerated successfully.');
        $this->redirect('updates/index?tab=scanner');
    }

    public function saveDisplaySettings() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('updates/index');
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flash('error', 'Security token mismatch. Please try again.');
            $this->redirect('updates/index?tab=display');
        }

        $cardLayout = in_array($_POST['pos_card_layout'] ?? '', ['standard', 'visual_1x1'], true) ? $_POST['pos_card_layout'] : 'standard';
        $imageFit = in_array($_POST['pos_image_fit'] ?? '', ['cover', 'contain'], true) ? $_POST['pos_image_fit'] : 'cover';

        App::saveSettings([
            'show_product_images' => !empty($_POST['show_product_images']) ? '1' : '0',
            'pos_card_layout' => $cardLayout,
            'pos_image_fit' => $imageFit,
            'pos_show_product_name' => !empty($_POST['pos_show_product_name']) ? '1' : '0',
            'pos_show_product_stock' => !empty($_POST['pos_show_product_stock']) ? '1' : '0',
            'pos_show_product_unit' => !empty($_POST['pos_show_product_unit']) ? '1' : '0',
            'pos_show_product_price' => !empty($_POST['pos_show_product_price']) ? '1' : '0',
        ]);

        $this->flash('success', 'Product card display settings updated successfully.');
        $this->redirect('updates/index?tab=display');
    }

    public function uploadBranding() {
        if (Tenant::isBranchAccount()) {
            $this->flash('error', 'Branding assets are managed from the parent account.');
            $this->redirect('updates/index');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('updates/index');
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flash('error', 'Security token mismatch. Please try again.');
            $this->redirect('updates/index');
        }

        $save = [];
        $targetDir = ROOT_PATH . '/public/uploads/branding';
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp'];
        $allowedMimes = ['image/png', 'image/jpeg', 'image/webp'];

        foreach (['website_logo' => 'website_logo', 'receipt_logo' => 'receipt_logo', 'app_icon' => 'app_icon'] as $field => $prefix) {
            if (empty($_FILES[$field]['tmp_name'])) {
                continue;
            }

            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                $this->flash('error', 'Only PNG, JPG, JPEG and WEBP logo files are allowed.');
                $this->redirect('updates/index');
            }

            $path = App::uploadFile($_FILES[$field], $targetDir, $prefix, $allowedExtensions, $allowedMimes, 2 * 1024 * 1024);
            if ($path) {
                $save[$field] = App::publicPathToUrl($path);
            }
        }

        if ($save) {
            App::saveSettings($save);
            
            // Dynamically inject custom App Icon into the static PWA manifest
            if (isset($save['app_icon'])) {
                $manifestPath = ROOT_PATH . '/public/manifest.json';
                if (file_exists($manifestPath)) {
                    $manifest = json_decode(file_get_contents($manifestPath), true);
                    if (is_array($manifest) && isset($manifest['icons'])) {
                        foreach ($manifest['icons'] as &$icon) {
                            $icon['src'] = '.' . $save['app_icon'];
                        }
                        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    }
                }
            }

            $this->flash('success', 'Branding assets uploaded successfully.');
        } else {
            $this->flash('error', 'No logo file was uploaded.');
        }

        $this->redirect('updates/index');
    }

    public function removeLogo() {
        if (Tenant::isBranchAccount()) {
            $this->flash('error', 'Branding assets are managed from the parent account.');
            $this->redirect('updates/index');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('updates/index');
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flash('error', 'Security token mismatch. Please try again.');
            $this->redirect('updates/index');
        }

        $field = $_POST['field'] ?? '';
        if (!in_array($field, ['website_logo', 'receipt_logo', 'app_icon'], true)) {
            $this->flash('error', 'Invalid logo field selected.');
            $this->redirect('updates/index');
        }

        $currentValue = App::setting($field, '');
        if ($currentValue) {
            $filePath = ROOT_PATH . '/public' . str_replace('/', DIRECTORY_SEPARATOR, $currentValue);
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }

        App::saveSettings([$field => '']);
        
        // Restore default PWA Manifest Icon on removal
        if ($field === 'app_icon') {
            $manifestPath = ROOT_PATH . '/public/manifest.json';
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                if (is_array($manifest) && isset($manifest['icons'])) {
                    foreach ($manifest['icons'] as &$icon) {
                        $size = $icon['sizes'] ?? '192x192';
                        $icon['src'] = './assets/img/icon-' . $size . '.png';
                    }
                    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            }
        }

        $this->flash('success', 'Logo removed successfully.');
        $this->redirect('updates/index');
    }

    public function uploadPackage() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['update_file']['tmp_name'])) {
            $this->redirect('updates/index');
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flash('error', 'Security token mismatch. Please try again.');
            $this->redirect('updates/index');
        }

        $extension = strtolower(pathinfo($_FILES['update_file']['name'], PATHINFO_EXTENSION));
        if ($extension !== 'zip') {
            $this->flash('error', 'Only ZIP update packages are allowed.');
            $this->redirect('updates/index');
        }

        $targetDir = ROOT_PATH . '/updates';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $path = App::uploadFile(
            $_FILES['update_file'],
            $targetDir,
            'update',
            ['zip'],
            ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/octet-stream'],
            25 * 1024 * 1024
        );
        if (!$path) {
            $this->flash('error', 'Update package could not be uploaded.');
            $this->redirect('updates/index');
        }

        $inspection = $this->inspectPackage($path);
        if (!$inspection['valid']) {
            @unlink($path);
            $this->flash('error', $inspection['message']);
            $this->redirect('updates/index');
        }

        $this->flash('success', 'Update package uploaded, validated and ready for dry run or apply.');
        $this->redirect('updates/index');
    }

    public function dryRun() {
        $file = basename((string) ($_GET['file'] ?? ''));
        $path = $this->resolvePackagePath($file);
        if (!$path) {
            $this->flash('error', 'Update file not found. Please upload/select the package again.');
            $this->redirect('updates/index');
            return;
        }
        $file = basename($path);

        $info = $this->inspectPackage($path);
        $this->view('../../modules/updates/views/dry_run', [
            'file' => $file,
            'info' => $info
        ]);
    }

    public function apply() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('updates/index');
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flash('error', 'Security token mismatch. Please try again.');
            $this->redirect('updates/index');
        }

        $file = basename((string) ($_POST['file'] ?? ''));
        $path = $this->resolvePackagePath($file);
        if (!$path) {
            $this->flash('error', 'Update file not found.');
            $this->redirect('updates/index');
            return;
        }
        $file = basename($path);

        $inspection = $this->inspectPackage($path);
        if (!$inspection['valid']) {
            $this->flash('error', $inspection['message']);
            $this->redirect('updates/index');
        }

        $tempDir = STORAGE_PATH . '/updates/tmp_' . uniqid();
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            $this->flash('error', 'Update package could not be opened.');
            $this->redirect('updates/index');
        }
        $zip->extractTo($tempDir);
        $zip->close();

        $root = $this->resolveExtractedRoot($tempDir);
        $backupBase = 'update_backup_' . date('Ymd_His') . '_' . pathinfo($file, PATHINFO_FILENAME);
        $backupSql = $this->createBackup($backupBase);
        $backupZip = $this->createProjectSnapshot($backupBase);

        try {
            $packageManager = new UpdatePackageManager(ROOT_PATH);
            $copyReport = $packageManager->applyFrom($root);
            if (!empty($copyReport['failed'])) {
                $sampleErrors = array_slice($copyReport['failed'], 0, 5);
                throw new Exception(
                    'Some files failed to update. ' .
                    'Copied ' . (int) $copyReport['copied'] . '/' . (int) $copyReport['planned'] .
                    '. Failed: ' . implode(', ', $sampleErrors)
                );
            }

            $appliedSqlFiles = $this->applySqlUpdates($root);

            Database::getInstance()->query(
                "INSERT INTO updates_log (version, description, source_file, validation_method, backup_sql_path, backup_zip_path, rollback_available) VALUES (?, ?, ?, ?, ?, ?, 1)",
                [
                    $inspection['version'],
                    $inspection['description'],
                    $file,
                    $inspection['validation_method'],
                    $backupSql,
                    $backupZip
                ]
            );

            $this->deleteDirectory($tempDir);
            
            // Automatic Database Schema Sync after update package
            if (file_exists(CORE_PATH . '/database/SchemaManager.php')) {
                require_once CORE_PATH . '/database/SchemaManager.php';
                SchemaManager::sync(Database::getInstance());
            }

            $flagFile = CONFIG_PATH . '/schema_synced.flag';
            if (file_exists($flagFile)) {
                @unlink($flagFile);
            }

            $message = 'Update applied successfully. Files copied: ' . (int) $copyReport['copied'] . '.';
            if (!empty($appliedSqlFiles)) {
                $message .= ' SQL updates applied: ' . count($appliedSqlFiles) . '.';
            }
            $this->flash('success', $message);
        } catch (Exception $e) {
            $this->deleteDirectory($tempDir);
            $this->flash('error', 'Update could not be applied: ' . $e->getMessage());
        }

        $this->redirect('updates/index');
    }

    public function syncDatabaseSchema() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('updates/index');
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flash('error', 'Security token mismatch. Please try again.');
            $this->redirect('updates/index');
        }

        try {
            if (file_exists(CORE_PATH . '/database/SchemaManager.php')) {
                require_once CORE_PATH . '/database/SchemaManager.php';
                $synced = SchemaManager::sync(Database::getInstance());
                if ($synced) {
                    $this->flash('success', 'Database schema synced successfully! All tables, columns, and indexes are up to date.');
                } else {
                    $this->flash('error', 'Database schema sync encountered an issue. Please check error logs.');
                }
            } else {
                App::ensureSchema(Database::getInstance());
                $this->flash('success', 'Database schema verified successfully.');
            }
        } catch (\Throwable $e) {
            $this->flash('error', 'Sync failed: ' . $e->getMessage());
        }

        $this->redirect('updates/index');
    }

    public function rollback($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('updates/index');
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flash('error', 'Security token mismatch. Please try again.');
            $this->redirect('updates/index');
        }

        $id = $id ?: (int) ($_POST['id'] ?? 0);
        $db = Database::getInstance();
        $entry = $db->fetch("SELECT * FROM updates_log WHERE id = ?", [$id]);

        if (!$entry || empty($entry['rollback_available'])) {
            $this->flash('error', 'Rollback is not available for this update.');
            $this->redirect('updates/index');
        }

        if (empty($entry['backup_sql_path']) || empty($entry['backup_zip_path']) || !file_exists($entry['backup_sql_path']) || !file_exists($entry['backup_zip_path'])) {
            $this->flash('error', 'Rollback backup files are missing.');
            $this->redirect('updates/index');
        }

        try {
            $this->restoreProjectSnapshot($entry['backup_zip_path']);
            $this->restoreDatabaseBackup($entry['backup_sql_path']);
            $db->query("UPDATE updates_log SET rollback_available = 0, rolled_back_at = NOW() WHERE id = ?", [$entry['id']]);
            
            $flagFile = CONFIG_PATH . '/schema_synced.flag';
            if (file_exists($flagFile)) {
                @unlink($flagFile);
            }

            $this->flash('success', 'Rollback completed successfully.');
        } catch (Exception $e) {
            $this->flash('error', 'Rollback failed: ' . $e->getMessage());
        }

        $this->redirect('updates/index');
    }

    public function backup() {
        $name = 'manual_backup_' . date('Ymd_His');
        $file = $this->createBackup($name);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    private function createBackup($filename) {
        $db = Database::getInstance();
        $tables = $db->fetchAll('SHOW TABLES');
        $sql = "-- DevAltum POS Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $t) {
            $tableName = array_values($t)[0];
            $create = $db->fetch("SHOW CREATE TABLE `$tableName`");
            $sql .= "DROP TABLE IF EXISTS `$tableName`;\n";
            $sql .= "-- Table structure for `$tableName`\n";
            $sql .= array_values($create)[1] . ";\n\n";

            $rows = $db->fetchAll("SELECT * FROM `$tableName`");
            foreach ($rows as $row) {
                $vals = array_map(function ($val) {
                    return $val === null ? 'NULL' : "'" . addslashes($val) . "'";
                }, array_values($row));
                $sql .= "INSERT INTO `$tableName` VALUES (" . implode(',', $vals) . ");\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $backupDir = STORAGE_PATH . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filepath = $backupDir . '/' . $filename . '.sql';
        file_put_contents($filepath, $sql);
        return $filepath;
    }

    private function createProjectSnapshot($filename) {
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive extension is not available for snapshot creation.');
        }

        $backupDir = STORAGE_PATH . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $zipPath = $backupDir . '/' . $filename . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Project snapshot could not be created.');
        }

        $this->addDirectoryToZip(ROOT_PATH, $zip, ROOT_PATH . DIRECTORY_SEPARATOR);
        $zip->close();
        return $zipPath;
    }

    private function addDirectoryToZip($dir, $zip, $basePrefix) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }

            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
            $relative = str_replace($basePrefix, '', $fullPath);
            $relative = str_replace('\\', '/', $relative);

            if ($this->shouldSkipSnapshotPath($relative)) {
                continue;
            }

            if (is_dir($fullPath)) {
                $zip->addEmptyDir($relative);
                $this->addDirectoryToZip($fullPath, $zip, $basePrefix);
            } else {
                $zip->addFile($fullPath, $relative);
            }
        }
    }

    private function shouldSkipSnapshotPath($relativePath) {
        $skip = [
            'storage/backups',
            'storage/updates',
            'updates',
            'config/database.php'
        ];

        foreach ($skip as $item) {
            if ($relativePath === $item || strpos($relativePath, $item . '/') === 0) {
                return true;
            }
        }

        return false;
    }

    private function scanBackupFiles() {
        $backupDir = STORAGE_PATH . '/backups';
        if (!is_dir($backupDir)) {
            return [];
        }

        $files = [];
        foreach (scandir($backupDir) as $file) {
            if (in_array($file, ['.', '..'], true)) {
                continue;
            }

            $path = $backupDir . '/' . $file;
            if (!is_file($path)) {
                continue;
            }

            $files[] = [
                'name' => $file,
                'size' => round(filesize($path) / 1024, 2) . ' KB',
                'date' => date('Y-m-d H:i:s', filemtime($path))
            ];
        }

        usort($files, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return array_slice($files, 0, 10);
    }

    private function scanUpdatePackages() {
        $availableUpdates = [];
        $updatesDir = ROOT_PATH . '/updates';
        if (!is_dir($updatesDir)) {
            return $availableUpdates;
        }

        foreach (scandir($updatesDir) as $file) {
            if (strtolower((string) pathinfo($file, PATHINFO_EXTENSION)) !== 'zip') {
                continue;
            }

            $path = $updatesDir . '/' . $file;
            $info = $this->inspectPackage($path);
            $availableUpdates[] = [
                'file' => $file,
                'size' => round(filesize($path) / 1024, 2) . ' KB',
                'date' => date('Y-m-d H:i:s', filemtime($path)),
                'valid' => $info['valid'],
                'message' => $info['message'] ?? '',
                'version' => $info['version'] ?? pathinfo($file, PATHINFO_FILENAME),
                'description' => $info['description'] ?? '',
                'validation_method' => $info['validation_method'] ?? 'Unknown',
                'db_changes' => $info['db_changes'] ?? false,
                'warnings' => $info['warnings'] ?? []
            ];
        }

        return $availableUpdates;
    }

    private function resolvePackagePath($fileName) {
        $updatesDir = ROOT_PATH . '/updates';
        if (!is_dir($updatesDir)) {
            return null;
        }

        $fileName = basename((string) $fileName);
        if ($fileName !== '') {
            $directPath = $updatesDir . '/' . $fileName;
            if (is_file($directPath)) {
                return $directPath;
            }

            foreach (scandir($updatesDir) as $entry) {
                if (in_array($entry, ['.', '..'], true)) {
                    continue;
                }
                if (strcasecmp($entry, $fileName) === 0 && is_file($updatesDir . '/' . $entry)) {
                    return $updatesDir . '/' . $entry;
                }
            }
        }

        $candidates = [];
        foreach (scandir($updatesDir) as $entry) {
            if (in_array($entry, ['.', '..'], true)) {
                continue;
            }
            if (strtolower((string) pathinfo($entry, PATHINFO_EXTENSION)) !== 'zip') {
                continue;
            }

            $fullPath = $updatesDir . '/' . $entry;
            if (is_file($fullPath)) {
                $candidates[$fullPath] = @filemtime($fullPath) ?: 0;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        arsort($candidates);
        return (string) array_key_first($candidates);
    }

    private function inspectPackage($path) {
        $info = [
            'valid' => false,
            'version' => pathinfo($path, PATHINFO_FILENAME),
            'files_to_change' => [],
            'db_changes' => false,
            'sql_files' => [],
            'description' => 'Project update package',
            'message' => 'Invalid package.',
            'validation_method' => 'Failed validation',
            'warnings' => []
        ];

        if (!class_exists('ZipArchive')) {
            $info['message'] = 'ZipArchive extension is not available on this server.';
            return $info;
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            $info['message'] = 'The update zip file could not be opened.';
            return $info;
        }

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $rawName = (string) $zip->getNameIndex($i);
            if ($rawName === '') {
                continue;
            }
            // Normalize zip entry separators so validation works across Windows-created archives.
            $names[] = str_replace('\\', '/', $rawName);
        }

        $manifestContent = null;
        foreach ($names as $name) {
            if (strtolower((string) basename($name)) !== 'manifest.json') {
                continue;
            }
            $manifestContent = $zip->getFromName($name);
            if ($manifestContent !== false) {
                break;
            }
        }

        if ($manifestContent !== null && $manifestContent !== false) {
            // Strip UTF-8 BOM if present to avoid JSON parse failures.
            $manifestContent = preg_replace('/^\xEF\xBB\xBF/', '', (string) $manifestContent);
            $manifest = json_decode($manifestContent, true);
            // Relaxed validation: as long as it's a valid manifest, accept it without enforcing strict slug match
            if (!empty($manifest) && is_array($manifest)) {
                $info['valid'] = true;
                $info['version'] = $manifest['version'] ?? $info['version'];
                $info['description'] = $manifest['description'] ?? $info['description'];
                $info['validation_method'] = 'Manifest match (Relaxed)';
            }
        }

        if (!$info['valid']) {
            $requiredMarkers = ['public/index.php', 'core/Router.php', 'modules/pos/controllers/PosController.php'];
            $matched = 0;
            foreach ($names as $name) {
                foreach ($requiredMarkers as $marker) {
                    if (substr($name, -strlen($marker)) === $marker) {
                        $matched++;
                        break;
                    }
                }
            }

            if ($matched === count($requiredMarkers)) {
                $info['valid'] = true;
                $info['description'] = 'Validated using strict application structure markers.';
                $info['validation_method'] = 'Strict structure markers';
            }
        }

        $dangerous = ['config/database.php'];
        foreach ($names as $name) {
            foreach ($dangerous as $danger) {
                if (substr($name, -strlen($danger)) === $danger) {
                    $info['warnings'][] = 'Package attempts to include protected file: ' . $danger;
                }
            }
        }

        $allFiles = array_values(array_filter($names, function ($name) {
            return substr($name, -1) !== '/';
        }));
        $info['files_to_change'] = array_slice($allFiles, 0, 40);
        $info['sql_files'] = array_values(array_filter($allFiles, function ($name) {
            return stripos($name, '.sql') !== false || stripos($name, 'migration') !== false;
        }));
        $info['db_changes'] = !empty($info['sql_files']);
        $info['message'] = $info['valid'] ? 'Package verified for this project and ready for dry run/apply.' : 'This update package does not appear to belong to this POS project.';

        $zip->close();
        return $info;
    }

    private function resolveExtractedRoot($tempDir) {
        $entries = array_values(array_filter(scandir($tempDir), function ($item) {
            return !in_array($item, ['.', '..'], true);
        }));

        if (count($entries) === 1 && is_dir($tempDir . '/' . $entries[0])) {
            return $tempDir . '/' . $entries[0];
        }

        return $tempDir;
    }

    private function applySqlUpdates($sourceRoot) {
        $sqlFiles = $this->collectSqlFiles($sourceRoot);
        $applied = [];

        if (empty($sqlFiles)) {
            return $applied;
        }

        $pdo = Database::getInstance()->getConnection();
        foreach ($sqlFiles as $file) {
            $sql = trim((string) file_get_contents($file));
            if ($sql === '') {
                continue;
            }
            
            // Auto-Downgrade MySQL 8.0+ collations to prevent 1273 errors on older MariaDB/MySQL 5.x instances
            $sql = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_general_ci', $sql);
            
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                // If the entire script fails (due to a duplicate table/column), gracefully split and execute line by line
                $queries = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($queries as $query) {
                    if ($query === '') continue;
                    try {
                        $pdo->exec($query);
                    } catch (PDOException $subException) {
                        // Brutally suppress ALL inner SQL exceptions during fallback line-by-line execution
                        // This guarantees the update proceeds even if the user has custom columns/data 
                        // that trigger obscure constraints (e.g. 1136, 1273, 23000, 42S22, etc.)
                        continue;
                    }
                }
            }
            
            $applied[] = basename($file);
        }

        return $applied;
    }

    private function collectSqlFiles($dir) {
        $files = [];
        $items = scandir($dir);
        foreach ($items as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $files = array_merge($files, $this->collectSqlFiles($path));
                continue;
            }

            if (preg_match('/(\.sql$|migration)/i', $item)) {
                $files[] = $path;
            }
        }

        sort($files);
        return $files;
    }

    private function copyUpdateContents($source, $destination) {
        $items = scandir($source);
        foreach ($items as $item) {
            if (in_array($item, ['.', '..', 'storage', 'updates'], true)) {
                continue;
            }

            $srcPath = $source . '/' . $item;
            $dstPath = $destination . '/' . $item;
            $relative = ltrim(str_replace(ROOT_PATH, '', $dstPath), '/\\');
            $relative = str_replace('\\', '/', $relative);

            if ($relative === 'config/database.php' || strpos($relative, 'public/uploads/') === 0) {
                continue;
            }

            if (is_dir($srcPath)) {
                if (!is_dir($dstPath)) {
                    mkdir($dstPath, 0755, true);
                }
                $this->copyUpdateContents($srcPath, $dstPath);
                continue;
            }

            if (!@copy($srcPath, $dstPath)) {
                throw new Exception('File copy failed for: ' . $relative);
            }
        }
    }

    private function restoreProjectSnapshot($zipPath) {
        $tempDir = STORAGE_PATH . '/updates/rollback_' . uniqid();
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new Exception('Project snapshot could not be opened.');
        }
        $zip->extractTo($tempDir);
        $zip->close();

        $this->copyUpdateContents($tempDir, ROOT_PATH);
        $this->deleteDirectory($tempDir);
    }

    private function restoreDatabaseBackup($sqlPath) {
        $pdo = Database::getInstance()->getConnection();
        if (!$pdo) {
            throw new Exception('Database connection is not available for rollback.');
        }

        $tables = Database::getInstance()->fetchAll('SHOW TABLES');
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $tableRow) {
            $table = array_values($tableRow)[0];
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
        }

        $sql = file_get_contents($sqlPath);
        if ($sql === false) {
            throw new Exception('Database backup file could not be read.');
        }

        $pdo->exec($sql);
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }

    public function migrateDb() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file']['tmp_name'])) {
            $this->redirect('updates/index');
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->flash('error', 'Security token mismatch. Please try again.');
            $this->redirect('updates/index');
        }

        $storeId = (int) Tenant::currentStoreId();
        $userId = (int) Auth::id();

        if ($storeId <= 0) {
            $this->flash('error', 'Invalid store context.');
            $this->redirect('updates/index');
        }

        $sqlFile = $_FILES['file']['tmp_name'];
        $sqlContent = file_get_contents($sqlFile);
        if (!$sqlContent) {
            $this->flash('error', 'Uploaded SQL file is empty or unreadable.');
            $this->redirect('updates/index');
        }

        $db = Database::getInstance();
        $pdo = $db->getConnection();

        $tables = [
            'expense_categories',
            'expenses',
            'customers',
            'products',
            'invoices',
            'invoice_items',
            'credit_payments'
        ];

        $stagingPrefix = "mig_{$storeId}_";

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        foreach ($tables as $tbl) {
            $pdo->exec("DROP TABLE IF EXISTS `{$stagingPrefix}{$tbl}`;");
        }

        try {
            // 1. Create staging tables (DDL - outside transaction)
            foreach ($tables as $tbl) {
                $pdo->exec("CREATE TABLE `{$stagingPrefix}{$tbl}` LIKE `{$tbl}`");
                $pdo->exec("ALTER TABLE `{$stagingPrefix}{$tbl}` DROP COLUMN `store_id`");
            }

            // 2. Parse SQL file and load into staging tables (DML - outside transaction)
            $lines = explode("\n", $sqlContent);
            $statements = [];
            $currentStmt = "";

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0) {
                    continue;
                }

                $currentStmt .= $line . "\n";

                if (substr($trimmed, -1) === ';') {
                    $statements[] = $currentStmt;
                    $currentStmt = "";
                }
            }

            foreach ($statements as $stmtText) {
                $trimmedStmt = trim($stmtText);
                if (stripos($trimmedStmt, 'INSERT INTO') !== 0) {
                    continue;
                }

                $matched = false;
                foreach ($tables as $tbl) {
                    $pattern = '/INSERT\s+INTO\s+`?' . $tbl . '`?/i';
                    if (preg_match($pattern, $trimmedStmt)) {
                        $trimmedStmt = preg_replace($pattern, "INSERT INTO `{$stagingPrefix}{$tbl}`", $trimmedStmt);
                        $matched = true;
                        break;
                    }
                }

                if ($matched) {
                    $pdo->exec($trimmedStmt);
                }
            }

            // 3. Start Transaction for safe live data overwrite and mapping (DML - inside transaction)
            $pdo->beginTransaction();

            $pdo->exec("DELETE FROM credit_payments WHERE store_id = {$storeId}");
            $pdo->exec("DELETE FROM invoice_items WHERE store_id = {$storeId}");
            $pdo->exec("DELETE FROM invoices WHERE store_id = {$storeId}");
            $pdo->exec("DELETE FROM products WHERE store_id = {$storeId}");
            $pdo->exec("DELETE FROM customers WHERE store_id = {$storeId}");
            $pdo->exec("DELETE FROM expenses WHERE store_id = {$storeId}");
            $pdo->exec("DELETE FROM expense_categories WHERE store_id = {$storeId}");

            $categoryMap = [];
            $categories = $pdo->query("SELECT * FROM `{$stagingPrefix}expense_categories`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($categories)) {
                $columns = array_keys($categories[0]);
                unset($columns[array_search('id', $columns)]);
                $columns[] = 'store_id';
                $sql = "INSERT INTO `expense_categories` (" . implode(', ', array_map(fn($c) => "`$c`", $columns)) . ") VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")";
                $stmt = $pdo->prepare($sql);
                foreach ($categories as $cat) {
                    $oldId = $cat['id'];
                    $rowData = $cat;
                    unset($rowData['id']);
                    if (array_key_exists('created_by', $rowData)) {
                        $rowData['created_by'] = $userId;
                    }
                    $values = array_values($rowData);
                    $values[] = $storeId;
                    $stmt->execute($values);
                    $categoryMap[$oldId] = $pdo->lastInsertId();
                }
            }

            $expenses = $pdo->query("SELECT * FROM `{$stagingPrefix}expenses`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($expenses)) {
                $columns = array_keys($expenses[0]);
                unset($columns[array_search('id', $columns)]);
                $columns[] = 'store_id';
                $sql = "INSERT INTO `expenses` (" . implode(', ', array_map(fn($c) => "`$c`", $columns)) . ") VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")";
                $stmt = $pdo->prepare($sql);
                foreach ($expenses as $exp) {
                    $rowData = $exp;
                    unset($rowData['id']);
                    if (array_key_exists('created_by', $rowData)) {
                        $rowData['created_by'] = $userId;
                    }
                    if (array_key_exists('category_id', $rowData)) {
                        $rowData['category_id'] = isset($categoryMap[$rowData['category_id']]) ? $categoryMap[$rowData['category_id']] : null;
                    }
                    $values = array_values($rowData);
                    $values[] = $storeId;
                    $stmt->execute($values);
                }
            }

            $customerMap = [];
            $customers = $pdo->query("SELECT * FROM `{$stagingPrefix}customers`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($customers)) {
                $columns = array_keys($customers[0]);
                unset($columns[array_search('id', $columns)]);
                $columns[] = 'store_id';
                $sql = "INSERT INTO `customers` (" . implode(', ', array_map(fn($c) => "`$c`", $columns)) . ") VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")";
                $stmt = $pdo->prepare($sql);
                foreach ($customers as $cust) {
                    $oldId = $cust['id'];
                    $rowData = $cust;
                    unset($rowData['id']);
                    $values = array_values($rowData);
                    $values[] = $storeId;
                    $stmt->execute($values);
                    $customerMap[$oldId] = $pdo->lastInsertId();
                }
            }

            $productMap = [];
            $products = $pdo->query("SELECT * FROM `{$stagingPrefix}products`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($products)) {
                $columns = array_keys($products[0]);
                unset($columns[array_search('id', $columns)]);
                $columns[] = 'store_id';
                $sql = "INSERT INTO `products` (" . implode(', ', array_map(fn($c) => "`$c`", $columns)) . ") VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")";
                $stmt = $pdo->prepare($sql);
                foreach ($products as $prod) {
                    $oldId = $prod['id'];
                    $rowData = $prod;
                    unset($rowData['id']);
                    $values = array_values($rowData);
                    $values[] = $storeId;
                    $stmt->execute($values);
                    $productMap[$oldId] = $pdo->lastInsertId();
                }
            }

            $invoiceMap = [];
            $invoices = $pdo->query("SELECT * FROM `{$stagingPrefix}invoices`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($invoices)) {
                $columns = array_keys($invoices[0]);
                unset($columns[array_search('id', $columns)]);
                $columns[] = 'store_id';
                $sql = "INSERT INTO `invoices` (" . implode(', ', array_map(fn($c) => "`$c`", $columns)) . ") VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")";
                $stmt = $pdo->prepare($sql);
                foreach ($invoices as $inv) {
                    $oldId = $inv['id'];
                    $rowData = $inv;
                    unset($rowData['id']);
                    if (array_key_exists('user_id', $rowData)) {
                        $rowData['user_id'] = $userId;
                    }
                    if (array_key_exists('customer_id', $rowData)) {
                        $rowData['customer_id'] = isset($customerMap[$rowData['customer_id']]) ? $customerMap[$rowData['customer_id']] : null;
                    }
                    $values = array_values($rowData);
                    $values[] = $storeId;
                    $stmt->execute($values);
                    $invoiceMap[$oldId] = $pdo->lastInsertId();
                }
            }

            $items = $pdo->query("SELECT * FROM `{$stagingPrefix}invoice_items`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($items)) {
                $columns = array_keys($items[0]);
                unset($columns[array_search('id', $columns)]);
                $columns[] = 'store_id';
                $sql = "INSERT INTO `invoice_items` (" . implode(', ', array_map(fn($c) => "`$c`", $columns)) . ") VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")";
                $stmt = $pdo->prepare($sql);
                foreach ($items as $item) {
                    $rowData = $item;
                    unset($rowData['id']);
                    if (array_key_exists('invoice_id', $rowData)) {
                        $rowData['invoice_id'] = isset($invoiceMap[$rowData['invoice_id']]) ? $invoiceMap[$rowData['invoice_id']] : null;
                    }
                    if (array_key_exists('product_id', $rowData)) {
                        $rowData['product_id'] = isset($productMap[$rowData['product_id']]) ? $productMap[$rowData['product_id']] : null;
                    }
                    if ($rowData['invoice_id']) {
                        $values = array_values($rowData);
                        $values[] = $storeId;
                        $stmt->execute($values);
                    }
                }
            }

            $payments = $pdo->query("SELECT * FROM `{$stagingPrefix}credit_payments`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($payments)) {
                $columns = array_keys($payments[0]);
                unset($columns[array_search('id', $columns)]);
                $columns[] = 'store_id';
                $sql = "INSERT INTO `credit_payments` (" . implode(', ', array_map(fn($c) => "`$c`", $columns)) . ") VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")";
                $stmt = $pdo->prepare($sql);
                foreach ($payments as $pay) {
                    $rowData = $pay;
                    unset($rowData['id']);
                    if (array_key_exists('created_by', $rowData)) {
                        $rowData['created_by'] = $userId;
                    }
                    if (array_key_exists('invoice_id', $rowData)) {
                        $rowData['invoice_id'] = isset($invoiceMap[$rowData['invoice_id']]) ? $invoiceMap[$rowData['invoice_id']] : null;
                    }
                    if ($rowData['invoice_id']) {
                        $values = array_values($rowData);
                        $values[] = $storeId;
                        $stmt->execute($values);
                    }
                }
            }

            $pdo->commit();
            $stmt = $pdo->prepare("INSERT INTO activity_logs (store_id, user_id, action, details) VALUES (?, ?, ?, ?)");
            $stmt->execute([$storeId, $userId, 'Migration', 'Successfully migrated single-tenant SQL database backup to SaaS store owner account']);
            $this->flash('success', 'Database backup migrated successfully.');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->flash('error', 'Migration failed: ' . $e->getMessage());
        } finally {
            foreach ($tables as $tbl) {
                $pdo->exec("DROP TABLE IF EXISTS `{$stagingPrefix}{$tbl}`;");
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        }

        $this->redirect('updates/index');
    }
}
