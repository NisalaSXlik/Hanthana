<?php

require_once __DIR__ . '/../../core/Database.php';

abstract class BaseGroupPostModel {
    protected $db;
    protected $hasGroupPostColumns = false;
    protected $hasPollVoteTable = false;

    public function __construct() {
        $this->db = new Database();
        $this->hasGroupPostColumns = $this->columnExists('Post', 'group_post_type');
        $this->hasPollVoteTable = $this->tableExists('GroupPostPollVote');
    }

    abstract public function getType(): string;

    /**
     * @param array $data sanitized form payload (content, image_path, file_path, request)
     */
    abstract public function create(int $userId, int $groupId, array $data): ?int;

    protected function columnExists(string $table, string $column): bool {
        try {
            $stmt = $this->db->getConnection()->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $stmt->execute([$column]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    protected function tableExists(string $table): bool {
        try {
            $stmt = $this->db->getConnection()->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    protected function persistPost(array $payload): ?int {
        $conn = $this->db->getConnection();
        $groupPostType = $payload['group_post_type'] ?? 'discussion';
        $dbPostType = $payload['db_post_type'] ?? $this->mapLegacyPostType($groupPostType);
        $content = $payload['content'] ?? '';
        $userId = (int)($payload['user_id'] ?? 0);
        $groupId = (int)($payload['group_id'] ?? 0);
        $metadata = $payload['metadata'] ?? [];
        $event = $payload['event'] ?? [];
        $imagePath = $payload['image_path'] ?? null;
        $documentPath = $payload['document_path'] ?? null;

        $metadataJson = ($this->hasGroupPostColumns && !empty($metadata)) ? json_encode($metadata) : null;
        $eventTitle = $event['title'] ?? null;
        $eventDate = $event['date'] ?? null;
        $eventTime = $event['time'] ?? null;
        $eventLocation = $event['location'] ?? null;

        try {
            if ($this->hasGroupPostColumns) {
                $sql = "INSERT INTO Post (author_id, group_id, content, post_type, visibility, is_group_post, group_post_type, metadata, event_title, event_date, event_time, event_location, created_at)
                        VALUES (?, ?, ?, ?, 'group', 1, ?, ?, ?, ?, ?, ?, NOW())";
                $params = [
                    $userId,
                    $groupId,
                    $content,
                    $dbPostType,
                    $groupPostType,
                    $metadataJson,
                    $eventTitle,
                    $eventDate,
                    $eventTime,
                    $eventLocation
                ];
            } else {
                $sql = "INSERT INTO Post (author_id, group_id, content, post_type, visibility, is_group_post, event_title, event_date, event_time, event_location, created_at)
                        VALUES (?, ?, ?, ?, 'group', 1, ?, ?, ?, ?, NOW())";
                $params = [
                    $userId,
                    $groupId,
                    $content,
                    $dbPostType,
                    $eventTitle,
                    $eventDate,
                    $eventTime,
                    $eventLocation
                ];
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            $postId = (int)$conn->lastInsertId();

            if ($imagePath) {
                $this->insertPostMedia($postId, $userId, $imagePath, 'image');
            }

            if ($documentPath) {
                $this->insertPostMedia($postId, $userId, $documentPath, 'document');
            }

            $this->incrementGroupPostCount($groupId);
            return $postId;
        } catch (PDOException $e) {
            error_log(static::class . ' persistPost error: ' . $e->getMessage());
            return null;
        }
    }

    protected function insertPostMedia(int $postId, int $userId, string $relativePath, string $fileType = 'image'): void {
        $conn = $this->db->getConnection();
        $mediaSql = "INSERT INTO PostMedia (post_id, uploader_id, file_name, file_type, file_url, uploaded_at)
                     VALUES (?, ?, ?, ?, ?, NOW())";
        $mediaStmt = $conn->prepare($mediaSql);
        $mediaStmt->execute([
            $postId,
            $userId,
            basename($relativePath),
            $fileType,
            $relativePath
        ]);
    }

    protected function incrementGroupPostCount(int $groupId): void {
        if ($groupId <= 0) {
            return;
        }
        $conn = $this->db->getConnection();
        $updateSql = "UPDATE GroupsTable SET post_count = post_count + 1 WHERE group_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute([$groupId]);
    }

    protected function mapLegacyPostType(string $groupPostType): string {
        switch ($groupPostType) {
            case 'poll':
                return 'poll';
            case 'event':
            case 'assignment':
                return 'event';
            default:
                return 'text';
        }
    }

    protected function updateMetadataColumn(int $postId, array $metadata): bool {
        if (!$this->hasGroupPostColumns) {
            return false;
        }
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE Post SET metadata = ? WHERE post_id = ?");
        return $stmt->execute([json_encode($metadata), $postId]);
    }

    protected function getConnection() {
        return $this->db->getConnection();
    }
}
