<?php
define('IS_API_REQUEST', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../DATABASE/db_connection.php';

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }

    $first_name = isset($_POST['first']) ? trim($_POST['first']) : '';
    $last_name = isset($_POST['last']) ? trim($_POST['last']) : '';
    $contact = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
    $gender = isset($_POST['gender']) ? strtolower(trim($_POST['gender'])) : 'male';
    $blood_group = isset($_POST['blood']) ? trim($_POST['blood']) : 'A+';
    $email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
    $raw_password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($raw_password)) {
        throw new Exception("First name, last name, email, and password are required.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }

    if (strlen($raw_password) < 6) {
        throw new Exception("Password must be at least 6 characters long.");
    }

    $pdo = get_db_connection();

    // Check if email already exists in users table
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $checkStmt->execute([':email' => $email]);
    if ($checkStmt->fetchColumn() > 0) {
        throw new Exception("An account with this email already exists.");
    }

    // Generate unique username from email prefix
    $usernameBase = explode('@', $email)[0];
    $usernameBase = preg_replace('/[^a-zA-Z0-9_]/', '', $usernameBase);
    $username = $usernameBase;
    $counter = 1;

    while (true) {
        $uCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
        $uCheck->execute([':u' => $username]);
        if ($uCheck->fetchColumn() == 0) {
            break;
        }
        $username = $usernameBase . $counter;
        $counter++;
    }

    // Hash password securely
    $password_hash = password_hash($raw_password, PASSWORD_BCRYPT);
    $full_name = trim($first_name . ' ' . $last_name);

    // Use transaction for atomic user & profile creation
    $pdo->beginTransaction();

    $userSql = "INSERT INTO users (email, username, password_hash, role, status)
                VALUES (:email, :username, :password_hash, 'student', 'ACTIVE')
                RETURNING id";
    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute([
        ':email' => $email,
        ':username' => $username,
        ':password_hash' => $password_hash
    ]);
    $userId = $userStmt->fetchColumn();

    $profileSql = "INSERT INTO profiles (user_id, first_name, last_name, full_name, gender, blood_group, phone_number)
                   VALUES (:user_id, :first_name, :last_name, :full_name, :gender, :blood_group, :phone_number)";
    $profileStmt = $pdo->prepare($profileSql);
    $profileStmt->execute([
        ':user_id' => $userId,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':full_name' => $full_name,
        ':gender' => $gender,
        ':blood_group' => $blood_group,
        ':phone_number' => $contact
    ]);

    $pdo->commit();

    // Auto-login registered student
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['user_type'] = 'student';
    $_SESSION['role'] = 'student';
    $_SESSION['name'] = $full_name;
    $_SESSION['last_activity'] = time();

    echo json_encode([
        'status' => 'success',
        'message' => 'Registration successful! Redirecting to dashboard...',
        'redirect' => '../../MODELS/index.html'
    ]);
    exit();

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Student Registration Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit();
}
?>