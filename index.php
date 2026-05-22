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
require_once __DIR__ . '/includes/auth.php';

// Setup schema and admin user if needed
setupAdminAndSchema();

// Ensure user_documents directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
    file_put_contents(UPLOAD_DIR . 'index.html', '<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>403 Forbidden</h1></body></html>');
    file_put_contents(UPLOAD_DIR . '.htaccess', "Options -Indexes\nDeny from all\n<FilesMatch \"\\.(?i:php|phtml|php3|php4|php5|phps|phar|sh|bash|exe|bat|cmd)$\">\n    Deny from all\n</FilesMatch>");
}

// Parse request URI
$requestUri = $_SERVER['REQUEST_URI'];
$urlParts = parse_url($requestUri);
$path = $urlParts['path'] ?? '/';

// Force remove trailing slash for SEO and consistency (except for root)
if ($path !== '/' && substr($path, -1) === '/') {
    $newPath = rtrim($path, '/');
    $queryString = isset($urlParts['query']) ? '?' . $urlParts['query'] : '';
    header("Location: " . $newPath . $queryString, true, 301);
    exit;
}

$path = rtrim($path, '/');
$basePath = '';
$path = substr($path, strlen($basePath));
$path = $path ?: '/';

// Maintenance mode check
if (file_exists(__DIR__ . '/config/maintenance.flag') && !isAdmin()) {
    if (strpos($path, '/api/') === 0 && $path !== '/api/auth/login') {
        jsonResponse(['success' => false, 'errors' => ['System is currently under maintenance.']], 503);
    } else if ($path !== '/login' && $path !== '/api/auth/login') {
        require __DIR__ . '/pages/maintenance.php';
        exit;
    }
}

// Route handling
switch (true) {
    case $path === '/' || $path === '':
        require __DIR__ . '/pages/home.php';
        break;

    // Auth pages
    case $path === '/login':
        if (isLoggedIn()) {
            header('Location: /dashboard');
            exit;
        }
        require __DIR__ . '/pages/login.php';
        break;
    case $path === '/register':
        if (isLoggedIn()) {
            header('Location: /dashboard');
            exit;
        }
        require __DIR__ . '/pages/register.php';
        break;
    case $path === '/forgot-password':
        if (isLoggedIn()) {
            header('Location: /dashboard');
            exit;
        }
        require __DIR__ . '/pages/forgot-password.php';
        break;
    case $path === '/reset-password':
        if (isLoggedIn()) {
            header('Location: /dashboard');
            exit;
        }
        require __DIR__ . '/pages/reset-password.php';
        break;
    case $path === '/dashboard':
        if (!isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        require __DIR__ . '/pages/dashboard.php';
        break;
    case $path === '/profile':
        if (!isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        require __DIR__ . '/pages/profile.php';
        break;
    case $path === '/admin':
        if (!isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        if (!isAdmin()) {
            header('Location: /dashboard');
            exit;
        }
        require __DIR__ . '/pages/admin.php';
        break;
    case $path === '/search':
        require __DIR__ . '/pages/search.php';
        break;
    case $path === '/messages':
        if (!isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        require __DIR__ . '/pages/messages.php';
        break;
    case $path === '/thoughts':
        require __DIR__ . '/pages/thoughts.php';
        break;
    case $path === '/logout':
        logoutUser();
        header('Location: /');
        exit;

    // Auth API
    case preg_match('#^/api/auth/login$#', $path):
        require __DIR__ . '/api/auth-login.php';
        break;
    case preg_match('#^/api/auth/register$#', $path):
        require __DIR__ . '/api/auth-register.php';
        break;
    case preg_match('#^/api/auth/forgot-password$#', $path):
        require __DIR__ . '/api/auth-forgot.php';
        break;
    case preg_match('#^/api/auth/reset-password$#', $path):
        require __DIR__ . '/api/auth-reset.php';
        break;
    case preg_match('#^/api/dashboard-stats$#', $path):
        require __DIR__ . '/api/dashboard-stats.php';
        break;
    case preg_match('#^/api/profile$#', $path):
        require __DIR__ . '/api/profile.php';
        break;
    case preg_match('#^/api/network$#', $path):
        require __DIR__ . '/api/network.php';
        break;
    case preg_match('#^/api/thoughts$#', $path):
        require __DIR__ . '/api/thoughts.php';
        break;

    // Existing API routes
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
    case preg_match('#^/api/update-folder-security$#', $path):
        require __DIR__ . '/api/update-folder-security.php';
        break;

    // Admin API routes
    case preg_match('#^/api/admin/update-user$#', $path):
        require __DIR__ . '/api/admin-update-user.php';
        break;
    case preg_match('#^/api/admin/delete-folder$#', $path):
        require __DIR__ . '/api/admin-delete-folder.php';
        break;
    case preg_match('#^/api/admin/bulk-delete-folders$#', $path):
        require __DIR__ . '/api/admin-bulk-delete-folders.php';
        break;
    case preg_match('#^/api/admin/bulk-delete-users$#', $path):
        require __DIR__ . '/api/admin-bulk-delete-users.php';
        break;
    case preg_match('#^/api/admin/bulk-delete-profile-cards$#', $path):
        require __DIR__ . '/api/admin-bulk-delete-profile-cards.php';
        break;
    case preg_match('#^/api/admin/delete-all-folders$#', $path):
        require __DIR__ . '/api/admin-delete-all.php';
        break;
    case preg_match('#^/api/admin/delete-profile-card$#', $path):
        require __DIR__ . '/api/admin-delete-profile-card.php';
        break;
    case preg_match('#^/api/admin/toggle-maintenance$#', $path):
        require __DIR__ . '/api/admin-toggle-maintenance.php';
        break;
        
    case preg_match('#^/api/user/delete-folder$#', $path):
        require __DIR__ . '/api/user-delete-folder.php';
        break;

    // Static assets
    case preg_match('#^/assets/#', $path):
        return false;

    // Public Profile route
    case preg_match('#^/u/([a-zA-Z0-9_-]+)$#', $path, $matches):
        $profileSlug = strtolower($matches[1]);
        require __DIR__ . '/pages/profile-card.php';
        break;

    // Single post route
    case preg_match('#^/p/([0-9]+)$#', $path, $matches):
        $thoughtId = (int)$matches[1];
        require __DIR__ . '/pages/single-thought.php';
        break;

    // Folder page - catch-all
    default:
        $slug = ltrim($path, '/');
        $slug = sanitizeSlug($slug);
        if (empty($slug)) {
            require __DIR__ . '/pages/error.php';
            break;
        }
        $folder = getFolderBySlug($slug);
        if ($folder) {
            incrementFolderVisits($folder['id']);
            $folder['visits'] = ($folder['visits'] ?? 0) + 1; // update local variable so it displays the incremented value
            require __DIR__ . '/pages/folder.php';
        } else {
            $errorType = 'not_found';
            require __DIR__ . '/pages/error.php';
        }
        break;
}
