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

require_once 'assets/php/announcement_functions.php';
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

if (isset($_POST['action']) && $_POST['action'] === 'get_curriculum_assignment_data') {
    try {
        $course_code = $_POST['course_code'];
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

if (isset($_POST['action']) && $_POST['action'] === 'get_curriculum_assignment_data_with_classes') {
    try {
        $course_code = $_POST['course_code'];
        $curriculum_query = "
            SELECT cur.curriculum_id, cur.year_level, cur.semester, cur.academic_year,
                   GROUP_CONCAT(DISTINCT c.class_code SEPARATOR ', ') as class_names
            FROM curriculum cur
            LEFT JOIN classes c ON c.year_level = cur.year_level 
                                AND c.semester = cur.semester 
                                AND c.academic_year = cur.academic_year
                                AND c.is_active = TRUE
            WHERE cur.course_code = ? AND cur.is_active = TRUE
            GROUP BY cur.curriculum_id, cur.year_level, cur.semester, cur.academic_year
            ORDER BY cur.year_level, cur.semester";
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

if (isset($_POST['action']) && $_POST['action'] === 'assign_course_to_curriculum') {
    try {
        $course_code = $_POST['course_code'];
        $year_level = $_POST['year_level'];
        $semester = $_POST['semester'];
        $academic_year = $_POST['academic_year'];
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

if (isset($_POST['action']) && $_POST['action'] === 'get_classes_for_course') {
    try {
        $course_code = $_POST['course_code'];
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

if (isset($_POST['action']) && $_POST['action'] === 'delete_course') {
    try {
        $course_code = $_POST['course_code'];
        
        $pdo->beginTransaction();
        $schedule_check = "SELECT COUNT(*) as count FROM schedules WHERE course_code = ? AND is_active = TRUE";
        $stmt = $pdo->prepare($schedule_check);
        $stmt->execute([$course_code]);
        $active_schedules = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($active_schedules > 0) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Cannot delete course: It is currently assigned to faculty schedules. Please remove all schedule assignments first.']);
            exit;
        }
        
        $curriculum_update = "UPDATE curriculum SET is_active = FALSE WHERE course_code = ?";
        $stmt = $pdo->prepare($curriculum_update);
        $stmt->execute([$course_code]);
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

</head>
<body>
    <?php include 'assets/php/feather_icons.php'; ?>
    <div class="main-container">

        <div class="content-wrapper" id="contentWrapper">
            <?php 
            $online_count = array_reduce($faculty_data, function($count, $faculty) {
                return $count + ($faculty['status'] === 'available' ? 1 : 0);
            }, 0);
            $total_schedules = array_reduce($classes_data, function($count, $class) {
                return $count + $class['total_subjects'];
            }, 0);
            
            $header_config = [
                'page_title' => 'FaculTrack - Program Chair',
                'page_subtitle' => 'Sultan Kudarat State University - Isulan Campus',
                'user_name' => $program_chair_name,
                'user_role' => 'Program Chair',
                'user_details' => $program,
                'announcements_count' => count($announcements),
                'announcements' => $announcements,
                'stats' => [
                    ['label' => 'Faculty', 'value' => count($faculty_data)],
                    ['label' => 'Classes', 'value' => count($classes_data)],
                    ['label' => 'Online', 'value' => $online_count],
                    ['label' => 'Subjects', 'value' => $total_schedules]
                ]
            ];
            include 'assets/php/page_header.php';
            ?>
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
                <div class="search-bar">
                    <div class="search-container collapsed" id="searchContainer">
                        <input type="text" class="search-input" placeholder="Search..." id="searchInput">
                        <button class="search-toggle" onclick="toggleSearch()">
                            <svg class="feather"><use href="#search"></use></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="tab-content active" id="faculty-content">
                <div class="faculty-grid" id="facultyGrid">
                    <div class="add-card add-card-first" data-modal="addFacultyModal">
                        <div class="add-card-icon">
                            <svg class="feather"><use href="#user-plus"></use></svg>
                        </div>
                        <div class="add-card-title">Add Faculty Member</div>
                        <div class="add-card-subtitle">Register a new faculty member</div>
                    </div>
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
                        <div class="add-card add-card-course add-card-first" data-modal="addCourseModal">
                            <div class="add-card-icon add-card-course-icon">
                                <svg class="feather"><use href="#book-open"></use></svg>
                            </div>
                            <div class="add-card-title">Add Course</div>
                            <div class="add-card-subtitle">Create a new course entry</div>
                        </div>
                        <?php foreach ($courses_data as $course): ?>
                            <div class="course-card" data-course="<?php echo htmlspecialchars($course['course_code']); ?>">
                                <div class="course-card-content">
                                    <div class="course-card-default-content">
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

                                    <div class="course-details-overlay">
                                        <div class="overlay-header">
                                            <h4>Current Assignments</h4>
                                        </div>
                                        <div class="overlay-body">
                                            <div class="assignments-preview" style="padding: 12px;">
                                                <div class="loading-assignments">Loading assignments...</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button class="course-details-toggle" onclick="toggleCourseDetailsOverlay(this, '<?php echo htmlspecialchars($course['course_code']); ?>')">
                                    View Assignments
                                    <span class="arrow">▼</span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            </div>

            <div class="tab-content" id="classes-content">
                <div class="classes-grid" id="classesGrid">
                    <div class="add-card add-card-first" data-modal="addClassModal">
                        <div class="add-card-icon">
                            <svg class="feather"><use href="#users"></use></svg>
                        </div>
                        <div class="add-card-title">Add Class</div>
                        <div class="add-card-subtitle">Assign a new class group</div>
                    </div>
                    <?php if (empty($classes_data)): ?>
                        <div class="no-data">
                            <h3>No classes assigned</h3>
                            <p>No classes are currently under your supervision</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($classes_data as $class): ?>
                            <div class="class-card" data-name="<?php echo htmlspecialchars($class['class_name']); ?>" data-code="<?php echo htmlspecialchars($class['class_code']); ?>">
                                <div class="class-card-content">
                                    <div class="class-card-default-content">
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
                                                    <div style="font-size: 2rem; margin-bottom: 10px;">
                                                        <svg class="feather feather-xl"><use href="#calendar"></use></svg>
                                                    </div>
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
        
        // Ensure toggleSearch is available immediately
        if (typeof window.toggleSearch !== 'function') {
            console.log('toggleSearch not found, defining fallback');
            window.toggleSearch = function() {
                console.log('Fallback toggleSearch called');
                const container = document.getElementById('searchContainer');
                const searchInput = document.getElementById('searchInput');
                
                if (!container || !searchInput) {
                    console.log('Search elements not found in fallback');
                    return;
                }
                
                if (container.classList.contains('collapsed')) {
                    container.classList.remove('collapsed');
                    container.classList.add('expanded');
                    setTimeout(() => searchInput.focus(), 400);
                } else {
                    container.classList.remove('expanded');
                    container.classList.add('collapsed');
                    searchInput.blur();
                }
            };
        }
    </script>
</body>
</html>