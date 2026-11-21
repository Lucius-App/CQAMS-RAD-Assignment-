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
-- Table structure for table `user_accounts`
--

DROP TABLE IF EXISTS `user_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_accounts` (
  `user_account_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('administrator','doctor','patient') NOT NULL,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_account_id`),
  UNIQUE KEY `uq_user_accounts_username` (`username`),
  KEY `idx_user_accounts_role_active` (`role`,`is_active`),
  KEY `idx_user_accounts_approval` (`approval_status`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_accounts`
--

LOCK TABLES `user_accounts` WRITE;
/*!40000 ALTER TABLE `user_accounts` DISABLE KEYS */;
INSERT INTO `user_accounts` VALUES (1,'admin_demo','$2y$10$wwyMorFZwPQZNQKJ1oORQOVsEy8mKlztK.P5h0ADP68NeqdZ0k.9S','administrator','approved',1,'2026-11-21 23:44:55','2026-11-08 19:42:43','2026-11-21 23:44:55'),(3,'doctor_demo','$2y$10$fh7mzGP/N/hCFru6LCy0v.QKEjOlNyHolm.HNErpEk1psq/H6o7ue','doctor','approved',1,'2026-11-21 23:46:40','2026-11-08 19:42:43','2026-11-21 23:46:40'),(4,'doctor2_demo','$2y$10$fh7mzGP/N/hCFru6LCy0v.QKEjOlNyHolm.HNErpEk1psq/H6o7ue','doctor','approved',1,'2026-11-21 22:53:35','2026-11-13 10:18:25','2026-11-21 22:53:35'),(7,'Lucas','$2y$10$GX9gqk91zpcve9TZ1.K40eZ0.7Pcva8G.n0ou62jpJJjXZN.siWoO','patient','approved',1,'2026-11-21 23:44:01','2026-11-21 20:19:09','2026-11-21 23:44:01'),(8,'doctor_north_demo','$2y$10$fh7mzGP/N/hCFru6LCy0v.QKEjOlNyHolm.HNErpEk1psq/H6o7ue','doctor','approved',1,NULL,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(9,'doctor_south_demo','$2y$10$fh7mzGP/N/hCFru6LCy0v.QKEjOlNyHolm.HNErpEk1psq/H6o7ue','doctor','approved',1,NULL,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(10,'patient_demo','$2y$10$OnAgSPLtBuT0WwvF4yItN.ncaXPSUhzqySN2xoCCUigNLHZN63psW','patient','approved',1,'2026-11-21 22:46:41','2026-11-21 20:38:16','2026-11-21 22:46:41'),(11,'pending_patient','$2y$10$OnAgSPLtBuT0WwvF4yItN.ncaXPSUhzqySN2xoCCUigNLHZN63psW','patient','pending',0,NULL,'2026-11-21 20:38:16','2026-11-21 20:38:16'),(66,'Lucaswrwee','$2y$10$E/9gNJ2G6L9gOd4VTEK49uGmGaDu/SogcAl.jeh54fyqg64gyof8S','patient','pending',0,NULL,'2026-11-21 23:42:48','2026-11-21 23:42:48');
/*!40000 ALTER TABLE `user_accounts` ENABLE KEYS */;
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
