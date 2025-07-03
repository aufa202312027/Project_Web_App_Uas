<?php
/**
 * Admin Users Management - Edit User
 * Form untuk mengedit user
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check admin access
requireAdmin();

// Get user ID
$user_id = (int)($_GET['id'] ?? 0);
if (!$user_id) {
    setFlashMessage('User ID is required.', 'error');
    header('Location: index.php');
    exit;
}

// Get user data
$user = getUserById($user_id);
if (!$user) {
    setFlashMessage('User not found.', 'error');
    header('Location: index.php');
    exit;
}

// Prevent editing own account role/status through this interface
$is_own_account = ($user_id == getCurrentUserId());

// Page configuration
$page_title = 'Edit User - ' . APP_NAME;
$is_admin_page = true;

$errors = [];
$form_data = [
    'username' => $user['username'],
    'email' => $user['email'],
    'full_name' => $user['full_name'],
    'phone' => $user['phone'] ?? '',
    'role' => $user['role'],
    'is_active' => $user['is_active']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = array_merge($form_data, sanitizeInput($_POST));
    
    // Custom validation
    if (empty($form_data['username'])) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($form_data['username']) < 3) {
        $errors['username'] = 'Username must be at least 3 characters';
    } elseif (strlen($form_data['username']) > 50) {
        $errors['username'] = 'Username must not exceed 50 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['username'])) {
        $errors['username'] = 'Username can only contain letters, numbers and underscore';
    } else {
        // Check if username exists (excluding current user)
        $existing = getRecord("SELECT id FROM users WHERE username = ? AND id != ?", [$form_data['username'], $user_id]);
        if ($existing) {
            $errors['username'] = 'Username already exists';
        }
    }
    
    if (empty($form_data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!isValidEmail($form_data['email'])) {
        $errors['email'] = 'Please enter a valid email address';
    } else {
        // Check if email exists (excluding current user)
        $existing = getRecord("SELECT id FROM users WHERE email = ? AND id != ?", [$form_data['email'], $user_id]);
        if ($existing) {
            $errors['email'] = 'Email already exists';
        }
    }
    
    if (empty($form_data['full_name'])) {
        $errors['full_name'] = 'Full name is required';
    } elseif (strlen($form_data['full_name']) > 100) {
        $errors['full_name'] = 'Full name must not exceed 100 characters';
    }
    
    if (!empty($form_data['phone']) && strlen($form_data['phone']) > 20) {
        $errors['phone'] = 'Phone number must not exceed 20 characters';
    }
    
    // Password validation (only if provided)
    if (!empty($form_data['password'])) {
        if (strlen($form_data['password']) < PASSWORD_MIN_LENGTH) {
            $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        }
        
        if ($form_data['password'] !== $form_data['password_confirm']) {
            $errors['password_confirm'] = 'Passwords do not match';
        }
    }
    
    // Role validation (prevent editing own role)
    if (!$is_own_account) {
        if (!in_array($form_data['role'], ['admin', 'user'])) {
            $errors['role'] = 'Invalid role selected';
        }
    } else {
        $form_data['role'] = $user['role']; // Keep original role
        $form_data['is_active'] = $user['is_active']; // Keep original status
    }
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Invalid security token';
    }
    
    // If no errors, update the user
    if (empty($errors)) {
        try {
            $update_data = [
                'username' => $form_data['username'],
                'email' => $form_data['email'],
                'full_name' => $form_data['full_name'],
                'phone' => !empty($form_data['phone']) ? $form_data['phone'] : null,
            ];
            
            // Add role and status if not editing own account
            if (!$is_own_account) {
                $update_data['role'] = $form_data['role'];
                $update_data['is_active'] = (bool)$form_data['is_active'];
            }
            
            // Add password if provided
            if (!empty($form_data['password'])) {
                $update_data['password'] = $form_data['password'];
            }
            
            $success = updateUser($user_id, $update_data);
            
            if ($success) {
                // Log activity
                $changes = [];
                if ($user['username'] !== $form_data['username']) $changes[] = 'username';
                if ($user['email'] !== $form_data['email']) $changes[] = 'email';
                if ($user['full_name'] !== $form_data['full_name']) $changes[] = 'full_name';
                if (($user['phone'] ?? '') !== $form_data['phone']) $changes[] = 'phone';
                if (!$is_own_account && $user['role'] !== $form_data['role']) $changes[] = 'role';
                if (!$is_own_account && $user['is_active'] != $form_data['is_active']) $changes[] = 'status';
                if (!empty($form_data['password'])) $changes[] = 'password';
                
                $change_text = !empty($changes) ? 'Updated: ' . implode(', ', $changes) : 'No changes';
                
                logActivity(getCurrentUserId(), 'UPDATE', 'users', $user_id, 
                           "Updated user: {$form_data['username']} - $change_text");
                
                setFlashMessage("User '{$form_data['username']}' has been updated successfully!", 'success');
                header('Location: index.php');
                exit;
            } else {
                $errors['general'] = 'No changes were made or update failed.';
            }
        } catch (Exception $e) {
            $errors['general'] = 'An error occurred while updating the user.';
            logMessage("User update error: " . $e->getMessage(), 'ERROR');
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
                    <h1>Edit User</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Users</a></li>
                            <li class="breadcrumb-item active">Edit: <?= htmlspecialchars($user['username']) ?></li>
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
                
                <?php if ($is_own_account): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> You are editing your own account. Role and status cannot be changed through this interface.
                    </div>
                <?php endif; ?>
                
                <div class="admin-form-container">
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-user-edit me-2"></i>Edit User Information
                        </div>
                        
                        <form method="POST" action="" id="editUserForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="form-grid">
                                <!-- Username -->
                                <div class="form-group">
                                    <label class="form-label required" for="username">Username</label>
                                    <input type="text" 
                                           class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                                           id="username" 
                                           name="username" 
                                           value="<?= htmlspecialchars($form_data['username']) ?>"
                                           required
                                           autocomplete="username">
                                    <?php if (isset($errors['username'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['username']) ?></div>
                                    <?php endif; ?>
                                    <div class="form-help">Unique identifier for the user. Only letters, numbers and underscore allowed.</div>
                                </div>
                                
                                <!-- Email -->
                                <div class="form-group">
                                    <label class="form-label required" for="email">Email Address</label>
                                    <input type="email" 
                                           class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                           id="email" 
                                           name="email" 
                                           value="<?= htmlspecialchars($form_data['email']) ?>"
                                           required
                                           autocomplete="email">
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                                    <?php endif; ?>
                                    <div class="form-help">Valid email address for login and notifications.</div>
                                </div>
                                
                                <!-- Full Name -->
                                <div class="form-group">
                                    <label class="form-label required" for="full_name">Full Name</label>
                                    <input type="text" 
                                           class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" 
                                           id="full_name" 
                                           name="full_name" 
                                           value="<?= htmlspecialchars($form_data['full_name']) ?>"
                                           required
                                           autocomplete="name">
                                    <?php if (isset($errors['full_name'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['full_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Phone -->
                                <div class="form-group">
                                    <label class="form-label" for="phone">Phone Number</label>
                                    <input type="text" 
                                           class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                           id="phone" 
                                           name="phone" 
                                           value="<?= htmlspecialchars($form_data['phone']) ?>"
                                           autocomplete="tel">
                                    <?php if (isset($errors['phone'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['phone']) ?></div>
                                    <?php endif; ?>
                                    <div class="form-help">Optional contact number.</div>
                                </div>
                                
                                <!-- Change Password Section -->
                                <div class="form-group col-span-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="changePassword">
                                        <label class="form-check-label" for="changePassword">
                                            Change Password
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Password -->
                                <div class="form-group password-field" style="display: none;">
                                    <label class="form-label" for="password">New Password</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                               id="password" 
                                               name="password" 
                                               autocomplete="new-password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if (isset($errors['password'])): ?>
                                            <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-help">Minimum <?= PASSWORD_MIN_LENGTH ?> characters required.</div>
                                </div>
                                
                                <!-- Confirm Password -->
                                <div class="form-group password-field" style="display: none;">
                                    <label class="form-label" for="password_confirm">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" 
                                               id="password_confirm" 
                                               name="password_confirm" 
                                               autocomplete="new-password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirm')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if (isset($errors['password_confirm'])): ?>
                                            <div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirm']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!$is_own_account): ?>
                                    <!-- Role -->
                                    <div class="form-group">
                                        <label class="form-label required" for="role">Role</label>
                                        <select class="form-select <?= isset($errors['role']) ? 'is-invalid' : '' ?>" 
                                                id="role" 
                                                name="role" 
                                                required>
                                            <option value="">Select Role</option>
                                            <option value="user" <?= $form_data['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                            <option value="admin" <?= $form_data['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                                        </select>
                                        <?php if (isset($errors['role'])): ?>
                                            <div class="invalid-feedback"><?= htmlspecialchars($errors['role']) ?></div>
                                        <?php endif; ?>
                                        <div class="form-help">
                                            <strong>User:</strong> Regular user with limited access<br>
                                            <strong>Administrator:</strong> Full access to all features
                                        </div>
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
                                                Active Account
                                            </label>
                                        </div>
                                        <div class="form-help">Inactive users cannot login to the system.</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>Update User
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
                    
                    <!-- User Information Card -->
                    <div class="form-section mt-4">
                        <div class="form-section-title">
                            <i class="fas fa-info-circle me-2"></i>Account Information
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">User ID:</label>
                                <div><?= $user['id'] ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Account Created:</label>
                                <div><?= formatDate($user['created_at'], 'd M Y H:i') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Last Updated:</label>
                                <div><?= formatDate($user['updated_at'], 'd M Y H:i') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Current Role:</label>
                                <div>
                                    <span class="badge <?= $user['role'] === 'admin' ? 'bg-success' : 'bg-primary' ?>">
                                        <i class="fas fa-<?= $user['role'] === 'admin' ? 'shield-alt' : 'user' ?> me-1"></i>
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php 
    $page_js = "
        // Form validation
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin me-2\"></i>Updating User...';
        });
        
        // Password toggle function
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // Toggle password fields
        document.getElementById('changePassword').addEventListener('change', function() {
            const passwordFields = document.querySelectorAll('.password-field');
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('password_confirm');
            
            if (this.checked) {
                passwordFields.forEach(field => field.style.display = 'block');
                passwordInput.required = true;
                confirmInput.required = true;
            } else {
                passwordFields.forEach(field => field.style.display = 'none');
                passwordInput.required = false;
                confirmInput.required = false;
                passwordInput.value = '';
                confirmInput.value = '';
            }
        });
        
        // Real-time validation
        document.getElementById('username').addEventListener('input', function() {
            const value = this.value;
            const regex = /^[a-zA-Z0-9_]+$/;
            
            if (value && !regex.test(value)) {
                this.setCustomValidity('Username can only contain letters, numbers and underscore');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Password confirmation validation
        document.getElementById('password_confirm').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            
            if (confirm && password !== confirm) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Email validation
        document.getElementById('email').addEventListener('input', function() {
            const email = this.value;
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !regex.test(email)) {
                this.setCustomValidity('Please enter a valid email address');
            } else {
                this.setCustomValidity('');
            }
        });
    ";
    ?>
</body>
</html>
