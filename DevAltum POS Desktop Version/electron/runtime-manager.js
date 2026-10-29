const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');
const http = require('http');
const net = require('net');
const config = require('./config');

class RuntimeManager {
    constructor() {
        this.phpProcess = null;
        this.mariaDbProcess = null;
        this.nginxProcess = null;
        this.activeHttpPort = config.defaultPorts.http;
        this.activeDbPort = config.defaultPorts.mariadb;
        this.isRunning = false;
    }

    // Check if a port is in use
    isPortAvailable(port) {
        return new Promise((resolve) => {
            const server = net.createServer();
            server.once('error', (err) => {
                resolve(err.code !== 'EADDRINUSE');
            });
            server.once('listening', () => {
                server.close();
                resolve(true);
            });
            server.listen(port, '127.0.0.1');
        });
    }

    // Find next available port starting from basePort
    async getAvailablePort(basePort) {
        let port = basePort;
        while (!(await this.isPortAvailable(port))) {
            port++;
        }
        return port;
    }

    // Recursive directory copier
    copyFolderRecursiveSync(src, dest) {
        if (!fs.existsSync(src)) return;
        if (!fs.existsSync(dest)) fs.mkdirSync(dest, { recursive: true });

        const entries = fs.readdirSync(src, { withFileTypes: true });
        for (const entry of entries) {
            const srcPath = path.join(src, entry.name);
            const destPath = path.join(dest, entry.name);
            if (entry.isDirectory()) {
                this.copyFolderRecursiveSync(srcPath, destPath);
            } else {
                fs.copyFileSync(srcPath, destPath);
            }
        }
    }

    // Ensure required AppData folders exist and auto-seed database on first launch
    ensureDirectories() {
        [config.userDataDir, config.dbDataDir, config.logsDir, config.backupsDir].forEach(dir => {
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true });
            }
        });

        // First run check: If database_data has no 'mysql' directory, copy pristine seed data
        const mysqlDir = path.join(config.dbDataDir, 'mysql');
        if (!fs.existsSync(mysqlDir)) {
            const candidateSeeds = [
                path.join(config.resourcesDir, 'installer', 'default_data'),
                path.join(config.rootDir, 'installer', 'default_data'),
                path.join(__dirname, '..', 'installer', 'default_data')
            ];

            const seedSrc = candidateSeeds.find(p => fs.existsSync(p));
            if (seedSrc) {
                console.log('[Database] First-time setup: Seeding pristine database...');
                this.copyFolderRecursiveSync(seedSrc, config.dbDataDir);
                console.log('[Database] Pristine database seeded successfully!');
            }
        }
    }

    // Start background runtime stack
    async startAll(onProgress = () => {}) {
        this.ensureDirectories();

        onProgress('Checking network ports...', 20);
        this.activeHttpPort = await this.getAvailablePort(config.defaultPorts.http);
        this.activeDbPort = await this.getAvailablePort(config.defaultPorts.mariadb);

        onProgress('Initializing database engine...', 40);
        await this.startMariaDB();

        onProgress('Launching local application server...', 70);
        await this.startApplicationServer();

        onProgress('Verifying system health...', 90);
        await this.waitForServerReady(`http://127.0.0.1:${this.activeHttpPort}`);

        this.isRunning = true;
        onProgress('DevAltum POS Ready!', 100);

        return `http://127.0.0.1:${this.activeHttpPort}`;
    }

    // MariaDB Management
    async startMariaDB() {
        const mysqldExe = path.join(config.mariadbDir, 'bin', 'mysqld.exe');

        if (fs.existsSync(mysqldExe)) {
            console.log(`Starting Portable MariaDB on port ${this.activeDbPort}...`);
            const args = [
                `--datadir=${config.dbDataDir}`,
                `--port=${this.activeDbPort}`,
                '--bind-address=127.0.0.1',
                '--skip-name-resolve',
                '--console'
            ];
            this.mariaDbProcess = spawn(mysqldExe, args, { windowsHide: true });

            this.mariaDbProcess.stdout?.on('data', (d) => console.log(`[MariaDB] ${d}`));
            this.mariaDbProcess.stderr?.on('data', (d) => console.log(`[MariaDB Log] ${d}`));
            this.mariaDbProcess.on('error', (err) => console.error('MariaDB process error:', err));

            // Wait 2 seconds for socket initialization
            await new Promise(r => setTimeout(r, 2000));
        } else {
            console.log('[Portable MariaDB] Not found in runtime/mariadb/bin, relying on host environment.');
        }
    }

    // Application Server (PHP Built-in Server for zero-config portable runtime)
    async startApplicationServer() {
        let phpExe = path.join(config.phpDir, 'php.exe');
        if (!fs.existsSync(phpExe)) {
            phpExe = 'php';
        }

        const phpIni = path.join(config.phpDir, 'php.ini');

        // Target application root
        const webRoot = fs.existsSync(config.appDir) && fs.readdirSync(config.appDir).length > 0
            ? config.appDir
            : path.join(__dirname, '..', '..');

        // Locate router.php on the physical filesystem (outside app.asar)
        const candidateRouters = [
            path.join(webRoot, 'router.php'),
            path.join(config.appDir, 'router.php'),
            path.join(config.runtimeDir, 'router.php'),
            path.join(config.resourcesDir, 'application', 'router.php'),
            path.join(__dirname, '..', 'router.php')
        ];

        const routerScript = candidateRouters.find(r => fs.existsSync(r)) || path.join(webRoot, 'public', 'index.php');

        const args = [
            '-S', `127.0.0.1:${this.activeHttpPort}`,
            '-t', webRoot
        ];

        // Explicitly load the bundled portable php.ini so no host INI is ever used
        if (fs.existsSync(phpIni)) {
            args.push('-c', phpIni);
        }

        args.push(routerScript);

        console.log(`Starting PHP server: ${phpExe} on 127.0.0.1:${this.activeHttpPort}`);
        console.log(`Web Root: ${webRoot}`);
        console.log(`Router Script: ${routerScript}`);

        this.phpProcess = spawn(phpExe, args, {
            windowsHide: true,
            env: {
                ...process.env,
                PHP_CLI_SERVER_WORKERS: '8'
            }
        });

        this.phpProcess.stdout?.on('data', (data) => console.log(`[PHP] ${data}`));
        this.phpProcess.stderr?.on('data', (data) => console.log(`[PHP Log] ${data}`));
        this.phpProcess.on('error', (err) => console.error('PHP server error:', err));
    }

    // Poll until HTTP server responds 200/302
    waitForServerReady(url, maxRetries = 35) {
        return new Promise((resolve) => {
            let retries = 0;
            const interval = setInterval(() => {
                http.get(url, (res) => {
                    clearInterval(interval);
                    resolve(true);
                }).on('error', () => {
                    retries++;
                    if (retries >= maxRetries) {
                        clearInterval(interval);
                        resolve(true);
                    }
                });
            }, 300);
        });
    }

    // Safe Graceful Shutdown
    async stopAll() {
        console.log('Stopping all background services safely...');

        if (this.phpProcess) {
            try { this.phpProcess.kill('SIGTERM'); } catch(e) {}
            this.phpProcess = null;
        }

        if (this.mariaDbProcess) {
            try {
                // Try clean mysqladmin shutdown first if available
                const mysqladminExe = path.join(config.mariadbDir, 'bin', 'mysqladmin.exe');
                if (fs.existsSync(mysqladminExe)) {
                    spawn(mysqladminExe, ['-h', '127.0.0.1', '-P', String(this.activeDbPort), '-u', 'root', 'shutdown']);
                } else {
                    this.mariaDbProcess.kill('SIGTERM');
                }
            } catch(e) {
                try { this.mariaDbProcess.kill('SIGTERM'); } catch(e2) {}
            }
            this.mariaDbProcess = null;
        }

        if (this.nginxProcess) {
            try { this.nginxProcess.kill('SIGTERM'); } catch(e) {}
            this.nginxProcess = null;
        }

        this.isRunning = false;
    }
}

module.exports = new RuntimeManager();
