<?php
/**
 * Admin Users Management - Process Handler
 * AJAX handler untuk operasi user
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check admin access
requireAdmin();

// Set JSON response header
header('Content-Type: application/json');

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    switch ($action) {
        case 'get_user':
            $user_id = (int)($_GET['id'] ?? 0);
            if (!$user_id) {
                throw new Exception('User ID is required');
            }
            
            $user = getUserById($user_id);
            if (!$user) {
                throw new Exception('User not found');
            }
            
            $response['success'] = true;
            $response['user'] = $user;
            break;
            
        case 'check_username':
            $username = $_POST['username'] ?? '';
            $user_id = (int)($_POST['user_id'] ?? 0);
            
            if (empty($username)) {
                throw new Exception('Username is required');
            }
            
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                throw new Exception('Username can only contain letters, numbers and underscore');
            }
            
            $sql = "SELECT id FROM users WHERE username = ?";
            $params = [$username];
            
            if ($user_id) {
                $sql .= " AND id != ?";
                $params[] = $user_id;
            }
            
            $existing = getRecord($sql, $params);
            
            $response['success'] = true;
            $response['available'] = !$existing;
            $response['message'] = $existing ? 'Username already exists' : 'Username is available';
            break;
            
        case 'check_email':
            $email = $_POST['email'] ?? '';
            $user_id = (int)($_POST['user_id'] ?? 0);
            
            if (empty($email)) {
                throw new Exception('Email is required');
            }
            
            if (!isValidEmail($email)) {
                throw new Exception('Invalid email format');
            }
            
            $sql = "SELECT id FROM users WHERE email = ?";
            $params = [$email];
            
            if ($user_id) {
                $sql .= " AND id != ?";
                $params[] = $user_id;
            }
            
            $existing = getRecord($sql, $params);
            
            $response['success'] = true;
            $response['available'] = !$existing;
            $response['message'] = $existing ? 'Email already exists' : 'Email is available';
            break;
            
        case 'toggle_status':
            // Verify CSRF token
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid security token');
            }
            
            $user_id = (int)($_POST['user_id'] ?? 0);
            if (!$user_id) {
                throw new Exception('User ID is required');
            }
            
            // Prevent modifying own account
            if ($user_id == getCurrentUserId()) {
                throw new Exception('You cannot modify your own account status');
            }
            
            $user = getUserById($user_id);
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Prevent deactivating the last admin
            if ($user['role'] === 'admin' && $user['is_active']) {
                $admin_count = getRecord("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1")['count'];
                if ($admin_count <= 1) {
                    throw new Exception('Cannot deactivate the last active administrator');
                }
            }
            
            $new_status = !$user['is_active'];
            $success = updateUser($user_id, ['is_active' => $new_status]);
            
            if (!$success) {
                throw new Exception('Failed to update user status');
            }
            
            // Log activity
            $status_text = $new_status ? 'activated' : 'deactivated';
            logActivity(getCurrentUserId(), 'UPDATE', 'users', $user_id, 
                       "Status changed: {$user['username']} $status_text");
            
            $response['success'] = true;
            $response['message'] = "User {$status_text} successfully";
            $response['new_status'] = $new_status;
            break;
            
        case 'bulk_action':
            // Verify CSRF token
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid security token');
            }
            
            $bulk_action = $_POST['bulk_action'] ?? '';
            $user_ids = $_POST['user_ids'] ?? [];
            
            if (empty($bulk_action)) {
                throw new Exception('Bulk action is required');
            }
            
            if (empty($user_ids) || !is_array($user_ids)) {
                throw new Exception('No users selected');
            }
            
            // Remove current user from bulk operations
            $current_user_id = getCurrentUserId();
            $user_ids = array_filter($user_ids, function($id) use ($current_user_id) {
                return (int)$id !== $current_user_id;
            });
            
            if (empty($user_ids)) {
                throw new Exception('No valid users selected for bulk operation');
            }
            
            $success_count = 0;
            $error_count = 0;
            
            foreach ($user_ids as $user_id) {
                $user_id = (int)$user_id;
                $user = getUserById($user_id);
                
                if (!$user) {
                    $error_count++;
                    continue;
                }
                
                try {
                    switch ($bulk_action) {
                        case 'activate':
                            updateUser($user_id, ['is_active' => true]);
                            logActivity(getCurrentUserId(), 'UPDATE', 'users', $user_id, 
                                       "Bulk activated: {$user['username']}");
                            $success_count++;
                            break;
                            
                        case 'deactivate':
                            // Prevent deactivating admins if it would leave no active admins
                            if ($user['role'] === 'admin') {
                                $admin_count = getRecord("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1 AND id NOT IN (" . implode(',', array_fill(0, count($user_ids), '?')) . ")", $user_ids)['count'];
                                if ($admin_count < 1) {
                                    $error_count++;
                                    continue 2; // Skip this user
                                }
                            }
                            
                            updateUser($user_id, ['is_active' => false]);
                            logActivity(getCurrentUserId(), 'UPDATE', 'users', $user_id, 
                                       "Bulk deactivated: {$user['username']}");
                            $success_count++;
                            break;
                            
                        case 'delete':
                            // Same admin check as deactivate
                            if ($user['role'] === 'admin') {
                                $admin_count = getRecord("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1 AND id NOT IN (" . implode(',', array_fill(0, count($user_ids), '?')) . ")", $user_ids)['count'];
                                if ($admin_count < 1) {
                                    $error_count++;
                                    continue 2;
                                }
                            }
                            
                            deleteUser($user_id, true); // Soft delete
                            logActivity(getCurrentUserId(), 'DELETE', 'users', $user_id, 
                                       "Bulk deleted: {$user['username']}");
                            $success_count++;
                            break;
                            
                        default:
                            $error_count++;
                    }
                } catch (Exception $e) {
                    $error_count++;
                }
            }
            
            $response['success'] = true;
            $response['message'] = "Bulk operation completed. $success_count successful, $error_count failed.";
            $response['success_count'] = $success_count;
            $response['error_count'] = $error_count;
            break;
            
        case 'get_user_stats':
            $stats = [
                'total_users' => getRecord("SELECT COUNT(*) as count FROM users")['count'],
                'active_users' => getRecord("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'],
                'inactive_users' => getRecord("SELECT COUNT(*) as count FROM users WHERE is_active = 0")['count'],
                'admin_users' => getRecord("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1")['count'],
                'regular_users' => getRecord("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND is_active = 1")['count'],
            ];
            
            // Recent registrations (last 30 days)
            $stats['recent_registrations'] = getRecord("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'];
            
            $response['success'] = true;
            $response['stats'] = $stats;
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Log error for debugging
    logMessage("User process error [$action]: " . $e->getMessage(), 'ERROR');
}

// Return JSON response
echo json_encode($response);
exit;
?>
