<?php
/**
 * Reports & Analytics - Dashboard
 * Admin page untuk reports dan analytics
 */

define('APP_ACCESS', true);
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Require admin access
requireAdmin();

$page_title = 'Reports & Analytics - ' . APP_NAME;
$is_admin_page = true;

// Date range for reports
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Sales Analytics
$sales_data = getRecord("
    SELECT 
        COUNT(o.id) as total_orders,
        COALESCE(SUM(od.quantity * od.price), 0) as total_revenue,
        COALESCE(AVG(od.quantity * od.price), 0) as avg_order_value,
        COUNT(DISTINCT o.customer_id) as unique_customers
    FROM orders o 
    LEFT JOIN order_details od ON o.id = od.order_id 
    WHERE DATE(o.order_date) BETWEEN ? AND ?
", [$date_from, $date_to]);

// Orders by Status
$orders_by_status = getRecords("
    SELECT 
        status, 
        COUNT(*) as count,
        COALESCE(SUM(od.quantity * od.price), 0) as revenue
    FROM orders o 
    LEFT JOIN order_details od ON o.id = od.order_id 
    WHERE DATE(o.order_date) BETWEEN ? AND ?
    GROUP BY status
    ORDER BY count DESC
", [$date_from, $date_to]);

// Daily Sales Chart Data (Last 30 days)
$daily_sales = getRecords("
    SELECT 
        DATE(o.order_date) as date,
        COUNT(o.id) as orders,
        COALESCE(SUM(od.quantity * od.price), 0) as revenue
    FROM orders o 
    LEFT JOIN order_details od ON o.id = od.order_id 
    WHERE DATE(o.order_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(o.order_date)
    ORDER BY date DESC
    LIMIT 30
");

// Top Products
$top_products = getRecords("
    SELECT 
        p.name,
        SUM(od.quantity) as total_sold,
        SUM(od.quantity * od.price) as total_revenue
    FROM order_details od
    JOIN products p ON od.product_id = p.id
    JOIN orders o ON od.order_id = o.id
    WHERE DATE(o.order_date) BETWEEN ? AND ?
    GROUP BY p.id, p.name
    ORDER BY total_sold DESC
    LIMIT 10
", [$date_from, $date_to]);

// Recent Activity
$recent_activities = getRecords("
    SELECT * FROM activity_logs 
    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY timestamp DESC 
    LIMIT 20
");

// Include header
include '../../includes/header.php';
?>

<div class="d-flex">
    <?php include '../../includes/admin_sidebar.php'; ?>
    
    <div class="admin-content flex-grow-1">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h2>
                <p class="text-muted mb-0">Business insights and performance metrics</p>
            </div>
            <div class="d-flex gap-2">
                <a href="sales.php" class="btn btn-outline-primary">
                    <i class="fas fa-chart-line me-2"></i>Sales Report
                </a>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control" 
                               value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control" 
                               value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Apply Filter
                        </button>
                    </div>
                    <div class="col-md-4 text-end">
                        <small class="text-muted">
                            Showing data from <?= formatDate($date_from) ?> to <?= formatDate($date_to) ?>
                        </small>
                    </div>
                </form>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
                        <h3 class="mb-0"><?= number_format($sales_data['total_orders']) ?></h3>
                        <small class="text-muted">Total Orders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                        <h3 class="mb-0"><?= formatCurrency($sales_data['total_revenue']) ?></h3>
                        <small class="text-muted">Total Revenue</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                        <h3 class="mb-0"><?= formatCurrency($sales_data['avg_order_value']) ?></h3>
                        <small class="text-muted">Avg Order Value</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-warning mb-2"></i>
                        <h3 class="mb-0"><?= number_format($sales_data['unique_customers']) ?></h3>
                        <small class="text-muted">Unique Customers</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sales Chart -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-area me-2"></i>Daily Sales (Last 30 Days)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Orders by Status -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-pie-chart me-2"></i>Orders by Status</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top Products -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Selling Products</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_products)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-box fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No product sales data available</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['name']) ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?= number_format($product['total_sold']) ?></span>
                                                </td>
                                                <td class="fw-bold"><?= formatCurrency($product['total_revenue']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_activities)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-history fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No recent activity</p>
                            </div>
                        <?php else: ?>
                            <div class="activity-list" style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="activity-icon me-3">
                                            <?php
                                            $icon_colors = [
                                                'CREATE' => 'success',
                                                'UPDATE' => 'warning',
                                                'DELETE' => 'danger',
                                                'LOGIN' => 'info',
                                                'LOGOUT' => 'secondary'
                                            ];
                                            $color = $icon_colors[$activity['action']] ?? 'primary';
                                            ?>
                                            <i class="fas fa-circle text-<?= $color ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold small"><?= htmlspecialchars($activity['description']) ?></div>
                                            <small class="text-muted">
                                                <?= timeAgo($activity['timestamp']) ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: [<?= implode(',', array_map(function($item) { return '"' . formatDate($item['date']) . '"'; }, array_reverse($daily_sales))) ?>],
        datasets: [{
            label: 'Revenue',
            data: [<?= implode(',', array_map(function($item) { return $item['revenue']; }, array_reverse($daily_sales))) ?>],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1
        }, {
            label: 'Orders',
            data: [<?= implode(',', array_map(function($item) { return $item['orders']; }, array_reverse($daily_sales))) ?>],
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            tension: 0.1,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                },
            }
        }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: [<?= implode(',', array_map(function($item) { return '"' . ucfirst($item['status']) . '"'; }, $orders_by_status)) ?>],
        datasets: [{
            data: [<?= implode(',', array_map(function($item) { return $item['count']; }, $orders_by_status)) ?>],
            backgroundColor: [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0',
                '#9966FF',
                '#FF9F40'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>
