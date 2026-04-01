<?php

class BinModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    private function resolveFileUrl(array $data): string
    {
        return (string)($data['file_url'] ?? $data['file_path'] ?? '');
    }

    public function getBinsWithFiles(int $groupId): array
    {
        $stmt = $this->dbInstance->prepare(
            "SELECT bin_id, name, group_id, created_by, created_at
             FROM Bin
             WHERE group_id = ?
             ORDER BY created_at DESC, bin_id DESC"
        );
        $stmt->execute([$groupId]);
        $bins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($bins)) {
            return [];
        }

        $binIds = array_map(static fn($bin) => (int) $bin['bin_id'], $bins);
        $placeholders = implode(',', array_fill(0, count($binIds), '?'));

        $fileStmt = $this->dbInstance->prepare(
            "SELECT media_id, bin_id, file_name, file_type, file_url AS file_path, file_size, added_at, added_by
             FROM BinMedia
             WHERE bin_id IN ($placeholders)
             ORDER BY added_at DESC, media_id DESC"
        );
        $fileStmt->execute($binIds);
        $files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

        $filesByBin = [];
        foreach ($files as $file) {
            $binId = (int) $file['bin_id'];
            if (!isset($filesByBin[$binId])) {
                $filesByBin[$binId] = [];
            }
            $filesByBin[$binId][] = $file;
        }

        foreach ($bins as &$bin) {
            $id = (int) $bin['bin_id'];
            $bin['files'] = $filesByBin[$id] ?? [];
            $bin['file_count'] = count($bin['files']);
        }

        return $bins;
    }

    public function getBinById(int $binId): ?array
    {
        $stmt = $this->dbInstance->prepare(
            "SELECT bin_id, name, group_id, created_by, created_at
             FROM Bin
             WHERE bin_id = ?
             LIMIT 1"
        );
        $stmt->execute([$binId]);
        $bin = $stmt->fetch(PDO::FETCH_ASSOC);

        return $bin ?: null;
    }

    public function createBin(array $data, int $userId): ?array
    {
        $stmt = $this->dbInstance->prepare(
            "INSERT INTO Bin (group_id, created_by, name) VALUES (?, ?, ?)"
        );
        $ok = $stmt->execute([
            (int) $data['group_id'],
            $userId,
            trim((string) $data['name'])
        ]);

        if (!$ok) {
            return null;
        }

        $binId = (int) $this->dbInstance->lastInsertId();
        return $this->getBinById($binId);
    }

    public function updateBin(array $data, int $binId): bool
    {
        $stmt = $this->dbInstance->prepare(
            "UPDATE Bin
             SET name = ?
             WHERE bin_id = ? AND group_id = ?"
        );

        return $stmt->execute([
            trim((string) $data['name']),
            $binId,
            (int) $data['group_id']
        ]);
    }

    public function deleteBin(int $binId, int $groupId): bool
    {
        $stmt = $this->dbInstance->prepare(
            "DELETE FROM Bin WHERE bin_id = ? AND group_id = ?"
        );

        return $stmt->execute([$binId, $groupId]);
    }

    public function addMedia(array $data, int $userId): ?array
    {
        try {
            $fileUrl = $this->resolveFileUrl($data);
            $fileType = (string)($data['bin_file_type'] ?? $data['media_file_type'] ?? 'other');
            $fileSize = (int)($data['file_size'] ?? 0);

            $sql = "INSERT INTO BinMedia (bin_id, file_name, file_type, file_url, file_size, added_by)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->dbInstance->prepare($sql);
            $stmt->execute([
                (int)$data['bin_id'],
                (string)$data['file_name'],
                $fileType,
                $fileUrl,
                $fileSize,
                $userId,
            ]);
            $mediaId = (int)$this->dbInstance->lastInsertId();

            return $this->getMediaById($mediaId);
        } catch (Throwable $e) {
            error_log('addMedia failed: ' . $e->getMessage());
            return null;
        }
    }

    public function editMedia(array $data, int $mediaId): bool
    {
        try {
            $fileName = trim((string) $data['file_name']);
            $fileSize = (int) ($data['file_size'] ?? 0);
            $binSql = "UPDATE BinMedia
                       SET file_name = ?";
            $binParams = [$fileName];

            if (isset($data['bin_file_type']) || isset($data['media_file_type'])) {
                $binSql .= ", file_type = ?";
                $binParams[] = (string)($data['bin_file_type'] ?? $data['media_file_type']);
            }
            if (isset($data['file_url']) || isset($data['file_path'])) {
                $binSql .= ", file_url = ?";
                $binParams[] = $this->resolveFileUrl($data);
            }
            if (isset($data['file_size'])) {
                $binSql .= ", file_size = ?";
                $binParams[] = $fileSize;
            }

            $binSql .= " WHERE media_id = ?";
            $binParams[] = $mediaId;

            $binStmt = $this->dbInstance->prepare($binSql);
            $binStmt->execute($binParams);
            return true;
        } catch (Throwable $e) {
            error_log('editMedia failed: ' . $e->getMessage());
            return false;
        }
    }

    public function removeMedia(int $mediaId): bool
    {
        try {
            $stmt = $this->dbInstance->prepare("DELETE FROM BinMedia WHERE media_id = ?");
            $stmt->execute([$mediaId]);
            return true;
        } catch (Throwable $e) {
            error_log('removeMedia failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getMediaById(int $mediaId): ?array
    {
        $stmt = $this->dbInstance->prepare(
            "SELECT media_id, bin_id, file_name, file_type, file_url AS file_path, file_size, added_at, added_by
             FROM BinMedia
             WHERE media_id = ?
             LIMIT 1"
        );
        $stmt->execute([$mediaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function checkUniqueBinName(int $groupId, string $name, ?int $excludeBinId = null): bool
    {
        $sql = "SELECT 1 FROM Bin WHERE group_id = ? AND name = ?";
        $params = [$groupId, trim($name)];

        if ($excludeBinId !== null) {
            $sql .= " AND bin_id != ?";
            $params[] = $excludeBinId;
        }

        $stmt = $this->dbInstance->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function checkUniqueFileName(int $binId, string $fileName, ?int $excludeMediaId = null): bool
    {
        $sql = "SELECT 1 FROM BinMedia WHERE bin_id = ? AND file_name = ?";
        $params = [$binId, trim($fileName)];

        if ($excludeMediaId !== null) {
            $sql .= " AND media_id != ?";
            $params[] = $excludeMediaId;
        }

        $stmt = $this->dbInstance->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }
}
