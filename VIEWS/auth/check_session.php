<?php
if (session_status() === PHP_SESSION_NONE) {
    // Set secure cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/../../config.php';

/**
 * Enforces session authentication and role-based authorization
 * 
 * @param array|string $allowedRoles Array of allowed roles e.g. ['admin', 'teacher'] or string 'admin'
 */
function require_auth($allowedRoles = []) {
    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    $allowedRoles = array_map('strtolower', $allowedRoles);

    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
              (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

    // Check if session exists
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        if ($isAjax) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Session expired or unauthenticated.',
                'redirect' => BASE_URL . 'login.php'
            ]);
            exit();
        } else {
            header("Location: " . BASE_URL . "login.php");
            exit();
        }
    }

    // Session inactivity timeout (2 hours)
    $timeout = 7200;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        if ($isAjax) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Session timed out due to inactivity.',
                'redirect' => BASE_URL . 'login.php'
            ]);
            exit();
        } else {
            header("Location: " . BASE_URL . "login.php?session=expired");
            exit();
        }
    }
    $_SESSION['last_activity'] = time();

    // Verify role authorization if restricted
    $currentUserRole = strtolower($_SESSION['user_type'] ?? $_SESSION['role'] ?? '');
    if (!empty($allowedRoles) && !in_array($currentUserRole, $allowedRoles)) {
        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Access forbidden: Insufficient permissions.'
            ]);
            exit();
        } else {
            http_response_code(403);
            echo "<!DOCTYPE html><html><head><title>Access Forbidden</title><style>body{font-family:sans-serif;text-align:center;padding:50px;background:#f8f9fa;}h1{color:#e74c3c;}.btn{display:inline-block;padding:10px 20px;background:#3498db;color:#fff;text-decoration:none;border-radius:5px;margin-top:20px;}</style></head><body><h1>403 Forbidden</h1><p>You do not have administrative permission to access this resource.</p><a href='" . BASE_URL . "login.php' class='btn'>Return to Safety</a></body></html>";
            exit();
        }
    }

    return true;
}

// Function to handle AJAX session check ping
function checkSessionStatus() {
    if (isset($_GET['check_session'])) {
        header('Content-Type: application/json');
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
            echo json_encode([
                'valid' => true,
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? '',
                'name' => $_SESSION['name'] ?? '',
                'user_type' => $_SESSION['user_type']
            ]);
        } else {
            echo json_encode(['valid' => false]);
        }
        exit();
    }
}

checkSessionStatus();
?>