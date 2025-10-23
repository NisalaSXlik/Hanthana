<?php
require_once 'Database.php';

class CommentModel {
    private $db;
    public function __construct() {
        $this->db = (new Database())->pdo;
    }

    public function addComment($post_id, $commenter_id, $content, $parent_comment_id = null) {
        $stmt = $this->db->prepare("INSERT INTO Comment (post_id, commenter_id, content, parent_comment_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$post_id, $commenter_id, $content, $parent_comment_id]);
        return $this->db->lastInsertId();
    }
}
?>