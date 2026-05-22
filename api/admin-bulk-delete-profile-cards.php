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
$allProfiles = intval($_POST['all_profiles'] ?? 0) === 1;

if (empty($userIDs) && !$allProfiles) {
    jsonResponse(['success' => false, 'errors' => ['No profile cards selected.']], 400);
}

try {
    $db = getDB();
    
    if ($allProfiles) {
        $db->query("UPDATE users SET profile_slug = NULL, phone = NULL, work_experience = NULL, social_links = NULL, avatar_path = NULL, cover_path = NULL");
        RedisCache::getInstance()->flush();
    } else {
        if (!is_array($userIDs)) {
            $userIDs = explode(',', $userIDs);
        }
        $userIDs = array_filter(array_map('intval', $userIDs));
        if (empty($userIDs)) {
            jsonResponse(['success' => false, 'errors' => ['Invalid users selected.']], 400);
        }
        $inQuery = implode(',', array_fill(0, count($userIDs), '?'));
        $stmt = $db->prepare("UPDATE users SET profile_slug = NULL, phone = NULL, work_experience = NULL, social_links = NULL, avatar_path = NULL, cover_path = NULL WHERE id IN ($inQuery)");
        $stmt->execute($userIDs);
        
        foreach ($userIDs as $id) {
            clearUserCache($id);
        }
    }

    jsonResponse(['success' => true]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'errors' => ['Database error.']], 500);
}
