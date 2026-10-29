const { app, BrowserWindow, ipcMain } = require('electron');
const path = require('path');
const runtimeManager = require('./runtime-manager');
const config = require('./config');
const AppUpdater = require('./updater');

let splashWindow = null;
let mainWindow = null;
let appUpdater = null;

// Hardware acceleration & performance switches
app.commandLine.appendSwitch('enable-gpu-rasterization');
app.commandLine.appendSwitch('enable-zero-copy');
app.commandLine.appendSwitch('ignore-gpu-blocklist');

// Enforce single application instance
const gotTheLock = app.requestSingleInstanceLock();
if (!gotTheLock) {
    app.quit();
} else {
    app.on('second-instance', () => {
        if (mainWindow) {
            if (mainWindow.isMinimized()) mainWindow.restore();
            mainWindow.focus();
        }
    });

    app.whenReady().then(initApp);
}

async function initApp() {
    createSplashWindow();

    try {
        const appUrl = await runtimeManager.startAll((message, percent) => {
            if (splashWindow && !splashWindow.isDestroyed()) {
                splashWindow.webContents.send('init-progress', { message, percent });
            }
        });

        // Small delay to let splash reach 100% smoothly
        setTimeout(() => {
            createMainWindow(appUrl);
        }, 500);

    } catch (err) {
        console.error('Fatal startup error:', err);
        if (splashWindow && !splashWindow.isDestroyed()) {
            splashWindow.webContents.send('init-progress', {
                message: 'Startup Error: ' + err.message,
                percent: 100
            });
        }
    }
}

const appIconPath = path.join(__dirname, 'assets', 'icon.png');

function createSplashWindow() {
    splashWindow = new BrowserWindow({
        width: 480,
        height: 320,
        frame: false,
        resizable: false,
        center: true,
        show: false,
        transparent: false,
        alwaysOnTop: true,
        icon: appIconPath,
        webPreferences: {
            nodeIntegration: true,
            contextIsolation: false
        }
    });

    splashWindow.loadFile(path.join(__dirname, 'splash.html'));
    splashWindow.once('ready-to-show', () => {
        splashWindow.show();
    });
}

function createMainWindow(url) {
    mainWindow = new BrowserWindow({
        width: 1366,
        height: 768,
        minWidth: 1024,
        minHeight: 640,
        show: false,
        backgroundColor: '#ffffff',
        title: config.appName,
        icon: appIconPath,
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            nodeIntegration: false,
            contextIsolation: true,
            webSecurity: true
        }
    });

    // Remove default menu bar for clean app look
    mainWindow.setMenuBarVisibility(false);

    mainWindow.loadURL(url);

    mainWindow.once('ready-to-show', () => {
        mainWindow.maximize();
        mainWindow.show();

        // Close splash window
        if (splashWindow && !splashWindow.isDestroyed()) {
            splashWindow.destroy();
            splashWindow = null;
        }

        // Initialize background update check
        try {
            appUpdater = new AppUpdater(mainWindow);
            appUpdater.checkForUpdates();
        } catch (updateErr) {
            console.log('[AutoUpdater] Init note:', updateErr.message);
        }
    });

    mainWindow.on('closed', () => {
        mainWindow = null;
    });
}

// IPC Handlers
ipcMain.handle('get-printers', async () => {
    if (!mainWindow) return [];
    return await mainWindow.webContents.getPrintersAsync();
});

ipcMain.handle('silent-print', async (event, options = {}) => {
    if (!mainWindow) return { success: false, error: 'No active window' };

    return new Promise((resolve) => {
        mainWindow.webContents.print({
            silent: options.silent !== false,
            printBackground: true,
            deviceName: options.deviceName || '',
            color: options.color || false,
            margins: { marginType: 'none' },
            copies: options.copies || 1,
            pageSize: options.pageSize || undefined
        }, (success, failureReason) => {
            resolve({ success, error: failureReason });
        });
    });
});

ipcMain.handle('get-system-info', () => {
    return {
        appName: config.appName,
        version: config.version,
        isDev: config.isDev,
        httpPort: runtimeManager.activeHttpPort,
        dbPort: runtimeManager.activeDbPort,
        userDataDir: config.userDataDir
    };
});

ipcMain.on('window-minimize', () => mainWindow?.minimize());
ipcMain.on('window-maximize', () => {
    if (mainWindow?.isMaximized()) mainWindow.unmaximize();
    else mainWindow?.maximize();
});
ipcMain.on('window-close', () => mainWindow?.close());

// Graceful application exit
app.on('before-quit', async (event) => {
    if (runtimeManager.isRunning) {
        event.preventDefault();
        await runtimeManager.stopAll();
        app.exit(0);
    }
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

