function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}
class LivePollingManager {
    constructor() {
        this.intervals = {};
        this.isActive = true;
        this.isOnline = navigator.onLine;
        this.updateQueue = [];
        this.defaultIntervals = {
            statistics: 3000,
            location: 3000,
            announcements: 3000,
            tables: 3000,
            schedules: 3000,
            courses: 3000,
            classes: 3000
        };
        this.visibilityObserver = null;
        this.currentTab = this.detectInitialTab();
        this.visibleElements = new Set();
        this.heartbeatInterval = null;
        this.pageType = this.detectPageType();
        this.observableElements = this.getObservableElementsForPage();
        this.lastStatusCheck = {};  // Track last known status for each faculty
        this.initialized = false;   // Track if this is initial load
        this.setupVisibilityHandling();
        this.setupNetworkHandling();
        this.setupIntersectionObserver();
        this.startHeartbeat();
        this.init();
    }
    detectPageType() {
        const title = document.title.toLowerCase();
        const url = window.location.pathname.toLowerCase();
        if (title.includes('faculty dashboard') || url.includes('faculty.php')) {
            return 'faculty';
        } else if (title.includes('director dashboard') || url.includes('director.php')) {
            return 'director';
        } else if (title.includes('program chair') || url.includes('program.php')) {
            return 'program';
        } else if (title.includes('class dashboard') || url.includes('home.php')) {
            return 'class';
        }
        return 'unknown';
    }
    detectInitialTab() {
        const activeTab = document.querySelector('.tab-content.active');
        if (activeTab) {
            return activeTab.id.replace('-content', '');
        }
        return 'faculty'; // default fallback
    }
    getObservableElementsForPage() {
        const elements = {};
        const statCards = document.querySelectorAll('.header-stat, .stat-card');
        if (statCards.length > 0) {
            elements.statistics = {
                selector: '.header-stat, .stat-card',
                description: 'Stat Cards',
                polling: 'statistics'
            };
        }
        switch (this.pageType) {
            case 'director':
                this.addDirectorElements(elements);
                break;
            case 'faculty':
                this.addFacultyElements(elements);
                break;
            case 'program':
                this.addProgramElements(elements);
                break;
            case 'class':
                this.addClassElements(elements);
                break;
        }
        return elements;
    }
    addDirectorElements(elements) {
        const tabs = ['faculty', 'classes', 'courses', 'announcements'];
        tabs.forEach(tab => {
            const tabElement = document.querySelector(`#${tab}-content`);
            if (tabElement) {
                elements[`${tab}_tab`] = {
                    selector: `#${tab}-content`,
                    description: `${tab.charAt(0).toUpperCase() + tab.slice(1)} Tab`,
                    polling: 'tables',
                    condition: () => {
                        const tabContent = document.querySelector(`#${tab}-content`);
                        const isActive = tabContent?.classList.contains('active');
                        return isActive;
                    }
                };
                const tableElement = tabElement.querySelector('.data-table');
                if (tableElement) {
                    elements[`${tab}_table`] = {
                        selector: `#${tab}-content .data-table`,
                        description: `${tab.charAt(0).toUpperCase() + tab.slice(1)} Table`,
                        polling: 'tables',
                        condition: () => {
                            const tabContent = document.querySelector(`#${tab}-content`);
                            const isActive = tabContent?.classList.contains('active');
                            const hasTable = !!tabContent?.querySelector('.data-table');
                            return isActive && hasTable;
                        }
                    };
                }
            }
        });
    }
    addFacultyElements(elements) {
        const locationElements = document.querySelectorAll('.faculty-location, .current-location-display');
        if (locationElements.length > 0) {
            elements.location = {
                selector: '.faculty-location, .current-location-display',
                description: 'Location',
                polling: 'location'
            };
        }
        const scheduleElements = document.querySelectorAll('.schedule-container, .schedule-section');
        if (scheduleElements.length > 0) {
            elements.schedules = {
                selector: '.schedule-container, .schedule-section',
                description: 'Schedule',
                polling: 'schedules'
            };
        }
    }
    addProgramElements(elements) {
        const tabs = ['faculty', 'classes', 'courses'];
        tabs.forEach(tab => {
            const tabElement = document.querySelector(`#${tab}-content`);
            if (tabElement) {
                elements[`${tab}_tab`] = {
                    selector: `#${tab}-content`,
                    description: `${tab.charAt(0).toUpperCase() + tab.slice(1)} Tab`,
                    polling: 'cards',
                    condition: () => document.querySelector(`#${tab}-content`)?.classList.contains('active')
                };
            }
        });
        const facultyGrid = document.querySelector('.faculty-grid');
        if (facultyGrid) {
            elements.faculty_cards = {
                selector: '.faculty-grid .faculty-card',
                description: 'Faculty Cards',
                polling: 'location'
            };
        }
        const coursesGrid = document.querySelector('.courses-grid');
        if (coursesGrid) {
            elements.course_cards = {
                selector: '.courses-grid .course-card',
                description: 'Course Cards',
                polling: 'cards',
                condition: () => document.querySelector('#courses-content')?.classList.contains('active')
            };
        }
    }
    addClassElements(elements) {
        const scheduleElements = document.querySelectorAll('.schedule-container');
        if (scheduleElements.length > 0) {
            elements.schedules = {
                selector: '.schedule-container',
                description: 'Schedule',
                polling: 'schedules'
            };
        }
        const announcementElements = document.querySelectorAll('.announcements-section');
        if (announcementElements.length > 0) {
            elements.announcements = {
                selector: '.announcements-section',
                description: 'Announcements',
                polling: 'announcements'
            };
        }
    }
    init() {
        if (typeof window.userRole !== 'undefined') {
            const pageName = document.title.split(' - ')[1] || 'Dashboard';
            setTimeout(() => {
                this.logCurrentStatus();
            }, 300);
            this.startPolling();
        }
    }
    setupVisibilityHandling() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pausePolling();
            } else {
                this.resumePolling();
                this.processUpdateQueue();
            }
        });
        window.addEventListener('beforeunload', () => {
            this.stopAllPolling();
        });
        document.addEventListener('click', (e) => {
            const tabButton = e.target.closest('.tab-button');
            if (tabButton && tabButton.dataset.tab) {
                const previousTab = this.currentTab;
                this.currentTab = tabButton.dataset.tab;
                console.clear();
                const pageName = document.title.split(' - ')[1] || 'Dashboard';
                console.log(`Page: ${pageName}`);
                this.logCurrentStatus();
                this.refreshVisibleElements();
            }
        });
    }
    setupNetworkHandling() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.processUpdateQueue();
            this.resumePolling();
        });
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.pausePolling();
        });
    }
    setupIntersectionObserver() {
        this.visibilityObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.visibleElements.add(entry.target.id || entry.target.className);
                } else {
                    this.visibleElements.delete(entry.target.id || entry.target.className);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });
        setTimeout(() => this.observeElements(), 100);
    }
    observeElements() {
        const statCards = document.querySelectorAll('.header-stat');
        statCards.forEach(el => {
            this.visibilityObserver.observe(el);
        });
        const tables = document.querySelectorAll('.data-table');
        tables.forEach(el => {
            this.visibilityObserver.observe(el);
        });
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(el => {
            this.visibilityObserver.observe(el);
        });
        const locationElements = document.querySelectorAll('.faculty-location, .current-location-display');
        locationElements.forEach(el => {
            this.visibilityObserver.observe(el);
        });
    }
    refreshVisibleElements() {
        this.visibleElements.clear();
        const statCards = document.querySelectorAll('.header-stat');
        statCards.forEach(el => {
            this.visibilityObserver.observe(el);
        });
        const tables = document.querySelectorAll('.data-table');
        tables.forEach(el => {
            this.visibilityObserver.observe(el);
        });
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(el => {
            this.visibilityObserver.observe(el);
        });
        const locationElements = document.querySelectorAll('.faculty-location, .current-location-display');
        locationElements.forEach(el => {
            this.visibilityObserver.observe(el);
        });
    }
    startPolling() {
        if (!this.isOnline) return;
        switch(window.userRole) {
            case 'program_chair':
            case 'campus_director':
                this.startStatisticsPolling();
                this.startAnnouncementsPolling();
                this.startTablePolling(); // This handles both tables and cards
                break;
            case 'faculty':
                this.startLocationPolling();
                this.startAnnouncementsPolling();
                this.startTablePolling();
                break;
            case 'class':
                this.startLocationPolling();
                this.startAnnouncementsPolling();
                this.startTablePolling();
                break;
        }
    }
    startSchedulePolling() {
        if (this.intervals.schedules) return; // Avoid duplicate intervals
        this.intervals.schedules = setInterval(() => {
            if (this.hasVisibleElement('schedules')) {
                this.fetchScheduleUpdates();
            }
        }, this.defaultIntervals.schedules);
        this.fetchScheduleUpdates();
    }
    hasVisibleElement(pollingType) {
        const targetTypes = pollingType === 'tables' ? ['tables', 'cards'] : [pollingType];
        return Object.values(this.observableElements).some(element => {
            if (!targetTypes.includes(element.polling)) return false;
            if (element.condition) {
                return element.condition();
            }
            const domElement = document.querySelector(element.selector);
            if (domElement) {
                return this.isElementVisible(domElement) || 
                       this.visibleElements.has(domElement.id || domElement.className) ||
                       (domElement.offsetParent !== null && domElement.offsetWidth > 0 && domElement.offsetHeight > 0);
            }
            return false;
        });
    }
    isElementVisible(element) {
        if (typeof element === 'string') {
            if (element.endsWith('-content')) {
                return document.querySelector(`#${element}`)?.classList.contains('active') || false;
            }
            const domElement = document.querySelector(element);
            return domElement ? this.isElementVisible(domElement) : false;
        }
        if (element && element.nodeType === Node.ELEMENT_NODE) {
            const elementId = element.id || element.className;
            return this.visibleElements.has(elementId) || 
                   (element.offsetParent !== null && element.offsetWidth > 0 && element.offsetHeight > 0);
        }
        return false;
    }
    startStatisticsPolling() {
        this.intervals.statistics = setInterval(() => {
            if (this.isElementVisible('header-stat') || this.visibleElements.size > 0) {
                this.fetchTableUpdates();
            }
        }, this.defaultIntervals.statistics);
        this.fetchTableUpdates();
    }
    startTablePolling() {
        this.intervals.tables = setInterval(() => {
            if (this.isElementVisible(`${this.currentTab}-content`)) {
                this.fetchTableUpdates();
            }
        }, this.defaultIntervals.tables);
        this.fetchTableUpdates();
    }
    startLocationPolling() {
        this.intervals.location = setInterval(() => {
            if (this.isElementVisible('faculty-location') || this.isElementVisible('current-location-display')) {
                this.fetchLocationUpdates();
            }
        }, this.defaultIntervals.location);
        this.fetchLocationUpdates();
    }
    startAnnouncementsPolling() {
        this.intervals.announcements = setInterval(() => {
            this.fetchAnnouncementsUpdates();
        }, this.defaultIntervals.announcements);
    }
    async fetchScheduleUpdates() {
        if (!this.isOnline) {
            this.queueUpdate('schedules', { action: 'get_schedule_updates' });
            return;
        }
        try {
            const response = await fetch('assets/php/polling_api.php?action=get_schedule_updates', {
                method: 'GET',
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (data.success) {
                this.updateScheduleDisplay(data);
            }
        } catch (error) {
            this.handlePollingError('schedules', error);
        }
    }
    updateScheduleDisplay(data) {
        if (data.schedules) {
            const scheduleContainers = document.querySelectorAll('.schedule-container, .schedule-section');
            scheduleContainers.forEach(container => {
                if (container.querySelector('.schedule-item')) {
                    this.updateScheduleItems(container, data.schedules);
                }
            });
        }
    }
    updateScheduleItems(container, schedules) {
        const scheduleItems = container.querySelectorAll('.schedule-item');
        scheduleItems.forEach(item => {
            const courseCode = item.querySelector('.course-code')?.textContent;
            const schedule = schedules.find(s => s.course_code === courseCode);
            if (schedule && schedule.status) {
                const statusBadge = item.querySelector('.status-badge');
                if (statusBadge) {
                    statusBadge.className = `status-badge status-${schedule.status}`;
                    statusBadge.textContent = this.getStatusText(schedule.status);
                }
                item.className = `schedule-item ${schedule.status}`;
            }
        });
    }
    getStatusText(status) {
        switch (status) {
            case 'ongoing': return 'In Progress';
            case 'upcoming': return 'Upcoming';
            case 'finished': return 'Completed';
            default: return 'Unknown';
        }
    }
    async fetchStatistics() {
        this.fetchTableUpdates();
    }
    async fetchTableUpdates() {
        if (!this.isOnline) {
            this.queueUpdate('tables', { action: 'fetch_tables', tab: this.currentTab });
            return;
        }
        try {
            let params = new URLSearchParams();
            params.append('action', 'get_dashboard_data');
            if (this.pageType === 'director') {
                params.append('tab', this.currentTab);
            }
            const response = await fetch(`assets/php/polling_api.php?${params}`, {
                method: 'GET',
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (data.success) {
                this.detectAndLogChanges(data);
                if (data.changes) {
                    this.handleDynamicChanges(data.changes);
                }
                if (data.current_entities) {
                    this.handleStatusUpdates(data.current_entities);
                }
                this.updateStatisticsFromTableData(data);
            }
        } catch (error) {
            this.handlePollingError('tables', error);
        }
    }
    detectAndLogChanges(data) {
        const activeTabContent = document.querySelector('.tab-content.active');
        const activeTabId = activeTabContent ? activeTabContent.id.replace('-content', '') : null;
        if (!this.previousData) this.previousData = {};
        let changesDetected = false;
        if (this.pageType === 'director') {
            if (activeTabId === 'faculty' && data.faculty_data) {
                const changes = this.checkTableChanges('#faculty-content .data-table tbody', data.faculty_data, 'faculty');
                if (changes) {
                    this.logChanges('faculty', changes);
                    changesDetected = true;
                }
                this.handleDeletions(this.previousData.faculty_data, data.faculty_data, 'faculty');
                this.previousData.faculty_data = [...data.faculty_data];
            } else if (activeTabId === 'classes' && data.classes_data) {
                const changes = this.checkTableChanges('#classes-content .data-table tbody', data.classes_data, 'classes');
                if (changes) {
                    this.logChanges('classes', changes);
                    changesDetected = true;
                }
                this.handleDeletions(this.previousData.classes_data, data.classes_data, 'classes');
                this.previousData.classes_data = [...data.classes_data];
            } else if (activeTabId === 'courses' && data.courses_data) {
                const changes = this.checkTableChanges('#courses-content .data-table tbody', data.courses_data, 'courses');
                if (changes) {
                    this.logChanges('courses', changes);
                    changesDetected = true;
                }
                this.handleDeletions(this.previousData.courses_data, data.courses_data, 'courses');
                this.previousData.courses_data = [...data.courses_data];
            } else if (activeTabId === 'announcements' && data.announcements_data) {
                const changes = this.checkTableChanges('#announcements-content .data-table tbody', data.announcements_data, 'announcements');
                if (changes) {
                    this.logChanges('announcements', changes);
                    changesDetected = true;
                }
                this.handleDeletions(this.previousData.announcements_data, data.announcements_data, 'announcements');
                this.previousData.announcements_data = [...data.announcements_data];
            }
        } else if (this.pageType === 'program') {
            if (data.faculty_data) {
                const changes = this.checkCardChanges('.faculty-grid', data.faculty_data, 'faculty');
                if (changes && activeTabId === 'faculty') {
                    this.logChanges('faculty', changes);
                    changesDetected = true;
                }
                this.handleDeletions(this.previousData.faculty_data, data.faculty_data, 'faculty');
                this.previousData.faculty_data = [...data.faculty_data];
            }
            if (data.classes_data) {
                const changes = this.checkCardChanges('.classes-grid', data.classes_data, 'classes');
                if (changes && activeTabId === 'classes') {
                    this.logChanges('classes', changes);
                    changesDetected = true;
                }
                this.handleDeletions(this.previousData.classes_data, data.classes_data, 'classes');
                this.previousData.classes_data = [...data.classes_data];
            }
            if (data.courses_data) {
                const changes = this.checkCardChanges('.courses-grid', data.courses_data, 'courses');
                if (changes && activeTabId === 'courses') {
                    this.logChanges('courses', changes);
                    changesDetected = true;
                }
                this.handleDeletions(this.previousData.courses_data, data.courses_data, 'courses');
                this.previousData.courses_data = [...data.courses_data];
            }
        }
    }
    checkTableChanges(containerSelector, newData, type) {
        const container = document.querySelector(containerSelector);
        if (!container || !Array.isArray(newData)) return null;
        const currentRows = (type === 'faculty' || type === 'classes') ? 
            container.querySelectorAll('tr.expandable-row').length : // Faculty/Classes: count main rows only
            container.querySelectorAll('tr:not(.expansion-row)').length; // Others: count non-expansion rows
        const newCount = newData.length;
        if (currentRows !== newCount) {
            const difference = newCount - currentRows;
            console.log(`${type} count change detected:`, {
                currentRows,
                newCount, 
                difference,
                containerSelector
            });
            if (difference > 0) {
                const currentIds = Array.from(container.querySelectorAll('tr')).map(row => {
                    const idField = this.getIdField(type).replace('_', '-');
                    return row.getAttribute(`data-${idField}`);
                }).filter(id => id);
                console.log(`${type} ID check:`, {
                    currentIds: currentIds,
                    newDataIds: newData.map(item => item[this.getIdField(type)]),
                    idField: this.getIdField(type)
                });
                newData.forEach(item => {
                    const itemId = item[this.getIdField(type)];
                    const willAdd = !currentIds.includes(itemId.toString());
                    if (willAdd) {
                        this.addToTable(type, item);
                    }
                });
            } else if (difference < 0) {
                this.reloadTableData(containerSelector, newData, type);
            }
            return {
                type: 'count_change',
                oldCount: currentRows,
                newCount: newCount,
                difference: difference
            };
        }
        return null;
    }
    checkCardChanges(containerSelector, newData, type) {
        const container = document.querySelector(containerSelector);
        if (!container || !Array.isArray(newData)) return null;
        const currentCards = container.querySelectorAll('.faculty-card:not(.add-card), .class-card:not(.add-card), .course-card:not(.add-card)').length;
        const newCount = newData.length;
        if (currentCards !== newCount) {
            const difference = newCount - currentCards;
            if (difference > 0) {
                this.reloadCardData(containerSelector, newData, type);
            } else if (difference < 0) {
                this.reloadCardData(containerSelector, newData, type);
            }
            return {
                type: 'count_change',
                oldCount: currentCards,
                newCount: newCount,
                difference: difference
            };
        }
        return null;
    }
    logCurrentStatus() {
        const pageName = document.title.split(' - ')[1] || 'Dashboard';
        console.log(`${this.currentTab}: Polling active`);
    }
    logChanges(type, changeInfo) {
        const pageName = document.title.split(' - ')[1] || 'Dashboard';
        this.logCurrentStatus();
        if (changeInfo.difference > 0) {
            const itemType = this.pageType === 'director' ? 'entr' : 'card';
        } else if (changeInfo.difference < 0) {
            const itemType = this.pageType === 'director' ? 'entr' : 'card';
        }
    }
    getLastUpdateTimestamp(tab) {
        return localStorage.getItem(`last_update_${tab}_${window.userRole}`);
    }
    setLastUpdateTimestamp(tab, timestamp) {
        localStorage.setItem(`last_update_${tab}_${window.userRole}`, timestamp);
    }
    hasDataChanged(row, update, tableType) {
        switch(tableType) {
            case 'faculty':
                const currentName = row.querySelector('.name-column')?.textContent.trim();
                const currentLocation = row.querySelector('.location-column')?.textContent.trim();
                return (update.full_name && update.full_name !== currentName) ||
                       (update.location && update.location !== currentLocation);
            case 'classes':
                const currentClassName = row.querySelector('.name-column')?.textContent.trim();
                return update.class_name && update.class_name !== currentClassName;
            case 'announcements':
                const currentTitle = row.querySelector('.name-column')?.textContent.trim();
                const currentPriority = row.querySelector('.status-column .status-badge')?.textContent.trim();
                return (update.title && update.title !== currentTitle) ||
                       (update.priority && update.priority.toUpperCase() !== currentPriority);
            default:
                return true;
        }
    }
    async fetchLocationUpdates() {
        if (!this.isOnline) {
            this.queueUpdate('location', { action: 'get_location_updates' });
            return;
        }
        try {
            const response = await fetch('assets/php/polling_api.php?action=get_location_updates', {
                method: 'GET',
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (data.success) {
                this.updateLocationDisplay(data);
            }
        } catch (error) {
            this.handlePollingError('location', error);
        }
    }
    async fetchAnnouncementsUpdates() {
        if (!this.isOnline) {
            this.queueUpdate('announcements', { action: 'fetch_announcements' });
            return;
        }
        try {
            const response = await fetch('assets/php/get_announcements.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (data.success) {
                this.updateAnnouncementsDisplay(data);
            }
        } catch (error) {
            this.handlePollingError('announcements', error);
        }
    }
    handleDynamicChanges(changes) {
    }
    handleDeletions(oldData, newData, entityType) {
        if (!oldData || !newData) return;
        const oldCount = Array.isArray(oldData) ? oldData.length : 0;
        const newCount = Array.isArray(newData) ? newData.length : 0;
        if (oldCount > newCount) {
            const deletedCount = oldCount - newCount;
            const oldIds = oldData.map(item => item[this.getIdField(entityType)]);
            const newIds = newData.map(item => item[this.getIdField(entityType)]);
            const deletedIds = oldIds.filter(id => !newIds.includes(id));
            deletedIds.forEach(id => {
                this.removeEntityFromUI(entityType, id);
            });
        }
    }
    getIdField(entityType) {
        const mapping = {
            'faculty': 'faculty_id',
            'courses': 'course_id',
            'classes': 'class_id',
            'announcements': 'announcement_id'
        };
        return mapping[entityType] || 'id';
    }
    removeEntityFromUI(entityType, entityId) {
        if (this.pageType === 'director') {
            const idField = this.getIdField(entityType);
            const row = document.querySelector(`tr[data-${idField.replace('_', '-')}="${entityId}"]`);
            if (row) {
                const nextRow = row.nextElementSibling;
                if (nextRow && nextRow.classList.contains('expansion-row')) {
                    nextRow.style.transition = 'opacity 0.3s ease-out';
                    nextRow.style.opacity = '0';
                    setTimeout(() => nextRow.remove(), 300);
                }
                row.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(() => row.remove(), 300);
            }
        } else if (this.pageType === 'program') {
            const cardSelectors = [
                `.faculty-card[data-faculty-id="${entityId}"]`,
                `.course-card[data-course-id="${entityId}"]`,
                `.class-card[data-class-id="${entityId}"]`
            ];
            cardSelectors.forEach(selector => {
                const card = document.querySelector(selector);
                if (card) {
                    card.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(() => card.remove(), 300);
                }
            });
        }
    }
    handleStatusUpdates(currentEntities) {
        if (!this.initialized) {
            this.initialized = true;
        }
    }
    updateEntityStatus(entityType, entityData) {
        if (this.pageType === 'director') {
            const idField = entityType.replace('s', '') + '_id';
            const entityId = entityData[idField];
            const row = document.querySelector(`tr[data-${entityType.replace('s', '')}-id="${entityId}"]`);
            if (row) {
                this.updateTableRowStatus(row, entityData, entityType);
            }
        } else if (this.pageType === 'program' && entityType === 'faculty') {
            const existingCard = document.querySelector(`.faculty-card[data-name*="${entityData.full_name}"]`);
            if (existingCard) {
                const oldStatus = existingCard.querySelector('.location-text')?.textContent?.trim();
                const newStatus = entityData.status || 'Offline';
                const lastKnownStatus = this.lastStatusCheck[entityData.full_name];
                if (lastKnownStatus && lastKnownStatus !== newStatus) {
                    const action = newStatus === 'Available' ? '🟢 logged in' : '🔴 logged out';
                }
                this.lastStatusCheck[entityData.full_name] = newStatus;
                this.updateCardStatus(existingCard, entityData, entityType);
            }
        }
    }
    updateTableRowStatus(row, entityData, entityType) {
        if (entityType === 'faculty') {
            const statusBadge = row.querySelector('.status-badge');
            const locationCell = row.querySelector('.location-column');
            if (statusBadge) {
                const status = entityData.status || 'Offline';
                statusBadge.className = `status-badge status-${status.toLowerCase()}`;
                statusBadge.textContent = status;
            }
            if (locationCell) {
                locationCell.textContent = entityData.current_location || 'Not Available';
            }
        }
    }
    updateCardStatus(card, entityData, entityType) {
        if (entityType === 'faculty') {
            const statusDot = card.querySelector('.status-dot');
            const locationText = card.querySelector('.location-text');
            const locationDiv = card.querySelector('.location-info div:nth-child(2)');
            if (statusDot && locationText) {
                const status = entityData.status || 'Offline';
                const statusClass = status.toLowerCase() === 'available' ? 'available' : 'offline';
                statusDot.className = `status-dot status-${statusClass}`;
                locationText.textContent = status;
            }
            if (locationDiv && entityData.current_location) {
                locationDiv.textContent = entityData.current_location;
            }
            const timeInfo = card.querySelector('.time-info');
            if (timeInfo) {
                timeInfo.textContent = 'Last updated: 0 minutes ago';
            }
        }
    }
    isRecentlyCreated(entity) {
        if (!entity.created_at) return false;
        const createdTime = new Date(entity.created_at);
        const currentTime = new Date();
        const timeDifference = (currentTime - createdTime) / 1000; // in seconds
        return timeDifference <= 5;
    }
    entityExistsInUI(entityType, entity) {
        const idField = entityType.replace('s', '') + '_id';
        const entityId = entity[idField];
        if (this.pageType === 'director') {
            return document.querySelector(`tr[data-${entityType.replace('s', '')}-id="${entityId}"]`) !== null;
        } else if (this.pageType === 'program') {
            const existingCard = document.querySelector(`.faculty-card[data-name*="${entity.full_name}"]`);
            const newCard = document.querySelector(`.faculty-card[data-faculty-id="${entityId}"]`);
            return existingCard !== null || newCard !== null;
        }
        return false;
    }
    addEntityToUI(entityType, entityData) {
        if (this.pageType === 'director') {
            this.addToTable(entityType, entityData);
        } else if (this.pageType === 'program') {
            this.addToCards(entityType, entityData);
        }
        this.updateCounts(entityType, 1);
    }
    addToTable(entityType, entityData) {
        if (this.pageType !== 'director') return;
        const tableBody = document.querySelector(`#${entityType}-content .data-table tbody`);
        if (!tableBody) return;
        const selector = `[data-${entityType.replace('s', '')}-id="${entityData[entityType.replace('s', '') + '_id']}"]`;
        const existingRow = tableBody.querySelector(selector);
        if (existingRow) {
            return; // Don't add if already exists
        }
        let newRow = '';
        switch(entityType) {
            case 'courses':
                newRow = this.createCourseRow(entityData);
                break;
            case 'faculty':
                newRow = this.createFacultyRow(entityData);
                break;
            case 'classes':
                newRow = this.createClassRow(entityData);
                break;
            case 'announcements':
                newRow = this.createAnnouncementRow(entityData);
                break;
        }
        if (newRow) {
            let insertedRow;
            if (entityType === 'faculty' || entityType === 'classes') {
                const tempTable = document.createElement('table');
                const tempTbody = document.createElement('tbody');
                tempTbody.innerHTML = newRow;
                tempTable.appendChild(tempTbody);
                const rows = tempTbody.querySelectorAll('tr');
                const firstExistingRow = tableBody.firstElementChild;
                rows.forEach((row, index) => {
                    const clonedRow = row.cloneNode(true);
                    if (firstExistingRow) {
                        tableBody.insertBefore(clonedRow, firstExistingRow);
                    } else {
                        tableBody.appendChild(clonedRow);
                    }
                });
                insertedRow = tableBody.firstElementChild;
            } else {
                tableBody.insertAdjacentHTML('afterbegin', newRow);
                insertedRow = tableBody.firstElementChild;
            }
            insertedRow.style.opacity = '0';
            insertedRow.style.transform = 'translateY(-20px)';
            insertedRow.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
            setTimeout(() => {
                insertedRow.style.opacity = '1';
                insertedRow.style.transform = 'translateY(0)';
            }, 100);
        }
    }
    addToCards(entityType, entityData) {
        if (this.pageType !== 'program') return;
        const cardContainer = document.querySelector(`.${entityType}-grid, .${entityType}-cards`);
        if (!cardContainer) return;
        const idField = entityType.replace('s', '') + '_id';
        const existingCard = cardContainer.querySelector(`[data-${entityType.replace('s', '')}-id="${entityData[idField]}"]`);
        if (existingCard) return; // Don't add if already exists
        let newCard = '';
        switch(entityType) {
            case 'faculty':
                newCard = this.createFacultyCard(entityData);
                break;
            case 'classes':
                newCard = this.createClassCard(entityData);
                break;
            case 'courses':
                newCard = this.createCourseCard(entityData);
                break;
        }
        if (newCard) {
            cardContainer.insertAdjacentHTML('afterbegin', newCard);
            const insertedCard = cardContainer.firstElementChild;
            insertedCard.style.opacity = '0';
            insertedCard.style.transform = 'scale(0.9)';
            insertedCard.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
            setTimeout(() => {
                insertedCard.style.opacity = '1';
                insertedCard.style.transform = 'scale(1)';
            }, 100);
        }
    }
    updateCounts(entityType, delta) {
        const statLabels = {
            'faculty': 'Faculty',
            'classes': 'Classes', 
            'courses': 'Courses',
            'announcements': 'Announcements'
        };
        const label = statLabels[entityType];
        if (label) {
            const statElements = document.querySelectorAll('.header-stat-label');
            statElements.forEach(statElement => {
                if (statElement.textContent.trim() === label) {
                    const numberElement = statElement.parentElement.querySelector('.header-stat-number');
                    if (numberElement) {
                        const currentValue = parseInt(numberElement.textContent) || 0;
                        const newValue = Math.max(0, currentValue + delta);
                        this.animateValueChange(numberElement, currentValue, newValue);
                    }
                }
            });
        }
    }
    createCourseRow(course) {
        return `
            <tr>
                <td class="id-column">${escapeHtml(course.course_code)}</td>
                <td class="description-column">${escapeHtml(course.course_description)}</td>
                <td class="id-column">${course.units}</td>
                <td class="id-column">${course.times_scheduled || 0}</td>
                <td class="actions-column">
                    <button class="delete-btn" onclick="deleteEntity('delete_course', ${course.course_id})">Delete</button>
                </td>
            </tr>
        `;
    }
    createFacultyRow(faculty) {
        const status = faculty.status || 'Offline';
        return `
            <tr class="expandable-row" onclick="toggleRowExpansion(this)" data-faculty-id="${faculty.faculty_id}">
                <td class="name-column">${escapeHtml(faculty.full_name)}</td>
                <td class="status-column">
                    <span class="status-badge status-${status.toLowerCase()}">${status}</span>
                </td>
                <td class="location-column">${escapeHtml(faculty.current_location || 'Not Available')}</td>
                <td class="actions-column">
                    <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_faculty', ${faculty.faculty_id})">Delete</button>
                </td>
            </tr>
            <tr class="expansion-row" id="faculty-expansion-${faculty.faculty_id}" style="display: none;">
                <td colspan="4" class="expansion-content">
                    <div class="expanded-details">
                        <div class="detail-item">
                            <span class="detail-label">Employee ID:</span>
                            <span class="detail-value">${escapeHtml(faculty.employee_id)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Program:</span>
                            <span class="detail-value">${escapeHtml(faculty.program)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Contact Email:</span>
                            <span class="detail-value">${escapeHtml(faculty.contact_email || 'N/A')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value">${escapeHtml(faculty.contact_phone || 'N/A')}</span>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }
    createClassRow(classData) {
        return `
            <tr class="expandable-row" onclick="toggleRowExpansion(this)" data-class-id="${classData.class_id}">
                <td class="id-column">${escapeHtml(classData.class_code)}</td>
                <td class="name-column">${escapeHtml(classData.class_name)}</td>
                <td class="id-column">${classData.year_level}</td>
                <td class="date-column">${escapeHtml(classData.academic_year)}</td>
                <td class="actions-column">
                    <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_class', ${classData.class_id})">Delete</button>
                </td>
            </tr>
            <tr class="expansion-row" id="class-expansion-${classData.class_id}" style="display: none;">
                <td colspan="5" class="expansion-content">
                    <div class="expanded-details">
                        <div class="detail-item">
                            <span class="detail-label">Semester:</span>
                            <span class="detail-value">${escapeHtml(classData.semester)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Program Chair:</span>
                            <span class="detail-value">${escapeHtml(classData.program_chair_name || 'Unassigned')}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Subjects:</span>
                            <span class="detail-value">${classData.total_subjects || 0}</span>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }
    createAnnouncementRow(announcement) {
        return `
            <tr class="expandable-row" onclick="toggleRowExpansion(this)" data-announcement-id="${announcement.announcement_id}">
                <td class="name-column">${escapeHtml(announcement.title)}</td>
                <td class="status-column">
                    <span class="status-badge priority-${announcement.priority}">${announcement.priority.toUpperCase()}</span>
                </td>
                <td class="program-column">${escapeHtml(announcement.target_audience)}</td>
                <td class="actions-column">
                    <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_announcement', ${announcement.announcement_id})">Delete</button>
                </td>
            </tr>
            <tr class="expansion-row" id="announcement-expansion-${announcement.announcement_id}" style="display: none;">
                <td colspan="4" class="expansion-content">
                    <div class="expanded-details">
                        <div class="detail-item">
                            <span class="detail-label">Content:</span>
                            <span class="detail-value">${escapeHtml(announcement.content)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Created By:</span>
                            <span class="detail-value">${escapeHtml(announcement.created_by_name)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Created Date:</span>
                            <span class="detail-value">${new Date(announcement.created_at).toLocaleDateString('en-US', {
                                month: 'short',
                                day: 'numeric', 
                                year: 'numeric',
                                hour: 'numeric',
                                minute: '2-digit'
                            })}</span>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }
    createFacultyCard(faculty) {
        const isProgram = this.pageType === 'program';
        const status = faculty.status || 'Offline';
        const statusClass = status.toLowerCase() === 'available' ? 'available' : 'offline';
        const nameParts = (faculty.full_name || '').split(' ');
        const initials = nameParts.map(part => part.charAt(0)).join('').substring(0, 2);
        return `
            <div class="faculty-card" data-name="${escapeHtml(faculty.full_name)}" ${!isProgram ? `data-faculty-id="${faculty.faculty_id}"` : ''}>
                <div class="faculty-avatar">${initials}</div>
                <div class="faculty-name">${escapeHtml(faculty.full_name)}</div>   
                <div class="location-info">
                    <div class="location-status">
                        <span class="status-dot status-${statusClass}"></span>
                        <span class="location-text">${status}</span>
                    </div>
                    <div style="margin-left: 14px; color: #333; font-weight: 500; font-size: 0.85rem;">
                        ${escapeHtml(faculty.current_location || 'Not Available')}
                    </div>
                    <div class="time-info">Last updated: 0 minutes ago</div>
                </div>
                <div class="contact-info">
                    <div class="office-hours">
                        Office Hours:<br>${escapeHtml(faculty.office_hours || 'Not specified')}
                    </div>
                    <div class="faculty-actions">
                        ${faculty.contact_email ? `<button class="action-btn primary" onclick="contactFaculty('${faculty.contact_email}')">Email</button>` : ''}
                        ${faculty.contact_phone ? `<button class="action-btn" onclick="callFaculty('${faculty.contact_phone}')">Call</button>` : ''}
                        <button class="action-btn" onclick="viewSchedule(${faculty.faculty_id})">Schedule</button>
                        <button class="action-btn" onclick="viewCourseLoad(${faculty.faculty_id})">Course Load</button>
                        ${!isProgram ? `<button class="action-btn danger" onclick="deleteEntity('delete_faculty', ${faculty.faculty_id})">Delete</button>` : ''}
                    </div>
                </div>
            </div>
        `;
    }
    createClassCard(classData) {
        return `
            <div class="class-card" data-name="${escapeHtml(classData.class_name)}" data-code="${escapeHtml(classData.class_code)}">
                <div class="class-card-content">
                    <div class="class-card-default-content">
                        <div class="class-header">
                            <div class="class-info">
                                <div class="class-name">${escapeHtml(classData.class_name)}</div>
                                <div class="class-code">${escapeHtml(classData.class_code)}</div>
                                <div class="class-meta">
                                    Year ${classData.year_level} • ${escapeHtml(classData.semester)} Semester • ${escapeHtml(classData.academic_year)}
                                </div>
                            </div>
                        </div>
                        <div class="class-stats">
                            <div class="class-stat">
                                <div class="class-stat-number">${classData.total_subjects || 0}</div>
                                <div class="class-stat-label">Subjects</div>
                            </div>
                            <div class="class-stat">
                                <div class="class-stat-number">${classData.assigned_faculty || 0}</div>
                                <div class="class-stat-label">Faculty</div>
                            </div>
                        </div>
                    </div>
                    <div class="class-details-overlay">
                        <div class="overlay-header">
                            <h4>Schedule</h4>
                        </div>
                        <div class="overlay-body">
                            <div class="no-data" style="padding: 20px; text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">
                                    <svg class="feather feather-xl"><use href="#calendar"></use></svg>
                                </div>
                                <p style="color: #666; margin: 0;">No schedules assigned yet</p>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="class-details-toggle" onclick="toggleClassDetailsOverlay(this)">
                    View Schedule Details
                    <span class="arrow">▼</span>
                </button>
            </div>
        `;
    }
    createEntity(entityData, entityType, viewType = 'auto') {
        const actualViewType = viewType === 'auto' ? this.pageType : viewType;
        switch(actualViewType) {
            case 'director':
                return this.createTableRow(entityData, entityType);
            case 'program':
                return this.createCard(entityData, entityType);
            default:
                return null;
        }
    }
    renderEntities(containerSelector, entitiesData, entityType, viewType = 'auto') {
        const container = document.querySelector(containerSelector);
        if (!container) return;
        const actualViewType = viewType === 'auto' ? this.pageType : viewType;
        if (actualViewType === 'director') {
            const tbody = container.querySelector('tbody') || container;
            tbody.querySelectorAll('tr:not(.expansion-row)').forEach(row => {
                const nextRow = row.nextElementSibling;
                if (nextRow && nextRow.classList.contains('expansion-row')) {
                    nextRow.remove();
                }
                row.remove();
            });
            entitiesData.forEach(entity => {
                const entityHTML = this.createEntity(entity, entityType, 'director');
                if (entityHTML) {
                    tbody.insertAdjacentHTML('beforeend', entityHTML);
                }
            });
        } else if (actualViewType === 'program') {
            container.querySelectorAll('.faculty-card:not(.add-card), .class-card:not(.add-card), .course-card:not(.add-card), .announcement-card:not(.add-card)').forEach(card => card.remove());
            entitiesData.forEach(entity => {
                const entityHTML = this.createEntity(entity, entityType, 'program');
                if (entityHTML) {
                    const addCard = container.querySelector('.add-card');
                    if (addCard) {
                        addCard.insertAdjacentHTML('beforebegin', entityHTML);
                    } else {
                        container.insertAdjacentHTML('beforeend', entityHTML);
                    }
                }
            });
        }
    }
    updateStatisticsFromTableData(data) {
        const stats = {};
        if (data.faculty_data) {
            stats.total_faculty = data.faculty_data.length;
            stats.available_faculty = data.faculty_data.filter(f => f.status === 'Available').length;
        }
        if (data.classes_data) {
            stats.total_classes = data.classes_data.length;
        }
        if (data.courses_data) {
            stats.total_courses = data.courses_data.length;
        }
        if (data.announcements_data) {
            stats.active_announcements = data.announcements_data.length;
        }
        this.updateStatisticsDisplay(stats);
    }
    reloadTableData(containerSelector, newData, type) {
        this.renderEntities(containerSelector, newData, type, 'director');
    }
    reloadCardData(containerSelector, newData, type) {
        this.renderEntities(containerSelector, newData, type, 'program');
    }
    refreshExistingContent(containerSelector, newData, type) {
        const container = document.querySelector(containerSelector);
        if (!container) return;
    }
    createTableRow(item, type) {
        switch(type) {
            case 'faculty':
                return this.createFacultyRow(item);
            case 'courses':
                return this.createCourseRow(item);
            case 'classes':
                return this.createClassRow(item);
            case 'announcements':
                return this.createAnnouncementRow(item);
            default:
                return null;
        }
    }
    createCard(item, type) {
        switch(type) {
            case 'faculty':
                return this.createFacultyCard(item);
            case 'courses':
                return this.createCourseCard(item);
            case 'classes':
                return this.createClassCard(item);
            case 'announcements':
                return this.createAnnouncementCard(item);
            default:
                return null;
        }
    }
    createCourseCard(course) {
        const units = parseFloat(course.units) || 0;
        const unitsDisplay = units % 1 === 0 ? `${units}.00` : units.toString();
        return `
            <div class="course-card" data-course="${course.course_code}" data-course-id="${course.course_id}" style="display: block;">
                <div class="course-card-content">
                    <div class="course-card-default-content">
                        <div class="course-header">
                            <div class="course-code">${escapeHtml(course.course_code)}</div>
                            <div class="course-units">${unitsDisplay} unit${units > 1 ? 's' : ''}</div>
                        </div>
                        <div class="course-description">
                            ${escapeHtml(course.course_description)}
                        </div>
                        <div class="course-actions">
                            <button class="action-btn primary" onclick="assignCourseToYearLevel('${course.course_code}')">
                                Assign to Year Level
                            </button>
                            <button class="action-btn danger" onclick="deleteCourse('${course.course_code}')">
                                Delete
                            </button>
                        </div>
                    </div>
                    <div class="course-details-overlay">
                        <div class="overlay-header">
                            <h4>Current Assignments</h4>
                        </div>
                        <div class="overlay-body">
                            <div class="assignments-preview" style="padding: 12px;">
                                <div class="loading-assignments">Loading assignments...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="course-details-toggle" onclick="toggleCourseDetailsOverlay(this, '${course.course_code}')">
                    View Assignments
                    <span class="arrow">▼</span>
                </button>
            </div>
        `;
    }
    updateStatisticsDisplay(stats) {
        const statElements = document.querySelectorAll('.header-stat');
        const statMappings = {
            'total_faculty': 'Faculty',
            'total_classes': 'Classes', 
            'total_courses': 'Courses',
            'active_announcements': 'Announcements',
            'available_faculty': 'Online'
        };
        statElements.forEach(element => {
            const label = element.querySelector('.header-stat-label');
            if (label) {
                const labelText = label.textContent.trim();
                const statKey = Object.keys(statMappings).find(key => 
                    statMappings[key] === labelText
                );
                if (statKey && stats[statKey] !== undefined) {
                    const numberElement = element.querySelector('.header-stat-number');
                    if (numberElement) {
                        const currentValue = parseInt(numberElement.textContent);
                        const newValue = parseInt(stats[statKey]);
                        if (currentValue !== newValue) {
                            const pageName = document.title.split(' - ')[1] || 'Dashboard';
                            this.logCurrentStatus();
                            this.animateValueChange(numberElement, currentValue, newValue);
                        }
                    }
                }
            }
        });
    }
    updateLocationDisplay(data) {
        if (window.userRole === 'class') {
            const facultyData = data.faculty || [];
            facultyData.forEach(faculty => {
                const facultyElements = document.querySelectorAll(`[data-faculty-id="${faculty.faculty_id}"]`);
                facultyElements.forEach(element => {
                    const locationElement = element.querySelector('.faculty-location');
                    const statusElement = element.querySelector('.faculty-status');
                    const lastUpdatedElement = element.querySelector('.last-updated');
                    if (locationElement) {
                        locationElement.textContent = faculty.current_location || 'Location not set';
                    }
                    if (statusElement) {
                        statusElement.className = `faculty-status status-${faculty.status}`;
                        statusElement.textContent = faculty.status.charAt(0).toUpperCase() + faculty.status.slice(1);
                    }
                    if (lastUpdatedElement) {
                        lastUpdatedElement.textContent = faculty.last_updated;
                    }
                });
            });
        } else if (window.userRole === 'faculty') {
            this.updateFacultyOwnLocation(data);
        }
    }
    updateFacultyOwnLocation(data) {
        const currentLocationElement = document.querySelector('.current-location-display');
        const lastUpdatedElement = document.querySelector('.location-last-updated');
        if (data.current_location && currentLocationElement) {
            currentLocationElement.textContent = data.current_location;
        }
        if (data.last_updated && lastUpdatedElement) {
            lastUpdatedElement.textContent = data.last_updated;
        }
    }
    updateAnnouncementsDisplay(data) {
        const announcementsContainer = document.getElementById('announcementsContainer');
        const announcementBadge = document.querySelector('.announcement-badge');
        if (data.count !== undefined && announcementBadge) {
            if (data.count > 0) {
                announcementBadge.textContent = data.count;
                announcementBadge.style.display = 'inline';
            } else {
                announcementBadge.style.display = 'none';
            }
        }
        if (data.announcements && announcementsContainer) {
            const currentCount = announcementsContainer.children.length;
            if (data.announcements.length !== currentCount) {
                const diff = data.announcements.length - currentCount;
                if (diff > 0) {
                }
            }
        }
    }
    animateValueChange(element, fromValue, toValue) {
        element.style.transform = 'scale(1.1)';
        element.style.color = '#4CAF50';
        setTimeout(() => {
            element.textContent = toValue;
            element.style.transform = 'scale(1)';
            setTimeout(() => {
                element.style.color = '';
            }, 300);
        }, 150);
    }
    pausePolling() {
        this.isActive = false;
        Object.keys(this.intervals).forEach(key => {
            if (this.intervals[key]) {
                clearInterval(this.intervals[key]);
            }
        });
    }
    resumePolling() {
        if (!this.isActive) {
            this.isActive = true;
            this.startPolling();
        }
    }
    stopAllPolling() {
        this.pausePolling();
        this.stopHeartbeat();
    }
    logCurrentStatus() {
        const elements = [];
        Object.keys(this.observableElements).forEach(key => {
            const element = this.observableElements[key];
            let isVisible = false;
            if (element.condition) {
                isVisible = element.condition();
            } else {
                const domElement = document.querySelector(element.selector);
                if (domElement) {
                    isVisible = this.isElementVisible(domElement) || 
                               this.visibleElements.has(domElement.id || domElement.className) ||
                               (domElement.offsetParent !== null && domElement.offsetWidth > 0 && domElement.offsetHeight > 0);
                }
            }
            elements.push(`${element.description}: ${isVisible}`);
        });
        elements.forEach(element => console.log(element));
    }
    startHeartbeat() {
        this.heartbeatInterval = setInterval(() => {
            if (this.isOnline && !document.hidden) {
                this.sendHeartbeat();
            }
        }, 120000); // 2 minutes
        this.sendHeartbeat();
    }
    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
    }
    async sendHeartbeat() {
        try {
            const response = await fetch('assets/php/session_heartbeat.php', {
                method: 'POST',
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (!data.success) {
            }
        } catch (error) {
        }
    }
    updateTableDisplay(data) {
        if (!this.isElementVisible(`${this.currentTab}-content`)) {
            return;
        }
        const currentTable = document.querySelector(`#${this.currentTab}-content .data-table tbody`);
        if (!currentTable) {
            return;
        }
        let actualChanges = 0;
        data.updates?.forEach(update => {
            let row = null;
            if (this.currentTab === 'faculty') {
                row = currentTable.querySelector(`tr[data-faculty-id="${update.id}"]`);
            } else if (this.currentTab === 'classes') {
                row = currentTable.querySelector(`tr[data-class-id="${update.id}"]`);
            } else if (this.currentTab === 'announcements') {
                row = currentTable.querySelector(`tr[data-announcement-id="${update.id}"]`);
            }
            if (row && row.classList.contains('expandable-row')) {
                const hasChange = this.hasDataChanged(row, update, this.currentTab);
                if (hasChange) {
                    actualChanges++;
                    this.updateTableRow(row, update, this.currentTab);
                }
            } else if (update.action === 'add') {
                actualChanges++;
                this.addTableRow(currentTable, update, this.currentTab);
            }
        });
        if (actualChanges > 0) {
            const pageName = document.title.split(' - ')[1] || 'Dashboard';
            this.logCurrentStatus();
        }
        if (data.total_count !== undefined) {
            this.updateRowCount(this.currentTab, data.total_count);
        }
    }
    updateTableRow(row, update, tableType) {
        switch(tableType) {
            case 'faculty':
                const nameCell = row.querySelector('.name-column');
                const statusCell = row.querySelector('.status-column .status-badge');
                const locationCell = row.querySelector('.location-column');
                if (update.full_name && nameCell) {
                    nameCell.textContent = update.full_name;
                }
                if (update.status && statusCell) {
                    statusCell.className = `status-badge status-${update.status.toLowerCase()}`;
                    statusCell.textContent = update.status.charAt(0).toUpperCase() + update.status.slice(1);
                }
                if (update.location && locationCell) {
                    locationCell.textContent = update.location || 'Not Available';
                }
                const expansionRow = row.nextElementSibling;
                if (expansionRow && expansionRow.classList.contains('expansion-row')) {
                    const detailItems = expansionRow.querySelectorAll('.detail-value');
                    if (detailItems.length >= 3) {
                        if (update.employee_id) detailItems[0].textContent = update.employee_id;
                        if (update.program) detailItems[1].textContent = update.program;
                        if (update.contact_email) detailItems[2].textContent = update.contact_email || 'N/A';
                    }
                }
                break;
            case 'classes':
                const classNameCell = row.querySelector('.name-column');
                if (update.class_name && classNameCell) {
                    classNameCell.textContent = update.class_name;
                }
                const classExpansionRow = row.nextElementSibling;
                if (classExpansionRow && classExpansionRow.classList.contains('expansion-row')) {
                    const classDetailItems = classExpansionRow.querySelectorAll('.detail-value');
                    if (classDetailItems.length >= 3) {
                        if (update.semester) classDetailItems[0].textContent = update.semester;
                        if (update.program_chair_name) classDetailItems[1].textContent = update.program_chair_name;
                        if (update.total_subjects !== undefined) classDetailItems[2].textContent = update.total_subjects;
                    }
                }
                break;
            case 'announcements':
                const titleCell = row.querySelector('.name-column');
                const priorityCell = row.querySelector('.status-column .status-badge');
                if (update.title && titleCell) {
                    titleCell.textContent = update.title;
                }
                if (update.priority && priorityCell) {
                    priorityCell.className = `status-badge priority-${update.priority}`;
                    priorityCell.textContent = update.priority.toUpperCase();
                }
                const announcementExpansionRow = row.nextElementSibling;
                if (announcementExpansionRow && announcementExpansionRow.classList.contains('expansion-row')) {
                    const announcementDetailItems = announcementExpansionRow.querySelectorAll('.detail-value');
                    if (announcementDetailItems.length >= 3) {
                        if (update.content) announcementDetailItems[0].textContent = update.content;
                        if (update.created_by_name) announcementDetailItems[1].textContent = update.created_by_name;
                        if (update.created_at) {
                            const date = new Date(update.created_at);
                            announcementDetailItems[2].textContent = date.toLocaleDateString('en-US', { 
                                year: 'numeric', month: 'short', day: 'numeric',
                                hour: 'numeric', minute: '2-digit', hour12: true 
                            });
                        }
                    }
                }
                break;
        }
        const hasActualChange = this.hasDataChanged(row, update, tableType);
        if (hasActualChange) {
            row.style.background = 'rgba(76, 175, 80, 0.1)';
            setTimeout(() => {
                row.style.background = '';
            }, 2000);
        }
    }
    addTableRow(tableBody, data, tableType) {
        let newRowHTML = '';
        let expansionRowHTML = '';
        switch(tableType) {
            case 'faculty':
                newRowHTML = `
                    <tr class="expandable-row" onclick="toggleRowExpansion(this)" data-faculty-id="${data.id}">
                        <td class="name-column">${data.full_name || ''}</td>
                        <td class="status-column">
                            <span class="status-badge status-${(data.status || 'offline').toLowerCase()}">
                                ${(data.status || 'Offline').charAt(0).toUpperCase() + (data.status || 'offline').slice(1)}
                            </span>
                        </td>
                        <td class="location-column">${data.location || 'Not Available'}</td>
                        <td class="actions-column">
                            <button class="delete-btn" onclick="event.stopPropagation(); deleteEntity('delete_faculty', ${data.id})">Delete</button>
                        </td>
                    </tr>
                `;
                expansionRowHTML = `
                    <tr class="expansion-row" id="faculty-expansion-${data.id}" style="display: none;">
                        <td colspan="4" class="expansion-content">
                            <div class="expanded-details">
                                <div class="detail-item">
                                    <span class="detail-label">Employee ID:</span>
                                    <span class="detail-value">${data.employee_id || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Program:</span>
                                    <span class="detail-value">${data.program || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Contact Email:</span>
                                    <span class="detail-value">${data.contact_email || 'N/A'}</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
                break;
        }
        if (newRowHTML) {
            tableBody.insertAdjacentHTML('beforeend', newRowHTML + expansionRowHTML);
        }
    }
    updateRowCount(tableType, count) {
        const countElement = document.querySelector(`#${tableType}-content .table-count`);
        if (countElement) {
            countElement.textContent = count;
        }
    }
    queueUpdate(type, data) {
        this.updateQueue.push({ type, data, timestamp: Date.now() });
        if (this.updateQueue.length > 50) {
            this.updateQueue = this.updateQueue.slice(-50);
        }
    }
    processUpdateQueue() {
        if (!this.isOnline || this.updateQueue.length === 0) return;
        const updates = [...this.updateQueue];
        this.updateQueue = [];
        updates.forEach(update => {
            switch(update.type) {
                case 'statistics':
                    this.fetchStatistics();
                    break;
                case 'location':
                    this.fetchLocationUpdates();
                    break;
                case 'announcements':
                    this.fetchAnnouncementsUpdates();
                    break;
                case 'tables':
                    this.fetchTableUpdates();
                    break;
            }
        });
    }
    handlePollingError(type, error) {
        const currentInterval = this.defaultIntervals[type];
        const newInterval = Math.min(currentInterval * 2, 30000); // Max 30 seconds
        setTimeout(() => {
            this.defaultIntervals[type] = 3000;
        }, 300000);
        this.updateInterval(type, newInterval);
    }
    updateInterval(type, newInterval) {
        if (this.intervals[type]) {
            clearInterval(this.intervals[type]);
        }
        this.defaultIntervals[type] = newInterval;
        if (this.isActive) {
            switch(type) {
                case 'statistics':
                    this.startStatisticsPolling();
                    break;
                case 'location':
                    this.startLocationPolling();
                    break;
                case 'announcements':
                    this.startAnnouncementsPolling();
                    break;
                case 'tables':
                    this.startTablePolling();
                    break;
            }
        }
    }
}
window.livePolling = new LivePollingManager();
