-- Users table
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    bio TEXT,
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    university VARCHAR(255),
    last_login TIMESTAMP NULL,
    friends_count INT DEFAULT 0
);

-- Friends table
CREATE TABLE Friends (
    friendship_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending','accepted','blocked') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE(user_id, friend_id)
);

-- Blocked Users
CREATE TABLE BlockedUsers (
    blocker_id INT NOT NULL,
    blocked_id INT NOT NULL,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (blocker_id, blocked_id),
    FOREIGN KEY (blocker_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Notifications
CREATE TABLE Notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    triggered_by_user_id INT NULL,
    type ENUM('friend_request','friend_request_accepted','post_like','post_comment','post_share','mention','message','event_invite','group_invite','system_alert'),
    reference_id INT NULL,
    reference_type ENUM('post','comment','event','group','message') NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    priority ENUM('low','medium','high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (triggered_by_user_id) REFERENCES Users(user_id) ON DELETE SET NULL,
    INDEX idx_user_unread(user_id, is_read),
    INDEX idx_user_type(user_id, type),
    INDEX idx_created_at(created_at),
    INDEX idx_expires_at(expires_at)
);

-- Groups
CREATE TABLE Groups (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    tag VARCHAR(50),
    description TEXT,
    display_picture VARCHAR(255),
    cover_image VARCHAR(255),
    privacy_status ENUM('public','private','secret') DEFAULT 'public',
    focus VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Group Members
CREATE TABLE GroupMember (
    membership_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin','moderator','member') DEFAULT 'member',
    status ENUM('active','banned','pending') DEFAULT 'active',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES Groups(group_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE(group_id, user_id)
);

-- Channels
CREATE TABLE Channel (
    channel_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES Groups(group_id) ON DELETE CASCADE
);

-- Media Files
CREATE TABLE MediaFile (
    media_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    uploader_id INT NOT NULL,
    channel_id INT NULL,
    file_name VARCHAR(255),
    file_type ENUM('image','video','doc','pdf','other'),
    file_url VARCHAR(255),
    requires_admin_approval BOOLEAN DEFAULT FALSE,
    status ENUM('approved','pending','rejected') DEFAULT 'approved',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES Groups(group_id) ON DELETE CASCADE,
    FOREIGN KEY (uploader_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES Channel(channel_id) ON DELETE SET NULL
);

-- Bins
CREATE TABLE Bin (
    bin_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    created_by INT NOT NULL,
    name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES Groups(group_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- BinMedia (Many-to-Many)
CREATE TABLE BinMedia (
    bin_id INT NOT NULL,
    media_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    added_by INT NOT NULL,
    PRIMARY KEY(bin_id, media_id),
    FOREIGN KEY (bin_id) REFERENCES Bin(bin_id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES MediaFile(media_id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Posts
CREATE TABLE Post (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT,
    post_type ENUM('text','image','video','event','poll','other') DEFAULT 'text',
    visibility ENUM('public','group-only','private') DEFAULT 'public',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    event_date DATE NULL,
    is_group_post BOOLEAN DEFAULT FALSE,
    group_id INT NULL,
    author_id INT NOT NULL,
    FOREIGN KEY (group_id) REFERENCES Groups(group_id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Post Media
CREATE TABLE PostMedia (
    postmedia_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    uploader_id INT NOT NULL,
    file_name VARCHAR(255),
    file_type ENUM('image','video','document','other'),
    file_url VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES Post(post_id) ON DELETE CASCADE,
    FOREIGN KEY (uploader_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Comments
CREATE TABLE Comment (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    commenter_id INT NOT NULL,
    parent_comment_id INT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES Post(post_id) ON DELETE CASCADE,
    FOREIGN KEY (commenter_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES Comment(comment_id) ON DELETE CASCADE
);

-- Likes
CREATE TABLE `Like` (
    like_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES Post(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE(post_id, user_id)
);

-- Shares
CREATE TABLE Share (
    share_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    shared_by_id INT NOT NULL,
    shared_to_group_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES Post(post_id) ON DELETE CASCADE,
    FOREIGN KEY (shared_by_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (shared_to_group_id) REFERENCES Groups(group_id) ON DELETE SET NULL
);
