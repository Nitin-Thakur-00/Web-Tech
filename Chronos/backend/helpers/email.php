<?php
function sendReminderEmail($to, $taskTitle, $deadline) {
    // Send email via SMTP (optional, for reminders)
    $host = getenv('SMTP_HOST');
    $port = getenv('SMTP_PORT');
    $user = getenv('SMTP_USER');
    $pass = getenv('SMTP_PASS');

    if (!$host || !$user) {
        return false; // Email not configured
    }

    $subject = "Reminder: $taskTitle";
    $message = "Don't forget! The task '$taskTitle' is scheduled for $deadline.";
    $headers = "From: $user\r\n" .
               "Reply-To: $user\r\n" .
               "X-Mailer: PHP/" . phpversion();

    // Utilizing PHP's built in mail function as a stub, although a library like PHPMailer would be used in prod
    return mail($to, $subject, $message, $headers);
}
