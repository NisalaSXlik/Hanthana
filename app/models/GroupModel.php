<?php
require_once __DIR__ . '/../core/Database.php';

class GroupModel {
    private $db;
    private $hasRoleChangeTables = false;
    private $hasDeleteApprovalTable = false;
    private $hasGroupModerationColumns = false;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->ensureGroupGovernanceTables();
        $this->ensureGroupModerationColumns();
        $this->reactivateExpiredGroups();
    }

    private function tableExists(string $tableName): bool {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    private function columnExists(string $tableName, string $columnName): bool {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$tableName} LIKE ?");
            $stmt->execute([$columnName]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function ensureGroupGovernanceTables(): void {
        try {
            $this->db->exec(
                "CREATE TABLE IF NOT EXISTS GroupRoleChangeRequests (
                    request_id INT AUTO_INCREMENT PRIMARY KEY,
                    group_id INT NOT NULL,
                    target_user_id INT NOT NULL,
                    requested_role ENUM('admin', 'member') NOT NULL,
                    current_role ENUM('admin', 'member') NOT NULL,
                    proposed_by INT NOT NULL,
                    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    resolved_at DATETIME NULL,
                    INDEX idx_grcr_group_status (group_id, status),
                    INDEX idx_grcr_target (target_user_id),
                    CONSTRAINT fk_grcr_group FOREIGN KEY (group_id) REFERENCES GroupsTable(group_id) ON DELETE CASCADE,
                    CONSTRAINT fk_grcr_target_user FOREIGN KEY (target_user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
                    CONSTRAINT fk_grcr_proposer FOREIGN KEY (proposed_by) REFERENCES Users(user_id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );

            $this->db->exec(
                "CREATE TABLE IF NOT EXISTS GroupRoleChangeVotes (
                    request_id INT NOT NULL,
                    admin_user_id INT NOT NULL,
                    voted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (request_id, admin_user_id),
                    INDEX idx_grcv_admin (admin_user_id),
                    CONSTRAINT fk_grcv_request FOREIGN KEY (request_id) REFERENCES GroupRoleChangeRequests(request_id) ON DELETE CASCADE,
                    CONSTRAINT fk_grcv_admin FOREIGN KEY (admin_user_id) REFERENCES Users(user_id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );

            $this->db->exec(
                "CREATE TABLE IF NOT EXISTS GroupDeleteApprovals (
                    group_id INT NOT NULL,
                    admin_user_id INT NOT NULL,
                    approved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (group_id, admin_user_id),
                    INDEX idx_gda_admin (admin_user_id),
                    CONSTRAINT fk_gda_group FOREIGN KEY (group_id) REFERENCES GroupsTable(group_id) ON DELETE CASCADE,
                    CONSTRAINT fk_gda_admin FOREIGN KEY (admin_user_id) REFERENCES Users(user_id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (Throwable $e) {
            error_log('ensureGroupGovernanceTables error: ' . $e->getMessage());
        }

        $this->hasRoleChangeTables = $this->tableExists('GroupRoleChangeRequests') && $this->tableExists('GroupRoleChangeVotes');
        $this->hasDeleteApprovalTable = $this->tableExists('GroupDeleteApprovals');
    }

    private function ensureGroupModerationColumns(): void {
        $columnDefinitions = [
            'disabled_until' => 'ALTER TABLE GroupsTable ADD COLUMN disabled_until DATETIME NULL DEFAULT NULL',
            'disable_reason' => 'ALTER TABLE GroupsTable ADD COLUMN disable_reason VARCHAR(255) NULL DEFAULT NULL',
            'disable_notes' => 'ALTER TABLE GroupsTable ADD COLUMN disable_notes TEXT NULL',
            'disabled_by' => 'ALTER TABLE GroupsTable ADD COLUMN disabled_by INT NULL DEFAULT NULL'
        ];

        foreach ($columnDefinitions as $column => $alterSql) {
            if ($this->columnExists('GroupsTable', $column)) {
                continue;
            }

            try {
                $this->db->exec($alterSql);
            } catch (Throwable $e) {
                error_log('ensureGroupModerationColumns error for ' . $column . ': ' . $e->getMessage());
            }
        }

        $required = ['disabled_until', 'disable_reason', 'disable_notes', 'disabled_by'];
        $this->hasGroupModerationColumns = true;
        foreach ($required as $column) {
            if (!$this->columnExists('GroupsTable', $column)) {
                $this->hasGroupModerationColumns = false;
                break;
            }
        }
    }

    private function reactivateExpiredGroups(): void {
        if (!$this->hasGroupModerationColumns) {
            return;
        }

        try {
            $sql = "UPDATE GroupsTable
                    SET is_active = 1,
                        disabled_until = NULL,
                        disable_reason = NULL,
                        disable_notes = NULL,
                        disabled_by = NULL,
                        updated_at = NOW()
                    WHERE COALESCE(is_active, 1) = 0
                        AND disabled_until IS NOT NULL
                        AND disabled_until <= NOW()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('reactivateExpiredGroups error: ' . $e->getMessage());
        }
    }

    /**
     * Get a lightweight list of groups a user belongs to (used on feed sidebar)
     */
    public function getUserGroups($userId, $limit = 6) {
        $sql = "SELECT 
                    g.group_id,
                    g.name,
                    g.display_picture,
                    gm.role,
                    gm.joined_at,
                    (SELECT COUNT(*) FROM GroupMember gm2 WHERE gm2.group_id = g.group_id AND gm2.status = 'active') AS member_count
                FROM GroupsTable g
                    INNER JOIN GroupMember gm ON g.group_id = gm.group_id
                WHERE gm.user_id = ? AND gm.status = 'active' AND COALESCE(g.is_active, 1) = 1
                ORDER BY gm.joined_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count how many active groups a user currently belongs to.
     */
    public function getUserJoinedGroupsCount(int $userId): int {
        if ($userId <= 0) {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS total
                FROM GroupMember gm
                    INNER JOIN GroupsTable g ON g.group_id = gm.group_id
                WHERE gm.user_id = ?
                    AND gm.status = 'active'
                    AND COALESCE(g.is_active, 1) = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Fetch a richer snapshot of the groups a user belongs to, including member counts.
     */
    public function getUserGroupsWithDetails(int $userId, int $limit = 5): array {
        if ($userId <= 0) {
            return [];
        }

        $limit = max(1, $limit);

        $sql = "SELECT
                    g.group_id,
                    g.name AS group_name,
                    g.description,
                    g.privacy_status,
                    g.display_picture AS group_photo,
                    gm.role,
                    gm.joined_at,
                    COALESCE(member_counts.member_count, 0) AS member_count
                FROM GroupMember gm
                    INNER JOIN GroupsTable g ON g.group_id = gm.group_id
                    LEFT JOIN (
                        SELECT group_id, COUNT(*) AS member_count
                        FROM GroupMember
                        WHERE status = 'active'
                        GROUP BY group_id
                    ) member_counts ON member_counts.group_id = g.group_id
                WHERE gm.user_id = ?
                    AND gm.status = 'active'
                    AND COALESCE(g.is_active, 1) = 1
                ORDER BY gm.joined_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get groups created by a user
     */
    public function getGroupsCreatedBy($userId) {
        $sql = "SELECT g.*, 
                       (SELECT COUNT(*) FROM GroupMember gm WHERE gm.group_id = g.group_id AND gm.status = 'active') as member_count
                FROM GroupsTable g 
                WHERE g.created_by = ? AND COALESCE(g.is_active, 1) = 1 
                ORDER BY g.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get groups joined by a user
     */
    public function getGroupsJoinedBy($userId) {
        $sql = "SELECT g.*, gm.joined_at,
                       (SELECT COUNT(*) FROM GroupMember gm2 WHERE gm2.group_id = g.group_id AND gm2.status = 'active') as member_count
                FROM GroupsTable g 
                INNER JOIN GroupMember gm ON g.group_id = gm.group_id 
                WHERE gm.user_id = ? AND gm.status = 'active' AND COALESCE(g.is_active, 1) = 1 
                ORDER BY gm.joined_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get group by ID
     */
    public function getById($groupId) {
        $sql = "SELECT g.*, 
                       u.username as creator_username,
                       u.first_name as creator_first_name,
                       u.last_name as creator_last_name,
                       (SELECT COUNT(*) FROM GroupMember gm WHERE gm.group_id = g.group_id AND gm.status = 'active') as member_count
                FROM GroupsTable g
                LEFT JOIN Users u ON g.created_by = u.user_id
                WHERE g.group_id = ? AND COALESCE(g.is_active, 1) = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new group
     */
    /*public function createGroup($data) {
        try {
            // GroupsTable
            $sql = "INSERT INTO GroupsTable (name, tag, description, focus, privacy_status, rules, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['tag'] ?? null,
                $data['description'] ?? null,
                $data['focus'] ?? null,
                $data['privacy_status'] ?? 'public',
                $data['rules'] ?? null,
                $data['created_by']
            ]);
            
            $groupId = $this->db->lastInsertId();

            // Conversations
            $sql = "INSERT INTO Conversations (conversation_type, name, created_by)
                    VALUES ('group', ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['name'] . " ⬥ Main",
                $data['created_by']
            ]);

            $convoId = $this->db->lastInsertId();

            // Channel
            $sql = "INSERT INTO Channel (group_id, name, created_by, conversation_id)
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $groupId,
                'Main',
                $data['created_by'],
                $convoId
            ]);

            // Membership
            if ($groupId) {
                $this->addMember($groupId, $data['created_by'], 'admin');
            }

            return $groupId;

        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            throw $e;
        }
    }*/

    public function createGroup($data, $userId) {
    $connection = $this->db;
    $connection->beginTransaction();
    try {
        // 1. Create Group
        $groupstmt = $connection->prepare(
            "INSERT INTO GroupsTable (name, tag, description, focus, privacy_status, rules, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $groupstmt->execute([
            $data['name'],
            $data['tag'] ?? null,
            $data['description'] ?? null,
            $data['focus'] ?? null,
            $data['privacy_status'] ?? 'public',
            $data['rules'] ?? null,
            $userId
        ]);
        $groupId = (int)$connection->lastInsertId();

        // 2. Create Conversation
        $converstaionstmt = $connection->prepare(
            "INSERT INTO Conversations (conversation_type, name, created_by, last_message_at, last_message_text)
             VALUES ('group', ?, ?, NOW(), ?)"
        );
        $welcomeText = 'Welcome to the group!';
        $converstaionstmt->execute([
            $data['name'] . " ⬥ Main",
            $userId,
            $welcomeText
        ]);
        $convoId = (int)$connection->lastInsertId();

        // 3. Create Channel
        $channelstmt = $connection->prepare(
            "INSERT INTO Channel (group_id, name, created_by, conversation_id, display_picture)
             VALUES (?, ?, ?, ?, ?)"
        );
        $channelstmt->execute([
            $groupId,
            'Main',
            $userId,
            $convoId,
            'uploads/channel_dp/default.png'
        ]);

        // 4. Add Creator as Admin Participant
        $cpstmt = $connection->prepare(
            "INSERT INTO ConversationParticipants (conversation_id, user_id, role, is_active)
             VALUES (?, ?, 'admin', 1)"
        );
        $cpstmt->execute([$convoId, $userId]);

        // 5. Insert Welcome Message
        $messagestmt = $connection->prepare(
            "INSERT INTO Messages (conversation_id, sender_id, message_type, content)
             VALUES (?, ?, 'system', ?)"
        );
        $messagestmt->execute([
            $convoId, 
            $userId,
            $welcomeText
        ]);

        $connection->commit();

        // Add to members table
        if ($groupId) {
            $this->addMember($groupId, $userId, 'admin');
        }

        return $groupId;

    } catch (\Throwable $e) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        error_log("Group Creation Failed: " . $e->getMessage());
        throw new RuntimeException('Unable to create group.');
    }
}

    /**
     * Update group details
     */
    public function updateGroup($groupId, $data) {
        $allowedFields = ['name', 'tag', 'description', 'focus', 'privacy_status', 'rules', 'display_picture', 'cover_image'];
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $groupId;
        $sql = "UPDATE GroupsTable SET " . implode(', ', $updates) . " WHERE group_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete group (soft delete)
     */
    public function deleteGroup($groupId) {
        $sql = "UPDATE GroupsTable SET is_active = 0, updated_at = NOW() WHERE group_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$groupId]);
    }

    /**
     * Add member to group
     */
    public function addMember($groupId, $userId, $role = 'member') {
        // Check if already a member
        $existing = $this->getMembership($groupId, $userId);
        if ($existing) {
            if ($existing['status'] === 'active') {
                return false; // Already a member
            } else {
                // Update existing record
                $sql = "UPDATE GroupMember SET status = 'active', role = ?, joined_at = NOW() WHERE group_id = ? AND user_id = ?";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$role, $groupId, $userId]);
                
                // Add to Main channel conversation
                if ($result) {
                    $this->addMemberToMainChannel($groupId, $userId);
                }
                
                return $result;
            }
        }
        
        // Add new member
        $sql = "INSERT INTO GroupMember (group_id, user_id, role, status, joined_at) VALUES (?, ?, ?, 'active', NOW())";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$groupId, $userId, $role]);
        
        // Add to Main channel conversation
        if ($result) {
            $this->addMemberToMainChannel($groupId, $userId);
        }
        
        return $result;
    }

    /**
     * Add user to Main channel conversation when they join a group
     */
    private function addMemberToMainChannel($groupId, $userId) {
        // Find the Main channel's conversation
        $sql = "SELECT c.conversation_id 
                FROM Channel ch
                INNER JOIN Conversations c ON ch.conversation_id = c.conversation_id
                WHERE ch.group_id = ? AND ch.name = 'Main'
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId]);
        $conversationId = $stmt->fetchColumn();
        
        if ($conversationId) {
            // Add user as participant if not already
            $checkSql = "SELECT 1 FROM ConversationParticipants 
                     WHERE conversation_id = ? AND user_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$conversationId, $userId]);
            
            if (!$checkStmt->fetchColumn()) {
                $insertSql = "INSERT INTO ConversationParticipants (conversation_id, user_id, role, is_active)
                          VALUES (?, ?, 'member', TRUE)";
                $insertStmt = $this->db->prepare($insertSql);
                $insertStmt->execute([$conversationId, $userId]);
            }
        }
    }

    /**
     * Get membership information
     */
    public function getMembership($groupId, $userId) {
        $sql = "SELECT * FROM GroupMember WHERE group_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get user's membership state in a group
     */
    public function getUserMembershipState($groupId, $userId) {
        $membership = $this->getMembership($groupId, $userId);
        if (!$membership) {
            return 'not_joined';
        }
        return $membership['status'] === 'active' ? 'active' : $membership['status'];
    }

    /**
     * Check if user is group admin
     */
    public function isGroupAdmin($groupId, $userId) {
        $membership = $this->getMembership($groupId, $userId);
        return $membership && $membership['status'] === 'active' && $membership['role'] === 'admin';
    }

    /**
     * Check if user is group member
     */
    public function isMember($groupId, $userId) {
        $membership = $this->getMembership($groupId, $userId);
        return $membership && $membership['status'] === 'active';
    }

    /**
     * Remove member from group
     */
    public function removeMember($groupId, $userId) {
        $sql = "DELETE FROM GroupMember WHERE group_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$groupId, $userId]);
    }

    /**
     * Get group members
     */
    public function getMembers($groupId, $status = 'active') {
        $sql = "SELECT gm.*, u.user_id, u.username, u.first_name, u.last_name, u.profile_picture
            FROM GroupMember gm
                INNER JOIN Users u ON gm.user_id = u.user_id
                WHERE gm.group_id = ? AND gm.status = ?
                ORDER BY 
                    CASE gm.role 
                        WHEN 'admin' THEN 1 
                        ELSE 2 
                    END,
                    gm.joined_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId, $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get group admins
     */
    public function getGroupAdmins($groupId) {
        $sql = "SELECT gm.*, u.user_id, u.username, u.first_name, u.last_name, u.profile_picture
            FROM GroupMember gm
                INNER JOIN Users u ON gm.user_id = u.user_id
                WHERE gm.group_id = ? AND gm.status = 'active' AND gm.role = 'admin'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if user has pending join request
     */
    public function hasPendingRequest($groupId, $userId) {
        $sql = "SELECT 1 FROM GroupMember WHERE group_id = ? AND user_id = ? AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * Create join request
     */
    public function createJoinRequest($groupId, $userId) {
        // Check if already exists
        $existing = $this->getMembership($groupId, $userId);
        if ($existing) {
            if ($existing['status'] === 'pending') {
                return 'exists'; // Already has pending request
            }
            return false; // Already a member or other status
        }
        
        // Create new pending request
        $sql = "INSERT INTO GroupMember (group_id, user_id, role, status, joined_at) VALUES (?, ?, 'member', 'pending', NULL)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$groupId, $userId]);
    }

    /**
     * Get pending join requests
     */
    public function getPendingRequests($groupId) {
        $sql = "SELECT gm.*, u.user_id, u.username, u.first_name, u.last_name, u.profile_picture
                , gm.joined_at AS requested_at
            FROM GroupMember gm
                INNER JOIN Users u ON gm.user_id = u.user_id
                WHERE gm.group_id = ? AND gm.status = 'pending'
                ORDER BY gm.joined_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Approve join request
     */
    public function approveJoinRequest($groupId, $userId, $adminId) {
        $sql = "UPDATE GroupMember SET status = 'active', joined_at = NOW() WHERE group_id = ? AND user_id = ? AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$groupId, $userId]);

        if ($result && $stmt->rowCount() > 0) {
            $this->addMemberToMainChannel($groupId, $userId);
        }

        return $result;
    }

    /**
     * Reject join request
     */
    public function rejectJoinRequest($groupId, $userId, $adminId) {
        $sql = "DELETE FROM GroupMember WHERE group_id = ? AND user_id = ? AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$groupId, $userId]);
    }

    /**
     * Search groups
     */
    public function searchGroups($query, $userId = null) {
        $sql = "SELECT g.*, 
                   (SELECT COUNT(*) FROM GroupMember gm WHERE gm.group_id = g.group_id AND gm.status = 'active') as member_count,
                   EXISTS(SELECT 1 FROM GroupMember gm2 WHERE gm2.group_id = g.group_id AND gm2.user_id = ? AND gm2.status = 'active') as is_member
            FROM GroupsTable g
            WHERE COALESCE(g.is_active, 1) = 1
                AND (g.name LIKE ? OR g.tag LIKE ? OR g.description LIKE ? OR g.focus LIKE ?)
                ORDER BY g.created_at DESC";
        
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get popular groups ranked by recent joins and total member count
     */
    public function getPopularGroups($limit = 5, $userId = 0) {
        $userMembershipSql = $userId ? ", EXISTS(SELECT 1 FROM GroupMember WHERE group_id = g.group_id AND user_id = {$userId} AND status = 'active') AS is_member" : ", 0 AS is_member";
        
        $sql = "SELECT 
                    g.group_id,
                    g.name,
                    g.description,
                    g.privacy_status,
                    g.display_picture,
                    g.cover_image,
                    g.tag,
                    COALESCE(members.member_count, 0) AS member_count,
                    COALESCE(recent_joins.joins_last_7, 0) AS recent_joins,
                    (COALESCE(recent_joins.joins_last_7, 0) * 3 + COALESCE(members.member_count, 0) * 0.5) AS engagement_score
                    {$userMembershipSql}
                FROM GroupsTable g
                LEFT JOIN (
                    SELECT group_id, COUNT(*) AS member_count
                    FROM GroupMember
                    WHERE status = 'active'
                    GROUP BY group_id
                ) members ON members.group_id = g.group_id
                LEFT JOIN (
                    SELECT group_id, COUNT(*) AS joins_last_7
                    FROM GroupMember
                    WHERE status = 'active' 
                        AND joined_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY group_id
                ) recent_joins ON recent_joins.group_id = g.group_id
                WHERE LOWER(TRIM(COALESCE(g.privacy_status, 'public'))) = 'public'
                    AND COALESCE(g.is_active, 1) = 1
                ORDER BY engagement_score DESC, member_count DESC, g.created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if group tag exists
     */
    public function isTagTaken($tag, $excludeGroupId = null) {
        $sql = "SELECT 1 FROM GroupsTable WHERE tag = ? AND is_active = 1";
        $params = [$tag];
        
        if ($excludeGroupId) {
            $sql .= " AND group_id != ?";
            $params[] = $excludeGroupId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * Get all groups (for admin or browsing)
     */
    public function getAllGroups($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT g.*, 
                   u.username as creator_username,
                   COUNT(gm.user_id) as member_count
            FROM GroupsTable g
            LEFT JOIN Users u ON g.created_by = u.user_id
            LEFT JOIN GroupMember gm ON g.group_id = gm.group_id AND gm.status = 'active'
            WHERE COALESCE(g.is_active, 1) = 1
                GROUP BY g.group_id
                ORDER BY g.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total groups count
     */
    public function getTotalGroupsCount() {
        $sql = "SELECT COUNT(*) as total FROM GroupsTable WHERE COALESCE(is_active, 1) = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Get groups by privacy status
     */
    public function getGroupsByPrivacy($privacyStatus, $userId = null) {
        $sql = "SELECT g.*, 
                   COUNT(gm.user_id) as member_count
                FROM GroupsTable g
                LEFT JOIN GroupMember gm ON g.group_id = gm.group_id AND gm.status = 'active'
                WHERE COALESCE(g.is_active, 1) = 1 AND g.privacy_status = ?
                GROUP BY g.group_id
                ORDER BY g.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$privacyStatus]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent group activity
     */
    public function getRecentGroupActivity($groupId, $limit = 10) {
        $sql = "SELECT 'post' as type, p.post_id as id, p.content, p.created_at, u.user_id, u.username, u.first_name, u.last_name
                FROM Post p
                INNER JOIN Users u ON p.author_id = u.user_id
                WHERE p.group_id = ? AND p.is_group_post = 1
                ORDER BY p.created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update group member role
     */
    public function updateMemberRole($groupId, $userId, $role) {
        $sql = "UPDATE GroupMember SET role = ? WHERE group_id = ? AND user_id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$role, $groupId, $userId]);
    }

    public function getActiveAdminCount(int $groupId): int {
        $sql = "SELECT COUNT(*) AS c FROM GroupMember WHERE group_id = ? AND status = 'active' AND role = 'admin'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public function getDeleteApprovalStatus(int $groupId, int $viewerId = 0): array {
        if (!$this->hasDeleteApprovalTable) {
            $this->ensureGroupGovernanceTables();
        }

        $adminCount = $this->getActiveAdminCount($groupId);

        if (!$this->hasDeleteApprovalTable) {
            return [
                'admin_count' => $adminCount,
                'approved_count' => 0,
                'viewer_approved' => false,
                'all_approved' => false
            ];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM GroupDeleteApprovals WHERE group_id = ?");
        $stmt->execute([$groupId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $approvedCount = (int)($row['c'] ?? 0);

        $viewerApproved = false;
        if ($viewerId > 0) {
            $voteStmt = $this->db->prepare("SELECT 1 FROM GroupDeleteApprovals WHERE group_id = ? AND admin_user_id = ? LIMIT 1");
            $voteStmt->execute([$groupId, $viewerId]);
            $viewerApproved = (bool)$voteStmt->fetchColumn();
        }

        return [
            'admin_count' => $adminCount,
            'approved_count' => $approvedCount,
            'viewer_approved' => $viewerApproved,
            'all_approved' => ($adminCount > 0 && $approvedCount >= $adminCount)
        ];
    }

    public function approveGroupDeletion(int $groupId, int $adminId): array {
        if (!$this->hasDeleteApprovalTable) {
            $this->ensureGroupGovernanceTables();
        }

        if (!$this->hasDeleteApprovalTable) {
            return ['success' => false, 'message' => 'Group deletion approvals are unavailable until database migrations are applied.'];
        }

        if (!$this->isGroupAdmin($groupId, $adminId)) {
            return ['success' => false, 'message' => 'Only group admins can approve deletion.'];
        }

        $ins = $this->db->prepare(
            "INSERT INTO GroupDeleteApprovals (group_id, admin_user_id, approved_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE approved_at = approved_at"
        );
        $ins->execute([$groupId, $adminId]);

        $status = $this->getDeleteApprovalStatus($groupId, $adminId);
        if ($status['all_approved']) {
            $deleted = $this->deleteGroup($groupId);
            if ($deleted) {
                return [
                    'success' => true,
                    'deleted' => true,
                    'message' => 'All admins approved. Group deleted.',
                    'status' => $status
                ];
            }

            return ['success' => false, 'message' => 'All approvals collected, but failed to delete group.', 'status' => $status];
        }

        return [
            'success' => true,
            'deleted' => false,
            'message' => 'Delete approval recorded.',
            'status' => $status
        ];
    }

    public function createRoleChangeRequest(int $groupId, int $targetUserId, string $requestedRole, int $adminId): array {
        if (!$this->hasRoleChangeTables) {
            $this->ensureGroupGovernanceTables();
        }

        if (!$this->hasRoleChangeTables) {
            return ['success' => false, 'message' => 'Role change voting is unavailable until database migrations are applied.'];
        }

        $requestedRole = strtolower(trim($requestedRole));
        if (!in_array($requestedRole, ['admin', 'member'], true)) {
            return ['success' => false, 'message' => 'Invalid target role.'];
        }

        if (!$this->isGroupAdmin($groupId, $adminId)) {
            return ['success' => false, 'message' => 'Only group admins can start role votes.'];
        }

        $membership = $this->getMembership($groupId, $targetUserId);
        if (!$membership || ($membership['status'] ?? '') !== 'active') {
            return ['success' => false, 'message' => 'Target user is not an active member.'];
        }

        $currentRole = strtolower((string)($membership['role'] ?? 'member'));
        if ($currentRole === $requestedRole) {
            return ['success' => false, 'message' => 'User already has that role.'];
        }

        $existingStmt = $this->db->prepare(
            "SELECT request_id
             FROM GroupRoleChangeRequests
             WHERE group_id = ? AND target_user_id = ? AND requested_role = ? AND status = 'pending'
             ORDER BY created_at DESC
             LIMIT 1"
        );
        $existingStmt->execute([$groupId, $targetUserId, $requestedRole]);
        $existingId = (int)$existingStmt->fetchColumn();

        if ($existingId > 0) {
            return $this->approveRoleChangeRequest($groupId, $existingId, $adminId);
        }

        $this->db->beginTransaction();
        try {
            $insert = $this->db->prepare(
                "INSERT INTO GroupRoleChangeRequests
                    (group_id, target_user_id, requested_role, current_role, proposed_by, status, created_at)
                 VALUES (?, ?, ?, ?, ?, 'pending', NOW())"
            );
            $insert->execute([$groupId, $targetUserId, $requestedRole, $currentRole, $adminId]);
            $requestId = (int)$this->db->lastInsertId();

            $voteInsert = $this->db->prepare(
                "INSERT INTO GroupRoleChangeVotes (request_id, admin_user_id, voted_at)
                 VALUES (?, ?, NOW())"
            );
            $voteInsert->execute([$requestId, $adminId]);

            $this->db->commit();

            return $this->approveRoleChangeRequest($groupId, $requestId, $adminId, true);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Failed to create role vote request.'];
        }
    }

    public function approveRoleChangeRequest(int $groupId, int $requestId, int $adminId, bool $alreadyVoted = false): array {
        if (!$this->hasRoleChangeTables) {
            $this->ensureGroupGovernanceTables();
        }

        if (!$this->hasRoleChangeTables) {
            return ['success' => false, 'message' => 'Role change voting is unavailable until database migrations are applied.'];
        }

        if (!$this->isGroupAdmin($groupId, $adminId)) {
            return ['success' => false, 'message' => 'Only group admins can vote on role changes.'];
        }

        $reqStmt = $this->db->prepare(
            "SELECT request_id, group_id, target_user_id, requested_role, status
             FROM GroupRoleChangeRequests
             WHERE request_id = ? AND group_id = ?
             LIMIT 1"
        );
        $reqStmt->execute([$requestId, $groupId]);
        $request = $reqStmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            return ['success' => false, 'message' => 'Role vote request not found.'];
        }

        if (($request['status'] ?? '') !== 'pending') {
            return ['success' => false, 'message' => 'This role vote is already closed.'];
        }

        if (!$alreadyVoted) {
            $voteStmt = $this->db->prepare(
                "INSERT INTO GroupRoleChangeVotes (request_id, admin_user_id, voted_at)
                 VALUES (?, ?, NOW())
                 ON DUPLICATE KEY UPDATE voted_at = voted_at"
            );
            $voteStmt->execute([$requestId, $adminId]);
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS c FROM GroupRoleChangeVotes WHERE request_id = ?");
        $countStmt->execute([$requestId]);
        $voteCount = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        $adminCount = $this->getActiveAdminCount($groupId);
        $needed = max(1, (int)floor($adminCount / 2) + 1);

        if ($voteCount >= $needed) {
            $this->db->beginTransaction();
            try {
                $roleStmt = $this->db->prepare(
                    "UPDATE GroupMember
                     SET role = ?
                     WHERE group_id = ? AND user_id = ? AND status = 'active'"
                );
                $roleStmt->execute([$request['requested_role'], $groupId, (int)$request['target_user_id']]);

                $closeStmt = $this->db->prepare(
                    "UPDATE GroupRoleChangeRequests
                     SET status = 'approved', resolved_at = NOW()
                     WHERE request_id = ?"
                );
                $closeStmt->execute([$requestId]);

                $this->db->commit();

                return [
                    'success' => true,
                    'applied' => true,
                    'message' => 'Majority reached. Member role updated.',
                    'votes' => $voteCount,
                    'needed' => $needed
                ];
            } catch (Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'message' => 'Vote passed, but failed to update role.'];
            }
        }

        return [
            'success' => true,
            'applied' => false,
            'message' => 'Vote recorded.',
            'votes' => $voteCount,
            'needed' => $needed
        ];
    }

    public function getRoleChangeRequests(int $groupId, int $viewerId = 0): array {
        if (!$this->hasRoleChangeTables) {
            $this->ensureGroupGovernanceTables();
        }

        if (!$this->hasRoleChangeTables) {
            return [];
        }

        $sql = "SELECT
                    r.request_id,
                    r.group_id,
                    r.target_user_id,
                    r.requested_role,
                    r.current_role,
                    r.proposed_by,
                    r.status,
                    r.created_at,
                    r.resolved_at,
                    u.username AS target_username,
                    u.first_name AS target_first_name,
                    u.last_name AS target_last_name,
                    COUNT(v.admin_user_id) AS vote_count
                FROM GroupRoleChangeRequests r
                INNER JOIN Users u ON u.user_id = r.target_user_id
                LEFT JOIN GroupRoleChangeVotes v ON v.request_id = r.request_id
                WHERE r.group_id = ?
                GROUP BY r.request_id
                ORDER BY FIELD(r.status, 'pending', 'approved', 'rejected'), r.created_at DESC
                LIMIT 25";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $adminCount = $this->getActiveAdminCount($groupId);
        $needed = max(1, (int)floor($adminCount / 2) + 1);

        if ($viewerId > 0 && !empty($rows)) {
            $voteStmt = $this->db->prepare("SELECT 1 FROM GroupRoleChangeVotes WHERE request_id = ? AND admin_user_id = ? LIMIT 1");
            foreach ($rows as &$row) {
                $voteStmt->execute([(int)$row['request_id'], $viewerId]);
                $row['viewer_voted'] = (bool)$voteStmt->fetchColumn();
                $row['votes_needed'] = $needed;
            }
            unset($row);
        }

        return $rows;
    }

    /**
     * Get group statistics
     */
    public function getGroupStatistics($groupId) {
        $sql = "SELECT 
            (SELECT COUNT(*) FROM GroupMember WHERE group_id = ? AND status = 'active') as total_members,
            (SELECT COUNT(*) FROM Post WHERE group_id = ? AND is_group_post = 1) as total_posts,
            (SELECT COUNT(*) FROM GroupMember WHERE group_id = ? AND status = 'pending') as pending_requests";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId, $groupId, $groupId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) AS total_groups,
                    SUM(CASE WHEN COALESCE(is_active, 1) = 1 THEN 1 ELSE 0 END) AS active_groups,
                    SUM(CASE WHEN COALESCE(is_active, 1) = 0 THEN 1 ELSE 0 END) AS inactive_groups,
                    SUM(CASE WHEN LOWER(privacy_status) = 'public' THEN 1 ELSE 0 END) AS public_groups,
                    SUM(CASE WHEN LOWER(privacy_status) IN ('private', 'secret') THEN 1 ELSE 0 END) AS private_groups
                FROM GroupsTable";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total_groups' => (int)($row['total_groups'] ?? 0),
            'active_groups' => (int)($row['active_groups'] ?? 0),
            'inactive_groups' => (int)($row['inactive_groups'] ?? 0),
            'public_groups' => (int)($row['public_groups'] ?? 0),
            'private_groups' => (int)($row['private_groups'] ?? 0)
        ];
    }

    public function getReviewSnapshot(): array {
        $summarySql = "SELECT 
                COUNT(*) AS total_groups,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS new_last_7,
                SUM(CASE WHEN COALESCE(is_active, 1) = 0 THEN 1 ELSE 0 END) AS inactive_groups
            FROM GroupsTable";

        $stmt = $this->db->prepare($summarySql);
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $pendingSql = "SELECT COUNT(*) AS pending_requests FROM GroupMember WHERE status = 'pending'";
        $pendingStmt = $this->db->prepare($pendingSql);
        $pendingStmt->execute();
        $pending = $pendingStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $avgMembersSql = "SELECT AVG(member_count) AS avg_members FROM (
                SELECT COUNT(*) AS member_count
                FROM GroupMember
                WHERE status = 'active'
                GROUP BY group_id
            ) active_counts";
        $avgStmt = $this->db->prepare($avgMembersSql);
        $avgStmt->execute();
        $avg = $avgStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_groups' => (int)($summary['total_groups'] ?? 0),
            'new_last_7' => (int)($summary['new_last_7'] ?? 0),
            'inactive_groups' => (int)($summary['inactive_groups'] ?? 0),
            'pending_requests' => (int)($pending['pending_requests'] ?? 0),
            'avg_members' => round((float)($avg['avg_members'] ?? 0), 1)
        ];
    }

    public function disableGroup(int $groupId, ?string $disableUntil = null, ?string $reason = null, ?int $adminId = null, ?string $notes = null): bool {
        if ($groupId <= 0) {
            return false;
        }

        $this->reactivateExpiredGroups();

        if ($this->hasGroupModerationColumns) {
            $sql = "UPDATE GroupsTable
                    SET is_active = 0,
                        disabled_until = :disabled_until,
                        disable_reason = :disable_reason,
                        disable_notes = :disable_notes,
                        disabled_by = :disabled_by,
                        updated_at = NOW()
                    WHERE group_id = :group_id
                        AND COALESCE(is_active, 1) = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':disabled_until' => $disableUntil,
                ':disable_reason' => $reason,
                ':disable_notes' => $notes,
                ':disabled_by' => $adminId,
                ':group_id' => $groupId
            ]);
            return $stmt->rowCount() > 0;
        }

        $sql = "UPDATE GroupsTable SET is_active = 0, updated_at = NOW() WHERE group_id = ? AND COALESCE(is_active, 1) = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId]);
        return $stmt->rowCount() > 0;
    }


    /**
     * Get popular groups ranked by engagement (recent joins, posts, comments, member count)
     */
    public function getTrendingGroups($limit = 5, $userId = 0) {
        $userMembershipSql = $userId ? ", EXISTS(SELECT 1 FROM GroupMember WHERE group_id = g.group_id AND user_id = {$userId} AND status = 'active') AS is_member" : ", 0 AS is_member";
        
        $sql = "SELECT 
                    g.group_id,
                    g.name,
                    g.description,
                    g.privacy_status,
                    g.display_picture,
                    g.cover_image,
                    g.tag,
                    COALESCE(members.member_count, 0) AS member_count,
                    COALESCE(recent_joins.joins_last_7, 0) AS recent_joins,
                    COALESCE(recent_posts.posts_last_7, 0) AS recent_posts,
                    COALESCE(recent_comments.comments_last_7, 0) AS recent_comments,
                    (
                        COALESCE(recent_joins.joins_last_7, 0) * 3 +
                        COALESCE(recent_posts.posts_last_7, 0) * 2 +
                        COALESCE(recent_comments.comments_last_7, 0) +
                        COALESCE(members.member_count, 0) * 0.1
                    ) AS engagement_score
                    {$userMembershipSql}
                FROM GroupsTable g
                LEFT JOIN (
                    SELECT group_id, COUNT(*) AS member_count
                    FROM GroupMember
                    WHERE status = 'active'
                    GROUP BY group_id
                ) members ON members.group_id = g.group_id
                LEFT JOIN (
                    SELECT group_id, COUNT(*) AS joins_last_7
                    FROM GroupMember
                    WHERE status = 'active' 
                        AND joined_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY group_id
                ) recent_joins ON recent_joins.group_id = g.group_id
                LEFT JOIN (
                    SELECT group_id, COUNT(*) AS posts_last_7
                    FROM Post
                    WHERE is_group_post = 1
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY group_id
                ) recent_posts ON recent_posts.group_id = g.group_id
                LEFT JOIN (
                    SELECT p.group_id, COUNT(*) AS comments_last_7
                    FROM Comment c
                    JOIN Post p ON p.post_id = c.post_id
                    WHERE p.is_group_post = 1
                        AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY p.group_id
                ) recent_comments ON recent_comments.group_id = g.group_id
                WHERE LOWER(TRIM(COALESCE(g.privacy_status, 'public'))) = 'public'
                    AND COALESCE(g.is_active, 1) = 1
                ORDER BY engagement_score DESC, member_count DESC, g.created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentGroups($limit = 5) {
        $sql = "SELECT 
                    g.group_id,
                    g.name,
                    g.privacy_status,
                    g.created_at,
                    g.focus,
                    g.tag,
                    COALESCE(g.is_active, 1) AS is_active,
                    COUNT(CASE WHEN gm.status = 'active' THEN gm.user_id END) AS member_count
                FROM GroupsTable g
                LEFT JOIN GroupMember gm ON gm.group_id = g.group_id
                WHERE COALESCE(g.is_active, 1) = 1
                GROUP BY g.group_id
                ORDER BY g.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdminGroupDirectory(?string $status = null, string $search = '', int $limit = 300): array {
        $this->reactivateExpiredGroups();

        $limit = max(25, min(1000, $limit));
        $status = strtolower(trim((string)$status));
        $search = trim($search);

        $sql = "SELECT
                    g.group_id,
                    g.name,
                    g.tag,
                    g.focus,
                    g.privacy_status,
                    COALESCE(g.is_active, 1) AS is_active,
                    g.created_at,
                    g.disabled_until,
                    g.disable_reason,
                    creator.user_id AS creator_id,
                    creator.username AS creator_username,
                    creator.first_name AS creator_first_name,
                    creator.last_name AS creator_last_name,
                    COALESCE(member_counts.member_count, 0) AS member_count,
                    COALESCE(pending_counts.pending_requests, 0) AS pending_requests,
                    COALESCE(post_counts.posts_last_30, 0) AS posts_last_30
                FROM GroupsTable g
                LEFT JOIN Users creator ON creator.user_id = g.created_by
                LEFT JOIN (
                    SELECT group_id, COUNT(*) AS member_count
                    FROM GroupMember
                    WHERE status = 'active'
                    GROUP BY group_id
                ) member_counts ON member_counts.group_id = g.group_id
                LEFT JOIN (
                    SELECT group_id, COUNT(*) AS pending_requests
                    FROM GroupMember
                    WHERE status = 'pending'
                    GROUP BY group_id
                ) pending_counts ON pending_counts.group_id = g.group_id
                LEFT JOIN (
                    SELECT group_id, COUNT(*) AS posts_last_30
                    FROM Post
                    WHERE is_group_post = 1
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY group_id
                ) post_counts ON post_counts.group_id = g.group_id";

        $conditions = [];
        $params = [];

        if ($status === 'active') {
            $conditions[] = 'COALESCE(g.is_active, 1) = 1';
        } elseif ($status === 'inactive' || $status === 'disabled') {
            $conditions[] = 'COALESCE(g.is_active, 1) = 0';
        }

        if ($search !== '') {
            $conditions[] = '(g.name LIKE :search OR g.tag LIKE :search OR g.focus LIKE :search OR creator.username LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY COALESCE(g.is_active, 1) DESC, g.created_at DESC LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function enableGroup(int $groupId): bool {
        if ($groupId <= 0) {
            return false;
        }

        $this->reactivateExpiredGroups();

        if ($this->hasGroupModerationColumns) {
            $sql = "UPDATE GroupsTable
                    SET is_active = 1,
                        disabled_until = NULL,
                        disable_reason = NULL,
                        disable_notes = NULL,
                        disabled_by = NULL,
                        updated_at = NOW()
                    WHERE group_id = :group_id
                        AND COALESCE(is_active, 1) = 0";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':group_id' => $groupId]);
            return $stmt->rowCount() > 0;
        }

        $sql = "UPDATE GroupsTable SET is_active = 1, updated_at = NOW() WHERE group_id = ? AND COALESCE(is_active, 1) = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId]);
        return $stmt->rowCount() > 0;
    }
}
?>