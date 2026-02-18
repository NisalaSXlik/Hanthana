<?php
class UserController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->requireAuth();
    }

    public function updateProfile()
    {
        $errors = $this->validateProfileUpdate();
        
        if (!empty($errors))
            return $this->response(['status' => 'error', 'errors' => $errors]);
        
        if ($this->userModel->update($this->data, $_SESSION['user_id']))
        {
            $user = $this->userModel->findByField('user_id', $_SESSION['user_id']);
            $_SESSION['role'] = $user['role'] ?? $_SESSION['role'];
            $_SESSION['is_active'] = $user['role'] ?? $_SESSION['is_active'] ;
            $_SESSION['banned_until'] = $user['banned_until'] ?? $_SESSION['banned_until'];

            return $this->response(['status' => 'success', 'message' => 'Profile updated successfully.']);
        }
        
        return $this->response(['status' => 'error', 'errors' => ['Profile update failed.']]);
    }
    
    public function changePassword()
    {
        $errors = [];

        $currentPassword = $this->data['current_password'] ?? '';
        $newPassword     = $this->data['new_password'] ?? '';
        $confirmPassword = $this->data['new_password_confirmation'] ?? '';
        
        if (!$currentPassword)  $errors[] = 'Enter the current password.';
        if (!$newPassword)      $errors[] = 'New password not given.';
        if (!$confirmPassword)  $errors[] = 'Please confirm your new password.';

        if ($errors)
            return $this->response([
                'status' => 'error', 'errors' => $errors
            ], 400);
        
        if ($newPassword !== $confirmPassword)
            return $this->response([
                'status' => 'error',
                'errors' => ['New passwords do not match.']
            ], 400);
            
        if (strlen($newPassword) < 6)
            return $this->response([
                'status' => 'error',
                'errors' => ['Password must be at least 6 characters.']
            ], 400);
            
        $user = $this->userModel->findByField('user_id', $_SESSION['user_id']);
        if (!$user || !password_verify($currentPassword, $user['password']))
            return $this->response([
                'status' => 'error',
                'errors' => ['Invalid current password.']
            ], 401);

        if ($newPassword === $currentPassword)
            return $this->response([
                'status' => 'error',
                'errors' => ['New password cannot be the same as the old password.']
            ], 400);
        
        $updateSuccess = $this->userModel->updatePassword($newPassword, $_SESSION['user_id']);
        if ($updateSuccess)
            return $this->response([
                'status' => 'success',
                'message' => 'Password changed successfully.'
            ]);
        
        return $this->response([
            'status' => 'error',
            'errors' => ['Server error. Please try again.']
        ], 500);
    }


    // Validate profile update this->data
    private function validateProfileUpdate()
    {
        $errors = [];
        
        if (empty($this->data['first_name']))    $errors[] = "First name is required.";
        if (empty($this->data['last_name']))     $errors[] = "Last name is required."; 
        if (empty($this->data['username']))      $errors[] = "Username is required.";
        if (empty($this->data['email']))         $errors[] = "Email is required.";
        
        if (!empty($this->data['email']) &&
            $this->userModel->checkIfUnique('email', $this->data['email'], $_SESSION['user_id']))
            $errors[] = "Email already exists.";

        if (!empty($this->data['username'])
            && $this->userModel->checkIfUnique('username', $this->data['username'], $_SESSION['user_id']))
            $errors[] = "Username already exists.";

        if (!empty($this->data['phone_number'])
            && $this->userModel->checkIfUnique('phone_number', $this->data['phone_number'], $_SESSION['user_id']))
            $errors[] = "Phone number already exists.";
        // should add other fields string sizes like bio and stuff
        return $errors;
    }
}