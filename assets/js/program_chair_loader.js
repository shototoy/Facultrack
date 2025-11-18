document.addEventListener('DOMContentLoaded', function() {
    loadProgramChairs();
    loadProgramsForCourse();
});

async function loadProgramChairs() {
    const select = document.getElementById('programChairSelect');
    if (!select) return;
    
    try {
        const response = await fetch('assets/php/polling_api.php?action=get_statistics');
        const data = await response.json();
        
        if (data.success && data.program_chairs) {
            select.innerHTML = '<option value="">Select Program Chair (Optional)</option>';
            data.program_chairs.forEach(chair => {
                const option = document.createElement('option');
                option.value = chair.user_id;
                option.textContent = `${chair.full_name} (${chair.program})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading program chairs:', error);
    }
}

async function loadProgramsForCourse() {
    const select = document.getElementById('programSelectCourse');
    if (!select) return;
    
    try {
        const response = await fetch('assets/php/polling_api.php?action=get_programs');
        const data = await response.json();
        
        if (data.success && data.programs) {
            select.innerHTML = '<option value="">Select Program</option>';
            data.programs.forEach(program => {
                const option = document.createElement('option');
                option.value = program.program_id;
                option.textContent = `${program.program_name} (${program.program_code})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading programs:', error);
    }
}