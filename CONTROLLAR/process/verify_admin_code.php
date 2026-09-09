<?php
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$inputCode = isset($_POST['admin_code']) ? trim($_POST['admin_code']) : '';
$secretCode = '123456';

if (empty($inputCode)) {
    echo json_encode(['success' => false, 'message' => 'Please enter the admin access code.']);
    exit();
}

if ($inputCode === $secretCode) {
    echo json_encode([
        'success' => true,
        'message' => '✓ Admin access code verified! Registration unlocked.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => '✗ Invalid Admin access code. Permission denied.'
    ]);
}
exit();
