<?php
/**
 * Admin Product Management - Process Handler
 * AJAX handler untuk operasi produk
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
        case 'get_product':
            $product_id = (int)($_GET['id'] ?? 0);
            if (!$product_id) {
                throw new Exception('Product ID is required');
            }
            
            $sql = "SELECT p.*, c.name as category_name, s.name as supplier_name
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    LEFT JOIN suppliers s ON p.supplier_id = s.id
                    WHERE p.id = ?";
            
            $product = getRecord($sql, [$product_id]);
            if (!$product) {
                throw new Exception('Product not found');
            }
            
            $response['success'] = true;
            $response['product'] = $product;
            break;
            
        case 'check_sku':
            $sku = $_POST['sku'] ?? '';
            $product_id = (int)($_POST['product_id'] ?? 0);
            
            if (empty($sku)) {
                $response['success'] = true;
                $response['available'] = true;
                $response['message'] = 'SKU is optional';
                break;
            }
            
            $sql = "SELECT id FROM products WHERE sku = ?";
            $params = [$sku];
            
            if ($product_id) {
                $sql .= " AND id != ?";
                $params[] = $product_id;
            }
            
            $existing = getRecord($sql, $params);
            
            $response['success'] = true;
            $response['available'] = !$existing;
            $response['message'] = $existing ? 'SKU already exists' : 'SKU is available';
            break;
            
        case 'toggle_status':
            // Verify CSRF token
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid security token');
            }
            
            $product_id = (int)($_POST['product_id'] ?? 0);
            if (!$product_id) {
                throw new Exception('Product ID is required');
            }
            
            $product = getRecord("SELECT * FROM products WHERE id = ?", [$product_id]);
            if (!$product) {
                throw new Exception('Product not found');
            }
            
            $new_status = !$product['is_active'];
            $success = updateRecord("UPDATE products SET is_active = ? WHERE id = ?", [$new_status, $product_id]);
            
            if (!$success) {
                throw new Exception('Failed to update product status');
            }
            
            // Log activity
            $status_text = $new_status ? 'activated' : 'deactivated';
            logActivity(getCurrentUserId(), 'UPDATE', 'products', $product_id, 
                       "Status changed: {$product['name']} $status_text");
            
            $response['success'] = true;
            $response['message'] = "Product {$status_text} successfully";
            $response['new_status'] = $new_status;
            break;
            
        case 'delete':
            // Verify CSRF token
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                setFlashMessage('Invalid security token.', 'error');
                header('Location: index.php');
                exit;
            }
            
            $product_id = (int)($_POST['product_id'] ?? 0);
            if (!$product_id) {
                setFlashMessage('Product ID is required.', 'error');
                header('Location: index.php');
                exit;
            }
            
            $product = getRecord("SELECT * FROM products WHERE id = ?", [$product_id]);
            if (!$product) {
                setFlashMessage('Product not found.', 'error');
                header('Location: index.php');
                exit;
            }
            
            // Check if product is used in orders
            $order_count = getRecord("SELECT COUNT(*) as count FROM order_details WHERE product_id = ?", [$product_id])['count'];
            if ($order_count > 0) {
                setFlashMessage('Cannot delete product that has been ordered. You can deactivate it instead.', 'error');
                header('Location: index.php');
                exit;
            }
            
            // Delete product image if exists
            if ($product['image'] && file_exists(UPLOAD_PATH . '/' . $product['image'])) {
                unlink(UPLOAD_PATH . '/' . $product['image']);
            }
            
            // Delete product
            $success = updateRecord("DELETE FROM products WHERE id = ?", [$product_id]);
            
            if ($success) {
                // Log activity
                logActivity(getCurrentUserId(), 'DELETE', 'products', $product_id, 
                           "Deleted product: {$product['name']} (SKU: {$product['sku']})");
                
                setFlashMessage("Product '{$product['name']}' has been deleted successfully.", 'success');
            } else {
                setFlashMessage('Failed to delete product. Please try again.', 'error');
            }
            
            header('Location: index.php');
            exit;
            break;
            
        case 'bulk_action':
            // Verify CSRF token
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid security token');
            }
            
            $bulk_action = $_POST['bulk_action'] ?? '';
            $product_ids = $_POST['product_ids'] ?? [];
            
            if (empty($bulk_action)) {
                throw new Exception('Bulk action is required');
            }
            
            if (empty($product_ids) || !is_array($product_ids)) {
                throw new Exception('No products selected');
            }
            
            $success_count = 0;
            $error_count = 0;
            
            foreach ($product_ids as $product_id) {
                $product_id = (int)$product_id;
                $product = getRecord("SELECT * FROM products WHERE id = ?", [$product_id]);
                
                if (!$product) {
                    $error_count++;
                    continue;
                }
                
                try {
                    switch ($bulk_action) {
                        case 'activate':
                            updateRecord("UPDATE products SET is_active = 1 WHERE id = ?", [$product_id]);
                            logActivity(getCurrentUserId(), 'UPDATE', 'products', $product_id, 
                                       "Bulk activated: {$product['name']}");
                            $success_count++;
                            break;
                            
                        case 'deactivate':
                            updateRecord("UPDATE products SET is_active = 0 WHERE id = ?", [$product_id]);
                            logActivity(getCurrentUserId(), 'UPDATE', 'products', $product_id, 
                                       "Bulk deactivated: {$product['name']}");
                            $success_count++;
                            break;
                            
                        case 'delete':
                            // Check if product is used in orders
                            $order_count = getRecord("SELECT COUNT(*) as count FROM order_details WHERE product_id = ?", [$product_id])['count'];
                            if ($order_count > 0) {
                                $error_count++;
                                continue 2;
                            }
                            
                            // Delete product image if exists
                            if ($product['image'] && file_exists(UPLOAD_PATH . '/' . $product['image'])) {
                                unlink(UPLOAD_PATH . '/' . $product['image']);
                            }
                            
                            updateRecord("DELETE FROM products WHERE id = ?", [$product_id]);
                            logActivity(getCurrentUserId(), 'DELETE', 'products', $product_id, 
                                       "Bulk deleted: {$product['name']}");
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
            
        case 'get_product_stats':
            $stats = [
                'total_products' => getRecord("SELECT COUNT(*) as count FROM products")['count'],
                'active_products' => getRecord("SELECT COUNT(*) as count FROM products WHERE is_active = 1")['count'],
                'inactive_products' => getRecord("SELECT COUNT(*) as count FROM products WHERE is_active = 0")['count'],
                'low_stock' => getRecord("SELECT COUNT(*) as count FROM products WHERE stock <= min_stock AND stock > 0")['count'],
                'out_of_stock' => getRecord("SELECT COUNT(*) as count FROM products WHERE stock = 0")['count'],
                'total_value' => getRecord("SELECT SUM(price * stock) as total FROM products WHERE is_active = 1")['total'] ?: 0,
            ];
            
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
    logMessage("Product process error [$action]: " . $e->getMessage(), 'ERROR');
}

// Return JSON response
echo json_encode($response);
exit;
?>
