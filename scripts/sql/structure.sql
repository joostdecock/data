SET NAMES 'UTF8';
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table 'comments'
--

DROP TABLE IF EXISTS comments;
CREATE TABLE IF NOT EXISTS comments (
id smallint(6) NOT NULL,
  `user` int(5) NOT NULL,
  `comment` text NOT NULL,
  `page` tinytext NOT NULL,
  `time` datetime NOT NULL,
  `status` enum('active','removed','restricted') NOT NULL DEFAULT 'active',
  parent smallint(6) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table 'drafts'
--

DROP TABLE IF EXISTS drafts;
CREATE TABLE IF NOT EXISTS drafts (
id int(5) NOT NULL,
  `user` int(5) NOT NULL,
  pattern varchar(64) NOT NULL,
  model int(5) NOT NULL,
  `name` varchar(64) NOT NULL,
  handle varchar(5) NOT NULL,
  `data` text NOT NULL,
  svg mediumtext,
  compared mediumtext NOT NULL,
  created datetime NOT NULL,
  shared tinyint(1) NOT NULL DEFAULT '0',
  notes text
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table 'errors'
--

DROP TABLE IF EXISTS errors;
CREATE TABLE IF NOT EXISTS `errors` (
id int(5) NOT NULL,
  `type` enum('php-error','php-exception','js-error') NOT NULL,
  `level` int(4) NOT NULL,
  message varchar(510) NOT NULL,
  `file` varchar(255) NOT NULL,
  line int(4) NOT NULL,
  origin varchar(128) NOT NULL,
  `user` varchar(5) DEFAULT NULL,
  ip varchar(16) DEFAULT NULL,
  `time` datetime NOT NULL,
  `status` enum('new','open','muted','closed') NOT NULL DEFAULT 'new',
  `hash` varchar(40) NOT NULL,
  raw mediumtext
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table 'models'
--

DROP TABLE IF EXISTS models;
CREATE TABLE IF NOT EXISTS models (
id int(5) NOT NULL,
  `user` int(5) NOT NULL,
  `name` varchar(64) NOT NULL,
  handle varchar(5) NOT NULL,
  body enum('female','male','other') DEFAULT NULL COMMENT 'true is female, false is male',
  picture tinytext NOT NULL,
  `data` text NOT NULL,
  units enum('metric','imperial') NOT NULL DEFAULT 'metric',
  created datetime NOT NULL,
  migrated tinyint(1) NOT NULL DEFAULT '0',
  shared tinyint(1) NOT NULL DEFAULT '0',
  notes text
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table 'referrals'
--

DROP TABLE IF EXISTS referrals;
CREATE TABLE IF NOT EXISTS referrals (
id int(6) NOT NULL,
  `host` tinytext NOT NULL,
  path tinytext NOT NULL,
  url text NOT NULL,
  site tinytext,
  `time` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table 'users'
--

DROP TABLE IF EXISTS users;
CREATE TABLE IF NOT EXISTS users (
id int(5) NOT NULL COMMENT 'user id',
  email varchar(191) NOT NULL COMMENT 'email address',
  username varchar(32) NOT NULL,
  handle varchar(5) NOT NULL COMMENT 'a random string that uniquely identifies the user. Used in URLs instead of user id to prevent scraping',
  `status` enum('inactive','active','blocked','frozen') NOT NULL DEFAULT 'inactive' COMMENT 'user status',
  created datetime NOT NULL COMMENT 'user activation date/time',
  migrated datetime DEFAULT NULL,
  login datetime DEFAULT NULL COMMENT 'date/time of the user''s last login',
  role enum('user','moderator','admin') NOT NULL,
  patron int(1) NOT NULL DEFAULT '0',
  patron_since datetime DEFAULT NULL,
  picture varchar(12) NOT NULL,
  units enum('metric','imperial') NOT NULL DEFAULT 'metric',
  theme varchar(32) NOT NULL,
  twitter varchar(255) DEFAULT NULL,
  instagram varchar(255) DEFAULT NULL,
  github varchar(255) DEFAULT NULL,
  `data` text COMMENT 'user data for API in JSON',
  ehash varchar(40) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  initial varchar(191) NOT NULL COMMENT 'Email address the user signed up with',
  pepper varchar(64) NOT NULL COMMENT 'Random string used for reset tokens and such'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='Holds user data';

--
-- Indexes for dumped tables
--

--
-- Indexes for table comments
--
ALTER TABLE comments
 ADD PRIMARY KEY (id);

--
-- Indexes for table drafts
--
ALTER TABLE drafts
 ADD PRIMARY KEY (id), ADD KEY `user` (`user`), ADD KEY model (model), ADD KEY handle (handle);

--
-- Indexes for table errors
--
ALTER TABLE errors
 ADD PRIMARY KEY (id), ADD KEY `hash` (`hash`), ADD KEY `status` (`status`), ADD KEY origin (origin);

--
-- Indexes for table models
--
ALTER TABLE models
 ADD PRIMARY KEY (id), ADD KEY `user` (`user`);

--
-- Indexes for table referrals
--
ALTER TABLE referrals
 ADD PRIMARY KEY (id);

--
-- Indexes for table users
--
ALTER TABLE users
 ADD PRIMARY KEY (id), ADD UNIQUE KEY username (handle), ADD UNIQUE KEY email (email), ADD UNIQUE KEY `user` (username);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table comments
--
ALTER TABLE comments
MODIFY id smallint(6) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table drafts
--
ALTER TABLE drafts
MODIFY id int(5) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table errors
--
ALTER TABLE errors
MODIFY id int(5) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table models
--
ALTER TABLE models
MODIFY id int(5) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table referrals
--
ALTER TABLE referrals
MODIFY id int(6) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table users
--
ALTER TABLE users
MODIFY id int(5) NOT NULL AUTO_INCREMENT COMMENT 'user id',AUTO_INCREMENT=1;