<?php
/**
 * Customer Management - Delete Customer
 * Handle delete customer operation
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Require admin access
requireAdmin();

$customer_id = (int)($_GET['id'] ?? $_POST['customer_id'] ?? 0);

if ($customer_id <= 0) {
    setFlashMessage('Invalid customer ID.', 'error');
    header('Location: index.php');
    exit;
}

// Get customer details for confirmation
$customer = getRecord("SELECT id, name FROM customers WHERE id = ?", [$customer_id]);

if (!$customer) {
    setFlashMessage('Customer not found.', 'error');
    header('Location: index.php');
    exit;
}

// Handle POST request (actual deletion)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid security token. Please try again.', 'error');
        header('Location: index.php');
        exit;
    }

    try {
        // Check if customer has orders
        $orders_count = getRecord("SELECT COUNT(*) as count FROM orders WHERE customer_id = ?", [$customer_id]);
        
        if ($orders_count['count'] > 0) {
            setFlashMessage(
                "Cannot delete customer '{$customer['name']}'. Customer has {$orders_count['count']} order(s). Please deactivate the customer instead.",
                'error'
            );
            header('Location: view.php?id=' . $customer_id);
            exit;
        }

        // Delete customer
        $deleted = executeQuery("DELETE FROM customers WHERE id = ?", [$customer_id]);
        
        if ($deleted) {
            // Log activity
            logActivity(
                getCurrentUserId(),
                'DELETE',
                'customers',
                $customer_id,
                "Deleted customer: {$customer['name']}"
            );

            setFlashMessage("Customer '{$customer['name']}' has been deleted successfully.", 'success');
            header('Location: index.php');
        } else {
            throw new Exception('Failed to delete customer from database.');
        }

    } catch (Exception $e) {
        error_log("Customer delete error: " . $e->getMessage());
        setFlashMessage('Failed to delete customer: ' . $e->getMessage(), 'error');
        header('Location: view.php?id=' . $customer_id);
    }
    exit;
}

// For GET request, show confirmation page
$page_title = 'Delete Customer - ' . APP_NAME;
$is_admin_page = true;

// Get customer orders count for warning
$orders_count = getRecord("SELECT COUNT(*) as count FROM orders WHERE customer_id = ?", [$customer_id]);

// Include header
include '../../includes/header.php';
?>

<div class="d-flex">
    <?php include '../../includes/admin_sidebar.php'; ?>
    
    <div class="admin-content flex-grow-1">
  
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-user-times me-2 text-danger"></i>Delete Customer</h2>
                <p class="text-muted mb-0">Permanently remove customer from system</p>
            </div>
            <div class="d-flex gap-2">
                <a href="view.php?id=<?= $customer['id'] ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-eye me-2"></i>View Customer
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Customers
                </a>
            </div>
        </div>

        <!-- Delete Confirmation -->
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Confirm Customer Deletion
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="avatar-lg bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                                <i class="fas fa-user-times fa-2x"></i>
                            </div>
                            <h4 class="text-danger">Delete Customer</h4>
                            <p class="text-muted">
                                You are about to permanently delete:
                            </p>
                            <h5 class="mb-3">
                                <strong><?= htmlspecialchars($customer['name']) ?></strong>
                            </h5>
                        </div>

                        <?php if ($orders_count['count'] > 0): ?>
                            <!-- Warning: Customer has orders -->
                            <div class="alert alert-warning">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="alert-heading">Cannot Delete Customer</h5>
                                        <p class="mb-2">
                                            This customer has <strong><?= $orders_count['count'] ?></strong> order(s) in the system.
                                            Customers with existing orders cannot be deleted to maintain data integrity.
                                        </p>
                                        <hr>
                                        <p class="mb-0">
                                            <strong>Alternative:</strong> You can deactivate this customer instead, 
                                            which will prevent them from placing new orders while preserving order history.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <form method="POST" action="process.php">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="redirect" value="index.php">
                                    <button type="submit" class="btn btn-warning btn-lg w-100">
                                        <i class="fas fa-user-slash me-2"></i>Deactivate Customer Instead
                                    </button>
                                </form>
                                <a href="view.php?id=<?= $customer['id'] ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Go Back
                                </a>
                            </div>

                        <?php else: ?>
                            <!-- Deletion allowed -->
                            <div class="alert alert-info">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="alert-heading">Deletion Impact</h5>
                                        <ul class="mb-0">
                                            <li>Customer profile will be permanently removed</li>
                                            <li>This action cannot be undone</li>
                                            <li>No orders are associated with this customer</li>
                                            <li>All customer data will be lost</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <form method="POST" onsubmit="return confirmDeletion()">
                                    <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    
                                    <button type="submit" class="btn btn-danger btn-lg w-100">
                                        <i class="fas fa-trash me-2"></i>Yes, Delete Customer Permanently
                                    </button>
                                </form>
                                
                                <a href="view.php?id=<?= $customer['id'] ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel, Keep Customer
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-lightbulb me-2"></i>Need Help?
                        </h6>
                    </div>
                    <div class="card-body">
                        <h6>Before deleting a customer:</h6>
                        <ul class="small">
                            <li>Make sure you have exported any important data</li>
                            <li>Consider deactivating instead of deleting</li>
                            <li>Verify no pending orders or quotes exist</li>
                            <li>Inform relevant team members about the deletion</li>
                        </ul>

                        <h6>Alternatives to deletion:</h6>
                        <ul class="small">
                            <li><strong>Deactivate:</strong> Prevents new orders while keeping history</li>
                            <li><strong>Archive:</strong> Move to inactive status with notes</li>
                            <li><strong>Transfer:</strong> Merge with another customer account</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeletion() {
    const customerName = <?= json_encode($customer['name']) ?>;
    return confirm(
        `Are you absolutely sure you want to delete customer "${customerName}"?\n\n` +
        'This action cannot be undone and will permanently remove all customer data.\n\n' +
        'Type "DELETE" in the next prompt to confirm.'
    ) && prompt('Type "DELETE" to confirm deletion:') === 'DELETE';
}

// Auto-focus on primary action when page loads
document.addEventListener('DOMContentLoaded', function() {
    const primaryButton = document.querySelector('.btn-danger, .btn-warning');
    if (primaryButton) {
        primaryButton.focus();
    }
});
</script>

<style>
.avatar-lg {
    width: 4rem;
    height: 4rem;
}

.card.border-danger {
    border-width: 2px;
}

.alert {
    border-left: 4px solid;
}

.alert-warning {
    border-left-color: #ffc107;
}

.alert-info {
    border-left-color: #0dcaf0;
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
}

@media (max-width: 576px) {
    .avatar-lg {
        width: 3rem;
        height: 3rem;
    }
    
    .btn-lg {
        padding: 0.625rem 1.25rem;
        font-size: 1rem;
    }
}
</style>
