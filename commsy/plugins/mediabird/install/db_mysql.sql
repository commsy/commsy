-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 16. November 2012 um 10:46
-- Server Version: 5.1.44
-- PHP-Version: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Datenbank: `mediabird`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cards`
--

CREATE TABLE IF NOT EXISTS `cards` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(60) NOT NULL,
  `index_num` int(11) unsigned NOT NULL,
  `topic_id` int(11) unsigned NOT NULL,
  `content_type` int(11) unsigned NOT NULL,
  `content_id` int(11) unsigned NOT NULL,
  `content_index` int(11) unsigned NOT NULL,
  `modifier` int(11) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `top_ix` (`topic_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cards`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `card_contents`
--

CREATE TABLE IF NOT EXISTS `card_contents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `locked_by` int(11) unsigned NOT NULL,
  `locked_time` datetime NOT NULL,
  `topic_id` int(11) unsigned NOT NULL,
  `card_id` int(11) unsigned NOT NULL,
  `content` text,
  `modifier` int(11) unsigned NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `top_ix` (`topic_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `card_contents`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `card_tags`
--

CREATE TABLE IF NOT EXISTS `card_tags` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `card_id` int(11) unsigned NOT NULL,
  `tag_id` int(11) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `crd_id` (`card_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `card_tags`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `checks`
--

CREATE TABLE IF NOT EXISTS `checks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `modifier` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `checks`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `check_statusses`
--

CREATE TABLE IF NOT EXISTS `check_statusses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `check_id` int(11) NOT NULL,
  `status_code` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `check_statusses`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `markers`
--

CREATE TABLE IF NOT EXISTS `markers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `card_id` int(11) unsigned NOT NULL,
  `card_type` int(11) unsigned NOT NULL,
  `topic_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `tool` varchar(60) DEFAULT NULL,
  `range_store` text,
  `shared` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `car_ix` (`card_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `markers`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `relations`
--

CREATE TABLE IF NOT EXISTS `relations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `marker_id` int(11) unsigned NOT NULL,
  `topic_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `relation_id` int(11) unsigned NOT NULL,
  `relation_type` varchar(21) NOT NULL DEFAULT '',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mar_ix` (`marker_id`),
  KEY `rel_ix` (`relation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `relations`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `relation_answers`
--

CREATE TABLE IF NOT EXISTS `relation_answers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `answer` varchar(1536) DEFAULT NULL,
  `question_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `que_ix` (`question_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `relation_answers`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `relation_flashcards`
--

CREATE TABLE IF NOT EXISTS `relation_flashcards` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `level_num` int(11) unsigned NOT NULL,
  `results` int(11) unsigned NOT NULL,
  `answer_time` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `relation_flashcards`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `relation_links`
--

CREATE TABLE IF NOT EXISTS `relation_links` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(3000) NOT NULL,
  `title` varchar(80) NOT NULL,
  `type_num` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `modifier` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `url_ix` (`url`(333)),
  KEY `usr_ix` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `relation_links`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `relation_questions`
--

CREATE TABLE IF NOT EXISTS `relation_questions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(1536) DEFAULT NULL,
  `question_mode` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `modifier` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `relation_questions`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `relation_stars`
--

CREATE TABLE IF NOT EXISTS `relation_stars` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `answer_id` int(11) unsigned NOT NULL,
  `question_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ans_ix` (`answer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `relation_stars`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `relation_votes`
--

CREATE TABLE IF NOT EXISTS `relation_votes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `answer_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ans_ix` (`answer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `relation_votes`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `rights`
--

CREATE TABLE IF NOT EXISTS `rights` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `topic_id` int(11) unsigned NOT NULL,
  `mask` int(11) unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usr_ix` (`user_id`),
  KEY `top_ix` (`topic_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `rights`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `card_id` int(11) unsigned NOT NULL,
  `editing` tinyint(1) NOT NULL,
  `modified` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `sessions`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tags`
--

CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `color` varchar(6) NOT NULL DEFAULT '00FF66',
  `title` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `tags`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tag_colors`
--

CREATE TABLE IF NOT EXISTS `tag_colors` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `tag_id` int(11) unsigned NOT NULL,
  `color` varchar(6) NOT NULL,
  `display_text` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `tag_colors`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `topics`
--

CREATE TABLE IF NOT EXISTS `topics` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `title` varchar(60) NOT NULL DEFAULT '',
  `category` varchar(60) NOT NULL DEFAULT '',
  `license` int(11) unsigned DEFAULT '0',
  `modifier` int(11) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `use_ix` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `topics`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `uploads`
--

CREATE TABLE IF NOT EXISTS `uploads` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(60) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `uploads`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `upload_access`
--

CREATE TABLE IF NOT EXISTS `upload_access` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `upload_id` int(11) unsigned NOT NULL,
  `mask` int(11) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `upload_access`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT NULL,
  `password` varchar(60) DEFAULT NULL,
  `email` varchar(60) DEFAULT NULL,
  `active` int(11) unsigned DEFAULT '0',
  `settings` text,
  `quota` int(11) unsigned DEFAULT '0',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `last_login` datetime NOT NULL,
  `last_notify` datetime DEFAULT NULL,
  `pic_url` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nam_ix` (`name`,`password`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `users`
--