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

let currentSelectedTimeSlot = null;
let currentSelectedDayGroup = null;

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
            <h4>Monday, Wednesday, Friday</h4>
            ${generateMWFScheduleTable(schedules, clickableClass, clickHandler)}
        </div>
        <div class="schedule-table-container">
            <h4>Tuesday, Thursday & Saturday</h4>
            ${generateTTHScheduleTable(schedules, clickableClass, clickHandler)}
        </div>
    `;
}

function generateMWFScheduleTable(schedules, clickableClass, clickHandler) {
    const times = ['08:00:00', '09:00:00', '10:00:00', '11:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00'];
    
    let html = `<table class="schedule-table"><thead><tr><th>Time</th><th>M</th><th>W</th><th>F</th></tr></thead><tbody>`;
    
    times.forEach(time => {
        html += `<tr><td class="time-cell">${formatTime(time)}</td>`;
        
        const courseM = findCourseForTimeAndDay(schedules, time, 'M');
        const courseW = findCourseForTimeAndDay(schedules, time, 'W');
        const courseF = findCourseForTimeAndDay(schedules, time, 'F');
        
        const clickableM = courseM ? '' : `class="${clickableClass}" data-time="${time}" data-day="M" data-group="MWF" ${clickHandler}`;
        const clickableW = courseW ? '' : `class="${clickableClass}" data-time="${time}" data-day="W" data-group="MWF" ${clickHandler}`;
        const clickableF = courseF ? '' : `class="${clickableClass}" data-time="${time}" data-day="F" data-group="MWF" ${clickHandler}`;
        
        html += `<td ${clickableM}>${courseM}</td>`;
        html += `<td ${clickableW}>${courseW}</td>`;
        html += `<td ${clickableF}>${courseF}</td></tr>`;
    });
    
    return html + '</tbody></table>';
}

function generateTTHScheduleTable(schedules, clickableClass, clickHandler) {
    const times = ['07:30:00', '09:00:00', '10:30:00', '13:00:00', '14:30:00', '16:00:00'];
    
    let html = `<table class="schedule-table"><thead><tr><th>Time</th><th>T</th><th>TH</th><th>S</th></tr></thead><tbody>`;
    
    times.forEach(time => {
        html += `<tr><td class="time-cell">${formatTime(time)}</td>`;
        
        const courseT = findCourseForTimeAndDay(schedules, time, 'T');
        const courseTH = findCourseForTimeAndDay(schedules, time, 'TH');
        const courseS = findCourseForTimeAndDay(schedules, time, 'S');
        
        const clickableT = courseT ? '' : `class="${clickableClass}" data-time="${time}" data-day="T" data-group="TTHS" ${clickHandler}`;
        const clickableTH = courseTH ? '' : `class="${clickableClass}" data-time="${time}" data-day="TH" data-group="TTHS" ${clickHandler}`;
        const clickableS = courseS ? '' : `class="${clickableClass}" data-time="${time}" data-day="S" data-group="TTHS" ${clickHandler}`;
        
        html += `<td ${clickableT}>${courseT}</td>`;
        html += `<td ${clickableTH}>${courseTH}</td>`;
        html += `<td ${clickableS}>${courseS}</td></tr>`;
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
    currentFacultyId = facultyId;
    
    return `
        <div class="courseload-assignment-form" style="display: none;">
            <form id="assignCourseLoadForm" onsubmit="event.preventDefault(); submitCourseAssignment(this, ${facultyId});">
                <div class="form-section">
                    <h4>Assign Course to Faculty</h4>
                    <div class="time-selection-notice" style="padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin-bottom: 15px; font-size: 0.9rem; color: #856404;">
                        Click on a time slot in the schedule above to begin assignment
                    </div>
                    
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
                            <div class="days-checkbox-group">
                                <label class="day-checkbox">
                                    <input type="checkbox" name="days[]" value="M"> M
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" name="days[]" value="T"> T
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" name="days[]" value="W"> W
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" name="days[]" value="TH"> TH
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" name="days[]" value="F"> F
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" name="days[]" value="S"> S
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Room</label>
                            <input type="text" name="room" class="form-input" placeholder="e.g., Room 101">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">End Time *</label>
                        <select name="time_end" class="form-select" required id="timeEndSelect">
                            <option value="">Select end time...</option>
                        </select>
                    </div>
                    
                    <input type="hidden" name="time_start" id="hiddenTimeStart">
                    
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
            'M': ['M', 'MW', 'MF', 'MWF'],
            'T': ['T', 'TTH'],
            'W': ['W', 'MW', 'WF', 'MWF'],
            'TH': ['TH', 'TTH'],
            'F': ['F', 'MF', 'WF', 'MWF'],
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
    const group = cell.getAttribute('data-group');
    
    currentSelectedTimeSlot = time;
    currentSelectedDayGroup = group;
    
    document.getElementById('hiddenTimeStart').value = time;
    const displayTimeStart = document.getElementById('displayTimeStart');
    if (displayTimeStart) {
        displayTimeStart.value = formatTime(time);
    }
    
    const allCheckboxes = document.querySelectorAll('input[name="days[]"]');
    allCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
        checkbox.disabled = false;
        checkbox.parentElement.style.opacity = '1';
        checkbox.parentElement.classList.remove('disabled');
        delete checkbox.dataset.required;
    });
    
    if (group === 'MWF') {
        ['T', 'TH', 'S'].forEach(d => {
            const cb = document.querySelector(`input[name="days[]"][value="${d}"]`);
            if (cb) {
                cb.disabled = true;
                cb.parentElement.style.opacity = '0.5';
                cb.parentElement.classList.add('disabled');
            }
        });
    } else if (group === 'TTHS') {
        ['M', 'W', 'F'].forEach(d => {
            const cb = document.querySelector(`input[name="days[]"][value="${d}"]`);
            if (cb) {
                cb.disabled = true;
                cb.parentElement.style.opacity = '0.5';
                cb.parentElement.classList.add('disabled');
            }
        });
    }
    
    const clickedCheckbox = document.querySelector(`input[name="days[]"][value="${day}"]`);
    if (clickedCheckbox) {
        clickedCheckbox.checked = true;
        clickedCheckbox.disabled = true;
        clickedCheckbox.dataset.required = 'true';
    }
    
    checkConflicts(time, group);
    updateEndTimeOptions();
    
    document.querySelector('.courseload-assignment-form').style.display = 'block';
    
    showNotification(`Time slot selected: ${day} at ${formatTime(time)}`, 'info');
}

function checkConflicts(selectedTime, selectedGroup) {
    const facultyId = currentFacultyId;
    const schedules = facultySchedules[facultyId] || [];
    
    const conflictingDays = [];
    
    schedules.forEach(schedule => {
        if (schedule.time_start === selectedTime) {
            const scheduleDays = schedule.days.toUpperCase();
            
            if (selectedGroup === 'MWF') {
                ['M', 'W', 'F'].forEach(day => {
                    if (scheduleDays.includes(day)) {
                        conflictingDays.push(day);
                    }
                });
            } else if (selectedGroup === 'TTHS') {
                ['T', 'TH', 'S'].forEach(day => {
                    if (scheduleDays.includes(day)) {
                        conflictingDays.push(day);
                    }
                });
            }
        }
    });
    
    conflictingDays.forEach(day => {
        const checkbox = document.querySelector(`input[name="days[]"][value="${day}"]`);
        if (checkbox && !checkbox.dataset.required) {
            checkbox.disabled = true;
            checkbox.parentElement.style.opacity = '0.3';
            checkbox.parentElement.classList.add('conflict');
            checkbox.parentElement.title = 'Already has a course assigned at this time';
        }
    });
}


function populateTimeOptions(group) {
    const timeSelect = document.getElementById('timeStartSelect');
    if (!timeSelect) return;
    
    let timeOptions = [];
    
    if (group === 'MWF') {
        timeOptions = [
            { value: '08:00:00', label: '8:00 AM' },
            { value: '09:00:00', label: '9:00 AM' },
            { value: '10:00:00', label: '10:00 AM' },
            { value: '11:00:00', label: '11:00 AM' },
            { value: '13:00:00', label: '1:00 PM' },
            { value: '14:00:00', label: '2:00 PM' },
            { value: '15:00:00', label: '3:00 PM' },
            { value: '16:00:00', label: '4:00 PM' }
        ];
    } else if (group === 'TTHS') {
        timeOptions = [
            { value: '07:30:00', label: '7:30 AM' },
            { value: '09:00:00', label: '9:00 AM' },
            { value: '10:30:00', label: '10:30 AM' },
            { value: '13:00:00', label: '1:00 PM' },
            { value: '14:30:00', label: '2:30 PM' },
            { value: '16:00:00', label: '4:00 PM' }
        ];
    } else {
        timeOptions = [
            { value: '07:30:00', label: '7:30 AM' },
            { value: '08:00:00', label: '8:00 AM' },
            { value: '09:00:00', label: '9:00 AM' },
            { value: '10:00:00', label: '10:00 AM' },
            { value: '10:30:00', label: '10:30 AM' },
            { value: '11:00:00', label: '11:00 AM' },
            { value: '13:00:00', label: '1:00 PM' },
            { value: '14:00:00', label: '2:00 PM' },
            { value: '14:30:00', label: '2:30 PM' },
            { value: '15:00:00', label: '3:00 PM' },
            { value: '16:00:00', label: '4:00 PM' }
        ];
    }
    
    timeSelect.innerHTML = '<option value="">Select start time...</option>';
    timeOptions.forEach(option => {
        timeSelect.innerHTML += `<option value="${option.value}">${option.label}</option>`;
    });
}

function updateEndTimeOptions() {
    const endTimeSelect = document.getElementById('timeEndSelect');
    
    if (!endTimeSelect || !currentSelectedTimeSlot) {
        if (endTimeSelect) {
            endTimeSelect.innerHTML = '<option value="">Select end time...</option>';
        }
        return;
    }
    
    const startTime = currentSelectedTimeSlot;
    let endTimeOptions = [];
    const startHour = parseInt(startTime.split(':')[0]);
    const startMinute = parseInt(startTime.split(':')[1]);
    
    const isMorningSession = startHour >= 8 && startHour < 12;
    const isAfternoonSession = startHour >= 13 && startHour <= 17;
    
    if (currentSelectedDayGroup === 'MWF') {
        if (isMorningSession) {
            for (let h = startHour + 1; h <= 12; h++) {
                const endTime = `${h.toString().padStart(2, '0')}:00:00`;
                const duration = h - startHour;
                const hourLabel = duration === 1 ? 'hour' : 'hours';
                endTimeOptions.push({
                    value: endTime,
                    label: `${formatTime(endTime)} (${duration} ${hourLabel})`
                });
            }
        } else if (isAfternoonSession) {
            for (let h = startHour + 1; h <= 17; h++) {
                const endTime = `${h.toString().padStart(2, '0')}:00:00`;
                const duration = h - startHour;
                const hourLabel = duration === 1 ? 'hour' : 'hours';
                endTimeOptions.push({
                    value: endTime,
                    label: `${formatTime(endTime)} (${duration} ${hourLabel})`
                });
            }
        }
    } else if (currentSelectedDayGroup === 'TTHS') {
        const tthSchedule = {
            '07:30:00': [
                { end: '09:00:00', duration: '1.5 hours' },
                { end: '10:30:00', duration: '3 hours' },
                { end: '12:00:00', duration: '4.5 hours' }
            ],
            '09:00:00': [
                { end: '10:30:00', duration: '1.5 hours' },
                { end: '12:00:00', duration: '3 hours' }
            ],
            '10:30:00': [
                { end: '12:00:00', duration: '1.5 hours' }
            ],
            '13:00:00': [
                { end: '14:30:00', duration: '1.5 hours' },
                { end: '16:00:00', duration: '3 hours' },
                { end: '17:30:00', duration: '4.5 hours' }
            ],
            '14:30:00': [
                { end: '16:00:00', duration: '1.5 hours' },
                { end: '17:30:00', duration: '3 hours' }
            ],
            '16:00:00': [
                { end: '17:30:00', duration: '1.5 hours' }
            ]
        };
        
        const options = tthSchedule[startTime] || [];
        options.forEach(option => {
            endTimeOptions.push({
                value: option.end,
                label: `${formatTime(option.end)} (${option.duration})`
            });
        });
    }
    
    endTimeSelect.innerHTML = '<option value="">Select end time...</option>';
    endTimeOptions.forEach(option => {
        endTimeSelect.innerHTML += `<option value="${option.value}">${option.label}</option>`;
    });
}

function handleDaySelection(checkbox) {
    if (!checkbox.checked && checkbox.dataset.required === 'true') {
        checkbox.checked = true;
        return;
    }
    
    if (checkbox.dataset.required === 'true') {
        delete checkbox.dataset.required;
    }
    
    updateEndTimeOptions();
}

function addHours(timeString, hours) {
    const [h, m, s] = timeString.split(':').map(Number);
    const totalMinutes = h * 60 + m + (hours * 60);
    const newHours = Math.floor(totalMinutes / 60);
    const newMinutes = totalMinutes % 60;
    return `${String(newHours).padStart(2, '0')}:${String(newMinutes).padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
}

function viewSchedule(facultyId) {
    generateScheduleView(facultyId, 'schedule');
}

function viewCourseLoad(facultyId) {
    generateScheduleView(facultyId, 'courseload');
}

function loadCourseAndClassData() {
    const formData = new FormData();
    formData.append('action', 'get_courses_and_classes');
    
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const courseSelect = document.getElementById('courseSelect');
            const classSelect = document.getElementById('classSelect');
            
            if (courseSelect && data.courses) {
                courseSelect.innerHTML = '<option value="">Choose a course...</option>';
                data.courses.forEach(course => {
                    courseSelect.innerHTML += `
                        <option value="${course.course_code}" 
                                data-units="${course.units}" 
                                data-description="${course.course_description}">
                            ${course.course_code} - ${course.course_description}
                        </option>
                    `;
                });
            }
            
            if (classSelect && data.classes) {
                classSelect.innerHTML = '<option value="">Choose a class...</option>';
                data.classes.forEach(cls => {
                    classSelect.innerHTML += `
                        <option value="${cls.class_id}">
                            ${cls.class_code} - ${cls.class_name} (Year ${cls.year_level})
                        </option>
                    `;
                });
            }
        } else {
            showNotification(data.message || 'Failed to load data', 'error');
        }
    })
    .catch(error => {
        console.error('Error loading data:', error);
        showNotification('Failed to load courses and classes', 'error');
    });
    
    populateTimeOptions('default');
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
    
    const selectedDays = Array.from(form.querySelectorAll('input[name="days[]"]:checked'))
        .map(checkbox => checkbox.value);
    
    if (selectedDays.length === 0) {
        showNotification('Please select at least one day', 'error');
        return;
    }
    
    formData.delete('days[]');
    formData.append('days', selectedDays.join(''));
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
