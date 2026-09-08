<?php
// Initialize variables
$admin_name = $age = $date_of_birth = $blood_group = $phone_number = $address = $email = $username = $password = '';
$errors = [];

// Database connection
try {
    $host = 'localhost';
    $dbname = 'online_education';
    $username = 'root';
    $password = '';

    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $errors['db'] = "Connection failed: " . $e->getMessage();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate admin_name
    $admin_name = trim($_POST['name']);
    if (empty($admin_name)) {
        $errors['admin_name'] = "Name is required";
    } elseif (strlen($admin_name) < 3) {
        $errors['admin_name'] = "Name must be at least 3 characters long";
    }

    // Validate age
    $age = trim($_POST['age']);
    if (empty($age)) {
        $errors['age'] = "Age is required";
    } elseif (!is_numeric($age) || $age < 18 || $age > 100) {
        $errors['age'] = "Age must be between 18 and 100";
    }

    // Validate date_of_birth
    $date_of_birth = trim($_POST['dob']);
    if (empty($date_of_birth)) {
        $errors['date_of_birth'] = "Date of birth is required";
    } else {
        $dob = new DateTime($date_of_birth);
        $today = new DateTime();
        $age_calc = $today->diff($dob)->y;
        if ($age_calc < 18) {
            $errors['date_of_birth'] = "You must be at least 18 years old";
        }
    }

    // Validate blood_group
    $blood_group = trim($_POST['bloodGroup']);
    $valid_blood_groups = ['A-', 'A+', 'B-', 'B+', 'AB-', 'AB+', 'O-', 'O+'];
    if (empty($blood_group)) {
        $errors['blood_group'] = "Blood group is required";
    } elseif (!in_array($blood_group, $valid_blood_groups)) {
        $errors['blood_group'] = "Invalid blood group";
    }

    // Validate phone_number
    $phone_number = trim($_POST['phone']);
    if (empty($phone_number)) {
        $errors['phone_number'] = "Phone number is required";
    } elseif (!preg_match("/^[0-9]{11}$/", $phone_number)) {
        $errors['phone_number'] = "Please enter a valid 11-digit phone number";
    }

    // Validate address
    $address = trim($_POST['address']);
    if (empty($address)) {
        $errors['address'] = "Address is required";
    }

    // Validate email
    $email = trim($_POST['email']);
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_registration WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = "Email already exists";
        }
    }

    // Validate username
    $username = trim($_POST['username']);
    if (empty($username)) {
        $errors['username'] = "Username is required";
    } elseif (strlen($username) < 4) {
        $errors['username'] = "Username must be at least 4 characters long";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_registration WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors['username'] = "Username already exists";
        }
    }

    // Validate password
    $password = $_POST['password'];
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters long";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO admin_registration (admin_name, age, date_of_birth, blood_group, phone_number, address, email, username, password) 
                    VALUES (:admin_name, :age, :date_of_birth, :blood_group, :phone_number, :address, :email, :username, :password)";
            
            $stmt = $conn->prepare($sql);
            
            $stmt->bindParam(':admin_name', $admin_name);
            $stmt->bindParam(':age', $age);
            $stmt->bindParam(':date_of_birth', $date_of_birth);
            $stmt->bindParam(':blood_group', $blood_group);
            $stmt->bindParam(':phone_number', $phone_number);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password_hash);

            if ($stmt->execute()) {
                echo "<script>
                        alert('Registration successful!');
                        window.location.href = 'Admin_view.php';
                      </script>";
                exit();
            }
        } catch (PDOException $e) {
            $errors['db'] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 600px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .error {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Registration</h1>
        <?php if (isset($errors['db'])): ?>
            <div class="error"><?php echo $errors['db']; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="registration-form">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($admin_name); ?>" required minlength="3">
                <?php if (isset($errors['admin_name'])): ?>
                    <div class="error"><?php echo $errors['admin_name']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($age); ?>" required min="18" max="100">
                <?php if (isset($errors['age'])): ?>
                    <div class="error"><?php echo $errors['age']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($date_of_birth); ?>" required>
                <?php if (isset($errors['date_of_birth'])): ?>
                    <div class="error"><?php echo $errors['date_of_birth']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="bloodGroup">Blood Group</label>
                <select id="bloodGroup" name="bloodGroup" required>
                    <option value="">Select Blood Group</option>
                    <?php
                    $blood_groups = ['A-', 'A+', 'B-', 'B+', 'AB-', 'AB+', 'O-', 'O+'];
                    foreach ($blood_groups as $group) {
                        $selected = ($blood_group === $group) ? 'selected' : '';
                        echo "<option value=\"$group\" $selected>$group</option>";
                    }
                    ?>
                </select>
                <?php if (isset($errors['blood_group'])): ?>
                    <div class="error"><?php echo $errors['blood_group']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone_number); ?>" required pattern="[0-9]{11}">
                <?php if (isset($errors['phone_number'])): ?>
                    <div class="error"><?php echo $errors['phone_number']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" required><?php echo htmlspecialchars($address); ?></textarea>
                <?php if (isset($errors['address'])): ?>
                    <div class="error"><?php echo $errors['address']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <div class="error"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required minlength="4">
                <?php if (isset($errors['username'])): ?>
                    <div class="error"><?php echo $errors['username']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
                <?php if (isset($errors['password'])): ?>
                    <div class="error"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="submit-btn">Register</button>
        </form>
        <a href="Admin_view.php" class="back-link">Back to Dashboard</a>
    </div>

    <script>
        // Add input event listeners for real-time validation
        document.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('input', function() {
                const errorElement = document.getElementById(this.id + 'Error');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 