<?php
require_once __DIR__ . '/../../DATABASE/db_connection.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = getPgPDO();

// Fetch courses from database
$query = "SELECT c.*, cat.name as category_name 
          FROM courses c 
          LEFT JOIN categories cat ON c.category_id = cat.id 
          ORDER BY c.created_at DESC";

try {
    $stmt = $conn->query($query);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $courses = [];
}

// Check user role
$userRole = $_SESSION['user_type'] ?? 'student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Catalog - Online Learning Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary-color: #9a47f8;
            --secondary-color: #4caf50;
            --text-color: #333;
            --bg-color: #f4f6f9;
            --white: #ffffff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --sidebar-color: #9a47f8;
            --sidebar-hover: #b47af9;
        }

        body {
            background: var(--bg-color);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 70px;
            background: var(--sidebar-color);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px 10px;
            box-shadow: var(--shadow);
            transition: width 0.3s ease;
            overflow: hidden;
            z-index: 100;
        }

        .sidebar:hover {
            width: 250px;
        }

        .sidebar h2 {
            color: var(--white);
            margin-bottom: 30px;
            text-align: center;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar:hover h2 {
            opacity: 1;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li {
            margin-bottom: 15px;
        }

        .sidebar ul li a {
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .sidebar ul li a:hover, .sidebar ul li.active a {
            background: var(--sidebar-hover);
        }

        .sidebar ul li a i {
            margin-right: 10px;
            font-size: 20px;
            min-width: 30px;
            text-align: center;
        }

        .sidebar ul li a span {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar:hover ul li a span {
            opacity: 1;
        }

        /* Main Content */
        .main-content {
            margin-left: 70px;
            width: calc(100% - 70px);
            padding: 30px;
            transition: margin-left 0.3s ease, width 0.3s ease;
            min-height: 100vh;
        }

        .sidebar:hover + .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        .header {
            background: var(--white);
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-title h1 {
            font-size: 24px;
            color: #1e293b;
        }

        .header-title p {
            font-size: 14px;
            color: #64748b;
        }

        .action-btns {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        /* Course Search & Filter Bar */
        .search-filter-bar {
            background: var(--white);
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input-wrap {
            position: relative;
            flex: 1;
            min-width: 250px;
        }

        .search-input-wrap input {
            width: 100%;
            padding: 10px 16px 10px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }

        .search-input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        /* Courses Grid */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }

        .course-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .course-img {
            height: 180px;
            position: relative;
            overflow: hidden;
            background: #e2e8f0;
        }

        .course-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .badge-category {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(15, 23, 42, 0.75);
            color: #fff;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-price {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: var(--secondary-color);
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }

        .course-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .course-body h3 {
            font-size: 18px;
            color: #1e293b;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .course-body p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .course-footer {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .duration-info {
            font-size: 13px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .enroll-link {
            background: var(--primary-color);
            color: white;
            padding: 6px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .enroll-link:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <h2>Learning App</h2>
        <ul>
            <li><a href="../../index.php"><i class="fas fa-home"></i> <span>Home</span></a></li>
            <?php if ($userRole === 'admin'): ?>
                <li><a href="Admin_view.php"><i class="fas fa-users-cog"></i> <span>Admin Users</span></a></li>
                <li><a href="courses_admin.php"><i class="fas fa-edit"></i> <span>Manage Courses</span></a></li>
            <?php elseif ($userRole === 'teacher'): ?>
                <li><a href="teacher_view.php"><i class="fas fa-chalkboard-teacher"></i> <span>Teacher Dashboard</span></a></li>
                <li><a href="courses_admin.php"><i class="fas fa-edit"></i> <span>Manage Courses</span></a></li>
            <?php else: ?>
                <li><a href="student_view.php"><i class="fas fa-user-graduate"></i> <span>Student Dashboard</span></a></li>
            <?php endif; ?>
            <li class="active"><a href="courses.php"><i class="fas fa-book"></i> <span>Course Catalog</span></a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="header-title">
                <h1><i class="fas fa-graduation-cap" style="color: var(--primary-color);"></i> Course Catalog</h1>
                <p>Browse all available online courses and expand your skills</p>
            </div>
            <div class="action-btns">
                <?php if ($userRole === 'admin' || $userRole === 'teacher'): ?>
                    <a href="courses_admin.php" class="btn btn-primary">
                        <i class="fas fa-cog"></i> Course Management (CRUD)
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-filter-bar">
            <div class="search-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="courseSearch" placeholder="Search courses by title or description...">
            </div>
        </div>

        <!-- Courses Grid -->
        <div class="courses-grid" id="courseGrid">
            <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $c): ?>
                    <?php 
                        $imgPath = !empty($c['thumbnail']) ? (strpos($c['thumbnail'], 'PUBLIC/') === 0 ? '../../' . $c['thumbnail'] : $c['thumbnail']) : '../../PUBLIC/pic/img.jpg';
                    ?>
                    <div class="course-card">
                        <div class="course-img">
                            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="Course Image" onerror="this.src='../../PUBLIC/pic/img.jpg'">
                            <span class="badge-category"><?php echo htmlspecialchars($c['category_name'] ?? 'General'); ?></span>
                            <span class="badge-price">BDT <?php echo number_format($c['price'] ?? 0, 2); ?></span>
                        </div>
                        <div class="course-body">
                            <h3><?php echo htmlspecialchars($c['title']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($c['description'] ?? '', 0, 100)) . '...'; ?></p>
                            <div class="course-footer">
                                <span class="duration-info">
                                    <i class="far fa-clock"></i> <?php echo htmlspecialchars($c['duration'] ?? 'N/A'); ?>
                                </span>
                                <a href="../../login.php" class="enroll-link">Enroll Now</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default Preset Courses if DB table is empty -->
                <div class="course-card">
                    <div class="course-img">
                        <img src="https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=600&q=80" alt="Web Dev">
                        <span class="badge-category">Web Development</span>
                        <span class="badge-price">BDT 1,500.00</span>
                    </div>
                    <div class="course-body">
                        <h3>Full Stack Modern Web Development</h3>
                        <p>Master HTML5, CSS3, JavaScript, PHP, PDO, PostgreSQL, and modern responsive UI frameworks.</p>
                        <div class="course-footer">
                            <span class="duration-info"><i class="far fa-clock"></i> 12 Weeks</span>
                            <a href="../../login.php" class="enroll-link">Enroll Now</a>
                        </div>
                    </div>
                </div>

                <div class="course-card">
                    <div class="course-img">
                        <img src="https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=600&q=80" alt="Python">
                        <span class="badge-category">Python & AI</span>
                        <span class="badge-price">Free</span>
                    </div>
                    <div class="course-body">
                        <h3>Python Programming & Machine Learning</h3>
                        <p>From core syntax to Machine Learning models, Neural Networks, Pandas, NumPy, and Scikit-Learn.</p>
                        <div class="course-footer">
                            <span class="duration-info"><i class="far fa-clock"></i> 8 Weeks</span>
                            <a href="../../login.php" class="enroll-link">Enroll Now</a>
                        </div>
                    </div>
                </div>

                <div class="course-card">
                    <div class="course-img">
                        <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=600&q=80" alt="Data Science">
                        <span class="badge-category">Data Science</span>
                        <span class="badge-price">BDT 2,000.00</span>
                    </div>
                    <div class="course-body">
                        <h3>Data Analytics & Business Intelligence</h3>
                        <p>Transform raw relational databases into interactive PowerBI & Tableau dashboards with SQL.</p>
                        <div class="course-footer">
                            <span class="duration-info"><i class="far fa-clock"></i> 10 Weeks</span>
                            <a href="../../login.php" class="enroll-link">Enroll Now</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#courseSearch').on('keyup', function() {
                const term = $(this).val().toLowerCase();
                $('.course-card').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(term));
                });
            });
        });
    </script>
</body>
</html>
