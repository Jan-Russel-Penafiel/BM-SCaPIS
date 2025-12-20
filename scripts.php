<!-- JavaScript Libraries -->

<!-- Notification System - Loaded in header.php to prevent conflicts -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>



<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Moment.js for date formatting -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<!-- Howler.js for audio notifications -->
<script src="https://cdn.jsdelivr.net/npm/howler@2.2.3/dist/howler.min.js"></script>

<!-- Service Worker Manager for Auto-Updates -->
<script src="assets/js/sw-manager.js"></script>

<!-- Pending registration notifier (Howler + WebAudio fallback) -->
<script src="assets/js/pending-registration-notifications.js"></script>

<!-- Custom JavaScript -->
<script>
    // Global Configuration
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Global AJAX setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': CSRF_TOKEN
        }
    });
    
    // Loading spinner functions
    function showLoading() {
        document.getElementById('loadingSpinner')?.classList.remove('d-none');
    }
    
    function hideLoading() {
        document.getElementById('loadingSpinner')?.classList.add('d-none');
    }
    
    // Initialize components
    $(document).ready(function() {
        
        // Initialize DataTables with responsive design
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 10,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries per page",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            columnDefs: [
                { responsivePriority: 1, targets: 0 },
                { responsivePriority: 2, targets: -1 }
            ]
        });
    });
    
    // Form validation
    function validateForm(formId) {
        const form = document.getElementById(formId);
        if (!form) return false;
        
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    }
    
    // Real-time form validation
    document.addEventListener('input', function(e) {
        if (e.target.hasAttribute('required')) {
            if (e.target.value.trim()) {
                e.target.classList.remove('is-invalid');
                e.target.classList.add('is-valid');
            } else {
                e.target.classList.remove('is-valid');
                e.target.classList.add('is-invalid');
            }
        }
    });
    
    // File upload handling
    function handleFileUpload(inputElement, previewElement = null) {
        inputElement.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Validate file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Too Large',
                    text: 'File size must be less than 5MB'
                });
                e.target.value = '';
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File Type',
                    text: 'Only JPG, PNG, and PDF files are allowed'
                });
                e.target.value = '';
                return;
            }
            
            // Show preview for images
            if (previewElement && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewElement.src = e.target.result;
                    previewElement.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Drag and drop file upload
    function initDragAndDrop(dropZone, fileInput) {
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dropZone.classList.remove('dragover');
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
        
        dropZone.addEventListener('click', function() {
            fileInput.click();
        });
    }
    
    // Phone number formatting
    function formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        
        // Convert to 09XXXXXXXXX format
        if (value.startsWith('63')) {
            value = '0' + value.substring(2);
        } else if (value.startsWith('+63')) {
            value = '0' + value.substring(3);
        } else if (!value.startsWith('0') && value.length === 10) {
            value = '0' + value;
        }
        
        // Ensure it starts with 09 and has 11 digits
        if (value.length === 11 && value.startsWith('09')) {
            input.value = value;
        } else {
            input.value = value;
        }
    }
    
    // Real-time notifications - DISABLED: Using Howler.js only for pending registrations
    // Old notification system removed to prevent unwanted sounds on clicks and all views
    function playNotificationSound() {
        // Old notification system disabled - only Howler.js system works on pending registration pages
        // This function is kept for compatibility but does nothing
        return;
    }
    
    // Check for new notifications - this function is handled by header.php notification system
    function checkForNotifications() {
        // This function is deprecated - notification checking is handled by header.php
        console.log('checkForNotifications is deprecated - using header notification system');
    }
    
    function updateNotificationBadge(count) {
        // This function is deprecated - badge update is handled by header.php notification system
        console.log('updateNotificationBadge is deprecated - using header notification system');
    }
    
    // Request notification permission
    function requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Request notification permission
        requestNotificationPermission();

        // Start best-effort automatic audio unlock attempts for pending registration ringtone
        try {
            if (window.PendingRegistrationNotifications && typeof window.PendingRegistrationNotifications.initAutoUnlock === 'function') {
                window.PendingRegistrationNotifications.initAutoUnlock();
            }
        } catch (e) { console.debug('initAutoUnlock call failed', e); }
        
        // Don't auto-check for notifications - let header handle this
        // The header notification system is sufficient and controlled
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Suppress notification sound briefly when user clicks navigation links
        window.NotificationNavClickSuppressed = false;
        function suppressNavNotification() {
            window.NotificationNavClickSuppressed = true;
            setTimeout(() => { window.NotificationNavClickSuppressed = false; }, 1200);
        }
        // Listen for clicks on common nav selectors
        document.addEventListener('click', function(e) {
            const el = e.target.closest('a');
            if (!el) return;
            // If link is inside sidebar or navbar or has nav-related classes, treat as nav click
            if (el.closest('.sidebar') || el.closest('.navbar') || el.classList.contains('nav-link') || el.classList.contains('nav-item') || el.id === 'sidebar' || el.classList.contains('sidebar-link')) {
                suppressNavNotification();
            }
        }, true);

        // Centralized SSE for pending registration counts (site-wide)
        try {
            if (!window.PendingRegistrationGlobalSSE && typeof EventSource === 'function') {
                window.PendingRegistrationGlobalSSE = true;

                // Ensure the auto-unlock initializer is registered (does not create AudioContext)
                try {
                    if (window.PendingRegistrationNotifications && typeof window.PendingRegistrationNotifications.initAutoUnlock === 'function') {
                        window.PendingRegistrationNotifications.initAutoUnlock();
                    }
                } catch (e) { console.debug('initAutoUnlock in SSE failed', e); }

                (function(){
                    let lastGlobalCount = parseInt(sessionStorage.getItem('pendingRegistrationsCount_global') || '0', 10);
                    const es = new EventSource('ajax/pending-count-sse.php');
                    const showIndicator = function(diff){
                        try {
                            const id = 'pending-notif-indicator';
                            let el = document.getElementById(id);
                            if (el) el.remove();
                            el = document.createElement('div');
                            el.id = id;
                            el.textContent = `🔔 New pending registrations: ${diff}`;
                            Object.assign(el.style, {
                                position: 'fixed',
                                right: '1rem',
                                bottom: '1rem',
                                background: 'rgba(0,0,0,0.8)',
                                color: '#fff',
                                padding: '0.6rem 1rem',
                                borderRadius: '0.4rem',
                                zIndex: 2147483647,
                                boxShadow: '0 6px 18px rgba(0,0,0,0.2)',
                                fontSize: '0.95rem'
                            });
                            document.body.appendChild(el);
                            setTimeout(() => { el.style.transition = 'opacity 0.5s ease'; el.style.opacity = '0'; setTimeout(() => el.remove(), 600); }, 4000);
                        } catch (ie) { console.debug('indicator failed', ie); }
                    };

                    const showEnableSoundButton = function(){
                        try {
                            const id = 'enable-sound-button';
                            if (document.getElementById(id)) return;
                            const btn = document.createElement('button');
                            btn.id = id;
                            btn.textContent = 'Enable Notification Sound';
                            Object.assign(btn.style, {
                                position: 'fixed',
                                right: '1rem',
                                bottom: '4.5rem',
                                background: '#0d6efd',
                                color: '#fff',
                                padding: '0.5rem 0.9rem',
                                border: 'none',
                                borderRadius: '0.4rem',
                                zIndex: 2147483647,
                                cursor: 'pointer',
                                boxShadow: '0 6px 18px rgba(0,0,0,0.2)'
                            });
                            btn.addEventListener('click', function(){
                                if (window.PendingRegistrationNotifications && typeof window.PendingRegistrationNotifications.tryUnlock === 'function') {
                                    // This is a real user gesture — force creation/resume of AudioContext
                                    window.PendingRegistrationNotifications.tryUnlock(true).then(u => {
                                        if (u) {
                                            try { window.PendingRegistrationNotifications.playForNewRegistrations(parseInt(sessionStorage.getItem('pendingRegistrationsCount_global')||'0',10), 0); } catch(e){}
                                            btn.remove();
                                        } else {
                                            // keep the button so user can try again
                                            console.debug('User click unlock failed');
                                        }
                                    }).catch(()=>{});
                                }
                            });
                            document.body.appendChild(btn);
                        } catch (e) { console.debug('enable sound button failed', e); }
                    };

                    es.addEventListener('message', function(e){
                        try {
                            const newCount = parseInt(e.data, 10);
                            if (isNaN(lastGlobalCount)) lastGlobalCount = 0;
                            if (isNaN(newCount)) return;
                            if (newCount > lastGlobalCount) {
                                const diff = newCount - lastGlobalCount;
                                // attempt to play: if audio already unlocked, play immediately; otherwise try to unlock then play
                                if (!window.NotificationNavClickSuppressed && window.PendingRegistrationNotifications) {
                                    (async function(){
                                        try {
                                            if (window.PendingRegistrationNotifications.userInteracted) {
                                                window.PendingRegistrationNotifications.playForNewRegistrations(newCount, lastGlobalCount);
                                            } else if (typeof window.PendingRegistrationNotifications.tryUnlock === 'function') {
                                                const unlocked = await window.PendingRegistrationNotifications.tryUnlock();
                                                if (unlocked) {
                                                    window.PendingRegistrationNotifications.playForNewRegistrations(newCount, lastGlobalCount);
                                                } else {
                                                    showEnableSoundButton();
                                                }
                                            } else {
                                                showEnableSoundButton();
                                            }
                                        } catch (playErr) { console.debug('play error', playErr); }
                                    })();
                                }
                                showIndicator(diff);
                            }
                            lastGlobalCount = newCount;
                            sessionStorage.setItem('pendingRegistrationsCount_global', String(newCount));
                        } catch (err) { console.debug('SSE central handler error', err); }
                    });
                    es.addEventListener('open', function(){ console.log('Central SSE connected for pending registrations'); });
                    es.addEventListener('error', function(ev){ console.debug('Central SSE error', ev); });
                })();
            }
        } catch (e) { console.debug('Central SSE setup failed', e); }
    });
    
    // Utility functions
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    }
    
    function formatDate(dateString) {
        return moment(dateString).format('MMM DD, YYYY hh:mm A');
    }
    
    function timeAgo(dateString) {
        return moment(dateString).fromNow();
    }
    
    // Confirmation dialogs
    function confirmAction(message, callback) {
        Swal.fire({
            title: 'Are you sure?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed && callback) {
                callback();
            }
        });
    }
    
    // Success message
    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    }
    
    // Error message
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: message
        });
    }
    
    // Loading toast
    function showLoadingToast(message = 'Processing...') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
    
    // Print functionality
    function printElement(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Print</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { font-family: Arial, sans-serif; }
                    .no-print { display: none !important; }
                    @media print {
                        .page-break { page-break-before: always; }
                    }
                </style>
            </head>
            <body>
                ${element.innerHTML}
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
        printWindow.close();
    }
    
    // Export to CSV
    function exportTableToCSV(tableId, filename = 'export.csv') {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [];
            const cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                let cellText = cols[j].innerText.replace(/"/g, '""');
                row.push('"' + cellText + '"');
            }
            
            csv.push(row.join(','));
        }
        
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', filename);
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    
    // Form auto-save (for drafts)
    function autoSaveForm(formId, storageKey) {
        const form = document.getElementById(formId);
        if (!form) return;
        
        // Load saved data
        const savedData = localStorage.getItem(storageKey);
        if (savedData) {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field && field.type !== 'file') {
                    field.value = data[key];
                }
            });
        }
        
        // Save on input
        form.addEventListener('input', function() {
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            localStorage.setItem(storageKey, JSON.stringify(data));
        });
        
        // Clear saved data on successful submit
        form.addEventListener('submit', function() {
            localStorage.removeItem(storageKey);
        });
    }
    
    // Network status indicator
    function updateNetworkStatus() {
        const indicator = document.getElementById('networkStatus');
        if (!indicator) return;
        
        if (navigator.onLine) {
            indicator.innerHTML = '<i class="bi bi-wifi text-success"></i>';
            indicator.title = 'Online';
        } else {
            indicator.innerHTML = '<i class="bi bi-wifi-off text-danger"></i>';
            indicator.title = 'Offline';
        }
    }
    
    // Listen for network status changes
    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);
</script>
