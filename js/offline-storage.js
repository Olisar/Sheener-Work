/* File: sheener/js/offline-storage.js */
/**
 * Offline Storage Manager using IndexedDB
 * Stores events and attachments locally when offline
 */

class OfflineStorage {
    constructor() {
        this.dbName = 'SHEEnerReporter';
        this.dbVersion = 1;
        this.storeName = 'pendingEvents';
        this.db = null;
    }

    /**
     * Initialize IndexedDB database
     */
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onerror = () => {
                console.error('IndexedDB error:', request.error);
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                console.log('IndexedDB initialized');
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Create object store if it doesn't exist
                if (!db.objectStoreNames.contains(this.storeName)) {
                    const objectStore = db.createObjectStore(this.storeName, {
                        keyPath: 'id',
                        autoIncrement: false
                    });

                    // Create indexes
                    objectStore.createIndex('timestamp', 'timestamp', { unique: false });
                    objectStore.createIndex('syncStatus', 'syncStatus', { unique: false });
                    objectStore.createIndex('retryCount', 'retryCount', { unique: false });

                    console.log('IndexedDB object store created');
                }
            };
        });
    }

    /**
     * Generate unique ID for event
     */
    generateId() {
        return 'event_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Convert File/Blob to ArrayBuffer for storage
     */
    async fileToArrayBuffer(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsArrayBuffer(file);
        });
    }

    /**
     * Convert ArrayBuffer back to Blob
     */
    arrayBufferToBlob(arrayBuffer, mimeType) {
        return new Blob([arrayBuffer], { type: mimeType });
    }

    /**
     * Save event with attachments to IndexedDB
     */
    async saveEvent(formData, attachments = []) {
        if (!this.db) {
            await this.init();
        }

        const eventId = this.generateId();
        const timestamp = new Date().toISOString();

        // Extract form data
        const eventData = {
            id: eventId,
            timestamp: timestamp,
            formData: {
                reporter_name: formData.get('reporter_name') || '',
                reporter_email: formData.get('reporter_email') || '',
                eventDate: formData.get('eventDate') || '',
                location: formData.get('location') || '',
                location_id: formData.get('location_id') || '',
                gps_coordinates: formData.get('gps_coordinates') || '',
                primaryCategory: formData.get('primaryCategory') || '',
                secondaryCategory: formData.get('secondaryCategory') || '',
                description: formData.get('description') || '',
                anonymous: formData.get('anonymous') || '1',
                send_emails: formData.get('send_emails') || '0'
            },
            attachments: [],
            attachmentMetadata: [],
            syncStatus: 'pending',
            retryCount: 0,
            lastSyncAttempt: null
        };

        // Process attachments with error handling and size limits
        const maxFileSize = 5 * 1024 * 1024; // 5MB per file
        const maxTotalSize = 50 * 1024 * 1024; // 50MB total
        
        let totalSize = 0;
        for (let i = 0; i < attachments.length; i++) {
            const file = attachments[i];
            
            // Check individual file size
            if (file.size > maxFileSize) {
                console.warn(`File ${file.name} exceeds size limit (${(file.size / 1024 / 1024).toFixed(2)}MB > 5MB), skipping`);
                continue;
            }
            
            // Check total size
            if (totalSize + file.size > maxTotalSize) {
                console.warn(`Total attachment size would exceed limit, skipping remaining files`);
                break;
            }
            
            try {
                const arrayBuffer = await this.fileToArrayBuffer(file);
                eventData.attachments.push({
                    name: file.name,
                    type: file.type,
                    size: file.size,
                    data: arrayBuffer
                });
                eventData.attachmentMetadata.push({
                    name: file.name,
                    type: file.type,
                    size: file.size
                });
                totalSize += file.size;
            } catch (error) {
                console.error('Error processing attachment:', error);
                // Continue with other attachments
            }
        }

        return new Promise((resolve, reject) => {
            try {
                const transaction = this.db.transaction([this.storeName], 'readwrite');
                const store = transaction.objectStore(this.storeName);

                const request = store.add(eventData);

                request.onsuccess = () => {
                    console.log('Event saved to IndexedDB:', eventId);
                    resolve(eventId);
                };

                request.onerror = () => {
                    const error = request.error;
                    console.error('Error saving event:', error);
                    
                    // Handle quota exceeded error
                    if (error.name === 'QuotaExceededError') {
                        reject(new Error('Storage quota exceeded. Please clear some data or use smaller attachments.'));
                    } else {
                        reject(error);
                    }
                };
            } catch (error) {
                console.error('Transaction error:', error);
                reject(error);
            }
        });
    }

    /**
     * Get all pending events
     */
    async getPendingEvents() {
        if (!this.db) {
            await this.init();
        }

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.storeName], 'readonly');
            const store = transaction.objectStore(this.storeName);
            const index = store.index('syncStatus');
            const request = index.getAll('pending');

            request.onsuccess = () => {
                const events = request.result.map(event => ({
                    ...event,
                    // Convert ArrayBuffer back to Blob for attachments
                    attachments: event.attachments.map(att => ({
                        ...att,
                        blob: this.arrayBufferToBlob(att.data, att.type)
                    }))
                }));
                resolve(events);
            };

            request.onerror = () => {
                console.error('Error getting pending events:', request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Get all events (pending, syncing, failed)
     */
    async getAllEvents() {
        if (!this.db) {
            await this.init();
        }

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.storeName], 'readonly');
            const store = transaction.objectStore(this.storeName);
            const request = store.getAll();

            request.onsuccess = () => {
                const events = request.result.map(event => ({
                    ...event,
                    attachments: event.attachments.map(att => ({
                        ...att,
                        blob: this.arrayBufferToBlob(att.data, att.type)
                    }))
                }));
                resolve(events);
            };

            request.onerror = () => {
                console.error('Error getting all events:', request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Update event sync status
     */
    async updateEventStatus(eventId, status, retryCount = null) {
        if (!this.db) {
            await this.init();
        }

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);
            const getRequest = store.get(eventId);

            getRequest.onsuccess = () => {
                const event = getRequest.result;
                if (!event) {
                    reject(new Error('Event not found'));
                    return;
                }

                event.syncStatus = status;
                event.lastSyncAttempt = new Date().toISOString();
                if (retryCount !== null) {
                    event.retryCount = retryCount;
                }

                const updateRequest = store.put(event);
                updateRequest.onsuccess = () => {
                    console.log('Event status updated:', eventId, status);
                    resolve();
                };
                updateRequest.onerror = () => {
                    console.error('Error updating event status:', updateRequest.error);
                    reject(updateRequest.error);
                };
            };

            getRequest.onerror = () => {
                console.error('Error getting event:', getRequest.error);
                reject(getRequest.error);
            };
        });
    }

    /**
     * Delete event after successful sync
     */
    async deleteEvent(eventId) {
        if (!this.db) {
            await this.init();
        }

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);
            const request = store.delete(eventId);

            request.onsuccess = () => {
                console.log('Event deleted:', eventId);
                resolve();
            };

            request.onerror = () => {
                console.error('Error deleting event:', request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Get count of pending events
     */
    async getPendingCount() {
        const events = await this.getPendingEvents();
        return events.length;
    }

    /**
     * Clear all events (use with caution)
     */
    async clearAll() {
        if (!this.db) {
            await this.init();
        }

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);
            const request = store.clear();

            request.onsuccess = () => {
                console.log('All events cleared');
                resolve();
            };

            request.onerror = () => {
                console.error('Error clearing events:', request.error);
                reject(request.error);
            };
        });
    }

    /**
     * Get storage usage estimate
     */
    async getStorageEstimate() {
        if (!navigator.storage || !navigator.storage.estimate) {
            return null;
        }

        try {
            const estimate = await navigator.storage.estimate();
            return {
                usage: estimate.usage,
                quota: estimate.quota,
                usagePercent: (estimate.usage / estimate.quota * 100).toFixed(2)
            };
        } catch (error) {
            console.error('Error getting storage estimate:', error);
            return null;
        }
    }
}

// Export singleton instance
window.offlineStorage = new OfflineStorage();
