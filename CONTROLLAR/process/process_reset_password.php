<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once __DIR__ . '/../../DATABASE/db_connection.php';
$pdo = getPgPDO();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['newPassword'])) {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $newPassword = $_POST['newPassword'];
    
    if (!$email) {
        die("Invalid email format");
    }

    if (strlen($newPassword) < 6) {
        die("Password must be at least 6 characters long");
    }

    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE email = ?");
        $stmt->execute([$passwordHash, $email]);
        $updated = $stmt->rowCount() > 0;

        if ($updated) {
            session_destroy();
            header("Location: ../../login.php?reset=success");
            exit();
        } else {
            die("Password reset failed: Email not found in users table.");
        }
    } catch(PDOException $e) {
        die("Password reset failed: " . $e->getMessage());
    }
} else {
    header("Location: ../../VIEWS/auth/forget-password.html");
    exit();
}
?>