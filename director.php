<?php
require_once 'assets/php/common_utilities.php';
initializeSession();
$pdo = initializeDatabase();
validateUserSession('campus_director');
$user_id = $_SESSION['user_id'];
$director_name = $_SESSION['full_name'];
require_once 'assets/php/announcement_functions.php';
$announcements = fetchAnnouncements($pdo, $_SESSION['role'], 10);
$faculty_data = [];
$classes_data = [];
$courses_data = [];
$all_announcements = [];
$program_chairs = [];
require_once 'assets/php/polling_api.php';
if (!isset($_GET['action']) && !isset($_POST['action'])) {
    $faculty_data = getAllFaculty($pdo);
    $classes_data = getAllClasses($pdo);
    $courses_data = getAllCourses($pdo);
    $all_announcements = getAllAnnouncements($pdo);
    $program_chairs = getProgramChairs($pdo);
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
                            <th class="name-column">Name</th>
                            <th class="status-column">Status</th>
                            <th class="location-column">Location</th>
                            <th class="actions-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faculty_data as $faculty): ?>
                            <tr class="expandable-row" onclick="toggleRowExpansion(this)" data-faculty-id="<?php echo $faculty['faculty_id']; ?>">
                                <td class="name-column"><?php echo htmlspecialchars($faculty['full_name']); ?></td>
                                <td class="status-column">
                                    <span class="status-badge status-<?php echo strtolower($faculty['status']); ?>">
                                        <?php echo $faculty['status']; ?>
                                    </span>
                                </td>
                                <td class="location-column"><?php echo htmlspecialchars($faculty['current_location'] ?? 'Not Available'); ?></td>
                                <td class="actions-column">
                                    <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_faculty', <?php echo $faculty['faculty_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <tr class="expansion-row" id="faculty-expansion-<?php echo $faculty['faculty_id']; ?>" style="display: none;">
                                <td colspan="4" class="expansion-content">
                                    <div class="expanded-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Employee ID:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($faculty['employee_id']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Program:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($faculty['program']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Contact Email:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($faculty['contact_email'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Phone:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($faculty['contact_phone'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
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
                        <button class="add-btn" data-modal="updateSemesterModal" style="background-color: #4CAF50;">
                            <svg class="feather feather-sm"><use href="#refresh-cw"></use></svg> Update Semester
                        </button>
                        <button class="add-btn" data-modal="addClassModal">
                            <svg class="feather feather-sm"><use href="#plus"></use></svg> Add Class
                        </button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="id-column">Class Code</th>
                            <th class="name-column">Class Name</th>
                            <th class="id-column">Year Level</th>
                            <th class="date-column">Academic Year</th>
                            <th class="actions-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes_data as $class): ?>
                            <tr class="expandable-row" onclick="toggleRowExpansion(this)" data-class-id="<?php echo $class['class_id']; ?>">
                                <td class="id-column"><?php echo htmlspecialchars($class['class_code']); ?></td>
                                <td class="name-column"><?php echo htmlspecialchars($class['class_name']); ?></td>
                                <td class="id-column"><?php echo $class['year_level']; ?></td>
                                <td class="date-column"><?php echo htmlspecialchars($class['academic_year']); ?></td>
                                <td class="actions-column">
                                    <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_class', <?php echo $class['class_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <tr class="expansion-row" id="class-expansion-<?php echo $class['class_id']; ?>" style="display: none;">
                                <td colspan="5" class="expansion-content">
                                    <div class="expanded-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Semester:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($class['semester']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Program Chair:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($class['program_chair_name'] ?? 'Unassigned'); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Total Subjects:</span>
                                            <span class="detail-value"><?php echo $class['total_subjects']; ?></span>
                                        </div>
                                    </div>
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
                            <th class="id-column">Course Code</th>
                            <th class="description-column">Course Description</th>
                            <th class="id-column">Units</th>
                            <th class="id-column">Times Scheduled</th>
                            <th class="actions-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses_data as $course): ?>
                            <tr>
                                <td class="id-column"><?php echo htmlspecialchars($course['course_code']); ?></td>
                                <td class="description-column"><?php echo htmlspecialchars($course['course_description']); ?></td>
                                <td class="id-column"><?php echo $course['units']; ?></td>
                                <td class="id-column"><?php echo $course['times_scheduled']; ?></td>
                                <td class="actions-column">
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
                            <th class="name-column">Title</th>
                            <th class="status-column">Priority</th>
                            <th class="program-column">Target Audience</th>
                            <th class="actions-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_announcements as $announcement): ?>
                            <tr class="expandable-row" onclick="toggleRowExpansion(this)" data-announcement-id="<?php echo $announcement['announcement_id']; ?>">
                                <td class="name-column"><?php echo htmlspecialchars($announcement['title']); ?></td>
                                <td class="status-column">
                                    <span class="status-badge priority-<?php echo $announcement['priority']; ?>">
                                        <?php echo strtoupper($announcement['priority']); ?>
                                    </span>
                                </td>
                                <td class="program-column"><?php echo htmlspecialchars($announcement['target_audience']); ?></td>
                                <td class="actions-column">
                                    <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_announcement', <?php echo $announcement['announcement_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <tr class="expansion-row" id="announcement-expansion-<?php echo $announcement['announcement_id']; ?>" style="display: none;">
                                <td colspan="4" class="expansion-content">
                                    <div class="expanded-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Content:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($announcement['content']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Created By:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($announcement['created_by_name']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Created Date:</span>
                                            <span class="detail-value"><?php echo date('M d, Y \a\t g:i A', strtotime($announcement['created_at'])); ?></span>
                                        </div>
                                    </div>
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
    <script>
        window.userRole = 'campus_director';
    </script>
    <script src="assets/js/polling_config.js"></script>
    <script src="assets/js/toast_manager.js"></script>
    <script src="assets/js/live_polling.js"></script>
    <script src="assets/js/shared_functions.js"></script>
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
        document.addEventListener('DOMContentLoaded', function() {
            const allExpansionRows = document.querySelectorAll('.expansion-row');
            allExpansionRows.forEach(row => {
                row.style.display = 'none';
            });
            const allExpandableRows = document.querySelectorAll('.expandable-row');
            allExpandableRows.forEach(row => {
                row.classList.remove('expanded');
            });
        });
        window.toggleRowExpansion = function(row) {
            const facultyId = row.getAttribute('data-faculty-id');
            const classId = row.getAttribute('data-class-id');
            const announcementId = row.getAttribute('data-announcement-id');
            let expansionRowId;
            if (facultyId) {
                expansionRowId = 'faculty-expansion-' + facultyId;
            } else if (classId) {
                expansionRowId = 'class-expansion-' + classId;
            } else if (announcementId) {
                expansionRowId = 'announcement-expansion-' + announcementId;
            }
            const expansionRow = document.getElementById(expansionRowId);
            if (!expansionRow) {
                return;
            }
            const isExpanded = expansionRow.style.display === 'table-row';
            const currentTable = row.closest('table');
            if (currentTable) {
                currentTable.querySelectorAll('.expansion-row').forEach(otherRow => {
                    if (otherRow !== expansionRow) {
                        otherRow.style.display = 'none';
                        const otherMainRow = otherRow.previousElementSibling;
                        if (otherMainRow) {
                            otherMainRow.classList.remove('expanded');
                        }
                    }
                });
            }
            if (isExpanded) {
                expansionRow.classList.remove('expanding', 'expanded');
                expansionRow.classList.add('collapsing');
                row.classList.remove('expanded');
                setTimeout(() => {
                    expansionRow.style.display = 'none';
                    expansionRow.classList.remove('collapsing');
                }, 400);
            } else {
                expansionRow.style.display = 'table-row';
                expansionRow.classList.remove('collapsing');
                expansionRow.offsetHeight;
                expansionRow.classList.add('expanding');
                row.classList.add('expanded');
                setTimeout(() => {
                    expansionRow.classList.remove('expanding');
                    expansionRow.classList.add('expanded');
                }, 400);
            }
        };
    </script>
</body>
</html>