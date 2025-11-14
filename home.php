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

require_once 'assets/php/announcement_functions.php';
$announcements = fetchAnnouncements($pdo, $_SESSION['role'], 10);

// Polling endpoints moved to assets/php/polling_api.php

function generateFacultyCardHTML($faculty, $courses) {
    ob_start();
    ?>
    <div class="faculty-card" data-faculty-id="<?php echo $faculty['faculty_id']; ?>">
        <div class="faculty-header">
            <h3 class="faculty-name"><?php echo htmlspecialchars($faculty['faculty_name']); ?></h3>
            <div class="status-badge status-<?php echo strtolower($faculty['status']); ?>">
                <?php echo $faculty['status']; ?>
            </div>
        </div>
        
        <div class="courses-section">
            <h4>Today's Classes</h4>
            <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $course): ?>
                    <div class="course-item course-<?php echo $course['status']; ?>">
                        <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                        <div class="course-description"><?php echo htmlspecialchars($course['course_description']); ?></div>
                        <div class="course-time"><?php echo formatTime($course['time_start']) . ' - ' . formatTime($course['time_end']); ?></div>
                        <div class="course-room">Room: <?php echo htmlspecialchars($course['room'] ?? 'TBA'); ?></div>
                        <div class="course-status status-<?php echo $course['status']; ?>">
                            <?php 
                            switch ($course['status']) {
                                case 'current': echo 'In Progress';
                                    break;
                                case 'upcoming': echo 'Upcoming';
                                    break;
                                case 'finished': echo 'Completed';
                                    break;
                                default: echo 'Not Today';
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-courses">No classes scheduled for today</div>
            <?php endif; ?>
        </div>

        <div class="location-section">
            <div class="location-info">
                <div class="current-location">
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
    </div>
    <?php
    return ob_get_clean();
}

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
    $current_day = date('w');
    $day_mapping = [0 => 'S', 1 => 'M', 2 => 'T', 3 => 'W', 4 => 'TH', 5 => 'F', 6 => 'SAT'];
    $today_code = $day_mapping[$current_day];
    $current_time = date('H:i:s');
    
    $courses_query = "
        SELECT s.course_code, c.course_description, s.days, s.time_start, s.time_end, s.room,
        CASE 
            WHEN (s.days = '$today_code' OR 
                  (s.days = 'MW' AND '$today_code' IN ('M', 'W')) OR
                  (s.days = 'MF' AND '$today_code' IN ('M', 'F')) OR
                  (s.days = 'WF' AND '$today_code' IN ('W', 'F')) OR
                  (s.days = 'MWF' AND '$today_code' IN ('M', 'W', 'F')) OR
                  (s.days = 'TTH' AND '$today_code' IN ('T', 'TH'))) 
                 AND TIME('$current_time') > s.time_end
                 THEN 'finished'
            WHEN TIME('$current_time') BETWEEN s.time_start AND s.time_end 
                 AND (s.days = '$today_code' OR 
                      (s.days = 'MW' AND '$today_code' IN ('M', 'W')) OR
                      (s.days = 'MF' AND '$today_code' IN ('M', 'F')) OR
                      (s.days = 'WF' AND '$today_code' IN ('W', 'F')) OR
                      (s.days = 'MWF' AND '$today_code' IN ('M', 'W', 'F')) OR
                      (s.days = 'TTH' AND '$today_code' IN ('T', 'TH'))) 
                 THEN 'current'
            WHEN TIME('$current_time') < s.time_start 
                 AND (s.days = '$today_code' OR 
                      (s.days = 'MW' AND '$today_code' IN ('M', 'W')) OR
                      (s.days = 'MF' AND '$today_code' IN ('M', 'F')) OR
                      (s.days = 'WF' AND '$today_code' IN ('W', 'F')) OR
                      (s.days = 'MWF' AND '$today_code' IN ('M', 'W', 'F')) OR
                      (s.days = 'TTH' AND '$today_code' IN ('T', 'TH'))) 
                 THEN 'upcoming'
            ELSE 'not-today'
        END as status
        FROM schedules s
        JOIN courses c ON s.course_code = c.course_code
        WHERE s.faculty_id = ? AND s.class_id = ? AND s.is_active = TRUE
        ORDER BY 
            CASE 
                WHEN (s.days = '$today_code' OR 
                      (s.days = 'MW' AND '$today_code' IN ('M', 'W')) OR
                      (s.days = 'MF' AND '$today_code' IN ('M', 'F')) OR
                      (s.days = 'WF' AND '$today_code' IN ('W', 'F')) OR
                      (s.days = 'MWF' AND '$today_code' IN ('M', 'W', 'F')) OR
                      (s.days = 'TTH' AND '$today_code' IN ('T', 'TH'))) 
                     AND TIME('$current_time') > s.time_end
                     THEN 1
                WHEN TIME('$current_time') BETWEEN s.time_start AND s.time_end 
                     AND (s.days = '$today_code' OR 
                          (s.days = 'MW' AND '$today_code' IN ('M', 'W')) OR
                          (s.days = 'MF' AND '$today_code' IN ('M', 'F')) OR
                          (s.days = 'WF' AND '$today_code' IN ('W', 'F')) OR
                          (s.days = 'MWF' AND '$today_code' IN ('M', 'W', 'F')) OR
                          (s.days = 'TTH' AND '$today_code' IN ('T', 'TH'))) 
                     THEN 2
                WHEN TIME('$current_time') < s.time_start 
                     AND (s.days = '$today_code' OR 
                          (s.days = 'MW' AND '$today_code' IN ('M', 'W')) OR
                          (s.days = 'MF' AND '$today_code' IN ('M', 'F')) OR
                          (s.days = 'WF' AND '$today_code' IN ('W', 'F')) OR
                          (s.days = 'MWF' AND '$today_code' IN ('M', 'W', 'F')) OR
                          (s.days = 'TTH' AND '$today_code' IN ('T', 'TH'))) 
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
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'assets/php/feather_icons.php'; ?>
    <div class="main-container">
        <div class="content-wrapper" id="contentWrapper">
            <?php 
            $online_faculty = count(array_filter($faculty_data, function($faculty) {
                return $faculty['status'] === 'available';
            }));
            
            $header_config = [
                'page_title' => 'FaculTrack',
                'page_subtitle' => 'Sultan Kudarat State University - Isulan Campus',
                'user_name' => $class_info['class_name'],
                'user_role' => 'Student Portal',
                'user_details' => 'Year ' . ($class_info['year_level'] ?? 'N/A') . ' - ' . ($class_info['semester'] ?? 'N/A'),
                'announcements_count' => count($announcements),
                'announcements' => $announcements,
                'stats' => [
                    ['label' => 'Faculty', 'value' => count($faculty_data)],
                    ['label' => 'Online', 'value' => $online_faculty],
                    ['label' => 'Courses', 'value' => count(array_unique(array_column($faculty_data, 'course_code')))]
                ]
            ];
            include 'assets/php/page_header.php';
            ?>

            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search faculty by name or department..." id="searchInput">
                <button class="search-btn" onclick="searchFaculty()">
                    <svg class="feather"><use href="#search"></use></svg>
                </button>
            </div>

            <div class="home-content-wrapper">
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
    </div>

<script>
    window.userRole = 'class';
</script>
<script src="assets/js/shared_functions.js"></script>
<script src="assets/js/live_polling.js"></script>
<script src="assets/js/home.js"></script>
</body>
</html>