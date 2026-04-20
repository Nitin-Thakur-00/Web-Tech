<?php
require_once __DIR__ . '/../helpers/response.php';

// Very basic local filesystem-based rate limiting. 
// For production, Redis or Memcached is strongly recommended.
function checkRateLimit($identifier, $limit, $timeWindowSeconds) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = "ratelimit_" . md5($identifier);
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }

    // Filter out requests older than the time window
    $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $timeWindowSeconds) {
        return ($now - $timestamp) < $timeWindowSeconds;
    });

    if (count($_SESSION[$key]) >= $limit) {
        jsonResponse(false, null, 'Too many requests. Please try again later.', 429);
    }

    $_SESSION[$key][] = $now;
}

function handleLoginRateLimit() {
    // Disabled temporarily to unblock user
    // $ip = $_SERVER['REMOTE_ADDR'];
    // checkRateLimit("login_$ip", 5, 900); 
}

function handleApiRateLimit() {
    $uid = $_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR'];
    checkRateLimit("api_$uid", 60, 60); // 60 requests per user/IP per 1 minute
}

function handleAiRateLimit() {
    $uid = $_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR'];
    checkRateLimit("ai_$uid", 30, 3600); // 30 requests per user/IP per 1 hour
}
