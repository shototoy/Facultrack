-- Auto-generated from facultrack.sql;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;

/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;

/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;

/*!40101 SET NAMES utf8mb4 */;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

SET time_zone = "+00:00";

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

CREATE TABLE `deans` (
  `dean_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `program` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `program_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `location_history` (
  `location_history_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `time_set` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_changed` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_announcements_target` (`target_audience`,`created_at`),
  ADD KEY `idx_announcements_active` (`is_active`);

ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD UNIQUE KEY `class_code` (`class_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `program_chair_id` (`program_chair_id`),
  ADD KEY `idx_classes_chair` (`program_chair_id`),
  ADD KEY `idx_classes_year` (`year_level`);

ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `fk_courses_program` (`program_id`),
  ADD KEY `idx_courses_code` (`course_code`),
  ADD KEY `idx_courses_active` (`is_active`),
  ADD KEY `idx_courses_program` (`program_id`);

ALTER TABLE `curriculum`
  ADD PRIMARY KEY (`curriculum_id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `program_chair_id` (`program_chair_id`),
  ADD KEY `idx_curriculum_year_sem` (`year_level`,`semester`),
  ADD KEY `idx_curriculum_active` (`is_active`);

ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_faculty_activity` (`is_active`,`last_activity`),
  ADD KEY `idx_faculty_program` (`program`),
  ADD KEY `idx_faculty_employee` (`employee_id`);

ALTER TABLE `deans`
  ADD PRIMARY KEY (`dean_id`),
  ADD UNIQUE KEY `uniq_program_id` (`program_id`),
  ADD KEY `idx_deans_faculty_id` (`faculty_id`);

ALTER TABLE `iftl_entries`
  ADD PRIMARY KEY (`entry_id`),
  ADD KEY `compliance_id` (`compliance_id`);

ALTER TABLE `iftl_weekly_compliance`
  ADD PRIMARY KEY (`compliance_id`),
  ADD UNIQUE KEY `unique_week_faculty` (`faculty_id`,`week_identifier`),
  ADD KEY `faculty_id` (`faculty_id`);

ALTER TABLE `location_history`
  ADD PRIMARY KEY (`location_history_id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `idx_location_time_set` (`time_set`),
  ADD KEY `idx_location_time_changed` (`time_changed`),
  ADD KEY `idx_location_faculty_time` (`faculty_id`,`time_set`),
  ADD KEY `idx_location_active` (`faculty_id`,`time_changed`);

ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD KEY `idx_programs_code` (`program_code`),
  ADD KEY `idx_programs_active` (`is_active`),
  ADD KEY `idx_programs_dean` (`dean_id`);

ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `idx_schedules_course` (`course_code`),
  ADD KEY `idx_schedules_class_faculty` (`class_id`,`faculty_id`),
  ADD KEY `idx_schedules_time` (`days`,`time_start`,`time_end`),
  ADD KEY `idx_schedules_active` (`is_active`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_role` (`role`);

ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

ALTER TABLE `curriculum`
  MODIFY `curriculum_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

ALTER TABLE `deans`
  MODIFY `dean_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

ALTER TABLE `iftl_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `iftl_weekly_compliance`
  MODIFY `compliance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `location_history`
  MODIFY `location_history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`program_chair_id`) REFERENCES `users` (`user_id`);

ALTER TABLE `courses`
  ADD CONSTRAINT `fk_courses_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`);

ALTER TABLE `deans`
  ADD CONSTRAINT `fk_deans_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_deans_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON UPDATE CASCADE;

ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`dean_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

ALTER TABLE `curriculum`
  ADD CONSTRAINT `curriculum_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `curriculum_ibfk_2` FOREIGN KEY (`program_chair_id`) REFERENCES `users` (`user_id`);

ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

ALTER TABLE `iftl_entries`
  ADD CONSTRAINT `iftl_entries_ibfk_1` FOREIGN KEY (`compliance_id`) REFERENCES `iftl_weekly_compliance` (`compliance_id`) ON DELETE CASCADE;

ALTER TABLE `iftl_weekly_compliance`
  ADD CONSTRAINT `iftl_weekly_compliance_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

ALTER TABLE `location_history`
  ADD CONSTRAINT `location_history_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`course_code`) REFERENCES `courses` (`course_code`),
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `schedules_ibfk_3` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`);

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `role`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'admin', 'admin123', 'Rommel M. Lagumen', 'campus_director', '2025-11-27 10:06:45', '2026-02-03 03:35:01', 1);

INSERT INTO `faculty` (`faculty_id`, `user_id`, `employee_id`, `program`, `current_location`, `last_location_update`, `last_activity`, `office_hours`, `contact_email`, `contact_phone`, `status`, `created_at`, `updated_at`, `is_active`) VALUES
(35, 1, 'DIR-1', 'Administration', NULL, '2026-02-03 03:35:01', '2025-11-27 10:06:48', NULL, NULL, NULL, 'Available', '2025-11-27 10:06:48', '2026-02-03 03:35:01', 1);

SET FOREIGN_KEY_CHECKS = 1;
