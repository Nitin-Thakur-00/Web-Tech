<?php
require_once __DIR__ . '/../helpers/response.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function getCsrfTokenFromRequest() {
    $token = null;

    // 1. Check headers (case-insensitive) using getallheaders if available
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'X-CSRF-Token') === 0) {
                return $value;
            }
        }
    }

    // 2. Check $_SERVER for Apache mapping (typically HTTP_X_CSRF_TOKEN)
    if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    // 3. Check JSON body
    $json = json_decode(file_get_contents("php://input"), true);
    if (isset($json['csrf_token'])) {
        return $json['csrf_token'];
    }

    // 4. Check POST body
    return $_POST['csrf_token'] ?? null;
}

function verifyCsrfToken() {
    $method = $_SERVER['REQUEST_METHOD'];
    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
        $token = getCsrfTokenFromRequest();

        if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            // Debugging (optional, remove in production)
            // error_log("CSRF mismatch. Session: " . ($_SESSION['csrf_token'] ?? 'none') . " Received: " . ($token ?? 'none'));
            jsonResponse(false, null, 'Invalid CSRF token', 403);
        }
    }
}
