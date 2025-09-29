<?php
require_once 'assets/php/common_utilities.php';
initializeSession();
$pdo = initializeDatabase();
validateUserSession('class');
$user_id = $_SESSION['user_id'];
$class_info = getClassInfo($pdo, $user_id);
if (!$class_info) {
    die("Class information not found.");
}
$class_id = $class_info['class_id'];
$announcements = getClassAnnouncements($pdo);
$faculty_data = getClassFaculty($pdo, $class_id);
$faculty_courses = [];

foreach ($faculty_data as $faculty) {
    $faculty_courses[$faculty['faculty_id']] = getFacultyCourses($pdo, $faculty['faculty_id'], $class_id);
}

require_once 'assets/php/fetch_announcements.php';
$announcements = fetchAnnouncements($pdo, $_SESSION['role'], 10);

function getClassInfo($pdo, $user_id) {
    $class_query = "SELECT class_id, class_code, class_name FROM classes WHERE user_id = ? AND is_active = TRUE";
    $stmt = $pdo->prepare($class_query);
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function getClassAnnouncements($pdo) {
    $announcements_query = "
        SELECT a.*, u.full_name as created_by_name,
               DATE_FORMAT(a.created_at, '%M %d, %Y at %h:%i %p') as formatted_date,
               CASE 
                   WHEN a.created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN CONCAT(TIMESTAMPDIFF(MINUTE, a.created_at, NOW()), ' minutes ago')
                   WHEN a.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, a.created_at, NOW()), ' hours ago')
                   WHEN a.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN CONCAT(TIMESTAMPDIFF(DAY, a.created_at, NOW()), ' days ago')
                   ELSE '1 week ago'
               END as time_ago
        FROM announcements a 
        JOIN users u ON a.created_by = u.user_id 
        WHERE a.is_active = TRUE 
        AND (a.target_audience = 'all' OR a.target_audience = 'classes')
        ORDER BY a.created_at DESC 
        LIMIT 10";
    $stmt = $pdo->prepare($announcements_query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getClassFaculty($pdo, $class_id) {
    $faculty_query = "
        SELECT f.faculty_id, u.full_name as faculty_name, f.program, f.current_location, f.last_location_update,
               f.office_hours, f.contact_email, f.contact_phone,
               " . getFacultyStatusSQL() . ",
               " . getTimeAgoSQL() . "
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id
        JOIN schedules s ON f.faculty_id = s.faculty_id
        WHERE f.is_active = TRUE AND s.is_active = TRUE AND s.class_id = ?
        GROUP BY f.faculty_id
        ORDER BY u.full_name";
    $stmt = $pdo->prepare($faculty_query);
    $stmt->execute([$class_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFacultyCourses($pdo, $faculty_id, $class_id) {
    $debug_time = '13:30:00'; // Debug time
    $debug_day = 'F'; // Debug day: M, T, W, TH, F, S
    
    $courses_query = "
        SELECT s.course_code, c.course_description, s.days, s.time_start, s.time_end, s.room,
        CASE 
            WHEN (s.days = '$debug_day' OR 
                  (s.days = 'MW' AND '$debug_day' IN ('M', 'W')) OR
                  (s.days = 'MF' AND '$debug_day' IN ('M', 'F')) OR
                  (s.days = 'WF' AND '$debug_day' IN ('W', 'F')) OR
                  (s.days = 'MWF' AND '$debug_day' IN ('M', 'W', 'F')) OR
                  (s.days = 'TTH' AND '$debug_day' IN ('T', 'TH'))) 
                 AND TIME('$debug_time') > s.time_end
                 THEN 'finished'
            WHEN TIME('$debug_time') BETWEEN s.time_start AND s.time_end 
                 AND (s.days = '$debug_day' OR 
                      (s.days = 'MW' AND '$debug_day' IN ('M', 'W')) OR
                      (s.days = 'MF' AND '$debug_day' IN ('M', 'F')) OR
                      (s.days = 'WF' AND '$debug_day' IN ('W', 'F')) OR
                      (s.days = 'MWF' AND '$debug_day' IN ('M', 'W', 'F')) OR
                      (s.days = 'TTH' AND '$debug_day' IN ('T', 'TH'))) 
                 THEN 'current'
            WHEN TIME('$debug_time') < s.time_start 
                 AND (s.days = '$debug_day' OR 
                      (s.days = 'MW' AND '$debug_day' IN ('M', 'W')) OR
                      (s.days = 'MF' AND '$debug_day' IN ('M', 'F')) OR
                      (s.days = 'WF' AND '$debug_day' IN ('W', 'F')) OR
                      (s.days = 'MWF' AND '$debug_day' IN ('M', 'W', 'F')) OR
                      (s.days = 'TTH' AND '$debug_day' IN ('T', 'TH'))) 
                 THEN 'upcoming'
            ELSE 'not-today'
        END as status
        FROM schedules s
        JOIN courses c ON s.course_code = c.course_code
        WHERE s.faculty_id = ? AND s.class_id = ? AND s.is_active = TRUE
        ORDER BY 
            CASE 
                WHEN (s.days = '$debug_day' OR 
                      (s.days = 'MW' AND '$debug_day' IN ('M', 'W')) OR
                      (s.days = 'MF' AND '$debug_day' IN ('M', 'F')) OR
                      (s.days = 'WF' AND '$debug_day' IN ('W', 'F')) OR
                      (s.days = 'MWF' AND '$debug_day' IN ('M', 'W', 'F')) OR
                      (s.days = 'TTH' AND '$debug_day' IN ('T', 'TH'))) 
                     AND TIME('$debug_time') > s.time_end
                     THEN 1
                WHEN TIME('$debug_time') BETWEEN s.time_start AND s.time_end 
                     AND (s.days = '$debug_day' OR 
                          (s.days = 'MW' AND '$debug_day' IN ('M', 'W')) OR
                          (s.days = 'MF' AND '$debug_day' IN ('M', 'F')) OR
                          (s.days = 'WF' AND '$debug_day' IN ('W', 'F')) OR
                          (s.days = 'MWF' AND '$debug_day' IN ('M', 'W', 'F')) OR
                          (s.days = 'TTH' AND '$debug_day' IN ('T', 'TH'))) 
                     THEN 2
                WHEN TIME('$debug_time') < s.time_start 
                     AND (s.days = '$debug_day' OR 
                          (s.days = 'MW' AND '$debug_day' IN ('M', 'W')) OR
                          (s.days = 'MF' AND '$debug_day' IN ('M', 'F')) OR
                          (s.days = 'WF' AND '$debug_day' IN ('W', 'F')) OR
                          (s.days = 'MWF' AND '$debug_day' IN ('M', 'W', 'F')) OR
                          (s.days = 'TTH' AND '$debug_day' IN ('T', 'TH'))) 
                     THEN 3
                ELSE 4
            END,
            s.time_start";
    $stmt = $pdo->prepare($courses_query);
    $stmt->execute([$faculty_id, $class_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaculTrack - Faculty Locator</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>

.course-info {
    position: relative;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px;
    margin-bottom: 8px;
    border-radius: 4px;
    transition: all 0.3s ease;
    color: black;
}

.course-content {
    flex: 1;
    padding-right: 10px;
}

.course-status-label {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
}

.course-info.course-finished {
    opacity: 0.85;
    background-color: #f5f5f5;
    border-left: 4px solid #9e9e9e;
}

.course-status-label.status-finished {
    background: linear-gradient(135deg, #9E9E9E 0%, #BDBDBD 100%);
    color: white;
}

.course-info.course-current {
    background-color: #c8e6c9;
    border-left: 4px solid #4caf50;
    padding-left: 8px;
}

.course-status-label.status-current {
    background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);
    color: white;
    animation: pulse-current 2s infinite;
}

@keyframes pulse-current {
    0%, 100% {
        box-shadow: 0 2px 6px rgba(76, 175, 80, 0.4),
                    inset 0 1px 0 rgba(255, 255, 255, 0.3);
    }
    50% {
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.6),
                    inset 0 1px 0 rgba(255, 255, 255, 0.4);
    }
}

.course-info.course-upcoming {
    background-color: #bbdefb;
    border-left: 4px solid #2196f3;
    padding-left: 8px;
}

.course-status-label.status-upcoming {
    background: linear-gradient(135deg, #2196F3 0%, #42A5F5 100%);
    color: white;
}

.course-info.course-not-today {
    background-color: #fff8e1;
    border-left: 4px solid #ffc107;
    color: #999;
    padding-left: 8px;
    margin-top: 12px;
}

.course-status-label.status-not-today {
    background: linear-gradient(135deg, #FFC107 0%, #FFD54F 100%);
    color: #333;
}

.course-info.course-not-today:first-of-type {
    margin-top: 16px;
}

@media (max-width: 768px) {
    .course-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .course-status-label {
        align-self: flex-end;
    }
}

        .location-info {
            background: linear-gradient(135deg, rgba(232, 245, 232, 0.9), rgba(241, 248, 233, 0.9));
            border-radius: 8px;
            padding: 8px;
            margin-bottom: 8px;
            clear: both;
            border-left: 3px solid #FFC107;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.15),
                        inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        .location-status {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }

        .location-text {
            font-weight: bold;
            color: #333;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }

        .time-info {
            color: #666;
            font-size: 0.75rem;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }

        .contact-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid rgba(238, 238, 238, 0.8);
        }

        .office-hours {
            font-size: 0.7rem;
            color: #666;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }

        .faculty-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
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
                    <?php echo renderAnnouncementCard($announcement); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="content-wrapper" id="contentWrapper">
            <div class="header">
                <h1>FaculTrack</h1>
                <p>Sultan Kudarat State University - Isulan Campus</p>
                <small>Showing faculty for: <strong><?php echo htmlspecialchars($class_info['class_name']); ?></strong></small>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($class_info['class_name']); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
        </div>
            </div>

            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search faculty by name or department..." id="searchInput">
                <button class="search-btn" onclick="searchFaculty()">🔍</button>
            </div>

            <div class="faculty-grid" id="facultyGrid">
                <?php if (empty($faculty_data)): ?>
                <div class="empty-state">
                    <h3>No faculty assigned</h3>
                    <p>No faculty members are currently assigned to your class schedules</p>
                </div>
                <?php else: ?>
                <?php foreach ($faculty_data as $faculty): ?>
                <div class="faculty-card" data-name="<?php echo htmlspecialchars($faculty['faculty_name']); ?>" data-program="<?php echo htmlspecialchars($faculty['program']); ?>" data-faculty-id="<?php echo $faculty['faculty_id']; ?>">
                    <div class="faculty-avatar"><?php echo getInitials($faculty['faculty_name']); ?></div>
                    <div class="faculty-name"><?php echo htmlspecialchars($faculty['faculty_name']); ?></div>
                    <div class="faculty-program"><?php echo htmlspecialchars($faculty['program']); ?></div>
                    
                <div class="courses-list">
                <?php foreach ($faculty_courses[$faculty['faculty_id']] as $course): ?>
                <div class="course-info course-<?php echo $course['status']; ?>">
                    <div class="course-content">
                        <strong><?php echo htmlspecialchars($course['course_code']); ?>:</strong> 
                        <?php echo htmlspecialchars($course['course_description']); ?>
                        <br>
                        <small>
                            <?php echo strtoupper($course['days']); ?> | 
                            <?php echo formatTime($course['time_start']); ?> - <?php echo formatTime($course['time_end']); ?> | 
                            <?php echo htmlspecialchars($course['room']); ?>
                        </small>
                    </div>
                    <span class="course-status-label status-<?php echo $course['status']; ?>">
                        <?php 
                            switch($course['status']) {
                                case 'current': echo 'CURRENT'; break;
                                case 'upcoming': echo 'UPCOMING'; break;
                                case 'finished': echo 'DONE'; break;
                                case 'not-today': echo 'NOT TODAY'; break;
                            }
                        ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
                    <div class="location-info">
                        <div class="location-status">
                            <span class="status-dot status-<?php echo $faculty['status']; ?>"></span>
                            <span class="location-text">
                                <?php 
                                    switch($faculty['status']) {
                                        case 'available': echo 'Available'; break;
                                        case 'busy': echo 'In Meeting'; break;
                                        case 'offline': echo 'Offline'; break;
                                        default: echo 'Unknown';
                                    }
                                ?>
                            </span>
                        </div>
                        <div style="margin-left: 14px; color: #333; font-weight: 500; font-size: 0.85rem;">
                            <?php echo htmlspecialchars($faculty['current_location']); ?>
                        </div>
                        <div class="time-info">Last updated: <?php echo $faculty['last_updated']; ?></div>
                    </div>

                    <div class="contact-info">
                        <div class="office-hours">
                            Office Hours:<br><?php echo htmlspecialchars($faculty['office_hours'] ?? 'Not specified'); ?>
                        </div>
                        <?php if (!empty($faculty['contact_email'])): ?>
                        <button class="contact-btn" onclick="contactFaculty('<?php echo htmlspecialchars($faculty['contact_email']); ?>')">
                            Contact
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

<script src="assets/js/home.js"></script>
</body>
</html>