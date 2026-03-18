// modal.js - simple modal and sidebar toggle behaviors
(function () {
    'use strict';

    window.openModal = function (id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('open');
        document.body.classList.add('no-scroll');
    };

    window.closeModal = function (id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('open');
        document.body.classList.remove('no-scroll');
    };

    document.addEventListener('click', function (e) {
        const close = e.target.closest('[data-close]');
        if (close) {
            const modal = close.closest('.modal');
            if (modal) closeModal(modal.id);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.open').forEach(function (m) {
                closeModal(m.id);
            });
        }
    });

    // Click on overlay closes modal
    document.addEventListener('click', function (e) {
        if (e.target.classList && e.target.classList.contains('modal-overlay')) {
            const modal = e.target.closest('.modal');
            if (modal) closeModal(modal.id);
        }
    });

    // Sidebar toggle for responsive
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (toggle && sidebar) {
            toggle.addEventListener('click', function () {
                const isOpen = sidebar.classList.toggle('collapsed');
                // aria-expanded true when sidebar is visible (collapsed class opens on small screens)
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        }
    });
    
    // Fill modal forms from data attributes helper
    window.fillForm = function(modalId, values) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        Object.keys(values).forEach(function(k){
            const field = modal.querySelector('[name="'+k+'"]');
            if (field) field.value = values[k];
        });
    };
})();
