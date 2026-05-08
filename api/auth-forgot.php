<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false,'errors'=>['Method not allowed.']], 405); }

$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCSRFToken($csrfToken)) { jsonResponse(['success'=>false,'errors'=>['Invalid security token.']], 403); }

$email = trim($_POST['email'] ?? '');
$result = createPasswordReset($email);
$result['csrf_token'] = generateCSRFToken();
jsonResponse($result);
