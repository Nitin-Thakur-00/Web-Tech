<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';

checkAuth();

$userId = getUserId();
$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $projectId = $_GET['project_id'] ?? null;
    if (!$projectId) jsonResponse(false, null, 'Project ID is required', 400);

    // Verify access
    $stmt = $db->prepare('SELECT p.id FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id WHERE p.id = ? AND (p.owner_id = ? OR pm.user_id = ?)');
    $stmt->execute([$projectId, $userId, $userId]);
    if (!$stmt->fetch()) jsonResponse(false, null, 'Unauthorized', 403);

    $stmt = $db->prepare(
        'SELECT u.id, u.username, u.full_name, u.profile_pic, pm.role '
        . 'FROM project_members pm JOIN users u ON pm.user_id = u.id '
        . 'WHERE pm.project_id = ? ORDER BY pm.role DESC'
    );
    $stmt->execute([$projectId]);
    jsonResponse(true, $stmt->fetchAll());
}

if (!in_array($method, ['POST', 'DELETE', 'PUT'])) {
    jsonResponse(false, null, 'Method not allowed', 405);
}

verifyCsrfToken();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$projectId = $input['project_id'] ?? null;
if (!$projectId) {
    jsonResponse(false, null, 'Project ID is required', 400);
}

// Check if user is leader/owner
$stmt = $db->prepare('SELECT role FROM project_members WHERE project_id = ? AND user_id = ?');
$stmt->execute([$projectId, $userId]);
$member = $stmt->fetch();
$isLeader = ($member && $member['role'] === 'leader');

$stmt = $db->prepare('SELECT owner_id FROM projects WHERE id = ?');
$stmt->execute([$projectId]);
$project = $stmt->fetch();
if (!$project) jsonResponse(false, null, 'Project not found', 404);

$isOwner = ($project['owner_id'] == $userId);

if (!$isLeader && !$isOwner) {
    jsonResponse(false, null, 'Unauthorized to manage members', 403);
}

if ($method === 'POST') {
    $username = $input['username'] ?? null;
    if (!$username) jsonResponse(false, null, 'Username to invite is required', 400);

    // Get user id from username
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $newMember = $stmt->fetch();

    if (!$newMember) jsonResponse(false, null, 'User not found', 404);

    try {
        $role = in_array($input['role'] ?? '', ['member', 'leader']) ? $input['role'] : 'member';
        $stmt = $db->prepare('INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)');
        $stmt->execute([$projectId, $newMember['id'], $role]);
        jsonResponse(true, null, 'Member invited successfully', 201);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            jsonResponse(false, null, 'User is already a member', 400);
        }
        error_log($e->getMessage());
        jsonResponse(false, null, 'Internal server error', 500);
    }

} elseif ($method === 'PUT') {
    $targetUserId = $input['user_id'] ?? null;
    $newRole = $input['role'] ?? null;
    if (!$targetUserId || !$newRole) jsonResponse(false, null, 'User ID and role are required', 400);
    if (!in_array($newRole, ['member', 'leader'])) jsonResponse(false, null, 'Invalid role', 400);
    if ($targetUserId == $project['owner_id']) jsonResponse(false, null, 'Cannot change owner role', 400);

    try {
        $stmt = $db->prepare('UPDATE project_members SET role = ? WHERE project_id = ? AND user_id = ?');
        $stmt->execute([$newRole, $projectId, $targetUserId]);
        jsonResponse(true, null, 'Role updated successfully');
    } catch (Exception $e) {
        error_log($e->getMessage());
        jsonResponse(false, null, 'Internal server error', 500);
    }

} elseif ($method === 'DELETE') {
    $targetUserId = $input['user_id'] ?? null;
    if (!$targetUserId) jsonResponse(false, null, 'User ID to remove is required', 400);
    if ($targetUserId == $project['owner_id']) jsonResponse(false, null, 'Cannot remove project owner', 400);

    try {
        $stmt = $db->prepare('DELETE FROM project_members WHERE project_id = ? AND user_id = ?');
        $stmt->execute([$projectId, $targetUserId]);
        jsonResponse(true, null, 'Member removed successfully');
    } catch (Exception $e) {
        error_log($e->getMessage());
        jsonResponse(false, null, 'Internal server error', 500);
    }
}
