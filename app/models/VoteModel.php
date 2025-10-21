<?php
require_once __DIR__ . '/../core/Database.php';

class VoteModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getConnection() {
        return $this->db->getConnection();
    }

    /**
     * Handle voting: Add, update, or remove a vote.
     * Returns ['action' => 'added'|'updated'|'removed']
     */
    public function vote(int $postId, int $userId, string $voteType): array {
        // Check existing vote
        $stmt = $this->getConnection()->prepare("SELECT vote_type FROM Vote WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $userId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ($existing['vote_type'] === $voteType) {
                // Same vote: Remove it
                $stmt = $this->getConnection()->prepare("DELETE FROM Vote WHERE post_id = ? AND user_id = ?");
                $stmt->execute([$postId, $userId]);
                return ['action' => 'removed'];
            } else {
                // Different vote: Update it
                $stmt = $this->getConnection()->prepare("UPDATE Vote SET vote_type = ?, updated_at = NOW() WHERE post_id = ? AND user_id = ?");
                $stmt->execute([$voteType, $postId, $userId]);
                return ['action' => 'updated'];
            }
        } else {
            // No vote: Add new
            $stmt = $this->getConnection()->prepare("INSERT INTO Vote (post_id, user_id, vote_type) VALUES (?, ?, ?)");
            $stmt->execute([$postId, $userId, $voteType]);
            return ['action' => 'added'];
        }
    }
}