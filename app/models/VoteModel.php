<?php
require_once __DIR__ . '/../core/Database.php';

class VoteModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function vote($postId, $userId, $voteType) {
        try {
            // Check existing vote
            $stmt = $this->db->prepare("SELECT vote_type FROM Vote WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$postId, $userId]);
            $existingVote = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingVote) {
                if ($existingVote['vote_type'] === $voteType) {
                    // Remove vote
                    $stmt = $this->db->prepare("DELETE FROM Vote WHERE post_id = ? AND user_id = ?");
                    $stmt->execute([$postId, $userId]);
                    $this->adjustVoteCount($postId, $voteType, -1);
                    return ['action' => 'removed'];
                } else {
                    // Change vote
                    $stmt = $this->db->prepare("UPDATE Vote SET vote_type = ?, updated_at = NOW() WHERE post_id = ? AND user_id = ?");
                    $stmt->execute([$voteType, $postId, $userId]);
                    $this->adjustVoteCount($postId, $existingVote['vote_type'], -1);
                    $this->adjustVoteCount($postId, $voteType, 1);
                    return ['action' => 'changed'];
                }
            } else {
                // Add new vote
                $stmt = $this->db->prepare("INSERT INTO Vote (post_id, user_id, vote_type) VALUES (?, ?, ?)");
                $stmt->execute([$postId, $userId, $voteType]);
                $this->adjustVoteCount($postId, $voteType, 1);
                return ['action' => 'added'];
            }
        } catch (PDOException $e) {
            error_log('Vote error: ' . $e->getMessage());
            return false;
        }
    }

    private function adjustVoteCount($postId, $voteType, $delta) {
        $column = $voteType === 'upvote' ? 'upvote_count' : 'downvote_count';
        $stmt = $this->db->prepare("UPDATE Post SET $column = GREATEST(0, $column + ?) WHERE post_id = ?");
        $stmt->execute([$delta, $postId]);
    }

    public function getVoteCounts($postId) {
        $stmt = $this->db->prepare("SELECT upvote_count, downvote_count FROM Post WHERE post_id = ?");
        $stmt->execute([$postId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPostAuthorId(int $postId): ?int {
        $stmt = $this->db->prepare("SELECT author_id FROM Post WHERE post_id = ? LIMIT 1");
        $stmt->execute([$postId]);
        $authorId = $stmt->fetchColumn();
        return $authorId !== false ? (int)$authorId : null;
    }
}