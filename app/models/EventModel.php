<?php

require_once __DIR__ . '/../core/Database.php';

class EventModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = (new Database())->getConnection();
    }

    /**
     * Get upcoming events (future events)
     */
    public function getUpcomingEvents(int $userId = 0, int $limit = 20): array {
        $sql = "
            SELECT 
                e.*,
                g.name AS group_name,
                g.display_picture AS group_picture,
                u.first_name,
                u.last_name,
                u.username,
                er.status AS user_rsvp_status,
                (SELECT COUNT(*) FROM EventRSVP WHERE event_id = e.event_id AND status = 'going') AS going_count,
                (SELECT COUNT(*) FROM EventRSVP WHERE event_id = e.event_id AND status = 'interested') AS interested_count
            FROM Event e
            LEFT JOIN GroupsTable g ON g.group_id = e.group_id
            LEFT JOIN Users u ON u.user_id = e.created_by
            LEFT JOIN EventRSVP er ON er.event_id = e.event_id AND er.user_id = :user_id
            WHERE e.is_active = TRUE
              AND e.event_date >= CURDATE()
            ORDER BY e.event_date ASC, e.event_time ASC
            LIMIT :result_limit
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':result_limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getUpcomingEvents error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user's events (created or RSVP'd)
     */
    public function getUserEvents(int $userId, int $limit = 20): array {
        $sql = "
            SELECT 
                e.*,
                g.name AS group_name,
                g.display_picture AS group_picture,
                u.first_name,
                u.last_name,
                u.username,
                er.status AS user_rsvp_status,
                (SELECT COUNT(*) FROM EventRSVP WHERE event_id = e.event_id AND status = 'going') AS going_count,
                (SELECT COUNT(*) FROM EventRSVP WHERE event_id = e.event_id AND status = 'interested') AS interested_count
            FROM Event e
            LEFT JOIN GroupsTable g ON g.group_id = e.group_id
            LEFT JOIN Users u ON u.user_id = e.created_by
            LEFT JOIN EventRSVP er ON er.event_id = e.event_id AND er.user_id = :user_id
            WHERE e.is_active = TRUE
              AND (e.created_by = :user_id OR er.user_id = :user_id)
            ORDER BY e.event_date ASC, e.event_time ASC
            LIMIT :result_limit
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':result_limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getUserEvents error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get past events
     */
    public function getPastEvents(int $userId = 0, int $limit = 20): array {
        $sql = "
            SELECT 
                e.*,
                g.name AS group_name,
                g.display_picture AS group_picture,
                u.first_name,
                u.last_name,
                u.username,
                er.status AS user_rsvp_status,
                (SELECT COUNT(*) FROM EventRSVP WHERE event_id = e.event_id AND status = 'going') AS going_count,
                (SELECT COUNT(*) FROM EventRSVP WHERE event_id = e.event_id AND status = 'interested') AS interested_count
            FROM Event e
            LEFT JOIN GroupsTable g ON g.group_id = e.group_id
            LEFT JOIN Users u ON u.user_id = e.created_by
            LEFT JOIN EventRSVP er ON er.event_id = e.event_id AND er.user_id = :user_id
            WHERE e.is_active = TRUE
              AND e.event_date < CURDATE()
            ORDER BY e.event_date DESC, e.event_time DESC
            LIMIT :result_limit
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':result_limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getPastEvents error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new event
     */
    public function createEvent(array $data): int {
        try {
            $sql = "INSERT INTO Event 
                    (title, description, event_date, event_time, location, group_id, created_by) 
                    VALUES 
                    (:title, :description, :event_date, :event_time, :location, :group_id, :created_by)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':event_date' => $data['event_date'],
                ':event_time' => $data['event_time'] ?? null,
                ':location' => $data['location'] ?? null,
                ':group_id' => $data['group_id'] ?? null,
                ':created_by' => $data['created_by']
            ]);

            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log('createEvent error: ' . $e->getMessage());
            throw new Exception('Failed to create event: ' . $e->getMessage());
        }
    }

    /**
     * Set user RSVP status for an event
     */
    public function setUserRSVP(int $eventId, int $userId, string $status): bool {
        try {
            // Check if RSVP exists
            $checkSql = "SELECT rsvp_id FROM EventRSVP WHERE event_id = ? AND user_id = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$eventId, $userId]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update existing RSVP
                $sql = "UPDATE EventRSVP SET status = ?, updated_at = NOW() WHERE event_id = ? AND user_id = ?";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([$status, $eventId, $userId]);
            } else {
                // Insert new RSVP
                $sql = "INSERT INTO EventRSVP (event_id, user_id, status) VALUES (?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([$eventId, $userId, $status]);
            }
        } catch (PDOException $e) {
            error_log('setUserRSVP error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get event by ID
     */
    public function getById(int $eventId): ?array {
        $sql = "
            SELECT 
                e.*,
                g.name AS group_name,
                g.display_picture AS group_picture,
                u.first_name,
                u.last_name,
                u.username,
                (SELECT COUNT(*) FROM EventRSVP WHERE event_id = e.event_id AND status = 'going') AS going_count,
                (SELECT COUNT(*) FROM EventRSVP WHERE event_id = e.event_id AND status = 'interested') AS interested_count
            FROM Event e
            LEFT JOIN GroupsTable g ON g.group_id = e.group_id
            LEFT JOIN Users u ON u.user_id = e.created_by
            WHERE e.event_id = ? AND e.is_active = TRUE
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$eventId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
