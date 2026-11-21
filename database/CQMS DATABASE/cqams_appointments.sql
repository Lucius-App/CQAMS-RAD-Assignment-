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
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `appointment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `clinic_id` bigint(20) unsigned DEFAULT 1,
  `patient_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `doctor_schedule_id` bigint(20) unsigned DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `reason_category` enum('routine','followup','emergency','refill','reschedule','other') NOT NULL DEFAULT 'routine',
  `reason` varchar(255) DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `approved_by_user_account_id` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_note` varchar(255) DEFAULT NULL,
  `status` enum('scheduled','confirmed','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
  `created_by_user_account_id` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by_user_account_id` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active_doctor_slot` varchar(180) GENERATED ALWAYS AS (if(`status` = 'cancelled' or `approval_status` = 'rejected',NULL,concat(`doctor_id`,'|',`appointment_date`,'|',`start_time`))) STORED,
  `active_patient_slot` varchar(180) GENERATED ALWAYS AS (if(`status` = 'cancelled' or `approval_status` = 'rejected',NULL,concat(`patient_id`,'|',`appointment_date`,'|',`start_time`))) STORED,
  PRIMARY KEY (`appointment_id`),
  UNIQUE KEY `uq_appointments_active_doctor_slot` (`active_doctor_slot`),
  UNIQUE KEY `uq_appointments_active_patient_slot` (`active_patient_slot`),
  KEY `idx_appointments_date_status` (`appointment_date`,`status`),
  KEY `idx_appointments_patient_date` (`patient_id`,`appointment_date`),
  KEY `idx_appointments_doctor_date` (`doctor_id`,`appointment_date`),
  KEY `fk_appointments_schedule` (`doctor_schedule_id`),
  KEY `fk_appointments_created_by` (`created_by_user_account_id`),
  KEY `fk_appointments_cancelled_by` (`cancelled_by_user_account_id`),
  KEY `idx_appointments_approval_date` (`approval_status`,`appointment_date`),
  KEY `fk_appointments_approved_by` (`approved_by_user_account_id`),
  KEY `idx_appointments_clinic` (`clinic_id`),
  CONSTRAINT `fk_appointments_approved_by` FOREIGN KEY (`approved_by_user_account_id`) REFERENCES `user_accounts` (`user_account_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_cancelled_by` FOREIGN KEY (`cancelled_by_user_account_id`) REFERENCES `user_accounts` (`user_account_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_created_by` FOREIGN KEY (`created_by_user_account_id`) REFERENCES `user_accounts` (`user_account_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_schedule` FOREIGN KEY (`doctor_schedule_id`) REFERENCES `doctor_schedules` (`doctor_schedule_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_appointments_end_time` CHECK (`end_time` is null or `end_time` > `start_time`),
  CONSTRAINT `chk_appointments_cancellation` CHECK (`status` <> 'cancelled' and `cancelled_at` is null or `status` = 'cancelled' and `cancelled_at` is not null)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` (`appointment_id`, `clinic_id`, `patient_id`, `doctor_id`, `doctor_schedule_id`, `appointment_date`, `start_time`, `end_time`, `reason_category`, `reason`, `approval_status`, `approved_by_user_account_id`, `approved_at`, `approval_note`, `status`, `created_by_user_account_id`, `cancelled_by_user_account_id`, `cancelled_at`, `cancellation_reason`, `created_at`, `updated_at`) VALUES (1,1,1,1,2,'2026-11-13','09:00:00','09:30:00','routine','Routine consultation','approved',NULL,NULL,NULL,'completed',NULL,NULL,NULL,NULL,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(2,1,2,2,3,'2026-11-13','09:30:00','10:00:00','routine','Follow-up consultation','approved',NULL,NULL,NULL,'confirmed',NULL,NULL,NULL,NULL,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(3,1,3,2,3,'2026-11-13','10:00:00','10:30:00','routine','Child health review','approved',NULL,NULL,NULL,'scheduled',NULL,NULL,NULL,NULL,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(4,1,4,1,5,'2026-11-14','09:00:00','09:30:00','routine','New patient consultation','approved',NULL,NULL,NULL,'scheduled',NULL,NULL,NULL,NULL,'2026-11-13 10:18:25','2026-11-13 10:18:25'),(11,1,12,2,18,'2026-11-24','13:30:00','14:00:00','routine','djioaj','pending',NULL,NULL,NULL,'scheduled',7,NULL,NULL,NULL,'2026-11-21 21:56:11','2026-11-21 21:56:11'),(15,1,12,2,21,'2026-11-27','15:00:00','15:30:00','routine','segfrdg','pending',NULL,NULL,NULL,'scheduled',7,NULL,NULL,NULL,'2026-11-21 23:44:35','2026-11-21 23:44:35');
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
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
