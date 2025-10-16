
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
    
    // Handle registration
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->validateRegistration($_POST);
            
            if (empty($errors)) {
                $data = [
                    'first_name' => trim($_POST['first_name']),
                    'last_name' => trim($_POST['last_name']),
                    'email' => trim($_POST['email']),
                    'password' => $_POST['password'],
                    'username' => trim($_POST['username']),
                    'bio' => trim($_POST['bio'] ?? ''),
                    'university' => trim($_POST['university'] ?? ''),
                    'date_of_birth' => $_POST['date_of_birth'] ?? null,
                    'location' => trim($_POST['location'] ?? '')
                ];
                
                if ($this->userModel->register($data)) {
                    //$_SESSION['success'] = "Registration successful! Please login.";
                    // Don't redirect - just show success message
                      return true;
                    //header('Location: login.php');
                    //exit;
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
            }
            
            // Return errors for display
            return $errors;
        }
    }
    
    // Handle login
    public function login($email, $password) {
        $user = $this->userModel->findByEmail($email);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            
            // Update last login
            $this->userModel->updateLastLogin($user['user_id']);
            
            return true;
        }
        return false;
    }
    
    // Handle logout
    public function logout() {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Get current user
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return $this->userModel->findById($_SESSION['user_id']);
        }
        return null;
    }
    
    // Update user profile
    public function updateProfile($user_id, $data) {
        $errors = $this->validateProfileUpdate($user_id, $data);
        
        if (empty($errors)) {
            return $this->userModel->updateProfile($user_id, $data);
        }
        return false;
    }
    
    // Validate registration data
    private function validateRegistration($data) {
        $errors = [];
        
        // Required fields
        $required = ['first_name', 'last_name', 'email', 'password', 'username'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
        }
        
        // Email validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required.";
        } elseif (!empty($data['email']) && $this->userModel->emailExists($data['email'])) {
            $errors[] = "Email already exists.";
        }
        
        // Username validation
        if (!empty($data['username']) && $this->userModel->usernameExists($data['username'])) {
            $errors[] = "Username already exists.";
        }
        
        // Password validation
        if (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
        
        return $errors;
    }
    
    // Validate profile update
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
        
        // Check unique fields
        if (!empty($data['email']) && $this->userModel->emailExists($data['email'], $user_id)) {
            $errors[] = "Email already exists.";
        }
        if (!empty($data['username']) && $this->userModel->usernameExists($data['username'], $user_id)) {
            $errors[] = "Username already exists.";
        }
        
        return $errors;
    }
}
?>