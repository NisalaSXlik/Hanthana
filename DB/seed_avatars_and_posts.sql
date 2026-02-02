-- Seed sample avatars and update three users' profile pictures
-- Adjust user_ids and filenames if your data differs

-- Put image files into public/images/avatars/ with these names
-- default.png, user1.jpg, user2.jpg, user3.jpg

UPDATE Users SET profile_picture = 'user1.jpg' WHERE user_id = 1;
UPDATE Users SET profile_picture = 'user2.jpg' WHERE user_id = 2;
UPDATE Users SET profile_picture = 'user3.jpg' WHERE user_id = 3;

-- Create 3 posts from 3 different authors (author_id = 1,2,3)
INSERT INTO Post (content, author_id, created_at)
VALUES
  ('Weekend getaway to Unawatuna Beach 🏖️', 1, NOW()),
  ('Caught the sunrise at Ella Rock 🌄', 2, NOW()),
  ('Exploring the tea trails in Nuwara Eliya 🍃', 3, NOW());

-- Optionally attach an image to each post (adjust post IDs if auto-increment offset differs)
-- Assuming these three are the latest posts
INSERT INTO PostMedia (post_id, uploader_id, file_name, file_type, file_url)
SELECT p.post_id, p.author_id, 'post1.jpg', 'image', 'post1.jpg'
FROM Post p ORDER BY p.post_id DESC LIMIT 1 OFFSET 2; -- first of the three

INSERT INTO PostMedia (post_id, uploader_id, file_name, file_type, file_url)
SELECT p.post_id, p.author_id, 'post2.jpg', 'image', 'post2.jpg'
FROM Post p ORDER BY p.post_id DESC LIMIT 1 OFFSET 1; -- second of the three

INSERT INTO PostMedia (post_id, uploader_id, file_name, file_type, file_url)
SELECT p.post_id, p.author_id, 'post3.jpg', 'image', 'post3.jpg'
FROM Post p ORDER BY p.post_id DESC LIMIT 1 OFFSET 0; -- most recent
