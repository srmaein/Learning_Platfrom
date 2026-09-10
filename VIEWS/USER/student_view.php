<?php
require_once __DIR__ . '/../../DATABASE/db_connection.php';
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$conn = getPgPDO();
$currentUserId = $_SESSION['user_id'] ?? 0;
$userEmail = $_SESSION['email'] ?? '';

// Fetch current logged-in student profile details
$studentProfile = null;
try {
    $stmtUser = $conn->prepare("
        SELECT u.id, u.email, u.username, u.role, u.status, u.created_at, 
               p.first_name, p.last_name, p.full_name, p.phone_number, p.gender, p.blood_group, p.qualifications
        FROM users u 
        LEFT JOIN profiles p ON u.id = p.user_id 
        WHERE u.id = :id OR LOWER(u.email) = LOWER(:email)
    ");
    $stmtUser->execute([':id' => $currentUserId, ':email' => $userEmail]);
    $studentProfile = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Student Profile Fetch Error: " . $e->getMessage());
}

$studentName = !empty($_SESSION['name']) ? $_SESSION['name'] : (!empty($studentProfile['full_name']) ? $studentProfile['full_name'] : ($_SESSION['username'] ?? 'Student'));
$studentEmail = !empty($studentProfile['email']) ? $studentProfile['email'] : ($_SESSION['email'] ?? 'student@platform.com');
$actualUid = $studentProfile['id'] ?? $currentUserId;

// Fetch enrolled courses for this student from DB
$enrolledCourses = [];
$enrolledCourseIds = [];
try {
    $eStmt = $conn->prepare("
        SELECT c.*, cat.name as category_name, e.progress_percent, e.status as enrollment_status, e.enrolled_at
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE e.user_id = :uid AND e.status = 'ENROLLED'
        ORDER BY e.enrolled_at DESC
    ");
    $eStmt->execute([':uid' => $actualUid]);
    $enrolledCourses = $eStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($enrolledCourses as $ec) {
        $enrolledCourseIds[] = $ec['id'];
    }
} catch (Exception $e) {
    $enrolledCourses = [];
}

// Fetch all catalog courses for exploration & demo enrollment
$catalogCourses = [];
try {
    $cStmt = $conn->query("
        SELECT c.*, cat.name as category_name 
        FROM courses c 
        LEFT JOIN categories cat ON c.category_id = cat.id 
        ORDER BY c.created_at DESC
    ");
    if ($cStmt) {
        $catalogCourses = $cStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $catalogCourses = [];
}

// If database has no courses, fallback to sample demo courses
if (empty($catalogCourses)) {
    $catalogCourses = [
        [
            'id' => 1,
            'course_code' => 'CSE-401',
            'title' => 'Full Stack Modern Web Development with PHP & MySQL',
            'description' => 'Master HTML5, CSS3, JavaScript, PHP PDO, MySQL database design, and modern responsive glassmorphism UI frameworks.',
            'category_name' => 'Web Development',
            'price' => 4500.00,
            'duration' => '12 Weeks',
            'thumbnail' => 'PUBLIC/pic/img.jpg'
        ],
        [
            'id' => 2,
            'course_code' => 'AI-302',
            'title' => 'Python Programming & Artificial Intelligence Essentials',
            'description' => 'From core syntax to Machine Learning models, Deep Neural Networks, Pandas, NumPy, and Scikit-Learn data science stack.',
            'category_name' => 'Python & AI',
            'price' => 6000.00,
            'duration' => '10 Weeks',
            'thumbnail' => 'PUBLIC/pic/img.jpg'
        ],
        [
            'id' => 3,
            'course_code' => 'DAT-205',
            'title' => 'Data Analytics & Business Intelligence Dashboarding',
            'description' => 'Transform raw relational databases into interactive PowerBI & Tableau dashboards with advanced SQL data analytics.',
            'category_name' => 'Data Science',
            'price' => 3500.00,
            'duration' => '8 Weeks',
            'thumbnail' => 'PUBLIC/pic/img.jpg'
        ],
        [
            'id' => 4,
            'course_code' => 'SEC-101',
            'title' => 'Cyber Security Essentials & Network Defense',
            'description' => 'Ethical hacking methodologies, penetration testing fundamentals, network security architecture, and vulnerability assessment.',
            'category_name' => 'Cyber Security',
            'price' => 5000.00,
            'duration' => '8 Weeks',
            'thumbnail' => 'PUBLIC/pic/img.jpg'
        ]
    ];
}

// Fetch active assignments for student's courses
$studentAssignments = [];
try {
    $aStmt = $conn->query("
        SELECT a.*, c.title as course_title, c.course_code
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE a.status = 'ACTIVE'
        ORDER BY a.created_at DESC LIMIT 5
    ");
    if ($aStmt) {
        $studentAssignments = $aStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $studentAssignments = [];
}

if (empty($studentAssignments)) {
    $studentAssignments = [
        [
            'id' => 101,
            'title' => 'Responsive Glassmorphism Portal Implementation',
            'course_title' => 'Full Stack Modern Web Development',
            'course_code' => 'CSE-401',
            'total_marks' => 100,
            'due_date' => date('Y-m-d', strtotime('+3 days')),
            'status' => 'ACTIVE'
        ],
        [
            'id' => 102,
            'title' => 'Machine Learning Model Optimization with Pandas',
            'course_title' => 'Python Programming & AI Essentials',
            'course_code' => 'AI-302',
            'total_marks' => 50,
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'status' => 'ACTIVE'
        ]
    ];
}

// Attendance Records Data
$attendanceRecords = [
    [
        'date' => date('Y-m-d', strtotime('today')),
        'course' => 'Full Stack Modern Web Development',
        'code' => 'CSE-401',
        'topic' => 'PHP PDO Architecture & Database Prepared Statements',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'PRESENT'
    ],
    [
        'date' => date('Y-m-d', strtotime('-1 day')),
        'course' => 'Python Programming & AI Essentials',
        'code' => 'AI-302',
        'topic' => 'Supervised Learning & Regression Pipeline',
        'time' => '02:00 PM - 03:30 PM',
        'status' => 'PRESENT'
    ],
    [
        'date' => date('Y-m-d', strtotime('-3 days')),
        'course' => 'Data Analytics & Business Intelligence',
        'code' => 'DAT-205',
        'topic' => 'Relational Schema Normalization & Window Functions',
        'time' => '11:00 AM - 12:30 PM',
        'status' => 'PRESENT'
    ],
    [
        'date' => date('Y-m-d', strtotime('-4 days')),
        'course' => 'Full Stack Modern Web Development',
        'code' => 'CSE-401',
        'topic' => 'CSS Flexbox, Grid Layouts & Micro-Animations',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'LATE'
    ]
];

// Attendance calculation
$totalClasses = count($attendanceRecords);
$presentCount = 0;
foreach ($attendanceRecords as $ar) {
    if ($ar['status'] === 'PRESENT' || $ar['status'] === 'LATE') {
        $presentCount++;
    }
}
$attendanceRate = $totalClasses > 0 ? round(($presentCount / $totalClasses) * 100, 1) : 100.0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Online Learning Platform</title>
    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #6c5ce7;
            --primary-dark: #4834d4;
            --primary-light: #a29bfe;
            --accent: #00cec9;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-main: #f8fafc;
            --sidebar-bg: #1e1e2d;
            --sidebar-hover: rgba(108, 92, 231, 0.15);
            --card-bg: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --radius-lg: 16px;
            --radius-md: 12px;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 10px 25px rgba(108, 92, 231, 0.08);
            --shadow-lg: 0 20px 35px rgba(0, 0, 0, 0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: #ffffff;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.15);
        }

        .sidebar-brand {
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .brand-logo {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.4);
        }

        .brand-text h3 {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.3px;
        }

        .brand-text span {
            font-size: 11px;
            color: var(--primary-light);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            padding: 20px 14px;
            list-style: none;
            flex: 1;
            overflow-y: auto;
        }

        .menu-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            padding: 12px 14px 6px;
            letter-spacing: 0.8px;
        }

        .menu-item {
            margin-bottom: 6px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.25s ease;
        }

        .menu-link i {
            font-size: 18px;
            width: 22px;
            text-align: center;
            transition: transform 0.2s ease;
        }

        .menu-link:hover {
            color: #ffffff;
            background: var(--sidebar-hover);
        }

        .menu-link.active {
            color: #ffffff;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.35);
        }

        .sidebar-footer {
            padding: 18px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #ffffff;
            font-size: 15px;
        }

        .user-details h5 {
            font-size: 13px;
            color: #ffffff;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 130px;
        }

        .user-details span {
            font-size: 11px;
            color: #94a3b8;
        }

        /* Main Content Wrapper */
        .main-wrapper {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Top Navbar Header */
        .top-navbar {
            background: var(--card-bg);
            height: 75px;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: var(--shadow-sm);
        }

        .navbar-title h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            letter-spacing: -0.3px;
        }

        .navbar-title p {
            font-size: 13px;
            color: var(--text-muted);
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .badge-currency {
            background: rgba(108, 92, 231, 0.1);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(108, 92, 231, 0.2);
        }

        .logout-btn {
            background: #f1f5f9;
            color: #475569;
            border: none;
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: #fee2e2;
            color: var(--danger);
        }

        /* Container Area */
        .content-container {
            padding: 32px;
            flex: 1;
        }

        /* Welcome Banner Card */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: var(--radius-lg);
            padding: 32px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            margin-bottom: 32px;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            right: -60px;
            top: -60px;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            pointer-events: none;
        }

        .welcome-text h1 {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .welcome-text p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.85);
            max-width: 580px;
            line-height: 1.5;
        }

        .welcome-stats-badges {
            display: flex;
            gap: 12px;
            margin-top: 18px;
        }

        .banner-pill {
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(10px);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .banner-action-btn {
            background: #ffffff;
            color: var(--primary-dark);
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .banner-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            background: #f8fafc;
        }

        /* KPI Metric Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .kpi-card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: 24px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.25s ease;
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: rgba(108, 92, 231, 0.3);
        }

        .kpi-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .icon-purple {
            background: rgba(108, 92, 231, 0.1);
            color: var(--primary);
        }

        .icon-teal {
            background: rgba(0, 206, 201, 0.1);
            color: var(--accent);
        }

        .icon-green {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .icon-amber {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .kpi-data h4 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1.2;
        }

        .kpi-data p {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 2px;
        }

        /* Section Title Header */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .section-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h3 i {
            color: var(--primary);
        }

        /* Courses Cards Grid */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .course-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
        }

        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: rgba(108, 92, 231, 0.3);
        }

        .course-thumb-wrapper {
            height: 160px;
            position: relative;
            overflow: hidden;
            background: #e2e8f0;
        }

        .course-thumb {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .course-card:hover .course-thumb {
            transform: scale(1.05);
        }

        .category-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(8px);
            color: #ffffff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .price-badge {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: var(--primary);
            color: #ffffff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .course-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .course-code-tag {
            font-size: 11px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .course-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
            line-height: 1.35;
        }

        .course-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 16px;
            flex: 1;
        }

        .progress-wrapper {
            margin-bottom: 16px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .progress-bar-bg {
            height: 8px;
            background: #f1f5f9;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 10px;
            transition: width 0.4s ease;
        }

        .course-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid #f1f5f9;
        }

        .course-duration {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-action {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            padding: 8px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-action:hover {
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.4);
            transform: translateY(-1px);
        }

        .btn-enrolled {
            background: #dcfce7;
            color: #15803d;
            cursor: default;
            font-weight: 700;
        }

        /* Attendance & Assignments Layout Grid */
        .content-grid-2col {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 24px;
            margin-bottom: 40px;
        }

        .content-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        /* Attendance Table */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .attendance-table th {
            text-align: left;
            padding: 12px 14px;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
            background: #f8fafc;
        }

        .attendance-table td {
            padding: 14px;
            font-size: 13px;
            color: var(--text-dark);
            border-bottom: 1px solid #f1f5f9;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-present {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-late {
            background: #fef9c3;
            color: #a16207;
        }

        /* Modal Popup Design System */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(6px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-card {
            background: #ffffff;
            border-radius: 20px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: modalSlide 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes modalSlide {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 24px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h4 {
            font-size: 18px;
            font-weight: 700;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.35);
        }

        .modal-body {
            padding: 24px;
        }

        .payment-option-card {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .payment-option-card:hover, .payment-option-card.selected {
            border-color: var(--primary);
            background: rgba(108, 92, 231, 0.04);
        }

        .payment-option-card input[type="radio"] {
            margin-top: 4px;
            accent-color: var(--primary);
        }

        .payment-info h5 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 2px;
        }

        .payment-info p {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .bkash-details-box {
            background: #fff0f5;
            border: 1px dashed #e60067;
            border-radius: 12px;
            padding: 16px;
            margin-top: 14px;
            display: none;
        }

        .bkash-number-badge {
            font-size: 18px;
            font-weight: 800;
            color: #e60067;
            letter-spacing: 1px;
            display: block;
            margin: 6px 0 10px;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.15);
        }

        .modal-footer {
            padding: 18px 24px;
            background: #f8fafc;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* Footer */
        .app-footer {
            background: var(--card-bg);
            border-top: 1px solid var(--border-color);
            padding: 20px 32px;
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
            margin-top: auto;
        }

        @media (max-width: 1024px) {
            .content-grid-2col {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="brand-text">
                <h3>EduLearn</h3>
                <span>Student Hub</span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="menu-label">Main Menu</li>
            <li class="menu-item">
                <a href="student_view.php" class="menu-link active">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#my-courses" class="menu-link">
                    <i class="fas fa-book-open"></i>
                    <span>My Enrolled Courses</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#catalog" class="menu-link">
                    <i class="fas fa-search"></i>
                    <span>Explore Courses</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#attendance" class="menu-link">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance Log</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#assignments" class="menu-link">
                    <i class="fas fa-tasks"></i>
                    <span>Assignments Hub</span>
                </a>
            </li>
            <li class="menu-label">User Account</li>
            <li class="menu-item">
                <a href="../../login.php" class="menu-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sign Out</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-pill">
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($studentName, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <h5><?php echo htmlspecialchars($studentName); ?></h5>
                    <span>Student ID: #STU-<?php echo sprintf('%04d', $actualUid); ?></span>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <!-- Top Navbar Header -->
        <header class="top-navbar">
            <div class="navbar-title">
                <h2>Student Dashboard</h2>
                <p>Welcome back, <?php echo htmlspecialchars($studentName); ?>! Explore curriculum & enroll in new courses.</p>
            </div>
            <div class="navbar-actions">
                <div class="badge-currency">
                    <i class="fas fa-wallet"></i>
                    <span>Currency: ৳ BDT</span>
                </div>
                <a href="../../login.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </header>

        <!-- Content Container -->
        <main class="content-container">
            <!-- Welcome Hero Banner -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1>Welcome Back, <?php echo htmlspecialchars($studentName); ?>! 👋</h1>
                    <p>Track your academic achievements, explore new industry-aligned courses, and complete instant enrollment via <strong>Cash on Delivery (COD)</strong> or <strong>bKash Mobile Financial Service</strong>.</p>
                    <div class="welcome-stats-badges">
                        <span class="banner-pill"><i class="fas fa-book"></i> <?php echo count($enrolledCourses); ?> Enrolled Courses</span>
                        <span class="banner-pill"><i class="fas fa-calendar-check"></i> <?php echo $attendanceRate; ?>% Attendance</span>
                        <span class="banner-pill"><i class="fas fa-award"></i> Active Student Status</span>
                    </div>
                </div>
                <div>
                    <a href="#catalog" class="banner-action-btn">
                        <i class="fas fa-plus-circle"></i> Enroll in New Course
                    </a>
                </div>
            </div>

            <!-- KPI Metric Cards Grid -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon icon-purple">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="kpi-data">
                        <h4><?php echo count($enrolledCourses); ?></h4>
                        <p>My Enrolled Courses</p>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon icon-teal">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="kpi-data">
                        <h4><?php echo $attendanceRate; ?>%</h4>
                        <p>Overall Attendance Rate</p>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon icon-green">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="kpi-data">
                        <h4><?php echo count($studentAssignments); ?></h4>
                        <p>Active Assignments</p>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon icon-amber">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="kpi-data">
                        <h4>3.92 / 4.0</h4>
                        <p>Academic GPA Average</p>
                    </div>
                </div>
            </div>

            <!-- Section 1: My Enrolled Courses -->
            <section id="my-courses" style="margin-bottom: 40px;">
                <div class="section-header">
                    <h3><i class="fas fa-book-open"></i> My Enrolled Courses & Progress</h3>
                </div>

                <?php if (!empty($enrolledCourses)): ?>
                    <div class="courses-grid">
                        <?php foreach ($enrolledCourses as $course): ?>
                            <?php 
                                $thumb = !empty($course['thumbnail']) ? (strpos($course['thumbnail'], 'PUBLIC/') === 0 ? '../../' . $course['thumbnail'] : $course['thumbnail']) : '../../PUBLIC/pic/img.jpg';
                                $fee = ($course['price'] == 0) ? 'FREE' : '৳ ' . number_format($course['price'], 2) . ' BDT';
                                $progress = isset($course['progress_percent']) ? intval($course['progress_percent']) : 0;
                                $code = !empty($course['course_code']) ? $course['course_code'] : ('CRS-' . sprintf('%03d', $course['id']));
                            ?>
                            <div class="course-card">
                                <div class="course-thumb-wrapper">
                                    <img src="<?php echo htmlspecialchars($thumb); ?>" class="course-thumb" alt="Course Thumbnail" onerror="this.src='../../PUBLIC/pic/img.jpg'">
                                    <span class="category-badge"><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                    <span class="price-badge"><?php echo htmlspecialchars($fee); ?></span>
                                </div>
                                <div class="course-body">
                                    <div class="course-code-tag"><?php echo htmlspecialchars($code); ?></div>
                                    <h4 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <p class="course-desc"><?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 90)) . '...'; ?></p>
                                    
                                    <div class="progress-wrapper">
                                        <div class="progress-header">
                                            <span>Progress</span>
                                            <span><?php echo $progress; ?>%</span>
                                        </div>
                                        <div class="progress-bar-bg">
                                            <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%;"></div>
                                        </div>
                                    </div>

                                    <div class="course-footer">
                                        <span class="course-duration"><i class="far fa-clock"></i> <?php echo htmlspecialchars($course['duration'] ?? '8 Weeks'); ?></span>
                                        <button class="btn-action btn-enrolled">
                                            <i class="fas fa-check-circle"></i> Enrolled
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="background: white; border-radius: 12px; padding: 32px; text-align: center; border: 1px solid var(--border-color);">
                        <i class="fas fa-info-circle" style="font-size: 32px; color: var(--primary); margin-bottom: 12px;"></i>
                        <h4 style="font-size: 16px; color: var(--text-dark); margin-bottom: 6px;">No Enrolled Courses Yet</h4>
                        <p style="font-size: 13px; color: var(--text-muted);">Browse the course catalog below and enroll using Cash on Delivery (COD) or bKash!</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Section 2: Explore Available Courses Catalog & Demo Enrollment -->
            <section id="catalog" style="margin-bottom: 40px;">
                <div class="section-header">
                    <h3><i class="fas fa-search"></i> Explore & Enroll in Available Courses</h3>
                    <span style="font-size: 13px; color: var(--text-muted);">Click "Enroll Now" to select payment method (COD or bKash)</span>
                </div>

                <div class="courses-grid">
                    <?php foreach ($catalogCourses as $c): ?>
                        <?php 
                            $isAlreadyEnrolled = in_array($c['id'], $enrolledCourseIds);
                            $thumb = !empty($c['thumbnail']) ? (strpos($c['thumbnail'], 'PUBLIC/') === 0 ? '../../' . $c['thumbnail'] : $c['thumbnail']) : '../../PUBLIC/pic/img.jpg';
                            $feeVal = (float)$c['price'];
                            $feeFormatted = ($feeVal == 0) ? 'FREE' : '৳ ' . number_format($feeVal, 2) . ' BDT';
                            $code = !empty($c['course_code']) ? $c['course_code'] : ('CRS-' . sprintf('%03d', $c['id']));
                        ?>
                        <div class="course-card">
                            <div class="course-thumb-wrapper">
                                <img src="<?php echo htmlspecialchars($thumb); ?>" class="course-thumb" alt="Course Thumbnail" onerror="this.src='../../PUBLIC/pic/img.jpg'">
                                <span class="category-badge"><?php echo htmlspecialchars($c['category_name'] ?? 'General'); ?></span>
                                <span class="price-badge"><?php echo htmlspecialchars($feeFormatted); ?></span>
                            </div>
                            <div class="course-body">
                                <div class="course-code-tag"><?php echo htmlspecialchars($code); ?></div>
                                <h4 class="course-title"><?php echo htmlspecialchars($c['title']); ?></h4>
                                <p class="course-desc"><?php echo htmlspecialchars(substr($c['description'] ?? '', 0, 95)) . '...'; ?></p>

                                <div class="course-footer">
                                    <span class="course-duration"><i class="far fa-clock"></i> <?php echo htmlspecialchars($c['duration'] ?? '8 Weeks'); ?></span>
                                    <?php if ($isAlreadyEnrolled): ?>
                                        <button class="btn-action btn-enrolled">
                                            <i class="fas fa-check-circle"></i> Enrolled
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-action" onclick="openEnrollModal(<?php echo $c['id']; ?>, '<?php echo addslashes(htmlspecialchars($c['title'])); ?>', '<?php echo $feeFormatted; ?>')">
                                            <i class="fas fa-plus-circle"></i> Enroll Now
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Section 3: Attendance Log & Assignments Hub -->
            <div class="content-grid-2col">
                <!-- Attendance Tracker Card -->
                <div class="content-card" id="attendance">
                    <div class="section-header" style="margin-bottom: 12px;">
                        <h3><i class="fas fa-calendar-check"></i> Attendance Tracker</h3>
                        <span style="font-size: 13px; color: var(--success); font-weight: 700;">
                            <i class="fas fa-check-circle"></i> <?php echo $presentCount; ?> / <?php echo $totalClasses; ?> Sessions Present
                        </span>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Course</th>
                                    <th>Topic</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceRecords as $record): ?>
                                    <tr>
                                        <td style="font-weight: 600; white-space: nowrap;"><?php echo htmlspecialchars($record['date']); ?></td>
                                        <td>
                                            <strong style="color: var(--primary); font-size: 12px; display: block;"><?php echo htmlspecialchars($record['code']); ?></strong>
                                            <span><?php echo htmlspecialchars($record['course']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['topic']); ?></td>
                                        <td>
                                            <?php if ($record['status'] === 'PRESENT'): ?>
                                                <span class="status-badge badge-present"><i class="fas fa-check"></i> Present</span>
                                            <?php else: ?>
                                                <span class="status-badge badge-late"><i class="fas fa-clock"></i> Late</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Assignments Hub Card -->
                <div class="content-card" id="assignments">
                    <div class="section-header" style="margin-bottom: 12px;">
                        <h3><i class="fas fa-tasks"></i> Assignments Hub</h3>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 14px;">
                        <?php foreach ($studentAssignments as $assign): ?>
                            <div style="padding: 14px; border: 1px solid var(--border-color); border-radius: 12px; background: #faf5ff;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <h5 style="font-size: 13px; font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($assign['title']); ?></h5>
                                    <span style="font-size: 11px; background: var(--primary); color: #fff; padding: 2px 8px; border-radius: 10px; font-weight: 700;">
                                        <?php echo htmlspecialchars($assign['course_code']); ?>
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted);">
                                    <span>Due Date: <strong><?php echo htmlspecialchars($assign['due_date']); ?></strong></span>
                                    <span>Marks: <?php echo intval($assign['total_marks']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="app-footer">
            <p>&copy; <?php echo date('Y'); ?> Online Learning Platform. All rights reserved. Registered Student Portal.</p>
        </footer>
    </div>

    <!-- Payment & Enrollment Modal -->
    <div class="modal-overlay" id="enrollModal">
        <div class="modal-card">
            <div class="modal-header">
                <h4><i class="fas fa-shopping-cart"></i> Complete Course Enrollment</h4>
                <button class="modal-close" onclick="closeEnrollModal()">&times;</button>
            </div>
            
            <form id="enrollmentForm" onsubmit="submitEnrollment(event)">
                <input type="hidden" id="modalCourseId" name="course_id" value="">
                
                <div class="modal-body">
                    <div style="background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 20px; border: 1px solid var(--border-color);">
                        <span style="font-size: 11px; font-weight: 700; color: var(--primary); text-transform: uppercase;">Selected Course</span>
                        <h4 id="modalCourseTitle" style="font-size: 16px; font-weight: 700; color: var(--text-dark); margin: 4px 0;"></h4>
                        <span id="modalCoursePrice" style="font-size: 15px; font-weight: 800; color: var(--primary-dark);"></span>
                    </div>

                    <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 10px;">Select Payment Method:</label>

                    <!-- Payment Option 1: Cash on Delivery -->
                    <label class="payment-option-card selected" id="optCOD" onclick="selectPaymentMethod('COD')">
                        <input type="radio" name="payment_method" value="COD" checked>
                        <div class="payment-info">
                            <h5>Cash on Delivery (COD)</h5>
                            <p>Pay cash upon course material/access receipt. Enrollment completes instantly with no further information required.</p>
                        </div>
                    </label>

                    <!-- Payment Option 2: bKash -->
                    <label class="payment-option-card" id="optBkash" onclick="selectPaymentMethod('bKash')">
                        <input type="radio" name="payment_method" value="bKash">
                        <div class="payment-info">
                            <h5>bKash Mobile Banking</h5>
                            <p>Send Money/Payment to our bKash Merchant number and enter your Transaction ID (TrxID) below.</p>
                        </div>
                    </label>

                    <!-- bKash Details Box -->
                    <div class="bkash-details-box" id="bkashBox">
                        <span style="font-size: 12px; color: #e60067; font-weight: 700;">bKash Merchant Account Number:</span>
                        <span class="bkash-number-badge"><i class="fas fa-mobile-alt"></i> 01799-887766</span>
                        <p style="font-size: 12px; color: #475569; margin-bottom: 10px;">Please transfer the exact fee amount via bKash and paste your 10-digit Transaction ID below:</p>
                        <input type="text" id="trxIdInput" name="transaction_id" class="form-control" placeholder="Enter bKash TrxID (e.g. TRX987654321)">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="logout-btn" onclick="closeEnrollModal()">Cancel</button>
                    <button type="submit" class="btn-action" id="submitEnrollBtn">
                        <i class="fas fa-check-circle"></i> Confirm & Complete Enrollment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript logic -->
    <script>
        function openEnrollModal(courseId, title, price) {
            document.getElementById('modalCourseId').value = courseId;
            document.getElementById('modalCourseTitle').textContent = title;
            document.getElementById('modalCoursePrice').textContent = price;
            
            // Reset modal payment state
            selectPaymentMethod('COD');
            document.getElementById('trxIdInput').value = '';
            document.getElementById('enrollModal').style.display = 'flex';
        }

        function closeEnrollModal() {
            document.getElementById('enrollModal').style.display = 'none';
        }

        function selectPaymentMethod(method) {
            const optCOD = document.getElementById('optCOD');
            const optBkash = document.getElementById('optBkash');
            const bkashBox = document.getElementById('bkashBox');

            if (method === 'bKash') {
                optCOD.classList.remove('selected');
                optBkash.classList.add('selected');
                optBkash.querySelector('input[type="radio"]').checked = true;
                bkashBox.style.display = 'block';
            } else {
                optBkash.classList.remove('selected');
                optCOD.classList.add('selected');
                optCOD.querySelector('input[type="radio"]').checked = true;
                bkashBox.style.display = 'none';
            }
        }

        function submitEnrollment(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitEnrollBtn');
            const courseId = document.getElementById('modalCourseId').value;
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            const trxId = document.getElementById('trxIdInput').value.trim();

            if (paymentMethod === 'bKash' && !trxId) {
                alert('Please enter your bKash Transaction ID (TrxID) to complete enrollment.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Enrollment...';

            const formData = new FormData();
            formData.append('action', 'enroll');
            formData.append('course_id', courseId);
            formData.append('payment_method', paymentMethod);
            formData.append('transaction_id', trxId);

            fetch('../../CONTROLLERS/enrollment_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm & Complete Enrollment';

                if (data.status === 'success') {
                    alert(data.message);
                    closeEnrollModal();
                    // Refresh dashboard so new enrolled course appears in My Enrolled Courses section
                    window.location.reload();
                } else {
                    alert(data.message || 'Enrollment failed. Please try again.');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm & Complete Enrollment';
                console.error('Enrollment Error:', err);
                alert('An error occurred while processing enrollment. Please try again.');
            });
        }
    </script>
</body>
</html>
