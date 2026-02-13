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

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `priority` enum('high','medium','low') DEFAULT 'medium',
  `target_audience` varchar(100) NOT NULL DEFAULT 'all',
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
(1, 36, 'IT-1A', 'Information Technology 2023-2024', 1, '1st', '2025-26', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(2, 37, 'IT-2A', 'Information Technology 2022-2023', 2, '1st', '2025-26', 2, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(3, 40, 'IS-1A', 'Information Systems 2023-2024', 1, '1st', '2025-26', 3, '2025-11-27 10:06:45', '2025-12-24 17:02:30', 1, 5),
(4, 41, 'IS-2A', 'Information Systems 2022-2023', 2, '1st', '2025-26', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(5, 45, 'CPE-1A', 'Computer Engineering 2025-2026', 1, '1st', '2025-2026', 52, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(6, 46, 'CPE-2A', 'Computer Engineering 2025-2026', 2, '1st', '2025-2026', 52, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(7, 47, 'CE-1A', 'Civil Engineering 2025-2026', 1, '1st', '2025-2026', 53, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(8, 48, 'CE-2A', 'Civil Engineering 2025-2026', 2, '1st', '2025-2026', 53, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(9, 49, 'ECE-1A', 'Electronics and Communications Engineering 2025-2026', 1, '1st', '2025-2026', 54, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(10, 50, 'ECE-2A', 'Electronics and Communications Engineering 2025-2026', 2, '1st', '2025-2026', 54, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(11, 55, 'CS-1A', 'Computer Science 2025-2026', 1, '1st', '2025-2026', 51, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0),
(12, 56, 'CS-2A', 'Computer Science 2025-2026', 2, '1st', '2025-2026', 51, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1, 0);
-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

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
(20, 'GE701', 'Mathematics in the Modern World', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(21, 'GE702', 'Purposive Communication', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(22, 'GE703', 'Ethics', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(23, 'GE704', 'Science, Technology and Society', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(24, 'GE705', 'The Contemporary World', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(25, 'GE706', 'Art Appreciation', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(26, 'GE707', 'Readings in Philippine History', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(27, 'GE708', 'Understanding the Self', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(28, 'GE709', 'The Life and Works of Jose Rizal', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(29, 'GE711', 'Culture of Mindanao', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(30, 'GE712', 'Gender and Society', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(31, 'PE101', 'Physical Fitness and Self-Testing Activities', 2.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(32, 'PE102', 'Rhythmic Activities', 2.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(33, 'PE103', 'Recreational Activities', 2.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(34, 'PE104', 'Team Sports', 2.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(35, 'NSTP102', 'National Service Training Program 2', 3.00, NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(36, 'ACCTG121', 'Basic Accounting for Partnership and Corporate Entities', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(37, 'ELECT1', 'Elective 1 (Customer Relationship Management)', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(38, 'ELECT2', 'Elective 2 (Data Mining)', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(39, 'ELECT3', 'Elective 3 (Supply Chain Management)', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(40, 'ELECT4', 'Elective 4(Business Intelligence)', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(41, 'IS111', 'Fundamentals of Information Systems', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(42, 'IS121', 'Organization and Managements Concepts', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(43, 'IS122', 'IT Infrastructure and Network Technology', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(44, 'IS221', 'Systems Analysis and Design', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(45, 'IS222', 'Enterprise Architecture', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(46, 'IS223', 'Business Process and Management', 3.00, 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(47, 'CPE101', 'Introduction to Computer Engineering', 3.00, 4, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(48, 'CPE102', 'Computer Engineering Drawing', 3.00, 4, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(49, 'CPE201', 'Digital Logic Design', 3.00, 4, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(50, 'CPE202', 'Computer Organization', 3.00, 4, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(51, 'CE101', 'Engineering Surveying', 3.00, 5, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(52, 'CE102', 'Construction Materials', 3.00, 5, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(53, 'CE201', 'Structural Analysis 1', 3.00, 5, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(54, 'CE202', 'Hydraulics', 3.00, 5, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(55, 'ECE101', 'Basic Electronics', 3.00, 6, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(56, 'ECE102', 'Circuit Analysis', 3.00, 6, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(57, 'ECE201', 'Signals and Systems', 3.00, 6, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(58, 'ECE202', 'Electronic Devices', 3.00, 6, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1);
-- --------------------------------------------------------

--
-- Table structure for table `curriculum`
--

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
(31, 'ACCTG111', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(32, 'CC111', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(33, 'CC112', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(34, 'GE701', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(35, 'GE708', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(36, 'IS111', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(37, 'PE101', 1, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(38, 'ACCTG121', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(39, 'CC113', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(40, 'GE702', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(41, 'GE707', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(42, 'IS121', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(43, 'IS122', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(44, 'NSTP102', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(45, 'PE102', 1, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(46, 'CC114', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(47, 'ELECT1', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(48, 'FIN212', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(49, 'GE704', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(50, 'GE703', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(51, 'GE706', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(52, 'GE709', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(53, 'PE103', 2, '1st', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(54, 'CC115', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(55, 'ELECT2', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(56, 'IS221', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(57, 'IS222', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(58, 'IS223', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(59, 'PE104', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(60, 'STAT003', 2, '2nd', 3, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(61, 'CPE101', 1, '1st', 52, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(62, 'CPE102', 1, '1st', 52, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(63, 'CPE201', 2, '1st', 52, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(64, 'CPE202', 2, '1st', 52, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(65, 'CE101', 1, '1st', 53, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(66, 'CE102', 1, '1st', 53, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(67, 'CE201', 2, '1st', 53, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(68, 'CE202', 2, '1st', 53, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(69, 'ECE101', 1, '1st', 54, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(70, 'ECE102', 1, '1st', 54, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(71, 'ECE201', 2, '1st', 54, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(72, 'ECE202', 2, '1st', 54, '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1);
-- --------------------------------------------------------

--
-- Table structure for table `deans`
--

CREATE TABLE `deans` (
  `dean_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `program` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `program_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deans`
--
-- No seed rows for deans. Dean assignments can be created from the Director dashboard.


-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

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
(5, 6, 'EMP-0004', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'cerilo.rubin@sksu.edu.ph', '09123456704', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(6, 7, 'EMP-0005', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'cyrus.rael@sksu.edu.ph', '09123456705', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(7, 8, 'EMP-0006', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'elbren.antonio@sksu.edu.ph', '09123456706', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(8, 9, 'EMP-0007', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'elmer.buenavides@sksu.edu.ph', '09123456707', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(9, 10, 'EMP-0008', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'esnehara.bagundang@sksu.edu.ph', '09123456708', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(10, 11, 'EMP-0009', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'florlyn.remegio@sksu.edu.ph', '09123456709', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(11, 12, 'EMP-0010', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'gerold.delapena@sksu.edu.ph', '09123456710', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(12, 13, 'EMP-0011', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'gregorio.ilao@sksu.edu.ph', '09123456711', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(13, 14, 'EMP-0012', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'ivy.madriaga@sksu.edu.ph', '09123456712', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(14, 15, 'EMP-0013', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'jaymark.arendain@sksu.edu.ph', '09123456713', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(15, 16, 'EMP-0014', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'joe.selayro@sksu.edu.ph', '09123456714', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(16, 17, 'EMP-0015', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'kristine.ampas@sksu.edu.ph', '09123456715', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(17, 18, 'EMP-0016', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'kyrene.dizon@sksu.edu.ph', '09123456716', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(18, 19, 'EMP-0017', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'marhodora.gallo@sksu.edu.ph', '09123456717', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(19, 20, 'EMP-0018', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'markjovic.daday@sksu.edu.ph', '09123456718', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(20, 21, 'EMP-0019', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'marygrace.perocho@sksu.edu.ph', '09123456719', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(21, 22, 'EMP-0020', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'romaamor.prades@sksu.edu.ph', '09123456720', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(22, 23, 'EMP-0021', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'velessa.dulin@sksu.edu.ph', '09123456721', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(23, 24, 'EMP-0022', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'zius.apresto@sksu.edu.ph', '09123456722', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(24, 25, 'EMP-0023', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'charlu.pemintel@sksu.edu.ph', '09123456723', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(25, 26, 'EMP-0024', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'doreen.tampus@sksu.edu.ph', '09123456724', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(26, 27, 'EMP-0025', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'edralin.mesias@sksu.edu.ph', '09123456725', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(27, 28, 'EMP-0026', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'eulogio.apellido@sksu.edu.ph', '09123456726', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(28, 29, 'EMP-0027', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'johnty.ventilacion@sksu.edu.ph', '09123456727', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(29, 30, 'EMP-0028', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'lowell.espinosa@sksu.edu.ph', '09123456728', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(30, 31, 'EMP-0029', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'maryrolanne.fuentes@sksu.edu.ph', '09123456729', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(31, 32, 'EMP-0030', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'marygrace.bialen@sksu.edu.ph', '09123456730', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(32, 33, 'EMP-0031', 'Information Systems', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'maryjoy.carnazo@sksu.edu.ph', '09123456731', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(33, 34, 'EMP-0032', 'Information Technology', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'may.gallano@sksu.edu.ph', '09123456732', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(34, 35, 'EMP-0033', 'Information Systems', NULL, '2025-11-27 10:34:29', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'nora.moya@sksu.edu.ph', '09123456733', 'Available', '2025-11-27 10:06:45', '2025-12-24 12:53:15', 0),
(35, 1, 'DIR-1', 'Administration', NULL, '2026-02-03 03:35:01', '2025-11-27 10:06:48', NULL, NULL, NULL, 'Available', '2025-11-27 10:06:48', '2026-02-03 03:35:01', 1),
(36, 51, 'CHAIR-003', 'Computer Science', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'chair.cs@sksu.edu.ph', '09123456790', 'Available', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(37, 52, 'CHAIR-004', 'Computer Engineering', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'chair.cpe@sksu.edu.ph', '09123456791', 'Available', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(38, 53, 'CHAIR-005', 'Civil Engineering', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'chair.ce@sksu.edu.ph', '09123456792', 'Available', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(39, 54, 'CHAIR-006', 'Electronics and Communications Engineering', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'chair.ece@sksu.edu.ph', '09123456793', 'Available', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(40, 44, 'EMP-0034', 'Computer Engineering', NULL, '2025-11-27 10:06:45', '2025-11-27 10:06:45', '8:00 AM - 5:00 PM', 'lenmar.catajay@sksu.edu.ph', '09123456734', 'Available', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1);
-- --------------------------------------------------------

--
-- Table structure for table `iftl_entries`
--

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
-- No seed rows for iftl_entries to avoid FK dependency on specific weekly compliance IDs during fresh installs.

-- --------------------------------------------------------

--
-- Table structure for table `iftl_weekly_compliance`
--

CREATE TABLE `iftl_weekly_compliance` (
  `compliance_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `week_identifier` varchar(20) NOT NULL COMMENT 'Format: YYYY-Wxx',
  `week_start_date` date NOT NULL,
  `status` enum('Draft','Submitted','Approved','Rejected') DEFAULT 'Draft',
  `is_override` tinyint(1) NOT NULL DEFAULT 0,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iftl_weekly_compliance`
--
-- No seed rows for iftl_weekly_compliance to keep fresh installs clean.


-- --------------------------------------------------------

--
-- Table structure for table `location_history`
--

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

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `program_description` text DEFAULT NULL,
  `dean_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_code`, `program_name`, `program_description`, `dean_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CS', 'Computer Science', 'Bachelor of Science in Computer Science', 8, 1, '2025-11-27 10:06:45', '2025-11-27 10:06:45'),
(2, 'IT', 'Information Technology', 'Bachelor of Science in Information Technology', 8, 1, '2025-11-27 10:06:45', '2025-11-27 10:06:45'),
(3, 'IS', 'Information Systems', 'Bachelor of Science in Information Systems', 8, 1, '2025-11-27 10:06:45', '2025-11-27 10:06:45'),
(4, 'CpE', 'Computer Engineering', 'Bachelor of Science in Computer Engineering', 44, 1, '2025-11-27 10:06:45', '2025-11-27 10:06:45'),
(5, 'CE', 'Civil Engineering', 'Bachelor of Science in Civil Engineering', 44, 1, '2025-11-27 10:06:45', '2025-11-27 10:06:45'),
(6, 'ECE', 'Electronics and Communications Engineering', 'Bachelor of Science in Electronics and Communications Engineering', 44, 1, '2025-11-27 10:06:45', '2025-11-27 10:06:45');
-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

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
(1, 'CC112', 1, 3, NULL, 'TTH', '07:30:00', '10:00:00', 'Room 101', '1st', '', '2025-12-22 17:50:41', '2025-12-22 17:50:41', 1);
-- --------------------------------------------------------

--
-- Table structure for table `users`
--

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
(1, 'admin', 'admin123', 'Rommel M. Lagumen', 'campus_director', '2025-11-27 10:06:45', '2026-02-03 03:35:01', 1),
(2, 'chair_it', 'chair123', 'Dr. Information Technology Chair', 'program_chair', '2025-11-27 10:06:45', '2026-02-02 15:10:39', 1),
(3, 'chair_is', 'chair123', 'Dr. Information Systems Chair', 'program_chair', '2025-11-27 10:06:45', '2026-01-26 05:04:07', 1),
(4, 'alexis.apresto', 'prof123', 'Alexis Apresto', 'faculty', '2025-11-27 10:06:45', '2026-02-02 06:34:47', 1),
(5, 'benedict.rabut', 'prof123', 'Benedict Rabut', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(6, 'cerilo.rubin', 'prof123', 'Cerilo Rubin Jr.', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(7, 'cyrus.rael', 'prof123', 'Cyrus Rael', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(8, 'elbren.antonio', 'prof123', 'Elbren Antonio', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(9, 'elmer.buenavides', 'prof123', 'Elmer Buenavides', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(10, 'esnehara.bagundang', 'prof123', 'Esnehara Bagundang', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(11, 'florlyn.remegio', 'prof123', 'Florlyn Remegio', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(12, 'gerold.delapena', 'prof123', 'Gerold Dela Peña', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(13, 'gregorio.ilao', 'prof123', 'Gregorio Ilao', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(14, 'ivy.madriaga', 'prof123', 'Ivy Lynn Madriaga', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(15, 'jaymark.arendain', 'prof123', 'Jaymark Arendain', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(16, 'joe.selayro', 'prof123', 'Joe Selayro', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(17, 'kristine.ampas', 'prof123', 'Kristine Mae Ampas', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(18, 'kyrene.dizon', 'prof123', 'Kyrene Dizon', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(19, 'marhodora.gallo', 'prof123', 'Ma. Rhodora Gallo', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(20, 'markjovic.daday', 'prof123', 'Mark Jovic Daday', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(21, 'marygrace.perocho', 'prof123', 'Mary Grace Perocho', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(22, 'romaamor.prades', 'prof123', 'Roma Amor Prades', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(23, 'velessa.dulin', 'prof123', 'Velessa Jane Dulin', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(24, 'zius.apresto', 'prof123', 'Zius Apresto', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(25, 'charlu.pemintel', 'prof123', 'Charlu Pemintel', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(26, 'doreen.tampus', 'prof123', 'Doreen Tampus', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(27, 'edralin.mesias', 'prof123', 'Edralin Mesias', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(28, 'eulogio.apellido', 'prof123', 'Eulogio Apellido', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(29, 'johnty.ventilacion', 'prof123', 'Johnty Ventilacion', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(30, 'lowell.espinosa', 'prof123', 'Lowell Espinosa', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(31, 'maryrolanne.fuentes', 'prof123', 'Mary Rolanne Fuentes', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(32, 'marygrace.bialen', 'prof123', 'Mary Grace Bialen', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(33, 'maryjoy.carnazo', 'prof123', 'Mary Joy Carnazo', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(34, 'may.gallano', 'prof123', 'May Gallano', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(35, 'nora.moya', 'prof123', 'Nora Moya', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(36, 'it1a_class', 'class123', 'IT 1A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(37, 'it2a_class', 'class123', 'IT 2A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(38, 'it3a_class', 'class123', 'IT 3A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(39, 'it4a_class', 'class123', 'IT 4A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(40, 'is1a_class', 'class123', 'IS 1A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(41, 'is2a_class', 'class123', 'IS 2A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(42, 'is3a_class', 'class123', 'IS 3A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(43, 'is4a_class', 'class123', 'IS 4A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:34:33', 1),
(44, 'lenmar.catajay', 'prof123', 'Lenmar Catajay', 'faculty', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(45, 'cpe1a_class', 'class123', 'CpE 1A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(46, 'cpe2a_class', 'class123', 'CpE 2A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(47, 'ce1a_class', 'class123', 'CE 1A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(48, 'ce2a_class', 'class123', 'CE 2A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(49, 'ece1a_class', 'class123', 'ECE 1A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(50, 'ece2a_class', 'class123', 'ECE 2A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(51, 'chair_cs', 'chair123', 'Dr. Computer Science Chair', 'program_chair', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(52, 'chair_cpe', 'chair123', 'Dr. Computer Engineering Chair', 'program_chair', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(53, 'chair_ce', 'chair123', 'Dr. Civil Engineering Chair', 'program_chair', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(54, 'chair_ece', 'chair123', 'Dr. Electronics and Communications Engineering Chair', 'program_chair', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(55, 'cs1a_class', 'class123', 'CS 1A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1),
(56, 'cs2a_class', 'class123', 'CS 2A Class Account', 'class', '2025-11-27 10:06:45', '2025-11-27 10:06:45', 1);


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
-- Indexes for table `deans`
--
ALTER TABLE `deans`
  ADD PRIMARY KEY (`dean_id`),
  ADD UNIQUE KEY `uniq_program_id` (`program_id`),
  ADD KEY `idx_deans_faculty_id` (`faculty_id`);

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
  ADD KEY `idx_programs_active` (`is_active`),
  ADD KEY `idx_programs_dean` (`dean_id`);

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
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `curriculum`
--
ALTER TABLE `curriculum`
  MODIFY `curriculum_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `deans`
--
ALTER TABLE `deans`
  MODIFY `dean_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `iftl_entries`
--
ALTER TABLE `iftl_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `iftl_weekly_compliance`
--
ALTER TABLE `iftl_weekly_compliance`
  MODIFY `compliance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

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
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

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
-- Constraints for table `deans`
--
ALTER TABLE `deans`
  ADD CONSTRAINT `fk_deans_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_deans_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON UPDATE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`dean_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

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

SET FOREIGN_KEY_CHECKS = 1;
