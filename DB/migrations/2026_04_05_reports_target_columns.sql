-- Adds missing report target columns required by ReportModel queries.
-- Safe to run on existing databases without dropping data.

SET @has_media := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Reports'
      AND COLUMN_NAME = 'reported_media_id'
);

SET @sql_media := IF(
    @has_media = 0,
    'ALTER TABLE Reports ADD COLUMN reported_media_id INT NULL AFTER reported_group_id',
    'SELECT ''reported_media_id already exists'' AS info'
);
PREPARE stmt_media FROM @sql_media;
EXECUTE stmt_media;
DEALLOCATE PREPARE stmt_media;

SET @has_question := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'Reports'
      AND COLUMN_NAME = 'reported_question_id'
);

SET @sql_question := IF(
    @has_question = 0,
    'ALTER TABLE Reports ADD COLUMN reported_question_id INT NULL AFTER reported_media_id',
    'SELECT ''reported_question_id already exists'' AS info'
);
PREPARE stmt_question FROM @sql_question;
EXECUTE stmt_question;
DEALLOCATE PREPARE stmt_question;
