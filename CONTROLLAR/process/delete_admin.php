<?php
session_start();
include_once __DIR__ . '/../../../MODELS/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get and sanitize input data
$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid admin ID']);
    exit();
}

try {
    // Check if admin exists
    $check_query = "SELECT id FROM admin_registration WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Admin not found']);
        exit();
    }

    // Delete admin
    $query = "DELETE FROM admin_registration WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Admin deleted successfully']);
    } else {
        throw new Exception("Failed to delete admin");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 