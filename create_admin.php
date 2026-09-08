<?php
/**
 * Initial Administrator Creation Script
 * Usage via CLI:
 *   php create_admin.php <email> <username> <full_name> <password>
 * Or access via Web browser if no admin exists yet.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/DATABASE/db_connection.php';

$message = '';
$error = '';
$isCli = (php_sapi_name() === 'cli');

try {
    $pdo = get_db_connection();

    // Check if CLI arguments were passed
    if ($isCli && $argc >= 5) {
        $email = trim($argv[1]);
        $username = trim($argv[2]);
        $fullName = trim($argv[3]);
        $password = $argv[4];

        createAdminAccount($pdo, $email, $username, $fullName, $password);
        echo "\n[SUCCESS] Administrator account successfully created!\n";
        echo "Email: {$email}\nUsername: {$username}\nRole: admin\n\n";
        exit(0);
    }

    // Web handling
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $setupKey = $_POST['setup_key'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';

        // Verification: check if admin already exists
        $adminCheck = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        $expectedKey = getenv('ADMIN_SETUP_KEY') ?: 'RailwayAdminSetup2026';

        if ($adminCheck > 0 && $setupKey !== $expectedKey) {
            throw new Exception("An administrator already exists. Security setup key required.");
        }

        if (empty($email) || empty($username) || empty($password)) {
            throw new Exception("Email, username, and password are required.");
        }

        createAdminAccount($pdo, $email, $username, $fullName, $password);
        $message = "Administrator account '{$username}' successfully created! You may now log in.";
    }

} catch (Exception $e) {
    if ($isCli) {
        echo "\n[ERROR] " . $e->getMessage() . "\n\n";
        exit(1);
    }
    $error = $e->getMessage();
}

function createAdminAccount($pdo, $email, $username, $fullName, $password) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address.");
    }
    if (strlen($password) < 8) {
        throw new Exception("Password must be at least 8 characters long.");
    }

    // Check existing email/username
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email OR username = :username");
    $stmt->execute([':email' => $email, ':username' => $username]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Email or username already in use.");
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $nameParts = explode(' ', $fullName, 2);
    $firstName = $nameParts[0] ?? $username;
    $lastName = $nameParts[1] ?? '';

    $pdo->beginTransaction();

    $userSql = "INSERT INTO users (email, username, password_hash, role, status)
                VALUES (:email, :username, :password_hash, 'admin', 'ACTIVE')
                RETURNING id";
    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute([
        ':email' => $email,
        ':username' => $username,
        ':password_hash' => $passwordHash
    ]);
    $userId = $userStmt->fetchColumn();

    $profileSql = "INSERT INTO profiles (user_id, first_name, last_name, full_name)
                   VALUES (:user_id, :first_name, :last_name, :full_name)";
    $profileStmt = $pdo->prepare($profileSql);
    $profileStmt->execute([
        ':user_id' => $userId,
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':full_name' => $fullName ?: $username
    ]);

    $pdo->commit();
}

if ($isCli) {
    echo "\n----------------------------------------------------\n";
    echo "  ONLINE LEARNING PLATFORM - ADMIN SETUP SCRIPT\n";
    echo "----------------------------------------------------\n";
    echo "Usage:\n";
    echo "  php create_admin.php <email> <username> \"<full_name>\" <password>\n\n";
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Initial Administrator Setup</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
        h2 { margin-top: 0; color: #2c3e50; font-size: 22px; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #7f8c8d; font-size: 13px; font-weight: 600; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #dcdfe6; border-radius: 5px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background: #3498db; color: #ffffff; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        .alert-error { background: #fde8e8; color: #e74c3c; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; }
        .alert-success { background: #e8f8f5; color: #2ecc71; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Administrator Setup</h2>
        <?php if ($error): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert-success">
                <?php echo htmlspecialchars($message); ?>
                <br><br>
                <a href="login.php" style="color: #2ecc71; font-weight: bold;">Proceed to Login</a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Admin Full Name</label>
                    <input type="text" name="full_name" required placeholder="e.g. System Administrator">
                </div>
                <div class="form-group">
                    <label>Admin Email</label>
                    <input type="email" name="email" required placeholder="admin@example.com">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="admin">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters">
                </div>
                <div class="form-group">
                    <label>Setup Key (Optional if no admins exist)</label>
                    <input type="password" name="setup_key" placeholder="Enter setup key if admins exist">
                </div>
                <button type="submit" class="btn">Create Administrator</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
