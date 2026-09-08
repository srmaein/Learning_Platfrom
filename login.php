<?php
// Set secure session cookie parameters
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/DATABASE/db_connection.php';

// Regenerate session ID periodically
if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

$error = '';
$success = '';

// Check for password reset or logout status messages
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = "Password has been reset successfully. Please log in with your new password.";
} elseif (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = "You have been logged out successfully.";
}

// If already authenticated, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
        case 'admin':
            header("Location: " . BASE_URL . "MODELS/dashboard.html");
            exit();
        case 'teacher':
            header("Location: " . BASE_URL . "MODELS/teadas.html");
            exit();
        case 'student':
            header("Location: " . BASE_URL . "MODELS/index.html");
            exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputUsername = isset($_POST['un']) ? trim($_POST['un']) : '';
    $inputPassword = isset($_POST['pw']) ? $_POST['pw'] : '';

    if (empty($inputUsername) || empty($inputPassword)) {
        $error = "Please enter both username/email and password.";
    } else {
        try {
            $pdo = get_db_connection();

            // Query users joined with profiles
            $sql = "SELECT u.id, u.email, u.username, u.password_hash, u.role, u.status,
                           p.full_name, p.first_name, p.last_name, p.teacher_user_id
                    FROM users u
                    LEFT JOIN profiles p ON u.id = p.user_id
                    WHERE u.email = :input 
                       OR u.username = :input 
                       OR p.teacher_user_id = :input";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':input' => $inputUsername]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Verify account status
                if ($user['status'] !== 'ACTIVE') {
                    $error = "Your account has been deactivated. Please contact administration.";
                } else {
                    $authenticated = false;

                    // Password verification (hashed password)
                    if (password_verify($inputPassword, $user['password_hash'])) {
                        $authenticated = true;
                    } 
                    // Fallback for legacy plain text passwords during migration
                    elseif ($inputPassword === $user['password_hash']) {
                        $authenticated = true;
                        // Auto-upgrade legacy password to secure hash
                        $newHash = password_hash($inputPassword, PASSWORD_BCRYPT);
                        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
                        $updateStmt->execute([':hash' => $newHash, ':id' => $user['id']]);
                    }

                    if ($authenticated) {
                        // Prevent session fixation
                        session_regenerate_id(true);

                        $displayName = $user['full_name'] ?: (($user['first_name'] . ' ' . $user['last_name']) ?: $user['username']);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_type'] = strtolower($user['role']);
                        $_SESSION['role'] = strtolower($user['role']);
                        $_SESSION['name'] = trim($displayName);
                        $_SESSION['teacher_id'] = $user['teacher_user_id'];
                        $_SESSION['last_activity'] = time();
                        $_SESSION['last_regeneration'] = time();

                        // Update last login timestamp
                        $loginStmt = $pdo->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id");
                        $loginStmt->execute([':id' => $user['id']]);

                        // Redirect based on role
                        if ($_SESSION['user_type'] === 'admin') {
                            header("Location: " . BASE_URL . "MODELS/dashboard.html");
                            exit();
                        } elseif ($_SESSION['user_type'] === 'teacher') {
                            header("Location: " . BASE_URL . "MODELS/teadas.html");
                            exit();
                        } else {
                            header("Location: " . BASE_URL . "MODELS/index.html");
                            exit();
                        }
                    } else {
                        $error = "Invalid email/username or password.";
                    }
                }
            } else {
                $error = "Invalid email/username or password.";
            }

        } catch (Exception $e) {
            error_log("Login Exception: " . $e->getMessage());
            $error = "A system error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>login_style.css">
    <link rel="icon" type="image/x-icon" href="<?php echo IMAGES_PATH; ?>top.png">
    <title>Login - Online Learning Platform</title>
    <style>
        body {
            background: url('<?php echo BASE_URL; ?>PUBLIC/pic/img.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        .error-message {
            color: #fff;
            background-color: #e74c3c;
            padding: 12px 15px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .success-message {
            color: #fff;
            background-color: #2ecc71;
            padding: 12px 15px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="loginForm">
            <h1>Login</h1>
            <div class="input_box">
                <input type="text" placeholder="Username, Email or ID" id="un" name="un" required value="<?php echo isset($_POST['un']) ? htmlspecialchars($_POST['un']) : ''; ?>">
                <i class='bx bx-user-circle'></i>
                <span id="UsernameError" style="color: #ff6b6b; font-size: 12px;"></span>
            </div>
            <div class="input_box">
                <input type="password" placeholder="Password" id="pw" name="pw" required>
                <i class='bx bx-lock'></i>
                <span id="passwordError" style="color: #ff6b6b; font-size: 12px;"></span>
            </div>
            
            <div class="forget">
                <input type="checkbox" name="rememberMe" id="rememberMe">
                <label for="rememberMe">Remember Me</label>
                <a href="VIEWS/auth/forget-password.html">Forgot Password?</a>
            </div>

            <button type="submit" name="login" class="btn" id="loginBtn">Login</button>

            <div class="registration_link">
                <p>Don't have an account? <a href="VIEWS/USER/Student_Registration.html">Register as Student</a></p>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        
        form.addEventListener('submit', function() {
            loginBtn.disabled = true;
            loginBtn.innerText = 'Verifying...';
        });
    });
    </script>
</body>
</html>
