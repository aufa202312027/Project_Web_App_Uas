<?php
/**
 * Admin Product Management - List Products
 * Halaman untuk mengelola data produk
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check admin access
requireAdmin();

// Page configuration
$page_title = 'Product Management - ' . APP_NAME;
$is_admin_page = true;
$include_datatables = true;

// Handle search and filters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$stock_filter = $_GET['stock'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = RECORDS_PER_PAGE;

// Build query conditions
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($status_filter)) {
    if ($status_filter === 'active') {
        $where_conditions[] = "p.is_active = 1";
    } elseif ($status_filter === 'inactive') {
        $where_conditions[] = "p.is_active = 0";
    }
}

if (!empty($stock_filter)) {
    switch ($stock_filter) {
        case 'in_stock':
            $where_conditions[] = "p.stock > p.min_stock";
            break;
        case 'low_stock':
            $where_conditions[] = "p.stock <= p.min_stock AND p.stock > 0";
            break;
        case 'out_of_stock':
            $where_conditions[] = "p.stock = 0";
            break;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              $where_clause";
$total_records = getRecord($count_sql, $params)['total'];
$total_pages = ceil($total_records / $per_page);

// Get products with pagination
$offset = ($page - 1) * $per_page;
$sql = "SELECT p.*, c.name as category_name, s.name as supplier_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        $where_clause 
        ORDER BY p.created_at DESC 
        LIMIT $per_page OFFSET $offset";

$products = getRecords($sql, $params);

// Get categories for filter
$categories = getRecords("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");

// Get product statistics
$product_stats = [
    'total_products' => getRecord("SELECT COUNT(*) as count FROM products")['count'],
    'active_products' => getRecord("SELECT COUNT(*) as count FROM products WHERE is_active = 1")['count'],
    'low_stock' => getRecord("SELECT COUNT(*) as count FROM products WHERE stock <= min_stock AND stock > 0")['count'],
    'out_of_stock' => getRecord("SELECT COUNT(*) as count FROM products WHERE stock = 0")['count']
];

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
                    <h1>Product Management</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Products</li>
                        </ol>
                    </nav>
                </div>
                
                <div class="header-controls">
                    <a href="categories.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-tags me-2"></i>Manage Categories
                    </a>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Product
                    </a>
                </div>
            </header>
            
            <!-- Main Content -->
            <div class="admin-main">
                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card-admin">
                            <div class="stat-header">
                                <div class="stat-title">Total Products</div>
                                <div class="stat-icon-admin">
                                    <i class="fas fa-box"></i>
                                </div>
                            </div>
                            <div class="stat-value"><?= number_format($product_stats['total_products']) ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-boxes"></i> All Products
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card-admin success">
                            <div class="stat-header">
                                <div class="stat-title">Active Products</div>
                                <div class="stat-icon-admin">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <div class="stat-value"><?= number_format($product_stats['active_products']) ?></div>
                            <div class="stat-change positive">
                                <i class="fas fa-check"></i> Published
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card-admin warning">
                            <div class="stat-header">
                                <div class="stat-title">Low Stock</div>
                                <div class="stat-icon-admin">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                            <div class="stat-value"><?= number_format($product_stats['low_stock']) ?></div>
                            <div class="stat-change negative">
                                <i class="fas fa-arrow-down"></i> Needs Restock
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card-admin danger">
                            <div class="stat-header">
                                <div class="stat-title">Out of Stock</div>
                                <div class="stat-icon-admin">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                            <div class="stat-value"><?= number_format($product_stats['out_of_stock']) ?></div>
                            <div class="stat-change negative">
                                <i class="fas fa-times"></i> Zero Stock
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters and Search -->
                <div class="admin-table-container">
                    <div class="table-header">
                        <h5 class="table-title">
                            <i class="fas fa-box me-2"></i>Products List
                            <span class="badge bg-secondary ms-2"><?= number_format($total_records) ?></span>
                        </h5>
                        
                        <div class="table-controls">
                            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control form-control-sm" 
                                           placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                                    <i class="fas fa-search search-icon"></i>
                                </div>
                                
                                <select name="category" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <select name="stock" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">All Stock</option>
                                    <option value="in_stock" <?= $stock_filter === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                                    <option value="low_stock" <?= $stock_filter === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                                    <option value="out_of_stock" <?= $stock_filter === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                                </select>
                                
                                <select name="status" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">All Status</option>
                                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                                
                                <button type="submit" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-filter"></i>
                                </button>
                                
                                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times"></i>
                                </a>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Products Table -->
                    <div class="table-responsive">
                        <table class="table admin-table" id="productsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="admin-empty-state">
                                                <i class="fas fa-box empty-icon"></i>
                                                <div class="empty-title">No Products Found</div>
                                                <div class="empty-description">
                                                    <?php if (!empty($search) || !empty($category_filter) || !empty($status_filter) || !empty($stock_filter)): ?>
                                                        Try adjusting your search criteria or filters.
                                                    <?php else: ?>
                                                        Start by adding your first product.
                                                    <?php endif; ?>
                                                </div>
                                                <a href="add.php" class="btn btn-primary mt-3">
                                                    <i class="fas fa-plus me-2"></i>Add Product
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <?php 
                                        $stock_status = 'success';
                                        $stock_text = 'In Stock';
                                        if ($product['stock'] == 0) {
                                            $stock_status = 'danger';
                                            $stock_text = 'Out of Stock';
                                        } elseif ($product['stock'] <= $product['min_stock']) {
                                            $stock_status = 'warning';
                                            $stock_text = 'Low Stock';
                                        }
                                        ?>
                                        <tr>
                                            <td><?= $product['id'] ?></td>
                                            <td>
                                                <?php if ($product['image']): ?>
                                                    <img src="<?= ASSETS_URL ?>/uploads/<?= htmlspecialchars($product['image']) ?>" 
                                                         alt="<?= htmlspecialchars($product['name']) ?>"
                                                         class="product-thumbnail" 
                                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                <?php else: ?>
                                                    <div class="product-thumbnail bg-light d-flex align-items-center justify-content-center" 
                                                         style="width: 50px; height: 50px; border-radius: 5px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($product['name']) ?></strong>
                                                    <?php if ($product['description']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <code><?= htmlspecialchars($product['sku'] ?: '-') ?></code>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= htmlspecialchars($product['category_name'] ?: 'No Category') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?= formatCurrency($product['price']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $stock_status ?>">
                                                    <?= number_format($product['stock']) ?> 
                                                    <?php if ($product['min_stock'] > 0): ?>
                                                        / <?= number_format($product['min_stock']) ?>
                                                    <?php endif; ?>
                                                </span>
                                                <br><small class="text-muted"><?= $stock_text ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?= $product['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                                    <i class="fas fa-<?= $product['is_active'] ? 'check' : 'times' ?> me-1"></i>
                                                    <?= $product['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="edit.php?id=<?= $product['id'] ?>" 
                                                       class="btn btn-sm btn-action edit" 
                                                       title="Edit Product">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <button type="button" 
                                                            class="btn btn-sm btn-action delete" 
                                                            title="Delete Product"
                                                            onclick="confirmDelete(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    
                                                    <button type="button" 
                                                            class="btn btn-sm btn-action view" 
                                                            title="View Details"
                                                            onclick="viewProduct(<?= $product['id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
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
                                'category' => $category_filter,
                                'status' => $status_filter,
                                'stock' => $stock_filter
                            ]) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Product Details Modal -->
    <div class="modal fade" id="productDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-box me-2"></i>Product Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="productDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
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
                    <p>Are you sure you want to delete product <strong id="deleteProductName"></strong>?</p>
                    <p class="text-muted small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="process.php" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" id="deleteProductId">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Product
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
            $('#productsTable').DataTable({
                responsive: true,
                pageLength: " . RECORDS_PER_PAGE . ",
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [1, 8] }
                ],
                language: {
                    search: 'Search products:',
                    lengthMenu: 'Show _MENU_ products per page',
                    info: 'Showing _START_ to _END_ of _TOTAL_ products',
                    emptyTable: 'No products found'
                }
            });
        }
        
        // Confirm delete function
        function confirmDelete(productId, productName) {
            document.getElementById('deleteProductId').value = productId;
            document.getElementById('deleteProductName').textContent = productName;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // View product details
        function viewProduct(productId) {
            const modal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
            const content = document.getElementById('productDetailsContent');
            
            content.innerHTML = '<div class=\"text-center\"><div class=\"spinner-border text-primary\" role=\"status\"><span class=\"visually-hidden\">Loading...</span></div></div>';
            modal.show();
            
            // Load product details via AJAX
            fetch('process.php?action=get_product&id=' + productId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.product;
                        const imageHtml = product.image ? 
                            `<img src=\"<?= ASSETS_URL ?>/uploads/\${product.image}\" alt=\"\${product.name}\" class=\"img-fluid rounded\" style=\"max-height: 200px;\">` :
                            '<div class=\"bg-light p-4 rounded\"><i class=\"fas fa-image fa-3x text-muted\"></i></div>';
                        
                        content.innerHTML = `
                            <div class=\"row g-3\">
                                <div class=\"col-md-4 text-center\">
                                    \${imageHtml}
                                </div>
                                <div class=\"col-md-8\">
                                    <h5>\${product.name}</h5>
                                    <p class=\"text-muted\">\${product.description || 'No description'}</p>
                                    <div class=\"row g-2\">
                                        <div class=\"col-sm-6\"><strong>SKU:</strong> \${product.sku || '-'}</div>
                                        <div class=\"col-sm-6\"><strong>Category:</strong> \${product.category_name || 'No Category'}</div>
                                        <div class=\"col-sm-6\"><strong>Price:</strong> \${formatCurrency(product.price)}</div>
                                        <div class=\"col-sm-6\"><strong>Stock:</strong> \${product.stock}</div>
                                        <div class=\"col-sm-6\"><strong>Min Stock:</strong> \${product.min_stock}</div>
                                        <div class=\"col-sm-6\"><strong>Supplier:</strong> \${product.supplier_name || '-'}</div>
                                        <div class=\"col-sm-6\"><strong>Status:</strong> <span class=\"badge \${product.is_active ? 'bg-success' : 'bg-danger'}\">\${product.is_active ? 'Active' : 'Inactive'}</span></div>
                                        <div class=\"col-sm-6\"><strong>Created:</strong> \${new Date(product.created_at).toLocaleDateString()}</div>
                                    </div>
                                </div>
                            </div>
                            <div class=\"mt-3 d-flex gap-2\">
                                <a href=\"edit.php?id=\${product.id}\" class=\"btn btn-primary btn-sm\">
                                    <i class=\"fas fa-edit me-1\"></i>Edit Product
                                </a>
                                <button type=\"button\" class=\"btn btn-secondary btn-sm\" data-bs-dismiss=\"modal\">Close</button>
                            </div>
                        `;
                    } else {
                        content.innerHTML = '<div class=\"alert alert-danger\">Error loading product details.</div>';
                    }
                })
                .catch(error => {
                    content.innerHTML = '<div class=\"alert alert-danger\">Error loading product details.</div>';
                });
        }
        
        // Helper function to format currency
        function formatCurrency(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        }
        
        // Auto-submit form on filter change
        document.querySelectorAll('select[name=\"category\"], select[name=\"status\"], select[name=\"stock\"]').forEach(select => {
            select.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });
    ";
    ?>
</body>
</html>
