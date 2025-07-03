<?php
/**
 * Order Management - List All Orders
 * Admin page untuk mengelola orders
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Require admin access
requireAdmin();

$page_title = 'Order Management - ' . APP_NAME;
$is_admin_page = true;

// Handle search and filters
$search = sanitizeInput($_GET['search'] ?? '');
$status_filter = sanitizeInput($_GET['status'] ?? '');
$date_from = sanitizeInput($_GET['date_from'] ?? '');
$date_to = sanitizeInput($_GET['date_to'] ?? '');

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(o.order_number LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(o.order_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(o.order_date) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get orders with pagination
$page = (int)($_GET['page'] ?? 1);
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) as total 
              FROM orders o 
              LEFT JOIN customers c ON o.customer_id = c.id 
              $where_clause";
$count_result = getRecord($count_sql, $params);
$total_records = $count_result['total'] ?? 0;
$total_pages = ceil($total_records / $limit);

$sql = "SELECT o.*, c.name as customer_name, c.email as customer_email,
               COALESCE(o.total_amount, 0) as calculated_total
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        $where_clause 
        ORDER BY o.order_date DESC 
        LIMIT $limit OFFSET $offset";

$orders = getRecords($sql, $params);

// Get statistics
$stats = [
    'total_orders' => getRecord("SELECT COUNT(*) as count FROM orders")['count'] ?? 0,
    'pending_orders' => getRecord("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'] ?? 0,
    'completed_orders' => getRecord("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")['count'] ?? 0,
    'cancelled_orders' => getRecord("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")['count'] ?? 0
];

// Include header
include '../../includes/header.php';
?>

<div class="d-flex">
    <?php include '../../includes/admin_sidebar.php'; ?>
    
    <div class="admin-content flex-grow-1">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-shopping-cart me-2"></i>Order Management</h2>
                <p class="text-muted mb-0">Manage customer orders and track status</p>
            </div>
            <div class="d-flex gap-2">
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Order
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
                        <h3 class="mb-0"><?= number_format($stats['total_orders']) ?></h3>
                        <small class="text-muted">Total Orders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h3 class="mb-0"><?= number_format($stats['pending_orders']) ?></h3>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h3 class="mb-0"><?= number_format($stats['completed_orders']) ?></h3>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                        <h3 class="mb-0"><?= number_format($stats['cancelled_orders']) ?></h3>
                        <small class="text-muted">Cancelled</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Order number, customer..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control" 
                               value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control" 
                               value="<?= htmlspecialchars($date_to) ?>">
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

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Orders List</h5>
                <span class="badge bg-secondary"><?= $total_records ?> total orders</span>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h5>No orders found</h5>
                        <p class="text-muted">Try adjusting your search criteria or add new orders.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order Number</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Total Amount</th>
                                    <th>Order Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($order['order_number']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($order['customer_email'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $status_colors = [
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'shipped' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            $color = $status_colors[$order['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold"><?= formatCurrency($order['calculated_total'] ?? $order['total_amount'] ?? 0) ?></span>
                                        </td>
                                        <td>
                                            <span title="<?= formatDateTime($order['order_date']) ?>">
                                                <?= formatDate($order['order_date']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view.php?id=<?= $order['id'] ?>" 
                                                   class="btn btn-outline-info" 
                                                   title="View Order">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?= $order['id'] ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Edit Order">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="invoice.php?id=<?= $order['id'] ?>" 
                                                   class="btn btn-outline-success" 
                                                   title="Generate Invoice"
                                                   target="_blank">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
                                                <?php if (in_array($order['status'], ['pending', 'cancelled'])): ?>
                                                <form method="POST" action="process.php" class="d-inline delete-order-form" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                    <input type="hidden" name="action" value="delete_order">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Delete Order" onclick="return confirm('Are you sure you want to delete this order?');">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
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
    $('#ordersTable').DataTable({
        paging: false,
        info: false,
        searching: false,
        ordering: true,
        responsive: true,
        columnDefs: [
            { orderable: false, targets: [5] } // Disable sorting for Actions column
        ]
    });
});
</script>
