# 📱 POS Wireless Mobile Scanner App (Capacitor & Web)

An enterprise-grade, high-speed wireless barcode scanner application built for **DevAltum POS**.

---

## 🚀 Key Features

- **Permanent 6-Digit Store PIN Pairing**: No expiring tokens. Connects instantly with your store's unique 6-digit PIN.
- **Scan-to-Connect QR Code**: Point your phone camera to pair in 1 second.
- **Sub-100ms Barcode Recognition**: Multi-engine decoding (EAN-13, CODE128, QR Code, UPC, Code39).
- **Audio & Haptic Feedback**: Synthetic audio beeps and vibration feedback on successful scans.
- **3 Dynamic Operation Modes**:
  1. 🛒 **POS Live Cart**: Instantly injects scanned products into active desktop POS checkout.
  2. 📦 **Stock Add / Audit**: Scan barcodes to count and verify warehouse stock.
  3. 🏷️ **Price & Details Check**: Inspect item prices, stock, and descriptions without modifying cart.
- **Torch / Flashlight & Camera Switcher**: Work seamlessly in dim retail environments.

---

## 🛠️ How to Build into Android APK (Capacitor)

### 1. Install Dependencies
Open terminal in the `mobile_scanner_app` directory:
```bash
cd mobile_scanner_app
npm install
```

### 2. Add Android Platform & Sync
```bash
npx cap add android
npx cap sync
```

### 3. Open in Android Studio & Generate APK
```bash
npx cap open android
```
- In Android Studio, click **Build > Build Bundle(s) / APK(s) > Build APK(s)**.
- Install the `.apk` on any Android smartphone or POS handheld terminal!

---

## 🌐 Instant Web / Mobile Browser Access

You can also open the scanner directly in any mobile browser (Chrome / Safari):
```
http://<your-lan-ip-or-domain>/pospro/scanner?code=849201
```
Or simply open the POS Desktop screen and click the **Mobile Scanner** button to view your store's 6-Digit Code and QR Code!
