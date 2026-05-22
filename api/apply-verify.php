<?php
ob_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Shutdown handler to capture fatal errors and return clean JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo json_encode([
            'success' => false,
            'message' => 'System Error: ' . $error['message'] . ' in ' . basename($error['file']) . ' on line ' . $error['line']
        ]);
    }
});

try {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        exit;
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
        exit;
    }

    $user = getCurrentUser();
    $userId = $user['id'];
    $db = getDB();

    // 1. Validate requirements criteria (retrieve fresh from DB to avoid stale cache)
    $stmtDbUser = $db->prepare("SELECT profile_visits, is_verified FROM users WHERE id = ?");
    $stmtDbUser->execute([$userId]);
    $dbUser = $stmtDbUser->fetch();
    $profileVisits = (int)($dbUser['profile_visits'] ?? 0);
    $isUserVerified = isset($dbUser['is_verified']) && $dbUser['is_verified'] == 1;

    if ($isUserVerified) {
        echo json_encode(['success' => false, 'message' => 'You are already verified.']);
        exit;
    }

    $stmtViews = $db->prepare("SELECT COALESCE(SUM(views), 0) FROM thoughts WHERE user_id = ?");
    $stmtViews->execute([$userId]);
    $thoughtViews = (int)$stmtViews->fetchColumn();

    if ($profileVisits < 500 || $thoughtViews < 1000) {
        echo json_encode(['success' => false, 'message' => 'You do not meet the minimum criteria for verification (500 profile card views and 1000 thought post views).']);
        exit;
    }

    // 2. Check for existing pending request
    $stmtCheck = $db->prepare("SELECT status FROM verification_requests WHERE user_id = ? AND status = 'pending' LIMIT 1");
    $stmtCheck->execute([$userId]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending verification request.']);
        exit;
    }

    // 3. Process inputs
    $realName = trim($_POST['real_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($realName) || empty($phone) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    // 4. Handle NID upload
    if (!isset($_FILES['nid_file']) || $_FILES['nid_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'NID document upload is required.']);
        exit;
    }

    $file = $_FILES['nid_file'];
    $fileSize = $file['size'];
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];

    // Validate file size (max 5MB)
    if ($fileSize > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Document size must be less than 5MB.']);
        exit;
    }

    // Validate file extension
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Unsupported document format. Please upload JPG, PNG, WEBP, or PDF.']);
        exit;
    }

    // Ensure target directory exists
    $uploadDir = __DIR__ . '/../uploads/nid/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
    if (is_dir($uploadDir)) {
        @file_put_contents($uploadDir . 'index.html', '<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>403 Forbidden</h1></body></html>');
    }

    // Generate secure file name (uniqid is extremely fast and cannot throw random_bytes exception)
    $newFileName = 'nid_' . uniqid() . '_' . mt_rand(100000, 999999) . '.' . $ext;
    $targetPath = $uploadDir . $newFileName;
    $relativeUrlPath = '/uploads/nid/' . $newFileName;

    if (!@move_uploaded_file($fileTmp, $targetPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded document. Please check folder permissions.']);
        exit;
    }

    // 5. Insert request into verification_requests table
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `verification_requests` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `real_name` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(50) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `nid_path` VARCHAR(255) NOT NULL,
            `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    } catch (Exception $e) {}

    $stmt = $db->prepare("INSERT INTO verification_requests (user_id, real_name, phone, email, nid_path, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$userId, $realName, $phone, $email, $relativeUrlPath]);
    
    // Invalidate user cache to ensure latest state is reflected
    clearUserCache($userId);
    
    echo json_encode(['success' => true, 'message' => 'Verification request submitted successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
}

ob_end_flush();
