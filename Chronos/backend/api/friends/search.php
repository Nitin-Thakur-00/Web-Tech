<?php
/**
 * friends/search.php — Search users by username (partial) or exact UID
 * NEW endpoint for the username-based friend discovery feature.
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
$query  = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    jsonResponse(true, []);
}

try {
    // Search by username (partial, case-insensitive) OR by exact numeric UID
    $isUid      = is_numeric($query) && (int)$query > 0;
    $searchPart = '%' . $query . '%';

    $stmt = $db->prepare('
        SELECT id, username, full_name, profile_pic, bio
        FROM users
        WHERE id != :me
          AND (username LIKE :search OR (:is_uid = 1 AND id = :uid))
        LIMIT 10
    ');
    $stmt->bindValue(':me',     $userId, PDO::PARAM_INT);
    $stmt->bindValue(':search', $searchPart, PDO::PARAM_STR);
    $stmt->bindValue(':is_uid', $isUid ? 1 : 0, PDO::PARAM_INT);
    $stmt->bindValue(':uid',    $isUid ? (int)$query : 0, PDO::PARAM_INT);
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(true, $users);
} catch (Exception $e) {
    error_log('friends/search.php: ' . $e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
