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
        case 'create_assignment':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            $courseId = (int)($_POST['course_id'] ?? 0);
            $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS));
            $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS));
            $deadline = trim($_POST['deadline'] ?? '');
            $totalMarks = (int)($_POST['total_marks'] ?? 100);

            if (!$courseId || empty($title) || empty($deadline)) {
                throw new Exception("Please fill in all required fields (Course, Title, and Deadline).");
            }

            if ($totalMarks <= 0) {
                throw new Exception("Total marks must be a positive integer.");
            }

            $sql = "INSERT INTO assignments (course_id, teacher_id, title, description, deadline, total_marks)
                    VALUES (:course_id, :teacher_id, :title, :description, :deadline, :total_marks)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':course_id' => $courseId,
                ':teacher_id' => $teacherId,
                ':title' => $title,
                ':description' => $description,
                ':deadline' => date('Y-m-d H:i:s', strtotime($deadline)),
                ':total_marks' => $totalMarks
            ]);

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Assignment '{$title}' created successfully!"
            ];

            header("Location: ../VIEWS/USER/teacher_dashboard.php#assignments");
            exit();

        case 'delete_assignment':
            $assignmentId = (int)($_POST['assignment_id'] ?? $_GET['id'] ?? 0);
            if (!$assignmentId) throw new Exception("Invalid assignment ID provided");

            $stmt = $db->prepare("DELETE FROM assignments WHERE id = :id");
            $stmt->execute([':id' => $assignmentId]);

            if (isset($_GET['ajax']) || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Assignment deleted successfully']);
                exit();
            }

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Assignment deleted successfully!"
            ];
            header("Location: ../VIEWS/USER/teacher_dashboard.php#assignments");
            exit();

        case 'get_details':
            $assignmentId = (int)($_GET['id'] ?? 0);
            if (!$assignmentId) throw new Exception("Assignment ID is required");

            $stmt = $db->prepare("
                SELECT a.*, COALESCE(c.course_name, c.title) as course_name, c.course_code, c.fee_amount
                FROM assignments a 
                JOIN courses c ON a.course_id = c.id
                WHERE a.id = :id
            ");
            $stmt->execute([':id' => $assignmentId]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$assignment) throw new Exception("Assignment details not found");

            // Calculate status & countdown
            $now = new DateTime();
            $due = new DateTime($assignment['deadline']);
            $interval = $now->diff($due);

            if ($due < $now) {
                $assignment['time_remaining'] = 'Expired / Past Due';
                $assignment['status_badge'] = 'Past Due';
            } else {
                $assignment['time_remaining'] = $interval->format('%a days, %h hours remaining');
                $assignment['status_badge'] = 'Open';
            }

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $assignment]);
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
