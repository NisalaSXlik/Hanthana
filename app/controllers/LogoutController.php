<?php
// LogoutController.php
require_once __DIR__ . '/../../config/config.php';

class LogoutController {
    public function index() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy the session
        session_destroy();
        
        // Clear any session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Redirect to login page using BASE_PATH
        header("Location: " . BASE_PATH . "index.php?controller=Login&action=index");
        exit();
    }
}

// Support direct access (backward compatibility)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $controller = new LogoutController();
    $controller->index();
}
?>