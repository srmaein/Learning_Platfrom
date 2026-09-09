<?php
require_once __DIR__ . '/../../DATABASE/db_connection.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

try {
    $teacher_name = trim($_POST['name'] ?? '');
    $age = filter_var($_POST['age'] ?? 0, FILTER_VALIDATE_INT);
    $date_of_birth = trim($_POST['dob'] ?? '');
    $blood_group = trim($_POST['bloodGroup'] ?? '');
    $phone_number = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $qualifications = trim($_POST['qualifications'] ?? '');
    $teacher_user_id = trim($_POST['userId'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $rawPassword = $_POST['password'] ?? '';

    if (empty($teacher_name) || empty($email) || empty($username) || empty($rawPassword)) {
        throw new Exception("Missing required fields (Name, Email, Username, or Password)");
    }

    if (!$email) {
        throw new Exception("Invalid email format");
    }

    if (strlen($rawPassword) < 6) {
        throw new Exception("Password must be at least 6 characters long");
    }

    $pdo = getPgPDO();
    $pdo->beginTransaction();

    // Check if email exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $emailCount = $stmt->fetchColumn();
    $stmt->closeCursor();

    if ($emailCount > 0) {
        throw new Exception("Email already registered");
    }

    // Check if username exists
    $stmtUserCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmtUserCheck->execute([$username]);
    $userCount = $stmtUserCheck->fetchColumn();
    $stmtUserCheck->closeCursor();

    if ($userCount > 0) {
        throw new Exception("Username already taken");
    }

    $passwordHash = password_hash($rawPassword, PASSWORD_BCRYPT);

    // Insert user record (Cross-database compatible)
    $stmtUser = $pdo->prepare("INSERT INTO users (email, username, password_hash, role, status) VALUES (?, ?, ?, 'teacher', 'ACTIVE')");
    $stmtUser->execute([$email, $username, $passwordHash]);
    $userId = $pdo->lastInsertId();
    $stmtUser->closeCursor();

    // Split name if possible
    $parts = explode(' ', $teacher_name, 2);
    $firstName = $parts[0];
    $lastName = $parts[1] ?? '';

    // Insert profile record
    $stmtProfile = $pdo->prepare("INSERT INTO profiles (user_id, first_name, last_name, full_name, age, date_of_birth, blood_group, phone_number, address, qualifications, teacher_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtProfile->execute([$userId, $firstName, $lastName, $teacher_name, $age ?: null, $date_of_birth ?: null, $blood_group ?: null, $phone_number, $address, $qualifications, $teacher_user_id ?: null]);
    $stmtProfile->closeCursor();

    $pdo->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => 'Registration successful! You can now login.',
        'redirect' => '../../login.php'
    ]);
    exit();

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Teacher registration error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit();
}
?>