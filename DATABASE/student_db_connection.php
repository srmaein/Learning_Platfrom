<?php
require_once __DIR__ . '/db_connection.php';

try {
    $pdo = getPgPDO();
    $conn = $pdo;
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>