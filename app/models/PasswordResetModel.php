<?php
require_once __DIR__ . '/../core/Database.php';

class PasswordResetModel {
    private PDO $db;
    private bool $tableReady = false;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    private function ensureTableExists(): void {
        if ($this->tableReady) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS PasswordResetCodes (
                    reset_id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    code_hash VARCHAR(255) NOT NULL,
                    attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
                    requested_ip VARCHAR(45) NULL,
                    expires_at DATETIME NOT NULL,
                    used_at DATETIME NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
                    INDEX idx_password_reset_user_active (user_id, used_at, expires_at),
                    INDEX idx_password_reset_expiry (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->exec($sql);
        $this->tableReady = true;
    }

    public function createCode(int $userId, string $codeHash, string $expiresAt, ?string $requestedIp = null): bool {
        $this->ensureTableExists();

        $sql = "INSERT INTO PasswordResetCodes (user_id, code_hash, expires_at, requested_ip)
                VALUES (:user_id, :code_hash, :expires_at, :requested_ip)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':code_hash' => $codeHash,
            ':expires_at' => $expiresAt,
            ':requested_ip' => $requestedIp,
        ]);
    }

    public function getLatestActiveCodeByUserId(int $userId): ?array {
        $this->ensureTableExists();

        $sql = "SELECT reset_id, user_id, code_hash, attempt_count, expires_at, used_at, created_at
                FROM PasswordResetCodes
                WHERE user_id = :user_id
                  AND used_at IS NULL
                  AND expires_at > NOW()
                ORDER BY created_at DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function incrementAttempts(int $resetId): bool {
        $this->ensureTableExists();

        $sql = "UPDATE PasswordResetCodes
                SET attempt_count = attempt_count + 1
                WHERE reset_id = :reset_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':reset_id' => $resetId]);
    }

    public function markCodeAsUsed(int $resetId): bool {
        $this->ensureTableExists();

        $sql = "UPDATE PasswordResetCodes
                SET used_at = NOW()
                WHERE reset_id = :reset_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':reset_id' => $resetId]);
    }

    public function invalidateActiveCodes(int $userId): bool {
        $this->ensureTableExists();

        $sql = "UPDATE PasswordResetCodes
                SET used_at = NOW()
                WHERE user_id = :user_id
                  AND used_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }

    public function deleteExpiredCodes(): bool {
        $this->ensureTableExists();

        $sql = "DELETE FROM PasswordResetCodes
                WHERE expires_at <= NOW()";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute();
    }
}
