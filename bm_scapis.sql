-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 25, 2025 at 10:21 AM
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
-- Database: `bm_scapis`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_affected` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_affected`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(77, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:03:31'),
(78, 22, 'User registered', 'users', 22, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:14:46'),
(79, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:16:01'),
(80, 1, 'process_registration_disapprove', 'users', 22, '{\"status\":\"pending\"}', '{\"status\":\"disapproved\",\"remarks\":\"adsdad\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:17:32'),
(81, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:21:14'),
(82, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:21:21'),
(83, 5, 'process_registration_disapprove', 'users', 21, '{\"status\":\"pending\"}', '{\"status\":\"disapproved\",\"remarks\":\"asdada\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:21:32'),
(93, 5, 'disapprove_registration', 'users', 20, '{\"user_id\":\"20\",\"disapproved_by\":5,\"disapproved_by_name\":\"Jan Russel Pe\\u00f1afiel\",\"remarks\":\"Not a resident of this purok\",\"disapproved_at\":\"2025-07-24 17:44:42\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:44:42'),
(94, 5, 'disapprove_registration', 'users', 19, '{\"user_id\":\"19\",\"disapproved_by\":5,\"disapproved_by_name\":\"Jan Russel Pe\\u00f1afiel\",\"remarks\":\"Invalid documentation provided\",\"disapproved_at\":\"2025-07-24 17:46:17\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-24 09:46:17'),
(95, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 05:34:39'),
(96, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 07:07:22'),
(97, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 07:07:36'),
(98, 1, 'disapprove_registration', 'users', 21, '{\"user_id\":\"21\",\"disapproved_by\":1,\"disapproved_by_name\":\"System Administrator\",\"remarks\":\"Not a resident of this purok\",\"disapproved_at\":\"2025-07-25 15:08:09\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 07:08:09');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `application_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `purpose` text NOT NULL,
  `urgency` enum('Regular','Rush') DEFAULT 'Regular',
  `status` enum('pending','processing','ready_for_pickup','completed','rejected') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','waived') DEFAULT 'unpaid',
  `payment_amount` decimal(8,2) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `pickup_date` timestamp NULL DEFAULT NULL,
  `appointment_date` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `priority_level` int(11) DEFAULT 1,
  `supporting_documents` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_history`
--

CREATE TABLE `application_history` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `remarks` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `appointment_type` enum('verification','pickup','interview') NOT NULL,
  `appointment_date` datetime NOT NULL,
  `status` enum('scheduled','completed','cancelled','rescheduled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `fee` decimal(8,2) DEFAULT 0.00,
  `requirements` text DEFAULT NULL,
  `processing_days` int(11) DEFAULT 3,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`id`, `type_name`, `description`, `fee`, `requirements`, `processing_days`, `is_active`, `created_at`) VALUES
(1, 'Barangay Clearance', 'Certificate of Barangay Clearance for various purposes', 50.00, 'Valid ID, Cedula, Residence Certificate', 3, 1, '2025-07-24 02:40:48'),
(2, 'Certificate of Residency', 'Proof of residency in Barangay Malangit', 30.00, 'Valid ID, Proof of Address', 2, 1, '2025-07-24 02:40:48'),
(3, 'Certificate of Indigency', 'Certificate for low-income residents', 25.00, 'Valid ID, Income Statement, Barangay ID', 3, 1, '2025-07-24 02:40:48'),
(4, 'Business Permit', 'Permit for small business operations', 200.00, 'Valid ID, Business Registration, Location Map', 5, 1, '2025-07-24 02:40:48'),
(5, 'Building Permit', 'Permit for construction/renovation', 500.00, 'Valid ID, Construction Plans, Lot Title', 7, 1, '2025-07-24 02:40:48'),
(6, 'Certificate of Good Moral', 'Character certificate', 40.00, 'Valid ID, Character References', 3, 1, '2025-07-24 02:40:48');

-- --------------------------------------------------------

--
-- Table structure for table `puroks`
--

CREATE TABLE `puroks` (
  `id` int(11) NOT NULL,
  `purok_name` varchar(100) NOT NULL,
  `purok_leader_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `puroks`
--

INSERT INTO `puroks` (`id`, `purok_name`, `purok_leader_id`, `created_at`) VALUES
(1, 'Purok 1', 5, '2025-07-24 02:40:48'),
(2, 'Purok 2', NULL, '2025-07-24 02:40:48'),
(4, 'Purok 4', NULL, '2025-07-24 02:40:48'),
(5, 'Purok 5', NULL, '2025-07-24 02:40:48'),
(6, 'Purok 6', NULL, '2025-07-24 02:40:48'),
(7, 'Purok 7', NULL, '2025-07-24 02:40:48'),
(8, 'Purok 8', NULL, '2025-07-24 02:40:48'),
(9, 'Purok 9', NULL, '2025-07-24 02:40:48'),
(11, 'Purok 1', NULL, '2025-07-24 08:32:01'),
(12, 'Purok 2', NULL, '2025-07-24 08:32:01'),
(13, 'Purok 3', NULL, '2025-07-24 08:32:01'),
(14, 'Purok 4', NULL, '2025-07-24 08:32:01'),
(15, 'Purok 5', NULL, '2025-07-24 08:32:01');

-- --------------------------------------------------------

--
-- Table structure for table `reports_cache`
--

CREATE TABLE `reports_cache` (
  `id` int(11) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `generated_by` int(11) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_notifications`
--

CREATE TABLE `sms_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `api_response` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_notifications`
--

INSERT INTO `sms_notifications` (`id`, `user_id`, `phone_number`, `message`, `status`, `api_response`, `sent_at`, `created_at`) VALUES
(10, 22, '639677726912', 'Welcome to BM-SCaPIS! Your account has been created successfully.\nUsername: janpe11588\nPassword: bmscapis2025\n\nYour registration is pending approval from your Purok Leader and Admin.', 'failed', '<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\">\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"ie=edge\">\r\n  <link href=\"https://fonts.googleapis.com/css?family=Raleway:500,800\" rel=\"stylesheet\">\r\n  <title>PhilSMS</title>\r\n  <style>\r\n  * {\r\n  margin:0;\r\n  padding: 0;\r\n}\r\nbody{\r\n  background: #233142;\r\n  \r\n}\r\n.whistle{\r\n  width: 20%;\r\n  fill: #f95959;\r\n  margin: 100px 40%;\r\n  text-align: left;\r\n  transform: translate(-50%, -50%);\r\n  transform: rotate(0);\r\n  transform-origin: 80% 30%;\r\n  animation: wiggle .2s infinite;\r\n}\r\n\r\n@keyframes wiggle {\r\n  0%{\r\n    transform: rotate(3deg);\r\n  }\r\n  50%{\r\n    transform: rotate(0deg);\r\n  }\r\n  100%{\r\n    transform: rotate(3deg);\r\n  }\r\n}\r\nh1{\r\n  margin-top: -100px;\r\n  margin-bottom: 20px;\r\n  color: #facf5a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 90px;\r\n  font-weight: 800;\r\n}\r\nh2{\r\n  color: #455d7a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 30px;\r\n  text-transform: uppercase;\r\n}\r\n</style>\r\n</head>\r\n<body>\r\n  <use>\r\n  <svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\" class=\"whistle\">\r\n<metadata> Svg Vector Icons : http://www.onlinewebfonts.com/icon </metadata>\r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\">\r\n<path d=\"M4295.8,3963.2c-113-57.4-122.5-107.2-116.8-622.3l5.7-461.4l63.2-55.5c72.8-65.1,178.1-74.7,250.8-24.9c86.2,61.3,97.6,128.3,97.6,584c0,474.8-11.5,526.5-124.5,580.1C4393.4,4001.5,4372.4,4001.5,4295.8,3963.2z\"/><path d=\"M3053.1,3134.2c-68.9-42.1-111-143.6-93.8-216.4c7.7-26.8,216.4-250.8,476.8-509.3c417.4-417.4,469.1-463.4,526.5-463.4c128.3,0,212.5,88.1,212.5,224c0,67-26.8,97.6-434.6,509.3c-241.2,241.2-459.5,449.9-488.2,465.3C3181.4,3180.1,3124,3178.2,3053.1,3134.2z\"/><path d=\"M2653,1529.7C1644,1445.4,765.1,850,345.8-32.7C62.4-628.2,22.2-1317.4,234.8-1960.8C451.1-2621.3,947-3186.2,1584.6-3500.2c1018.6-501.6,2228.7-296.8,3040.5,515.1c317.8,317.8,561,723.7,670.1,1120.1c101.5,369.5,158.9,455.7,360,553.3c114.9,57.4,170.4,65.1,1487.7,229.8c752.5,93.8,1392,181.9,1420.7,193.4C8628.7-857.9,9900,1250.1,9900,1328.6c0,84.3-67,172.3-147.4,195.3c-51.7,15.3-790.8,19.1-2558,15.3l-2487.2-5.7l-55.5-63.2l-55.5-61.3v-344.6V719.8h-411.7h-411.7v325.5c0,509.3,11.5,499.7-616.5,494C2921,1537.3,2695.1,1533.5,2653,1529.7z\"/></g></g>\r\n</svg>\r\n</use>\r\n<h1>404</h1>\r\n<h2>not found</h2>\r\n</body>\r\n</html>\r\n', NULL, '2025-07-24 09:14:46'),
(11, 22, '639677726912', 'Hello Jan Russelss,\n\nYour registration at Barangay Malangit has been disapproved.\nReason: adsdad\n\nPlease contact the barangay office for more information.', 'pending', NULL, NULL, '2025-07-24 09:17:32');

-- --------------------------------------------------------

--
-- Table structure for table `system_config`
--

CREATE TABLE `system_config` (
  `id` int(11) NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_config`
--

INSERT INTO `system_config` (`id`, `config_key`, `config_value`, `created_at`, `updated_at`) VALUES
(1, 'philsms_api_key', '2100|J9BVGEx9FFOJAbHV0xfn6SMOkKBt80HTLjHb6zZX', '2025-07-24 02:40:48', '2025-07-24 02:41:34'),
(2, 'philsms_sender_name', 'PhilSMS', '2025-07-24 02:40:48', '2025-07-24 02:41:42'),
(3, 'system_name', 'BM-SCaPIS', '2025-07-24 02:40:48', '2025-07-24 02:40:48'),
(4, 'barangay_name', 'Barangay Malangit', '2025-07-24 02:40:48', '2025-07-24 02:40:48'),
(5, 'ringtone_enabled', '1', '2025-07-24 02:40:48', '2025-07-24 02:40:48');

-- --------------------------------------------------------

--
-- Table structure for table `system_notifications`
--

CREATE TABLE `system_notifications` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `target_role` enum('admin','purok_leader','all') NOT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_notifications`
--

INSERT INTO `system_notifications` (`id`, `type`, `title`, `message`, `target_role`, `target_user_id`, `is_read`, `metadata`, `created_at`, `read_at`) VALUES
(43, 'new_registration', 'New Resident Registration', 'New registration from Jan Russel Peñafiel', 'admin', NULL, 0, '{\"user_id\": 19, \"purok_id\": 1}', '2025-07-24 09:04:07', NULL),
(44, 'new_registration', 'New Resident Registration in Your Purok', 'New registration from Jan Russel Peñafiel in your purok', 'purok_leader', NULL, 0, '{\"user_id\": 19, \"purok_id\": 1}', '2025-07-24 09:04:07', NULL),
(45, 'new_registration', 'New Resident Registration', 'New registration from Jan Russel Peñafiel', 'admin', NULL, 0, '{\"user_id\": 20, \"purok_id\": 1}', '2025-07-24 09:05:27', NULL),
(46, 'new_registration', 'New Resident Registration in Your Purok', 'New registration from Jan Russel Peñafiel in your purok', 'purok_leader', NULL, 0, '{\"user_id\": 20, \"purok_id\": 1}', '2025-07-24 09:05:27', NULL),
(47, 'new_registration', 'New Resident Registration', 'New registration from Jan Russel Peñafiel', 'admin', NULL, 0, '{\"user_id\": 21, \"purok_id\": 1}', '2025-07-24 09:05:38', NULL),
(48, 'new_registration', 'New Resident Registration in Your Purok', 'New registration from Jan Russel Peñafiel in your purok', 'purok_leader', NULL, 0, '{\"user_id\": 21, \"purok_id\": 1}', '2025-07-24 09:05:38', NULL),
(49, 'new_registration', 'New Resident Registration', 'New registration from Jan Russelss Peñafielsss', 'admin', NULL, 0, '{\"user_id\": 22, \"purok_id\": 11}', '2025-07-24 09:14:46', NULL),
(50, 'new_registration', 'New Resident Registration in Your Purok', 'New registration from Jan Russelss Peñafielsss in your purok', 'purok_leader', NULL, 0, '{\"user_id\": 22, \"purok_id\": 11}', '2025-07-24 09:14:46', NULL),
(51, 'registration_disapproved', 'Registration Disapproved', 'Your registration has been disapproved. Reason: adsdad', 'admin', 22, 0, NULL, '2025-07-24 09:17:32', NULL),
(52, 'registration_processed', 'Purok Leader Disapproved Registration', 'Purok leader Jan Russel Peñafiel has disapproved the registration of Jan Russel Peñafiel', 'admin', NULL, 0, '{\"user_id\":21,\"purok_id\":1,\"action\":\"disapprove\"}', '2025-07-24 09:21:32', NULL),
(62, 'registration_disapproved', 'Registration Disapproved', 'Registration for Jan Russel Peñafiel has been disapproved by Purok Leader (Jan Russel Peñafiel)', 'admin', NULL, 0, '{\"user_id\":\"20\",\"disapproved_by\":5,\"disapproved_at\":\"2025-07-24 17:44:42\",\"remarks\":\"Not a resident of this purok\"}', '2025-07-24 09:44:42', NULL),
(63, 'registration_disapproved', 'Registration Disapproved', 'Registration for Jan Russel Peñafiel has been disapproved by Purok Leader (Jan Russel Peñafiel)', 'admin', NULL, 0, '{\"user_id\":\"19\",\"disapproved_by\":5,\"disapproved_at\":\"2025-07-24 17:46:17\",\"remarks\":\"Invalid documentation provided\"}', '2025-07-24 09:46:17', NULL),
(64, 'registration_disapproved', 'Registration Disapproved', 'Registration for Jan Russel Peñafiel has been disapproved by Admin (System Administrator)', 'admin', NULL, 0, '{\"user_id\":\"21\",\"disapproved_by\":1,\"disapproved_at\":\"2025-07-25 15:08:09\",\"remarks\":\"Not a resident of this purok\"}', '2025-07-25 07:08:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('resident','purok_leader','admin') NOT NULL,
  `status` enum('pending','approved','disapproved') DEFAULT 'pending',
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `civil_status` enum('Single','Married','Divorced','Widowed') NOT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `purok_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `monthly_income` decimal(10,2) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(15) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `valid_id_front` varchar(255) DEFAULT NULL,
  `valid_id_back` varchar(255) DEFAULT NULL,
  `purok_leader_approval` enum('pending','approved','disapproved') DEFAULT 'pending',
  `admin_approval` enum('pending','approved','disapproved') DEFAULT 'pending',
  `purok_leader_remarks` text DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `approved_by_purok_leader` int(11) DEFAULT NULL,
  `approved_by_admin` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `sms_notifications` tinyint(1) DEFAULT 1,
  `email_notifications` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `status`, `first_name`, `middle_name`, `last_name`, `suffix`, `birthdate`, `age`, `gender`, `civil_status`, `contact_number`, `email`, `purok_id`, `address`, `occupation`, `monthly_income`, `emergency_contact_name`, `emergency_contact_number`, `profile_picture`, `valid_id_front`, `valid_id_back`, `purok_leader_approval`, `admin_approval`, `purok_leader_remarks`, `admin_remarks`, `approved_by_purok_leader`, `approved_by_admin`, `approved_at`, `sms_notifications`, `email_notifications`, `created_at`, `updated_at`) VALUES
(1, 'admin001', 'admin123', 'admin', 'approved', 'System', NULL, 'Administrator', NULL, NULL, NULL, 'Male', 'Single', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 'approved', NULL, NULL, NULL, NULL, NULL, 1, 1, '2025-07-24 02:40:48', '2025-07-24 02:40:48'),
(5, 'jpeafiel_p5', '3d881bf7', 'purok_leader', 'approved', 'Jan Russel', 'asdadgd', 'Peñafiel', '', '2003-01-20', 22, 'Female', 'Widowed', '09677726912', 'artiedastephany@gmail.com', 1, 'Panay Santo Niño South Cotabato', 'asda', 2000.00, 'Jan Russel asdadgd Peñafiel', '09677726912', NULL, NULL, NULL, 'approved', 'approved', NULL, NULL, NULL, 1, '2025-07-24 06:52:15', 1, 1, '2025-07-24 06:52:15', '2025-07-24 07:15:09'),
(22, 'janpe11588', '$2y$10$GzHv5R2pFuYz2dKZetadGe9xSnlh3OsBXWJ.iRtNLyX5gvSKSEiZu', 'resident', 'disapproved', 'Jan Russelss', 'asdadgdsssss', 'Peñafielsss', '', '2002-02-02', 23, 'Male', 'Single', '639677726912', 'penafielliezl11221@gmail.com', 11, 'Panay Santo Niño South Cotabato', 'asda', 200200.00, 'Jan Russel asdadgd Peñafiel', '639677726912', '6881f986085163.48251949.png', '6881f986089858.38790689.png', '6881f98608e068.94622918.png', 'pending', 'disapproved', NULL, 'adsdad', NULL, 1, NULL, 1, 1, '2025-07-24 09:14:46', '2025-07-24 09:17:32');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `tr_new_registration_notification` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    IF NEW.role = 'resident' THEN
        -- Notify admin
        INSERT INTO system_notifications (type, title, message, target_role, metadata)
        VALUES (
            'new_registration',
            'New Resident Registration',
            CONCAT('New registration from ', NEW.first_name, ' ', NEW.last_name),
            'admin',
            JSON_OBJECT('user_id', NEW.id, 'purok_id', NEW.purok_id)
        );
        
        -- Notify purok leader if purok is assigned
        IF NEW.purok_id IS NOT NULL THEN
            INSERT INTO system_notifications (type, title, message, target_role, metadata)
            VALUES (
                'new_registration',
                'New Resident Registration in Your Purok',
                CONCAT('New registration from ', NEW.first_name, ' ', NEW.last_name, ' in your purok'),
                'purok_leader',
                JSON_OBJECT('user_id', NEW.id, 'purok_id', NEW.purok_id)
            );
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_application_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_application_summary` (
`id` int(11)
,`application_number` varchar(50)
,`applicant_name` varchar(201)
,`document_type` varchar(100)
,`purpose` text
,`status` enum('pending','processing','ready_for_pickup','completed','rejected')
,`payment_status` enum('unpaid','paid','waived')
,`payment_amount` decimal(8,2)
,`purok_name` varchar(100)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_pending_registrations`
-- (See below for the actual view)
--
CREATE TABLE `vw_pending_registrations` (
`id` int(11)
,`username` varchar(50)
,`full_name` varchar(302)
,`gender` enum('Male','Female','Other')
,`age` int(11)
,`purok_name` varchar(100)
,`contact_number` varchar(15)
,`purok_leader_approval` enum('pending','approved','disapproved')
,`admin_approval` enum('pending','approved','disapproved')
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Structure for view `vw_application_summary`
--
DROP TABLE IF EXISTS `vw_application_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_application_summary`  AS SELECT `a`.`id` AS `id`, `a`.`application_number` AS `application_number`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `applicant_name`, `dt`.`type_name` AS `document_type`, `a`.`purpose` AS `purpose`, `a`.`status` AS `status`, `a`.`payment_status` AS `payment_status`, `a`.`payment_amount` AS `payment_amount`, `p`.`purok_name` AS `purok_name`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at` FROM (((`applications` `a` join `users` `u` on(`a`.`user_id` = `u`.`id`)) join `document_types` `dt` on(`a`.`document_type_id` = `dt`.`id`)) left join `puroks` `p` on(`u`.`purok_id` = `p`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_pending_registrations`
--
DROP TABLE IF EXISTS `vw_pending_registrations`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_pending_registrations`  AS SELECT `u`.`id` AS `id`, `u`.`username` AS `username`, concat(`u`.`first_name`,' ',coalesce(`u`.`middle_name`,''),' ',`u`.`last_name`) AS `full_name`, `u`.`gender` AS `gender`, `u`.`age` AS `age`, `p`.`purok_name` AS `purok_name`, `u`.`contact_number` AS `contact_number`, `u`.`purok_leader_approval` AS `purok_leader_approval`, `u`.`admin_approval` AS `admin_approval`, `u`.`created_at` AS `created_at` FROM (`users` `u` left join `puroks` `p` on(`u`.`purok_id` = `p`.`id`)) WHERE `u`.`role` = 'resident' AND `u`.`status` = 'pending' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_user_date` (`user_id`,`created_at`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_number` (`application_number`),
  ADD KEY `document_type_id` (`document_type_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_applications_status` (`status`),
  ADD KEY `idx_applications_user_status` (`user_id`,`status`);

--
-- Indexes for table `application_history`
--
ALTER TABLE `application_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `puroks`
--
ALTER TABLE `puroks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `puroks_ibfk_1` (`purok_leader_id`);

--
-- Indexes for table `reports_cache`
--
ALTER TABLE `reports_cache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `sms_notifications`
--
ALTER TABLE `sms_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sms_status` (`status`),
  ADD KEY `sms_notifications_ibfk_1` (`user_id`);

--
-- Indexes for table `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

--
-- Indexes for table `system_notifications`
--
ALTER TABLE `system_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_read` (`target_user_id`,`is_read`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_role_status` (`role`,`status`),
  ADD KEY `users_ibfk_1` (`purok_id`),
  ADD KEY `users_ibfk_2` (`approved_by_purok_leader`),
  ADD KEY `users_ibfk_3` (`approved_by_admin`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `application_history`
--
ALTER TABLE `application_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `puroks`
--
ALTER TABLE `puroks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `reports_cache`
--
ALTER TABLE `reports_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_notifications`
--
ALTER TABLE `sms_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `system_config`
--
ALTER TABLE `system_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `system_notifications`
--
ALTER TABLE `system_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`),
  ADD CONSTRAINT `applications_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `application_history`
--
ALTER TABLE `application_history`
  ADD CONSTRAINT `application_history_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`),
  ADD CONSTRAINT `application_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `puroks`
--
ALTER TABLE `puroks`
  ADD CONSTRAINT `puroks_ibfk_1` FOREIGN KEY (`purok_leader_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `reports_cache`
--
ALTER TABLE `reports_cache`
  ADD CONSTRAINT `reports_cache_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `sms_notifications`
--
ALTER TABLE `sms_notifications`
  ADD CONSTRAINT `sms_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `system_notifications`
--
ALTER TABLE `system_notifications`
  ADD CONSTRAINT `system_notifications_ibfk_1` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`purok_id`) REFERENCES `puroks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`approved_by_purok_leader`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`approved_by_admin`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
