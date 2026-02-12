<?php
/**
 * Database Connection - Enhanced
 * NAS Dashboard v2.0
 */

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'nas_dashboard');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static $instance = null;
    private $connection = null;
    private $connected = false;

    private function __construct() {
        $this->connect();
    }

    private function connect() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->connected = true;
            
            error_log("Database connected successfully");
            
        } catch (PDOException $e) {
            $this->connected = false;
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Try to create database if it doesn't exist
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                $this->createDatabase();
            }
        }
    }

    private function createDatabase() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;charset=%s",
                DB_HOST,
                DB_PORT,
                DB_CHARSET
            );
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            error_log("Database created successfully");
            
            // Reconnect to new database
            $this->connect();
            
        } catch (PDOException $e) {
            error_log("Error creating database: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        if (!$this->connected) {
            $this->connect();
        }
        return $this->connection;
    }

    public function isConnected() {
        return $this->connected;
    }

    public function query($sql, $params = []) {
        try {
            if (!$this->connected) {
                throw new Exception("Database not connected");
            }
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : null;
    }

    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? true : false;
    }

    public function lastInsertId() {
        return $this->connected ? $this->connection->lastInsertId() : 0;
    }

    public function beginTransaction() {
        if ($this->connected) {
            return $this->connection->beginTransaction();
        }
        return false;
    }

    public function commit() {
        if ($this->connected) {
            return $this->connection->commit();
        }
        return false;
    }

    public function rollback() {
        if ($this->connected) {
            return $this->connection->rollBack();
        }
        return false;
    }
}

// Helper function
function getDB() {
    return Database::getInstance();
}

// Check database connection
function checkDatabaseConnection() {
    $db = getDB();
    if (!$db->isConnected()) {
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'database_connection_failed',
            'message' => 'فشل الاتصال بقاعدة البيانات. يرجى التحقق من إعدادات الاتصال.',
            'details' => [
                'host' => DB_HOST,
                'port' => DB_PORT,
                'database' => DB_NAME,
                'user' => DB_USER
            ]
        ]);
        exit;
    }
    return true;
}
?>
