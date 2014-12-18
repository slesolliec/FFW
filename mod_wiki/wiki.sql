
CREATE TABLE `pages` (
  `wiki_id` smallint(6) NOT NULL,
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(250) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `tags` varchar(250) NOT NULL DEFAULT '',
  `user_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `editor_ip` varchar(15) NOT NULL DEFAULT '',
  `editor_hostname` varchar(100) NOT NULL DEFAULT '',
  `edit_desc` varchar(200) NOT NULL DEFAULT '',
  `menu_title` varchar(250) NOT NULL DEFAULT '',
  `menu_rank` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `dir` varchar(250) NOT NULL DEFAULT '',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `nb_comments` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `page_date` date DEFAULT NULL,
  `page_time` time DEFAULT NULL,
  `category` varchar(99) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nameindex` (`name`)
);

CREATE TABLE `pages_archives` (
  `wiki_id` smallint(6) NOT NULL,
  `id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(250) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `tags` varchar(250) NOT NULL DEFAULT '',
  `user_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `editor_ip` varchar(15) NOT NULL DEFAULT '',
  `editor_hostname` varchar(100) NOT NULL DEFAULT '',
  `edit_desc` varchar(200) NOT NULL DEFAULT '',
  `menu_title` varchar(250) NOT NULL DEFAULT '',
  `menu_rank` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `dir` varchar(250) NOT NULL DEFAULT '',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `nb_comments` mediumint(8) unsigned DEFAULT NULL,
  `page_date` date DEFAULT NULL,
  `page_time` time DEFAULT NULL,
  `category` varchar(99) DEFAULT NULL,
  KEY `pageindex` (`id`)
);

CREATE TABLE `tag_index` (
  `tag_type` varchar(30) NOT NULL DEFAULT '',
  `tag` varchar(100) NOT NULL DEFAULT '',
  `page_id` smallint(5) unsigned NOT NULL DEFAULT '0'
);

CREATE TABLE `wiki_subscribers` (
  `wiki_id` mediumint(8) unsigned DEFAULT NULL,
  `user_id` mediumint(8) unsigned DEFAULT NULL
);

