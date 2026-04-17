<?php
require_once __DIR__ . '/../core/Database.php';

class ChannelModel {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function isActiveGroupMember(int $groupId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1
             FROM GroupMember
             WHERE group_id = ? AND user_id = ? AND status = 'active'
             LIMIT 1"
        );
        $stmt->execute([$groupId, $userId]);

        return (bool) $stmt->fetchColumn();
    }

    public function isChannelNameTaken(int $groupId, string $name): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1
             FROM Channel
             WHERE group_id = ? AND LOWER(name) = LOWER(?)
             LIMIT 1"
        );
        $stmt->execute([$groupId, trim($name)]);

        return (bool) $stmt->fetchColumn();
    }

    public function getChannelById(int $channelId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT channel_id, conversation_id, group_id, name, description, display_picture, created_by, created_at
             FROM Channel
             WHERE channel_id = ?
             LIMIT 1"
        );
        $stmt->execute([$channelId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function ensureMainChannelSetup(int $groupId): void
    {
        $groupStmt = $this->db->prepare(
            "SELECT group_id, name, created_by
             FROM GroupsTable
             WHERE group_id = ?
             LIMIT 1"
        );
        $groupStmt->execute([$groupId]);
        $group = $groupStmt->fetch(PDO::FETCH_ASSOC);

        if (!$group) {
            return;
        }

        $mainStmt = $this->db->prepare(
            "SELECT channel_id, conversation_id
             FROM Channel
             WHERE group_id = ? AND LOWER(name) = 'main'
             LIMIT 1"
        );
        $mainStmt->execute([$groupId]);
        $main = $mainStmt->fetch(PDO::FETCH_ASSOC);

        if (!$main) {
            $this->db->beginTransaction();
            try {
                $conversationStmt = $this->db->prepare(
                    "INSERT INTO Conversations (conversation_type, name, created_by, last_message_at, last_message_text)
                     VALUES ('group', ?, ?, NOW(), ?)"
                );
                $conversationStmt->execute([
                    $group['name'] . ' ⬥ Main',
                    (int) $group['created_by'],
                    'Welcome to the group!'
                ]);

                $conversationId = (int) $this->db->lastInsertId();

                $channelStmt = $this->db->prepare(
                    "INSERT INTO Channel (conversation_id, group_id, name, description, display_picture, created_by)
                     VALUES (?, ?, 'Main', ?, ?, ?)"
                );
                $channelStmt->execute([
                    $conversationId,
                    $groupId,
                    'Default group channel for all active members.',
                    'uploads/channel_dp/default.png',
                    (int) $group['created_by']
                ]);

                $this->db->commit();
                $main = [
                    'conversation_id' => $conversationId,
                ];
            } catch (Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw $e;
            }
        }

        $conversationId = (int) ($main['conversation_id'] ?? 0);
        if ($conversationId <= 0) {
            return;
        }

        $participantStmt = $this->db->prepare(
            "INSERT IGNORE INTO ConversationParticipants (conversation_id, user_id, role, is_active)
             SELECT ?, gm.user_id,
                    CASE WHEN gm.role = 'admin' THEN 'admin' ELSE 'member' END,
                    1
             FROM GroupMember gm
             WHERE gm.group_id = ? AND gm.status = 'active'"
        );
        $participantStmt->execute([$conversationId, $groupId]);
    }

    public function listChannelsForUser(int $groupId, int $userId): array
    {
        $this->ensureMainChannelSetup($groupId);

        $stmt = $this->db->prepare(
            "SELECT
                c.channel_id,
                c.conversation_id,
                c.group_id,
                c.name,
                c.description,
                c.display_picture,
                c.created_by,
                c.created_at,
                COUNT(CASE WHEN cp_all.is_active = 1 THEN 1 END) AS member_count,
                CASE WHEN cp.participant_id IS NULL OR cp.is_active = 0 THEN 0 ELSE 1 END AS joined,
                CASE WHEN LOWER(c.name) = 'main' THEN 1 ELSE 0 END AS is_main
             FROM Channel c
             LEFT JOIN ConversationParticipants cp
                ON cp.conversation_id = c.conversation_id
                AND cp.user_id = ?
             LEFT JOIN ConversationParticipants cp_all
                ON cp_all.conversation_id = c.conversation_id
             WHERE c.group_id = ?
             GROUP BY
                c.channel_id,
                c.conversation_id,
                c.group_id,
                c.name,
                c.description,
                c.display_picture,
                c.created_by,
                c.created_at,
                cp.participant_id,
                cp.is_active
             ORDER BY member_count DESC, c.created_at ASC, c.channel_id ASC"
        );
        $stmt->execute([$userId, $groupId]);
        $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($channels as &$channel) {
            $channel['joined'] = (bool) ((int) ($channel['joined'] ?? 0));
            $channel['is_main'] = (bool) ((int) ($channel['is_main'] ?? 0));
            $channel['member_count'] = (int) ($channel['member_count'] ?? 0);
            $channel['display_picture'] = $channel['display_picture'] ?: 'uploads/channel_dp/default.png';
        }

        return $channels;
    }

    public function createChannel(array $payload, int $userId): ?array
    {
        $groupId = (int) ($payload['group_id'] ?? 0);
        $name = trim((string) ($payload['name'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $displayPicture = trim((string) ($payload['display_picture'] ?? '')) ?: 'uploads/channel_dp/default.png';

        if ($groupId <= 0 || $name === '') {
            return null;
        }

        $groupStmt = $this->db->prepare(
            "SELECT name
             FROM GroupsTable
             WHERE group_id = ?
             LIMIT 1"
        );
        $groupStmt->execute([$groupId]);
        $group = $groupStmt->fetch(PDO::FETCH_ASSOC);
        if (!$group) {
            return null;
        }

        $this->db->beginTransaction();
        try {
            $conversationStmt = $this->db->prepare(
                "INSERT INTO Conversations (conversation_type, name, created_by, last_message_at, last_message_text)
                 VALUES ('group', ?, ?, NOW(), ?)"
            );
            $conversationStmt->execute([
                $group['name'] . ' ⬥ ' . $name,
                $userId,
                'Channel created'
            ]);
            $conversationId = (int) $this->db->lastInsertId();

            $channelStmt = $this->db->prepare(
                "INSERT INTO Channel (conversation_id, group_id, name, description, display_picture, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $channelStmt->execute([
                $conversationId,
                $groupId,
                $name,
                $description !== '' ? $description : null,
                $displayPicture,
                $userId,
            ]);
            $channelId = (int) $this->db->lastInsertId();

            $participantStmt = $this->db->prepare(
                "INSERT INTO ConversationParticipants (conversation_id, user_id, role, is_active)
                 VALUES (?, ?, 'admin', 1)"
            );
            $participantStmt->execute([$conversationId, $userId]);

            $messageStmt = $this->db->prepare(
                "INSERT INTO Messages (conversation_id, sender_id, message_type, content)
                 VALUES (?, ?, 'system', ?)"
            );
            $messageStmt->execute([$conversationId, $userId, 'Channel created.']);

            $this->db->commit();

            return $this->getChannelById($channelId);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('createChannel failed: ' . $e->getMessage());
            return null;
        }
    }

    public function updateChannel(int $channelId, array $payload): ?array
    {
        $channel = $this->getChannelById($channelId);
        if (!$channel) {
            return null;
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $displayPicture = trim((string) ($payload['display_picture'] ?? ''));

        if ($name === '') {
            return null;
        }

        $fields = ['name' => $name, 'description' => $description !== '' ? $description : null];
        if ($displayPicture !== '') {
            $fields['display_picture'] = $displayPicture;
        }

        $setParts = [];
        $params = [];
        foreach ($fields as $column => $value) {
            $setParts[] = $column . ' = ?';
            $params[] = $value;
        }
        $params[] = $channelId;

        $stmt = $this->db->prepare(
            'UPDATE Channel SET ' . implode(', ', $setParts) . ' WHERE channel_id = ?'
        );

        if (!$stmt->execute($params)) {
            return null;
        }

        return $this->getChannelById($channelId);
    }

    public function deleteChannel(int $channelId): bool
    {
        $channel = $this->getChannelById($channelId);
        if (!$channel) {
            return false;
        }

        $conversationId = (int) ($channel['conversation_id'] ?? 0);

        $this->db->beginTransaction();
        try {
            if ($conversationId > 0) {
                $conversationStmt = $this->db->prepare('DELETE FROM Conversations WHERE conversation_id = ?');
                $conversationStmt->execute([$conversationId]);
            }

            $channelStmt = $this->db->prepare('DELETE FROM Channel WHERE channel_id = ?');
            $channelStmt->execute([$channelId]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('deleteChannel failed: ' . $e->getMessage());
            return false;
        }
    }

    public function joinChannel(int $channelId, int $userId): bool
    {
        $channel = $this->getChannelById($channelId);
        if (!$channel) {
            return false;
        }

        $conversationId = (int) ($channel['conversation_id'] ?? 0);
        if ($conversationId <= 0) {
            return false;
        }

        $existingStmt = $this->db->prepare(
            "SELECT participant_id
             FROM ConversationParticipants
             WHERE conversation_id = ? AND user_id = ?
             LIMIT 1"
        );
        $existingStmt->execute([$conversationId, $userId]);
        $participantId = (int) $existingStmt->fetchColumn();

        if ($participantId > 0) {
            $updateStmt = $this->db->prepare(
                "UPDATE ConversationParticipants
                 SET is_active = 1, left_at = NULL
                 WHERE participant_id = ?"
            );
            return $updateStmt->execute([$participantId]);
        }

        $insertStmt = $this->db->prepare(
            "INSERT INTO ConversationParticipants (conversation_id, user_id, role, is_active)
             VALUES (?, ?, 'member', 1)"
        );

        return $insertStmt->execute([$conversationId, $userId]);
    }

    public function searchJoinedGroupChannels(int $userId, string $term, int $limit = 10) {
        $sql =
           "SELECT
                c.channel_id AS friend_user_id,
                c.name /*c.description,*/ AS username,
                c.display_picture AS profile_picture,
                c.name AS full_name,
                'group' AS conversation_type
            FROM Channel c
            INNER JOIN ConversationParticipants cp
                ON cp.conversation_id = c.conversation_id
                AND cp.user_id = :user
                AND cp.is_active = 1
                WHERE c.group_id IN (
                    SELECT gm.group_id FROM GroupMember gm
                    WHERE gm.user_id = :user2 AND gm.status = 'active'
                )
                AND c.name LIKE :term
            ORDER BY c.name ASC
            LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $like = '%' . $term . '%';
        $stmt->bindValue(':user', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':user2', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':term', $like, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
?>