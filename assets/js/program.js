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

function viewSchedule(facultyId) {
    const modal = document.getElementById('facultyScheduleModal');
    const content = document.getElementById('scheduleContent');
    const title = document.getElementById('scheduleModalTitle');
        modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    const facultyName = facultyNames[facultyId];
    const schedules = facultySchedules[facultyId] || [];
    
    title.textContent = `${facultyName} - Schedule`;
    
    if (schedules.length === 0) {
        content.innerHTML = `
            <div class="empty-state">
                <h3>No Schedule Found</h3>
                <p>This faculty member has no assigned classes.</p>
            </div>
        `;
    } else {
        let scheduleHTML = '<div class="schedule-list">';
        
        schedules.forEach(schedule => {
            scheduleHTML += `
                <div class="schedule-item-detail">
                    <div class="schedule-header">
                        <div class="course-info">
                            <div class="sched-course-code">${schedule.course_code}</div>
                            <div class="sched-course-name">${schedule.course_description}</div>
                        </div>
                        <div class="schedule-time">
                            <div class="days">${schedule.days.toUpperCase()}</div>
                            <div class="time">${formatTime(schedule.time_start)} - ${formatTime(schedule.time_end)}</div>
                        </div>
                    </div>
                    <div class="schedule-details">
                        <div class="detail-item">
                            <span class="label">Class:</span>
                            <span class="value">${schedule.class_name} (${schedule.class_code})</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Room:</span>
                            <span class="value">${schedule.room || 'Not specified'}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        scheduleHTML += '</div>';
        content.innerHTML = scheduleHTML;
    }
}

function formatTime(time) {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
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
