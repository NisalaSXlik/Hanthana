<?php
// Load DB config and Database core to obtain a PDO connection
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/AuthController.php';
$db = new Database();
$pdo = $db->getConnection();

class SignupController {
    public function index() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController = new AuthController();
            $result = $authController->register($_POST);
            if ($result['success']) {
                header("Location: " . BASE_PATH . "index.php?controller=Home&action=index");
                exit;
            } else {
                $_SESSION['signup_errors'] = $result['errors'];
                header("Location: " . BASE_PATH . "index.php?controller=Signup&action=index");
                exit;
            }
        } else {
            require_once __DIR__ . '/../views/signup.php';
        }
    }
}