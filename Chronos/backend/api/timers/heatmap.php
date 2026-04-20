<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

try {
    checkAuth();
} catch (Exception $e) {
    jsonResponse(false, null, 'Unauthorized', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, null, 'Method not allowed', 405);
}

try {
    $userId = getUserId();
    $db = Database::getInstance()->getConnection();
    $year = $_GET['year'] ?? date('Y');

    if (!is_numeric($year)) {
        jsonResponse(false, null, 'Invalid year parameter', 400);
    }

    // Return array of { date, minutes } for last 365 days or specific year
    $query = "
        SELECT session_date AS date, SUM(duration_minutes) AS minutes
        FROM timer_sessions
        WHERE user_id = ?
          AND YEAR(session_date) = ?
        GROUP BY session_date
        ORDER BY session_date ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$userId, (int)$year]);
    $heatmapData = $stmt->fetchAll();

    jsonResponse(true, $heatmapData);
} catch (Exception $e) {
    error_log("Heatmap Error: " . $e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
