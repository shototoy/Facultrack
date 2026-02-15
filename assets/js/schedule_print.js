function printFacultySchedule(facultyId) {
    const facultyName = facultyNames[facultyId] || 'Unknown Faculty';
    const deanName = (window.facultyDeanNames && window.facultyDeanNames[facultyId]) || 'Dean';
    const directorName = window.campusDirectorName || 'Campus Director';
    const period = (window.facultyPrintPeriods && window.facultyPrintPeriods[facultyId]) || {};
    const semester = period.semester || '1st';
    const academicYear = period.academic_year || '2025-2026';
    const schedules = facultySchedules[facultyId] || [];
    const { mwfSchedules, tthSchedules } = separateSchedulesByType(schedules);
    const summary = calculateSummaryData(schedules);
    const printWindow = window.open('', '_blank', 'width=1200,height=800');
    printWindow.document.write(generatePrintHTML(facultyName, mwfSchedules, tthSchedules, summary, deanName, directorName, semester, academicYear));
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
    schedules.forEach(schedule => {
        preparations.add(schedule.course_code);
        const start = new Date(`2000-01-01 ${schedule.time_start}`);
        const end = new Date(`2000-01-01 ${schedule.time_end}`);
        const hours = (end - start) / (1000 * 60 * 60);
        const dayCount = countDaysInSchedule(schedule.days);
        totalClassHours += hours * dayCount;
        const units = parseFloat(schedule.units || 3);
        lectureUnits += units;
    });
    const actualTeachingLoad = lectureUnits + labUnits;
    const normalLoad = 18;
    const overload = actualTeachingLoad - normalLoad;
    const maxDailyHours = 8;
    const officeHours = Math.max(0, maxDailyHours - Math.round(totalClassHours));
    const consultationHours = 4;
    const totalHours = totalClassHours + officeHours + consultationHours;
    return {
        preparations: preparations.size,
        loadDisplacement: 12,
        researchLoad: 0,
        lectureUnits,
        labUnits,
        actualTeachingLoad,
        creditStudentFactor: 0,
        actualTotalLoad: actualTeachingLoad,
        normalLoad,
        overload,
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
    } else {
        return [
            '7:30-9:00', '9:00-10:30', '10:30-12:00',
            '1:00-2:30', '2:30-4:00', '4:00-5:30'
        ];
    }
}
function findScheduleForSlot(schedules, day, timeSlot) {
    const [slotStart] = timeSlot.split('-');
    const normalizeTime = (time) => {
        const parts = time.split(':');
        const hours = parts[0].padStart(2, '0');
        const minutes = parts[1] || '00';
        const seconds = parts[2] || '00';
        return `${hours}:${minutes}:${seconds}`;
    };
    const slotStartTime = normalizeTime(slotStart);
    const dayMap = {
        'MONDAY': 'M',
        'WEDNESDAY': 'W',
        'FRIDAY': 'F',
        'TUESDAY': 'T',
        'THURSDAY': 'TH',
        'SATURDAY': 'S'
    };
    return schedules.find(schedule => {
        const scheduleDays = parseDayCodes(schedule.days || '');
        const hasDayMatch = scheduleDays.includes(dayMap[day]);
        const scheduleStart = normalizeTime(schedule.time_start);
        const scheduleEnd = normalizeTime(schedule.time_end);
        const hasTimeMatch = slotStartTime >= scheduleStart && slotStartTime < scheduleEnd;
        return hasDayMatch && hasTimeMatch;
    });
}
function generateScheduleGrid(type, schedules) {
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
            const schedule = findScheduleForSlot(schedules, day, slot);
            if (schedule) {
                const rowspan = calculateRowspan(schedule.time_start, schedule.time_end, type, slot);
                for (let i = 1; i < rowspan; i++) {
                    occupiedCells[`${slotIndex + i}-${dayIndex}`] = true;
                }
                const rowspanAttr = rowspan > 1 ? ` rowspan="${rowspan}"` : '';
                html += `
                    <td${rowspanAttr}>${schedule.course_code || ''}</td>
                    <td${rowspanAttr}>${schedule.class_name || ''}</td>
                    <td${rowspanAttr}>${schedule.room || ''}</td>
                    <td${rowspanAttr}>${schedule.total_students !== undefined && schedule.total_students !== null ? schedule.total_students : ''}</td>
                `;
            } else {
                html += `<td></td><td></td><td></td><td></td>`;
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
    const renderedStart = renderedSlot ? `${renderedSlot.split('-')[0]}:00` : startTime;
    const start = new Date(`2000-01-01 ${renderedStart}`);
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
function generatePrintHTML(facultyName, mwfSchedules, tthSchedules, summary, deanName, directorName, semester, academicYear) {
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
        <div class="header">
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
            ${generateScheduleGrid('MWF', mwfSchedules)}
            ${generateScheduleGrid('TTH', tthSchedules)}
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
                <tr><td>10. Overload/Underload</td><td>${summary.overload}</td></tr>
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

