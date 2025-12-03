<?php
require_once __DIR__ . '/../core/Database.php';

class UserSettingsModel
{
    private Database $db;
    private string $table = 'UserSettings';

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getDefaults(): array
    {
        return [
            'profile_visibility' => 'friends',
            'post_visibility' => 'friends',
            'friend_request_visibility' => 'everyone',
            'show_email' => 0,
            'show_phone' => 0,
            'email_comments' => 1,
            'email_likes' => 1,
            'email_friend_requests' => 1,
            'email_messages' => 1,
            'email_group_activity' => 1,
            'push_enabled' => 1,
            'theme' => 'light',
            'font_size' => 'medium',
        ];
    }

    public function getByUserId(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            $this->createDefault($userId);
            return $this->getDefaults();
        }

        return array_merge($this->getDefaults(), $settings);
    }

    private function createDefault(int $userId): bool
    {
        $sql = "INSERT INTO {$this->table} (user_id) VALUES (:user_id)";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }

    public function updateSettings(int $userId, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $columns = [];
        $params = [':user_id' => $userId];

        foreach ($data as $column => $value) {
            $columns[] = "$column = :$column";
            $params[":" . $column] = $value;
        }

        $setClause = implode(', ', $columns);
        $sql = "UPDATE {$this->table} SET {$setClause}, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id";

        $stmt = $this->db->getConnection()->prepare($sql);
        $updated = $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            $this->createDefault($userId);
            $stmt = $this->db->getConnection()->prepare($sql);
            $updated = $stmt->execute($params);
        }

        return $updated;
    }
}
