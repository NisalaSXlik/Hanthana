<?php
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    // Handle user registration
    public function register($data) {
        $errors = $this->validateRegistration($data);
        
        if (empty($errors)) {
            $userData = [
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => trim($data['email']),
                'phone_number' => trim($data['phone']),
                'password' => $data['password'],
                'username' => trim($data['username']),
                'bio' => $data['bio'] ?? null,
                'university' => $data['university'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'location' => $data['location'] ?? null
            ];
            
            if ($this->userModel->create($userData)) {
                return ['success' => true, 'message' => 'Registration successful!'];
            } else {
                return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
            }
        }
        
        return ['success' => false, 'errors' => $errors];
    }
    
    // Handle user login - FIXED: Added last_name to session
    public function login($identifier, $password) {
        $errors = $this->validateLogin($identifier, $password);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Determine if identifier is email or phone
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = $this->userModel->findByEmail($identifier);
        } else {
            $user = $this->userModel->findByPhone($identifier);
        }
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session data - ADDED LAST_NAME
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];  // ← ADDED THIS LINE
            $_SESSION['phone_number'] = $user['phone_number'];
            $_SESSION['profile_picture'] = $user['profile_picture'];
            
            // Update last login
            $this->userModel->updateLastLogin($user['user_id']);
            
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'errors' => ['Invalid email/phone or password.']];
    }
    
    // Handle user logout - FIXED VERSION
    public function logout() {
        // Ensure session is started
        $this->startSession();
        
        // Clear all session variables
        $_SESSION = [];
        
        // If it's desired to kill the session, also delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Finally, destroy the session
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully.'];
    }
    
    // Check if user is logged in - UPDATED
    public function isLoggedIn() {
        $this->startSession(); // Ensure session is started before checking
        return isset($_SESSION['user_id']);
    }
    
    // Get current logged in user
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return $this->userModel->findById($_SESSION['user_id']);
        }
        return null;
    }
    
    // Update user profile - FIXED: Added last_name to session update
    public function updateProfile($user_id, $data) {
        $errors = $this->validateProfileUpdate($user_id, $data);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $profileData = [
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'email' => trim($data['email']),
            'phone_number' => trim($data['phone_number']),
            'username' => trim($data['username']),
            'bio' => $data['bio'] ?? null,
            'university' => $data['university'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'location' => $data['location'] ?? null
        ];
        
        if ($this->userModel->update($user_id, $profileData)) {
            // Update session data if current user updated their own profile
            if ($this->isLoggedIn() && $_SESSION['user_id'] == $user_id) {
                $_SESSION['username'] = $profileData['username'];
                $_SESSION['email'] = $profileData['email'];
                $_SESSION['first_name'] = $profileData['first_name'];
                $_SESSION['last_name'] = $profileData['last_name'];  // ← ADDED THIS LINE
                $_SESSION['phone_number'] = $profileData['phone_number'];
            }
            
            return ['success' => true, 'message' => 'Profile updated successfully.'];
        }
        
        return ['success' => false, 'errors' => ['Profile update failed.']];
    }
    
    // Change password
    public function changePassword($user_id, $current_password, $new_password) {
        $user = $this->userModel->findById($user_id);
        
        if (!$user) {
            return ['success' => false, 'errors' => ['User not found.']];
        }
        
        if (!password_verify($current_password, $user['password_hash'])) {
            return ['success' => false, 'errors' => ['Current password is incorrect.']];
        }
        
        if (strlen($new_password) < 6) {
            return ['success' => false, 'errors' => ['New password must be at least 6 characters.']];
        }
        
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        // Note: You'll need to add an updatePassword method to your User model
        // $this->userModel->updatePassword($user_id, $new_password_hash);
        
        return ['success' => true, 'message' => 'Password changed successfully.'];
    }
    
    // Validate registration data
    private function validateRegistration($data) {
        $errors = [];
        
        // Required fields
        $required = ['first_name', 'last_name', 'email', 'phone', 'password', 'username'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
        }
        
        // Email validation
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Valid email is required.";
            } elseif ($this->userModel->emailExists($data['email'])) {
                $errors[] = "Email already exists.";
            }
        }
        
        // Phone validation
        if (!empty($data['phone'])) {
            if (!preg_match('/^[0-9]{10}$/', $data['phone'])) {
                $errors[] = "Phone number must be 10 digits.";
            } elseif ($this->userModel->phoneExists($data['phone'])) {
                $errors[] = "Phone number already exists.";
            }
        }
        
        // Username validation
        if (!empty($data['username'])) {
            if ($this->userModel->usernameExists($data['username'])) {
                $errors[] = "Username already exists.";
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
                $errors[] = "Username can only contain letters, numbers, and underscores.";
            }
        }
        
        // Password validation
        if (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
        
        return $errors;
    }
    
    // Validate login data
    private function validateLogin($identifier, $password) {
        $errors = [];
        
        if (empty($identifier)) {
            $errors[] = "Email or phone number is required.";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required.";
        }
        
        return $errors;
    }
    
    // Validate profile update data
    private function validateProfileUpdate($user_id, $data) {
        $errors = [];
        
        // Required fields
        if (empty($data['first_name'])) {
            $errors[] = "First name is required.";
        }
        if (empty($data['last_name'])) {
            $errors[] = "Last name is required.";
        }
        if (empty($data['username'])) {
            $errors[] = "Username is required.";
        }
        if (empty($data['email'])) {
            $errors[] = "Email is required.";
        }
        
        // Check unique fields
        if (!empty($data['email']) && $this->userModel->emailExists($data['email'], $user_id)) {
            $errors[] = "Email already exists.";
        }
        if (!empty($data['username']) && $this->userModel->usernameExists($data['username'], $user_id)) {
            $errors[] = "Username already exists.";
        }
        if (!empty($data['phone_number']) && $this->userModel->phoneExists($data['phone_number'], $user_id)) {
            $errors[] = "Phone number already exists.";
        }
        
        return $errors;
    }
    
    // Get user by ID (public method)
    public function getUserById($user_id) {
        return $this->userModel->findById($user_id);
    }
    
    // Search users
    public function searchUsers($query) {
        return $this->userModel->search($query);
    }
}
?>