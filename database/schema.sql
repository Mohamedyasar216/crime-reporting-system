-- Database Schema for Crime Reporting System (CRS)
-- Unified User Table structure and Seed Data

CREATE DATABASE IF NOT EXISTS `crime_reporting_db`;
USE `crime_reporting_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Table structure for table `districts`
DROP TABLE IF EXISTS `districts`;
CREATE TABLE `districts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table structure for table `users`
DROP TABLE IF EXISTS `users`;
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

-- 3. Table structure for table `crimes`
DROP TABLE IF EXISTS `crimes`;
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
  `status` varchar(100) DEFAULT 'Pending',
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

-- 4. Table structure for table `crime_updates`
DROP TABLE IF EXISTS `crime_updates`;
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

-- 5. Table structure for table `evidence`
DROP TABLE IF EXISTS `evidence`;
CREATE TABLE `evidence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `crime_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_evidence_crime` FOREIGN KEY (`crime_id`) REFERENCES `crimes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Table structure for table `public_notices`
DROP TABLE IF EXISTS `public_notices`;
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

-- 7. Table structure for table `system_logs`
DROP TABLE IF EXISTS `system_logs`;
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

-- SEED DATA

-- Districts
INSERT INTO `districts` (`id`, `name`) VALUES
(1, 'Ariyalur'), (2, 'Chengalpattu'), (3, 'Chennai'), (4, 'Coimbatore'), (5, 'Cuddalore'),
(6, 'Dharmapuri'), (7, 'Dindigul'), (8, 'Erode'), (9, 'Kallakurichi'), (10, 'Kanchipuram'),
(11, 'Kanyakumari'), (12, 'Karur'), (13, 'Krishnagiri'), (14, 'Madurai'), (15, 'Mayiladuthurai'),
(16, 'Nagapattinam'), (17, 'Namakkal'), (18, 'Nilgiris'), (19, 'Perambalur'), (20, 'Pudukkottai'),
(21, 'Ramanathapuram'), (22, 'Ranipet'), (23, 'Salem'), (24, 'Sivaganga'), (25, 'Tenkasi'),
(26, 'Thanjavur'), (27, 'Theni'), (28, 'Thoothukudi'), (29, 'Tiruchirappalli'), (30, 'Tirunelveli'),
(31, 'Tirupathur'), (32, 'Tiruppur'), (33, 'Tiruvallur'), (34, 'Tiruvannamalai'), (35, 'Tiruvarur'),
(36, 'Vellore'), (37, 'Viluppuram'), (38, 'Virudhunagar')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Users (Admin, DGP, SPs, Citizens)
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `mobile`, `role`, `address`, `state`, `district`, `pincode`, `badge_number`, `rank`, `specialization`, `police_status`, `created_at`, `district_id`) VALUES
(1, 'Super Admin', 'admincrs@gmail.com', '$2y$10$NTocvZzg9TS7sdMaMU..ee5zGQSJEsRfbR1AjD9TAuIlh7oENxd66', '9999999999', 'admin', 'HQ, New Delhi', 'Delhi', 'New Delhi', '110001', NULL, NULL, NULL, 'Active', '2026-01-29 10:58:48', NULL),
(7, 'Director General of Police', 'dgp@gmail.com', '$2y$10$pwwhcWtFQMdCGJFtri9gBuK/LQacytAw1SB4gQK/j/9IdzVyZWno6', '9999999999', 'police', NULL, NULL, 'Tamil Nadu', NULL, 'TN01DGP', 'DGP', NULL, 'Active', '2026-01-29 11:35:48', NULL),
(8, 'SP Ariyalur', 'sp_ariyalur@gmail.com', '$2y$10$VFYKUzImMBqgGx0rfTiSnOZU4e/MZu544wiSnX3h/U2I.9qMl6hnq', '9876543200', 'police', NULL, NULL, 'Ariyalur', NULL, 'TN02SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 1),
(9, 'SP Chengalpattu', 'sp_chengalpattu@gmail.com', '$2y$10$eCYp2zyN4BarGvGiX6iFH.cTGaHu4fODNWKXGmuqiX/OpANYgxff6', '9876543201', 'police', NULL, NULL, 'Chengalpattu', NULL, 'TN03SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 2),
(10, 'SP Chennai', 'sp_chennai@gmail.com', '$2y$10$aJHFZ.vJYLBLearj3ZR7kOA1TrExptWthq8NTv/8gDeW/sfmox/eq', '9876543202', 'police', NULL, NULL, 'Chennai', NULL, 'TN04SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 3),
(11, 'SP Coimbatore', 'sp_coimbatore@gmail.com', '$2y$10$SZASIsMX41LiCp7UqqD2XuKIEothBoV6W3RUvqSu9I6KoKyGaTCA2', '9876543203', 'police', NULL, NULL, 'Coimbatore', NULL, 'TN05SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 4),
(12, 'SP Cuddalore', 'sp_cuddalore@gmail.com', '$2y$10$OVpZ3vRmImNKxpuv1l1T7OLiWnlCBLmvHItnfwdTMcr72c0jxVgEC', '9876543204', 'police', NULL, NULL, 'Cuddalore', NULL, 'TN06SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 5),
(13, 'SP Dharmapuri', 'sp_dharmapuri@gmail.com', '$2y$10$9sZcOd10cbHbZ8.h5VqYAeqWDUHWOBkgj8qmb9FiAgqiEcluFR/BC', '9876543205', 'police', NULL, NULL, 'Dharmapuri', NULL, 'TN07SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 6),
(14, 'SP Dindigul', 'sp_dindigul@gmail.com', '$2y$10$EsDmOEe0UU.dxDnH5JAsXOLZd8DQKOB0myVmB4o.vgAMPa2TaXfye', '9876543206', 'police', NULL, NULL, 'Dindigul', NULL, 'TN08SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 7),
(15, 'SP Erode', 'sp_erode@gmail.com', '$2y$10$nEqMYtnspMyZaaxdl/XmNeL.EuEWSNFu3.PHJf2PWszAcPVO.0SY.', '9876543207', 'police', NULL, NULL, 'Erode', NULL, 'TN09SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 8),
(16, 'SP Kallakurichi', 'sp_kallakurichi@gmail.com', '$2y$10$JSvQm7f/2lO6A2kV5Q7dZ.K4AVTMd.9YOA8YJiLudenm1sMTBkG.a', '9876543208', 'police', NULL, NULL, 'Kallakurichi', NULL, 'TN10SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 9),
(17, 'SP Kanchipuram', 'sp_kanchipuram@gmail.com', '$2y$10$zvqgfh7oI77/bk/va0lOfuW9.oFQSBvETawe6clKU4d8H4ToMHcKi', '9876543209', 'police', NULL, NULL, 'Kanchipuram', NULL, 'TN11SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 10),
(18, 'SP Kanyakumari', 'sp_kanyakumari@gmail.com', '$2y$10$YJDMBiwyGr6nG7Tgu7R71.6dxF1kEQXioRGHlCGMUhnfkZNRPobIa', '9876543210', 'police', NULL, NULL, 'Kanyakumari', NULL, 'TN12SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 11),
(19, 'SP Karur', 'sp_karur@gmail.com', '$2y$10$.vS.WEmKle8hw1OE996imuuPqphQ7t7I8opUzli/548ckx30NW4iW', '9876543211', 'police', NULL, NULL, 'Karur', NULL, 'TN13SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 12),
(20, 'SP Krishnagiri', 'sp_krishnagiri@gmail.com', '$2y$10$A0qYghNsMZfAPTVIFAZRTO538c7jziK/rGmYf1qtYzUtkx.Cq5bjy', '9876543212', 'police', NULL, NULL, 'Krishnagiri', NULL, 'TN14SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 13),
(21, 'SP Madurai', 'sp_madurai@gmail.com', '$2y$10$91zbkv.YbBMfape0RTA3wOfIgBhukmUMnxVCw30joVqudSwpE50e2', '9876543213', 'police', NULL, NULL, 'Madurai', NULL, 'TN15SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 14),
(22, 'SP Mayiladuthurai', 'sp_mayiladuthurai@gmail.com', '$2y$10$CeZKywYzuLgZkjwe0l8hn.60zbcRzgohOWY0Vkjo6Eqxz70chtBGy', '9876543214', 'police', NULL, NULL, 'Mayiladuthurai', NULL, 'TN16SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 15),
(23, 'SP Nagapattinam', 'sp_nagapattinam@gmail.com', '$2y$10$CVasCS29Atm4q9LgQngEzu9wOmOx3jrF1cGMTx2I5JkLT.u8/BHei', '9876543215', 'police', NULL, NULL, 'Nagapattinam', NULL, 'TN17SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 16),
(24, 'SP Namakkal', 'sp_namakkal@gmail.com', '$2y$10$u9mRRzPqZUK2FqsTi94rW.ltUyf7j.Kv0A1eHYFVXPzKD0idL5To.', '9876543216', 'police', NULL, NULL, 'Namakkal', NULL, 'TN18SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 17),
(25, 'SP Nilgiris', 'sp_nilgiris@gmail.com', '$2y$10$IESkkQxKPcvcR1P92XM0V..wIBENdUGgpjPLZfEm8BISbkIo7FhRG', '9876543217', 'police', NULL, NULL, 'Nilgiris', NULL, 'TN19SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 18),
(26, 'SP Perambalur', 'sp_perambalur@gmail.com', '$2y$10$TsSArmrWTf0BUfAMfmmr4uS/2YN3ZTTZ6MGqWeSkphUW1WWL5j.sG', '9876543218', 'police', NULL, NULL, 'Perambalur', NULL, 'TN20SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 19),
(27, 'SP Pudukkottai', 'sp_pudukkottai@gmail.com', '$2y$10$YyGTfZsq6uf9zGGS.hkixeDc4Fdcsn3G3nidShrKIgHuP7tCXxI8O', '9876543219', 'police', NULL, NULL, 'Pudukkottai', NULL, 'TN21SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 20),
(28, 'SP Ramanathapuram', 'sp_ramanathapuram@gmail.com', '$2y$10$KfxrBcUWzbXTa0744M2p.Oj5UwyKwTegWrcrPpxZ2QcHmkImqvJai', '9876543220', 'police', NULL, NULL, 'Ramanathapuram', NULL, 'TN22SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 21),
(29, 'SP Ranipet', 'sp_ranipet@gmail.com', '$2y$10$dqplhZ6Hx2tCtkayrGHbJumLBZlxCHHS/G1ceNnR5HpEyWd5xGxv2', '9876543221', 'police', NULL, NULL, 'Ranipet', NULL, 'TN23SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 22),
(30, 'SP Salem', 'sp_salem@gmail.com', '$2y$10$87LYJIJmjcXrX8LKkTha7e7OMYeJXYLSdUy63EO2qHhHRhNFgXR4m', '9876543222', 'police', NULL, NULL, 'Salem', NULL, 'TN24SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 23),
(31, 'SP Sivaganga', 'sp_sivaganga@gmail.com', '$2y$10$d6MUs3L9zsEohSbgb9oleuyNZb9RpCRQ3o6Lke2RONiAv5ILqUvFu', '9876543223', 'police', NULL, NULL, 'Sivaganga', NULL, 'TN25SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 24),
(32, 'SP Tenkasi', 'sp_tenkasi@gmail.com', '$2y$10$RJBXZy8NVh3yYMWC01DAYuOpoCxRTqqNAmI1s1bQYzFSsqB2VuR6W', '9876543224', 'police', NULL, NULL, 'Tenkasi', NULL, 'TN26SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 25),
(33, 'SP Thanjavur', 'sp_thanjavur@gmail.com', '$2y$10$NuxzNkInoGeun4WH0Io2u./7RT.02TTVyrwyVS8VPufMefvw9Ccc6', '9876543225', 'police', NULL, NULL, 'Thanjavur', NULL, 'TN27SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 26),
(34, 'SP Theni', 'sp_theni@gmail.com', '$2y$10$PDSvk.JzuocHyypHB4B52e21.POC6P.ILUzAPKUJqg96BaICJGWgS', '9876543226', 'police', NULL, NULL, 'Theni', NULL, 'TN28SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 27),
(35, 'SP Thoothukudi', 'sp_thoothukudi@gmail.com', '$2y$10$834lEwkCwKxuAD/ekKnJceVGmZgMkesUulqldE2bGSLm4jeGcFs5O', '9876543227', 'police', NULL, NULL, 'Thoothukudi', NULL, 'TN29SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 28),
(36, 'SP Tiruchirappalli', 'sp_tiruchirappalli@gmail.com', '$2y$10$ClJBB8sUPOxr0mGW.c1WVONBiTiFgOXI8EXxSwTvSRLpOsV0ZGxXO', '9876543228', 'police', NULL, NULL, 'Tiruchirappalli', NULL, 'TN30SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 29),
(37, 'SP Tirunelveli', 'sp_tirunelveli@gmail.com', '$2y$10$ktEqIBxpK1PUdeA.Wb5E1uM.Nw4lDkf8C0mC97tm990Q1wyWhkVK2', '9876543229', 'police', NULL, NULL, 'Tirunelveli', NULL, 'TN31SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 30),
(38, 'SP Tirupathur', 'sp_tirupathur@gmail.com', '$2y$10$YyQ3jpIxuCc4y8R1YUL5e.QyoHVnx1hQbJl7fVBJh9k5/g2pgV8SS', '9876543230', 'police', NULL, NULL, 'Tirupathur', NULL, 'TN32SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 31),
(39, 'SP Tiruppur', 'sp_tiruppur@gmail.com', '$2y$10$7dvhIHKvBK1irJwTSC4M6OHC1X.KB2eDN/gl3xtOGlSANb9YaPuQu', '9876543231', 'police', NULL, NULL, 'Tiruppur', NULL, 'TN33SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 32),
(40, 'SP Tiruvallur', 'sp_tiruvallur@gmail.com', '$2y$10$J2Auj2nlzxcJLfeV1bqu/exVtRSVCGrMgfDFr2VpBXfjSlrTnk8ZG', '9876543232', 'police', NULL, NULL, 'Tiruvallur', NULL, 'TN34SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 33),
(41, 'SP Tiruvannamalai', 'sp_tiruvannamalai@gmail.com', '$2y$10$.eiS4emv7OEdoXwjpMPCiuITRFk2MeVIFXEhUWpsVYh2AnXq5EOd.', '9876543233', 'police', NULL, NULL, 'Tiruvannamalai', NULL, 'TN35SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 34),
(42, 'SP Tiruvarur', 'sp_tiruvarur@gmail.com', '$2y$10$jht/zhwqYNCktcmS5avXR.x1Xljd2cjRMmfat2bOxRPmgh8Z7Nv8.', '9876543234', 'police', NULL, NULL, 'Tiruvarur', NULL, 'TN36SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 35),
(44, 'SP Viluppuram', 'sp_viluppuram@gmail.com', '$2y$10$0UgS0ITAmOTDAnR5ww9aNeN/a.9UX13Zw6iepDSvCZ.B9q.k6gRam', '9876543236', 'police', NULL, NULL, 'Viluppuram', NULL, 'TN38SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 37),
(45, 'SP Virudhunagar', 'sp_virudhunagar@gmail.com', '$2y$10$A1VN6hlsjbaab8NW1I9GQOC1AmTnzaMjzgL8bk2DusZZ655ugSMt.', '9876543237', 'police', NULL, NULL, 'Virudhunagar', NULL, 'TN39SP', 'SP', NULL, 'Active', '2026-01-29 11:35:48', 38),
(47, 'aslam', 'aslam@gmail.com', '$2y$10$rGWaWeFu6rs/MW2PAR0DMuyPDcSmDbOW2ov8c8qJm4vwRNrzufzGO', '6546367673', 'citizen', 'anna nagar', 'Tamil Nadu', 'Chennai', '', NULL, NULL, NULL, 'Active', '2026-01-30 04:25:35', 3),
(50, 'mujahith', 'muja@gmail.com', '$2y$10$elP2HwoFJu/ejET5J22AeuA1s8Sdkc/C8HXxJy13mewlHBvw3bGpW', '7893487973', 'citizen', 'melapalayam', 'Tamil Nadu', 'Tirunelveli', '', NULL, NULL, NULL, 'Active', '2026-01-30 05:28:33', 30),
(51, 'haleeth', 'haleeth@gmail.com', '$2y$10$Ao6BDpzoICHFhJy/lCOEveGqSATqK8HQunb4XVl6JaqWYLz5FPINa', '7654763673', 'citizen', 'melapalayam', 'Tamil Nadu', 'Tirunelveli', '', NULL, NULL, NULL, 'Active', '2026-01-30 10:10:20', 30),
(52, 'abdul', 'abdul@gmail.com', '$2y$10$VQGUPfqJUiQ6OAtCLiUMaOU7qjuR7vjbundr/jQ6VOFFJaSQAZB5a', '1234567898', 'citizen', 'bustand', 'Tamil Nadu', 'Tiruchirappalli', '', NULL, NULL, NULL, 'Active', '2026-01-30 10:59:27', 29),
(53, 'hasan', 'hasan@gmail.com', '$2y$10$qNGmceD/DEnjtGt9cN52LOHHMJ7QsngZkYQi0fi8an3Us.8QkJt8O', '8734673333', 'citizen', 'testtt', 'Tamil Nadu', 'Thanjavur', '', NULL, NULL, NULL, 'Active', '2026-01-30 11:11:40', 26),
(54, 'rahman', 'rahman@gmail.com', '$2y$10$T.HD2Ya4QmZWaHcl6pfU2.u7cVd8WfzYK5wqncIBCFiOqIPfFHvS6', '1234985317', 'citizen', 'tesing', 'Tamil Nadu', 'Coimbatore', '', NULL, NULL, NULL, 'Active', '2026-01-31 10:57:40', 4),
(55, 'usman', 'usman@gmail.com', '$2y$10$yMhVRLKtzKrnrwghWnK4iOTef2hlAExeuCtK3mYPKGFvzIMMSYtlm', '8635689223', 'citizen', 'melapalayam', 'Tamil Nadu', 'Tirunelveli', '', NULL, NULL, NULL, 'Active', '2026-02-01 06:29:09', 30),
(56, 'basha', 'basha@gmail.com', '$2y$10$/VSyOK82BlYCpPgvntw03Opi3kwZMfonCRygglFICTnDRFOY8t4te', '7587673633', 'citizen', 'mount road ', 'Tamil Nadu', 'Pudukkottai', '', NULL, NULL, NULL, 'Active', '2026-02-04 11:39:40', 20),
(57, 'SP Vellore', 'sp_vellore@gmail.com', '$2y$10$nq7M94.nbv9J1Ci0RcjH9OkMHmpu9tVIP9I0pVLZtxu.3/6moGKxG', NULL, 'police', NULL, NULL, 'Vellore', NULL, 'TN37SP', 'SP', NULL, 'Active', '2026-02-04 16:06:21', 36),
(58, 'raheem', 'raheem@gmail.com', '$2y$10$fKxM0dwO9cwfE41gS3OntOJoBmb4flHyC2CEMZkOpP8ft28AMdwR2', '7365879833', 'citizen', 'mount road ', 'Tamil Nadu', 'Salem', '', NULL, NULL, NULL, 'Active', '2026-02-04 16:55:44', 23),
(59, 'riswan', 'riswan@gmail.com', '$2y$10$Ul5stAm7Wp2R7qMF46Q3/uJy6MS8eBE7ZjEl3pxLXOBTe7Jgsz.di', '6532103423', 'citizen', 'near bustand', 'Tamil Nadu', 'Vellore', '', NULL, NULL, NULL, 'Active', '2026-02-05 17:05:10', 36);
