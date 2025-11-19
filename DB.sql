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
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `office_hours` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`faculty_id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_faculty_activity` (`is_active`, `last_activity`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`class_id`),
  UNIQUE KEY `class_code` (`class_code`),
  KEY `user_id` (`user_id`),
  KEY `program_chair_id` (`program_chair_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`program_chair_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL AUTO_INCREMENT,
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `program_description` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`program_id`),
  UNIQUE KEY `program_code` (`program_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(20) NOT NULL,
  `course_description` varchar(255) NOT NULL,
  `units` decimal(3,2) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`course_id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `fk_courses_program` (`program_id`),
  CONSTRAINT `fk_courses_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`)
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
  `semester` enum('1st','2nd','Summer') NOT NULL,
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

CREATE TABLE `curriculum` (
  `curriculum_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(20) NOT NULL,
  `year_level` int(11) NOT NULL,
  `semester` enum('1st','2nd','Summer') NOT NULL,
  `program_chair_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`curriculum_id`),
  KEY `course_code` (`course_code`),
  KEY `program_chair_id` (`program_chair_id`),
  KEY `idx_curriculum_year_sem` (`year_level`, `semester`),
  KEY `idx_curriculum_active` (`is_active`),
  FOREIGN KEY (`course_code`) REFERENCES `courses`(`course_code`),
  FOREIGN KEY (`program_chair_id`) REFERENCES `users`(`user_id`)
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
(6, 'it1b_class', 'class123', 'IT 1B Class Account', 'class', NOW(), NOW(), 1),
(7, 'it2a_class', 'class123', 'IT 2A Class Account', 'class', NOW(), NOW(), 1),
(8, 'it2b_class', 'class123', 'IT 2B Class Account', 'class', NOW(), NOW(), 1),
(9, 'it3a_class', 'class123', 'IT 3A Class Account', 'class', NOW(), NOW(), 1),
(10, 'it3b_class', 'class123', 'IT 3B Class Account', 'class', NOW(), NOW(), 1),
(11, 'it4a_class', 'class123', 'IT 4A Class Account', 'class', NOW(), NOW(), 1),
(12, 'it4b_class', 'class123', 'IT 4B Class Account', 'class', NOW(), NOW(), 1);

INSERT INTO `faculty` (`faculty_id`, `user_id`, `employee_id`, `program`, `current_location`, `last_location_update`, `last_activity`, `office_hours`, `contact_email`, `contact_phone`, `created_at`, `updated_at`, `is_active`) VALUES 
(1, 2, 'CHAIR-001', 'Information Technology', 'Dean Office - IT Building', NOW(), NOW(), '8:00 AM - 5:00 PM', 'chair.it@sksu.edu.ph', '09123456789', NOW(), NOW(), 1),
(2, 3, 'EMP-001', 'Information Technology', 'Faculty Lounge - IT Building', NOW(), NOW(), '9:00 AM - 4:00 PM', 'maria.garcia@sksu.edu.ph', '09123456790', NOW(), NOW(), 1),
(3, 4, 'EMP-002', 'Information Technology', 'Room 201 - IT Building', NOW(), NOW(), '10:00 AM - 5:00 PM', 'john.santos@sksu.edu.ph', '09123456791', NOW(), NOW(), 1);

INSERT INTO `classes` VALUES 
(1, 5, 'IT-1A', 'Information Technology 2024-2025', 1, '1st', '2024-25', 2, NOW(), NOW(), 1),
(2, 6, 'IT-1B', 'Information Technology 2024-2025', 1, '1st', '2024-25', 2, NOW(), NOW(), 1),
(3, 7, 'IT-2A', 'Information Technology 2024-2025', 2, '1st', '2024-25', 2, NOW(), NOW(), 1),
(4, 8, 'IT-2B', 'Information Technology 2024-2025', 2, '1st', '2024-25', 2, NOW(), NOW(), 1),
(5, 9, 'IT-3A', 'Information Technology 2024-2025', 3, '1st', '2024-25', 2, NOW(), NOW(), 1),
(6, 10, 'IT-3B', 'Information Technology 2024-2025', 3, '1st', '2024-25', 2, NOW(), NOW(), 1),
(7, 11, 'IT-4A', 'Information Technology 2024-2025', 4, '1st', '2024-25', 2, NOW(), NOW(), 1),
(8, 12, 'IT-4B', 'Information Technology 2024-2025', 4, '1st', '2024-25', 2, NOW(), NOW(), 1);

INSERT INTO `programs` VALUES 
(1, 'CS', 'Computer Science', 'Bachelor of Science in Computer Science', 1, NOW(), NOW()),
(2, 'IT', 'Information Technology', 'Bachelor of Science in Information Technology', 1, NOW(), NOW()),
(3, 'ENG', 'Engineering', 'Bachelor of Science in Engineering', 1, NOW(), NOW()),
(4, 'BA', 'Business Administration', 'Bachelor of Science in Business Administration', 1, NOW(), NOW()),
(5, 'EDU', 'Education', 'Bachelor of Science in Education', 1, NOW(), NOW());

INSERT INTO `courses` VALUES 
(1, 'CC111', 'Introduction to Computing', 3.00, 2, NOW(), NOW(), 1),
(2, 'CC112', 'Computer Programming 1', 3.00, 2, NOW(), NOW(), 1),
(3, 'CC113', 'Computer Programming 2', 3.00, 2, NOW(), NOW(), 1),
(4, 'CC114', 'Data Structures and Algorithms', 3.00, 2, NOW(), NOW(), 1),
(5, 'CC115', 'Information Management', 3.00, 2, NOW(), NOW(), 1),
(6, 'CC116', 'Application Development and Emerging Technologies', 3.00, 2, NOW(), NOW(), 1),
(7, 'MS121', 'Discrete Mathematics', 3.00, 2, NOW(), NOW(), 1),
(8, 'IM121', 'Fundamentals of Database Systems', 3.00, 2, NOW(), NOW(), 1),
(9, 'ACCTG111', 'Financial Accounting and Reporting', 3.00, 2, NOW(), NOW(), 1),
(10, 'APC211', 'Graphics and Multimedia Systems', 3.00, 2, NOW(), NOW(), 1),
(11, 'PF211', 'Object-Oriented Programming', 3.00, 2, NOW(), NOW(), 1),
(12, 'PT212', 'Platform Technologies', 3.00, 2, NOW(), NOW(), 1),
(13, 'WS213', 'Web Systems and Technologies', 3.00, 2, NOW(), NOW(), 1),
(14, 'FIN212', 'Financial Management', 3.00, 2, NOW(), NOW(), 1),
(15, 'HCI221', 'Introduction to Human Computer Interaction', 3.00, 2, NOW(), NOW(), 1),
(16, 'IM223', 'Advanced Database Systems', 3.00, 2, NOW(), NOW(), 1),
(17, 'IPT225', 'Integrative Programming and Technologies 1', 3.00, 2, NOW(), NOW(), 1),
(18, 'PF221', 'Event-Driven Programming', 3.00, 2, NOW(), NOW(), 1),
(19, 'STAT003', 'Statistics with Computer Application', 3.00, 2, NOW(), NOW(), 1),
(20, 'AT316', 'Digital Design', 3.00, 2, NOW(), NOW(), 1),
(21, 'IAS314', 'Information Assurance and Security 1', 3.00, 2, NOW(), NOW(), 1),
(22, 'IPT313', 'Integrative Programming and Technologies 2', 3.00, 2, NOW(), NOW(), 1),
(23, 'MS312', 'Quantitative Methods (Including Modelling and Simulation)', 3.00, 2, NOW(), NOW(), 1),
(24, 'NET311', 'Networking 1', 3.00, 2, NOW(), NOW(), 1),
(25, 'SIA317', 'Systems Integration and Architecture 1', 3.00, 2, NOW(), NOW(), 1),
(26, 'AT324', 'Embedded Systems', 3.00, 2, NOW(), NOW(), 1),
(27, 'AT327', 'Mobile Computing', 3.00, 2, NOW(), NOW(), 1),
(28, 'CAP325', 'Capstone Project and Research 1', 3.00, 2, NOW(), NOW(), 1),
(29, 'ENG001', 'Advanced Technical Writing', 3.00, 2, NOW(), NOW(), 1),
(30, 'FTS321', 'Field Trip and Seminar', 3.00, 2, NOW(), NOW(), 1),
(31, 'IAS322', 'Information Assurance and Security 2', 3.00, 2, NOW(), NOW(), 1),
(32, 'NET321', 'Networking 2', 3.00, 2, NOW(), NOW(), 1),
(33, 'SP326', 'Social and Professional Issues', 3.00, 2, NOW(), NOW(), 1),
(34, 'PRACTI101', 'Practicum (486 hours)', 6.00, 2, NOW(), NOW(), 1),
(35, 'CAP420', 'Capstone Project and Research 2', 3.00, 2, NOW(), NOW(), 1),
(36, 'SA421', 'System Administration and Maintenance', 3.00, 2, NOW(), NOW(), 1),
(37, 'GE701', 'Mathematics in the Modern World', 3.00, NULL, NOW(), NOW(), 1),
(38, 'GE702', 'Purposive Communication', 3.00, NULL, NOW(), NOW(), 1),
(39, 'GE703', 'Ethics', 3.00, NULL, NOW(), NOW(), 1),
(40, 'GE704', 'Science, Technology and Society', 3.00, NULL, NOW(), NOW(), 1),
(41, 'GE705', 'The Contemporary World', 3.00, NULL, NOW(), NOW(), 1),
(42, 'GE706', 'Art Appreciation', 3.00, NULL, NOW(), NOW(), 1),
(43, 'GE707', 'Readings in Philippine History', 3.00, NULL, NOW(), NOW(), 1),
(44, 'GE708', 'Understanding the Self', 3.00, NULL, NOW(), NOW(), 1),
(45, 'GE709', 'The Life and Works of Jose Rizal', 3.00, NULL, NOW(), NOW(), 1),
(46, 'GE711', 'Culture of Mindanao', 3.00, NULL, NOW(), NOW(), 1),
(47, 'GE712', 'Gender and Society', 3.00, NULL, NOW(), NOW(), 1),
(48, 'PE101', 'Physical Fitness and Self-Testing Activities', 2.00, NULL, NOW(), NOW(), 1),
(49, 'PE102', 'Rhythmic Activities', 2.00, NULL, NOW(), NOW(), 1),
(50, 'PE103', 'Recreational Activities', 2.00, NULL, NOW(), NOW(), 1),
(51, 'PE104', 'Team Sports', 2.00, NULL, NOW(), NOW(), 1),
(52, 'NSTP102', 'National Service Training Program 2', 3.00, NULL, NOW(), NOW(), 1);

INSERT INTO `curriculum` VALUES 
(1, 'CC112', 1, '1st', 2, NOW(), NOW(), 1),
(2, 'CC111', 1, '1st', 2, NOW(), NOW(), 1),
(3, 'GE701', 1, '1st', 2, NOW(), NOW(), 1),
(4, 'GE708', 1, '1st', 2, NOW(), NOW(), 1),
(5, 'GE702', 1, '1st', 2, NOW(), NOW(), 1),
(6, 'GE707', 1, '1st', 2, NOW(), NOW(), 1),
(7, 'PE101', 1, '1st', 2, NOW(), NOW(), 1),
(8, 'CC113', 1, '2nd', 2, NOW(), NOW(), 1),
(9, 'GE704', 1, '2nd', 2, NOW(), NOW(), 1),
(10, 'GE712', 1, '2nd', 2, NOW(), NOW(), 1),
(11, 'GE705', 1, '2nd', 2, NOW(), NOW(), 1),
(12, 'MS121', 1, '2nd', 2, NOW(), NOW(), 1),
(13, 'IM121', 1, '2nd', 2, NOW(), NOW(), 1),
(14, 'NSTP102', 1, '2nd', 2, NOW(), NOW(), 1),
(15, 'PE102', 1, '2nd', 2, NOW(), NOW(), 1),
(16, 'ACCTG111', 2, '1st', 2, NOW(), NOW(), 1),
(17, 'APC211', 2, '1st', 2, NOW(), NOW(), 1),
(18, 'CC114', 2, '1st', 2, NOW(), NOW(), 1),
(19, 'CC115', 2, '1st', 2, NOW(), NOW(), 1),
(20, 'PE103', 2, '1st', 2, NOW(), NOW(), 1),
(21, 'PF211', 2, '1st', 2, NOW(), NOW(), 1),
(22, 'PT212', 2, '1st', 2, NOW(), NOW(), 1),
(23, 'WS213', 2, '1st', 2, NOW(), NOW(), 1),
(24, 'FIN212', 2, '2nd', 2, NOW(), NOW(), 1),
(25, 'HCI221', 2, '2nd', 2, NOW(), NOW(), 1),
(26, 'IM223', 2, '2nd', 2, NOW(), NOW(), 1),
(27, 'IPT225', 2, '2nd', 2, NOW(), NOW(), 1),
(28, 'PE104', 2, '2nd', 2, NOW(), NOW(), 1),
(29, 'PF221', 2, '2nd', 2, NOW(), NOW(), 1),
(30, 'STAT003', 2, '2nd', 2, NOW(), NOW(), 1),
(31, 'AT316', 3, '1st', 2, NOW(), NOW(), 1),
(32, 'CC116', 3, '1st', 2, NOW(), NOW(), 1),
(33, 'IAS314', 3, '1st', 2, NOW(), NOW(), 1),
(34, 'IPT313', 3, '1st', 2, NOW(), NOW(), 1),
(35, 'MS312', 3, '1st', 2, NOW(), NOW(), 1),
(36, 'NET311', 3, '1st', 2, NOW(), NOW(), 1),
(37, 'SIA317', 3, '1st', 2, NOW(), NOW(), 1),
(38, 'AT324', 3, '2nd', 2, NOW(), NOW(), 1),
(39, 'AT327', 3, '2nd', 2, NOW(), NOW(), 1),
(40, 'CAP325', 3, '2nd', 2, NOW(), NOW(), 1),
(41, 'ENG001', 3, '2nd', 2, NOW(), NOW(), 1),
(42, 'FTS321', 3, '2nd', 2, NOW(), NOW(), 1),
(43, 'IAS322', 3, '2nd', 2, NOW(), NOW(), 1),
(44, 'NET321', 3, '2nd', 2, NOW(), NOW(), 1),
(45, 'SP326', 3, '2nd', 2, NOW(), NOW(), 1),
(46, 'PRACTI101', 4, '1st', 2, NOW(), NOW(), 1),
(47, 'CAP420', 4, '2nd', 2, NOW(), NOW(), 1),
(48, 'GE703', 4, '2nd', 2, NOW(), NOW(), 1),
(49, 'GE706', 4, '2nd', 2, NOW(), NOW(), 1),
(50, 'GE709', 4, '2nd', 2, NOW(), NOW(), 1),
(51, 'GE711', 4, '2nd', 2, NOW(), NOW(), 1),
(52, 'SA421', 4, '2nd', 2, NOW(), NOW(), 1);

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
CREATE INDEX idx_programs_code ON programs(program_code);
CREATE INDEX idx_programs_active ON programs(is_active);
CREATE INDEX idx_courses_code ON courses(course_code);
CREATE INDEX idx_courses_active ON courses(is_active);
CREATE INDEX idx_courses_program ON courses(program_id);
CREATE INDEX idx_schedules_course ON schedules(course_code);
CREATE INDEX idx_schedules_class_faculty ON schedules(class_id, faculty_id);
CREATE INDEX idx_schedules_time ON schedules(days, time_start, time_end);
CREATE INDEX idx_schedules_active ON schedules(is_active);
CREATE INDEX idx_announcements_target ON announcements(target_audience, created_at);
CREATE INDEX idx_announcements_active ON announcements(is_active);

COMMIT;