<?php
class ReportController {
    private ReportModel $reportModel;
    private PostModel $postModel;
    private UserModel $userModel;
    private GroupModel $groupModel;

    public function __construct() {
        $this->reportModel = new ReportModel();
        $this->postModel = new PostModel();
        $this->userModel = new UserModel();
        $this->groupModel = new GroupModel();
    }

    public function submit(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Please sign in to report content']);
            return;
        }

        $input = $this->resolveInput();
        $targetType = strtolower(trim($input['target_type'] ?? ''));
        $targetId = (int)($input['target_id'] ?? 0);
        $reportType = strtolower(trim($input['report_type'] ?? 'other'));
        $description = trim($input['description'] ?? '');

        $allowedTargets = ['post', 'user', 'group'];
        if (!in_array($targetType, $allowedTargets, true) || $targetId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid report target']);
            return;
        }

        $reporterId = (int)$_SESSION['user_id'];
        if ($targetType === 'user' && $targetId === $reporterId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You cannot report your own account']);
            return;
        }

        $targetRecord = $this->resolveTargetRecord($targetType, $targetId);
        if (!$targetRecord) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => ucfirst($targetType) . ' not found']);
            return;
        }

        $payload = [
            'reporter_id' => $reporterId,
            'report_type' => $reportType,
            'description' => $description
        ];

        switch ($targetType) {
            case 'post':
                $payload['reported_post_id'] = $targetId;
                $payload['reported_user_id'] = (int)($targetRecord['user_id'] ?? 0) ?: null;
                break;
            case 'group':
                $payload['reported_group_id'] = $targetId;
                $payload['reported_user_id'] = (int)($targetRecord['created_by'] ?? 0) ?: null;
                break;
            case 'user':
                $payload['reported_user_id'] = $targetId;
                break;
        }

        $result = $this->reportModel->createReport($payload);
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Thanks for letting us know. Our team will review this shortly.'
            ]);
            return;
        }

        http_response_code(500);
        $message = $result['message'] ?? 'Unable to submit report right now';
        echo json_encode(['success' => false, 'message' => $message]);
    }

    private function resolveInput(): array {
        if (!empty($_POST)) {
            return $_POST;
        }

        $rawInput = file_get_contents('php://input');
        if ($rawInput) {
            $decoded = json_decode($rawInput, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function resolveTargetRecord(string $targetType, int $targetId): ?array {
        switch ($targetType) {
            case 'post':
                return $this->postModel->getPostById($targetId);
            case 'group':
                return $this->groupModel->getById($targetId);
            case 'user':
                return $this->userModel->findById($targetId);
            default:
                return null;
        }
    }
}
