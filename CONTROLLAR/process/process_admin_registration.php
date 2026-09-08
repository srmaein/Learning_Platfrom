<?php
// Database connection
$host = 'localhost';
$dbname = 'online_education';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to sanitize input data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input data
    $name = sanitize($_POST['name']);
    $age = filter_var($_POST['age'], FILTER_VALIDATE_INT);
    $dob = sanitize($_POST['dob']);
    $bloodGroup = sanitize($_POST['bloodGroup']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    // Validate required fields
    if (empty($name) || empty($age) || empty($dob) || empty($bloodGroup) || 
        empty($phone) || empty($address) || empty($email) || 
        empty($username) || empty($password)) {
        header("Location: ../../VIEWS/USER/admin_registration.php?error=empty_fields");
        exit();
    }

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_registration WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            header("Location: ../../VIEWS/USER/admin_registration.php?error=email_exists");
            exit();
        }

        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_registration WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            header("Location: ../../VIEWS/USER/admin_registration.php?error=username_exists");
            exit();
        }

        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert data into database
        $sql = "INSERT INTO admin_registration (admin_name, age, date_of_birth, blood_group, 
                phone_number, address, email, username, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name,
            $age,
            $dob,
            $bloodGroup,
            $phone,
            $address,
            $email,
            $username,
            $password_hash
        ]);

        // Redirect to Admin_view.php on success
        header("Location: ../../VIEWS/USER/Admin_view.php?registration=success");
        exit();
    } catch(PDOException $e) {
        header("Location: ../../VIEWS/USER/admin_registration.php?error=database_error");
        exit();
    }
} else {
    // If not POST request, redirect to registration page
    header("Location: ../../VIEWS/USER/admin_registration.php");
    exit();
}
?> 