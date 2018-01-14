SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `comments` (
  `id` smallint(6) NOT NULL,
  `user` int(5) NOT NULL,
  `comment` text NOT NULL,
  `page` tinytext NOT NULL,
  `time` datetime NOT NULL,
  `status` enum('active','removed','restricted') NOT NULL DEFAULT 'active',
  `parent` smallint(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `drafts` (
  `id` int(5) NOT NULL,
  `user` int(5) NOT NULL,
  `pattern` varchar(64) NOT NULL,
  `model` int(5) NOT NULL,
  `name` varchar(64) NOT NULL,
  `handle` varchar(5) NOT NULL,
  `data` text NOT NULL,
  `svg` mediumtext,
  `compared` mediumtext NOT NULL,
  `created` datetime NOT NULL,
  `shared` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `models` (
  `id` int(5) NOT NULL,
  `user` int(5) NOT NULL,
  `name` varchar(64) NOT NULL,
  `handle` varchar(5) NOT NULL,
  `body` enum('female','male','other') DEFAULT NULL COMMENT 'true is female, false is male',
  `picture` tinytext NOT NULL,
  `data` text NOT NULL,
  `units` enum('metric','imperial') NOT NULL DEFAULT 'metric',
  `created` datetime NOT NULL,
  `shared` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `referrals` (
  `id` int(6) NOT NULL,
  `host` tinytext NOT NULL,
  `path` tinytext NOT NULL,
  `url` text NOT NULL,
  `site` tinytext,
  `time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `users` (
  `id` int(5) NOT NULL COMMENT 'user id',
  `email` varchar(191) NOT NULL COMMENT 'email address',
  `username` varchar(32) NOT NULL,
  `handle` varchar(5) NOT NULL COMMENT 'a random string that uniquely identifies the user. Used in URLs instead of user id to prevent scraping',
  `status` enum('inactive','active','blocked') NOT NULL DEFAULT 'inactive' COMMENT 'user status',
  `created` datetime NOT NULL COMMENT 'user activation date/time',
  `login` datetime DEFAULT NULL COMMENT 'date/time of the user''s last login',
  `role` enum('user','moderator','admin') NOT NULL,
  `picture` varchar(12) NOT NULL,
  `data` text COMMENT 'user data for API in JSON',
  `password` varchar(255) NOT NULL,
  `initial` varchar(191) NOT NULL COMMENT 'Email address the user signed up with',
  `pepper` varchar(64) NOT NULL COMMENT 'Random string used for reset tokens and such'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Holds user data';

ALTER TABLE `comments` ADD PRIMARY KEY (`id`);

ALTER TABLE `drafts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user`),
  ADD KEY `model` (`model`),
  ADD KEY `handle` (`handle`);

ALTER TABLE `models`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user`);

ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`handle`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `user` (`username`);

ALTER TABLE `comments`
  MODIFY `id` smallint(6) NOT NULL AUTO_INCREMENT;

ALTER TABLE `drafts` MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;
ALTER TABLE `models` MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;
ALTER TABLE `referrals` MODIFY `id` int(6) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `id` int(5) NOT NULL AUTO_INCREMENT COMMENT 'user id';
