<?php
require_once __DIR__ . '/DATABASE/db_connection.php';
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_type'] ?? '';
$userName = $_SESSION['name'] ?? 'User';

$dashboardUrl = 'login.php';
if ($isLoggedIn) {
    if ($userRole === 'admin') {
        $dashboardUrl = 'VIEWS/USER/Admin_view.php';
    } elseif ($userRole === 'teacher') {
        $dashboardUrl = 'VIEWS/USER/teacher_view.php';
    } else {
        $dashboardUrl = 'VIEWS/USER/student_view.php';
    }
}

$conn = getPgPDO();
$featuredCourses = [];
try {
    $stmt = $conn->query("SELECT c.*, cat.name as category_name, cat.slug as category_slug 
                          FROM courses c 
                          LEFT JOIN categories cat ON c.category_id = cat.id 
                          ORDER BY c.created_at DESC");
    if ($stmt) {
        $featuredCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $featuredCourses = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Learning Platform - Empower Your Future</title>
    <meta name="description" content="Discover modern online courses in Web Development, AI, Data Science, Cyber Security, and Mobile App Development. Join thousands of students and expert instructors today.">
    
    <!-- Google Fonts & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Landing Glassmorphism Stylesheet -->
    <link rel="stylesheet" href="PUBLIC/CSS/landing_style.css">
</head>
<body>

    <!-- Background Ambient Orbs -->
    <div class="ambient-orb-center"></div>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo-brand">
                <div class="logo-icon">
                    <i class="bx bxs-graduation"></i>
                </div>
                <div class="logo-text">
                    Online Learning <span>Platform</span>
                </div>
            </a>

            <ul class="nav-menu">
                <li><a href="#hero" class="nav-link active">Home</a></li>
                <li><a href="#portals" class="nav-link">Portals</a></li>
                <li><a href="#courses" class="nav-link">Courses</a></li>
                <li><a href="#features" class="nav-link">Features</a></li>
                <li><a href="#stats" class="nav-link">About</a></li>
            </ul>

            <div class="nav-actions">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo $dashboardUrl; ?>" class="btn btn-primary">
                        <i class="bx bxs-dashboard"></i> My Dashboard
                    </a>
                    <a href="VIEWS/auth/logout.php" class="btn btn-glass">
                        <i class="bx bx-log-out"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-glass">
                        <i class="bx bx-log-in-circle"></i> Sign In
                    </a>
                    <button onclick="openPortalModal('register')" class="btn btn-primary">
                        <i class="bx bx-user-plus"></i> Get Started
                    </button>
                <?php endif; ?>
                <button class="mobile-toggle" aria-label="Toggle navigation">
                    <i class="bx bx-menu"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="hero" class="hero">
        <div class="container hero-grid">
            <div class="hero-content">
                <div class="badge-tag">
                    <i class="bx bxs-bolt-circle"></i> Next-Gen Learning Experience
                </div>
                <h1>
                    Master New Skills with <br>
                    <span class="gradient-text">Interactive Online Education</span>
                </h1>
                <p>
                    Unlock your full potential with industry-aligned courses, real-time mentorship, interactive quizzes, and verified certification for Students, Teachers, and Administrators.
                </p>

                <div class="hero-buttons">
                    <?php if (!$isLoggedIn): ?>
                        <button onclick="openPortalModal('register')" class="btn btn-primary">
                            <i class="bx bx-rocket"></i> Register Now
                        </button>
                        <button onclick="openPortalModal('login')" class="btn btn-glass">
                            <i class="bx bxs-key"></i> Choose Portal Login
                        </button>
                    <?php else: ?>
                        <a href="<?php echo $dashboardUrl; ?>" class="btn btn-primary">
                            <i class="bx bxs-dashboard"></i> Return to Dashboard
                        </a>
                        <a href="#courses" class="btn btn-glass">
                            <i class="bx bx-book-open"></i> Browse Courses
                        </a>
                    <?php endif; ?>
                </div>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <h3>1,250+</h3>
                        <p>Active Students</p>
                    </div>
                    <div class="mini-stat">
                        <h3>50+</h3>
                        <p>Expert Instructors</p>
                    </div>
                    <div class="mini-stat">
                        <h3>98.4%</h3>
                        <p>Satisfaction Rate</p>
                    </div>
                </div>
            </div>

            <!-- Hero Glass Mockup Visual -->
            <div class="hero-visual">
                <!-- Floating Badge 1 -->
                <div class="floating-badge badge-top-right">
                    <div class="badge-icon-circle">
                        <i class="bx bxs-award"></i>
                    </div>
                    <div class="badge-text-group">
                        <h5>Certified Diploma</h5>
                        <p>Global Accreditation</p>
                    </div>
                </div>

                <!-- Floating Badge 2 -->
                <div class="floating-badge badge-bottom-left">
                    <div class="badge-icon-circle" style="background: linear-gradient(135deg, #06b6d4, #3b82f6);">
                        <i class="bx bxs-check-shield"></i>
                    </div>
                    <div class="badge-text-group">
                        <h5>100% Verified</h5>
                        <p>Secure Role Access</p>
                    </div>
                </div>

                <!-- Glass Mockup Dashboard Preview -->
                <div class="mockup-container">
                    <div class="mockup-header">
                        <div class="window-dots">
                            <span class="dot red"></span>
                            <span class="dot yellow"></span>
                            <span class="dot green"></span>
                        </div>
                        <div class="mockup-title">online_learning_platform_v2.0</div>
                        <div style="color: var(--primary); font-size: 14px;"><i class="bx bxs-circle"></i> Live</div>
                    </div>

                    <div class="mockup-body">
                        <div class="mock-card purple">
                            <div class="mock-card-icon">
                                <i class="bx bxs-layer"></i>
                            </div>
                            <h4>Full Stack Web Dev</h4>
                            <p>8 Modules • Completed 75%</p>
                        </div>

                        <div class="mock-card pink">
                            <div class="mock-card-icon">
                                <i class="bx bxs-brain"></i>
                            </div>
                            <h4>AI & Data Science</h4>
                            <p>12 Modules • Active</p>
                        </div>

                        <div class="mock-card cyan">
                            <div class="mock-card-icon">
                                <i class="bx bxs-group"></i>
                            </div>
                            <h4>Interactive Class</h4>
                            <p>Live Q&A Sessions</p>
                        </div>

                        <div class="mock-card emerald">
                            <div class="mock-card-icon">
                                <i class="bx bxs-badge-check"></i>
                            </div>
                            <h4>Graduation Ready</h4>
                            <p>Certificates Issued</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Role Portals Access Hub -->
    <section id="portals" class="portals-section">
        <div class="container">
            <div class="section-header">
                <div class="badge-tag">Unified Access</div>
                <h2>Dedicated Access Portals</h2>
                <p>Choose your designated portal to register or log into your interactive dashboard.</p>
            </div>

            <div class="portal-grid">
                <!-- Student Portal Card -->
                <div class="glass-card portal-card student">
                    <span class="portal-badge">Learner Portal</span>
                    <div class="portal-icon">
                        <i class="bx bxs-user-badge"></i>
                    </div>
                    <h3>Student Portal</h3>
                    <p>Access enrolled courses, track study progress, submit assignments, and take interactive quizzes.</p>
                    <div class="portal-actions">
                        <a href="VIEWS/USER/Student_Registration.html" class="btn btn-primary">
                            <i class="bx bx-user-plus"></i> Register as Student
                        </a>
                        <a href="login.php" class="btn btn-glass">
                            <i class="bx bx-log-in"></i> Student Login
                        </a>
                    </div>
                </div>

                <!-- Teacher Portal Card -->
                <div class="glass-card portal-card teacher">
                    <span class="portal-badge">Instructor Portal</span>
                    <div class="portal-icon">
                        <i class="bx bxs-chalkboard"></i>
                    </div>
                    <h3>Teacher Portal</h3>
                    <p>Manage curriculum, create course modules, evaluate student progress, and organize live lectures.</p>
                    <div class="portal-actions">
                        <a href="VIEWS/USER/teacherregistration.html" class="btn btn-primary" style="background: linear-gradient(135deg, #ec4899, #f43f5e);">
                            <i class="bx bx-user-plus"></i> Register as Teacher
                        </a>
                        <a href="login.php" class="btn btn-glass">
                            <i class="bx bx-log-in"></i> Teacher Login
                        </a>
                    </div>
                </div>

                <!-- Admin Portal Card -->
                <div class="glass-card portal-card admin">
                    <span class="portal-badge">System Portal</span>
                    <div class="portal-icon">
                        <i class="bx bxs-shield-quarter"></i>
                    </div>
                    <h3>Admin Portal</h3>
                    <p>Monitor platform statistics, manage active users, configure system security, and oversee system reports.</p>
                    <div class="portal-actions">
                        <a href="VIEWS/USER/admin_registration.php" class="btn btn-primary" style="background: linear-gradient(135deg, #06b6d4, #3b82f6);">
                            <i class="bx bx-user-plus"></i> Register as Admin
                        </a>
                        <a href="login.php" class="btn btn-glass">
                            <i class="bx bx-log-in"></i> Admin Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Courses Showcase Section -->
    <section id="courses" class="courses-section">
        <div class="container">
            <div class="section-header">
                <div class="badge-tag">Curriculum</div>
                <h2>Explore Featured Courses</h2>
                <p>Empower your skill set with industry-standard courses designed by leading tech professionals.</p>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="tab-btn active" data-filter="all">All Courses</button>
                <button class="tab-btn" data-filter="web">Web Development</button>
                <button class="tab-btn" data-filter="python">Python & AI</button>
                <button class="tab-btn" data-filter="data">Data Science</button>
                <button class="tab-btn" data-filter="security">Cyber Security</button>
            </div>

            <!-- Courses Grid -->
            <div class="courses-grid">
                <?php if (!empty($featuredCourses)): ?>
                    <?php foreach ($featuredCourses as $fc): ?>
                        <?php 
                            $catSlug = $fc['category_slug'] ?? 'web';
                            $imgPath = !empty($fc['thumbnail']) ? $fc['thumbnail'] : 'PUBLIC/pic/img.jpg';
                            $priceText = ($fc['price'] == 0) ? 'Free' : 'BDT ' . number_format($fc['price'], 2);
                        ?>
                        <div class="glass-card course-card" data-category="<?php echo htmlspecialchars($catSlug); ?>">
                            <div class="course-thumb">
                                <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($fc['title']); ?>" onerror="this.src='PUBLIC/pic/img.jpg'">
                                <span class="course-category-badge"><?php echo htmlspecialchars($fc['category_name'] ?? 'General'); ?></span>
                                <span class="course-price-badge"><?php echo htmlspecialchars($priceText); ?></span>
                            </div>
                            <div class="course-content">
                                <div class="course-meta">
                                    <span><i class="bx bx-time"></i> <?php echo htmlspecialchars($fc['duration'] ?? 'N/A'); ?></span>
                                    <span class="rating"><i class="bx bxs-star"></i> 4.9</span>
                                </div>
                                <h3 class="course-title"><?php echo htmlspecialchars($fc['title']); ?></h3>
                                <p class="course-desc"><?php echo htmlspecialchars(substr($fc['description'] ?? '', 0, 110)) . '...'; ?></p>
                                <div class="course-footer">
                                    <div class="instructor">
                                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80" alt="Instructor">
                                        <span>Verified Instructor</span>
                                    </div>
                                    <a href="VIEWS/USER/courses.php" class="btn btn-outline" style="padding: 8px 16px; font-size: 13px;">View Course</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default Preset Courses -->
                    <div class="glass-card course-card" data-category="web">
                        <div class="course-thumb">
                            <img src="https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=600&q=80" alt="Full Stack Web Development">
                            <span class="course-category-badge">Web Development</span>
                            <span class="course-price-badge">BDT 1,500.00</span>
                        </div>
                        <div class="course-content">
                            <div class="course-meta">
                                <span><i class="bx bx-time"></i> 12 Weeks</span>
                                <span class="rating"><i class="bx bxs-star"></i> 4.9</span>
                            </div>
                            <h3 class="course-title">Full Stack Modern Web Development</h3>
                            <p class="course-desc">Master HTML5, CSS3, JavaScript, PHP, PDO, PostgreSQL, and modern responsive glassmorphism UI frameworks.</p>
                            <div class="course-footer">
                                <div class="instructor">
                                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80" alt="Instructor">
                                    <span>Dr. Sarah Jenkins</span>
                                </div>
                                <a href="VIEWS/USER/courses.php" class="btn btn-outline" style="padding: 8px 16px; font-size: 13px;">View Course</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Why Choose Us / Features -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="section-header">
                <div class="badge-tag">Why Choose Us</div>
                <h2>State-Of-The-Art Learning Ecosystem</h2>
                <p>Designed for maximum engagement, high course retention, and effortless platform management.</p>
            </div>

            <div class="features-grid">
                <div class="glass-card feature-card">
                    <div class="feature-icon">
                        <i class="bx bxs-devices"></i>
                    </div>
                    <h3>Multi-Device Responsive UI</h3>
                    <p>Experience seamless glassmorphism visuals optimized across desktop PCs, tablets, and smartphones.</p>
                </div>

                <div class="glass-card feature-card">
                    <div class="feature-icon">
                        <i class="bx bxs-lock-alt"></i>
                    </div>
                    <h3>Role-Based Security</h3>
                    <p>Separate, encrypted authentication logic for Student, Teacher, and Administrator roles with PDO prepared statements.</p>
                </div>

                <div class="glass-card feature-card">
                    <div class="feature-icon">
                        <i class="bx bxs-bar-chart-alt-2"></i>
                    </div>
                    <h3>Real-Time Analytics</h3>
                    <p>Track student performance, completion percentages, quiz scores, and system audit logs dynamically.</p>
                </div>

                <div class="glass-card feature-card">
                    <div class="feature-icon">
                        <i class="bx bxs-certification"></i>
                    </div>
                    <h3>Verified Certificates</h3>
                    <p>Earn official completion credentials upon completing module assignments and passing final exams.</p>
                </div>

                <div class="glass-card feature-card">
                    <div class="feature-icon">
                        <i class="bx bxs-data"></i>
                    </div>
                    <h3>Multi-Database Support</h3>
                    <p>Built with cross-database compatibility supporting PostgreSQL for Railway cloud and SQLite for offline local dev.</p>
                </div>

                <div class="glass-card feature-card">
                    <div class="feature-icon">
                        <i class="bx bxs-conversation"></i>
                    </div>
                    <h3>Instructor Mentorship</h3>
                    <p>Direct communication channels between registered students and teachers for interactive code reviews.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Counter Banner -->
    <section id="stats" class="stats-section">
        <div class="container">
            <div class="glass-card stats-card-banner">
                <div class="stat-item">
                    <h2 class="counter-value" data-target="1250">0</h2>
                    <p>Active Students</p>
                </div>
                <div class="stat-item">
                    <h2 class="counter-value" data-target="56">0</h2>
                    <p>Live Courses</p>
                </div>
                <div class="stat-item">
                    <h2 class="counter-value" data-target="28">0</h2>
                    <p>Expert Instructors</p>
                </div>
                <div class="stat-item">
                    <h2 class="counter-value" data-target="98">0</h2>
                    <p>% Completion Rate</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Role Selection Modal -->
    <div id="portalModal" class="modal-overlay">
        <div class="glass-card modal-box">
            <button id="modalCloseBtn" class="modal-close" aria-label="Close modal">
                <i class="bx bx-x"></i>
            </button>
            <div class="modal-title">
                <h3>Select Your Role Portal</h3>
                <p id="modalSubTitle">Choose your account type to proceed</p>
            </div>

            <div class="role-select-list">
                <a href="VIEWS/USER/Student_Registration.html" class="role-select-item student">
                    <div class="role-item-info">
                        <div class="role-item-icon">
                            <i class="bx bxs-user"></i>
                        </div>
                        <div>
                            <h4>Student Registration</h4>
                            <p>Create a learner account & enroll in courses</p>
                        </div>
                    </div>
                    <i class="bx bx-chevron-right" style="font-size: 22px; color: var(--primary);"></i>
                </a>

                <a href="VIEWS/USER/teacherregistration.html" class="role-select-item teacher">
                    <div class="role-item-info">
                        <div class="role-item-icon">
                            <i class="bx bxs-chalkboard"></i>
                        </div>
                        <div>
                            <h4>Teacher Registration</h4>
                            <p>Register as an instructor to publish courses</p>
                        </div>
                    </div>
                    <i class="bx bx-chevron-right" style="font-size: 22px; color: var(--secondary);"></i>
                </a>

                <a href="VIEWS/USER/admin_registration.php" class="role-select-item admin">
                    <div class="role-item-info">
                        <div class="role-item-icon">
                            <i class="bx bxs-shield-quarter"></i>
                        </div>
                        <div>
                            <h4>Admin Registration</h4>
                            <p>Register system administration account</p>
                        </div>
                    </div>
                    <i class="bx bx-chevron-right" style="font-size: 22px; color: var(--accent-cyan);"></i>
                </a>

                <div style="text-align: center; margin-top: 10px;">
                    <span style="font-size: 13px; color: var(--text-muted);">Already have an account?</span>
                    <a href="login.php" style="font-size: 13px; color: var(--primary); font-weight: 600; text-decoration: none; margin-left: 6px;">Sign In Here</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="index.php" class="logo-brand" style="margin-bottom: 16px;">
                        <div class="logo-icon">
                            <i class="bx bxs-graduation"></i>
                        </div>
                        <div class="logo-text">
                            Online Learning <span>Platform</span>
                        </div>
                    </a>
                    <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; max-width: 320px;">
                        Empowering students, instructors, and administrators with state-of-the-art interactive digital education tools.
                    </p>
                </div>

                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="#hero">Home</a></li>
                        <li><a href="#portals">Access Portals</a></li>
                        <li><a href="#courses">Courses</a></li>
                        <li><a href="#features">Platform Features</a></li>
                        <li><a href="login.php">Sign In</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Role Portals</h4>
                    <ul class="footer-links">
                        <li><a href="VIEWS/USER/Student_Registration.html">Student Portal</a></li>
                        <li><a href="VIEWS/USER/teacherregistration.html">Teacher Portal</a></li>
                        <li><a href="VIEWS/USER/admin_registration.php">Admin Portal</a></li>
                        <li><a href="VIEWS/USER/student_view.php">Student Dashboard</a></li>
                        <li><a href="VIEWS/USER/teacher_view.php">Teacher Dashboard</a></li>
                        <li><a href="VIEWS/USER/Admin_view.php">Admin Dashboard</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>System Security</h4>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                        Cross-database PDO architecture with encrypted BCrypt authentication.
                    </p>
                    <div style="display: flex; gap: 12px; font-size: 20px;">
                        <a href="#" style="color: var(--text-muted);"><i class="bx bxl-github"></i></a>
                        <a href="#" style="color: var(--text-muted);"><i class="bx bxl-twitter"></i></a>
                        <a href="#" style="color: var(--text-muted);"><i class="bx bxl-linkedin"></i></a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Online Learning Platform. All rights reserved.</p>
                <p>Designed with Glassmorphism & PHP / PDO</p>
            </div>
        </div>
    </footer>

    <!-- Interactive JavaScript -->
    <script src="PUBLIC/JS/landing.js"></script>
</body>
</html>
