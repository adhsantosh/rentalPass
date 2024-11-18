-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2024 at 02:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rental_pass`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `rentals`
--

CREATE TABLE `rentals` (
  `rental_id` int(11) NOT NULL,
  `UID` int(11) DEFAULT NULL,
  `VID` int(11) DEFAULT NULL,
  `rental_start` datetime DEFAULT NULL,
  `rental_end` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rentals`
--

INSERT INTO `rentals` (`rental_id`, `UID`, `VID`, `rental_start`, `rental_end`, `status`) VALUES
(3, 1, 1, '2024-01-01 00:00:00', '2024-01-02 00:00:00', 'active'),
(4, 1, 4, '2024-01-05 00:00:00', '2024-01-06 00:00:00', 'canceled'),
(5, 2, 1, '2024-01-01 00:00:00', '2024-01-02 00:00:00', 'canceled'),
(6, 3, 5, '2024-01-03 00:00:00', '2024-01-04 00:00:00', 'canceled'),
(7, 1, 1, '2024-11-04 07:34:00', '2024-11-29 07:34:00', 'canceled'),
(8, 4, 4, '2024-11-13 09:56:00', '2024-11-14 09:56:00', 'canceled'),
(9, 6, 5, '2024-11-13 19:51:00', '2024-11-15 19:51:00', 'canceled'),
(10, 6, 5, '2024-11-13 20:49:00', '2024-11-14 20:49:00', 'canceled'),
(11, 6, 6, '2024-11-14 20:52:00', '2024-11-15 20:52:00', 'canceled'),
(12, 3, 4, '2024-11-13 11:33:00', '2024-11-15 11:33:00', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UID` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UID`, `name`, `phone`, `address`, `password`) VALUES
(1, 'mbmc', '9111111111', 'anamnagar', '$2y$10$u9mh890xaQl2tYiQ.wTrcOEQg.OodsKrffTFx1jdTe.IKcTVFN90e'),
(2, 'user', '9222222222', 'address', '$2y$10$4iLCZIX5LhFrdkA1FQKybuC.g2slApFIgRhq0YIzzJx9txTZETv.K'),
(3, 'user1', '9333333333', 'address', '$2y$10$V719K65dtCCLoG0CnD8CAuwIZMd2Yas1siaUnRO0sToanOe04X64a'),
(4, 'test', '9999999999', 'test', '$2y$10$TCfG.sjk7Bn7mnc29g6mWO.mGleDB4BRgtVY7G4FLceuDLXkTujgy'),
(5, 'siba', '9999999900', 'bhaktapur', '$2y$10$uh3CRXRgsGvNoZbLMSjAJu9CWqyAlB5h0UPcwLEfov0TAGUEhR.1y'),
(6, 'Shiva', '9800000001', 'Bhaktapurr', '$2y$10$90ta4giD6psyF3FnwySBR.cFFc6/D6mzjkiY4B5zW6DPisG.DlMGK');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `VID` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `price` varchar(255) DEFAULT NULL,
  `available` tinyint(1) DEFAULT 1,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`VID`, `name`, `model`, `price`, `available`, `photo`) VALUES
(1, 'Giant Bicycle', 'Cannondale', '12000', 1, 'uploads/kona-process-134-29-397019-12.png'),
(4, 'NGS', 'Norco', '17000', 0, 'uploads/hardtail-mtb.png'),
(5, 'Strout', 'KONA', '14000', 1, 'uploads/dr-1681827677.jpg'),
(6, 'Marin Bicycle', 'Marin', '8500', 1, 'uploads/marinbike.jpg'),
(7, 'Bianchi', 'Bianchi', '8700', 1, 'uploads/bianchi.jpeg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`rental_id`),
  ADD KEY `UID` (`UID`),
  ADD KEY `VID` (`VID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UID`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`VID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rentals`
--
ALTER TABLE `rentals`
  MODIFY `rental_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `VID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rentals`
--
ALTER TABLE `rentals`
  ADD CONSTRAINT `rentals_ibfk_1` FOREIGN KEY (`UID`) REFERENCES `users` (`UID`),
  ADD CONSTRAINT `rentals_ibfk_2` FOREIGN KEY (`VID`) REFERENCES `vehicles` (`VID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
