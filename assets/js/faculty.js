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
        const response = await fetch(window.location.pathname + '?action=get_location_history');
        const result = await response.json();
        
        if (result.success) {
            historyList.innerHTML = result.html;
        } else {
            historyList.innerHTML = '<div class="no-history"><p>Failed to load history</p></div>';
        }
    } catch (error) {
        console.error('Error:', error);
        historyList.innerHTML = '<div class="no-history"><p>Error loading history</p></div>';
    }
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
        const response = await fetch(window.location.pathname, {
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
            alert('Error updating location: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while updating location. Please try again.');
    }
}

async function markAttendance(scheduleId) {
    if (!confirm('Mark yourself as present for this class?')) {
        return;
    }
    
    try {
        const response = await fetch(window.location.pathname, {
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
        console.error('Error:', error);
        showNotification('An error occurred while marking attendance', 'error');
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

async function updateLocationStatus() {
    try {
        const response = await fetch(window.location.pathname + '?action=get_status');
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

document.addEventListener('DOMContentLoaded', function() {
    console.log('Faculty dashboard loaded');
    
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
    
    const ongoingClasses = document.querySelectorAll('.schedule-item.ongoing');
    if (ongoingClasses.length > 0) {
        showNotification(`You have ${ongoingClasses.length} ongoing class(es)`, 'info');
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
            }
        }
    }
});

document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        updateLocationStatus();
    }
});

setInterval(function() {
    if (document.visibilityState === 'visible') {
        updateLocationStatus();
    }
}, 300000);