document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.getElementById('mobile-nav-toggle');
    const sidebarToggle = document.getElementById('sidebar-toggle');

    // Mobile navigation toggle
    if (mobileToggle && sidebar) {
        const toggleMobileNav = () => {
            const isOpen = sidebar.classList.contains('mobile-open');

            if (isOpen) {
                sidebar.classList.remove('mobile-open');
                body.classList.remove('overflow-hidden');
            } else {
                sidebar.classList.add('mobile-open');
                body.classList.add('overflow-hidden');
            }
        };

        mobileToggle.addEventListener('click', toggleMobileNav);

        // Close sidebar when clicking on links (mobile only)
        sidebar.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    toggleMobileNav();
                }
            });
        });

        // Close sidebar when clicking outside (mobile only)
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768 && 
                sidebar.classList.contains('mobile-open') && 
                !sidebar.contains(e.target) && 
                !mobileToggle.contains(e.target)) {
                toggleMobileNav();
            }
        });
    }

    // Desktop sidebar toggle (collapse/expand)
    if (sidebarToggle && sidebar) {
        // Check localStorage for saved state (desktop only)
        if (window.innerWidth >= 768) {
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState === 'true') {
                sidebar.classList.add('collapsed');
            }
        }

        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            
            // Only allow collapse on desktop
            if (window.innerWidth >= 768) {
                sidebar.classList.toggle('collapsed');
                
                // Save state to localStorage
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            }
        });
    }

    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            // Desktop view - remove mobile classes
            sidebar.classList.remove('mobile-open');
            body.classList.remove('overflow-hidden');
            
            // Restore collapsed state from localStorage
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState === 'true') {
                sidebar.classList.add('collapsed');
            }
        } else {
            // Mobile view - remove collapsed state
            sidebar.classList.remove('collapsed');
        }
    });
});

