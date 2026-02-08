-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 03, 2026 at 04:37 AM
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
-- Database: `facultrack_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `priority` enum('high','medium','low') DEFAULT 'medium',
  `target_audience` enum('all','faculty','classes','program_chairs') DEFAULT 'all',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `priority`, `target_audience`, `created_by`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'Welcome to FaculTrack System', 'The new FaculTrack system is now live. All users can access their respective dashboards using their assigned credentials.', 'medium', 'all', 1, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(2, 'Academic Year 2024-2025 Guidelines', 'Please review the updated academic guidelines for the current academic year. All policies are now available in the system.', 'high', 'all', 1, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1);
-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `year_level` int(11) NOT NULL,
  `semester` enum('1st','2nd','Summer') NOT NULL,
  `academic_year` varchar(10) NOT NULL,
  `program_chair_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `total_students` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `user_id`, `class_code`, `class_name`, `year_level`, `semester`, `academic_year`, `program_chair_id`, `created_at`, `updated_at`, `is_active`, `total_students`) VALUES
(1, 42, 'IT-1A', 'Information Technology 2023-2024', 1, '1st', '2023-24', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(2, 43, 'IT-1B', 'Information Technology 2023-2024', 1, '1st', '2023-24', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(3, 44, 'IT-2A', 'Information Technology 2022-2023', 2, '1st', '2023-24', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(4, 45, 'IT-2B', 'Information Technology 2022-2023', 2, '1st', '2023-24', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(5, 46, 'IT-3A', 'Information Technology 2021-2022', 3, '1st', '2023-24', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(6, 47, 'IT-3B', 'Information Technology 2021-2022', 3, '1st', '2023-24', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(7, 48, 'IT-4A', 'Information Technology 2020-2021', 4, '1st', '2023-24', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(8, 49, 'IT-4B', 'Information Technology 2020-2021', 4, '1st', '2023-24', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(9, 50, 'IS-1A', 'Information Systems 2023-2024', 1, '1st', '2023-24', 3, '2025-11-27 10:06:45', '2025-12-24 17:02:30', 1, 5),
(10, 51, 'IS-1B', 'Information Systems 2023-2024', 1, '1st', '2023-24', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(11, 52, 'IS-2A', 'Information Systems 2022-2023', 2, '1st', '2023-24', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(12, 53, 'IS-2B', 'Information Systems 2022-2023', 2, '1st', '2023-24', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(13, 54, 'IS-3A', 'Information Systems 2021-2022', 3, '1st', '2023-24', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(14, 55, 'IS-3B', 'Information Systems 2021-2022', 3, '1st', '2023-24', 3, '2025-11-27 10:06:45', '2025-12-24 17:02:35', 1, 12),
(15, 56, 'IS-4A', 'Information Systems 2020-2021', 4, '1st', '2023-24', 3, '2025-11-27 10:06:45', '2025-11-27 10:34:33', 1, 0),
(16, 57, 'IS-4B', 'Information Systems 2020-2021', 4, '1st', '2023-24', 3, '2025-11-27 10:06:45', '2025-12-24 17:12:13', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_description` varchar(255) NOT NULL,
  `units` decimal(3,2) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_code`, `course_description`, `units`, `program_id`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'CC111', 'Introduction to Computing', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(2, 'CC112', 'Computer Programming 1', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(3, 'CC113', 'Computer Programming 2', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(4, 'CC114', 'Data Structures and Algorithms', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(5, 'CC115', 'Information Management', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(6, 'CC116', 'Application Development and Emerging Technologies', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(7, 'MS121', 'Discrete Mathematics', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(8, 'IM121', 'Fundamentals of Database Systems', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(9, 'ACCTG111', 'Financial Accounting and Reporting', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(10, 'APC211', 'Graphics and Multimedia Systems', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(11, 'PF211', 'Object-Oriented Programming', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(12, 'PT212', 'Platform Technologies', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(13, 'WS213', 'Web Systems and Technologies', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(14, 'FIN212', 'Financial Management', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(15, 'HCI221', 'Introduction to Human Computer Interaction', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(16, 'IM223', 'Advanced Database Systems', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(17, 'IPT225', 'Integrative Programming and Technologies 1', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(18, 'PF221', 'Event-Driven Programming', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(19, 'STAT003', 'Statistics with Computer Application', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(20, 'AT316', 'Digital Design', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(21, 'IAS314', 'Information Assurance and Security 1', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(22, 'IPT313', 'Integrative Programming and Technologies 2', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(23, 'MS312', 'Quantitative Methods (Including Modelling and Simulation)', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(24, 'NET311', 'Networking 1', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(25, 'SIA317', 'Systems Integration and Architecture 1', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(26, 'AT324', 'Embedded Systems', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(27, 'AT327', 'Mobile Computing', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(28, 'CAP325', 'Capstone Project and Research 1', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(29, 'ENG001', 'Advanced Technical Writing', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(30, 'FTS321', 'Field Trip and Seminar', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(31, 'IAS322', 'Information Assurance and Security 2', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(32, 'NET321', 'Networking 2', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(33, 'SP326', 'Social and Professional Issues', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(34, 'PRACTI101', 'Practicum (486 hours)', 6.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(35, 'CAP420', 'Capstone Project and Research 2', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(36, 'SA421', 'System Administration and Maintenance', 3.00, 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(37, 'GE701', 'Mathematics in the Modern World', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(38, 'GE702', 'Purposive Communication', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(39, 'GE703', 'Ethics', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(40, 'GE704', 'Science, Technology and Society', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(41, 'GE705', 'The Contemporary World', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(42, 'GE706', 'Art Appreciation', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(43, 'GE707', 'Readings in Philippine History', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(44, 'GE708', 'Understanding the Self', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(45, 'GE709', 'The Life and Works of Jose Rizal', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(46, 'GE711', 'Culture of Mindanao', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(47, 'GE712', 'Gender and Society', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(48, 'PE101', 'Physical Fitness and Self-Testing Activities', 2.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(49, 'PE102', 'Rhythmic Activities', 2.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(50, 'PE103', 'Recreational Activities', 2.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(51, 'PE104', 'Team Sports', 2.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(52, 'NSTP102', 'National Service Training Program 2', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(53, 'ACCTG121', 'Basic Accounting for Partnership and Corporate Entities', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(54, 'ELECT1', 'Elective 1 (Customer Relationship Management)', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(55, 'ELECT2', 'Elective 2 (Data Mining)', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(56, 'ELECT3', 'Elective 3 (Supply Chain Management)', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(57, 'ELECT4', 'Elective 4(Business Intelligence)', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(58, 'IS111', 'Fundamentals of Information Systems', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(59, 'IS121', 'Organization and Managements Concepts', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(60, 'IS122', 'IT Infrastructure and Network Technology', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(61, 'IS221', 'Systems Analysis and Design', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(62, 'IS222', 'Enterprise Architecture', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(63, 'IS223', 'Business Process and Management', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(64, 'IS311', 'Evaluation of Business Performance', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(65, 'IS312', 'System Infrastructure and Integration', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(66, 'IS313', 'IS Project Management 1', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(67, 'IS314', 'Professional Issues in Information Systems', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(68, 'IS321', 'Management Information Systems', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(69, 'IS322', 'Capstone Project I', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1);

-- --------------------------------------------------------

--
-- Table structure for table `curriculum`
--

DROP TABLE IF EXISTS `curriculum`;
CREATE TABLE `curriculum` (
  `curriculum_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `year_level` int(11) NOT NULL,
  `semester` enum('1st','2nd','Summer') NOT NULL,
  `program_chair_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `curriculum`
--

INSERT INTO `curriculum` (`curriculum_id`, `course_code`, `year_level`, `semester`, `program_chair_id`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'CC112', 1, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(2, 'CC111', 1, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(3, 'GE701', 1, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(4, 'GE708', 1, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(5, 'GE702', 1, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(6, 'GE707', 1, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(7, 'PE101', 1, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(8, 'CC113', 1, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(9, 'GE704', 1, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(10, 'GE712', 1, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(11, 'GE705', 1, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(12, 'MS121', 1, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(13, 'IM121', 1, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(14, 'NSTP102', 1, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(15, 'PE102', 1, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(16, 'ACCTG111', 2, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(17, 'APC211', 2, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(18, 'CC114', 2, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(19, 'CC115', 2, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(20, 'PE103', 2, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(21, 'PF211', 2, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(22, 'PT212', 2, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(23, 'WS213', 2, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(24, 'FIN212', 2, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(25, 'HCI221', 2, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(26, 'IM223', 2, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(27, 'IPT225', 2, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(28, 'PE104', 2, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(29, 'PF221', 2, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(30, 'STAT003', 2, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(31, 'AT316', 3, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(32, 'CC116', 3, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(33, 'IAS314', 3, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(34, 'IPT313', 3, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(35, 'MS312', 3, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(36, 'NET311', 3, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(37, 'SIA317', 3, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(38, 'AT324', 3, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(39, 'AT327', 3, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(40, 'CAP325', 3, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(41, 'ENG001', 3, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(42, 'FTS321', 3, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(43, 'IAS322', 3, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(44, 'NET321', 3, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(45, 'SP326', 3, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(46, 'PRACTI101', 4, '1st', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(47, 'CAP420', 4, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(48, 'GE703', 4, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(49, 'GE706', 4, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(50, 'GE709', 4, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(51, 'GE711', 4, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(52, 'SA421', 4, '2nd', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(53, 'ACCTG111', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(54, 'CC111', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(55, 'CC112', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(56, 'GE701', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(57, 'GE708', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(59, 'IS111', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(61, 'PE101', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(62, 'ACCTG121', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(63, 'CC113', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(64, 'GE702', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(65, 'GE707', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(67, 'IS121', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(68, 'IS122', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(69, 'NSTP102', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(70, 'PE102', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(71, 'CC114', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(72, 'ELECT1', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(73, 'FIN212', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(74, 'GE704', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(75, 'GE703', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(76, 'GE706', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(77, 'GE709', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(78, 'PE103', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(79, 'CC115', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(80, 'ELECT2', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(81, 'IS221', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(82, 'IS222', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(83, 'IS223', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(84, 'PE104', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(85, 'STAT003', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(86, 'ELECT3', 3, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(87, 'GE705', 3, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(88, 'IS311', 3, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(89, 'IS312', 3, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(90, 'IS313', 3, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(91, 'IS314', 3, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),

(93, 'ELECT4', 3, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(94, 'ENG001', 3, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(95, 'FTS321', 3, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(96, 'IS321', 3, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(97, 'IS322', 3, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),

(99, 'CC116', 4, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1);

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

DROP TABLE IF EXISTS `faculty`;
CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `current_location` varchar(255) DEFAULT 'Not Available',
  `last_location_update` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `office_hours` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `status` enum('Available','In Meeting','On Leave') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `user_id`, `employee_id`, `program`, `current_location`, `last_location_update`, `last_activity`, `office_hours`, `contact_email`, `contact_phone`, `status`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 2, 'CHAIR-001', 'Information Technology', NULL, '2026-02-02 15:10:39', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'chair.it@sksu.edu.ph', '09123456789', 'Available', '2025-11-27 10:06:45', '2026-02-02 15:10:39', 1),
(2, 3, 'CHAIR-002', 'Information Systems', NULL, '2026-01-26 05:04:07', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'chair.is@sksu.edu.ph', '09123456788', 'Available', '2025-11-27 10:06:45', '2026-01-26 05:04:08', 0),
(3, 4, 'EMP-0001', 'Information Technology', 'NR109', '2026-02-02 06:34:47', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'alexis.apresto@sksu.edu.ph', '09123456701', 'Available', '2025-11-27 10:06:45', '2026-02-02 06:35:23', 0),
(4, 5, 'EMP-0002', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'benedict.rabut@sksu.edu.ph', '091234567024', 'Available', '2025-11-27 10:06:45', '2026-01-29 17:37:56', 0),
(6, 7, 'EMP-0004', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'cerilo.rubin@sksu.edu.ph', '09123456704', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(7, 8, 'EMP-0005', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'cyrus.rael@sksu.edu.ph', '09123456705', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(8, 9, 'EMP-0006', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'elbren.antonio@sksu.edu.ph', '09123456706', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(9, 10, 'EMP-0007', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'elmer.buenavides@sksu.edu.ph', '09123456707', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(10, 11, 'EMP-0008', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'esnehara.bagundang@sksu.edu.ph', '09123456708', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(11, 12, 'EMP-0009', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'florlyn.remegio@sksu.edu.ph', '09123456709', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(12, 13, 'EMP-0010', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'gerold.delapena@sksu.edu.ph', '09123456710', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(13, 14, 'EMP-0011', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'gregorio.ilao@sksu.edu.ph', '09123456711', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(14, 15, 'EMP-0012', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'ivy.madriaga@sksu.edu.ph', '09123456712', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(15, 16, 'EMP-0013', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'jaymark.arendain@sksu.edu.ph', '09123456713', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(16, 17, 'EMP-0014', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'joe.selayro@sksu.edu.ph', '09123456714', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(17, 18, 'EMP-0015', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'kristine.ampas@sksu.edu.ph', '09123456715', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(18, 19, 'EMP-0016', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'kyrene.dizon@sksu.edu.ph', '09123456716', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(19, 20, 'EMP-0017', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'marhodora.gallo@sksu.edu.ph', '09123456717', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(20, 21, 'EMP-0018', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'markjovic.daday@sksu.edu.ph', '09123456718', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(21, 22, 'EMP-0019', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'marygrace.perocho@sksu.edu.ph', '09123456719', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(22, 23, 'EMP-0020', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'romaamor.prades@sksu.edu.ph', '09123456720', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(23, 24, 'EMP-0021', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'velessa.dulin@sksu.edu.ph', '09123456721', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(24, 25, 'EMP-0022', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'zius.apresto@sksu.edu.ph', '09123456722', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(25, 26, 'EMP-0023', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'charlu.pemintel@sksu.edu.ph', '09123456723', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(26, 27, 'EMP-0024', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'doreen.tampus@sksu.edu.ph', '09123456724', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(27, 28, 'EMP-0025', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'edralin.mesias@sksu.edu.ph', '09123456725', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(28, 29, 'EMP-0026', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'eulogio.apellido@sksu.edu.ph', '09123456726', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(29, 30, 'EMP-0027', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'johnty.ventilacion@sksu.edu.ph', '09123456727', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(30, 31, 'EMP-0028', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'lowell.espinosa@sksu.edu.ph', '09123456728', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(31, 32, 'EMP-0029', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'maryrolanne.fuentes@sksu.edu.ph', '09123456729', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(32, 33, 'EMP-0030', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'marygrace.bialen@sksu.edu.ph', '09123456730', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(33, 34, 'EMP-0031', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'maryjoy.carnazo@sksu.edu.ph', '09123456731', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(34, 35, 'EMP-0032', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'may.gallano@sksu.edu.ph', '09123456732', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(35, 36, 'EMP-0033', 'Information Systems', NULL, '2025-11-27 10:34:29', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'nora.moya@sksu.edu.ph', '09123456733', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0);
(41, 1, 'DIR-1', 'Administration', NULL, '2026-02-03 03:35:01', '2025-11-27 10:06:48', NULL, NULL, NULL, 'Available', '2025-11-27 10:06:48', '2026-02-03 03:35:01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `iftl_entries`
--

DROP TABLE IF EXISTS `iftl_entries`;
CREATE TABLE `iftl_entries` (
  `entry_id` int(11) NOT NULL,
  `compliance_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `class_name` varchar(100) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL DEFAULT 'Class',
  `status` enum('Regular','Vacant','Leave','Makeup','Dismissed') DEFAULT 'Regular',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_modified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iftl_entries`
--

INSERT INTO `iftl_entries` (`entry_id`, `compliance_id`, `day_of_week`, `time_start`, `time_end`, `course_code`, `class_name`, `room`, `activity_type`, `status`, `remarks`, `created_at`, `is_modified`) VALUES
(33, 5, 'Monday', '08:00:00', '09:00:00', 'VACATION', NULL, 'Japan', '', 'Regular', '', '2026-02-02 06:35:21', 1),
(34, 5, 'Monday', '09:00:00', '15:00:00', 'AT316', NULL, 'Room 201', 'Information Systems 2021-2022', 'Regular', '', '2026-02-02 06:35:21', 1),
(35, 5, 'Tuesday', '07:30:00', '10:00:00', 'CC112', NULL, 'Room 101', 'Information Technology 2023-2024', 'Regular', '', '2026-02-02 06:35:21', 1),
(36, 5, 'Wednesday', '08:00:00', '09:00:00', 'ACCTG111', NULL, 'Computer Lab 2', 'Information Technology 2023-2024', 'Regular', '', '2026-02-02 06:35:21', 1),
(37, 5, 'Wednesday', '09:00:00', '15:00:00', 'AT316', NULL, 'Room 201', 'Information Systems 2021-2022', 'Regular', '', '2026-02-02 06:35:21', 1),
(38, 5, 'Thursday', '07:30:00', '10:00:00', 'CC112', NULL, 'Room 101', 'Information Technology 2023-2024', 'Regular', '', '2026-02-02 06:35:21', 1),
(39, 5, 'Friday', '08:00:00', '09:00:00', 'ACCTG111', NULL, 'Computer Lab 2', 'Information Technology 2023-2024', 'Regular', '', '2026-02-02 06:35:21', 1),
(40, 5, 'Friday', '13:00:00', '17:00:00', 'CC112', NULL, 'Room 102', 'Information Systems 2023-2024', 'Regular', '', '2026-02-02 06:35:21', 1);

-- --------------------------------------------------------

--
-- Table structure for table `iftl_weekly_compliance`
--

DROP TABLE IF EXISTS `iftl_weekly_compliance`;
CREATE TABLE `iftl_weekly_compliance` (
  `compliance_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `week_identifier` varchar(20) NOT NULL COMMENT 'Format: YYYY-Wxx',
  `week_start_date` date NOT NULL,
  `status` enum('Draft','Submitted','Approved','Rejected') DEFAULT 'Draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iftl_weekly_compliance`
--

INSERT INTO `iftl_weekly_compliance` (`compliance_id`, `faculty_id`, `week_identifier`, `week_start_date`, `status`, `submitted_at`, `reviewed_at`, `reviewer_id`, `created_at`, `updated_at`) VALUES
(5, 3, '2026-W06', '2026-02-02', 'Submitted', NULL, NULL, NULL, '2026-02-02 06:34:51', '2026-02-02 06:35:21');

-- --------------------------------------------------------

--
-- Table structure for table `location_history`
--

DROP TABLE IF EXISTS `location_history`;
CREATE TABLE `location_history` (
  `location_history_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `time_set` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_changed` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `program_description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_code`, `program_name`, `program_description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CS', 'Computer Science', 'Bachelor of Science in Computer Science', 1, '2025-11-27 10:06:45', '2025-11-27 10:06:45'),
(2, 'IT', 'Information Technology', 'Bachelor of Science in Information Technology', 1, '2025-11-27 10:06:45', '2025-11-27 10:06:45'),
(3, 'IS', 'Information Systems', 'Bachelor of Science in Information Systems', 1, '2025-11-27 10:06:45', '2025-11-27 10:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `class_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `section` varchar(10) DEFAULT NULL,
  `days` enum('M','T','W','TH','F','S','MW','MF','WF','MWF','TTH','MTWTHF','SAT') NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `semester` enum('1st','2nd','Summer') NOT NULL,
  `academic_year` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `course_code`, `class_id`, `faculty_id`, `section`, `days`, `time_start`, `time_end`, `room`, `semester`, `academic_year`, `created_at`, `updated_at`, `is_active`) VALUES
(12, 'ACCTG111', 2, 3, NULL, 'MWF', '08:00:00', '09:00:00', 'Computer Lab 2', '1st', '', '2025-12-22 17:22:19', '2025-12-22 17:22:19', 1),
(13, 'AT316', 14, 3, NULL, 'MW', '09:00:00', '15:00:00', 'Room 201', '1st', '', '2025-12-22 17:49:47', '2025-12-22 17:49:47', 1),
(14, 'CC112', 10, 3, NULL, 'F', '13:00:00', '17:00:00', 'Room 102', '1st', '', '2025-12-22 17:50:12', '2025-12-22 17:50:12', 1),
(15, 'CC112', 1, 3, NULL, 'TTH', '07:30:00', '10:00:00', 'Room 101', '1st', '', '2025-12-22 17:50:41', '2025-12-22 17:50:41', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('faculty','program_chair','campus_director','class') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `role`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'admin', 'admin123', 'Campus Director', 'campus_director', '2025-11-27 10:06:45', '2026-02-03 03:35:01', 1),
(2, 'chair_it', 'chair123', 'Dr. Information Technology Chair', 'program_chair', '2025-11-27 10:06:45', '2026-02-02 15:10:39', 1),
(3, 'chair_is', 'chair123', 'Dr. Information Systems Chair', 'program_chair', '2025-11-27 10:06:45', '2026-01-26 05:04:07', 1),
(4, 'alexis.apresto', 'prof123', 'Prof. Alexis Apresto', 'faculty', '2025-11-27 10:06:45', '2026-02-02 06:34:47', 1),
(5, 'benedict.rabut', 'prof123', 'Prof. Benedict Rabut', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(7, 'cerilo.rubin', 'prof123', 'Prof. Cerilo Rubin Jr.', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(8, 'cyrus.rael', 'prof123', 'Prof. Cyrus Rael', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(9, 'elbren.antonio', 'prof123', 'Prof. Elbren Antonio', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(10, 'elmer.buenavides', 'prof123', 'Prof. Elmer Buenavides', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(11, 'esnehara.bagundang', 'prof123', 'Prof. Esnehara Bagundang', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(12, 'florlyn.remegio', 'prof123', 'Prof. Florlyn Remegio', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(13, 'gerold.delapena', 'prof123', 'Prof. Gerold Dela Peña', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(14, 'gregorio.ilao', 'prof123', 'Prof. Gregorio Ilao', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(15, 'ivy.madriaga', 'prof123', 'Prof. Ivy Lynn Madriaga', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(16, 'jaymark.arendain', 'prof123', 'Prof. Jaymark Arendain', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(17, 'joe.selayro', 'prof123', 'Prof. Joe Selayro', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(18, 'kristine.ampas', 'prof123', 'Prof. Kristine Mae Ampas', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(19, 'kyrene.dizon', 'prof123', 'Prof. Kyrene Dizon', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(20, 'marhodora.gallo', 'prof123', 'Prof. Ma. Rhodora Gallo', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(21, 'markjovic.daday', 'prof123', 'Prof. Mark Jovic Daday', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(22, 'marygrace.perocho', 'prof123', 'Prof. Mary Grace Perocho', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(23, 'romaamor.prades', 'prof123', 'Prof. Roma Amor Prades', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(24, 'velessa.dulin', 'prof123', 'Prof. Velessa Jane Dulin', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(25, 'zius.apresto', 'prof123', 'Prof. Zius Apresto', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(26, 'charlu.pemintel', 'prof123', 'Prof. Charlu Pemintel', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(27, 'doreen.tampus', 'prof123', 'Prof. Doreen Tampus', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(28, 'edralin.mesias', 'prof123', 'Prof. Edralin Mesias', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(29, 'eulogio.apellido', 'prof123', 'Prof. Eulogio Apellido', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(30, 'johnty.ventilacion', 'prof123', 'Prof. Johnty Ventilacion', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(31, 'lowell.espinosa', 'prof123', 'Prof. Lowell Espinosa', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(32, 'maryrolanne.fuentes', 'prof123', 'Prof. Mary Rolanne Fuentes', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(33, 'marygrace.bialen', 'prof123', 'Prof. Mary Grace Bialen', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(34, 'maryjoy.carnazo', 'prof123', 'Prof. Mary Joy Carnazo', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(35, 'may.gallano', 'prof123', 'Prof. May Gallano', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(36, 'nora.moya', 'prof123', 'Prof. Nora Moya', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1);
(42, 'it1a_class', 'class123', 'IT 1A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(43, 'it1b_class', 'class123', 'IT 1B Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(44, 'it2a_class', 'class123', 'IT 2A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(45, 'it2b_class', 'class123', 'IT 2B Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(46, 'it3a_class', 'class123', 'IT 3A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(47, 'it3b_class', 'class123', 'IT 3B Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(48, 'it4a_class', 'class123', 'IT 4A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(49, 'it4b_class', 'class123', 'IT 4B Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(50, 'is1a_class', 'class123', 'IS 1A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(51, 'is1b_class', 'class123', 'IS 1B Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(52, 'is2a_class', 'class123', 'IS 2A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(53, 'is2b_class', 'class123', 'IS 2B Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(54, 'is3a_class', 'class123', 'IS 3A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(55, 'is3b_class', 'class123', 'IS 3B Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(56, 'is4a_class', 'class123', 'IS 4A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:34:33', 1),
(57, 'is4b_class', 'class123', 'IS 4B Class Account', 'class', '2025-11-27 10:06:45', '2025-12-24 17:12:13', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_announcements_target` (`target_audience`,`created_at`),
  ADD KEY `idx_announcements_active` (`is_active`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD UNIQUE KEY `class_code` (`class_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `program_chair_id` (`program_chair_id`),
  ADD KEY `idx_classes_chair` (`program_chair_id`),
  ADD KEY `idx_classes_year` (`year_level`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `fk_courses_program` (`program_id`),
  ADD KEY `idx_courses_code` (`course_code`),
  ADD KEY `idx_courses_active` (`is_active`),
  ADD KEY `idx_courses_program` (`program_id`);

--
-- Indexes for table `curriculum`
--
ALTER TABLE `curriculum`
  ADD PRIMARY KEY (`curriculum_id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `program_chair_id` (`program_chair_id`),
  ADD KEY `idx_curriculum_year_sem` (`year_level`,`semester`),
  ADD KEY `idx_curriculum_active` (`is_active`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_faculty_activity` (`is_active`,`last_activity`),
  ADD KEY `idx_faculty_program` (`program`),
  ADD KEY `idx_faculty_employee` (`employee_id`);

--
-- Indexes for table `iftl_entries`
--
ALTER TABLE `iftl_entries`
  ADD PRIMARY KEY (`entry_id`),
  ADD KEY `compliance_id` (`compliance_id`);

--
-- Indexes for table `iftl_weekly_compliance`
--
ALTER TABLE `iftl_weekly_compliance`
  ADD PRIMARY KEY (`compliance_id`),
  ADD UNIQUE KEY `unique_week_faculty` (`faculty_id`,`week_identifier`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `location_history`
--
ALTER TABLE `location_history`
  ADD PRIMARY KEY (`location_history_id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `idx_location_time_set` (`time_set`),
  ADD KEY `idx_location_time_changed` (`time_changed`),
  ADD KEY `idx_location_faculty_time` (`faculty_id`,`time_set`),
  ADD KEY `idx_location_active` (`faculty_id`,`time_changed`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD KEY `idx_programs_code` (`program_code`),
  ADD KEY `idx_programs_active` (`is_active`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `idx_schedules_course` (`course_code`),
  ADD KEY `idx_schedules_class_faculty` (`class_id`,`faculty_id`),
  ADD KEY `idx_schedules_time` (`days`,`time_start`,`time_end`),
  ADD KEY `idx_schedules_active` (`is_active`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `curriculum`
--
ALTER TABLE `curriculum`
  MODIFY `curriculum_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `iftl_entries`
--
ALTER TABLE `iftl_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `iftl_weekly_compliance`
--
ALTER TABLE `iftl_weekly_compliance`
  MODIFY `compliance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `location_history`
--
ALTER TABLE `location_history`
  MODIFY `location_history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`program_chair_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_courses_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`);

--
-- Constraints for table `curriculum`
--
ALTER TABLE `curriculum`
  ADD CONSTRAINT `curriculum_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `curriculum_ibfk_2` FOREIGN KEY (`program_chair_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `iftl_entries`
--
ALTER TABLE `iftl_entries`
  ADD CONSTRAINT `iftl_entries_ibfk_1` FOREIGN KEY (`compliance_id`) REFERENCES `iftl_weekly_compliance` (`compliance_id`) ON DELETE CASCADE;

--
-- Constraints for table `iftl_weekly_compliance`
--
ALTER TABLE `iftl_weekly_compliance`
  ADD CONSTRAINT `iftl_weekly_compliance_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `location_history`
--
ALTER TABLE `location_history`
  ADD CONSTRAINT `location_history_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `schedules_ibfk_3` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
