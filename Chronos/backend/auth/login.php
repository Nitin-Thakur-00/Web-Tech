<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../middleware/ratelimit.php';
require_once __DIR__ . '/../middleware/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed', 405);
}

handleLoginRateLimit();
verifyCsrfToken();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$email = validateEmail($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$email || !$password) {
    jsonResponse(false, null, 'Invalid email or password.', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare('SELECT id, username, email, full_name, profile_pic, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true); // Prevent session fixation
        
        $_SESSION['user_id'] = $user['id'];
        
        // Regenerate CSRF token post-login so the pre-login guest token is invalidated
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Remove password hash from response
        unset($user['password_hash']);
        
        jsonResponse(true, ['user' => $user, 'redirect' => 'dashboard.php', 'csrf_token' => $_SESSION['csrf_token']], 'Login successful');
    } else {
        jsonResponse(false, null, 'Invalid email or password.', 401);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
