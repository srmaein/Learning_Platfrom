<?php
// Database connection diagnostic check
require_once __DIR__ . '/../../DATABASE/db_connection.php';

try {
    $pdo = getPgPDO();
    echo "PostgreSQL Connection Successful!\n\n";
    
    // Check users table in PostgreSQL
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'");
    $teacherCount = $stmt->fetchColumn();
    echo "Number of teachers in PostgreSQL database: " . $teacherCount . "\n";

    // Show users table count by role
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    echo "\nUser breakdown by role:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . ucfirst($row['role']) . ": " . $row['count'] . "\n";
    }

} catch(Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>