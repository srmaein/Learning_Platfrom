<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Save test
        $stmt = $pdo->prepare("INSERT INTO tests (title, description) VALUES (?, ?)");
        $stmt->execute([$data['title'], $data['description']]);
        $testId = $pdo->lastInsertId();

        // Save questions
        $stmt = $pdo->prepare("INSERT INTO questions (test_id, question_text, question_type, correct_answer) VALUES (?, ?, ?, ?)");
        foreach ($data['questions'] as $question) {
            $stmt->execute([
                $testId,
                $question['text'],
                $question['type'],
                $question['correct']
            ]);
            $questionId = $pdo->lastInsertId();

            // Save options for MCQ questions
            if ($question['type'] === 'mcq') {
                $stmt = $pdo->prepare("INSERT INTO options (question_id, option_text) VALUES (?, ?)");
                foreach ($question['options'] as $option) {
                    $stmt->execute([$questionId, $option]);
                }
            }
        }

        echo json_encode(['success' => true, 'test_id' => $testId]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get all tests
        $stmt = $pdo->query("SELECT * FROM tests ORDER BY created_at DESC");
        $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'tests' => $tests]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?> 