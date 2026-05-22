<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

if (!isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized.'], 403);
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    jsonResponse(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
}

$requestId = intval($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($requestId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid request ID.'], 400);
}

try {
    $db = getDB();
    
    // Check if verification request exists
    $stmt = $db->prepare("SELECT * FROM verification_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    
    if (!$request) {
        jsonResponse(['success' => false, 'message' => 'Verification request not found.'], 404);
    }
    
    $userId = $request['user_id'];
    
    if ($action === 'update_verify_request') {
        $realName = trim($_POST['real_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($realName) || empty($phone) || empty($email)) {
            jsonResponse(['success' => false, 'message' => 'All fields are required.'], 400);
        }
        
        $up = $db->prepare("UPDATE verification_requests SET real_name = ?, phone = ?, email = ? WHERE id = ?");
        $up->execute([$realName, $phone, $email, $requestId]);
        
        jsonResponse(['success' => true, 'message' => 'Request details updated successfully.']);
        
    } elseif ($action === 'approve') {
        // Approve verification: set request status and user verified flag
        $db->beginTransaction();
        
        $upRequest = $db->prepare("UPDATE verification_requests SET status = 'approved' WHERE id = ?");
        $upRequest->execute([$requestId]);
        
        $upUser = $db->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $upUser->execute([$userId]);
        
        $db->commit();
        clearUserCache($userId);
        
        jsonResponse(['success' => true, 'message' => 'User verified successfully.']);
        
    } elseif ($action === 'reject') {
        // Reject verification: set request status to rejected and user is_verified to 0
        $db->beginTransaction();
        
        $upRequest = $db->prepare("UPDATE verification_requests SET status = 'rejected' WHERE id = ?");
        $upRequest->execute([$requestId]);
        
        $upUser = $db->prepare("UPDATE users SET is_verified = 0 WHERE id = ?");
        $upUser->execute([$userId]);
        
        $db->commit();
        clearUserCache($userId);
        
        jsonResponse(['success' => true, 'message' => 'Verification request rejected.']);
        
    } else {
        jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
    }
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
