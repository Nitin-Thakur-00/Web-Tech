<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start(['name' => 'chronos_session']);
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
