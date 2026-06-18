<?php
/**
 * Database Connection Configuration
 * 
 * WARNING: Never commit real credentials to version control!
 * Use environment variables or a separate config file that's gitignored.
 */

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: 3306,
    'database' => getenv('DB_NAME') ?: 'lite_shelf',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'table_prefix' => getenv('DB_PREFIX') ?: '',
];
