<?php
/**
 * Centralized PostgreSQL Database Connection Layer for Railway
 */

function get_db_connection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dbUrl = getenv('DATABASE_URL');
    if (!$dbUrl && isset($_ENV['DATABASE_URL'])) {
        $dbUrl = $_ENV['DATABASE_URL'];
    }

    $host = getenv('PGHOST') ?: ($_ENV['PGHOST'] ?? '127.0.0.1');
    $port = getenv('PGPORT') ?: ($_ENV['PGPORT'] ?? '5432');
    $dbname = getenv('PGDATABASE') ?: ($_ENV['PGDATABASE'] ?? 'online_education');
    $user = getenv('PGUSER') ?: ($_ENV['PGUSER'] ?? 'postgres');
    $pass = getenv('PGPASSWORD') ?: ($_ENV['PGPASSWORD'] ?? 'postgres');

    if ($dbUrl) {
        $parsed = parse_url($dbUrl);
        if ($parsed) {
            $host = $parsed['host'] ?? $host;
            $port = $parsed['port'] ?? $port;
            $user = $parsed['user'] ?? $user;
            $pass = $parsed['pass'] ?? $pass;
            $dbname = ltrim($parsed['path'] ?? '', '/') ?: $dbname;
        }
    }

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("PostgreSQL Database Connection Error: " . $e->getMessage());
        if (defined('IS_API_REQUEST') && IS_API_REQUEST) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Database connection unavailable.'
            ]);
            exit();
        }
        throw new Exception("Database connection failed. Please ensure Railway PostgreSQL is running and environment variables are set.");
    }
}

// Global PDO connection variable for backward compatibility with procedural code
try {
    $conn = get_db_connection();
    $pdo = $conn;
} catch (Exception $e) {
    // Graceful fallback
    $conn = null;
    $pdo = null;
}
?>