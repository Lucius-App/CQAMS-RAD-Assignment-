-- MySQL dump 10.13  Distrib 8.0.34, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: cqams
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `queue_events`
--

DROP TABLE IF EXISTS `queue_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `queue_events` (
  `queue_event_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue_id` bigint(20) unsigned NOT NULL,
  `previous_status` enum('waiting','called','in_consultation','completed','skipped','cancelled') DEFAULT NULL,
  `new_status` enum('waiting','called','in_consultation','completed','skipped','cancelled') NOT NULL,
  `changed_by_user_account_id` bigint(20) unsigned DEFAULT NULL,
  `event_note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`queue_event_id`),
  KEY `idx_queue_events_queue_time` (`queue_id`,`created_at`),
  KEY `fk_queue_events_changed_by` (`changed_by_user_account_id`),
  CONSTRAINT `fk_queue_events_changed_by` FOREIGN KEY (`changed_by_user_account_id`) REFERENCES `user_accounts` (`user_account_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_queue_events_queue` FOREIGN KEY (`queue_id`) REFERENCES `queues` (`queue_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `queue_events`
--

LOCK TABLES `queue_events` WRITE;
/*!40000 ALTER TABLE `queue_events` DISABLE KEYS */;
INSERT INTO `queue_events` VALUES (1,1,NULL,'waiting',NULL,'Development sample check-in','2026-11-13 10:18:25'),(2,2,NULL,'waiting',NULL,'Development sample check-in','2026-11-13 10:18:25'),(3,3,NULL,'waiting',NULL,'Development sample check-in','2026-11-13 10:18:25'),(4,1,'waiting','called',NULL,'Development sample call','2026-11-13 10:18:25'),(5,2,'waiting','called',NULL,'Development sample call','2026-11-13 10:18:25'),(7,1,'called','in_consultation',3,'Development sample consultation start','2026-11-13 10:18:25'),(8,1,'in_consultation','completed',3,'Development sample consultation complete','2026-11-13 10:18:25'),(9,4,NULL,'waiting',1,'Walk-in check-in','2026-11-21 21:58:26'),(10,4,'waiting','called',1,NULL,'2026-11-21 21:58:28'),(11,4,'called','in_consultation',1,NULL,'2026-11-21 21:58:31'),(12,5,NULL,'waiting',1,'Walk-in check-in','2026-11-21 21:59:07'),(13,5,'waiting','called',1,NULL,'2026-11-21 21:59:10');
/*!40000 ALTER TABLE `queue_events` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-21 23:50:58
