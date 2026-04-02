<?php
require_once __DIR__ . '/../models/CommentModel.php';
require_once __DIR__ . '/../models/NotificationsModel.php';
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';

class CommentController {
    private $commentModel;
    private $notificationsModel;
    private $postModel;

    public function __construct() {
        $this->commentModel = new CommentModel();
        $this->notificationsModel = new NotificationsModel();
        $this->postModel = new PostModel();
    }

    public function handleAjax() {
        // Start output buffering to catch any unwanted output
        ob_start();
        
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            ob_clean(); // Clear any output
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            exit;
        }

        $subAction = $_POST['sub_action'] ?? '';
        
        switch ($subAction) {
            case 'load':
                $this->loadComments();
                break;
            case 'add':
                $this->addComment();
                break;
            case 'edit':
                $this->editComment();
                break;
            case 'delete':
                $this->deleteComment();
                break;
            default:
                ob_clean(); // Clear any output
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        ob_end_flush(); // Send the buffered output
        exit;
    }

    private function loadComments() {
        $postId = (int)($_POST['post_id'] ?? 0);
        if ($postId <= 0) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
            return;
        }

        $comments = $this->commentModel->getCommentsByPost($postId);
        
        foreach ($comments as &$comment) {
            $comment['profile_picture'] = MediaHelper::resolveMediaPath(
                $comment['profile_picture'] ?? '', 
                'uploads/user_dp/default_user_dp.jpg'
            );
            if (!empty($comment['replies'])) {
                foreach ($comment['replies'] as &$reply) {
                    $reply['profile_picture'] = MediaHelper::resolveMediaPath(
                        $reply['profile_picture'] ?? '', 
                        'uploads/user_dp/default_user_dp.jpg'
                    );
                }
            }
        }
        
        $postOwnerId = $this->commentModel->getPostOwnerId($postId);
        
        ob_clean(); // Clear any output before JSON
        echo json_encode([
            'success' => true,
            'comments' => $comments,
            'postOwnerId' => $postOwnerId,
            'currentUserId' => (int)$_SESSION['user_id']
        ]);
    }

    private function addComment() {
        $postId = (int)($_POST['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $parentId = isset($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null;
        $createdAt = $_POST['created_at'] ?? null; // Get client timestamp

        if ($postId <= 0 || empty($content)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            return;
        }

        try {
            $commentId = $this->commentModel->addComment($postId, $_SESSION['user_id'], $content, $parentId, $createdAt);
            
            // Check if comment was added (lastInsertId returns string, so check if it's not false and not empty)
            if ($commentId !== false && $commentId !== null) {
                $count = $this->commentModel->getCommentCount($postId);
                $this->notifyAboutNewComment($postId, (int)$_SESSION['user_id']);
                
                // Get user's profile picture
                $profilePicture = $this->commentModel->getUserProfilePicture($_SESSION['user_id']);
                $profilePicturePath = MediaHelper::resolveMediaPath(
                    $profilePicture ?? '', 
                    'uploads/user_dp/default_user_dp.jpg'
                );
                
                ob_clean(); // Clear any output before JSON
                echo json_encode([
                    'success' => true, 
                    'comment_id' => (int)$commentId, 
                    'comment_count' => $count,
                    'profile_picture' => $profilePicturePath,
                    'username' => $_SESSION['username'] ?? 'Unknown'
                ]);
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
            }
        } catch (Exception $e) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    private function editComment() {
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if ($commentId <= 0 || empty($content)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            return;
        }

        $result = $this->commentModel->editComment($commentId, $_SESSION['user_id'], $content);
        ob_clean(); // Clear any output before JSON
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Comment updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update comment']);
        }
    }

    private function deleteComment() {
        $commentId = (int)($_POST['comment_id'] ?? 0);
        if ($commentId <= 0) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid comment ID']);
            return;
        }

        $softDeleted = false;
        $result = $this->commentModel->deleteComment($commentId, $_SESSION['user_id'], $softDeleted);
        
        if ($result) {
            // FIXED: Use the model's connection method
            $stmt = $this->commentModel->getConnection()->prepare("SELECT post_id FROM Comment WHERE comment_id = ?");
            $stmt->execute([$commentId]);
            $postId = $stmt->fetchColumn();
            
            ob_clean(); // Clear any output before JSON
            if ($postId) {
                $count = $this->commentModel->getCommentCount($postId);
                echo json_encode(['success' => true, 'soft_deleted' => $softDeleted, 'comment_count' => $count]);
            } else {
                echo json_encode(['success' => true, 'soft_deleted' => $softDeleted]);
            }
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
        }
    }

    private function notifyAboutNewComment(int $postId, int $commenterId): void {
        $postOwnerId = $this->commentModel->getPostOwnerId($postId);
        if (!$postOwnerId || $postOwnerId === $commenterId) {
            return;
        }

        $commenterName = $this->getCurrentUserDisplayName();
        $message = $commenterName . ' commented on your post.';
        $actionUrl = $this->buildPostActionUrl($postId, true);
        $this->notificationsModel->createNotification(
            $postOwnerId,
            $commenterId,
            'post_comment',
            'New comment on your post',
            $message,
            $actionUrl,
            'medium'
        );
    }

    private function getCurrentUserDisplayName(): string {
        $first = trim($_SESSION['first_name'] ?? '');
        $last = trim($_SESSION['last_name'] ?? '');
        $full = trim($first . ' ' . $last);
        if ($full !== '') {
            return $full;
        }
        return $_SESSION['username'] ?? 'Someone';
    }

    private function buildPostActionUrl(int $postId, bool $jumpToComments = false): ?string {
        $post = $this->postModel->getPostById($postId);
        if (!$post) {
            return null;
        }

        $authorId = (int)($post['user_id'] ?? $post['author_id'] ?? 0);
        $groupId = isset($post['group_id']) ? (int)$post['group_id'] : 0;

        if ($groupId > 0) {
            $anchor = $jumpToComments ? '#comments-post-' . $postId : '#post-' . $postId;
            return $this->getBasePath() . 'index.php?controller=Group&action=index&group_id=' . $groupId . $anchor;
        }

        if ($authorId <= 0) {
            return null;
        }

        $anchor = $jumpToComments
            ? '#personal-post-' . $postId . '-comments'
            : '#personal-post-' . $postId;

        return $this->getBasePath() . 'index.php?controller=Profile&action=view&user_id=' . $authorId . $anchor;
    }

    private function getBasePath(): string {
        return defined('BASE_PATH') ? BASE_PATH : '/';
    }
}
?>