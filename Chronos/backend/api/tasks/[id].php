<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';

checkAuth();
$userId = getUserId();
$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Parse ID from URL or query string.
$taskId = $_GET['id'] ?? null;
if (!$taskId) {
    // If routing directly e.g. /tasks/5.php
    $taskId = basename(__FILE__, '.php');
    if (!is_numeric($taskId)) {
        jsonResponse(false, null, 'Task ID is required', 400);
    }
}

// Verify task ownership
$stmt = $db->prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ?');
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    jsonResponse(false, null, 'Task not found or unauthorized', 404);
}

if ($method === 'GET') {
    jsonResponse(true, $task);
} elseif ($method === 'PUT') {
    verifyCsrfToken();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $updatableFields = ['title', 'description', 'deadline', 'is_flagged', 'tag', 'reminder_time', 'project_id', 'is_completed'];
    $updates = [];
    $params = [];

    foreach ($updatableFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            if (in_array($field, ['deadline', 'reminder_time']) && $input[$field]) {
                $params[] = date('Y-m-d H:i:s', strtotime($input[$field]));
            } else {
                $params[] = $input[$field];
            }
        }
    }

    if (empty($updates)) {
        jsonResponse(false, null, 'No valid fields provided for update', 400);
    }

    $params[] = $taskId;
    $params[] = $userId;

    try {
        $stmt = $db->prepare('UPDATE tasks SET ' . implode(', ', $updates) . ' WHERE id = ? AND user_id = ?');
        $stmt->execute($params);
        jsonResponse(true, null, 'Task updated successfully');
    } catch (Exception $e) {
        error_log($e->getMessage());
        jsonResponse(false, null, 'Internal server error', 500);
    }
} elseif ($method === 'DELETE') {
    verifyCsrfToken();
    try {
        $stmt = $db->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
        $stmt->execute([$taskId, $userId]);
        jsonResponse(true, null, 'Task deleted successfully');
    } catch (Exception $e) {
        error_log($e->getMessage());
        jsonResponse(false, null, 'Internal server error', 500);
    }
} else {
    jsonResponse(false, null, 'Method not allowed', 405);
}
