# Group Post Creation Integration Guide

## Files Created/Modified:

### 1. **groupprofileview.php** - Updated create post section and added modal
- Replaced simple input with clickable trigger
- Added quick action buttons (Photo, Poll, Question, Resource)
- Added comprehensive create post modal with 6 post types

### 2. **group-post-create.js** - New JavaScript file
- Handles modal open/close
- Manages post type switching
- Handles image and file uploads
- Submits form via AJAX

### 3. **groupprofileview.css** - Added CSS
- Styled create post modal
- Post type selector buttons
- Conditional fields styling
- Upload buttons and previews

## Controller Method Needed:

Add this method to `app/controllers/GroupController.php`:

```php
public function createPost() {
    ob_start();
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    $userId = $_SESSION['user_id'] ?? null;
    $groupId = (int)($_POST['group_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $postType = $_POST['post_type'] ?? 'discussion';

    if (!$userId) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Please log in']);
        return;
    }

    if (!$groupId) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid group']);
        return;
    }

    // Check if user is a member
    require_once __DIR__ . '/../models/GroupModel.php';
    $groupModel = new GroupModel();
    if (!$groupModel->isGroupMember($groupId, $userId)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'You must be a member to post']);
        return;
    }

    if (empty($content)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Post content is required']);
        return;
    }

    // Handle image upload
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/../../public/uploads/posts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid image format']);
            return;
        }

        $fileName = uniqid('post_') . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = 'uploads/posts/' . $fileName;
        }
    }

    // Handle file upload (for resources)
    $filePath = null;
    if (!empty($_FILES['file']['name'])) {
        $uploadDir = __DIR__ . '/../../public/uploads/resources/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid('resource_') . '_' . basename($_FILES['file']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            $filePath = 'uploads/resources/' . $fileName;
        }
    }

    // Prepare metadata based on post type
    $metadata = [];
    switch ($postType) {
        case 'question':
            $metadata['category'] = $_POST['question_category'] ?? 'general';
            $metadata['tags'] = $_POST['tags'] ?? '';
            break;
        case 'resource':
            $metadata['resource_title'] = $_POST['resource_title'] ?? '';
            $metadata['resource_type'] = $_POST['resource_type'] ?? 'notes';
            $metadata['resource_link'] = $_POST['resource_link'] ?? '';
            $metadata['file_path'] = $filePath;
            break;
        case 'poll':
            $metadata['poll_question'] = $_POST['poll_question'] ?? '';
            $metadata['options'] = [
                $_POST['poll_option_1'] ?? '',
                $_POST['poll_option_2'] ?? '',
                $_POST['poll_option_3'] ?? '',
                $_POST['poll_option_4'] ?? ''
            ];
            $metadata['options'] = array_filter($metadata['options']);
            $metadata['duration'] = (int)($_POST['poll_duration'] ?? 7);
            break;
        case 'event':
            $metadata['event_title'] = $_POST['event_title'] ?? '';
            $metadata['event_date'] = $_POST['event_date'] ?? '';
            $metadata['event_time'] = $_POST['event_time'] ?? '';
            $metadata['event_location'] = $_POST['event_location'] ?? '';
            break;
        case 'assignment':
            $metadata['assignment_title'] = $_POST['assignment_title'] ?? '';
            $metadata['assignment_deadline'] = $_POST['assignment_deadline'] ?? '';
            $metadata['assignment_points'] = (int)($_POST['assignment_points'] ?? 0);
            break;
    }

    // Create the post via the per-type model
    require_once __DIR__ . '/../models/post_types/PostTypeFactory.php';
    $postTypeModel = PostTypeFactory::make($postType);

    $payload = [
        'content' => $content,
        'image_path' => $imagePath,
        'request' => $_POST
    ];
    if (!empty($filePath)) {
        $payload['file_path'] = $filePath;
    }

    $postId = $postTypeModel->create($userId, $groupId, $payload);

    if ($postId) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Post created successfully',
            'post_id' => $postId
        ]);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to create post']);
    }
    
    ob_end_flush();
}
```

## Database Changes:

Update the Posts table to support educational post types:

```sql
ALTER TABLE Posts 
ADD COLUMN post_type VARCHAR(50) DEFAULT 'discussion' AFTER content,
ADD COLUMN metadata JSON AFTER post_type;

-- Index for post types
CREATE INDEX idx_post_type ON Posts(post_type);
```

## Post Model Method:

Group posts now resolve through dedicated models inside `app/models/post_types/`. Each class extends `BaseGroupPostModel`, ensuring shared database behavior while keeping business rules encapsulated per type. Use `PostTypeFactory::make($type)` to obtain the correct model and call `create()` with sanitized payload data. The factory covers:

- `DiscussionPostModel`
- `QuestionPostModel`
- `ResourcePostModel`
- `PollPostModel` (also exposes `recordVote()`)
- `EventPostModel`
- `AssignmentPostModel`

## Post Types Supported:

1. **Discussion** - General posts with text/images
2. **Question** - Q&A format with categories and tags
3. **Resource** - Share educational materials (notes, slides, documents, links)
4. **Poll** - Create polls with up to 4 options
5. **Event** - Schedule study sessions or group events
6. **Assignment** - Post assignment details with deadlines

## Features:

✅ Modal-based post creation
✅ 6 educational post types
✅ Image upload for all types
✅ File upload for resources
✅ Conditional fields per post type
✅ Type-specific metadata storage
✅ Member-only posting
✅ Smooth animations and UX
✅ Form validation
✅ Success/error feedback

## Next Steps:

1. Run the SQL ALTER statements
2. Ensure `GroupController::createPost` routes through `PostTypeFactory` so each type uses its own model
3. Use `GroupPostModel` for fetching group feeds/polls inside GroupController
4. Test each post type
5. Customize post display based on type in the feed
