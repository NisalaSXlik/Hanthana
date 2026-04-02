<?php
require_once __DIR__ . '/../models/VoteModel.php';
require_once __DIR__ . '/../models/NotificationsModel.php';
require_once __DIR__ . '/../models/PostModel.php';

class VoteController {
    private $voteModel;
    private $notificationsModel;
    private $postModel;

    public function __construct() {
        $this->voteModel = new VoteModel();
        $this->notificationsModel = new NotificationsModel();
        $this->postModel = new PostModel();
    }

    public function handleAjax() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            exit;
        }

        $subAction = $_POST['sub_action'] ?? '';
        
        if ($subAction === 'vote') {
            $this->vote();
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        exit;
    }

    private function vote() {
        $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $voteType = $_POST['vote_type'] ?? '';
        $userId = (int)$_SESSION['user_id'];

        if ($postId <= 0 || !in_array($voteType, ['upvote', 'downvote'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            return;
        }

        $result = $this->voteModel->vote($postId, $userId, $voteType);

        if ($result) {
            $counts = $this->voteModel->getVoteCounts($postId);
            $this->maybeSendLikeNotification($postId, $userId, $voteType, $result['action'] ?? '');
            echo json_encode([
                'success' => true,
                'action' => $result['action'],
                'upvote_count' => (int)$counts['upvote_count'],
                'downvote_count' => (int)$counts['downvote_count']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Vote failed']);
        }
    }

    private function maybeSendLikeNotification(int $postId, int $likerId, string $voteType, string $action): void {
        if ($voteType !== 'upvote' || !in_array($action, ['added', 'changed'], true)) {
            return;
        }

        $postOwnerId = $this->voteModel->getPostAuthorId($postId);
        if (!$postOwnerId || $postOwnerId === $likerId) {
            return;
        }

        $likerName = $this->getCurrentUserDisplayName();
        $message = $likerName . ' liked your post.';
        $actionUrl = $this->buildPostActionUrl($postId);
        $this->notificationsModel->createNotification(
            $postOwnerId,
            $likerId,
            'post_upvote',
            'New like on your post',
            $message,
            $actionUrl,
            'low'
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