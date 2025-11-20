-- Thêm cột topic_id vào bảng posts và tạo khóa ngoại
ALTER TABLE posts
ADD COLUMN topic_id INT(11) NULL,
ADD CONSTRAINT fk_posts_topic_id
FOREIGN KEY (topic_id) REFERENCES topics(id)
ON DELETE SET NULL; 