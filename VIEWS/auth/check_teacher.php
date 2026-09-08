<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once 'db_connection.php';

// Check if the request includes a specific teacher to look up
$username = isset($_GET['username']) ? $_GET['username'] : 'karim45';
$email = isset($_GET['email']) ? $_GET['email'] : 'karim.abdul@example.com';

// Function to safely display password info
function displayPasswordInfo($password) {
    if (empty($password)) {
        return 'Empty password';
    }
    
    $length = strlen($password);
    $firstChars = substr($password, 0, 3);
    $lastChars = substr($password, -3);
    
    return "Length: $length chars, Starts with: $firstChars..., Ends with: ...$lastChars";
}

// Function to check if password would verify
function checkPassword($inputPassword, $hashedPassword) {
    if (empty($hashedPassword)) {
        return "Cannot verify: stored password is empty";
    }
    
    // Check if it looks like a bcrypt hash
    if (strlen($hashedPassword) === 60 && substr($hashedPassword, 0, 4) === '$2y$') {
        if (password_verify($inputPassword, $hashedPassword)) {
            return "MATCH! Password verified with hash";
        } else {
            return "NO MATCH! Password did not verify with hash";
        }
    }
    
    // Direct comparison
    if ($inputPassword === $hashedPassword) {
        return "MATCH! Direct string comparison matches";
    }
    
    return "NO MATCH! Neither hashed nor direct comparison works";
}

// Function to create HTML table from array
function arrayToTable($array) {
    if (empty($array) || !is_array($array)) {
        return "<p>No data found.</p>";
    }
    
    $html = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
    
    // Table header
    $html .= '<tr style="background-color: #f2f2f2;">';
    foreach ($array as $key => $value) {
        $html .= '<th>' . htmlspecialchars($key) . '</th>';
    }
    $html .= '</tr>';
    
    // Table row
    $html .= '<tr>';
    foreach ($array as $key => $value) {
        // Special handling for the password field
        if ($key === 'password') {
            $html .= '<td>' . displayPasswordInfo($value) . '</td>';
        } else {
            $html .= '<td>' . htmlspecialchars($value) . '</td>';
        }
    }
    $html .= '</tr>';
    
    $html .= '</table>';
    return $html;
}

// HTML Header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Database Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        h1, h2, h3 {
            color: #2e7d32;
        }
        .section {
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }
        .password-test {
            background-color: #fff8e1;
            border: 1px solid #ffecb3;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .success {
            color: #2e7d32;
            font-weight: bold;
        }
        .error {
            color: #c62828;
            font-weight: bold;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 15px 0;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <h1>Teacher Database Check</h1>';

try {
    echo '<div class="section">';
    echo '<h2>Database Connection</h2>';
    echo '<p>Database connection successful.</p>';
    echo '</div>';
    
    // Get database structure for teachers table
    echo '<div class="section">';
    echo '<h2>Teachers Table Structure</h2>';
    
    $stmt = $conn->prepare("DESCRIBE teachers");
    $stmt->execute();
    $tableStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($tableStructure) > 0) {
        echo '<table>';
        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        
        foreach ($tableStructure as $column) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($column['Field']) . '</td>';
            echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($column['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($column['Key']) . '</td>';
            echo '<td>' . htmlspecialchars($column['Default'] ?? 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($column['Extra']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        echo '<p>Table structure not found.</p>';
    }
    echo '</div>';
    
    // Get teacher by username
    echo '<div class="section">';
    echo '<h2>Teacher Record by Username</h2>';
    
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $teacherByUsername = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($teacherByUsername) {
        echo arrayToTable($teacherByUsername);
        
        // Store password for testing
        $storedPassword = $teacherByUsername['password'];
        
        echo '<div class="password-test">';
        echo '<h3>Password Test for Username Login</h3>';
        echo '<p>Testing password "pass1234" against stored password:</p>';
        echo '<p>' . checkPassword('pass1234', $storedPassword) . '</p>';
        echo '</div>';
    } else {
        echo '<p>No teacher found with username: ' . htmlspecialchars($username) . '</p>';
    }
    echo '</div>';
    
    // Get teacher by email
    echo '<div class="section">';
    echo '<h2>Teacher Record by Email</h2>';
    
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $teacherByEmail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($teacherByEmail) {
        echo arrayToTable($teacherByEmail);
        
        // Only test password if different from previous test
        if (!isset($teacherByUsername) || $teacherByEmail['id'] !== $teacherByUsername['id']) {
            $storedPassword = $teacherByEmail['password'];
            
            echo '<div class="password-test">';
            echo '<h3>Password Test for Email Login</h3>';
            echo '<p>Testing password "pass1234" against stored password:</p>';
            echo '<p>' . checkPassword('pass1234', $storedPassword) . '</p>';
            echo '</div>';
        }
    } else {
        echo '<p>No teacher found with email: ' . htmlspecialchars($email) . '</p>';
    }
    echo '</div>';
    
    // Suggest fixes based on findings
    echo '<div class="section">';
    echo '<h2>Possible Solutions</h2>';
    
    if (!$teacherByUsername && !$teacherByEmail) {
        echo '<p class="error">No teacher record found at all. You need to create the teacher record first.</p>';
        echo '<p>Run this SQL to add a teacher:</p>';
        echo '<pre>
INSERT INTO teachers (username, password, teacher_name, email, user_id) 
VALUES (\'karim45\', \'pass1234\', \'Karim Abdul\', \'karim.abdul@example.com\', \'T123\');
        </pre>';
    } else {
        if (isset($storedPassword)) {
            // Check if password is hashed
            $isHashed = (strlen($storedPassword) === 60 && substr($storedPassword, 0, 4) === '$2y$');
            
            if ($isHashed) {
                echo '<p>The password appears to be properly hashed.</p>';
                echo '<p>If login is still failing, you may need to reset the password:</p>';
            } else {
                echo '<p class="error">The password does not appear to be properly hashed.</p>';
                echo '<p>You should update the password with proper hashing:</p>';
            }
            
            echo '<pre>
// For plaintext password storage (not recommended):
UPDATE teachers SET password = \'pass1234\' WHERE username = \'karim45\';

// For hashed password storage (recommended):
UPDATE teachers SET password = \'' . password_hash('pass1234', PASSWORD_BCRYPT) . '\' 
WHERE username = \'karim45\';
            </pre>';
        }
    }
    
    echo '</div>';
    
} catch(PDOException $e) {
    echo '<div class="section error">';
    echo '<h2>Database Error</h2>';
    echo '<p>Connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}

// HTML Footer
echo '</body></html>';
?> 