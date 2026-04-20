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

verifyCsrfToken();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$username = sanitizeInput($input['username'] ?? '');
$email = validateEmail($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$username || !validateUsername($username)) {
    jsonResponse(false, null, 'Invalid username. Must contain at least one letter and be 3-50 chars long.', 400);
}

if (!$email) {
    jsonResponse(false, null, 'Invalid email format.', 400);
}

if (strlen($password) < 8) {
    jsonResponse(false, null, 'Password must be at least 8 characters.', 400);
}

$db = Database::getInstance()->getConnection();

// Check unique
$stmt = $db->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
$stmt->execute([$email, $username]);
if ($stmt->fetch()) {
    jsonResponse(false, null, 'Email or username already exists.', 400);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    $stmt = $db->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, $hash]);
    $userId = $db->lastInsertId();
    
    jsonResponse(true, ['user_id' => $userId], 'Registration successful', 201);
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
