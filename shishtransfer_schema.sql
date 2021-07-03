-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 03, 2021 at 07:22 AM
-- Server version: 10.3.27-MariaDB-0+deb10u1
-- PHP Version: 7.4.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shsh`
--

-- --------------------------------------------------------

--
-- Table structure for table `dlinkshead`
--

CREATE TABLE `dlinkshead` (
  `contentlength` varchar(50) NOT NULL,
  `flink` varchar(10) NOT NULL,
  `contenttype` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `partsize` varchar(50) NOT NULL,
  `hash` varchar(33) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `dllinks`
--

CREATE TABLE `dllinks` (
  `uniq` bigint(11) NOT NULL,
  `flink` varchar(12) NOT NULL,
  `username` varchar(35) NOT NULL,
  `msgid` varchar(32) NOT NULL,
  `backup_hash` varchar(32) NOT NULL,
  `mirror_id` varchar(32) NOT NULL,
  `mirror_username` varchar(35) NOT NULL,
  `backup_id` varchar(32) NOT NULL,
  `myid` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dlinkshead`
--
ALTER TABLE `dlinkshead`
  ADD PRIMARY KEY (`flink`),
  ADD KEY `flink` (`flink`);

--
-- Indexes for table `dllinks`
--
ALTER TABLE `dllinks`
  ADD PRIMARY KEY (`uniq`),
  ADD KEY `flink` (`flink`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dllinks`
--
ALTER TABLE `dllinks`
  MODIFY `uniq` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=221;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
