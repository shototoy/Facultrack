function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text.toString();
    return div.innerHTML;
}
function switchTab(tabName) {
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => content.classList.remove('active'));
    
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => button.classList.remove('active'));
    
    document.getElementById(tabName + '-content').classList.add('active');
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
}
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
    const row = button.closest('tr');

    try {
        button.disabled = true;
        button.textContent = 'Deleting...';

        const formData = new FormData();
        formData.append('action', action);
        formData.append(idField, id);

        const response = await fetch('handle_admin_actions.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            row.remove();
            updateStatistics();
            showNotification(`${capitalize(label)} deleted successfully`, 'success');
        } else {
            throw new Error(result.message || `Failed to delete ${label}`);
        }
    } catch (error) {
        console.error(`Error deleting ${label}:`, error);
        showNotification(`Error deleting ${label}: ${error.message}`, 'error');
        button.disabled = false;
        button.textContent = 'Delete';
    }
}
function capitalize(text) {
    return text.charAt(0).toUpperCase() + text.slice(1);
}
function updateStatistics() {
    fetch('get_statistics.php')
        .then(response => response.json())
        .then(data => {
            updateDashboardStats(data);
        })
        .catch(error => {
            console.error('Error updating statistics:', error);
            updateStatisticsFromTables();
        });
}
function updateStatisticsFromTables() {
    const statsConfig = [
        { selector: '#faculty-content .data-table tbody tr', cardId: 'total-faculty' },
        { selector: '#classes-content .data-table tbody tr', cardId: 'total-classes' },
        { selector: '#courses-content .data-table tbody tr', cardId: 'total-courses' },
        { selector: '#announcements-content .data-table tbody tr', cardId: 'active-announcements' }
    ];
    
    statsConfig.forEach(config => {
        const count = document.querySelectorAll(config.selector).length;
        updateStatCard(config.cardId, count);
    });
}
function updateDashboardStats(data) {
    const statsMapping = {
        'total_faculty': 'total-faculty',
        'total_classes': 'total-classes', 
        'total_courses': 'total-courses',
        'active_announcements': 'active-announcements'
    };
    
    Object.entries(statsMapping).forEach(([dataKey, cardId]) => {
        if (data[dataKey]) {
            updateStatCard(cardId, data[dataKey]);
        }
    });
}
function updateStatCard(cardId, value) {
    const card = document.getElementById(cardId);
    if (card) {
        const numberElement = card.querySelector('.stat-number');
        if (numberElement) {
            const currentValue = parseInt(numberElement.textContent) || 0;
            animateNumber(numberElement, currentValue, value);
        }
    }
}
function animateNumber(element, start, end) {
    const duration = 500;
    const stepTime = 50;
    const steps = duration / stepTime;
    const stepValue = (end - start) / steps;
    let current = start;
    
    const timer = setInterval(() => {
        current += stepValue;
        element.textContent = Math.round(current);
        
        if ((stepValue > 0 && current >= end) || (stepValue < 0 && current <= end)) {
            element.textContent = end;
            clearInterval(timer);
        }
    }, stepTime);
}
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
        
        const response = await fetch('handle_admin_actions.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            switchTab(type);
            setTimeout(() => {
                addNewRowToTable(type, result.data);
                updateStatistics();
            }, 50);
            
            closeModal();
            form.reset();
            showNotification(result.message, 'success');
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error(`Error adding ${type}:`, error);
        showNotification(`Error: ${error.message}`, 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    }
}

function addNewRowToTable(type, data) {
    if (!data) {
        console.error('No data provided to addNewRowToTable');
        return;
    }
    
    const activeTab = document.querySelector('.tab-content.active');
    if (!activeTab || !activeTab.id.startsWith(type)) {
        switchTab(type);
        setTimeout(() => addNewRowToTable(type, data), 50);
        return;
    }
    
    const tableBody = document.querySelector(`#${type}-content .data-table tbody`);
    if (!tableBody) {
        console.error('Table body not found for type:', type);
        return;
    }
    
    const newRow = document.createElement('tr');
    newRow.style.display = 'none';
    
    const rowTemplates = {
        faculty: (data) => `
            <td>${escapeHtml(data.full_name || 'N/A')}</td>
            <td>${escapeHtml(data.employee_id || 'N/A')}</td>
            <td>${escapeHtml(data.program || 'N/A')}</td>
            <td><span class="status-badge status-${data.status ? data.status.toLowerCase() : 'offline'}">${data.status || 'Offline'}</span></td>
            <td>${escapeHtml(data.current_location || 'Not Available')}</td>
            <td>${escapeHtml(data.contact_email || 'N/A')}</td>
            <td><button class="delete-btn" onclick="deleteEntity('delete_faculty', ${data.faculty_id})">Delete</button></td>
        `,
        classes: (data) => `
            <td>${escapeHtml(data.class_code || 'N/A')}</td>
            <td>${escapeHtml(data.class_name || 'N/A')}</td>
            <td>${data.year_level || 'N/A'}</td>
            <td>${escapeHtml(data.semester || 'N/A')}</td>
            <td>${escapeHtml(data.academic_year || 'N/A')}</td>
            <td>${escapeHtml(data.program_chair_name || 'Unassigned')}</td>
            <td>${data.total_subjects || 0}</td>
            <td><button class="delete-btn" onclick="deleteEntity('delete_class', ${data.class_id})">Delete</button></td>
        `,
        courses: (data) => `
            <td>${escapeHtml(data.course_code || 'N/A')}</td>
            <td>${escapeHtml(data.course_description || 'N/A')}</td>
            <td>${data.units || 0}</td>
            <td>${data.times_scheduled || 0}</td>
            <td><button class="delete-btn" onclick="deleteEntity('delete_course', ${data.course_id})">Delete</button></td>
        `,
        announcements: (data) => {
            const createdDate = data.created_at ? 
                new Date(data.created_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'}) :
                new Date().toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
                
            return `
                <td>${escapeHtml(data.title || 'N/A')}</td>
                <td>${escapeHtml((data.content || '').substring(0, 50))}${data.content && data.content.length > 50 ? '...' : ''}</td>
                <td><span class="status-badge priority-${data.priority || 'low'}">${(data.priority || 'LOW').toUpperCase()}</span></td>
                <td>${escapeHtml(data.target_audience || 'N/A')}</td>
                <td>${escapeHtml(data.created_by_name || 'Unknown')}</td>
                <td>${createdDate}</td>
                <td><button class="delete-btn" onclick="deleteEntity('delete_announcement', ${data.announcement_id})">Delete</button></td>
            `;
        }
    };
    
    const template = rowTemplates[type];
    if (!template) {
        console.error('Unknown type for addNewRowToTable:', type);
        return;
    }
    
    newRow.innerHTML = template(data);
    tableBody.insertBefore(newRow, tableBody.firstChild);
    
    newRow.style.display = '';
    newRow.style.backgroundColor = '#d4edda';
    newRow.style.transition = 'background-color 0.5s ease';
    
    newRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    setTimeout(() => {
        newRow.style.backgroundColor = '';
    }, 3000);
}

function setupFormHandlers() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        const modal = newForm.closest('.modal-overlay, .modal');
        if (!modal) return;
        
        let type = '';
        if (modal.id.includes('faculty') || newForm.id.includes('faculty')) {
            type = 'faculty';
        } else if (modal.id.includes('class') || newForm.id.includes('class')) {
            type = 'classes';
        } else if (modal.id.includes('course') || newForm.id.includes('course')) {
            type = 'courses';
        } else if (modal.id.includes('announcement') || newForm.id.includes('announcement')) {
            type = 'announcements';
        }
        
        if (type) {
            newForm.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                handleFormSubmission(this, type);
                return false;
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => setupFormHandlers(), 500);
    setTimeout(() => setupFormHandlers(), 2000);
});