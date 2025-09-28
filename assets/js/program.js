function switchTab(tabName) {
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => content.classList.remove('active'));
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => button.classList.remove('active'));
    document.getElementById(tabName + '-content').classList.add('active');
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    document.getElementById('searchInput').value = '';
    resetAllTabsVisibility();
}

function resetAllTabsVisibility() {
    const facultyCards = document.querySelectorAll('.faculty-card');
    facultyCards.forEach(card => card.style.display = 'block');
    const courseCards = document.querySelectorAll('.course-card');
    courseCards.forEach(card => card.style.display = 'block');  
    const classCards = document.querySelectorAll('.class-card');
    classCards.forEach(card => card.style.display = 'block');
    const emptyStates = document.querySelectorAll('.search-empty-state');
    emptyStates.forEach(state => state.remove());
}

function searchContent() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const activeTab = document.querySelector('.tab-content.active').id;
    
    if (activeTab === 'faculty-content') {
        searchFaculty(searchTerm);
    } else if (activeTab === 'courses-content') {
        searchCourses(searchTerm);
    } else if (activeTab === 'classes-content') {
        searchClasses(searchTerm);
    }
}

function searchFaculty(searchTerm) {
    const facultyCards = document.querySelectorAll('.faculty-card:not(.add-card)');
    let visibleCount = 0;

    facultyCards.forEach(card => {
        const name = card.getAttribute('data-name').toLowerCase();
        
        if (name.includes(searchTerm)) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    updateEmptyState('#facultyGrid', visibleCount, searchTerm, 'No faculty found', 'Try adjusting your search criteria');
}

function searchCourses(searchTerm) {
    const courseCards = document.querySelectorAll('.course-card');
    let visibleCount = 0;

    courseCards.forEach(card => {
        const courseCode = card.querySelector('.course-code').textContent.toLowerCase();
        const courseDescription = card.querySelector('.course-description').textContent.toLowerCase();
        
        if (courseCode.includes(searchTerm) || courseDescription.includes(searchTerm)) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    updateEmptyState('#courses-content .courses-grid', visibleCount, searchTerm, 'No courses found', 'Try adjusting your search criteria');
}

function searchClasses(searchTerm) {
    const classCards = document.querySelectorAll('.class-card:not(.add-card)');
    let visibleCount = 0;

    classCards.forEach(card => {
        const name = card.getAttribute('data-name').toLowerCase();
        const code = card.getAttribute('data-code').toLowerCase();
        
        if (name.includes(searchTerm) || code.includes(searchTerm)) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    updateEmptyState('#classesGrid', visibleCount, searchTerm, 'No classes found', 'Try adjusting your search criteria');
}

function updateEmptyState(containerSelector, visibleCount, searchTerm, title, message) {
    const container = document.querySelector(containerSelector);
    if (!container) return;
    
    const existingEmptyState = container.querySelector('.search-empty-state');
    
    if (existingEmptyState) {
        existingEmptyState.remove();
    }

    if (visibleCount === 0 && searchTerm.trim() !== '') {
        const emptyState = document.createElement('div');
        emptyState.className = 'empty-state search-empty-state';
        emptyState.innerHTML = `
            <h3>${title}</h3>
            <p>${message}</p>
        `;
        container.appendChild(emptyState);
    }
}

function contactFaculty(email) {
    window.location.href = 'mailto:' + email;
}

function callFaculty(phone) {
    window.location.href = 'tel:' + phone;
}

function generateScheduleView(facultyId, viewType = 'schedule') {
    const modalId = viewType === 'courseload' ? 'facultyCourseLoadModal' : 'facultyScheduleModal';
    const contentId = viewType === 'courseload' ? 'courseLoadContent' : 'scheduleContent';
    const titleId = viewType === 'courseload' ? 'courseLoadModalTitle' : 'scheduleModalTitle';
    
    const modal = document.getElementById(modalId);
    const content = document.getElementById(contentId);
    const title = document.getElementById(titleId);
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    const facultyName = facultyNames[facultyId];
    const schedules = facultySchedules[facultyId] || [];
    
    if (viewType === 'courseload') {
        title.textContent = `${facultyName} - Course Load Assignment`;
        content.innerHTML = generateGridLayout(schedules, generateCourseLoadForm(facultyId), true);
        loadCourseAndClassData();
    } else {
        title.textContent = `${facultyName} - Schedule`;
        if (schedules.length === 0) {
            content.innerHTML = `
                <div class="empty-state">
                    <h3>No Schedule Found</h3>
                    <p>This faculty member has no assigned classes.</p>
                </div>
            `;
            return;
        }
        content.innerHTML = generateGridLayout(schedules, generateScheduleSummary(schedules, facultyId), false);
    }
}

function generateGridLayout(schedules, rightContent, isClickable = false) {
    return `
        <div class="modal-grid-container" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; height: 100%;">
            <div class="schedule-tables">
                ${generateScheduleTables(schedules, isClickable)}
            </div>
            <div class="right-panel">
                ${rightContent}
            </div>
        </div>
    `;
}

function generateScheduleTables(schedules, isClickable = false) {
    const clickableClass = isClickable ? 'clickable-cell' : '';
    const clickHandler = isClickable ? 'onclick="handleTimeSlotClick(this)"' : '';
    
    return `
        <div class="schedule-table-container">
            <h4>Monday, Wednesday, Friday & Saturday</h4>
            ${generateMWFScheduleTable(schedules, clickableClass, clickHandler)}
        </div>
        <div class="schedule-table-container">
            <h4>Tuesday & Thursday</h4>
            ${generateTTHScheduleTable(schedules, clickableClass, clickHandler)}
        </div>
    `;
}

function generateMWFScheduleTable(schedules, clickableClass, clickHandler) {
    const times = ['08:00:00', '09:00:00', '10:00:00', '11:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00'];
    
    let html = `<table class="schedule-table"><thead><tr><th>Time</th><th>M</th><th>W</th><th>F</th><th>S</th></tr></thead><tbody>`;
    
    times.forEach(time => {
        html += `<tr><td class="time-cell">${formatTime(time)}</td>`;
        html += `<td class="${clickableClass}" data-time="${time}" data-day="M" ${clickHandler}>${findCourseForTimeAndDay(schedules, time, 'M')}</td>`;
        html += `<td class="${clickableClass}" data-time="${time}" data-day="W" ${clickHandler}>${findCourseForTimeAndDay(schedules, time, 'W')}</td>`;
        html += `<td class="${clickableClass}" data-time="${time}" data-day="F" ${clickHandler}>${findCourseForTimeAndDay(schedules, time, 'F')}</td>`;
        html += `<td class="${clickableClass}" data-time="${time}" data-day="S" ${clickHandler}>${findCourseForTimeAndDay(schedules, time, 'S')}</td></tr>`;
    });
    
    return html + '</tbody></table>';
}

function generateTTHScheduleTable(schedules, clickableClass, clickHandler) {
    const times = ['07:30:00', '09:00:00', '10:30:00', '13:00:00', '14:30:00', '16:00:00', '17:30:00'];
    
    let html = `<table class="schedule-table"><thead><tr><th>Time</th><th>T</th><th>TH</th></tr></thead><tbody>`;
    
    times.forEach(time => {
        html += `<tr><td class="time-cell">${formatTime(time)}</td>`;
        html += `<td class="${clickableClass}" data-time="${time}" data-day="T" ${clickHandler}>${findCourseForTimeAndDay(schedules, time, 'T')}</td>`;
        html += `<td class="${clickableClass}" data-time="${time}" data-day="TH" ${clickHandler}>${findCourseForTimeAndDay(schedules, time, 'TH')}</td></tr>`;
    });
    
    return html + '</tbody></table>';
}

function generateScheduleSummary(schedules, facultyId) {
    const totalUnits = schedules.reduce((sum, schedule) => sum + parseInt(schedule.units), 0);
    const totalSubjects = schedules.length;
    
    return `
        <div class="schedule-summary-content">
            <h4>Schedule Summary</h4>
            <div class="load-stats">
                <div class="load-stat">
                    <div class="stat-number">${totalSubjects}</div>
                    <div class="stat-label">Total Subjects</div>
                </div>
                <div class="load-stat">
                    <div class="stat-number">${totalUnits}</div>
                    <div class="stat-label">Total Units</div>
                </div>
            </div>
            
            <div class="subjects-list">
                <h5>Assigned Subjects</h5>
                ${schedules.map(schedule => `
                    <div class="subject-item">
                        <div class="subject-code">${schedule.course_code}</div>
                        <div class="subject-details">
                            <div class="subject-desc">${schedule.course_description}</div>
                            <div class="subject-info">
                                ${schedule.units} units • ${schedule.days} • ${formatTime(schedule.time_start)}-${formatTime(schedule.time_end)}
                            </div>
                            <div class="subject-class">${schedule.class_name} (${schedule.class_code})</div>
                        </div>
                    </div>
                `).join('')}
            </div>
            
            <div class="load-actions">
                <button class="btn-secondary" onclick="exportSchedule(${facultyId})">Export Schedule</button>
                <button class="btn-primary" onclick="printSchedule(${facultyId})">Print Schedule</button>
            </div>
        </div>
    `;
}

function generateCourseLoadForm(facultyId) {
    return `
        <div class="courseload-assignment-form">
            <form id="assignCourseLoadForm" onsubmit="event.preventDefault(); submitCourseAssignment(this, ${facultyId});">
                <div class="form-section">
                    <h4>Assign Course to Faculty</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Select Course *</label>
                            <select name="course_code" class="form-select" required id="courseSelect" onchange="updateCourseInfo()">
                                <option value="">Choose a course...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Select Class *</label>
                            <select name="class_id" class="form-select" required id="classSelect">
                                <option value="">Choose a class...</option>
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
                            <select name="days" class="form-select" required>
                                <option value="">Select days...</option>
                                <option value="MWF">Monday, Wednesday, Friday</option>
                                <option value="MW">Monday, Wednesday</option>
                                <option value="TTH">Tuesday, Thursday</option>
                                <option value="MF">Monday, Friday</option>
                                <option value="WF">Wednesday, Friday</option>
                                <option value="S">Saturday</option>
                                <option value="MTWTHF">Monday to Friday</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Room</label>
                            <input type="text" name="room" class="form-input" placeholder="e.g., Room 101">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Start Time *</label>
                            <select name="time_start" class="form-select" required>
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
                            <select name="time_end" class="form-select" required>
                                <option value="">Select end time...</option>
                                <option value="08:30:00">8:30 AM</option>
                                <option value="09:00:00">9:00 AM</option>
                                <option value="10:00:00">10:00 AM</option>
                                <option value="10:30:00">10:30 AM</option>
                                <option value="11:00:00">11:00 AM</option>
                                <option value="12:00:00">12:00 PM</option>
                                <option value="14:00:00">2:00 PM</option>
                                <option value="15:00:00">3:00 PM</option>
                                <option value="15:30:00">3:30 PM</option>
                                <option value="16:00:00">4:00 PM</option>
                                <option value="17:00:00">5:00 PM</option>
                                <option value="18:00:00">6:00 PM</option>
                                <option value="19:00:00">7:00 PM</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('facultyCourseLoadModal')">Cancel</button>
                        <button type="submit" class="btn-primary">Assign Course</button>
                    </div>
                </div>
            </form>
        </div>
    `;
}

function findCourseForTimeAndDay(schedules, timeSlot, day) {
    const schedule = schedules.find(s => {
        const daysValue = s.days.toUpperCase();
        const dayMap = {
            'M': ['M', 'MW', 'MF', 'MWF', 'MTWTHF'],
            'T': ['T', 'TTH', 'MTWTHF'],
            'W': ['W', 'MW', 'WF', 'MWF', 'MTWTHF'],
            'TH': ['TH', 'TTH', 'MTWTHF'],
            'F': ['F', 'MF', 'WF', 'MWF', 'MTWTHF'],
            'S': ['S']
        };
        
        return dayMap[day]?.includes(daysValue) && s.time_start === timeSlot;
    });
    
    if (schedule) {
        return `<div class="course-code">${schedule.course_code}</div><div class="room-info">${schedule.room || 'TBA'}</div>`;
    }
    return '';
}

function handleTimeSlotClick(cell) {
    const time = cell.getAttribute('data-time');
    const day = cell.getAttribute('data-day');
    
    const timeSelect = document.querySelector('select[name="time_start"]');
    const daysSelect = document.querySelector('select[name="days"]');
    
    if (timeSelect) {
        timeSelect.value = time;
    }
    
    if (daysSelect && day) {
        const dayMappings = {
            'M': 'MWF',
            'W': 'MWF', 
            'F': 'MWF',
            'T': 'TTH',
            'TH': 'TTH',
            'S': 'S'
        };
        daysSelect.value = dayMappings[day] || '';
    }
    
    showNotification(`Time slot selected: ${day} at ${formatTime(time)}`, 'info');
}

function viewSchedule(facultyId) {
    generateScheduleView(facultyId, 'schedule');
}

function viewCourseLoad(facultyId) {
    generateScheduleView(facultyId, 'courseload');
}

function loadCourseAndClassData() {
    fetch('program.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_course_class_data'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateCourseSelect(data.courses);
            populateClassSelect(data.classes);
        }
    })
    .catch(error => {
        console.error('Error loading data:', error);
    });
}

function populateCourseSelect(courses) {
    const courseSelect = document.getElementById('courseSelect');
    courseSelect.innerHTML = '<option value="">Choose a course...</option>';
    
    courses.forEach(course => {
        const option = document.createElement('option');
        option.value = course.course_code;
        option.textContent = `${course.course_code} - ${course.course_description}`;
        option.setAttribute('data-units', course.units);
        option.setAttribute('data-description', course.course_description);
        courseSelect.appendChild(option);
    });
}

function populateClassSelect(classes) {
    const classSelect = document.getElementById('classSelect');
    classSelect.innerHTML = '<option value="">Choose a class...</option>';
    
    classes.forEach(classItem => {
        const option = document.createElement('option');
        option.value = classItem.class_id;
        option.textContent = `${classItem.class_code} - ${classItem.class_name} (Year ${classItem.year_level})`;
        classSelect.appendChild(option);
    });
}

function updateCourseInfo() {
    const courseSelect = document.getElementById('courseSelect');
    const selectedOption = courseSelect.options[courseSelect.selectedIndex];
    const courseInfoDiv = document.getElementById('courseInfoDiv');
    
    if (selectedOption.value) {
        document.getElementById('courseDescription').textContent = selectedOption.getAttribute('data-description');
        document.getElementById('courseUnits').textContent = selectedOption.getAttribute('data-units');
        courseInfoDiv.style.display = 'block';
    } else {
        courseInfoDiv.style.display = 'none';
    }
}

function submitCourseAssignment(form, facultyId) {
    const formData = new FormData(form);
    formData.append('action', 'assign_course_load');
    formData.append('faculty_id', facultyId);
    
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Course assigned successfully!', 'success');
            closeModal('facultyCourseLoadModal');
            location.reload();
        } else {
            showNotification(data.message || 'Failed to assign course', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while assigning the course', 'error');
    });
}

function exportSchedule(facultyId) {
    showNotification('Export functionality will be implemented soon', 'info');
}

function printSchedule(facultyId) {
    const facultyName = facultyNames[facultyId];
    const printContent = document.querySelector('#scheduleContent').innerHTML;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>${facultyName} Schedule</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .schedule-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    .schedule-table th, .schedule-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
                    .schedule-table th { background-color: #f5f5f5; }
                    .course-code { font-weight: bold; }
                    .room-info { font-size: 0.8em; color: #666; }
                    .right-panel { display: none; }
                    @media print { body { margin: 0; } .right-panel { display: none; } }
                </style>
            </head>
            <body>
                <h2>${facultyName} - Schedule</h2>
                ${printContent}
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}

function formatTime(time) {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${hour >= 12 ? 'PM' : 'AM'}`;
}

function viewClassDetails(classId) {
    showNotification('Class details view will be implemented soon', 'info');
}

function manageSchedule(classId) {
    showNotification('Schedule management will be implemented soon', 'info');
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const contentWrapper = document.getElementById('contentWrapper');

    sidebar.classList.toggle('open');
    overlay.classList.toggle('show');
    contentWrapper.classList.toggle('sidebar-open');
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const contentWrapper = document.getElementById('contentWrapper');

    sidebar.classList.remove('open');
    overlay.classList.remove('show');
    contentWrapper.classList.remove('sidebar-open');
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchContent();
            }
        });
        searchInput.addEventListener('input', searchContent);
        searchInput.focus();
    }

    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.announcement-toggle');

        if (sidebar && toggle &&
            window.innerWidth > 768 &&
            !sidebar.contains(e.target) &&
            !toggle.contains(e.target) &&
            sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
});

setInterval(function() {
    if (!document.querySelector('.modal-overlay.show')) {
        location.reload();
    }
}, 300000);

document.addEventListener('click', function(e) {
    const card = e.target.closest('.add-card');
    if (card && card.dataset.modal) {
        openModal(card.dataset.modal);
    }
});
