<!-- JavaScript libraries -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/howler@2.2.3/dist/howler.min.js"></script>
<script src="assets/js/sw-manager.js"></script>
<?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'resident'): ?>
<script src="assets/js/pending-registration-notifications.js"></script>
<?php endif; ?>

<!-- Application scripts -->
<script>
// Server-side flag for audio unlock (do NOT rely on localStorage)
<?php if (!empty($_SESSION['pending_audio_unlocked']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'resident')): ?>
// Signal that this session previously performed a user gesture enabling audio.
window.PENDING_AUDIO_UNLOCKED = true;
<?php endif; ?>

(function(window, document, $){
    'use strict';

    // Helpers for safe storage access
    function safeGetStorage(key){ try { return localStorage.getItem(key) || sessionStorage.getItem(key); } catch(e){ return null; } }
    function safeSetStorage(key, value){ try { localStorage.setItem(key, value); sessionStorage.setItem(key, value); } catch(e){} }

    // Global config
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    $.ajaxSetup({ headers: { 'X-CSRF-Token': CSRF_TOKEN } });

    // Simple UI helpers
    const ui = {
        showLoading(){ document.getElementById('loadingSpinner')?.classList.remove('d-none'); },
        hideLoading(){ document.getElementById('loadingSpinner')?.classList.add('d-none'); }
    };

    // Document-ready initializers
    $(function(){
        // DataTables
        $('.data-table').each(function(){
            if (!$.fn.dataTable.isDataTable(this)) {
                $(this).DataTable({ responsive:true, pageLength:10, columnDefs:[{responsivePriority:1, targets:0},{responsivePriority:2, targets:-1}] });
            }
        });

        // Tooltips & popovers
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=> new bootstrap.Tooltip(el));
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el=> new bootstrap.Popover(el));

        // Auto-hide non-permanent alerts
        setTimeout(()=>{ document.querySelectorAll('.alert:not(.alert-permanent)').forEach(a=> new bootstrap.Alert(a).close()); }, 5000);

        // Request Notification permission (non-blocking)
        if ('Notification' in window && Notification.permission === 'default') { try { Notification.requestPermission(); } catch(e){} }

        <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'resident'): ?>
        // Initialize pending registration notifier (best-effort)
        try {
            if (window.PendingRegistrationNotifications) {
                if (typeof window !== 'undefined' && window.PENDING_AUDIO_UNLOCKED) {
                    try { PendingRegistrationNotifications.userInteracted = true; } catch(e){}
                    try { PendingRegistrationNotifications.enable(); } catch(e){}
                    PendingRegistrationNotifications.tryUnlock(false).catch(()=>{});
                }
            }
        } catch(e){ console.debug && console.debug('PendingRegistrationNotifications init failed', e); }
        <?php endif; ?>

        // Suppress notification sounds briefly on navigation clicks
        <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'resident'): ?>
        window.NotificationNavClickSuppressed = false;
        const suppressNavNotification = ()=>{ window.NotificationNavClickSuppressed = true; setTimeout(()=> window.NotificationNavClickSuppressed = false, 1200); };
        document.addEventListener('click', function(e){ const el = e.target.closest && e.target.closest('a'); if (!el) return; if (el.closest('.sidebar') || el.closest('.navbar') || el.classList.contains('nav-link') || el.classList.contains('nav-item') || el.id === 'sidebar' || el.classList.contains('sidebar-link')) suppressNavNotification(); }, true);

        // Central SSE for pending registrations
        if (!window.PendingRegistrationGlobalSSE && typeof EventSource === 'function') {
            window.PendingRegistrationGlobalSSE = true;
            (function(){
                let lastCount = parseInt(sessionStorage.getItem('pendingRegistrationsCount_global') || '0', 10) || 0;
                const es = new EventSource('ajax/pending-count-sse.php');
                es.addEventListener('message', async function(e){
                    try {
                        const newCount = parseInt(e.data, 10);
                        if (isNaN(newCount)) return;
                        if (newCount > lastCount) {
                            const diff = newCount - lastCount;
                            if (!window.NotificationNavClickSuppressed && window.PendingRegistrationNotifications) {
                                try {
                                    if (PendingRegistrationNotifications.userInteracted) {
                                        PendingRegistrationNotifications.playForNewRegistrations(newCount, lastCount);
                                    } else if (typeof PendingRegistrationNotifications.tryUnlock === 'function') {
                                        const unlocked = await PendingRegistrationNotifications.tryUnlock();
                                        if (unlocked) PendingRegistrationNotifications.playForNewRegistrations(newCount, lastCount);
                                        else PendingRegistrationNotifications.initAutoUnlock && PendingRegistrationNotifications.initAutoUnlock();
                                    }
                                } catch(err){ console.debug && console.debug('Play attempt failed', err); }
                            }
                            // simple toast-like indicator
                            try { const id='pending-notif-indicator'; document.getElementById(id)?.remove(); const el=document.createElement('div'); el.id=id; el.textContent=`🔔 New pending registrations: ${diff}`; Object.assign(el.style,{position:'fixed',right:'1rem',bottom:'1rem',background:'rgba(0,0,0,0.8)',color:'#fff',padding:'0.6rem 1rem',borderRadius:'0.4rem',zIndex:2147483647,boxShadow:'0 6px 18px rgba(0,0,0,0.2)',fontSize:'0.95rem'}); document.body.appendChild(el); setTimeout(()=>{ el.style.transition='opacity 0.5s'; el.style.opacity='0'; setTimeout(()=>el.remove(),600); },4000);}catch(e){}
                        }
                        lastCount = newCount; sessionStorage.setItem('pendingRegistrationsCount_global', String(newCount));
                    } catch(e){ console.debug && console.debug('SSE handler error', e); }
                });
                es.addEventListener('open', ()=> console.log('Pending registrations SSE connected'));
                es.addEventListener('error', ()=> console.debug && console.debug('Pending registrations SSE error'));
            })();
        }
        <?php endif; ?>
    }); // end ready

    // Lightweight utilities exported globally
    window.appUtils = {
        showLoading: ui.showLoading,
        hideLoading: ui.hideLoading,
        safeGetStorage, safeSetStorage,
        formatCurrency(amount){ return new Intl.NumberFormat('en-PH',{style:'currency',currency:'PHP'}).format(amount); },
        formatDate(dateString){ return moment(dateString).format('MMM DD, YYYY hh:mm A'); },
        timeAgo(dateString){ return moment(dateString).fromNow(); },
        showSuccess(message){ Swal.fire({ icon:'success', title:'Success!', text:message, timer:3000, showConfirmButton:false }); },
        showError(message){ Swal.fire({ icon:'error', title:'Error!', text:message }); },
        showLoadingToast(message='Processing...'){ Swal.fire({ title:message, allowOutsideClick:false, allowEscapeKey:false, showConfirmButton:false, didOpen:()=>Swal.showLoading() }); }
    };

    // Expose some DOM helpers
    window.handleFileUpload = function(inputElement, previewElement=null){
        if (!inputElement) return;
        inputElement.addEventListener('change', function(e){ const file = e.target.files && e.target.files[0]; if (!file) return; if (file.size > 5*1024*1024){ Swal.fire({icon:'error',title:'File Too Large',text:'File size must be less than 5MB'}); e.target.value=''; return; } const allowed=['image/jpeg','image/jpg','image/png','application/pdf']; if (!allowed.includes(file.type)){ Swal.fire({icon:'error',title:'Invalid File Type',text:'Only JPG, PNG, and PDF files are allowed'}); e.target.value=''; return; } if (previewElement && file.type.startsWith('image/')){ const r=new FileReader(); r.onload=ev=>{ previewElement.src=ev.target.result; previewElement.style.display='block'; }; r.readAsDataURL(file); } });
    };

    window.initDragAndDrop = function(dropZone, fileInput){ if (!dropZone || !fileInput) return; dropZone.addEventListener('dragover', e=>{ e.preventDefault(); dropZone.classList.add('dragover'); }); dropZone.addEventListener('dragleave', e=>{ e.preventDefault(); dropZone.classList.remove('dragover'); }); dropZone.addEventListener('drop', e=>{ e.preventDefault(); dropZone.classList.remove('dragover'); const files = e.dataTransfer.files; if (files.length>0){ fileInput.files = files; fileInput.dispatchEvent(new Event('change')); } }); dropZone.addEventListener('click', ()=> fileInput.click()); };

    window.validateForm = function(formId){ const form=document.getElementById(formId); if (!form) return false; let ok=true; form.querySelectorAll('[required]').forEach(f=>{ if (!f.value.trim()){ f.classList.add('is-invalid'); ok=false; } else { f.classList.remove('is-invalid'); } }); return ok; };

    window.formatPhoneNumber = function(input){ if (!input) return; let v=input.value.replace(/\D/g,''); if (v.startsWith('+63')) v='0'+v.substring(3); else if (v.startsWith('63')) v='0'+v.substring(2); else if (!v.startsWith('0') && v.length===10) v='0'+v; input.value = v; };

    // Print and CSV helpers kept compact
    window.printElement = function(elementId){ const el=document.getElementById(elementId); if (!el) return; const w=window.open('','_blank'); w.document.write(`<!doctype html><html><head><title>Print</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head><body>${el.innerHTML}</body></html>`); w.document.close(); w.print(); w.close(); };
    window.exportTableToCSV = function(tableId, filename='export.csv'){ const table=document.getElementById(tableId); if (!table) return; const rows = Array.from(table.querySelectorAll('tr')); const csv = rows.map(r=> Array.from(r.querySelectorAll('td,th')).map(c=> '"'+c.innerText.replace(/"/g,'""')+'"').join(',')).join('\n'); const blob=new Blob([csv],{type:'text/csv'}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download=filename; a.style.display='none'; document.body.appendChild(a); a.click(); a.remove(); };

    // Network status indicator
    function updateNetworkStatus(){ const i=document.getElementById('networkStatus'); if (!i) return; if (navigator.onLine){ i.innerHTML = '<i class="bi bi-wifi text-success"></i>'; i.title='Online'; } else { i.innerHTML = '<i class="bi bi-wifi-off text-danger"></i>'; i.title='Offline'; } }
    window.addEventListener('online', updateNetworkStatus); window.addEventListener('offline', updateNetworkStatus);

})(window, document, jQuery);
</script>
