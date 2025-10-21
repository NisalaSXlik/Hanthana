<?php
require_once __DIR__ . '/../models/FriendModel.php';

class SettingsController {
    private FriendModel $friendModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->friendModel = new FriendModel();
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit();
        }

        $friendRequests = $this->friendModel->getIncomingRequests((int)$_SESSION['user_id']);
        require_once __DIR__ . '/../views/settings.php';
    }
    // ...other actions...
}

