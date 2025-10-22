<?php
session_start();
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/GroupModel.php';

class GroupController {
    private $groupModel;

    public function __construct() {
        $this->groupModel = new GroupModel();
    }

    public function index() {
        require_once __DIR__ . '/../views/groupprofileview.php';
    }

    public function handleAjax() {
        header('Content-Type: application/json');
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        switch ($action) {
            case 'create':
                $this->createGroup($input);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        exit;
    }

    private function createGroup($data) {
        try {
            $userId = $_SESSION['user_id'];
            
            // Validate required fields
            if (empty($data['name'])) {
                echo json_encode(['success' => false, 'message' => 'Group name is required']);
                return;
            }

            // Prepare group data
            $groupData = [
                'name' => trim($data['name']),
                'tag' => !empty($data['tag']) ? trim($data['tag']) : null,
                'description' => !empty($data['description']) ? trim($data['description']) : null,
                'focus' => !empty($data['focus']) ? trim($data['focus']) : null,
                'privacy_status' => $data['privacy_status'] ?? 'public',
                'rules' => !empty($data['rules']) ? trim($data['rules']) : null,
                'created_by' => $userId
            ];

            $groupId = $this->groupModel->createGroup($groupData);

            if ($groupId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Group created successfully',
                    'group_id' => $groupId
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create group']);
            }
        } catch (Exception $e) {
            error_log('Create group error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new GroupController();
    $controller->handleAjax();
}

