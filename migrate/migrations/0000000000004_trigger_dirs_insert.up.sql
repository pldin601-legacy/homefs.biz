CREATE TRIGGER `trg_dirs_insert`
    AFTER INSERT
    ON `dirs`
    FOR EACH ROW
BEGIN
    IF NEW.parent_id != 0 THEN
        SELECT rgt INTO @myVar FROM dirs_tree WHERE id = NEW.parent_id;
        SET @newlft = IFNULL(@myVar, 0);
        SET @newrgt = IFNULL(@myVar, 0) + 1;
        UPDATE dirs_tree SET lft = lft + 2 WHERE lft >= @myVar;
        UPDATE dirs_tree SET rgt = rgt + 2 WHERE rgt >= @myVar;
    ELSE
        SELECT MAX(rgt) INTO @myVar FROM dirs_tree;
        SET @newlft = IFNULL(@myVar, 0) + 1;
        SET @newrgt = IFNULL(@myVar, 0) + 2;
    END IF;
    REPLACE INTO dirs_tree VALUES (NEW.id, 0, @newlft, @newrgt);
END
