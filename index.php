<?php
/**
 * FileFlow - Main Router
 * Handles URL routing for SEO-friendly URLs
 */

session_start();

// Error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Ensure user_documents directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
    file_put_contents(UPLOAD_DIR . 'index.html', '<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>403 Forbidden</h1></body></html>');
    file_put_contents(UPLOAD_DIR . '.htaccess', "Options -Indexes\nDeny from all\n<FilesMatch \"\\.(?i:php|phtml|php3|php4|php5|phps|phar|sh|bash|exe|bat|cmd)$\">\n    Deny from all\n</FilesMatch>");
}

// Parse request URI
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$path = rtrim($path, '/');

// Remove base path if needed
$basePath = '';
$path = substr($path, strlen($basePath));
$path = $path ?: '/';

// Route handling
switch (true) {
    // Home page
    case $path === '/' || $path === '':
        require __DIR__ . '/pages/home.php';
        break;
    
    // API routes
    case preg_match('#^/api/create-folder$#', $path):
        require __DIR__ . '/api/create-folder.php';
        break;
    
    case preg_match('#^/api/upload$#', $path):
        require __DIR__ . '/api/upload.php';
        break;
    
    case preg_match('#^/api/download$#', $path):
        require __DIR__ . '/api/download.php';
        break;
    
    case preg_match('#^/api/delete-file$#', $path):
        require __DIR__ . '/api/delete-file.php';
        break;

    case preg_match('#^/api/folder-info$#', $path):
        require __DIR__ . '/api/folder-info.php';
        break;
    
    // Static assets - let Apache handle
    case preg_match('#^/assets/#', $path):
        return false;
    
    // Folder page - catch-all for /{folder-slug}
    default:
        $slug = ltrim($path, '/');
        $slug = sanitizeSlug($slug);
        
        if (empty($slug)) {
            require __DIR__ . '/pages/error.php';
            break;
        }
        
        $folder = getFolderBySlug($slug);
        
        if ($folder) {
            require __DIR__ . '/pages/folder.php';
        } else {
            $errorType = 'not_found';
            require __DIR__ . '/pages/error.php';
        }
        break;
}
