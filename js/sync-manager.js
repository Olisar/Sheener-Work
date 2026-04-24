/* File: sheener/js/sync-manager.js */
/**
 * Sync Manager - Handles background sync of pending events
 */

class SyncManager {
    constructor() {
        this.isSyncing = false;
        this.syncListeners = [];
        this.maxRetries = 3;
        this.autoSyncInterval = null;
        this.onlineListenerAdded = false;
        this.visibilityListenerAdded = false;
    }

    /**
     * Check if device is online and connected to intranet
     */
    async isIntranetConnected() {
        // Check basic online status
        if (!navigator.onLine) {
            return false;
        }

        // Try to reach the server (intranet check)
        try {
            // Use AbortController for timeout (more compatible than AbortSignal.timeout)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
            
            const response = await fetch('php/submit_anonymous_event.php', {
                method: 'HEAD',
                cache: 'no-cache',
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            return response.ok || response.status < 500;
        } catch (error) {
            // Ignore abort errors (timeout) and other errors
            if (error.name !== 'AbortError') {
                console.log('Intranet connection check failed:', error.message);
            }
            return false;
        }
    }

    /**
     * Convert stored event back to FormData for submission
     */
    eventToFormData(event) {
        const formData = new FormData();

        // Add form fields
        Object.keys(event.formData).forEach(key => {
            if (event.formData[key]) {
                formData.append(key, event.formData[key]);
            }
        });

        // Add attachments
        event.attachments.forEach(attachment => {
            if (attachment.blob) {
                formData.append('attachments[]', attachment.blob, attachment.name);
            }
        });

        return formData;
    }

    /**
     * Sync a single event
     */
    async syncEvent(event) {
        try {
            // Update status to syncing
            await offlineStorage.updateEventStatus(event.id, 'syncing', event.retryCount);

            // Notify listeners
            this.notifyListeners('syncing', event);

            // Convert to FormData
            const formData = this.eventToFormData(event);

            // Submit to server with timeout
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

            let response;
            try {
                response = await fetch('php/submit_anonymous_event.php', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
            } catch (fetchError) {
                clearTimeout(timeoutId);
                if (fetchError.name === 'AbortError') {
                    throw new Error('Request timeout - server did not respond in time');
                }
                throw fetchError;
            }

            // Check response status
            if (!response.ok) {
                throw new Error(`Server error: ${response.status} ${response.statusText}`);
            }

            const result = await response.json();

            if (result.success) {
                // Success - delete from storage
                await offlineStorage.deleteEvent(event.id);
                this.notifyListeners('synced', event);
                console.log('Event synced successfully:', event.id);
                return true;
            } else {
                // Server error - mark as failed
                const newRetryCount = event.retryCount + 1;
                if (newRetryCount >= this.maxRetries) {
                    await offlineStorage.updateEventStatus(event.id, 'failed', newRetryCount);
                    this.notifyListeners('failed', event);
                    console.error('Event sync failed after max retries:', event.id, result.error);
                } else {
                    await offlineStorage.updateEventStatus(event.id, 'pending', newRetryCount);
                    this.notifyListeners('retry', event);
                    console.log('Event sync failed, will retry:', event.id, result.error);
                }
                return false;
            }
        } catch (error) {
            console.error('Error syncing event:', error);
            const newRetryCount = event.retryCount + 1;
            if (newRetryCount >= this.maxRetries) {
                await offlineStorage.updateEventStatus(event.id, 'failed', newRetryCount);
                this.notifyListeners('failed', event);
            } else {
                await offlineStorage.updateEventStatus(event.id, 'pending', newRetryCount);
                this.notifyListeners('retry', event);
            }
            return false;
        }
    }

    /**
     * Calculate exponential backoff delay based on retry count
     */
    getRetryDelay(retryCount) {
        // Exponential backoff: 2^retryCount seconds, max 60 seconds
        const baseDelay = Math.min(Math.pow(2, retryCount) * 1000, 60000);
        // Add some jitter to avoid thundering herd
        const jitter = Math.random() * 1000;
        return baseDelay + jitter;
    }

    /**
     * Sync all pending events
     */
    async syncAll() {
        if (this.isSyncing) {
            console.log('Sync already in progress');
            return;
        }

        // Check connection
        const isConnected = await this.isIntranetConnected();
        if (!isConnected) {
            console.log('Not connected to intranet, skipping sync');
            this.notifyListeners('offline', null);
            return;
        }

        this.isSyncing = true;
        this.notifyListeners('start', null);

        try {
            // Get all pending events, sorted by timestamp (oldest first)
            const events = await offlineStorage.getPendingEvents();
            
            // Sort by timestamp to process oldest first
            events.sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));

            if (events.length === 0) {
                console.log('No pending events to sync');
                this.notifyListeners('complete', { synced: 0, failed: 0 });
                return;
            }

            console.log(`Syncing ${events.length} pending events...`);

            let syncedCount = 0;
            let failedCount = 0;

            // Sync events one by one (to avoid overwhelming the server)
            for (const event of events) {
                // Check if event needs retry delay (exponential backoff)
                if (event.retryCount > 0 && event.lastSyncAttempt) {
                    const timeSinceLastAttempt = Date.now() - new Date(event.lastSyncAttempt).getTime();
                    const requiredDelay = this.getRetryDelay(event.retryCount - 1);
                    
                    if (timeSinceLastAttempt < requiredDelay) {
                        // Not enough time has passed, skip this event for now
                        console.log(`Skipping event ${event.id} - waiting for retry delay`);
                        continue;
                    }
                }
                
                const success = await this.syncEvent(event);
                if (success) {
                    syncedCount++;
                } else {
                    failedCount++;
                }

                // Small delay between syncs to avoid overwhelming server
                await new Promise(resolve => setTimeout(resolve, 500));
            }

            console.log(`Sync complete: ${syncedCount} synced, ${failedCount} failed`);
            this.notifyListeners('complete', { synced: syncedCount, failed: failedCount });

        } catch (error) {
            console.error('Error during sync:', error);
            this.notifyListeners('error', error);
        } finally {
            this.isSyncing = false;
        }
    }

    /**
     * Register sync status listener
     */
    onSyncStatus(callback) {
        this.syncListeners.push(callback);
    }

    /**
     * Notify all listeners of sync status
     */
    notifyListeners(status, data) {
        this.syncListeners.forEach(callback => {
            try {
                callback(status, data);
            } catch (error) {
                console.error('Error in sync listener:', error);
            }
        });
    }

    /**
     * Start automatic sync monitoring
     * DISABLED by default to prevent continuous checking loops
     * Sync will happen on form submission or can be triggered manually
     */
    startAutoSync(interval = 30000) {
        // DISABLED: Auto-sync causes continuous checking loops
        // Sync will happen automatically when:
        // 1. User submits a form (checks connection and syncs)
        // 2. User manually triggers sync
        console.log('Auto-sync disabled to prevent continuous checking. Sync happens on form submission.');
        return;
        
        // OLD CODE (disabled):
        // // Don't start multiple intervals
        // if (this.autoSyncInterval) {
        //     console.log('Auto-sync already started');
        //     return;
        // }
        // 
        // // Initial sync check (delayed to prevent blocking)
        // setTimeout(() => {
        //     if (!this.isSyncing) {
        //         this.syncAll();
        //     }
        // }, 5000);
        // 
        // // Periodic sync check
        // this.autoSyncInterval = setInterval(() => {
        //     if (!this.isSyncing) {
        //         this.syncAll();
        //     }
        // }, interval);
        // 
        // // Sync on online event (only once)
        // if (!this.onlineListenerAdded) {
        //     window.addEventListener('online', () => {
        //         console.log('Device came online, triggering sync...');
        //         setTimeout(() => {
        //             if (!this.isSyncing) {
        //                 this.syncAll();
        //             }
        //         }, 3000);
        //     });
        //     this.onlineListenerAdded = true;
        // }
        // 
        // // Sync on visibility change (when user returns to app)
        // if (!this.visibilityListenerAdded) {
        //     document.addEventListener('visibilitychange', () => {
        //         if (!document.hidden && !this.isSyncing) {
        //             console.log('App became visible, checking for sync...');
        //             setTimeout(() => {
        //                 if (!this.isSyncing) {
        //                     this.syncAll();
        //                 }
        //             }, 2000);
        //         }
        //     });
        //     this.visibilityListenerAdded = true;
        // }
    }
}

// Export singleton instance
window.syncManager = new SyncManager();
