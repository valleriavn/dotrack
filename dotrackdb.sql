-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2025 at 10:58 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dotrackdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `collaborate_group`
--

CREATE TABLE `collaborate_group` (
  `colgroup_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `colgroup_name` varchar(255) DEFAULT NULL,
  `group_code` char(36) NOT NULL DEFAULT uuid(),
  `teammem_id` int(11) NOT NULL,
  `colproj_id` int(11) DEFAULT NULL,
  `coltask_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collaborate_group`
--

INSERT INTO `collaborate_group` (`colgroup_id`, `user_id`, `colgroup_name`, `group_code`, `teammem_id`, `colproj_id`, `coltask_id`, `created_at`, `deleted_at`) VALUES
(17, 9, 'nei', '84YQPED9', 0, NULL, NULL, '2025-06-24 11:43:27', NULL),
(18, 9, 'nei', 'F2YCU71Z', 0, NULL, NULL, '2025-06-24 11:44:08', NULL),
(19, 10, 'IPT', 'EMZULK9L', 0, NULL, NULL, '2025-06-25 16:47:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `collaborate_project`
--

CREATE TABLE `collaborate_project` (
  `colproj_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `colproj_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('not started','in progress','on hold','cancelled','done') NOT NULL DEFAULT 'not started',
  `colgroup_id` int(11) DEFAULT NULL,
  `coltask_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collaborate_project`
--

INSERT INTO `collaborate_project` (`colproj_id`, `user_id`, `colproj_name`, `description`, `status`, `colgroup_id`, `coltask_id`, `start_date`, `due_date`, `created_at`, `deleted_at`) VALUES
(10, 10, 'Information Management', 'Final Presentation', 'in progress', 18, NULL, '2025-06-24', '2025-06-30', '2025-06-24 12:12:37', NULL),
(11, 10, 'IPT Project', 'Presentation', 'cancelled', 18, NULL, '2025-06-27', '2025-06-28', '2025-06-25 16:48:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `collaborate_task`
--

CREATE TABLE `collaborate_task` (
  `coltask_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `coltask_name` varchar(255) NOT NULL,
  `colsubtask_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('not started','in progress','on hold','cancelled','done') NOT NULL DEFAULT 'not started',
  `colproj_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `assigned_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collaborate_task`
--

INSERT INTO `collaborate_task` (`coltask_id`, `user_id`, `coltask_name`, `colsubtask_name`, `description`, `status`, `colproj_id`, `start_date`, `due_date`, `created_at`, `deleted_at`, `assigned_user_id`) VALUES
(11, 10, 'DoTrack', '', NULL, 'in progress', 10, '2025-06-24', '2025-06-30', '2025-06-24 12:12:37', NULL, 10),
(12, 10, 'Presentation', '', NULL, 'on hold', 11, '2025-06-25', '2025-06-27', '2025-06-25 16:48:30', NULL, 9);

-- --------------------------------------------------------

--
-- Table structure for table `collaborate_teammem`
--

CREATE TABLE `collaborate_teammem` (
  `teammem_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `colproj_id` int(11) DEFAULT NULL,
  `coltask_id` int(11) DEFAULT NULL,
  `colgroup_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collaborate_teammem`
--

INSERT INTO `collaborate_teammem` (`teammem_id`, `user_id`, `colproj_id`, `coltask_id`, `colgroup_id`, `created_at`, `deleted_at`) VALUES
(31, 10, NULL, NULL, 18, '2025-06-24 11:50:51', NULL),
(32, 10, NULL, NULL, 18, '2025-06-24 11:54:54', NULL),
(33, 10, NULL, NULL, 18, '2025-06-24 11:55:00', NULL),
(34, 10, 10, NULL, 18, '2025-06-24 12:12:37', NULL),
(35, 10, NULL, NULL, 18, '2025-06-24 13:59:56', NULL),
(36, 10, NULL, NULL, 18, '2025-06-24 14:00:30', NULL),
(37, 10, 11, NULL, 18, '2025-06-25 16:48:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `personal_habit`
--

CREATE TABLE `personal_habit` (
  `habit_id` int(11) NOT NULL,
  `habit_name` varchar(255) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal_habit`
--

INSERT INTO `personal_habit` (`habit_id`, `habit_name`, `day`, `user_id`, `created_at`, `deleted_at`) VALUES
(15, 'Presentation', 'Tuesday', 10, '2025-06-24 12:11:31', NULL),
(16, 'Presentation 1', 'Friday', 10, '2025-06-25 16:45:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `personal_project`
--

CREATE TABLE `personal_project` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `persubtask_name` text DEFAULT NULL,
  `priority` enum('not assigned','low','medium','high') NOT NULL DEFAULT 'not assigned',
  `status` enum('pending','not started','in progress','on hold','cancelled','submitted') NOT NULL DEFAULT 'pending',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal_project`
--

INSERT INTO `personal_project` (`project_id`, `user_id`, `project_name`, `description`, `persubtask_name`, `priority`, `status`, `start_date`, `due_date`, `created_at`, `deleted_at`) VALUES
(6, 10, 'Information Management', 'Finals', 'Database', 'high', 'in progress', '2025-06-26', '2025-06-30', '2025-06-24 12:11:18', NULL),
(7, 10, 'Group Presentation', 'Finals', '123', 'high', 'in progress', '2025-07-01', '2025-07-04', '2025-06-25 16:45:06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `personal_todo`
--

CREATE TABLE `personal_todo` (
  `todo_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `todo_name` varchar(255) NOT NULL,
  `status` enum('pending','not started','in progress','on hold','cancelled','done','submitted') NOT NULL DEFAULT 'not started',
  `due_date` date DEFAULT NULL,
  `priority` enum('not assigned','low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `category` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal_todo`
--

INSERT INTO `personal_todo` (`todo_id`, `user_id`, `todo_name`, `status`, `due_date`, `priority`, `category`, `created_at`, `deleted_at`) VALUES
(7, 10, 'Information Management', 'not started', '2025-06-25', 'urgent', 'COMP 010', '2025-06-24 12:10:20', NULL),
(8, 10, 'Final Presentation', 'in progress', '2025-06-30', 'high', 'IPT', '2025-06-25 16:43:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_active` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role`, `status`, `last_active`, `created_at`, `deleted_at`) VALUES
(3, 'Admin', 'admin@gmail.com', '$2a$12$LoFCgM1Bdl6lnrTM8O8k/uz9i7tylCXZZSOOrn9eXwcM6ukQHuUnu', 'admin', 'active', '2025-06-25 08:51:25', '2025-06-12 16:46:19', NULL),
(9, 'Kim', 'kim@gmail.com', '$2y$10$qqqIlMe5IOutgr7Wmftdhebr6gxDDwCxPxR1/jNh5irDM6OEhTRfO', 'user', 'active', '2025-06-24 03:46:47', '2025-06-24 03:38:04', NULL),
(10, 'nei', 'nei@gmail.com', '$2y$10$bXhDZbS6ANTBEJftkxOrvOsi.3AmHz.JdyFyTfBK6We7iq3bOEqY.', 'user', 'inactive', '2025-06-25 08:49:51', '2025-06-24 03:47:26', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `collaborate_group`
--
ALTER TABLE `collaborate_group`
  ADD PRIMARY KEY (`colgroup_id`),
  ADD UNIQUE KEY `group_code` (`group_code`) USING BTREE,
  ADD KEY `user_id` (`user_id`),
  ADD KEY `colproj_id` (`colproj_id`),
  ADD KEY `collaborate_group_ibfk_3` (`coltask_id`);

--
-- Indexes for table `collaborate_project`
--
ALTER TABLE `collaborate_project`
  ADD PRIMARY KEY (`colproj_id`),
  ADD KEY `colgroup_id` (`colgroup_id`),
  ADD KEY `collaborate_project_ibfk_1` (`user_id`),
  ADD KEY `collaborate_project_ibfk_3` (`coltask_id`);

--
-- Indexes for table `collaborate_task`
--
ALTER TABLE `collaborate_task`
  ADD PRIMARY KEY (`coltask_id`),
  ADD UNIQUE KEY `unique_task_subtask` (`coltask_id`,`colsubtask_name`),
  ADD KEY `colproj_id` (`colproj_id`),
  ADD KEY `collaborate_task_ibfk_1` (`user_id`),
  ADD KEY `fk_assigned_user` (`assigned_user_id`);

--
-- Indexes for table `collaborate_teammem`
--
ALTER TABLE `collaborate_teammem`
  ADD PRIMARY KEY (`teammem_id`),
  ADD KEY `colgroup_id` (`colgroup_id`),
  ADD KEY `collaborate_teammem_ibfk_3` (`colproj_id`),
  ADD KEY `collaborate_teammem_ibfk_1` (`user_id`),
  ADD KEY `fk_task_subtask` (`coltask_id`);

--
-- Indexes for table `personal_habit`
--
ALTER TABLE `personal_habit`
  ADD PRIMARY KEY (`habit_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `personal_project`
--
ALTER TABLE `personal_project`
  ADD PRIMARY KEY (`project_id`),
  ADD UNIQUE KEY `persubtask-id` (`project_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `personal_todo`
--
ALTER TABLE `personal_todo`
  ADD PRIMARY KEY (`todo_id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `collaborate_group`
--
ALTER TABLE `collaborate_group`
  MODIFY `colgroup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `collaborate_project`
--
ALTER TABLE `collaborate_project`
  MODIFY `colproj_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `collaborate_task`
--
ALTER TABLE `collaborate_task`
  MODIFY `coltask_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `collaborate_teammem`
--
ALTER TABLE `collaborate_teammem`
  MODIFY `teammem_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `personal_habit`
--
ALTER TABLE `personal_habit`
  MODIFY `habit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `personal_project`
--
ALTER TABLE `personal_project`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `personal_todo`
--
ALTER TABLE `personal_todo`
  MODIFY `todo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `collaborate_group`
--
ALTER TABLE `collaborate_group`
  ADD CONSTRAINT `collaborate_group_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `collaborate_group_ibfk_2` FOREIGN KEY (`colproj_id`) REFERENCES `collaborate_project` (`colproj_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `collaborate_group_ibfk_3` FOREIGN KEY (`coltask_id`) REFERENCES `collaborate_task` (`coltask_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `collaborate_project`
--
ALTER TABLE `collaborate_project`
  ADD CONSTRAINT `collaborate_project_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `collaborate_project_ibfk_2` FOREIGN KEY (`colgroup_id`) REFERENCES `collaborate_group` (`colgroup_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `collaborate_project_ibfk_3` FOREIGN KEY (`coltask_id`) REFERENCES `collaborate_task` (`coltask_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `collaborate_task`
--
ALTER TABLE `collaborate_task`
  ADD CONSTRAINT `collaborate_task_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `collaborate_task_ibfk_2` FOREIGN KEY (`colproj_id`) REFERENCES `collaborate_project` (`colproj_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assigned_user` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `collaborate_teammem`
--
ALTER TABLE `collaborate_teammem`
  ADD CONSTRAINT `collaborate_teammem_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `collaborate_teammem_ibfk_2` FOREIGN KEY (`colgroup_id`) REFERENCES `collaborate_group` (`colgroup_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `collaborate_teammem_ibfk_3` FOREIGN KEY (`colproj_id`) REFERENCES `collaborate_project` (`colproj_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `collaborate_teammem_ibfk_4` FOREIGN KEY (`coltask_id`) REFERENCES `collaborate_task` (`coltask_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `personal_habit`
--
ALTER TABLE `personal_habit`
  ADD CONSTRAINT `personal_habit_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `personal_project`
--
ALTER TABLE `personal_project`
  ADD CONSTRAINT `personal_project_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `personal_todo`
--
ALTER TABLE `personal_todo`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
