<?php
define('IS_API_REQUEST', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../DATABASE/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);

    if (!$data) {
        $data = $_POST;
    }

    $transaction_id = $data['id'] ?? $data['transaction_id'] ?? ('tx_' . time());
    $email = trim($data['email'] ?? '');
    $course_name = trim($data['courseName'] ?? $data['course_name'] ?? 'Online Course');
    $course_id = $data['courseId'] ?? $data['course_id'] ?? null;
    $amount = $data['amount'] ?? 'BDT 0.00';
    $payment_method = $data['paymentMethod'] ?? $data['payment_method'] ?? 'Online';
    $status = $data['status'] ?? 'Completed';

    if (empty($email) || empty($course_name)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Missing required transaction fields: email and course name."
        ]);
        exit();
    }

    try {
        $pdo = get_db_connection();

        // Optional: link user_id if email matches
        $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $userStmt->execute([':email' => $email]);
        $userId = $userStmt->fetchColumn() ?: null;

        $stmt = $pdo->prepare("INSERT INTO payments (transaction_id, user_id, email, course_name, course_id, amount, payment_method, status) 
                               VALUES (:txid, :uid, :email, :cname, :cid, :amount, :pmethod, :status)");

        $stmt->execute([
            ':txid' => $transaction_id,
            ':uid' => $userId,
            ':email' => $email,
            ':cname' => $course_name,
            ':cid' => $course_id,
            ':amount' => (string)$amount,
            ':pmethod' => $payment_method,
            ':status' => $status
        ]);

        // Auto-enroll user if user found and valid course_id
        if ($userId && $course_id && is_numeric($course_id)) {
            $enrollStmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, status) VALUES (:uid, :cid, 'ENROLLED') ON CONFLICT DO NOTHING");
            $enrollStmt->execute([':uid' => $userId, ':cid' => (int)$course_id]);
        }

        echo json_encode([
            'success' => true,
            'message' => "Transaction saved successfully in PostgreSQL"
        ]);
    } catch (Exception $e) {
        error_log("Save Transaction Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => "Database error saving transaction."
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => "Method Not Allowed"
    ]);
}
?>