<?php
/**
 * Cron Job Script for Reminders
 * Run every hour via CLI
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/email.php';

$db = Database::getInstance()->getConnection();

try {
    // Select tasks needing reminders
    // Assuming we do not continually send them, so we'd flag them as sent in a real app,
    // but schema provided does not have `reminder_sent`. 
    // For this demonstration, we query what's past due and uncompleted
    
    // In actual implementation without `reminder_sent`, this could spam.
    // As a workaround, check tasks strictly within the last hour.
    
    $query = "
        SELECT t.id, t.title, t.deadline, u.email, u.full_name 
        FROM tasks t
        JOIN users u ON t.user_id = u.id
        WHERE t.is_completed = 0 
        AND t.reminder_time IS NOT NULL
        AND t.reminder_time <= NOW()
        AND t.reminder_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tasks = $stmt->fetchAll();
    
    foreach ($tasks as $task) {
        $sent = sendReminderEmail($task['email'], $task['title'], $task['deadline']);
        echo "Sent reminder for {$task['id']} to {$task['email']}: " . ($sent ? "SUCCESS" : "FAIL") . "\n";
    }

    echo "Cron executed successfully. Found " . count($tasks) . " reminders.\n";

} catch (Exception $e) {
    error_log("Cron Error: " . $e->getMessage());
    echo "Error processing reminders.";
}
