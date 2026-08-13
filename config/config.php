<?php
/**
 * SAMRIDHI AGRO - Application Configuration
 * 
 * This file contains all global application settings, constants,
 * and environment-specific configurations for the Samridhi Agro platform.
 * 
 * @package SamridhiAgro
 * @subpackage Config
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// ============================================
// APPLICATION BASE CONFIGURATION
// ============================================

// Application name and version
define('APP_NAME', 'Samridhi Agro');
define('APP_VERSION', '1.0.0');
define('APP_SHORT_NAME', 'SAGRO');
define('APP_ENV', 'development'); // development | production | staging

// Application URLs
define('SITE_URL', 'http://localhost/agro/');
define('ADMIN_URL', SITE_URL . 'admin/');
define('STAFF_URL', SITE_URL . 'staff/');
define('AGENT_URL', SITE_URL . 'agent/');
define('SHOP_URL', SITE_URL . 'shop/');
define('ASSETS_URL', SITE_URL . 'assets/');
define('CSS_URL', ASSETS_URL . 'css/');
define('JS_URL', ASSETS_URL . 'js/');
define('IMAGES_URL', ASSETS_URL . 'images/');

// Application paths (server paths)
define('ROOT_PATH', realpath(dirname(__DIR__)) . '/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');

// ============================================
// TIME & DATE CONFIGURATION
// ============================================

date_default_timezone_set('Asia/Kolkata'); // IST timezone
define('DATE_FORMAT', 'd-m-Y');
define('TIME_FORMAT', 'h:i A');
define('DATETIME_FORMAT', 'd-m-Y h:i A');
define('DB_DATE_FORMAT', 'Y-m-d');
define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');

// ============================================
// SECURITY & SESSION CONFIGURATION
// ============================================

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', (APP_ENV === 'production') ? 1 : 0);

// CSRF Token settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LENGTH', 32);

// Session timeout (in seconds) - 24 hours
define('SESSION_TIMEOUT', 86400);

// Login attempt limits
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 900); // 15 minutes in seconds
define('LOGIN_LOCKOUT_TIME', 1800); // 30 minutes in seconds

// Password requirements
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_SPECIAL', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_UPPER', true);

// ============================================
// FILE UPLOAD CONFIGURATION
// ============================================

// Allowed file types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']);
define('ALLOWED_DOCUMENT_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Maximum file sizes (in bytes)
define('MAX_FILE_SIZE', 5242880); // 5MB
define('MAX_IMAGE_SIZE', 5242880); // 5MB
define('MAX_DOCUMENT_SIZE', 10485760); // 10MB

// Image upload settings
define('IMAGE_QUALITY', 85);
define('IMAGE_MAX_WIDTH', 2000);
define('IMAGE_MAX_HEIGHT', 2000);
define('IMAGE_THUMB_WIDTH', 300);
define('IMAGE_THUMB_HEIGHT', 300);
define('IMAGE_SMALL_WIDTH', 100);
define('IMAGE_SMALL_HEIGHT', 100);

// ============================================
// PAGINATION CONFIGURATION
// ============================================

define('PAGINATION_DEFAULT_LIMIT', 20);
define('PAGINATION_MAX_LIMIT', 100);
define('PAGINATION_PAGE_RANGE', 5);

// ============================================
// DASHBOARD CONFIGURATION
// ============================================

define('DASHBOARD_REFRESH_INTERVAL', 300); // 5 minutes in seconds
define('DASHBOARD_CHART_COLORS', [
    '#14532D', // Forest Green
    '#16A34A', // Agri Green
    '#22C55E', // Fresh Green
    '#65A30D', // Leaf Green
    '#EAB308', // Gold
    '#B45309', // Gold Dark
    '#DC2626', // Red
    '#2563EB', // Blue
]);

// ============================================
// NOTIFICATION CONFIGURATION
// ============================================

define('NOTIFICATION_LIFETIME', 30); // Days to keep notifications
define('NOTIFICATION_MAX_DISPLAY', 10); // Maximum notifications to show in dropdown

// ============================================
// CACHE CONFIGURATION
// ============================================

define('CACHE_ENABLED', false);
define('CACHE_LIFETIME', 3600); // 1 hour in seconds
define('CACHE_PATH', ROOT_PATH . 'cache/');

// ============================================
// EMAIL CONFIGURATION
// ============================================

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@samridhiagro.com');
define('SMTP_FROM_NAME', 'Samridhi Agro');

// ============================================
// BUSINESS RULES CONFIGURATION
// ============================================

// Order settings
define('ORDER_STATUSES', [
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'processing' => 'Processing',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
    'returned' => 'Returned'
]);

// Payment statuses
define('PAYMENT_STATUSES', [
    'pending' => 'Pending',
    'paid' => 'Paid',
    'failed' => 'Failed',
    'refunded' => 'Refunded'
]);

// Product statuses
define('PRODUCT_STATUSES', [
    'active' => 'Active',
    'inactive' => 'Inactive',
    'out_of_stock' => 'Out of Stock'
]);

// User statuses
define('USER_STATUSES', [
    'active' => 'Active',
    'inactive' => 'Inactive',
    'suspended' => 'Suspended'
]);

// Approval statuses
define('APPROVAL_STATUSES', [
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'suspended' => 'Suspended'
]);

// ============================================
// LOGGING CONFIGURATION
// ============================================

define('LOG_ENABLED', true);
define('LOG_PATH', ROOT_PATH . 'logs/');
define('LOG_LEVEL', 'info'); // debug | info | warning | error | critical
define('LOG_FILE_EXTENSION', '.log');

// ============================================
// API CONFIGURATION (Future use)
// ============================================

define('API_ENABLED', false);
define('API_KEY_LENGTH', 32);
define('API_RATE_LIMIT', 100); // Requests per minute
define('API_VERSION', 'v1');

// ============================================
// MAINTENANCE MODE
// ============================================

define('MAINTENANCE_MODE', false);
define('MAINTENANCE_MESSAGE', 'Samridhi Agro is currently undergoing maintenance. Please check back later.');

// ============================================
// ERROR REPORTING (Development vs Production)
// ============================================

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// ============================================
// CREATE REQUIRED DIRECTORIES
// ============================================

// Ensure required directories exist
$requiredDirs = [
    UPLOADS_PATH,
    UPLOADS_PATH . 'products/',
    UPLOADS_PATH . 'products/thumbs/',
    UPLOADS_PATH . 'avatars/',
    UPLOADS_PATH . 'documents/',
    UPLOADS_PATH . 'temp/',
    LOG_PATH,
    CACHE_PATH
];

foreach ($requiredDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ============================================
// AUTO-LOAD CONFIGURATION
// ============================================

/**
 * Simple autoloader for application classes
 * 
 * @param string $class The class name to load
 */
function appAutoloader($class) {
    $prefix = 'SamridhiAgro\\';
    $base_dir = ROOT_PATH . 'src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
}

// Register autoloader
spl_autoload_register('appAutoloader');

// ============================================
// GLOBAL CONSTANTS FOR THEME COLORS
// ============================================

// Samridhi Agro Theme Colors
define('THEME_FOREST_GREEN', '#14532D');
define('THEME_AGRI_GREEN', '#16A34A');
define('THEME_FRESH_GREEN', '#22C55E');
define('THEME_LIGHT_GREEN', '#DCFCE7');
define('THEME_DARK_GREEN', '#052E16');
define('THEME_LEAF_GREEN', '#65A30D');
define('THEME_SOFT_MINT', '#F0FDF4');
define('THEME_BG_GREEN', '#F7FCF7');
define('THEME_GOLD', '#EAB308');
define('THEME_GOLD_DARK', '#B45309');

// ============================================
// ENVIRONMENT CHECK
// ============================================

// Check if database configuration exists
$dbConfigPath = CONFIG_PATH . 'database.php';
if (!file_exists($dbConfigPath)) {
    if (APP_ENV === 'development') {
        trigger_error('Database configuration file not found: ' . $dbConfigPath, E_USER_WARNING);
    }
    // Continue loading - database will handle error gracefully
}

// Check if uploads directory is writable
if (!is_writable(UPLOADS_PATH)) {
    if (APP_ENV === 'development') {
        trigger_error('Uploads directory is not writable: ' . UPLOADS_PATH, E_USER_WARNING);
    }
}

// ============================================
// APPLICATION STARTUP
// ============================================

// Start session if not already started
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// Set response headers for security
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}