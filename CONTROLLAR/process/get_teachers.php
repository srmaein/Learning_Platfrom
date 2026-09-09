<?php
require_once '../../MODELS/database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Modified query to match the new table structure
    $query = "SELECT 
                id,
                teacher_name,
                age,
                date_of_birth,
                blood_group,
                phone_number,
                address,
                email,
                qualifications,
                user_id,
                username,
                created_at,
                updated_at
              FROM teachers 
              ORDER BY id DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($teachers === false) {
        echo json_encode(['error' => 'No teachers found']);
    } else {
        // Debug output
        error_log("Teachers data: " . print_r($teachers, true));
        echo json_encode($teachers);
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'details' => 'Database connection or query failed'
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'details' => 'Database error occurred'
    ]);
}
?> 