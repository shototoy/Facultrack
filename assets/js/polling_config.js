// Unified Polling Configuration
// BATCH 4: Centralized polling intervals and settings

class PollingConfig {
    constructor() {
        this.intervals = {
            statistics: 3000,    // 3 seconds
            location: 3000,      // 3 seconds 
            announcements: 5000, // 5 seconds
            schedules: 2000,     // 2 seconds (faster for real-time schedule updates)
            tables: 4000,        // 4 seconds
            courses: 5000,       // 5 seconds
            classes: 5000        // 5 seconds
        };
        
        this.retrySettings = {
            maxRetries: 3,
            baseDelay: 1000,     // 1 second base delay
            maxDelay: 10000,     // 10 second max delay
            backoffMultiplier: 2  // Exponential backoff
        };
        
        this.networkSettings = {
            timeout: 10000,      // 10 second timeout
            enableOfflineMode: true,
            enableBackgroundPolling: true
        };
    }
    
    // Get interval for specific polling type
    getInterval(type) {
        return this.intervals[type] || this.intervals.statistics;
    }
    
    // Update interval (for dynamic adjustment)
    setInterval(type, interval) {
        this.intervals[type] = Math.max(1000, Math.min(30000, interval)); // Between 1-30 seconds
    }
    
    // Get retry delay with exponential backoff
    getRetryDelay(attemptNumber) {
        const delay = this.retrySettings.baseDelay * Math.pow(this.retrySettings.backoffMultiplier, attemptNumber);
        return Math.min(delay, this.retrySettings.maxDelay);
    }
    
    // Check if should retry
    shouldRetry(attemptNumber) {
        return attemptNumber < this.retrySettings.maxRetries;
    }
}

// Export singleton instance
window.PollingConfig = window.PollingConfig || new PollingConfig();