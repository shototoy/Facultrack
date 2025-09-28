<?php
require_once 'assets/php/common_utilities.php';
initializeSession();
$pdo = initializeDatabase();
validateUserSession('campus_director');

$user_id = $_SESSION['user_id'];
$director_name = $_SESSION['full_name'];

require_once 'assets/php/fetch_announcements.php';
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
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .table-container {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 0px 0px 12px 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9),
                        0 0 0 1px rgba(46, 125, 50, 0.08);
            overflow: hidden;
            border: 1px solid rgba(222, 226, 230, 0.3);
            backdrop-filter: blur(10px);
            position: relative;
        }

        .table-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.3), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid rgba(222, 226, 230, 0.5);
            backdrop-filter: blur(10px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8),
                        0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .table-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
            position: relative;
        }

        .table-title::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #2E7D32, #4CAF50);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .table-container:hover .table-title::after {
            width: 100%;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(222, 226, 230, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .data-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600;
            color: #495057;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8),
                        0 1px 3px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .data-table th::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.05), transparent);
            transition: left 0.6s;
        }

        .data-table th:hover::before {
            left: 100%;
        }

        .data-table tr {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .data-table tr::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, rgba(46, 125, 50, 0.1), rgba(76, 175, 80, 0.05));
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .data-table tr:hover {
            background: linear-gradient(90deg, rgba(248, 249, 250, 0.8), rgba(233, 236, 239, 0.8));
            transform: translateX(2px);
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.1),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }

        .data-table tr:hover::before {
            width: 4px;
        }

        .data-table tr:hover td {
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }

        .data-table .item-checkbox {
            margin-right: 8px;
            transform: scale(1.1);
            filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.1));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .data-table .item-checkbox:hover {
            transform: scale(1.2);
            filter: drop-shadow(0 2px 6px rgba(46, 125, 50, 0.3));
        }

        .bulk-actions {
            display: none;
            background: linear-gradient(135deg, rgba(227, 242, 253, 0.9), rgba(187, 222, 251, 0.9));
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            box-shadow: 0 4px 15px rgba(25, 118, 210, 0.15),
                        inset 0 1px 0 rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(25, 118, 210, 0.2);
            backdrop-filter: blur(10px);
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bulk-actions button {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            margin-right: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            box-shadow: 0 3px 10px rgba(25, 118, 210, 0.3),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .bulk-actions button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .bulk-actions button:hover::before {
            left: 100%;
        }

        .bulk-actions button:hover {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            box-shadow: 0 6px 20px rgba(25, 118, 210, 0.4),
                        inset 0 2px 0 rgba(255, 255, 255, 0.3);
            transform: translateY(-2px) scale(1.05);
        }

        .bulk-actions button:active {
            transform: translateY(-1px) scale(1.02);
            box-shadow: 0 3px 12px rgba(25, 118, 210, 0.3),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1>FaculTrack - Campus Director</h1>
            <p>Sultan Kudarat State University - Isulan Campus</p>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($director_name); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-number" id="total-faculty"><?php echo count($faculty_data); ?></div>
                <div class="stat-label">Total Faculty</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-classes"><?php echo count($classes_data); ?></div>
                <div class="stat-label">Total Classes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-courses"><?php echo count($courses_data); ?></div>
                <div class="stat-label">Total Courses</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="active-announcements"><?php echo count($all_announcements); ?></div>
                <div class="stat-label">Active Announcements</div>
            </div>
        </div>

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
        </div>

        <div class="tab-content active" id="faculty-content">
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">Faculty Members</h3>
                    <div class="table-actions">
                        <button class="export-btn" onclick="exportData('faculty')" title="Export Faculty Data">📊 Export</button>
                        <button class="add-btn" data-modal="addFacultyModal">➕ Add Faculty Member</button>
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
                        <button class="export-btn" onclick="exportData('classes')" title="Export Classes Data">📊 Export</button>
                        <button class="add-btn" data-modal="addClassModal">➕ Add Class</button>
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
                        <button class="export-btn" onclick="exportData('courses')" title="Export Courses Data">📊 Export</button>
                        <button class="add-btn" data-modal="addCourseModal">➕ Add Course</button>
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
                        <button class="export-btn" onclick="exportData('announcements')" title="Export Announcements Data">📊 Export</button>
                        <button class="add-btn" data-modal="addAnnouncementModal">➕ Add Announcement</button>
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
    </script>
</body>
</html>