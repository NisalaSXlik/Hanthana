-- Group governance tables (role-change votes and delete approvals)
-- Safe to run multiple times.

CREATE TABLE IF NOT EXISTS GroupRoleChangeRequests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    target_user_id INT NOT NULL,
    requested_role ENUM('admin', 'member') NOT NULL,
    current_role ENUM('admin', 'member') NOT NULL,
    proposed_by INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    INDEX idx_grcr_group_status (group_id, status),
    INDEX idx_grcr_target (target_user_id),
    CONSTRAINT fk_grcr_group FOREIGN KEY (group_id) REFERENCES GroupsTable(group_id) ON DELETE CASCADE,
    CONSTRAINT fk_grcr_target_user FOREIGN KEY (target_user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_grcr_proposer FOREIGN KEY (proposed_by) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS GroupRoleChangeVotes (
    request_id INT NOT NULL,
    admin_user_id INT NOT NULL,
    voted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (request_id, admin_user_id),
    INDEX idx_grcv_admin (admin_user_id),
    CONSTRAINT fk_grcv_request FOREIGN KEY (request_id) REFERENCES GroupRoleChangeRequests(request_id) ON DELETE CASCADE,
    CONSTRAINT fk_grcv_admin FOREIGN KEY (admin_user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS GroupDeleteApprovals (
    group_id INT NOT NULL,
    admin_user_id INT NOT NULL,
    approved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (group_id, admin_user_id),
    INDEX idx_gda_admin (admin_user_id),
    CONSTRAINT fk_gda_group FOREIGN KEY (group_id) REFERENCES GroupsTable(group_id) ON DELETE CASCADE,
    CONSTRAINT fk_gda_admin FOREIGN KEY (admin_user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
