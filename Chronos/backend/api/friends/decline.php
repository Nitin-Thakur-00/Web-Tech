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

$friendId = $input['friend_id'] ?? null;
if (!$friendId) {
    jsonResponse(false, null, 'friend_id is required', 400);
}

try {
    // Only the receiving user can decline (delete the request)
    $stmt = $db->prepare('DELETE FROM friends WHERE user_id = ? AND friend_id = ? AND status = "pending"');
    $stmt->execute([$friendId, $userId]);

    if ($stmt->rowCount() > 0) {
        jsonResponse(true, null, 'Friend request declined');
    } else {
        jsonResponse(false, null, 'No pending request found from this user', 404);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
