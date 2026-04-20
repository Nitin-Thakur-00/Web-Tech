<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';

checkAuth();
$userId = getUserId();
$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Verify user has access to this project (owner or member)
function verifyProjectAccess($db, $projectId, $userId) {
    if (!$projectId) jsonResponse(false, null, 'Project ID is required', 400);
    
    $stmt = $db->prepare('SELECT p.id FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id WHERE p.id = ? AND (p.owner_id = ? OR pm.user_id = ?)');
    $stmt->execute([$projectId, $userId, $userId]);
    if (!$stmt->fetch()) jsonResponse(false, null, 'Unauthorized or project not found', 403);
}

if ($method === 'GET') {
    $projectId = $_GET['project_id'] ?? null;
    verifyProjectAccess($db, $projectId, $userId);

    $stmt = $db->prepare('SELECT * FROM subtasks WHERE project_id = ? ORDER BY created_at ASC');
    $stmt->execute([$projectId]);
    jsonResponse(true, $stmt->fetchAll());

} else {
    verifyCsrfToken();
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    if ($method === 'POST') {
        $projectId = $input['project_id'] ?? null;
        verifyProjectAccess($db, $projectId, $userId);

        $title = $input['title'] ?? null;
        $assignedTo = $input['assigned_to'] ?? null;
        $isMilestone = !empty($input['is_milestone']) ? 1 : 0;

        if (!$title) jsonResponse(false, null, 'Title is required', 400);

        try {
            $stmt = $db->prepare('INSERT INTO subtasks (project_id, title, assigned_to, is_milestone) VALUES (?, ?, ?, ?)');
            $stmt->execute([$projectId, $title, $assignedTo, $isMilestone]);
            jsonResponse(true, ['id' => $db->lastInsertId()], 'Subtask created', 201);
        } catch (Exception $e) {
            error_log($e->getMessage());
            jsonResponse(false, null, 'Internal server error', 500);
        }

    } elseif ($method === 'PUT') {
        $subtaskId = $input['subtask_id'] ?? $input['id'] ?? null;
        if (!$subtaskId) jsonResponse(false, null, 'Subtask ID is required', 400);

        // Get project_id for auth verify
        $stmt = $db->prepare('SELECT project_id FROM subtasks WHERE id = ?');
        $stmt->execute([$subtaskId]);
        $subtask = $stmt->fetch();
        if (!$subtask) jsonResponse(false, null, 'Subtask not found', 404);

        verifyProjectAccess($db, $subtask['project_id'], $userId);

        $updatableFields = ['title', 'is_completed', 'assigned_to', 'is_milestone', 'priority'];
        $updates = [];
        $params = [];

        foreach ($updatableFields as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $params[] = $input[$field];
            }
        }

        if (empty($updates)) jsonResponse(false, null, 'No valid fields provided', 400);
        $params[] = $subtaskId;

        try {
            $stmt = $db->prepare('UPDATE subtasks SET ' . implode(', ', $updates) . ' WHERE id = ?');
            $stmt->execute($params);
            jsonResponse(true, null, 'Subtask updated');
        } catch (Exception $e) {
            error_log($e->getMessage());
            jsonResponse(false, null, 'Internal server error', 500);
        }

    } elseif ($method === 'DELETE') {
        $subtaskId = $input['subtask_id'] ?? $input['id'] ?? null;
        if (!$subtaskId) jsonResponse(false, null, 'Subtask ID is required', 400);

        $stmt = $db->prepare('SELECT project_id FROM subtasks WHERE id = ?');
        $stmt->execute([$subtaskId]);
        $subtask = $stmt->fetch();
        if (!$subtask) jsonResponse(false, null, 'Subtask not found', 404);

        verifyProjectAccess($db, $subtask['project_id'], $userId);

        try {
            $stmt = $db->prepare('DELETE FROM subtasks WHERE id = ?');
            $stmt->execute([$subtaskId]);
            jsonResponse(true, null, 'Subtask deleted');
        } catch (Exception $e) {
            error_log($e->getMessage());
            jsonResponse(false, null, 'Internal server error', 500);
        }
    } else {
        jsonResponse(false, null, 'Method not allowed', 405);
    }
}
