const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('desktopAPI', {
    isDesktop: true,
    platform: process.platform,

    // Window controls
    minimize: () => ipcRenderer.send('window-minimize'),
    maximize: () => ipcRenderer.send('window-maximize'),
    close: () => ipcRenderer.send('window-close'),

    // Native Printing & Thermal Hardware
    getPrinters: () => ipcRenderer.invoke('get-printers'),
    silentPrint: (options) => ipcRenderer.invoke('silent-print', options),

    // System Status
    getSystemInfo: () => ipcRenderer.invoke('get-system-info'),

    // Auto-Updates (GitHub)
    checkForUpdates: () => ipcRenderer.invoke('check-for-updates'),
    onUpdateStatus: (callback) => ipcRenderer.on('updater-status', (event, data) => callback(data)),
    onUpdateProgress: (callback) => ipcRenderer.on('updater-progress', (event, data) => callback(data)),
    installUpdate: () => ipcRenderer.invoke('install-update')
});

