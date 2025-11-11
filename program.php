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
               COUNT(DISTINCT curr.course_code) as total_subjects,
               COUNT(DISTINCT s.faculty_id) as assigned_faculty
        FROM classes c
        JOIN users u ON c.user_id = u.user_id
        LEFT JOIN curriculum curr ON c.year_level = curr.year_level 
                                  AND c.semester = curr.semester 
                                  AND c.academic_year = curr.academic_year
                                  AND curr.is_active = TRUE
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
        LEFT JOIN schedules s ON c.course_code = s.course_code AND s.class_id IN ({$in_clause['placeholders']})
        WHERE c.is_active = TRUE
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

if (isset($_POST['action']) && $_POST['action'] === 'get_courses_and_classes') {
    try {
        $courses_query = "SELECT course_code, course_description, units FROM courses WHERE is_active = TRUE ORDER BY course_code";
        $courses_stmt = $pdo->prepare($courses_query);
        $courses_stmt->execute();
        $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $classes_query = "SELECT class_id, class_code, class_name, year_level FROM classes WHERE program_chair_id = ? AND is_active = TRUE ORDER BY year_level, class_name";
        $classes_stmt = $pdo->prepare($classes_query);
        $classes_stmt->execute([$user_id]);
        $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'courses' => $courses,
            'classes' => $classes
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch data: ' . $e->getMessage()
        ]);
    }
    exit;
}


if (isset($_POST['action']) && $_POST['action'] === 'assign_course_load') {
    try {
        $faculty_id = $_POST['faculty_id'];
        $course_code = $_POST['course_code']; 
        $class_id = $_POST['class_id'];
        $days = $_POST['days']; 
        $time_start = $_POST['time_start'];
        $time_end = $_POST['time_end'];
        $room = $_POST['room'] ?? null;
        
        $conflict_query = "SELECT COUNT(*) as conflicts FROM schedules 
                          WHERE faculty_id = ? AND time_start = ? AND days = ? AND is_active = TRUE";
        $conflict_stmt = $pdo->prepare($conflict_query);
        $conflict_stmt->execute([$faculty_id, $time_start, $days]);
        $conflicts = $conflict_stmt->fetch(PDO::FETCH_ASSOC)['conflicts'];
        
        if ($conflicts > 0) {
            echo json_encode(['success' => false, 'message' => 'Time conflict detected']);
            exit;
        }
        
        $insert_query = "INSERT INTO schedules (faculty_id, course_code, class_id, days, time_start, time_end, room, semester, academic_year, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, '1st', '2024-2025', TRUE)";
        $stmt = $pdo->prepare($insert_query);
        
        if ($stmt->execute([$faculty_id, $course_code, $class_id, $days, $time_start, $time_end, $room])) {
            echo json_encode(['success' => true, 'message' => 'Course assigned successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to assign course']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Get curriculum assignment data
if (isset($_POST['action']) && $_POST['action'] === 'get_curriculum_assignment_data') {
    try {
        $course_code = $_POST['course_code'];
        
        // Get existing curriculum assignments for this course
        $curriculum_query = "
            SELECT curriculum_id, year_level, semester, academic_year
            FROM curriculum 
            WHERE course_code = ? AND is_active = TRUE
            ORDER BY year_level, semester";
        $stmt = $pdo->prepare($curriculum_query);
        $stmt->execute([$course_code]);
        $existingAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'existingAssignments' => $existingAssignments
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Assign course to curriculum
if (isset($_POST['action']) && $_POST['action'] === 'assign_course_to_curriculum') {
    try {
        $course_code = $_POST['course_code'];
        $year_level = $_POST['year_level'];
        $semester = $_POST['semester'];
        $academic_year = $_POST['academic_year'];
        
        // Check if this assignment already exists
        $check_query = "SELECT COUNT(*) as count FROM curriculum 
                       WHERE course_code = ? AND year_level = ? AND semester = ? AND academic_year = ? AND is_active = TRUE";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$course_code, $year_level, $semester, $academic_year]);
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if ($exists) {
            echo json_encode(['success' => false, 'message' => 'This course is already assigned to that year level and semester']);
            exit;
        }
        
        $insert_query = "INSERT INTO curriculum (course_code, year_level, semester, academic_year, program_chair_id, is_active) 
                        VALUES (?, ?, ?, ?, ?, TRUE)";
        $stmt = $pdo->prepare($insert_query);
        
        if ($stmt->execute([$course_code, $year_level, $semester, $academic_year, $user_id])) {
            echo json_encode(['success' => true, 'message' => 'Course assigned to curriculum successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to assign course to curriculum']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Remove curriculum assignment
if (isset($_POST['action']) && $_POST['action'] === 'remove_curriculum_assignment') {
    try {
        $curriculum_id = $_POST['curriculum_id'];
        
        $update_query = "UPDATE curriculum SET is_active = FALSE WHERE curriculum_id = ?";
        $stmt = $pdo->prepare($update_query);
        
        if ($stmt->execute([$curriculum_id])) {
            echo json_encode(['success' => true, 'message' => 'Course removed from curriculum successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove course from curriculum']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Get courses and classes data for faculty course load assignment
if (isset($_POST['action']) && $_POST['action'] === 'get_courses_and_classes') {
    try {
        // Get courses for this program chair's classes
        $courses = getProgramCourses($pdo, $class_ids);
        
        // Get classes for this program chair
        $classes = getProgramClasses($pdo, $user_id);
        
        echo json_encode([
            'success' => true,
            'courses' => $courses,
            'classes' => $classes
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Get classes that have a specific course in their curriculum
if (isset($_POST['action']) && $_POST['action'] === 'get_classes_for_course') {
    try {
        $course_code = $_POST['course_code'];
        
        // Get classes that have this course in their curriculum
        $classes_query = "
            SELECT DISTINCT c.class_id, c.class_code, c.class_name, c.year_level, c.semester
            FROM classes c
            JOIN curriculum cur ON c.year_level = cur.year_level 
                                AND c.semester = cur.semester 
                                AND c.academic_year = cur.academic_year
            WHERE cur.course_code = ? AND cur.is_active = TRUE AND c.is_active = TRUE 
            AND c.program_chair_id = ?
            ORDER BY c.year_level, c.class_name";
        $stmt = $pdo->prepare($classes_query);
        $stmt->execute([$course_code, $user_id]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'classes' => $classes
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Delete course
if (isset($_POST['action']) && $_POST['action'] === 'delete_course') {
    try {
        $course_code = $_POST['course_code'];
        
        // Begin transaction to ensure data consistency
        $pdo->beginTransaction();
        
        // Check if course is currently being used in schedules
        $schedule_check = "SELECT COUNT(*) as count FROM schedules WHERE course_code = ? AND is_active = TRUE";
        $stmt = $pdo->prepare($schedule_check);
        $stmt->execute([$course_code]);
        $active_schedules = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($active_schedules > 0) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Cannot delete course: It is currently assigned to faculty schedules. Please remove all schedule assignments first.']);
            exit;
        }
        
        // Deactivate curriculum assignments
        $curriculum_update = "UPDATE curriculum SET is_active = FALSE WHERE course_code = ?";
        $stmt = $pdo->prepare($curriculum_update);
        $stmt->execute([$course_code]);
        
        // Deactivate the course
        $course_update = "UPDATE courses SET is_active = FALSE WHERE course_code = ?";
        $stmt = $pdo->prepare($course_update);
        $stmt->execute([$course_code]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Course deleted successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaculTrack - Program Chair Dashboard</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/scheduling.css">
    <style>
        /* Program-specific class and course layouts */
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .class-card {
            background: white;
            border-radius: 12px;
            padding: 20px 20px 50px 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left: 4px solid var(--text-green-secondary);
            transition: transform 0.3s ease;
            position: relative;
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
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .class-code {
            color: var(--text-green-secondary);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .class-meta {
            font-size: 0.85rem;
            color: var(--text-secondary);
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
            color: var(--text-green-secondary);
        }

        .class-stat-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
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
            color: var(--text-primary);
            font-size: 2.25rem;
        }

        .course-units {
            color: var(--text-secondary);
            font-size: 1.2rem;
        }

        .course-description {
            font-size: 1rem;
            color: var(--text-primary);
        }

        .schedule-preview {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .schedule-preview {
            padding: 0;
            margin: 0;
        }

        .schedule-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 8px 12px;
            font-size: 0.85rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .schedule-item:last-child {
            border-bottom: none;
        }

        .schedule-course {
            font-weight: 500;
            color: var(--text-primary);
        }

        .schedule-time {
            color: var(--text-secondary);
            font-size: 0.8rem;
            text-align: right;
            min-width: 140px;
            flex-shrink: 0;
            line-height: 1.3;
        }

        .schedule-course-info {
            flex: 1;
            margin-right: 15px;
        }

        .add-card {
            background: linear-gradient(135deg, #E8F5E8 0%, #F1F8E9 100%);
            border: 2px dashed var(--text-green-secondary);
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
            border-color: var(--text-green-primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.15);
        }

        .add-card-icon {
            font-size: 3rem;
            color: var(--text-green-secondary);
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .add-card-course-icon {
            font-size: 1.5rem;
            margin-bottom: 0px;
        }

        .add-card:hover .add-card-icon {
            color: var(--text-green-primary);
            transform: scale(1.1);
        }

        .add-card-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--text-green-primary);
        }

        .add-card-subtitle {
            font-size: 0.9rem;
            color: var(--text-green-secondary);
            opacity: 0.8;
        }

        .class-details-toggle {
            position: absolute;
            bottom: 10px;
            left: 10px;
            right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.8rem;
            color: var(--text-secondary);
            z-index: 200;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .class-details-toggle:hover {
            background: #f8f9fa;
            border-color: var(--text-green-secondary);
            color: var(--text-green-secondary);
        }

        .class-details-toggle .arrow {
            margin-left: 8px;
        }

        .class-card-content {
            position: relative;
            overflow: hidden;
        }

        .class-details-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: white;
            border-radius: 12px 12px 0 0;
            z-index: 100;
            transform: translateY(-100%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid var(--text-green-secondary);
            border-right: 1px solid #e0e0e0;
            border-top: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            margin: 0;
            box-sizing: border-box;
        }

        .class-details-overlay.show {
            transform: translateY(0);
        }

        .class-details-overlay .overlay-header {
            padding: 8px 12px;
            border-bottom: 1px solid #e0e0e0;
            background: var(--faculty-card-bg);
            border-radius: 12px 12px 0 0;
            flex-shrink: 0;
        }

        .class-details-overlay .overlay-header h4 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .class-details-overlay .overlay-body {
            flex: 1;
            padding: 0;
            margin: 0;
            overflow-y: auto;
        }

        .sched-course-code {
            color: #2c3e50;
        }

        .schedule-table td {
            text-align: center;
            vertical-align: middle;
            padding: 8px 4px;
            font-size: 0.8rem;
        }

        .course-info {
            background: linear-gradient(135deg, rgba(240, 244, 255, 0.9), rgba(227, 242, 253, 0.9));
            border-radius: 8px;
            padding: 8px;
            margin-bottom: 8px;
            font-size: 0.85rem;
            border-left: 3px solid #2196F3;
            clear: both;
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.15),
                        inset 0 1px 0 rgba(255, 255, 255, 0.8);
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }

        .course-info:last-child {
            margin-bottom: 0;
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

        .room-info {
            font-size: 0.7rem;
            color: #666;
            margin-top: 2px;
            font-style: italic;
        }

        .time-cell {
            font-weight: 500;
            background: #f8f9fa;
            color: #333;
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
                                <button class="action-btn" onclick="viewCourseLoad(<?php echo $faculty['faculty_id']; ?>)">
                                    Course Load
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
                                <div class="course-actions">
                                    <button class="action-btn primary" onclick="assignCourseToYearLevel('<?php echo htmlspecialchars($course['course_code']); ?>')">
                                        Assign to Year Level
                                    </button>
                                    <button class="action-btn danger" onclick="deleteCourse('<?php echo htmlspecialchars($course['course_code']); ?>')">
                                        Delete
                                    </button>
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
                                <div class="class-card-content">
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

                                    <div class="class-details-overlay">
                                        <div class="overlay-header">
                                            <h4>Schedule</h4>
                                        </div>
                                        <div class="overlay-body">
                                            <?php if (!empty($class_schedules[$class['class_id']])): ?>
                                                <div class="schedule-preview">
                                                    <?php foreach ($class_schedules[$class['class_id']] as $schedule): ?>
                                                        <div class="schedule-item">
                                                            <div class="schedule-course-info">
                                                                <div class="schedule-course"><?php echo htmlspecialchars($schedule['course_code']); ?></div>
                                                                <div style="font-size: 0.75rem; color: #888;">
                                                                    <?php echo htmlspecialchars($schedule['faculty_name']); ?>
                                                                </div>
                                                            </div>
                                                            <div class="schedule-time">
                                                                <strong><?php echo strtoupper($schedule['days']); ?></strong><br>
                                                                <?php echo formatTime($schedule['time_start']); ?>-<?php echo formatTime($schedule['time_end']); ?>
                                                                <?php if (!empty($schedule['room'])): ?>
                                                                    <br><span style="color: #666; font-size: 0.75rem;">Room: <?php echo htmlspecialchars($schedule['room']); ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="no-data" style="padding: 20px; text-align: center;">
                                                    <div style="font-size: 2rem; margin-bottom: 10px;">📅</div>
                                                    <p style="color: #666; margin: 0;">No schedules assigned yet</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <button class="class-details-toggle" onclick="toggleClassDetailsOverlay(this)">
                                    View Schedule Details
                                    <span class="arrow">▼</span>
                                </button>
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
    <?php include 'assets/php/shared_modals.php'; ?>
    <script src="assets/js/shared_modals.js"></script>
    <script src="assets/js/program.js"></script>
    <script>
        const facultySchedules = <?php echo json_encode($faculty_schedules); ?>;
        const facultyNames = <?php echo json_encode(array_column($faculty_data, 'faculty_name', 'faculty_id')); ?>;
    </script>
</body>
</html>