<?php
require_once __DIR__ . '/../core/Database.php';

class GroupModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = (new Database())->getConnection();
    }

    // Get groups created by the user
    public function getGroupsCreatedBy($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM GroupsTable WHERE created_by = ? AND is_active = TRUE ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get groups joined by the user (including those created by the user)
    public function getGroupsJoinedBy($userId) {
        $stmt = $this->pdo->prepare("
            SELECT g.* FROM GroupsTable g
            INNER JOIN GroupMember m ON g.group_id = m.group_id
            WHERE m.user_id = ? AND g.is_active = TRUE
            ORDER BY m.joined_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    public function addMember(int $groupId, int $userId, string $role = 'member'): bool {
        try {
            $sql = "INSERT INTO GroupMember (group_id, user_id, role) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$groupId, $userId, $role]);
        } catch (PDOException $e) {
            error_log('Add member error: ' . $e->getMessage());
            return false;
        }
    }

    public function searchGroups(string $term, int $userId, int $limit = 6): array {
        $limit = max(1, (int)$limit);
        $likeTerm = '%' . $term . '%';

        $sql = "
            SELECT
                g.group_id,
                g.name,
                g.tag,
                g.description,
                g.display_picture,
                g.privacy_status,
                g.member_count,
                g.created_by,
                CASE WHEN gm.user_id IS NULL THEN 0 ELSE 1 END AS is_member,
                CASE WHEN gjr.request_id IS NULL THEN 0 ELSE 1 END AS has_pending_request
            FROM GroupsTable g
            LEFT JOIN GroupMember gm
                ON gm.group_id = g.group_id
                AND gm.user_id = :user_id
                AND gm.status = 'active'
            LEFT JOIN GroupJoinRequests gjr
                ON gjr.group_id = g.group_id
                AND gjr.user_id = :user_id
                AND gjr.status = 'pending'
            WHERE g.is_active = TRUE
              AND (
                    g.name LIKE :like_term
                    OR COALESCE(g.tag, '') LIKE :like_term
                    OR COALESCE(g.description, '') LIKE :like_term
                    OR COALESCE(g.focus, '') LIKE :like_term
              )
            ORDER BY g.member_count DESC, g.name ASC
            LIMIT :result_limit
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':like_term', $likeTerm, PDO::PARAM_STR);
            $stmt->bindValue(':result_limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('searchGroups error: ' . $e->getMessage());
            return [];
        }
    }

    /** Remove a member from the group */
    public function removeMember(int $groupId, int $userId): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM GroupMember WHERE group_id = ? AND user_id = ?");
            return $stmt->execute([$groupId, $userId]);
        } catch (PDOException $e) {
            error_log('removeMember error: ' . $e->getMessage());
            return false;
        }
    }

    /** Check if a user is a member (active) of a group */
    public function isMember(int $groupId, int $userId): bool {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM GroupMember WHERE group_id = ? AND user_id = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$groupId, $userId]);
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('isMember error: ' . $e->getMessage());
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
     * Check if user has admin permissions in group
     */
    public function isGroupAdmin(int $groupId, int $userId): bool {
        $sql = "SELECT 1
                FROM GroupMember
                WHERE group_id = ?
                  AND user_id = ?
                  AND role IN ('admin','moderator')
                  AND status = 'active'
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$groupId, $userId]);
        return (bool)$stmt->fetchColumn();
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
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('SQL error in updateGroup: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete (deactivate) a group
     */
    public function deleteGroup(int $groupId): bool {
        $sql = "UPDATE GroupsTable SET is_active = FALSE WHERE group_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$groupId]);
    }

    /**
     * Get popular groups (sorted by member count)
     */
    public function getPopularGroups(int $limit = 12, int $userId = 0): array {
        $sql = "
            SELECT 
                g.*,
                CASE WHEN gm.user_id IS NULL THEN 0 ELSE 1 END AS is_member,
                CASE WHEN gjr.request_id IS NULL THEN 0 ELSE 1 END AS has_pending_request
            FROM GroupsTable g
            LEFT JOIN GroupMember gm
                ON gm.group_id = g.group_id
                AND gm.user_id = :user_id
                AND gm.status = 'active'
            LEFT JOIN GroupJoinRequests gjr
                ON gjr.group_id = g.group_id
                AND gjr.user_id = :user_id
                AND gjr.status = 'pending'
            WHERE g.is_active = TRUE
              AND g.privacy_status = 'public'
            ORDER BY g.member_count DESC, g.created_at DESC
            LIMIT :result_limit
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':result_limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getPopularGroups error: ' . $e->getMessage());
            return [];
        }
    }
}
