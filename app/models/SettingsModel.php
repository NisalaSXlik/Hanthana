<?php
require_once __DIR__ . '/../core/Database.php';

class SettingsModel {
    private $db;
    private $table = 'UserSettings';

    public function __construct() {
        $this->db = new Database();
    }

    public function getUserSettings($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Return defaults if no settings found
        if (!$settings) {
            return $this->getDefaultSettings();
        }
        
        return array_merge($this->getDefaultSettings(), $settings);
    }

    private function getDefaultSettings() {
        return [
            'profile_visibility' => 'everyone',
            'post_visibility' => 'everyone', 
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
            'font_size' => 'medium'
        ];
    }

    public function updatePrivacySettings($userId, $data) {
        $allowedFields = [
            'profile_visibility', 'post_visibility', 'friend_request_visibility', 
            'show_email', 'show_phone'
        ];
        
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $userId;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE user_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        // If no rows affected, insert new settings
        if ($stmt->execute($params) && $stmt->rowCount() === 0) {
            return $this->createUserSettings($userId, $data);
        }
        
        return true;
    }

    private function createUserSettings($userId, $data) {
        $sql = "INSERT INTO {$this->table} (user_id, profile_visibility, post_visibility, friend_request_visibility, show_email, show_phone) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            $userId,
            $data['profile_visibility'] ?? 'everyone',
            $data['post_visibility'] ?? 'everyone',
            $data['friend_request_visibility'] ?? 'everyone',
            $data['show_email'] ?? 0,
            $data['show_phone'] ?? 0
        ]);
    }

    public function updateNotificationSettings($userId, $data) {
        $allowedFields = [
            'email_comments', 'email_likes', 'email_friend_requests', 
            'email_messages', 'email_group_activity', 'push_enabled'
        ];
        
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $userId;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE user_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        if ($stmt->execute($params) && $stmt->rowCount() === 0) {
            return $this->createDefaultUserSettings($userId);
        }
        
        return true;
    }

    public function updateAppearanceSettings($userId, $data) {
        $allowedFields = ['theme', 'font_size'];
        
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $userId;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE user_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        if ($stmt->execute($params) && $stmt->rowCount() === 0) {
            return $this->createDefaultUserSettings($userId);
        }
        
        return true;
    }

    private function createDefaultUserSettings($userId) {
        $defaults = $this->getDefaultSettings();
        $sql = "INSERT INTO {$this->table} (user_id, profile_visibility, post_visibility, friend_request_visibility, show_email, show_phone) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            $userId,
            $defaults['profile_visibility'],
            $defaults['post_visibility'],
            $defaults['friend_request_visibility'],
            $defaults['show_email'],
            $defaults['show_phone']
        ]);
    }

    // Privacy check methods
    public function canViewProfile($profileUserId, $viewerId) {
        if ($profileUserId == $viewerId) return true;
        
        $settings = $this->getUserSettings($profileUserId);
        
        switch ($settings['profile_visibility']) {
            case 'only_me':
                return false;
            case 'friends_only':
                return $this->areFriends($profileUserId, $viewerId);
            case 'everyone':
            default:
                return true;
        }
    }

    public function canViewPosts($profileUserId, $viewerId) {
        if ($profileUserId == $viewerId) return true;
        
        $settings = $this->getUserSettings($profileUserId);
        
        switch ($settings['post_visibility']) {
            case 'only_me':
                return false;
            case 'friends_only':
                return $this->areFriends($profileUserId, $viewerId);
            case 'everyone':
            default:
                return true;
        }
    }

    public function canSendFriendRequest($profileUserId, $viewerId) {
        if ($profileUserId == $viewerId) return false;
        
        $settings = $this->getUserSettings($profileUserId);
        
        switch ($settings['friend_request_visibility']) {
            case 'no_one':
                return false;
            case 'friends_of_friends':
                return $this->haveMutualFriends($profileUserId, $viewerId);
            case 'everyone':
            default:
                return true;
        }
    }

    public function shouldShowEmail($profileUserId, $viewerId) {
        if ($profileUserId == $viewerId) return true;
        
        $settings = $this->getUserSettings($profileUserId);
        return $settings['show_email'] == 1 && $this->canViewProfile($profileUserId, $viewerId);
    }

    public function shouldShowPhone($profileUserId, $viewerId) {
        if ($profileUserId == $viewerId) return true;
        
        $settings = $this->getUserSettings($profileUserId);
        return $settings['show_phone'] == 1 && $this->canViewProfile($profileUserId, $viewerId);
    }

    // Helper methods
    private function areFriends($userId1, $userId2) {
        $sql = "SELECT * FROM Friends 
                WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) 
                AND status = 'accepted' 
                LIMIT 1";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
        return $stmt->fetch() !== false;
    }


    public function getSettingsSummary() {
        $pdo = $this->db->getConnection();

        $columnsStmt = $pdo->prepare("SHOW COLUMNS FROM {$this->table}");
        $columnsStmt->execute();
        $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $hasTheme = in_array('theme', $columns, true);
        $hasPush = in_array('push_enabled', $columns, true);

        $selectParts = [
            "COUNT(*) AS total_rows",
            "SUM(CASE WHEN profile_visibility = 'everyone' THEN 1 ELSE 0 END) AS public_profiles",
            "SUM(CASE WHEN profile_visibility = 'friends' THEN 1 ELSE 0 END) AS friends_only_profiles",
            "SUM(CASE WHEN profile_visibility = 'private' THEN 1 ELSE 0 END) AS private_profiles"
        ];

        if ($hasPush) {
            $selectParts[] = "SUM(CASE WHEN push_enabled = 1 THEN 1 ELSE 0 END) AS push_enabled_users";
        }

        if ($hasTheme) {
            $selectParts[] = "SUM(CASE WHEN theme = 'light' THEN 1 ELSE 0 END) AS light_theme";
            $selectParts[] = "SUM(CASE WHEN theme = 'dark' THEN 1 ELSE 0 END) AS dark_theme";
            $selectParts[] = "SUM(CASE WHEN theme = 'auto' THEN 1 ELSE 0 END) AS auto_theme";
        }

        $sql = 'SELECT ' . implode(",\n                    ", $selectParts) . " FROM {$this->table}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalRows = (int)($row['total_rows'] ?? 0);
        $total = max(1, $totalRows);
        $percent = function($count) use ($total) {
            return round(($count / $total) * 100);
        };

        $summary = [
            'total_rows' => $totalRows,
            'public_profiles' => (int)($row['public_profiles'] ?? 0),
            'friends_only_profiles' => (int)($row['friends_only_profiles'] ?? 0),
            'private_profiles' => (int)($row['private_profiles'] ?? 0),
            'push_enabled_users' => $hasPush ? (int)($row['push_enabled_users'] ?? 0) : 0,
            'light_theme' => 0,
            'dark_theme' => 0,
            'auto_theme' => 0,
            'has_theme' => $hasTheme,
            'has_push' => $hasPush,
            'percent' => [
                'public_profiles' => $percent((int)($row['public_profiles'] ?? 0)),
                'friends_only_profiles' => $percent((int)($row['friends_only_profiles'] ?? 0)),
                'private_profiles' => $percent((int)($row['private_profiles'] ?? 0)),
                'light_theme' => 0,
                'dark_theme' => 0,
                'auto_theme' => 0,
            ]
        ];

        if ($hasTheme) {
            $summary['light_theme'] = (int)($row['light_theme'] ?? 0);
            $summary['dark_theme'] = (int)($row['dark_theme'] ?? 0);
            $summary['auto_theme'] = (int)($row['auto_theme'] ?? 0);
            $summary['percent']['light_theme'] = $percent($summary['light_theme']);
            $summary['percent']['dark_theme'] = $percent($summary['dark_theme']);
            $summary['percent']['auto_theme'] = $percent($summary['auto_theme']);
        }

        return $summary;
    }
    private function haveMutualFriends($userId1, $userId2) {
        $sql = "SELECT COUNT(*) as mutual_count
                FROM Friends f1
                JOIN Friends f2 ON f1.friend_id = f2.friend_id
                WHERE f1.user_id = ? 
                AND f2.user_id = ?
                AND f1.status = 'accepted'
                AND f2.status = 'accepted'
                AND f1.friend_id != ?
                AND f1.friend_id != ?";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId1, $userId2, $userId1, $userId2]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result['mutual_count'] ?? 0) > 0;
    }

    public function getBlockedUsers($userId) {
        $sql = "SELECT u.user_id, u.username, u.first_name, u.last_name, u.profile_picture
                FROM Users u
                INNER JOIN BlockedUsers bu ON bu.blocked_user_id = u.user_id
                WHERE bu.user_id = ? AND u.is_active = TRUE
                ORDER BY u.username ASC";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function unblockUser($userId, $blockedUserId) {
        $sql = "DELETE FROM BlockedUsers WHERE user_id = ? AND blocked_user_id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([$userId, $blockedUserId]);
    }
}
?>