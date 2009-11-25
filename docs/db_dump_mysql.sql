-- phpMyAdmin SQL Dump
-- version 2.11.8.1deb1ubuntu0.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 25. November 2009 um 12:34
-- Server Version: 5.0.67
-- PHP-Version: 5.2.6-2ubuntu4.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `commsy_update`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `item_id` int(11) NOT NULL default '0',
  `context_id` int(11) default NULL,
  `creator_id` int(11) NOT NULL default '0',
  `modifier_id` int(11) default NULL,
  `deleter_id` int(11) default NULL,
  `creation_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `modification_date` datetime default NULL,
  `deletion_date` datetime default NULL,
  `user_id` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL default '0',
  `is_contact` tinyint(4) NOT NULL default '0',
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `lastlogin` datetime default NULL,
  `visible` tinyint(4) NOT NULL default '1',
  `extras` text,
  `auth_source` int(11) default NULL,
  `description` text,
  PRIMARY KEY  (`item_id`),
  KEY `context_id` (`context_id`),
  KEY `creator_id` (`creator_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `user`
--

INSERT INTO `user` (`item_id`, `context_id`, `creator_id`, `modifier_id`, `deleter_id`, `creation_date`, `modification_date`, `deletion_date`, `user_id`, `status`, `is_contact`, `firstname`, `lastname`, `email`, `city`, `lastlogin`, `visible`, `extras`, `auth_source`, `description`) VALUES
(98, 99, 99, 99, NULL, '2006-09-13 12:17:17', '2006-09-13 12:17:17', NULL, 'root', 3, 1, 'CommSy', 'Administrator', '', '', NULL, 1, '', 100, NULL);
