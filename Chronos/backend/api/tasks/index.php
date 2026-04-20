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
    $filter = $_GET['filter'] ?? null;
    $projectId = $_GET['project_id'] ?? null;
    $tag = $_GET['tag'] ?? null;
    $isFlagged = isset($_GET['is_flagged']) ? (int)$_GET['is_flagged'] : null;
    $isCompleted = isset($_GET['is_completed']) ? (int)$_GET['is_completed'] : null;

    try {
        $query = "SELECT * FROM tasks WHERE user_id = :userId";
        $params = [':userId' => $userId];

        if ($projectId) {
            $query .= " AND project_id = :projectId";
            $params[':projectId'] = $projectId;
        }
        if ($tag) {
            $query .= " AND tag = :tag";
            $params[':tag'] = $tag;
        }
        if ($isFlagged !== null) {
            $query .= " AND is_flagged = :isFlagged";
            $params[':isFlagged'] = $isFlagged;
        }
        if ($isCompleted !== null) {
            $query .= " AND is_completed = :isCompleted";
            $params[':isCompleted'] = $isCompleted;
        }

        if ($filter) {
            if ($filter === 'today') {
                $query .= " AND DATE(deadline) = CURDATE()";
            } elseif ($filter === 'week') {
                $query .= " AND YEARWEEK(deadline, 1) = YEARWEEK(CURDATE(), 1)";
            } elseif ($filter === 'month') {
                $query .= " AND MONTH(deadline) = MONTH(CURDATE()) AND YEAR(deadline) = YEAR(CURDATE())";
            }
        }

        $query .= " ORDER BY is_completed ASC, deadline ASC, is_flagged DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll();

        jsonResponse(true, $tasks);
    } catch (Exception $e) {
        error_log("Tasks Fetch Error: " . $e->getMessage());
        jsonResponse(false, null, 'Failed to fetch tasks', 500);
    }

} elseif ($method === 'POST') {
    verifyCsrfToken();
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if (empty($input['title'])) {
        jsonResponse(false, null, 'Title is required', 400);
    }

    $title = $input['title'];
    $projectId = !empty($input['project_id']) ? $input['project_id'] : null;
    $description = $input['description'] ?? null;
    $deadline = !empty($input['deadline']) ? date('Y-m-d H:i:s', strtotime($input['deadline'])) : null;
    $isFlagged = !empty($input['is_flagged']) ? 1 : 0;
    $tag = $input['tag'] ?? null;
    $reminderTime = !empty($input['reminder_time']) ? date('Y-m-d H:i:s', strtotime($input['reminder_time'])) : null;

    try {
        $stmt = $db->prepare('INSERT INTO tasks (user_id, project_id, title, description, deadline, is_flagged, tag, reminder_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $projectId, $title, $description, $deadline, $isFlagged, $tag, $reminderTime]);
        jsonResponse(true, ['id' => $db->lastInsertId()], 'Task created successfully', 201);
    } catch (Exception $e) {
        error_log($e->getMessage());
        jsonResponse(false, null, 'Internal server error', 500);
    }
} else {
    jsonResponse(false, null, 'Method not allowed', 405);
}
