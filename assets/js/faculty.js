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
                        <span class="class-info">${escapeHtml(schedule.class_name)}</span>
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
    window.currentIFTLData = entries || [];
    window.currentIFTLStatus = status;
    const draftBtn = document.getElementById('iftlDraftBtn');
    const submitBtn = document.getElementById('iftlSubmitBtn');
    const resetBtn = document.getElementById('iftlResetBtn');
    const isLocked = status === 'Submitted' || status === 'Approved';
    if (draftBtn) draftBtn.disabled = isLocked;
    if (submitBtn) submitBtn.disabled = isLocked;
    if (resetBtn) resetBtn.disabled = isLocked;
    let html = `
        <div class="iftl-status-header" style="margin-bottom:5px;">
            Status: <strong>${status}</strong>
            ${status === 'Submitted' ? '<span class="status-badge" style="background:#2196F3;color:white;">Submitted - Locked</span>' : ''}
        </div>
        <table class="data-table iftl-table editable-grid">
            <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                <tr>
                    <th style="width: 12%; padding: 12px 4px;">Day</th>
                    <th style="width: 20%; padding: 12px 4px;">Time</th>
                    <th style="width: 20%; padding: 12px 4px;">Activity/Course</th>
                    <th style="width: 15%; padding: 12px 4px;">Class</th>
                    <th style="width: 15%; padding: 12px 4px;">Location</th>
                    <th style="width: 13%; padding: 12px 4px;">Remarks</th>
                    <th style="width: 5%; padding: 12px 4px;"></th>
                </tr>
            </thead>
            <tbody>
    `;
    window.currentIFTLData.forEach((entry, index) => {
        const isDisabled = status === 'Submitted' || status === 'Approved';
        const dayOptions = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
            .map(day => `<option value="${day}" ${entry.day_of_week === day ? 'selected' : ''}>${day}</option>`)
            .join('');
        const timeStartOptions = generateTimeOptions(entry.time_start);
        const timeEndOptions = generateTimeOptions(entry.time_end);
        html += `
            <tr data-index="${index}" class="${entry.is_modified == 1 ? 'modified-row' : ''}" style="padding: 2px;">
                <td style="padding: 4px; vertical-align: middle;">
                    <select class="form-select" ${isDisabled ? 'disabled' : ''} onchange="updateIFTLEntry(${index}, 'day_of_week', this.value)" style="width: 100%; padding: 4px; font-size: 0.9rem;">
                        ${dayOptions}
                    </select>
                </td>
                <td style="padding: 4px; vertical-align: middle;">
                    <div style="display: flex; gap: 4px; align-items: center; width: 100%;">
                       <select class="form-select" ${isDisabled ? 'disabled' : ''} onchange="updateIFTLEntry(${index}, 'time_start', this.value)" style="flex: 1; padding: 4px; min-width: 0; font-size: 0.9rem;">${timeStartOptions}</select>
                       <span style="font-size: 0.8rem; line-height: 1;">-</span>
                       <select class="form-select" ${isDisabled ? 'disabled' : ''} onchange="updateIFTLEntry(${index}, 'time_end', this.value)" style="flex: 1; padding: 4px; min-width: 0; font-size: 0.9rem;">${timeEndOptions}</select>
                    </div>
                </td>
                <td style="padding: 4px; vertical-align: middle;"><input type="text" class="form-input entry-course" value="${entry.course_code || entry.activity_type || ''}" ${isDisabled ? 'disabled' : ''} style="width:100%; padding: 4px; font-size: 0.9rem;" placeholder="Activity/Course"></td>
                <td style="padding: 4px; vertical-align: middle;"><input type="text" class="form-input entry-class" value="${entry.activity_type !== 'Class' ? entry.activity_type : ''}" ${isDisabled ? 'disabled' : ''} style="width:100%; padding: 4px; font-size: 0.9rem;" placeholder="Class/Sec"></td>
                <td style="padding: 4px; vertical-align: middle;"><input type="text" class="form-input entry-room" value="${entry.room || ''}" ${isDisabled ? 'disabled' : ''} style="width:100%; padding: 4px; font-size: 0.9rem;" placeholder="Location"></td>
                <td style="padding: 4px; vertical-align: middle;"><input type="text" class="form-input entry-remarks" value="${entry.remarks || ''}" ${isDisabled ? 'disabled' : ''} placeholder="Remarks" style="width:100%; padding: 4px; font-size: 0.9rem;"></td>
                <td style="padding: 4px; text-align: center; vertical-align: middle;">
                    ${!isDisabled ? `<button class="btn-delete" style="background: #e53935; color: white; border: none; padding: 6px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;" onclick="deleteIFTLEntry(${index})" title="Remove">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>` : ''}
                </td>
            </tr>
        `;
    });
    html += `</tbody></table>`;
    if (status !== 'Submitted' && status !== 'Approved') {
        html += `
        <div style="position: sticky; bottom: 0; background: white; padding: 10px; border-top: 1px solid #eee; text-align: center; box-shadow: 0 -2px 5px rgba(0,0,0,0.05);">
            <button class="btn-secondary" style="width: 100%; padding: 10px;" onclick="addIFTLEntry()">+ Add New Entry</button>
        </div>
        `;
    }
    content.innerHTML = html;
}
function addIFTLEntry() {
    if (!window.currentIFTLData) window.currentIFTLData = [];
    window.currentIFTLData.push({
        day_of_week: 'Monday',
        time_start: '08:00:00',
        time_end: '09:00:00',
        course_code: '',
        room: '',
        activity_type: '',
        status: 'Regular',
        remarks: '',
        is_modified: 1
    });
    sortIFTLData();
    renderEditableIFTL(window.currentIFTLData, { status: window.currentIFTLStatus });
}
function generateTimeOptions(selectedTime) {
    let options = '';
    const startHour = 7;
    const endHour = 21;
    for (let h = startHour; h <= endHour; h++) {
        for (let m = 0; m < 60; m += 30) {
            const timeStr = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:00`;
            const displayTime = formatTimeClient(timeStr);
            const safeSelected = selectedTime || '';
            const isSelected = safeSelected.startsWith(timeStr.substring(0, 5)) ? 'selected' : '';
            options += `<option value="${timeStr}" ${isSelected}>${displayTime}</option>`;
        }
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
        if (field === 'day_of_week' || field === 'time_start' || field === 'time_end') {
            sortIFTLData();
            renderEditableIFTL(window.currentIFTLData, { status: window.currentIFTLStatus });
        }
    }
}
function deleteIFTLEntry(index) {
    const proceed = () => {
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
    if (window.currentIFTLStatus === 'Submitted' && status === 'Submitted') {
        return;
    }
    if (status === 'Submitted' && !skipConfirm && typeof confirmAction === 'function') {
        confirmAction(
            'Submit IFTL',
            'Are you sure you want to submit? This will lock your IFTL for this week.',
            function () {
                saveIFTLData('Submitted', true);
            }
        );
        return;
    }
    const rows = document.querySelectorAll('.editable-grid tbody tr[data-index]');
    rows.forEach(row => {
        const index = row.dataset.index;
        const entry = window.currentIFTLData[index];
        if (entry) {
            const courseInput = row.querySelector('.entry-course');
            if (courseInput) entry.course_code = courseInput.value;
            const classInput = row.querySelector('.entry-class');
            if (classInput) entry.activity_type = classInput.value;
            const roomInput = row.querySelector('.entry-room');
            if (roomInput) entry.room = roomInput.value;
            entry.status = 'Regular';
            const remarksInput = row.querySelector('.entry-remarks');
            if (remarksInput) entry.remarks = remarksInput.value;
            entry.is_modified = 1;
        }
    });
    const weekSelect = document.getElementById('facultyIFTLWeekSelect');
    const weekIdentifier = weekSelect.value;
    const weekStartDate = weekSelect.options[weekSelect.selectedIndex].dataset.startDate;
    if (status === 'Submitted' && !skipConfirm) return;
    try {
        const formData = new FormData();
        formData.append('action', 'save_iftl');
        formData.append('week_identifier', weekIdentifier);
        formData.append('week_start_date', weekStartDate);
        formData.append('status', status);
        formData.append('entries', JSON.stringify(window.currentIFTLData));
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
            if (typeof showNotification === 'function') showNotification('Schedule reset to standard load', 'success');
        } else {
            content.innerHTML = '<div class="error">Failed to reset</div>';
        }
    } catch (e) {
        content.innerHTML = '<div class="error">Error resetting</div>';
    }
}
