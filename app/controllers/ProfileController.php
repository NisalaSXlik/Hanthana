<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/FriendModel.php';
require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/GroupPostModel.php';
require_once __DIR__ . '/../models/SettingsModel.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';

class ProfileController {
    private $userModel;
    private $postModel;
    private $friendModel;
    private $groupModel;
    private $groupPostModel;
    private $settingsModel;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->postModel = new PostModel();
        $this->friendModel = new FriendModel();
        $this->groupModel = new GroupModel();
        $this->groupPostModel = new GroupPostModel();
        $this->settingsModel = new SettingsModel();
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Landing&action=index');
            exit();
        }

        $viewerId = $_SESSION['user_id'];
        $profileUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $viewerId;
        
        // If no user_id specified and user is viewing own profile
        if (!isset($_GET['user_id'])) {
            $profileUserId = $viewerId;
        }

        $profileUser = $this->userModel->findById($profileUserId);
        if (!$profileUser) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Feed&action=index&error=user_not_found');
            exit();
        }

        $isOwner = ($viewerId === $profileUserId);
        
        // Get user's display information
        $displayName = trim(($profileUser['first_name'] ?? '') . ' ' . ($profileUser['last_name'] ?? ''));
        if ($displayName === '') {
            $displayName = $profileUser['username'] ?? 'Unknown User';
        }
        $displayHandle = !empty($profileUser['username']) ? '@' . $profileUser['username'] : '';
        
        // Get user details
        $bio = $profileUser['bio'] ?? '';
        $location = $profileUser['location'] ?? 'Not specified';
        $university = $profileUser['university'] ?? 'Not specified';

        $rawEmail = $profileUser['email'] ?? '';
        $rawPhone = $profileUser['phone_number'] ?? '';
        $canViewEmail = $this->settingsModel->shouldShowEmail($profileUserId, $viewerId);
        $canViewPhone = $this->settingsModel->shouldShowPhone($profileUserId, $viewerId);
        $email = $canViewEmail ? $rawEmail : '';
        $phone = $canViewPhone ? $rawPhone : '';
        
        // Format date of birth
        $dob = '';
        $dobValue = '';
        if (!empty($profileUser['date_of_birth'])) {
            $dob = date('F j, Y', strtotime($profileUser['date_of_birth']));
            $dobValue = $profileUser['date_of_birth'];
        }
        
        $joinedAt = date('F Y', strtotime($profileUser['created_at'] ?? 'now'));
        
        // Get interest tags
        $interestTags = [];
        if (!empty($profileUser['interests'])) {
            $interestTags = explode(',', $profileUser['interests']);
            $interestTags = array_map('trim', $interestTags);
            $interestTags = array_filter($interestTags);
        }

        // Get friend information
        $friendsCount = $this->friendModel->getFriendsCount($profileUserId);
        $friendListLimit = 10;
        $friendList = $this->friendModel->getAcceptedFriends($profileUserId, $friendListLimit);
        $friendListCount = count($friendList);
        $hasMoreFriends = $friendsCount > $friendListLimit;

        // Friend relationship status for non-owners
        $friendButtonState = 'none';
        $friendButtonLabel = 'Add Friend';
        $friendButtonIcon = 'uil uil-user-plus';
        $friendButtonVariant = 'btn-primary';
        $friendButtonDisabled = false;
        $canSendFriendRequest = true;

        if (!$isOwner) {
            $friendStatus = $this->friendModel->getFriendshipStatus($viewerId, $profileUserId);
            
            switch ($friendStatus) {
                case 'friends':
                    $friendButtonState = 'friends';
                    $friendButtonLabel = 'Friends';
                    $friendButtonIcon = 'uil uil-user-check';
                    $friendButtonVariant = 'btn-secondary';
                    $canSendFriendRequest = false;
                    break;
                case 'pending_them':
                    $friendButtonState = 'pending_outgoing';
                    $friendButtonLabel = 'Request Sent';
                    $friendButtonIcon = 'uil uil-clock';
                    $friendButtonVariant = 'btn-secondary';
                    $friendButtonDisabled = true;
                    $canSendFriendRequest = false;
                    break;
                case 'pending_me':
                    $friendButtonState = 'incoming_pending';
                    $friendButtonLabel = 'Request Pending';
                    $friendButtonIcon = 'uil uil-user-plus';
                    $friendButtonVariant = 'btn-secondary';
                    $friendButtonDisabled = true;
                    $canSendFriendRequest = false;
                    break;
                default:
                    $friendButtonState = 'none';
                    $friendButtonLabel = 'Add Friend';
                    $friendButtonIcon = 'uil uil-user-plus';
                    $friendButtonVariant = 'btn-primary';
                    $canSendFriendRequest = true;
            }
        }

        // Get posts with privacy check
        $postVisibility = $profileUser['post_visibility'] ?? 'public';
        $profileVisibility = $profileUser['profile_visibility'] ?? 'public';
        
        $postsArePrivate = false;
        $personalPosts = [];
        $personalPostCount = 0;
        
        if ($isOwner) {
            // Owner can see all their posts
            $personalPosts = $this->postModel->getUserPosts($profileUserId);
            $personalPostCount = $this->postModel->getUserPostsCount($profileUserId);
        } else {
            // Check if viewer can see posts based on privacy settings
            if ($postVisibility === 'only_me') {
                $postsArePrivate = true;
            } elseif ($postVisibility === 'friends_only') {
                $areFriends = $this->friendModel->areFriends($viewerId, $profileUserId);
                if (!$areFriends) {
                    $postsArePrivate = true;
                } else {
                    $personalPosts = $this->postModel->getUserPosts($profileUserId);
                    $personalPostCount = $this->postModel->getUserPostsCount($profileUserId);
                }
            } else {
                // Public posts
                $personalPosts = $this->postModel->getUserPosts($profileUserId);
                $personalPostCount = $this->postModel->getUserPostsCount($profileUserId);
            }
        }

        // Get group posts with privacy check
        $groupPosts = [];
        $groupPostCount = 0;
        
        if (!$postsArePrivate || $isOwner) {
            $groupPosts = $this->groupPostModel->getUserGroupPosts($profileUserId);
            $groupPostCount = count($groupPosts);
        }

        // Calculate total posts count (personal + group)
        $totalPostsCount = $personalPostCount + $groupPostCount;

        // Get photo posts (posts with images)
        $photoPosts = [];
        if (!$postsArePrivate || $isOwner) {
            $photoPosts = $this->postModel->getUserPhotoPosts($profileUserId);
        }

        // Get incoming friend requests for the viewer (for navbar)
        $incomingFriendRequests = $this->friendModel->getIncomingRequests($viewerId);

        // GET REAL GROUP DATA - NO MORE DUMMY DATA
        $joinedGroupsCount = $this->groupModel->getUserJoinedGroupsCount($profileUserId);
        $userGroups = $this->groupModel->getUserGroupsWithDetails($profileUserId, 5);

        // Pass all variables to view
        require __DIR__ . '/../views/userprofileview.php';
    }

    /**
     * Alias for index() to support action=view in URLs
     */
    public function view() {
        $this->index();
    }

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
            case 'update_profile':
                $this->updateProfile($input);
                break;
            case 'upload_avatar':
                $this->uploadAvatar();
                break;
            case 'upload_cover':
                $this->uploadCover();
                break;
            case 'add_friend':
                $this->addFriend($input);
                break;
            case 'accept_friend_request':
                $this->acceptFriendRequest($input);
                break;
            case 'decline_friend_request':
                $this->declineFriendRequest($input);
                break;
            case 'remove_friend':
                $this->removeFriend($input);
                break;
            case 'cancel_friend_request':
                $this->cancelFriendRequest($input);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        exit;
    }

    private function updateProfile($data) {
        try {
            $userId = $_SESSION['user_id'];
            
            // Log incoming data for debugging
            error_log('UpdateProfile - User ID: ' . $userId);
            error_log('UpdateProfile - Data: ' . print_r($data, true));
            
            $updateData = [];
            $fields = ['first_name', 'last_name', 'username', 'email', 'phone_number', 'date_of_birth', 'location', 'university', 'bio'];
            
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $value = trim($data[$field]);
                    // Skip empty date_of_birth to avoid SQL errors
                    if ($field === 'date_of_birth' && $value === '') {
                        $updateData[$field] = null;
                    } else {
                        $updateData[$field] = $value;
                    }
                }
            }

            // Validate required fields
            if (empty($updateData['first_name']) || empty($updateData['last_name']) || empty($updateData['username']) || empty($updateData['email'])) {
                error_log('UpdateProfile - Validation failed: Missing required fields');
                echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
                return;
            }

            // Check if username is already taken by another user
            $existingUser = $this->userModel->findByUsername($updateData['username']);
            if ($existingUser && $existingUser['user_id'] != $userId) {
                error_log('UpdateProfile - Username already taken: ' . $updateData['username']);
                echo json_encode(['success' => false, 'message' => 'Username is already taken']);
                return;
            }

            // Check if email is already taken by another user
            $existingEmail = $this->userModel->findByEmail($updateData['email']);
            if ($existingEmail && $existingEmail['user_id'] != $userId) {
                error_log('UpdateProfile - Email already taken: ' . $updateData['email']);
                echo json_encode(['success' => false, 'message' => 'Email is already taken']);
                return;
            }

            // Handle profile picture upload if provided
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/user_dp/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array(strtolower($fileExtension), $allowedExtensions)) {
                    $fileName = 'user_' . $userId . '_dp_' . time() . '.' . $fileExtension;
                    $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filePath)) {
                        $updateData['profile_picture'] = 'uploads/user_dp/' . $fileName;
                    }
                }
            }

            // Handle cover photo upload if provided
            if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/user_cover/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileExtension = pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array(strtolower($fileExtension), $allowedExtensions)) {
                    $fileName = 'user_' . $userId . '_cover_' . time() . '.' . $fileExtension;
                    $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['cover_photo']['tmp_name'], $filePath)) {
                        $updateData['cover_photo'] = 'uploads/user_cover/' . $fileName;
                    }
                }
            }

            error_log('UpdateProfile - Calling updateUser with data: ' . print_r($updateData, true));
            $success = $this->userModel->updateUser($userId, $updateData);
            error_log('UpdateProfile - Update result: ' . ($success ? 'true' : 'false'));
            
            if ($success) {
                $response = [
                    'success' => true, 
                    'message' => 'Profile updated successfully',
                    'user' => [
                        'first_name' => $updateData['first_name'],
                        'last_name' => $updateData['last_name'],
                        'username' => $updateData['username'],
                        'email' => $updateData['email']
                    ]
                ];
                
                if (isset($updateData['profile_picture'])) {
                    $response['profile_picture'] = BASE_PATH . $updateData['profile_picture'];
                }
                if (isset($updateData['cover_photo'])) {
                    $response['cover_photo'] = BASE_PATH . $updateData['cover_photo'];
                }
                
                echo json_encode($response);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
            }
        } catch (Exception $e) {
            error_log('Update profile error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    private function uploadAvatar() {
        try {
            $userId = $_SESSION['user_id'];
            
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
                return;
            }

            $uploadDir = __DIR__ . '/../../public/uploads/user_dp/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
                return;
            }

            $fileName = 'user_' . $userId . '_dp_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
                $relativePath = 'uploads/user_dp/' . $fileName;
                
                // Update user profile picture in database
                $success = $this->userModel->updateUser($userId, ['profile_picture' => $relativePath]);
                
                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Profile picture updated successfully',
                        'image_url' => BASE_PATH . $relativePath
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update profile picture in database']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            }
        } catch (Exception $e) {
            error_log('Upload avatar error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    private function uploadCover() {
        try {
            $userId = $_SESSION['user_id'];
            
            if (!isset($_FILES['cover']) || $_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
                return;
            }

            $uploadDir = __DIR__ . '/../../public/uploads/user_cover/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileExtension = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
                return;
            }

            $fileName = 'user_' . $userId . '_cover_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['cover']['tmp_name'], $filePath)) {
                $relativePath = 'uploads/user_cover/' . $fileName;
                
                // Update user cover photo in database
                $success = $this->userModel->updateUser($userId, ['cover_photo' => $relativePath]);
                
                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Cover photo updated successfully',
                        'image_url' => BASE_PATH . $relativePath
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update cover photo in database']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            }
        } catch (Exception $e) {
            error_log('Upload cover error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    private function addFriend($data) {
        try {
            $userId = $_SESSION['user_id'];
            $friendId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
            
            if (!$friendId) {
                echo json_encode(['success' => false, 'message' => 'Invalid user']);
                return;
            }

            if ($userId === $friendId) {
                echo json_encode(['success' => false, 'message' => 'Cannot add yourself as a friend']);
                return;
            }

            $result = $this->friendModel->sendFriendRequest($userId, $friendId);
            
            if ($result === true) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Friend request sent successfully',
                    'state' => 'pending'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => $result]);
            }
        } catch (Exception $e) {
            error_log('Add friend error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    private function acceptFriendRequest($data) {
        try {
            $userId = $_SESSION['user_id'];
            $friendId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
            
            if (!$friendId) {
                echo json_encode(['success' => false, 'message' => 'Invalid user']);
                return;
            }

            $success = $this->friendModel->acceptFriendRequest($friendId, $userId);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Friend request accepted',
                    'state' => 'friends'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to accept friend request']);
            }
        } catch (Exception $e) {
            error_log('Accept friend request error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    private function declineFriendRequest($data) {
        try {
            $userId = $_SESSION['user_id'];
            $friendId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
            
            if (!$friendId) {
                echo json_encode(['success' => false, 'message' => 'Invalid user']);
                return;
            }

            $success = $this->friendModel->declineFriendRequest($friendId, $userId);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Friend request declined',
                    'state' => 'add'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to decline friend request']);
            }
        } catch (Exception $e) {
            error_log('Decline friend request error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    private function removeFriend($data) {
        try {
            $userId = $_SESSION['user_id'];
            $friendId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
            
            if (!$friendId) {
                echo json_encode(['success' => false, 'message' => 'Invalid user']);
                return;
            }

            $success = $this->friendModel->removeFriend($userId, $friendId);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Friend removed successfully',
                    'state' => 'add'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove friend']);
            }
        } catch (Exception $e) {
            error_log('Remove friend error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    private function cancelFriendRequest($data) {
        try {
            $userId = $_SESSION['user_id'];
            $friendId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
            
            if (!$friendId) {
                echo json_encode(['success' => false, 'message' => 'Invalid user']);
                return;
            }

            $success = $this->friendModel->cancelFriendRequest($userId, $friendId);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Friend request cancelled',
                    'state' => 'add'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to cancel friend request']);
            }
        } catch (Exception $e) {
            error_log('Cancel friend request error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }
}
?>