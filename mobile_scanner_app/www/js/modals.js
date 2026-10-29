/**
 * DevAltum POS Mobile Companion - Synchronous Universal Modals, Drawer & Dialog Engine
 */

function escapeHtml(str) {
    if (typeof str !== 'string') return String(str || '');
    return str.replace(/[&<>"']/g, (m) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[m]);
}

function triggerHaptic(pattern) {
    // 1. Check if user turned off vibration in settings
    const vibPref = (typeof AppState !== 'undefined' && typeof AppState.vibrationEnabled !== 'undefined')
        ? AppState.vibrationEnabled
        : (localStorage.getItem('pos_scanner_vib') !== 'false');

    if (vibPref === false) return;

    // 2. Resolve & normalize pattern
    let p = pattern;
    if (!p) {
        const patternKey = (typeof AppState !== 'undefined' && AppState.vibrationPattern)
            ? AppState.vibrationPattern
            : (localStorage.getItem('pos_scanner_vib_pattern') || 'crisp');
        const patternMap = {
            crisp: [35],
            medium: [65],
            double: [35, 45, 35],
            heavy: [110]
        };
        p = patternMap[patternKey] || [35];
    } else if (typeof p === 'number') {
        p = [p];
    }

    // 3. Hardware Mobile Vibration API
    try {
        if (typeof navigator !== 'undefined' && navigator.vibrate) {
            navigator.vibrate(p);
        }
    } catch(e) {}

    // 4. Multi-Platform Acoustic Sub-Bass Haptic Impulse (55Hz Physical Resonant Thump)
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (AudioCtx) {
            const ctx = new AudioCtx();
            if (ctx.state === 'suspended') ctx.resume();
            const now = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(55, now);
            osc.frequency.exponentialRampToValueAtTime(30, now + 0.04);
            gain.gain.setValueAtTime(0.35, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.04);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(now);
            osc.stop(now + 0.04);
        }
    } catch(e) {}
}

function openDialog(dialogId) {
    ensureUniversalModalsMounted();
    const d = document.getElementById(dialogId);
    if (d) {
        d.classList.add('show', 'active');
        triggerHaptic(20);
    }
}

function closeDialog(dialogId) {
    const d = document.getElementById(dialogId);
    if (d) {
        d.classList.remove('show', 'active');
        triggerHaptic(15);
    }
}

/**
 * Universal Custom Confirmation Modal Helper
 */
let universalConfirmCallback = null;

function showCustomConfirmDialog(title, message, onConfirm) {
    ensureUniversalModalsMounted();
    const titleEl = document.getElementById('del-modal-product-title');
    const msgEl = document.getElementById('del-modal-product-msg');

    if (titleEl) titleEl.textContent = title || 'Delete?';
    if (msgEl) msgEl.textContent = message || 'Are you sure you want to delete?';

    universalConfirmCallback = onConfirm;
    openDialog('modal-delete-product-confirm');
}

function handleUniversalModalConfirm() {
    closeDialog('modal-delete-product-confirm');
    if (typeof universalConfirmCallback === 'function') {
        const cb = universalConfirmCallback;
        universalConfirmCallback = null;
        cb();
        return;
    }
    if (typeof executeDeleteCurrentProduct === 'function') {
        executeDeleteCurrentProduct();
    }
}

/**
 * Sidebar Drawer Controller
 */
function openSidebarDrawer() {
    ensureUniversalModalsMounted();
    const store = (typeof AppState !== 'undefined' ? AppState.storeName : (localStorage.getItem('pos_scanner_store') || 'DevAltum Store'));
    const term = (typeof AppState !== 'undefined' ? AppState.terminalId : (localStorage.getItem('pos_scanner_terminal_id') || 'StarPOS-01'));
    
    const storeEl = document.getElementById('drawer-store-name');
    const termEl = document.getElementById('drawer-terminal-id');
    if (storeEl) storeEl.textContent = store;
    if (termEl) termEl.textContent = `Terminal: ${term}`;

    const drawer = document.getElementById('modal-sidebar-drawer');
    if (drawer) {
        drawer.classList.add('show', 'active');
        triggerHaptic(25);
    }
}

function closeSidebarDrawer() {
    const drawer = document.getElementById('modal-sidebar-drawer');
    if (drawer) {
        drawer.classList.remove('show', 'active');
        triggerHaptic(15);
    }
}

/**
 * Notifications Center Controller
 */
function openNotificationsModal() {
    ensureUniversalModalsMounted();
    openDialog('modal-notifications-center');
}

/**
 * Logout Flow
 */
function openLogoutModal() {
    ensureUniversalModalsMounted();
    openDialog('modal-logout-confirm');
}

function executeLogout() {
    closeDialog('modal-logout-confirm');
    closeSidebarDrawer();

    if (typeof AppState !== 'undefined') {
        AppState.isAuthenticated = false;
        AppState.pairToken = '';
        AppState.pairCode = '';
        AppState.storeName = '';
        AppState.terminalId = '';
    }

    localStorage.removeItem('pos_scanner_token');
    localStorage.removeItem('pos_scanner_code');
    localStorage.removeItem('pos_scanner_store');
    localStorage.removeItem('pos_scanner_terminal_id');
    localStorage.removeItem('pos_scanner_connected_at');

    showAppToast('Logged out successfully', 'info');
    setTimeout(() => {
        window.location.href = 'welcome.html';
    }, 400);
}

/**
 * In-App Floating Toast Notification System (Dynamic Island / iOS Luxury Style)
 */
function showAppToast(message, type = 'success', duration = 2800) {
    let container = document.getElementById('app-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'app-toast-container';
        container.className = 'app-toast-container';
        document.body.appendChild(container);
    }

    triggerHaptic(type === 'error' ? [40, 60, 40] : 25);

    const icons = {
        success: 'check_circle',
        error: 'error',
        info: 'info',
        warning: 'warning'
    };

    const item = document.createElement('div');
    item.className = `app-toast-item toast-${type}`;
    item.innerHTML = `
        <div class="app-toast-icon-wrap">
            <span class="material-symbols-rounded toast-icon">${icons[type] || 'info'}</span>
        </div>
        <div class="app-toast-text">${escapeHtml(message)}</div>
    `;

    container.appendChild(item);

    setTimeout(() => {
        item.classList.add('toast-leave');
        setTimeout(() => {
            if (item.parentNode) item.parentNode.removeChild(item);
        }, 280);
    }, duration);
}

/**
 * Synchronously Injects Full Universal Modal Library into DOM
 */
function ensureUniversalModalsMounted() {
    let container = document.getElementById('app-modal-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'app-modal-container';
        document.body.appendChild(container);
    }

    if (document.getElementById('modal-sidebar-drawer')) return; // Already present

    container.innerHTML = `
        <!-- 0. SLIDE-OUT SIDEBAR NAVIGATION DRAWER -->
        <div class="app-drawer-backdrop" id="modal-sidebar-drawer" onclick="if(event.target===this) closeSidebarDrawer()">
            <div class="app-drawer-panel">
                <div class="drawer-header-card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div style="width:48px; height:48px; border-radius:14px; background:#ffffff; overflow:hidden; display:grid; place-items:center; box-shadow:0 4px 14px rgba(0,0,0,0.15); border: 1.5px solid rgba(255,255,255,0.4); padding:2px;">
                            <img src="../assets/logo.png" alt="DevAltum Logo" style="width:100%; height:100%; object-fit:contain; border-radius:11px; display:block;">
                        </div>
                        <button type="button" class="header-action-btn" style="color:#ffffff; width:32px; height:32px;" onclick="closeSidebarDrawer()">
                            <span class="material-symbols-rounded" style="font-size:20px;">close</span>
                        </button>
                    </div>
                    <div style="margin-top:14px;">
                        <div style="font-size:16.5px; font-weight: 700; letter-spacing:0.1px;" id="drawer-store-name">DevAltum Store</div>
                        <div style="font-size:12px; color:#dbeafe; font-weight:700; margin-top:2px; display:flex; align-items:center; gap:6px;">
                            <span style="color:#4ade80;">&bull;</span>
                            <span id="drawer-terminal-id">Terminal: StarPOS-01</span>
                        </div>
                    </div>
                </div>

                <div class="drawer-nav-list">
                    <button type="button" class="drawer-nav-item" onclick="closeSidebarDrawer(); navigateTo('dashboard')">
                        <span class="material-symbols-rounded">dashboard</span>
                        <span>Dashboard</span>
                    </button>
                    <button type="button" class="drawer-nav-item" onclick="closeSidebarDrawer(); navigateTo('scanner')">
                        <span class="material-symbols-rounded">qr_code_scanner</span>
                        <span>Live Scanner</span>
                    </button>
                    <button type="button" class="drawer-nav-item" onclick="closeSidebarDrawer(); navigateTo('sales')">
                        <span class="material-symbols-rounded">receipt_long</span>
                        <span>Sales History</span>
                    </button>
                    <button type="button" class="drawer-nav-item" onclick="closeSidebarDrawer(); navigateTo('products')">
                        <span class="material-symbols-rounded">inventory_2</span>
                        <span>Products Directory</span>
                    </button>
                    <button type="button" class="drawer-nav-item" onclick="closeSidebarDrawer(); navigateTo('customers')">
                        <span class="material-symbols-rounded">group</span>
                        <span>Customers Ledger</span>
                    </button>
                    <button type="button" class="drawer-nav-item" onclick="closeSidebarDrawer(); navigateTo('settings')">
                        <span class="material-symbols-rounded">settings</span>
                        <span>Settings</span>
                    </button>
                    <div style="height:1px; background:#f1f5f9; margin:6px 8px;"></div>
                    <button type="button" class="drawer-nav-item" onclick="closeSidebarDrawer(); promptConfigureServer()">
                        <span class="material-symbols-rounded">dns</span>
                        <span>Server Host URL</span>
                    </button>
                    <button type="button" class="drawer-nav-item" onclick="closeSidebarDrawer(); openDialog('modal-help-center')">
                        <span class="material-symbols-rounded">support_agent</span>
                        <span>Help & Support</span>
                    </button>
                    <button type="button" class="drawer-nav-item" onclick="closeSidebarDrawer(); openDialog('modal-about-app')">
                        <span class="material-symbols-rounded">info</span>
                        <span>About App</span>
                    </button>
                </div>

                <div style="padding:14px 12px; border-top:1px solid #f1f5f9;">
                    <button type="button" class="drawer-nav-item" style="color:#ef4444;" onclick="openLogoutModal()">
                        <span class="material-symbols-rounded" style="color:#ef4444;">logout</span>
                        <span>Logout Terminal</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 1. NOTIFICATIONS CENTER MODAL -->
        <div class="app-modal-backdrop-layer" id="modal-notifications-center">
            <div class="app-modal-dialog-box" style="max-width:360px; padding:20px 16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="material-symbols-rounded" style="color:#0f6dff; font-size:22px;">notifications</span>
                        <h3 style="font-size:17px; font-weight: 700; color:#0f172a; margin:0;">Notifications</h3>
                    </div>
                    <button type="button" class="header-action-btn" style="width:32px; height:32px;" onclick="closeDialog('modal-notifications-center')">
                        <span class="material-symbols-rounded" style="font-size:18px;">close</span>
                    </button>
                </div>
                <div style="display:flex; flex-direction:column; gap:10px; text-align:left; max-height:60vh; overflow-y:auto;">
                    <div class="settings-item-card" style="padding:12px; border-left:3.5px solid #16a34a;">
                        <div>
                            <div style="font-size:13px; font-weight: 700; color:#0f172a;">POS Connected</div>
                            <div style="font-size:11.5px; color:#64748b; margin-top:2px;">Terminal linked with live database successfully.</div>
                        </div>
                        <span style="font-size:10.5px; font-weight: 700; color:#94a3b8;">Now</span>
                    </div>
                    <div class="settings-item-card" style="padding:12px; border-left:3.5px solid #0f6dff;">
                        <div>
                            <div style="font-size:13px; font-weight: 700; color:#0f172a;">Inventory Synchronized</div>
                            <div style="font-size:11.5px; color:#64748b; margin-top:2px;">All products and prices are up to date.</div>
                        </div>
                        <span style="font-size:10.5px; font-weight: 700; color:#94a3b8;">Today</span>
                    </div>
                </div>
                <button type="button" class="btn-primary-action-block" style="margin-top:14px; padding:11px;" onclick="closeDialog('modal-notifications-center')">
                    Mark All as Read
                </button>
            </div>
        </div>

        <!-- 2. CURRENCY PICKER MODAL (WITH INSTANT SEARCH & FLAGS) -->
        <div class="app-modal-backdrop-layer" id="modal-currency-picker">
            <div class="app-modal-dialog-box" style="max-width:380px; max-height:86vh; display:flex; flex-direction:column; padding:20px 16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="material-symbols-rounded" style="color:#0f6dff; font-size:22px;">payments</span>
                        <h3 style="font-size:16.5px; font-weight: 700; color:#0f172a; margin:0;">Select Currency</h3>
                    </div>
                    <button type="button" class="header-action-btn" style="width:32px; height:32px;" onclick="closeDialog('modal-currency-picker')">
                        <span class="material-symbols-rounded" style="font-size:18px;">close</span>
                    </button>
                </div>
                <!-- Search Bar -->
                <div style="position:relative; margin-bottom:12px;">
                    <span class="material-symbols-rounded" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:18px; color:#94a3b8; pointer-events:none;">search</span>
                    <input type="text" id="currency-picker-search-input" class="form-control-pro" placeholder="Search country or currency..." style="padding-left:38px; font-size:13px; border-radius:12px;" oninput="filterCurrencyPickerList(this.value)">
                </div>
                <div style="overflow-y:auto; display:flex; flex-direction:column; gap:4px; text-align:left; flex:1;" id="currency-options-list"></div>
            </div>
        </div>

        <!-- 2.1 SOUND TONE PICKER MODAL -->
        <div class="app-modal-backdrop-layer" id="modal-sound-tone-picker">
            <div class="app-modal-dialog-box" style="max-width:380px; max-height:86vh; display:flex; flex-direction:column; padding:20px 16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="material-symbols-rounded" style="color:#0f6dff; font-size:22px;">volume_up</span>
                        <h3 style="font-size:16.5px; font-weight: 700; color:#0f172a; margin:0;">Product Scan Sound</h3>
                    </div>
                    <button type="button" class="header-action-btn" style="width:32px; height:32px;" onclick="closeDialog('modal-sound-tone-picker')">
                        <span class="material-symbols-rounded" style="font-size:18px;">close</span>
                    </button>
                </div>
                <p style="font-size:12px; color:#64748b; margin-bottom:14px; text-align:left;">Choose the audio tone played when scanning or adding products:</p>
                <div style="overflow-y:auto; display:flex; flex-direction:column; gap:4px; text-align:left;" id="sound-tone-options-list"></div>
            </div>
        </div>

        <!-- 2.2 VIBRATION (HAPTIC) PICKER MODAL -->
        <div class="app-modal-backdrop-layer" id="modal-vibration-picker">
            <div class="app-modal-dialog-box" style="max-width:380px; max-height:86vh; display:flex; flex-direction:column; padding:20px 16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="material-symbols-rounded" style="color:#f59e0b; font-size:22px;">vibration</span>
                        <h3 style="font-size:16.5px; font-weight: 700; color:#0f172a; margin:0;">Haptic Vibration Style</h3>
                    </div>
                    <button type="button" class="header-action-btn" style="width:32px; height:32px;" onclick="closeDialog('modal-vibration-picker')">
                        <span class="material-symbols-rounded" style="font-size:18px;">close</span>
                    </button>
                </div>
                <p style="font-size:12px; color:#64748b; margin-bottom:14px; text-align:left;">Select tactile vibration feedback pattern on mobile device:</p>
                <div style="overflow-y:auto; display:flex; flex-direction:column; gap:4px; text-align:left;" id="vibration-options-list"></div>
            </div>
        </div>

        <!-- 3. SERVER URL CONFIG MODAL -->
        <div class="app-modal-backdrop-layer" id="modal-server-config">
            <div class="app-modal-dialog-box" style="max-width:360px; padding:20px 16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <h3 style="font-size:17px; font-weight: 700; color:#0f172a; display:flex; align-items:center; gap:6px;">
                        <span class="material-symbols-rounded" style="color:#0f6dff; font-size:20px;">dns</span>
                        <span>POS Server URL</span>
                    </h3>
                    <button type="button" class="header-action-btn" style="width:32px; height:32px;" onclick="closeDialog('modal-server-config')">
                        <span class="material-symbols-rounded" style="font-size:18px;">close</span>
                    </button>
                </div>
                <p style="font-size:12px; color:#64748b; margin-bottom:14px; text-align:left;">Enter your Desktop POS IP address or domain (e.g. <code>http://192.168.1.10/pospro</code> or <code>http://localhost/pospro</code>):</p>
                <div style="margin-bottom:14px;">
                    <input type="url" id="input-server-host-url" class="form-control-pro" placeholder="http://192.168.1.10/pospro" style="padding-left:14px; font-size:13px;">
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="btn-neutral-outline" style="flex:1; padding:11px; font-size:12.5px; font-weight: 700; border-radius:12px;" onclick="closeDialog('modal-server-config')">Cancel</button>
                    <button type="button" class="btn-primary-action-block" style="flex:1; padding:11px; font-size:12.5px; font-weight: 700; border-radius:12px; margin:0;" onclick="saveServerHostUrl()">Save URL</button>
                </div>
            </div>
        </div>

        <!-- 4. LOGOUT CONFIRMATION MODAL -->
        <div class="app-modal-backdrop-layer" id="modal-logout-confirm">
            <div class="app-modal-dialog-box" style="padding:22px 18px; max-width:340px;">
                <div style="width:52px; height:52px; border-radius:50%; background:#fee2e2; color:#ef4444; display:grid; place-items:center; margin:0 auto 12px auto;">
                    <span class="material-symbols-rounded" style="font-size:28px;">logout</span>
                </div>
                <h3 style="font-size:17px; font-weight: 700; margin-bottom:4px; color:#0f172a;">Disconnect Device?</h3>
                <p style="font-size:12.5px; color:#64748b; margin-bottom:18px;">Are you sure you want to disconnect from this POS terminal?</p>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn-neutral-outline" onclick="closeDialog('modal-logout-confirm')" style="flex:1; padding:12px; font-size:13px; font-weight: 700; border-radius:14px; margin:0;">
                        Cancel
                    </button>
                    <button type="button" class="btn-primary-action-block" onclick="executeLogout()" style="flex:1; padding:12px; font-size:13px; font-weight: 700; border-radius:14px; background:#ef4444; border-color:#dc2626; box-shadow:0 4px 12px rgba(239,68,68,0.25); margin:0;">
                        Disconnect
                    </button>
                </div>
            </div>
        </div>

        <!-- 4.05 DELETE PRODUCT CONFIRMATION MODAL -->
        <div class="app-modal-backdrop-layer" id="modal-delete-product-confirm">
            <div class="app-modal-dialog-box" style="padding:22px 18px; max-width:340px; text-align:center;">
                <div style="width:52px; height:52px; border-radius:50%; background:#fee2e2; color:#ef4444; display:grid; place-items:center; margin:0 auto 12px auto;">
                    <span class="material-symbols-rounded" style="font-size:28px;">delete_forever</span>
                </div>
                <h3 style="font-size:17px; font-weight: 700; margin-bottom:6px; color:#0f172a;" id="del-modal-product-title">Delete Product?</h3>
                <p style="font-size:13px; color:#64748b; margin-bottom:18px;" id="del-modal-product-msg">Are you sure you want to delete?</p>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn-neutral-outline" onclick="closeDialog('modal-delete-product-confirm')" style="flex:1; padding:12px; font-size:13px; font-weight: 700; border-radius:14px; margin:0;">
                        Cancel
                    </button>
                    <button type="button" class="btn-primary-action-block" onclick="handleUniversalModalConfirm()" style="flex:1; padding:12px; font-size:13px; font-weight: 700; border-radius:14px; background:#ef4444; border-color:#dc2626; box-shadow:0 4px 12px rgba(239,68,68,0.25); margin:0;">
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- 4.1 CACHE & DATA STORAGE MANAGER MODAL -->
        <div class="app-modal-backdrop-layer" id="modal-backup-restore">
            <div class="app-modal-dialog-box" style="max-width:360px; padding:22px 18px; text-align:left;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color:#fff; display:grid; place-items:center;">
                            <span class="material-symbols-rounded" style="font-size:18px;">cloud_sync</span>
                        </div>
                        <h3 style="font-size:16.5px; font-weight: 700; color:#0f172a; margin:0;">Cache & Storage</h3>
                    </div>
                    <button type="button" class="header-action-btn" style="width:30px; height:30px;" onclick="closeDialog('modal-backup-restore')">
                        <span class="material-symbols-rounded" style="font-size:18px;">close</span>
                    </button>
                </div>

                <!-- Storage Info Card -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:14px; margin-bottom:14px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <span style="font-size:12px; color:#64748b; font-weight:600;">Local App Storage</span>
                        <strong style="font-size:13px; color:#0f172a;" id="cache-stat-usage">24.5 KB</strong>
                    </div>
                    <div style="width:100%; height:6px; background:#e2e8f0; border-radius:999px; overflow:hidden; margin-bottom:10px;">
                        <div id="cache-stat-progress" style="width:12%; height:100%; background:linear-gradient(90deg, #06b6d4, #0f6dff); border-radius:999px;"></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:11.5px; color:#64748b;">
                        <span>Last Synchronized:</span>
                        <strong style="color:#10b981;" id="cache-stat-last-sync">Just now</strong>
                    </div>
                </div>

                <!-- Actions List -->
                <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:14px;">
                    <button type="button" class="btn-primary-action-block" id="btn-force-sync" onclick="executeForceSync()" style="padding:12px; font-size:13px; margin:0; display:flex; align-items:center; justify-content:center; gap:8px;">
                        <span class="material-symbols-rounded" id="sync-btn-icon" style="font-size:18px;">sync</span>
                        <span id="sync-btn-text">Sync All Catalog & Metrics</span>
                    </button>

                    <button type="button" class="btn-neutral-outline" onclick="executeClearCache()" style="padding:11px; font-size:12.5px; font-weight:700; border-radius:14px; display:flex; align-items:center; justify-content:center; gap:6px; color:#ef4444; border-color:#fecaca; background:#fff;">
                        <span class="material-symbols-rounded" style="font-size:17px; color:#ef4444;">delete_sweep</span>
                        <span>Clear Temporary Cache</span>
                    </button>
                </div>

                <div style="font-size:11px; color:#94a3b8; line-height:1.4; text-align:center;">
                    Syncing updates all prices, stock inventory, and customers locally for ultra-fast offline scanning.
                </div>
            </div>
        </div>

        <!-- 5. UNIVERSAL INTERACTIVE CALENDAR MODAL (DASHBOARD & SALES) -->
        <div class="app-modal-backdrop-layer" id="modal-dashboard-calendar">
            <div class="app-modal-dialog-box" style="max-width:340px; padding:18px 16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div style="font-size:15px; font-weight: 700; color:#0f172a; display:flex; align-items:center; gap:6px;">
                        <span class="material-symbols-rounded" style="color:#0f6dff; font-size:20px;">calendar_month</span>
                        <span id="cal-modal-title">Select Date Filter</span>
                    </div>
                    <button type="button" class="header-action-btn" style="width:30px; height:30px;" onclick="closeDialog('modal-dashboard-calendar')">
                        <span class="material-symbols-rounded" style="font-size:18px;">close</span>
                    </button>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:6px 4px; background:#f8fafc; border-radius:12px; margin-bottom:12px;">
                    <button type="button" class="header-action-btn" style="width:30px; height:30px;" onclick="prevUniversalCalMonth()">
                        <span class="material-symbols-rounded" style="font-size:16px;">chevron_left</span>
                    </button>
                    <div style="font-size:13.5px; font-weight: 700; color:#0f172a;" id="universal-cal-month-year">August 2026</div>
                    <button type="button" class="header-action-btn" style="width:30px; height:30px;" onclick="nextUniversalCalMonth()">
                        <span class="material-symbols-rounded" style="font-size:16px;">chevron_right</span>
                    </button>
                </div>
                <div style="display:grid; grid-template-columns:repeat(7, 1fr); text-align:center; font-size:11px; font-weight: 700; color:#94a3b8; margin-bottom:6px;">
                    <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                </div>
                <div id="universal-cal-days-grid" style="display:grid; grid-template-columns:repeat(7, 1fr); gap:4px;"></div>
                <div style="display:flex; justify-content:flex-end; margin-top:12px; padding-top:8px; border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn-primary-action-block" style="padding:6px 16px; font-size:12px; font-weight: 700; border-radius:10px; width:auto; margin:0;" onclick="selectUniversalCalToday()">Today</button>
                </div>
            </div>
        </div>

        <!-- 6. HELP CENTER MODAL -->
        <div class="app-modal-backdrop-layer" id="modal-help-center">
            <div class="app-modal-dialog-box" style="max-width:350px; padding:20px 16px;">
                <div style="width:54px; height:54px; border-radius:50%; background:#eef4ff; color:#0f6dff; display:grid; place-items:center; margin:0 auto 12px auto;">
                    <span class="material-symbols-rounded" style="font-size:30px;">support_agent</span>
                </div>
                <h3 style="font-size:17.5px; font-weight: 700; margin-bottom:4px; color:#0f172a;">Help & Support</h3>
                <p style="font-size:12px; color:#64748b; margin-bottom:16px;">Assistance with POS device pairing, live scanning & ledger.</p>
                <div style="display:flex; flex-direction:column; gap:8px; text-align:left; font-size:12.5px; font-weight: 700; margin-bottom:16px;">
                    <div class="settings-item-card" style="padding:10px 12px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span class="material-symbols-rounded" style="color:#0f6dff; font-size:18px;">support_agent</span>
                            <span>Support Helpline</span>
                        </div>
                        <span style="font-weight: 700; color:#0f172a;">+92 312 3456789</span>
                    </div>
                    <div class="settings-item-card" style="padding:10px 12px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <svg style="width:18px; height:18px; fill:#25D366; flex-shrink:0;" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.05 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            <span>WhatsApp Support</span>
                        </div>
                        <span style="color:#16a34a; font-weight: 700;">Online 24/7</span>
                    </div>
                </div>
                <button type="button" class="btn-primary-action-block" style="margin:0; padding:11px;" onclick="closeDialog('modal-help-center')">OK</button>
            </div>
        </div>

        <!-- 7. ABOUT APP MODAL -->
        <div class="app-modal-backdrop-layer" id="modal-about-app">
            <div class="app-modal-dialog-box" style="max-width:340px; padding:22px 18px; text-align:center;">
                <div style="width:68px; height:68px; border-radius:18px; background:#ffffff; overflow:hidden; display:grid; place-items:center; margin:0 auto 14px auto; box-shadow:0 6px 18px rgba(15,109,255,0.15); border: 1.5px solid #e2e8f0; padding:6px;">
                    <img src="../assets/logo.png" alt="DevAltum Logo" style="width:100%; height:100%; object-fit:contain; border-radius:12px; display:block;" onerror="this.src='../../assets/logo.png'">
                </div>
                <h3 style="font-size:18px; font-weight: 700; margin-bottom:2px; color:#0f172a;">DevAltum POS</h3>
                <div style="display:inline-block; background:#eff6ff; color:#0f6dff; border:1px solid #bfdbfe; padding:3px 10px; border-radius:999px; font-size:11.5px; font-weight: 700; margin-bottom:12px;">
                    v2.0 Pro Enterprise Edition
                </div>
                <p style="font-size:12px; color:#64748b; line-height:1.5; margin-bottom:16px;">Next-generation high-speed companion app for lightning-fast barcode scanning, live sales metrics, and catalog access.</p>
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:10px 12px; font-size:11.5px; color:#64748b; margin-bottom:16px; text-align:left;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                        <span>Engine Version:</span>
                        <strong style="color:#0f172a;">v2.0.4 Pro</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span>Hardware Acceleration:</span>
                        <strong style="color:#10b981;">Active</strong>
                    </div>
                </div>
                <button type="button" class="btn-primary-action-block" style="margin:0; padding:11px;" onclick="closeDialog('modal-about-app')">Close</button>
            </div>
        </div>

        <!-- 8. MODAL: WEIGHT / FRACTIONAL QUANTITY CALCULATOR -->
        <div class="app-modal-backdrop-layer" id="modal-weight-calculator">
            <div class="app-modal-dialog-box" style="max-width:340px; padding:20px 18px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                    <div>
                        <div style="font-size:16px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:6px;">
                            <span class="material-symbols-rounded" style="color:#16a34a; font-size:20px;">scale</span>
                            <span>Weight Calculator</span>
                        </div>
                        <div style="font-size:12px; font-weight:700; color:#0f6dff; margin-top:2px;" id="weight-modal-item-title">Product Name</div>
                        <div style="font-size:11px; color:#64748b;" id="weight-modal-base-rate">Base Rate: Rs 1,000 / KG</div>
                    </div>
                    <button type="button" class="header-action-btn" style="width:30px; height:30px;" onclick="closeDialog('modal-weight-calculator')">
                        <span class="material-symbols-rounded" style="font-size:18px;">close</span>
                    </button>
                </div>

                <div class="weight-unit-toggle-deck">
                    <button type="button" class="weight-unit-tab active" id="tab-weight-g" onclick="setWeightTab('g')">Grams (g)</button>
                    <button type="button" class="weight-unit-tab" id="tab-weight-kg" onclick="setWeightTab('kg')">Kilograms (kg)</button>
                </div>

                <div class="product-input-wrap" style="margin-bottom:12px;">
                    <div class="product-input-label">
                        <span>Enter Weight (<span id="weight-input-unit-label">G</span>)</span>
                    </div>
                    <div class="input-with-icon-box">
                        <span class="material-symbols-rounded input-icon-prefix">balance</span>
                        <input type="number" step="any" id="weight-modal-input" class="form-control-pro" placeholder="e.g. 200" oninput="recalcWeightFormula()">
                    </div>
                </div>

                <div class="weight-calc-display-box">
                    <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Calculated Item Total</div>
                    <div class="weight-calc-result-price" id="weight-result-price">Rs 0</div>
                    <div class="weight-calc-formula-sub" id="weight-result-formula">Enter weight above to calculate</div>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn-neutral-outline" style="flex:1; padding:12px; border-radius:14px; margin:0;" onclick="closeDialog('modal-weight-calculator')">Cancel</button>
                    <button type="button" class="btn-primary-action-block" style="flex:1; padding:12px; border-radius:14px; background:#16a34a; border-color:#15803d; margin:0;" onclick="applyWeightCalculator()">Apply Weight</button>
                </div>
            </div>
        </div>

        <!-- 9. MODAL: HELD ORDERS (CART MANAGER) -->
        <div class="app-modal-backdrop-layer" id="modal-held-orders">
            <div class="app-modal-dialog-box" style="max-width:360px; padding:20px 18px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                    <div style="font-size:16px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:6px;">
                        <span class="material-symbols-rounded" style="color:#f59e0b; font-size:22px;">pause_circle</span>
                        <span>Held Orders (Carts)</span>
                    </div>
                    <button type="button" class="header-action-btn" style="width:30px; height:30px;" onclick="closeDialog('modal-held-orders')">
                        <span class="material-symbols-rounded" style="font-size:18px;">close</span>
                    </button>
                </div>

                <div id="held-orders-list-mount" style="max-height:280px; overflow-y:auto;"></div>

                <div id="held-orders-empty" style="display:none; text-align:center; padding:30px 10px; color:#94a3b8;">
                    <span class="material-symbols-rounded" style="font-size:36px; margin-bottom:6px;">shopping_basket</span>
                    <div style="font-size:13px; font-weight:700;">No Held Orders</div>
                    <div style="font-size:11px; margin-top:2px;">Tap "Hold Order" on the cart screen to save a cart for later.</div>
                </div>

                <button type="button" class="btn-neutral-outline" style="width:100%; padding:11px; border-radius:12px; margin-top:12px;" onclick="closeDialog('modal-held-orders')">Close</button>
            </div>
        </div>

        <!-- 10. MODAL: CUSTOMER CHECKOUT SHEET -->
        <div class="app-modal-backdrop-layer" id="modal-customer-checkout-sheet">
            <div class="app-modal-dialog-box" style="max-width:360px; padding:22px 18px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px;">
                    <div>
                        <div style="font-size:16.5px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:6px;">
                            <span class="material-symbols-rounded" style="color:#0f6dff; font-size:22px;">person_add</span>
                            <span>Customer Details</span>
                        </div>
                        <div style="font-size:11.5px; color:#64748b; margin-top:2px;">Optional &bull; Enter info or tap Skip & Print</div>
                    </div>
                    <button type="button" class="header-action-btn" style="width:30px; height:30px;" onclick="closeDialog('modal-customer-checkout-sheet')">
                        <span class="material-symbols-rounded" style="font-size:18px;">close</span>
                    </button>
                </div>

                <div class="product-input-wrap" style="margin-bottom:10px;">
                    <div class="product-input-label"><span>Customer Name (Optional)</span></div>
                    <div class="input-with-icon-box">
                        <span class="material-symbols-rounded input-icon-prefix">badge</span>
                        <input type="text" id="checkout-cust-name" class="form-control-pro" placeholder="Walk-in Customer">
                    </div>
                </div>

                <div class="product-input-wrap" style="margin-bottom:16px;">
                    <div class="product-input-label"><span>Phone Number (Optional)</span></div>
                    <div class="input-with-icon-box">
                        <span class="material-symbols-rounded input-icon-prefix">call</span>
                        <input type="tel" id="checkout-cust-phone" class="form-control-pro" placeholder="03001234567">
                    </div>
                </div>

                <!-- Dual Action Buttons -->
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <button type="button" class="btn-primary-action-block" style="padding:13px; font-size:13.5px; font-weight:800; border-radius:14px; margin:0;" onclick="executeCheckout(false)">
                        <span class="material-symbols-rounded" style="font-size:18px;">print</span>
                        <span>Complete & Print Invoice</span>
                    </button>
                    <button type="button" class="btn-neutral-outline" style="padding:11px; font-size:12.5px; font-weight:700; border-radius:14px; margin:0; border-color:#cbd5e1;" onclick="executeCheckout(true)">
                        <span class="material-symbols-rounded" style="font-size:16px;">fast_forward</span>
                        <span>Skip & Print (Walk-in Customer)</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 11. MODAL: SALE COMPLETED & THERMAL RECEIPT -->
        <div class="app-modal-backdrop-layer" id="modal-sale-complete-receipt">
            <div class="app-modal-dialog-box" style="max-width:380px; padding:18px 16px; max-height:92vh; display:flex; flex-direction:column;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid #e2e8f0;">
                    <div>
                        <div style="font-size:15.5px; font-weight:800; color:#10b981; display:flex; align-items:center; gap:5px;">
                            <span class="material-symbols-rounded" style="font-size:20px;">check_circle</span>
                            <span>Sale Completed!</span>
                        </div>
                        <div style="font-size:11px; color:#64748b;">Invoice: <strong id="receipt-modal-inv-no" style="color:#0f172a;">---</strong> &bull; Total: <strong id="receipt-modal-total" style="color:#0f6dff;">Rs 0</strong></div>
                    </div>
                    <button type="button" class="header-action-btn" style="width:30px; height:30px;" onclick="closeDialog('modal-sale-complete-receipt')">
                        <span class="material-symbols-rounded" style="font-size:18px;">close</span>
                    </button>
                </div>

                <div style="flex:1; min-height:280px; max-height:360px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:12px; background:#fff; margin-bottom:12px;">
                    <iframe id="complete-receipt-frame" src="" style="width:100%; height:100%; min-height:320px; border:none;"></iframe>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                    <button type="button" class="btn-primary-action-block" style="padding:11px; font-size:12.5px; font-weight:700; border-radius:12px; margin:0; display:flex; align-items:center; justify-content:center; gap:6px;" onclick="printReceiptFromModal()">
                        <span class="material-symbols-rounded" style="font-size:18px;">print</span>
                        <span>Print Receipt</span>
                    </button>
                    <button type="button" class="btn-cust-wa-green" style="padding:11px; font-size:12.5px; font-weight:700; border-radius:12px; margin:0; display:flex; align-items:center; justify-content:center; gap:6px; background:#25D366; color:#fff; border:none; box-shadow:0 4px 12px rgba(37,211,102,0.28);" onclick="shareReceiptOnWhatsApp()">
                        <svg style="width:18px; height:18px; fill:#fff; flex-shrink:0;" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.05 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        <span>WhatsApp</span>
                    </button>
                </div>

                <button type="button" class="btn-neutral-outline" style="width:100%; padding:10px; font-size:12.5px; font-weight:700; border-radius:12px; margin:0; display:flex; align-items:center; justify-content:center; gap:6px;" onclick="startNewSaleFromCart()">
                    <span class="material-symbols-rounded" style="font-size:16px;">qr_code_scanner</span>
                    <span>New Sale (Back to Scanner)</span>
                </button>
            </div>
        </div>

        <!-- 12. MODAL: CLEAR CART CONFIRMATION -->
        <div class="app-modal-backdrop-layer" id="modal-clear-cart-confirm">
            <div class="app-modal-dialog-box" style="padding:22px 18px; max-width:340px; text-align:center;">
                <div style="width:54px; height:54px; border-radius:50%; background:#fee2e2; color:#ef4444; display:grid; place-items:center; margin:0 auto 12px auto;">
                    <span class="material-symbols-rounded" style="font-size:28px;">remove_shopping_cart</span>
                </div>
                <h3 style="font-size:17.5px; font-weight:800; margin-bottom:6px; color:#0f172a;">Clear Cart?</h3>
                <p style="font-size:13px; color:#64748b; margin-bottom:18px; line-height:1.4;">Are you sure you want to remove all items from your checkout cart?</p>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn-neutral-outline" onclick="closeDialog('modal-clear-cart-confirm')" style="flex:1; padding:12px; font-size:13px; font-weight:700; border-radius:14px; margin:0;">
                        Cancel
                    </button>
                    <button type="button" class="btn-primary-action-block" onclick="executeConfirmedClearCart()" style="flex:1; padding:12px; font-size:13px; font-weight:700; border-radius:14px; background:#ef4444; border-color:#dc2626; box-shadow:0 4px 12px rgba(239,68,68,0.25); margin:0;">
                        Clear All
                    </button>
                </div>
            </div>
        </div>

        <!-- 13. MODAL: CREDIT DUE DATE CALENDAR -->
        <div class="app-modal-backdrop-layer" id="modal-cart-credit-calendar">
            <div class="app-modal-dialog-box" style="max-width:340px; padding:18px 16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div style="font-size:15px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:6px;">
                        <span class="material-symbols-rounded" style="color:#0f6dff; font-size:20px;">event_upcoming</span>
                        <span>Select Credit Due Date</span>
                    </div>
                    <button type="button" class="header-action-btn" style="width:30px; height:30px;" onclick="closeDialog('modal-cart-credit-calendar')">
                        <span class="material-symbols-rounded" style="font-size:18px;">close</span>
                    </button>
                </div>

                <div style="display:flex; gap:6px; margin-bottom:12px;">
                    <button type="button" class="btn-neutral-outline" style="flex:1; padding:7px 4px; font-size:11px; font-weight:700; border-radius:8px; margin:0;" onclick="selectCreditQuickDays(7)">+7 Days</button>
                    <button type="button" class="btn-neutral-outline" style="flex:1; padding:7px 4px; font-size:11px; font-weight:700; border-radius:8px; margin:0;" onclick="selectCreditQuickDays(15)">+15 Days</button>
                    <button type="button" class="btn-neutral-outline" style="flex:1; padding:7px 4px; font-size:11px; font-weight:700; border-radius:8px; margin:0;" onclick="selectCreditQuickDays(30)">+30 Days</button>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; padding:6px 8px; background:#f8fafc; border-radius:12px; margin-bottom:10px;">
                    <button type="button" class="header-action-btn" style="width:28px; height:28px;" onclick="prevCartCalMonth()">
                        <span class="material-symbols-rounded" style="font-size:16px;">chevron_left</span>
                    </button>
                    <div style="font-size:13px; font-weight:700; color:#0f172a;" id="cart-cal-month-year">March 2026</div>
                    <button type="button" class="header-action-btn" style="width:28px; height:28px;" onclick="nextCartCalMonth()">
                        <span class="material-symbols-rounded" style="font-size:16px;">chevron_right</span>
                    </button>
                </div>

                <div class="calendar-weekdays-grid" style="display:grid; grid-template-columns:repeat(7,1fr); text-align:center; font-size:10.5px; font-weight:700; color:#94a3b8; margin-bottom:6px;">
                    <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                </div>

                <div id="cart-credit-cal-grid" style="display:grid; grid-template-columns:repeat(7,1fr); gap:3px; margin-bottom:12px;"></div>

                <button type="button" class="btn-neutral-outline" style="width:100%; padding:10px; font-size:12px; font-weight:700; border-radius:12px; margin:0;" onclick="closeDialog('modal-cart-credit-calendar')">Close</button>
            </div>
        </div>
    `;
}

/**
 * Server Host URL Actions
 */
function promptConfigureServer() {
    ensureUniversalModalsMounted();
    const input = document.getElementById('input-server-host-url');
    if (input) {
        input.value = (typeof AppState !== 'undefined' ? AppState.serverUrl : (localStorage.getItem('pos_scanner_server') || 'http://localhost/pospro'));
    }
    openDialog('modal-server-config');
}

function saveServerHostUrl() {
    const input = document.getElementById('input-server-host-url');
    if (!input) return;

    let url = (input.value || '').trim();
    if (!url) {
        showAppToast('Please enter a valid URL', 'error');
        return;
    }

    if (!url.startsWith('http://') && !url.startsWith('https://')) {
        url = 'http://' + url;
    }
    url = url.replace(/\/+$/, '');

    localStorage.setItem('pos_scanner_server', url);
    if (typeof AppState !== 'undefined') {
        AppState.serverUrl = url;
    }

    closeDialog('modal-server-config');
    showAppToast(`Server Host updated: ${url}`, 'success');
}

/**
 * Currency Picker Actions (With Search & Country Flags)
 */
function openCurrencyPickerModal() {
    ensureUniversalModalsMounted();
    renderCurrencyPickerList('');
    openDialog('modal-currency-picker');
    const searchInput = document.getElementById('currency-picker-search-input');
    if (searchInput) {
        searchInput.value = '';
        setTimeout(() => searchInput.focus(), 150);
    }
}

function filterCurrencyPickerList(query) {
    const list = document.getElementById('currency-options-list');
    if (!list) return;

    const currentCode = (typeof AppState !== 'undefined' ? AppState.currencyCode : (localStorage.getItem('pos_scanner_currency_code') || 'PKR'));
    const currencies = (typeof SupportedCurrencies !== 'undefined' ? SupportedCurrencies : [
        { code: 'PKR', symbol: 'Rs', name: 'Pakistani Rupee', flag: '🇵🇰', country: 'Pakistan' },
        { code: 'USD', symbol: '$', name: 'US Dollar', flag: '🇺🇸', country: 'United States' },
        { code: 'SAR', symbol: 'SAR', name: 'Saudi Riyal', flag: '🇸🇦', country: 'Saudi Arabia' },
        { code: 'AED', symbol: 'AED', name: 'UAE Dirham', flag: '🇦🇪', country: 'United Arab Emirates' },
        { code: 'GBP', symbol: '£', name: 'British Pound', flag: '🇬🇧', country: 'United Kingdom' },
        { code: 'EUR', symbol: '€', name: 'Euro', flag: '🇪🇺', country: 'European Union' },
        { code: 'INR', symbol: '₹', name: 'Indian Rupee', flag: '🇮🇳', country: 'India' },
        { code: 'CAD', symbol: 'CA$', name: 'Canadian Dollar', flag: '🇨🇦', country: 'Canada' },
        { code: 'AUD', symbol: 'AU$', name: 'Australian Dollar', flag: '🇦🇺', country: 'Australia' },
        { code: 'TRY', symbol: '₺', name: 'Turkish Lira', flag: '🇹🇷', country: 'Turkey' },
        { code: 'QAR', symbol: 'QAR', name: 'Qatari Riyal', flag: '🇶🇦', country: 'Qatar' }
    ]);

    const q = (query || '').toLowerCase().trim();
    const filtered = currencies.filter(c => {
        if (!q) return true;
        return (c.code && c.code.toLowerCase().includes(q)) ||
               (c.name && c.name.toLowerCase().includes(q)) ||
               (c.country && c.country.toLowerCase().includes(q)) ||
               (c.symbol && c.symbol.toLowerCase().includes(q));
    });

    if (filtered.length === 0) {
        list.innerHTML = `
            <div style="text-align:center; padding:24px 12px; color:#94a3b8;">
                <span class="material-symbols-rounded" style="font-size:36px; opacity:0.6;">search_off</span>
                <div style="font-size:13px; font-weight:600; margin-top:6px;">No currency found for "${escapeHtml(query)}"</div>
            </div>
        `;
        return;
    }

    list.innerHTML = filtered.map(c => {
        const isSelected = (currentCode === c.code);
        return `
            <div class="settings-item-card clickable" 
                 onclick="selectAppCurrency('${c.code}', '${c.symbol}')"
                 style="cursor:pointer; padding:12px 14px; margin-bottom:8px; border-color:${isSelected ? '#0f6dff' : '#e2e8f0'}; background:${isSelected ? '#eff6ff' : '#ffffff'};">
                <div class="settings-item-left" style="gap:12px;">
                    <div style="font-size:26px; line-height:1; display:grid; place-items:center; width:34px; height:34px; flex-shrink:0; background:transparent; border:none; box-shadow:none;">
                        <span>${c.flag || '🌐'}</span>
                    </div>
                    <div>
                        <div class="settings-item-title" style="font-size:13.5px; color:#0f172a; display:flex; align-items:center; gap:6px;">
                            <span>${c.name}</span>
                        </div>
                        <div class="settings-item-sub" style="font-size:11.5px; color:#64748b;">${c.code} &bull; ${c.country || c.code}</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span class="settings-pill-badge" style="background:${isSelected ? '#0f6dff' : '#f1f5f9'}; color:${isSelected ? '#ffffff' : '#0f172a'}; border-color:${isSelected ? '#0f6dff' : '#e2e8f0'}; font-size:13px; font-weight:700; padding:4px 10px;">
                        ${c.symbol}
                    </span>
                    ${isSelected ? '<span class="material-symbols-rounded" style="color:#0f6dff; font-size:22px;">check_circle</span>' : ''}
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Universal Interactive Calendar Controller for Dashboard & Sales
 */
let uCalDate = new Date();
let uCalMonth = uCalDate.getMonth();
let uCalYear = uCalDate.getFullYear();
let uCalSelectedIso = '';
let uCalCallback = null;

function openUniversalCalendarModal(title = 'Select Date Filter', onSelect = null) {
    ensureUniversalModalsMounted();
    const titleEl = document.getElementById('cal-modal-title');
    if (titleEl) titleEl.textContent = title;

    uCalCallback = onSelect;
    renderUniversalCalendar();
    openDialog('modal-dashboard-calendar');
}

function renderUniversalCalendar() {
    const monthYearEl = document.getElementById('universal-cal-month-year');
    const gridEl = document.getElementById('universal-cal-days-grid');
    if (!monthYearEl || !gridEl) return;

    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    monthYearEl.textContent = `${monthNames[uCalMonth]} ${uCalYear}`;

    gridEl.innerHTML = '';
    const firstDay = new Date(uCalYear, uCalMonth, 1).getDay();
    const daysInMonth = new Date(uCalYear, uCalMonth + 1, 0).getDate();

    for (let i = 0; i < firstDay; i++) {
        const emptyCell = document.createElement('div');
        gridEl.appendChild(emptyCell);
    }

    const todayStr = new Date().toISOString().split('T')[0];

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${uCalYear}-${String(uCalMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'cal-day-cell';
        btn.textContent = d;

        if (dateStr === todayStr) btn.classList.add('today');
        if (dateStr === uCalSelectedIso) btn.classList.add('selected');

        btn.onclick = () => {
            uCalSelectedIso = dateStr;
            closeDialog('modal-dashboard-calendar');
            if (typeof uCalCallback === 'function') {
                uCalCallback(dateStr);
            }
        };

        gridEl.appendChild(btn);
    }
}

function prevUniversalCalMonth() {
    uCalMonth--;
    if (uCalMonth < 0) {
        uCalMonth = 11;
        uCalYear--;
    }
    renderUniversalCalendar();
}

function nextUniversalCalMonth() {
    uCalMonth++;
    if (uCalMonth > 11) {
        uCalMonth = 0;
        uCalYear++;
    }
    renderUniversalCalendar();
}

function selectUniversalCalToday() {
    const todayStr = new Date().toISOString().split('T')[0];
    uCalSelectedIso = todayStr;
    closeDialog('modal-dashboard-calendar');
    if (typeof uCalCallback === 'function') {
        uCalCallback(todayStr);
    }
}

/**
 * =========================================================
 * CART CREDIT DUE DATE CALENDAR ENGINE
 * =========================================================
 */
let cartCalYear = new Date().getFullYear();
let cartCalMonth = new Date().getMonth();
let cartCalSelectedIso = '';

function openCartCreditCalendar() {
    ensureUniversalModalsMounted();
    if (!cartCalSelectedIso) {
        const d = new Date(Date.now() + 7 * 86400000);
        cartCalSelectedIso = d.toISOString().split('T')[0];
    }
    const [y, m] = cartCalSelectedIso.split('-');
    cartCalYear = parseInt(y, 10);
    cartCalMonth = parseInt(m, 10) - 1;
    renderCartCreditCalendar();
    openDialog('modal-cart-credit-calendar');
}

function renderCartCreditCalendar() {
    const monthYearEl = document.getElementById('cart-cal-month-year');
    const gridEl = document.getElementById('cart-credit-cal-grid');
    if (!monthYearEl || !gridEl) return;

    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    monthYearEl.textContent = `${monthNames[cartCalMonth]} ${cartCalYear}`;

    gridEl.innerHTML = '';
    const firstDay = new Date(cartCalYear, cartCalMonth, 1).getDay();
    const daysInMonth = new Date(cartCalYear, cartCalMonth + 1, 0).getDate();

    for (let i = 0; i < firstDay; i++) {
        const blank = document.createElement('div');
        blank.className = 'cal-day-cell empty';
        gridEl.appendChild(blank);
    }

    const todayStr = new Date().toISOString().split('T')[0];

    for (let day = 1; day <= daysInMonth; day++) {
        const mStr = String(cartCalMonth + 1).padStart(2, '0');
        const dStr = String(day).padStart(2, '0');
        const dateIso = `${cartCalYear}-${mStr}-${dStr}`;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'cal-day-cell';
        btn.textContent = day;

        if (dateIso === todayStr) btn.classList.add('today');
        if (dateIso === cartCalSelectedIso) btn.classList.add('selected');

        btn.onclick = () => {
            cartCalSelectedIso = dateIso;
            closeDialog('modal-cart-credit-calendar');
            if (typeof onCreditDueDateSelected === 'function') {
                onCreditDueDateSelected(dateIso);
            }
        };

        gridEl.appendChild(btn);
    }
}

function selectCreditQuickDays(days) {
    const d = new Date(Date.now() + days * 86400000);
    const iso = d.toISOString().split('T')[0];
    cartCalSelectedIso = iso;
    closeDialog('modal-cart-credit-calendar');
    if (typeof onCreditDueDateSelected === 'function') {
        onCreditDueDateSelected(iso);
    }
}

function prevCartCalMonth() {
    cartCalMonth--;
    if (cartCalMonth < 0) {
        cartCalMonth = 11;
        cartCalYear--;
    }
    renderCartCreditCalendar();
}

function nextCartCalMonth() {
    cartCalMonth++;
    if (cartCalMonth > 11) {
        cartCalMonth = 0;
        cartCalYear++;
    }
    renderCartCreditCalendar();
}

/**
 * Execute Confirmed Clear Cart
 */
function executeConfirmedClearCart() {
    closeDialog('modal-clear-cart-confirm');
    if (typeof executeCartClearConfirmed === 'function') {
        executeCartClearConfirmed();
    }
}

// Mount synchronously as soon as DOM is interactive
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ensureUniversalModalsMounted);
} else {
    ensureUniversalModalsMounted();
}
