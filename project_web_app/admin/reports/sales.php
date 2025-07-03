<?php
/**
 * Reports - Sales Report
 * Detailed sales analytics and reporting
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Require admin access
requireAdmin();

$page_title = 'Sales Report - ' . APP_NAME;
$is_admin_page = true;

// Date range parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today
$period = $_GET['period'] ?? 'daily'; // daily, weekly, monthly

// Validate date range
if (strtotime($date_from) > strtotime($date_to)) {
    $temp = $date_from;
    $date_from = $date_to;
    $date_to = $temp;
}

// Validate period
if (!in_array($period, ['daily', 'weekly', 'monthly'])) {
    $period = 'daily';
}

try {
    // Sales summary - simplified query first
    $sales_summary = getRecord("
        SELECT 
            COUNT(o.id) as total_orders,
            COUNT(DISTINCT o.customer_id) as unique_customers,
            COALESCE(SUM(o.total_amount), 0) as total_revenue,
            COALESCE(AVG(o.total_amount), 0) as avg_order_value
        FROM orders o 
        WHERE DATE(o.order_date) BETWEEN ? AND ?
        AND o.status NOT IN ('cancelled')
    ", [$date_from, $date_to]);

    // If no sales summary data, provide defaults
    if (!$sales_summary) {
        $sales_summary = [
            'total_orders' => 0,
            'unique_customers' => 0,
            'total_revenue' => 0,
            'avg_order_value' => 0
        ];
    }

    // Sales by period
    $period_format = [
        'daily' => '%Y-%m-%d',
        'weekly' => '%Y-Week %u',
        'monthly' => '%Y-%m'
    ];

    $format = $period_format[$period];
    $sales_by_period = getRecords("
        SELECT 
            DATE_FORMAT(o.order_date, ?) as period,
            COUNT(o.id) as orders_count,
            COALESCE(SUM(o.total_amount), 0) as revenue
        FROM orders o 
        WHERE DATE(o.order_date) BETWEEN ? AND ?
        AND o.status NOT IN ('cancelled')
        GROUP BY period
        ORDER BY period DESC
        LIMIT 20
    ", [$format, $date_from, $date_to]);

    // Top products - check if order_details table exists and has data
    $top_products = [];
    try {
        $top_products = getRecords("
            SELECT 
                p.name,
                p.sku,
                SUM(od.quantity) as total_sold,
                SUM(od.quantity * od.price) as total_revenue,
                AVG(od.price) as avg_price
            FROM order_details od
            JOIN products p ON od.product_id = p.id
            JOIN orders o ON od.order_id = o.id
            WHERE DATE(o.order_date) BETWEEN ? AND ?
            AND o.status NOT IN ('cancelled')
            GROUP BY p.id, p.name, p.sku
            ORDER BY total_revenue DESC
            LIMIT 10
        ", [$date_from, $date_to]);
    } catch (Exception $e) {
        // If order_details query fails, skip this section
        $top_products = [];
    }

    // Sales by status
    $sales_by_status = getRecords("
        SELECT 
            o.status,
            COUNT(*) as count,
            COALESCE(SUM(o.total_amount), 0) as revenue
        FROM orders o 
        WHERE DATE(o.order_date) BETWEEN ? AND ?
        GROUP BY o.status
        ORDER BY revenue DESC
    ", [$date_from, $date_to]);

    // Customer analysis - simplified
    $customer_analysis = getRecord("
        SELECT 
            COUNT(DISTINCT o.customer_id) as unique_customers,
            COUNT(o.id) as total_orders,
            COALESCE(AVG(o.total_amount), 0) as avg_order_value
        FROM orders o 
        WHERE DATE(o.order_date) BETWEEN ? AND ?
        AND o.status NOT IN ('cancelled')
    ", [$date_from, $date_to]);

    if (!$customer_analysis) {
        $customer_analysis = [
            'unique_customers' => 0,
            'total_orders' => 0,
            'avg_order_value' => 0
        ];
    }

} catch (Exception $e) {
    // Set default values if any query fails
    $sales_summary = [
        'total_orders' => 0,
        'unique_customers' => 0,
        'total_revenue' => 0,
        'avg_order_value' => 0
    ];
    $sales_by_period = [];
    $top_products = [];
    $sales_by_status = [];
    $customer_analysis = [
        'unique_customers' => 0,
        'total_orders' => 0,
        'avg_order_value' => 0
    ];
    
    // Log the error for debugging
    error_log("Sales report error: " . $e->getMessage());
    $error_message = "Error loading sales data. Please check if orders exist in the selected date range.";
}

// Check for flash messages
$flash = getFlashMessage();

// Include header
include '../../includes/header.php';
?>

<div class="d-flex">
    <?php include '../../includes/admin_sidebar.php'; ?>
    
    <div class="admin-content flex-grow-1">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-chart-line me-2"></i>Sales Report</h2>
                <p class="text-muted mb-0">Detailed sales analytics and insights</p>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Reports
                </a>
            </div>
        </div>

        <!-- Error Message -->
        <?php if (isset($error_message)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Flash Messages -->
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
                <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>Filter Options
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="period" class="form-label">Group By</label>
                        <select class="form-select" id="period" name="period">
                            <option value="daily" <?= $period === 'daily' ? 'selected' : '' ?>>Daily</option>
                            <option value="weekly" <?= $period === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= $period === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Apply Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Orders
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= number_format($sales_summary['total_orders']) ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total Revenue
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= formatCurrency($sales_summary['total_revenue']) ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Average Order Value
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= formatCurrency($sales_summary['avg_order_value']) ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Unique Customers
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= number_format($sales_summary['unique_customers']) ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Sales Trend Chart -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Sales Trend (<?= ucfirst($period) ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="salesTrendChart" width="400" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- Sales by Status -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Sales by Status</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Tables Row -->
        <div class="row">
            <!-- Top Products -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Top Products</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_products)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No products sold in selected period</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th class="text-end">Sold</th>
                                            <th class="text-end">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($top_products, 0, 10) as $product): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($product['name']) ?></strong>
                                                </td>
                                                <td>
                                                    <code><?= htmlspecialchars($product['sku']) ?></code>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge bg-primary"><?= number_format($product['total_sold']) ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <strong><?= formatCurrency($product['total_revenue']) ?></strong>
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

            <!-- Sales by Status Table -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Orders by Status</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($sales_by_status)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No orders found in selected period</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th class="text-end">Orders</th>
                                            <th class="text-end">Revenue</th>
                                            <th class="text-end">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_orders = array_sum(array_column($sales_by_status, 'count'));
                                        foreach ($sales_by_status as $status): 
                                            $percentage = $total_orders > 0 ? ($status['count'] / $total_orders) * 100 : 0;
                                            $status_colors = [
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            $color = $status_colors[$status['status']] ?? 'secondary';
                                        ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-<?= $color ?>">
                                                        <?= ucfirst($status['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end"><?= number_format($status['count']) ?></td>
                                                <td class="text-end"><?= formatCurrency($status['revenue']) ?></td>
                                                <td class="text-end"><?= number_format($percentage, 1) ?>%</td>
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Sales Trend Chart
const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
new Chart(salesTrendCtx, {
    type: 'line',
    data: {
        labels: [<?= implode(',', array_map(function($item) { return '"' . $item['period'] . '"'; }, $sales_by_period)) ?>],
        datasets: [{
            label: 'Revenue',
            data: [<?= implode(',', array_column($sales_by_period, 'revenue')) ?>],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Sales Trend Over Time'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: [<?= implode(',', array_map(function($item) { return '"' . ucfirst($item['status']) . '"'; }, $sales_by_status)) ?>],
        datasets: [{
            data: [<?= implode(',', array_column($sales_by_status, 'count')) ?>],
            backgroundColor: [
                '#28a745',
                '#ffc107', 
                '#17a2b8',
                '#dc3545'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});

// Quick date range buttons
document.addEventListener('DOMContentLoaded', function() {
    // Add quick date range buttons if needed
});
</script>
