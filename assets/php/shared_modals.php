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
                <div class="modal-grid-container" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; height: 100%;">
                    <div class="schedule-tables">
                        <!-- Tables will be populated by JavaScript -->
                    </div>
                    <div class="right-panel">
                        <div class="courseload-assignment-form">
                            <form id="assignCourseLoadForm" onsubmit="event.preventDefault(); submitCourseAssignment(this);" class="modal-form">
                                <div class="form-section">
                                    <h4>Assign Course to Faculty</h4>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Select Course *</label>
                                            <select name="course_code" class="form-select" required id="courseSelect" onchange="updateCourseInfo()">
                                                <option value="">Choose a course...</option>
                                                <?php
                                                $courses_query = "SELECT course_code, course_description, units FROM courses WHERE is_active = TRUE ORDER BY course_code";
                                                $courses_stmt = $pdo->prepare($courses_query);
                                                $courses_stmt->execute();
                                                $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
                                                foreach ($courses as $course):
                                                ?>
                                                <option value="<?= $course['course_code'] ?>" 
                                                        data-units="<?= $course['units'] ?>" 
                                                        data-description="<?= htmlspecialchars($course['course_description']) ?>">
                                                    <?= $course['course_code'] ?> - <?= htmlspecialchars($course['course_description']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Select Class *</label>
                                            <select name="class_id" class="form-select" required id="classSelect">
                                                <option value="">Choose a class...</option>
                                                <?php
                                                $classes_query = "SELECT class_id, class_code, class_name, year_level FROM classes WHERE program_chair_id = ? AND is_active = TRUE ORDER BY year_level, class_name";
                                                $classes_stmt = $pdo->prepare($classes_query);
                                                $classes_stmt->execute([$user_id]);
                                                $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
                                                foreach ($classes as $class):
                                                ?>
                                                <option value="<?= $class['class_id'] ?>">
                                                    <?= $class['class_code'] ?> - <?= htmlspecialchars($class['class_name']) ?> (Year <?= $class['year_level'] ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group" id="courseInfoDiv" style="display: none;">
                                        <div class="course-info-display">
                                            <div class="info-item">
                                                <strong>Course Description:</strong>
                                                <span id="courseDescription">-</span>
                                            </div>
                                            <div class="info-item">
                                                <strong>Units:</strong>
                                                <span id="courseUnits">-</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Days *</label>
                                            <div class="days-checkbox-group">
                                                <label class="day-checkbox">
                                                    <input type="checkbox" name="days[]" value="M" onchange="handleDaySelection(this)"> M
                                                </label>
                                                <label class="day-checkbox">
                                                    <input type="checkbox" name="days[]" value="T" onchange="handleDaySelection(this)"> T
                                                </label>
                                                <label class="day-checkbox">
                                                    <input type="checkbox" name="days[]" value="W" onchange="handleDaySelection(this)"> W
                                                </label>
                                                <label class="day-checkbox">
                                                    <input type="checkbox" name="days[]" value="TH" onchange="handleDaySelection(this)"> TH
                                                </label>
                                                <label class="day-checkbox">
                                                    <input type="checkbox" name="days[]" value="F" onchange="handleDaySelection(this)"> F
                                                </label>
                                                <label class="day-checkbox">
                                                    <input type="checkbox" name="days[]" value="S" onchange="handleDaySelection(this)"> S
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Room</label>
                                            <input type="text" name="room" class="form-input" placeholder="e.g., Room 101">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Start Time *</label>
                                            <select name="time_start" class="form-select" required id="timeStartSelect" onchange="updateEndTimeOptions()">
                                                <option value="">Select start time...</option>
                                                <option value="07:30:00">7:30 AM</option>
                                                <option value="08:00:00">8:00 AM</option>
                                                <option value="09:00:00">9:00 AM</option>
                                                <option value="10:00:00">10:00 AM</option>
                                                <option value="10:30:00">10:30 AM</option>
                                                <option value="11:00:00">11:00 AM</option>
                                                <option value="13:00:00">1:00 PM</option>
                                                <option value="14:00:00">2:00 PM</option>
                                                <option value="14:30:00">2:30 PM</option>
                                                <option value="15:00:00">3:00 PM</option>
                                                <option value="16:00:00">4:00 PM</option>
                                                <option value="17:00:00">5:00 PM</option>
                                                <option value="17:30:00">5:30 PM</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">End Time *</label>
                                            <select name="time_end" class="form-select" required id="timeEndSelect">
                                                <option value="">Select end time...</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="modal-actions">
                                        <button type="button" class="btn-secondary" onclick="closeModal('facultyCourseLoadModal')">Cancel</button>
                                        <button type="submit" class="btn-primary">Assign Course</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>