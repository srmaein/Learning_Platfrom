<?php
session_start();

// Function to check if session is valid
function checkSession() {
    // Check if session exists and has required data
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || !isset($_SESSION['username'])) {
        // Clear any existing session data
        session_unset();
        session_destroy();
        
        // Set response headers
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Session expired or invalid',
            'redirect' => '../../login.php'
        ]);
        exit();
    }
    
    // Check if session cookie exists
    if (!isset($_COOKIE[session_name()])) {
        // Clear any existing session data
        session_unset();
        session_destroy();
        
        // Set response headers
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Session cookie not found',
            'redirect' => '../../login.php'
        ]);
        exit();
    }
    
    return true;
}

// Function to handle AJAX session checks
function handleAjaxSessionCheck() {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        checkSession();
        echo json_encode(['status' => 'success']);
        exit();
    }
}

// If this file is included directly, perform the check
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    checkSession();
}

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type'])) {
    header("Location: ../../login.php");
    exit();
}

// Check if user is accessing the correct dashboard
$current_page = basename($_SERVER['PHP_SELF']);
$user_type = $_SESSION['user_type'];

switch ($current_page) {
    case 'dashboard.html':
        if ($user_type !== 'admin') {
            header("Location: ../../login.php");
            exit();
        }
        break;
    case 'index.html':
        if ($user_type !== 'student') {
            header("Location: ../../login.php");
            exit();
        }
        break;
    case 'teadas.html':
        if ($user_type !== 'teacher') {
            header("Location: ../../login.php");
            exit();
        }
        break;
}

// Return user information for AJAX requests
if (isset($_GET['get_info'])) {
    echo json_encode([
        'status' => 'success',
        'data' => [
            'username' => $_SESSION['username'],
            'name' => $_SESSION['name'],
            'user_type' => $_SESSION['user_type']
        ]
    ]);
    exit();
}
?> 