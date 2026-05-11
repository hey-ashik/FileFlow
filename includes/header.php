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
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" defer></script>

    <!-- Main Stylesheet -->
    <?php $ver = '3.2.8'; // Premium UI Overhaul: Vertical Advanced Settings ?>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo $ver; ?>">
</head>

<body class="<?php echo $currentPage === 'home' ? 'page-home' : 'page-inner'; ?>">
    <!-- SPA Loader -->
    <div id="spa-loader">
        <div id="spa-loader-fill"></div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="navbar-inner">
            <a href="/" class="navbar-brand" id="nav-brand">
                <div class="brand-icon">
                    <img src="/assets/img/favicon.png" alt="Logo"
                        style="width: 32px; height: 32px; object-fit: contain; border-radius: 6px;">
                </div>
                <span class="brand-text">File<span class="brand-accent">Flow</span></span>
            </a>

            <button class="navbar-toggle" id="nav-toggle" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="navbar-links" id="nav-links">
                <div class="navbar-center-pill">
                    <a href="/" class="nav-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" id="nav-home">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <span>Home</span>
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <?php $currentUser = getCurrentUser(); ?>
                        <a href="/dashboard" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>"
                            id="nav-dashboard">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
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
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M12 2l9 4.9V12c0 5.5-4 10.7-9 12-5-1.3-9-6.5-9-12V6.9L12 2z" />
                                </svg>
                                <span>Admin Panel</span>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="/login" class="nav-link <?php echo $currentPage === 'login' ? 'active' : ''; ?>"
                            id="nav-login">
                            <svg width="18" height="18" viewBox="0 0 512 512" fill="currentColor">
                                <path
                                    d="M217.9 105.9L340.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L217.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1L32 320c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM352 416l64 0c17.7 0 32-14.3 32-32l0-256c0-17.7-14.3-32-32-32l-64 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l64 0c53 0 96 43 96 96l0 256c0 53-43 96-96 96l-64 0c-17.7 0-32-14.3-32-32s14.3-32 32-32z" />
                            </svg>
                            <span>Login</span>
                        </a>
                        <a href="/register" class="nav-link <?php echo $currentPage === 'register' ? 'active' : ''; ?>"
                            id="nav-register">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="8.5" cy="7" r="4" />
                                <line x1="20" y1="8" x2="20" y2="14" />
                                <line x1="23" y1="11" x2="17" y2="11" />
                            </svg>
                            <span>Sign Up</span>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="navbar-right-actions">
                    <div class="nav-search-container">
                        <form action="/search" method="GET" class="nav-search-pill">
                            <input type="text" name="q" placeholder="Search profile..." autocomplete="off">
                            <button type="submit" aria-label="Search"
                                style="background: transparent; border: none; padding: 0; margin: 0; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--gray-500);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </button>
                        </form>
                    </div>

                    <?php if (isLoggedIn()): ?>
                        <div class="nav-user-menu" id="nav-user-menu">
                            <button class="nav-avatar" id="nav-avatar-btn"
                                style="<?php echo empty($currentUser['avatar_path']) ? 'background:' . htmlspecialchars($currentUser['color']) : 'background:transparent;'; ?>; padding: 0.25rem;">
                                <?php if (!empty($currentUser['avatar_path'])): ?>
                                    <div
                                        style="display:flex; width:36px; height:36px; border-radius:50%; overflow:hidden; flex-shrink:0; align-items:center; justify-content:center;">
                                        <img src="<?php echo htmlspecialchars($currentUser['avatar_path']); ?>" alt="Avatar"
                                            style="width:100%; height:100%; object-fit:cover; display:block;">
                                    </div>
                                <?php else: ?>
                                    <div
                                        style="display:flex; width:36px; height:36px; border-radius:50%; align-items:center; justify-content:center; flex-shrink:0;">
                                        <?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </button>
                            <div class="nav-dropdown" id="nav-dropdown">
                                <div class="nav-dropdown-header">
                                    <div class="nav-dropdown-avatar"
                                        style="<?php echo empty($currentUser['avatar_path']) ? 'background:' . htmlspecialchars($currentUser['color']) : 'background:transparent;'; ?>; padding:0;">
                                        <?php if (!empty($currentUser['avatar_path'])): ?>
                                            <div
                                                style="display:flex; width:40px; height:40px; border-radius:50%; overflow:hidden; flex-shrink:0; align-items:center; justify-content:center;">
                                                <img src="<?php echo htmlspecialchars($currentUser['avatar_path']); ?>"
                                                    alt="Avatar"
                                                    style="width:100%; height:100%; object-fit:cover; display:block;">
                                            </div>
                                        <?php else: ?>
                                            <div
                                                style="display:flex; width:40px; height:40px; border-radius:50%; align-items:center; justify-content:center; flex-shrink:0;">
                                                <?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="nav-dropdown-name"><?php echo htmlspecialchars($currentUser['name']); ?>
                                        </div>
                                        <div class="nav-dropdown-email">
                                            <?php echo htmlspecialchars($currentUser['email']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="nav-dropdown-divider"></div>

                                <?php if (isset($currentUser['is_admin']) && $currentUser['is_admin']): ?>
                                    <a href="/admin" class="nav-dropdown-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <path d="M12 2l9 4.9V12c0 5.5-4 10.7-9 12-5-1.3-9-6.5-9-12V6.9L12 2z" />
                                        </svg>
                                        Admin Panel
                                    </a>
                                <?php endif; ?>
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
                                <a href="/profile" class="nav-dropdown-item" data-no-spa="true">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                    Profile Card
                                </a>
                                <a href="/messages" class="nav-dropdown-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                    Messages
                                </a>
                                <a href="/logout" class="nav-dropdown-item nav-dropdown-logout"
                                    onclick="window.location.href='/logout'; return false;">
                                    <svg width="16" height="16" viewBox="0 0 512 512" fill="currentColor">
                                        <path
                                            d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z" />
                                    </svg>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <input type="hidden" id="csrf-token" value="<?php echo $csrfToken; ?>">
    <main class="main-content">