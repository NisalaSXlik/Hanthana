<?php
require_once __DIR__ . '/../core/Database.php';

class PostModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = (new Database())->getConnection();
    }

    // Feed/listing using schema: Post(content, author_id) + first image from PostMedia
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
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Create a post and optionally attach one image via PostMedia
    public function createPost(int $authorId, ?string $content, ?string $imageUrl): int {
        $this->pdo->beginTransaction();
        try {
            // Insert minimal required fields. Add more columns if your schema requires.
            $stmt = $this->pdo->prepare(
                "INSERT INTO Post (author_id, content) VALUES (?, ?)"
            );
            $stmt->execute([$authorId, $content]);
            $postId = (int)$this->pdo->lastInsertId();

            if ($imageUrl) {
                $fileName = basename($imageUrl);
                $stmt2 = $this->pdo->prepare(
                    "INSERT INTO PostMedia (post_id, uploader_id, file_name, file_type, file_url)
                     VALUES (?, ?, ?, 'image', ?)"
                );
                $stmt2->execute([$postId, $authorId, $fileName, $imageUrl]);
            }

            $this->pdo->commit();
            return $postId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

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
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}