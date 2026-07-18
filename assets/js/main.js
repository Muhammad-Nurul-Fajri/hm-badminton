/**
 * FILE: assets/js/main.js
 * FUNGSI: Client-side logic — flash dismiss, form helpers
 */

document.addEventListener('DOMContentLoaded', function() {

    // ============================================
    // Auto-dismiss flash messages
    // ============================================
    const flash = document.querySelector('.flash-message');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'all 0.4s cubic-bezier(0.16, 1, 0.3, 1)';
            flash.style.opacity = '0';
            flash.style.transform = 'translateX(100%)';
            setTimeout(() => flash.remove(), 400);
        }, 4000);
    }

    // ============================================
    // Confirm before destructive actions
    // ============================================
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // ============================================
    // File input preview
    // ============================================
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                // Remove existing preview
                const existingPreview = this.parentElement.querySelector('.file-preview');
                if (existingPreview) existingPreview.remove();

                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('div');
                    preview.className = 'file-preview';
                    preview.style.cssText = 'margin-top:12px;';
                    preview.innerHTML = `
                        <img src="${e.target.result}" 
                             style="max-width:200px; max-height:200px; border-radius:10px; border:2px solid #e2e8f0;" 
                             alt="Preview">
                        <p style="margin-top:6px; font-size:0.85em; color:#718096;">${file.name} (${(file.size / 1024).toFixed(1)} KB)</p>
                    `;
                    input.parentElement.appendChild(preview);
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // ============================================
    // Mobile sidebar toggle
    // ============================================
    const sidebarToggle = document.querySelector('.sidebar-toggle-btn');
    const sidebarClose = document.querySelector('.sidebar-close-btn');
    const sidebar = document.getElementById('app-sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.add('active');
        });
    }

    if (sidebarClose && sidebar) {
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });
    }

    // Close sidebar on click outside
    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('active') && !sidebar.contains(e.target) && (!sidebarToggle || !sidebarToggle.contains(e.target))) {
            sidebar.classList.remove('active');
        }
    });
});
