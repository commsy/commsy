CREATE TABLE IF NOT EXISTS `portfolio` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8 NOT NULL,
  `description` mediumtext CHARACTER SET utf8 NOT NULL,
  `modifictaion_date` datetime NOT NULL,
  `deletion_date` datetime NOT NULL,
  `rows` int(11) NOT NULL DEFAULT '0',
  `columns` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`)
);



CREATE TABLE IF NOT EXISTS `user_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT '0',
  `u_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`p_id`,`u_id`)
);



CREATE TABLE IF NOT EXISTS `tag_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT '0',
  `t_id` int(11) NOT NULL DEFAULT '0',
  `row` int(11) NOT NULL DEFAULT '0',
  `column` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`p_id`,`t_id`),
  KEY `row` (`row`,`column`)
);


CREATE TABLE IF NOT EXISTS `annotation_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT '0',
  `a_id` int(11) NOT NULL DEFAULT '0',
  `row` int(11) NOT NULL DEFAULT '0',
  `column` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`p_id`,`a_id`),
  KEY `row` (`row`,`column`)
);