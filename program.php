<?php
require_once 'assets/php/common_utilities.php';
initializeSession();
$pdo = initializeDatabase();
validateUserSession('program_chair');
$user_id = $_SESSION['user_id'];
$program_chair_name = $_SESSION['full_name'];
$chair_info = getProgramChairInfo($pdo, $user_id);
$program = $chair_info ? $chair_info['program'] : 'Unknown Program';
$classes_data = getProgramClasses($pdo, $user_id);
$class_ids = array_column($classes_data, 'class_id');
$faculty_data = [];
$class_schedules = [];
$courses_data = [];
$faculty_schedules = [];

if (!empty($class_ids)) {
    $faculty_data = getAllFaculty($pdo);
    foreach ($faculty_data as $faculty) {
        $faculty_schedules[$faculty['faculty_id']] = getFacultySchedules($pdo, $faculty['faculty_id']);
    }
    $courses_data = getProgramCourses($pdo, $class_ids);
    foreach ($classes_data as $class) {
        $class_schedules[$class['class_id']] = getClassSchedules($pdo, $class['class_id']);
    }
}

require_once 'assets/php/fetch_announcements.php';
$announcements = fetchAnnouncements($pdo, $_SESSION['role'], 10);

function getProgramChairInfo($pdo, $user_id) {
    $chair_program_query = "SELECT program FROM faculty WHERE user_id = ? AND is_active = TRUE";
    $stmt = $pdo->prepare($chair_program_query);
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getProgramClasses($pdo, $user_id) {
    $classes_query = "
        SELECT c.class_id, c.class_code, c.class_name, c.year_level, c.semester, c.academic_year,
               u.full_name as class_account_name,
               COUNT(s.schedule_id) as total_subjects,
               COUNT(DISTINCT s.faculty_id) as assigned_faculty
        FROM classes c
        JOIN users u ON c.user_id = u.user_id
        LEFT JOIN schedules s ON c.class_id = s.class_id AND s.is_active = TRUE
        WHERE c.program_chair_id = ? AND c.is_active = TRUE
        GROUP BY c.class_id
        ORDER BY c.year_level, c.class_name";
    $stmt = $pdo->prepare($classes_query);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllFaculty($pdo) {
    $faculty_query = "
        SELECT f.faculty_id, u.full_name as faculty_name, f.employee_id, f.current_location, f.last_location_update,
               f.office_hours, f.contact_email, f.contact_phone,
               " . getFacultyStatusSQL() . ",
               " . getTimeAgoSQL() . "
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id
        WHERE f.is_active = TRUE 
        ORDER BY u.full_name";
    $stmt = $pdo->prepare($faculty_query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFacultySchedules($pdo, $faculty_id) {
    $schedule_query = "
        SELECT s.course_code, c.course_description, c.units, s.days, s.time_start, s.time_end, s.room,
               cl.class_name, cl.class_code
        FROM schedules s
        JOIN courses c ON s.course_code = c.course_code
        JOIN classes cl ON s.class_id = cl.class_id
        WHERE s.faculty_id = ? AND s.is_active = TRUE
        ORDER BY s.time_start";
    $stmt = $pdo->prepare($schedule_query);
    $stmt->execute([$faculty_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProgramCourses($pdo, $class_ids) {
    $in_clause = buildInClause($class_ids);
    $course_query = "
        SELECT DISTINCT c.course_code, c.course_description, c.units
        FROM courses c
        JOIN schedules s ON c.course_code = s.course_code
        WHERE s.is_active = TRUE AND s.class_id IN ({$in_clause['placeholders']})
        ORDER BY c.course_code";
    $stmt = $pdo->prepare($course_query);
    $stmt->execute($in_clause['values']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getClassSchedules($pdo, $class_id) {
    $schedule_query = "
        SELECT s.course_code, c.course_description, s.days, s.time_start, s.time_end, s.room,
               u.full_name as faculty_name
        FROM schedules s
        JOIN courses c ON s.course_code = c.course_code
        JOIN faculty f ON s.faculty_id = f.faculty_id
        JOIN users u ON f.user_id = u.user_id
        WHERE s.class_id = ? AND s.is_active = TRUE
        ORDER BY s.time_start";
    $stmt = $pdo->prepare($schedule_query);
    $stmt->execute([$class_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaculTrack - Program Chair Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .class-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left: 4px solid #2E7D32;
            transition: transform 0.3s ease;
        }

        .class-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .class-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .class-info {
            flex: 1;
        }

        .class-name {
            font-size: 1.1rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .class-code {
            color: #2E7D32;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .class-meta {
            font-size: 0.85rem;
            color: #666;
        }

        .class-stats {
            display: flex;
            gap: 15px;
            margin: 15px 0;
        }

        .class-stat {
            text-align: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 6px;
            flex: 1;
        }

        .class-stat-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2E7D32;
        }

        .class-stat-label {
            font-size: 0.75rem;
            color: #666;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .course-card {
            background: #fff;
            border: 1px solid #ddd;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .course-code {
            color: #2c3e50;
            font-size: 2.25rem;
        }

        .course-units {
            color: #888;
            font-size: 1.2rem;
        }

        .course-description {
            font-size: 1rem;
            color: #333;
        }

        .schedule-preview {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .schedule-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 0.85rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .schedule-item:last-child {
            border-bottom: none;
        }

        .schedule-course {
            font-weight: 500;
            color: #333;
        }

        .schedule-time {
            color: #666;
            font-size: 0.8rem;
        }

        .add-card {
            background: linear-gradient(135deg, #E8F5E8 0%, #F1F8E9 100%);
            border: 2px dashed #2E7D32;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }

        .add-card-course {
            border-radius: 12px;
            padding: 20px;
            min-height: 100px;
        }

        .add-card:hover {
            background: linear-gradient(135deg, #C8E6C9 0%, #E8F5E8 100%);
            border-color: #1B5E20;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.15);
        }

        .add-card-icon {
            font-size: 3rem;
            color: #2E7D32;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .add-card-course-icon {
            font-size: 1.5rem;
            margin-bottom: 0px;
        }

        .add-card:hover .add-card-icon {
            color: #1B5E20;
            transform: scale(1.1);
        }

        .add-card-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #1B5E20;
        }

        .add-card-subtitle {
            font-size: 0.9rem;
            color: #2E7D32;
            opacity: 0.8;
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
                <h1>FaculTrack - Program Chair</h1>
                <p>Sultan Kudarat State University - Isulan Campus</p>
                <small>Program: <strong><?php echo htmlspecialchars($program); ?></strong></small>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($program_chair_name); ?></span>
                    <span style="font-size: 0.8rem; color: white;">(<?php echo htmlspecialchars($program); ?>)</span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($faculty_data); ?></div>
                    <div class="stat-label">Faculty Members</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($classes_data); ?></div>
                    <div class="stat-label">Classes Managed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $online_count = array_reduce($faculty_data, function($count, $faculty) {
                            return $count + ($faculty['status'] === 'available' ? 1 : 0);
                        }, 0);
                        echo $online_count;
                        ?>
                    </div>
                    <div class="stat-label">Faculty Online</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $total_schedules = array_reduce($classes_data, function($count, $class) {
                            return $count + $class['total_subjects'];
                        }, 0);
                        echo $total_schedules;
                        ?>
                    </div>
                    <div class="stat-label">Total Subjects</div>
                </div>
            </div>
            <div class="dashboard-tabs">
                <button class="tab-button active" onclick="switchTab('faculty')" data-tab="faculty">
                    Faculty Members
                </button>
                <button class="tab-button" onclick="switchTab('courses')" data-tab="courses">
                    Courses
                </button>
                <button class="tab-button" onclick="switchTab('classes')" data-tab="classes">
                    Classes
                </button>
            </div>
            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search..." id="searchInput">
                <button class="search-btn" onclick="searchContent()">🔍</button>
            </div>
            <div class="tab-content active" id="faculty-content">
                <div class="faculty-grid" id="facultyGrid">
                    <?php if (empty($faculty_data)): ?>
                    <div class="empty-state">
                        <h3>No faculty members found</h3>
                        <p>No faculty members are currently assigned to the <?php echo htmlspecialchars($program); ?> program</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($faculty_data as $faculty): ?>
                    <div class="faculty-card" data-name="<?php echo htmlspecialchars($faculty['faculty_name']); ?>">
                        <div class="faculty-avatar"><?php echo getInitials($faculty['faculty_name']); ?></div>
                        <div class="faculty-name"><?php echo htmlspecialchars($faculty['faculty_name']); ?></div>   
                        
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
                            
                            <div class="faculty-actions">
                                <?php if (!empty($faculty['contact_email'])): ?>
                                <button class="action-btn primary" onclick="contactFaculty('<?php echo htmlspecialchars($faculty['contact_email']); ?>')">
                                    Email
                                </button>
                                <?php endif; ?>
                                <?php if (!empty($faculty['contact_phone'])): ?>
                                <button class="action-btn" onclick="callFaculty('<?php echo htmlspecialchars($faculty['contact_phone']); ?>')">
                                    Call
                                </button>
                                <?php endif; ?>
                                <button class="action-btn" onclick="viewSchedule(<?php echo $faculty['faculty_id']; ?>)">
                                    Schedule
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="add-card" data-modal="addFacultyModal">
                        <div class="add-card-icon">👨‍🏫</div>
                        <div class="add-card-title">Add Faculty Member</div>
                        <div class="add-card-subtitle">Register a new faculty member</div>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="courses-content">
                <?php if (empty($courses_data)): ?>
                    <div class="empty-state">
                        <h3>No courses found</h3>
                        <p>No courses are currently scheduled under the <?php echo htmlspecialchars($program); ?> program</p>
                    </div>
                <?php else: ?>
                    <div class="courses-grid">
                        <?php foreach ($courses_data as $course): ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                                    <div class="course-units"><?php echo htmlspecialchars($course['units']); ?> unit<?php echo $course['units'] > 1 ? 's' : ''; ?></div>
                                </div>
                                <div class="course-description">
                                    <?php echo htmlspecialchars($course['course_description']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="add-card add-card-course" data-modal="addCourseModal">
                        <div class="add-card-icon add-card-course-icon">📘</div>
                        <div class="add-card-title">Add Course</div>
                        <div class="add-card-subtitle">Create a new course entry</div>
                </div>
                    </div>
                <?php endif; ?>
                
            </div>

            <div class="tab-content" id="classes-content">
                <div class="classes-grid" id="classesGrid">
                    <?php if (empty($classes_data)): ?>
                        <div class="no-data">
                            <h3>No classes assigned</h3>
                            <p>No classes are currently under your supervision</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($classes_data as $class): ?>
                            <div class="class-card" data-name="<?php echo htmlspecialchars($class['class_name']); ?>" data-code="<?php echo htmlspecialchars($class['class_code']); ?>">
                                <div class="class-header">
                                    <div class="class-info">
                                        <div class="class-name"><?php echo htmlspecialchars($class['class_name']); ?></div>
                                        <div class="class-code"><?php echo htmlspecialchars($class['class_code']); ?></div>
                                        <div class="class-meta">
                                            Year <?php echo $class['year_level']; ?> • <?php echo $class['semester']; ?> Semester • <?php echo $class['academic_year']; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="class-stats">
                                    <div class="class-stat">
                                        <div class="class-stat-number"><?php echo $class['total_subjects']; ?></div>
                                        <div class="class-stat-label">Subjects</div>
                                    </div>
                                    <div class="class-stat">
                                        <div class="class-stat-number"><?php echo $class['assigned_faculty']; ?></div>
                                        <div class="class-stat-label">Faculty</div>
                                    </div>
                                </div>

                                <?php if (!empty($class_schedules[$class['class_id']])): ?>
                                    <div class="schedule-preview">
                                        <?php foreach (array_slice($class_schedules[$class['class_id']], 0, 3) as $schedule): ?>
                                            <div class="schedule-item">
                                                <div>
                                                    <div class="schedule-course"><?php echo htmlspecialchars($schedule['course_code']); ?></div>
                                                    <div style="font-size: 0.75rem; color: #888;">
                                                        <?php echo htmlspecialchars($schedule['faculty_name']); ?>
                                                    </div>
                                                </div>
                                                <div class="schedule-time">
                                                    <?php echo strtoupper($schedule['days']); ?><br>
                                                    <?php echo formatTime($schedule['time_start']); ?>-<?php echo formatTime($schedule['time_end']); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if (count($class_schedules[$class['class_id']]) > 3): ?>
                                            <div style="text-align: center; padding: 8px; font-size: 0.8rem; color: #666; font-style: italic;">
                                                +<?php echo count($class_schedules[$class['class_id']]) - 3; ?> more subjects
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data" style="padding: 20px;">
                                        <em>No schedules assigned</em>
                                    </div>
                                <?php endif; ?>

                                <div class="faculty-actions">
                                    <button class="action-btn primary" onclick="viewClassDetails(<?php echo $class['class_id']; ?>)">
                                        View Details
                                    </button>
                                    <button class="action-btn" onclick="manageSchedule(<?php echo $class['class_id']; ?>)">
                                        Manage Schedule
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="add-card" data-modal="addClassModal">
                        <div class="add-card-icon">🏫</div>
                        <div class="add-card-title">Add Class</div>
                        <div class="add-card-subtitle">Assign a new class group</div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <script>
        const facultySchedules = <?php echo json_encode($faculty_schedules); ?>;
        const facultyNames = <?php echo json_encode(array_column($faculty_data, 'faculty_name', 'faculty_id')); ?>;
    </script>
    <?php include 'assets/php/shared_modals.php'; ?>
    <script src="assets/js/shared_modals.js"></script>
    <script src="assets/js/program.js"></script>
</body>
</html>