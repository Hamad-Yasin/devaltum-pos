/**
 * =========================================================
 * DevAltum POS - Next-Gen Immersive AR Universal Camera Scanner
 * =========================================================
 * 1. 100% Full-Screen Edge-to-Edge HD Camera Viewport (No Static Box)
 * 2. Ultra-Fast Zero-Latency 60 FPS Real-Time Decoding (GPU + jsQR + ZXing)
 * 3. Robust Camera Lifecycle (Zero Blackouts, Auto-Reconnect on Resume)
 * 4. Dynamic AR Bounding Box & Target Locking Tracker
 * 5. Instant Slide-Up Interactive Result Bottom-Sheet & Zero-Lag Audio/Haptics
 */

let isCameraRunning = false;
let cameraAnimationId = null;
let currentScanContext = 'checkout'; // 'checkout', 'add_product', or 'pair'
let isScanThrottled = false;
let isTorchOn = false;
let activeVideoTrack = null;
let currentZoom = 1;
let cameraWatchdogTimer = null;

// Offscreen Analysis Canvas & AR Overlay Canvas
let offscreenCanvas = null;
let offscreenCtx = null;
let arCanvas = null;
let arCtx = null;

// Native BarcodeDetector & ZXing Instances
let nativeBarcodeDetector = null;
let zxingMultiReader = null;

// Smooth AR Target Interpolation State
let arTarget = {
    active: false,
    x: 0, y: 0, w: 0, h: 0,
    targetX: 0, targetY: 0, targetW: 0, targetH: 0,
    alpha: 0,
    pulseTime: 0
};

/**
 * Initialize Ultra HD Camera Stream & Start Real-Time AR Scanner
 */
async function startCameraEngine() {
    stopCameraEngine();

    const video = document.getElementById('camera-live-feed');
    arCanvas = document.getElementById('ar-tracking-overlay');
    if (!video || !arCanvas) return;

    // Ensure robust video element attributes to prevent mobile blackouts
    video.muted = true;
    video.playsInline = true;
    video.autoplay = true;
    video.setAttribute('playsinline', 'true');
    video.setAttribute('webkit-playsinline', 'true');
    video.setAttribute('autoplay', 'true');
    video.setAttribute('muted', 'true');

    arCtx = arCanvas.getContext('2d');

    // Create Lightweight Fast Offscreen Processing Canvas (640x480 max for ultra speed)
    if (!offscreenCanvas) {
        offscreenCanvas = document.createElement('canvas');
        offscreenCtx = offscreenCanvas.getContext('2d', { willReadFrequently: true });
    }

    // Set Context from URL Query
    const urlParams = new URLSearchParams(window.location.search);
    const modeParam = urlParams.get('mode');
    const hudTitle = document.getElementById('scanner-hud-title');

    if (modeParam === 'pair') {
        currentScanContext = 'pair';
        if (hudTitle) hudTitle.textContent = 'Pairing Store QR';
    } else if (modeParam === 'add_product') {
        currentScanContext = 'add_product';
        if (hudTitle) hudTitle.textContent = 'Register Product';
    } else {
        currentScanContext = 'checkout';
        if (hudTitle) hudTitle.textContent = 'Live 60 FPS HD';
        updateFloatingCartBadge();
    }

    // Initialize Decoders
    await initEngines();

    // Request Hardware Camera Stream
    const facing = (typeof AppState !== 'undefined' && AppState.facingMode) ? AppState.facingMode : 'environment';

    try {
        const constraints = {
            audio: false,
            video: {
                facingMode: { ideal: facing },
                width: { ideal: 1280, min: 640 },
                height: { ideal: 720, min: 480 },
                focusMode: { ideal: 'continuous' },
                exposureMode: { ideal: 'continuous' }
            }
        };

        let stream;
        try {
            stream = await navigator.mediaDevices.getUserMedia(constraints);
        } catch(e) {
            // Fallback constraint
            stream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: { facingMode: facing }
            });
        }

        if (typeof AppState !== 'undefined') AppState.videoStream = stream;
        activeVideoTrack = stream.getVideoTracks()[0];

        video.srcObject = stream;

        // Ensure video is playing cleanly upon metadata ready
        await new Promise((resolve) => {
            if (video.readyState >= 2) {
                resolve();
            } else {
                video.onloadedmetadata = () => resolve();
                setTimeout(resolve, 800); // Safety timeout
            }
        });

        try {
            await video.play();
        } catch(playErr) {
            console.warn('Autoplay error handled:', playErr);
        }

        isCameraRunning = true;

        // Check Zoom Capability
        initZoomCapabilities();

        // Start High-Frequency AR Analysis Loop
        startARProcessingLoop(video);

        // Blackout Watchdog: If camera shows 0 width after 3s, soft auto-reconnect
        if (cameraWatchdogTimer) clearTimeout(cameraWatchdogTimer);
        cameraWatchdogTimer = setTimeout(() => {
            if (isCameraRunning && video.videoWidth === 0) {
                console.warn('Camera feed stalled, soft-reconnecting stream...');
                startCameraEngine();
            }
        }, 3000);

    } catch(err) {
        console.error('Camera activation error:', err);
        showAppToast('Camera access denied or unavailable.', 'error', 4000);
    }
}

/**
 * Initialize Native & JS Multi-Format Decoders
 */
async function initEngines() {
    // 1. Native Hardware BarcodeDetector (GPU zero-copy)
    if ('BarcodeDetector' in window && !nativeBarcodeDetector) {
        try {
            const formats = await BarcodeDetector.getSupportedFormats();
            nativeBarcodeDetector = new BarcodeDetector({ formats: formats });
        } catch(e) {
            nativeBarcodeDetector = null;
        }
    }

    // 2. ZXing Reader
    if (typeof ZXing !== 'undefined' && !zxingMultiReader) {
        try {
            const hints = new Map();
            hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
            zxingMultiReader = new ZXing.BrowserMultiFormatReader(hints);
        } catch(e) {}
    }
}

/**
 * Main AR Video Analysis & Real-Time Target Tracking Loop (Ultra-Fast 60 FPS)
 */
function startARProcessingLoop(video) {
    let frameIndex = 0;
    let scanLaserY = 0;
    let laserDirection = 1;

    const renderLoop = async () => {
        if (!isCameraRunning || !video) return;

        // Resize AR Canvas to Match Screen Viewport
        const screenW = video.clientWidth || window.innerWidth;
        const screenH = video.clientHeight || window.innerHeight;

        if (arCanvas.width !== screenW || arCanvas.height !== screenH) {
            arCanvas.width = screenW;
            arCanvas.height = screenH;
        }

        arCtx.clearRect(0, 0, screenW, screenH);

        // Analyze Video Frame if Video Ready
        if (video.readyState >= 2 && !isScanThrottled) {
            frameIndex++;

            const vidW = video.videoWidth || 1280;
            const vidH = video.videoHeight || 720;

            let detectedCode = null;
            let detectedBox = null;

            // ── Pass 1: Hardware GPU BarcodeDetector (Direct zero-copy video detection, < 2ms) ──
            if (nativeBarcodeDetector) {
                try {
                    const barcodes = await nativeBarcodeDetector.detect(video);
                    if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
                        detectedCode = barcodes[0].rawValue;
                        const box = barcodes[0].boundingBox;
                        if (box) {
                            detectedBox = { x: box.x, y: box.y, w: box.width, h: box.height };
                        }
                    }
                } catch(e) {}
            }

            // ── Pass 2: jsQR High-Speed Matrix Engine (Optimized 640px lightweight canvas, < 3ms) ──
            if (!detectedCode && typeof jsQR !== 'undefined') {
                const targetW = 640;
                const targetH = Math.round((targetW / vidW) * vidH);

                if (offscreenCanvas.width !== targetW || offscreenCanvas.height !== targetH) {
                    offscreenCanvas.width = targetW;
                    offscreenCanvas.height = targetH;
                }

                offscreenCtx.drawImage(video, 0, 0, targetW, targetH);

                try {
                    const imgData = offscreenCtx.getImageData(0, 0, targetW, targetH);
                    const qr = jsQR(imgData.data, targetW, targetH, { inversionAttempts: "attemptBoth" });
                    if (qr && qr.data) {
                        detectedCode = qr.data;
                        const loc = qr.location;
                        const scaleBack = vidW / targetW;
                        const minX = Math.min(loc.topLeftCorner.x, loc.bottomLeftCorner.x) * scaleBack;
                        const maxX = Math.max(loc.topRightCorner.x, loc.bottomRightCorner.x) * scaleBack;
                        const minY = Math.min(loc.topLeftCorner.y, loc.topRightCorner.y) * scaleBack;
                        const maxY = Math.max(loc.bottomLeftCorner.y, loc.bottomRightCorner.y) * scaleBack;
                        detectedBox = {
                            x: minX, y: minY,
                            w: maxX - minX, h: maxY - minY
                        };
                    }
                } catch(e) {}
            }

            // ── Pass 3: ZXing Multi-Format Engine (For 1D retail barcodes: EAN-13, Code-128) ──
            if (!detectedCode && zxingMultiReader && frameIndex % 2 === 0) {
                try {
                    const res = zxingMultiReader.decode(offscreenCanvas);
                    if (res && res.getText()) {
                        detectedCode = res.getText();
                    }
                } catch(e) {}
            }

            // ── Update AR Bounding Box Target ──
            if (detectedCode) {
                if (detectedBox) {
                    const scaleX = screenW / vidW;
                    const scaleY = screenH / vidH;
                    const scale = Math.max(scaleX, scaleY);
                    const offsetX = (screenW - vidW * scale) / 2;
                    const offsetY = (screenH - vidH * scale) / 2;

                    const pad = 20;
                    arTarget.targetX = (detectedBox.x * scale) + offsetX - pad;
                    arTarget.targetY = (detectedBox.y * scale) + offsetY - pad;
                    arTarget.targetW = (detectedBox.w * scale) + (pad * 2);
                    arTarget.targetH = (detectedBox.h * scale) + (pad * 2);
                } else {
                    // Center snap box if no coordinates available
                    const centerSize = Math.min(screenW, screenH) * 0.55;
                    arTarget.targetX = (screenW - centerSize) / 2;
                    arTarget.targetY = (screenH - centerSize) / 2;
                    arTarget.targetW = centerSize;
                    arTarget.targetH = centerSize;
                }

                arTarget.active = true;
                arTarget.alpha = 1;

                // Dispatch Scan Event
                handleSuccessfulScan(detectedCode);

            } else if (!arTarget.active) {
                arTarget.alpha = Math.max(0, arTarget.alpha - 0.06);
            }

            // Low-Light Ambient Check every 60 frames
            if (frameIndex % 60 === 0) {
                checkAmbientLight(offscreenCtx, offscreenCanvas.width, offscreenCanvas.height);
            }
        }

        // ── Render Dynamic AR Target Frame ──
        if (arTarget.alpha > 0.02) {
            // Smooth Fast Interpolation (Lerp)
            arTarget.x += (arTarget.targetX - arTarget.x) * 0.45;
            arTarget.y += (arTarget.targetY - arTarget.y) * 0.45;
            arTarget.w += (arTarget.targetW - arTarget.w) * 0.45;
            arTarget.h += (arTarget.targetH - arTarget.h) * 0.45;

            drawARNeonFrame(arCtx, arTarget.x, arTarget.y, arTarget.w, arTarget.h, arTarget.alpha);
        } else {
            // Ambient Laser Radar Beam
            scanLaserY += 4.5 * laserDirection;
            if (scanLaserY > screenH * 0.78) laserDirection = -1;
            if (scanLaserY < screenH * 0.22) laserDirection = 1;

            drawAmbientRadarBeam(arCtx, screenW, scanLaserY);
        }

        cameraAnimationId = requestAnimationFrame(renderLoop);
    };

    cameraAnimationId = requestAnimationFrame(renderLoop);
}

/**
 * Draw Dynamic Glowing Neon Corner Brackets (AR Tracking Frame)
 */
function drawARNeonFrame(ctx, x, y, w, h, alpha) {
    if (w < 40 || h < 40) return;

    ctx.save();
    ctx.globalAlpha = alpha;

    // Glowing Fill
    ctx.fillStyle = 'rgba(16, 185, 129, 0.14)';
    ctx.beginPath();
    ctx.roundRect(x, y, w, h, 18);
    ctx.fill();

    // Neon Glow Border
    ctx.shadowColor = '#10b981';
    ctx.shadowBlur = 20;
    ctx.strokeStyle = '#34d399';
    ctx.lineWidth = 3.5;

    const cornerLen = Math.min(32, w * 0.25);
    const r = 14;

    // Top-Left Corner
    ctx.beginPath();
    ctx.moveTo(x, y + cornerLen);
    ctx.lineTo(x, y + r);
    ctx.arcTo(x, y, x + r, y, r);
    ctx.lineTo(x + cornerLen, y);
    ctx.stroke();

    // Top-Right Corner
    ctx.beginPath();
    ctx.moveTo(x + w - cornerLen, y);
    ctx.lineTo(x + w - r, y);
    ctx.arcTo(x + w, y, x + w, y + r, r);
    ctx.lineTo(x + w, y + cornerLen);
    ctx.stroke();

    // Bottom-Right Corner
    ctx.beginPath();
    ctx.moveTo(x + w, y + h - cornerLen);
    ctx.lineTo(x + w, y + h - r);
    ctx.arcTo(x + w, y + h, x + w - r, y + h, r);
    ctx.lineTo(x + w - cornerLen, y + h);
    ctx.stroke();

    // Bottom-Left Corner
    ctx.beginPath();
    ctx.moveTo(x + cornerLen, y + h);
    ctx.lineTo(x + r, y + h);
    ctx.arcTo(x, y + h, x, y + h - r, r);
    ctx.lineTo(x, y + h - cornerLen);
    ctx.stroke();

    ctx.restore();
}

/**
 * Ambient Laser Radar Scanning Beam
 */
function drawAmbientRadarBeam(ctx, width, y) {
    ctx.save();
    const grad = ctx.createLinearGradient(0, y - 2, 0, y + 2);
    grad.addColorStop(0, 'rgba(14, 110, 255, 0)');
    grad.addColorStop(0.5, 'rgba(14, 110, 255, 0.85)');
    grad.addColorStop(1, 'rgba(14, 110, 255, 0)');

    ctx.shadowColor = '#0e6eff';
    ctx.shadowBlur = 14;
    ctx.fillStyle = grad;
    ctx.fillRect(width * 0.15, y - 1.5, width * 0.7, 3);
    ctx.restore();
}

/**
 * Check Ambient Lighting for Low-Light Hint
 */
function checkAmbientLight(ctx, w, h) {
    try {
        const sampleSize = 48;
        const img = ctx.getImageData((w - sampleSize) / 2, (h - sampleSize) / 2, sampleSize, sampleSize);
        let total = 0;
        for (let i = 0; i < img.data.length; i += 4) {
            total += (img.data[i] * 0.299 + img.data[i + 1] * 0.587 + img.data[i + 2] * 0.114);
        }
        const avg = total / (sampleSize * sampleSize);
        const hintEl = document.getElementById('ambient-torch-hint');
        if (hintEl) {
            hintEl.style.display = (avg < 25 && !isTorchOn) ? 'inline-flex' : 'none';
        }
    } catch(e) {}
}

/**
 * Handle Successful Barcode/QR Code Detection
 */
async function handleSuccessfulScan(rawCode) {
    const code = String(rawCode || '').trim();
    if (!code) return;

    // Debounce duplicate scans
    const now = Date.now();
    if (typeof AppState !== 'undefined') {
        if (code === AppState.lastScannedBarcode && (now - AppState.lastScanTime) < 1400) {
            return;
        }
        AppState.lastScannedBarcode = code;
        AppState.lastScanTime = now;
    }

    isScanThrottled = true;
    setTimeout(() => {
        isScanThrottled = false;
        arTarget.active = false;
    }, 1200);

    playProductSound();
    triggerHaptic();

    // 1. Store Pairing PIN Mode
    if (currentScanContext === 'pair' || (code.startsWith('{') && code.endsWith('}')) || code.includes('code=')) {
        let pin = '';
        if (code.startsWith('{') && code.endsWith('}')) {
            try {
                const parsed = JSON.parse(code);
                if (parsed.code) pin = String(parsed.code);
            } catch(e) {}
        } else if (code.includes('code=')) {
            const m = code.match(/code=([0-9]{6})/);
            if (m) pin = m[1];
        } else if (/^[0-9]{6}$/.test(code)) {
            pin = code;
        }

        if (pin && pin.length === 6) {
            playProductSound();
            triggerHaptic([40, 60, 40]);
            stopCameraEngine();
            showAppToast(`PIN: ${pin}`, 'success', 2200);
            if (typeof connectWithPin === 'function') {
                connectWithPin(pin);
            }
            return;
        }
    }

    // 2. Add Product Registration Mode (scanner.html?mode=add_product)
    if (currentScanContext === 'add_product') {
        // Pre-check database if this barcode already exists in inventory
        try {
            const checkRes = await apiFetch('/scanner/scan', {
                method: 'POST',
                body: JSON.stringify({
                    code: (typeof AppState !== 'undefined' ? AppState.pairCode : localStorage.getItem('pos_scanner_code')),
                    barcode: code,
                    scan_context: 'check_only',
                    device_name: 'Mobile Scanner App'
                })
            });

            if (checkRes && checkRes.success && checkRes.found && checkRes.product) {
                // Barcode ALREADY EXISTS in inventory!
                playErrorAudio();
                triggerHaptic([50, 50, 50]);
                showAppToast('Already Exists', 'warning', 2500);
                return;
            }
        } catch(checkErr) {
            // Unlisted or offline, continue to registration bottom sheet
        }

        // Barcode is New / Unregistered -> Show Bottom Sheet with Register Product Action
        playProductSound();
        triggerHaptic([30, 40, 30]);
        showBottomSheetResult({
            title: 'New Barcode Scanned',
            code: `Barcode: ${code}`,
            price: 'Unregistered Item',
            stock: 'Ready to Register',
            actionText: 'Register as New Product',
            onAction: () => {
                stopCameraEngine();
                navigateTo('add-product', `barcode=${encodeURIComponent(code)}`);
            }
        });
        return;
    }

    // 3. Web URL / General QR Code
    if (code.startsWith('http://') || code.startsWith('https://') || code.startsWith('www.')) {
        playProductSound();
        triggerHaptic([30, 40, 30]);
        showBottomSheetResult({
            title: 'Web Link Detected',
            code: code,
            price: 'URL',
            stock: 'External',
            actionText: 'Open Link',
            onAction: () => {
                window.open(code, '_blank');
            }
        });
        return;
    }

    // 4. POS Checkout Mode (Standard scanner.html - NO Bottom Sheet, Direct Fast Cart Addition)
    try {
        const response = await apiFetch('/scanner/scan', {
            method: 'POST',
            body: JSON.stringify({
                code: (typeof AppState !== 'undefined' ? AppState.pairCode : localStorage.getItem('pos_scanner_code')),
                barcode: code,
                scan_context: 'checkout',
                device_name: 'Mobile Scanner App'
            })
        });

        if (response && response.success && response.found && response.product) {
            const p = response.product;
            playProductSound();
            triggerHaptic([30, 45, 30]);
            
            // Add product to Mobile Cart & Update Floating Pill
            addItemToMobileCart(p);

            // Direct item addition toast
            showAppToast(`Added: ${p.name}`, 'success', 1800);
        } else {
            playErrorAudio();
            triggerHaptic([50, 50, 50]);
            showAppToast('Not Found', 'error', 2500);
        }
    } catch(err) {
        playErrorAudio();
        triggerHaptic([50, 50, 50]);
        showAppToast('Not Found', 'error', 2500);
    }
}

function updateFloatingCartBadge() {
    const pill = document.getElementById('scanner-floating-cart-pill');
    if (!pill) return;

    if (currentScanContext !== 'checkout') {
        pill.classList.remove('visible');
        return;
    }

    const cart = JSON.parse(localStorage.getItem('pos_mobile_cart') || '[]');
    if (!cart || cart.length === 0) {
        pill.classList.remove('visible');
        return;
    }

    let totalQty = 0;
    let totalPrice = 0;

    cart.forEach(item => {
        const q = Number(item.quantity || 1);
        const p = Number(item.price || 0);
        totalQty += (q >= 1 ? q : 1);
        totalPrice += (q * p);
    });

    const countEl = document.getElementById('floating-cart-count');
    const titleEl = document.getElementById('floating-cart-title');
    const totalEl = document.getElementById('floating-cart-total');

    if (countEl) countEl.textContent = cart.length;
    if (titleEl) titleEl.textContent = `${cart.length} Item${cart.length > 1 ? 's' : ''} in Cart`;
    if (totalEl) totalEl.textContent = `${AppState.currency} ${Math.round(totalPrice).toLocaleString()}`;

    pill.classList.add('visible');
}

function addItemToMobileCart(p) {
    if (!p) return;
    let cart = JSON.parse(localStorage.getItem('pos_mobile_cart') || '[]');
    const existingIndex = cart.findIndex(item => (p.id && String(item.id) === String(p.id)) || (p.barcode && String(item.barcode) === String(p.barcode)));

    if (existingIndex !== -1) {
        cart[existingIndex].quantity = (Number(cart[existingIndex].quantity) || 1) + 1;
    } else {
        cart.push({
            id: p.id,
            name: p.name || 'Product',
            barcode: p.barcode || '',
            sku: p.model_number || p.sku || '',
            price: Number(p.price || 0),
            cost: Number(p.cost || 0),
            quantity: 1,
            unit_type: p.unit || p.unit_type || 'pcs'
        });
    }

    localStorage.setItem('pos_mobile_cart', JSON.stringify(cart));
    updateFloatingCartBadge();
}

/**
 * Slide-Up Interactive Result Bottom Sheet
 */
function showBottomSheetResult(data) {
    const sheet = document.getElementById('scanner-bottom-sheet');
    const titleEl = document.getElementById('sheet-res-title');
    const codeEl = document.getElementById('sheet-res-code');
    const priceEl = document.getElementById('sheet-res-price');
    const stockEl = document.getElementById('sheet-res-stock');
    const actionContainer = document.getElementById('sheet-actions-container');

    if (!sheet) return;

    if (titleEl) titleEl.textContent = data.title || 'Scanned Code';
    if (codeEl) codeEl.textContent = data.code || '';
    if (priceEl) priceEl.textContent = data.price || '';
    if (stockEl) stockEl.textContent = data.stock || '';

    if (actionContainer) {
        actionContainer.innerHTML = `
            <button type="button" class="btn-primary-action-block" id="btn-sheet-primary" style="flex:1; padding:12px; font-size:13px; margin:0;">
                <span>${data.actionText || 'Continue'}</span>
            </button>
            <button type="button" class="btn-neutral-outline" style="width:48px; padding:0; display:grid; place-items:center; border-radius:14px; margin:0;" onclick="dismissBottomSheet()" title="Dismiss">
                <span class="material-symbols-rounded" style="font-size:20px;">close</span>
            </button>
        `;

        const primaryBtn = document.getElementById('btn-sheet-primary');
        if (primaryBtn && data.onAction) {
            primaryBtn.onclick = data.onAction;
        }
    }

    sheet.classList.add('sheet-show');
}

function dismissBottomSheet() {
    const sheet = document.getElementById('scanner-bottom-sheet');
    if (sheet) sheet.classList.remove('sheet-show');
    arTarget.active = false;
}

/**
 * Stop Camera Engine
 */
function stopCameraEngine() {
    isCameraRunning = false;
    if (cameraWatchdogTimer) {
        clearTimeout(cameraWatchdogTimer);
        cameraWatchdogTimer = null;
    }
    if (cameraAnimationId) {
        cancelAnimationFrame(cameraAnimationId);
        cameraAnimationId = null;
    }
    if (typeof AppState !== 'undefined' && AppState.videoStream) {
        try {
            AppState.videoStream.getTracks().forEach(t => t.stop());
        } catch(e) {}
        AppState.videoStream = null;
    }
    activeVideoTrack = null;
}

/**
 * Toggle Flashlight / Torch
 */
async function toggleTorch() {
    if (!activeVideoTrack) {
        showAppToast('Camera not active', 'info', 1500);
        return;
    }

    try {
        isTorchOn = !isTorchOn;
        await activeVideoTrack.applyConstraints({
            advanced: [{ torch: isTorchOn }]
        });
        const btn = document.getElementById('btn-camera-torch');
        if (btn) {
            btn.classList.toggle('torch-active', isTorchOn);
        }
        const hintEl = document.getElementById('ambient-torch-hint');
        if (hintEl && isTorchOn) hintEl.style.display = 'none';
    } catch(e) {
        showAppToast('Torch not supported on this device', 'info', 1800);
    }
}

/**
 * Switch Front/Rear Camera
 */
async function flipCamera() {
    if (typeof AppState !== 'undefined') {
        AppState.facingMode = (AppState.facingMode === 'environment') ? 'user' : 'environment';
    }
    await startCameraEngine();
}

/**
 * Zoom Capabilities (1x, 2x, 3x)
 */
function initZoomCapabilities() {
    const zoomDeck = document.getElementById('scanner-zoom-controls');
    if (!activeVideoTrack || !zoomDeck) return;
    const caps = activeVideoTrack.getCapabilities ? activeVideoTrack.getCapabilities() : {};
    if (caps.zoom) {
        zoomDeck.style.display = 'flex';
    }
}

async function setZoomLevel(level, btn) {
    currentZoom = level;
    document.querySelectorAll('.zoom-step-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    if (activeVideoTrack) {
        try {
            await activeVideoTrack.applyConstraints({
                advanced: [{ zoom: level }]
            });
        } catch(e) {}
    }
}

/**
 * 1-Tap Snapshot Manual Shutter Scan
 */
async function triggerCameraCapture() {
    const video = document.getElementById('camera-live-feed');
    if (!video || !isCameraRunning) return;

    triggerHaptic(40);
    playProductSound();
    showAppToast('Analyzing Frame...', 'info', 900);

    const vidW = video.videoWidth || 1280;
    const vidH = video.videoHeight || 720;

    if (!offscreenCanvas) offscreenCanvas = document.createElement('canvas');
    offscreenCanvas.width = 640;
    offscreenCanvas.height = Math.round((640 / vidW) * vidH);
    offscreenCtx = offscreenCanvas.getContext('2d', { willReadFrequently: true });
    offscreenCtx.drawImage(video, 0, 0, offscreenCanvas.width, offscreenCanvas.height);

    let detected = null;

    // 1. GPU BarcodeDetector
    if (nativeBarcodeDetector) {
        try {
            const res = await nativeBarcodeDetector.detect(video);
            if (res && res.length > 0 && res[0].rawValue) detected = res[0].rawValue;
        } catch(e) {}
    }

    // 2. jsQR
    if (!detected && typeof jsQR !== 'undefined') {
        try {
            const imgData = offscreenCtx.getImageData(0, 0, offscreenCanvas.width, offscreenCanvas.height);
            const qr = jsQR(imgData.data, offscreenCanvas.width, offscreenCanvas.height);
            if (qr && qr.data) detected = qr.data;
        } catch(e) {}
    }

    // 3. ZXing
    if (!detected && zxingMultiReader) {
        try {
            const res = zxingMultiReader.decode(offscreenCanvas);
            if (res && res.getText()) detected = res.getText();
        } catch(e) {}
    }

    if (detected) {
        handleSuccessfulScan(detected);
    } else {
        showAppToast('Continuous auto-scan active. Bring code into view.', 'info', 2000);
    }
}

// Auto resume camera on visibility change (recovers from mobile app switching)
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && !isCameraRunning) {
        startCameraEngine();
    }
});


