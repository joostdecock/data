SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `freesewing_data`
--

-- --------------------------------------------------------

--
-- Table structure for table `mmpmodels`
--

CREATE TABLE IF NOT EXISTS `mmpmodels` (
  `modelid` int(5) NOT NULL,
  `uid` int(5) NOT NULL COMMENT 'The MMP user id',
  `title` tinytext NOT NULL,
  `sex` tinyint(1) NOT NULL COMMENT 'false is male, true is female',
  `picture` text NOT NULL,
  `data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mmpusers`
--

CREATE TABLE IF NOT EXISTS `mmpusers` (
  `uid` int(5) NOT NULL,
  `email` varchar(192) NOT NULL,
  `username` varchar(60) NOT NULL,
  `picture` text NOT NULL,
  `initial` varchar(254) NOT NULL,
  `created` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `models`
--

CREATE TABLE IF NOT EXISTS `models` (
`id` int(5) NOT NULL,
  `user` int(5) NOT NULL,
  `name` varchar(64) NOT NULL,
  `handle` varchar(5) NOT NULL,
  `body` enum('female','male','other') DEFAULT NULL COMMENT 'true is female, false is male',
  `picture` tinytext NOT NULL,
  `data` text NOT NULL,
  `units` enum('metric','imperial') NOT NULL DEFAULT 'metric',
  `created` datetime NOT NULL,
  `migrated` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
`id` int(5) NOT NULL COMMENT 'user id',
  `email` varchar(191) NOT NULL COMMENT 'email address',
  `username` varchar(32) NOT NULL,
  `handle` varchar(5) NOT NULL COMMENT 'a random string that uniquely identifies the user. Used in URLs instead of user id to prevent scraping',
  `status` enum('inactive','active','blocked') NOT NULL DEFAULT 'inactive' COMMENT 'user status',
  `created` datetime NOT NULL COMMENT 'user activation date/time',
  `migrated` datetime DEFAULT NULL,
  `login` datetime DEFAULT NULL COMMENT 'date/time of the user''s last login',
  `role` enum('user','moderator','admin') NOT NULL,
  `picture` varchar(12) NOT NULL,
  `data` text COMMENT 'user data for API in JSON',
  `password` varchar(255) NOT NULL,
  `initial` varchar(191) NOT NULL COMMENT 'Email address the user signed up with',
  `pepper` varchar(64) NOT NULL COMMENT 'Random string used for reset tokens and such'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Holds user data';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mmpmodels`
--
ALTER TABLE `mmpmodels`
 ADD PRIMARY KEY (`modelid`);

--
-- Indexes for table `mmpusers`
--
ALTER TABLE `mmpusers`
 ADD PRIMARY KEY (`uid`), ADD KEY `mail` (`email`);

--
-- Indexes for table `models`
--
ALTER TABLE `models`
 ADD PRIMARY KEY (`id`), ADD KEY `user` (`user`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `username` (`handle`), ADD UNIQUE KEY `email` (`email`), ADD UNIQUE KEY `user` (`username`), ADD KEY `username_2` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `models`
--
ALTER TABLE `models`
MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
MODIFY `id` int(5) NOT NULL AUTO_INCREMENT COMMENT 'user id';
