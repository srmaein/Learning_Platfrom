<?php
require_once __DIR__ . '/../../DATABASE/db_connection.php';
header('Content-Type: application/json');

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }

    $required_fields = ['first', 'last', 'mobile', 'gender', 'blood', 'email', 'password'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("Missing or empty required field: $field");
        }
    }

    $first_name = trim($_POST['first']);
    $last_name = trim($_POST['last']);
    $contact = trim($_POST['mobile']);
    $gender = strtolower(trim($_POST['gender']));
    $blood_group = trim($_POST['blood']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $rawPassword = $_POST['password'];

    if (!$email) {
        throw new Exception("Invalid email format");
    }

    if (strlen($rawPassword) < 6) {
        throw new Exception("Password must be at least 6 characters long");
    }

    $pdo = getPgPDO();
    $pdo->beginTransaction();

    // Check if email already exists
    $check_email = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $check_email->execute([$email]);
    $emailCount = $check_email->fetchColumn();
    $check_email->closeCursor();

    if ($emailCount > 0) {
        throw new Exception("Email already registered");
    }

    // Generate unique username
    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $first_name . '.' . $last_name));
    if (empty($username)) {
        $username = 'student_' . time();
    }
    $check_user = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $check_user->execute([$username]);
    $userCount = $check_user->fetchColumn();
    $check_user->closeCursor();

    if ($userCount > 0) {
        $username .= '_' . rand(100, 999);
    }

    $passwordHash = password_hash($rawPassword, PASSWORD_BCRYPT);

    // Insert user record (Cross-database compatible)
    $stmtUser = $pdo->prepare("INSERT INTO users (email, username, password_hash, role, status) VALUES (?, ?, ?, 'student', 'ACTIVE')");
    $stmtUser->execute([$email, $username, $passwordHash]);
    $userId = $pdo->lastInsertId();
    $stmtUser->closeCursor();

    // Insert profile record
    $fullName = $first_name . ' ' . $last_name;
    $stmtProfile = $pdo->prepare("INSERT INTO profiles (user_id, first_name, last_name, full_name, gender, blood_group, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtProfile->execute([$userId, $first_name, $last_name, $fullName, $gender, $blood_group, $contact]);
    $stmtProfile->closeCursor();

    $pdo->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => 'Registration successful!',
        'redirect' => '../../login.php'
    ]);
    exit();

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Student Registration Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
    exit();
}
?>