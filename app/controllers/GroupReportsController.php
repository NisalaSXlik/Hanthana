<?php

class GroupReportsController extends BaseController
{
    private GroupModel $groupModel;
    private UserModel $userModel;
    private ReportModel $reportModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->groupModel = new GroupModel();
        $this->userModel = new UserModel();
        $this->reportModel = new ReportModel();
    }

    public function index()
    {
        $groupId = $this->resolveGroupId();

        if ($groupId <= 0) {
            $this->redirect('Feed');
        }

        $group = $this->groupModel->getById($groupId);
        if (!$group) {
            $this->redirect('Feed');
        }

        $_SESSION['current_group_id'] = $groupId;

        $currentUserId = (int)$_SESSION['user_id'];
        $currentUser = $this->userModel->findById($currentUserId);
        $isAdmin = $this->groupModel->isGroupAdmin($groupId, $currentUserId);
        $canModerateFileBank = $isAdmin;
        $membershipState = $this->groupModel->getUserMembershipState($groupId, $currentUserId);
        $isJoined = ($membershipState === 'active');

        if (!$isAdmin) {
            $this->redirect('GroupProfileView');
        }

        $reportsByTab = [
            'content' => [],
            'filebank' => [],
            'messages' => [],
            'channels' => [],
        ];

        $reportRows = $this->reportModel->getGroupModerationReports($groupId, 250);
        foreach ($reportRows as $row) {
            $bucket = $this->resolveBucket((string)($row['target_type'] ?? ''));
            $row['target_url'] = $this->buildTargetUrl($row, $groupId);
            $row['context_label'] = !empty($row['group_id']) ? ('Group #' . (int)$row['group_id']) : 'System';
            $row['target_label'] = ucfirst(str_replace('_', ' ', (string)($row['target_type'] ?? 'target')));
            $row['owner_label'] = $row['owner_username'] ?? 'N/A';
            $row['action_taken_label'] = $this->resolveActionLabel((string)($row['target_type'] ?? ''));
            $reportsByTab[$bucket][] = $row;
        }

        require_once __DIR__ . '/../views/groupmoderation.php';
    }

    public function markReviewed()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $groupId = $this->resolveGroupId();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$this->canModerateGroup($groupId, $userId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        $report = $this->reportModel->getReportById($reportId);
        if (!$report || !$this->isReportInScope($report, $groupId)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Report not found']);
            return;
        }

        $updated = $this->reportModel->markReviewed($reportId, $userId);
        if (!$updated) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to mark reviewed']);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Report marked as reviewed']);
    }

    public function resolveReport()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $groupId = $this->resolveGroupId();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$this->canModerateGroup($groupId, $userId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        $report = $this->reportModel->getReportById($reportId);
        if (!$report || !$this->isReportInScope($report, $groupId)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Report not found']);
            return;
        }

        $updated = $this->reportModel->resolveReport($reportId, $userId);
        if (!$updated) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to resolve report']);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Report resolved successfully']);
    }

    public function updateReport()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $groupId = $this->resolveGroupId();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$this->canModerateGroup($groupId, $userId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        $report = $this->reportModel->getReportById($reportId);
        if (!$report || !$this->isReportInScope($report, $groupId)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Report not found']);
            return;
        }

        $updated = $this->reportModel->updateModerationReport($reportId, $userId, [
            'status' => $_POST['status'] ?? null,
            'action_taken' => $_POST['action_taken'] ?? null,
            'description' => $_POST['description'] ?? null,
            'reviewer_note' => $_POST['reviewer_note'] ?? null,
        ]);

        if (!$updated) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to update report']);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Report updated successfully']);
    }

    private function canModerateGroup(int $groupId, int $userId): bool
    {
        if ($groupId <= 0 || $userId <= 0) {
            return false;
        }

        $group = $this->groupModel->getById($groupId);
        if (!$group) {
            return false;
        }
        
        $isAdmin = $this->groupModel->isGroupAdmin($groupId, $userId);
        return $isAdmin;
    }

    private function isReportInScope(array $report, int $groupId): bool
    {
        $reportGroupId = (int)($report['group_id'] ?? 0);
        $targetType = (string)($report['target_type'] ?? '');
        $targetId = (int)($report['target_id'] ?? 0);

        return $reportGroupId === $groupId || ($targetType === 'group' && $targetId === $groupId);
    }

    private function resolveBucket(string $targetType): string
    {
        $targetType = strtolower($targetType);
        if (in_array($targetType, ['bin', 'bin_media'], true)) {
            return 'filebank';
        }
        if ($targetType === 'message') {
            return 'messages';
        }
        if ($targetType === 'channel') {
            return 'channels';
        }
        return 'content';
    }

    private function resolveActionLabel(string $targetType): string
    {
        $map = [
            'post' => 'Deleted',
            'comment' => 'Deleted',
            'question' => 'Deleted',
            'answer' => 'Deleted',
            'message' => 'Removed',
            'channel' => 'Restricted',
            'bin' => 'Removed',
            'bin_media' => 'Removed',
            'group' => 'Restricted',
            'user' => 'warned',
        ];

        return $map[strtolower($targetType)] ?? 'Action taken';
    }

    private function buildTargetUrl(array $report, int $groupId): string
    {
        $base = rtrim(BASE_PATH, '/') . '/index.php?';
        $type = strtolower((string)($report['target_type'] ?? ''));
        $targetId = (int)($report['target_id'] ?? 0);
        $contextGroupId = (int)($report['group_id'] ?? 0);
        $effectiveGroupId = $contextGroupId > 0 ? $contextGroupId : $groupId;
        $ownerId = (int)($report['reported_user_id'] ?? 0);

        switch ($type) {
            case 'post':
                if ($effectiveGroupId > 0) {
                    return $base . 'controller=Group&action=index&group_id=' . $effectiveGroupId . '#post-' . $targetId;
                }
                if ($ownerId > 0) {
                    return $base . 'controller=Profile&action=view&user_id=' . $ownerId . '#personal-post-' . $targetId;
                }
                return $base . 'controller=Feed&action=index';
            case 'comment':
                if ($effectiveGroupId > 0) {
                    return $base . 'controller=Group&action=index&group_id=' . $effectiveGroupId . '#comment-' . $targetId;
                }
                if ($ownerId > 0) {
                    return $base . 'controller=Profile&action=view&user_id=' . $ownerId . '#comment-' . $targetId;
                }
                return $base . 'controller=Feed&action=index';
            case 'group':
                return $base . 'controller=Group&action=index&group_id=' . $targetId;
            case 'user':
                return $base . 'controller=Profile&action=view&user_id=' . $targetId;
            case 'question':
                return $base . 'controller=QnA&action=view&id=' . $targetId;
            case 'answer':
                return $base . 'controller=QnA&action=index';
            case 'bin':
                if ($effectiveGroupId > 0) {
                    return $base . 'controller=FileBank&action=index&group_id=' . $effectiveGroupId . '#bin-' . $targetId;
                }
                return $base . 'controller=Feed&action=index';
            case 'bin_media':
                if ($effectiveGroupId > 0) {
                    return $base . 'controller=FileBank&action=index&group_id=' . $effectiveGroupId . '#media-' . $targetId;
                }
                return $base . 'controller=Feed&action=index';
            case 'channel':
                if ($effectiveGroupId > 0) {
                    return $base . 'controller=ChannelPage&action=index&group_id=' . $effectiveGroupId . '#channel-' . $targetId;
                }
                return $base . 'controller=Feed&action=index';
            case 'message':
                if ($effectiveGroupId > 0) {
                    return $base . 'controller=ChannelPage&action=index&group_id=' . $effectiveGroupId . '#message-' . $targetId;
                }
                return $base . 'controller=Feed&action=index';
            default:
                return $base . 'controller=Group&action=index&group_id=' . $effectiveGroupId;
        }
    }

    private function resolveGroupId(): int
    {
        $candidates = [
            $_GET['group_id'] ?? null,
            $_GET['groupId'] ?? null,
            $_GET['id'] ?? null,
            $_SESSION['current_group_id'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $groupId = (int)$candidate;
            if ($groupId > 0) {
                return $groupId;
            }
        }

        $joinedGroups = $this->groupModel->getGroupsJoinedBy((int)$_SESSION['user_id']);
        if (!empty($joinedGroups) && !empty($joinedGroups[0]['group_id'])) {
            return (int)$joinedGroups[0]['group_id'];
        }

        return 0;
    }
}
