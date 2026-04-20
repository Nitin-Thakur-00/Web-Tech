<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

session_start();

$clientId = getenv('GITHUB_CLIENT_ID');
$clientSecret = getenv('GITHUB_CLIENT_SECRET');
$redirectUri = getenv('GITHUB_REDIRECT_URI');

if (isset($_GET['code'])) {
    $tokenUrl = 'https://github.com/login/oauth/access_token';
    $data = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'code' => $_GET['code'],
        'redirect_uri' => $redirectUri
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($tokenUrl, false, $context);

    if ($result === FALSE) {
        die("Error exchanging code for token");
    }

    $tokenData = json_decode($result, true);
    $accessToken = $tokenData['access_token'];

    // Get user info
    $userInfoUrl = 'https://api.github.com/user';
    $options = [
        'http' => [
            'header' => "Authorization: Bearer $accessToken\r\nUser-Agent: Chronos-App\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $userInfo = @file_get_contents($userInfoUrl, false, $context);

    if ($userInfo) {
        $userData = json_decode($userInfo, true);
        $oauthId = $userData['id'];
        $username = $userData['login'];
        $email = $userData['email'] ?? ($username . '@github.local'); // Provide fallback if email not public
        $name = $userData['name'] ?? null;
        $picture = $userData['avatar_url'] ?? null;

        $db = Database::getInstance()->getConnection();

        // Check if user exists via oauth_id or email
        $stmt = $db->prepare('SELECT id FROM users WHERE oauth_id = ? OR email = ?');
        $stmt->execute([$oauthId, $email]);
        $user = $stmt->fetch();

        if ($user) {
            $stmt = $db->prepare('UPDATE users SET oauth_provider = "github", oauth_id = ? WHERE id = ?');
            $stmt->execute([$oauthId, $user['id']]);
            $_SESSION['user_id'] = $user['id'];
        } else {
            // Guarantee unique username
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $username .= rand(100, 999);
            }

            $stmt = $db->prepare('INSERT INTO users (username, email, full_name, profile_pic, oauth_provider, oauth_id) VALUES (?, ?, ?, ?, "github", ?)');
            $stmt->execute([$username, $email, $name, $picture, $oauthId]);
            $_SESSION['user_id'] = $db->lastInsertId();
        }

        // Redirect to dashboard
        header('Location: /dashboard.php');
        exit;
    }
} else {
    // Generate OAuth login URL
    $authUrl = "https://github.com/login/oauth/authorize?" . http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'scope' => 'user:email'
    ]);
    header('Location: ' . $authUrl);
    exit;
}
