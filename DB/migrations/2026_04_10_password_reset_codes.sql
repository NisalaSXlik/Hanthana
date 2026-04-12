CREATE TABLE IF NOT EXISTS PasswordResetCodes (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
