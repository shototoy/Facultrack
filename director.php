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
    $programs_data = getAllPrograms($pdo);
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
            <button class="tab-button" onclick="switchTab('announcements')" data-tab="announcements">
                Announcements
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
                                <td class="location-column"><?php echo htmlspecialchars($faculty['current_location'] ?? 'No Location'); ?></td>
                                <td class="actions-column">
                                    <button class="action-btn view-btn" onclick="openIFTLModal(<?php echo $faculty['faculty_id']; ?>, '<?php echo htmlspecialchars($faculty['full_name']); ?>')" title="View IFTL">
                                        <svg class="feather feather-sm"><use href="#calendar"></use></svg> IFTL
                                    </button>
                                    <button class="action-btn edit-btn" onclick="openEditFacultyModal(<?php echo $faculty['faculty_id']; ?>)" title="Edit">
                                        <svg class="feather feather-sm"><use href="#edit"></use></svg> Edit
                                    </button>
                                    <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_faculty', <?php echo $faculty['faculty_id']; ?>)">
                                        <svg class="feather feather-sm"><use href="#trash-2"></use></svg> Delete
                                    </button>
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
                                    <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_announcement', <?php echo $announcement['announcement_id']; ?>)">
                                        <svg class="feather feather-sm"><use href="#trash-2"></use></svg> Delete
                                    </button>
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
        

        <div class="modal-overlay" id="directorIFTLModal">
            <div class="modal large-modal">
                <div class="modal-header">
                    <h3 class="modal-title">Faculty IFTL - <span id="iftlFacultyName"></span></h3>
                    <button type="button" class="modal-close" onclick="closeModal('directorIFTLModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="iftl-controls" style="margin-bottom: 20px;">
                        <label for="iftlWeekSelect">Select Week:</label>
                        <select id="iftlWeekSelect" class="form-select" onchange="loadFacultyIFTL()">
                            <!-- Options populated via JS -->
                        </select>
                    </div>
                    <div id="iftlContent" class="schedule-table-container">
                        <!-- Content loaded via JS -->
                        <div class="loading">Select a week to view IFTL</div>
                    </div>
                </div>
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
    <script src="assets/js/program_chair_loader.js"></script>
    <script src="assets/js/director.js"></script>
    <script src="assets/js/schedule_print.js"></script>
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
            const programId = row.getAttribute('data-program-id');
            let expansionRowId;
            if (facultyId) {
                expansionRowId = 'faculty-expansion-' + facultyId;
            } else if (classId) {
                expansionRowId = 'class-expansion-' + classId;
            } else if (announcementId) {
                expansionRowId = 'announcement-expansion-' + announcementId;
            } else if (programId) {
                expansionRowId = 'program-expansion-' + programId;
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
                
                if (programId && !expansionRow.dataset.loaded) {
                    loadProgramCourses(programId);
                    expansionRow.dataset.loaded = 'true';
                }
            }
        };
    </script>
        <div class="modal-overlay" id="editFacultyModal">
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Edit Faculty Member</h3>
                    <button type="button" class="modal-close" onclick="closeModal('editFacultyModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="editFacultyForm" onsubmit="event.preventDefault(); submitEditFaculty();">
                        <input type="hidden" name="faculty_id" id="editFacultyId">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" id="editFullName" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" id="editUsername" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Program *</label>
                            <select name="program" id="editProgram" class="form-select">
                                <option value="">Select Program</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Education">Education</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact Email</label>
                            <input type="email" name="contact_email" id="editContactEmail" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="contact_phone" id="editContactPhone" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" name="password" id="editPassword" class="form-input">
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" onclick="closeModal('editFacultyModal')">Cancel</button>
                            <button type="submit" class="btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <script>
        // Edit Faculty Logic
        async function openEditFacultyModal(facultyId) {
            // Prevent event propagation if triggered from row click
            if(window.event) window.event.stopPropagation();
            
            const modal = document.getElementById('editFacultyModal');
            const form = document.getElementById('editFacultyForm');
            form.reset();
            
            try {
                const response = await fetch(`assets/php/polling_api.php?action=get_faculty_details&faculty_id=${facultyId}`);
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    document.getElementById('editFacultyId').value = data.faculty_id;
                    document.getElementById('editFullName').value = data.full_name;
                    document.getElementById('editUsername').value = data.username;
                    document.getElementById('editProgram').value = data.program;
                    document.getElementById('editContactEmail').value = data.contact_email;
                    document.getElementById('editContactPhone').value = data.contact_phone;
                    
                    modal.classList.add('show');
                } else {
                    alert('Error fetching faculty details: ' + result.message);
                }
            } catch (e) {
                console.error(e);
                alert('Error fetching details');
            }
        }
        
        async function submitEditFaculty() {
            if (typeof showConfirmation === 'function') {
                showConfirmation(
                    'Update Faculty Member',
                    'Are you sure you want to update this faculty member?',
                    async function() {
                         const form = document.getElementById('editFacultyForm');
                        const formData = new FormData(form);
                        formData.append('action', 'update_faculty');
                        
                        try {
                            const response = await fetch('assets/php/polling_api.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();
                            
                            if (result.success) {
                                alert('Faculty updated successfully');
                                closeModal('editFacultyModal');
                                location.reload(); // Simple reload to reflect changes
                            } else {
                                alert('Error updating faculty: ' + result.message);
                            }
                        } catch (e) {
                            console.error(e);
                            alert('Error updating faculty');
                        }
                    }
                );
                return;
            }

            const form = document.getElementById('editFacultyForm');
            const formData = new FormData(form);
            formData.append('action', 'update_faculty');
            
            try {
                const response = await fetch('assets/php/polling_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('Faculty updated successfully');
                    closeModal('editFacultyModal');
                    location.reload(); // Simple reload to reflect changes
                } else {
                    alert('Error updating faculty: ' + result.message);
                }
            } catch (e) {
                console.error(e);
                alert('Error updating faculty');
            }
        }
    </script>
</body>
</html>