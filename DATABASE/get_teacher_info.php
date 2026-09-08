<?php
session_start();

// Set appropriate headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in and is a teacher
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher' && isset($_SESSION['name'])) {
    // Return teacher information
    echo json_encode([
        'status' => 'success',
        'user_type' => $_SESSION['user_type'],
        'name' => $_SESSION['name'],
        'username' => $_SESSION['username'],
        'loggedIn' => true
    ]);
} else {
    // User not logged in or not a teacher
    echo json_encode([
        'status' => 'error',
        'message' => 'Not logged in as teacher',
        'loggedIn' => false
    ]);
}
?> 