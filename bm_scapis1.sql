-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2025 at 12:34 PM
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
(98, 1, 'disapprove_registration', 'users', 21, '{\"user_id\":\"21\",\"disapproved_by\":1,\"disapproved_by_name\":\"System Administrator\",\"remarks\":\"Not a resident of this purok\",\"disapproved_at\":\"2025-07-25 15:08:09\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 07:08:09'),
(99, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:16:42'),
(100, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:18:29'),
(101, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:18:33'),
(102, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:19:06'),
(103, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:19:10'),
(104, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:20:53'),
(105, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:20:57'),
(106, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:21:01'),
(107, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:21:08'),
(108, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:22:57'),
(110, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:25:44'),
(111, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:38:11'),
(112, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 10:38:17'),
(113, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 11:00:04'),
(114, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 11:00:10'),
(115, 5, 'approve_registration', 'users', 23, '{\"user_id\":\"23\",\"approved_by\":5,\"approved_by_name\":\"Jan Russel Pe\\u00f1afiel\",\"remarks\":\"sdada\",\"approved_at\":\"2025-07-25 19:00:21\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 11:00:21'),
(116, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 11:01:05'),
(117, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 11:01:14'),
(118, 1, 'approve_registration', 'users', 23, '{\"user_id\":\"23\",\"approved_by\":1,\"approved_by_name\":\"System Administrator\",\"remarks\":\"sadas\",\"approved_at\":\"2025-07-25 19:08:32\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 11:08:32'),
(119, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 11:09:32'),
(120, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 11:09:36'),
(121, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 11:10:17'),
(122, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 11:10:21'),
(123, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 11:44:12'),
(125, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-25 11:46:18'),
(126, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-25 14:31:44'),
(127, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-25 14:33:41'),
(128, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 03:50:39'),
(129, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 04:14:56'),
(130, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 04:15:58'),
(131, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 04:16:04'),
(132, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 04:16:10'),
(133, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 04:16:17'),
(134, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 04:17:20'),
(135, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 04:17:33'),
(136, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 04:17:38'),
(137, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 04:17:40'),
(138, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 04:17:48'),
(139, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 04:17:54'),
(140, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 04:18:26'),
(141, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-26 04:18:28'),
(142, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 04:19:43'),
(143, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 05:33:47'),
(144, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 05:40:44'),
(145, 5, 'approve_registration', 'users', 24, '{\"user_id\":\"24\",\"approved_by\":5,\"approved_by_name\":\"Jan Russel Pe\\u00f1afiel\",\"remarks\":\"asda\",\"approved_at\":\"2025-07-26 13:47:14\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 05:47:14'),
(146, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 05:47:37'),
(147, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 05:48:03'),
(148, 1, 'approve_registration', 'users', 24, '{\"user_id\":\"24\",\"approved_by\":1,\"approved_by_name\":\"System Administrator\",\"remarks\":\"asda\",\"approved_at\":\"2025-07-26 13:49:06\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 05:49:06'),
(149, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:39:54'),
(150, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:41:21'),
(151, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:41:28'),
(152, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:41:37'),
(153, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:42:24'),
(154, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:54:18'),
(155, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:54:24'),
(156, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:54:44'),
(157, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:54:54'),
(158, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:55:10'),
(159, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:55:42'),
(160, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:55:45'),
(161, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:56:12'),
(166, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 06:58:32'),
(167, 1, 'waive_payment', 'applications', 2, '{\"payment_status\":\"unpaid\",\"payment_amount\":\"50.00\"}', '{\"payment_status\":\"waived\",\"payment_amount\":\"50.00\"}', '::1', NULL, '2025-07-26 07:00:42'),
(168, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 07:00:55'),
(171, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 07:04:16'),
(172, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 07:11:22'),
(175, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 07:11:43'),
(176, 1, 'Started processing application #APP-20250726-4446', 'applications', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 07:16:58'),
(177, 1, 'Marked application #APP-20250726-4446 as ready for pickup', 'applications', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 08:45:58'),
(178, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:17:01'),
(179, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:18:00'),
(180, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:18:53'),
(182, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:20:42'),
(183, 5, 'approve_registration', 'users', 25, '{\"user_id\":\"25\",\"approved_by\":5,\"approved_by_name\":\"Jan Russel Pe\\u00f1afiel\",\"remarks\":\"asdadad\",\"approved_at\":\"2025-07-26 17:20:59\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:20:59'),
(184, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:21:03'),
(185, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:21:07'),
(186, 1, 'approve_registration', 'users', 25, '{\"user_id\":\"25\",\"approved_by\":1,\"approved_by_name\":\"System Administrator\",\"remarks\":\"asda\",\"approved_at\":\"2025-07-26 17:21:19\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:21:19'),
(187, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:21:26'),
(190, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:22:27'),
(191, 1, 'waive_payment', 'applications', 3, '{\"payment_status\":\"unpaid\",\"payment_amount\":\"50.00\"}', '{\"payment_status\":\"waived\",\"payment_amount\":\"50.00\"}', '::1', NULL, '2025-07-26 09:25:55'),
(192, 1, 'delete_resident', 'users', 25, '{\"name\":\"Jan Russessl Pe\\u00f1afielss\",\"purok_id\":1,\"deleted_at\":\"2025-07-26 17:30:01\",\"deleted_by\":1,\"deleted_by_name\":\"System Administrator\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:30:01'),
(193, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:30:07'),
(197, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:33:36'),
(198, 5, 'approve_registration', 'users', 26, '{\"user_id\":\"26\",\"approved_by\":5,\"approved_by_name\":\"Jan Russel Pe\\u00f1afiel\",\"remarks\":\"asda\",\"approved_at\":\"2025-07-26 17:33:41\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:33:41'),
(199, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:33:46'),
(200, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:33:51'),
(201, 1, 'approve_registration', 'users', 26, '{\"user_id\":\"26\",\"approved_by\":1,\"approved_by_name\":\"System Administrator\",\"remarks\":\"asda\",\"approved_at\":\"2025-07-26 17:34:00\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:34:00'),
(202, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:36:38'),
(207, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:39:22'),
(208, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:41:32'),
(211, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:42:26'),
(212, 1, 'waive_payment', 'applications', 4, '{\"payment_status\":\"unpaid\",\"payment_amount\":\"30.00\"}', '{\"payment_status\":\"waived\",\"payment_amount\":\"30.00\"}', '::1', NULL, '2025-07-26 09:43:45'),
(213, 1, 'Started processing application #APP-20250726-5724', 'applications', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:44:00'),
(214, 1, 'Marked application #APP-20250726-5724 as ready for pickup', 'applications', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:44:15'),
(215, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:49:32'),
(218, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:49:52'),
(219, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:51:37'),
(220, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 09:52:12'),
(221, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 10:09:20'),
(222, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 10:09:28'),
(223, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 10:13:39'),
(225, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 10:17:53'),
(226, 5, 'approve_registration', 'users', 29, '{\"user_id\":\"29\",\"approved_by\":5,\"approved_by_name\":\"Jan Russel Pe\\u00f1afiel\",\"remarks\":\"asda\",\"approved_at\":\"2025-07-26 18:24:53\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 10:24:53'),
(227, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 10:25:04'),
(228, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 10:25:10'),
(229, 1, 'approve_registration', 'users', 29, '{\"user_id\":\"29\",\"approved_by\":1,\"approved_by_name\":\"System Administrator\",\"remarks\":\"sdfs\",\"approved_at\":\"2025-07-26 18:27:28\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 10:27:28'),
(230, 1, 'delete_resident', 'users', 29, '{\"name\":\"Jan Russelsss Pe\\u00f1afielss\",\"purok_id\":1,\"deleted_at\":\"2025-07-26 22:30:53\",\"deleted_by\":1,\"deleted_by_name\":\"System Administrator\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 14:30:53'),
(231, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 14:31:43'),
(232, 30, 'User registered', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 14:32:28'),
(233, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 14:32:40'),
(234, 1, 'delete_resident', 'users', 24, '{\"name\":\"Jan Russel Pe\\u00f1afiel\",\"purok_id\":1,\"deleted_at\":\"2025-07-26 22:34:02\",\"deleted_by\":1,\"deleted_by_name\":\"System Administrator\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-26 14:34:02'),
(235, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 09:50:04'),
(236, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 09:50:22'),
(237, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 10:21:38'),
(238, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 10:57:58'),
(239, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-27 11:17:52'),
(240, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-27 11:23:52'),
(241, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-27 11:27:47'),
(242, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-27 11:27:50'),
(243, 5, 'approve_registration', 'users', 30, '{\"user_id\":\"30\",\"approved_by\":5,\"approved_by_name\":\"Jan Russel Pe\\u00f1afiel\",\"remarks\":\"asdada\",\"approved_at\":\"2025-07-27 19:28:48\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-27 11:28:48'),
(244, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-27 11:28:51'),
(245, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-27 11:28:54'),
(246, 1, 'approve_registration', 'users', 30, '{\"user_id\":\"30\",\"approved_by\":1,\"approved_by_name\":\"System Administrator\",\"remarks\":\"asda\",\"approved_at\":\"2025-07-27 19:31:09\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-27 11:31:09'),
(247, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-27 11:31:23'),
(248, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-27 11:31:49'),
(249, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 00:53:09'),
(250, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 00:53:36'),
(251, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 00:53:46'),
(252, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 00:53:58'),
(253, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 00:54:01'),
(254, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 01:10:05'),
(255, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 01:10:29'),
(256, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-07-28 01:10:39'),
(257, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:05:00'),
(258, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:05:07'),
(259, 1, 'update_setting', 'system_config', 0, '{\"value\":\"09677726912\"}', '{\"value\":\"09677726912\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:05:31'),
(260, 1, 'update_setting', 'system_config', 0, '{\"value\":\"Jan Russel Elizares Pe\\u00f1afiel\"}', '{\"value\":\"Jan Russel Elizares Pe\\u00f1afiel\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:05:31'),
(261, 1, 'update_setting', 'system_config', 0, '{\"value\":\"1\"}', '{\"value\":\"1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:05:31'),
(262, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:05:35'),
(263, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:05:55'),
(264, 30, 'GCash payment verified for application #APP-20250728-8520', 'applications', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:08:57'),
(265, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', '2025-07-28 03:14:16'),
(266, 30, 'GCash payment verified for application #APP-20250728-7081', 'applications', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', '2025-07-28 03:15:41'),
(267, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:36:03'),
(268, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:36:09'),
(269, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:45:19'),
(270, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:45:31'),
(271, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:47:26'),
(272, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:47:37'),
(273, 1, 'Scheduled payment appointment for application #APP-20250728-8474', 'appointments', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:47:57'),
(274, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:52:32'),
(275, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 03:52:41'),
(276, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 04:02:59'),
(277, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 04:03:13'),
(278, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 04:03:19'),
(279, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 04:03:22'),
(280, 1, 'Marked application #APP-20250728-8520 as ready for pickup', 'applications', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 04:13:52'),
(281, 1, 'Marked application #APP-20250728-7081 as ready for pickup', 'applications', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 04:27:51'),
(283, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 13:04:19'),
(284, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 04:11:22'),
(285, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 04:11:33'),
(286, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 04:11:38'),
(287, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-23 04:20:41'),
(288, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:08:01'),
(289, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:08:20'),
(290, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:08:23'),
(291, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:08:58'),
(292, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:09:01'),
(293, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:09:11'),
(294, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:09:17'),
(295, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:09:54'),
(296, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:09:57'),
(297, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:10:03'),
(298, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:11:26'),
(299, 30, 'GCash payment verified for application #APP-20250728-8474', 'applications', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:12:22'),
(300, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:14:53'),
(301, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 13:14:59'),
(302, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-29 07:38:22'),
(303, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:16:49'),
(304, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:32:04'),
(305, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:32:12'),
(306, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:32:29'),
(307, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:32:32'),
(308, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:44:09'),
(309, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:44:14'),
(310, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:44:33'),
(311, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:44:36'),
(312, 1, 'Scheduled payment appointment for application #APP-20251005-4518', 'appointments', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:44:56'),
(313, 1, 'Completed payment appointment for application #APP-20251005-4518', 'appointments', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:45:37'),
(314, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:46:14'),
(315, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:46:19'),
(316, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:47:12'),
(317, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:47:15'),
(318, 1, 'Rescheduled pickup appointment for application #APP-20250728-8520', 'appointments', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:48:10'),
(319, 1, 'Scheduled payment appointment for application #APP-20251005-4518', 'appointments', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-05 05:48:42'),
(320, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:49:12'),
(321, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:49:18'),
(322, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:49:54'),
(323, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:49:58'),
(324, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:50:13'),
(325, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:50:16'),
(326, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:50:28'),
(327, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:50:34'),
(328, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:52:42'),
(329, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:52:47'),
(330, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:57:36'),
(331, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:57:43'),
(332, 1, 'waive_payment', 'applications', 10, '{\"payment_status\":\"unpaid\",\"payment_amount\":\"25.00\"}', '{\"payment_status\":\"waived\",\"payment_amount\":\"25.00\"}', '::1', NULL, '2025-10-06 05:58:10'),
(333, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:59:23'),
(334, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:59:29'),
(335, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:59:44'),
(336, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 05:59:47'),
(337, 1, 'remove_purok_leader', 'users', 5, '{\"role\":\"purok_leader\",\"purok_id\":1,\"purok_name\":\"Purok 1\"}', '{\"role\":\"resident\",\"remarks\":\"sfsd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:03:09'),
(338, 1, 'assign_purok_leader', 'puroks', 1, '{\"purok_leader_id\":null}', '{\"purok_leader_id\":30,\"resident_name\":\"Jan Russelsss Pe\\u00f1afielsss\",\"purok_name\":\"Purok 1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:03:16'),
(339, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:05:08'),
(340, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:05:14'),
(341, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:07:26'),
(342, 31, 'User registered', 'users', 31, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:08:58');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_affected`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(343, 30, 'User logged in', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:10:01'),
(344, 30, 'approve_registration', 'users', 31, '{\"user_id\":\"31\",\"approved_by\":30,\"approved_by_name\":\"Jan Russelsss Pe\\u00f1afielsss\",\"remarks\":\"asdada\",\"approved_at\":\"2025-10-06 14:10:07\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:10:07'),
(345, 30, 'User logged out', 'users', 30, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:10:11'),
(346, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:10:14'),
(347, 1, 'approve_registration', 'users', 31, '{\"user_id\":\"31\",\"approved_by\":1,\"approved_by_name\":\"System Administrator\",\"remarks\":\"ada\",\"approved_at\":\"2025-10-06 14:10:25\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:10:25'),
(348, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:10:32'),
(349, 31, 'User logged in', 'users', 31, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:10:46'),
(350, 31, 'User logged out', 'users', 31, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-06 06:45:54'),
(351, 5, 'User logged in', 'users', 5, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '2025-10-09 07:31:50'),
(352, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 04:45:39'),
(353, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 04:49:38'),
(354, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 04:49:53'),
(355, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 04:49:59'),
(356, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:12:35'),
(357, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:12:40'),
(358, 1, 'Scheduled payment appointment for application #APP-20251102-1293', 'appointments', 8, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:13:52'),
(359, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:15:04'),
(360, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:15:12'),
(361, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:25:18'),
(362, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:25:24'),
(363, 1, 'Advance payment confirmed for application #APP-20251102-1293', 'applications', 11, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:30:32'),
(364, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:50:34'),
(365, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:50:41'),
(366, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:53:21'),
(367, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:53:26'),
(368, 1, 'Scheduled payment appointment for application #APP-20251102-8175', 'appointments', 9, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:54:27'),
(369, 1, 'Marked payment appointment as done (advance payment) for application #APP-20251102-8175', 'applications', 12, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 05:54:43'),
(370, 1, 'waive_payment', 'applications', 9, '{\"payment_status\":\"unpaid\",\"payment_amount\":\"50.00\"}', '{\"payment_status\":\"waived\",\"payment_amount\":\"50.00\"}', '::1', NULL, '2025-11-02 06:01:35'),
(371, 1, 'Marked payment as completed manually for application #APP-20251102-8175 and started processing', 'applications', 12, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:04:30'),
(372, 1, 'Scheduled payment appointment for application #APP-20251102-4189', 'appointments', 10, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:06:21'),
(373, 1, 'Marked payment appointment as done (advance payment) for application #APP-20251102-4189', 'applications', 13, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:06:26'),
(374, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:07:08'),
(375, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:07:13'),
(376, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:17:11'),
(377, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:17:18'),
(378, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 06:38:07'),
(379, 1, 'Sent support message to resident', 'support_messages', 1, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:39:11'),
(380, 5, 'Sent support message to admin', 'support_messages', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 06:39:26'),
(381, 5, 'Sent support message to admin', 'support_messages', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 06:39:48'),
(382, 5, 'Sent support message to admin', 'support_messages', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 06:40:18'),
(383, 1, 'Sent support message to resident', 'support_messages', 5, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:40:26'),
(384, 1, 'Sent broadcast message to all users', 'support_messages', 6, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:41:34'),
(385, 1, 'Sent broadcast message to all users', 'support_messages', 7, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:41:44'),
(386, 5, 'Sent support message to admin', 'support_messages', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 06:42:07'),
(387, 1, 'Sent support message to resident', 'support_messages', 9, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:42:21'),
(388, 1, 'Uploaded file in support chat: 564729553_1854505422614273_3305278570070114941_n.jpg', 'support_messages', 10, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:43:44'),
(389, 5, 'Sent support message to admin', 'support_messages', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 06:50:07'),
(390, 1, 'Sent support message to resident', 'support_messages', 12, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:52:11'),
(391, 1, 'Sent support message to resident', 'support_messages', 13, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:52:31'),
(392, 5, 'Sent support message to admin', 'support_messages', 14, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 06:54:15'),
(393, 1, 'Sent support message to resident', 'support_messages', 15, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:54:39'),
(394, 1, 'Sent support message to resident', 'support_messages', 16, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:54:49'),
(395, 5, 'Sent support message to admin', 'support_messages', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 06:55:11'),
(396, 5, 'Sent support message to admin', 'support_messages', 18, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 06:58:25'),
(397, 1, 'Sent support message to resident', 'support_messages', 19, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:58:35'),
(398, 5, 'Sent support message to admin', 'support_messages', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 06:58:54'),
(399, 1, 'Sent support message to resident', 'support_messages', 21, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:59:09'),
(400, 5, 'Sent support message to admin', 'support_messages', 22, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 06:59:13'),
(401, 1, 'Sent support message to resident', 'support_messages', 23, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 06:59:17'),
(402, 5, 'Sent support message to admin', 'support_messages', 24, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 06:59:22'),
(403, 1, 'Sent support message to resident', 'support_messages', 25, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 07:04:26'),
(404, 5, 'Sent support message to admin', 'support_messages', 26, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 07:04:34'),
(405, 1, 'Sent support message to resident', 'support_messages', 27, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 07:10:44'),
(406, 5, 'Sent support message to admin', 'support_messages', 28, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 07:10:53'),
(407, 1, 'Sent support message to resident', 'support_messages', 29, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 07:10:59'),
(408, 1, 'Sent support message to resident', 'support_messages', 30, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 07:11:10'),
(409, 5, 'Sent support message to admin', 'support_messages', 31, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 07:11:14'),
(410, 1, 'Sent support message to resident', 'support_messages', 32, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 07:11:17'),
(411, 5, 'Sent support message to admin', 'support_messages', 33, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 07:11:21'),
(412, 1, 'Sent support message to resident', 'support_messages', 34, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 07:16:30'),
(413, 5, 'Sent support message to admin', 'support_messages', 35, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-02 07:16:34'),
(414, 1, 'Sent support message to resident', 'support_messages', 36, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-02 07:16:37'),
(415, 1, 'Sent support message to resident', 'support_messages', 37, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 07:22:02'),
(416, 1, 'Sent support message to resident', 'support_messages', 38, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 07:22:03'),
(417, 1, 'Sent support message to resident', 'support_messages', 39, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 07:22:04'),
(418, 1, 'Sent support message to resident', 'support_messages', 40, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 07:22:04'),
(419, 1, 'Sent support message to resident', 'support_messages', 41, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 07:22:04'),
(420, 1, 'Sent support message to resident', 'support_messages', 42, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 07:22:04'),
(421, 1, 'Sent support message to resident', 'support_messages', 43, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 07:22:04'),
(422, 1, 'Sent support message to resident', 'support_messages', 44, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 07:22:04'),
(423, 1, 'Sent support message to resident', 'support_messages', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 07:22:05'),
(424, 1, 'Sent support message to resident', 'support_messages', 46, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 07:22:05'),
(425, 1, 'Sent support message to resident', 'support_messages', 47, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 07:22:05'),
(426, 1, 'Sent support message to resident', 'support_messages', 48, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 07:22:05'),
(427, 1, 'Sent support message to resident', 'support_messages', 49, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 09:23:43'),
(428, 1, 'Sent support message to resident', 'support_messages', 50, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 09:24:09'),
(429, 1, 'Sent support message to resident', 'support_messages', 51, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 09:48:18'),
(430, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 10:43:17'),
(431, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 10:43:24'),
(432, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 10:50:20'),
(433, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 10:50:26'),
(434, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 10:50:42'),
(435, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 10:50:46'),
(436, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 10:52:36'),
(437, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 10:52:42'),
(438, 1, 'User logged out', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 10:54:00'),
(439, 5, 'User logged in', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 10:54:06'),
(440, 5, 'User logged out', 'users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 11:14:10'),
(441, 1, 'User logged in', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 11:14:15');

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
  `payment_method` enum('cash','gcash','bank_transfer','other') DEFAULT NULL,
  `payment_amount` decimal(8,2) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_receipt` varchar(255) DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `pickup_date` timestamp NULL DEFAULT NULL,
  `appointment_date` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `priority_level` int(11) DEFAULT 1,
  `supporting_documents` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `application_number`, `user_id`, `document_type_id`, `purpose`, `urgency`, `status`, `payment_status`, `payment_method`, `payment_amount`, `payment_date`, `payment_reference`, `payment_receipt`, `admin_remarks`, `pickup_date`, `appointment_date`, `processed_by`, `priority_level`, `supporting_documents`, `created_at`, `updated_at`) VALUES
(6, 'APP-20250728-8520', 30, 2, 'sdasdad', 'Regular', 'ready_for_pickup', 'unpaid', 'gcash', 30.00, '2025-07-28 03:08:54', 'GC17536719835758', NULL, '2025-07-28 12:13:49 - Ready for pickup: sadada2025-10-05 13:48:08 - Pickup appointment rescheduled to 2025-10-06T13:48 by System Administrator', '2025-07-31 04:13:00', NULL, NULL, 1, NULL, '2025-07-28 00:53:53', '2025-10-05 05:48:08'),
(7, 'APP-20250728-7081', 30, 1, 'asda', 'Regular', 'ready_for_pickup', 'paid', 'gcash', 50.00, '2025-07-28 03:15:38', 'GC17536724943183', NULL, '2025-07-28 12:27:48 - Ready for pickup: asdadada', '2025-07-30 04:27:00', NULL, NULL, 1, NULL, '2025-07-28 03:10:01', '2025-07-28 04:27:48'),
(8, 'APP-20250728-8474', 30, 2, 'Ysysusus', 'Regular', 'processing', 'paid', 'gcash', 30.00, '2025-08-25 13:12:18', 'GC17561275005981', NULL, '2025-07-28 11:47:57 - Payment appointment scheduled for 2025-07-30T11:47 by System Administrator', NULL, NULL, NULL, 1, NULL, '2025-07-28 03:16:23', '2025-08-25 13:12:18'),
(9, 'APP-20251005-4518', 30, 1, 'asdadad', 'Regular', 'processing', 'waived', NULL, 50.00, '2025-11-02 06:01:35', 'Waived by Admin', NULL, '2025-10-05 13:44:55 - Payment appointment scheduled for 2025-10-16T13:44 by System Administrator2025-10-05 13:45:35 - Payment appointment completed by System Administrator2025-10-05 13:48:41 - Payment appointment scheduled for 2025-10-06T13:48 by System Administrator2025-11-02 14:01:35 - Payment waived by System Administrator. Processing started automatically.', NULL, NULL, 1, 1, NULL, '2025-10-05 05:44:25', '2025-11-02 06:01:35'),
(10, 'APP-20251005-7148', 30, 3, 'sdfs', 'Regular', 'processing', 'waived', NULL, 25.00, '2025-10-06 05:58:10', 'Waived by Admin', NULL, '2025-10-06 13:58:10 - Payment waived by System Administrator. Processing started automatically.', NULL, NULL, 1, 1, NULL, '2025-10-05 05:47:08', '2025-10-06 05:58:10'),
(11, 'APP-20251102-1293', 5, 4, 'Government Transaction', 'Regular', 'processing', 'paid', '', 200.00, '2025-11-02 05:30:30', 'Advance payment confirmed by admin', NULL, '2025-11-02 13:13:51 - Payment appointment scheduled for 2025-11-04T13:13 by System Administrator2025-11-02 13:30:30 - Advance payment confirmed. Application moved to processing by System Administrator', NULL, NULL, 1, 1, NULL, '2025-11-02 04:46:15', '2025-11-02 05:30:30'),
(12, 'APP-20251102-8175', 5, 5, 'Government Transaction', 'Regular', 'processing', 'paid', '', 500.00, '2025-11-02 06:04:30', 'MANUAL-20251102140430', NULL, '2025-11-02 13:54:25 - Payment appointment scheduled for 2025-11-06T13:54 by System Administrator2025-11-02 13:54:43 - Payment allowed by System Administrator2025-11-02 14:04:30 - Payment marked as completed manually by System Administrator. Processing started automatically.\n', NULL, NULL, 1, 1, NULL, '2025-11-02 05:33:08', '2025-11-02 06:04:30'),
(13, 'APP-20251102-4189', 5, 2, 'Government Transaction', 'Regular', 'pending', 'unpaid', NULL, 30.00, NULL, NULL, NULL, '2025-11-02 14:06:19 - Payment appointment scheduled for 2025-11-08T14:06 by System Administrator2025-11-02 14:06:26 - Payment allowed by System Administrator', NULL, NULL, NULL, 1, NULL, '2025-11-02 06:05:56', '2025-11-02 06:06:26');

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

--
-- Dumping data for table `application_history`
--

INSERT INTO `application_history` (`id`, `application_id`, `status`, `remarks`, `changed_by`, `created_at`) VALUES
(10, 6, 'processing', 'GCash payment verified. Reference: GC17536719835758', 30, '2025-07-28 03:08:54'),
(11, 7, 'processing', 'GCash payment verified. Reference: GC17536724943183', 30, '2025-07-28 03:15:38'),
(12, 8, 'pending', 'Payment appointment scheduled for Jul 30, 2025 11:47 AM', 1, '2025-07-28 03:47:57'),
(13, 6, 'ready_for_pickup', 'sadada', 1, '2025-07-28 04:13:49'),
(14, 7, 'ready_for_pickup', 'asdadada', 1, '2025-07-28 04:27:48'),
(15, 8, 'processing', 'GCash payment verified. Reference: GC17561275005981', 30, '2025-08-25 13:12:18'),
(16, 9, 'pending', 'Payment appointment scheduled for Oct 16, 2025 1:44 PM', 1, '2025-10-05 05:44:55'),
(17, 9, 'pending', 'Payment appointment completed', 1, '2025-10-05 05:45:35'),
(18, 6, 'pending', 'Pickup appointment rescheduled to Oct 6, 2025 1:48 PM', 1, '2025-10-05 05:48:08'),
(19, 9, 'pending', 'Payment appointment scheduled for Oct 6, 2025 1:48 PM', 1, '2025-10-05 05:48:41'),
(20, 10, 'payment_waived', 'Payment waived by administrator', 1, '2025-10-06 05:58:10'),
(21, 10, 'processing', 'Processing started automatically after payment waiver. Processing time: 3 to 5 working days (except holidays)', 1, '2025-10-06 05:58:10'),
(22, 11, 'pending', 'Payment appointment scheduled for Nov 4, 2025 1:13 PM', 1, '2025-11-02 05:13:51'),
(23, 11, 'processing', 'Advance payment confirmed. Processing started automatically. Processing time: 3 to 5 working days (except holidays)', 1, '2025-11-02 05:30:30'),
(24, 12, 'pending', 'Payment appointment scheduled for Nov 6, 2025 1:54 PM', 1, '2025-11-02 05:54:25'),
(25, 12, 'pending', 'Payment allowed - Resident can now make payment', 1, '2025-11-02 05:54:43'),
(26, 9, 'payment_waived', 'Payment waived by administrator', 1, '2025-11-02 06:01:35'),
(27, 9, 'processing', 'Processing started automatically after payment waiver. Processing time: 3 to 5 working days (except holidays)', 1, '2025-11-02 06:01:35'),
(28, 12, 'paid', 'Payment marked as completed manually - Processing started', 1, '2025-11-02 06:04:30'),
(29, 12, 'processing', 'Application processing started after manual payment completion', 1, '2025-11-02 06:04:30'),
(30, 13, 'pending', 'Payment appointment scheduled for Nov 8, 2025 2:06 PM', 1, '2025-11-02 06:06:19'),
(31, 13, 'pending', 'Payment allowed - Resident can now make payment', 1, '2025-11-02 06:06:26');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `appointment_type` enum('verification','pickup','interview','payment') NOT NULL,
  `appointment_date` datetime NOT NULL,
  `status` enum('scheduled','completed','cancelled','rescheduled','payment_allowed') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `application_id`, `user_id`, `appointment_type`, `appointment_date`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(3, 8, 30, 'payment', '2025-07-30 11:47:00', 'scheduled', 'Pay With Gcash', 1, '2025-07-28 03:47:57', '2025-07-28 03:47:57'),
(4, 6, 30, 'pickup', '2025-10-06 13:48:00', 'rescheduled', 'sadada\nRescheduled on 2025-10-05 13:48:08 to 2025-10-06T13:48 by System Administrator', 1, '2025-07-28 04:13:49', '2025-10-05 05:48:08'),
(5, 7, 30, 'pickup', '2025-07-30 12:27:00', 'scheduled', 'asdadada', 1, '2025-07-28 04:27:48', '2025-07-28 04:27:48'),
(6, 9, 30, 'payment', '2025-10-16 13:44:00', 'completed', 'asda\nCompleted on 2025-10-05 13:45:35 by System Administrator', 1, '2025-10-05 05:44:55', '2025-10-05 05:45:35'),
(7, 9, 30, 'payment', '2025-10-06 13:48:00', 'scheduled', 'sdfs', 1, '2025-10-05 05:48:41', '2025-10-05 05:48:41'),
(8, 11, 5, 'payment', '2025-11-04 13:13:00', 'completed', 'sdfsfsfs\nPayment completed on 2025-11-02 13:30:30 by System Administrator', 1, '2025-11-02 05:13:51', '2025-11-02 05:30:30'),
(9, 12, 5, 'payment', '2025-11-06 13:54:00', 'payment_allowed', 'dsadad\nAppointment marked as done (advance payment) on 2025-11-02 13:54:43 by System Administrator', 1, '2025-11-02 05:54:25', '2025-11-02 05:54:43'),
(10, 13, 5, 'payment', '2025-11-08 14:06:00', 'payment_allowed', '\nAppointment marked as done (advance payment) on 2025-11-02 14:06:26 by System Administrator', 1, '2025-11-02 06:06:19', '2025-11-02 06:06:26');

-- --------------------------------------------------------

--
-- Table structure for table `chat_conversations`
--

CREATE TABLE `chat_conversations` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT 'Payment Support',
  `status` enum('active','closed','waiting') DEFAULT 'active',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `application_id` int(11) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `last_message_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resident_last_seen` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_last_seen` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_conversations`
--

INSERT INTO `chat_conversations` (`id`, `resident_id`, `admin_id`, `subject`, `status`, `priority`, `application_id`, `payment_id`, `last_message_at`, `resident_last_seen`, `admin_last_seen`, `created_at`, `updated_at`) VALUES
(2, 5, NULL, 'General Support', 'active', 'normal', NULL, NULL, '2025-11-03 10:50:14', '2025-11-03 10:50:14', NULL, '2025-11-03 10:50:14', '2025-11-03 10:55:35');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('resident','admin') NOT NULL,
  `message_type` enum('text','file','system') DEFAULT 'text',
  `message_content` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `reply_to_message_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `conversation_id`, `sender_id`, `sender_type`, `message_type`, `message_content`, `file_path`, `file_name`, `file_size`, `is_read`, `read_at`, `is_deleted`, `deleted_at`, `reply_to_message_id`, `created_at`) VALUES
(2, 2, 5, 'resident', 'text', 'sadada', NULL, NULL, NULL, 1, NULL, 0, NULL, NULL, '2025-11-03 10:50:14'),
(3, 2, 1, 'admin', 'text', 'hello', NULL, NULL, NULL, 1, NULL, 0, NULL, NULL, '2025-11-03 10:50:37'),
(4, 2, 5, 'resident', 'file', '📎 Shared a file: 564729553_1854505422614273_3305278570070114941_n.jpg', 'uploads/chat/1762167056_69088910e2de7.jpg', '564729553_1854505422614273_3305278570070114941_n.jpg', NULL, 1, NULL, 0, NULL, NULL, '2025-11-03 10:50:56'),
(6, 2, 1, 'admin', 'text', 'ahh okay', NULL, NULL, NULL, 1, NULL, 0, NULL, NULL, '2025-11-03 10:53:49'),
(7, 2, 5, 'resident', 'text', 'I need help with requirements', NULL, NULL, NULL, 1, NULL, 0, NULL, NULL, '2025-11-03 10:55:35');

-- --------------------------------------------------------

--
-- Table structure for table `chat_online_status`
--

CREATE TABLE `chat_online_status` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status_message` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_rate_limits`
--

CREATE TABLE `chat_rate_limits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message_count` int(11) DEFAULT 1,
  `window_start` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_rate_limits`
--

INSERT INTO `chat_rate_limits` (`id`, `user_id`, `message_count`, `window_start`, `created_at`) VALUES
(1, 5, 1, '2025-11-03 10:50:14', '2025-11-03 10:50:14'),
(2, 1, 1, '2025-11-03 10:50:37', '2025-11-03 10:50:37'),
(3, 5, 1, '2025-11-03 10:50:56', '2025-11-03 10:50:56'),
(4, 1, 1, '2025-11-03 10:53:49', '2025-11-03 10:53:49'),
(5, 5, 1, '2025-11-03 10:55:35', '2025-11-03 10:55:35');

-- --------------------------------------------------------

--
-- Table structure for table `chat_settings`
--

CREATE TABLE `chat_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_settings`
--

INSERT INTO `chat_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'chat_enabled', '1', 'Enable/disable chat system', '2025-11-03 10:07:54', '2025-11-03 10:07:54'),
(2, 'auto_assign_admin', '1', 'Automatically assign available admin to new conversations', '2025-11-03 10:07:54', '2025-11-03 10:07:54'),
(3, 'max_file_size', '5242880', 'Maximum file upload size in bytes (5MB)', '2025-11-03 10:07:54', '2025-11-03 10:07:54'),
(4, 'allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,txt', 'Allowed file extensions for uploads', '2025-11-03 10:07:54', '2025-11-03 10:07:54'),
(5, 'chat_widget_position', 'bottom-right', 'Position of chat widget (bottom-right, bottom-left)', '2025-11-03 10:07:54', '2025-11-03 10:07:54'),
(6, 'chat_widget_color', '#007bff', 'Primary color of chat widget', '2025-11-03 10:07:54', '2025-11-03 10:07:54'),
(7, 'offline_message', 'Admin is currently offline. Your message will be answered as soon as possible.', 'Message shown when admin is offline', '2025-11-03 10:07:54', '2025-11-03 10:07:54'),
(8, 'welcome_message', 'Hello! How can we help you with your payment or application?', 'Welcome message for new conversations', '2025-11-03 10:07:54', '2025-11-03 10:07:54'),
(9, 'typing_timeout', '3000', 'Typing indicator timeout in milliseconds', '2025-11-03 10:07:54', '2025-11-03 10:07:54'),
(10, 'message_limit_per_minute', '10', 'Maximum messages per minute per user', '2025-11-03 10:07:54', '2025-11-03 10:07:54'),
(11, 'admin_notification_sound', '1', 'Play sound for admin when receiving new messages', '2025-11-03 10:07:54', '2025-11-03 10:07:54'),
(12, 'resident_can_upload_files', '1', 'Allow residents to upload files in chat', '2025-11-03 10:07:54', '2025-11-03 10:07:54'),
(13, 'chat_history_retention_days', '365', 'Number of days to keep chat history', '2025-11-03 10:07:54', '2025-11-03 10:07:54');

-- --------------------------------------------------------

--
-- Table structure for table `chat_typing_indicators`
--

CREATE TABLE `chat_typing_indicators` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_typing` tinyint(1) DEFAULT 0,
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
(6, 'Community Tax (Cedula)', 'Community Tax Certificate (also known as Cedula)', 40.00, 'Valid ID, Character References', 3, 1, '2025-07-24 02:40:48');

-- --------------------------------------------------------

--
-- Table structure for table `payment_verifications`
--

CREATE TABLE `payment_verifications` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','verified','failed','expired') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 15 minute)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_verifications`
--

INSERT INTO `payment_verifications` (`id`, `application_id`, `reference_number`, `amount`, `status`, `created_at`, `verified_at`, `expires_at`) VALUES
(1, 6, 'GC17536716979356', 30.00, 'pending', '2025-07-28 03:01:37', NULL, '2025-07-28 03:16:37'),
(2, 6, 'GC17536717477147', 30.00, 'pending', '2025-07-28 03:02:27', NULL, '2025-07-28 03:17:27'),
(3, 6, 'GC17536718173425', 30.00, 'verified', '2025-07-28 03:03:37', '2025-07-28 03:04:20', '2025-07-28 03:18:37'),
(4, 6, 'GC17536719835758', 30.00, 'verified', '2025-07-28 03:06:23', '2025-07-28 03:08:54', '2025-07-28 03:21:23'),
(5, 7, 'GC17536722079169', 50.00, 'pending', '2025-07-28 03:10:07', NULL, '2025-07-28 03:25:07'),
(6, 7, 'GC17536724943183', 50.00, 'verified', '2025-07-28 03:14:54', '2025-07-28 03:15:38', '2025-07-28 03:29:54'),
(7, 8, 'GC17536725907672', 30.00, 'pending', '2025-07-28 03:16:30', NULL, '2025-07-28 03:31:30'),
(8, 8, 'GC17536727128627', 30.00, 'pending', '2025-07-28 03:18:32', NULL, '2025-07-28 03:33:32'),
(9, 8, 'GC17536727704178', 30.00, 'pending', '2025-07-28 03:19:30', NULL, '2025-07-28 03:34:30'),
(10, 8, 'GC17536729252419', 30.00, 'pending', '2025-07-28 03:22:05', NULL, '2025-07-28 03:37:05'),
(11, 8, 'GC17536729887681', 30.00, 'pending', '2025-07-28 03:23:08', NULL, '2025-07-28 03:38:08'),
(12, 8, 'GC17536747708186', 30.00, 'pending', '2025-07-28 03:52:50', NULL, '2025-07-28 04:07:50'),
(13, 8, 'GC17536751537253', 30.00, 'pending', '2025-07-28 03:59:13', NULL, '2025-07-28 04:14:13'),
(14, 8, 'GC17561275005981', 30.00, 'verified', '2025-08-25 13:11:40', '2025-08-25 13:12:18', '2025-08-25 13:26:40'),
(15, 10, 'GC17597300853961', 25.00, 'failed', '2025-10-06 05:54:45', NULL, '2025-10-06 06:09:45'),
(16, 11, 'GC17620587814737', 200.00, 'expired', '2025-11-02 04:46:21', NULL, '2025-11-02 05:01:21'),
(17, 11, 'GC17620590175872', 200.00, 'expired', '2025-11-02 04:50:17', NULL, '2025-11-02 05:05:17'),
(18, 11, 'GC17620599315743', 200.00, 'pending', '2025-11-02 05:05:31', NULL, '2025-11-02 05:20:31'),
(19, 12, 'GC17620631219685', 500.00, 'pending', '2025-11-02 05:58:41', NULL, '2025-11-02 06:13:41'),
(20, 13, 'GC17620635959033', 30.00, 'pending', '2025-11-02 06:06:35', NULL, '2025-11-02 06:21:35'),
(21, 13, 'GC17620636395474', 30.00, 'pending', '2025-11-02 06:07:19', NULL, '2025-11-02 06:22:19'),
(22, 13, 'GC17620636714286', 30.00, 'expired', '2025-11-02 06:07:51', NULL, '2025-11-02 06:22:51');

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
(1, 'Purok 1', 30, '2025-07-24 02:40:48'),
(2, 'Purok 2', NULL, '2025-07-24 02:40:48'),
(4, 'Purok 4', NULL, '2025-07-24 02:40:48'),
(5, 'Purok 5', NULL, '2025-07-24 02:40:48'),
(6, 'Purok 6', NULL, '2025-07-24 02:40:48'),
(7, 'Purok 7', NULL, '2025-07-24 02:40:48'),
(8, 'Purok 8', NULL, '2025-07-24 02:40:48'),
(9, 'Purok 9', NULL, '2025-07-24 02:40:48');

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
(27, 1, '639123456789', 'Test SMS from BM-SCaPIS system. Time: 2025-07-26 15:13:55', 'failed', '<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\">\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"ie=edge\">\r\n  <link href=\"https://fonts.googleapis.com/css?family=Raleway:500,800\" rel=\"stylesheet\">\r\n  <title>PhilSMS</title>\r\n  <style>\r\n  * {\r\n  margin:0;\r\n  padding: 0;\r\n}\r\nbody{\r\n  background: #233142;\r\n  \r\n}\r\n.whistle{\r\n  width: 20%;\r\n  fill: #f95959;\r\n  margin: 100px 40%;\r\n  text-align: left;\r\n  transform: translate(-50%, -50%);\r\n  transform: rotate(0);\r\n  transform-origin: 80% 30%;\r\n  animation: wiggle .2s infinite;\r\n}\r\n\r\n@keyframes wiggle {\r\n  0%{\r\n    transform: rotate(3deg);\r\n  }\r\n  50%{\r\n    transform: rotate(0deg);\r\n  }\r\n  100%{\r\n    transform: rotate(3deg);\r\n  }\r\n}\r\nh1{\r\n  margin-top: -100px;\r\n  margin-bottom: 20px;\r\n  color: #facf5a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 90px;\r\n  font-weight: 800;\r\n}\r\nh2{\r\n  color: #455d7a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 30px;\r\n  text-transform: uppercase;\r\n}\r\n</style>\r\n</head>\r\n<body>\r\n  <use>\r\n  <svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\" class=\"whistle\">\r\n<metadata> Svg Vector Icons : http://www.onlinewebfonts.com/icon </metadata>\r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\">\r\n<path d=\"M4295.8,3963.2c-113-57.4-122.5-107.2-116.8-622.3l5.7-461.4l63.2-55.5c72.8-65.1,178.1-74.7,250.8-24.9c86.2,61.3,97.6,128.3,97.6,584c0,474.8-11.5,526.5-124.5,580.1C4393.4,4001.5,4372.4,4001.5,4295.8,3963.2z\"/><path d=\"M3053.1,3134.2c-68.9-42.1-111-143.6-93.8-216.4c7.7-26.8,216.4-250.8,476.8-509.3c417.4-417.4,469.1-463.4,526.5-463.4c128.3,0,212.5,88.1,212.5,224c0,67-26.8,97.6-434.6,509.3c-241.2,241.2-459.5,449.9-488.2,465.3C3181.4,3180.1,3124,3178.2,3053.1,3134.2z\"/><path d=\"M2653,1529.7C1644,1445.4,765.1,850,345.8-32.7C62.4-628.2,22.2-1317.4,234.8-1960.8C451.1-2621.3,947-3186.2,1584.6-3500.2c1018.6-501.6,2228.7-296.8,3040.5,515.1c317.8,317.8,561,723.7,670.1,1120.1c101.5,369.5,158.9,455.7,360,553.3c114.9,57.4,170.4,65.1,1487.7,229.8c752.5,93.8,1392,181.9,1420.7,193.4C8628.7-857.9,9900,1250.1,9900,1328.6c0,84.3-67,172.3-147.4,195.3c-51.7,15.3-790.8,19.1-2558,15.3l-2487.2-5.7l-55.5-63.2l-55.5-61.3v-344.6V719.8h-411.7h-411.7v325.5c0,509.3,11.5,499.7-616.5,494C2921,1537.3,2695.1,1533.5,2653,1529.7z\"/></g></g>\r\n</svg>\r\n</use>\r\n<h1>404</h1>\r\n<h2>not found</h2>\r\n</body>\r\n</html>\r\n', NULL, '2025-07-26 07:13:55'),
(28, 1, '639123456789', 'Test SMS from BM-SCaPIS system. Time: 2025-07-26 15:14:05', 'failed', '<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\">\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"ie=edge\">\r\n  <link href=\"https://fonts.googleapis.com/css?family=Raleway:500,800\" rel=\"stylesheet\">\r\n  <title>PhilSMS</title>\r\n  <style>\r\n  * {\r\n  margin:0;\r\n  padding: 0;\r\n}\r\nbody{\r\n  background: #233142;\r\n  \r\n}\r\n.whistle{\r\n  width: 20%;\r\n  fill: #f95959;\r\n  margin: 100px 40%;\r\n  text-align: left;\r\n  transform: translate(-50%, -50%);\r\n  transform: rotate(0);\r\n  transform-origin: 80% 30%;\r\n  animation: wiggle .2s infinite;\r\n}\r\n\r\n@keyframes wiggle {\r\n  0%{\r\n    transform: rotate(3deg);\r\n  }\r\n  50%{\r\n    transform: rotate(0deg);\r\n  }\r\n  100%{\r\n    transform: rotate(3deg);\r\n  }\r\n}\r\nh1{\r\n  margin-top: -100px;\r\n  margin-bottom: 20px;\r\n  color: #facf5a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 90px;\r\n  font-weight: 800;\r\n}\r\nh2{\r\n  color: #455d7a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 30px;\r\n  text-transform: uppercase;\r\n}\r\n</style>\r\n</head>\r\n<body>\r\n  <use>\r\n  <svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\" class=\"whistle\">\r\n<metadata> Svg Vector Icons : http://www.onlinewebfonts.com/icon </metadata>\r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\">\r\n<path d=\"M4295.8,3963.2c-113-57.4-122.5-107.2-116.8-622.3l5.7-461.4l63.2-55.5c72.8-65.1,178.1-74.7,250.8-24.9c86.2,61.3,97.6,128.3,97.6,584c0,474.8-11.5,526.5-124.5,580.1C4393.4,4001.5,4372.4,4001.5,4295.8,3963.2z\"/><path d=\"M3053.1,3134.2c-68.9-42.1-111-143.6-93.8-216.4c7.7-26.8,216.4-250.8,476.8-509.3c417.4-417.4,469.1-463.4,526.5-463.4c128.3,0,212.5,88.1,212.5,224c0,67-26.8,97.6-434.6,509.3c-241.2,241.2-459.5,449.9-488.2,465.3C3181.4,3180.1,3124,3178.2,3053.1,3134.2z\"/><path d=\"M2653,1529.7C1644,1445.4,765.1,850,345.8-32.7C62.4-628.2,22.2-1317.4,234.8-1960.8C451.1-2621.3,947-3186.2,1584.6-3500.2c1018.6-501.6,2228.7-296.8,3040.5,515.1c317.8,317.8,561,723.7,670.1,1120.1c101.5,369.5,158.9,455.7,360,553.3c114.9,57.4,170.4,65.1,1487.7,229.8c752.5,93.8,1392,181.9,1420.7,193.4C8628.7-857.9,9900,1250.1,9900,1328.6c0,84.3-67,172.3-147.4,195.3c-51.7,15.3-790.8,19.1-2558,15.3l-2487.2-5.7l-55.5-63.2l-55.5-61.3v-344.6V719.8h-411.7h-411.7v325.5c0,509.3,11.5,499.7-616.5,494C2921,1537.3,2695.1,1533.5,2653,1529.7z\"/></g></g>\r\n</svg>\r\n</use>\r\n<h1>404</h1>\r\n<h2>not found</h2>\r\n</body>\r\n</html>\r\n', NULL, '2025-07-26 07:14:05'),
(29, 1, '639123456789', 'Test SMS with valid user_id', 'failed', '<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\">\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"ie=edge\">\r\n  <link href=\"https://fonts.googleapis.com/css?family=Raleway:500,800\" rel=\"stylesheet\">\r\n  <title>PhilSMS</title>\r\n  <style>\r\n  * {\r\n  margin:0;\r\n  padding: 0;\r\n}\r\nbody{\r\n  background: #233142;\r\n  \r\n}\r\n.whistle{\r\n  width: 20%;\r\n  fill: #f95959;\r\n  margin: 100px 40%;\r\n  text-align: left;\r\n  transform: translate(-50%, -50%);\r\n  transform: rotate(0);\r\n  transform-origin: 80% 30%;\r\n  animation: wiggle .2s infinite;\r\n}\r\n\r\n@keyframes wiggle {\r\n  0%{\r\n    transform: rotate(3deg);\r\n  }\r\n  50%{\r\n    transform: rotate(0deg);\r\n  }\r\n  100%{\r\n    transform: rotate(3deg);\r\n  }\r\n}\r\nh1{\r\n  margin-top: -100px;\r\n  margin-bottom: 20px;\r\n  color: #facf5a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 90px;\r\n  font-weight: 800;\r\n}\r\nh2{\r\n  color: #455d7a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 30px;\r\n  text-transform: uppercase;\r\n}\r\n</style>\r\n</head>\r\n<body>\r\n  <use>\r\n  <svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\" class=\"whistle\">\r\n<metadata> Svg Vector Icons : http://www.onlinewebfonts.com/icon </metadata>\r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\">\r\n<path d=\"M4295.8,3963.2c-113-57.4-122.5-107.2-116.8-622.3l5.7-461.4l63.2-55.5c72.8-65.1,178.1-74.7,250.8-24.9c86.2,61.3,97.6,128.3,97.6,584c0,474.8-11.5,526.5-124.5,580.1C4393.4,4001.5,4372.4,4001.5,4295.8,3963.2z\"/><path d=\"M3053.1,3134.2c-68.9-42.1-111-143.6-93.8-216.4c7.7-26.8,216.4-250.8,476.8-509.3c417.4-417.4,469.1-463.4,526.5-463.4c128.3,0,212.5,88.1,212.5,224c0,67-26.8,97.6-434.6,509.3c-241.2,241.2-459.5,449.9-488.2,465.3C3181.4,3180.1,3124,3178.2,3053.1,3134.2z\"/><path d=\"M2653,1529.7C1644,1445.4,765.1,850,345.8-32.7C62.4-628.2,22.2-1317.4,234.8-1960.8C451.1-2621.3,947-3186.2,1584.6-3500.2c1018.6-501.6,2228.7-296.8,3040.5,515.1c317.8,317.8,561,723.7,670.1,1120.1c101.5,369.5,158.9,455.7,360,553.3c114.9,57.4,170.4,65.1,1487.7,229.8c752.5,93.8,1392,181.9,1420.7,193.4C8628.7-857.9,9900,1250.1,9900,1328.6c0,84.3-67,172.3-147.4,195.3c-51.7,15.3-790.8,19.1-2558,15.3l-2487.2-5.7l-55.5-63.2l-55.5-61.3v-344.6V719.8h-411.7h-411.7v325.5c0,509.3,11.5,499.7-616.5,494C2921,1537.3,2695.1,1533.5,2653,1529.7z\"/></g></g>\r\n</svg>\r\n</use>\r\n<h1>404</h1>\r\n<h2>not found</h2>\r\n</body>\r\n</html>\r\n', NULL, '2025-07-26 07:14:24'),
(30, 1, '639123456789', 'Test SMS with null user_id', 'failed', '<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\">\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"ie=edge\">\r\n  <link href=\"https://fonts.googleapis.com/css?family=Raleway:500,800\" rel=\"stylesheet\">\r\n  <title>PhilSMS</title>\r\n  <style>\r\n  * {\r\n  margin:0;\r\n  padding: 0;\r\n}\r\nbody{\r\n  background: #233142;\r\n  \r\n}\r\n.whistle{\r\n  width: 20%;\r\n  fill: #f95959;\r\n  margin: 100px 40%;\r\n  text-align: left;\r\n  transform: translate(-50%, -50%);\r\n  transform: rotate(0);\r\n  transform-origin: 80% 30%;\r\n  animation: wiggle .2s infinite;\r\n}\r\n\r\n@keyframes wiggle {\r\n  0%{\r\n    transform: rotate(3deg);\r\n  }\r\n  50%{\r\n    transform: rotate(0deg);\r\n  }\r\n  100%{\r\n    transform: rotate(3deg);\r\n  }\r\n}\r\nh1{\r\n  margin-top: -100px;\r\n  margin-bottom: 20px;\r\n  color: #facf5a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 90px;\r\n  font-weight: 800;\r\n}\r\nh2{\r\n  color: #455d7a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 30px;\r\n  text-transform: uppercase;\r\n}\r\n</style>\r\n</head>\r\n<body>\r\n  <use>\r\n  <svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\" class=\"whistle\">\r\n<metadata> Svg Vector Icons : http://www.onlinewebfonts.com/icon </metadata>\r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\">\r\n<path d=\"M4295.8,3963.2c-113-57.4-122.5-107.2-116.8-622.3l5.7-461.4l63.2-55.5c72.8-65.1,178.1-74.7,250.8-24.9c86.2,61.3,97.6,128.3,97.6,584c0,474.8-11.5,526.5-124.5,580.1C4393.4,4001.5,4372.4,4001.5,4295.8,3963.2z\"/><path d=\"M3053.1,3134.2c-68.9-42.1-111-143.6-93.8-216.4c7.7-26.8,216.4-250.8,476.8-509.3c417.4-417.4,469.1-463.4,526.5-463.4c128.3,0,212.5,88.1,212.5,224c0,67-26.8,97.6-434.6,509.3c-241.2,241.2-459.5,449.9-488.2,465.3C3181.4,3180.1,3124,3178.2,3053.1,3134.2z\"/><path d=\"M2653,1529.7C1644,1445.4,765.1,850,345.8-32.7C62.4-628.2,22.2-1317.4,234.8-1960.8C451.1-2621.3,947-3186.2,1584.6-3500.2c1018.6-501.6,2228.7-296.8,3040.5,515.1c317.8,317.8,561,723.7,670.1,1120.1c101.5,369.5,158.9,455.7,360,553.3c114.9,57.4,170.4,65.1,1487.7,229.8c752.5,93.8,1392,181.9,1420.7,193.4C8628.7-857.9,9900,1250.1,9900,1328.6c0,84.3-67,172.3-147.4,195.3c-51.7,15.3-790.8,19.1-2558,15.3l-2487.2-5.7l-55.5-63.2l-55.5-61.3v-344.6V719.8h-411.7h-411.7v325.5c0,509.3,11.5,499.7-616.5,494C2921,1537.3,2695.1,1533.5,2653,1529.7z\"/></g></g>\r\n</svg>\r\n</use>\r\n<h1>404</h1>\r\n<h2>not found</h2>\r\n</body>\r\n</html>\r\n', NULL, '2025-07-26 07:14:25'),
(31, 1, '639123456789', 'Test SMS with valid user_id', 'failed', '<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\">\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"ie=edge\">\r\n  <link href=\"https://fonts.googleapis.com/css?family=Raleway:500,800\" rel=\"stylesheet\">\r\n  <title>PhilSMS</title>\r\n  <style>\r\n  * {\r\n  margin:0;\r\n  padding: 0;\r\n}\r\nbody{\r\n  background: #233142;\r\n  \r\n}\r\n.whistle{\r\n  width: 20%;\r\n  fill: #f95959;\r\n  margin: 100px 40%;\r\n  text-align: left;\r\n  transform: translate(-50%, -50%);\r\n  transform: rotate(0);\r\n  transform-origin: 80% 30%;\r\n  animation: wiggle .2s infinite;\r\n}\r\n\r\n@keyframes wiggle {\r\n  0%{\r\n    transform: rotate(3deg);\r\n  }\r\n  50%{\r\n    transform: rotate(0deg);\r\n  }\r\n  100%{\r\n    transform: rotate(3deg);\r\n  }\r\n}\r\nh1{\r\n  margin-top: -100px;\r\n  margin-bottom: 20px;\r\n  color: #facf5a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 90px;\r\n  font-weight: 800;\r\n}\r\nh2{\r\n  color: #455d7a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 30px;\r\n  text-transform: uppercase;\r\n}\r\n</style>\r\n</head>\r\n<body>\r\n  <use>\r\n  <svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\" class=\"whistle\">\r\n<metadata> Svg Vector Icons : http://www.onlinewebfonts.com/icon </metadata>\r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\">\r\n<path d=\"M4295.8,3963.2c-113-57.4-122.5-107.2-116.8-622.3l5.7-461.4l63.2-55.5c72.8-65.1,178.1-74.7,250.8-24.9c86.2,61.3,97.6,128.3,97.6,584c0,474.8-11.5,526.5-124.5,580.1C4393.4,4001.5,4372.4,4001.5,4295.8,3963.2z\"/><path d=\"M3053.1,3134.2c-68.9-42.1-111-143.6-93.8-216.4c7.7-26.8,216.4-250.8,476.8-509.3c417.4-417.4,469.1-463.4,526.5-463.4c128.3,0,212.5,88.1,212.5,224c0,67-26.8,97.6-434.6,509.3c-241.2,241.2-459.5,449.9-488.2,465.3C3181.4,3180.1,3124,3178.2,3053.1,3134.2z\"/><path d=\"M2653,1529.7C1644,1445.4,765.1,850,345.8-32.7C62.4-628.2,22.2-1317.4,234.8-1960.8C451.1-2621.3,947-3186.2,1584.6-3500.2c1018.6-501.6,2228.7-296.8,3040.5,515.1c317.8,317.8,561,723.7,670.1,1120.1c101.5,369.5,158.9,455.7,360,553.3c114.9,57.4,170.4,65.1,1487.7,229.8c752.5,93.8,1392,181.9,1420.7,193.4C8628.7-857.9,9900,1250.1,9900,1328.6c0,84.3-67,172.3-147.4,195.3c-51.7,15.3-790.8,19.1-2558,15.3l-2487.2-5.7l-55.5-63.2l-55.5-61.3v-344.6V719.8h-411.7h-411.7v325.5c0,509.3,11.5,499.7-616.5,494C2921,1537.3,2695.1,1533.5,2653,1529.7z\"/></g></g>\r\n</svg>\r\n</use>\r\n<h1>404</h1>\r\n<h2>not found</h2>\r\n</body>\r\n</html>\r\n', NULL, '2025-07-26 07:15:16'),
(32, 1, '639123456789', 'Test SMS with null user_id', 'failed', '<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\">\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"ie=edge\">\r\n  <link href=\"https://fonts.googleapis.com/css?family=Raleway:500,800\" rel=\"stylesheet\">\r\n  <title>PhilSMS</title>\r\n  <style>\r\n  * {\r\n  margin:0;\r\n  padding: 0;\r\n}\r\nbody{\r\n  background: #233142;\r\n  \r\n}\r\n.whistle{\r\n  width: 20%;\r\n  fill: #f95959;\r\n  margin: 100px 40%;\r\n  text-align: left;\r\n  transform: translate(-50%, -50%);\r\n  transform: rotate(0);\r\n  transform-origin: 80% 30%;\r\n  animation: wiggle .2s infinite;\r\n}\r\n\r\n@keyframes wiggle {\r\n  0%{\r\n    transform: rotate(3deg);\r\n  }\r\n  50%{\r\n    transform: rotate(0deg);\r\n  }\r\n  100%{\r\n    transform: rotate(3deg);\r\n  }\r\n}\r\nh1{\r\n  margin-top: -100px;\r\n  margin-bottom: 20px;\r\n  color: #facf5a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 90px;\r\n  font-weight: 800;\r\n}\r\nh2{\r\n  color: #455d7a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 30px;\r\n  text-transform: uppercase;\r\n}\r\n</style>\r\n</head>\r\n<body>\r\n  <use>\r\n  <svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\" class=\"whistle\">\r\n<metadata> Svg Vector Icons : http://www.onlinewebfonts.com/icon </metadata>\r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\">\r\n<path d=\"M4295.8,3963.2c-113-57.4-122.5-107.2-116.8-622.3l5.7-461.4l63.2-55.5c72.8-65.1,178.1-74.7,250.8-24.9c86.2,61.3,97.6,128.3,97.6,584c0,474.8-11.5,526.5-124.5,580.1C4393.4,4001.5,4372.4,4001.5,4295.8,3963.2z\"/><path d=\"M3053.1,3134.2c-68.9-42.1-111-143.6-93.8-216.4c7.7-26.8,216.4-250.8,476.8-509.3c417.4-417.4,469.1-463.4,526.5-463.4c128.3,0,212.5,88.1,212.5,224c0,67-26.8,97.6-434.6,509.3c-241.2,241.2-459.5,449.9-488.2,465.3C3181.4,3180.1,3124,3178.2,3053.1,3134.2z\"/><path d=\"M2653,1529.7C1644,1445.4,765.1,850,345.8-32.7C62.4-628.2,22.2-1317.4,234.8-1960.8C451.1-2621.3,947-3186.2,1584.6-3500.2c1018.6-501.6,2228.7-296.8,3040.5,515.1c317.8,317.8,561,723.7,670.1,1120.1c101.5,369.5,158.9,455.7,360,553.3c114.9,57.4,170.4,65.1,1487.7,229.8c752.5,93.8,1392,181.9,1420.7,193.4C8628.7-857.9,9900,1250.1,9900,1328.6c0,84.3-67,172.3-147.4,195.3c-51.7,15.3-790.8,19.1-2558,15.3l-2487.2-5.7l-55.5-63.2l-55.5-61.3v-344.6V719.8h-411.7h-411.7v325.5c0,509.3,11.5,499.7-616.5,494C2921,1537.3,2695.1,1533.5,2653,1529.7z\"/></g></g>\r\n</svg>\r\n</use>\r\n<h1>404</h1>\r\n<h2>not found</h2>\r\n</body>\r\n</html>\r\n', NULL, '2025-07-26 07:15:18'),
(33, 1, '639123456789', 'Test SMS with valid user_id', 'failed', '<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\">\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"ie=edge\">\r\n  <link href=\"https://fonts.googleapis.com/css?family=Raleway:500,800\" rel=\"stylesheet\">\r\n  <title>PhilSMS</title>\r\n  <style>\r\n  * {\r\n  margin:0;\r\n  padding: 0;\r\n}\r\nbody{\r\n  background: #233142;\r\n  \r\n}\r\n.whistle{\r\n  width: 20%;\r\n  fill: #f95959;\r\n  margin: 100px 40%;\r\n  text-align: left;\r\n  transform: translate(-50%, -50%);\r\n  transform: rotate(0);\r\n  transform-origin: 80% 30%;\r\n  animation: wiggle .2s infinite;\r\n}\r\n\r\n@keyframes wiggle {\r\n  0%{\r\n    transform: rotate(3deg);\r\n  }\r\n  50%{\r\n    transform: rotate(0deg);\r\n  }\r\n  100%{\r\n    transform: rotate(3deg);\r\n  }\r\n}\r\nh1{\r\n  margin-top: -100px;\r\n  margin-bottom: 20px;\r\n  color: #facf5a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 90px;\r\n  font-weight: 800;\r\n}\r\nh2{\r\n  color: #455d7a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 30px;\r\n  text-transform: uppercase;\r\n}\r\n</style>\r\n</head>\r\n<body>\r\n  <use>\r\n  <svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\" class=\"whistle\">\r\n<metadata> Svg Vector Icons : http://www.onlinewebfonts.com/icon </metadata>\r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\">\r\n<path d=\"M4295.8,3963.2c-113-57.4-122.5-107.2-116.8-622.3l5.7-461.4l63.2-55.5c72.8-65.1,178.1-74.7,250.8-24.9c86.2,61.3,97.6,128.3,97.6,584c0,474.8-11.5,526.5-124.5,580.1C4393.4,4001.5,4372.4,4001.5,4295.8,3963.2z\"/><path d=\"M3053.1,3134.2c-68.9-42.1-111-143.6-93.8-216.4c7.7-26.8,216.4-250.8,476.8-509.3c417.4-417.4,469.1-463.4,526.5-463.4c128.3,0,212.5,88.1,212.5,224c0,67-26.8,97.6-434.6,509.3c-241.2,241.2-459.5,449.9-488.2,465.3C3181.4,3180.1,3124,3178.2,3053.1,3134.2z\"/><path d=\"M2653,1529.7C1644,1445.4,765.1,850,345.8-32.7C62.4-628.2,22.2-1317.4,234.8-1960.8C451.1-2621.3,947-3186.2,1584.6-3500.2c1018.6-501.6,2228.7-296.8,3040.5,515.1c317.8,317.8,561,723.7,670.1,1120.1c101.5,369.5,158.9,455.7,360,553.3c114.9,57.4,170.4,65.1,1487.7,229.8c752.5,93.8,1392,181.9,1420.7,193.4C8628.7-857.9,9900,1250.1,9900,1328.6c0,84.3-67,172.3-147.4,195.3c-51.7,15.3-790.8,19.1-2558,15.3l-2487.2-5.7l-55.5-63.2l-55.5-61.3v-344.6V719.8h-411.7h-411.7v325.5c0,509.3,11.5,499.7-616.5,494C2921,1537.3,2695.1,1533.5,2653,1529.7z\"/></g></g>\r\n</svg>\r\n</use>\r\n<h1>404</h1>\r\n<h2>not found</h2>\r\n</body>\r\n</html>\r\n', NULL, '2025-07-26 07:15:39'),
(34, 1, '639123456789', 'Test SMS with null user_id', 'failed', '<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\">\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"ie=edge\">\r\n  <link href=\"https://fonts.googleapis.com/css?family=Raleway:500,800\" rel=\"stylesheet\">\r\n  <title>PhilSMS</title>\r\n  <style>\r\n  * {\r\n  margin:0;\r\n  padding: 0;\r\n}\r\nbody{\r\n  background: #233142;\r\n  \r\n}\r\n.whistle{\r\n  width: 20%;\r\n  fill: #f95959;\r\n  margin: 100px 40%;\r\n  text-align: left;\r\n  transform: translate(-50%, -50%);\r\n  transform: rotate(0);\r\n  transform-origin: 80% 30%;\r\n  animation: wiggle .2s infinite;\r\n}\r\n\r\n@keyframes wiggle {\r\n  0%{\r\n    transform: rotate(3deg);\r\n  }\r\n  50%{\r\n    transform: rotate(0deg);\r\n  }\r\n  100%{\r\n    transform: rotate(3deg);\r\n  }\r\n}\r\nh1{\r\n  margin-top: -100px;\r\n  margin-bottom: 20px;\r\n  color: #facf5a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 90px;\r\n  font-weight: 800;\r\n}\r\nh2{\r\n  color: #455d7a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 30px;\r\n  text-transform: uppercase;\r\n}\r\n</style>\r\n</head>\r\n<body>\r\n  <use>\r\n  <svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\" class=\"whistle\">\r\n<metadata> Svg Vector Icons : http://www.onlinewebfonts.com/icon </metadata>\r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\">\r\n<path d=\"M4295.8,3963.2c-113-57.4-122.5-107.2-116.8-622.3l5.7-461.4l63.2-55.5c72.8-65.1,178.1-74.7,250.8-24.9c86.2,61.3,97.6,128.3,97.6,584c0,474.8-11.5,526.5-124.5,580.1C4393.4,4001.5,4372.4,4001.5,4295.8,3963.2z\"/><path d=\"M3053.1,3134.2c-68.9-42.1-111-143.6-93.8-216.4c7.7-26.8,216.4-250.8,476.8-509.3c417.4-417.4,469.1-463.4,526.5-463.4c128.3,0,212.5,88.1,212.5,224c0,67-26.8,97.6-434.6,509.3c-241.2,241.2-459.5,449.9-488.2,465.3C3181.4,3180.1,3124,3178.2,3053.1,3134.2z\"/><path d=\"M2653,1529.7C1644,1445.4,765.1,850,345.8-32.7C62.4-628.2,22.2-1317.4,234.8-1960.8C451.1-2621.3,947-3186.2,1584.6-3500.2c1018.6-501.6,2228.7-296.8,3040.5,515.1c317.8,317.8,561,723.7,670.1,1120.1c101.5,369.5,158.9,455.7,360,553.3c114.9,57.4,170.4,65.1,1487.7,229.8c752.5,93.8,1392,181.9,1420.7,193.4C8628.7-857.9,9900,1250.1,9900,1328.6c0,84.3-67,172.3-147.4,195.3c-51.7,15.3-790.8,19.1-2558,15.3l-2487.2-5.7l-55.5-63.2l-55.5-61.3v-344.6V719.8h-411.7h-411.7v325.5c0,509.3,11.5,499.7-616.5,494C2921,1537.3,2695.1,1533.5,2653,1529.7z\"/></g></g>\r\n</svg>\r\n</use>\r\n<h1>404</h1>\r\n<h2>not found</h2>\r\n</body>\r\n</html>\r\n', NULL, '2025-07-26 07:15:40'),
(35, 1, '639123456789', 'Test SMS from BM-SCaPIS system. Time: 2025-07-26 15:15:57', 'failed', '<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\">\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"ie=edge\">\r\n  <link href=\"https://fonts.googleapis.com/css?family=Raleway:500,800\" rel=\"stylesheet\">\r\n  <title>PhilSMS</title>\r\n  <style>\r\n  * {\r\n  margin:0;\r\n  padding: 0;\r\n}\r\nbody{\r\n  background: #233142;\r\n  \r\n}\r\n.whistle{\r\n  width: 20%;\r\n  fill: #f95959;\r\n  margin: 100px 40%;\r\n  text-align: left;\r\n  transform: translate(-50%, -50%);\r\n  transform: rotate(0);\r\n  transform-origin: 80% 30%;\r\n  animation: wiggle .2s infinite;\r\n}\r\n\r\n@keyframes wiggle {\r\n  0%{\r\n    transform: rotate(3deg);\r\n  }\r\n  50%{\r\n    transform: rotate(0deg);\r\n  }\r\n  100%{\r\n    transform: rotate(3deg);\r\n  }\r\n}\r\nh1{\r\n  margin-top: -100px;\r\n  margin-bottom: 20px;\r\n  color: #facf5a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 90px;\r\n  font-weight: 800;\r\n}\r\nh2{\r\n  color: #455d7a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 30px;\r\n  text-transform: uppercase;\r\n}\r\n</style>\r\n</head>\r\n<body>\r\n  <use>\r\n  <svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\" class=\"whistle\">\r\n<metadata> Svg Vector Icons : http://www.onlinewebfonts.com/icon </metadata>\r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\">\r\n<path d=\"M4295.8,3963.2c-113-57.4-122.5-107.2-116.8-622.3l5.7-461.4l63.2-55.5c72.8-65.1,178.1-74.7,250.8-24.9c86.2,61.3,97.6,128.3,97.6,584c0,474.8-11.5,526.5-124.5,580.1C4393.4,4001.5,4372.4,4001.5,4295.8,3963.2z\"/><path d=\"M3053.1,3134.2c-68.9-42.1-111-143.6-93.8-216.4c7.7-26.8,216.4-250.8,476.8-509.3c417.4-417.4,469.1-463.4,526.5-463.4c128.3,0,212.5,88.1,212.5,224c0,67-26.8,97.6-434.6,509.3c-241.2,241.2-459.5,449.9-488.2,465.3C3181.4,3180.1,3124,3178.2,3053.1,3134.2z\"/><path d=\"M2653,1529.7C1644,1445.4,765.1,850,345.8-32.7C62.4-628.2,22.2-1317.4,234.8-1960.8C451.1-2621.3,947-3186.2,1584.6-3500.2c1018.6-501.6,2228.7-296.8,3040.5,515.1c317.8,317.8,561,723.7,670.1,1120.1c101.5,369.5,158.9,455.7,360,553.3c114.9,57.4,170.4,65.1,1487.7,229.8c752.5,93.8,1392,181.9,1420.7,193.4C8628.7-857.9,9900,1250.1,9900,1328.6c0,84.3-67,172.3-147.4,195.3c-51.7,15.3-790.8,19.1-2558,15.3l-2487.2-5.7l-55.5-63.2l-55.5-61.3v-344.6V719.8h-411.7h-411.7v325.5c0,509.3,11.5,499.7-616.5,494C2921,1537.3,2695.1,1533.5,2653,1529.7z\"/></g></g>\r\n</svg>\r\n</use>\r\n<h1>404</h1>\r\n<h2>not found</h2>\r\n</body>\r\n</html>\r\n', NULL, '2025-07-26 07:15:57'),
(36, 1, '639123456789', 'Test SMS from BM-SCaPIS system. Time: 2025-07-26 15:16:20', 'failed', '<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\">\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"ie=edge\">\r\n  <link href=\"https://fonts.googleapis.com/css?family=Raleway:500,800\" rel=\"stylesheet\">\r\n  <title>PhilSMS</title>\r\n  <style>\r\n  * {\r\n  margin:0;\r\n  padding: 0;\r\n}\r\nbody{\r\n  background: #233142;\r\n  \r\n}\r\n.whistle{\r\n  width: 20%;\r\n  fill: #f95959;\r\n  margin: 100px 40%;\r\n  text-align: left;\r\n  transform: translate(-50%, -50%);\r\n  transform: rotate(0);\r\n  transform-origin: 80% 30%;\r\n  animation: wiggle .2s infinite;\r\n}\r\n\r\n@keyframes wiggle {\r\n  0%{\r\n    transform: rotate(3deg);\r\n  }\r\n  50%{\r\n    transform: rotate(0deg);\r\n  }\r\n  100%{\r\n    transform: rotate(3deg);\r\n  }\r\n}\r\nh1{\r\n  margin-top: -100px;\r\n  margin-bottom: 20px;\r\n  color: #facf5a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 90px;\r\n  font-weight: 800;\r\n}\r\nh2{\r\n  color: #455d7a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 30px;\r\n  text-transform: uppercase;\r\n}\r\n</style>\r\n</head>\r\n<body>\r\n  <use>\r\n  <svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\" class=\"whistle\">\r\n<metadata> Svg Vector Icons : http://www.onlinewebfonts.com/icon </metadata>\r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\">\r\n<path d=\"M4295.8,3963.2c-113-57.4-122.5-107.2-116.8-622.3l5.7-461.4l63.2-55.5c72.8-65.1,178.1-74.7,250.8-24.9c86.2,61.3,97.6,128.3,97.6,584c0,474.8-11.5,526.5-124.5,580.1C4393.4,4001.5,4372.4,4001.5,4295.8,3963.2z\"/><path d=\"M3053.1,3134.2c-68.9-42.1-111-143.6-93.8-216.4c7.7-26.8,216.4-250.8,476.8-509.3c417.4-417.4,469.1-463.4,526.5-463.4c128.3,0,212.5,88.1,212.5,224c0,67-26.8,97.6-434.6,509.3c-241.2,241.2-459.5,449.9-488.2,465.3C3181.4,3180.1,3124,3178.2,3053.1,3134.2z\"/><path d=\"M2653,1529.7C1644,1445.4,765.1,850,345.8-32.7C62.4-628.2,22.2-1317.4,234.8-1960.8C451.1-2621.3,947-3186.2,1584.6-3500.2c1018.6-501.6,2228.7-296.8,3040.5,515.1c317.8,317.8,561,723.7,670.1,1120.1c101.5,369.5,158.9,455.7,360,553.3c114.9,57.4,170.4,65.1,1487.7,229.8c752.5,93.8,1392,181.9,1420.7,193.4C8628.7-857.9,9900,1250.1,9900,1328.6c0,84.3-67,172.3-147.4,195.3c-51.7,15.3-790.8,19.1-2558,15.3l-2487.2-5.7l-55.5-63.2l-55.5-61.3v-344.6V719.8h-411.7h-411.7v325.5c0,509.3,11.5,499.7-616.5,494C2921,1537.3,2695.1,1533.5,2653,1529.7z\"/></g></g>\r\n</svg>\r\n</use>\r\n<h1>404</h1>\r\n<h2>not found</h2>\r\n</body>\r\n</html>\r\n', NULL, '2025-07-26 07:16:20'),
(37, 1, '639123456789', 'Test SMS from BM-SCaPIS system. Time: 2025-07-26 15:16:47', 'failed', '<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\">\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"ie=edge\">\r\n  <link href=\"https://fonts.googleapis.com/css?family=Raleway:500,800\" rel=\"stylesheet\">\r\n  <title>PhilSMS</title>\r\n  <style>\r\n  * {\r\n  margin:0;\r\n  padding: 0;\r\n}\r\nbody{\r\n  background: #233142;\r\n  \r\n}\r\n.whistle{\r\n  width: 20%;\r\n  fill: #f95959;\r\n  margin: 100px 40%;\r\n  text-align: left;\r\n  transform: translate(-50%, -50%);\r\n  transform: rotate(0);\r\n  transform-origin: 80% 30%;\r\n  animation: wiggle .2s infinite;\r\n}\r\n\r\n@keyframes wiggle {\r\n  0%{\r\n    transform: rotate(3deg);\r\n  }\r\n  50%{\r\n    transform: rotate(0deg);\r\n  }\r\n  100%{\r\n    transform: rotate(3deg);\r\n  }\r\n}\r\nh1{\r\n  margin-top: -100px;\r\n  margin-bottom: 20px;\r\n  color: #facf5a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 90px;\r\n  font-weight: 800;\r\n}\r\nh2{\r\n  color: #455d7a;\r\n  text-align: center;\r\n  font-family: \'Raleway\';\r\n  font-size: 30px;\r\n  text-transform: uppercase;\r\n}\r\n</style>\r\n</head>\r\n<body>\r\n  <use>\r\n  <svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\" viewBox=\"0 0 1000 1000\" enable-background=\"new 0 0 1000 1000\" xml:space=\"preserve\" class=\"whistle\">\r\n<metadata> Svg Vector Icons : http://www.onlinewebfonts.com/icon </metadata>\r\n<g><g transform=\"translate(0.000000,511.000000) scale(0.100000,-0.100000)\">\r\n<path d=\"M4295.8,3963.2c-113-57.4-122.5-107.2-116.8-622.3l5.7-461.4l63.2-55.5c72.8-65.1,178.1-74.7,250.8-24.9c86.2,61.3,97.6,128.3,97.6,584c0,474.8-11.5,526.5-124.5,580.1C4393.4,4001.5,4372.4,4001.5,4295.8,3963.2z\"/><path d=\"M3053.1,3134.2c-68.9-42.1-111-143.6-93.8-216.4c7.7-26.8,216.4-250.8,476.8-509.3c417.4-417.4,469.1-463.4,526.5-463.4c128.3,0,212.5,88.1,212.5,224c0,67-26.8,97.6-434.6,509.3c-241.2,241.2-459.5,449.9-488.2,465.3C3181.4,3180.1,3124,3178.2,3053.1,3134.2z\"/><path d=\"M2653,1529.7C1644,1445.4,765.1,850,345.8-32.7C62.4-628.2,22.2-1317.4,234.8-1960.8C451.1-2621.3,947-3186.2,1584.6-3500.2c1018.6-501.6,2228.7-296.8,3040.5,515.1c317.8,317.8,561,723.7,670.1,1120.1c101.5,369.5,158.9,455.7,360,553.3c114.9,57.4,170.4,65.1,1487.7,229.8c752.5,93.8,1392,181.9,1420.7,193.4C8628.7-857.9,9900,1250.1,9900,1328.6c0,84.3-67,172.3-147.4,195.3c-51.7,15.3-790.8,19.1-2558,15.3l-2487.2-5.7l-55.5-63.2l-55.5-61.3v-344.6V719.8h-411.7h-411.7v325.5c0,509.3,11.5,499.7-616.5,494C2921,1537.3,2695.1,1533.5,2653,1529.7z\"/></g></g>\r\n</svg>\r\n</use>\r\n<h1>404</h1>\r\n<h2>not found</h2>\r\n</body>\r\n</html>\r\n', NULL, '2025-07-26 07:16:47'),
(39, 1, '639123456789', 'Debug test SMS from BM-SCaPIS - 2025-07-26 15:18:06', 'failed', '{\"endpoint\":\"https:\\/\\/philsms.com\\/api\\/sms\\/send\",\"http_code\":404,\"response\":\"<!doctype html>\\n<html class=\\\"no-js\\\" lang=\\\"en\\\">\\n\\n<head>\\n    <meta charset=\\\"UTF-8\\\">\\n    <meta name=\\\"description\\\" content=\\\"\\\">\\n    <meta http-equiv=\\\"X-UA-Compatible\\\" content=\\\"IE=edge\\\">\\n    <meta name=\\\"viewport\\\" content=\\\"width=device-width, initial-scale=1, shrink-to-fit=no\\\">\\n    <!-- The above 4 meta tags *must* come first in the head; any other head content must come *after* these tags -->\\n\\n    <!-- Title  -->\\n    <title>PhilSMS | Branded SMS Gateway to the Philippines<\\/title>\\n\\n    <!-- Favicon  --\",\"curl_error\":\"\",\"data_sent\":{\"recipient\":\"639123456789\",\"message\":\"Debug test SMS from BM-SCaPIS - 2025-07-26 15:18:06\",\"sender_name\":\"PhilSMS\"}}', NULL, '2025-07-26 07:18:06'),
(40, 1, '639123456789', 'Test unified SMS function', 'sent', '{\"success\":true,\"message\":\"SMS sent successfully\",\"reference_id\":null,\"delivery_status\":\"success\",\"timestamp\":\"2025-07-26 4:44 PM\"}', '2025-07-26 08:44:36', '2025-07-26 08:44:33'),
(43, 1, '639677726912', 'Test admin notification from unified SMS system', 'sent', '{\"success\":true,\"message\":\"SMS sent successfully\",\"reference_id\":null,\"delivery_status\":\"success\",\"timestamp\":\"2025-07-26 4:44 PM\"}', '2025-07-26 08:44:43', '2025-07-26 08:44:41'),
(52, 30, '09677726917', 'Congratulations! Your BM-SCaPIS registration has been approved.\n\nUsername: janpe01297\nPassword: bmscapis2025\n\nYou can now log in to your account and apply for documents.', 'sent', '{\"success\":true,\"message\":\"SMS sent successfully\",\"reference_id\":null,\"delivery_status\":\"success\",\"timestamp\":\"2025-07-27 7:31 PM\"}', '2025-07-27 11:31:09', '2025-07-27 11:31:06'),
(53, 30, '09677726917', 'GCash payment received for application #APP-20250728-8520. Amount: ₱30.00. Reference: GC17536719835758 Your Certificate of Residency is now being processed.', 'failed', '{\"success\":false,\"message\":\"API Error: Telco Issues\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"Telco Issues\"}}', NULL, '2025-07-28 03:08:54'),
(54, 30, '09677726917', 'GCash payment received for application #APP-20250728-7081. Amount: ₱50.00. Reference: GC17536724943183 Your Barangay Clearance is now being processed.', 'failed', '{\"success\":false,\"message\":\"API Error: Telco Issues\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"Telco Issues\"}}', NULL, '2025-07-28 03:15:38'),
(55, 30, '09677726917', 'Hi Jan Russelsss, your payment appointment for Certificate of Residency application #APP-20250728-8474 has been scheduled for Jul 30, 2025 11:47 AM. Please bring ₱30.00 for payment.', 'pending', NULL, NULL, '2025-07-28 03:47:57'),
(56, 30, '09677726917', 'Your Certificate of Residency (#APP-20250728-8520) is ready for pickup. Please visit the barangay office on Jul 29, 2025.', 'sent', '{\"success\":true,\"message\":\"SMS sent successfully\",\"reference_id\":null,\"delivery_status\":\"success\",\"timestamp\":\"2025-07-28 12:13 PM\"}', '2025-07-28 04:13:52', '2025-07-28 04:13:49'),
(57, 30, '09677726917', 'Your Barangay Clearance (#APP-20250728-7081) is ready for pickup. Please visit the barangay office on Jul 30, 2025.', 'sent', '{\"success\":true,\"message\":\"SMS sent successfully\",\"reference_id\":null,\"delivery_status\":\"success\",\"timestamp\":\"2025-07-28 12:27 PM\"}', '2025-07-28 04:27:51', '2025-07-28 04:27:48'),
(58, 1, '09123456789', 'Test SMS from BM-SCaPIS system. Time: 2025-08-03 15:53:26', 'sent', '{\"success\":true,\"message\":\"SMS sent successfully\",\"reference_id\":null,\"delivery_status\":\"success\",\"timestamp\":\"2025-08-03 3:53 PM\"}', '2025-08-03 07:53:30', '2025-08-03 07:53:26'),
(59, 30, '09677726917', 'GCash payment received for application #APP-20250728-8474. Amount: ₱30.00. Reference: GC17561275005981 Your Certificate of Residency is now being processed.', 'failed', '{\"success\":false,\"message\":\"API Error: There is not enough balance available to send this message.You have 0.3 of the 1.05\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"There is not enough balance available to send this message.You have 0.3 of the 1.05\"}}', NULL, '2025-08-25 13:12:18'),
(60, 30, '09677726917', 'Hi Jan Russelsss, your payment appointment for Barangay Clearance application #APP-20251005-4518 has been scheduled for Oct 16, 2025 1:44 PM. Please bring ₱50.00 for payment.', 'failed', '{\"success\":false,\"message\":\"API Error: There is not enough balance available to send this message.You have 0.3 of the 1.05\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"There is not enough balance available to send this message.You have 0.3 of the 1.05\"}}', NULL, '2025-10-05 05:44:55'),
(61, 30, '09677726917', 'Hi Jan Russelsss, your Payment appointment for Barangay Clearance application #APP-20251005-4518 has been completed successfully. Thank you for visiting the barangay office.', 'failed', '{\"success\":false,\"message\":\"API Error: There is not enough balance available to send this message.You have 0.3 of the 0.7\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"There is not enough balance available to send this message.You have 0.3 of the 0.7\"}}', NULL, '2025-10-05 05:45:35'),
(62, 30, '09677726917', 'Hi Jan Russelsss, your Pickup appointment for Certificate of Residency application #APP-20250728-8520 has been rescheduled to Oct 6, 2025 1:48 PM. Note: sdfsfs', 'failed', '{\"success\":false,\"message\":\"API Error: There is not enough balance available to send this message.You have 0.3 of the 0.35\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"There is not enough balance available to send this message.You have 0.3 of the 0.35\"}}', NULL, '2025-10-05 05:48:08'),
(63, 30, '09677726917', 'Hi Jan Russelsss, your payment appointment for Barangay Clearance application #APP-20251005-4518 has been scheduled for Oct 6, 2025 1:48 PM. Please bring ₱50.00 for payment.', 'failed', '{\"success\":false,\"message\":\"API Error: There is not enough balance available to send this message.You have 0.3 of the 1.05\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"There is not enough balance available to send this message.You have 0.3 of the 1.05\"}}', NULL, '2025-10-05 05:48:41'),
(64, 30, '09677726917', 'Your application #APP-20251005-7148 payment has been waived. Your Certificate of Indigency application is now being processed.', 'failed', '{\"success\":false,\"message\":\"API Error: There is not enough balance available to send this message.You have 0.3 of the 0.35\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"There is not enough balance available to send this message.You have 0.3 of the 0.35\"}}', NULL, '2025-10-06 05:58:10'),
(65, 5, '09677726912', 'Dear Jan Russel, you have been removed from your position as purok leader of Purok 1. Reason: sfsd', 'pending', NULL, NULL, '2025-10-06 06:03:09'),
(66, 30, '09677726917', 'Dear Jan Russelsss, you have been assigned as the leader of Purok 1. Please log in to your account to manage your purok.', 'pending', NULL, NULL, '2025-10-06 06:03:16'),
(67, 31, '09942270388', 'Congratulations! Your BM-SCaPIS registration has been approved.\n\nUsername: janruss01159\nPassword: bmscapis2025\n\nYou can now log in to your account and apply for documents.', 'failed', '{\"success\":false,\"message\":\"API Error: There is not enough balance available to send this message.You have 0.3 of the 0.7\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"There is not enough balance available to send this message.You have 0.3 of the 0.7\"}}', NULL, '2025-10-06 06:10:22'),
(68, 5, '09677726912', 'Hi Jan Russel, your payment appointment for Business Permit application #APP-20251102-1293 has been scheduled for Nov 4, 2025 1:13 PM. Please bring ₱200.00 for payment.', 'failed', '{\"success\":false,\"message\":\"API Error: There is not enough balance available to send this message.You have 0.3 of the 1.05\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"There is not enough balance available to send this message.You have 0.3 of the 1.05\"}}', NULL, '2025-11-02 05:13:51'),
(69, 5, '09677726912', 'Hi Jan Russel, your advance payment of ₱200.00 for your Business Permit application #APP-20251102-1293 has been confirmed. Your application is now being processed. You will be notified when it\'s ready for pickup.', 'failed', '{\"success\":false,\"message\":\"API Error: There is not enough balance available to send this message.You have 0.3 of the 1.4\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"There is not enough balance available to send this message.You have 0.3 of the 1.4\"}}', NULL, '2025-11-02 05:30:30'),
(70, 5, '09677726912', 'Hi Jan Russel, your payment appointment for Building Permit application #APP-20251102-8175 has been scheduled for Nov 6, 2025 1:54 PM. Please bring ₱500.00 for payment.', 'failed', '{\"success\":false,\"message\":\"API Error: There is not enough balance available to send this message.You have 0.3 of the 1.05\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"There is not enough balance available to send this message.You have 0.3 of the 1.05\"}}', NULL, '2025-11-02 05:54:25'),
(71, 5, '09677726912', 'Hi , your payment appointment for Building Permit application #APP-20251102-8175 has been marked as completed. You can now proceed with payment through the system.', 'pending', NULL, NULL, '2025-11-02 05:54:43'),
(72, 30, '09677726917', 'Your application #APP-20251005-4518 payment has been waived. Your Barangay Clearance application is now being processed.', 'failed', '{\"success\":false,\"message\":\"API Error: There is not enough balance available to send this message.You have 0.3 of the 0.35\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"There is not enough balance available to send this message.You have 0.3 of the 0.35\"}}', NULL, '2025-11-02 06:01:35'),
(73, 5, '09677726912', 'Hi Jan Russel, your payment for Building Permit application #APP-20251102-8175 has been confirmed. Your document is now being processed (3-5 working days).', 'pending', NULL, NULL, '2025-11-02 06:04:30'),
(74, 5, '09677726912', 'Hi Jan Russel, your payment appointment for Certificate of Residency application #APP-20251102-4189 has been scheduled for Nov 8, 2025 2:06 PM. Please bring ₱30.00 for payment.', 'failed', '{\"success\":false,\"message\":\"API Error: There is not enough balance available to send this message.You have 0.3 of the 1.05\",\"error_code\":403,\"error_details\":{\"status\":\"error\",\"message\":\"There is not enough balance available to send this message.You have 0.3 of the 1.05\"}}', NULL, '2025-11-02 06:06:19'),
(75, 5, '09677726912', 'Hi , your payment appointment for Certificate of Residency application #APP-20251102-4189 has been marked as completed. You can now proceed with payment through the system.', 'pending', NULL, NULL, '2025-11-02 06:06:26');

-- --------------------------------------------------------

--
-- Table structure for table `support_chat_files`
--

CREATE TABLE `support_chat_files` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_messages`
--

CREATE TABLE `support_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_role` enum('admin','purok_leader','resident','system') NOT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `recipient_role` enum('admin','purok_leader','resident','all') DEFAULT NULL,
  `message` text NOT NULL,
  `file_url` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `support_messages`
--

INSERT INTO `support_messages` (`id`, `sender_id`, `sender_role`, `recipient_id`, `recipient_role`, `message`, `file_url`, `is_read`, `created_at`, `updated_at`) VALUES
(18, 5, 'resident', NULL, 'admin', 'Hsbs', NULL, 1, '2025-11-02 06:58:25', '2025-11-02 06:58:58'),
(19, 1, 'admin', NULL, 'resident', 'efsd', NULL, 1, '2025-11-02 06:58:35', '2025-11-02 06:58:46'),
(20, 5, 'resident', NULL, 'admin', 'Jdjd', NULL, 1, '2025-11-02 06:58:54', '2025-11-02 06:58:58'),
(21, 1, 'admin', NULL, 'resident', 'jg', NULL, 1, '2025-11-02 06:59:09', '2025-11-02 07:00:02'),
(22, 5, 'resident', NULL, 'admin', 'Jdj', NULL, 1, '2025-11-02 06:59:13', '2025-11-02 07:00:36'),
(23, 1, 'admin', NULL, 'resident', 'ghg', NULL, 1, '2025-11-02 06:59:17', '2025-11-02 07:00:02'),
(24, 5, 'resident', NULL, 'admin', 'Ndjdj', NULL, 1, '2025-11-02 06:59:22', '2025-11-02 07:00:36'),
(25, 1, 'admin', NULL, 'resident', 'fsdfs', NULL, 1, '2025-11-02 07:04:26', '2025-11-02 07:04:59'),
(26, 5, 'resident', NULL, 'admin', 'Okay admin', NULL, 1, '2025-11-02 07:04:34', '2025-11-02 07:04:46'),
(27, 1, 'admin', NULL, 'resident', 'asdada', NULL, 1, '2025-11-02 07:10:44', '2025-11-02 07:10:47'),
(28, 5, 'resident', NULL, 'admin', 'Hello jm not', NULL, 1, '2025-11-02 07:10:53', '2025-11-02 07:11:05'),
(29, 1, 'admin', NULL, 'resident', 'wqdasda', NULL, 1, '2025-11-02 07:10:59', '2025-11-02 07:11:01'),
(30, 1, 'admin', NULL, 'resident', 'asdada', NULL, 1, '2025-11-02 07:11:10', '2025-11-02 07:11:23'),
(31, 5, 'resident', NULL, 'admin', 'Jdjdjs', NULL, 1, '2025-11-02 07:11:14', '2025-11-02 07:11:35'),
(32, 1, 'admin', NULL, 'resident', 'jgjh', NULL, 1, '2025-11-02 07:11:17', '2025-11-02 07:11:23'),
(33, 5, 'resident', NULL, 'admin', 'Usheh', NULL, 1, '2025-11-02 07:11:21', '2025-11-02 07:11:35'),
(34, 1, 'admin', NULL, 'resident', 'hbjh', NULL, 0, '2025-11-02 07:16:30', '2025-11-02 07:16:30'),
(35, 5, 'resident', NULL, 'admin', 'Hello', NULL, 1, '2025-11-02 07:16:34', '2025-11-02 07:16:49'),
(36, 1, 'admin', NULL, 'resident', 'jhghj', NULL, 0, '2025-11-02 07:16:37', '2025-11-02 07:16:37'),
(37, 1, 'admin', NULL, 'resident', 'asdada', NULL, 0, '2025-11-03 07:22:02', '2025-11-03 07:22:02'),
(38, 1, 'admin', NULL, 'resident', 'asda', NULL, 0, '2025-11-03 07:22:03', '2025-11-03 07:22:03'),
(39, 1, 'admin', NULL, 'resident', 'd', NULL, 0, '2025-11-03 07:22:04', '2025-11-03 07:22:04'),
(40, 1, 'admin', NULL, 'resident', 'a', NULL, 0, '2025-11-03 07:22:04', '2025-11-03 07:22:04'),
(41, 1, 'admin', NULL, 'resident', 'd', NULL, 0, '2025-11-03 07:22:04', '2025-11-03 07:22:04'),
(42, 1, 'admin', NULL, 'resident', 'as', NULL, 0, '2025-11-03 07:22:04', '2025-11-03 07:22:04'),
(43, 1, 'admin', NULL, 'resident', 'd', NULL, 0, '2025-11-03 07:22:04', '2025-11-03 07:22:04'),
(44, 1, 'admin', NULL, 'resident', 'a', NULL, 0, '2025-11-03 07:22:04', '2025-11-03 07:22:04'),
(45, 1, 'admin', NULL, 'resident', 'd', NULL, 0, '2025-11-03 07:22:05', '2025-11-03 07:22:05'),
(46, 1, 'admin', NULL, 'resident', 'a', NULL, 0, '2025-11-03 07:22:05', '2025-11-03 07:22:05'),
(47, 1, 'admin', NULL, 'resident', 'd', NULL, 0, '2025-11-03 07:22:05', '2025-11-03 07:22:05'),
(48, 1, 'admin', NULL, 'resident', 'sda', NULL, 0, '2025-11-03 07:22:05', '2025-11-03 07:22:05'),
(49, 1, 'admin', NULL, 'resident', 'asdada', NULL, 0, '2025-11-03 09:23:43', '2025-11-03 09:23:43'),
(50, 1, 'admin', NULL, 'resident', 'asdada', NULL, 0, '2025-11-03 09:24:09', '2025-11-03 09:24:09'),
(51, 1, 'admin', NULL, 'resident', 'asdada', NULL, 0, '2025-11-03 09:48:18', '2025-11-03 09:48:18');

-- --------------------------------------------------------

--
-- Table structure for table `support_typing_indicators`
--

CREATE TABLE `support_typing_indicators` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_role` enum('admin','purok_leader','resident') NOT NULL,
  `is_typing` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `support_typing_indicators`
--

INSERT INTO `support_typing_indicators` (`id`, `user_id`, `user_role`, `is_typing`, `updated_at`) VALUES
(230, 1, 'admin', 1, '2025-11-03 09:48:17');

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
(5, 'ringtone_enabled', '1', '2025-07-24 02:40:48', '2025-07-24 02:40:48'),
(6, 'gcash_number', '09677726912', '2025-07-28 03:05:31', '2025-07-28 03:05:31'),
(7, 'gcash_account_name', 'Jan Russel Elizares Peñafiel', '2025-07-28 03:05:31', '2025-07-28 03:05:31'),
(8, 'gcash_enabled', '1', '2025-07-28 03:05:31', '2025-07-28 03:05:31');

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
(52, 'registration_processed', 'Purok Leader Disapproved Registration', 'Purok leader Jan Russel Peñafiel has disapproved the registration of Jan Russel Peñafiel', 'admin', NULL, 0, '{\"user_id\":21,\"purok_id\":1,\"action\":\"disapprove\"}', '2025-07-24 09:21:32', NULL),
(62, 'registration_disapproved', 'Registration Disapproved', 'Registration for Jan Russel Peñafiel has been disapproved by Purok Leader (Jan Russel Peñafiel)', 'admin', NULL, 0, '{\"user_id\":\"20\",\"disapproved_by\":5,\"disapproved_at\":\"2025-07-24 17:44:42\",\"remarks\":\"Not a resident of this purok\"}', '2025-07-24 09:44:42', NULL),
(63, 'registration_disapproved', 'Registration Disapproved', 'Registration for Jan Russel Peñafiel has been disapproved by Purok Leader (Jan Russel Peñafiel)', 'admin', NULL, 0, '{\"user_id\":\"19\",\"disapproved_by\":5,\"disapproved_at\":\"2025-07-24 17:46:17\",\"remarks\":\"Invalid documentation provided\"}', '2025-07-24 09:46:17', NULL),
(64, 'registration_disapproved', 'Registration Disapproved', 'Registration for Jan Russel Peñafiel has been disapproved by Admin (System Administrator)', 'admin', NULL, 0, '{\"user_id\":\"21\",\"disapproved_by\":1,\"disapproved_at\":\"2025-07-25 15:08:09\",\"remarks\":\"Not a resident of this purok\"}', '2025-07-25 07:08:09', NULL),
(65, 'new_registration', 'New Resident Registration', 'New registration from Jan Russel Peñafiel', 'admin', NULL, 1, '{\"user_id\": 23, \"purok_id\": 1}', '2025-07-25 10:25:12', '2025-07-25 10:53:04'),
(66, 'new_registration', 'New Resident Registration in Your Purok', 'New registration from Jan Russel Peñafiel in your purok', 'purok_leader', NULL, 0, '{\"user_id\": 23, \"purok_id\": 1}', '2025-07-25 10:25:12', NULL),
(68, 'new_registration', 'New Resident Registration', 'New registration from Jan Russel Peñafiel', 'admin', NULL, 0, '{\"user_id\": 24, \"purok_id\": 1}', '2025-07-25 11:44:55', NULL),
(69, 'new_registration', 'New Resident Registration in Your Purok', 'New registration from Jan Russel Peñafiel in your purok', 'purok_leader', NULL, 0, '{\"user_id\": 24, \"purok_id\": 1}', '2025-07-25 11:44:55', NULL),
(72, 'application_submitted', 'New Document Application', 'New application APP-20250726-4446 submitted by Jan Russel Peñafiel', 'admin', NULL, 0, '{\"application_id\":\"2\"}', '2025-07-26 06:58:17', NULL),
(76, 'document_ready_admin', 'Document Ready for Pickup', 'Document for Jan Russel Peñafiel is ready for pickup.', 'admin', NULL, 0, '{\"application_id\":\"2\",\"application_number\":\"APP-20250726-4446\",\"resident_name\":\"Jan Russel Pe\\u00f1afiel\",\"pickup_date\":\"2025-07-28 00:00:00\"}', '2025-07-26 08:45:58', NULL),
(77, 'new_registration', 'New Resident Registration', 'New registration from Jan Russessl Peñafielss', 'admin', NULL, 0, '{\"user_id\": 25, \"purok_id\": 1}', '2025-07-26 09:19:33', NULL),
(78, 'new_registration', 'New Resident Registration in Your Purok', 'New registration from Jan Russessl Peñafielss in your purok', 'purok_leader', NULL, 0, '{\"user_id\": 25, \"purok_id\": 1}', '2025-07-26 09:19:33', NULL),
(81, 'application_submitted', 'New Document Application', 'New application APP-20250726-1955 submitted by Jan Russessl Peñafielss', 'admin', NULL, 0, '{\"application_id\":\"3\"}', '2025-07-26 09:21:55', NULL),
(83, 'resident_deleted', 'Resident Deleted', 'Resident Jan Russessl Peñafielss has been deleted by System Administrator', 'admin', NULL, 0, '{\"deleted_user_id\":25,\"deleted_by\":1,\"deleted_at\":\"2025-07-26 17:30:01\",\"purok_id\":1}', '2025-07-26 09:30:01', NULL),
(84, 'new_registration', 'New Resident Registration', 'New registration from Jan Russelssss Peñafielsss', 'admin', NULL, 0, '{\"user_id\": 26, \"purok_id\": 1}', '2025-07-26 09:31:30', NULL),
(85, 'new_registration', 'New Resident Registration in Your Purok', 'New registration from Jan Russelssss Peñafielsss in your purok', 'purok_leader', NULL, 0, '{\"user_id\": 26, \"purok_id\": 1}', '2025-07-26 09:31:30', NULL),
(86, 'new_registration', 'New Resident Registration', 'New registration from Jan Russelssss Peñafielsss', 'admin', NULL, 0, '{\"user_id\": 27, \"purok_id\": 1}', '2025-07-26 09:31:41', NULL),
(87, 'new_registration', 'New Resident Registration in Your Purok', 'New registration from Jan Russelssss Peñafielsss in your purok', 'purok_leader', NULL, 0, '{\"user_id\": 27, \"purok_id\": 1}', '2025-07-26 09:31:41', NULL),
(88, 'new_registration', 'New Resident Registration', 'New registration from Jan Russelssss Peñafielsss', 'admin', NULL, 0, '{\"user_id\": 28, \"purok_id\": 1}', '2025-07-26 09:32:08', NULL),
(89, 'new_registration', 'New Resident Registration in Your Purok', 'New registration from Jan Russelssss Peñafielsss in your purok', 'purok_leader', NULL, 0, '{\"user_id\": 28, \"purok_id\": 1}', '2025-07-26 09:32:08', NULL),
(92, 'application_submitted', 'New Document Application', 'New application APP-20250726-5724 submitted by Jan Russel Peñafiel', 'admin', NULL, 0, '{\"application_id\":\"4\"}', '2025-07-26 09:42:16', NULL),
(96, 'document_ready_admin', 'Document Ready for Pickup', 'Document for Jan Russel Peñafiel is ready for pickup.', 'admin', NULL, 0, '{\"application_id\":\"4\",\"application_number\":\"APP-20250726-5724\",\"resident_name\":\"Jan Russel Pe\\u00f1afiel\",\"pickup_date\":\"2025-07-31 17:44:00\"}', '2025-07-26 09:44:15', NULL),
(97, 'application_submitted', 'New Document Application', 'New application APP-20250726-8481 submitted by Jan Russel Peñafiel', 'admin', NULL, 0, '{\"application_id\":\"5\"}', '2025-07-26 09:49:47', NULL),
(98, 'new_registration', 'New Resident Registration', 'New registration from Jan Russelsss Peñafielss', 'admin', NULL, 0, '{\"user_id\": 29, \"purok_id\": 1}', '2025-07-26 10:17:36', NULL),
(99, 'new_registration', 'New Resident Registration in Your Purok', 'New registration from Jan Russelsss Peñafielss in your purok', 'purok_leader', NULL, 0, '{\"user_id\": 29, \"purok_id\": 1}', '2025-07-26 10:17:36', NULL),
(102, 'resident_deleted', 'Resident Deleted', 'Resident Jan Russelsss Peñafielss has been deleted by System Administrator', 'admin', NULL, 0, '{\"deleted_user_id\":29,\"deleted_by\":1,\"deleted_at\":\"2025-07-26 22:30:53\",\"purok_id\":1}', '2025-07-26 14:30:53', NULL),
(103, 'new_registration', 'New Resident Registration', 'New registration from Jan Russelsss Peñafielsss', 'admin', NULL, 0, '{\"user_id\": 30, \"purok_id\": 1}', '2025-07-26 14:32:28', NULL),
(104, 'new_registration', 'New Resident Registration in Your Purok', 'New registration from Jan Russelsss Peñafielsss in your purok', 'purok_leader', NULL, 0, '{\"user_id\": 30, \"purok_id\": 1}', '2025-07-26 14:32:28', NULL),
(105, 'resident_deleted', 'Resident Deleted', 'Resident Jan Russel Peñafiel has been deleted by System Administrator', 'admin', NULL, 0, '{\"deleted_user_id\":24,\"deleted_by\":1,\"deleted_at\":\"2025-07-26 22:34:02\",\"purok_id\":1}', '2025-07-26 14:34:02', NULL),
(106, 'registration_approved', 'Registration Approved', 'Your registration has been approved by Purok Leader (Jan Russel Peñafiel)', '', 30, 0, '{\"approved_by\":5,\"approved_at\":\"2025-07-27 19:28:48\",\"remarks\":\"asdada\"}', '2025-07-27 11:28:48', NULL),
(107, 'registration_approved', 'Registration Approved', 'Your registration has been fully approved (System Administrator)', '', 30, 0, '{\"approved_by\":1,\"approved_at\":\"2025-07-27 19:31:09\",\"remarks\":\"asda\"}', '2025-07-27 11:31:09', NULL),
(108, 'application_submitted', 'New Document Application', 'New application APP-20250728-8520 submitted by Jan Russelsss Peñafielsss', 'admin', NULL, 0, '{\"application_id\":\"6\"}', '2025-07-28 00:53:53', NULL),
(109, 'application_submitted', 'New Document Application', 'New application APP-20250728-7081 submitted by Jan Russelsss Peñafielsss', 'admin', NULL, 0, '{\"application_id\":\"7\"}', '2025-07-28 03:10:01', NULL),
(110, 'application_submitted', 'New Document Application', 'New application APP-20250728-8474 submitted by Jan Russelsss Peñafielsss', 'admin', NULL, 0, '{\"application_id\":\"8\"}', '2025-07-28 03:16:23', NULL),
(111, 'payment_appointment_scheduled', 'Payment Appointment Scheduled - Application #APP-20250728-8474', '\r\n        Dear Jan Russelsss Peñafielsss,\r\n        \r\n        Your payment appointment for the following application has been scheduled:\r\n        \r\n        Application Details:\r\n        - Application Number: APP-20250728-8474\r\n        - Document Type: Certificate of Residency\r\n        - Amount Due: ₱30.00\r\n        - Appointment Date: July 30, 2025 11:47 AM\r\n        \r\n        Please bring the exact amount for payment. If you have any questions, please contact the barangay office.\r\n        \r\n        Thank you,\r\n        Barangay Malangit Administration\r\n        ', '', 30, 0, '{\"application_id\":\"8\",\"appointment_id\":\"3\",\"appointment_date\":\"2025-07-30T11:47\",\"amount\":\"30.00\"}', '2025-07-28 03:47:57', NULL),
(112, 'document_ready', 'Document Ready for Pickup', 'Your Certificate of Residency is ready for pickup.', '', 30, 0, '{\"application_id\":\"6\",\"application_number\":\"APP-20250728-8520\",\"pickup_date\":\"2025-07-31 12:13:00\",\"remarks\":\"sadada\"}', '2025-07-28 04:13:52', NULL),
(113, 'document_ready_admin', 'Document Ready for Pickup', 'Document for Jan Russelsss Peñafielsss is ready for pickup.', 'admin', NULL, 0, '{\"application_id\":\"6\",\"application_number\":\"APP-20250728-8520\",\"resident_name\":\"Jan Russelsss Pe\\u00f1afielsss\",\"pickup_date\":\"2025-07-31 12:13:00\"}', '2025-07-28 04:13:52', NULL),
(114, 'document_ready', 'Document Ready for Pickup', 'Your Barangay Clearance is ready for pickup.', '', 30, 0, '{\"application_id\":\"7\",\"application_number\":\"APP-20250728-7081\",\"pickup_date\":\"2025-07-30 12:27:00\",\"remarks\":\"asdadada\"}', '2025-07-28 04:27:51', NULL),
(115, 'document_ready_admin', 'Document Ready for Pickup', 'Document for Jan Russelsss Peñafielsss is ready for pickup.', 'admin', NULL, 0, '{\"application_id\":\"7\",\"application_number\":\"APP-20250728-7081\",\"resident_name\":\"Jan Russelsss Pe\\u00f1afielsss\",\"pickup_date\":\"2025-07-30 12:27:00\"}', '2025-07-28 04:27:51', NULL),
(116, 'application_submitted', 'New Document Application', 'New application APP-20251005-4518 submitted by Jan Russelsss Peñafielsss', 'admin', NULL, 0, '{\"application_id\":\"9\"}', '2025-10-05 05:44:25', NULL),
(117, 'payment_appointment_scheduled', 'Payment Appointment Scheduled - Application #APP-20251005-4518', '\r\n        Dear Jan Russelsss Peñafielsss,\r\n        \r\n        Your payment appointment for the following application has been scheduled:\r\n        \r\n        Application Details:\r\n        - Application Number: APP-20251005-4518\r\n        - Document Type: Barangay Clearance\r\n        - Amount Due: ₱50.00\r\n        - Appointment Date: October 16, 2025 1:44 PM\r\n        \r\n        Please bring the exact amount for payment. If you have any questions, please contact the barangay office.\r\n        \r\n        Thank you,\r\n        Barangay Malangit Administration\r\n        ', '', 30, 0, '{\"application_id\":\"9\",\"appointment_id\":\"6\",\"appointment_date\":\"2025-10-16T13:44\",\"amount\":\"50.00\"}', '2025-10-05 05:44:56', NULL),
(118, 'appointment_completed', 'Payment Appointment Completed - Application #APP-20251005-4518', '\r\n        Dear Jan Russelsss Peñafielsss,\r\n        \r\n        Your Payment appointment for the following application has been completed:\r\n        \r\n        Application Details:\r\n        - Application Number: APP-20251005-4518\r\n        - Document Type: Barangay Clearance\r\n        - Appointment Type: Payment\r\n        - Completion Date: October 5, 2025 1:45 PM\r\n        \r\n        Thank you for visiting the barangay office. If you have any questions, please contact us.\r\n        \r\n        Thank you,\r\n        Barangay Malangit Administration\r\n        ', '', 30, 0, '{\"application_id\":9,\"appointment_id\":6,\"appointment_type\":\"payment\",\"completion_date\":\"2025-10-05 13:45:37\"}', '2025-10-05 05:45:37', NULL),
(119, 'application_submitted', 'New Document Application', 'New application APP-20251005-7148 submitted by Jan Russelsss Peñafielsss', 'admin', NULL, 0, '{\"application_id\":\"10\"}', '2025-10-05 05:47:08', NULL),
(120, 'appointment_rescheduled', 'Pickup Appointment Rescheduled - Application #APP-20250728-8520', '\r\n        Dear Jan Russelsss Peñafielsss,\r\n        \r\n        Your Pickup appointment for the following application has been rescheduled:\r\n        \r\n        Application Details:\r\n        - Application Number: APP-20250728-8520\r\n        - Document Type: Certificate of Residency\r\n        - Appointment Type: Pickup\r\n        - New Appointment Date: October 6, 2025 1:48 PM\r\n        - Reason: sdfsfs\n\r\n        Please note the new appointment time. If you have any questions, please contact the barangay office.\r\n        \r\n        Thank you,\r\n        Barangay Malangit Administration\r\n        ', '', 30, 0, '{\"application_id\":6,\"appointment_id\":\"4\",\"appointment_type\":\"pickup\",\"new_appointment_date\":\"2025-10-06T13:48\",\"notes\":\"sdfsfs\"}', '2025-10-05 05:48:10', NULL),
(121, 'payment_appointment_scheduled', 'Payment Appointment Scheduled - Application #APP-20251005-4518', '\r\n        Dear Jan Russelsss Peñafielsss,\r\n        \r\n        Your payment appointment for the following application has been scheduled:\r\n        \r\n        Application Details:\r\n        - Application Number: APP-20251005-4518\r\n        - Document Type: Barangay Clearance\r\n        - Amount Due: ₱50.00\r\n        - Appointment Date: October 6, 2025 1:48 PM\r\n        \r\n        Please bring the exact amount for payment. If you have any questions, please contact the barangay office.\r\n        \r\n        Thank you,\r\n        Barangay Malangit Administration\r\n        ', '', 30, 0, '{\"application_id\":\"9\",\"appointment_id\":\"7\",\"appointment_date\":\"2025-10-06T13:48\",\"amount\":\"50.00\"}', '2025-10-05 05:48:42', NULL),
(122, 'payment_waived', 'Payment Waived - Application #APP-20251005-7148', '\n        Dear Jan Russelsss Peñafielsss,\n        \n        Your payment for Certificate of Indigency application #APP-20251005-7148 has been waived.\n        \n        Application Details:\n        - Application Number: APP-20251005-7148\n        - Document Type: Certificate of Indigency\n        - Original Fee: ₱25.00\n        - Status: Payment Waived\n        \n        Your application is now being processed. You will be notified once it\'s ready for pickup.\n        \n        Thank you,\n        Barangay Malangit Administration\n        ', 'all', 30, 0, '{\"application_id\":10,\"document_type\":\"Certificate of Indigency\",\"original_fee\":\"25.00\"}', '2025-10-06 05:58:11', NULL),
(123, 'leader_removed', 'Purok Leader Removed', 'Purok leader position removed from Jan Russel Peñafiel for Purok 1. Reason: sfsd', 'admin', 5, 0, NULL, '2025-10-06 06:03:09', NULL),
(124, 'leader_assigned', 'New Purok Leader Assigned', 'Jan Russelsss Peñafielsss has been assigned as leader of Purok 1', 'admin', 30, 0, NULL, '2025-10-06 06:03:16', NULL),
(125, 'new_registration', 'New Resident Registration', 'New registration from Jan Russel Peñafielsss', 'admin', NULL, 0, '{\"user_id\": 31, \"purok_id\": 1}', '2025-10-06 06:08:58', NULL),
(126, 'new_registration', 'New Resident Registration in Your Purok', 'New registration from Jan Russel Peñafielsss in your purok', 'purok_leader', NULL, 0, '{\"user_id\": 31, \"purok_id\": 1}', '2025-10-06 06:08:58', NULL),
(127, 'registration_approved', 'Registration Approved', 'Your registration has been approved by Purok Leader (Jan Russelsss Peñafielsss)', '', 31, 0, '{\"approved_by\":30,\"approved_at\":\"2025-10-06 14:10:07\",\"remarks\":\"asdada\"}', '2025-10-06 06:10:07', NULL),
(128, 'registration_approved', 'Registration Approved', 'Your registration has been fully approved (System Administrator)', '', 31, 0, '{\"approved_by\":1,\"approved_at\":\"2025-10-06 14:10:25\",\"remarks\":\"ada\"}', '2025-10-06 06:10:25', NULL),
(129, 'application_submitted', 'New Document Application', 'New application APP-20251102-1293 submitted by Jan Russel Peñafiel', 'admin', NULL, 0, '{\"application_id\":\"11\"}', '2025-11-02 04:46:15', NULL),
(130, 'payment_appointment_scheduled', 'Payment Appointment Scheduled - Application #APP-20251102-1293', '\r\n        Dear Jan Russel Peñafiel,\r\n        \r\n        Your payment appointment for the following application has been scheduled:\r\n        \r\n        Application Details:\r\n        - Application Number: APP-20251102-1293\r\n        - Document Type: Business Permit\r\n        - Amount Due: ₱200.00\r\n        - Appointment Date: November 4, 2025 1:13 PM\r\n        \r\n        Please bring the exact amount for payment. If you have any questions, please contact the barangay office.\r\n        \r\n        Thank you,\r\n        Barangay Malangit Administration\r\n        ', '', 5, 0, '{\"application_id\":\"11\",\"appointment_id\":\"8\",\"appointment_date\":\"2025-11-04T13:13\",\"amount\":\"200.00\"}', '2025-11-02 05:13:52', NULL),
(131, 'payment_received', 'Advance Payment Confirmed - Application #APP-20251102-1293', '\r\n        Dear Jan Russel Peñafiel,\r\n        \r\n        Your advance payment for your application has been confirmed:\r\n        \r\n        Application Details:\r\n        - Application Number: APP-20251102-1293\r\n        - Document Type: Business Permit\r\n        - Amount Paid: ₱200.00\r\n        - Payment Date: November 2, 2025 1:30 PM\r\n        - Payment Method: Advance Payment\r\n        \r\n        Your application is now being processed. You will be notified when your document is ready for pickup.\r\n        \r\n        Thank you,\r\n        Barangay Malangit Administration\r\n        ', '', 5, 0, '{\"application_id\":11,\"appointment_id\":8,\"amount_paid\":\"200.00\",\"payment_date\":\"2025-11-02 13:30:32\",\"payment_type\":\"advance payment\"}', '2025-11-02 05:30:32', NULL),
(132, 'application_submitted', 'New Document Application', 'New application APP-20251102-8175 submitted by Jan Russel Peñafiel', 'admin', NULL, 0, '{\"application_id\":\"12\"}', '2025-11-02 05:33:08', NULL),
(133, 'payment_appointment_scheduled', 'Payment Appointment Scheduled - Application #APP-20251102-8175', '\r\n        Dear Jan Russel Peñafiel,\r\n        \r\n        Your payment appointment for the following application has been scheduled:\r\n        \r\n        Application Details:\r\n        - Application Number: APP-20251102-8175\r\n        - Document Type: Building Permit\r\n        - Amount Due: ₱500.00\r\n        - Appointment Date: November 6, 2025 1:54 PM\r\n        \r\n        Please bring the exact amount for payment. If you have any questions, please contact the barangay office.\r\n        \r\n        Thank you,\r\n        Barangay Malangit Administration\r\n        ', '', 5, 0, '{\"application_id\":\"12\",\"appointment_id\":\"9\",\"appointment_date\":\"2025-11-06T13:54\",\"amount\":\"500.00\"}', '2025-11-02 05:54:27', NULL),
(134, 'appointment_completed', 'Payment Appointment Completed - Application #APP-20251102-8175', 'Dear  ,\n\nYour payment appointment has been marked as completed:\n\nApplication Details:\n- Application Number: APP-20251102-8175\n- Document Type: Building Permit\n- Amount Due: ₱500.00\n\nYou can now proceed with payment through the system. Please log in to your account to make the payment.\n\nThank you,\nBarangay Malangit Administration', '', 5, 0, '{\"application_id\":12,\"appointment_id\":9,\"amount\":\"500.00\",\"appointment_done\":true}', '2025-11-02 05:54:43', NULL),
(135, 'payment_waived', 'Payment Waived - Application #APP-20251005-4518', '\n        Dear Jan Russelsss Peñafielsss,\n        \n        Your payment for Barangay Clearance application #APP-20251005-4518 has been waived.\n        \n        Application Details:\n        - Application Number: APP-20251005-4518\n        - Document Type: Barangay Clearance\n        - Original Fee: ₱50.00\n        - Status: Payment Waived\n        \n        Your application is now being processed. You will be notified once it\'s ready for pickup.\n        \n        Thank you,\n        Barangay Malangit Administration\n        ', 'all', 30, 0, '{\"application_id\":9,\"document_type\":\"Barangay Clearance\",\"original_fee\":\"50.00\"}', '2025-11-02 06:01:36', NULL),
(136, 'application_processing', 'Payment Confirmed - Application #APP-20251102-8175', 'Dear Jan Russel Peñafiel,\r\n\r\nYour payment has been confirmed and your application is now being processed:\r\n\r\nApplication Details:\r\n- Application Number: APP-20251102-8175\r\n- Document Type: Building Permit\r\n- Payment Amount: ₱500.00\r\n- Payment Method: Manual Processing\r\n- Processing Time: 3 to 5 working days (except holidays)\r\n\r\nYou will be notified when your document is ready for pickup.\r\n\r\nThank you,\r\nBarangay Malangit Administration', '', 5, 0, '{\"application_id\":12,\"payment_method\":\"manual\",\"amount\":\"500.00\"}', '2025-11-02 06:04:30', NULL),
(137, 'application_submitted', 'New Document Application', 'New application APP-20251102-4189 submitted by Jan Russel Peñafiel', 'admin', NULL, 0, '{\"application_id\":\"13\"}', '2025-11-02 06:05:56', NULL),
(138, 'payment_appointment_scheduled', 'Payment Appointment Scheduled - Application #APP-20251102-4189', '\r\n        Dear Jan Russel Peñafiel,\r\n        \r\n        Your payment appointment for the following application has been scheduled:\r\n        \r\n        Application Details:\r\n        - Application Number: APP-20251102-4189\r\n        - Document Type: Certificate of Residency\r\n        - Amount Due: ₱30.00\r\n        - Appointment Date: November 8, 2025 2:06 PM\r\n        \r\n        Please bring the exact amount for payment. If you have any questions, please contact the barangay office.\r\n        \r\n        Thank you,\r\n        Barangay Malangit Administration\r\n        ', '', 5, 0, '{\"application_id\":\"13\",\"appointment_id\":\"10\",\"appointment_date\":\"2025-11-08T14:06\",\"amount\":\"30.00\"}', '2025-11-02 06:06:21', NULL),
(139, 'appointment_completed', 'Payment Appointment Completed - Application #APP-20251102-4189', 'Dear  ,\n\nYour payment appointment has been marked as completed:\n\nApplication Details:\n- Application Number: APP-20251102-4189\n- Document Type: Certificate of Residency\n- Amount Due: ₱30.00\n\nYou can now proceed with payment through the system. Please log in to your account to make the payment.\n\nThank you,\nBarangay Malangit Administration', '', 5, 0, '{\"application_id\":13,\"appointment_id\":10,\"amount\":\"30.00\",\"appointment_done\":true}', '2025-11-02 06:06:26', NULL),
(140, '5', 'New Support Message from System Administrator', 'asdada', '', 1, 0, '\"info\"', '2025-11-02 06:39:11', NULL),
(141, '31', 'New Support Message from System Administrator', 'asdada', '', 1, 0, '\"info\"', '2025-11-02 06:39:11', NULL),
(145, '5', 'New Support Message from System Administrator', 'i can hear you', '', 5, 0, '\"info\"', '2025-11-02 06:40:26', NULL),
(146, '31', 'New Support Message from System Administrator', 'i can hear you', '', 5, 0, '\"info\"', '2025-11-02 06:40:26', NULL),
(168, '5', 'New Support Message from System Administrator', 'asdada', '', 30, 0, '\"info\"', '2025-11-02 07:11:10', NULL),
(169, '31', 'New Support Message from System Administrator', 'asdada', '', 30, 0, '\"info\"', '2025-11-02 07:11:10', NULL),
(170, '1', 'New Support Message from Jan Russel Peñafiel', 'Jdjdjs', '', 31, 0, '\"info\"', '2025-11-02 07:11:14', NULL);

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
(1, 'admin001', 'admin123', 'admin', 'approved', 'System', NULL, 'Administrator', NULL, NULL, NULL, 'Male', 'Single', '09677726912', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 'approved', NULL, NULL, NULL, NULL, NULL, 1, 1, '2025-07-24 02:40:48', '2025-07-26 07:15:14'),
(5, 'jpeafiel_p5', '3d881bf7', 'resident', 'approved', 'Jan Russel', 'asdadgd', 'Peñafiel', '', '2003-01-20', 22, 'Female', 'Widowed', '09677726912', 'artiedastephany@gmail.com', 1, 'Panay Santo Niño South Cotabato', 'asda', 2000.00, 'Jan Russel asdadgd Peñafiel', '09677726912', NULL, NULL, NULL, 'approved', 'approved', NULL, NULL, NULL, 1, '2025-07-24 06:52:15', 1, 1, '2025-07-24 06:52:15', '2025-10-06 06:03:09'),
(30, 'janpe01297', 'bmscapis2025', 'purok_leader', 'approved', 'Jan Russelsss', 'Elizaressss', 'Peñafielsss', '', '2002-02-02', 23, 'Male', 'Single', '09677726917', 'penafielliezl9999s@gmail.com', 1, 'Purok Paghidaet 1&2 Panay Santo Nino South Cotabato', '', 0.00, 'Jan Russel Elizares Peñafiel', '09677726912', '6884e6fc8fe827.86404042.png', '6884e6fc905625.35238474.png', '6884e6fc90b094.79721121.png', 'approved', 'approved', 'asdada', 'asda', 5, 1, '2025-07-27 11:31:06', 1, 1, '2025-07-26 14:32:28', '2025-10-06 06:03:16'),
(31, 'janruss01159', 'bmscapis2025', 'resident', 'approved', 'Jan', 'Elizares', 'Russel Peñafielsss', '', '1997-02-05', 28, 'Male', 'Single', '09942270388', 'penafielliezl9999@gmail.com', 1, 'Panay Sto. Niño South Cotabato', 'asdada', 1312313.00, 'asdad', '09677726912', '68e35cfa713f59.55372625.png', '68e35cfa718603.94421453.png', '68e35cfa71d6e0.49000045.jpg', 'approved', 'approved', 'asdada', 'ada', 30, 1, '2025-10-06 06:10:22', 1, 1, '2025-10-06 06:08:58', '2025-10-06 06:10:22');

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
  ADD KEY `idx_applications_user_status` (`user_id`,`status`),
  ADD KEY `idx_applications_payment_status` (`payment_status`),
  ADD KEY `idx_applications_payment_method` (`payment_method`),
  ADD KEY `idx_applications_payment_date` (`payment_date`);

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
-- Indexes for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `idx_resident_id` (`resident_id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_last_message` (`last_message_at`),
  ADD KEY `idx_status_updated` (`status`,`updated_at`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reply_to_message_id` (`reply_to_message_id`),
  ADD KEY `idx_conversation_id` (`conversation_id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_conversation_read` (`conversation_id`,`is_read`),
  ADD KEY `idx_sender_type` (`sender_type`,`created_at`);

--
-- Indexes for table `chat_online_status`
--
ALTER TABLE `chat_online_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_online` (`is_online`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- Indexes for table `chat_rate_limits`
--
ALTER TABLE `chat_rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_window_start` (`window_start`);

--
-- Indexes for table `chat_settings`
--
ALTER TABLE `chat_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `chat_typing_indicators`
--
ALTER TABLE `chat_typing_indicators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_conversation` (`conversation_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_conversation_id` (`conversation_id`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_verifications`
--
ALTER TABLE `payment_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `reference_number` (`reference_number`),
  ADD KEY `status` (`status`),
  ADD KEY `expires_at` (`expires_at`),
  ADD KEY `idx_payment_verifications_status_created` (`status`,`created_at`),
  ADD KEY `idx_payment_verifications_expires_at` (`expires_at`);

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
-- Indexes for table `support_chat_files`
--
ALTER TABLE `support_chat_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_message` (`message_id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`);

--
-- Indexes for table `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_id`,`sender_role`),
  ADD KEY `idx_recipient` (`recipient_id`,`recipient_role`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `support_typing_indicators`
--
ALTER TABLE `support_typing_indicators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`,`user_role`),
  ADD KEY `idx_typing` (`is_typing`,`updated_at`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=442;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `application_history`
--
ALTER TABLE `application_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `chat_online_status`
--
ALTER TABLE `chat_online_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_rate_limits`
--
ALTER TABLE `chat_rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `chat_settings`
--
ALTER TABLE `chat_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `chat_typing_indicators`
--
ALTER TABLE `chat_typing_indicators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment_verifications`
--
ALTER TABLE `payment_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `support_chat_files`
--
ALTER TABLE `support_chat_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `support_typing_indicators`
--
ALTER TABLE `support_typing_indicators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=290;

--
-- AUTO_INCREMENT for table `system_config`
--
ALTER TABLE `system_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `system_notifications`
--
ALTER TABLE `system_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=192;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD CONSTRAINT `chat_conversations_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_conversations_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `chat_conversations_ibfk_3` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_3` FOREIGN KEY (`reply_to_message_id`) REFERENCES `chat_messages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chat_online_status`
--
ALTER TABLE `chat_online_status`
  ADD CONSTRAINT `chat_online_status_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_rate_limits`
--
ALTER TABLE `chat_rate_limits`
  ADD CONSTRAINT `chat_rate_limits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_typing_indicators`
--
ALTER TABLE `chat_typing_indicators`
  ADD CONSTRAINT `chat_typing_indicators_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_typing_indicators_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_verifications`
--
ALTER TABLE `payment_verifications`
  ADD CONSTRAINT `fk_payment_verifications_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `support_chat_files`
--
ALTER TABLE `support_chat_files`
  ADD CONSTRAINT `support_chat_files_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `support_messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_notifications`
--
ALTER TABLE `system_notifications`
  ADD CONSTRAINT `system_notifications_ibfk_1` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
