<?php

function renderThoughtCard($thought, $currentUserId = null, $isSinglePage = false) {
    if (!defined('THOUGHT_COMPONENT_CSS_ADDED')) {
        define('THOUGHT_COMPONENT_CSS_ADDED', true);
        echo '<style>
        @keyframes cardFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .thought-card {
            opacity: 0;
            animation: cardFadeIn 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @media (max-width: 600px) {
            .comment-input-avatar {
                display: none !important;
            }
            .comment-input-row {
                gap: 0.5rem !important;
            }
            .comment-post-btn {
                padding: 0.5rem 0.75rem !important;
            }
        }
        </style>';
    }
    $avatar = !empty($thought['avatar_path']) 
        ? '<img src="' . htmlspecialchars($thought['avatar_path']) . '" loading="lazy" decoding="async" style="width:100%; height:100%; object-fit:cover;">'
        : '<span>' . strtoupper(substr($thought['full_name'], 0, 1)) . '</span>';
    
    $avatarStyle = empty($thought['avatar_path']) ? 'background:' . htmlspecialchars($thought['avatar_color']) . ';' : 'background:transparent;';
    
    $mediaPaths = !empty($thought['media_paths']) ? json_decode($thought['media_paths'], true) : [];
    
    // Format content with hyperlinks
    $content = htmlspecialchars($thought['content']);
    $content = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" target="_blank" style="color: var(--green-600); text-decoration: underline;">$1</a>', $content);
    
    $linkHtml = '';
    if (!empty($thought['link'])) {
        $linkUrl = htmlspecialchars($thought['link']);
        $linkHtml = '<a href="' . $linkUrl . '" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 0.5rem; background: #f1f5f9; color: var(--green-600); padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 500; margin-bottom: 1rem;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>' . (strlen($linkUrl) > 40 ? substr($linkUrl, 0, 40) . '...' : $linkUrl) . '</a>';
    }
    
    $privacy = $thought['privacy'] ?? 'public';
    $privacyIcon = '';
    if ($privacy === 'public') {
        $privacyIcon = '<span title="Public" style="display: inline-flex; align-items: center; margin-left: 0.4rem; color: var(--gray-500);"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg></span>';
    } elseif ($privacy === 'friends') {
        $privacyIcon = '<span title="Friends Only" style="display: inline-flex; align-items: center; margin-left: 0.4rem; color: var(--gray-500);"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>';
    } else {
        $privacyIcon = '<span title="Private" style="display: inline-flex; align-items: center; margin-left: 0.4rem; color: var(--gray-500);"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></span>';
    }

    $timeAgo = timeAgo($thought['created_at']);
    $thoughtId = $thought['id'];
    
    $isLiked = false;
    if ($currentUserId) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM thought_likes WHERE thought_id = ? AND user_id = ?");
        $stmt->execute([$thoughtId, $currentUserId]);
        $isLiked = (bool)$stmt->fetch();
    }
    
    $likeColor = $isLiked ? 'var(--green-600)' : 'currentColor';
    $likeFill = $isLiked ? 'var(--green-600)' : 'none';
    
    $canDelete = false;
    if ($currentUserId) {
        if ($thought['user_id'] == $currentUserId) {
            $canDelete = true;
        } else {
            $currUser = getCurrentUser();
            if (!empty($currUser['is_admin'])) {
                $canDelete = true;
            }
        }
    }

    echo '<div class="thought-card" data-id="' . $thoughtId . '" style="background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">';
    
    // Header
    echo '<div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem;">';
    echo '<div style="display: flex; align-items: center; gap: 1rem;">';
    echo '<a href="/u/' . htmlspecialchars($thought['profile_slug']) . '" data-no-spa="true" style="display: block; width: 48px; height: 48px; border-radius: 50%; overflow: hidden; flex-shrink: 0; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; text-decoration:none; outline:none; ' . $avatarStyle . '">' . $avatar . '</a>';
    echo '<div>';
    echo '<a href="/u/' . htmlspecialchars($thought['profile_slug']) . '" data-no-spa="true" style="text-decoration: none; color: var(--gray-900); font-weight: 700; font-size: 1.05rem;">' . htmlspecialchars($thought['full_name']) . '</a>';
    echo '<a href="/p/' . $thoughtId . '" data-no-spa="true" style="font-size: 0.8rem; color: var(--gray-500); text-decoration: none; display: flex; align-items: center;">' . timeAgo($thought['created_at']) . $privacyIcon . '</a>';
    echo '</div>';
    echo '</div>';
    
    if ($canDelete) {
        echo '<div style="display: flex; gap: 0.5rem; align-items: center;">';
        if ($thought['user_id'] == $currentUserId) {
            echo '<button onclick="editThought(' . $thoughtId . ')" style="background: none; border: none; color: var(--gray-500); cursor: pointer; padding: 0.25rem; transition: opacity 0.2s; opacity: 0.7;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.7" title="Edit Post"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>';
        }
        echo '<button onclick="deleteThought(' . $thoughtId . ')" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0.25rem; transition: opacity 0.2s; opacity: 0.7;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.7" title="Delete Post"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg></button>';
        echo '</div>';
    }
    echo '</div>';
    
    // Content
    echo '<div id="thought-content-display-' . $thoughtId . '" onclick="window.location.href=\'/p/' . $thoughtId . '\';" style="font-size: 1rem; color: var(--gray-900); line-height: 1.6; margin-bottom: 1rem; white-space: pre-wrap; word-break: break-word; cursor: pointer;">' . $content . '</div>';
    echo '<div id="thought-content-raw-' . $thoughtId . '" style="display:none;">' . htmlspecialchars($thought['content']) . '</div>';
    echo '<div id="thought-link-raw-' . $thoughtId . '" style="display:none;">' . htmlspecialchars($thought['link'] ?? '') . '</div>';
    echo '<div id="thought-privacy-raw-' . $thoughtId . '" style="display:none;">' . htmlspecialchars($privacy) . '</div>';
    echo '<div id="thought-has-media-' . $thoughtId . '" style="display:none;">' . (!empty($thought['media_paths']) && $thought['media_paths'] !== 'null' ? '1' : '0') . '</div>';
    echo $linkHtml;
    
    // Media
    if (!empty($mediaPaths)) {
        $visualMedia = [];
        $otherMedia = [];
        foreach ($mediaPaths as $media) {
            $ext = strtolower(pathinfo($media, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg'])) {
                $visualMedia[] = $media;
            } else {
                $otherMedia[] = $media;
            }
        }

        if (!empty($visualMedia)) {
            $count = count($visualMedia);
            if ($isSinglePage || $count === 1) {
                // Show all fully
                echo '<div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem; margin-bottom: 1rem;">';
                foreach ($visualMedia as $media) {
                    $ext = strtolower(pathinfo($media, PATHINFO_EXTENSION));
                    $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                    
                    echo '<div style="border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc; max-height: 600px; display: flex; justify-content: center; align-items: center;">';
                    if ($isImg) {
                        echo '<img src="' . htmlspecialchars($media) . '" loading="lazy" decoding="async" style="width: 100%; height: auto; max-height: 600px; object-fit: contain; cursor: pointer;" onclick="window.open(this.src, \'_blank\')">';
                    } else {
                        echo '<video src="' . htmlspecialchars($media) . '" controls style="width: 100%; height: auto; max-height: 600px; object-fit: contain; outline: none;"></video>';
                    }
                    echo '</div>';
                }
                echo '</div>';
            } else {
                // Smart Grid / Collage Layout
                // Make the entire collage block clickable to open the single post page
                echo '<div onclick="window.location.href=\'/p/' . $thoughtId . '\';" style="cursor: pointer; margin-top: 1rem; margin-bottom: 1rem; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc; display: flex; flex-direction: column; gap: 4px; position: relative;">';
                
                if ($count === 2) {
                    // 2 columns
                    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; height: 280px;">';
                    foreach (array_slice($visualMedia, 0, 2) as $media) {
                        renderCollageItem($media);
                    }
                    echo '</div>';
                } else if ($count === 3) {
                    // 1 big on left, 2 stacked on right
                    echo '<div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 4px; height: 340px;">';
                    // Left item (1st selected)
                    renderCollageItem($visualMedia[0]);
                    // Right items: rendered anti-clockwise (Bottom-right gets 2nd, Top-right gets 3rd)
                    echo '<div style="display: grid; grid-template-rows: 1fr 1fr; gap: 4px; height: 100%;">';
                    renderCollageItem($visualMedia[2]); // Top-right (3rd selected)
                    renderCollageItem($visualMedia[1]); // Bottom-right (2nd selected)
                    echo '</div>';
                    echo '</div>';
                } else {
                    // 4 or more items: 1 big on top, 3 smaller on bottom
                    echo '<div style="display: flex; flex-direction: column; gap: 4px; height: 380px;">';
                    // Top item
                    echo '<div style="flex: 1.5; min-height: 0; position: relative;">';
                    renderCollageItem($visualMedia[0]);
                    echo '</div>';
                    // Bottom row
                    echo '<div style="flex: 1; min-height: 0; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 4px;">';
                    renderCollageItem($visualMedia[1]);
                    renderCollageItem($visualMedia[2]);
                    
                    // For the last slot (3rd item of the row / 4th item overall)
                    echo '<div style="position: relative; height: 100%; width: 100%;">';
                    renderCollageItem($visualMedia[3]);
                    if ($count > 4) {
                        $remaining = $count - 3;
                        echo '<div style="position: absolute; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.8rem; font-weight: 700; pointer-events: none;">+' . $remaining . '</div>';
                    }
                    echo '</div>';
                    
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
            }
        }

        // Other non-visual media (PDF, ZIP, etc.)
        if (!empty($otherMedia)) {
            echo '<div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem; margin-bottom: 1rem;">';
            foreach ($otherMedia as $media) {
                echo '<div style="align-self: start; min-width: 0; width: 100%;">';
                echo '<a href="' . htmlspecialchars($media) . '" target="_blank" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #f1f5f9; border-radius: 8px; text-decoration: none; color: var(--gray-900); font-weight: 500; min-width: 0; box-sizing: border-box; width: 100%; overflow: hidden;">';
                echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>';
                echo '<span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; flex: 1; min-width: 0;">' . htmlspecialchars(basename($media)) . '</span>';
                echo '</a></div>';
            }
            echo '</div>';
        }
    }
    
    // Action bar
    echo '<div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 1rem; margin-top: 1rem;">';
    
    echo '<div style="display: flex; gap: 1.5rem;">';
    
    // Like button
    echo '<button onclick="toggleThoughtLike(' . $thoughtId . ', this)" style="display: flex; align-items: center; gap: 0.5rem; background: none; border: none; color: var(--gray-500); font-size: 0.95rem; font-weight: 500; cursor: pointer; transition: color 0.2s;">';
    echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="' . $likeFill . '" stroke="' . $likeColor . '" stroke-width="2" style="transition: all 0.2s;"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>';
    echo '<span class="like-count">' . number_format($thought['likes_count']) . '</span>';
    echo '</button>';
    
    // Comment button
    echo '<button onclick="toggleComments(' . $thoughtId . ')" style="display: flex; align-items: center; gap: 0.5rem; background: none; border: none; color: var(--gray-500); font-size: 0.95rem; font-weight: 500; cursor: pointer; transition: color 0.2s;">';
    echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>';
    echo '<span class="comment-count">' . number_format($thought['comments_count']) . '</span>';
    echo '</button>';
    
    // Share button
    echo '<button onclick="shareThought(' . $thoughtId . ')" style="display: flex; align-items: center; gap: 0.5rem; background: none; border: none; color: var(--gray-500); font-size: 0.95rem; font-weight: 500; cursor: pointer; transition: color 0.2s;">';
    echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>';
    echo '<span class="share-count">' . number_format($thought['shares_count']) . '</span>';
    echo '</button>';
    
    echo '</div>'; // End left flex
    
    // View count
    echo '<div style="display: flex; align-items: center; gap: 0.5rem; color: var(--gray-500); font-size: 0.9rem; font-weight: 500;" title="Views">';
    echo '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    echo '<span>' . number_format($thought['views'] ?? 0) . '</span>';
    echo '</div>';
    
    echo '</div>'; // End action bar
    
    // Comments section (hidden by default)
    echo '<div id="comments-section-' . $thoughtId . '" style="display: none; margin-top: 1.5rem; border-top: 1px solid #e2e8f0; padding-top: 1.5rem;">';
    echo '<div id="comments-list-' . $thoughtId . '"></div>';
    
    if ($currentUserId) {
        echo '<div class="comment-input-row" style="display: flex; gap: 1rem; margin-top: 1rem; width: 100%; box-sizing: border-box;">';
        echo '<div class="comment-input-avatar" style="flex-shrink:0; width:36px; height:36px; border-radius:50%; background:var(--green-600); color:white; display:flex; align-items:center; justify-content:center; font-weight:bold;">Me</div>';
        echo '<input type="text" id="comment-input-' . $thoughtId . '" placeholder="Write a comment..." style="flex: 1; border: 1px solid #cbd5e1; border-radius: 9999px; padding: 0.5rem 1rem; outline: none; font-family: inherit; min-width: 0;">';
        echo '<button class="comment-post-btn" onclick="postComment(' . $thoughtId . ')" style="background: var(--green-600); color: white; border: none; border-radius: 9999px; padding: 0.5rem 1rem; font-weight: 600; cursor: pointer; flex-shrink: 0;">Post</button>';
        echo '</div>';
    } else {
        echo '<div style="text-align: center; color: var(--gray-500); margin-top: 1rem;">Please <a href="/login" style="color: var(--green-600);">log in</a> to comment.</div>';
    }
    echo '</div>';
    
    echo '</div>'; // End thought card
}

if (!function_exists('renderCollageItem')) {
    function renderCollageItem($media) {
        $ext = strtolower(pathinfo($media, PATHINFO_EXTENSION));
        $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        
        echo '<div style="width: 100%; height: 100%; overflow: hidden; background: #cbd5e1; position: relative;">';
        if ($isImg) {
            echo '<img src="' . htmlspecialchars($media) . '" loading="lazy" decoding="async" style="width: 100%; height: 100%; object-fit: cover; display: block;">';
        } else {
            echo '<video src="' . htmlspecialchars($media) . '" style="width: 100%; height: 100%; object-fit: cover; display: block; pointer-events: none;" muted></video>';
            echo '<div style="position: absolute; top: 0.5rem; right: 0.5rem; background: rgba(0,0,0,0.6); border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; color: white;"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg></div>';
        }
        echo '</div>';
    }
}
?>
