<?php
/**
 * Header Component
 * HTML header yang digunakan di semua halaman
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Set default page variables if not set
$page_title = $page_title ?? APP_NAME;
$page_description = $page_description ?? APP_DESCRIPTION;
$additional_css = $additional_css ?? [];
$body_class = $body_class ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="author" content="<?= APP_NAME ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    
    <title><?= htmlspecialchars($page_title) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= ASSETS_URL ?>/images/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS (if needed) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Chart.js (if charts are needed) -->
    <?php if (isset($include_charts) && $include_charts): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chart.js/3.9.1/chart.min.js"></script>
    <?php endif; ?>
    
    <!-- Custom CSS -->
    <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
    
    <!-- Admin CSS (jika halaman admin) -->
    <?php if (isset($is_admin_page) && $is_admin_page): ?>
        <link href="<?= ASSETS_URL ?>/css/admin.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Additional CSS files -->
    <?php foreach ($additional_css as $css_file): ?>
        <link href="<?= htmlspecialchars($css_file) ?>" rel="stylesheet">
    <?php endforeach; ?>
    
    <!-- Global CSS Variables -->
    <style>
        :root {
            --primary-color: #667eea;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --text-color: #212529;
            
            --sidebar-width: 250px;
            --navbar-height: 60px;
            --border-radius: 10px;
            --box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --box-shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
            
            --font-family-base: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            --font-size-base: 0.875rem;
            --line-height-base: 1.5;
        }
        
        body {
            font-family: var(--font-family-base);
            font-size: var(--font-size-base);
            line-height: var(--line-height-base);
            background-color: var(--light-color);
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Session warning modal styles */
        .session-warning {
            z-index: 10000;
        }
        
        /* Responsive utilities */
        @media (max-width: 768px) {
            :root {
                --sidebar-width: 100%;
                --font-size-base: 0.8rem;
            }
        }
    </style>
    
    <!-- JavaScript Global Variables -->
    <script>
        window.APP_CONFIG = {
            BASE_URL: '<?= BASE_URL ?>',
            ASSETS_URL: '<?= ASSETS_URL ?>',
            APP_NAME: '<?= APP_NAME ?>',
            CSRF_TOKEN: '<?= generateCSRFToken() ?>',
            SESSION_TIMEOUT: <?= SESSION_TIMEOUT ?>,
            USER_DATA: <?php 
                if (isLoggedIn()) {
                    echo json_encode([
                        'id' => getCurrentUserId(),
                        'username' => getCurrentUsername(),
                        'role' => getCurrentUserRole(),
                        'full_name' => getCurrentUserFullName()
                    ]);
                } else {
                    echo 'null';
                }
            ?>
        };
    </script>
</head>
<body class="<?= htmlspecialchars($body_class) ?>">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="loading-spinner"></div>
            <p class="mt-3 text-muted">Loading...</p>
        </div>
    </div>
    
    <!-- Session Warning Modal -->
    <div class="modal fade session-warning" id="sessionWarningModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-clock me-2"></i>Session Expiring
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <p id="sessionWarningMessage">Your session will expire soon.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-warning" id="extendSessionBtn">
                        <i class="fas fa-refresh me-2"></i>Extend Session
                    </button>
                    <button type="button" class="btn btn-secondary" id="logoutNowBtn">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout Now
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Flash Messages Container -->
    <div id="flashMessagesContainer" class="position-fixed" style="top: 80px; right: 20px; z-index: 1050;">
        <!-- Flash messages akan di-inject di sini via JavaScript -->
    </div>
    
    <!-- Page Content Start -->