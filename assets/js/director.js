function createSearchResultActions() {
    if (document.querySelector('#searchInput')) {
        const searchInput = document.querySelector('#searchInput');
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const activeTab = document.querySelector('.tab-content.active');
            if (!activeTab) return;

            if (activeTab.id === 'faculty-content') {
                searchTable(query, 'faculty');
            } else if (activeTab.id === 'classes-content') {
                searchTable(query, 'class');
            } else if (activeTab.id === 'courses-content') {
                searchTable(query, 'course');
            } else if (activeTab.id === 'announcements-content') {
                searchTable(query, 'announcements');
            } else if (activeTab.id === 'iftl-content') {
                searchTable(query, 'iftl');
            }
        });
    }
}
async function loadProgramCourses(programId) {
    const container = document.getElementById(`program-courses-${programId}`);
    if (!container) return;
    try {
        const response = await fetch(`assets/php/polling_api.php?action=get_program_courses&program_id=${programId}`);
        const data = await response.json();
        if (data.success && data.courses) {
            if (data.courses.length === 0) {
                container.innerHTML = '<div class="no-courses">No courses assigned to this program yet.</div>';
            } else {
                const coursesHtml = data.courses.map(course => `
                    <div class="course-item">
                        <strong>${course.course_code}</strong> - ${course.course_description}
                        <span class="course-meta">${course.units} units, scheduled ${course.times_scheduled} times</span>
                    </div>
                `).join('');
                container.innerHTML = coursesHtml;
            }
        } else {
            container.innerHTML = '<div class="error">Failed to load courses</div>';
        }
    } catch (error) {
        console.error('Error loading program courses:', error);
        container.innerHTML = '<div class="error">Error loading courses</div>';
    }
}
window.loadProgramCourses = loadProgramCourses;
async function loadIFTLFaculty() {
    const container = document.querySelector('#iftl-content .table-container');
    container.innerHTML = `
        <div class="loading-container">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading faculty list...</div>
        </div>
    `;
    try {
        const response = await fetch('assets/php/polling_api.php?action=get_iftl_faculty_list');
        const data = await response.json();
        if (data.success) {
            if (data.faculty.length === 0) {
                container.innerHTML = '<div class="empty-state"><h3>No faculty members found.</h3></div>';
                return;
            }
            let html = `
                <div class="table-header">
                    <h3 class="table-title">Individual Faculty Teaching Load (Week: ${data.current_week})</h3>
                </div>
                <div class="iftl-grid">
            `;
            data.faculty.forEach(faculty => {
                const hasIFTL = faculty.has_iftl > 0;
                const statusClass = hasIFTL ? '' : 'no-iftl';
                const statusText = hasIFTL ? 'Submitted' : 'No IFTL';
                const statusBadge = hasIFTL ? '<span class="status-badge status-available">Submitted</span>' : '<span class="status-badge status-offline">Missing</span>';
                html += `
                    <div class="iftl-card ${statusClass}" onclick="openIFTLModal(${faculty.faculty_id}, '${faculty.full_name}')">
                        <div class="iftl-card-header">
                            <div class="iftl-avatar">${getInitials(faculty.full_name)}</div>
                            <div class="iftl-info">
                                <div class="iftl-name">${faculty.full_name}</div>
                                <div class="iftl-program">${faculty.program || 'N/A'}</div>
                            </div>
                        </div>
                        <div class="iftl-status">
                            ${statusBadge}
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = `<div class="error-message">Error: ${data.message}</div>`;
        }
    } catch (error) {
        console.error('Error loading IFTL faculty:', error);
        container.innerHTML = '<div class="error-message">Failed to load faculty list.</div>';
    }
}
function getInitials(name) {
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
}
function searchTable(query, type) {
    if (type === 'iftl') {
        const cards = document.querySelectorAll('.iftl-card');
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(query) ? 'flex' : 'none';
        });
        return;
    }
    const table = document.querySelector(`#${type === 'class' ? 'classes' : type}-content .data-table`);
    if (!table) return;
    const rows = table.querySelectorAll('tbody tr:not(.expansion-row)');
    let visibleCount = 0;
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const isVisible = text.includes(query);
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
    });
}

document.addEventListener('DOMContentLoaded', function () {
    createSearchResultActions();
    const iftlTabBtn = document.querySelector('button[data-tab="iftl"]');
    if (iftlTabBtn) {
        iftlTabBtn.addEventListener('click', function () {
            loadIFTLFaculty();
        });
    }
});
function removeEntityFromUI(entityType, entityId) {
    switch (entityType) {
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
        case 'program':
            removeProgramFromTable(entityId);
            break;
    }
}
function removeFacultyFromTable(facultyId) {
    const row = document.querySelector(`tr[data-faculty-id="${facultyId}"]`);
    if (row) {
        row.style.transition = 'all 0.3s ease';
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        setTimeout(() => {
            row.remove();
            updateHeaderStatistics('faculty', -1);
        }, 300);
    } else {
        const selectors = [
            `tr:has(button[onclick*="delete_faculty, ${facultyId}"])`,
            `tr:has(button[onclick*="'delete_faculty', ${facultyId}"])`,
            `button[onclick*="delete_faculty, ${facultyId}"]`,
            `button[onclick*="'delete_faculty', ${facultyId}"]`
        ];
        for (const selector of selectors) {
            try {
                const button = document.querySelector(selector);
                if (button) {
                    const tr = button.closest('tr');
                    if (tr) {
                        tr.style.transition = 'all 0.3s ease';
                        tr.style.opacity = '0';
                        tr.style.transform = 'translateX(-20px)';
                        setTimeout(() => {
                            tr.remove();
                            updateHeaderStatistics('faculty', -1);
                        }, 300);
                        return;
                    }
                }
            } catch (e) { }
        }
    }
}
function removeClassFromTable(classId) {
    const row = document.querySelector(`tr[data-class-id="${classId}"]`);
    if (row) {
        row.style.transition = 'all 0.3s ease';
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        setTimeout(() => {
            row.remove();
            updateHeaderStatistics('class', -1);
        }, 300);
    } else {
        const targetRow = document.querySelector(`button[onclick*="'delete_class', ${classId}"]`)?.closest('tr') ||
            document.querySelector(`button[onclick*="delete_class, ${classId}"]`)?.closest('tr');
        if (targetRow) {
            targetRow.style.transition = 'all 0.3s ease';
            targetRow.style.opacity = '0';
            targetRow.style.transform = 'translateX(-20px)';
            setTimeout(() => {
                targetRow.remove();
                updateHeaderStatistics('class', -1);
            }, 300);
        }
    }
}
function removeCourseFromTable(courseId) {
    const rows = document.querySelectorAll('#courses-content .data-table tbody tr');
    rows.forEach(row => {
        const deleteBtn = row.querySelector('.delete-btn');
        if (deleteBtn && deleteBtn.onclick && deleteBtn.onclick.toString().includes(courseId)) {
            row.remove();
            updateHeaderStatistics('course', -1);
        }
    });
}
function removeAnnouncementFromTable(announcementId) {
    const row = document.querySelector(`tr[data-announcement-id="${announcementId}"]`);
    if (row) {
        row.style.transition = 'all 0.3s ease';
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        setTimeout(() => {
            row.remove();
            updateHeaderStatistics('announcement', -1);
        }, 300);
    } else {
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
}
function removeProgramFromTable(programId) {
    const row = document.querySelector(`tr[data-program-id="${programId}"]`);
    if (row) {
        row.style.transition = 'all 0.3s ease';
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        setTimeout(() => {
            row.remove();
            updateHeaderStatistics('program', -1);
        }, 300);
    } else {
        const targetRow = document.querySelector(`button[onclick*="'delete_program', ${programId}"]`)?.closest('tr') ||
            document.querySelector(`button[onclick*="delete_program, ${programId}"]`)?.closest('tr');
        if (targetRow) {
            targetRow.style.transition = 'all 0.3s ease';
            targetRow.style.opacity = '0';
            targetRow.style.transform = 'translateX(-20px)';
            setTimeout(() => {
                targetRow.remove();
                updateHeaderStatistics('program', -1);
            }, 300);
        }
    }
}
function updateHeaderStatistics(entityType, delta) {
    const elementMap = {
        'faculty': 'totalFaculty',
        'class': 'totalClasses',
        'course': 'totalCourses',
        'announcement': 'totalAnnouncements',
        'program': 'totalPrograms'
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
}
window.loadClassesForSemester = async function (semester) {
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
window.updateSemester = async function (form) {
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
                showNotification(result.message, 'success');
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
            showNotification('Error updating semester: ' + error.message, 'error');
        }
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        const updateForm = document.getElementById('updateSemesterForm');
        if (updateForm) {
            updateForm.addEventListener('submit', function (e) {
                e.preventDefault();
                window.updateSemester(this);
            });
        }
    }, 1000);
});
window.facultyNames = {};
window.facultySchedules = {};
let currentIFTLFacultyId = null;
async function openIFTLModal(facultyId, facultyName) {
    window.facultyNames[facultyId] = facultyName;
    const originalText = event ? event.target.innerHTML : '';
    const button = event ? event.target.closest('button') : null;
    if (button) {
        button.disabled = true;
        button.innerHTML = '<span class="loading-spinner-sm"></span> Loading...';
    }
    try {
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_full_faculty_schedule&faculty_id=${facultyId}`
        });
        const result = await response.json();
        if (result.success) {
            window.facultySchedules[facultyId] = result.schedules;
            if (typeof printFacultySchedule === 'function') {
                printFacultySchedule(facultyId);
            } else {
                console.error('printFacultySchedule function not found');
                alert('Printing function not loaded. Please refresh the page.');
            }
        } else {
            alert('Error fetching schedule: ' + (result.message || 'Unknown error'));
        }
    } catch (e) {
        console.error("Error fetching schedule for print", e);
        alert('Error fetching schedule data');
    } finally {
        if (button) {
            button.disabled = false;
            button.innerHTML = originalText || '<svg class="feather feather-sm"><use href="#calendar"></use></svg> IFTL';
        }
    }
}
