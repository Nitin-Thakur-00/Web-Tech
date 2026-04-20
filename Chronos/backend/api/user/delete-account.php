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

$password = $input['password'] ?? null;

$stmt = $db->prepare('SELECT password_hash, oauth_provider FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(false, null, 'User not found', 404);
}

// If using email/password login, check password
if (empty($user['oauth_provider'])) {
    if (!$password || !password_verify($password, $user['password_hash'])) {
        jsonResponse(false, null, 'Incorrect password', 403);
    }
}

try {
    // Delete user. Cascades will handle remaining user data delete
    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    
    session_destroy();
    
    jsonResponse(true, null, 'Account deleted successfully');
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
