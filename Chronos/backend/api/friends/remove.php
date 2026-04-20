<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';

checkAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(false, null, 'Method not allowed', 405);
}
verifyCsrfToken();

$userId = getUserId();
$db = Database::getInstance()->getConnection();
$input = json_decode(file_get_contents('php://input'), true);

$friendId = $input['friend_id'] ?? null;
if (!$friendId) {
    jsonResponse(false, null, 'friend_id is required', 400);
}

try {
    // Remove in either direction
    $stmt = $db->prepare('DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)');
    $stmt->execute([$userId, $friendId, $friendId, $userId]);

    jsonResponse(true, null, 'Friend removed successfully');
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
