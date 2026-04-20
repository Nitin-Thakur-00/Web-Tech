<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

checkAuth();

$userId = getUserId();
$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(false, null, 'Method not allowed', 405);
}

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

if (!$startDate || !$endDate) {
    jsonResponse(false, null, 'start_date and end_date are required', 400);
}

try {
    $events = [];

    $stmt = $db->prepare('
        SELECT id, title, DATE(deadline) AS event_date, "task" AS source_type, is_flagged, project_id AS related_project_id
        FROM tasks
        WHERE user_id = ?
          AND deadline IS NOT NULL
          AND DATE(deadline) BETWEEN ? AND ?
    ');
    $stmt->execute([$userId, $startDate, $endDate]);
    $events = array_merge($events, $stmt->fetchAll());

    $stmt = $db->prepare('
        SELECT p.id, p.name AS title, p.deadline AS event_date, "project" AS source_type, 0 AS is_flagged, p.id AS related_project_id
        FROM projects p
        LEFT JOIN project_members pm ON p.id = pm.project_id
        WHERE (p.owner_id = ? OR pm.user_id = ?) 
        AND p.deadline IS NOT NULL
        AND p.deadline BETWEEN ? AND ?
        GROUP BY p.id
    ');
    $stmt->execute([$userId, $userId, $startDate, $endDate]);
    $events = array_merge($events, $stmt->fetchAll());

    $stmt = $db->prepare('
        SELECT id, title, event_date, source_type, 0 AS is_flagged, NULL AS related_project_id
        FROM calendar_events
        WHERE user_id = ? AND event_date BETWEEN ? AND ?
    ');
    $stmt->execute([$userId, $startDate, $endDate]);
    $events = array_merge($events, $stmt->fetchAll());

    usort($events, function($a, $b) {
        return strtotime($a['event_date']) - strtotime($b['event_date']);
    });

    jsonResponse(true, $events);
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
