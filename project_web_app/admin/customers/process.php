<?php
/**
 * Customer Management - Process Actions
 * Handle semua operasi customer (create, update, delete)
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Require admin access
requireAdmin();

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('Invalid security token. Please try again.', 'error');
    header('Location: index.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            // Validate required fields
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $is_active = (int)($_POST['is_active'] ?? 1);

            if (empty($name)) {
                throw new Exception('Customer name is required.');
            }

            // Validate email if provided
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please provide a valid email address.');
            }

            // Check if email already exists
            if (!empty($email)) {
                $existing = getRecord("SELECT id FROM customers WHERE email = ?", [$email]);
                if ($existing) {
                    throw new Exception('Email address already exists.');
                }
            }

            // Insert new customer
            $sql = "INSERT INTO customers (name, email, phone, city, postal_code, address, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [$name, $email, $phone, $city, $postal_code, $address, $is_active];
            
            if (executeQuery($sql, $params)) {
                setFlashMessage('Customer created successfully.', 'success');
                header('Location: index.php');
            } else {
                throw new Exception('Failed to create customer.');
            }
            break;

        case 'update':
            $customer_id = (int)($_POST['customer_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $is_active = (int)($_POST['is_active'] ?? 1);

            if ($customer_id <= 0) {
                throw new Exception('Invalid customer ID.');
            }

            if (empty($name)) {
                throw new Exception('Customer name is required.');
            }

            // Validate email if provided
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please provide a valid email address.');
            }

            // Check if email already exists (exclude current customer)
            if (!empty($email)) {
                $existing = getRecord("SELECT id FROM customers WHERE email = ? AND id != ?", [$email, $customer_id]);
                if ($existing) {
                    throw new Exception('Email address already exists.');
                }
            }

            // Verify customer exists
            $customer = getRecord("SELECT id FROM customers WHERE id = ?", [$customer_id]);
            if (!$customer) {
                throw new Exception('Customer not found.');
            }

            // Update customer
            $sql = "UPDATE customers SET name = ?, email = ?, phone = ?, city = ?, postal_code = ?, address = ?, is_active = ? WHERE id = ?";
            $params = [$name, $email, $phone, $city, $postal_code, $address, $is_active, $customer_id];
            
            if (executeQuery($sql, $params)) {
                setFlashMessage('Customer updated successfully.', 'success');
                header('Location: view.php?id=' . $customer_id);
            } else {
                throw new Exception('Failed to update customer.');
            }
            break;

        case 'delete':
            $customer_id = (int)($_POST['customer_id'] ?? 0);

            if ($customer_id <= 0) {
                throw new Exception('Invalid customer ID.');
            }

            // Verify customer exists
            $customer = getRecord("SELECT id, name FROM customers WHERE id = ?", [$customer_id]);
            if (!$customer) {
                throw new Exception('Customer not found.');
            }

            // Check if customer has orders
            $orders = getRecord("SELECT COUNT(*) as count FROM orders WHERE customer_id = ?", [$customer_id]);
            if ($orders['count'] > 0) {
                throw new Exception('Cannot delete customer with existing orders. Please deactivate instead.');
            }

            // Delete customer
            if (executeQuery("DELETE FROM customers WHERE id = ?", [$customer_id])) {
                setFlashMessage('Customer deleted successfully.', 'success');
                header('Location: index.php');
            } else {
                throw new Exception('Failed to delete customer.');
            }
            break;

        case 'toggle_status':
            $customer_id = (int)($_POST['customer_id'] ?? 0);

            if ($customer_id <= 0) {
                throw new Exception('Invalid customer ID.');
            }

            // Get current status
            $customer = getRecord("SELECT is_active FROM customers WHERE id = ?", [$customer_id]);
            if (!$customer) {
                throw new Exception('Customer not found.');
            }

            $new_status = $customer['is_active'] ? 0 : 1;
            $status_text = $new_status ? 'activated' : 'deactivated';

            // Update status
            if (executeQuery("UPDATE customers SET is_active = ? WHERE id = ?", [$new_status, $customer_id])) {
                setFlashMessage("Customer {$status_text} successfully.", 'success');
            } else {
                throw new Exception("Failed to {$status_text} customer.");
            }

            header('Location: ' . ($_POST['redirect'] ?? 'index.php'));
            break;

        default:
            throw new Exception('Invalid action.');
    }

} catch (Exception $e) {
    error_log("Customer process error: " . $e->getMessage());
    setFlashMessage($e->getMessage(), 'error');
    
    // Redirect back to appropriate page
    $redirect = $_POST['redirect'] ?? 'index.php';
    header('Location: ' . $redirect);
}
exit;
