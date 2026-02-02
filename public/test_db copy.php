<?php
// Test database connection
require_once '../config/config.php';
require_once '../app/core/Database.php';

echo "<h3>Testing Database Connection</h3>";

try {
    $database = new Database();
    $connection = $database->getConnection();
    
    echo "✅ Database connected successfully!<br>";
    
    // Test query
    $stmt = $connection->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    echo "Connected to database: <strong>" . $result['db_name'] . "</strong><br>";
    
    // Check if tables exist
    $tables = ['Users', 'Post', 'Comment','Vote'];
    foreach ($tables as $table) {
        $stmt = $connection->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing - run your SQL file<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>