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
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patients` (
  `patient_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_account_id` bigint(20) unsigned DEFAULT NULL,
  `registration_number` varchar(50) NOT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` enum('female','male','other','undisclosed') NOT NULL DEFAULT 'undisclosed',
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(254) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `emergency_contact_name` varchar(160) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`patient_id`),
  UNIQUE KEY `uq_patients_registration_number` (`registration_number`),
  UNIQUE KEY `uq_patients_national_id` (`national_id`),
  UNIQUE KEY `uq_patients_user_account` (`user_account_id`),
  KEY `idx_patients_name` (`last_name`,`first_name`),
  KEY `idx_patients_phone` (`phone`),
  CONSTRAINT `fk_patients_user_account` FOREIGN KEY (`user_account_id`) REFERENCES `user_accounts` (`user_account_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (1,NULL,'PAT-000001','DEMO-ID-001','Sample','Patient','2000-01-15','undisclosed','0000000010','patient@example.test','Demo address',NULL,NULL,1,'2026-11-08 19:42:43','2026-11-08 19:42:43'),(2,NULL,'PAT-000002','DEMO-ID-002','Nurul','Hassan','1995-03-22','female','0000000011','nurul@example.test','Demo address 2',NULL,NULL,1,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(3,NULL,'PAT-000003','DEMO-ID-003','Jason','Lee','1988-07-09','male','0000000012','jason@example.test','Demo address 3',NULL,NULL,1,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(4,NULL,'PAT-000004','DEMO-ID-004','Mei','Wong','2012-11-18','female','0000000013','mei@example.test','Demo address 4',NULL,NULL,1,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(5,NULL,'PAT-000005','DEMO-ID-005','Farid','Aziz','1979-01-30','male','0000000014','farid@example.test','Demo address 5',NULL,NULL,1,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(10,NULL,'PAT-202611-92C018','nkjsfjs2324','Lucas','Aung',NULL,'undisclosed','0147881353','jack135306@gmail.com','wahhhhh','vmosjdokvj','0394999959',1,'2026-11-13 10:20:55','2026-11-13 10:20:55'),(11,NULL,'PAT-202611-7D41A9','svgfd','Lucas','Aung',NULL,'undisclosed','0147881353','jack135306@gmail.com','wahhhhh','Lucas Aung','0147881353',1,'2026-11-13 10:22:14','2026-11-13 10:22:14'),(12,7,'PAT-202611-71E7F1','MJ234567','Aung Pyae','Phyoe','2026-10-01','undisclosed','0394999959','luciuslt76@gmail.com','wahhhhh','Lucas Aung','0147881353',1,'2026-11-21 20:19:09','2026-11-21 20:19:09'),(28,10,'PAT-DEMO-BOOKING','DEMO-BOOKING-001','Demo','Booking Patient','1990-01-01','undisclosed','0000000020','booking.patient@example.test','Demo address',NULL,NULL,1,'2026-11-21 20:40:16','2026-11-21 20:40:16'),(60,66,'PAT-202611-B26E05','MJ2364654654','Aung Pyae','Phyoe2','2026-10-01','undisclosed','0394999959','luciuslt76@gmail.com','fsgse','Lucas Aung','0147881353',1,'2026-11-21 23:42:48','2026-11-21 23:42:48');
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
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
