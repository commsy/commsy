ALTER TABLE `cards`
CHANGE `user` `user_id` BIGINT( 11 ) UNSIGNED NOT NULL ,
CHANGE `index` `index_num` BIGINT( 11 ) UNSIGNED NOT NULL ,
CHANGE `level` `level_num` BIGINT( 11 ) UNSIGNED NOT NULL ;

ALTER TABLE `flashcards`
CHANGE `user` `user_id` BIGINT( 11 ) UNSIGNED NOT NULL ,
CHANGE `number` `num` BIGINT( 11 ) UNSIGNED NOT NULL ,
CHANGE `level` `level_num` BIGINT( 11 ) UNSIGNED NOT NULL ;

ALTER TABLE `groups`
CHANGE `access` `access_num` BIGINT( 11 ) UNSIGNED NOT NULL  ;

ALTER TABLE `markers`
CHANGE `user` `user_id` BIGINT( 11 ) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `range` `range_store` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;

ALTER TABLE `memberships`
CHANGE `user` `user_id` BIGINT( 11 ) UNSIGNED NOT NULL ,
CHANGE `group` `group_id` BIGINT( 11 ) UNSIGNED NOT NULL ,
CHANGE `level` `level_num` BIGINT( 11 ) UNSIGNED NOT NULL DEFAULT '0' ;

ALTER TABLE `rights`
CHANGE `group` `group_id` BIGINT( 11 ) UNSIGNED NOT NULL  ;

ALTER TABLE `topics`
CHANGE `user` `user_id` BIGINT( 11 ) UNSIGNED NOT NULL  ;

CREATE TABLE IF NOT EXISTS `uploads` (
  `id` bigint(10) unsigned NOT NULL auto_increment,
  `type` smallint(4) unsigned NOT NULL default '0',
  `user_id` bigint(10) unsigned NOT NULL,
  `topic_id` bigint(10) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL default '',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
);