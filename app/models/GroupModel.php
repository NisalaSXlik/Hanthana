<?php
require_once __DIR__ . '/../core/Database.php';

class GroupModel {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
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
            if ($this->hasPendingRequest($groupId, $userId)) {
                return 'pending';
            }
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
        if ($this->tableExists('GroupJoinRequests')) {
            $sql = "SELECT 1 FROM GroupJoinRequests WHERE group_id = ? AND user_id = ? AND status = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$groupId, $userId]);
            if ($stmt->fetch(PDO::FETCH_ASSOC) !== false) {
                return true;
            }
        }

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

        if ($this->tableExists('GroupJoinRequests')) {
            $sql = "SELECT status FROM GroupJoinRequests WHERE group_id = ? AND user_id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$groupId, $userId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($request) {
                if (($request['status'] ?? '') === 'pending') {
                    return 'exists';
                }

                $sql = "UPDATE GroupJoinRequests
                        SET status = 'pending', requested_at = NOW(), reviewed_by = NULL, reviewed_at = NULL
                        WHERE group_id = ? AND user_id = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$groupId, $userId]);
            }

            $sql = "INSERT INTO GroupJoinRequests (group_id, user_id, status, requested_at) VALUES (?, ?, 'pending', NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$groupId, $userId]);
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
        if ($this->tableExists('GroupJoinRequests')) {
            $sql = "SELECT gjr.request_id, gjr.group_id, gjr.user_id, gjr.status,
                           gjr.requested_at,
                           gjr.requested_at AS joined_at,
                           u.username, u.first_name, u.last_name, u.profile_picture
                    FROM GroupJoinRequests gjr
                    INNER JOIN Users u ON gjr.user_id = u.user_id
                    WHERE gjr.group_id = ? AND gjr.status = 'pending'
                    ORDER BY gjr.requested_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$groupId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

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
        if ($this->tableExists('GroupJoinRequests')) {
            $sql = "UPDATE GroupJoinRequests
                    SET status = 'approved', reviewed_by = ?, reviewed_at = NOW()
                    WHERE group_id = ? AND user_id = ? AND status = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$adminId, $groupId, $userId]);

            if ($stmt->rowCount() > 0) {
                $added = $this->addMember($groupId, $userId, 'member');
                if ($added) {
                    return true;
                }

                $membership = $this->getMembership($groupId, $userId);
                return $membership && ($membership['status'] ?? '') === 'active';
            }
        }

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
        if ($this->tableExists('GroupJoinRequests')) {
            $sql = "UPDATE GroupJoinRequests
                    SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW()
                    WHERE group_id = ? AND user_id = ? AND status = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$adminId, $groupId, $userId]);

            if ($stmt->rowCount() > 0) {
                return true;
            }
        }

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

    private function closeExpiredGovernanceVotes(int $groupId): void {
        $expiredSql = "SELECT vote_event_id
                       FROM GroupGovernanceVoteEvent
                       WHERE group_id = ? AND result = 'in_process' AND expires_at <= NOW()";
        $stmt = $this->db->prepare($expiredSql);
        $stmt->execute([$groupId]);
        $eventIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        foreach ($eventIds as $eventId) {
            $this->finalizeGovernanceVoteEvent($groupId, (int)$eventId, true);
        }
    }

    private function finalizeGovernanceVoteEvent(int $groupId, int $eventId, bool $expired = false): void {
        $countStmt = $this->db->prepare(
            "SELECT
                SUM(CASE WHEN vote_choice = 'in_favor' THEN 1 ELSE 0 END) AS in_favor_count,
                SUM(CASE WHEN vote_choice = 'not_in_favor' THEN 1 ELSE 0 END) AS not_in_favor_count
             FROM GroupGovernanceVote
             WHERE vote_event_id = ?"
        );
        $countStmt->execute([$eventId]);
        $counts = $countStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $inFavor = (int)($counts['in_favor_count'] ?? 0);
        $notInFavor = (int)($counts['not_in_favor_count'] ?? 0);
        $result = ($inFavor > $notInFavor) ? 'accepted' : (($inFavor === $notInFavor && $expired) ? 'expired' : 'rejected');

        $metaStmt = $this->db->prepare(
            "SELECT vote_type, target_id, target_type, meta_json
             FROM GroupGovernanceVoteEvent
             WHERE vote_event_id = ? AND group_id = ?
             LIMIT 1"
        );
        $metaStmt->execute([$eventId, $groupId]);
        $event = $metaStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$event) {
            return;
        }

        if ($result === 'accepted' && ($event['vote_type'] ?? '') === 'member_role_change' && ($event['target_type'] ?? '') === 'user') {
            $meta = json_decode((string)($event['meta_json'] ?? '{}'), true) ?: [];
            $requestedRole = strtolower((string)($meta['to_role'] ?? $meta['requested_role'] ?? 'member'));
            if (in_array($requestedRole, ['admin', 'member'], true)) {
                $roleStmt = $this->db->prepare(
                    "UPDATE GroupMember SET role = ? WHERE group_id = ? AND user_id = ? AND status = 'active'"
                );
                $roleStmt->execute([$requestedRole, $groupId, (int)($event['target_id'] ?? 0)]);
            }
        }

        $closeStmt = $this->db->prepare(
            "UPDATE GroupGovernanceVoteEvent
             SET result = ?, closed_at = NOW()
             WHERE vote_event_id = ? AND group_id = ? AND result = 'in_process'"
        );
        $closeStmt->execute([$result, $eventId, $groupId]);
    }

    public function createGovernanceVoteEvent(int $groupId, string $targetType, int $targetId, string $voteType, string $reason, int $creatorId, array $meta = []): array {
        if (!$this->isGroupAdmin($groupId, $creatorId)) {
            return ['success' => false, 'message' => 'Only group admins can start governance votes.'];
        }

        $targetType = strtolower(trim($targetType));
        if (!in_array($targetType, ['group', 'user', 'channel'], true)) {
            $targetType = 'group';
        }

        $voteTypeMap = [
            'role' => 'member_role_change',
            'member_role_change' => 'member_role_change',
            'delete' => 'group_deletion',
            'group_deletion' => 'group_deletion',
            'visibility' => 'group_visibility_change',
            'group_visibility_change' => 'group_visibility_change',
        ];
        $voteType = $voteTypeMap[strtolower(trim($voteType))] ?? '';
        if ($voteType === '') {
            return ['success' => false, 'message' => 'Invalid vote type.'];
        }

        if ($targetType === 'group' || $targetId <= 0) {
            $targetId = $groupId;
        }

        $meta = is_array($meta) ? $meta : [];

        $insert = $this->db->prepare(
            "INSERT INTO GroupGovernanceVoteEvent
                (group_id, target_type, target_id, vote_type, reason, meta_json, created_by, created_at, expires_at, result)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), 'in_process')"
        );
        $insert->execute([
            $groupId,
            $targetType,
            $targetId,
            $voteType,
            trim($reason),
            !empty($meta) ? json_encode($meta) : null,
            $creatorId
        ]);

        return [
            'success' => true,
            'message' => 'Governance vote created.',
            'vote_event_id' => (int)$this->db->lastInsertId()
        ];
    }

    public function castGovernanceVote(int $groupId, int $eventId, int $voterId, string $voteChoice): array {
        if (!$this->isGroupAdmin($groupId, $voterId)) {
            return ['success' => false, 'message' => 'Only group admins can vote on governance events.'];
        }

        $voteChoice = ($voteChoice === 'in_favor') ? 'in_favor' : (($voteChoice === 'not_in_favor') ? 'not_in_favor' : '');
        if ($voteChoice === '') {
            return ['success' => false, 'message' => 'Invalid vote choice.'];
        }

        $this->closeExpiredGovernanceVotes($groupId);

        $eventStmt = $this->db->prepare(
            "SELECT vote_event_id, expires_at, result
             FROM GroupGovernanceVoteEvent
             WHERE vote_event_id = ? AND group_id = ?
             LIMIT 1"
        );
        $eventStmt->execute([$eventId, $groupId]);
        $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
        if (!$event) {
            return ['success' => false, 'message' => 'Governance vote event not found.'];
        }
        if (($event['result'] ?? '') !== 'in_process') {
            return ['success' => false, 'message' => 'Voting is closed for this event.'];
        }

        $voteStmt = $this->db->prepare(
            "INSERT INTO GroupGovernanceVote (vote_event_id, voter_user_id, vote_choice, voted_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE vote_choice = VALUES(vote_choice), voted_at = NOW()"
        );
        $voteStmt->execute([$eventId, $voterId, $voteChoice]);

        $adminCount = $this->getActiveAdminCount($groupId);
        $countStmt = $this->db->prepare("SELECT COUNT(*) AS c FROM GroupGovernanceVote WHERE vote_event_id = ?");
        $countStmt->execute([$eventId]);
        $voteCount = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        if ($voteCount >= $adminCount && $adminCount > 0) {
            $this->finalizeGovernanceVoteEvent($groupId, $eventId, false);
        }

        return ['success' => true, 'message' => 'Vote recorded.'];
    }

    public function getGovernanceVoteEvents(int $groupId, int $viewerId = 0): array {
        $this->closeExpiredGovernanceVotes($groupId);

        $sql = "SELECT
                    e.vote_event_id,
                    e.group_id,
                    e.target_type,
                    e.target_id,
                    e.vote_type,
                    e.reason,
                    e.meta_json,
                    e.created_by,
                    e.created_at,
                    e.expires_at,
                    e.result,
                    tu.username AS target_username,
                    tu.first_name AS target_first_name,
                    tu.last_name AS target_last_name,
                    su.username AS starter_username,
                    su.first_name AS starter_first_name,
                    su.last_name AS starter_last_name,
                    SUM(CASE WHEN v.vote_choice = 'in_favor' THEN 1 ELSE 0 END) AS in_favor_count,
                    SUM(CASE WHEN v.vote_choice = 'not_in_favor' THEN 1 ELSE 0 END) AS not_in_favor_count,
                    MAX(CASE WHEN v.voter_user_id = ? THEN v.vote_choice ELSE NULL END) AS viewer_vote
                FROM GroupGovernanceVoteEvent e
                LEFT JOIN GroupGovernanceVote v ON v.vote_event_id = e.vote_event_id
                LEFT JOIN Users tu ON e.target_type = 'user' AND tu.user_id = e.target_id
                LEFT JOIN Users su ON su.user_id = e.created_by
                WHERE e.group_id = ?
                GROUP BY e.vote_event_id
                ORDER BY FIELD(e.result, 'in_process', 'accepted', 'rejected', 'expired'), e.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$viewerId, $groupId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $voterStmt = $this->db->prepare(
            "SELECT u.first_name, u.last_name, u.username, u.profile_picture
             FROM GroupGovernanceVote v
             INNER JOIN Users u ON u.user_id = v.voter_user_id
             WHERE v.vote_event_id = ? AND v.vote_choice = ?
             ORDER BY v.voted_at ASC"
        );

        $events = [];
        foreach ($rows as $row) {
            $meta = json_decode((string)($row['meta_json'] ?? '{}'), true) ?: [];
            $voteType = (string)($row['vote_type'] ?? 'member_role_change');
            $inFavor = (int)($row['in_favor_count'] ?? 0);
            $notInFavor = (int)($row['not_in_favor_count'] ?? 0);
            $result = (string)($row['result'] ?? 'in_process');
            $startedByName = trim((string)($row['starter_first_name'] ?? '') . ' ' . (string)($row['starter_last_name'] ?? ''));
            if ($startedByName === '') {
                $startedByName = (string)($row['starter_username'] ?? 'Admin');
            }

            $typeKey = 'role';
            $typeLabel = 'Member Role Change';
            $tone = 'role';
            if ($voteType === 'group_deletion') {
                $typeKey = 'delete';
                $typeLabel = 'Group Deletion';
                $tone = 'danger';
            } elseif ($voteType === 'group_visibility_change') {
                $typeKey = 'visibility';
                $typeLabel = 'Group Visibility Change';
                $tone = 'danger';
            }

            $supporters = [];
            $opponents = [];
            $eventId = (int)($row['vote_event_id'] ?? 0);
            foreach (['in_favor', 'not_in_favor'] as $choice) {
                $voterStmt->execute([$eventId, $choice]);
                $list = [];
                foreach ($voterStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $v) {
                    $fullName = trim((string)($v['first_name'] ?? '') . ' ' . (string)($v['last_name'] ?? ''));
                    if ($fullName === '') {
                        $fullName = (string)($v['username'] ?? 'Member');
                    }
                    $list[] = [
                        'name' => $fullName,
                        'username' => (string)($v['username'] ?? 'member'),
                        'avatar' => $this->resolveUserProfilePicture((string)($v['profile_picture'] ?? '')),
                    ];
                }

                if ($choice === 'in_favor') {
                    $supporters = $list;
                } else {
                    $opponents = $list;
                }
            }

            $events[] = [
                'event_id' => $eventId,
                'type' => $typeLabel,
                'type_key' => $typeKey,
                'reason' => (string)($row['reason'] ?? ''),
                'in_favor' => $inFavor,
                'against' => $notInFavor,
                'status' => $result,
                'result_text' => $inFavor . ' in favor',
                'tone' => $tone,
                'supporters' => $supporters,
                'opponents' => $opponents,
                'target_user_id' => (int)($row['target_id'] ?? 0),
                'target_username' => (string)($row['target_username'] ?? ''),
                'target_first_name' => (string)($row['target_first_name'] ?? ''),
                'target_last_name' => (string)($row['target_last_name'] ?? ''),
                'requested_role' => (string)($meta['to_role'] ?? $meta['requested_role'] ?? 'member'),
                'from_role' => (string)($meta['from_role'] ?? ''),
                'to_role' => (string)($meta['to_role'] ?? $meta['requested_role'] ?? ''),
                'from_visibility' => (string)($meta['from_visibility'] ?? ''),
                'to_visibility' => (string)($meta['to_visibility'] ?? ''),
                'started_by_user_id' => (int)($row['created_by'] ?? 0),
                'started_by_username' => (string)($row['starter_username'] ?? ''),
                'started_by_name' => $startedByName,
                'target_type' => (string)($row['target_type'] ?? 'group'),
                'target_id' => (int)($row['target_id'] ?? 0),
                'viewer_voted' => !empty($row['viewer_vote']),
                'votes_needed' => max(1, (int)floor($this->getActiveAdminCount($groupId) / 2) + 1),
            ];
        }

        return $events;
    }

    private function resolveUserProfilePicture(string $rawPath): string {
        $rawPath = trim($rawPath);
        if ($rawPath !== '') {
            return $rawPath;
        }
        return 'uploads/user_dp/default_user_dp.jpg';
    }

    public function getDeleteApprovalStatus(int $groupId, int $viewerId = 0): array {
        $events = $this->getGovernanceVoteEvents($groupId, $viewerId);
        $deleteEvent = null;
        foreach ($events as $event) {
            if (($event['type_key'] ?? '') === 'delete') {
                $deleteEvent = $event;
                break;
            }
        }

        $adminCount = $this->getActiveAdminCount($groupId);
        $approvedCount = (int)($deleteEvent['in_favor'] ?? 0);
        return [
            'admin_count' => $adminCount,
            'approved_count' => $approvedCount,
            'viewer_approved' => !empty($deleteEvent['viewer_voted']),
            'all_approved' => (($deleteEvent['status'] ?? '') === 'accepted')
        ];
    }

    public function approveGroupDeletion(int $groupId, int $adminId): array {
        if (!$this->isGroupAdmin($groupId, $adminId)) {
            return ['success' => false, 'message' => 'Only group admins can approve deletion.'];
        }

        $eventId = 0;
        $existing = $this->db->prepare(
            "SELECT vote_event_id FROM GroupGovernanceVoteEvent
             WHERE group_id = ? AND vote_type = 'group_deletion' AND result = 'in_process'
             ORDER BY created_at DESC LIMIT 1"
        );
        $existing->execute([$groupId]);
        $eventId = (int)$existing->fetchColumn();

        if ($eventId <= 0) {
            $created = $this->createGovernanceVoteEvent($groupId, 'group', $groupId, 'group_deletion', 'Group deletion vote initiated by admin.', $adminId);
            if (empty($created['success'])) {
                return $created;
            }
            $eventId = (int)($created['vote_event_id'] ?? 0);
        }

        return $this->castGovernanceVote($groupId, $eventId, $adminId, 'in_favor');
    }

    public function createRoleChangeRequest(int $groupId, int $targetUserId, string $requestedRole, int $adminId): array {
        $requestedRole = strtolower(trim($requestedRole));
        if (!in_array($requestedRole, ['admin', 'member'], true)) {
            return ['success' => false, 'message' => 'Invalid target role.'];
        }

        $membership = $this->getMembership($groupId, $targetUserId);
        if (!$membership || ($membership['status'] ?? '') !== 'active') {
            return ['success' => false, 'message' => 'Target user is not an active member.'];
        }

        $currentRole = strtolower((string)($membership['role'] ?? 'member'));
        if ($currentRole === $requestedRole) {
            return ['success' => false, 'message' => 'User already has that role.'];
        }

        $eventId = 0;
        $existing = $this->db->prepare(
            "SELECT vote_event_id FROM GroupGovernanceVoteEvent
             WHERE group_id = ? AND vote_type = 'member_role_change' AND target_type = 'user' AND target_id = ? AND result = 'in_process'
             ORDER BY created_at DESC LIMIT 1"
        );
        $existing->execute([$groupId, $targetUserId]);
        $eventId = (int)$existing->fetchColumn();

        if ($eventId <= 0) {
            $fullName = trim((string)($membership['first_name'] ?? '') . ' ' . (string)($membership['last_name'] ?? ''));
            if ($fullName === '') {
                $fullName = (string)($membership['username'] ?? 'member');
            }
            $reason = 'Change the role for ' . $fullName . ' to ' . ucfirst($requestedRole) . '.';
            $created = $this->createGovernanceVoteEvent(
                $groupId,
                'user',
                $targetUserId,
                'member_role_change',
                $reason,
                $adminId,
                [
                    'from_role' => $currentRole,
                    'to_role' => $requestedRole,
                ]
            );
            if (empty($created['success'])) {
                return $created;
            }
            $eventId = (int)($created['vote_event_id'] ?? 0);
        }

        return $this->castGovernanceVote($groupId, $eventId, $adminId, 'in_favor');
    }

    public function approveRoleChangeRequest(int $groupId, int $requestId, int $adminId, bool $alreadyVoted = false): array {
        unset($alreadyVoted);
        return $this->castGovernanceVote($groupId, $requestId, $adminId, 'in_favor');
    }

    public function getRoleChangeRequests(int $groupId, int $viewerId = 0): array {
        $events = $this->getGovernanceVoteEvents($groupId, $viewerId);
        $rows = [];
        foreach ($events as $event) {
            if (($event['type_key'] ?? '') !== 'role') {
                continue;
            }

            $rows[] = [
                'request_id' => (int)($event['event_id'] ?? 0),
                'group_id' => $groupId,
                'target_user_id' => (int)($event['target_user_id'] ?? 0),
                'requested_role' => (string)($event['requested_role'] ?? 'member'),
                'current_role' => 'member',
                'proposed_by' => 0,
                'status' => (string)($event['status'] ?? 'in_process'),
                'created_at' => null,
                'resolved_at' => null,
                'target_username' => (string)($event['target_username'] ?? ''),
                'target_first_name' => (string)($event['target_first_name'] ?? ''),
                'target_last_name' => (string)($event['target_last_name'] ?? ''),
                'vote_count' => (int)($event['in_favor'] ?? 0),
                'viewer_voted' => !empty($event['viewer_voted']),
                'votes_needed' => (int)($event['votes_needed'] ?? 1),
            ];
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
      
    public function isPostApprovalRequired(int $groupId): bool {
        if ($groupId <= 0) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("SELECT require_post_approval FROM GroupSettings WHERE group_id = ? LIMIT 1");
            $stmt->execute([$groupId]);
            $value = $stmt->fetchColumn();
            return (int)$value === 1;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function queuePostCreationRequest(int $groupId, int $requesterId, string $postType, array $payload): bool {
        $postType = strtolower(trim($postType));
        $jsonPayload = json_encode($payload);
        if ($jsonPayload === false) {
            $jsonPayload = '{}';
        }

        if ($postType === 'poll') {
            $sql = "INSERT INTO GroupPostPollRequests
                (group_id, requester_id, post_id, payload_json, status, requested_at, reviewed_by, reviewed_at)
                VALUES (?, ?, NULL, ?, 'pending', NOW(), NULL, NULL)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$groupId, $requesterId, $jsonPayload]);
        }

        $sql = "INSERT INTO GroupPostRequests
            (group_id, requester_id, post_id, payload_json, status, requested_at, reviewed_by, reviewed_at)
            VALUES (?, ?, NULL, ?, 'pending', NOW(), NULL, NULL)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$groupId, $requesterId, $jsonPayload]);
    }

    public function queueBinCreationRequest(int $groupId, int $requesterId, array $payload): bool {
        $jsonPayload = json_encode($payload);
        if ($jsonPayload === false) {
            $jsonPayload = '{}';
        }

        $sql = "INSERT INTO GroupBinRequests
            (group_id, requester_id, bin_id, payload_json, status, requested_at, reviewed_by, reviewed_at)
            VALUES (?, ?, NULL, ?, 'pending', NOW(), NULL, NULL)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$groupId, $requesterId, $jsonPayload]);
    }

    public function queueBinMediaAddRequest(int $groupId, int $requesterId, int $binId, array $payload): bool {
        $jsonPayload = json_encode($payload);
        if ($jsonPayload === false) {
            $jsonPayload = '{}';
        }

        $sql = "INSERT INTO GroupBinMediaRequests
            (group_id, requester_id, bin_id, media_id, payload_json, status, requested_at, reviewed_by, reviewed_at)
            VALUES (?, ?, ?, NULL, ?, 'pending', NOW(), NULL, NULL)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$groupId, $requesterId, $binId, $jsonPayload]);
    }

    public function queueChannelCreationRequest(int $groupId, int $requesterId, array $payload): bool {
        $jsonPayload = json_encode($payload);
        if ($jsonPayload === false) {
            $jsonPayload = '{}';
        }

        $sql = "INSERT INTO GroupChannelRequests
            (group_id, requester_id, channel_id, payload_json, status, requested_at, reviewed_by, reviewed_at)
            VALUES (?, ?, NULL, ?, 'pending', NOW(), NULL, NULL)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$groupId, $requesterId, $jsonPayload]);
    }

    public function getPendingPostCreationRequests(int $groupId): array {
        $sql = "SELECT r.request_id, r.group_id, r.requester_id, r.payload_json, r.requested_at,
                       u.username, u.first_name, u.last_name,
                       'post' AS request_kind,
                       'post' AS post_type
                FROM GroupPostRequests r
                INNER JOIN Users u ON u.user_id = r.requester_id
            WHERE r.group_id = ? AND r.status = 'pending'
                UNION ALL
            SELECT r.request_id, r.group_id, r.requester_id, r.payload_json, r.requested_at,
                       u.username, u.first_name, u.last_name,
                       'post_poll' AS request_kind,
                       'poll' AS post_type
                FROM GroupPostPollRequests r
                INNER JOIN Users u ON u.user_id = r.requester_id
            WHERE r.group_id = ? AND r.status = 'pending'
                ORDER BY requested_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId, $groupId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $payload = $this->decodeRequestPayload((string)($row['payload_json'] ?? ''));
            if (!empty($payload['post_type'])) {
                $row['post_type'] = (string)$payload['post_type'];
            }
            $row['reason'] = 'Creation request pending approval';
            $row['member'] = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? ''));
            if ($row['member'] === '') {
                $row['member'] = (string)($row['username'] ?? 'Unknown');
            }
        }
        unset($row);

        return $rows;
    }

    public function getPendingBinCreationRequests(int $groupId): array {
     $sql = "SELECT r.request_id, r.group_id, r.requester_id, r.payload_json, r.requested_at,
                       u.username, u.first_name, u.last_name,
                       'bin' AS request_kind,
                       r.bin_id
                FROM GroupBinRequests r
                INNER JOIN Users u ON u.user_id = r.requester_id
          WHERE r.group_id = ? AND r.status = 'pending'
                UNION ALL
          SELECT r.request_id, r.group_id, r.requester_id, r.payload_json, r.requested_at,
                       u.username, u.first_name, u.last_name,
                       'bin_media' AS request_kind,
                       r.bin_id
                FROM GroupBinMediaRequests r
                INNER JOIN Users u ON u.user_id = r.requester_id
          WHERE r.group_id = ? AND r.status = 'pending'
                ORDER BY requested_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId, $groupId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $payload = $this->decodeRequestPayload((string)($row['payload_json'] ?? ''));
            $row['member'] = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? ''));
            if ($row['member'] === '') {
                $row['member'] = (string)($row['username'] ?? 'Unknown');
            }

            if (($row['request_kind'] ?? '') === 'bin') {
                $row['bin_name'] = (string)($payload['name'] ?? ('Bin #' . (int)($row['bin_id'] ?? 0)));
                $row['request'] = 'Create bin request pending approval';
            } else {
                $row['bin_name'] = (string)($payload['file_name'] ?? 'Pending file');
                $row['request'] = 'Add file to bin pending approval';
            }
        }
        unset($row);

        return $rows;
    }

    public function getPendingChannelCreationRequests(int $groupId): array {
        $sql = "SELECT r.request_id, r.group_id, r.requester_id, r.payload_json, r.requested_at,
                       u.username, u.first_name, u.last_name,
                       'channel' AS request_kind
                FROM GroupChannelRequests r
                INNER JOIN Users u ON u.user_id = r.requester_id
                WHERE r.group_id = ? AND r.status = 'pending'
                ORDER BY r.requested_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $payload = $this->decodeRequestPayload((string)($row['payload_json'] ?? ''));
            $row['member'] = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? ''));
            if ($row['member'] === '') {
                $row['member'] = (string)($row['username'] ?? 'Unknown');
            }

            $name = trim((string)($payload['name'] ?? ''));
            $description = trim((string)($payload['description'] ?? ''));
            $row['channel'] = $name !== '' ? $name : ('Channel request #' . (int)($row['request_id'] ?? 0));
            $row['request_type'] = 'Create channel';
            $row['reason'] = $description !== '' ? $description : 'Creation request pending approval';
        }
        unset($row);

        return $rows;
    }

    public function getPendingPostRequestById(int $groupId, int $requestId, string $requestKind): ?array {
        $requestKind = strtolower(trim($requestKind));
        $table = ($requestKind === 'post_poll') ? 'GroupPostPollRequests' : 'GroupPostRequests';

        $sql = "SELECT * FROM {$table} WHERE request_id = ? AND group_id = ? AND status = 'pending' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$requestId, $groupId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row && $requestKind === 'post') {
            $sql = "SELECT * FROM GroupPostPollRequests WHERE request_id = ? AND group_id = ? AND status = 'pending' LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$requestId, $groupId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['_request_table'] = 'GroupPostPollRequests';
            }
        }

        if (!$row) {
            return null;
        }

        if (!isset($row['_request_table'])) {
            $row['_request_table'] = $table;
        }
        $row['_payload'] = $this->decodeRequestPayload((string)($row['payload_json'] ?? ''));
        return $row;
    }

    public function getPendingBinRequestById(int $groupId, int $requestId, string $requestKind): ?array {
        $requestKind = strtolower(trim($requestKind));
        $table = ($requestKind === 'bin_media') ? 'GroupBinMediaRequests' : 'GroupBinRequests';

        $sql = "SELECT * FROM {$table} WHERE request_id = ? AND group_id = ? AND status = 'pending' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$requestId, $groupId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row && $requestKind === 'bin') {
            $sql = "SELECT * FROM GroupBinMediaRequests WHERE request_id = ? AND group_id = ? AND status = 'pending' LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$requestId, $groupId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $row['_request_table'] = 'GroupBinMediaRequests';
            }
        }

        if (!$row) {
            return null;
        }

        if (!isset($row['_request_table'])) {
            $row['_request_table'] = $table;
        }
        $row['_payload'] = $this->decodeRequestPayload((string)($row['payload_json'] ?? ''));
        return $row;
    }

    public function getPendingChannelRequestById(int $groupId, int $requestId): ?array {
        $sql = "SELECT * FROM GroupChannelRequests WHERE request_id = ? AND group_id = ? AND status = 'pending' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$requestId, $groupId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['_request_table'] = 'GroupChannelRequests';
        $row['_payload'] = $this->decodeRequestPayload((string)($row['payload_json'] ?? ''));
        return $row;
    }

    public function markRequestStatus(string $table, int $requestId, int $adminId, string $status): bool {
        $status = strtolower(trim($status));
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }

        $allowedTables = [
            'GroupPostRequests',
            'GroupPostPollRequests',
            'GroupBinRequests',
            'GroupBinMediaRequests',
            'GroupChannelRequests'
        ];
        if (!in_array($table, $allowedTables, true)) {
            return false;
        }

        $sql = "UPDATE {$table}
                SET status = ?, reviewed_by = ?, reviewed_at = NOW()
                WHERE request_id = ? AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $adminId, $requestId]);
        return $stmt->rowCount() > 0;
    }

    private function decodeRequestPayload(string $raw): array {
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
?>