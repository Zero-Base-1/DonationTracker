document.addEventListener('DOMContentLoaded', () => {
    const mobileToggle = document.getElementById('mobile-nav-toggle');
    const aside = document.querySelector('aside');
    const body = document.body;

    if (!mobileToggle || !aside) {
        return;
    }

    const toggleNav = () => {
        const isOpen = aside.classList.contains('fixed');

        if (isOpen) {
            aside.classList.add('hidden');
            aside.classList.remove('fixed', 'inset-0', 'z-40');
            body.classList.remove('overflow-hidden');
        } else {
            aside.classList.remove('hidden');
            aside.classList.add('fixed', 'inset-0', 'z-40');
            body.classList.add('overflow-hidden');
        }
    };

    mobileToggle.addEventListener('click', toggleNav);

    aside.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                toggleNav();
            }
        });
    });
});

