<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Information</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            overflow-x: auto;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .search-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .search-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .register-button {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: inline-block;
        }

        .register-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }

        .back-button {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: inline-block;
            margin-right: 10px;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }

        .button-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            white-space: nowrap;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
        }

        .edit-btn, .delete-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
        }

        .edit-btn:hover {
            background-color: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .delete-btn:hover {
            background-color: #da190b;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* Responsive Design */
        @media screen and (max-width: 1200px) {
            .container {
                padding: 20px;
            }

            th, td {
                padding: 12px 8px;
                font-size: 14px;
            }

            .search-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input {
                width: 100%;
            }

            .button-group {
                flex-direction: column;
                width: 100%;
            }

            .back-button {
                width: 100%;
                text-align: center;
                margin-right: 0;
                margin-bottom: 10px;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-radius: 50%;
            border-top: 3px solid #667eea;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Tooltip for long text */
        td {
            position: relative;
        }

        td:hover::after {
            content: attr(title);
            position: absolute;
            left: 0;
            top: 100%;
            background: #333;
            color: white;
            padding: 5px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 2;
            white-space: normal;
            max-width: 200px;
            display: none;
        }

        td:hover::after {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Teacher Information</h1>
        
        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Search by name, email, ID, or username...">
            <div class="button-group">
                <a href="../../MODELS/dashboard.html" class="back-button">Back to Dashboard</a>
                <button class="search-button" onclick="searchTeachers()">Search</button>
                <a href="teacherregistration.html" class="register-button">Register New Teacher</a>
            </div>
        </div>

        <table id="teacherTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Date of Birth</th>
                    <th>Blood Group</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Qualifications</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="teacherTableBody">
                <!-- Data will be loaded here -->
            </tbody>
        </table>
    </div>

    <script>
        // Load teachers when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadTeachers();
            setupSearchListener();
        });

        // Function to load all teachers
        function loadTeachers() {
            fetch('../../CONTROLLAR/process/get_teachers.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        alert(data.error);
                        return;
                    }
                    displayTeachers(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading teachers data. Please check console for details.');
                });
        }

        // Function to setup search input listener
        function setupSearchListener() {
            const searchInput = document.getElementById('searchInput');
            let searchTimeout;

            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                const searchTerm = e.target.value.trim();
                
                if (searchTerm === '') {
                    loadTeachers();
                    return;
                }

                // Add a small delay to prevent too many requests
                searchTimeout = setTimeout(() => {
                    searchTeachers(searchTerm);
                }, 300);
            });
        }

        // Function to search teachers
        function searchTeachers(searchTerm = null) {
            if (searchTerm === null) {
                searchTerm = document.getElementById('searchInput').value.trim();
            }
            
            if (!searchTerm) {
                loadTeachers();
                return;
            }

            const tbody = document.getElementById('teacherTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="no-results">
                        <div class="loading"></div>
                        Searching...
                    </td>
                </tr>
            `;

            fetch('../../CONTROLLAR/process/search_teachers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'search=' + encodeURIComponent(searchTerm)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    tbody.innerHTML = `<tr><td colspan="11" class="no-results">${data.error}</td></tr>`;
                    return;
                }
                displayTeachers(data);
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.innerHTML = '<tr><td colspan="11" class="no-results">Error searching teachers. Please try again.</td></tr>';
            });
        }

        // Function to display teachers in table
        function displayTeachers(teachers) {
            const tbody = document.getElementById('teacherTableBody');
            tbody.innerHTML = '';

            if (!Array.isArray(teachers) || teachers.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="11" class="no-results">No teachers found</td>
                    </tr>
                `;
                return;
            }

            teachers.forEach(teacher => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${teacher.id || ''}</td>
                    <td title="${teacher.teacher_name || ''}">${teacher.teacher_name || ''}</td>
                    <td>${teacher.age || ''}</td>
                    <td>${teacher.date_of_birth || ''}</td>
                    <td>${teacher.blood_group || ''}</td>
                    <td title="${teacher.phone_number || ''}">${teacher.phone_number || ''}</td>
                    <td title="${teacher.email || ''}">${teacher.email || ''}</td>
                    <td title="${teacher.username || ''}">${teacher.username || ''}</td>
                    <td title="${teacher.qualifications || ''}">${teacher.qualifications || ''}</td>
                    <td title="${teacher.address || ''}">${teacher.address || ''}</td>
                    <td class="action-buttons">
                        <button class="edit-btn" onclick="editTeacher(${teacher.id})">Edit</button>
                        <button class="delete-btn" onclick="deleteTeacher(${teacher.id})">Delete</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Function to edit teacher
        function editTeacher(id) {
            window.location.href = `edit_teacher.php?id=${id}`;
        }

        // Function to delete teacher
        function deleteTeacher(id) {
            if (confirm('Are you sure you want to delete this teacher?')) {
                fetch('../../CONTROLLAR/process/delete_teacher.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        loadTeachers(); // Reload the table
                    } else {
                        alert(data.message || 'Error deleting teacher');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting teacher. Please check console for details.');
                });
            }
        }
    </script>
</body>
</html> 