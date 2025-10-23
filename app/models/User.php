<?php
require_once __DIR__ . '/../core/Database.php';

class User {
    private $db;
    private $table = 'Users';
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Create new user
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (first_name, last_name, email, phone_number, password_hash, username, bio, profile_picture, cover_photo, university, date_of_birth, location) 
                VALUES (:first_name, :last_name, :email, :phone_number, :password_hash, :username, :bio, :profile_picture, :cover_photo, :university, :date_of_birth, :location)";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':phone_number' => $data['phone_number'] ?? null,
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':username' => $data['username'],
            ':bio' => $data['bio'] ?? null,
            ':profile_picture' => $data['profile_picture'] ?? 'defaultProfilePic.png',
            ':cover_photo' => $data['cover_photo'] ?? null,
            ':university' => $data['university'] ?? null,
            ':date_of_birth' => $data['date_of_birth'] ?? null,
            ':location' => $data['location'] ?? null
        ]);
    }
    
    // Find user by ID
    public function findById($user_id) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND is_active = TRUE";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Find user by email
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email AND is_active = TRUE";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Find user by username
    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username AND is_active = TRUE";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Find user by phone number
    public function findByPhone($phone_number) {
        $sql = "SELECT * FROM {$this->table} WHERE phone_number = :phone_number AND is_active = TRUE";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':phone_number' => $phone_number]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Update user profile
    public function update($user_id, $data) {
        $sql = "UPDATE {$this->table} SET 
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
        
        $stmt = $this->db->getConnection()->prepare($sql);
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
    
    // Update last login
    public function updateLastLogin($user_id) {
        $sql = "UPDATE {$this->table} SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':user_id' => $user_id]);
    }
    
    // Delete user (soft delete)
    public function delete($user_id) {
        $sql = "UPDATE {$this->table} SET is_active = FALSE WHERE user_id = :user_id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':user_id' => $user_id]);
    }
    
    // Check if email exists
    public function emailExists($email, $exclude_user_id = null) {
        $sql = "SELECT user_id FROM {$this->table} WHERE email = :email AND is_active = TRUE";
        if ($exclude_user_id) {
            $sql .= " AND user_id != :exclude_id";
        }
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $params = [':email' => $email];
        if ($exclude_user_id) {
            $params[':exclude_id'] = $exclude_user_id;
        }
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    // Check if username exists
    public function usernameExists($username, $exclude_user_id = null) {
        $sql = "SELECT user_id FROM {$this->table} WHERE username = :username AND is_active = TRUE";
        if ($exclude_user_id) {
            $sql .= " AND user_id != :exclude_id";
        }
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $params = [':username' => $username];
        if ($exclude_user_id) {
            $params[':exclude_id'] = $exclude_user_id;
        }
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    // Check if phone number exists
    public function phoneExists($phone_number, $exclude_user_id = null) {
        $sql = "SELECT user_id FROM {$this->table} WHERE phone_number = :phone_number AND is_active = TRUE";
        if ($exclude_user_id) {
            $sql .= " AND user_id != :exclude_id";
        }
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $params = [':phone_number' => $phone_number];
        if ($exclude_user_id) {
            $params[':exclude_id'] = $exclude_user_id;
        }
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    // Get all active users
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = TRUE ORDER BY created_at DESC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Search users by name, username, or email
    public function search($query) {
        $sql = "SELECT user_id, username, first_name, last_name, email, profile_picture 
                FROM {$this->table} 
                WHERE (username LIKE :query OR first_name LIKE :query OR last_name LIKE :query OR email LIKE :query) 
                AND is_active = TRUE 
                LIMIT 20";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':query' => "%$query%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Update profile picture
    public function updateProfilePicture($user_id, $profile_picture) {
        $sql = "UPDATE {$this->table} SET profile_picture = :profile_picture WHERE user_id = :user_id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            ':profile_picture' => $profile_picture,
            ':user_id' => $user_id
        ]);
    }
    
    // Update cover photo
    public function updateCoverPhoto($user_id, $cover_photo) {
        $sql = "UPDATE {$this->table} SET cover_photo = :cover_photo WHERE user_id = :user_id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            ':cover_photo' => $cover_photo,
            ':user_id' => $user_id
        ]);
    }
    
    // Increment friends count
    public function incrementFriendsCount($user_id) {
        $sql = "UPDATE {$this->table} SET friends_count = friends_count + 1 WHERE user_id = :user_id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':user_id' => $user_id]);
    }
    
    // Decrement friends count
    public function decrementFriendsCount($user_id) {
        $sql = "UPDATE {$this->table} SET friends_count = friends_count - 1 WHERE user_id = :user_id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':user_id' => $user_id]);
    }

    public function searchUsers(string $term, int $limit = 10): array {
        $sql = "SELECT user_id, username, first_name, last_name, profile_picture
                FROM {$this->table}
                WHERE is_active = TRUE
                  AND (username LIKE :query OR first_name LIKE :query OR last_name LIKE :query)
                ORDER BY username ASC
                LIMIT :limit";

        $stmt = $this->db->getConnection()->prepare($sql);
        $like = '%' . $term . '%';
        $stmt->bindValue(':query', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>