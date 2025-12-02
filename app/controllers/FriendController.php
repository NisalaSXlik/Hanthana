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
        // Clean any output buffer to prevent corruption
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            exit;
        }

        $viewerId = (int)$_SESSION['user_id'];
        $targetId = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;

        if ($targetId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid target user.']);
            exit;
        }

        if ($targetId === $viewerId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You cannot send a friend request to yourself.']);
            exit;
        }

        try {
            $result = $this->friendModel->sendFriendRequest($viewerId, $targetId);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'state' => $result['status'],
                'message' => $result['message'],
            ]);
        } catch (Throwable $e) {
            error_log("Friend request error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to send friend request. Please try again.']);
        }
        exit;
    }

    public function acceptRequest(): void
    {
        // Clean any output buffer to prevent corruption
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            exit;
        }

        $friendshipId = isset($_POST['friendship_id']) ? (int)$_POST['friendship_id'] : 0;
        if ($friendshipId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid friend request.']);
            exit;
        }

        try {
            $result = $this->friendModel->acceptFriendRequest($friendshipId, (int)$_SESSION['user_id']);
            $friendCount = (array_key_exists('friend_count', $result) && $result['friend_count'] !== null)
                ? (int)$result['friend_count']
                : null;
            $friendCountOther = (array_key_exists('friend_count_other', $result) && $result['friend_count_other'] !== null)
                ? (int)$result['friend_count_other']
                : null;

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'status' => $result['status'] ?? 'accepted',
                'message' => $result['message'] ?? 'Friend request accepted.',
                'friend_count' => $friendCount,
                'friend_count_other' => $friendCountOther,
            ]);
        } catch (\RuntimeException $e) {
            error_log("Accept request error: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            error_log("Accept request error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to accept friend request. Please try again.']);
        }
        exit;
    }

    public function declineRequest(): void
    {
        // Clean any output buffer to prevent corruption
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            exit;
        }

        $friendshipId = isset($_POST['friendship_id']) ? (int)$_POST['friendship_id'] : 0;
        if ($friendshipId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid friend request.']);
            exit;
        }

        try {
            $result = $this->friendModel->declineFriendRequest($friendshipId, (int)$_SESSION['user_id']);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'status' => $result['status'] ?? 'declined',
                'message' => $result['message'] ?? 'Friend request declined.',
            ]);
        } catch (\RuntimeException $e) {
            error_log("Decline request error: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            error_log("Decline request error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to decline friend request. Please try again.']);
        }
        exit;
    }

    public function removeFriend(): void
    {
        // Clean any output buffer to prevent corruption
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            exit;
        }

        $viewerId = (int)$_SESSION['user_id'];
        $targetId = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
        if ($targetId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid target user.']);
            exit;
        }

        try {
            $result = $this->friendModel->removeFriendship($viewerId, $targetId);
            $friendCount = isset($result['friend_count']) ? (int)$result['friend_count'] : null;
            $friendCountOther = isset($result['friend_count_other']) ? (int)$result['friend_count_other'] : null;
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'status' => $result['status'] ?? 'removed',
                'message' => $result['message'] ?? 'Friend removed successfully.',
                'friend_count' => $friendCount,
                'friend_count_other' => $friendCountOther,
            ]);
        } catch (\RuntimeException $e) {
            error_log("Remove friend error: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            error_log("Remove friend error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to remove friend. Please try again.']);
        }
        exit;
    }
}
