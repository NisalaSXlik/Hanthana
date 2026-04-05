<?php

class AcedemicDashboardModel extends BaseModel
{
    private bool $tablesEnsured = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensureResourceTables();
    }

    private function ensureResourceTables(): void
    {
        if ($this->tablesEnsured) {
            return;
        }

        $this->dbInstance->exec(
            "CREATE TABLE IF NOT EXISTS BinMediaDownload (
                download_id INT AUTO_INCREMENT PRIMARY KEY,
                media_id INT NOT NULL,
                user_id INT NULL,
                downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_binmedia_download_media (media_id),
                INDEX idx_binmedia_download_user (user_id),
                CONSTRAINT fk_binmedia_download_media FOREIGN KEY (media_id) REFERENCES BinMedia(media_id) ON DELETE CASCADE,
                CONSTRAINT fk_binmedia_download_user FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->dbInstance->exec(
            "CREATE TABLE IF NOT EXISTS BinMediaSave (
                save_id INT AUTO_INCREMENT PRIMARY KEY,
                media_id INT NOT NULL,
                user_id INT NOT NULL,
                saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_binmedia_save (media_id, user_id),
                INDEX idx_binmedia_save_user (user_id),
                CONSTRAINT fk_binmedia_save_media FOREIGN KEY (media_id) REFERENCES BinMedia(media_id) ON DELETE CASCADE,
                CONSTRAINT fk_binmedia_save_user FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->tablesEnsured = true;
    }

    public function getGroupsByLatestUpload(int $userId): array
    {
        $sql = "SELECT
                    g.group_id,
                    g.name AS group_name,
                    COALESCE(NULLIF(g.focus, ''), NULLIF(g.tag, ''), 'General') AS group_category,
                    g.display_picture,
                    COUNT(DISTINCT b.bin_id) AS bin_count,
                    COUNT(bm.media_id) AS file_count,
                    MAX(bm.added_at) AS latest_upload_at
                FROM GroupMember gm
                INNER JOIN GroupsTable g ON g.group_id = gm.group_id
                LEFT JOIN Bin b ON b.group_id = g.group_id
                LEFT JOIN BinMedia bm ON bm.bin_id = b.bin_id
                WHERE gm.user_id = ?
                  AND gm.status = 'active'
                  AND COALESCE(g.is_active, 1) = 1
                GROUP BY g.group_id, g.name, g.focus, g.tag, g.display_picture
                ORDER BY
                    CASE WHEN MAX(bm.added_at) IS NULL THEN 1 ELSE 0 END,
                    MAX(bm.added_at) DESC,
                    g.name ASC";

        $stmt = $this->dbInstance->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getBinsForGroup(int $userId, int $groupId): array
    {
        if (!$this->userCanAccessGroup($userId, $groupId)) {
            return [];
        }

        $sql = "SELECT
                    b.bin_id,
                    b.name AS bin_name,
                    b.group_id,
                    COUNT(bm.media_id) AS file_count,
                    MAX(bm.added_at) AS latest_upload_at
                FROM Bin b
                LEFT JOIN BinMedia bm ON bm.bin_id = b.bin_id
                WHERE b.group_id = ?
                GROUP BY b.bin_id, b.name, b.group_id
                ORDER BY
                    CASE WHEN MAX(bm.added_at) IS NULL THEN 1 ELSE 0 END,
                    MAX(bm.added_at) DESC,
                    b.name ASC";

        $stmt = $this->dbInstance->prepare($sql);
        $stmt->execute([$groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getFilesForBin(int $userId, int $groupId, int $binId, string $tab): array
    {
        if (!$this->userCanAccessGroup($userId, $groupId)) {
            return [];
        }

        $params = [$userId, $groupId, $binId];

        $baseSql = "SELECT
                        bm.media_id,
                        bm.bin_id,
                        bm.file_name,
                        bm.file_type,
                        bm.file_path,
                        bm.file_size,
                        bm.added_at,
                        COALESCE(dl.download_count, 0) AS download_count,
                        CASE WHEN ms.media_id IS NULL THEN 0 ELSE 1 END AS is_saved
                    FROM BinMedia bm
                    INNER JOIN Bin b ON b.bin_id = bm.bin_id
                    LEFT JOIN (
                        SELECT media_id, COUNT(*) AS download_count
                        FROM BinMediaDownload
                        GROUP BY media_id
                    ) dl ON dl.media_id = bm.media_id
                    LEFT JOIN BinMediaSave ms ON ms.media_id = bm.media_id AND ms.user_id = ?
                    WHERE b.group_id = ?
                      AND bm.bin_id = ?";

        if ($tab === 'my_saves') {
            $baseSql .= " AND ms.media_id IS NOT NULL";
        }

        if ($tab === 'top_downloads') {
            $baseSql .= " ORDER BY COALESCE(dl.download_count, 0) DESC, bm.added_at DESC, bm.media_id DESC";
        } else {
            $baseSql .= " ORDER BY bm.added_at DESC, bm.media_id DESC";
        }

        $stmt = $this->dbInstance->prepare($baseSql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getRecentFiles(int $userId, int $limit = 30): array
    {
        $limit = max(1, min($limit, 100));

        $sql = "SELECT
                    bm.media_id,
                    bm.bin_id,
                    bm.file_name,
                    bm.file_type,
                    bm.file_path,
                    bm.file_size,
                    bm.added_at,
                    b.name AS bin_name,
                    g.group_id,
                    g.name AS group_name,
                    COALESCE(dl.download_count, 0) AS download_count,
                    CASE WHEN ms.media_id IS NULL THEN 0 ELSE 1 END AS is_saved
                FROM GroupMember gm
                INNER JOIN GroupsTable g ON g.group_id = gm.group_id
                INNER JOIN Bin b ON b.group_id = g.group_id
                INNER JOIN BinMedia bm ON bm.bin_id = b.bin_id
                LEFT JOIN (
                    SELECT media_id, COUNT(*) AS download_count
                    FROM BinMediaDownload
                    GROUP BY media_id
                ) dl ON dl.media_id = bm.media_id
                LEFT JOIN BinMediaSave ms ON ms.media_id = bm.media_id AND ms.user_id = ?
                WHERE gm.user_id = ?
                  AND gm.status = 'active'
                  AND COALESCE(g.is_active, 1) = 1
                ORDER BY bm.added_at DESC, bm.media_id DESC
                LIMIT {$limit}";

        $stmt = $this->dbInstance->prepare($sql);
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTopDownloadedFiles(int $userId, int $limit = 30): array
    {
        $limit = max(1, min($limit, 100));

        $sql = "SELECT
                    bm.media_id,
                    bm.bin_id,
                    bm.file_name,
                    bm.file_type,
                    bm.file_path,
                    bm.file_size,
                    bm.added_at,
                    b.name AS bin_name,
                    g.group_id,
                    g.name AS group_name,
                    dl.download_count AS download_count,
                    CASE WHEN ms.media_id IS NULL THEN 0 ELSE 1 END AS is_saved
                FROM GroupMember gm
                INNER JOIN GroupsTable g ON g.group_id = gm.group_id
                INNER JOIN Bin b ON b.group_id = g.group_id
                INNER JOIN BinMedia bm ON bm.bin_id = b.bin_id
                INNER JOIN (
                    SELECT media_id, COUNT(*) AS download_count
                    FROM BinMediaDownload
                    WHERE downloaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY media_id
                ) dl ON dl.media_id = bm.media_id
                LEFT JOIN BinMediaSave ms ON ms.media_id = bm.media_id AND ms.user_id = ?
                WHERE gm.user_id = ?
                  AND gm.status = 'active'
                  AND COALESCE(g.is_active, 1) = 1
                ORDER BY dl.download_count DESC, bm.added_at DESC, bm.media_id DESC
                LIMIT {$limit}";

        $stmt = $this->dbInstance->prepare($sql);
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getSavedFiles(int $userId, int $limit = 30): array
    {
        $limit = max(1, min($limit, 100));

        $sql = "SELECT
                    bm.media_id,
                    bm.bin_id,
                    bm.file_name,
                    bm.file_type,
                    bm.file_path,
                    bm.file_size,
                    bm.added_at,
                    b.name AS bin_name,
                    g.group_id,
                    g.name AS group_name,
                    COALESCE(dl.download_count, 0) AS download_count,
                    1 AS is_saved
                FROM BinMediaSave ms
                INNER JOIN BinMedia bm ON bm.media_id = ms.media_id
                INNER JOIN Bin b ON b.bin_id = bm.bin_id
                INNER JOIN GroupsTable g ON g.group_id = b.group_id
                INNER JOIN GroupMember gm ON gm.group_id = g.group_id AND gm.user_id = ? AND gm.status = 'active'
                LEFT JOIN (
                    SELECT media_id, COUNT(*) AS download_count
                    FROM BinMediaDownload
                    GROUP BY media_id
                ) dl ON dl.media_id = bm.media_id
                WHERE ms.user_id = ?
                  AND COALESCE(g.is_active, 1) = 1
                ORDER BY ms.saved_at DESC, bm.media_id DESC
                LIMIT {$limit}";

        $stmt = $this->dbInstance->prepare($sql);
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function toggleFileSave(int $userId, int $mediaId): ?bool
    {
        if (!$this->userCanAccessFile($userId, $mediaId)) {
            return null;
        }

        $checkStmt = $this->dbInstance->prepare("SELECT save_id FROM BinMediaSave WHERE user_id = ? AND media_id = ? LIMIT 1");
        $checkStmt->execute([$userId, $mediaId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $deleteStmt = $this->dbInstance->prepare("DELETE FROM BinMediaSave WHERE user_id = ? AND media_id = ?");
            $deleteStmt->execute([$userId, $mediaId]);
            return false;
        }

        $insertStmt = $this->dbInstance->prepare("INSERT INTO BinMediaSave (media_id, user_id) VALUES (?, ?)");
        $insertStmt->execute([$mediaId, $userId]);
        return true;
    }

    public function recordDownload(int $userId, int $mediaId): ?array
    {
        $file = $this->getFileByIdForUser($userId, $mediaId);
        if (!$file) {
            return null;
        }

        $insertStmt = $this->dbInstance->prepare("INSERT INTO BinMediaDownload (media_id, user_id) VALUES (?, ?)");
        $insertStmt->execute([$mediaId, $userId]);

        $countStmt = $this->dbInstance->prepare("SELECT COUNT(*) AS total FROM BinMediaDownload WHERE media_id = ?");
        $countStmt->execute([$mediaId]);
        $row = $countStmt->fetch(PDO::FETCH_ASSOC);
        $downloadCount = (int)($row['total'] ?? 0);

        return [
            'file_path' => (string)($file['file_path'] ?? ''),
            'download_count' => $downloadCount,
        ];
    }

    private function getFileByIdForUser(int $userId, int $mediaId): ?array
    {
        $sql = "SELECT bm.media_id, bm.file_path
                FROM BinMedia bm
                INNER JOIN Bin b ON b.bin_id = bm.bin_id
                INNER JOIN GroupMember gm ON gm.group_id = b.group_id
                INNER JOIN GroupsTable g ON g.group_id = gm.group_id
                WHERE bm.media_id = ?
                  AND gm.user_id = ?
                  AND gm.status = 'active'
                  AND COALESCE(g.is_active, 1) = 1
                LIMIT 1";

        $stmt = $this->dbInstance->prepare($sql);
        $stmt->execute([$mediaId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function userCanAccessGroup(int $userId, int $groupId): bool
    {
        $stmt = $this->dbInstance->prepare(
            "SELECT 1
             FROM GroupMember gm
             INNER JOIN GroupsTable g ON g.group_id = gm.group_id
             WHERE gm.user_id = ?
               AND gm.group_id = ?
               AND gm.status = 'active'
               AND COALESCE(g.is_active, 1) = 1
             LIMIT 1"
        );
        $stmt->execute([$userId, $groupId]);
        return (bool)$stmt->fetchColumn();
    }

    private function userCanAccessFile(int $userId, int $mediaId): bool
    {
        return $this->getFileByIdForUser($userId, $mediaId) !== null;
    }
}
