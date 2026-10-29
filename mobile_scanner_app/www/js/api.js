/**
 * DevAltum POS Mobile Companion - API & State Module
 * Complete Offline-First Guest Sandbox & Real-Time Sync Engine
 */

const SupportedCurrencies = [
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
    { code: 'QAR', symbol: 'QAR', name: 'Qatari Riyal', flag: '🇶🇦', country: 'Qatar' },
    { code: 'OMR', symbol: 'OMR', name: 'Omani Rial', flag: '🇴🇲', country: 'Oman' },
    { code: 'KWD', symbol: 'KWD', name: 'Kuwaiti Dinar', flag: '🇰🇼', country: 'Kuwait' },
    { code: 'BHD', symbol: 'BHD', name: 'Bahraini Dinar', flag: '🇧🇭', country: 'Bahrain' },
    { code: 'MYR', symbol: 'RM', name: 'Malaysian Ringgit', flag: '🇲🇾', country: 'Malaysia' },
    { code: 'SGD', symbol: 'S$', name: 'Singapore Dollar', flag: '🇸🇬', country: 'Singapore' },
    { code: 'CNY', symbol: '¥', name: 'Chinese Yuan', flag: '🇨🇳', country: 'China' },
    { code: 'JPY', symbol: '¥', name: 'Japanese Yen', flag: '🇯🇵', country: 'Japan' }
];

function getBaseServerUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    const qServer = urlParams.get('server');
    if (qServer) {
        const cleaned = decodeURIComponent(qServer).replace(/\/+$/, '');
        localStorage.setItem('pos_scanner_server', cleaned);
        return cleaned;
    }

    const saved = localStorage.getItem('pos_scanner_server');
    if (saved && saved.trim() !== '') {
        return saved.trim().replace(/\/+$/, '');
    }

    const origin = window.location.origin || '';
    const pathname = window.location.pathname || '';

    const scannerIdx = pathname.indexOf('/mobile_scanner_app/');
    if (scannerIdx !== -1) {
        const subPath = pathname.substring(0, scannerIdx);
        return (origin + subPath).replace(/\/+$/, '');
    }

    return origin.replace(/\/+$/, '');
}

const AppState = {
    serverUrl: getBaseServerUrl(),
    pairToken: localStorage.getItem('pos_scanner_token') || '',
    pairCode: localStorage.getItem('pos_scanner_code') || '748642',
    storeName: localStorage.getItem('pos_scanner_store') || 'DevAltum Store',
    terminalId: localStorage.getItem('pos_scanner_terminal_id') || 'StarPOS-01',
    currency: localStorage.getItem('pos_scanner_currency') || 'Rs',
    currencyCode: localStorage.getItem('pos_scanner_currency_code') || 'PKR',
    currencyFlag: localStorage.getItem('pos_scanner_currency_flag') || '🇵🇰',
    soundTone: localStorage.getItem('pos_scanner_sound_tone') || 'crystal',
    vibrationPattern: localStorage.getItem('pos_scanner_vib_pattern') || 'crisp',
    connectedAt: localStorage.getItem('pos_scanner_connected_at') || 'Online',
    currentView: 'view-welcome',
    isAuthenticated: Boolean(localStorage.getItem('pos_scanner_token') && localStorage.getItem('pos_scanner_code')),
    isGuestMode: localStorage.getItem('pos_is_guest') === 'true',
    videoStream: null,
    codeReader: null,
    isTorchOn: false,
    facingMode: 'environment',
    lastScannedBarcode: '',
    lastScanTime: 0,
    soundEnabled: localStorage.getItem('pos_scanner_sound') !== 'false',
    vibrationEnabled: localStorage.getItem('pos_scanner_vib') !== 'false'
};

// Check if URL parameters have pair code
const initialUrlParams = new URLSearchParams(window.location.search);
const queryCode = initialUrlParams.get('code') || initialUrlParams.get('pin');
if (queryCode && queryCode.length === 6) {
    AppState.pairCode = queryCode;
}

// ─────────────────────────────────────────────────────────
// REALISTIC DEFAULT GUEST DATABASES (CATALOG, CUSTOMERS, SALES)
// ─────────────────────────────────────────────────────────

const DefaultGuestCatalog = [
    { id: 101, name: 'Coca-Cola 500ml Bottle', barcode: '5449000000996', price: 150, cost: 110, wholesale_price: 135, stock: 48, min_stock: 10, unit_type: 'pcs', unit_value: 1, category: 'Beverages', model_number: 'BEV-CC500' },
    { id: 102, name: 'Lays Classic Salted Chips', barcode: '028400010992', price: 120, cost: 85, wholesale_price: 105, stock: 35, min_stock: 8, unit_type: 'pcs', unit_value: 1, category: 'Snacks', model_number: 'SNK-LAYS' },
    { id: 103, name: 'Nestle Pure Life 1.5L Water', barcode: '7613035659876', price: 110, cost: 70, wholesale_price: 95, stock: 60, min_stock: 12, unit_type: 'pcs', unit_value: 1, category: 'Beverages', model_number: 'BEV-NPL15' },
    { id: 104, name: 'DevAltum Wireless Mouse Pro', barcode: '8901234567890', price: 1850, cost: 1200, wholesale_price: 1600, stock: 15, min_stock: 4, unit_type: 'pcs', unit_value: 1, category: 'Electronics', model_number: 'DEV-WM01' },
    { id: 105, name: 'Cadbury Dairy Milk Silk 100g', barcode: '7622210287123', price: 320, cost: 230, wholesale_price: 285, stock: 24, min_stock: 5, unit_type: 'pcs', unit_value: 1, category: 'Chocolates', model_number: 'CHO-CDMS' },
    { id: 106, name: 'Red Bull Energy Drink 250ml', barcode: '9002490100070', price: 480, cost: 360, wholesale_price: 430, stock: 30, min_stock: 6, unit_type: 'pcs', unit_value: 1, category: 'Beverages', model_number: 'BEV-RB250' },
    { id: 107, name: 'Super Basmati Rice (per KG)', barcode: '2001068423600', price: 1000, cost: 750, wholesale_price: 900, stock: 120.5, min_stock: 20, unit_type: 'kg', unit_value: 1, category: 'Grocery', model_number: 'GRO-RICE' },
    { id: 108, name: 'Apple 20W USB-C Fast Adapter', barcode: '194252157008', price: 5500, cost: 4200, wholesale_price: 4900, stock: 8, min_stock: 2, unit_type: 'pcs', unit_value: 1, category: 'Electronics', model_number: 'ACC-A20W' }
];

const DefaultGuestCustomers = [
    { id: 201, name: 'Usman Ali', phone: '0300-1234567', address: 'Shop #12, Commercial Market, Lahore', total_orders: 8, total_spent: 24500, total_due: 3200, created_at: '2026-02-15' },
    { id: 202, name: 'Hamad Yasin', phone: '0312-9876543', address: 'House 45, Sector F-8/2, Islamabad', total_orders: 14, total_spent: 58200, total_due: 0, created_at: '2026-01-20' },
    { id: 203, name: 'Muhammad Bilal', phone: '0333-5551234', address: 'Main Bazaar, Saddar, Rawalpindi', total_orders: 5, total_spent: 12800, total_due: 1500, created_at: '2026-02-28' },
    { id: 204, name: 'Zeeshan Ahmed', phone: '0321-4447890', address: 'Gulberg III, Near MM Alam Rd, Lahore', total_orders: 19, total_spent: 89400, total_due: 0, created_at: '2026-01-05' },
    { id: 205, name: 'Farhan Tariq', phone: '0345-6663210', address: 'Block B, North Nazimabad, Karachi', total_orders: 3, total_spent: 6400, total_due: 850, created_at: '2026-03-01' }
];

const nowTs = new Date();
const todayDateStr = nowTs.toISOString().split('T')[0];
const yesterdayDateStr = new Date(Date.now() - 86400000).toISOString().split('T')[0];

const DefaultGuestSales = [
    {
        id: 301,
        invoice_no: 'INV-260301-A101',
        customer_id: 202,
        customer_name: 'Hamad Yasin',
        customer_phone: '0312-9876543',
        total: 5850,
        subtotal: 5850,
        discount: 0,
        tax: 0,
        items_count: 2,
        payment_method: 'Cash',
        time: '02:45 PM',
        date: todayDateStr,
        created_at: new Date().toISOString(),
        items: [
            { id: 104, product_name: 'DevAltum Wireless Mouse Pro', quantity: 1, price: 1850, total: 1850 },
            { id: 107, product_name: 'Super Basmati Rice (per KG)', quantity: 4, price: 1000, total: 4000 }
        ]
    },
    {
        id: 302,
        invoice_no: 'INV-260301-B202',
        customer_id: 201,
        customer_name: 'Usman Ali',
        customer_phone: '0300-1234567',
        total: 1450,
        subtotal: 1450,
        discount: 0,
        tax: 0,
        items_count: 4,
        payment_method: 'Online',
        time: '11:15 AM',
        date: todayDateStr,
        created_at: new Date(Date.now() - 3600000 * 3).toISOString(),
        items: [
            { id: 101, product_name: 'Coca-Cola 500ml Bottle', quantity: 3, price: 150, total: 450 },
            { id: 107, product_name: 'Super Basmati Rice (per KG)', quantity: 1, price: 1000, total: 1000 }
        ]
    },
    {
        id: 303,
        invoice_no: 'INV-260228-C303',
        customer_id: 204,
        customer_name: 'Zeeshan Ahmed',
        customer_phone: '0321-4447890',
        total: 5500,
        subtotal: 5500,
        discount: 0,
        tax: 0,
        items_count: 1,
        payment_method: 'Credit',
        time: '04:20 PM',
        date: yesterdayDateStr,
        created_at: new Date(Date.now() - 86400000).toISOString(),
        items: [
            { id: 108, product_name: 'Apple 20W USB-C Fast Adapter', quantity: 1, price: 5500, total: 5500 }
        ]
    },
    {
        id: 304,
        invoice_no: 'INV-260228-D404',
        customer_id: 0,
        customer_name: 'Walk-in Customer',
        customer_phone: '',
        total: 920,
        subtotal: 920,
        discount: 0,
        tax: 0,
        items_count: 3,
        payment_method: 'Cash',
        time: '01:10 PM',
        date: yesterdayDateStr,
        created_at: new Date(Date.now() - 86400000 - 3600000 * 2).toISOString(),
        items: [
            { id: 102, product_name: 'Lays Classic Salted Chips', quantity: 2, price: 120, total: 240 },
            { id: 106, product_name: 'Red Bull Energy Drink 250ml', quantity: 1, price: 480, total: 480 },
            { id: 103, product_name: 'Nestle Pure Life 1.5L Water', quantity: 2, price: 100, total: 200 }
        ]
    }
];

function initGuestDatabase() {
    if (!localStorage.getItem('pos_guest_catalog')) {
        localStorage.setItem('pos_guest_catalog', JSON.stringify(DefaultGuestCatalog));
    }
    if (!localStorage.getItem('pos_guest_customers')) {
        localStorage.setItem('pos_guest_customers', JSON.stringify(DefaultGuestCustomers));
    }
    if (!localStorage.getItem('pos_guest_sales_history')) {
        localStorage.setItem('pos_guest_sales_history', JSON.stringify(DefaultGuestSales));
    }
    if (!localStorage.getItem('pos_guest_stats')) {
        localStorage.setItem('pos_guest_stats', JSON.stringify({
            today_sales: 7300,
            today_orders: 2,
            today_scans: 18,
            net_profit: 2450
        }));
    }
}

function isGuestSession() {
    return localStorage.getItem('pos_is_guest') === 'true';
}

function enableGuestMode() {
    initGuestDatabase();
    localStorage.setItem('pos_is_guest', 'true');
    localStorage.setItem('pos_scanner_token', 'GUEST_DEMO_TOKEN');
    localStorage.setItem('pos_scanner_code', 'GUEST1');
    localStorage.setItem('pos_scanner_store', 'Demo Store (Guest Sandbox)');
    localStorage.setItem('pos_scanner_terminal_id', 'GuestTerminal-01');
    localStorage.setItem('pos_scanner_connected_at', new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));

    AppState.isAuthenticated = true;
    AppState.isGuestMode = true;
    AppState.storeName = 'Demo Store (Guest Sandbox)';
    AppState.pairCode = 'GUEST1';
    AppState.pairToken = 'GUEST_DEMO_TOKEN';
    AppState.terminalId = 'GuestTerminal-01';

    if (typeof showAppToast === 'function') {
        showAppToast('Guest Sandbox Mode Activated!', 'success', 2500);
    }
}

function exitGuestMode() {
    localStorage.removeItem('pos_is_guest');
    localStorage.removeItem('pos_scanner_token');
    localStorage.removeItem('pos_scanner_code');
    localStorage.removeItem('pos_scanner_store');
    AppState.isAuthenticated = false;
    AppState.isGuestMode = false;
}

// Check initial guest mode state on boot
if (isGuestSession()) {
    initGuestDatabase();
    AppState.isGuestMode = true;
    AppState.isAuthenticated = true;
    AppState.storeName = localStorage.getItem('pos_scanner_store') || 'Demo Store (Guest Sandbox)';
}

/**
 * Universal Authenticated API Fetch with Seamless Guest Sandbox Interceptor
 */
async function apiFetch(endpoint, options = {}) {
    const rawEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    const [pathPart, queryPart] = rawEndpoint.split('?');
    const queryParams = new URLSearchParams(queryPart || '');

    // ─────────────────────────────────────────────────────────
    // GUEST SANDBOX MOCK INTERCEPTOR
    // ─────────────────────────────────────────────────────────
    if (isGuestSession()) {
        initGuestDatabase();
        let catalog = JSON.parse(localStorage.getItem('pos_guest_catalog') || '[]');
        let customers = JSON.parse(localStorage.getItem('pos_guest_customers') || '[]');
        let sales = JSON.parse(localStorage.getItem('pos_guest_sales_history') || '[]');
        let stats = JSON.parse(localStorage.getItem('pos_guest_stats') || '{}');
        const currSym = AppState.currency || 'Rs';

        // 1. PRODUCTS LIST (/scanner/products, /products, /api/products)
        if (pathPart === '/scanner/products' || pathPart === '/products' || pathPart === '/api/products') {
            const search = (queryParams.get('q') || queryParams.get('search') || '').toLowerCase().trim();
            let result = catalog;
            if (search) {
                result = catalog.filter(p => 
                    (p.name && p.name.toLowerCase().includes(search)) ||
                    (p.barcode && p.barcode.toLowerCase().includes(search)) ||
                    (p.model_number && p.model_number.toLowerCase().includes(search))
                );
            }
            return {
                success: true,
                products: result.map(p => ({
                    ...p,
                    formatted_price: `${currSym} ${Number(p.price || 0).toLocaleString()}`
                }))
            };
        }

        // 2. GET SINGLE PRODUCT (/scanner/get_product)
        if (pathPart === '/scanner/get_product') {
            const id = queryParams.get('id');
            const barcode = queryParams.get('barcode');
            const target = catalog.find(p => (id && String(p.id) === String(id)) || (barcode && String(p.barcode) === String(barcode)));
            return {
                success: Boolean(target),
                product: target || null
            };
        }

        // 3. BARCODE LOOKUP (/scanner/lookup_barcode)
        if (pathPart === '/scanner/lookup_barcode') {
            const barcode = String(queryParams.get('barcode') || '').trim();
            const target = catalog.find(p => String(p.barcode).trim() === barcode);
            return {
                success: Boolean(target),
                product: target || null
            };
        }

        // 4. ADD PRODUCT (/scanner/add_product, /scanner/add-product)
        if (pathPart === '/scanner/add_product' || pathPart === '/scanner/add-product') {
            let body = {};
            try { body = typeof options.body === 'string' ? JSON.parse(options.body) : (options.body || {}); } catch(e) {}
            
            const newProd = {
                id: Date.now(),
                name: body.name || 'New Product',
                barcode: String(body.barcode || ('890' + Math.floor(100000 + Math.random() * 900000))).trim(),
                price: Number(body.price || 0),
                cost: Number(body.cost || 0),
                wholesale_price: Number(body.wholesale_price || 0),
                stock: Number(body.stock || 0),
                min_stock: Number(body.min_stock || 5),
                unit_type: body.unit_type || 'pcs',
                unit_value: Number(body.unit_value || 1),
                model_number: body.model_number || body.sku || '',
                batch_number: body.batch_number || '',
                expiry_date: body.expiry_date || '',
                category: body.category || 'General'
            };

            catalog.unshift(newProd);
            localStorage.setItem('pos_guest_catalog', JSON.stringify(catalog));

            return {
                success: true,
                message: 'Product registered successfully in Guest Catalog!',
                product: newProd
            };
        }

        // 5. UPDATE PRODUCT (/scanner/update_product)
        if (pathPart === '/scanner/update_product') {
            let body = {};
            try { body = typeof options.body === 'string' ? JSON.parse(options.body) : (options.body || {}); } catch(e) {}
            const editId = body.id;
            const queryBarcode = String(body.barcode || '').trim();

            const idx = catalog.findIndex(item => (editId && String(item.id) === String(editId)) || (queryBarcode && String(item.barcode) === queryBarcode));
            if (idx !== -1) {
                catalog[idx] = {
                    ...catalog[idx],
                    name: body.name || catalog[idx].name,
                    barcode: queryBarcode || catalog[idx].barcode,
                    price: Number(body.price ?? catalog[idx].price),
                    cost: Number(body.cost ?? catalog[idx].cost),
                    wholesale_price: Number(body.wholesale_price ?? catalog[idx].wholesale_price),
                    stock: Number(body.stock ?? catalog[idx].stock),
                    min_stock: Number(body.min_stock ?? catalog[idx].min_stock),
                    unit_type: body.unit_type || catalog[idx].unit_type || 'pcs',
                    model_number: body.model_number || body.sku || catalog[idx].model_number || '',
                    batch_number: body.batch_number || catalog[idx].batch_number || '',
                    expiry_date: body.expiry_date || catalog[idx].expiry_date || ''
                };
                localStorage.setItem('pos_guest_catalog', JSON.stringify(catalog));
                return {
                    success: true,
                    message: 'Product updated successfully!',
                    product: catalog[idx]
                };
            }
            return { success: false, message: 'Product not found to update' };
        }

        // 6. DELETE PRODUCT (/scanner/delete_product, /scanner/delete-product)
        if (pathPart === '/scanner/delete_product' || pathPart === '/scanner/delete-product') {
            let body = {};
            try { body = typeof options.body === 'string' ? JSON.parse(options.body) : (options.body || {}); } catch(e) {}
            const delId = body.id;
            const delBarcode = String(body.barcode || '').trim();

            const updated = catalog.filter(item => (!delId || String(item.id) !== String(delId)) && (!delBarcode || String(item.barcode) !== delBarcode));
            localStorage.setItem('pos_guest_catalog', JSON.stringify(updated));
            return { success: true, message: 'Product deleted from Guest Catalog!' };
        }

        // 7. REAL-TIME BARCODE SCAN (/scanner/scan)
        if (pathPart === '/scanner/scan') {
            let body = {};
            try { body = typeof options.body === 'string' ? JSON.parse(options.body) : (options.body || {}); } catch(e) {}
            const queryBarcode = String(body.barcode || '').trim();

            const found = catalog.find(item => String(item.barcode).trim() === queryBarcode || String(item.model_number).trim() === queryBarcode);

            if (found) {
                if (body.scan_context === 'checkout') {
                    stats.today_scans = (stats.today_scans || 0) + 1;
                    localStorage.setItem('pos_guest_stats', JSON.stringify(stats));
                }
                return {
                    success: true,
                    found: true,
                    product: {
                        id: found.id,
                        name: found.name,
                        barcode: found.barcode,
                        price: found.price,
                        formatted_price: `${currSym} ${Number(found.price).toLocaleString()}`,
                        stock: found.stock,
                        unit: found.unit_type || 'pcs'
                    },
                    message: `Added: ${found.name}`
                };
            } else {
                return { success: true, found: false, message: 'Barcode not found' };
            }
        }

        // 8. SALES HISTORY (/scanner/sales, /sales, /scanner/transactions)
        if (pathPart === '/scanner/sales' || pathPart === '/sales' || pathPart === '/scanner/transactions') {
            const period = (queryParams.get('period') || 'today').toLowerCase();
            const search = (queryParams.get('q') || '').toLowerCase().trim();
            const customDate = queryParams.get('custom_date') || '';

            let filtered = sales;
            const todayStr = new Date().toISOString().split('T')[0];
            const yesterdayStr = new Date(Date.now() - 86400000).toISOString().split('T')[0];

            if (period === 'today') {
                filtered = sales.filter(s => (s.date === todayStr) || (s.created_at && s.created_at.startsWith(todayStr)));
            } else if (period === 'yesterday') {
                filtered = sales.filter(s => (s.date === yesterdayStr) || (s.created_at && s.created_at.startsWith(yesterdayStr)));
            } else if (period === 'custom' && customDate) {
                filtered = sales.filter(s => (s.date === customDate) || (s.created_at && s.created_at.startsWith(customDate)));
            }

            if (search) {
                filtered = filtered.filter(s => 
                    (s.invoice_no && s.invoice_no.toLowerCase().includes(search)) ||
                    (s.customer_name && s.customer_name.toLowerCase().includes(search)) ||
                    (s.customer_phone && s.customer_phone.toLowerCase().includes(search))
                );
            }

            let sumTotal = 0;
            filtered.forEach(s => { sumTotal += Number(s.total || s.amount || 0); });

            return {
                success: true,
                sales: filtered.map(s => ({
                    ...s,
                    formatted_amount: `${currSym} ${Number(s.total || s.amount || 0).toLocaleString()}`
                })),
                transactions: filtered,
                total_sales: sumTotal,
                formatted_total: `${currSym} ${sumTotal.toLocaleString()}`
            };
        }

        // 9. CUSTOMERS DIRECTORY (/scanner/customers, /customers)
        if (pathPart === '/scanner/customers' || pathPart === '/customers') {
            const search = (queryParams.get('q') || '').toLowerCase().trim();
            let result = customers;
            if (search) {
                result = customers.filter(c =>
                    (c.name && c.name.toLowerCase().includes(search)) ||
                    (c.phone && c.phone.toLowerCase().includes(search)) ||
                    (c.address && c.address.toLowerCase().includes(search))
                );
            }
            return {
                success: true,
                customers: result.map(c => ({
                    ...c,
                    formatted_due: `${currSym} ${Number(c.total_due || 0).toLocaleString()}`,
                    formatted_spent: `${currSym} ${Number(c.total_spent || 0).toLocaleString()}`
                }))
            };
        }

        // 10. CUSTOMER PROFILE (/scanner/customer_profile, /scanner/get_customer)
        if (pathPart === '/scanner/customer_profile' || pathPart === '/scanner/get_customer') {
            const id = queryParams.get('id');
            const cust = customers.find(c => String(c.id) === String(id)) || customers[0];
            const custInvoices = sales.filter(s => String(s.customer_id) === String(cust?.id || ''));

            const totalSpent = Number(cust?.total_spent || 0);
            const totalDue = Number(cust?.total_due || 0);
            const totalPaid = Math.max(0, totalSpent - totalDue);
            const cleanPhone = (cust?.phone || '').replace(/[^0-9]/g, '');

            return {
                success: Boolean(cust),
                customer: cust ? {
                    ...cust,
                    formatted_due: `${currSym} ${totalDue.toLocaleString()}`,
                    formatted_spent: `${currSym} ${totalSpent.toLocaleString()}`
                } : null,
                summary: {
                    total_purchases: totalSpent,
                    formatted_purchases: `${currSym} ${totalSpent.toLocaleString()}`,
                    total_paid: totalPaid,
                    formatted_paid: `${currSym} ${totalPaid.toLocaleString()}`,
                    remaining_due: totalDue,
                    formatted_due: `${currSym} ${totalDue.toLocaleString()}`,
                    order_count: cust?.total_orders || custInvoices.length
                },
                whatsapp_url: cleanPhone ? `https://wa.me/${cleanPhone}` : '#',
                invoices: custInvoices.map(s => ({
                    id: s.id,
                    invoice_no: s.invoice_no,
                    total: s.total,
                    formatted_total: `${currSym} ${Number(s.total || 0).toLocaleString()}`,
                    due_amount: (s.payment_method || '').toLowerCase() === 'credit' ? s.total : 0,
                    status: (s.payment_method || '').toLowerCase() === 'credit' ? 'unpaid' : 'paid',
                    created_at: s.created_at ? new Date(s.created_at).toLocaleDateString([], { day: '2-digit', month: 'short', year: 'numeric' }) : 'Recent'
                }))
            };
        }

        // 11. THERMAL RECEIPT HTML VIEW (/scanner/receipt_html)
        if (pathPart === '/scanner/receipt_html') {
            const id = queryParams.get('id');
            return generateGuestThermalReceiptHtml(id);
        }

        // 11. DASHBOARD METRICS (/scanner/metrics, /dashboard/stats)
        if (pathPart === '/scanner/metrics' || pathPart === '/dashboard/stats') {
            const todayStr = new Date().toISOString().split('T')[0];
            const todaySalesList = sales.filter(s => (s.date === todayStr) || (s.created_at && s.created_at.startsWith(todayStr)));
            let todaySum = 0;
            todaySalesList.forEach(s => { todaySum += Number(s.total || s.amount || 0); });

            return {
                success: true,
                data: {
                    today_sales: todaySum || stats.today_sales || 7300,
                    formatted_today_sales: `${currSym} ${(todaySum || stats.today_sales || 7300).toLocaleString()}`,
                    today_orders: todaySalesList.length || stats.today_orders || 2,
                    today_scans: stats.today_scans || 18,
                    catalog_count: catalog.length,
                    net_profit: Math.round((todaySum || 7300) * 0.35)
                }
            };
        }

        // 12. CHECKOUT SALE (/scanner/checkout, /pos/checkout)
        if (pathPart === '/scanner/checkout' || pathPart === '/pos/checkout') {
            let body = {};
            try { body = typeof options.body === 'string' ? JSON.parse(options.body) : (options.body || {}); } catch(e) {}

            const items = body.items || [];
            const custName = body.customer_name || 'Walk-in Customer';
            const custPhone = body.customer_phone || '';
            const paymentMethod = body.payment_method || 'Cash';
            const discount = Number(body.discount || 0);
            const taxRate = Number(body.tax_rate || 0);
            const paidAmount = Number(body.paid_amount || 0);
            const creditDueDate = body.credit_due_date || '';

            let subtotal = 0;
            items.forEach(it => {
                const qty = Number(it.quantity || it.qty || 1);
                const pr = Number(it.price || 0);
                subtotal += (qty * pr);

                // Deduct from guest catalog
                const catItem = catalog.find(c => String(c.id) === String(it.id) || (it.barcode && String(c.barcode) === String(it.barcode)));
                if (catItem) {
                    catItem.stock = Math.max(0, Number((catItem.stock - qty).toFixed(3)));
                }
            });
            localStorage.setItem('pos_guest_catalog', JSON.stringify(catalog));

            const discountAmount = Math.min(subtotal, discount);
            const taxAmount = (taxRate > 0) ? Math.round(((subtotal - discountAmount) * taxRate) / 100) : 0;
            const grandTotal = Math.round(subtotal - discountAmount + taxAmount);
            const isCreditSale = paymentMethod.toLowerCase() === 'credit';
            const actualPaid = isCreditSale ? paidAmount : grandTotal;
            const balanceDue = isCreditSale ? Math.max(0, grandTotal - paidAmount) : 0;

            const invoiceNo = 'INV-' + new Date().toISOString().slice(2, 10).replace(/-/g, '') + '-' + Math.floor(1000 + Math.random() * 9000);
            const invoiceId = Date.now();

            // Record in guest sales
            const newSale = {
                id: invoiceId,
                invoice_no: invoiceNo,
                customer_name: custName,
                customer_phone: custPhone,
                total: grandTotal,
                subtotal: subtotal,
                discount: discountAmount,
                tax: taxAmount,
                items_count: items.length,
                payment_method: paymentMethod,
                paid_amount: actualPaid,
                balance_due: balanceDue,
                credit_due_date: creditDueDate,
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                date: new Date().toISOString().split('T')[0],
                created_at: new Date().toISOString(),
                items: items.map(it => ({
                    product_name: it.name,
                    quantity: it.quantity || 1,
                    price: it.price || 0,
                    total: (it.quantity || 1) * (it.price || 0)
                }))
            };

            sales.unshift(newSale);
            localStorage.setItem('pos_guest_sales_history', JSON.stringify(sales));

            // Update stats
            stats.today_sales = (stats.today_sales || 0) + grandTotal;
            stats.today_orders = (stats.today_orders || 0) + 1;
            localStorage.setItem('pos_guest_stats', JSON.stringify(stats));

            // Update customer record
            const matchedCust = customers.find(c => c.name.toLowerCase() === custName.toLowerCase() || (custPhone && c.phone === custPhone));
            if (matchedCust) {
                matchedCust.total_orders = (matchedCust.total_orders || 0) + 1;
                matchedCust.total_spent = (matchedCust.total_spent || 0) + grandTotal;
                if (isCreditSale) {
                    matchedCust.total_due = (matchedCust.total_due || 0) + balanceDue;
                }
                localStorage.setItem('pos_guest_customers', JSON.stringify(customers));
            } else if (custName !== 'Walk-in Customer') {
                customers.unshift({
                    id: Date.now(),
                    name: custName,
                    phone: custPhone || 'No Phone',
                    address: body.customer_address || 'Walk-in Store',
                    total_orders: 1,
                    total_spent: grandTotal,
                    total_due: balanceDue,
                    created_at: new Date().toISOString().split('T')[0]
                });
                localStorage.setItem('pos_guest_customers', JSON.stringify(customers));
            }

            return {
                success: true,
                message: 'Sale completed & invoice generated successfully!',
                invoice_id: invoiceId,
                invoice_number: invoiceNo,
                subtotal: subtotal,
                discount: discountAmount,
                tax: taxAmount,
                grand_total: grandTotal,
                formatted_total: `${currSym} ${grandTotal.toLocaleString()}`,
                items_count: items.length,
                customer_name: custName,
                customer_phone: custPhone,
                payment_method: paymentMethod,
                created_at: new Date().toLocaleDateString('en-GB')
            };
        }

        // 13. PAIRING PIN VERIFICATION (/scanner/verify-pin)
        if (pathPart === '/scanner/verify-pin') {
            return {
                success: true,
                token: 'GUEST_DEMO_TOKEN',
                store_name: 'Demo Store (Guest Sandbox)',
                terminal_id: 'GuestTerminal-01'
            };
        }
    }

    // ─────────────────────────────────────────────────────────
    // LIVE SERVER API REQUEST
    // ─────────────────────────────────────────────────────────
    const baseUrl = (AppState.serverUrl || getBaseServerUrl()).replace(/\/+$/, '');
    const url = `${baseUrl}${rawEndpoint}`;

    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {})
    };

    if (AppState.pairToken) {
        headers['Authorization'] = `Bearer ${AppState.pairToken}`;
    }

    try {
        const res = await fetch(url, {
            ...options,
            headers
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok && !data.message) {
            data.message = `HTTP Error ${res.status}`;
        }
        return data;
    } catch (err) {
        if (isGuestSession()) {
            return { success: true };
        }
        throw new Error(err.message || 'Network connection failed');
    }
}

/**
 * Global Guest Mode Thermal Receipt Generator
 * Renders 100% authentic POS thermal receipt with live catalog & checkout data
 */
function generateGuestThermalReceiptHtml(invoiceId, invoiceNo, overrideData) {
    let sales = [];
    try { sales = JSON.parse(localStorage.getItem('pos_guest_sales_history') || '[]'); } catch(e) {}
    let inv = sales.find(s => String(s.id) === String(invoiceId) || (invoiceNo && String(s.invoice_no) === String(invoiceNo)));
    if (!inv && sales.length > 0) inv = sales[0];

    const storeName = AppState.storeName || localStorage.getItem('pos_scanner_store') || 'DevAltum Retail Superstore';
    const storeAddress = 'Floor 2, Central Commercial Avenue, Model Town';
    const storeNtn = 'NTN: 8294102-7';
    const curr = AppState.currency || 'Rs';

    const invNum = invoiceNo || inv?.invoice_no || ('INV-' + new Date().toISOString().slice(2, 10).replace(/-/g, '') + '-001');
    const dateStr = inv?.date || new Date().toISOString().split('T')[0];
    const timeStr = inv?.time || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const custName = inv?.customer_name || overrideData?.customer_name || 'Walk-in Customer';
    const custPhone = inv?.customer_phone || overrideData?.customer_phone || '';
    const paymentMethod = inv?.payment_method || overrideData?.payment_method || 'Cash';
    const isCredit = (paymentMethod || '').toLowerCase() === 'credit';
    
    const items = (inv?.items && inv.items.length > 0) ? inv.items : (overrideData?.items || [
        { product_name: 'DevAltum Wireless Mouse Pro', quantity: 1, price: 1850, total: 1850 }
    ]);

    let subtotal = Number(inv?.subtotal || 0);
    if (!subtotal) {
        items.forEach(it => { subtotal += Number(it.total || ((it.quantity || 1) * (it.price || 0)) || 0); });
    }
    const discount = Number(inv?.discount || overrideData?.discount || 0);
    const tax = Number(inv?.tax || overrideData?.tax || 0);
    const grandTotal = Number(inv?.total || overrideData?.grand_total || (subtotal - discount + tax));
    const paid = Number(inv?.paid_amount ?? (isCredit ? (overrideData?.paid_amount || 0) : grandTotal));
    const balanceDue = isCredit ? Math.max(0, grandTotal - paid) : 0;
    const dueDate = inv?.credit_due_date || overrideData?.credit_due_date || '';

    return `
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Receipt - ${invNum}</title>
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body {
                    font-family: 'Courier New', Courier, monospace;
                    background: #fff;
                    color: #000;
                    padding: 12px 10px;
                    font-size: 12px;
                    line-height: 1.35;
                }
                .sheet {
                    width: 100%;
                    max-width: 320px;
                    margin: 0 auto;
                }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .text-left { text-align: left; }
                .bold { font-weight: 800; }
                .divider {
                    border-top: 1px dashed #000;
                    margin: 7px 0;
                }
                .store-title {
                    font-size: 15.5px;
                    font-weight: 900;
                    letter-spacing: 0.5px;
                    text-transform: uppercase;
                }
                .store-sub {
                    font-size: 10.5px;
                    margin-top: 2px;
                }
                .meta-row {
                    display: flex;
                    justify-content: space-between;
                    font-size: 11.5px;
                    margin-bottom: 2.5px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 4px 0;
                }
                th {
                    font-size: 11px;
                    font-weight: 800;
                    padding: 3px 0;
                    border-bottom: 1px dashed #000;
                }
                td {
                    font-size: 11.5px;
                    padding: 4px 0;
                    vertical-align: top;
                }
                .grand-total-row {
                    display: flex;
                    justify-content: space-between;
                    font-size: 15px;
                    font-weight: 900;
                    padding: 4px 0;
                }
                .footer {
                    margin-top: 10px;
                    text-align: center;
                    font-size: 10.5px;
                }
                @media print {
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            <div class="sheet">
                <!-- Header -->
                <div class="text-center">
                    <div class="store-title">${storeName}</div>
                    <div class="store-sub">${storeAddress}</div>
                    <div class="store-sub">${storeNtn}</div>
                    <div class="store-sub bold" style="margin-top:2px;">*** SALES INVOICE ***</div>
                </div>

                <div class="divider"></div>

                <!-- Invoice Meta -->
                <div class="meta-row">
                    <span>Invoice #: <strong>${invNum}</strong></span>
                    <span>${dateStr}</span>
                </div>
                <div class="meta-row">
                    <span>Cashier: Cashier 01</span>
                    <span>${timeStr}</span>
                </div>
                <div class="meta-row">
                    <span>Customer: <strong>${custName}</strong></span>
                    <span>${custPhone || ''}</span>
                </div>
                <div class="meta-row">
                    <span>Payment: <strong>${paymentMethod.toUpperCase()}</strong></span>
                    ${isCredit && dueDate ? `<span>Due: <strong>${dueDate}</strong></span>` : ''}
                </div>

                <div class="divider"></div>

                <!-- Items Table -->
                <table>
                    <thead>
                        <tr>
                            <th class="text-left" style="width:50%;">ITEM</th>
                            <th class="text-center" style="width:15%;">QTY</th>
                            <th class="text-right" style="width:15%;">RATE</th>
                            <th class="text-right" style="width:20%;">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map(it => `
                            <tr>
                                <td class="text-left bold">${it.product_name || it.name || 'Item'}</td>
                                <td class="text-center">${it.quantity || 1}</td>
                                <td class="text-right">${Number(it.price || 0).toLocaleString()}</td>
                                <td class="text-right bold">${Number(it.total || ((it.quantity || 1) * (it.price || 0))).toLocaleString()}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <div class="divider"></div>

                <!-- Totals -->
                <div class="meta-row">
                    <span>Subtotal:</span>
                    <span class="bold">${curr} ${subtotal.toLocaleString()}</span>
                </div>
                ${discount > 0 ? `
                    <div class="meta-row">
                        <span>Discount:</span>
                        <span>- ${curr} ${discount.toLocaleString()}</span>
                    </div>
                ` : ''}
                ${tax > 0 ? `
                    <div class="meta-row">
                        <span>Sales Tax:</span>
                        <span>+ ${curr} ${tax.toLocaleString()}</span>
                    </div>
                ` : ''}

                <div class="divider"></div>

                <div class="grand-total-row">
                    <span>GRAND TOTAL:</span>
                    <span>${curr} ${grandTotal.toLocaleString()}</span>
                </div>

                ${isCredit ? `
                    <div class="divider"></div>
                    <div class="meta-row">
                        <span>Paid Upfront:</span>
                        <span class="bold">${curr} ${paid.toLocaleString()}</span>
                    </div>
                    <div class="meta-row bold" style="font-size:13px; color:#c2410c;">
                        <span>BALANCE DUE:</span>
                        <span>${curr} ${balanceDue.toLocaleString()}</span>
                    </div>
                    ${dueDate ? `
                        <div class="meta-row" style="font-size:11px; margin-top:2px;">
                            <span>Due Date:</span>
                            <span>${dueDate}</span>
                        </div>
                    ` : ''}
                ` : `
                    <div class="meta-row">
                        <span>Cash Paid:</span>
                        <span>${curr} ${grandTotal.toLocaleString()}</span>
                    </div>
                    <div class="meta-row">
                        <span>Change:</span>
                        <span>${curr} 0.00</span>
                    </div>
                `}

                <div class="divider"></div>

                <!-- Footer -->
                <div class="footer">
                    <div class="bold">THANK YOU FOR YOUR VISIT!</div>
                    <div style="font-size:9.5px; margin-top:3px; color:#555;">DevAltum Cloud POS System</div>
                </div>
            </div>
        </body>
        </html>
    `;
}

if (typeof window !== 'undefined') {
    window.generateGuestThermalReceiptHtml = generateGuestThermalReceiptHtml;
}
