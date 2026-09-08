<?php
require_once __DIR__ . '/../../VIEWS/auth/check_session.php';
require_once __DIR__ . '/../../DATABASE/db_connection.php';

require_auth(['admin', 'teacher']);

$pdo = get_db_connection();

$sql = "SELECT u.id, u.email, u.username, u.status, u.created_at,
               p.first_name, p.last_name, p.full_name, p.phone_number, p.gender, p.blood_group
        FROM users u
        LEFT JOIN profiles p ON u.id = p.user_id
        WHERE u.role = 'student'
        ORDER BY p.first_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Directory - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        :root { --primary-color: #4caf50; --bg-color: #f2f2f2; --white: #ffffff; --shadow: 0 0 20px rgba(0, 0, 0, 0.1); --sidebar-color: #9a47f8; }
        body { background: var(--bg-color); min-height: 100vh; display: flex; position: relative; padding-bottom: 60px; }
        .sidebar { width: 70px; background: var(--sidebar-color); height: 100vh; position: fixed; left: 0; top: 0; padding: 20px 10px; box-shadow: var(--shadow); transition: width 0.3s ease; overflow: hidden; }
        .sidebar:hover { width: 250px; }
        .sidebar h2 { color: var(--white); margin-bottom: 30px; text-align: center; white-space: nowrap; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar:hover h2 { opacity: 1; }
        .sidebar ul { list-style: none; }
        .sidebar ul li a { color: var(--white); text-decoration: none; display: flex; align-items: center; padding: 10px; border-radius: 5px; margin-bottom: 10px; white-space: nowrap; }
        .sidebar ul li a:hover { background: #b47af9; }
        .sidebar ul li a i { margin-right: 10px; font-size: 20px; min-width: 30px; text-align: center; }
        .sidebar ul li a span { opacity: 0; transition: opacity 0.3s ease; }
        .sidebar:hover ul li a span { opacity: 1; }
        .main-content { margin-left: 70px; width: calc(100% - 70px); padding: 20px; transition: margin-left 0.3s ease; }
        .sidebar:hover + .main-content { margin-left: 250px; width: calc(100% - 250px); }
        .header { background: var(--white); padding: 15px 30px; border-radius: 10px; box-shadow: var(--shadow); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .student-table { width: 100%; background: var(--white); border-radius: 10px; box-shadow: var(--shadow); margin-top: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: #333; }
        .footer { background: var(--white); padding: 15px; text-align: center; position: fixed; bottom: 0; left: 70px; right: 0; z-index: 1000; }
        .sidebar:hover + .main-content .footer { left: 250px; }
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
            <h2>Registered Students Directory (PostgreSQL)</h2>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search students..." style="padding:8px 12px; border:1px solid #ddd; border-radius:5px; width:250px;">
            </div>
        </div>

        <div class="student-table">
            <table>
                <thead>
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Blood Group</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:20px;">No registered students found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['first_name'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($s['last_name'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($s['phone_number'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($s['gender'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($s['blood_group'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td><span style="color:#2ecc71; font-weight:bold;"><?php echo htmlspecialchars($s['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>&copy; 2026 Student Directory. All rights reserved.</p>
        </div>
    </div>

    <script>
    $('#searchInput').on('keyup', function() {
        const val = $(this).val().toLowerCase();
        $('.student-table tbody tr').each(function() {
            $(this).toggle($(this).text().toLowerCase().includes(val));
        });
    });
    </script>
</body>
</html>
