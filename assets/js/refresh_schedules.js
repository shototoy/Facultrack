function refreshFacultySchedules() {
    return fetch('program.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_faculty_schedules'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Object.keys(data.faculty_schedules).forEach(facultyId => {
                    facultySchedules[facultyId] = data.faculty_schedules[facultyId];
                });
                console.log('Faculty schedules refreshed successfully');
            }
        })
        .catch(error => {
            console.error('Error refreshing faculty schedules:', error);
        });
}

