<?php
// Database configuration
define('DB_HOST', 'db');
define('DB_NAME', 'hanthane_db');
define('DB_USER', 'root');
define('DB_PASS', 'rootpassword');
define('DB_CHARSET', 'utf8mb4');

// Timezone configuration
define('APP_TIMEZONE', 'Asia/Colombo'); // Change this to your timezone
date_default_timezone_set(APP_TIMEZONE);

// App base path (for portability, can be adjusted later)
define('BASE_PATH', '/');

// Google OAuth (set these in environment variables in production)
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: '');

// Mail defaults used by password reset emails
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@hanthana.local');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Hanthana');

// SMTP settings for email delivery (used when configured)
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_TIMEOUT', (int)(getenv('SMTP_TIMEOUT') ?: 10));
?>