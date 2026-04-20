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

try {
    $query = "
        SELECT
            CASE
                WHEN f.user_id = ? THEN f.friend_id
                ELSE f.user_id
            END AS id,
            u.username,
            u.full_name,
            u.profile_pic,
            u.bio,
            f.status,
            CASE
                WHEN f.friend_id = ? AND f.status = 'pending' THEN 'incoming'
                WHEN f.user_id = ? AND f.status = 'pending' THEN 'outgoing'
                ELSE 'connected'
            END AS direction
        FROM friends f
        JOIN users u ON u.id = CASE WHEN f.user_id = ? THEN f.friend_id ELSE f.user_id END
        WHERE (f.user_id = ? OR f.friend_id = ?)
          AND f.status != 'blocked'
        ORDER BY f.created_at DESC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
    $records = $stmt->fetchAll();

    jsonResponse(true, $records);
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
