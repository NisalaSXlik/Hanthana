<?php
require_once __DIR__ . '/../core/Database.php';

class GroupModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = (new Database())->getConnection();
    }

    /**
     * Create a new group
     */
    public function createGroup(array $data): int {
        try {
            $sql = "INSERT INTO GroupsTable 
                    (name, tag, description, focus, privacy_status, rules, created_by) 
                    VALUES 
                    (:name, :tag, :description, :focus, :privacy_status, :rules, :created_by)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':tag' => $data['tag'],
                ':description' => $data['description'],
                ':focus' => $data['focus'],
                ':privacy_status' => $data['privacy_status'],
                ':rules' => $data['rules'],
                ':created_by' => $data['created_by']
            ]);

            $groupId = (int)$this->pdo->lastInsertId();

            // Automatically add creator as admin member
            if ($groupId) {
                $this->addMember($groupId, $data['created_by'], 'admin');
            }

            return $groupId;
        } catch (PDOException $e) {
            // Check for duplicate tag error
            if ($e->getCode() == 23000) {
                throw new Exception('Group tag already exists. Please choose a different tag.');
            }
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Add a member to a group
     */
    private function addMember(int $groupId, int $userId, string $role = 'member'): bool {
        try {
            $sql = "INSERT INTO GroupMember (group_id, user_id, role) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$groupId, $userId, $role]);
        } catch (PDOException $e) {
            error_log('Add member error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get group by ID
     */
    public function getById(int $groupId): ?array {
        $sql = "SELECT * FROM GroupsTable WHERE group_id = ? AND is_active = TRUE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$groupId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get all groups user has joined
     */
    public function getUserGroups(int $userId): array {
        $sql = "SELECT g.*, gm.role, gm.joined_at
                FROM GroupsTable g
                JOIN GroupMember gm ON g.group_id = gm.group_id
                WHERE gm.user_id = ? AND g.is_active = TRUE
                ORDER BY gm.joined_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update group details
     */
    public function updateGroup(int $groupId, array $data): bool {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'tag', 'description', 'focus', 'privacy_status', 'rules', 'display_picture', 'cover_image'])) {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }

        if (empty($fields)) return false;

        $params[] = $groupId;
        $sql = "UPDATE GroupsTable SET " . implode(', ', $fields) . " WHERE group_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete (deactivate) a group
     */
    public function deleteGroup(int $groupId): bool {
        $sql = "UPDATE GroupsTable SET is_active = FALSE WHERE group_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$groupId]);
    }
}
