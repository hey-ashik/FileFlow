<?php
/**
 * FileFlow API - Update Folder Security
 */

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

// CSRF validation
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token. Please refresh the page.'], 403);
}

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$user = getCurrentUser();
$folderId = (int)($_POST['folder_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$folderId) {
    jsonResponse(['success' => false, 'message' => 'Invalid folder.'], 400);
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM folders WHERE id = ? AND user_id = ?");
$stmt->execute([$folderId, $user['id']]);
$folder = $stmt->fetch();

if (!$folder) {
    jsonResponse(['success' => false, 'message' => 'Folder not found or unauthorized.'], 404);
}

if ($action === 'protect') {
    $newPassword = $_POST['new_password'] ?? '';
    if (empty($newPassword)) {
        jsonResponse(['success' => false, 'message' => 'Password cannot be empty.'], 400);
    }
    if (strlen($newPassword) < 4) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least 4 characters long.'], 400);
    }
    
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE folders SET password_hash = ? WHERE id = ?");
    if ($stmt->execute([$hash, $folderId])) {
        jsonResponse(['success' => true, 'message' => 'Folder is now password protected.', 'csrf_token' => generateCSRFToken()]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to update folder.'], 500);
    }
} elseif ($action === 'unprotect') {
    $currentPassword = $_POST['current_password'] ?? '';
    
    if (empty($folder['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Folder is not password protected.'], 400);
    }
    
    if (!password_verify($currentPassword, $folder['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Incorrect password.'], 403);
    }
    
    $stmt = $db->prepare("UPDATE folders SET password_hash = NULL WHERE id = ?");
    if ($stmt->execute([$folderId])) {
        // Also clear session auth so if they protect it again they need to enter pass
        if (isset($_SESSION['folder_auth_' . $folderId])) {
            unset($_SESSION['folder_auth_' . $folderId]);
        }
        jsonResponse(['success' => true, 'message' => 'Password removed successfully.', 'csrf_token' => generateCSRFToken()]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to remove password.'], 500);
    }
} else {
    jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
}
