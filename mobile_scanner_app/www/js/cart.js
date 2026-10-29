/**
 * =========================================================
 * DevAltum POS Mobile Companion - Cart & Checkout Engine
 * =========================================================
 * Features:
 * 1. Real-time Cart Management & Decimal Fractional Steppers
 * 2. Smart Conditional Weight Calculator (KG, Gram, Ltr, Meter only)
 * 3. Hold Order & Recall System (Clean notifications)
 * 4. Custom Design Modal Confirmations for Clear Cart
 * 5. Full Credit Terms: Partial Paid Amount, Balance Due, & Custom Calendar
 * 6. Dual Customer Checkout (Skip & Print vs Complete & Print)
 * 7. Instant Thermal Receipt Rendering & POS Cart Sync
 */

const CartState = {
    items: [],
    discount: 0,
    taxRate: 0,
    taxLabel: 'Tax',
    paymentMethod: 'cash',
    paidAmount: 0,
    creditDueDate: '',
    activeWeightIndex: -1,
    activeWeightUnit: 'g' // 'g' or 'kg'
};

document.addEventListener('DOMContentLoaded', () => {
    // Default credit due date (+7 days)
    const d = new Date(Date.now() + 7 * 86400000);
    CartState.creditDueDate = d.toISOString().split('T')[0];

    loadCartFromStorage();
    renderCartItems();
    renderSummary();
    checkTaxSettings();
    updateHeldOrdersBadge();
});

function updateHeldOrdersBadge() {
    const badge = document.getElementById('held-orders-badge');
    if (!badge) return;
    try {
        const heldOrders = JSON.parse(localStorage.getItem('pos_held_orders') || '[]');
        if (heldOrders && heldOrders.length > 0) {
            badge.textContent = heldOrders.length;
            badge.style.display = 'grid';
        } else {
            badge.style.display = 'none';
        }
    } catch(e) {
        badge.style.display = 'none';
    }
}

function loadCartFromStorage() {
    try {
        CartState.items = JSON.parse(localStorage.getItem('pos_mobile_cart') || '[]');
    } catch(e) {
        CartState.items = [];
    }
}

function saveCartToStorage() {
    localStorage.setItem('pos_mobile_cart', JSON.stringify(CartState.items));
    renderSummary();
}

/**
 * Render Cart Items List with Smart Conditional Weight Button
 */
function renderCartItems() {
    const container = document.getElementById('cart-items-mount');
    const emptyState = document.getElementById('cart-empty-state');
    const bottomDeck = document.getElementById('cart-bottom-actions-deck');
    const summaryCard = document.getElementById('cart-summary-deck');
    const holdBtn = document.getElementById('btn-hold-cart-header');

    if (!container) return;

    if (!CartState.items || CartState.items.length === 0) {
        container.innerHTML = '';
        if (emptyState) emptyState.style.display = 'flex';
        if (bottomDeck) bottomDeck.style.display = 'none';
        if (summaryCard) summaryCard.style.display = 'none';
        if (holdBtn) holdBtn.style.opacity = '0.5';
        return;
    }

    if (emptyState) emptyState.style.display = 'none';
    if (bottomDeck) bottomDeck.style.display = 'block';
    if (summaryCard) summaryCard.style.display = 'block';
    if (holdBtn) holdBtn.style.opacity = '1';

    const curr = AppState.currency || 'Rs';

    container.innerHTML = CartState.items.map((item, index) => {
        const qty = Number(item.quantity || 1);
        const price = Number(item.price || 0);
        const lineTotal = qty * price;
        const unit = (item.unit_type || item.unit || 'pcs').toLowerCase();
        
        // Smart condition: Only show weight calculator for weight/liquid/measurement units
        const isWeightUnit = ['kg', 'g', 'gram', 'grams', 'ltr', 'liter', 'litre', 'ml', 'meter', 'm', 'weight', 'weighted'].includes(unit) || (qty < 1 && qty > 0) || (qty % 1 !== 0);

        // Format quantity display
        let displayQty = qty;
        let qtySubLabel = '';
        if (qty < 1 && (unit === 'kg' || isWeightUnit)) {
            const grams = Math.round(qty * 1000);
            qtySubLabel = `(${grams}g)`;
        }

        return `
            <div class="cart-item-card" id="cart-item-${index}">
                <div class="cart-item-top-row">
                    <div class="cart-item-avatar">
                        <span class="material-symbols-rounded" style="font-size:20px;">${isWeightUnit ? 'scale' : 'inventory_2'}</span>
                    </div>
                    <div class="cart-item-info">
                        <div class="cart-item-name">${escapeHtml(item.name || 'Product')}</div>
                        <div class="cart-item-sub">
                            <span>${curr} ${price.toLocaleString()} / ${escapeHtml(unit)}</span>
                            ${item.barcode ? ` &bull; <span>${escapeHtml(item.barcode)}</span>` : ''}
                        </div>
                    </div>
                    <button type="button" class="cart-item-del-btn" onclick="confirmRemoveCartItem(${index})" title="Remove Item">
                        <span class="material-symbols-rounded" style="font-size:19px;">delete</span>
                    </button>
                </div>

                <div class="cart-item-bottom-row">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div class="cart-stepper-wrap">
                            <button type="button" class="cart-stepper-btn" onclick="decrementCartItem(${index})">&minus;</button>
                            <input type="number" step="any" min="0.001" class="cart-qty-input" value="${displayQty}" onchange="setCartItemQuantity(${index}, this.value)">
                            <button type="button" class="cart-stepper-btn" onclick="incrementCartItem(${index})">&plus;</button>
                        </div>
                        ${isWeightUnit ? `
                            <button type="button" class="cart-weight-trigger-btn" onclick="openWeightModal(${index})" title="Weight & Unit Calculator">
                                <span class="material-symbols-rounded" style="font-size:13px;">scale</span>
                                <span>${qtySubLabel || 'Weight'}</span>
                            </button>
                        ` : ''}
                    </div>

                    <div class="cart-item-total-tag">
                        ${curr} ${lineTotal.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Stepper Actions
 */
function incrementCartItem(index) {
    if (!CartState.items[index]) return;
    const current = Number(CartState.items[index].quantity || 1);
    const unit = (CartState.items[index].unit_type || '').toLowerCase();
    const step = (unit === 'kg' && current < 1) ? 0.1 : 1;
    CartState.items[index].quantity = Number((current + step).toFixed(3));
    saveCartToStorage();
    renderCartItems();
    triggerHaptic([20]);
}

function decrementCartItem(index) {
    if (!CartState.items[index]) return;
    const current = Number(CartState.items[index].quantity || 1);
    const unit = (CartState.items[index].unit_type || '').toLowerCase();
    const step = (unit === 'kg' && current <= 1) ? 0.1 : 1;
    const next = Number((current - step).toFixed(3));

    if (next <= 0) {
        removeCartItem(index);
    } else {
        CartState.items[index].quantity = next;
        saveCartToStorage();
        renderCartItems();
        triggerHaptic([20]);
    }
}

function setCartItemQuantity(index, val) {
    if (!CartState.items[index]) return;
    const parsed = parseFloat(val);
    if (isNaN(parsed) || parsed <= 0) {
        removeCartItem(index);
    } else {
        CartState.items[index].quantity = Number(parsed.toFixed(3));
        saveCartToStorage();
        renderCartItems();
    }
}

function confirmRemoveCartItem(index) {
    if (!CartState.items[index]) return;
    if (typeof showCustomConfirmDialog === 'function') {
        showCustomConfirmDialog('Delete Item?', 'Are you sure you want to delete?', () => {
            removeCartItem(index);
        });
    } else {
        removeCartItem(index);
    }
}

function removeCartItem(index) {
    if (!CartState.items[index]) return;
    CartState.items.splice(index, 1);
    saveCartToStorage();
    renderCartItems();
    triggerHaptic([30, 40]);
    showAppToast('Item removed from cart', 'info', 1400);
}

/**
 * Clear Cart with Custom Confirmation Modal
 */
function promptClearCart() {
    if (!CartState.items || CartState.items.length === 0) {
        showAppToast('Cart is already empty', 'info', 1400);
        return;
    }
    ensureUniversalModalsMounted();
    openDialog('modal-clear-cart-confirm');
}

function executeCartClearConfirmed() {
    CartState.items = [];
    saveCartToStorage();
    renderCartItems();
    triggerHaptic([40, 50, 40]);
    showAppToast('Cart cleared', 'info', 1400);
}

/**
 * Order Summary & Pricing Calculation
 */
function renderSummary() {
    const subtotalEl = document.getElementById('summary-subtotal');
    const discountEl = document.getElementById('summary-discount');
    const taxEl = document.getElementById('summary-tax');
    const grandTotalEl = document.getElementById('summary-grand-total');

    let subtotal = 0;
    (CartState.items || []).forEach(it => {
        const q = Number(it.quantity || 1);
        const p = Number(it.price || 0);
        subtotal += (q * p);
    });

    const discountAmount = Math.min(subtotal, CartState.discount);
    const taxable = Math.max(0, subtotal - discountAmount);
    const taxAmount = (CartState.taxRate > 0) ? (taxable * CartState.taxRate) / 100 : 0;
    const grandTotal = Math.round(taxable + taxAmount);
    const curr = AppState.currency || 'Rs';

    if (subtotalEl) subtotalEl.textContent = `${curr} ${subtotal.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;
    if (discountEl) discountEl.textContent = `- ${curr} ${discountAmount.toLocaleString()}`;
    if (taxEl) taxEl.textContent = `${curr} ${taxAmount.toLocaleString()}`;
    if (grandTotalEl) grandTotalEl.textContent = `${curr} ${grandTotal.toLocaleString()}`;

    // Update Credit Terms Calculations if active
    updateCreditTermsCalculations(grandTotal);
}

function selectPaymentMethod(method, el) {
    CartState.paymentMethod = method;
    document.querySelectorAll('.cart-payment-pill').forEach(p => p.classList.remove('active'));
    if (el) el.classList.add('active');

    const creditDeck = document.getElementById('cart-credit-terms-deck');
    if (creditDeck) {
        if (method === 'credit') {
            creditDeck.style.display = 'block';
            updateCreditTermsCalculations();
        } else {
            creditDeck.style.display = 'none';
        }
    }
    triggerHaptic([20]);
}

function updateCreditTermsCalculations(forcedGrandTotal = null) {
    let grandTotal = forcedGrandTotal;
    if (grandTotal === null) {
        let subtotal = 0;
        (CartState.items || []).forEach(it => { subtotal += (Number(it.quantity || 1) * Number(it.price || 0)); });
        const discountAmount = Math.min(subtotal, CartState.discount);
        const taxable = Math.max(0, subtotal - discountAmount);
        const taxAmount = (CartState.taxRate > 0) ? (taxable * CartState.taxRate) / 100 : 0;
        grandTotal = Math.round(taxable + taxAmount);
    }

    const paidInput = document.getElementById('credit-partial-paid-input');
    const balanceDueEl = document.getElementById('credit-balance-due-val');
    const dueDateDisplay = document.getElementById('credit-due-date-display');
    const curr = AppState.currency || 'Rs';

    const paidVal = parseFloat(paidInput?.value || '0') || 0;
    CartState.paidAmount = paidVal;
    const balanceDue = Math.max(0, grandTotal - paidVal);

    if (balanceDueEl) {
        balanceDueEl.textContent = `${curr} ${balanceDue.toLocaleString()}`;
    }

    if (dueDateDisplay && CartState.creditDueDate) {
        const d = new Date(CartState.creditDueDate);
        dueDateDisplay.textContent = d.toLocaleDateString([], { day: '2-digit', month: 'short', year: 'numeric' });
    }
}

function onCreditPaidInput(val) {
    updateCreditTermsCalculations();
}

function onCreditDueDateSelected(isoDate) {
    CartState.creditDueDate = isoDate;
    const dueDateDisplay = document.getElementById('credit-due-date-display');
    if (dueDateDisplay) {
        const d = new Date(isoDate);
        dueDateDisplay.textContent = d.toLocaleDateString([], { day: '2-digit', month: 'short', year: 'numeric' });
    }
    showAppToast(`Due Date: ${isoDate}`, 'info', 1400);
}

function checkTaxSettings() {
    try {
        const savedTax = localStorage.getItem('pos_tax_rate');
        if (savedTax) CartState.taxRate = parseFloat(savedTax) || 0;
    } catch(e) {}
}

/**
 * =========================================================
 * WEIGHT / FRACTIONAL QUANTITY CALCULATOR MODAL
 * =========================================================
 */
function openWeightModal(index) {
    const item = CartState.items[index];
    if (!item) return;

    CartState.activeWeightIndex = index;
    const titleEl = document.getElementById('weight-modal-item-title');
    const rateEl = document.getElementById('weight-modal-base-rate');
    const inputEl = document.getElementById('weight-modal-input');

    const curr = AppState.currency || 'Rs';
    if (titleEl) titleEl.textContent = item.name || 'Weighted Product';
    if (rateEl) rateEl.textContent = `Base Rate: ${curr} ${Number(item.price || 0).toLocaleString()} per KG`;

    // Detect if current quantity is in grams (< 1)
    const currentQty = Number(item.quantity || 1);
    if (currentQty < 1) {
        CartState.activeWeightUnit = 'g';
        setWeightTab('g');
        if (inputEl) inputEl.value = Math.round(currentQty * 1000);
    } else {
        CartState.activeWeightUnit = 'kg';
        setWeightTab('kg');
        if (inputEl) inputEl.value = currentQty;
    }

    recalcWeightFormula();
    ensureUniversalModalsMounted();
    openDialog('modal-weight-calculator');
}

function setWeightTab(unit) {
    CartState.activeWeightUnit = unit;
    const tabG = document.getElementById('tab-weight-g');
    const tabKg = document.getElementById('tab-weight-kg');
    const unitLabel = document.getElementById('weight-input-unit-label');

    if (tabG) tabG.classList.toggle('active', unit === 'g');
    if (tabKg) tabKg.classList.toggle('active', unit === 'kg');
    if (unitLabel) unitLabel.textContent = unit.toUpperCase();

    recalcWeightFormula();
    triggerHaptic([15]);
}

function recalcWeightFormula() {
    const inputVal = parseFloat(document.getElementById('weight-modal-input')?.value || '0');
    const priceDisplay = document.getElementById('weight-result-price');
    const formulaDisplay = document.getElementById('weight-result-formula');
    const item = CartState.items[CartState.activeWeightIndex];

    if (!item || isNaN(inputVal) || inputVal <= 0) {
        if (priceDisplay) priceDisplay.textContent = `${AppState.currency} 0`;
        if (formulaDisplay) formulaDisplay.textContent = 'Enter weight above to calculate';
        return;
    }

    const basePrice = Number(item.price || 0);
    let decimalQty = 0;

    if (CartState.activeWeightUnit === 'g') {
        decimalQty = inputVal / 1000;
        const linePrice = decimalQty * basePrice;
        if (priceDisplay) priceDisplay.textContent = `${AppState.currency} ${linePrice.toFixed(2)}`;
        if (formulaDisplay) formulaDisplay.textContent = `${inputVal}g ÷ 1000 = ${decimalQty.toFixed(3)} kg × ${basePrice} = ${AppState.currency} ${linePrice.toFixed(2)}`;
    } else {
        decimalQty = inputVal;
        const linePrice = decimalQty * basePrice;
        if (priceDisplay) priceDisplay.textContent = `${AppState.currency} ${linePrice.toFixed(2)}`;
        if (formulaDisplay) formulaDisplay.textContent = `${decimalQty} kg × ${basePrice} = ${AppState.currency} ${linePrice.toFixed(2)}`;
    }
}

function applyWeightCalculator() {
    const inputVal = parseFloat(document.getElementById('weight-modal-input')?.value || '0');
    const item = CartState.items[CartState.activeWeightIndex];

    if (!item || isNaN(inputVal) || inputVal <= 0) {
        showAppToast('Please enter a valid weight.', 'warning');
        return;
    }

    let finalQty = (CartState.activeWeightUnit === 'g') ? (inputVal / 1000) : inputVal;
    item.quantity = Number(finalQty.toFixed(3));
    item.unit_type = 'kg';

    saveCartToStorage();
    renderCartItems();
    closeDialog('modal-weight-calculator');
    triggerHaptic([30, 40]);
    showAppToast(`Weight applied: ${finalQty} kg`, 'success');
}

/**
 * =========================================================
 * HOLD ORDER & RECALL SYSTEM (Clean notifications)
 * =========================================================
 */
function holdCurrentOrder() {
    if (!CartState.items || CartState.items.length === 0) {
        showAppToast('Cart is empty, nothing to hold.', 'warning');
        return;
    }

    let heldOrders = JSON.parse(localStorage.getItem('pos_held_orders') || '[]');
    const holdId = Date.now();
    const holdName = `Hold #${heldOrders.length + 1} (${CartState.items.length} items)`;

    heldOrders.push({
        id: holdId,
        title: holdName,
        items: [...CartState.items],
        created_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        date_str: new Date().toLocaleDateString()
    });

    localStorage.setItem('pos_held_orders', JSON.stringify(heldOrders));

    // Clear current active cart
    CartState.items = [];
    saveCartToStorage();
    renderCartItems();
    updateHeldOrdersBadge();

    triggerHaptic([40, 60, 40]);
    showAppToast('Order placed on hold', 'success', 1600);
}

function openHeldOrdersModal() {
    const heldOrders = JSON.parse(localStorage.getItem('pos_held_orders') || '[]');
    const mount = document.getElementById('held-orders-list-mount');
    const emptyMsg = document.getElementById('held-orders-empty');

    if (!mount) return;

    if (!heldOrders || heldOrders.length === 0) {
        mount.innerHTML = '';
        if (emptyMsg) emptyMsg.style.display = 'block';
    } else {
        if (emptyMsg) emptyMsg.style.display = 'none';
        const curr = AppState.currency || 'Rs';

        mount.innerHTML = heldOrders.map((h, index) => {
            let total = 0;
            (h.items || []).forEach(it => { total += (Number(it.quantity || 1) * Number(it.price || 0)); });

            return `
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:12px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-size:13.5px; font-weight:700; color:#0f172a;">${escapeHtml(h.title)}</div>
                        <div style="font-size:11px; color:#64748b; margin-top:2px;">Time: ${escapeHtml(h.created_at)} &bull; <strong>${curr} ${total.toLocaleString()}</strong></div>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <button type="button" class="btn-primary-action-block" style="padding:7px 14px; font-size:12px; font-weight:700; border-radius:10px; width:auto; margin:0;" onclick="resumeHeldOrder(${index})">
                            Recall
                        </button>
                        <button type="button" onclick="confirmDeleteHeldOrder(${index})" title="Delete Held Order" style="background:transparent; border:none; outline:none; color:#ef4444; width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0; cursor:pointer; transition:background 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'">
                            <span class="material-symbols-rounded" style="font-size:20px;">delete</span>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    ensureUniversalModalsMounted();
    openDialog('modal-held-orders');
}

function resumeHeldOrder(index) {
    let heldOrders = JSON.parse(localStorage.getItem('pos_held_orders') || '[]');
    const selected = heldOrders[index];
    if (!selected) return;

    CartState.items = [...(selected.items || [])];
    heldOrders.splice(index, 1);
    localStorage.setItem('pos_held_orders', JSON.stringify(heldOrders));

    saveCartToStorage();
    renderCartItems();
    updateHeldOrdersBadge();
    closeDialog('modal-held-orders');
    triggerHaptic([30, 50]);
    showAppToast('Held order restored to active cart', 'success', 1600);
}

function confirmDeleteHeldOrder(index) {
    if (typeof showCustomConfirmDialog === 'function') {
        showCustomConfirmDialog('Delete Order?', 'Are you sure you want to delete?', () => {
            deleteHeldOrder(index);
        });
    } else {
        deleteHeldOrder(index);
    }
}

function deleteHeldOrder(index) {
    let heldOrders = JSON.parse(localStorage.getItem('pos_held_orders') || '[]');
    heldOrders.splice(index, 1);
    localStorage.setItem('pos_held_orders', JSON.stringify(heldOrders));
    updateHeldOrdersBadge();
    openHeldOrdersModal();
    showAppToast('Held order deleted', 'info', 1400);
}

/**
 * =========================================================
 * CUSTOMER DETAILS MODAL & DUAL CHECKOUT PRINT ACTIONS
 * =========================================================
 */
function openCustomerCheckoutSheet() {
    if (!CartState.items || CartState.items.length === 0) {
        showAppToast('Please add items to cart before checkout.', 'warning');
        return;
    }

    // Reset customer fields
    const nameInput = document.getElementById('checkout-cust-name');
    const phoneInput = document.getElementById('checkout-cust-phone');
    if (nameInput) nameInput.value = '';
    if (phoneInput) phoneInput.value = '';

    ensureUniversalModalsMounted();
    openDialog('modal-customer-checkout-sheet');
}

async function executeCheckout(isSkipCustomer = false) {
    if (!CartState.items || CartState.items.length === 0) {
        showAppToast('Cart is empty.', 'error');
        return;
    }

    let custName = 'Walk-in Customer';
    let custPhone = '';
    let custAddress = '';

    if (!isSkipCustomer) {
        const nameVal = (document.getElementById('checkout-cust-name')?.value || '').trim();
        const phoneVal = (document.getElementById('checkout-cust-phone')?.value || '').trim();
        if (nameVal) custName = nameVal;
        if (phoneVal) custPhone = phoneVal;
    }

    closeDialog('modal-customer-checkout-sheet');

    const btn = document.getElementById('btn-main-checkout');
    if (btn) btn.disabled = true;

    showAppToast('Processing sale & generating invoice...', 'info', 1800);

    try {
        const response = await apiFetch('/scanner/checkout', {
            method: 'POST',
            body: JSON.stringify({
                code: AppState.pairCode,
                items: CartState.items,
                customer_name: custName,
                customer_phone: custPhone,
                customer_address: custAddress,
                discount: CartState.discount,
                tax_rate: CartState.taxRate,
                tax_label: CartState.taxLabel,
                payment_method: CartState.paymentMethod,
                paid_amount: CartState.paidAmount,
                credit_due_date: CartState.creditDueDate
            })
        });

        if (response && response.success) {
            playSuccessAudio();
            triggerHaptic([40, 80, 40]);

            // Clear active cart & storage
            CartState.items = [];
            CartState.paidAmount = 0;
            saveCartToStorage();
            renderCartItems();

            // Display Receipt Modal / Screen
            const invoiceId = response.invoice_id;
            const invoiceNo = response.invoice_number || ('INV-' + invoiceId);
            showCheckoutSuccessReceiptModal(invoiceId, invoiceNo, response);
        } else {
            playErrorAudio();
            triggerHaptic([50, 50, 50]);
            showAppToast(response.message || 'Checkout failed', 'error');
        }
    } catch(err) {
        playErrorAudio();
        showAppToast('Checkout failed: ' + err.message, 'error');
    } finally {
        if (btn) btn.disabled = false;
    }
}

/**
 * Show Luxury Sale Success & Print Receipt Modal
 */
function showCheckoutSuccessReceiptModal(invoiceId, invoiceNo, data) {
    const baseUrl = (AppState.serverUrl || getBaseServerUrl()).replace(/\/+$/, '');
    const receiptUrl = `${baseUrl}/scanner/receipt_html?code=${encodeURIComponent(AppState.pairCode)}&id=${invoiceId}`;

    const modal = document.getElementById('modal-sale-complete-receipt');
    const frame = document.getElementById('complete-receipt-frame');
    const invNoEl = document.getElementById('receipt-modal-inv-no');
    const grandTotalEl = document.getElementById('receipt-modal-total');

    if (invNoEl) invNoEl.textContent = invoiceNo;
    if (grandTotalEl) grandTotalEl.textContent = data.formatted_total || `${AppState.currency} ${data.grand_total}`;

    if (typeof isGuestSession === 'function' && isGuestSession()) {
        const guestHtml = (typeof generateGuestThermalReceiptHtml === 'function') 
            ? generateGuestThermalReceiptHtml(invoiceId, invoiceNo, data)
            : '';
        if (frame) {
            frame.removeAttribute('src');
            frame.srcdoc = guestHtml;
        }
    } else {
        const baseUrl = (AppState.serverUrl || getBaseServerUrl()).replace(/\/+$/, '');
        const receiptUrl = `${baseUrl}/scanner/receipt_html?code=${encodeURIComponent(AppState.pairCode)}&id=${invoiceId}`;
        if (frame) {
            frame.removeAttribute('srcdoc');
            frame.src = receiptUrl;
        }
    }

    ensureUniversalModalsMounted();
    openDialog('modal-sale-complete-receipt');
}

function printReceiptFromModal() {
    const frame = document.getElementById('complete-receipt-frame');
    if (frame && frame.contentWindow) {
        frame.contentWindow.focus();
        frame.contentWindow.print();
    } else {
        window.print();
    }
}

function shareReceiptOnWhatsApp() {
    const invNo = document.getElementById('receipt-modal-inv-no')?.textContent || 'INV';
    const total = document.getElementById('receipt-modal-total')?.textContent || 'Rs 0';
    const store = AppState.storeName || 'DevAltum Store';
    const text = encodeURIComponent(`*${store}* - Sale Receipt\nInvoice #: ${invNo}\nGrand Total: ${total}\nThank you for shopping with us!`);
    window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank');
}

function startNewSaleFromCart() {
    closeDialog('modal-sale-complete-receipt');
    navigateTo('scanner');
}
