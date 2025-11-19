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
            const locationUpdated = document.querySelector('.location-updated');
            locationUpdated.textContent = 'Last updated: Just now';
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
            const locationUpdated = document.querySelector('.location-updated');
            locationUpdated.textContent = 'Last updated: Just now';
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
        case 'ongoing': return {text: 'In Progress', class: 'ongoing'};
        case 'upcoming': return {text: 'Upcoming', class: 'upcoming'};
        case 'finished': return {text: 'Completed', class: 'finished'};
        case 'pending': return {text: 'Loading...', class: 'pending'};
        case 'not-today': return {text: 'Not Today', class: 'not-today'};
        default: return {text: 'Unknown', class: 'unknown'};
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
document.addEventListener('DOMContentLoaded', function() {
    const locationSelect = document.getElementById('locationSelect');
    const customLocationInput = document.getElementById('customLocation');
    if (locationSelect && customLocationInput) {
        locationSelect.addEventListener('change', function() {
            if (this.value) {
                customLocationInput.value = '';
            }
        });
        customLocationInput.addEventListener('input', function() {
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
document.addEventListener('click', function(e) {
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
document.addEventListener('keydown', function(e) {
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
document.addEventListener('DOMContentLoaded', function() {
    initializeContentPosition();
    window.addEventListener('resize', initializeContentPosition);
    window.addEventListener('scroll', requestTick, { passive: true });
});