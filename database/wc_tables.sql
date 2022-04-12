-- phpMyAdmin SQL Dump
-- version 4.1.14.8
-- http://www.phpmyadmin.net
--
-- Host: infongd-eu7183
-- Generation Time: Aug 30, 2018 at 02:34 PM
-- Server version: 5.5.60-0+deb7u1
-- PHP Version: 5.4.45-0+deb7u14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `db712617283`
--

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
CREATE TABLE IF NOT EXISTS `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `authorId` int(11) NOT NULL,
  `title_en` text CHARACTER SET utf8 NOT NULL,
  `body_en` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=10 ;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int(11) NOT NULL,
  `our_commission` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
CREATE TABLE IF NOT EXISTS `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `event_id` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paid_fee` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=5497 ;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
CREATE TABLE IF NOT EXISTS `staff` (
  `user_id` int(11) NOT NULL,
  `introduction` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `score_taking` tinyint(1) NOT NULL DEFAULT '0',
  `check_in` tinyint(4) NOT NULL DEFAULT '0',
  `wca_booth` tinyint(1) NOT NULL DEFAULT '0',
  `day_18` tinyint(1) NOT NULL DEFAULT '0',
  `day_19` tinyint(1) NOT NULL DEFAULT '0',
  `day_20` tinyint(1) NOT NULL DEFAULT '0',
  `day_21` tinyint(1) NOT NULL DEFAULT '0',
  `day_22` tinyint(1) NOT NULL DEFAULT '0',
  `t_shirt_size` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_events`
--

DROP TABLE IF EXISTS `staff_events`;
CREATE TABLE IF NOT EXISTS `staff_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=5927 ;

-- --------------------------------------------------------

--
-- Table structure for table `staff_votes`
--

DROP TABLE IF EXISTS `staff_votes`;
CREATE TABLE IF NOT EXISTS `staff_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voter_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `candidate_id` (`candidate_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=716 ;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `d19` tinyint(4) NOT NULL DEFAULT '0',
  `d20` tinyint(4) NOT NULL DEFAULT '0',
  `d21` tinyint(4) NOT NULL DEFAULT '0',
  `d22` tinyint(4) NOT NULL DEFAULT '0',
  `all_days` tinyint(4) NOT NULL DEFAULT '0',
  `amount` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wca_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dob` date NOT NULL,
  `gender` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_iso2` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delegate_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_thumb_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `yt_video_id` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` int(11) NOT NULL DEFAULT '0',
  `orga_team` tinyint(4) NOT NULL DEFAULT '0',
  `amount_paid` int(11) NOT NULL DEFAULT '0',
  `amount_used` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `wca_id` (`wca_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
