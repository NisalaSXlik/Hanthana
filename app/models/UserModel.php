<?php
require_once __DIR__ . '/../core/Database.php';

class UserModel
{
    private PDO $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Create new user
    public function create($data) {
        $sql = "INSERT INTO Users (first_name, last_name, email, phone_number, password_hash, username, bio, profile_picture, cover_photo, university, date_of_birth, location, role) 
            VALUES (:first_name, :last_name, :email, :phone_number, :password_hash, :username, :bio, :profile_picture, :cover_photo, :university, :date_of_birth, :location, :role)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':phone_number' => $data['phone_number'] ?? null,
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':username' => $data['username'],
            ':bio' => $data['bio'] ?? null,
            ':profile_picture' => $data['profile_picture'] ?? 'uploads/user_dp/default.png',
            ':cover_photo' => $data['cover_photo'] ?? 'uploads/user_cover/default.png',
            ':university' => $data['university'] ?? null,
            ':date_of_birth' => $data['date_of_birth'] ?? null,
            ':location' => $data['location'] ?? null,
            ':role' => $data['role'] ?? 'user'
        ]);
    }
    
    // Find user by ID
    public function findById($user_id) {
        $sql = "SELECT * FROM Users WHERE user_id = :user_id AND is_active = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Find user by email
    public function findByEmail($email) {
        $sql = "SELECT * FROM Users WHERE email = :email AND is_active = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Find user by username
    public function findByUsername($username) {
        $sql = "SELECT * FROM Users WHERE username = :username AND is_active = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Find user by phone number
    public function findByPhone($phone_number) {
        $sql = "SELECT * FROM Users WHERE phone_number = :phone_number AND is_active = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':phone_number' => $phone_number]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Update user profile
    public function update($user_id, $data) {
        $sql = "UPDATE Users SET 
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone_number = :phone_number,
                username = :username,
                bio = :bio,
                profile_picture = :profile_picture,
                cover_photo = :cover_photo,
                university = :university,
                date_of_birth = :date_of_birth,
                location = :location,
                updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':phone_number' => $data['phone_number'] ?? null,
            ':username' => $data['username'],
            ':bio' => $data['bio'] ?? null,
            ':profile_picture' => $data['profile_picture'] ?? null,
            ':cover_photo' => $data['cover_photo'] ?? null,
            ':university' => $data['university'] ?? null,
            ':date_of_birth' => $data['date_of_birth'] ?? null,
            ':location' => $data['location'] ?? null,
            ':user_id' => $user_id
        ]);
    }
    
    // Update user profile for settings (with different field names)
    public function updateUser($userId, $data) {
        $allowedFields = ['first_name', 'last_name', 'username', 'email', 'phone_number', 'bio', 'university', 'location', 'date_of_birth', 'profile_picture', 'cover_photo'];
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $userId;
        $sql = "UPDATE Users SET " . implode(', ', $updates) . " WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    // Update password
    public function updatePassword($userId, $hashedPassword) {
        $sql = "UPDATE Users SET password_hash = ? WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$hashedPassword, $userId]);
    }
    
    // Update last login
    public function updateLastLogin($user_id) {
        $sql = "UPDATE Users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $user_id]);
    }
    
    // Delete user (soft delete)
    public function delete($user_id) {
        $sql = "UPDATE Users SET is_active = FALSE WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $user_id]);
    }
    
    // Check if email exists
    public function emailExists($email, $exclude_user_id = null) {
        $sql = "SELECT user_id FROM Users WHERE email = :email AND is_active = TRUE";
        if ($exclude_user_id) {
            $sql .= " AND user_id != :exclude_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [':email' => $email];
        if ($exclude_user_id) {
            $params[':exclude_id'] = $exclude_user_id;
        }
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    // Check if username exists
    public function usernameExists($username, $exclude_user_id = null) {
        $sql = "SELECT user_id FROM Users WHERE username = :username AND is_active = TRUE";
        if ($exclude_user_id) {
            $sql .= " AND user_id != :exclude_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [':username' => $username];
        if ($exclude_user_id) {
            $params[':exclude_id'] = $exclude_user_id;
        }
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    // Check if phone number exists
    public function phoneExists($phone_number, $exclude_user_id = null) {
        $sql = "SELECT user_id FROM Users WHERE phone_number = :phone_number AND is_active = TRUE";
        if ($exclude_user_id) {
            $sql .= " AND user_id != :exclude_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [':phone_number' => $phone_number];
        if ($exclude_user_id) {
            $params[':exclude_id'] = $exclude_user_id;
        }
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    // Get all active users
    public function getAll() {
        $sql = "SELECT * FROM Users WHERE is_active = TRUE ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) AS total_users,
                    SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) AS active_users,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS new_users_last_7
                FROM Users";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total_users' => (int)($row['total_users'] ?? 0),
            'active_users' => (int)($row['active_users'] ?? 0),
            'new_users_last_7' => (int)($row['new_users_last_7'] ?? 0)
        ];
    }

    public function getDailyActiveUsers(int $days = 7): array {
        $days = max(1, min(30, $days));
        $start = new DateTime('today');
        $start->modify('-' . ($days - 1) . ' days');
        $startDateTime = $start->format('Y-m-d 00:00:00');

        $sql = "SELECT DATE(last_login) AS day, COUNT(DISTINCT user_id) AS active_count
                FROM Users
                WHERE last_login IS NOT NULL AND last_login >= :start_date
                GROUP BY DATE(last_login)
                ORDER BY day ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':start_date' => $startDateTime]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $map = [];
        foreach ($rows as $row) {
            $map[$row['day']] = (int)$row['active_count'];
        }

        $labels = [];
        $counts = [];
        $cursor = clone $start;
        for ($i = 0; $i < $days; $i++) {
            $dayKey = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('M d');
            $counts[] = $map[$dayKey] ?? 0;
            $cursor->modify('+1 day');
        }

        $latestCount = $counts ? $counts[count($counts) - 1] : 0;

        return [
            'labels' => $labels,
            'counts' => $counts,
            'latest_count' => $latestCount,
            'start_date' => $start->format('Y-m-d')
        ];
    }

    public function getRecentUsers($limit = 5) {
        $sql = "SELECT user_id, first_name, last_name, username, email, created_at, role
                FROM Users
                ORDER BY created_at DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function banUser(int $userId, ?string $untilDate, ?string $reason, ?int $adminId = null, ?string $notes = null): bool {
        try {
            $sql = "UPDATE Users
                    SET banned_until = :until,
                        ban_reason = :reason,
                        ban_notes = :notes,
                        banned_by = :admin_id
                    WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':until' => $untilDate,
                ':reason' => $reason,
                ':notes' => $notes,
                ':admin_id' => $adminId,
                ':user_id' => $userId,
            ]);
        } catch (PDOException $e) {
            error_log('banUser error: ' . $e->getMessage());
            return false;
        }
    }

    public function clearBan(int $userId): bool {
        try {
            $sql = "UPDATE Users
                    SET banned_until = NULL,
                        ban_reason = NULL,
                        ban_notes = NULL,
                        banned_by = NULL
                    WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log('clearBan error: ' . $e->getMessage());
            return false;
        }
    }

    public function getBanInfo(int $userId): ?array {
        $sql = "SELECT banned_until, ban_reason, ban_notes FROM Users WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function isAdmin($userId) {
        $sql = "SELECT role FROM Users WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? ($row['role'] === 'admin') : false;
    }
    
    // Search users by name, username, or email
    public function search($query) {
        $sql = "SELECT user_id, username, first_name, last_name, email, profile_picture 
                FROM Users 
                WHERE (username LIKE :query OR first_name LIKE :query OR last_name LIKE :query OR email LIKE :query) 
                AND is_active = TRUE 
                LIMIT 20";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':query' => "%$query%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Update profile picture
    public function updateProfilePicture($user_id, $uploadedFile) {
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) return false;
        $uploadDir = __DIR__ . '/../../public/uploads/user_dp/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = uniqid() . '_' . basename($uploadedFile['name']);
        $serverPath = $uploadDir . $filename;
        if (!move_uploaded_file($uploadedFile['tmp_name'], $serverPath)) return false;
        $dbPath = 'uploads/user_dp/' . $filename;
        $sql = "UPDATE Users SET profile_picture = :path WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':path' => $dbPath, ':id' => $user_id]);
    }
    
    // Update cover photo
    public function updateCoverPhoto($user_id, $uploadedFile) {
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) return false;
        $uploadDir = __DIR__ . '/../../public/uploads/user_cover/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = uniqid() . '_' . basename($uploadedFile['name']);
        $serverPath = $uploadDir . $filename;
        if (!move_uploaded_file($uploadedFile['tmp_name'], $serverPath)) return false;
        $dbPath = 'uploads/user_cover/' . $filename;
        $sql = "UPDATE Users SET cover_photo = :path WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':path' => $dbPath, ':id' => $user_id]);
    }
    
    // Increment friends count
    public function incrementFriendsCount($user_id) {
        $sql = "UPDATE Users SET friends_count = friends_count + 1 WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $user_id]);
    }
    
    // Decrement friends count
    public function decrementFriendsCount($user_id) {
        $sql = "UPDATE Users SET friends_count = friends_count - 1 WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $user_id]);
    }

    // Update password hash
    public function updatePasswordHash($userId, $passwordHash) {
        $sql = "UPDATE Users SET password_hash = :password, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':password' => $passwordHash,
            ':user_id' => $userId,
        ]);
    }

    // Search users
    public function searchUsers($term, $limit = 10) {
        $sql = "SELECT user_id, username, first_name, last_name, profile_picture
                FROM Users
                WHERE is_active = TRUE
                  AND (username LIKE :query OR first_name LIKE :query OR last_name LIKE :query)
                ORDER BY username ASC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $like = '%' . $term . '%';
        $stmt->bindValue(':query', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get user profile with privacy check
    public function getProfileWithPrivacy($userId, $viewerId) {
        $user = $this->findById($userId);
        if (!$user) {
            return null;
        }
        
        // Check privacy settings
        require_once __DIR__ . '/SettingsModel.php';
        $settingsModel = new SettingsModel();
        
        $canViewProfile = $settingsModel->canViewProfile($userId, $viewerId);
        $showEmail = $settingsModel->shouldShowEmail($userId, $viewerId);
        $showPhone = $settingsModel->shouldShowPhone($userId, $viewerId);
        
        // Remove sensitive data based on privacy
        if (!$canViewProfile) {
            return [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'profile_picture' => $user['profile_picture'],
                'is_private' => true,
                'message' => 'This profile is private'
            ];
        }
        
        // Remove email/phone if not allowed
        if (!$showEmail) {
            unset($user['email']);
        }
        if (!$showPhone) {
            unset($user['phone_number']);
        }
        
        return $user;
    }

    // Search users with privacy check
    public function searchUsersWithPrivacy($term, $viewerId, $limit = 10) {
        $sql = "SELECT user_id, username, first_name, last_name, profile_picture, is_online
                FROM Users
                WHERE is_active = TRUE
                AND (username LIKE :query OR first_name LIKE :query OR last_name LIKE :query)
                ORDER BY username ASC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $like = '%' . $term . '%';
        $stmt->bindValue(':query', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filter results based on privacy
        require_once __DIR__ . '/SettingsModel.php';
        $settingsModel = new SettingsModel();
        
        $filteredUsers = [];
        foreach ($users as $user) {
            $canView = $settingsModel->canViewProfile($user['user_id'], $viewerId);
            if ($canView) {
                $filteredUsers[] = $user;
            }
        }
        
        return $filteredUsers;
    }
}
?>
