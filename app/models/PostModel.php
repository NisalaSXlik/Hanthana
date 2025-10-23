<?php
require_once __DIR__ . '/../core/Database.php';  // Fixed path

class PostModel {
    private $db;

    public function __construct() {
        $this->db = new Database();  // Fixed: added ()
    }
    
    public function getConnection() {
        return $this->db->getConnection();
    }

    // Merged from PostModel.php: Get feed posts with images from uploads and user avatars from avatars
    public function getFeedPosts($userId = null): array {
        // Build user vote subquery if user is logged in
        $userVoteSql = $userId ? ", (SELECT vote_type FROM Vote WHERE post_id = p.post_id AND user_id = " . (int)$userId . " LIMIT 1) AS user_vote" : ", NULL AS user_vote";
        
        $sql = "
            SELECT
                p.post_id,
                p.content,
                p.created_at,
                p.upvote_count,
                p.comment_count,
                u.user_id,
                u.username,
                u.first_name,
                u.last_name,
                u.profile_picture,
                pm.file_url AS image_url
                {$userVoteSql}  -- Add user vote
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
            ORDER BY p.created_at DESC
        ";
        $stmt = $this->db->getConnection()->query($sql);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Adjust paths and process likers
        foreach ($posts as &$post) {
            if (!empty($post['image_url'])) {
                $post['image_url'] = $this->resolveImagePath($post['image_url']);
            }
            if (!empty($post['profile_picture'])) {
                $post['profile_picture'] = $this->resolveAvatarPath($post['profile_picture']);
            }
            /*// Process top_likers
            $post['likers'] = [];
            if (!empty($post['top_likers'])) {
                $likerPairs = explode(';', $post['top_likers']);
                foreach ($likerPairs as $pair) {
                    list($username, $avatarBasename) = explode('|', $pair);
                    $avatarPath = BASE_PATH . '/public/images/avatars/' . $avatarBasename;
                    $post['likers'][] = ['username' => $username, 'avatar' => $avatarPath];
                }
            }
            unset($post['top_likers']);*/
            // Add user_vote
            $post['user_vote'] = $post['user_vote'] ?? null;
        }
        return $posts;
    }

    /**
     * Helper method to resolve image paths properly
     */
    private function resolveImagePath(?string $path): string {
        if (empty($path)) {
            return '';
        }
        
        // If it's already a full URL, return as-is
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        
        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        $base = rtrim(BASE_PATH, '/');
        
        // If path already starts with BASE_PATH, return as-is
        if (strpos($path, $base) === 0) {
            return $path;
        }
        
        // If path starts with public/, use it directly
        if (strpos($normalized, 'public/') === 0) {
            return $base . '/' . $normalized;
        }
        
        // If path starts with images/ or uploads/, prepend public/
        if (strpos($normalized, 'images/') === 0 || strpos($normalized, 'uploads/') === 0) {
            return $base . '/public/' . $normalized;
        }
        
        // Try to find the file in common image directories
        $filename = basename($normalized);
        $possiblePaths = [
            __DIR__ . '/../../public/images/posts/' . $filename,
            __DIR__ . '/../../public/uploads/' . $filename,
            __DIR__ . '/../../public/images/' . $filename,
        ];
        
        foreach ($possiblePaths as $index => $fullPath) {
            if (file_exists($fullPath)) {
                // Return the corresponding URL based on which path exists
                if ($index === 0) {
                    return $base . '/public/images/posts/' . $filename;
                } elseif ($index === 1) {
                    return $base . '/public/uploads/' . $filename;
                } else {
                    return $base . '/public/images/' . $filename;
                }
            }
        }
        
        // If file doesn't exist anywhere, default to uploads directory
        // (the file might not exist yet or might be uploaded later)
        return $base . '/public/uploads/' . $filename;
    }
    
    /**
     * Helper method to resolve avatar paths properly
     */
    private function resolveAvatarPath(?string $path): string {
        if (empty($path)) {
            return BASE_PATH . '/public/images/avatars/defaultProfilePic.png';
        }
        
        // If it's already a full URL, return as-is
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        
        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        $base = rtrim(BASE_PATH, '/');
        
        // If path already starts with BASE_PATH, return as-is
        if (strpos($path, $base) === 0) {
            return $path;
        }
        
        // If path starts with public/, use it directly
        if (strpos($normalized, 'public/') === 0) {
            return $base . '/' . $normalized;
        }
        
        // If path starts with images/, prepend public/
        if (strpos($normalized, 'images/') === 0) {
            return $base . '/public/' . $normalized;
        }
        
        // Default to avatars directory
        return $base . '/public/images/avatars/' . basename($normalized);
    }

    // Merged from PostModel.php: Get single post by ID
    public function getById(int $postId, int $userId = 0): ?array {
        // Build user vote subquery if user is logged in
        $userVoteSql = $userId ? ", (SELECT vote_type FROM Vote WHERE post_id = p.post_id AND user_id = " . (int)$userId . " LIMIT 1) AS user_vote" : ", NULL AS user_vote";
        
        $sql = "
            SELECT
                p.post_id,
                p.content,
                p.created_at,
                p.upvote_count,
                p.downvote_count,
                p.comment_count,
                u.user_id,
                u.username,
                u.first_name,
                u.last_name,
                u.profile_picture,
                pm.file_url AS image_url
                {$userVoteSql}
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
            WHERE p.post_id = ?
            LIMIT 1
        ";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            // Adjust paths using helper methods
            if (!empty($row['image_url'])) {
                $row['image_url'] = $this->resolveImagePath($row['image_url']);
            }
            if (!empty($row['profile_picture'])) {
                $row['profile_picture'] = $this->resolveAvatarPath($row['profile_picture']);
            }
            // Ensure user_vote is set
            $row['user_vote'] = $row['user_vote'] ?? null;
        }
        return $row ?: null;
    }

    public function createPost($data) {
        try {
            $conn = $this->getConnection();  // Fixed: added $

            // Insert into Post table
            $stmt = $conn->prepare("
                INSERT INTO Post (content, post_type, visibility, event_title, event_date, event_location, is_group_post, group_id, author_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['content'],
                $data['post_type'],
                $data['visibility'],
                $data['event_title'] ?? null,
                $data['event_date'] ?? null,
                $data['event_location'] ?? null,
                $data['is_group_post'] ?? false,
                $data['group_id'] ?? null,
                $data['author_id']
            ]);

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
                    $data['image_path'],  // Now a full web path
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
        
        $sql = "
            SELECT
                p.post_id,
                p.content,
                p.created_at,
                p.upvote_count,
                p.comment_count,
                u.user_id,
                u.username,
                u.first_name,
                u.last_name,
                u.profile_picture,
                pm.file_url AS image_url,
                (p.upvote_count * 2 + p.comment_count) AS engagement_score
                {$userVoteSql}
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
            WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY engagement_score DESC, p.created_at DESC
            LIMIT :result_limit
        ";
        
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':result_limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adjust paths for each post
            foreach ($posts as &$post) {
                if (!empty($post['image_url'])) {
                    $post['image_url'] = $this->resolveImagePath($post['image_url']);
                }
                if (!empty($post['profile_picture'])) {
                    $post['profile_picture'] = $this->resolveAvatarPath($post['profile_picture']);
                }
                // Ensure user_vote is set
                $post['user_vote'] = $post['user_vote'] ?? null;
            }
            
            return $posts;
        } catch (PDOException $e) {
            error_log('getTrendingPosts error: ' . $e->getMessage());
            return [];
        }
    }
}