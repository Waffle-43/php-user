-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2025 at 04:35 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `salon_spa`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `stylist_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `duration` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_reschedule_history`
--

CREATE TABLE `appointment_reschedule_history` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `original_date` date NOT NULL,
  `original_time` time NOT NULL,
  `new_date` date NOT NULL,
  `new_time` time NOT NULL,
  `rescheduled_by` enum('customer','provider') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `profile_image`, `created_at`) VALUES
(1, 'Jessica Thompson', 'jessica@example.com', '123-456-7890', 'https://randomuser.me/api/portraits/women/12.jpg', '2025-05-09 01:37:35'),
(2, 'Michael Rodriguez', 'michael@example.com', '234-567-8901', 'https://randomuser.me/api/portraits/men/45.jpg', '2025-05-09 01:37:35'),
(3, 'Amanda Smith', 'amanda@example.com', '345-678-9012', 'https://randomuser.me/api/portraits/women/28.jpg', '2025-05-09 01:37:35');

-- --------------------------------------------------------

--
-- Table structure for table `customer_roles`
--

CREATE TABLE `customer_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'manage_system_settings', 'Manage overall system settings', '2025-05-13 02:30:38', '2025-05-13 02:30:38'),
(2, 'manage_roles_permissions', 'Manage roles and permissions', '2025-05-13 02:30:38', '2025-05-13 02:30:38'),
(3, 'manage_all_users', 'Manage all user accounts (customers and staff)', '2025-05-13 02:30:38', '2025-05-13 02:30:38'),
(4, 'manage_appointments', 'Manage appointments (create, edit, cancel for any customer)', '2025-05-13 02:30:38', '2025-05-13 02:30:38'),
(5, 'view_staff_dashboard', 'Access the staff dashboard', '2025-05-13 02:30:38', '2025-05-13 02:30:38'),
(6, 'manage_services', 'Manage available services', '2025-05-13 02:30:38', '2025-05-13 02:30:38'),
(7, 'manage_own_availability', 'Manage own working hours/availability', '2025-05-13 02:30:38', '2025-05-13 02:30:38'),
(8, 'book_appointments', 'Book new appointments', '2025-05-13 02:30:38', '2025-05-13 02:30:38'),
(9, 'view_own_appointments', 'View own past and upcoming appointments', '2025-05-13 02:30:38', '2025-05-13 02:30:38'),
(10, 'cancel_own_appointments', 'Cancel own appointments', '2025-05-13 02:30:38', '2025-05-13 02:30:38'),
(11, 'update_own_profile', 'Update own user profile', '2025-05-13 02:30:38', '2025-05-13 02:30:38');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `stylist_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `customer_id`, `service_id`, `stylist_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 1, 1, 5, 'Emma transformed my hair! I\'ve never received so many compliments. The online booking was so convenient and the salon atmosphere was relaxing.', '2025-05-06 19:37:35'),
(2, 2, 2, 2, 5, 'David gives the best haircut I\'ve ever had. The online system made it easy to book exactly when I wanted. Will definitely be coming back regularly!', '2025-05-01 19:37:35'),
(3, 3, 3, 3, 5, 'Sophia\'s facial treatments have completely transformed my skin. The ability to book online and see her availability in real-time is a game changer.', '2025-04-17 19:37:35');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `permissions` text NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `permissions`, `description`) VALUES
(1, 'admin', '[\"PERMISSION_MANAGE_APPOINTMENTS\",\"PERMISSION_MANAGE_SERVICES\",\"PERMISSION_MANAGE_USERS\",\"PERMISSION_MANAGE_STAFF\",\"PERMISSION_VIEW_REPORTS\",\"PERMISSION_MANAGE_SETTINGS\"]', 'Administrator with full access'),
(2, 'staff', '[\"PERMISSION_MANAGE_APPOINTMENTS\", \"PERMISSION_MANAGE_USERS\"]', 'Regular staff member'),
(3, 'stylists', '[\"PERMISSION_MANAGE_APPOINTMENTS\"]', 'Spa stylist'),
(4, 'Customer', '', 'Customer with access to booking and personal appointments');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES
(1, 1, '2025-05-13 02:30:38'),
(1, 2, '2025-05-13 02:30:38'),
(1, 3, '2025-05-13 02:30:38'),
(1, 4, '2025-05-13 02:30:38'),
(1, 5, '2025-05-13 02:30:38'),
(1, 6, '2025-05-13 02:30:38'),
(1, 7, '2025-05-13 02:30:38'),
(1, 8, '2025-05-13 02:30:38'),
(1, 9, '2025-05-13 02:30:38'),
(1, 10, '2025-05-13 02:30:38'),
(1, 11, '2025-05-13 02:30:38'),
(2, 4, '2025-05-13 02:30:38'),
(2, 5, '2025-05-13 02:30:38'),
(2, 6, '2025-05-13 02:30:38'),
(2, 7, '2025-05-13 02:30:38'),
(2, 11, '2025-05-13 02:30:38'),
(4, 8, '2025-05-13 02:30:38'),
(4, 9, '2025-05-13 02:30:38'),
(4, 10, '2025-05-13 02:30:38'),
(4, 11, '2025-05-13 02:30:38');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` int(11) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `available` tinyint(1) DEFAULT 1,
  `image` blob DEFAULT NULL,
  `promotion` int(11) DEFAULT 0,
  `price_after_discount` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `price`, `duration`, `location`, `category`, `available`, `image`, `promotion`, `price_after_discount`, `is_active`, `created_at`) VALUES
(1, 'Haircut & Styling', 'Professional haircut and styling service', 65.00, 60, NULL, NULL, 1, NULL, 0, NULL, 1, '2025-05-09 01:17:26'),
(2, 'Hair Coloring', 'Full hair coloring service with consultation', 120.00, 120, NULL, NULL, 1, NULL, 0, NULL, 1, '2025-05-09 01:17:26'),
(3, 'Manicure', 'Basic manicure service', 35.00, 45, NULL, NULL, 1, NULL, 0, NULL, 1, '2025-05-09 01:17:26'),
(4, 'Pedicure', 'Basic pedicure service', 45.00, 60, NULL, NULL, 1, NULL, 0, NULL, 1, '2025-05-09 01:17:26'),
(5, 'Facial', 'Deep cleansing facial treatment', 80.00, 90, NULL, NULL, 1, NULL, 0, NULL, 1, '2025-05-09 01:17:26');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `role` enum('admin','manager','staff','user') DEFAULT 'staff',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `permissions` text DEFAULT NULL COMMENT 'JSON encoded permissions'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `username`, `email`, `password`, `status`, `role`, `created_at`, `updated_at`, `last_login`, `permissions`) VALUES
(7, 'Admin User', 'adminuser', 'mrvin@gmail.com', '$2y$10$TSvVUwVNlk4Y0wg67iIAT.cHAI..hTJKFT/ZlxQFRR6.WmANwGdm.', 'approved', 'admin', '2025-05-08 19:57:26', NULL, NULL, NULL),
(8, 'Alex Admin', 'alexadmin', 'alex@gmail.com', '$2y$10$qw/Aj3rDej/bSEfAYezxNuB8gwh1qbwFi07ximSBeblti7oufZs.2', 'approved', 'staff', '2025-05-09 05:00:50', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff_roles`
--

CREATE TABLE `staff_roles` (
  `staff_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stylists`
--

CREATE TABLE `stylists` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stylists`
--

INSERT INTO `stylists` (`id`, `name`, `specialization`, `bio`, `profile_image`, `rating`, `is_active`, `created_at`) VALUES
(1, 'Jessica Parker', 'Hair Styling', 'Expert in modern haircuts and coloring techniques', NULL, 4.80, 1, '2025-05-09 01:17:26'),
(2, 'Michael Chen', 'Nail Care', 'Specialized in nail art and treatments', NULL, 4.90, 1, '2025-05-09 01:17:26'),
(3, 'Sarah Johnson', 'Facial Treatments', 'Certified esthetician with 5 years of experience', NULL, 4.70, 1, '2025-05-09 01:17:26'),
(4, 'David Wilson', 'Hair Coloring', 'Master colorist specializing in balayage and highlights', NULL, 4.90, 1, '2025-05-09 01:17:26');

-- --------------------------------------------------------

--
-- Table structure for table `stylist_schedule`
--

CREATE TABLE `stylist_schedule` (
  `id` int(11) NOT NULL,
  `stylist_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(50) NOT NULL,
  `roles` enum('admin','manager','staff','user') DEFAULT NULL,
  `verification_code` int(11) DEFAULT NULL,
  `verification_status` enum('pending','verified') DEFAULT 'pending',
  `verification_requested_at` timestamp NULL DEFAULT NULL,
  `request_attempts` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `profile_picture`, `roles`, `verification_code`, `verification_status`, `verification_requested_at`, `request_attempts`, `created_at`) VALUES
(6, 'Marvin', 'marvin', 'marvinw@gmail.com', '$2y$10$D5XvnHm2Pi34D2x2fmro8uSlNV0TiUv.ZJwy/JyFvcw113csZbZDu', './uploadsprofile.png', NULL, NULL, 'verified', '2025-05-08 10:45:12', 0, '2025-04-20 13:42:20'),
(12, 'John', 'Staffhs01', 'staffhs@gmail.com', '$2y$10$YMri8c7Zu6TNwoZdgOIzOeSKeRFD7BUkZ7RTWQ6CJj3MuNJ1vZqpi', '', NULL, NULL, 'verified', NULL, 0, '2025-05-07 04:18:54'),
(13, 'testingal', 'tester', 'test@gmail.com', '$2y$10$cdfR9IVOLmDJCsszF7WY7uXLd.BURFxFD0DEGKXnxhXKIqgkUVNau', '', NULL, NULL, 'verified', NULL, 0, '2025-05-09 04:19:13'),
(14, 'marvin wong', 'example', 'marvintest@gmail.com', '$2y$10$7NOJ5rsUUFnQPMX9FGZ7WewQfpcXJNZ5Z0ElDbmQXnbxNsX0h3VwC', './uploadsprofile.png', NULL, NULL, 'verified', NULL, 0, '2025-05-09 07:52:46');

-- --------------------------------------------------------

--
-- Table structure for table `waitlist`
--

CREATE TABLE `waitlist` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `preferred_stylist_id` int(11) DEFAULT NULL,
  `service_date` date NOT NULL,
  `preferred_time_start` time DEFAULT NULL,
  `preferred_time_end` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','contacted','booked','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `stylist_id` (`stylist_id`);

--
-- Indexes for table `appointment_reschedule_history`
--
ALTER TABLE `appointment_reschedule_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_roles`
--
ALTER TABLE `customer_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `stylist_id` (`stylist_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `staff_roles`
--
ALTER TABLE `staff_roles`
  ADD PRIMARY KEY (`staff_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `stylists`
--
ALTER TABLE `stylists`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stylist_schedule`
--
ALTER TABLE `stylist_schedule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule` (`stylist_id`,`day_of_week`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `waitlist`
--
ALTER TABLE `waitlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `preferred_stylist_id` (`preferred_stylist_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointment_reschedule_history`
--
ALTER TABLE `appointment_reschedule_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `stylists`
--
ALTER TABLE `stylists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stylist_schedule`
--
ALTER TABLE `stylist_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `waitlist`
--
ALTER TABLE `waitlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`stylist_id`) REFERENCES `stylists` (`id`);

--
-- Constraints for table `appointment_reschedule_history`
--
ALTER TABLE `appointment_reschedule_history`
  ADD CONSTRAINT `appointment_reschedule_history_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`);

--
-- Constraints for table `customer_roles`
--
ALTER TABLE `customer_roles`
  ADD CONSTRAINT `customer_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`stylist_id`) REFERENCES `stylists` (`id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_roles`
--
ALTER TABLE `staff_roles`
  ADD CONSTRAINT `staff_roles_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stylist_schedule`
--
ALTER TABLE `stylist_schedule`
  ADD CONSTRAINT `stylist_schedule_ibfk_1` FOREIGN KEY (`stylist_id`) REFERENCES `stylists` (`id`);

--
-- Constraints for table `waitlist`
--
ALTER TABLE `waitlist`
  ADD CONSTRAINT `waitlist_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `waitlist_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `waitlist_ibfk_3` FOREIGN KEY (`preferred_stylist_id`) REFERENCES `stylists` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
