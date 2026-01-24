-- Migration: Add GroupPostPollVote table for poll voting
-- Run this SQL in your database to enable poll vote persistence

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

-- Verify the table was created
SELECT 'GroupPostPollVote table created successfully!' AS status;
