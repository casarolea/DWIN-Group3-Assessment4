<?php
require_once __DIR__ . '/../includes/auth.php';
cookbook_require_login();
$pdo = cookbook_db();

$statement = $pdo->prepare('SELECT profile_photo FROM users WHERE user_id = ?');
$statement->execute([cookbook_current_user()['id']]);
$relativePath = $statement->fetchColumn();
$profileDirectory = realpath(__DIR__ . '/../images/profiles');
$filePath = $relativePath ? realpath(__DIR__ . '/../images/' . $relativePath) : false;

if (
    !$profileDirectory
    || !$filePath
    || !str_starts_with($relativePath, 'profiles/')
    || !str_starts_with($filePath, $profileDirectory . DIRECTORY_SEPARATOR)
) {
    http_response_code(404);
    exit;
}

$mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($filePath);
if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-store');
readfile($filePath);
