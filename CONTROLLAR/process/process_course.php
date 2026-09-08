<?php
define('IS_API_REQUEST', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../DATABASE/db_connection.php';
require_once __DIR__ . '/../../VIEWS/auth/check_session.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = get_db_connection();

    switch ($action) {
        // Public / Student: Get catalog of courses
        case 'get_courses':
            $publishedOnly = !(isset($_GET['all']) && $_GET['all'] === 'true');
            
            $sql = "SELECT c.id, c.course_code, c.title, c.slug, c.description, c.price, 
                           c.duration, c.level, c.thumbnail, c.tutorials_count, c.is_published,
                           cat.name as category_name,
                           p.full_name as instructor_name
                    FROM courses c
                    LEFT JOIN categories cat ON c.category_id = cat.id
                    LEFT JOIN profiles p ON c.instructor_id = p.user_id";
            
            if ($publishedOnly) {
                $sql .= " WHERE c.is_published = TRUE";
            }
            
            $sql .= " ORDER BY c.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate student enrollment status if logged in
            if (isset($_SESSION['user_id']) && ($_SESSION['user_type'] ?? '') === 'student') {
                $userId = $_SESSION['user_id'];
                $enrolledStmt = $pdo->prepare("SELECT course_id, progress_percent FROM enrollments WHERE user_id = :uid");
                $enrolledStmt->execute([':uid' => $userId]);
                $userEnrollments = $enrolledStmt->fetchAll(PDO::FETCH_KEY_PAIR);

                foreach ($courses as &$c) {
                    $c['is_enrolled'] = isset($userEnrollments[$c['id']]);
                    $c['user_progress'] = $userEnrollments[$c['id']] ?? 0;
                }
            }

            echo json_encode([
                'status' => 'success',
                'data' => $courses
            ]);
            break;

        // Get single course details
        case 'get_course':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) throw new Exception("Invalid course ID");

            $sql = "SELECT c.*, cat.name as category_name, p.full_name as instructor_name
                    FROM courses c
                    LEFT JOIN categories cat ON c.category_id = cat.id
                    LEFT JOIN profiles p ON c.instructor_id = p.user_id
                    WHERE c.id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$course) throw new Exception("Course not found");

            // Fetch lessons
            $lessonStmt = $pdo->prepare("SELECT * FROM lessons WHERE course_id = :cid ORDER BY sort_order ASC");
            $lessonStmt->execute([':cid' => $id]);
            $course['lessons'] = $lessonStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data' => $course
            ]);
            break;

        // Admin: Create new course with Image Upload support
        case 'create_course':
            require_auth(['admin']);

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $categoryId = (int)($_POST['category_id'] ?? 1);
            $price = (float)($_POST['price'] ?? 0.00);
            $duration = trim($_POST['duration'] ?? '08:00 hours/Daily');
            $level = trim($_POST['level'] ?? 'Beginner');
            $tutorialsCount = (int)($_POST['tutorials_count'] ?? 10);
            $isPublished = isset($_POST['is_published']) ? ($_POST['is_published'] === 'true' || $_POST['is_published'] === '1') : true;

            if (empty($title)) throw new Exception("Course title is required");

            // Process uploaded image if provided
            $thumbnailPath = handleCourseImageUpload();
            if (!$thumbnailPath) {
                $thumbnailPath = trim($_POST['thumbnail'] ?? 'PUBLIC/pic/img.jpg');
            }

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
            $courseCode = 'c' . rand(100, 999);

            $sql = "INSERT INTO courses (course_code, title, slug, description, category_id, instructor_id, price, duration, level, thumbnail, tutorials_count, is_published)
                    VALUES (:code, :title, :slug, :desc, :cat_id, :inst_id, :price, :duration, :level, :thumb, :tcount, :pub)
                    RETURNING id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':code' => $courseCode,
                ':title' => $title,
                ':slug' => $slug,
                ':desc' => $description,
                ':cat_id' => $categoryId,
                ':inst_id' => $_SESSION['user_id'],
                ':price' => $price,
                ':duration' => $duration,
                ':level' => $level,
                ':thumb' => $thumbnailPath,
                ':tcount' => $tutorialsCount,
                ':pub' => $isPublished ? 'true' : 'false'
            ]);

            $newId = $stmt->fetchColumn();

            // Log admin audit
            logAdminAudit($pdo, $_SESSION['user_id'], 'COURSE_CREATE', 'courses', $newId, "Created course '{$title}' with price {$price}");

            echo json_encode([
                'status' => 'success',
                'message' => 'Course created successfully with image!',
                'course_id' => $newId,
                'thumbnail' => $thumbnailPath
            ]);
            break;

        // Admin: Update course with image replacement support
        case 'update_course':
            require_auth(['admin']);

            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0.00);
            $duration = trim($_POST['duration'] ?? '');
            $level = trim($_POST['level'] ?? '');

            if (!$id || empty($title)) throw new Exception("Course ID and Title are required");

            // Fetch current course to handle legacy image replacement
            $currStmt = $pdo->prepare("SELECT thumbnail FROM courses WHERE id = :id");
            $currStmt->execute([':id' => $id]);
            $currentCourse = $currStmt->fetch(PDO::FETCH_ASSOC);

            // Process new uploaded image if provided
            $newThumbnail = handleCourseImageUpload();
            if ($newThumbnail) {
                // Remove old uploaded image file if replacing custom upload
                if (!empty($currentCourse['thumbnail']) && str_contains($currentCourse['thumbnail'], 'uploads/courses/')) {
                    $oldFilePath = ROOT_PATH . '/' . $currentCourse['thumbnail'];
                    if (file_exists($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }
                $thumbnailPath = $newThumbnail;
            } else {
                $thumbnailPath = trim($_POST['thumbnail'] ?? ($currentCourse['thumbnail'] ?? 'PUBLIC/pic/img.jpg'));
            }

            $sql = "UPDATE courses 
                    SET title = :title, description = :desc, price = :price, 
                        duration = :duration, level = :level, thumbnail = :thumb,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':title' => $title,
                ':desc' => $description,
                ':price' => $price,
                ':duration' => $duration,
                ':level' => $level,
                ':thumb' => $thumbnailPath
            ]);

            // Log admin audit
            logAdminAudit($pdo, $_SESSION['user_id'], 'COURSE_UPDATE', 'courses', $id, "Updated course ID {$id} details and image");

            echo json_encode([
                'status' => 'success',
                'message' => 'Course updated successfully!',
                'thumbnail' => $thumbnailPath
            ]);
            break;

        // Admin: Toggle Publish/Unpublish status
        case 'toggle_publish':
            require_auth(['admin']);

            $id = (int)($_POST['id'] ?? 0);
            $isPublished = ($_POST['is_published'] === 'true' || $_POST['is_published'] === '1') ? 'true' : 'false';

            if (!$id) throw new Exception("Course ID required");

            $stmt = $pdo->prepare("UPDATE courses SET is_published = :pub, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute([':pub' => $isPublished, ':id' => $id]);

            logAdminAudit($pdo, $_SESSION['user_id'], 'TOGGLE_PUBLISH', 'courses', $id, "Set publish status to {$isPublished}");

            echo json_encode([
                'status' => 'success',
                'message' => 'Course publish status updated successfully.'
            ]);
            break;

        // Admin: Delete course
        case 'delete_course':
            require_auth(['admin']);

            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception("Invalid course ID");

            // Clean up uploaded image if exists
            $imgStmt = $pdo->prepare("SELECT thumbnail FROM courses WHERE id = :id");
            $imgStmt->execute([':id' => $id]);
            $thumb = $imgStmt->fetchColumn();
            if ($thumb && str_contains($thumb, 'uploads/courses/')) {
                $filePath = ROOT_PATH . '/' . $thumb;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM courses WHERE id = :id");
            $stmt->execute([':id' => $id]);

            logAdminAudit($pdo, $_SESSION['user_id'], 'COURSE_DELETE', 'courses', $id, "Deleted course ID {$id}");

            echo json_encode([
                'status' => 'success',
                'message' => 'Course deleted successfully!'
            ]);
            break;

        default:
            throw new Exception("Invalid action specified");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit();
}

/**
 * Validates and processes uploaded course image file
 * 
 * @return string|null Relative thumbnail path or null if no file uploaded
 */
function handleCourseImageUpload() {
    if (!isset($_FILES['course_image']) || $_FILES['course_image']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES['course_image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload failed with error code " . $file['error']);
    }

    // 1. Max File Size Check (5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception("Uploaded image exceeds maximum allowed size of 5MB.");
    }

    // 2. MIME Type Validation
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes)) {
        throw new Exception("Invalid image file format ({$mimeType}). Allowed formats: JPG, PNG, WEBP.");
    }

    // 3. File Extension Check
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowedExts)) {
        throw new Exception("Invalid file extension (.{$extension}). Executable or script files are forbidden.");
    }

    // 4. Ensure Destination Directory Exists
    $uploadDir = ROOT_PATH . '/PUBLIC/uploads/courses/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 5. Generate Collision-Proof Secure Unique Filename
    $secureName = 'course_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = $uploadDir . $secureName;

    // 6. Move Uploaded File
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to save uploaded image to persistent storage.");
    }

    return 'PUBLIC/uploads/courses/' . $secureName;
}

/**
 * Logs administrator audit event to PostgreSQL
 */
function logAdminAudit($pdo, $adminId, $action, $entityType, $entityId, $details) {
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_audit_logs (admin_id, action, entity_type, entity_id, details) 
                               VALUES (:aid, :act, :etype, :eid, :dt)");
        $stmt->execute([
            ':aid' => $adminId,
            ':act' => $action,
            ':etype' => $entityType,
            ':eid' => $entityId,
            ':dt' => $details
        ]);
    } catch (Exception $e) {
        error_log("Audit Log Error: " . $e->getMessage());
    }
}
?>
