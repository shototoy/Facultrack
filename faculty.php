<?php
require_once 'assets/php/common_utilities.php';
initializeSession();
$pdo = initializeDatabase();

$DEBUG_MODE = true;
$DEBUG_TIME = '10:30:00';
$DEBUG_DATE = '2025-01-29';

$current_time = $DEBUG_MODE ? $DEBUG_TIME : date('H:i:s');
$current_date = $DEBUG_MODE ? $DEBUG_DATE : date('Y-m-d');
$current_day = $DEBUG_MODE ? date('w', strtotime($DEBUG_DATE)) : date('w');

function getFacultyInfo($pdo, $user_id) {
    $faculty_query = "SELECT f.*, u.full_name FROM faculty f JOIN users u ON f.user_id = u.user_id WHERE f.user_id = ? AND f.is_active = TRUE";
    $stmt = $pdo->prepare($faculty_query);
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getTodaySchedule($pdo, $faculty_id) {
    global $DEBUG_MODE, $current_day, $current_time;
    
    $day_mapping = [0 => 'S', 1 => 'M', 2 => 'T', 3 => 'W', 4 => 'TH', 5 => 'F', 6 => 'SAT'];
    $today_code = $day_mapping[$current_day];
    
    $time_condition = $DEBUG_MODE ? "TIME('$current_time')" : "TIME(NOW())";
    
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
            (s.days = 'MWF' AND ? IN ('M', 'W', 'F')) OR
            (s.days = 'TTH' AND ? IN ('T', 'TH')) OR
            (s.days = 'MW' AND ? IN ('M', 'W')) OR
            (s.days = 'MF' AND ? IN ('M', 'F')) OR
            (s.days = 'WF' AND ? IN ('W', 'F')) OR
            (s.days = 'MTWTHF' AND ? NOT IN ('S', 'SAT'))
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
    $stmt->execute([$faculty_id, $today_code, $today_code, $today_code, $today_code, $today_code, $today_code, $today_code]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getScheduleForDays($pdo, $faculty_id, $days) {
    global $DEBUG_MODE, $current_time;
    
    $time_condition = $DEBUG_MODE ? "TIME('$current_time')" : "TIME(NOW())";
    
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

function getLocationHistory($pdo, $faculty_id, $limit = 10) {
    $limit = (int)$limit;
    $query = "SELECT location, time_set, time_changed 
              FROM location_history 
              WHERE faculty_id = ? 
              ORDER BY time_set DESC 
              LIMIT " . $limit;
    $stmt = $pdo->prepare($query);
    $stmt->execute([$faculty_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getScheduleTabs($pdo, $faculty_id) {
    $days_query = "SELECT DISTINCT s.days FROM schedules s WHERE s.faculty_id = ? AND s.is_active = TRUE ORDER BY s.days";
    $stmt = $pdo->prepare($days_query);
    $stmt->execute([$faculty_id]);
    $schedule_days = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return array_unique($schedule_days);
}

function getScheduleStatus($status) {
    switch ($status) {
        case 'ongoing': return ['text' => 'In Progress', 'class' => 'ongoing'];
        case 'upcoming': return ['text' => 'Upcoming', 'class' => 'upcoming'];
        case 'finished': return ['text' => 'Completed', 'class' => 'finished'];
        default: return ['text' => 'Unknown', 'class' => 'unknown'];
    }
}

function generateScheduleHTML($schedule_data) {
    $html = '';
    foreach ($schedule_data as $schedule) {
        $status_info = getScheduleStatus($schedule['status']);
        $start = strtotime($schedule['time_start']);
        $end = strtotime($schedule['time_end']);
        $duration = ($end - $start) / 3600;
        
        $html .= '<div class="schedule-item ' . $status_info['class'] . '">';
        $html .= '<div class="schedule-time">';
        $html .= '<div class="time-display">' . formatTime($schedule['time_start']) . '</div>';
        $html .= '<div class="time-duration">' . $duration . 'hr</div>';
        $html .= '</div>';
        $html .= '<div class="schedule-details">';
        $html .= '<div class="schedule-course">';
        $html .= '<div class="course-code">' . htmlspecialchars($schedule['course_code']) . '</div>';
        $html .= '<div class="course-name">' . htmlspecialchars($schedule['course_description']) . '</div>';
        $html .= '</div>';
        $html .= '<div class="schedule-info">';
        $html .= '<span class="class-info">' . htmlspecialchars($schedule['class_name']) . '</span>';
        $html .= '<span class="room-info">Room: ' . htmlspecialchars($schedule['room'] ?: 'TBA') . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="schedule-status">';
        $html .= '<span class="status-badge status-' . $status_info['class'] . '">' . $status_info['text'] . '</span>';
        if ($schedule['status'] === 'ongoing') {
            $html .= '<button class="btn-small btn-primary" onclick="markAttendance(' . $schedule['schedule_id'] . ')">Mark Present</button>';
        }
        $html .= '</div>';
        $html .= '</div>';
    }
    return $html;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_attendance') {
    validateUserSession('faculty');
    $user_id = $_SESSION['user_id'];
    $schedule_id = validateInput($_POST['schedule_id'] ?? '');
    
    if (empty($schedule_id)) {
        sendJsonResponse(['success' => false, 'message' => 'Schedule ID is required']);
    }
    
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
            sendJsonResponse(['success' => false, 'message' => 'Schedule not found']);
        }
        
        $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = ? AND is_active = TRUE";
        $stmt = $pdo->prepare($faculty_query);
        $stmt->execute([$user_id]);
        $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$faculty) {
            $pdo->rollBack();
            sendJsonResponse(['success' => false, 'message' => 'Faculty not found']);
        }
        
        $faculty_id = $faculty['faculty_id'];
        $location = !empty($schedule_info['room']) ? $schedule_info['room'] : 'In Class';
        
        $update_prev_query = "UPDATE location_history 
                             SET time_changed = NOW() 
                             WHERE faculty_id = ? AND time_changed IS NULL";
        $stmt = $pdo->prepare($update_prev_query);
        $stmt->execute([$faculty_id]);
        
        $insert_history_query = "INSERT INTO location_history (faculty_id, location, time_set) 
                                VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($insert_history_query);
        $stmt->execute([$faculty_id, $location]);
        
        $update_location_query = "UPDATE faculty 
                                  SET current_location = ?, 
                                      last_location_update = NOW() 
                                  WHERE faculty_id = ? AND is_active = TRUE";
        $stmt = $pdo->prepare($update_location_query);
        $stmt->execute([$location, $faculty_id]);
        
        $pdo->commit();
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Attendance marked and location updated',
            'location' => $location,
            'course' => $schedule_info['course_code']
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        sendJsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_location') {
    validateUserSession('faculty');
    $user_id = $_SESSION['user_id'];
    $location = validateInput($_POST['location'] ?? '');
    
    if (empty($location)) {
        sendJsonResponse(['success' => false, 'message' => 'Location cannot be empty']);
    }
    
    try {
        $pdo->beginTransaction();
        
        $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = ? AND is_active = TRUE";
        $stmt = $pdo->prepare($faculty_query);
        $stmt->execute([$user_id]);
        $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$faculty) {
            $pdo->rollBack();
            sendJsonResponse(['success' => false, 'message' => 'Faculty not found']);
        }
        
        $faculty_id = $faculty['faculty_id'];
        
        $update_prev_query = "UPDATE location_history 
                             SET time_changed = NOW() 
                             WHERE faculty_id = ? AND time_changed IS NULL";
        $stmt = $pdo->prepare($update_prev_query);
        $stmt->execute([$faculty_id]);
        
        $insert_history_query = "INSERT INTO location_history (faculty_id, location, time_set) 
                                VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($insert_history_query);
        $stmt->execute([$faculty_id, $location]);
        
        $update_query = "UPDATE faculty 
                        SET current_location = ?, 
                            last_location_update = NOW() 
                        WHERE user_id = ? AND is_active = TRUE";
        $stmt = $pdo->prepare($update_query);
        $stmt->execute([$location, $user_id]);
        
        $pdo->commit();
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Location updated successfully',
            'location' => $location,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        sendJsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_status') {
    validateUserSession('faculty');
    $user_id = $_SESSION['user_id'];
    
    try {
        $status_query = "SELECT last_location_update FROM faculty WHERE user_id = ? AND is_active = TRUE";
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
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_schedule') {
    validateUserSession('faculty');
    $days = $_POST['days'] ?? '';
    $faculty_info = getFacultyInfo($pdo, $_SESSION['user_id']);
    $faculty_id = $faculty_info['faculty_id'];
    $schedule_data = getScheduleForDays($pdo, $faculty_id, $days);
    
    if (empty($schedule_data)) {
        sendJsonResponse(['success' => false, 'message' => 'No schedule found']);
    }
    
    $html = generateScheduleHTML($schedule_data);
    sendJsonResponse(['success' => true, 'html' => $html]);
}

validateUserSession('faculty');

$user_id = $_SESSION['user_id'];
$faculty_name = $_SESSION['full_name'];
$faculty_info = getFacultyInfo($pdo, $user_id);

if (!$faculty_info) {
    die("Faculty information not found");
}

$today_schedule = getTodaySchedule($pdo, $faculty_info['faculty_id']);
$location_history = getLocationHistory($pdo, $faculty_info['faculty_id'], 10);
$schedule_tabs = getScheduleTabs($pdo, $faculty_info['faculty_id']);

require_once 'assets/php/fetch_announcements.php';
$announcements = fetchAnnouncements($pdo, $_SESSION['role'], 10);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaculTrack - Faculty Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>            
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            grid-template-rows: auto auto;
            gap: 2px;
            flex: 1;
            background: linear-gradient(135deg, #e0e0e0 0%, #d6d6d6 100%);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .schedule-section {
            grid-row: 1 / 3;
            grid-column: 1;
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            padding: 2rem;
            overflow-y: auto;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9),
                        0 4px 15px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
            position: relative;
        }

        .schedule-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.3), transparent);
            animation: shimmer 4s infinite;
        }

        @keyframes shimmer {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }

        .location-section {
            grid-row: 1;
            grid-column: 2;
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            padding: 25px;
            border-left: 1px solid rgba(224, 224, 224, 0.5);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9),
                        0 4px 15px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
        }

        .actions-section {
            grid-row: 2;
            grid-column: 2;
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            padding: 25px;
            border-left: 1px solid rgba(224, 224, 224, 0.5);
            border-top: 1px solid rgba(224, 224, 224, 0.5);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9),
                        0 4px 15px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
        }

        .schedule-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(240, 240, 240, 0.8);
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.05), transparent);
            border-radius: 8px;
            padding: 15px;
            margin: -10px -10px 20px -10px;
        }

        .schedule-header h3 {
            color: #1B5E20;
            margin: 0;
            font-size: 1.4rem;
            text-shadow: 0 2px 4px rgba(255, 255, 255, 0.8);
            font-weight: 700;
        }

        .schedule-date {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }

        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .schedule-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: linear-gradient(145deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            border-left: 4px solid #e0e0e0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            position: relative;
            overflow: hidden;
            gap: 10px;
        }

        .schedule-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .schedule-item:hover::before {
            left: 100%;
        }

        .schedule-item:hover {
            transform: translateY(-3px) translateX(2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12),
                        inset 0 2px 0 rgba(255, 255, 255, 1);
        }

        .schedule-item.ongoing {
            background: linear-gradient(135deg, rgba(232, 245, 232, 0.9), rgba(200, 230, 201, 0.9));
            border-left-color: #4CAF50;
            box-shadow: 0 4px 20px rgba(76, 175, 80, 0.25),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(76, 175, 80, 0.2);
        }

        .schedule-item.upcoming {
            background: linear-gradient(135deg, rgba(255, 243, 224, 0.9), rgba(255, 224, 178, 0.9));
            border-left-color: #FF9800;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.2),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 152, 0, 0.2);
        }

        .schedule-item.finished {
            background: linear-gradient(135deg, rgba(245, 245, 245, 0.9), rgba(238, 238, 238, 0.9));
            border-left-color: #9E9E9E;
            opacity: 0.8;
            box-shadow: 0 2px 8px rgba(158, 158, 158, 0.2),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }

        .location-update-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .location-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.05), transparent);
            border-radius: 8px;
            margin: -10px -10px 15px -10px;
        }

        .location-header h3 {
            color: #1B5E20;
            margin: 0;
            font-size: 1.2rem;
            text-shadow: 0 2px 4px rgba(255, 255, 255, 0.8);
            font-weight: 700;
        }

        .location-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
            color: #4CAF50;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
            padding: 4px 8px;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2);
        }

        .location-display {
            background: linear-gradient(135deg, rgba(248, 249, 250, 0.9), rgba(233, 236, 239, 0.9));
            padding: 12px;
            border-radius: 10px;
            border-left: 3px solid #FFC107;
            margin-bottom: 15px;
            flex: 1;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.15),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }

        .location-updated {
            font-size: 0.8rem;
            color: #666;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }

        .location-actions {
            display: flex;
            gap: 10px;
        }

        .location-actions button {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.3),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .location-actions button:hover {
            background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 100%);
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.4),
                        inset 0 2px 0 rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .quick-actions {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .quick-actions h3 {
            color: #1B5E20;
            margin: 0 0 15px 0;
            font-size: 1.2rem;
            text-shadow: 0 2px 4px rgba(255, 255, 255, 0.8);
            font-weight: 700;
            padding: 15px;
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.05), transparent);
            border-radius: 8px;
            margin: -10px -10px 15px -10px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            flex: 1;
        }

        .action-card {
            border: 2px solid rgba(46, 125, 50, 0.1);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.1), transparent);
            transition: left 0.6s;
        }

        .action-card:hover::before {
            left: 100%;
        }

        .action-card:hover {
            background: linear-gradient(135deg, rgba(233, 236, 239, 0.9), rgba(248, 249, 250, 0.9));
            border-color: #2E7D32;
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.15),
                        inset 0 2px 0 rgba(255, 255, 255, 1);
        }

        .action-icon {
            font-size: 2rem;
            margin-bottom: 4px;
            color: #2E7D32;
            filter: drop-shadow(0 2px 4px rgba(46, 125, 50, 0.3));
            transition: all 0.3s ease;
        }

        .action-card:hover .action-icon {
            transform: scale(1.1);
            filter: drop-shadow(0 4px 8px rgba(46, 125, 50, 0.4));
        }

        .action-title {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 3px;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }

        .action-subtitle {
            font-size: 0.75rem;
            color: #666;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }

        .schedule-time {
            min-width: 70px;
            text-align: center;
            margin-right: 15px;
        }

        .time-display {
            font-size: 1rem;
            font-weight: bold;
            color: #333;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }

        .time-duration {
            font-size: 0.75rem;
            color: #666;
            margin-top: 2px;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }

        .schedule-details {
            flex: 1;
            margin-right: 15px;
        }

        .schedule-info {
            gap: 12px;
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }

            .schedule-info span {
            display: block;
        }

        .schedule-status {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }

        .no-schedule {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .no-schedule-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.5;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .no-schedule-text {
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: #333;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }

        .no-schedule-subtitle {
            font-size: 0.85rem;
            color: #666;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }

        .schedule-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .schedule-tab {
            padding: 8px 16px;
            border: 1px solid rgba(221, 221, 221, 0.5);
            background: linear-gradient(145deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }

        .schedule-tab.active {
            background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);
            color: white;
            border-color: #2E7D32;
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.3),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }

        .schedule-tab:hover:not(.active) {
            background: linear-gradient(145deg, #e9ecef, #dee2e6);
            border-color: #aaa;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08),
                        inset 0 1px 0 rgba(255, 255, 255, 1);
            transform: translateY(-2px);
        }

        /* IMPROVED RESPONSIVE DESIGN */
        @media (max-width: 1023px) {
            .dashboard-grid {
                grid-template-columns: 1fr !important;
                grid-template-rows: auto auto !important;
                gap: 2px;
            }
            
            .schedule-section {
                grid-row: 1 !important;
                grid-column: 1 !important;
                padding: 20px;
                border-left: none;
            }
            
            .location-section {
                grid-row: 2 !important;
                grid-column: 1 !important;
                padding: 20px;
                border-left: none;
                border-top: 1px solid rgba(224, 224, 224, 0.5);
            }
            
            .actions-section {
                grid-row: unset !important;
                grid-column: unset !important;
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 1000;
                background: rgba(255, 255, 255, 0.98);
                padding: 15px;
                border-radius: 0;
                border: none;
                border-top: 1px solid rgba(46, 125, 50, 0.2);
                box-shadow: 0 -5px 30px rgba(0, 0, 0, 0.15),
                            inset 0 1px 0 rgba(255, 255, 255, 0.9);
                margin: 0;
            }
            
            .quick-actions h3 {
                display: none;
            }
            
            .actions-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            
            .action-card {
                background: linear-gradient(145deg, #ffffff, #f8f9fa);
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08),
                            inset 0 1px 0 rgba(255, 255, 255, 0.9);
                border: 1px solid rgba(46, 125, 50, 0.15);
                padding: 12px;
            }

            .action-card:hover {
                background: linear-gradient(145deg, #f8f9fa, #e9ecef);
                transform: translateY(-2px) scale(1.02);
                box-shadow: 0 6px 20px rgba(46, 125, 50, 0.15),
                            inset 0 2px 0 rgba(255, 255, 255, 1);
            }
            
            .schedule-item {
                padding: 6px 10px;
                min-height: auto;
                display: grid;
                grid-template-columns: 55px 1fr auto;
                grid-template-rows: auto;
                gap: 0 8px;
                align-items: center;
            }
            
            .schedule-time {
                grid-column: 1;
                margin: 0;
                min-width: auto;
                align-self: center;
            }
            
            .schedule-details {
                grid-column: 2;
                margin: 0;
                line-height: 1.1;
            }
            
                .schedule-info span {
                display: inline-block;
            }
            .schedule-details h4 {
                margin: 0;
                font-size: 0.85rem;
                line-height: 1.1;
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                align-items: baseline;
            }
            
            .course-code {
                font-weight: bold;
            }
            
            .course-name {
                font-weight: 500;
                opacity: 0.9;
            }
            
            .schedule-info {

                gap: 6px;
                margin: 1px 0 0 0;
                font-size: 0.7rem;
                color: #666;
            }
            
            .schedule-status {
                grid-column: 3;
                align-items: center;
                justify-content: center;
                margin: 0;
                gap: 4px;
            }
            
            .schedule-tabs {
                display: flex;
                gap: 4px;
                margin-bottom: 15px;
                flex-wrap: wrap;
                overflow-x: auto;
                padding-bottom: 2px;
            }
            
            .schedule-tab {
                padding: 6px 12px;
                font-size: 0.8rem;
                white-space: nowrap;
                flex-shrink: 0;
            }
            
            body {
                padding-bottom: 100px !important;
            }
        }

        /* TABLET-SPECIFIC ADJUSTMENTS (481px - 1023px) */
        @media (min-width: 481px) and (max-width: 1023px) {
            .schedule-section,
            .location-section {
                padding: 25px;
            }
            
            .location-header{
                margin: 0px;
                padding: 0px;
            }

            .location-display{
                margin: 4px
            }
            .location-section {
                padding: 12px 20px

            }
            

            .actions-section {
                padding: 0px !important;
            }
            
            .actions-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0px;
            }

            .action-card {
                padding: 8;
            }

            .action-icon {
                font-size: 1.8rem;
            }

            .action-title {
                font-size: 1rem;
            }

            .action-subtitle {
                font-size: 0.75rem;
            }

            .schedule-item {
                min-height: auto;
                display: grid;
                grid-template-columns: 60px 1fr auto;
                grid-template-rows: auto;
                align-items: center;
            }
            
            .schedule-details h4 {
                font-size: 0.95rem;
                line-height: 1.1;
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                align-items: baseline;
            }

            .schedule-info {
                font-size: 0.8rem;
            }

            body {
                padding-bottom: 120px !important;
            }
        }

        /* MOBILE-SPECIFIC ADJUSTMENTS (<= 480px) */
        @media (max-width: 480px) {  
            .schedule-section,
            .location-section {
                padding: 12px;
            }
            
            .actions-section {
                padding: 10px !important;
            }
            
            .actions-grid {
                gap: 8px;
            }

            .action-card {
                padding: 8px;
            }

            .action-icon {
                font-size: 1.4rem;
                margin-bottom: 2px;
            }

            .action-title {
                font-size: 0.8rem;
                margin-bottom: 1px;
            }

            .action-subtitle {
                font-size: 0.65rem;
            }

            .schedule-item {
                padding: 6px 10px;
                grid-template-columns: 50px 1fr auto;
                gap: 0 6px;
            }

            .schedule-details h4 {
                font-size: 0.85rem;
                line-height: 1.1;
            }

            .schedule-info {
                font-size: 0.7rem;
                gap: 6px;
                margin-top: 1px;
            }

            .time-display {
                font-size: 0.9rem;
            }

            .time-duration {
                font-size: 0.65rem;
            }

            .schedule-tab {
                padding: 4px 8px;
                font-size: 0.75rem;
            }

            .schedule-header h3 {
                font-size: 1.2rem;
            }

            .schedule-date {
                font-size: 0.8rem;
            }

            .location-actions {
                flex-direction: column;
                gap: 6px;
            }

            .location-actions button {
                padding: 6px 10px;
                font-size: 0.75rem;
            }

            .location-header h3 {
                font-size: 1rem;
            }

            .location-status {
                font-size: 0.75rem;
                padding: 2px 6px;
            }

            .location-display {
                padding: 8px;
                font-size: 0.85rem;
            }

            .location-updated {
                font-size: 0.7rem;
            }

            body {
                padding-bottom: 80px !important;
            }
        }
</style>
</head>
<body>
    <div class="main-container">
        <button class="announcement-toggle" onclick="toggleSidebar()">
            📢
            <span class="announcement-badge"><?php echo count($announcements); ?></span>
        </button>

        <div class="sidebar-overlay" onclick="closeSidebar()"></div>

        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="close-btn" onclick="closeSidebar()">×</button>
                <div class="sidebar-title">Announcements</div>
                <div class="sidebar-subtitle">Latest Updates</div>
            </div>
            <div class="announcements-container" id="announcementsContainer">
                <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-card">
                    <div class="announcement-header">
                        <div>
                            <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                            <div class="announcement-category"><?php echo getAnnouncementCategory($announcement['target_audience']); ?></div>
                        </div>
                    </div>
                    <div class="announcement-content"><?php echo htmlspecialchars($announcement['content']); ?></div>
                    <div class="announcement-meta">
                        <span class="announcement-time"><?php echo $announcement['time_ago']; ?></span>
                        <span class="announcement-priority priority-<?php echo $announcement['priority']; ?>">
                            <?php echo strtoupper($announcement['priority']); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="content-wrapper" id="contentWrapper">
            <div class="header">
                <h1>FaculTrack - Faculty Portal</h1>
                <p>Sultan Kudarat State University - Isulan Campus</p>
                <small>Employee ID: <strong><?php echo htmlspecialchars($faculty_info['employee_id']); ?></strong></small>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($faculty_name); ?></span>
                    <span style="font-size: 0.8rem; color: #666;">(Faculty)</span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="schedule-section">
                    <div class="schedule-card">
                    <div class="schedule-header">
                        <h3>Schedule</h3>
                        <div class="schedule-date">
                            <?php 
                            $display_date = $DEBUG_MODE ? date('F j, Y - l', strtotime($DEBUG_DATE)) : date('F j, Y - l');
                            echo $display_date;
                            ?>
                        </div>
                    </div>
                        
                        <div class="schedule-tabs">
                            <?php if (!empty($schedule_tabs)): ?>
                                <?php foreach ($schedule_tabs as $index => $tab): ?>
                                <button class="schedule-tab <?php echo $index === 0 ? 'active' : ''; ?>" 
                                        onclick="switchScheduleTab('<?php echo $tab; ?>', this)">
                                    <?php echo $tab; ?>
                                </button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="schedule-list" id="scheduleList">
                            <?php if (!empty($schedule_tabs)): ?>
                                <?php $first_tab_schedule = getScheduleForDays($pdo, $faculty_info['faculty_id'], $schedule_tabs[0]); ?>
                                <?php if (empty($first_tab_schedule)): ?>
                                <div class="no-schedule">
                                    <div class="no-schedule-icon">📅</div>
                                    <div class="no-schedule-text">No classes scheduled</div>
                                    <div class="no-schedule-subtitle">for <?php echo $schedule_tabs[0]; ?></div>
                                </div>
                                <?php else: ?>
                                    <?php foreach ($first_tab_schedule as $schedule): ?>
                                    <?php $status_info = getScheduleStatus($schedule['status']); ?>
                                    <div class="schedule-item <?php echo $status_info['class']; ?>">
                                        <div class="schedule-time">
                                            <div class="time-display"><?php echo formatTime($schedule['time_start']); ?></div>
                                            <div class="time-duration">
                                                <?php 
                                                $start = strtotime($schedule['time_start']);
                                                $end = strtotime($schedule['time_end']);
                                                $duration = ($end - $start) / 3600;
                                                echo $duration . 'hr';
                                                ?>
                                            </div>
                                        </div>
                                        
                                        <div class="schedule-details">
                                            <div class="schedule-course">
                                                <div class="course-code"><?php echo htmlspecialchars($schedule['course_code']); ?></div>
                                                <div class="course-name"><?php echo htmlspecialchars($schedule['course_description']); ?></div>
                                            </div>
                                            <div class="schedule-info">
                                                <span class="class-info"><?php echo htmlspecialchars($schedule['class_name']); ?></span>
                                                <span class="room-info">Room: <?php echo htmlspecialchars($schedule['room'] ?: 'TBA'); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="schedule-status">
                                            <span class="status-badge status-<?php echo $status_info['class']; ?>">
                                                <?php echo $status_info['text']; ?>
                                            </span>
                                            <?php if ($schedule['status'] === 'ongoing'): ?>
                                            <button class="btn-small btn-primary" onclick="markAttendance(<?php echo $schedule['schedule_id']; ?>)">
                                                Mark Present
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php else: ?>
                            <div class="no-schedule">
                                <div class="no-schedule-icon">📅</div>
                                <div class="no-schedule-text">No schedules found</div>
                                <div class="no-schedule-subtitle">No classes assigned</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="location-section">
                    <div class="location-update-card">
                        <div class="location-current">
                            <div class="location-header">
                                <h3>Current Location</h3>
                                <div class="location-status">
                                    <span class="status-dot"></span>
                                    <span>Available</span>
                                </div>
                            </div>
                            <div class="location-display">
                                <div class="location-text" id="currentLocation">
                                    <?php echo htmlspecialchars($faculty_info['current_location'] ?: 'Location not set'); ?>
                                </div>
                                <div class="location-updated">
                                    Last updated: <?php 
                                        if ($faculty_info['last_location_update']) {
                                            $time_diff = time() - strtotime($faculty_info['last_location_update']);
                                            if ($time_diff < 3600) {
                                                echo floor($time_diff / 60) . ' minutes ago';
                                            } elseif ($time_diff < 86400) {
                                                echo floor($time_diff / 3600) . ' hours ago';
                                            } else {
                                                echo floor($time_diff / 86400) . ' days ago';
                                            }
                                        } else {
                                            echo 'Never';
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="actions-section">
                    <div class="quick-actions">
                        <h3>Quick Actions</h3>
                        <div class="actions-grid">
                            <button class="action-card btn-primary" onclick="openLocationModal()">
                                <div class="action-icon">📍</div>
                                <div class="action-title">Update Location</div>
                            </button>
                            
                            <button class="action-card" onclick="viewLocationHistory()">
                                <div class="action-icon">📋</div>
                                <div class="action-title">Location History</div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-overlay" id="locationModal">
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Update Location</h3>
                    <button type="button" class="modal-close" onclick="closeLocationModal()">&times;</button>
                </div>
                
                <form id="locationForm" class="modal-form" onsubmit="event.preventDefault(); updateLocation();">
                    <div class="form-group">
                        <label class="form-label">Current Location *</label>
                        <select id="locationSelect" name="location" class="form-select" required>
                            <option value="">Select your location</option>
                            <option value="NR102">NR102</option>
                            <option value="NR103">NR103</option>
                            <option value="NR104">NR104</option>
                            <option value="NR105">NR105</option>
                            <option value="NR106">NR106</option>
                            <option value="NR107">NR107</option>
                            <option value="NR108">NR108</option>
                            <option value="NR109">NR109</option>
                            <option value="NR202">NR202</option>
                            <option value="NR203">NR203</option>
                            <option value="NR204">NR204</option>
                            <option value="NR205">NR205</option>
                            <option value="NR206">NR206</option>
                            <option value="NR207">NR207</option>
                            <option value="CL208">CL208</option>
                            <option value="CL209">CL209</option>
                            <option value="NR302">NR302</option>
                            <option value="NR303">NR303</option>
                            <option value="NR304">NR304</option>
                            <option value="NR305">NR305</option>
                            <option value="NR306">NR306</option>
                            <option value="NR307">NR307</option>
                            <option value="CL308">CL308</option>
                            <option value="CL309">CL309</option>
                            <option value="Gymnasium">Gymnasium</option>
                            <option value="Cafeteria">Cafeteria</option>
                            <option value="Library">Library</option>
                            <option value="Registrar">Registrar</option>
                            <option value="Outside Campus">Outside Campus</option>
                            <option value="In Meeting">In Meeting</option>
                            <option value="Idle">Idle</option>
                            <option value="On Leave">On Leave</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Custom Location</label>
                        <input type="text" id="customLocation" name="custom_location" class="form-input" 
                            placeholder="Type custom location if not in list above">
                        <small class="form-help">Leave empty to use selected location above</small>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeLocationModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Update Location</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="locationHistoryModal">
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Location History</h3>
                    <button type="button" class="modal-close" onclick="closeLocationHistoryModal()">&times;</button>
                </div>
                
        <div class="modal-overlay" id="locationHistoryModal">
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Location History</h3>
                    <button type="button" class="modal-close" onclick="closeLocationHistoryModal()">&times;</button>
                </div>
                
                <div class="location-history-list">
                    <?php if (!empty($location_history)): ?>
                        <?php foreach ($location_history as $history): ?>
                        <div class="history-item">
                            <div class="history-location">
                                <?php echo htmlspecialchars($history['location']); ?>
                            </div>
                            <div class="history-time">
                                <div>From: <?php echo date('M j, Y g:i A', strtotime($history['time_set'])); ?></div>
                                <?php if ($history['time_changed']): ?>
                                    <div>To: <?php echo date('M j, Y g:i A', strtotime($history['time_changed'])); ?></div>
                                    <div class="history-duration">
                                        Duration: <?php 
                                            $start = strtotime($history['time_set']);
                                            $end = strtotime($history['time_changed']);
                                            $diff = $end - $start;
                                            $hours = floor($diff / 3600);
                                            $minutes = floor(($diff % 3600) / 60);
                                            echo $hours > 0 ? $hours . 'h ' : '';
                                            echo $minutes . 'm';
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <div style="color: #4CAF50; font-weight: 500;">Current Location</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-history">
                            <p>No location history available</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeLocationHistoryModal()">Close</button>
                </div>
            </div>
        </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeLocationHistoryModal()">Close</button>
                </div>
            </div>
        </div>

        <script>
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                const contentWrapper = document.getElementById('contentWrapper');

                sidebar.classList.toggle('open');
                overlay.classList.toggle('show');
                contentWrapper.classList.toggle('sidebar-open');
            }

            function closeSidebar() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                const contentWrapper = document.getElementById('contentWrapper');

                sidebar.classList.remove('open');
                overlay.classList.remove('show');
                contentWrapper.classList.remove('sidebar-open');
            }

            function openLocationModal() {
                document.getElementById('locationModal').classList.add('show');
                document.body.style.overflow = 'hidden';
            }

            function closeLocationModal() {
                document.getElementById('locationModal').classList.remove('show');
                document.body.style.overflow = 'auto';
                document.getElementById('locationForm').reset();
            }

            function viewLocationHistory() {
                document.getElementById('locationHistoryModal').classList.add('show');
                document.body.style.overflow = 'hidden';
            }

            function closeLocationHistoryModal() {
                document.getElementById('locationHistoryModal').classList.remove('show');
                document.body.style.overflow = 'auto';
            }

            async function updateLocation() {
                const form = document.getElementById('locationForm');
                const formData = new FormData(form);
                
                const customLocation = formData.get('custom_location');
                const selectedLocation = formData.get('location');
                
                if (!customLocation && !selectedLocation) {
                    alert('Please select a location or enter a custom location.');
                    return;
                }
                
                const finalLocation = customLocation || selectedLocation;
                
                try {
                    const response = await fetch(window.location.pathname, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=update_location&location=${encodeURIComponent(finalLocation)}`
                    });

                    const result = await response.json();

                    if (result.success) {
                        document.getElementById('currentLocation').textContent = finalLocation;
                        
                        const locationUpdated = document.querySelector('.location-updated');
                        locationUpdated.textContent = 'Last updated: Just now';
                        
                        closeLocationModal();
                        
                        showNotification('Location updated successfully!', 'success');
                    } else {
                        alert('Error updating location: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while updating location. Please try again.');
                }
            }

            function viewFullSchedule() {
                showNotification('Full schedule view coming soon!', 'info');
            }

            function updateProfile() {
                showNotification('Profile update feature coming soon!', 'info');
            }

            function viewStudents() {
                showNotification('Student management feature coming soon!', 'info');
            }

            function leaveRequest() {
                showNotification('Leave request feature coming soon!', 'info');
            }

            function markAttendance(scheduleId) {
                if (confirm('Mark yourself as present for this class?')) {
                    showNotification('Attendance marked successfully!', 'success');
                }
            }

            function showNotification(message, type = 'info') {
                const existingNotifications = document.querySelectorAll('.notification');
                existingNotifications.forEach(notification => notification.remove());

                const notification = document.createElement('div');
                notification.className = `notification notification-${type}`;
                notification.innerHTML = `
                    <div class="notification-content">
                        <span class="notification-message">${message}</span>
                        <button class="notification-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
                    </div>
                `;

                document.body.appendChild(notification);

                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 3000);
            }

            document.addEventListener('DOMContentLoaded', function() {
                const locationSelect = document.getElementById('locationSelect');
                const customLocationInput = document.getElementById('customLocation');

                if (locationSelect && customLocationInput) {
                    locationSelect.addEventListener('change', function() {
                        if (this.value) {
                            customLocationInput.value = '';
                        }
                    });

                    customLocationInput.addEventListener('input', function() {
                        if (this.value) {
                            locationSelect.value = '';
                        }
                    });
                }
            });

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal-overlay')) {
                    if (e.target.id === 'locationModal') {
                        closeLocationModal();
                    } else if (e.target.id === 'locationHistoryModal') {
                        closeLocationHistoryModal();
                    }
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const openModal = document.querySelector('.modal-overlay.show');
                    if (openModal) {
                        if (openModal.id === 'locationModal') {
                            closeLocationModal();
                        } else if (openModal.id === 'locationHistoryModal') {
                            closeLocationHistoryModal();
                        }
                    }
                }
            });

            setInterval(function() {
                if (document.visibilityState === 'visible') {
                    updateLocationStatus();
                }
            }, 300000);

            async function updateLocationStatus() {
                try {
                    const response = await fetch(window.location.pathname + '?action=get_status');
                    const result = await response.json();
                    
                    if (result.success) {
                        const locationUpdated = document.querySelector('.location-updated');
                        if (locationUpdated) {
                            locationUpdated.textContent = `Last updated: ${result.last_updated}`;
                        }
                    }
                } catch (error) {
                    console.error('Error updating location status:', error);
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                console.log('Faculty dashboard loaded');
                
                const ongoingClasses = document.querySelectorAll('.schedule-item.ongoing');
                if (ongoingClasses.length > 0) {
                    showNotification(`You have ${ongoingClasses.length} ongoing class(es)`, 'info');
                }
            });

            async function switchScheduleTab(days, tabElement) {
                document.querySelectorAll('.schedule-tab').forEach(tab => tab.classList.remove('active'));
                tabElement.classList.add('active');
                
                try {
                    const response = await fetch(window.location.pathname, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=get_schedule&days=${encodeURIComponent(days)}`
                    });

                    const result = await response.json();
                    const scheduleList = document.getElementById('scheduleList');
                    
                    if (result.success) {
                        scheduleList.innerHTML = result.html;
                    } else {
                        scheduleList.innerHTML = `
                            <div class="no-schedule">
                                <div class="no-schedule-icon">📅</div>
                                <div class="no-schedule-text">No classes scheduled</div>
                                <div class="no-schedule-subtitle">for ${days}</div>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('Error loading schedule:', error);
                }
            }
        </script>
    </body>
</html>