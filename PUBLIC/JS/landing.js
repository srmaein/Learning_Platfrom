/**
 * LANDING PAGE INTERACTIVE JAVASCRIPT
 * Features: Smooth Navigation, Glass Modal, Course Filter, Animated Stats Counter
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sticky Navbar on Scroll
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 40) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // 2. Mobile Menu Toggle
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (mobileToggle && navMenu) {
        mobileToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = mobileToggle.querySelector('i');
            if (icon) {
                if (navMenu.classList.contains('active')) {
                    icon.className = 'bx bx-x';
                } else {
                    icon.className = 'bx bx-menu';
                }
            }
        });

        // Close menu on nav link click
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                if (mobileToggle.querySelector('i')) {
                    mobileToggle.querySelector('i').className = 'bx bx-menu';
                }
            });
        });
    }

    // 3. Course Filter Tabs
    const tabBtns = document.querySelectorAll('.tab-btn');
    const courseCards = document.querySelectorAll('.course-card');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all buttons
            tabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filter = btn.getAttribute('data-filter');

            courseCards.forEach(card => {
                const category = card.getAttribute('data-category');
                if (filter === 'all' || category === filter) {
                    card.style.display = 'flex';
                    card.style.animation = 'fadeInUp 0.5s ease forwards';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // 4. Modal Logic for Quick Role Selection
    const modalOverlay = document.getElementById('portalModal');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalSubTitle = document.getElementById('modalSubTitle');

    window.openPortalModal = function(type = 'login') {
        if (!modalOverlay) return;
        if (modalSubTitle) {
            if (type === 'register') {
                modalSubTitle.textContent = 'Select your role to complete registration';
            } else {
                modalSubTitle.textContent = 'Select your portal to access your dashboard';
            }
        }
        modalOverlay.classList.add('active');
    };

    window.closePortalModal = function() {
        if (modalOverlay) {
            modalOverlay.classList.remove('active');
        }
    };

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', window.closePortalModal);
    }

    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) {
                window.closePortalModal();
            }
        });
    }

    // 5. Scroll Active Link Highlight
    const sections = document.querySelectorAll('section[id]');
    window.addEventListener('scroll', () => {
        const scrollY = window.pageYOffset;
        sections.forEach(current => {
            const sectionHeight = current.offsetHeight;
            const sectionTop = current.offsetTop - 120;
            const sectionId = current.getAttribute('id');
            const navLink = document.querySelector(`.nav-menu a[href*="#${sectionId}"]`);

            if (navLink) {
                if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                    navLink.classList.add('active');
                } else {
                    navLink.classList.remove('active');
                }
            }
        });
    });

    // 6. Animated Stats Counter
    const statCounters = document.querySelectorAll('.counter-value');
    let animated = false;

    function animateCounters() {
        statCounters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText;
            const increment = target / 60;

            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(animateCounters, 25);
            } else {
                counter.innerText = target.toLocaleString() + '+';
            }
        });
    }

    const statsSection = document.querySelector('.stats-section');
    if (statsSection) {
        window.addEventListener('scroll', () => {
            const sectionPos = statsSection.getBoundingClientRect().top;
            const screenPos = window.innerHeight / 1.3;
            if (sectionPos < screenPos && !animated) {
                animated = true;
                animateCounters();
            }
        });
    }
});
