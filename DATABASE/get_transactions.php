<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db_connection.php';

try {
    $pdo = get_db_connection();

    $emailFilter = $_GET['email'] ?? null;

    if ($emailFilter) {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE email = :email ORDER BY created_at DESC");
        $stmt->execute([':email' => $emailFilter]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM payments ORDER BY created_at DESC");
        $stmt->execute();
    }

    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $transactions
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>