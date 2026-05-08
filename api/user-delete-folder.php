<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'errors' => ['Method not allowed.']], 405);
}

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'errors' => ['Unauthorized.']], 403);
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    jsonResponse(['success' => false, 'errors' => ['Invalid token.']], 403);
}

$folderId = intval($_POST['folder_id'] ?? 0);
if ($folderId <= 0) {
    jsonResponse(['success' => false, 'errors' => ['Invalid input.']], 400);
}

$user = getCurrentUser();

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, slug, user_id FROM folders WHERE id = ?");
    $stmt->execute([$folderId]);
    $folder = $stmt->fetch();
    
    if (!$folder || $folder['user_id'] !== $user['id']) {
        jsonResponse(['success' => false, 'errors' => ['Folder not found or access denied.']], 404);
    }
    
    if ($folder) {
        $dirPath = UPLOAD_DIR . $folder['slug'];
        if (is_dir($dirPath)) {
            $files = array_diff(scandir($dirPath), array('.','..'));
            foreach ($files as $file) {
                @unlink("$dirPath/$file");
            }
            @rmdir($dirPath);
        }
        $db->prepare("DELETE FROM folders WHERE id = ?")->execute([$folderId]);
    }
    
    jsonResponse(['success' => true]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'errors' => ['Database error.']], 500);
}
