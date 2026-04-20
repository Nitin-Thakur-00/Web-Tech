<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';

checkAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed', 405);
}
verifyCsrfToken();

$userId = getUserId();
$db = Database::getInstance()->getConnection();
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$date = $input['date'] ?? null;
if (!$date) {
    jsonResponse(false, null, 'date is required', 400);
}

try {
    $events = [];

    $stmt = $db->prepare('
        SELECT id, title, DATE(deadline) AS event_date, "task" AS source_type, is_flagged, project_id AS related_project_id
        FROM tasks
        WHERE user_id = ? AND DATE(deadline) = ?
    ');
    $stmt->execute([$userId, $date]);
    $events = array_merge($events, $stmt->fetchAll());

    $stmt = $db->prepare('
        SELECT p.id, p.name AS title, p.deadline AS event_date, "project" AS source_type, 0 AS is_flagged, p.id AS related_project_id
        FROM projects p
        LEFT JOIN project_members pm ON p.id = pm.project_id
        WHERE (p.owner_id = ? OR pm.user_id = ?)
          AND p.deadline = ?
        GROUP BY p.id
    ');
    $stmt->execute([$userId, $userId, $date]);
    $events = array_merge($events, $stmt->fetchAll());

    $stmt = $db->prepare('
        SELECT id, title, event_date, source_type, 0 AS is_flagged, NULL AS related_project_id
        FROM calendar_events
        WHERE user_id = ? AND event_date = ?
    ');
    $stmt->execute([$userId, $date]);
    $events = array_merge($events, $stmt->fetchAll());

    usort($events, function($a, $b) {
        return strcmp($a['title'], $b['title']);
    });

    jsonResponse(true, $events);
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
