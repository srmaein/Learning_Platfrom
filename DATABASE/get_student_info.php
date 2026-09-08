<?php
session_start();

// Set appropriate headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in and is a student
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student' && isset($_SESSION['name'])) {
    // Return student information
    echo json_encode([
        'status' => 'success',
        'user_type' => $_SESSION['user_type'],
        'name' => $_SESSION['name'],
        'username' => $_SESSION['username'],
        'loggedIn' => true
    ]);
} else {
    // User not logged in or not a student
    echo json_encode([
        'status' => 'error',
        'message' => 'Not logged in as student',
        'loggedIn' => false
    ]);
}
?> 