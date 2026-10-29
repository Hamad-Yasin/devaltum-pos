/**
 * =========================================================
 * DevAltum POS Mobile Companion & Scanner App Engine
 * Modular SPA Architecture, Real-Time Sales & Period Filtering,
 * Live Multi-Format Scanner, Products & Customer Directory
 * =========================================================
 */

let currentDashPeriod = 'today';
let currentDashCustomDate = '';
let currentCustomCalMonth = new Date().getMonth();
let currentCustomCalYear = new Date().getFullYear();

let currentSalesPeriod = 'today';
let currentSalesCustomDate = '';
let currentSalesSearchQuery = '';
let currentSalesCalMonth = new Date().getMonth();
let currentSalesCalYear = new Date().getFullYear();

let currentReceiptUrl = '';
let activeCustomerId = 0;

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
 * ─────────────────────────────────────────────────────────
 * 5 Rich Synthesized Audio Tones & Real-Time Haptic Engine
 * ─────────────────────────────────────────────────────────
 */
const SoundTonesList = [
    { id: 'crystal', name: 'Crystal Chime', desc: 'Rising harmonious chime (Default)', icon: 'music_note' },
    { id: 'supermarket', name: 'Retail Laser', desc: 'Ultra-crisp supermarket scanner beep', icon: 'barcode_scanner' },
    { id: 'softbell', name: 'Soft Bell (Ding)', desc: 'Warm acoustic resonant chime', icon: 'notifications_active' },
    { id: 'bubble', name: 'Bubble Pop', desc: 'Modern smooth droplet pop', icon: 'bubble_chart' },
    { id: 'scifi', name: 'Sci-Fi Pulse', desc: 'Futuristic high-tech digital sweep', icon: 'sensors' }
];

const VibrationPatterns = {
    crisp: { name: 'Crisp Tap', pattern: [25], desc: 'Short, clean 25ms haptic tap' },
    medium: { name: 'Medium Pulse', pattern: [55], desc: 'Noticeable 55ms haptic pulse' },
    double: { name: 'Double Pulse', pattern: [30, 45, 30], desc: 'Two rapid tactile taps' },
    heavy: { name: 'Heavy Buzz', pattern: [90], desc: 'Strong 90ms tactile buzz' }
};

function playProductSound(specificTone) {
    if (typeof AppState !== 'undefined' && !AppState.soundEnabled) return;
    const tone = specificTone || (typeof AppState !== 'undefined' && AppState.soundTone ? AppState.soundTone : (localStorage.getItem('pos_scanner_sound_tone') || 'crystal'));

    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        if (ctx.state === 'suspended') {
            ctx.resume();
        }

        const now = ctx.currentTime;

        if (tone === 'supermarket') {
            // Supermarket POS Scanner Laser Chirp
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(2400, now);
            osc.frequency.exponentialRampToValueAtTime(1800, now + 0.06);
            gain.gain.setValueAtTime(0.35, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.07);
            osc.start(now);
            osc.stop(now + 0.07);
        } else if (tone === 'softbell') {
            // Soft Bell Ding (Harmonic C6 + C7)
            const osc1 = ctx.createOscillator();
            const osc2 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            const gain2 = ctx.createGain();

            osc1.type = 'sine';
            osc2.type = 'sine';
            osc1.frequency.setValueAtTime(1046.5, now); // C6
            osc2.frequency.setValueAtTime(2093.0, now); // C7

            gain1.gain.setValueAtTime(0.3, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
            gain2.gain.setValueAtTime(0.12, now);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.25);

            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);

            osc1.start(now);
            osc2.start(now);
            osc1.stop(now + 0.36);
            osc2.stop(now + 0.36);
        } else if (tone === 'bubble') {
            // Bubble Pop (Quick downward frequency sweep)
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(680, now);
            osc.frequency.exponentialRampToValueAtTime(220, now + 0.09);
            gain.gain.setValueAtTime(0.4, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.1);
            osc.start(now);
            osc.stop(now + 0.1);
        } else if (tone === 'scifi') {
            // Sci-Fi Cyber Pulse (Fast rising tri-tone)
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(987.77, now); // B5
            osc.frequency.setValueAtTime(1318.51, now + 0.03); // E6
            osc.frequency.setValueAtTime(1975.53, now + 0.06); // B6
            gain.gain.setValueAtTime(0.3, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.14);
            osc.start(now);
            osc.stop(now + 0.14);
        } else {
            // Default: Crystal Chime (Pleasant rising chime 880 -> 1320Hz)
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, now);
            osc.frequency.exponentialRampToValueAtTime(1320, now + 0.12);
            gain.gain.setValueAtTime(0.3, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.15);
            osc.start(now);
            osc.stop(now + 0.15);
        }
    } catch(e) {}
}

function playSuccessAudio(specificTone) {
    playProductSound(specificTone);
}

function playErrorAudio() {
    if (typeof AppState !== 'undefined' && !AppState.soundEnabled) return;
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(320, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(180, ctx.currentTime + 0.22);
        gain.gain.setValueAtTime(0.35, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.25);
    } catch(e) {}
}

let currentEnteredPin = '';

function pressPinDigit(digit) {
    if (currentEnteredPin.length >= 6) return;
    currentEnteredPin += String(digit);
    renderPinBoxes();
    triggerHaptic(20);

    if (currentEnteredPin.length === 6) {
        connectWithPin(currentEnteredPin);
    }
}

function pressPinBackspace() {
    if (currentEnteredPin.length > 0) {
        currentEnteredPin = currentEnteredPin.slice(0, -1);
        renderPinBoxes();
        triggerHaptic(15);
    }
}

function renderPinBoxes() {
    const boxes = document.querySelectorAll('.pin-digit-box');
    boxes.forEach((box, idx) => {
        if (idx < currentEnteredPin.length) {
            box.textContent = currentEnteredPin[idx];
            box.classList.add('filled');
        } else {
            box.textContent = '';
            box.classList.remove('filled');
        }
    });
}

function openPairingQrScanner() {
    navigateTo('scanner', 'mode=pair');
}

async function connectWithPin(pin, silent = false) {
    if (!pin || pin.length !== 6 || !/^\d{6}$/.test(pin)) {
        if (!silent) {
            showAppToast('Please enter a valid 6-digit numeric PIN.', 'error');
        }
        return;
    }

    try {
        const data = await apiFetch('/scanner/pair', {
            method: 'POST',
            body: JSON.stringify({ code: pin })
        });

        if (data && data.success) {
            AppState.isAuthenticated = true;
            AppState.pairCode = pin;
            AppState.pairToken = data.token || pin;
            AppState.storeName = data.store_name || 'Usman Mobile Center';
            AppState.terminalId = data.terminal_id || 'StarPOS-02';
            AppState.currency = data.currency || 'Rs';
            AppState.connectedAt = data.connected_at || 'Just now';

            localStorage.setItem('pos_scanner_token', AppState.pairToken);
            localStorage.setItem('pos_scanner_code', pin);
            localStorage.setItem('pos_scanner_store', AppState.storeName);
            localStorage.setItem('pos_scanner_terminal_id', AppState.terminalId);
            localStorage.setItem('pos_scanner_currency', AppState.currency);
            localStorage.setItem('pos_scanner_connected_at', AppState.connectedAt);

            playSuccessAudio();
            triggerHaptic([40, 60, 40]);
            showAppToast('Connected to POS Successfully!', 'success');

            if (!silent) {
                navigateTo('connected-success');
            } else {
                navigateTo('dashboard');
            }
        } else {
            playErrorAudio();
            triggerHaptic([60, 60, 60]);
            currentEnteredPin = '';
            renderPinBoxes();
            showAppToast(data.message || 'Invalid 6-digit store code.', 'error');
        }
    } catch(err) {
        playErrorAudio();
        currentEnteredPin = '';
        renderPinBoxes();
        showAppToast('Connection failed: ' + (err.message || 'Server unreachable'), 'error');
    }
}

function setupPinInputs() {
    renderPinBoxes();
}

function renderLocalStateUI() {
    const dashStoreTitle = document.getElementById('dash-store-name');
    if (dashStoreTitle) dashStoreTitle.textContent = AppState.storeName || 'Usman Mobile Center';

    const dashTermId = document.getElementById('dash-terminal-id');
    if (dashTermId) dashTermId.textContent = AppState.terminalId || 'StarPOS-02';

    const dashConnTime = document.getElementById('dash-connected-time');
    if (dashConnTime) {
        const now = new Date();
        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const dateStr = now.toLocaleDateString([], { day: '2-digit', month: 'short', year: 'numeric' });
        dashConnTime.textContent = `Connected At: ${AppState.connectedAt || (timeStr + ', ' + dateStr)}`;
    }

    const currLabel = document.getElementById('setting-currency-label');
    if (currLabel) currLabel.textContent = `${AppState.currencyCode || 'PKR'} (${AppState.currency || 'Rs'}) >`;
}

/**
 * Dashboard Metrics API
 */
async function fetchDashboardData(period = currentDashPeriod, customDate = currentDashCustomDate) {
    if (typeof isGuestSession === 'function' && isGuestSession()) {
        const stats = JSON.parse(localStorage.getItem('pos_guest_stats') || '{}');
        const currency = AppState.currency || 'Rs';
        const fmt = (num) => `${currency} ${Math.round(Number(num) || 0).toLocaleString()}`;

        AppState.storeName = 'Demo Store (Guest Sandbox)';
        AppState.terminalId = 'GuestTerminal-01';
        renderLocalStateUI();

        const netProfitEl = document.getElementById('dash-net-profit');
        if (netProfitEl) netProfitEl.textContent = fmt(stats.net_profit || 3200);

        const salesEl = document.getElementById('dash-sales-value');
        if (salesEl) salesEl.textContent = fmt(stats.today_sales || 8450);

        const txnsEl = document.getElementById('dash-txns-value');
        if (txnsEl) txnsEl.textContent = String(stats.today_orders || 14);

        const cashEl = document.getElementById('dash-cash-value');
        if (cashEl) cashEl.textContent = fmt(Math.round((stats.today_sales || 8450) * 0.65));

        const onlineEl = document.getElementById('dash-online-value');
        if (onlineEl) onlineEl.textContent = fmt(Math.round((stats.today_sales || 8450) * 0.35));

        const creditGivenEl = document.getElementById('dash-credit-given-value');
        if (creditGivenEl) creditGivenEl.textContent = fmt(1200);

        const creditRecEl = document.getElementById('dash-credit-rec-value');
        if (creditRecEl) creditRecEl.textContent = fmt(800);

        return;
    }

    if (!AppState.pairCode) return;

    try {
        let endpoint = `/scanner/dashboard?code=${encodeURIComponent(AppState.pairCode)}&period=${encodeURIComponent(period)}`;
        if (period === 'custom' && customDate) {
            endpoint += `&date=${encodeURIComponent(customDate)}`;
        }

        const data = await apiFetch(endpoint);
        if (data.success) {
            AppState.storeName = data.store_name || AppState.storeName || 'Usman Mobile Center';
            AppState.terminalId = data.terminal_id || AppState.terminalId || 'StarPOS-02';
            renderLocalStateUI();

            const currency = AppState.currency || 'Rs';
            const fmt = (num) => `${currency} ${Math.round(Number(num) || 0).toLocaleString()}`;

            const netProfitEl = document.getElementById('dash-net-profit');
            if (netProfitEl) netProfitEl.textContent = fmt(data.net_profit || 0);

            const salesEl = document.getElementById('dash-sales-value');
            if (salesEl) salesEl.textContent = fmt(data.total_sales || 0);

            const txnsEl = document.getElementById('dash-txns-value');
            if (txnsEl) txnsEl.textContent = String(data.total_txns || 0);

            const cashEl = document.getElementById('dash-cash-value');
            if (cashEl) cashEl.textContent = fmt(data.cash_sales || data.cash_payment || 0);

            const onlineEl = document.getElementById('dash-online-value');
            if (onlineEl) onlineEl.textContent = fmt(data.online_sales || data.online_payment || 0);

            const creditGivenEl = document.getElementById('dash-credit-given-value');
            if (creditGivenEl) creditGivenEl.textContent = fmt(data.credit_given || 0);

            const creditRecEl = document.getElementById('dash-credit-rec-value');
            if (creditRecEl) creditRecEl.textContent = fmt(data.credit_recovered || 0);
        }
    } catch(e) {}
}

function formatDateReadable(isoDate) {
    if (!isoDate) return '';
    const parts = isoDate.split('-');
    if (parts.length !== 3) return isoDate;
    const y = parts[0];
    const m = parseInt(parts[1], 10) - 1;
    const d = parseInt(parts[2], 10);
    const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    return `${d} ${monthNames[m] || parts[1]} ${y}`;
}

function switchDashboardPeriod(period) {
    currentDashPeriod = period;
    const chips = document.querySelectorAll('#view-dashboard .period-chip[data-period], .period-chip[data-period]');
    const customChip = document.querySelector('.period-chip[data-period="custom"]');

    chips.forEach(c => {
        if (c.dataset.period === period) c.classList.add('active');
        else c.classList.remove('active');
    });

    const headingEl = document.getElementById('summary-section-heading') || document.getElementById('dash-summary-title');

    if (period === 'custom') {
        if (typeof openUniversalCalendarModal === 'function') {
            openUniversalCalendarModal('Select Dashboard Date', (selectedDate) => {
                currentDashCustomDate = selectedDate;
                const formatted = formatDateReadable(selectedDate);
                if (customChip) customChip.textContent = `Custom: ${formatted}`;
                if (headingEl) headingEl.textContent = `Summary (${formatted})`;
                fetchDashboardData('custom', selectedDate);
                showAppToast(`Summary for ${formatted}`, 'info');
            });
        }
    } else {
        currentDashCustomDate = '';
        if (customChip) customChip.textContent = 'Custom';
        const titles = {
            today: "Today's Summary",
            yesterday: "Yesterday's Summary",
            week: "This Week's Summary",
            month: "This Month's Summary"
        };
        if (headingEl) headingEl.textContent = titles[period] || "Summary";
        fetchDashboardData(period);
    }
}

function openDashboardCalendarModal() {
    switchDashboardPeriod('custom');
}

function renderCustomCalendar() {
    const monthYearEl = document.getElementById('custom-cal-month-year');
    const daysContainer = document.getElementById('custom-cal-days-container');
    if (!monthYearEl || !daysContainer) return;

    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    monthYearEl.textContent = `${monthNames[currentCustomCalMonth]} ${currentCustomCalYear}`;

    daysContainer.innerHTML = '';
    const firstDay = new Date(currentCustomCalYear, currentCustomCalMonth, 1).getDay();
    const daysInMonth = new Date(currentCustomCalYear, currentCustomCalMonth + 1, 0).getDate();

    for (let i = 0; i < firstDay; i++) {
        const emptyCell = document.createElement('div');
        daysContainer.appendChild(emptyCell);
    }

    const todayStr = new Date().toISOString().split('T')[0];

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${currentCustomCalYear}-${String(currentCustomCalMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const dayBtn = document.createElement('button');
        dayBtn.type = 'button';
        dayBtn.className = 'cal-day-cell';
        dayBtn.textContent = d;

        if (dateStr === todayStr) dayBtn.classList.add('today');
        if (dateStr === currentDashCustomDate) dayBtn.classList.add('selected');

        dayBtn.onclick = () => {
            currentDashCustomDate = dateStr;
            const dateBadge = document.getElementById('dash-selected-date-badge');
            const dateBadgeText = document.getElementById('dash-selected-date-text');
            if (dateBadge && dateBadgeText) {
                dateBadge.style.display = 'flex';
                dateBadgeText.textContent = `${d} ${monthNames[currentCustomCalMonth]} ${currentCustomCalYear}`;
            }
            const customCalBox = document.getElementById('dash-custom-calendar-box');
            if (customCalBox) customCalBox.style.display = 'none';
            fetchDashboardData('custom', dateStr);
        };

        daysContainer.appendChild(dayBtn);
    }
}

function prevCustomCalMonth() {
    currentCustomCalMonth--;
    if (currentCustomCalMonth < 0) {
        currentCustomCalMonth = 11;
        currentCustomCalYear--;
    }
    renderCustomCalendar();
}

function nextCustomCalMonth() {
    currentCustomCalMonth++;
    if (currentCustomCalMonth > 11) {
        currentCustomCalMonth = 0;
        currentCustomCalYear++;
    }
    renderCustomCalendar();
}

/**
 * Products Catalog & Search
 */
let productSearchTimer = null;
function handleProductSearch(query) {
    clearTimeout(productSearchTimer);
    productSearchTimer = setTimeout(() => {
        fetchProducts(query);
    }, 250);
}

async function fetchProducts(searchQuery = '') {
    const mount = document.getElementById('products-list-mount');
    if (!mount) return;

    if (!searchQuery) {
        mount.innerHTML = `
            <div class="skeleton-card">
                <div class="skeleton-box" style="width:44px; height:44px; border-radius:12px;"></div>
                <div style="flex:1; display:flex; flex-direction:column; gap:6px;">
                    <div class="skeleton-box" style="width:70%; height:14px;"></div>
                    <div class="skeleton-box" style="width:45%; height:11px;"></div>
                </div>
            </div>
            <div class="skeleton-card">
                <div class="skeleton-box" style="width:44px; height:44px; border-radius:12px;"></div>
                <div style="flex:1; display:flex; flex-direction:column; gap:6px;">
                    <div class="skeleton-box" style="width:60%; height:14px;"></div>
                    <div class="skeleton-box" style="width:40%; height:11px;"></div>
                </div>
            </div>
        `;
    }

    try {
        const data = await apiFetch(`/scanner/products?code=${encodeURIComponent(AppState.pairCode)}&q=${encodeURIComponent(searchQuery)}`);
        if (data.success && Array.isArray(data.products)) {
            if (data.products.length === 0) {
                mount.innerHTML = `
                    <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                        <span class="material-symbols-rounded" style="font-size:46px; color:#cbd5e1; margin-bottom:8px;">inventory_2</span>
                        <div style="font-size:14px; font-weight: 700; color:#64748b; margin-bottom:4px;">No Products Found</div>
                        <div style="font-size:12px; color:#94a3b8;">Try a different search term or register a new product.</div>
                    </div>
                `;
                return;
            }

            mount.innerHTML = data.products.map(p => {
                const stock = Number(p.stock) || 0;
                const minStock = Number(p.min_stock) || 5;
                let stockClass = 'in-stock';
                let stockLabel = `In Stock: ${stock} ${p.unit_type || 'pcs'}`;

                if (stock <= 0) {
                    stockClass = 'out-of-stock';
                    stockLabel = 'Out of Stock';
                } else if (stock <= minStock) {
                    stockClass = 'low-stock';
                    stockLabel = `Low Stock: ${stock} ${p.unit_type || 'pcs'}`;
                }

                return `
                    <div class="product-item-card" onclick="openProductDetailModal(${JSON.stringify(p).replace(/"/g, '&quot;')})">
                        <div class="product-avatar-icon">
                            <span class="material-symbols-rounded">inventory_2</span>
                        </div>
                        <div class="product-meta-block">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                                <div class="product-name-row" style="flex:1; margin-bottom:0;">${escapeHtml(p.name)}</div>
                                <button type="button" class="btn-product-card-edit" title="Edit Product" onclick="event.stopPropagation(); startEditProductFlow(${p.id || 0}, '${encodeURIComponent(p.barcode || '')}', ${JSON.stringify(p).replace(/"/g, '&quot;')})">
                                    <span class="material-symbols-rounded" style="font-size:15px;">edit</span>
                                </button>
                            </div>
                            <div class="product-sub-row" style="margin-top:2px;">
                                <span>Barcode: <strong>${escapeHtml(p.barcode || '---')}</strong></span>
                            </div>
                            <div class="product-pricing-row">
                                <span class="product-price-tag">${AppState.currency} ${Number(p.price || 0).toLocaleString()}</span>
                                <span class="stock-pill ${stockClass}">${stockLabel}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    } catch(err) {
        mount.innerHTML = `
            <div style="text-align:center; padding:30px 20px; color:#ef4444;">
                <span class="material-symbols-rounded" style="font-size:38px; margin-bottom:6px;">error</span>
                <div style="font-size:13px; font-weight: 700;">Failed to load products</div>
                <div style="font-size:11.5px; color:#94a3b8; margin-top:4px;">${escapeHtml(err.message)}</div>
            </div>
        `;
    }
}

function startEditProductFlow(id, barcode, productObj) {
    if (productObj) {
        sessionStorage.setItem('edit_product_data', JSON.stringify(productObj));
    }
    navigateTo('add-product', 'id=' + id + '&barcode=' + barcode + '&edit=1');
}

function renderLiveBarcodePreview(code) {
    code = String(code || '').trim();
    const wrap = document.getElementById('barcode-live-preview-wrap');
    const svgEl = document.getElementById('barcode-live-svg');
    const textEl = document.getElementById('barcode-live-text');

    if (!wrap || !svgEl) return;

    if (!code || code.length < 2) {
        wrap.style.display = 'none';
        return;
    }

    wrap.style.display = 'block';
    if (textEl) textEl.textContent = code;

    try {
        if (typeof JsBarcode === 'function') {
            JsBarcode(svgEl, code, {
                format: "auto",
                lineColor: "#0f172a",
                width: 1.8,
                height: 44,
                displayValue: false,
                margin: 2,
                background: "transparent"
            });
        }
    } catch(e) {
        try {
            if (typeof JsBarcode === 'function') {
                JsBarcode(svgEl, code, {
                    format: "CODE128",
                    lineColor: "#0f172a",
                    width: 1.8,
                    height: 44,
                    displayValue: false,
                    margin: 2,
                    background: "transparent"
                });
            }
        } catch(err) {
            wrap.style.display = 'none';
        }
    }
}

function handleBarcodeFieldInput(val) {
    renderLiveBarcodePreview(val);
    checkBarcodeLookup(val);
}

function generateRandomBarcode() {
    const randomCode = '890' + Math.floor(100000 + Math.random() * 900000);
    const barcodeInput = document.getElementById('full-add-barcode');
    if (barcodeInput) {
        barcodeInput.value = randomCode;
        renderLiveBarcodePreview(randomCode);
        showAppToast(`Generated Barcode: ${randomCode}`, 'info');
    }
}

async function checkBarcodeLookup(barcode) {
    barcode = (barcode || '').trim();
    if (!barcode || barcode.length < 3) return;

    try {
        const data = await apiFetch(`/scanner/lookup_barcode?code=${encodeURIComponent(AppState.pairCode)}&barcode=${encodeURIComponent(barcode)}`);
        if (data.success && data.product) {
            const p = data.product;
            const nameEl = document.getElementById('full-add-name');
            const priceEl = document.getElementById('full-add-price');
            const costEl = document.getElementById('full-add-cost');
            const skuEl = document.getElementById('full-add-sku');
            const stockEl = document.getElementById('full-add-stock');

            if (nameEl && !nameEl.value) nameEl.value = p.name || '';
            if (priceEl && (!priceEl.value || priceEl.value === '0')) priceEl.value = p.price || '';
            if (costEl && (!costEl.value || costEl.value === '0')) costEl.value = p.cost || '';
            if (skuEl && !skuEl.value) skuEl.value = p.model_number || '';
            if (stockEl && (!stockEl.value || stockEl.value === '0')) stockEl.value = p.stock || '0';

            calculateProfitMargin();
            showAppToast('Product data found & filled!', 'info');
        }
    } catch(e) {}
}

function openAddProductScanner() {
    navigateTo('scanner', 'mode=add_product');
}

function resetAddProductForm(clearBarcode = true) {
    sessionStorage.removeItem('edit_product_data');
    if (clearBarcode) {
        const barcodeInput = document.getElementById('full-add-barcode');
        if (barcodeInput) barcodeInput.value = '';
        renderLiveBarcodePreview('');
    }

    const nameInput = document.getElementById('full-add-name');
    const skuInput = document.getElementById('full-add-sku');
    const unitValInput = document.getElementById('full-add-unit-val');
    const unitTypeInput = document.getElementById('full-add-unit-type');
    const priceInput = document.getElementById('full-add-price');
    const costInput = document.getElementById('full-add-cost');
    const wholesaleInput = document.getElementById('full-add-wholesale');
    const stockInput = document.getElementById('full-add-stock');
    const minStockInput = document.getElementById('full-add-min-stock');
    const batchInput = document.getElementById('full-add-batch');
    const expiryInput = document.getElementById('full-add-expiry');
    const base64Input = document.getElementById('full-add-image-base64');
    const idInput = document.getElementById('full-add-product-id');
    const previewBox = document.getElementById('product-img-preview-box');

    if (nameInput) nameInput.value = '';
    if (skuInput) skuInput.value = '';
    if (unitValInput) unitValInput.value = '1';
    if (unitTypeInput) unitTypeInput.value = 'pcs';
    if (priceInput) priceInput.value = '';
    if (costInput) costInput.value = '';
    if (wholesaleInput) wholesaleInput.value = '';
    if (stockInput) stockInput.value = '0';
    if (minStockInput) minStockInput.value = '5';
    if (batchInput) batchInput.value = '';
    if (expiryInput) expiryInput.value = '';
    if (base64Input) base64Input.value = '';
    if (idInput) idInput.value = '';
    if (previewBox) {
        previewBox.innerHTML = `<span class="material-symbols-rounded" style="font-size:28px;">add_photo_alternate</span>`;
    }

    calculateProfitMargin();
}

function calculateProfitMargin() {
    const price = parseFloat(document.getElementById('full-add-price')?.value || '0');
    const cost = parseFloat(document.getElementById('full-add-cost')?.value || '0');
    const badge = document.getElementById('full-add-profit-badge');
    if (!badge) return;

    if (price > 0) {
        const profit = price - cost;
        const margin = Math.round((profit / price) * 100);
        if (profit >= 0) {
            badge.style.color = '#16a34a';
            badge.style.background = '#f0fdf4';
            badge.style.borderColor = '#bbf7d0';
            badge.textContent = `Margin: ${margin}% (Profit: ${AppState.currency} ${profit.toFixed(0)})`;
        } else {
            badge.style.color = '#dc2626';
            badge.style.background = '#fef2f2';
            badge.style.borderColor = '#fecaca';
            badge.textContent = `Loss: ${AppState.currency} ${Math.abs(profit).toFixed(0)}`;
        }
    } else {
        badge.style.color = '#64748b';
        badge.style.background = '#f8fafc';
        badge.style.borderColor = '#e2e8f0';
        badge.textContent = 'Margin: 0%';
    }
}

function toggleExpiryFields() {
    const collapse = document.getElementById('full-add-expiry-collapse');
    const chevron = document.getElementById('expiry-chevron');
    if (!collapse) return;

    const isHidden = collapse.style.display === 'none' || collapse.style.display === '';
    collapse.style.display = isHidden ? 'block' : 'none';
    if (chevron) chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
}

async function checkAndPopulateEditProductForm() {
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('id');
    const barcodeParam = urlParams.get('barcode');
    const isEditMode = urlParams.get('edit') === '1' || Boolean(editId);

    if (!isEditMode && !barcodeParam) return;

    if (barcodeParam && !isEditMode) {
        const barcodeInput = document.getElementById('full-add-barcode');
        if (barcodeInput) {
            barcodeInput.value = barcodeParam;
            renderLiveBarcodePreview(barcodeParam);
        }
        checkBarcodeLookup(barcodeParam);
        return;
    }

    if (isEditMode) {
        // Switch UI to Edit Mode
        const headerTitle = document.getElementById('add-product-header-title');
        if (headerTitle) headerTitle.textContent = 'Edit Product';

        const saveBtnText = document.getElementById('btn-full-save-product-text');
        if (saveBtnText) saveBtnText.textContent = 'Update Product in POS Cloud';

        const headerDelBtn = document.getElementById('btn-header-delete-product');
        if (headerDelBtn) headerDelBtn.style.display = 'grid';

        const bottomDelBtn = document.getElementById('btn-full-delete-product');
        if (bottomDelBtn) bottomDelBtn.style.display = 'flex';

        const idInput = document.getElementById('full-add-product-id');
        if (idInput) idInput.value = editId || '';

        // 1. Try local session cache first for instant zero-latency fill
        let targetProduct = null;
        const cachedRaw = sessionStorage.getItem('edit_product_data');
        if (cachedRaw) {
            try {
                const parsed = JSON.parse(cachedRaw);
                if (parsed && (String(parsed.id) === String(editId) || (barcodeParam && String(parsed.barcode) === String(barcodeParam)))) {
                    targetProduct = parsed;
                }
            } catch(e) {}
        }

        // 2. Check Guest Catalog if guest mode
        if (!targetProduct && typeof isGuestSession === 'function' && isGuestSession()) {
            const catalog = JSON.parse(localStorage.getItem('pos_guest_catalog') || '[]');
            targetProduct = catalog.find(item => (editId && String(item.id) === String(editId)) || (barcodeParam && String(item.barcode) === String(barcodeParam)));
        }

        // 3. Fallback to API call
        if (!targetProduct && AppState.pairCode) {
            try {
                const res = await apiFetch(`/scanner/get_product?code=${encodeURIComponent(AppState.pairCode)}&id=${encodeURIComponent(editId || '')}&barcode=${encodeURIComponent(barcodeParam || '')}`);
                if (res && res.success && res.product) {
                    targetProduct = res.product;
                }
            } catch(e) {}
        }

        if (targetProduct) {
            const p = targetProduct;
            if (idInput) idInput.value = p.id || editId || '';
            const barcodeEl = document.getElementById('full-add-barcode');
            const nameEl = document.getElementById('full-add-name');
            const skuEl = document.getElementById('full-add-sku');
            const priceEl = document.getElementById('full-add-price');
            const costEl = document.getElementById('full-add-cost');
            const wholesaleEl = document.getElementById('full-add-wholesale');
            const stockEl = document.getElementById('full-add-stock');
            const minStockEl = document.getElementById('full-add-min-stock');
            const unitValEl = document.getElementById('full-add-unit-val');
            const unitTypeEl = document.getElementById('full-add-unit-type');
            const batchEl = document.getElementById('full-add-batch');
            const expiryEl = document.getElementById('full-add-expiry');

            if (barcodeEl) {
                barcodeEl.value = p.barcode || '';
                renderLiveBarcodePreview(barcodeEl.value);
            }
            if (nameEl) nameEl.value = p.name || '';
            if (skuEl) skuEl.value = p.model_number || p.sku || '';
            if (priceEl) priceEl.value = p.price ?? '';
            if (costEl) costEl.value = p.cost ?? '';
            if (wholesaleEl) wholesaleEl.value = p.wholesale_price ?? '';
            if (stockEl) stockEl.value = p.stock ?? '0';
            if (minStockEl) minStockEl.value = p.min_stock ?? '5';
            if (unitValEl) unitValEl.value = p.unit_value ?? '1';
            if (unitTypeEl) unitTypeEl.value = p.unit_type || p.unit || 'pcs';
            if (batchEl) batchEl.value = p.batch_number || '';
            if (expiryEl) expiryEl.value = p.expiry_date || '';

            if (p.batch_number || p.expiry_date) {
                const collapse = document.getElementById('full-add-expiry-collapse');
                if (collapse) collapse.style.display = 'block';
            }

            calculateProfitMargin();
        }
    }
}

function confirmDeleteCurrentProduct() {
    const titleEl = document.getElementById('del-modal-product-title');
    const msgEl = document.getElementById('del-modal-product-msg');

    if (titleEl) titleEl.textContent = 'Delete Product?';
    if (msgEl) msgEl.textContent = 'Are you sure you want to delete?';

    ensureUniversalModalsMounted();
    openDialog('modal-delete-product-confirm');
}

async function executeDeleteCurrentProduct() {
    closeDialog('modal-delete-product-confirm');
    const productId = (document.getElementById('full-add-product-id')?.value || '').trim();
    const barcode = (document.getElementById('full-add-barcode')?.value || '').trim();

    if (typeof isGuestSession === 'function' && isGuestSession()) {
        let catalog = JSON.parse(localStorage.getItem('pos_guest_catalog') || '[]');
        catalog = catalog.filter(item => (productId && String(item.id) !== String(productId)) && (!barcode || String(item.barcode) !== String(barcode)));
        localStorage.setItem('pos_guest_catalog', JSON.stringify(catalog));
        triggerHaptic([40, 50, 40]);
        showAppToast('Product deleted successfully', 'success');
        setTimeout(() => navigateTo('products'), 450);
        return;
    }

    try {
        const res = await apiFetch('/scanner/delete_product', {
            method: 'POST',
            body: JSON.stringify({
                code: AppState.pairCode,
                id: productId,
                barcode: barcode
            })
        });
        if (res && res.success) {
            triggerHaptic([40, 50, 40]);
            showAppToast('Product deleted successfully', 'success');
            setTimeout(() => navigateTo('products'), 450);
        } else {
            showAppToast(res.message || 'Failed to delete product', 'error');
        }
    } catch(e) {
        showAppToast('Failed to delete product', 'error');
    }
}

async function handleFullAddProductSubmit(e) {
    if (e && e.preventDefault) e.preventDefault();

    const productId = (document.getElementById('full-add-product-id')?.value || '').trim();
    const isEditMode = Boolean(productId);

    let barcode = (document.getElementById('full-add-barcode')?.value || '').trim();
    if (!barcode) {
        barcode = '890' + Math.floor(100000 + Math.random() * 900000);
    }

    const name = (document.getElementById('full-add-name')?.value || '').trim();
    const sku = (document.getElementById('full-add-sku')?.value || '').trim();
    const unitValue = parseFloat(document.getElementById('full-add-unit-val')?.value || '1');
    const unitType = (document.getElementById('full-add-unit-type')?.value || 'pcs').trim();
    const price = parseFloat(document.getElementById('full-add-price')?.value || '0');
    const cost = parseFloat(document.getElementById('full-add-cost')?.value || '0');
    const wholesale = parseFloat(document.getElementById('full-add-wholesale')?.value || '0');
    const stock = parseFloat(document.getElementById('full-add-stock')?.value || '0');
    const minStock = parseFloat(document.getElementById('full-add-min-stock')?.value || '5');
    const batch = (document.getElementById('full-add-batch')?.value || '').trim();
    const expiry = (document.getElementById('full-add-expiry')?.value || '').trim();
    const imageBase64 = (document.getElementById('full-add-image-base64')?.value || '').trim();

    if (!name) {
        showAppToast('Please enter a product name.', 'error');
        return;
    }
    if (price <= 0 || isNaN(price)) {
        showAppToast('Please enter a valid retail price.', 'error');
        return;
    }

    const submitBtn = document.getElementById('btn-full-save-product');
    const submitBtnText = document.getElementById('btn-full-save-product-text');
    if (submitBtn) {
        submitBtn.disabled = true;
        if (submitBtnText) submitBtnText.textContent = isEditMode ? 'Updating Product...' : 'Saving Product...';
    }

    try {
        const endpoint = isEditMode ? '/scanner/update_product' : '/scanner/add_product';
        const response = await apiFetch(endpoint, {
            method: 'POST',
            body: JSON.stringify({
                id: productId || undefined,
                code: AppState.pairCode,
                barcode: barcode,
                name: name,
                model_number: sku,
                unit_value: unitValue,
                unit_type: unitType,
                price: price,
                cost: cost,
                wholesale_price: wholesale,
                stock: stock,
                min_stock: minStock,
                batch_number: batch,
                expiry_date: expiry,
                image_data: imageBase64
            })
        });

        if (response.success) {
            playSuccessAudio();
            triggerHaptic([40, 60, 40]);
            showAppToast(isEditMode ? 'Product updated successfully!' : 'Product saved to POS Cloud!', 'success');
            resetAddProductForm(true);
            setTimeout(() => navigateTo('products'), 500);
        } else {
            playErrorAudio();
            triggerHaptic([60, 60, 60]);
            showAppToast(response.message || 'Failed to save product.', 'error');
        }
    } catch(err) {
        playErrorAudio();
        showAppToast('Network error while saving product.', 'error');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            if (submitBtnText) submitBtnText.textContent = isEditMode ? 'Update Product in POS Cloud' : 'Save Product to POS Cloud';
        }
    }
}

/**
 * Sales History with Advanced Period Filters
 */
let salesSearchTimer = null;
function handleSalesSearch(query) {
    clearTimeout(salesSearchTimer);
    salesSearchTimer = setTimeout(() => {
        currentSalesSearchQuery = query;
        fetchSales(currentSalesPeriod, currentSalesCustomDate, query);
    }, 250);
}

function filterSalesPeriod(period, btnEl) {
    currentSalesPeriod = period;
    const chips = document.querySelectorAll('.period-chip');
    chips.forEach(c => c.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');

    const calBox = document.getElementById('sales-custom-calendar-box');
    if (calBox) calBox.style.display = 'none';

    currentSalesCustomDate = '';
    fetchSales(period, '', currentSalesSearchQuery);
}

function toggleSalesCustomCalendar(btnEl) {
    currentSalesPeriod = 'custom';
    const chips = document.querySelectorAll('.period-chip');
    chips.forEach(c => c.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');

    const calBox = document.getElementById('sales-custom-calendar-box');
    if (!calBox) return;

    const isHidden = calBox.style.display === 'none' || calBox.style.display === '';
    calBox.style.display = isHidden ? 'block' : 'none';
    if (isHidden) {
        renderSalesCalendar();
    }
}

function renderSalesCalendar() {
    const monthYearEl = document.getElementById('sales-cal-month-year');
    const daysContainer = document.getElementById('sales-cal-days-container');
    if (!monthYearEl || !daysContainer) return;

    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    monthYearEl.textContent = `${monthNames[currentSalesCalMonth]} ${currentSalesCalYear}`;

    daysContainer.innerHTML = '';
    const firstDay = new Date(currentSalesCalYear, currentSalesCalMonth, 1).getDay();
    const daysInMonth = new Date(currentSalesCalYear, currentSalesCalMonth + 1, 0).getDate();

    for (let i = 0; i < firstDay; i++) {
        const emptyCell = document.createElement('div');
        daysContainer.appendChild(emptyCell);
    }

    const todayStr = new Date().toISOString().split('T')[0];

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${currentSalesCalYear}-${String(currentSalesCalMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const dayBtn = document.createElement('button');
        dayBtn.type = 'button';
        dayBtn.className = 'cal-day-cell';
        dayBtn.textContent = d;

        if (dateStr === todayStr) dayBtn.classList.add('today');
        if (dateStr === currentSalesCustomDate) dayBtn.classList.add('selected');

        dayBtn.onclick = () => {
            currentSalesCustomDate = dateStr;
            const calBox = document.getElementById('sales-custom-calendar-box');
            if (calBox) calBox.style.display = 'none';
            fetchSales('custom', dateStr, currentSalesSearchQuery);
        };

        daysContainer.appendChild(dayBtn);
    }
}

function prevSalesCalMonth() {
    currentSalesCalMonth--;
    if (currentSalesCalMonth < 0) {
        currentSalesCalMonth = 11;
        currentSalesCalYear--;
    }
    renderSalesCalendar();
}

function nextSalesCalMonth() {
    currentSalesCalMonth++;
    if (currentSalesCalMonth > 11) {
        currentSalesCalMonth = 0;
        currentSalesCalYear++;
    }
    renderSalesCalendar();
}

let currentPreviewProduct = null;
function openProductDetailModal(p) {
    if (!p) return;
    currentPreviewProduct = p;

    const nameEl = document.getElementById('modal-prod-name');
    const skuEl = document.getElementById('modal-prod-sku');
    const priceEl = document.getElementById('modal-prod-price');
    const stockEl = document.getElementById('modal-prod-stock');
    const barcodeEl = document.getElementById('modal-prod-barcode');
    const badgeEl = document.getElementById('modal-prod-stock-badge');

    const stock = Number(p.stock) || 0;
    const minStock = Number(p.min_stock) || 5;

    if (nameEl) nameEl.textContent = p.name || 'Product';
    if (skuEl) skuEl.textContent = p.sku || p.model_number ? `SKU: ${p.sku || p.model_number}` : 'No SKU';
    if (priceEl) priceEl.textContent = p.formatted_price || `${AppState.currency} ${Number(p.price || 0).toLocaleString()}`;
    if (stockEl) stockEl.textContent = `${stock} ${p.unit || p.unit_type || 'pcs'}`;
    if (barcodeEl) barcodeEl.textContent = p.barcode || 'None';

    if (badgeEl) {
        if (stock <= 0) {
            badgeEl.className = 'stock-pill out-of-stock';
            badgeEl.textContent = 'Out of Stock';
        } else if (stock <= minStock) {
            badgeEl.className = 'stock-pill low-stock';
            badgeEl.textContent = `Low Stock (${stock})`;
        } else {
            badgeEl.className = 'stock-pill in-stock';
            badgeEl.textContent = `In Stock (${stock})`;
        }
    }

    openDialog('modal-product-detail');
}

function switchSalesPeriod(period) {
    currentSalesPeriod = period;
    const chips = document.querySelectorAll('#view-history .period-chip[data-period]');
    const customChip = document.querySelector('#view-history .period-chip[data-period="custom"]');

    chips.forEach(c => {
        if (c.dataset.period === period) c.classList.add('active');
        else c.classList.remove('active');
    });

    if (period !== 'custom') {
        currentSalesCustomDate = '';
        if (customChip) customChip.textContent = 'Custom Date';
        fetchSales(period, '', currentSalesSearchQuery);
    } else {
        openSalesCalendarModal();
    }
}

function openSalesCalendarModal() {
    if (typeof openUniversalCalendarModal === 'function') {
        openUniversalCalendarModal('Select Sales Date', (selectedDate) => {
            currentSalesPeriod = 'custom';
            currentSalesCustomDate = selectedDate;

            const chips = document.querySelectorAll('#view-history .period-chip[data-period]');
            const customChip = document.querySelector('#view-history .period-chip[data-period="custom"]');
            const formatted = formatDateReadable(selectedDate);

            chips.forEach(c => {
                if (c.dataset.period === 'custom') c.classList.add('active');
                else c.classList.remove('active');
            });

            if (customChip) customChip.textContent = `Custom: ${formatted}`;

            fetchSales('custom', selectedDate, currentSalesSearchQuery);
            showAppToast(`Sales for ${formatted}`, 'info');
        });
    }
}

async function fetchSales(period = 'today', customDate = '', query = '') {
    const mount = document.getElementById('transactions-list-mount');
    if (!mount) return;

    mount.innerHTML = `
        <div class="skeleton-card">
            <div class="skeleton-box" style="width:40px; height:40px; border-radius:50%;"></div>
            <div style="flex:1; display:flex; flex-direction:column; gap:6px;">
                <div class="skeleton-box" style="width:60%; height:14px;"></div>
                <div class="skeleton-box" style="width:40%; height:11px;"></div>
            </div>
        </div>
        <div class="skeleton-card">
            <div class="skeleton-box" style="width:40px; height:40px; border-radius:50%;"></div>
            <div style="flex:1; display:flex; flex-direction:column; gap:6px;">
                <div class="skeleton-box" style="width:55%; height:14px;"></div>
                <div class="skeleton-box" style="width:35%; height:11px;"></div>
            </div>
        </div>
    `;

    try {
        let endpoint = `/scanner/sales?code=${encodeURIComponent(AppState.pairCode)}&period=${encodeURIComponent(period)}`;
        if (period === 'custom' && customDate) {
            endpoint += `&date=${encodeURIComponent(customDate)}`;
        }
        if (query) {
            endpoint += `&q=${encodeURIComponent(query)}`;
        }

        const data = await apiFetch(endpoint);
        const salesList = data.transactions || data.sales || [];
        if (data.success && Array.isArray(salesList)) {
            if (salesList.length === 0) {
                mount.innerHTML = `
                    <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                        <span class="material-symbols-rounded" style="font-size:46px; color:#cbd5e1; margin-bottom:8px;">receipt_long</span>
                        <div style="font-size:14px; font-weight: 700; color:#64748b; margin-bottom:4px;">No Sales Found</div>
                        <div style="font-size:12px; color:#94a3b8;">No transactions found for this period.</div>
                    </div>
                `;
                return;
            }

            mount.innerHTML = salesList.map(s => {
                const invoiceId = s.id || '';
                const invoiceNo = s.txn_code || s.custom_id || s.invoice_number || `INV-#${invoiceId}`;
                const amountStr = s.formatted_amount || `${AppState.currency} ${Math.round(Number(s.amount || s.total || s.final_total || 0)).toLocaleString()}`;
                const customer = s.customer_name || 'Walk-in Customer';
                const paymentRaw = (s.payment_method || 'Cash').toLowerCase();
                let iconName = 'payments';
                let iconClass = 'cash';
                let tagClass = 'cash';

                if (paymentRaw.includes('online') || paymentRaw.includes('card') || paymentRaw.includes('bank') || paymentRaw.includes('pos')) {
                    iconName = 'credit_card';
                    iconClass = 'online';
                    tagClass = 'online';
                } else if (paymentRaw.includes('credit') || paymentRaw.includes('udhar')) {
                    iconName = 'account_balance_wallet';
                    iconClass = 'credit';
                    tagClass = 'credit';
                }

                const createdTime = s.time || (s.created_at ? new Date(s.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '');
                const createdDate = s.date || (s.created_at ? new Date(s.created_at).toLocaleDateString([], { day: '2-digit', month: 'short' }) : '');

                return `
                    <div class="transaction-item-card" onclick="viewSaleReceipt('${encodeURIComponent(invoiceId)}', '${encodeURIComponent(invoiceNo)}')">
                        <div class="txn-left-block">
                            <div class="txn-icon-circle ${iconClass}">
                                <span class="material-symbols-rounded" style="font-size:20px;">${iconName}</span>
                            </div>
                            <div style="min-width:0;">
                                <div class="txn-invoice-title">${escapeHtml(invoiceNo)}</div>
                                <div class="txn-sub-info">
                                    <span>${escapeHtml(customer)}</span>
                                    <span>&bull;</span>
                                    <span class="txn-payment-tag ${tagClass}">${escapeHtml(s.payment_method || 'Cash')}</span>
                                </div>
                            </div>
                        </div>
                        <div class="txn-right-block">
                            <div>
                                <div class="txn-amount-val">${amountStr}</div>
                                <div class="txn-time-stamp">${createdTime} ${createdDate}</div>
                            </div>
                            <div class="txn-eye-action-badge" title="View Receipt">
                                <span class="material-symbols-rounded" style="font-size:18px;">visibility</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    } catch(err) {
        mount.innerHTML = `
            <div style="text-align:center; padding:30px 20px; color:#ef4444;">
                <span class="material-symbols-rounded" style="font-size:38px; margin-bottom:6px;">error</span>
                <div style="font-size:13px; font-weight: 700;">Failed to load sales</div>
                <div style="font-size:11.5px; color:#94a3b8; margin-top:4px;">${escapeHtml(err.message)}</div>
            </div>
        `;
    }
}

function viewSaleReceipt(invoiceId, invoiceNo) {
    const isInsidePages = window.location.pathname.includes('/pages/') || window.location.href.includes('/pages/');
    const isReceiptPage = window.location.pathname.endsWith('sale-receipt.html') || window.location.href.includes('sale-receipt.html');

    if (!isReceiptPage) {
        navigateTo('sale-receipt', `id=${encodeURIComponent(invoiceId)}&no=${encodeURIComponent(invoiceNo)}`);
        return;
    }

    const frame = document.getElementById('receipt-full-frame');
    const titleEl = document.getElementById('receipt-screen-title');

    if (titleEl) titleEl.textContent = `Receipt: ${decodeURIComponent(invoiceNo || '')}`;

    if (typeof isGuestSession === 'function' && isGuestSession()) {
        const guestHtml = (typeof generateGuestThermalReceiptHtml === 'function')
            ? generateGuestThermalReceiptHtml(invoiceId, invoiceNo)
            : '';
        if (frame) {
            frame.removeAttribute('src');
            frame.srcdoc = guestHtml;
        }
    } else {
        const baseUrl = (AppState.serverUrl || getBaseServerUrl()).replace(/\/+$/, '');
        const receiptUrl = `${baseUrl}/scanner/receipt_html?code=${encodeURIComponent(AppState.pairCode)}&id=${invoiceId}`;
        currentReceiptUrl = receiptUrl;
        if (frame) {
            frame.removeAttribute('srcdoc');
            frame.src = receiptUrl;
        }
    }
}

function refreshReceiptFrame() {
    const frame = document.getElementById('receipt-full-frame');
    if (typeof isGuestSession === 'function' && isGuestSession()) {
        const params = new URLSearchParams(window.location.search);
        const invId = params.get('id');
        const invNo = params.get('no');
        const guestHtml = (typeof generateGuestThermalReceiptHtml === 'function')
            ? generateGuestThermalReceiptHtml(invId, invNo)
            : '';
        if (frame) {
            frame.srcdoc = guestHtml;
            showAppToast('Receipt refreshed', 'info');
        }
        return;
    }

    if (frame && currentReceiptUrl) {
        frame.src = currentReceiptUrl;
        showAppToast('Receipt refreshed', 'info');
    }
}

/**
 * Customers Directory
 */
let customersSearchTimer = null;
function handleCustomersSearch(query) {
    clearTimeout(customersSearchTimer);
    customersSearchTimer = setTimeout(() => {
        fetchCustomers(query);
    }, 250);
}

async function fetchCustomers(searchQuery = '') {
    const container = document.getElementById('customers-list-container');
    const badge = document.getElementById('customers-count-badge');
    if (!container) return;

    if (!searchQuery) {
        container.innerHTML = `
            <div class="skeleton-card">
                <div class="skeleton-box" style="width:46px; height:46px; border-radius:50%;"></div>
                <div style="flex:1; display:flex; flex-direction:column; gap:6px;">
                    <div class="skeleton-box" style="width:50%; height:15px;"></div>
                    <div class="skeleton-box" style="width:65%; height:11px;"></div>
                </div>
            </div>
            <div class="skeleton-card">
                <div class="skeleton-box" style="width:46px; height:46px; border-radius:50%;"></div>
                <div style="flex:1; display:flex; flex-direction:column; gap:6px;">
                    <div class="skeleton-box" style="width:45%; height:15px;"></div>
                    <div class="skeleton-box" style="width:60%; height:11px;"></div>
                </div>
            </div>
        `;
    }

    try {
        const data = await apiFetch(`/scanner/customers?code=${encodeURIComponent(AppState.pairCode)}&q=${encodeURIComponent(searchQuery)}`);
        if (data.success && Array.isArray(data.customers)) {
            if (badge) badge.textContent = `${data.customers.length} Customers Found`;

            if (data.customers.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                        <span class="material-symbols-rounded" style="font-size:46px; color:#cbd5e1; margin-bottom:8px;">group_off</span>
                        <div style="font-size:14px; font-weight: 700; color:#64748b; margin-bottom:4px;">No Customers Found</div>
                        <div style="font-size:12px; color:#94a3b8;">No customer matches your search.</div>
                    </div>
                `;
                return;
            }

            container.innerHTML = data.customers.map(c => {
                const initial = (c.name || 'C').trim().charAt(0).toUpperCase();
                const totalDue = Number(c.total_due || 0);
                const hasDue = totalDue > 0;
                const dueText = c.formatted_due || `${AppState.currency} ${Math.round(totalDue).toLocaleString()}`;
                const ordersCount = c.total_orders || 0;

                return `
                    <div class="customer-item-card" onclick="viewCustomerProfile(${c.id})">
                        <div class="cust-left-block">
                            <div class="cust-avatar-circle">
                                ${escapeHtml(initial)}
                            </div>
                            <div class="cust-meta-wrap">
                                <div class="cust-name-title">${escapeHtml(c.name)}</div>
                                <div class="cust-sub-details">
                                    <span class="material-symbols-rounded" style="font-size:14px; color:#94a3b8;">call</span>
                                    <span>${escapeHtml(c.phone || 'No Phone')}</span>
                                    <span>&bull;</span>
                                    <span>${ordersCount} Orders</span>
                                </div>
                            </div>
                        </div>
                        <div class="cust-right-block">
                            ${hasDue ? `
                                <div class="cust-due-pill due">
                                    Due: ${dueText}
                                </div>
                            ` : `
                                <div class="cust-due-pill cleared">
                                    Cleared
                                </div>
                            `}
                            <span class="material-symbols-rounded cust-chevron-arrow">chevron_right</span>
                        </div>
                    </div>
                `;
            }).join('');
        }
    } catch(e) {
        container.innerHTML = `<div style="text-align:center; color:#ef4444; padding:20px;">Failed to load customers.</div>`;
    }
}

async function viewCustomerProfile(customerId) {
    const isProfilePage = window.location.pathname.endsWith('customer-profile.html') || window.location.href.includes('customer-profile.html');

    if (!isProfilePage) {
        navigateTo('customer-profile', `id=${encodeURIComponent(customerId)}`);
        return;
    }

    activeCustomerId = customerId;
    const nameEl = document.getElementById('cust-profile-name');
    const phoneEl = document.getElementById('cust-profile-phone');
    const addrEl = document.getElementById('cust-profile-address');
    const avatarEl = document.getElementById('cust-profile-avatar');
    const waBtn = document.getElementById('cust-profile-wa-btn');
    const callBtn = document.getElementById('cust-profile-call-btn');
    const purchasesEl = document.getElementById('cust-profile-purchases');
    const ordersCountEl = document.getElementById('cust-profile-orders-count');
    const paidEl = document.getElementById('cust-profile-paid');
    const dueEl = document.getElementById('cust-profile-due');
    const invListContainer = document.getElementById('cust-profile-invoices-list');
    const historyBadge = document.getElementById('cust-profile-history-badge');

    if (nameEl) nameEl.textContent = 'Loading...';

    try {
        const data = await apiFetch(`/scanner/customer_profile?code=${encodeURIComponent(AppState.pairCode)}&id=${customerId}`);
        if (data.success && data.customer) {
            const c = data.customer;
            const sum = data.summary || {};
            const invCount = sum.order_count || (data.invoices ? data.invoices.length : 0);

            if (nameEl) nameEl.textContent = c.name || 'Customer';
            if (phoneEl) phoneEl.textContent = c.phone || 'No Phone';
            if (addrEl) addrEl.textContent = c.address || 'Address not set';
            if (avatarEl) avatarEl.textContent = (c.name || 'C').trim().charAt(0).toUpperCase();

            if (c.phone) {
                const cleanPhone = c.phone.replace(/[^0-9]/g, '');
                if (waBtn) waBtn.href = data.whatsapp_url || `https://wa.me/${cleanPhone}`;
                if (callBtn) callBtn.href = `tel:${cleanPhone}`;
            }

            if (purchasesEl) purchasesEl.textContent = sum.formatted_purchases || `${AppState.currency} ${Math.round(sum.total_purchases || 0).toLocaleString()}`;
            if (ordersCountEl) ordersCountEl.textContent = `${invCount} orders`;
            if (paidEl) paidEl.textContent = sum.formatted_paid || `${AppState.currency} ${Math.round(sum.total_paid || 0).toLocaleString()}`;
            if (dueEl) dueEl.textContent = sum.formatted_due || `${AppState.currency} ${Math.round(sum.remaining_due || 0).toLocaleString()}`;
            if (historyBadge) historyBadge.textContent = `${invCount} invoices`;

            if (invListContainer && Array.isArray(data.invoices)) {
                if (data.invoices.length === 0) {
                    invListContainer.innerHTML = `<div style="text-align:center; padding:24px; color:#94a3b8; font-size:12.5px;">No purchase invoices for this customer.</div>`;
                } else {
                    invListContainer.innerHTML = data.invoices.map(inv => {
                        const isUnpaid = Number(inv.due_amount || 0) > 0 || (inv.status || '').toLowerCase() === 'unpaid';

                        return `
                            <div class="cust-invoice-card" onclick="viewSaleReceipt('${encodeURIComponent(inv.id)}', '${encodeURIComponent(inv.invoice_no)}')">
                                <!-- Top Row: Icon + Invoice No + Status Badge -->
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span class="material-symbols-rounded" style="font-size:18px; color:#0f6dff;">description</span>
                                        <span style="font-size:14px; font-weight: 700; color:#0f172a;">${escapeHtml(inv.invoice_no)}</span>
                                    </div>
                                    ${isUnpaid ? `
                                        <span style="background:#fee2e2; color:#ef4444; font-size:10px; font-weight: 700; padding:2px 7px; border-radius:6px; letter-spacing:0.3px;">UNPAID</span>
                                    ` : `
                                        <span style="background:#f0fdf4; color:#16a34a; font-size:10px; font-weight: 700; padding:2px 7px; border-radius:6px; letter-spacing:0.3px;">PAID</span>
                                    `}
                                </div>

                                <!-- Middle Row: Date Timestamp & Total Amount -->
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                                    <div style="font-size:12px; color:#64748b; font-weight: 600;">${escapeHtml(inv.created_at)}</div>
                                    <div style="font-size:15px; font-weight: 700; color:#0f172a;">${inv.formatted_total || `${AppState.currency} ${inv.total}`}</div>
                                </div>

                                <!-- Bottom Row: Due Status / Fully Paid + Eye Preview Icon -->
                                <div style="display:flex; justify-content:space-between; align-items:center; padding-top:8px; border-top:1px dashed #e2e8f0;">
                                    ${isUnpaid ? `
                                        <div style="font-size:12px; font-weight: 700; color:#ef4444;">Due: ${inv.formatted_due || `${AppState.currency} ${inv.due_amount}`}</div>
                                    ` : `
                                        <div style="font-size:12px; font-weight: 700; color:#16a34a;">Fully Paid</div>
                                    `}
                                    <div style="color:#0f6dff; display:flex; align-items:center;">
                                        <span class="material-symbols-rounded" style="font-size:20px;">visibility</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            }
        }
    } catch(e) {}
}

/**
 * Profile & Settings
 */
async function fetchProfile() {
    try {
        const data = await apiFetch(`/scanner/profile?code=${encodeURIComponent(AppState.pairCode)}`);
        if (data.success) {
            const nameEl = document.getElementById('profile-user-name');
            const heroEmailEl = document.getElementById('profile-hero-email');
            const emailEl = document.getElementById('profile-user-email');
            const phoneEl = document.getElementById('profile-user-phone');
            const bizEl = document.getElementById('profile-business-name');
            const roleBadgeEl = document.getElementById('profile-role-badge');
            const langEl = document.getElementById('profile-language-text');
            const avatarCharEl = document.getElementById('profile-avatar-char');

            const name = data.user_name || 'Usman';
            const role = data.user_role || 'Store Owner';
            const email = data.user_email || 'sales.support@fizzysole.com.pk';
            const phone = data.user_phone || '+923256240824';
            const business = data.business_name || AppState.storeName || 'Usman Mobile Center';

            if (nameEl) nameEl.textContent = name;
            if (heroEmailEl) heroEmailEl.textContent = email;
            if (emailEl) emailEl.textContent = email;
            if (phoneEl) phoneEl.textContent = phone;
            if (bizEl) bizEl.textContent = business;
            if (roleBadgeEl) roleBadgeEl.textContent = role;
            if (langEl) langEl.textContent = data.language || 'English';
            if (avatarCharEl) {
                avatarCharEl.textContent = (name || 'U').trim().charAt(0).toUpperCase();
            }
        }
    } catch(e) {}
}

/**
 * ─────────────────────────────────────────────────────────
 * Sound Tone Selection Modal & Real-Time Audition
 * ─────────────────────────────────────────────────────────
 */
function openSoundTonePickerModal() {
    ensureUniversalModalsMounted();
    const list = document.getElementById('sound-tone-options-list');
    if (!list) return;

    const currentTone = (typeof AppState !== 'undefined' && AppState.soundTone) ? AppState.soundTone : (localStorage.getItem('pos_scanner_sound_tone') || 'crystal');

    list.innerHTML = SoundTonesList.map(t => {
        const isSelected = (currentTone === t.id);
        return `
            <div class="settings-item-card clickable ${isSelected ? 'selected-tone-card' : ''}" 
                 onclick="selectSoundTone('${t.id}')"
                 style="cursor:pointer; padding:12px 14px; margin-bottom:8px; border-color:${isSelected ? '#0f6dff' : '#e2e8f0'}; background:${isSelected ? '#eff6ff' : '#ffffff'};">
                <div class="settings-item-left" style="gap:12px;">
                    <div class="settings-icon-badge ${isSelected ? 'blue' : 'slate'}" style="width:36px; height:36px; border-radius:10px;">
                        <span class="material-symbols-rounded" style="font-size:20px;">${t.icon || 'music_note'}</span>
                    </div>
                    <div>
                        <div class="settings-item-title" style="font-size:13.5px; color:#0f172a;">${t.name}</div>
                        <div class="settings-item-sub" style="font-size:11.5px; color:#64748b;">${t.desc}</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" class="settings-quick-action-btn" title="Play Preview" onclick="event.stopPropagation(); testPlayTone('${t.id}')" style="background:${isSelected ? '#0f6dff' : '#f1f5f9'}; color:${isSelected ? '#ffffff' : '#0f6dff'};">
                        <span class="material-symbols-rounded" style="font-size:18px;">play_arrow</span>
                    </button>
                    ${isSelected ? '<span class="material-symbols-rounded" style="color:#0f6dff; font-size:22px;">check_circle</span>' : ''}
                </div>
            </div>
        `;
    }).join('');

    openDialog('modal-sound-tone-picker');
}

function testPlayTone(toneId) {
    playProductSound(toneId);
    triggerHaptic([20]);
}

function selectSoundTone(toneId) {
    if (typeof AppState !== 'undefined') {
        AppState.soundTone = toneId;
    }
    localStorage.setItem('pos_scanner_sound_tone', toneId);
    playProductSound(toneId);
    triggerHaptic([25]);

    closeDialog('modal-sound-tone-picker');
    updateSettingsDisplayValues();

    const toneObj = SoundTonesList.find(t => t.id === toneId);
    showAppToast(`Scan sound set to: ${toneObj ? toneObj.name : toneId}`, 'success', 2200);
}

/**
 * ─────────────────────────────────────────────────────────
 * Vibration (Haptic) Selection Modal & Real-Time Testing
 * ─────────────────────────────────────────────────────────
 */
function openVibrationPickerModal() {
    ensureUniversalModalsMounted();
    const list = document.getElementById('vibration-options-list');
    if (!list) return;

    const currentPattern = (typeof AppState !== 'undefined' && AppState.vibrationPattern) ? AppState.vibrationPattern : (localStorage.getItem('pos_scanner_vib_pattern') || 'crisp');

    list.innerHTML = Object.entries(VibrationPatterns).map(([key, v]) => {
        const isSelected = (currentPattern === key);
        return `
            <div class="settings-item-card clickable ${isSelected ? 'selected-vib-card' : ''}" 
                 onclick="selectVibrationPattern('${key}')"
                 style="cursor:pointer; padding:12px 14px; margin-bottom:8px; border-color:${isSelected ? '#0f6dff' : '#e2e8f0'}; background:${isSelected ? '#eff6ff' : '#ffffff'};">
                <div class="settings-item-left" style="gap:12px;">
                    <div class="settings-icon-badge ${isSelected ? 'amber' : 'slate'}" style="width:36px; height:36px; border-radius:10px;">
                        <span class="material-symbols-rounded" style="font-size:20px;">vibration</span>
                    </div>
                    <div>
                        <div class="settings-item-title" style="font-size:13.5px; color:#0f172a;">${v.name}</div>
                        <div class="settings-item-sub" style="font-size:11.5px; color:#64748b;">${v.desc}</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" class="settings-quick-action-btn" title="Test Vibration" onclick="event.stopPropagation(); testVibratePattern('${key}')" style="background:${isSelected ? '#d97706' : '#f1f5f9'}; color:${isSelected ? '#ffffff' : '#d97706'};">
                        <span class="material-symbols-rounded" style="font-size:18px;">touch_app</span>
                    </button>
                    ${isSelected ? '<span class="material-symbols-rounded" style="color:#0f6dff; font-size:22px;">check_circle</span>' : ''}
                </div>
            </div>
        `;
    }).join('');

    openDialog('modal-vibration-picker');
}

function testVibratePattern(patternKey) {
    const patternObj = VibrationPatterns[patternKey];
    if (patternObj) {
        triggerHaptic(patternObj.pattern);
    }
}

function selectVibrationPattern(patternKey) {
    if (typeof AppState !== 'undefined') {
        AppState.vibrationPattern = patternKey;
    }
    localStorage.setItem('pos_scanner_vib_pattern', patternKey);
    testVibratePattern(patternKey);

    closeDialog('modal-vibration-picker');
    updateSettingsDisplayValues();

    const patternObj = VibrationPatterns[patternKey];
    showAppToast(`Haptic set to: ${patternObj ? patternObj.name : patternKey}`, 'success', 2200);
}

/**
 * ─────────────────────────────────────────────────────────
 * Currency Picker with Flags, Search Filter & Minimal Symbols
 * ─────────────────────────────────────────────────────────
 */
function openCurrencyPickerModal() {
    ensureUniversalModalsMounted();
    renderCurrencyPickerList('');
    openDialog('modal-currency-picker');
    
    // Clear search input if present
    const searchInput = document.getElementById('currency-picker-search-input');
    if (searchInput) {
        searchInput.value = '';
        setTimeout(() => searchInput.focus(), 150);
    }
}

function filterCurrencyPickerList(query) {
    renderCurrencyPickerList(query);
}

function renderCurrencyPickerList(query = '') {
    const list = document.getElementById('currency-options-list');
    if (!list) return;

    const currentCode = (typeof AppState !== 'undefined' ? AppState.currencyCode : (localStorage.getItem('pos_scanner_currency_code') || 'PKR'));
    const q = (query || '').toLowerCase().trim();

    const filtered = SupportedCurrencies.filter(c => {
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
            <div class="settings-item-card clickable ${isSelected ? 'selected-curr-card' : ''}" 
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

function selectAppCurrency(code, symbol) {
    if (typeof AppState !== 'undefined') {
        AppState.currency = symbol;
        AppState.currencyCode = code;
    }

    localStorage.setItem('pos_scanner_currency', symbol);
    localStorage.setItem('pos_scanner_currency_code', code);

    closeDialog('modal-currency-picker');
    renderLocalStateUI();
    updateSettingsDisplayValues();

    playProductSound();
    triggerHaptic([25]);

    showAppToast(`Currency set to ${symbol} (${code})`, 'success');
}

function calculateStorageStats() {
    let totalBytes = 0;
    try {
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            const val = localStorage.getItem(key);
            totalBytes += (key ? key.length : 0) + (val ? val.length : 0);
        }
    } catch(e) {}

    const kb = Math.max(0.8, (totalBytes / 1024)).toFixed(1);
    const lastSyncTime = localStorage.getItem('pos_scanner_last_sync');
    let syncText = 'Ready to sync';
    if (lastSyncTime) {
        const diff = Math.floor((Date.now() - Number(lastSyncTime)) / 1000);
        if (diff < 60) syncText = 'Just now';
        else if (diff < 3600) syncText = `${Math.floor(diff / 60)} mins ago`;
        else syncText = new Date(Number(lastSyncTime)).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    return {
        kb: kb,
        bytes: totalBytes,
        syncText: syncText
    };
}

function updateCacheModalStats() {
    const usageEl = document.getElementById('cache-stat-usage');
    const syncEl = document.getElementById('cache-stat-last-sync');
    const progressEl = document.getElementById('cache-stat-progress');

    const stats = calculateStorageStats();
    if (usageEl) usageEl.textContent = `${stats.kb} KB`;
    if (syncEl) syncEl.textContent = stats.syncText;
    if (progressEl) {
        const pct = Math.min(100, Math.max(12, Math.round((parseFloat(stats.kb) / 250) * 100)));
        progressEl.style.width = `${pct}%`;
    }
}

function openBackupModal() {
    ensureUniversalModalsMounted();
    updateCacheModalStats();
    openDialog('modal-backup-restore');
}

async function executeForceSync() {
    const btnIcon = document.getElementById('sync-btn-icon');
    const btnText = document.getElementById('sync-btn-text');
    if (btnIcon) btnIcon.style.animation = 'spin 0.8s linear infinite';
    if (btnText) btnText.textContent = 'Synchronizing...';

    triggerHaptic([25]);

    try {
        if (typeof fetchProducts === 'function') {
            await fetchProducts();
        }
        if (typeof fetchDashboardData === 'function') {
            await fetchDashboardData();
        }
        if (typeof fetchCustomers === 'function') {
            await fetchCustomers();
        }

        localStorage.setItem('pos_scanner_last_sync', String(Date.now()));
        updateCacheModalStats();

        playProductSound();
        triggerHaptic([30, 45, 30]);
        showAppToast('Catalog & Metrics Synchronized Successfully!', 'success', 2800);
    } catch(err) {
        showAppToast('Sync completed with local records', 'info', 2200);
    } finally {
        if (btnIcon) btnIcon.style.animation = '';
        if (btnText) btnText.textContent = 'Sync All Catalog & Metrics';
        setTimeout(() => closeDialog('modal-backup-restore'), 650);
    }
}

function executeClearCache() {
    triggerHaptic([40]);

    // Safely preserve auth & user settings
    const preserved = {
        token: localStorage.getItem('pos_scanner_token'),
        code: localStorage.getItem('pos_scanner_code'),
        server: localStorage.getItem('pos_scanner_server'),
        store: localStorage.getItem('pos_scanner_store'),
        terminal: localStorage.getItem('pos_scanner_terminal_id'),
        sound: localStorage.getItem('pos_scanner_sound'),
        tone: localStorage.getItem('pos_scanner_sound_tone'),
        vib: localStorage.getItem('pos_scanner_vib'),
        vibPattern: localStorage.getItem('pos_scanner_vib_pattern'),
        currency: localStorage.getItem('pos_scanner_currency'),
        currencyCode: localStorage.getItem('pos_scanner_currency_code')
    };

    localStorage.clear();

    // Restore preserved
    if (preserved.token) localStorage.setItem('pos_scanner_token', preserved.token);
    if (preserved.code) localStorage.setItem('pos_scanner_code', preserved.code);
    if (preserved.server) localStorage.setItem('pos_scanner_server', preserved.server);
    if (preserved.store) localStorage.setItem('pos_scanner_store', preserved.store);
    if (preserved.terminal) localStorage.setItem('pos_scanner_terminal_id', preserved.terminal);
    if (preserved.sound !== null) localStorage.setItem('pos_scanner_sound', preserved.sound);
    if (preserved.tone) localStorage.setItem('pos_scanner_sound_tone', preserved.tone);
    if (preserved.vib !== null) localStorage.setItem('pos_scanner_vib', preserved.vib);
    if (preserved.vibPattern) localStorage.setItem('pos_scanner_vib_pattern', preserved.vibPattern);
    if (preserved.currency) localStorage.setItem('pos_scanner_currency', preserved.currency);
    if (preserved.currencyCode) localStorage.setItem('pos_scanner_currency_code', preserved.currencyCode);

    updateCacheModalStats();
    showAppToast('Temporary Cache & Offline Logs Cleared', 'info', 2500);
    setTimeout(() => closeDialog('modal-backup-restore'), 400);
}

function syncLocalCache() {
    executeForceSync();
}

function clearAppCache() {
    executeClearCache();
}

function openHelpCenterModal() { openDialog('modal-help-center'); }
function openAboutAppModal() { openDialog('modal-about-app'); }

function openLogoutModal() {
    openDialog('modal-logout-confirm');
}

function executeLogout() {
    closeDialog('modal-logout-confirm');
    AppState.isAuthenticated = false;
    AppState.pairToken = '';
    AppState.pairCode = '';
    AppState.storeName = '';
    AppState.terminalId = '';
    localStorage.removeItem('pos_scanner_token');
    localStorage.removeItem('pos_scanner_code');
    localStorage.removeItem('pos_scanner_store');
    localStorage.removeItem('pos_scanner_terminal_id');
    showAppToast('Logged out successfully', 'info');
    navigateTo('welcome');
}

function updateSettingsDisplayValues() {
    // 1. Currency Display - Clean Symbol Only
    const currEl = document.getElementById('settings-current-currency-text');
    if (currEl) {
        const symbol = AppState.currency || 'Rs';
        currEl.innerHTML = `
            <span class="settings-pill-badge" style="background:#eff6ff; color:#0f6dff; border-color:#bfdbfe; font-size:13px; font-weight:700; padding:4px 12px;">
                <span>${symbol}</span>
                <span class="chevron" style="font-size:14px; margin-left:4px;">&gt;</span>
            </span>
        `;
    }

    // 2. Sound Tone Display
    const toneEl = document.getElementById('settings-current-tone-text');
    if (toneEl) {
        const activeTone = SoundTonesList.find(t => t.id === AppState.soundTone) || SoundTonesList[0];
        toneEl.innerHTML = `
            <span class="settings-pill-badge" style="background:#f1f5f9; color:#0f172a; border-color:#e2e8f0;">
                <span>${activeTone.name}</span>
                <span class="chevron" style="font-size:14px; margin-left:2px;">&gt;</span>
            </span>
        `;
    }

    // 3. Vibration Pattern Display
    const vibEl = document.getElementById('settings-current-vib-text');
    if (vibEl) {
        const activeVib = VibrationPatterns[AppState.vibrationPattern] || VibrationPatterns.crisp;
        vibEl.innerHTML = `
            <span class="settings-pill-badge" style="background:#f1f5f9; color:#0f172a; border-color:#e2e8f0;">
                <span>${activeVib.name}</span>
                <span class="chevron" style="font-size:14px; margin-left:2px;">&gt;</span>
            </span>
        `;
    }

    // 4. Server Host Display
    const serverEl = document.getElementById('settings-current-server-text');
    if (serverEl) {
        const rawUrl = AppState.serverUrl || getBaseServerUrl();
        const shortUrl = rawUrl.replace(/^https?:\/\//, '').replace(/\/+$/, '');
        serverEl.innerHTML = `
            <span class="settings-pill-badge" style="background:#ecfdf5; color:#059669; border-color:#a7f3d0; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:#10b981; margin-right:2px;"></span>
                ${shortUrl}
                <span class="chevron" style="font-size:14px; margin-left:2px;">&gt;</span>
            </span>
        `;
    }

    // 5. Store / Terminal Hero Card
    const storeHero = document.getElementById('settings-hero-store-name');
    const termHero = document.getElementById('settings-hero-terminal-id');
    if (storeHero) storeHero.textContent = AppState.storeName || 'DevAltum POS Store';
    if (termHero) termHero.textContent = `Terminal: ${AppState.terminalId || 'StarPOS-01'}`;
}

function initSettingsUI() {
    const notifyToggle = document.getElementById('setting-notifications-toggle') || document.getElementById('setting-notify-toggle');
    const soundToggle = document.getElementById('setting-sound-toggle');
    const vibToggle = document.getElementById('setting-vibration-toggle');

    if (notifyToggle) {
        notifyToggle.checked = (localStorage.getItem('pos_scanner_notifications') !== 'false');
        notifyToggle.onchange = () => {
            localStorage.setItem('pos_scanner_notifications', notifyToggle.checked);
            triggerHaptic(15);
        };
    }

    if (soundToggle) {
        soundToggle.checked = AppState.soundEnabled;
        soundToggle.onchange = () => {
            AppState.soundEnabled = soundToggle.checked;
            localStorage.setItem('pos_scanner_sound', soundToggle.checked);
            if (soundToggle.checked) playProductSound();
            triggerHaptic(15);
        };
    }

    if (vibToggle) {
        vibToggle.checked = AppState.vibrationEnabled;
        vibToggle.onchange = () => {
            AppState.vibrationEnabled = vibToggle.checked;
            localStorage.setItem('pos_scanner_vib', vibToggle.checked);
            if (vibToggle.checked) triggerHaptic([30]);
        };
    }

    updateSettingsDisplayValues();
}

function highlightActiveNavTab() {
    const path = window.location.pathname.toLowerCase();
    const tabs = document.querySelectorAll('.bottom-tab-link');
    tabs.forEach(tab => {
        const onclickAttr = tab.getAttribute('onclick') || '';
        if (
            (path.includes('dashboard') && onclickAttr.includes('dashboard')) ||
            (path.includes('sales') && onclickAttr.includes('sales')) ||
            (path.includes('products') && onclickAttr.includes('products')) ||
            (path.includes('profile') && onclickAttr.includes('profile'))
        ) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });
}

let dashLivePollTimer = null;

async function initApp() {
    setupPinInputs();
    initSettingsUI();
    renderLocalStateUI();
    highlightActiveNavTab();

    const path = window.location.pathname.toLowerCase();

    if (!AppState.pairCode) {
        AppState.pairCode = localStorage.getItem('pos_scanner_code') || '100000';
    }

    // Page-specific initialization hooks for standalone HTML pages
    if (path.endsWith('dashboard.html') || document.getElementById('view-dashboard')) {
        fetchDashboardData();
        if (!dashLivePollTimer) {
            dashLivePollTimer = setInterval(() => {
                fetchDashboardData();
            }, 7000);
        }
    } else if (path.endsWith('sales-history.html') || document.getElementById('view-history')) {
        fetchSales(currentSalesPeriod, currentSalesCustomDate, currentSalesSearchQuery);
    } else if (path.endsWith('products.html') || document.getElementById('view-products')) {
        fetchProducts();
    } else if (path.endsWith('customers.html') || document.getElementById('view-customers')) {
        fetchCustomers();
    } else if (path.endsWith('profile.html') || document.getElementById('view-profile')) {
        fetchProfile();
    } else if (path.endsWith('add-product.html') || document.getElementById('view-add-product')) {
        checkAndPopulateEditProductForm();
    } else if (path.endsWith('settings.html') || document.getElementById('view-settings')) {
        const currEl = document.getElementById('settings-current-currency-text');
        if (currEl) currEl.innerHTML = `<span>${AppState.currencyCode || 'PKR'} (${AppState.currency || 'Rs'}) &gt;</span>`;
    } else if (path.endsWith('scanner.html') || document.getElementById('view-scanner')) {
        if (typeof startCameraEngine === 'function') startCameraEngine();
    }
}

// Start Application on DOM Ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}
