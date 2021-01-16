CREATE TABLE `waveform`
(
    `id`    int(11)        NOT NULL AUTO_INCREMENT,
    `data`  mediumblob     NOT NULL,
    `scale` decimal(10, 4) NOT NULL DEFAULT '1.0000',
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;
