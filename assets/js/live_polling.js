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
        
        // Common elements across pages
        const statCards = document.querySelectorAll('.header-stat, .stat-card');
        if (statCards.length > 0) {
            elements.statistics = {
                selector: '.header-stat, .stat-card',
                description: 'Stat Cards',
                polling: 'statistics'
            };
        }

        // Page-specific elements
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
        // Tab-based tables
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
                        // console.log(`Tab ${tab} active check: ${isActive}`); // Debug
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
                            // console.log(`Table ${tab} active check: ${isActive && hasTable}`); // Debug
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
        // Three tabs with GRIDS/CARDS (not tables!)
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
        
        // Faculty cards (online status polling)
        const facultyGrid = document.querySelector('.faculty-grid');
        if (facultyGrid) {
            elements.faculty_cards = {
                selector: '.faculty-grid .faculty-card',
                description: 'Faculty Cards',
                polling: 'location'
            };
        }
        
        // Course cards polling when courses tab is active
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
            console.clear();
            const pageName = document.title.split(' - ')[1] || 'Dashboard';
            console.log(`Page: ${pageName}`);
            
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
        
        // Tab switching detection
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
        
        // Observe elements after DOM is loaded
        setTimeout(() => this.observeElements(), 100);
    }
    
    observeElements() {
        // Observe stat cards
        const statCards = document.querySelectorAll('.header-stat');
        statCards.forEach(el => {
            this.visibilityObserver.observe(el);
        });
        
        // Observe tables
        const tables = document.querySelectorAll('.data-table');
        tables.forEach(el => {
            this.visibilityObserver.observe(el);
        });
        
        // Observe tab contents
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(el => {
            this.visibilityObserver.observe(el);
        });
        
        // Observe location displays
        const locationElements = document.querySelectorAll('.faculty-location, .current-location-display');
        locationElements.forEach(el => {
            this.visibilityObserver.observe(el);
        });
        
    }
    
    refreshVisibleElements() {
        // Clear current observations
        this.visibleElements.clear();
        
        // Re-observe elements without logging
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
        
        // Original polling logic based on user role
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
        // Handle both 'tables' and 'cards' polling types
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
        // Handle both element objects and selector strings
        if (typeof element === 'string') {
            if (element.endsWith('-content')) {
                return document.querySelector(`#${element}`)?.classList.contains('active') || false;
            }
            const domElement = document.querySelector(element);
            return domElement ? this.isElementVisible(domElement) : false;
        }
        
        // Handle DOM elements
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
                this.fetchStatistics();
            }
        }, this.defaultIntervals.statistics);
        
        this.fetchStatistics();
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
            console.error('Schedule polling failed:', error);
            this.handlePollingError('schedules', error);
        }
    }

    updateScheduleDisplay(data) {
        if (data.schedules) {
            // Update schedule containers
            const scheduleContainers = document.querySelectorAll('.schedule-container, .schedule-section');
            scheduleContainers.forEach(container => {
                if (container.querySelector('.schedule-item')) {
                    // Update existing schedule items
                    this.updateScheduleItems(container, data.schedules);
                }
            });
        }
    }

    updateScheduleItems(container, schedules) {
        // Update schedule items based on current time and status
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
                
                // Update class info based on schedule status
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
        if (!this.isOnline) {
            this.queueUpdate('statistics', { action: 'get_statistics' });
            return;
        }
        
        try {
            const response = await fetch('assets/php/polling_api.php?action=get_statistics', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateStatisticsDisplay(data.data);
            } else {
                console.log('Statistics fetch failed:', data.message);
            }
        } catch (error) {
            console.error('Statistics polling failed:', error);
            this.handlePollingError('statistics', error);
        }
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
            }
        } catch (error) {
            console.error('Table polling failed:', error);
            this.handlePollingError('tables', error);
        }
    }
    
    detectAndLogChanges(data) {
        const activeTabContent = document.querySelector('.tab-content.active');
        const activeTabId = activeTabContent ? activeTabContent.id.replace('-content', '') : null;
        
        let changesDetected = false;
        
        if (this.pageType === 'director') {
            // Director: Check current active tab
            if (activeTabId === 'faculty' && data.faculty_data) {
                const changes = this.checkTableChanges('#faculty-content .data-table tbody', data.faculty_data, 'faculty');
                if (changes) {
                    this.logChanges('faculty', changes);
                    changesDetected = true;
                }
            } else if (activeTabId === 'classes' && data.classes_data) {
                const changes = this.checkTableChanges('#classes-content .data-table tbody', data.classes_data, 'classes');
                if (changes) {
                    this.logChanges('classes', changes);
                    changesDetected = true;
                }
            } else if (activeTabId === 'courses' && data.courses_data) {
                const changes = this.checkTableChanges('#courses-content .data-table tbody', data.courses_data, 'courses');
                if (changes) {
                    this.logChanges('courses', changes);
                    changesDetected = true;
                }
            } else if (activeTabId === 'announcements' && data.announcements_data) {
                const changes = this.checkTableChanges('#announcements-content .data-table tbody', data.announcements_data, 'announcements');
                if (changes) {
                    this.logChanges('announcements', changes);
                    changesDetected = true;
                }
            }
        } else if (this.pageType === 'program') {
            // Program: Check all tabs but only log for active one
            if (data.faculty_data) {
                const changes = this.checkCardChanges('.faculty-grid', data.faculty_data, 'faculty');
                if (changes && activeTabId === 'faculty') {
                    this.logChanges('faculty', changes);
                    changesDetected = true;
                }
            }
            
            if (data.classes_data) {
                const changes = this.checkCardChanges('.classes-grid', data.classes_data, 'classes');
                if (changes && activeTabId === 'classes') {
                    this.logChanges('classes', changes);
                    changesDetected = true;
                }
            }
            
            if (data.courses_data) {
                const changes = this.checkCardChanges('.courses-grid', data.courses_data, 'courses');
                if (changes && activeTabId === 'courses') {
                    this.logChanges('courses', changes);
                    changesDetected = true;
                }
            }
        }
    }
    
    checkTableChanges(containerSelector, newData, type) {
        const container = document.querySelector(containerSelector);
        if (!container || !Array.isArray(newData)) return null;
        
        const currentRows = container.querySelectorAll('tr.expandable-row, tr:not(.expansion-row)').length;
        const newCount = newData.length;
        
        if (currentRows !== newCount) {
            return {
                type: 'count_change',
                oldCount: currentRows,
                newCount: newCount,
                difference: newCount - currentRows
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
            return {
                type: 'count_change',
                oldCount: currentCards,
                newCount: newCount,
                difference: newCount - currentCards
            };
        }
        
        return null;
    }
    
    logChanges(type, changeInfo) {
        console.clear();
        const pageName = document.title.split(' - ')[1] || 'Dashboard';
        console.log(`Page: ${pageName}`);
        this.logCurrentStatus();
        
        if (changeInfo.difference > 0) {
            const itemType = this.pageType === 'director' ? 'entr' : 'card';
            console.log(`${type}: ${changeInfo.difference} new ${itemType}${changeInfo.difference > 1 ? (this.pageType === 'director' ? 'ies' : 's') : (this.pageType === 'director' ? 'y' : '')} added`);
        } else if (changeInfo.difference < 0) {
            const itemType = this.pageType === 'director' ? 'entr' : 'card';
            console.log(`${type}: ${Math.abs(changeInfo.difference)} ${itemType}${Math.abs(changeInfo.difference) > 1 ? (this.pageType === 'director' ? 'ies' : 's') : (this.pageType === 'director' ? 'y' : '')} removed`);
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
                
                // Only check visible table data, ignore is_active status changes
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
            console.error('Location polling failed:', error);
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
            console.error('Announcements polling failed:', error);
            this.handlePollingError('announcements', error);
        }
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
                            console.clear();
                            const pageName = document.title.split(' - ')[1] || 'Dashboard';
                            console.log(`Page: ${pageName}`);
                            this.logCurrentStatus();
                            console.log(`${statKey}: ${currentValue} → ${newValue}`);
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
                location.reload();
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
        
        // Use dynamic observable elements
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
        
        // Log all found elements
        elements.forEach(element => console.log(element));
    }
    
    startHeartbeat() {
        // Send heartbeat every 2 minutes to maintain online status
        this.heartbeatInterval = setInterval(() => {
            if (this.isOnline && !document.hidden) {
                this.sendHeartbeat();
            }
        }, 120000); // 2 minutes
        
        // Send initial heartbeat
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
                console.warn('Heartbeat failed:', data.message);
            }
        } catch (error) {
            console.error('Heartbeat error:', error);
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
        
        // Update existing rows
        data.updates?.forEach(update => {
            let row = null;
            
            // Find row by data attribute (faculty-id, class-id, or announcement-id)
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
        
        // Only log if there were actual changes
        if (actualChanges > 0) {
            console.clear();
            const pageName = document.title.split(' - ')[1] || 'Dashboard';
            console.log(`Page: ${pageName}`);
            this.logCurrentStatus();
            console.log(`${this.currentTab}: ${actualChanges} actual change${actualChanges > 1 ? 's' : ''}`);
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
                
                // Update hidden expansion row details
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
        
        // Only highlight if there was an actual change
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
        
        // Limit queue size
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
        // Exponential backoff for errors
        const currentInterval = this.defaultIntervals[type];
        const newInterval = Math.min(currentInterval * 2, 30000); // Max 30 seconds
        
        console.warn(`Polling error for ${type}, backing off to ${newInterval}ms`);
        
        // Reset to normal interval after 5 minutes
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