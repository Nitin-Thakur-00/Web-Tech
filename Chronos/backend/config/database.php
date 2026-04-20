<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        // Most robust lookup: getenv and $_SERVER first (avoids GPCS/$_ENV issues)
        $host = getenv('DB_HOST') ?: ($_SERVER['DB_HOST'] ?? '127.0.0.1');
        $db   = getenv('DB_NAME') ?: ($_SERVER['DB_NAME'] ?? 'chronos_v8');
        $user = getenv('DB_USER') ?: ($_SERVER['DB_USER'] ?? 'root');
        $pass = getenv('DB_PASS') ?: ($_SERVER['DB_PASS'] ?? '');
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            exit(json_encode(['success' => false, 'error' => 'Internal server error']));
        }
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
