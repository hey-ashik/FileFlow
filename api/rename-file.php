<?php
/**
 * FileFlow API - Rename File
 */

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'errors' => ['Method not allowed.']], 405);
}

// CSRF validation
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    jsonResponse(['success' => false, 'errors' => ['Invalid security token.']], 403);
}

$fileId = intval($_POST['file_id'] ?? 0);
$newName = $_POST['new_name'] ?? '';

if ($fileId <= 0) {
    jsonResponse(['success' => false, 'errors' => ['Invalid file ID.']], 400);
}

$result = renameFileInDatabase($fileId, $newName);

// Generate new CSRF token
$newToken = generateCSRFToken();
$result['csrf_token'] = $newToken;

jsonResponse($result);
