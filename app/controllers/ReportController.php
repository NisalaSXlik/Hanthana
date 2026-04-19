<?php
require_once __DIR__ . '/../models/ReportModel.php';
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/QuestionModel.php';
require_once __DIR__ . '/../models/BinModel.php';
require_once __DIR__ . '/../models/MessageModel.php';
require_once __DIR__ . '/../models/ChannelModel.php';

class ReportController {
    private ReportModel $reportModel;
    private PostModel $postModel;
    private UserModel $userModel;
    private GroupModel $groupModel;
    private QuestionModel $questionModel;
    private BinModel $binModel;
    private MessageModel $messageModel;
    private ChannelModel $channelModel;

    public function __construct() {
        $this->reportModel = new ReportModel();
        $this->postModel = new PostModel();
        $this->userModel = new UserModel();
        $this->groupModel = new GroupModel();
        $this->questionModel = new QuestionModel();
        $this->binModel = new BinModel();
        $this->messageModel = new MessageModel();
        $this->channelModel = new ChannelModel();
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

        $allowedTargets = ['post', 'user', 'group', 'bin', 'question', 'group_question', 'media', 'message', 'channel'];
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

        if ($targetType === 'question' && !empty($targetRecord['group_id']) && (($targetRecord['group_post_type'] ?? '') === 'question')) {
            // Group questions are stored in Post table, so reports should be post reports.
            $targetType = 'post';
        }

        // Backward compatibility: normalize legacy client value to post.
        if ($targetType === 'group_question') {
            $targetType = 'post';
        }

        $payload = [
            'reporter_id' => $reporterId,
            'target_type' => $targetType === 'media' ? 'bin_media' : $targetType,
            'target_id' => $targetId,
            'report_type' => $reportType,
            'description' => $description
        ];

        switch ($targetType) {
            case 'post':
                $payload['group_id'] = (int)($targetRecord['group_id'] ?? 0) ?: null;
                $payload['reported_user_id'] = (int)($targetRecord['author_id'] ?? $targetRecord['user_id'] ?? 0) ?: null;
                break;
            case 'group':
                $payload['group_id'] = $targetId;
                $payload['reported_user_id'] = (int)($targetRecord['created_by'] ?? 0) ?: null;
                break;
            case 'bin':
                $payload['group_id'] = (int)($targetRecord['group_id'] ?? 0) ?: null;
                $payload['reported_user_id'] = (int)($targetRecord['created_by'] ?? 0) ?: null;
                break;
            case 'user':
                $payload['reported_user_id'] = $targetId;
                break;
            case 'question':
                $payload['reported_user_id'] = (int)($targetRecord['user_id'] ?? 0) ?: null;
                break;
            case 'media':
                $payload['group_id'] = (int)($targetRecord['group_id'] ?? 0) ?: null;
                $payload['reported_user_id'] = (int)($targetRecord['added_by'] ?? 0) ?: null;
                break;
            case 'message':
                $payload['group_id'] = (int)($targetRecord['group_id'] ?? 0) ?: null;
                $payload['reported_user_id'] = (int)($targetRecord['sender_id'] ?? 0) ?: null;
                break;
            case 'channel':
                $payload['group_id'] = (int)($targetRecord['group_id'] ?? 0) ?: null;
                $payload['reported_user_id'] = (int)($targetRecord['created_by'] ?? 0) ?: null;
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
            case 'bin':
                return $this->binModel->getBinById($targetId);
            case 'user':
                return $this->userModel->findById($targetId);
            case 'question':
                $question = $this->questionModel->getQuestion($targetId, (int)$_SESSION['user_id']);
                if ($question) {
                    return $question;
                }

                $groupQuestion = $this->postModel->getPostById($targetId);
                return ($groupQuestion && (($groupQuestion['group_post_type'] ?? '') === 'question')) ? $groupQuestion : null;
            case 'group_question':
                $groupQuestion = $this->postModel->getPostById($targetId);
                return ($groupQuestion && (($groupQuestion['group_post_type'] ?? '') === 'question')) ? $groupQuestion : null;
            case 'media':
                return $this->binModel->getMediaById($targetId);
            case 'message':
                return $this->messageModel->getMessageForReport($targetId);
            case 'channel':
                return $this->channelModel->getChannelById($targetId);
            default:
                return null;
        }
    }
}
