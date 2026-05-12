-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: tablet_control
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
-- Table structure for table `admin_policy`
--

DROP TABLE IF EXISTS `admin_policy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_policy` (
  `id` int(11) NOT NULL DEFAULT 1,
  `vpn_enabled` tinyint(1) DEFAULT 0,
  `dns_primary` varchar(45) DEFAULT '1.1.1.1',
  `dns_secondary` varchar(45) DEFAULT '1.0.0.1',
  `disable_installs` tinyint(1) DEFAULT 1,
  `lock_settings` tinyint(1) DEFAULT 0,
  `kiosk_mode` tinyint(1) DEFAULT 0,
  `kiosk_app` varchar(200) DEFAULT NULL,
  `disable_status_bar` tinyint(1) DEFAULT 0,
  `disable_screenshot` tinyint(1) DEFAULT 0,
  `disable_camera` tinyint(1) DEFAULT 0,
  `disable_usb` tinyint(1) DEFAULT 0,
  `mdm_package_name` varchar(200) NOT NULL DEFAULT 'com.mdm.agent',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `block_settings_access` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_policy`
--

LOCK TABLES `admin_policy` WRITE;
/*!40000 ALTER TABLE `admin_policy` DISABLE KEYS */;
INSERT INTO `admin_policy` VALUES (1,0,'1.1.1.1','1.0.0.1',1,1,1,NULL,0,0,0,1,'com.mdm.agent','2026-04-29 16:23:30',1);
/*!40000 ALTER TABLE `admin_policy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','$2y$10$opiGSCNBizX7yijIC7x6Cu1esYaZjBLpptBeslnSwFuOflmjY7VZW','2026-04-20 12:33:21');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `allowed_apps`
--

DROP TABLE IF EXISTS `allowed_apps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `allowed_apps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_name` varchar(200) NOT NULL,
  `app_label` varchar(200) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pkg` (`package_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `allowed_apps`
--

LOCK TABLES `allowed_apps` WRITE;
/*!40000 ALTER TABLE `allowed_apps` DISABLE KEYS */;
INSERT INTO `allowed_apps` VALUES (1,'com.mdm.agent','MDM Agent','2026-04-21 01:42:24'),(2,'com.android.dialer','Phone Dialer','2026-04-21 01:42:24'),(3,'com.android.phone','Phone','2026-04-21 01:42:24'),(4,'com.google.android.dialer','Google Phone','2026-04-21 01:42:24');
/*!40000 ALTER TABLE `allowed_apps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_users`
--

DROP TABLE IF EXISTS `app_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_users`
--

LOCK TABLES `app_users` WRITE;
/*!40000 ALTER TABLE `app_users` DISABLE KEYS */;
INSERT INTO `app_users` VALUES (1,'eofori','$2y$10$Qha5kffd1lZ8ZxxXTD1lN.4387ZYj62wemdn5t8xW6e0TGr69cjJq','EBENEZER OFORI',1,'2026-04-28 04:45:24');
/*!40000 ALTER TABLE `app_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blocked_apps`
--

DROP TABLE IF EXISTS `blocked_apps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blocked_apps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `auto_added` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `package_name` (`package_name`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocked_apps`
--

LOCK TABLES `blocked_apps` WRITE;
/*!40000 ALTER TABLE `blocked_apps` DISABLE KEYS */;
INSERT INTO `blocked_apps` VALUES (2,'com.google.android.apps.kids.familylink','2026-04-28 05:31:31',0),(3,'com.example.teachers_attendance','2026-04-28 05:40:49',0),(5,'com.sportybet.android.gp.gh','2026-04-28 12:08:49',0),(7,'com.mediatek.camera','2026-04-29 09:33:49',0),(8,'com.google.android.apps.messaging','2026-04-29 09:37:02',0),(9,'com.android.server.telecom','2026-04-29 09:37:22',0),(10,'com.android.settings','2026-04-29 16:14:29',0),(11,'com.android.providers.settings','2026-04-29 16:15:33',0),(12,'com.android.settings.intelligence','2026-04-29 16:16:04',0),(13,'com.google.android.calendar','2026-04-29 16:16:19',0),(14,'com.samsung.android.settings','2026-04-29 16:38:48',1),(15,'com.huawei.systemmanager','2026-04-29 16:38:48',1),(16,'com.motorola.settings','2026-04-29 16:38:48',1),(17,'com.lge.settings','2026-04-29 16:38:48',1),(18,'com.sonyericsson.settings','2026-04-29 16:38:48',1),(19,'com.htc.preference','2026-04-29 16:38:48',1),(20,'com.google.android.packageinstaller','2026-04-29 16:38:48',1),(30,'com.samsung.android.sm','2026-04-29 16:50:29',1),(33,'com.android.bluetooth','2026-04-29 16:56:56',0);
/*!40000 ALTER TABLE `blocked_apps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blocked_sites`
--

DROP TABLE IF EXISTS `blocked_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blocked_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain` (`domain`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocked_sites`
--

LOCK TABLES `blocked_sites` WRITE;
/*!40000 ALTER TABLE `blocked_sites` DISABLE KEYS */;
INSERT INTO `blocked_sites` VALUES (4,'www.xnxx.com','2026-04-28 03:59:53'),(5,'https://www.sportybet.com','2026-04-28 08:24:41'),(6,'www.snapchat.com','2026-04-29 09:39:57');
/*!40000 ALTER TABLE `blocked_sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,'Grade 7','2026-05-11 22:34:55'),(2,'Grade 8','2026-05-11 22:34:55'),(3,'Grade 9','2026-05-11 22:34:55'),(4,'Grade 10','2026-05-11 22:34:55'),(5,'Grade 11','2026-05-11 22:34:55'),(6,'Grade 12','2026-05-11 22:34:55');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curfew_settings`
--

DROP TABLE IF EXISTS `curfew_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curfew_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `curfew_time` time NOT NULL DEFAULT '22:00:00',
  `unlock_password_hash` varchar(64) NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `whitelist_mode` tinyint(1) NOT NULL DEFAULT 0,
  `uninstall_password_hash` varchar(64) NOT NULL DEFAULT '',
  `uninstall_protection_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `tamper_lockdown` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curfew_settings`
--

LOCK TABLES `curfew_settings` WRITE;
/*!40000 ALTER TABLE `curfew_settings` DISABLE KEYS */;
INSERT INTO `curfew_settings` VALUES (1,1,'22:00:00','0fe0ec5b9b20895a3bfea08e961e05d738aca5bb3e0b9873815f221d1f8cb551','2026-05-11 22:10:33',0,'0fe0ec5b9b20895a3bfea08e961e05d738aca5bb3e0b9873815f221d1f8cb551',1,1);
/*!40000 ALTER TABLE `curfew_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `device_commands`
--

DROP TABLE IF EXISTS `device_commands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `device_commands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) NOT NULL,
  `command` varchar(50) NOT NULL,
  `payload` text DEFAULT NULL,
  `status` enum('pending','delivered','failed') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_device_status` (`device_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `device_commands`
--

LOCK TABLES `device_commands` WRITE;
/*!40000 ALTER TABLE `device_commands` DISABLE KEYS */;
/*!40000 ALTER TABLE `device_commands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `device_locations`
--

DROP TABLE IF EXISTS `device_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `device_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `accuracy` float DEFAULT NULL,
  `battery_level` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_device_created` (`device_id`,`created_at`),
  CONSTRAINT `fk_loc_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=226 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `device_locations`
--

LOCK TABLES `device_locations` WRITE;
/*!40000 ALTER TABLE `device_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `device_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_name` varchar(100) NOT NULL,
  `student_name` varchar(120) NOT NULL DEFAULT '',
  `serial_number` varchar(100) NOT NULL,
  `imei` varchar(20) DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `status` enum('online','offline','removed') NOT NULL DEFAULT 'offline',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `removed_at` datetime DEFAULT NULL,
  `class_name` varchar(80) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_number` (`serial_number`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devices`
--

LOCK TABLES `devices` WRITE;
/*!40000 ALTER TABLE `devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disabled_apps`
--

DROP TABLE IF EXISTS `disabled_apps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disabled_apps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_name` varchar(200) NOT NULL,
  `app_label` varchar(200) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp(),
  `auto_added` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `package_name` (`package_name`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disabled_apps`
--

LOCK TABLES `disabled_apps` WRITE;
/*!40000 ALTER TABLE `disabled_apps` DISABLE KEYS */;
INSERT INTO `disabled_apps` VALUES (3,'com.huawei.systemmanager','Huawei Settings','2026-04-29 16:38:48',1),(4,'com.motorola.settings','Motorola Settings','2026-04-29 16:38:48',1),(5,'com.lge.settings','LG Settings','2026-04-29 16:38:48',1),(6,'com.sonyericsson.settings','Sony/Xperia Settings','2026-04-29 16:38:48',1),(7,'com.htc.preference','HTC Settings','2026-04-29 16:38:48',1),(18,'com.samsung.android.sm','Samsung Device Care','2026-04-29 16:50:29',1),(19,'com.samsung.android.settings','Samsung Settings','2026-04-29 16:51:00',1);
/*!40000 ALTER TABLE `disabled_apps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `installed_apps`
--

DROP TABLE IF EXISTS `installed_apps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `installed_apps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) NOT NULL,
  `package_name` varchar(200) NOT NULL,
  `app_label` varchar(200) DEFAULT '',
  `version_name` varchar(50) DEFAULT '',
  `version_code` int(11) DEFAULT 0,
  `is_system` tinyint(1) DEFAULT 0,
  `first_seen` datetime DEFAULT current_timestamp(),
  `last_seen` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dev_pkg` (`device_id`,`package_name`),
  KEY `idx_device` (`device_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25951 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `installed_apps`
--

LOCK TABLES `installed_apps` WRITE;
/*!40000 ALTER TABLE `installed_apps` DISABLE KEYS */;
/*!40000 ALTER TABLE `installed_apps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `device_id` (`device_id`),
  CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=221 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timed_app_blocks`
--

DROP TABLE IF EXISTS `timed_app_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timed_app_blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_name` varchar(255) NOT NULL,
  `label` varchar(100) NOT NULL DEFAULT '',
  `block_from` time NOT NULL DEFAULT '00:00:00',
  `block_to` time NOT NULL DEFAULT '23:59:59',
  `days_of_week` varchar(20) NOT NULL DEFAULT '1234567',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timed_app_blocks`
--

LOCK TABLES `timed_app_blocks` WRITE;
/*!40000 ALTER TABLE `timed_app_blocks` DISABLE KEYS */;
INSERT INTO `timed_app_blocks` VALUES (1,'com.android.vending','Google Play Store','00:00:00','23:59:59','1234567',0,'2026-04-28 06:47:58');
/*!40000 ALTER TABLE `timed_app_blocks` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-12  2:12:13
