<?php
/**
 * user/upload-avatar.php — Handle avatar file upload
 * Previously the JS called this but it had no server-side implementation.
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/csrf.php';

checkAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed', 405);
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['avatar']['error'] ?? -1;
    $errMsg  = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
    ][$errCode] ?? 'Upload error. Please try again.';
    jsonResponse(false, null, $errMsg, 400);
}

$file    = $_FILES['avatar'];
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// Validate MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
if (!in_array($mime, $allowed)) {
    jsonResponse(false, null, 'Only JPEG, PNG, GIF, or WEBP images are allowed.', 400);
}

// Validate size (max 2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    jsonResponse(false, null, 'File too large. Maximum size is 2MB.', 400);
}

$userId    = getUserId();
$ext       = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
][$mime];
$filename  = 'avatar_' . $userId . '_' . time() . '.' . $ext;
$uploadDir = __DIR__ . '/../../../../uploads/avatars/';
$uploadPath = $uploadDir . $filename;

// Create dir if missing
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    jsonResponse(false, null, 'Failed to save the file. Check server permissions.', 500);
}

// Delete old avatar file if it was a local upload
try {
    $db   = Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT profile_pic FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $old = $stmt->fetchColumn();
    if ($old && str_starts_with($old, 'uploads/avatars/')) {
        $oldFile = __DIR__ . '/../../../../' . $old;
        if (file_exists($oldFile)) @unlink($oldFile);
    }

    $avatarUrl = 'uploads/avatars/' . $filename;
    $stmt = $db->prepare('UPDATE users SET profile_pic = ? WHERE id = ?');
    $stmt->execute([$avatarUrl, $userId]);

    jsonResponse(true, ['avatar_url' => $avatarUrl]);
} catch (Exception $e) {
    error_log('upload-avatar: ' . $e->getMessage());
    jsonResponse(false, null, 'Database error updating profile picture.', 500);
}
