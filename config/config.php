<?php
// config.php - Put your actual credentials here
define('DB_HOST', 'localhost');
define('DB_NAME', 'Hanthane');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your actual MySQL password
define('DB_CHARSET', 'utf8mb4');

// Dynamically set base path (e.g., '/newest')
define('BASE_PATH', dirname($_SERVER['SCRIPT_NAME']));
?>