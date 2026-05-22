<?php
/**
 * FileFlow - Admin Delete Profile Card API
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'errors' => ['Unauthorized']]);
    exit;
}

// Check if user is admin
$currentUser = getCurrentUser();
if (!$currentUser || !$currentUser['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'errors' => ['Forbidden']]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method Not Allowed']]);
    exit;
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'errors' => ['Invalid CSRF token']]);
    exit;
}

$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => ['Valid User ID is required']]);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE users SET profile_slug = NULL, phone = NULL, work_experience = NULL, social_links = NULL, avatar_path = NULL, cover_path = NULL WHERE id = ?");
    $result = $stmt->execute([$user_id]);
    
    if ($result) {
        clearUserCache($user_id);
        echo json_encode(['success' => true, 'message' => 'Profile card deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'errors' => ['Failed to update user record']]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    // Don't leak raw errors
    echo json_encode(['success' => false, 'errors' => ['Database error occurred']]);
}
