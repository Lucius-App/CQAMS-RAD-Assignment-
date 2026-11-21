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
-- Table structure for table `queues`
--

DROP TABLE IF EXISTS `queues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `queues` (
  `queue_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `clinic_id` bigint(20) unsigned DEFAULT 1,
  `clinic_code` varchar(30) NOT NULL DEFAULT 'MAIN',
  `queue_date` date NOT NULL,
  `queue_number` int(10) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `status` enum('waiting','called','in_consultation','completed','skipped','cancelled') NOT NULL DEFAULT 'waiting',
  `checked_in_at` datetime NOT NULL DEFAULT current_timestamp(),
  `called_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `checked_in_by_user_account_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`queue_id`),
  UNIQUE KEY `uq_queues_daily_number` (`clinic_code`,`queue_date`,`queue_number`),
  UNIQUE KEY `uq_queues_patient_day` (`clinic_code`,`queue_date`,`patient_id`),
  UNIQUE KEY `uq_queues_appointment` (`appointment_id`),
  KEY `idx_queues_display` (`clinic_code`,`queue_date`,`status`,`queue_number`),
  KEY `idx_queues_doctor_status` (`doctor_id`,`queue_date`,`status`),
  KEY `fk_queues_patient` (`patient_id`),
  KEY `fk_queues_checked_in_by` (`checked_in_by_user_account_id`),
  KEY `idx_queues_clinic` (`clinic_id`),
  CONSTRAINT `fk_queues_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_queues_checked_in_by` FOREIGN KEY (`checked_in_by_user_account_id`) REFERENCES `user_accounts` (`user_account_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_queues_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_queues_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_queues_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_queues_number` CHECK (`queue_number` > 0),
  CONSTRAINT `chk_queues_called_time` CHECK (`status` = 'waiting' or `called_at` is not null),
  CONSTRAINT `chk_queues_completed_time` CHECK (`status` <> 'completed' or `completed_at` is not null)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `queues`
--

LOCK TABLES `queues` WRITE;
/*!40000 ALTER TABLE `queues` DISABLE KEYS */;
INSERT INTO `queues` VALUES (1,1,'MAIN','2026-11-13',1,1,1,1,'completed','2026-11-13 08:18:25','2026-11-13 08:48:25','2026-11-13 09:18:25',NULL,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(2,1,'MAIN','2026-11-13',2,2,2,2,'called','2026-11-13 09:48:25','2026-11-13 10:13:25',NULL,NULL,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(3,1,'MAIN','2026-11-13',3,3,3,2,'waiting','2026-11-13 10:08:25',NULL,NULL,NULL,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(4,1,'MAIN','2026-11-21',1,10,NULL,1,'in_consultation','2026-11-21 21:58:26','2026-11-21 21:58:28',NULL,1,'2026-11-21 21:58:26','2026-11-21 21:58:31'),(5,1,'MAIN','2026-11-21',2,28,NULL,7,'called','2026-11-21 21:59:07','2026-11-21 21:59:10',NULL,1,'2026-11-21 21:59:07','2026-11-21 21:59:10');
/*!40000 ALTER TABLE `queues` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-21 23:50:56
