<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../DATABASE/db_connection.php';
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please log in to complete course enrollment.'
    ]);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action !== 'enroll') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid action specified.'
    ]);
    exit();
}

try {
    $pdo = getPgPDO();
    $userId = $_SESSION['user_id'];
    $email = $_SESSION['email'] ?? 'student@platform.com';
    $courseId = intval($_POST['course_id'] ?? 0);
    $paymentMethod = trim($_POST['payment_method'] ?? 'COD');
    $trxId = trim($_POST['transaction_id'] ?? '');

    if ($courseId <= 0) {
        throw new Exception("Invalid course selected for enrollment.");
    }

    // Fetch course details
    $cStmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $cStmt->execute([$courseId]);
    $course = $cStmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        throw new Exception("Course not found.");
    }

    // Validate bKash transaction id
    if ($paymentMethod === 'bKash') {
        if (empty($trxId)) {
            throw new Exception("Please provide a valid bKash Transaction ID (TrxID).");
        }
        $finalTrxId = 'BKASH-' . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $trxId));
        $methodLabel = 'bKash';
    } else {
        $finalTrxId = 'COD-' . strtoupper(bin2hex(random_bytes(4)));
        $methodLabel = 'Cash on Delivery (COD)';
    }

    // 1. Create or Update Enrollment Record
    try {
        $insEnroll = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, status, progress_percent) VALUES (?, ?, 'ENROLLED', 0)");
        $insEnroll->execute([$userId, $courseId]);
    } catch (Exception $exEnroll) {
        // If duplicate entry exists, ensure status is ENROLLED
        $updEnroll = $pdo->prepare("UPDATE enrollments SET status = 'ENROLLED' WHERE user_id = ? AND course_id = ?");
        $updEnroll->execute([$userId, $courseId]);
    }

    // 2. Create Payment Record
    $formattedAmount = "BDT " . number_format((float)$course['price'], 2);
    try {
        $insPay = $pdo->prepare("
            INSERT INTO payments (transaction_id, user_id, email, course_name, course_id, amount, currency, payment_method, status)
            VALUES (?, ?, ?, ?, ?, ?, 'BDT', ?, 'COMPLETED')
        ");
        $insPay->execute([
            $finalTrxId,
            $userId,
            $email,
            $course['title'],
            $courseId,
            $formattedAmount,
            $methodLabel
        ]);
    } catch (Exception $exPay) {
        error_log("Payment Insert Notice: " . $exPay->getMessage());
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Enrollment completed successfully! Course added to your dashboard.',
        'data' => [
            'course_id' => $courseId,
            'title' => $course['title'],
            'price' => $course['price'],
            'payment_method' => $methodLabel,
            'transaction_id' => $finalTrxId
        ]
    ]);
    exit();

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit();
}
?>
