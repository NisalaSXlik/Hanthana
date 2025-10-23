<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/PostModel.php';

class PopularController {
    private $groupModel;
    private $postModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->groupModel = new GroupModel();
        $this->postModel = new PostModel();
    }

    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit();
        }

        require_once __DIR__ . '/../views/popular.php';
    }

    /**
     * Get popular groups via AJAX
     */
    public function getPopularGroups() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        try {
            $userId = $_SESSION['user_id'];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
            
            // Get popular groups (sorted by member count)
            $groups = $this->groupModel->getPopularGroups($limit, $userId);
            
            echo json_encode([
                'success' => true,
                'groups' => $groups
            ]);
        } catch (Exception $e) {
            error_log('Get popular groups error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error loading popular groups'
            ]);
        }
        exit;
    }

    /**
     * Get trending posts via AJAX
     */
    public function getTrendingPosts() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        try {
            $userId = $_SESSION['user_id'];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            // Get trending posts (high engagement)
            $posts = $this->postModel->getTrendingPosts($limit, $userId);
            
            echo json_encode([
                'success' => true,
                'posts' => $posts
            ]);
        } catch (Exception $e) {
            error_log('Get trending posts error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error loading trending posts'
            ]);
        }
        exit;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_action'])) {
    $controller = new PopularController();
    $action = $_GET['ajax_action'];
    
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}
