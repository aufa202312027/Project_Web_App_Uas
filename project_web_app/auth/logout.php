<?php
/**
 * Logout Handler
 * Handle user logout and session cleanup
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../includes/functions.php';

// Start session if not already started
startSecureSession();

// Check if user is logged in
if (isLoggedIn()) {
    // Log logout activity before destroying session
    logActivity(getCurrentUserId(), 'LOGOUT', 'users', getCurrentUserId(), 'User logged out');
    
    // Clear remember me cookie if exists
    if (isset($_COOKIE['remember_user'])) {
        setcookie('remember_user', '', time() - 3600, '/');
    }
    
    // Set flash message for successful logout
    setFlashMessage('You have been successfully logged out.', 'success');
}

// Destroy session
logoutUser();

// Redirect to login page
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
?>