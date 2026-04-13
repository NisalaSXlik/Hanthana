<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/PasswordResetModel.php';

class AuthController {
    private $userModel;
    private $passwordResetModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->passwordResetModel = new PasswordResetModel();
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

    private function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function setFlash(string $key, string $message): void {
        $_SESSION[$key] = $message;
    }

    private function getFlash(string $key): string {
        $message = (string)($_SESSION[$key] ?? '');
        unset($_SESSION[$key]);
        return $message;
    }

    private function redirectToForgotPassword(string $email = ''): void {
        $url = BASE_PATH . 'index.php?controller=Auth&action=forgotPassword';
        if ($email !== '') {
            $url .= '&email=' . urlencode($email);
        }

        header('Location: ' . $url);
        exit();
    }

    private function redirectToResetPassword(string $email = ''): void {
        $url = BASE_PATH . 'index.php?controller=Auth&action=resetPassword';
        if ($email !== '') {
            $url .= '&email=' . urlencode($email);
        }

        header('Location: ' . $url);
        exit();
    }

    private function buildPasswordResetCode(): string {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function isSmtpConfigured(): bool {
        return trim((string)SMTP_HOST) !== '' && (int)SMTP_PORT > 0;
    }

    private function smtpReadResponse($socket, array $expectedCodes): bool {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;

            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        if ($response === '') {
            return false;
        }

        $statusCode = (int)substr($response, 0, 3);
        $ok = in_array($statusCode, $expectedCodes, true);

        if (!$ok) {
            error_log('SMTP unexpected response: ' . trim($response));
        }

        return $ok;
    }

    private function smtpWriteCommand($socket, string $command, array $expectedCodes): bool {
        if (fwrite($socket, $command . "\r\n") === false) {
            return false;
        }

        return $this->smtpReadResponse($socket, $expectedCodes);
    }

    private function buildSmtpData(string $toEmail, string $fromAddress, string $fromName, string $subject, string $message): string {
        $safeFromName = trim(preg_replace('/[\r\n]+/', ' ', $fromName));
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $normalizedBody = str_replace(["\r\n", "\r"], "\n", $message);
        $bodyLines = explode("\n", $normalizedBody);
        $safeBodyLines = [];

        foreach ($bodyLines as $line) {
            $safeBodyLines[] = str_starts_with($line, '.') ? '.' . $line : $line;
        }

        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $safeFromName . ' <' . $fromAddress . '>',
            'To: <' . $toEmail . '>',
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'X-Mailer: Hanthana SMTP',
        ];

        return implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $safeBodyLines);
    }

    private function sendMailViaSmtp(string $toEmail, string $fromAddress, string $fromName, string $subject, string $message): bool {
        $host = trim((string)SMTP_HOST);
        $port = max(1, (int)SMTP_PORT);
        $timeout = max(5, (int)SMTP_TIMEOUT);
        $encryption = strtolower(trim((string)SMTP_ENCRYPTION));
        $transportHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($transportHost . ':' . $port, $errno, $errstr, $timeout);

        if ($socket === false) {
            error_log('SMTP connection failed: ' . $errstr . ' (' . $errno . ')');
            return false;
        }

        stream_set_timeout($socket, $timeout);

        if (!$this->smtpReadResponse($socket, [220])) {
            fclose($socket);
            return false;
        }

        $clientName = $_SERVER['SERVER_NAME'] ?? 'hanthana.local';
        if (!$this->smtpWriteCommand($socket, 'EHLO ' . $clientName, [250])) {
            if (!$this->smtpWriteCommand($socket, 'HELO ' . $clientName, [250])) {
                fclose($socket);
                return false;
            }
        }

        if ($encryption === 'tls') {
            if (!$this->smtpWriteCommand($socket, 'STARTTLS', [220])) {
                fclose($socket);
                return false;
            }

            if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return false;
            }

            if (!$this->smtpWriteCommand($socket, 'EHLO ' . $clientName, [250])) {
                fclose($socket);
                return false;
            }
        }

        $username = trim((string)SMTP_USERNAME);
        $password = (string)SMTP_PASSWORD;

        if ($username !== '' || $password !== '') {
            if ($username === '' || $password === '') {
                error_log('SMTP auth requires both SMTP_USERNAME and SMTP_PASSWORD.');
                fclose($socket);
                return false;
            }

            if (!$this->smtpWriteCommand($socket, 'AUTH LOGIN', [334])) {
                fclose($socket);
                return false;
            }

            if (!$this->smtpWriteCommand($socket, base64_encode($username), [334])) {
                fclose($socket);
                return false;
            }

            if (!$this->smtpWriteCommand($socket, base64_encode($password), [235])) {
                fclose($socket);
                return false;
            }
        }

        if (!$this->smtpWriteCommand($socket, 'MAIL FROM:<' . $fromAddress . '>', [250])) {
            fclose($socket);
            return false;
        }

        if (!$this->smtpWriteCommand($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251])) {
            fclose($socket);
            return false;
        }

        if (!$this->smtpWriteCommand($socket, 'DATA', [354])) {
            fclose($socket);
            return false;
        }

        $data = $this->buildSmtpData($toEmail, $fromAddress, $fromName, $subject, $message);
        if (fwrite($socket, $data . "\r\n.\r\n") === false) {
            fclose($socket);
            return false;
        }

        if (!$this->smtpReadResponse($socket, [250])) {
            fclose($socket);
            return false;
        }

        $this->smtpWriteCommand($socket, 'QUIT', [221]);
        fclose($socket);
        return true;
    }

    private function canUsePhpMail(): bool {
        if (!function_exists('mail')) {
            return false;
        }

        $sendmailPath = trim((string)ini_get('sendmail_path'));
        if ($sendmailPath === '') {
            return false;
        }

        $parts = preg_split('/\s+/', $sendmailPath);
        $binary = trim((string)($parts[0] ?? ''));
        if ($binary === '') {
            return false;
        }

        if (PHP_OS_FAMILY !== 'Windows' && !is_file($binary)) {
            return false;
        }

        return true;
    }

    private function sendPasswordResetCodeEmail(string $toEmail, string $recipientName, string $code, string $expiresAt): bool {
        $fromAddress = MAIL_FROM_ADDRESS;
        $fromName = MAIL_FROM_NAME;
        $safeFromName = trim(preg_replace('/[\r\n]+/', ' ', $fromName));
        $safeFromAddress = trim(preg_replace('/[\r\n]+/', '', $fromAddress));

        if (!filter_var($safeFromAddress, FILTER_VALIDATE_EMAIL)) {
            $smtpUser = trim((string)SMTP_USERNAME);
            $safeFromAddress = filter_var($smtpUser, FILTER_VALIDATE_EMAIL) ? $smtpUser : 'no-reply@hanthana.local';
        }

        $subject = 'Your Hanthana password reset code';
        $displayName = trim($recipientName);

        $expiryLabel = $expiresAt;
        try {
            $expiryLabel = (new DateTime($expiresAt))->format('M d, Y h:i A');
        } catch (Exception $e) {
            // Keep original value if parsing fails.
        }

        $greeting = $displayName !== '' ? 'Hi ' . $displayName . ',' : 'Hi,';
        $message = $greeting . "\n\n"
            . "We received a request to reset your Hanthana password.\n"
            . "Use this 6-digit verification code:\n\n"
            . $code . "\n\n"
            . "This code expires at " . $expiryLabel . ".\n"
            . "If you did not request this, you can ignore this email.\n\n"
            . "- Hanthana Team";

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $safeFromName . ' <' . $safeFromAddress . '>',
            'X-Mailer: PHP/' . phpversion(),
        ];

        if ($this->isSmtpConfigured()) {
            $smtpSent = $this->sendMailViaSmtp($toEmail, $safeFromAddress, $safeFromName, $subject, $message);
            if ($smtpSent) {
                return true;
            }
        }

        if ($this->canUsePhpMail()) {
            return @mail($toEmail, $subject, $message, implode("\r\n", $headers));
        }

        return false;
    }

    public function forgotPassword() {
        if ($this->isLoggedIn()) {
            header('Location: ' . BASE_PATH . 'index.php?controller=AcedemicDashboard&action=index');
            exit();
        }

        $email = trim((string)($_GET['email'] ?? ''));
        $error = $this->getFlash('forgot_password_error');
        $success = $this->getFlash('forgot_password_success');

        require_once __DIR__ . '/../views/forgot-password.php';
    }

    public function sendPasswordResetCode() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToForgotPassword();
        }

        $email = trim((string)($_POST['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('forgot_password_error', 'Please enter a valid email address.');
            $this->redirectToForgotPassword($email);
        }

        $user = $this->userModel->findByEmail($email);
        if ($user) {
            $userId = (int)$user['user_id'];
            $this->passwordResetModel->deleteExpiredCodes();
            $this->passwordResetModel->invalidateActiveCodes($userId);

            $code = $this->buildPasswordResetCode();
            $codeHash = password_hash($code, PASSWORD_DEFAULT);
            $expiresAt = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');
            $requestedIp = $_SERVER['REMOTE_ADDR'] ?? null;

            $stored = $this->passwordResetModel->createCode($userId, $codeHash, $expiresAt, $requestedIp);
            if (!$stored) {
                $this->setFlash('forgot_password_error', 'Could not generate reset code. Please try again.');
                $this->redirectToForgotPassword($email);
            }

            $recipientName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $sent = $this->sendPasswordResetCodeEmail($email, $recipientName, $code, $expiresAt);
            if (!$sent) {
                $this->setFlash('forgot_password_error', 'Could not send email right now. Please try again in a moment.');
                $this->redirectToForgotPassword($email);
            }
        }

        $_SESSION['password_reset_email'] = $email;
        $this->setFlash('forgot_password_success', 'If the email exists in our system, we sent a 6-digit code.');
        $this->redirectToResetPassword($email);
    }

    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleResetPasswordPost();
            return;
        }

        if ($this->isLoggedIn()) {
            header('Location: ' . BASE_PATH . 'index.php?controller=AcedemicDashboard&action=index');
            exit();
        }

        $email = trim((string)($_GET['email'] ?? ($_SESSION['password_reset_email'] ?? '')));
        $error = $this->getFlash('reset_password_error');
        $success = $this->getFlash('forgot_password_success');

        require_once __DIR__ . '/../views/reset-password.php';
    }

    private function handleResetPasswordPost(): void {
        $email = trim((string)($_POST['email'] ?? ''));
        $code = trim((string)($_POST['code'] ?? ''));
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('reset_password_error', 'Please enter a valid email address.');
            $this->redirectToResetPassword($email);
        }

        if (!preg_match('/^\d{6}$/', $code)) {
            $this->setFlash('reset_password_error', 'Enter the 6-digit code sent to your email.');
            $this->redirectToResetPassword($email);
        }

        if (strlen($newPassword) < 6) {
            $this->setFlash('reset_password_error', 'New password must be at least 6 characters.');
            $this->redirectToResetPassword($email);
        }

        if ($newPassword !== $confirmPassword) {
            $this->setFlash('reset_password_error', 'Passwords do not match.');
            $this->redirectToResetPassword($email);
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            $this->setFlash('reset_password_error', 'Invalid reset code or email.');
            $this->redirectToResetPassword($email);
        }

        $userId = (int)$user['user_id'];
        $record = $this->passwordResetModel->getLatestActiveCodeByUserId($userId);
        if (!$record) {
            $this->setFlash('reset_password_error', 'Code is invalid or expired. Request a new one.');
            $this->redirectToResetPassword($email);
        }

        if ((int)$record['attempt_count'] >= 5) {
            $this->setFlash('reset_password_error', 'Too many attempts. Request a new code and try again.');
            $this->redirectToResetPassword($email);
        }

        if (!password_verify($code, $record['code_hash'])) {
            $this->passwordResetModel->incrementAttempts((int)$record['reset_id']);
            $this->setFlash('reset_password_error', 'Invalid reset code.');
            $this->redirectToResetPassword($email);
        }

        if (password_verify($newPassword, (string)$user['password_hash'])) {
            $this->setFlash('reset_password_error', 'Choose a new password different from the current one.');
            $this->redirectToResetPassword($email);
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updated = $this->userModel->updatePasswordHash($userId, $passwordHash);

        if (!$updated) {
            $this->setFlash('reset_password_error', 'Could not update password. Please try again.');
            $this->redirectToResetPassword($email);
        }

        $this->passwordResetModel->markCodeAsUsed((int)$record['reset_id']);
        $this->passwordResetModel->invalidateActiveCodes($userId);
        $this->passwordResetModel->deleteExpiredCodes();

        unset($_SESSION['password_reset_email']);

        header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index&password_reset=success');
        exit();
    }

    private function isValidUniversityEmail(string $email): bool {
        return (bool) preg_match('/^[^@\s]+@[a-z0-9-]+(?:\.[a-z0-9-]+)*\.ac\.lk$/i', $email);
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
            $email = trim((string)$data['email']);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Valid email is required.";
            } elseif (!$this->isValidUniversityEmail($email)) {
                $errors[] = "Please use a university email ending with .ac.lk (e.g., 2023cs140@stu.ucsc.cmb.ac.lk).";
            } elseif ($this->userModel->emailExists($email)) {
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
        if (!empty($data['email'])) {
            $email = trim((string)$data['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$this->isValidUniversityEmail($email)) {
                $errors[] = "Please use a university email ending with .ac.lk (e.g., 2023cs140@stu.ucsc.cmb.ac.lk).";
            } elseif ($this->userModel->emailExists($email, $user_id)) {
                $errors[] = "Email already exists.";
            }
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

    // Check if a registration field is available
    public function checkAvailability() {
        header('Content-Type: application/json; charset=utf-8');

        $field = strtolower(trim($_GET['field'] ?? $_POST['field'] ?? ''));
        $value = trim($_GET['value'] ?? $_POST['value'] ?? '');

        if ($field === '' || $value === '') {
            echo json_encode([
                'success' => false,
                'available' => false,
                'message' => 'Field and value are required.'
            ]);
            return;
        }

        if ($field === 'username') {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
                echo json_encode([
                    'success' => true,
                    'available' => false,
                    'message' => 'Username can only contain letters, numbers, and underscores.'
                ]);
                return;
            }

            $exists = $this->userModel->usernameExists($value);
            echo json_encode([
                'success' => true,
                'available' => !$exists,
                'message' => $exists ? 'Username is already taken.' : 'Username is available.'
            ]);
            return;
        }

        if ($field === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'success' => true,
                    'available' => false,
                    'message' => 'Please enter a valid email address.'
                ]);
                return;
            }

            if (!$this->isValidUniversityEmail($value)) {
                echo json_encode([
                    'success' => true,
                    'available' => false,
                    'message' => 'Use your university email format: name@....ac.lk'
                ]);
                return;
            }

            $exists = $this->userModel->emailExists($value);
            echo json_encode([
                'success' => true,
                'available' => !$exists,
                'message' => $exists ? 'Email is already registered.' : 'Email is available.'
            ]);
            return;
        }

        if ($field === 'phone') {
            if (!preg_match('/^[0-9]{10}$/', $value)) {
                echo json_encode([
                    'success' => true,
                    'available' => false,
                    'message' => 'Phone number must be 10 digits.'
                ]);
                return;
            }

            $exists = $this->userModel->phoneExists($value);
            echo json_encode([
                'success' => true,
                'available' => !$exists,
                'message' => $exists ? 'Phone number is already registered.' : 'Phone number is available.'
            ]);
            return;
        }

        echo json_encode([
            'success' => false,
            'available' => false,
            'message' => 'Unsupported field.'
        ]);
    }
}
?>