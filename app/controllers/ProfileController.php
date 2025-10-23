<?php
require_once __DIR__ . '/../models/User.php';

class ProfileController {
    private User $userModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->userModel = new User();
    }

    public function index(): void {
        $this->renderProfileView();
    }

    public function view(): void {
        $this->renderProfileView();
    }

    public function update(): void {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            return;
        }

        $userId = (int)$_SESSION['user_id'];

        $required = ['first_name', 'last_name', 'email', 'username'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required.']);
                return;
            }
        }

        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            return;
        }

        $profileData = [
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'email' => trim($_POST['email']),
            'phone_number' => trim($_POST['phone_number'] ?? ''),
            'username' => trim($_POST['username']),
            'bio' => trim($_POST['bio'] ?? ''),
            'university' => trim($_POST['university'] ?? ''),
            'date_of_birth' => $_POST['date_of_birth'] ?? null,
            'location' => trim($_POST['location'] ?? ''),
            'profile_picture' => null,
            'cover_photo' => null
        ];

        foreach (['phone_number', 'bio', 'university', 'location'] as $optionalField) {
            if ($profileData[$optionalField] === '') {
                $profileData[$optionalField] = null;
            }
        }
        if (empty($profileData['date_of_birth'])) {
            $profileData['date_of_birth'] = null;
        }

        if ($this->userModel->emailExists($profileData['email'], $userId)) {
            echo json_encode(['success' => false, 'message' => 'Email already in use.']);
            return;
        }

        if ($this->userModel->usernameExists($profileData['username'], $userId)) {
            echo json_encode(['success' => false, 'message' => 'Username already in use.']);
            return;
        }

        if (!empty($profileData['phone_number']) && $this->userModel->phoneExists($profileData['phone_number'], $userId)) {
            echo json_encode(['success' => false, 'message' => 'Phone number already in use.']);
            return;
        }

        // Preserve existing images unless new ones are uploaded
        $existingUser = $this->userModel->findById($userId);
        if ($existingUser) {
            $profileData['profile_picture'] = $existingUser['profile_picture'] ?? null;
            $profileData['cover_photo'] = $existingUser['cover_photo'] ?? null;
        }

        $uploadErrors = $this->handleUploads($userId, $profileData);
        if (!empty($uploadErrors)) {
            echo json_encode(['success' => false, 'message' => implode("\n", $uploadErrors)]);
            return;
        }

        $updateResult = $this->userModel->update($userId, $profileData);

        if (!$updateResult) {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile.']);
            return;
        }

        // Refresh session snapshot for navbar/sidebar
        $_SESSION['username'] = $profileData['username'];
        $_SESSION['email'] = $profileData['email'];
        $_SESSION['first_name'] = $profileData['first_name'];
        $_SESSION['last_name'] = $profileData['last_name'];
        $_SESSION['phone_number'] = $profileData['phone_number'];
        $_SESSION['profile_picture'] = $profileData['profile_picture'];

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'profile_picture' => $profileData['profile_picture'],
            'cover_photo' => $profileData['cover_photo']
        ]);
    }

    public function updateProfilePicture(): void {
        $this->handleDirectMediaUpdate(
            'profile_picture',
            'images/avatars',
            'avatar',
            function (int $userId, string $path) {
                return $this->userModel->updateProfilePicture($userId, $path);
            },
            'Profile picture updated successfully.'
        );
    }

    public function updateCoverPhoto(): void {
        $this->handleDirectMediaUpdate(
            'cover_photo',
            'images/userCover',
            'cover',
            function (int $userId, string $path) {
                return $this->userModel->updateCoverPhoto($userId, $path);
            },
            'Cover photo updated successfully.'
        );
    }

    private function renderProfileView(): void {
        require_once __DIR__ . '/../views/userprofileview.php';
    }

    private function handleUploads(int $userId, array &$profileData): array {
        $errors = [];

        $avatarDir = __DIR__ . '/../../public/images/avatars';
        $coverDir = __DIR__ . '/../../public/images/userCover';

        if (!is_dir($avatarDir) && !mkdir($avatarDir, 0777, true)) {
            $errors[] = 'Unable to create avatars directory.';
        }
        if (!is_dir($coverDir) && !mkdir($coverDir, 0777, true)) {
            $errors[] = 'Unable to create cover images directory.';
        }

        if (!empty($errors)) {
            return $errors;
        }

        if (isset($_FILES['profile_picture']) && is_uploaded_file($_FILES['profile_picture']['tmp_name'])) {
            $uploadError = $this->processUpload($_FILES['profile_picture'], $avatarDir, 'avatar_' . $userId . '_' . time());
            if (is_string($uploadError)) {
                $profileData['profile_picture'] = 'images/avatars/' . basename($uploadError);
            } else {
                $errors[] = $uploadError['error'] ?? 'Failed to upload profile picture.';
            }
        }

        if (isset($_FILES['cover_photo']) && is_uploaded_file($_FILES['cover_photo']['tmp_name'])) {
            $uploadError = $this->processUpload($_FILES['cover_photo'], $coverDir, 'cover_' . $userId . '_' . time());
            if (is_string($uploadError)) {
                $profileData['cover_photo'] = 'images/userCover/' . basename($uploadError);
            } else {
                $errors[] = $uploadError['error'] ?? 'Failed to upload cover photo.';
            }
        }

        return $errors;
    }

    private function processUpload(array $file, string $destinationDir, string $baseName) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Upload error code: ' . $file['error']];
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes, true)) {
            return ['error' => 'Unsupported image type.'];
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $baseName . '.' . $extension;
        $targetPath = rtrim($destinationDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['error' => 'Failed to move uploaded file.'];
        }

        return $targetPath;
    }

    private function handleDirectMediaUpdate(string $fieldName, string $relativeDirectory, string $filenamePrefix, callable $updater, string $successMessage): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            return;
        }

        if (!isset($_FILES[$fieldName]) || !is_uploaded_file($_FILES[$fieldName]['tmp_name'])) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $absoluteDirectory = __DIR__ . '/../../public/' . trim($relativeDirectory, '/');

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0777, true)) {
            echo json_encode(['success' => false, 'message' => 'Unable to prepare upload directory.']);
            return;
        }

        $uploadResult = $this->processUpload(
            $_FILES[$fieldName],
            $absoluteDirectory,
            $filenamePrefix . '_' . $userId . '_' . time()
        );

        if (is_array($uploadResult)) {
            $error = $uploadResult['error'] ?? 'Failed to upload file.';
            echo json_encode(['success' => false, 'message' => $error]);
            return;
        }

        $relativePath = trim($relativeDirectory, '/') . '/' . basename($uploadResult);

        if (!$updater($userId, $relativePath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to persist image.']);
            return;
        }

        if ($fieldName === 'profile_picture') {
            $_SESSION['profile_picture'] = $relativePath;
        }

        if ($fieldName === 'cover_photo') {
            $_SESSION['cover_photo'] = $relativePath;
        }

        echo json_encode([
            'success' => true,
            'message' => $successMessage,
            $fieldName => $relativePath
        ]);
    }
}

