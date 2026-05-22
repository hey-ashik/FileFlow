<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'errors' => ['Method not allowed.']], 405);
}

if (!isAdmin()) {
    jsonResponse(['success' => false, 'errors' => ['Unauthorized.']], 403);
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    jsonResponse(['success' => false, 'errors' => ['Invalid token.']], 403);
}

$userId = intval($_POST['user_id'] ?? 0);
$folderLimit = intval($_POST['folder_limit'] ?? 3);
$limit = intval($_POST['space_limit_mb'] ?? 0);
$uploadLimit = intval($_POST['file_upload_limit_mb'] ?? 50);

if ($userId <= 0 || $limit <= 0 || $uploadLimit <= 0 || $folderLimit <= 0) {
    jsonResponse(['success' => false, 'errors' => ['Invalid input.']], 400);
}

try {
    $db = getDB();
    
    // Add columns if not exists dynamically before updating
    try {
        $db->exec("ALTER TABLE users ADD COLUMN file_upload_limit_mb INT NOT NULL DEFAULT 50");
    } catch (PDOException $e) {}
    try {
        $db->exec("ALTER TABLE users ADD COLUMN folder_limit INT NOT NULL DEFAULT 3");
    } catch (PDOException $e) {}

    $isVerified = isset($_POST['is_verified']) && $_POST['is_verified'] === '1' ? 1 : 0;

    $db->prepare("UPDATE users SET space_limit_mb = ?, file_upload_limit_mb = ?, folder_limit = ?, is_verified = ? WHERE id = ?")->execute([$limit, $uploadLimit, $folderLimit, $isVerified, $userId]);
    clearUserCache($userId);
    jsonResponse(['success' => true]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'errors' => ['Database error.']], 500);
}
