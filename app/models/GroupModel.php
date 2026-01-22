<?php
require_once __DIR__ . '/../core/Database.php';

class GroupModel {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
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
    public function createGroup($data) {
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
        
        // Add creator as admin member
        if ($groupId) {
            $this->addMember($groupId, $data['created_by'], 'admin');
        }
        
        return $groupId;
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
                return $stmt->execute([$role, $groupId, $userId]);
            }
        }
        
        // Add new member
        $sql = "INSERT INTO GroupMember (group_id, user_id, role, status, joined_at) VALUES (?, ?, ?, 'active', NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId, $userId, $role]);
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
        return $stmt->execute([$groupId, $userId]);
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
     * Get popular groups
     */
    public function getPopularGroups($limit = 5) {
        $sql = "SELECT g.*, 
                   COUNT(gm.user_id) as member_count
                FROM GroupsTable g
                LEFT JOIN GroupMember gm ON g.group_id = gm.group_id AND gm.status = 'active'
                WHERE COALESCE(g.is_active, 1) = 1
                GROUP BY g.group_id
                ORDER BY member_count DESC, g.created_at DESC
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

    public function disableGroup(int $groupId): bool {
        if ($groupId <= 0) {
            return false;
        }

        $sql = "UPDATE GroupsTable SET is_active = 0, updated_at = NOW() WHERE group_id = ? AND COALESCE(is_active, 1) = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId]);
        return $stmt->rowCount() > 0;
    }

    public function getTrendingGroups($limit = 5): array {
        $sql = "SELECT 
                    g.group_id,
                    g.name,
                    g.privacy_status,
                    COALESCE(members.member_count, 0) AS member_count,
                    COALESCE(posts_recent.posts_last_7, 0) AS posts_last_7,
                    COALESCE(comments_recent.comments_last_7, 0) AS comments_last_7,
                    COALESCE(posts_total.total_posts, 0) AS total_posts,
                    COALESCE(comments_total.total_comments, 0) AS total_comments,
                    (COALESCE(posts_recent.posts_last_7, 0) * 2 + COALESCE(comments_recent.comments_last_7, 0) + COALESCE(members.member_count, 0) * 0.1) AS engagement_score
                FROM GroupsTable g
                LEFT JOIN (
                    SELECT group_id, COUNT(*) AS member_count
                    FROM GroupMember
                    WHERE status = 'active'
                    GROUP BY group_id
                ) members ON members.group_id = g.group_id
                LEFT JOIN (
                    SELECT group_id, COUNT(*) AS posts_last_7
                    FROM Post
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    AND group_id IS NOT NULL
                    GROUP BY group_id
                ) posts_recent ON posts_recent.group_id = g.group_id
                LEFT JOIN (
                    SELECT group_id, COUNT(*) AS total_posts
                    FROM Post
                    WHERE group_id IS NOT NULL
                    GROUP BY group_id
                ) posts_total ON posts_total.group_id = g.group_id
                LEFT JOIN (
                    SELECT p.group_id, COUNT(*) AS comments_last_7
                    FROM Comment c
                    JOIN Post p ON p.post_id = c.post_id
                    WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    AND p.group_id IS NOT NULL
                    GROUP BY p.group_id
                ) comments_recent ON comments_recent.group_id = g.group_id
                LEFT JOIN (
                    SELECT p.group_id, COUNT(*) AS total_comments
                    FROM Comment c
                    JOIN Post p ON p.post_id = c.post_id
                    WHERE p.group_id IS NOT NULL
                    GROUP BY p.group_id
                ) comments_total ON comments_total.group_id = g.group_id
                WHERE COALESCE(g.is_active, 1) = 1
                ORDER BY engagement_score DESC, member_count DESC, g.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
}
?>