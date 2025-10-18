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
    public function getFeedPosts(): array {
        $sql = "
            SELECT
                p.post_id,
                p.content,
                p.created_at,
                u.user_id,
                u.username,
                u.first_name,
                u.last_name,
                u.profile_picture,
                pm.file_url AS image_url
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
        
        // Adjust paths for DB: Map images to uploads, avatars to avatars folder
        foreach ($posts as &$post) {
            if (!empty($post['image_url'])) {
                $post['image_url'] = '/Hanthane/public/uploads/' . basename($post['image_url']);  // Drag from uploads
            }
            if (!empty($post['profile_picture'])) {
                $post['profile_picture'] = '/Hanthane/public/images/avatars/' . basename($post['profile_picture']);  // Drag from avatars
            }
        }
        return $posts;
    }

    // Merged from PostModel.php: Get single post by ID
    public function getById(int $postId): ?array {
        $sql = "
            SELECT
                p.post_id,
                p.content,
                p.created_at,
                u.user_id,
                u.username,
                u.first_name,
                u.last_name,
                u.profile_picture,
                pm.file_url AS image_url
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
            // Adjust paths
            if (!empty($row['image_url'])) {
                $row['image_url'] = '/Hanthane/public/uploads/' . basename($row['image_url']);
            }
            if (!empty($row['profile_picture'])) {
                $row['profile_picture'] = '/Hanthane/public/images/avatars/' . basename($row['profile_picture']);
            }
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
}