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

        // Support both JSON and multipart/form-data
        $action = null;
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
        } else {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
        }

        switch ($action) {
            case 'create':
                $this->createGroup(isset($input) ? $input : $_POST);
                break;
            case 'edit':
                $this->editGroup();
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        exit;
    }
    /**
     * Handle group edit (update group details and images)
     */
    private function editGroup() {
        try {
            $userId = $_SESSION['user_id'];
            $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : null;
            if (!$groupId) {
                error_log('DEBUG: Missing group ID');
                echo json_encode(['success' => false, 'message' => 'Missing group ID.']);
                return;
            }
            $group = $this->groupModel->getById($groupId);
            if (!$group || $group['created_by'] != $userId) {
                error_log('DEBUG: Permission denied for user ' . $userId . ' on group ' . $groupId);
                echo json_encode(['success' => false, 'message' => 'Permission denied.']);
                return;
            }

            $fields = ['name', 'tag', 'description', 'focus', 'privacy_status', 'rules'];
            $updateData = [];
            foreach ($fields as $field) {
                if (isset($_POST[$field]) && $_POST[$field] !== '') {
                    $updateData[$field] = trim($_POST[$field]);
                }
            }
            error_log('DEBUG: updateData after fields: ' . json_encode($updateData));

            // Handle file uploads (display_picture, cover_image)
            $coverDir = __DIR__ . '/../../public/images/groupCover';
            $dpDir = __DIR__ . '/../../public/images/groupDp';
            if (!is_dir($coverDir) && !mkdir($coverDir, 0777, true)) {
                error_log('DEBUG: Failed to create cover directory: ' . $coverDir . ' - ' . print_r(error_get_last(), true));
            }
            if (!is_dir($dpDir) && !mkdir($dpDir, 0777, true)) {
                error_log('DEBUG: Failed to create DP directory: ' . $dpDir . ' - ' . print_r(error_get_last(), true));
            }
            if (!is_writable($coverDir)) {
                error_log('DEBUG: Cover directory not writable: ' . $coverDir);
            }
            if (!is_writable($dpDir)) {
                error_log('DEBUG: DP directory not writable: ' . $dpDir);
            }
            // Display Picture
            if (isset($_FILES['display_picture']) && is_uploaded_file($_FILES['display_picture']['tmp_name'])) {
                error_log('DEBUG: display_picture upload error: ' . $_FILES['display_picture']['error']);
                if ($_FILES['display_picture']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['display_picture']['name'], PATHINFO_EXTENSION);
                    $dpName = 'group_' . $groupId . '_dp_' . time() . '.' . $ext;
                    $dpPath = $dpDir . DIRECTORY_SEPARATOR . $dpName;
                    if (move_uploaded_file($_FILES['display_picture']['tmp_name'], $dpPath)) {
                        $updateData['display_picture'] = 'images/groupDp/' . $dpName;
                        error_log('DEBUG: display_picture uploaded to ' . $dpPath);
                    } else {
                        error_log('DEBUG: Failed to move display_picture to ' . $dpPath . ' - ' . print_r(error_get_last(), true));
                    }
                }
            }
            // Cover Image
            if (isset($_FILES['cover_image']) && is_uploaded_file($_FILES['cover_image']['tmp_name'])) {
                error_log('DEBUG: cover_image upload error: ' . $_FILES['cover_image']['error']);
                if ($_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                    $coverName = 'group_' . $groupId . '_cover_' . time() . '.' . $ext;
                    $coverPath = $coverDir . DIRECTORY_SEPARATOR . $coverName;
                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $coverPath)) {
                        $updateData['cover_image'] = 'images/groupCover/' . $coverName;
                        error_log('DEBUG: cover_image uploaded to ' . $coverPath);
                    } else {
                        error_log('DEBUG: Failed to move cover_image to ' . $coverPath . ' - ' . print_r(error_get_last(), true));
                    }
                }
            }

            error_log('DEBUG: updateData before updateGroup: ' . json_encode($updateData));
            if (empty($updateData)) {
                error_log('DEBUG: No changes detected');
                echo json_encode(['success' => false, 'message' => 'No changes detected.']);
                return;
            }

            $success = $this->groupModel->updateGroup($groupId, $updateData);
            error_log('DEBUG: updateGroup result: ' . ($success ? 'success' : 'fail'));
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Group updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update group.']);
            }
        } catch (Exception $e) {
            error_log('Edit group error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function createGroup($data) {
        try {
            $userId = $_SESSION['user_id'];
            $errors = [];
            // Validate required fields
            if (empty($data['name'])) {
                $errors[] = 'Group Name: This field is required.';
            } else if (strlen($data['name']) > 255) {
                $errors[] = 'Group Name: Must be 255 characters or less.';
            }
            if (!empty($data['tag'])) {
                if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['tag'])) {
                    $errors[] = 'Group Tag: Can only contain letters, numbers, hyphens, and underscores.';
                }
                if (strlen($data['tag']) > 50) {
                    $errors[] = 'Group Tag: Must be 50 characters or less.';
                }
            }
            if (!empty($data['focus']) && strlen($data['focus']) > 100) {
                $errors[] = 'Focus/Category: Must be 100 characters or less.';
            }
            if (!empty($data['privacy_status']) && !in_array($data['privacy_status'], ['public','private','secret'])) {
                $errors[] = 'Privacy: Invalid privacy status.';
            }
            // Don't check for duplicate tag here, let DB handle it for atomicity
            if ($errors) {
                echo json_encode(['success' => false, 'message' => implode("\n", $errors)]);
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

            try {
                $groupId = $this->groupModel->createGroup($groupData);
            } catch (Exception $e) {
                $msg = $e->getMessage();
                $errorList = [];
                if (stripos($msg, 'tag already exists') !== false) {
                    $errorList[] = 'Group Tag: This tag is already in use. Please choose a unique tag.';
                }
                // Add more DB error parsing if needed
                if (empty($errorList)) {
                    $errorList[] = 'An error occurred: ' . $msg;
                }
                echo json_encode(['success' => false, 'message' => implode("\n", $errorList)]);
                return;
            }

            if ($groupId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Group created successfully',
                    'group_id' => $groupId
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create group.']);
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

