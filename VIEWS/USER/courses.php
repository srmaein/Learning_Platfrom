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
        .btn-success {
            background: #2ecc71;
            color: white;
        }

        .btn-success:hover {
            background: #27ae60;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-info {
            background: #3498db;
            color: white;
        }

        .card-actions {
            display: flex;
            gap: 6px;
        }

        /* Modal styling */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-card {
            background: #fff;
            width: 550px;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #1e293b;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
        }

        .image-preview-box {
            width: 100%;
            height: 120px;
            border: 2px dashed #cbd5e1;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-top: 8px;
            background: #f8fafc;
        }

        .image-preview-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
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
                <button class="btn btn-success" onclick="openAddCourseModal()">
                    <i class="fas fa-plus-circle"></i> + Add New Course
                </button>
                <?php if ($userRole === 'admin' || $userRole === 'teacher'): ?>
                    <a href="courses_admin.php" class="btn btn-primary">
                        <i class="fas fa-cog"></i> Advanced Admin Table
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
                        $jsonCourse = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="course-card" data-id="<?php echo $c['id']; ?>">
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
                                <div class="card-actions">
                                    <button onclick="editCourseFromCard(<?php echo $jsonCourse; ?>)" class="btn btn-info" style="padding: 6px 12px; font-size: 12px;">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button onclick="deleteCourseFromCard(<?php echo $c['id']; ?>)" class="btn btn-danger" style="padding: 6px 10px; font-size: 12px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
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
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Course Modal -->
    <div class="modal-overlay" id="courseModal">
        <div class="modal-card">
            <h3 id="modalTitle">Create New Course</h3>
            <form id="courseForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="courseId">
                <div class="form-group">
                    <label>Course Title</label>
                    <input type="text" name="title" id="courseTitle" required placeholder="e.g. Advanced Full Stack Web Development">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="courseCategory">
                        <option value="1">Web Development</option>
                        <option value="2">Python & AI</option>
                        <option value="3">Data Science</option>
                        <option value="4">Cyber Security</option>
                        <option value="5">Mobile App</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="courseDesc" rows="3" placeholder="Course outline and key concepts..."></textarea>
                </div>
                <div class="form-group">
                    <label>Price (BDT)</label>
                    <input type="number" step="0.01" name="price" id="coursePrice" required value="1500.00">
                </div>
                <div class="form-group">
                    <label>Duration</label>
                    <input type="text" name="duration" id="courseDuration" value="08:00 hours/Daily">
                </div>
                <div class="form-group">
                    <label>Level</label>
                    <select name="level" id="courseLevel">
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Course Image Upload (JPG, PNG, WEBP)</label>
                    <input type="file" name="course_image" id="courseImageFile" accept="image/jpeg,image/png,image/webp" onchange="previewUploadImage(this)">
                    <div class="image-preview-box" id="previewBox">
                        <span style="color:#aaa; font-size:13px;">No image selected</span>
                    </div>
                </div>
                <input type="hidden" name="thumbnail" id="courseThumb" value="PUBLIC/pic/img.jpg">
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn btn-warning" onclick="closeCourseModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveCourseBtn">Save Course</button>
                </div>
            </form>
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

            $('#courseForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#courseId').val();
                const action = id ? 'update_course' : 'create_course';
                
                const formData = new FormData(this);
                const saveBtn = $('#saveCourseBtn');
                saveBtn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: '../../CONTROLLAR/process/process_course.php?action=' + action,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        saveBtn.prop('disabled', false).text('Save Course');
                        if (response.status === 'success') {
                            alert(response.message);
                            closeCourseModal();
                            window.location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(err) {
                        saveBtn.prop('disabled', false).text('Save Course');
                        alert("Error saving course: " + (err.responseJSON ? err.responseJSON.message : "Server or network error"));
                    }
                });
            });
        });

        function openAddCourseModal() {
            $('#modalTitle').text('Create New Course');
            $('#courseForm')[0].reset();
            $('#courseId').val('');
            $('#courseThumb').val('PUBLIC/pic/img.jpg');
            $('#previewBox').html('<span style="color:#aaa; font-size:13px;">No image selected</span>');
            $('#courseModal').css('display', 'flex');
        }

        function closeCourseModal() {
            $('#courseModal').hide();
        }

        function editCourseFromCard(c) {
            $('#modalTitle').text('Edit Course Details');
            $('#courseId').val(c.id);
            $('#courseTitle').val(c.title);
            $('#courseCategory').val(c.category_id || '1');
            $('#courseDesc').val(c.description);
            $('#coursePrice').val(c.price);
            $('#courseDuration').val(c.duration);
            $('#courseLevel').val(c.level || 'Beginner');
            $('#courseThumb').val(c.thumbnail || 'PUBLIC/pic/img.jpg');
            
            const imgPath = c.thumbnail ? (c.thumbnail.startsWith('PUBLIC/') ? '../../' + c.thumbnail : c.thumbnail) : '../../PUBLIC/pic/img.jpg';
            $('#previewBox').html(`<img src="${imgPath}" alt="Preview" onerror="this.src='../../PUBLIC/pic/img.jpg'">`);
            $('#courseModal').css('display', 'flex');
        }

        function deleteCourseFromCard(id) {
            if (confirm("Are you sure you want to delete this course?")) {
                $.post('../../CONTROLLAR/process/process_course.php?action=delete_course', { id: id }, function(res) {
                    if (res.status === 'success') {
                        alert("Course deleted successfully!");
                        window.location.reload();
                    } else {
                        alert(res.message);
                    }
                }, 'json');
            }
        }

        function previewUploadImage(input) {
            const previewBox = document.getElementById('previewBox');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewBox.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
