<?php
require_once __DIR__ . '/../../MODELS/db.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="admin_information.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, array(
    'Admin Name',
    'Age',
    'Date of Birth',
    'Blood Group',
    'Phone Number',
    'Address',
    'Email',
    'Username'
));

// Get admin data
$db = new Database();
$conn = $db->getConnection();
$query = "SELECT * FROM admin_registration";
$result = $conn->query($query);

// Add data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, array(
        $row['admin_name'],
        $row['age'],
        $row['date_of_birth'],
        $row['blood_group'],
        $row['phone_number'],
        $row['address'],
        $row['email'],
        $row['username']
    ));
}

// Close the output stream
fclose($output);
?> 