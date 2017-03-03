-- phpMyAdmin SQL Dump
-- version 4.2.13.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 18, 2015 at 05:03 PM
-- Server version: 5.6.22-log
-- PHP Version: 5.5.21-pl0-gentoo

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `games`
--
CREATE DATABASE IF NOT EXISTS `games` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `games`;

-- --------------------------------------------------------

--
-- Table structure for table `game_kacky`
--

CREATE TABLE IF NOT EXISTS `game_kacky` (
`g_id` int(11) NOT NULL,
  `g_data` text NOT NULL,
  `g_active` int(11) NOT NULL,
  `g_title` text NOT NULL,
  `g_ts` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `game_kacky`
--

INSERT INTO `game_kacky` (`g_id`, `g_data`, `g_active`, `g_title`, `g_ts`) VALUES
(1, '', 0, 'Kacicky', '2015-03-29 15:00:00'),
(3, '', 1, 'Ine kacky', '2015-03-29 14:52:00'),
(5, '', 0, 'neaktivne kacky', '2015-03-29 15:07:39'),
(7, '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', 1, 'aktivne kacky', '2015-03-29 15:07:39');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
`u_id` int(11) NOT NULL,
  `u_name` text NOT NULL,
  `u_pass` text NOT NULL COMMENT 'SHA1(name:game:pass)'
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`u_id`, `u_name`, `u_pass`) VALUES
(1, 'comp', 'c26e3af81f8d7beb096f7ebe2285fb233b58c9ee'),
(3, 'tamara', '8d60cfa4b87d57dfbd2cfd57a21d953ce8ac6fcc');

-- --------------------------------------------------------

--
-- Table structure for table `user2game`
--

CREATE TABLE IF NOT EXISTS `user2game` (
  `u_id` int(11) NOT NULL,
  `g_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user2game`
--

INSERT INTO `user2game` (`u_id`, `g_id`) VALUES
(3, 1),
(1, 7),
(3, 7);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `game_kacky`
--
ALTER TABLE `game_kacky`
 ADD PRIMARY KEY (`g_id`), ADD KEY `g_active` (`g_active`), ADD KEY `g_ts` (`g_ts`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
 ADD PRIMARY KEY (`u_id`);

--
-- Indexes for table `user2game`
--
ALTER TABLE `user2game`
 ADD PRIMARY KEY (`u_id`,`g_id`), ADD KEY `g_id` (`g_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `game_kacky`
--
ALTER TABLE `game_kacky`
MODIFY `g_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
MODIFY `u_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `user2game`
--
ALTER TABLE `user2game`
ADD CONSTRAINT `user2game_ibfk_1` FOREIGN KEY (`g_id`) REFERENCES `game_kacky` (`g_id`) ON DELETE CASCADE,
ADD CONSTRAINT `user2game_ibfk_2` FOREIGN KEY (`u_id`) REFERENCES `user` (`u_id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
