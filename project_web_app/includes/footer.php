<?php
/**
 * Footer Component
 * Footer yang digunakan di semua halaman
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Get current year for copyright
$current_year = date('Y');

// Default JavaScript files yang selalu dimuat
$default_js = [
    'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js'
];

// Additional JavaScript files (jika diperlukan)
$additional_js = $additional_js ?? [];

// DataTables JS (jika diperlukan)
if (isset($include_datatables) && $include_datatables) {
    $additional_js[] = 'https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/js/jquery.dataTables.min.js';
    $additional_js[] = 'https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/js/dataTables.bootstrap5.min.js';
}

// Chart.js (jika diperlukan)
if (isset($include_charts) && $include_charts) {
    $additional_js[] = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js';
}
?>

    <!-- Page Content End -->
    
    <!-- Footer -->
    <footer class="bg-primary text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-cube me-2 fs-4"></i>
                        <h5 class="mb-0"><?= APP_NAME ?></h5>
                    </div>
                    <p class="text-muted mb-2"><?= APP_DESCRIPTION ?></p>
                    <p class="text-muted small mb-0">
                        Version <?= APP_VERSION ?> | 
                        Environment: <span class="badge bg-<?= ENVIRONMENT === 'production' ? 'success' : 'warning' ?>">
                            <?= ucfirst(ENVIRONMENT) ?>
                        </span>
                    </p>
                </div>
                
                <div class="col-md-3">
                    <h6 class="text-uppercase fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <?php if (isLoggedIn()): ?>
                            <?php if (isAdmin()): ?>
                                <li><a href="/admin/dashboard.php" class="text-muted text-decoration-none">Dashboard</a></li>
                                <li><a href="/admin/users/" class="text-muted text-decoration-none">Users</a></li>
                                <li><a href="/admin/reports/" class="text-muted text-decoration-none">Reports</a></li>
                            <?php else: ?>
                                <li><a href="/index.php" class="text-muted text-decoration-none">Dashboard</a></li>
                            <?php endif; ?>
                            <li><a href="/auth/logout.php" class="text-muted text-decoration-none">Logout</a></li>
                        <?php else: ?>
                            <li><a href="/auth/login.php" class="text-muted text-decoration-none">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-md-3">
                    <h6 class="text-uppercase fw-bold mb-3">System Info</h6>
                    <div class="text-muted small">
                        <div class="mb-1">
                            <i class="fas fa-server me-2"></i>
                            Server: <?= php_uname('n') ?>
                        </div>
                        <div class="mb-1">
                            <i class="fas fa-code me-2"></i>
                            PHP: <?= PHP_VERSION ?>
                        </div>
                        <div class="mb-1">
                            <i class="fas fa-clock me-2"></i>
                            Timezone: <?= DEFAULT_TIMEZONE ?>
                        </div>
                        <?php if (isLoggedIn()): ?>
                            <div class="mb-1">
                                <i class="fas fa-user me-2"></i>
                                User: <?= htmlspecialchars(getCurrentUsername()) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted small mb-0">
                        &copy; <?= $current_year ?> <?= APP_NAME ?>. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex justify-content-md-end justify-content-center">
                        <button class="btn btn-outline-light btn-sm me-2" onclick="scrollToTop()" title="Back to Top">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        
                        <?php if (isDebugMode()): ?>
                            <button class="btn btn-outline-warning btn-sm me-2" onclick="toggleDebugInfo()" title="Debug Info">
                                <i class="fas fa-bug"></i>
                            </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-outline-info btn-sm" onclick="showSystemInfo()" title="System Info">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Debug Information (Development Only) -->
    <?php if (isDebugMode()): ?>
        <div id="debugInfo" class="bg-warning text-dark p-3" style="display: none;">
            <div class="container">
                <h6><i class="fas fa-bug me-2"></i>Debug Information</h6>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Session Data:</strong>
                        <pre class="small bg-light p-2 rounded mt-1"><?= json_encode($_SESSION ?? [], JSON_PRETTY_PRINT) ?></pre>
                    </div>
                    <div class="col-md-6">
                        <strong>Server Info:</strong>
                        <pre class="small bg-light p-2 rounded mt-1"><?php
                            echo "Current Page: " . $_SERVER['PHP_SELF'] . "\n";
                            echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
                            echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
                            echo "Memory Usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
                            echo "Peak Memory: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
                            echo "Execution Time: " . round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? 0)), 4) . "s\n";
                        ?></pre>
                    </div>
                    <div class="col-12 mt-3">
                        <strong>Database Queries:</strong>
                        <div class="small bg-light p-2 rounded mt-1">
                            <em>Query logging would appear here in development mode</em>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Load JavaScript Files -->
    <?php foreach ($default_js as $js_file): ?>
        <script src="<?= htmlspecialchars($js_file) ?>"></script>
    <?php endforeach; ?>
    
    <?php foreach ($additional_js as $js_file): ?>
        <script src="<?= htmlspecialchars($js_file) ?>"></script>
    <?php endforeach; ?>
    
    <!-- Custom JavaScript -->
    <script src="<?= ASSETS_URL ?>/js/custom.js"></script>
    
    <!-- Global JavaScript Functions -->
    <script>
        // Global app object
        window.App = window.App || {};
        
        // CSRF Token for AJAX requests
        window.App.csrfToken = '<?= generateCSRFToken() ?>';
        
        // App configuration
        window.App.config = {
            baseUrl: '<?= BASE_URL ?>',
            assetsUrl: '<?= ASSETS_URL ?>',
            environment: '<?= ENVIRONMENT ?>',
            sessionTimeout: <?= SESSION_TIMEOUT ?>,
            isLoggedIn: <?= isLoggedIn() ? 'true' : 'false' ?>,
            isAdmin: <?= (isLoggedIn() && isAdmin()) ? 'true' : 'false' ?>
        };
        
        // Utility functions
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function toggleDebugInfo() {
            const debugDiv = document.getElementById('debugInfo');
            if (debugDiv) {
                debugDiv.style.display = debugDiv.style.display === 'none' ? 'block' : 'none';
            }
        }
        
        function showSystemInfo() {
            const modal = new bootstrap.Modal(document.getElementById('systemInfoModal') || createSystemInfoModal());
            modal.show();
        }
        
        function createSystemInfoModal() {
            const modalHtml = `
                <div class="modal fade" id="systemInfoModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">System Information</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Application</h6>
                                        <ul class="list-unstyled small">
                                            <li><strong>Name:</strong> <?= APP_NAME ?></li>
                                            <li><strong>Version:</strong> <?= APP_VERSION ?></li>
                                            <li><strong>Environment:</strong> <?= ENVIRONMENT ?></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Server</h6>
                                        <ul class="list-unstyled small">
                                            <li><strong>PHP:</strong> <?= PHP_VERSION ?></li>
                                            <li><strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></li>
                                            <li><strong>OS:</strong> <?= PHP_OS ?></li>
                                        </ul>
                                    </div>
                                </div>
                                <?php if (isLoggedIn()): ?>
                                <hr>
                                <div class="row">
                                    <div class="col-12">
                                        <h6>Session Information</h6>
                                        <ul class="list-unstyled small">
                                            <li><strong>User:</strong> <?= htmlspecialchars(getCurrentUsername()) ?></li>
                                            <li><strong>Role:</strong> <?= htmlspecialchars(getCurrentUserRole()) ?></li>
                                            <li><strong>Session ID:</strong> <code><?= session_id() ?></code></li>
                                        </ul>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            return document.getElementById('systemInfoModal');
        }
        
        // Show flash messages
        function showFlashMessage(message, type = 'info') {
            const alertClass = {
                'success': 'alert-success',
                'error': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            }[type] || 'alert-info';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            const container = document.getElementById('flashMessagesContainer');
            if (container) {
                container.insertAdjacentHTML('beforeend', alertHtml);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    const alerts = container.querySelectorAll('.alert');
                    if (alerts.length > 0) {
                        const alert = alerts[0];
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Setup AJAX defaults
            if (typeof $ !== 'undefined') {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': window.App.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            }
        });
        
        <?php if (isLoggedIn()): ?>
        // Session monitoring for logged-in users
        let sessionCheckInterval;
        
        function startSessionMonitoring() {
            sessionCheckInterval = setInterval(checkSession, 60000); // Check every minute
        }
        
        function checkSession() {
            fetch('/auth/check_session.php', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.logged_in) {
                    clearInterval(sessionCheckInterval);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else if (data.show_warning) {
                    showSessionWarning(data.warning_message);
                }
            })
            .catch(error => {
                console.error('Session check failed:', error);
            });
        }
        
        function showSessionWarning(message) {
            showFlashMessage(message, 'warning');
        }
        
        // Start monitoring
        startSessionMonitoring();
        <?php endif; ?>
    </script>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($page_js)): ?>
        <script><?= $page_js ?></script>
    <?php endif; ?>
    
</body>
</html>