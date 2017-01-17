CREATE DATABASE `homefs` CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `homefs`;

CREATE TABLE `dirs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `size` bigint(20) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `modified` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  FULLTEXT KEY `FT` (`name`),
  CONSTRAINT `dirs_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `dirs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44222 DEFAULT CHARSET=utf8;

CREATE TABLE `dirs_tree` (
  `id` int(11) NOT NULL,
  `reindexed` int(11) NOT NULL,
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `dirs_tree_ibfk_1` FOREIGN KEY (`id`) REFERENCES `dirs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `md5` varchar(32) NOT NULL,
  `extension` varchar(16) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `dir_id` int(11) NOT NULL,
  `modified` int(11) NOT NULL,
  `size` bigint(20) NOT NULL,
  `tags` text NOT NULL,
  `rnd` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `dir_id` (`dir_id`),
  KEY `EXTENSION` (`extension`),
  KEY `MD5` (`md5`),
  FULLTEXT KEY `FT` (`tags`),
  CONSTRAINT `files_ibfk_1` FOREIGN KEY (`dir_id`) REFERENCES `dirs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=694142 DEFAULT CHARSET=utf8;

CREATE TRIGGER `trg_dirs_insert` AFTER INSERT ON `dirs` FOR EACH ROW BEGIN
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
  REPLACE INTO dirs_tree VALUES(NEW.id, 0, @newlft, @newrgt);
END;


CREATE TABLE `cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wavedata` varchar(32) NOT NULL,
  `cover` varchar(32) NOT NULL,
  `thumbnail` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `cache_ibfk_1` FOREIGN KEY (`id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

