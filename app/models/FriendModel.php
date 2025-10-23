<?php
require_once __DIR__ . '/../core/Database.php';

class FriendModel
{
    private PDO $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getFriendship(int $userId, int $otherUserId): ?array
    {
        $sql = "SELECT * FROM Friends
                WHERE (user_id = :user AND friend_id = :other)
                   OR (user_id = :other AND friend_id = :user)
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user' => $userId,
            ':other' => $otherUserId,
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function sendFriendRequest(int $requesterId, int $targetId): array
    {
        $existing = $this->getFriendship($requesterId, $targetId);

        if ($existing) {
            $status = $existing['status'];
            $requesterIsSender = (int)$existing['user_id'] === $requesterId;

            if ($status === 'accepted') {
                return [
                    'status' => 'friends',
                    'message' => 'You are already friends.',
                ];
            }

            if ($status === 'blocked') {
                return [
                    'status' => 'blocked',
                    'message' => 'You cannot send a friend request to this user.',
                ];
            }

            if ($status === 'pending') {
                if ($requesterIsSender) {
                    return [
                        'status' => 'pending_outgoing',
                        'message' => 'Friend request already sent.',
                    ];
                }

                return [
                    'status' => 'incoming_pending',
                    'message' => 'This user has already sent you a friend request.',
                ];
            }
        }

        $sql = "INSERT INTO Friends (user_id, friend_id, status) VALUES (:user, :friend, 'pending')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user' => $requesterId,
            ':friend' => $targetId,
        ]);

        return [
            'status' => 'pending_outgoing',
            'message' => 'Friend request sent successfully.',
        ];
    }

    public function getIncomingRequests(int $userId, int $limit = 10): array
    {
        $sql = "SELECT f.friendship_id,
                       f.user_id   AS requester_id,
                       f.friend_id AS target_id,
                       f.status,
                       f.requested_at,
                       u.first_name,
                       u.last_name,
                       u.username,
                       u.profile_picture,
                       u.friends_count
                FROM Friends f
                INNER JOIN Users u ON u.user_id = f.user_id
                WHERE f.friend_id = :target
                  AND f.status = 'pending'
                ORDER BY f.requested_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':target', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function acceptFriendRequest(int $friendshipId, int $userId): array
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "SELECT friendship_id, user_id, friend_id, status
                 FROM Friends
                 WHERE friendship_id = :id
                 FOR UPDATE"
            );
            $stmt->execute([':id' => $friendshipId]);
            $friendship = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$friendship) {
                throw new \RuntimeException('Friend request not found.');
            }

            $requesterId = (int)$friendship['user_id'];
            $targetId = (int)$friendship['friend_id'];

            if ($targetId !== $userId) {
                throw new \RuntimeException('You are not authorized to accept this request.');
            }

            if ($friendship['status'] === 'accepted') {
                $counts = $this->getFriendCounts($requesterId, $targetId);
                $this->db->commit();

                return [
                    'status' => 'accepted',
                    'message' => 'You are already friends.',
                    'friend_count' => $counts[$targetId] ?? null,
                    'friend_count_other' => $counts[$requesterId] ?? null,
                ];
            }

            if ($friendship['status'] !== 'pending') {
                throw new \RuntimeException('This friend request can no longer be accepted.');
            }

            $updateStmt = $this->db->prepare(
                "UPDATE Friends
                 SET status = 'accepted', accepted_at = CURRENT_TIMESTAMP
                 WHERE friendship_id = :id"
            );
            $updateStmt->execute([':id' => $friendshipId]);

            $incrementStmt = $this->db->prepare(
                "UPDATE Users
                 SET friends_count = friends_count + 1
                 WHERE user_id IN (:requester, :target)"
            );
            $incrementStmt->execute([
                ':requester' => $requesterId,
                ':target' => $targetId,
            ]);

            $counts = $this->getFriendCounts($requesterId, $targetId);
            $this->db->commit();

            return [
                'status' => 'accepted',
                'message' => 'Friend request accepted.',
                'friend_count' => $counts[$targetId] ?? null,
                'friend_count_other' => $counts[$requesterId] ?? null,
            ];
    } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function declineFriendRequest(int $friendshipId, int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT friendship_id, user_id, friend_id, status
             FROM Friends
             WHERE friendship_id = :id"
        );
        $stmt->execute([':id' => $friendshipId]);
        $friendship = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$friendship) {
            throw new \RuntimeException('Friend request not found.');
        }

        if ((int)$friendship['friend_id'] !== $userId) {
            throw new \RuntimeException('You are not authorized to decline this request.');
        }

        if ($friendship['status'] !== 'pending') {
            throw new \RuntimeException('This friend request can no longer be declined.');
        }

        $deleteStmt = $this->db->prepare('DELETE FROM Friends WHERE friendship_id = :id');
        $deleteStmt->execute([':id' => $friendshipId]);

        return [
            'status' => 'declined',
            'message' => 'Friend request declined.',
        ];
    }

    public function getAcceptedFriends(int $userId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT
                    f.friendship_id,
                    CASE WHEN f.user_id = :user THEN f.friend_id ELSE f.user_id END AS friend_user_id,
                    u.first_name,
                    u.last_name,
                    u.username,
                    u.profile_picture,
                    u.friends_count,
                    f.accepted_at
                FROM Friends f
                INNER JOIN Users u ON u.user_id = CASE WHEN f.user_id = :user THEN f.friend_id ELSE f.user_id END
                WHERE f.status = 'accepted'
                  AND (f.user_id = :user OR f.friend_id = :user)
                ORDER BY f.accepted_at DESC, u.first_name ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getFriendCounts(int $firstUserId, int $secondUserId): array
    {
        $stmt = $this->db->prepare(
            "SELECT user_id, friends_count
             FROM Users
             WHERE user_id IN (:first, :second)"
        );
        $stmt->execute([
            ':first' => $firstUserId,
            ':second' => $secondUserId,
        ]);

        $counts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $counts[(int)$row['user_id']] = (int)$row['friends_count'];
        }

        return $counts;
    }

    /**
     * Remove an accepted friendship between two users and decrement their friends_count.
     */
    public function removeFriendship(int $userId, int $otherUserId): array
    {
        $this->db->beginTransaction();
        try {
            // Lock the friendship row
            $stmt = $this->db->prepare(
                "SELECT friendship_id, user_id, friend_id, status
                 FROM Friends
                 WHERE (user_id = :user AND friend_id = :other)
                    OR (user_id = :other AND friend_id = :user)
                 FOR UPDATE"
            );
            $stmt->execute([
                ':user' => $userId,
                ':other' => $otherUserId,
            ]);
            $friendship = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$friendship || $friendship['status'] !== 'accepted') {
                throw new \RuntimeException('You are not friends.');
            }

            // Delete friendship
            $del = $this->db->prepare('DELETE FROM Friends WHERE friendship_id = :id');
            $del->execute([':id' => (int)$friendship['friendship_id']]);

            // Decrement both users' counts
            $dec = $this->db->prepare(
                'UPDATE Users SET friends_count = CASE WHEN friends_count > 0 THEN friends_count - 1 ELSE 0 END WHERE user_id IN (:a, :b)'
            );
            $dec->execute([':a' => $userId, ':b' => $otherUserId]);

            $counts = $this->getFriendCounts($userId, $otherUserId);
            $this->db->commit();

            return [
                'status' => 'removed',
                'message' => 'Friend removed successfully.',
                'friend_count' => $counts[$userId] ?? null,
                'friend_count_other' => $counts[$otherUserId] ?? null,
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}