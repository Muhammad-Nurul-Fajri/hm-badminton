<?php
/**
 * FILE: index.php (ROOT)
 * FUNGSI: Landing Page Utama & Entry Point Aplikasi
 */

require_once __DIR__ . '/config/functions.php';

// Jika user sudah login, redirect otomatis ke dashboard masing-masing
if (isLoggedIn()) {
    switch ($_SESSION['role'] ?? 'user') {
        case 'admin': redirect('admin/dashboard.php'); break;
        case 'owner': redirect('owner/dashboard.php'); break;
        default: redirect('pelanggan/dashboard.php'); break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Book your badminton court quickly, easily, and anytime with HM Badminton Court Rental Management System.">
    <meta name="base-url" content="<?php echo BASE_URL; ?>">
    <title>HM Badminton Court — Modern Court Rental System</title>
    
    <!-- Design System & Landing Styles -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/design-system.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/landing.css">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>

    <!-- STICKY NAVIGATION BAR -->
    <nav class="landing-navbar">
        <div class="navbar-container">
            <a href="#home" class="navbar-brand">
                <!-- SVG logo mimicking the uploaded image -->
                <svg width="45" height="40" viewBox="0 0 100 80" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;">
                    <text x="5" y="55" fill="var(--primary)" font-family="'Poppins', sans-serif" font-weight="900" font-size="52" font-style="italic">HM</text>
                    <path d="M 50 15 C 75 15, 95 35, 95 55" stroke="var(--text-primary)" stroke-width="4" fill="none" stroke-linecap="round"/>
                    <path d="M 60 25 C 75 35, 80 50, 80 55" stroke="var(--text-primary)" stroke-width="2" fill="none"/>
                    <path d="M 70 32 C 80 40, 83 50, 83 55" stroke="var(--text-primary)" stroke-width="2" fill="none"/>
                </svg>
                <span class="navbar-brand-text">HM <span class="text-primary">BADMINTON</span></span>
            </a>
            
            <button class="navbar-toggle" aria-label="Buka Navigasi">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <ul class="navbar-menu">
                <li><a href="#home">Beranda</a></li>
                <!-- <li><a href="#about">Tentang Kami</a></li> -->
                <li><a href="#live-booking">Jadwal Langsung</a></li>
                <li><a href="<?php echo BASE_URL; ?>auth/login.php" class="nav-login">Masuk</a></li>
            </ul>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <header id="home" class="hero-section">
        <!-- Background Carousel -->
        <div class="hero-carousel">
            <div class="hero-slide active">
                <img src="<?php echo BASE_URL; ?>assets/images/hero-court.png" alt="Badminton Court">
            </div>
            <div class="hero-slide">
                <img src="<?php echo BASE_URL; ?>assets/images/hero-play.png" alt="Interior View">
            </div>
            <div class="hero-slide">
                <img src="<?php echo BASE_URL; ?>assets/images/hero-tournament.png" alt="Tournament Activities">
            </div>
            <div class="hero-slide">
                <img src="<?php echo BASE_URL; ?>assets/images/hero-facility.png" alt="Customer Activities">
            </div>
        </div>
        
        <!-- Dark Overlay -->
        <div class="hero-overlay"></div>
        
        <!-- Hero Centered Content -->
        <div class="hero-content">
            <div class="hero-badge">
                <i class="bi bi-star-fill"></i> Best Badminton Court Management System
            </div>
            <h1 class="hero-title">HM Badminton Court<br><span>Rental Management System</span></h1>
            <p class="hero-subtitle">Book your badminton court quickly, easily, and anytime. Experience premium flooring, excellent lighting, and a user-friendly scheduling platform built for badminton enthusiasts.</p>
            <a href="<?php echo BASE_URL; ?>auth/login.php" class="hero-cta">
                Pesan Sekarang <i class="bi bi-arrow-right-short" style="font-size: 1.3rem;"></i>
            </a>
        </div>
        
        <!-- Carousel Nav Buttons & Indicators -->
        <div class="carousel-nav">
            <!-- <button class="carousel-btn carousel-prev" aria-label="Previous Slide">
                <i class="bi bi-chevron-left"></i>
            </button> -->
            <div class="carousel-indicators">
                <button class="carousel-dot active" aria-label="Slide 1"></button>
                <!-- <button class="carousel-dot" aria-label="Slide 2"></button>
                <button class="carousel-dot" aria-label="Slide 3"></button>
                <button class="carousel-dot" aria-label="Slide 4"></button> -->
            </div>
            <!-- <button class="carousel-btn carousel-next" aria-label="Next Slide">
                <i class="bi bi-chevron-right"></i>
            </button> -->
        </div>
    </header>

    <!-- ABOUT SECTION -->
    <section id="about" class="landing-section bg-white">
        <div class="landing-container">
            <!-- <div class="section-header animate-on-scroll animate-fade-up">
                <span class="section-label"><i class="bi bi-info-circle-fill"></i> Discover Us</span>
                <h2 class="section-title">About HM Badminton</h2>
                <p class="section-subtitle">We offer premium facilities designed for amateur players, professional tournaments, and sports training with multiple strategic locations.</p>
            </div> -->
            
            <div class="about-grid">
                <!-- Card 1: Court 1 -->
                <!-- <div class="about-card animate-on-scroll animate-fade-up animate-delay-1">
                    <div class="about-card-image">
                        <img src="<?php echo BASE_URL; ?>assets/images/court-katamso.png" alt="HM Badminton Court 1">
                    </div>
                    <div class="about-card-body">
                        <span class="card-label"><i class="bi bi-geo-alt-fill"></i> Branch 1</span>
                        <h3>HM Badminton Court 1</h3>
                        <p>Located at Jl. Brigjend Katamso. A professional facility equipped with 2 premium indoor courts, excellent ventilation, and spacious spectator stands.</p>
                    </div>
                </div> -->
                
                <!-- Card 2: Court 2
                <div class="about-card animate-on-scroll animate-fade-up animate-delay-2">
                    <div class="about-card-image">
                        <img src="<?php echo BASE_URL; ?>assets/images/court-garu.png" alt="HM Badminton Court 2">
                    </div>
                    <div class="about-card-body">
                        <span class="card-label"><i class="bi bi-geo-alt-fill"></i> Branch 2</span>
                        <h3>HM Badminton Court 2</h3>
                        <p>Located at Jl. Garu 3. Our largest branch featuring 5 synthetic rubber indoor courts, full locker rooms, and an on-site sports cafe for post-game relaxation.</p>
                    </div>
                </div> -->
                
                <!-- Card 3: Operating Hours -->
                <!-- <div class="about-card animate-on-scroll animate-fade-up animate-delay-3">
                    <div class="about-card-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="about-card-body">
                        <span class="card-label"><i class="bi bi-calendar-event-fill"></i> Schedule</span>
                        <h3>Operating Hours</h3>
                        <p>We are open every single day of the week to serve your active lifestyle. Book early to secure your preferred slots!</p>
                        <div class="hours-grid">
                            <div class="hours-item">
                                <span class="day">Monday &ndash; Sunday</span>
                                <span class="time">08:00 AM &ndash; 12:00 AM</span>
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
    </section>

    <!-- LIVE BOOKING SECTION -->
    <section id="live-booking" class="landing-section bg-light">
        <div class="landing-container">
            <div class="section-header animate-on-scroll animate-fade-up">
                <span class="section-label"><i class="bi bi-activity"></i> Realtime Status</span>
                <h2 class="section-title">Live Booking Schedule</h2>
                <p class="section-subtitle">See active bookings for today. If you want to make a reservation, click Book Now to sign in or create an account.</p>
            </div>
            
            <div class="booking-list-container">
                <!-- Live Booking Cards (rendered by JS calling api_bookings.php) -->
                <div id="live-booking-list" class="booking-list">
                    <!-- Shimmer Loader -->
                    <div style="text-align:center; padding: 40px; color: var(--text-secondary);">
                        <div style="width: 40px; height: 40px; border: 4px solid var(--border); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
                        <p>Loading schedule...</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="landing-footer">
        <div class="footer-grid">
            <div class="footer-column footer-about">
                <div class="footer-brand">
                    <svg width="45" height="40" viewBox="0 0 100 80" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;">
                        <text x="5" y="55" fill="var(--primary)" font-family="'Poppins', sans-serif" font-weight="900" font-size="52" font-style="italic">HM</text>
                        <path d="M 50 15 C 75 15, 95 35, 95 55" stroke="var(--text-inverse)" stroke-width="4" fill="none" stroke-linecap="round"/>
                        <path d="M 60 25 C 75 35, 80 50, 80 55" stroke="rgba(255,255,255,0.4)" stroke-width="2" fill="none"/>
                        <path d="M 70 32 C 80 40, 83 50, 83 55" stroke="rgba(255,255,255,0.4)" stroke-width="2" fill="none"/>
                    </svg>
                    <span>HM BADMINTON</span>
                </div>
                <p>HM Badminton Court offers high-quality indoor court venues in Medan with seamless online reservation, professional customer support, and elite play facilities.</p>
                <div class="footer-social">
                    <a href="https://facebook.com" target="_blank" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://instagram.com" target="_blank" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://wa.me/6281234567890" target="_blank" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="#home"><i class="bi bi-chevron-right"></i> Home</a></li>
                    <li><a href="#about"><i class="bi bi-chevron-right"></i> About Us</a></li>
                    <li><a href="#live-booking"><i class="bi bi-chevron-right"></i> Live Booking</a></li>
                    <li><a href="<?php echo BASE_URL; ?>auth/login.php"><i class="bi bi-chevron-right"></i> Login System</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4>Our Locations</h4>
                <div class="footer-contact-item">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span><strong>HM Court 1</strong><br>Jl. Brigjend Katamso, Medan</span>
                </div>
                <div class="footer-contact-item" style="margin-top: var(--space-4);">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span><strong>HM Court 2</strong><br>Jl. Garu 3, Medan</span>
                </div>
            </div>
            
            <div class="footer-column">
                <h4>Contact Us</h4>
                <div class="footer-contact-item">
                    <i class="bi bi-telephone-fill"></i>
                    <span>+62 812-3456-7890</span>
                </div>
                <div class="footer-contact-item">
                    <i class="bi bi-envelope-fill"></i>
                    <span>info@hmbadminton.com</span>
                </div>
                <div class="footer-contact-item">
                    <i class="bi bi-clock-fill"></i>
                    <span>Mon - Sun: 08:00 AM - 12:00 AM</span>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-bottom-inner">
                <p>&copy; <?php echo date('Y'); ?> HM Badminton Court Rental. All Rights Reserved.</p>
                <p>Designed with <i class="bi bi-heart-fill text-primary" style="font-size: 0.8rem"></i> for Badminton Lovers</p>
            </div>
        </div>
    </footer>

    <!-- Landing page specific script -->
    <script src="<?php echo BASE_URL; ?>assets/js/landing.js"></script>
</body>
</html>