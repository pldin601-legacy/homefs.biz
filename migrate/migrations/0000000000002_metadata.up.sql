CREATE TABLE `metadata`
(
    `id`           int(11) NOT NULL,
    `artist`       varchar(255)   DEFAULT NULL,
    `title`        varchar(255)   DEFAULT NULL,
    `duration`     decimal(11, 2) DEFAULT NULL,
    `bitrate`      decimal(11, 0) DEFAULT NULL,
    `width`        int(11)        DEFAULT NULL,
    `height`       int(11)        DEFAULT NULL,
    `album`        varchar(255)   DEFAULT NULL,
    `genre`        varchar(255)   DEFAULT NULL,
    `date`         varchar(255)   DEFAULT NULL,
    `album_artist` varchar(255)   DEFAULT NULL,
    `tracknumber`  varchar(255)   DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `ARTIST` (`artist`),
    FOREIGN KEY (`id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;
