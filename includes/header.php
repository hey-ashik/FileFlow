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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" defer></script>
    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Main Stylesheet -->
    <?php $ver = '5.2.6'; // Premium UI Overhaul: Custom dropdown select arrow alignment ?>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo $ver; ?>">
    <style>
        /* Prevent FOUC: hide main content immediately during initial load */
        body.is-loading-page .main-content {
            display: none !important;
        }
        body.is-loading-page #first-load-skeleton {
            display: block !important;
        }
        #first-load-skeleton {
            display: none;
        }
    </style>
</head>

<body class="<?php echo $currentPage === 'home' ? 'page-home' : 'page-inner'; ?> is-loading-page">
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
                    <a href="/thoughts" class="nav-link <?php echo $currentPage === 'thoughts' ? 'active' : ''; ?>"
                        id="nav-thoughts" data-no-spa="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path
                                d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z">
                            </path>
                        </svg>
                        <span>Thoughts</span>
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
                    <div class="nav-search-container" style="position: relative;">
                        <form action="/search" method="GET" class="nav-search-pill">
                            <input type="text" name="q" id="header-search-input" placeholder="Search profile..."
                                autocomplete="off">
                            <button type="submit" aria-label="Search"
                                style="background: transparent; border: none; padding: 0; margin: 0; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--gray-500);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </button>
                        </form>
                        <div id="header-search-results"
                            style="display: none; position: absolute; top: 100%; right: 0; width: 320px; background: white; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.15); z-index: 9999; max-height: 400px; overflow-y: auto; margin-top: 0.5rem; border: 1px solid #e2e8f0; padding: 0.5rem;">
                        </div>
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
                                        <div class="nav-dropdown-name">
                                            <?php echo htmlspecialchars($currentUser['name']) . getVerifiedBadgeHtml($currentUser['is_verified'] ?? 0, $currentUser['is_admin'] ?? 0); ?>
                                        </div>
                                        <div class="nav-dropdown-email">
                                            <?php echo htmlspecialchars($currentUser['email']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="nav-dropdown-divider"></div>

                                <a href="/verify-badge" class="nav-dropdown-item" data-no-spa="true">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    Verify Badge
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const headerSearchInput = document.getElementById('header-search-input');
            const headerSearchResults = document.getElementById('header-search-results');
            let headerSearchTimeout = null;

            if (headerSearchInput && headerSearchResults) {
                headerSearchInput.addEventListener('input', function () {
                    const query = this.value.trim();

                    // Sync with page search input if on search page
                    const pageSearchInput = document.getElementById('profiles-search-input');
                    if (pageSearchInput) {
                        pageSearchInput.value = this.value;
                        if (typeof window.searchProfiles === 'function') {
                            window.searchProfiles();
                        }
                        return;
                    }

                    // Sync with page search input if on thoughts page
                    const thoughtsSearchInput = document.getElementById('thoughts-search-input');
                    if (thoughtsSearchInput) {
                        thoughtsSearchInput.value = this.value;
                        if (typeof window.searchThoughts === 'function') {
                            window.searchThoughts();
                        }
                        return;
                    }

                    // Otherwise show dropdown
                    clearTimeout(headerSearchTimeout);
                    if (!query) {
                        headerSearchResults.innerHTML = '';
                        headerSearchResults.style.display = 'none';
                        return;
                    }

                    headerSearchTimeout = setTimeout(async () => {
                        headerSearchResults.innerHTML = `
                            <div class="skeleton-search" style="padding: 4px; display: flex; flex-direction: column; gap: 8px; animation: fadeInSkeleton 0.2s ease-out;">
                                <div style="display: flex; align-items: center; gap: 12px; padding: 6px;">
                                    <div class="skeleton" style="width: 36px; height: 36px; border-radius: 50%;"></div>
                                    <div>
                                        <div class="skeleton" style="height: 12px; width: 120px; margin-bottom: 6px; border-radius: 4px;"></div>
                                        <div class="skeleton" style="height: 8px; width: 80px; border-radius: 4px;"></div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px; padding: 6px;">
                                    <div class="skeleton" style="width: 36px; height: 36px; border-radius: 50%;"></div>
                                    <div>
                                        <div class="skeleton" style="height: 12px; width: 140px; margin-bottom: 6px; border-radius: 4px;"></div>
                                        <div class="skeleton" style="height: 8px; width: 70px; border-radius: 4px;"></div>
                                    </div>
                                </div>
                            </div>
                        `;
                        headerSearchResults.style.display = 'block';

                        try {
                            const res = await fetch(`/search?json=1&q=${encodeURIComponent(query)}`);
                            const data = await res.json();

                            if (data && data.length > 0) {
                                let html = '<div style="display: flex; flex-direction: column; gap: 0.25rem;">';
                                data.forEach(profile => {
                                    const avatarColor = profile.avatar_color || '#16a34a';
                                    const avatarHtml = profile.avatar_path
                                        ? `<img src="${profile.avatar_path}" style="width: 100%; height: 100%; object-fit: cover;">`
                                        : `<span>${(profile.full_name || 'U').substring(0, 1).toUpperCase()}</span>`;

                                    const isVerified = (parseInt(profile.is_verified) || parseInt(profile.is_admin)) ? true : false;
                                    const badgeHtml = isVerified ? `<span style="display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 4px; vertical-align: middle;" title="Verified User"><i class="fa-solid fa-circle-check" style="color: rgb(62, 156, 230); font-size: 16px !important; line-height: 1; flex-shrink: 0;"></i></span>` : '';

                                    html += `
                                    <a href="/u/${profile.profile_slug}" data-no-spa="true" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem; border-radius: 8px; transition: background 0.2s; text-decoration: none; color: inherit; text-align: left;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                                        <div style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden; background: ${avatarColor}; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.9rem; flex-shrink: 0;">
                                            ${avatarHtml}
                                        </div>
                                        <div style="min-width: 0; flex: 1;">
                                            <div style="font-weight: 600; font-size: 0.9rem; color: #0f172a; display: flex; align-items: center; max-width: 100%; min-width: 0;">
                                                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; flex-shrink: 1;">${profile.full_name}</span>
                                                ${badgeHtml}
                                            </div>
                                            <div style="font-size: 0.75rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">@${profile.profile_slug}</div>
                                        </div>
                                    </a>
                                `;
                                });
                                html += '</div>';
                                headerSearchResults.innerHTML = html;
                            } else {
                                headerSearchResults.innerHTML = '<div style="text-align: center; color: var(--gray-500); padding: 1rem 0; font-size: 0.9rem;">No profiles found</div>';
                            }
                        } catch (err) {
                            headerSearchResults.innerHTML = '<div style="text-align: center; color: #ef4444; padding: 1rem 0; font-size: 0.9rem;">Error searching</div>';
                        }
                    }, 300);
                });

                // Hide dropdown when clicking outside
                document.addEventListener('click', function (e) {
                    if (!headerSearchInput.contains(e.target) && !headerSearchResults.contains(e.target)) {
                        headerSearchResults.style.display = 'none';
                    }
                });

                // Show dropdown on focus if input has value
                headerSearchInput.addEventListener('focus', function () {
                    if (this.value.trim() && !document.getElementById('profiles-search-input') && !document.getElementById('thoughts-search-input')) {
                        headerSearchResults.style.display = 'block';
                    }
                });
            }
        });
    </script>
    <?php
    // Render first-load skeleton based on current page
    $skeletonHtml = '';
    $currentPageVal = $currentPage ?? '';
    if ($currentPageVal === 'home') {
        $skeletonHtml = '
            <div style="max-width: 1200px; margin: 0 auto; padding: 4rem 1.5rem; text-align: center;">
                <div class="skeleton" style="height: 48px; width: 60%; margin: 0 auto 1.5rem; border-radius: 8px;"></div>
                <div class="skeleton" style="height: 24px; width: 40%; margin: 0 auto 3rem; border-radius: 6px;"></div>
                <div class="skeleton" style="height: 250px; width: 100%; max-width: 700px; margin: 0 auto 4rem; border-radius: 16px;"></div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin-top: 3rem;">
                    <div class="skeleton" style="height: 180px; border-radius: 12px;"></div>
                    <div class="skeleton" style="height: 180px; border-radius: 12px;"></div>
                    <div class="skeleton" style="height: 180px; border-radius: 12px;"></div>
                </div>
            </div>
        ';
    } else if ($currentPageVal === 'dashboard') {
        $skeletonHtml = '
            <div style="max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <div class="skeleton" style="height: 36px; width: 280px; border-radius: 6px; margin-bottom: 8px;"></div>
                        <div class="skeleton" style="height: 18px; width: 180px; border-radius: 4px;"></div>
                    </div>
                    <div class="skeleton" style="height: 40px; width: 150px; border-radius: 100px;"></div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">
                    <div class="skeleton" style="height: 100px; border-radius: 12px;"></div>
                    <div class="skeleton" style="height: 100px; border-radius: 12px;"></div>
                    <div class="skeleton" style="height: 100px; border-radius: 12px;"></div>
                    <div class="skeleton" style="height: 100px; border-radius: 12px;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div class="skeleton" style="height: 28px; width: 150px; border-radius: 6px;"></div>
                    <div class="skeleton" style="height: 36px; width: 120px; border-radius: 6px;"></div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem;">
                    <div class="skeleton" style="height: 80px; border-radius: 12px;"></div>
                    <div class="skeleton" style="height: 80px; border-radius: 12px;"></div>
                    <div class="skeleton" style="height: 80px; border-radius: 12px;"></div>
                    <div class="skeleton" style="height: 80px; border-radius: 12px;"></div>
                </div>
            </div>
        ';
    } else if ($currentPageVal === 'thoughts') {
        $skeletonHtml = '
            <div style="max-width: 800px; margin: 0 auto; padding: 2rem 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <div class="skeleton" style="height: 32px; width: 220px; margin-bottom: 8px; border-radius: 6px;"></div>
                        <div class="skeleton" style="height: 18px; width: 140px; border-radius: 4px;"></div>
                    </div>
                </div>
                <div class="skeleton" style="height: 160px; border-radius: 12px; margin-bottom: 2rem;"></div>
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div style="padding: 1.5rem; background: var(--white); border-radius: 12px; border: 1px solid var(--gray-100);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
                            <div class="skeleton" style="width: 44px; height: 44px; border-radius: 50%;"></div>
                            <div>
                                <div class="skeleton" style="height: 18px; width: 120px; margin-bottom: 6px; border-radius: 4px;"></div>
                                <div class="skeleton" style="height: 12px; width: 80px; border-radius: 4px;"></div>
                            </div>
                        </div>
                        <div class="skeleton" style="height: 16px; width: 90%; margin-bottom: 8px; border-radius: 4px;"></div>
                        <div class="skeleton" style="height: 16px; width: 75%; margin-bottom: 1.5rem; border-radius: 4px;"></div>
                    </div>
                </div>
            </div>
        ';
    } else if ($currentPageVal === 'messages') {
        $skeletonHtml = '
            <div style="max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem; height: 75vh; display: flex; gap: 1.5rem;">
                <div style="width: 320px; display: flex; flex-direction: column; gap: 1rem; border-right: 1px solid var(--gray-100); padding-right: 1.5rem;">
                    <div class="skeleton" style="height: 40px; border-radius: 8px; margin-bottom: 1rem;"></div>
                    <div class="skeleton" style="height: 60px; border-radius: 10px;"></div>
                    <div class="skeleton" style="height: 60px; border-radius: 10px;"></div>
                    <div class="skeleton" style="height: 60px; border-radius: 10px;"></div>
                </div>
                <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between; padding-left: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 12px; border-bottom: 1px solid var(--gray-100); padding-bottom: 1rem; margin-bottom: 1rem;">
                        <div class="skeleton" style="width: 40px; height: 40px; border-radius: 50%;"></div>
                        <div>
                            <div class="skeleton" style="height: 18px; width: 140px; margin-bottom: 6px; border-radius: 4px;"></div>
                        </div>
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 1rem; justify-content: flex-end; margin-bottom: 2rem;">
                        <div class="skeleton" style="height: 48px; width: 45%; align-self: flex-start; border-radius: 12px 12px 12px 0;"></div>
                        <div class="skeleton" style="height: 36px; width: 30%; align-self: flex-end; border-radius: 12px 12px 0 12px;"></div>
                    </div>
                    <div class="skeleton" style="height: 50px; border-radius: 25px;"></div>
                </div>
            </div>
        ';
    } else if ($currentPageVal === 'admin') {
        $skeletonHtml = '
            <div style="max-width: 100%; display: flex; gap: 0; min-height: 85vh; padding-top: 20px;">
                <!-- Sidebar Skeleton -->
                <div style="width: 280px; border-right: 1px solid var(--gray-100); padding: 2rem 1.5rem; display: flex; flex-direction: column; gap: 1.25rem; box-sizing: border-box;">
                    <div class="skeleton" style="height: 18px; width: 80px; margin-bottom: 8px; border-radius: 4px;"></div>
                    <div class="skeleton" style="height: 38px; width: 100%; border-radius: 6px;"></div>
                    <div class="skeleton" style="height: 38px; width: 100%; border-radius: 6px;"></div>
                    <div class="skeleton" style="height: 38px; width: 100%; border-radius: 6px;"></div>
                    <div class="skeleton" style="height: 38px; width: 100%; border-radius: 6px;"></div>
                </div>
                <!-- Content Skeleton -->
                <div style="flex: 1; padding: 2rem; box-sizing: border-box;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <div class="skeleton" style="height: 36px; width: 250px; border-radius: 6px;"></div>
                        <div class="skeleton" style="height: 40px; width: 120px; border-radius: 6px;"></div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
                        <div class="skeleton" style="height: 100px; border-radius: 10px;"></div>
                        <div class="skeleton" style="height: 100px; border-radius: 10px;"></div>
                        <div class="skeleton" style="height: 100px; border-radius: 10px;"></div>
                        <div class="skeleton" style="height: 100px; border-radius: 10px;"></div>
                    </div>
                    <div class="skeleton" style="height: 250px; border-radius: 10px;"></div>
                </div>
            </div>
        ';
    } else if ($currentPageVal === 'profile' || $currentPageVal === 'profile_card') {
        $skeletonHtml = '
            <div style="max-width: 1000px; margin: 0 auto; padding: 2rem 1.5rem;">
                <div class="skeleton" style="height: 200px; border-radius: 12px; margin-bottom: 4rem;"></div>
                <div style="position: relative; padding: 0 2rem; margin-bottom: 2rem;">
                    <div class="skeleton" style="position: absolute; top: -70px; left: 2rem; width: 110px; height: 110px; border-radius: 50%; border: 4px solid var(--white);"></div>
                    <div style="padding-top: 50px;">
                        <div class="skeleton" style="height: 28px; width: 200px; margin-bottom: 8px; border-radius: 6px;"></div>
                        <div class="skeleton" style="height: 18px; width: 140px; margin-bottom: 2rem; border-radius: 4px;"></div>
                    </div>
                </div>
            </div>
        ';
    } else {
        $skeletonHtml = '
            <div style="max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <div class="skeleton" style="height: 32px; width: 240px; margin-bottom: 8px; border-radius: 6px;"></div>
                        <div class="skeleton" style="height: 18px; width: 150px; border-radius: 4px;"></div>
                    </div>
                </div>
                <div class="skeleton" style="height: 180px; border-radius: 12px; margin-bottom: 2.5rem;"></div>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.25rem;">
                    <div class="skeleton" style="height: 140px; border-radius: 12px;"></div>
                    <div class="skeleton" style="height: 140px; border-radius: 12px;"></div>
                    <div class="skeleton" style="height: 140px; border-radius: 12px;"></div>
                    <div class="skeleton" style="height: 140px; border-radius: 12px;"></div>
                </div>
            </div>
        ';
    }
    echo '
    <div id="first-load-skeleton" class="skeleton-container-wrapper" style="width: 100%; min-height: 100vh; padding-top: 72px;">
        ' . $skeletonHtml . '
    </div>';
    ?>
    <main class="main-content">