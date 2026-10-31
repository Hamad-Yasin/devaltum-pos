<?php
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($isAjax) {
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json');
    $payload = json_encode([
        'html' => $content,
        'extraJs' => $extraJs ?? '',
        'pageTitle' => $pageTitle ?? 'DevAltum POS',
        'activeMenu' => $activeMenu ?? '',
        'csrfToken' => Security::generateCsrfToken(),
        'flash' => App::pullFlash()
    ], JSON_INVALID_UTF8_SUBSTITUTE);

    if ($payload === false) {
        $payload = json_encode([
            'error' => true,
            'message' => 'JSON Encoding Error: ' . json_last_error_msg()
        ]);
    }
    echo $payload;
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    exit;
}

$settings = App::settings([
    'company_name' => 'DevAltum POS',
    'company_tagline' => 'Official point of sale system',
    'website_logo' => '',
    'receipt_logo' => '',
    'currency_symbol' => 'Rs',
    'whatsapp_number' => '+923101648600',
    'whatsapp_message' => 'I am coming from DevAltum POS'
]);
$flash = App::pullFlash();
$lowStockItems = App::lowStockProducts(8);
$dueCreditItems = App::dueCreditNotifications(8);
$latestNotifications = App::latestNotifications(5);
$newNotificationsCount = App::unreadCreditNotificationsCount();
$user = Auth::user();
$canAccess = function ($permission) {
    return Auth::isAdmin() || Auth::can($permission);
};
$isBranchAccount = Tenant::isBranchAccount();
$isStoreExpired = Tenant::isStoreExpired();
$developerWaNumber = '923101648600';
$developerWaMessage = urlencode('I am coming from DevAltum POS');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="DevAltum POS">
    <link rel="manifest" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/img/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/img/icon-192x192.png">
    <link rel="shortcut icon" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/favicon.ico">
    
    <title><?php echo $pageTitle ?? $settings['company_name']; ?></title>
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/fonts/fonts.css?v=<?= @filemtime(ROOT_PATH . '/public/assets/fonts/fonts.css') ?: time() ?>">
    <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/css/style.css?v=<?= @filemtime(ROOT_PATH . '/public/assets/css/style.css') ?: time() ?>">
    <script>
        window.BASE_URL = '<?= defined('BASE_URL') ? BASE_URL : '' ?>';
        window.baseUrl = window.BASE_URL;
    </script>
    <style>
        .custom-splash {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #020617; z-index: 999999;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: #fff; opacity: 1; transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        .custom-splash.hide { opacity: 0; visibility: hidden; pointer-events: none; }
        .custom-splash-brand { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .custom-splash-brand img { height: 90px; width: auto; border-radius: 20px; margin-bottom: 20px; animation: splashPulse 2s infinite ease-in-out; }
        .custom-splash-footer { padding-bottom: 40px; font-size: 13px; color: #64748b; font-weight: 600; letter-spacing: 0.5px; }
        html.splash-handled .custom-splash { display: none !important; }
        @keyframes splashPulse { 0% { transform: scale(0.95); opacity: 0.8; } 50% { transform: scale(1.05); opacity: 1; } 100% { transform: scale(0.95); opacity: 0.8; } }

        /* Scrollable Sidebar with Clean Custom Scrollbar */
        .sidebar {
            position: fixed !important;
            top: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            height: 100vh !important;
            max-height: 100vh !important;
            display: flex !important;
            flex-direction: column !important;
            box-sizing: border-box !important;
            z-index: 1000;
        }

        .sidebar-brand,
        .mobile-sidebar-profile,
        .sidebar-footer {
            flex-shrink: 0 !important;
        }

        .sidebar-nav {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            padding: 14px 6px !important;
            scrollbar-width: thin !important;
            scrollbar-color: rgba(255, 255, 255, 0.22) transparent !important;
            -webkit-overflow-scrolling: touch !important;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 5px !important;
            display: block !important;
        }
        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent !important;
        }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.22) !important;
            border-radius: 999px !important;
        }
        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.45) !important;
        }

        .header-sidebar-toggle {
            display: none !important;
        }

        .mobile-toggle {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: #ffffff;
            color: #334155;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mobile-toggle:hover {
            background: #eef4ff;
            color: #0f6dff;
            border-color: #bfd0e4;
            transform: scale(1.05);
        }

        .mobile-toggle .material-symbols-rounded {
            font-size: 22px;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Desktop Mini Sidebar / Icon-Only Collapsed Mode */
        @media (min-width: 1025px) {
            .app-shell.sidebar-collapsed .sidebar {
                width: 72px !important;
                min-width: 72px !important;
                max-width: 72px !important;
                padding: 16px 0 !important;
                transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
                overflow: hidden !important;
            }
            .app-shell.sidebar-collapsed .main-wrapper {
                margin-left: 72px !important;
                width: calc(100% - 72px) !important;
                transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1), width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .app-shell.sidebar-collapsed .sidebar-brand {
                padding: 10px 0 16px !important;
                justify-content: center !important;
                align-items: center !important;
                display: flex !important;
                width: 100% !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            }
            .app-shell.sidebar-collapsed .sidebar-brand > div:not(.brand-mark) {
                display: none !important;
            }
            .app-shell.sidebar-collapsed .brand-mark {
                margin: 0 auto !important;
                width: 42px !important;
                height: 42px !important;
                min-width: 42px !important;
                min-height: 42px !important;
                max-width: 42px !important;
                max-height: 42px !important;
                border-radius: 12px !important;
                aspect-ratio: 1 / 1 !important;
                flex-shrink: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                background: rgba(255, 255, 255, 0.12) !important;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
                overflow: hidden !important;
            }
            .app-shell.sidebar-collapsed .brand-mark img {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
            }
            .app-shell.sidebar-collapsed .brand-mark .material-symbols-rounded {
                font-size: 22px !important;
                line-height: 1 !important;
            }
            .app-shell.sidebar-collapsed .mobile-sidebar-profile {
                display: none !important;
            }
            .app-shell.sidebar-collapsed .sidebar-nav {
                padding: 10px 0 !important;
                width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
            }
            .app-shell.sidebar-collapsed .sidebar-nav .nav-item {
                width: 44px !important;
                height: 44px !important;
                min-width: 44px !important;
                min-height: 44px !important;
                max-width: 44px !important;
                max-height: 44px !important;
                margin: 4px auto !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 12px !important;
                position: relative !important;
                box-sizing: border-box !important;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }
            .app-shell.sidebar-collapsed .sidebar-nav .nav-item span:not(.icon) {
                display: none !important;
            }
            .app-shell.sidebar-collapsed .sidebar-nav .nav-item .icon {
                margin: 0 !important;
                font-size: 22px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            .app-shell.sidebar-collapsed .sidebar-nav .nav-item:hover {
                background: rgba(255, 255, 255, 0.1) !important;
                transform: scale(1.08);
            }
            .app-shell.sidebar-collapsed .sidebar-nav .nav-item.active {
                background: linear-gradient(135deg, rgba(15, 109, 255, 0.4), rgba(59, 130, 246, 0.2)) !important;
                color: #60a5fa !important;
                box-shadow: 0 4px 14px rgba(15, 109, 255, 0.35), inset 0 0 0 1.5px rgba(96, 165, 250, 0.4) !important;
            }
            .app-shell.sidebar-collapsed .sidebar-nav .nav-item.pos-highlight {
                background: linear-gradient(135deg, rgba(245, 158, 11, 0.35), rgba(251, 191, 36, 0.15)) !important;
                color: #fbbf24 !important;
                box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35), inset 0 0 0 1.5px rgba(245, 158, 11, 0.5) !important;
            }
            .app-shell.sidebar-collapsed .sidebar-footer {
                padding: 12px 0 !important;
                width: 100% !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
            }
            .app-shell.sidebar-collapsed .sidebar-footer span:not(.material-symbols-rounded) {
                display: none !important;
            }
            .app-shell.sidebar-collapsed .sidebar-footer .material-symbols-rounded {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 auto !important;
                font-size: 20px !important;
            }
            .app-shell.sidebar-collapsed .sidebar-footer .powered-link {
                width: 42px !important;
                height: 42px !important;
                margin: 0 auto !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 12px !important;
                background: rgba(255, 255, 255, 0.06) !important;
            }

            /* Floating Tooltip in Mini Mode */
            .app-shell.sidebar-collapsed .sidebar-nav .nav-item::after {
                content: attr(data-title);
                position: absolute;
                left: calc(100% + 12px);
                top: 50%;
                transform: translateY(-50%);
                background: #0f172a;
                color: #ffffff;
                padding: 6px 12px;
                border-radius: 8px;
                font-size: 12px;
                font-weight: 800;
                white-space: nowrap;
                box-shadow: 0 6px 20px rgba(0,0,0,0.35);
                border: 1px solid rgba(255,255,255,0.15);
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.15s ease, transform 0.15s ease;
                z-index: 9999999;
            }
            .app-shell.sidebar-collapsed .sidebar-nav .nav-item:hover::after {
                opacity: 1;
                transform: translateY(-50%) translateX(4px);
            }
        }
        @media (max-width: 1024px) {
            .header-sidebar-toggle {
                display: none !important;
            }
        }

        /* Expired Store Lock States */
        .nav-item.expired-disabled-link {
            opacity: 0.42 !important;
            cursor: not-allowed !important;
            position: relative;
            filter: grayscale(0.85);
        }
        .nav-item.expired-disabled-link:hover {
            background: transparent !important;
            color: rgba(255, 255, 255, 0.5) !important;
            transform: none !important;
        }
    </style>
    <script>
        (function() {
            if (sessionStorage.getItem('splash_shown')) { document.documentElement.classList.add('splash-handled'); }
            const currentAppVersion = '1.1.7';
            const lastAppVersion = localStorage.getItem('devaltum_pos_version');
            if (lastAppVersion && lastAppVersion !== currentAppVersion) {
                try {
                    sessionStorage.clear();
                    localStorage.removeItem('last_flash_toast');
                    localStorage.removeItem('last_flash_time');
                } catch(e) {}
            }
            localStorage.setItem('devaltum_pos_version', currentAppVersion);
        })();
    </script>
</head>

<body data-currency-symbol="<?php echo Security::escape($settings['currency_symbol']); ?>"
    data-tab-session="<?php echo Security::escape((string) ($_GET['tab_session'] ?? $_POST['tab_session'] ?? (defined('TAB_SESSION_ID') ? TAB_SESSION_ID : ''))); ?>"
    data-csrf-token="<?php echo Security::escape(Security::generateCsrfToken()); ?>">
    
    <div id="pwa-splash" class="custom-splash">
        <div class="custom-splash-brand">
            <?php 
                $appBrandLogo = App::brandLogoUrl($settings['website_logo'] ?? '');
                $defaultLogoUrl = (defined('BASE_URL') ? rtrim(BASE_URL, '/') : '') . '/assets/img/icon-192x192.png';
            ?>
            <img src="<?php echo Security::escape($appBrandLogo); ?>" alt="Logo" style="max-height:80px; max-width:80px; object-fit:contain; border-radius:18px; margin-bottom:14px; background:#fff; padding:6px; box-shadow:0 8px 24px rgba(0,0,0,0.3);" onerror="this.onerror=null; this.src='<?php echo Security::escape($defaultLogoUrl); ?>';">
            <h2 style="font-size:26px; font-weight:800; letter-spacing: -0.5px; margin:0;"><?php echo Security::escape($settings['company_name']); ?></h2>
            <p style="color:#64748b; font-size:14px; margin-top:8px;"><?php echo Security::escape($settings['company_tagline']); ?></p>
        </div>
        <div class="custom-splash-footer">
            Powered by DevAltum
        </div>
    </div>
    <script>
        window.addEventListener('load', () => {
            if (!sessionStorage.getItem('splash_shown')) {
                setTimeout(() => {
                    document.getElementById('pwa-splash').classList.add('hide');
                    sessionStorage.setItem('splash_shown', 'true');
                }, 1000);
            }
        });
    </script>

    <div class="app-shell" id="main-app-shell">
        <script>
            if (localStorage.getItem('pos_sidebar_collapsed') === '1' && window.innerWidth > 1024) {
                document.getElementById('main-app-shell')?.classList.add('sidebar-collapsed');
            }
        </script>
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-mark" style="overflow:hidden; display:flex; align-items:center; justify-content:center; background:#ffffff; border-radius:12px; padding:2px;">
                    <img src="<?php echo Security::escape($appBrandLogo); ?>" alt="Logo" style="width:100%; height:100%; object-fit:contain;" onerror="this.onerror=null; this.src='<?php echo Security::escape($defaultLogoUrl); ?>';">
                </div>
                <div>
                    <div class="brand-title"><?php echo Security::escape($settings['company_name']); ?></div>
                    <div class="brand-subtitle">
                        <?php echo Security::escape($settings['company_tagline'] ?: 'Official point of sale system'); ?></div>
                </div>
            </div>
            <div class="mobile-sidebar-profile">
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/auth/profile" class="mobile-sidebar-profile-link">
                    <span
                        class="mobile-sidebar-avatar"><?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?></span>
                    <span class="mobile-sidebar-meta">
                        <strong><?php echo Security::escape($user['name'] ?? 'Admin'); ?></strong>
                        <small><?php echo Security::escape(ucfirst(str_replace('_', ' ', $user['role'] ?? 'admin'))); ?></small>
                    </span>
                    <span class="material-symbols-rounded">chevron_right</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <?php if (Auth::isSuperAdmin()): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/index"
                    data-title="Dashboard"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'sa_dashboard' ? 'active' : ''; ?>">
                    <span class="material-symbols-rounded icon">dashboard</span><span>Dashboard</span>
                </a>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/stores"
                    data-title="Store Management"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'sa_stores' ? 'active' : ''; ?>">
                    <span class="material-symbols-rounded icon">store</span><span>Store Management</span>
                </a>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/plans"
                    data-title="Subscription Plans"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'sa_plans' ? 'active' : ''; ?>">
                    <span class="material-symbols-rounded icon">workspace_premium</span><span>Subscription Plans</span>
                </a>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/templates"
                    data-title="Templates"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'sa_templates' ? 'active' : ''; ?>">
                    <span class="material-symbols-rounded icon">design_services</span><span>Templates</span>
                </a>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/paymentMethods"
                    data-title="Payment Methods"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'sa_methods' ? 'active' : ''; ?>">
                    <span class="material-symbols-rounded icon">account_balance_wallet</span><span>Payment Methods</span>
                </a>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/payments"
                    data-title="Payment History"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'sa_payments' ? 'active' : ''; ?>">
                    <span class="material-symbols-rounded icon">receipt_long</span><span>Payment History</span>
                </a>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/userAccess"
                    data-title="User Access"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'sa_users' ? 'active' : ''; ?>">
                    <span class="material-symbols-rounded icon">manage_accounts</span><span>User Access</span>
                </a>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/notifications/index?tab=new"
                    data-title="Notifications"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'sa_notifications' ? 'active' : ''; ?>">
                    <span class="material-symbols-rounded icon">notifications</span><span>Notifications</span>
                </a>
                <?php else: ?>
                <?php
                $expCls = ($isStoreExpired && !Auth::isSuperAdmin()) ? ' expired-disabled-link' : '';
                $expClick = ($isStoreExpired && !Auth::isSuperAdmin()) ? ' onclick="event.preventDefault(); window.location.href=\''.(defined('BASE_URL') ? BASE_URL : '').'/plans/pay\';"' : '';
                ?>
                <?php if ($canAccess('dashboard')): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/home/index"
                    data-title="Dashboard"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'dashboard' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">dashboard</span><span>Dashboard</span>
                </a>
                <?php endif; ?>
                <?php if ($canAccess('pos')): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/pos/index"
                    data-title="POS Checkout"
                    class="nav-item pos-highlight <?php echo ($activeMenu ?? '') === 'pos' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">point_of_sale</span><span>POS Checkout</span>
                </a>
                <?php endif; ?>
                <?php if ($canAccess('sales')): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/index"
                    data-title="Sales"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'sales' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">receipt_long</span><span>Sales</span>
                </a>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/returns/index"
                    data-title="Returns"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'returns' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">assignment_return</span><span>Returns</span>
                </a>
                <?php endif; ?>
                <?php if ($canAccess('credits')): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/credits/index"
                    data-title="Credit Dues"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'credits' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">credit_card</span><span>Credit Dues</span>
                </a>
                <?php endif; ?>
                <?php if ($canAccess('quotations')): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/quotations/index"
                    data-title="Quotations"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'quotations' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">request_quote</span><span>Quotations</span>
                </a>
                <?php endif; ?>
                <?php if ($canAccess('inventory')): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/index"
                    data-title="Inventory"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'inventory' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">inventory_2</span><span>Inventory</span>
                </a>
                <?php endif; ?>
                <?php if ($canAccess('expenses')): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/expenses/index"
                    data-title="Expenses"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'expenses' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">payments</span><span>Expenses</span>
                </a>
                <?php endif; ?>
                <?php if ($canAccess('cashflow') || Auth::isAdmin()): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/cashflow/index"
                    data-title="Cashflow"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'cashflow' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">account_balance_wallet</span><span>Cashflow</span>
                </a>
                <?php endif; ?>
                <?php if ($canAccess('customers')): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/index"
                    data-title="Customers"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'customers' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">group</span><span>Customers</span>
                </a>
                <?php endif; ?>
                <?php if ($canAccess('reports')): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/reports/index"
                    data-title="Reports"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'reports' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">bar_chart</span><span>Reports</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::isAdmin()): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/staffanalytics/index"
                    data-title="Staff Analytics"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'staff_analytics' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">groups</span><span>Staff Analytics</span>
                </a>
                <?php endif; ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/notifications/index?tab=new"
                    data-title="Notifications"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'notifications' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">notifications</span><span>Notifications</span>
                </a>

                <?php if (Auth::isAdmin()): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/users/index"
                    data-title="Users"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'users' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">manage_accounts</span><span>Users</span>
                </a>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/templates/index"
                    data-title="Templates"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'templates' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">receipt_long</span><span>Templates</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::isAdmin() || $canAccess('settings')): ?>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/software"
                    data-title="Software Updates"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'software_updates' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">system_update</span><span>Software Updates</span>
                    <span id="nav-update-indicator-dot" style="display:none; margin-left:auto; width:8px; height:8px; border-radius:50%; background:#10b981; box-shadow:0 0 8px #10b981;"></span>
                </a>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/index"
                    data-title="Settings"
                    class="nav-item <?php echo ($activeMenu ?? '') === 'settings' ? 'active' : ''; ?><?= $expCls ?>"<?= $expClick ?>>
                    <span class="material-symbols-rounded icon">settings</span><span>Settings</span>
                </a>
                <?php endif; ?>
                <?php endif; ?>
                
                <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/auth/logout" method="POST" data-no-ajax>
                    <?php echo Security::csrfField(); ?>
                    <button type="submit" class="nav-item nav-danger"
                        data-title="Logout"
                        style="width:100%; border:none; background:none; text-align:left;">
                        <span class="material-symbols-rounded icon">logout</span><span>Logout</span>
                    </button>
                </form>
            </nav>

            <div class="sidebar-footer">
                <a class="powered-link"
                    href="https://wa.me/<?php echo $developerWaNumber; ?>?text=<?php echo $developerWaMessage; ?>"
                    target="_blank">
                    <span>Powered by DevAltum</span>
                    <span class="material-symbols-rounded">open_in_new</span>
                </a>
            </div>
        </aside>

        <main class="main-wrapper">
            <header class="top-header">
                <div style="display:flex; align-items:center; gap:12px; flex:1; max-width:560px; min-width:0;">
                    <div class="search-box" style="flex:1; width:100%; min-width:0;">
                        <span class="material-symbols-rounded search-icon">search</span>
                        <input type="text" id="global-search" placeholder="<?php echo Auth::isSuperAdmin() ? 'Search stores, plans, payments, users...' : 'Search products, customers, invoices...'; ?>">
                    </div>
                </div>

                <div class="header-actions">
                    <?php if (!Auth::isSuperAdmin()): ?>
                    <div class="dropdown" id="web-orders-notification" style="display: none; margin-right: 8px;">
                        <button id="web-orders-bell-btn"
                            class="icon-btn dropdown-toggle bell-shake"
                            style="background: rgba(34, 197, 94, 0.1); color: #22c55e;"
                            type="button" data-dropdown="web-orders" title="Pending Online Orders">
                            <span class="material-symbols-rounded">shopping_bag</span>
                            <span id="web-orders-badge" class="icon-badge" style="background: #22c55e;">0</span>
                        </button>
                        <div class="dropdown-menu" data-dropdown-menu="web-orders" id="web-orders-dropdown-menu">
                            <div class="dropdown-title" style="color: #22c55e;">New Online Orders</div>
                            <div id="web-orders-dropdown-list">
                                <div class="dropdown-empty">No pending online orders.</div>
                            </div>
                            <a class="dropdown-link dropdown-link-view-all"
                                href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/index?source=web">
                                <span class="material-symbols-rounded"
                                    style="font-size:18px; color: #22c55e;">shopping_cart_checkout</span>
                                <span>Manage Web Orders</span>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php
                    $currentStore = Tenant::currentStore();
                    if (!$currentStore) {
                        $currentStore = Database::getInstance()->fetch("SELECT * FROM stores ORDER BY id ASC LIMIT 1");
                    }
                    $activeStoreName = !empty($currentStore['store_name']) ? $currentStore['store_name'] : (!empty($_SESSION['store_name']) ? $_SESSION['store_name'] : App::setting('company_name', 'DevAltum Store'));
                    $roleLabel = ucfirst(str_replace('_', ' ', $user['role'] ?? 'Admin'));
                    $profileSub = ($user['name'] ?? $user['email'] ?? 'User') . ' • ' . $roleLabel;
                    ?>

                    <!-- Notifications Dropdown -->
                    <div class="dropdown">
                        <button id="notifications-bell-btn"
                            class="icon-btn dropdown-toggle <?php echo $newNotificationsCount > 0 ? 'bell-shake' : ''; ?>"
                            type="button" data-dropdown="notifications" title="<?php echo Auth::isSuperAdmin() ? 'Platform notifications' : 'Credit due notifications'; ?>">
                            <span class="material-symbols-rounded">notifications</span>
                            <?php if ($newNotificationsCount > 0): ?>
                            <span id="notifications-bell-badge"
                                class="icon-badge"><?php echo $newNotificationsCount; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu" data-dropdown-menu="notifications" id="notifications-dropdown-menu">

                            <div class="dropdown-title"><?php echo Auth::isSuperAdmin() ? 'Platform Notifications' : 'New Due Notifications'; ?></div>
                            <div id="notifications-dropdown-list">
                                <?php if (empty($latestNotifications)): ?>
                                <div class="dropdown-empty">No new notifications.</div>
                                <?php else: ?>
                                <?php foreach ($latestNotifications as $notify): ?>
                                <a class="dropdown-link" data-notification-open
                                    href="<?php echo Security::escape($notify['link']); ?>">
                                    <div class="dropdown-link-title"><?php echo Security::escape($notify['type']); ?>:
                                        <?php echo Security::escape($notify['title']); ?></div>
                                    <div class="dropdown-link-subtitle">
                                        <?php echo Security::escape($notify['message']); ?> |
                                        <?php echo date('M d, h:i A', strtotime($notify['created_at'])); ?></div>
                                </a>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <a class="dropdown-link dropdown-link-view-all"
                                href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/notifications/index?tab=new">
                                <span class="material-symbols-rounded"
                                    style="font-size:18px;">notifications_active</span>
                                <span>View All</span>
                            </a>
                        </div>
                    </div>

                    <div class="dropdown" style="position:relative;">
                        <button class="profile-chip dropdown-toggle" type="button" data-dropdown="profile" id="userProfileChipBtn">
                            <span
                                class="profile-avatar"><?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?></span>
                            <span class="profile-meta">
                                <strong id="headerProfileStoreName"><?php echo Security::escape($activeStoreName); ?></strong>
                                <small id="headerProfileSubText"><?php echo Security::escape($profileSub); ?></small>
                            </span>
                            <span class="material-symbols-rounded">expand_more</span>
                        </button>
                        <div class="dropdown-menu" data-dropdown-menu="profile" id="userProfileDropdownMenu">
                            <div class="dropdown-title">
                                <div style="font-weight: 800; color: #0f172a; font-size: 14px;" id="profileModalUserName"><?php echo Security::escape($user['name'] ?? 'Admin'); ?></div>
                                <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;"><?php echo Security::escape($user['email'] ?? ''); ?></div>
                            </div>
                            <a class="dropdown-link" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/auth/profile">
                                <span class="material-symbols-rounded" style="font-size:18px;">account_circle</span>
                                <span>My Profile</span>
                            </a>
                            <?php if (Auth::isAdmin() || $canAccess('settings')): ?>
                            <a class="dropdown-link"
                                href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/index">
                                <span class="material-symbols-rounded" style="font-size:18px;">settings</span>
                                <span>Settings</span>
                            </a>
                            <?php endif; ?>
                            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/auth/logout" method="POST"
                                data-no-ajax>
                                <?php echo Security::csrfField(); ?>
                                <button type="submit" class="dropdown-link danger"
                                    style="width:100%; text-align:left; border:none; background:none; cursor:pointer;">
                                    <span class="material-symbols-rounded" style="font-size:18px;">logout</span>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    <button id="mobile-menu-toggle" class="icon-btn mobile-toggle" type="button" aria-label="Toggle Sidebar"
                        title="Toggle Sidebar">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                    <script>
                        (function() {
                            const isCollapsed = localStorage.getItem('pos_sidebar_collapsed') === '1' && window.innerWidth > 1024;
                            const iconEl = document.querySelector('#mobile-menu-toggle .material-symbols-rounded');
                            if (iconEl) {
                                iconEl.textContent = (window.innerWidth <= 1024 || isCollapsed) ? 'menu' : 'close';
                            }
                        })();
                    </script>
                </div>
            </header>

            <div class="content-area">
                <?php if ($flash): ?>
                <div class="app-alert alert-<?php echo Security::escape($flash['type']); ?>">
                    <span
                        class="material-symbols-rounded"><?php echo $flash['type'] === 'success' ? 'check_circle' : 'error'; ?></span>
                    <span><?php echo Security::escape($flash['message']); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($isStoreExpired && !Auth::isSuperAdmin()): ?>
                <div style="background: linear-gradient(135deg, #1e1b4b 0%, #311025 100%); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 16px; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 20px; box-shadow: 0 8px 24px rgba(239, 68, 68, 0.15);">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="material-symbols-rounded" style="color:#ef4444; font-size:28px;">lock</span>
                        <div>
                            <strong style="color:#ffffff; font-size:15px; display:block;">Your Subscription Plan Has Expired!</strong>
                            <span style="color:#cbd5e1; font-size:13px; font-weight:500;">All POS operations & sales are locked. Please complete your subscription payment to unlock full features.</span>
                        </div>
                    </div>
                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/pay" class="btn btn-primary" style="background:#ef4444; border-color:#ef4444; font-weight:800; border-radius:12px; height:40px; padding:0 18px; display:inline-flex; align-items:center; gap:6px; box-shadow:0 4px 14px rgba(239,68,68,0.35); text-decoration:none;">
                        <span class="material-symbols-rounded" style="font-size:19px;">payments</span> Pay & Renew Now
                    </a>
                </div>
                <?php endif; ?>

                <?php echo $content; ?>

                <div class="mobile-powered-footer">
                    <div class="powered-link-mobile">
                        <span>Powered by <a href="https://wa.me/923101648600?text=I+am+coming+from+DevAltum+POS"
                                target="_blank" style="color:var(--primary); font-weight:700; text-decoration:none;">DevAltum</a></span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <a id="mobile-floating-bell" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/notifications/index?tab=new"
        class="mobile-floating-bell <?php echo $newNotificationsCount > 0 ? 'show' : ''; ?>">
        <span class="material-symbols-rounded">notifications_active</span>
        <span id="mobile-floating-bell-badge"
            class="icon-badge"><?php echo max(0, (int) $newNotificationsCount); ?></span>
    </a>

    <?php
    $showFloatingPayBtn = false;
    if (!Auth::isSuperAdmin()) {
        $currentStoreForLayout = Tenant::currentStore();
        if ($currentStoreForLayout && !empty($currentStoreForLayout['next_monthly_charge_date'])) {
            $dueTs = strtotime($currentStoreForLayout['next_monthly_charge_date'] . ' 23:59:59');
            $nowTs = time();
            $diffSeconds = $dueTs - $nowTs;
            if ($diffSeconds <= 12 * 3600) {
                $showFloatingPayBtn = true;
            }
        }
    }
    ?>
    <?php if ($showFloatingPayBtn): ?>
    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/plans/pay" class="floating-pay-now-btn" title="Pay Installment Now">
        <span class="material-symbols-rounded">credit_card</span>
        <span class="pulse-glowing-effect"></span>
    </a>
    <style>
    .floating-pay-now-btn {
        position: fixed;
        right: 24px;
        bottom: 24px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981, #059669);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 32px rgba(16, 185, 129, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
        z-index: 9999;
        text-decoration: none;
        transition: transform 0.2s, box-shadow 0.2s;
        animation: payPulse 2s infinite ease-in-out;
    }
    .floating-pay-now-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 12px 40px rgba(16, 185, 129, 0.6);
    }
    .floating-pay-now-btn .material-symbols-rounded {
        font-size: 28px;
    }
    .pulse-glowing-effect {
        position: absolute;
        inset: -6px;
        border-radius: 50%;
        border: 2px solid #10b981;
        opacity: 0;
        animation: glowingEffect 2s infinite ease-in-out;
        pointer-events: none;
    }
    @keyframes payPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.06); }
        100% { transform: scale(1); }
    }
    @keyframes glowingEffect {
        0% { transform: scale(0.9); opacity: 0.8; }
        100% { transform: scale(1.3); opacity: 0; }
    }
    @media (max-width: 1100px) {
        .floating-pay-now-btn {
            right: 16px;
            bottom: 154px;
        }
    }
    </style>
    <?php endif; ?>

    <?php if (!Auth::isSuperAdmin() && (!empty($_SESSION['superadmin_return_id']) || !empty($_SESSION['parent_owner_return_id']))): ?>
    <aside class="return-switcher" data-return-switcher aria-label="Account return shortcuts">
        <button class="return-switcher-toggle" type="button" data-return-switcher-toggle aria-expanded="false" title="Return shortcuts">
            <span class="material-symbols-rounded">chevron_left</span>
        </button>
        <div class="return-switcher-panel">
            <div class="return-switcher-head">
                <span class="material-symbols-rounded">sync_alt</span>
                <div>
                    <strong>Return Panel</strong>
                    <small>Switch back quickly</small>
                </div>
            </div>
            <?php if (!empty($_SESSION['superadmin_return_id'])): ?>
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/auth/returnToSuperAdmin" method="POST" data-no-ajax>
                <?php echo Security::csrfField(); ?>
                <button class="return-switcher-action" type="submit">
                    <span class="material-symbols-rounded">switch_account</span>
                    <span>Return to Super Admin</span>
                </button>
            </form>
            <?php endif; ?>
            <?php if (!empty($_SESSION['parent_owner_return_id'])): ?>
            <form action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/auth/returnToParentOwner" method="POST" data-no-ajax>
                <?php echo Security::csrfField(); ?>
                <button class="return-switcher-action" type="submit">
                    <span class="material-symbols-rounded">keyboard_return</span>
                    <span>Return to Main Branch</span>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </aside>
    <?php endif; ?>

    <div id="app-modal-root"></div>
    <nav class="mobile-bottom-nav">
        <?php if (Auth::isSuperAdmin()): ?>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/index"
            class="<?php echo ($activeMenu ?? '') === 'sa_dashboard' ? 'active' : ''; ?>"><span
                class="material-symbols-rounded">dashboard</span><small>Dashboard</small></a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/stores"
            class="<?php echo ($activeMenu ?? '') === 'sa_stores' ? 'active' : ''; ?>"><span
                class="material-symbols-rounded">store</span><small>Stores</small></a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/plans"
            class="<?php echo ($activeMenu ?? '') === 'sa_plans' ? 'active' : ''; ?>"><span
                class="material-symbols-rounded">workspace_premium</span><small>Plans</small></a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/payments"
            class="<?php echo ($activeMenu ?? '') === 'sa_payments' ? 'active' : ''; ?>"><span
                class="material-symbols-rounded">receipt_long</span><small>Billing</small></a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/superadmin/userAccess"
            class="<?php echo ($activeMenu ?? '') === 'sa_users' ? 'active' : ''; ?>"><span
                class="material-symbols-rounded">manage_accounts</span><small>Users</small></a>
        <?php else: ?>
        <?php if ($canAccess('dashboard')): ?><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/home/index"
            class="<?php echo ($activeMenu ?? '') === 'dashboard' ? 'active' : ''; ?>"><span
                class="material-symbols-rounded">dashboard</span><small>Home</small></a><?php endif; ?>
        <?php if ($canAccess('inventory')): ?><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/inventory/index"
            class="<?php echo ($activeMenu ?? '') === 'inventory' ? 'active' : ''; ?>"><span
                class="material-symbols-rounded">inventory_2</span><small>Stock</small></a><?php endif; ?>
        <?php if ($canAccess('pos')): ?><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/pos/index"
            class="pos-tab <?php echo ($activeMenu ?? '') === 'pos' ? 'active' : ''; ?>"><span
                class="material-symbols-rounded">point_of_sale</span><small>POS</small></a><?php endif; ?>
        <?php if ($canAccess('sales')): ?><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/index"
            class="<?php echo ($activeMenu ?? '') === 'sales' ? 'active' : ''; ?>"><span
                class="material-symbols-rounded">receipt_long</span><small>Sales</small></a><?php endif; ?>
        <?php if ($canAccess('customers')): ?><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/customers/index"
            class="<?php echo ($activeMenu ?? '') === 'customers' ? 'active' : ''; ?>"><span
                class="material-symbols-rounded">group</span><small>Customers</small></a><?php endif; ?>
        <?php endif; ?>
    </nav>
    <script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/JsBarcode.all.min.js"></script>
    <script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/qrcode.min.js"></script>
    <script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/app.js?v=<?= @filemtime(ROOT_PATH . '/public/assets/js/app.js') ?: time() ?>"></script>
    <?php if (!Auth::isSuperAdmin()): ?>
    <script>
        window.openWirelessScannerPairModal = function() {
            const pin = <?php echo json_encode($navScannerPin ?? '123456'); ?>;
            const mobileUrl = <?php echo json_encode($navScannerUrl ?? ''); ?>;

            const modalHtml = `
                <div style="text-align:center; padding:10px 0;">
                    <div style="width:64px; height:64px; border-radius:20px; background:linear-gradient(135deg, #2563eb, #10b981); display:grid; place-items:center; margin:0 auto 16px; color:#fff; box-shadow:0 10px 25px rgba(37,99,235,0.3);">
                        <span class="material-symbols-rounded" style="font-size:36px;">qr_code_scanner</span>
                    </div>
                    <h3 style="font-size:18px; font-weight:900; color:#0f172a; margin-bottom:4px;">Mobile Wireless Scanner</h3>
                    <p style="font-size:12.5px; color:#64748b; margin-bottom:20px;">Use any smartphone camera as a wireless barcode scanner for this POS.</p>

                    <div style="background:#f8fafc; border:2px dashed #cbd5e1; border-radius:18px; padding:18px; margin-bottom:18px; display:flex; flex-direction:column; align-items:center;">
                        <span style="font-size:11px; font-weight:800; text-transform:uppercase; color:#64748b; letter-spacing:1px; margin-bottom:6px;">6-Digit Pairing PIN</span>
                        <div style="font-size:32px; font-weight:900; letter-spacing:6px; color:#1d4ed8; font-family:monospace; background:#fff; padding:6px 20px; border-radius:12px; border:1px solid #bfdbfe; box-shadow:0 4px 12px rgba(29,78,216,0.1); margin-bottom:14px;">
                            ${pin}
                        </div>
                        <div id="pair-qr-code-canvas" style="padding:10px; background:#fff; border-radius:12px; box-shadow:0 4px 14px rgba(0,0,0,0.06); border:1px solid #e2e8f0; margin-bottom:10px;"></div>
                        <span style="font-size:11px; color:#64748b; font-weight:700;">Scan QR with phone camera or enter 6-digit PIN</span>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:8px; text-align:left; background:#eef4ff; border:1px solid #bfdbfe; border-radius:12px; padding:12px 14px; font-size:12px; color:#1e3a8a; font-weight:700;">
                        <div style="display:flex; align-items:center; gap:8px;"><span class="material-symbols-rounded" style="color:#2563eb; font-size:18px;">check_circle</span> <span><strong>POS Mode:</strong> Scans instantly fly into your active checkout cart!</span></div>
                        <div style="display:flex; align-items:center; gap:8px;"><span class="material-symbols-rounded" style="color:#2563eb; font-size:18px;">check_circle</span> <span><strong>Inventory Mode:</strong> Scans auto-fill Add Product barcode field.</span></div>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; gap:10px;">
                        <a href="${mobileUrl}" target="_blank" class="btn btn-secondary" style="font-size:12px; font-weight:800;">Open Mobile URL &rarr;</a>
                        <button type="button" class="btn btn-primary" data-modal-close style="padding:10px 24px; font-weight:850;">Done</button>
                    </div>
                </div>
            `;

            window.appModal.open({
                title: 'Wireless Scanner Pairing',
                body: modalHtml
            });

            setTimeout(() => {
                const qrMount = document.getElementById('pair-qr-code-canvas');
                if (qrMount && window.QRCode) {
                    new QRCode(qrMount, mobileUrl, {
                        width: 140,
                        height: 140,
                        colorDark: '#0f172a',
                        colorLight: '#ffffff'
                    });
                }
            }, 50);
        };
    </script>
    <?php endif; ?>
    <script>
        // Ensure standalone desktop offline reliability by unregistering any legacy PWA service workers/caches
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(registrations => {
                for (let registration of registrations) {
                    registration.unregister();
                }
            }).catch(() => {});
            if ('caches' in window) {
                caches.keys().then(keys => {
                    keys.forEach(key => caches.delete(key));
                }).catch(() => {});
            }
        }
    </script>
    <?php if (!Auth::isSuperAdmin()): ?>
    <script>
        (() => {
            const bell = document.getElementById('web-orders-notification');
            const badge = document.getElementById('web-orders-badge');
            const listContainer = document.getElementById('web-orders-dropdown-list');
            
            if (!bell || !badge || !listContainer) return;
            
            let lastEventId = 0;
            let activeOrders = new Set();
            
            const playChime = () => {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
                    osc.frequency.setValueAtTime(880, ctx.currentTime + 0.15); // A5
                    gain.gain.setValueAtTime(0, ctx.currentTime);
                    gain.gain.linearRampToValueAtTime(0.2, ctx.currentTime + 0.05);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.55);
                    setTimeout(() => ctx.close(), 600);
                } catch(e) {}
            };

            const updateOrdersDropdown = (orders) => {
                let playedSound = false;
                orders.forEach(order => {
                    const orderId = order.id;
                    if (!activeOrders.has(orderId)) {
                        activeOrders.add(orderId);
                        if (!playedSound) {
                            playChime();
                            playedSound = true;
                        }
                    }
                    if (order.id > lastEventId) {
                        lastEventId = order.id;
                    }
                });

                badge.textContent = activeOrders.size;
                if (activeOrders.size > 0) {
                    bell.style.display = 'block';
                    listContainer.innerHTML = orders.map(order => `
                        <a class="dropdown-link" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/sales/view/${order.id}">
                            <div class="dropdown-link-title" style="color: #22c55e; font-weight:800;">Order: ${order.custom_id}</div>
                            <div class="dropdown-link-subtitle">Total: Rs ${parseFloat(order.total).toFixed(2)} | Pending</div>
                        </a>
                    `).join('');
                } else {
                    bell.style.display = 'none';
                    listContainer.innerHTML = '<div class="dropdown-empty">No pending online orders.</div>';
                }
            };

            // Check active orders initially (no continuous blocking SSE loop)
            const checkInitialOrders = async () => {
                try {
                    const res = await fetch(`<?= defined('BASE_URL') ? BASE_URL : '' ?>/storeapi/active_pending_count`);
                    const data = await res.json();
                    if (data && Array.isArray(data.orders)) {
                        activeOrders = new Set(data.orders.map(o => o.id));
                        updateOrdersDropdown(data.orders);
                    }
                } catch(e) {}
            };
            
            // Only check once on load if needed
            checkInitialOrders();
        })();
    </script>
    <?php endif; ?>

    <?php if (!Auth::isSuperAdmin() && ($activeMenu ?? '') !== 'pos' && App::setting('scanner_global_pos_redirect', '1') === '1'): ?>
    <!-- Universal Barcode Scanner Auto-Redirect to POS Checkout -->
    <script>
        (() => {
            const baseUrl = '<?= defined('BASE_URL') ? BASE_URL : '' ?>';
            const mobileScannerEnabled = <?= json_encode(App::setting('mobile_scanner_enabled', '0') === '1') ?>;
            const storePairCode = <?= json_encode(class_exists('Tenant') ? App::getStoreScannerPairCode((int) Tenant::currentStoreId()) : '123456') ?>;

            let barcodeBuffer = '';
            let barcodeTimer = null;
            let bufferStartTime = 0;
            let keystrokeCount = 0;
            let isRedirecting = false;

            const handleGlobalBarcodeRedirect = (rawBarcode) => {
                if (isRedirecting) return;
                let barcode = String(rawBarcode || '').trim();
                if (!barcode || barcode.length < 3) return;

                // Fix potential doubled keystrokes from fast scanner bounce (e.g. 112233 -> 123)
                if (barcode.length >= 6 && barcode.length % 2 === 0) {
                    let isDoubled = true;
                    for (let i = 0; i < barcode.length; i += 2) {
                        if (barcode[i] !== barcode[i + 1]) { isDoubled = false; break; }
                    }
                    if (isDoubled) {
                        let single = '';
                        for (let i = 0; i < barcode.length; i += 2) single += barcode[i];
                        barcode = single;
                    }
                }

                if (!barcode) return;
                isRedirecting = true;

                // Immediate redirect to POS with ?scan parameter
                window.location.href = baseUrl + '/pos?scan=' + encodeURIComponent(barcode);
            };

            // 1. Hardware Barcode Scanner Listener (USB / Wireless Keyboard Emulation)
            window.addEventListener('keydown', (e) => {
                if (isRedirecting) return;

                const key = e.key || '';
                const code = e.code || '';
                const isCtrl = e.ctrlKey || e.metaKey;
                const isAlt = e.altKey;

                if (key === 'Enter' || code === 'Enter' || code === 'NumpadEnter') {
                    const now = Date.now();
                    const duration = now - bufferStartTime;
                    const count = keystrokeCount;
                    const candidate = barcodeBuffer.trim();
                    barcodeBuffer = '';
                    keystrokeCount = 0;
                    clearTimeout(barcodeTimer);

                    // Scanners emit characters at superhuman speed (<55ms per char on avg)
                    // If candidate has at least 3 chars and was entered in < 500ms total
                    if (candidate.length >= 3 && count >= 3 && (duration <= count * 70 + 80)) {
                        e.preventDefault();
                        e.stopPropagation();
                        handleGlobalBarcodeRedirect(candidate);
                        return;
                    }
                    return;
                }

                // Collect single printable characters
                if (key.length === 1 && !isCtrl && !isAlt) {
                    if (barcodeBuffer === '') {
                        bufferStartTime = Date.now();
                        keystrokeCount = 0;
                    }
                    barcodeBuffer += key;
                    keystrokeCount++;

                    clearTimeout(barcodeTimer);
                    // Reset buffer if delay between keystrokes exceeds 70ms (human typing)
                    barcodeTimer = setTimeout(() => {
                        barcodeBuffer = '';
                        keystrokeCount = 0;
                    }, 70);
                }
            }, true);
        })();
    </script>
    <?php endif; ?>

    <!-- DevAltum POS Premium Draggable Floating Update Widget (Hidden by default, only shown when 100% downloaded) -->
    <div id="pos-draggable-update-widget" style="display: none; position: fixed; z-index: 99998; touch-action: none; cursor: grab; user-select: none;">
        <!-- Widget Floating Pill -->
        <div id="widget-main-pill" style="display: flex; align-items: center; gap: 8px; background: rgba(15, 23, 42, 0.88); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid rgba(255, 255, 255, 0.16); padding: 7px 14px 7px 9px; border-radius: 30px; box-shadow: 0 12px 30px rgba(0, 0, 0, 0.28), 0 0 0 1px rgba(255, 255, 255, 0.05); transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease;">
            <!-- Icon with pulse indicator -->
            <div style="position: relative; width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #1d4ed8); display: flex; align-items: center; justify-content: center; box-shadow: 0 3px 10px rgba(37,99,235,0.4);">
                <span class="material-symbols-rounded" id="widget-icon" style="font-size: 17px; color: #ffffff;">system_update</span>
                <span id="widget-pulse-dot" style="position: absolute; top: -1px; right: -1px; width: 8px; height: 8px; border-radius: 50%; background: #10b981; border: 1.5px solid #0f172a;"></span>
            </div>
            <!-- Dynamic Label -->
            <div style="display: flex; flex-direction: column; line-height: 1.1;">
                <span style="font-size: 10px; font-weight: 500; color: #94a3b8; letter-spacing: 0.3px; text-transform: uppercase;">DevAltum</span>
                <span id="widget-status-text" style="font-size: 12px; font-weight: 700; color: #f8fafc;">v1.1.0</span>
            </div>
            <!-- Action chevron / status icon -->
            <span class="material-symbols-rounded" id="widget-action-icon" style="font-size: 16px; color: #94a3b8; margin-left: 2px;">expand_less</span>
        </div>

        <!-- Mini Quick Panel (Toggleable) -->
        <div id="widget-quick-panel" style="display: none; position: absolute; bottom: 48px; right: 0; width: 260px; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.16); border-radius: 16px; padding: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); color: #fff; transform-origin: bottom right;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span class="material-symbols-rounded" style="color: #3b82f6; font-size: 18px;">cloud_sync</span>
                    <strong style="font-size: 13px; color: #f1f5f9;">Update Manager</strong>
                </div>
                <span id="panel-status-badge" style="font-size: 10px; font-weight: 600; padding: 2px 7px; border-radius: 10px; background: rgba(16,185,129,0.2); color: #34d399;">Up to Date</span>
            </div>
            <p id="panel-status-desc" style="font-size: 11px; color: #94a3b8; margin: 0 0 12px 0; line-height: 1.4;">
                Desktop app checks GitHub for releases. All store data is 100% safe.
            </p>
            <div id="panel-progress-container" style="display: none; margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; font-size: 10px; color: #cbd5e1; margin-bottom: 4px;">
                    <span>Downloading...</span>
                    <span id="panel-progress-percent">0%</span>
                </div>
                <div style="height: 5px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                    <div id="panel-progress-bar" style="width: 0%; height: 100%; background: #3b82f6; border-radius: 3px;"></div>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/updates/software" style="flex: 1; text-align: center; background: #2563eb; color: #fff; text-decoration: none; padding: 7px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; transition: background 0.2s;">
                    Update Center
                </a>
                <button type="button" id="panel-restart-btn" onclick="if(window.desktopAPI) window.desktopAPI.installUpdate();" style="display: none; flex: 1; background: #10b981; color: #fff; border: none; padding: 7px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer;">
                    Restart
                </button>
            </div>
        </div>
    </div>

    <script>
    (function initDraggableUpdateWidget() {
        const widget = document.getElementById('pos-draggable-update-widget');
        const pill = document.getElementById('widget-main-pill');
        const panel = document.getElementById('widget-quick-panel');
        const statusText = document.getElementById('widget-status-text');
        const pulseDot = document.getElementById('widget-pulse-dot');
        const panelBadge = document.getElementById('panel-status-badge');
        const panelDesc = document.getElementById('panel-status-desc');
        const progressContainer = document.getElementById('panel-progress-container');
        const progressBar = document.getElementById('panel-progress-bar');
        const progressPercent = document.getElementById('panel-progress-percent');
        const restartBtn = document.getElementById('panel-restart-btn');

        if (!widget || !pill) return;

        // Restore stored position or default to bottom-right
        let posX = localStorage.getItem('devaltum_widget_x');
        let posY = localStorage.getItem('devaltum_widget_y');

        const margin = 20;
        const screenW = window.innerWidth;
        const screenH = window.innerHeight;

        if (posX !== null && posY !== null) {
            posX = Math.max(margin, Math.min(screenW - 170, parseInt(posX)));
            posY = Math.max(margin, Math.min(screenH - 50, parseInt(posY)));
            widget.style.left = posX + 'px';
            widget.style.top = posY + 'px';
        } else {
            widget.style.right = '24px';
            widget.style.bottom = '24px';
        }

        // Draggable Logic (Smooth Touch + Mouse)
        let isDragging = false;
        let startX = 0, startY = 0;
        let initWidgetX = 0, initWidgetY = 0;
        let movedDistance = 0;

        pill.addEventListener('pointerdown', function(e) {
            isDragging = true;
            widget.style.cursor = 'grabbing';
            pill.style.transform = 'scale(0.96)';

            const rect = widget.getBoundingClientRect();
            initWidgetX = rect.left;
            initWidgetY = rect.top;

            startX = e.clientX;
            startY = e.clientY;
            movedDistance = 0;

            // Remove bottom/right pinning once dragged
            widget.style.right = 'auto';
            widget.style.bottom = 'auto';
            widget.style.left = initWidgetX + 'px';
            widget.style.top = initWidgetY + 'px';

            pill.setPointerCapture(e.pointerId);
        });

        window.addEventListener('pointermove', function(e) {
            if (!isDragging) return;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            movedDistance += Math.abs(dx) + Math.abs(dy);

            let newX = initWidgetX + dx;
            let newY = initWidgetY + dy;

            // Viewport clamping
            const rect = widget.getBoundingClientRect();
            newX = Math.max(8, Math.min(window.innerWidth - rect.width - 8, newX));
            newY = Math.max(8, Math.min(window.innerHeight - rect.height - 8, newY));

            widget.style.left = newX + 'px';
            widget.style.top = newY + 'px';

            // Auto position quick panel to stay inside screen
            if (newY < 180) {
                panel.style.bottom = 'auto';
                panel.style.top = '48px';
            } else {
                panel.style.top = 'auto';
                panel.style.bottom = '48px';
            }
            if (newX < 260) {
                panel.style.right = 'auto';
                panel.style.left = '0px';
            } else {
                panel.style.left = 'auto';
                panel.style.right = '0px';
            }
        });

        function stopDragging(e) {
            if (!isDragging) return;
            isDragging = false;
            widget.style.cursor = 'grab';
            pill.style.transform = 'scale(1)';

            const rect = widget.getBoundingClientRect();
            localStorage.setItem('devaltum_widget_x', Math.round(rect.left));
            localStorage.setItem('devaltum_widget_y', Math.round(rect.top));

            // If it was a click (not a drag)
            if (movedDistance < 6) {
                if (panel.style.display === 'none' || panel.style.display === '') {
                    panel.style.display = 'block';
                } else {
                    panel.style.display = 'none';
                }
            }
        }

        window.addEventListener('pointerup', stopDragging);
        window.addEventListener('pointercancel', stopDragging);

        // Close panel when clicking outside
        document.addEventListener('click', function(e) {
            if (!widget.contains(e.target)) {
                panel.style.display = 'none';
            }
        });

        // Electron desktop updater connection
        if (typeof window.desktopAPI !== 'undefined' && window.desktopAPI.isDesktop) {
            window.desktopAPI.getSystemInfo().then(info => {
                if (info && info.version) {
                    statusText.textContent = 'v' + info.version;
                }
            }).catch(() => {});

            window.desktopAPI.onUpdateStatus(data => {
                if (!data) return;
                if (data.status === 'downloaded') {
                    // Update is 100% downloaded and ready to install -> Reveal floating pill!
                    widget.style.display = 'block';
                    statusText.textContent = 'Update Ready!';
                    pulseDot.style.background = '#10b981';
                    pulseDot.style.boxShadow = '0 0 10px #10b981';
                    panelBadge.textContent = 'Ready to Install';
                    panelBadge.style.color = '#34d399';
                    panelDesc.textContent = `Version v${data.version || ''} downloaded. Restart to apply.`;
                    progressContainer.style.display = 'none';
                    if (restartBtn) restartBtn.style.display = 'block';
                }
                else {
                    // Hidden during idle, checking, or up-to-date
                    widget.style.display = 'none';
                    if (data.status === 'available') {
                        statusText.textContent = 'Downloading...';
                        pulseDot.style.background = '#f59e0b';
                        panelBadge.textContent = 'v' + (data.version || '') + ' Available';
                        panelBadge.style.color = '#fbbf24';
                        panelDesc.textContent = 'New update is downloading in the background.';
                        progressContainer.style.display = 'block';
                    }
                    else if (data.status === 'not-available') {
                        pulseDot.style.background = '#10b981';
                        panelBadge.textContent = 'Up to Date';
                        panelBadge.style.color = '#34d399';
                        panelDesc.textContent = 'You are running the verified latest release.';
                        progressContainer.style.display = 'none';
                    }
                }
            });

            window.desktopAPI.onUpdateProgress(data => {
                progressContainer.style.display = 'block';
                const percent = Math.min(100, Math.max(0, data.percent || 0));
                statusText.textContent = percent + '%';
                progressBar.style.width = percent + '%';
                progressPercent.textContent = percent + '%';
            });
        }
    })();
    </script>

    <?php echo $extraJs ?? ''; ?>
</body>

</html>
