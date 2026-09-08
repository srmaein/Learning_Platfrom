<?php
require_once __DIR__ . '/../DATABASE/db_connection.php';

class Database {
    private $conn;

    public function __construct() {
        $this->conn = get_db_connection();
    }

    public static function getInstance() {
        return get_db_connection();
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>