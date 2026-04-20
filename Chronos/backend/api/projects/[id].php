<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';

checkAuth();
$userId = getUserId();
$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

$projectId = $_GET['id'] ?? basename(__FILE__, '.php');
if (!is_numeric($projectId)) {
    jsonResponse(false, null, 'Project ID is required', 400);
}

// Verify project access
$stmt = $db->prepare('SELECT p.* FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id WHERE p.id = ? AND (p.owner_id = ? OR pm.user_id = ?)');
$stmt->execute([$projectId, $userId, $userId]);
$project = $stmt->fetch();

if (!$project) {
    jsonResponse(false, null, 'Project not found or unauthorized', 404);
}

if ($method === 'GET') {
    // Fetch members
    $stmt = $db->prepare('SELECT u.id, u.username, u.profile_pic, pm.role FROM project_members pm JOIN users u ON pm.user_id = u.id WHERE pm.project_id = ?');
    $stmt->execute([$projectId]);
    $project['members'] = $stmt->fetchAll();

    // Fetch subtasks
    $stmt = $db->prepare('SELECT * FROM subtasks WHERE project_id = ?');
    $stmt->execute([$projectId]);
    $project['subtasks'] = $stmt->fetchAll();

    jsonResponse(true, $project);

} elseif ($method === 'PUT') {
    verifyCsrfToken();
    if ($project['owner_id'] != $userId) {
        jsonResponse(false, null, 'Only the owner can update the project', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $updatableFields = ['name', 'description', 'deadline', 'github_repo', 'colour', 'is_team', 'is_past', 'notes'];
    $updates = [];
    $params = [];

    foreach ($updatableFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            $params[] = $input[$field];
        }
    }

    if (empty($updates)) {
        jsonResponse(false, null, 'No valid fields provided for update', 400);
    }

    $params[] = $projectId;

    try {
        $stmt = $db->prepare('UPDATE projects SET ' . implode(', ', $updates) . ' WHERE id = ?');
        $stmt->execute($params);
        jsonResponse(true, null, 'Project updated successfully');
    } catch (Exception $e) {
        error_log($e->getMessage());
        jsonResponse(false, null, 'Internal server error', 500);
    }

} elseif ($method === 'DELETE') {
    verifyCsrfToken();
    if ($project['owner_id'] != $userId) {
        jsonResponse(false, null, 'Only the owner can delete the project', 403);
    }

    try {
        $stmt = $db->prepare('DELETE FROM projects WHERE id = ?');
        $stmt->execute([$projectId]);
        jsonResponse(true, null, 'Project deleted successfully');
    } catch (Exception $e) {
        error_log($e->getMessage());
        jsonResponse(false, null, 'Internal server error', 500);
    }
} else {
    jsonResponse(false, null, 'Method not allowed', 405);
}
