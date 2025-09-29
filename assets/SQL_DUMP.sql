SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

DROP DATABASE IF EXISTS facultrack_db;
CREATE DATABASE facultrack_db;
USE facultrack_db;

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('faculty','program_chair','campus_director','class') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `current_location` varchar(255) DEFAULT 'Not Available',
  `last_location_update` timestamp NOT NULL DEFAULT current_timestamp(),
  `office_hours` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`faculty_id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `year_level` int(11) NOT NULL,
  `semester` enum('1st','2nd','summer') NOT NULL,
  `academic_year` varchar(10) NOT NULL,
  `program_chair_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`class_id`),
  UNIQUE KEY `class_code` (`class_code`),
  KEY `user_id` (`user_id`),
  KEY `program_chair_id` (`program_chair_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`program_chair_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(20) NOT NULL,
  `course_description` varchar(255) NOT NULL,
  `units` decimal(3,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`course_id`),
  UNIQUE KEY `course_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(20) NOT NULL,
  `class_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `section` varchar(10) DEFAULT NULL,
  `days` enum('M','T','W','TH','F','S','MW','MF','WF','MWF','TTH','MTWTHF','SAT') NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `semester` enum('1st','2nd','summer') NOT NULL,
  `academic_year` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`schedule_id`),
  KEY `course_code` (`course_code`),
  KEY `class_id` (`class_id`),
  KEY `faculty_id` (`faculty_id`),
  FOREIGN KEY (`course_code`) REFERENCES `courses`(`course_code`),
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`class_id`),
  FOREIGN KEY (`faculty_id`) REFERENCES `faculty`(`faculty_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `priority` enum('high','medium','low') DEFAULT 'medium',
  `target_audience` enum('all','faculty','classes','program_chairs') DEFAULT 'all',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`announcement_id`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `location_history` (
  `location_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `time_set` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_changed` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`location_history_id`),
  KEY `faculty_id` (`faculty_id`),
  KEY `idx_location_time_set` (`time_set`),
  KEY `idx_location_time_changed` (`time_changed`),
  KEY `idx_location_faculty_time` (`faculty_id`, `time_set`),
  KEY `idx_location_active` (`faculty_id`, `time_changed`),
  FOREIGN KEY (`faculty_id`) REFERENCES `faculty`(`faculty_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES 
(1, 'admin', 'admin123', 'Campus Director', 'campus_director', NOW(), NOW(), 1),
(2, 'chair_it', 'chair123', 'Dr. Information Technology Chair', 'program_chair', NOW(), NOW(), 1),
(3, 'prof_garcia', 'prof123', 'Prof. Maria Garcia', 'faculty', NOW(), NOW(), 1),
(4, 'prof_santos', 'prof123', 'Prof. John Santos', 'faculty', NOW(), NOW(), 1),
(5, 'it1a_class', 'class123', 'IT 1A Class Account', 'class', NOW(), NOW(), 1),
(6, 'it1b_class', 'class123', 'IT 1B Class Account', 'class', NOW(), NOW(), 1);

INSERT INTO `faculty` VALUES 
(1, 2, 'CHAIR-001', 'Information Technology', 'Dean Office - IT Building', NOW(), '8:00 AM - 5:00 PM', 'chair.it@sksu.edu.ph', '09123456789', NOW(), NOW(), 1),
(2, 3, 'EMP-001', 'Information Technology', 'Faculty Lounge - IT Building', NOW(), '9:00 AM - 4:00 PM', 'maria.garcia@sksu.edu.ph', '09123456790', NOW(), NOW(), 1),
(3, 4, 'EMP-002', 'Information Technology', 'Room 201 - IT Building', NOW(), '10:00 AM - 5:00 PM', 'john.santos@sksu.edu.ph', '09123456791', NOW(), NOW(), 1);

INSERT INTO `classes` VALUES 
(1, 5, 'IT-1A', 'Information Technology 1A', 1, '1st', '2024-2025', 2, NOW(), NOW(), 1),
(2, 6, 'IT-1B', 'Information Technology 1B', 1, '1st', '2024-2025', 2, NOW(), NOW(), 1);

INSERT INTO `courses` VALUES 
(1, 'CC111', 'Introduction to Computing', 3.00, NOW(), NOW(), 1),
(2, 'CC112', 'Computer Programming 1', 3.00, NOW(), NOW(), 1),
(3, 'IT111', 'Discrete Mathematics', 3.00, NOW(), NOW(), 1),
(4, 'CC113', 'Computer Programming 2', 3.00, NOW(), NOW(), 1),
(5, 'IT121', 'Fundamentals of Database Systems', 3.00, NOW(), NOW(), 1),
(6, 'CC114', 'Data Structures and Algorithm', 3.00, NOW(), NOW(), 1);

INSERT INTO `announcements` VALUES 
(1, 'Welcome to FaculTrack System', 'The new FaculTrack system is now live. All users can access their respective dashboards using their assigned credentials.', 'medium', 'all', 1, NOW(), NOW(), 1),
(2, 'Academic Year 2024-2025 Guidelines', 'Please review the updated academic guidelines for the current academic year. All policies are now available in the system.', 'high', 'all', 1, NOW(), NOW(), 1),
(3, 'Emergency Contact Information', 'Updated emergency contact numbers are now available. Please check your profile settings for the latest information.', 'low', 'all', 1, NOW(), NOW(), 1),
(4, 'Faculty Meeting - Monthly Assembly', 'All faculty members are required to attend the monthly faculty meeting on Friday, 2:00 PM in the Conference Room.', 'high', 'faculty', 1, NOW(), NOW(), 1),
(5, 'Professional Development Workshop', 'A workshop on modern teaching methodologies will be held next month. Registration is now open for all faculty members.', 'low', 'faculty', 1, NOW(), NOW(), 1),
(6, 'Schedule Assignment Deadline', 'Program Chairs must finalize all class schedules and faculty assignments by the end of this week.', 'high', 'program_chairs', 1, NOW(), NOW(), 1),
(7, 'Budget Planning Meeting', 'Department budget planning session scheduled for next Tuesday at 10:00 AM. Attendance is mandatory for all Program Chairs.', 'high', 'program_chairs', 1, NOW(), NOW(), 1),
(8, 'Curriculum Review Process', 'Annual curriculum review process begins next month. Please prepare necessary documentation and recommendations.', 'medium', 'program_chairs', 1, NOW(), NOW(), 1);

CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_faculty_program ON faculty(program);
CREATE INDEX idx_faculty_employee ON faculty(employee_id);
CREATE INDEX idx_classes_chair ON classes(program_chair_id);
CREATE INDEX idx_classes_year ON classes(year_level);
CREATE INDEX idx_courses_code ON courses(course_code);
CREATE INDEX idx_courses_active ON courses(is_active);
CREATE INDEX idx_schedules_course ON schedules(course_code);
CREATE INDEX idx_schedules_class_faculty ON schedules(class_id, faculty_id);
CREATE INDEX idx_schedules_time ON schedules(days, time_start, time_end);
CREATE INDEX idx_schedules_active ON schedules(is_active);
CREATE INDEX idx_announcements_target ON announcements(target_audience, created_at);
CREATE INDEX idx_announcements_active ON announcements(is_active);

COMMIT;

