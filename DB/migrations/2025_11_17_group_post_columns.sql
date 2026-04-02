-- Adds the columns and tables required for the enhanced group post types/polls.
-- Run this against the live database once per environment.

ALTER TABLE Post
    ADD COLUMN IF NOT EXISTS group_post_type ENUM('discussion','question','resource','poll','event','assignment') DEFAULT 'discussion' AFTER author_id,
    ADD COLUMN IF NOT EXISTS metadata JSON NULL AFTER group_post_type;

-- Backfill the new group_post_type column for existing rows so UI logic has sane values.
UPDATE Post
SET group_post_type = CASE
        WHEN post_type = 'poll' THEN 'poll'
        WHEN post_type = 'event' THEN 'event'
        ELSE 'discussion'
    END
WHERE group_post_type IS NULL;

CREATE TABLE IF NOT EXISTS GroupPostPollVote (
    vote_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    option_index TINYINT NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_poll_vote (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES Post(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);
