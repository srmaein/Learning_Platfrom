<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Not logged in'
    ]);
    exit();
}

// Return user information
echo json_encode([
    'status' => 'success',
    'data' => [
        'username' => $_SESSION['username'],
        'name' => $_SESSION['name'],
        'user_type' => $_SESSION['user_type']
    ]
]);
?> 