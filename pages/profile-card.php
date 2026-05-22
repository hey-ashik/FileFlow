<?php
// Ensure functions are available
if (!function_exists('incrementProfileVisits')) {
    require_once __DIR__ . '/../includes/functions.php';
}

$db = getDB();

try {
    $stmt = $db->prepare("SELECT id, full_name, email, avatar_path, avatar_color, cover_path, phone, work_experience, social_links, cv_path, cv_description, cv_button_color, profile_visits, is_public FROM users WHERE profile_slug = ? AND is_active = 1");
    $stmt->execute([$profileSlug]);
    $userProfile = $stmt->fetch();
} catch (PDOException $e) {
    // Fallback if profile_visits or is_public column doesn't exist yet
    $stmt = $db->prepare("SELECT id, full_name, email, avatar_path, avatar_color, cover_path, phone, work_experience, social_links, cv_path, cv_description, cv_button_color FROM users WHERE profile_slug = ? AND is_active = 1");
    $stmt->execute([$profileSlug]);
    $userProfile = $stmt->fetch();
    if ($userProfile) {
        $userProfile['profile_visits'] = 0;
        $userProfile['is_public'] = 0;
    }
}

if (!$userProfile) {
    $errorType = 'not_found';
    require __DIR__ . '/error.php';
    exit;
}

// Increment visit counter
try {
    incrementProfileVisits($profileSlug);
    if (isset($userProfile['profile_visits'])) {
        $userProfile['profile_visits']++;
    }
} catch (Exception $e) {
    // Ignore increment errors to prevent page crash
}

// Parse social links if JSON
$socialLinks = [];
if (!empty($userProfile['social_links'])) {
    $parsed = json_decode($userProfile['social_links'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
        $socialLinks = $parsed;
    } else {
        // Fallback for old plain text format
        $socialLinks = [['platform' => 'other', 'value' => $userProfile['social_links']]];
    }
}
$hasSocial = false;
foreach ($socialLinks as $sl) {
    if (!empty(trim($sl['value'])))
        $hasSocial = true;
}

$pageTitle = htmlspecialchars($userProfile['full_name']) . "'s Profile Card";
$pageDescription = "View " . htmlspecialchars($userProfile['full_name']) . "'s digital profile card.";

$isLoggedInUser = function_exists('isLoggedIn') && isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $pageTitle; ?></title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- QRCode JS -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <style>
        :root {
            --primary: #16a34a;
            --green-600: #16a34a;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --gray-900: #0f172a;
            --text-muted: #64748b;
            --gray-500: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: transparent;
            color: var(--text-main);
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
            min-height: 100vh;
        }

        .profile-container {
            width: 100%;
            max-width: 450px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }

        .card-header {
            height: 180px;
            background:
                <?php echo empty($userProfile['cover_path']) ? 'linear-gradient(135deg, ' . htmlspecialchars($userProfile['avatar_color']) . ', #059669)' : 'url(' . htmlspecialchars($userProfile['cover_path']) . ') center/cover no-repeat'; ?>
            ;
            position: relative;
        }

        .avatar-container {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background:
                <?php echo htmlspecialchars($userProfile['avatar_color']); ?>
            ;
            position: absolute;
            bottom: -60px;
            left: 50%;
            transform: translateX(-50%);
            border: 4px solid var(--card-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: 600;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .avatar-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-body {
            padding: 4rem 2rem 2rem 2rem;
            text-align: center;
        }

        .name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.25rem;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
            margin-bottom: 1.5rem;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .info-row {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 0.5rem;
        }

        .info-row svg {
            width: 16px;
            height: 16px;
            color: var(--primary);
        }

        .section {
            text-align: left;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-main);
        }

        .section-content {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .social-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
            justify-items: center;
            justify-content: center;
            display: flex;
            flex-wrap: wrap;
        }

        .social-icon-link {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
            transition: all 0.2s;
            text-decoration: none;
        }

        .social-icon-link:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .social-icon-link svg {
            width: 24px;
            height: 24px;
        }

        /* Specific Brand Colors on Hover */
        .social-icon-link.s-facebook:hover {
            background: #1877F2;
            color: white;
        }

        .social-icon-link.s-twitter:hover {
            background: #1DA1F2;
            color: white;
        }

        /* or black for X */
        .social-icon-link.s-instagram:hover {
            background: #E1306C;
            color: white;
        }

        .social-icon-link.s-linkedin:hover {
            background: #0A66C2;
            color: white;
        }

        .social-icon-link.s-whatsapp:hover {
            background: #25D366;
            color: white;
        }

        .social-icon-link.s-github:hover {
            background: #333;
            color: white;
        }

        .social-icon-link.s-youtube:hover {
            background: #FF0000;
            color: white;
        }

        .qr-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        #qrcode {
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .btn-share {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #f1f5f9;
            color: var(--text-main);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-share:hover {
            background: #e2e8f0;
        }

        .footer-branding {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .footer-branding a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        /* Thoughts tab overrides: generally reduce margin and padding of the thought post boxes */
        #user-thoughts-container .thought-card {
            padding: 1rem !important;
            margin-bottom: 1rem !important;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
            border: 1px solid #f1f5f9;
        }

        /* Responsiveness for mobile screens */
        @media (max-width: 480px) {
            body {
                padding: 1rem 0.5rem;
            }

            .card-body {
                padding: 3.5rem 1rem 1.5rem 1rem;
            }

            .name {
                font-size: 1.3rem;
            }

            .contact-info {
                font-size: 0.9rem;
            }

            /* Adjust avatar size on small screen to avoid taking up too much vertical space */
            .avatar-container {
                width: 100px;
                height: 100px;
                bottom: -50px;
            }

            .card-header {
                height: 140px;
            }

            /* Reduce margin and padding of the post box in the thoughts section even more on mobile */
            #user-thoughts-container .thought-card {
                padding: 0.75rem !important;
                margin-bottom: 0.75rem !important;
            }

            /* Fix nested comments left margin indentation on mobile so it doesn't push them off-screen */
            #user-thoughts-container [style*="margin-left"] {
                margin-left: 0.75rem !important;
                padding-left: 0.5rem !important;
            }

            /* Prevent email/phone text overflow */
            .info-row a {
                word-break: break-all;
            }
        }
    </style>
</head>

<body>
    <div style="position: fixed; inset: 0; z-index: -1; background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 50%, #f0fdf4 100%); overflow: hidden; pointer-events: none;">
        <div style="position: absolute; border-radius: 50%; filter: blur(80px); opacity: .4; width: 400px; height: 400px; background: #86efac; top: -100px; right: -100px; animation: float 10s ease-in-out infinite;"></div>
        <div style="position: absolute; border-radius: 50%; filter: blur(80px); opacity: .4; width: 300px; height: 300px; background: #10b981; bottom: -80px; left: -80px; animation: float 12s ease-in-out infinite reverse;"></div>
        <div style="position: absolute; border-radius: 50%; filter: blur(80px); opacity: .4; width: 200px; height: 200px; background: #bbf7d0; top: 40%; left: 60%; animation: float 8s ease-in-out infinite 2s;"></div>
    </div>
    <style>@keyframes float { 0% { transform: translateY(0) scale(1); } 50% { transform: translateY(-20px) scale(1.05); } 100% { transform: translateY(0) scale(1); } }</style>

    <div class="profile-container" style="position: relative; z-index: 1;">
        <div class="card">
            <div class="card-header">
                <div class="avatar-container">
                    <?php if (!empty($userProfile['avatar_path'])): ?>
                        <img src="<?php echo htmlspecialchars($userProfile['avatar_path']); ?>"
                            alt="<?php echo htmlspecialchars($userProfile['full_name']); ?>">
                    <?php else: ?>
                        <span><?php echo strtoupper(substr($userProfile['full_name'], 0, 1)); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <h1 class="name"><?php echo htmlspecialchars($userProfile['full_name']); ?></h1>
                <div style="font-size: 0.9rem; color: #94a3b8; font-weight: 500;">
                    @<?php echo htmlspecialchars($profileSlug); ?></div>

                <div class="contact-info">
                    <?php if (!empty($userProfile['email'])): ?>
                        <div class="info-row">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                <polyline points="22,6 12,13 2,6" />
                            </svg>
                            <a href="mailto:<?php echo htmlspecialchars($userProfile['email']); ?>"
                                style="color: inherit; text-decoration: none;">
                                <?php echo htmlspecialchars($userProfile['email']); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($userProfile['phone'])): ?>
                        <div class="info-row">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                            </svg>
                            <a href="tel:<?php echo htmlspecialchars($userProfile['phone']); ?>"
                                style="color: inherit; text-decoration: none;">
                                <?php echo htmlspecialchars($userProfile['phone']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($userProfile['is_public'])): 
                    // Get connection count
                    $followerCount = 0;
                    try {
                        $connCountStmt = $db->prepare("SELECT COUNT(*) FROM connections WHERE (requester_id = ? OR receiver_id = ?) AND status = 'accepted'");
                        $connCountStmt->execute([$userProfile['id'], $userProfile['id']]);
                        $followerCount = $connCountStmt->fetchColumn();
                    } catch(Exception $e) {}

                    $connStatus = null;
                    $connId = null;
                    $isRequester = false;
                    if ($isLoggedInUser) {
                        $currUser = getCurrentUser();
                        if ($currUser['id'] !== $userProfile['id']) {
                            try {
                                $stmt = $db->prepare("SELECT id, status, requester_id FROM connections WHERE (requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?)");
                                $stmt->execute([$currUser['id'], $userProfile['id'], $userProfile['id'], $currUser['id']]);
                                $c = $stmt->fetch();
                                if ($c) {
                                    $connStatus = $c['status'];
                                    $connId = $c['id'];
                                    $isRequester = ($c['requester_id'] == $currUser['id']);
                                }
                            } catch (Exception $e) {}
                        } else {
                            $connStatus = 'self'; // Viewing own profile
                        }
                    }
                ?>
                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: center; justify-content: flex-start; gap: 0.75rem; font-size: 0.9rem; color: var(--text-muted); font-weight: 500; margin-bottom: 1rem;">
                            <span style="display: flex; align-items: center; gap: 0.25rem;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                Views: <span style="color: var(--text-main); font-weight: 700;"><?php echo number_format($userProfile['profile_visits'] ?? 0); ?></span>
                            </span>
                            <span style="color: #cbd5e1;">|</span>
                            <span style="display: flex; align-items: center; gap: 0.25rem;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                Followers: <span style="color: var(--text-main); font-weight: 700;"><?php echo number_format($followerCount); ?></span>
                            </span>
                        </div>
                        <?php if ($connStatus !== 'self'): ?>
                        <div style="display: flex; gap: 0.75rem; justify-content: center;">
                            <?php if ($connStatus === 'accepted'): ?>
                                <button class="btn-connect" ondblclick="handleDisconnect(<?php echo $userProfile['id']; ?>, this)" title="Double-click to unfriend" style="flex: 1; padding: 0.6rem 1rem; border-radius: 8px; border: none; background: #cbd5e1; color: var(--text-main); font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: background 0.2s;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    Connected
                                </button>
                            <?php elseif ($connStatus === 'pending'): ?>
                                <?php if ($isRequester): ?>
                                    <button class="btn-connect" disabled style="flex: 1; padding: 0.6rem 1rem; border-radius: 8px; border: none; background: #cbd5e1; color: var(--text-main); font-weight: 600; cursor: default; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        Requested
                                    </button>
                                <?php else: ?>
                                    <button onclick="respondConnectionProfile(<?php echo $connId; ?>, 'accepted', this)" style="flex: 1; padding: 0.6rem; border-radius: 8px; border: none; background: #10b981; color: white; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);">Accept</button>
                                    <button onclick="respondConnectionProfile(<?php echo $connId; ?>, 'rejected', this)" style="flex: 1; padding: 0.6rem; border-radius: 8px; border: 1px solid #cbd5e1; background: #f1f5f9; color: #64748b; font-weight: 600; cursor: pointer; transition: all 0.2s;">Decline</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button onclick="handleConnect(<?php echo $userProfile['id']; ?>)" class="btn-connect" style="flex: 1; padding: 0.6rem 1rem; border-radius: 8px; border: none; background: var(--primary); color: white; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                    Connect
                                </button>
                            <?php endif; ?>

                            <button onclick="handleMessage(<?php echo $userProfile['id']; ?>, '<?php echo $connStatus; ?>')" class="btn-message" style="flex: 1; padding: 0.6rem 1rem; border-radius: 8px; border: 1px solid #cbd5e1; background: white; color: var(--text-main); font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                Message
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($userProfile['is_public'])): ?>
                    <div class="profile-tabs" style="display: flex; border-bottom: 1px solid #e2e8f0; margin-bottom: 1.5rem; gap: 0.5rem;">
                        <button class="tab-btn active" onclick="switchTab('professional')" id="tab-professional" style="flex: 1; background: none; border: none; padding: 0.75rem; font-weight: 600; font-size: 0.95rem; color: var(--primary); border-bottom: 2px solid var(--primary); cursor: pointer; transition: all 0.2s;">Professional</button>
                        <button class="tab-btn" onclick="switchTab('thoughts')" id="tab-thoughts" style="flex: 1; background: none; border: none; padding: 0.75rem; font-weight: 600; font-size: 0.95rem; color: var(--text-muted); border-bottom: 2px solid transparent; cursor: pointer; transition: all 0.2s;">Thoughts</button>
                    </div>
                <?php endif; ?>

                <div id="tab-content-professional">
                <?php if ($hasSocial): ?>
                    <div class="section">
                        <div class="section-title">Social Connections</div>
                        <div class="social-grid">
                            <?php
                            foreach ($socialLinks as $sl) {
                                $p = $sl['platform'] ?? 'other';
                                $v = trim($sl['value'] ?? '');
                                if (empty($v))
                                    continue;

                                $href = $v;
                                if ($p === 'whatsapp') {
                                    $cleanPhone = preg_replace('/[^0-9]/', '', $v);
                                    $href = "https://wa.me/" . $cleanPhone;
                                } else if (!preg_match("~^(?:f|ht)tps?://~i", $v) && $p !== 'other') {
                                    $href = "https://" . $v;
                                }

                                $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>'; // Link def
                        
                                if ($p === 'facebook')
                                    $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>';
                                if ($p === 'twitter')
                                    $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path></svg>';
                                if ($p === 'instagram')
                                    $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>';
                                if ($p === 'linkedin')
                                    $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>';
                                if ($p === 'whatsapp')
                                    $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>';
                                if ($p === 'github')
                                    $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>';
                                if ($p === 'youtube')
                                    $svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon></svg>';

                                if ($p === 'other') {
                                    echo '<a href="' . htmlspecialchars($href) . '" target="_blank" style="display:inline-block; margin-right:1rem; color:var(--primary); text-decoration:underline;">' . htmlspecialchars($v) . '</a>';
                                } else {
                                    echo '<a href="' . htmlspecialchars($href) . '" class="social-icon-link s-' . htmlspecialchars($p) . '" target="_blank" title="' . ucfirst(htmlspecialchars($p)) . '">' . $svg . '</a>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($userProfile['work_experience'])): ?>
                    <div class="section">
                        <div class="section-title">Work Experience</div>
                        <div class="section-content"><?php echo htmlspecialchars($userProfile['work_experience']); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($userProfile['cv_path'])): ?>
                    <div class="section">
                        <div class="section-title">Career Portfolio</div>
                        <?php if (!empty($userProfile['cv_description'])): ?>
                            <div class="section-content" style="margin-bottom: 1rem; text-align: left;"><?php echo htmlspecialchars($userProfile['cv_description']); ?></div>
                        <?php endif; ?>
                        <a href="<?php echo htmlspecialchars($userProfile['cv_path']); ?>" target="_blank"
                            style="display: block; width: 100%; text-align: center; padding: 0.75rem; border-radius: 8px; font-weight: 600; text-decoration: none; color: white; background-color: <?php echo htmlspecialchars($userProfile['cv_button_color'] ?: '#16a34a'); ?>; transition: opacity 0.2s;">
                            View My Resume / CV
                        </a>
                    </div>
                <?php endif; ?>

                <div class="qr-section">
                    <div id="qrcode"></div>
                    <button class="btn-share" onclick="copyProfileLink()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                        </svg>
                        Copy Profile Link
                    </button>
                </div>
                </div> <!-- End of tab-content-professional -->
                
                <?php if (!empty($userProfile['is_public'])): ?>
                <div id="tab-content-thoughts" style="display: none;">
                    <div id="user-thoughts-container" style="text-align: left;">
                        <!-- Thoughts will be loaded here via JS -->
                        <div style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Loading thoughts...</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-branding"
            style="display: flex; justify-content: center; align-items: center; gap: 10px; font-size: 0.8rem; color: var(--text-muted); margin-top: 2rem;">
            <div>Powered by <a href="/"
                    style="color: var(--primary); text-decoration: none; font-weight: 600;">FileFlow</a></div>
        </div>
    </div>

    <script>
        window.switchTab = function(tab) {
            const btnProf = document.getElementById('tab-professional');
            const btnThoughts = document.getElementById('tab-thoughts');
            const contentProf = document.getElementById('tab-content-professional');
            const contentThoughts = document.getElementById('tab-content-thoughts');
            
            if (!btnProf || !btnThoughts) return;

            if (tab === 'professional') {
                btnProf.style.color = 'var(--primary)';
                btnProf.style.borderBottomColor = 'var(--primary)';
                btnThoughts.style.color = 'var(--text-muted)';
                btnThoughts.style.borderBottomColor = 'transparent';
                contentProf.style.display = 'block';
                contentThoughts.style.display = 'none';
            } else {
                btnThoughts.style.color = 'var(--primary)';
                btnThoughts.style.borderBottomColor = 'var(--primary)';
                btnProf.style.color = 'var(--text-muted)';
                btnProf.style.borderBottomColor = 'transparent';
                contentProf.style.display = 'none';
                contentThoughts.style.display = 'block';
                loadUserThoughts();
            }
        }

        let thoughtsLoaded = false;
        window.loadUserThoughts = async function() {
            if (thoughtsLoaded) return;
            const container = document.getElementById('user-thoughts-container');
            try {
                const res = await fetch(`/api/thoughts?action=get_user_thoughts&user_id=<?php echo $userProfile['id']; ?>`);
                const html = await res.text();
                container.innerHTML = html;
                thoughtsLoaded = true;
                
                // Stagger animation delays
                const cards = container.querySelectorAll('.thought-card');
                cards.forEach((card, idx) => {
                    card.style.animationDelay = `${idx * 0.05}s`;
                    card.style.opacity = '0'; // start hidden before animation begins
                });
            } catch (err) {
                container.innerHTML = '<div style="color: red; text-align: center;">Failed to load thoughts.</div>';
            }
        }

        window.toggleThoughtLike = async function(thoughtId, btnElem) {
            <?php if (!$isLoggedInUser): ?>
                alert('Please log in to like posts.');
                return;
            <?php endif; ?>
            
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_like');
                formData.append('thought_id', thoughtId);
                
                const res = await fetch('/api/thoughts', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    const countSpan = btnElem.querySelector('.like-count');
                    countSpan.textContent = data.count;
                    const svg = btnElem.querySelector('svg');
                    if (data.liked) {
                        svg.setAttribute('fill', 'var(--primary)');
                        svg.setAttribute('stroke', 'var(--primary)');
                    } else {
                        svg.setAttribute('fill', 'none');
                        svg.setAttribute('stroke', 'currentColor');
                    }
                } else {
                    alert(data.message || 'Error');
                }
            } catch (err) {}
        }

        window.toggleComments = function(thoughtId) {
            const section = document.getElementById(`comments-section-${thoughtId}`);
            if (section.style.display === 'none') {
                section.style.display = 'block';
                loadComments(thoughtId);
            } else {
                section.style.display = 'none';
            }
        }

        window.loadComments = async function(thoughtId) {
            const container = document.getElementById(`comments-list-${thoughtId}`);
            container.innerHTML = '<div style="text-align: center; color: var(--text-muted); font-size: 0.9rem;">Loading...</div>';
            try {
                const res = await fetch(`/api/thoughts?action=get_comments&thought_id=${thoughtId}`);
                const html = await res.text();
                container.innerHTML = html;
            } catch (err) {
                container.innerHTML = '<div style="color: red; font-size: 0.9rem;">Failed to load comments.</div>';
            }
        }

        window.postComment = async function(thoughtId) {
            const input = document.getElementById(`comment-input-${thoughtId}`);
            const comment = input.value.trim();
            if (!comment) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'post_comment');
                formData.append('thought_id', thoughtId);
                formData.append('comment', comment);
                
                const res = await fetch('/api/thoughts', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    input.value = '';
                    loadComments(thoughtId);
                    const card = document.getElementById(`comments-section-${thoughtId}`).closest('.thought-card');
                    const countSpan = card.querySelector('.comment-count');
                    countSpan.textContent = data.count;
                } else {
                    alert(data.message || 'Error');
                }
            } catch (err) {
                alert('Failed to post comment.');
            }
        }

        window.shareThought = async function(thoughtId) {
            try {
                const formData = new FormData();
                formData.append('action', 'share_thought');
                formData.append('thought_id', thoughtId);
                
                const res = await fetch('/api/thoughts', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    const card = document.getElementById(`comments-section-${thoughtId}`).closest('.thought-card');
                    const countSpan = card.querySelector('.share-count');
                    countSpan.textContent = data.count;
                    
                    navigator.clipboard.writeText(data.link).then(() => {
                        alert('Link copied to clipboard!');
                    }).catch(err => {
                        alert('Shared! Link: ' + data.link);
                    });
                } else {
                    if (data.message === 'Please log in to share.') {
                        alert(data.message);
                    } else {
                        navigator.clipboard.writeText(window.location.origin + '/thoughts?id=' + thoughtId);
                        alert('Link copied to clipboard!');
                    }
                }
            } catch (err) {}
        }
        
        window.deleteThought = async function(thoughtId) {
            if (!confirm('Are you sure you want to delete this post?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_thought');
                formData.append('thought_id', thoughtId);
                
                const res = await fetch('/api/thoughts', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    const card = document.querySelector(`.thought-card[data-id="${thoughtId}"]`);
                    if (card) {
                        card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        card.style.maxHeight = card.offsetHeight + 'px';
                        card.offsetHeight; // force reflow
                        
                        card.style.maxHeight = '0';
                        card.style.paddingTop = '0';
                        card.style.paddingBottom = '0';
                        card.style.marginTop = '0';
                        card.style.marginBottom = '0';
                        card.style.border = 'none';
                        card.style.overflow = 'hidden';
                        
                        setTimeout(() => {
                            card.remove();
                        }, 500);
                    } else {
                        thoughtsLoaded = false;
                        window.loadUserThoughts();
                    }
                } else {
                    alert(data.message || 'Error deleting post.');
                }
            } catch (err) {
                alert('Failed to delete post.');
            }
        }
        
        window.editThought = function(thoughtId) {
            const displayDiv = document.getElementById(`thought-content-display-${thoughtId}`);
            const rawDiv = document.getElementById(`thought-content-raw-${thoughtId}`);
            const rawLink = document.getElementById(`thought-link-raw-${thoughtId}`);
            const rawPrivacy = document.getElementById(`thought-privacy-raw-${thoughtId}`);
            const hasMedia = document.getElementById(`thought-has-media-${thoughtId}`).textContent === '1';
            if (document.getElementById(`thought-edit-container-${thoughtId}`)) return;
            
            const container = document.createElement('div');
            container.id = `thought-edit-container-${thoughtId}`;
            container.style.marginBottom = '1rem';
            
            const editorContainer = document.createElement('div');
            editorContainer.style.marginBottom = '0.5rem';
            initializeRichTextEditor(editorContainer, 'thought-edit-content-' + thoughtId, 'Edit your thought...', rawDiv.textContent);
            const textarea = editorContainer.querySelector('textarea');
            
            const linkInput = document.createElement('input');
            linkInput.type = 'text';
            linkInput.placeholder = 'Link URL (optional)';
            linkInput.style.width = '100%';
            linkInput.style.padding = '0.5rem';
            linkInput.style.borderRadius = '8px';
            linkInput.style.border = '1px solid #cbd5e1';
            linkInput.style.marginBottom = '0.5rem';
            linkInput.style.fontFamily = 'inherit';
            linkInput.value = rawLink ? rawLink.textContent : '';
            
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.multiple = true;
            fileInput.accept = 'image/*,video/*';
            fileInput.style.marginBottom = '0.5rem';
            fileInput.style.display = 'block';
            fileInput.style.fontSize = '0.85rem';
            
            let removeMediaCheckbox = null;
            if (hasMedia) {
                const removeLabel = document.createElement('label');
                removeLabel.style.display = 'block';
                removeLabel.style.fontSize = '0.85rem';
                removeLabel.style.marginBottom = '0.5rem';
                removeLabel.style.color = '#ef4444';
                removeLabel.style.cursor = 'pointer';
                
                removeMediaCheckbox = document.createElement('input');
                removeMediaCheckbox.type = 'checkbox';
                removeMediaCheckbox.style.marginRight = '0.5rem';
                
                removeLabel.appendChild(removeMediaCheckbox);
                removeLabel.appendChild(document.createTextNode('Remove existing media (or select files above to replace them)'));
                container.appendChild(editorContainer);
                container.appendChild(linkInput);
                container.appendChild(fileInput);
                container.appendChild(removeLabel);
            } else {
                container.appendChild(editorContainer);
                container.appendChild(linkInput);
                container.appendChild(fileInput);
            }
            
            const privacySelect = document.createElement('select');
            privacySelect.className = 'custom-select-arrow';
            privacySelect.style.border = '1px solid #cbd5e1';
            privacySelect.style.borderRadius = '8px';
            privacySelect.style.padding = '0.5rem 1rem';
            privacySelect.style.fontFamily = 'inherit';
            privacySelect.style.fontSize = '0.85rem';
            privacySelect.style.marginBottom = '0.5rem';
            privacySelect.style.width = '100%';
            privacySelect.innerHTML = '<option value="public">Public</option><option value="friends">Friends</option><option value="private">Private</option>';
            privacySelect.value = rawPrivacy ? rawPrivacy.textContent : 'public';
            container.appendChild(privacySelect);
            
            const actions = document.createElement('div');
            actions.style.display = 'flex';
            actions.style.gap = '0.5rem';
            
            const saveBtn = document.createElement('button');
            saveBtn.textContent = 'Save';
            saveBtn.style.padding = '0.4rem 1rem';
            saveBtn.style.background = 'var(--green-600)';
            saveBtn.style.color = 'white';
            saveBtn.style.border = 'none';
            saveBtn.style.borderRadius = '6px';
            saveBtn.style.cursor = 'pointer';
            
            const cancelBtn = document.createElement('button');
            cancelBtn.textContent = 'Cancel';
            cancelBtn.style.padding = '0.4rem 1rem';
            cancelBtn.style.background = '#e2e8f0';
            cancelBtn.style.color = '#475569';
            cancelBtn.style.border = 'none';
            cancelBtn.style.borderRadius = '6px';
            cancelBtn.style.cursor = 'pointer';
            
            saveBtn.onclick = async () => {
                const newContent = textarea.value.trim();
                const newLink = linkInput.value.trim();
                if (!newContent && !newLink && (!hasMedia || (removeMediaCheckbox && removeMediaCheckbox.checked)) && fileInput.files.length === 0) {
                    alert('Post cannot be completely empty.');
                    return;
                }
                
                try {
                    saveBtn.textContent = 'Saving...';
                    saveBtn.disabled = true;
                    const formData = new FormData();
                    formData.append('action', 'edit_thought');
                    formData.append('thought_id', thoughtId);
                    formData.append('content', newContent);
                    formData.append('link', newLink);
                    formData.append('privacy', privacySelect.value);
                    if (removeMediaCheckbox && removeMediaCheckbox.checked) {
                        formData.append('remove_media', '1');
                    }
                    for (let i = 0; i < fileInput.files.length; i++) {
                        formData.append('media[]', fileInput.files[i]);
                    }
                    
                    const res = await fetch('/api/thoughts', { method: 'POST', body: formData });
                    const data = await res.json();
                    
                    if (data.success) {
                        thoughtsLoaded = false;
                        if (typeof window.loadUserThoughts === 'function') window.loadUserThoughts();
                    } else {
                        alert(data.message || 'Error editing post.');
                        saveBtn.textContent = 'Save';
                        saveBtn.disabled = false;
                    }
                } catch (err) {
                    alert('Failed to edit post.');
                    saveBtn.textContent = 'Save';
                    saveBtn.disabled = false;
                }
            };
            
            cancelBtn.onclick = () => {
                container.remove();
                displayDiv.style.display = 'block';
            };
            
            actions.appendChild(saveBtn);
            actions.appendChild(cancelBtn);
            container.appendChild(actions);
            
            displayDiv.style.display = 'none';
            displayDiv.parentNode.insertBefore(container, displayDiv.nextSibling);
        };

        window.editComment = function(commentId, thoughtId) {
            const displayDiv = document.getElementById(`comment-content-${commentId}`);
            const rawDiv = document.getElementById(`comment-content-raw-${commentId}`);
            if (document.getElementById(`comment-edit-container-${commentId}`)) return;
            
            const originalText = rawDiv ? rawDiv.textContent : displayDiv.textContent;
            const container = document.createElement('div');
            container.id = `comment-edit-container-${commentId}`;
            container.style.marginTop = '0.5rem';
            
            const input = document.createElement('input');
            input.type = 'text';
            input.value = originalText;
            input.style.width = '100%';
            input.style.padding = '0.5rem';
            input.style.borderRadius = '6px';
            input.style.border = '1px solid #cbd5e1';
            input.style.marginBottom = '0.5rem';
            
            const actions = document.createElement('div');
            actions.style.display = 'flex';
            actions.style.gap = '0.5rem';
            
            const saveBtn = document.createElement('button');
            saveBtn.textContent = 'Save';
            saveBtn.style.padding = '0.2rem 0.75rem';
            saveBtn.style.background = 'var(--green-600)';
            saveBtn.style.color = 'white';
            saveBtn.style.border = 'none';
            saveBtn.style.borderRadius = '4px';
            saveBtn.style.cursor = 'pointer';
            saveBtn.style.fontSize = '0.8rem';
            
            const cancelBtn = document.createElement('button');
            cancelBtn.textContent = 'Cancel';
            cancelBtn.style.padding = '0.2rem 0.75rem';
            cancelBtn.style.background = '#e2e8f0';
            cancelBtn.style.color = '#475569';
            cancelBtn.style.border = 'none';
            cancelBtn.style.borderRadius = '4px';
            cancelBtn.style.cursor = 'pointer';
            cancelBtn.style.fontSize = '0.8rem';
            
            saveBtn.onclick = async () => {
                const newText = input.value.trim();
                if (!newText) return;
                
                try {
                    saveBtn.textContent = '...';
                    saveBtn.disabled = true;
                    const formData = new FormData();
                    formData.append('action', 'edit_comment');
                    formData.append('comment_id', commentId);
                    formData.append('comment', newText);
                    
                    const res = await fetch('/api/thoughts', { method: 'POST', body: formData });
                    const data = await res.json();
                    
                    if (data.success) {
                        container.remove();
                        displayDiv.style.display = 'block';
                        window.loadComments(thoughtId);
                    } else {
                        alert(data.message || 'Error editing comment.');
                        saveBtn.textContent = 'Save';
                        saveBtn.disabled = false;
                    }
                } catch (err) {
                    alert('Failed to edit comment.');
                    saveBtn.textContent = 'Save';
                    saveBtn.disabled = false;
                }
            };
            
            cancelBtn.onclick = () => {
                container.remove();
                displayDiv.style.display = 'block';
            };
            
            actions.appendChild(saveBtn);
            actions.appendChild(cancelBtn);
            container.appendChild(input);
            container.appendChild(actions);
            
            displayDiv.style.display = 'none';
            displayDiv.parentNode.insertBefore(container, displayDiv.nextSibling);
        };

        window.deleteComment = async function(commentId, thoughtId) {
            if (!confirm('Are you sure you want to delete this comment?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_comment');
                formData.append('comment_id', commentId);
                formData.append('thought_id', thoughtId);
                
                const res = await fetch('/api/thoughts', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    window.loadComments(thoughtId);
                    const card = document.getElementById(`comments-section-${thoughtId}`).closest('.thought-card');
                    if (card) {
                        const countSpan = card.querySelector('.comment-count');
                        if (countSpan) countSpan.textContent = data.count;
                    }
                } else {
                    alert(data.message || 'Error deleting comment.');
                }
            } catch (err) {
                alert('Failed to delete comment.');
            }
        }

        window.showReplyForm = function(commentId) {
            const container = document.getElementById(`reply-container-${commentId}`);
            if (container) {
                container.style.display = 'block';
                document.getElementById(`reply-input-${commentId}`).focus();
            }
        };

        window.hideReplyForm = function(commentId) {
            const container = document.getElementById(`reply-container-${commentId}`);
            if (container) {
                container.style.display = 'none';
                document.getElementById(`reply-input-${commentId}`).value = '';
            }
        };

        window.postReply = async function(commentId, thoughtId) {
            const input = document.getElementById(`reply-input-${commentId}`);
            const replyText = input.value.trim();
            if (!replyText) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'post_comment');
                formData.append('thought_id', thoughtId);
                formData.append('comment', replyText);
                formData.append('parent_id', commentId);
                
                const res = await fetch('/api/thoughts', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    input.value = '';
                    window.loadComments(thoughtId);
                    const card = document.getElementById(`comments-section-${thoughtId}`).closest('.thought-card');
                    if (card) {
                        const countSpan = card.querySelector('.comment-count');
                        if (countSpan) countSpan.textContent = data.count;
                    }
                } else {
                    alert(data.message || 'Error posting reply.');
                }
            } catch (err) {
                alert('Failed to post reply.');
            }
        };
        
        // Generate QR Code
        const pageUrl = window.location.href;
        new QRCode(document.getElementById("qrcode"), {
            text: pageUrl,
            width: 140,
            height: 140,
            colorDark: "#0f172a",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // Copy link function
        window.copyProfileLink = function() {
            navigator.clipboard.writeText(pageUrl).then(() => {
                const btn = document.querySelector('.btn-share');
                const originalText = btn.innerHTML;
                btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Copied!`;
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            }).catch(err => {
                alert('Failed to copy link. Please copy from address bar.');
            });
        }

        window.handleConnect = async function(userId) {
            const btn = document.querySelector('.btn-connect');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Sending...';
            btn.disabled = true;

            try {
                const res = await fetch('/api/network?action=connect', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `target_id=${userId}`
                });
                const data = await res.json();
                if (data.success) {
                    btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Requested`;
                    btn.style.background = '#10b981';
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Error sending request');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (err) {
                alert('Connection failed');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        function handleMessage(userId, connStatus) {
            if (connStatus !== 'accepted') {
                alert('Connect first, then you can send a message.');
                return;
            }
            window.location.href = `/messages?chat=${userId}`;
        }

        async function respondConnectionProfile(connId, status, btnElem) {
            const originalText = btnElem.innerHTML;
            btnElem.innerHTML = '...';
            btnElem.disabled = true;

            try {
                const res = await fetch('/api/network?action=respond_connection', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `connection_id=${connId}&response=${status}`
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error responding to request');
                    btnElem.innerHTML = originalText;
                    btnElem.disabled = false;
                }
            } catch (err) {
                alert('Request failed');
                btnElem.innerHTML = originalText;
                btnElem.disabled = false;
            }
        }
        async function handleDisconnect(userId, btnElem) {
            if (!confirm("Are you sure you want to unfriend this user?")) return;

            const originalText = btnElem.innerHTML;
            btnElem.innerHTML = 'Disconnecting...';
            btnElem.disabled = true;

            try {
                const res = await fetch('/api/network?action=disconnect', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `target_id=${userId}`
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error disconnecting');
                    btnElem.innerHTML = originalText;
                    btnElem.disabled = false;
                }
            } catch (err) {
                alert('Request failed');
                btnElem.innerHTML = originalText;
                btnElem.disabled = false;
            }
        }
    </script>

</body>

</html>