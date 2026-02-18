<?php

class UserModel extends BaseModel
{
    protected array $attributes = [
        'first_name', 'last_name',
        'email', 'phone_number',
        'username', 'bio', 'date_of_birth',
        'profile_picture', 'cover_photo',
        'university', 'location', 'friends_count',
    ];

    protected array $sensitive = [
        'password', 'ban_reason', 'ban_notes'
    ];

    protected array $system = [
        'user_id', 'role',
        'created_at', 'updated_at',
        'last_login', 'is_active',
        'banned_by', 'banned_until',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {

    }

    public function create($data): bool
    {        
        $fields = [];
        $params = [];
        $values = [];

        foreach ($this->attributes as $field)
        {
            if (isset($data[$field]))
            {
                $fields[] = $field;
                $values[] = "?";
                $params[] = $data[$field];
            }
        }

        $fields[] = 'password';
        $values[] = "?";
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO Users (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $values) . ")";
        $status = $this->dbInstance->prepare($sql)->execute($params);

        return $status;   
    }

    public function retrieve()
    {
        
    }

    public function update($data, $userId): bool
    {
        $updates = [];
        $params = [];

        foreach ($this->attributes as $field)
        {
            if (isset($data[$field]))
            {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (!$updates) return true;

        $params[] = $userId;

        $sql = "UPDATE Users SET" . implode(", ", $updates) . " WHERE user_id = ?";
        $status = $this->dbInstance->prepare($sql)->execute($params);

        return $status;
    }

    public function updatePassword($password, $userId): bool
    {
        $sql = "UPDATE Users SET password = ? WHERE user_id = ?";
        $params = [password_hash($password, PASSWORD_DEFAULT), $userId];

        $status = $this->dbInstance->prepare($sql)->execute($params);

        return $status;
    }

    public function checkIfUnique($field, $value, $exclude_id = null): bool
    {
        $sql = "SELECT user_id FROM Users WHERE " . $field . " = ? AND is_active = TRUE";
        $params = [$value];

        if ($exclude_id)
        {
            $sql .= "AND user_id != ?";
            $params[] = $exclude_id;
        }

        $stmt = $this->dbInstance->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? true : false;
    }

    public function findByField($field, $value)
    {
        $sql = "SELECT "
            . implode(', ', $this->attributes) .  ", "
            . implode(', ', $this->system)
            . " FROM Users WHERE " . $field . " = ? AND is_active = TRUE";
        $params = [$value];

        $stmt = $this->dbInstance->prepare($sql);
        $stmt->execute($params);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user;
    }

    public function fetchPassword($identifier, $value)
    {
        $sql = "SELECT password FROM Users WHERE " . $identifier . " = ? AND is_active = TRUE";
        $params = [$value];

        $stmt = $this->dbInstance->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['password'] : null;
    }

    public function clearBan($userId): bool
    {
        try
        {
            $sql = "UPDATE Users SET
                        banned_until = NULL,
                        ban_reason = NULL,
                        ban_notes = NULL,
                        banned_by = NULL
                    WHERE user_id = ?";

            $stmt = $this->dbInstance->prepare($sql);
            return $stmt->execute([$userId]);
        }
        catch (PDOException $e)
        {
            error_log('clearBan error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateLastLogin($user_id): bool
    {
        $sql = "UPDATE Users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?";
        $stmt = $this->dbInstance->prepare($sql);

        return $stmt->execute([$user_id]);
    }
}