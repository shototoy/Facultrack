<?php
require_once 'assets/php/common_utilities.php';
initializeSession();
$pdo = initializeDatabase();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_location') {
    validateUserSession('faculty');
    $user_id = $_SESSION['user_id'];
    $location = validateInput($_POST['location'] ?? '');
    
    if (empty($location)) {
        sendJsonResponse(['success' => false, 'message' => 'Location cannot be empty']);
    }
    
    try {
        $update_query = "UPDATE faculty SET current_location = ?, last_location_update = NOW() WHERE user_id = ? AND is_active = TRUE";
        $stmt = $pdo->prepare($update_query);
        $success = $stmt->execute([$location, $user_id]);
        
        if ($success && $stmt->rowCount() > 0) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Location updated successfully',
                'location' => $location,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            sendJsonResponse(['success' => false, 'message' => 'Failed to update location']);
        }
    } catch (Exception $e) {
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
    $faculty_id = getFacultyInfo($pdo, $_SESSION['user_id'])['faculty_id'];
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
$location_history = getLocationHistory($pdo, $faculty_info['faculty_id']);
$schedule_tabs = getScheduleTabs($pdo, $faculty_info['faculty_id']);

require_once 'assets/php/fetch_announcements.php';
$announcements = fetchAnnouncements($pdo, $_SESSION['role'], 10);

function getFacultyInfo($pdo, $user_id) {
    $faculty_query = "SELECT f.*, u.full_name FROM faculty f JOIN users u ON f.user_id = u.user_id WHERE f.user_id = ? AND f.is_active = TRUE";
    $stmt = $pdo->prepare($faculty_query);
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getTodaySchedule($pdo, $faculty_id) {
    $today = date('w');
    $day_mapping = [0 => 'S', 1 => 'M', 2 => 'T', 3 => 'W', 4 => 'TH', 5 => 'F', 6 => 'SAT'];
    $today_code = $day_mapping[$today];
    
    $schedule_query = "
        SELECT s.*, c.course_description, cl.class_name, cl.class_code,
            CASE 
                WHEN TIME(NOW()) BETWEEN s.time_start AND s.time_end THEN 'ongoing'
                WHEN TIME(NOW()) < s.time_start THEN 'upcoming'
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
        ORDER BY s.time_start";
    $stmt = $pdo->prepare($schedule_query);
    $stmt->execute([$faculty_id, $today_code, $today_code, $today_code, $today_code, $today_code, $today_code, $today_code]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLocationHistory($pdo, $faculty_id) {
    $query = "SELECT current_location, last_location_update FROM faculty WHERE faculty_id = ? ORDER BY last_location_update DESC LIMIT 5";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$faculty_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getScheduleTabs($pdo, $faculty_id) {
    $days_query = "SELECT DISTINCT s.days FROM schedules s WHERE s.faculty_id = ? AND s.is_active = TRUE ORDER BY s.days";
    $stmt = $pdo->prepare($days_query);
    $stmt->execute([$faculty_id]);
    $schedule_days = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return createDayTabs($schedule_days);
}

function createDayTabs($schedule_days) {
    $day_sets = [];
    $all_days = [];
    
    foreach ($schedule_days as $days) {
        $day_sets[] = $days;
        $individual_days = [];
        if (strpos($days, 'MTWTHF') !== false) {
            $individual_days = ['M', 'T', 'W', 'TH', 'F'];
        } else {
            $remaining = $days;
            while ($remaining) {
                if (strpos($remaining, 'TH') === 0) {
                    $individual_days[] = 'TH';
                    $remaining = substr($remaining, 2);
                } elseif (strpos($remaining, 'SAT') === 0) {
                    $individual_days[] = 'SAT';
                    $remaining = substr($remaining, 3);
                } else {
                    $individual_days[] = $remaining[0];
                    $remaining = substr($remaining, 1);
                }
            }
        }
        $all_days = array_merge($all_days, $individual_days);
    }
    
    $all_days = array_unique($all_days);
    $unique_tabs = [];
    
    foreach ($day_sets as $days) {
        if (strlen($days) > 2 || $days == 'TH' || $days == 'SAT') {
            $unique_tabs[] = $days;
        }
    }
    
    foreach ($all_days as $day) {
        $covered = false;
        foreach ($unique_tabs as $tab) {
            if (strpos($tab, $day) !== false) {
                $covered = true;
                break;
            }
        }
        if (!$covered) {
            $unique_tabs[] = $day;
        }
    }
    
    return array_unique($unique_tabs);
}

function getScheduleForDays($pdo, $faculty_id, $days) {
    $schedule_query = "
        SELECT s.*, c.course_description, cl.class_name, cl.class_code,
            CASE 
                WHEN TIME(NOW()) BETWEEN s.time_start AND s.time_end AND s.days = ? THEN 'ongoing'
                WHEN TIME(NOW()) < s.time_start AND s.days = ? THEN 'upcoming'
                ELSE 'finished'
            END as status
        FROM schedules s
        JOIN courses c ON s.course_code = c.course_code
        JOIN classes cl ON s.class_id = cl.class_id
        WHERE s.faculty_id = ? AND s.is_active = TRUE AND s.days = ?
        ORDER BY s.time_start";
    $stmt = $pdo->prepare($schedule_query);
    $stmt->execute([$days, $days, $faculty_id, $days]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            gap: 1px;
            flex: 1;
            background: #e0e0e0;
        }

        .schedule-section {
            grid-row: 1 / 3;
            grid-column: 1;
            background: white;
            padding: 25px;
            overflow-y: auto;
        }

        .location-section {
            grid-row: 1;
            grid-column: 2;
            background: white;
            padding: 25px;
            border-left: 1px solid #e0e0e0;
        }

        .actions-section {
            grid-row: 2;
            grid-column: 2;
            background: white;
            padding: 25px;
            border-left: 1px solid #e0e0e0;
            border-top: 1px solid #e0e0e0;
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
            border-bottom: 2px solid #f0f0f0;
        }

        .schedule-header h3 {
            color: #1B5E20;
            margin: 0;
            font-size: 1.4rem;
        }

        .schedule-date {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            flex: 1;
            overflow-y: auto;
        }

        .schedule-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .schedule-item.ongoing {
            background: #e8f5e8;
            border-left-color: #4CAF50;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2);
        }

        .schedule-item.upcoming {
            background: #fff3e0;
            border-left-color: #FF9800;
        }

        .schedule-item.finished {
            background: #f5f5f5;
            border-left-color: #9E9E9E;
            opacity: 0.8;
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
        }

        .location-header h3 {
            color: #1B5E20;
            margin: 0;
            font-size: 1.2rem;
        }

        .location-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
            color: #4CAF50;
        }

        .location-display {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            border-left: 3px solid #FFC107;
            margin-bottom: 15px;
            flex: 1;
        }

        .location-updated {
            font-size: 0.8rem;
            color: #666;
        }

        .location-actions {
            display: flex;
            gap: 10px;
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
        }

        .actions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            flex: 1;
        }

        .action-card {
            border: 2px solid transparent;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .action-card:hover {
            background: #e9ecef;
            border-color: #2E7D32;
            transform: translateY(-2px);
        }

        .action-icon {
            font-size: 2rem;
            margin-bottom: 4px;
            color: #2E7D32;
        }

        .action-title {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .action-subtitle {
            font-size: 0.75rem;
            color: #666;
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
        }

        .time-duration {
            font-size: 0.75rem;
            color: #666;
            margin-top: 2px;
        }

        .schedule-details {
            flex: 1;
            margin-right: 15px;
        }

        .schedule-info {
            display: flex;
            gap: 12px;
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
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
        }

        .no-schedule-text {
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: #333;
        }

        .no-schedule-subtitle {
            font-size: 0.85rem;
            color: #666;
        }

        .schedule-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .schedule-tab {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .schedule-tab.active {
            background: #2E7D32;
            color: white;
            border-color: #2E7D32;
        }

        .schedule-tab:hover:not(.active) {
            background: #e9ecef;
            border-color: #aaa;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <button class="announcement-toggle" onclick="toggleSidebar()">
            📢
            <span class="announcement-badge"><?php echo count($announcements); ?></span>
        </button>

        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($faculty_name); ?></span>
            <span style="font-size: 0.8rem; color: #666;">(Faculty)</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

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
            </div>

            <div class="dashboard-grid">
                <div class="schedule-section">
                    <div class="schedule-card">
                        <div class="schedule-header">
                            <h3>Schedule</h3>
                            <div class="schedule-date"><?php echo date('F j, Y - l'); ?></div>
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
                            <option value="Faculty Lounge - 1st Floor">Faculty Lounge - 1st Floor</option>
                            <option value="Faculty Lounge - 2nd Floor">Faculty Lounge - 2nd Floor</option>
                            <option value="Faculty Lounge - 3rd Floor">Faculty Lounge - 3rd Floor</option>
                            <option value="Room 101 - CCS Building">Room 101 - CCS Building</option>
                            <option value="Room 102 - CCS Building">Room 102 - CCS Building</option>
                            <option value="Room 201 - CCS Building">Room 201 - CCS Building</option>
                            <option value="Room 202 - CCS Building">Room 202 - CCS Building</option>
                            <option value="Room 301 - CCS Building">Room 301 - CCS Building</option>
                            <option value="Room 302 - CCS Building">Room 302 - CCS Building</option>
                            <option value="Library - Main Floor">Library - Main Floor</option>
                            <option value="Library - 2nd Floor">Library - 2nd Floor</option>
                            <option value="Registrar Office">Registrar Office</option>
                            <option value="Dean Office - CCS">Dean Office - CCS</option>
                            <option value="Conference Room A">Conference Room A</option>
                            <option value="Conference Room B">Conference Room B</option>
                            <option value="Cafeteria">Cafeteria</option>
                            <option value="Gymnasium">Gymnasium</option>
                            <option value="Outside Campus">Outside Campus</option>
                            <option value="In Meeting">In Meeting</option>
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
                
                <div class="location-history-list">
                    <?php foreach ($location_history as $history): ?>
                    <div class="history-item">
                        <div class="history-location"><?php echo htmlspecialchars($history['current_location']); ?></div>
                        <div class="history-time">
                            <?php echo date('M j, Y g:i A', strtotime($history['last_location_update'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
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