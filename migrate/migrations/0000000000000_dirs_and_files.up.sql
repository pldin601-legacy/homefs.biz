CREATE TABLE `dirs`
(
    `id`        int(11)      NOT NULL AUTO_INCREMENT,
    `name`      varchar(255) NOT NULL,
    `size`      bigint(20)   NOT NULL,
    `parent_id` int(11) DEFAULT NULL,
    `modified`  int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `parent_id` (`parent_id`),
    FULLTEXT KEY `FT` (`name`),
    FOREIGN KEY (`parent_id`) REFERENCES `dirs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE `dirs_tree`
(
    `id`        int(11) NOT NULL,
    `reindexed` int(11) NOT NULL,
    `lft`       int(11) NOT NULL,
    `rgt`       int(11) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id`) REFERENCES `dirs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE `files`
(
    `id`        int(11)                                          NOT NULL AUTO_INCREMENT,
    `name`      varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
    `md5`       varchar(32)                                      NOT NULL,
    `extension` varchar(16) CHARACTER SET utf8 COLLATE utf8_bin           DEFAULT '',
    `dir_id`    int(11)                                          NOT NULL,
    `modified`  int(11)                                          NOT NULL,
    `size`      bigint(20)                                       NOT NULL,
    `tags`      text                                             NOT NULL,
    `rnd`       int(11)                                          NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `dir_id` (`dir_id`),
    KEY `EXTENSION` (`extension`),
    KEY `MD5` (`md5`),
    FULLTEXT KEY `FT` (`tags`),
    FOREIGN KEY (`dir_id`) REFERENCES `dirs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;
