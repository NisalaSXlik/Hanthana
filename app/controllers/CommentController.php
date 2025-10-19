<?php
session_start();
// Use project config and Database core to obtain a PDO connection
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../core/Database.php';

class CommentHandler {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Add a new comment or reply
    public function addComment($post_id, $commenter_id, $content, $parent_comment_id = null) {
        try {
            // If parent_comment_id is provided, check if it's a reply to a reply
            if ($parent_comment_id) {
                $parentComment = $this->getCommentById($parent_comment_id);
                if ($parentComment && $parentComment['parent_comment_id']) {
                    // It's a reply to a reply—redirect to main comment and add @mention
                    $mainCommentId = $parentComment['parent_comment_id'];
                    $parentUsername = $this->getUsernameByCommentId($parent_comment_id); // Helper to get username
                    $content = "@{$parentUsername} {$content}";
                    $parent_comment_id = $mainCommentId; // Set to main comment
                }
            }

            $stmt = $this->pdo->prepare("INSERT INTO Comment (post_id, commenter_id, content, parent_comment_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$post_id, $commenter_id, $content, $parent_comment_id]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // Handle error
            return false;
        }
    }

    // Helper: Get comment by ID
    private function getCommentById($comment_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM Comment WHERE comment_id = ?");
        $stmt->execute([$comment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Helper: Get username of commenter for a comment
    private function getUsernameByCommentId($comment_id) {
        $stmt = $this->pdo->prepare("
            SELECT u.username FROM Comment c
            JOIN Users u ON c.commenter_id = u.user_id
            WHERE c.comment_id = ?
        ");
        $stmt->execute([$comment_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['username'] ?? 'unknown';
    }
    
    // Get all comments for a post with user information (limit to one level)
    public function getCommentsByPost($post_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, u.username, u.profile_picture
                FROM Comment c
                JOIN Users u ON c.commenter_id = u.user_id
                WHERE c.post_id = ?
                ORDER BY c.created_at ASC
            ");
            $stmt->execute([$post_id]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Build flat structure: main comments with direct replies
            $mainComments = [];
            $replies = [];
            foreach ($comments as $comment) {
                if ($comment['parent_comment_id'] === null) {
                    $mainComments[] = $comment;
                } else {
                    $replies[$comment['parent_comment_id']][] = $comment;
                }
            }

            // Attach direct replies to main comments
            foreach ($mainComments as &$main) {
                $main['replies'] = $replies[$main['comment_id']] ?? [];
            }

            return $mainComments; // No deeper nesting
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get comment count for a post
    public function getCommentCount($post_id) {
        try {
            $sql = "SELECT COUNT(*) as count FROM Comment WHERE post_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$post_id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (PDOException $e) {
            error_log("Error counting comments: " . $e->getMessage());
            return 0;
        }
    }
    
    // Edit a comment
    public function editComment($comment_id, $user_id, $new_content) {
        try {
            $sql = "UPDATE Comment 
                    SET content = ?, is_edited = TRUE, edited_at = CURRENT_TIMESTAMP 
                    WHERE comment_id = ? AND commenter_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$new_content, $comment_id, $user_id]);
        } catch (PDOException $e) {
            error_log("Error editing comment: " . $e->getMessage());
            return false;
        }
    }
    
    // Delete a comment
    public function deleteComment($comment_id, $user_id) {
        try {
            $sql = "DELETE FROM Comment WHERE comment_id = ? AND commenter_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$comment_id, $user_id]);
        } catch (PDOException $e) {
            error_log("Error deleting comment: " . $e->getMessage());
            return false;
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $pdo = $db->getConnection();
    $commentHandler = new CommentHandler($pdo);
    
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Please login to comment';
        echo json_encode($response);
        exit;
    }
    
    switch ($action) {
        case 'add_comment':
            error_log('Add comment POST: ' . json_encode($_POST));
            $post_id = $_POST['post_id'];
            $content = trim($_POST['content']);
            $parent_comment_id = $_POST['parent_comment_id'] ?? null;
            
            if (empty($content)) {
                $response['message'] = 'Comment cannot be empty';
            } else {
                $comment_id = $commentHandler->addComment(
                    $post_id, 
                    $_SESSION['user_id'], 
                    $content, 
                    $parent_comment_id
                );
                
                if ($comment_id) {
                    $response['success'] = true;
                    $response['message'] = 'Comment added successfully';
                    $response['comment_id'] = $comment_id;
                } else {
                    $response['message'] = 'Failed to add comment';
                }
            }
            break;
            
        case 'get_comments':
            $post_id = $_POST['post_id'];
            $comments = $commentHandler->getCommentsByPost($post_id);
            $response['success'] = true;
            $response['comments'] = $comments;
            break;
            
        case 'edit_comment':
            $comment_id = $_POST['comment_id'];
            $content = trim($_POST['content']);
            
            if (empty($content)) {
                $response['message'] = 'Comment cannot be empty';
            } else {
                $success = $commentHandler->editComment($comment_id, $_SESSION['user_id'], $content);
                if ($success) {
                    $response['success'] = true;
                    $response['message'] = 'Comment updated successfully';
                } else {
                    $response['message'] = 'Failed to update comment';
                }
            }
            break;
            
        case 'delete_comment':
            $comment_id = $_POST['comment_id'];
            $success = $commentHandler->deleteComment($comment_id, $_SESSION['user_id']);
            if ($success) {
                $response['success'] = true;
                $response['message'] = 'Comment deleted successfully';
            } else {
                $response['message'] = 'Failed to delete comment';
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}
?>