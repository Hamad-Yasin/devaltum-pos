/**
 * DevAltum POS - Database Manager
 * Handles automatic schema initialization, migrations, and health checks on startup.
 */

const fs = require('fs');
const path = require('path');
const config = require('./config');

class DatabaseManager {
    constructor() {
        this.isInitialized = false;
    }

    /**
     * Check if initial schema dump exists
     */
    getSchemaFile() {
        const candidates = [
            path.join(config.rootDir, 'installer', 'schema.sql'),
            path.join(config.rootDir, 'database.sql'),
            path.join(config.rootDir, 'talha_pos.sql'),
            path.join(config.rootDir, 'saas-pro-pos.sql')
        ];

        for (const file of candidates) {
            if (fs.existsSync(file)) return file;
        }
        return null;
    }

    /**
     * Verify database connection parameters
     */
    verifyConfig() {
        const envFile = path.join(config.rootDir, '.env');
        if (fs.existsSync(envFile)) {
            const content = fs.readFileSync(envFile, 'utf8');
            return {
                configured: true,
                hasDbName: content.includes('DB_DATABASE='),
                content
            };
        }
        return { configured: false };
    }
}

module.exports = new DatabaseManager();

