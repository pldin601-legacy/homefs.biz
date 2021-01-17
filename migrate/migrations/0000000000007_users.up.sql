CREATE TABLE `users`
(
    `uid`  int(11)     NOT NULL AUTO_INCREMENT,
    `user` varchar(64) NOT NULL,
    `pass` varchar(32) NOT NULL,
    PRIMARY KEY (`uid`),
    UNIQUE KEY `UN` (`user`)
) ENGINE = InnoDB;
