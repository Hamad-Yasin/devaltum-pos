const { autoUpdater } = require('electron-updater');
const { dialog, ipcMain } = require('electron');
const path = require('path');

class AppUpdater {
    constructor(mainWindow) {
        this.mainWindow = mainWindow;
        this.isUpdateDownloaded = false;

        // Logging
        autoUpdater.logger = console;

        // Auto-download updates silently in background when detected
        autoUpdater.autoDownload = true;
        autoUpdater.autoInstallOnAppQuit = true;

        this.registerEvents();
    }

    registerEvents() {
        // 1. Checking for update
        autoUpdater.on('checking-for-update', () => {
            console.log('[AutoUpdater] Checking for updates on GitHub...');
            this.sendToRenderer('updater-status', { status: 'checking', message: 'Checking for updates...' });
        });

        // 2. Update found
        autoUpdater.on('update-available', (info) => {
            console.log('[AutoUpdater] Update available:', info.version);
            this.sendToRenderer('updater-status', {
                status: 'available',
                version: info.version,
                message: `New version v${info.version} is downloading in the background...`
            });
        });

        // 3. No update found
        autoUpdater.on('update-not-available', (info) => {
            console.log('[AutoUpdater] App is up to date.');
            this.sendToRenderer('updater-status', { status: 'not-available', message: 'Application is up to date.' });
        });

        // 4. Download progress
        autoUpdater.on('download-progress', (progressObj) => {
            const percent = Math.round(progressObj.percent);
            console.log(`[AutoUpdater] Download: ${percent}%`);
            this.sendToRenderer('updater-progress', {
                percent: percent,
                bytesPerSecond: progressObj.bytesPerSecond,
                transferred: progressObj.transferred,
                total: progressObj.total
            });
        });

        // 5. Update downloaded and ready to install
        autoUpdater.on('update-downloaded', (info) => {
            console.log('[AutoUpdater] Update downloaded successfully.');
            this.isUpdateDownloaded = true;

            this.sendToRenderer('updater-status', {
                status: 'downloaded',
                version: info.version,
                message: `Update v${info.version} ready to install.`
            });

            // Prompt user with native dialog
            dialog.showMessageBox(this.mainWindow, {
                type: 'info',
                title: 'Naya Update Tayyar Hai',
                message: `DevAltum POS ka naya version (v${info.version}) download ho chuka hai.`,
                detail: 'Naye features apply karne ke liye application ko restart karein. Client ka tamam data bilkul safe rahega.',
                buttons: ['Restart Now (Update Karein)', 'Baad Mein (Later)'],
                defaultId: 0,
                cancelId: 1,
                noLink: true
            }).then((result) => {
                if (result.response === 0) {
                    // Quit and install immediately
                    setImmediate(() => autoUpdater.quitAndInstall(false, true));
                }
            });
        });

        // 6. Error handling (e.g. offline, no internet)
        autoUpdater.on('error', (err) => {
            console.warn('[AutoUpdater] Update check skipped or failed:', err.message);
            this.sendToRenderer('updater-status', { status: 'error', message: err.message });
            // Silently ignore network failures so POS operation is never blocked
        });

        // IPC: Renderer can trigger manual check
        ipcMain.handle('check-for-updates', async () => {
            try {
                return await autoUpdater.checkForUpdates();
            } catch (err) {
                return { error: err.message };
            }
        });

        // IPC: Renderer can trigger restart
        ipcMain.handle('install-update', () => {
            if (this.isUpdateDownloaded) {
                autoUpdater.quitAndInstall(false, true);
            }
        });
    }

    sendToRenderer(channel, data) {
        if (this.mainWindow && !this.mainWindow.isDestroyed()) {
            this.mainWindow.webContents.send(channel, data);
        }
    }

    // Initiate update check (call after window loads and runtime starts)
    checkForUpdates() {
        // Wait 5 seconds after startup so main window loads cleanly first
        setTimeout(() => {
            autoUpdater.checkForUpdatesAndNotify().catch((err) => {
                console.log('[AutoUpdater] Background check notice:', err.message);
            });
        }, 5000);
    }
}

module.exports = AppUpdater;

