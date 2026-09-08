<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'Online_Education';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['newPassword'])) {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $newPassword = $_POST['newPassword'];
    
    if (!$email) {
        die("Invalid email format");
    }

    // Validate password
    if (strlen($newPassword) < 8) {
        die("Password must be at least 8 characters long");
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    try {
        $updated = false;
        
        // Get user type from session
        $userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
        
        switch($userType) {
            case 'admin':
                $stmt = $pdo->prepare("UPDATE admin_registration SET password = ? WHERE email = ?");
                $stmt->execute([$hashedPassword, $email]);
                $updated = $stmt->rowCount() > 0;
                break;
                
            case 'student':
                $stmt = $pdo->prepare("UPDATE student_registration SET password = ? WHERE email = ?");
                $stmt->execute([$hashedPassword, $email]);
                $updated = $stmt->rowCount() > 0;
                break;
                
            case 'teacher':
                $stmt = $pdo->prepare("UPDATE teachers SET password = ? WHERE email = ?");
                $stmt->execute([$hashedPassword, $email]);
                $updated = $stmt->rowCount() > 0;
                break;
                
            default:
                die("Invalid user type");
        }

        if ($updated) {
            // Clear session
            session_destroy();
            
            // Redirect to login page with success message
            header("Location: ../../login.php?reset=success");
            exit();
        } else {
            die("Password reset failed: Email not found");
        }
    } catch(PDOException $e) {
        die("Password reset failed: " . $e->getMessage());
    }
} else {
    header("Location: ../../VIEWS/auth/forget-password.html");
    exit();
}
?> 