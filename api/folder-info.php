<?php
/**
 * FileFlow API - Get Folder Info
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$slug = sanitizeSlug($_GET['slug'] ?? '');

if (empty($slug)) {
    jsonResponse(['success' => false, 'errors' => ['Invalid folder.']], 400);
}

$folder = getFolderBySlug($slug);

if (!$folder) {
    jsonResponse(['success' => false, 'errors' => ['Folder not found.']], 404);
}

$files = getFilesByFolderId($folder['id']);

$fileList = array_map(function($file) {
    return [
        'id' => $file['id'],
        'name' => $file['original_name'],
        'size' => formatFileSize($file['file_size']),
        'extension' => $file['file_extension'],
        'category' => getFileCategory($file['file_extension']),
        'icon' => getFileIcon($file['file_extension']),
        'uploaded_at' => timeAgo($file['uploaded_at']),
        'downloads' => $file['download_count']
    ];
}, $files);

jsonResponse([
    'success' => true,
    'folder' => [
        'id' => $folder['id'],
        'name' => $folder['display_name'],
        'slug' => $folder['slug'],
        'total_files' => $folder['total_files'],
        'total_size' => formatFileSize($folder['total_size']),
        'created_at' => timeAgo($folder['created_at'])
    ],
    'files' => $fileList
]);
