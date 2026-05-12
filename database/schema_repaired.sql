-- Clean Database Schema for Crime Reporting System
CREATE DATABASE IF NOT EXISTS `crime_reporting_db`;
USE `crime_reporting_db`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `crime_updates`;
DROP TABLE IF EXISTS `evidence`;
DROP TABLE IF EXISTS `crimes`;
DROP TABLE IF EXISTS `public_notices`;
DROP TABLE IF EXISTS `system_logs`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `districts`;

-- 1. Districts
CREATE TABLE `districts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `role` enum('citizen','police','admin') NOT NULL DEFAULT 'citizen',
  `address` text DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `badge_number` varchar(50) DEFAULT NULL,
  `rank` varchar(50) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `police_status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `district_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `badge_number` (`badge_number`),
  CONSTRAINT `fk_user_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Crimes
CREATE TABLE `crimes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `crime_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `incident_date` datetime NOT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `district` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('Pending','Assigned','In Investigation','Resolved','Closed','Rejected') DEFAULT 'Pending',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_escalated` tinyint(1) DEFAULT 0,
  `district_id` int(11) DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_crime_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`),
  CONSTRAINT `fk_crimes_police` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_crimes_reporter` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Crime Updates
CREATE TABLE `crime_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `crime_id` int(11) NOT NULL,
  `status_from` varchar(50) NOT NULL,
  `status_to` varchar(50) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_updates_crime` FOREIGN KEY (`crime_id`) REFERENCES `crimes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Evidence
CREATE TABLE `evidence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `crime_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_evidence_crime` FOREIGN KEY (`crime_id`) REFERENCES `crimes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Public Notices
CREATE TABLE `public_notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `target_role` enum('all','citizen','police') DEFAULT 'all',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_notices_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. System Logs
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
