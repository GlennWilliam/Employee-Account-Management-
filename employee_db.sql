-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 26, 2023 at 06:35 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `employee_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_permissions`
--

CREATE TABLE `access_permissions` (
  `access_id` int(11) NOT NULL,
  `bank_name` varchar(50) DEFAULT NULL,
  `canPost` tinyint(1) DEFAULT NULL,
  `canUpdate` tinyint(1) DEFAULT NULL,
  `canDelete` tinyint(1) DEFAULT NULL,
  `canView` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `access_permissions`
--

INSERT INTO `access_permissions` (`access_id`, `bank_name`, `canPost`, `canUpdate`, `canDelete`, `canView`) VALUES
(1, 'Bank A', 1, 1, 1, 1),
(2, 'Bank B', 1, 1, 1, 1),
(3, 'Bank C', 1, 1, 1, 1),
(4, 'Bank A', 1, 1, 1, 1),
(5, 'Bank B', 1, 1, 1, 1),
(6, 'Bank C', 1, 1, 1, 1),
(7, 'Bank A', 1, 1, 1, 1),
(8, 'Bank B', 1, 1, 1, 1),
(9, 'Bank C', 1, 1, 1, 1),
(10, 'Bank A', 1, 1, 1, 1),
(11, 'Bank B', 1, 1, 1, 1),
(12, 'Bank C', 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `banka`
--

CREATE TABLE `banka` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT NULL,
  `Nominal` int(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `banka`
--

INSERT INTO `banka` (`product_id`, `product_name`, `isActive`, `Nominal`) VALUES
(2, 'Test1', 0, 100);

-- --------------------------------------------------------

--
-- Table structure for table `bankb`
--

CREATE TABLE `bankb` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT NULL,
  `Nominal` int(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bankb`
--

INSERT INTO `bankb` (`product_id`, `product_name`, `isActive`, `Nominal`) VALUES
(1, 'Test1', 0, 100);

-- --------------------------------------------------------

--
-- Table structure for table `bankc`
--

CREATE TABLE `bankc` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT NULL,
  `Nominal` int(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bankc`
--

INSERT INTO `bankc` (`product_id`, `product_name`, `isActive`, `Nominal`) VALUES
(1, 'Test1', 0, 100);

-- --------------------------------------------------------

--
-- Table structure for table `employee_access_permissions`
--

CREATE TABLE `employee_access_permissions` (
  `employee_access_id` int(11) NOT NULL,
  `permission_name` varchar(50) NOT NULL,
  `canPost` tinyint(1) NOT NULL,
  `canView` tinyint(1) NOT NULL,
  `canUpdate` tinyint(1) NOT NULL,
  `canDelete` tinyint(1) NOT NULL,
  `canDetails` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_access_permissions`
--

INSERT INTO `employee_access_permissions` (`employee_access_id`, `permission_name`, `canPost`, `canView`, `canUpdate`, `canDelete`, `canDetails`) VALUES
(1, 'Employee Information', 1, 1, 1, 1, 1),
(2, 'Role Information', 0, 1, 1, 1, 1),
(3, 'Employee Information', 1, 1, 1, 1, 1),
(4, 'Role Information', 1, 1, 1, 1, 1),
(5, 'Employee Information', 1, 1, 1, 1, 1),
(6, 'Role Information', 1, 1, 1, 1, 0),
(7, 'Employee Information', 1, 1, 1, 1, 1),
(8, 'Role Information', 1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `employee_permission`
--

CREATE TABLE `employee_permission` (
  `employee_permission_id` int(11) NOT NULL,
  `permission_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_role_access_mapping`
--

CREATE TABLE `employee_role_access_mapping` (
  `employee_access_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_role_access_mapping`
--

INSERT INTO `employee_role_access_mapping` (`employee_access_id`, `role_id`) VALUES
(1, 0),
(2, 0),
(7, 47),
(7, 0),
(8, 47),
(8, 0);

-- --------------------------------------------------------

--
-- Table structure for table `employee_role_permission_mapping`
--

CREATE TABLE `employee_role_permission_mapping` (
  `role_id` int(11) DEFAULT NULL,
  `employee_permission_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_user_role_mapping`
--

CREATE TABLE `employee_user_role_mapping` (
  `employee_user_role_mapping_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_user_role_mapping`
--

INSERT INTO `employee_user_role_mapping` (`employee_user_role_mapping_id`, `user_id`, `role_id`) VALUES
(1, 2, 47);

-- --------------------------------------------------------

--
-- Table structure for table `permission`
--

CREATE TABLE `permission` (
  `permission_id` int(11) NOT NULL,
  `permission_name` varchar(50) NOT NULL,
  `canPost` tinyint(1) DEFAULT NULL,
  `canDelete` tinyint(1) DEFAULT NULL,
  `canUpdate` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_access_mapping`
--

CREATE TABLE `role_access_mapping` (
  `role_id` int(11) DEFAULT NULL,
  `access_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_access_mapping`
--

INSERT INTO `role_access_mapping` (`role_id`, `access_id`) VALUES
(47, 10),
(47, 11),
(47, 12);

-- --------------------------------------------------------

--
-- Table structure for table `role_permission_mapping`
--

CREATE TABLE `role_permission_mapping` (
  `role_id` int(11) DEFAULT NULL,
  `permission_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_information`
--

CREATE TABLE `user_information` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `user_type` varchar(255) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_information`
--

INSERT INTO `user_information` (`user_id`, `first_name`, `last_name`, `email`, `password`, `user_type`) VALUES
(2, 'admin', 'admin', 'admin@gmail.com', '21232f297a57a5a743894a0e4a801fc3', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `user_role`
--

CREATE TABLE `user_role` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_role`
--

INSERT INTO `user_role` (`role_id`, `role_name`) VALUES
(47, 'SuperAdmin');

-- --------------------------------------------------------

--
-- Table structure for table `user_role_mapping`
--

CREATE TABLE `user_role_mapping` (
  `user_role_mapping_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_role_mapping`
--

INSERT INTO `user_role_mapping` (`user_role_mapping_id`, `user_id`, `role_id`) VALUES
(2, 2, 47);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_permissions`
--
ALTER TABLE `access_permissions`
  ADD PRIMARY KEY (`access_id`);

--
-- Indexes for table `banka`
--
ALTER TABLE `banka`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `bankb`
--
ALTER TABLE `bankb`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `bankc`
--
ALTER TABLE `bankc`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `employee_access_permissions`
--
ALTER TABLE `employee_access_permissions`
  ADD PRIMARY KEY (`employee_access_id`);

--
-- Indexes for table `employee_permission`
--
ALTER TABLE `employee_permission`
  ADD PRIMARY KEY (`employee_permission_id`);

--
-- Indexes for table `employee_role_access_mapping`
--
ALTER TABLE `employee_role_access_mapping`
  ADD KEY `employee_access_id` (`employee_access_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `employee_role_permission_mapping`
--
ALTER TABLE `employee_role_permission_mapping`
  ADD KEY `role_id` (`role_id`),
  ADD KEY `employee_permission_id` (`employee_permission_id`);

--
-- Indexes for table `employee_user_role_mapping`
--
ALTER TABLE `employee_user_role_mapping`
  ADD PRIMARY KEY (`employee_user_role_mapping_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `permission`
--
ALTER TABLE `permission`
  ADD PRIMARY KEY (`permission_id`);

--
-- Indexes for table `role_access_mapping`
--
ALTER TABLE `role_access_mapping`
  ADD KEY `role_id` (`role_id`),
  ADD KEY `access_id` (`access_id`);

--
-- Indexes for table `role_permission_mapping`
--
ALTER TABLE `role_permission_mapping`
  ADD KEY `role_id` (`role_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `user_information`
--
ALTER TABLE `user_information`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_role`
--
ALTER TABLE `user_role`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `user_role_mapping`
--
ALTER TABLE `user_role_mapping`
  ADD PRIMARY KEY (`user_role_mapping_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_permissions`
--
ALTER TABLE `access_permissions`
  MODIFY `access_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `banka`
--
ALTER TABLE `banka`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bankb`
--
ALTER TABLE `bankb`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bankc`
--
ALTER TABLE `bankc`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employee_access_permissions`
--
ALTER TABLE `employee_access_permissions`
  MODIFY `employee_access_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employee_permission`
--
ALTER TABLE `employee_permission`
  MODIFY `employee_permission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_user_role_mapping`
--
ALTER TABLE `employee_user_role_mapping`
  MODIFY `employee_user_role_mapping_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permission`
--
ALTER TABLE `permission`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_information`
--
ALTER TABLE `user_information`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_role`
--
ALTER TABLE `user_role`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `user_role_mapping`
--
ALTER TABLE `user_role_mapping`
  MODIFY `user_role_mapping_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
