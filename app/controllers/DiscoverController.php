<?php
require_once __DIR__ . '/../../config/config.php';

class DiscoverController {
    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_PATH . "index.php?controller=Login&action=index");
            exit();
        }
        
        require_once __DIR__ . '/../views/discover.php';
    }
    // ...other actions...
}

