<?php
/**
 * FileFlow API - Create Folder
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

// Get folder name (handle both old and new param names)
$folderName = trim($_POST['fld_slug_val'] ?? $_POST['fld_name_val'] ?? '');
$password = $_POST['password'] ?? null;
$expiry = $_POST['expiry'] ?? null;

if (empty($folderName)) {
    jsonResponse(['success' => false, 'errors' => ['Please enter a folder name.']], 400);
}

// Create folder
$result = createFolder($folderName, $password, $expiry);

if ($result['success']) {
    // Generate new CSRF token
    $newToken = generateCSRFToken();
    $result['csrf_token'] = $newToken;
    jsonResponse($result, 201);
} else {
    jsonResponse($result, 400);
}
