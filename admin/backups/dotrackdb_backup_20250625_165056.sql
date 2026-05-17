-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: dotrackdb
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `collaborate_group`
--

DROP TABLE IF EXISTS `collaborate_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collaborate_group` (
  `colgroup_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `colgroup_name` varchar(255) DEFAULT NULL,
  `group_code` char(36) NOT NULL DEFAULT uuid(),
  `teammem_id` int(11) NOT NULL,
  `colproj_id` int(11) DEFAULT NULL,
  `coltask_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`colgroup_id`),
  UNIQUE KEY `group_code` (`group_code`) USING BTREE,
  KEY `user_id` (`user_id`),
  KEY `colproj_id` (`colproj_id`),
  KEY `collaborate_group_ibfk_3` (`coltask_id`),
  CONSTRAINT `collaborate_group_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `collaborate_group_ibfk_2` FOREIGN KEY (`colproj_id`) REFERENCES `collaborate_project` (`colproj_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `collaborate_group_ibfk_3` FOREIGN KEY (`coltask_id`) REFERENCES `collaborate_task` (`coltask_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collaborate_group`
--

LOCK TABLES `collaborate_group` WRITE;
/*!40000 ALTER TABLE `collaborate_group` DISABLE KEYS */;
INSERT INTO `collaborate_group` VALUES (17,9,'nei','84YQPED9',0,NULL,NULL,'2025-06-24 11:43:27',NULL),(18,9,'nei','F2YCU71Z',0,NULL,NULL,'2025-06-24 11:44:08',NULL),(19,10,'IPT','EMZULK9L',0,NULL,NULL,'2025-06-25 16:47:13',NULL);
/*!40000 ALTER TABLE `collaborate_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collaborate_project`
--

DROP TABLE IF EXISTS `collaborate_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collaborate_project` (
  `colproj_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `colproj_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('not started','in progress','on hold','cancelled','done') NOT NULL DEFAULT 'not started',
  `colgroup_id` int(11) DEFAULT NULL,
  `coltask_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`colproj_id`),
  KEY `colgroup_id` (`colgroup_id`),
  KEY `collaborate_project_ibfk_1` (`user_id`),
  KEY `collaborate_project_ibfk_3` (`coltask_id`),
  CONSTRAINT `collaborate_project_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `collaborate_project_ibfk_2` FOREIGN KEY (`colgroup_id`) REFERENCES `collaborate_group` (`colgroup_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `collaborate_project_ibfk_3` FOREIGN KEY (`coltask_id`) REFERENCES `collaborate_task` (`coltask_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collaborate_project`
--

LOCK TABLES `collaborate_project` WRITE;
/*!40000 ALTER TABLE `collaborate_project` DISABLE KEYS */;
INSERT INTO `collaborate_project` VALUES (10,10,'Information Management','Final Presentation','in progress',18,NULL,'2025-06-24','2025-06-30','2025-06-24 12:12:37',NULL),(11,10,'IPT Project','Presentation','cancelled',18,NULL,'2025-06-27','2025-06-28','2025-06-25 16:48:30',NULL);
/*!40000 ALTER TABLE `collaborate_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collaborate_task`
--

DROP TABLE IF EXISTS `collaborate_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collaborate_task` (
  `coltask_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `assigned_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`coltask_id`),
  UNIQUE KEY `unique_task_subtask` (`coltask_id`,`colsubtask_name`),
  KEY `colproj_id` (`colproj_id`),
  KEY `collaborate_task_ibfk_1` (`user_id`),
  KEY `fk_assigned_user` (`assigned_user_id`),
  CONSTRAINT `collaborate_task_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `collaborate_task_ibfk_2` FOREIGN KEY (`colproj_id`) REFERENCES `collaborate_project` (`colproj_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assigned_user` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collaborate_task`
--

LOCK TABLES `collaborate_task` WRITE;
/*!40000 ALTER TABLE `collaborate_task` DISABLE KEYS */;
INSERT INTO `collaborate_task` VALUES (11,10,'DoTrack','',NULL,'in progress',10,'2025-06-24','2025-06-30','2025-06-24 12:12:37',NULL,10),(12,10,'Presentation','',NULL,'on hold',11,'2025-06-25','2025-06-27','2025-06-25 16:48:30',NULL,9);
/*!40000 ALTER TABLE `collaborate_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collaborate_teammem`
--

DROP TABLE IF EXISTS `collaborate_teammem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collaborate_teammem` (
  `teammem_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `colproj_id` int(11) DEFAULT NULL,
  `coltask_id` int(11) DEFAULT NULL,
  `colgroup_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`teammem_id`),
  KEY `colgroup_id` (`colgroup_id`),
  KEY `collaborate_teammem_ibfk_3` (`colproj_id`),
  KEY `collaborate_teammem_ibfk_1` (`user_id`),
  KEY `fk_task_subtask` (`coltask_id`),
  CONSTRAINT `collaborate_teammem_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `collaborate_teammem_ibfk_2` FOREIGN KEY (`colgroup_id`) REFERENCES `collaborate_group` (`colgroup_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `collaborate_teammem_ibfk_3` FOREIGN KEY (`colproj_id`) REFERENCES `collaborate_project` (`colproj_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `collaborate_teammem_ibfk_4` FOREIGN KEY (`coltask_id`) REFERENCES `collaborate_task` (`coltask_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collaborate_teammem`
--

LOCK TABLES `collaborate_teammem` WRITE;
/*!40000 ALTER TABLE `collaborate_teammem` DISABLE KEYS */;
INSERT INTO `collaborate_teammem` VALUES (31,10,NULL,NULL,18,'2025-06-24 11:50:51',NULL),(32,10,NULL,NULL,18,'2025-06-24 11:54:54',NULL),(33,10,NULL,NULL,18,'2025-06-24 11:55:00',NULL),(34,10,10,NULL,18,'2025-06-24 12:12:37',NULL),(35,10,NULL,NULL,18,'2025-06-24 13:59:56',NULL),(36,10,NULL,NULL,18,'2025-06-24 14:00:30',NULL),(37,10,11,NULL,18,'2025-06-25 16:48:30',NULL);
/*!40000 ALTER TABLE `collaborate_teammem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_habit`
--

DROP TABLE IF EXISTS `personal_habit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_habit` (
  `habit_id` int(11) NOT NULL AUTO_INCREMENT,
  `habit_name` varchar(255) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`habit_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `personal_habit_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_habit`
--

LOCK TABLES `personal_habit` WRITE;
/*!40000 ALTER TABLE `personal_habit` DISABLE KEYS */;
INSERT INTO `personal_habit` VALUES (15,'Presentation','Tuesday',10,'2025-06-24 12:11:31','2025-06-25 16:46:15'),(16,'Presentation 1','Friday',10,'2025-06-25 16:45:31',NULL);
/*!40000 ALTER TABLE `personal_habit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_project`
--

DROP TABLE IF EXISTS `personal_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_project` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `persubtask_name` text DEFAULT NULL,
  `priority` enum('not assigned','low','medium','high') NOT NULL DEFAULT 'not assigned',
  `status` enum('pending','not started','in progress','on hold','cancelled','submitted') NOT NULL DEFAULT 'pending',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`project_id`),
  UNIQUE KEY `persubtask-id` (`project_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `personal_project_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_project`
--

LOCK TABLES `personal_project` WRITE;
/*!40000 ALTER TABLE `personal_project` DISABLE KEYS */;
INSERT INTO `personal_project` VALUES (6,10,'Information Management','Finals','Database','high','in progress','2025-06-26','2025-06-30','2025-06-24 12:11:18',NULL),(7,10,'Group Presentation','Finals','123','high','in progress','2025-07-01','2025-07-04','2025-06-25 16:45:06',NULL);
/*!40000 ALTER TABLE `personal_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_todo`
--

DROP TABLE IF EXISTS `personal_todo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_todo` (
  `todo_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `todo_name` varchar(255) NOT NULL,
  `status` enum('pending','not started','in progress','on hold','cancelled','done','submitted') NOT NULL DEFAULT 'not started',
  `due_date` date DEFAULT NULL,
  `priority` enum('not assigned','low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `category` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`todo_id`),
  KEY `fk_user_id` (`user_id`),
  CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_todo`
--

LOCK TABLES `personal_todo` WRITE;
/*!40000 ALTER TABLE `personal_todo` DISABLE KEYS */;
INSERT INTO `personal_todo` VALUES (7,10,'Information Management','not started','2025-06-25','urgent','COMP 010','2025-06-24 12:10:20',NULL),(8,10,'Final Presentation','in progress','2025-06-30','high','IPT','2025-06-25 16:43:51',NULL);
/*!40000 ALTER TABLE `personal_todo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_active` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,'Admin','admin@gmail.com','$2a$12$LoFCgM1Bdl6lnrTM8O8k/uz9i7tylCXZZSOOrn9eXwcM6ukQHuUnu','admin','active','2025-06-25 08:50:56','2025-06-12 16:46:19',NULL),(9,'Kim','kim@gmail.com','$2y$10$qqqIlMe5IOutgr7Wmftdhebr6gxDDwCxPxR1/jNh5irDM6OEhTRfO','user','active','2025-06-24 03:46:47','2025-06-24 03:38:04',NULL),(10,'nei','nei@gmail.com','$2y$10$bXhDZbS6ANTBEJftkxOrvOsi.3AmHz.JdyFyTfBK6We7iq3bOEqY.','user','inactive','2025-06-25 08:49:51','2025-06-24 03:47:26',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-25 16:50:57
