<?php
// pages/single-thought.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = 'Post - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';

$thoughtId = (int)($thoughtId ?? 0);
$db = getDB();

$currentUserId = isLoggedIn() ? $_SESSION['user_id'] : null;

// Fetch the thought
$stmt = $db->prepare("SELECT t.*, u.full_name, u.avatar_path, u.avatar_color, u.profile_slug, (u.is_verified OR u.is_admin) as is_verified,
    (SELECT COUNT(*) FROM thought_likes WHERE thought_id = t.id) as likes_count,
    (SELECT COUNT(*) FROM thought_comments WHERE thought_id = t.id) as comments_count,
    (SELECT COUNT(*) FROM thought_shares WHERE thought_id = t.id) as shares_count
    FROM thoughts t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?");
$stmt->execute([$thoughtId]);
$thought = $stmt->fetch();

if (!$thought) {
    echo '<div style="max-width: 600px; margin: 2rem auto; padding: 0 1rem; text-align: center; color: var(--gray-500);">Post not found.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Check privacy
$privacy = $thought['privacy'] ?? 'public';
$canView = false;

if ($privacy === 'public') {
    $canView = true;
} elseif ($currentUserId) {
    if ($thought['user_id'] == $currentUserId || isAdmin()) {
        $canView = true;
    } elseif ($privacy === 'friends') {
        $stmt = $db->prepare("SELECT id FROM connections WHERE status = 'accepted' AND 
            ((requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?))");
        $stmt->execute([$currentUserId, $thought['user_id'], $thought['user_id'], $currentUserId]);
        if ($stmt->fetch()) {
            $canView = true;
        }
    }
}

if (!$canView) {
    echo '<div style="max-width: 600px; margin: 2rem auto; padding: 0 1rem; text-align: center; color: var(--gray-500);">You do not have permission to view this post.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Increment views if not the author
if ($currentUserId != $thought['user_id']) {
    $db->prepare("UPDATE thoughts SET views = views + 1 WHERE id = ?")->execute([$thoughtId]);
    $thought['views']++;
}
?>

<div style="max-width: 600px; margin: 100px auto 2rem auto; padding: 0 1rem;">
    <?php
    require_once __DIR__ . '/../includes/thought_component.php';
    renderThoughtCard($thought, $currentUserId, true);
    ?>
    
    <div style="text-align: center; margin-top: 2rem;">
        <a href="/thoughts" data-no-spa="true" style="color: var(--green-600); text-decoration: none; font-weight: 500;">&larr; Back to Global Thoughts</a>
    </div>
</div>

<script>
window.toggleThoughtLike = async function(thoughtId, btnElem) {
    <?php if (!$currentUserId): ?>
        alert('Please log in to like posts.');
        return;
    <?php endif; ?>
    
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_like');
        formData.append('thought_id', thoughtId);
        
        const res = await fetch('/api/thoughts', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            const countSpan = btnElem.querySelector('.like-count');
            countSpan.textContent = data.count;
            const svg = btnElem.querySelector('svg');
            if (data.liked) {
                svg.setAttribute('fill', 'var(--green-600)');
                svg.setAttribute('stroke', 'var(--green-600)');
            } else {
                svg.setAttribute('fill', 'none');
                svg.setAttribute('stroke', 'currentColor');
            }
        } else {
            alert(data.message || 'Error');
        }
    } catch (err) {
        console.error(err);
    }
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
    container.innerHTML = '<div style="text-align: center; color: var(--gray-500); font-size: 0.9rem;">Loading...</div>';
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
        
        const res = await fetch('/api/thoughts', { method: 'POST', body: formData });
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
        
        const res = await fetch('/api/thoughts', { method: 'POST', body: formData });
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
                navigator.clipboard.writeText(window.location.origin + '/p/' + thoughtId);
                alert('Link copied to clipboard!');
            }
        }
    } catch (err) {
        console.error(err);
    }
}

window.deleteThought = async function(thoughtId) {
    if (!confirm('Are you sure you want to delete this post?')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_thought');
        formData.append('thought_id', thoughtId);
        
        const res = await fetch('/api/thoughts', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            window.location.href = '/thoughts';
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
    
    saveBtn.onclick = () => {
        const newContent = textarea.value.trim();
        const newLink = linkInput.value.trim();
        if (!newContent && !newLink && (!hasMedia || (removeMediaCheckbox && removeMediaCheckbox.checked)) && fileInput.files.length === 0) {
            alert('Post cannot be completely empty.');
            return;
        }
        
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
        
        window.saveThoughtWithProgress(saveBtn, thoughtId, formData, (data) => {
            window.location.reload();
        }, (errMsg) => {
            alert(errMsg);
            saveBtn.textContent = 'Save';
            saveBtn.disabled = false;
        });
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
