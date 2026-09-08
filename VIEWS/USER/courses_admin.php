<?php
require_once __DIR__ . '/../../VIEWS/auth/check_session.php';
require_auth(['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Course Management (Uploads & Audit Logging)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        :root { --primary-color: #4caf50; --sidebar-color: #9a47f8; --sidebar-hover: #b47af9; --shadow: 0 0 20px rgba(0,0,0,0.1); }
        body { background: #f2f2f2; min-height: 100vh; display: flex; }
        .sidebar { width: 70px; background: var(--sidebar-color); height: 100vh; position: fixed; left: 0; top: 0; padding: 20px 10px; transition: width 0.3s; overflow: hidden; }
        .sidebar:hover { width: 250px; }
        .sidebar h2 { color: #fff; text-align: center; margin-bottom: 30px; opacity: 0; transition: opacity 0.3s; white-space: nowrap; }
        .sidebar:hover h2 { opacity: 1; }
        .sidebar ul { list-style: none; }
        .sidebar ul li a { color: #fff; text-decoration: none; display: flex; align-items: center; padding: 12px; border-radius: 5px; margin-bottom: 10px; white-space: nowrap; }
        .sidebar ul li a:hover { background: var(--sidebar-hover); }
        .sidebar ul li a i { margin-right: 15px; font-size: 18px; min-width: 25px; text-align: center; }
        .sidebar ul li a span { opacity: 0; transition: opacity 0.3s; }
        .sidebar:hover ul li a span { opacity: 1; }
        
        .main-content { margin-left: 70px; width: calc(100% - 70px); padding: 25px; transition: margin-left 0.3s; }
        .sidebar:hover + .main-content { margin-left: 250px; width: calc(100% - 250px); }
        
        .header { background: #fff; padding: 15px 30px; border-radius: 10px; box-shadow: var(--shadow); display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .btn { padding: 10px 18px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; }
        .btn-primary { background: #4caf50; color: #fff; }
        .btn-primary:hover { background: #45a049; }
        .btn-danger { background: #e74c3c; color: #fff; }
        .btn-warning { background: #f39c12; color: #fff; }
        .btn-info { background: #3498db; color: #fff; }
        
        .table-container { background: #fff; border-radius: 10px; padding: 20px; box-shadow: var(--shadow); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f8f9fa; font-weight: 600; color: #333; }
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-published { background: #e8f8f5; color: #2ecc71; }
        .status-draft { background: #fef9e7; color: #f39c12; }

        /* Modal styling */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .modal-card { background: #fff; width: 550px; padding: 25px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
        .modal-card h3 { margin-top: 0; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        
        .image-preview-box { width: 100%; height: 120px; border: 2px dashed #ccc; border-radius: 5px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-top: 8px; background: #fafafa; }
        .image-preview-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Dashboard</h2>
        <ul>
            <li><a href="../../MODELS/dashboard.html"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="courses_admin.php"><i class="fas fa-book"></i> <span>Courses (CRUD)</span></a></li>
            <li><a href="Admin_view.php"><i class="fas fa-users"></i> <span>Admin Users</span></a></li>
            <li><a href="student_view.php"><i class="fas fa-user-graduate"></i> <span>Students</span></a></li>
            <li><a href="teacher_view.php"><i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span></a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h2><i class="fas fa-book-open"></i> Admin Course & Image Upload Management</h2>
            <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Create New Course</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="courseTableBody">
                    <tr><td colspan="7" style="text-align:center;">Loading course catalog from PostgreSQL...</td></tr>
                </tbody>
            </table>
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
                    <label>Course Image Upload (JPG, PNG, WEBP - Max 5MB)</label>
                    <input type="file" name="course_image" id="courseImageFile" accept="image/jpeg,image/png,image/webp" onchange="previewUploadImage(this)">
                    <div class="image-preview-box" id="previewBox">
                        <span style="color:#aaa; font-size:13px;">No image selected</span>
                    </div>
                </div>
                <input type="hidden" name="thumbnail" id="courseThumb" value="PUBLIC/pic/img.jpg">
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn btn-warning" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveCourseBtn">Save Course & Image</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            loadAdminCourses();

            $('#courseForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#courseId').val();
                const action = id ? 'update_course' : 'create_course';
                
                const formData = new FormData(this);
                const saveBtn = $('#saveCourseBtn');
                saveBtn.prop('disabled', true).text('Uploading & Saving...');

                $.ajax({
                    url: '../../CONTROLLAR/process/process_course.php?action=' + action,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        saveBtn.prop('disabled', false).text('Save Course & Image');
                        if (response.status === 'success') {
                            alert(response.message);
                            closeModal();
                            loadAdminCourses();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(err) {
                        saveBtn.prop('disabled', false).text('Save Course & Image');
                        alert("Error saving course: " + (err.responseJSON ? err.responseJSON.message : "Server or network error"));
                    }
                });
            });
        });

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

        function loadAdminCourses() {
            $.getJSON('../../CONTROLLAR/process/process_course.php?action=get_courses&all=true', function(res) {
                if (res.status === 'success') {
                    const tbody = $('#courseTableBody');
                    if (res.data.length === 0) {
                        tbody.html('<tr><td colspan="7" style="text-align:center;">No courses found in database.</td></tr>');
                        return;
                    }
                    tbody.empty();
                    res.data.forEach(c => {
                        const isPub = c.is_published === true || c.is_published === 't' || c.is_published === 1;
                        const statusBadge = isPub ? 
                            '<span class="status-badge status-published">Published</span>' : 
                            '<span class="status-badge status-draft">Draft/Hidden</span>';

                        const toggleBtn = isPub ?
                            `<button onclick="togglePublish(${c.id}, false)" class="btn btn-warning" style="padding:4px 8px; font-size:12px;"><i class="fas fa-eye-slash"></i> Unpublish</button>` :
                            `<button onclick="togglePublish(${c.id}, true)" class="btn btn-primary" style="padding:4px 8px; font-size:12px;"><i class="fas fa-eye"></i> Publish</button>`;

                        const imgPath = c.thumbnail ? (c.thumbnail.startsWith('PUBLIC/') ? '../../' + c.thumbnail : c.thumbnail) : '../../PUBLIC/pic/img.jpg';

                        tbody.append(`
                            <tr>
                                <td><img src="${imgPath}" width="45" height="45" style="object-fit:cover; border-radius:6px; border:1px solid #ddd;" onerror="this.src='../../PUBLIC/pic/img.jpg'"></td>
                                <td><strong>${escapeHtml(c.title)}</strong></td>
                                <td>${escapeHtml(c.category_name || 'General')}</td>
                                <td>BDT ${parseFloat(c.price).toFixed(2)}</td>
                                <td>${escapeHtml(c.duration || 'N/A')}</td>
                                <td>${statusBadge}</td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        ${toggleBtn}
                                        <button onclick='editCourse(${JSON.stringify(c)})' class="btn btn-info" style="padding:4px 8px; font-size:12px;"><i class="fas fa-edit"></i> Edit</button>
                                        <button onclick="deleteCourse(${c.id})" class="btn btn-danger" style="padding:4px 8px; font-size:12px;"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        `);
                    });
                }
            });
        }

        function openAddModal() {
            $('#modalTitle').text('Create New Course');
            $('#courseForm')[0].reset();
            $('#courseId').val('');
            $('#courseThumb').val('PUBLIC/pic/img.jpg');
            $('#previewBox').html('<span style="color:#aaa; font-size:13px;">No image selected</span>');
            $('#courseModal').css('display', 'flex');
        }

        function closeModal() {
            $('#courseModal').hide();
        }

        function editCourse(c) {
            $('#modalTitle').text('Edit Course & Image');
            $('#courseId').val(c.id);
            $('#courseTitle').val(c.title);
            $('#courseDesc').val(c.description);
            $('#coursePrice').val(c.price);
            $('#courseDuration').val(c.duration);
            $('#courseLevel').val(c.level || 'Beginner');
            $('#courseThumb').val(c.thumbnail || 'PUBLIC/pic/img.jpg');
            
            const imgPath = c.thumbnail ? (c.thumbnail.startsWith('PUBLIC/') ? '../../' + c.thumbnail : c.thumbnail) : '../../PUBLIC/pic/img.jpg';
            $('#previewBox').html(`<img src="${imgPath}" alt="Preview" onerror="this.src='../../PUBLIC/pic/img.jpg'">`);
            $('#courseModal').css('display', 'flex');
        }

        function togglePublish(id, makePublished) {
            $.post('../../CONTROLLAR/process/process_course.php?action=toggle_publish', { id: id, is_published: makePublished ? 'true' : 'false' }, function(res) {
                if (res.status === 'success') {
                    loadAdminCourses();
                } else {
                    alert(res.message);
                }
            }, 'json');
        }

        function deleteCourse(id) {
            if (confirm("Are you sure you want to delete this course and its uploaded image from PostgreSQL?")) {
                $.post('../../CONTROLLAR/process/process_course.php?action=delete_course', { id: id }, function(res) {
                    if (res.status === 'success') {
                        loadAdminCourses();
                    } else {
                        alert(res.message);
                    }
                }, 'json');
            }
        }

        function escapeHtml(str) {
            return str ? str.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])) : '';
        }
    </script>
</body>
</html>
