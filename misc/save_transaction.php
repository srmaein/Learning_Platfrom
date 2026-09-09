<?php
// PostgreSQL transaction saving endpoint
require_once __DIR__ . '/../DATABASE/db_connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getPgPDO();
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data) {
        $data = $_POST;
    }

    $transaction_id = trim($data['transaction_id'] ?? $data['id'] ?? '');
    $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $course_name = trim($data['course_name'] ?? $data['courseName'] ?? '');
    $course_id = isset($data['course_id']) ? intval($data['course_id']) : (isset($data['courseId']) ? intval($data['courseId']) : null);
    $amount = trim($data['amount'] ?? '');
    $payment_method = trim($data['payment_method'] ?? $data['paymentMethod'] ?? '');
    $status = trim($data['status'] ?? 'COMPLETED');

    if (empty($transaction_id) || empty($email) || empty($course_name)) {
        echo json_encode(['success' => false, 'message' => 'Missing required transaction fields']);
        exit;
    }

    // Find user_id by email
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmtUser->execute([$email]);
    $userId = $stmtUser->fetchColumn() ?: null;

    // Save payment
    $sqlPayment = "INSERT INTO payments (transaction_id, user_id, email, course_name, course_id, amount, payment_method, status, transaction_date) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                   ON CONFLICT (transaction_id) DO UPDATE SET status = EXCLUDED.status";
    $stmtPayment = $pdo->prepare($sqlPayment);
    $stmtPayment->execute([$transaction_id, $userId, $email, $course_name, $course_id, $amount, $payment_method, $status]);

    // Automatically enroll user if user_id & course_id are present
    if ($userId && $course_id) {
        $stmtEnroll = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, status) VALUES (?, ?, 'ENROLLED') ON CONFLICT (user_id, course_id) DO UPDATE SET status = 'ENROLLED'");
        $stmtEnroll->execute([$userId, $course_id]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Transaction saved successfully',
        'transaction_id' => $transaction_id
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Error saving transaction: " . $e->getMessage()
    ]);
}
?>