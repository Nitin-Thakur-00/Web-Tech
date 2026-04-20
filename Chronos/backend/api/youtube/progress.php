<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';

checkAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse(false, null, 'Method not allowed', 405);
}
verifyCsrfToken();

$userId = getUserId();
$db = Database::getInstance()->getConnection();
$input = json_decode(file_get_contents('php://input'), true);

$courseId = $input['course_id'] ?? null;
$watchedSeconds = isset($input['watched_seconds']) ? (int)$input['watched_seconds'] : null;

if (!$courseId || $watchedSeconds === null || $watchedSeconds < 0) {
    jsonResponse(false, null, 'Invalid course_id or watched_seconds', 400);
}

try {
    $stmt = $db->prepare('SELECT id, total_seconds FROM youtube_courses WHERE id = ? AND user_id = ?');
    $stmt->execute([$courseId, $userId]);
    $course = $stmt->fetch();
    
    if (!$course) {
        jsonResponse(false, null, 'Course not found or unauthorized', 404);
    }
    
    $totalSeconds = $course['total_seconds'] > 0 ? $course['total_seconds'] : 1; // avoid divide by zero
    $completionPercentage = min(100, round(($watchedSeconds / $totalSeconds) * 100, 2));

    $stmt = $db->prepare('UPDATE youtube_courses SET watched_seconds = ? WHERE id = ?');
    $stmt->execute([$watchedSeconds, $courseId]);

    jsonResponse(true, ['completion_percentage' => $completionPercentage], 'Progress updated');
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
