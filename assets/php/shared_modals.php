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
                <select name="program_chair_id" class="form-select">
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
            <div id="scheduleContent">
                <div class="loading">Loading schedule...</div>
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
            <div id="courseLoadContent">
                <div class="loading">Loading...</div>
            </div>
        </div>
    </div>
</div>