<?php
class AuthController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        
    }

    public function register()
    {
        $errors = $this->validateRegistration();
        
        if (empty($errors))
        {            
            if ($this->userModel->create($this->data))
            {
                $user = $this->userModel->findByField('username', $this->data['username']);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                $_SESSION['is_active'] = $user['role'];
                $_SESSION['banned_until'] = $user['banned_until'];
                
                $this->userModel->updateLastLogin($user['user_id']);

                return $this->response([
                    'status' => 'success',
                    'redirect' => 'Feed'
                ]);
            }
            else
            {
                return $this->response(['
                    status' => 'error',
                    'errors' => ['Registration failed. Please try again.']
                ]);
            }
        }
        return $this->response([
            'status' => 'error',
            'errors' => $errors
        ]);
    }

    public function login()
    {
        $errors = $this->validateLogin();
        
        if (!empty($errors)) {
            return $this->response(['status' => 'error', 'errors' => $errors]);
        }
        
        // Determine if identifier is email or phone
        if (filter_var($this->data['identifier'], FILTER_VALIDATE_EMAIL))
            $identifier_type = 'email';
        else
            $identifier_type = 'phone_number';

        
        $user = $this->userModel->findByField($identifier_type, $this->data['identifier']);
        $password = $this->userModel->fetchPassword($identifier_type, $this->data['identifier']);
        
        if ($user && password_verify($this->data['password'], $password))
        {
            if (!empty($user['banned_until']))
            {
                try
                {
                    $banUntil = new DateTime($user['banned_until']);
                }
                catch (Exception $e)
                {
                    $banUntil = null;
                }

                if ($banUntil && $banUntil > new DateTime())
                {
                    $formatted = $banUntil->format('M d, Y H:i');
                    $reason = $user['ban_reason'] ?? 'policy violation';

                    return $this->response([
                        'status' => 'error',
                        'errors' => ["Account banned until {$formatted}. Reason: {$reason}."]
                    ]);
                }

                if ($banUntil && $banUntil <= new DateTime())
                {
                    $this->userModel->clearBan($user['user_id']);
                }
            }

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            $_SESSION['is_active'] = $user['role'];
            $_SESSION['banned_until'] = $user['banned_until'];
            
            $this->userModel->updateLastLogin($user['user_id']);
            
            return $this->response([
                'status' => 'success',
                'redirect' => 'Feed'
            ]);
        }
        
        return $this->response(['status' => 'error', 'errors' => 'Invalid email/phone or password.']);
    }


    public function logout()
    {
        $this->requireAuth();
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies"))
        {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        return $this->response(['status' => 'success', 'message' => 'Logged out successfully.']);
    }

    private function validateRegistration()
    {
        $errors = [];
        
        $required = ['first_name', 'last_name', 'email', 'phone_number', 'password', 'username'];
        foreach ($required as $field)
        {
            if (empty($this->data[$field]))
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
        
        if (!empty($this->data['email']))
        {
            if (!filter_var($this->data['email'], FILTER_VALIDATE_EMAIL))
                $errors[] = "Valid email is required.";

            elseif ($this->userModel->checkIfUnique('email', $this->data['email']))
                $errors[] = "Email already exists.";
        }
        
        if (!empty($this->data['phone_number']))
        {
            if (!preg_match('/^[0-9]{10}$/', $this->data['phone_number']))
                $errors[] = "Phone number must be 10 digits.";

            elseif ($this->userModel->checkIfUnique('phone_number', $this->data['phone_number']))
                $errors[] = "Phone number already exists.";
        }

        if (!empty($this->data['username']))
        {
            if ($this->userModel->checkIfUnique('username', $this->data['username']))
                $errors[] = "Username already exists.";

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->data['username']))
                $errors[] = "Username can only contain letters, numbers, and underscores.";
        }
        
        if (!empty($this->data['password']))
        {
            if (strlen($this->data['password']) < 6)
                $errors[] = "Password must be at least 6 characters.";
            
            if (!empty($this->data['password_confirmation']) &&
                $this->data['password'] !== $this->data['password_confirmation'])
                $errors[] = "Passwords do not match.";

            elseif (empty($this->data['password_confirmation']))
                $errors[] = "Please confirm your password.";
        }
        
        return $errors;
    }

    private function validateLogin() {
        $errors = [];
        
        if (empty($this->data['identifier'])) {
            $errors[] = "Email or phone number is required.";
        }
        
        if (empty($this->data['password'])) {
            $errors[] = "Password is required.";
        }
        
        return $errors;
    }
}