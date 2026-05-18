<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'errors' => ['Method not allowed.']], 405);
}

if (!isAdmin()) {
    jsonResponse(['success' => false, 'errors' => ['Unauthorized.']], 403);
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    jsonResponse(['success' => false, 'errors' => ['Invalid token.']], 403);
}

$flagFile = __DIR__ . '/../config/maintenance.flag';

if (file_exists($flagFile)) {
    unlink($flagFile);
} else {
    file_put_contents($flagFile, 'Maintenance Mode Active');
}

jsonResponse(['success' => true]);
