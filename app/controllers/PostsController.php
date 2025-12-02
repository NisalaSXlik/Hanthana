<?php
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/SettingsModel.php';

class PostsController {
    public function handleAjax() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $input = $_POST;
        if (empty($_POST) || !isset($_POST['sub_action'])) {
            $rawInput = file_get_contents('php://input');
            if ($rawInput) {
                $decoded = json_decode($rawInput, true);
                if ($decoded) $input = $decoded;
            }
        }
        $subAction = $input['sub_action'] ?? '';

        switch ($subAction) {
            case 'create': $this->createPost($input); break;
            case 'update': $this->updatePost($input); break;
            case 'delete': $this->deletePost($input); break;
            default: echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        exit;
    }

    private function createPost($data) {
        $authorId = $_SESSION['user_id'];

        // Validate foreign key: Check if author_id exists in Users
        $model = new PostModel();
        $conn = $model->getConnection();
        $stmt = $conn->prepare("SELECT user_id FROM Users WHERE user_id = ?");
        $stmt->execute([$authorId]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
            return;
        }

        // Get POST data
        $caption = trim($data['caption'] ?? '');
        $tags = trim($data['tags'] ?? '');
        $postType = $data['postType'] ?? 'general';
        $eventTitle = trim($data['eventTitle'] ?? '');
        $eventDate = $data['eventDate'] ?? '';
        $eventLocation = trim($data['eventLocation'] ?? '');
        $groupId = null;

        // Get user's post visibility setting
        $settingsModel = new SettingsModel();
        $userSettings = $settingsModel->getUserSettings($authorId);
        $postVisibility = $userSettings['post_visibility'] ?? 'friends';

        // Validate requisites
        $errors = [];
        if (empty($caption)) $errors[] = 'Caption required.';
        $tagArray = array_filter(array_map('trim', explode(',', $tags)));
        if (count($tagArray) < 5) $errors[] = 'At least 5 tags required.';
        if ($postType === 'event' && (empty($eventTitle) || empty($eventDate))) $errors[] = 'Event details required.';

        // Check foreign key for group_id if set
        if ($groupId) {
            $stmt = $conn->prepare("SELECT group_id FROM GroupsTable WHERE group_id = ?");
            $stmt->execute([$groupId]);
            if (!$stmt->fetch()) $errors[] = 'Invalid group ID.';
        }

        if ($errors) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            return;
        }

        // Handle image upload (only if provided)
        $serverPath = null;
        $webPath = null;
        $imageName = null;
        $imageSize = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/post_media/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
            $serverPath = $uploadDir . $imageName;
            $dbPath = 'uploads/post_media/' . $imageName;
            $imageSize = $_FILES['image']['size'];

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $serverPath)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Upload failed.']);
                return;
            }
        }

        // Prepare data with privacy settings
        $data = [
            'content' => $caption,
            'post_type' => ($postType === 'event') ? 'event' : 'image',
            'visibility' => $postVisibility, // Use user's privacy setting
            'event_title' => $eventTitle ?: null,
            'event_date' => $eventDate ? date('Y-m-d H:i:s', strtotime($eventDate)) : null,
            'event_location' => $eventLocation ?: null,
            'is_group_post' => false,
            'group_id' => $groupId,
            'author_id' => $authorId,
            'image_path' => $dbPath ?? null,
            'image_name' => $imageName,
            'image_size' => $imageSize
        ];

        // Create post
        $result = $model->createPost($data);

        if ($result['success']) {
            echo json_encode([
                'success' => true, 
                'message' => 'Post created!', 
                'post_id' => $result['post_id'],
                'visibility' => $postVisibility // Return visibility for frontend
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $result['error']]);
        }
    }

    private function updatePost($data) {
        $userId = (int) $_SESSION['user_id'];
        $postId = (int) ($data['post_id'] ?? 0);
        $content = trim($data['content'] ?? '');

        if ($postId <= 0 || $content === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            return;
        }

        $model = new PostModel();
        $ok = $model->updatePostContent($postId, $userId, $content);
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Post updated']);
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Update failed or not allowed']);
        }
    }

    private function deletePost($data) {
        $userId = (int) $_SESSION['user_id'];
        $postId = (int) ($data['post_id'] ?? 0);

        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing post id']);
            return;
        }

        $model = new PostModel();
        $ok = $model->deletePost($postId, $userId);
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Post deleted']);
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Delete failed or not allowed']);
        }
    }
}