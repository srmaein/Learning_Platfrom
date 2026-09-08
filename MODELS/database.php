<?php
require_once __DIR__ . '/../DATABASE/db_connection.php';

class Database {
    private static $instance = null;
    private $conn;

    public function __construct() {
        $this->conn = get_db_connection();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->getConnection();
    }

    public function getConnection() {
        return $this->conn;
    }

    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
                $result = $stmt->fetch();
                return [
                    'status' => 'success',
                    'message' => 'Railway PostgreSQL database connection successful',
                    'user_count' => $result['count']
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
?>