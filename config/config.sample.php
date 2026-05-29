<?php
/**
 * ============================================================
 * Doge Faucet - Configuration File (sample)
 * ------------------------------------------------------------
 * 1. Copy this file to: config/config.php
 * 2. Update the values below for your hosting environment.
 * 3. Make sure config.php is NOT publicly accessible
 *    (the .htaccess file in /config blocks direct access).
 * ============================================================
 */

// Database connection settings (cPanel: see "MySQL Databases")
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// Site URL (no trailing slash). Example: https://faucet.example.com
define('SITE_URL', 'https://example.com');

// Absolute filesystem path to the project root (auto-detected; usually leave as-is)
define('SITE_ROOT', dirname(__DIR__));

// Cookie / session settings
define('SESSION_NAME', 'DOGEFAUCETSID');
define('COOKIE_SECURE', false);   // set true if site is served over HTTPS only
define('COOKIE_HTTPONLY', true);

// App secret used for CSRF tokens, password reset tokens etc.
// Generate a long random string - DO NOT leave default in production.
define('APP_SECRET', 'change-me-to-a-long-random-string');

// Default timezone
date_default_timezone_set('UTC');

// Display errors? (turn OFF in production)
define('DEBUG', false);

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
