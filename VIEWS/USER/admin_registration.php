<?php
// Initialize variables
$admin_name = $age = $date_of_birth = $blood_group = $phone_number = $address = $email = $username = $password = $admin_code = '';
$errors = [];

// Database connection
try {
    require_once __DIR__ . '/../../DATABASE/db_connection.php';
    $conn = getPgPDO();
} catch(Exception $e) {
    $errors['db'] = "Connection failed: " . $e->getMessage();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate Security Access Code
    $admin_code = trim($_POST['admin_code'] ?? '');
    if (empty($admin_code) || $admin_code !== '123456') {
        $errors['admin_code'] = "Invalid or missing Admin Security Access Code";
    }

    // Validate admin_name
    $admin_name = trim($_POST['name'] ?? '');
    if (empty($admin_name)) {
        $errors['admin_name'] = "Name is required";
    } elseif (strlen($admin_name) < 3) {
        $errors['admin_name'] = "Name must be at least 3 characters long";
    }

    // Validate age
    $age = trim($_POST['age'] ?? '');
    if (empty($age)) {
        $errors['age'] = "Age is required";
    } elseif (!is_numeric($age) || $age < 18 || $age > 100) {
        $errors['age'] = "Age must be between 18 and 100";
    }

    // Validate date_of_birth
    $date_of_birth = trim($_POST['dob'] ?? '');
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
    $blood_group = trim($_POST['bloodGroup'] ?? '');
    $valid_blood_groups = ['A-', 'A+', 'B-', 'B+', 'AB-', 'AB+', 'O-', 'O+'];
    if (empty($blood_group)) {
        $errors['blood_group'] = "Blood group is required";
    } elseif (!in_array($blood_group, $valid_blood_groups)) {
        $errors['blood_group'] = "Invalid blood group";
    }

    // Validate phone_number
    $phone_number = trim($_POST['phone'] ?? '');
    if (empty($phone_number)) {
        $errors['phone_number'] = "Phone number is required";
    } elseif (!preg_match("/^[0-9]{11}$/", $phone_number)) {
        $errors['phone_number'] = "Please enter a valid 11-digit phone number";
    }

    // Validate address
    $address = trim($_POST['address'] ?? '');
    if (empty($address)) {
        $errors['address'] = "Address is required";
    }

    // Validate email
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address";
    } else {
        // Check if email already exists in users or admin_registration
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = "Email already registered";
        }
    }

    // Validate username
    $username = trim($_POST['username'] ?? '');
    if (empty($username)) {
        $errors['username'] = "Username is required";
    } elseif (strlen($username) < 4) {
        $errors['username'] = "Username must be at least 4 characters long";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors['username'] = "Username already taken";
        }
    }

    // Validate password
    $password = $_POST['password'] ?? '';
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters long";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert into users table
            $stmtUser = $conn->prepare("INSERT INTO users (email, username, password_hash, role, status) VALUES (?, ?, ?, 'admin', 'ACTIVE')");
            $stmtUser->execute([$email, $username, $password_hash]);
            $userId = $conn->lastInsertId();
            $stmtUser->closeCursor();

            // Split name into first and last
            $parts = explode(' ', $admin_name, 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? '';

            // Insert into profiles table
            $stmtProfile = $conn->prepare("INSERT INTO profiles (user_id, first_name, last_name, full_name, age, date_of_birth, blood_group, phone_number, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtProfile->execute([$userId, $firstName, $lastName, $admin_name, $age, $date_of_birth, $blood_group, $phone_number, $address]);
            $stmtProfile->closeCursor();

            // Also insert into admin_registration for legacy table compatibility
            try {
                $sqlLegacy = "INSERT INTO admin_registration (admin_name, age, date_of_birth, blood_group, phone_number, address, email, username, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtLegacy = $conn->prepare($sqlLegacy);
                $stmtLegacy->execute([$admin_name, $age, $date_of_birth, $blood_group, $phone_number, $address, $email, $username, $password_hash]);
                $stmtLegacy->closeCursor();
            } catch (Exception $exLegacy) {}

            $conn->commit();

            echo "<script>
                    alert('Admin Registration Successful!');
                    window.location.href = '../../login.php';
                  </script>";
            exit();
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
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
    <title>Admin Security Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f0c20 0%, #1a103c 50%, #2b1055 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            color: #fff;
        }

        .container {
            background: rgba(22, 16, 42, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(168, 85, 247, 0.3);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 30px rgba(168, 85, 247, 0.2);
            width: 100%;
            max-width: 650px;
        }

        h1 {
            text-align: center;
            color: #fff;
            margin-bottom: 8px;
            font-size: 2.2em;
            letter-spacing: 1px;
        }

        .subtitle {
            text-align: center;
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .security-box {
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.4);
            padding: 20px;
            border-radius: 14px;
            margin-bottom: 30px;
        }

        .security-title {
            font-size: 15px;
            font-weight: 600;
            color: #c084fc;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .code-input-group {
            display: flex;
            gap: 12px;
        }

        .code-input-group input {
            flex: 1;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(168, 85, 247, 0.4);
            border-radius: 10px;
            color: #fff;
            font-size: 16px;
            letter-spacing: 2px;
            font-weight: bold;
        }

        .code-input-group input:focus {
            outline: none;
            border-color: #a855f7;
            box-shadow: 0 0 15px rgba(168, 85, 247, 0.4);
        }

        .verify-btn {
            background: linear-gradient(135deg, #a855f7, #ec4899);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .verify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(168, 85, 247, 0.5);
        }

        #codeStatus {
            margin-top: 10px;
            font-size: 13px;
            font-weight: 500;
        }

        .status-success {
            color: #4ade80;
        }

        .status-error {
            color: #f87171;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
            font-weight: 500;
            font-size: 14px;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            font-size: 15px;
            color: #fff;
            transition: all 0.3s ease;
        }

        input:disabled, select:disabled, textarea:disabled {
            background: rgba(255, 255, 255, 0.02);
            border-color: rgba(255, 255, 255, 0.06);
            color: #64748b;
            cursor: not-allowed;
            opacity: 0.6;
        }

        input:focus:not(:disabled), select:focus:not(:disabled), textarea:focus:not(:disabled) {
            outline: none;
            border-color: #a855f7;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 15px rgba(168, 85, 247, 0.3);
        }

        select option {
            background: #1a103c;
            color: #fff;
        }

        .submit-btn {
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(168, 85, 247, 0.5);
        }

        .submit-btn:disabled {
            background: rgba(255, 255, 255, 0.1);
            color: #64748b;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: #c084fc;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .error {
            color: #f87171;
            font-size: 13px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Registration</h1>
        <p class="subtitle">Protected System Administrator Enrollment</p>

        <?php if (isset($errors['db'])): ?>
            <div class="error" style="margin-bottom: 20px; text-align: center; font-weight: bold;"><?php echo $errors['db']; ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="adminRegForm">
            
            <!-- Admin Security Access Code Box -->
            <div class="security-box">
                <div class="security-title">
                    <i class='bx bxs-shield-lock'></i> Required Admin Authorization Code
                </div>
                <div class="code-input-group">
                    <input type="password" id="admin_code" name="admin_code" placeholder="Enter Access Code" required value="<?php echo htmlspecialchars($admin_code); ?>">
                    <button type="button" id="verifyCodeBtn" class="verify-btn" onclick="verifyAdminCode()">Verify Code</button>
                </div>
                <div id="codeStatus">
                    <?php if (isset($errors['admin_code'])): ?>
                        <span class="status-error"><?php echo $errors['admin_code']; ?></span>
                    <?php else: ?>
                        <span>Please input the secret admin authorization code to unlock registration.</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Restricted Form Inputs (Disabled by default until code is verified via AJAX) -->
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($admin_name); ?>" required minlength="3" disabled>
                <?php if (isset($errors['admin_name'])): ?>
                    <div class="error"><?php echo $errors['admin_name']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($age); ?>" required min="18" max="100" disabled>
                <?php if (isset($errors['age'])): ?>
                    <div class="error"><?php echo $errors['age']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($date_of_birth); ?>" required disabled>
                <?php if (isset($errors['date_of_birth'])): ?>
                    <div class="error"><?php echo $errors['date_of_birth']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="bloodGroup">Blood Group</label>
                <select id="bloodGroup" name="bloodGroup" required disabled>
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
                <label for="phone">Phone Number (11 Digits)</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone_number); ?>" required pattern="[0-9]{11}" disabled>
                <?php if (isset($errors['phone_number'])): ?>
                    <div class="error"><?php echo $errors['phone_number']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" required disabled><?php echo htmlspecialchars($address); ?></textarea>
                <?php if (isset($errors['address'])): ?>
                    <div class="error"><?php echo $errors['address']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required disabled>
                <?php if (isset($errors['email'])): ?>
                    <div class="error"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required minlength="4" disabled>
                <?php if (isset($errors['username'])): ?>
                    <div class="error"><?php echo $errors['username']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6" disabled>
                <?php if (isset($errors['password'])): ?>
                    <div class="error"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" id="submitBtn" class="submit-btn" disabled>Complete Admin Registration</button>
        </form>
        <a href="../../index.php" class="back-link"><i class='bx bx-arrow-back'></i> Return to Landing Page</a>
    </div>

    <script>
        function toggleRestInputs(enable) {
            const inputs = document.querySelectorAll('#adminRegForm input:not(#admin_code), #adminRegForm select, #adminRegForm textarea, #submitBtn');
            inputs.forEach(input => {
                if (enable) {
                    input.removeAttribute('disabled');
                } else {
                    input.setAttribute('disabled', 'disabled');
                }
            });
        }

        function verifyAdminCode() {
            const codeInput = document.getElementById('admin_code');
            const codeStatus = document.getElementById('codeStatus');
            const verifyBtn = document.getElementById('verifyCodeBtn');
            const code = codeInput.value.trim();

            if (!code) {
                codeStatus.innerHTML = '<span class="status-error">✗ Please enter an authorization code.</span>';
                toggleRestInputs(false);
                return;
            }

            codeStatus.innerHTML = '<span style="color: #94a3b8;"><i class="bx bx-loader-alt bx-spin"></i> Verifying code...</span>';

            const formData = new FormData();
            formData.append('admin_code', code);

            fetch('../../CONTROLLAR/process/verify_admin_code.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    codeStatus.innerHTML = '<span class="status-success">' + data.message + '</span>';
                    toggleRestInputs(true);
                    codeInput.setAttribute('readonly', 'readonly');
                    verifyBtn.textContent = 'Verified ✓';
                    verifyBtn.style.background = '#10b981';
                    verifyBtn.disabled = true;
                } else {
                    codeStatus.innerHTML = '<span class="status-error">' + data.message + '</span>';
                    toggleRestInputs(false);
                }
            })
            .catch(error => {
                console.error('AJAX error:', error);
                codeStatus.innerHTML = '<span class="status-error">✗ Verification request failed. Try again.</span>';
                toggleRestInputs(false);
            });
        }

        // Automatic verification on typing 6 digits
        document.getElementById('admin_code').addEventListener('input', function() {
            if (this.value.trim().length >= 6) {
                verifyAdminCode();
            }
        });
    </script>
</body>
</html>