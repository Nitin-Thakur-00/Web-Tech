<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';
require_once __DIR__ . '/../../middleware/ratelimit.php';

checkAuth();
handleAiRateLimit();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed', 405);
}
verifyCsrfToken();

$userId = getUserId();
$db = Database::getInstance()->getConnection();
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$prompt = $input['prompt'] ?? null;
$context = $input['context'] ?? '';

if (!$prompt) {
    jsonResponse(false, null, 'Prompt is required', 400);
}

$provider = getenv('AI_PROVIDER') ?: 'gemini';
$aiResponse = '';

if ($provider === 'gemini') {
    $apiKey = getenv('GEMINI_API_KEY');
    if (!$apiKey) jsonResponse(false, null, 'Gemini API not configured', 500);

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;
    
    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => "Context: $context\n\nUser: $prompt"]
                ]
            ]
        ]
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($payload),
            'ignore_errors' => true
        ]
    ];

    $contextStream = stream_context_create($options);
    $result = @file_get_contents($url, false, $contextStream);
    
    if ($result) {
        $data = json_decode($result, true);
        $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Could not parse response';
    } else {
        $aiResponse = "API connection failed";
    }

} elseif ($provider === 'deepseek') {
    $apiKey = getenv('DEEPSEEK_API_KEY');
    if (!$apiKey) jsonResponse(false, null, 'DeepSeek API not configured', 500);

    $url = 'https://api.deepseek.com/v1/chat/completions';
    $payload = [
        'model' => 'deepseek-chat',
        'messages' => [
            ['role' => 'system', 'content' => $context],
            ['role' => 'user', 'content' => $prompt]
        ]
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\nAuthorization: Bearer $apiKey\r\n",
            'method'  => 'POST',
            'content' => json_encode($payload),
            'ignore_errors' => true
        ]
    ];

    $contextStream = stream_context_create($options);
    $result = @file_get_contents($url, false, $contextStream);
    
    if ($result) {
        $data = json_decode($result, true);
        $aiResponse = $data['choices'][0]['message']['content'] ?? 'Could not parse response';
    } else {
        $aiResponse = "API connection failed";
    }
}

try {
    $stmt = $db->prepare('INSERT INTO ai_conversations (user_id, prompt, response) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $prompt, $aiResponse]);
    jsonResponse(true, ['response' => $aiResponse]);
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, 'Internal server error', 500);
}
