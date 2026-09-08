<?php
session_start();
include_once __DIR__ . '/../../MODELS/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: Admin_view.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$admin_id = $_GET['id'];
$query = "SELECT * FROM admin_registration WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    header('Location: Admin_view.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin</title>
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
        }

        body {
            background: var(--bg-color);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .edit-form {
            background: var(--white);
            padding: 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 600px;
        }

        .form-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .form-header h2 {
            color: var(--text-color);
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            flex: 1;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-secondary {
            background: #666;
            color: white;
        }

        .btn:hover:not(:disabled) {
            opacity: 0.9;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .loading {
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin: -10px 0 0 -10px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="edit-form">
        <div class="form-header">
            <h2>Edit Admin Information</h2>
        </div>
        <div id="alert" class="alert"></div>
        <form id="editAdminForm">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($admin['id']); ?>">
            
            <div class="form-group">
                <label for="admin_name">Admin Name</label>
                <input type="text" id="admin_name" name="admin_name" value="<?php echo htmlspecialchars($admin['admin_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($admin['age']); ?>" required min="18" max="100">
            </div>

            <div class="form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($admin['date_of_birth']); ?>" required>
            </div>

            <div class="form-group">
                <label for="blood_group">Blood Group</label>
                <input type="text" id="blood_group" name="blood_group" value="<?php echo htmlspecialchars($admin['blood_group']); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($admin['phone_number']); ?>" required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($admin['address']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="Admin_view.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            const form = $('#editAdminForm');
            const submitBtn = form.find('button[type="submit"]');
            const alert = $('#alert');
            
            form.on('submit', function(e) {
                e.preventDefault();
                
                // Validate form
                if (!this.checkValidity()) {
                    return;
                }
                
                // Show loading state
                submitBtn.prop('disabled', true).addClass('loading').text('Updating...');
                
                // Clear previous alerts
                alert.removeClass('alert-success alert-danger').hide();
                
                // Get form data
                const formData = new FormData(this);
                
                // Send AJAX request
                $.ajax({
                    url: '../../CONTROLLAR/process/update_admin.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Success Response:', response);
                        
                        if (response.success) {
                            alert.removeClass('alert-danger')
                                .addClass('alert-success')
                                .html(response.message)
                                .fadeIn();
                            
                            setTimeout(function() {
                                window.location.href = 'Admin_view.php';
                            }, 2000);
                        } else {
                            alert.removeClass('alert-success')
                                .addClass('alert-danger')
                                .html(response.message)
                                .fadeIn();
                            
                            submitBtn.prop('disabled', false)
                                .removeClass('loading')
                                .text('Update');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        
                        let errorMessage = 'An error occurred. Please try again.';
                        
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            console.error('JSON Parse Error:', e);
                        }
                        
                        alert.removeClass('alert-success')
                            .addClass('alert-danger')
                            .html(errorMessage)
                            .fadeIn();
                        
                        submitBtn.prop('disabled', false)
                            .removeClass('loading')
                            .text('Update');
                    }
                });
            });
        });
    </script>
</body>
</html> 