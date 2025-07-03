<?php
/**
 * Admin Product Management - Add Product
 * Form untuk menambah produk baru
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check admin access
requireAdmin();

// Page configuration
$page_title = 'Add Product - ' . APP_NAME;
$is_admin_page = true;

$errors = [];
$form_data = [
    'name' => '',
    'sku' => '',
    'description' => '',
    'category_id' => '',
    'supplier_id' => '',
    'price' => '',
    'cost' => '',
    'stock' => '',
    'min_stock' => '',
    'weight' => '',
    'dimensions' => '',
    'is_active' => '1'
];

// Get categories and suppliers for form
$categories = getRecords("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$suppliers = getRecords("SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = array_merge($form_data, sanitizeInput($_POST));
    
    // Validation
    if (empty($form_data['name'])) {
        $errors['name'] = 'Product name is required';
    } elseif (strlen($form_data['name']) > 255) {
        $errors['name'] = 'Product name must not exceed 255 characters';
    }
    
    if (!empty($form_data['sku'])) {
        // Check if SKU exists
        $existing = getRecord("SELECT id FROM products WHERE sku = ?", [$form_data['sku']]);
        if ($existing) {
            $errors['sku'] = 'SKU already exists';
        }
    }
    
    if (empty($form_data['category_id'])) {
        $errors['category_id'] = 'Category is required';
    }
    
    if (empty($form_data['price']) || !is_numeric($form_data['price']) || $form_data['price'] < 0) {
        $errors['price'] = 'Valid price is required';
    }
    
    if (!empty($form_data['cost']) && (!is_numeric($form_data['cost']) || $form_data['cost'] < 0)) {
        $errors['cost'] = 'Cost must be a valid number';
    }
    
    if (empty($form_data['stock']) || !is_numeric($form_data['stock']) || $form_data['stock'] < 0) {
        $errors['stock'] = 'Valid stock quantity is required';
    }
    
    if (empty($form_data['min_stock']) || !is_numeric($form_data['min_stock']) || $form_data['min_stock'] < 0) {
        $errors['min_stock'] = 'Valid minimum stock is required';
    }
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Invalid security token';
    }
    
    // Handle file upload
    $image_filename = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['image'], UPLOAD_PATH, ['jpg', 'jpeg', 'png', 'gif']);
        if ($upload_result) {
            $image_filename = $upload_result;
        } else {
            $errors['image'] = 'Failed to upload image. Please check file size and format.';
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File is too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File is too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $errors['image'] = $upload_errors[$_FILES['image']['error']] ?? 'Unknown upload error';
    }
    
    // If no errors, create the product
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO products (name, sku, description, category_id, supplier_id, price, cost, stock, min_stock, weight, dimensions, image, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $form_data['name'],
                $form_data['sku'] ?: null,
                $form_data['description'],
                $form_data['category_id'],
                $form_data['supplier_id'] ?: null,
                $form_data['price'],
                $form_data['cost'] ?: null,
                $form_data['stock'],
                $form_data['min_stock'],
                $form_data['weight'] ?: null,
                $form_data['dimensions'] ?: null,
                $image_filename,
                (bool)$form_data['is_active']
            ];
            
            $product_id = insertRecord($sql, $params);
            
            if ($product_id) {
                // Log activity
                logActivity(getCurrentUserId(), 'CREATE', 'products', $product_id, 
                           "Created product: {$form_data['name']}");
                
                setFlashMessage("Product '{$form_data['name']}' has been created successfully!", 'success');
                header('Location: index.php');
                exit;
            } else {
                $errors['general'] = 'Failed to create product. Please try again.';
            }
        } catch (Exception $e) {
            $errors['general'] = 'An error occurred while creating the product.';
            logMessage("Product creation error: " . $e->getMessage(), 'ERROR');
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
                    <h1>Add Product</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Products</a></li>
                            <li class="breadcrumb-item active">Add Product</li>
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
                            <i class="fas fa-plus me-2"></i>Product Information
                        </div>
                        
                        <form method="POST" action="" enctype="multipart/form-data" id="addProductForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="form-grid">
                                <!-- Product Name -->
                                <div class="form-group">
                                    <label class="form-label required" for="name">Product Name</label>
                                    <input type="text" 
                                           class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                           id="name" 
                                           name="name" 
                                           value="<?= htmlspecialchars($form_data['name']) ?>"
                                           required>
                                    <?php if (isset($errors['name'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- SKU -->
                                <div class="form-group">
                                    <label class="form-label" for="sku">SKU</label>
                                    <input type="text" 
                                           class="form-control <?= isset($errors['sku']) ? 'is-invalid' : '' ?>" 
                                           id="sku" 
                                           name="sku" 
                                           value="<?= htmlspecialchars($form_data['sku']) ?>">
                                    <?php if (isset($errors['sku'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['sku']) ?></div>
                                    <?php endif; ?>
                                    <div class="form-help">Optional unique product identifier</div>
                                </div>
                                
                                <!-- Category -->
                                <div class="form-group">
                                    <label class="form-label required" for="category_id">Category</label>
                                    <select class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" 
                                            id="category_id" 
                                            name="category_id" 
                                            required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" <?= $form_data['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['category_id'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['category_id']) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Supplier -->
                                <div class="form-group">
                                    <label class="form-label" for="supplier_id">Supplier</label>
                                    <select class="form-select <?= isset($errors['supplier_id']) ? 'is-invalid' : '' ?>" 
                                            id="supplier_id" 
                                            name="supplier_id">
                                        <option value="">Select Supplier</option>
                                        <?php foreach ($suppliers as $supplier): ?>
                                            <option value="<?= $supplier['id'] ?>" <?= $form_data['supplier_id'] == $supplier['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($supplier['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['supplier_id'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['supplier_id']) ?></div>
                                    <?php endif; ?>
                                    <div class="form-help">Optional supplier information</div>
                                </div>
                                
                                <!-- Price -->
                                <div class="form-group">
                                    <label class="form-label required" for="price">Selling Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?= CURRENCY_SYMBOL ?></span>
                                        <input type="number" 
                                               class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>" 
                                               id="price" 
                                               name="price" 
                                               value="<?= htmlspecialchars($form_data['price']) ?>"
                                               min="0"
                                               step="0.01"
                                               required>
                                        <?php if (isset($errors['price'])): ?>
                                            <div class="invalid-feedback"><?= htmlspecialchars($errors['price']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Cost -->
                                <div class="form-group">
                                    <label class="form-label" for="cost">Cost Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?= CURRENCY_SYMBOL ?></span>
                                        <input type="number" 
                                               class="form-control <?= isset($errors['cost']) ? 'is-invalid' : '' ?>" 
                                               id="cost" 
                                               name="cost" 
                                               value="<?= htmlspecialchars($form_data['cost']) ?>"
                                               min="0"
                                               step="0.01">
                                        <?php if (isset($errors['cost'])): ?>
                                            <div class="invalid-feedback"><?= htmlspecialchars($errors['cost']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-help">Optional cost for profit calculation</div>
                                </div>
                                
                                <!-- Stock -->
                                <div class="form-group">
                                    <label class="form-label required" for="stock">Stock Quantity</label>
                                    <input type="number" 
                                           class="form-control <?= isset($errors['stock']) ? 'is-invalid' : '' ?>" 
                                           id="stock" 
                                           name="stock" 
                                           value="<?= htmlspecialchars($form_data['stock']) ?>"
                                           min="0"
                                           required>
                                    <?php if (isset($errors['stock'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['stock']) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Min Stock -->
                                <div class="form-group">
                                    <label class="form-label required" for="min_stock">Minimum Stock</label>
                                    <input type="number" 
                                           class="form-control <?= isset($errors['min_stock']) ? 'is-invalid' : '' ?>" 
                                           id="min_stock" 
                                           name="min_stock" 
                                           value="<?= htmlspecialchars($form_data['min_stock']) ?>"
                                           min="0"
                                           required>
                                    <?php if (isset($errors['min_stock'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['min_stock']) ?></div>
                                    <?php endif; ?>
                                    <div class="form-help">Alert when stock reaches this level</div>
                                </div>
                                
                                <!-- Description -->
                                <div class="form-group col-span-2">
                                    <label class="form-label" for="description">Description</label>
                                    <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                              id="description" 
                                              name="description" 
                                              rows="4"><?= htmlspecialchars($form_data['description']) ?></textarea>
                                    <?php if (isset($errors['description'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Product Image -->
                                <div class="form-group">
                                    <label class="form-label" for="image">Product Image</label>
                                    <input type="file" 
                                           class="form-control <?= isset($errors['image']) ? 'is-invalid' : '' ?>" 
                                           id="image" 
                                           name="image" 
                                           accept="image/*">
                                    <?php if (isset($errors['image'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['image']) ?></div>
                                    <?php endif; ?>
                                    <div class="form-help">Optional product image (JPG, PNG, GIF)</div>
                                </div>
                                
                                <!-- Status -->
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="is_active" 
                                               name="is_active" 
                                               value="1" 
                                               <?= $form_data['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">
                                            Active Product
                                        </label>
                                    </div>
                                    <div class="form-help">Inactive products are hidden from listings</div>
                                </div>
                            </div>
                            
                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>Create Product
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
        // Form validation
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin me-2\"></i>Creating Product...';
        });
        
        // Image preview
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Add image preview if needed
                };
                reader.readAsDataURL(file);
            }
        });
        
        // SKU generation suggestion
        document.getElementById('name').addEventListener('input', function() {
            const name = this.value;
            const skuField = document.getElementById('sku');
            if (name && !skuField.value) {
                // Generate SKU suggestion from product name
                const sku = name.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 10);
                skuField.placeholder = 'Suggested: ' + sku;
            }
        });
    ";
    ?>
</body>
</html>
