<?php
require_once '../../MODELS/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['search'])) {
        throw new Exception('Search term is required');
    }

    $search = trim($_POST['search']);
    
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    if (empty($search)) {
        // If search is empty, return all teachers
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
        
        // Debug output
        error_log("All teachers data: " . print_r($teachers, true));
        echo json_encode($teachers);
        exit;
    }

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
              WHERE teacher_name LIKE :search 
              OR email LIKE :search 
              OR id LIKE :search 
              OR phone_number LIKE :search 
              OR username LIKE :search
              OR user_id LIKE :search
              ORDER BY id DESC";
              
    $stmt = $conn->prepare($query);
    $searchTerm = "%{$search}%";
    $stmt->bindParam(':search', $searchTerm);
    $stmt->execute();
    
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($teachers === false) {
        echo json_encode(['error' => 'No teachers found']);
    } else {
        // Debug output
        error_log("Search results: " . print_r($teachers, true));
        echo json_encode($teachers);
    }
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'details' => 'Invalid request or database connection failed'
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'details' => 'Database error occurred'
    ]);
}
?> 