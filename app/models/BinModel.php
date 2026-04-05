<?php

class BinModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
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
            "SELECT media_id, bin_id, file_name, file_type, file_path, file_size, added_at, added_by
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
        $this->dbInstance->beginTransaction();

        try {
            $mediaStmt = $this->dbInstance->prepare(
                "INSERT INTO MediaFile
                    (group_id, uploader_id, file_name, file_type, file_url, file_size, status, requires_admin_approval)
                 VALUES (?, ?, ?, ?, ?, ?, 'approved', 0)"
            );
            $mediaStmt->execute([
                (int) $data['group_id'],
                $userId,
                $data['file_name'],
                $data['media_file_type'],
                $data['file_path'],
                (int) ($data['file_size'] ?? 0),
            ]);

            $mediaId = (int) $this->dbInstance->lastInsertId();

            $binMediaStmt = $this->dbInstance->prepare(
                "INSERT INTO BinMedia
                    (media_id, bin_id, file_name, file_type, file_path, file_size, added_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $binMediaStmt->execute([
                $mediaId,
                (int) $data['bin_id'],
                $data['file_name'],
                $data['bin_file_type'],
                $data['file_path'],
                (int) ($data['file_size'] ?? 0),
                $userId,
            ]);

            $this->dbInstance->commit();
            return $this->getMediaById($mediaId);
        } catch (Throwable $e) {
            $this->dbInstance->rollBack();
            error_log('addMedia failed: ' . $e->getMessage());
            return null;
        }
    }

    public function editMedia(array $data, int $mediaId): bool
    {
        $this->dbInstance->beginTransaction();

        try {
            $fileName = trim((string) $data['file_name']);
            $fileSize = (int) ($data['file_size'] ?? 0);

            $mediaSql = "UPDATE MediaFile
                         SET file_name = ?";
            $mediaParams = [$fileName];

            if (isset($data['media_file_type'])) {
                $mediaSql .= ", file_type = ?";
                $mediaParams[] = $data['media_file_type'];
            }
            if (isset($data['file_path'])) {
                $mediaSql .= ", file_url = ?";
                $mediaParams[] = $data['file_path'];
            }
            if (isset($data['file_size'])) {
                $mediaSql .= ", file_size = ?";
                $mediaParams[] = $fileSize;
            }

            $mediaSql .= " WHERE media_id = ?";
            $mediaParams[] = $mediaId;

            $mediaStmt = $this->dbInstance->prepare($mediaSql);
            $mediaStmt->execute($mediaParams);

            $binSql = "UPDATE BinMedia
                       SET file_name = ?";
            $binParams = [$fileName];

            if (isset($data['bin_file_type'])) {
                $binSql .= ", file_type = ?";
                $binParams[] = $data['bin_file_type'];
            }
            if (isset($data['file_path'])) {
                $binSql .= ", file_path = ?";
                $binParams[] = $data['file_path'];
            }
            if (isset($data['file_size'])) {
                $binSql .= ", file_size = ?";
                $binParams[] = $fileSize;
            }

            $binSql .= " WHERE media_id = ?";
            $binParams[] = $mediaId;

            $binStmt = $this->dbInstance->prepare($binSql);
            $binStmt->execute($binParams);

            $this->dbInstance->commit();
            return true;
        } catch (Throwable $e) {
            $this->dbInstance->rollBack();
            error_log('editMedia failed: ' . $e->getMessage());
            return false;
        }
    }

    public function removeMedia(int $mediaId): bool
    {
        $this->dbInstance->beginTransaction();

        try {
            $binStmt = $this->dbInstance->prepare("DELETE FROM BinMedia WHERE media_id = ?");
            $binStmt->execute([$mediaId]);

            $mediaStmt = $this->dbInstance->prepare("DELETE FROM MediaFile WHERE media_id = ?");
            $mediaStmt->execute([$mediaId]);

            $this->dbInstance->commit();
            return true;
        } catch (Throwable $e) {
            $this->dbInstance->rollBack();
            error_log('removeMedia failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getMediaById(int $mediaId): ?array
    {
        $stmt = $this->dbInstance->prepare(
            "SELECT media_id, bin_id, file_name, file_type, file_path, file_size, added_at, added_by
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
