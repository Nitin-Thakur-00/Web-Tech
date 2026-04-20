<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';

checkAuth();
$userId = getUserId();
$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

function verifyProjectAccess($db, $projectId, $userId) {
    if (!$projectId) jsonResponse(false, null, 'Project ID is required', 400);
    $stmt = $db->prepare('SELECT p.id FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id WHERE p.id = ? AND (p.owner_id = ? OR pm.user_id = ?)');
    $stmt->execute([$projectId, $userId, $userId]);
    if (!$stmt->fetch()) jsonResponse(false, null, 'Unauthorized or project not found', 403);
}

if ($method === 'GET') {
    $projectId = $_GET['project_id'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    
    verifyProjectAccess($db, $projectId, $userId);

    // Fetch messages where it's for the project and either not private, or I am the sender, or I am the recipient
    $query = "
        SELECT c.*, u.username as sender_name, u.profile_pic as sender_pic
        FROM team_chat_messages c
        JOIN users u ON c.sender_id = u.id
        WHERE c.project_id = :projectId 
        AND (c.is_private = 0 OR c.sender_id = :userId1 OR c.recipient_id = :userId2)
        ORDER BY c.created_at DESC
        LIMIT :limit
    ";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':projectId', $projectId, PDO::PARAM_INT);
    $stmt->bindValue(':userId1', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':userId2', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    // Return in chronological order
    $messages = array_reverse($stmt->fetchAll());
    jsonResponse(true, $messages);

} elseif ($method === 'POST') {
    verifyCsrfToken();
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $projectId = $input['project_id'] ?? null;
    verifyProjectAccess($db, $projectId, $userId);

    $message = $input['message'] ?? null;
    if (!$message) jsonResponse(false, null, 'Message is required', 400);

    $recipientId = $input['recipient_id'] ?? null;
    $isPrivate = 0;

    // Parse @username to set recipient_id for private whispers
    // Assumes format: @username hello there
    if (preg_match('/^@([a-zA-Z0-9_]{3,50})\b/', $message, $matches)) {
        $mentionedUsername = $matches[1];
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$mentionedUsername]);
        $user = $stmt->fetch();
        if ($user) {
            $recipientId = $user['id'];
            $isPrivate = 1;
        }
    }

    try {
        $stmt = $db->prepare('INSERT INTO team_chat_messages (project_id, sender_id, message, is_private, recipient_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$projectId, $userId, $message, $isPrivate, $recipientId]);
        
        $msgId = $db->lastInsertId();
        
        // Return structured new message
        $stmt = $db->prepare('SELECT c.*, u.username as sender_name, u.profile_pic as sender_pic FROM team_chat_messages c JOIN users u ON c.sender_id = u.id WHERE c.id = ?');
        $stmt->execute([$msgId]);

        jsonResponse(true, $stmt->fetch(), 'Message sent', 201);
    } catch (Exception $e) {
        error_log($e->getMessage());
        jsonResponse(false, null, 'Internal server error', 500);
    }
} else {
    jsonResponse(false, null, 'Method not allowed', 405);
}
