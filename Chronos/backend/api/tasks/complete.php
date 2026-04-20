<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';

checkAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse(false, null, 'Method not allowed', 405);
}
verifyCsrfToken();

$userId = getUserId();
$db = Database::getInstance()->getConnection();
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['task_id']) || !isset($input['is_completed'])) {
    jsonResponse(false, null, 'task_id and is_completed are required', 400);
}

$taskId = $input['task_id'];
$isCompleted = $input['is_completed'] ? 1 : 0;

try {
    $stmt = $db->prepare('UPDATE tasks SET is_completed = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$isCompleted, $taskId, $userId]);
    
    if ($stmt->rowCount() > 0) {
        jsonResponse(true, null, 'Task completion status updated');
    } else {
        jsonResponse(false, null, 'Task not found or unauthorized', 404);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
