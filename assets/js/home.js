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

function searchFaculty() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const facultyCards = document.querySelectorAll('.faculty-card');
    let visibleCount = 0;

    facultyCards.forEach(card => {
        const name = card.getAttribute('data-name').toLowerCase();
        const program = card.getAttribute('data-program').toLowerCase();
        
        if (name.includes(searchTerm) || program.includes(searchTerm)) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    const grid = document.getElementById('facultyGrid');
    const existingEmptyState = grid.querySelector('.search-empty-state');
    
    if (existingEmptyState) {
        existingEmptyState.remove();
    }

    if (visibleCount === 0 && searchTerm.trim() !== '') {
        const emptyState = document.createElement('div');
        emptyState.className = 'empty-state search-empty-state';
        emptyState.innerHTML = `
            <h3>No faculty found</h3>
            <p>Try adjusting your search criteria</p>
        `;
        grid.appendChild(emptyState);
    }
}

function contactFaculty(email) {
    window.location.href = 'mailto:' + email;
}

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchFaculty();
    }
});

document.getElementById('searchInput').addEventListener('input', searchFaculty);

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

let locationPolling = null;

function startLocationPolling() {
    locationPolling = setInterval(updateFacultyLocations, 1000);
}

function stopLocationPolling() {
    if (locationPolling) {
        clearInterval(locationPolling);
        locationPolling = null;
    }
}

async function updateFacultyLocations() {
    try {
        const response = await fetch('assets/php/get_location.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        });

        if (response.ok) {
            const result = await response.json();
            
            if (result.success && result.faculty) {
                updateFacultyCards(result.faculty);
            }
        }
    } catch (error) {
        console.error('Error updating faculty locations:', error);
    }
}

function updateFacultyCards(facultyData) {
    facultyData.forEach(faculty => {
        const facultyCard = document.querySelector(`[data-faculty-id="${faculty.faculty_id}"]`);
        if (facultyCard) {
            const statusDot = facultyCard.querySelector('.status-dot');
            if (statusDot) {
                statusDot.className = `status-dot status-${faculty.status}`;
            }

            const statusText = facultyCard.querySelector('.location-text');
            if (statusText) {
                let statusLabel = 'Unknown';
                switch(faculty.status) {
                    case 'available': statusLabel = 'Available'; break;
                    case 'busy': statusLabel = 'In Meeting'; break;
                    case 'offline': statusLabel = 'Offline'; break;
                }
                statusText.textContent = statusLabel;
            }

            const locationDiv = facultyCard.querySelector('.location-info > div:nth-child(2)');
            if (locationDiv) {
                locationDiv.textContent = faculty.current_location;
            }

            const timeInfo = facultyCard.querySelector('.time-info');
            if (timeInfo) {
                timeInfo.textContent = `Last updated: ${faculty.last_updated}`;
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    startLocationPolling();
});

document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopLocationPolling();
    } else {
        startLocationPolling();
    }
});

window.addEventListener('beforeunload', function() {
    stopLocationPolling();
});