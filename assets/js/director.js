
function createSearchResultActions() {
    if (document.querySelector('#searchInput')) {
        const searchInput = document.querySelector('#searchInput');
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const activeTab = document.querySelector('.tab-content.active');
            
            if (activeTab.id === 'faculty-content') {
                searchTable(query, 'faculty');
            } else if (activeTab.id === 'classes-content') {
                searchTable(query, 'class');
            } else if (activeTab.id === 'courses-content') {
                searchTable(query, 'course');
            } else if (activeTab.id === 'announcements-content') {
                searchTable(query, 'announcement');
            }
        });
    }
}

function searchTable(query, type) {
    const table = document.querySelector(`#${type === 'class' ? 'classes' : type}-content .data-table`);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr:not(.expansion-row)');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const isVisible = text.includes(query);
        row.style.display = isVisible ? '' : 'none';
        
        const expansionRow = row.nextElementSibling;
        if (expansionRow && expansionRow.classList.contains('expansion-row')) {
            expansionRow.style.display = isVisible ? 'none' : 'none';
            row.classList.remove('expanded');
        }
        
        if (isVisible) visibleCount++;
    });
}

function createFacultyRow(faculty) {
    const status = faculty.status || 'Offline';
    const statusClass = status.toLowerCase().replace(' ', '-');
    
    return `
        <tr class="expandable-row" onclick="toggleRowExpansion(this)">
            <td>
                <div class="faculty-info">
                    <div class="faculty-name">${faculty.full_name}</div>
                    <div class="faculty-id">${faculty.employee_id}</div>
                </div>
            </td>
            <td>
                <span class="status-indicator ${statusClass}">
                    ${status}
                </span>
            </td>
            <td>${faculty.program || 'N/A'}</td>
            <td>${faculty.contact_email || 'N/A'}</td>
            <td class="actions-cell">
                <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_faculty', ${faculty.faculty_id})">Delete</button>
            </td>
        </tr>
        <tr class="expansion-row">
            <td colspan="5">
                <div class="expansion-content">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Office Hours:</label>
                            <span>${faculty.office_hours || 'Not set'}</span>
                        </div>
                        <div class="info-item">
                            <label>Phone:</label>
                            <span>${faculty.contact_phone || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <label>Current Location:</label>
                            <span>${faculty.current_location || 'Unknown'}</span>
                        </div>
                        <div class="info-item">
                            <label>Last Update:</label>
                            <span>${faculty.last_location_update ? new Date(faculty.last_location_update).toLocaleString() : 'Never'}</span>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    `;
}

function addRowToTable(tableSelector, htmlContent, entityType) {
    const tableBody = document.querySelector(`${tableSelector} tbody`);
    if (!tableBody) return;
    
    tableBody.insertAdjacentHTML('afterbegin', htmlContent);
    
    const newRows = [];
    if (entityType === 'faculty') {
        newRows.push(tableBody.firstElementChild);
        newRows.push(tableBody.children[1]);
    } else {
        newRows.push(tableBody.firstElementChild);
    }
    
    const mainRow = newRows[entityType === 'faculty' ? 1 : 0];
    if (mainRow) {
        mainRow.style.opacity = '0';
        mainRow.style.transform = 'translateX(-20px)';
        
        requestAnimationFrame(() => {
            mainRow.style.transition = 'all 0.3s ease';
            mainRow.style.opacity = '1';
            mainRow.style.transform = 'translateX(0)';
        });
    }
    
    return newRows;
}

function createClassRow(classData) {
    return `
        <tr class="expandable-row" onclick="toggleRowExpansion(this)">
            <td>
                <div class="class-info">
                    <div class="class-name">${classData.class_name}</div>
                    <div class="class-code">${classData.class_code}</div>
                </div>
            </td>
            <td>Year ${classData.year_level}</td>
            <td>${classData.semester}</td>
            <td>${classData.academic_year}</td>
            <td>${classData.program_chair_name || 'N/A'}</td>
            <td class="actions-cell">
                <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_class', ${classData.class_id})">Delete</button>
            </td>
        </tr>
        <tr class="expansion-row">
            <td colspan="6">
                <div class="expansion-content">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Total Subjects:</label>
                            <span>${classData.total_subjects || 0}</span>
                        </div>
                        <div class="info-item">
                            <label>Enrolled Students:</label>
                            <span>${classData.enrolled_students || 0}</span>
                        </div>
                        <div class="info-item">
                            <label>Program Chair:</label>
                            <span>${classData.program_chair_name || 'Not assigned'}</span>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    `;
}

function resetTableRowStates() {
    const allExpansionRows = document.querySelectorAll('.expansion-row');
    allExpansionRows.forEach(row => {
        row.style.display = 'none';
    });
    
    const allExpandableRows = document.querySelectorAll('.expandable-row');
    allExpandableRows.forEach(row => {
        row.classList.remove('expanded');
    });
}

function createCourseRow(course) {
    return `
        <tr>
            <td>
                <div class="course-info">
                    <div class="course-code">${course.course_code}</div>
                    <div class="course-description">${course.course_description}</div>
                </div>
            </td>
            <td>${course.units}</td>
            <td>${course.times_scheduled || 0}</td>
            <td class="actions-cell">
                <button class="delete-btn" onclick="deleteEntity('delete_course', ${course.course_id})">Delete</button>
            </td>
        </tr>
    `;
}

function createAnnouncementRow(announcement) {
    const priorityClass = announcement.priority ? announcement.priority.toLowerCase() : 'normal';
    const date = new Date(announcement.created_at);
    const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    
    return `
        <tr>
            <td>
                <div class="announcement-info">
                    <div class="announcement-title">${announcement.title}</div>
                    <div class="announcement-preview">${announcement.content.substring(0, 100)}${announcement.content.length > 100 ? '...' : ''}</div>
                </div>
            </td>
            <td><span class="priority-badge ${priorityClass}">${announcement.priority || 'Normal'}</span></td>
            <td>${announcement.target_audience || 'All'}</td>
            <td>${announcement.created_by_name || 'System'}</td>
            <td>${formattedDate}</td>
            <td class="actions-cell">
                <button class="delete-btn" onclick="deleteEntity('delete_announcement', ${announcement.announcement_id})">Delete</button>
            </td>
        </tr>
    `;
}

function removeEntityFromUI(entityType, entityId) {
    switch(entityType) {
        case 'faculty':
            removeFacultyFromTable(entityId);
            break;
        case 'class':
            removeClassFromTable(entityId);
            break;
        case 'course':
            removeCourseFromTable(entityId);
            break;
        case 'announcement':
            removeAnnouncementFromTable(entityId);
            break;
    }
}

function removeFacultyFromTable(facultyId) {
    const selectors = [
        `tr:has(button[onclick*="delete_faculty, ${facultyId}"])`,
        `tr:has(button[onclick*="'delete_faculty', ${facultyId}"])`,
        `button[onclick*="delete_faculty, ${facultyId}"]`,
        `button[onclick*="'delete_faculty', ${facultyId}"]`
    ];
    
    let targetRow = null;
    
    for (const selector of selectors) {
        try {
            const button = document.querySelector(selector);
            if (button) {
                targetRow = button.closest('tr');
                break;
            }
        } catch (e) {
            const buttons = document.querySelectorAll('button[onclick*="delete_faculty"]');
            buttons.forEach(button => {
                if (button.getAttribute('onclick').includes(facultyId.toString())) {
                    targetRow = button.closest('tr');
                }
            });
            if (targetRow) break;
        }
    }
    
    if (!targetRow) return;
    
    const expansionRow = targetRow.nextElementSibling;
    if (expansionRow && expansionRow.classList.contains('expansion-row')) {
        expansionRow.remove();
    }
    
    targetRow.style.transition = 'all 0.3s ease';
    targetRow.style.opacity = '0';
    targetRow.style.transform = 'translateX(-20px)';
    
    setTimeout(() => {
        targetRow.remove();
        updateHeaderStatistics('faculty', -1);
    }, 300);
}

function removeClassFromTable(classId) {
    const targetRow = document.querySelector(`button[onclick*="'delete_class', ${classId}"]`)?.closest('tr') ||
                     document.querySelector(`button[onclick*="delete_class, ${classId}"]`)?.closest('tr');
    
    if (targetRow) {
        const expansionRow = targetRow.nextElementSibling;
        if (expansionRow && expansionRow.classList.contains('expansion-row')) {
            expansionRow.remove();
        }
        
        targetRow.style.transition = 'all 0.3s ease';
        targetRow.style.opacity = '0';
        targetRow.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            targetRow.remove();
            updateHeaderStatistics('class', -1);
        }, 300);
    }
}

function removeCourseFromTable(courseId) {
    const targetRow = document.querySelector(`button[onclick*="'delete_course', ${courseId}"]`)?.closest('tr') ||
                     document.querySelector(`button[onclick*="delete_course, ${courseId}"]`)?.closest('tr');
    
    if (targetRow) {
        targetRow.style.transition = 'all 0.3s ease';
        targetRow.style.opacity = '0';
        targetRow.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            targetRow.remove();
            updateHeaderStatistics('course', -1);
        }, 300);
    }
}

function removeAnnouncementFromTable(announcementId) {
    const targetRow = document.querySelector(`button[onclick*="'delete_announcement', ${announcementId}"]`)?.closest('tr') ||
                     document.querySelector(`button[onclick*="delete_announcement, ${announcementId}"]`)?.closest('tr');
    
    if (targetRow) {
        targetRow.style.transition = 'all 0.3s ease';
        targetRow.style.opacity = '0';
        targetRow.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            targetRow.remove();
            updateHeaderStatistics('announcement', -1);
        }, 300);
    }
}

function updateHeaderStatistics(entityType, delta) {
    const elementMap = {
        'faculty': 'totalFaculty',
        'class': 'totalClasses', 
        'course': 'totalCourses',
        'announcement': 'totalAnnouncements'
    };
    
    const elementId = elementMap[entityType];
    const element = document.getElementById(elementId);
    
    if (element) {
        const currentValue = parseInt(element.textContent) || 0;
        const newValue = Math.max(0, currentValue + delta);
        
        if (currentValue !== newValue) {
            element.style.transition = 'all 0.3s ease';
            element.style.transform = 'scale(1.1)';
            element.textContent = newValue;
            
            setTimeout(() => {
                element.style.transform = 'scale(1)';
            }, 150);
        }
    }
}

function updateTableCounts(entityType, delta) {
    updateTableCounts(entityType, delta);
}

function toggleRowExpansion(row) {
    const expansionRow = row.nextElementSibling;
    
    if (expansionRow && expansionRow.classList.contains('expansion-row')) {
        const isExpanded = row.classList.contains('expanded');
        
        if (isExpanded) {
            row.classList.remove('expanded');
            expansionRow.style.display = 'none';
        } else {
            row.classList.add('expanded');
            expansionRow.style.display = 'table-row';
        }
    }
}

window.loadClassesForSemester = async function(semester) {
    const academicYearSelect = document.querySelector('[name="academic_year"]');
    const academicYear = academicYearSelect.value;
    const previewDiv = document.getElementById('classesPreview');
    const previewContent = document.getElementById('classesPreviewContent');
    const updateBtn = document.getElementById('updateSemesterBtn');
    
    previewDiv.style.display = 'none';
    
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
            } else {
                previewContent.innerHTML = '<div style="text-align: center; padding: 20px; color: #999;">No classes found for this semester and academic year.</div>';
                previewDiv.style.display = 'block';
            }
        }
    } catch (error) {
    }
}

window.updateSemester = async function(form) {
    if (!confirm('Are you sure you want to update the semester? This will:\n• Add ALL curriculum courses to selected classes\n• Reset ALL faculty assignments\n• This action cannot be undone!')) {
        return;
    }
    
    const submitBtn = document.getElementById('updateSemesterBtn');
    const originalText = submitBtn.textContent;
    
    try {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Updating...';
        
        const formData = new FormData(form);
        formData.set('action', 'update_semester');
        
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (typeof showNotification === 'function') {
                showNotification(result.message, 'success');
            } else if (typeof window.showNotification === 'function') {
                window.showNotification(result.message, 'success');
            } else {
                alert('Success: ' + result.message);
            }
            
            if (typeof closeModal === 'function') {
                closeModal('updateSemesterModal');
            } else if (typeof window.closeModal === 'function') {
                window.closeModal('updateSemesterModal');
            } else {
                document.getElementById('updateSemesterModal').classList.remove('show');
            }
            
            form.reset();
            
            const previewDiv = document.getElementById('classesPreview');
            if (previewDiv) previewDiv.style.display = 'none';
            submitBtn.disabled = true;
            
            const activeTab = document.querySelector('.tab-content.active');
            if (activeTab && activeTab.id === 'classes-content') {
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        } else {
            throw new Error(result.message || 'Failed to update semester');
        }
    } catch (error) {
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

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const updateForm = document.getElementById('updateSemesterForm');
        if (updateForm) {
            updateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                window.updateSemester(this);
            });
        }
    }, 1000);
});