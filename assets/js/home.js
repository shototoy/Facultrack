// Home/Class Dashboard JavaScript

function contactFaculty(email) {
    if (email) {
        window.location.href = `mailto:${email}`;
    } else {
        showNotification('Contact information not available', 'info');
    }
}

function showNotification(message, type = 'info') {
    // Remove any existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;

    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Update faculty cards with real-time data
function updateFacultyCards(facultyData, facultyCourses) {
    const facultyContainer = document.querySelector('.faculty-grid');
    if (!facultyContainer) return;

    facultyData.forEach(faculty => {
        const facultyCard = facultyContainer.querySelector(`[data-faculty-id="${faculty.faculty_id}"]`);
        if (facultyCard) {
            // Update status badge
            const statusBadge = facultyCard.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = `status-badge status-${faculty.status.toLowerCase()}`;
                statusBadge.textContent = faculty.status;
            }

            // Update location
            const locationElement = facultyCard.querySelector('.current-location');
            if (locationElement) {
                locationElement.textContent = faculty.current_location;
            }

            // Update time info
            const timeInfo = facultyCard.querySelector('.time-info');
            if (timeInfo) {
                timeInfo.textContent = `Last updated: ${faculty.last_updated}`;
            }

            // Update courses
            const courses = facultyCourses[faculty.faculty_id] || [];
            updateCourseItems(facultyCard, courses);
        }
    });
}

function updateCourseItems(facultyCard, courses) {
    const coursesSection = facultyCard.querySelector('.courses-section');
    if (!coursesSection) return;

    // Remove existing course items
    const existingCourses = coursesSection.querySelectorAll('.course-item');
    existingCourses.forEach(course => course.remove());

    // Add updated course items
    if (courses.length > 0) {
        courses.forEach(course => {
            const courseElement = document.createElement('div');
            courseElement.className = `course-item course-${course.status}`;
            
            let statusText = '';
            switch (course.status) {
                case 'current': statusText = 'In Progress'; break;
                case 'upcoming': statusText = 'Upcoming'; break;
                case 'finished': statusText = 'Completed'; break;
                default: statusText = 'Not Today';
            }
            
            courseElement.innerHTML = `
                <div class="course-code">${course.course_code}</div>
                <div class="course-description">${course.course_description}</div>
                <div class="course-time">${formatTime(course.time_start)} - ${formatTime(course.time_end)}</div>
                <div class="course-room">Room: ${course.room || 'TBA'}</div>
                <div class="course-status status-${course.status}">${statusText}</div>
            `;
            
            coursesSection.appendChild(courseElement);
        });
    } else {
        const noCourses = document.createElement('div');
        noCourses.className = 'no-courses';
        noCourses.textContent = 'No classes scheduled for today';
        coursesSection.appendChild(noCourses);
    }
}

function formatTime(timeString) {
    if (!timeString) return '';
    
    const [hours, minutes] = timeString.split(':');
    const hour24 = parseInt(hours);
    const hour12 = hour24 === 0 ? 12 : hour24 > 12 ? hour24 - 12 : hour24;
    const ampm = hour24 >= 12 ? 'PM' : 'AM';
    
    return `${hour12}:${minutes} ${ampm}`;
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Class Dashboard loaded');
});

// Handle live polling updates specifically for class dashboard
window.addEventListener('facultyUpdatesReceived', function(event) {
    const data = event.detail;
    if (data.success) {
        updateFacultyCards(data.faculty_data, data.faculty_courses);
    }
});