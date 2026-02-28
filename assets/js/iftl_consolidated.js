// Consolidated IFTL manager/api/http requests + schedule fetch/print utilities.
(function () {
    window.facultyNames = window.facultyNames || {};
    window.facultySchedules = window.facultySchedules || {};
    window.facultyDeanNames = window.facultyDeanNames || {};
    window.facultyPrintPeriods = window.facultyPrintPeriods || {};

    let currentIFTLFacultyId = null;

    function buildFullScheduleRequest(facultyId, weekIdentifier) {
        const formData = new URLSearchParams();
        formData.set('action', 'get_full_faculty_schedule');
        formData.set('faculty_id', facultyId);
        formData.set('week_identifier', weekIdentifier);
        return formData;
    }

    async function fetchIFTLWeeksForFaculty(facultyId) {
        const response = await fetch(`assets/php/polling_api.php?action=get_iftl_weeks_month&faculty_id=${encodeURIComponent(facultyId)}`);
        return response.json();
    }

    async function fetchIFTLFullSchedule(facultyId, weekIdentifier) {
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: buildFullScheduleRequest(facultyId, weekIdentifier).toString()
        });
        return response.json();
    }

    window.IFTLHttpRequests = {
        fetchIFTLWeeksForFaculty,
        fetchIFTLFullSchedule
    };

    window.IFTLApi = {
        async loadWeeks(facultyId) {
            return fetchIFTLWeeksForFaculty(facultyId);
        },
        async loadFullSchedule(facultyId, weekIdentifier) {
            return fetchIFTLFullSchedule(facultyId, weekIdentifier);
        }
    };

    window.IFTLManager = {
        getCurrentFacultyId() {
            return currentIFTLFacultyId;
        },
        setCurrentFacultyId(facultyId) {
            currentIFTLFacultyId = facultyId;
        }
    };

    function schedulesOverlap(a, b) {
        if ((a.days || '') !== (b.days || '')) return false;
        const aStart = timeToMinutes(a.time_start);
        const aEnd = timeToMinutes(a.time_end);
        const bStart = timeToMinutes(b.time_start);
        const bEnd = timeToMinutes(b.time_end);
        return aStart < bEnd && aEnd > bStart;
    }

    function timeToMinutes(time) {
        if (!time) return 0;
        const [h, m] = String(time).split(':');
        return (parseInt(h || '0', 10) * 60) + parseInt(m || '0', 10);
    }

    function mergeScheduleEntries(baseSchedules, overlaySchedules) {
        const base = Array.isArray(baseSchedules) ? baseSchedules : [];
        const overlay = Array.isArray(overlaySchedules) ? overlaySchedules : [];
        if (overlay.length === 0) return base;

        const filteredBase = base.filter(baseEntry => !overlay.some(overlayEntry => schedulesOverlap(baseEntry, overlayEntry)));
        return [...filteredBase, ...overlay].sort((a, b) => {
            const dayOrder = { M: 1, T: 2, W: 3, TH: 4, F: 5, S: 6, SUN: 7 };
            const da = dayOrder[a.days] || 99;
            const db = dayOrder[b.days] || 99;
            if (da !== db) return da - db;
            return String(a.time_start || '').localeCompare(String(b.time_start || ''));
        });
    }

    function printFacultySchedule(facultyId, useIFTL = false) {
        const facultyName = window.facultyNames[facultyId] || 'Unknown Faculty';
        const deanName = (window.facultyDeanNames && window.facultyDeanNames[facultyId]) || 'Dean';
        const directorName = window.campusDirectorName || 'Campus Director';
        const period = (window.facultyPrintPeriods && window.facultyPrintPeriods[facultyId]) || {};
        const semester = period.semester || '1st';
        const academicYear = period.academic_year || '2025-2026';
        const baseSchedules = window.facultySchedules[facultyId] || [];
        const overlaySchedules = useIFTL && Array.isArray(window.iftlEntries) ? window.iftlEntries : [];
        const schedules = useIFTL ? mergeScheduleEntries(baseSchedules, overlaySchedules) : baseSchedules;
        const { mwfSchedules, tthSchedules } = separateSchedulesByType(schedules);
        const summary = calculateSummaryData(schedules);
        const printWindow = window.open('', '_blank', 'width=1200,height=800');
        printWindow.document.write(generatePrintHTML(facultyName, mwfSchedules, tthSchedules, summary, deanName, directorName, semester, academicYear, useIFTL));
        printWindow.document.close();
        printWindow.onload = () => {
            setTimeout(() => printWindow.print(), 500);
        };
    }

    function separateSchedulesByType(schedules) {
        const mwfSchedules = [];
        const tthSchedules = [];
        schedules.forEach(schedule => {
            const dayCodes = parseDayCodes(schedule.days || '');
            if (dayCodes.some(code => ['M', 'W', 'F'].includes(code))) mwfSchedules.push(schedule);
            if (dayCodes.some(code => ['T', 'TH', 'S'].includes(code))) tthSchedules.push(schedule);
        });
        return { mwfSchedules, tthSchedules };
    }

    function parseDayCodes(days) {
        const dayString = String(days || '').toUpperCase().replace(/\s+/g, '');
        const parsed = [];
        for (let i = 0; i < dayString.length;) {
            if (dayString.substr(i, 3) === 'SUN') {
                parsed.push('SUN');
                i += 3;
                continue;
            }
            if (dayString.substr(i, 3) === 'SAT') {
                parsed.push('S');
                i += 3;
                continue;
            }
            if (dayString.substr(i, 2) === 'TH') {
                parsed.push('TH');
                i += 2;
                continue;
            }
            const ch = dayString[i];
            if (['M', 'T', 'W', 'F', 'S'].includes(ch)) {
                parsed.push(ch);
            }
            i += 1;
        }
        return parsed;
    }

    function calculateSummaryData(schedules) {
        const preparations = new Set();
        let totalClassHours = 0;
        let lectureUnits = 0;
        let labUnits = 0;
        const subjectUnits = {};
        schedules.forEach(schedule => {
            preparations.add(schedule.course_code);
            const start = new Date(`2000-01-01 ${schedule.time_start}`);
            const end = new Date(`2000-01-01 ${schedule.time_end}`);
            const hours = (end - start) / (1000 * 60 * 60);
            const dayCount = countDaysInSchedule(schedule.days);
            totalClassHours += hours * dayCount;
            const units = parseFloat(schedule.units || 3);
            if (!(schedule.course_code in subjectUnits)) {
                subjectUnits[schedule.course_code] = units;
            }
        });
        lectureUnits = Object.values(subjectUnits).reduce((sum, u) => sum + u, 0);
        const actualTeachingLoad = lectureUnits + labUnits;
        const normalLoad = 18;
        const loadDisplacement = actualTeachingLoad;
        let overloadUnderloadValue = 0;
        let overloadUnderloadLabel = '';
        if (normalLoad > loadDisplacement) {
            overloadUnderloadValue = normalLoad - loadDisplacement;
            overloadUnderloadLabel = 'Underload';
        } else if (normalLoad < loadDisplacement) {
            overloadUnderloadValue = loadDisplacement - normalLoad;
            overloadUnderloadLabel = 'Overload';
        } else {
            overloadUnderloadValue = 0;
            overloadUnderloadLabel = 'Normal';
        }
        const maxDailyHours = 8;
        const officeHours = Math.max(0, maxDailyHours - Math.round(totalClassHours));
        const consultationHours = 4;
        const totalHours = totalClassHours + officeHours + consultationHours;
        return {
            preparations: preparations.size,
            loadDisplacement: loadDisplacement,
            researchLoad: 0,
            lectureUnits,
            labUnits,
            actualTeachingLoad,
            creditStudentFactor: 0,
            actualTotalLoad: actualTeachingLoad,
            normalLoad: normalLoad,
            overloadUnderloadValue: overloadUnderloadValue,
            overloadUnderloadLabel: overloadUnderloadLabel,
            classHours: Math.round(totalClassHours),
            officeHours,
            consultationHours,
            totalHours: Math.round(totalHours)
        };
    }

    function countDaysInSchedule(days) {
        const dayString = days.toUpperCase().replace(/\s/g, '');
        let count = 0;
        let i = 0;
        while (i < dayString.length) {
            if (i < dayString.length - 1 && dayString.substr(i, 2) === 'TH') {
                count++;
                i += 2;
            } else {
                count++;
                i++;
            }
        }
        return count;
    }

    function generateTimeSlots(type) {
        if (type === 'MWF') {
            return [
                '8:00-9:00', '9:00-10:00', '10:00-11:00', '11:00-12:00',
                '1:00-2:00', '2:00-3:00', '3:00-4:00', '4:00-5:00'
            ];
        }
        return [
            '7:30-9:00', '9:00-10:30', '10:30-12:00',
            '1:00-2:30', '2:30-4:00', '4:00-5:30'
        ];
    }

    function findScheduleForSlot(schedules, day, timeSlot) {
        const [slotStart, slotEnd] = timeSlot.split('-');
        const normalizeTime = (time) => {
            const parts = time.split(':');
            const hours = parts[0].padStart(2, '0');
            const minutes = parts[1] || '00';
            const seconds = parts[2] || '00';
            return `${hours}:${minutes}:${seconds}`;
        };
        const slotStartTime = normalizeTime(slotStart);
        const slotEndTime = normalizeTime(slotEnd);
        const dayMap = {
            MONDAY: 'M',
            WEDNESDAY: 'W',
            FRIDAY: 'F',
            TUESDAY: 'T',
            THURSDAY: 'TH',
            SATURDAY: 'S'
        };
        return schedules.find(schedule => {
            const scheduleDays = parseDayCodes(schedule.days || '');
            const hasDayMatch = scheduleDays.includes(dayMap[day]);
            const scheduleStart = normalizeTime(schedule.time_start);
            const scheduleEnd = normalizeTime(schedule.time_end);
            const isFirstSlot = slotStartTime === scheduleStart;
            const hasTimeMatch = slotStartTime < scheduleEnd && slotEndTime > scheduleStart && isFirstSlot;
            return hasDayMatch && hasTimeMatch;
        });
    }

    function generateScheduleGrid(type, schedules, useIFTL = false) {
        const days = type === 'MWF'
            ? ['MONDAY', 'WEDNESDAY', 'FRIDAY']
            : ['TUESDAY', 'THURSDAY', 'SATURDAY'];
        const timeSlots = generateTimeSlots(type);
        const occupiedCells = {};
        let html = `
        <table class="schedule-grid">
            <thead>
                <tr>
                    <th class="time-col">TIME</th>
                    ${days.map(day => `
                        <th>${day}</th>
                        <th>Yr/Crs/Sec</th>
                        <th>Room</th>
                        <th>No. of Students</th>
                    `).join('')}
                </tr>
            </thead>
            <tbody>
    `;
        timeSlots.forEach((slot, slotIndex) => {
            html += `<tr><td class="time-col">${slot}</td>`;
            days.forEach((day, dayIndex) => {
                const cellKey = `${slotIndex}-${dayIndex}`;
                if (occupiedCells[cellKey]) {
                    return;
                }

                const [slotStart] = slot.split('-');
                let slotHour;
                let slotMinute;
                if (slotStart.includes(':')) {
                    [slotHour, slotMinute] = slotStart.split(':');
                } else {
                    slotHour = slotStart;
                    slotMinute = '00';
                }
                if (slotHour.length === 1) slotHour = '0' + slotHour;
                if (parseInt(slotHour, 10) < 7) slotHour = (parseInt(slotHour, 10) + 12).toString().padStart(2, '0');
                const slotStartNorm = `${slotHour}:${slotMinute.padStart(2, '0')}:00`;

                const targetDayCode = day === 'THURSDAY' ? 'TH' : day[0];
                const schedule = schedules.find(e => {
                    const parsedDays = parseDayCodes(e.days || '');
                    return e.time_start === slotStartNorm && parsedDays.includes(targetDayCode);
                });

                if (schedule) {
                    const startHour = parseInt(schedule.time_start.split(':')[0], 10);
                    const endHour = parseInt(schedule.time_end.split(':')[0], 10);
                    const duration = endHour - startHour;
                    for (let i = 0; i < duration; i++) {
                        occupiedCells[`${slotIndex + i}-${dayIndex}`] = true;
                    }
                    const rowspanAttr = duration > 1 ? ` rowspan="${duration}"` : '';
                    html += `
                    <td${rowspanAttr}>${schedule.course_code || ''}</td>
                    <td${rowspanAttr}>${schedule.class_code || ''}</td>
                    <td${rowspanAttr}>${schedule.room || ''}</td>
                    <td${rowspanAttr}>${schedule.total_students !== undefined && schedule.total_students !== null ? schedule.total_students : ''}</td>
                `;
                } else {
                    html += '<td></td><td></td><td></td><td></td>';
                }
            });
            html += '</tr>';
        });
        html += `
            </tbody>
            <tfoot>
                <tr>
                    <td>Class Hours</td>
                    ${days.map(() => '<td></td><td></td><td></td><td>2</td>').join('')}
                </tr>
                <tr>
                    <td>Office Hours</td>
                    ${days.map(() => '<td></td><td></td><td></td><td>8</td>').join('')}
                </tr>
                <tr>
                    <td>Total</td>
                    ${days.map(() => '<td></td><td></td><td></td><td>8</td>').join('')}
                </tr>
            </tfoot>
        </table>
    `;
        return html;
    }

    function calculateRowspan(startTime, endTime, scheduleType, renderedSlot = null) {
        const start = new Date(`2000-01-01 ${startTime}`);
        const end = new Date(`2000-01-01 ${endTime}`);
        const durationHours = (end - start) / (1000 * 60 * 60);
        const slotDuration = scheduleType === 'MWF' ? 1 : 1.5;
        return Math.max(1, Math.ceil(durationHours / slotDuration));
    }

    function formatSemesterLabel(semester) {
        const value = String(semester || '').trim().toLowerCase();
        if (value === '1st' || value === 'first') return 'First Semester';
        if (value === '2nd' || value === 'second') return 'Second Semester';
        if (value === 'summer') return 'Summer';
        return semester || 'Semester';
    }

    function generatePrintHTML(facultyName, mwfSchedules, tthSchedules, summary, deanName, directorName, semester, academicYear, useIFTL = false) {
        const safeDeanName = deanName || 'Dean';
        const safeDirectorName = directorName || 'Campus Director';
        const semesterLabel = formatSemesterLabel(semester);
        const ayLabel = academicYear || '2025-2026';
        return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Faculty Schedule - ${facultyName}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.5cm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 7pt;
            padding: 8px;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .container {
            display: grid;
            grid-template-columns: 1fr 200px;
            gap: 10px;
            max-width: 1100px;
            width: 100%;
            align-items: start;
        }
        .left-section {
            display: flex;
            flex-direction: column;
        }
        .summary-box {
            border: 1px solid #000;
            padding: 5px;
            font-size: 6.5pt;
            height: fit-content;
            margin-top: 0;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 8px;
            grid-column: 1 / -1;
            width: 100%;
        }
        .header-text {
            text-align: center;
        }
        .logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        .header h2 {
            font-size: 11pt;
            margin: 2px 0;
            font-weight: bold;
        }
        .header h3 {
            font-size: 10pt;
            margin: 2px 0;
            font-weight: bold;
        }
        .header p {
            font-size: 9pt;
            margin: 2px 0;
            font-weight: bold;
        }
        .faculty-info {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 5px;
            margin-bottom: 8px;
            font-size: 7pt;
            border: 1px solid #000;
            padding: 4px;
        }
        .faculty-info div {
            display: flex;
            gap: 3px;
        }
        .faculty-info strong {
            min-width: 70px;
        }
        .schedule-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            font-size: 6pt;
        }
        .schedule-grid th,
        .schedule-grid td {
            border: 1px solid #000;
            padding: 1px 2px;
            text-align: center;
            height: 14px;
            overflow: hidden;
        }
        .schedule-grid thead th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 6.5pt;
        }
        .time-col {
            width: 55px;
            font-size: 5.5pt;
        }
        .schedule-grid tbody td {
            font-size: 5.5pt;
        }
        .schedule-grid tfoot td {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .summary-box h4 {
            text-align: center;
            margin-bottom: 5px;
            font-size: 7pt;
            font-weight: bold;
        }
        .summary-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-box td {
            padding: 1px 2px;
            border-bottom: 1px solid #ddd;
            font-size: 6pt;
        }
        .summary-box td:first-child {
            text-align: left;
        }
        .summary-box td:last-child {
            text-align: right;
            font-weight: bold;
            width: 30px;
        }
        .footer {
            margin-top: 8px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            font-size: 6.5pt;
            grid-column: 1 / -1;
        }
        .footer-section {
            text-align: center;
        }
        .footer-section p {
            margin: 2px 0;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 25px;
            padding-top: 2px;
            font-weight: bold;
        }
        @media print {
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header" style="position:relative;">
            <div style="position:absolute; top:0; right:0; text-align:right; font-size:8pt; font-weight:bold; z-index:10; background:transparent;">
                SKSU-INS-MCI-08<br>
                Revision: 00<br>
                Effective Date: July 07, 2025
            </div>
            <img src="assets/images/logo1.png" alt="SKSU Logo" class="logo" onerror="this.style.display='none'">
            <div class="header-text">
                <h2>SULTAN KUDARAT STATE UNIVERSITY</h2>
                <h3>ACCESS Campus, EJC Montilla, Tacurong City</h3>
                <h3>INDIVIDUAL FACULTY TIME AND LOCATION</h3>
                <p>${semesterLabel}, AY ${ayLabel}</p>
            </div>
            <img src="assets/images/logo2.png" alt="ACCESS Logo" class="logo" onerror="this.style.display='none'">
        </div>
        <div class="left-section">
            <div class="faculty-info">
                <div><strong>Name:</strong> <span>${facultyName}</span></div>
                <div><strong>College:</strong> <span>Teacher Education</span></div>
                <div><strong>Department:</strong> <span>Secondary</span></div>
                <div><strong>Designation:</strong> <span>Division Director</span></div>
            </div>
            ${generateScheduleGrid('MWF', mwfSchedules, useIFTL)}
            ${generateScheduleGrid('TTH', tthSchedules, useIFTL)}
            <div class="footer">
                <div class="footer-section">
                    <p>Prepared by:</p>
                    <p class="signature-line">${facultyName}</p>
                    <p>Faculty</p>
                </div>
                <div class="footer-section">
                    <p style="margin-top: 8px;">Recommending Approval:</p>
                    <p class="signature-line">${safeDeanName}</p>
                    <p>Dean</p>
                </div>
                <div class="footer-section">
                    <p>Approved by:</p>
                    <p class="signature-line">${safeDirectorName}</p>
                    <p>Campus Director</p>
                </div>
            </div>
        </div>
        <div class="summary-box">
            <h4>SUMMARY OF INFORMATION</h4>
            <table>
                <tr><td>1. No. of Preparation</td><td>${summary.preparations}</td></tr>
                <tr><td>2. Load Displacement</td><td>${summary.loadDisplacement}</td></tr>
                <tr><td>3. Research/Extension Load</td><td>${summary.researchLoad}</td></tr>
                <tr><td>4. Lecture Units</td><td>${summary.lectureUnits}</td></tr>
                <tr><td>5. Laboratory Units</td><td>${summary.labUnits}</td></tr>
                <tr><td>6. Actual Teaching Load (4+5)</td><td>${summary.actualTeachingLoad}</td></tr>
                <tr><td>7. Credit for Student Factor</td><td>${summary.creditStudentFactor}</td></tr>
                <tr><td>8. Actual Total Load (6 + 7)</td><td>${summary.actualTotalLoad}</td></tr>
                <tr><td>9. Normal Load</td><td>${summary.normalLoad}</td></tr>
                <tr><td>10. Overload/Underload</td><td>${summary.overloadUnderloadLabel}: ${summary.overloadUnderloadValue}</td></tr>
                <tr><td>11. Class Hours</td><td>${summary.classHours}</td></tr>
                <tr><td>12. Office Hours</td><td>${summary.officeHours}</td></tr>
                <tr><td>13. Consultation Hours</td><td>${summary.consultationHours}</td></tr>
                <tr><td>14. Total Hours (add #11, 12 & 13)</td><td>${summary.totalHours}</td></tr>
            </table>
            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
                <p style="margin: 3px 0;"><strong>Prepared by:</strong></p>
                <p style="margin: 15px 0 3px 0; border-top: 1px solid #000; padding-top: 2px; font-weight: bold;">${facultyName}</p>
                <p style="margin: 3px 0;">Faculty</p>
                <p style="margin: 10px 0 3px 0;"><strong>CC:</strong></p>
                <p style="margin: 2px 0;">Faculty</p>
                <p style="margin: 2px 0;">Dean</p>
                <p style="margin: 2px 0;">VPAA</p>
            </div>
        </div>
    </div>
</body>
</html>
    `;
    }

    function refreshFacultySchedules() {
        return fetch('program.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=get_faculty_schedules'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Object.keys(data.faculty_schedules).forEach(facultyId => {
                        window.facultySchedules[facultyId] = data.faculty_schedules[facultyId];
                    });
                    console.log('Faculty schedules refreshed successfully');
                }
            })
            .catch(error => {
                console.error('Error refreshing faculty schedules:', error);
            });
    }

    async function openIFTLModal(facultyId, facultyName) {
        window.facultyNames[facultyId] = facultyName;
        currentIFTLFacultyId = facultyId;
        const title = document.getElementById('iftlFacultyName');
        if (title) {
            title.textContent = facultyName;
        }
        if (typeof openModal === 'function') {
            openModal('directorIFTLModal');
        } else {
            const modal = document.getElementById('directorIFTLModal');
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }
        loadDirectorIFTLWeeksButtons();
        const content = document.getElementById('iftlContent');
        if (content) {
            content.innerHTML = '<div class="loading">Select a week to print IFTL.</div>';
        }
    }

    async function loadDirectorIFTLWeeksButtons() {
        const weekBtnContainer = document.getElementById('iftlWeekBtnContainer');
        if (!weekBtnContainer) return;
        weekBtnContainer.innerHTML = '<div class="loading">Loading weeks...</div>';
        try {
            const result = await window.IFTLApi.loadWeeks(currentIFTLFacultyId);
            if (result.success) {
                weekBtnContainer.innerHTML = '';
                result.weeks.forEach(week => {
                    const btn = document.createElement('button');
                    btn.className = 'iftl-week-btn';
                    btn.textContent = week.label;
                    btn.dataset.identifier = week.identifier;
                    if (week.is_current) btn.classList.add('current-week');
                    if (week.is_submitted) {
                        btn.classList.add('is-submitted');
                        btn.disabled = false;
                        btn.onclick = () => printIFTLForWeekBtn(week.identifier);
                    } else {
                        btn.classList.add('no-entry');
                        btn.disabled = true;
                    }
                    weekBtnContainer.appendChild(btn);
                });
            } else {
                weekBtnContainer.innerHTML = '<div class="error">Error loading weeks</div>';
            }
        } catch (e) {
            weekBtnContainer.innerHTML = '<div class="error">Error loading weeks</div>';
        }
    }

    async function printIFTLForWeekBtn(weekIdentifier) {
        if (!currentIFTLFacultyId) return;
        try {
            const result = await window.IFTLApi.loadFullSchedule(currentIFTLFacultyId, weekIdentifier);
            if (result.success) {
                window.iftlEntries = result.schedules;
                if (result.dean_name) {
                    window.facultyDeanNames[currentIFTLFacultyId] = result.dean_name;
                }
                window.facultyPrintPeriods[currentIFTLFacultyId] = {
                    semester: result.semester || null,
                    academic_year: result.academic_year || null
                };
                if (typeof printFacultySchedule === 'function') {
                    printFacultySchedule(currentIFTLFacultyId, true);
                }
            } else if (typeof showNotification === 'function') {
                showNotification(result.message || 'Failed to load schedule', 'error');
            }
        } catch (e) {
            if (typeof showNotification === 'function') {
                showNotification('Error loading schedule', 'error');
            }
        }
    }

    async function loadDirectorIFTLWeeks() {
        const select = document.getElementById('iftlWeekSelect');
        if (!select) return;
        select.innerHTML = '<option>Loading weeks...</option>';
        try {
            const response = await fetch('assets/php/polling_api.php?action=get_iftl_weeks_month');
            const result = await response.json();
            if (result.success) {
                select.innerHTML = '';
                result.weeks.forEach(week => {
                    const option = document.createElement('option');
                    option.value = week.identifier;
                    option.textContent = week.label;
                    option.dataset.startDate = week.start_date;
                    if (week.is_current) option.selected = true;
                    select.appendChild(option);
                });
                select.onchange = () => printIFTLForWeek();
            } else {
                select.innerHTML = '<option>Error loading weeks</option>';
            }
        } catch (e) {
            select.innerHTML = '<option>Error loading weeks</option>';
        }
    }

    async function printIFTLForWeek() {
        const weekSelect = document.getElementById('iftlWeekSelect');
        if (!weekSelect || !currentIFTLFacultyId) return;
        const week = weekSelect.value;
        try {
            const result = await window.IFTLApi.loadFullSchedule(currentIFTLFacultyId, week);
            if (result.success) {
                window.iftlEntries = result.schedules;
                if (result.dean_name) {
                    window.facultyDeanNames[currentIFTLFacultyId] = result.dean_name;
                }
                window.facultyPrintPeriods[currentIFTLFacultyId] = {
                    semester: result.semester || null,
                    academic_year: result.academic_year || null
                };
                if (typeof printFacultySchedule === 'function') {
                    printFacultySchedule(currentIFTLFacultyId, true);
                }
            } else if (typeof showNotification === 'function') {
                showNotification(result.message || 'Failed to load schedule', 'error');
            }
        } catch (e) {
            if (typeof showNotification === 'function') {
                showNotification('Error loading schedule', 'error');
            }
        }
    }

    window.printFacultySchedule = printFacultySchedule;
    window.separateSchedulesByType = separateSchedulesByType;
    window.parseDayCodes = parseDayCodes;
    window.calculateSummaryData = calculateSummaryData;
    window.countDaysInSchedule = countDaysInSchedule;
    window.generateTimeSlots = generateTimeSlots;
    window.findScheduleForSlot = findScheduleForSlot;
    window.generateScheduleGrid = generateScheduleGrid;
    window.calculateRowspan = calculateRowspan;
    window.formatSemesterLabel = formatSemesterLabel;
    window.generatePrintHTML = generatePrintHTML;
    window.refreshFacultySchedules = refreshFacultySchedules;
    window.openIFTLModal = openIFTLModal;
    window.loadDirectorIFTLWeeksButtons = loadDirectorIFTLWeeksButtons;
    window.printIFTLForWeekBtn = printIFTLForWeekBtn;
    window.loadDirectorIFTLWeeks = loadDirectorIFTLWeeks;
    window.printIFTLForWeek = printIFTLForWeek;
})();
