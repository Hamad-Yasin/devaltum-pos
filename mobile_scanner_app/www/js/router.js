/**
 * DevAltum POS Mobile Companion - Multi-Page URL Router & Navigation Engine
 * Directly navigates between standalone HTML files (pages/settings.html, pages/dashboard.html, etc.)
 */

function getPageUrl(target) {
    const isInsidePages = window.location.pathname.includes('/pages/') || window.location.href.includes('/pages/');
    const pageFileMap = {
        'welcome': 'welcome.html',
        'connect': 'connect.html',
        'connecting': 'connecting.html',
        'connected-success': 'connected-success.html',
        'dashboard': 'dashboard.html',
        'sales': 'sales-history.html',
        'history': 'sales-history.html',
        'sales-history': 'sales-history.html',
        'receipt': 'sale-receipt.html',
        'sale-receipt': 'sale-receipt.html',
        'scanner': 'scanner.html',
        'products': 'products.html',
        'add-product': 'add-product.html',
        'customers': 'customers.html',
        'customer-profile': 'customer-profile.html',
        'settings': 'settings.html',
        'profile': 'profile.html'
    };

    const targetFile = pageFileMap[target] || (target.endsWith('.html') ? target : `${target}.html`);
    if (isInsidePages) {
        return targetFile;
    } else {
        return `pages/${targetFile}`;
    }
}

function navigateTo(target, paramQuery = '') {
    let url = getPageUrl(target);
    if (paramQuery) {
        url += paramQuery.startsWith('?') ? paramQuery : `?${paramQuery}`;
    }
    window.location.href = url;
}

function navigateBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        navigateTo('dashboard');
    }
}
