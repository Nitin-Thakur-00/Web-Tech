<?php
if (ob_get_level() === 0) {
    ob_start();
}

function jsonResponse($success, $data = null, $error = null, $statusCode = 200) {
    // Clear any preceding output (warnings, notices) to ensure valid JSON
    if (ob_get_length()) {
        ob_clean();
    }
    
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}
