ALTER TABLE notifications
MODIFY type ENUM('like', 'comment', 'dislike') NOT NULL;
