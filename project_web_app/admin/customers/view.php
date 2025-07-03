<?php
/**
 * Customer Management - View Customer Details
 * Detail view untuk customer tertentu
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Require admin access
requireAdmin();

$customer_id = (int)($_GET['id'] ?? 0);

if ($customer_id <= 0) {
    setFlashMessage('Invalid customer ID.', 'error');
    header('Location: index.php');
    exit;
}

// Get customer details
$customer = getRecord("
    SELECT * FROM customers WHERE id = ?
", [$customer_id]);

if (!$customer) {
    setFlashMessage('Customer not found.', 'error');
    header('Location: index.php');
    exit;
}

// Get customer orders
$orders = executeQuery("
    SELECT o.*, COUNT(od.id) as item_count
    FROM orders o
    LEFT JOIN order_details od ON o.id = od.order_id
    WHERE o.customer_id = ?
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT 10
", [$customer_id]);

// Get customer statistics
$stats = getRecord("
    SELECT 
        COUNT(o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent,
        COALESCE(AVG(o.total_amount), 0) as average_order,
        MAX(o.order_date) as last_order_date,
        MIN(o.order_date) as first_order_date
    FROM orders o
    WHERE o.customer_id = ?
", [$customer_id]);

$page_title = 'Customer: ' . $customer['name'] . ' - ' . APP_NAME;
$is_admin_page = true;

// Include header
include '../../includes/header.php';
?>

<div class="d-flex">
    <?php include '../../includes/admin_sidebar.php'; ?>
    
    <div class="admin-content flex-grow-1">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-user me-2"></i>Customer Details</h2>
                <p class="text-muted mb-0"><?= htmlspecialchars($customer['name']) ?></p>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Customers
                </a>
                <a href="edit.php?id=<?= $customer['id'] ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Edit Customer
                </a>
                <button type="button" class="btn btn-outline-danger" 
                        onclick="confirmDelete(<?= $customer['id'] ?>, '<?= htmlspecialchars($customer['name']) ?>')">
                    <i class="fas fa-trash me-2"></i>Delete
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Customer Information -->
            <div class="col-lg-4">
                <!-- Customer Profile -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>Customer Profile
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="avatar-lg bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                                <i class="fas fa-user fa-3x"></i>
                            </div>
                            <h4 class="mb-1"><?= htmlspecialchars($customer['name']) ?></h4>
                            <p class="text-muted mb-3">
                                Customer since <?= formatDateTime($customer['created_at'], 'F Y') ?>
                            </p>
                            <span class="badge bg-<?= $customer['is_active'] ? 'success' : 'danger' ?> fs-6">
                                <?= $customer['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>

                        <div class="customer-info">
                            <dl class="row mb-0">
                                <?php if (!empty($customer['email'])): ?>
                                    <dt class="col-4">
                                        <i class="fas fa-envelope text-muted me-1"></i>Email:
                                    </dt>
                                    <dd class="col-8">
                                        <a href="mailto:<?= htmlspecialchars($customer['email']) ?>">
                                            <?= htmlspecialchars($customer['email']) ?>
                                        </a>
                                    </dd>
                                <?php endif; ?>
                                
                                <?php if (!empty($customer['phone'])): ?>
                                    <dt class="col-4">
                                        <i class="fas fa-phone text-muted me-1"></i>Phone:
                                    </dt>
                                    <dd class="col-8">
                                        <a href="tel:<?= htmlspecialchars($customer['phone']) ?>">
                                            <?= htmlspecialchars($customer['phone']) ?>
                                        </a>
                                    </dd>
                                <?php endif; ?>
                                
                                <?php if (!empty($customer['city'])): ?>
                                    <dt class="col-4">
                                        <i class="fas fa-city text-muted me-1"></i>City:
                                    </dt>
                                    <dd class="col-8"><?= htmlspecialchars($customer['city']) ?></dd>
                                <?php endif; ?>
                                
                                <?php if (!empty($customer['postal_code'])): ?>
                                    <dt class="col-4">
                                        <i class="fas fa-mail-bulk text-muted me-1"></i>Postal:
                                    </dt>
                                    <dd class="col-8"><?= htmlspecialchars($customer['postal_code']) ?></dd>
                                <?php endif; ?>
                            </dl>
                        </div>

                        <?php if (!empty($customer['address'])): ?>
                            <div class="mt-4">
                                <h6 class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>Address:
                                </h6>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($customer['address'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Customer Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Order Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="border-end">
                                    <h3 class="text-primary mb-1"><?= number_format($stats['total_orders']) ?></h3>
                                    <small class="text-muted">Total Orders</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <h3 class="text-success mb-1"><?= formatCurrency($stats['total_spent']) ?></h3>
                                <small class="text-muted">Total Spent</small>
                            </div>
                            <div class="col-12">
                                <h4 class="text-info mb-1"><?= formatCurrency($stats['average_order']) ?></h4>
                                <small class="text-muted">Average Order Value</small>
                            </div>
                        </div>

                        <?php if ($stats['total_orders'] > 0): ?>
                            <hr>
                            <div class="small text-muted">
                                <div class="d-flex justify-content-between">
                                    <span>First Order:</span>
                                    <span><?= formatDateTime($stats['first_order_date'], 'M d, Y') ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Last Order:</span>
                                    <span><?= formatDateTime($stats['last_order_date'], 'M d, Y') ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order History -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-shopping-cart me-2"></i>Recent Orders
                            </h5>
                            <?php if (!empty($orders)): ?>
                                <a href="../orders/index.php?customer_id=<?= $customer['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    View All Orders
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Orders Found</h5>
                                <p class="text-muted">This customer hasn't placed any orders yet.</p>
                                <a href="../orders/add.php?customer_id=<?= $customer['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Create First Order
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Date</th>
                                            <th>Items</th>
                                            <th>Status</th>
                                            <th>Total</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                                </td>
                                                <td><?= formatDateTime($order['order_date'], 'M d, Y') ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= number_format($order['item_count']) ?> items</span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_colors = [
                                                        'pending' => 'warning',
                                                        'processing' => 'info',
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
                                                    <strong><?= formatCurrency($order['total_amount']) ?></strong>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="../orders/view.php?id=<?= $order['id'] ?>" 
                                                           class="btn btn-outline-primary btn-sm" title="View Order">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="../orders/invoice.php?id=<?= $order['id'] ?>" 
                                                           class="btn btn-outline-success btn-sm" title="Invoice" target="_blank">
                                                            <i class="fas fa-file-invoice"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
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
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5>Are you sure?</h5>
                    <p class="text-muted">
                        This will permanently delete the customer <strong id="customerName"></strong> 
                        and all associated data. This action cannot be undone.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="process.php" class="d-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="customer_id" id="deleteCustomerId">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Customer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(customerId, customerName) {
    document.getElementById('deleteCustomerId').value = customerId;
    document.getElementById('customerName').textContent = customerName;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
