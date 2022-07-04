-- MariaDB dump 10.19  Distrib 10.6.8-MariaDB, for Linux (x86_64)
--
-- Host: db    Database: commsy_test
-- ------------------------------------------------------
-- Server version	10.4.25-MariaDB-1:10.4.25+maria~focal

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `password_md5` varchar(32) DEFAULT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `language` varchar(10) NOT NULL,
  `auth_source_id` int(11) NOT NULL,
  `locked` tinyint(1) NOT NULL,
  `activity_state` varchar(15) NOT NULL DEFAULT 'active',
  `activity_state_updated` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounts_idx` (`context_id`,`username`,`auth_source_id`),
  KEY `IDX_CAC89EAC91C3C0F3` (`auth_source_id`),
  CONSTRAINT `accounts_auth_source_id_fk` FOREIGN KEY (`auth_source_id`) REFERENCES `auth_source` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (1,99,'root','','$2y$13$jgr8HC8tMzIPi2R5wQhxwODouVIZkqKvK6Z44vKIdTpy27lAQfcCu',NULL,'CommSy','Administrator','de',100,0,'active',NULL,NULL);
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `annotation_portfolio`
--

DROP TABLE IF EXISTS `annotation_portfolio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annotation_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT 0,
  `a_id` int(11) NOT NULL DEFAULT 0,
  `row` int(11) NOT NULL DEFAULT 0,
  `column` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`p_id`,`a_id`),
  KEY `row` (`row`,`column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `annotation_portfolio`
--

LOCK TABLES `annotation_portfolio` WRITE;
/*!40000 ALTER TABLE `annotation_portfolio` DISABLE KEYS */;
/*!40000 ALTER TABLE `annotation_portfolio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `annotations`
--

DROP TABLE IF EXISTS `annotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annotations` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `linked_item_id` int(11) NOT NULL DEFAULT 0,
  `linked_version_id` int(11) NOT NULL DEFAULT 0,
  `extras` text DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `linked_item_id` (`linked_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `annotations`
--

LOCK TABLES `annotations` WRITE;
/*!40000 ALTER TABLE `annotations` DISABLE KEYS */;
/*!40000 ALTER TABLE `annotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement`
--

DROP TABLE IF EXISTS `announcement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `enddate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `extras` text DEFAULT NULL,
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement`
--

LOCK TABLES `announcement` WRITE;
/*!40000 ALTER TABLE `announcement` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessments`
--

DROP TABLE IF EXISTS `assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessments`
--

LOCK TABLES `assessments` WRITE;
/*!40000 ALTER TABLE `assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth_source`
--

DROP TABLE IF EXISTS `auth_source`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portal_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `default` tinyint(1) NOT NULL,
  `add_account` enum('yes','no','invitation') DEFAULT NULL,
  `change_username` tinyint(1) NOT NULL,
  `delete_account` tinyint(1) NOT NULL,
  `change_userdata` tinyint(1) NOT NULL,
  `change_password` tinyint(1) NOT NULL,
  `create_room` tinyint(1) NOT NULL,
  `login_url` varchar(255) DEFAULT NULL,
  `logout_url` varchar(255) DEFAULT NULL,
  `password_reset_url` varchar(255) DEFAULT NULL,
  `mapping_username` varchar(50) DEFAULT NULL,
  `mapping_firstname` varchar(50) DEFAULT NULL,
  `mapping_lastname` varchar(50) DEFAULT NULL,
  `mapping_email` varchar(50) DEFAULT NULL,
  `server_url` varchar(255) DEFAULT NULL,
  `uid_key` varchar(50) DEFAULT NULL,
  `base_dn` varchar(50) DEFAULT NULL,
  `search_dn` varchar(50) DEFAULT NULL,
  `search_password` varchar(50) DEFAULT NULL,
  `auth_dn` varchar(50) DEFAULT NULL,
  `auth_query` varchar(50) DEFAULT NULL,
  `mail_regex` varchar(100) DEFAULT NULL,
  `identity_provider` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `portal_id` (`portal_id`),
  CONSTRAINT `FK_7F29D891B887E1DD` FOREIGN KEY (`portal_id`) REFERENCES `portal` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_source`
--

LOCK TABLES `auth_source` WRITE;
/*!40000 ALTER TABLE `auth_source` DISABLE KEYS */;
INSERT INTO `auth_source` VALUES (100,NULL,'CommSy',NULL,'local',1,1,'no',0,0,1,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `auth_source` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendars`
--

DROP TABLE IF EXISTS `calendars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `color` varchar(255) NOT NULL,
  `external_url` varchar(255) DEFAULT NULL,
  `default_calendar` tinyint(4) NOT NULL DEFAULT 0,
  `creator_id` int(11) DEFAULT NULL,
  `synctoken` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendars`
--

LOCK TABLES `calendars` WRITE;
/*!40000 ALTER TABLE `calendars` DISABLE KEYS */;
/*!40000 ALTER TABLE `calendars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cron_task`
--

DROP TABLE IF EXISTS `cron_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cron_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_run` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cron_task`
--

LOCK TABLES `cron_task` WRITE;
/*!40000 ALTER TABLE `cron_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `cron_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dates`
--

DROP TABLE IF EXISTS `dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dates` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `start_time` varchar(100) DEFAULT NULL,
  `end_time` varchar(100) DEFAULT NULL,
  `start_day` varchar(100) NOT NULL,
  `end_day` varchar(100) DEFAULT NULL,
  `place` varchar(100) DEFAULT NULL,
  `datetime_start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `datetime_end` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `date_mode` tinyint(4) NOT NULL DEFAULT 0,
  `color` varchar(255) DEFAULT NULL,
  `recurrence_id` int(11) DEFAULT NULL,
  `recurrence_pattern` text DEFAULT NULL,
  `extras` text DEFAULT NULL,
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  `calendar_id` int(11) DEFAULT NULL,
  `external` tinyint(4) NOT NULL DEFAULT 0,
  `uid` varchar(255) DEFAULT NULL,
  `whole_day` tinyint(4) NOT NULL DEFAULT 0,
  `datetime_recurrence` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dates`
--

LOCK TABLES `dates` WRITE;
/*!40000 ALTER TABLE `dates` DISABLE KEYS */;
/*!40000 ALTER TABLE `dates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discussionarticles`
--

DROP TABLE IF EXISTS `discussionarticles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discussionarticles` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `discussion_id` int(11) NOT NULL DEFAULT 0,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `position` varchar(255) NOT NULL DEFAULT '1',
  `extras` text DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discussionarticles`
--

LOCK TABLES `discussionarticles` WRITE;
/*!40000 ALTER TABLE `discussionarticles` DISABLE KEYS */;
/*!40000 ALTER TABLE `discussionarticles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discussions`
--

DROP TABLE IF EXISTS `discussions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discussions` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `latest_article_item_id` int(11) DEFAULT NULL,
  `latest_article_modification_date` datetime DEFAULT NULL,
  `status` int(2) NOT NULL DEFAULT 1,
  `discussion_type` varchar(10) NOT NULL DEFAULT 'simple',
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `extras` text DEFAULT NULL,
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discussions`
--

LOCK TABLES `discussions` WRITE;
/*!40000 ALTER TABLE `discussions` DISABLE KEYS */;
/*!40000 ALTER TABLE `discussions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `external2commsy_id`
--

DROP TABLE IF EXISTS `external2commsy_id`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `external2commsy_id` (
  `external_id` varchar(255) NOT NULL,
  `source_system` varchar(60) NOT NULL,
  `commsy_id` int(11) NOT NULL,
  PRIMARY KEY (`external_id`,`source_system`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `external2commsy_id`
--

LOCK TABLES `external2commsy_id` WRITE;
/*!40000 ALTER TABLE `external2commsy_id` DISABLE KEYS */;
/*!40000 ALTER TABLE `external2commsy_id` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `external_viewer`
--

DROP TABLE IF EXISTS `external_viewer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `external_viewer` (
  `item_id` int(11) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  PRIMARY KEY (`item_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `external_viewer`
--

LOCK TABLES `external_viewer` WRITE;
/*!40000 ALTER TABLE `external_viewer` DISABLE KEYS */;
/*!40000 ALTER TABLE `external_viewer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `size` int(30) NOT NULL DEFAULT 0,
  `has_html` enum('0','1','2') NOT NULL DEFAULT '0',
  `scan` tinyint(1) NOT NULL DEFAULT -1,
  `extras` text DEFAULT NULL,
  `temp_upload_session_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`files_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `files`
--

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
/*!40000 ALTER TABLE `files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hash`
--

DROP TABLE IF EXISTS `hash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hash`
--

LOCK TABLES `hash` WRITE;
/*!40000 ALTER TABLE `hash` DISABLE KEYS */;
/*!40000 ALTER TABLE `hash` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invitations`
--

DROP TABLE IF EXISTS `invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `authsource_id` int(11) NOT NULL,
  `context_id` int(11) NOT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expiration_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invitations`
--

LOCK TABLES `invitations` WRITE;
/*!40000 ALTER TABLE `invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_link_file`
--

DROP TABLE IF EXISTS `item_link_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_link_file` (
  `item_iid` int(11) NOT NULL DEFAULT 0,
  `item_vid` int(11) NOT NULL DEFAULT 0,
  `file_id` int(11) NOT NULL DEFAULT 0,
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_iid`,`item_vid`,`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_link_file`
--

LOCK TABLES `item_link_file` WRITE;
/*!40000 ALTER TABLE `item_link_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `item_link_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `type` varchar(15) NOT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `draft` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` VALUES (98,99,'user',NULL,NULL,NULL,0),(99,0,'server',NULL,NULL,'2014-08-19 15:38:16',0);
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `labels`
--

DROP TABLE IF EXISTS `labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `labels` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(15) NOT NULL,
  `extras` text DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `labels`
--

LOCK TABLES `labels` WRITE;
/*!40000 ALTER TABLE `labels` DISABLE KEYS */;
/*!40000 ALTER TABLE `labels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `licenses`
--

DROP TABLE IF EXISTS `licenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `licenses`
--

LOCK TABLES `licenses` WRITE;
/*!40000 ALTER TABLE `licenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `licenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `link_items`
--

DROP TABLE IF EXISTS `link_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `link_items` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `first_item_id` int(11) NOT NULL DEFAULT 0,
  `first_item_type` varchar(15) DEFAULT NULL,
  `second_item_id` int(11) NOT NULL DEFAULT 0,
  `second_item_type` varchar(15) DEFAULT NULL,
  `sorting_place` int(11) DEFAULT NULL,
  `extras` text DEFAULT NULL,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `link_items`
--

LOCK TABLES `link_items` WRITE;
/*!40000 ALTER TABLE `link_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `link_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `link_modifier_item`
--

DROP TABLE IF EXISTS `link_modifier_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `link_modifier_item` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `modifier_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`,`modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `link_modifier_item`
--

LOCK TABLES `link_modifier_item` WRITE;
/*!40000 ALTER TABLE `link_modifier_item` DISABLE KEYS */;
INSERT INTO `link_modifier_item` VALUES (99,98);
/*!40000 ALTER TABLE `link_modifier_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `links`
--

DROP TABLE IF EXISTS `links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `links` (
  `from_item_id` int(11) NOT NULL DEFAULT 0,
  `from_version_id` int(11) NOT NULL DEFAULT 0,
  `to_item_id` int(11) NOT NULL DEFAULT 0,
  `to_version_id` int(11) NOT NULL DEFAULT 0,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `links`
--

LOCK TABLES `links` WRITE;
/*!40000 ALTER TABLE `links` DISABLE KEYS */;
/*!40000 ALTER TABLE `links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) DEFAULT NULL,
  `agent` varchar(250) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `request` varchar(250) DEFAULT NULL,
  `post_content` mediumtext DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_archive`
--

DROP TABLE IF EXISTS `log_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) DEFAULT NULL,
  `agent` varchar(250) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `request` varchar(250) DEFAULT NULL,
  `post_content` mediumtext DEFAULT NULL,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_archive`
--

LOCK TABLES `log_archive` WRITE;
/*!40000 ALTER TABLE `log_archive` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_archive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materials` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `version_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modifier_id` int(11) DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `author` varchar(200) DEFAULT NULL,
  `publishing_date` varchar(20) DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `world_public` smallint(2) NOT NULL DEFAULT 0,
  `extras` text DEFAULT NULL,
  `new_hack` tinyint(1) NOT NULL DEFAULT 0,
  `copy_of` int(11) DEFAULT NULL,
  `workflow_status` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '3_none',
  `workflow_resubmission_date` datetime DEFAULT NULL,
  `workflow_validity_date` datetime DEFAULT NULL,
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  `license_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`,`version_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `modifier_id` (`modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials`
--

LOCK TABLES `materials` WRITE;
/*!40000 ALTER TABLE `materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migration_versions`
--

DROP TABLE IF EXISTS `migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migration_versions` (
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migration_versions`
--

LOCK TABLES `migration_versions` WRITE;
/*!40000 ALTER TABLE `migration_versions` DISABLE KEYS */;
INSERT INTO `migration_versions` VALUES ('DoctrineMigrations\\Version20150623133246','2022-07-04 15:09:49',403),('DoctrineMigrations\\Version20150623135455','2022-07-04 15:09:49',1296),('DoctrineMigrations\\Version20150831152400','2022-07-04 15:09:51',20),('DoctrineMigrations\\Version20150914082323','2022-07-04 15:09:51',2),('DoctrineMigrations\\Version20160718213927','2022-07-04 15:09:51',27),('DoctrineMigrations\\Version20160719021757','2022-07-04 15:09:51',7),('DoctrineMigrations\\Version20160727100551','2022-07-04 15:09:51',3),('DoctrineMigrations\\Version20160727103653','2022-07-04 15:09:51',7),('DoctrineMigrations\\Version20160727111607','2022-07-04 15:09:51',3),('DoctrineMigrations\\Version20160727112623','2022-07-04 15:09:51',9),('DoctrineMigrations\\Version20160727133717','2022-07-04 15:09:51',3),('DoctrineMigrations\\Version20160728231457','2022-07-04 15:09:51',2),('DoctrineMigrations\\Version20170225094328','2022-07-04 15:09:51',2),('DoctrineMigrations\\Version20170225121940','2022-07-04 15:09:51',5),('DoctrineMigrations\\Version20170420141745','2022-07-04 15:09:51',5),('DoctrineMigrations\\Version20170521105856','2022-07-04 15:09:51',6267),('DoctrineMigrations\\Version20170616103508','2022-07-04 15:09:57',161),('DoctrineMigrations\\Version20170714122834','2022-07-04 15:09:57',9),('DoctrineMigrations\\Version20170721185631','2022-07-04 15:09:57',114),('DoctrineMigrations\\Version20170802185102','2022-07-04 15:09:58',24),('DoctrineMigrations\\Version20170810143230','2022-07-04 15:09:58',24),('DoctrineMigrations\\Version20170824064811','2022-07-04 15:09:58',17),('DoctrineMigrations\\Version20170908083138','2022-07-04 15:09:58',72),('DoctrineMigrations\\Version20180212155007','2022-07-04 15:09:58',43),('DoctrineMigrations\\Version20180227100813','2022-07-04 15:09:58',33),('DoctrineMigrations\\Version20180315103403','2022-07-04 15:09:58',56),('DoctrineMigrations\\Version20180713115204','2022-07-04 15:09:58',14),('DoctrineMigrations\\Version20190125123633','2022-07-04 15:09:58',23),('DoctrineMigrations\\Version20190523132611','2022-07-04 15:09:58',27),('DoctrineMigrations\\Version20190708172814','2022-07-04 15:09:58',110),('DoctrineMigrations\\Version20190923121921','2022-07-04 15:09:58',61),('DoctrineMigrations\\Version20190923152100','2022-07-04 15:09:58',298),('DoctrineMigrations\\Version20190924133007','2022-07-04 15:09:58',369),('DoctrineMigrations\\Version20190924140632','2022-07-04 15:09:59',370),('DoctrineMigrations\\Version20191007171054','2022-07-04 15:09:59',260),('DoctrineMigrations\\Version20200617133036','2022-07-04 15:09:59',112),('DoctrineMigrations\\Version20201106104557','2022-07-04 15:10:00',41),('DoctrineMigrations\\Version20210209170044','2022-07-04 15:10:00',67),('DoctrineMigrations\\Version20210311145311','2022-07-04 15:10:00',16),('DoctrineMigrations\\Version20210329134429','2022-07-04 15:10:00',104),('DoctrineMigrations\\Version20210329134856','2022-07-04 15:10:00',3),('DoctrineMigrations\\Version20210406110145','2022-07-04 15:10:00',81),('DoctrineMigrations\\Version20210406133819','2022-07-04 15:10:00',15),('DoctrineMigrations\\Version20210505123313','2022-07-04 15:10:00',3),('DoctrineMigrations\\Version20210506100803','2022-07-04 15:10:00',110),('DoctrineMigrations\\Version20210507071808','2022-07-04 15:10:00',54),('DoctrineMigrations\\Version20210519150306','2022-07-04 15:10:00',136),('DoctrineMigrations\\Version20210913150510','2022-07-04 15:10:00',49),('DoctrineMigrations\\Version20211014124121','2022-07-04 15:10:00',15),('DoctrineMigrations\\Version20211015075059','2022-07-04 15:10:00',9),('DoctrineMigrations\\Version20211015122115','2022-07-04 15:10:00',664),('DoctrineMigrations\\Version20220107083123','2022-07-04 15:10:01',17),('DoctrineMigrations\\Version20220117151238','2022-07-04 15:10:01',8),('DoctrineMigrations\\Version20220222152603','2022-07-04 15:10:01',146),('DoctrineMigrations\\Version20220224121901','2022-07-04 15:10:01',2),('DoctrineMigrations\\Version20220309134224','2022-07-04 15:10:01',3),('DoctrineMigrations\\Version20220325180130','2022-07-04 15:10:01',3),('DoctrineMigrations\\Version20220331141954','2022-07-04 15:10:01',18),('DoctrineMigrations\\Version20220401141736','2022-07-04 15:10:01',691),('DoctrineMigrations\\Version20220414210351','2022-07-04 15:10:02',8),('DoctrineMigrations\\Version20220426072549','2022-07-04 15:10:02',266),('DoctrineMigrations\\Version20220617141005','2022-07-04 15:10:02',731),('DoctrineMigrations\\Version20220624134748','2022-07-04 15:10:03',14);
/*!40000 ALTER TABLE `migration_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `noticed`
--

DROP TABLE IF EXISTS `noticed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `noticed` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `version_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `noticed`
--

LOCK TABLES `noticed` WRITE;
/*!40000 ALTER TABLE `noticed` DISABLE KEYS */;
/*!40000 ALTER TABLE `noticed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal`
--

DROP TABLE IF EXISTS `portal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL,
  `modification_date` datetime NOT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description_de` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `terms_de` text DEFAULT NULL,
  `terms_en` text DEFAULT NULL,
  `logo_filename` varchar(255) DEFAULT NULL,
  `extras` text DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `activity` int(11) NOT NULL DEFAULT 0,
  `clear_inactive_accounts_feature_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `clear_inactive_accounts_notify_lock_days` smallint(6) NOT NULL DEFAULT 180,
  `clear_inactive_accounts_lock_days` smallint(6) NOT NULL DEFAULT 30,
  `clear_inactive_accounts_notify_delete_days` smallint(6) NOT NULL DEFAULT 180,
  `clear_inactive_accounts_delete_days` smallint(6) NOT NULL DEFAULT 30,
  `clear_inactive_rooms_feature_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `clear_inactive_rooms_notify_lock_days` smallint(6) NOT NULL DEFAULT 180,
  `clear_inactive_rooms_lock_days` smallint(6) NOT NULL DEFAULT 30,
  `clear_inactive_rooms_notify_delete_days` smallint(6) NOT NULL DEFAULT 180,
  `clear_inactive_rooms_delete_days` smallint(6) NOT NULL DEFAULT 30,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal`
--

LOCK TABLES `portal` WRITE;
/*!40000 ALTER TABLE `portal` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portfolio`
--

DROP TABLE IF EXISTS `portfolio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portfolio` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext NOT NULL,
  `template` tinyint(4) NOT NULL DEFAULT -1,
  `creation_date` datetime NOT NULL,
  `modification_date` datetime NOT NULL,
  `deletion_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `creator_id` (`creator_id`),
  KEY `modifier_id` (`modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='General Portfolio Information';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portfolio`
--

LOCK TABLES `portfolio` WRITE;
/*!40000 ALTER TABLE `portfolio` DISABLE KEYS */;
/*!40000 ALTER TABLE `portfolio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reader`
--

DROP TABLE IF EXISTS `reader`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reader` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `version_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reader`
--

LOCK TABLES `reader` WRITE;
/*!40000 ALTER TABLE `reader` DISABLE KEYS */;
/*!40000 ALTER TABLE `reader` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room`
--

DROP TABLE IF EXISTS `room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL,
  `modification_date` datetime NOT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `extras` longtext DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `activity` int(11) NOT NULL DEFAULT 0,
  `type` varchar(20) NOT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `is_open_for_guests` tinyint(4) NOT NULL DEFAULT 0,
  `continuous` smallint(6) NOT NULL DEFAULT -1,
  `template` smallint(6) NOT NULL DEFAULT -1,
  `contact_persons` varchar(255) DEFAULT NULL,
  `room_description` varchar(10000) DEFAULT NULL,
  `lastlogin` datetime DEFAULT NULL,
  `activity_state` varchar(15) NOT NULL DEFAULT 'active',
  `activity_state_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `activity` (`activity`),
  KEY `title` (`title`),
  KEY `lastlogin` (`lastlogin`),
  KEY `delete_idx` (`deleter_id`,`deletion_date`),
  KEY `search_idx` (`title`,`contact_persons`),
  KEY `IDX_729F519B61220EA6` (`creator_id`),
  KEY `IDX_729F519BD079F553` (`modifier_id`),
  KEY `IDX_729F519BEAEF1DFE` (`deleter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room`
--

LOCK TABLES `room` WRITE;
/*!40000 ALTER TABLE `room` DISABLE KEYS */;
/*!40000 ALTER TABLE `room` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_categories`
--

DROP TABLE IF EXISTS `room_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_categories`
--

LOCK TABLES `room_categories` WRITE;
/*!40000 ALTER TABLE `room_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_categories_links`
--

DROP TABLE IF EXISTS `room_categories_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_categories_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_categories_links`
--

LOCK TABLES `room_categories_links` WRITE;
/*!40000 ALTER TABLE `room_categories_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_categories_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_privat`
--

DROP TABLE IF EXISTS `room_privat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_privat` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `extras` text DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `activity` int(11) NOT NULL DEFAULT 0,
  `type` varchar(20) NOT NULL DEFAULT 'privateroom',
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `is_open_for_guests` tinyint(4) NOT NULL DEFAULT 0,
  `continuous` tinyint(4) NOT NULL DEFAULT -1,
  `template` tinyint(4) NOT NULL DEFAULT -1,
  `contact_persons` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `lastlogin` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `status` (`status`),
  KEY `creator_id` (`creator_id`),
  KEY `lastlogin` (`lastlogin`),
  KEY `status_2` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_privat`
--

LOCK TABLES `room_privat` WRITE;
/*!40000 ALTER TABLE `room_privat` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_privat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saved_searches`
--

DROP TABLE IF EXISTS `saved_searches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saved_searches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `search_url` varchar(3000) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saved_searches`
--

LOCK TABLES `saved_searches` WRITE;
/*!40000 ALTER TABLE `saved_searches` DISABLE KEYS */;
/*!40000 ALTER TABLE `saved_searches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `section`
--

DROP TABLE IF EXISTS `section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `section` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `version_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `creation_date` datetime DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `number` smallint(6) NOT NULL DEFAULT 0,
  `material_item_id` int(11) NOT NULL DEFAULT 0,
  `extras` text DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`,`version_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `material_item_id` (`material_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `section`
--

LOCK TABLES `section` WRITE;
/*!40000 ALTER TABLE `section` DISABLE KEYS */;
/*!40000 ALTER TABLE `section` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `server`
--

DROP TABLE IF EXISTS `server`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `server` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `extras` text DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `activity` int(11) NOT NULL DEFAULT 0,
  `type` varchar(10) NOT NULL DEFAULT 'server',
  `is_open_for_guests` tinyint(4) NOT NULL DEFAULT 1,
  `url` varchar(255) DEFAULT NULL,
  `logo_image_name` varchar(255) DEFAULT NULL,
  `commsy_icon_link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `server`
--

LOCK TABLES `server` WRITE;
/*!40000 ALTER TABLE `server` DISABLE KEYS */;
INSERT INTO `server` VALUES (99,0,NULL,NULL,NULL,'2006-09-13 12:16:38','2014-08-19 15:38:16',NULL,'CommSy-Server','a:3:{s:8:\"HOMECONF\";s:0:\"\";s:12:\"DEFAULT_AUTH\";s:3:\"100\";s:7:\"VERSION\";s:5:\"8.1.8\";}','1',74,'server',1,'',NULL,NULL);
/*!40000 ALTER TABLE `server` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `sess_id` varchar(128) COLLATE utf8mb4_bin NOT NULL,
  `sess_data` blob NOT NULL,
  `sess_time` int(10) unsigned NOT NULL,
  `sess_lifetime` int(10) unsigned NOT NULL,
  PRIMARY KEY (`sess_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `step`
--

DROP TABLE IF EXISTS `step`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `step` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `creation_date` datetime DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `minutes` float NOT NULL DEFAULT 0,
  `time_type` smallint(6) NOT NULL DEFAULT 1,
  `todo_item_id` int(11) NOT NULL,
  `extras` text DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `todo_item_id` (`todo_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `step`
--

LOCK TABLES `step` WRITE;
/*!40000 ALTER TABLE `step` DISABLE KEYS */;
/*!40000 ALTER TABLE `step` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tag`
--

DROP TABLE IF EXISTS `tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tag` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `public` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tag`
--

LOCK TABLES `tag` WRITE;
/*!40000 ALTER TABLE `tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tag2tag`
--

DROP TABLE IF EXISTS `tag2tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tag2tag` (
  `link_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_item_id` int(11) NOT NULL DEFAULT 0,
  `to_item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) NOT NULL DEFAULT 0,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tag2tag`
--

LOCK TABLES `tag2tag` WRITE;
/*!40000 ALTER TABLE `tag2tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `tag2tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tag_portfolio`
--

DROP TABLE IF EXISTS `tag_portfolio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tag_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT 0,
  `t_id` int(11) NOT NULL DEFAULT 0,
  `row` int(11) DEFAULT 0,
  `column` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`p_id`,`t_id`),
  KEY `row` (`row`,`column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tag_portfolio`
--

LOCK TABLES `tag_portfolio` WRITE;
/*!40000 ALTER TABLE `tag_portfolio` DISABLE KEYS */;
/*!40000 ALTER TABLE `tag_portfolio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL,
  `linked_item_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `template_portfolio`
--

DROP TABLE IF EXISTS `template_portfolio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `template_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT 0,
  `u_id` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`p_id`,`u_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `template_portfolio`
--

LOCK TABLES `template_portfolio` WRITE;
/*!40000 ALTER TABLE `template_portfolio` DISABLE KEYS */;
/*!40000 ALTER TABLE `template_portfolio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `terms`
--

DROP TABLE IF EXISTS `terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content_de` text NOT NULL,
  `content_en` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms`
--

LOCK TABLES `terms` WRITE;
/*!40000 ALTER TABLE `terms` DISABLE KEYS */;
/*!40000 ALTER TABLE `terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `todos`
--

DROP TABLE IF EXISTS `todos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `todos` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `date` datetime DEFAULT NULL,
  `status` tinyint(3) NOT NULL DEFAULT 1,
  `minutes` float DEFAULT NULL,
  `time_type` smallint(6) NOT NULL DEFAULT 1,
  `description` mediumtext DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `extras` text DEFAULT NULL,
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `todos`
--

LOCK TABLES `todos` WRITE;
/*!40000 ALTER TABLE `todos` DISABLE KEYS */;
/*!40000 ALTER TABLE `todos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translation`
--

DROP TABLE IF EXISTS `translation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `translation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) NOT NULL,
  `translation_key` varchar(255) NOT NULL,
  `translation_de` varchar(2000) NOT NULL,
  `translation_en` varchar(2000) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation`
--

LOCK TABLES `translation` WRITE;
/*!40000 ALTER TABLE `translation` DISABLE KEYS */;
/*!40000 ALTER TABLE `translation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT 0,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `not_deleted` tinyint(1) GENERATED ALWAYS AS (if(`deleter_id` is null and `deletion_date` is null,1,NULL)) STORED,
  `user_id` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `is_contact` tinyint(4) NOT NULL DEFAULT 0,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `lastlogin` datetime DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT 1,
  `extras` text DEFAULT NULL,
  `auth_source` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `expire_date` datetime DEFAULT NULL,
  `use_portal_email` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `unique_non_soft_deleted_idx` (`user_id`,`auth_source`,`context_id`,`not_deleted`),
  KEY `deleted_idx` (`deletion_date`,`deleter_id`),
  KEY `creator_idx` (`creator_id`),
  KEY `context_idx` (`context_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (98,99,99,99,NULL,'2006-09-13 12:17:17','2006-09-13 12:17:17',NULL,1,'root',3,1,'CommSy','Administrator','','',NULL,1,'',100,NULL,NULL,0);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_portfolio`
--

DROP TABLE IF EXISTS `user_portfolio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_portfolio` (
  `p_id` int(11) NOT NULL DEFAULT 0,
  `u_id` varchar(32) NOT NULL,
  PRIMARY KEY (`p_id`,`u_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_portfolio`
--

LOCK TABLES `user_portfolio` WRITE;
/*!40000 ALTER TABLE `user_portfolio` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_portfolio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_read`
--

DROP TABLE IF EXISTS `workflow_read`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workflow_read` (
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`item_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_read`
--

LOCK TABLES `workflow_read` WRITE;
/*!40000 ALTER TABLE `workflow_read` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_read` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_annotations`
--

DROP TABLE IF EXISTS `zzz_annotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_annotations` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `linked_item_id` int(11) NOT NULL DEFAULT 0,
  `linked_version_id` int(11) NOT NULL DEFAULT 0,
  `extras` text DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `linked_item_id` (`linked_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_annotations`
--

LOCK TABLES `zzz_annotations` WRITE;
/*!40000 ALTER TABLE `zzz_annotations` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_annotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_announcement`
--

DROP TABLE IF EXISTS `zzz_announcement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_announcement` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `enddate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `extras` text DEFAULT NULL,
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_announcement`
--

LOCK TABLES `zzz_announcement` WRITE;
/*!40000 ALTER TABLE `zzz_announcement` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_announcement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_assessments`
--

DROP TABLE IF EXISTS `zzz_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_assessments`
--

LOCK TABLES `zzz_assessments` WRITE;
/*!40000 ALTER TABLE `zzz_assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_calendars`
--

DROP TABLE IF EXISTS `zzz_calendars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_calendars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `color` varchar(255) NOT NULL,
  `external_url` varchar(255) DEFAULT NULL,
  `default_calendar` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_calendars`
--

LOCK TABLES `zzz_calendars` WRITE;
/*!40000 ALTER TABLE `zzz_calendars` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_calendars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_dates`
--

DROP TABLE IF EXISTS `zzz_dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_dates` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `start_time` varchar(100) DEFAULT NULL,
  `end_time` varchar(100) DEFAULT NULL,
  `start_day` varchar(100) NOT NULL,
  `end_day` varchar(100) DEFAULT NULL,
  `place` varchar(100) DEFAULT NULL,
  `datetime_start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `datetime_end` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `date_mode` tinyint(4) NOT NULL DEFAULT 0,
  `color` varchar(255) DEFAULT NULL,
  `recurrence_id` int(11) DEFAULT NULL,
  `recurrence_pattern` text DEFAULT NULL,
  `extras` text DEFAULT NULL,
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  `calendar_id` int(11) DEFAULT NULL,
  `external` tinyint(4) NOT NULL DEFAULT 0,
  `uid` varchar(255) DEFAULT NULL,
  `whole_day` tinyint(4) NOT NULL DEFAULT 0,
  `datetime_recurrence` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_dates`
--

LOCK TABLES `zzz_dates` WRITE;
/*!40000 ALTER TABLE `zzz_dates` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_dates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_discussionarticles`
--

DROP TABLE IF EXISTS `zzz_discussionarticles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_discussionarticles` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `discussion_id` int(11) NOT NULL DEFAULT 0,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `position` varchar(255) NOT NULL DEFAULT '1',
  `extras` text DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_discussionarticles`
--

LOCK TABLES `zzz_discussionarticles` WRITE;
/*!40000 ALTER TABLE `zzz_discussionarticles` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_discussionarticles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_discussions`
--

DROP TABLE IF EXISTS `zzz_discussions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_discussions` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `latest_article_item_id` int(11) DEFAULT NULL,
  `latest_article_modification_date` datetime DEFAULT NULL,
  `status` int(2) NOT NULL DEFAULT 1,
  `discussion_type` varchar(10) NOT NULL DEFAULT 'simple',
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `extras` text DEFAULT NULL,
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_discussions`
--

LOCK TABLES `zzz_discussions` WRITE;
/*!40000 ALTER TABLE `zzz_discussions` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_discussions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_external_viewer`
--

DROP TABLE IF EXISTS `zzz_external_viewer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_external_viewer` (
  `item_id` int(11) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  PRIMARY KEY (`item_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_external_viewer`
--

LOCK TABLES `zzz_external_viewer` WRITE;
/*!40000 ALTER TABLE `zzz_external_viewer` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_external_viewer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_files`
--

DROP TABLE IF EXISTS `zzz_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `size` int(30) NOT NULL DEFAULT 0,
  `has_html` enum('0','1','2') NOT NULL DEFAULT '0',
  `scan` tinyint(1) NOT NULL DEFAULT -1,
  `extras` text DEFAULT NULL,
  `temp_upload_session_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`files_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_files`
--

LOCK TABLES `zzz_files` WRITE;
/*!40000 ALTER TABLE `zzz_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_hash`
--

DROP TABLE IF EXISTS `zzz_hash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_hash`
--

LOCK TABLES `zzz_hash` WRITE;
/*!40000 ALTER TABLE `zzz_hash` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_hash` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_item_link_file`
--

DROP TABLE IF EXISTS `zzz_item_link_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_item_link_file` (
  `item_iid` int(11) NOT NULL DEFAULT 0,
  `item_vid` int(11) NOT NULL DEFAULT 0,
  `file_id` int(11) NOT NULL DEFAULT 0,
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_iid`,`item_vid`,`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_item_link_file`
--

LOCK TABLES `zzz_item_link_file` WRITE;
/*!40000 ALTER TABLE `zzz_item_link_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_item_link_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_items`
--

DROP TABLE IF EXISTS `zzz_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `type` varchar(15) NOT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `draft` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_items`
--

LOCK TABLES `zzz_items` WRITE;
/*!40000 ALTER TABLE `zzz_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_labels`
--

DROP TABLE IF EXISTS `zzz_labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_labels` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(15) NOT NULL,
  `extras` text DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_labels`
--

LOCK TABLES `zzz_labels` WRITE;
/*!40000 ALTER TABLE `zzz_labels` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_labels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_link_items`
--

DROP TABLE IF EXISTS `zzz_link_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_link_items` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `first_item_id` int(11) NOT NULL DEFAULT 0,
  `first_item_type` varchar(15) DEFAULT NULL,
  `second_item_id` int(11) NOT NULL DEFAULT 0,
  `second_item_type` varchar(15) DEFAULT NULL,
  `sorting_place` int(11) DEFAULT NULL,
  `extras` text DEFAULT NULL,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_link_items`
--

LOCK TABLES `zzz_link_items` WRITE;
/*!40000 ALTER TABLE `zzz_link_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_link_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_link_modifier_item`
--

DROP TABLE IF EXISTS `zzz_link_modifier_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_link_modifier_item` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `modifier_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`,`modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_link_modifier_item`
--

LOCK TABLES `zzz_link_modifier_item` WRITE;
/*!40000 ALTER TABLE `zzz_link_modifier_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_link_modifier_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_links`
--

DROP TABLE IF EXISTS `zzz_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_links` (
  `from_item_id` int(11) NOT NULL DEFAULT 0,
  `from_version_id` int(11) NOT NULL DEFAULT 0,
  `to_item_id` int(11) NOT NULL DEFAULT 0,
  `to_version_id` int(11) NOT NULL DEFAULT 0,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_links`
--

LOCK TABLES `zzz_links` WRITE;
/*!40000 ALTER TABLE `zzz_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_materials`
--

DROP TABLE IF EXISTS `zzz_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_materials` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `version_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modifier_id` int(11) DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `author` varchar(200) DEFAULT NULL,
  `publishing_date` varchar(20) DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `world_public` smallint(2) NOT NULL DEFAULT 0,
  `extras` text DEFAULT NULL,
  `new_hack` tinyint(1) NOT NULL DEFAULT 0,
  `copy_of` int(11) DEFAULT NULL,
  `workflow_status` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '3_none',
  `workflow_resubmission_date` datetime DEFAULT NULL,
  `workflow_validity_date` datetime DEFAULT NULL,
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  `license_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`,`version_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `modifier_id` (`modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_materials`
--

LOCK TABLES `zzz_materials` WRITE;
/*!40000 ALTER TABLE `zzz_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_noticed`
--

DROP TABLE IF EXISTS `zzz_noticed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_noticed` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `version_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_noticed`
--

LOCK TABLES `zzz_noticed` WRITE;
/*!40000 ALTER TABLE `zzz_noticed` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_noticed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_reader`
--

DROP TABLE IF EXISTS `zzz_reader`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_reader` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `version_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_reader`
--

LOCK TABLES `zzz_reader` WRITE;
/*!40000 ALTER TABLE `zzz_reader` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_reader` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_room`
--

DROP TABLE IF EXISTS `zzz_room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_room` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL,
  `modification_date` datetime NOT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `extras` longtext DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `activity` int(11) NOT NULL DEFAULT 0,
  `type` varchar(20) NOT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `is_open_for_guests` tinyint(4) NOT NULL DEFAULT 0,
  `continuous` smallint(6) NOT NULL DEFAULT -1,
  `template` smallint(6) NOT NULL DEFAULT -1,
  `contact_persons` varchar(255) DEFAULT NULL,
  `room_description` varchar(10000) DEFAULT NULL,
  `lastlogin` datetime DEFAULT NULL,
  `activity_state` varchar(15) NOT NULL DEFAULT 'active',
  `activity_state_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `activity` (`activity`),
  KEY `deletion_date` (`deletion_date`),
  KEY `deleter_id` (`deleter_id`),
  KEY `title` (`title`),
  KEY `lastlogin` (`lastlogin`),
  KEY `IDX_538B256EEAEF1DFE` (`deleter_id`),
  KEY `delete_idx` (`deleter_id`,`deletion_date`),
  KEY `search_idx` (`title`,`contact_persons`),
  KEY `IDX_538B256E61220EA6` (`creator_id`),
  KEY `IDX_538B256ED079F553` (`modifier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_room`
--

LOCK TABLES `zzz_room` WRITE;
/*!40000 ALTER TABLE `zzz_room` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_room` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_section`
--

DROP TABLE IF EXISTS `zzz_section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_section` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `version_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `creation_date` datetime DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `number` smallint(6) NOT NULL DEFAULT 0,
  `material_item_id` int(11) NOT NULL DEFAULT 0,
  `extras` text DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`,`version_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `material_item_id` (`material_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_section`
--

LOCK TABLES `zzz_section` WRITE;
/*!40000 ALTER TABLE `zzz_section` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_section` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_step`
--

DROP TABLE IF EXISTS `zzz_step`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_step` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `creation_date` datetime DEFAULT '0000-00-00 00:00:00',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `minutes` float NOT NULL DEFAULT 0,
  `time_type` smallint(6) NOT NULL DEFAULT 1,
  `todo_item_id` int(11) NOT NULL,
  `extras` text DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `todo_item_id` (`todo_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_step`
--

LOCK TABLES `zzz_step` WRITE;
/*!40000 ALTER TABLE `zzz_step` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_step` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_tag`
--

DROP TABLE IF EXISTS `zzz_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_tag` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `public` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_tag`
--

LOCK TABLES `zzz_tag` WRITE;
/*!40000 ALTER TABLE `zzz_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_tag2tag`
--

DROP TABLE IF EXISTS `zzz_tag2tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_tag2tag` (
  `link_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_item_id` int(11) NOT NULL DEFAULT 0,
  `to_item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) NOT NULL DEFAULT 0,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_tag2tag`
--

LOCK TABLES `zzz_tag2tag` WRITE;
/*!40000 ALTER TABLE `zzz_tag2tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_tag2tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_tasks`
--

DROP TABLE IF EXISTS `zzz_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_tasks` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL,
  `linked_item_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_tasks`
--

LOCK TABLES `zzz_tasks` WRITE;
/*!40000 ALTER TABLE `zzz_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_todos`
--

DROP TABLE IF EXISTS `zzz_todos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_todos` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `date` datetime DEFAULT NULL,
  `status` tinyint(3) NOT NULL DEFAULT 1,
  `minutes` float DEFAULT NULL,
  `time_type` smallint(6) NOT NULL DEFAULT 1,
  `description` mediumtext DEFAULT NULL,
  `public` tinyint(11) NOT NULL DEFAULT 0,
  `extras` text DEFAULT NULL,
  `locking_date` datetime DEFAULT NULL,
  `locking_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_todos`
--

LOCK TABLES `zzz_todos` WRITE;
/*!40000 ALTER TABLE `zzz_todos` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_todos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_user`
--

DROP TABLE IF EXISTS `zzz_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_user` (
  `item_id` int(11) NOT NULL DEFAULT 0,
  `context_id` int(11) DEFAULT NULL,
  `creator_id` int(11) NOT NULL DEFAULT 0,
  `modifier_id` int(11) DEFAULT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modification_date` datetime DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `not_deleted` tinyint(1) GENERATED ALWAYS AS (if(`deleter_id` is null and `deletion_date` is null,1,NULL)) STORED,
  `user_id` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `is_contact` tinyint(4) NOT NULL DEFAULT 0,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `lastlogin` datetime DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT 1,
  `extras` text DEFAULT NULL,
  `auth_source` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `expire_date` datetime DEFAULT NULL,
  `use_portal_email` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `unique_non_soft_deleted_idx` (`user_id`,`auth_source`,`context_id`,`not_deleted`),
  KEY `deleted_idx` (`deletion_date`,`deleter_id`),
  KEY `creator_idx` (`creator_id`),
  KEY `context_idx` (`context_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_user`
--

LOCK TABLES `zzz_user` WRITE;
/*!40000 ALTER TABLE `zzz_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zzz_workflow_read`
--

DROP TABLE IF EXISTS `zzz_workflow_read`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zzz_workflow_read` (
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`item_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zzz_workflow_read`
--

LOCK TABLES `zzz_workflow_read` WRITE;
/*!40000 ALTER TABLE `zzz_workflow_read` DISABLE KEYS */;
/*!40000 ALTER TABLE `zzz_workflow_read` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-07-04 13:11:30
