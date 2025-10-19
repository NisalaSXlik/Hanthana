-- Users table (enhanced with phone number)
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NULL, -- ADDED PHONE NUMBER
    password_hash VARCHAR(255) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    bio TEXT,
    profile_picture VARCHAR(255),
    cover_photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    university VARCHAR(255),
    last_login TIMESTAMP NULL,
    friends_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    date_of_birth DATE,
    location VARCHAR(255)
);

-- User Settings
CREATE TABLE UserSettings (
    user_id INT PRIMARY KEY,
    push_notifications BOOLEAN DEFAULT TRUE,
    privacy_level ENUM('public', 'friends_only', 'private') DEFAULT 'friends_only',
    show_online_status BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Friends table (existing - good)
CREATE TABLE Friends (
    friendship_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending','accepted','blocked') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE(user_id, friend_id)
);

-- Blocked Users (existing - good)
CREATE TABLE BlockedUsers (
    blocker_id INT NOT NULL,
    blocked_id INT NOT NULL,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (blocker_id, blocked_id),
    FOREIGN KEY (blocker_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Groups (enhanced)
CREATE TABLE GroupsTable (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    tag VARCHAR(50) UNIQUE,
    description TEXT,
    display_picture VARCHAR(255),
    cover_image VARCHAR(255),
    privacy_status ENUM('public','private','secret') DEFAULT 'public',
    focus VARCHAR(100),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    member_count INT DEFAULT 0,
    post_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    rules TEXT,
    FOREIGN KEY (created_by) REFERENCES Users(user_id) ON DELETE CASCADE
);



-- Group Members (clean version - no invited_by)
CREATE TABLE GroupMember (
    membership_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin','moderator','member') DEFAULT 'member',
    status ENUM('active','banned','pending') DEFAULT 'active',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES GroupsTable(group_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE(group_id, user_id)
);

-- Group Join Requests (for private groups) NEW
CREATE TABLE GroupJoinRequests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (group_id) REFERENCES GroupsTable(group_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES Users(user_id) ON DELETE SET NULL,
    UNIQUE(group_id, user_id)
);

-- Channels (enhanced for group chats)
CREATE TABLE Channel (
    channel_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES GroupsTable(group_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Posts table with vote counts and view count
CREATE TABLE Post (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT,
    post_type ENUM('text','image','video','event','poll','other') DEFAULT 'text',
    visibility ENUM('public','friends_only','private','group') DEFAULT 'public',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    event_title VARCHAR(255),
    event_date DATE NULL,
    event_time TIME NULL,
    event_location VARCHAR(255) NULL,
    is_group_post BOOLEAN DEFAULT FALSE,
    group_id INT NULL,
    author_id INT NOT NULL,
    upvote_count INT DEFAULT 0,
    downvote_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    comment_count INT DEFAULT 0,
    share_count INT DEFAULT 0,
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    FOREIGN KEY (group_id) REFERENCES GroupsTable(group_id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Post Media (existing - good)
CREATE TABLE PostMedia (
    postmedia_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    uploader_id INT NOT NULL,
    file_name VARCHAR(255),
    file_type ENUM('image','video','document','other'),
    file_url VARCHAR(255),
    file_size INT,
    duration INT NULL, 
    thumbnail_url VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES Post(post_id) ON DELETE CASCADE,
    FOREIGN KEY (uploader_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Comments (enhanced)
CREATE TABLE Comment (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    commenter_id INT NOT NULL,
    parent_comment_id INT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    FOREIGN KEY (post_id) REFERENCES Post(post_id) ON DELETE CASCADE,
    FOREIGN KEY (commenter_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES Comment(comment_id) ON DELETE CASCADE
);



-- Replace the Like table with Vote table
CREATE TABLE Vote (
    vote_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type ENUM('upvote','downvote') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES Post(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE(post_id, user_id)
);





-- Chat Conversations (Direct Messages & Group Chats)
CREATE TABLE Conversations (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_type ENUM('direct','group') DEFAULT 'direct',
    name VARCHAR(255) NULL, 
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_message_at TIMESTAMP NULL,
    last_message_text TEXT NULL,
    FOREIGN KEY (created_by) REFERENCES Users(user_id) ON DELETE SET NULL
);

-- Conversation Participants
CREATE TABLE ConversationParticipants (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin','member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (conversation_id) REFERENCES Conversations(conversation_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE(conversation_id, user_id)
);

-- Messages
CREATE TABLE Messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    message_type ENUM('text','image','video','file','system') DEFAULT 'text',
    content TEXT,
    file_url VARCHAR(255) NULL,
    file_name VARCHAR(255) NULL,
    file_size INT NULL,
    replied_to_message_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_edited BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (conversation_id) REFERENCES Conversations(conversation_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (replied_to_message_id) REFERENCES Messages(message_id) ON DELETE SET NULL
);

-- Message Read Status
CREATE TABLE MessageReadStatus (
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id, user_id),
    FOREIGN KEY (message_id) REFERENCES Messages(message_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Notifications (enhanced)
CREATE TABLE Notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    triggered_by_user_id INT NULL,
    type ENUM('friend_request','friend_request_accepted','post_upvote','post_downvote','post_comment','post_share','mention','message','event_invite','group_invite','group_request','event_reminder','system_alert') NOT NULL,
    reference_id INT NULL,
    reference_type ENUM('post','comment','event','group','message','friend_request') NULL,
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
    INDEX idx_user_unread(user_id, is_read, created_at),
    INDEX idx_user_type(user_id, type),
    INDEX idx_created_at(created_at),
    INDEX idx_expires_at(expires_at)
);

-- Media Files (existing - good)
CREATE TABLE MediaFile (
    media_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    uploader_id INT NOT NULL,
    channel_id INT NULL,
    file_name VARCHAR(255),
    file_type ENUM('image','video','doc','pdf','other'),
    file_url VARCHAR(255),
    file_size INT,
    requires_admin_approval BOOLEAN DEFAULT FALSE,
    status ENUM('approved','pending','rejected') DEFAULT 'approved',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES GroupsTable(group_id) ON DELETE CASCADE,
    FOREIGN KEY (uploader_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES Channel(channel_id) ON DELETE SET NULL
);

-- Bins (existing - good)
CREATE TABLE Bin (
    bin_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    created_by INT NOT NULL,
    name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES GroupsTable(group_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- BinMedia (existing - good)
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




-- Post Views Tracking (for popular feed)
CREATE TABLE PostViews (
    view_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES Post(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL
);

CREATE TABLE GroupSettings (
    group_id INT PRIMARY KEY,
    allow_member_posting BOOLEAN DEFAULT TRUE,
    require_post_approval BOOLEAN DEFAULT FALSE,
    allow_file_uploads BOOLEAN DEFAULT TRUE,
    max_file_size INT DEFAULT 50, -- in MB
    updated_by INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES GroupsTable(group_id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE AdminActions (
    action_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type ENUM('user_ban','post_remove','comment_remove','group_remove','user_warn') NOT NULL,
    target_user_id INT NULL,
    target_post_id INT NULL,
    target_group_id INT NULL,
    reason TEXT,
    action_taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES Users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (target_post_id) REFERENCES Post(post_id) ON DELETE SET NULL,
    FOREIGN KEY (target_group_id) REFERENCES GroupsTable(group_id) ON DELETE SET NULL
);

CREATE TABLE Reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_user_id INT NULL,
    reported_post_id INT NULL,
    reported_comment_id INT NULL,
    reported_group_id INT NULL,
    report_type ENUM('spam','harassment','inappropriate','other') NOT NULL,
    description TEXT,
    status ENUM('pending','reviewed','resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (reporter_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES Users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (reported_post_id) REFERENCES Post(post_id) ON DELETE SET NULL,
    FOREIGN KEY (reported_comment_id) REFERENCES Comment(comment_id) ON DELETE SET NULL,
    FOREIGN KEY (reported_group_id) REFERENCES GroupsTable(group_id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES Users(user_id) ON DELETE SET NULL
);

-- Triggers for maintaining counts
DELIMITER //

-- Vote triggers
CREATE TRIGGER after_vote_insert
AFTER INSERT ON Vote
FOR EACH ROW
BEGIN
    IF NEW.vote_type = 'upvote' THEN
        UPDATE Post SET upvote_count = upvote_count + 1 WHERE post_id = NEW.post_id;
    ELSE
        UPDATE Post SET downvote_count = downvote_count + 1 WHERE post_id = NEW.post_id;
    END IF;
END//

CREATE TRIGGER after_vote_update
AFTER UPDATE ON Vote
FOR EACH ROW
BEGIN
    -- Remove old vote
    IF OLD.vote_type = 'upvote' THEN
        UPDATE Post SET upvote_count = upvote_count - 1 WHERE post_id = OLD.post_id;
    ELSE
        UPDATE Post SET downvote_count = downvote_count - 1 WHERE post_id = OLD.post_id;
    END IF;
    
    -- Add new vote
    IF NEW.vote_type = 'upvote' THEN
        UPDATE Post SET upvote_count = upvote_count + 1 WHERE post_id = NEW.post_id;
    ELSE
        UPDATE Post SET downvote_count = downvote_count + 1 WHERE post_id = NEW.post_id;
    END IF;
END//

CREATE TRIGGER after_vote_delete
AFTER DELETE ON Vote
FOR EACH ROW
BEGIN
    IF OLD.vote_type = 'upvote' THEN
        UPDATE Post SET upvote_count = upvote_count - 1 WHERE post_id = OLD.post_id;
    ELSE
        UPDATE Post SET downvote_count = downvote_count - 1 WHERE post_id = OLD.post_id;
    END IF;
END//

-- Existing triggers for comments and group members
CREATE TRIGGER after_comment_insert
AFTER INSERT ON Comment
FOR EACH ROW
BEGIN
    UPDATE Post SET comment_count = comment_count + 1 WHERE post_id = NEW.post_id;
END//

CREATE TRIGGER after_comment_delete
AFTER DELETE ON Comment
FOR EACH ROW
BEGIN
    UPDATE Post SET comment_count = comment_count - 1 WHERE post_id = OLD.post_id;
END//

CREATE TRIGGER after_group_member_insert
AFTER INSERT ON GroupMember
FOR EACH ROW
BEGIN
    UPDATE GroupsTable SET member_count = member_count + 1 WHERE group_id = NEW.group_id;
END//

CREATE TRIGGER after_group_member_delete
AFTER DELETE ON GroupMember
FOR EACH ROW
BEGIN
    UPDATE GroupsTable SET member_count = member_count - 1 WHERE group_id = OLD.group_id;
END//

DELIMITER ;