# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.5.5-10.1.30-MariaDB-1~jessie)
# Datenbank: test
# Erstellt am: 2018-03-14 00:11:20 +0000
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

DROP TABLE IF EXISTS `annotation_portfolio`;

CREATE TABLE `annotation_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT '0',
  `a_id` int(11) NOT NULL DEFAULT '0',
  `row` int(11) NOT NULL DEFAULT '0',
  `column` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`p_id`,`a_id`),
  KEY `row` (`row`,`column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle annotations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `annotations`;

CREATE TABLE `annotations` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle announcement
# ------------------------------------------------------------

DROP TABLE IF EXISTS `announcement`;

CREATE TABLE `announcement` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle assessments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `assessments`;

CREATE TABLE `assessments` (
  `item_id` int(11) NOT NULL,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle auth
# ------------------------------------------------------------

DROP TABLE IF EXISTS `auth`;

CREATE TABLE `auth` (
  `commsy_id` int(11) NOT NULL DEFAULT '0',
  `user_id` varchar(32) NOT NULL,
  `password_md5` varchar(32) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `language` varchar(10) NOT NULL,
  PRIMARY KEY (`commsy_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `auth` WRITE;
/*!40000 ALTER TABLE `auth` DISABLE KEYS */;

INSERT INTO `auth` (`commsy_id`, `user_id`, `password_md5`, `firstname`, `lastname`, `email`, `language`)
VALUES
  (99,'root','63a9f0ea7bb98050796b649e85481845','CommSy','Administrator','','de');

/*!40000 ALTER TABLE `auth` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle auth_source
# ------------------------------------------------------------

DROP TABLE IF EXISTS `auth_source`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `auth_source` WRITE;
/*!40000 ALTER TABLE `auth_source` DISABLE KEYS */;

INSERT INTO `auth_source` (`item_id`, `context_id`, `creator_id`, `modifier_id`, `deleter_id`, `creation_date`, `modification_date`, `deletion_date`, `title`, `extras`)
VALUES
  (100,99,99,99,NULL,'2006-09-14 12:32:24','2006-09-14 12:32:24',NULL,'CommSy','a:4:{s:14:\"COMMSY_DEFAULT\";s:1:\"1\";s:6:\"SOURCE\";s:5:\"MYSQL\";s:13:\"CONFIGURATION\";a:5:{s:11:\"ADD_ACCOUNT\";s:1:\"0\";s:13:\"CHANGE_USERID\";s:1:\"0\";s:14:\"DELETE_ACCOUNT\";s:1:\"0\";s:15:\"CHANGE_USERDATA\";s:1:\"1\";s:15:\"CHANGE_PASSWORD\";s:1:\"1\";}s:4:\"SHOW\";s:1:\"1\";}');

/*!40000 ALTER TABLE `auth_source` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle calendars
# ------------------------------------------------------------

DROP TABLE IF EXISTS `calendars`;

CREATE TABLE `calendars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `color` varchar(255) NOT NULL,
  `external_url` varchar(255) DEFAULT NULL,
  `default_calendar` tinyint(4) NOT NULL DEFAULT '0',
  `creator_id` int(11) DEFAULT NULL,
  `synctoken` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle dates
# ------------------------------------------------------------

DROP TABLE IF EXISTS `dates`;

CREATE TABLE `dates` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  `calendar_id` int(11) DEFAULT NULL,
  `external` tinyint(4) NOT NULL DEFAULT '0',
  `uid` varchar(255) DEFAULT NULL,
  `whole_day` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle discussionarticles
# ------------------------------------------------------------

DROP TABLE IF EXISTS `discussionarticles`;

CREATE TABLE `discussionarticles` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `discussion_id` int(11) NOT NULL DEFAULT '0',
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle discussions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `discussions`;

CREATE TABLE `discussions` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle external_viewer
# ------------------------------------------------------------

DROP TABLE IF EXISTS `external_viewer`;

CREATE TABLE `external_viewer` (
  `item_id` int(11) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  PRIMARY KEY (`item_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle external2commsy_id
# ------------------------------------------------------------

DROP TABLE IF EXISTS `external2commsy_id`;

CREATE TABLE `external2commsy_id` (
  `external_id` varchar(255) NOT NULL,
  `source_system` varchar(60) NOT NULL,
  `commsy_id` int(11) NOT NULL,
  PRIMARY KEY (`external_id`,`source_system`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle files
# ------------------------------------------------------------

DROP TABLE IF EXISTS `files`;

CREATE TABLE `files` (
  `files_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `size` int(30) NOT NULL DEFAULT '0',
  `has_html` enum('0','1','2') NOT NULL DEFAULT '0',
  `scan` tinyint(1) NOT NULL DEFAULT '-1',
  `extras` text,
  `temp_upload_session_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`files_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle hash
# ------------------------------------------------------------

DROP TABLE IF EXISTS `hash`;

CREATE TABLE `hash` (
  `user_item_id` int(11) NOT NULL,
  `rss` char(32) DEFAULT NULL,
  `ical` char(32) DEFAULT NULL,
  `caldav` char(32) DEFAULT NULL,
  PRIMARY KEY (`user_item_id`),
  KEY `rss` (`rss`),
  KEY `ical` (`ical`),
  KEY `caldav` (`caldav`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle invitations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `invitations`;

CREATE TABLE `invitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `authsource_id` int(11) NOT NULL,
  `context_id` int(11) NOT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expiration_date` datetime DEFAULT NULL,
  `redeemed` tinyint(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle item_backup
# ------------------------------------------------------------

DROP TABLE IF EXISTS `item_backup`;

CREATE TABLE `item_backup` (
  `item_id` int(11) NOT NULL,
  `backup_date` datetime NOT NULL,
  `modification_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `public` tinyint(11) NOT NULL,
  `special` text CHARACTER SET ucs2 NOT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle item_link_file
# ------------------------------------------------------------

DROP TABLE IF EXISTS `item_link_file`;

CREATE TABLE `item_link_file` (
  `item_iid` int(11) NOT NULL DEFAULT '0',
  `item_vid` int(11) NOT NULL DEFAULT '0',
  `file_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_iid`,`item_vid`,`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle items
# ------------------------------------------------------------

DROP TABLE IF EXISTS `items`;

CREATE TABLE `items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `type` varchar(15) NOT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `draft` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;

INSERT INTO `items` (`item_id`, `context_id`, `type`, `deleter_id`, `deletion_date`, `modification_date`, `draft`)
VALUES
  (98,99,'user',NULL,NULL,NULL,0),
  (99,0,'server',NULL,NULL,'2014-08-19 15:38:16',0),
  (100,99,'auth_source',NULL,NULL,'2006-09-14 12:32:24',0);

/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle labels
# ------------------------------------------------------------

DROP TABLE IF EXISTS `labels`;

CREATE TABLE `labels` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle link_items
# ------------------------------------------------------------

DROP TABLE IF EXISTS `link_items`;

CREATE TABLE `link_items` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle link_modifier_item
# ------------------------------------------------------------

DROP TABLE IF EXISTS `link_modifier_item`;

CREATE TABLE `link_modifier_item` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`,`modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `link_modifier_item` WRITE;
/*!40000 ALTER TABLE `link_modifier_item` DISABLE KEYS */;

INSERT INTO `link_modifier_item` (`item_id`, `modifier_id`)
VALUES
  (99,98);

/*!40000 ALTER TABLE `link_modifier_item` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle links
# ------------------------------------------------------------

DROP TABLE IF EXISTS `links`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle log
# ------------------------------------------------------------

DROP TABLE IF EXISTS `log`;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;



# Export von Tabelle log_archive
# ------------------------------------------------------------

DROP TABLE IF EXISTS `log_archive`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle materials
# ------------------------------------------------------------

DROP TABLE IF EXISTS `materials`;

CREATE TABLE `materials` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`,`version_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `modifier_id` (`modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle migration_versions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `migration_versions`;

CREATE TABLE `migration_versions` (
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `migration_versions` WRITE;
/*!40000 ALTER TABLE `migration_versions` DISABLE KEYS */;

INSERT INTO `migration_versions` (`version`)
VALUES
  ('20150623133246'),
  ('20150623135455'),
  ('20150831152400'),
  ('20150914082323'),
  ('20160718213927'),
  ('20160719021757'),
  ('20160727100551'),
  ('20160727103653'),
  ('20160727111607'),
  ('20160727112623'),
  ('20160727133717'),
  ('20160728231457'),
  ('20170225094328'),
  ('20170225121940'),
  ('20170420141745'),
  ('20170521105856'),
  ('20170616103508'),
  ('20170714122834'),
  ('20170721185631'),
  ('20170802185102'),
  ('20170810143230'),
  ('20170824064811'),
  ('20170908083138');

/*!40000 ALTER TABLE `migration_versions` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle noticed
# ------------------------------------------------------------

DROP TABLE IF EXISTS `noticed`;

CREATE TABLE `noticed` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle portal
# ------------------------------------------------------------

DROP TABLE IF EXISTS `portal`;

CREATE TABLE `portal` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle portfolio
# ------------------------------------------------------------

DROP TABLE IF EXISTS `portfolio`;

CREATE TABLE `portfolio` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext NOT NULL,
  `template` tinyint(4) NOT NULL DEFAULT '-1',
  `creation_date` datetime NOT NULL,
  `modification_date` datetime NOT NULL,
  `deletion_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `creator_id` (`creator_id`),
  KEY `modifier_id` (`modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='General Portfolio Information';



# Export von Tabelle reader
# ------------------------------------------------------------

DROP TABLE IF EXISTS `reader`;

CREATE TABLE `reader` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle room
# ------------------------------------------------------------

DROP TABLE IF EXISTS `room`;

CREATE TABLE `room` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  KEY `contact_persons` (`contact_persons`),
  KEY `title` (`title`),
  KEY `modifier_id` (`modifier_id`),
  KEY `lastlogin` (`lastlogin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle room_categories
# ------------------------------------------------------------

DROP TABLE IF EXISTS `room_categories`;

CREATE TABLE `room_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle room_categories_links
# ------------------------------------------------------------

DROP TABLE IF EXISTS `room_categories_links`;

CREATE TABLE `room_categories_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle room_privat
# ------------------------------------------------------------

DROP TABLE IF EXISTS `room_privat`;

CREATE TABLE `room_privat` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle section
# ------------------------------------------------------------

DROP TABLE IF EXISTS `section`;

CREATE TABLE `section` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle server
# ------------------------------------------------------------

DROP TABLE IF EXISTS `server`;

CREATE TABLE `server` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `server` WRITE;
/*!40000 ALTER TABLE `server` DISABLE KEYS */;

INSERT INTO `server` (`item_id`, `context_id`, `creator_id`, `modifier_id`, `deleter_id`, `creation_date`, `modification_date`, `deletion_date`, `title`, `extras`, `status`, `activity`, `type`, `is_open_for_guests`, `url`)
VALUES
  (99,0,NULL,NULL,NULL,'2006-09-13 12:16:38','2014-08-19 15:38:16',NULL,'CommSy-Server','a:3:{s:8:\"HOMECONF\";s:0:\"\";s:12:\"DEFAULT_AUTH\";s:3:\"100\";s:7:\"VERSION\";s:5:\"8.1.8\";}','1',74,'server',1,'');

/*!40000 ALTER TABLE `server` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle session
# ------------------------------------------------------------

DROP TABLE IF EXISTS `session`;

CREATE TABLE `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(150) NOT NULL,
  `session_key` varchar(30) NOT NULL,
  `session_value` longtext NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;



# Export von Tabelle step
# ------------------------------------------------------------

DROP TABLE IF EXISTS `step`;

CREATE TABLE `step` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle tag
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tag`;

CREATE TABLE `tag` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle tag_portfolio
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tag_portfolio`;

CREATE TABLE `tag_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT '0',
  `t_id` int(11) NOT NULL DEFAULT '0',
  `row` int(11) DEFAULT '0',
  `column` int(11) DEFAULT '0',
  `description` text,
  PRIMARY KEY (`p_id`,`t_id`),
  KEY `row` (`row`,`column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle tag2tag
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tag2tag`;

CREATE TABLE `tag2tag` (
  `link_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_item_id` int(11) NOT NULL DEFAULT '0',
  `to_item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) NOT NULL DEFAULT '0',
  `creator_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modifier_id` int(11) DEFAULT NULL,
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `sorting_place` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`link_id`),
  KEY `from_item_id` (`from_item_id`),
  KEY `context_id` (`context_id`),
  KEY `deletion_date` (`deletion_date`),
  KEY `deleter_id` (`deleter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle tasks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tasks`;

CREATE TABLE `tasks` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle template_portfolio
# ------------------------------------------------------------

DROP TABLE IF EXISTS `template_portfolio`;

CREATE TABLE `template_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT '0',
  `u_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`p_id`,`u_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle todos
# ------------------------------------------------------------

DROP TABLE IF EXISTS `todos`;

CREATE TABLE `todos` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle user
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user`;

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
  `use_portal_email` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `user_id` (`user_id`),
  KEY `deletion_date` (`deletion_date`),
  KEY `deleter_id` (`deleter_id`),
  KEY `status` (`status`),
  KEY `is_contact` (`is_contact`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;

INSERT INTO `user` (`item_id`, `context_id`, `creator_id`, `modifier_id`, `deleter_id`, `creation_date`, `modification_date`, `deletion_date`, `user_id`, `status`, `is_contact`, `firstname`, `lastname`, `email`, `city`, `lastlogin`, `visible`, `extras`, `auth_source`, `description`, `expire_date`, `use_portal_email`)
VALUES
  (98,99,99,99,NULL,'2006-09-13 12:17:17','2006-09-13 12:17:17',NULL,'root',3,1,'CommSy','Administrator','','',NULL,1,'',100,NULL,NULL,0);

/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;


# Export von Tabelle user_portfolio
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_portfolio`;

CREATE TABLE `user_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT '0',
  `u_id` varchar(32) NOT NULL,
  PRIMARY KEY (`p_id`,`u_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle workflow_read
# ------------------------------------------------------------

DROP TABLE IF EXISTS `workflow_read`;

CREATE TABLE `workflow_read` (
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`item_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_annotations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_annotations`;

CREATE TABLE `zzz_annotations` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_announcement
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_announcement`;

CREATE TABLE `zzz_announcement` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_assessments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_assessments`;

CREATE TABLE `zzz_assessments` (
  `item_id` int(11) NOT NULL,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_calendars
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_calendars`;

CREATE TABLE `zzz_calendars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `color` varchar(255) NOT NULL,
  `external_url` varchar(255) DEFAULT NULL,
  `default_calendar` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_dates
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_dates`;

CREATE TABLE `zzz_dates` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  `calendar_id` int(11) DEFAULT NULL,
  `external` tinyint(4) NOT NULL DEFAULT '0',
  `uid` varchar(255) DEFAULT NULL,
  `whole_day` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_discussionarticles
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_discussionarticles`;

CREATE TABLE `zzz_discussionarticles` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `discussion_id` int(11) NOT NULL DEFAULT '0',
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_discussions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_discussions`;

CREATE TABLE `zzz_discussions` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_external_viewer
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_external_viewer`;

CREATE TABLE `zzz_external_viewer` (
  `item_id` int(11) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  PRIMARY KEY (`item_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_files
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_files`;

CREATE TABLE `zzz_files` (
  `files_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `size` int(30) NOT NULL DEFAULT '0',
  `has_html` enum('0','1','2') NOT NULL DEFAULT '0',
  `scan` tinyint(1) NOT NULL DEFAULT '-1',
  `extras` text,
  `temp_upload_session_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`files_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_hash
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_hash`;

CREATE TABLE `zzz_hash` (
  `user_item_id` int(11) NOT NULL,
  `rss` char(32) DEFAULT NULL,
  `ical` char(32) DEFAULT NULL,
  `caldav` char(32) DEFAULT NULL,
  PRIMARY KEY (`user_item_id`),
  KEY `rss` (`rss`),
  KEY `ical` (`ical`),
  KEY `caldav` (`caldav`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_item_link_file
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_item_link_file`;

CREATE TABLE `zzz_item_link_file` (
  `item_iid` int(11) NOT NULL DEFAULT '0',
  `item_vid` int(11) NOT NULL DEFAULT '0',
  `file_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_iid`,`item_vid`,`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_items
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_items`;

CREATE TABLE `zzz_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `type` varchar(15) NOT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `draft` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_labels
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_labels`;

CREATE TABLE `zzz_labels` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_link_items
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_link_items`;

CREATE TABLE `zzz_link_items` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_link_modifier_item
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_link_modifier_item`;

CREATE TABLE `zzz_link_modifier_item` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`,`modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_links
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_links`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_materials
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_materials`;

CREATE TABLE `zzz_materials` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`,`version_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `modifier_id` (`modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_noticed
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_noticed`;

CREATE TABLE `zzz_noticed` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_reader
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_reader`;

CREATE TABLE `zzz_reader` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_room
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_room`;

CREATE TABLE `zzz_room` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  KEY `contact_persons` (`contact_persons`),
  KEY `title` (`title`),
  KEY `modifier_id` (`modifier_id`),
  KEY `lastlogin` (`lastlogin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_section
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_section`;

CREATE TABLE `zzz_section` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_step
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_step`;

CREATE TABLE `zzz_step` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_tag
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_tag`;

CREATE TABLE `zzz_tag` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_tag2tag
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_tag2tag`;

CREATE TABLE `zzz_tag2tag` (
  `link_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_item_id` int(11) NOT NULL DEFAULT '0',
  `to_item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) NOT NULL DEFAULT '0',
  `creator_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modifier_id` int(11) DEFAULT NULL,
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `sorting_place` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`link_id`),
  KEY `from_item_id` (`from_item_id`),
  KEY `context_id` (`context_id`),
  KEY `deletion_date` (`deletion_date`),
  KEY `deleter_id` (`deleter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_tasks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_tasks`;

CREATE TABLE `zzz_tasks` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_todos
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_todos`;

CREATE TABLE `zzz_todos` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
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
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_user
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_user`;

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
  `use_portal_email` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `user_id` (`user_id`),
  KEY `deletion_date` (`deletion_date`),
  KEY `deleter_id` (`deleter_id`),
  KEY `status` (`status`),
  KEY `is_contact` (`is_contact`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Export von Tabelle zzz_workflow_read
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zzz_workflow_read`;

CREATE TABLE `zzz_workflow_read` (
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`item_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
