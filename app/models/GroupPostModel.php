<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';

class GroupPostModel {
    private Database $db;
    private bool $hasGroupPostColumns = false;
    private bool $hasPollVoteTable = false;

    public function __construct() {
        $this->db = new Database();
        $this->hasGroupPostColumns = $this->columnExists('Post', 'group_post_type');
        $this->hasPollVoteTable = $this->tableExists('GroupPostPollVote');
    }

    private function tableExists(string $table): bool {
        try {
            $stmt = $this->db->getConnection()->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                return false;
            }
            // Also check if option_index column exists if checking GroupPostPollVote
            if ($table === 'GroupPostPollVote') {
                return $this->columnExists('GroupPostPollVote', 'option_index');
            }
            return true;
        } catch (PDOException $e) {
            error_log("tableExists error for {$table}: " . $e->getMessage());
            return false;
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

    private function selectGroupPostColumns(): string {
        if ($this->hasGroupPostColumns) {
            return "                p.group_post_type,\n                p.metadata,";
        }

        return "                'discussion' AS group_post_type,\n                NULL AS metadata,";
    }

    private function formatMediaAndMetadata(array &$posts, int $viewerId = 0): void {
        foreach ($posts as &$post) {
            if (!empty($post['image_url'])) {
                $post['image_url'] = MediaHelper::resolveMediaPath($post['image_url'], 'images/default_post.png');
            }
            if (!empty($post['profile_picture'])) {
                $post['profile_picture'] = MediaHelper::resolveMediaPath($post['profile_picture'], 'images/avatars/defaultProfilePic.png');
            }
            $post['user_vote'] = $post['user_vote'] ?? null;
            $post['metadata'] = !empty($post['metadata']) ? json_decode($post['metadata'], true) : [];
            if (!is_array($post['metadata'])) {
                $post['metadata'] = [];
            }

            if (($post['group_post_type'] ?? '') === 'resource') {
                // Normalize legacy resource keys so downstream views can rely on the same names
                if (empty($post['metadata']['resource_type']) && !empty($post['metadata']['type'])) {
                    $post['metadata']['resource_type'] = $post['metadata']['type'];
                }
                if (empty($post['metadata']['resource_link']) && !empty($post['metadata']['link'])) {
                    $post['metadata']['resource_link'] = $post['metadata']['link'];
                }
            }
            
            // Recalculate poll votes from database for accuracy when table exists
            if ($this->hasPollVoteTable && ($post['group_post_type'] ?? '') === 'poll' && !empty($post['metadata']['options'])) {
                $post['metadata']['votes'] = $this->getPollVoteCounts((int)$post['post_id'], count($post['metadata']['options']));
            }
            
            // Extract user's vote from metadata when table doesn't exist
            if (!$this->hasPollVoteTable && ($post['group_post_type'] ?? '') === 'poll' && !empty($post['metadata']['user_votes'])) {
                $userId = $viewerId > 0 ? $viewerId : $this->getCurrentUserId();
                if ($userId > 0) {
                    $userVotes = $post['metadata']['user_votes'];
                    // Check both numeric and string keys since JSON encoding may convert keys
                    if (isset($userVotes[$userId])) {
                        $post['user_poll_vote'] = (int)$userVotes[$userId];
                    } elseif (isset($userVotes[(string)$userId])) {
                        $post['user_poll_vote'] = (int)$userVotes[(string)$userId];
                    }
                }
            }
        }
        unset($post);
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
            error_log("getPollVoteCounts error: " . $e->getMessage());
        }
        return $counts;
    }

    public function getPollVoteDetails(int $postId): array {
        error_log("getPollVoteDetails called for post $postId");
        $post = $this->getGroupPostById($postId);
        if (!$post || ($post['group_post_type'] ?? '') !== 'poll') {
            error_log("getPollVoteDetails: Post not found or not a poll");
            return ['options' => [], 'total_votes' => 0];
        }

        $metadata = $post['metadata'] ?? [];
        $options = $metadata['options'] ?? [];
        $optionCount = count($options);
        error_log("getPollVoteDetails: Found $optionCount options");
        $votersByOption = array_fill(0, $optionCount, []);
        $totalVotes = 0;
        $metadataVoters = $this->extractMetadataVoters($metadata, $optionCount);
        error_log("getPollVoteDetails: Metadata voters extracted: " . json_encode($metadataVoters));

        if ($optionCount === 0) {
            return ['options' => [], 'total_votes' => 0];
        }

        error_log("getPollVoteDetails: hasPollVoteTable = " . ($this->hasPollVoteTable ? 'true' : 'false'));
        if ($this->hasPollVoteTable) {
            try {
                $sql = "SELECT gpv.option_index, gpv.user_id, gpv.voted_at, u.username, u.first_name, u.last_name, u.profile_picture
                        FROM GroupPostPollVote gpv
                        JOIN Users u ON u.user_id = gpv.user_id
                        WHERE gpv.post_id = ?
                        ORDER BY gpv.voted_at DESC";
                $stmt = $this->db->getConnection()->prepare($sql);
                $stmt->execute([$postId]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $idx = (int)$row['option_index'];
                    if ($idx >= 0 && $idx < $optionCount) {
                        $totalVotes++;
                        $votersByOption[$idx][] = $this->formatVoterEntry($row);
                    }
                }
            } catch (PDOException $e) {
                error_log('getPollVoteDetails table path error: ' . $e->getMessage());
            }
            error_log("getPollVoteDetails: After table query, totalVotes = $totalVotes");
        }

        error_log("getPollVoteDetails: Before fallback check - totalVotes=$totalVotes, metadataVotersTotal=" . $metadataVoters['total']);
        if (!$this->hasPollVoteTable || $totalVotes === 0) {
            // Use metadata fallback either when table doesn't exist or table has no rows but metadata stores them
            if (!empty($metadataVoters['voters'])) {
                error_log("getPollVoteDetails: Using metadata fallback");
                $votersByOption = $metadataVoters['voters'];
                $totalVotes = $metadataVoters['total'];
            }
        } else {
            // When table has data we still need name data already included; nothing else to do
            error_log("getPollVoteDetails: Using table data");
        }

        error_log("getPollVoteDetails: Final totalVotes = $totalVotes");
        error_log("getPollVoteDetails: Final votersByOption = " . json_encode($votersByOption));

        $optionsPayload = [];
        foreach ($options as $idx => $label) {
            $optionVotes = count($votersByOption[$idx]);
            $optionsPayload[] = [
                'index' => $idx,
                'label' => $label,
                'votes' => $optionVotes,
                'percentage' => $totalVotes > 0 ? round(($optionVotes / $totalVotes) * 100) : 0,
                'voters' => $votersByOption[$idx]
            ];
        }

        return [
            'options' => $optionsPayload,
            'total_votes' => $totalVotes
        ];
    }

    private function formatVoterEntry(array $row): array {
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        if ($name === '') {
            $name = $row['username'] ?? 'Member';
        }
        $avatar = MediaHelper::resolveMediaPath($row['profile_picture'] ?? '', 'images/avatars/defaultProfilePic.png');
        return [
            'user_id' => (int)($row['user_id'] ?? 0),
            'name' => $name,
            'username' => $row['username'] ?? null,
            'avatar' => $avatar,
            'voted_at' => $row['voted_at'] ?? null
        ];
    }

    private function formatFallbackVoter(int $userId): array {
        return [
            'user_id' => $userId,
            'name' => 'Member #' . $userId,
            'username' => null,
            'avatar' => MediaHelper::resolveMediaPath('', 'images/avatars/defaultProfilePic.png'),
            'voted_at' => null
        ];
    }

    private function getUserSummaries(array $userIds): array {
        $filtered = array_values(array_unique(array_filter($userIds, function ($id) {
            return $id > 0;
        })));
        if (empty($filtered)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($filtered), '?'));
        try {
            $stmt = $this->db->getConnection()->prepare(
                "SELECT user_id, username, first_name, last_name, profile_picture
                 FROM Users
                 WHERE user_id IN ($placeholders)"
            );
            $stmt->execute($filtered);
            $map = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entry = $this->formatVoterEntry($row);
                $map[$entry['user_id']] = $entry;
            }
            return $map;
        } catch (PDOException $e) {
            error_log('getUserSummaries error: ' . $e->getMessage());
            return [];
        }
    }

    private function extractMetadataVoters(array $metadata, int $optionCount): array {
        error_log("extractMetadataVoters: metadata keys = " . json_encode(array_keys($metadata)));
        error_log("extractMetadataVoters: optionCount = $optionCount");
        
        if ($optionCount <= 0) {
            error_log("extractMetadataVoters: optionCount <= 0, returning empty");
            return ['voters' => array_fill(0, $optionCount, []), 'total' => 0];
        }
        $votersByOption = array_fill(0, $optionCount, []);
        $totalVotes = 0;
        $userVotes = $metadata['user_votes'] ?? [];
        
        error_log("extractMetadataVoters: user_votes = " . json_encode($userVotes));
        error_log("extractMetadataVoters: is_array(user_votes) = " . (is_array($userVotes) ? 'true' : 'false'));
        error_log("extractMetadataVoters: empty(user_votes) = " . (empty($userVotes) ? 'true' : 'false'));
        
        if (!is_array($userVotes) || empty($userVotes)) {
            error_log("extractMetadataVoters: user_votes is not array or empty, returning empty");
            return ['voters' => $votersByOption, 'total' => 0];
        }
        $userIdKeys = array_keys($userVotes);
        $userIdInts = [];
        foreach ($userIdKeys as $key) {
            $userIdInts[] = (int)$key;
        }
        
        error_log("extractMetadataVoters: userIdInts = " . json_encode($userIdInts));
        $userSummaries = $this->getUserSummaries($userIdInts);
        error_log("extractMetadataVoters: userSummaries = " . json_encode($userSummaries));
        
        foreach ($userVotes as $userKey => $voteIdx) {
            $idx = (int)$voteIdx;
            $userId = (int)$userKey;
            error_log("extractMetadataVoters: Processing userKey=$userKey, userId=$userId, voteIdx=$voteIdx, idx=$idx");
            
            if ($idx >= 0 && $idx < $optionCount && $userId > 0) {
                $totalVotes++;
                $summary = $userSummaries[$userId] ?? $this->formatFallbackVoter($userId);
                error_log("extractMetadataVoters: Adding voter to option $idx: " . json_encode($summary));
                $votersByOption[$idx][] = $summary;
            } else {
                error_log("extractMetadataVoters: Skipping invalid - idx=$idx, optionCount=$optionCount, userId=$userId");
            }
        }
        
        error_log("extractMetadataVoters: Returning totalVotes=$totalVotes");
        error_log("extractMetadataVoters: votersByOption = " . json_encode($votersByOption));
        return ['voters' => $votersByOption, 'total' => $totalVotes];
    }

    public function getGroupResourceLibrary(int $groupId): array {
        if ($groupId <= 0) {
            return [];
        }

        $sql = "
            SELECT p.post_id, p.content, p.metadata, p.created_at,
                   u.user_id, u.first_name, u.last_name, u.username
            FROM Post p
            JOIN Users u ON u.user_id = p.author_id
            WHERE p.group_id = ?
              AND p.is_group_post = 1
              AND p.group_post_type = 'resource'
            ORDER BY p.created_at DESC";

        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$groupId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getGroupResourceLibrary error: ' . $e->getMessage());
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $metadata = !empty($row['metadata']) ? json_decode($row['metadata'], true) : [];
            if (!is_array($metadata)) {
                $metadata = [];
            }
            if (empty($metadata['resource_type']) && !empty($metadata['type'])) {
                $metadata['resource_type'] = $metadata['type'];
            }
            if (empty($metadata['resource_link']) && !empty($metadata['link'])) {
                $metadata['resource_link'] = $metadata['link'];
            }

            $typeKey = $this->normalizeResourceTypeKey($metadata['resource_type'] ?? '');
            $resource = [
                'post_id' => (int)$row['post_id'],
                'title' => $metadata['title'] ?? 'Shared Resource',
                'description' => $row['content'] ?? '',
                'file_path' => $metadata['file_path'] ?? null,
                'link' => $metadata['resource_link'] ?? '',
                'uploaded_at' => $row['created_at'] ?? null,
                'uploader_name' => $this->formatResourceUploaderName($row),
                'uploader_id' => (int)($row['user_id'] ?? 0),
                'resource_type' => $typeKey
            ];

            if (!isset($grouped[$typeKey])) {
                $grouped[$typeKey] = [];
            }
            $grouped[$typeKey][] = $resource;
        }

        return $grouped;
    }

    private function normalizeResourceTypeKey(string $type): string {
        $key = strtolower(trim($type));
        if ($key === '') {
            return 'document';
        }

        $aliases = [
            'doc' => 'document',
            'docx' => 'document',
            'pdf' => 'document',
            'ppt' => 'slides',
            'pptx' => 'slides',
            'presentation' => 'slides',
            'url' => 'link',
            'web' => 'link',
            'article' => 'link'
        ];

        if (isset($aliases[$key])) {
            return $aliases[$key];
        }

        $valid = ['notes', 'slides', 'document', 'link', 'video', 'book'];
        if (in_array($key, $valid, true)) {
            return $key;
        }

        return 'other';
    }

    private function formatResourceUploaderName(array $row): string {
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        if ($name === '') {
            $name = $row['username'] ?? 'Member';
        }
        return $name;
    }

    public function getGroupPosts(int $groupId, int $userId = 0): array {
        $userVoteSql = $userId ? ", (SELECT vote_type FROM Vote WHERE post_id = p.post_id AND user_id = " . (int)$userId . " LIMIT 1) AS user_vote" : ", NULL AS user_vote";
        $pollVoteSql = ($userId && $this->hasPollVoteTable) ? ", (SELECT option_index FROM GroupPostPollVote WHERE post_id = p.post_id AND user_id = " . (int)$userId . " LIMIT 1) AS user_poll_vote" : ", NULL AS user_poll_vote";
        $groupColumns = $this->selectGroupPostColumns();
        $sql = "
            SELECT
                p.post_id,
                p.content,
                p.created_at,
                p.upvote_count,
                p.downvote_count,
                p.comment_count,
            {$groupColumns}
                p.event_title,
                p.event_date,
                p.event_time,
                p.event_location,
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
            WHERE p.is_group_post = 1 AND p.group_id = ?
            ORDER BY p.created_at DESC
        ";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$groupId]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->formatMediaAndMetadata($posts, $userId);
        return $posts;
    }

    public function getGroupPostById(int $postId, int $userId = 0): ?array {
        $userVoteSql = $userId ? ", (SELECT vote_type FROM Vote WHERE post_id = p.post_id AND user_id = " . (int)$userId . " LIMIT 1) AS user_vote" : ", NULL AS user_vote";
        $pollVoteSql = ($userId && $this->hasPollVoteTable) ? ", (SELECT option_index FROM GroupPostPollVote WHERE post_id = p.post_id AND user_id = " . (int)$userId . " LIMIT 1) AS user_poll_vote" : ", NULL AS user_poll_vote";
        $groupColumns = $this->selectGroupPostColumns();
        $sql = "
            SELECT
                p.post_id,
                p.content,
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
                p.is_group_post,
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
            WHERE p.post_id = ? AND p.is_group_post = 1
            LIMIT 1
        ";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $posts = [$row];
            $this->formatMediaAndMetadata($posts, $userId);
            return $posts[0];
        }
        return null;
    }

    private function getCurrentUserId(): int {
        if (isset($_SESSION) && isset($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }
        return 0;
    }

    /**
     * Get all group posts by a specific user
     */
    public function getUserGroupPosts(int $userId): array {
        $groupColumns = $this->selectGroupPostColumns();
        $sql = "
            SELECT
                p.post_id,
                p.content,
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
                p.is_group_post,
                u.user_id,
                u.username,
                u.first_name,
                u.last_name,
                u.profile_picture,
                g.name AS group_name,
                g.display_picture AS group_photo,
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
                        WHERE p.is_group_post = 1
                            AND p.author_id = ?
                            AND COALESCE(g.is_active, 1) = 1
            ORDER BY p.created_at DESC
        ";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->formatMediaAndMetadata($posts, $this->getCurrentUserId());
        return $posts;
    }

    /**
     * Get count of group posts by a specific user
     */
    public function getUserGroupPostsCount(int $userId): int {
        $sql = "SELECT COUNT(*) FROM Post WHERE is_group_post = 1 AND author_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
}
