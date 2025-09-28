function openModal(modalId) {
    closeModal();
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal() {
    document.querySelectorAll('.modal-overlay.show').forEach(modal => modal.classList.remove('show'));
    document.body.style.overflow = '';
}

function submitGenericForm(formElement) {
    const action = formElement.dataset.action;
    const tab = formElement.dataset.tab;
    submitForm(action, formElement.id, tab);
}

async function submitForm(action, formId, tabName) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    formData.append('action', action);

    try {
        showFormLoading(formId, true);

        const response = await fetch('handle_admin_actions.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            switchTab(tabName);
            setTimeout(() => {
                addNewRowToTable(tabName, result.data);
                updateStatistics();
            }, 100);

            closeModal();
            form.reset();
            showNotification(result.message, 'success');
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('An unexpected error occurred.', 'error');
    } finally {
        showFormLoading(formId, false);
    }
}

function showFormLoading(formId, isLoading) {
    const form = document.getElementById(formId);
    const submitBtn = form?.querySelector('button[type="submit"]');
    if (!submitBtn) return;

    submitBtn.disabled = isLoading;
    submitBtn.innerHTML = isLoading
        ? '<span class="loading-spinner"></span> Processing...'
        : submitBtn.dataset.originalText || 'Submit';
}

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

function setupClassCodeGeneration() {
    const classNameInput = document.getElementById('class_name');
    const yearLevelSelect = document.getElementById('year_level');
    const classCodeInput = document.getElementById('class_code');

    function generateClassCode() {
        if (!classNameInput || !yearLevelSelect || !classCodeInput) return;

        const className = classNameInput.value.trim();
        const yearLevel = yearLevelSelect.value;
        if (!className || !yearLevel) return;

        const skipWords = ['bs', 'bachelor', 'of', 'science', 'arts', 'master', 'masters', 'in'];
        const acronym = className
            .split(' ')
            .filter(word => !skipWords.includes(word.toLowerCase()))
            .map(word => word.charAt(0).toUpperCase())
            .join('');

        const prefix = className.toLowerCase().includes('bs ') || className.toLowerCase().includes('bachelor') ? 'BS' : '';
        classCodeInput.value = `${prefix}${acronym}-${yearLevel}A`;
    }

    classNameInput?.addEventListener('input', generateClassCode);
    yearLevelSelect?.addEventListener('change', generateClassCode);
}

function setupAcademicYear() {
    const input = document.getElementById('academic_year');
    if (input && !input.value) {
        const y = new Date().getFullYear();
        input.value = `${y}-${y + 1}`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setupClassCodeGeneration();
    setupAcademicYear();

    document.querySelectorAll('button[type="submit"]').forEach(btn => {
        btn.dataset.originalText = btn.innerHTML;
    });
});

document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) closeModal();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelector('.modal-overlay.show')?.classList.remove('show');
        document.body.style.overflow = '';
    }
});

document.addEventListener('click', function(e) {
    const modalId =
        e.target.closest('button[data-modal]')?.dataset.modal ||
        e.target.closest('.add-card[data-modal]')?.dataset.modal;

    if (modalId) {
        openModal(modalId);
    }
});
