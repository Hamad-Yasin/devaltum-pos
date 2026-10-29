/**
 * DevAltum POS - Runtime Preparation Script
 * Verifies or copies local PHP & MariaDB binaries into runtime/ directory for standalone distribution.
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');
const RUNTIME_DIR = path.join(ROOT, 'runtime');
const PHP_DIR = path.join(RUNTIME_DIR, 'php');
const MARIADB_DIR = path.join(RUNTIME_DIR, 'mariadb');
const NGINX_DIR = path.join(RUNTIME_DIR, 'nginx');

console.log('--- DevAltum POS Runtime Status Check ---');
console.log('Runtime Directory:', RUNTIME_DIR);

function checkDirectory(dirName, dirPath) {
    if (fs.existsSync(dirPath) && fs.readdirSync(dirPath).length > 0) {
        console.log(`[OK] ${dirName} is populated.`);
        return true;
    } else {
        console.log(`[INFO] ${dirName} is ready for portable binaries.`);
        return false;
    }
}

checkDirectory('PHP Runtime', PHP_DIR);
checkDirectory('MariaDB Runtime', MARIADB_DIR);
checkDirectory('Nginx Runtime', NGINX_DIR);

console.log('-----------------------------------------');
console.log('Development mode will automatically fallback to system Laragon PHP & MySQL.');

