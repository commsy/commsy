# ************************************************************
# Sequel Pro SQL dump
# Version 4004
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: commsy.dev (MySQL 5.1.63-0+squeeze1)
# Datenbank: commsy_vanilla
# Erstellungsdauer: 2013-03-25 12:40:33 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Export von Tabelle annotation_portfolio
# ------------------------------------------------------------

CREATE TABLE `annotation_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT '0',
  `a_id` int(11) NOT NULL DEFAULT '0',
  `row` int(11) NOT NULL DEFAULT '0',
  `column` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`p_id`,`a_id`),
  KEY `row` (`row`,`column`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle annotations
# ------------------------------------------------------------

CREATE TABLE `annotations` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `linked_item_id` int(11) NOT NULL DEFAULT '0',
  `linked_version_id` int(11) NOT NULL DEFAULT '0',
  `extras` text,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `linked_item_id` (`linked_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle announcement
# ------------------------------------------------------------

CREATE TABLE `announcement` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `enddate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `extras` text,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle assessments
# ------------------------------------------------------------

CREATE TABLE `assessments` (
  `item_id` int(11) NOT NULL,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `item_link_id` int(11) NOT NULL,
  `assessment` int(2) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `item_link_id` (`item_link_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `deleter_id` (`deleter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle auth
# ------------------------------------------------------------

CREATE TABLE `auth` (
  `commsy_id` int(11) NOT NULL DEFAULT '0',
  `user_id` varchar(32) NOT NULL,
  `password_md5` varchar(32) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `language` varchar(10) NOT NULL,
  PRIMARY KEY (`commsy_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `auth` WRITE;
/*!40000 ALTER TABLE `auth` DISABLE KEYS */;

INSERT INTO `auth` (`commsy_id`, `user_id`, `password_md5`, `firstname`, `lastname`, `email`, `language`)
VALUES
	(99,'root','63a9f0ea7bb98050796b649e85481845','CommSy','Administrator','','de');

/*!40000 ALTER TABLE `auth` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle auth_source
# ------------------------------------------------------------

CREATE TABLE `auth_source` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `extras` text,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `auth_source` WRITE;
/*!40000 ALTER TABLE `auth_source` DISABLE KEYS */;

INSERT INTO `auth_source` (`item_id`, `context_id`, `creator_id`, `modifier_id`, `deleter_id`, `creation_date`, `modification_date`, `deletion_date`, `title`, `extras`)
VALUES
	(100,99,99,99,NULL,'2006-09-14 12:32:24','2006-09-14 12:32:24',NULL,'CommSy','a:4:{s:14:\"COMMSY_DEFAULT\";s:1:\"1\";s:6:\"SOURCE\";s:5:\"MYSQL\";s:13:\"CONFIGURATION\";a:5:{s:11:\"ADD_ACCOUNT\";s:1:\"0\";s:13:\"CHANGE_USERID\";s:1:\"0\";s:14:\"DELETE_ACCOUNT\";s:1:\"0\";s:15:\"CHANGE_USERDATA\";s:1:\"1\";s:15:\"CHANGE_PASSWORD\";s:1:\"1\";}s:4:\"SHOW\";s:1:\"1\";}');

/*!40000 ALTER TABLE `auth_source` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle dates
# ------------------------------------------------------------

CREATE TABLE `dates` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `start_time` varchar(100) DEFAULT NULL,
  `end_time` varchar(100) DEFAULT NULL,
  `start_day` varchar(100) NOT NULL,
  `end_day` varchar(100) DEFAULT NULL,
  `place` varchar(100) DEFAULT NULL,
  `datetime_start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `datetime_end` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `date_mode` tinyint(4) NOT NULL DEFAULT '0',
  `color` varchar(255) DEFAULT NULL,
  `recurrence_id` int(11) DEFAULT NULL,
  `recurrence_pattern` text,
  `extras` text,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle discussionarticles
# ------------------------------------------------------------

CREATE TABLE `discussionarticles` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `discussion_id` int(11) NOT NULL DEFAULT '0',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` mediumtext,
  `position` varchar(255) NOT NULL DEFAULT '1',
  `extras` text,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle discussions
# ------------------------------------------------------------

CREATE TABLE `discussions` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `latest_article_item_id` int(11) DEFAULT NULL,
  `latest_article_modification_date` datetime DEFAULT NULL,
  `status` int(2) NOT NULL DEFAULT '1',
  `discussion_type` varchar(10) NOT NULL DEFAULT 'simple',
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `extras` text,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle external_viewer
# ------------------------------------------------------------

CREATE TABLE `external_viewer` (
  `item_id` int(11) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  KEY `item_id` (`item_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle external2commsy_id
# ------------------------------------------------------------

CREATE TABLE `external2commsy_id` (
  `external_id` varchar(255) NOT NULL,
  `source_system` varchar(60) NOT NULL,
  `commsy_id` int(11) NOT NULL,
  PRIMARY KEY (`external_id`,`source_system`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle file_multi_upload
# ------------------------------------------------------------

CREATE TABLE `file_multi_upload` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(150) NOT NULL,
  `file_array` text NOT NULL,
  `cid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle files
# ------------------------------------------------------------

CREATE TABLE `files` (
  `files_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `size` int(30) NOT NULL DEFAULT '0',
  `has_html` enum('0','1','2') NOT NULL DEFAULT '0',
  `scan` tinyint(1) NOT NULL DEFAULT '-1',
  `extras` text,
  `temp_upload_session_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`files_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle hash
# ------------------------------------------------------------

CREATE TABLE `hash` (
  `user_item_id` int(11) NOT NULL,
  `rss` char(32) DEFAULT NULL,
  `ical` char(32) DEFAULT NULL,
  PRIMARY KEY (`user_item_id`),
  KEY `rss` (`rss`),
  KEY `ical` (`ical`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle homepage_link_page_page
# ------------------------------------------------------------

CREATE TABLE `homepage_link_page_page` (
  `link_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_item_id` int(11) NOT NULL DEFAULT '0',
  `to_item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) NOT NULL DEFAULT '0',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modifier_id` int(11) NOT NULL DEFAULT '0',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `sorting_place` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`link_id`),
  KEY `from_item_id` (`from_item_id`),
  KEY `context_id` (`context_id`),
  KEY `to_item_id` (`to_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle homepage_page
# ------------------------------------------------------------

CREATE TABLE `homepage_page` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `page_type` varchar(10) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle item_backup
# ------------------------------------------------------------

CREATE TABLE `item_backup` (
  `item_id` int(11) NOT NULL,
  `backup_date` datetime NOT NULL,
  `modification_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `public` tinyint(11) NOT NULL,
  `special` text CHARACTER SET ucs2 NOT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle item_link_file
# ------------------------------------------------------------

CREATE TABLE `item_link_file` (
  `item_iid` int(11) NOT NULL DEFAULT '0',
  `item_vid` int(11) NOT NULL DEFAULT '0',
  `file_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_iid`,`item_vid`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle items
# ------------------------------------------------------------

CREATE TABLE `items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `type` varchar(15) NOT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;

INSERT INTO `items` (`item_id`, `context_id`, `type`, `deleter_id`, `deletion_date`, `modification_date`)
VALUES
	(98,99,'user',NULL,NULL,NULL),
	(99,0,'server',NULL,NULL,'2013-03-25 13:39:22'),
	(100,99,'auth_source',NULL,NULL,'2006-09-14 12:32:24');

/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle labels
# ------------------------------------------------------------

CREATE TABLE `labels` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `type` varchar(15) NOT NULL,
  `extras` text,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle link_items
# ------------------------------------------------------------

CREATE TABLE `link_items` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `first_item_id` int(11) NOT NULL DEFAULT '0',
  `first_item_type` varchar(15) DEFAULT NULL,
  `second_item_id` int(11) NOT NULL DEFAULT '0',
  `second_item_type` varchar(15) DEFAULT NULL,
  `sorting_place` int(11) DEFAULT NULL,
  `extras` text,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `first_item_id` (`first_item_id`),
  KEY `second_item_id` (`second_item_id`),
  KEY `first_item_type` (`first_item_type`),
  KEY `second_item_type` (`second_item_type`),
  KEY `deletion_date` (`deletion_date`),
  KEY `deleter_id` (`deleter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle link_modifier_item
# ------------------------------------------------------------

CREATE TABLE `link_modifier_item` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`,`modifier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `link_modifier_item` WRITE;
/*!40000 ALTER TABLE `link_modifier_item` DISABLE KEYS */;

INSERT INTO `link_modifier_item` (`item_id`, `modifier_id`)
VALUES
	(99,98);

/*!40000 ALTER TABLE `link_modifier_item` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle links
# ------------------------------------------------------------

CREATE TABLE `links` (
  `from_item_id` int(11) NOT NULL DEFAULT '0',
  `from_version_id` int(11) NOT NULL DEFAULT '0',
  `to_item_id` int(11) NOT NULL DEFAULT '0',
  `to_version_id` int(11) NOT NULL DEFAULT '0',
  `link_type` char(30) NOT NULL,
  `context_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `x` int(11) DEFAULT NULL,
  `y` int(11) DEFAULT NULL,
  PRIMARY KEY (`from_item_id`,`from_version_id`,`to_item_id`,`to_version_id`,`link_type`),
  KEY `context_id` (`context_id`),
  KEY `link_type` (`link_type`),
  KEY `from_item_id` (`from_item_id`),
  KEY `from_version_id` (`from_version_id`),
  KEY `to_item_id` (`to_item_id`),
  KEY `to_version_id` (`to_version_id`),
  KEY `link_type_2` (`link_type`),
  KEY `from_item_id_2` (`from_item_id`),
  KEY `from_version_id_2` (`from_version_id`),
  KEY `to_item_id_2` (`to_item_id`),
  KEY `to_version_id_2` (`to_version_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle log
# ------------------------------------------------------------

CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) DEFAULT NULL,
  `agent` varchar(250) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `request` varchar(250) DEFAULT NULL,
  `post_content` mediumtext,
  `method` varchar(10) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  `ulogin` varchar(250) DEFAULT NULL,
  `cid` int(11) DEFAULT NULL,
  `module` varchar(250) DEFAULT NULL,
  `fct` varchar(250) DEFAULT NULL,
  `param` varchar(250) DEFAULT NULL,
  `iid` int(11) DEFAULT NULL,
  `queries` smallint(6) DEFAULT NULL,
  `time` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timestamp` (`timestamp`),
  KEY `cid` (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;

INSERT INTO `log` (`id`, `ip`, `agent`, `timestamp`, `request`, `post_content`, `method`, `uid`, `ulogin`, `cid`, `module`, `fct`, `param`, `iid`, `queries`, `time`)
VALUES
	(1,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:37:42','/commsy.php?','NULL','GET',0,'',99,'home','index','',0,0,0),
	(2,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:37:42','/commsy.php?cid=99&mod=home&fct=index&jscheck=1&isJS=1&SID=3667347c4e62464c210eb97dc08156a5&https=-1&flash=1','NULL','GET',0,'guest',99,'home','index','jscheck=1&isJS=1&https=-1&flash=1',0,12,0.593),
	(3,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:37:48','/commsy.php?cid=99&mod=home&fct=index&jscheck=1&isJS=1&https=-1&flash=1','NULL','GET',98,'root',99,'home','index','jscheck=1&isJS=1&https=-1&flash=1',0,12,0.198),
	(4,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:37:50','/commsy.php?cid=99&mod=configuration&fct=index','NULL','GET',98,'root',99,'configuration','index','',0,9,0.247),
	(5,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:37:53','/commsy.php?cid=99&mod=configuration&fct=update','NULL','GET',98,'root',99,'configuration','update','',0,13,0.601),
	(6,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:37:53','/commsy.php?cid=99&mod=configuration&fct=update','NULL','GET',98,'root',99,'configuration','update','',0,12,0.441),
	(7,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:37:58','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<6_3_3_TO_6_5_0>1</6_3_3_TO_6_5_0>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,32,0.362),
	(8,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:03','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<6_5_0_TO_6_5_1>1</6_5_0_TO_6_5_1>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,34,0.373),
	(9,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:09','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<6_5_1_TO_7_0_0>1</6_5_1_TO_7_0_0>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,39,0.313),
	(10,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:16','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_0_3_TO_7_0_4>1</7_0_3_TO_7_0_4>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,26,0.328),
	(11,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:19','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_0_4_TO_7_1_0>1</7_0_4_TO_7_1_0>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,20,0.301),
	(12,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:23','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_1_0_TO_7_1_1>1</7_1_0_TO_7_1_1>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,19,0.285),
	(13,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:28','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_1_1_TO_7_1_2>1</7_1_1_TO_7_1_2>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,18,0.28),
	(14,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:32','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_1_2_TO_7_2_0>1</7_1_2_TO_7_2_0>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,32,0.312),
	(15,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:36','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_2_0_TO_7_2_1>1</7_2_0_TO_7_2_1>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,43,0.294),
	(16,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:40','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_2_1_TO_7_2_2>1</7_2_1_TO_7_2_2>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,21,0.262),
	(17,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:43','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_2_2_TO_7_2_3>1</7_2_2_TO_7_2_3>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,20,0.277),
	(18,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:46','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_2_3_TO_7_2_4>1</7_2_3_TO_7_2_4>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,17,0.21),
	(19,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:50','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_2_4_TO_7_3_0>1</7_2_4_TO_7_3_0>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,20,0.263),
	(20,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:54','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_3_0_TO_7_5_0>1</7_3_0_TO_7_5_0>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,134,0.352),
	(21,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:38:58','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_5_0_TO_7_5_1>1</7_5_0_TO_7_5_1>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,17,0.205),
	(22,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:39:02','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_5_1_TO_7_5_2>1</7_5_1_TO_7_5_2>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,18,0.297),
	(23,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:39:04','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_5_2_TO_7_5_3>1</7_5_2_TO_7_5_3>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,18,0.191),
	(24,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:39:10','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_5_3_TO_7_5_4>1</7_5_3_TO_7_5_4>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,20,0.249),
	(25,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:39:13','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_5_4_TO_7_5_5>1</7_5_4_TO_7_5_5>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,31,0.258),
	(26,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:39:16','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<7_5_5_TO_8_0_0>1</7_5_5_TO_8_0_0>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,187,0.395),
	(27,'192.168.2.12','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22','2013-03-25 13:39:22','/commsy.php?cid=99&mod=configuration&fct=update','<SECURITY_TOKEN>eeebc83a3eb1338d16e7a9c05771977b</SECURITY_TOKEN>\n<8_0_0_TO_8_0_1>1</8_0_0_TO_8_0_1>\n<OPTION>Update durchführen</OPTION>\n','POST',98,'root',99,'configuration','update','',0,23,0.241);

/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle log_ads
# ------------------------------------------------------------

CREATE TABLE `log_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) DEFAULT NULL,
  `aim` varchar(255) NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle log_archive
# ------------------------------------------------------------

CREATE TABLE `log_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) DEFAULT NULL,
  `agent` varchar(250) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `request` varchar(250) DEFAULT NULL,
  `post_content` mediumtext,
  `method` varchar(10) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  `ulogin` varchar(250) DEFAULT NULL,
  `cid` int(11) DEFAULT NULL,
  `module` varchar(250) DEFAULT NULL,
  `fct` varchar(250) DEFAULT NULL,
  `param` varchar(250) DEFAULT NULL,
  `iid` int(11) DEFAULT NULL,
  `queries` smallint(6) DEFAULT NULL,
  `time` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ulogin` (`ulogin`),
  KEY `cid` (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle log_error
# ------------------------------------------------------------

CREATE TABLE `log_error` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `number` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `message` mediumtext NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `referer` varchar(255) DEFAULT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `context` int(11) NOT NULL,
  `module` varchar(255) NOT NULL,
  `function` varchar(255) NOT NULL,
  `user` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle log_message_tag
# ------------------------------------------------------------

CREATE TABLE `log_message_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) NOT NULL,
  `version` varchar(50) NOT NULL,
  `datetime` datetime NOT NULL,
  `language` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `log_message_tag` WRITE;
/*!40000 ALTER TABLE `log_message_tag` DISABLE KEYS */;

INSERT INTO `log_message_tag` (`id`, `tag`, `version`, `datetime`, `language`)
VALUES
	(1,'USAGE_INFO_TEXT_SERVER_FOR_CONFIGURATION_UPDATE_FORM','8.0.1','2013-03-25 13:37:53','de');

/*!40000 ALTER TABLE `log_message_tag` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle materials
# ------------------------------------------------------------

CREATE TABLE `materials` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modifier_id` int(11) DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `author` varchar(200) DEFAULT NULL,
  `publishing_date` varchar(20) DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `world_public` smallint(2) NOT NULL DEFAULT '0',
  `extras` text,
  `new_hack` tinyint(1) NOT NULL DEFAULT '0',
  `copy_of` int(11) DEFAULT NULL,
  `workflow_status` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '3_none',
  `workflow_resubmission_date` datetime DEFAULT NULL,
  `workflow_validity_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`,`version_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `modifier_id` (`modifier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle noticed
# ------------------------------------------------------------

CREATE TABLE `noticed` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle portal
# ------------------------------------------------------------

CREATE TABLE `portal` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `extras` text,
  `status` varchar(20) NOT NULL,
  `activity` int(11) NOT NULL DEFAULT '0',
  `type` varchar(10) NOT NULL DEFAULT 'portal',
  `is_open_for_guests` tinyint(4) NOT NULL DEFAULT '1',
  `url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle portfolio
# ------------------------------------------------------------

CREATE TABLE `portfolio` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `creator_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID of private room user',
  `modifier_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID of private room user',
  `title` varchar(255) NOT NULL,
  `description` mediumtext NOT NULL,
  `template` tinyint(4) NOT NULL DEFAULT '-1',
  `creation_date` datetime NOT NULL,
  `modification_date` datetime NOT NULL,
  `deletion_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `creator_id` (`creator_id`),
  KEY `modifier_id` (`modifier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='General Portfolio Information';



# Export von Tabelle reader
# ------------------------------------------------------------

CREATE TABLE `reader` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle room
# ------------------------------------------------------------

CREATE TABLE `room` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `extras` text,
  `status` varchar(20) NOT NULL,
  `activity` int(11) NOT NULL DEFAULT '0',
  `type` varchar(20) NOT NULL DEFAULT 'project',
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `is_open_for_guests` tinyint(4) NOT NULL DEFAULT '0',
  `continuous` tinyint(4) NOT NULL DEFAULT '-1',
  `template` tinyint(4) NOT NULL DEFAULT '-1',
  `contact_persons` varchar(255) DEFAULT NULL,
  `description` text,
  `room_description` varchar(10000) DEFAULT NULL,
  `lastlogin` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `activity` (`activity`),
  KEY `deletion_date` (`deletion_date`),
  KEY `deleter_id` (`deleter_id`),
  KEY `room_description` (`room_description`(333)),
  KEY `contact_persons` (`contact_persons`),
  KEY `title` (`title`),
  KEY `modifier_id` (`modifier_id`),
  KEY `lastlogin` (`lastlogin`),
  KEY `status_2` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle room_privat
# ------------------------------------------------------------

CREATE TABLE `room_privat` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `extras` text,
  `status` varchar(20) NOT NULL,
  `activity` int(11) NOT NULL DEFAULT '0',
  `type` varchar(20) NOT NULL DEFAULT 'privateroom',
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `is_open_for_guests` tinyint(4) NOT NULL DEFAULT '0',
  `continuous` tinyint(4) NOT NULL DEFAULT '-1',
  `template` tinyint(4) NOT NULL DEFAULT '-1',
  `contact_persons` varchar(255) DEFAULT NULL,
  `description` text,
  `lastlogin` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `status` (`status`),
  KEY `creator_id` (`creator_id`),
  KEY `lastlogin` (`lastlogin`),
  KEY `status_2` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle search_index
# ------------------------------------------------------------

CREATE TABLE `search_index` (
  `si_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `si_sw_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `si_item_id` int(11) NOT NULL DEFAULT '0',
  `si_item_type` varchar(15) NOT NULL,
  `si_count` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`si_id`),
  UNIQUE KEY `un_si_sw_id` (`si_item_id`,`si_sw_id`,`si_item_type`),
  KEY `si_sw_id` (`si_sw_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle search_time
# ------------------------------------------------------------

CREATE TABLE `search_time` (
  `st_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `st_item_id` int(11) NOT NULL DEFAULT '0',
  `st_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`st_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle search_word
# ------------------------------------------------------------

CREATE TABLE `search_word` (
  `sw_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `sw_word` varchar(32) NOT NULL DEFAULT '',
  `sw_lang` varchar(5) NOT NULL,
  PRIMARY KEY (`sw_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle section
# ------------------------------------------------------------

CREATE TABLE `section` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `creation_date` datetime DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `number` smallint(6) NOT NULL DEFAULT '0',
  `material_item_id` int(11) NOT NULL DEFAULT '0',
  `extras` text,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`,`version_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `material_item_id` (`material_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle server
# ------------------------------------------------------------

CREATE TABLE `server` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `extras` text,
  `status` varchar(20) NOT NULL,
  `activity` int(11) NOT NULL DEFAULT '0',
  `type` varchar(10) NOT NULL DEFAULT 'server',
  `is_open_for_guests` tinyint(4) NOT NULL DEFAULT '1',
  `url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `server` WRITE;
/*!40000 ALTER TABLE `server` DISABLE KEYS */;

INSERT INTO `server` (`item_id`, `context_id`, `creator_id`, `modifier_id`, `deleter_id`, `creation_date`, `modification_date`, `deletion_date`, `title`, `extras`, `status`, `activity`, `type`, `is_open_for_guests`, `url`)
VALUES
	(99,0,99,0,NULL,'2006-09-13 12:16:38','2013-03-25 13:39:22',NULL,'CommSy-Server','a:3:{s:8:\"HOMECONF\";s:0:\"\";s:12:\"DEFAULT_AUTH\";s:3:\"100\";s:7:\"VERSION\";s:5:\"8.0.1\";}','1',61,'server',1,'');

/*!40000 ALTER TABLE `server` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle session
# ------------------------------------------------------------

CREATE TABLE `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(150) NOT NULL,
  `session_key` varchar(30) NOT NULL,
  `session_value` longtext NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `session` WRITE;
/*!40000 ALTER TABLE `session` DISABLE KEYS */;

INSERT INTO `session` (`id`, `session_id`, `session_key`, `session_value`, `created`)
VALUES
	(1,'3667347c4e62464c210eb97dc08156a5','new_session_type','a:7:{s:7:\"user_id\";s:5:\"guest\";s:9:\"commsy_id\";i:99;s:6:\"cookie\";s:1:\"1\";s:10:\"javascript\";i:1;s:5:\"https\";i:-1;s:5:\"flash\";i:1;s:7:\"history\";a:1:{i:0;a:4:{s:7:\"context\";i:99;s:6:\"module\";s:4:\"home\";s:8:\"function\";s:5:\"index\";s:9:\"parameter\";a:4:{s:7:\"jscheck\";s:1:\"1\";s:4:\"isJS\";s:1:\"1\";s:5:\"https\";s:2:\"-1\";s:5:\"flash\";s:1:\"1\";}}}}','2013-03-25 13:37:42'),
	(2,'da24b11d81405846ff4c327ec947ad27','new_session_type','a:8:{s:7:\"user_id\";s:4:\"root\";s:6:\"cookie\";i:1;s:10:\"javascript\";i:1;s:5:\"https\";i:-1;s:5:\"flash\";i:1;s:9:\"commsy_id\";i:99;s:11:\"auth_source\";s:3:\"100\";s:7:\"history\";a:3:{i:0;a:4:{s:7:\"context\";i:99;s:6:\"module\";s:13:\"configuration\";s:8:\"function\";s:6:\"update\";s:9:\"parameter\";a:0:{}}i:1;a:4:{s:7:\"context\";i:99;s:6:\"module\";s:13:\"configuration\";s:8:\"function\";s:5:\"index\";s:9:\"parameter\";a:0:{}}i:2;a:4:{s:7:\"context\";i:99;s:6:\"module\";s:4:\"home\";s:8:\"function\";s:5:\"index\";s:9:\"parameter\";a:4:{s:7:\"jscheck\";s:1:\"1\";s:4:\"isJS\";s:1:\"1\";s:5:\"https\";s:2:\"-1\";s:5:\"flash\";s:1:\"1\";}}}}','2013-03-25 13:39:22');

/*!40000 ALTER TABLE `session` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle step
# ------------------------------------------------------------

CREATE TABLE `step` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `creation_date` datetime DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `minutes` float NOT NULL DEFAULT '0',
  `time_type` smallint(6) NOT NULL DEFAULT '1',
  `todo_item_id` int(11) NOT NULL,
  `extras` text,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `todo_item_id` (`todo_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle tag
# ------------------------------------------------------------

CREATE TABLE `tag` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle tag_portfolio
# ------------------------------------------------------------

CREATE TABLE `tag_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT '0',
  `t_id` int(11) NOT NULL DEFAULT '0',
  `row` int(11) DEFAULT '0',
  `column` int(11) DEFAULT '0',
  `description` text,
  PRIMARY KEY (`p_id`,`t_id`),
  KEY `row` (`row`,`column`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle tag2tag
# ------------------------------------------------------------

CREATE TABLE `tag2tag` (
  `link_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_item_id` int(11) NOT NULL DEFAULT '0',
  `to_item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) NOT NULL DEFAULT '0',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modifier_id` int(11) NOT NULL DEFAULT '0',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `sorting_place` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`link_id`),
  KEY `from_item_id` (`from_item_id`),
  KEY `context_id` (`context_id`),
  KEY `deletion_date` (`deletion_date`),
  KEY `deleter_id` (`deleter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


# Export von Tabelle template_portfolio
# ------------------------------------------------------------

CREATE TABLE `template_portfolio` (
  `id` int(11) NOT NULL DEFAULT '0',
  `u_id` varchar(32) CHARACTER SET utf8 NOT NULL DEFAULT '',
  PRIMARY KEY (`p_id`,`u_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


# Export von Tabelle tasks
# ------------------------------------------------------------

CREATE TABLE `tasks` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL,
  `linked_item_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle todos
# ------------------------------------------------------------

CREATE TABLE `todos` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `date` datetime DEFAULT NULL,
  `status` tinyint(3) NOT NULL DEFAULT '1',
  `minutes` float DEFAULT NULL,
  `time_type` smallint(6) NOT NULL DEFAULT '1',
  `description` mediumtext,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `extras` text,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle user
# ------------------------------------------------------------

CREATE TABLE `user` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `user_id` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `is_contact` tinyint(4) NOT NULL DEFAULT '0',
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `lastlogin` datetime DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `extras` text,
  `auth_source` int(11) DEFAULT NULL,
  `description` text,
  `expire_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `user_id` (`user_id`),
  KEY `deletion_date` (`deletion_date`),
  KEY `deleter_id` (`deleter_id`),
  KEY `status` (`status`),
  KEY `is_contact` (`is_contact`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;

INSERT INTO `user` (`item_id`, `context_id`, `creator_id`, `modifier_id`, `deleter_id`, `creation_date`, `modification_date`, `deletion_date`, `user_id`, `status`, `is_contact`, `firstname`, `lastname`, `email`, `city`, `lastlogin`, `visible`, `extras`, `auth_source`, `description`)
VALUES
	(98,99,99,99,NULL,'2006-09-13 12:17:17','2006-09-13 12:17:17',NULL,'root',3,1,'CommSy','Administrator','','',NULL,1,'',100,NULL);

/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle user_portfolio
# ------------------------------------------------------------

CREATE TABLE `user_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT '0',
  `u_id` varchar(32) NOT NULL,
  PRIMARY KEY (`p_id`,`u_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle workflow_read
# ------------------------------------------------------------

CREATE TABLE `workflow_read` (
  `item_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  KEY `item_id` (`item_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_annotations
# ------------------------------------------------------------

CREATE TABLE `zzz_annotations` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `linked_item_id` int(11) NOT NULL DEFAULT '0',
  `linked_version_id` int(11) NOT NULL DEFAULT '0',
  `extras` text,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `linked_item_id` (`linked_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_announcement
# ------------------------------------------------------------

CREATE TABLE `zzz_announcement` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `enddate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `extras` text,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_assessments
# ------------------------------------------------------------

CREATE TABLE `zzz_assessments` (
  `item_id` int(11) NOT NULL,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `item_link_id` int(11) NOT NULL,
  `assessment` int(2) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `item_link_id` (`item_link_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `deleter_id` (`deleter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_dates
# ------------------------------------------------------------

CREATE TABLE `zzz_dates` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `start_time` varchar(100) DEFAULT NULL,
  `end_time` varchar(100) DEFAULT NULL,
  `start_day` varchar(100) NOT NULL,
  `end_day` varchar(100) DEFAULT NULL,
  `place` varchar(100) DEFAULT NULL,
  `datetime_start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `datetime_end` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `date_mode` tinyint(4) NOT NULL DEFAULT '0',
  `color` varchar(255) DEFAULT NULL,
  `recurrence_id` int(11) DEFAULT NULL,
  `recurrence_pattern` text,
  `extras` text,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_discussionarticles
# ------------------------------------------------------------

CREATE TABLE `zzz_discussionarticles` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `discussion_id` int(11) NOT NULL DEFAULT '0',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` mediumtext,
  `position` varchar(255) NOT NULL DEFAULT '1',
  `extras` text,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_discussions
# ------------------------------------------------------------

CREATE TABLE `zzz_discussions` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `latest_article_item_id` int(11) DEFAULT NULL,
  `latest_article_modification_date` datetime DEFAULT NULL,
  `status` int(2) NOT NULL DEFAULT '1',
  `discussion_type` varchar(10) NOT NULL DEFAULT 'simple',
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `extras` text,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_external_viewer
# ------------------------------------------------------------

CREATE TABLE `zzz_external_viewer` (
  `item_id` int(11) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  KEY `item_id` (`item_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_files
# ------------------------------------------------------------

CREATE TABLE `zzz_files` (
  `files_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `size` int(30) NOT NULL DEFAULT '0',
  `has_html` enum('0','1','2') NOT NULL DEFAULT '0',
  `scan` tinyint(1) NOT NULL DEFAULT '-1',
  `extras` text,
  `temp_upload_session_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`files_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_hash
# ------------------------------------------------------------

CREATE TABLE `zzz_hash` (
  `user_item_id` int(11) NOT NULL,
  `rss` char(32) DEFAULT NULL,
  `ical` char(32) DEFAULT NULL,
  PRIMARY KEY (`user_item_id`),
  KEY `rss` (`rss`),
  KEY `ical` (`ical`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_homepage_link_page_page
# ------------------------------------------------------------

CREATE TABLE `zzz_homepage_link_page_page` (
  `link_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_item_id` int(11) NOT NULL DEFAULT '0',
  `to_item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) NOT NULL DEFAULT '0',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modifier_id` int(11) NOT NULL DEFAULT '0',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `sorting_place` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`link_id`),
  KEY `from_item_id` (`from_item_id`),
  KEY `context_id` (`context_id`),
  KEY `to_item_id` (`to_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_homepage_page
# ------------------------------------------------------------

CREATE TABLE `zzz_homepage_page` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `page_type` varchar(10) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_item_link_file
# ------------------------------------------------------------

CREATE TABLE `zzz_item_link_file` (
  `item_iid` int(11) NOT NULL DEFAULT '0',
  `item_vid` int(11) NOT NULL DEFAULT '0',
  `file_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_iid`,`item_vid`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_items
# ------------------------------------------------------------

CREATE TABLE `zzz_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `type` varchar(15) NOT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_labels
# ------------------------------------------------------------

CREATE TABLE `zzz_labels` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `type` varchar(15) NOT NULL,
  `extras` text,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_link_items
# ------------------------------------------------------------

CREATE TABLE `zzz_link_items` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `first_item_id` int(11) NOT NULL DEFAULT '0',
  `first_item_type` varchar(15) DEFAULT NULL,
  `second_item_id` int(11) NOT NULL DEFAULT '0',
  `second_item_type` varchar(15) DEFAULT NULL,
  `sorting_place` int(11) DEFAULT NULL,
  `extras` text,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `first_item_id` (`first_item_id`),
  KEY `second_item_id` (`second_item_id`),
  KEY `first_item_type` (`first_item_type`),
  KEY `second_item_type` (`second_item_type`),
  KEY `deletion_date` (`deletion_date`),
  KEY `deleter_id` (`deleter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_link_modifier_item
# ------------------------------------------------------------

CREATE TABLE `zzz_link_modifier_item` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`,`modifier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_links
# ------------------------------------------------------------

CREATE TABLE `zzz_links` (
  `from_item_id` int(11) NOT NULL DEFAULT '0',
  `from_version_id` int(11) NOT NULL DEFAULT '0',
  `to_item_id` int(11) NOT NULL DEFAULT '0',
  `to_version_id` int(11) NOT NULL DEFAULT '0',
  `link_type` char(30) NOT NULL,
  `context_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `x` int(11) DEFAULT NULL,
  `y` int(11) DEFAULT NULL,
  PRIMARY KEY (`from_item_id`,`from_version_id`,`to_item_id`,`to_version_id`,`link_type`),
  KEY `context_id` (`context_id`),
  KEY `link_type` (`link_type`),
  KEY `from_item_id` (`from_item_id`),
  KEY `from_version_id` (`from_version_id`),
  KEY `to_item_id` (`to_item_id`),
  KEY `to_version_id` (`to_version_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_materials
# ------------------------------------------------------------

CREATE TABLE `zzz_materials` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modifier_id` int(11) DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `author` varchar(200) DEFAULT NULL,
  `publishing_date` varchar(20) DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `world_public` smallint(2) NOT NULL DEFAULT '0',
  `extras` text,
  `new_hack` tinyint(1) NOT NULL DEFAULT '0',
  `copy_of` int(11) DEFAULT NULL,
  `workflow_status` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '3_none',
  `workflow_resubmission_date` datetime DEFAULT NULL,
  `workflow_validity_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`,`version_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `modifier_id` (`modifier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_noticed
# ------------------------------------------------------------

CREATE TABLE `zzz_noticed` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_reader
# ------------------------------------------------------------

CREATE TABLE `zzz_reader` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_room
# ------------------------------------------------------------

CREATE TABLE `zzz_room` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `extras` text,
  `status` varchar(20) NOT NULL,
  `activity` int(11) NOT NULL DEFAULT '0',
  `type` varchar(20) NOT NULL DEFAULT 'project',
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `is_open_for_guests` tinyint(4) NOT NULL DEFAULT '0',
  `continuous` tinyint(4) NOT NULL DEFAULT '-1',
  `template` tinyint(4) NOT NULL DEFAULT '-1',
  `contact_persons` varchar(255) DEFAULT NULL,
  `description` text,
  `room_description` varchar(10000) DEFAULT NULL,
  `lastlogin` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `activity` (`activity`),
  KEY `deletion_date` (`deletion_date`),
  KEY `deleter_id` (`deleter_id`),
  KEY `room_description` (`room_description`(333)),
  KEY `contact_persons` (`contact_persons`),
  KEY `title` (`title`),
  KEY `modifier_id` (`modifier_id`),
  KEY `lastlogin` (`lastlogin`),
  KEY `status_2` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_section
# ------------------------------------------------------------

CREATE TABLE `zzz_section` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `creation_date` datetime DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `number` smallint(6) NOT NULL DEFAULT '0',
  `material_item_id` int(11) NOT NULL DEFAULT '0',
  `extras` text,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`,`version_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `material_item_id` (`material_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_step
# ------------------------------------------------------------

CREATE TABLE `zzz_step` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `creation_date` datetime DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `minutes` float NOT NULL DEFAULT '0',
  `time_type` smallint(6) NOT NULL DEFAULT '1',
  `todo_item_id` int(11) NOT NULL,
  `extras` text,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `todo_item_id` (`todo_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_tag
# ------------------------------------------------------------

CREATE TABLE `zzz_tag` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_tag2tag
# ------------------------------------------------------------

CREATE TABLE `zzz_tag2tag` (
  `link_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_item_id` int(11) NOT NULL DEFAULT '0',
  `to_item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) NOT NULL DEFAULT '0',
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modifier_id` int(11) NOT NULL DEFAULT '0',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `sorting_place` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`link_id`),
  KEY `from_item_id` (`from_item_id`),
  KEY `context_id` (`context_id`),
  KEY `deletion_date` (`deletion_date`),
  KEY `deleter_id` (`deleter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_tasks
# ------------------------------------------------------------

CREATE TABLE `zzz_tasks` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL,
  `linked_item_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_todos
# ------------------------------------------------------------

CREATE TABLE `zzz_todos` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `date` datetime DEFAULT NULL,
  `status` tinyint(3) NOT NULL DEFAULT '1',
  `minutes` float DEFAULT NULL,
  `time_type` smallint(6) NOT NULL DEFAULT '1',
  `description` mediumtext,
  `public` tinyint(11) NOT NULL DEFAULT '0',
  `extras` text,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_user
# ------------------------------------------------------------

CREATE TABLE `zzz_user` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `user_id` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `is_contact` tinyint(4) NOT NULL DEFAULT '0',
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `lastlogin` datetime DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `extras` text,
  `auth_source` int(11) DEFAULT NULL,
  `description` text,
  `expire_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `user_id` (`user_id`),
  KEY `deletion_date` (`deletion_date`),
  KEY `deleter_id` (`deleter_id`),
  KEY `status` (`status`),
  KEY `is_contact` (`is_contact`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_workflow_read
# ------------------------------------------------------------

CREATE TABLE `zzz_workflow_read` (
  `item_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  KEY `item_id` (`item_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
