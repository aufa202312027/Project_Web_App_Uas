<?php
/**
 * Order Management - View Order Details
 * Detail view untuk order tertentu
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Require admin access
requireAdmin();

$order_id = (int)($_GET['id'] ?? 0);

if ($order_id <= 0) {
    setFlashMessage('Invalid order ID.', 'error');
    header('Location: index.php');
    exit;
}

// Get order details
$order = getRecord("
    SELECT o.*, c.name as customer_name, c.email as customer_email, 
           c.phone as customer_phone, c.address as customer_address
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.id 
    WHERE o.id = ?
", [$order_id]);

if (!$order) {
    setFlashMessage('Order not found.', 'error');
    header('Location: index.php');
    exit;
}

// Get order items
$order_items = executeQuery("
    SELECT od.*, p.name as product_name, p.sku as product_sku, p.image as product_image
    FROM order_details od
    LEFT JOIN products p ON od.product_id = p.id
    WHERE od.order_id = ?
    ORDER BY od.id
", [$order_id]);

// Calculate totals
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['quantity'] * $item['price'];
}

$page_title = 'Order #' . $order['order_number'] . ' - ' . APP_NAME;
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
                <h2><i class="fas fa-eye me-2"></i>Order Details</h2>
                <p class="text-muted mb-0">Order #<?= htmlspecialchars($order['order_number']) ?></p>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Orders
                </a>
                <button type="button" class="btn btn-outline-primary" 
                        onclick="updateStatus(<?= $order['id'] ?>, '<?= $order['status'] ?>')">
                    <i class="fas fa-edit me-2"></i>Update Status
                </button>
                <a href="invoice.php?id=<?= $order['id'] ?>" class="btn btn-success" target="_blank">
                    <i class="fas fa-file-invoice me-2"></i>Generate Invoice
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Order Information -->
            <div class="col-lg-8">
                <!-- Order Status & Basic Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">Order Number:</dt>
                                    <dd class="col-sm-8">
                                        <span class="fw-bold"><?= htmlspecialchars($order['order_number']) ?></span>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Status:</dt>
                                    <dd class="col-sm-8">
                                        <?php
                                        $status_colors = [
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'completed' => 'success',
                                            'cancelled' => 'danger'
                                        ];
                                        $color = $status_colors[$order['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?> fs-6">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Order Date:</dt>
                                    <dd class="col-sm-8"><?= formatDateTime($order['order_date']) ?></dd>
                                    
                                    <dt class="col-sm-4">Payment Status:</dt>
                                    <dd class="col-sm-8">
                                        <?php
                                        $payment_colors = [
                                            'unpaid' => 'danger',
                                            'paid' => 'success',
                                            'partial' => 'warning'
                                        ];
                                        $payment_color = $payment_colors[$order['payment_status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $payment_color ?>">
                                            <?= ucfirst($order['payment_status']) ?>
                                        </span>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <?php if (!empty($order['notes'])): ?>
                                    <dl class="row">
                                        <dt class="col-sm-4">Notes:</dt>
                                        <dd class="col-sm-8">
                                            <div class="alert alert-info py-2">
                                                <?= nl2br(htmlspecialchars($order['notes'])) ?>
                                            </div>
                                        </dd>
                                    </dl>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($order_items)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No items found in this order</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($item['product_image'])): ?>
                                                            <img src="<?= ASSETS_URL ?>/uploads/<?= htmlspecialchars($item['product_image']) ?>" 
                                                                 alt="Product" class="me-3" 
                                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                                        <?php else: ?>
                                                            <div class="bg-light me-3 d-flex align-items-center justify-content-center" 
                                                                 style="width: 50px; height: 50px; border-radius: 4px;">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($item['product_name'] ?? 'Product Deleted') ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <code><?= htmlspecialchars($item['product_sku'] ?? 'N/A') ?></code>
                                                </td>
                                                <td><?= formatCurrency($item['price']) ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?= number_format($item['quantity']) ?></span>
                                                </td>
                                                <td class="fw-bold"><?= formatCurrency($item['quantity'] * $item['price']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-info">
                                            <th colspan="4" class="text-end">Subtotal:</th>
                                            <th><?= formatCurrency($subtotal) ?></th>
                                        </tr>
                                        <?php if (!empty($order['tax_amount'])): ?>
                                            <tr>
                                                <th colspan="4" class="text-end">Tax:</th>
                                                <th><?= formatCurrency($order['tax_amount']) ?></th>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($order['shipping_cost'])): ?>
                                            <tr>
                                                <th colspan="4" class="text-end">Shipping:</th>
                                                <th><?= formatCurrency($order['shipping_cost']) ?></th>
                                            </tr>
                                        <?php endif; ?>
                                        <tr class="table-success">
                                            <th colspan="4" class="text-end">Total:</th>
                                            <th class="fs-5"><?= formatCurrency($order['total_amount'] ?? $subtotal) ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Customer Information</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($order['customer_id']): ?>
                            <div class="text-center mb-3">
                                <div class="avatar-lg bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2">
                                    <i class="fas fa-user fa-2x"></i>
                                </div>
                                <h6 class="mb-0"><?= htmlspecialchars($order['customer_name']) ?></h6>
                            </div>
                            
                            <dl class="row">
                                <dt class="col-4">Email:</dt>
                                <dd class="col-8">
                                    <a href="mailto:<?= htmlspecialchars($order['customer_email']) ?>">
                                        <?= htmlspecialchars($order['customer_email']) ?>
                                    </a>
                                </dd>
                                
                                <?php if (!empty($order['customer_phone'])): ?>
                                    <dt class="col-4">Phone:</dt>
                                    <dd class="col-8">
                                        <a href="tel:<?= htmlspecialchars($order['customer_phone']) ?>">
                                            <?= htmlspecialchars($order['customer_phone']) ?>
                                        </a>
                                    </dd>
                                <?php endif; ?>
                                
                                <?php if (!empty($order['customer_address'])): ?>
                                    <dt class="col-4">Address:</dt>
                                    <dd class="col-8">
                                        <?= nl2br(htmlspecialchars($order['customer_address'])) ?>
                                    </dd>
                                <?php endif; ?>
                            </dl>
                            
                            <div class="d-grid">
                                <a href="../customers/view.php?id=<?= $order['customer_id'] ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-user me-2"></i>View Customer Profile
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-user-slash fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Guest Order</p>
                                <small class="text-muted">No customer account</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Actions -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary" 
                                    onclick="updateStatus(<?= $order['id'] ?>, '<?= $order['status'] ?>')">
                                <i class="fas fa-edit me-2"></i>Update Status
                            </button>
                            <a href="invoice.php?id=<?= $order['id'] ?>" class="btn btn-outline-success" target="_blank">
                                <i class="fas fa-file-invoice me-2"></i>Generate Invoice
                            </a>
                            <button type="button" class="btn btn-outline-info" onclick="printOrder()">
                                <i class="fas fa-print me-2"></i>Print Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="process.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="statusOrderId" value="<?= $order['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <input type="text" class="form-control" value="<?= ucfirst($order['status']) ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select name="status" id="statusSelect" class="form-select" required>
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="Add any notes about this status update..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateStatus(orderId, currentStatus) {
    document.getElementById('statusOrderId').value = orderId;
    document.getElementById('statusSelect').value = currentStatus;
    new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
}

function printOrder() {
    window.print();
}

// Add print styles
const style = document.createElement('style');
style.textContent = `
@media print {
    .admin-sidebar, .btn, .card-header, .modal { display: none !important; }
    .admin-content { margin-left: 0 !important; }
    body { background: white !important; }
}
`;
document.head.appendChild(style);
</script>
