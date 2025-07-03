<?php
/**
 * Main Landing Page
 * Landing page atau redirect ke dashboard
 */

define('APP_ACCESS', true);
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Get current user info
$user_info = getSessionInfo();
$is_admin = isAdmin();

// Redirect admin users to admin dashboard
if ($is_admin) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

// Get some basic stats for regular users
$user_stats = [
    'my_orders' => 0,
    'recent_activities' => []
];

try {
    // Get user's order count
    $result = getRecord("SELECT COUNT(*) as total FROM orders WHERE user_id = ?", [$user_info['user_id']]);
    $user_stats['my_orders'] = $result['total'];
    
    // Get user's recent activities
    $user_stats['recent_activities'] = getRecentActivities(5, $user_info['user_id']);
    
} catch (Exception $e) {
    logMessage("Error getting user stats: " . $e->getMessage(), 'ERROR');
}

// Check for flash messages
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= APP_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-brand {
            font-weight: bold;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .stat-icon.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-icon.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .stat-icon.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .activity-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .quick-actions .btn {
            border-radius: 10px;
            padding: 12px 20px;
            margin: 5px;
        }
        
        .session-info {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-cube me-2"></i>
                <?= APP_NAME ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?= htmlspecialchars($user_info['full_name'] ?: $user_info['username']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                    <i class="fas fa-user-edit me-2"></i>Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container mt-4">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?= $flash['type'] === 'error' ? 'exclamation-circle' : 'check-circle' ?> me-2"></i>
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-wave-square me-2"></i>Welcome back, <?= htmlspecialchars($user_info['full_name'] ?: $user_info['username']) ?>!</h2>
                    <p class="mb-0">You're logged in as <strong><?= ucfirst($user_info['role']) ?></strong></p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="session-info">
                        <small>
                            <i class="fas fa-clock me-1"></i>
                            Login: <?= formatDate($user_info['login_time'] ? date('Y-m-d H:i:s', $user_info['login_time']) : '') ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <div class="stat-icon primary mx-auto">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3><?= $user_stats['my_orders'] ?></h3>
                        <p class="text-muted mb-0">My Orders</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <div class="stat-icon success mx-auto">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h3>Active</h3>
                        <p class="text-muted mb-0">Account Status</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card stat-card text-center">
                    <div class="card-body">
                        <div class="stat-icon warning mx-auto">
                            <i class="fas fa-history"></i>
                        </div>
                        <h3><?= count($user_stats['recent_activities']) ?></h3>
                        <p class="text-muted mb-0">Recent Activities</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($user_stats['recent_activities'])): ?>
                            <?php foreach ($user_stats['recent_activities'] as $activity): ?>
                                <div class="activity-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?= htmlspecialchars($activity['action']) ?></strong>
                                            <?php if ($activity['description']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($activity['description']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <small class="activity-time">
                                            <?= formatDate($activity['timestamp'], 'd M Y H:i') ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">
                                <i class="fas fa-info-circle me-2"></i>
                                No recent activities found.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-rocket me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body quick-actions text-center">
                        <p class="text-muted mb-3">Common actions for your role:</p>
                        
                        <a href="#" class="btn btn-outline-primary" onclick="showComingSoon()">
                            <i class="fas fa-eye me-2"></i>View Orders
                        </a>
                        
                        <a href="#" class="btn btn-outline-success" onclick="showComingSoon()">
                            <i class="fas fa-box me-2"></i>View Products
                        </a>
                        
                        <a href="#" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </a>
                        
                        <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>Profile Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Username:</strong><br>
                            <span class="text-muted"><?= htmlspecialchars($user_info['username']) ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Role:</strong><br>
                            <span class="badge bg-primary"><?= ucfirst($user_info['role']) ?></span>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Login Time:</strong><br>
                            <span class="text-muted"><?= formatDate($user_info['login_time'] ? date('Y-m-d H:i:s', $user_info['login_time']) : '') ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Session ID:</strong><br>
                            <span class="text-muted"><small><?= substr($user_info['session_id'], 0, 16) ?>...</small></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="showComingSoon()">Edit Profile</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showComingSoon() {
            alert('This feature is coming soon in the next development phase!');
        }
        
        // Session management
        let sessionCheckInterval;
        
        function checkSession() {
            fetch('/auth/check_session.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.logged_in) {
                        alert('Your session has expired. Redirecting to login page.');
                        window.location.href = data.redirect || '/auth/login.php';
                    } else if (data.show_warning) {
                        if (confirm(data.warning_message + ' Do you want to extend your session?')) {
                            extendSession(data.session_token);
                        }
                    }
                })
                .catch(error => {
                    console.error('Session check error:', error);
                });
        }
        
        function extendSession(token) {
            fetch('/auth/check_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'extend',
                    token: token
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('Session extended successfully');
                } else {
                    alert('Failed to extend session. Please login again.');
                    window.location.href = '/auth/login.php';
                }
            })
            .catch(error => {
                console.error('Session extend error:', error);
            });
        }
        
        // Check session every 2 minutes
        sessionCheckInterval = setInterval(checkSession, 2 * 60 * 1000);
        
        // Auto-dismiss alerts
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>