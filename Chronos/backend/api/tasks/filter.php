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

$dateFrom = !empty($input['date_from']) ? date('Y-m-d', strtotime($input['date_from'])) : null;
$dateTo = !empty($input['date_to']) ? date('Y-m-d', strtotime($input['date_to'])) : null;
$tags = !empty($input['tags']) && is_array($input['tags']) ? $input['tags'] : [];
$priorities = !empty($input['priorities']) && is_array($input['priorities']) ? $input['priorities'] : []; // assumes 1 = flagged, 0 = unflagged
$projects = !empty($input['projects']) && is_array($input['projects']) ? $input['projects'] : [];

$query = "SELECT * FROM tasks WHERE user_id = ?";
$params = [$userId];

if ($dateFrom) {
    $query .= " AND DATE(deadline) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $query .= " AND DATE(deadline) <= ?";
    $params[] = $dateTo;
}

if (!empty($tags)) {
    $placeholders = implode(',', array_fill(0, count($tags), '?'));
    $query .= " AND tag IN ($placeholders)";
    $params = array_merge($params, $tags);
}

if (!empty($priorities)) {
    $placeholders = implode(',', array_fill(0, count($priorities), '?'));
    $query .= " AND is_flagged IN ($placeholders)";
    $params = array_merge($params, $priorities);
}

if (!empty($projects)) {
    $placeholders = implode(',', array_fill(0, count($projects), '?'));
    $query .= " AND project_id IN ($placeholders)";
    $params = array_merge($params, $projects);
}

$query .= " ORDER BY deadline ASC";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
    jsonResponse(true, $tasks);
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
