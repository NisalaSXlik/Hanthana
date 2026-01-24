<?php
    class HomeController {    
        public function index() {
            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                header("Location: " . BASE_PATH . "index.php?controller=Login&action=index");
                exit();
            }
            header("Location: " . BASE_PATH . "index.php?controller=Feed&action=index");
        }
    }
?>