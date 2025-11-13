// SHARED FUNCTIONS - Common utilities used across director.js and program.js
// This consolidates duplicate code to reduce redundancy

// Common utility functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text.toString();
    return div.innerHTML;
}

// Shared tab switching functionality
function switchTab(tabName) {
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => content.classList.remove('active'));
    
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => button.classList.remove('active'));
    
    document.getElementById(tabName + '-content').classList.add('active');
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Reset expansion states when switching tabs (director specific)
    if (typeof toggleRowExpansion === 'function') {
        const allExpansionRows = document.querySelectorAll('.expansion-row');
        allExpansionRows.forEach(row => {
            row.style.display = 'none';
        });
        
        const allExpandableRows = document.querySelectorAll('.expandable-row');
        allExpandableRows.forEach(row => {
            row.classList.remove('expanded');
        });
    }
    
    // Reset card visibility (program specific)
    const facultyCards = document.querySelectorAll('.faculty-card');
    facultyCards.forEach(card => card.style.display = 'block');
    const courseCards = document.querySelectorAll('.course-card');
    courseCards.forEach(card => card.style.display = 'block');  
    const classCards = document.querySelectorAll('.class-card');
    classCards.forEach(card => card.style.display = 'block');
}

// Shared export functionality
function exportData(type) {
    const config = {
        faculty: {
            selector: '#faculty-content .data-table',
            filename: 'faculty_data.csv',
            message: 'Faculty data exported successfully'
        },
        classes: {
            selector: '#classes-content .data-table',
            filename: 'classes_data.csv',
            message: 'Classes data exported successfully'
        },
        courses: {
            selector: '#courses-content .data-table',
            filename: 'courses_data.csv',
            message: 'Courses data exported successfully'
        },
        announcements: {
            selector: '#announcements-content .data-table',
            filename: 'announcements_data.csv',
            message: 'Announcements data exported successfully'
        }
    };

    const typeConfig = config[type];
    
    if (!typeConfig) {
        showNotification('Export type not supported', 'error');
        return;
    }

    const table = document.querySelector(typeConfig.selector);
    if (!table) {
        showNotification(`No ${type} data to export`, 'error');
        return;
    }
    
    const data = extractTableData(table);
    downloadCSV(data, typeConfig.filename);
    showNotification(typeConfig.message, 'success');
}

function extractTableData(table) {
    const rows = table.querySelectorAll('tr');
    const data = [];
    
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll(index === 0 ? 'th' : 'td');
        const rowData = [];
        
        cells.forEach(cell => {
            if (!cell.querySelector('.delete-btn')) {
                rowData.push(cell.textContent.trim());
            }
        });
        
        if (rowData.length > 0) {
            data.push(rowData);
        }
    });
    
    return data;
}

function downloadCSV(data, filename) {
    const csv = data.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Shared delete functionality
async function deleteEntity(action, id) {
    const idFields = {
        delete_faculty: 'faculty_id',
        delete_class: 'class_id',
        delete_course: 'course_id',
        delete_announcement: 'announcement_id'
    };

    const labels = {
        delete_faculty: 'faculty member',
        delete_class: 'class',
        delete_course: 'course',
        delete_announcement: 'announcement'
    };

    const label = labels[action] || 'item';
    const idField = idFields[action] || 'id';

    if (!confirm(`Are you sure you want to delete this ${label}? This action cannot be undone.`)) return;

    const button = event.target;
    const originalText = button.textContent;

    try {
        button.disabled = true;
        button.textContent = 'Deleting...';

        const formData = new FormData();
        formData.set('action', action);
        formData.set(idField, id);

        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showNotification(`${capitalize(label)} deleted successfully`, 'success');
            // Let polling system handle UI updates automatically
        } else {
            throw new Error(result.message || `Failed to delete ${label}`);
        }
    } catch (error) {
        showNotification(`Error deleting ${label}: ${error.message}`, 'error');
        button.disabled = false;
        button.textContent = originalText;
    }
}

function capitalize(text) {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

// Shared form submission handler
async function handleFormSubmission(form, type) {
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    
    const actionMap = {
        'faculty': 'add_faculty',
        'classes': 'add_class', 
        'courses': 'add_course',
        'announcements': 'add_announcement'
    };
    
    try {
        submitButton.disabled = true;
        submitButton.textContent = 'Adding...';
        
        const formData = new FormData(form);
        formData.set('action', actionMap[type]);
        
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            switchTab(type);
            if (typeof closeModal === 'function') closeModal();
            form.reset();
            showNotification(result.message, 'success');
            // Let polling system handle UI updates automatically
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        showNotification(`Error: ${error.message}`, 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    }
}

// Notification function (if not already available globally)
if (typeof showNotification === 'undefined') {
    function showNotification(message, type = 'info') {
        document.querySelector('.notification')?.remove();

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 10000;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        `;
        notification.textContent = message;

        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 5000);
        notification.addEventListener('click', () => notification.remove());
    }
    window.showNotification = showNotification;
}