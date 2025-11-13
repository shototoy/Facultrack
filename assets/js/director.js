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
    
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Reset expansion states when switching tabs
    const allExpansionRows = document.querySelectorAll('.expansion-row');
    allExpansionRows.forEach(row => {
        row.style.display = 'none';
    });
    
    const allExpandableRows = document.querySelectorAll('.expandable-row');
    allExpandableRows.forEach(row => {
        row.classList.remove('expanded');
    });
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

        // Use FormData exactly like the add functionality does
        const formData = new FormData();
        formData.set('admin_action', action);
        formData.set(idField, id);

        console.log('Director delete request:');
        console.log('- Action:', action);
        console.log('- ID Field:', idField);
        console.log('- ID:', id);
        for (let pair of formData.entries()) {
            console.log('- FormData:', pair[0], '=', pair[1]);
        }

        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            body: formData
        });

        console.log('Director response status:', response.status);
        const responseText = await response.text();
        console.log('Director raw response:', responseText);

        const result = JSON.parse(responseText);
        console.log('Director parsed response:', result);

        if (result.success) {
            showNotification(`${capitalize(label)} deleted successfully`, 'success');
            // Removed manual deletion - polling system handles this automatically
        } else {
            throw new Error(result.message || `Failed to delete ${label}`);
        }
    } catch (error) {
        showNotification(`Error deleting ${label}: ${error.message}`, 'error');
        button.disabled = false;
        button.textContent = 'Delete';
    }
}
function capitalize(text) {
    return text.charAt(0).toUpperCase() + text.slice(1);
}
function updateStatistics() {
    fetch('assets/php/get_statistics.php')
        .then(response => response.json())
        .then(data => {
            updateDashboardStats(data);
        })
        .catch(error => {
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
        formData.set('admin_action', actionMap[type]);
        
        console.log('Sending add request:', actionMap[type]);
        
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            switchTab(type);
            closeModal();
            form.reset();
            showNotification(result.message, 'success');
            // Removed manual row addition - polling system handles this automatically
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

function addNewRowToTable(type, data) {
    if (!data) {
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
        return;
    }
    
    const rowTemplates = {
        faculty: (data) => `
            <tr class="expandable-row" onclick="toggleRowExpansion(this)" data-faculty-id="${data.faculty_id}">
                <td class="name-column">${escapeHtml(data.full_name || 'N/A')}</td>
                <td class="status-column">
                    <span class="status-badge status-${data.status ? data.status.toLowerCase() : 'offline'}">
                        ${data.status || 'Offline'}
                    </span>
                </td>
                <td class="location-column">${escapeHtml(data.current_location || 'Not Available')}</td>
                <td class="actions-column">
                    <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_faculty', ${data.faculty_id})">Delete</button>
                </td>
            </tr>
            <tr class="expansion-row" id="faculty-expansion-${data.faculty_id}" style="display: none;">
                <td colspan="4" class="expansion-content">
                    <div class="expanded-details">
                        <div class="detail-item">
                            <span class="detail-label">Employee ID:</span>
                            <span class="detail-value">${escapeHtml(data.employee_id || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Program:</span>
                            <span class="detail-value">${escapeHtml(data.program || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Contact Email:</span>
                            <span class="detail-value">${escapeHtml(data.contact_email || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value">${escapeHtml(data.contact_phone || 'N/A')}</span>
                        </div>
                    </div>
                </td>
            </tr>
        `,
        classes: (data) => `
            <tr class="expandable-row" onclick="toggleRowExpansion(this)" data-class-id="${data.class_id}">
                <td class="id-column">${escapeHtml(data.class_code)}</td>
                <td class="name-column">${escapeHtml(data.class_name)}</td>
                <td class="id-column">${data.year_level}</td>
                <td class="date-column">${escapeHtml(data.academic_year)}</td>
                <td class="actions-column">
                    <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_class', ${data.class_id})">Delete</button>
                </td>
            </tr>
            <tr class="expansion-row" id="class-expansion-${data.class_id}" style="display: none;">
                <td colspan="5" class="expansion-content">
                    <div class="expanded-details">
                        <div class="detail-item">
                            <span class="detail-label">Semester:</span>
                            <span class="detail-value">${escapeHtml(data.semester || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Program Chair:</span>
                            <span class="detail-value">${escapeHtml(data.program_chair_name || 'Unassigned')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Subjects:</span>
                            <span class="detail-value">${data.total_subjects || 0}</span>
                        </div>
                    </div>
                </td>
            </tr>
        `,
        courses: (data) => `
            <tr data-course-id="${data.course_id}" style="display: none;">
                <td class="id-column">${escapeHtml(data.course_code || 'N/A')}</td>
                <td class="description-column">${escapeHtml(data.course_description || 'N/A')}</td>
                <td class="id-column">${data.units || 0}</td>
                <td class="id-column">${data.times_scheduled || 0}</td>
                <td class="actions-column"><button class="delete-btn" onclick="deleteEntity('delete_course', ${data.course_id})">Delete</button></td>
            </tr>
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
        return;
    }
    
    // Insert the HTML directly since templates now include <tr> elements
    tableBody.insertAdjacentHTML('afterbegin', template(data));
    
    // Get the newly inserted row(s)
    const newRows = [];
    if (type === 'faculty') {
        // Faculty has 2 rows (main + expansion)
        newRows.push(tableBody.firstElementChild); // expansion row
        newRows.push(tableBody.children[1]); // main row
    } else {
        // Other types have 1 row
        newRows.push(tableBody.firstElementChild);
    }
    
    // Show and animate the main row
    const mainRow = newRows[newRows.length - 1];
    mainRow.style.display = '';
    mainRow.style.backgroundColor = '#d4edda';
    mainRow.style.transition = 'background-color 0.5s ease';
    
    mainRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    setTimeout(() => {
        mainRow.style.backgroundColor = '';
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

function resetAllTabsVisibility() {
    const rows = document.querySelectorAll('.data-table tbody tr:not(.expansion-row)');
    rows.forEach(row => row.style.display = '');
    
    // Explicitly hide all expansion rows
    const expansionRows = document.querySelectorAll('.expansion-row');
    expansionRows.forEach(row => row.style.display = 'none');
    
    // Remove expanded classes
    const expandableRows = document.querySelectorAll('.expandable-row');
    expandableRows.forEach(row => row.classList.remove('expanded'));
    
    const emptyStates = document.querySelectorAll('.search-empty-state');
    emptyStates.forEach(state => state.remove());
}

function searchContent() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const activeTab = document.querySelector('.tab-content.active').id;
    
    if (activeTab === 'faculty-content') {
        searchFaculty(searchTerm);
    } else if (activeTab === 'classes-content') {
        searchClasses(searchTerm);
    } else if (activeTab === 'courses-content') {
        searchCourses(searchTerm);
    } else if (activeTab === 'announcements-content') {
        searchAnnouncements(searchTerm);
    }
}

function searchFaculty(searchTerm) {
    const rows = document.querySelectorAll('#faculty-content .data-table tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length === 0) return;
        
        const name = cells[0].textContent.toLowerCase();
        const employeeId = cells[1].textContent.toLowerCase();
        const program = cells[2].textContent.toLowerCase();
        const email = cells[5].textContent.toLowerCase();
        
        if (name.includes(searchTerm) || employeeId.includes(searchTerm) || 
            program.includes(searchTerm) || email.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    updateEmptyState('#faculty-content .table-container', visibleCount, searchTerm, 'No faculty found', 'Try adjusting your search criteria');
}

function searchClasses(searchTerm) {
    const rows = document.querySelectorAll('#classes-content .data-table tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length === 0) return;
        
        const classCode = cells[0].textContent.toLowerCase();
        const className = cells[1].textContent.toLowerCase();
        const yearLevel = cells[2].textContent.toLowerCase();
        const semester = cells[3].textContent.toLowerCase();
        const academicYear = cells[4].textContent.toLowerCase();
        const programChair = cells[5].textContent.toLowerCase();
        
        if (classCode.includes(searchTerm) || className.includes(searchTerm) || 
            yearLevel.includes(searchTerm) || semester.includes(searchTerm) ||
            academicYear.includes(searchTerm) || programChair.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    updateEmptyState('#classes-content .table-container', visibleCount, searchTerm, 'No classes found', 'Try adjusting your search criteria');
}

function searchCourses(searchTerm) {
    const rows = document.querySelectorAll('#courses-content .data-table tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length === 0) return;
        
        const courseCode = cells[0].textContent.toLowerCase();
        const courseDescription = cells[1].textContent.toLowerCase();
        const units = cells[2].textContent.toLowerCase();
        
        if (courseCode.includes(searchTerm) || courseDescription.includes(searchTerm) || 
            units.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    updateEmptyState('#courses-content .table-container', visibleCount, searchTerm, 'No courses found', 'Try adjusting your search criteria');
}

function searchAnnouncements(searchTerm) {
    const rows = document.querySelectorAll('#announcements-content .data-table tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length === 0) return;
        
        const title = cells[0].textContent.toLowerCase();
        const content = cells[1].textContent.toLowerCase();
        const priority = cells[2].textContent.toLowerCase();
        const targetAudience = cells[3].textContent.toLowerCase();
        const createdBy = cells[4].textContent.toLowerCase();
        
        if (title.includes(searchTerm) || content.includes(searchTerm) || 
            priority.includes(searchTerm) || targetAudience.includes(searchTerm) ||
            createdBy.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    updateEmptyState('#announcements-content .table-container', visibleCount, searchTerm, 'No announcements found', 'Try adjusting your search criteria');
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
        emptyState.style.textAlign = 'center';
        emptyState.style.padding = '40px';
        emptyState.style.color = '#666';
        emptyState.innerHTML = `
            <h3>${title}</h3>
            <p>${message}</p>
        `;
        container.appendChild(emptyState);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                searchContent();
            } else {
                searchContent();
            }
        });
    }
    
    setTimeout(() => setupFormHandlers(), 500);
    setTimeout(() => setupFormHandlers(), 2000);
});

// Dynamic UI removal functions for consolidated admin actions
function removeEntityFromUI(entityType, entityId, idField) {
    const currentPage = detectPageType();
    
    if (currentPage === 'director') {
        // Remove from table view
        removeFromTable(entityType, entityId, idField);
    } else if (currentPage === 'program') {
        // Remove from card view  
        removeFromCards(entityType, entityId, idField);
    }
}

function removeFromTable(entityType, entityId, idField) {
    const row = document.querySelector(`tr[data-${idField.replace('_', '-')}="${entityId}"]`);
    if (!row) {
        // Try alternative selector patterns
        const alternativeSelectors = [
            `tr[data-faculty-id="${entityId}"]`,
            `tr[data-class-id="${entityId}"]`, 
            `tr[data-course-id="${entityId}"]`,
            `tr[data-announcement-id="${entityId}"]`
        ];
        
        for (const selector of alternativeSelectors) {
            const foundRow = document.querySelector(selector);
            if (foundRow) {
                performTableRowRemoval(foundRow, entityType);
                return;
            }
        }
        
        // Fallback: find row by checking delete button onclick
        const deleteButtons = document.querySelectorAll('.delete-btn');
        for (const btn of deleteButtons) {
            const onclick = btn.getAttribute('onclick');
            if (onclick && onclick.includes(entityId)) {
                const row = btn.closest('tr');
                if (row) {
                    performTableRowRemoval(row, entityType);
                    return;
                }
            }
        }
    } else {
        performTableRowRemoval(row, entityType);
    }
}

function performTableRowRemoval(row, entityType) {
    // Remove expansion row if it exists
    const nextRow = row.nextElementSibling;
    if (nextRow && nextRow.classList.contains('expansion-row')) {
        nextRow.style.transition = 'opacity 0.3s ease-out';
        nextRow.style.opacity = '0';
        setTimeout(() => nextRow.remove(), 300);
    }
    
    // Remove main row with animation
    row.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
    row.style.opacity = '0';
    row.style.transform = 'translateX(-20px)';
    setTimeout(() => {
        row.remove();
        updateTableCounts(entityType, -1);
    }, 300);
}

function removeFromCards(entityType, entityId, idField) {
    let cardSelector;
    switch(entityType) {
        case 'faculty':
            cardSelector = `.faculty-card[data-faculty-id="${entityId}"]`;
            break;
        case 'class':
            cardSelector = `.class-card[data-class-id="${entityId}"]`;
            break;
        case 'course':
            cardSelector = `.course-card[data-course-id="${entityId}"]`;
            break;
        default:
            return;
    }
    
    const card = document.querySelector(cardSelector);
    if (card) {
        // Remove card with animation
        card.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.9)';
        setTimeout(() => {
            card.remove();
            updateCardCounts(entityType, -1);
        }, 300);
    }
}

function detectPageType() {
    const url = window.location.pathname.toLowerCase();
    const title = document.title.toLowerCase();
    
    if (title.includes('director') || url.includes('director.php')) {
        return 'director';
    } else if (title.includes('program') || url.includes('program.php')) {
        return 'program';
    }
    return 'unknown';
}

function updateTableCounts(entityType, delta) {
    // Update header statistics if they exist
    const statLabels = {
        'faculty': 'Faculty',
        'class': 'Classes', 
        'course': 'Courses',
        'announcement': 'Announcements'
    };
    
    const label = statLabels[entityType];
    if (label) {
        const statElements = document.querySelectorAll('.header-stat-label, .stat-label');
        statElements.forEach(statElement => {
            if (statElement.textContent.trim() === label) {
                const numberElement = statElement.parentElement.querySelector('.header-stat-number, .stat-number');
                if (numberElement) {
                    const currentValue = parseInt(numberElement.textContent) || 0;
                    const newValue = Math.max(0, currentValue + delta);
                    numberElement.textContent = newValue;
                    
                    // Animate the change
                    numberElement.style.color = delta > 0 ? '#4CAF50' : '#f44336';
                    setTimeout(() => {
                        numberElement.style.color = '';
                    }, 2000);
                }
            }
        });
    }
}

function updateCardCounts(entityType, delta) {
    updateTableCounts(entityType, delta); // Same logic for now
}

// Toggle row expansion functionality
function toggleRowExpansion(row) {
    const expansionRow = row.nextElementSibling;
    
    if (expansionRow && expansionRow.classList.contains('expansion-row')) {
        const isExpanded = row.classList.contains('expanded');
        
        if (isExpanded) {
            // Collapse
            row.classList.remove('expanded');
            expansionRow.style.display = 'none';
        } else {
            // Expand
            row.classList.add('expanded');
            expansionRow.style.display = 'table-row';
        }
    }
}

// UPDATE SEMESTER FUNCTIONALITY - Make functions globally available
window.loadClassesForSemester = async function(semester) {
    const academicYearSelect = document.querySelector('[name="academic_year"]');
    const academicYear = academicYearSelect.value;
    const previewDiv = document.getElementById('classesPreview');
    const previewContent = document.getElementById('classesPreviewContent');
    const updateBtn = document.getElementById('updateSemesterBtn');
    
    // Reset state
    previewDiv.style.display = 'none';
    // updateBtn.disabled = true; // COMMENTED OUT - Don't disable the button
    
    if (!semester || !academicYear) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.set('action', 'get_classes_for_semester');
        formData.set('semester', semester);
        formData.set('academic_year', academicYear);
        
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (result.classes.length > 0) {
                // Show classes preview
                let html = '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px; padding: 10px;">';
                result.classes.forEach(classItem => {
                    html += `
                        <div style="display: flex; justify-content: space-between; padding: 8px; border-bottom: 1px solid #eee;">
                            <div>
                                <strong>${escapeHtml(classItem.class_name)}</strong> (${escapeHtml(classItem.class_code)})<br>
                                <small>Year ${classItem.year_level} • ${escapeHtml(classItem.program_chair_name || 'No Program Chair')}</small><br>
                                <small style="color: #888;">Currently: ${escapeHtml(classItem.current_semester || 'N/A')} ${escapeHtml(classItem.current_academic_year || 'N/A')}</small>
                            </div>
                            <div style="text-align: right; color: #666;">
                                <small>Current subjects: ${classItem.current_subjects || 0}</small>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                
                previewContent.innerHTML = html;
                previewDiv.style.display = 'block';
                // updateBtn.disabled = false; // Keep button enabled
            } else {
                previewContent.innerHTML = '<div style="text-align: center; padding: 20px; color: #999;">No classes found for this semester and academic year.</div>';
                previewDiv.style.display = 'block';
                // updateBtn.disabled = true; // Keep button enabled for testing
            }
        } else {
            if (typeof showNotification === 'function') {
                showNotification('Error loading classes: ' + result.message, 'error');
            } else {
                console.error('Error loading classes:', result.message);
            }
        }
    } catch (error) {
        if (typeof showNotification === 'function') {
            showNotification('Error loading classes: ' + error.message, 'error');
        } else {
            console.error('Error loading classes:', error.message);
        }
    }
}

window.updateSemester = async function(form) {
    alert('updateSemester function called! Form: ' + (form ? 'Found' : 'NULL'));
    
    const submitBtn = document.getElementById('updateSemesterBtn');
    const originalText = submitBtn ? submitBtn.textContent : 'BUTTON NOT FOUND';
    
    alert('Submit button: ' + (submitBtn ? 'Found' : 'NOT FOUND') + ', Text: ' + originalText);
    
    if (!confirm('Are you sure you want to update the semester? This will:\n• Add ALL curriculum courses to selected classes\n• Reset ALL faculty assignments\n• This action cannot be undone!')) {
        alert('User cancelled confirmation');
        return;
    }
    
    alert('User confirmed, proceeding with update');
    
    try {
        console.log('Starting form submission...');
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Updating...';
        
        const formData = new FormData(form);
        formData.set('action', 'update_semester');
        
        console.log('FormData created:');
        for (let pair of formData.entries()) {
            console.log('- ' + pair[0] + ': ' + pair[1]);
        }
        
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('Response status:', response.status);
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        const result = JSON.parse(responseText);
        console.log('Parsed result:', result);
        
        if (result.success) {
            // Use the global notification system
            if (typeof showNotification === 'function') {
                showNotification(result.message, 'success');
            } else if (typeof window.showNotification === 'function') {
                window.showNotification(result.message, 'success');
            } else {
                alert('Success: ' + result.message);
            }
            
            // Close modal using the global function
            if (typeof closeModal === 'function') {
                closeModal('updateSemesterModal');
            } else if (typeof window.closeModal === 'function') {
                window.closeModal('updateSemesterModal');
            } else {
                document.getElementById('updateSemesterModal').classList.remove('show');
            }
            
            form.reset();
            
            // Reset modal state
            const previewDiv = document.getElementById('classesPreview');
            if (previewDiv) previewDiv.style.display = 'none';
            submitBtn.disabled = true;
            
            // Refresh classes tab if it's currently active
            const activeTab = document.querySelector('.tab-content.active');
            if (activeTab && activeTab.id === 'classes-content') {
                // Trigger a refresh of the classes data
                setTimeout(() => {
                    window.location.reload(); // Simple refresh for now
                }, 1500);
            }
        } else {
            throw new Error(result.message || 'Failed to update semester');
        }
    } catch (error) {
        // Use the global notification system for errors too
        if (typeof showNotification === 'function') {
            showNotification('Error updating semester: ' + error.message, 'error');
        } else if (typeof window.showNotification === 'function') {
            window.showNotification('Error updating semester: ' + error.message, 'error');
        } else {
            alert('Error updating semester: ' + error.message);
        }
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

// Add direct event listener for Update Semester form when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const updateForm = document.getElementById('updateSemesterForm');
        if (updateForm) {
            console.log('Found updateSemesterForm, adding event listener');
            updateForm.addEventListener('submit', function(e) {
                alert('Form submit event fired!');
                e.preventDefault();
                window.updateSemester(this);
            });
        } else {
            console.log('updateSemesterForm NOT FOUND');
        }
    }, 1000); // Wait for modal to be ready
});