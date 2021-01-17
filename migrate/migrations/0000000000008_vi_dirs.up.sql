CREATE VIEW `vi_dirs` AS
    SELECT `dirs`.`id` as `id`,
           `dirs`.`name` as `name`,
           `dirs`.`parent_id` as `parent_id`,
           `dirs`.`modified` as `modified`,
           `dirs_tree`.`reindexed` as `reindexed`,
           `dirs_tree`.`lft` as `lft`,
           `dirs_tree`.`rgt` as `rgt`
    FROM (`dirs` JOIN `dirs_tree` ON ((`dirs`.`id` = `dirs_tree`.`id`)))
