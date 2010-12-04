SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE `tags` (
  `key` varchar(255) NOT NULL,
  `tag` varchar(255) NOT NULL,
  `servers` char(32) NOT NULL,
  UNIQUE KEY `tags` (`tag`,`key`,`servers`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;