<?php
/**
 * Order Management - Process Actions
 * Handle order-related actions
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Require admin access
requireAdmin();

// Handle POST request only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verify CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($token)) {
    setFlashMessage('Invalid security token. Please try again.', 'error');
    header('Location: index.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'update_status':
            updateOrderStatus();
            break;
            
        case 'add_order':
            addOrder();
            break;
            
        case 'delete_order':
            deleteOrder();
            break;
            
        default:
            setFlashMessage('Invalid action.', 'error');
            break;
    }
} catch (Exception $e) {
    setFlashMessage('Error: ' . $e->getMessage(), 'error');
}

header('Location: index.php');
exit;

/**
 * Update order status
 */
function updateOrderStatus() {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if ($order_id <= 0) {
        throw new Exception('Invalid order ID.');
    }
    
    $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status.');
    }
    
    // Get current order info
    $order = getRecord("SELECT * FROM orders WHERE id = ?", [$order_id]);
    if (!$order) {
        throw new Exception('Order not found.');
    }
    
    // Update order status
    $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    $result = executeQuery($sql, [$status, $order_id]);
    
    if ($result === false) {
        throw new Exception('Failed to update order status.');
    }
    
    // Log activity
    $activity_description = "Updated order #{$order['order_number']} status from '{$order['status']}' to '{$status}'";
    if (!empty($notes)) {
        $activity_description .= " - Notes: {$notes}";
    }
    
    logActivity(
        getCurrentUserId(),
        'UPDATE',
        'orders',
        $order_id,
        $activity_description
    );
    
    setFlashMessage("Order status updated to '{$status}' successfully.", 'success');
}

/**
 * Add new order (basic implementation)
 */
function addOrder() {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $order_number = sanitizeInput($_POST['order_number'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'pending');
    
    if (empty($order_number)) {
        $order_number = generateOrderNumber();
    }
    
    // Basic validation
    if (!in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
        throw new Exception('Invalid status.');
    }
    
    // Insert order
    $sql = "INSERT INTO orders (customer_id, order_number, status, order_date) 
            VALUES (?, ?, ?, NOW())";
    
    $result = executeQuery($sql, [$customer_id ?: null, $order_number, $status]);
    
    if ($result === false) {
        throw new Exception('Failed to create order.');
    }
    
    $order_id = getLastInsertId();
    
    // Log activity
    logActivity(
        getCurrentUserId(),
        'CREATE',
        'orders',
        $order_id,
        "Created new order: {$order_number}"
    );
    
    setFlashMessage("Order '{$order_number}' created successfully.", 'success');
}

/**
 * Delete order by ID
 */
function deleteOrder() {
    $order_id = (int)($_POST['order_id'] ?? 0);
    if ($order_id <= 0) {
        throw new Exception('Invalid order ID.');
    }
    // Get order info
    $order = getRecord("SELECT * FROM orders WHERE id = ?", [$order_id]);
    if (!$order) {
        throw new Exception('Order not found.');
    }
    // Delete order (order_details and payments should be ON DELETE CASCADE or handled here if needed)
    $result = executeQuery("DELETE FROM orders WHERE id = ?", [$order_id]);
    if ($result === false) {
        throw new Exception('Failed to delete order.');
    }
    // Log activity
    logActivity(
        getCurrentUserId(),
        'DELETE',
        'orders',
        $order_id,
        "Deleted order: {$order['order_number']}"
    );
    setFlashMessage("Order '{$order['order_number']}' deleted successfully.", 'success');
}
