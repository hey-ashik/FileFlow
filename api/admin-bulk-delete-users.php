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

$userIDs = $_POST['user_ids'] ?? [];
$allUsers = intval($_POST['all_users'] ?? 0) === 1;

if (empty($userIDs) && !$allUsers) {
    jsonResponse(['success' => false, 'errors' => ['No users selected.']], 400);
}

try {
    $db = getDB();
    $currentUser = getCurrentUser();
    $currentAdminId = intval($currentUser['id'] ?? 0);
    
    if ($allUsers) {
        // Find all users except the current admin
        $stmt = $db->prepare("SELECT id FROM users WHERE id != ?");
        $stmt->execute([$currentAdminId]);
        $usersToDelete = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        if (!is_array($userIDs)) {
            $userIDs = explode(',', $userIDs);
        }
        $userIDs = array_filter(array_map('intval', $userIDs));
        // Filter out current admin to prevent self-deletion
        $usersToDelete = array_diff($userIDs, [$currentAdminId]);
    }

    if (empty($usersToDelete)) {
        jsonResponse(['success' => false, 'errors' => ['No valid users to delete.']], 400);
    }

    foreach ($usersToDelete as $uId) {
        // 1. Delete all folders & files belonging to this user
        $stmt = $db->prepare("SELECT id, slug FROM folders WHERE user_id = ?");
        $stmt->execute([$uId]);
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

        // 2. Fetch avatar, cover, and CV paths to delete from disk
        $stmt = $db->prepare("SELECT avatar_path, cover_path, cv_path FROM users WHERE id = ?");
        $stmt->execute([$uId]);
        $uData = $stmt->fetch();
        if ($uData) {
            // Delete avatar
            if ($uData['avatar_path'] && file_exists(__DIR__ . '/../' . $uData['avatar_path'])) {
                @unlink(__DIR__ . '/../' . $uData['avatar_path']);
            }
            // Delete cover
            if ($uData['cover_path'] && file_exists(__DIR__ . '/../' . $uData['cover_path'])) {
                @unlink(__DIR__ . '/../' . $uData['cover_path']);
            }
            // Delete CV
            if ($uData['cv_path'] && file_exists(__DIR__ . '/../' . $uData['cv_path'])) {
                @unlink(__DIR__ . '/../' . $uData['cv_path']);
            }
        }

        // 3. Delete the user row. Cascade handles comments, likes, messages, etc.
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$uId]);
        clearUserCache($uId);
    }

    jsonResponse(['success' => true]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'errors' => ['Database error.']], 500);
}
