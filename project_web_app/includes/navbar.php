<?php
/**
 * Navigation Bar Component
 * Navbar yang responsive untuk semua halaman
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_path = $_SERVER['REQUEST_URI'];

// Check if user is logged in
$is_logged_in = isLoggedIn();
$user_info = $is_logged_in ? getSessionInfo() : null;
$is_admin = $is_logged_in && isAdmin();

// Define menu items based on user role
$menu_items = [];

if ($is_admin) {
    $menu_items = [
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
            'active' => strpos($current_path, '/admin/users') !== false,
            'submenu' => [
                ['title' => 'All Users', 'url' => BASE_URL . '/admin/users/index.php'],
                ['title' => 'Add User', 'url' => BASE_URL . '/admin/users/add.php']
            ]
        ],
        'customers' => [
            'title' => 'Customers',
            'url' => BASE_URL . '/admin/customers/',
            'icon' => 'fas fa-user-friends',
            'active' => strpos($current_path, '/admin/customers') !== false,
            'submenu' => [
                ['title' => 'All Customers', 'url' => BASE_URL . '/admin/customers/index.php'],
                ['title' => 'Add Customer', 'url' => BASE_URL . '/admin/customers/add.php']
            ]
        ],
        'products' => [
            'title' => 'Products',
            'url' => BASE_URL . '/admin/products/',
            'icon' => 'fas fa-box',
            'active' => strpos($current_path, '/admin/products') !== false,
            'submenu' => [
                ['title' => 'All Products', 'url' => BASE_URL . '/admin/products/index.php'],
                ['title' => 'Add Product', 'url' => BASE_URL . '/admin/products/add.php'],
                ['title' => 'Categories', 'url' => BASE_URL . '/admin/products/categories.php']
            ]
        ],
        'orders' => [
            'title' => 'Orders',
            'url' => BASE_URL . '/admin/orders/',
            'icon' => 'fas fa-shopping-cart',
            'active' => strpos($current_path, '/admin/orders') !== false,
            'submenu' => [
                ['title' => 'All Orders', 'url' => BASE_URL . '/admin/orders/index.php'],
                ['title' => 'Add Order', 'url' => BASE_URL . '/admin/orders/add.php']
            ]
        ],
        'reports' => [
            'title' => 'Reports',
            'url' => BASE_URL . '/admin/reports/',
            'icon' => 'fas fa-chart-bar',
            'active' => strpos($current_path, '/admin/reports') !== false,
            'submenu' => [
                ['title' => 'Dashboard', 'url' => BASE_URL . '/admin/reports/index.php'],
                ['title' => 'Sales Report', 'url' => BASE_URL . '/admin/reports/sales.php']
            ]
        ]
    ];
} elseif ($is_logged_in) {
    $menu_items = [
        'dashboard' => [
            'title' => 'Dashboard',
            'url' => BASE_URL . '/index.php',
            'icon' => 'fas fa-home',
            'active' => $current_path === '/' || $current_path === '/index.php'
        ]
    ];
}
?>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-primary fixed-top shadow">
    <div class="container-fluid">
        <!-- Brand Logo -->
        <a class="navbar-brand d-flex align-items-center" href="<?= $is_logged_in ? ($is_admin ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/index.php') : BASE_URL . '/auth/login.php' ?>">>
            <i class="fas fa-cube me-2 fs-4"></i>
            <span class="fw-bold"><?= APP_NAME ?></span>
            <?php if (ENVIRONMENT === 'development'): ?>
                <span class="badge bg-warning text-dark ms-2 small">DEV</span>
            <?php endif; ?>
        </a>
        
        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if ($is_logged_in): ?>
                <!-- Main Menu -->
                <ul class="navbar-nav me-auto">
                    <?php foreach ($menu_items as $key => $item): ?>
                        <li class="nav-item <?= isset($item['submenu']) ? 'dropdown' : '' ?>">
                            <?php if (isset($item['submenu'])): ?>
                                <!-- Dropdown Menu -->
                                <a class="nav-link dropdown-toggle <?= $item['active'] ? 'active' : '' ?>" 
                                   href="#" 
                                   id="navbarDropdown<?= ucfirst($key) ?>" 
                                   role="button" 
                                   data-bs-toggle="dropdown" 
                                   aria-expanded="false">
                                    <i class="<?= $item['icon'] ?> me-1"></i>
                                    <?= $item['title'] ?>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown<?= ucfirst($key) ?>">
                                    <?php foreach ($item['submenu'] as $subitem): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?= $subitem['url'] ?>">
                                                <?= $subitem['title'] ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <!-- Regular Menu -->
                                <a class="nav-link <?= $item['active'] ? 'active' : '' ?>" href="<?= $item['url'] ?>">
                                    <i class="<?= $item['icon'] ?> me-1"></i>
                                    <?= $item['title'] ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <!-- Right Side Menu -->
                <ul class="navbar-nav">
                    <!-- Notifications (placeholder) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                0
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <li><a class="dropdown-item text-muted text-center" href="#">No new notifications</a></li>
                        </ul>
                    </li>
                    
                    <!-- Session Timer -->
                    <li class="nav-item d-none d-lg-block">
                        <span class="nav-link text-muted small" id="sessionTimer">
                            <i class="fas fa-clock me-1"></i>
                            <span id="timeRemaining">--:--</span>
                        </span>
                    </li>
                    
                    <!-- User Profile Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" 
                           href="#" 
                           id="userProfileDropdown" 
                           role="button" 
                           data-bs-toggle="dropdown" 
                           aria-expanded="false">
                            <div class="user-avatar me-2">
                                <i class="fas fa-user-circle fs-5"></i>
                            </div>
                            <div class="d-none d-md-block">
                                <div class="fw-semibold small">
                                    <?= htmlspecialchars($user_info['full_name'] ?: $user_info['username']) ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    <?= ucfirst($user_info['role']) ?>
                                </div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userProfileDropdown">
                            <li>
                                <div class="dropdown-header">
                                    <div class="fw-bold"><?= htmlspecialchars($user_info['username']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($user_info['role']) ?></small>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                    <i class="fas fa-user-edit me-2"></i>Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="showComingSoon()">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            <?php else: ?>
                <!-- Not Logged In Menu -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Spacer for fixed navbar -->
<div style="height: <?= isset($navbar_spacer) ? $navbar_spacer : '80px' ?>;"></div>

<!-- Profile Modal (jika user login) -->
<?php if ($is_logged_in): ?>
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">
                    <i class="fas fa-user-circle me-2"></i>Profile Information
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Username:</label>
                        <p class="text-muted"><?= htmlspecialchars($user_info['username']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Role:</label>
                        <p><span class="badge bg-primary"><?= ucfirst($user_info['role']) ?></span></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Full Name:</label>
                        <p class="text-muted"><?= htmlspecialchars($user_info['full_name'] ?: 'Not set') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Login Time:</label>
                        <p class="text-muted">
                            <?= $user_info['login_time'] ? formatDate(date('Y-m-d H:i:s', $user_info['login_time']), 'd M Y H:i') : 'Unknown' ?>
                        </p>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Session Info:</label>
                        <div class="bg-light p-3 rounded">
                            <small class="text-muted">
                                <strong>Session ID:</strong> <?= substr($user_info['session_id'], 0, 16) ?>...<br>
                                <strong>IP Address:</strong> <?= htmlspecialchars($user_info['ip_address'] ?? 'Unknown') ?><br>
                                <strong>Time Remaining:</strong> <span id="modalTimeRemaining">Calculating...</span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="showComingSoon()">
                    <i class="fas fa-edit me-2"></i>Edit Profile
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Additional navbar styles */
.navbar-brand {
    font-size: 1.25rem;
}

.user-avatar {
    color: #fff;
}

.dropdown-menu {
    border: none;
    box-shadow: var(--box-shadow-lg);
    border-radius: var(--border-radius);
}

.dropdown-item {
    padding: 0.5rem 1rem;
    border-radius: 5px;
    margin: 2px 5px;
}

.dropdown-item:hover {
    background-color: var(--light-color);
}

.navbar-nav .nav-link.active {
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 5px;
}

/* Session timer styles */
#sessionTimer {
    font-family: 'Courier New', monospace;
}

/* Responsive adjustments */
@media (max-width: 991px) {
    .navbar-nav {
        padding: 1rem 0;
    }
    
    .dropdown-menu {
        position: static !important;
        float: none;
        box-shadow: none;
        border: 1px solid #dee2e6;
        margin: 0.5rem 0;
    }
}

/* Animation for navbar collapse */
.navbar-collapse {
    transition: all 0.3s ease;
}

/* Mobile navbar improvements */
@media (max-width: 768px) {
    .navbar-brand span {
        font-size: 1rem;
    }
    
    .nav-link {
        padding: 0.75rem 1rem;
    }
}
</style>