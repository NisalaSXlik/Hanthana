<?php
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
                header("Location: " . BASE_PATH . "index.php?controller=Landing&action=index");
                exit;
            }
            
            // Load data
            $posts = $this->postModel->getFeedPosts($_SESSION['user_id'], true);
            $groups = $this->groupModel->getUserGroups($_SESSION['user_id']);
            $friendRequests = $this->friendModel->getIncomingRequests($_SESSION['user_id']);
            
            // Pass to view
            require __DIR__ . '/../views/myFeed.php';
        }
    }
?>