<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/SettingsModel.php';

class SettingsController {
    private $userModel;
    private $settingsModel;

    private function isValidUniversityEmail(string $email): bool {
        return (bool) preg_match('/^[^@\s]+@[a-z0-9-]+(?:\.[a-z0-9-]+)*\.ac\.lk$/i', $email);
    }

    private function getAllowedUniversities(): array {
        return [
            'University of Colombo',
            'University of Peradeniya',
            'University of Moratuwa',
            'University of Sri Jayewardenepura',
            'University of Kelaniya',
            'University of Ruhuna',
            'University of Jaffna',
            'Uva Wellassa University',
            'Rajarata University of Sri Lanka',
            'Sabaragamuwa University of Sri Lanka',
            'South Eastern University of Sri Lanka',
            'Eastern University Sri Lanka',
            'Wayamba University of Sri Lanka',
            'University of Vavuniya'
        ];
    }
    
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

        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }
        
        $userId = (int)$_SESSION['user_id'];
        $data = $_POST;

        $firstName = trim((string)($data['first_name'] ?? ''));
        $lastName = trim((string)($data['last_name'] ?? ''));
        $username = trim((string)($data['username'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $phoneNumber = trim((string)($data['phone_number'] ?? ''));
        $bio = trim((string)($data['bio'] ?? ''));
        $university = trim((string)($data['university'] ?? ''));
        $location = trim((string)($data['location'] ?? ''));
        $dateOfBirthRaw = trim((string)($data['date_of_birth'] ?? ''));
        
        // Validate required fields
        if ($firstName === '' || $lastName === '' || $email === '') {
            echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$this->isValidUniversityEmail($email)) {
            echo json_encode([
                'success' => false,
                'message' => 'Please use a university email ending with .ac.lk (e.g., 2023cs140@stu.ucsc.cmb.ac.lk).'
            ]);
            exit();
        }

        if ($username === '') {
            echo json_encode(['success' => false, 'message' => 'Username is required']);
            exit();
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores']);
            exit();
        }
        
        // Check if email is already taken by another user
        if ($this->userModel->emailExists($email, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Email is already taken']);
            exit();
        }
        
        // Check if username is already taken by another user
        if ($this->userModel->usernameExists($username, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Username is already taken']);
            exit();
        }

        if ($phoneNumber !== '') {
            if (!preg_match('/^[0-9]{10}$/', $phoneNumber)) {
                echo json_encode(['success' => false, 'message' => 'Phone number must be 10 digits']);
                exit();
            }

            if ($this->userModel->phoneExists($phoneNumber, $userId)) {
                echo json_encode(['success' => false, 'message' => 'Phone number is already taken']);
                exit();
            }
        }

        if ($university !== '' && !in_array($university, $this->getAllowedUniversities(), true)) {
            echo json_encode(['success' => false, 'message' => 'Please select a valid university']);
            exit();
        }

        $dateOfBirth = null;
        if ($dateOfBirthRaw !== '') {
            $dateOfBirthParsed = DateTime::createFromFormat('Y-m-d', $dateOfBirthRaw);
            $dateErrors = DateTime::getLastErrors();
            $hasDateErrors = is_array($dateErrors)
                && (($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0);

            if (!$dateOfBirthParsed || $hasDateErrors || $dateOfBirthParsed->format('Y-m-d') !== $dateOfBirthRaw) {
                echo json_encode(['success' => false, 'message' => 'Please provide a valid date of birth']);
                exit();
            }

            $dateOfBirth = $dateOfBirthRaw;
        }
        
        try {
            $result = $this->userModel->updateUser($userId, [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => $username,
                'email' => $email,
                'phone_number' => $phoneNumber !== '' ? $phoneNumber : null,
                'bio' => $bio !== '' ? $bio : null,
                'university' => $university !== '' ? $university : null,
                'location' => $location !== '' ? $location : null,
                'date_of_birth' => $dateOfBirth
            ]);
            
            if ($result) {
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['phone_number'] = $phoneNumber !== '' ? $phoneNumber : null;
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

    public function deleteAccount() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }

        $userId = (int)$_SESSION['user_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $confirmText = strtoupper(trim($_POST['confirm_text'] ?? ''));

        if ($currentPassword === '') {
            echo json_encode(['success' => false, 'message' => 'Current password is required']);
            exit();
        }

        if ($confirmText !== 'DELETE') {
            echo json_encode(['success' => false, 'message' => 'Please type DELETE to confirm']);
            exit();
        }

        $user = $this->userModel->findById($userId);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit();
        }

        try {
            $deleted = $this->userModel->delete($userId);
            if (!$deleted) {
                echo json_encode(['success' => false, 'message' => 'Failed to delete account']);
                exit();
            }

            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();

            echo json_encode([
                'success' => true,
                'message' => 'Account deleted successfully',
                'redirect' => BASE_PATH . 'index.php?controller=Login&action=index'
            ]);
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

    public function blockUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }

        $userId = (int)$_SESSION['user_id'];
        $blockedUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

        if ($blockedUserId <= 0) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit();
        }

        if ($blockedUserId === $userId) {
            echo json_encode(['success' => false, 'message' => 'You cannot block yourself']);
            exit();
        }

        try {
            $result = $this->settingsModel->blockUser($userId, $blockedUserId);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'User blocked successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to block user']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
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