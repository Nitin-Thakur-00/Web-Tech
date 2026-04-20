<?php
/**
 * timers/sessions.php — GET recent focus sessions
 * Fixed: This was the missing endpoint causing "API.getSessions is not a function" errors.
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

checkAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, null, 'Method not allowed', 405);
}

$userId = getUserId();
$db     = Database::getInstance()->getConnection();
$limit  = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 10;

try {
    $stmt = $db->prepare("
        SELECT id, duration_minutes, session_type, session_date, created_at
        FROM timer_sessions
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT " . (int)$limit . "
    ");
    $stmt->execute([$userId]);

    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(true, $sessions);
} catch (Exception $e) {
    error_log('sessions.php: ' . $e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
