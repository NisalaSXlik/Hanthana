<?php
require_once __DIR__ . '/../core/Database.php';

class NotificationsModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Create a notification for a user
     */
    public function createNotification(int $userId, ?int $triggeredByUserId, string $type, string $title, string $message, ?string $actionUrl = null, string $priority = 'medium') : bool {
        try {
            $sql = "INSERT INTO Notifications (user_id, triggered_by_user_id, type, title, message, action_url, priority) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->getConnection()->prepare($sql);
            return (bool)$stmt->execute([$userId, $triggeredByUserId, $type, $title, $message, $actionUrl, $priority]);
        } catch (PDOException $e) {
            error_log('createNotification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get latest notifications for a user (limit)
     */
    public function getLatestNotifications(int $userId, int $limit = 8): array {
        try {
            // Join with Users to fetch basic info about who triggered the notification
            $sql = "SELECT n.*, u.first_name AS trigger_first_name, u.last_name AS trigger_last_name, u.profile_picture AS trigger_profile_picture
                    FROM Notifications n
                    LEFT JOIN Users u ON u.user_id = n.triggered_by_user_id
                    WHERE n.user_id = :user_id
                    ORDER BY n.created_at DESC
                    LIMIT :limit";

            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getLatestNotifications error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count unread notifications
     */
    public function countUnread(int $userId): int {
        try {
            $stmt = $this->db->getConnection()->prepare("SELECT COUNT(*) FROM Notifications WHERE user_id = ? AND is_read = FALSE");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('countUnread error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool {
        try {
            $sql = "UPDATE Notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP WHERE notification_id = ? AND user_id = ?";
            $stmt = $this->db->getConnection()->prepare($sql);
            return (bool)$stmt->execute([$notificationId, $userId]);
        } catch (PDOException $e) {
            error_log('markAsRead error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a single notification belonging to a user
     */
    public function deleteNotification(int $notificationId, int $userId): bool {
        try {
            $sql = "DELETE FROM Notifications WHERE notification_id = ? AND user_id = ?";
            $stmt = $this->db->getConnection()->prepare($sql);
            return (bool)$stmt->execute([$notificationId, $userId]);
        } catch (PDOException $e) {
            error_log('deleteNotification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete read notifications (optionally older than X days) for a user. This is used by the "Clear read" UI.
     */
    public function deleteReadNotifications(int $userId, int $olderThanDays = 0): bool {
        try {
            if ($olderThanDays > 0) {
                $sql = "DELETE FROM Notifications WHERE user_id = ? AND is_read = TRUE AND created_at < (NOW() - INTERVAL ? DAY)";
                $stmt = $this->db->getConnection()->prepare($sql);
                return (bool)$stmt->execute([$userId, $olderThanDays]);
            } else {
                $sql = "DELETE FROM Notifications WHERE user_id = ? AND is_read = TRUE";
                $stmt = $this->db->getConnection()->prepare($sql);
                return (bool)$stmt->execute([$userId]);
            }
        } catch (PDOException $e) {
            error_log('deleteReadNotifications error: ' . $e->getMessage());
            return false;
        }
    }
}
