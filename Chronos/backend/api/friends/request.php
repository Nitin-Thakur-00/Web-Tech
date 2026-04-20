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

$identifier = $input['username'] ?? $input['uid'] ?? $input['friend_id'] ?? null;
if (!$identifier) {
    jsonResponse(false, null, 'Username or uid is required', 400);
}

try {
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR id = ?');
    $stmt->execute([$identifier, $identifier]);
    $friend = $stmt->fetch();

    if (!$friend) {
        jsonResponse(false, null, 'User not found', 404);
    }
    
    $friendId = $friend['id'];
    if ($friendId == $userId) {
        jsonResponse(false, null, 'You cannot send a friend request to yourself', 400);
    }

    $stmt = $db->prepare('SELECT status FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)');
    $stmt->execute([$userId, $friendId, $friendId, $userId]);
    $existing = $stmt->fetch();

    if ($existing) {
        jsonResponse(false, null, "Friendship status: {$existing['status']}", 400);
    }

    $stmt = $db->prepare('INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, "pending")');
    $stmt->execute([$userId, $friendId]);

    jsonResponse(true, null, 'Friend request sent', 201);
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
