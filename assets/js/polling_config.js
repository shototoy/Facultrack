
class PollingConfig {
    constructor() {
        this.intervals = {
            statistics: 3000,    
            location: 3000,      
            announcements: 5000, 
            schedules: 2000,     
            tables: 4000,        
            courses: 5000,       
            classes: 5000        
        };
        this.retrySettings = {
            maxRetries: 3,
            baseDelay: 1000,     
            maxDelay: 10000,     
            backoffMultiplier: 2  
        };
        this.networkSettings = {
            timeout: 10000,      
            enableOfflineMode: true,
            enableBackgroundPolling: true
        };
    }
    getInterval(type) {
        return this.intervals[type] || this.intervals.statistics;
    }
    setInterval(type, interval) {
        this.intervals[type] = Math.max(1000, Math.min(30000, interval)); 
    }
    getRetryDelay(attemptNumber) {
        const delay = this.retrySettings.baseDelay * Math.pow(this.retrySettings.backoffMultiplier, attemptNumber);
        return Math.min(delay, this.retrySettings.maxDelay);
    }
    shouldRetry(attemptNumber) {
        return attemptNumber < this.retrySettings.maxRetries;
    }
}
window.PollingConfig = window.PollingConfig || new PollingConfig();