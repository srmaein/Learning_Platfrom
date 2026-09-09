<?php
require_once __DIR__ . '/../DATABASE/db_connection.php';

class Database {
    private static $instance = null;
    private $conn;

    public function __construct() {
        $this->conn = getPgPDO();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = getPgPDO();
        }
        return self::$instance;
    }

    public function getConnection() {
        return getPgPDO();
    }

    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
                $result = $stmt->fetch();
                return [
                    'status' => 'success',
                    'message' => 'Database connection successful',
                    'user_count' => $result['count'] ?? 0
                ];
            }
        } catch(Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
?>