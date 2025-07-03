<?php
/**
 * Application Configuration
 * Konfigurasi umum aplikasi
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Environment configuration
define('ENVIRONMENT', 'development'); // development, staging, production

// Application basic info
define('APP_NAME', 'Web Management System');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Complete web application for business management');

// URL Configuration
define('BASE_URL', 'http://localhost/project_web_app');
define('ASSETS_URL', BASE_URL . '/assets');

// Path Configuration
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CONFIG_PATH', ROOT_PATH . '/config');

// Session Configuration (set BEFORE any session operations)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS only
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
}

// Session settings
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('SESSION_NAME', 'web_app_session');

// Security Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 300); // 5 minutes

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads');

// Pagination Configuration
define('RECORDS_PER_PAGE', 10);
define('MAX_PAGINATION_LINKS', 5);

// Date/Time Configuration
define('DEFAULT_TIMEZONE', 'Asia/Jakarta');
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd-m-Y');
define('DISPLAY_DATETIME_FORMAT', 'd-m-Y H:i');

// Currency Configuration
define('CURRENCY_SYMBOL', 'Rp');
define('CURRENCY_CODE', 'IDR');
define('DECIMAL_PLACES', 0);

// Email Configuration (if needed)
define('MAIL_HOST', 'localhost');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_FROM_EMAIL', 'noreply@webapp.com');
define('MAIL_FROM_NAME', APP_NAME);

// Application Settings
$app_settings = [
    'app_name' => APP_NAME,
    'app_version' => APP_VERSION,
    'maintenance_mode' => false,
    'registration_enabled' => true,
    'email_verification' => false,
    'backup_enabled' => true,
    'debug_mode' => (ENVIRONMENT === 'development'),
    'log_activities' => true,
    'password_reset_enabled' => true
];

// Error Reporting based on environment
switch (ENVIRONMENT) {
    case 'development':
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
        break;
        
    case 'staging':
        error_reporting(E_ALL & ~E_NOTICE);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        break;
        
    case 'production':
        error_reporting(0);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        break;
}

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Auto-create upload directory if not exists
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

/**
 * Get application setting
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getAppSetting($key, $default = null) {
    global $app_settings;
    return isset($app_settings[$key]) ? $app_settings[$key] : $default;
}

/**
 * Set application setting
 * @param string $key
 * @param mixed $value
 */
function setAppSetting($key, $value) {
    global $app_settings;
    $app_settings[$key] = $value;
}

/**
 * Check if application is in maintenance mode
 * @return bool
 */
function isMaintenanceMode() {
    return getAppSetting('maintenance_mode', false);
}

/**
 * Check if debug mode is enabled
 * @return bool
 */
function isDebugMode() {
    return getAppSetting('debug_mode', false);
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format date for display
 * @param string $date
 * @param string $format
 * @return string
 */
// formatDate function moved to includes/functions.php to avoid duplication

/**
 * Sanitize input data
 * @param mixed $data
 * @return mixed
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random string
 * @param int $length
 * @return string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Log message to file
 * @param string $message
 * @param string $level
 */
function logMessage($message, $level = 'INFO') {
    if (getAppSetting('debug_mode', false)) {
        $logFile = ROOT_PATH . '/logs/app.log';
        $logDir = dirname($logFile);
        
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// Load database configuration
require_once CONFIG_PATH . '/database.php';

// Load session management
require_once CONFIG_PATH . '/session.php';

// Initialize application
if (isMaintenanceMode() && !isset($_SESSION['admin_override'])) {
    // Show maintenance page
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 3600'); // 1 hour
    
    if (file_exists(ROOT_PATH . '/maintenance.html')) {
        include ROOT_PATH . '/maintenance.html';
    } else {
        echo '<h1>System Maintenance</h1><p>We are currently performing scheduled maintenance. Please try again later.</p>';
    }
    exit;
}

?>