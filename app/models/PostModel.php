<?php
require_once __DIR__ . '/../core/Database.php';  // Fixed path
require_once __DIR__ . '/../helpers/MediaHelper.php';

class PostModel {
    private $db;
    private $hasGroupPostColumns = false;
    private $hasEventTimeColumn = false;
    private $hasPostBookmarkTable = false;
    private $hasPollVoteTable = false;

    public function __construct() {
        $this->db = new Database();
        $this->hasGroupPostColumns = $this->columnExists('Post', 'group_post_type');
        $this->hasEventTimeColumn = $this->columnExists('Post', 'event_time');
        $this->hasPostBookmarkTable = $this->tableExists('PostBookmark');
        $this->hasPollVoteTable = $this->tableExists('GroupPostPollVote');
        if (!$this->hasPostBookmarkTable) {
            $this->ensurePostBookmarkTable();
        }
    }

    private function columnExists(string $table, string $column): bool {
        try {
            $stmt = $this->db->getConnection()->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $stmt->execute([$column]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    private function tableExists(string $table): bool {
        try {
            $stmt = $this->db->getConnection()->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            $exists = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
            if (!$exists) {
                return false;
            }

            if ($table === 'GroupPostPollVote') {
                return $this->columnExists('GroupPostPollVote', 'option_index');
            }

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function ensurePostBookmarkTable(): void {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS PostBookmark (
                        bookmark_id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        post_id INT NOT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY uniq_user_post (user_id, post_id),
                        INDEX idx_bookmark_user_created (user_id, created_at),
                        INDEX idx_bookmark_post (post_id),
                        CONSTRAINT fk_postbookmark_user FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
                        CONSTRAINT fk_postbookmark_post FOREIGN KEY (post_id) REFERENCES Post(post_id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

            $this->db->getConnection()->exec($sql);
            $this->hasPostBookmarkTable = $this->tableExists('PostBookmark');
        } catch (PDOException $e) {
            error_log('ensurePostBookmarkTable error: ' . $e->getMessage());
            $this->hasPostBookmarkTable = $this->tableExists('PostBookmark');
        }
    }

    private function selectBookmarkColumn(int $userId, string $postAlias = 'p'): string {
        if ($userId <= 0 || !$this->hasPostBookmarkTable) {
            return ", 0 AS is_bookmarked";
        }

        return ", EXISTS(
                    SELECT 1
                    FROM PostBookmark pb
                    WHERE pb.post_id = {$postAlias}.post_id
                      AND pb.user_id = {$userId}
                ) AS is_bookmarked";
    }

    private function selectGroupPostColumns(): string {
        if ($this->hasGroupPostColumns) {
            return "                p.group_post_type,\n                p.metadata,";
        }

        return "                'discussion' AS group_post_type,\n                NULL AS metadata,";
    }

    private function selectPollVoteColumn(int $userId, string $postAlias = 'p'): string {
        if ($userId <= 0 || !$this->hasPollVoteTable || !$this->hasGroupPostColumns) {
            return ", NULL AS user_poll_vote";
        }

        return ", (SELECT option_index
                    FROM GroupPostPollVote
                    WHERE post_id = {$postAlias}.post_id
                      AND user_id = {$userId}
                    LIMIT 1) AS user_poll_vote";
    }

    private function getPollVoteCounts(int $postId, int $optionCount): array {
        if (!$this->hasPollVoteTable || $optionCount <= 0) {
            return array_fill(0, max($optionCount, 0), 0);
        }

        $counts = array_fill(0, $optionCount, 0);
        try {
            $stmt = $this->db->getConnection()->prepare(
                "SELECT option_index, COUNT(*) AS total
                 FROM GroupPostPollVote
                 WHERE post_id = ?
                 GROUP BY option_index"
            );
            $stmt->execute([$postId]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $idx = (int)$row['option_index'];
                if ($idx >= 0 && $idx < $optionCount) {
                    $counts[$idx] = (int)$row['total'];
                }
            }
        } catch (PDOException $e) {
            error_log('PostModel getPollVoteCounts error: ' . $e->getMessage());
        }

        return $counts;
    }

    private function getUserPollVoteFromTable(int $postId, int $userId): ?int {
        if (!$this->hasPollVoteTable || $postId <= 0 || $userId <= 0) {
            return null;
        }

        try {
            $stmt = $this->db->getConnection()->prepare(
                "SELECT option_index
                 FROM GroupPostPollVote
                 WHERE post_id = ? AND user_id = ?
                 LIMIT 1"
            );
            $stmt->execute([$postId, $userId]);
            $value = $stmt->fetchColumn();
            if ($value === false || $value === null || $value === '') {
                return null;
            }
            return (int)$value;
        } catch (PDOException $e) {
            error_log('PostModel getUserPollVoteFromTable error: ' . $e->getMessage());
            return null;
        }
    }

    private function extractUserPollVoteFromMetadata(array $metadata, int $viewerId, int $optionCount): ?int {
        if ($viewerId <= 0 || $optionCount <= 0) {
            return null;
        }

        $userVotes = $metadata['user_votes'] ?? [];
        if (!is_array($userVotes)) {
            return null;
        }

        $value = null;
        if (isset($userVotes[$viewerId])) {
            $value = $userVotes[$viewerId];
        } elseif (isset($userVotes[(string)$viewerId])) {
            $value = $userVotes[(string)$viewerId];
        }

        if ($value === null || $value === '') {
            return null;
        }

        $voteIndex = (int)$value;
        if ($voteIndex < 0 || $voteIndex >= $optionCount) {
            return null;
        }

        return $voteIndex;
    }

    private function normalizePollPost(array &$post, int $viewerId): void {
        if (($post['group_post_type'] ?? '') !== 'poll') {
            if (!array_key_exists('user_poll_vote', $post)) {
                $post['user_poll_vote'] = null;
            }
            return;
        }

        $metadata = $post['metadata'] ?? [];
        if (!is_array($metadata)) {
            $metadata = [];
        }

        $options = $metadata['options'] ?? [];
        if (!is_array($options)) {
            $options = [];
        }
        $optionCount = count($options);

        if ($optionCount <= 0) {
            $metadata['votes'] = [];
            $post['metadata'] = $metadata;
            $post['user_poll_vote'] = null;
            return;
        }

        if ($this->hasPollVoteTable) {
            $metadata['votes'] = $this->getPollVoteCounts((int)$post['post_id'], $optionCount);
        } else {
            $votes = $metadata['votes'] ?? [];
            $normalizedVotes = array_fill(0, $optionCount, 0);

            if (is_array($votes)) {
                foreach ($votes as $idx => $count) {
                    $slot = (int)$idx;
                    if ($slot >= 0 && $slot < $optionCount) {
                        $normalizedVotes[$slot] = max(0, (int)$count);
                    }
                }
            }

            $userVotes = $metadata['user_votes'] ?? [];
            if (is_array($userVotes) && !empty($userVotes)) {
                $normalizedVotes = array_fill(0, $optionCount, 0);
                foreach ($userVotes as $voteIdx) {
                    $idx = (int)$voteIdx;
                    if ($idx >= 0 && $idx < $optionCount) {
                        $normalizedVotes[$idx]++;
                    }
                }
            }

            $metadata['votes'] = $normalizedVotes;
        }

        $post['metadata'] = $metadata;

        if ($viewerId <= 0) {
            $post['user_poll_vote'] = null;
            return;
        }

        $userVote = null;
        if (isset($post['user_poll_vote']) && $post['user_poll_vote'] !== null && $post['user_poll_vote'] !== '') {
            $userVote = (int)$post['user_poll_vote'];
        } elseif ($this->hasPollVoteTable) {
            $userVote = $this->getUserPollVoteFromTable((int)$post['post_id'], $viewerId);
        }

        if ($userVote === null) {
            $userVote = $this->extractUserPollVoteFromMetadata($metadata, $viewerId, $optionCount);
        }

        if ($userVote !== null && ($userVote < 0 || $userVote >= $optionCount)) {
            $userVote = null;
        }

        $post['user_poll_vote'] = $userVote;
    }
    
    public function getConnection() {
        return $this->db->getConnection();
    }
    
    // Get feed posts: friends + own posts + eligible group posts WITH PRIVACY FILTERING
    public function getFeedPosts($userId = null, $excludeEvents = false): array {
        if (!$userId) return [];

        $userId = (int)$userId;

        $eventFilter = $excludeEvents ? "AND (p.post_type != 'event' OR p.post_type IS NULL)" : "";

        $personalWhere = "(
            (p.is_group_post = 0 OR p.is_group_post IS NULL)
            {$eventFilter}
            AND (
                p.author_id IN (
                    SELECT friend_id FROM Friends WHERE user_id = ? AND status = 'accepted'
                    UNION
                    SELECT user_id FROM Friends WHERE friend_id = ? AND status = 'accepted'
                )
                OR p.author_id = ?
            )
        )";

        $groupEventFilter = "";
        if ($excludeEvents) {
            $groupEventFilter = $this->hasGroupPostColumns 
                ? "AND (p.group_post_type NOT IN ('event', 'assignment') OR p.group_post_type IS NULL)" 
                : "AND (p.post_type NOT IN ('event', 'assignment') OR p.post_type IS NULL)";
        }

        $groupWhere = "(
            p.is_group_post = 1
            AND p.group_id IS NOT NULL
            {$groupEventFilter}
            AND COALESCE(g.is_active, 1) = 1
            AND (
                LOWER(TRIM(COALESCE(g.privacy_status, 'public'))) = 'public'
                OR EXISTS (
                    SELECT 1
                    FROM GroupMember gm
                    WHERE gm.group_id = p.group_id
                      AND gm.user_id = ?
                      AND gm.status = 'active'
                )
            )
        )";

        $personalPosts = $this->runFeedQuery($userId, $personalWhere, [$userId, $userId, $userId]);
        $groupPosts = $this->runFeedQuery($userId, $groupWhere, [$userId]);

        $mergedById = [];
        foreach (array_merge($personalPosts, $groupPosts) as $post) {
            if (!isset($mergedById[$post['post_id']])) {
                $mergedById[$post['post_id']] = $post;
            }
        }

        $posts = array_values($mergedById);
        
        // FILTER POSTS BASED ON PRIVACY SETTINGS
        $filteredPosts = [];
        foreach ($posts as $post) {
            $authorId = (int)$post['user_id'];
            
            // Always show user's own posts
            if ($authorId === $userId) {
                $filteredPosts[] = $post;
                continue;
            }
            
            // Get author's privacy settings
            $authorSettings = $this->getUserPrivacySettings($authorId);
            
            // Check if post should be visible
            if ($this->shouldShowPost($authorSettings, $authorId, $userId)) {
                $filteredPosts[] = $post;
            }
        }
        
        usort($filteredPosts, function ($a, $b) {
            $timeA = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
            $timeB = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
            return $timeB <=> $timeA;
        });
        
        // Adjust paths and process likers
        foreach ($filteredPosts as &$post) {
            if (!empty($post['image_url'])) {
                $post['image_url'] = MediaHelper::resolveMediaPath($post['image_url'], 'images/default_post.png');
            }
            if (!empty($post['profile_picture'])) {
                $post['profile_picture'] = MediaHelper::resolveMediaPath($post['profile_picture'], 'uploads/user_dp/default.png');
            }
            $post['metadata'] = !empty($post['metadata']) ? json_decode($post['metadata'], true) : [];
            if (!is_array($post['metadata'])) {
                $post['metadata'] = [];
            }
            $post['user_vote'] = $post['user_vote'] ?? null;
            $this->normalizePollPost($post, $userId);
        }
        
        return $filteredPosts;
    }

    // Helper methods for privacy filtering
    private function getUserPrivacySettings($userId) {
        $sql = "SELECT post_visibility FROM UserSettings WHERE user_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $settings ?: ['post_visibility' => 'everyone'];
    }

    private function shouldShowPost($authorSettings, $authorId, $viewerId) {
        $visibility = $authorSettings['post_visibility'] ?? 'everyone';
        
        switch ($visibility) {
            case 'only_me':
                return false; // ❌ Never show to others
                
            case 'friends_only':
                return $this->areFriends($authorId, $viewerId);
                
            case 'everyone':
            default:
                return true;
        }
    }

    private function areFriends($userId1, $userId2) {
        $sql = "SELECT * FROM Friends 
                WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) 
                AND status = 'accepted' 
                LIMIT 1";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
        return $stmt->fetch() !== false;
    }

    private function buildFeedBaseSelect(int $userId): string {
        $userVoteSql = ", (SELECT vote_type FROM Vote WHERE post_id = p.post_id AND user_id = {$userId} LIMIT 1) AS user_vote";
        $pollVoteSql = $this->selectPollVoteColumn($userId, 'p');
        $bookmarkSql = $this->selectBookmarkColumn($userId, 'p');
        $groupColumns = $this->selectGroupPostColumns();
        
        $goingCountSql = ", (SELECT COUNT(*) FROM CalendarReminders cr WHERE cr.post_id = p.post_id) AS going_count";
        $isGoingSql = ", (SELECT 1 FROM CalendarReminders cr WHERE cr.post_id = p.post_id AND cr.user_id = {$userId} LIMIT 1) AS is_going";
        $interestedCountSql = ", 0 AS interested_count";

        return "
            SELECT
                p.post_id,
                p.content,
                p.post_type,
                p.visibility,
                p.created_at,
                p.upvote_count,
                p.downvote_count,
                p.comment_count,
            {$groupColumns}
                p.event_title,
                p.event_date,
                p.event_time,
                p.event_location,
                p.group_id,
                g.name AS group_name,
                u.user_id,
                u.username,
                u.first_name,
                u.last_name,
                u.profile_picture,
                pm.file_url AS image_url
                {$userVoteSql}
                {$pollVoteSql}
                {$bookmarkSql}
                {$goingCountSql}
                {$isGoingSql}
                {$interestedCountSql}
            FROM Post p
            JOIN Users u ON u.user_id = p.author_id
            LEFT JOIN (
                SELECT pm1.post_id, pm1.file_url
                FROM PostMedia pm1
                INNER JOIN (
                    SELECT post_id, MIN(postmedia_id) AS first_media_id
                    FROM PostMedia
                    WHERE file_type = 'image'
                    GROUP BY post_id
                ) x ON x.first_media_id = pm1.postmedia_id
            ) pm ON pm.post_id = p.post_id
            LEFT JOIN GroupsTable g ON g.group_id = p.group_id
        ";
    }

    private function runFeedQuery(int $userId, string $whereClause, array $params): array {
        $sql = $this->buildFeedBaseSelect($userId) . " WHERE {$whereClause} ORDER BY p.created_at DESC";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createPost($data) {
        try {
            $conn = $this->getConnection();

            $fields = ['content', 'post_type', 'visibility', 'event_title', 'event_date', 'event_location', 'is_group_post', 'group_id', 'author_id'];
            $placeholders = ['?', '?', '?', '?', '?', '?', '?', '?', '?'];
            $params = [
                $data['content'],
                $data['post_type'],
                $data['visibility'],
                $data['event_title'] ?? null,
                $data['event_date'] ?? null,
                $data['event_location'] ?? null,
                (int)($data['is_group_post'] ?? 0),
                $data['group_id'] ?? null,
                $data['author_id']
            ];

            if ($this->hasEventTimeColumn) {
                $fields[] = 'event_time';
                $placeholders[] = '?';
                $params[] = $data['event_time'] ?? null;
            }

            $sql = "INSERT INTO Post (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            $postId = $conn->lastInsertId();

            // If there's an image, insert into PostMedia table
            if (!empty($data['image_path'])) {
                $stmt = $conn->prepare("
                    INSERT INTO PostMedia (post_id, uploader_id, file_name, file_type, file_url, file_size)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $postId,
                    $data['author_id'],
                    $data['image_name'],
                    'image',
                    $data['image_path'],
                    $data['image_size']
                ]);
            }

            return ['success' => true, 'post_id' => $postId];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function deletePost(int $postId, int $authorId): bool {
        // Check ownership
        $stmt = $this->getConnection()->prepare("SELECT author_id FROM Post WHERE post_id = ?");
        $stmt->execute([$postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)$row['author_id'] !== $authorId) return false;

        // Delete PostMedia first (foreign key)
        $stmt = $this->getConnection()->prepare("DELETE FROM PostMedia WHERE post_id = ?");
        $stmt->execute([$postId]);

        // Then delete Post
        $stmt = $this->getConnection()->prepare("DELETE FROM Post WHERE post_id = ?");
        return $stmt->execute([$postId]);
    }

    public function removePostByAdmin(int $postId): bool {
        if ($postId <= 0) {
            return false;
        }

        $conn = $this->getConnection();

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("DELETE FROM PostMedia WHERE post_id = ?");
            $stmt->execute([$postId]);

            $stmt = $conn->prepare("DELETE FROM Post WHERE post_id = ?");
            $stmt->execute([$postId]);

            $conn->commit();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log('removePostByAdmin error: ' . $e->getMessage());
            return false;
        }
    }

    public function updatePostContent(int $postId, int $authorId, string $content): bool {
        // Check ownership
        $stmt = $this->getConnection()->prepare("SELECT author_id FROM Post WHERE post_id = ?");
        $stmt->execute([$postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)$row['author_id'] !== $authorId) return false;

        // Update content
        $stmt = $this->getConnection()->prepare("UPDATE Post SET content = ?, is_edited = TRUE, edited_at = NOW() WHERE post_id = ?");
        return $stmt->execute([$content, $postId]);
    }

    /**
     * Get trending posts (high engagement - sorted by upvotes and comments)
     */
    public function getTrendingPosts(int $limit = 10, int $userId = 0): array {
        $userVoteSql = $userId ? ", (SELECT vote_type FROM Vote WHERE post_id = p.post_id AND user_id = " . (int)$userId . " LIMIT 1) AS user_vote" : ", NULL AS user_vote";
        $pollVoteSql = $this->selectPollVoteColumn((int)$userId, 'p');
        $groupColumns = $this->selectGroupPostColumns();
        $bookmarkSql = $this->selectBookmarkColumn((int)$userId, 'p');

        $sql = "
            SELECT
                p.post_id,
                p.content,
                p.post_type,
                p.visibility,
                p.created_at,
                COALESCE(vt_total.upvotes, 0) AS upvote_count,
                COALESCE(vt_total.downvotes, 0) AS downvote_count,
                COALESCE(ct_total.comment_count, 0) AS comment_count,
            {$groupColumns}
                p.event_title,
                p.event_date,
                p.event_time,
                p.event_location,
                p.group_id,
                g.name AS group_name,
                u.user_id,
                u.username,
                u.first_name,
                u.last_name,
                u.profile_picture,
                pm.file_url AS image_url,
                (COALESCE(vt_recent.upvotes, 0) * 2 + COALESCE(ct_recent.comment_count, 0)) AS engagement_score
                {$userVoteSql}
                {$pollVoteSql}
                {$bookmarkSql}
            FROM Post p
            JOIN Users u ON u.user_id = p.author_id
            LEFT JOIN GroupsTable g ON g.group_id = p.group_id
            LEFT JOIN (
                SELECT 
                    post_id,
                    SUM(CASE WHEN vote_type = 'upvote' THEN 1 ELSE 0 END) AS upvotes,
                    SUM(CASE WHEN vote_type = 'downvote' THEN 1 ELSE 0 END) AS downvotes
                FROM Vote
                GROUP BY post_id
            ) vt_total ON vt_total.post_id = p.post_id
            LEFT JOIN (
                SELECT 
                    post_id,
                    SUM(CASE WHEN vote_type = 'upvote' THEN 1 ELSE 0 END) AS upvotes,
                    SUM(CASE WHEN vote_type = 'downvote' THEN 1 ELSE 0 END) AS downvotes
                FROM Vote
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY post_id
            ) vt_recent ON vt_recent.post_id = p.post_id
            LEFT JOIN (
                SELECT post_id, COUNT(*) AS comment_count
                FROM Comment
                GROUP BY post_id
            ) ct_total ON ct_total.post_id = p.post_id
            LEFT JOIN (
                SELECT post_id, COUNT(*) AS comment_count
                FROM Comment
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY post_id
            ) ct_recent ON ct_recent.post_id = p.post_id
            LEFT JOIN (
                SELECT pm1.post_id, pm1.file_url
                FROM PostMedia pm1
                INNER JOIN (
                    SELECT post_id, MIN(postmedia_id) AS first_media_id
                    FROM PostMedia
                    WHERE file_type = 'image'
                    GROUP BY post_id
                ) x ON x.first_media_id = pm1.postmedia_id
            ) pm ON pm.post_id = p.post_id
            WHERE (p.group_id IS NULL OR COALESCE(g.is_active, 1) = 1)
            ORDER BY
                CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 0 ELSE 1 END,
                engagement_score DESC,
                p.created_at DESC
            LIMIT :result_limit
        ";

        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':result_limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($posts as &$post) {
                if (!empty($post['image_url'])) {
                    $post['image_url'] = MediaHelper::resolveMediaPath($post['image_url'], 'images/default_post.png');
                }
                if (!empty($post['profile_picture'])) {
                    $post['profile_picture'] = MediaHelper::resolveMediaPath($post['profile_picture'], 'images/avatars/defaultProfilePic.png');
                }
                $post['metadata'] = !empty($post['metadata']) ? json_decode($post['metadata'], true) : [];
                if (!is_array($post['metadata'])) {
                    $post['metadata'] = [];
                }
                $post['user_vote'] = $post['user_vote'] ?? null;
                $this->normalizePollPost($post, (int)$userId);
            }

            return $posts;
        } catch (PDOException $e) {
            error_log('getTrendingPosts error: ' . $e->getMessage());
            return [];
        }
    }

    public function setPostBookmark(int $userId, int $postId, string $action = 'toggle'): array {
        if ($userId <= 0 || $postId <= 0) {
            return ['success' => false, 'bookmarked' => false, 'message' => 'Invalid bookmark request'];
        }

        if (!$this->hasPostBookmarkTable) {
            $this->ensurePostBookmarkTable();
        }

        if (!$this->hasPostBookmarkTable) {
            return ['success' => false, 'bookmarked' => false, 'message' => 'Bookmarks are not available right now'];
        }

        try {
            $existsStmt = $this->getConnection()->prepare("SELECT 1 FROM Post WHERE post_id = ? LIMIT 1");
            $existsStmt->execute([$postId]);
            if (!$existsStmt->fetchColumn()) {
                return ['success' => false, 'bookmarked' => false, 'message' => 'Post not found'];
            }

            $normalizedAction = in_array($action, ['add', 'remove', 'toggle'], true) ? $action : 'toggle';
            $isCurrentlyBookmarked = $this->isPostBookmarked($userId, $postId);

            if ($normalizedAction === 'toggle') {
                $normalizedAction = $isCurrentlyBookmarked ? 'remove' : 'add';
            }

            if ($normalizedAction === 'add') {
                $stmt = $this->getConnection()->prepare("INSERT IGNORE INTO PostBookmark (user_id, post_id, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$userId, $postId]);
                return ['success' => true, 'bookmarked' => true, 'message' => 'Post saved'];
            }

            $stmt = $this->getConnection()->prepare("DELETE FROM PostBookmark WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$userId, $postId]);
            return ['success' => true, 'bookmarked' => false, 'message' => 'Bookmark removed'];
        } catch (PDOException $e) {
            error_log('setPostBookmark error: ' . $e->getMessage());
            return ['success' => false, 'bookmarked' => false, 'message' => 'Unable to update bookmark'];
        }
    }

    public function isPostBookmarked(int $userId, int $postId): bool {
        if ($userId <= 0 || $postId <= 0 || !$this->hasPostBookmarkTable) {
            return false;
        }

        try {
            $stmt = $this->getConnection()->prepare("SELECT 1 FROM PostBookmark WHERE user_id = ? AND post_id = ? LIMIT 1");
            $stmt->execute([$userId, $postId]);
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function appendBookmarkFlags(array &$posts, int $userId): void {
        if ($userId <= 0) {
            foreach ($posts as &$post) {
                $post['is_bookmarked'] = false;
            }
            unset($post);
            return;
        }

        if (!$this->hasPostBookmarkTable) {
            $this->ensurePostBookmarkTable();
        }

        $postIds = array_values(array_unique(array_filter(array_map(static function ($post) {
            return isset($post['post_id']) ? (int)$post['post_id'] : 0;
        }, $posts))));

        if (empty($postIds) || !$this->hasPostBookmarkTable) {
            foreach ($posts as &$post) {
                $post['is_bookmarked'] = false;
            }
            unset($post);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $params = array_merge([$userId], $postIds);
        $bookmarkedMap = [];

        try {
            $stmt = $this->getConnection()->prepare(
                "SELECT post_id FROM PostBookmark WHERE user_id = ? AND post_id IN ({$placeholders})"
            );
            $stmt->execute($params);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $bookmarkedMap[(int)$row['post_id']] = true;
            }
        } catch (PDOException $e) {
            error_log('appendBookmarkFlags error: ' . $e->getMessage());
        }

        foreach ($posts as &$post) {
            $id = isset($post['post_id']) ? (int)$post['post_id'] : 0;
            $post['is_bookmarked'] = !empty($bookmarkedMap[$id]);
        }
        unset($post);
    }

    public function getUserSavedPosts(int $userId, int $viewerId = 0, int $limit = 200): array {
        if ($userId <= 0) {
            return [];
        }

        if (!$this->hasPostBookmarkTable) {
            $this->ensurePostBookmarkTable();
        }

        if (!$this->hasPostBookmarkTable) {
            return [];
        }

        $viewerId = (int)$viewerId;
        $viewerVoteSql = $viewerId > 0
            ? ", (SELECT vote_type FROM Vote WHERE post_id = p.post_id AND user_id = {$viewerId} LIMIT 1) AS user_vote"
            : ", NULL AS user_vote";
        $pollVoteSql = $this->selectPollVoteColumn($viewerId, 'p');

        $groupColumns = $this->selectGroupPostColumns();

        $sql = "
            SELECT
                p.post_id,
                p.content,
                p.post_type,
                p.visibility,
                p.created_at,
                p.upvote_count,
                p.downvote_count,
                p.comment_count,
            {$groupColumns}
                p.event_title,
                p.event_date,
                p.event_time,
                p.event_location,
                p.group_id,
                g.name AS group_name,
                u.user_id,
                u.username,
                u.first_name,
                u.last_name,
                u.profile_picture,
                pm.file_url AS image_url,
                pb.created_at AS saved_at,
                1 AS is_bookmarked
                {$viewerVoteSql}
                {$pollVoteSql}
            FROM PostBookmark pb
            INNER JOIN Post p ON p.post_id = pb.post_id
            INNER JOIN Users u ON u.user_id = p.author_id
            LEFT JOIN GroupsTable g ON g.group_id = p.group_id
            LEFT JOIN (
                SELECT pm1.post_id, pm1.file_url
                FROM PostMedia pm1
                INNER JOIN (
                    SELECT post_id, MIN(postmedia_id) AS first_media_id
                    FROM PostMedia
                    WHERE file_type = 'image'
                    GROUP BY post_id
                ) x ON x.first_media_id = pm1.postmedia_id
            ) pm ON pm.post_id = p.post_id
            WHERE pb.user_id = :user_id
              AND (p.group_id IS NULL OR COALESCE(g.is_active, 1) = 1)
            ORDER BY pb.created_at DESC
            LIMIT :result_limit
        ";

        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':result_limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($posts as &$post) {
                if (!empty($post['image_url'])) {
                    $post['image_url'] = MediaHelper::resolveMediaPath($post['image_url'], 'images/default_post.png');
                }
                if (!empty($post['profile_picture'])) {
                    $post['profile_picture'] = MediaHelper::resolveMediaPath($post['profile_picture'], 'images/avatars/defaultProfilePic.png');
                }
                $post['metadata'] = !empty($post['metadata']) ? json_decode($post['metadata'], true) : [];
                if (!is_array($post['metadata'])) {
                    $post['metadata'] = [];
                }
                $post['user_vote'] = $post['user_vote'] ?? null;
                $this->normalizePollPost($post, $viewerId);
            }
            unset($post);

            return $posts;
        } catch (PDOException $e) {
            error_log('getUserSavedPosts error: ' . $e->getMessage());
            return [];
        }
    }

    
    

    public function getPostById(int $postId): ?array {
        $sql = "
            SELECT
                p.post_id,
                p.content,
                p.post_type,
                p.visibility,
                p.created_at,
                p.upvote_count,
                p.comment_count,
                p.group_id,
                g.name AS group_name,
                u.user_id,
                u.username,
                u.first_name,
                u.last_name,
                u.profile_picture,
                pm.file_url AS image_url
            FROM Post p
            JOIN Users u ON u.user_id = p.author_id
            LEFT JOIN GroupsTable g ON g.group_id = p.group_id
            LEFT JOIN (
                SELECT pm1.post_id, pm1.file_url
                FROM PostMedia pm1
                INNER JOIN (
                    SELECT post_id, MIN(postmedia_id) AS first_media_id
                    FROM PostMedia
                    WHERE file_type = 'image'
                    GROUP BY post_id
                ) x ON x.first_media_id = pm1.postmedia_id
            ) pm ON pm.post_id = p.post_id
            WHERE p.post_id = :post_id
            LIMIT 1";

        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([':post_id' => $postId]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$post) {
                return null;
            }

            if (!empty($post['image_url'])) {
                $post['image_url'] = MediaHelper::resolveMediaPath($post['image_url'], 'images/default_post.png');
            }

            $post['profile_picture'] = MediaHelper::resolveMediaPath(
                $post['profile_picture'] ?? '',
                'images/avatars/defaultProfilePic.png'
            );

            return $post;
        } catch (PDOException $e) {
            error_log('getPostById error: ' . $e->getMessage());
            return null;
        }
    }
    /**
     * Get trending hashtags from posts (last 7 days)
     * Extracts hashtags from post content and ranks by frequency
     * @param int $limit Number of hashtags to return (default 10)
     * @return array Array of trending hashtags with rank, hashtag name, and count
     */
    public function getTrendingHashtags(int $limit = 10): array {
        try {
            // Fetch all posts from last 7 days
            $sql = "SELECT content FROM Post 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    AND content IS NOT NULL";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Extract and count hashtags
            $hashtags = [];
            foreach ($posts as $post) {
                // Match all #hashtag patterns (letters, numbers, underscores)
                if (preg_match_all('/#([a-zA-Z0-9_]+)/', $post['content'], $matches)) {
                    foreach ($matches[1] as $tag) {
                        $tag = strtolower($tag);
                        if (!isset($hashtags[$tag])) {
                            $hashtags[$tag] = 0;
                        }
                        $hashtags[$tag]++;
                    }
                }
            }

            // Sort by frequency (descending)
            arsort($hashtags);
            
            // Format output with rank
            $trending = [];
            $rank = 1;
            foreach (array_slice($hashtags, 0, $limit, true) as $tag => $count) {
                $trending[] = [
                    'rank' => $rank++,
                    'hashtag' => '#' . $tag,
                    'count' => $count
                ];
            }

            return $trending;
        } catch (PDOException $e) {
            error_log('getTrendingHashtags error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all posts authored by a specific user, including group posts and media.
     * Does not apply viewer-based privacy filtering (ProfileController will handle visibility checks).
     */
    public function getPostsByAuthor(int $authorId, int $viewerId = 0): array {
        try {
            $userVoteSql = $viewerId ? ", (SELECT vote_type FROM Vote WHERE post_id = p.post_id AND user_id = " . (int)$viewerId . " LIMIT 1) AS user_vote" : ", NULL AS user_vote";
            $pollVoteSql = $this->selectPollVoteColumn((int)$viewerId, 'p');
            $groupColumns = $this->selectGroupPostColumns();

            $sql = "
                SELECT
                    p.post_id,
                    p.content,
                    p.post_type,
                    p.visibility,
                    p.created_at,
                    p.upvote_count,
                    p.downvote_count,
                    p.comment_count,
                {$groupColumns}
                    p.event_title,
                    p.event_date,
                    p.event_time,
                    p.event_location,
                    p.group_id,
                    g.name AS group_name,
                    u.user_id,
                    u.username,
                    u.first_name,
                    u.last_name,
                    u.profile_picture,
                    pm.file_url AS image_url
                    {$userVoteSql}
                    {$pollVoteSql}
                FROM Post p
                JOIN Users u ON u.user_id = p.author_id
                LEFT JOIN (
                    SELECT pm1.post_id, pm1.file_url
                    FROM PostMedia pm1
                    INNER JOIN (
                        SELECT post_id, MIN(postmedia_id) AS first_media_id
                        FROM PostMedia
                        WHERE file_type = 'image'
                        GROUP BY post_id
                    ) x ON x.first_media_id = pm1.postmedia_id
                ) pm ON pm.post_id = p.post_id
                                LEFT JOIN GroupsTable g ON g.group_id = p.group_id
                                WHERE p.author_id = :author_id
                                    AND (p.group_id IS NULL OR COALESCE(g.is_active, 1) = 1)
                ORDER BY p.created_at DESC
            ";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([':author_id' => $authorId]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($posts as &$post) {
                if (!empty($post['image_url'])) {
                    $post['image_url'] = MediaHelper::resolveMediaPath($post['image_url'], 'images/default_post.png');
                }
                if (!empty($post['profile_picture'])) {
                    $post['profile_picture'] = MediaHelper::resolveMediaPath($post['profile_picture'], 'images/avatars/defaultProfilePic.png');
                }
                $post['metadata'] = !empty($post['metadata']) ? json_decode($post['metadata'], true) : [];
                if (!is_array($post['metadata'])) $post['metadata'] = [];
                $post['user_vote'] = $post['user_vote'] ?? null;
                $this->normalizePollPost($post, (int)$viewerId);
            }

            return $posts;
        } catch (PDOException $e) {
            error_log('getPostsByAuthor error: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserPosts(int $userId): array {
        try {
            $groupColumns = $this->selectGroupPostColumns();
            $sql = "SELECT p.post_id, p.content, p.post_type, p.visibility, p.created_at, p.updated_at, p.upvote_count, p.downvote_count, p.comment_count, p.share_count, p.is_edited, p.edited_at, {$groupColumns} p.event_title, p.event_date, p.event_time, p.event_location, p.is_group_post, p.author_id, u.user_id, u.username, u.first_name, u.last_name, u.profile_picture, pm.file_url AS image_url FROM Post p JOIN Users u ON u.user_id = p.author_id LEFT JOIN (SELECT pm1.post_id, pm1.file_url FROM PostMedia pm1 INNER JOIN (SELECT post_id, MIN(postmedia_id) AS first_media_id FROM PostMedia WHERE file_type = 'image' GROUP BY post_id) x ON x.first_media_id = pm1.postmedia_id) pm ON pm.post_id = p.post_id LEFT JOIN GroupsTable g ON g.group_id = p.group_id WHERE p.author_id = ? AND COALESCE(p.is_group_post, 0) = 0 ORDER BY p.created_at DESC";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([$userId]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($posts as &$post) {
                if (!empty($post['image_url'])) $post['image_url'] = MediaHelper::resolveMediaPath($post['image_url'], 'images/default_post.png');
                if (!empty($post['profile_picture'])) $post['profile_picture'] = MediaHelper::resolveMediaPath($post['profile_picture'], 'images/avatars/defaultProfilePic.png');
                $post['metadata'] = !empty($post['metadata']) ? json_decode($post['metadata'], true) : [];
                if (!is_array($post['metadata'])) $post['metadata'] = [];
            }
            return $posts;
        } catch (PDOException $e) {
            error_log('getUserPosts error: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserPostsCount(int $userId): int {
        try {
                        $sql = "SELECT COUNT(*)
                                        FROM Post p
                                        WHERE p.author_id = ?
                                            AND COALESCE(p.is_group_post, 0) = 0";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function getUserPhotoPosts(int $userId): array {
        try {
                        $sql = "SELECT DISTINCT p.post_id, pm.file_url AS image_url, p.created_at
                                        FROM Post p
                                        INNER JOIN PostMedia pm ON pm.post_id = p.post_id
                                        LEFT JOIN GroupsTable g ON g.group_id = p.group_id
                                        WHERE p.author_id = ?
                                            AND pm.file_type = 'image'
                                            AND (p.group_id IS NULL OR COALESCE(g.is_active, 1) = 1)
                                        ORDER BY p.created_at DESC";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([$userId]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($posts as &$post) {
                if (!empty($post['image_url'])) $post['image_url'] = MediaHelper::resolveMediaPath($post['image_url'], 'images/default_post.png');
            }
            return $posts;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getStats(): array {
        $sql = "SELECT 
                    COUNT(*) AS total_posts,
                    SUM(CASE WHEN COALESCE(is_group_post, 0) = 1 THEN 1 ELSE 0 END) AS group_posts,
                    SUM(CASE WHEN post_type = 'event' THEN 1 ELSE 0 END) AS event_posts
                FROM Post";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total_posts' => (int)($row['total_posts'] ?? 0),
            'group_posts' => (int)($row['group_posts'] ?? 0),
            'event_posts' => (int)($row['event_posts'] ?? 0)
        ];
    }

    public function getModerationSnapshot(): array {
        $defaults = [
            'total_reports' => 0,
            'reports_last_7' => 0,
            'post_reports' => 0,
            'comment_reports' => 0,
            'group_reports' => 0
        ];

        if (!$this->tableExists('Reports')) {
            return $defaults;
        }

        $sql = "SELECT 
                    COUNT(*) AS total_reports,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS reports_last_7,
                    SUM(CASE WHEN reported_post_id IS NOT NULL THEN 1 ELSE 0 END) AS post_reports,
                    SUM(CASE WHEN reported_comment_id IS NOT NULL THEN 1 ELSE 0 END) AS comment_reports,
                    SUM(CASE WHEN reported_group_id IS NOT NULL THEN 1 ELSE 0 END) AS group_reports
                FROM Reports";

        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            return [
                'total_reports' => (int)($row['total_reports'] ?? 0),
                'reports_last_7' => (int)($row['reports_last_7'] ?? 0),
                'post_reports' => (int)($row['post_reports'] ?? 0),
                'comment_reports' => (int)($row['comment_reports'] ?? 0),
                'group_reports' => (int)($row['group_reports'] ?? 0)
            ];
        } catch (PDOException $e) {
            error_log('getModerationSnapshot error: ' . $e->getMessage());
            return $defaults;
        }
    }
}
?>