const SIDEBAR_PREF_KEY = 'sidebarCollapsed';
const SIDEBAR_COOKIE_NAME = 'sidebarCollapsed';
const SIDEBAR_COOKIE_MAX_AGE = 60 * 60 * 24 * 365;
const DESKTOP_BREAKPOINT = 768;

function readCookie(name) {
    const escapedName = name.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
    const pattern = new RegExp(`(?:^|;\\s*)${escapedName}=([^;]*)`);
    const match = document.cookie.match(pattern);
    return match ? decodeURIComponent(match[1]) : null;
}

function readSidebarPreference() {
    let stored = null;

    if (typeof Storage !== 'undefined') {
        try {
            const localValue = localStorage.getItem(SIDEBAR_PREF_KEY);
            if (localValue === 'true' || localValue === 'false') {
                stored = localValue === 'true';
            }
        } catch (err) {
        }
    }

    if (stored === null) {
        const cookieValue = readCookie(SIDEBAR_COOKIE_NAME);
        if (cookieValue === 'true' || cookieValue === 'false') {
            stored = cookieValue === 'true';
        }
    }

    return stored;
}

function persistSidebarPreference(isCollapsed) {
    const value = isCollapsed ? 'true' : 'false';

    if (typeof Storage !== 'undefined') {
        try {
            localStorage.setItem(SIDEBAR_PREF_KEY, value);
        } catch (err) {
        }
    }

    const cookieDirectives = [
        `${SIDEBAR_COOKIE_NAME}=${value}`,
        'path=/',
        `max-age=${SIDEBAR_COOKIE_MAX_AGE}`,
        'samesite=lax'
    ];

    if (window.location && window.location.protocol === 'https:') {
        cookieDirectives.push('secure');
    }

    document.cookie = cookieDirectives.join(';');

    if (document.body) {
        if (isCollapsed) {
            document.body.setAttribute('data-sidebar-collapsed', 'true');
        } else {
            document.body.removeAttribute('data-sidebar-collapsed');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.getElementById('mobile-nav-toggle');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const savedPreference = readSidebarPreference();
    
    body.classList.add('loaded');

    if (sidebar && sidebar.hasAttribute('data-sidebar-init')) {
        requestAnimationFrame(() => {
            sidebar.removeAttribute('data-sidebar-init');
        });
    }

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

        mobileToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleMobileNav();
        });

        sidebar.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', (e) => {
                if (window.innerWidth < 768 && sidebar.classList.contains('mobile-open')) {
                    toggleMobileNav();
                }
            });
        });

        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768 && 
                sidebar.classList.contains('mobile-open') && 
                !sidebar.contains(e.target) && 
                !mobileToggle.contains(e.target)) {
                toggleMobileNav();
            }
        });
    }

    if (sidebarToggle && sidebar) {
        if (window.innerWidth >= DESKTOP_BREAKPOINT && savedPreference !== null) {
            sidebar.classList.toggle('collapsed', savedPreference);
            persistSidebarPreference(savedPreference);
        }

        sidebarToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (window.innerWidth >= DESKTOP_BREAKPOINT) {
                sidebar.classList.toggle('collapsed');
                
                const isCollapsed = sidebar.classList.contains('collapsed');
                persistSidebarPreference(isCollapsed);
            }
        });
    } else if (savedPreference !== null && window.innerWidth >= DESKTOP_BREAKPOINT && sidebar) {
        sidebar.classList.toggle('collapsed', savedPreference);
        persistSidebarPreference(savedPreference);
    } else if (savedPreference !== null) {
        persistSidebarPreference(savedPreference);
    }

    window.addEventListener('resize', () => {
        if (window.innerWidth >= DESKTOP_BREAKPOINT) {
            if (sidebar) {
                sidebar.classList.remove('mobile-open');
            }
            body.classList.remove('overflow-hidden');
            
            const desktopPreference = readSidebarPreference();
            if (desktopPreference !== null && sidebar) {
                sidebar.classList.toggle('collapsed', desktopPreference);
                persistSidebarPreference(desktopPreference);
            }
        } else {
            if (sidebar) {
                sidebar.classList.remove('collapsed');
            }
        }
    });
});
