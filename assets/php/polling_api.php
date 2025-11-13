<?php
require_once 'common_utilities.php';
initializeSession();
header('Content-Type: application/json');

$pdo = initializeDatabase();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// CONSOLIDATED POLLING API - ALL polling endpoints in one place
switch ($action) {
    // STATISTICS ENDPOINTS
    case 'get_statistics':
        include 'get_statistics.php';
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
                include_once '../../director_functions.php';
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
                include_once '../../director_functions.php';
                
                $classes_query = "SELECT * FROM classes WHERE program_chair_id = ? AND is_active = TRUE ORDER BY year_level, class_name";
                $stmt = $pdo->prepare($classes_query);
                $stmt->execute([$user_id]);
                $response_data['classes_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response_data['faculty_data'] = getAllFaculty($pdo);
                $response_data['courses_data'] = getAllCourses($pdo);
                
            } else if ($role === 'class') {
                // Class: Return faculty data with courses
                include_once '../../home_functions.php';
                $response_data['faculty_data'] = getAllFacultyForClass($pdo, $_SESSION['user_id']);
                $response_data['faculty_courses'] = getFacultyCoursesForClass($pdo, $_SESSION['user_id']);
            }
            
            sendJsonResponse($response_data);
            
        } catch (Exception $e) {
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
            include_once '../../faculty_functions.php';
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
        
    // PROGRAM CHAIR ENDPOINTS
    case 'assign_course_load':
    case 'get_curriculum_assignment_data':
    case 'remove_curriculum_assignment':
    case 'get_faculty_schedules':
    case 'validate_schedule':
    case 'get_course_curriculum':
    case 'get_validated_options':
        validateUserSession('program_chair');
        include_once '../../program_functions.php';
        handleProgramAction($pdo, $action, $_POST);
        break;
        
    // ATTENDANCE/LOCATION UPDATES
    case 'mark_attendance':
        validateUserSession('faculty');
        $user_id = $_SESSION['user_id'];
        $schedule_id = validateInput($_POST['schedule_id'] ?? '');
        
        if (empty($schedule_id)) {
            sendJsonResponse(['success' => false, 'message' => 'Schedule ID is required']);
        }
        
        try {
            include_once '../../faculty_functions.php';
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
            include_once '../../faculty_functions.php';
            $result = updateFacultyLocation($pdo, $user_id, $location);
            sendJsonResponse($result);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;
        
    default:
        sendJsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
        break;
}
?>