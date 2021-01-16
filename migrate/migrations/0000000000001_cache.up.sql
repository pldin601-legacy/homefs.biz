CREATE TABLE `cache`
(
    `id`        int(11)     NOT NULL AUTO_INCREMENT,
    `wavedata`  varchar(32) NOT NULL,
    `cover`     varchar(32) NOT NULL,
    `thumbnail` varchar(32) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;
