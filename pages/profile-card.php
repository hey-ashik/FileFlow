<?php
// Ensure functions are available
if (!function_exists('incrementProfileVisits')) {
    require_once __DIR__ . '/../includes/functions.php';
}

$db = getDB();

try {
    $stmt = $db->prepare("SELECT full_name, email, avatar_path, avatar_color, cover_path, phone, work_experience, social_links, cv_path, cv_description, cv_button_color, profile_visits FROM users WHERE profile_slug = ? AND is_active = 1");
    $stmt->execute([$profileSlug]);
    $userProfile = $stmt->fetch();
} catch (PDOException $e) {
    // Fallback if profile_visits column doesn't exist yet
    $stmt = $db->prepare("SELECT full_name, email, avatar_path, avatar_color, cover_path, phone, work_experience, social_links, cv_path, cv_description, cv_button_color FROM users WHERE profile_slug = ? AND is_active = 1");
    $stmt->execute([$profileSlug]);
    $userProfile = $stmt->fetch();
    if ($userProfile)
        $userProfile['profile_visits'] = 0;
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
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
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
            justify-content: center;
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
    </style>
</head>

<body>

    <div class="profile-container">
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
                            <div class="section-content" style="margin-bottom: 1rem;">
                                <?php echo htmlspecialchars($userProfile['cv_description']); ?>
                            </div>
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
            </div>
        </div>

        <div class="footer-branding"
            style="display: flex; justify-content: right; align-items: center; gap: 10px; font-size: 0.8rem; color: var(--text-muted); margin-top: 2rem;">
            <div>Powered by <a href="/"
                    style="color: var(--primary); text-decoration: none; font-weight: 600;">FileFlow</a></div>
            <div style="color: #e2e8f0;">|</div>
            <div style="display: flex; align-items: center; gap: 5px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted);">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <span>Views: <?php echo number_format($userProfile['profile_visits'] ?? 0); ?></span>
            </div>
        </div>
    </div>

    <script>
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
        function copyProfileLink() {
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
    </script>

</body>

</html>