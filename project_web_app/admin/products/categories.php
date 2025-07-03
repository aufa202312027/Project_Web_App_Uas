<?php
/**
 * Admin Product Management - Categories
 * Halaman untuk mengelola kategori produk
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check admin access
requireAdmin();

// Page configuration
$page_title = 'Product Categories - ' . APP_NAME;
$is_admin_page = true;
$include_datatables = true;

// Handle search and filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = RECORDS_PER_PAGE;

// Build query conditions
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    if ($status_filter === 'active') {
        $where_conditions[] = "is_active = 1";
    } elseif ($status_filter === 'inactive') {
        $where_conditions[] = "is_active = 0";
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM categories $where_clause";
$total_records = getRecord($count_sql, $params)['total'];
$total_pages = ceil($total_records / $per_page);

// Get categories with pagination
$offset = ($page - 1) * $per_page;
$sql = "SELECT c.*, 
               (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count
        FROM categories c 
        $where_clause 
        ORDER BY c.created_at DESC 
        LIMIT $per_page OFFSET $offset";

$categories = getRecords($sql, $params);

// Get category statistics
$category_stats = [
    'total_categories' => getRecord("SELECT COUNT(*) as count FROM categories")['count'],
    'active_categories' => getRecord("SELECT COUNT(*) as count FROM categories WHERE is_active = 1")['count'],
    'inactive_categories' => getRecord("SELECT COUNT(*) as count FROM categories WHERE is_active = 0")['count'],
    'categories_with_products' => getRecord("SELECT COUNT(DISTINCT category_id) as count FROM products WHERE category_id IS NOT NULL")['count']
];

// Handle form submission for adding/editing categories
$errors = [];
$form_data = ['name' => '', 'description' => '', 'is_active' => '1'];
$edit_mode = false;
$edit_category = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $form_data = array_merge($form_data, sanitizeInput($_POST));
        
        // Validation
        if (empty($form_data['name'])) {
            $errors['name'] = 'Category name is required';
        } elseif (strlen($form_data['name']) > 100) {
            $errors['name'] = 'Category name must not exceed 100 characters';
        } else {
            // Check if name exists
            $existing_sql = "SELECT id FROM categories WHERE name = ?";
            $existing_params = [$form_data['name']];
            
            if ($action === 'edit' && !empty($_POST['category_id'])) {
                $existing_sql .= " AND id != ?";
                $existing_params[] = $_POST['category_id'];
            }
            
            $existing = getRecord($existing_sql, $existing_params);
            if ($existing) {
                $errors['name'] = 'Category name already exists';
            }
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors['csrf'] = 'Invalid security token';
        }
        
        if (empty($errors)) {
            try {
                if ($action === 'add') {
                    // Add new category
                    $sql = "INSERT INTO categories (name, description, is_active, created_at) VALUES (?, ?, ?, NOW())";
                    $params = [$form_data['name'], $form_data['description'], (bool)$form_data['is_active']];
                    $category_id = insertRecord($sql, $params);
                    
                    if ($category_id) {
                        logActivity(getCurrentUserId(), 'CREATE', 'categories', $category_id, 
                                   "Created category: {$form_data['name']}");
                        setFlashMessage("Category '{$form_data['name']}' has been created successfully!", 'success');
                    } else {
                        $errors['general'] = 'Failed to create category. Please try again.';
                    }
                } else {
                    // Edit existing category
                    $category_id = (int)$_POST['category_id'];
                    $sql = "UPDATE categories SET name = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ?";
                    $params = [$form_data['name'], $form_data['description'], (bool)$form_data['is_active'], $category_id];
                    $success = updateRecord($sql, $params);
                    
                    if ($success) {
                        logActivity(getCurrentUserId(), 'UPDATE', 'categories', $category_id, 
                                   "Updated category: {$form_data['name']}");
                        setFlashMessage("Category '{$form_data['name']}' has been updated successfully!", 'success');
                    } else {
                        $errors['general'] = 'Failed to update category. Please try again.';
                    }
                }
                
                if (empty($errors)) {
                    header('Location: categories.php');
                    exit;
                }
            } catch (Exception $e) {
                $errors['general'] = 'An error occurred while processing the category.';
                logMessage("Category processing error: " . $e->getMessage(), 'ERROR');
            }
        }
    }
}

// Check for edit mode
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_category = getRecord("SELECT * FROM categories WHERE id = ?", [$_GET['edit']]);
    if ($edit_category) {
        $edit_mode = true;
        $form_data = [
            'name' => $edit_category['name'],
            'description' => $edit_category['description'] ?? '',
            'is_active' => $edit_category['is_active']
        ];
    }
}

// Check for flash messages
$flash = getFlashMessage();
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
                    <h1>Product Categories</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Products</a></li>
                            <li class="breadcrumb-item active">Categories</li>
                        </ol>
                    </nav>
                </div>
                
                <div class="header-controls">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                        <i class="fas fa-plus me-2"></i>Add Category
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Products
                    </a>
                </div>
            </header>
            
            <!-- Main Content -->
            <div class="admin-main">
                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $flash['type'] === 'error' ? 'exclamation-triangle' : 'check-circle' ?> me-2"></i>
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card-admin">
                            <div class="stat-header">
                                <div class="stat-title">Total Categories</div>
                                <div class="stat-icon-admin">
                                    <i class="fas fa-tags"></i>
                                </div>
                            </div>
                            <div class="stat-value"><?= number_format($category_stats['total_categories']) ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-tags"></i> Categories
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card-admin success">
                            <div class="stat-header">
                                <div class="stat-title">Active Categories</div>
                                <div class="stat-icon-admin">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <div class="stat-value"><?= number_format($category_stats['active_categories']) ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> Active
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card-admin warning">
                            <div class="stat-header">
                                <div class="stat-title">With Products</div>
                                <div class="stat-icon-admin">
                                    <i class="fas fa-box"></i>
                                </div>
                            </div>
                            <div class="stat-value"><?= number_format($category_stats['categories_with_products']) ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-box"></i> Used
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card-admin danger">
                            <div class="stat-header">
                                <div class="stat-title">Inactive</div>
                                <div class="stat-icon-admin">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                            <div class="stat-value"><?= number_format($category_stats['inactive_categories']) ?></div>
                            <div class="stat-change negative">
                                <i class="fas fa-times"></i> Inactive
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters and Search -->
                <div class="admin-table-container">
                    <div class="table-header">
                        <div class="table-actions">
                            <form method="GET" action="" class="d-flex gap-2 align-items-center">
                                <div class="search-box">
                                    <input type="text" 
                                           name="search" 
                                           class="form-control form-control-sm" 
                                           placeholder="Search categories..." 
                                           value="<?= htmlspecialchars($search) ?>">
                                </div>
                                
                                <select name="status" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">All Status</option>
                                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                                
                                <button type="submit" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-filter"></i>
                                </button>
                                
                                <a href="categories.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times"></i>
                                </a>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Categories Table -->
                    <div class="table-responsive">
                        <table class="table admin-table" id="categoriesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="admin-empty-state">
                                                <i class="fas fa-tags empty-icon"></i>
                                                <div class="empty-title">No Categories Found</div>
                                                <div class="empty-description">
                                                    <?php if (!empty($search) || !empty($status_filter)): ?>
                                                        Try adjusting your search criteria or filters.
                                                    <?php else: ?>
                                                        Start by adding your first category.
                                                    <?php endif; ?>
                                                </div>
                                                <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#categoryModal">
                                                    <i class="fas fa-plus me-2"></i>Add Category
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?= $category['id'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($category['name']) ?></strong>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($category['description'] ?: '-') ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= $category['product_count'] ?> products
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $category['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                                    <i class="fas fa-<?= $category['is_active'] ? 'check' : 'times' ?> me-1"></i>
                                                    <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= formatDate($category['created_at'], 'd M Y') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="categories.php?edit=<?= $category['id'] ?>" 
                                                       class="btn btn-sm btn-action edit" 
                                                       title="Edit Category">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($category['product_count'] == 0): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-action delete" 
                                                                title="Delete Category"
                                                                onclick="confirmDelete(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-action delete disabled" 
                                                                title="Cannot delete category with products"
                                                                disabled>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-container">
                            <div class="pagination-info">
                                Showing <strong><?= number_format(($page - 1) * $per_page + 1) ?></strong> to 
                                <strong><?= number_format(min($page * $per_page, $total_records)) ?></strong> of 
                                <strong><?= number_format($total_records) ?></strong> entries
                            </div>
                            
                            <?= generatePagination($page, $total_pages, [
                                'search' => $search,
                                'status' => $status_filter
                            ]) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-<?= $edit_mode ? 'edit' : 'plus' ?> me-2"></i>
                        <?= $edit_mode ? 'Edit Category' : 'Add New Category' ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="categoryForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="<?= $edit_mode ? 'edit' : 'add' ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="category_id" value="<?= $edit_category['id'] ?>">
                        <?php endif; ?>
                        
                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($errors['general']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Category Name -->
                        <div class="form-group mb-3">
                            <label class="form-label required" for="name">Category Name</label>
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
                        
                        <!-- Description -->
                        <div class="form-group mb-3">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                      id="description" 
                                      name="description" 
                                      rows="3"><?= htmlspecialchars($form_data['description']) ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['description']) ?></div>
                            <?php endif; ?>
                            <div class="form-text">Optional description for the category</div>
                        </div>
                        
                        <!-- Status -->
                        <div class="form-group mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1" 
                                       <?= $form_data['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">
                                    Active Category
                                </label>
                            </div>
                            <div class="form-text">Inactive categories are hidden from product forms</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save me-2"></i>
                            <?= $edit_mode ? 'Update Category' : 'Create Category' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category <strong id="deleteCategoryName"></strong>?</p>
                    <p class="text-muted small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="process.php" style="display: inline;">
                        <input type="hidden" name="category_id" id="deleteCategoryId">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Category
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php 
    $page_js = "
        // Initialize DataTable
        if (typeof $.fn.DataTable !== 'undefined') {
            $('#categoriesTable').DataTable({
                responsive: true,
                pageLength: " . RECORDS_PER_PAGE . ",
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [6] }
                ]
            });
        }
        
        // Show modal if edit mode
        " . ($edit_mode ? "new bootstrap.Modal(document.getElementById('categoryModal')).show();" : "") . "
        
        // Form validation
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin me-2\"></i>Processing...';
        });
        
        // Confirm delete function
        function confirmDelete(categoryId, categoryName) {
            document.getElementById('deleteCategoryId').value = categoryId;
            document.getElementById('deleteCategoryName').textContent = categoryName;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Auto-submit form on filter change
        document.querySelector('select[name=\"status\"]').addEventListener('change', function() {
            this.closest('form').submit();
        });
    ";
    ?>
</body>
</html>
