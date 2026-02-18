<?php
// Database configuration
define('DB_HOST', 'db');
define('DB_NAME', 'hanthana_db');
define('DB_USER', 'root');
define('DB_PASS', 'rootpassword');
define('DB_CHARSET', 'utf8mb4');

// Timezone configuration
define('APP_TIMEZONE', 'Asia/Colombo'); // Change this to your timezone
date_default_timezone_set(APP_TIMEZONE);

// App base path (for portability, can be adjusted later)
define('BASE_PATH', '/');
?>