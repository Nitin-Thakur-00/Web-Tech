<?php
require_once __DIR__ . '/../../config/env.php';
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

$playlistUrl = $input['playlist_url'] ?? null;
if (!$playlistUrl) {
    jsonResponse(false, null, 'Playlist URL is required', 400);
}

// Very basic youtube parsing and mockup API call
// Real app would use standard YouTube SDK, this does basic manual input fallback or fake calculation
$totalSeconds = 0;
// Note: Actual logic requires parsing $playlistUrl for ID and calling Google API.
// Simulated fetching length:
$totalSeconds = 3600 * mt_rand(1, 10);

try {
    $stmt = $db->prepare('INSERT INTO youtube_courses (user_id, playlist_url, total_seconds) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $playlistUrl, $totalSeconds]);
    jsonResponse(true, ['id' => $db->lastInsertId(), 'total_seconds' => $totalSeconds], 'Course added successfully', 201);
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
