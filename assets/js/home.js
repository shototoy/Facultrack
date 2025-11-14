// Home/Class Dashboard JavaScript

function contactFaculty(email) {
    if (email) {
        window.location.href = `mailto:${email}`;
    } else {
        showNotification('Contact information not available', 'info');
    }
}

// showNotification function available from shared_functions.js

// Faculty card updates now handled automatically by live_polling.js

// Course item updates now handled automatically by live_polling.js

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

// Live polling updates now handled automatically by live_polling.js