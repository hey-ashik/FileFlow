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

// Helper function to delete directory recursively
if (!function_exists('deleteDirectory')) {
    function deleteDirectory($dir) {
        if (!is_dir($dir)) return false;
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? deleteDirectory("$dir/$file") : @unlink("$dir/$file");
        }
        return @rmdir($dir);
    }
}

// Clean up old chunk directories (older than 24 hours) to prevent disk space leaks
$chunksParentDir = UPLOAD_DIR . 'chunks/';
if (is_dir($chunksParentDir)) {
    $now = time();
    $dirFiles = @scandir($chunksParentDir);
    if (is_array($dirFiles)) {
        foreach ($dirFiles as $f) {
            if ($f === '.' || $f === '..') continue;
            $dirPath = $chunksParentDir . $f;
            if (is_dir($dirPath)) {
                $mtime = filemtime($dirPath);
                if ($now - $mtime > 86400) { // 24 hours
                    deleteDirectory($dirPath);
                }
            }
        }
    }
}

// Calculate max file size limit & space limit for this folder
$maxSizeBytes = MAX_FILE_SIZE;
$spaceLimitMb = 100; // default space limit if user_id is empty
if (!empty($folder['user_id'])) {
    $stmtUser = $db->prepare("SELECT space_limit_mb, file_upload_limit_mb FROM users WHERE id = ?");
    $stmtUser->execute([$folder['user_id']]);
    $uRow = $stmtUser->fetch();
    if ($uRow) {
        if (isset($uRow['file_upload_limit_mb'])) {
            $maxSizeBytes = (int)$uRow['file_upload_limit_mb'] * 1024 * 1024;
        }
        if (isset($uRow['space_limit_mb'])) {
            $spaceLimitMb = (int)$uRow['space_limit_mb'];
        }
    }
}

// Check if it is a chunked upload
$chunkIndex = isset($_POST['chunk_index']) ? intval($_POST['chunk_index']) : null;
$totalChunks = isset($_POST['total_chunks']) ? intval($_POST['total_chunks']) : null;
$fileUuid = isset($_POST['file_uuid']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['file_uuid']) : null;
$fileName = $_POST['file_name'] ?? null;
$fileSize = isset($_POST['file_size']) ? intval($_POST['file_size']) : null;

if ($chunkIndex !== null && $totalChunks !== null && !empty($fileUuid) && !empty($fileName)) {
    // 1. Validate file extension early before receiving chunks
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!array_key_exists($ext, ALLOWED_EXTENSIONS)) {
        jsonResponse(['success' => false, 'errors' => ['File type ".' . $ext . '" is not allowed.']], 400);
    }

    // 2. Validate file size early before receiving chunks
    if ($fileSize !== null && $fileSize > $maxSizeBytes) {
        jsonResponse(['success' => false, 'errors' => ['File size exceeds the maximum limit of ' . formatFileSize($maxSizeBytes) . '.']], 400);
    }
    
    // 3. Validate storage limit early before receiving chunks (for logged-in user folders)
    if (!empty($folder['user_id']) && $fileSize !== null) {
        $stats = getUserDashboardStats($folder['user_id']);
        if (($stats['total_size'] + $fileSize) > ($spaceLimitMb * 1024 * 1024)) {
            jsonResponse(['success' => false, 'errors' => ["Storage limit exceeded. Maximum limit is {$spaceLimitMb}MB. Please delete files to free up space."]], 400);
        }
    }

    if (empty($_FILES['files'])) {
        jsonResponse(['success' => false, 'errors' => ['No chunk files uploaded.']], 400);
    }
    
    $chunkFile = $_FILES['files'];
    if (is_array($chunkFile['name'])) {
        $chunk = [
            'name'     => $chunkFile['name'][0],
            'type'     => $chunkFile['type'][0],
            'tmp_name' => $chunkFile['tmp_name'][0],
            'error'    => $chunkFile['error'][0],
            'size'     => $chunkFile['size'][0],
        ];
    } else {
        $chunk = $chunkFile;
    }
    
    if ($chunk['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['success' => false, 'errors' => ['Chunk upload failed with error code ' . $chunk['error']]], 400);
    }
    
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    $chunksDir = UPLOAD_DIR . 'chunks/' . $fileUuid;
    if (!is_dir($chunksDir)) {
        mkdir($chunksDir, 0755, true);
    }
    
    $chunkTargetPath = $chunksDir . '/' . $chunkIndex;
    if (!move_uploaded_file($chunk['tmp_name'], $chunkTargetPath)) {
        jsonResponse(['success' => false, 'errors' => ['Failed to save chunk.']], 500);
    }
    
    $allChunksUploaded = true;
    for ($j = 0; $j < $totalChunks; $j++) {
        if (!file_exists($chunksDir . '/' . $j)) {
            $allChunksUploaded = false;
            break;
        }
    }
    
    if ($allChunksUploaded) {
        $mergedFilePath = $chunksDir . '/merged';
        $out = fopen($mergedFilePath, 'wb');
        if (!$out) {
            jsonResponse(['success' => false, 'errors' => ['Failed to open merged file.']], 500);
        }
        for ($j = 0; $j < $totalChunks; $j++) {
            $in = fopen($chunksDir . '/' . $j, 'rb');
            if ($in) {
                while ($buff = fread($in, 16384)) {
                    fwrite($out, $buff);
                }
                fclose($in);
            }
        }
        fclose($out);
        
        $mime = 'application/octet-stream';
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($mergedFilePath) ?: 'application/octet-stream';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($mergedFilePath) ?: 'application/octet-stream';
        }
        
        $fileData = [
            'name' => $fileName,
            'type' => $mime,
            'tmp_name' => $mergedFilePath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($mergedFilePath),
            'is_merged_chunk' => true
        ];
        
        $result = uploadFile($fileData, $folder['id'], $folder['slug'], $maxSizeBytes);
        
        for ($j = 0; $j < $totalChunks; $j++) {
            @unlink($chunksDir . '/' . $j);
        }
        @unlink($mergedFilePath);
        @rmdir($chunksDir);
        
        $newToken = generateCSRFToken();
        
        if ($result['success']) {
            jsonResponse([
                'success' => true,
                'uploaded' => 1,
                'failed' => 0,
                'results' => [[
                    'success' => true,
                    'file' => $result['file'],
                    'name' => $fileName
                ]],
                'csrf_token' => $newToken
            ]);
        } else {
            jsonResponse([
                'success' => false,
                'uploaded' => 0,
                'failed' => 1,
                'results' => [[
                    'success' => false,
                    'name' => $fileName,
                    'errors' => $result['errors']
                ]],
                'csrf_token' => $newToken
            ]);
        }
    } else {
        jsonResponse([
            'success' => true,
            'chunk_uploaded' => true,
            'chunk_index' => $chunkIndex
        ]);
    }
    exit;
}

// Check if files were uploaded
if (empty($_FILES['files'])) {
    jsonResponse(['success' => false, 'errors' => ['No files selected.']], 400);
}

// Handle multiple files (Standard fallback upload path)
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
    
    $totalUploadSize = 0;
    for ($i = 0; $i < $fileCount; $i++) {
        $totalUploadSize += $files['size'][$i];
    }
    
    if (($stats['total_size'] + $totalUploadSize) > ($spaceLimitMb * 1024 * 1024)) {
        jsonResponse(['success' => false, 'errors' => ["Storage limit exceeded. Maximum limit is {$spaceLimitMb}MB. Please delete files to free up space."]], 400);
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
