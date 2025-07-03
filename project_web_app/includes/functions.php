<?php
/**
 * Helper Functions
 * Fungsi-fungsi bantuan untuk aplikasi
 */

// Include configuration
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

/**
 * Log user activity to database
 * @param int $user_id
 * @param string $action
 * @param string $table_affected
 * @param int $record_id
 * @param string $description
 * @return bool
 */
function logActivity($user_id, $action, $table_affected = null, $record_id = null, $description = null) {
    try {
        if (!getAppSetting('log_activities', true)) {
            return true; // Logging disabled
        }
        
        $sql = "INSERT INTO activity_logs (user_id, action, table_affected, record_id, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $user_id,
            $action,
            $table_affected,
            $record_id,
            $description,
            getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        return executeQuery($sql, $params) !== false;
        
    } catch (Exception $e) {
        if (function_exists('logMessage')) {
            logMessage("Log activity error: " . $e->getMessage(), 'ERROR');
        }
        return false;
    }
}

/**
 * Validate user login credentials
 * @param string $username
 * @param string $password
 * @return array|false
 */
function validateLogin($username, $password) {
    try {
        // Check by username or email - menggunakan is_active bukan status
        $sql = "SELECT id, username, email, password, full_name, role, is_active,
                       created_at, updated_at
                FROM users 
                WHERE (username = ? OR email = ?) AND is_active = 1";
        
        $user = getRecord($sql, [$username, $username]);
        
        if (!$user) {
            return false;
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        
        // Update last login (menggunakan kolom yang ada)
        updateRecord(
            "UPDATE users SET updated_at = NOW() WHERE id = ?",
            [$user['id']]
        );
        
        // Return user data (exclude password)
        unset($user['password']);
        return $user;
        
    } catch (Exception $e) {
        if (function_exists('logMessage')) {
            logMessage("Login validation error: " . $e->getMessage(), 'ERROR');
        }
        return false;
    }
}

/**
 * Create new user account
 * @param array $data
 * @return int|false
 */
function createUser($data) {
    try {
        // Validate required fields
        $required = ['username', 'email', 'password', 'full_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        // Check if username or email already exists
        $existing = getRecord(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$data['username'], $data['email']]
        );
        
        if ($existing) {
            return false;
        }
        
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert user - Include phone field
        $sql = "INSERT INTO users (username, email, password, full_name, phone, role, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $data['username'],
            $data['email'],
            $hashed_password,
            $data['full_name'],
            $data['phone'] ?? null,
            $data['role'] ?? 'user',
            $data['is_active'] ?? 1
        ];
        
        // Debug logging
        if (function_exists('logMessage')) {
            logMessage("Creating user with data: " . json_encode([
                'username' => $data['username'],
                'email' => $data['email'],
                'full_name' => $data['full_name'],
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'] ?? 'user'
            ]), 'INFO');
        }
        
        $user_id = insertRecord($sql, $params);
        
        if ($user_id) {
            logActivity($user_id, 'CREATE', 'users', $user_id, 'User account created');
        }
        
        return $user_id;
        
    } catch (Exception $e) {
        if (function_exists('logMessage')) {
            logMessage("Create user error: " . $e->getMessage(), 'ERROR');
        }
        return false;
    }
}

/**
 * Get user by ID
 * @param int $user_id
 * @return array|false
 */
function getUserById($user_id) {
    try {
        $sql = "SELECT id, username, email, full_name, phone, role, is_active, created_at, updated_at 
                FROM users WHERE id = ?";
        return getRecord($sql, [$user_id]);
        
    } catch (Exception $e) {
        if (function_exists('logMessage')) {
            logMessage("Get user error: " . $e->getMessage(), 'ERROR');
        }
        return false;
    }
}

/**
 * Update user data
 * @param int $user_id
 * @param array $data
 * @return bool
 */
function updateUser($user_id, $data) {
    try {
        // Build update query dynamically - Include phone field
        $allowed_fields = ['username', 'email', 'full_name', 'phone', 'role', 'is_active'];
        $set_clauses = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $set_clauses[] = "{$field} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($set_clauses)) {
            return false;
        }
        
        // Handle password update separately
        if (!empty($data['password'])) {
            $set_clauses[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Always update updated_at
        $set_clauses[] = "updated_at = NOW()";
        $params[] = $user_id;
        
        $sql = "UPDATE users SET " . implode(', ', $set_clauses) . " WHERE id = ?";
        
        // Debug logging
        if (function_exists('logMessage')) {
            logMessage("Updating user ID $user_id with data: " . json_encode($data), 'INFO');
            logMessage("Update SQL: $sql", 'INFO');
        }
        
        $affected = updateRecord($sql, $params);
        
        if ($affected > 0) {
            logActivity(getCurrentUserId(), 'UPDATE', 'users', $user_id, 'User data updated');
        }
        
        return $affected > 0;
        
    } catch (Exception $e) {
        if (function_exists('logMessage')) {
            logMessage("Update user error: " . $e->getMessage(), 'ERROR');
        }
        return false;
    }
}

/**
 * Delete user (soft delete by default)
 * @param int $user_id
 * @param bool $soft_delete
 * @return bool
 */
function deleteUser($user_id, $soft_delete = true) {
    try {
        // Validate user_id
        if (!$user_id || !is_numeric($user_id)) {
            if (function_exists('logMessage')) {
                logMessage("Delete user error: Invalid user ID: $user_id", 'ERROR');
            }
            return false;
        }
        
        // Check if user exists first
        $user = getUserById($user_id);
        if (!$user) {
            if (function_exists('logMessage')) {
                logMessage("Delete user error: User not found with ID: $user_id", 'ERROR');
            }
            return false;
        }
        
        if ($soft_delete) {
            // Soft delete - just deactivate
            $sql = "UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?";
            $affected = updateRecord($sql, [$user_id]);
        } else {
            // Hard delete - permanently remove
            $sql = "DELETE FROM users WHERE id = ?";
            $affected = updateRecord($sql, [$user_id]);
        }
        
        if (function_exists('logMessage')) {
            logMessage("Delete user query executed: $sql with params: " . json_encode([$user_id]) . " affected: $affected", 'INFO');
        }
        
        if ($affected > 0) {
            $action = $soft_delete ? 'DEACTIVATE' : 'DELETE';
            logActivity(getCurrentUserId(), $action, 'users', $user_id, 'User account ' . strtolower($action) . 'd');
            return true;
        }
        
        if (function_exists('logMessage')) {
            logMessage("Delete user: No rows affected for user_id: $user_id", 'WARNING');
        }
        
        return false;
        
    } catch (Exception $e) {
        if (function_exists('logMessage')) {
            logMessage("Delete user error: " . $e->getMessage(), 'ERROR');
        }
        return false;
    }
}

/**
 * Format date only (without time)
 * @param string $date
 * @param string $format Custom format (optional)
 * @return string
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 * @param string $datetime
 * @param string $format Custom format (optional)
 * @return string
 */
function formatDateTime($datetime, $format = DISPLAY_DATETIME_FORMAT) {
    if (empty($datetime) || $datetime === '0000-00-00' || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

/**
 * Time ago helper
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

/**
 * Generate pagination HTML
 * @param int $current_page
 * @param int $total_pages
 * @param array $get_params
 * @return string
 */
function generatePagination($current_page, $total_pages, $get_params = []) {
    if ($total_pages <= 1) return '';
    
    $html = '';
    $range = 2; // Number of pages to show around current page
    
    // Ensure $get_params is an array
    if (!is_array($get_params)) {
        $get_params = [];
    }
    
    // Remove page parameter from GET params
    if (isset($get_params['page'])) {
        unset($get_params['page']);
    }
    $query_string = !empty($get_params) ? '&' . http_build_query($get_params) : '';
    
    // Start pagination wrapper
    $html .= '<nav aria-label="Page navigation" class="pagination-wrapper">';
    $html .= '<ul class="pagination pagination-modern justify-content-center mb-0">';
    
    // Previous button
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $html .= '<li class="page-item">
                    <a class="page-link" href="?page=' . $prev_page . $query_string . '" aria-label="Previous">
                        <i class="fas fa-chevron-left"></i>
                        <span class="d-none d-sm-inline ms-1">Previous</span>
                    </a>
                  </li>';
    } else {
        $html .= '<li class="page-item disabled">
                    <span class="page-link" aria-label="Previous">
                        <i class="fas fa-chevron-left"></i>
                        <span class="d-none d-sm-inline ms-1">Previous</span>
                    </span>
                  </li>';
    }
    
    // Page numbers
    $start = max(1, $current_page - $range);
    $end = min($total_pages, $current_page + $range);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=1' . $query_string . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        if ($i == $current_page) {
            $html .= '<li class="page-item active" aria-current="page">
                        <span class="page-link">' . $i . '</span>
                      </li>';
        } else {
            $html .= '<li class="page-item">
                        <a class="page-link" href="?page=' . $i . $query_string . '">' . $i . '</a>
                      </li>';
        }
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . $query_string . '">' . $total_pages . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $html .= '<li class="page-item">
                    <a class="page-link" href="?page=' . $next_page . $query_string . '" aria-label="Next">
                        <span class="d-none d-sm-inline me-1">Next</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                  </li>';
    } else {
        $html .= '<li class="page-item disabled">
                    <span class="page-link" aria-label="Next">
                        <span class="d-none d-sm-inline me-1">Next</span>
                        <i class="fas fa-chevron-right"></i>
                    </span>
                  </li>';
    }
    
    // End pagination wrapper
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Get last insert ID
 * @return int
 */
function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

/**
 * Generate order number
 * @return string
 */
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Get system statistics
 * @return array
 */
function getSystemStats() {
    try {
        $stats = [];
        
        // User statistics
        $stats['total_users'] = getRecord("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
        $stats['active_users'] = getRecord("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'] ?? 0;
        
        // Product statistics
        $stats['total_products'] = getRecord("SELECT COUNT(*) as count FROM products")['count'] ?? 0;
        $stats['active_products'] = getRecord("SELECT COUNT(*) as count FROM products WHERE is_active = 1")['count'] ?? 0;
        $stats['low_stock_products'] = getRecord("SELECT COUNT(*) as count FROM products WHERE stock <= min_stock AND stock > 0")['count'] ?? 0;
        $stats['out_of_stock_products'] = getRecord("SELECT COUNT(*) as count FROM products WHERE stock = 0")['count'] ?? 0;
        
        // Order statistics
        $stats['total_orders'] = getRecord("SELECT COUNT(*) as count FROM orders")['count'] ?? 0;
        $stats['pending_orders'] = getRecord("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'] ?? 0;
        $stats['completed_orders'] = getRecord("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")['count'] ?? 0;
        
        // Revenue statistics
        $revenue_data = getRecord("
            SELECT 
                COALESCE(SUM(od.quantity * od.price), 0) as total_revenue,
                COALESCE(AVG(od.quantity * od.price), 0) as avg_order_value
            FROM orders o 
            LEFT JOIN order_details od ON o.id = od.order_id 
            WHERE o.status IN ('completed', 'delivered')
        ");
        
        $stats['total_revenue'] = $revenue_data['total_revenue'] ?? 0;
        $stats['avg_order_value'] = $revenue_data['avg_order_value'] ?? 0;
        
        // Customer statistics
        $stats['total_customers'] = getRecord("SELECT COUNT(*) as count FROM customers")['count'] ?? 0;
        $stats['active_customers'] = getRecord("SELECT COUNT(*) as count FROM customers WHERE is_active = 1")['count'] ?? 0;
        
        return $stats;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get recent activities
 * @param int $limit
 * @return array
 */
function getRecentActivities($limit = 10) {
    try {
        return executeQuery("
            SELECT al.*, u.username 
            FROM activity_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            ORDER BY al.created_at DESC 
            LIMIT ?
        ", [$limit]) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Clean old activity logs
 * @param int $days_to_keep
 * @return bool
 */
function cleanOldLogs($days_to_keep = 30) {
    try {
        $sql = "DELETE FROM activity_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $affected = updateRecord($sql, [$days_to_keep]);
        
        if (function_exists('logMessage')) {
            logMessage("Cleaned {$affected} old log records", 'INFO');
        }
        return true;
        
    } catch (Exception $e) {
        if (function_exists('logMessage')) {
            logMessage("Clean old logs error: " . $e->getMessage(), 'ERROR');
        }
        return false;
    }
}

/**
 * Upload file helper
 * @param array $file $_FILES array element
 * @param string $upload_dir Upload directory
 * @param array $allowed_types Allowed file extensions
 * @return string|false Filename on success, false on failure
 */
function uploadFile($file, $upload_dir, $allowed_types = []) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return false;
    }
    
    // Get file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check allowed types
    if (!empty($allowed_types) && !in_array($file_extension, $allowed_types)) {
        return false;
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_path = $upload_dir . '/' . $filename;
    
    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $filename;
    }
    
    return false;
}

/**
 * Delete uploaded file
 * @param string $filename
 * @param string $upload_dir
 * @return bool
 */
function deleteUploadedFile($filename, $upload_dir) {
    if (empty($filename)) return true;
    
    $file_path = $upload_dir . '/' . $filename;
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    
    return true;
}

/**
 * Format currency value
 * @param float $amount
 * @param string $currency
 * @return string
 */
function formatCurrency($amount, $currency = 'IDR') {
    if ($currency === 'IDR') {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
    return '$' . number_format($amount, 2, '.', ',');
}

?>