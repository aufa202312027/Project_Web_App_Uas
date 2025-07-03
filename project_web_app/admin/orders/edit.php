<?php
/**
 * Admin Order Management - Edit Order
 * Form untuk mengedit order
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check admin access
requireAdmin();

// Page configuration
$page_title = 'Edit Order - ' . APP_NAME;
$is_admin_page = true;

$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) {
    setFlashMessage('Order ID tidak valid.', 'danger');
    header('Location: index.php');
    exit;
}

// Ambil data order
$order = getRecord("SELECT * FROM orders WHERE id = ?", [$order_id]);
if (!$order) {
    setFlashMessage('Order tidak ditemukan.', 'danger');
    header('Location: index.php');
    exit;
}

// Ambil detail produk order
$order_details = getRecords("SELECT od.*, p.name, p.price FROM order_details od LEFT JOIN products p ON od.product_id = p.id WHERE od.order_id = ?", [$order_id]);

// Get customers for form
$customers = getRecords("SELECT id, name, email FROM customers WHERE is_active = 1 ORDER BY name");
// Get products for form
$products = getRecords("SELECT id, name, price FROM products WHERE is_active = 1 ORDER BY name");

$errors = [];
$form_data = [
    'customer_id' => $order['customer_id'],
    'order_date' => date('Y-m-d', strtotime($order['order_date'])),
    'status' => $order['status'],
    'notes' => $order['notes'],
    'products' => []
];
foreach ($order_details as $i => $detail) {
    $form_data['products'][] = [
        'product_id' => $detail['product_id'],
        'quantity' => $detail['quantity']
    ];
}

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
    // If no errors, update the order
    if (empty($errors)) {
        try {
            $pdo = getDB();
            $pdo->beginTransaction();
            // Update order
            $sql = "UPDATE orders SET customer_id=?, order_date=?, status=?, notes=? WHERE id=?";
            $params = [
                $form_data['customer_id'],
                $form_data['order_date'],
                $form_data['status'],
                $form_data['notes'],
                $order_id
            ];
            updateRecord($sql, $params);
            // Hapus detail lama
            $pdo->prepare("DELETE FROM order_details WHERE order_id = ?")->execute([$order_id]);
            // Tambah detail baru
            foreach ($form_data['products'] as $product) {
                if (!empty($product['product_id']) && !empty($product['quantity'])) {
                    $product_info = getRecord("SELECT price FROM products WHERE id = ?", [$product['product_id']]);
                    $detail_sql = "INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                    insertRecord($detail_sql, [
                        $order_id,
                        $product['product_id'],
                        $product['quantity'],
                        $product_info['price']
                    ]);
                }
            }
            $pdo->commit();
            logActivity(getCurrentUserId(), 'UPDATE', 'orders', $order_id, "Updated order: {$order['order_number']}");
            setFlashMessage("Order '{$order['order_number']}' has been updated!", 'success');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['general'] = 'An error occurred while updating the order.';
            logMessage("Order update error: " . $e->getMessage(), 'ERROR');
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
        <?php include '../../includes/admin_sidebar.php'; ?>
        <main class="admin-content">
            <header class="admin-header">
                <div class="header-left">
                    <h1>Edit Order</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Orders</a></li>
                            <li class="breadcrumb-item active">Edit Order</li>
                        </ol>
                    </nav>
                </div>
                <div class="header-controls">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                </div>
            </header>
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
                            <i class="fas fa-edit me-2"></i>Edit Order Information
                        </div>
                        <form method="POST" action="" id="editOrderForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="customer_id">Customer <span class="text-danger">*</span></label>
                                    <select class="form-select <?= isset($errors['customer_id']) ? 'is-invalid' : '' ?>" id="customer_id" name="customer_id" required>
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
                                <div class="col-md-6">
                                    <label class="form-label" for="order_date">Order Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control <?= isset($errors['order_date']) ? 'is-invalid' : '' ?>" id="order_date" name="order_date" value="<?= htmlspecialchars($form_data['order_date']) ?>" required>
                                    <?php if (isset($errors['order_date'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['order_date']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="status">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="pending" <?= $form_data['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= $form_data['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="completed" <?= $form_data['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $form_data['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="notes">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Optional order notes"><?= htmlspecialchars($form_data['notes']) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Products <span class="text-danger">*</span></label>
                                    <?php if (isset($errors['products'])): ?>
                                        <div class="text-danger small mb-2"><?= htmlspecialchars($errors['products']) ?></div>
                                    <?php endif; ?>
                                    <div id="products-container">
                                        <?php foreach ($form_data['products'] as $i => $product): ?>
                                        <div class="product-item border rounded p-3 mb-3">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-6">
                                                    <label class="form-label">Product</label>
                                                    <select class="form-select" name="products[<?= $i ?>][product_id]" required>
                                                        <option value="">Select Product</option>
                                                        <?php foreach ($products as $prod): ?>
                                                            <option value="<?= $prod['id'] ?>" data-price="<?= $prod['price'] ?>" <?= $product['product_id'] == $prod['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($prod['name']) ?> - <?= formatCurrency($prod['price']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Quantity</label>
                                                    <input type="number" class="form-control" name="products[<?= $i ?>][quantity]" min="1" value="<?= $product['quantity'] ?>" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Price</label>
                                                    <input type="text" class="form-control" value="<?= isset($product['product_id']) ? formatCurrency(array_column($products, 'price', 'id')[$product['product_id']]) : '' ?>" readonly>
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-danger btn-sm remove-product" <?= count($form_data['products']) == 1 ? 'disabled' : '' ?>>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>Update Order
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
        let productIndex = " . count($form_data['products']) . ";
        document.getElementById('add-product').addEventListener('click', function() {
            const container = document.getElementById('products-container');
            const newProduct = document.querySelector('.product-item').cloneNode(true);
            newProduct.querySelectorAll('select, input').forEach(input => {
                if (input.name) {
                    input.name = input.name.replace(/\[\d+\]/, '[' + productIndex + ']');
                }
                if (input.type !== 'button') {
                    input.value = input.type === 'number' ? '1' : '';
                }
            });
            newProduct.querySelector('.remove-product').disabled = false;
            container.appendChild(newProduct);
            productIndex++;
            attachProductEventListeners(newProduct);
        });
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-product') || e.target.closest('.remove-product')) {
                e.target.closest('.product-item').remove();
                const productItems = document.querySelectorAll('.product-item');
                if (productItems.length === 1) {
                    productItems[0].querySelector('.remove-product').disabled = true;
                }
            }
        });
        function attachProductEventListeners(item) {
            const select = item.querySelector('select');
            const priceInput = item.querySelector('input[readonly]');
            select.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                priceInput.value = price ? formatCurrency(price) : '';
            });
        }
        document.querySelectorAll('.product-item').forEach(attachProductEventListeners);
        document.getElementById('editOrderForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin me-2\"></i>Updating...';
        });
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
