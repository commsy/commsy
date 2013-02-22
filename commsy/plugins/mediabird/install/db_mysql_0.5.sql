-- phpMyAdmin SQL Dump
-- version 2.11.3deb1ubuntu1.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 28, 2009 at 12:33 PM
-- Server version: 5.0.51
-- PHP Version: 5.2.4-2ubuntu5.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `mediabird`
--
-- CREATE DATABASE `mediabird` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
-- USE `mediabird`;

-- --------------------------------------------------------

--
-- Table structure for table `cards`
--

CREATE TABLE IF NOT EXISTS `cards` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) unsigned NOT NULL,
  `title` varchar(60) NOT NULL default '',
  `index` int(11) unsigned NOT NULL,
  `topic` int(11) unsigned NOT NULL,
  `level` int(11) unsigned NOT NULL,
  `content` text,
  `revision` int(11) unsigned NOT NULL default '0',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `locked_by` int(11) unsigned NOT NULL default '0',
  `locked_time` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `top_ix` (`topic`),
  KEY `loc_ix` (`locked_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `feed_messages`
--

CREATE TABLE IF NOT EXISTS `feed_messages` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `feed_id` int(11) unsigned default NULL,
  `user_id` int(11) unsigned default NULL,
  `object_id` int(11) unsigned default NULL,
  `object_type` int(11) unsigned default NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `fee_ix` (`feed_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `feed_messages_status`
--

CREATE TABLE IF NOT EXISTS `feed_messages_status` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `message_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `status` smallint(4) unsigned NOT NULL default '0',
  `created` datetime default NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `mes_ix` (`message_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `feed_subscriptions`
--

CREATE TABLE IF NOT EXISTS `feed_subscriptions` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user_id` int(11) unsigned default NULL,
  `feed_id` int(11) unsigned default NULL,
  `use_email` tinyint(1) unsigned NOT NULL default '1',
  `use_view` tinyint(1) unsigned NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `use_ix` (`user_id`),
  KEY `fee_ix` (`feed_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `feeds`
--

CREATE TABLE IF NOT EXISTS `feeds` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `message_type` int(11) unsigned default NULL,
  `title` varchar(255) NOT NULL default '',
  `created` datetime default NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `flashcards`
--

CREATE TABLE IF NOT EXISTS `flashcards` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `marker` int(11) unsigned NOT NULL,
  `user` int(11) unsigned NOT NULL,
  `number` int(11) unsigned NOT NULL,
  `level` int(11) unsigned NOT NULL,
  `lasttimeanswered` bigint(20) unsigned NOT NULL,
  `markedforrepetition` int(11) unsigned NOT NULL,
  `results` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `mar_ix` (`marker`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(60) NOT NULL default '',
  `description` text,
  `type` int(11) unsigned NOT NULL,
  `access` int(11) unsigned NOT NULL,
  `category` varchar(60) NOT NULL default '',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `markers`
--

CREATE TABLE IF NOT EXISTS `markers` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `card` int(11) unsigned default NULL,
  `user` int(11) unsigned NOT NULL default '0',
  `tool` varchar(60) default NULL,
  `data` text,
  `range` text,
  `shared` tinyint(1) unsigned NOT NULL default '0',
  `notify` tinyint(1) unsigned NOT NULL default '0',
  `revision` int(11) unsigned NOT NULL default '0',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `car_ix` (`card`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `memberships`
--

CREATE TABLE IF NOT EXISTS `memberships` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) unsigned NOT NULL,
  `group` int(11) unsigned NOT NULL,
  `level` int(11) unsigned NOT NULL default '0',
  `active` int(11) unsigned NOT NULL default '0',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `gro_ix` (`group`),
  KEY `use_ix` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `prerequisites`
--

CREATE TABLE IF NOT EXISTS `prerequisites` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `topic` int(11) unsigned NOT NULL,
  `title` varchar(60) NOT NULL default '',
  `requiredtopic` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `relation_answers`
--

CREATE TABLE IF NOT EXISTS `relation_answers` (
  `id` bigint(11) unsigned NOT NULL auto_increment,
  `answer` varchar(1536) default NULL,
  `user_id` bigint(11) unsigned NOT NULL,
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `relation_links`
--

CREATE TABLE IF NOT EXISTS `relation_links` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `link` varchar(3000) NOT NULL default '',
  `title` varchar(80) NOT NULL default '',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `lin_ix` (`link`(333))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `relation_questions`
--

CREATE TABLE IF NOT EXISTS `relation_questions` (
  `id` bigint(11) unsigned NOT NULL auto_increment,
  `question` varchar(1536) NOT NULL default '',
  `user_id` bigint(11) unsigned NOT NULL,
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `relation_translations`
--

CREATE TABLE IF NOT EXISTS `relation_translations` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `source_lang` int(11) unsigned NOT NULL,
  `source_term` varchar(1024) NOT NULL default '',
  `dest_lang` int(11) unsigned NOT NULL,
  `dest_term` varchar(1024) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `relations`
--

CREATE TABLE IF NOT EXISTS `relations` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `marker_id` int(11) unsigned NOT NULL,
  `relation_id` int(11) unsigned NOT NULL,
  `relation_type` varchar(21) NOT NULL default '',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `mar_ix` (`marker_id`),
  KEY `rel_ix` (`relation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rights`
--

CREATE TABLE IF NOT EXISTS `rights` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `group` int(11) unsigned NOT NULL,
  `topic` int(11) unsigned NOT NULL,
  `mask` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `gro_ix` (`group`),
  KEY `top_ix` (`topic`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `topics`
--

CREATE TABLE IF NOT EXISTS `topics` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` int(11) unsigned NOT NULL,
  `title` varchar(60) NOT NULL default '',
  `category` varchar(60) NOT NULL default '',
  `license` int(11) unsigned default '0',
  `language` int(11) unsigned NOT NULL default '0',
  `type` int(11) unsigned NOT NULL default '0',
  `revision` int(11) unsigned NOT NULL default '0',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `use_ix` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(60) default NULL,
  `password` varchar(60) default NULL,
  `email` varchar(60) default NULL,
  `active` int(11) unsigned default '0',
  `settings` text,
  `quota` int(11) unsigned default '0',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `last_login` datetime NOT NULL default '0000-00-00 00:00:00',
  `overlay_coords` bigint(20) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `nam_ix` (`name`,`password`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
