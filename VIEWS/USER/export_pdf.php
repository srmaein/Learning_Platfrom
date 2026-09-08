<?php
require_once __DIR__ . '/../../MODELS/db.php';

// Get admin data
$db = new Database();
$conn = $db->getConnection();
$query = "SELECT * FROM admin_registration";
$result = $conn->query($query);

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="admin_information.pdf"');

// Create HTML content
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Information</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h1 { text-align: center; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .date { color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Admin Information Report</h1>
        <div class="date">Generated on: ' . date('Y-m-d H:i:s') . '</div>
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
            </tr>
        </thead>
        <tbody>';

while ($row = $result->fetch_assoc()) {
    $html .= '
        <tr>
            <td>' . htmlspecialchars($row['admin_name']) . '</td>
            <td>' . htmlspecialchars($row['age']) . '</td>
            <td>' . htmlspecialchars($row['date_of_birth']) . '</td>
            <td>' . htmlspecialchars($row['blood_group']) . '</td>
            <td>' . htmlspecialchars($row['phone_number']) . '</td>
            <td>' . htmlspecialchars($row['address']) . '</td>
            <td>' . htmlspecialchars($row['email']) . '</td>
            <td>' . htmlspecialchars($row['username']) . '</td>
        </tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

// Convert HTML to PDF using wkhtmltopdf if available, otherwise output HTML
if (file_exists('C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe')) {
    $temp_html = tempnam(sys_get_temp_dir(), 'pdf_');
    file_put_contents($temp_html, $html);
    
    $output_pdf = tempnam(sys_get_temp_dir(), 'output_');
    exec('"C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe" ' . $temp_html . ' ' . $output_pdf);
    
    readfile($output_pdf);
    unlink($temp_html);
    unlink($output_pdf);
} else {
    // If wkhtmltopdf is not available, output HTML
    header('Content-Type: text/html');
    echo $html;
}
?> 