<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/session.php';

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, null, 'Unauthorized access - Please log in', 401);
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}
