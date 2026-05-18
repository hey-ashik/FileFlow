<?php
/**
 * FileFlow API - Upload File
 */

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'errors' => ['Method not allowed.']], 405);
}

// CSRF validation
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    jsonResponse(['success' => false, 'errors' => ['Invalid security token. Please refresh the page.']], 403);
}

// Get folder ID
$folderId = intval($_POST['folder_id'] ?? 0);
if ($folderId <= 0) {
    jsonResponse(['success' => false, 'errors' => ['Invalid folder.']], 400);
}

// Session is authenticated and CSRF verified. Close session to prevent blocking other requests.
session_write_close();

// Verify folder exists
$db = getDB();
$stmt = $db->prepare("SELECT id, slug, user_id FROM folders WHERE id = ? AND is_active = 1");
$stmt->execute([$folderId]);
$folder = $stmt->fetch();

if (!$folder) {
    jsonResponse(['success' => false, 'errors' => ['Folder not found.']], 404);
}

// Check if files were uploaded
if (empty($_FILES['files'])) {
    jsonResponse(['success' => false, 'errors' => ['No files selected.']], 400);
}

// Handle multiple files
$files = $_FILES['files'];
$results = [];
$successCount = 0;
$errorCount = 0;

// Normalize files array for single/multiple uploads
if (!is_array($files['name'])) {
    $files = [
        'name'     => [$files['name']],
        'type'     => [$files['type']],
        'tmp_name' => [$files['tmp_name']],
        'error'    => [$files['error']],
        'size'     => [$files['size']],
    ];
}

$fileCount = count($files['name']);

// Check file count limit
if ($fileCount > MAX_FILES_PER_UPLOAD) {
    jsonResponse(['success' => false, 'errors' => ['Maximum ' . MAX_FILES_PER_UPLOAD . ' files per upload.']], 400);
}

// Check storage space limit
if (!empty($folder['user_id'])) {
    $stats = getUserDashboardStats($folder['user_id']);
    
    $stmtUser = $db->prepare("SELECT space_limit_mb, file_upload_limit_mb FROM users WHERE id = ?");
    $stmtUser->execute([$folder['user_id']]);
    $uRow = $stmtUser->fetch();
    $limitMb = $uRow ? (int)$uRow['space_limit_mb'] : 100;
    $fileUploadLimitMb = $uRow && isset($uRow['file_upload_limit_mb']) ? (int)$uRow['file_upload_limit_mb'] : 50;
    
    $totalUploadSize = 0;
    for ($i = 0; $i < $fileCount; $i++) {
        $totalUploadSize += $files['size'][$i];
    }
    
    if (($stats['total_size'] + $totalUploadSize) > ($limitMb * 1024 * 1024)) {
        jsonResponse(['success' => false, 'errors' => ["Storage limit exceeded. Maximum limit is {$limitMb}MB. Please delete files to free up space."]], 400);
    }
}

for ($i = 0; $i < $fileCount; $i++) {
    $file = [
        'name'     => $files['name'][$i],
        'type'     => $files['type'][$i],
        'tmp_name' => $files['tmp_name'][$i],
        'error'    => $files['error'][$i],
        'size'     => $files['size'][$i],
    ];
    
    $maxSizeBytes = isset($fileUploadLimitMb) ? ($fileUploadLimitMb * 1024 * 1024) : MAX_FILE_SIZE;
    $result = uploadFile($file, $folder['id'], $folder['slug'], $maxSizeBytes);
    
    if ($result['success']) {
        $successCount++;
        $results[] = [
            'success' => true,
            'file' => $result['file'],
            'name' => $file['name']
        ];
    } else {
        $errorCount++;
        $results[] = [
            'success' => false,
            'name' => $file['name'],
            'errors' => $result['errors']
        ];
    }
}

// Generate new CSRF token
$newToken = generateCSRFToken();

jsonResponse([
    'success' => $successCount > 0,
    'uploaded' => $successCount,
    'failed' => $errorCount,
    'results' => $results,
    'csrf_token' => $newToken
]);
