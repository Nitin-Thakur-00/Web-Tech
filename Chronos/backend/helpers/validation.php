<?php
function validateUsername($username) {
    // Must contain at least one letter, can include numbers
    return preg_match('/[A-Za-z]/', $username) && strlen($username) >= 3 && strlen($username) <= 50;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateEmail($email) {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}
