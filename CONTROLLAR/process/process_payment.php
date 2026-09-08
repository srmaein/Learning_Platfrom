<?php
define('IS_API_REQUEST', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../DATABASE/db_connection.php';
require_once __DIR__ . '/../../VIEWS/auth/check_session.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = get_db_connection();

    switch ($action) {
        // Step 1: Initiate Payment Checkout (Fetches authoritative price from PostgreSQL)
        case 'initiate':
            require_auth(['student', 'admin']);

            $userId = $_SESSION['user_id'];
            $courseId = (int)($_POST['course_id'] ?? $_GET['course_id'] ?? 0);
            $paymentMethod = trim($_POST['payment_method'] ?? 'bKash');

            if (!$courseId) {
                throw new Exception("Invalid course specified.");
            }

            // CRITICAL: Retrieve true course details and price from PostgreSQL. NEVER trust browser price!
            $courseStmt = $pdo->prepare("SELECT id, title, price, is_published FROM courses WHERE id = :cid");
            $courseStmt->execute([':cid' => $courseId]);
            $course = $courseStmt->fetch(PDO::FETCH_ASSOC);

            if (!$course || !($course['is_published'] === true || $course['is_published'] === 't' || $course['is_published'] === 1)) {
                throw new Exception("Course is unavailable for enrollment.");
            }

            // Check if user is already enrolled
            $enrolledCheck = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = :uid AND course_id = :cid");
            $enrolledCheck->execute([':uid' => $userId, ':cid' => $courseId]);
            if ($enrolledCheck->fetch()) {
                echo json_encode([
                    'status' => 'success',
                    'already_enrolled' => true,
                    'message' => 'You are already enrolled in this course.'
                ]);
                exit();
            }

            $authoritativePrice = (float)$course['price'];
            $formattedAmount = "BDT " . number_format($authoritativePrice, 2, '.', '');
            $transactionId = 'TXN' . strtoupper(bin2hex(random_bytes(6)));

            // Save pending payment record in PostgreSQL
            $paymentSql = "INSERT INTO payments (transaction_id, user_id, email, course_name, course_id, amount, currency, payment_method, status)
                           VALUES (:txid, :uid, :email, :cname, :cid, :amount, 'BDT', :pmethod, 'PENDING')
                           RETURNING id";
            $paymentStmt = $pdo->prepare($paymentSql);
            $paymentStmt->execute([
                ':txid' => $transactionId,
                ':uid' => $userId,
                ':email' => $_SESSION['email'],
                ':cname' => $course['title'],
                ':cid' => $courseId,
                ':amount' => $formattedAmount,
                ':pmethod' => $paymentMethod
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Payment session initiated.',
                'data' => [
                    'transaction_id' => $transactionId,
                    'course_id' => $courseId,
                    'course_name' => $course['title'],
                    'authoritative_price' => $authoritativePrice,
                    'formatted_amount' => $formattedAmount,
                    'payment_method' => $paymentMethod,
                    'email' => $_SESSION['email']
                ]
            ]);
            break;

        // Step 2: Confirm / Process Gateway Callback
        case 'confirm':
            require_auth(['student', 'admin']);

            $userId = $_SESSION['user_id'];
            $transactionId = trim($_POST['transaction_id'] ?? '');
            $providedTxId = trim($_POST['user_provided_txid'] ?? '');
            $paymentMethod = trim($_POST['payment_method'] ?? 'bKash');

            if (empty($transactionId) && !empty($providedTxId)) {
                $transactionId = $providedTxId;
            }

            if (empty($transactionId)) {
                throw new Exception("Transaction ID is required for verification.");
            }

            // Find payment record
            $stmt = $pdo->prepare("SELECT p.*, c.price as db_course_price 
                                   FROM payments p
                                   LEFT JOIN courses c ON p.course_id = c.id
                                   WHERE p.transaction_id = :txid OR p.gateway_reference = :txid");
            $stmt->execute([':txid' => $transactionId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            // If not found by auto-generated txid, search by matching pending user payment
            if (!$payment && !empty($providedTxId)) {
                $pStmt = $pdo->prepare("SELECT p.*, c.price as db_course_price 
                                        FROM payments p
                                        LEFT JOIN courses c ON p.course_id = c.id
                                        WHERE p.user_id = :uid AND p.status = 'PENDING'
                                        ORDER BY p.id DESC LIMIT 1");
                $pStmt->execute([':uid' => $userId]);
                $payment = $pStmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$payment) {
                throw new Exception("Payment record not found.");
            }

            // Idempotency Check: If payment already completed, prevent duplicate processing
            if ($payment['status'] === 'COMPLETED') {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Payment already completed and verified.',
                    'transaction_id' => $payment['transaction_id']
                ]);
                exit();
            }

            // Verify backend price matches payment record
            $dbPrice = (float)$payment['db_course_price'];
            $paidAmountNumeric = (float)preg_replace('/[^0-9.]/', '', $payment['amount']);

            if (abs($paidAmountNumeric - $dbPrice) > 0.01) {
                // Price tampering detected!
                $failStmt = $pdo->prepare("UPDATE payments SET status = 'FAILED' WHERE id = :id");
                $failStmt->execute([':id' => $payment['id']]);
                throw new Exception("Payment verification failed: Amount paid does not match PostgreSQL course price.");
            }

            // Execute atomic transaction: Update payment status to COMPLETED & create enrollment
            $pdo->beginTransaction();

            $updatePayment = $pdo->prepare("UPDATE payments 
                                           SET status = 'COMPLETED', gateway_reference = :gref, paid_at = CURRENT_TIMESTAMP 
                                           WHERE id = :id");
            $updatePayment->execute([
                ':gref' => $providedTxId ?: $transactionId,
                ':id' => $payment['id']
            ]);

            // Create enrollment record
            if ($payment['course_id']) {
                $enrollSql = "INSERT INTO enrollments (user_id, course_id, status)
                              VALUES (:uid, :cid, 'ENROLLED')
                              ON CONFLICT (user_id, course_id) DO UPDATE SET status = 'ENROLLED'";
                $enrollStmt = $pdo->prepare($enrollSql);
                $enrollStmt->execute([
                    ':uid' => $userId,
                    ':cid' => $payment['course_id']
                ]);
            }

            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'Payment verified successfully! Course enrollment granted.',
                'transaction_id' => $payment['transaction_id']
            ]);
            break;

        // Step 3: Handle Cancelled Payment
        case 'cancel':
            require_auth(['student', 'admin']);
            $transactionId = trim($_POST['transaction_id'] ?? '');

            if ($transactionId) {
                $stmt = $pdo->prepare("UPDATE payments SET status = 'CANCELLED' WHERE transaction_id = :txid AND status = 'PENDING'");
                $stmt->execute([':txid' => $transactionId]);
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Payment transaction cancelled.'
            ]);
            break;

        default:
            throw new Exception("Invalid payment action specified.");
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit();
}
?>
