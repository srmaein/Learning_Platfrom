<?php
require_once '../../MODELS/database.php';

header('Content-Type: application/json');

if (!isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Teacher ID is required']);
    exit;
}

$id = $_POST['id'];

try {
    $db = new Database();
    $conn = $db->getConnection();

    $query = "DELETE FROM teachers WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Teacher deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete teacher']);
    }
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?> 