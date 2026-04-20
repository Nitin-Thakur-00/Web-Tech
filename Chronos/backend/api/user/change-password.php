<?php
/**
 * user/change-password.php — Change user password
 * NEW endpoint for the password change feature in settings.
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';

checkAuth();
verifyCsrfToken();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed', 405);
}

$userId = getUserId();
$db     = Database::getInstance()->getConnection();
$input  = json_decode(file_get_contents('php://input'), true);

$current = $input['current_password'] ?? '';
$new     = $input['new_password']     ?? '';

if (!$current || !$new) {
    jsonResponse(false, null, 'Both current and new password are required.', 400);
}

if (strlen($new) < 8) {
    jsonResponse(false, null, 'New password must be at least 8 characters.', 400);
}

try {
    // Fetch current password hash
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($current, $hash)) {
        jsonResponse(false, null, 'Current password is incorrect.', 403);
    }

    // Set new password
    $newHash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$newHash, $userId]);

    jsonResponse(true, null, null, 200);
} catch (Exception $e) {
    error_log('change-password: ' . $e->getMessage());
    jsonResponse(false, null, 'Internal server error.', 500);
}
