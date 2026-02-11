<?php
require_once 'assets/php/common_utilities.php';
initializeSession();
$pdo = get_db_connection();
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
                <svg class="feather feather-sm"><use href="#users"></use></svg> Faculty Members
            </button>
            <button class="tab-button" onclick="switchTab('announcements')" data-tab="announcements">
                <svg class="feather feather-sm"><use href="#bell"></use></svg> Announcements
            </button>
            <button class="tab-button" onclick="switchTab('iftl')" data-tab="iftl">
                <svg class="feather feather-sm"><use href="#calendar"></use></svg> IFTL
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
                            <tr data-faculty-id="<?php echo $faculty['faculty_id']; ?>" onclick="openFacultyDetails(<?php echo htmlspecialchars(json_encode($faculty)); ?>)">
                                <td class="name-column"><?php echo htmlspecialchars($faculty['full_name']); ?></td>
                                <td class="status-column">
                                    <span class="status-badge status-<?php echo strtolower($faculty['status']); ?>">
                                        <?php echo $faculty['status']; ?>
                                    </span>
                                </td>
                                <td class="location-column"><?php echo htmlspecialchars($faculty['current_location'] ?? 'No Location'); ?></td>
                                <td class="actions-column">
                                    <button class="action-btn view-btn" onclick="event.stopPropagation(); openIFTLModal(<?php echo $faculty['faculty_id']; ?>, '<?php echo htmlspecialchars($faculty['full_name']); ?>')" title="View IFTL">
                                        <svg class="feather feather-sm"><use href="#calendar"></use></svg> IFTL
                                    </button>
                                    <button class="action-btn edit-btn" onclick="event.stopPropagation(); openEditFacultyModal(<?php echo $faculty['faculty_id']; ?>)" title="Edit">
                                        <svg class="feather feather-sm"><use href="#edit"></use></svg> Edit
                                    </button>
                                    <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_faculty', <?php echo $faculty['faculty_id']; ?>)">
                                        <svg class="feather feather-sm"><use href="#trash-2"></use></svg> Delete
                                    </button>
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
                            <tr data-announcement-id="<?php echo $announcement['announcement_id']; ?>" onclick="openAnnouncementDetails(<?php echo htmlspecialchars(json_encode($announcement)); ?>)">
                                <td class="name-column"><?php echo htmlspecialchars($announcement['title']); ?></td>
                                <td class="status-column">
                                    <span class="status-badge priority-<?php echo $announcement['priority']; ?>">
                                        <?php echo strtoupper($announcement['priority']); ?>
                                    </span>
                                </td>
                                <td class="program-column"><?php echo htmlspecialchars($announcement['target_audience']); ?></td>
                                <td class="actions-column">
                                    <button class="action-btn print-btn" onclick="event.stopPropagation(); printAnnouncement(<?php echo htmlspecialchars(json_encode($announcement)); ?>)" title="Print">
                                        <svg class="feather feather-sm"><use href="#printer"></use></svg> Print
                                    </button>
                                    <button class="action-btn email-btn" onclick="event.stopPropagation(); emailAnnouncement(<?php echo htmlspecialchars(json_encode($announcement)); ?>)" title="Email">
                                        <svg class="feather feather-sm"><use href="#mail"></use></svg> Email
                                    </button>
                                    <button class="action-btn edit-btn" onclick="event.stopPropagation(); openEditAnnouncementModal(<?php echo $announcement['announcement_id']; ?>)" title="Edit">
                                        <svg class="feather feather-sm"><use href="#edit"></use></svg> Edit
                                    </button>
                                    <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_announcement', <?php echo $announcement['announcement_id']; ?>)">
                                        <svg class="feather feather-sm"><use href="#trash-2"></use></svg> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="tab-content" id="iftl-content">
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">Individual Faculty Teaching Load</h3>
                </div>
                <div class="empty-state">
                    <h3>IFTL information will be displayed here.</h3>
                    <p>Select a faculty member from the Faculty tab to view their IFTL.</p>
                </div>
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

                        </select>
                    </div>
                    <div id="iftlContent" class="schedule-table-container">

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
                                <?php if (!empty($programs_data)): ?>
                                    <?php foreach ($programs_data as $program): ?>
                                        <option value="<?php echo htmlspecialchars($program['program_name']); ?>">
                                            <?php echo htmlspecialchars($program['program_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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

        <div class="modal-overlay" id="facultyDetailsModal">
            <div class="modal medium-modal">
                <div class="modal-header">
                    <h3 class="modal-title">Faculty Details</h3>
                    <button type="button" class="modal-close" onclick="closeModal('facultyDetailsModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="facultyDetailsContent" class="details-grid">

                    </div>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="announcementDetailsModal">
            <div class="modal medium-modal">
                <div class="modal-header">
                    <h3 class="modal-title">Announcement Details</h3>
                    <button type="button" class="modal-close" onclick="closeModal('announcementDetailsModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="announcementDetailsContent" class="details-grid">

                    </div>
                </div>
            </div>
        </div>
    <script>
        async function loadProgramsForSelect(selectElement, selectedProgram) {
            if (!selectElement) return;
            selectElement.innerHTML = '<option value="">Select Program</option>';
            try {
                const response = await fetch('assets/php/polling_api.php?action=get_programs');
                const result = await response.json();
                if (result.success && Array.isArray(result.programs)) {
                    result.programs.forEach(program => {
                        const option = document.createElement('option');
                        option.value = program.program_name;
                        option.textContent = program.program_name;
                        selectElement.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading programs:', error);
            }
            if (selectedProgram) {
                const hasOption = Array.from(selectElement.options)
                    .some(option => option.value === selectedProgram);
                if (!hasOption) {
                    const option = document.createElement('option');
                    option.value = selectedProgram;
                    option.textContent = selectedProgram;
                    selectElement.appendChild(option);
                }
                selectElement.value = selectedProgram;
            }
        }
        async function openEditFacultyModal(facultyId) {
            if(window.event) window.event.stopPropagation();
            const modal = document.getElementById('editFacultyModal');
            const form = document.getElementById('editFacultyForm');
            form.reset();
            try {
                const response = await fetch(`assets/php/polling_api.php?action=get_faculty_details&faculty_id=${facultyId}`);
                const result = await response.json();
                if (result.success) {
                    const data = result.data;
                    await loadProgramsForSelect(document.getElementById('editProgram'), data.program);
                    document.getElementById('editFacultyId').value = data.faculty_id;
                    document.getElementById('editFullName').value = data.full_name;
                    document.getElementById('editUsername').value = data.username;
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
                                location.reload();
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
                    location.reload();
                } else {
                    alert('Error updating faculty: ' + result.message);
                }
            } catch (e) {
                console.error(e);
                alert('Error updating faculty');
            }
        }
        function openFacultyDetails(faculty) {
            if (typeof faculty === 'string') {
                try {
                    faculty = JSON.parse(faculty);
                } catch (e) {
                    console.error("Error parsing faculty data", e);
                    return;
                }
            }
            const content = document.getElementById('facultyDetailsContent');
            const statusClass = (faculty.status || 'Offline').toLowerCase().replace(' ', '-');
            content.innerHTML = `
                <div class="details-section">
                    <h4>${faculty.full_name}</h4>
                    <span class="status-badge status-${statusClass}">${faculty.status || 'Offline'}</span>
                </div>
                <div class="details-row">
                    <div class="detail-group">
                        <label>Employee ID</label>
                        <span>${faculty.employee_id || 'N/A'}</span>
                    </div>
                    <div class="detail-group">
                        <label>Program</label>
                        <span>${faculty.program || 'N/A'}</span>
                    </div>
                    <div class="detail-group">
                        <label>Email</label>
                        <span>${faculty.contact_email || 'N/A'}</span>
                    </div>
                    <div class="detail-group">
                        <label>Phone</label>
                        <span>${faculty.contact_phone || 'N/A'}</span>
                    </div>
                </div>
            `;
            const modal = document.getElementById('facultyDetailsModal');
            modal.classList.add('show');
        }
        function openAnnouncementDetails(announcement) {
            if (typeof announcement === 'string') {
                try {
                    announcement = JSON.parse(announcement);
                } catch (e) {
                    console.error("Error parsing announcement data", e);
                    return;
                }
            }
            const content = document.getElementById('announcementDetailsContent');
            const date = new Date(announcement.created_at).toLocaleString();
            content.innerHTML = `
                <div class="details-section">
                    <h4>${announcement.title}</h4>
                    <span class="status-badge priority-${announcement.priority}">${announcement.priority.toUpperCase()}</span>
                </div>
                <div class="detail-content-box">
                    <label>Content</label>
                    <div class="content-text">${announcement.content}</div>
                </div>
                <div class="details-row">
                    <div class="detail-group">
                        <label>Target Audience</label>
                        <span>${announcement.target_audience}</span>
                    </div>
                    <div class="detail-group">
                        <label>Created By</label>
                        <span>${announcement.created_by_name}</span>
                    </div>
                    <div class="detail-group">
                        <label>Date</label>
                        <span>${date}</span>
                    </div>
                </div>
            `;
            const modal = document.getElementById('announcementDetailsModal');
            modal.classList.add('show');
        }
        async function openEditAnnouncementModal(announcementId) {
            if(window.event) window.event.stopPropagation();
            const modal = document.getElementById('editAnnouncementModal');
            const form = document.getElementById('editAnnouncementForm');
            form.reset();
            try {
                const response = await fetch(`assets/php/polling_api.php?action=get_announcement_details&announcement_id=${announcementId}`);
                const result = await response.json();
                if (result.success) {
                    const data = result.data;
                    document.getElementById('editAnnouncementId').value = data.announcement_id;
                    document.getElementById('editAnnouncementTitle').value = data.title;
                    document.getElementById('editAnnouncementContent').value = data.content;
                    document.getElementById('editAnnouncementPriority').value = data.priority;
                    document.getElementById('editAnnouncementAudience').value = data.target_audience;
                    closeModal('announcementDetailsModal');
                    modal.classList.add('show');
                } else {
                    alert('Error fetching announcement details: ' + result.message);
                }
            } catch (e) {
                console.error(e);
                alert('Error fetching details');
            }
        }
        async function submitEditAnnouncement() {
            if (typeof showConfirmation === 'function') {
                showConfirmation(
                    'Update Announcement',
                    'Are you sure you want to update this announcement?',
                    async function() {
                        const form = document.getElementById('editAnnouncementForm');
                        const formData = new FormData(form);
                        formData.append('action', 'update_announcement');
                        try {
                            const response = await fetch('assets/php/polling_api.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();
                            if (result.success) {
                                alert('Announcement updated successfully');
                                closeModal('editAnnouncementModal');
                                location.reload();
                            } else {
                                alert('Error updating announcement: ' + result.message);
                            }
                        } catch (e) {
                            console.error(e);
                            alert('Error updating announcement');
                        }
                    }
                );
                return;
            }
            const form = document.getElementById('editAnnouncementForm');
            const formData = new FormData(form);
            formData.append('action', 'update_announcement');
            try {
                const response = await fetch('assets/php/polling_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    alert('Announcement updated successfully');
                    closeModal('editAnnouncementModal');
                    location.reload();
                } else {
                    alert('Error updating announcement: ' + result.message);
                }
            } catch (e) {
                console.error(e);
                alert('Error updating announcement');
            }
        }
    </script>
    <div class="modal-overlay" id="editAnnouncementModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Edit Announcement</h3>
                <button type="button" class="modal-close" onclick="closeModal('editAnnouncementModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editAnnouncementForm" onsubmit="event.preventDefault(); submitEditAnnouncement();">
                    <input type="hidden" name="announcement_id" id="editAnnouncementId">
                    <div class="form-group">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" id="editAnnouncementTitle" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Content *</label>
                        <textarea name="content" id="editAnnouncementContent" class="form-textarea" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Priority</label>
                            <select name="priority" id="editAnnouncementPriority" class="form-select">
                                <option value="low">Normal</option>
                                <option value="medium">Important</option>
                                <option value="high">Urgent</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Target Audience</label>
                            <select name="target_audience" id="editAnnouncementAudience" class="form-select">
                                <option value="all">All Users</option>
                                <option value="faculty">Faculty Only</option>
                                <option value="program_chair">Program Chairs Only</option>
                                <option value="students">Students Only</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('editAnnouncementModal')">Cancel</button>
                        <button type="submit" class="btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

