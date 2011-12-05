-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 05. Dezember 2011 um 10:01
-- Server Version: 5.1.44
-- PHP-Version: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `commsy_db_dump`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `annotations`
--

CREATE TABLE IF NOT EXISTS `annotations` (
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
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `linked_item_id` (`linked_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `annotations`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `announcement`
--

CREATE TABLE IF NOT EXISTS `announcement` (
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

--
-- Daten für Tabelle `announcement`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `assessments`
--

CREATE TABLE IF NOT EXISTS `assessments` (
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

--
-- Daten für Tabelle `assessments`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `auth`
--

CREATE TABLE IF NOT EXISTS `auth` (
  `commsy_id` int(11) NOT NULL DEFAULT '0',
  `user_id` varchar(32) NOT NULL,
  `password_md5` varchar(32) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `language` varchar(10) NOT NULL,
  PRIMARY KEY (`commsy_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `auth`
--

INSERT INTO `auth` (`commsy_id`, `user_id`, `password_md5`, `firstname`, `lastname`, `email`, `language`) VALUES
(99, 'root', '63a9f0ea7bb98050796b649e85481845', 'CommSy', 'Administrator', '', 'de');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `auth_source`
--

CREATE TABLE IF NOT EXISTS `auth_source` (
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

--
-- Daten für Tabelle `auth_source`
--

INSERT INTO `auth_source` (`item_id`, `context_id`, `creator_id`, `modifier_id`, `deleter_id`, `creation_date`, `modification_date`, `deletion_date`, `title`, `extras`) VALUES
(100, 99, 99, 99, NULL, '2006-09-14 12:32:24', '2006-09-14 12:32:24', NULL, 'CommSy', 'a:4:{s:14:"COMMSY_DEFAULT";s:1:"1";s:6:"SOURCE";s:5:"MYSQL";s:13:"CONFIGURATION";a:5:{s:11:"ADD_ACCOUNT";s:1:"0";s:13:"CHANGE_USERID";s:1:"0";s:14:"DELETE_ACCOUNT";s:1:"0";s:15:"CHANGE_USERDATA";s:1:"1";s:15:"CHANGE_PASSWORD";s:1:"1";}s:4:"SHOW";s:1:"1";}');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `dates`
--

CREATE TABLE IF NOT EXISTS `dates` (
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

--
-- Daten für Tabelle `dates`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `discussionarticles`
--

CREATE TABLE IF NOT EXISTS `discussionarticles` (
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
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `discussionarticles`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `discussions`
--

CREATE TABLE IF NOT EXISTS `discussions` (
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

--
-- Daten für Tabelle `discussions`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `external2commsy_id`
--

CREATE TABLE IF NOT EXISTS `external2commsy_id` (
  `external_id` varchar(255) NOT NULL,
  `source_system` varchar(60) NOT NULL,
  `commsy_id` int(11) NOT NULL,
  PRIMARY KEY (`external_id`,`source_system`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `external2commsy_id`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `external_viewer`
--

CREATE TABLE IF NOT EXISTS `external_viewer` (
  `item_id` int(11) NOT NULL,
  `user_id` varchar(32) NOT NULL,
  KEY `item_id` (`item_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `external_viewer`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `files`
--

CREATE TABLE IF NOT EXISTS `files` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

--
-- Daten für Tabelle `files`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `file_multi_upload`
--

CREATE TABLE IF NOT EXISTS `file_multi_upload` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(150) NOT NULL,
  `file_array` text NOT NULL,
  `cid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `file_multi_upload`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `hash`
--

CREATE TABLE IF NOT EXISTS `hash` (
  `user_item_id` int(11) NOT NULL,
  `rss` char(32) DEFAULT NULL,
  `ical` char(32) DEFAULT NULL,
  PRIMARY KEY (`user_item_id`),
  KEY `rss` (`rss`),
  KEY `ical` (`ical`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `hash`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `homepage_link_page_page`
--

CREATE TABLE IF NOT EXISTS `homepage_link_page_page` (
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
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `homepage_link_page_page`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `homepage_page`
--

CREATE TABLE IF NOT EXISTS `homepage_page` (
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

--
-- Daten für Tabelle `homepage_page`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `items`
--

CREATE TABLE IF NOT EXISTS `items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `type` varchar(15) NOT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=160 ;

--
-- Daten für Tabelle `items`
--

INSERT INTO `items` (`item_id`, `context_id`, `type`, `deleter_id`, `deletion_date`, `modification_date`) VALUES
(98, 99, 'user', NULL, NULL, NULL),
(99, 0, 'server', NULL, NULL, NULL),
(100, 99, 'auth_source', NULL, NULL, '2006-09-14 12:32:24');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `item_backup`
--

CREATE TABLE IF NOT EXISTS `item_backup` (
  `item_id` int(11) NOT NULL,
  `backup_date` datetime NOT NULL,
  `modification_date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `public` tinyint(11) NOT NULL,
  `special` text CHARACTER SET ucs2 NOT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `item_backup`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `item_link_file`
--

CREATE TABLE IF NOT EXISTS `item_link_file` (
  `item_iid` int(11) NOT NULL DEFAULT '0',
  `item_vid` int(11) NOT NULL DEFAULT '0',
  `file_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_iid`,`item_vid`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `item_link_file`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `labels`
--

CREATE TABLE IF NOT EXISTS `labels` (
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
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `labels`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `links`
--

CREATE TABLE IF NOT EXISTS `links` (
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

--
-- Daten für Tabelle `links`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `link_items`
--

CREATE TABLE IF NOT EXISTS `link_items` (
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
  KEY `second_item_id` (`second_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `link_items`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `link_modifier_item`
--

CREATE TABLE IF NOT EXISTS `link_modifier_item` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`,`modifier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `link_modifier_item`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log`
--

CREATE TABLE IF NOT EXISTS `log` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1553 ;

--
-- Daten für Tabelle `log`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_ads`
--

CREATE TABLE IF NOT EXISTS `log_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) DEFAULT NULL,
  `aim` varchar(255) NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `log_ads`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_archive`
--

CREATE TABLE IF NOT EXISTS `log_archive` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `log_archive`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_error`
--

CREATE TABLE IF NOT EXISTS `log_error` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=71202 ;

--
-- Daten für Tabelle `log_error`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_message_tag`
--

CREATE TABLE IF NOT EXISTS `log_message_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) NOT NULL,
  `version` varchar(50) NOT NULL,
  `datetime` datetime NOT NULL,
  `language` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=61 ;

--
-- Daten für Tabelle `log_message_tag`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `materials`
--

CREATE TABLE IF NOT EXISTS `materials` (
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

--
-- Daten für Tabelle `materials`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `noticed`
--

CREATE TABLE IF NOT EXISTS `noticed` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `noticed`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `portal`
--

CREATE TABLE IF NOT EXISTS `portal` (
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

--
-- Daten für Tabelle `portal`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `reader`
--

CREATE TABLE IF NOT EXISTS `reader` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `reader`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `room`
--

CREATE TABLE IF NOT EXISTS `room` (
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
  KEY `status_2` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `room`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `room_privat`
--

CREATE TABLE IF NOT EXISTS `room_privat` (
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
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `status` (`status`),
  KEY `creator_id` (`creator_id`),
  KEY `status_2` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `room_privat`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `search_index`
--

CREATE TABLE IF NOT EXISTS `search_index` (
  `si_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `si_sw_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `si_item_id` int(11) NOT NULL DEFAULT '0',
  `si_item_type` varchar(15) NOT NULL,
  `si_count` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`si_id`),
  UNIQUE KEY `un_si_sw_id` (`si_item_id`,`si_sw_id`,`si_item_type`),
  KEY `si_sw_id` (`si_sw_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `search_index`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `search_time`
--

CREATE TABLE IF NOT EXISTS `search_time` (
  `st_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `st_item_id` int(11) NOT NULL DEFAULT '0',
  `st_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`st_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `search_time`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `search_word`
--

CREATE TABLE IF NOT EXISTS `search_word` (
  `sw_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `sw_word` varchar(32) NOT NULL DEFAULT '',
  `sw_lang` varchar(5) NOT NULL,
  PRIMARY KEY (`sw_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `search_word`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `section`
--

CREATE TABLE IF NOT EXISTS `section` (
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
  PRIMARY KEY (`item_id`,`version_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `material_item_id` (`material_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `section`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `server`
--

CREATE TABLE IF NOT EXISTS `server` (
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

--
-- Daten für Tabelle `server`
--

INSERT INTO `server` (`item_id`, `context_id`, `creator_id`, `modifier_id`, `deleter_id`, `creation_date`, `modification_date`, `deletion_date`, `title`, `extras`, `status`, `activity`, `type`, `is_open_for_guests`, `url`) VALUES
(99, 0, 99, 0, NULL, '2006-09-13 12:16:38', '2006-09-13 12:16:38', NULL, 'CommSy-Server', 'a:2:{s:8:"HOMECONF";s:0:"";s:12:"DEFAULT_AUTH";s:3:"100";}', '1', 30, 'server', 1, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `session`
--

CREATE TABLE IF NOT EXISTS `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(150) NOT NULL,
  `session_key` varchar(30) NOT NULL,
  `session_value` longtext NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=60 ;

--
-- Daten für Tabelle `session`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `step`
--

CREATE TABLE IF NOT EXISTS `step` (
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
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `todo_item_id` (`todo_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `step`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tag`
--

CREATE TABLE IF NOT EXISTS `tag` (
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

--
-- Daten für Tabelle `tag`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tag2tag`
--

CREATE TABLE IF NOT EXISTS `tag2tag` (
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
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `tag2tag`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tasks`
--

CREATE TABLE IF NOT EXISTS `tasks` (
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

--
-- Daten für Tabelle `tasks`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `todos`
--

CREATE TABLE IF NOT EXISTS `todos` (
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

--
-- Daten für Tabelle `todos`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE IF NOT EXISTS `user` (
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
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `user`
--

INSERT INTO `user` (`item_id`, `context_id`, `creator_id`, `modifier_id`, `deleter_id`, `creation_date`, `modification_date`, `deletion_date`, `user_id`, `status`, `is_contact`, `firstname`, `lastname`, `email`, `city`, `lastlogin`, `visible`, `extras`, `auth_source`, `description`) VALUES
(98, 99, 99, 99, NULL, '2006-09-13 12:17:17', '2006-09-13 12:17:17', NULL, 'root', 3, 1, 'CommSy', 'Administrator', '', '', NULL, 1, '', 100, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `workflow_read`
--

CREATE TABLE IF NOT EXISTS `workflow_read` (
  `item_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  KEY `item_id` (`item_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `workflow_read`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_annotations`
--

CREATE TABLE IF NOT EXISTS `zzz_annotations` (
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
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `linked_item_id` (`linked_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_annotations`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_announcement`
--

CREATE TABLE IF NOT EXISTS `zzz_announcement` (
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

--
-- Daten für Tabelle `zzz_announcement`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_dates`
--

CREATE TABLE IF NOT EXISTS `zzz_dates` (
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

--
-- Daten für Tabelle `zzz_dates`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_discussionarticles`
--

CREATE TABLE IF NOT EXISTS `zzz_discussionarticles` (
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
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_discussionarticles`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_discussions`
--

CREATE TABLE IF NOT EXISTS `zzz_discussions` (
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

--
-- Daten für Tabelle `zzz_discussions`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_files`
--

CREATE TABLE IF NOT EXISTS `zzz_files` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

--
-- Daten für Tabelle `zzz_files`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_hash`
--

CREATE TABLE IF NOT EXISTS `zzz_hash` (
  `user_item_id` int(11) NOT NULL,
  `rss` char(32) DEFAULT NULL,
  `ical` char(32) DEFAULT NULL,
  PRIMARY KEY (`user_item_id`),
  KEY `rss` (`rss`),
  KEY `ical` (`ical`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_hash`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_homepage_link_page_page`
--

CREATE TABLE IF NOT EXISTS `zzz_homepage_link_page_page` (
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
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `zzz_homepage_link_page_page`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_homepage_page`
--

CREATE TABLE IF NOT EXISTS `zzz_homepage_page` (
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

--
-- Daten für Tabelle `zzz_homepage_page`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_items`
--

CREATE TABLE IF NOT EXISTS `zzz_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `context_id` int(11) DEFAULT NULL,
  `type` varchar(15) NOT NULL,
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  `modification_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=137 ;

--
-- Daten für Tabelle `zzz_items`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_item_link_file`
--

CREATE TABLE IF NOT EXISTS `zzz_item_link_file` (
  `item_iid` int(11) NOT NULL DEFAULT '0',
  `item_vid` int(11) NOT NULL DEFAULT '0',
  `file_id` int(11) NOT NULL DEFAULT '0',
  `deleter_id` int(11) DEFAULT NULL,
  `deletion_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_iid`,`item_vid`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_item_link_file`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_labels`
--

CREATE TABLE IF NOT EXISTS `zzz_labels` (
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
  KEY `creator_id` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_labels`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_links`
--

CREATE TABLE IF NOT EXISTS `zzz_links` (
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

--
-- Daten für Tabelle `zzz_links`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_link_items`
--

CREATE TABLE IF NOT EXISTS `zzz_link_items` (
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
  KEY `second_item_id` (`second_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_link_items`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_link_modifier_item`
--

CREATE TABLE IF NOT EXISTS `zzz_link_modifier_item` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `modifier_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`,`modifier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_link_modifier_item`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_materials`
--

CREATE TABLE IF NOT EXISTS `zzz_materials` (
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

--
-- Daten für Tabelle `zzz_materials`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_noticed`
--

CREATE TABLE IF NOT EXISTS `zzz_noticed` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_noticed`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_reader`
--

CREATE TABLE IF NOT EXISTS `zzz_reader` (
  `item_id` int(11) NOT NULL DEFAULT '0',
  `version_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `read_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`item_id`,`version_id`,`user_id`,`read_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_reader`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_room`
--

CREATE TABLE IF NOT EXISTS `zzz_room` (
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
  KEY `status_2` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_room`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_section`
--

CREATE TABLE IF NOT EXISTS `zzz_section` (
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
  PRIMARY KEY (`item_id`,`version_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `material_item_id` (`material_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_section`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_step`
--

CREATE TABLE IF NOT EXISTS `zzz_step` (
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
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `todo_item_id` (`todo_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_step`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_tag`
--

CREATE TABLE IF NOT EXISTS `zzz_tag` (
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

--
-- Daten für Tabelle `zzz_tag`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_tag2tag`
--

CREATE TABLE IF NOT EXISTS `zzz_tag2tag` (
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
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `zzz_tag2tag`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_tasks`
--

CREATE TABLE IF NOT EXISTS `zzz_tasks` (
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

--
-- Daten für Tabelle `zzz_tasks`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_todos`
--

CREATE TABLE IF NOT EXISTS `zzz_todos` (
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

--
-- Daten für Tabelle `zzz_todos`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_user`
--

CREATE TABLE IF NOT EXISTS `zzz_user` (
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
  PRIMARY KEY (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_user`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zzz_workflow_read`
--

CREATE TABLE IF NOT EXISTS `zzz_workflow_read` (
  `item_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  KEY `item_id` (`item_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `zzz_workflow_read`
--

