<?php
/**
 * Admin Users Management - Delete User
 * Handler untuk menghapus user (hard delete by default, soft delete optional)
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check admin access
requireAdmin();

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('Invalid request method.', 'error');
    header('Location: index.php');
    exit;
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('Invalid security token.', 'error');
    header('Location: index.php');
    exit;
}

// Get user ID
$user_id = (int)($_POST['user_id'] ?? 0);
if (!$user_id) {
    setFlashMessage('User ID is required.', 'error');
    header('Location: index.php');
    exit;
}

// Get user data
$user = getUserById($user_id);
if (!$user) {
    setFlashMessage('User not found.', 'error');
    header('Location: index.php');
    exit;
}

// Prevent deleting own account
if ($user_id == getCurrentUserId()) {
    setFlashMessage('You cannot delete your own account.', 'error');
    header('Location: index.php');
    exit;
}

// Prevent deleting the last admin
if ($user['role'] === 'admin') {
    $admin_count = getRecord("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1")['count'];
    if ($admin_count <= 1) {
        setFlashMessage('Cannot delete the last active administrator.', 'error');
        header('Location: index.php');
        exit;
    }
}

try {
    // Check if this is a hard delete request (default) or soft delete
    $delete_type = $_POST['delete_type'] ?? 'hard';
    $hard_delete = ($delete_type === 'hard');
    
    // Perform delete operation (hard delete by default)
    $success = deleteUser($user_id, !$hard_delete);
    
    if ($success) {
        if ($hard_delete) {
            // Log hard delete activity
            logActivity(getCurrentUserId(), 'DELETE', 'users', $user_id, 
                       "Permanently deleted user: {$user['username']} ({$user['role']})");
            
            setFlashMessage("User '{$user['username']}' has been permanently deleted.", 'success');
        } else {
            // Log soft delete activity
            logActivity(getCurrentUserId(), 'DEACTIVATE', 'users', $user_id, 
                       "Deactivated user: {$user['username']} ({$user['role']})");
            
            setFlashMessage("User '{$user['username']}' has been deactivated successfully.", 'success');
        }
    } else {
        $action = $hard_delete ? 'delete' : 'deactivate';
        setFlashMessage("Failed to {$action} user. Please try again.", 'error');
        
        // Log failure for debugging
        logMessage("User deletion failed: user_id=$user_id, hard_delete=$hard_delete", 'ERROR');
    }
    
} catch (Exception $e) {
    logMessage("User deletion error: " . $e->getMessage(), 'ERROR');
    setFlashMessage('An error occurred while processing the user deletion. Error: ' . $e->getMessage(), 'error');
}

// Redirect back to users list
header('Location: index.php');
exit;
?>
