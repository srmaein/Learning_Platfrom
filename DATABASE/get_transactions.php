<?php
// PostgreSQL transaction fetch endpoint
require_once __DIR__ . '/db_connection.php';
header('Content-Type: application/json');

try {
    $pdo = getPgPDO();
    $email_filter = isset($_GET['email']) ? trim($_GET['email']) : '';

    if (!empty($email_filter)) {
        $sql = "SELECT transaction_id, email, course_name, course_id, amount, payment_method, transaction_date, status FROM payments WHERE email ILIKE ? ORDER BY transaction_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%" . $email_filter . "%"]);
    } else {
        $sql = "SELECT transaction_id, email, course_name, course_id, amount, payment_method, transaction_date, status FROM payments ORDER BY transaction_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $transactions = [];
    foreach ($rows as $row) {
        $transactions[] = [
            'id' => $row['transaction_id'],
            'email' => $row['email'],
            'courseName' => $row['course_name'],
            'courseId' => $row['course_id'],
            'amount' => $row['amount'],
            'paymentMethod' => $row['payment_method'],
            'date' => $row['transaction_date'],
            'status' => $row['status']
        ];
    }

    echo json_encode([
        'success' => true,
        'transactions' => $transactions
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Database error: " . $e->getMessage()
    ]);
}
?>