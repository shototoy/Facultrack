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

function viewLocationHistory() {
    document.getElementById('locationHistoryModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeLocationHistoryModal() {
    document.getElementById('locationHistoryModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

async function updateLocation() {
    const form = document.getElementById('locationForm');
    const formData = new FormData(form);
    const customLocation = formData.get('custom_location');
    const selectedLocation = formData.get('location');
    
    if (!customLocation && !selectedLocation) {
        alert('Please select a location or enter a custom location.');
        return;
    }
    
    const finalLocation = customLocation || selectedLocation;
    
    try {
        const response = await fetch('assets/php/faculty_update_location.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `location=${encodeURIComponent(finalLocation)}`
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById('currentLocation').textContent = finalLocation;
            const locationUpdated = document.querySelector('.location-updated');
            locationUpdated.textContent = 'Last updated: Just now';
            closeLocationModal();
            showNotification('Location updated successfully!', 'success');
        } else {
            alert('Error updating location: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while updating location. Please try again.');
    }
}

function viewFullSchedule() {
    // TODO: Implement full schedule view
    showNotification('Full schedule view coming soon!', 'info');
}

function updateProfile() {
    // TODO: Implement profile update
    showNotification('Profile update feature coming soon!', 'info');
}

function viewStudents() {
    // TODO: Implement student list view
    showNotification('Student management feature coming soon!', 'info');
}

function leaveRequest() {
    // TODO: Implement leave request form
    showNotification('Leave request feature coming soon!', 'info');
}

function markAttendance(scheduleId) {
    // TODO: Implement attendance marking
    if (confirm('Mark yourself as present for this class?')) {
        showNotification('Attendance marked successfully!', 'success');
    }
}

function showNotification(message, type = 'info') {
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
        </div>
    `;
    document.body.appendChild(notification);
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 3000);
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
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        if (e.target.id === 'locationModal') {
            closeLocationModal();
        } else if (e.target.id === 'locationHistoryModal') {
            closeLocationHistoryModal();
        }
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
            }
        }
    }
});

document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.announcement-toggle');

    if (window.innerWidth > 768 &&
        !sidebar.contains(e.target) &&
        !toggle.contains(e.target) &&
        sidebar.classList.contains('open')) {
        closeSidebar();
    }
});

setInterval(function() {
    if (document.visibilityState === 'visible') {
        updateLocationStatus();
    }
}, 300000);

async function updateLocationStatus() {
    try {
        const response = await fetch('assets/php/faculty_get_status.php');
        const result = await response.json();
        
        if (result.success) {
            const locationUpdated = document.querySelector('.location-updated');
            if (locationUpdated) {
                locationUpdated.textContent = `Last updated: ${result.last_updated}`;
            }
        }
    } catch (error) {
        console.error('Error updating location status:', error);
    }
}
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        updateLocationStatus();
    }
});


//TODO: I modify ning showNotif
document.addEventListener('DOMContentLoaded', function() {
    console.log('Faculty dashboard loaded');
    const ongoingClasses = document.querySelectorAll('.schedule-item.ongoing');
    if (ongoingClasses.length > 0) {
        showNotification(`You have ${ongoingClasses.length} ongoing class(es)`, 'info');
    }
});

async function switchScheduleTab(days, tabElement) {
    document.querySelectorAll('.schedule-tab').forEach(tab => tab.classList.remove('active'));
    tabElement.classList.add('active');
    try {
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_schedule&days=${encodeURIComponent(days)}`
        });
        const result = await response.json();
        const scheduleList = document.getElementById('scheduleList');
        if (result.success) {
            scheduleList.innerHTML = result.html;
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
        console.error('Error loading schedule:', error);
    }
}

async function markAttendance(scheduleId) {
    if (!confirm('Mark yourself as present for this class?')) {
        return;
    }
    
    try {
        const scheduleResponse = await fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=mark_attendance&schedule_id=${scheduleId}`
        });

        const scheduleResult = await scheduleResponse.json();

        if (scheduleResult.success) {
            const updateResponse = await fetch(window.location.pathname, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_location&location=${encodeURIComponent(scheduleResult.location)}`
            });

            const updateResult = await updateResponse.json();
            
            if (updateResult.success) {
                document.getElementById('currentLocation').textContent = scheduleResult.location;
                const locationUpdated = document.querySelector('.location-updated');
                locationUpdated.textContent = 'Last updated: Just now';
                showNotification(`Attendance marked! Location updated to ${scheduleResult.location}`, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Attendance marked but location update failed', 'warning');
            }
        } else {
            showNotification('Error: ' + scheduleResult.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred while marking attendance', 'error');
    }
}