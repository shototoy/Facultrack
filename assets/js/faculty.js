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
function openLocationModal() {
    document.getElementById('locationModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeLocationModal() {
    document.getElementById('locationModal').classList.remove('show');
    document.body.style.overflow = 'auto';
    document.getElementById('locationForm').reset();
}
async function viewLocationHistory() {
    document.getElementById('locationHistoryModal').classList.add('show');
    document.body.style.overflow = 'hidden';
    const historyList = document.querySelector('.location-history-list');
    historyList.innerHTML = '<div style="text-align: center; padding: 20px;">Loading...</div>';
    try {
        const response = await fetch('assets/php/polling_api.php?action=get_location_history');
        const result = await response.json();
        if (result.success) {
            historyList.innerHTML = result.html;
        } else {
            historyList.innerHTML = '<div class="no-history"><p>Failed to load history</p></div>';
        }
    } catch (error) {
        historyList.innerHTML = '<div class="no-history"><p>Error loading history</p></div>';
    }
}
function closeLocationHistoryModal() {
    document.getElementById('locationHistoryModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}
function openStatusModal() {
    document.getElementById('statusModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeStatusModal() {
    document.getElementById('statusModal').classList.remove('show');
    document.body.style.overflow = 'auto';
    document.getElementById('statusForm').reset();
}
async function updateStatus() {
    const statusSelect = document.getElementById('statusSelect');
    const selectedStatus = statusSelect.value;
    if (!selectedStatus) {
        showNotification('Please select a status', 'error');
        return;
    }
    try {
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_status&status=${encodeURIComponent(selectedStatus)}`
        });
        const result = await response.json();
        if (result.success) {
            showNotification(`Status updated to: ${selectedStatus}`, 'success');
            closeStatusModal();
        } else {
            throw new Error(result.message || 'Failed to update status');
        }
    } catch (error) {
        console.error('Status update error:', error);
        showNotification('Failed to update status. Please try again.', 'error');
    }
}
async function updateLocation() {
    const form = document.getElementById('locationForm');
    const formData = new FormData(form);
    const customLocation = formData.get('custom_location');
    const selectedLocation = formData.get('location');
    if (!customLocation && !selectedLocation) {
        showNotification('Please select a location or enter a custom location.', 'warning');
        return;
    }
    const finalLocation = customLocation || selectedLocation;
    try {
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_location&location=${encodeURIComponent(finalLocation)}`
        });
        const result = await response.json();
        if (result.success) {
            document.getElementById('currentLocation').textContent = finalLocation;
            closeLocationModal();
            showNotification('Location updated successfully!', 'success');
        } else {
            showNotification('Error updating location: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('An error occurred while updating location. Please try again.', 'error');
    }
}
async function markAttendance(scheduleId) {
    try {
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=mark_attendance&schedule_id=${scheduleId}`
        });
        const result = await response.json();
        if (result.success) {
            document.getElementById('currentLocation').textContent = result.location;
            showNotification(`Attendance marked! Location updated to ${result.location}`, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('An error occurred while marking attendance', 'error');
    }
}
async function switchScheduleTab(days, tabElement) {
    document.querySelectorAll('.schedule-tab').forEach(tab => tab.classList.remove('active'));
    tabElement.classList.add('active');
    const scheduleList = document.getElementById('scheduleList');
    scheduleList.innerHTML = '<div class="loading-state">Loading schedule...</div>';
    try {
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_schedule&days=${encodeURIComponent(days)}`
        });
        const result = await response.json();
        if (result.success && result.schedules) {
            scheduleList.innerHTML = generateScheduleHTML(result.schedules);
        } else {
            scheduleList.innerHTML = `
                <div class="no-schedule">
                    <div class="no-schedule-icon">📅</div>
                    <div class="no-schedule-text">No classes scheduled</div>
                    <div class="no-schedule-subtitle">for ${days}</div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error fetching schedule:', error);
        scheduleList.innerHTML = `
            <div class="error-state">
                <div class="error-icon">⚠️</div>
                <div class="error-text">Failed to load schedule</div>
                <div class="error-subtitle">Please try again</div>
            </div>
        `;
    }
}
function generateScheduleHTML(schedules) {
    if (!schedules || schedules.length === 0) {
        return '';
    }
    let html = '';
    schedules.forEach(schedule => {
        const statusInfo = getScheduleStatusClient(schedule.status);
        const duration = calculateDuration(schedule.time_start, schedule.time_end);
        html += `
            <div class="schedule-item ${statusInfo.class}">
                <div class="schedule-time">
                    <div class="time-display">${formatTimeClient(schedule.time_start)}</div>
                    <div class="time-duration">${duration}hr</div>
                </div>
                <div class="schedule-details">
                    <div class="schedule-course">
                        <div class="course-code">${escapeHtml(schedule.course_code)}</div>
                        <div class="course-name">${escapeHtml(schedule.course_description)}</div>
                    </div>
                    <div class="schedule-info">
                        <span class="class-info">${escapeHtml(schedule.class_code)}</span>
                        <span class="room-info">Room: ${escapeHtml(schedule.room || 'TBA')}</span>
                    </div>
                </div>
                <div class="schedule-status">
                    <span class="status-badge status-${statusInfo.class}">
                        ${statusInfo.text}
                    </span>
                    ${schedule.status === 'ongoing' ?
                `<button class="btn-small btn-primary" onclick="markAttendance(${schedule.schedule_id})">
                            Mark Present
                        </button>` : ''
            }
                </div>
            </div>
        `;
    });
    return html;
}
function getScheduleStatusClient(status) {
    switch (status) {
        case 'ongoing': return { text: 'In Progress', class: 'ongoing' };
        case 'upcoming': return { text: 'Upcoming', class: 'upcoming' };
        case 'finished': return { text: 'Completed', class: 'finished' };
        case 'pending': return { text: 'Loading...', class: 'pending' };
        case 'not-today': return { text: 'Not Today', class: 'not-today' };
        default: return { text: 'Unknown', class: 'unknown' };
    }
}
function calculateDuration(timeStart, timeEnd) {
    const start = new Date(`1970-01-01 ${timeStart}`);
    const end = new Date(`1970-01-01 ${timeEnd}`);
    const diffMs = end - start;
    const diffHours = diffMs / (1000 * 60 * 60);
    return diffHours.toFixed(1);
}
function formatTimeClient(time) {
    if (!time) return '';
    const [hours, minutes] = time.split(':');
    const hour12 = hours % 12 || 12;
    const ampm = hours >= 12 ? 'PM' : 'AM';
    return `${hour12}:${minutes} ${ampm}`;
}
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
document.addEventListener('DOMContentLoaded', function () {
    const locationSelect = document.getElementById('locationSelect');
    const customLocationInput = document.getElementById('customLocation');
    if (locationSelect && customLocationInput) {
        locationSelect.addEventListener('change', function () {
            if (this.value) {
                customLocationInput.value = '';
            }
        });
        customLocationInput.addEventListener('input', function () {
            if (this.value) {
                locationSelect.value = '';
            }
        });
    }
    const activeTab = document.querySelector('.schedule-tab.active');
    if (activeTab) {
        const days = activeTab.textContent.trim();
        switchScheduleTab(days, activeTab);
    }
});
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        if (e.target.id === 'locationModal') {
            closeLocationModal();
        } else if (e.target.id === 'locationHistoryModal') {
            closeLocationHistoryModal();
        } else if (e.target.id === 'statusModal') {
            closeStatusModal();
        }
    }
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.announcement-toggle');
    if (window.innerWidth > 768 &&
        !sidebar.contains(e.target) &&
        !toggle.contains(e.target) &&
        sidebar.classList.contains('open')) {
        closeSidebar();
    }
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal-overlay.show');
        if (openModal) {
            if (openModal.id === 'locationModal') {
                closeLocationModal();
            } else if (openModal.id === 'locationHistoryModal') {
                closeLocationHistoryModal();
            } else if (openModal.id === 'statusModal') {
                closeStatusModal();
            }
        }
    }
});
let lastScrollTop = 0;
let ticking = false;
function initializeContentPosition() {
    if (window.innerWidth <= 768) {
        const header = document.querySelector('.page-header');
        const contentWrapper = document.querySelector('.content-wrapper');
        if (header && contentWrapper) {
            const headerHeight = header.offsetHeight;
            const baseMargin = 20;
            contentWrapper.style.marginTop = `${headerHeight + baseMargin}px`;
            const actionsSection = document.querySelector('.actions-section');
            if (actionsSection) {
                actionsSection.classList.remove('scroll-visible');
            }
        }
    } else {
        const contentWrapper = document.querySelector('.content-wrapper');
        if (contentWrapper) {
            contentWrapper.style.removeProperty('margin-top');
        }
    }
}
function facultyScrollHandler() {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    if (window.innerWidth <= 768) {
        const header = document.querySelector('.page-header');
        const dashboardGrid = document.querySelector('.dashboard-grid');
        const actionsSection = document.querySelector('.actions-section');
        const locationSection = document.querySelector('.location-section');
        const body = document.body;
        const dashboardRect = dashboardGrid.getBoundingClientRect();
        const headerHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-height') || '160');
        const dashboardReachedTop = dashboardRect.top <= 0;
        if (dashboardReachedTop) {
            body.classList.add('dashboard-reached-top');
        } else {
            body.classList.remove('dashboard-reached-top');
        }
        if (header && actionsSection) {
            const scheduleSection = document.querySelector('.schedule-section');
            if (scrollTop > 200) {
                header.classList.add('scroll-hidden');
                actionsSection.classList.add('scroll-visible');
                if (scheduleSection) {
                    scheduleSection.classList.add('scroll-mode-active');
                }
            } else {
                header.classList.remove('scroll-hidden');
                actionsSection.classList.remove('scroll-visible');
                if (scheduleSection) {
                    scheduleSection.classList.remove('scroll-mode-active');
                }
            }
        }
    }
    lastScrollTop = scrollTop;
    ticking = false;
}
function requestTick() {
    if (!ticking) {
        requestAnimationFrame(facultyScrollHandler);
        ticking = true;
    }
}
document.addEventListener('DOMContentLoaded', function () {
    initializeContentPosition();
    window.addEventListener('resize', initializeContentPosition);
    window.addEventListener('scroll', requestTick, { passive: true });
});
function openIFTLOverlay() {
    const modal = document.getElementById('iftlModal');
    if (modal) {
        modal.classList.add('show');
        loadIFTLWeeks();
    }
}
function closeIFTLOverlay() {
    const modal = document.getElementById('iftlModal');
    if (modal) modal.classList.remove('show');
}
async function loadIFTLWeeks() {
    const select = document.getElementById('facultyIFTLWeekSelect');
    if (!select) return;
    select.innerHTML = '<option>Loading weeks...</option>';
    try {
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_iftl_weeks`
        });
        const result = await response.json();
        if (result.success) {
            select.innerHTML = '';
            result.weeks.forEach(week => {
                const option = document.createElement('option');
                option.value = week.identifier;
                option.textContent = week.label;
                option.dataset.startDate = week.start_date;
                if (week.is_current) option.selected = true;
                select.appendChild(option);
            });
            loadFacultyIFTLData();
        }
    } catch (e) {
        console.error(e);
        select.innerHTML = '<option>Error loading weeks</option>';
    }
}
async function loadFacultyIFTLData() {
    const weekSelect = document.getElementById('facultyIFTLWeekSelect');
    if (!weekSelect) return;
    const week = weekSelect.value;
    const content = document.getElementById('facultyIFTLContent');
    content.innerHTML = '<div class="loading">Loading schedule...</div>';
    try {
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_faculty_iftl&week=${week}`
        });
        const result = await response.json();
        if (result.success) {
            window.originalIFTLData = JSON.parse(JSON.stringify(result.entries || []));
            renderEditableIFTL(result.entries, result.compliance);
        } else {
            content.innerHTML = '<div class="error">Failed to load</div>';
        }
    } catch (e) {
        content.innerHTML = '<div class="error">Error loading</div>';
    }
}
function renderEditableIFTL(entries, compliance) {
    const content = document.getElementById('facultyIFTLContent');
    const status = compliance.status || 'Draft';
    const normalizedStatus = String(status || '').trim().toLowerCase();
    const isSubmittedState = normalizedStatus === 'submitted' || normalizedStatus === 're-submitted' || normalizedStatus === 'resubmitted';
    window.currentIFTLData = entries || [];
    window.currentIFTLStatus = status;
    const submitBtn = document.getElementById('iftlSubmitBtn');
    const resetBtn = document.getElementById('iftlResetBtn');
    if (submitBtn) {
        if (isSubmittedState) {
            submitBtn.textContent = 'Re-Submit';
            submitBtn.classList.add('resubmit-btn');
        } else {
            submitBtn.textContent = 'Submit';
            submitBtn.classList.remove('resubmit-btn');
        }
        submitBtn.disabled = false;
    }
    if (resetBtn) resetBtn.disabled = false;
    const statusBadgeHtml = (() => {
        if (normalizedStatus === 'submitted') {
            return '<span class="status-badge" style="background:#2e7d32;color:white;">Submitted</span>';
        }
        if (normalizedStatus === 're-submitted' || normalizedStatus === 'resubmitted') {
            return '<span class="status-badge" style="background:#fbc02d;color:#1f1f1f;">Re-Submitted</span>';
        }
        return '<span class="status-badge" style="background:#d32f2f;color:white;">Not Submitted</span>';
    })();
    let html = `
        <div class="iftl-status-header" style="margin-bottom:5px;">
            Status: ${statusBadgeHtml}
        </div>
        <table class="data-table iftl-table editable-grid">
            <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                <tr>
                    <th style="width: 12%; padding: 12px 4px;">Day</th>
                    <th style="width: 20%; padding: 12px 4px;">Time</th>
                    <th style="width: 18%; padding: 12px 4px;">Activity/Course</th>
                    <th style="width: 10%; padding: 12px 4px;"># Students</th>
                    <th style="width: 15%; padding: 12px 4px;">Class</th>
                    <th style="width: 15%; padding: 12px 4px;">Location</th>
                    <th style="width: 13%; padding: 12px 4px;">Remarks</th>
                    <th style="width: 5%; padding: 12px 4px;"></th>
                </tr>
            </thead>
            <tbody>
    `;
    window.currentIFTLData.forEach((entry, index) => {
        const isDisabled = false;
        const dayOptions = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
            .map(day => `<option value="${day}" ${entry.day_of_week === day ? 'selected' : ''}>${day}</option>`)
            .join('');
        const timeStartOptions = generateTimeOptions(entry.day_of_week, entry.time_start, 'start');
        const timeEndOptions = generateTimeOptions(entry.day_of_week, entry.time_end, 'end', entry.time_start);
        html += `
            <tr data-index="${index}" class="${entry.is_modified == 1 ? 'modified-row' : ''}" style="padding: 2px;">
                <td data-label="Day" style="padding: 4px; vertical-align: middle;">
                    <select class="form-select" ${isDisabled ? 'disabled' : ''} onchange="updateIFTLEntry(${index}, 'day_of_week', this.value)" style="width: 100%; padding: 4px; font-size: 0.9rem;">
                        ${dayOptions}
                    </select>
                </td>
                <td data-label="Time" style="padding: 4px; vertical-align: middle;">
                    <div style="display: flex; gap: 4px; align-items: center; width: 100%;">
                       <select class="form-select" ${isDisabled ? 'disabled' : ''} onchange="updateIFTLEntry(${index}, 'time_start', this.value)" style="flex: 1; padding: 4px; min-width: 0; font-size: 0.9rem;">${timeStartOptions}</select>
                       <span style="font-size: 0.8rem; line-height: 1;">-</span>
                       <select class="form-select" ${isDisabled ? 'disabled' : ''} onchange="updateIFTLEntry(${index}, 'time_end', this.value)" style="flex: 1; padding: 4px; min-width: 0; font-size: 0.9rem;">${timeEndOptions}</select>
                    </div>
                </td>
                <td data-label="Course" style="padding: 4px; vertical-align: middle;"><input type="text" class="form-input entry-course" value="${entry.course_code || ''}" ${isDisabled ? 'disabled' : ''} onchange="updateIFTLEntry(${index}, 'course_code', this.value)" oninput="updateIFTLEntry(${index}, 'course_code', this.value)" style="width:100%; padding: 4px; font-size: 0.9rem;" placeholder="Activity/Course"></td>
                <td data-label="# Students" style="padding: 4px; vertical-align: middle;">
                    <input type="number" min="0" class="form-input entry-students" value="${typeof entry.total_students !== 'undefined' ? entry.total_students : ''}" ${isDisabled ? 'disabled' : ''} onchange="updateIFTLEntry(${index}, 'total_students', this.value)" oninput="updateIFTLEntry(${index}, 'total_students', this.value)" style="width:100%; padding: 4px; font-size: 0.9rem;" placeholder="# Students">
                </td>
                <td data-label="Class" style="padding: 4px; vertical-align: middle;"><input type="text" class="form-input entry-class" value="${entry.class_code || entry.class_name || ''}" ${isDisabled ? 'disabled' : ''} onchange="updateIFTLEntry(${index}, 'class_code', this.value)" oninput="updateIFTLEntry(${index}, 'class_code', this.value)" style="width:100%; padding: 4px; font-size: 0.9rem;" placeholder="Class/Sec"></td>
                <td data-label="Location" style="padding: 4px; vertical-align: middle;"><input type="text" class="form-input entry-room" value="${entry.room || ''}" ${isDisabled ? 'disabled' : ''} onchange="updateIFTLEntry(${index}, 'room', this.value)" oninput="updateIFTLEntry(${index}, 'room', this.value)" style="width:100%; padding: 4px; font-size: 0.9rem;" placeholder="Location"></td>
                <td data-label="Remarks" style="padding: 4px; vertical-align: middle;"><input type="text" class="form-input entry-remarks" value="${entry.remarks || ''}" ${isDisabled ? 'disabled' : ''} onchange="updateIFTLEntry(${index}, 'remarks', this.value)" oninput="updateIFTLEntry(${index}, 'remarks', this.value)" placeholder="Remarks" style="width:100%; padding: 4px; font-size: 0.9rem;"></td>
                <td data-label="" style="padding: 4px; text-align: center; vertical-align: middle;">
                    ${!isDisabled ? `<button class="btn-delete" style="background: #e53935; color: white; border: none; padding: 6px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; width: 100%;" onclick="deleteIFTLEntry(${index})" title="Remove">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>` : ''}
                </td>
            </tr>
        `;
    });
    html += `</tbody></table>`;
    html += `
    <div style="position: sticky; bottom: 0; background: white; padding: 10px; border-top: 1px solid #eee; text-align: center; box-shadow: 0 -2px 5px rgba(0,0,0,0.05);">
        <button class="btn-secondary" style="width: 100%; padding: 10px;" onclick="addIFTLEntry()">+ Add New Entry</button>
    </div>
    `;
    content.innerHTML = html;
}
function addIFTLEntry() {
    syncIFTLRowsToMemory();
    if (!window.currentIFTLData) window.currentIFTLData = [];
    window.currentIFTLData.push({
        day_of_week: 'Monday',
        time_start: '08:00:00',
        time_end: '09:00:00',
        course_code: '',
        total_students: '',
        room: '',
        class_code: '',
        status: 'Regular',
        remarks: '',
        is_modified: 1
    });
    sortIFTLData();
    renderEditableIFTL(window.currentIFTLData, { status: window.currentIFTLStatus });
}
function syncIFTLRowsToMemory() {
    const rows = document.querySelectorAll('.editable-grid tbody tr[data-index]');
    rows.forEach(row => {
        const index = row.dataset.index;
        const entry = window.currentIFTLData && window.currentIFTLData[index];
        if (!entry) return;
        const courseInput = row.querySelector('.entry-course');
        if (courseInput) entry.course_code = courseInput.value;
        const studentsInput = row.querySelector('.entry-students');
        if (studentsInput) entry.total_students = studentsInput.value;
        const classInput = row.querySelector('.entry-class');
        if (classInput) entry.class_code = classInput.value;
        const roomInput = row.querySelector('.entry-room');
        if (roomInput) entry.room = roomInput.value;
        const remarksInput = row.querySelector('.entry-remarks');
        if (remarksInput) entry.remarks = remarksInput.value;
    });
}

function createIFTLEntryKey(entry) {
    return `${entry.day_of_week || ''}|${normalizeTimeValue(entry.time_start || '')}|${normalizeTimeValue(entry.time_end || '')}`;
}

function normalizeIFTLComparableEntry(entry) {
    return {
        day_of_week: entry.day_of_week || '',
        time_start: normalizeTimeValue(entry.time_start || ''),
        time_end: normalizeTimeValue(entry.time_end || ''),
        course_code: (entry.course_code || '').trim(),
        total_students: entry.total_students === '' || entry.total_students === null || typeof entry.total_students === 'undefined'
            ? null
            : parseInt(entry.total_students, 10),
        class_code: (entry.class_code || entry.class_name || '').trim(),
        room: (entry.room || '').trim(),
        remarks: (entry.remarks || '').trim()
    };
}

function areIFTLEntriesEquivalent(a, b) {
    return a.day_of_week === b.day_of_week &&
        a.time_start === b.time_start &&
        a.time_end === b.time_end &&
        a.course_code === b.course_code &&
        a.total_students === b.total_students &&
        a.class_code === b.class_code &&
        a.room === b.room &&
        a.remarks === b.remarks;
}

function buildModifiedIFTLPayload(currentEntries, originalEntries) {
    const current = Array.isArray(currentEntries) ? currentEntries : [];
    const original = Array.isArray(originalEntries) ? originalEntries : [];
    const originalMap = new Map();
    const currentMap = new Map();

    original.forEach(entry => {
        const normalized = normalizeIFTLComparableEntry(entry);
        originalMap.set(createIFTLEntryKey(normalized), normalized);
    });
    current.forEach(entry => {
        const normalized = normalizeIFTLComparableEntry(entry);
        currentMap.set(createIFTLEntryKey(normalized), normalized);
    });

    const modified = [];

    current.forEach(entry => {
        const normalized = normalizeIFTLComparableEntry(entry);
        const key = createIFTLEntryKey(normalized);
        const originalEntry = originalMap.get(key);
        const isChanged = !originalEntry || !areIFTLEntriesEquivalent(normalized, originalEntry) || Number(entry.is_modified || 0) === 1;
        if (isChanged) {
            modified.push({
                day_of_week: normalized.day_of_week,
                time_start: normalized.time_start,
                time_end: normalized.time_end,
                course_code: normalized.course_code || null,
                total_students: normalized.total_students,
                room: normalized.room || null,
                class_code: normalized.class_code || null,
                status: entry.status || 'Regular',
                remarks: normalized.remarks || null,
                is_modified: 1
            });
        }
    });

    originalMap.forEach((originalEntry, key) => {
        if (currentMap.has(key)) return;
        modified.push({
            day_of_week: originalEntry.day_of_week,
            time_start: originalEntry.time_start,
            time_end: originalEntry.time_end,
            course_code: null,
            total_students: null,
            room: null,
            class_code: null,
            status: 'Vacant',
            remarks: 'Removed',
            is_modified: 1
        });
    });

    return modified;
}
function normalizeTimeValue(timeValue) {
    if (!timeValue) return '';
    const value = String(timeValue).trim();
    if (/^\d{2}:\d{2}:\d{2}$/.test(value)) return value;
    if (/^\d{1,2}:\d{2}$/.test(value)) {
        const [h, m] = value.split(':');
        return `${h.padStart(2, '0')}:${m}:00`;
    }
    return value;
}
function getIFTLTimeOptionValues(dayOfWeek, mode = 'start', startTime = null) {
    const normalizedDay = String(dayOfWeek || '').trim().toLowerCase();
    const isTTHS = normalizedDay === 'tuesday' || normalizedDay === 'thursday' || normalizedDay === 'saturday';
    const startOptions = isTTHS
        ? ['07:30:00', '09:00:00', '10:30:00', '13:00:00', '14:30:00', '16:00:00']
        : ['08:00:00', '09:00:00', '10:00:00', '11:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00'];
    const endOptions = isTTHS
        ? ['09:00:00', '10:30:00', '12:00:00', '14:30:00', '16:00:00', '17:30:00']
        : ['09:00:00', '10:00:00', '11:00:00', '12:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00'];
    if (mode === 'end') {
        const normalizedStart = normalizeTimeValue(startTime);
        if (!normalizedStart) return endOptions;
        return endOptions.filter(time => time > normalizedStart);
    }
    return startOptions;
}
function generateTimeOptions(dayOfWeek, selectedTime, mode = 'start', startTime = null) {
    let options = '';
    const safeSelected = normalizeTimeValue(selectedTime || '');
    const values = getIFTLTimeOptionValues(dayOfWeek, mode, startTime);
    values.forEach(timeStr => {
        const displayTime = formatTimeClient(timeStr);
        const isSelected = safeSelected.startsWith(timeStr.substring(0, 5)) ? 'selected' : '';
        options += `<option value="${timeStr}" ${isSelected}>${displayTime}</option>`;
    });
    if (safeSelected && !values.includes(safeSelected)) {
        options += `<option value="${safeSelected}" selected>${formatTimeClient(safeSelected)}</option>`;
    }
    return options;
}
function sortIFTLData() {
    const dayMap = { 'Monday': 1, 'Tuesday': 2, 'Wednesday': 3, 'Thursday': 4, 'Friday': 5, 'Saturday': 6, 'Sunday': 7 };
    if (!window.currentIFTLData) return;
    window.currentIFTLData.sort((a, b) => {
        const da = dayMap[a.day_of_week] || 8;
        const db = dayMap[b.day_of_week] || 8;
        if (da !== db) return da - db;
        return (a.time_start || '').localeCompare(b.time_start || '');
    });
}
function updateIFTLEntry(index, field, value) {
    if (window.currentIFTLData[index]) {
        window.currentIFTLData[index][field] = value;
        window.currentIFTLData[index].is_modified = 1;
        if (field === 'day_of_week' || field === 'time_start') {
            const entry = window.currentIFTLData[index];
            const validStartTimes = getIFTLTimeOptionValues(entry.day_of_week, 'start');
            if (!validStartTimes.includes(normalizeTimeValue(entry.time_start))) {
                entry.time_start = validStartTimes[0] || entry.time_start;
            }
            const validEndTimes = getIFTLTimeOptionValues(entry.day_of_week, 'end', entry.time_start);
            if (!validEndTimes.includes(normalizeTimeValue(entry.time_end))) {
                entry.time_end = validEndTimes[0] || entry.time_end;
            }
        }
        if (field === 'day_of_week' || field === 'time_start' || field === 'time_end') {
            sortIFTLData();
            renderEditableIFTL(window.currentIFTLData, { status: window.currentIFTLStatus });
        }
    }
}
function deleteIFTLEntry(index) {
    const proceed = () => {
        syncIFTLRowsToMemory();
        window.currentIFTLData.splice(index, 1);
        renderEditableIFTL(window.currentIFTLData, { status: window.currentIFTLStatus });
    };
    if (typeof confirmAction === 'function') {
        confirmAction('Remove Entry', 'Remove this entry?', proceed);
        return;
    }
    proceed();
}
async function saveIFTLData(status, skipConfirm = false) {
    const normalizedStatus = String(window.currentIFTLStatus || '').trim().toLowerCase();
    const submitStatusToSave = (normalizedStatus === 'submitted' || normalizedStatus === 're-submitted' || normalizedStatus === 'resubmitted')
        ? 'Re-Submitted'
        : 'Submitted';
    if (status === 'Submitted' && !skipConfirm && typeof confirmAction === 'function') {
        confirmAction(
            submitStatusToSave === 'Re-Submitted' ? 'Re-Submit IFTL' : 'Submit IFTL',
            submitStatusToSave === 'Re-Submitted'
                ? 'Are you sure you want to re-submit your updated IFTL for this week?'
                : 'Are you sure you want to submit your IFTL for this week?',
            function () {
                saveIFTLData('Submitted', true);
            }
        );
        return;
    }
    syncIFTLRowsToMemory();
    const weekSelect = document.getElementById('facultyIFTLWeekSelect');
    const weekIdentifier = weekSelect.value;
    const weekStartDate = weekSelect.options[weekSelect.selectedIndex].dataset.startDate;
    try {
        const formData = new FormData();
        formData.append('action', 'save_iftl');
        formData.append('week_identifier', weekIdentifier);
        formData.append('week_start_date', weekStartDate);
        formData.append('status', status === 'Submitted' ? submitStatusToSave : status);
        const modifiedEntries = buildModifiedIFTLPayload(window.currentIFTLData, window.originalIFTLData);
        formData.append('entries', JSON.stringify(modifiedEntries));
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            if (typeof showNotification === 'function') {
                showNotification('IFTL Saved!', 'success');
            }
            loadFacultyIFTLData();
        } else {
            if (typeof showNotification === 'function') {
                showNotification('Error saving: ' + result.message, 'error');
            }
        }
    } catch (e) {
        console.error(e);
        if (typeof showNotification === 'function') {
            showNotification('Error saving IFTL', 'error');
        }
    }
}
async function regenerateIFTLWeek() {
    if (typeof confirmAction === 'function') {
        confirmAction(
            'Reset IFTL Week',
            'This will reset your custom entries for this week to the standard course load. Continue?',
            function () {
                regenerateIFTLWeekConfirmed();
            }
        );
        return;
    }
    regenerateIFTLWeekConfirmed();
}

async function regenerateIFTLWeekConfirmed() {
    const weekSelect = document.getElementById('facultyIFTLWeekSelect');
    const week = weekSelect.value;
    const content = document.getElementById('facultyIFTLContent');
    content.innerHTML = '<div class="loading">Resetting to standard schedule...</div>';
    try {
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_faculty_iftl&week=${encodeURIComponent(week)}&reset=1`
        });
        const result = await response.json();
        if (result.success) {
            renderEditableIFTL(result.entries, result.compliance);
            window.originalIFTLData = JSON.parse(JSON.stringify(result.entries || []));
            if (typeof showNotification === 'function') showNotification('Schedule reset to standard load', 'success');
        } else {
            content.innerHTML = '<div class="error">Failed to reset</div>';
        }
    } catch (e) {
        content.innerHTML = '<div class="error">Error resetting</div>';
    }
}
