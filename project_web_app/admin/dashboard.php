<?php
/**
 * Admin Dashboard
 * Halaman utama admin panel
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check admin access
requireAdmin();

// Page configuration
$page_title = 'Admin Dashboard - ' . APP_NAME;
$is_admin_page = true;
$include_charts = true;
$include_datatables = true;

// Get dashboard statistics
$stats = getSystemStats();

// Get recent activities
$recent_activities = getRecentActivities(10);

// Get dashboard data
$dashboard_data = [
    'total_users' => $stats['total_users'] ?? 0,
    'total_products' => $stats['total_products'] ?? 0,
    'total_orders' => $stats['total_orders'] ?? 0,
    'total_revenue' => $stats['total_revenue'] ?? 0,
    'low_stock_products' => $stats['low_stock_products'] ?? 0,
    'recent_activities' => $recent_activities
];

// Check for flash messages
$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $additional_css = [ASSETS_URL . '/css/admin.css'];
    include '../includes/header.php'; 
    ?>

    <div class="admin-container">
        <!-- Sidebar -->
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="admin-content">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-left">
                    <h1>Dashboard</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </nav>
                </div>
                
                <div class="header-controls">
                    <button class="btn btn-outline-primary btn-sm" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
            </header>
            
            <!-- Dashboard Content -->
            <div class="admin-main">
                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $flash['type'] === 'error' ? 'exclamation-circle' : 'check-circle' ?> me-2"></i>
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Welcome Message -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card card-gradient">
                            <div class="card-body text-center py-4">
                                <h3 class="text-white mb-2">Welcome back, <?= htmlspecialchars(getCurrentUserFullName() ?: getCurrentUsername()) ?>!</h3>
                                <p class="text-white-50 mb-0">Here's what's happening with your business today.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="dashboard-stats">
                    <div class="stat-card-admin" data-link="<?= BASE_URL ?>/admin/users/">
                        <div class="stat-header">
                            <div class="stat-title">Total Users</div>
                            <div class="stat-icon-admin">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= number_format($dashboard_data['total_users']) ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>12% from last month</span>
                        </div>
                    </div>
                    
                    <div class="stat-card-admin success" data-link="<?= BASE_URL ?>/admin/products/">
                        <div class="stat-header">
                            <div class="stat-title">Total Products</div>
                            <div class="stat-icon-admin">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= number_format($dashboard_data['total_products']) ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>8% from last month</span>
                        </div>
                    </div>
                    
                    <div class="stat-card-admin warning" data-link="<?= BASE_URL ?>/admin/orders/">
                        <div class="stat-header">
                            <div class="stat-title">Total Orders</div>
                            <div class="stat-icon-admin">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= number_format($dashboard_data['total_orders']) ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>24% from last month</span>
                        </div>
                    </div>
                    
                    <div class="stat-card-admin info">
                        <div class="stat-header">
                            <div class="stat-title">Revenue</div>
                            <div class="stat-icon-admin">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= formatCurrency($dashboard_data['total_revenue']) ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>18% from last month</span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions and Recent Activity -->
                <div class="admin-grid-3">
                    <!-- Quick Actions -->
                    <div class="widget-container">
                        <div class="widget-header">
                            <h5 class="widget-title">Quick Actions</h5>
                        </div>
                        <div class="widget-body">
                            <div class="d-grid gap-2">
                                <a href="<?= BASE_URL ?>/admin/users/add.php" class="btn btn-outline-primary">
                                    <i class="fas fa-user-plus me-2"></i>Add New User
                                </a>
                                <a href="<?= BASE_URL ?>/admin/products/add.php" class="btn btn-outline-success">
                                    <i class="fas fa-plus me-2"></i>Add Product
                                </a>
                                <a href="<?= BASE_URL ?>/admin/orders/" class="btn btn-outline-warning">
                                    <i class="fas fa-eye me-2"></i>View Orders
                                </a>
                                <a href="<?= BASE_URL ?>/admin/reports/" class="btn btn-outline-info">
                                    <i class="fas fa-chart-bar me-2"></i>Generate Report
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Status -->
                    <div class="widget-container">
                        <div class="widget-header">
                            <h5 class="widget-title">System Status</h5>
                        </div>
                        <div class="widget-body">
                            <div class="mb-3">
                                <div class="progress-label">
                                    <span>Server Load</span>
                                    <span class="percentage">23%</span>
                                </div>
                                <div class="progress-admin">
                                    <div class="progress-bar-admin" style="width: 23%"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="progress-label">
                                    <span>Storage Used</span>
                                    <span class="percentage">67%</span>
                                </div>
                                <div class="progress-admin">
                                    <div class="progress-bar-admin" style="width: 67%"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="progress-label">
                                    <span>Memory Usage</span>
                                    <span class="percentage">45%</span>
                                </div>
                                <div class="progress-admin">
                                    <div class="progress-bar-admin" style="width: 45%"></div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <span class="status-indicator online"></span>
                                <small class="text-muted">All systems operational</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="widget-container">
                        <div class="widget-header">
                            <h5 class="widget-title">Recent Activity</h5>
                            <div class="widget-actions">
                                <a href="<?= BASE_URL ?>/admin/logs/" class="btn btn-sm btn-outline-secondary">View All</a>
                            </div>
                        </div>
                        <div class="widget-body">
                            <div class="activity-timeline">
                                <?php if (!empty($dashboard_data['recent_activities'])): ?>
                                    <?php foreach (array_slice($dashboard_data['recent_activities'], 0, 5) as $activity): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker"></div>
                                            <div class="timeline-content">
                                                <div class="timeline-time">
                                                    <?= formatDate($activity['timestamp'], 'H:i') ?>
                                                </div>
                                                <div class="timeline-title">
                                                    <?= htmlspecialchars($activity['action']) ?>
                                                </div>
                                                <div class="timeline-description">
                                                    <?= htmlspecialchars($activity['description'] ?: 'No description') ?>
                                                    <?php if ($activity['username']): ?>
                                                        by <strong><?= htmlspecialchars($activity['username']) ?></strong>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No recent activities
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Alerts Section -->
                <?php if ($dashboard_data['low_stock_products'] > 0): ?>
                <div class="alert alert-warning" role="alert">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
                    </h6>
                    <p class="mb-0">
                        You have <strong><?= $dashboard_data['low_stock_products'] ?></strong> products with low stock. 
                        <a href="<?= BASE_URL ?>/admin/products/?filter=low_stock" class="alert-link">View products</a>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <?php 
    $page_js = "
        // Refresh dashboard function
        function refreshDashboard() {
            App.showLoading();
            location.reload();
        }
    ";
    ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        <?php echo $page_js; ?>
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Dashboard initialized
        });
    </script>
</body>
</html>