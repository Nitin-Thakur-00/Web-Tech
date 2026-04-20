<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validation.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';

checkAuth();

$userId = getUserId();
$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT id, username, email, full_name, bio, profile_pic, created_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(false, null, 'User not found', 404);
    }
    
    jsonResponse(true, $user);

} elseif ($method === 'PUT') {
    verifyCsrfToken();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $updatableFields = ['full_name', 'bio', 'username'];
    $updates = [];
    $params = [];

    if (isset($input['username'])) {
        $username = sanitizeInput($input['username']);
        if (!validateUsername($username)) {
            jsonResponse(false, null, 'Invalid username format', 400);
        }
        
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $stmt->execute([$username, $userId]);
        if ($stmt->fetch()) {
            jsonResponse(false, null, 'Username is already taken', 400);
        }
        $updates[] = "username = ?";
        $params[] = $username;
    }

    foreach (['full_name', 'bio'] as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            $params[] = sanitizeInput($input[$field]); // sanitize plain text bounds
        }
    }

    if (empty($updates)) {
        jsonResponse(false, null, 'No valid fields provided', 400);
    }

    $params[] = $userId;

    try {
        $stmt = $db->prepare('UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?');
        $stmt->execute($params);
        jsonResponse(true, null, 'Profile updated successfully');
    } catch (Exception $e) {
        error_log($e->getMessage());
        jsonResponse(false, null, 'Internal server error', 500);
    }

} else {
    jsonResponse(false, null, 'Method not allowed', 405);
}
