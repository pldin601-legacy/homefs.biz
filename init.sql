
#
# CREATE TABLE `config_directories`
# (
#     `dir_name`     varchar(255) NOT NULL,
#     `dir_location` varchar(255) NOT NULL,
#     PRIMARY KEY (`dir_name`)
# ) ENGINE = InnoDB
#   DEFAULT CHARSET = utf8;
#
#
# CREATE TABLE `config_inputs`
# (
#     `input_id`       int(11)      NOT NULL AUTO_INCREMENT,
#     `input_dir`      varchar(255) NOT NULL,
#     `initial_rights` varchar(255) NOT NULL,
#     PRIMARY KEY (`input_id`)
# ) ENGINE = InnoDB
#   AUTO_INCREMENT = 6
#   DEFAULT CHARSET = utf8;
#
#
# CREATE TABLE `config_musicplayer`
# (
#     `rule_id`      int(11)           NOT NULL AUTO_INCREMENT,
#     `ip_regexp`    varchar(255)      NOT NULL,
#     `bitrate`      int(11)           NOT NULL,
#     `channels`     int(11)           NOT NULL,
#     `frequency`    int(11)           NOT NULL,
#     `use_throttle` enum ('yes','no') NOT NULL DEFAULT 'no',
#     `comment`      varchar(255)      NOT NULL,
#     PRIMARY KEY (`rule_id`)
# ) ENGINE = InnoDB;


CREATE TABLE `extensions`
(
    `extension` varchar(32)                                                                     NOT NULL,
    `type`      enum ('image','audio','disk-image','archive','temp','document','video','other') NOT NULL DEFAULT 'other',
    PRIMARY KEY (`extension`)
) ENGINE = InnoDB;



CREATE TABLE `statistics`
(
    `parameter` varchar(32) NOT NULL,
    `value`     varchar(64) NOT NULL,
    PRIMARY KEY (`parameter`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;


DELIMITER ;;
CREATE PROCEDURE `index_update`()
UPDATE `statistics`
SET `value` = NOW()
WHERE `parameter` = 'index_mtime' ;;
DELIMITER ;




DELIMITER ;;
CREATE TRIGGER `files_update`
    AFTER UPDATE
    ON `files`
    FOR EACH ROW CALL index_update();;
DELIMITER ;



# CREATE TABLE `proc_lores_making`
# (
#     `job_md5`     varchar(32)                                 NOT NULL,
#     `job_title`   varchar(255)                                NOT NULL,
#     `job_status`  enum ('queued','processing','done','error') NOT NULL,
#     `job_percent` int(11)                                     NOT NULL,
#     PRIMARY KEY (`job_md5`)
# ) ENGINE = InnoDB
#   DEFAULT CHARSET = utf8;


# CREATE TABLE `search_requests`
# (
#     `count`   int(11)      NOT NULL DEFAULT '1',
#     `request` varchar(255) NOT NULL,
#     PRIMARY KEY (`request`),
#     KEY `REQ` (`request`)
# ) ENGINE = InnoDB
#   DEFAULT CHARSET = utf8;


# CREATE TABLE `system_patterns`
# (
#     `pattern_id`   varchar(255) NOT NULL,
#     `pattern_data` varchar(255) NOT NULL,
#     PRIMARY KEY (`pattern_id`)
# ) ENGINE = InnoDB
#   DEFAULT CHARSET = utf8;


# CREATE TABLE `users`
# (
#     `uid`  int(11)     NOT NULL AUTO_INCREMENT,
#     `user` varchar(64) NOT NULL,
#     `pass` varchar(32) NOT NULL,
#     PRIMARY KEY (`uid`),
#     UNIQUE KEY `UN` (`user`)
# ) ENGINE = InnoDB
#   AUTO_INCREMENT = 5
#   DEFAULT CHARSET = utf8;
