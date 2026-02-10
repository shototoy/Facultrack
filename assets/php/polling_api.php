<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
require_once 'common_utilities.php';
initializeSession();
$pdo = initializeDatabase();
$php_timezone = date_default_timezone_get();
$mysql_timezone_map = [
    'Asia/Manila' => '+08:00',
    'Europe/Berlin' => '+01:00',
    'UTC' => '+00:00',
    'America/New_York' => '-05:00',
    'America/Los_Angeles' => '-08:00'
];
$mysql_timezone = '+08:00';
$pdo->exec("SET time_zone = '$mysql_timezone'");
$action = $_GET['action'] ?? $_POST['action'] ?? $_POST['admin_action'] ?? '';
if (basename($_SERVER['PHP_SELF']) === 'polling_api.php') {
    header('Content-Type: application/json');
} else {
    return;
}
function getAllFaculty($pdo) {
    $faculty_query = "
        SELECT 
            f.faculty_id,
            u.full_name,
            f.employee_id,
            f.program,
            f.office_hours,
            f.contact_email,
            f.contact_phone,
            f.current_location,
            f.last_location_update,
            CASE 
                WHEN f.is_active = 1 THEN f.status
                ELSE 'Offline'
            END as status,
            f.is_active as connection_status,
            f.status as activity_status,
            COALESCE(
                (SELECT CASE 
                    WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN CONCAT(TIMESTAMPDIFF(MINUTE, lh.time_set, NOW()), ' minutes ago')
                    WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, lh.time_set, NOW()), ' hours ago')
                    WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN CONCAT(TIMESTAMPDIFF(DAY, lh.time_set, NOW()), ' days ago')
                    ELSE 'Over a week ago'
                END
                FROM location_history lh 
                WHERE lh.faculty_id = f.faculty_id 
                ORDER BY lh.time_set DESC 
                LIMIT 1),
                'No location history'
            ) as last_updated
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id
        ORDER BY u.full_name
    ";
    $stmt = $pdo->prepare($faculty_query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getAllClasses($pdo) {
    $classes_query = "
        SELECT 
            c.class_id,
            c.class_code,
            c.class_name,
            c.year_level,
            c.semester,
            c.academic_year,
            u.full_name as program_chair_name,
            COUNT(s.schedule_id) as total_subjects
        FROM classes c
        LEFT JOIN faculty f ON c.program_chair_id = f.user_id
        LEFT JOIN users u ON f.user_id = u.user_id
        LEFT JOIN schedules s ON c.class_id = s.class_id AND s.is_active = TRUE
        WHERE c.is_active = TRUE
        GROUP BY c.class_id
        ORDER BY c.year_level, c.class_name";
    $stmt = $pdo->prepare($classes_query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getAllCourses($pdo) {
    $courses_query = "
        SELECT 
            c.course_id,
            c.course_code,
            c.course_description,
            c.units,
            COALESCE(p.program_name, 'General Education') as program_name,
            COUNT(s.schedule_id) as times_scheduled
        FROM courses c
        LEFT JOIN programs p ON c.program_id = p.program_id
        LEFT JOIN schedules s ON c.course_code = s.course_code AND s.is_active = TRUE
        WHERE c.is_active = TRUE
        GROUP BY c.course_id
        ORDER BY c.course_code";
    $stmt = $pdo->prepare($courses_query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllPrograms($pdo) {
    $programs_query = "
        SELECT 
            p.program_id,
            p.program_code,
            p.program_name,
            p.program_description,
            p.created_at,
            COUNT(c.course_id) as course_count
        FROM programs p
        LEFT JOIN courses c ON p.program_id = c.program_id AND c.is_active = TRUE
        WHERE p.is_active = TRUE
        GROUP BY p.program_id
        ORDER BY p.program_name";
    $stmt = $pdo->prepare($programs_query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getAllAnnouncements($pdo) {
    $all_announcements_query = "
        SELECT 
            a.announcement_id,
            a.title,
            a.content,
            a.priority,
            a.target_audience,
            a.created_at,
            u.full_name as created_by_name
        FROM announcements a
        JOIN users u ON a.created_by = u.user_id
        WHERE a.is_active = TRUE
        ORDER BY a.created_at DESC";
    $stmt = $pdo->prepare($all_announcements_query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getProgramChairs($pdo) {
    $program_chairs_query = "
        SELECT f.user_id, u.full_name, f.program
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id
        WHERE u.role = 'program_chair' AND f.is_active = TRUE
        ORDER BY u.full_name";
    $stmt = $pdo->prepare($program_chairs_query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getFacultyInfo($pdo, $user_id) {
    $faculty_query = "SELECT f.*, u.full_name FROM faculty f JOIN users u ON f.user_id = u.user_id WHERE f.user_id = ? AND u.is_active = TRUE";
    $stmt = $pdo->prepare($faculty_query);
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function getScheduleForDays($pdo, $faculty_id, $days) {
    $current_day = date('w');
    $day_mapping = [0 => 'S', 1 => 'M', 2 => 'T', 3 => 'W', 4 => 'TH', 5 => 'F', 6 => 'SAT'];
    $today_code = $day_mapping[$current_day];
    $viewing_day = $days;
    $schedule_query = "
        SELECT s.*, c.course_description, cl.class_name, cl.class_code,
            CASE 

                WHEN '$viewing_day' = '$today_code' THEN
                    CASE 
                        WHEN TIME(NOW()) > s.time_end THEN 'finished'
                        WHEN TIME(NOW()) BETWEEN s.time_start AND s.time_end THEN 'ongoing'
                        WHEN TIME(NOW()) < s.time_start THEN 'upcoming'
                        ELSE 'finished'
                    END

                WHEN (
                    ('$viewing_day' = 'M' AND '$today_code' IN ('T', 'W', 'TH', 'F', 'S')) OR
                    ('$viewing_day' = 'T' AND '$today_code' IN ('W', 'TH', 'F', 'S')) OR
                    ('$viewing_day' = 'W' AND '$today_code' IN ('TH', 'F', 'S')) OR
                    ('$viewing_day' = 'TH' AND '$today_code' IN ('F', 'S')) OR
                    ('$viewing_day' = 'F' AND '$today_code' = 'S') OR
                    ('$viewing_day' = 'S' AND '$today_code' = 'M')
                ) THEN 'finished'

                WHEN (
                    ('$viewing_day' = 'T' AND '$today_code' = 'M') OR
                    ('$viewing_day' = 'W' AND '$today_code' IN ('M', 'T')) OR
                    ('$viewing_day' = 'TH' AND '$today_code' IN ('M', 'T', 'W')) OR
                    ('$viewing_day' = 'F' AND '$today_code' IN ('M', 'T', 'W', 'TH')) OR
                    ('$viewing_day' = 'S' AND '$today_code' IN ('M', 'T', 'W', 'TH', 'F')) OR
                    ('$viewing_day' = 'M' AND '$today_code' = 'S')
                ) THEN 'not-today'
                ELSE 'not-today'
            END as status
        FROM schedules s
        JOIN courses c ON s.course_code = c.course_code
        JOIN classes cl ON s.class_id = cl.class_id
        WHERE s.faculty_id = ? AND s.is_active = TRUE AND s.faculty_id IS NOT NULL
        AND (
            (s.days = '$viewing_day') OR 
            (s.days = 'MW' AND '$viewing_day' IN ('M', 'W')) OR
            (s.days = 'MF' AND '$viewing_day' IN ('M', 'F')) OR
            (s.days = 'WF' AND '$viewing_day' IN ('W', 'F')) OR
            (s.days = 'MWF' AND '$viewing_day' IN ('M', 'W', 'F')) OR
            (s.days = 'TTH' AND '$viewing_day' IN ('T', 'TH')) OR
            (s.days = 'MTWTHF' AND '$viewing_day' IN ('M', 'T', 'W', 'TH', 'F'))
        )
        ORDER BY 
            CASE 
                WHEN '$viewing_day' = '$today_code' THEN
                    CASE 
                        WHEN TIME(NOW()) > s.time_end THEN 1
                        WHEN TIME(NOW()) BETWEEN s.time_start AND s.time_end THEN 2
                        WHEN TIME(NOW()) < s.time_start THEN 3
                        ELSE 4
                    END
                ELSE 5
            END,
            s.time_start";
    $stmt = $pdo->prepare($schedule_query);
    $stmt->execute([$faculty_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function generateScheduleHTML($schedule_data) {
    $html = '';
    foreach ($schedule_data as $schedule) {
        $status_info = getScheduleStatus($schedule['status']);
        $html .= '<div class="schedule-item ' . $schedule['status'] . '">';
        $html .= '<div class="schedule-time">';
        $html .= '<div class="time-display">' . date('g:i A', strtotime($schedule['time_start'])) . '</div>';
        $html .= '<div class="time-duration">' . date('g:i A', strtotime($schedule['time_end'])) . '</div>';
        $html .= '</div>';
        $html .= '<div class="schedule-details">';
        $html .= '<h4>' . htmlspecialchars($schedule['course_code']) . '</h4>';
        $html .= '<p>' . htmlspecialchars($schedule['course_description']) . '</p>';
        $html .= '<div class="schedule-info">';
        $html .= '<span>Class: ' . htmlspecialchars($schedule['class_name']) . '</span>';
        $html .= '<span>Room: ' . htmlspecialchars($schedule['room'] ?? 'TBA') . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="schedule-status">';
        $html .= '<span class="status-badge status-' . $status_info['class'] . '">' . $status_info['text'] . '</span>';
        if ($schedule['status'] === 'ongoing') {
            $html .= '<button class="attend-btn" onclick="markAttendance(' . $schedule['schedule_id'] . ')">Mark Attendance</button>';
        }
        $html .= '</div>';
        $html .= '</div>';
    }
    return $html;
}
function getScheduleStatus($status) {
    switch ($status) {
        case 'ongoing': return ['text' => 'In Progress', 'class' => 'ongoing'];
        case 'upcoming': return ['text' => 'Upcoming', 'class' => 'upcoming'];
        case 'finished': return ['text' => 'Completed', 'class' => 'finished'];
        case 'not-today': return ['text' => 'Not Today', 'class' => 'not-today'];
        default: return ['text' => 'Unknown', 'class' => 'unknown'];
    }
}
function markFacultyAttendance($pdo, $user_id, $schedule_id) {
    try {
        $pdo->beginTransaction();
    $location_query = "SELECT s.room, c.course_code, c.course_description 
                      FROM schedules s 
                      JOIN courses c ON s.course_code = c.course_code 
                      WHERE s.schedule_id = ? AND s.is_active = TRUE";
    $stmt = $pdo->prepare($location_query);
    $stmt->execute([$schedule_id]);
    $schedule_info = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$schedule_info) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Schedule not found'];
    }
    $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = ? AND is_active = TRUE";
    $stmt = $pdo->prepare($faculty_query);
    $stmt->execute([$user_id]);
    $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$faculty) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Faculty not found'];
    }
    $faculty_id = $faculty['faculty_id'];
    $location = !empty($schedule_info['room']) ? $schedule_info['room'] : 'In Class';
    $update_prev_query = "UPDATE location_history SET time_changed = NOW() WHERE faculty_id = ? AND time_changed IS NULL";
    $stmt = $pdo->prepare($update_prev_query);
    $stmt->execute([$faculty_id]);
    $insert_history_query = "INSERT INTO location_history (faculty_id, location, time_set) VALUES (?, ?, NOW())";
    $stmt = $pdo->prepare($insert_history_query);
    $stmt->execute([$faculty_id, $location]);
    $update_location_query = "UPDATE faculty SET current_location = ?, last_location_update = NOW() WHERE faculty_id = ?";
    $stmt = $pdo->prepare($update_location_query);
    $stmt->execute([$location, $faculty_id]);
        $pdo->commit();
        return [
            'success' => true,
            'message' => 'Attendance marked and location updated',
            'location' => $location,
            'course' => $schedule_info['course_code']
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}
function updateFacultyLocation($pdo, $user_id, $location) {
    try {
        $pdo->beginTransaction();
    $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = ? AND is_active = TRUE";
    $stmt = $pdo->prepare($faculty_query);
    $stmt->execute([$user_id]);
    $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$faculty) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Faculty not found'];
    }
    $faculty_id = $faculty['faculty_id'];
    $update_prev_query = "UPDATE location_history SET time_changed = NOW() WHERE faculty_id = ? AND time_changed IS NULL";
    $stmt = $pdo->prepare($update_prev_query);
    $stmt->execute([$faculty_id]);
    $insert_history_query = "INSERT INTO location_history (faculty_id, location, time_set) VALUES (?, ?, NOW())";
    $stmt = $pdo->prepare($insert_history_query);
    $stmt->execute([$faculty_id, $location]);
    $update_query = "UPDATE faculty SET current_location = ?, last_location_update = NOW() WHERE user_id = ?";
    $stmt = $pdo->prepare($update_query);
    $stmt->execute([$location, $user_id]);
        $pdo->commit();
        return [
            'success' => true,
            'message' => 'Location updated successfully',
            'location' => $location,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}
function getClassFaculty($pdo, $user_id) {
    $class_query = "SELECT class_id FROM classes WHERE user_id = ? AND is_active = TRUE";
    $stmt = $pdo->prepare($class_query);
    $stmt->execute([$user_id]);
    $class_info = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$class_info) return [];
    $class_id = $class_info['class_id'];
    $faculty_query = "
        SELECT DISTINCT
            f.faculty_id,
            u.full_name as faculty_name,
            f.current_location,
            CASE 
                WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN 'available'
                WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'busy'
                ELSE 'offline'
            END as status,
            f.last_location_update
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id
        JOIN schedules s ON f.faculty_id = s.faculty_id
        WHERE f.is_active = TRUE 
        AND s.is_active = TRUE 
        AND s.class_id = ?
        AND s.faculty_id IS NOT NULL";
    $stmt = $pdo->prepare($faculty_query);
    $stmt->execute([$class_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getFacultyCoursesForClass($pdo, $user_id) {
    $class_query = "SELECT class_id FROM classes WHERE user_id = ? AND is_active = TRUE";
    $stmt = $pdo->prepare($class_query);
    $stmt->execute([$user_id]);
    $class_info = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$class_info) return [];
    $class_id = $class_info['class_id'];
    $faculty_courses = [];
    $current_day = date('w');
    $day_mapping = [0 => 'S', 1 => 'M', 2 => 'T', 3 => 'W', 4 => 'TH', 5 => 'F', 6 => 'SAT'];
    $today_code = $day_mapping[$current_day];
    $courses_query = "
        SELECT s.faculty_id, s.course_code, c.course_description, s.time_start, s.time_end, s.room,
            CASE 
                WHEN TIME(NOW()) BETWEEN s.time_start AND s.time_end 
                     AND (
                         (s.days = '$today_code') OR
                         (s.days = 'MW' AND '$today_code' IN ('M', 'W')) OR
                         (s.days = 'MF' AND '$today_code' IN ('M', 'F')) OR
                         (s.days = 'WF' AND '$today_code' IN ('W', 'F')) OR
                         (s.days = 'MWF' AND '$today_code' IN ('M', 'W', 'F')) OR
                         (s.days = 'TTH' AND '$today_code' IN ('T', 'TH')) OR
                         (s.days = 'MTWTHF' AND '$today_code' IN ('M', 'T', 'W', 'TH', 'F'))
                     ) THEN 'current'
                WHEN TIME(NOW()) < s.time_start 
                     AND (
                         (s.days = '$today_code') OR
                         (s.days = 'MW' AND '$today_code' IN ('M', 'W')) OR
                         (s.days = 'MF' AND '$today_code' IN ('M', 'F')) OR
                         (s.days = 'WF' AND '$today_code' IN ('W', 'F')) OR
                         (s.days = 'MWF' AND '$today_code' IN ('M', 'W', 'F')) OR
                         (s.days = 'TTH' AND '$today_code' IN ('T', 'TH')) OR
                         (s.days = 'MTWTHF' AND '$today_code' IN ('M', 'T', 'W', 'TH', 'F'))
                     ) THEN 'upcoming'
                ELSE 'finished'
            END as status
        FROM schedules s
        JOIN courses c ON s.course_code = c.course_code
        WHERE s.class_id = ? AND s.is_active = TRUE
        ORDER BY s.time_start";
    $stmt = $pdo->prepare($courses_query);
    $stmt->execute([$class_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($courses as $course) {
        $faculty_courses[$course['faculty_id']][] = $course;
    }
    return $faculty_courses;
}
switch ($action) {
    case 'add_course':
    case 'add_faculty':
    case 'add_class':
    case 'add_announcement':
    case 'add_program':
    case 'delete_course':
    case 'delete_faculty':
    case 'delete_class':
    case 'delete_announcement':
    case 'delete_program':
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'];
        if ($action === 'delete_course') {
            $course_id = $_POST['course_id'] ?? '';
            if (empty($course_id)) {
                sendJsonResponse(['success' => false, 'message' => 'Course ID is required']);
                break;
            }
            try {
                $stmt = $pdo->prepare("SELECT course_code FROM courses WHERE course_id = ?");
                $stmt->execute([$course_id]);
                $course = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$course) {
                    sendJsonResponse(['success' => false, 'message' => 'Course not found']);
                    break;
                }
                $course_code = $course['course_code'];
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("DELETE FROM schedules WHERE course_code = ?");
                $stmt->execute([$course_code]);
                $stmt = $pdo->prepare("DELETE FROM curriculum WHERE course_code = ?");
                $stmt->execute([$course_code]);
                $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
                $stmt->execute([$course_id]);
                $pdo->commit();
                sendJsonResponse(['success' => true, 'message' => 'Course deleted successfully']);
            } catch (Exception $e) {
                if (isset($pdo)) {
                    $pdo->rollback();
                }
                sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
        } elseif ($action === 'delete_class') {
            $class_id = $_POST['class_id'] ?? '';
            if (empty($class_id)) {
                sendJsonResponse(['success' => false, 'message' => 'Class ID is required']);
                break;
            }
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("SELECT user_id FROM classes WHERE class_id = ?");
                $stmt->execute([$class_id]);
                $class_data = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$class_data) {
                    sendJsonResponse(['success' => false, 'message' => 'Class not found']);
                    break;
                }
                $stmt = $pdo->prepare("DELETE FROM schedules WHERE class_id = ?");
                $stmt->execute([$class_id]);
                $stmt = $pdo->prepare("DELETE FROM classes WHERE class_id = ?");
                $stmt->execute([$class_id]);
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$class_data['user_id']]);
                $pdo->commit();
                sendJsonResponse(['success' => true, 'message' => 'Class and user account deleted successfully']);
            } catch (Exception $e) {
                if (isset($pdo)) {
                    $pdo->rollback();
                }
                sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
        } elseif ($action === 'delete_faculty') {
            $faculty_id = $_POST['faculty_id'] ?? '';
            if (empty($faculty_id)) {
                sendJsonResponse(['success' => false, 'message' => 'Faculty ID is required']);
                break;
            }
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("SELECT user_id FROM faculty WHERE faculty_id = ?");
                $stmt->execute([$faculty_id]);
                $faculty_data = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$faculty_data) {
                    sendJsonResponse(['success' => false, 'message' => 'Faculty not found']);
                    break;
                }
                $stmt = $pdo->prepare("DELETE FROM schedules WHERE faculty_id = ?");
                $stmt->execute([$faculty_id]);
                $stmt = $pdo->prepare("DELETE FROM location_history WHERE faculty_id = ?");
                $stmt->execute([$faculty_id]);
                $stmt = $pdo->prepare("DELETE FROM faculty WHERE faculty_id = ?");
                $stmt->execute([$faculty_id]);
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$faculty_data['user_id']]);
                $pdo->commit();
                sendJsonResponse(['success' => true, 'message' => 'Faculty and user account deleted successfully']);
            } catch (Exception $e) {
                if (isset($pdo)) {
                    $pdo->rollback();
                }
                sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
        } elseif ($action === 'delete_announcement') {
            $announcement_id = $_POST['announcement_id'] ?? '';
            if (empty($announcement_id)) {
                sendJsonResponse(['success' => false, 'message' => 'Announcement ID is required']);
                break;
            }
            try {
                $stmt = $pdo->prepare("DELETE FROM announcements WHERE announcement_id = ?");
                $stmt->execute([$announcement_id]);
                if ($stmt->rowCount() > 0) {
                    sendJsonResponse(['success' => true, 'message' => 'Announcement deleted successfully']);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Announcement not found']);
                }
            } catch (Exception $e) {
                sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
        } elseif ($action === 'delete_program') {
            $program_id = $_POST['program_id'] ?? '';
            if (empty($program_id)) {
                sendJsonResponse(['success' => false, 'message' => 'Program ID is required']);
                break;
            }
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE program_id = ? AND is_active = TRUE");
                $stmt->execute([$program_id]);
                $courseCount = $stmt->fetchColumn();
                
                if ($courseCount > 0) {
                    $pdo->rollback();
                    sendJsonResponse(['success' => false, 'message' => 'Cannot delete program: ' . $courseCount . ' courses are still assigned to this program']);
                    break;
                }
                
                $stmt = $pdo->prepare("DELETE FROM programs WHERE program_id = ?");
                $stmt->execute([$program_id]);
                
                if ($stmt->rowCount() > 0) {
                    $pdo->commit();
                    sendJsonResponse(['success' => true, 'message' => 'Program deleted successfully']);
                } else {
                    $pdo->rollback();
                    sendJsonResponse(['success' => false, 'message' => 'Program not found']);
                }
            } catch (Exception $e) {
                if (isset($pdo)) {
                    $pdo->rollback();
                }
                sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
        } else {
            if (strpos($action, 'add_') === 0) {
                $config = [
                    'add_faculty' => [
                        'required' => ['full_name', 'username', 'password'],
                        'unique' => ['users.username'],
                        'user' => ['role' => function($d) {
                            return isset($d['is_program_chair']) && $d['is_program_chair'] == '1' ? 'program_chair' : 'faculty';
                        }],
                        'generate_id' => ['table' => 'faculty', 'column' => 'employee_id', 'prefix' => function($d) {
                            return isset($d['is_program_chair']) && $d['is_program_chair'] == '1' ? 'CHAIR-' : 'EMP-';
                        }],
                        'table' => 'faculty',
                        'fields' => ['program', 'office_hours', 'contact_email', 'contact_phone']
                    ],
                    'add_course' => [
                        'required' => ['course_code', 'course_description', 'units'],
                        'unique' => ['courses.course_code'],
                        'table' => 'courses',
                        'fields' => ['course_code', 'course_description', 'units', 'program_id']
                    ],
                    'add_program' => [
                        'required' => ['program_code', 'program_name'],
                        'unique' => ['programs.program_code'],
                        'table' => 'programs',
                        'fields' => ['program_code', 'program_name', 'program_description']
                    ],
                    'add_class' => [
                        'required' => ['class_name', 'class_code', 'year_level', 'semester', 'academic_year', 'username', 'password'],
                        'unique' => ['users.username', 'classes.class_code'],
                        'user' => ['role' => 'class'],
                        'table' => 'classes',
                        'fields' => ['class_code', 'class_name', 'year_level', 'semester', 'academic_year', 'total_students']
                    ],
                    'add_announcement' => [
                        'required' => ['title', 'content', 'priority', 'target_audience'],
                        'role_required' => 'campus_director',
                        'table' => 'announcements',
                        'fields' => ['title', 'content', 'priority', 'target_audience']
                    ]
                ];
                if (!isset($config[$action])) {
                    sendJsonResponse(['success' => false, 'message' => 'Invalid action']);
                    break;
                }
                $c = $config[$action];
                if (isset($c['role_required']) && $user_role !== $c['role_required']) {
                    sendJsonResponse(['success' => false, 'message' => 'Unauthorized']);
                    break;
                }
                foreach ($c['required'] as $field) {
                    if (empty($_POST[$field])) {
                        sendJsonResponse(['success' => false, 'message' => "Missing required field: $field"]);
                        break 2;
                    }
                }
                foreach ($c['unique'] ?? [] as $u) {
                    [$table, $column] = explode('.', $u);
                    $stmt = $pdo->prepare("SELECT 1 FROM $table WHERE $column = ?");
                    $stmt->execute([$_POST[$column]]);
                    if ($stmt->fetch()) {
                        sendJsonResponse(['success' => false, 'message' => ucfirst($column) . ' already exists']);
                        break 2;
                    }
                }
                try {
                    $pdo->beginTransaction();
                    $new_user_id = null;
                    if (isset($c['user'])) {
                        $role = is_callable($c['user']['role']) ? $c['user']['role']($_POST) : $c['user']['role'];
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                        $stmt->execute([
                            $_POST['username'],
                            $_POST['password'],
                            $_POST['full_name'] . ($role === 'class' ? ' Class Account' : ''),
                            $role
                        ]);
                        $new_user_id = $pdo->lastInsertId();
                    }
                    $insert_data = [];
                    if ($action === 'add_faculty') {
                        $role = is_callable($c['user']['role']) ? $c['user']['role']($_POST) : 'faculty';
                        if ($role === 'program_chair') {
                            if ($user_role === 'campus_director') {
                                $insert_data['program'] = $_POST['program'] ?? null;
                                if (!$insert_data['program']) {
                                    throw new Exception('Program is required for Program Chair');
                                }
                            } else {
                                $stmt = $pdo->prepare("SELECT program FROM faculty WHERE user_id = ? AND is_active = TRUE");
                                $stmt->execute([$user_id]);
                                $insert_data['program'] = $stmt->fetchColumn();
                            }
                        }
                        $prefix = $c['generate_id']['prefix']($_POST);
                        $unique = false;
                        $attempts = 0;
                        while (!$unique && $attempts < 5) {
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$c['generate_id']['table']} WHERE {$c['generate_id']['column']} LIKE ?");
                            $stmt->execute([$prefix . '%']);
                            $count = $stmt->fetchColumn();
                            // If attempts > 0, it means the ID generated from count collided, so add attempt count to skip ahead
                            $next_id = $prefix . str_pad($count + 1 + $attempts, 4, '0', STR_PAD_LEFT);
                            
                            $stmt = $pdo->prepare("SELECT 1 FROM {$c['generate_id']['table']} WHERE {$c['generate_id']['column']} = ?");
                            $stmt->execute([$next_id]);
                            if (!$stmt->fetch()) {
                                $insert_data['employee_id'] = $next_id;
                                $unique = true;
                            }
                            $attempts++;
                        }
                        if (!$unique) throw new Exception("Failed to generate unique Employee ID");
                    }
                    if ($action === 'add_class') {
                        if ($user_role === 'program_chair') {
                            $insert_data['program_chair_id'] = $user_id;
                        } else {
                            $insert_data['program_chair_id'] = $_POST['program_chair_id'] ?? null;
                            if (!$insert_data['program_chair_id']) {
                                throw new Exception('Program Chair ID is required for class creation');
                            }
                        }
                    }
                    if ($action === 'add_course') {
                        if ($user_role === 'program_chair') {
                            $stmt = $pdo->prepare("SELECT program FROM faculty WHERE user_id = ? AND is_active = TRUE");
                            $stmt->execute([$user_id]);
                            $insert_data['program'] = $stmt->fetchColumn();
                        }
                    }
                    if ($action === 'add_announcement') {
                        $insert_data['created_by'] = $user_id;
                    }
                    foreach ($c['fields'] as $field) {
                        if ($field === 'program_id' && $action === 'add_course') {
                            $program_id = $_POST[$field] ?? '';
                            $insert_data[$field] = ($program_id === '' || $program_id === 'general') ? null : (int)$program_id;
                        } else {
                            $insert_data[$field] = $_POST[$field] ?? null;
                        }
                    }
                    if ($new_user_id) {
                        $insert_data['user_id'] = $new_user_id;
                    }
                    $columns = array_keys($insert_data);
                    $placeholders = array_fill(0, count($columns), '?');
                    $stmt = $pdo->prepare("INSERT INTO {$c['table']} (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")");
                    $stmt->execute(array_values($insert_data));
                    $entity_id = $pdo->lastInsertId();
                    $pdo->commit();
                    $result = fetchAddedRecord($pdo, $action, $entity_id);
                    sendJsonResponse($result);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    sendJsonResponse(['success' => false, 'message' => $e->getMessage()]);
                }
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Unknown action: ' . $action]);
            }
        }
        break;
    case 'test':
        sendJsonResponse(['success' => true, 'message' => 'Polling API is working', 'timestamp' => date('Y-m-d H:i:s')]);
        break;
    case 'get_statistics':
        $response_data = [
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        if ($_SESSION['role'] === 'campus_director' || $_SESSION['role'] === 'program_chair') {
            $response_data['faculty_data'] = getAllFaculty($pdo);
            $response_data['classes_data'] = getAllClasses($pdo);
            $response_data['courses_data'] = getAllCourses($pdo);
            $response_data['announcements_data'] = getAllAnnouncements($pdo);
            $response_data['data'] = [
                'total_faculty' => count($response_data['faculty_data']),
                'total_classes' => count($response_data['classes_data']),
                'total_courses' => count($response_data['courses_data']),
                'active_announcements' => count($response_data['announcements_data']),
                'available_faculty' => count(array_filter($response_data['faculty_data'], function($f) {
                    return $f['status'] === 'Available';
                }))
            ];
        }
        sendJsonResponse($response_data);
        break;
    case 'get_location_updates':
        if (!isset($_SESSION['user_id'])) {
            sendJsonResponse(['success' => false, 'message' => 'Unauthorized access'], 401);
        }
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'];
        try {
            if ($user_role === 'class') {
                $class_query = "SELECT class_id FROM classes WHERE user_id = ? AND is_active = TRUE";
                $stmt = $pdo->prepare($class_query);
                $stmt->execute([$user_id]);
                $class_info = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$class_info) {
                    sendJsonResponse(['success' => false, 'message' => 'Class not found'], 404);
                }
                $class_id = $class_info['class_id'];
                $faculty_query = "
                    SELECT DISTINCT
                        f.faculty_id,
                        u.full_name as faculty_name,
                        f.current_location,
                        CASE 
                            WHEN f.is_active = 1 THEN f.status
                            ELSE 'Offline'
                        END as status,
                        COALESCE(
                            (SELECT CASE 
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN CONCAT(TIMESTAMPDIFF(MINUTE, lh.time_set, NOW()), ' minutes ago')
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, lh.time_set, NOW()), ' hours ago')
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN CONCAT(TIMESTAMPDIFF(DAY, lh.time_set, NOW()), ' days ago')
                                ELSE 'Over a week ago'
                            END
                            FROM location_history lh 
                            WHERE lh.faculty_id = f.faculty_id 
                            ORDER BY lh.time_set DESC 
                            LIMIT 1),
                            'No location history'
                        ) as last_updated
                    FROM faculty f
                    JOIN users u ON f.user_id = u.user_id
                    JOIN schedules s ON f.faculty_id = s.faculty_id
                    WHERE s.is_active = TRUE 
                    AND s.class_id = ?
                ";
                $stmt = $pdo->prepare($faculty_query);
                $stmt->execute([$class_id]);
                $faculty_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendJsonResponse([
                    'success' => true,
                    'faculty' => $faculty_data,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } elseif ($user_role === 'faculty') {
                $stmt = $pdo->prepare("UPDATE faculty SET is_active = 1 WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $faculty_query = "
                    SELECT 
                        f.faculty_id,
                        f.user_id,
                        u.full_name as faculty_name,
                        f.current_location,
                        f.last_location_update,
                        CASE 
                            WHEN f.is_active = 1 THEN f.status
                            ELSE 'Offline'
                        END as status,
                        COALESCE(
                            (SELECT CASE 
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 1 MINUTE) THEN 'Just now'
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 60 MINUTE) THEN CONCAT(TIMESTAMPDIFF(MINUTE, lh.time_set, NOW()), ' minutes ago')
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, lh.time_set, NOW()), ' hours ago')
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN CONCAT(TIMESTAMPDIFF(DAY, lh.time_set, NOW()), ' days ago')
                                ELSE 'Over a week ago'
                            END
                            FROM location_history lh 
                            WHERE lh.faculty_id = f.faculty_id 
                            ORDER BY lh.time_set DESC 
                            LIMIT 1),
                            'No location history'
                        ) as last_updated
                    FROM faculty f
                    JOIN users u ON f.user_id = u.user_id
                    WHERE f.user_id = ?
                ";
                $stmt = $pdo->prepare($faculty_query);
                $stmt->execute([$user_id]);
                $faculty_data = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($faculty_data) {
                    sendJsonResponse([
                        'success' => true,
                        'current_location' => $faculty_data['current_location'],
                        'status' => $faculty_data['status'],
                        'last_updated' => $faculty_data['last_updated'],
                        'faculty' => [$faculty_data],
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Faculty not found'], 404);
                }
            } elseif ($user_role === 'program_chair') {
                $faculty_query = "
                    SELECT DISTINCT
                        f.faculty_id,
                        u.full_name as faculty_name,
                        f.current_location,
                        CASE 
                            WHEN f.is_active = 1 THEN f.status
                            ELSE 'Offline'
                        END as status,
                        COALESCE(
                            (SELECT CASE 
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 1 MINUTE) THEN 'Just now'
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 60 MINUTE) THEN CONCAT(TIMESTAMPDIFF(MINUTE, lh.time_set, NOW()), ' minutes ago')
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, lh.time_set, NOW()), ' hours ago')
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN CONCAT(TIMESTAMPDIFF(DAY, lh.time_set, NOW()), ' days ago')
                                ELSE 'Over a week ago'
                            END
                            FROM location_history lh 
                            WHERE lh.faculty_id = f.faculty_id 
                            ORDER BY lh.time_set DESC 
                            LIMIT 1),
                            'No location history'
                        ) as last_updated
                    FROM faculty f
                    JOIN users u ON f.user_id = u.user_id
                ";
                $stmt = $pdo->prepare($faculty_query);
                $stmt->execute();
                $faculty_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendJsonResponse([
                    'success' => true,
                    'faculty' => $faculty_data,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } elseif ($user_role === 'campus_director') {
                $faculty_query = "
                    SELECT DISTINCT
                        f.faculty_id,
                        u.full_name as faculty_name,
                        f.current_location,
                        CASE 
                            WHEN f.is_active = 1 THEN f.status
                            ELSE 'Offline'
                        END as status,
                        COALESCE(
                            (SELECT CASE 
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 1 MINUTE) THEN 'Just now'
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 60 MINUTE) THEN CONCAT(TIMESTAMPDIFF(MINUTE, lh.time_set, NOW()), ' minutes ago')
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, lh.time_set, NOW()), ' hours ago')
                                WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN CONCAT(TIMESTAMPDIFF(DAY, lh.time_set, NOW()), ' days ago')
                                ELSE 'Over a week ago'
                            END
                            FROM location_history lh 
                            WHERE lh.faculty_id = f.faculty_id 
                            ORDER BY lh.time_set DESC 
                            LIMIT 1),
                            'No location history'
                        ) as last_updated
                    FROM faculty f
                    JOIN users u ON f.user_id = u.user_id
                ";
                $stmt = $pdo->prepare($faculty_query);
                $stmt->execute();
                $faculty_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendJsonResponse([
                    'success' => true,
                    'faculty' => $faculty_data,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Unauthorized role'], 403);
            }
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Error fetching location data: ' . $e->getMessage()]);
        }
        break;
    case 'update_status':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
            sendJsonResponse(['success' => false, 'message' => 'Unauthorized access'], 401);
        }
        $user_id = $_SESSION['user_id'];
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['Available', 'In Meeting', 'On Leave'])) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid status']);
        }
        try {
            $stmt = $pdo->prepare("UPDATE faculty SET status = ?, last_location_update = NOW() WHERE user_id = ?");
            $stmt->execute([$status, $user_id]);
            if ($stmt->rowCount() > 0) {
                sendJsonResponse(['success' => true, 'message' => 'Status updated successfully', 'status' => $status]);
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Faculty not found']);
            }
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
    case 'get_status':
        validateUserSession('faculty');
        try {
            $user_id = $_SESSION['user_id'];
            $status_query = "SELECT last_location_update FROM faculty WHERE user_id = ?";
            $stmt = $pdo->prepare($status_query);
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                sendJsonResponse(['success' => true, 'last_updated' => getTimeAgo($result['last_location_update'])]);
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Faculty not found']);
            }
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Database error']);
        }
        break;
    case 'get_location_history':
        validateUserSession('faculty');
        try {
            $user_id = $_SESSION['user_id'];
            $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = ? AND is_active = TRUE";
            $stmt = $pdo->prepare($faculty_query);
            $stmt->execute([$user_id]);
            $faculty_info = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$faculty_info) {
                sendJsonResponse(['success' => false, 'message' => 'Faculty not found']);
            }
            $limit = 10;
            $query = "SELECT location, time_set, time_changed 
                      FROM location_history 
                      WHERE faculty_id = ? 
                      ORDER BY time_set DESC 
                      LIMIT " . $limit;
            $stmt = $pdo->prepare($query);
            $stmt->execute([$faculty_info['faculty_id']]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $html = '';
            if (!empty($history)) {
                foreach ($history as $index => $h) {
                    $isCurrent = $index === 0 && $h['time_changed'] === null;
                    $timeDisplay = $h['time_changed'] 
                        ? date('M j, Y g:i A', strtotime($h['time_changed']))
                        : date('M j, Y g:i A', strtotime($h['time_set']));
                    $html .= '<div class="history-item' . ($isCurrent ? ' current-location' : '') . '">';
                    $html .= '<div class="history-location-name">' . htmlspecialchars($h['location']) . '</div>';
                    $html .= '<div class="history-timestamp">' . $timeDisplay . '</div>';
                    if ($isCurrent) {
                        $html .= '<div class="current-badge">Current</div>';
                    }
                    $html .= '</div>';
                }
            } else {
                $html = '<div class="no-history"><p>No location history available</p></div>';
            }
            sendJsonResponse(['success' => true, 'html' => $html]);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Database error']);
        }
        break;
    case 'get_iftl_faculty_list':
        validateUserSession('campus_director');
        try {
            $week_identifier = date('Y') . '-W' . date('W'); // Current week
            
            // Query faculty with IFTL status
            $query = "
                SELECT 
                    f.faculty_id, 
                    u.full_name, 
                    f.program, 
                    f.status,
                    f.current_location,
                    (SELECT COUNT(*) 
                     FROM iftl_weekly_compliance iwc 
                     WHERE iwc.faculty_id = f.faculty_id 
                     AND iwc.week_identifier = ?) as has_iftl
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                WHERE u.role NOT IN ('program_chair', 'campus_director')
                ORDER BY u.full_name ASC
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$week_identifier]);
            $faculty_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Debugging if empty: Check total faculty count without filter
            if (empty($faculty_list)) { 
                $debug_query = "SELECT u.full_name, u.role FROM faculty f JOIN users u ON f.user_id = u.user_id WHERE f.is_active = TRUE";
                $stmt_debug = $pdo->prepare($debug_query);
                $stmt_debug->execute();
                $all_faculty = $stmt_debug->fetchAll(PDO::FETCH_ASSOC);
                 // If you want to see this in network tab, temporary change response structure
                 // sendJsonResponse(['success' => true, 'faculty' => [], 'debug_all' => $all_faculty, 'week' => $week_identifier]); 
                 // But for now, let's just make sure the filter isn't too aggressive. 
                 // It is possible the role stored in DB is 'Program Chair' not 'program_chair' or something?
                 // But wait, the standard get_dashboard_data works. 
                 // Let's try removing the filter temporarily to see if data appears.
            }
            
            sendJsonResponse([
                'success' => true,
                'faculty' => $faculty_list,
                'current_week' => $week_identifier
            ]);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
    case 'get_dashboard_data':
        $tab = $_GET['tab'] ?? 'faculty';
        $role = $_SESSION['role'];
            $last_update = $_GET['last_update'] ?? '1970-01-01 00:00:00';
        if (!in_array($role, ['campus_director', 'program_chair', 'class', 'faculty'])) {
            sendJsonResponse(['success' => false, 'message' => 'Unauthorized access'], 403);
        }
        try {
            $response_data = [
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s'),
                'has_changes' => true,
                'optimized' => true
            ];
            if ($role === 'campus_director') {
                switch ($tab) {
                    case 'faculty':
                        $response_data['faculty_data'] = getAllFaculty($pdo);
                        break;
                    case 'classes':
                        $response_data['classes_data'] = getAllClasses($pdo);
                        break;
                    case 'courses':
                        $response_data['courses_data'] = getAllCourses($pdo);
                        break;
                    case 'announcements':
                        $announcements_data = getAllAnnouncements($pdo);
                        $response_data['announcements_data'] = $announcements_data;
                        $response_data['count'] = count($announcements_data);
                        $response_data['announcements'] = array_slice($announcements_data, 0, 10);
                        break;
                    case 'programs':
                        $response_data['programs_data'] = getAllPrograms($pdo);
                        break;
                    case 'all':
                        $response_data['faculty_data'] = getAllFaculty($pdo);
                        $response_data['classes_data'] = getAllClasses($pdo);
                        $response_data['courses_data'] = getAllCourses($pdo);
                        $response_data['announcements_data'] = getAllAnnouncements($pdo);
                        $response_data['programs_data'] = getAllPrograms($pdo);
                        $response_data['program_chairs'] = getProgramChairs($pdo);
                        break;
                }
            } else if ($role === 'program_chair') {
                $user_id = $_SESSION['user_id'];
                $classes_query = "
                    SELECT 
                        c.class_id,
                        c.class_code,
                        c.class_name,
                        c.year_level,
                        c.semester,
                        c.academic_year,
                        c.program_chair_id,
                        u.full_name as program_chair_name,
                        COUNT(s.schedule_id) as total_subjects
                    FROM classes c
                    LEFT JOIN faculty f ON c.program_chair_id = f.user_id
                    LEFT JOIN users u ON f.user_id = u.user_id
                    LEFT JOIN schedules s ON c.class_id = s.class_id AND s.is_active = TRUE
                    WHERE c.is_active = TRUE AND c.program_chair_id = ?
                    GROUP BY c.class_id
                    ORDER BY c.year_level, c.class_name";
                $stmt = $pdo->prepare($classes_query);
                $stmt->execute([$user_id]);
                $response_data['classes_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response_data['faculty_data'] = getAllFaculty($pdo);
                
                $stmt = $pdo->prepare("SELECT program FROM faculty WHERE user_id = ? AND is_active = TRUE");
                $stmt->execute([$user_id]);
                $program_chair_program = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("SELECT program_id FROM programs WHERE program_name = ? AND is_active = TRUE");
                $stmt->execute([$program_chair_program]);
                $program_id = $stmt->fetchColumn();
                
                $courses_query = "
                    SELECT 
                        c.course_id,
                        c.course_code,
                        c.course_description,
                        c.units,
                        COALESCE(p.program_name, 'General Education') as program_name,
                        COUNT(s.schedule_id) as times_scheduled,
                        CASE WHEN c.program_id IS NULL THEN 1 ELSE 0 END as is_general_education
                    FROM courses c
                    LEFT JOIN programs p ON c.program_id = p.program_id
                    LEFT JOIN schedules s ON c.course_code = s.course_code AND s.is_active = TRUE
                    WHERE c.is_active = TRUE 
                    AND (c.program_id = ? OR c.program_id IS NULL)
                    GROUP BY c.course_id
                    ORDER BY c.course_code";
                $stmt = $pdo->prepare($courses_query);
                $stmt->execute([$program_id]);
                $response_data['courses_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else if ($role === 'class') {
                $user_id = $_SESSION['user_id'];
                $class_query = "SELECT class_id FROM classes WHERE user_id = ? AND is_active = TRUE";
                $stmt = $pdo->prepare($class_query);
                $stmt->execute([$user_id]);
                $class_info = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($class_info) {
                    $class_id = $class_info['class_id'];
                    $faculty_query = "
                        SELECT f.faculty_id, u.full_name as faculty_name, f.program, f.current_location, f.last_location_update,
                               f.office_hours, f.contact_email, f.contact_phone, f.is_active,
                               CASE 
                                   WHEN f.is_active = 1 THEN f.status
                                   ELSE 'Offline'
                               END as status,
                               COALESCE(
                                   (SELECT CASE 
                                       WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN CONCAT(TIMESTAMPDIFF(MINUTE, lh.time_set, NOW()), ' minutes ago')
                                       WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, lh.time_set, NOW()), ' hours ago')
                                       WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN CONCAT(TIMESTAMPDIFF(DAY, lh.time_set, NOW()), ' days ago')
                                       ELSE 'Over a week ago'
                                   END
                                   FROM location_history lh 
                                   WHERE lh.faculty_id = f.faculty_id 
                                   ORDER BY lh.time_set DESC 
                                   LIMIT 1),
                                   'No location history'
                               ) as last_updated
                        FROM faculty f
                        JOIN users u ON f.user_id = u.user_id
                        JOIN schedules s ON f.faculty_id = s.faculty_id
                        WHERE s.is_active = TRUE AND s.class_id = ? AND s.faculty_id IS NOT NULL
                        GROUP BY f.faculty_id
                        ORDER BY u.full_name";
                    $stmt = $pdo->prepare($faculty_query);
                    $stmt->execute([$class_id]);
                    $response_data['faculty_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $response_data['faculty_data'] = [];
                }
            } else if ($role === 'faculty') {
                $user_id = $_SESSION['user_id'];
                $stmt = $pdo->prepare("
                    SELECT 
                        f.faculty_id,
                        u.full_name,
                        f.employee_id,
                        f.program,
                        f.office_hours,
                        f.contact_email,
                        f.contact_phone,
                        f.current_location,
                        f.last_location_update,
                        CASE 
                            WHEN f.is_active = 1 THEN f.status
                            ELSE 'Offline'
                        END as status,
                        f.is_active as connection_status,
                        f.status as activity_status
                    FROM faculty f
                    JOIN users u ON f.user_id = u.user_id
                    WHERE f.user_id = ?
                ");
                $stmt->execute([$user_id]);
                $faculty_data = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($faculty_data) {
                    $response_data['current_entities'] = ['faculty' => [$faculty_data]];
                }
                
                if ($tab === 'announcements') {
                    $announcements_query = "
                        SELECT 
                            a.announcement_id,
                            a.title,
                            a.content,
                            a.priority,
                            a.target_audience,
                            a.created_at,
                            u.full_name as created_by_name
                        FROM announcements a
                        JOIN users u ON a.created_by = u.user_id
                        WHERE a.is_active = TRUE 
                        AND (a.target_audience = 'all' OR a.target_audience = 'faculty')
                        ORDER BY a.created_at DESC";
                    $stmt = $pdo->prepare($announcements_query);
                    $stmt->execute();
                    $announcements_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $response_data['announcements_data'] = $announcements_data;
                    $response_data['count'] = count($announcements_data);
                    $response_data['announcements'] = array_slice($announcements_data, 0, 10);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Unauthorized tab access for faculty'], 403);
                    break;
                }
            }
            $response_data['changes'] = detectDataChanges($pdo, $role, $tab);
            $response_data['current_entities'] = getAllCurrentEntities($pdo, $role, $tab);
            if (($role === 'campus_director' || $role === 'class') && isset($response_data['faculty_data'])) {
                $response_data['current_entities']['faculty'] = $response_data['faculty_data'];
            }
            sendJsonResponse($response_data);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_programs':
        $programs = getAllPrograms($pdo);
        sendJsonResponse(['success' => true, 'programs' => $programs]);
        break;
        
    case 'get_program_courses':
        $program_id = $_GET['program_id'] ?? $_POST['program_id'] ?? null;
        if (!$program_id) {
            sendJsonResponse(['success' => false, 'message' => 'Program ID required']);
            break;
        }
        
        $courses_query = "
            SELECT c.course_id, c.course_code, c.course_description, c.units,
                   COUNT(s.schedule_id) as times_scheduled
            FROM courses c
            LEFT JOIN schedules s ON c.course_code = s.course_code AND s.is_active = TRUE
            WHERE c.program_id = ? AND c.is_active = TRUE
            GROUP BY c.course_id
            ORDER BY c.course_code";
        $stmt = $pdo->prepare($courses_query);
        $stmt->execute([$program_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse(['success' => true, 'courses' => $courses]);
        break;
    case 'get_schedule_updates':
        validateUserSession('faculty');
        try {
            $user_id = $_SESSION['user_id'];
            $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = ? AND is_active = TRUE";
            $stmt = $pdo->prepare($faculty_query);
            $stmt->execute([$user_id]);
            $faculty_info = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$faculty_info) {
                sendJsonResponse(['success' => false, 'message' => 'Faculty not found']);
                break;
            }
            $faculty_id = $faculty_info['faculty_id'];
            $current_day = date('w');
            $day_mapping = [0 => 'S', 1 => 'M', 2 => 'T', 3 => 'W', 4 => 'TH', 5 => 'F', 6 => 'SAT'];
            $today_code = $day_mapping[$current_day];
            $schedule_query = "
                SELECT s.*, c.course_description, cl.class_name, cl.class_code, cl.total_students,
                    CASE 
                        WHEN TIME(NOW()) BETWEEN s.time_start AND s.time_end 
                             AND (
                                 (s.days = '$today_code') OR
                                 (s.days = 'MW' AND '$today_code' IN ('M', 'W')) OR
                                 (s.days = 'MF' AND '$today_code' IN ('M', 'F')) OR
                                 (s.days = 'WF' AND '$today_code' IN ('W', 'F')) OR
                                 (s.days = 'MWF' AND '$today_code' IN ('M', 'W', 'F')) OR
                                 (s.days = 'TTH' AND '$today_code' IN ('T', 'TH')) OR
                                 (s.days = 'MTWTHF' AND '$today_code' IN ('M', 'T', 'W', 'TH', 'F'))
                             ) THEN 'ongoing'
                        WHEN TIME(NOW()) < s.time_start 
                             AND (
                                 (s.days = '$today_code') OR
                                 (s.days = 'MW' AND '$today_code' IN ('M', 'W')) OR
                                 (s.days = 'MF' AND '$today_code' IN ('M', 'F')) OR
                                 (s.days = 'WF' AND '$today_code' IN ('W', 'F')) OR
                                 (s.days = 'MWF' AND '$today_code' IN ('M', 'W', 'F')) OR
                                 (s.days = 'TTH' AND '$today_code' IN ('T', 'TH')) OR
                                 (s.days = 'MTWTHF' AND '$today_code' IN ('M', 'T', 'W', 'TH', 'F'))
                             ) THEN 'upcoming'
                        ELSE 'finished'
                    END as status
                FROM schedules s
                JOIN courses c ON s.course_code = c.course_code
                JOIN classes cl ON s.class_id = cl.class_id
                WHERE s.faculty_id = ? AND s.is_active = TRUE
                AND (
                    (s.days = '$today_code') OR
                    (s.days = 'MW' AND '$today_code' IN ('M', 'W')) OR
                    (s.days = 'MF' AND '$today_code' IN ('M', 'F')) OR
                    (s.days = 'WF' AND '$today_code' IN ('W', 'F')) OR
                    (s.days = 'MWF' AND '$today_code' IN ('M', 'W', 'F')) OR
                    (s.days = 'TTH' AND '$today_code' IN ('T', 'TH')) OR
                    (s.days = 'MTWTHF' AND '$today_code' IN ('M', 'T', 'W', 'TH', 'F'))
                )
                ORDER BY 
                    CASE 
                        WHEN TIME(NOW()) > s.time_end THEN 1
                        WHEN TIME(NOW()) BETWEEN s.time_start AND s.time_end THEN 2
                        WHEN TIME(NOW()) < s.time_start THEN 3
                        ELSE 4
                    END,
                    s.time_start";
            $stmt = $pdo->prepare($schedule_query);
            $stmt->execute([$faculty_id]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);


            $stats = [
                'today' => count($schedules),
                'ongoing' => 0,
                'completed' => 0
            ];
            foreach ($schedules as $s) {
                if ($s['status'] === 'ongoing') $stats['ongoing']++;
                if ($s['status'] === 'finished') $stats['completed']++;
            }
            

            $status_stmt = $pdo->prepare("SELECT status FROM faculty WHERE faculty_id = ?");
            $status_stmt->execute([$faculty_id]);
            $stats['status'] = $status_stmt->fetchColumn() ?: 'Offline';

            sendJsonResponse([
                'success' => true,
                'schedules' => $schedules,
                'stats' => $stats,
                'current_time' => date('H:i:s'),
                'current_day' => $today_code,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
    case 'get_full_faculty_schedule':
        if ($_SESSION['role'] !== 'campus_director' && $_SESSION['role'] !== 'program_chair') {
            sendJsonResponse(['success' => false, 'message' => 'Unauthorized access'], 403);
            break;
        }
        $faculty_id = $_POST['faculty_id'] ?? '';
        if (empty($faculty_id)) {
            sendJsonResponse(['success' => false, 'message' => 'Faculty ID is required']);
            break;
        }
        try {
            // Check for IFTL compliance for the current week
            $current_week = date('Y') . '-W' . date('W');
            $compliance_query = "SELECT compliance_id FROM iftl_weekly_compliance WHERE faculty_id = ? AND week_identifier = ?";
            $stmt = $pdo->prepare($compliance_query);
            $stmt->execute([$faculty_id, $current_week]);
            $compliance_id = $stmt->fetchColumn();

            if ($compliance_id) {
                // Fetch from IFTL entries
                $schedule_query = "
                    SELECT 
                        e.entry_id as schedule_id,
                        e.time_start,
                        e.time_end,
                        e.day_of_week,
                        CASE 
                            WHEN e.day_of_week = 'Monday' THEN 'M'
                            WHEN e.day_of_week = 'Tuesday' THEN 'T'
                            WHEN e.day_of_week = 'Wednesday' THEN 'W'
                            WHEN e.day_of_week = 'Thursday' THEN 'TH'
                            WHEN e.day_of_week = 'Friday' THEN 'F'
                            WHEN e.day_of_week = 'Saturday' THEN 'S'
                            WHEN e.day_of_week = 'Sunday' THEN 'SUN'
                            ELSE '' 
                        END as days,
                        CASE 
                            WHEN e.status = 'Leave' THEN 'LEAVE'
                            WHEN e.status = 'Vacant' THEN 'VACANT'
                            WHEN e.course_code IS NOT NULL AND e.course_code != '' THEN e.course_code
                            ELSE e.activity_type
                        END as course_code,
                        e.room,
                        e.class_name,
                        e.status,
                        c.course_description,
                        c.units,
                        cl.total_students,
                        'iftl' as source
                    FROM iftl_entries e
                    LEFT JOIN courses c ON e.course_code = c.course_code
                    LEFT JOIN classes cl ON e.class_name = cl.class_name
                    WHERE e.compliance_id = ?
                    ORDER BY e.time_start";
                
                $stmt = $pdo->prepare($schedule_query);
                $stmt->execute([$compliance_id]);
                $schedule_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Fallback to standard schedule
                $schedule_query = "
                    SELECT s.*, c.course_description, cl.class_name, cl.class_code, cl.total_students,
                           'upcoming' as status, 'standard' as source
                    FROM schedules s
                    JOIN courses c ON s.course_code = c.course_code
                    JOIN classes cl ON s.class_id = cl.class_id
                    WHERE s.faculty_id = ? AND s.is_active = TRUE
                    ORDER BY s.time_start";
                
                $stmt = $pdo->prepare($schedule_query);
                $stmt->execute([$faculty_id]);
                $schedule_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            sendJsonResponse([
                'success' => true, 
                'schedules' => $schedule_data,
                'is_iftl' => !!$compliance_id,
                'week' => $current_week,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;

    case 'get_schedule':
        validateUserSession('faculty');
        $days = $_POST['days'] ?? '';
        $user_id = $_SESSION['user_id'];
        try {
            $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = ? AND is_active = TRUE";
            $stmt = $pdo->prepare($faculty_query);
            $stmt->execute([$user_id]);
            $faculty_info = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$faculty_info) {
                sendJsonResponse(['success' => false, 'message' => 'Faculty not found']);
                break;
            }
            $faculty_id = $faculty_info['faculty_id'];
            $schedule_data = getScheduleForDays($pdo, $faculty_id, $days);
            sendJsonResponse([
                'success' => true, 
                'schedules' => $schedule_data,
                'tab' => $days,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;
    case 'mark_attendance':
        validateUserSession('faculty');
        $user_id = $_SESSION['user_id'];
        $schedule_id = validateInput($_POST['schedule_id'] ?? '');
        if (empty($schedule_id)) {
            sendJsonResponse(['success' => false, 'message' => 'Schedule ID is required']);
        }
        try {
            $result = markFacultyAttendance($pdo, $user_id, $schedule_id);
            sendJsonResponse($result);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;
    case 'update_location':
        validateUserSession('faculty');
        $user_id = $_SESSION['user_id'];
        $location = validateInput($_POST['location'] ?? '');
        if (empty($location)) {
            sendJsonResponse(['success' => false, 'message' => 'Location cannot be empty']);
        }
        try {
            $result = updateFacultyLocation($pdo, $user_id, $location);
            sendJsonResponse($result);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;
    case 'get_classes_for_semester':
        validateUserSession('campus_director');
        try {
            $semester = $_POST['semester'] ?? '';
            $academic_year = $_POST['academic_year'] ?? '';
            if (empty($semester) || empty($academic_year)) {
                sendJsonResponse(['success' => false, 'message' => 'Semester and academic year are required']);
                break;
            }
            $classes_query = "
                SELECT c.class_id, c.class_code, c.class_name, c.year_level, c.total_students,
                       u.full_name as program_chair_name,
                       COUNT(s.schedule_id) as current_subjects,
                       c.semester as current_semester,
                       c.academic_year as current_academic_year
                FROM classes c
                LEFT JOIN faculty f ON c.program_chair_id = f.user_id
                LEFT JOIN users u ON f.user_id = u.user_id
                LEFT JOIN schedules s ON c.class_id = s.class_id AND s.is_active = TRUE
                WHERE c.is_active = TRUE
                GROUP BY c.class_id
                ORDER BY c.year_level, c.class_name";
            $stmt = $pdo->prepare($classes_query);
            $stmt->execute();
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendJsonResponse([
                'success' => true,
                'classes' => $classes
            ]);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;
    case 'update_semester':
        validateUserSession('campus_director');
        try {
            $semester = $_POST['semester'] ?? '';
            $academic_year = $_POST['academic_year'] ?? '';
            if (empty($semester) || empty($academic_year)) {
                sendJsonResponse(['success' => false, 'message' => 'Semester and academic year are required']);
                break;
            }
            $pdo->beginTransaction();
            $classes_query = "
                SELECT c.class_id, c.year_level, c.program_chair_id 
                FROM classes c
                WHERE c.is_active = TRUE";
            $stmt = $pdo->prepare($classes_query);
            $stmt->execute();
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($classes)) {
                $pdo->rollback();
                sendJsonResponse(['success' => false, 'message' => 'No classes found for the selected semester']);
                break;
            }
            $update_classes_query = "
                UPDATE classes 
                SET semester = ?, academic_year = ?, updated_at = NOW()
                WHERE class_id IN (" . implode(',', array_column($classes, 'class_id')) . ")";
            $stmt = $pdo->prepare($update_classes_query);
            $stmt->execute([$semester, $academic_year]);
            $updated_classes = 0;
            $total_schedules_added = 0;
            foreach ($classes as $class) {
                $class_id = $class['class_id'];
                $year_level = $class['year_level'];
                $program_chair_id = $class['program_chair_id'];
                $delete_schedules = "DELETE FROM schedules WHERE class_id = ?";
                $stmt = $pdo->prepare($delete_schedules);
                $stmt->execute([$class_id]);
                $curriculum_query = "
                    SELECT curr.course_code 
                    FROM curriculum curr
                    WHERE curr.year_level = ? 
                    AND curr.semester = ? 
                    AND curr.program_chair_id = ?
                    AND curr.is_active = TRUE";
                $stmt = $pdo->prepare($curriculum_query);
                $stmt->execute([$year_level, $semester, $program_chair_id]);
                $curriculum_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($curriculum_courses as $course) {
                    $insert_schedule = "
                        INSERT INTO schedules (course_code, class_id, faculty_id, section, days, time_start, time_end, 
                                             room, semester, academic_year, is_active, created_at, updated_at) 
                        VALUES (?, ?, NULL, 'TBA', 'TBA', '00:00:00', '00:00:00', 'TBA', ?, ?, TRUE, NOW(), NOW())";
                    $stmt = $pdo->prepare($insert_schedule);
                    $stmt->execute([
                        $course['course_code'],
                        $class_id,
                        $semester,
                        $academic_year
                    ]);
                    $total_schedules_added++;
                }
                $updated_classes++;
            }
            $pdo->commit();
            sendJsonResponse([
                'success' => true,
                'message' => "Successfully updated {$updated_classes} classes with {$total_schedules_added} courses from curriculum. All faculty assignments have been reset."
            ]);
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollback();
            }
            sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;

    case 'get_faculty_details':
        validateUserSession('campus_director');
        $faculty_id = $_GET['faculty_id'] ?? null;
        if (!$faculty_id) {
            sendJsonResponse(['success' => false, 'message' => 'Faculty ID is required']);
        }
        
        $stmt = $pdo->prepare("SELECT f.*, u.username, u.full_name, u.role, u.is_active as user_active FROM faculty f JOIN users u ON f.user_id = u.user_id WHERE f.faculty_id = ?");
        $stmt->execute([$faculty_id]);
        $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($faculty) {
            sendJsonResponse(['success' => true, 'data' => $faculty]);
        } else {
            sendJsonResponse(['success' => false, 'message' => 'Faculty not found']);
        }
        break;

    case 'update_faculty':
        validateUserSession('campus_director');
        try {
            $pdo->beginTransaction();
            
            $faculty_id = $_POST['faculty_id'];
            $full_name = trim($_POST['full_name']);
            $username = trim($_POST['username']);
            $program = $_POST['program'] ?? null;
            $contact_email = $_POST['contact_email'] ?? null;
            $contact_phone = $_POST['contact_phone'] ?? null;
            // Password update is optional
            $password = !empty($_POST['password']) ? $_POST['password'] : null;
            
            // Get user_id
            $stmt = $pdo->prepare("SELECT user_id FROM faculty WHERE faculty_id = ?");
            $stmt->execute([$faculty_id]);
            $user_id = $stmt->fetchColumn();
            
            if (!$user_id) throw new Exception("Faculty not found");
            
            // Allow checking for duplicates (username) if username changed?
            // Simplified for now.
            
            // Update users table
            $user_sql = "UPDATE users SET full_name = ?, username = ? " . ($password ? ", password = ?" : "") . " WHERE user_id = ?";
            $user_params = $password ? [$full_name, $username, $password, $user_id] : [$full_name, $username, $user_id];
            
            $stmt = $pdo->prepare($user_sql);
            $stmt->execute($user_params);
            
            // Update faculty table
            $faculty_sql = "UPDATE faculty SET program = ?, contact_email = ?, contact_phone = ? WHERE faculty_id = ?";
            $stmt = $pdo->prepare($faculty_sql);
            $stmt->execute([$program, $contact_email, $contact_phone, $faculty_id]);
            
            $pdo->commit();
            sendJsonResponse(['success' => true, 'message' => 'Faculty updated successfully']);
        } catch (Exception $e) {
            $pdo->rollback();
            sendJsonResponse(['success' => false, 'message' => 'Error updating faculty: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_faculty_details':
        validateUserSession('campus_director');
        try {
            $faculty_id = $_GET['faculty_id'] ?? null;
            if (!$faculty_id) {
                sendJsonResponse(['success' => false, 'message' => 'Faculty ID required']);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT f.*, u.full_name, u.username
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                WHERE f.faculty_id = ?
            ");
            $stmt->execute([$faculty_id]);
            $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($faculty) {
                sendJsonResponse(['success' => true, 'data' => $faculty]);
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Faculty not found']);
            }
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_announcement_details':
        validateUserSession('campus_director');
        try {
            $announcement_id = $_GET['announcement_id'] ?? null;
            if (!$announcement_id) {
                sendJsonResponse(['success' => false, 'message' => 'Announcement ID required']);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT a.*, u.full_name as created_by_name
                FROM announcements a
                JOIN users u ON a.created_by = u.user_id
                WHERE a.announcement_id = ?
            ");
            $stmt->execute([$announcement_id]);
            $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($announcement) {
                sendJsonResponse(['success' => true, 'data' => $announcement]);
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Announcement not found']);
            }
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;
        
    case 'update_announcement':
        validateUserSession('campus_director');
        try {
            $pdo->beginTransaction();
            
            $announcement_id = $_POST['announcement_id'] ?? null;
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $priority = $_POST['priority'] ?? 'normal';
            $target_audience = $_POST['target_audience'] ?? 'all';
            
            if (!$announcement_id || !$title || !$content) {
                throw new Exception('Missing required fields');
            }
            
            $stmt = $pdo->prepare("
                UPDATE announcements 
                SET title = ?, content = ?, priority = ?, target_audience = ?
                WHERE announcement_id = ?
            ");
            $stmt->execute([$title, $content, $priority, $target_audience, $announcement_id]);
            
            if ($stmt->rowCount() > 0) {
                $pdo->commit();
                sendJsonResponse(['success' => true, 'message' => 'Announcement updated successfully']);
            } else {
                throw new Exception('Announcement not found or no changes made');
            }
        } catch (Exception $e) {
            $pdo->rollback();
            sendJsonResponse(['success' => false, 'message' => 'Error updating announcement: ' . $e->getMessage()]);
        }
        break;
    case 'get_iftl_weeks':
        $weeks = [];
        // Start from last Monday for consistency
        $base_date = strtotime('last Monday', strtotime('tomorrow'));
        // Current week + next 4 weeks + past 4 weeks
        for ($i = -4; $i <= 4; $i++) {
            $week_start = date('Y-m-d', strtotime("$i weeks", $base_date));
            $week_end = date('Y-m-d', strtotime("$week_start +6 days"));
            $week_identifier = date('Y-\WW', strtotime($week_start));
            $is_current = (date('Y-m-d') >= $week_start && date('Y-m-d') <= $week_end);
            $weeks[] = [
                'identifier' => $week_identifier,
                'label' => ($is_current ? "[Current] " : "") . date('M d', strtotime($week_start)) . " - " . date('M d', strtotime($week_end)),
                'start_date' => $week_start,
                'is_current' => $is_current
            ];
        }
        sendJsonResponse(['success' => true, 'weeks' => $weeks]);
        break;

    case 'get_faculty_iftl':
        $target_faculty_id = $_POST['faculty_id'] ?? $_SESSION['faculty_id'] ?? null;
        if (!$target_faculty_id && $_SESSION['role'] === 'faculty') {
             $stmt = $pdo->prepare("SELECT faculty_id FROM faculty WHERE user_id = ?");
             $stmt->execute([$_SESSION['user_id']]);
             $target_faculty_id = $stmt->fetchColumn();
        }
        $week = $_POST['week'] ?? date('Y-\WW');
        
        // Check if compliance record exists
        $stmt = $pdo->prepare("SELECT * FROM iftl_weekly_compliance WHERE faculty_id = ? AND week_identifier = ?");
        $stmt->execute([$target_faculty_id, $week]);
        $compliance = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($compliance && !isset($_POST['reset'])) {
            $stmt = $pdo->prepare("SELECT * FROM iftl_entries WHERE compliance_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), time_start");
            $stmt->execute([$compliance['compliance_id']]);
            $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $compliance = ['status' => 'None'];
            $entries = generateIFTLFromStandard($pdo, $target_faculty_id);
        }
        sendJsonResponse(['success' => true, 'compliance' => $compliance, 'entries' => $entries]);
        break;

    case 'save_iftl':
        $compliance_id = $_POST['compliance_id'] ?? null;
        $faculty_id = $_POST['faculty_id'] ?? $_SESSION['faculty_id'] ?? null;
        
        if (!$faculty_id && $_SESSION['role'] === 'faculty') {
             $stmt = $pdo->prepare("SELECT faculty_id FROM faculty WHERE user_id = ?");
             $stmt->execute([$_SESSION['user_id']]);
             $faculty_id = $stmt->fetchColumn();
        }

        $week_identifier = $_POST['week_identifier'];
        $week_start_date = $_POST['week_start_date'];
        $status = $_POST['status'] ?? 'Draft';
        
        try {
            $pdo->beginTransaction();
            
            if ($compliance_id) {
                // Determine if we need to update status (e.g. Draft -> Submitted)
                $stmt = $pdo->prepare("UPDATE iftl_weekly_compliance SET status = ?, updated_at = NOW() WHERE compliance_id = ?");
                $stmt->execute([$status, $compliance_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO iftl_weekly_compliance (faculty_id, week_identifier, week_start_date, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()");
                $stmt->execute([$faculty_id, $week_identifier, $week_start_date, $status]);
                $compliance_id = $pdo->lastInsertId();
                 if ($compliance_id == 0) {
                    $stmt = $pdo->prepare("SELECT compliance_id FROM iftl_weekly_compliance WHERE faculty_id = ? AND week_identifier = ?");
                    $stmt->execute([$faculty_id, $week_identifier]);
                    $compliance_id = $stmt->fetchColumn();
                 }
            }
            
            $stmt = $pdo->prepare("DELETE FROM iftl_entries WHERE compliance_id = ?");
            $stmt->execute([$compliance_id]);
            
            $entries = json_decode($_POST['entries'], true);
            if ($entries) {
                $insert_stmt = $pdo->prepare("INSERT INTO iftl_entries (compliance_id, day_of_week, time_start, time_end, course_code, room, activity_type, status, remarks, is_modified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($entries as $e) {
                    $insert_stmt->execute([
                        $compliance_id,
                        $e['day_of_week'],
                        $e['time_start'],
                        $e['time_end'],
                        $e['course_code'] ?? null,
                        $e['room'] ?? null,
                        $e['activity_type'] ?? 'Class',
                        $e['status'] ?? 'Regular',
                        $e['remarks'] ?? null,
                        $e['is_modified'] ?? 0
                    ]);
                }
            }
            
            $pdo->commit();
            sendJsonResponse(['success' => true, 'message' => 'IFTL saved successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            sendJsonResponse(['success' => false, 'message' => 'Error saving IFTL: ' . $e->getMessage()]);
        }
        break;

    default:
        sendJsonResponse(['success' => false, 'message' => 'Unknown action: ' . $action], 400);
        break;
}
function fetchAddedRecord($pdo, $action, $id) {
    switch ($action) {
        case 'add_faculty':
            $stmt = $pdo->prepare("
                SELECT f.faculty_id, u.full_name, f.employee_id, f.program, f.office_hours,
                       f.contact_email, f.contact_phone, f.current_location, f.last_location_update,
                       CASE 
                           WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN 'Available'
                           WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'Busy'
                           ELSE 'Offline'
                       END as status
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                WHERE f.faculty_id = ?
            ");
            break;
        case 'add_course':
            $stmt = $pdo->prepare("
                SELECT c.course_id, c.course_code, c.course_description, c.units,
                       COUNT(s.schedule_id) as times_scheduled
                FROM courses c
                LEFT JOIN schedules s ON c.course_code = s.course_code AND s.is_active = TRUE
                WHERE c.course_id = ?
                GROUP BY c.course_id
            ");
            break;
        case 'add_class':
            $stmt = $pdo->prepare("
                SELECT c.class_id, c.class_code, c.class_name, c.year_level, c.semester, c.academic_year,
                       u.full_name as program_chair_name,
                       COUNT(s.schedule_id) as total_subjects
                FROM classes c
                LEFT JOIN faculty f ON c.program_chair_id = f.user_id
                LEFT JOIN users u ON f.user_id = u.user_id
                LEFT JOIN schedules s ON c.class_id = s.class_id AND s.is_active = TRUE
                WHERE c.class_id = ? AND c.is_active = TRUE
                GROUP BY c.class_id
            ");
            break;
        case 'add_announcement':
            $stmt = $pdo->prepare("
                SELECT a.announcement_id, a.title, a.content, a.priority, a.target_audience,
                       a.created_at, u.full_name as created_by_name
                FROM announcements a
                JOIN users u ON a.created_by = u.user_id
                WHERE a.announcement_id = ?
            ");
            break;
        case 'add_program':
            $stmt = $pdo->prepare("
                SELECT p.program_id, p.program_code, p.program_name, p.program_description,
                       p.created_at, COUNT(c.course_id) as course_count
                FROM programs p
                LEFT JOIN courses c ON p.program_id = c.program_id AND c.is_active = TRUE
                WHERE p.program_id = ? AND p.is_active = TRUE
                GROUP BY p.program_id
            ");
            break;
        default:
            return ['success' => false, 'message' => 'Unknown action for fetching record'];
    }
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) {
        return ['success' => false, 'message' => 'Failed to fetch added record'];
    }
    return [
        'success' => true, 
        'data' => $record, 
        'message' => ucfirst(str_replace('add_', '', $action)) . ' added successfully',
        'action' => 'add',
        'entity_type' => str_replace('add_', '', $action),
        'entity_id' => $id
    ];
}
function detectDataChanges($pdo, $role, $tab = null) {
    $changes = [];
    try {
        if ($role === 'campus_director' || $role === 'program_chair') {
            $time_threshold = date('Y-m-d H:i:s', strtotime('-3 seconds'));
            if (!$tab || $tab === 'faculty') {
                $changes['faculty'] = checkNewOrDeletedEntities($pdo, 'faculty', 'faculty_id', $time_threshold);
            }
            if (!$tab || $tab === 'classes') {
                $changes['classes'] = checkNewOrDeletedEntities($pdo, 'classes', 'class_id', $time_threshold);
            }
            if (!$tab || $tab === 'courses') {
                $changes['courses'] = checkNewOrDeletedEntities($pdo, 'courses', 'course_id', $time_threshold);
            }
            if (!$tab || $tab === 'announcements') {
                $changes['announcements'] = checkNewOrDeletedEntities($pdo, 'announcements', 'announcement_id', $time_threshold);
            }
        }
        return array_filter($changes);
    } catch (Exception $e) {
        return [];
    }
}
function checkNewOrDeletedEntities($pdo, $table, $id_field, $time_threshold) {
    $changes = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE created_at >= ? ORDER BY created_at DESC LIMIT 2");
        $stmt->execute([$time_threshold]);
        $newEntities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($newEntities)) {
            $changes['added'] = $newEntities;
        }
        return $changes;
    } catch (Exception $e) {
        return [];
    }
}
function getAllCurrentEntities($pdo, $role, $tab = null) {
    $entities = [];
    try {
        if ($role === 'campus_director' || $role === 'program_chair') {
            if (!$tab || $tab === 'faculty') {
                $entities['faculty'] = getAllFaculty($pdo);
            }
            if (!$tab || $tab === 'classes') {
                $entities['classes'] = getAllClasses($pdo);
            }
            if (!$tab || $tab === 'courses') {
                $entities['courses'] = getAllCourses($pdo);
            }
            if (!$tab || $tab === 'announcements') {
                $entities['announcements'] = getAllAnnouncements($pdo);
            }
        }
        return $entities;
    } catch (Exception $e) {
        return [];
    }
}

function generateIFTLFromStandard($pdo, $faculty_id) {
    if (!$faculty_id) return [];
    
    $stmt = $pdo->prepare("
        SELECT s.*, c.class_name 
        FROM schedules s 
        LEFT JOIN classes c ON s.class_id = c.class_id 
        WHERE s.faculty_id = ? AND s.is_active = 1
    ");
    $stmt->execute([$faculty_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $entries = [];
    $day_map = [
        'M' => 'Monday', 'T' => 'Tuesday', 'W' => 'Wednesday', 'TH' => 'Thursday', 'F' => 'Friday', 'S' => 'Saturday', 'SAT' => 'Saturday'
    ];
    
    foreach ($schedules as $sched) {
        $days_str = strtoupper($sched['days']);
        $parsed_days = [];
        $i = 0;
        while ($i < strlen($days_str)) {
            if ($i < strlen($days_str) - 1 && substr($days_str, $i, 2) === 'TH') {
                $parsed_days[] = 'TH'; $i += 2;
            } else {
                $valid_days = ['M', 'T', 'W', 'F', 'S'];
                if (in_array($days_str[$i], $valid_days)) {
                     $parsed_days[] = $days_str[$i]; 
                }
                $i++;
            }
        }
        
        foreach ($parsed_days as $d) {
            if (isset($day_map[$d])) {
                $entries[] = [
                    'day_of_week' => $day_map[$d],
                    'time_start' => $sched['time_start'],
                    'time_end' => $sched['time_end'],
                    'course_code' => $sched['course_code'],
                    'room' => $sched['room'],
                    // Using activity_type to store Class Name/Section
                    'activity_type' => $sched['class_name'] ?? 'Class', 
                    'status' => 'Regular',
                    'remarks' => '',
                    'is_modified' => 0
                ];
            }
        }
    }
    
    usort($entries, function($a, $b) {
        $days = ['Monday'=>1, 'Tuesday'=>2, 'Wednesday'=>3, 'Thursday'=>4, 'Friday'=>5, 'Saturday'=>6, 'Sunday'=>7];
        $da = $days[$a['day_of_week']] ?? 8;
        $db = $days[$b['day_of_week']] ?? 8;
        if ($da !== $db) return $da - $db;
        return strcmp($a['time_start'], $b['time_start']);
    });
    
    return $entries;
}
?>
