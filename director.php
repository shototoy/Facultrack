<?php
require_once 'assets/php/common_utilities.php';
initializeSession();
$pdo = initializeDatabase();
validateUserSession('campus_director');

$user_id = $_SESSION['user_id'];
$director_name = $_SESSION['full_name'];

require_once 'assets/php/announcement_functions.php';
$announcements = fetchAnnouncements($pdo, $_SESSION['role'], 10);

$faculty_data = getAllFaculty($pdo);
$classes_data = getAllClasses($pdo);
$courses_data = getAllCourses($pdo);
$all_announcements = getAllAnnouncements($pdo);
$program_chairs = getProgramChairs($pdo);

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
                WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN 'Available'
                WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'Busy'
                ELSE 'Offline'
            END as status
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id
        WHERE f.is_active = TRUE
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaculTrack - Campus Director Dashboard</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'assets/php/feather_icons.php'; ?>
    <div class="main-container">
        <?php 
        $online_faculty = count(array_filter($faculty_data, function($faculty) {
            return $faculty['status'] === 'Available';
        }));
        
        $header_config = [
            'page_title' => 'FaculTrack - Campus Director',
            'page_subtitle' => 'Sultan Kudarat State University - Isulan Campus',
            'user_name' => $director_name,
            'user_role' => 'Campus Director',
            'user_details' => 'System Administrator',
            'announcements_count' => count($announcements),
            'announcements' => $announcements,
            'stats' => [
                ['label' => 'Faculty', 'value' => count($faculty_data)],
                ['label' => 'Classes', 'value' => count($classes_data)],
                ['label' => 'Courses', 'value' => count($courses_data)],
                ['label' => 'Online', 'value' => $online_faculty],
                ['label' => 'Announcements', 'value' => count($all_announcements)]
            ]
        ];
        include 'assets/php/page_header.php';
        ?>

        <div class="dashboard-tabs">
            <button class="tab-button active" onclick="switchTab('faculty')" data-tab="faculty">
                Faculty Members
            </button>
            <button class="tab-button" onclick="switchTab('classes')" data-tab="classes">
                Classes
            </button>
            <button class="tab-button" onclick="switchTab('courses')" data-tab="courses">
                Courses
            </button>
            <button class="tab-button" onclick="switchTab('announcements')" data-tab="announcements">
                Manage Announcements
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
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">Faculty Members</h3>
                    <div class="table-actions">
                        <button class="export-btn" onclick="exportData('faculty')" title="Export Faculty Data">
                            <svg class="feather feather-sm"><use href="#download"></use></svg> Export
                        </button>
                        <button class="add-btn" data-modal="addFacultyModal">
                            <svg class="feather feather-sm"><use href="#plus"></use></svg> Add Faculty Member
                        </button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Employee ID</th>
                            <th>Program</th>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faculty_data as $faculty): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($faculty['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($faculty['employee_id']); ?></td>
                                <td><?php echo htmlspecialchars($faculty['program']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($faculty['status']); ?>">
                                        <?php echo $faculty['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($faculty['current_location'] ?? 'Not Available'); ?></td>
                                <td><?php echo htmlspecialchars($faculty['contact_email'] ?? 'N/A'); ?></td>
                                <td>
                                    <button class="delete-btn" onclick="deleteEntity('delete_faculty', <?php echo $faculty['faculty_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-content" id="classes-content">
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">Classes</h3>
                    <div class="table-actions">
                        <button class="export-btn" onclick="exportData('classes')" title="Export Classes Data">
                            <svg class="feather feather-sm"><use href="#download"></use></svg> Export
                        </button>
                        <button class="add-btn" data-modal="addClassModal">
                            <svg class="feather feather-sm"><use href="#plus"></use></svg> Add Class
                        </button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Class Code</th>
                            <th>Class Name</th>
                            <th>Year Level</th>
                            <th>Semester</th>
                            <th>Academic Year</th>
                            <th>Program Chair</th>
                            <th>Subjects</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes_data as $class): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class['class_code']); ?></td>
                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                <td><?php echo $class['year_level']; ?></td>
                                <td><?php echo htmlspecialchars($class['semester']); ?></td>
                                <td><?php echo htmlspecialchars($class['academic_year']); ?></td>
                                <td><?php echo htmlspecialchars($class['program_chair_name'] ?? 'Unassigned'); ?></td>
                                <td><?php echo $class['total_subjects']; ?></td>
                                <td>
                                    <button class="delete-btn" onclick="deleteEntity('delete_class', <?php echo $class['class_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-content" id="courses-content">
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">Courses</h3>
                    <div class="table-actions">
                        <button class="export-btn" onclick="exportData('courses')" title="Export Courses Data">
                            <svg class="feather feather-sm"><use href="#download"></use></svg> Export
                        </button>
                        <button class="add-btn" data-modal="addCourseModal">
                            <svg class="feather feather-sm"><use href="#plus"></use></svg> Add Course
                        </button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Description</th>
                            <th>Units</th>
                            <th>Times Scheduled</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses_data as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($course['course_description']); ?></td>
                                <td><?php echo $course['units']; ?></td>
                                <td><?php echo $course['times_scheduled']; ?></td>
                                <td>
                                    <button class="delete-btn" onclick="deleteEntity('delete_course', <?php echo $course['course_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-content" id="announcements-content">
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">Announcements</h3>
                    <div class="table-actions">
                        <button class="export-btn" onclick="exportData('announcements')" title="Export Announcements Data">
                            <svg class="feather feather-sm"><use href="#download"></use></svg> Export
                        </button>
                        <button class="add-btn" data-modal="addAnnouncementModal">
                            <svg class="feather feather-sm"><use href="#plus"></use></svg> Add Announcement
                        </button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Content</th>
                            <th>Priority</th>
                            <th>Target Audience</th>
                            <th>Created By</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_announcements as $announcement): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                                <td><?php echo htmlspecialchars(substr($announcement['content'], 0, 50)) . '...'; ?></td>
                                <td>
                                    <span class="status-badge priority-<?php echo $announcement['priority']; ?>">
                                        <?php echo strtoupper($announcement['priority']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($announcement['target_audience']); ?></td>
                                <td><?php echo htmlspecialchars($announcement['created_by_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></td>
                                <td>
                                    <button class="delete-btn" onclick="deleteEntity('delete_announcement', <?php echo $announcement['announcement_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php 
    $GLOBALS['program_chairs'] = $program_chairs;
    include 'assets/php/shared_modals.php'; 
    ?>
    
    <script src="assets/js/shared_modals.js"></script>
    <script src="assets/js/director.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const programChairSelect = document.querySelector('select[name="program_chair_id"]');
            if (programChairSelect) {
                const programChairs = <?php echo json_encode($program_chairs); ?>;
                programChairSelect.innerHTML = '<option value="">Select Program Chair (Optional)</option>';

                programChairs.forEach(chair => {
                    const option = document.createElement('option');
                    option.value = chair.user_id;
                    option.textContent = `${chair.full_name} (${chair.program || 'Unassigned'})`;
                    programChairSelect.appendChild(option);
                });
            }
        });
        
        // Ensure toggleSearch is available immediately
        if (typeof window.toggleSearch !== 'function') {
            window.toggleSearch = function() {
                const container = document.getElementById('searchContainer');
                const searchInput = document.getElementById('searchInput');
                
                if (!container || !searchInput) {
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