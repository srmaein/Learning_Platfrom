<?php
// Prevent any output before JSON response
ob_start();

// Start session and include database
session_start();
include_once __DIR__ . '/../../../MODELS/db.php';

// Set JSON header
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($success, $message) {
    ob_clean(); // Clear any previous output
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Check session
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Unauthorized access');
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get and sanitize input data
    $admin_name = filter_input(INPUT_POST, 'admin_name', FILTER_SANITIZE_STRING);
    $age = filter_input(INPUT_POST, 'age', FILTER_SANITIZE_NUMBER_INT);
    $date_of_birth = filter_input(INPUT_POST, 'date_of_birth', FILTER_SANITIZE_STRING);
    $blood_group = filter_input(INPUT_POST, 'blood_group', FILTER_SANITIZE_STRING);
    $phone_number = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password']; // Don't sanitize password

    // Validate required fields
    if (!$admin_name || !$age || !$date_of_birth || !$blood_group || !$phone_number || !$address || !$email || !$username || !$password) {
        throw new Exception('All fields are required');
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate age
    if ($age < 18 || $age > 100) {
        throw new Exception('Age must be between 18 and 100');
    }

    // Check if username or email already exists
    $check_query = "SELECT id FROM admin_registration WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_query);
    if (!$check_stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $check_stmt->bind_param("ss", $username, $email);
    if (!$check_stmt->execute()) {
        throw new Exception('Database execute error: ' . $check_stmt->error);
    }

    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        throw new Exception('Username or email already exists');
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new admin
    $query = "INSERT INTO admin_registration (
                admin_name, 
                age, 
                date_of_birth, 
                blood_group, 
                phone_number, 
                address, 
                email, 
                username, 
                password
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->bind_param("sisssssss", 
        $admin_name, 
        $age, 
        $date_of_birth, 
        $blood_group, 
        $phone_number, 
        $address, 
        $email, 
        $username, 
        $hashed_password
    );

    if (!$stmt->execute()) {
        throw new Exception('Database execute error: ' . $stmt->error);
    }

    sendJsonResponse(true, 'Admin registered successfully');

} catch (Exception $e) {
    error_log('Admin Registration Error: ' . $e->getMessage());
    sendJsonResponse(false, 'Error: ' . $e->getMessage());
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($check_stmt)) {
        $check_stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
    ob_end_flush();
}
?> 