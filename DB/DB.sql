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
    location VARCHAR(255),
    role ENUM('user','admin') DEFAULT 'user',
    banned_until TIMESTAMP NULL,
    ban_reason VARCHAR(255) NULL,
    ban_notes TEXT NULL,
    banned_by INT NULL,
    FOREIGN KEY (banned_by) REFERENCES Users(user_id) ON DELETE SET NULL
);

-- User Settings
CREATE TABLE UserSettings (
    user_id INT PRIMARY KEY,
    profile_visibility ENUM('everyone', 'friends', 'private') DEFAULT 'friends',
    post_visibility ENUM('everyone', 'friends', 'private') DEFAULT 'friends',
    friend_request_visibility ENUM('everyone', 'friends_of_friends', 'none') DEFAULT 'everyone',
    show_email BOOLEAN DEFAULT FALSE,
    show_phone BOOLEAN DEFAULT FALSE,
    email_comments BOOLEAN DEFAULT TRUE,
    email_likes BOOLEAN DEFAULT TRUE,
    email_friend_requests BOOLEAN DEFAULT TRUE,
    email_messages BOOLEAN DEFAULT TRUE,
    email_group_activity BOOLEAN DEFAULT TRUE,
    push_enabled BOOLEAN DEFAULT TRUE,
    theme ENUM('light', 'dark', 'auto') DEFAULT 'light',
    font_size ENUM('small', 'medium', 'large') DEFAULT 'medium',
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
    conversation_id INT NOT NULL UNIQUE,
    group_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    display_picture VARCHAR(255),
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
    event_time TIME NULL,-- no need 
    event_location VARCHAR(255) NULL,
    is_group_post BOOLEAN DEFAULT FALSE,
    group_id INT NULL,
    author_id INT NOT NULL,
    group_post_type ENUM('discussion','question','resource','poll','event','assignment') DEFAULT 'discussion',
    metadata JSON NULL,
    upvote_count INT DEFAULT 0,
    downvote_count INT DEFAULT 0,
    view_count INT DEFAULT 0,-- no need
    comment_count INT DEFAULT 0,
    share_count INT DEFAULT 0,
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    FOREIGN KEY (group_id) REFERENCES GroupsTable(group_id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE GroupPostPollVote (
    vote_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    option_index TINYINT NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_poll_vote (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES Post(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
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

-- Calendar reminders for user events
CREATE TABLE CalendarReminders (
    reminder_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    group_id INT NULL,
    post_id INT NULL,
    title VARCHAR(255) NOT NULL,
    event_date DATE NULL,
    event_time TIME NULL,
    location VARCHAR(255) NULL,
    description TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_post (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES GroupsTable(group_id) ON DELETE SET NULL,
    FOREIGN KEY (post_id) REFERENCES Post(post_id) ON DELETE CASCADE
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

-- filepath: /mnt/c/Users/G-San/Desktop/Hanthane/DB/DB.sql
-- Add after MediaFile table
/*CREATE TABLE FileReviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    media_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    parent_review_id INT NULL,  -- For replies, max depth 2
    content TEXT NOT NULL,
    rating INT NULL CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (media_id) REFERENCES MediaFile(media_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_review_id) REFERENCES FileReviews(review_id) ON DELETE CASCADE
);

-- filepath: /mnt/c/Users/G-San/Desktop/Hanthane/DB/DB.sql
-- Add after FileReviews
CREATE TABLE FileAccess (
    access_id INT AUTO_INCREMENT PRIMARY KEY,
    media_id INT NOT NULL,
    user_id INT NOT NULL,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (media_id) REFERENCES MediaFile(media_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE(media_id, user_id)  -- Remove ON CONFLICT REPLACE
);*/

/*-- Triggers for maintaining counts
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

DELIMITER ;*/

-- Chat Folders table for file management
CREATE TABLE ChatFolders (
    folder_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    parent_folder_id INT NULL,
    folder_name VARCHAR(255) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES Conversations(conversation_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_folder_id) REFERENCES ChatFolders(folder_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_conversation_parent (conversation_id, parent_folder_id)
);

-- Chat Files table for file management
CREATE TABLE ChatFiles (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    folder_id INT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES Conversations(conversation_id) ON DELETE CASCADE,
    FOREIGN KEY (folder_id) REFERENCES ChatFolders(folder_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_conversation_folder (conversation_id, folder_id)
);

-- Questions table for Q&A system
CREATE TABLE Questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    content TEXT,
    category VARCHAR(100),
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_created (created_at),
    FULLTEXT idx_search (title, content)
);

-- Answers table
CREATE TABLE Answers (
    answer_id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    is_accepted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES Questions(question_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_question (question_id),
    INDEX idx_created (created_at)
);

-- Question votes
CREATE TABLE QuestionVotes (
    vote_id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type ENUM('upvote', 'downvote') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES Questions(question_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_question (user_id, question_id)
);

-- Answer votes
CREATE TABLE AnswerVotes (
    vote_id INT AUTO_INCREMENT PRIMARY KEY,
    answer_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type ENUM('upvote', 'downvote') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (answer_id) REFERENCES Answers(answer_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_answer (user_id, answer_id)
);

-- Question topics/tags
CREATE TABLE QuestionTopics (
    topic_id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    topic_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (question_id) REFERENCES Questions(question_id) ON DELETE CASCADE,
    INDEX idx_topic (topic_name)
);

INSERT INTO `Users` (`user_id`, `first_name`, `last_name`, `email`, `phone_number`, `password_hash`, `username`, `bio`, `profile_picture`, `cover_photo`, `created_at`, `updated_at`, `university`, `last_login`, `friends_count`, `is_active`, `date_of_birth`, `location`, `role`, `banned_until`, `ban_reason`, `ban_notes`, `banned_by`) VALUES
(1, 'Dummy', 'Admin', 'admin@hanthana.com', '0000000000', '$2y$10$Ys6K.2VyuP482eNAUDBHK.zGRJb18Cz5dlCOGUfNnPEHakO6Qlrvy', 'admin', NULL, 'uploads/user_dp/default.png', 'uploads/user_cover/default.png', '2026-01-22 20:15:29', '2026-01-22 20:16:50', NULL, '2026-01-22 20:15:54', 0, 1, NULL, NULL, 'admin', NULL, NULL, NULL, NULL),
(2, 'Dummy', 'User1', 'user1@hanthana.com', '0000000001', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'user1', NULL, 'uploads/user_dp/default.png', 'uploads/user_cover/default.png', '2026-01-22 20:17:41', '2026-01-22 20:18:24', NULL, '2026-01-22 20:17:59', 0, 1, NULL, NULL, 'user', NULL, NULL, NULL, NULL);

ALTER TABLE `Users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
(1, 'Dummy', 'Admin', 'admin@hanthana.com', '0000000000', '$2y$10$Ys6K.2VyuP482eNAUDBHK.zGRJb18Cz5dlCOGUfNnPEHakO6Qlrvy', 'admin', NULL, 'public/images/profile-1.jpg', 'public/images/story-1.jpg', '2026-01-22 20:15:29', '2026-01-22 20:16:50', NULL, '2026-01-22 20:15:54', 0, 1, NULL, NULL, 'admin', NULL, NULL, NULL, NULL),
(2, 'Dummy', 'User1', 'user1@hanthana.com', '0000000001', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'user1', NULL, 'public/images/profile-2.jpg', 'public/images/story-2.jpg', '2026-01-22 20:17:41', '2026-01-22 20:18:24', NULL, '2026-01-22 20:17:59', 0, 1, NULL, NULL, 'user', NULL, NULL, NULL, NULL),
(3, 'Maya', 'Lee', 'maya.lee@hanthana.edu', '5550101003', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'mayalee', 'STEM education researcher facilitating first-year calculus support.', 'public/images/profile-3.jpg', 'public/images/story-3.jpg', '2026-02-01 08:45:00', '2026-02-06 16:10:00', 'Hanthana University of Education', '2026-02-06 16:05:00', 3, 1, '1992-04-14', 'Boston, MA', 'user', NULL, NULL, NULL, NULL),
(4, 'Ravi', 'Perera', 'ravi.perera@hanthana.edu', '5550101004', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'raviP', 'Physics lecturer mentoring engineering undergraduates.', 'public/images/profile-4.jpg', 'public/images/story-4.jpg', '2026-02-01 09:05:00', '2026-02-07 09:40:00', 'Hanthana University of Education', '2026-02-07 08:55:00', 3, 1, '1985-09-22', 'Kandy, Sri Lanka', 'user', NULL, NULL, NULL, NULL),
(5, 'Sarah', 'Kim', 'sarah.kim@hanthana.edu', '5550101005', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'sarahK', 'Instructional designer prototyping flipped classroom resources.', 'public/images/profile-5.jpg', 'public/images/story-5.jpg', '2026-02-01 09:20:00', '2026-02-07 12:05:00', 'Hanthana University of Education', '2026-02-07 11:50:00', 4, 1, '1990-01-18', 'Seattle, WA', 'user', NULL, NULL, NULL, NULL),
(6, 'Daniel', 'Owens', 'daniel.owens@hanthana.edu', '5550101006', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'danOwens', 'Lab coordinator supporting physics bridge courses.', 'public/images/profile-6.jpg', 'public/images/story-6.jpg', '2026-02-01 09:40:00', '2026-02-06 14:12:00', 'Hanthana University of Education', '2026-02-06 14:00:00', 2, 1, '1988-06-10', 'Denver, CO', 'user', NULL, NULL, NULL, NULL),
(7, 'Priya', 'Singh', 'priya.singh@hanthana.edu', '5550101007', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'priyaS', 'Peer mentor guiding calculus problem-solving workshops.', 'public/images/profile-7.jpg', 'public/images/1.jpg', '2026-02-01 09:55:00', '2026-02-07 17:25:00', 'Hanthana University of Education', '2026-02-07 17:00:00', 3, 1, '2001-11-02', 'Colombo, Sri Lanka', 'user', NULL, NULL, NULL, NULL),
(8, 'Leon', 'Wu', 'leon.wu@hanthana.edu', '5550101008', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'leonWu', 'Scholarship officer curating study funding pathways.', 'public/images/profile-8.jpg', 'public/images/2.jpg', '2026-02-01 10:10:00', '2026-02-06 19:30:00', 'Hanthana University of Education', '2026-02-06 19:10:00', 2, 1, '1993-05-07', 'San Francisco, CA', 'user', NULL, NULL, NULL, NULL),
(9, 'Clara', 'Mendes', 'clara.mendes@hanthana.edu', '5550101009', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'claraM', 'STEM librarian curating open educational resources.', 'public/images/profile-9.jpg', 'public/images/3.jpg', '2026-02-01 10:25:00', '2026-02-07 13:45:00', 'Hanthana University of Education', '2026-02-07 13:20:00', 3, 1, '1994-12-28', 'Lisbon, Portugal', 'user', NULL, NULL, NULL, NULL),
(10, 'Jamal', 'Carter', 'jamal.carter@hanthana.edu', '5550101010', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'jamalC', 'Leadership coach coordinating campus mentorship network.', 'public/images/profile-10.jpg', 'public/images/4.jpg', '2026-02-01 10:45:00', '2026-02-07 15:15:00', 'Hanthana University of Education', '2026-02-07 15:00:00', 3, 1, '1987-03-16', 'Chicago, IL', 'user', NULL, NULL, NULL, NULL),
(11, 'Aisha', 'Banerjee', 'aisha.banerjee@hanthana.edu', '5550101011', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'aishaB', 'Graduate assistant studying inclusive teaching strategies.', 'public/images/profile-11.jpg', 'public/images/5.jpg', '2026-02-01 11:00:00', '2026-02-06 21:05:00', 'Hanthana University of Education', '2026-02-06 20:50:00', 2, 1, '1998-08-30', 'Mumbai, India', 'user', NULL, NULL, NULL, NULL),
(12, 'Elena', 'Petrova', 'elena.petrova@hanthana.edu', '5550101012', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'elenaP', 'Teacher educator leading practicum reflections.', 'public/images/profile-12.jpg', 'public/images/6.jpg', '2026-02-01 11:15:00', '2026-02-07 16:45:00', 'Hanthana University of Education', '2026-02-07 16:20:00', 2, 1, '1989-02-11', 'Sofia, Bulgaria', 'user', NULL, NULL, NULL, NULL),
(13, 'Tomas', 'Rivera', 'tomas.rivera@hanthana.edu', '5550101013', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'tomasR', 'Service-learning coordinator connecting mentors with schools.', 'public/images/profile-13.jpg', 'public/images/gpvprofAAT_cover.jpg', '2026-02-01 11:30:00', '2026-02-06 18:00:00', 'Hanthana University of Education', '2026-02-06 17:40:00', 2, 1, '1991-10-05', 'San Juan, Puerto Rico', 'user', NULL, NULL, NULL, NULL),
(14, 'Noor', 'Hassan', 'noor.hassan@hanthana.edu', '5550101014', '$2y$10$n0yBtBE3bEz53dEjCHGLOOaw5Sha2umqYkXoF90jEbuCfeO.8thYG', 'noorH', 'STEM bridge student documenting study breakthroughs.', 'public/images/profile-14.jpg', 'public/images/gpvpost_content1.jpg', '2026-02-01 11:45:00', '2026-02-07 18:30:00', 'Hanthana University of Education', '2026-02-07 18:10:00', 1, 1, '2003-04-22', 'Doha, Qatar', 'user', NULL, NULL, NULL, NULL);

ALTER TABLE `Users`
    MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

-- Education-focused defaults for user preferences
INSERT INTO `UserSettings` (`user_id`, `profile_visibility`, `post_visibility`, `friend_request_visibility`, `show_email`, `show_phone`, `email_comments`, `email_likes`, `email_friend_requests`, `email_messages`, `email_group_activity`, `push_enabled`, `theme`, `font_size`) VALUES
(1, 'friends', 'friends', 'everyone', 0, 0, 1, 1, 1, 1, 1, 1, 'auto', 'medium'),
(2, 'friends', 'friends', 'friends_of_friends', 0, 0, 1, 1, 1, 1, 1, 1, 'light', 'medium'),
(3, 'friends', 'friends', 'friends_of_friends', 0, 0, 1, 1, 1, 1, 1, 1, 'dark', 'medium'),
(4, 'everyone', 'friends', 'everyone', 1, 0, 1, 1, 1, 1, 1, 1, 'light', 'medium'),
(5, 'friends', 'friends', 'friends_of_friends', 0, 0, 1, 1, 1, 1, 1, 1, 'light', 'medium'),
(6, 'friends', 'friends', 'none', 0, 0, 1, 1, 0, 1, 1, 1, 'dark', 'medium'),
(7, 'friends', 'friends', 'friends_of_friends', 0, 0, 1, 1, 1, 1, 1, 1, 'auto', 'medium'),
(8, 'everyone', 'everyone', 'everyone', 1, 0, 1, 1, 1, 1, 1, 1, 'light', 'medium'),
(9, 'friends', 'friends', 'friends_of_friends', 0, 0, 1, 1, 1, 1, 1, 1, 'dark', 'medium'),
(10, 'friends', 'friends', 'friends_of_friends', 0, 0, 1, 1, 1, 1, 1, 1, 'light', 'large'),
(11, 'friends', 'friends', 'none', 0, 0, 1, 1, 0, 1, 1, 1, 'dark', 'medium'),
(12, 'friends', 'friends', 'friends_of_friends', 0, 0, 1, 1, 1, 1, 1, 1, 'light', 'medium'),
(13, 'friends', 'friends', 'friends_of_friends', 0, 0, 1, 1, 1, 1, 1, 1, 'auto', 'medium'),
(14, 'friends', 'friends', 'friends_of_friends', 0, 0, 1, 1, 1, 1, 1, 1, 'dark', 'small');

-- Peer connections among education community members
INSERT INTO `Friends` (`friendship_id`, `user_id`, `friend_id`, `status`, `requested_at`, `accepted_at`) VALUES
(1, 3, 4, 'accepted', '2026-02-02 09:00:00', '2026-02-03 09:00:00'),
(2, 3, 5, 'accepted', '2026-02-02 09:10:00', '2026-02-03 09:20:00'),
(3, 3, 7, 'accepted', '2026-02-02 09:20:00', '2026-02-03 09:30:00'),
(4, 4, 5, 'accepted', '2026-02-02 09:45:00', '2026-02-03 09:55:00'),
(5, 4, 8, 'accepted', '2026-02-02 10:00:00', '2026-02-03 10:15:00'),
(6, 5, 6, 'accepted', '2026-02-02 10:05:00', '2026-02-03 10:25:00'),
(7, 5, 7, 'accepted', '2026-02-02 10:15:00', '2026-02-03 10:35:00'),
(8, 6, 9, 'accepted', '2026-02-02 10:40:00', '2026-02-03 11:05:00'),
(9, 7, 10, 'accepted', '2026-02-02 11:00:00', '2026-02-03 11:20:00'),
(10, 8, 9, 'accepted', '2026-02-02 11:10:00', '2026-02-03 11:25:00'),
(11, 9, 10, 'accepted', '2026-02-02 11:25:00', '2026-02-03 11:45:00'),
(12, 10, 11, 'accepted', '2026-02-02 11:40:00', '2026-02-03 12:05:00'),
(13, 11, 12, 'accepted', '2026-02-02 11:55:00', '2026-02-03 12:15:00'),
(14, 12, 13, 'accepted', '2026-02-02 12:05:00', '2026-02-03 12:25:00'),
(15, 13, 14, 'accepted', '2026-02-02 12:15:00', '2026-02-03 12:35:00');

ALTER TABLE `Friends`
    MODIFY `friendship_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

-- Education-oriented collaboration spaces
INSERT INTO `GroupsTable` (`group_id`, `name`, `tag`, `description`, `display_picture`, `cover_image`, `privacy_status`, `focus`, `created_by`, `created_at`, `updated_at`, `member_count`, `post_count`, `is_active`, `rules`) VALUES
(1, 'STEM Study Circle', 'stem-study', 'Peer-led study group supporting calculus, physics, and engineering bridge courses.', 'public/images/gpvrelatedAC_dp.jpg', 'public/images/gpvpost_content2.jpg', 'public', 'STEM', 3, '2026-02-01 08:30:00', '2026-02-07 14:00:00', 5, 3, 1, 'Respect scheduled problem-solving slots, cite sources, and keep solutions collaborative.'),
(2, 'Instructional Design Lab', 'instructional-design', 'Space for designing flipped classroom scripts and microlearning resources.', 'public/images/gpvrelatedCS_dp.jpg', 'public/images/gpvpost_content3.jpg', 'private', 'Pedagogy', 4, '2026-02-01 08:45:00', '2026-02-07 13:30:00', 4, 2, 1, 'Share prototypes with context, welcome critique, and archive version history.'),
(3, 'Campus Mentors Network', 'campus-mentors', 'Coaching hub coordinating mentorship playbooks for first-year students.', 'public/images/gpvrelatedME_dp.jpg', 'public/images/gpvpost_content4.jpg', 'public', 'Mentoring', 10, '2026-02-01 09:00:00', '2026-02-07 15:45:00', 4, 2, 1, 'Maintain confidentiality, log mentoring hours, and flag safeguarding issues promptly.'),
(4, 'Teacher Training Hub', 'teacher-training', 'Community of practice for practicum supervisors and trainee teachers.', 'public/images/gpvprofAAT_dp.jpg', 'public/images/gpvprofAAT_cover.jpg', 'secret', 'Teacher Training', 12, '2026-02-01 09:15:00', '2026-02-07 16:10:00', 5, 0, 1, 'Keep school partner details private, document feedback cycles, and share reflective notes.');

ALTER TABLE `GroupsTable`
    MODIFY `group_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- Membership roster for learning communities
INSERT INTO `GroupMember` (`membership_id`, `group_id`, `user_id`, `role`, `status`, `joined_at`) VALUES
(1, 1, 3, 'admin', 'active', '2026-02-01 08:35:00'),
(2, 1, 4, 'moderator', 'active', '2026-02-01 09:10:00'),
(3, 1, 5, 'member', 'active', '2026-02-01 09:25:00'),
(4, 1, 7, 'member', 'active', '2026-02-01 09:40:00'),
(5, 1, 9, 'member', 'active', '2026-02-01 09:55:00'),
(6, 2, 4, 'admin', 'active', '2026-02-01 08:50:00'),
(7, 2, 5, 'moderator', 'active', '2026-02-01 09:30:00'),
(8, 2, 6, 'member', 'active', '2026-02-01 09:45:00'),
(9, 2, 8, 'member', 'active', '2026-02-01 10:15:00'),
(10, 3, 10, 'admin', 'active', '2026-02-01 09:05:00'),
(11, 3, 3, 'member', 'active', '2026-02-01 09:20:00'),
(12, 3, 9, 'member', 'active', '2026-02-01 09:35:00'),
(13, 3, 11, 'member', 'active', '2026-02-01 09:50:00'),
(14, 4, 12, 'admin', 'active', '2026-02-01 09:20:00'),

(15, 4, 6, 'moderator', 'active', '2026-02-01 09:50:00'),
(16, 4, 13, 'member', 'active', '2026-02-01 10:05:00'),
(17, 4, 14, 'member', 'active', '2026-02-01 10:20:00'),
(18, 4, 11, 'member', 'active', '2026-02-05 18:15:00');

ALTER TABLE `GroupMember`
    MODIFY `membership_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

-- Group collaboration policies
INSERT INTO `GroupSettings` (`group_id`, `allow_member_posting`, `require_post_approval`, `allow_file_uploads`, `max_file_size`, `updated_by`, `updated_at`) VALUES
(1, 1, 0, 1, 80, 3, '2026-02-06 10:00:00'),
(2, 1, 1, 1, 60, 4, '2026-02-06 11:15:00'),
(3, 1, 0, 1, 100, 10, '2026-02-06 12:30:00'),
(4, 1, 1, 1, 50, 12, '2026-02-06 13:45:00');

-- Pending requests for specialized groups
INSERT INTO `GroupJoinRequests` (`request_id`, `group_id`, `user_id`, `status`, `requested_at`, `reviewed_by`, `reviewed_at`) VALUES
(1, 2, 7, 'pending', '2026-02-07 08:30:00', NULL, NULL),
(2, 4, 11, 'approved', '2026-02-05 14:10:00', 12, '2026-02-05 18:05:00');

ALTER TABLE `GroupJoinRequests`
    MODIFY `request_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- Education-themed posts and activities
INSERT INTO `Post` (`post_id`, `content`, `post_type`, `visibility`, `created_at`, `updated_at`, `event_title`, `event_date`, `event_time`, `event_location`, `is_group_post`, `group_id`, `author_id`, `group_post_type`, `metadata`, `upvote_count`, `downvote_count`, `view_count`, `comment_count`, `share_count`, `is_edited`, `edited_at`) VALUES
(1, 'Shared annotated calculus workbook aligned with week 3 engineering tutorials. Track your problem-solving attempts and note misconceptions for review.', 'text', 'group', '2026-02-02 10:05:00', '2026-02-02 10:05:00', NULL, NULL, NULL, NULL, 1, 1, 3, 'resource', '{"subject":"Calculus","attachments":["public/images/gpvpost_content5.jpg"],"difficulty":"first-year"}', 4, 0, 128, 2, 1, 0, NULL),
(2, 'Physics lab prep meetup to rehearse safety procedures and data logging before assessments.', 'event', 'group', '2026-02-03 08:40:00', '2026-02-03 08:40:00', 'Physics Lab Prep Session', '2026-02-10', '15:00:00', 'Engineering Building Lab 2', 1, 1, 5, 'event', '{"rsvp_limit":20,"materials":["goggles","lab notebooks"]}', 3, 0, 94, 1, 0, 0, NULL),
(3, 'Drafting microlearning storyboard for flipped classroom module on projectile motion. Feedback welcome.', 'text', 'group', '2026-02-03 10:15:00', '2026-02-03 10:15:00', NULL, NULL, NULL, NULL, 1, 2, 4, 'discussion', '{"links":["https://openlearning.org/microlearning"],"sprint":"Week4"}', 5, 1, 152, 2, 2, 0, NULL),
(4, 'Which LMS enhancement should we prioritize for the March rollout?', 'poll', 'group', '2026-02-04 09:25:00', '2026-02-04 09:25:00', NULL, NULL, NULL, NULL, 1, 2, 6, 'poll', '{"question":"Which LMS feature upgrade matters most?","options":["Adaptive release","Quick feedback rubrics","Course analytics"],"expires":"2026-02-20"}', 2, 0, 76, 0, 0, 0, NULL),
(5, 'Uploaded mentorship session checklist and reflection prompts for week 2 mentee meetings.', 'text', 'group', '2026-02-04 14:40:00', '2026-02-04 14:40:00', NULL, NULL, NULL, NULL, 1, 3, 10, 'resource', '{"format":"doc","focus":"mentorship","attachments":["public/images/gpvpost_content6.jpg"]}', 3, 0, 88, 1, 1, 0, NULL),
(6, 'Scholarship planning thread: timeline, recommendation letter tips, and budgeting templates for STEM bridge students.', 'text', 'public', '2026-02-05 09:05:00', '2026-02-05 09:05:00', NULL, NULL, NULL, NULL, 0, NULL, 8, 'discussion', '{"audience":"first-year","topics":["scholarship","planning"],"resources":["public/images/gpvpost_content1.jpg"]}', 6, 0, 240, 3, 2, 0, NULL);

ALTER TABLE `Post`
    MODIFY `post_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

-- Media supporting shared learning resources
INSERT INTO `PostMedia` (`postmedia_id`, `post_id`, `uploader_id`, `file_name`, `file_type`, `file_url`, `file_size`, `duration`, `thumbnail_url`, `uploaded_at`) VALUES
(1, 1, 3, 'gpvpost_content5.jpg', 'image', 'public/images/gpvpost_content5.jpg', 24576, NULL, 'public/images/gpvpost_content5.jpg', '2026-02-02 10:06:00'),
(2, 5, 10, 'gpvpost_content6.jpg', 'image', 'public/images/gpvpost_content6.jpg', 16384, NULL, 'public/images/gpvpost_content6.jpg', '2026-02-04 14:41:00'),
(3, 6, 8, 'gpvpost_content1.jpg', 'image', 'public/images/gpvpost_content1.jpg', 8192, NULL, 'public/images/gpvpost_content1.jpg', '2026-02-05 09:06:00');

ALTER TABLE `PostMedia`
    MODIFY `postmedia_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

-- Discussion threads around the shared resources
INSERT INTO `Comment` (`comment_id`, `post_id`, `commenter_id`, `parent_comment_id`, `content`, `created_at`, `updated_at`, `is_edited`, `edited_at`) VALUES
(1, 1, 5, NULL, 'Thanks Maya! I will layer in practice set C for our Thursday tutorial.', '2026-02-02 11:00:00', '2026-02-02 11:00:00', 0, NULL),
(2, 1, 7, 1, 'Great idea. I can model a think-aloud for the first problem.', '2026-02-02 11:20:00', '2026-02-02 11:20:00', 0, NULL),
(3, 2, 9, NULL, 'I''ll prepare the safety signage layout before the session.', '2026-02-03 09:05:00', '2026-02-03 09:05:00', 0, NULL),
(4, 3, 8, NULL, 'Consider chunking into three micro-lessons to keep pacing crisp.', '2026-02-03 11:10:00', '2026-02-03 11:10:00', 0, NULL),
(5, 3, 5, 4, 'Agreed. I''ll storyboard the intro animation separately.', '2026-02-03 11:35:00', '2026-02-03 11:35:00', 0, NULL),
(6, 5, 11, NULL, 'Can we add a wellbeing check prompt to the checklist?', '2026-02-04 15:20:00', '2026-02-04 15:20:00', 0, NULL),
(7, 6, 12, NULL, 'Great breakdown. I''m sharing this with my practicum cohort.', '2026-02-05 10:15:00', '2026-02-05 10:15:00', 0, NULL),
(8, 6, 13, NULL, 'I''ll adapt the budget template for community partner stipends.', '2026-02-05 10:40:00', '2026-02-05 10:40:00', 0, NULL),
(9, 6, 14, 7, 'Do you have a printable checklist for recommendations?', '2026-02-05 10:55:00', '2026-02-05 10:55:00', 0, NULL);

ALTER TABLE `Comment`
    MODIFY `comment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

-- Reactions to community posts
INSERT INTO `Vote` (`vote_id`, `post_id`, `user_id`, `vote_type`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 'upvote', '2026-02-02 11:05:00', '2026-02-02 11:05:00'),
(2, 1, 5, 'upvote', '2026-02-02 11:07:00', '2026-02-02 11:07:00'),
(3, 1, 7, 'upvote', '2026-02-02 11:25:00', '2026-02-02 11:25:00'),
(4, 1, 9, 'upvote', '2026-02-02 11:30:00', '2026-02-02 11:30:00'),
(5, 2, 3, 'upvote', '2026-02-03 09:10:00', '2026-02-03 09:10:00'),
(6, 2, 4, 'upvote', '2026-02-03 09:12:00', '2026-02-03 09:12:00'),
(7, 2, 8, 'upvote', '2026-02-03 09:18:00', '2026-02-03 09:18:00'),
(8, 3, 4, 'upvote', '2026-02-03 11:15:00', '2026-02-03 11:15:00'),
(9, 3, 5, 'upvote', '2026-02-03 11:18:00', '2026-02-03 11:18:00'),
(10, 3, 8, 'upvote', '2026-02-03 11:22:00', '2026-02-03 11:22:00'),
(11, 3, 9, 'upvote', '2026-02-03 11:25:00', '2026-02-03 11:25:00'),
(12, 3, 10, 'upvote', '2026-02-03 11:30:00', '2026-02-03 11:30:00'),
(13, 3, 6, 'downvote', '2026-02-03 11:35:00', '2026-02-03 11:35:00'),
(14, 4, 4, 'upvote', '2026-02-04 09:30:00', '2026-02-04 09:30:00'),
(15, 4, 5, 'upvote', '2026-02-04 09:32:00', '2026-02-04 09:32:00'),
(16, 5, 3, 'upvote', '2026-02-04 14:50:00', '2026-02-04 14:50:00'),
(17, 5, 11, 'upvote', '2026-02-04 15:25:00', '2026-02-04 15:25:00'),
(18, 5, 12, 'upvote', '2026-02-04 15:35:00', '2026-02-04 15:35:00'),
(19, 6, 3, 'upvote', '2026-02-05 09:20:00', '2026-02-05 09:20:00'),
(20, 6, 4, 'upvote', '2026-02-05 09:22:00', '2026-02-05 09:22:00'),
(21, 6, 5, 'upvote', '2026-02-05 09:24:00', '2026-02-05 09:24:00'),
(22, 6, 6, 'upvote', '2026-02-05 09:26:00', '2026-02-05 09:26:00'),
(23, 6, 9, 'upvote', '2026-02-05 09:35:00', '2026-02-05 09:35:00'),
(24, 6, 10, 'upvote', '2026-02-05 09:40:00', '2026-02-05 09:40:00');

ALTER TABLE `Vote`
    MODIFY `vote_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

-- Poll participation details
INSERT INTO `GroupPostPollVote` (`vote_id`, `post_id`, `user_id`, `option_index`, `voted_at`) VALUES
(1, 4, 4, 0, '2026-02-04 09:35:00'),
(2, 4, 5, 1, '2026-02-04 09:38:00'),
(3, 4, 8, 2, '2026-02-04 09:40:00');

ALTER TABLE `GroupPostPollVote`
    MODIFY `vote_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

-- Reminder schedules for academic events
INSERT INTO `CalendarReminders` (`reminder_id`, `user_id`, `group_id`, `post_id`, `title`, `event_date`, `event_time`, `location`, `description`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 2, 'Physics Lab Prep Session', '2026-02-10', '15:00:00', 'Engineering Building Lab 2', 'Arrive 15 minutes early to set up data loggers.', '{"alerts":["2026-02-09T09:00:00","2026-02-10T13:30:00"]}', '2026-02-03 09:00:00', '2026-02-03 09:00:00'),
(2, 7, 1, 2, 'Physics Lab Prep Session', '2026-02-10', '15:00:00', 'Engineering Building Lab 2', 'Bring annotated lab safety checklist.', '{"alerts":["2026-02-09T10:00:00"]}', '2026-02-03 09:05:00', '2026-02-03 09:05:00'),
(3, 12, 4, NULL, 'Reflective Practice Circle', '2026-02-12', '17:30:00', 'Education Faculty Studio', 'Monthly practicum reflection huddle.', '{"host":"Teacher Training Hub"}', '2026-02-05 12:00:00', '2026-02-05 12:00:00');

ALTER TABLE `CalendarReminders`
    MODIFY `reminder_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

-- Q&A threads connected to learning support
INSERT INTO `Questions` (`question_id`, `user_id`, `title`, `content`, `category`, `views`, `created_at`, `updated_at`, `is_deleted`) VALUES
(1, 7, 'How do you scaffold group problem solving in calculus tutorials?', 'Looking for stepwise prompts that keep mixed-ability teams on track during integration practice.', 'Mathematics Instruction', 45, '2026-02-05 08:30:00', '2026-02-05 08:30:00', 0),
(2, 10, 'What metrics capture mentorship impact for first-year engineering students?', 'We are piloting a leadership mentoring program and need actionable indicators beyond attendance.', 'Mentoring', 36, '2026-02-05 12:10:00', '2026-02-05 12:10:00', 0),
(3, 12, 'Best practices for sustaining engagement in virtual practicum reflections?', 'Hybrid placements mean half of our reflections happen online. How do you keep discussions authentic?', 'Educational Technology', 28, '2026-02-05 14:45:00', '2026-02-05 14:45:00', 0);

ALTER TABLE `Questions`
    MODIFY `question_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

-- Shared solutions to the posted questions
INSERT INTO `Answers` (`answer_id`, `question_id`, `user_id`, `content`, `is_accepted`, `created_at`, `updated_at`, `is_deleted`) VALUES
(1, 1, 3, 'Map each problem to a structured thinking routine: clarify givens, sketch visuals, then assign roles for computation and checking.', 0, '2026-02-05 09:10:00', '2026-02-05 09:10:00', 0),
(2, 1, 5, 'Try progressive disclosure. Release hints in phases and have teams log metacognitive takeaways after each checkpoint.', 1, '2026-02-05 09:35:00', '2026-02-05 09:35:00', 0),
(3, 2, 9, 'Blend quantitative data (retention, GPA shifts) with qualitative mentor logs capturing confidence and belonging.', 1, '2026-02-05 12:45:00', '2026-02-05 12:45:00', 0),
(4, 3, 8, 'Use breakout choice boards so interns pick reflection modes—audio, visual, written—and rotate facilitation roles.', 0, '2026-02-05 15:10:00', '2026-02-05 15:10:00', 0);

ALTER TABLE `Answers`
    MODIFY `answer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- Voting signals for question threads
INSERT INTO `QuestionVotes` (`vote_id`, `question_id`, `user_id`, `vote_type`, `created_at`) VALUES
(1, 1, 3, 'upvote', '2026-02-05 09:05:00'),
(2, 1, 4, 'upvote', '2026-02-05 09:06:00'),
(3, 1, 5, 'upvote', '2026-02-05 09:08:00'),
(4, 2, 9, 'upvote', '2026-02-05 12:20:00'),
(5, 2, 11, 'upvote', '2026-02-05 12:25:00'),
(6, 3, 8, 'upvote', '2026-02-05 15:15:00'),
(7, 3, 12, 'upvote', '2026-02-05 15:18:00');

ALTER TABLE `QuestionVotes`
    MODIFY `vote_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

INSERT INTO `AnswerVotes` (`vote_id`, `answer_id`, `user_id`, `vote_type`, `created_at`) VALUES
(1, 1, 7, 'upvote', '2026-02-05 09:12:00'),
(2, 1, 4, 'upvote', '2026-02-05 09:14:00'),
(3, 2, 7, 'upvote', '2026-02-05 09:40:00'),
(4, 2, 3, 'upvote', '2026-02-05 09:42:00'),
(5, 3, 10, 'upvote', '2026-02-05 12:48:00'),
(6, 3, 11, 'upvote', '2026-02-05 12:55:00'),
(7, 4, 12, 'upvote', '2026-02-05 15:20:00');

ALTER TABLE `AnswerVotes`
    MODIFY `vote_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

-- Post visibility analytics for feeds
INSERT INTO `PostViews` (`view_id`, `post_id`, `user_id`, `viewed_at`) VALUES
(1, 1, 4, '2026-02-02 10:10:00'),
(2, 1, 5, '2026-02-02 10:12:00'),
(3, 3, 8, '2026-02-03 10:20:00'),
(4, 6, 12, '2026-02-05 09:15:00'),
(5, 6, 13, '2026-02-05 09:18:00'),
(6, 6, 14, '2026-02-05 09:19:00');

ALTER TABLE `PostViews`
    MODIFY `view_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
