<?php
require_once __DIR__ . '/../core/Database.php';

class User {
    private $db;
    private $table = 'Users';
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Create new user (Registration)
    public function register($data) {
        $sql = "INSERT INTO Users (first_name, last_name, email, password_hash, username, bio, university, date_of_birth, location) 
                VALUES (:first_name, :last_name, :email, :password_hash, :username, :bio, :university, :date_of_birth, :location)";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':username' => $data['username'],
            ':bio' => $data['bio'] ?? '',
            ':university' => $data['university'] ?? '',
            ':date_of_birth' => $data['date_of_birth'] ?? null,
            ':location' => $data['location'] ?? ''
        ]);
    }
    
    // Find user by email (for login)
    public function findByEmail($email) {
        $sql = "SELECT * FROM Users WHERE email = :email AND is_active = TRUE";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }
    
    // Find user by username
    public function findByUsername($username) {
        $sql = "SELECT * FROM Users WHERE username = :username AND is_active = TRUE";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }
    
    // Find user by ID
    public function findById($id) {
        $sql = "SELECT * FROM Users WHERE user_id = :id AND is_active = TRUE";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    // Update user profile
    public function updateProfile($user_id, $data) {
        $sql = "UPDATE Users SET 
                first_name = :first_name,
                last_name = :last_name,
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
            ':username' => $data['username'],
            ':bio' => $data['bio'] ?? '',
            ':profile_picture' => $data['profile_picture'] ?? '',
            ':cover_photo' => $data['cover_photo'] ?? '',
            ':university' => $data['university'] ?? '',
            ':date_of_birth' => $data['date_of_birth'] ?? null,
            ':location' => $data['location'] ?? '',
            ':user_id' => $user_id
        ]);
    }
    
    // Update last login
    public function updateLastLogin($user_id) {
        $sql = "UPDATE Users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':user_id' => $user_id]);
    }
    
    // Check if email exists
    public function emailExists($email, $exclude_user_id = null) {
        $sql = "SELECT user_id FROM Users WHERE email = :email AND is_active = TRUE";
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
        $sql = "SELECT user_id FROM Users WHERE username = :username AND is_active = TRUE";
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
}
?>