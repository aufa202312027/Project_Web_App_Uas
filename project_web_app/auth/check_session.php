<?php
/**
 * Session Check
 * AJAX endpoint untuk check session status dan extend session
 */

define('APP_ACCESS', true);
require_once '../config/config.php';
require_once '../includes/functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Start session
startSecureSession();

$response = [];

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        $response = [
            'status' => 'error',
            'message' => 'Session expired',
            'logged_in' => false,
            'redirect' => '/auth/login.php'
        ];
    } else {
        // Get session info
        $session_info = getSessionInfo();
        $time_remaining = $session_info['time_remaining'];
        
        // Check if session is about to expire (less than 5 minutes)
        $warning_threshold = 5 * 60; // 5 minutes
        $show_warning = $time_remaining <= $warning_threshold && $time_remaining > 0;
        
        // Handle extend session request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (isset($input['action']) && $input['action'] === 'extend') {
                // Verify session token for security
                $token = $input['token'] ?? '';
                if (verifySessionToken($token)) {
                    extendSession();
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'Session extended',
                        'logged_in' => true,
                        'extended' => true,
                        'time_remaining' => SESSION_TIMEOUT
                    ];
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'Invalid token',
                        'logged_in' => false,
                        'redirect' => '/auth/login.php'
                    ];
                }
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Invalid action',
                    'logged_in' => true
                ];
            }
        } else {
            // Regular session check (GET request)
            $response = [
                'status' => 'success',
                'logged_in' => true,
                'user_id' => $session_info['user_id'],
                'username' => $session_info['username'],
                'role' => $session_info['role'],
                'time_remaining' => $time_remaining,
                'show_warning' => $show_warning,
                'session_token' => generateSessionToken()
            ];
            
            // Add warning message if session is about to expire
            if ($show_warning) {
                $minutes_left = ceil($time_remaining / 60);
                $response['warning_message'] = "Your session will expire in {$minutes_left} minute(s). Click 'Extend' to continue.";
            }
        }
    }
} catch (Exception $e) {
    // Log error
    logMessage("Session check error: " . $e->getMessage(), 'ERROR');
    
    $response = [
        'status' => 'error',
        'message' => 'Session check failed',
        'logged_in' => false,
        'redirect' => '/auth/login.php'
    ];
}

// Output JSON response
echo json_encode($response);
exit;
?>