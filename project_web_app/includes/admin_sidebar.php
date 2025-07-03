<?php
/**
 * Admin Sidebar Component
 * Sidebar yang konsisten untuk semua halaman admin
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_path = $_SERVER['REQUEST_URI'];

// Define admin menu items
$admin_menu = [
    'dashboard' => [
        'title' => 'Dashboard',
        'url' => BASE_URL . '/admin/dashboard.php',
        'icon' => 'fas fa-tachometer-alt',
        'active' => strpos($current_path, '/admin/dashboard') !== false
    ],
    'users' => [
        'title' => 'Users',
        'url' => BASE_URL . '/admin/users/',
        'icon' => 'fas fa-users',
        'active' => strpos($current_path, '/admin/users') !== false
    ],
    'products' => [
        'title' => 'Products',
        'url' => BASE_URL . '/admin/products/',
        'icon' => 'fas fa-box',
        'active' => strpos($current_path, '/admin/products') !== false
    ],
    'orders' => [
        'title' => 'Orders',
        'url' => BASE_URL . '/admin/orders/',
        'icon' => 'fas fa-shopping-cart',
        'active' => strpos($current_path, '/admin/orders') !== false
    ],
    'customers' => [
        'title' => 'Customers',
        'url' => BASE_URL . '/admin/customers/',
        'icon' => 'fas fa-user-friends',
        'active' => strpos($current_path, '/admin/customers') !== false
    ],
    'reports' => [
        'title' => 'Reports',
        'url' => BASE_URL . '/admin/reports/',
        'icon' => 'fas fa-chart-line',
        'active' => strpos($current_path, '/admin/reports') !== false
    ]
];
?>

<!-- Admin Sidebar -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <h4><i class="fas fa-cube me-2"></i><?= APP_NAME ?></h4>
        <small>Admin Panel</small>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link <?= strpos($current_path, '/admin/dashboard') !== false ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Management</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>/admin/users/" class="nav-link <?= strpos($current_path, '/admin/users') !== false ? 'active' : '' ?>">
                    <i class="fas fa-users me-2"></i>Users
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>/admin/products/" class="nav-link <?= strpos($current_path, '/admin/products') !== false ? 'active' : '' ?>">
                    <i class="fas fa-box me-2"></i>Products
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>/admin/orders/" class="nav-link <?= strpos($current_path, '/admin/orders') !== false ? 'active' : '' ?>">
                    <i class="fas fa-shopping-cart me-2"></i>Orders
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>/admin/customers/" class="nav-link <?= strpos($current_path, '/admin/customers') !== false ? 'active' : '' ?>">
                    <i class="fas fa-user-friends me-2"></i>Customers
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>/admin/reports/" class="nav-link <?= strpos($current_path, '/admin/reports') !== false ? 'active' : '' ?>">
                    <i class="fas fa-chart-line me-2"></i>Reports
                </a>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>
</aside>
        
        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>
</aside>
