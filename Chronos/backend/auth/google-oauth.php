<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

session_start();

$clientId = getenv('GOOGLE_CLIENT_ID');
$clientSecret = getenv('GOOGLE_CLIENT_SECRET');
$redirectUri = getenv('GOOGLE_REDIRECT_URI');

if (isset($_GET['code'])) {
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $data = [
        'code' => $_GET['code'],
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
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
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $options = [
        'http' => [
            'header' => "Authorization: Bearer $accessToken\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $userInfo = @file_get_contents($userInfoUrl, false, $context);
    
    if ($userInfo) {
        $userData = json_decode($userInfo, true);
        $oauthId = $userData['id'];
        $email = $userData['email'];
        $name = $userData['name'] ?? null;
        $picture = $userData['picture'] ?? null;

        $db = Database::getInstance()->getConnection();

        // Check if user exists
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Update OAuth provider if necessary
            $stmt = $db->prepare('UPDATE users SET oauth_provider = "google", oauth_id = ?, profile_pic = COALESCE(profile_pic, ?) WHERE email = ?');
            $stmt->execute([$oauthId, $picture, $email]);
            $_SESSION['user_id'] = $user['id'];
        } else {
            // Create new user (using email prefix as default username)
            $username = explode('@', $email)[0] . rand(100, 999);
            $stmt = $db->prepare('INSERT INTO users (username, email, full_name, profile_pic, oauth_provider, oauth_id) VALUES (?, ?, ?, ?, "google", ?)');
            $stmt->execute([$username, $email, $name, $picture, $oauthId]);
            $_SESSION['user_id'] = $db->lastInsertId();
        }

        // Redirect to dashboard
        header('Location: /dashboard.php');
        exit;
    }
} else {
    // Generate OAuth login URL
    $authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online'
    ]);
    header('Location: ' . $authUrl);
    exit;
}
