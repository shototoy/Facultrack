<?php
require_once 'common_utilities.php';
$pdo = get_db_connection();
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `iftl_weekly_compliance` (
            `compliance_id` INT(11) NOT NULL AUTO_INCREMENT,
            `faculty_id` INT(11) NOT NULL,
            `week_identifier` VARCHAR(20) NOT NULL COMMENT 'Format: YYYY-Wxx',
            `week_start_date` DATE NOT NULL,
            `status` ENUM('Draft', 'Submitted', 'Approved', 'Rejected') DEFAULT 'Draft',
            `submitted_at` TIMESTAMP NULL DEFAULT NULL,
            `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
            `reviewer_id` INT(11) DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`compliance_id`),
            UNIQUE KEY `unique_week_faculty` (`faculty_id`, `week_identifier`),
            KEY `faculty_id` (`faculty_id`),
            FOREIGN KEY (`faculty_id`) REFERENCES `faculty`(`faculty_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        CREATE TABLE IF NOT EXISTS `iftl_entries` (
            `entry_id` INT(11) NOT NULL AUTO_INCREMENT,
            `compliance_id` INT(11) NOT NULL,
            `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
            `time_start` TIME NOT NULL,
            `time_end` TIME NOT NULL,
            `course_code` VARCHAR(20) DEFAULT NULL,
            `class_name` VARCHAR(100) DEFAULT NULL,
            `room` VARCHAR(50) DEFAULT NULL,
            `activity_type` VARCHAR(50) NOT NULL DEFAULT 'Class',
            `status` ENUM('Regular', 'Vacant', 'Leave', 'Makeup', 'Dismissed') DEFAULT 'Regular',
            `remarks` TEXT,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`entry_id`),
            KEY `compliance_id` (`compliance_id`),
            FOREIGN KEY (`compliance_id`) REFERENCES `iftl_weekly_compliance`(`compliance_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "IFTL tables created successfully.";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>


