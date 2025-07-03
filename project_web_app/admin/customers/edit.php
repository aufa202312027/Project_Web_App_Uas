<?php
/**
 * Customer Management - Edit Customer
 * Form untuk edit customer yang sudah ada
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

// Get customer data
$customer = getRecord("SELECT * FROM customers WHERE id = ?", [$customer_id]);

if (!$customer) {
    setFlashMessage('Customer not found.', 'error');
    header('Location: index.php');
    exit;
}

$page_title = 'Edit Customer - ' . APP_NAME;
$is_admin_page = true;

// Check for flash messages
$flash = getFlashMessage();

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
                <h2><i class="fas fa-user-edit me-2"></i>Edit Customer</h2>
                <p class="text-muted mb-0">Update customer information</p>
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

        <!-- Customer Edit Form -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($flash): ?>
                            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
                                <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                                <?= htmlspecialchars($flash['message']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="process.php" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">
                                            Customer Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="name" 
                                               name="name" 
                                               value="<?= htmlspecialchars($customer['name']) ?>"
                                               required>
                                        <div class="invalid-feedback">
                                            Please provide a valid customer name.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email" 
                                               value="<?= htmlspecialchars($customer['email']) ?>">
                                        <div class="invalid-feedback">
                                            Please provide a valid email address.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" 
                                               class="form-control" 
                                               id="phone" 
                                               name="phone" 
                                               value="<?= htmlspecialchars($customer['phone']) ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="is_active" class="form-label">Status</label>
                                        <select class="form-select" id="is_active" name="is_active" required>
                                            <option value="1" <?= $customer['is_active'] ? 'selected' : '' ?>>Active</option>
                                            <option value="0" <?= !$customer['is_active'] ? 'selected' : '' ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="city" 
                                               name="city" 
                                               value="<?= htmlspecialchars($customer['city']) ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="postal_code" class="form-label">Postal Code</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="postal_code" 
                                               name="postal_code" 
                                               value="<?= htmlspecialchars($customer['postal_code']) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" 
                                          id="address" 
                                          name="address" 
                                          rows="3"><?= htmlspecialchars($customer['address']) ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Customer
                                </button>
                                <a href="view.php?id=<?= $customer['id'] ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>
