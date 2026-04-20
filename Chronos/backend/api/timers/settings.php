<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';

checkAuth();

$userId = getUserId();
$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT pomodoro_cycles FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) jsonResponse(false, null, 'User not found', 404);
    
    jsonResponse(true, [
        'pomodoro_cycles' => (int)$user['pomodoro_cycles'],
        // Default values assumed unless we alter schema to add them
        'default_work_minutes' => 25,
        'default_break_minutes' => 5
    ]);
} elseif ($method === 'PUT') {
    verifyCsrfToken();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $cycles = isset($input['pomodoro_cycles']) ? (int)$input['pomodoro_cycles'] : null;
    
    if ($cycles === null || $cycles < 1 || $cycles > 10) {
        jsonResponse(false, null, 'Valid pomodoro_cycles (1-10) is required', 400);
    }
    
    try {
        $stmt = $db->prepare('UPDATE users SET pomodoro_cycles = ? WHERE id = ?');
        $stmt->execute([$cycles, $userId]);
        jsonResponse(true, null, 'Timer settings updated');
    } catch (Exception $e) {
        error_log($e->getMessage());
        jsonResponse(false, null, 'Internal server error', 500);
    }
} else {
    jsonResponse(false, null, 'Method not allowed', 405);
}
