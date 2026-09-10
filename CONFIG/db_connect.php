<?php
// Central Database Connection configuration for school management portal
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../DATABASE/db_connection.php';

if (!function_exists('get_school_db')) {
    function get_school_db() {
        static $pdo = null;
        if ($pdo !== null) {
            return $pdo;
        }

        try {
            $pdo = getPgPDO();
            if ($pdo) {
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                return $pdo;
            }
        } catch (Exception $e) {
            error_log("DB Connect Error: " . $e->getMessage());
        }

        // Direct MySQL fallback
        $myHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $myDb   = defined('DB_NAME') ? DB_NAME : 'online_education';
        $myUser = defined('DB_USER') ? DB_USER : 'root';
        $myPass = defined('DB_PASS') ? DB_PASS : '';

        $pdo = new PDO("mysql:host={$myHost};dbname={$myDb};charset=utf8mb4", $myUser, $myPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        return $pdo;
    }
}

$conn = get_school_db();
$pdo = $conn;
?>
