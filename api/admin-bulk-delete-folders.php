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

$folderIds = $_POST['folder_ids'] ?? [];
$allUnassigned = intval($_POST['all_unassigned'] ?? 0) === 1;

if (empty($folderIds) && !$allUnassigned) {
    jsonResponse(['success' => false, 'errors' => ['No folders selected.']], 400);
}

try {
    $db = getDB();
    
    if ($allUnassigned) {
        // Find all unassigned folders (where user_id is NULL)
        $stmt = $db->query("SELECT id, slug FROM folders WHERE user_id IS NULL");
        $folders = $stmt->fetchAll();
    } else {
        // Sanitize folders array
        if (!is_array($folderIds)) {
            $folderIds = explode(',', $folderIds);
        }
        $folderIds = array_filter(array_map('intval', $folderIds));
        if (empty($folderIds)) {
            jsonResponse(['success' => false, 'errors' => ['Invalid folders selected.']], 400);
        }
        $inQuery = implode(',', array_fill(0, count($folderIds), '?'));
        $stmt = $db->prepare("SELECT id, slug, user_id FROM folders WHERE id IN ($inQuery)");
        $stmt->execute($folderIds);
        $folders = $stmt->fetchAll();
    }

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
        clearFolderCache($folder['slug'], $folder['id'], $folder['user_id'] ?? null);
    }
    
    jsonResponse(['success' => true]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'errors' => ['Database error.']], 500);
}
