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
if ($userId <= 0) {
    jsonResponse(['success' => false, 'errors' => ['Invalid input.']], 400);
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, slug FROM folders WHERE user_id = ?");
    $stmt->execute([$userId]);
    $folders = $stmt->fetchAll();
    
    foreach ($folders as $folder) {
        $dirPath = UPLOAD_DIR . $folder['slug'];
        if (is_dir($dirPath)) {
            $files = array_diff(scandir($dirPath), array('.','..'));
            foreach ($files as $file) {
                @unlink("$dirPath/$file");
            }
            @rmdir($dirPath);
        }
        $db->prepare("DELETE FROM folders WHERE id = ?")->execute([$folder['id']]);
    }
    
    jsonResponse(['success' => true]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'errors' => ['Database error.']], 500);
}
