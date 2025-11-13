<?php
// Turn off error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'common_utilities.php';
initializeSession();
header('Content-Type: application/json');


$pdo = initializeDatabase();
$action = $_GET['action'] ?? $_POST['action'] ?? $_POST['admin_action'] ?? '';

// Debug: Log what we received
// error_log("Polling API called with action: " . $action);
// error_log("GET params: " . print_r($_GET, true));
// error_log("POST params: " . print_r($_POST, true));

// Include required functions from other files
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
                WHEN f.is_active = 1 THEN 'Available'
                ELSE 'Offline'
            END as status
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
            COUNT(s.schedule_id) as times_scheduled
        FROM courses c
        LEFT JOIN schedules s ON c.course_code = s.course_code AND s.is_active = TRUE
        GROUP BY c.course_id
        ORDER BY c.course_code";
    
    $stmt = $pdo->prepare($courses_query);
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
    $current_time = date('H:i:s');
    $time_condition = "TIME(NOW())";
    
    $schedule_query = "
        SELECT s.*, c.course_description, cl.class_name, cl.class_code,
            CASE 
                WHEN $time_condition BETWEEN s.time_start AND s.time_end THEN 'ongoing'
                WHEN $time_condition < s.time_start THEN 'upcoming'
                ELSE 'finished'
            END as status
        FROM schedules s
        JOIN courses c ON s.course_code = c.course_code
        JOIN classes cl ON s.class_id = cl.class_id
        WHERE s.faculty_id = ? AND s.is_active = TRUE 
        AND (s.days = ? OR 
            (s.days = 'MW' AND ? IN ('M', 'W', 'MW')) OR
            (s.days = 'MF' AND ? IN ('M', 'F', 'MF')) OR
            (s.days = 'WF' AND ? IN ('W', 'F', 'WF')) OR
            (s.days = 'MWF' AND ? IN ('M', 'W', 'F', 'MWF')) OR
            (s.days = 'TTH' AND ? IN ('T', 'TH', 'TTH')) OR
            (s.days = 'MTWTHF' AND ? IN ('M', 'T', 'W', 'TH', 'F', 'MTWTHF'))
        )
        ORDER BY 
            CASE 
                WHEN $time_condition > s.time_end THEN 1
                WHEN $time_condition BETWEEN s.time_start AND s.time_end THEN 2
                WHEN $time_condition < s.time_start THEN 3
                ELSE 4
            END,
            s.time_start";
    $stmt = $pdo->prepare($schedule_query);
    $stmt->execute([$faculty_id, $days, $days, $days, $days, $days, $days, $days]);
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
        default: return ['text' => 'Unknown', 'class' => 'unknown'];
    }
}

// formatTime function exists in common_utilities.php - removed duplicate

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
    // Get class info first
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
        AND s.class_id = ?";
    
    $stmt = $pdo->prepare($faculty_query);
    $stmt->execute([$class_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFacultyCoursesForClass($pdo, $user_id) {
    // Get class info first
    $class_query = "SELECT class_id FROM classes WHERE user_id = ? AND is_active = TRUE";
    $stmt = $pdo->prepare($class_query);
    $stmt->execute([$user_id]);
    $class_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_info) return [];
    
    $class_id = $class_info['class_id'];
    $faculty_courses = [];
    
    $courses_query = "
        SELECT s.faculty_id, s.course_code, c.course_description, s.time_start, s.time_end, s.room,
            CASE 
                WHEN TIME(NOW()) BETWEEN s.time_start AND s.time_end THEN 'current'
                WHEN TIME(NOW()) < s.time_start THEN 'upcoming'
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

// Program action functions removed - they belong in program.php, not polling


// CONSOLIDATED POLLING API - ALL polling endpoints in one place
switch ($action) {
    // ADMIN ACTIONS
    case 'add_course':
    case 'add_faculty':
    case 'add_class':
    case 'add_announcement':
    case 'delete_course':
    case 'delete_faculty':
    case 'delete_class':
    case 'delete_announcement':
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'];
        
        // Handle delete operations directly (bypassing handle_admin_actions.php complexity)
        if ($action === 'delete_course') {
            $course_id = $_POST['course_id'] ?? '';
            
            if (empty($course_id)) {
                sendJsonResponse(['success' => false, 'message' => 'Course ID is required']);
                break;
            }
            
            try {
                // Get course_code first for cascade deletes
                $stmt = $pdo->prepare("SELECT course_code FROM courses WHERE course_id = ?");
                $stmt->execute([$course_id]);
                $course = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$course) {
                    sendJsonResponse(['success' => false, 'message' => 'Course not found']);
                    break;
                }
                
                $course_code = $course['course_code'];
                
                $pdo->beginTransaction();
                
                // Delete related schedules
                $stmt = $pdo->prepare("DELETE FROM schedules WHERE course_code = ?");
                $stmt->execute([$course_code]);
                
                // Delete related curriculum
                $stmt = $pdo->prepare("DELETE FROM curriculum WHERE course_code = ?");
                $stmt->execute([$course_code]);
                
                // Delete course
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
                
                // Get user_id before deleting class record (classes have associated user accounts)
                $stmt = $pdo->prepare("SELECT user_id FROM classes WHERE class_id = ?");
                $stmt->execute([$class_id]);
                $class_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$class_data) {
                    sendJsonResponse(['success' => false, 'message' => 'Class not found']);
                    break;
                }
                
                // Delete related schedules first
                $stmt = $pdo->prepare("DELETE FROM schedules WHERE class_id = ?");
                $stmt->execute([$class_id]);
                
                // HARD DELETE: Delete the class record
                $stmt = $pdo->prepare("DELETE FROM classes WHERE class_id = ?");
                $stmt->execute([$class_id]);
                
                // HARD DELETE: Delete user account completely (like faculty)
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
                
                // Get user_id before deleting faculty record
                $stmt = $pdo->prepare("SELECT user_id FROM faculty WHERE faculty_id = ?");
                $stmt->execute([$faculty_id]);
                $faculty_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$faculty_data) {
                    sendJsonResponse(['success' => false, 'message' => 'Faculty not found']);
                    break;
                }
                
                // Delete related schedules
                $stmt = $pdo->prepare("DELETE FROM schedules WHERE faculty_id = ?");
                $stmt->execute([$faculty_id]);
                
                // Delete location history
                $stmt = $pdo->prepare("DELETE FROM location_history WHERE faculty_id = ?");
                $stmt->execute([$faculty_id]);
                
                // HARD DELETE: Delete faculty record
                $stmt = $pdo->prepare("DELETE FROM faculty WHERE faculty_id = ?");
                $stmt->execute([$faculty_id]);
                
                // HARD DELETE: Delete user account completely
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
                // Simple delete for announcements (no cascading needed)
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
        } else {
            // Handle add operations and other deletes via handle_admin_actions.php
            require_once 'handle_admin_actions.php';
            
            try {
                if (strpos($action, 'add_') === 0) {
                    $result = handleAdd($pdo, $_POST, $user_id, $user_role);
                } else {
                    $result = handleDelete($pdo, $_POST, $user_id, $user_role);
                }
                sendJsonResponse($result);
            } catch (Exception $e) {
                sendJsonResponse(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
        
    // TEST ENDPOINT
    case 'test':
        sendJsonResponse(['success' => true, 'message' => 'Polling API is working', 'timestamp' => date('Y-m-d H:i:s')]);
        break;
        
    // STATISTICS ENDPOINTS - DEPRECATED: Use get_dashboard_data for consistency
    case 'get_statistics':
        // Redirect to dashboard data for consistency
        $response_data = [
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($_SESSION['role'] === 'campus_director' || $_SESSION['role'] === 'program_chair') {
            $response_data['faculty_data'] = getAllFaculty($pdo);
            $response_data['classes_data'] = getAllClasses($pdo);
            $response_data['courses_data'] = getAllCourses($pdo);
            $response_data['announcements_data'] = getAllAnnouncements($pdo);
            
            // Calculate statistics from the same data
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
        
    // LOCATION ENDPOINTS  
    case 'get_location_updates':
        include 'get_location.php';
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
        
    // DASHBOARD DATA ENDPOINTS
    case 'get_dashboard_data':
        $tab = $_GET['tab'] ?? 'faculty';
        $role = $_SESSION['role'];
        
        if (!in_array($role, ['campus_director', 'program_chair', 'class'])) {
            sendJsonResponse(['success' => false, 'message' => 'Unauthorized access'], 403);
        }
        
        try {
            $response_data = [
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            if ($role === 'campus_director') {
                // Director: Return specific tab data
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
                        $response_data['announcements_data'] = getAllAnnouncements($pdo);
                        break;
                    case 'all':
                        $response_data['faculty_data'] = getAllFaculty($pdo);
                        $response_data['classes_data'] = getAllClasses($pdo);
                        $response_data['courses_data'] = getAllCourses($pdo);
                        $response_data['announcements_data'] = getAllAnnouncements($pdo);
                        $response_data['program_chairs'] = getProgramChairs($pdo);
                        break;
                }
            } else if ($role === 'program_chair') {
                // Program Chair: Return all data
                $user_id = $_SESSION['user_id'];
                
                $classes_query = "SELECT * FROM classes WHERE program_chair_id = ? AND is_active = TRUE ORDER BY year_level, class_name";
                $stmt = $pdo->prepare($classes_query);
                $stmt->execute([$user_id]);
                $response_data['classes_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response_data['faculty_data'] = getAllFaculty($pdo);
                $response_data['courses_data'] = getAllCourses($pdo);
                
            } else if ($role === 'class') {
                // Class: Return faculty data with courses
                $response_data['faculty_data'] = getClassFaculty($pdo, $_SESSION['user_id']);
                $response_data['faculty_courses'] = getFacultyCoursesForClass($pdo, $_SESSION['user_id']);
            }
            
            // Add change tracking for NEW/DELETED entities only
            $response_data['changes'] = detectDataChanges($pdo, $role, $tab);
            
            // Add current entity data for status updates
            $response_data['current_entities'] = getAllCurrentEntities($pdo, $role, $tab);
            
            sendJsonResponse($response_data);
            
        } catch (Exception $e) {
            error_log("Dashboard data error: " . $e->getMessage());
            sendJsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    // SCHEDULE ENDPOINTS
    case 'get_schedule_updates':
        validateUserSession('faculty');
        try {
            $user_id = $_SESSION['user_id'];
            $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = ? AND is_active = TRUE";
            $stmt = $pdo->prepare($faculty_query);
            $stmt->execute([$user_id]);
            $faculty_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($faculty_info) {
                $schedule_query = "
                    SELECT s.*, c.course_description, cl.class_name,
                        CASE 
                            WHEN TIME(NOW()) BETWEEN s.time_start AND s.time_end THEN 'ongoing'
                            WHEN TIME(NOW()) < s.time_start THEN 'upcoming'
                            ELSE 'finished'
                        END as status
                    FROM schedules s
                    JOIN courses c ON s.course_code = c.course_code
                    JOIN classes cl ON s.class_id = cl.class_id
                    WHERE s.faculty_id = ? AND s.is_active = TRUE
                    ORDER BY s.time_start";
                    
                $stmt = $pdo->prepare($schedule_query);
                $stmt->execute([$faculty_info['faculty_id']]);
                $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendJsonResponse([
                    'success' => true,
                    'schedules' => $schedules,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Faculty not found']);
            }
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_schedule':
        validateUserSession('faculty');
        $days = $_POST['days'] ?? '';
        $user_id = $_SESSION['user_id'];
        
        try {
            $faculty_info = getFacultyInfo($pdo, $user_id);
            $faculty_id = $faculty_info['faculty_id'];
            $schedule_data = getScheduleForDays($pdo, $faculty_id, $days);
            
            if (empty($schedule_data)) {
                sendJsonResponse(['success' => false, 'message' => 'No schedule found']);
            }
            
            $html = generateScheduleHTML($schedule_data);
            sendJsonResponse(['success' => true, 'html' => $html]);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;
        
    // PROGRAM CHAIR ENDPOINTS - removed, these are actions not polling
        
    // ATTENDANCE/LOCATION UPDATES
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
        
    default:
        error_log("Unknown action received: " . $action);
        sendJsonResponse(['success' => false, 'message' => 'Unknown action: ' . $action], 400);
        break;
}

// Dynamic change detection for indexed updates
function detectDataChanges($pdo, $role, $tab = null) {
    $changes = [];
    
    try {
        if ($role === 'campus_director' || $role === 'program_chair') {
            // Only detect ACTUAL new entities (created in last 3 seconds) or hard deletions
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
        error_log("Change detection error: " . $e->getMessage());
        return [];
    }
}

function checkNewOrDeletedEntities($pdo, $table, $id_field, $time_threshold) {
    $changes = [];
    
    try {
        // ONLY check for genuinely NEW entities (created recently)
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE created_at >= ? ORDER BY created_at DESC LIMIT 2");
        $stmt->execute([$time_threshold]);
        $newEntities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($newEntities)) {
            $changes['added'] = $newEntities;
            error_log("New {$table} entities added: " . count($newEntities));
        }
        
        // For hard deletions, we can't detect them directly since records are gone
        // But we can detect count mismatches in the frontend polling comparison
        
        return $changes;
        
    } catch (Exception $e) {
        error_log("Entity check error for {$table}: " . $e->getMessage());
        return [];
    }
}

// Return all current entity data for status updates
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
        error_log("Get entities error: " . $e->getMessage());
        return [];
    }
}

?>