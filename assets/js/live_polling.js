function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function (m) { return map[m]; });
}
class LivePollingManager {
    constructor() {
        this.intervals = {};
        this.isActive = true;
        this.isOnline = navigator.onLine;
        this.updateQueue = [];
        this.config = window.PollingConfig || null;
        this.defaultIntervals = this.config ? this.config.intervals : {
            statistics: 3000,
            location: 3000,
            announcements: 3000,
            tables: 3000,
            schedules: 3000,
            courses: 3000,
            classes: 3000
        };
        this.maxPollingInterval = 10000;
        this.minPollingInterval = 2000;
        this.consecutiveNoChanges = 0;
        this.lastUpdateTime = '1970-01-01 00:00:00';
        this.visibilityObserver = null;
        this.currentTab = this.detectInitialTab();
        this.visibleElements = new Set();
        this.heartbeatInterval = null;
        this.pageType = this.detectPageType();
        this.observableElements = this.getObservableElementsForPage();
        this.lastStatusCheck = {};
        this.initialized = false;
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
        return 'faculty';
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
        const tabs = ['faculty', 'classes', 'courses', 'announcements', 'programs'];
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
        const locationElements = document.querySelectorAll('.location-section, .location-update-card');
        if (locationElements.length > 0) {
            elements.location_tab = {
                selector: '.location-section',
                description: 'Location Tab',
                polling: 'location'
            };
        }
        const scheduleElements = document.querySelectorAll('.schedule-section, .schedule-list');
        if (scheduleElements.length > 0) {
            elements.schedule_tab = {
                selector: '.schedule-section',
                description: 'Schedule Tab',
                polling: 'schedules'
            };
        }
        const scheduleTabs = document.querySelectorAll('.schedule-tab');
        scheduleTabs.forEach(tab => {
            const tabText = tab.textContent.trim();
            if (tabText) {
                elements[`schedule_${tabText.toLowerCase()}_tab`] = {
                    selector: `.schedule-tab:contains('${tabText}')`,
                    description: `${tabText} Tab`,
                    polling: 'schedules',
                    condition: () => {
                        const tabElement = Array.from(document.querySelectorAll('.schedule-tab'))
                            .find(t => t.textContent.trim() === tabText);
                        return tabElement?.classList.contains('active') || false;
                    }
                };
            }
        });
        const actionsElements = document.querySelectorAll('.actions-section');
        if (actionsElements.length > 0) {
            elements.actions_tab = {
                selector: '.actions-section',
                description: 'Actions Tab',
                polling: 'tables'
            };
        }
        const scheduleItems = document.querySelectorAll('.schedule-item');
        if (scheduleItems.length > 0) {
            elements.schedule_items = {
                selector: '.schedule-item',
                description: 'Schedule Items',
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
            console.log('Faculty grid found, faculty cards:', facultyGrid.querySelectorAll('.faculty-card').length);
        } else {
            console.log('Faculty grid NOT found');
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
        const facultyGrid = document.querySelector('.faculty-grid');
        if (facultyGrid) {
            elements.faculty_cards = {
                selector: '.faculty-grid .faculty-card',
                description: 'Faculty Cards',
                polling: 'location'
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
            const scheduleTab = e.target.closest('.schedule-tab');
            if (tabButton && tabButton.dataset.tab) {
                const previousTab = this.currentTab;
                this.currentTab = tabButton.dataset.tab;
                console.clear();
                const pageName = document.title.split(' - ')[1] || 'Dashboard';
                console.log(`Page: ${pageName}`);
                this.logCurrentStatus();
                this.refreshVisibleElements();
            } else if (scheduleTab && this.pageType === 'faculty') {
                setTimeout(() => {
                    console.clear();
                    const pageName = document.title.split(' - ')[1] || 'Dashboard';
                    console.log(`Page: ${pageName}`);
                    this.logCurrentStatus();
                    this.refreshVisibleElements();
                }, 100);
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
        this.startLocationPolling();
        this.startAnnouncementsPolling();
        if (window.userRole === 'faculty') {
            this.startSchedulePolling();
            this.startStatisticsPolling();
            this.startTablePolling();
        }
        if (['program_chair', 'campus_director'].includes(window.userRole)) {
            this.startStatisticsPolling();
            this.startTablePolling();
        }
        if (window.userRole === 'class') {
            this.startTablePolling();
        }
    }
    startSchedulePolling() {
        if (this.intervals.schedules) return;
        this.intervals.schedules = setInterval(() => {
            if (this.pageType === 'faculty') {
                this.fetchScheduleUpdates();
            } else if (this.hasVisibleElement('schedules')) {
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
        if (this.intervals.statistics) return;
        this.intervals.statistics = setInterval(() => {
            if (this.hasVisibleElement('statistics')) {
                this.fetchStatistics();
            }
        }, this.defaultIntervals.statistics);
        this.fetchStatistics();
    }
    startTablePolling() {
        this.intervals.tables = setInterval(() => {
            const activeTab = this.getActiveTab();
            if (this.hasVisibleElement('tables') && this.isTabObservable(activeTab)) {
                this.fetchTableUpdates();
            }
        }, this.defaultIntervals.tables);
        this.fetchTableUpdates();
    }
    startLocationPolling() {
        if (this.intervals.location) return;
        this.intervals.location = setInterval(() => {
            this.fetchLocationUpdates();
        }, this.defaultIntervals.location);
        this.fetchLocationUpdates();
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
    hasLocationElements() {
        if (this.pageType === 'director') {
            return this.isElementVisible('faculty-content') && document.querySelector('#faculty-content .status-badge');
        } else if (this.pageType === 'program') {
            return this.isElementVisible('faculty-content') && document.querySelector('.faculty-grid .status-dot');
        } else if (this.pageType === 'faculty') {
            return document.querySelector('.location-section') && this.isElementVisible('.location-section');
        } else if (this.pageType === 'class') {
            return this.isElementVisible('facultyGrid') && document.querySelector('#facultyGrid .status-dot');
        }
        return false;
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
        if (data.stats) {
            this.updateFacultyStats(data.stats);
        }
        if (data.schedules) {
            const scheduleContainers = document.querySelectorAll('.schedule-container, .schedule-section, .schedule-list');
            scheduleContainers.forEach(container => {
                if (container.querySelector('.schedule-item')) {
                    this.updateScheduleItems(container, data.schedules);
                }
            });
        }
    }
    updateScheduleItems(container, schedules) {
        const scheduleItems = container.querySelectorAll('.schedule-item');
        let updatedCount = 0;
        scheduleItems.forEach(item => {
            const courseCode = item.querySelector('.course-code')?.textContent?.trim();
            if (courseCode) {
                const schedule = schedules.find(s => s.course_code === courseCode);
                if (schedule && schedule.status) {
                    const statusBadge = item.querySelector('.status-badge');
                    const currentStatus = statusBadge?.textContent?.trim();
                    const newStatusText = this.getStatusText(schedule.status);
                    if (currentStatus !== newStatusText || statusBadge?.classList.contains('status-pending')) {
                        if (statusBadge) {
                            statusBadge.className = statusBadge.className.replace(/status-\w+/g, '');
                            statusBadge.className = `status-badge status-${schedule.status}`;
                            statusBadge.textContent = newStatusText;
                            statusBadge.style.animation = 'statusUpdate 0.3s ease-in-out';
                            setTimeout(() => statusBadge.style.animation = '', 300);
                        }
                        item.className = item.className.replace(/(ongoing|upcoming|finished|pending)/g, '');
                        item.classList.add(schedule.status);
                        this.updateScheduleActions(item, schedule);
                        updatedCount++;
                    }
                }
            }
        });
    }
    updateScheduleActions(item, schedule) {
        const statusSection = item.querySelector('.schedule-status');
        if (statusSection) {
            const existingButton = statusSection.querySelector('button');
            if (existingButton) {
                existingButton.remove();
            }
            if (schedule.status === 'ongoing') {
                const button = document.createElement('button');
                button.className = 'btn-small btn-primary';
                button.textContent = 'Mark Present';
                button.onclick = () => markAttendance(schedule.schedule_id);
                statusSection.appendChild(button);
            }
        }
    }
    getStatusText(status) {
        switch (status) {
            case 'ongoing': return 'In Progress';
            case 'upcoming': return 'Upcoming';
            case 'finished': return 'Completed';
            case 'pending': return 'Loading...';
            default: return 'Unknown';
        }
    }
    getStatusClass(status) {
        switch (status) {
            case 'Available':
                return 'status-available';
            case 'In Meeting':
                return 'status-in status-meeting';
            case 'On Leave':
                return 'status-on status-leave';
            case 'Offline':
            default:
                return 'status-offline';
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
        const observableElements = this.getObservableElementsStatus();
        const activeElements = Object.keys(observableElements).filter(key => observableElements[key]);
        if (activeElements.length === 0) {
            return;
        }
        const pollingTargets = this.getPollingTargetsFromObservable(activeElements);
        for (const target of pollingTargets) {
            try {
                let params = new URLSearchParams();
                params.append('action', 'get_dashboard_data');
                if (this.pageType === 'director') {
                    params.append('tab', target);
                }
                params.append('optimized', 'true');
                params.append('last_update', this.getLastUpdateForTab(target));
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
                    this.adjustPollingInterval(data.has_changes || false);
                    this.setLastUpdateForTab(target, data.timestamp);
                }
            } catch (error) {
                this.handlePollingError('tables', error);
            }
        }
    }
    updateStatisticsFromTableData(data) {
        if (this.pageType === 'faculty' && data.stats) {
            this.updateFacultyStats(data.stats);
        } else if (data.total_count !== undefined && data.tab) {
            this.updateTotalCount(data.tab, data.total_count);
        } else if (data.count !== undefined && data.tab) {
            this.updateTotalCount(data.tab, data.count);
        }
    }
    updateFacultyStats(stats) {
        const mapping = {
            'Today': stats.today,
            'Ongoing': stats.ongoing,
            'Completed': stats.completed,
            'Status': stats.status
        };
        document.querySelectorAll('.header-stat').forEach(card => {
            const label = card.querySelector('.header-stat-label')?.textContent?.trim();
            const numberEl = card.querySelector('.header-stat-number');
            if (label && mapping[label] !== undefined && numberEl) {
                numberEl.textContent = mapping[label];
            }
        });
    }
    getActiveTab() {
        const activeTabElement = document.querySelector('.tab-content.active');
        if (activeTabElement) {
            return activeTabElement.id.replace('-content', '');
        }
        return this.currentTab;
    }
    isTabObservable(tab) {
        const tabElementKey = `${tab}_tab`;
        const tableElementKey = `${tab}_table`;
        const tabElement = this.observableElements[tabElementKey];
        const tableElement = this.observableElements[tableElementKey];
        if (tabElement && tabElement.condition) {
            return tabElement.condition();
        }
        if (tableElement && tableElement.condition) {
            return tableElement.condition();
        }
        return this.isElementVisible(`${tab}-content`);
    }
    getLastUpdateForTab(tab) {
        if (!this.lastUpdateTimes) {
            this.lastUpdateTimes = {};
        }
        return this.lastUpdateTimes[tab] || '1970-01-01 00:00:00';
    }
    setLastUpdateForTab(tab, timestamp) {
        if (!this.lastUpdateTimes) {
            this.lastUpdateTimes = {};
        }
        if (timestamp) {
            this.lastUpdateTimes[tab] = timestamp;
        }
    }
    getObservableElementsStatus() {
        const observableStatus = {};
        Object.keys(this.observableElements).forEach(elementKey => {
            const element = this.observableElements[elementKey];
            let isObservable = false;
            if (element.condition) {
                isObservable = element.condition();
            } else {
                const domElement = document.querySelector(element.selector);
                isObservable = domElement ? this.isElementVisible(domElement) : false;
            }
            observableStatus[elementKey] = isObservable;
        });
        return observableStatus;
    }
    getPollingTargetsFromObservable(activeElements) {
        const targets = new Set();
        activeElements.forEach(elementKey => {
            const element = this.observableElements[elementKey];
            if (element.polling === 'tables' || element.polling === 'cards') {
                if (elementKey.includes('_tab') || elementKey.includes('_table')) {
                    const tabName = elementKey.replace('_tab', '').replace('_table', '');
                    targets.add(tabName);
                }
            }
        });
        if (targets.size === 0 && this.pageType === 'director') {
            const activeTab = this.getActiveTab();
            if (activeTab) {
                targets.add(activeTab);
            }
        }
        return Array.from(targets);
    }
    handleOptimizedResponse(data) {
        if (data.has_changes && data.updates && data.updates.length > 0) {
            data.updates.forEach(update => {
                if (this.pageType === 'director') {
                    this.addToTable(data.tab, update);
                } else if (this.pageType === 'program') {
                    this.addToCards(data.tab, update);
                }
            });
            this.updateCounts(data.tab, data.updates.length);
            console.log(`${data.tab}: ${data.updates.length} new updates received`);
        } else {
            console.log(`${data.tab}: No changes detected`);
        }
        if (data.total_count !== undefined) {
            this.updateTotalCount(data.tab, data.total_count);
        }
    }
    adjustPollingInterval(hasChanges) {
        const currentInterval = this.defaultIntervals.tables;
        if (hasChanges) {
            this.consecutiveNoChanges = 0;
            if (currentInterval > this.minPollingInterval) {
                this.defaultIntervals.tables = Math.max(this.minPollingInterval, currentInterval - 1000);
                this.restartTablePolling();
            }
        } else {
            this.consecutiveNoChanges++;
            if (this.consecutiveNoChanges >= 3) {
                if (currentInterval < this.maxPollingInterval) {
                    this.defaultIntervals.tables = Math.min(this.maxPollingInterval, currentInterval + 1000);
                    this.restartTablePolling();
                }
            }
        }
    }
    restartTablePolling() {
        if (this.intervals.tables) {
            clearInterval(this.intervals.tables);
            this.intervals.tables = setInterval(() => {
                const activeTab = this.getActiveTab();
                if (this.hasVisibleElement('tables') && this.isTabObservable(activeTab)) {
                    this.fetchTableUpdates();
                }
            }, this.defaultIntervals.tables);
        }
    }
    updateTotalCount(tab, totalCount) {
        const statLabels = {
            'faculty': 'Faculty',
            'classes': 'Classes',
            'courses': 'Courses',
            'announcements': 'Announcements'
        };
        const label = statLabels[tab];
        if (label) {
            const statElements = document.querySelectorAll('.header-stat-label');
            statElements.forEach(statElement => {
                if (statElement.textContent.trim() === label) {
                    const numberElement = statElement.parentElement.querySelector('.header-stat-number');
                    if (numberElement) {
                        const currentValue = parseInt(numberElement.textContent) || 0;
                        if (currentValue !== totalCount) {
                            this.animateValueChange(numberElement, currentValue, totalCount);
                        }
                    }
                }
            });
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
                this.handleDeletions(this.previousData.faculty_data, data.faculty_data, 'faculty');
                this.previousData.faculty_data = [...data.faculty_data];
            }
            if (data.classes_data) {
                this.handleDeletions(this.previousData.classes_data, data.classes_data, 'classes');
                this.previousData.classes_data = [...data.classes_data];
            }
            if (data.courses_data) {
                this.handleDeletions(this.previousData.courses_data, data.courses_data, 'courses');
                this.previousData.courses_data = [...data.courses_data];
            }
        }
    }
    checkTableChanges(containerSelector, newData, type) {
        const container = document.querySelector(containerSelector);
        if (!container || !Array.isArray(newData)) return null;
        const currentRows = container.querySelectorAll('tr:not(.expansion-row)').length;
        const newCount = newData.length;
        if (currentRows !== newCount) {
            console.log(`${type} count change detected. Reloading page to update...`);
            setTimeout(() => location.reload(), 500);
            return { type: 'reload_triggered' };
        }
        const currentIds = Array.from(container.querySelectorAll('tr:not(.expansion-row)')).map(row => {
            const idField = this.getIdField(type).replace('_', '-');
            return row.getAttribute(`data-${idField}`);
        }).filter(id => id);
        const newIds = newData.map(item => item[this.getIdField(type)].toString());
        const hasIdMismatch = newIds.some(id => !currentIds.includes(id)) || currentIds.some(id => !newIds.includes(id));
        if (hasIdMismatch) {
            console.log(`${type} content mismatch detected. Reloading page...`);
            setTimeout(() => location.reload(), 500);
            return { type: 'reload_triggered' };
        }
        return null;
    }
    checkCardChanges(containerSelector, newData, type) {
        const container = document.querySelector(containerSelector);
        if (!container || !Array.isArray(newData)) return null;
        let cardSelector;
        if (type === 'faculty') {
            cardSelector = '.faculty-card:not(.add-card)';
        } else if (type === 'classes') {
            cardSelector = '.class-card:not(.add-card)';
        } else if (type === 'courses') {
            cardSelector = '.course-card:not(.add-card)';
        } else {
            return null;
        }
        const currentCards = container.querySelectorAll(cardSelector).length;
        const newCount = newData.length;
        if (currentCards !== newCount) {
            console.log(`${type} (cards) count change detected. Current: ${currentCards}, New: ${newCount}. Reloading page...`);
            setTimeout(() => location.reload(), 500);
            return { type: 'reload_triggered' };
        }
        return null;
    }
    logCurrentStatus() {
        const pageName = document.title.split(' - ')[1] || 'Dashboard';
        const activeTab = this.getActiveTab();
        console.log('Observable elements:');
        const observableStatus = this.getObservableElementsStatus();
        Object.keys(observableStatus).forEach(elementKey => {
            console.log(`${elementKey}: ${observableStatus[elementKey]}`);
        });
        const activeElements = Object.keys(observableStatus).filter(key => observableStatus[key]);
        const pollingTargets = this.getPollingTargetsFromObservable(activeElements);
        console.log(`Active polling targets: ${pollingTargets.length > 0 ? pollingTargets.join(', ') : 'none'}`);
        if (pollingTargets.length > 0) {
            pollingTargets.forEach(target => {
                const lastUpdate = this.getLastUpdateForTab(target);
                console.log(`${target}: Optimized mode, last update: ${lastUpdate}`);
            });
        }
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
        switch (tableType) {
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
                if (this.pageType === 'class') {
                    console.clear();
                    this.logCurrentStatus();
                }
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
            const response = await fetch('assets/php/polling_api.php?action=get_dashboard_data&tab=announcements', {
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
            'announcements': 'announcement_id',
            'programs': 'program_id'
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
        if (currentEntities && currentEntities.faculty) {
            currentEntities.faculty.forEach(faculty => {
                this.updateEntityStatus('faculty', faculty);
            });
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
    updateLocationDisplay(data) {
        if (this.pageType === 'faculty') {
            if (data.current_location !== undefined || data.status !== undefined) {
                const locationText = document.getElementById('currentLocation');
                const statusDots = document.querySelectorAll('.status-dot');
                const statusTexts = document.querySelectorAll('.location-status span:not(.status-dot)');
                if (data.current_location !== undefined && locationText) {
                    locationText.textContent = data.current_location || 'No Location';
                }
                if (data.status !== undefined) {
                    const status = data.status || 'Offline';
                    const statusClass = this.getStatusClass(status);
                    statusDots.forEach(dot => {
                        dot.className = `status-dot ${statusClass}`;
                    });
                    statusTexts.forEach(text => {
                        text.textContent = status;
                    });
                }
            }
            if (data.last_updated !== undefined) {
                const lastUpdatedElement = document.querySelector('.location-updated');
                if (lastUpdatedElement) {
                    const originalText = lastUpdatedElement.textContent;
                    const newText = `Last updated: ${data.last_updated}`;
                    lastUpdatedElement.textContent = newText;
                    lastUpdatedElement.style.transition = 'background-color 0.5s ease';
                    lastUpdatedElement.style.backgroundColor = '#fffbeb';
                    lastUpdatedElement.style.padding = '2px 5px';
                    lastUpdatedElement.style.borderRadius = '4px';
                    setTimeout(() => {
                        lastUpdatedElement.style.backgroundColor = 'transparent';
                    }, 500);
                }
            }
            if (data.faculty && Array.isArray(data.faculty)) {
                this.updateFacultyOwnStatus(data.faculty);
            }
        } else if (this.pageType === 'program' && data.faculty) {
            data.faculty.forEach(faculty => {
                const existingCard = document.querySelector(`.faculty-card[data-faculty-id="${faculty.faculty_id}"]`);
                if (existingCard) {
                    this.updateCardStatus(existingCard, faculty, 'faculty');
                }
            });
        } else if (this.pageType === 'director' && data.faculty) {
            data.faculty.forEach(faculty => {
                const row = document.querySelector(`tr[data-faculty-id="${faculty.faculty_id}"]`);
                if (row) {
                    this.updateTableRowStatus(row, faculty, 'faculty');
                }
            });
        } else if (this.pageType === 'class' && data.faculty) {
            data.faculty.forEach(faculty => {
                const existingCard = document.querySelector(`.faculty-card[data-faculty-id="${faculty.faculty_id}"]`);
                if (existingCard) {
                    this.updateCardStatus(existingCard, faculty, 'faculty');
                }
            });
        }
    }
    updateTableRowStatus(row, entityData, entityType) {
        const statusClass = this.getStatusClass(entityData.status);
        switch (entityType) {
            case 'faculty':
                const statusBadge = row.querySelector('.status-badge');
                if (statusBadge) {
                    statusBadge.className = `status-badge ${statusClass}`;
                    statusBadge.textContent = entityData.status || 'Offline';
                }
                const statusDot = row.querySelector('.status-dot');
                if (statusDot) {
                    statusDot.className = `status-dot ${statusClass}`;
                }
                const locationCell = row.querySelector('.location-column, .location-cell, .current-location');
                if (locationCell && entityData.current_location !== undefined) {
                    locationCell.textContent = entityData.current_location || 'Not Available';
                }
                const lastSeenCell = row.querySelector('.last-seen');
                if (lastSeenCell && entityData.last_seen !== undefined) {
                    lastSeenCell.textContent = entityData.last_seen || 'Unknown';
                }
                break;
            default:
                const defaultStatusBadge = row.querySelector('.status-badge');
                if (defaultStatusBadge) {
                    defaultStatusBadge.className = `status-badge ${statusClass}`;
                    defaultStatusBadge.textContent = entityData.status || 'Offline';
                }
                const defaultStatusDot = row.querySelector('.status-dot');
                if (defaultStatusDot) {
                    defaultStatusDot.className = `status-dot ${statusClass}`;
                }
                break;
        }
    }
    updateCardStatus(card, entityData, entityType) {
        const statusClass = this.getStatusClass(entityData.status);
        switch (entityType) {
            case 'faculty':
                const statusBadge = card.querySelector('.status-badge');
                if (statusBadge) {
                    statusBadge.className = `status-badge ${statusClass}`;
                    statusBadge.textContent = entityData.status || 'Offline';
                }
                const statusDot = card.querySelector('.status-dot');
                if (statusDot) {
                    statusDot.className = `status-dot ${statusClass}`;
                }
                const locationText = card.querySelector('.location-text');
                if (locationText) {
                    locationText.textContent = entityData.status || 'Offline';
                }
                const locationDiv = card.querySelector('.location-info div:nth-child(2)');
                if (locationDiv && entityData.current_location !== undefined) {
                    locationDiv.textContent = entityData.current_location || 'Not Available';
                }
                const currentLocationElement = card.querySelector('.current-location, .faculty-location');
                if (currentLocationElement && entityData.current_location !== undefined) {
                    currentLocationElement.textContent = entityData.current_location || 'Not Available';
                }
                const timeInfo = card.querySelector('.time-info');
                if (timeInfo && entityData.last_updated) {
                    timeInfo.textContent = `Last updated: ${entityData.last_updated}`;
                }
                const lastSeenElement = card.querySelector('.last-seen, .faculty-last-seen');
                if (lastSeenElement && entityData.last_seen !== undefined) {
                    lastSeenElement.textContent = entityData.last_seen || 'Unknown';
                }
                break;
            default:
                const defaultStatusBadge = card.querySelector('.status-badge');
                if (defaultStatusBadge) {
                    defaultStatusBadge.className = `status-badge ${statusClass}`;
                    defaultStatusBadge.textContent = entityData.status || 'Offline';
                }
                const defaultStatusDot = card.querySelector('.status-dot');
                if (defaultStatusDot) {
                    defaultStatusDot.className = `status-dot ${statusClass}`;
                }
                break;
        }
    }
    isRecentlyCreated(entity) {
        if (!entity.created_at) return false;
        const createdTime = new Date(entity.created_at);
        const currentTime = new Date();
        const timeDifference = (currentTime - createdTime) / 1000;
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
    addToCards(entityType, entityData) {
        if (this.pageType !== 'program') return;
        const cardContainer = document.querySelector(`.${entityType}-grid, .${entityType}-cards`);
        if (!cardContainer) return;
        const idField = entityType.replace('s', '') + '_id';
        const existingCard = cardContainer.querySelector(`[data-${entityType.replace('s', '')}-id="${entityData[idField]}"]`);
        if (existingCard) return;
        let newCard = '';
        switch (entityType) {
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
                    <div class="time-info">Last updated: ${faculty.last_updated || 'Unknown'}</div>
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
        switch (actualViewType) {
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
    addToTable(tab, entityData) {
        const tableBody = document.querySelector(`#${tab}-content .data-table tbody`);
        if (!tableBody) return;
        const rowHTML = this.createTableRow(entityData, tab);
        if (rowHTML) {
            tableBody.insertAdjacentHTML('beforeend', rowHTML);
            const newRow = tableBody.lastElementChild;
            if (newRow) {
                newRow.style.animation = 'fadeIn 0.5s ease-out';
            }
        }
    }
    createTableRow(item, type) {
        switch (type) {
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
        switch (type) {
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
    updateLocationDisplay(data) {
        if (this.pageType === 'faculty' || this.pageType === 'class') {
            if (data.faculty && Array.isArray(data.faculty)) {
                this.updateFacultyList(data.faculty);
            }
            if (data.current_location !== undefined) {
                this.updateCurrentLocation(data.current_location, data.status, data.last_updated);
            }
        } else if (this.pageType === 'program') {
        } else if (this.pageType === 'director') {
            if (data.faculty && Array.isArray(data.faculty)) {
                this.updateDirectorFacultyStatus(data.faculty);
            }
        } else if (this.pageType === 'class') {
            if (data.faculty && Array.isArray(data.faculty)) {
                this.updateClassFacultyStatus(data.faculty);
            }
        }
    }
    updateDirectorFacultyStatus(facultyData) {
        const facultyTableBody = document.querySelector('#faculty-content .data-table tbody');
        if (!facultyTableBody) return;
        facultyData.forEach(faculty => {
            const row = facultyTableBody.querySelector(`tr[data-faculty-id="${faculty.faculty_id}"]`);
            if (row) {
                const statusBadge = row.querySelector('.status-indicator, .status-badge');
                const locationCell = row.querySelector('td:nth-child(5)');
                if (statusBadge) {
                    const oldStatus = statusBadge.textContent.trim().toLowerCase();
                    const newStatus = faculty.status || 'offline';
                    if (oldStatus !== newStatus.toLowerCase()) {
                        statusBadge.className = statusBadge.className.replace(/status-\w+/, `status-${newStatus.toLowerCase()}`);
                        statusBadge.textContent = newStatus;
                        statusBadge.style.animation = 'statusPulse 0.6s ease';
                    }
                }
                if (locationCell && faculty.current_location) {
                    const oldLocation = locationCell.textContent.trim();
                    const newLocation = faculty.current_location || 'Not Available';
                    if (oldLocation !== newLocation) {
                        locationCell.textContent = newLocation;
                        locationCell.style.color = '#2ecc71';
                        locationCell.style.fontWeight = 'bold';
                        setTimeout(() => {
                            locationCell.style.color = '';
                            locationCell.style.fontWeight = '';
                        }, 2000);
                    }
                }
            }
        });
    }
    updateProgramFacultyStatus(facultyData) {
        const facultyGrid = document.querySelector('.faculty-grid');
        if (!facultyGrid) return;
        facultyData.forEach(faculty => {
            const card = facultyGrid.querySelector(`.faculty-card[data-faculty-id="${faculty.faculty_id}"]`) ||
                facultyGrid.querySelector(`.faculty-card[data-name*="${faculty.faculty_name}"]`);
            if (card) {
                const statusDot = card.querySelector('.status-dot, .status-indicator');
                const locationText = card.querySelector('.location-text, .status-text');
                const locationDiv = card.querySelector('.current-location, .location-info p:last-child');
                if (statusDot) {
                    const oldStatus = statusDot.className;
                    const newStatus = faculty.status || 'offline';
                    const statusClass = newStatus.toLowerCase() === 'available' ? 'available' : 'offline';
                    const newStatusClass = `status-dot status-${statusClass}`;
                    if (oldStatus !== newStatusClass) {
                        statusDot.className = newStatusClass;
                        if (locationText) locationText.textContent = newStatus;
                        statusDot.style.transform = 'scale(1.3)';
                        statusDot.style.boxShadow = '0 0 15px rgba(46, 204, 113, 0.6)';
                        setTimeout(() => {
                            statusDot.style.transform = 'scale(1)';
                            statusDot.style.boxShadow = '';
                        }, 500);
                    }
                }
                if (locationDiv && faculty.current_location) {
                    const oldLocation = locationDiv.textContent.trim();
                    const newLocation = faculty.current_location;
                    if (oldLocation !== newLocation) {
                        locationDiv.textContent = newLocation;
                        locationDiv.style.color = '#2ecc71';
                        locationDiv.style.fontWeight = 'bold';
                        setTimeout(() => {
                            locationDiv.style.color = '';
                            locationDiv.style.fontWeight = '';
                        }, 2000);
                    }
                }
                const timeInfo = card.querySelector('.time-info, .last-updated');
                if (timeInfo && faculty.last_updated) {
                    timeInfo.textContent = faculty.last_updated;
                }
            }
        });
    }
    updateClassFacultyStatus(facultyData) {
        const facultyGrid = document.querySelector('#facultyGrid');
        if (!facultyGrid) return;
        facultyData.forEach(faculty => {
            const card = facultyGrid.querySelector(`.faculty-card[data-faculty-id="${faculty.faculty_id}"]`);
            if (card) {
                const statusDot = card.querySelector('.status-dot');
                const locationText = card.querySelector('.location-text');
                const currentLocationDiv = card.querySelector('.location-info div:nth-child(2)');
                const timeInfo = card.querySelector('.time-info');
                if (statusDot) {
                    const oldStatus = statusDot.className.replace('status-dot ', '');
                    const newStatus = faculty.status || 'offline';
                    const newStatusClass = `status-dot status-${newStatus}`;
                    if (statusDot.className !== newStatusClass) {
                        statusDot.className = newStatusClass;
                        statusDot.style.transition = 'all 0.5s ease';
                        statusDot.style.transform = 'scale(1.3)';
                        if (newStatus === 'available') {
                            statusDot.style.boxShadow = '0 0 15px rgba(46, 204, 113, 0.8)';
                        } else {
                            statusDot.style.boxShadow = '0 0 15px rgba(108, 117, 125, 0.8)';
                        }
                        setTimeout(() => {
                            statusDot.style.transform = 'scale(1)';
                            statusDot.style.boxShadow = '';
                        }, 500);
                    }
                }
                if (locationText) {
                    const statusLabels = {
                        'available': 'Available',
                        'offline': 'Offline'
                    };
                    const newStatusText = statusLabels[faculty.status] || 'Unknown';
                    if (locationText.textContent.trim() !== newStatusText) {
                        locationText.textContent = newStatusText;
                        locationText.style.color = '#2ecc71';
                        locationText.style.fontWeight = 'bold';
                        setTimeout(() => {
                            locationText.style.color = '';
                            locationText.style.fontWeight = '';
                        }, 2000);
                    }
                }
                if (currentLocationDiv && faculty.current_location) {
                    const oldLocation = currentLocationDiv.textContent.trim();
                    const newLocation = faculty.current_location || 'Unknown Location';
                    if (oldLocation !== newLocation) {
                        currentLocationDiv.textContent = newLocation;
                        currentLocationDiv.style.transition = 'all 0.4s ease';
                        currentLocationDiv.style.color = '#2ecc71';
                        currentLocationDiv.style.fontWeight = 'bold';
                        currentLocationDiv.style.transform = 'translateX(3px)';
                        setTimeout(() => {
                            currentLocationDiv.style.color = '#333';
                            currentLocationDiv.style.fontWeight = '500';
                            currentLocationDiv.style.transform = '';
                        }, 3000);
                    }
                }
                if (timeInfo && faculty.last_updated) {
                    const updateText = `Last updated: ${faculty.last_updated}`;
                    if (timeInfo.textContent !== updateText) {
                        timeInfo.textContent = updateText;
                        timeInfo.style.color = '#3498db';
                        timeInfo.style.fontWeight = 'bold';
                        setTimeout(() => {
                            timeInfo.style.color = '';
                            timeInfo.style.fontWeight = '';
                        }, 2000);
                    }
                }
            }
        });
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
        const now = new Date();
        const currentDay = now.toLocaleDateString('en-US', { weekday: 'long' });
        const currentTime = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
        const currentDate = now.toLocaleDateString('en-US');
        const pageTitle = this.pageType === 'faculty' ? 'Faculty Dashboard' :
            this.pageType === 'class' ? 'Class Dashboard' :
                'Dashboard';
        console.log(`Page: ${pageTitle}`);
        console.log(`${currentDay}, ${currentDate} ${currentTime}`);
        elements.forEach(element => console.log(element));
        if (this.pageType === 'class') {
            console.log('Faculty with schedules:');
            this.logActualScheduleItems();
        }
    }
    logActualScheduleItems() {
        if (this.pageType === 'class') {
            console.log('CLASS DEBUG FUNCTION CALLED!');
            const facultyCards = document.querySelectorAll('.faculty-card:not(.add-card)');
            if (facultyCards.length === 0) {
                console.log('No faculty found');
                return;
            }
            console.log('Faculties found:');
            facultyCards.forEach(card => {
                const facultyName = card.querySelector('.faculty-name')?.textContent?.trim() || 'Unknown Faculty';
                const program = card.querySelector('.faculty-program')?.textContent?.trim() || '';
                console.log(`  ${facultyName} (${program})`);
            });
            console.log('\nClass schedule:');
            const allCourses = [];
            facultyCards.forEach(card => {
                const facultyName = card.querySelector('.faculty-name')?.textContent?.trim() || 'Unknown Faculty';
                const courseItems = card.querySelectorAll('.course-info');
                courseItems.forEach(item => {
                    const courseContent = item.querySelector('.course-content');
                    const statusElement = item.querySelector('.course-status-label');
                    if (courseContent && statusElement) {
                        const strongElement = courseContent.querySelector('strong');
                        const smallElement = courseContent.querySelector('small');
                        if (strongElement && smallElement) {
                            const courseCodeText = strongElement.textContent.trim();
                            const courseCode = courseCodeText.replace(':', '').trim();
                            const fullText = courseContent.textContent.trim();
                            const courseName = fullText.split('\n')[0].replace(courseCodeText, '').trim();
                            const detailText = smallElement.textContent.trim();
                            const parts = detailText.split('|').map(p => p.trim());
                            const days = parts[0] || '';
                            const timeRange = parts[1] || '';
                            const room = parts[2] || '';
                            let formattedTime = timeRange;
                            if (timeRange.includes(' - ')) {
                                const [start, end] = timeRange.split(' - ');
                                let startTime = start.replace(' AM', '').replace(' PM', '');
                                let endTime = end.replace(' AM', '').replace(' PM', '');
                                startTime = startTime.replace(':00', '');
                                endTime = endTime.replace(':00', '');
                                formattedTime = `${startTime}-${endTime}`;
                            }
                            const status = statusElement.textContent.trim().toLowerCase();
                            allCourses.push({
                                courseCode,
                                courseName,
                                facultyName,
                                days,
                                time: formattedTime,
                                status,
                                room
                            });
                        }
                    }
                });
            });
            allCourses.sort((a, b) => a.courseCode.localeCompare(b.courseCode));
            if (allCourses.length === 0) {
                console.log('  No courses scheduled');
            } else {
                allCourses.forEach(course => {
                    console.log(`  ${course.courseCode}: ${course.facultyName}, ${course.courseName}, ${course.days} ${course.time}, ${course.status}`);
                });
            }
        } else {
            const possibleContainers = [
                '.schedule-grid',
                '.schedule-list',
                '.today-schedule',
                '.schedule-container',
                '.tab-content.active',
                '#schedule-content',
                '[data-tab="schedule"]'
            ];
            let scheduleItems = [];
            for (const selector of possibleContainers) {
                const container = document.querySelector(selector);
                if (container) {
                    const items = container.querySelectorAll('.schedule-item, .schedule-card, .class-item');
                    if (items.length > 0) {
                        scheduleItems = items;
                        break;
                    }
                }
            }
            if (scheduleItems.length === 0) {
                scheduleItems = document.querySelectorAll('.schedule-item, .schedule-card, .class-item');
            }
            if (scheduleItems.length === 0) {
                console.log('No items found');
                return;
            }
            scheduleItems.forEach(item => {
                const timeElement = item.querySelector('.time, .schedule-time, .time-range, .class-time');
                const courseElement = item.querySelector('.course-code, .course-name, .class-code, .subject-code');
                const statusElement = item.querySelector('.status-badge, .schedule-status, .class-status, .badge');
                let timeText = timeElement ? timeElement.textContent.trim() : '';
                const course = courseElement ? courseElement.textContent.trim() : 'Unknown';
                const status = statusElement ? statusElement.textContent.trim().toLowerCase() : 'unknown';
                if (timeText.includes(' - ')) {
                    const [startTime, endTime] = timeText.split(' - ');
                    const start = this.formatTime12Hour(startTime);
                    const duration = this.calculateDuration(startTime, endTime);
                    timeText = `${start} ${duration}hr`;
                }
                console.log(`${timeText} ${course}: ${status}`);
            });
        }
    }
    logScheduleItemsForActiveTabs(elements) {
        this.logActualScheduleItems();
    }
    formatTime12Hour(time24) {
        if (!time24) return '';
        const [hours, minutes] = time24.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    }
    calculateDuration(startTime, endTime) {
        if (!startTime || !endTime) return '?';
        const start = new Date(`2000-01-01 ${startTime}`);
        const end = new Date(`2000-01-01 ${endTime}`);
        const diffMs = end - start;
        const diffHours = diffMs / (1000 * 60 * 60);
        return diffHours.toFixed(1);
    }
    startHeartbeat() {
        this.heartbeatInterval = setInterval(() => {
            if (this.isOnline && !document.hidden) {
                this.sendHeartbeat();
            }
        }, 120000);
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
            switch (update.type) {
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
        const newInterval = Math.min(currentInterval * 2, 30000);
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
            switch (type) {
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
    updateFacultyOwnStatus(facultyData) {
        const currentUserId = window.userId || sessionStorage.getItem('userId');
        const currentUser = facultyData.find(faculty => faculty.user_id == currentUserId);
        if (!currentUser) return;
        const statusDots = document.querySelectorAll('.status-dot');
        const statusTexts = document.querySelectorAll('.location-status span:not(.status-dot)');
        const locationText = document.getElementById('currentLocation');
        const status = currentUser.status || 'Offline';
        const statusClass = this.getStatusClass(status);
        statusDots.forEach(dot => {
            const oldClass = dot.className;
            const newClass = `status-dot ${statusClass}`;
            if (oldClass !== newClass) {
                dot.className = newClass;
                dot.style.animation = 'statusUpdate 0.3s ease-in-out';
                setTimeout(() => dot.style.animation = '', 300);
            }
        });
        statusTexts.forEach(text => {
            if (text.textContent.trim() !== status) {
                text.textContent = status;
                text.style.animation = 'statusUpdate 0.3s ease-in-out';
                setTimeout(() => text.style.animation = '', 300);
            }
        });
        if (locationText && currentUser.current_location) {
            locationText.textContent = currentUser.current_location || 'No Location';
        }
        console.log('Current user data:', currentUser);
        console.log('last_updated value:', currentUser.last_updated);
        if (currentUser.last_updated) {
            const lastUpdatedElement = document.querySelector('.location-updated');
            console.log('lastUpdatedElement found:', lastUpdatedElement);
            if (lastUpdatedElement) {
                lastUpdatedElement.textContent = `Last updated: ${currentUser.last_updated}`;
                console.log('Updated timestamp to:', currentUser.last_updated);
            }
        } else {
            console.log('No last_updated in currentUser object');
        }
    }
}
window.livePolling = new LivePollingManager();

