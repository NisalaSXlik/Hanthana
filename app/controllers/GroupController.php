<?php
// Don't start session here - let the view handle it
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/GroupPostModel.php';
require_once __DIR__ . '/../models/CalendarReminderModel.php';
require_once __DIR__ . '/../models/post_types/PostTypeFactory.php';

class GroupController {
    private $groupModel;
    private $calendarModel;

    public function __construct() {
        $this->groupModel = new GroupModel();
        $this->calendarModel = new CalendarReminderModel();
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Landing&action=index');
            exit();
        }

        // Accept both 'id' and 'group_id' parameters for flexibility
        $groupId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['group_id']) ? (int)$_GET['group_id'] : null);
        if (!$groupId) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Feed&action=index');
            exit();
        }

        $userId = $_SESSION['user_id'];
        $group = $this->groupModel->getById($groupId);
        if (!$group) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Feed&action=index&error=group_not_found');
            exit();
        }

        $isCreator = (int)$group['created_by'] === $userId;
        $isAdmin = $this->groupModel->isGroupAdmin($groupId, $userId);
        $joinedGroups = $this->groupModel->getGroupsJoinedBy($userId);
        $membershipState = $this->groupModel->getUserMembershipState($groupId, $userId);
        $isJoined = ($membershipState === 'active' || $isCreator);

        $groupPostModel = new GroupPostModel();
        $groupPosts = $groupPostModel->getGroupPosts($groupId, $userId);
        $groupEvents = array_values(array_map(function ($post) {
            $metadata = is_array($post['metadata']) ? $post['metadata'] : [];
            return [
                'post_id' => (int)$post['post_id'],
                'title' => $metadata['title'] ?? $post['event_title'] ?? 'Untitled Event',
                'date' => $metadata['date'] ?? $post['event_date'] ?? null,
                'time' => $metadata['time'] ?? $post['event_time'] ?? null,
                'location' => $metadata['location'] ?? $post['event_location'] ?? null,
                'description' => $metadata['description'] ?? $post['content'] ?? '',
                'image_url' => $post['image_url'] ?? null
            ];
        }, array_filter($groupPosts, function ($post) {
            return ($post['group_post_type'] ?? '') === 'event';
        })));

        if (!empty($groupEvents)) {
            $eventPostIds = array_column($groupEvents, 'post_id');
            $interestedMap = $this->calendarModel->getRemindersForPosts($userId, $eventPostIds);
            foreach ($groupEvents as &$eventItem) {
                $eventItem['interested'] = !empty($interestedMap[$eventItem['post_id']]);
            }
            unset($eventItem);
        }
        $photoPosts = array_values(array_filter($groupPosts, function ($post) {
            return !empty($post['image_url']);
        }));

        $friendModel = new FriendModel();
        $incomingFriendRequests = $friendModel->getIncomingRequests($userId);
        // Load group members for the members tab
        $groupMembers = $this->groupModel->getMembers($groupId);

        $hasPendingRequest = (!$isCreator && !$isJoined && $membershipState === 'pending');

        // If admin, load pending join requests to surface in view
        $pendingRequests = [];
        if ($isAdmin) {
            $pendingRequests = $this->groupModel->getPendingRequests($groupId);
        }

        $groupResourceBuckets = $groupPostModel->getGroupResourceLibrary($groupId);

        // Pass all variables to view
        require __DIR__ . '/../views/groupprofileview.php';
    }

    /**
     * Backwards-compatible alias so routes using action=view still work
     */
    public function view() {
        $this->index();
    }

    /**
     * Admin manage page for group: view and act on pending join requests
     */
    public function manage() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Landing&action=index');
            exit();
        }

        $groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
        if (!$groupId) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Feed&action=index');
            exit();
        }

        $userId = $_SESSION['user_id'];
        $group = $this->groupModel->getById($groupId);
        if (!$group) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Feed&action=index&error=group_not_found');
            exit();
        }

        $isAdmin = $this->groupModel->isGroupAdmin($groupId, $userId);
        if (!$isAdmin) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Group&action=index&group_id=' . $groupId);
            exit();
        }

        $pendingRequests = $this->groupModel->getPendingRequests($groupId);
        require __DIR__ . '/../views/groupmanage.php';
    }

    public function handleAjax() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        // Unified sub-action parsing
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
                case 'create': $this->createGroup($input); break;
                case 'edit': $this->editGroup(); break;  // Uses $_POST/$_FILES directly
                case 'delete': $this->deleteGroup(); break;
                case 'join': $this->joinGroup(); break;
                case 'approve_request': $this->approveRequest(); break;
                case 'reject_request': $this->rejectRequest(); break;
                case 'delete_notification': $this->deleteNotificationAjax(); break;
                case 'clear_notifications': $this->clearNotificationsAjax(); break;
                case 'fetch_pending_requests': $this->fetchPendingRequestsAjax(); break;
                case 'mark_notification_read': $this->markNotificationRead(); break;
                case 'leave': $this->leaveGroup(); break;
                case 'createPost': $this->createPost(); break;
                case 'votePollOption': $this->votePollOption(); break;
                case 'fetchPollVotes': $this->fetchPollVotes(); break;
                case 'toggleEventReminder': $this->toggleEventReminder($input); break;
                default: echo json_encode(['success' => false, 'message' => 'Invalid action']);
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
            $coverDir = __DIR__ . '/../../public/uploads/group_cover';
            $dpDir = __DIR__ . '/../../public/uploads/group_dp';
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
                        $updateData['display_picture'] = 'uploads/group_dp/' . $dpName;
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
                        $updateData['cover_image'] = 'uploads/group_cover/' . $coverName;
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
                $groupId = $this->groupModel->createGroup($groupData, $userId);
            } catch (PDOException $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'Duplicate entry') !== false && stripos($msg, 'tag') !== false) {
                    echo json_encode(['success' => false, 'message' => 'Group Tag: This tag is already in use. Please choose a unique tag.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $msg]);
                }
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

    private function deleteGroup() {
        try {
            $groupId = $_POST['group_id'] ?? null;
            $userId = $_SESSION['user_id'];

            if (!$groupId) {
                echo json_encode(['success' => false, 'message' => 'Group ID required']);
                return;
            }

            // Check if user is creator
            $group = $this->groupModel->getById($groupId);
            if (!$group || $group['created_by'] != $userId) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }

            if ($this->groupModel->deleteGroup($groupId)) {
                echo json_encode(['success' => true, 'message' => 'Group deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete group']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    private function joinGroup() {
        try {
            $groupId = $_POST['group_id'] ?? null;
            $userId = $_SESSION['user_id'];

            if (!$groupId) {
                echo json_encode(['success' => false, 'message' => 'Group ID required']);
                return;
            }

            // Check if already joined
            $joinedGroups = $this->groupModel->getGroupsJoinedBy($userId);
            foreach ($joinedGroups as $g) {
                if ($g['group_id'] == $groupId) {
                    echo json_encode(['success' => false, 'message' => 'Already joined']);
                    return;
                }
            }

            // Check group privacy and either add or create join request
            $group = $this->groupModel->getById((int)$groupId);
            if (!$group) {
                echo json_encode(['success' => false, 'message' => 'Group not found']);
                return;
            }

            $privacy = $group['privacy_status'] ?? 'public';
            if ($privacy === 'public') {
                if ($this->groupModel->addMember($groupId, $userId)) {
                    echo json_encode(['success' => true, 'message' => 'Joined group successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to join group']);
                }
                return;
            }

            // For private/secret groups: create a join request and notify admins
            if ($this->groupModel->hasPendingRequest($groupId, $userId)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'You already have a pending request',
                    'pending' => true,
                    'membership_state' => 'pending'
                ]);
                return;
            }

            $created = $this->groupModel->createJoinRequest($groupId, $userId);
            if ($created === 'exists') {
                echo json_encode([
                    'success' => false,
                    'message' => 'You already have a pending request',
                    'pending' => true,
                    'membership_state' => 'pending'
                ]);
                return;
            }

            if ($created !== true) {
                echo json_encode(['success' => false, 'message' => 'Failed to create join request']);
                return;
            }

            // Notify group admins only when we actually created a new request
            require_once __DIR__ . '/../models/NotificationsModel.php';
            $notifModel = new NotificationsModel();

            $admins = $this->groupModel->getGroupAdmins($groupId);
            $userModel = new UserModel();
            // UserModel exposes findById
            $requester = method_exists($userModel, 'findById') ? $userModel->findById($userId) : (method_exists($userModel, 'getById') ? $userModel->getById($userId) : null);
            $requesterName = $requester ? ($requester['first_name'] . ' ' . $requester['last_name']) : 'User';
            $groupName = $group['name'] ?? 'Group';

            foreach ($admins as $admin) {
                $adminId = (int)$admin['user_id'];
                $title = 'New group join request';
                $msg = "$requesterName has requested to join $groupName.";
                // Link admins back to the group page where pending requests are shown
                $actionUrl = BASE_PATH . 'index.php?controller=Group&action=manage&group_id=' . (int)$groupId;
                $notifModel->createNotification($adminId, $userId, 'group_request', $title, $msg, $actionUrl, 'high');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Join request sent to group admins',
                'pending' => true,
                'membership_state' => 'pending'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    private function toggleEventReminder(array $input): void {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $postId = isset($input['post_id']) ? (int)$input['post_id'] : 0;
        $groupId = isset($input['group_id']) ? (int)$input['group_id'] : 0;

        if ($postId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid event']);
            return;
        }

        $groupPostModel = new GroupPostModel();
        $post = $groupPostModel->getGroupPostById($postId, $userId);

        if (!$post || ($post['group_post_type'] ?? '') !== 'event') {
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            return;
        }

        $existing = $this->calendarModel->getReminderForPost($userId, $postId);
        if ($existing) {
            $this->calendarModel->deleteReminder($userId, $postId);
            echo json_encode([
                'success' => true,
                'interested' => false,
                'message' => 'Removed from your calendar'
            ]);
            return;
        }

        $metadata = $post['metadata'] ?? [];
        $payload = [
            'title' => $metadata['title'] ?? $post['event_title'] ?? 'Untitled Event',
            'event_date' => $metadata['date'] ?? $post['event_date'] ?? null,
            'event_time' => $metadata['time'] ?? $post['event_time'] ?? null,
            'location' => $metadata['location'] ?? $post['event_location'] ?? null,
            'description' => $metadata['description'] ?? $post['content'] ?? null,
            'metadata' => [
                'source' => 'group_event',
                'group_id' => $groupId ?: ($post['group_id'] ?? null),
                'post_id' => $postId
            ]
        ];

        $created = $this->calendarModel->upsertReminder($userId, $payload['metadata']['group_id'] ?? null, $postId, $payload);
        if ($created) {
            echo json_encode([
                'success' => true,
                'interested' => true,
                'message' => 'Event saved to your calendar'
            ]);
            return;
        }

        echo json_encode(['success' => false, 'message' => 'Failed to save reminder']);
    }

    private function leaveGroup() {
        try {
            $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
            $userId = (int)$_SESSION['user_id'];

            if (!$groupId) {
                echo json_encode(['success' => false, 'message' => 'Group ID required']);
                return;
            }

            $group = $this->groupModel->getById($groupId);
            if (!$group) {
                echo json_encode(['success' => false, 'message' => 'Group not found']);
                return;
            }

            // Prevent creator from leaving their own group
            if ((int)$group['created_by'] === $userId) {
                echo json_encode(['success' => false, 'message' => 'Group creators cannot leave their own group.']);
                return;
            }

            // Ensure membership exists
            if (!$this->groupModel->isMember($groupId, $userId)) {
                echo json_encode(['success' => false, 'message' => 'You are not a member of this group.']);
                return;
            }

            if ($this->groupModel->removeMember($groupId, $userId)) {
                echo json_encode(['success' => true, 'message' => 'You left the group successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to leave the group.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    private function approveRequest() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); return; }
        $adminId = (int)$_SESSION['user_id'];
        $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if (!$groupId || !$userId) { echo json_encode(['success'=>false,'message'=>'Missing params']); return; }

        // Only admins can approve
        if (!$this->groupModel->isGroupAdmin($groupId, $adminId)) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); return; }

        $ok = $this->groupModel->approveJoinRequest($groupId, $userId, $adminId);
        if ($ok) {
            // Notify the requester
            require_once __DIR__ . '/../models/NotificationsModel.php';
            $notifModel = new NotificationsModel();
            $title = 'Group request approved';
            $msg = 'Your request to join the group has been approved.';
            $actionUrl = BASE_PATH . 'index.php?controller=Group&action=index&group_id=' . $groupId;
            $notifModel->createNotification($userId, $adminId, 'group_request', $title, $msg, $actionUrl, 'high');
            echo json_encode(['success'=>true,'message'=>'Request approved']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Failed to approve request']);
        }
    }

    private function rejectRequest() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); return; }
        $adminId = (int)$_SESSION['user_id'];
        $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if (!$groupId || !$userId) { echo json_encode(['success'=>false,'message'=>'Missing params']); return; }

        if (!$this->groupModel->isGroupAdmin($groupId, $adminId)) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); return; }

        $ok = $this->groupModel->rejectJoinRequest($groupId, $userId, $adminId);
        if ($ok) {
            require_once __DIR__ . '/../models/NotificationsModel.php';
            $notifModel = new NotificationsModel();
            $title = 'Group request declined';
            $msg = 'Your request to join the group was not approved.';
            $notifModel->createNotification($userId, $adminId, 'group_request', $title, $msg, null, 'medium');
            echo json_encode(['success'=>true,'message'=>'Request rejected']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Failed to reject request']);
        }
    }

    private function markNotificationRead() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); return; }
        $userId = (int)$_SESSION['user_id'];
        $notifId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
        if (!$notifId) { echo json_encode(['success'=>false,'message'=>'Missing notification id']); return; }
        require_once __DIR__ . '/../models/NotificationsModel.php';
        $notifModel = new NotificationsModel();
        $ok = $notifModel->markAsRead($notifId, $userId);
        echo json_encode(['success' => (bool)$ok]);
    }

    /**
     * Delete a single notification via AJAX (user can dismiss)
     */
    private function deleteNotificationAjax() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); return; }
        $userId = (int)$_SESSION['user_id'];
        $notifId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
        if (!$notifId) { echo json_encode(['success'=>false,'message'=>'Missing notification id']); return; }
        require_once __DIR__ . '/../models/NotificationsModel.php';
        $notifModel = new NotificationsModel();
        $ok = $notifModel->deleteNotification($notifId, $userId);
        echo json_encode(['success' => (bool)$ok]);
    }

    /**
     * Clear read notifications (AJAX)
     */
    private function clearNotificationsAjax() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); return; }
        $userId = (int)$_SESSION['user_id'];
        // optional: accept olderThanDays param
        $older = isset($_POST['older_than_days']) ? (int)$_POST['older_than_days'] : 0;
        require_once __DIR__ . '/../models/NotificationsModel.php';
        $notifModel = new NotificationsModel();
        $ok = $notifModel->deleteReadNotifications($userId, $older);
        echo json_encode(['success' => (bool)$ok]);
    }

    /**
     * Create a new post in a group
     */
    private function createPost() {
        // Don't set headers or ob here - handleAjax already did
        try {
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                return;
            }
            
            $userId = (int)$_SESSION['user_id'];
            $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
            $content = isset($_POST['content']) ? trim($_POST['content']) : '';
            $postType = isset($_POST['post_type']) ? trim($_POST['post_type']) : 'discussion';
            
            if (!$groupId || !$content) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                return;
            }
            
            // Check if user is a member of the group
            $membershipState = $this->groupModel->getUserMembershipState($groupId, $userId);
            
            if ($membershipState !== 'joined') {
                echo json_encode(['success' => false, 'message' => 'You must be a member to post']);
                return;
            }
        
        // Handle image upload
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/posts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'post_' . $groupId . '_' . $userId . '_' . time() . '.' . $extension;
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imagePath = 'uploads/posts/' . $filename;
            }
        }
        
        // Handle file upload (for resources)
        $filePath = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/resources/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $filename = 'resource_' . $groupId . '_' . $userId . '_' . time() . '.' . $extension;
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                $filePath = 'uploads/resources/' . $filename;
            }
        }
        
        $postTypeModel = PostTypeFactory::make($postType);
        $postId = $postTypeModel->create($userId, $groupId, [
            'content' => $content,
            'image_path' => $imagePath,
            'file_path' => $filePath,
            'request' => $_POST
        ]);
        
        if ($postId) {
            echo json_encode([
                'success' => true,
                'message' => 'Post created successfully',
                'post_id' => $postId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create post in database']);
        }
        
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * Record a poll vote for the authenticated user
     */
    private function votePollOption() {
        try {
            error_log('votePollOption called');
            
            if (!isset($_SESSION['user_id'])) {
                error_log('votePollOption: User not authenticated');
                echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                return;
            }

            $userId = (int)$_SESSION['user_id'];
            $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
            $optionIndex = isset($_POST['option_index']) ? (int)$_POST['option_index'] : -1;
            
            error_log("votePollOption: userId=$userId, postId=$postId, optionIndex=$optionIndex");

            if ($postId <= 0 || $optionIndex < 0) {
                error_log('votePollOption: Invalid poll data');
                echo json_encode(['success' => false, 'message' => 'Invalid poll data']);
                return;
            }

            $groupPostModel = new GroupPostModel();
            $post = $groupPostModel->getGroupPostById($postId, $userId);
            
            error_log('votePollOption: Post retrieved: ' . json_encode($post ? ['id' => $post['post_id'], 'type' => $post['group_post_type'] ?? 'unknown'] : null));

            if (!$post || ($post['group_post_type'] ?? '') !== 'poll' || empty($post['group_id'])) {
                error_log('votePollOption: Poll not found or invalid');
                echo json_encode(['success' => false, 'message' => 'Poll not found']);
                return;
            }

            $groupId = (int)$post['group_id'];
            $isMember = $this->groupModel->isMember($groupId, $userId);
            error_log("votePollOption: isMember = " . ($isMember ? 'true' : 'false'));
            
            if (!$isMember) {
                echo json_encode(['success' => false, 'message' => 'Join the group to vote']);
                return;
            }

            $metadata = is_array($post['metadata']) ? $post['metadata'] : [];
            $options = $metadata['options'] ?? [];
            
            error_log('votePollOption: Options count = ' . count($options));
            
            if (!isset($options[$optionIndex])) {
                error_log('votePollOption: Invalid poll option index');
                echo json_encode(['success' => false, 'message' => 'Invalid poll option']);
                return;
            }

            $pollModel = PostTypeFactory::make('poll');
            $counts = $pollModel->recordVote($postId, $userId, $optionIndex, count($options));
            
            error_log('votePollOption: Vote counts after recording: ' . json_encode($counts));
            
            echo json_encode([
                'success' => true,
                'selected' => $optionIndex,
                'votes' => $counts
            ]);
            error_log('votePollOption: Success response sent');
        } catch (Exception $e) {
            error_log('votePollOption error: ' . $e->getMessage());
            error_log('votePollOption stack trace: ' . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }

    private function fetchPollVotes() {
        try {
            $userId = (int)$_SESSION['user_id'];
            $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
            if ($postId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid poll reference']);
                return;
            }

            $groupPostModel = new GroupPostModel();
            $post = $groupPostModel->getGroupPostById($postId, $userId);
            if (!$post || ($post['group_post_type'] ?? '') !== 'poll' || empty($post['group_id'])) {
                echo json_encode(['success' => false, 'message' => 'Poll not found']);
                return;
            }

            $groupId = (int)$post['group_id'];
            if (!$this->groupModel->isMember($groupId, $userId)) {
                echo json_encode(['success' => false, 'message' => 'Join the group to view poll votes']);
                return;
            }

            $details = $groupPostModel->getPollVoteDetails($postId);

            echo json_encode([
                'success' => true,
                'options' => $details['options'],
                'total_votes' => $details['total_votes']
            ]);
        } catch (Exception $e) {
            error_log('fetchPollVotes error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Unable to load voters']);
        }
    }

    /**
     * Return pending requests as JSON (for AJAX refresh on manage page)
     */
    private function fetchPendingRequestsAjax() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); return; }
        $userId = (int)$_SESSION['user_id'];
        $groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : (isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0);
        if (!$groupId) { echo json_encode(['success'=>false,'message'=>'Missing group id']); return; }

        // Only admins may fetch pending requests
        if (!$this->groupModel->isGroupAdmin($groupId, $userId)) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); return; }

        $pending = $this->groupModel->getPendingRequests($groupId);
        echo json_encode(['success' => true, 'requests' => $pending]);
    }

}
