<?php
/**
 * Customer Management - List All Customers
 * Admin page untuk mengelola customers
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Require admin access
requireAdmin();

$page_title = 'Customer Management - ' . APP_NAME;
$is_admin_page = true;

// Handle search and filters
$search = sanitizeInput($_GET['search'] ?? '');
$status_filter = sanitizeInput($_GET['status'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
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

// Get customers with pagination
$page = (int)($_GET['page'] ?? 1);
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) as total FROM customers $where_clause";
$count_result = getRecord($count_sql, $params);
$total_records = $count_result['total'] ?? 0;
$total_pages = ceil($total_records / $limit);

$sql = "SELECT c.*, 
               (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) as total_orders,
               (SELECT SUM(od.quantity * od.price) 
                FROM orders o 
                JOIN order_details od ON o.id = od.order_id 
                WHERE o.customer_id = c.id) as total_spent
        FROM customers c 
        $where_clause 
        ORDER BY c.created_at DESC 
        LIMIT $limit OFFSET $offset";

$customers = getRecords($sql, $params);

// Include header
include '../../includes/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-12 col-md-3 col-lg-2 mb-3">
      <?php include '../../includes/admin_sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="col-12 col-md-9 col-lg-10">
      <div class="admin-content">
       
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-user-friends me-2"></i>Customer Management</h2>
                <p class="text-muted mb-0">Manage customer accounts and information</p>
            </div>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Customer
            </a>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Name, email, or phone..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search me-1"></i>Filter
                            </button>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Customers List</h5>
                <span class="badge bg-secondary"><?= $total_records ?> total customers</span>
            </div>
            <div class="card-body">
                <?php if (empty($customers)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                        <h5>No customers found</h5>
                        <p class="text-muted">Try adjusting your search criteria or add new customers.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="customersTable">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                                    <?= strtoupper(substr($customer['name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($customer['name']) ?></div>
                                                    <small class="text-muted">ID: #<?= $customer['id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($customer['email']) ?></div>
                                            <?php if (!empty($customer['phone'])): ?>
                                                <small class="text-muted"><?= htmlspecialchars($customer['phone']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?= (int)$customer['total_orders'] ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-bold"><?= formatCurrency($customer['total_spent'] ?? 0) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $customer['is_active'] ? 'success' : 'secondary' ?>">
                                                <?= $customer['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span title="<?= $customer['created_at'] ?>">
                                                <?= formatDate($customer['created_at']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view.php?id=<?= $customer['id'] ?>" 
                                                   class="btn btn-outline-info" 
                                                   title="View Customer">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?= $customer['id'] ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Edit Customer">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete.php?id=<?= $customer['id'] ?>" 
                                                   class="btn btn-outline-danger"
                                                   title="Delete Customer">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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
                            
                            <?= generatePagination($page, $total_pages, $_GET) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize DataTable
$(document).ready(function() {
    $('#customersTable').DataTable({
        paging: false,
        info: false,
        searching: false,
        ordering: true,
        responsive: true,
        columnDefs: [
            { orderable: false, targets: [6] } // Disable sorting for Actions column
        ]
    });
});
</script>
