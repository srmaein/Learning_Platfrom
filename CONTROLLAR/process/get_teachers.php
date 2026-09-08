<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../DATABASE/db_connection.php';

try {
    $pdo = get_db_connection();

    $query = "SELECT u.id, u.email, u.username, u.created_at,
                     p.full_name as teacher_name, p.age, p.date_of_birth, p.blood_group, p.phone_number,
                     p.address, p.qualifications, p.teacher_user_id as user_id
              FROM users u
              LEFT JOIN profiles p ON u.id = p.user_id
              WHERE u.role = 'teacher'
              ORDER BY u.id DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($teachers ?: []);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'details' => 'Database query failed'
    ]);
}
?>