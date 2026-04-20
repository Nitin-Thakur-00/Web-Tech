<?php
require_once __DIR__ . '/../config/config.php';

session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session and send browser back to login page
session_destroy();
header('Location: ../../index.php');
exit;
