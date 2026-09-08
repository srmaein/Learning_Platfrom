<?php
// Database connection
$host = 'localhost';
$dbname = 'Online_Education';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if teachers table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'teachers'");
    if ($stmt->rowCount() == 0) {
        // Create teachers table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS teachers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_name VARCHAR(100) NOT NULL,
            age INT NOT NULL,
            date_of_birth DATE NOT NULL,
            blood_group ENUM('A-', 'A+', 'B-', 'B+', 'AB-', 'AB+', 'O-', 'O+') NOT NULL,
            phone_number VARCHAR(15) NOT NULL,
            address TEXT NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            qualifications TEXT NOT NULL,
            user_id VARCHAR(50) NOT NULL UNIQUE,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "Teachers table created successfully!\n";
    }

    // Check table structure
    $stmt = $pdo->query("DESCRIBE teachers");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Required columns
    $required_columns = ['id', 'teacher_name', 'email', 'username', 'password', 'user_id'];
    $missing_columns = array_diff($required_columns, $columns);
    
    if (!empty($missing_columns)) {
        echo "Missing columns found: " . implode(', ', $missing_columns) . "\n";
        
        // Add missing columns
        foreach ($missing_columns as $column) {
            switch ($column) {
                case 'email':
                    $sql = "ALTER TABLE teachers ADD email VARCHAR(100) NOT NULL UNIQUE";
                    break;
                case 'username':
                    $sql = "ALTER TABLE teachers ADD username VARCHAR(50) NOT NULL UNIQUE";
                    break;
                case 'password':
                    $sql = "ALTER TABLE teachers ADD password VARCHAR(255) NOT NULL";
                    break;
                case 'user_id':
                    $sql = "ALTER TABLE teachers ADD user_id VARCHAR(50) NOT NULL UNIQUE";
                    break;
            }
            $pdo->exec($sql);
            echo "Added column: $column\n";
        }
    }

    // Check if there are any teachers in the table
    $stmt = $pdo->query("SELECT COUNT(*) FROM teachers");
    $count = $stmt->fetchColumn();
    echo "Number of teachers in database: $count\n";

    // Show table structure
    $stmt = $pdo->query("DESCRIBE teachers");
    echo "\nTable structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }

} catch(PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?> 