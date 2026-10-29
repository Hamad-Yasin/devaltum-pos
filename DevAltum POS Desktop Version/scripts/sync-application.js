/**
 * DevAltum POS - Application Sync Script
 * Mirrors the POS application codebase into the desktop distribution directory.
 */

const fs = require('fs');
const path = require('path');

const ROOT_APP = path.join(__dirname, '..', '..');
const TARGET_APP = path.join(__dirname, '..', 'application');

const SYNC_FOLDERS = [
    'app',
    'config',
    'core',
    'modules',
    'public',
    'storage',
    'vendor',
    'installer'
];

const SYNC_FILES = [
    '.env',
    'composer.json',
    'router.php'
];

console.log('--- Syncing POS Application Codebase ---');
console.log('Source:', ROOT_APP);
console.log('Target:', TARGET_APP);

if (!fs.existsSync(TARGET_APP)) {
    fs.mkdirSync(TARGET_APP, { recursive: true });
}

// Recursive copy helper
function copyFolderRecursive(src, dest) {
    if (!fs.existsSync(src)) return;
    if (!fs.existsSync(dest)) fs.mkdirSync(dest, { recursive: true });

    const entries = fs.readdirSync(src, { withFileTypes: true });
    for (const entry of entries) {
        // Skip git, node_modules, and cache files
        if (entry.name === '.git' || entry.name === 'node_modules' || entry.name.endsWith('.tmp')) {
            continue;
        }

        const srcPath = path.join(src, entry.name);
        const destPath = path.join(dest, entry.name);

        if (entry.isDirectory()) {
            copyFolderRecursive(srcPath, destPath);
        } else {
            fs.copyFileSync(srcPath, destPath);
        }
    }
}

// Copy directories
for (const folder of SYNC_FOLDERS) {
    const src = path.join(ROOT_APP, folder);
    const dest = path.join(TARGET_APP, folder);
    console.log(`Syncing folder: ${folder}...`);
    copyFolderRecursive(src, dest);
}

// Copy single files
for (const file of SYNC_FILES) {
    const src = path.join(ROOT_APP, file);
    const dest = path.join(TARGET_APP, file);
    if (fs.existsSync(src)) {
        fs.copyFileSync(src, dest);
        console.log(`Copied file: ${file}`);
    }
}

console.log('[SUCCESS] Application codebase synced cleanly for desktop packaging!');

