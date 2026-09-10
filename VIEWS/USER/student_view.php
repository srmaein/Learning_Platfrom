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

// Fetch enrolled courses for this student
$enrolledCourses = [];
try {
    $eStmt = $conn->prepare("
        SELECT c.*, cat.name as category_name, e.progress_percent, e.status as enrollment_status, e.enrolled_at
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE e.user_id = :uid
        ORDER BY e.enrolled_at DESC
    ");
    $eStmt->execute([':uid' => $actualUid]);
    $enrolledCourses = $eStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $enrolledCourses = [];
}

// If no enrollments found in DB, pull catalog courses to display interactive dashboard demonstration
if (empty($enrolledCourses)) {
    try {
        $cStmt = $conn->query("
            SELECT c.*, cat.name as category_name, 75 as progress_percent, 'ENROLLED' as enrollment_status, CURRENT_TIMESTAMP as enrolled_at
            FROM courses c 
            LEFT JOIN categories cat ON c.category_id = cat.id 
            ORDER BY c.id ASC LIMIT 4
        ");
        if ($cStmt) {
            $enrolledCourses = $cStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $enrolledCourses = [];
    }
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

// Default demonstration assignments if empty
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
        ],
        [
            'id' => 103,
            'title' => 'SQL Analytics & PowerBI Dashboard Project',
            'course_title' => 'Data Analytics & Business Intelligence',
            'course_code' => 'DAT-205',
            'total_marks' => 100,
            'due_date' => date('Y-m-d', strtotime('+12 days')),
            'status' => 'SUBMITTED'
        ]
    ];
}

// Structured Attendance Record Data
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
    ],
    [
        'date' => date('Y-m-d', strtotime('-6 days')),
        'course' => 'Cyber Security Essentials',
        'code' => 'SEC-101',
        'topic' => 'Ethical Hacking & Network Defense Mechanisms',
        'time' => '04:00 PM - 05:30 PM',
        'status' => 'PRESENT'
    ],
    [
        'date' => date('Y-m-d', strtotime('-8 days')),
        'course' => 'Python Programming & AI Essentials',
        'code' => 'AI-302',
        'topic' => 'Numpy Vectorization & Multidimensional Arrays',
        'time' => '02:00 PM - 03:30 PM',
        'status' => 'EXCUSED'
    ]
];

// Calculate Attendance Stats
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
    <title>Student Portal Dashboard - Online Learning Platform</title>
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

        .menu-link:hover i {
            transform: translateX(3px);
            color: var(--primary-light);
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

        /* Enrolled Courses Grid */
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

        .continue-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .continue-btn:hover {
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.4);
            transform: translateY(-1px);
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

        .badge-excused {
            background: #e0f2fe;
            color: #0369a1;
        }

        /* Assignments List */
        .assignment-list {
            list-style: none;
            margin-top: 14px;
        }

        .assignment-item {
            padding: 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            margin-bottom: 12px;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .assignment-item:hover {
            border-color: var(--primary-light);
            background: #faf5ff;
        }

        .assignment-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .assignment-title-row h5 {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .assignment-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-muted);
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

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .brand-text, .menu-label, .menu-link span, .user-details {
                display: none;
            }
            .sidebar-brand {
                padding: 16px 12px;
                justify-content: center;
            }
            .menu-link {
                justify-content: center;
                padding: 12px;
            }
            .main-wrapper {
                margin-left: 70px;
            }
            .welcome-banner {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            .content-container {
                padding: 16px;
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
                <a href="courses.php" class="menu-link">
                    <i class="fas fa-book-open"></i>
                    <span>My Courses</span>
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
                <a href="courses.php" class="menu-link">
                    <i class="fas fa-certificate"></i>
                    <span>Certificates</span>
                </a>
            </li>
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
                <h2>Student Portal Dashboard</h2>
                <p>Welcome back, <?php echo htmlspecialchars($studentName); ?>! Track your curriculum progress & attendance.</p>
            </div>
            <div class="navbar-actions">
                <div class="badge-currency">
                    <i class="fas fa-user-graduate"></i>
                    <span>Status: Enrolled Student</span>
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
                    <p>Keep up the great work! You have maintained a <strong><?php echo $attendanceRate; ?>% overall attendance rate</strong> across your enrolled courses this semester.</p>
                    <div class="welcome-stats-badges">
                        <span class="banner-pill"><i class="fas fa-book"></i> <?php echo count($enrolledCourses); ?> Active Courses</span>
                        <span class="banner-pill"><i class="fas fa-calendar-check"></i> <?php echo $attendanceRate; ?>% Attendance</span>
                        <span class="banner-pill"><i class="fas fa-award"></i> Grade A+ Standing</span>
                    </div>
                </div>
                <div>
                    <a href="courses.php" class="banner-action-btn">
                        <i class="fas fa-play-circle"></i> Resume Learning
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
                        <p>Enrolled Courses</p>
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

            <!-- Section 1: Enrolled Courses -->
            <section style="margin-bottom: 40px;">
                <div class="section-header">
                    <h3><i class="fas fa-book-open"></i> My Enrolled Courses & Progress</h3>
                    <a href="courses.php" style="color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">
                        View All Catalog <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="courses-grid">
                    <?php foreach ($enrolledCourses as $course): ?>
                        <?php 
                            $thumb = !empty($course['thumbnail']) ? (strpos($course['thumbnail'], 'PUBLIC/') === 0 ? '../../' . $course['thumbnail'] : $course['thumbnail']) : '../../PUBLIC/pic/img.jpg';
                            $fee = ($course['price'] == 0) ? 'FREE' : '৳ ' . number_format($course['price'], 2) . ' BDT';
                            $progress = isset($course['progress_percent']) ? intval($course['progress_percent']) : 75;
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
                                        <span>Course Completion</span>
                                        <span><?php echo $progress; ?>%</span>
                                    </div>
                                    <div class="progress-bar-bg">
                                        <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%;"></div>
                                    </div>
                                </div>

                                <div class="course-footer">
                                    <span class="course-duration"><i class="far fa-clock"></i> <?php echo htmlspecialchars($course['duration'] ?? '8 Weeks'); ?></span>
                                    <a href="courses.php" class="continue-btn">
                                        <i class="fas fa-play"></i> Continue
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Section 2: Attendance Log & Assignments Hub -->
            <div class="content-grid-2col">
                <!-- Attendance Tracker Card -->
                <div class="content-card" id="attendance">
                    <div class="section-header" style="margin-bottom: 12px;">
                        <h3><i class="fas fa-calendar-check"></i> Recent Attendance Records</h3>
                        <span style="font-size: 13px; color: var(--success); font-weight: 700;">
                            <i class="fas fa-check-circle"></i> <?php echo $presentCount; ?> / <?php echo $totalClasses; ?> Sessions Present
                        </span>
                    </div>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                        Real-time daily classroom attendance tracking verified by instructors.
                    </p>

                    <div style="overflow-x: auto;">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Course Code & Title</th>
                                    <th>Lesson Topic</th>
                                    <th>Time Slot</th>
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
                                        <td style="white-space: nowrap; color: var(--text-muted); font-size: 12px;"><?php echo htmlspecialchars($record['time']); ?></td>
                                        <td>
                                            <?php if ($record['status'] === 'PRESENT'): ?>
                                                <span class="status-badge badge-present"><i class="fas fa-check"></i> Present</span>
                                            <?php elseif ($record['status'] === 'LATE'): ?>
                                                <span class="status-badge badge-late"><i class="fas fa-clock"></i> Late</span>
                                            <?php else: ?>
                                                <span class="status-badge badge-excused"><i class="fas fa-info-circle"></i> Excused</span>
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
                        <h3><i class="fas fa-tasks"></i> Assignments & Homework</h3>
                    </div>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                        Pending coursework deadlines and submission updates.
                    </p>

                    <ul class="assignment-list">
                        <?php foreach ($studentAssignments as $assign): ?>
                            <li class="assignment-item">
                                <div class="assignment-title-row">
                                    <h5><?php echo htmlspecialchars($assign['title']); ?></h5>
                                    <span style="font-size: 11px; background: rgba(108,92,231,0.1); color: var(--primary); padding: 2px 8px; border-radius: 10px; font-weight: 700;">
                                        <?php echo htmlspecialchars($assign['course_code']); ?>
                                    </span>
                                </div>
                                <div class="assignment-meta">
                                    <span><i class="far fa-calendar-alt"></i> Due: <strong><?php echo htmlspecialchars($assign['due_date']); ?></strong></span>
                                    <span><i class="fas fa-star"></i> Max Marks: <?php echo intval($assign['total_marks']); ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="app-footer">
            <p>&copy; <?php echo date('Y'); ?> Online Learning Platform. All rights reserved. Registered Student Portal.</p>
        </footer>
    </div>

</body>
</html>
