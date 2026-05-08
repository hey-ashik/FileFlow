<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$csrfToken = generateCSRFToken();
$currentPage = $currentPage ?? 'home';
$pageTitle = $pageTitle ?? APP_NAME . ' - Secure File Sharing';
$pageDescription = $pageDescription ?? APP_DESCRIPTION;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="file sharing, upload files, share files, cloud storage, FileFlow">
    <meta name="author" content="FileFlow">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo APP_URL; ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" defer></script>

    <!-- Main Stylesheet -->
    <?php $ver = '3.0.' . time(); // Using time() to break browser cache instantly ?>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo $ver; ?>">
</head>

<body class="<?php echo $currentPage === 'home' ? 'page-home' : 'page-inner'; ?>">
    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="navbar-inner">
            <a href="/" class="navbar-brand" id="nav-brand">
                <div class="brand-icon">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="32" height="32" rx="8" fill="url(#grad1)" />
                        <path d="M10 20L16 8L22 20" stroke="white" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path d="M12 16H20" stroke="white" stroke-width="2.5" stroke-linecap="round" />
                        <path d="M16 20V24" stroke="white" stroke-width="2.5" stroke-linecap="round" />
                        <defs>
                            <linearGradient id="grad1" x1="0" y1="0" x2="32" y2="32">
                                <stop stop-color="#16a34a" />
                                <stop offset="1" stop-color="#059669" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <span class="brand-text">File<span class="brand-accent">Flow</span></span>
            </a>

            <div class="navbar-links" id="nav-links">
                <div class="navbar-center-pill">
                    <a href="/" class="nav-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" id="nav-home">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                            <polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                        <span>Home</span>
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <?php $currentUser = getCurrentUser(); ?>
                        <a href="/dashboard" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>"
                            id="nav-dashboard">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7" />
                                <rect x="14" y="3" width="7" height="7" />
                                <rect x="14" y="14" width="7" height="7" />
                                <rect x="3" y="14" width="7" height="7" />
                            </svg>
                            <span>Dashboard</span>
                        </a>
                        <?php if (isset($currentUser['is_admin']) && $currentUser['is_admin']): ?>
                            <a href="/admin" class="nav-link <?php echo $currentPage === 'admin' ? 'active' : ''; ?>"
                                id="nav-admin">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2l9 4.9V12c0 5.5-4 10.7-9 12-5-1.3-9-6.5-9-12V6.9L12 2z" />
                                </svg>
                                <span>Admin Panel</span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="navbar-right-actions">
                    <?php if (isLoggedIn()): ?>
                        <div class="nav-user-menu" id="nav-user-menu">
                        <button class="nav-avatar" id="nav-avatar-btn"
                            style="<?php echo empty($currentUser['avatar_path']) ? 'background:'.htmlspecialchars($currentUser['color']) : 'background:transparent;'; ?>; padding: 0.25rem;">
                            <?php if (!empty($currentUser['avatar_path'])): ?>
                                <div style="display:flex; width:36px; height:36px; border-radius:50%; overflow:hidden; flex-shrink:0; align-items:center; justify-content:center;">
                                    <img src="<?php echo htmlspecialchars($currentUser['avatar_path']); ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; display:block;">
                                </div>
                            <?php else: ?>
                                <div style="display:flex; width:36px; height:36px; border-radius:50%; align-items:center; justify-content:center; flex-shrink:0;">
                                    <?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </button>
                        <div class="nav-dropdown" id="nav-dropdown">
                            <div class="nav-dropdown-header">
                                <div class="nav-dropdown-avatar"
                                    style="<?php echo empty($currentUser['avatar_path']) ? 'background:'.htmlspecialchars($currentUser['color']) : 'background:transparent;'; ?>; padding:0;">
                                    <?php if (!empty($currentUser['avatar_path'])): ?>
                                        <div style="display:flex; width:40px; height:40px; border-radius:50%; overflow:hidden; flex-shrink:0; align-items:center; justify-content:center;">
                                            <img src="<?php echo htmlspecialchars($currentUser['avatar_path']); ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; display:block;">
                                        </div>
                                    <?php else: ?>
                                        <div style="display:flex; width:40px; height:40px; border-radius:50%; align-items:center; justify-content:center; flex-shrink:0;">
                                            <?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="nav-dropdown-name"><?php echo htmlspecialchars($currentUser['name']); ?>
                                    </div>
                                    <div class="nav-dropdown-email"><?php echo htmlspecialchars($currentUser['email']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="nav-dropdown-divider"></div>
                            <a href="/dashboard" class="nav-dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7" />
                                    <rect x="14" y="3" width="7" height="7" />
                                    <rect x="14" y="14" width="7" height="7" />
                                    <rect x="3" y="14" width="7" height="7" />
                                </svg>
                                Dashboard
                            </a>
                            <?php if (isset($currentUser['is_admin']) && $currentUser['is_admin']): ?>
                                <a href="/admin" class="nav-dropdown-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M12 2l9 4.9V12c0 5.5-4 10.7-9 12-5-1.3-9-6.5-9-12V6.9L12 2z" />
                                    </svg>
                                    Admin Panel
                                </a>
                            <?php endif; ?>
                            <a href="/profile" class="nav-dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                Profile Card
                            </a>
                            <a href="/logout" class="nav-dropdown-item nav-dropdown-logout">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                </svg>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                        <a href="/login" class="nav-link <?php echo $currentPage === 'login' ? 'active' : ''; ?>"
                            id="nav-login">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                                <polyline points="10 17 15 12 10 7" />
                                <line x1="15" y1="12" x2="3" y2="12" />
                            </svg>
                            <span>Login</span>
                        </a>
                        <a href="/register" class="nav-link nav-cta" id="nav-register">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="8.5" cy="7" r="4" />
                                <line x1="20" y1="8" x2="20" y2="14" />
                                <line x1="23" y1="11" x2="17" y2="11" />
                            </svg>
                            <span>Sign Up</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <button class="navbar-toggle" id="nav-toggle" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    <input type="hidden" id="csrf-token" value="<?php echo $csrfToken; ?>">
    <main class="main-content">