<?php

require_once __DIR__ . '/../core/Database.php';

class CalendarReminderModel {
    private $db;
    private $hasCalendarTable = false;

    public function __construct() {
        $this->db = new Database();
        $this->hasCalendarTable = $this->tableExists('CalendarReminders');
    }

    private function tableExists(string $table): bool {
        try {
            $stmt = $this->db->getConnection()->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$table]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('CalendarReminderModel tableExists error: ' . $e->getMessage());
            return false;
        }
    }

    private function ensureTable(): bool {
        if ($this->hasCalendarTable) {
            return true;
        }
        error_log('CalendarReminderModel: CalendarReminders table missing. Skipping reminder operation.');
        return false;
    }

    public function upsertReminder(int $userId, ?int $groupId, int $postId, array $payload): bool {
        if (!$this->ensureTable()) {
            return false;
        }

        $sql = "
            INSERT INTO CalendarReminders
                (user_id, group_id, post_id, title, event_date, event_time, location, description, metadata)
            VALUES
                (:user_id, :group_id, :post_id, :title, :event_date, :event_time, :location, :description, :metadata)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                event_date = VALUES(event_date),
                event_time = VALUES(event_time),
                location = VALUES(location),
                description = VALUES(description),
                metadata = VALUES(metadata),
                updated_at = CURRENT_TIMESTAMP
        ";

        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':group_id' => $groupId,
            ':post_id' => $postId,
            ':title' => $payload['title'] ?? 'Untitled Event',
            ':event_date' => $payload['event_date'] ?? null,
            ':event_time' => $payload['event_time'] ?? null,
            ':location' => $payload['location'] ?? null,
            ':description' => $payload['description'] ?? null,
            ':metadata' => !empty($payload['metadata']) ? json_encode($payload['metadata']) : null
        ]);
    }

    public function deleteReminder(int $userId, int $postId): bool {
        if (!$this->ensureTable()) {
            return false;
        }

        $stmt = $this->db->getConnection()->prepare(
            "DELETE FROM CalendarReminders WHERE user_id = ? AND post_id = ?"
        );
        return $stmt->execute([$userId, $postId]);
    }

    public function getReminderForPost(int $userId, int $postId): ?array {
        if (!$this->ensureTable()) {
            return null;
        }
        $stmt = $this->db->getConnection()->prepare(
            "SELECT * FROM CalendarReminders WHERE user_id = ? AND post_id = ? LIMIT 1"
        );
        $stmt->execute([$userId, $postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getRemindersForPosts(int $userId, array $postIds): array {
        if (empty($postIds) || !$this->ensureTable()) {
            return [];
        }
        $filtered = array_values(array_unique(array_map('intval', $postIds)));
        $placeholders = implode(',', array_fill(0, count($filtered), '?'));
        $params = array_merge([$userId], $filtered);
        $stmt = $this->db->getConnection()->prepare(
            "SELECT post_id FROM CalendarReminders WHERE user_id = ? AND post_id IN ($placeholders)"
        );
        $stmt->execute($params);
        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $map[(int)$row['post_id']] = true;
        }
        return $map;
    }

    public function listReminders(int $userId): array {
        if (!$this->ensureTable()) {
            return [];
        }
        $stmt = $this->db->getConnection()->prepare(
            "SELECT reminder_id, user_id, group_id, post_id, title, event_date, event_time, location, description, metadata, created_at, updated_at
             FROM CalendarReminders
             WHERE user_id = ?
             ORDER BY COALESCE(event_date, DATE(created_at)) ASC, COALESCE(event_time, '00:00:00') ASC"
        );
        $stmt->execute([$userId]);
        $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($reminders as &$reminder) {
            $reminder['metadata'] = !empty($reminder['metadata']) ? json_decode($reminder['metadata'], true) : [];
        }
        unset($reminder);
        return $reminders;
    }

    public function getGoingCount(int $postId): int {
        if (!$this->ensureTable()) {
            return 0;
        }
        $stmt = $this->db->getConnection()->prepare("SELECT COUNT(*) FROM CalendarReminders WHERE post_id = ?");
        $stmt->execute([$postId]);
        return (int)$stmt->fetchColumn();
    }
}
