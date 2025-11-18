<?php
$role = $_SESSION['role'] ?? '';
?>
<div class="modal-overlay" id="addFacultyModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add New Faculty Member</h3>
            <button type="button" class="modal-close" onclick="closeModal('addFacultyModal')">&times;</button>
        </div>
        <form id="addFacultyForm" data-action="add_faculty" data-tab="faculty" onsubmit="event.preventDefault(); submitGenericForm(this);" class="modal-form">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-input" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
            </div>
            <?php if ($role === 'campus_director'): ?>
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="is_program_chair" value="1" onchange="toggleProgramField(this)">
                    Assign as Program Chair
                </label>
            </div>
            <div class="form-group" id="programField" style="display:none;">
                <label class="form-label">Program *</label>
                <select name="program" class="form-select">
                    <option value="">Select Program</option>
                    <option value="Computer Science">Computer Science</option>
                    <option value="Information Technology">Information Technology</option>
                    <option value="Engineering">Engineering</option>
                    <option value="Business Administration">Business Administration</option>
                    <option value="Education">Education</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="contact_email" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="contact_phone" class="form-input">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('addFacultyModal')">Cancel</button>
                <button type="submit" class="btn-primary">Add Faculty</button>
            </div>
        </form>
        <script>
        function toggleProgramField(checkbox) {
            const field = document.getElementById('programField');
            field.style.display = checkbox.checked ? 'block' : 'none';
        }
        </script>
    </div>
</div>
<div class="modal-overlay" id="addCourseModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add New Course</h3>
            <button type="button" class="modal-close" onclick="closeModal('addCourseModal')">&times;</button>
        </div>
        <form id="addCourseForm" data-action="add_course" data-tab="courses" onsubmit="event.preventDefault(); submitGenericForm(this);" class="modal-form">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Course Code *</label>
                    <input type="text" name="course_code" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Units *</label>
                    <select name="units" class="form-select" required>
                        <option value="">Select Units</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?> Unit<?= $i > 1 ? 's' : '' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Course Description *</label>
                <input type="text" name="course_description" class="form-input" required>
            </div>
            <?php if ($role === 'campus_director'): ?>
            <div class="form-group">
                <label class="form-label">Program *</label>
                <select name="program_id" class="form-select" id="programSelectCourse" required>
                    <option value="">Select Program</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('addCourseModal')">Cancel</button>
                <button type="submit" class="btn-primary">Add Course</button>
            </div>
        </form>
    </div>
</div>
<div class="modal-overlay" id="addClassModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add New Class</h3>
            <button type="button" class="modal-close" onclick="closeModal('addClassModal')">&times;</button>
        </div>
        <form id="addClassForm" data-action="add_class" data-tab="classes" onsubmit="event.preventDefault(); submitGenericForm(this);" class="modal-form">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Class Name *</label>
                    <input type="text" name="class_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Year Level *</label>
                    <select name="year_level" class="form-select" required>
                        <option value="">Select Year</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?><?= $i === 1 ? 'st' : ($i === 2 ? 'nd' : ($i === 3 ? 'rd' : 'th')) ?> Year</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Class Code *</label>
                    <input type="text" name="class_code" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Semester *</label>
                    <select name="semester" class="form-select" required>
                        <option value="">Select Semester</option>
                        <option value="1st">1st Semester</option>
                        <option value="2nd">2nd Semester</option>
                        <option value="summer">Summer</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Academic Year *</label>
                <input type="text" name="academic_year" class="form-input" required>
            </div>
            <?php if ($role === 'campus_director'): ?>
            <div class="form-group">
                <label class="form-label">Assign Program Chair</label>
                <select name="program_chair_id" class="form-select" id="programChairSelect">
                    <option value="">Select Program Chair (Optional)</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Class Account Username *</label>
                    <input type="text" name="username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Class Account Password *</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('addClassModal')">Cancel</button>
                <button type="submit" class="btn-primary">Add Class</button>
            </div>
        </form>
    </div>
</div>
<?php if ($role === 'campus_director'): ?>
<div class="modal-overlay" id="addAnnouncementModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add New Announcement</h3>
            <button type="button" class="modal-close" onclick="closeModal('addAnnouncementModal')">&times;</button>
        </div>
        <form id="addAnnouncementForm" data-action="add_announcement" data-tab="announcements" onsubmit="event.preventDefault(); submitGenericForm(this);" class="modal-form">
            <div class="form-group">
                <label class="form-label">Title *</label>
                <input type="text" name="title" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Content *</label>
                <textarea name="content" class="form-textarea" required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="normal">Normal</option>
                        <option value="important">Important</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Target Audience</label>
                    <select name="target_audience" class="form-select">
                        <option value="all">All Users</option>
                        <option value="faculty">Faculty Only</option>
                        <option value="program_chair">Program Chairs Only</option>
                        <option value="students">Students Only</option>
                    </select>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('addAnnouncementModal')">Cancel</button>
                <button type="submit" class="btn-primary">Add Announcement</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<div class="modal-overlay" id="facultyScheduleModal">
    <div class="modal large-modal">
        <div class="modal-header">
            <h3 class="modal-title" id="scheduleModalTitle">Faculty Schedule</h3>
            <button type="button" class="modal-close" onclick="closeModal('facultyScheduleModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Mobile pagination navigation -->
            <div class="mobile-pagination-nav">
                <button class="pagination-btn active" onclick="showMobilePage(1)" data-page="1">MWF Schedule</button>
                <button class="pagination-btn" onclick="showMobilePage(2)" data-page="2">TTH Schedule</button>
                <button class="pagination-btn" onclick="showMobilePage(3)" data-page="3">Summary</button>
            </div>
            <!-- Content area with both mobile pages and desktop grid -->
            <div id="scheduleContent">
                <!-- Mobile paginated content -->
                <div class="mobile-page-content">
                    <div class="mobile-page page-1 active">
                        <div class="schedule-table-container" id="mwfTableContainer">
                            <div class="loading">Loading MWF schedule...</div>
                        </div>
                    </div>
                    <div class="mobile-page page-2">
                        <div class="schedule-table-container" id="tthTableContainer">
                            <div class="loading">Loading TTH schedule...</div>
                        </div>
                    </div>
                    <div class="mobile-page page-3">
                        <div class="schedule-table-container" id="fullSummaryContainer">
                            <div class="right-panel" id="mobileSummaryPanel">
                                <div class="loading">Loading summary...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Desktop/tablet grid content -->
                <div class="desktop-grid-content">
                    <div class="loading">Loading schedule...</div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-overlay" id="facultyCourseLoadModal">
    <div class="modal large-modal">
        <div class="modal-header">
            <h3 class="modal-title" id="courseLoadModalTitle">Faculty Course Load</h3>
            <button type="button" class="modal-close" onclick="closeModal('facultyCourseLoadModal')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Mobile pagination navigation - 2 pages for course load -->
            <div class="mobile-pagination-nav">
                <button class="pagination-btn active" onclick="showCourseLoadPage(1)" data-page="1">MWF Schedule</button>
                <button class="pagination-btn" onclick="showCourseLoadPage(2)" data-page="2">TTH Schedule</button>
            </div>
            <!-- Content area -->
            <div id="courseLoadContent">
                <!-- Mobile paginated content -->
                <div class="mobile-page-content">
                    <div class="mobile-page page-1 active">
                        <div class="schedule-table-container" id="courseLoadMwfTableContainer">
                            <div class="loading">Loading MWF schedule...</div>
                        </div>
                    </div>
                    <div class="mobile-page page-2">
                        <div class="schedule-table-container" id="courseLoadTthTableContainer">
                            <div class="loading">Loading TTH schedule...</div>
                        </div>
                    </div>
                </div>
                <!-- Desktop/tablet grid content -->
                <div class="desktop-grid-content">
                    <div class="loading">Loading course load...</div>
                </div>
                <!-- Assignment panel at bottom -->
                <div id="assignmentPanel" class="assignment-panel" style="display: none;">
                    <div class="assignment-header">
                        <span id="selectedTimeSlot">Select a time slot to assign courses</span>
                        <button onclick="closeAssignmentPanel()" class="close-btn">×</button>
                    </div>
                    <div id="assignmentContent" class="assignment-content">
                        <!-- Assignment form will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-overlay" id="curriculumAssignModal">
    <div class="modal large-modal">
        <div class="modal-header">
            <h3 class="modal-title" id="curriculumModalTitle">Assign Course to Curriculum</h3>
            <button type="button" class="modal-close" onclick="closeModal('curriculumAssignModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="curriculumAssignContent">
                <div class="loading">Loading...</div>
            </div>
        </div>
    </div>
</div>
<div class="modal-overlay" id="updateSemesterModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Update Semester</h3>
            <button type="button" class="modal-close" onclick="closeModal('updateSemesterModal')">&times;</button>
        </div>
        <form id="updateSemesterForm" onsubmit="event.preventDefault(); updateSemester(this);" class="modal-form">
            <div class="form-group">
                <label class="form-label">Select Semester to Update *</label>
                <select name="semester" class="form-select" required onchange="loadClassesForSemester(this.value)">
                    <option value="">Select Semester</option>
                    <option value="1st">1st Semester</option>
                    <option value="2nd">2nd Semester</option>
                    <option value="Summer">Summer</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Academic Year *</label>
                <select name="academic_year" class="form-select" required onchange="loadClassesForSemester(document.querySelector('[name=semester]').value)">
                    <option value="">Select Academic Year</option>
                    <?php 
                    $current_year = date('Y');
                    for ($i = $current_year - 2; $i <= $current_year + 2; $i++) {
                        $ay = $i . '-' . ($i + 1);
                        $selected = ($ay === $current_year . '-' . ($current_year + 1)) ? 'selected' : '';
                        echo "<option value='$ay' $selected>$ay</option>";
                    }
                    ?>
                </select>
            </div>
            <div id="classesPreview" class="form-group" style="display: none;">
                <label class="form-label">Classes to Update</label>
                <div id="classesPreviewContent" class="preview-content">
                    <!-- Classes will be loaded here -->
                </div>
            </div>
            <div class="form-group">
                <div class="warning-box" style="background: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 5px; margin: 10px 0;">
                    <strong>⚠️ Warning:</strong> This will:
                    <ul style="margin: 10px 0 0 20px;">
                        <li>Add ALL courses from the curriculum to selected classes</li>
                        <li>Reset ALL existing faculty assignments for those classes</li>
                        <li>This action cannot be undone</li>
                    </ul>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('updateSemesterModal')">Cancel</button>
                <button type="button" class="btn-primary" id="updateSemesterBtn" onclick="window.updateSemester(document.getElementById('updateSemesterForm'));">Update Semester</button>
            </div>
        </form>
    </div>
</div>