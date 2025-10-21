<?php
session_start();
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/FriendModel.php';

class FeedController {
    private $postModel;
    private $groupModel;
    private $friendModel;
    
    public function __construct() {
        $this->postModel = new PostModel();
        $this->groupModel = new GroupModel();
        $this->friendModel = new FriendModel();
    }
    
    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header("Location: ../views/login.php");
            exit();
        }
        
        // Get user data from session
        $user_id = $_SESSION['user_id'];
        $first_name = $_SESSION['first_name'] ?? '';
        $last_name = $_SESSION['last_name'] ?? '';
        $username = $_SESSION['username'] ?? '';
        $profile_picture = $_SESSION['profile_picture'] ?? 'default.png';
        
        // Combine first and last name
        $user_full_name = trim($first_name . ' ' . $last_name);
        
        // Get posts for feed
        $posts = $this->postModel->getFeedPosts($user_id);

        // Get user's groups
        $groups = $this->groupModel->getUserGroups($user_id);

        // Get incoming friend requests for the sidebar card
        $friendRequests = $this->friendModel->getIncomingRequests($user_id);
        
        // Include the view and pass all data
        require __DIR__ . '/../views/myfeed.php';
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    (new FeedController())->index();
}
?>