/**
 * FILE: assets/js/landing.js
 * FUNGSI: Landing page interactivity — carousel, navbar scroll, scroll animations, mobile menu
 */

document.addEventListener('DOMContentLoaded', function () {

    // ============================================
    // HERO CAROUSEL
    // ============================================
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.carousel-dot');
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    let currentSlide = 0;
    let slideInterval;
    const SLIDE_DURATION = 6000; // 6 seconds

    function showSlide(index) {
        // Wrap around
        if (index >= slides.length) index = 0;
        if (index < 0) index = slides.length - 1;

        slides.forEach(s => s.classList.remove('active'));
        dots.forEach(d => d.classList.remove('active'));

        slides[index].classList.add('active');
        dots[index].classList.add('active');
        currentSlide = index;
    }

    function nextSlide() {
        showSlide(currentSlide + 1);
    }

    function prevSlide() {
        showSlide(currentSlide - 1);
    }

    function startAutoSlide() {
        stopAutoSlide();
        slideInterval = setInterval(nextSlide, SLIDE_DURATION);
    }

    function stopAutoSlide() {
        if (slideInterval) clearInterval(slideInterval);
    }

    // Event listeners
    if (nextBtn) nextBtn.addEventListener('click', function () {
        nextSlide();
        startAutoSlide(); // Reset timer
    });

    if (prevBtn) prevBtn.addEventListener('click', function () {
        prevSlide();
        startAutoSlide();
    });

    dots.forEach(function (dot, index) {
        dot.addEventListener('click', function () {
            showSlide(index);
            startAutoSlide();
        });
    });

    // Initialize carousel
    if (slides.length > 0) {
        showSlide(0);
        startAutoSlide();
    }

    // Pause on hover
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        heroSection.addEventListener('mouseenter', stopAutoSlide);
        heroSection.addEventListener('mouseleave', startAutoSlide);
    }

    // ============================================
    // STICKY NAVBAR — transparent to solid on scroll
    // ============================================
    const navbar = document.querySelector('.landing-navbar');
    if (navbar) {
        function updateNavbar() {
            if (window.scrollY > 60) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }
        window.addEventListener('scroll', updateNavbar, { passive: true });
        updateNavbar(); // Initial check
    }

    // ============================================
    // MOBILE MENU TOGGLE
    // ============================================
    const toggle = document.querySelector('.navbar-toggle');
    const menu = document.querySelector('.navbar-menu');

    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            toggle.classList.toggle('active');
            menu.classList.toggle('active');
            document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';
        });

        // Close menu when clicking a link
        menu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                toggle.classList.remove('active');
                menu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });

        // Close menu on outside click
        document.addEventListener('click', function (e) {
            if (menu.classList.contains('active') && !menu.contains(e.target) && !toggle.contains(e.target)) {
                toggle.classList.remove('active');
                menu.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }

    // ============================================
    // SMOOTH SCROLL for anchor links
    // ============================================
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                const navbarHeight = navbar ? navbar.offsetHeight : 0;
                const targetPos = target.getBoundingClientRect().top + window.scrollY - navbarHeight - 20;
                window.scrollTo({ top: targetPos, behavior: 'smooth' });
            }
        });
    });

    // ============================================
    // SCROLL-TRIGGERED ANIMATIONS (Intersection Observer)
    // ============================================
    const animatedElements = document.querySelectorAll('.animate-on-scroll');

    if (animatedElements.length > 0 && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target); // Only animate once
                }
            });
        }, {
            root: null,
            rootMargin: '0px 0px -80px 0px',
            threshold: 0.1
        });

        animatedElements.forEach(function (el) {
            observer.observe(el);
        });
    }

    // ============================================
    // LIVE BOOKING — Fetch from API
    // ============================================
    const bookingContainer = document.getElementById('live-booking-list');
    if (bookingContainer) {
        fetchLiveBookings();
    }

    function fetchLiveBookings() {
        const baseUrl = document.querySelector('meta[name="base-url"]');
        const apiUrl = baseUrl ? baseUrl.content + 'api/api_bookings.php' : 'api/api_bookings.php';

        fetch(apiUrl)
            .then(function (response) { return response.json(); })
            .then(function (data) {
                renderBookings(data);
            })
            .catch(function (error) {
                console.error('Error fetching bookings:', error);
                bookingContainer.innerHTML = renderEmptyState();
            });
    }

    function renderBookings(data) {
        if (!data.bookings || data.bookings.length === 0) {
            bookingContainer.innerHTML = renderEmptyState();
            return;
        }

        var html = '';
        data.bookings.forEach(function (booking) {
            var statusClass = getStatusClass(booking.status);
            var statusLabel = getStatusLabel(booking.status);

            html += '<div class="booking-card animate-on-scroll animate-fade-up is-visible">' +
                '<div class="booking-time">' +
                    '<span class="time">' + booking.waktu_mulai + ' - ' + booking.waktu_selesai + '</span>' +
                    '<span class="duration">' + booking.durasi_jam + ' jam</span>' +
                '</div>' +
                '<div class="booking-info">' +
                    '<h4>' + escapeHtml(booking.lapangan_nama) + ' &mdash; ' + escapeHtml(booking.cabang_nama) + '</h4>' +
                    '<p><i class="bi bi-person-fill"></i> ' + escapeHtml(booking.user_nama) + ' &nbsp;·&nbsp; <i class="bi bi-calendar3"></i> ' + booking.tanggal_formatted + '</p>' +
                '</div>' +
                '<div class="booking-status">' +
                    '<span class="status-badge ' + statusClass + '">' +
                        '<i class="bi bi-circle-fill" style="font-size:6px"></i> ' + statusLabel +
                    '</span>' +
                '</div>' +
            '</div>';
        });

        bookingContainer.innerHTML = html;
    }

    function renderEmptyState() {
        return '<div class="booking-empty">' +
            '<div class="booking-empty-icon"><i class="bi bi-calendar-x"></i></div>' +
            '<h3>No Bookings Today</h3>' +
            '<p>There are no active bookings for today. Book your court now!</p>' +
        '</div>';
    }

    function getStatusClass(status) {
        switch (status) {
            case 'confirmed': return 'confirmed';
            case 'pending': return 'pending';
            case 'cancelled': return 'cancelled';
            case 'completed': return 'completed';
            default: return 'pending';
        }
    }

    function getStatusLabel(status) {
        switch (status) {
            case 'confirmed': return 'Booked';
            case 'pending': return 'Pending';
            case 'cancelled': return 'Expired';
            case 'completed': return 'Completed';
            default: return status;
        }
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

});
