<?php
require_once __DIR__ . '/../models/PostModel.php';

class PostsController {
    public function create() {
        session_start();  // Start session for user auth

        // Check session (requisite: must be logged in)
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = 1;  // For testing purposes only
            /*http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
            return;*/
        }

        $authorId = $_SESSION['user_id'];

        // Validate foreign key: Check if author_id exists in Users
        $model = new PostModel();
        $conn = $model->getConnection();  // Access DB for check
        $stmt = $conn->prepare("SELECT user_id FROM Users WHERE user_id = ?");
        $stmt->execute([$authorId]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
            return;
        }

        // Get POST data
        $caption = trim($_POST['caption'] ?? '');
        $tags = trim($_POST['tags'] ?? '');
        $postType = $_POST['postType'] ?? 'general';
        $eventTitle = trim($_POST['eventTitle'] ?? '');
        $eventDate = $_POST['eventDate'] ?? '';
        $eventLocation = trim($_POST['eventLocation'] ?? '');
        $groupId = null;  // For now, no group; add from session or param if needed

        // Validate requisites
        $errors = [];
        if (empty($caption)) $errors[] = 'Caption required.';
        $tagArray = array_filter(array_map('trim', explode(',', $tags)));
        if (count($tagArray) < 5) $errors[] = 'At least 5 tags required.';
        if ($postType === 'event' && (empty($eventTitle) || empty($eventDate))) $errors[] = 'Event details required.';
        // if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) $errors[] = 'Image required.';

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
            $uploadDir = __DIR__ . '/../../public/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
            $serverPath = $uploadDir . $imageName;  // Server path for file move
            $webPath = '/Hanthane/public/uploads/' . $imageName;  // Web path for DB
            $imageSize = $_FILES['image']['size'];

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $serverPath)) {  // Use server path
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Upload failed.']);
                return;
            }
        }

        // Prepare data
        $data = [
            'content' => $caption,
            'post_type' => ($postType === 'event') ? 'event' : 'image',
            'visibility' => 'public',
            'event_title' => $eventTitle ?: null,
            'event_date' => $eventDate ? date('Y-m-d H:i:s', strtotime($eventDate)) : null,
            'event_location' => $eventLocation ?: null,
            'is_group_post' => false,
            'group_id' => $groupId,
            'author_id' => $authorId,
            'image_path' => $webPath,
            'image_name' => $imageName,
            'image_size' => $imageSize
        ];

        // Create post
        $result = $model->createPost($data);

        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'Post created!', 'post_id' => $result['post_id']]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $result['error']]);
        }
    }

    public function update() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $postId = (int) ($_POST['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if ($postId <= 0 || $content === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            return;
        }

        $model = new PostModel();
        $ok = $model->updatePostContent($postId, $userId, $content);  // From PostModel
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Post updated']);
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Update failed or not allowed']);
        }
    }

    // New: Handle post delete (remove from Post and PostMedia)
    public function delete() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $postId = (int) ($_POST['post_id'] ?? 0);

        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing post id']);
            return;
        }

        $model = new PostModel();
        $ok = $model->deletePost($postId, $userId);  // From PostModel
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Post deleted']);
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Delete failed or not allowed']);
        }
    }
}