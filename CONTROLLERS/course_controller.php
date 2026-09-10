<?php
$savePath = session_save_path();
if (empty($savePath) || !is_dir($savePath) || !is_writable($savePath)) {
    $tempDir = sys_get_temp_dir();
    if (is_dir($tempDir) && is_writable($tempDir)) {
        @session_save_path($tempDir);
    }
}

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../CONFIG/db_connect.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $db = get_school_db();
    $teacherId = $_SESSION['user_id'] ?? 1; // Default active teacher ID

    switch ($action) {
        case 'create_course':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            // CSRF or Token check if provided
            $courseName = trim(filter_input(INPUT_POST, 'course_name', FILTER_SANITIZE_SPECIAL_CHARS));
            $courseCode = strtoupper(trim(filter_input(INPUT_POST, 'course_code', FILTER_SANITIZE_SPECIAL_CHARS)));
            $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS));
            $rawFee = $_POST['fee_amount'] ?? '';
            $status = trim($_POST['status'] ?? 'Active');

            if (empty($courseName) || empty($courseCode)) {
                throw new Exception("Course Name and Course Code are required fields.");
            }

            // Defensive Validation for Bangladeshi Taka (৳ BDT)
            if (!is_numeric($rawFee) || (float)$rawFee < 0) {
                throw new Exception("Please enter a valid amount in Bangladeshi Taka (৳).");
            }

            $feeAmount = (float)$rawFee;
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $courseName), '-'));

            try {
                $sql = "INSERT INTO courses (teacher_id, instructor_id, title, course_name, course_code, slug, description, price, fee_amount, status)
                        VALUES (:teacher_id, :instructor_id, :title, :course_name, :course_code, :slug, :description, :price, :fee_amount, :status)";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':teacher_id' => $teacherId,
                    ':instructor_id' => $teacherId,
                    ':title' => $courseName,
                    ':course_name' => $courseName,
                    ':course_code' => $courseCode,
                    ':slug' => $slug,
                    ':description' => $description,
                    ':price' => $feeAmount,
                    ':fee_amount' => $feeAmount,
                    ':status' => in_array($status, ['Active', 'Upcoming', 'Archived']) ? $status : 'Active'
                ]);

                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => "Course '{$courseName}' created with fee ৳ " . number_format($feeAmount, 2) . " BDT successfully!"
                ];
            } catch (PDOException $e) {
                if ($e->getCode() == '23000' || str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'unique')) {
                    throw new Exception("Course code '{$courseCode}' already exists. Please use a unique code.");
                }
                throw new Exception("Database error: " . $e->getMessage());
            }

            header("Location: ../VIEWS/USER/teacher_dashboard.php#courses");
            exit();

        case 'delete_course':
            $courseId = (int)($_POST['course_id'] ?? $_GET['id'] ?? 0);
            if (!$courseId) throw new Exception("Invalid course ID provided");

            $stmt = $db->prepare("DELETE FROM courses WHERE id = :id");
            $stmt->execute([':id' => $courseId]);

            if (isset($_GET['ajax']) || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Course deleted successfully']);
                exit();
            }

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Course deleted successfully!"
            ];
            header("Location: ../VIEWS/USER/teacher_dashboard.php#courses");
            exit();

        case 'get_details':
            $courseId = (int)($_GET['id'] ?? 0);
            if (!$courseId) throw new Exception("Course ID is required");

            $stmt = $db->prepare("
                SELECT c.*, 
                       (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as assignment_count,
                       (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count
                FROM courses c 
                WHERE c.id = :id
            ");
            $stmt->execute([':id' => $courseId]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$course) throw new Exception("Course details not found");

            // Fetch course assignments
            $assStmt = $db->prepare("SELECT id, title, deadline, total_marks FROM assignments WHERE course_id = :cid ORDER BY deadline ASC");
            $assStmt->execute([':cid' => $courseId]);
            $course['assignments'] = $assStmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $course]);
            exit();

        default:
            throw new Exception("Action not supported");
    }

} catch (Exception $e) {
    if (isset($_GET['ajax']) || isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_GET['action']) && $_GET['action'] === 'get_details')) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }

    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => $e->getMessage()
    ];
    header("Location: ../VIEWS/USER/teacher_dashboard.php");
    exit();
}
?>
