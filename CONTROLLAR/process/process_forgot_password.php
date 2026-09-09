<?php
// Database connection
require_once __DIR__ . '/../../DATABASE/db_connection.php';
$pdo = getPgPDO();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        die(json_encode([
            'status' => 'error',
            'message' => 'Invalid email format'
        ]));
    }

    try {
        // Query unified users table
        $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE email = ? AND status = 'ACTIVE'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['reset_email'] = $user['email'];
            $_SESSION['user_type'] = $user['role'];
            $_SESSION['user_id'] = $user['id'];
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Email verified successfully'
            ]);
            exit();
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Email not found in our records'
            ]);
            exit();
        }
    } catch(PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
        exit();
    }
} else {
    header("Location: ../../VIEWS/auth/forget-password.html");
    exit();
}
?>