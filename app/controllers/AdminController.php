<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/FriendModel.php';
require_once __DIR__ . '/../models/SettingsModel.php';
require_once __DIR__ . '/../models/ReportModel.php';
require_once __DIR__ . '/../../config/config.php';

class AdminController {
    private UserModel $userModel;
    private PostModel $postModel;
    private GroupModel $groupModel;
    private FriendModel $friendModel;
    private SettingsModel $settingsModel;
    private ReportModel $reportModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit;
        }

        if (($_SESSION['role'] ?? 'user') !== 'admin') {
            http_response_code(403);
            echo 'Access denied';
            exit;
        }

        $this->userModel = new UserModel();
        $this->postModel = new PostModel();
        $this->groupModel = new GroupModel();
        $this->friendModel = new FriendModel();
        $this->settingsModel = new SettingsModel();
        $this->reportModel = new ReportModel();
    }

    public function index() {
        $userStats = $this->userModel->getStats();
        $recentUsers = $this->userModel->getRecentUsers(6);
        $dailyActiveUsers = $this->userModel->getDailyActiveUsers(7);
        $postStats = $this->postModel->getStats();
        $groupStats = $this->groupModel->getStats();
        $friendStats = $this->friendModel->getStats();
        $recentGroups = $this->groupModel->getRecentGroups(5);
        $trendingGroups = $this->groupModel->getTrendingGroups(4);
        $groupReviewSnapshot = $this->groupModel->getReviewSnapshot();
        $trendingPosts = $this->postModel->getTrendingPosts(5, (int)$_SESSION['user_id']);
        $moderationSnapshot = $this->postModel->getModerationSnapshot();
        $complaintStats = $this->reportModel->getComplaintStats(7);
        $recentComplaints = $this->reportModel->getRecentComplaints(6);
        $complaintsByStatus = [
            'received' => $this->reportModel->getComplaintsByStatus('received', 15),
            'pending' => $this->reportModel->getComplaintsByStatus('pending', 15),
            'resolved' => $this->reportModel->getComplaintsByStatus('resolved', 15)
        ];
        $settingsSummary = $this->settingsModel->getSettingsSummary();

        require __DIR__ . '/../views/admin/dashboard.php';
    }

    public function banUser() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $duration = trim($_POST['duration'] ?? '');
        $customUntil = trim($_POST['custom_until'] ?? '');
        $reason = trim($_POST['reason'] ?? 'Policy violation');
        $notes = trim($_POST['notes'] ?? '');

        if ($userId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid user id']);
            return;
        }

        if ($userId === (int)$_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You cannot ban yourself']);
            return;
        }

        $banUntil = $this->resolveBanUntil($duration, $customUntil);

        if (!$banUntil) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid duration']);
            return;
        }

        $banUntilFormatted = $banUntil->format('Y-m-d H:i:s');
        $result = $this->userModel->banUser($userId, $banUntilFormatted, $reason, (int)$_SESSION['user_id'], $notes);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'User banned until ' . $banUntil->format('M d, Y H:i')
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to ban user']);
        }
    }

    public function previewPost() {
        header('Content-Type: application/json');
        $postId = (int)($_GET['post_id'] ?? $_POST['post_id'] ?? 0);

        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing post id']);
            return;
        }

        $post = $this->postModel->getPostById($postId);
        if ($post) {
            echo json_encode(['success' => true, 'post' => $post]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Post not found']);
        }
    }

    public function removePost() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $postId = (int)($_POST['post_id'] ?? 0);
        $reportId = (int)($_POST['report_id'] ?? 0);

        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid post id']);
            return;
        }

        $success = $this->postModel->removePostByAdmin($postId);
        if ($success) {
            if ($reportId > 0) {
                $this->reportModel->resolveReport($reportId, (int)$_SESSION['user_id']);
            }
            echo json_encode(['success' => true, 'message' => 'Post removed successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to remove post.']);
        }
    }

    public function disableGroup() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $groupId = (int)($_POST['group_id'] ?? 0);
        $reportId = (int)($_POST['report_id'] ?? 0);
        $duration = trim($_POST['duration'] ?? '24h');
        $customUntil = trim($_POST['custom_until'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($groupId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid group id']);
            return;
        }

        if ($reason === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Reason is required']);
            return;
        }

        $disableUntil = $this->resolveBanUntil($duration, $customUntil ?: null);
        if (!$disableUntil) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Select a valid duration']);
            return;
        }

        $success = $this->groupModel->disableGroup($groupId);
        if ($success) {
            if ($reportId > 0) {
                $this->reportModel->resolveReport($reportId, (int)$_SESSION['user_id']);
            }
            $durationLabels = [
                '24h' => '24 hours',
                '72h' => '3 days',
                '1w' => '1 week',
                '2w' => '2 weeks',
                '1m' => '1 month',
                'custom' => $disableUntil->format('M d, Y H:i')
            ];
            $durationLabel = $durationLabels[$duration] ?? $disableUntil->format('M d, Y H:i');
            $message = sprintf('Group disabled. Duration logged: %s. Reason: %s', $durationLabel, $reason);
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to disable group.']);
        }
    }

    public function markReportReviewed() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        if ($reportId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid report id']);
            return;
        }

        $adminId = (int)$_SESSION['user_id'];
        $result = $this->reportModel->markReviewed($reportId, $adminId);
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to update complaint']);
        }
    }

    public function complaintsBoardData() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $complaintStats = $this->reportModel->getComplaintStats(7);
        $recentComplaints = $this->reportModel->getRecentComplaints(6);
        $complaintsByStatus = [
            'received' => $this->reportModel->getComplaintsByStatus('received', 15),
            'pending' => $this->reportModel->getComplaintsByStatus('pending', 15),
            'resolved' => $this->reportModel->getComplaintsByStatus('resolved', 15)
        ];

        echo json_encode([
            'success' => true,
            'stats' => $complaintStats,
            'recent' => $recentComplaints,
            'byStatus' => $complaintsByStatus
        ]);
    }

    private function resolveBanUntil(string $duration, ?string $customUntil): ?DateTime {
        $duration = strtolower($duration);
        $now = new DateTime();

        $map = [
            '24h' => 'PT24H',
            '72h' => 'PT72H',
            '1w' => 'P7D',
            '2w' => 'P14D',
            '1m' => 'P1M'
        ];

        if (isset($map[$duration])) {
            try {
                $now->add(new DateInterval($map[$duration]));
                return $now;
            } catch (Exception $e) {
                return null;
            }
        }

        if ($duration === 'custom' && !empty($customUntil)) {
            try {
                $customDate = new DateTime($customUntil);
                if ($customDate <= new DateTime()) {
                    return null;
                }
                return $customDate;
            } catch (Exception $e) {
                return null;
            }
        }

        return null;
    }
}
?>
