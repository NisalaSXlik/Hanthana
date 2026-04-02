<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/SettingsModel.php';

class SettingsController {
    private $userModel;
    private $settingsModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->settingsModel = new SettingsModel();
    }
    
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit();
        }
        
        $userId = $_SESSION['user_id'];
        $user = $this->userModel->findById($userId);
        
        if (!$user) {
            header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
            exit();
        }
        
        // Get user settings
        $userSettings = $this->settingsModel->getUserSettings($userId);
        
        // Get friend requests for sidebar using your actual FriendModel method
        $friendRequests = [];
        try {
            if (file_exists(__DIR__ . '/../models/FriendModel.php')) {
                require_once __DIR__ . '/../models/FriendModel.php';
                $friendModel = new FriendModel();
                // Use the correct method name from your FriendModel
                $friendRequests = $friendModel->getIncomingRequests($userId, 5); // Limit to 5 requests
            }
        } catch (Exception $e) {
            // Log error but don't break the page
            error_log("FriendModel error: " . $e->getMessage());
            $friendRequests = [];
        }
        
        // Include the view
        require_once __DIR__ . '/../views/settings.php';
    }
    
    public function updateProfile() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }
        
        $userId = $_SESSION['user_id'];
        $data = $_POST;
        
        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
            echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
            exit();
        }
        
        // Check if email is already taken by another user
        $existingUser = $this->userModel->findByEmail($data['email']);
        if ($existingUser && $existingUser['user_id'] != $userId) {
            echo json_encode(['success' => false, 'message' => 'Email is already taken']);
            exit();
        }
        
        // Check if username is already taken by another user
        if (!empty($data['username'])) {
            $existingUser = $this->userModel->findByUsername($data['username']);
            if ($existingUser && $existingUser['user_id'] != $userId) {
                echo json_encode(['success' => false, 'message' => 'Username is already taken']);
                exit();
            }
        }
        
        try {
            $result = $this->userModel->updateUser($userId, [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'] ?? null,
                'bio' => $data['bio'] ?? null,
                'university' => $data['university'] ?? null,
                'location' => $data['location'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function updatePassword() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }
        
        $userId = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        
        if (empty($currentPassword)) {
            echo json_encode(['success' => false, 'message' => 'Current password is required']);
            exit();
        }
        
        if (empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'New password is required']);
            exit();
        }
        
        if (strlen($newPassword) < 8) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long']);
            exit();
        }
        
        // Verify current password
        $user = $this->userModel->findById($userId);
        if (!password_verify($currentPassword, $user['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit();
        }
        
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $result = $this->userModel->updatePassword($userId, $hashedPassword);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update password']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function updatePrivacy() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }
        
        $userId = $_SESSION['user_id'];
        $data = $_POST;
        
        try {
            $result = $this->settingsModel->updatePrivacySettings($userId, [
                'profile_visibility' => $data['profile_visibility'] ?? 'friends',
                'post_visibility' => $data['post_visibility'] ?? 'friends',
                'friend_request_visibility' => $data['friend_request_visibility'] ?? 'everyone',
                'show_email' => isset($data['show_email']) ? 1 : 0,
                'show_phone' => isset($data['show_phone']) ? 1 : 0
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Privacy settings updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update privacy settings']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function updateNotifications() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }
        
        $userId = $_SESSION['user_id'];
        $data = $_POST;
        
        try {
            $result = $this->settingsModel->updateNotificationSettings($userId, [
                'email_comments' => isset($data['email_comments']) ? 1 : 0,
                'email_likes' => isset($data['email_likes']) ? 1 : 0,
                'email_friend_requests' => isset($data['email_friend_requests']) ? 1 : 0,
                'email_messages' => isset($data['email_messages']) ? 1 : 0,
                'email_group_activity' => isset($data['email_group_activity']) ? 1 : 0,
                'push_enabled' => isset($data['push_enabled']) ? 1 : 0
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Notification settings updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update notification settings']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function updateAppearance() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }
        
        $userId = $_SESSION['user_id'];
        $data = $_POST;
        
        try {
            $result = $this->settingsModel->updateAppearanceSettings($userId, [
                'theme' => $data['theme'] ?? 'light',
                'font_size' => $data['font_size'] ?? 'medium'
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Appearance settings updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update appearance settings']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function getBlockedUsers() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }
        
        $userId = $_SESSION['user_id'];
        $blockedUsers = $this->settingsModel->getBlockedUsers($userId);
        
        echo json_encode([
            'success' => true,
            'users' => $blockedUsers
        ]);
    }
    
    public function unblockUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }
        
        $userId = $_SESSION['user_id'];
        $blockedUserId = $_POST['user_id'] ?? null;
        
        if (!$blockedUserId) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit();
        }
        
        try {
            $result = $this->settingsModel->unblockUser($userId, $blockedUserId);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'User unblocked successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to unblock user']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
?>