<?php
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        die(json_encode([
            'status' => 'error',
            'message' => 'Invalid email format'
        ]));
    }

    try {
        // Check in admin_registration table
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_registration WHERE email = ?");
        $stmt->execute([$email]);
        $adminExists = $stmt->fetchColumn() > 0;

        // Check in student_registration table
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_registration WHERE email = ?");
        $stmt->execute([$email]);
        $studentExists = $stmt->fetchColumn() > 0;

        // Check in teachers table (changed from teacher_registration)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE email = ?");
        $stmt->execute([$email]);
        $teacherExists = $stmt->fetchColumn() > 0;

        if ($adminExists || $studentExists || $teacherExists) {
            // Email exists in at least one table
            session_start();
            $_SESSION['reset_email'] = $email;
            
            // Store the user type in session for password reset
            if ($adminExists) {
                $_SESSION['user_type'] = 'admin';
            } elseif ($studentExists) {
                $_SESSION['user_type'] = 'student';
            } else {
                $_SESSION['user_type'] = 'teacher';
            }
            
            // Return success response
            echo json_encode([
                'status' => 'success',
                'message' => 'Email verified successfully'
            ]);
            exit();
        } else {
            // Email not found in any table
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