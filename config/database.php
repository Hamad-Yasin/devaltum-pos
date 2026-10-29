<?php

// Ensure Env helper is loaded
if (!class_exists('Env')) {
    $envHelper = dirname(__DIR__) . '/core/helpers/Env.php';
    if (file_exists($envHelper)) {
        require_once $envHelper;
    }
}

if (class_exists('Env')) {
    Env::load(dirname(__DIR__) . '/.env');
}

if (!defined('DB_HOST')) define('DB_HOST', env('DB_HOST', 'localhost'));
if (!defined('DB_NAME')) define('DB_NAME', env('DB_DATABASE', env('DB_NAME', 'saas-pro-pos')));
if (!defined('DB_USER')) define('DB_USER', env('DB_USERNAME', env('DB_USER', 'root')));
if (!defined('DB_PASS')) define('DB_PASS', env('DB_PASSWORD', env('DB_PASS', '')));
if (!defined('DB_PORT')) define('DB_PORT', env('DB_PORT', '3306'));
