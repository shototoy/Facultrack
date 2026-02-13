function openModal(modalId) {
    closeModal();
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        resetPaginatedModal(modal);
    }
}
function closeModal() {
    document.querySelectorAll('.modal-overlay.show').forEach(modal => modal.classList.remove('show'));
    document.body.style.overflow = '';
}
function resetPaginatedModal(modal) {
    if (!modal) return;
    const forms = modal.querySelectorAll('form[data-paginated="true"]');
    forms.forEach(form => {
        const pages = form.querySelectorAll('.modal-page');
        pages.forEach((page, index) => {
            const isFirstPage = index === 0;
            page.classList.toggle('active', isFirstPage);
            page.style.display = isFirstPage ? '' : 'none';
        });
    });
}
function validateCurrentModalPage(form) {
    const currentPage = form.querySelector('.modal-page.active');
    if (!currentPage) return true;
    const requiredFields = currentPage.querySelectorAll('input[required], select[required], textarea[required]');
    for (const field of requiredFields) {
        if (!field.checkValidity()) {
            field.reportValidity();
            return false;
        }
    }
    return true;
}
function goToModalPage(form, pageIndex) {
    const pages = Array.from(form.querySelectorAll('.modal-page'));
    if (pages.length === 0 || pageIndex < 0 || pageIndex >= pages.length) return;
    pages.forEach((page, index) => {
        const isActive = index === pageIndex;
        page.classList.toggle('active', isActive);
        page.style.display = isActive ? '' : 'none';
    });
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
        const response = await fetch('assets/php/polling_api.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            switchTab(tabName);
            setTimeout(() => {
                addNewRowToTable(tabName, result.data);
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
        input.value = `${y}-${(y + 1).toString().slice(-2)}`;
        input.style.backgroundColor = '#f8f9fa';
        input.title = 'Auto-generated based on current year (for new classes only)';
        input.readOnly = true;
    }
}
document.addEventListener('DOMContentLoaded', () => {
    setupClassCodeGeneration();
    setupAcademicYear();
    document.querySelectorAll('.modal-overlay').forEach(modal => resetPaginatedModal(modal));
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
    const nextBtn = e.target.closest('.modal-next-btn');
    if (nextBtn) {
        const form = nextBtn.closest('form');
        if (form && validateCurrentModalPage(form)) {
            const pages = Array.from(form.querySelectorAll('.modal-page'));
            const currentIndex = pages.findIndex(page => page.classList.contains('active'));
            goToModalPage(form, currentIndex + 1);
        }
        return;
    }

    const prevBtn = e.target.closest('.modal-prev-btn');
    if (prevBtn) {
        const form = prevBtn.closest('form');
        if (form) {
            const pages = Array.from(form.querySelectorAll('.modal-page'));
            const currentIndex = pages.findIndex(page => page.classList.contains('active'));
            goToModalPage(form, currentIndex - 1);
        }
        return;
    }

    const modalId =
        e.target.closest('button[data-modal]')?.dataset.modal ||
        e.target.closest('.add-card[data-modal]')?.dataset.modal;
    if (modalId) {
        openModal(modalId);
    }
});
function toggleSearch() {
    const container = document.getElementById('searchContainer');
    const searchInput = document.getElementById('searchInput');
    if (!container || !searchInput) {
        return;
    }
    if (container.classList.contains('collapsed')) {
        container.classList.remove('collapsed');
        container.classList.add('expanded');
        setTimeout(() => {
            searchInput.focus();
        }, 400);
    } else {
        container.classList.remove('expanded');
        container.classList.add('collapsed');
        searchInput.blur();
        if (searchInput.value.trim() === '') {
            searchInput.value = '';
        }
    }
}
window.toggleSearch = toggleSearch;
function setupSearchFunctionality() {
    const searchInput = document.getElementById('searchInput');
    const searchContainer = document.getElementById('searchContainer');
    const searchBar = document.querySelector('.search-bar');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            if (typeof searchContent === 'function') {
                searchContent();
            }
        });
    }
    if (searchContainer && searchBar) {
        document.addEventListener('click', function(event) {
            if (!searchBar.contains(event.target) &&
                !event.target.closest('.search-toggle') &&
                searchContainer.classList.contains('expanded')) {
                if (searchInput.value.trim() === '') {
                    toggleSearch();
                }
            }
        });
    }
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupSearchFunctionality);
} else {
    setupSearchFunctionality();
}

