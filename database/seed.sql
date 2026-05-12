-- Seeding Data for Unified Crime Reporting System
-- Default Password: 'password' (for general accounts)
-- Admin Password: 'admin123' ($2y$10$7z.SjA8yqjGQ.8/n6y0nOe9YlE/F9L2P2XjXF7L0jKzX0N3/z8Vp.)

USE `crime_reporting_db`;

START TRANSACTION;

-- 1. Insert Districts (Tamil Nadu - 38 Districts)
INSERT INTO `districts` (`id`, `name`) VALUES
(1, 'Ariyalur'),
(2, 'Chengalpattu'),
(3, 'Chennai'),
(4, 'Coimbatore'),
(5, 'Cuddalore'),
(6, 'Dharmapuri'),
(7, 'Dindigul'),
(8, 'Erode'),
(9, 'Kallakurichi'),
(10, 'Kanchipuram'),
(11, 'Kanyakumari'),
(12, 'Karur'),
(13, 'Krishnagiri'),
(14, 'Madurai'),
(15, 'Mayiladuthurai'),
(16, 'Nagapattinam'),
(17, 'Namakkal'),
(18, 'Nilgiris'),
(19, 'Perambalur'),
(20, 'Pudukkottai'),
(21, 'Ramanathapuram'),
(22, 'Ranipet'),
(23, 'Salem'),
(24, 'Sivaganga'),
(25, 'Tenkasi'),
(26, 'Thanjavur'),
(27, 'Theni'),
(28, 'Thoothukudi'),
(29, 'Tiruchirappalli'),
(30, 'Tirunelveli'),
(31, 'Tirupathur'),
(32, 'Tiruppur'),
(33, 'Tiruvallur'),
(34, 'Tiruvannamalai'),
(35, 'Tiruvarur'),
(36, 'Vellore'),
(37, 'Viluppuram'),
(38, 'Virudhunagar')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 2. Insert Users (Admins, Police, Citizens)
INSERT INTO `users` 
(`id`, `full_name`, `email`, `password`, `mobile`, `role`, `address`, `state`, `district`, `pincode`, `rank`, `specialization`) 
VALUES
-- Admin
(1, 'Super Admin', 'admincrs@gmail.com', '$2y$10$NTocvZzg9TS7sdMaMU..ee5zGQSJEsRfbR1AjD9TAuIlh7oENxd66', '9999999999', 'admin', 'HQ, New Delhi', 'Delhi', 'New Delhi', '110001', NULL, NULL),

-- Registered Citizens
(47, 'aslam', 'aslam@gmail.com', '$2y$10$rGWaWeFu6rs/MW2PAR0DMuyPDcSmDbOW2ov8c8qJm4vwRNrzufzGO', '6546367673', 'citizen', 'anna nagar', 'Tamil Nadu', 'Chennai', '', NULL, NULL),
(50, 'mujahith', 'muja@gmail.com', '$2y$10$elP2HwoFJu/ejET5J22AeuA1s8Sdkc/C8HXxJy13mewlHBvw3bGpW', '7893487973', 'citizen', 'melapalayam', 'Tamil Nadu', 'Tirunelveli', '', NULL, NULL),
(51, 'haleeth', 'haleeth@gmail.com', '$2y$10$Ao6BDpzoICHFhJy/lCOEveGqSATqK8HQunb4XVl6JaqWYLz5FPINa', '7654763673', 'citizen', 'melapalayam', 'Tamil Nadu', 'Tirunelveli', '', NULL, NULL),
(52, 'abdul', 'abdul@gmail.com', '$2y$10$VQGUPfqJUiQ6OAtCLiUMaOU7qjuR7vjbundr/jQ6VOFFJaSQAZB5a', '1234567898', 'citizen', 'bustand', 'Tamil Nadu', 'Tiruchirappalli', '', NULL, NULL),
(53, 'hasan', 'hasan@gmail.com', '$2y$10$qNGmceD/DEnjtGt9cN52LOHHMJ7QsngZkYQi0fi8an3Us.8QkJt8O', '8734673333', 'citizen', 'testtt', 'Tamil Nadu', 'Thanjavur', '', NULL, NULL),
(54, 'rahman', 'rahman@gmail.com', '$2y$10$T.HD2Ya4QmZWaHcl6pfU2.u7cVd8WfzYK5wqncIBCFiOqIPfFHvS6', '1234985317', 'citizen', 'tesing', 'Tamil Nadu', 'Coimbatore', '', NULL, NULL),
(55, 'usman', 'usman@gmail.com', '$2y$10$yMhVRLKtzKrnrwghWnK4iOTef2hlAExeuCtK3mYPKGFvzIMMSYtlm', '8635689223', 'citizen', 'melapalayam', 'Tamil Nadu', 'Tirunelveli', '', NULL, NULL),
(56, 'basha', 'basha@gmail.com', '$2y$10$/VSyOK82BlYCpPgvntw03Opi3kwZMfonCRygglFICTnDRFOY8t4te', '7587673633', 'citizen', 'mount road ', 'Tamil Nadu', 'Pudukkottai', '', NULL, NULL),
(58, 'raheem', 'raheem@gmail.com', '$2y$10$fKxM0dwO9cwfE41gS3OntOJoBmb4flHyC2CEMZkOpP8ft28AMdwR2', '7365879833', 'citizen', 'mount road ', 'Tamil Nadu', 'Salem', '', NULL, NULL),
(59, 'riswan', 'riswan@gmail.com', '$2y$10$Ul5stAm7Wp2R7qMF46Q3/uJy6MS8eBE7ZjEl3pxLXOBTe7Jgsz.di', '6532103423', 'citizen', 'near bustand', 'Tamil Nadu', 'Vellore', '', NULL, NULL);

-- 3. Insert Crime Reports
INSERT INTO `crimes` 
(`user_id`, `crime_type`, `description`, `incident_date`, `landmark`, `area`, `district`, `state`, `status`, `assigned_to`) 
VALUES
(4, 'Theft', 'Bike stolen from marketplace', '2025-10-15 14:30:00', 'Near Mall', 'Andheri West', 'Mumbai', 'Maharashtra', 'Pending', NULL),
(5, 'Cybercrime', 'Bank fraud call', '2025-10-20 10:15:00', 'Home', 'Lajpat Nagar', 'New Delhi', 'Delhi', 'In Investigation', 3);

-- 4. Insert Evidence (Mock)
-- (No specialized table needed for now, handled via logic)

-- 5. Case Updates
INSERT INTO `crime_updates` (`crime_id`, `status_from`, `status_to`, `remarks`, `updated_by`) VALUES
(2, 'Pending', 'In Investigation', 'Initial probe started', 3);

COMMIT;
