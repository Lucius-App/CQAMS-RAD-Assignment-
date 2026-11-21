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
-- Table structure for table `doctor_schedules`
--

DROP TABLE IF EXISTS `doctor_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor_schedules` (
  `doctor_schedule_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `day_of_week` tinyint(3) unsigned NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration_minutes` smallint(5) unsigned NOT NULL DEFAULT 30,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`doctor_schedule_id`),
  UNIQUE KEY `uq_doctor_schedule_slot` (`doctor_id`,`day_of_week`,`start_time`,`end_time`),
  KEY `idx_doctor_schedules_lookup` (`doctor_id`,`day_of_week`,`is_active`),
  CONSTRAINT `fk_doctor_schedules_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_doctor_schedules_day` CHECK (`day_of_week` between 1 and 7),
  CONSTRAINT `chk_doctor_schedules_time` CHECK (`end_time` > `start_time`),
  CONSTRAINT `chk_doctor_schedules_slot_duration` CHECK (`slot_duration_minutes` between 5 and 480)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_schedules`
--

LOCK TABLES `doctor_schedules` WRITE;
/*!40000 ALTER TABLE `doctor_schedules` DISABLE KEYS */;
INSERT INTO `doctor_schedules` VALUES (1,1,1,'08:00:00','17:00:00',30,1,'2026-11-08 19:42:43','2026-11-08 19:42:43'),(2,1,6,'08:00:00','17:00:00',30,1,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(3,2,6,'08:00:00','17:00:00',30,1,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(5,1,7,'09:00:00','13:00:00',30,1,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(6,2,7,'09:00:00','13:00:00',30,1,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(10,1,2,'08:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(11,1,3,'08:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(12,1,4,'08:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(13,1,5,'08:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(14,1,6,'09:00:00','13:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(17,2,1,'08:00:00','16:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(18,2,2,'08:00:00','16:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(19,2,3,'08:00:00','16:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(20,2,4,'08:00:00','16:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(21,2,5,'08:00:00','16:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(24,6,1,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(25,7,1,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(26,6,2,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(27,7,2,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(28,6,3,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(29,7,3,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(30,6,4,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(31,7,4,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(32,6,5,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(33,7,5,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(34,6,6,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(35,7,6,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(36,6,7,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(37,7,7,'09:00:00','17:00:00',30,1,'2026-11-21 20:38:16','2026-11-21 20:38:16');
/*!40000 ALTER TABLE `doctor_schedules` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-21 23:50:55
