<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/QuestionModel.php';
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/SettingsModel.php';

class PopularController {
    private $questionModel;
    private $postModel;
    private $settingsModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->questionModel = new QuestionModel();
        $this->postModel = new PostModel();
        $this->settingsModel = new SettingsModel();
    }

    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit();
        }

        $userId = $_SESSION['user_id'];
        $sort = $_GET['sort'] ?? 'recent';
        $filters = [
            'sort' => $sort,
            'category' => $_GET['category'] ?? null,
            'topic' => $_GET['topic'] ?? null,
            'search' => $_GET['search'] ?? null,
            'mine' => (isset($_GET['mine']) && $_GET['mine'] === '1') || $sort === 'my_questions'
        ];
        
        $questions = $this->questionModel->getQuestionsFeed($userId, $filters);
        $categories = $this->questionModel->getCategories();

        require_once __DIR__ . '/../views/popular.php';
    }

    public function view() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit();
        }
        
        $questionId = $_GET['id'] ?? null;
        if (!$questionId) {
            header('Location: ' . BASE_PATH . 'index.php?controller=QnA&action=index');
            exit();
        }
        
        $userId = $_SESSION['user_id'];
        $skipViewIncrement = isset($_GET['no_view']) && $_GET['no_view'] === '1';
        if (!$skipViewIncrement) {
            $this->questionModel->incrementViews((int)$questionId);
        }
        $question = $this->questionModel->getQuestion($questionId, $userId);
        
        if (!$question) {
            header('Location: ' . BASE_PATH . 'index.php?controller=QnA&action=index');
            exit();
        }

        $answers = $this->questionModel->getAnswers($questionId, $userId);
        $categories = $this->questionModel->getCategories();
        
        require __DIR__ . '/../views/question-detail.php';
    }

    public function handleAjax() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $action = $_POST['sub_action'] ?? '';
        $userId = (int)$_SESSION['user_id'];

        switch ($action) {
            case 'createQuestion':
                $this->createQuestion($userId);
                break;
            case 'getAnswers':
                $this->getAnswers($userId);
                break;
            case 'createAnswer':
                $this->createAnswer($userId);
                break;
            case 'editAnswer':
                $this->editAnswer($userId);
                break;
            case 'deleteAnswer':
                $this->deleteAnswer($userId);
                break;
            case 'voteQuestion':
                $this->voteQuestion($userId);
                break;
            case 'voteAnswer':
                $this->voteAnswer($userId);
                break;
            case 'editQuestion':
                $this->editQuestion($userId);
                break;
            case 'deleteQuestion':
                $this->deleteQuestion($userId);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }

    private function createQuestion($userId) {
        $data = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'category' => $_POST['category'] ?? 'General',
            'topics' => !empty($_POST['topics']) ? explode(',', $_POST['topics']) : []
        ];
        
        if (empty($data['title'])) {
            echo json_encode(['success' => false, 'message' => 'Title is required']);
            return;
        }

        if (isset($_FILES['attachment_file']) && is_array($_FILES['attachment_file'])) {
            $uploadResult = $this->handleQuestionAttachmentUpload($_FILES['attachment_file']);
            if (!empty($uploadResult['errors'])) {
                echo json_encode(['success' => false, 'message' => $uploadResult['errors'][0]]);
                return;
            }
            $data = array_merge($data, $uploadResult);
        }
        
        $questionId = $this->questionModel->createQuestion($userId, $data);
        echo json_encode([
            'success' => true,
            'question_id' => $questionId,
            'message' => 'Question posted successfully'
        ]);
    }

    private function handleQuestionAttachmentUpload(array $file): array {
        if (!isset($file['error'])) {
            return ['errors' => ['Invalid attachment upload request.']];
        }

        if ((int)$file['error'] === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            return ['errors' => ['Attachment upload failed.']];
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['errors' => ['Invalid uploaded attachment.']];
        }

        $originalName = (string)($file['name'] ?? 'attachment.bin');
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png', 'zip', 'xlsx'];

        if ($ext === '' || !in_array($ext, $allowedExtensions, true)) {
            return ['errors' => ['Unsupported file type. Allowed: PDF, DOC, DOCX, TXT, JPG, PNG, ZIP, XLSX.']];
        }

        $projectRoot = dirname(__DIR__, 2);
        $targetDir = $projectRoot . '/public/uploads/questions';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return ['errors' => ['Could not prepare question upload directory.']];
        }

        $cleanOriginalName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $cleanOriginalName = $cleanOriginalName ?: ('attachment.' . $ext);
        $storedName = uniqid('q_', true) . '_' . $cleanOriginalName;
        $absolutePath = $targetDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            return ['errors' => ['Could not save uploaded attachment.']];
        }

        return [
            'attachment_name' => $originalName,
            'attachment_path' => 'uploads/questions/' . $storedName,
            'attachment_type' => $ext,
            'attachment_size' => (int)($file['size'] ?? 0),
        ];
    }
    
    private function createAnswer($userId) {
        $questionId = $_POST['question_id'] ?? null;
        $content = $_POST['content'] ?? '';
        $parentAnswerId = isset($_POST['parent_answer_id']) ? (int)$_POST['parent_answer_id'] : null;
        
        if (!$questionId || empty($content)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            return;
        }
        
        $answer = $this->questionModel->createAnswer($userId, $questionId, $content, $parentAnswerId);
        echo json_encode([
            'success' => true,
            'answer' => $answer,
            'message' => 'Answer posted successfully'
        ]);
    }

    private function getAnswers($userId) {
        $questionId = (int)($_POST['question_id'] ?? 0);
        if ($questionId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid question']);
            return;
        }

        $answers = $this->questionModel->getAnswers($questionId, $userId);
        echo json_encode(['success' => true, 'answers' => $answers]);
    }

    private function editAnswer($userId) {
        $answerId = (int)($_POST['answer_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if ($answerId <= 0 || $content === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            return;
        }

        $result = $this->questionModel->editAnswer($answerId, $userId, $content);
        echo json_encode($result);
    }

    private function deleteAnswer($userId) {
        $answerId = (int)($_POST['answer_id'] ?? 0);

        if ($answerId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid answer']);
            return;
        }

        $result = $this->questionModel->deleteAnswer($answerId, $userId);
        echo json_encode($result);
    }
    
    private function voteQuestion($userId) {
        $questionId = $_POST['question_id'] ?? null;
        $voteType = $_POST['vote_type'] ?? null;
        
        if (!$questionId || !in_array($voteType, ['upvote', 'downvote'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            return;
        }
        
        $result = $this->questionModel->voteQuestion($userId, $questionId, $voteType);
        echo json_encode(['success' => true, 'action' => $result]);
    }
    
    private function voteAnswer($userId) {
        $answerId = $_POST['answer_id'] ?? null;
        $voteType = $_POST['vote_type'] ?? null;
        
        if (!$answerId || !in_array($voteType, ['upvote', 'downvote'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            return;
        }
        
        $result = $this->questionModel->voteAnswer($userId, $answerId, $voteType);
        echo json_encode(['success' => true, 'action' => $result]);
    }

    private function editQuestion($userId) {
        $questionId = (int)($_POST['question_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $topics = !empty($_POST['topics']) ? explode(',', $_POST['topics']) : [];

        if ($questionId <= 0 || $title === '' || $content === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            return;
        }

        $result = $this->questionModel->updateQuestion($questionId, $userId, [
            'title' => $title,
            'content' => $content,
            'category' => $category,
            'topics' => $topics
        ]);

        echo json_encode($result);
    }

    private function deleteQuestion($userId) {
        $questionId = (int)($_POST['question_id'] ?? 0);

        if ($questionId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid question']);
            return;
        }

        $result = $this->questionModel->deleteQuestion($questionId, $userId);
        echo json_encode($result);
    }
}
