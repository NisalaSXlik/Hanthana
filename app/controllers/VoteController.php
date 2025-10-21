<?php
session_start();
require_once __DIR__ . '/../models/VoteModel.php';

class VoteController {
    public function vote() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }

        $postId = (int)($_POST['post_id'] ?? 0);
        $voteType = $_POST['vote_type'] ?? '';

        if ($postId <= 0 || !in_array($voteType, ['upvote', 'downvote'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            return;
        }

        $model = new VoteModel();
        $result = $model->vote($postId, $_SESSION['user_id'], $voteType);

        echo json_encode(['success' => true, 'action' => $result['action']]);
    }
}