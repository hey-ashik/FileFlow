<?php
// api/thoughts.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/thought_component.php';
require_once __DIR__ . '/../includes/redis.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDB();

$currentUserId = null;
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    $currentUserId = $_SESSION['user_id'];
}

// Auto-migrate parent_id for nested comments if missing
try {
    $db->query("SELECT parent_id FROM thought_comments LIMIT 1");
} catch (PDOException $e) {
    try {
        $db->exec("ALTER TABLE thought_comments ADD COLUMN parent_id BIGINT UNSIGNED DEFAULT NULL");
        $db->exec("ALTER TABLE thought_comments ADD FOREIGN KEY (parent_id) REFERENCES thought_comments(id) ON DELETE CASCADE");
    } catch (Exception $ex) {}
}

// Auto-migrate privacy for thoughts if missing
try {
    $db->query("SELECT privacy FROM thoughts LIMIT 1");
} catch (PDOException $e) {
    try {
        $db->exec("ALTER TABLE thoughts ADD COLUMN privacy ENUM('public', 'friends', 'private') NOT NULL DEFAULT 'public'");
    } catch (Exception $ex) {}
}

$friends = [];
if ($currentUserId) {
    $stmt = $db->prepare("SELECT requester_id, receiver_id FROM connections WHERE status = 'accepted' AND (requester_id = ? OR receiver_id = ?)");
    $stmt->execute([$currentUserId, $currentUserId]);
    foreach ($stmt->fetchAll() as $conn) {
        $friends[] = $conn['requester_id'] == $currentUserId ? $conn['receiver_id'] : $conn['requester_id'];
    }
}
$friendsList = !empty($friends) ? implode(',', $friends) : '0';

if ($action === 'get_user_thoughts') {
    $userId = (int)($_GET['user_id'] ?? 0);
    
    $privacyCondition = "t.privacy = 'public'";
    if ($currentUserId) {
        if ($userId == $currentUserId) {
            $privacyCondition = "1=1"; // user can see all their own thoughts
        } else {
            $isFriend = in_array($userId, $friends) ? '1' : '0';
            $privacyCondition = "(t.privacy = 'public' OR (t.privacy = 'friends' AND 1 = $isFriend))";
        }
    }
    
    $cache = RedisCache::getInstance();
    $userVersion = (int)$cache->get("thoughts:user:version:{$userId}") ?: 1;
    $cacheKey = "thoughts:user:{$userId}:version:{$userVersion}:viewer:" . ($currentUserId ?: 0);
    
    $thoughts = $cache->get($cacheKey);
    if ($thoughts === null) {
        $stmt = $db->prepare("SELECT t.*, u.full_name, u.avatar_path, u.avatar_color, u.profile_slug, (u.is_verified OR u.is_admin) as is_verified,
            (SELECT COUNT(*) FROM thought_likes WHERE thought_id = t.id) as likes_count,
            (SELECT COUNT(*) FROM thought_comments WHERE thought_id = t.id) as comments_count,
            (SELECT COUNT(*) FROM thought_shares WHERE thought_id = t.id) as shares_count
            FROM thoughts t 
            JOIN users u ON t.user_id = u.id 
            WHERE t.user_id = ? AND $privacyCondition
            ORDER BY t.created_at DESC");
        $stmt->execute([$userId]);
        $thoughts = $stmt->fetchAll();
        
        $cache->set($cacheKey, $thoughts, 600);
    }

    if (empty($thoughts)) {
        echo '<div style="padding: 2rem; text-align: center; color: var(--gray-500);">No thoughts shared yet.</div>';
        exit;
    }

    foreach ($thoughts as $thought) {
        renderThoughtCard($thought, $currentUserId);
    }
    exit;
}

if ($action === 'get_feed_thoughts') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    // Increment view count randomly just for showing it's a feed. But wait, views should be incremented on load?
    // The requirement says: "all user can see the total views of the post as well as. to under and the reach of the post."
    // Let's just increment views for all posts that are fetched.
    $privacyCondition = "t.privacy = 'public'";
    if ($currentUserId) {
        $privacyCondition = "(t.privacy = 'public' OR (t.privacy = 'friends' AND t.user_id IN ($friendsList)) OR t.user_id = $currentUserId)";
    }
    
    $searchCondition = "1=1";
    $params = [];
    if (!empty($search)) {
        $searchCondition = "(t.content LIKE ? OR u.full_name LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    
    $cache = RedisCache::getInstance();
    $feedVersion = (int)$cache->get("thoughts:feed:version") ?: 1;
    $cacheKey = "thoughts:feed:version:{$feedVersion}:user:" . ($currentUserId ?: 0) . ":search:" . md5($search);
    
    $thoughts = $cache->get($cacheKey);
    if ($thoughts === null) {
        $stmt = $db->prepare("SELECT t.*, u.full_name, u.avatar_path, u.avatar_color, u.profile_slug, (u.is_verified OR u.is_admin) as is_verified,
            (SELECT COUNT(*) FROM thought_likes WHERE thought_id = t.id) as likes_count,
            (SELECT COUNT(*) FROM thought_comments WHERE thought_id = t.id) as comments_count,
            (SELECT COUNT(*) FROM thought_shares WHERE thought_id = t.id) as shares_count
            FROM thoughts t 
            JOIN users u ON t.user_id = u.id 
            WHERE ($privacyCondition) AND ($searchCondition)
            ORDER BY t.created_at DESC LIMIT 50");
        $stmt->execute($params);
        $thoughts = $stmt->fetchAll();
        
        $cache->set($cacheKey, $thoughts, 600);
    }
    
    if (!empty($thoughts)) {
        $ids = array_column($thoughts, 'id');
        $idList = implode(',', $ids);
        $db->query("UPDATE thoughts SET views = views + 1 WHERE id IN ($idList)");
    }

    if (empty($thoughts)) {
        echo '<div style="padding: 2rem; text-align: center; color: var(--gray-500);">No thoughts in the feed yet. Be the first to post!</div>';
        exit;
    }

    foreach ($thoughts as $thought) {
        renderThoughtCard($thought, $currentUserId);
    }
    exit;
}

if ($action === 'create_thought') {
    if (!$currentUserId) {
        jsonResponse(['success' => false, 'message' => 'Please log in to post.']);
    }
    
    $content = trim($_POST['content'] ?? '');
    $link = trim($_POST['link'] ?? '');
    
    if (empty($content) && empty($link) && empty($_FILES['media']['name'][0])) {
        jsonResponse(['success' => false, 'message' => 'Cannot create an empty post.']);
    }
    
    $mediaPaths = [];
    if (!empty($_FILES['media']['name'][0])) {
        $uniqueSubdir = 'thought_' . uniqid() . '_' . time();
        $thoughtsDir = __DIR__ . '/../uploads/thoughts/' . $uniqueSubdir . '/';
        if (!is_dir($thoughtsDir)) {
            mkdir($thoughtsDir, 0755, true);
        }
        foreach ($_FILES['media']['name'] as $key => $name) {
            if ($_FILES['media']['error'][$key] === UPLOAD_ERR_OK) {
                if ($_FILES['media']['size'][$key] > 10 * 1024 * 1024) {
                    jsonResponse(['success' => false, 'message' => 'Files must be 10MB or less.']);
                }
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg', 'pdf', 'zip'];
                if (!in_array($ext, $allowed)) {
                    jsonResponse(['success' => false, 'message' => 'Unsupported file type.']);
                }
                $originalName = basename($name);
                $targetPath = $thoughtsDir . $originalName;
                if (move_uploaded_file($_FILES['media']['tmp_name'][$key], $targetPath)) {
                    $mediaPaths[] = '/uploads/thoughts/' . $uniqueSubdir . '/' . $originalName;
                }
            }
        }
    }
    
    $mediaPathsJson = !empty($mediaPaths) ? json_encode($mediaPaths) : null;
    $privacy = $_POST['privacy'] ?? 'public';
    if (!in_array($privacy, ['public', 'friends', 'private'])) $privacy = 'public';
    
    $stmt = $db->prepare("INSERT INTO thoughts (user_id, content, media_paths, link, privacy) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$currentUserId, $content, $mediaPathsJson, $link, $privacy]);
    
    clearUserCache($currentUserId);
    
    jsonResponse(['success' => true]);
}

if ($action === 'toggle_like') {
    if (!$currentUserId) {
        jsonResponse(['success' => false, 'message' => 'Please log in to like posts.']);
    }
    
    $thoughtId = (int)($_POST['thought_id'] ?? 0);
    
    $stmt = $db->prepare("SELECT id FROM thought_likes WHERE thought_id = ? AND user_id = ?");
    $stmt->execute([$thoughtId, $currentUserId]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        $db->prepare("DELETE FROM thought_likes WHERE id = ?")->execute([$exists['id']]);
        $liked = false;
    } else {
        $db->prepare("INSERT INTO thought_likes (thought_id, user_id) VALUES (?, ?)")->execute([$thoughtId, $currentUserId]);
        $liked = true;
    }
    
    $count = $db->prepare("SELECT COUNT(*) FROM thought_likes WHERE thought_id = ?");
    $count->execute([$thoughtId]);
    $likesCount = $count->fetchColumn();
    
    // Invalidate caches
    $cache = RedisCache::getInstance();
    $feedVersion = (int)$cache->get("thoughts:feed:version") ?: 1;
    $cache->set("thoughts:feed:version", $feedVersion + 1);
    
    $stmtOwner = $db->prepare("SELECT user_id FROM thoughts WHERE id = ?");
    $stmtOwner->execute([$thoughtId]);
    $ownerId = $stmtOwner->fetchColumn();
    if ($ownerId) {
        $userVersion = (int)$cache->get("thoughts:user:version:{$ownerId}") ?: 1;
        $cache->set("thoughts:user:version:{$ownerId}", $userVersion + 1);
    }
    
    jsonResponse(['success' => true, 'liked' => $liked, 'count' => $likesCount]);
}

if ($action === 'delete_thought') {
    if (!$currentUserId) {
        jsonResponse(['success' => false, 'message' => 'Please log in to delete posts.']);
    }
    
    $thoughtId = (int)($_POST['thought_id'] ?? 0);
    
    $stmt = $db->prepare("SELECT user_id, media_paths FROM thoughts WHERE id = ?");
    $stmt->execute([$thoughtId]);
    $thought = $stmt->fetch();
    
    if (!$thought) {
        jsonResponse(['success' => false, 'message' => 'Thought not found.']);
    }
    
    $currUser = getCurrentUser();
    $isAdmin = !empty($currUser['is_admin']);
    
    if ($thought['user_id'] != $currentUserId && !$isAdmin) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized to delete this post.']);
    }
    
    // Delete media files if exist
    if (!empty($thought['media_paths'])) {
        $paths = json_decode($thought['media_paths'], true);
        if (is_array($paths)) {
            foreach ($paths as $path) {
                $filePath = __DIR__ . '/..' . $path;
                if (file_exists($filePath)) {
                    unlink($filePath);
                    $dirPath = dirname($filePath);
                    if (basename($dirPath) !== 'thoughts' && is_dir($dirPath)) {
                        @rmdir($dirPath);
                    }
                }
            }
        }
    }
    
    $db->prepare("DELETE FROM thoughts WHERE id = ?")->execute([$thoughtId]);
    
    // Invalidate caches
    $cache = RedisCache::getInstance();
    $feedVersion = (int)$cache->get("thoughts:feed:version") ?: 1;
    $cache->set("thoughts:feed:version", $feedVersion + 1);
    
    $ownerId = intval($thought['user_id']);
    $userVersion = (int)$cache->get("thoughts:user:version:{$ownerId}") ?: 1;
    $cache->set("thoughts:user:version:{$ownerId}", $userVersion + 1);
    
    jsonResponse(['success' => true]);
}

if ($action === 'edit_thought') {
    if (!$currentUserId) {
        jsonResponse(['success' => false, 'message' => 'Please log in to edit posts.']);
    }
    
    $thoughtId = (int)($_POST['thought_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $removeMedia = !empty($_POST['remove_media']);
    
    $stmt = $db->prepare("SELECT user_id, media_paths FROM thoughts WHERE id = ?");
    $stmt->execute([$thoughtId]);
    $thought = $stmt->fetch();
    
    if (!$thought) {
        jsonResponse(['success' => false, 'message' => 'Thought not found.']);
    }
    
    if ($thought['user_id'] != $currentUserId) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized to edit this post.']);
    }
    
    $mediaPaths = [];
    if (!empty($thought['media_paths']) && $thought['media_paths'] !== 'null') {
        $mediaPaths = json_decode($thought['media_paths'], true) ?: [];
    }
    
    if ($removeMedia) {
        foreach ($mediaPaths as $path) {
            $filePath = __DIR__ . '/..' . $path;
            if (file_exists($filePath)) {
                unlink($filePath);
                $dirPath = dirname($filePath);
                if (basename($dirPath) !== 'thoughts' && is_dir($dirPath)) {
                    @rmdir($dirPath);
                }
            }
        }
        $mediaPaths = [];
    }
    
    if (!empty($_FILES['media']['name'][0])) {
        // user uploaded new media, replace the old media
        if (!$removeMedia) {
            foreach ($mediaPaths as $path) {
                $filePath = __DIR__ . '/..' . $path;
                if (file_exists($filePath)) {
                    unlink($filePath);
                    $dirPath = dirname($filePath);
                    if (basename($dirPath) !== 'thoughts' && is_dir($dirPath)) {
                        @rmdir($dirPath);
                    }
                }
            }
        }
        $mediaPaths = [];
        $uniqueSubdir = 'thought_' . uniqid() . '_' . time();
        $thoughtsDir = __DIR__ . '/../uploads/thoughts/' . $uniqueSubdir . '/';
        if (!is_dir($thoughtsDir)) {
            mkdir($thoughtsDir, 0755, true);
        }
        foreach ($_FILES['media']['name'] as $key => $name) {
            if ($_FILES['media']['error'][$key] === UPLOAD_ERR_OK) {
                if ($_FILES['media']['size'][$key] > 10 * 1024 * 1024) {
                    jsonResponse(['success' => false, 'message' => 'Files must be 10MB or less.']);
                }
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg', 'pdf', 'zip'];
                if (!in_array($ext, $allowed)) {
                    jsonResponse(['success' => false, 'message' => 'Unsupported file type.']);
                }
                $originalName = basename($name);
                $targetPath = $thoughtsDir . $originalName;
                if (move_uploaded_file($_FILES['media']['tmp_name'][$key], $targetPath)) {
                    $mediaPaths[] = '/uploads/thoughts/' . $uniqueSubdir . '/' . $originalName;
                }
            }
        }
    }
    
    if (empty($content) && empty($link) && empty($mediaPaths)) {
        jsonResponse(['success' => false, 'message' => 'Post cannot be empty.']);
    }
    
    $mediaPathsJson = !empty($mediaPaths) ? json_encode($mediaPaths) : null;
    $privacy = $_POST['privacy'] ?? 'public';
    if (!in_array($privacy, ['public', 'friends', 'private'])) $privacy = 'public';
    
    $db->prepare("UPDATE thoughts SET content = ?, link = ?, media_paths = ?, privacy = ? WHERE id = ?")->execute([$content, $link, $mediaPathsJson, $privacy, $thoughtId]);
    
    // Invalidate caches
    $cache = RedisCache::getInstance();
    $feedVersion = (int)$cache->get("thoughts:feed:version") ?: 1;
    $cache->set("thoughts:feed:version", $feedVersion + 1);
    
    $ownerId = intval($thought['user_id']);
    $userVersion = (int)$cache->get("thoughts:user:version:{$ownerId}") ?: 1;
    $cache->set("thoughts:user:version:{$ownerId}", $userVersion + 1);
    
    jsonResponse(['success' => true]);
}

if ($action === 'get_comments') {
    $thoughtId = (int)($_GET['thought_id'] ?? 0);
    
    $stmt = $db->prepare("SELECT c.*, u.full_name, u.avatar_path, u.avatar_color, u.profile_slug, (u.is_verified OR u.is_admin) as is_verified 
        FROM thought_comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.thought_id = ? 
        ORDER BY c.created_at ASC");
    $stmt->execute([$thoughtId]);
    $comments = $stmt->fetchAll();
    
    if (empty($comments)) {
        echo '<div style="color: var(--gray-500); font-size: 0.9rem; text-align: center; margin-bottom: 1rem;">No comments yet.</div>';
        exit;
    }
    
    $commentsByParent = ['root' => []];
    foreach ($comments as $comment) {
        $parentId = $comment['parent_id'] ?: 'root';
        $commentsByParent[$parentId][] = $comment;
    }
    
    function renderCommentTree($parentId, $commentsByParent, $currentUserId, $thoughtId, $level = 0) {
        if (!isset($commentsByParent[$parentId])) return;
        
        foreach ($commentsByParent[$parentId] as $comment) {
            $avatar = !empty($comment['avatar_path']) 
                ? '<img src="' . htmlspecialchars($comment['avatar_path']) . '" style="width:100%; height:100%; object-fit:cover;">'
                : '<span>' . strtoupper(substr($comment['full_name'], 0, 1)) . '</span>';
            
            $avatarStyle = empty($comment['avatar_path']) ? 'background:' . htmlspecialchars($comment['avatar_color']) . ';' : 'background:transparent;';
            
            $marginLeft = $level > 0 ? 'margin-left: ' . min($level * 3, 6) . 'rem; border-left: 2px solid #e2e8f0; padding-left: 1rem;' : '';
            
            echo '<div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; ' . $marginLeft . '">';
            echo '<a href="/u/' . htmlspecialchars($comment['profile_slug']) . '" data-no-spa="true" style="display: block; width: 32px; height: 32px; border-radius: 50%; overflow: hidden; flex-shrink: 0; display:flex; align-items:center; justify-content:center; color:white; font-size:0.8rem; font-weight:bold; text-decoration:none; outline:none; ' . $avatarStyle . '">' . $avatar . '</a>';
            echo '<div style="background: #f1f5f9; border-radius: 12px; padding: 0.75rem 1rem; flex: 1; position: relative;">';
            echo '<a href="/u/' . htmlspecialchars($comment['profile_slug']) . '" data-no-spa="true" style="text-decoration: none; color: var(--gray-900); font-weight: 700; font-size: 0.9rem; margin-right: 0.5rem; outline:none;">' . htmlspecialchars($comment['full_name']) . getVerifiedBadgeHtml($comment['is_verified'] ?? 0) . '</a>';
            echo '<span style="font-size: 0.75rem; color: var(--gray-500);">' . timeAgo($comment['created_at']) . '</span>';
            
            $canEditComment = $currentUserId && ($comment['user_id'] == $currentUserId);
            $isAdmin = false;
            if ($currentUserId && isset($_SESSION['is_admin'])) $isAdmin = $_SESSION['is_admin'];
            $canDeleteComment = $canEditComment || $isAdmin;
            
            $commentContent = htmlspecialchars($comment['comment']);
            $commentContent = preg_replace('/&lt;u&gt;(.*?)&lt;\/u&gt;/is', '<u>$1</u>', $commentContent);
            $commentContent = preg_replace('/&lt;span\s+style=&quot;font-family:\s*(.*?);?&quot;&gt;(.*?)&lt;\/span&gt;/is', '<span style="font-family: $1;">$2</span>', $commentContent);
            $commentContent = preg_replace('/\[(.*?)\]\((.*?)\)/s', '<a href="$2" target="_blank" style="color: var(--green-600); text-decoration: underline;">$1</a>', $commentContent);
            $commentContent = preg_replace('/(?<!href=")(?<!href=&quot;)(?<!=")(?<!=&quot;)(https?:\/\/[^\s\)<>"\']+)/', '<a href="$1" target="_blank" style="color: var(--green-600); text-decoration: underline;">$1</a>', $commentContent);
            $commentContent = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $commentContent);
            $commentContent = preg_replace('/\*([^\*]+)\*/s', '<em>$1</em>', $commentContent);

            echo '<div id="comment-content-' . $comment['id'] . '" style="font-size: 0.95rem; color: var(--gray-900); margin-top: 0.25rem; white-space:pre-wrap; word-break: break-word;">' . $commentContent . '</div>';
            echo '<div id="comment-content-raw-' . $comment['id'] . '" style="display:none;">' . htmlspecialchars($comment['comment']) . '</div>';
            
            echo '<div style="display: flex; gap: 1rem; margin-top: 0.5rem; align-items: center;">';
            if ($currentUserId) {
                echo '<button onclick="showReplyForm(' . $comment['id'] . ', ' . $thoughtId . ')" style="background: none; border: none; color: var(--green-600); cursor: pointer; padding: 0; font-weight:600; font-size:0.75rem;" title="Reply">Reply</button>';
            }
            if ($canEditComment || $canDeleteComment) {
                if ($canEditComment) {
                    echo '<button onclick="editComment(' . $comment['id'] . ', ' . $thoughtId . ')" style="background: none; border: none; color: var(--gray-600); cursor: pointer; padding: 0; font-weight:600; font-size:0.75rem;" title="Edit">Edit</button>';
                }
                if ($canDeleteComment) {
                    echo '<button onclick="deleteComment(' . $comment['id'] . ', ' . $thoughtId . ')" style="background: none; border: none; color: var(--gray-600); cursor: pointer; padding: 0; font-weight:600; font-size:0.75rem;" title="Delete">Delete</button>';
                }
            }
            echo '</div>';
            
            // Reply Form Container
            echo '<div id="reply-container-' . $comment['id'] . '" style="display:none; margin-top:0.5rem; gap:0.5rem;">';
            echo '<input type="text" id="reply-input-' . $comment['id'] . '" placeholder="Write a reply..." style="flex:1; padding:0.5rem; border-radius:6px; border:1px solid #cbd5e1; width:100%; box-sizing:border-box;">';
            echo '<div style="display:flex; gap:0.5rem; margin-top:0.5rem;">';
            echo '<button onclick="postReply(' . $comment['id'] . ', ' . $thoughtId . ')" style="padding:0.3rem 0.75rem; background:var(--green-600); color:white; border:none; border-radius:4px; font-size:0.8rem; cursor:pointer;">Post Reply</button>';
            echo '<button onclick="hideReplyForm(' . $comment['id'] . ')" style="padding:0.3rem 0.75rem; background:#e2e8f0; color:#475569; border:none; border-radius:4px; font-size:0.8rem; cursor:pointer;">Cancel</button>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
            echo '</div>';
            
            renderCommentTree($comment['id'], $commentsByParent, $currentUserId, $thoughtId, $level + 1);
        }
    }
    
    renderCommentTree('root', $commentsByParent, $currentUserId, $thoughtId);
    
    exit;
}

if ($action === 'post_comment') {
    if (!$currentUserId) {
        jsonResponse(['success' => false, 'message' => 'Please log in to comment.']);
    }
    
    $thoughtId = (int)($_POST['thought_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    
    if (empty($comment)) {
        jsonResponse(['success' => false, 'message' => 'Comment cannot be empty.']);
    }
    
    $stmt = $db->prepare("INSERT INTO thought_comments (thought_id, user_id, comment, parent_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$thoughtId, $currentUserId, $comment, $parentId]);
    
    $count = $db->prepare("SELECT COUNT(*) FROM thought_comments WHERE thought_id = ?");
    $count->execute([$thoughtId]);
    $commentsCount = $count->fetchColumn();
    
    // Invalidate caches
    $cache = RedisCache::getInstance();
    $feedVersion = (int)$cache->get("thoughts:feed:version") ?: 1;
    $cache->set("thoughts:feed:version", $feedVersion + 1);
    
    $stmtOwner = $db->prepare("SELECT user_id FROM thoughts WHERE id = ?");
    $stmtOwner->execute([$thoughtId]);
    $ownerId = $stmtOwner->fetchColumn();
    if ($ownerId) {
        $userVersion = (int)$cache->get("thoughts:user:version:{$ownerId}") ?: 1;
        $cache->set("thoughts:user:version:{$ownerId}", $userVersion + 1);
    }
    
    jsonResponse(['success' => true, 'count' => $commentsCount]);
}

if ($action === 'edit_comment') {
    if (!$currentUserId) {
        jsonResponse(['success' => false, 'message' => 'Please log in to edit comments.']);
    }
    
    $commentId = (int)($_POST['comment_id'] ?? 0);
    $commentText = trim($_POST['comment'] ?? '');
    
    if (empty($commentText)) {
        jsonResponse(['success' => false, 'message' => 'Comment cannot be empty.']);
    }
    
    $stmt = $db->prepare("SELECT user_id, thought_id FROM thought_comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $commentData = $stmt->fetch();
    
    if (!$commentData || $commentData['user_id'] != $currentUserId) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized to edit this comment.']);
    }
    
    $db->prepare("UPDATE thought_comments SET comment = ? WHERE id = ?")->execute([$commentText, $commentId]);
    
    // Invalidate caches
    $cache = RedisCache::getInstance();
    $feedVersion = (int)$cache->get("thoughts:feed:version") ?: 1;
    $cache->set("thoughts:feed:version", $feedVersion + 1);
    
    $thoughtId = $commentData['thought_id'];
    $stmtOwner = $db->prepare("SELECT user_id FROM thoughts WHERE id = ?");
    $stmtOwner->execute([$thoughtId]);
    $ownerId = $stmtOwner->fetchColumn();
    if ($ownerId) {
        $userVersion = (int)$cache->get("thoughts:user:version:{$ownerId}") ?: 1;
        $cache->set("thoughts:user:version:{$ownerId}", $userVersion + 1);
    }
    
    jsonResponse(['success' => true]);
}

if ($action === 'delete_comment') {
    if (!$currentUserId) {
        jsonResponse(['success' => false, 'message' => 'Please log in to delete comments.']);
    }
    
    $commentId = (int)($_POST['comment_id'] ?? 0);
    $thoughtId = (int)($_POST['thought_id'] ?? 0);
    
    $stmt = $db->prepare("SELECT user_id FROM thought_comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $commentData = $stmt->fetch();
    
    $currUser = getCurrentUser();
    $isAdmin = !empty($currUser['is_admin']);
    
    if (!$commentData || ($commentData['user_id'] != $currentUserId && !$isAdmin)) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized to delete this comment.']);
    }
    
    $db->prepare("DELETE FROM thought_comments WHERE id = ?")->execute([$commentId]);
    
    $count = $db->prepare("SELECT COUNT(*) FROM thought_comments WHERE thought_id = ?");
    $count->execute([$thoughtId]);
    $commentsCount = $count->fetchColumn();
    
    // Invalidate caches
    $cache = RedisCache::getInstance();
    $feedVersion = (int)$cache->get("thoughts:feed:version") ?: 1;
    $cache->set("thoughts:feed:version", $feedVersion + 1);
    
    $stmtOwner = $db->prepare("SELECT user_id FROM thoughts WHERE id = ?");
    $stmtOwner->execute([$thoughtId]);
    $ownerId = $stmtOwner->fetchColumn();
    if ($ownerId) {
        $userVersion = (int)$cache->get("thoughts:user:version:{$ownerId}") ?: 1;
        $cache->set("thoughts:user:version:{$ownerId}", $userVersion + 1);
    }
    
    jsonResponse(['success' => true, 'count' => $commentsCount]);
}

if ($action === 'share_thought') {
    if (!$currentUserId) {
        jsonResponse(['success' => false, 'message' => 'Please log in to share.']);
    }
    
    $thoughtId = (int)($_POST['thought_id'] ?? 0);
    
    $stmt = $db->prepare("INSERT INTO thought_shares (thought_id, user_id) VALUES (?, ?)");
    $stmt->execute([$thoughtId, $currentUserId]);
    
    $count = $db->prepare("SELECT COUNT(*) FROM thought_shares WHERE thought_id = ?");
    $count->execute([$thoughtId]);
    $sharesCount = $count->fetchColumn();
    
    // Invalidate caches
    $cache = RedisCache::getInstance();
    $feedVersion = (int)$cache->get("thoughts:feed:version") ?: 1;
    $cache->set("thoughts:feed:version", $feedVersion + 1);
    
    $stmtOwner = $db->prepare("SELECT user_id FROM thoughts WHERE id = ?");
    $stmtOwner->execute([$thoughtId]);
    $ownerId = $stmtOwner->fetchColumn();
    if ($ownerId) {
        $userVersion = (int)$cache->get("thoughts:user:version:{$ownerId}") ?: 1;
        $cache->set("thoughts:user:version:{$ownerId}", $userVersion + 1);
    }
    
    jsonResponse(['success' => true, 'count' => $sharesCount, 'link' => APP_URL . '/p/' . $thoughtId]);
}

jsonResponse(['success' => false, 'message' => 'Invalid action']);
