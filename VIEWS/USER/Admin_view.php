<?php
require_once __DIR__ . '/../../DATABASE/db_connection.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$conn = getPgPDO();

// Fetch all admin information
$query = "SELECT u.id, u.email, u.username, u.status, u.created_at, p.full_name as admin_name, p.phone_number, p.address, p.age, p.date_of_birth, p.blood_group
          FROM users u 
          LEFT JOIN profiles p ON u.id = p.user_id 
          WHERE u.role = 'admin'";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary-color: #4caf50;
            --secondary-color: #45a049;
            --text-color: #333;
            --bg-color: #f2f2f2;
            --white: #ffffff;
            --shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            --sidebar-color: #9a47f8;
            --sidebar-hover: #b47af9;
        }

        body {
            background: var(--bg-color);
            min-height: 100vh;
            display: flex;
            position: relative;
            padding-bottom: 60px;
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

        .sidebar ul li a:hover {
            background: var(--sidebar-hover);
            color: var(--white);
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

        /* Main Content Styles */
        .main-content {
            margin-left: 70px;
            width: calc(100% - 70px);
            padding: 20px;
            transition: margin-left 0.3s ease, width 0.3s ease;
            min-height: 100vh;
            position: relative;
        }

        .sidebar:hover + .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        /* Header Styles */
        .header {
            background: var(--white);
            padding: 15px 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 10px 40px 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 300px;
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-success {
            background: #2ecc71;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .admin-info {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .info-item label {
            display: block;
            color: #666;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .info-item span {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        /* Footer Styles */
        .footer {
            background: var(--white);
            padding: 15px;
            text-align: center;
            position: fixed;
            bottom: 0;
            left: 70px;
            right: 0;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            transition: left 0.3s ease;
            z-index: 1000;
        }

        .sidebar:hover + .main-content .footer {
            left: 250px;
        }

        .footer p {
            color: #666;
            margin: 0;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 10px;
            }

            .sidebar h2,
            .sidebar ul li a span {
                display: none;
            }

            .main-content {
                margin-left: 70px;
            }

            .search-box input {
                width: 200px;
            }

            .action-buttons {
                flex-wrap: wrap;
            }

            .footer {
                left: 70px;
            }

            .sidebar:hover + .main-content .footer {
                left: 70px;
            }
        }

        /* Table Styles */
        .admin-table {
            width: 100%;
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-top: 20px;
            overflow: hidden;
        }

        .admin-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .admin-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .admin-table tr:hover {
            background: #f8f9fa;
        }

        .action-cell {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .edit-btn {
            background: #3498db;
            color: white;
        }

        .delete-btn {
            background: #e74c3c;
            color: white;
        }

        .action-btn:hover {
            opacity: 0.9;
        }

        .no-records {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-title {
            font-size: 20px;
            color: #333;
        }

        @media (max-width: 768px) {
            .admin-table {
                overflow-x: auto;
            }

            .action-cell {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Dashboard</h2>
        <ul>
            <li><a href="../../MODELS/dashboard.html"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="admin_view.php"><i class="fas fa-user"></i> <span>Profile</span></a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
            <li><a href="courses.php"><i class="fas fa-book"></i> <span>Courses</span></a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search admin information...">
                <i class="fas fa-search"></i>
            </div>
            <div class="user-info">
                <span>Welcome, Admin</span>
                <img src="../PUBLIC/pic/user-avatar.png" alt="User Avatar">
            </div>
        </div>

        <div class="action-buttons">
            <a href="admin_registration.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Admin
            </a>
            <button onclick="exportToPDF()" class="btn btn-success">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
            <button onclick="exportToCSV()" class="btn btn-danger">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
        </div>

        <div class="admin-table">
            <div class="table-header">
                <h2 class="table-title">Admin List</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Admin Name</th>
                        <th>Age</th>
                        <th>Date of Birth</th>
                        <th>Blood Group</th>
                        <th>Phone Number</th>
                        <th>Address</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rows = $result ? $result->fetchAll(PDO::FETCH_ASSOC) : [];
                    if (!empty($rows)) {
                        foreach ($rows as $row) {
                            echo '<tr data-id="' . htmlspecialchars($row['id']) . '">
                                <td>' . htmlspecialchars($row['admin_name'] ?? '') . '</td>
                                <td>' . htmlspecialchars($row['age'] ?? 'N/A') . '</td>
                                <td>' . htmlspecialchars($row['date_of_birth'] ?? 'N/A') . '</td>
                                <td>' . htmlspecialchars($row['blood_group'] ?? 'N/A') . '</td>
                                <td>' . htmlspecialchars($row['phone_number'] ?? 'N/A') . '</td>
                                <td>' . htmlspecialchars($row['address'] ?? 'N/A') . '</td>
                                <td>' . htmlspecialchars($row['email'] ?? '') . '</td>
                                <td>' . htmlspecialchars($row['username'] ?? '') . '</td>
                                <td class="action-cell">
                                    <a href="edit_admin.php?id=' . htmlspecialchars($row['id']) . '" class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button onclick="deleteAdmin(' . htmlspecialchars($row['id']) . ')" class="action-btn delete-btn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>';
                        }
                    } else {
                        echo '<tr>
                            <td colspan="9" class="no-records">
                                <i class="fas fa-info-circle"></i> No admin records found
                            </td>
                        </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>&copy; 2024 Admin Dashboard. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Search functionality
        function searchAdmin() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const tableRows = document.querySelectorAll('.admin-table tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Export to PDF
        function exportToPDF() {
            window.location.href = 'export_pdf.php';
        }

        // Export to CSV
        function exportToCSV() {
            window.location.href = 'export_csv.php';
        }

        // Delete admin
        function deleteAdmin(id) {
            if (confirm('Are you sure you want to delete this admin?')) {
                $.ajax({
                    url: '../../CONTROLLAR/process/delete_admin.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Remove the row from the table
                            $(`tr[data-id="${id}"]`).fadeOut(400, function() {
                                $(this).remove();
                                // Check if table is empty
                                if ($('.admin-table tbody tr').length === 0) {
                                    $('.admin-table tbody').html(`
                                        <tr>
                                            <td colspan="9" class="no-records">
                                                <i class="fas fa-info-circle"></i> No admin records found
                                            </td>
                                        </tr>
                                    `);
                                }
                            });
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while deleting the admin.');
                    }
                });
            }
        }

        // Add event listener for Enter key in search box
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchAdmin();
            }
        });

        // Add event listener for search icon click
        document.querySelector('.search-box i').addEventListener('click', searchAdmin);
    </script>
</body>
</html>
