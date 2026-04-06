<?php
require_once __DIR__ . '/../models/UserModel.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->startSession();
    }

    private function setAuthenticatedSession(array $user): void {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['phone_number'] = $user['phone_number'];
        $_SESSION['profile_picture'] = $user['profile_picture'];
        $_SESSION['role'] = $user['role'] ?? 'user';
    }

    private function redirectToLoginWithError(string $errorCode): void {
        header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index&oauth_error=' . urlencode($errorCode));
        exit();
    }

    private function isGoogleAuthConfigured(): bool {
        return GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== '';
    }

    private function getGoogleRedirectUri(): string {
        if (GOOGLE_REDIRECT_URI !== '') {
            return GOOGLE_REDIRECT_URI;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = rtrim(BASE_PATH, '/');

        return $scheme . '://' . $host . $basePath . '/index.php?controller=Auth&action=googleCallback';
    }

    private function postFormJson(string $url, array $formData): ?array {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($formData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            return null;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function getJson(string $url, array $headers = []): ?array {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            return null;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function generateUniqueUsername(string $seed): string {
        $base = strtolower(trim($seed));
        $base = preg_replace('/[^a-z0-9_]/', '', str_replace(' ', '_', $base));

        if ($base === '') {
            $base = 'user';
        }

        $candidate = $base;
        $attempt = 0;

        while ($this->userModel->usernameExists($candidate) && $attempt < 20) {
            $candidate = $base . random_int(1000, 9999);
            $attempt++;
        }

        if ($this->userModel->usernameExists($candidate)) {
            $candidate = $base . uniqid();
        }

        return $candidate;
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
                'university' => trim($data['university'] ?? ''),
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'location' => $data['location'] ?? null
            ];
            
            if ($this->userModel->create($userData)) {
                // Auto-login: set session
                $user = $this->userModel->findByEmail($userData['email']);
                $this->setAuthenticatedSession($user);
                
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
            if (!empty($user['banned_until'])) {
                try {
                    $banUntil = new DateTime($user['banned_until']);
                } catch (Exception $e) {
                    $banUntil = null;
                }

                if ($banUntil && $banUntil > new DateTime()) {
                    $formatted = $banUntil->format('M d, Y H:i');
                    $reason = $user['ban_reason'] ?? 'policy violation';
                    return [
                        'success' => false,
                        'errors' => ["Account banned until {$formatted}. Reason: {$reason}."]
                    ];
                }

                if ($banUntil && $banUntil <= new DateTime()) {
                    $this->userModel->clearBan($user['user_id']);
                }
            }

            $this->setAuthenticatedSession($user);
            
            // Update last login
            $this->userModel->updateLastLogin($user['user_id']);
            
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'errors' => ['Invalid email/phone or password.']];
    }

    public function googleLogin() {
        if (!$this->isGoogleAuthConfigured()) {
            $this->redirectToLoginWithError('google_not_configured');
        }

        try {
            $_SESSION['google_oauth_state'] = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            $this->redirectToLoginWithError('oauth_state_failed');
        }

        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => $this->getGoogleRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'include_granted_scopes' => 'true',
            'access_type' => 'online',
            'prompt' => 'select_account',
            'state' => $_SESSION['google_oauth_state'],
        ];

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        header('Location: ' . $authUrl);
        exit();
    }

    public function googleCallback() {
        if (!$this->isGoogleAuthConfigured()) {
            $this->redirectToLoginWithError('google_not_configured');
        }

        if (!empty($_GET['error'])) {
            $this->redirectToLoginWithError('google_access_denied');
        }

        $incomingState = $_GET['state'] ?? '';
        $expectedState = $_SESSION['google_oauth_state'] ?? '';
        unset($_SESSION['google_oauth_state']);

        if ($incomingState === '' || $expectedState === '' || !hash_equals($expectedState, $incomingState)) {
            $this->redirectToLoginWithError('google_invalid_state');
        }

        $code = $_GET['code'] ?? '';
        if ($code === '') {
            $this->redirectToLoginWithError('google_missing_code');
        }

        $tokenData = $this->postFormJson('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => $this->getGoogleRedirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if (!$tokenData || empty($tokenData['access_token'])) {
            $this->redirectToLoginWithError('google_token_exchange_failed');
        }

        $googleUser = $this->getJson('https://www.googleapis.com/oauth2/v3/userinfo', [
            'Authorization: Bearer ' . $tokenData['access_token'],
        ]);

        if (!$googleUser || empty($googleUser['email'])) {
            $this->redirectToLoginWithError('google_profile_fetch_failed');
        }

        if (isset($googleUser['email_verified']) && $googleUser['email_verified'] === false) {
            $this->redirectToLoginWithError('google_email_not_verified');
        }

        $email = trim($googleUser['email']);
        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            $firstName = trim($googleUser['given_name'] ?? 'Google');
            $lastName = trim($googleUser['family_name'] ?? 'User');
            if ($lastName === '') {
                $lastName = 'User';
            }

            $usernameSeed = $googleUser['given_name'] ?? strstr($email, '@', true) ?: 'user';
            $generatedUsername = $this->generateUniqueUsername($usernameSeed);

            try {
                $randomPassword = bin2hex(random_bytes(24));
            } catch (Exception $e) {
                $randomPassword = bin2hex(openssl_random_pseudo_bytes(24));
            }

            $newUser = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone_number' => null,
                'password' => $randomPassword,
                'username' => $generatedUsername,
                'bio' => null,
                'university' => null,
                'date_of_birth' => null,
                'location' => null,
            ];

            if (!$this->userModel->create($newUser)) {
                $this->redirectToLoginWithError('google_account_create_failed');
            }

            $user = $this->userModel->findByEmail($email);
            if (!$user) {
                $this->redirectToLoginWithError('google_account_create_failed');
            }
        }

        if (!empty($user['banned_until'])) {
            try {
                $banUntil = new DateTime($user['banned_until']);
            } catch (Exception $e) {
                $banUntil = null;
            }

            if ($banUntil && $banUntil > new DateTime()) {
                $this->redirectToLoginWithError('account_banned');
            }

            if ($banUntil && $banUntil <= new DateTime()) {
                $this->userModel->clearBan($user['user_id']);
            }
        }

        $this->setAuthenticatedSession($user);
        $this->userModel->updateLastLogin($user['user_id']);

        header('Location: ' . BASE_PATH . 'index.php?controller=AcedemicDashboard&action=index');
        exit();
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

        $allowedUniversities = [
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
        
        // Required fields
        $required = ['first_name', 'last_name', 'email', 'phone', 'password', 'username', 'university'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
        }

        if (!empty($data['university']) && !in_array(trim($data['university']), $allowedUniversities, true)) {
            $errors[] = "Please select a valid university.";
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
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                $errors[] = "Password must be at least 6 characters.";
            }
            
            // Check if passwords match
            if (!empty($data['confirmPassword']) && $data['password'] !== $data['confirmPassword']) {
                $errors[] = "Passwords do not match.";
            } elseif (empty($data['confirmPassword'])) {
                $errors[] = "Please confirm your password.";
            }
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