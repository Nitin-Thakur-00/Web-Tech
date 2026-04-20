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
    $includePast = isset($_GET['include_past']) && $_GET['include_past'] == 'true' ? 1 : 0;
    
    try {
        $pastFilter = $includePast ? '' : ' AND p.is_past = 0';
        $query = "
            SELECT p.* FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.owner_id = :ownerId OR pm.user_id = :memberId)
            {$pastFilter}
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ";

        $stmt = $db->prepare($query);
        $stmt->execute([':ownerId' => $userId, ':memberId' => $userId]);
        $projects = $stmt->fetchAll();

        jsonResponse(true, $projects);
    } catch (Exception $e) {
        error_log("Projects Fetch Error: " . $e->getMessage());
        jsonResponse(false, null, 'Failed to fetch projects', 500);
    }

} elseif ($method === 'POST') {
    verifyCsrfToken();
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    if (empty($input['name'])) {
        jsonResponse(false, null, 'Project name is required', 400);
    }

    $name = $input['name'];
    $description = $input['description'] ?? null;
    $deadline = !empty($input['deadline']) ? $input['deadline'] : null;
    $githubRepo = $input['github_repo'] ?? null;
    $colour = $input['colour'] ?? '#4f46e5';
    $isTeam = !empty($input['is_team']) ? 1 : 0;
    $members = !empty($input['members']) && is_array($input['members']) ? $input['members'] : [];

    try {
        $db->beginTransaction();

        $stmt = $db->prepare('INSERT INTO projects (name, description, deadline, github_repo, colour, is_team, owner_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $description, $deadline, $githubRepo, $colour, $isTeam, $userId]);
        $projectId = $db->lastInsertId();

        // Add owner as leader member automatically if it's a team
        if ($isTeam) {
            $stmt = $db->prepare('INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, "leader")');
            $stmt->execute([$projectId, $userId]);

            // Add other members if any
            if (!empty($members)) {
                $memberStmt = $db->prepare('INSERT INTO project_members (project_id, user_id, role) VALUES (?, (SELECT id FROM users WHERE username = ?), "member")');
                foreach ($members as $username) {
                    try {
                        $memberStmt->execute([$projectId, $username]);
                    } catch (Exception $e) {
                        // Ignore if username not found
                    }
                }
            }
        }

        $db->commit();
        jsonResponse(true, ['id' => $projectId], 'Project created successfully', 201);
    } catch (Exception $e) {
        $db->rollBack();
        error_log($e->getMessage());
        jsonResponse(false, null, 'Internal server error', 500);
    }
} else {
    jsonResponse(false, null, 'Method not allowed', 405);
}
