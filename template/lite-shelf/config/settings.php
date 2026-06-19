<?php
/**
 * Application Settings Configuration
 * 
 * Configure your application here
 */

return [
    // Application info
    'app_name' => 'Lite_Shelf',
    'timezone' => 'UTC',
    
    // API settings
    'api_version' => 'v1',
    'default_per_page' => 50,
    'max_per_page' => 100,
    
    // Storage limits
    'max_file_size_mb' => 100,
    'max_file_size_bytes' => 104857600, // 100MB in bytes
    
    // File type restrictions
    // Empty array = all file types allowed (except blocked_extensions)
    'allowed_extensions' => [],
    
    'blocked_extensions' => [
        'exe', 'bat', 'sh', 'cmd', 'ps1', 'vbs', 
        'js', 'php', 'phtml', 'pl', 'py', 'rb'
    ],
    
    // Function execution
    'function_timeout_seconds' => 30,
    'functions_directory' => __DIR__ . '/../functions/',
    
    // Upload directories
    'uploads_base_path' => __DIR__ . '/../uploads/',
    'uploads_url_prefix' => '/uploads/',
    
    // Admin settings
    'admin_session_lifetime' => 3600, // 1 hour in seconds
    
    // Logging
    'log_errors' => true,
    'log_location' => __DIR__ . '/../logs/errors.log',
    
    // Security
    'api_key_header' => 'X-API-Key',
    'bcrypt_cost' => 10,
];
