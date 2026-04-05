<?php

require_once __DIR__ . '/../models/QuestionModel.php';
require_once __DIR__ . '/../models/AcedemicDashboardModel.php';

class AcedemicDashboardController
{
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit();
        }

        $questionModel = new QuestionModel();
        $myQuestionAnswers = $questionModel->getMyQuestionsLatestAnswers((int)$_SESSION['user_id'], 5);

        require_once __DIR__ . '/../views/acedemicdashboard.php';
    }

    public function handleAjax()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $payload = $_POST;
        if (empty($payload)) {
            $raw = file_get_contents('php://input');
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            }
        }

        $action = (string)($payload['sub_action'] ?? 'resource_data');
        $userId = (int)$_SESSION['user_id'];
        $resourceModel = new AcedemicDashboardModel();

        try {
            switch ($action) {
                case 'resource_data':
                    $this->handleResourceData($resourceModel, $payload, $userId);
                    break;
                case 'toggle_save':
                    $this->handleToggleSave($resourceModel, $payload, $userId);
                    break;
                case 'record_download':
                    $this->handleRecordDownload($resourceModel, $payload, $userId);
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    break;
            }
        } catch (Throwable $e) {
            error_log('AcedemicDashboardController handleAjax error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }

        exit;
    }

    private function handleResourceData(AcedemicDashboardModel $model, array $payload, int $userId): void
    {
        $tab = (string)($payload['tab'] ?? 'all');
        $groupId = (int)($payload['group_id'] ?? 0);
        $binId = (int)($payload['bin_id'] ?? 0);

        $allowedTabs = ['all', 'recent_uploads', 'top_downloads', 'my_saves'];
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'all';
        }

        $groups = $model->getGroupsByLatestUpload($userId);

        if ($tab === 'recent_uploads') {
            $recentFiles = $model->getRecentFiles($userId, 40);
            echo json_encode([
                'success' => true,
                'data' => [
                    'tab' => $tab,
                    'groups' => [],
                    'bins' => [],
                    'files' => $recentFiles,
                    'selected_group_id' => 0,
                    'selected_bin_id' => 0,
                ],
            ]);
            return;
        }

        if ($tab === 'top_downloads') {
            $topDownloadedFiles = $model->getTopDownloadedFiles($userId, 40);
            echo json_encode([
                'success' => true,
                'data' => [
                    'tab' => $tab,
                    'groups' => [],
                    'bins' => [],
                    'files' => $topDownloadedFiles,
                    'selected_group_id' => 0,
                    'selected_bin_id' => 0,
                ],
            ]);
            return;
        }

        if ($tab === 'my_saves') {
            $savedFiles = $model->getSavedFiles($userId, 40);
            echo json_encode([
                'success' => true,
                'data' => [
                    'tab' => $tab,
                    'groups' => [],
                    'bins' => [],
                    'files' => $savedFiles,
                    'selected_group_id' => 0,
                    'selected_bin_id' => 0,
                ],
            ]);
            return;
        }

        if ($groupId <= 0 && !empty($groups)) {
            $groupId = (int)$groups[0]['group_id'];
        }

        $bins = $groupId > 0 ? $model->getBinsForGroup($userId, $groupId) : [];

        if ($binId <= 0 && !empty($bins)) {
            $binId = (int)$bins[0]['bin_id'];
        }

        $files = ($groupId > 0 && $binId > 0)
            ? $model->getFilesForBin($userId, $groupId, $binId, $tab)
            : [];

        echo json_encode([
            'success' => true,
            'data' => [
                'tab' => $tab,
                'groups' => $groups,
                'bins' => $bins,
                'files' => $files,
                'selected_group_id' => $groupId,
                'selected_bin_id' => $binId,
            ],
        ]);
    }

    private function handleToggleSave(AcedemicDashboardModel $model, array $payload, int $userId): void
    {
        $mediaId = (int)($payload['media_id'] ?? 0);
        if ($mediaId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Valid media id is required']);
            return;
        }

        $saved = $model->toggleFileSave($userId, $mediaId);
        if ($saved === null) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You cannot access this file']);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => ['saved' => $saved],
            'message' => $saved ? 'File saved' : 'File removed from saved',
        ]);
    }

    private function handleRecordDownload(AcedemicDashboardModel $model, array $payload, int $userId): void
    {
        $mediaId = (int)($payload['media_id'] ?? 0);
        if ($mediaId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Valid media id is required']);
            return;
        }

        $download = $model->recordDownload($userId, $mediaId);
        if (!$download) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You cannot access this file']);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'download_count' => (int)$download['download_count'],
                'file_path' => (string)$download['file_path'],
            ],
        ]);
    }
}
