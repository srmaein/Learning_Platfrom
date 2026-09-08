<?php
require_once '../../DATABASE/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Sanitize and validate input
        $teacher_name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $age = filter_var($_POST['age'], FILTER_VALIDATE_INT);
        $date_of_birth = $_POST['dob'];
        $blood_group = $_POST['bloodGroup'];
        $phone_number = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
        $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $qualifications = filter_var($_POST['qualifications'], FILTER_SANITIZE_STRING);
        $user_id = filter_var($_POST['userId'], FILTER_SANITIZE_STRING);
        $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Check if email already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM teachers WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Email already registered");
        }

        // Check if username already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM teachers WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username already taken");
        }

        // Check if user_id already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM teachers WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("User ID already exists");
        }

        $sql = "INSERT INTO teachers (teacher_name, age, date_of_birth, blood_group, phone_number, address, email, qualifications, user_id, username, password)
                VALUES (:teacher_name, :age, :date_of_birth, :blood_group, :phone_number, :address, :email, :qualifications, :user_id, :username, :password)";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':teacher_name', $teacher_name);
        $stmt->bindParam(':age', $age);
        $stmt->bindParam(':date_of_birth', $date_of_birth);
        $stmt->bindParam(':blood_group', $blood_group);
        $stmt->bindParam(':phone_number', $phone_number);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':qualifications', $qualifications);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);

        $stmt->execute();
        error_log("Teacher registered successfully: " . $username);
        echo json_encode(['status' => 'success', 'message' => 'Registration successful! You can now login.']);
        
    } catch(Exception $e) {
        error_log("Teacher registration error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>