CREATE TRIGGER `trg_files_insert`
    AFTER INSERT
    ON `files`
    FOR EACH ROW
BEGIN
    SELECT `lft`, `rgt` INTO @lft, @rgt FROM `dirs_tree` WHERE `id` = NEW.`dir_id`;
    UPDATE `dirs`
    SET `size` = `size` + NEW.`size`
    WHERE `id` IN (
        SELECT `id`
        FROM `dirs_tree`
        WHERE `lft` <= @lft AND `rgt` >= @rgt);
END
