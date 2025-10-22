<?php
// Show errors in development (REMOVE on production)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'Hanthana');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Base path - define once based on the project root
if (!defined('BASE_PATH')) {
    $projectRoot = realpath(__DIR__ . '/..');
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? $projectRoot);

    if ($projectRoot !== false && $documentRoot !== false && strpos($projectRoot, $documentRoot) === 0) {
        $relativePath = substr($projectRoot, strlen($documentRoot));
        $relativePath = '/' . trim(str_replace('\\', '/', $relativePath), '/') . '/';
        define('BASE_PATH', $relativePath === '//' ? '/' : $relativePath);
    } else {
        define('BASE_PATH', '/');
    }
}

// Debug: Log BASE_PATH (remove after testing)
error_log('BASE_PATH set to: ' . BASE_PATH);
?>