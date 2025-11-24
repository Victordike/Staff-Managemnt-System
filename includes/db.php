<?php
require_once __DIR__ . '/../config/config.php';

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            // Connect to PostgreSQL database
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch(PDOException $e) {
            // Log detailed error for debugging
            error_log("Database Connection Error: " . $e->getMessage());
            error_log("DSN: pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME);
            die("Connection failed: Unable to connect to PostgreSQL database.\n\nError: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}
?>
