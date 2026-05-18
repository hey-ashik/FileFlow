<?php
/**
 * FileFlow API - Download File
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$fileId = intval($_GET['id'] ?? 0);

if ($fileId <= 0) {
    http_response_code(400);
    echo 'Invalid file ID.';
    exit;
}

$file = getFileById($fileId);

if (!$file) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

$filePath = UPLOAD_DIR . $file['folder_slug'] . '/' . $file['stored_name'];

if (!file_exists($filePath)) {
    http_response_code(404);
    echo 'File not found on server.';
    exit;
}

// Increment download counter
try {
    $db = getDB();
    $db->prepare("UPDATE files SET download_count = download_count + 1 WHERE id = ?")->execute([$fileId]);
} catch (Exception $e) {
    // Non-critical, continue with download
}

// Secure download headers
$safeFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file['original_name']);

$isPreview = isset($_GET['preview']) && $_GET['preview'] == 1;
$disposition = $isPreview ? 'inline' : 'attachment';

if ($isPreview) {
    $ext = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'txt' => 'text/plain'
    ];
    $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $contentType);
} else {
    header('Content-Type: application/octet-stream');
}

header('Content-Disposition: ' . $disposition . '; filename="' . $safeFilename . '"');
header('Content-Length: ' . filesize($filePath));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

// Output file
if (session_status() !== PHP_SESSION_NONE) {
    session_write_close();
}
readfile($filePath);
exit;
