<?php
session_start();

require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../core/Database.php';

header('Content-Type: application/json');

// Handle GET requests for ajax_action
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_action'])) {
    $action = $_GET['ajax_action'];
    $postModel = new PostModel();
    $userId = $_SESSION['user_id'] ?? 0;

    try {
        switch ($action) {
            case 'getPost':
                $postId = (int)($_GET['post_id'] ?? 0);
                if ($postId <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Missing post id']);
                    exit;
                }
                $post = $postModel->getById($postId, $userId);
                if ($post) {
                    echo json_encode(['success' => true, 'post' => $post]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Post not found']);
                }
                exit;

            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
                exit;
        }
    } catch (Throwable $e) {
        error_log('PostController GET error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$action = $_POST['action'] ?? '';
$postModel = new PostModel();
$userId = (int) $_SESSION['user_id'];

try {
    switch ($action) {
        case 'update_post':
            $postId = (int)($_POST['post_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            if ($postId <= 0 || $content === '') {
                echo json_encode(['success' => false, 'message' => 'Missing data']);
                exit;
            }
            $ok = $postModel->updatePostContent($postId, $userId, $content);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Post updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed or not allowed']);
            }
            exit;

        case 'delete_post':
            $postId = (int)($_POST['post_id'] ?? 0);
            if ($postId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Missing post id']);
                exit;
            }
            $ok = $postModel->deletePost($postId, $userId);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Post deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Delete failed or not allowed']);
            }
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            exit;
    }
} catch (Throwable $e) {
    error_log('PostController error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
