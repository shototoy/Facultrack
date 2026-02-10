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
    const existingMainEmptyState = container.querySelector('.empty-state-container:not(.search-empty-state)');
    if (existingMainEmptyState && visibleCount > 0) {
    }
    if (visibleCount === 0 && searchTerm.trim() !== '') {
        if (typeof getEmptyStateHTML === 'function') {
            container.insertAdjacentHTML('beforeend', getEmptyStateHTML(title, message));
            const added = container.lastElementChild;
            if (added) added.classList.add('search-empty-state');
        } else {
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state search-empty-state';
            emptyState.innerHTML = `
                <h3>${title}</h3>
                <p>${message}</p>
            `;
            container.appendChild(emptyState);
        }
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
        populateCourseLoadModalContent(schedules, generateCourseLoadForm(facultyId));
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
        populateScheduleModalContent(schedules, generateScheduleSummary(schedules, facultyId));
    }
}
function populateScheduleModalContent(schedules, rightContent) {
    const mwfContainer = document.getElementById('mwfTableContainer');
    const tthContainer = document.getElementById('tthTableContainer');
    const mobileSummaryPanel = document.getElementById('mobileSummaryPanel');
    const desktopContent = document.querySelector('#facultyScheduleModal .desktop-grid-content');
    if (mwfContainer) {
        mwfContainer.innerHTML = generateScheduleMWFPageContent(schedules);
    }
    if (tthContainer) {
        tthContainer.innerHTML = generateScheduleTTHPageContent(schedules);
    }
    if (mobileSummaryPanel) {
        mobileSummaryPanel.innerHTML = rightContent;
    }
    if (desktopContent) {
        desktopContent.innerHTML = `
            <div class="modal-grid-container">
                <div class="schedule-tables">
                    ${generateScheduleTables(schedules, false)}
                </div>
                <div class="right-panel">
                    ${rightContent}
                </div>
            </div>
        `;
    }
}
function populateCourseLoadModalContent(schedules, rightContent) {
    const courseLoadMwfContainer = document.getElementById('courseLoadMwfTableContainer');
    const courseLoadTthContainer = document.getElementById('courseLoadTthTableContainer');
    const desktopContent = document.querySelector('#facultyCourseLoadModal .desktop-grid-content');
    if (courseLoadMwfContainer) {
        courseLoadMwfContainer.innerHTML = generateCourseLoadMWFPageContent(schedules);
    }
    if (courseLoadTthContainer) {
        courseLoadTthContainer.innerHTML = generateCourseLoadTTHPageContent(schedules);
    }
    if (desktopContent) {
        desktopContent.innerHTML = `
            <div class="modal-grid-container">
                <div class="schedule-tables">
                    ${generateScheduleTables(schedules, true)}
                </div>
                <div class="right-panel">
                    ${rightContent}
                </div>
            </div>
        `;
    }
}
function generateCourseLoadMWFPageContent(schedules) {
    return `
        <div class="schedule-table-wrapper">
            ${generateMWFScheduleTable(schedules, 'clickable-cell', 'onclick="handleCourseLoadTimeSlotClick(this)"')}
        </div>
        <div class="page-summary">
            <div class="summary-header">
                Click any time slot to assign courses
            </div>
        </div>
    `;
}
function generateCourseLoadTTHPageContent(schedules) {
    return `
        <div class="schedule-table-wrapper">
            ${generateTTHScheduleTable(schedules, 'clickable-cell', 'onclick="handleCourseLoadTimeSlotClick(this)"')}
        </div>
        <div class="page-summary">
            <div class="summary-header">
                Click any time slot to assign courses
            </div>
        </div>
    `;
}
function showCourseLoadPage(pageNumber) {
    document.querySelectorAll('#facultyCourseLoadModal .pagination-btn').forEach(btn => {
        btn.classList.remove('active');
        if (parseInt(btn.dataset.page) === pageNumber) {
            btn.classList.add('active');
        }
    });
    document.querySelectorAll('#facultyCourseLoadModal .mobile-page').forEach(page => {
        page.classList.remove('active');
    });
    const targetPage = document.querySelector(`#facultyCourseLoadModal .page-${pageNumber}`);
    if (targetPage) {
        targetPage.classList.add('active');
    } else {
    }
}
function handleCourseLoadTimeSlotClick(cell) {
    const timeSlot = cell.closest('tr').querySelector('.time-cell').textContent;
    const dayColumn = cell.cellIndex - 1;
    let day = '';
    const table = cell.closest('table');
    const headers = table.querySelectorAll('th');
    if (headers.length > dayColumn + 1) {
        const dayHeader = headers[dayColumn + 1].textContent.trim();
        if (dayHeader === 'M') day = 'Monday';
        else if (dayHeader === 'W') day = 'Wednesday';
        else if (dayHeader === 'F') day = 'Friday';
        else if (dayHeader === 'T') day = 'Tuesday';
        else if (dayHeader === 'TH') day = 'Thursday';
        else if (dayHeader === 'S') day = 'Saturday';
    }
    const existingCourse = findExistingCourse(cell, day, timeSlot);
    showCourseAssignmentPage(day, timeSlot, cell, existingCourse);
}
function findExistingCourse(cell, day, timeSlot) {
    const courseCodeDiv = cell.querySelector('.course-code');
    if (courseCodeDiv && courseCodeDiv.textContent.trim()) {
        const courseCode = courseCodeDiv.textContent.trim();
        const schedules = facultySchedules[currentFacultyId] || [];
        const timeValue = convertTimeSlotToValue(timeSlot);
        const existingSchedule = schedules.find(schedule => {
            return schedule.course_code === courseCode && schedule.time_start === timeValue;
        });
        if (existingSchedule) {
            return existingSchedule;
        } else {
            const courseMatch = schedules.find(schedule => schedule.course_code === courseCode);
            if (courseMatch) {
                return courseMatch;
            }
        }
    } else {
        const schedules = facultySchedules[currentFacultyId] || [];
        const timeValue = convertTimeSlotToValue(timeSlot);
        const dayMap = {
            'Monday': 'M',
            'Tuesday': 'T',
            'Wednesday': 'W',
            'Thursday': 'TH',
            'Friday': 'F',
            'Saturday': 'S'
        };
        const dayValue = dayMap[day];
        const occupyingSchedule = schedules.find(schedule => {
            const scheduleDays = schedule.days.toUpperCase();
            const scheduleStart = schedule.time_start;
            const scheduleEnd = schedule.time_end;
            return scheduleDays.includes(dayValue) &&
                timeValue >= scheduleStart &&
                timeValue < scheduleEnd;
        });
        if (occupyingSchedule) {
            return occupyingSchedule;
        }
    }
    return null;
}
function showAssignmentPanel(day, timeSlot, cell, existingCourse = null) {
    const isEditMode = existingCourse !== null;
    console.log('showAssignmentPanel called:', { day, timeSlot, existingCourse, isEditMode });
    const form = document.querySelector('.courseload-assignment-form');
    if (!form) {
        console.error('Course load form not found!');
        return;
    }
    const timeValue = convertTimeSlotToValue(timeSlot);
    const formHTML = generateAssignmentForm({
        isEditMode,
        existingCourse,
        day,
        timeSlot,
        timeValue,
        tableType: 'MWF',
        context: 'desktop'
    });
    form.innerHTML = `
        <div class="form-section">
            <h4 style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; font-size: 1.1em;">
                ${isEditMode ? 'Edit' : 'Assign'} Course to Faculty
                ${isEditMode ? '<span class="edit-indicator" style="background: #ff9800; color: white; padding: 3px 10px; border-radius: 4px; font-size: 0.75em; font-weight: 600;">EDIT</span>' : ''}
            </h4>
            <div class="time-selection-notice" style="padding: 8px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 12px; display: flex; align-items: center;">
                <span class="day-indicator" style="background: #2e7d32; color: white; padding: 3px 10px; border-radius: 4px; font-weight: 600; margin-right: 6px; font-size: 0.85em;">${day}</span>
                <span class="time-indicator" style="background: #2e7d32; color: white; padding: 3px 10px; border-radius: 4px; font-weight: 600; font-size: 0.85em;">${timeSlot}</span>
            </div>
            ${formHTML}
        </div>
    `;
    form.style.display = 'block';
    const formGroups = form.querySelectorAll('.form-group');
    formGroups.forEach(group => {
        group.style.marginBottom = '10px';
        const label = group.querySelector('label');
        if (label) {
            label.style.marginBottom = '4px';
            label.style.fontSize = '0.9em';
        }
        const select = group.querySelector('select');
        if (select) {
            select.style.padding = '6px 8px';
            select.style.fontSize = '0.9em';
        }
    });
    const formActions = form.querySelector('.form-actions');
    if (formActions) {
        formActions.style.marginTop = '12px';
        formActions.style.display = 'flex';
        formActions.style.gap = '8px';
        formActions.style.flexWrap = 'wrap';
        const buttons = formActions.querySelectorAll('button');
        buttons.forEach(btn => {
            btn.style.padding = '8px 16px';
            btn.style.fontSize = '0.9em';
        });
    }
    const dayMap = { 'Monday': 'M', 'Tuesday': 'T', 'Wednesday': 'W', 'Thursday': 'TH', 'Friday': 'F', 'Saturday': 'S' };
    const dayCheckboxes = form.querySelectorAll('input[name="days[]"]');
    dayCheckboxes.forEach(cb => {
        cb.checked = false;
        cb.disabled = false;
    });
    const dayCheckbox = form.querySelector(`input[name="days[]"][value="${dayMap[day]}"]`);
    if (dayCheckbox) {
        dayCheckbox.checked = true;
        if (!isEditMode) {
            dayCheckbox.disabled = true;
        }
        handleDayCheckboxChange(dayCheckbox);
    }
    loadAssignmentFormData('desktop', existingCourse);
}
function closeAssignmentPanel() {
    document.getElementById('assignmentPanel').style.display = 'none';
}
function showCourseAssignmentPage(day, timeSlot, cell, existingCourse = null) {
    const isEditMode = existingCourse !== null;
    if (window.innerWidth > 768) {
        showAssignmentPanel(day, timeSlot, cell, existingCourse);
        return;
    }
    const paginationNav = document.querySelector('#facultyCourseLoadModal .mobile-pagination-nav');
    if (paginationNav) {
        paginationNav.style.display = 'none';
    }
    const mobilePageContent = document.querySelector('#facultyCourseLoadModal .mobile-page-content');
    const desktopContent = document.querySelector('#facultyCourseLoadModal .desktop-grid-content');
    if (mobilePageContent) {
        mobilePageContent.style.display = 'none';
    }
    if (desktopContent) {
        desktopContent.style.display = 'none';
    }
    const courseLoadContent = document.getElementById('courseLoadContent');
    const assignmentPage = document.getElementById('courseAssignmentPage');
    if (!assignmentPage) {
        const newAssignmentPage = document.createElement('div');
        newAssignmentPage.id = 'courseAssignmentPage';
        newAssignmentPage.className = 'course-assignment-page';
        newAssignmentPage.style.display = 'none';
        courseLoadContent.appendChild(newAssignmentPage);
    }
    populateCourseAssignmentPage(day, timeSlot, cell, existingCourse);
    document.getElementById('courseAssignmentPage').style.display = 'block';
}
function populateCourseAssignmentPage(day, timeSlot, cell, existingCourse = null) {
    const assignmentPage = document.getElementById('courseAssignmentPage');
    const isEditMode = existingCourse !== null;
    const timeValue = convertTimeSlotToValue(timeSlot);
    const tableType = getTableTypeFromDay(day);
    const formHTML = generateAssignmentForm({
        isEditMode,
        existingCourse,
        day,
        timeSlot,
        timeValue,
        tableType,
        context: 'mobile'
    });
    assignmentPage.innerHTML = `
        <div class="assignment-page-header">
            <button class="back-btn" onclick="closeCourseAssignmentPage()">
                ← Back to Schedule
            </button>
            <h3>${isEditMode ? 'Edit' : 'Course'} Assignment</h3>
            <div class="selected-slot-info">
                <span class="day-indicator">${day}</span>
                <span class="time-indicator">${timeSlot}</span>
                ${isEditMode ? '<span class="edit-indicator">EDIT</span>' : ''}
            </div>
        </div>
        <div class="assignment-page-content">
            ${formHTML}
        </div>
    `;
    initializeAssignmentPageForm(day, timeValue, tableType, existingCourse);
    loadAssignmentFormData('mobile', existingCourse);
}
function convertTimeSlotToValue(timeSlot) {
    const timeParts = timeSlot.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
    if (timeParts) {
        let hours = parseInt(timeParts[1]);
        const minutes = timeParts[2];
        const period = timeParts[3].toLowerCase();
        if (period === 'pm' && hours !== 12) {
            hours += 12;
        } else if (period === 'am' && hours === 12) {
            hours = 0;
        }
        return `${String(hours).padStart(2, '0')}:${minutes}:00`;
    }
    return timeSlot;
}
function getTableTypeFromDay(day) {
    const mwfDays = ['Monday', 'Wednesday', 'Friday'];
    const tthDays = ['Tuesday', 'Thursday', 'Saturday'];
    if (mwfDays.includes(day)) {
        return 'MWF';
    } else if (tthDays.includes(day)) {
        return 'TTH';
    }
    return 'UNKNOWN';
}
function initializeAssignmentPageForm(day, timeValue, tableType, existingCourse = null) {
    const dayMap = {
        'Monday': 'M',
        'Tuesday': 'T',
        'Wednesday': 'W',
        'Thursday': 'TH',
        'Friday': 'F',
        'Saturday': 'S'
    };
    const allCheckboxes = document.querySelectorAll('#courseAssignmentPage input[name="days[]"]');
    if (tableType === 'MWF') {
        allCheckboxes.forEach(checkbox => {
            if (['T', 'TH', 'S'].includes(checkbox.value)) {
                checkbox.disabled = true;
                checkbox.parentElement.classList.add('disabled');
                checkbox.parentElement.style.opacity = '0.5';
                checkbox.parentElement.title = 'Not available for MWF schedule times';
            }
        });
    } else if (tableType === 'TTH') {
        allCheckboxes.forEach(checkbox => {
            if (['M', 'W', 'F'].includes(checkbox.value)) {
                checkbox.disabled = true;
                checkbox.parentElement.classList.add('disabled');
                checkbox.parentElement.style.opacity = '0.5';
                checkbox.parentElement.title = 'Not available for TTH schedule times';
            }
        });
    }
    if (existingCourse && existingCourse.days) {
        const existingDays = existingCourse.days.toUpperCase();
        allCheckboxes.forEach(checkbox => {
            if (existingDays.includes(checkbox.value)) {
                checkbox.checked = true;
                checkbox.disabled = false;
                checkbox.parentElement.style.opacity = '1';
            }
        });
        const firstChecked = document.querySelector('#courseAssignmentPage input[name="days[]"]:checked');
        if (firstChecked) {
            handleDayCheckboxChange(firstChecked);
        }
    } else {
        const dayValue = dayMap[day];
        if (dayValue) {
            const checkbox = document.querySelector(`#courseAssignmentPage input[name="days[]"][value="${dayValue}"]`);
            if (checkbox) {
                checkbox.checked = true;
                checkbox.disabled = false;
                checkbox.parentElement.style.opacity = '1';
                checkbox.dataset.required = 'true';
                handleDayCheckboxChange(checkbox);
            }
        }
    }
    updateEndTimeOptionsPage(tableType, timeValue, existingCourse);
}
function closeCourseAssignmentPage() {
    const assignmentPage = document.getElementById('courseAssignmentPage');
    if (assignmentPage) {
        assignmentPage.style.display = 'none';
    }
    if (window.innerWidth <= 768) {
        const paginationNav = document.querySelector('#facultyCourseLoadModal .mobile-pagination-nav');
        const mobilePageContent = document.querySelector('#facultyCourseLoadModal .mobile-page-content');
        if (paginationNav) {
            paginationNav.style.display = 'flex';
        }
        if (mobilePageContent) {
            mobilePageContent.style.display = 'flex';
        }
        const desktopContent = document.querySelector('#facultyCourseLoadModal .desktop-grid-content');
        if (desktopContent) {
            desktopContent.style.display = 'none';
        }
        const rightPanels = document.querySelectorAll('#facultyCourseLoadModal .right-panel');
        rightPanels.forEach(panel => {
            panel.style.display = 'none';
        });
    } else {
        const desktopContent = document.querySelector('#facultyCourseLoadModal .desktop-grid-content');
        if (desktopContent) {
            desktopContent.style.display = 'block';
        }
        const paginationNav = document.querySelector('#facultyCourseLoadModal .mobile-pagination-nav');
        const mobilePageContent = document.querySelector('#facultyCourseLoadModal .mobile-page-content');
        if (paginationNav) {
            paginationNav.style.display = 'none';
        }
        if (mobilePageContent) {
            mobilePageContent.style.display = 'none';
        }
    }
}
function generateScheduleMWFPageContent(schedules) {
    const mwfSchedules = schedules.filter(s => {
        const days = s.days.toUpperCase();
        return days.includes('M') || days.includes('W') || days.includes('F');
    });
    const totalUnits = mwfSchedules.reduce((sum, schedule) => sum + parseInt(schedule.units), 0);
    return `
        <div class="schedule-table-wrapper">
            ${generateMWFScheduleTable(schedules, '', '')}
        </div>
        <div class="page-summary">
            <div class="summary-header">
                ${mwfSchedules.length} subjects, ${totalUnits} units
            </div>
            <div class="subjects-list" data-count="${getDataCount(mwfSchedules.length)}">
                ${mwfSchedules.map(schedule => `
                    <div class="subject-item">
                        <div class="subject-code">${schedule.course_code}</div>
                        <div class="subject-details">${schedule.course_description} • ${schedule.units}u • ${formatTime(schedule.time_start)}-${formatTime(schedule.time_end)}</div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}
function generateScheduleTTHPageContent(schedules) {
    const tthSchedules = schedules.filter(s => {
        const days = s.days.toUpperCase();
        return days.includes('T') || days.includes('TH') || days.includes('S');
    });
    const totalUnits = tthSchedules.reduce((sum, schedule) => sum + parseInt(schedule.units), 0);
    return `
        <div class="schedule-table-wrapper">
            ${generateTTHScheduleTable(schedules, '', '')}
        </div>
        <div class="page-summary">
            <div class="summary-header">
                ${tthSchedules.length} subjects, ${totalUnits} units
            </div>
            <div class="subjects-list" data-count="${getDataCount(tthSchedules.length)}">
                ${tthSchedules.map(schedule => `
                    <div class="subject-item">
                        <div class="subject-code">${schedule.course_code}</div>
                        <div class="subject-details">${schedule.course_description} • ${schedule.units}u • ${formatTime(schedule.time_start)}-${formatTime(schedule.time_end)}</div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}
function generateScheduleTables(schedules, isClickable = false) {
    const clickableClass = isClickable ? 'clickable-cell' : '';
    const clickHandler = isClickable ? 'onclick="handleCourseLoadTimeSlotClick(this)"' : '';
    return `
        <div class="schedule-table-container">
            ${generateMWFScheduleTable(schedules, clickableClass, clickHandler)}
        </div>
        <div class="schedule-table-container">
            ${generateTTHScheduleTable(schedules, clickableClass, clickHandler)}
        </div>
    `;
}
function generateMWFScheduleTable(schedules, clickableClass, clickHandler) {
    const times = ['08:00:00', '09:00:00', '10:00:00', '11:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00'];
    let html = `<table class="schedule-table"><thead><tr><th>Time</th><th>M</th><th>W</th><th>F</th></tr></thead><tbody>`;
    const occupiedCells = new Map();
    times.forEach((time, timeIndex) => {
        const row = [];
        row.push(`<td class="time-cell">${formatTime(time)}</td>`);
        ['M', 'W', 'F'].forEach(day => {
            const cellKey = `${timeIndex}-${day}`;
            if (occupiedCells.get(cellKey)) {
                return;
            }
            const schedule = schedules.find(s => {
                const daysValue = s.days.toUpperCase();
                const dayMap = {
                    'M': ['M', 'MW', 'MF', 'MWF'],
                    'W': ['W', 'MW', 'WF', 'MWF'],
                    'F': ['F', 'MF', 'WF', 'MWF']
                };
                if (clickableClass) {
                    const startHour = parseInt(s.time_start.split(':')[0]);
                    const endHour = parseInt(s.time_end.split(':')[0]);
                    const currentHour = parseInt(time.split(':')[0]);
                    return dayMap[day]?.includes(daysValue) &&
                        currentHour >= startHour &&
                        currentHour < endHour;
                } else {
                    return dayMap[day]?.includes(daysValue) && s.time_start === time;
                }
            });
            if (schedule) {
                if (clickableClass) {
                    row.push(`<td class="${clickableClass}" ${clickHandler}>
                        <div class="sched-course-code">${schedule.course_code}</div>
                        <div class="sched-room-info">${schedule.room || 'TBA'}</div>
                    </td>`);
                } else {
                    const startHour = parseInt(schedule.time_start.split(':')[0]);
                    const endHour = parseInt(schedule.time_end.split(':')[0]);
                    const duration = endHour - startHour;
                    const days = schedule.days.toUpperCase();
                    let colspanCount = 1;
                    let affectedDays = [day];
                    if (days === 'MWF') {
                        colspanCount = 3;
                        affectedDays = ['M', 'W', 'F'];
                    } else if (days === 'MW') {
                        colspanCount = 2;
                        affectedDays = ['M', 'W'];
                    } else if (days === 'MF') {
                        colspanCount = 2;
                        affectedDays = ['M', 'F'];
                    } else if (days === 'WF') {
                        colspanCount = 2;
                        affectedDays = ['W', 'F'];
                    }
                    const rowspan = duration > 1 ? `rowspan="${duration}"` : '';
                    const colspan = colspanCount > 1 ? `colspan="${colspanCount}"` : '';
                    for (let i = 0; i < duration; i++) {
                        const futureTimeIndex = timeIndex + i;
                        if (futureTimeIndex < times.length) {
                            affectedDays.forEach(affectedDay => {
                                occupiedCells.set(`${futureTimeIndex}-${affectedDay}`, true);
                            });
                        }
                    }
                    row.push(`<td ${rowspan} ${colspan}>
                        <div class="sched-course-code">${schedule.course_code}</div>
                        <div class="sched-room-info">${schedule.room || 'TBA'}</div>
                    </td>`);
                    if (colspanCount === 2) {
                        if (days === 'MW' && day === 'M') {
                        } else if (days === 'MF' && day === 'M') {
                            if (!occupiedCells.get(`${timeIndex}-W`)) {
                                const clickable = clickHandler ? `class="${clickableClass}" data-time="${time}" data-day="W" data-group="MWF" ${clickHandler}` : '';
                                row.push(`<td ${clickable}></td>`);
                            }
                        } else if (days === 'WF' && day === 'W') {
                        }
                    } else if (colspanCount === 3) {
                    }
                }
            } else {
                const clickable = clickHandler ? `class="${clickableClass}" data-time="${time}" data-day="${day}" data-group="MWF" ${clickHandler}` : '';
                row.push(`<td ${clickable}></td>`);
            }
        });
        html += `<tr>${row.join('')}</tr>`;
    });
    return html + '</tbody></table>';
}
function generateTTHScheduleTable(schedules, clickableClass, clickHandler) {
    const times = ['07:30:00', '09:00:00', '10:30:00', '13:00:00', '14:30:00', '16:00:00'];
    let html = `<table class="schedule-table"><thead><tr><th>Time</th><th>T</th><th>TH</th><th>S</th></tr></thead><tbody>`;
    const occupiedCells = new Map();
    times.forEach((time, timeIndex) => {
        const row = [];
        row.push(`<td class="time-cell">${formatTime(time)}</td>`);
        ['T', 'TH', 'S'].forEach(day => {
            const cellKey = `${timeIndex}-${day}`;
            if (occupiedCells.get(cellKey)) {
                return;
            }
            const schedule = schedules.find(s => {
                const daysValue = s.days.toUpperCase();
                const dayMap = {
                    'T': ['T', 'TTH'],
                    'TH': ['TH', 'TTH'],
                    'S': ['S']
                };
                if (clickableClass) {
                    const startTime = s.time_start;
                    const endTime = s.time_end;
                    return dayMap[day]?.includes(daysValue) && isTimeInRange(time, startTime, endTime);
                } else {
                    return dayMap[day]?.includes(daysValue) && s.time_start === time;
                }
            });
            if (schedule) {
                if (clickableClass) {
                    row.push(`<td class="${clickableClass}" ${clickHandler}>
                        <div class="sched-course-code">${schedule.course_code}</div>
                        <div class="sched-room-info">${schedule.room || 'TBA'}</div>
                    </td>`);
                } else {
                    const tthMap = {
                        '07:30:00-09:00:00': 1,
                        '07:30:00-10:30:00': 2,
                        '07:30:00-12:00:00': 3,
                        '09:00:00-10:30:00': 1,
                        '09:00:00-12:00:00': 2,
                        '10:30:00-12:00:00': 1,
                        '13:00:00-14:30:00': 1,
                        '13:00:00-16:00:00': 2,
                        '13:00:00-17:30:00': 3,
                        '14:30:00-16:00:00': 1,
                        '14:30:00-17:30:00': 2,
                        '16:00:00-17:30:00': 1
                    };
                    const key = `${schedule.time_start}-${schedule.time_end}`;
                    const duration = tthMap[key] || 1;
                    const days = schedule.days.toUpperCase();
                    const colspanCount = days === 'TTH' ? 2 : 1;
                    const rowspan = duration > 1 ? `rowspan="${duration}"` : '';
                    const colspan = colspanCount > 1 ? `colspan="${colspanCount}"` : '';
                    for (let i = 0; i < duration; i++) {
                        const futureTimeIndex = timeIndex + i;
                        if (futureTimeIndex < times.length) {
                            if (days === 'TTH') {
                                occupiedCells.set(`${futureTimeIndex}-T`, true);
                                occupiedCells.set(`${futureTimeIndex}-TH`, true);
                            } else {
                                occupiedCells.set(`${futureTimeIndex}-${day}`, true);
                            }
                        }
                    }
                    row.push(`<td ${rowspan} ${colspan}>
                        <div class="sched-course-code">${schedule.course_code}</div>
                        <div class="sched-room-info">${schedule.room || 'TBA'}</div>
                    </td>`);
                    if (days === 'TTH' && day === 'T') {
                    }
                }
            } else {
                const clickable = clickHandler ? `class="${clickableClass}" data-time="${time}" data-day="${day}" data-group="TTHS" ${clickHandler}` : '';
                row.push(`<td ${clickable}></td>`);
            }
        });
        html += `<tr>${row.join('')}</tr>`;
    });
    return html + '</tbody></table>';
}
function isTimeInRange(currentTime, startTime, endTime) {
    const current = timeToMinutes(currentTime);
    const start = timeToMinutes(startTime);
    const end = timeToMinutes(endTime);
    return current >= start && current < end;
}
function timeToMinutes(time) {
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
}
function generateScheduleSummary(schedules, facultyId) {
    const totalUnits = schedules.reduce((sum, schedule) => sum + parseInt(schedule.units), 0);
    const totalSubjects = schedules.length;
    return `
        <div class="schedule-summary-content">
            <h4>Complete Schedule Summary</h4>
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
            <div class="subjects-list" data-count="${getDataCount(schedules.length)}">
                <h5>All Assigned Subjects</h5>
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
function generateMWFSummary(schedules) {
    const mwfSchedules = schedules.filter(s => {
        const days = s.days.toUpperCase();
        return days.includes('M') || days.includes('W') || days.includes('F');
    });
    return `
        <div class="subjects-list" data-count="${getDataCount(mwfSchedules.length)}">
            ${mwfSchedules.map(schedule => `
                <div class="subject-item">
                    <div class="subject-code">${schedule.course_code}</div>
                    <div class="subject-details">${schedule.course_description} • ${schedule.units}u • ${formatTime(schedule.time_start)}-${formatTime(schedule.time_end)}</div>
                </div>
            `).join('')}
        </div>
    `;
}
function generateTTHSummary(schedules) {
    const tthSchedules = schedules.filter(s => {
        const days = s.days.toUpperCase();
        return days.includes('T') || days.includes('TH') || days.includes('S');
    });
    return `
        <div class="subjects-list" data-count="${getDataCount(tthSchedules.length)}">
            ${tthSchedules.map(schedule => `
                <div class="subject-item">
                    <div class="subject-code">${schedule.course_code}</div>
                    <div class="subject-details">${schedule.course_description} • ${schedule.units}u • ${formatTime(schedule.time_start)}-${formatTime(schedule.time_end)}</div>
                </div>
            `).join('')}
        </div>
    `;
}
function getDataCount(count) {
    if (count <= 2) return count.toString();
    if (count <= 4) return count.toString();
    return 'data-count-large';
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
                            <select name="course_code" class="form-select" required id="courseSelect">
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
                            <select name="room" class="form-select" required>
                                <option value="">Select room...</option>
                            </select>
                            <label class="form-label" style="margin-top: 10px;">End Time *</label>
                            <select name="time_end" class="form-select" required id="timeEndSelect">
                                <option value="">Select end time...</option>
                            </select>
                        </div>
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
        return `<div class="sched-course-code">${schedule.course_code}</div><div class="sched-room-info">${schedule.room || 'TBA'}</div>`;
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
    loadRoomOptionsForCourseLoad();
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
function assignCourseToYearLevel(courseCode) {
    openModal('curriculumAssignModal');
    const modalTitle = document.getElementById('curriculumModalTitle');
    modalTitle.textContent = `Assign ${courseCode} to Curriculum`;
    const content = document.getElementById('curriculumAssignContent');
    content.innerHTML = '<div class="loading">Loading curriculum options...</div>';
    loadCurriculumAssignmentForm(courseCode);
}
function loadCurriculumAssignmentForm(courseCode) {
    const formData = new FormData();
    formData.append('action', 'get_curriculum_assignment_data');
    formData.append('course_code', courseCode);
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                generateCurriculumAssignmentForm(courseCode, data.existingAssignments);
            } else {
                document.getElementById('curriculumAssignContent').innerHTML =
                    '<div class="error">Failed to load curriculum data: ' + (data.message || 'Unknown error') + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('curriculumAssignContent').innerHTML =
                '<div class="error">Failed to load curriculum data. Please try again.</div>';
        });
}
function generateCurriculumAssignmentForm(courseCode, existingAssignments) {
    const content = document.getElementById('curriculumAssignContent');
    content.innerHTML = `
        <form id="curriculumAssignForm" onsubmit="event.preventDefault(); submitCurriculumAssignment(this, '${courseCode}');">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Year Level:</label>
                    <select name="year_level" class="form-select" required>
                        <option value="">Select Year Level</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Semester:</label>
                    <select name="semester" class="form-select" required>
                        <option value="">Select Semester</option>
                        <option value="1st">1st Semester</option>
                        <option value="2nd">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('curriculumAssignModal')">Cancel</button>
                <button type="submit" class="btn-primary">Add to Curriculum</button>
            </div>
        </form>
    `;
}
function submitCurriculumAssignment(form, courseCode) {
    const formData = new FormData(form);
    formData.append('action', 'assign_course_to_curriculum');
    formData.append('course_code', courseCode);
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Course assigned to curriculum successfully!', 'success');
                closeModal('curriculumAssignModal');
            } else {
                showNotification(data.message || 'Failed to assign course to curriculum', 'error');
            }
        })
        .catch(error => {
            showNotification('An error occurred while assigning the course to curriculum', 'error');
        });
}
function removeCurriculumAssignment(courseCode, curriculumId) {
    const formData = new FormData();
    formData.append('action', 'remove_curriculum_assignment');
    formData.append('curriculum_id', curriculumId);
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Course removed from curriculum successfully!', 'success');
                loadCurriculumAssignmentForm(courseCode);
            } else {
                showNotification(data.message || 'Failed to remove course from curriculum', 'error');
            }
        })
        .catch(error => {
            showNotification('An error occurred while removing the course', 'error');
        });
}
function deleteCourse(courseCode) {
    const courseCard = document.querySelector(`[data-course="${courseCode}"]`);
    if (!courseCard) {
        showNotification('Course not found', 'error');
        return;
    }
    let courseId = courseCard.dataset.courseId;
    if (!courseId) {
        showNotification('Course ID not found', 'error');
        return;
    }
    const formData = new FormData();
    formData.set('action', 'delete_course');
    formData.set('course_id', courseId);
    for (let pair of formData.entries()) {
    }
    fetch('assets/php/polling_api.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    showNotification('Course deleted successfully!', 'success');
                } else {
                    showNotification(data.message || 'Failed to delete course', 'error');
                }
            } catch (parseError) {
                showNotification('Invalid server response', 'error');
            }
        })
        .catch(error => {
            showNotification('Network error occurred while deleting the course', 'error');
        });
}
function loadCourseAssignments(courseCode, overlay) {
    const assignmentsContainer = overlay.querySelector('.assignments-preview');
    assignmentsContainer.innerHTML = '<div class="loading-assignments">Loading assignments...</div>';
    const formData = new FormData();
    formData.append('action', 'get_curriculum_assignment_data_with_classes');
    formData.append('course_code', courseCode);
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.existingAssignments.length > 0) {
                    assignmentsContainer.innerHTML = data.existingAssignments.map(assignment => `
                    <div class="assignment-item" style="padding: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text-green-secondary); margin-bottom: 4px;">
                                    ${assignment.class_names || 'No Classes Yet'}
                                </div>
                                <div style="font-size: 0.8rem; color: #666;">
                                    Year ${assignment.year_level} • ${assignment.semester} Semester
                                </div>
                            </div>
                            <button class="btn-danger small" onclick="removeCurriculumAssignment('${courseCode}', ${assignment.curriculum_id})">Remove</button>
                        </div>
                    </div>
                `).join('');
                } else {
                    assignmentsContainer.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;"><em>No curriculum assignments yet</em></div>';
                }
            } else {
                assignmentsContainer.innerHTML = '<div style="color: #dc3545; text-align: center;">Error loading assignments</div>';
            }
        })
        .catch(error => {
            assignmentsContainer.innerHTML = '<div style="color: #dc3545; text-align: center;">Error loading assignments</div>';
        });
}
document.addEventListener('click', function (event) {
    if (event.target.closest('.class-details-toggle, .course-details-toggle')) {
        return;
    }
    if (!event.target.closest('.class-card, .course-card')) {
        document.querySelectorAll('.class-details-overlay.show, .course-details-overlay.show').forEach(overlay => {
            overlay.classList.remove('show');
            const card = overlay.closest('.class-card, .course-card');
            const toggleButton = card.querySelector('.class-details-toggle, .course-details-toggle');
            if (toggleButton) {
                if (toggleButton.classList.contains('course-details-toggle')) {
                    toggleButton.innerHTML = 'View Assignments <span class="arrow">▼</span>';
                } else {
                    toggleButton.innerHTML = 'View Schedule Details <span class="arrow">▼</span>';
                }
            }
        });
    }
});
function loadCourseAndClassDataPage(existingCourse = null) {
    const currentFaculty = currentFacultyId;
    const currentTimeStart = existingCourse?.time_start || '';
    const currentTimeEnd = existingCourse?.time_end || '';
    const currentDays = existingCourse?.days || '';
    const isEditMode = existingCourse !== null;
    fetch('program.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_validated_options&faculty_id=${currentFaculty}&time_start=${currentTimeStart}&time_end=${currentTimeEnd}&days=${currentDays}&is_edit=${isEditMode}&original_course=${existingCourse?.course_code || ''}&original_time_start=${existingCourse?.time_start || ''}&original_days=${existingCourse?.days || ''}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const courseSelect = document.getElementById('courseSelectPage');
                const classSelect = document.getElementById('classSelectPage');
                const roomSelect = document.getElementById('roomSelectPage');
                if (courseSelect) {
                    courseSelect.innerHTML = '<option value="">Choose a course...</option>';
                    data.courses.forEach(course => {
                        const isSelected = existingCourse && existingCourse.course_code === course.course_code ? 'selected' : '';
                        courseSelect.innerHTML += `<option value="${course.course_code}" ${isSelected}>${course.course_code} - ${course.course_description}</option>`;
                    });
                    courseSelect.addEventListener('change', function () {
                        reloadValidatedOptionsForCourse(this.value);
                    });
                }
                if (classSelect) {
                    classSelect.innerHTML = '<option value="">Choose a class...</option>';
                    data.classes.forEach(cls => {
                        const isSelected = existingCourse && existingCourse.class_id == cls.class_id ? 'selected' : '';
                        classSelect.innerHTML += `<option value="${cls.class_id}" ${isSelected}>${cls.class_code} - ${cls.class_name} (Year ${cls.year_level})</option>`;
                    });
                    classSelect.addEventListener('change', validateAndCheckConflicts);
                }
                if (roomSelect) {
                    roomSelect.innerHTML = '<option value="">Select room...</option>';
                    data.rooms.forEach(room => {
                        const isSelected = existingCourse && existingCourse.room === room ? 'selected' : '';
                        roomSelect.innerHTML += `<option value="${room}" ${isSelected}>${room}</option>`;
                    });
                    roomSelect.addEventListener('change', validateAndCheckConflicts);
                }
                if (existingCourse) {
                    const timeStartSelect = document.getElementById('timeStartSelectPage');
                    if (timeStartSelect) {
                        populateAllStartTimeOptions(timeStartSelect, existingCourse);
                    }
                    setTimeout(() => validateAndCheckConflicts(), 100);
                }
            } else {
            }
        })
        .catch(error => {
        });
}
function reloadValidatedOptionsForCourse(selectedCourse) {
    if (!selectedCourse) return;
    const currentFaculty = currentFacultyId;
    const existingCourse = document.querySelector('input[name="original_course_code"]')?.value;
    const isEditMode = !!existingCourse;
    fetch('program.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_validated_options&faculty_id=${currentFaculty}&selected_course=${selectedCourse}&is_edit=${isEditMode}&original_course=${existingCourse || ''}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const classSelect = document.getElementById('classSelectPage');
                if (classSelect) {
                    classSelect.innerHTML = '<option value="">Choose a class...</option>';
                    data.classes.forEach(cls => {
                        classSelect.innerHTML += `<option value="${cls.class_id}">${cls.class_code} - ${cls.class_name} (Year ${cls.year_level})</option>`;
                    });
                }
                validateAndCheckConflicts();
            }
        })
        .catch(error => {
        });
}
function filterClassesForCourse(courseCode, existingCourse = null) {
    const classSelect = document.getElementById('classSelectPage');
    if (!classSelect || !courseCode || !window.classData) return;
    fetch('program.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_course_curriculum&course_code=${encodeURIComponent(courseCode)}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.curriculum) {
                const validYearLevels = data.curriculum.map(curr => curr.year_level);
                classSelect.innerHTML = '<option value="">Choose a class...</option>';
                window.classData.forEach(cls => {
                    if (validYearLevels.includes(parseInt(cls.year_level))) {
                        const isSelected = existingCourse && existingCourse.class_id == cls.class_id ? 'selected' : '';
                        classSelect.innerHTML += `<option value="${cls.class_id}" ${isSelected}>${cls.class_code} - ${cls.class_name} (Year ${cls.year_level})</option>`;
                    }
                });
            } else {
                classSelect.innerHTML = '<option value="">Choose a class...</option>';
                window.classData.forEach(cls => {
                    const isSelected = existingCourse && existingCourse.class_id == cls.class_id ? 'selected' : '';
                    classSelect.innerHTML += `<option value="${cls.class_id}" ${isSelected}>${cls.class_code} - ${cls.class_name} (Year ${cls.year_level})</option>`;
                });
            }
        })
        .catch(error => {
            classSelect.innerHTML = '<option value="">Choose a class...</option>';
            window.classData.forEach(cls => {
                const isSelected = existingCourse && existingCourse.class_id == cls.class_id ? 'selected' : '';
                classSelect.innerHTML += `<option value="${cls.class_id}" ${isSelected}>${cls.class_code} - ${cls.class_name} (Year ${cls.year_level})</option>`;
            });
        });
}
function loadRoomOptions(roomSelect, existingCourse) {
    fetch('program.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_room_options'
    })
        .then(response => response.json())
        .then(data => {
            const rooms = data.success ? data.rooms : [
                'Room 101', 'Room 102', 'Room 103', 'Room 104', 'Room 105',
                'Room 201', 'Room 202', 'Room 203', 'Room 204', 'Room 205',
                'Room 301', 'Room 302', 'Room 303', 'Room 304', 'Room 305',
                'Computer Lab 1', 'Computer Lab 2', 'Physics Lab', 'Chemistry Lab',
                'Library', 'Auditorium', 'Conference Room', 'TBA'
            ];
            roomSelect.innerHTML = '<option value="">Select room...</option>';
            rooms.forEach(room => {
                const isSelected = existingCourse && existingCourse.room === room ? 'selected' : '';
                roomSelect.innerHTML += `<option value="${room}" ${isSelected}>${room}</option>`;
            });
        })
        .catch(error => {
            const defaultRooms = [
                'Room 101', 'Room 102', 'Room 103', 'Room 104', 'Room 105',
                'Room 201', 'Room 202', 'Room 203', 'Room 204', 'Room 205',
                'Room 301', 'Room 302', 'Room 303', 'Room 304', 'Room 305',
                'Computer Lab 1', 'Computer Lab 2', 'Physics Lab', 'Chemistry Lab',
                'Library', 'Auditorium', 'Conference Room', 'TBA'
            ];
            roomSelect.innerHTML = '<option value="">Select room...</option>';
            defaultRooms.forEach(room => {
                const isSelected = existingCourse && existingCourse.room === room ? 'selected' : '';
                roomSelect.innerHTML += `<option value="${room}" ${isSelected}>${room}</option>`;
            });
        });
}
function validateAndCheckConflicts() {
    const classSelect = document.getElementById('classSelectPage');
    const courseSelect = document.getElementById('courseSelectPage');
    const endTimeSelect = document.getElementById('timeEndSelectPage');
    const roomSelect = document.getElementById('roomSelectPage');
    const submitBtn = document.querySelector('button[type="submit"]');
    const isEditMode = document.querySelector('input[name="is_edit_mode"]').value === 'true';
    if (submitBtn) {
        submitBtn.style.backgroundColor = '';
    }
    if (!courseSelect.value || !classSelect.value || !endTimeSelect.value) {
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Please complete all required fields';
            submitBtn.style.backgroundColor = '#6c757d';
        }
        return;
    }
    const selectedDays = Array.from(document.querySelectorAll('input[name="days[]"]:checked')).map(cb => cb.value).join('');
    if (!selectedDays) {
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Please select at least one day';
            submitBtn.style.backgroundColor = '#6c757d';
        }
        return;
    }
    const timeStart = document.querySelector('input[name="time_start"]').value;
    const timeEnd = endTimeSelect.value;
    validateWithBackend(courseSelect.value, classSelect.value, roomSelect.value, timeStart, timeEnd, selectedDays, isEditMode, submitBtn);
}
function populateAllStartTimeOptions(timeStartSelect, existingCourse) {
    const tableType = getTableTypeFromDay(
        ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'].find(day =>
            existingCourse.days.includes(['M', 'T', 'W', 'TH', 'F', 'S'][['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'].indexOf(day)])
        )
    );
    let timeOptions = [];
    if (tableType === 'MWF') {
        timeOptions = [
            { value: '08:00:00', label: '8:00 AM' },
            { value: '09:00:00', label: '9:00 AM' },
            { value: '10:00:00', label: '10:00 AM' },
            { value: '11:00:00', label: '11:00 AM' },
            { value: '12:00:00', label: '12:00 PM' },
            { value: '13:00:00', label: '1:00 PM' },
            { value: '14:00:00', label: '2:00 PM' },
            { value: '15:00:00', label: '3:00 PM' },
            { value: '16:00:00', label: '4:00 PM' }
        ];
    } else {
        timeOptions = [
            { value: '07:30:00', label: '7:30 AM' },
            { value: '09:00:00', label: '9:00 AM' },
            { value: '10:30:00', label: '10:30 AM' },
            { value: '12:00:00', label: '12:00 PM' },
            { value: '13:00:00', label: '1:00 PM' },
            { value: '14:30:00', label: '2:30 PM' },
            { value: '16:00:00', label: '4:00 PM' }
        ];
    }
    timeStartSelect.innerHTML = '<option value="">Select start time...</option>';
    timeOptions.forEach(option => {
        const isSelected = existingCourse && existingCourse.time_start === option.value ? 'selected' : '';
        timeStartSelect.innerHTML += `<option value="${option.value}" ${isSelected}>${option.label}</option>`;
    });
    timeStartSelect.addEventListener('change', function () {
        updateValidatedEndTimeOptions(this.value, tableType);
        validateAndCheckConflicts();
    });
}
function updateValidatedEndTimeOptions(startTime, tableType) {
    const endTimeSelect = document.getElementById('timeEndSelectPage');
    if (!endTimeSelect || !startTime) return;
    let allEndTimes = [];
    if (tableType === 'MWF') {
        allEndTimes = [
            { value: '09:00:00', label: '9:00 AM' },
            { value: '10:00:00', label: '10:00 AM' },
            { value: '11:00:00', label: '11:00 AM' },
            { value: '12:00:00', label: '12:00 PM' },
            { value: '14:00:00', label: '2:00 PM' },
            { value: '15:00:00', label: '3:00 PM' },
            { value: '16:00:00', label: '4:00 PM' },
            { value: '17:00:00', label: '5:00 PM' }
        ];
    } else {
        allEndTimes = [
            { value: '09:00:00', label: '9:00 AM' },
            { value: '10:30:00', label: '10:30 AM' },
            { value: '12:00:00', label: '12:00 PM' },
            { value: '14:30:00', label: '2:30 PM' },
            { value: '16:00:00', label: '4:00 PM' },
            { value: '17:30:00', label: '5:30 PM' }
        ];
    }
    const startHour = parseInt(startTime.split(':')[0]);
    const startMinutes = parseInt(startTime.split(':')[1]);
    const startTimeMinutes = startHour * 60 + startMinutes;
    const validEndTimes = allEndTimes.filter(endTime => {
        const endHour = parseInt(endTime.value.split(':')[0]);
        const endMin = parseInt(endTime.value.split(':')[1]);
        const endTimeMinutes = endHour * 60 + endMin;
        return endTimeMinutes > startTimeMinutes;
    });
    endTimeSelect.innerHTML = '<option value="">Select end time...</option>';
    validEndTimes.forEach(option => {
        endTimeSelect.innerHTML += `<option value="${option.value}">${option.label}</option>`;
    });
}
function validateWithBackend(courseCode, classId, room, timeStart, timeEnd, selectedDays, isEditMode, submitBtn) {
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Validating...';
        submitBtn.style.backgroundColor = '#ffc107';
    }
    if (isEditMode && !timeStart) {
        const timeStartSelect = document.getElementById('timeStartSelectPage');
        if (timeStartSelect) {
            timeStart = timeStartSelect.value;
        }
    }
    if (!courseCode || !classId || !timeStart || !timeEnd || !selectedDays) {
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Please complete all required fields';
            submitBtn.style.backgroundColor = '#6c757d';
        }
        return;
    }
    const validationData = new FormData();
    validationData.append('action', 'validate_schedule');
    validationData.append('faculty_id', currentFacultyId);
    validationData.append('course_code', courseCode);
    validationData.append('class_id', classId);
    validationData.append('time_start', timeStart);
    validationData.append('time_end', timeEnd);
    validationData.append('days', selectedDays);
    validationData.append('room', room);
    validationData.append('is_edit', isEditMode.toString());
    if (isEditMode) {
        const originalCourse = document.querySelector('input[name="original_course_code"]')?.value || '';
        const originalTimeStart = document.querySelector('input[name="original_time_start"]')?.value || '';
        const originalDays = document.querySelector('input[name="original_days"]')?.value || '';
        validationData.append('original_course', originalCourse);
        validationData.append('original_time_start', originalTimeStart);
        validationData.append('original_days', originalDays);
    }
    fetch('program.php', {
        method: 'POST',
        body: validationData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && !data.has_conflicts) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = isEditMode ? 'Update Course' : 'Assign Course';
                    submitBtn.style.backgroundColor = '#2e7d32';
                }
                showNotification('No conflicts detected. Ready to proceed.', 'success');
            } else {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Schedule Conflicts Detected';
                    submitBtn.style.backgroundColor = '#dc3545';
                }
                if (data.conflicts && data.conflicts.length > 0) {
                    const conflictList = data.conflicts.map((conflict, index) => `${index + 1}. ${conflict}`).join('\n');
                    showNotification(`Schedule conflicts detected:\n${conflictList}\n\nPlease choose different time, class, or room.`, 'error');
                } else {
                    showNotification(data.message || 'Schedule conflicts detected. Please adjust your selection.', 'error');
                }
            }
        })
        .catch(error => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = isEditMode ? 'Update Course (Validation Failed)' : 'Assign Course (Validation Failed)';
                submitBtn.style.backgroundColor = '#6c757d';
            }
            showNotification('Validation failed. Please try again.', 'error');
        });
}
function deleteCourseAssignment() {
    const originalCourseCode = document.querySelector('input[name="original_course_code"]').value;
    const originalTimeStart = document.querySelector('input[name="original_time_start"]').value;
    const originalDays = document.querySelector('input[name="original_days"]').value;
    const formData = new FormData();
    formData.append('action', 'delete_schedule');
    formData.append('faculty_id', currentFacultyId);
    formData.append('course_code', originalCourseCode);
    formData.append('time_start', originalTimeStart);
    formData.append('days', originalDays);
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Course assignment deleted successfully!', 'success');
                closeCourseAssignmentPage();
                regenerateCourseLoadModal();
            } else {
                showNotification(data.message || 'Failed to delete course assignment', 'error');
            }
        })
        .catch(error => {
            showNotification('An error occurred while deleting the course assignment', 'error');
        });
}
function updateCourseInfoPage() {
    return;
}
function updateEndTimeOptionsPage(group, startTime, existingCourse = null) {
    const endTimeSelect = document.getElementById('timeEndSelectPage');
    if (!endTimeSelect) return;
    let endTimeOptions = [];
    const startHour = parseInt(startTime.split(':')[0]);
    const startMinutes = timeToMinutes(startTime);
    const isAMStart = startHour < 12;
    if (group === 'MWF') {
        const mwfTimes = [
            { value: '09:00:00', label: '9:00 AM', isAM: true },
            { value: '10:00:00', label: '10:00 AM', isAM: true },
            { value: '11:00:00', label: '11:00 AM', isAM: true },
            { value: '12:00:00', label: '12:00 PM', isAM: false },
            { value: '14:00:00', label: '2:00 PM', isAM: false },
            { value: '15:00:00', label: '3:00 PM', isAM: false },
            { value: '16:00:00', label: '4:00 PM', isAM: false },
            { value: '17:00:00', label: '5:00 PM', isAM: false }
        ];
        endTimeOptions = mwfTimes.filter(time => {
            const endHour = parseInt(time.value.split(':')[0]);
            const isLater = endHour > startHour;
            if (isAMStart) {
                return isLater && endHour <= 12;
            }
            else {
                return isLater && endHour >= 12;
            }
        });
    } else {
        const tthTimes = [
            { value: '09:00:00', label: '9:00 AM', isAM: true },
            { value: '10:30:00', label: '10:30 AM', isAM: true },
            { value: '12:00:00', label: '12:00 PM', isAM: false },
            { value: '14:30:00', label: '2:30 PM', isAM: false },
            { value: '16:00:00', label: '4:00 PM', isAM: false },
            { value: '17:30:00', label: '5:30 PM', isAM: false }
        ];
        endTimeOptions = tthTimes.filter(time => {
            const endMinutes = timeToMinutes(time.value);
            const endHour = parseInt(time.value.split(':')[0]);
            const isLater = endMinutes > startMinutes;
            if (isAMStart) {
                return isLater && endHour <= 12;
            }
            else {
                return isLater && endHour >= 12;
            }
        });
    }
    endTimeSelect.innerHTML = '<option value="">Select end time...</option>';
    endTimeOptions.forEach(option => {
        const isSelected = existingCourse && existingCourse.time_end === option.value ? 'selected' : '';
        endTimeSelect.innerHTML += `<option value="${option.value}" ${isSelected}>${option.label}</option>`;
    });
}
function removeCourseAssignment() {
    const form = document.getElementById('courseAssignmentForm');
    const formData = new FormData(form);
    formData.append('action', 'remove_course_assignment');
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Course assignment removed successfully!', 'success');
                closeCourseAssignmentPage();
                regenerateCourseLoadModal();
            } else {
                showNotification(data.message || 'Failed to remove course assignment', 'error');
            }
        })
        .catch(error => {
            showNotification('An error occurred while removing the course assignment', 'error');
        });
}
function regenerateCourseLoadModal() {
    fetch('program.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_faculty_schedules'
    })
        .then(response => response.json())
        .then(freshData => {
            if (freshData.success) {
                facultySchedules = freshData.faculty_schedules;
                generateScheduleView(currentFacultyId, 'courseload');
            } else {
                generateScheduleView(currentFacultyId, 'courseload');
            }
        })
        .catch(error => {
            generateScheduleView(currentFacultyId, 'courseload');
        });
}
function submitCourseAssignmentFromPage(form, isEditMode = false) {
    const formData = new FormData(form);
    formData.append('action', 'assign_course_load');
    formData.append('is_edit_mode', isEditMode.toString());
    if (isEditMode) {
        const originalCourse = document.querySelector('input[name="original_course_code"]')?.value || '';
        const originalTimeStart = document.querySelector('input[name="original_time_start"]')?.value || '';
        const originalDays = document.querySelector('input[name="original_days"]')?.value || '';
        formData.append('original_course_code', originalCourse);
        formData.append('original_time_start', originalTimeStart);
        formData.append('original_days', originalDays);
    }
    const dayCheckboxes = form.querySelectorAll('input[name="days[]"]:checked');
    const days = Array.from(dayCheckboxes).map(cb => cb.value).join('');
    formData.delete('days[]');
    formData.append('days', days);
    if (days.length === 0) {
        showNotification('Please select at least one day', 'error');
        return;
    }
    const successMessage = isEditMode ? 'Course assignment updated successfully!' : 'Course assigned successfully!';
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(successMessage, 'success');
                closeCourseAssignmentPage();
                regenerateCourseLoadModal();
            } else {
                showNotification(data.message || `Failed to ${isEditMode ? 'update' : 'assign'} course`, 'error');
            }
        })
        .catch(error => {
            showNotification(`An error occurred while ${isEditMode ? 'updating' : 'assigning'} the course`, 'error');
        });
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
                    courseSelect.addEventListener('change', function () {
                        updateClassDropdownBasedOnCourse(this.value, data.classes);
                    });
                }
                if (classSelect && data.classes) {
                    classSelect.disabled = true;
                    classSelect.innerHTML = '<option value="">Select a course first...</option>';
                }
            } else {
                showNotification(data.message || 'Failed to load data', 'error');
            }
        })
        .catch(error => {
            showNotification('Failed to load courses and classes', 'error');
        });
    populateTimeOptions('default');
}
function updateClassDropdownBasedOnCourse(courseCode, allClasses) {
    const classSelect = document.getElementById('classSelect');
    if (!courseCode) {
        classSelect.disabled = true;
        classSelect.innerHTML = '<option value="">Select a course first...</option>';
        return;
    }
    classSelect.disabled = false;
    classSelect.innerHTML = '<option value="">Loading classes...</option>';
    const formData = new FormData();
    formData.append('action', 'get_classes_for_course');
    formData.append('course_code', courseCode);
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                classSelect.innerHTML = '<option value="">Choose a class...</option>';
                if (data.classes.length === 0) {
                    classSelect.innerHTML += '<option value="" disabled>No classes have this course in their curriculum</option>';
                } else {
                    data.classes.forEach(cls => {
                        classSelect.innerHTML += `
                        <option value="${cls.class_id}">
                            ${cls.class_code} - ${cls.class_name} (Year ${cls.year_level})
                        </option>
                    `;
                    });
                }
            } else {
                classSelect.innerHTML = '<option value="" disabled>Error loading classes</option>';
            }
        })
        .catch(error => {
            classSelect.innerHTML = '<option value="" disabled>Error loading classes</option>';
        });
}
function handleDayCheckboxChange(checkbox) {
    const mwfDays = ['M', 'W', 'F'];
    const tthDays = ['T', 'TH', 'S'];
    const form = checkbox.closest('form');
    const allCheckboxes = form.querySelectorAll('input[name="days[]"]');
    const checkedMWF = Array.from(allCheckboxes).filter(cb =>
        cb.checked && mwfDays.includes(cb.value)
    );
    const checkedTTH = Array.from(allCheckboxes).filter(cb =>
        cb.checked && tthDays.includes(cb.value)
    );
    if (checkedMWF.length > 0) {
        allCheckboxes.forEach(cb => {
            if (tthDays.includes(cb.value) && !cb.checked) {
                cb.disabled = true;
            }
        });
    } else if (checkedTTH.length > 0) {
        allCheckboxes.forEach(cb => {
            if (mwfDays.includes(cb.value) && !cb.checked) {
                cb.disabled = true;
            }
        });
    } else {
        allCheckboxes.forEach(cb => {
            cb.disabled = false;
        });
    }
}
function exportSchedule(facultyId) {
    const facultyName = facultyNames[facultyId] || 'Unknown Faculty';
    const schedules = facultySchedules[facultyId] || [];
    const { mwfSchedules, tthSchedules } = separateSchedulesByType(schedules);
    const summary = calculateSummaryData(schedules);
    const exportWindow = window.open('', '_blank', 'width=1200,height=800');
    exportWindow.document.write(generatePrintHTML(facultyName, mwfSchedules, tthSchedules, summary));
    exportWindow.document.close();
    exportWindow.onload = () => {
        if (typeof html2canvas === 'undefined') {
            const script = exportWindow.document.createElement('script');
            script.src = 'https:
            script.onload = () => {
                captureAndDownload(exportWindow, facultyName);
            };
            exportWindow.document.head.appendChild(script);
        } else {
            captureAndDownload(exportWindow, facultyName);
        }
    };
}
function captureAndDownload(targetWindow, facultyName) {
    targetWindow.html2canvas(targetWindow.document.body, {
        scale: 2,
        useCORS: true,
        logging: false,
        width: targetWindow.document.body.scrollWidth,
        height: targetWindow.document.body.scrollHeight
    }).then(canvas => {
        canvas.toBlob(blob => {
            const url = URL.createObjectURL(blob);
            const a = targetWindow.document.createElement('a');
            a.href = url;
            a.download = `${facultyName.replace(/\s+/g, '_')}_Schedule.png`;
            a.click();
            URL.revokeObjectURL(url);
            setTimeout(() => targetWindow.close(), 1000);
        });
    });
}
function printSchedule(facultyId) {
    printFacultySchedule(facultyId);
}
function formatTime(time) {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${hour >= 12 ? 'PM' : 'AM'}`;
}
function loadRoomOptionsForPanel(existingCourse = null) {
    const roomSelect = document.getElementById('roomSelect');
    if (!roomSelect) return;
    fetch('program.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_room_options'
    })
        .then(response => response.json())
        .then(data => {
            const rooms = data.success ? data.rooms : ['Room 101', 'Room 102', 'Room 103', 'Room 201', 'Room 202', 'Room 203', 'Computer Lab 1', 'Computer Lab 2', 'TBA'];
            roomSelect.innerHTML = '<option value="">Select room...</option>';
            rooms.forEach(room => {
                const selected = existingCourse && existingCourse.room === room ? 'selected' : '';
                roomSelect.innerHTML += `<option value="${room}" ${selected}>${room}</option>`;
            });
        })
        .catch(error => {
            roomSelect.innerHTML = '<option value="">Select room...</option>';
        });
}
function populateEndTimeOptionsForPanel(startTime, existingCourse = null) {
    const endTimeSelect = document.getElementById('endTimeSelect');
    if (!endTimeSelect || !startTime) return;
    const startHour = parseInt(startTime.split(':')[0]);
    const allEndTimes = [
        { value: '09:00:00', label: '9:00 AM' }, { value: '10:00:00', label: '10:00 AM' },
        { value: '11:00:00', label: '11:00 AM' }, { value: '12:00:00', label: '12:00 PM' },
        { value: '13:00:00', label: '1:00 PM' }, { value: '14:00:00', label: '2:00 PM' },
        { value: '15:00:00', label: '3:00 PM' }, { value: '16:00:00', label: '4:00 PM' },
        { value: '17:00:00', label: '5:00 PM' }
    ];
    endTimeSelect.innerHTML = '<option value="">Select end time...</option>';
    allEndTimes.forEach(time => {
        const timeHour = parseInt(time.value.split(':')[0]);
        if (timeHour > startHour) {
            const selected = existingCourse && existingCourse.time_end === time.value ? 'selected' : '';
            endTimeSelect.innerHTML += `<option value="${time.value}" ${selected}>${time.label}</option>`;
        }
    });
}
function submitAssignmentFromPanel() {
    const courseSelect = document.getElementById('courseSelect');
    const classSelect = document.getElementById('classSelect');
    const roomSelect = document.getElementById('roomSelect');
    const endTimeSelect = document.getElementById('endTimeSelect');
    const timeStart = document.getElementById('assignmentTimeStart').value;
    const day = document.getElementById('assignmentDay').value;
    const isEdit = document.getElementById('assignmentIsEdit').value === 'true';
    if (!courseSelect.value || !classSelect.value || !endTimeSelect.value) {
        showNotification('Please fill all required fields', 'error');
        return;
    }
    const dayMap = { 'Monday': 'M', 'Tuesday': 'T', 'Wednesday': 'W', 'Thursday': 'TH', 'Friday': 'F', 'Saturday': 'S' };
    const formData = new FormData();
    formData.append('action', 'assign_course_load');
    formData.append('faculty_id', currentFacultyId);
    formData.append('course_code', courseSelect.value);
    formData.append('class_id', classSelect.value);
    formData.append('room', roomSelect.value);
    formData.append('time_start', timeStart);
    formData.append('time_end', endTimeSelect.value);
    formData.append('days', dayMap[day]);
    formData.append('is_edit_mode', isEdit);
    if (isEdit) {
        formData.append('original_course_code', document.getElementById('originalCourseCode').value);
        formData.append('original_time_start', document.getElementById('originalTimeStart').value);
        formData.append('original_days', document.getElementById('originalDays').value);
    }
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(isEdit ? 'Course updated successfully!' : 'Course assigned successfully!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showNotification(data.message || 'Failed to assign course', 'error');
            }
        })
        .catch(error => {
            console.error('Assignment error:', error);
            showNotification('An error occurred', 'error');
        });
}
function deleteAssignmentFromPanel() {
    if (!confirm('Are you sure you want to delete this course assignment?')) {
        return;
    }
    const originalCourseCode = document.getElementById('originalCourseCode').value;
    const originalTimeStart = document.getElementById('originalTimeStart').value;
    const originalDays = document.getElementById('originalDays').value;
    const formData = new FormData();
    formData.append('action', 'delete_schedule');
    formData.append('faculty_id', currentFacultyId);
    formData.append('course_code', originalCourseCode);
    formData.append('time_start', originalTimeStart);
    formData.append('days', originalDays);
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Course assignment deleted successfully!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showNotification(data.message || 'Failed to delete assignment', 'error');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            showNotification('An error occurred', 'error');
        });
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
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                searchContent();
            }
        });
        searchInput.addEventListener('input', searchContent);
        searchInput.focus();
    }
    document.addEventListener('click', function (e) {
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
setInterval(function () {
    if (!document.querySelector('.modal-overlay.show')) {
    }
}, 300000);
document.addEventListener('click', function (e) {
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
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                searchContent();
            }
        });
        searchInput.addEventListener('input', searchContent);
        searchInput.focus();
    }
    document.addEventListener('click', function (e) {
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
setInterval(function () {
    if (!document.querySelector('.modal-overlay.show')) {
    }
}, 300000);
document.addEventListener('click', function (e) {
    const card = e.target.closest('.add-card');
    if (card && card.dataset.modal) {
        openModal(card.dataset.modal);
    }
});
function toggleCourseDetailsOverlay(button, courseCode) {
    const courseCard = button.closest('.course-card');
    const overlay = courseCard.querySelector('.course-details-overlay');
    const isCurrentlyVisible = overlay.classList.contains('overlay-visible');
    if (isCurrentlyVisible) {
        overlay.classList.remove('overlay-visible');
        button.innerHTML = 'View Assignments <span class="arrow">▼</span>';
    } else {
        loadCourseAssignments(courseCode, overlay);
        setTimeout(() => {
            overlay.classList.add('overlay-visible');
        }, 10);
        button.innerHTML = 'Hide Assignments <span class="arrow">▲</span>';
    }
}
function toggleClassDetailsOverlay(button) {
    const classCard = button.closest('.class-card');
    const overlay = classCard.querySelector('.class-details-overlay');
    const isCurrentlyVisible = overlay.classList.contains('overlay-visible');
    if (isCurrentlyVisible) {
        overlay.classList.remove('overlay-visible');
        button.innerHTML = 'View Schedule Details <span class="arrow">▼</span>';
    } else {
        overlay.classList.add('overlay-visible');
        button.innerHTML = 'Hide Schedule Details <span class="arrow">▲</span>';
    }
}
function showMobilePage(pageNumber) {
    document.querySelectorAll('.pagination-btn').forEach(btn => {
        btn.classList.remove('active');
        if (parseInt(btn.dataset.page) === pageNumber) {
            btn.classList.add('active');
        }
    });
    document.querySelectorAll('.mobile-page').forEach(page => {
        page.classList.remove('active');
    });
    const targetPage = document.querySelector(`.page-${pageNumber}`);
    if (targetPage) {
        targetPage.classList.add('active');
    }
}
function loadCourseAssignments(courseCode, overlay) {
    const assignmentsDiv = overlay.querySelector('.assignments-preview');
    fetch('program.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_curriculum_assignment_data_with_classes&course_code=${encodeURIComponent(courseCode)}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.existingAssignments.length > 0) {
                    let html = '';
                    data.existingAssignments.forEach(assignment => {
                        html += `
                        <div class="assignment-item">
                            <div class="assignment-info">
                                <strong>Year ${assignment.year_level} - ${assignment.semester} Semester</strong><br>
                                <span style="color: #666;">Year Level: ${assignment.year_level} • Semester: ${assignment.semester}</span>
                                ${assignment.class_names ? `<br><span style="color: #2e7d32;">Classes: ${assignment.class_names}</span>` : ''}
                            </div>
                            <button class="remove-assignment-btn" onclick="removeCurriculumAssignment(${assignment.curriculum_id}, '${courseCode}')">
                                Remove
                            </button>
                        </div>
                    `;
                    });
                    assignmentsDiv.innerHTML = html;
                } else {
                    assignmentsDiv.innerHTML = '<div style="text-align: center; color: #666; padding: 20px;">No curriculum assignments found</div>';
                }
            } else {
                assignmentsDiv.innerHTML = '<div style="text-align: center; color: #d32f2f; padding: 20px;">Error loading assignments</div>';
            }
        })
        .catch(error => {
            assignmentsDiv.innerHTML = '<div style="text-align: center; color: #d32f2f; padding: 20px;">Error loading assignments</div>';
        });
}
function addNewRowToTable(type, data) {
    if (data) {
        setTimeout(() => {
        }, 1000);
    }
}
function updateStatistics() {
}
function loadRoomOptionsForCourseLoad() {
    const roomSelect = document.querySelector('.courseload-assignment-form select[name="room"]');
    if (!roomSelect) return;
    fetch('program.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_room_options'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                roomSelect.innerHTML = '<option value="">Select room...</option>';
                data.rooms.forEach(room => {
                    roomSelect.innerHTML += `<option value="${room}">${room}</option>`;
                });
            } else {
                const defaultRooms = ['NR102', 'NR103', 'NR104', 'NR105', 'Computer Lab 1', 'Computer Lab 2', 'Library', 'Auditorium', 'TBA'];
                roomSelect.innerHTML = '<option value="">Select room...</option>';
                defaultRooms.forEach(room => {
                    roomSelect.innerHTML += `<option value="${room}">${room}</option>`;
                });
            }
        })
        .catch(error => {
            console.error('Error loading room options:', error);
            const defaultRooms = ['NR102', 'NR103', 'NR104', 'NR105', 'Computer Lab 1', 'Computer Lab 2', 'Library', 'Auditorium', 'TBA'];
            roomSelect.innerHTML = '<option value="">Select room...</option>';
            defaultRooms.forEach(room => {
                roomSelect.innerHTML += `<option value="${room}">${room}</option>`;
            });
        });
}
function generateAssignmentForm(options) {
    const { isEditMode, existingCourse, day, timeSlot, timeValue, tableType, context } = options;
    const idSuffix = context === 'mobile' ? 'Page' : '';
    return `
        <form id="assignmentForm${idSuffix}" onsubmit="event.preventDefault(); submitAssignment(this, ${isEditMode});" class="assignment-form-unified">
            <div class="form-section-main">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Course *</label>
                        <select name="course_code" class="form-select" required id="courseSelect${idSuffix}" onchange="validateAndCheckConflicts()">
                            <option value="">Choose a course...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Class *</label>
                        <select name="class_id" class="form-select" required id="classSelect${idSuffix}" onchange="validateAndCheckConflicts()">
                            <option value="">Choose a class...</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Days *${context === 'mobile' ? ` (${tableType} schedule only)` : ''}</label>
                        <div class="days-checkbox-group">
                            <label class="day-checkbox">
                                <input type="checkbox" name="days[]" value="M" onchange="handleDayCheckboxChange(this)"> M
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="days[]" value="T" onchange="handleDayCheckboxChange(this)"> T
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="days[]" value="W" onchange="handleDayCheckboxChange(this)"> W
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="days[]" value="TH" onchange="handleDayCheckboxChange(this)"> TH
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="days[]" value="F" onchange="handleDayCheckboxChange(this)"> F
                            </label>
                            <label class="day-checkbox">
                                <input type="checkbox" name="days[]" value="S" onchange="handleDayCheckboxChange(this)"> S
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Room</label>
                        <select name="room" class="form-select" id="roomSelect${idSuffix}">
                            <option value="">Select room...</option>
                        </select>
                        <label class="form-label" style="margin-top: 10px;">End Time *</label>
                        <select name="time_end" class="form-select" required id="timeEndSelect${idSuffix}" onchange="validateAndCheckConflicts()">
                            <option value="">Select end time...</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="time_start" id="hiddenTimeStart${idSuffix}" value="${timeValue}">
                <input type="hidden" name="faculty_id" value="${currentFacultyId}">
                ${context === 'mobile' ? `<input type="hidden" name="table_type" value="${tableType}">` : ''}
                <input type="hidden" name="is_edit_mode" value="${isEditMode}">
                ${isEditMode ? `<input type="hidden" name="original_course_code" value="${existingCourse.course_code}">` : ''}
                ${isEditMode ? `<input type="hidden" name="original_time_start" value="${existingCourse.time_start}">` : ''}
                ${isEditMode ? `<input type="hidden" name="original_days" value="${existingCourse.days}">` : ''}
                <div class="form-actions${context === 'mobile' ? '-page' : ''}">
                    ${context === 'mobile' ? '<button type="button" class="btn-secondary" onclick="closeCourseAssignmentPage()">Cancel</button>' : '<button type="button" class="btn-secondary" onclick="closeModal(\'facultyCourseLoadModal\')">Cancel</button>'}
                    <button type="submit" class="btn-primary">${isEditMode ? 'Update Course' : 'Assign Course'}</button>
                    ${isEditMode ? '<button type="button" class="btn-delete-orange" style="background: #ff9800; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;" onclick="deleteAssignment()">Delete Course Assignment</button>' : ''}
                </div>
            </div>
        </form>
    `;
}
function loadAssignmentFormData(context, existingCourse) {
    const idSuffix = context === 'mobile' ? 'Page' : '';
    fetch('program.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_courses_and_classes'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const courseSelect = document.getElementById(`courseSelect${idSuffix}`);
                const classSelect = document.getElementById(`classSelect${idSuffix}`);
                if (courseSelect) {
                    courseSelect.innerHTML = '<option value="">Choose a course...</option>';
                    data.courses.forEach(course => {
                        const selected = existingCourse && existingCourse.course_code === course.course_code ? 'selected' : '';
                        courseSelect.innerHTML += `<option value="${course.course_code}" ${selected}>${course.course_code} - ${course.course_description}</option>`;
                    });
                    courseSelect.addEventListener('change', function () {
                        updateClassDropdownUnified(this.value, context, existingCourse);
                    });
                    if (existingCourse) {
                        updateClassDropdownUnified(existingCourse.course_code, context, existingCourse);
                    }
                }
                if (classSelect && !existingCourse) {
                    classSelect.disabled = true;
                    classSelect.innerHTML = '<option value="">Select a course first...</option>';
                }
            }
        })
        .catch(error => console.error('Error loading courses:', error));
    loadRoomOptionsUnified(context, existingCourse);
    const timeStart = document.getElementById(`hiddenTimeStart${idSuffix}`).value;
    populateEndTimeOptionsUnified(timeStart, context, existingCourse);
}
function updateClassDropdownUnified(courseCode, context, existingCourse = null) {
    const idSuffix = context === 'mobile' ? 'Page' : '';
    const classSelect = document.getElementById(`classSelect${idSuffix}`);
    if (!courseCode) {
        classSelect.disabled = true;
        classSelect.innerHTML = '<option value="">Select a course first...</option>';
        return;
    }
    classSelect.disabled = false;
    classSelect.innerHTML = '<option value="">Loading classes...</option>';
    fetch('program.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_classes_for_course&course_code=${courseCode}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                classSelect.innerHTML = '<option value="">Choose a class...</option>';
                if (data.classes.length === 0) {
                    classSelect.innerHTML += '<option value="" disabled>No classes have this course in their curriculum</option>';
                } else {
                    data.classes.forEach(cls => {
                        classSelect.innerHTML += `<option value="${cls.class_id}">${cls.class_code} - ${cls.class_name} (Year ${cls.year_level})</option>`;
                    });
                    if (existingCourse && existingCourse.class_code) {
                        const matchingClass = data.classes.find(cls => cls.class_code === existingCourse.class_code);
                        if (matchingClass) {
                            setTimeout(() => {
                                classSelect.value = matchingClass.class_id;
                            }, 50);
                        }
                    }
                }
            }
        })
        .catch(error => classSelect.innerHTML = '<option value="" disabled>Error loading classes</option>');
}
function loadRoomOptionsUnified(context, existingCourse = null) {
    const idSuffix = context === 'mobile' ? 'Page' : '';
    const roomSelect = document.getElementById(`roomSelect${idSuffix}`);
    if (!roomSelect) return;
    fetch('program.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_room_options'
    })
        .then(response => response.json())
        .then(data => {
            const rooms = data.success ? data.rooms : ['Room 101', 'Room 102', 'Room 103', 'Room 201', 'Room 202', 'Room 203', 'Computer Lab 1', 'Computer Lab 2', 'TBA'];
            roomSelect.innerHTML = '<option value="">Select room...</option>';
            rooms.forEach(room => {
                const selected = existingCourse && existingCourse.room === room ? 'selected' : '';
                roomSelect.innerHTML += `<option value="${room}" ${selected}>${room}</option>`;
            });
        })
        .catch(error => roomSelect.innerHTML = '<option value="">Select room...</option>');
}
function populateEndTimeOptionsUnified(startTime, context, existingCourse = null) {
    const idSuffix = context === 'mobile' ? 'Page' : '';
    const endTimeSelect = document.getElementById(`timeEndSelect${idSuffix}`);
    if (!endTimeSelect || !startTime) return;
    const startHour = parseInt(startTime.split(':')[0]);
    const allEndTimes = [
        { value: '09:00:00', label: '9:00 AM' }, { value: '10:00:00', label: '10:00 AM' },
        { value: '11:00:00', label: '11:00 AM' }, { value: '12:00:00', label: '12:00 PM' },
        { value: '13:00:00', label: '1:00 PM' }, { value: '14:00:00', label: '2:00 PM' },
        { value: '15:00:00', label: '3:00 PM' }, { value: '16:00:00', label: '4:00 PM' },
        { value: '17:00:00', label: '5:00 PM' }
    ];
    endTimeSelect.innerHTML = '<option value="">Select end time...</option>';
    allEndTimes.forEach(time => {
        const timeHour = parseInt(time.value.split(':')[0]);
        if (timeHour > startHour) {
            const selected = existingCourse && existingCourse.time_end === time.value ? 'selected' : '';
            endTimeSelect.innerHTML += `<option value="${time.value}" ${selected}>${time.label}</option>`;
        }
    });
}
function submitAssignment(formElement, isEditMode) {
    const formData = new FormData(formElement);
    const selectedDays = Array.from(formElement.querySelectorAll('input[name="days[]"]:checked'))
        .map(checkbox => checkbox.value);
    if (selectedDays.length === 0) {
        showNotification('Please select at least one day', 'error');
        return;
    }
    formData.delete('days[]');
    formData.append('days', selectedDays.join(''));
    formData.append('action', 'assign_course_load');
    formData.append('faculty_id', currentFacultyId);
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(isEditMode ? 'Course updated successfully!' : 'Course assigned successfully!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showNotification(data.message || 'Failed to assign course', 'error');
            }
        })
        .catch(error => {
            console.error('Assignment error:', error);
            showNotification('An error occurred', 'error');
        });
}
function deleteAssignment() {
    if (!confirm('Are you sure you want to delete this course assignment?')) {
        return;
    }
    const form = document.querySelector('form[id^="assignmentForm"]');
    const originalCourse = form.querySelector('input[name="original_course_code"]').value;
    const originalTime = form.querySelector('input[name="original_time_start"]').value;
    const originalDays = form.querySelector('input[name="original_days"]').value;
    const formData = new FormData();
    formData.append('action', 'delete_schedule');
    formData.append('faculty_id', currentFacultyId);
    formData.append('course_code', originalCourse);
    formData.append('time_start', originalTime);
    formData.append('days', originalDays);
    fetch('program.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Course assignment deleted successfully!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showNotification(data.message || 'Failed to delete assignment', 'error');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            showNotification('An error occurred', 'error');
        });
}

