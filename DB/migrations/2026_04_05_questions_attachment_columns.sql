-- Adds missing question attachment columns required by QuestionModel and Popular view.
-- Safe for existing databases: adds each column only if it does not exist.

SET @has_attachment_name := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Questions'
      AND COLUMN_NAME = 'attachment_name'
);

SET @sql_attachment_name := IF(
    @has_attachment_name = 0,
    'ALTER TABLE Questions ADD COLUMN attachment_name VARCHAR(255) NULL AFTER content',
    'SELECT ''attachment_name already exists'' AS info'
);
PREPARE stmt_attachment_name FROM @sql_attachment_name;
EXECUTE stmt_attachment_name;
DEALLOCATE PREPARE stmt_attachment_name;

SET @has_attachment_path := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Questions'
      AND COLUMN_NAME = 'attachment_path'
);

SET @sql_attachment_path := IF(
    @has_attachment_path = 0,
    'ALTER TABLE Questions ADD COLUMN attachment_path VARCHAR(500) NULL AFTER attachment_name',
    'SELECT ''attachment_path already exists'' AS info'
);
PREPARE stmt_attachment_path FROM @sql_attachment_path;
EXECUTE stmt_attachment_path;
DEALLOCATE PREPARE stmt_attachment_path;

SET @has_attachment_type := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Questions'
      AND COLUMN_NAME = 'attachment_type'
);

SET @sql_attachment_type := IF(
    @has_attachment_type = 0,
    'ALTER TABLE Questions ADD COLUMN attachment_type VARCHAR(20) NULL AFTER attachment_path',
    'SELECT ''attachment_type already exists'' AS info'
);
PREPARE stmt_attachment_type FROM @sql_attachment_type;
EXECUTE stmt_attachment_type;
DEALLOCATE PREPARE stmt_attachment_type;

SET @has_attachment_size := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Questions'
      AND COLUMN_NAME = 'attachment_size'
);

SET @sql_attachment_size := IF(
    @has_attachment_size = 0,
    'ALTER TABLE Questions ADD COLUMN attachment_size INT NULL AFTER attachment_type',
    'SELECT ''attachment_size already exists'' AS info'
);
PREPARE stmt_attachment_size FROM @sql_attachment_size;
EXECUTE stmt_attachment_size;
DEALLOCATE PREPARE stmt_attachment_size;
