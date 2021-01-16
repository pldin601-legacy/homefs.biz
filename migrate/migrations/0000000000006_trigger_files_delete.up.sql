CREATE TRIGGER `trg_files_delete`
    AFTER DELETE
    ON `files`
    FOR EACH ROW
BEGIN
    SELECT `lft`, `rgt` INTO @lft, @rgt FROM `dirs_tree` WHERE `id` = OLD.`dir_id`;
    UPDATE `dirs`
    SET `size` = GREATEST(`size` - OLD.`size`, 0)
    WHERE `id` IN (
        SELECT `id`
        FROM `dirs_tree`
        WHERE `lft` <= @lft AND `rgt` >= @rgt);
END
