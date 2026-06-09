(function () {
    const body = document.body;

    const navScroll = document.querySelector('[data-nav-scroll]');
    const navPrev = document.querySelector('[data-nav-prev]');
    const navNext = document.querySelector('[data-nav-next]');

    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const mobileMenuOpen = document.querySelector('[data-mobile-menu-open]');
    const mobileMenuCloseItems = document.querySelectorAll('[data-mobile-menu-close], [data-sidebar-close]');

    const userMenu = document.querySelector('.user-menu');

    const newsletterModal = document.getElementById('newsletterModal');
    const newsletterFrame = document.getElementById('newsletterFrame');
    const newsletterTabs = document.getElementById('newsletterTabs');
    const newsletterOpenDrive = document.getElementById('newsletterOpenDrive');
    const newsletterOpenButtons = document.querySelectorAll('[data-newsletter-open]');
    const newsletterCloseButtons = document.querySelectorAll('[data-newsletter-close]');

    const newsletterCurrentTitle = document.getElementById('newsletterCurrentTitle');
    const newsletterCurrentPill = document.getElementById('newsletterCurrentPill');
    const newsletterMonthTag = document.getElementById('newsletterMonthTag');
    const newsletterTitle = document.getElementById('newsletterTitle');
    const newsletterSubtitle = document.getElementById('newsletterSubtitle');

    const aboutModal = document.getElementById('aboutModal');
    const aboutOpenButtons = document.querySelectorAll('[data-about-open]');
    const aboutCloseButtons = document.querySelectorAll('[data-about-close]');

    const certTrack = document.querySelector('[data-cert-track]');
    const certPrevButtons = document.querySelectorAll('[data-cert-prev]');
    const certNextButtons = document.querySelectorAll('[data-cert-next]');
    const certSlides = document.querySelectorAll('.cert-slide');

    const NEWSLETTER_CATALOG = {
        default: [
            {
                title: 'Boletín principal',
                driveId: '1sJVccrYWGKlNl2MPrMznfvwASeYZG5Kc',
            },
        ],
    };

    const monthNames = [
        'Enero',
        'Febrero',
        'Marzo',
        'Abril',
        'Mayo',
        'Junio',
        'Julio',
        'Agosto',
        'Septiembre',
        'Octubre',
        'Noviembre',
        'Diciembre',
    ];

    const now = new Date();
    const currentMonthName = monthNames[now.getMonth()];
    const currentYear = now.getFullYear();
    const currentKey = `${currentYear}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    const currentNewsletterSet = NEWSLETTER_CATALOG[currentKey] || NEWSLETTER_CATALOG.default;

    let activeNewsletterIndex = 0;
    let activeCertIndex = 0;

    function isMobileLayout() {
        return window.matchMedia('(max-width: 1080px)').matches;
    }

    function closeUserMenu() {
        if (userMenu) {
            userMenu.removeAttribute('open');
        }
    }

    function updateNavButtons() {
        if (!navScroll || !navPrev || !navNext) {
            return;
        }

        const maxScroll = navScroll.scrollWidth - navScroll.clientWidth;
        const currentScroll = navScroll.scrollLeft;

        navPrev.disabled = currentScroll <= 2;
        navNext.disabled = currentScroll >= maxScroll - 2;
    }

    function moveNav(direction) {
        if (!navScroll) {
            return;
        }

        const distance = Math.max(180, Math.floor(navScroll.clientWidth * 0.7));

        navScroll.scrollBy({
            left: direction * distance,
            behavior: 'smooth',
        });
    }

    function openMobileMenu() {
        body.classList.add('mobile-menu-open');

        if (mobileMenuOpen) {
            mobileMenuOpen.setAttribute('aria-expanded', 'true');
        }

        closeUserMenu();
    }

    function closeMobileMenu() {
        body.classList.remove('mobile-menu-open');

        if (mobileMenuOpen) {
            mobileMenuOpen.setAttribute('aria-expanded', 'false');
        }
    }

    function toggleSidebar() {
        if (isMobileLayout()) {
            closeMobileMenu();
            return;
        }

        body.classList.toggle('sidebar-collapsed');
    }

    function getPreviewUrl(driveId) {
        return `https://drive.google.com/file/d/${driveId}/preview`;
    }

    function getDriveUrl(driveId) {
        return `https://drive.google.com/file/d/${driveId}/view`;
    }

    function updateNewsletterLabels() {
        if (newsletterCurrentTitle) {
            newsletterCurrentTitle.textContent = `Newsletter de ${currentMonthName} ${currentYear}`;
        }

        if (newsletterCurrentPill) {
            newsletterCurrentPill.textContent = `${currentMonthName} ${currentYear}`;
        }

        if (newsletterMonthTag) {
            newsletterMonthTag.textContent = `${currentMonthName} ${currentYear}`;
        }

        if (newsletterTitle) {
            newsletterTitle.textContent = `Innovación al Día · ${currentMonthName} ${currentYear}`;
        }

        if (newsletterSubtitle) {
            newsletterSubtitle.textContent = `Consulta el comunicado interno correspondiente a ${currentMonthName.toLowerCase()} ${currentYear}.`;
        }
    }

    function renderNewsletterTabs() {
        if (!newsletterTabs) {
            return;
        }

        newsletterTabs.innerHTML = '';

        currentNewsletterSet.forEach(function (item, index) {
            const button = document.createElement('button');

            button.type = 'button';
            button.className = index === activeNewsletterIndex ? 'newsletter-tab is-active' : 'newsletter-tab';
            button.textContent = item.title;

            button.addEventListener('click', function () {
                activeNewsletterIndex = index;
                updateNewsletter();
            });

            newsletterTabs.appendChild(button);
        });
    }

    function updateNewsletter() {
        const item = currentNewsletterSet[activeNewsletterIndex];

        if (!item) {
            return;
        }

        if (newsletterFrame) {
            newsletterFrame.src = getPreviewUrl(item.driveId);
        }

        if (newsletterOpenDrive) {
            newsletterOpenDrive.href = getDriveUrl(item.driveId);
        }

        if (newsletterTabs) {
            newsletterTabs.querySelectorAll('.newsletter-tab').forEach(function (button, index) {
                button.classList.toggle('is-active', index === activeNewsletterIndex);
            });
        }
    }

    function openNewsletter() {
        if (!newsletterModal) {
            return;
        }

        closeUserMenu();
        updateNewsletterLabels();
        renderNewsletterTabs();
        updateNewsletter();

        newsletterModal.classList.add('is-open');
        newsletterModal.setAttribute('aria-hidden', 'false');
        body.style.overflow = 'hidden';
    }

    function closeNewsletter() {
        if (!newsletterModal) {
            return;
        }

        newsletterModal.classList.remove('is-open');
        newsletterModal.setAttribute('aria-hidden', 'true');

        if (!aboutModal || !aboutModal.classList.contains('is-open')) {
            body.style.overflow = '';
        }
    }

    function openAbout() {
        if (!aboutModal) {
            return;
        }

        closeUserMenu();

        aboutModal.classList.add('is-open');
        aboutModal.setAttribute('aria-hidden', 'false');
        body.style.overflow = 'hidden';
    }

    function closeAbout() {
        if (!aboutModal) {
            return;
        }

        aboutModal.classList.remove('is-open');
        aboutModal.setAttribute('aria-hidden', 'true');

        if (!newsletterModal || !newsletterModal.classList.contains('is-open')) {
            body.style.overflow = '';
        }
    }

    function updateCertSlider() {
        if (!certTrack || certSlides.length === 0) {
            return;
        }

        certTrack.style.transform = `translateX(-${activeCertIndex * 100}%)`;
    }

    function moveCert(direction) {
        if (certSlides.length === 0) {
            return;
        }

        activeCertIndex += direction;

        if (activeCertIndex < 0) {
            activeCertIndex = certSlides.length - 1;
        }

        if (activeCertIndex >= certSlides.length) {
            activeCertIndex = 0;
        }

        updateCertSlider();
    }

    if (navPrev) {
        navPrev.addEventListener('click', function () {
            moveNav(-1);
        });
    }

    if (navNext) {
        navNext.addEventListener('click', function () {
            moveNav(1);
        });
    }

    if (navScroll) {
        navScroll.addEventListener('scroll', updateNavButtons);
        window.addEventListener('resize', updateNavButtons);
        updateNavButtons();
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    if (mobileMenuOpen) {
        mobileMenuOpen.addEventListener('click', openMobileMenu);
    }

    mobileMenuCloseItems.forEach(function (item) {
        item.addEventListener('click', closeMobileMenu);
    });

    document.querySelectorAll('.sidebar a, .nav a').forEach(function (link) {
        link.addEventListener('click', function () {
            closeMobileMenu();
            closeUserMenu();
        });
    });

    newsletterOpenButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            openNewsletter();
        });
    });

    newsletterCloseButtons.forEach(function (button) {
        button.addEventListener('click', closeNewsletter);
    });

    aboutOpenButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            openAbout();
        });
    });

    aboutCloseButtons.forEach(function (button) {
        button.addEventListener('click', closeAbout);
    });

    certPrevButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            moveCert(-1);
        });
    });

    certNextButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            moveCert(1);
        });
    });

    document.addEventListener('click', function (event) {
        if (!userMenu) {
            return;
        }

        if (!userMenu.contains(event.target)) {
            closeUserMenu();
        }
    });

    window.addEventListener('resize', function () {
        if (!isMobileLayout()) {
            closeMobileMenu();
        }

        updateNavButtons();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        closeMobileMenu();
        closeNewsletter();
        closeAbout();
        closeUserMenu();
    });

    updateNewsletterLabels();
    updateCertSlider();
})();

(() => {
    const ready = (callback) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    };

    ready(() => {
        const body = document.body;
        const sidebar = document.querySelector('.sidebar');
        const backdrop = document.querySelector('.sidebar-backdrop');
        const mobileButtons = document.querySelectorAll('.mobile-menu-btn, [data-mobile-menu], [data-sidebar-open], #mobileMenuButton');
        const collapseButtons = document.querySelectorAll('.sidebar__toggle, [data-sidebar-collapse], #sidebarToggle');

        const isMobile = () => window.matchMedia('(max-width: 1080px)').matches;

        const openSidebar = () => {
            body.classList.add('mobile-menu-open');
            body.classList.remove('sidebar-collapsed');
        };

        const closeSidebar = () => {
            body.classList.remove('mobile-menu-open');
        };

        const toggleSidebar = () => {
            if (isMobile()) {
                if (body.classList.contains('mobile-menu-open')) {
                    closeSidebar();
                    return;
                }

                openSidebar();
                return;
            }

            body.classList.toggle('sidebar-collapsed');
        };

        mobileButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopImmediatePropagation();

                if (body.classList.contains('mobile-menu-open')) {
                    closeSidebar();
                    return;
                }

                openSidebar();
            }, true);
        });

        collapseButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopImmediatePropagation();
                toggleSidebar();
            }, true);
        });

        if (backdrop) {
            backdrop.addEventListener('click', closeSidebar);
        }

        if (sidebar) {
            sidebar.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => {
                    if (isMobile()) {
                        closeSidebar();
                    }
                });
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        window.addEventListener('resize', () => {
            if (!isMobile()) {
                closeSidebar();
            }
        });

        const nav = document.querySelector('.nav');
        const arrows = Array.from(document.querySelectorAll('.nav-arrow'));

        if (nav && arrows.length >= 2) {
            const leftArrow = document.querySelector('.nav-arrow--left') || arrows[0];
            const rightArrow = document.querySelector('.nav-arrow--right') || arrows[arrows.length - 1];

            const updateArrows = () => {
                const maxScroll = nav.scrollWidth - nav.clientWidth;

                leftArrow.disabled = nav.scrollLeft <= 2;
                rightArrow.disabled = nav.scrollLeft >= maxScroll - 2;
            };

            leftArrow.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopImmediatePropagation();

                nav.scrollBy({
                    left: -Math.max(180, nav.clientWidth * 0.65),
                    behavior: 'smooth'
                });
            }, true);

            rightArrow.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopImmediatePropagation();

                nav.scrollBy({
                    left: Math.max(180, nav.clientWidth * 0.65),
                    behavior: 'smooth'
                });
            }, true);

            nav.addEventListener('scroll', updateArrows);
            window.addEventListener('resize', updateArrows);

            setTimeout(updateArrows, 100);
        }
    });
})();