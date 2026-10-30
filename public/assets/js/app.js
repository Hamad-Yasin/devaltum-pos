document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const headerBarcodeScan = document.getElementById('header-barcode-scan');
    const mobileBarcodeScan = document.getElementById('mobile-barcode-scan');
    const returnSwitcher = document.querySelector('[data-return-switcher]');
    const returnSwitcherToggle = document.querySelector('[data-return-switcher-toggle]');
    const contentArea = document.querySelector('.content-area');
    const modalRoot = document.getElementById('app-modal-root');
    const currencySymbol = document.body.dataset.currencySymbol || 'Rs';
    const tabSessionId = document.body.dataset.tabSession || '';
    const offlineQueueKey = 'devaltum_offline_queue';
    let csrfToken = document.body?.dataset?.csrfToken || '';
    window.appCsrfToken = csrfToken;
    const getCsrfToken = () => window.appCsrfToken || document.body?.dataset?.csrfToken || csrfToken || '';
    let searchTimeout = null;

    const formatMoney = (value) => {
        const num = Number(value || 0);
        const fixed = Math.round(num).toFixed(2);
        const normalized = fixed.endsWith('.00') ? fixed.slice(0, -3) : fixed;
        return `${currencySymbol} ${normalized}`;
    };
    window.appFormatMoney = formatMoney;
    const pageSize = 10;

    const initTablePagination = (root = document) => {
        root.querySelectorAll('table').forEach((table) => {
            if (table.hasAttribute('data-no-pagination')) return;
            const body = table.querySelector('tbody');
            if (!body) return;

            const rows = Array.from(body.querySelectorAll('tr')).filter((row) => !row.querySelector('.empty-state'));
            const existingPager = table.parentElement?.querySelector('.table-pagination');
            if (existingPager) {
                existingPager.remove();
            }

            if (rows.length <= pageSize) {
                rows.forEach((row) => {
                    row.style.display = '';
                });
                return;
            }

            let currentPage = 1;
            const totalPages = Math.ceil(rows.length / pageSize);
            const pager = document.createElement('div');
            pager.className = 'table-pagination';
            pager.innerHTML = `
                <button type="button" class="btn btn-secondary btn-sm" data-page-action="prev">Previous</button>
                <span class="table-pagination-info" data-page-info></span>
                <button type="button" class="btn btn-secondary btn-sm" data-page-action="next">Next</button>
            `;
            table.parentElement?.appendChild(pager);

            const info = pager.querySelector('[data-page-info]');
            const prev = pager.querySelector('[data-page-action="prev"]');
            const next = pager.querySelector('[data-page-action="next"]');

            const render = () => {
                const start = (currentPage - 1) * pageSize;
                const end = start + pageSize;

                rows.forEach((row, idx) => {
                    row.style.display = idx >= start && idx < end ? '' : 'none';
                });

                if (info) {
                    info.textContent = `Page ${currentPage} of ${totalPages} (${rows.length} items)`;
                }
                if (prev) {
                    prev.disabled = currentPage <= 1;
                }
                if (next) {
                    next.disabled = currentPage >= totalPages;
                }
            };

            prev?.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage -= 1;
                    render();
                }
            });

            next?.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage += 1;
                    render();
                }
            });

            render();
        });
    };
    window.appInitTablePagination = initTablePagination;

    const initStoreDirectory = (root = document) => {
        root.querySelectorAll('[data-store-directory]').forEach((directory) => {
            if (directory.dataset.directoryReady === '1') {
                return;
            }
            directory.dataset.directoryReady = '1';

            const search = directory.querySelector('[data-store-directory-search]');
            const typeFilter = directory.querySelector('[data-store-directory-type]');
            const statusFilter = directory.querySelector('[data-store-directory-status]');
            const rows = Array.from(directory.querySelectorAll('[data-store-row]'));
            const countNode = directory.querySelector('[data-store-directory-count]');
            const emptyNode = directory.querySelector('[data-store-directory-empty]');
            const pageNode = directory.querySelector('[data-store-directory-page]');
            const prevButton = directory.querySelector('[data-store-directory-prev]');
            const nextButton = directory.querySelector('[data-store-directory-next]');
            const filterStatus = directory.querySelector('[data-store-directory-filter-status]');
            const pageSize = Math.max(1, Number(directory.dataset.pageSize || 10));
            let currentPage = 1;
            let filteredRows = rows;

            const render = () => {
                const query = (search?.value || '').trim().toLowerCase();
                const type = typeFilter?.value || 'all';
                const status = statusFilter?.value || 'all';
                const hasActiveFilter = Boolean(query) || type !== 'all' || status !== 'all';

                filteredRows = rows.filter((row) => {
                    const matchesQuery = !query || (row.dataset.search || '').includes(query);
                    const matchesType = type === 'all' || row.dataset.type === type;
                    const matchesStatus = status === 'all' || row.dataset.status === status;
                    return matchesQuery && matchesType && matchesStatus;
                });

                const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
                currentPage = Math.min(currentPage, totalPages);
                const start = (currentPage - 1) * pageSize;
                const end = start + pageSize;
                const visibleSet = new Set(filteredRows.slice(start, end));

                rows.forEach((row) => {
                    row.style.display = visibleSet.has(row) ? '' : 'none';
                });

                if (countNode) {
                    countNode.textContent = `${filteredRows.length} record${filteredRows.length === 1 ? '' : 's'} found`;
                }
                search?.classList.toggle('is-filter-active', Boolean(query));
                typeFilter?.classList.toggle('is-filter-active', type !== 'all');
                statusFilter?.classList.toggle('is-filter-active', status !== 'all');
                if (filterStatus) {
                    filterStatus.innerHTML = hasActiveFilter
                        ? '<span class="material-symbols-rounded">filter_alt</span>Filters applied live'
                        : '<span class="material-symbols-rounded">bolt</span>Live filters ready';
                }
                if (emptyNode) {
                    emptyNode.style.display = filteredRows.length ? 'none' : 'block';
                }
                if (pageNode) {
                    pageNode.textContent = `Page ${currentPage} of ${totalPages}`;
                }
                if (prevButton) {
                    prevButton.disabled = currentPage <= 1;
                }
                if (nextButton) {
                    nextButton.disabled = currentPage >= totalPages;
                }
            };

            const resetAndRender = () => {
                currentPage = 1;
                render();
            };

            search?.addEventListener('input', resetAndRender);
            typeFilter?.addEventListener('change', resetAndRender);
            statusFilter?.addEventListener('change', resetAndRender);
            prevButton?.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage -= 1;
                    render();
                }
            });
            nextButton?.addEventListener('click', () => {
                const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
                if (currentPage < totalPages) {
                    currentPage += 1;
                    render();
                }
            });

            render();
        });
    };
    window.appInitStoreDirectory = initStoreDirectory;

    const syncCustomDateFields = (root = document) => {
        root.querySelectorAll('[data-custom-date-select]').forEach((select) => {
            const target = select.dataset.customDateTarget || '';
            const form = select.closest('form');
            const fields = Array.from(form?.querySelectorAll(`[data-custom-date-field="${target}"]`) || []);
            const isCustom = select.value === 'custom';

            fields.forEach((field) => {
                field.hidden = !isCustom;
                field.disabled = !isCustom;
                if (!isCustom) {
                    field.value = '';
                }
            });
        });
    };
    window.appSyncCustomDateFields = syncCustomDateFields;
    const scopeTabSessionUrl = (rawUrl) => {
        if (!tabSessionId || !rawUrl || rawUrl.startsWith('#') || rawUrl.startsWith('javascript:') || rawUrl.startsWith('mailto:') || rawUrl.startsWith('tel:')) {
            return rawUrl;
        }
        let url;
        try {
            url = new URL(rawUrl, window.location.href);
        } catch (error) {
            return rawUrl;
        }
        if (url.origin !== window.location.origin) {
            return rawUrl;
        }
        url.searchParams.set('tab_session', tabSessionId);
        return url.pathname + url.search + url.hash;
    };
    window.appScopeTabSessionUrl = scopeTabSessionUrl;
    const applyTabSessionScope = (root = document) => {
        if (!tabSessionId) return;
        root.querySelectorAll('a[href]').forEach((link) => {
            link.setAttribute('href', scopeTabSessionUrl(link.getAttribute('href')));
        });
        root.querySelectorAll('form').forEach((form) => {
            if (form.action) {
                form.action = scopeTabSessionUrl(form.action);
            }
            if (!form.querySelector('input[name="tab_session"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tab_session';
                input.value = tabSessionId;
                form.appendChild(input);
            }
        });
    };
    applyTabSessionScope();
    window.appApplyTabSessionScope = applyTabSessionScope;
    if (returnSwitcher && returnSwitcherToggle) {
        returnSwitcherToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const isOpen = returnSwitcher.classList.toggle('is-open');
            returnSwitcherToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', (event) => {
            if (!returnSwitcher.classList.contains('is-open')) return;
            if (returnSwitcher.contains(event.target)) return;
            returnSwitcher.classList.remove('is-open');
            returnSwitcherToggle.setAttribute('aria-expanded', 'false');
        });
    }
    const getOfflineQueue = () => {
        try {
            return JSON.parse(localStorage.getItem(offlineQueueKey) || '[]');
        } catch (error) {
            return [];
        }
    };
    const setOfflineQueue = (items) => localStorage.setItem(offlineQueueKey, JSON.stringify(items));

    window.appOfflineQueue = {
        add(item) {
            const queue = getOfflineQueue();
            queue.push({ id: Date.now(), ...item });
            setOfflineQueue(queue);
        },
        all() {
            return getOfflineQueue();
        },
        async sync() {
            const queue = getOfflineQueue();
            if (!queue.length || !navigator.onLine) return [];
            const remaining = [];
            const synced = [];

            for (const item of queue) {
                try {
                    const response = await fetch(scopeTabSessionUrl(item.url), {
                        method: item.method || 'POST',
                        headers: item.headers || {},
                        body: item.body
                    });
                    const data = await response.json();
                    if (data.success) {
                        synced.push(data);
                    } else {
                        remaining.push(item);
                    }
                } catch (error) {
                    remaining.push(item);
                }
            }

            setOfflineQueue(remaining);
            return synced;
        }
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const setBellCount = (count) => {
        const bell = document.getElementById('notifications-bell-btn');
        const mobileBell = document.getElementById('mobile-floating-bell');
        const mobileBellBadge = document.getElementById('mobile-floating-bell-badge');
        if (!bell) return;

        const safeCount = Math.max(0, Number(count || 0));
        let badge = document.getElementById('notifications-bell-badge');
        if (safeCount <= 0) {
            if (badge) badge.remove();
            bell.classList.remove('bell-shake');
            if (mobileBell) {
                mobileBell.classList.remove('show');
            }
            if (mobileBellBadge) {
                mobileBellBadge.textContent = '0';
            }
            return;
        }

        if (!badge) {
            badge = document.createElement('span');
            badge.id = 'notifications-bell-badge';
            badge.className = 'icon-badge';
            bell.appendChild(badge);
        }
        badge.textContent = String(safeCount);
        bell.classList.add('bell-shake');
        if (mobileBell) {
            mobileBell.classList.add('show');
        }
        if (mobileBellBadge) {
            mobileBellBadge.textContent = String(safeCount);
        }
    };

    const setBellList = (notifications) => {
        const listRoot = document.getElementById('notifications-dropdown-list');
        if (!listRoot) return;

        const items = Array.isArray(notifications) ? notifications : [];
        if (!items.length) {
            listRoot.innerHTML = '<div class="dropdown-empty">No new notifications.</div>';
            return;
        }

        listRoot.innerHTML = items.map((notify) => `
            <a class="dropdown-link" data-notification-open href="${escapeHtml(notify.link || '/?url=notifications/index&tab=new')}">
                <div class="dropdown-link-title">${escapeHtml(notify.type || 'Notification')}: ${escapeHtml(notify.title || '')}</div>
                <div class="dropdown-link-subtitle">${escapeHtml(notify.message || '')} | ${escapeHtml(notify.created_at || '')}</div>
            </a>
        `).join('');
    };

    const removeClickedNotificationFromBell = (link) => {
        const row = link.closest('[data-notification-open]');
        if (!row) return;
        row.remove();

        const listRoot = document.getElementById('notifications-dropdown-list');
        if (listRoot && !listRoot.querySelector('[data-notification-open]')) {
            listRoot.innerHTML = '<div class="dropdown-empty">No new notifications.</div>';
        }

        const badge = document.getElementById('notifications-bell-badge');
        if (badge) {
            const nextCount = Math.max(0, Number(badge.textContent || '0') - 1);
            setBellCount(nextCount);
        }
    };

    const syncNotificationBell = async () => {
        try {
            const response = await fetch(scopeTabSessionUrl('/?url=notifications/feed'), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            setBellCount(data.count || 0);
            setBellList(data.notifications || []);
        } catch (error) {
            // Keep UI stable if polling fails temporarily.
        }
    };

    const executeScriptsInNode = (node) => {
        if (!node) return;
        const scripts = node.querySelectorAll('script');
        scripts.forEach((oldScript) => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.textContent = oldScript.textContent;
            if (oldScript.parentNode) {
                oldScript.parentNode.replaceChild(newScript, oldScript);
            }
        });
    };

    const runExtraJs = (extraJs, mountNode) => {
        if (!extraJs) return;
        const code = String(extraJs).replace(/<script\b[^>]*>/gi, '').replace(/<\/script>/gi, '').trim();
        if (!code) return;
        try {
            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.text = code;
            (mountNode || document.body).appendChild(script);
        } catch (err) {
            console.error('Error executing extraJs:', err);
        }
    };

    const closeDropdowns = () => {
        document.querySelectorAll('.dropdown-menu.open').forEach((menu) => menu.classList.remove('open'));
    };

    const buildUrlFromForm = (form, submitter = null) => {
        const formData = new FormData(form);
        if (submitter && submitter.name) {
            formData.append(submitter.name, submitter.value);
        }
        const params = new URLSearchParams(formData);
        const action = form.getAttribute('action') || window.location.pathname;
        const query = params.toString();
        const url = query ? `${action}${action.includes('?') ? '&' : '?'}${query}` : action;
        return typeof scopeTabSessionUrl === 'function' ? scopeTabSessionUrl(url) : url;
    };

    const setStaticModalState = (targetId, isOpen) => {
        const modal = document.getElementById(targetId);
        if (modal) {
            modal.style.display = isOpen ? 'flex' : 'none';
        }
    };

    const iconMap = [
        { match: /name/i, icon: 'badge' },
        { match: /email/i, icon: 'mail' },
        { match: /phone|mobile|whatsapp|contact/i, icon: 'call' },
        { match: /password/i, icon: 'lock' },
        { match: /price|cost|amount|discount|total|currency/i, icon: 'payments' },
        { match: /stock|qty|quantity/i, icon: 'inventory_2' },
        { match: /model/i, icon: 'sell' },
        { match: /note|footer|description|message|tagline/i, icon: 'note' },
        { match: /logo|image/i, icon: 'image' },
        { match: /date/i, icon: 'calendar_today' },
        { match: /status/i, icon: 'tune' },
        { match: /type/i, icon: 'category' }
    ];

    const inferFieldIcon = (field) => {
        const label = field.closest('.form-group')?.querySelector('.form-label')?.textContent?.trim() || '';
        const name = field.getAttribute('name') || '';
        const placeholder = field.getAttribute('placeholder') || '';
        const type = field.getAttribute('type') || '';
        const haystack = `${label} ${name} ${placeholder} ${type}`;

        if (type === 'email') return 'mail';
        if (type === 'password') return 'lock';
        if (type === 'number') return 'payments';
        if (type === 'date') return 'calendar_today';

        const matched = iconMap.find((item) => item.match.test(haystack));
        return matched ? matched.icon : 'edit_square';
    };

    const enhanceFormIcons = (root = document) => {
        root.querySelectorAll('.form-group .form-input, .form-group .form-select, .form-group .form-textarea').forEach((field) => {
            if (field.closest('.field-icon-wrap, .input-icon-wrap')) return;
            if (field.type === 'hidden' || field.type === 'file' || field.type === 'checkbox' || field.type === 'radio' || field.matches('[data-unit-picker]')) return;

            const wrapper = document.createElement('div');
            wrapper.className = 'field-icon-wrap';
            if (field.type === 'password') {
                wrapper.classList.add('password-field-wrap');
            }
            field.parentNode.insertBefore(wrapper, field);
            wrapper.appendChild(field);

            const icon = document.createElement('span');
            icon.className = 'material-symbols-rounded';
            icon.textContent = inferFieldIcon(field);
            wrapper.appendChild(icon);

            if (field.type === 'password') {
                const toggle = document.createElement('button');
                toggle.className = 'password-visibility-toggle';
                toggle.type = 'button';
                toggle.setAttribute('aria-label', 'Show password');
                toggle.setAttribute('title', 'Show password');
                toggle.innerHTML = '<span class="material-symbols-rounded">visibility</span>';
                wrapper.appendChild(toggle);
            }
        });
    };

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('.password-visibility-toggle');
        if (!toggle) return;

        const wrapper = toggle.closest('.password-field-wrap');
        const field = wrapper?.querySelector('input');
        if (!field) return;

        const shouldShow = field.type === 'password';
        field.type = shouldShow ? 'text' : 'password';
        toggle.querySelector('.material-symbols-rounded').textContent = shouldShow ? 'visibility_off' : 'visibility';
        toggle.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
        toggle.setAttribute('title', shouldShow ? 'Hide password' : 'Show password');
    });

    const closeUnitPickers = (except = null) => {
        document.querySelectorAll('.unit-picker.open').forEach((picker) => {
            if (picker !== except) {
                picker.classList.remove('open');
                picker.querySelector('.unit-picker-toggle')?.setAttribute('aria-expanded', 'false');
            }
        });
    };

    const initUnitPickers = (root = document) => {
        root.querySelectorAll('select[data-unit-picker]').forEach((select) => {
            if (select.dataset.unitPickerReady === '1') return;
            select.dataset.unitPickerReady = '1';
            select.classList.add('unit-picker-native');

            const picker = document.createElement('div');
            picker.className = 'unit-picker';
            const options = Array.from(select.options).map((option) => ({
                value: option.value,
                label: option.textContent.trim(),
                code: String(option.value || '').toUpperCase()
            }));

            picker.innerHTML = `
                <button class="unit-picker-toggle" type="button" aria-expanded="false">
                    <span data-unit-picker-label></span>
                    <span style="display:flex; align-items:center; gap:6px;"><small data-unit-picker-code></small><span class="material-symbols-rounded">expand_more</span></span>
                </button>
                <div class="unit-picker-panel">
                    <input class="unit-picker-search" type="search" placeholder="Search unit..." autocomplete="off">
                    <div class="unit-picker-list"></div>
                </div>
            `;
            select.insertAdjacentElement('afterend', picker);

            const toggle = picker.querySelector('.unit-picker-toggle');
            const labelNode = picker.querySelector('[data-unit-picker-label]');
            const codeNode = picker.querySelector('[data-unit-picker-code]');
            const search = picker.querySelector('.unit-picker-search');
            const list = picker.querySelector('.unit-picker-list');

            const currentOption = () => options.find((option) => option.value === select.value) || options[0] || { label: 'Select Unit', code: '' };
            const syncLabel = () => {
                const selected = currentOption();
                labelNode.textContent = selected.label;
                codeNode.textContent = selected.code;
            };

            const render = (query = '') => {
                const normalized = query.trim().toLowerCase();
                const matched = options.filter((option) => !normalized || option.label.toLowerCase().includes(normalized) || option.code.toLowerCase().includes(normalized));
                if (!matched.length) {
                    list.innerHTML = '<div class="unit-picker-empty">No unit found</div>';
                    return;
                }
                list.innerHTML = matched.map((option) => `
                    <button class="unit-picker-option ${option.value === select.value ? 'active' : ''}" type="button" data-unit-value="${escapeHtml(option.value)}">
                        <span>${escapeHtml(option.label)}</span>
                        <small>${escapeHtml(option.code)}</small>
                    </button>
                `).join('');
            };

            toggle.addEventListener('click', () => {
                const willOpen = !picker.classList.contains('open');
                closeUnitPickers(picker);
                picker.classList.toggle('open', willOpen);
                toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                if (willOpen) {
                    render('');
                    search.value = '';
                    setTimeout(() => search.focus(), 30);
                }
            });

            search.addEventListener('input', () => render(search.value));
            list.addEventListener('click', (event) => {
                const optionButton = event.target.closest('[data-unit-value]');
                if (!optionButton) return;
                select.value = optionButton.dataset.unitValue || select.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                syncLabel();
                render(search.value);
                closeUnitPickers();
            });

            syncLabel();
            render('');
        });
    };

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.unit-picker')) {
            closeUnitPickers();
        }

        const clearFiltersButton = event.target.closest('[data-clear-filters]');
        if (clearFiltersButton) {
            const form = clearFiltersButton.closest('form');
            if (!form) return;

            form.querySelectorAll('input, select').forEach((field) => {
                if (field.name === 'url' || field.type === 'hidden') {
                    return;
                }

                if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                    return;
                }

                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = false;
                    return;
                }

                field.value = '';
            });

            loadPage(buildUrlFromForm(form, clearFiltersButton));
            return;
        }

        const openModalButton = event.target.closest('[data-open-modal]');
        if (openModalButton) {
            setStaticModalState(openModalButton.dataset.openModal, true);
            return;
        }

        const closeModalButton = event.target.closest('[data-close-modal-target]');
        if (closeModalButton) {
            setStaticModalState(closeModalButton.dataset.closeModalTarget, false);
            return;
        }

        const staticBackdrop = event.target.closest('.modal-backdrop');
        if (staticBackdrop && event.target === staticBackdrop && staticBackdrop.style.display !== 'none') {
            staticBackdrop.style.display = 'none';
            return;
        }

        const toggle = event.target.closest('.dropdown-toggle');
        if (toggle) {
            const key = toggle.dataset.dropdown;
            const menu = toggle.closest('.dropdown')?.querySelector('.dropdown-menu') || document.querySelector(`[data-dropdown-menu="${key}"]`);
            const isOpen = menu?.classList.contains('open');
            closeDropdowns();
            if (menu && !isOpen) {
                menu.classList.add('open');
            }
            return;
        }

        if (!event.target.closest('.dropdown')) {
            closeDropdowns();
        }

        if (sidebar?.classList.contains('open')) {
            const clickedInsideSidebar = !!event.target.closest('.sidebar');
            const clickedMenuToggle = !!event.target.closest('#mobile-menu-toggle');
            if (!clickedInsideSidebar && !clickedMenuToggle) {
                sidebar.classList.remove('open');
                setMobileMenuIcon(false);
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeUnitPickers();
        }
    });

    const appShell = document.querySelector('.app-shell');

    if (localStorage.getItem('pos_sidebar_collapsed') === '1' && window.innerWidth > 1024) {
        appShell?.classList.add('sidebar-collapsed');
    }

    const updateSidebarToggleIcons = () => {
        const isDesktop = window.innerWidth > 1024;
        const isCollapsed = appShell?.classList.contains('sidebar-collapsed');
        const isMobileOpen = sidebar?.classList.contains('open');

        // When sidebar is open/expanded: show 'close' (✕). When collapsed/closed: show 'menu' (☰).
        const isOpen = isDesktop ? !isCollapsed : isMobileOpen;

        const mIcon = mobileMenuToggle?.querySelector('.material-symbols-rounded');
        if (mIcon) {
            mIcon.textContent = isOpen ? 'close' : 'menu';
            mIcon.style.transform = isOpen ? 'rotate(90deg)' : 'rotate(0deg)';
        }
    };

    updateSidebarToggleIcons();

    const toggleSidebarAction = (event) => {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        if (window.innerWidth > 1024) {
            const isCollapsed = appShell?.classList.toggle('sidebar-collapsed');
            localStorage.setItem('pos_sidebar_collapsed', isCollapsed ? '1' : '0');
        } else {
            sidebar?.classList.toggle('open');
        }
        updateSidebarToggleIcons();
    };

    mobileMenuToggle?.addEventListener('click', toggleSidebarAction);

    // Expand sidebar on click when collapsed in desktop
    sidebar?.addEventListener('click', (event) => {
        if (window.innerWidth > 1024 && appShell?.classList.contains('sidebar-collapsed')) {
            if (!event.target.closest('.nav-item')) {
                appShell.classList.remove('sidebar-collapsed');
                localStorage.setItem('pos_sidebar_collapsed', '0');
                updateSidebarToggleIcons();
            }
        }
    });

    window.addEventListener('resize', updateSidebarToggleIcons);

    const normalizeBarcodeInput = (value) => String(value || '')
        .trim()
        .toUpperCase()
        .replace(/[^A-Z0-9\-.\s$/+%]/g, '')
        .slice(0, 48);

    const playScannerBeep = (type = 'success') => {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = type === 'success' ? 880 : 220;
            gain.gain.setValueAtTime(0.0001, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(type === 'success' ? 0.18 : 0.12, ctx.currentTime + 0.012);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.16);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.18);
            setTimeout(() => ctx.close?.(), 260);
        } catch (error) {}
    };
    window.playScannerBeep = playScannerBeep;

    const stopBarcodeCamera = () => {
        if (window.__barcodeScannerStream) {
            window.__barcodeScannerStream.getTracks().forEach((track) => track.stop());
            window.__barcodeScannerStream = null;
        }
        window.__barcodeScannerActive = false;
        const scanner = document.getElementById('fullscreen-barcode-scanner');
        if (scanner) scanner.remove();
    };

    const openBarcodeScanner = () => {
        stopBarcodeCamera();
        const scanner = document.createElement('div');
        scanner.id = 'fullscreen-barcode-scanner';
        scanner.innerHTML = `
            <style>
                #fullscreen-barcode-scanner {
                    position: fixed; inset: 0; z-index: 9999999; background: #020617;
                    overflow: hidden; color: #fff;
                }
                .fs-scanner-video {
                    position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;
                    background: #020617;
                }
                .fs-scanner-overlay {
                    position: absolute; inset: 0;
                    background:
                        radial-gradient(circle at 50% 50%, transparent 0 28%, rgba(2,6,23,.34) 38%, rgba(2,6,23,.78) 100%);
                    pointer-events: none;
                }
                .fs-scanner-top {
                    position: absolute; left: 22px; right: 22px; top: 20px;
                    display: flex; align-items: center; justify-content: space-between; gap: 14px;
                }
                .fs-scanner-brand {
                    display: flex; align-items: center; gap: 12px;
                    padding: 10px 14px; border-radius: 18px;
                    background: rgba(15,23,42,.62); border: 1px solid rgba(255,255,255,.14);
                    backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
                    box-shadow: 0 18px 40px rgba(0,0,0,.25);
                }
                .fs-scanner-brand .material-symbols-rounded {
                    width: 38px; height: 38px; display: grid; place-items: center;
                    border-radius: 13px; background: rgba(255,255,255,.12);
                }
                .fs-scanner-brand strong { display: block; font-size: 15px; line-height: 1.1; }
                .fs-scanner-brand span:last-child { display: block; color: #cbd5e1; font-size: 12px; font-weight: 700; margin-top: 2px; }
                .fs-scanner-close {
                    width: 48px; height: 48px; border-radius: 16px; border: 1px solid rgba(255,255,255,.16);
                    background: rgba(15,23,42,.62); color: #fff; display: grid; place-items: center;
                    cursor: pointer; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
                }
                .fs-scanner-frame {
                    position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
                    width: min(74vw, 560px); aspect-ratio: 1.7 / 1;
                    border-radius: 28px;
                    box-shadow:
                        0 0 0 9999px rgba(2,6,23,.46),
                        inset 0 0 0 1px rgba(255,255,255,.55),
                        inset 0 0 28px rgba(34,197,94,.10);
                    background: linear-gradient(135deg, rgba(255,255,255,.06), rgba(255,255,255,.015));
                    backdrop-filter: blur(1px);
                    -webkit-backdrop-filter: blur(1px);
                }
                .fs-scanner-frame::before,
                .fs-scanner-frame::after {
                    content: ''; position: absolute; inset: 16px; border-radius: 20px; pointer-events: none;
                }
                .fs-scanner-frame::before {
                    border-top: 1px solid rgba(255,255,255,.42);
                    border-bottom: 1px solid rgba(255,255,255,.42);
                    filter: drop-shadow(0 0 14px rgba(34,197,94,.32));
                }
                .fs-scanner-frame::after {
                    left: 12%; right: 12%; top: 50%; bottom: auto; height: 2px;
                    background: #22c55e; box-shadow: 0 0 22px rgba(34,197,94,.9);
                    animation: scannerLine 1.6s ease-in-out infinite;
                }
                .fs-scanner-corner {
                    position: absolute;
                    width: 58px;
                    height: 58px;
                    border-color: #22c55e;
                    filter: drop-shadow(0 0 12px rgba(34,197,94,.72));
                    pointer-events: none;
                }
                .fs-scanner-corner.tl { top: -2px; left: -2px; border-top: 5px solid; border-left: 5px solid; border-radius: 26px 0 0 0; }
                .fs-scanner-corner.tr { top: -2px; right: -2px; border-top: 5px solid; border-right: 5px solid; border-radius: 0 26px 0 0; }
                .fs-scanner-corner.bl { bottom: -2px; left: -2px; border-bottom: 5px solid; border-left: 5px solid; border-radius: 0 0 0 26px; }
                .fs-scanner-corner.br { bottom: -2px; right: -2px; border-bottom: 5px solid; border-right: 5px solid; border-radius: 0 0 26px 0; }
                .fs-scanner-frame-label {
                    position: absolute;
                    left: 50%;
                    bottom: -46px;
                    transform: translateX(-50%);
                    padding: 8px 13px;
                    border-radius: 999px;
                    background: rgba(15,23,42,.72);
                    border: 1px solid rgba(255,255,255,.14);
                    color: #dbeafe;
                    font-size: 12px;
                    font-weight: 900;
                    white-space: nowrap;
                    backdrop-filter: blur(12px);
                    -webkit-backdrop-filter: blur(12px);
                }
                @keyframes scannerLine {
                    0%,100% { transform: translateY(-52px); opacity: .55; }
                    50% { transform: translateY(52px); opacity: 1; }
                }
                .fs-scanner-status {
                    position: absolute; left: 50%; bottom: 34px; transform: translateX(-50%);
                    max-width: min(92vw, 560px); width: max-content;
                    padding: 13px 18px; border-radius: 999px;
                    background: rgba(15,23,42,.72); border: 1px solid rgba(255,255,255,.14);
                    color: #e2e8f0; font-size: 13px; font-weight: 800; text-align: center;
                    backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
                }
                .fs-scanner-placeholder {
                    position: absolute; inset: 0; display: grid; place-items: center;
                    color: #cbd5e1; text-align: center; padding: 28px;
                }
                @media (max-width: 720px) {
                    .fs-scanner-frame { width: 84vw; aspect-ratio: 1.35 / 1; }
                    .fs-scanner-brand strong { font-size: 13px; }
                    .fs-scanner-brand span:last-child { font-size: 11px; }
                }
            </style>
            <video id="header-barcode-video" class="fs-scanner-video" autoplay muted playsinline></video>
            <div id="header-barcode-camera-placeholder" class="fs-scanner-placeholder">
                <div>
                    <span class="material-symbols-rounded" style="font-size:58px; display:block; margin-bottom:12px;">photo_camera</span>
                    <strong style="display:block; color:#fff; font-size:20px; margin-bottom:6px;">Opening Camera</strong>
                    <span>Allow camera permission to scan barcode.</span>
                </div>
            </div>
            <div class="fs-scanner-overlay"></div>
            <div class="fs-scanner-top">
                <div class="fs-scanner-brand">
                    <span class="material-symbols-rounded">barcode_scanner</span>
                    <div><strong>Barcode Scanner</strong><span>Align barcode inside frame</span></div>
                </div>
                <button class="fs-scanner-close" type="button" id="fullscreen-barcode-close" aria-label="Close scanner">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="fs-scanner-frame">
                <span class="fs-scanner-corner tl"></span>
                <span class="fs-scanner-corner tr"></span>
                <span class="fs-scanner-corner bl"></span>
                <span class="fs-scanner-corner br"></span>
                <span class="fs-scanner-frame-label">Place barcode inside frame</span>
            </div>
            <div id="header-barcode-status" class="fs-scanner-status">Starting camera...</div>
        `;
        document.body.appendChild(scanner);

        const status = document.getElementById('header-barcode-status');
        const video = document.getElementById('header-barcode-video');
        const placeholder = document.getElementById('header-barcode-camera-placeholder');
        document.getElementById('fullscreen-barcode-close')?.addEventListener('click', stopBarcodeCamera);

        let isScanBusy = false;
        const runScan = async (rawBarcode) => {
            if (isScanBusy) return;
            const barcode = normalizeBarcodeInput(rawBarcode || '');
            if (!barcode) {
                playScannerBeep('error');
                if (status) status.textContent = 'Unreadable barcode. Hold steady.';
                return;
            }

            isScanBusy = true;
            if (status) status.textContent = `Scanned: ${barcode}. Looking up...`;
            try {
                let url = scopeTabSessionUrl(`/?url=search/barcode&q=${encodeURIComponent(barcode)}`);
                let response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });

                if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                    url = scopeTabSessionUrl(`/search/barcode?q=${encodeURIComponent(barcode)}`);
                    response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                }

                const data = await response.json();
                if (!data || !data.found) {
                    playScannerBeep('error');
                    if (status) status.textContent = `❌ Product not found for barcode: ${barcode}`;
                    window.appToast?.(data?.message || `Product not found for barcode: ${barcode}`, 'error');
                    setTimeout(() => { isScanBusy = false; }, 1600);
                    return;
                }

                playScannerBeep('success');
                window.appToast?.(`Added: ${data.product?.name || 'Product'}`, 'success');
                stopBarcodeCamera();

                // If currently on POS page, add directly to active cart
                if (typeof window.addScannedProduct === 'function' && data.product) {
                    window.addScannedProduct(data.product);
                } else if (typeof window.handleRemoteBarcode === 'function') {
                    window.handleRemoteBarcode(barcode, data.product);
                } else {
                    loadPage(data.url || `/pos/index&add=${data.product.id}`);
                }
            } catch (error) {
                console.error('Camera scanner lookup error:', error);
                playScannerBeep('error');
                if (status) status.textContent = `Product not found: ${barcode}`;
                window.appToast?.(`Product not found for barcode: ${barcode}`, 'error');
                setTimeout(() => { isScanBusy = false; }, 1600);
            }
        };

        const startCamera = async () => {
            if (!navigator.mediaDevices?.getUserMedia) {
                if (status) status.textContent = 'Camera is not available in this browser.';
                return;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false
                });
                window.__barcodeScannerStream = stream;
                window.__barcodeScannerActive = true;
                if (video) {
                    video.srcObject = stream;
                    await video.play();
                }
                if (placeholder) placeholder.style.display = 'none';
                if (status) status.textContent = 'Camera ready. Align barcode or QR inside the frame.';

                // 1. Hardware BarcodeDetector API
                if ('BarcodeDetector' in window) {
                    try {
                        const detector = new BarcodeDetector();
                        const scanFrame = async () => {
                            if (!window.__barcodeScannerActive || !video || video.readyState < 2) {
                                if (window.__barcodeScannerActive) requestAnimationFrame(scanFrame);
                                return;
                            }
                            try {
                                const codes = await detector.detect(video);
                                if (codes && codes.length > 0 && !isScanBusy) {
                                    await runScan(codes[0].rawValue || '');
                                    return;
                                }
                            } catch (error) {}
                            if (window.__barcodeScannerActive) {
                                requestAnimationFrame(scanFrame);
                            }
                        };
                        requestAnimationFrame(scanFrame);
                        return;
                    } catch (e) {}
                }

                // 2. ZXing Multi-Format Reader Fallback
                if (typeof ZXing !== 'undefined') {
                    window.__barcodeZXingReader = new ZXing.BrowserMultiFormatReader();
                    window.__barcodeZXingReader.decodeFromVideoElement(video, (result, err) => {
                        if (result && result.getText() && window.__barcodeScannerActive && !isScanBusy) {
                            runScan(result.getText());
                        }
                    });
                }
            } catch (error) {
                if (status) status.textContent = 'Camera permission unavailable. Allow camera access and try again.';
                if (placeholder) {
                    placeholder.innerHTML = '<div><span class="material-symbols-rounded" style="font-size:58px; display:block; margin-bottom:12px;">videocam_off</span><strong style="display:block; color:#fff; font-size:20px; margin-bottom:6px;">Camera unavailable</strong><span>Allow permission in browser settings and reopen.</span></div>';
                }
            }
        };

        startCamera();
    };

    /**
     * Workflow B: Camera Scanner for Add Product Form (Inventory)
     */
    window.openBarcodeCameraModal = function(targetInputId) {
        const input = document.getElementById(targetInputId);
        let activeStream = null;
        let zxingReader = null;
        let isChecking = false;

        const modalOverlay = document.createElement('div');
        modalOverlay.className = 'fs-scanner-overlay';
        modalOverlay.style.zIndex = '99999';
        modalOverlay.innerHTML = `
            <div class="fs-scanner-head">
                <div class="fs-scanner-title">
                    <span class="material-symbols-rounded">photo_camera</span>
                    <span>Scan Product Barcode / QR</span>
                </div>
                <button class="fs-scanner-close" type="button" id="modal-barcode-camera-close" aria-label="Close scanner">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="fs-scanner-viewport" style="position:relative; width:100%; height:100%; display:flex; align-items:center; justify-content:center;">
                <video id="modal-barcode-camera-video" playsinline muted autoplay style="width:100%; height:100%; object-fit:cover;"></video>
                <div class="fs-scanner-frame">
                    <span class="fs-scanner-corner tl"></span>
                    <span class="fs-scanner-corner tr"></span>
                    <span class="fs-scanner-corner bl"></span>
                    <span class="fs-scanner-corner br"></span>
                    <span class="fs-scanner-frame-label">Align product barcode or QR</span>
                </div>
            </div>
            <div id="modal-barcode-camera-status" class="fs-scanner-status">Starting camera preview...</div>
        `;
        document.body.appendChild(modalOverlay);

        const status = document.getElementById('modal-barcode-camera-status');
        const video = document.getElementById('modal-barcode-camera-video');

        const closeModal = () => {
            if (activeStream) {
                try { activeStream.getTracks().forEach(t => t.stop()); } catch(e) {}
                activeStream = null;
            }
            if (zxingReader) {
                try { zxingReader.reset(); } catch(e) {}
                zxingReader = null;
            }
            if (modalOverlay.parentNode) {
                modalOverlay.parentNode.removeChild(modalOverlay);
            }
        };

        document.getElementById('modal-barcode-camera-close')?.addEventListener('click', closeModal);

        const handleBarcodeDetected = async (rawCode) => {
            if (isChecking) return;
            const barcode = normalizeBarcodeInput(rawCode || '');
            if (!barcode) return;

            isChecking = true;
            if (status) status.textContent = `Checking barcode: ${barcode}...`;

            try {
                // Workflow B: Check if barcode already exists
                const res = await fetch(scopeTabSessionUrl(`/scanner/check_barcode?barcode=${encodeURIComponent(barcode)}`), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                if (data && data.exists && data.product) {
                    playScannerBeep('error');
                    if (status) status.textContent = `⚠️ Barcode Already Exists: "${data.product.name}" (${data.product.formatted_price})`;
                    window.appToast?.(`Barcode already registered to "${data.product.name}"`, 'warning');
                    setTimeout(() => { isChecking = false; }, 2000);
                    return;
                }

                // New barcode: populate input and close
                playScannerBeep('success');
                if (input) {
                    input.value = barcode;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
                window.appToast?.(`Barcode captured: ${barcode}`, 'success');
                closeModal();
            } catch (e) {
                // Fallback: still populate input if check fails
                playScannerBeep('success');
                if (input) {
                    input.value = barcode;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
                closeModal();
            }
        };

        // Start Camera
        (async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false
                });
                activeStream = stream;
                if (video) {
                    video.srcObject = stream;
                    await video.play();
                }
                if (status) status.textContent = 'Camera active. Point at physical product barcode.';

                if ('BarcodeDetector' in window) {
                    try {
                        const detector = new BarcodeDetector();
                        const checkFrame = async () => {
                            if (!modalOverlay.parentNode || !video || video.readyState < 2) {
                                if (modalOverlay.parentNode) requestAnimationFrame(checkFrame);
                                return;
                            }
                            try {
                                const codes = await detector.detect(video);
                                if (codes && codes.length > 0 && !isChecking) {
                                    await handleBarcodeDetected(codes[0].rawValue || '');
                                    return;
                                }
                            } catch(e) {}
                            if (modalOverlay.parentNode) requestAnimationFrame(checkFrame);
                        };
                        requestAnimationFrame(checkFrame);
                        return;
                    } catch(e) {}
                }

                if (typeof ZXing !== 'undefined') {
                    zxingReader = new ZXing.BrowserMultiFormatReader();
                    zxingReader.decodeFromVideoElement(video, (result, err) => {
                        if (result && result.getText() && modalOverlay.parentNode && !isChecking) {
                            handleBarcodeDetected(result.getText());
                        }
                    });
                }
            } catch (err) {
                if (status) status.textContent = 'Camera permission required. Please allow camera access in browser.';
            }
        })();
    };

    headerBarcodeScan?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        openBarcodeScanner();
    });

    mobileBarcodeScan?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        openBarcodeScanner();
    });



    const saveFocusState = () => {
        const active = document.activeElement;
        if (!active || !active.name) return null;
        return {
            name: active.name,
            id: active.id,
            selectionStart: active.selectionStart,
            selectionEnd: active.selectionEnd
        };
    };

    const restoreFocusState = (state) => {
        if (!state) return;
        requestAnimationFrame(() => {
            let el = state.id ? document.getElementById(state.id) : null;
            if (!el) el = document.querySelector(`[name="${state.name}"]`);
            if (el) {
                el.focus();
                if (el.setSelectionRange && state.selectionStart !== undefined) {
                    try { el.setSelectionRange(state.selectionStart, state.selectionEnd); } catch(e) {}
                }
            }
        });
    };

    window.stopAllActiveCameraStreams = function() {
        if (window.__inventoryProductCameraStream) {
            try { window.__inventoryProductCameraStream.getTracks().forEach(t => t.stop()); } catch(e) {}
            window.__inventoryProductCameraStream = null;
        }
        if (window.__activeInventoryCameraStream) {
            try { window.__activeInventoryCameraStream.getTracks().forEach(t => t.stop()); } catch(e) {}
            window.__activeInventoryCameraStream = null;
        }
        if (window.__editCamStream) {
            try { window.__editCamStream.getTracks().forEach(t => t.stop()); } catch(e) {}
            window.__editCamStream = null;
        }
        if (window.__inventoryZxingReader) {
            try { window.__inventoryZxingReader.reset(); } catch(e) {}
            window.__inventoryZxingReader = null;
        }
        if (window.__editCamZxingReader) {
            try { window.__editCamZxingReader.reset(); } catch(e) {}
            window.__editCamZxingReader = null;
        }
        document.querySelectorAll('video').forEach(vid => {
            if (vid.srcObject && typeof vid.srcObject.getTracks === 'function') {
                try { vid.srcObject.getTracks().forEach(t => t.stop()); } catch(e) {}
                vid.srcObject = null;
            }
        });
        const m1 = document.getElementById('barcode-cam-modal-container');
        if (m1) m1.remove();
        const m2 = document.getElementById('inventory-barcode-modal-container');
        if (m2) m2.remove();
    };

    window.addEventListener('pagehide', () => window.stopAllActiveCameraStreams?.());
    window.addEventListener('beforeunload', () => window.stopAllActiveCameraStreams?.());

    const initAppAlerts = (root = document) => {
        root.querySelectorAll('.app-alert').forEach((alert) => {
            if (alert.dataset.dismissInit) return;
            alert.dataset.dismissInit = 'true';
            alert.style.transition = 'opacity 0.35s ease, transform 0.35s ease, max-height 0.35s ease';
            
            if (!alert.querySelector('.alert-close-btn')) {
                const closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'alert-close-btn';
                closeBtn.innerHTML = '&times;';
                closeBtn.style.cssText = 'margin-left:auto; background:none; border:none; font-size:20px; line-height:1; cursor:pointer; color:inherit; opacity:0.6; padding:0 4px;';
                closeBtn.onclick = () => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 350);
                };
                alert.appendChild(closeBtn);
            }

            setTimeout(() => {
                if (alert && alert.parentElement) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-4px)';
                    setTimeout(() => alert.remove(), 350);
                }
            }, 4000);
        });
    };
    initAppAlerts();
    window.appInitAppAlerts = initAppAlerts;

    const triggerFlashToast = (flash) => {
        if (!flash || !flash.message) return;
        const flashKey = `${flash.type || 'info'}_${flash.message}`;
        const lastShownKey = sessionStorage.getItem('last_flash_toast');
        const lastShownTime = Number(sessionStorage.getItem('last_flash_time') || 0);
        const now = Date.now();
        // Prevent duplicate flash toast if the exact same message was shown in the last 15 seconds
        if (lastShownKey === flashKey && (now - lastShownTime < 15000)) {
            return;
        }
        sessionStorage.setItem('last_flash_toast', flashKey);
        sessionStorage.setItem('last_flash_time', String(now));
        window.appToast(flash.message, flash.type);
    };

    const loadPage = async (url, pushState = true) => {
        url = scopeTabSessionUrl(url);
        window.stopAllActiveCameraStreams();
        if (window.__inventoryRefreshTimer) {
            clearInterval(window.__inventoryRefreshTimer);
            window.__inventoryRefreshTimer = null;
        }

        if (!contentArea) {
            window.location.href = url;
            return;
        }

        // Show premium top progress bar loader
        let progressBar = document.getElementById('app-top-progress-bar');
        if (!progressBar) {
            progressBar = document.createElement('div');
            progressBar.id = 'app-top-progress-bar';
            progressBar.style.cssText = `
                position: fixed; top: 0; left: 0; height: 3px; background: var(--primary, #0f6dff);
                z-index: 9999999; transition: width 0.4s cubic-bezier(0.08, 0.82, 0.17, 1), opacity 0.3s ease;
                width: 0%; opacity: 1; box-shadow: 0 0 8px var(--primary, #0f6dff);
            `;
            document.body.appendChild(progressBar);
        }
        progressBar.style.width = '10%';
        progressBar.style.opacity = '1';
        setTimeout(() => { if (progressBar.style.width === '10%') progressBar.style.width = '70%'; }, 150);

        contentArea.style.opacity = '0.5';
        contentArea.style.pointerEvents = 'none';

        try {
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
                }
            });

            if (!res.ok) {
                window.location.href = res.url || url;
                return;
            }

            const contentType = res.headers.get('content-type') || '';
            if (contentType.includes('text/html') || res.redirected) {
                window.location.href = res.redirected ? res.url : url;
                return;
            }

            const rawText = await res.text();
            let data;
            try {
                data = JSON.parse(rawText);
            } catch (jsonErr) {
                // Non-JSON response, fallback cleanly to full page navigation
                window.location.href = url;
                return;
            }
            
            if (data.error) {
                window.appToast(data.message || 'An unknown server error occurred.', 'error');
                return;
            }

            if (data.ajax_redirect) {
                if (data.csrfToken) {
                window.appCsrfToken = data.csrfToken;
                csrfToken = data.csrfToken;
                if (document.body) {
                    document.body.dataset.csrfToken = data.csrfToken;
                }
                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = data.csrfToken;
                });
            }

            triggerFlashToast(data.flash);
                return loadPage(data.ajax_redirect, pushState);
            }
            
            const focusState = saveFocusState();
            
            document.title = data.pageTitle || document.title;
            
            // PRE-PROCESS TABS TO PREVENT FLICKER
            try {
                const targetUrlContext = res.redirected ? res.url : url;
                const tabKeyMatch = 'tab_' + new URLSearchParams(targetUrlContext.includes('?') ? targetUrlContext.split('?')[1] : '').get('url');
                const activeSavedTab = sessionStorage.getItem(tabKeyMatch);
                if (activeSavedTab && data.html) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.html;
                    let tabFoundMatch = false;
                    // style.display tabs (expense, users, credits, credit, returns, updates, notifications)
                    tempDiv.querySelectorAll('[data-expense-panel],[data-users-panel],[data-credits-panel],[data-credit-panel],[data-returns-panel],[data-updates-panel],[data-settings-panel],[data-notifications-panel]').forEach(p => {
                        const attr = Array.from(p.attributes).find(a => a.name.endsWith('-panel'));
                        if (attr) {
                            if (attr.value === activeSavedTab) { p.style.display = ''; tabFoundMatch = true; }
                            else { p.style.display = 'none'; }
                        }
                    });
                    tempDiv.querySelectorAll('[data-expense-tab],[data-users-tab],[data-credits-tab],[data-credit-tab],[data-returns-tab],[data-updates-tab],[data-settings-tab],[data-notifications-tab]').forEach(b => {
                        const attr = Array.from(b.attributes).find(a => a.name.endsWith('-tab'));
                        if (attr) {
                            if (attr.value === activeSavedTab) { b.classList.add('btn-primary'); b.classList.remove('btn-secondary'); }
                            else { b.classList.remove('btn-primary'); b.classList.add('btn-secondary'); }
                        }
                    });
                    // .active class tabs (inventory, customers)
                    tempDiv.querySelectorAll('[data-inventory-pane],[data-customers-pane]').forEach(p => {
                        const attr = Array.from(p.attributes).find(a => a.name.endsWith('-pane'));
                        if (attr) {
                            if (attr.value === activeSavedTab) { p.classList.add('active'); tabFoundMatch = true; }
                            else { p.classList.remove('active'); }
                        }
                    });
                    tempDiv.querySelectorAll('[data-inventory-tab],[data-customers-tab]').forEach(b => {
                        const attr = Array.from(b.attributes).find(a => a.name.endsWith('-tab'));
                        if (attr) {
                            if (attr.value === activeSavedTab) { b.classList.add('active'); }
                            else { b.classList.remove('active'); }
                        }
                    });
                    if (tabFoundMatch) { data.html = tempDiv.innerHTML; }
                }
            } catch(e) {}

            contentArea.innerHTML = data.html || '';
            contentArea.style.pointerEvents = 'auto';
            
            restoreFocusState(focusState);

            applyTabSessionScope(contentArea);
            enhanceFormIcons(contentArea);
            initUnitPickers(contentArea);
            initTablePagination(contentArea);
            syncCustomDateFields(contentArea);
            executeScriptsInNode(contentArea);
            runExtraJs(data.extraJs, contentArea);
            initAppAlerts(contentArea);

            if (data.csrfToken) {
                window.appCsrfToken = data.csrfToken;
                csrfToken = data.csrfToken;
                if (document.body) {
                    document.body.dataset.csrfToken = data.csrfToken;
                }
                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = data.csrfToken;
                });
            }

            triggerFlashToast(data.flash);

            document.querySelectorAll('.sidebar-nav .nav-item, .mobile-bottom-nav a').forEach((item) => {
                item.classList.remove('active');
                const menuMap = {
                    dashboard: 'home/index', pos: 'pos/index', sales: 'sales/index',
                    credits: 'credits/index', inventory: 'inventory/index', expenses: 'expenses/index',
                    customers: 'customers/index', reports: 'reports/index', users: 'users/index', settings: 'updates/index',
                    quotations: 'quotations/index', templates: 'templates/index'
                };
                const menuTarget = menuMap[data.activeMenu] || data.activeMenu;
                if (data.activeMenu && item.href && item.href.includes(menuTarget)) {
                    item.classList.add('active');
                }
            });

            if (pushState) {
                history.pushState({ url }, '', res.redirected ? res.url : url);
            }

            if (progressBar) {
                progressBar.style.width = '100%';
                setTimeout(() => {
                    progressBar.style.opacity = '0';
                    setTimeout(() => { progressBar.style.width = '0%'; }, 300);
                }, 200);
            }
            contentArea.style.opacity = '1';
            contentArea.style.pointerEvents = 'auto';

        } catch (error) {
            if (progressBar) {
                progressBar.style.width = '100%';
                setTimeout(() => {
                    progressBar.style.opacity = '0';
                    setTimeout(() => { progressBar.style.width = '0%'; }, 300);
                }, 200);
            }
            contentArea.style.opacity = '1';
            contentArea.style.pointerEvents = 'auto';

            console.warn('AJAX load fallback to standard navigation:', error);
            window.location.href = url;
        }
    };

    window.appLoadPage = loadPage;

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank' || link.hasAttribute('data-no-ajax') || href.includes('export') || href.includes('backup') || link.hasAttribute('download') || href.includes('/plans/pay') || href.includes('/auth/')) {
            return;
        }

        try {
            const urlObj = new URL(link.href, window.location.origin);
            if (urlObj.origin !== window.location.origin) {
                return;
            }
        } catch(e) {}

        if (href.includes('/delete/')) {
            event.preventDefault();
            window.appModal.open({
                title: 'Confirm Delete',
                body: `
                    <div style="padding:6px 0 4px;">
                        <div style="font-weight:700; margin-bottom:8px;">Are you sure you want to delete this record?</div>
                        <div class="page-subtitle">This action may affect related data and cannot be easily undone.</div>
                    </div>
                `,
                actions: `
                    <button class="btn btn-secondary" type="button" data-modal-close>Cancel</button>
                    <button class="btn btn-danger" type="button" id="confirm-delete-action">
                        <span class="material-symbols-rounded">delete</span>Yes, Delete
                    </button>
                `
            });
            const confirmDeleteButton = document.getElementById('confirm-delete-action');
            if (confirmDeleteButton) {
                confirmDeleteButton.onclick = () => {
                    window.appModal.close();
                    loadPage(link.href);
                };
            }
            return;
        }

        if (link.hasAttribute('data-notification-open')) {
            removeClickedNotificationFromBell(link);
        }

        event.preventDefault();
        closeDropdowns();
        loadPage(link.href);
        sidebar?.classList.remove('open');
        setMobileMenuIcon(false);
    });

    const executeFormSubmit = (form, submitter, method, action) => {
        const finalFormData = new FormData(form);
        if (submitter && submitter.name) {
            finalFormData.append(submitter.name, submitter.value);
        }

        action = scopeTabSessionUrl(action);
        fetch(action, {
            method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
            },
            body: finalFormData
        })
            .then(async (res) => {
                if (!res.ok) throw new Error('Network error');
                const data = await res.json();
                
                if (data.error) {
                    window.appToast(data.message || 'An unknown server error occurred.', 'error');
                    return;
                }

                if (data.ajax_redirect) {
                    if (data.csrfToken) {
                window.appCsrfToken = data.csrfToken;
                csrfToken = data.csrfToken;
                if (document.body) {
                    document.body.dataset.csrfToken = data.csrfToken;
                }
                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = data.csrfToken;
                });
            }

            triggerFlashToast(data.flash);
                    return loadPage(data.ajax_redirect, true);
                }
                
                const focusState = saveFocusState();
                document.title = data.pageTitle || document.title;
                
                // PRE-PROCESS TABS TO PREVENT FLICKER
                try {
                    const targetUrlContext = res.redirected ? res.url : action;
                    const tabKeyMatch = 'tab_' + new URLSearchParams(targetUrlContext.includes('?') ? targetUrlContext.split('?')[1] : '').get('url');
                    const activeSavedTab = sessionStorage.getItem(tabKeyMatch);
                    if (activeSavedTab && data.html) {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = data.html;
                        let tabFoundMatch = false;
                        // style.display tabs
                        tempDiv.querySelectorAll('[data-expense-panel],[data-users-panel],[data-credits-panel],[data-credit-panel],[data-returns-panel],[data-updates-panel],[data-settings-panel],[data-notifications-panel]').forEach(p => {
                            const attr = Array.from(p.attributes).find(a => a.name.endsWith('-panel'));
                            if (attr) {
                                if (attr.value === activeSavedTab) { p.style.display = ''; tabFoundMatch = true; }
                                else { p.style.display = 'none'; }
                            }
                        });
                        tempDiv.querySelectorAll('[data-expense-tab],[data-users-tab],[data-credits-tab],[data-credit-tab],[data-returns-tab],[data-updates-tab],[data-settings-tab],[data-notifications-tab]').forEach(b => {
                            const attr = Array.from(b.attributes).find(a => a.name.endsWith('-tab'));
                            if (attr) {
                                if (attr.value === activeSavedTab) { b.classList.add('btn-primary'); b.classList.remove('btn-secondary'); }
                                else { b.classList.remove('btn-primary'); b.classList.add('btn-secondary'); }
                            }
                        });
                        // .active class tabs (inventory, customers)
                        tempDiv.querySelectorAll('[data-inventory-pane],[data-customers-pane]').forEach(p => {
                            const attr = Array.from(p.attributes).find(a => a.name.endsWith('-pane'));
                            if (attr) {
                                if (attr.value === activeSavedTab) { p.classList.add('active'); tabFoundMatch = true; }
                                else { p.classList.remove('active'); }
                            }
                        });
                        tempDiv.querySelectorAll('[data-inventory-tab],[data-customers-tab]').forEach(b => {
                            const attr = Array.from(b.attributes).find(a => a.name.endsWith('-tab'));
                            if (attr) {
                                if (attr.value === activeSavedTab) { b.classList.add('active'); }
                                else { b.classList.remove('active'); }
                            }
                        });
                        if (tabFoundMatch) { data.html = tempDiv.innerHTML; }
                    }
                } catch(e) {}

                contentArea.innerHTML = data.html || '';
                contentArea.style.pointerEvents = 'auto';
                restoreFocusState(focusState);

                applyTabSessionScope(contentArea);
                enhanceFormIcons(contentArea);
                initUnitPickers(contentArea);
                initTablePagination(contentArea);
                initStoreDirectory(contentArea);
                syncCustomDateFields(contentArea);
                executeScriptsInNode(contentArea);
                runExtraJs(data.extraJs, contentArea);
                
                if (data.csrfToken) {
                window.appCsrfToken = data.csrfToken;
                csrfToken = data.csrfToken;
                if (document.body) {
                    document.body.dataset.csrfToken = data.csrfToken;
                }
                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = data.csrfToken;
                });
            }

            triggerFlashToast(data.flash);

                if (res.redirected) {
                    history.pushState({ url: res.url }, '', res.url);
                }
            })
            .catch((error) => {
                console.error('AJAX Submit Error:', error);
                if (!navigator.onLine) {
                    window.appToast("You are currently offline. This action requires an internet connection.", "error");
                } else {
                    window.appToast("AJAX Submit Error: " + error.message, "error");
                }
            });
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;
        const submitter = event.submitter;
        const method = (form.method || 'GET').toUpperCase();
        const action = form.action || window.location.href;

        if (action.includes('/delete/')) {
            if (form.dataset.confirmBypassed === 'true') {
                // Already confirmed via modal, proceed cleanly without duplicate dialog
            } else if (form.dataset.confirm || (form.getAttribute('onsubmit') && form.getAttribute('onsubmit').includes('confirm'))) {
                // Form has its own confirm attribute/handler which will be handled by universal appConfirm interceptor
                return;
            } else {
                event.preventDefault();
                const isDanger = form.dataset.confirmDanger === 'false' ? false : true;
                window.appConfirm('Are you sure you want to delete this record?\nThis action may affect related data and cannot be easily undone.', 'Confirm Delete', { isDanger: isDanger, confirmText: 'Yes, Delete' }).then((confirmed) => {
                    if (confirmed) {
                        form.dataset.confirmBypassed = 'true';
                        if (method === 'GET') {
                            loadPage(buildUrlFromForm(form, submitter));
                        } else {
                            executeFormSubmit(form, submitter, method, action);
                        }
                    }
                });
                return;
            }
        }

        // Ensure CSRF token is present and fresh on all form submissions
        let csrfInput = form.querySelector('input[name="csrf_token"]');
        const activeToken = getCsrfToken();
        if (activeToken) {
            if (!csrfInput) {
                csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = activeToken;
                form.appendChild(csrfInput);
            } else if (!csrfInput.value) {
                csrfInput.value = activeToken;
            }
        }

        if (form.hasAttribute('data-no-ajax')) {
            return;
        }

        event.preventDefault();

        if (method === 'GET') {
            const url = buildUrlFromForm(form, submitter);
            loadPage(url);
            return;
        }
        
        executeFormSubmit(form, submitter, method, action);
    });

    document.addEventListener('change', async (event) => {
        const checkbox = event.target.closest('[data-store-status-checkbox]');
        if (!checkbox) return;

        const form = checkbox.closest('[data-store-status-form]');
        if (!form || form.dataset.loading === '1') return;

        const toggle = form.querySelector('[data-store-status-toggle]');
        const statusInput = form.querySelector('[data-store-status-input]');
        const text = form.querySelector('[data-store-status-text]');
        const icon = form.querySelector('[data-store-status-icon]');
        const previousStatus = checkbox.dataset.lastStatus || (checkbox.checked ? 'suspended' : 'active');
        const nextStatus = checkbox.checked ? 'active' : 'suspended';

        const classForStatus = (status) => {
            if (status === 'active' || status === 'paid') return 'is-good';
            if (status === 'suspended' || status === 'expired') return 'is-danger';
            return 'is-warn';
        };

        const applyStatus = (status, label = null) => {
            const cleanStatus = status === 'active' ? 'active' : 'suspended';
            const cleanLabel = label || (cleanStatus === 'active' ? 'Active' : 'Suspended');
            checkbox.checked = cleanStatus === 'active';
            checkbox.dataset.lastStatus = cleanStatus;
            if (statusInput) statusInput.value = cleanStatus;
            if (text) text.textContent = cleanLabel;
            if (icon) icon.textContent = cleanStatus === 'active' ? 'toggle_on' : 'toggle_off';

            document.querySelectorAll('[data-store-status-badge], [data-store-status-pill]').forEach((node) => {
                node.classList.remove('is-good', 'is-warn', 'is-danger');
                node.classList.add(classForStatus(cleanStatus));
                const labelNode = node.querySelector('[data-store-status-label]');
                if (labelNode) {
                    labelNode.textContent = cleanLabel;
                } else {
                    node.textContent = cleanLabel;
                }
            });
        };

        if (statusInput) statusInput.value = nextStatus;
        form.dataset.loading = '1';
        toggle?.classList.add('is-loading');

        try {
            const response = await fetch(scopeTabSessionUrl(form.action), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.error || data.message || 'Status update failed.');
            }
            applyStatus(data.status || nextStatus, data.label);
            if (window.appToast) {
                window.appToast(data.message || 'Store status updated.', 'success');
            }
        } catch (error) {
            applyStatus(previousStatus);
            if (window.appToast) {
                window.appToast(error.message || 'Status update failed.', 'error');
            } else {
                alert(error.message || 'Status update failed.');
            }
        } finally {
            delete form.dataset.loading;
            toggle?.classList.remove('is-loading');
        }
    });

    window.addEventListener('popstate', (event) => {
        if (event.state?.url) {
            loadPage(event.state.url, false);
        }
    });

    const searchInput = document.getElementById('global-search');
    if (searchInput) {
        const resultsBox = document.createElement('div');
        resultsBox.className = 'search-results';
        searchInput.parentNode.appendChild(resultsBox);

        searchInput.addEventListener('input', (event) => {
            const query = event.target.value.trim();
            clearTimeout(searchTimeout);

            if (query.length < 2) {
                resultsBox.classList.remove('open');
                return;
            }

            searchTimeout = setTimeout(() => {
                resultsBox.innerHTML = '<div class="empty-state">Searching...</div>';
                resultsBox.classList.add('open');

                fetch(scopeTabSessionUrl(`/?url=search/index&q=${encodeURIComponent(query)}`), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then((res) => res.json())
                    .then((data) => {
                        const results = data.results || [];
                        if (!results.length) {
                            resultsBox.innerHTML = '<div class="empty-state">No results found.</div>';
                            resultsBox.classList.add('open');
                            return;
                        }

                        let group = '';
                        let html = '';
                        results.forEach((item) => {
                            const safeType = escapeHtml(item.type || 'Result');
                            const safeUrl = escapeHtml(item.url || '#');
                            const safeTitle = escapeHtml(item.title || '');
                            const safeSubtitle = escapeHtml(item.subtitle || '');

                            if (group !== safeType) {
                                group = safeType;
                                html += `<div class="search-group">${safeType}</div>`;
                            }
                            html += `<a class="search-item" href="${safeUrl}"><strong>${safeTitle}</strong><span>${safeSubtitle}</span></a>`;
                        });
                        resultsBox.innerHTML = html;
                        resultsBox.classList.add('open');
                    })
                    .catch(() => {
                        resultsBox.innerHTML = '<div class="empty-state">Search is not available right now.</div>';
                        resultsBox.classList.add('open');
                    });
            }, 250);
        });

        document.addEventListener('click', (event) => {
            if (!searchInput.contains(event.target) && !resultsBox.contains(event.target)) {
                resultsBox.classList.remove('open');
            }
        });

        resultsBox.addEventListener('click', (event) => {
            const link = event.target.closest('a.search-item');
            if (!link) return;
            searchInput.value = '';
            resultsBox.classList.remove('open');
        });

        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                resultsBox.classList.remove('open');
                searchInput.blur();
            }
        });
    }

    enhanceFormIcons(document);
    initUnitPickers(document);
    initTablePagination(document);
    initStoreDirectory(document);
    syncCustomDateFields(document);

    document.addEventListener('input', (event) => {
        const input = event.target.closest('[data-live-search]');
        if (!input) return;

        clearTimeout(input._liveTimer);
        input._liveTimer = setTimeout(() => {
            const form = input.closest('form');
            if (form) {
                loadPage(buildUrlFromForm(form));
            }
        }, 250);
    });

    document.addEventListener('change', (event) => {
        const select = event.target.closest('[data-custom-date-select]');
        if (!select) return;

        const target = select.dataset.customDateTarget || '';
        const form = select.closest('form');
        const fields = Array.from(form?.querySelectorAll(`[data-custom-date-field="${target}"]`) || []);
        const isCustom = select.value === 'custom';

        fields.forEach((field) => {
            field.hidden = !isCustom;
            field.disabled = !isCustom;
            if (!isCustom) {
                field.value = '';
            }
        });

        if (isCustom) {
            event.stopImmediatePropagation();
            fields[0]?.focus();
        }
    }, true);

    document.addEventListener('change', (event) => {
        const field = event.target.closest('[data-live-search]');
        if (!field) return;
        const form = field.closest('form');
        if (form) {
            loadPage(buildUrlFromForm(form, null));
        }
    });

    window.appModal = {
        open({ title = '', body = '', actions = '', maxWidth = '480px' }) {
            if (!modalRoot) return;
            const scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = `${scrollBarWidth}px`;
            
            modalRoot.innerHTML = `
                <style>
                    .premium-modal-backdrop {
                        position: fixed; top:0; left:0; right:0; bottom:0; z-index:999999;
                        background: rgba(15, 23, 42, 0.45);
                        backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
                        display: flex; align-items: center; justify-content: center;
                        animation: modalFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                        padding: 16px;
                        box-sizing: border-box;
                    }
                    .premium-modal-card {
                        background: #ffffff;
                        border-radius: 20px;
                        width: 100%; max-width: ${maxWidth};
                        box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(15, 23, 42, 0.05);
                        overflow: hidden;
                        animation: modalSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                        max-height: 90vh;
                        display: flex;
                        flex-direction: column;
                    }
                    .premium-modal-header {
                        padding: 18px 24px 14px;
                        display: flex; justify-content: space-between; align-items: center;
                        border-bottom: 1px solid #f1f5f9;
                        flex-shrink: 0;
                    }
                    .premium-modal-header h3 {
                        margin: 0; font-size: 17px; font-weight: 800; color: #0f172a;
                    }
                    .premium-modal-close-btn {
                        background: #f1f5f9; border: none; border-radius: 50%;
                        width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
                        color: #64748b; cursor: pointer; transition: all 0.2s ease;
                    }
                    .premium-modal-close-btn:hover { background: #e2e8f0; color: #0f172a; transform: rotate(90deg); }
                    .premium-modal-body { padding: 18px 24px; color: #475569; font-size: 14px; line-height: 1.5; overflow-y: auto; flex: 1; }
                    .premium-modal-actions {
                        padding: 14px 24px; background: #f8fafc; border-top: 1px solid #f1f5f9;
                        display: flex; justify-content: flex-end; gap: 10px;
                        flex-shrink: 0;
                    }
                    @keyframes modalFadeIn { from { opacity: 0; } to { opacity: 1; } }
                    @keyframes modalSlideUp { from { opacity: 0; transform: translateY(24px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
                </style>
                <div class="premium-modal-backdrop" data-modal-backdrop>
                    <div class="premium-modal-card">
                        <div class="premium-modal-header">
                            <h3>${title}</h3>
                            <button class="premium-modal-close-btn" type="button" data-modal-close><span class="material-symbols-rounded" style="font-size:20px;">close</span></button>
                        </div>
                        <div class="premium-modal-body">${body}</div>
                        ${actions ? `<div class="premium-modal-actions">${actions}</div>` : ''}
                    </div>
                </div>
            `;
        },
        updateBody(newBody) {
            if (!modalRoot) return;
            const bodyEl = modalRoot.querySelector('.premium-modal-body');
            if (bodyEl) bodyEl.innerHTML = newBody;
        },
        updateTitle(newTitle) {
            if (!modalRoot) return;
            const h3 = modalRoot.querySelector('.premium-modal-header h3');
            if (h3) h3.textContent = newTitle;
        },
        updateActions(newActions) {
            if (!modalRoot) return;
            let actionsEl = modalRoot.querySelector('.premium-modal-actions');
            if (!actionsEl && newActions) {
                const card = modalRoot.querySelector('.premium-modal-card');
                if (card) {
                    actionsEl = document.createElement('div');
                    actionsEl.className = 'premium-modal-actions';
                    card.appendChild(actionsEl);
                }
            }
            if (actionsEl) {
                if (newActions) {
                    actionsEl.innerHTML = newActions;
                    actionsEl.style.display = 'flex';
                } else {
                    actionsEl.style.display = 'none';
                }
            }
        },
        close() {
            if (modalRoot) {
                const card = modalRoot.querySelector('.premium-modal-card');
                const backdrop = modalRoot.querySelector('.premium-modal-backdrop');
                if (card && backdrop) {
                    card.style.animation = 'modalFadeOut 0.2s ease forwards';
                    backdrop.style.animation = 'modalFadeOut 0.2s ease forwards';
                    setTimeout(() => {
                        modalRoot.innerHTML = '';
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 200);
                } else {
                    modalRoot.innerHTML = '';
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }
            }
        }
    };

    window.appToast = (msg, type = 'success') => {
        const box = document.createElement('div');
        const isSuccess = type === 'success';
        const isWarning = type === 'warning';
        
        const bgColor = isSuccess ? 'rgba(255, 255, 255, 0.95)' : (isWarning ? 'rgba(255, 251, 235, 0.95)' : 'rgba(254, 242, 242, 0.95)');
        const iconColor = isSuccess ? '#10b981' : (isWarning ? '#f59e0b' : '#ef4444');
        const iconName = isSuccess ? 'check_circle' : (isWarning ? 'warning' : 'error');
        const textColor = '#0f172a';
        const borderColor = isSuccess ? 'rgba(16, 185, 129, 0.2)' : (isWarning ? 'rgba(245, 158, 11, 0.2)' : 'rgba(239, 68, 68, 0.2)');
        
        box.style.cssText = `
            position: fixed; bottom: 32px; right: 32px; z-index: 2100000;
            background: ${bgColor}; color: ${textColor};
            padding: 16px 24px; border-radius: 20px;
            font-weight: 600; font-size: 15px; letter-spacing: 0.2px;
            box-shadow: 0 20px 40px -8px rgba(15, 23, 42, 0.15), 0 0 0 1px ${borderColor};
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            display: flex; align-items: center; gap: 14px;
            animation: slideToastIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        `;
        box.innerHTML = `
            <style>
                @keyframes slideToastIn { 0% { transform: translateY(100px) scale(0.9); opacity: 0; } 100% { transform: translateY(0) scale(1); opacity: 1; } }
                @keyframes slideToastOut { 0% { transform: translateY(0) scale(1); opacity: 1; } 100% { transform: translateY(30px) scale(0.95); opacity: 0; } }
            </style>
            <span class="material-symbols-rounded" style="color:${iconColor}; font-size:24px;">${iconName}</span>
            <div style="line-height:1.4;">${msg}</div>
        `;
        document.body.appendChild(box);
        setTimeout(() => {
            box.style.animation = 'slideToastOut 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards';
            setTimeout(() => box.remove(), 400);
        }, 4000);
    };

    window.appAlert = (msg, title = 'Notice', type = 'info') => {
        return new Promise((resolve) => {
            const backdrop = document.createElement('div');
            backdrop.className = 'app-modal-backdrop';
            backdrop.style.cssText = 'position:fixed; inset:0; background:rgba(15,23,42,0.72); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); z-index:2000000; display:flex; align-items:center; justify-content:center; padding:18px; animation:appFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);';
            
            const iconName = type === 'error' ? 'error' : (type === 'success' ? 'check_circle' : 'info');
            const iconColor = type === 'error' ? 'var(--error, #dc2626)' : (type === 'success' ? 'var(--success, #059669)' : 'var(--primary, #0f6dff)');

            backdrop.innerHTML = `
                <div class="app-dialog-card" style="max-width: 420px; padding: 24px; text-align: center;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: ${type === 'error' ? 'rgba(220,38,38,0.1)' : 'rgba(15,109,255,0.1)'}; color: ${iconColor}; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <span class="material-symbols-rounded" style="font-size: 32px;">${iconName}</span>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--text-main); margin-bottom: 8px;">${title}</h3>
                    <p style="font-size: 14px; color: var(--text-muted); line-height: 1.5; margin-bottom: 22px;">${msg}</p>
                    <button type="button" class="btn btn-primary" style="width: 100%; justify-content: center; height: 42px; font-weight: 800;">
                        OK, Got it
                    </button>
                </div>
            `;

            document.body.appendChild(backdrop);
            const btn = backdrop.querySelector('button');
            btn.focus();
            btn.addEventListener('click', () => {
                backdrop.remove();
                resolve(true);
            });
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) {
                    backdrop.remove();
                    resolve(true);
                }
            });
        });
    };

    window.appConfirm = (msg, title = 'Confirm Action', options = {}) => {
        return new Promise((resolve) => {
            const backdrop = document.createElement('div');
            backdrop.className = 'app-modal-backdrop';
            backdrop.style.cssText = 'position:fixed; inset:0; background:rgba(15,23,42,0.72); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); z-index:2000000; display:flex; align-items:center; justify-content:center; padding:18px; animation:appFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);';
            
            const isDanger = options.isDanger ?? false;
            const confirmText = options.confirmText || (isDanger ? 'Delete' : 'Confirm');
            const cancelText = options.cancelText || 'Cancel';
            const iconName = options.icon || (isDanger ? 'delete_forever' : 'help');
            const iconColor = isDanger ? '#ef4444' : '#0f6dff';
            const iconBg = isDanger ? '#fee2e2' : '#eff6ff';
            const iconBorder = isDanger ? '#fecaca' : '#bfdbfe';

            backdrop.innerHTML = `
                <div class="app-dialog-card" style="max-width: 440px; width: 100%; padding: 28px 24px; text-align: center; border-radius: 20px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 25px 60px -15px rgba(15,23,42,0.4); animation: appPopIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: ${iconBg}; color: ${iconColor}; border: 1.5px solid ${iconBorder}; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <span class="material-symbols-rounded" style="font-size: 32px;">${iconName}</span>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 850; color: #0f172a; margin: 0 0 8px 0; letter-spacing: -0.01em;">${title}</h3>
                    <div style="font-size: 13.5px; color: #64748b; line-height: 1.55; margin-bottom: 24px;">${msg}</div>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button type="button" id="appConfirmCancelBtn" style="flex: 1; height: 42px; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; color: #334155; font-size: 13px; font-weight: 750; cursor: pointer; transition: all 0.15s ease;">
                            ${cancelText}
                        </button>
                        <button type="button" id="appConfirmOkBtn" style="flex: 1.2; height: 42px; border-radius: 10px; border: none; background: ${isDanger ? '#ef4444' : '#0f6dff'} !important; color: #ffffff !important; font-size: 13px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px ${isDanger ? 'rgba(239,68,68,0.35)' : 'rgba(15,109,255,0.35)'}; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.15s ease;">
                            <span class="material-symbols-rounded" style="font-size: 17px; color:#ffffff !important;">${isDanger ? 'delete' : 'check_circle'}</span>
                            <span>${confirmText}</span>
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(backdrop);
            const cancelBtn = backdrop.querySelector('#appConfirmCancelBtn');
            const okBtn = backdrop.querySelector('#appConfirmOkBtn');

            const cleanup = (result) => {
                window.removeEventListener('keydown', handleKey);
                backdrop.remove();
                resolve(result);
            };

            const handleKey = (e) => {
                if (e.key === 'Escape') cleanup(false);
                if (e.key === 'Enter') cleanup(true);
            };
            window.addEventListener('keydown', handleKey);

            cancelBtn.addEventListener('click', () => cleanup(false));
            okBtn.addEventListener('click', () => cleanup(true));
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) cleanup(false);
            });
            okBtn.focus();
        });
    };

    // Universal Global Confirmation Modal Interceptor
    // Automatically upgrades all native confirm() dialogs and delete triggers into the high-quality appConfirm modal
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form || form.dataset.confirmBypassed === 'true') return;

        const onsubmitAttr = form.getAttribute('onsubmit') || '';
        const dataConfirm = form.dataset.confirm || form.getAttribute('data-confirm');
        const customTitle = form.dataset.confirmTitle || 'Confirm Deletion';

        let msg = '';
        if (dataConfirm) {
            msg = dataConfirm;
        } else if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
            const match = onsubmitAttr.match(/confirm\s*\(\s*(['"`])([\s\S]*?)\1\s*\)/);
            msg = match ? match[2] : 'Are you sure you want to delete this record?';
        }

        if (msg) {
            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();

            const isDanger = form.dataset.confirmDanger === 'false' ? false : true;
            window.appConfirm(msg, customTitle, { isDanger: isDanger, confirmText: 'Yes, Delete' }).then((confirmed) => {
                if (confirmed) {
                    form.dataset.confirmBypassed = 'true';
                    form.removeAttribute('onsubmit');
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            });
        }
    }, true);

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('a[onclick*="confirm("], button[onclick*="confirm("], a[data-confirm], button[data-confirm]');
        if (!trigger || trigger.dataset.confirmBypassed === 'true') return;

        const onclickAttr = trigger.getAttribute('onclick') || '';
        const dataConfirm = trigger.dataset.confirm || trigger.getAttribute('data-confirm');
        const customTitle = trigger.dataset.confirmTitle || 'Confirm Action';

        let msg = '';
        if (dataConfirm) {
            msg = dataConfirm;
        } else if (onclickAttr && onclickAttr.includes('confirm(')) {
            const match = onclickAttr.match(/confirm\s*\(\s*(['"`])([\s\S]*?)\1\s*\)/);
            msg = match ? match[2] : 'Are you sure you want to proceed?';
        }

        if (msg) {
            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();

            const isDanger = /delete|remove|clear|wipe|cancel|trash/i.test(msg) || /delete|remove/i.test(trigger.href || '');
            window.appConfirm(msg, customTitle, { isDanger: isDanger, confirmText: isDanger ? 'Yes, Delete' : 'Confirm' }).then((confirmed) => {
                if (confirmed) {
                    trigger.dataset.confirmBypassed = 'true';
                    if (trigger.form) {
                        trigger.form.dataset.confirmBypassed = 'true';
                    }
                    trigger.removeAttribute('onclick');
                    if (trigger.tagName === 'A' && trigger.href) {
                        window.location.href = trigger.href;
                    } else {
                        trigger.click();
                    }
                }
            });
        }
    }, true);

    document.addEventListener('click', (event) => {
        const dateFilterButton = event.target.closest('button[name="date_filter"]');
        if (dateFilterButton && dateFilterButton.value !== 'custom') {
            const form = dateFilterButton.closest('form');
            const range = form?.querySelector('[data-custom-date-range]');
            if (range) {
                range.hidden = true;
            }
        }

        const toggle = event.target.closest('[data-custom-date-toggle]');
        if (!toggle) return;

        const form = toggle.closest('form');
        const range = form?.querySelector('[data-custom-date-range]');
        if (!range || !range.hidden) return;

        event.preventDefault();
        range.hidden = false;
        toggle.classList.add('btn-primary');
        toggle.classList.remove('btn-secondary');
        const hiddenFilter = form?.querySelector('input[name="date_filter"]');
        if (hiddenFilter) {
            hiddenFilter.value = 'custom';
        }
        range.querySelector('input[type="date"]')?.focus();
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-modal-close]') || event.target.matches('[data-modal-backdrop]')) {
            window.appModal.close();
        }
    });

    window.addEventListener('online', async () => {
        const syncedItems = await window.appOfflineQueue.sync();
        if (syncedItems && syncedItems.length > 0) {
            window.appToast(`${syncedItems.length} offline sale(s) have been successfully synced to the database!`, 'success');
        }
    });

    setMobileMenuIcon(false);
    syncNotificationBell();
    setInterval(syncNotificationBell, 20000);

    // --- PWA Installation Architecture ---
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent default mini-infobar
        e.preventDefault();
        deferredPrompt = e;
        
        // Show custom install promption UI if it doesn't already exist
        if (!document.getElementById('pwa-install-banner') && !sessionStorage.getItem('pwa_dismissed')) {
            const installBanner = document.createElement('div');
            installBanner.id = 'pwa-install-banner';
            installBanner.innerHTML = `
                <div style="position:fixed; bottom:20px; left:50%; transform:translateX(-50%); z-index:9999; background:#0f172a; border:1px solid #1e293b; padding:12px 20px; border-radius:100px; display:flex; align-items:center; gap:16px; box-shadow:0 10px 25px rgba(0,0,0,0.5); animation: fadeUpIn 0.5s ease;">
                    <style>@keyframes fadeUpIn { from { opacity:0; transform:translate(-50%, 20px); } to { opacity:1; transform:translate(-50%, 0); } }</style>
                    <div style="color:#fff; font-size:14px; font-weight:600; white-space:nowrap;">Install POS App</div>
                    <button id="pwa-install-btn" style="background:#0f6dff; border:none; border-radius:100px; color:#fff; padding:6px 16px; font-size:13px; font-weight:700; cursor:pointer; text-transform:uppercase; letter-spacing:0.5px;">Install</button>
                    <button id="pwa-dismiss-btn" style="background:none; border:none; color:#94a3b8; cursor:pointer; padding:4px; display:flex; align-items:center; justify-content:center; border-radius:50%; transition:background 0.2s;"><span class="material-symbols-rounded" style="font-size:18px;">close</span></button>
                </div>
            `;
            document.body.appendChild(installBanner);

            document.getElementById('pwa-install-btn').addEventListener('click', async () => {
                installBanner.remove();
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    deferredPrompt = null;
                }
            });

            document.getElementById('pwa-dismiss-btn').addEventListener('click', () => {
                installBanner.remove();
                sessionStorage.setItem('pwa_dismissed', 'true');
            });
        }
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        const banner = document.getElementById('pwa-install-banner');
        if (banner) banner.remove();
    });
});
