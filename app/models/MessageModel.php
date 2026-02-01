<?php
require_once __DIR__ . '/../core/Database.php';

class MessageModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    private function conn(): PDO {
        return $this->db->getConnection();
    }

    public function getUserConversations(int $userId): array {
        $sql =
           "SELECT
                c.conversation_id, c.conversation_type, c.name AS conversation_name,
                c.last_message_at, c.last_message_text,
                
                lm.message_id AS last_message_id,
                lm.message_type AS last_message_type,
                lm.content AS last_message_content,
                lm.created_at AS message_created_at,
                
                sender.user_id AS sender_id,
                sender.first_name AS sender_first_name,
                sender.last_name AS sender_last_name,
                sender.profile_picture AS sender_avatar,
                
                COALESCE(unread_counts.unread_count, 0) AS unread_count
            FROM Conversations c
            INNER JOIN ConversationParticipants cp
                ON cp.conversation_id = c.conversation_id
                AND cp.user_id = :userId
                AND cp.is_active = TRUE
            LEFT JOIN Messages lm
                ON lm.message_id = (
                    SELECT m2.message_id
                    FROM Messages m2
                    WHERE
                        m2.conversation_id = c.conversation_id
                        AND m2.is_deleted = FALSE
                    ORDER BY m2.created_at DESC
                    LIMIT 1
                )
            LEFT JOIN Users sender
                ON sender.user_id = lm.sender_id
            LEFT JOIN (
                SELECT
                    m.conversation_id,
                    COUNT(*) AS unread_count
                FROM Messages m
                LEFT JOIN MessageReadStatus mrs
                    ON mrs.message_id = m.message_id
                    AND mrs.user_id = :userId
                WHERE
                    m.is_deleted = FALSE
                    AND m.sender_id <> :userId
                    AND mrs.message_id IS NULL
                GROUP BY m.conversation_id
            ) AS unread_counts
                ON unread_counts.conversation_id = c.conversation_id
            ORDER BY c.last_message_at DESC, c.conversation_id DESC";

        $stmt = $this->conn()->prepare($sql);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $conversations = [];
        foreach ($rows as $row) {
            $conversations[] = $this->formatConversation($row, $userId);
        }

        return $conversations;
    }

    public function getConversationById(int $conversationId, int $userId): ?array {
        $sql =
           "SELECT
                c.conversation_id, c.conversation_type, c.name AS conversation_name,
                c.last_message_at, c.last_message_text,
                
                lm.message_id AS last_message_id,
                lm.message_type AS last_message_type,
                lm.content AS last_message_content,
                lm.created_at AS message_created_at,
                
                sender.user_id AS sender_id,
                sender.first_name AS sender_first_name,
                sender.last_name AS sender_last_name,
                sender.profile_picture AS sender_avatar
            FROM Conversations c
            INNER JOIN ConversationParticipants cp
                ON cp.conversation_id = c.conversation_id
                AND cp.user_id = :userId
                AND cp.is_active = TRUE
            LEFT JOIN Messages lm
                ON lm.message_id = (
                    SELECT m2.message_id
                    FROM Messages m2
                    WHERE m2.conversation_id = c.conversation_id AND m2.is_deleted = FALSE
                    ORDER BY m2.created_at DESC
                    LIMIT 1
                )
            LEFT JOIN Users sender ON sender.user_id = lm.sender_id
            WHERE c.conversation_id = :conversationId
            LIMIT 1";

        $stmt = $this->conn()->prepare($sql);
        $stmt->bindValue(':conversationId', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->formatConversation($row, $userId);
    }

    public function ensureDirectConversation(int $userId, int $otherUserId): array {
        $existingId = $this->findDirectConversationId($userId, $otherUserId);
        if ($existingId) {
            $this->ensureParticipant($existingId, $userId);
            $this->ensureParticipant($existingId, $otherUserId);
            $conversation = $this->getConversationById($existingId, $userId);
            if ($conversation) {
                return $conversation;
            }
        }

        $connection = $this->conn();
        $connection->beginTransaction();
        try {
            $stmt = $connection->prepare(
                "INSERT INTO Conversations (conversation_type, created_by, created_at, last_message_at)
                 VALUES ('direct', :creator, NOW(), NOW())"
            );
            $stmt->execute([':creator' => $userId]);
            $conversationId = (int)$connection->lastInsertId();

            $participantStmt = $connection->prepare(
                "INSERT INTO ConversationParticipants (conversation_id, user_id, role, is_active)
                 VALUES (:conversationId, :userId, 'member', TRUE)"
            );

            foreach ([$userId, $otherUserId] as $participant) {
                $participantStmt->execute([
                    ':conversationId' => $conversationId,
                    ':userId' => $participant,
                ]);
            }

            $connection->commit();
        } catch (\Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw new RuntimeException('Unable to create conversation.');
        }

        $conversation = $this->getConversationById($conversationId, $userId);
        if (!$conversation) {
            throw new RuntimeException('Conversation could not be loaded.');
        }

        return $conversation;
    }

    public function ensureGroupConversation($userId, $channelId) {
        $sql =
           "SELECT
            co.conversation_id, co.conversation_type, co.name AS conversation_name,
            co.last_message_at, co.last_message_text,
            
            lm.message_id AS last_message_id,
            lm.message_type AS last_message_type,
            lm.content AS last_message_content,
            lm.created_at AS message_created_at,
            
            ch.channel_id AS sender_id,
            ch.name AS sender_first_name,
            '' AS sender_last_name,
            ch.display_picture AS sender_avatar
        FROM Conversations co
        INNER JOIN Channel ch ON ch.conversation_id = co.conversation_id
        INNER JOIN ConversationParticipants cp
            ON cp.conversation_id = co.conversation_id
            AND cp.user_id = :userId
            AND cp.is_active = TRUE
        LEFT JOIN Messages lm
            ON lm.message_id = (
                SELECT m2.message_id
                FROM Messages m2
                WHERE m2.conversation_id = co.conversation_id
                AND m2.is_deleted = FALSE
                ORDER BY m2.created_at DESC
                LIMIT 1
            )
        WHERE ch.channel_id = :channelId
        LIMIT 1";

        $stmt = $this->conn()->prepare($sql);
        $stmt->bindValue(':channelId', $channelId, PDO::PARAM_INT);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $this->formatConversation($row, $userId);
        }

        // If conversation doesn't exist, create it
        return $this->createGroupConversation($userId, $channelId);
    }

    // Add this new method to create group conversations
    private function createGroupConversation(int $userId, int $channelId): array {
        // First, check if user is a member of the group
        $checkSql = "SELECT g.group_id, c.name 
                 FROM Channel c
                 INNER JOIN GroupsTable g ON c.group_id = g.group_id
                 INNER JOIN GroupMember gm ON g.group_id = gm.group_id
                 WHERE c.channel_id = :channelId 
                 AND gm.user_id = :userId 
                 AND gm.status = 'active'";
        
        $checkStmt = $this->conn()->prepare($checkSql);
        $checkStmt->execute([':channelId' => $channelId, ':userId' => $userId]);
        $channelData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$channelData) {
            throw new RuntimeException('You are not a member of this group.');
        }

        $connection = $this->conn();
        $connection->beginTransaction();
        
        try {
            // Create conversation
            $stmt = $connection->prepare(
                "INSERT INTO Conversations (conversation_type, name, created_by, created_at, last_message_at)
                 VALUES ('group', :name, :creator, NOW(), NOW())"
            );
            $stmt->execute([
                ':name' => $channelData['name'],
                ':creator' => $userId
            ]);
            $conversationId = (int)$connection->lastInsertId();

            // Link channel to conversation
            $linkStmt = $connection->prepare(
                "UPDATE Channel SET conversation_id = :conversationId WHERE channel_id = :channelId"
            );
            $linkStmt->execute([
                ':conversationId' => $conversationId,
                ':channelId' => $channelId
            ]);

            // Add all group members as conversation participants
            $membersSql = "SELECT user_id FROM GroupMember 
                       WHERE group_id = :groupId AND status = 'active'";
            $membersStmt = $connection->prepare($membersSql);
            $membersStmt->execute([':groupId' => $channelData['group_id']]);
            $members = $membersStmt->fetchAll(PDO::FETCH_COLUMN);

            $participantStmt = $connection->prepare(
                "INSERT INTO ConversationParticipants (conversation_id, user_id, role, is_active)
                 VALUES (:conversationId, :userId, 'member', TRUE)"
            );

            foreach ($members as $memberId) {
                $participantStmt->execute([
                    ':conversationId' => $conversationId,
                    ':userId' => $memberId
                ]);
            }

            $connection->commit();
            
            $conversation = $this->getConversationById($conversationId, $userId);
            if (!$conversation) {
                throw new RuntimeException('Conversation could not be loaded.');
            }

            return $conversation;
            
        } catch (\Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw new RuntimeException('Unable to create group conversation: ' . $e->getMessage());
        }
    }

    private function findDirectConversationId(int $userId, int $otherUserId): ?int {
        $sql = "
            SELECT c.conversation_id
            FROM Conversations c
            INNER JOIN ConversationParticipants cp1
                ON cp1.conversation_id = c.conversation_id
               AND cp1.user_id = :userOne
            INNER JOIN ConversationParticipants cp2
                ON cp2.conversation_id = c.conversation_id
               AND cp2.user_id = :userTwo
            WHERE c.conversation_type = 'direct'
            ORDER BY c.last_message_at DESC, c.conversation_id DESC
            LIMIT 1";

        $stmt = $this->conn()->prepare($sql);
        $stmt->bindValue(':userOne', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':userTwo', $otherUserId, PDO::PARAM_INT);
        $stmt->execute();
        $conversationId = $stmt->fetchColumn();

        return $conversationId ? (int)$conversationId : null;
    }

    private function ensureParticipant(int $conversationId, int $userId): void {
        $sql = "
            INSERT INTO ConversationParticipants (conversation_id, user_id, role, is_active)
            VALUES (:conversationId, :userId, 'member', TRUE)
            ON DUPLICATE KEY UPDATE is_active = TRUE, left_at = NULL";

        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([
            ':conversationId' => $conversationId,
            ':userId' => $userId,
        ]);
    }

    public function getMessages(int $conversationId, int $userId, ?int $afterId = null, int $limit = 50): array {
        if (!$this->userCanAccessConversation($conversationId, $userId)) {
            return [];
        }

        $sql = "
            SELECT m.message_id, m.conversation_id, m.sender_id, m.message_type, m.content,
                   m.file_url, m.file_name, m.file_size, m.created_at,
                   u.first_name, u.last_name, u.username, u.profile_picture
            FROM Messages m
            INNER JOIN Users u ON u.user_id = m.sender_id
            WHERE m.conversation_id = :conversationId AND m.is_deleted = FALSE";

        if ($afterId !== null) {
            $sql .= " AND m.message_id > :afterId";
        }

        $sql .= " ORDER BY m.created_at ASC, m.message_id ASC LIMIT :limit";

        $stmt = $this->conn()->prepare($sql);
        $stmt->bindValue(':conversationId', $conversationId, PDO::PARAM_INT);
        if ($afterId !== null) {
            $stmt->bindValue(':afterId', $afterId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(function ($message) use ($userId) {
            return $this->hydrateMessage($message, $userId);
        }, $messages);
    }

    public function createMessage(int $conversationId, int $senderId, string $content, string $messageType = 'text', array $fileMeta = []): array {
        if (!$this->userCanAccessConversation($conversationId, $senderId)) {
            throw new InvalidArgumentException('You are not part of this conversation.');
        }

        $content = trim($content);
        if ($content === '' && empty($fileMeta)) {
            throw new InvalidArgumentException('Message cannot be empty.');
        }

        $sql = "INSERT INTO Messages (conversation_id, sender_id, message_type, content, file_url, file_name, file_size)
                VALUES (:conversationId, :senderId, :type, :content, :fileUrl, :fileName, :fileSize)";

        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([
            ':conversationId' => $conversationId,
            ':senderId' => $senderId,
            ':type' => $messageType,
            ':content' => $content,
            ':fileUrl' => $fileMeta['file_url'] ?? null,
            ':fileName' => $fileMeta['file_name'] ?? null,
            ':fileSize' => $fileMeta['file_size'] ?? null,
        ]);

        $messageId = (int)$this->conn()->lastInsertId();

        $previewText = $this->buildMessagePreview($content, $messageType, $fileMeta);

        $updateSql = "UPDATE Conversations SET last_message_at = NOW(), last_message_text = :text WHERE conversation_id = :conversationId";
        $updateStmt = $this->conn()->prepare($updateSql);
        $updateStmt->execute([
            ':text' => $previewText,
            ':conversationId' => $conversationId,
        ]);

        $this->markMessageAsRead($messageId, $senderId);

        return $this->getMessageById($messageId, $senderId);
    }

    private function buildMessagePreview(string $content, string $messageType, array $fileMeta): string {
        $normalized = trim($content);
        if ($normalized !== '') {
            return $normalized;
        }

        return match ($messageType) {
            'image' => 'Shared an image',
            'video' => 'Shared a video',
            'file' => 'Shared a file',
            default => $fileMeta['file_name'] ?? 'Shared an attachment',
        };
    }

    public function markConversationRead(int $conversationId, int $userId): void {
        $sql = "
            INSERT INTO MessageReadStatus (message_id, user_id, read_at)
            SELECT m.message_id, :userId, NOW()
            FROM Messages m
            LEFT JOIN MessageReadStatus mrs
                ON mrs.message_id = m.message_id AND mrs.user_id = :userId
            WHERE m.conversation_id = :conversationId
              AND m.sender_id != :userId
              AND m.is_deleted = FALSE
              AND mrs.message_id IS NULL";

        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([
            ':userId' => $userId,
            ':conversationId' => $conversationId,
        ]);
    }

    public function userCanAccessConversation(int $conversationId, int $userId): bool {
        $sql = "SELECT 1 FROM ConversationParticipants WHERE conversation_id = :conversationId AND user_id = :userId AND is_active = TRUE";
        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([
            ':conversationId' => $conversationId,
            ':userId' => $userId,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    private function formatConversation(array $row, int $userId): array {
        $displayName = null;
        $displayPicture = null;
        $isOnline = false;

        if ($row['conversation_type'] === 'direct') {
            $peer = $this->getDirectPeer($row['conversation_id'], $userId);
            if ($peer) {
                $displayName = trim(($peer['first_name'] ?? '') . ' ' . ($peer['last_name'] ?? '')) ?: $peer['username'];
                $displayPicture = $peer['profile_picture'];
                $isOnline = $peer['is_online'] ?? false;
            }
        } elseif ($row['conversation_type'] === 'group') {
            $displayName = trim($row['conversation_name']);
            $channel = $this->getGroupChannel($row['conversation_id']);
            if ($channel) {
                $displayPicture = $channel['display_picture'] ?? null;
                // Groups don't have online status
            }
        }

        if (!$displayName) {
            $displayName = 'Conversation #' . $row['conversation_id'];
        }

        $lastMessage = null;
        if (!empty($row['last_message_id'])) {
            $lastMessage = [
                'message_id' => (int)$row['last_message_id'],
                'content' => $row['last_message_content'],
                'message_type' => $row['last_message_type'] ?? null,
                'sender_id' => $row['sender_id'] ? (int)$row['sender_id'] : null,
                'sender_name' => $row['sender_first_name'] || $row['sender_last_name']
                    ? trim(($row['sender_first_name'] ?? '') . ' ' . ($row['sender_last_name'] ?? ''))
                    : null,
                'created_at' => $row['message_created_at'],
            ];
        }

        $previewText = $row['last_message_text'] ?? null;
        if (!$previewText && $lastMessage) {
            $previewText = $lastMessage['content'] ?? null;
        }

        return [
            'conversation_id' => (int)$row['conversation_id'],
            'conversation_type' => $row['conversation_type'],
            'display_name' => $displayName,
            'display_picture' => $displayPicture,
            'avatar' => $displayPicture, // Add alias for compatibility
            'is_online' => $isOnline,
            'last_message_at' => $row['last_message_at'],
            'last_message' => $lastMessage,
            'last_message_preview' => $previewText,
            'last_message_type' => $lastMessage['message_type'] ?? null,
            'unread_count' => $this->getUnreadCount($row['conversation_id'], $userId),
        ];
    }

    private function getDirectPeer(int $conversationId, int $userId): ?array {
        $sql = "
            SELECT u.user_id, u.username, u.first_name, u.last_name, u.profile_picture
            FROM ConversationParticipants cp
            INNER JOIN Users u ON u.user_id = cp.user_id
            WHERE cp.conversation_id = :conversationId AND cp.user_id != :userId
            LIMIT 1";

        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([
            ':conversationId' => $conversationId,
            ':userId' => $userId,
        ]);

        $peer = $stmt->fetch(PDO::FETCH_ASSOC);
        return $peer ?: null;
    }

    private function getGroupChannel(int $conversationId): ?array {
        $sql = "
            SELECT c.channel_id, c.name, c.display_picture
            FROM ConversationParticipants cp
            INNER JOIN Channel c ON c.conversation_id = cp.conversation_id
            WHERE cp.conversation_id = :conversationId
            LIMIT 1";

        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([
            ':conversationId' => $conversationId
        ]);

        $channel = $stmt->fetch(PDO::FETCH_ASSOC);
        return $channel ?: null;
    }

    private function getUnreadCount(int $conversationId, int $userId): int {
        $sql = "
            SELECT COUNT(*)
            FROM Messages m
            LEFT JOIN MessageReadStatus mrs ON m.message_id = mrs.message_id AND mrs.user_id = :userId
            WHERE m.conversation_id = :conversationId
              AND m.sender_id != :userId
              AND m.is_deleted = FALSE
              AND mrs.message_id IS NULL";

        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([
            ':conversationId' => $conversationId,
            ':userId' => $userId,
        ]);

        return (int)$stmt->fetchColumn();
    }

    private function hydrateMessage(array $message, int $userId): array {
        $fullName = trim(($message['first_name'] ?? '') . ' ' . ($message['last_name'] ?? ''));

        return [
            'message_id' => (int)$message['message_id'],
            'conversation_id' => (int)$message['conversation_id'],
            'sender_id' => (int)$message['sender_id'],
            'sender_name' => $fullName ?: $message['username'],
            'sender_avatar' => $message['profile_picture'],
            'message_type' => $message['message_type'],
            'content' => $message['content'],
            'file_url' => $message['file_url'],
            'file_name' => $message['file_name'],
            'file_size' => $message['file_size'],
            'created_at' => $message['created_at'],
            'is_own' => (int)$message['sender_id'] === $userId,
        ];
    }

    private function getMessageById(int $messageId, int $requesterId): array {
        $sql = "
            SELECT m.message_id, m.conversation_id, m.sender_id, m.message_type, m.content,
                   m.file_url, m.file_name, m.file_size, m.created_at,
                   u.first_name, u.last_name, u.username, u.profile_picture
            FROM Messages m
            INNER JOIN Users u ON u.user_id = m.sender_id
            WHERE m.message_id = :messageId";

        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([':messageId' => $messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$message) {
            throw new RuntimeException('Message not found.');
        }

        return $this->hydrateMessage($message, $requesterId);
    }

    private function markMessageAsRead(int $messageId, int $userId): void {
        $sql = "
            INSERT INTO MessageReadStatus (message_id, user_id, read_at)
            VALUES (:messageId, :userId, NOW())
            ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)";

        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([
            ':messageId' => $messageId,
            ':userId' => $userId,
        ]);
    }

    public function getSharedMedia(int $conversationId): array {
        $sql = "
            SELECT message_id, message_type, file_url, file_name, file_size, created_at
            FROM Messages
            WHERE conversation_id = :conversationId
              AND is_deleted = FALSE
              AND message_type IN ('image', 'video', 'file')
              AND file_url IS NOT NULL
            ORDER BY created_at DESC
        ";

        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([':conversationId' => $conversationId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $photos = [];
        $videos = [];
        $documents = [];

        foreach ($items as $item) {
            switch ($item['message_type']) {
                case 'image':
                    $photos[] = $item;
                    break;
                case 'video':
                    $videos[] = $item;
                    break;
                case 'file':
                    $documents[] = $item;
                    break;
            }
        }

        return [
            'photos' => $photos,
            'videos' => $videos,
            'documents' => $documents,
        ];
    }

    public function getFileStructure(int $conversationId, $folderId): array {
        // Get folders
        $folderSql = "
            SELECT folder_id AS id, folder_name AS name, created_by, created_at
            FROM ChatFolders
            WHERE conversation_id = :conversationId
              AND " . ($folderId === 'root' || $folderId === null ? "parent_folder_id IS NULL" : "parent_folder_id = :folderId") . "
            ORDER BY folder_name ASC
        ";
        $folderStmt = $this->conn()->prepare($folderSql);
        $folderStmt->bindValue(':conversationId', $conversationId, PDO::PARAM_INT);
        if ($folderId !== 'root' && $folderId !== null) {
            $folderStmt->bindValue(':folderId', $folderId, PDO::PARAM_INT);
        }
        $folderStmt->execute();
        $folders = $folderStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get files
        $fileSql = "
            SELECT file_id AS id, file_name, file_url, file_size, uploaded_by, uploaded_at
            FROM ChatFiles
            WHERE conversation_id = :conversationId
              AND " . ($folderId === 'root' || $folderId === null ? "folder_id IS NULL" : "folder_id = :folderId") . "
            ORDER BY uploaded_at DESC
        ";
        $fileStmt = $this->conn()->prepare($fileSql);
        $fileStmt->bindValue(':conversationId', $conversationId, PDO::PARAM_INT);
        if ($folderId !== 'root' && $folderId !== null) {
            $fileStmt->bindValue(':folderId', $folderId, PDO::PARAM_INT);
        }
        $fileStmt->execute();
        $files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'folders' => $folders,
            'files' => $files,
        ];
    }

    public function createChatFolder(int $conversationId, ?int $parentFolderId, string $folderName, int $userId): int {
        $sql = "
            INSERT INTO ChatFolders (conversation_id, parent_folder_id, folder_name, created_by)
            VALUES (:conversationId, :parentFolderId, :folderName, :userId)
        ";
        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([
            ':conversationId' => $conversationId,
            ':parentFolderId' => $parentFolderId,
            ':folderName' => $folderName,
            ':userId' => $userId,
        ]);
        return (int)$this->conn()->lastInsertId();
    }

    public function saveChatFile(int $conversationId, ?int $folderId, string $fileName, string $fileUrl, int $fileSize, int $userId): int {
        $sql = "
            INSERT INTO ChatFiles (conversation_id, folder_id, file_name, file_url, file_size, uploaded_by)
            VALUES (:conversationId, :folderId, :fileName, :fileUrl, :fileSize, :userId)
        ";
        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([
            ':conversationId' => $conversationId,
            ':folderId' => $folderId,
            ':fileName' => $fileName,
            ':fileUrl' => $fileUrl,
            ':fileSize' => $fileSize,
            ':userId' => $userId,
        ]);
        return (int)$this->conn()->lastInsertId();
    }

    public function deleteChatFolder(int $folderId, int $userId): void {
        $sql = "DELETE FROM ChatFolders WHERE folder_id = :folderId";
        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([':folderId' => $folderId]);
    }

    public function deleteChatFile(int $fileId, int $userId): void {
        $sql = "SELECT file_url FROM ChatFiles WHERE file_id = :fileId";
        $stmt = $this->conn()->prepare($sql);
        $stmt->execute([':fileId' => $fileId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        $deleteSql = "DELETE FROM ChatFiles WHERE file_id = :fileId";
        $deleteStmt = $this->conn()->prepare($deleteSql);
        $deleteStmt->execute([':fileId' => $fileId]);

        if ($file && !empty($file['file_url'])) {
            $filePath = __DIR__ . '/../../public/' . ltrim($file['file_url'], '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }
}