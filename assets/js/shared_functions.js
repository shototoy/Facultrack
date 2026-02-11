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
    const facultyCards = document.querySelectorAll('.faculty-card');
    facultyCards.forEach(card => card.style.display = 'block');
    const courseCards = document.querySelectorAll('.course-card');
    courseCards.forEach(card => card.style.display = 'block');
    const classCards = document.querySelectorAll('.class-card');
    classCards.forEach(card => card.style.display = 'block');
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
        },
        programs: {
            selector: '#programs-content .data-table',
            filename: 'programs_data.csv',
            message: 'Programs data exported successfully'
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
        delete_announcement: 'announcement_id',
        delete_program: 'program_id'
    };
    const labels = {
        delete_faculty: 'faculty member',
        delete_class: 'class',
        delete_course: 'course',
        delete_announcement: 'announcement',
        delete_program: 'program'
    };
    const label = labels[action] || 'item';
    const idField = idFields[action] || 'id';
    const button = event.target;
    if (!button || !button.textContent) return;
    if (typeof showConfirmation === 'function') {
        showConfirmation(
            `Delete ${capitalize(label)}`,
            `Are you sure you want to delete this ${label}?`,
            async function () {
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
                        setTimeout(() => location.reload(), 500);
                    } else {
                        throw new Error(result.message || `Failed to delete ${label}`);
                    }
                } catch (error) {
                    showNotification(`Error deleting ${label}: ${error.message}`, 'error');
                } finally {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            }
        );
        return;
    }
}
function capitalize(text) {
    return text.charAt(0).toUpperCase() + text.slice(1);
}
async function handleFormSubmission(form, type) {
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    const actionMap = {
        'faculty': 'add_faculty',
        'classes': 'add_class',
        'courses': 'add_course',
        'announcements': 'add_announcement',
        'programs': 'add_program'
    };
    const actionLabel = actionMap[type].replace('add_', '');
    if (typeof showConfirmation === 'function') {
        showConfirmation(
            `Add ${capitalize(actionLabel)}`,
            `Are you sure you want to add this ${actionLabel}?`,
            async function () {
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
                        showNotification(result.message, 'success');
                        setTimeout(() => location.reload(), 500);
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
        );
        return;
    }
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
            showNotification(result.message, 'success');
            setTimeout(() => location.reload(), 500);
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

function generateAnnouncementPDF(id, title, content, authorName, date) {

    const path = window.location.pathname;
    const directory = path.substring(0, path.lastIndexOf('/'));
    const appRoot = window.location.origin + directory + '/';

    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Announcement - ${title}</title>
            <base href="${appRoot}">
            <style>
                @media print {
                    @page { margin: 0; size: letter portrait; }
                    body {
                        margin: 0;
                        padding: 0;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    .background-layer {
                        display: block !important;
                    }
                }
                body {
                    font-family: 'Times New Roman', Times, serif;
                    margin: 0;
                    padding: 0;
                    min-height: 100vh;
                    box-sizing: border-box;
                    background: white;
                }
                .background-layer {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: -10;
                }
                .background-layer img {
                    width: 100%;
                    height: 100%;
                    object-fit: fill;
                }

                .content-wrapper {
                    padding: 154px 96px 96px 96px;
                    position: relative;
                    z-index: 1;
                }

                .document-header {
                    position: absolute;
                    top: 15px;
                    left: 25px;
                    width: 75%;
                    display: flex;
                    align-items: center;
                    justify-content: flex-start;
                    height: 90px;
                }

                .logos-left {
                    display: flex;
                    gap: 15px;
                    align-items: center;
                    margin-right: 25px;
                }

                .logo {
                    width: 75px;
                    height: 75px;
                    object-fit: contain;
                }

                .header-text {
                    text-align: left;
                    color: #000;
                    line-height: 1.1;
                    font-family: 'Times New Roman', Times, serif;
                    width: 100%;
                }
                .header-text h2 {
                    font-size: 15pt;
                    font-weight: bold;
                    margin: 2px 0;
                    color: #008000;
                    text-transform: uppercase;
                }
                .header-text p {
                    font-size: 10.5pt;
                    margin: 0;
                    font-style: italic;
                }

                .office-title-block {
                    margin-top: 30px;
                    text-align: center;
                    margin-bottom: 35px;
                }
                .office-title {
                    font-family: 'Monotype Corsiva', 'Apple Chancery', cursive;
                    font-size: 24pt;
                    color: #000;
                    line-height: 1;
                }
                .office-subtitle {
                    font-family: Arial, sans-serif;
                    font-size: 12pt;
                    margin-top: 8px;
                }

                .memo-info {
                    margin-bottom: 20px;
                    font-family: Arial, sans-serif;
                    font-size: 11pt;
                }
                .memo-row {
                    display: flex;
                    margin-bottom: 8px;
                }
                .memo-label {
                    font-weight: bold;
                    width: 100px;
                    flex-shrink: 0;
                }
                .memo-value {
                    font-weight: bold;
                }
                .memo-line {
                     border-bottom: 2px solid #000;
                     margin-bottom: 30px;
                }

                .content {
                    font-size: 12pt;
                    line-height: 1.5;
                    color: #000;
                    margin-bottom: 50px;
                    white-space: pre-wrap;
                    text-align: justify;
                    font-family: Arial, sans-serif;
                }

                .footer-text {
                    position: fixed;
                    bottom: 48px;
                    left: 96px;
                    right: 96px;
                    text-align: center;
                    font-family: Arial, sans-serif;
                    font-size: 7pt;
                    color: #000;
                    border-top: 1px dotted #ccc;
                    padding-top: 5px;
                    background: transparent;
                }
                .footer-text span {
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="background-layer">
                <img src="assets/images/announcement.png" alt="">
            </div>

            <div class="content-wrapper">
                <!-- Header removed as requested -->
                <div class="document-header" style="display: none;">

                </div>

                <div class="office-title-block">
                    <div class="office-title">Office of the Campus Director</div>
                    <div class="office-subtitle">Isulan Campus</div>
                </div>

                <div class="memo-info">
                    <div style="font-weight: bold; margin-bottom: 20px;">OFFICE MEMORANDUM No. ${id}, Series ${new Date(date).getFullYear() || new Date().getFullYear()}</div>

                    <div class="memo-row"><span class="memo-label">TO:</span> <span class="memo-value">CAMPUS DESIGNATED PERSONNEL</span></div>
                    <div class="memo-row"><span class="memo-label">FROM:</span> <span class="memo-value">${authorName.toUpperCase()}</span></div>
                    <div class="memo-row" style="margin-left: 100px; margin-top: -8px; font-weight: normal; font-size: 10pt;">Campus Director</div>

                    <div class="memo-row"><span class="memo-label">SUBJECT:</span> <span class="memo-value" style="text-transform: uppercase;">${title}</span></div>
                    <div class="memo-row"><span class="memo-label">DATE:</span> <span class="memo-value">${date}</span></div>
                </div>

                <div class="memo-line"></div>

                <div class="content">${content}</div>

                <div class="signatory">
                    <p>For your information and guidance.</p>
                </div>
            </div>

            <!-- Footer removed as requested -->
            <div class="footer-text" style="display: none;">
                <span>VISION:</span> A leading University in advancing scholarly innovation, multi-cultural convergence, and responsive public service in a borderless Region. |
                <span>MISSION:</span> The University shall primarily provide advanced instruction and professional training in science and technology, agriculture, fisheries, education and other relevant fields of study. It shall also undertake research and extension services, and provide progressive leadership in its areas of specialization. |
                <span>MAXIM:</span> Generator of Solutions. |
                <span>CORE VALUES:</span> Patriotism, Respect, Integrity, Zeal, Excellence in Public Service.
            </div>

            <script>
                setTimeout(() => {
                    window.print();
                }, 1000);
            </script>
        </body>
        </html>
    `;
}

function printAnnouncement(id) {
    const card = document.getElementById('announcement-' + id);
    if (!card) return;

    const clone = card.cloneNode(true);
    const btn = clone.querySelector('button');
    if (btn) btn.remove();

    const title = clone.querySelector('.announcement-title').textContent;
    const content = clone.querySelector('.announcement-content').textContent;
    const authorElem = clone.querySelector('.announcement-author');
    const authorName = authorElem ? authorElem.textContent.replace('By:', '').trim() : 'ADMINISTRATION';

    const dateDiv = clone.querySelector('.announcement-date');
    const date = (dateDiv && dateDiv.getAttribute('data-full-date'))
        ? dateDiv.getAttribute('data-full-date')
        : (dateDiv ? dateDiv.textContent.trim() : '');

    const pdfContent = generateAnnouncementPDF(id, title, content, authorName, date);

    const printWindow = window.open('', '_blank', 'width=850,height=1100');
    printWindow.document.write(pdfContent);
    printWindow.document.close();
}

async function emailAnnouncement(announcement) {
    const subject = encodeURIComponent(`Announcement: ${announcement.title}`);


    const path = window.location.pathname;
    const directory = path.substring(0, path.lastIndexOf('/'));
    const appRoot = window.location.origin + directory + '/';
    const memoLink = `${appRoot}view_memo.php?id=${announcement.announcement_id}`;

    const bodyContent = `${announcement.title}\n\n` +
        `Priority: ${announcement.priority.toUpperCase()}\n` +
        `Target Audience: ${announcement.target_audience}\n\n` +
        `${announcement.content}\n\n` +
        `View Memo: ${memoLink}\n\n` +
        `--\nSent via FaculTrack`;

    let ccEmails = [];
    if (announcement.target_audience) {
        try {
            const response = await fetch(`assets/php/polling_api.php?action=get_audience_emails&audience=${encodeURIComponent(announcement.target_audience)}`);
            const data = await response.json();
            if (data.success && Array.isArray(data.emails)) {
                ccEmails = data.emails;
            }
        } catch (error) {
            console.error("Error fetching audience emails:", error);
        }
    }
    const recipientString = ccEmails.join(',');
    const mailtoLink = `mailto:${recipientString}?subject=${subject}&body=${encodeURIComponent(bodyContent)}`;
    const link = document.createElement('a');
    link.href = mailtoLink;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

window.printAnnouncement = printAnnouncement;
window.generateAnnouncementPDF = generateAnnouncementPDF;
window.emailAnnouncement = emailAnnouncement;