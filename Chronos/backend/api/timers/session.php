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

$durationMinutes = isset($input['duration_minutes']) ? (int)$input['duration_minutes'] : 0;
$sessionType = $input['session_type'] ?? 'pomodoro';
$sessionDate = date('Y-m-d');

if ($durationMinutes <= 0) {
    jsonResponse(false, null, 'Valid duration is required', 400);
}

$validTypes = ['pomodoro', 'study', 'break'];
if (!in_array($sessionType, $validTypes)) {
    jsonResponse(false, null, 'Invalid session type', 400);
}

try {
    $stmt = $db->prepare('INSERT INTO timer_sessions (user_id, duration_minutes, session_type, session_date) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $durationMinutes, $sessionType, $sessionDate]);
    jsonResponse(true, ['id' => $db->lastInsertId()], 'Timer session logged successfully', 201);
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
