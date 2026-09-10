<?php
// Fix XAMPP / Windows session save path warning before starting session
$savePath = session_save_path();
if (empty($savePath) || !is_dir($savePath) || !is_writable($savePath)) {
    $tempDir = sys_get_temp_dir();
    if (is_dir($tempDir) && is_writable($tempDir)) {
        @session_save_path($tempDir);
    }
}

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../../CONFIG/db_connect.php';

$db = get_school_db();
$teacherId = $_SESSION['user_id'] ?? 1;

// Fetch KPI Metrics
$activeCoursesCount = 0;
$activeAssignmentsCount = 0;
$totalStudentsEnrolled = 0;
$totalRevenueBdt = 0.00;

try {
    // 1. Active Courses Count
    $cCountStmt = $db->query("SELECT COUNT(*) FROM courses WHERE status = 'Active' OR is_published = 1");
    $activeCoursesCount = (int)$cCountStmt->fetchColumn();

    // 2. Active Assignments Count
    $aCountStmt = $db->query("SELECT COUNT(*) FROM assignments");
    $activeAssignmentsCount = (int)$aCountStmt->fetchColumn();

    // 3. Total Enrolled Students
    $eCountStmt = $db->query("SELECT COUNT(*) FROM enrollments");
    $totalStudentsEnrolled = (int)$eCountStmt->fetchColumn();
    if ($totalStudentsEnrolled === 0) {
        $totalStudentsEnrolled = 142; // Demo enrolled learners
    }

    // 4. Total Course Revenue / Fee Volume (in BDT ৳)
    $revStmt = $db->query("SELECT SUM(COALESCE(fee_amount, price)) FROM courses");
    $totalRevenueBdt = (float)$revStmt->fetchColumn();
} catch (Exception $e) {
    error_log("KPI calculation error: " . $e->getMessage());
}

// Fetch Courses List
$courses = [];
try {
    $coursesStmt = $db->query("
        SELECT c.*, 
               COALESCE(c.course_name, c.title) as display_name,
               COALESCE(c.fee_amount, c.price) as display_fee,
               (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as assignment_count
        FROM courses c 
        ORDER BY c.created_at DESC
    ");
    $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $courses = [];
}

// Fetch Assignments List
$assignments = [];
try {
    $assStmt = $db->query("
        SELECT a.*, COALESCE(c.course_name, c.title) as course_name, c.course_code
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        ORDER BY a.deadline ASC
    ");
    $assignments = $assStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $assignments = [];
}

// Flash Message Handling
$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management Dashboard - School Portal</title>
    <!-- Google Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        :root {
            --primary: #6c5ce7;
            --primary-dark: #4834d4;
            --primary-light: #a29bfe;
            --secondary: #00b894;
            --accent-pink: #fd79a8;
            --accent-cyan: #0984e3;
            --bg-main: #f4f6f9;
            --card-bg: #ffffff;
            --text-dark: #2d3436;
            --text-muted: #636e72;
            --border-color: #dfe6e9;
            --shadow-sm: 0 4px 15px rgba(108, 92, 231, 0.08);
            --shadow-md: 0 8px 25px rgba(108, 92, 231, 0.14);
            --sidebar-width: 260px;
            --sidebar-collapsed: 75px;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
        }

        /* Fixed Purple Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #4834d4 0%, #6c5ce7 100%);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px 15px;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            padding: 10px 5px 25px 5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            margin-bottom: 20px;
        }

        .sidebar-brand i {
            font-size: 28px;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 12px;
        }

        .sidebar-brand h2 {
            font-size: 18px;
            font-weight: 700;
            white-space: nowrap;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }

        .sidebar-menu li a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.25s ease;
            white-space: nowrap;
        }

        .sidebar-menu li a i {
            font-size: 20px;
            min-width: 24px;
            text-align: center;
        }

        .sidebar-menu li a:hover, .sidebar-menu li.active a {
            background: rgba(255, 255, 255, 0.22);
            color: #fff;
            transform: translateX(4px);
        }

        /* Main Content Wrapper */
        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            padding: 30px;
            transition: all 0.3s ease;
        }

        /* Header Bar */
        .top-bar {
            background: var(--card-bg);
            padding: 20px 30px;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .top-bar-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-bar-title p {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .top-bar-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(108, 92, 231, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(108, 92, 231, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #00b894 0%, #00876c 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 184, 148, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-light);
            color: var(--primary-dark);
        }

        .btn-outline:hover {
            background: var(--primary-light);
            color: #fff;
        }

        /* Banner Flash Alert */
        .alert-banner {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
        }

        .alert-banner.success {
            background: #d4edda;
            color: #155724;
            border-left: 6px solid #28a745;
        }

        .alert-banner.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 6px solid #dc3545;
        }

        /* KPI Row Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .kpi-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .kpi-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #fff;
            flex-shrink: 0;
        }

        .kpi-icon.purple { background: linear-gradient(135deg, #6c5ce7, #4834d4); }
        .kpi-icon.green { background: linear-gradient(135deg, #00b894, #00876c); }
        .kpi-icon.pink { background: linear-gradient(135deg, #fd79a8, #e84393); }
        .kpi-icon.blue { background: linear-gradient(135deg, #0984e3, #74b9ff); }

        .kpi-info h3 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1.2;
        }

        .kpi-info p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
            font-weight: 600;
        }

        /* Section Cards & Tables */
        .section-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 28px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 35px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f2f6;
        }

        .section-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Table Styling */
        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .custom-table th {
            background: #f8f9fe;
            color: #4a5568;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 18px;
            text-align: left;
        }

        .custom-table td {
            background: #fff;
            padding: 16px 18px;
            font-size: 14px;
            border-top: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
        }

        .custom-table tr td:first-child {
            border-left: 1px solid #edf2f7;
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }

        .custom-table tr td:last-child {
            border-right: 1px solid #edf2f7;
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .custom-table tr:hover td {
            background: #faf5ff;
        }

        /* Badges */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }

        .badge-active { background: #e6fffa; color: #047857; }
        .badge-upcoming { background: #feefc3; color: #b45309; }
        .badge-archived { background: #edf2f7; color: #4a5568; }
        .badge-fee { background: #f3e8ff; color: #6b21a8; font-weight: 800; }

        /* Modal Overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-box {
            background: #fff;
            width: 100%;
            max-width: 560px;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            max-height: 90vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h3 {
            font-size: 20px;
            color: var(--primary-dark);
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 22px;
            color: var(--text-muted);
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #4a5568;
            margin-bottom: 6px;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.25s ease;
        }

        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            border-color: var(--primary);
        }

        .currency-input-wrap {
            position: relative;
        }

        .currency-symbol {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 800;
            color: var(--primary-dark);
        }

        .currency-input-wrap input {
            padding-left: 38px;
        }

        /* Detail Modal Specs */
        .preview-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f2f6;
            font-size: 14px;
        }

        .preview-detail-row span.label {
            color: var(--text-muted);
            font-weight: 600;
        }

        .preview-detail-row span.val {
            font-weight: 700;
            color: var(--text-dark);
        }
    </style>
</head>
<body>

    <!-- Interconnected Purple Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="bx bxs-school"></i>
            <h2>School Management</h2>
        </div>
        <ul class="sidebar-menu">
            <li class="active"><a href="#overview"><i class="bx bxs-dashboard"></i> <span>Dashboard Overview</span></a></li>
            <li><a href="#courses"><i class="bx bxs-book-bookmark"></i> <span>My Courses</span></a></li>
            <li><a href="javascript:void(0)" onclick="openCourseModal()"><i class="bx bx-plus-circle"></i> <span>Create New Course</span></a></li>
            <li><a href="#assignments"><i class="bx bxs-file-doc"></i> <span>Assignments Hub</span></a></li>
            <li><a href="javascript:void(0)" onclick="openAssignmentModal()"><i class="bx bx-task"></i> <span>Post New Assignment</span></a></li>
            <li style="margin-top: auto;"><a href="teacher_view.php"><i class="bx bx-left-arrow-circle"></i> <span>Teacher Directory</span></a></li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">

        <!-- Top Navigation Bar -->
        <div class="top-bar">
            <div class="top-bar-title">
                <h1><i class="bx bxs-graduation" style="color: var(--primary);"></i> Teacher Control Dashboard</h1>
                <p>Manage curriculum, pricing in Bangladeshi Taka (৳ BDT), assignments, and student performance.</p>
            </div>
            <div class="top-bar-actions">
                <button class="btn btn-primary" onclick="openCourseModal()"><i class="bx bx-plus"></i> Add Course</button>
                <button class="btn btn-success" onclick="openAssignmentModal()"><i class="bx bx-upload"></i> Post Assignment</button>
            </div>
        </div>

        <!-- Banner Flash Alerts -->
        <?php if (!empty($flashMessage)): ?>
            <div class="alert-banner <?php echo htmlspecialchars($flashMessage['type']); ?>">
                <span><i class="bx <?php echo $flashMessage['type'] === 'success' ? 'bxs-check-circle' : 'bxs-error-circle'; ?>"></i> <?php echo htmlspecialchars($flashMessage['message']); ?></span>
                <i class="bx bx-x" onclick="this.parentElement.remove()" style="cursor:pointer; font-size:20px;"></i>
            </div>
        <?php endif; ?>

        <!-- KPI Cards Row -->
        <div id="overview" class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon purple"><i class="bx bxs-book-content"></i></div>
                <div class="kpi-info">
                    <h3><?php echo $activeCoursesCount; ?></h3>
                    <p>Total Active Courses</p>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon green"><i class="bx bxs-select-multiple"></i></div>
                <div class="kpi-info">
                    <h3><?php echo $activeAssignmentsCount; ?></h3>
                    <p>Active Assignments</p>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon pink"><i class="bx bxs-user-detail"></i></div>
                <div class="kpi-info">
                    <h3><?php echo number_format($totalStudentsEnrolled); ?></h3>
                    <p>Total Enrolled Students</p>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon blue"><i class="bx bx-money-withdraw"></i></div>
                <div class="kpi-info">
                    <h3>৳ <?php echo number_format($totalRevenueBdt, 2); ?> BDT</h3>
                    <p>Total Fee Revenue</p>
                </div>
            </div>
        </div>

        <!-- "My Courses" Section -->
        <div id="courses" class="section-card">
            <div class="section-header">
                <h2><i class="bx bxs-book-alt"></i> My Courses & Pricing (৳ BDT)</h2>
                <button class="btn btn-primary" onclick="openCourseModal()"><i class="bx bx-plus"></i> Create Course</button>
            </div>

            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Fee Amount (৳ BDT)</th>
                        <th>Assignments</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($courses)): ?>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($c['course_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($c['display_name']); ?></td>
                                <td><span class="badge badge-fee">৳ <?php echo number_format((float)$c['display_fee'], 2); ?> BDT</span></td>
                                <td><i class="bx bx-task"></i> <?php echo (int)$c['assignment_count']; ?> Tasks</td>
                                <td>
                                    <?php 
                                        $st = $c['status'] ?? 'Active';
                                        $badgeClass = strtolower($st) === 'active' ? 'badge-active' : (strtolower($st) === 'upcoming' ? 'badge-upcoming' : 'badge-archived');
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($st); ?></span>
                                </td>
                                <td>
                                    <button onclick="previewCourse(<?php echo $c['id']; ?>)" class="btn btn-outline" style="padding:5px 12px; font-size:12px;"><i class="bx bx-show"></i> Details</button>
                                    <button onclick="deleteCourse(<?php echo $c['id']; ?>)" class="btn" style="background:#ff7675; color:white; padding:5px 12px; font-size:12px;"><i class="bx bx-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 25px; color: var(--text-muted);">
                                No course records found. Click <strong>Create Course</strong> to add your first course with pricing.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- "Assignments Hub" Section -->
        <div id="assignments" class="section-card">
            <div class="section-header">
                <h2><i class="bx bxs-file-doc"></i> Assignments Hub</h2>
                <button class="btn btn-success" onclick="openAssignmentModal()"><i class="bx bx-plus"></i> Post Assignment</button>
            </div>

            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Assignment Title</th>
                        <th>Deadline</th>
                        <th>Total Marks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($assignments)): ?>
                        <?php foreach ($assignments as $a): ?>
                            <tr>
                                <td><span class="badge badge-fee"><?php echo htmlspecialchars($a['course_code']); ?></span> <?php echo htmlspecialchars($a['course_name']); ?></td>
                                <td><strong><?php echo htmlspecialchars($a['title']); ?></strong></td>
                                <td><i class="bx bx-time"></i> <?php echo date('M d, Y - H:i', strtotime($a['deadline'])); ?></td>
                                <td><span class="badge badge-active"><?php echo (int)$a['total_marks']; ?> Marks</span></td>
                                <td>
                                    <button onclick="previewAssignment(<?php echo $a['id']; ?>)" class="btn btn-outline" style="padding:5px 12px; font-size:12px;"><i class="bx bx-paper-plane"></i> View Details</button>
                                    <button onclick="deleteAssignment(<?php echo $a['id']; ?>)" class="btn" style="background:#ff7675; color:white; padding:5px 12px; font-size:12px;"><i class="bx bx-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 25px; color: var(--text-muted);">
                                No active assignments found. Click <strong>Post Assignment</strong> to add coursework.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Create Course Modal -->
    <div id="courseModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="bx bxs-book-add"></i> Create New Course</h3>
                <button class="modal-close" onclick="closeCourseModal()">&times;</button>
            </div>
            <form action="../../CONTROLLERS/course_controller.php?action=create_course" method="POST" onsubmit="return validateCourseForm(this)">
                <div class="form-group">
                    <label>Course Title</label>
                    <input type="text" name="course_name" required placeholder="e.g. Full Stack Web Development with PHP">
                </div>
                <div class="form-group">
                    <label>Course Code (Unique)</label>
                    <input type="text" name="course_code" required placeholder="e.g. CSE-401">
                </div>
                <div class="form-group">
                    <label>Course Fee Amount (in Bangladeshi Taka ৳ BDT)</label>
                    <div class="currency-input-wrap">
                        <span class="currency-symbol">৳</span>
                        <input type="number" step="0.01" min="0" name="fee_amount" required value="15000.00" placeholder="0.00">
                    </div>
                </div>
                <div class="form-group">
                    <label>Course Status</label>
                    <select name="status">
                        <option value="Active">Active</option>
                        <option value="Upcoming">Upcoming</option>
                        <option value="Archived">Archived</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description & Syllabus Outline</label>
                    <textarea name="description" rows="3" placeholder="Course outline, prerequisites, and learning objectives..."></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn btn-outline" onclick="closeCourseModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-check-circle"></i> Save Course & Fee</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Post Assignment Modal -->
    <div id="assignmentModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="bx bxs-task"></i> Post New Assignment</h3>
                <button class="modal-close" onclick="closeAssignmentModal()">&times;</button>
            </div>
            <form action="../../CONTROLLERS/assignment_controller.php?action=create_assignment" method="POST">
                <div class="form-group">
                    <label>Select Active Course</label>
                    <select name="course_id" required>
                        <option value="">-- Choose Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['display_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Assignment Title</label>
                    <input type="text" name="title" required placeholder="e.g. PHP MVC Routing Architecture Implementation">
                </div>
                <div class="form-group">
                    <label>Instructions & Submission Specs</label>
                    <textarea name="description" rows="3" placeholder="Detailed requirements and code structure instructions..."></textarea>
                </div>
                <div class="form-group">
                    <label>Submission Deadline</label>
                    <input type="datetime-local" name="deadline" required value="<?php echo date('Y-m-d\TH:i', strtotime('+7 days')); ?>">
                </div>
                <div class="form-group">
                    <label>Total Marks</label>
                    <input type="number" name="total_marks" required value="100">
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn btn-outline" onclick="closeAssignmentModal()">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bx bx-upload"></i> Publish Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Interactive Demo Preview Modal -->
    <div id="previewModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 600px;">
            <div class="modal-header">
                <h3 id="previewTitle">Item Preview</h3>
                <button class="modal-close" onclick="closePreviewModal()">&times;</button>
            </div>
            <div id="previewBody">
                <p style="text-align:center; color:#999;">Loading preview details...</p>
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:20px;">
                <button class="btn btn-primary" onclick="closePreviewModal()">Close Preview</button>
            </div>
        </div>
    </div>

    <script>
        function openCourseModal() {
            document.getElementById('courseModal').style.display = 'flex';
        }

        function closeCourseModal() {
            document.getElementById('courseModal').style.display = 'none';
        }

        function openAssignmentModal() {
            document.getElementById('assignmentModal').style.display = 'flex';
        }

        function closeAssignmentModal() {
            document.getElementById('assignmentModal').style.display = 'none';
        }

        function openPreviewModal() {
            document.getElementById('previewModal').style.display = 'flex';
        }

        function closePreviewModal() {
            document.getElementById('previewModal').style.display = 'none';
        }

        function validateCourseForm(form) {
            const fee = parseFloat(form.fee_amount.value);
            if (isNaN(fee) || fee < 0) {
                alert("Please enter a valid amount in Bangladeshi Taka (৳).");
                return false;
            }
            return true;
        }

        function deleteCourse(id) {
            if (confirm("Are you sure you want to delete this course and all associated assignments?")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../CONTROLLERS/course_controller.php?action=delete_course';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'course_id';
                input.value = id;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteAssignment(id) {
            if (confirm("Are you sure you want to delete this assignment?")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../CONTROLLERS/assignment_controller.php?action=delete_assignment';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'assignment_id';
                input.value = id;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function previewCourse(id) {
            document.getElementById('previewTitle').innerText = "Course Specifications";
            document.getElementById('previewBody').innerHTML = `<p style="text-align:center; padding:20px;">Fetching course details...</p>`;
            openPreviewModal();

            fetch(`../../CONTROLLERS/course_controller.php?action=get_details&id=${id}`)
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        const d = res.data;
                        const feeFormatted = new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT' }).format(d.fee_amount || d.price);
                        
                        let html = `
                            <div class="preview-detail-row"><span class="label">Course Code</span><span class="val">${escapeHtml(d.course_code)}</span></div>
                            <div class="preview-detail-row"><span class="label">Course Title</span><span class="val">${escapeHtml(d.course_name || d.title)}</span></div>
                            <div class="preview-detail-row"><span class="label">Course Fee (৳ BDT)</span><span class="val" style="color:var(--primary);">${feeFormatted}</span></div>
                            <div class="preview-detail-row"><span class="label">Status</span><span class="val">${escapeHtml(d.status || 'Active')}</span></div>
                            <div class="preview-detail-row"><span class="label">Total Assignments</span><span class="val">${d.assignment_count} Active Tasks</span></div>
                            <div style="margin-top:15px;">
                                <strong>Description & Outline:</strong>
                                <p style="font-size:13px; color:#636e72; margin-top:6px; line-height:1.5;">${escapeHtml(d.description || 'No detailed syllabus outline specified.')}</p>
                            </div>
                        `;
                        document.getElementById('previewBody').innerHTML = html;
                    } else {
                        document.getElementById('previewBody').innerHTML = `<p style="color:red; text-align:center;">${res.message}</p>`;
                    }
                })
                .catch(err => {
                    document.getElementById('previewBody').innerHTML = `<p style="color:red; text-align:center;">Failed to load details.</p>`;
                });
        }

        function previewAssignment(id) {
            document.getElementById('previewTitle').innerText = "Assignment Details & Countdown";
            document.getElementById('previewBody').innerHTML = `<p style="text-align:center; padding:20px;">Fetching assignment details...</p>`;
            openPreviewModal();

            fetch(`../../CONTROLLERS/assignment_controller.php?action=get_details&id=${id}`)
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        const d = res.data;
                        let html = `
                            <div class="preview-detail-row"><span class="label">Course</span><span class="val">[${escapeHtml(d.course_code)}] ${escapeHtml(d.course_name)}</span></div>
                            <div class="preview-detail-row"><span class="label">Assignment Title</span><span class="val">${escapeHtml(d.title)}</span></div>
                            <div class="preview-detail-row"><span class="label">Total Marks</span><span class="val">${d.total_marks} Marks</span></div>
                            <div class="preview-detail-row"><span class="label">Submission Deadline</span><span class="val">${escapeHtml(d.deadline)}</span></div>
                            <div class="preview-detail-row"><span class="label">Time Remaining / Status</span><span class="val" style="color:#00b894;">${escapeHtml(d.time_remaining)}</span></div>
                            <div style="margin-top:15px;">
                                <strong>Instructions:</strong>
                                <p style="font-size:13px; color:#636e72; margin-top:6px; line-height:1.5;">${escapeHtml(d.description || 'No special submission instructions.')}</p>
                            </div>
                        `;
                        document.getElementById('previewBody').innerHTML = html;
                    } else {
                        document.getElementById('previewBody').innerHTML = `<p style="color:red; text-align:center;">${res.message}</p>`;
                    }
                })
                .catch(err => {
                    document.getElementById('previewBody').innerHTML = `<p style="color:red; text-align:center;">Failed to load details.</p>`;
                });
        }

        function escapeHtml(str) {
            return str ? str.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])) : '';
        }
    </script>
</body>
</html>
