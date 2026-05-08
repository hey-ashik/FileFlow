<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) { jsonResponse(['success'=>false,'errors'=>['Not authenticated.']], 401); }

$user = getCurrentUser();
$stats = getUserDashboardStats($user['id']);
$uploadStats = getUploadStats($user['id'], 7);

jsonResponse(['success'=>true, 'stats'=>$stats, 'upload_activity'=>$uploadStats]);
