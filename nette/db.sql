-- Adminer 4.7.7 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `device`;
CREATE TABLE `device` (
  `id` varchar(12) NOT NULL,
  `int_id_grido` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` tinyint(4) NOT NULL,
  `name` varchar(64) NOT NULL,
  `order` tinyint(3) unsigned NOT NULL,
  `color` varchar(7) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `int_id_grido` (`int_id_grido`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `device_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `measurement`;
CREATE TABLE `measurement` (
  `device_id` varchar(12) NOT NULL,
  `ts` int(10) unsigned NOT NULL,
  `t1` float DEFAULT NULL,
  `t2` float DEFAULT NULL,
  `h` float DEFAULT NULL,
  `r` float DEFAULT NULL,
  `ws` float DEFAULT NULL,
  `wg` float DEFAULT NULL,
  `wd` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`device_id`,`ts`),
  CONSTRAINT `measurement_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `password` varchar(60) NOT NULL,
  `role` varchar(32) NOT NULL,
  `updatenames` tinyint(1) NOT NULL DEFAULT 1,
  `devicetoken` varchar(140) NOT NULL,
  `vendorid` varchar(36) NOT NULL,
  `phoneid` varchar(12) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- 2022-03-29 18:35:53