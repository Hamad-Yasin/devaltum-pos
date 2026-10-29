const path = require('path');
const electron = require('electron');
const app = electron.app || (electron.remote ? electron.remote.app : null);

const isDev = process.env.NODE_ENV === 'development' || process.argv.includes('--dev') || (!app || !app.isPackaged);

// Base paths
const rootDir = isDev
    ? path.join(__dirname, '..')
    : (app ? path.dirname(app.getPath('exe')) : path.join(__dirname, '..'));

const resourcesDir = isDev
    ? path.join(__dirname, '..')
    : (process.resourcesPath || path.join(__dirname, '..'));

// User Data Directory (Safe from updates, preserved forever)
const userDataDir = app && app.getPath
    ? app.getPath('userData')
    : path.join(process.env.APPDATA || path.join(__dirname, '..'), 'devaltump-pos-desktop');
const dbDataDir = path.join(userDataDir, 'database_data');
const logsDir = path.join(userDataDir, 'logs');
const backupsDir = path.join(userDataDir, 'backups');

module.exports = {
    isDev,
    appName: 'DevAltum POS',
    version: '1.0.0',
    rootDir,
    resourcesDir,
    userDataDir,
    dbDataDir,
    logsDir,
    backupsDir,

    // Subsystem Paths
    runtimeDir: path.join(resourcesDir, 'runtime'),
    phpDir: path.join(resourcesDir, 'runtime', 'php'),
    mariadbDir: path.join(resourcesDir, 'runtime', 'mariadb'),
    nginxDir: path.join(resourcesDir, 'runtime', 'nginx'),
    appDir: path.join(resourcesDir, 'application'),

    // Default Network Ports (with auto-fallback if busy)
    defaultPorts: {
        http: 8420,
        phpCgi: 9074,
        mariadb: 3306
    }
};

