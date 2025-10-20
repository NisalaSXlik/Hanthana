<?php
// Add this line to load the config file
require_once __DIR__ . '/../../config/config.php';

class Database {
    private $connection;
    
    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER, 
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
}
?>