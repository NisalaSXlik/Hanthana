<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/FriendModel.php';

use Throwable;

class FriendController
{
    private FriendModel $friendModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->friendModel = new FriendModel();
    }

    public function sendRequest(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            return;
        }

        $viewerId = (int)$_SESSION['user_id'];
        $targetId = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;

        if ($targetId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid target user.']);
            return;
        }

        if ($targetId === $viewerId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You cannot send a friend request to yourself.']);
            return;
        }

        try {
            $result = $this->friendModel->sendFriendRequest($viewerId, $targetId);
            echo json_encode([
                'success' => true,
                'state' => $result['status'],
                'message' => $result['message'],
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to send friend request.']);
        }
    }

    public function acceptRequest(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            return;
        }

        $friendshipId = isset($_POST['friendship_id']) ? (int)$_POST['friendship_id'] : 0;
        if ($friendshipId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid friend request.']);
            return;
        }

        try {
            $result = $this->friendModel->acceptFriendRequest($friendshipId, (int)$_SESSION['user_id']);
            $friendCount = (array_key_exists('friend_count', $result) && $result['friend_count'] !== null)
                ? (int)$result['friend_count']
                : null;
            $friendCountOther = (array_key_exists('friend_count_other', $result) && $result['friend_count_other'] !== null)
                ? (int)$result['friend_count_other']
                : null;

            echo json_encode([
                'success' => true,
                'status' => $result['status'] ?? 'accepted',
                'message' => $result['message'] ?? 'Friend request accepted.',
                'friend_count' => $friendCount,
                'friend_count_other' => $friendCountOther,
            ]);
        } catch (\RuntimeException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to accept friend request.']);
        }
    }

    public function declineRequest(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            return;
        }

        $friendshipId = isset($_POST['friendship_id']) ? (int)$_POST['friendship_id'] : 0;
        if ($friendshipId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid friend request.']);
            return;
        }

        try {
            $result = $this->friendModel->declineFriendRequest($friendshipId, (int)$_SESSION['user_id']);
            echo json_encode([
                'success' => true,
                'status' => $result['status'] ?? 'declined',
                'message' => $result['message'] ?? 'Friend request declined.',
            ]);
        } catch (\RuntimeException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to decline friend request.']);
        }
    }

    public function removeFriend(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            return;
        }

        $viewerId = (int)$_SESSION['user_id'];
        $targetId = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
        if ($targetId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid target user.']);
            return;
        }

        try {
            $result = $this->friendModel->removeFriendship($viewerId, $targetId);
            $friendCount = isset($result['friend_count']) ? (int)$result['friend_count'] : null;
            $friendCountOther = isset($result['friend_count_other']) ? (int)$result['friend_count_other'] : null;
            echo json_encode([
                'success' => true,
                'status' => $result['status'] ?? 'removed',
                'message' => $result['message'] ?? 'Friend removed successfully.',
                'friend_count' => $friendCount,
                'friend_count_other' => $friendCountOther,
            ]);
        } catch (\RuntimeException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to remove friend.']);
        }
    }
}
