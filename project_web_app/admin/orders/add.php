<?php
/**
 * Admin Order Management - Add Order
 * Form untuk menambah order baru
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check admin access
requireAdmin();

// Page configuration
$page_title = 'Add Order - ' . APP_NAME;
$is_admin_page = true;

$errors = [];
$form_data = [
    'customer_id' => '',
    'order_date' => date('Y-m-d'),
    'status' => 'pending',
    'notes' => '',
    'products' => []
];

// Get customers for form
$customers = getRecords("SELECT id, name, email FROM customers WHERE is_active = 1 ORDER BY name");

// Get products for form
$products = getRecords("SELECT id, name, price FROM products WHERE is_active = 1 ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = array_merge($form_data, sanitizeInput($_POST));
    
    // Validation
    if (empty($form_data['customer_id'])) {
        $errors['customer_id'] = 'Customer is required';
    }
    
    if (empty($form_data['order_date'])) {
        $errors['order_date'] = 'Order date is required';
    }
    
    // Validate products
    if (empty($form_data['products']) || !is_array($form_data['products'])) {
        $errors['products'] = 'At least one product is required';
    } else {
        foreach ($form_data['products'] as $product) {
            if (empty($product['quantity']) || $product['quantity'] <= 0) {
                $errors['products'] = 'All products must have valid quantities';
                break;
            }
        }
    }
    
    // CSRF validation
    if (!verifyCSRFToken($form_data['csrf_token'])) {
        $errors['csrf'] = 'Invalid security token';
    }
    
    // If no errors, create the order
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo = getDB();
            $pdo->beginTransaction();
            
            // Create order
            $sql = "INSERT INTO orders (customer_id, user_id, order_number, total_amount, status, payment_status, order_date, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Calculate total_amount
            $total_amount = 0;
            foreach ($form_data['products'] as $product) {
                if (!empty($product['product_id']) && !empty($product['quantity'])) {
                    $product_info = getRecord("SELECT price FROM products WHERE id = ?", [$product['product_id']]);
                    $total_amount += $product_info['price'] * $product['quantity'];
                }
            }
            
            $params = [
                $form_data['customer_id'],
                getCurrentUserId(),
                $order_number,
                $total_amount,
                $form_data['status'],
                ($form_data['status'] === 'completed' ? 'paid' : 'unpaid'),
                $form_data['order_date'],
                $form_data['notes']
            ];
            
            $order_id = insertRecord($sql, $params);
            
            if ($order_id) {
                // Add order details
                foreach ($form_data['products'] as $product) {
                    if (!empty($product['product_id']) && !empty($product['quantity'])) {
                        $product_info = getRecord("SELECT price FROM products WHERE id = ?", [$product['product_id']]);
                        $detail_sql = "INSERT INTO order_details (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)";
                        insertRecord($detail_sql, [
                            $order_id,
                            $product['product_id'],
                            $product['quantity'],
                            $product_info['price'],
                            $product_info['price'] * $product['quantity']
                        ]);
                    }
                }
                
                $pdo->commit();
                
                // Log activity
                logActivity(getCurrentUserId(), 'CREATE', 'orders', $order_id, 
                           "Created order: $order_number");
                
                setFlashMessage("Order '$order_number' has been created successfully!", 'success');
                header('Location: index.php');
                exit;
            } else {
                $pdo->rollBack();
                $errors['general'] = 'Failed to create order. Please try again.';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['general'] = 'An error occurred while creating the order.';
            logMessage("Order creation error: " . $e->getMessage(), 'ERROR');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $additional_css = [ASSETS_URL . '/css/admin.css'];
    include '../../includes/header.php'; 
    ?>
</head>
<body class="admin-body">
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include '../../includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="admin-content">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-left">
                    <h1>Add Order</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Orders</a></li>
                            <li class="breadcrumb-item active">Add Order</li>
                        </ol>
                    </nav>
                </div>
                
                <div class="header-controls">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                </div>
            </header>
            
            <!-- Main Content -->
            <div class="admin-main">
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($errors['general']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="admin-form-container">
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-plus me-2"></i>Order Information
                        </div>
                        
                        <form method="POST" action="" id="addOrderForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="row g-3">
                                <!-- Customer Selection -->
                                <div class="col-md-6">
                                    <label class="form-label" for="customer_id">Customer <span class="text-danger">*</span></label>
                                    <select class="form-select <?= isset($errors['customer_id']) ? 'is-invalid' : '' ?>" 
                                            id="customer_id" 
                                            name="customer_id" 
                                            required>
                                        <option value="">Select Customer</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?= $customer['id'] ?>" <?= $form_data['customer_id'] == $customer['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($customer['name']) ?> (<?= htmlspecialchars($customer['email']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['customer_id'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['customer_id']) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Order Date -->
                                <div class="col-md-6">
                                    <label class="form-label" for="order_date">Order Date <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           class="form-control <?= isset($errors['order_date']) ? 'is-invalid' : '' ?>" 
                                           id="order_date" 
                                           name="order_date" 
                                           value="<?= htmlspecialchars($form_data['order_date']) ?>" 
                                           required>
                                    <?php if (isset($errors['order_date'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['order_date']) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Status -->
                                <div class="col-md-6">
                                    <label class="form-label" for="status">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="pending" <?= $form_data['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= $form_data['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="completed" <?= $form_data['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $form_data['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                
                                <!-- Notes -->
                                <div class="col-12">
                                    <label class="form-label" for="notes">Notes</label>
                                    <textarea class="form-control" 
                                              id="notes" 
                                              name="notes" 
                                              rows="3" 
                                              placeholder="Optional order notes"><?= htmlspecialchars($form_data['notes']) ?></textarea>
                                </div>
                                
                                <!-- Products Section -->
                                <div class="col-12">
                                    <label class="form-label">Products <span class="text-danger">*</span></label>
                                    <?php if (isset($errors['products'])): ?>
                                        <div class="text-danger small mb-2"><?= htmlspecialchars($errors['products']) ?></div>
                                    <?php endif; ?>
                                    
                                    <div id="products-container">
                                        <div class="product-item border rounded p-3 mb-3">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-6">
                                                    <label class="form-label">Product</label>
                                                    <select class="form-select" name="products[0][product_id]" required>
                                                        <option value="">Select Product</option>
                                                        <?php foreach ($products as $product): ?>
                                                            <option value="<?= $product['id'] ?>">
                                                                <?= htmlspecialchars($product['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Quantity</label>
                                                    <input type="number" class="form-control" name="products[0][quantity]" min="1" value="1" required>
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-danger btn-sm remove-product" disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>Create Order
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="reset" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-2"></i>Reset Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php 
    $page_js = "
        let productIndex = 1;
        
        // Add product functionality
        document.getElementById('add-product').addEventListener('click', function() {
            const container = document.getElementById('products-container');
            const newProduct = document.querySelector('.product-item').cloneNode(true);
            
            // Update names and reset values
            newProduct.querySelectorAll('select, input').forEach(input => {
                if (input.name) {
                    input.name = input.name.replace('[0]', '[' + productIndex + ']');
                }
                if (input.type !== 'button') {
                    input.value = input.type === 'number' ? '1' : '';
                }
            });
            
            // Enable remove button
            newProduct.querySelector('.remove-product').disabled = false;
            
            container.appendChild(newProduct);
            productIndex++;
            
            // Add event listeners
            attachProductEventListeners(newProduct);
        });
        
        // Remove product functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-product') || e.target.closest('.remove-product')) {
                e.target.closest('.product-item').remove();
                
                // Disable remove button if only one product left
                const productItems = document.querySelectorAll('.product-item');
                if (productItems.length === 1) {
                    productItems[0].querySelector('.remove-product').disabled = true;
                }
            }
        });
        
        // Attach event listeners to product items
        function attachProductEventListeners(item) {
            const select = item.querySelector('select');
            const priceInput = item.querySelector('input[readonly]');
            
            select.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                priceInput.value = price ? formatCurrency(price) : '';
            });
        }
        
        // Initialize existing product items
        document.querySelectorAll('.product-item').forEach(attachProductEventListeners);
        
        // Form validation
        document.getElementById('addOrderForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin me-2\"></i>Creating...';
        });
        
        // Format currency function
        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(amount);
        }
    ";
    ?>
</body>
</html>
