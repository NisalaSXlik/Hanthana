<?php
require_once __DIR__ . '/../core/Database.php';

class CommentModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getCommentsByPost($postId) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.username, u.profile_picture
            FROM Comment c
            JOIN Users u ON c.commenter_id = u.user_id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$postId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mainComments = [];
        $replies = [];
        foreach ($comments as $comment) {
            if ($comment['parent_comment_id'] === null) {
                $mainComments[] = $comment;
            } else {
                $replies[$comment['parent_comment_id']][] = $comment;
            }
        }

        foreach ($mainComments as &$main) {
            $main['replies'] = $replies[$main['comment_id']] ?? [];
        }

        return $mainComments;
    }

    public function addComment($postId, $userId, $content, $parentId = null, $createdAt = null) {
        try {
            // Use provided timestamp or let database use CURRENT_TIMESTAMP
            if ($createdAt) {
                $stmt = $this->db->prepare("INSERT INTO Comment (post_id, commenter_id, content, parent_comment_id, created_at) VALUES (?, ?, ?, ?, ?)");
                $result = $stmt->execute([$postId, $userId, $content, $parentId, $createdAt]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO Comment (post_id, commenter_id, content, parent_comment_id) VALUES (?, ?, ?, ?)");
                $result = $stmt->execute([$postId, $userId, $content, $parentId]);
            }
            
            if ($result) {
                $lastId = $this->db->lastInsertId();
                // Adjust comment count after getting the ID
                $this->adjustCommentCount($postId, 1);
                // Return the ID as integer (lastInsertId returns string)
                return $lastId ? (int)$lastId : false;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            error_log("PDO error in addComment: " . $e->getMessage());
            return false;
        }
    }

    public function editComment($commentId, $userId, $content) {
        if (!$this->canUserModerateComment($commentId, $userId)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE Comment SET content = ?, is_edited = 1, edited_at = NOW() WHERE comment_id = ?");
        return $stmt->execute([$content, $commentId]);
    }

    public function deleteComment($commentId, $userId, &$softDeleted = false) {
        if (!$this->canUserModerateComment($commentId, $userId)) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT post_id FROM Comment WHERE comment_id = ?");
        $stmt->execute([$commentId]);
        $postId = $stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Comment WHERE parent_comment_id = ?");
        $stmt->execute([$commentId]);
        $hasReplies = (int)$stmt->fetchColumn() > 0;

        if ($hasReplies) {
            $softDeleted = true;
            $stmt = $this->db->prepare("UPDATE Comment SET content = '[deleted]', is_deleted = 1 WHERE comment_id = ?");
            $result = $stmt->execute([$commentId]);
        } else {
            $softDeleted = false;
            $stmt = $this->db->prepare("DELETE FROM Comment WHERE comment_id = ?");
            $result = $stmt->execute([$commentId]);
            if ($postId) {
                $this->adjustCommentCount($postId, -1);
            }
        }
        return $result;
    }

    public function getCommentCount($postId) {
        $stmt = $this->db->prepare("SELECT comment_count FROM Post WHERE post_id = ?");
        $stmt->execute([$postId]);
        return (int)$stmt->fetchColumn();
    }

    public function getPostOwnerId($postId) {
        $stmt = $this->db->prepare("SELECT author_id FROM Post WHERE post_id = ?");
        $stmt->execute([$postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['author_id'] : null;
    }

    private function canUserModerateComment($commentId, $userId) {
        $stmt = $this->db->prepare("SELECT commenter_id, post_id FROM Comment WHERE comment_id = ?");
        $stmt->execute([$commentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        if ((int)$row['commenter_id'] === (int)$userId) return true;

        $stmt = $this->db->prepare("SELECT author_id FROM Post WHERE post_id = ?");
        $stmt->execute([$row['post_id']]); // FIXED: Added missing execute
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($post && (int)$post['author_id'] === (int)$userId) return true;
        
        return false;
    }

    private function adjustCommentCount($postId, $delta) {
        try {
            $stmt = $this->db->prepare("UPDATE Post SET comment_count = GREATEST(0, comment_count + ?) WHERE post_id = ?");
            $stmt->execute([$delta, $postId]);
        } catch (PDOException $e) {
            error_log("PDO error in adjustCommentCount: " . $e->getMessage());
        }
    }

    public function getUserProfilePicture($userId) {
        $stmt = $this->db->prepare("SELECT profile_picture FROM Users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['profile_picture'] : null;
    }

    // FIXED: Added missing method
    public function getConnection() {
        return $this->db;
    }
}
?>