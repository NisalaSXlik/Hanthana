<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/QuestionModel.php';

class PopularController {
    private $questionModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->questionModel = new QuestionModel();
    }

    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit();
        }

        $userId = $_SESSION['user_id'];
        $filters = [
            'sort' => $_GET['sort'] ?? 'recent',
            'category' => $_GET['category'] ?? null,
            'topic' => $_GET['topic'] ?? null,
            'search' => $_GET['search'] ?? null
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
            header('Location: ' . BASE_PATH . 'index.php?controller=Popular&action=index');
            exit();
        }
        
        $userId = $_SESSION['user_id'];
        $question = $this->questionModel->getQuestion($questionId, $userId);
        
        if (!$question) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Popular&action=index');
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
        $userId = $_SESSION['user_id'];
        
        switch ($action) {
            case 'createQuestion':
                $this->createQuestion($userId);
                break;
            case 'createAnswer':
                $this->createAnswer($userId);
                break;
            case 'voteQuestion':
                $this->voteQuestion($userId);
                break;
            case 'voteAnswer':
                $this->voteAnswer($userId);
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
        
        $questionId = $this->questionModel->createQuestion($userId, $data);
        echo json_encode([
            'success' => true,
            'question_id' => $questionId,
            'message' => 'Question posted successfully'
        ]);
    }
    
    private function createAnswer($userId) {
        $questionId = $_POST['question_id'] ?? null;
        $content = $_POST['content'] ?? '';
        
        if (!$questionId || empty($content)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            return;
        }
        
        $answerId = $this->questionModel->createAnswer($userId, $questionId, $content);
        echo json_encode([
            'success' => true,
            'answer_id' => $answerId,
            'message' => 'Answer posted successfully'
        ]);
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
}
