-- Add topic_id column to posts table
ALTER TABLE posts
ADD COLUMN topic_id INT(11) NULL,
ADD CONSTRAINT fk_posts_topic_id
FOREIGN KEY (topic_id) REFERENCES topics(id)
ON DELETE SET NULL; 