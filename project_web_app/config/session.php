<?php
/**
 * Session Management
 * Mengelola session dan authentication
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Start secure session
 */
function startSecureSession() {
    // Only set ini configurations if session is not active
    if (session_status() === PHP_SESSION_NONE) {
        // Konfigurasi session sebelum memulai
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set 1 untuk HTTPS
        ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
        
        // Set session name
        session_name(SESSION_NAME);
        
        // Start session
        session_start();
        
        // Regenerate session ID untuk keamanan
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        destroySession();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Destroy session
 */
function destroySession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['username']) && 
           isset($_SESSION['role']);
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Get current username
 * @return string|null
 */
function getCurrentUsername() {
    return isLoggedIn() ? $_SESSION['username'] : null;
}

/**
 * Get current user role
 * @return string|null
 */
function getCurrentUserRole() {
    return isLoggedIn() ? $_SESSION['role'] : null;
}

/**
 * Get current user full name
 * @return string|null
 */
function getCurrentUserFullName() {
    return isLoggedIn() && isset($_SESSION['full_name']) ? $_SESSION['full_name'] : null;
}

/**
 * Login user
 * @param array $user_data
 * @return bool
 */
function loginUser($user_data) {
    if (!is_array($user_data) || !isset($user_data['id'])) {
        return false;
    }
    
    startSecureSession();
    
    // Regenerate session ID untuk keamanan
    session_regenerate_id(true);
    
    // Set session data
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['email'] = $user_data['email'];
    $_SESSION['role'] = $user_data['role'];
    $_SESSION['full_name'] = $user_data['full_name'] ?? '';
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = getClientIP();
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Log login activity (only if function exists)
    if (function_exists('logActivity')) {
        logActivity($_SESSION['user_id'], 'LOGIN', 'users', $_SESSION['user_id'], 'User logged in');
    }
    
    return true;
}

/**
 * Logout user
 */
function logoutUser() {
    if (isLoggedIn()) {
        // Log logout activity (only if function exists)
        if (function_exists('logActivity')) {
            logActivity(getCurrentUserId(), 'LOGOUT', 'users', getCurrentUserId(), 'User logged out');
        }
    }
    
    destroySession();
}

/**
 * Require login (redirect if not logged in)
 * @param string $redirect_url
 */
function requireLogin($redirect_url = '/auth/login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Require admin (redirect if not admin)
 * @param string $redirect_url
 */
function requireAdmin($redirect_url = '/index.php') {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error_message'] = 'Access denied. Admin privileges required.';
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Get client IP address
 * @return string
 */
function getClientIP() {
    $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Set flash message
 * @param string $message
 * @param string $type (success, error, warning, info)
 */
function setFlashMessage($message, $type = 'info') {
    startSecureSession();
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlashMessage() {
    startSecureSession();
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Check session security
 * @return bool
 */
function checkSessionSecurity() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Check IP address (optional - can be disabled for mobile users)
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== getClientIP()) {
        // logMessage("Session security: IP mismatch for user " . getCurrentUserId(), 'WARNING');
        // return false; // Uncomment to enable IP checking
    }
    
    // Check user agent
    $current_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $current_user_agent) {
        if (function_exists('logMessage')) {
            logMessage("Session security: User agent mismatch for user " . getCurrentUserId(), 'WARNING');
        }
        // return false; // Uncomment to enable user agent checking
    }
    
    return true;
}

/**
 * Get session info
 * @return array
 */
function getSessionInfo() {
    startSecureSession();
    
    if (!isLoggedIn()) {
        return [];
    }
    
    return [
        'user_id' => getCurrentUserId(),
        'username' => getCurrentUsername(),
        'role' => getCurrentUserRole(),
        'full_name' => getCurrentUserFullName(),
        'login_time' => $_SESSION['login_time'] ?? null,
        'last_activity' => $_SESSION['last_activity'] ?? null,
        'ip_address' => $_SESSION['ip_address'] ?? null,
        'session_id' => session_id(),
        'time_remaining' => SESSION_TIMEOUT - (time() - ($_SESSION['last_activity'] ?? time()))
    ];
}

/**
 * Extend session (reset timeout)
 */
function extendSession() {
    if (isLoggedIn()) {
        $_SESSION['last_activity'] = time();
        return true;
    }
    return false;
}

/**
 * Generate session token for AJAX requests
 * @return string
 */
function generateSessionToken() {
    startSecureSession();
    if (!isset($_SESSION['ajax_token'])) {
        $_SESSION['ajax_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['ajax_token'];
}

/**
 * Verify session token for AJAX requests
 * @param string $token
 * @return bool
 */
function verifySessionToken($token) {
    startSecureSession();
    return isset($_SESSION['ajax_token']) && hash_equals($_SESSION['ajax_token'], $token);
}

// Auto-start session for all requests
startSecureSession();

// Check session security
if (isLoggedIn() && !checkSessionSecurity()) {
    setFlashMessage('Session security violation detected. Please login again.', 'error');
    logoutUser();
}

?>