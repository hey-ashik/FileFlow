<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = 'thoughts';
$pageTitle = 'Thoughts - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';

$isLoggedInUser = isLoggedIn();
?>

<style>
    @media (max-width: 600px) {
        .thought-input-avatar {
            display: none !important;
        }

        .thought-input-row,
        .thought-input-actions {
            padding-left: 0 !important;
        }

        .thought-input-actions {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 1rem;
        }

        .thought-input-actions>div {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-label-text {
            display: none;
        }

        .search-label {
            padding: 0 0.5rem !important;
        }
    }
</style>

<div
    style="position: fixed; inset: 0; z-index: -1; background: linear-gradient(135deg, var(--green-50) 0%, var(--white) 50%, var(--green-50) 100%); overflow: hidden; pointer-events: none;">
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-orb hero-orb-3"></div>
</div>

<div style="max-width: 600px; margin: 2rem auto; padding: 0 1rem; position: relative; z-index: 1;">
    <div
        style="background: white; border-radius: 9999px; padding: 0.75rem 1.5rem; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); display: flex; align-items: center; border: 1px solid #7e8286;">
        <div style="flex: 1; display: flex; align-items: center; gap: 0.75rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"
                style="flex-shrink:0;">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="thoughts-search-input" placeholder="Search by name or content..."
                style="border: none; outline: none; width: 100%; font-family: inherit; font-size: 1rem; color: var(--gray-700); background: transparent; min-width:0;"
                oninput="debounceSearchThoughts()">
        </div>
    </div>

    <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--gray-900); margin-bottom: 1.5rem;">Global Thoughts</h1>

    <?php if ($isLoggedInUser): ?>
        <div
            style="background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #7e8286;">
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <?php
                $currentUser = getCurrentUser();
                $avatarStyle = empty($currentUser['avatar_path']) ? 'background:' . htmlspecialchars($currentUser['color']) . ';' : 'background:transparent;';
                $avatar = !empty($currentUser['avatar_path'])
                    ? '<img src="' . htmlspecialchars($currentUser['avatar_path']) . '" style="width:100%; height:100%; object-fit:cover;">'
                    : '<span>' . strtoupper(substr($currentUser['name'], 0, 1)) . '</span>';
                ?>
                <div class="thought-input-avatar"
                    style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; flex-shrink: 0; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; <?php echo $avatarStyle; ?>">
                    <?php echo $avatar; ?>
                </div>
                <div id="thought-editor-container" style="flex: 1; display: flex; flex-direction: column;"></div>
            </div>

            <div class="thought-input-row" style="margin-bottom: 1rem; padding-left: calc(48px + 1rem); display: flex;">
                <div id="thought-links-container" style="display: flex; flex-direction: column; gap: 0.5rem; width: 100%;">
                    <div style="display: flex; gap: 0.5rem; align-items: center; width: 100%;">
                        <input type="text" class="thought-link-input" placeholder="Share a link (optional)"
                            style="flex: 1; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.5rem 0.75rem; font-family: inherit; font-size: 0.95rem; outline: none;">
                        <button type="button" onclick="addLinkInput()"
                            style="background: #f1f5f9; border: 1px solid #cbd5e1; color: var(--gray-700); width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; cursor: pointer; transition: background 0.2s;"
                            title="Add another link">
                            +
                        </button>
                    </div>
                </div>
            </div>

            <div id="media-count"
                style="font-size: 0.85rem; font-weight: 600; color: var(--green-600); display: none; padding-left: calc(48px + 1rem); margin-bottom: 0.75rem;"></div>

            <div class="thought-input-actions"
                style="display: flex; align-items: center; justify-content: space-between; padding-left: calc(48px + 1rem);">
                <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <select id="thought-privacy" class="custom-select-arrow"
                        style="border: 1px solid #cbd5e1; border-radius: 9999px; padding: 0.5rem 1rem; font-family: inherit; font-size: 0.85rem; outline: none; background: white; color: var(--gray-700); cursor: pointer;">
                        <option value="public">Public</option>
                        <option value="friends">Friends</option>
                        <option value="private">Private</option>
                    </select>
                    <div>
                        <input type="file" id="thought-media" multiple accept="image/*,video/*,.pdf,.zip"
                            style="display: none;" onchange="updateMediaCount()">
                        <button type="button" onclick="document.getElementById('thought-media').click()"
                            style="display: flex; align-items: center; gap: 0.5rem; background: none; border: 1px solid #cbd5e1; padding: 0.5rem 1rem; border-radius: 9999px; color: var(--gray-900); font-weight: 500; cursor: pointer; transition: background 0.2s;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            Upload Media
                        </button>
                    </div>
                </div>
                <button type="button" onclick="createThought()" id="btn-create-thought"
                    style="background: var(--green-600); color: white; border: none; padding: 0.5rem 1.5rem; border-radius: 9999px; font-weight: 600; cursor: pointer; transition: opacity 0.2s;">
                    Post
                </button>
            </div>
        </div>
    <?php else: ?>
        <div
            style="background: white; border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
            <p style="color: var(--gray-900); font-size: 0.95rem; font-weight: 500; margin: 0; flex: 1; min-width: 200px;">
                Join the conversation and share your thoughts!</p>
            <a href="/login"
                style="display: inline-block; background: var(--green-600); color: white; text-decoration: none; padding: 0.5rem 1.2rem; border-radius: 9999px; font-weight: 600; font-size: 0.9rem; white-space: nowrap;">Log
                In to Post</a>
        </div>
    <?php endif; ?>

    <div id="feed-thoughts-container">
        <div style="text-align: center; color: var(--gray-500); padding: 2rem 0;">Loading thoughts...</div>
    </div>
</div>

<script>
    (function() {
        const init = () => {
            const container = document.getElementById('thought-editor-container');
            if (container) {
                initializeRichTextEditor(container, 'thought-content', 'Share your current thoughts...', '');
            }
        };
        if (typeof initializeRichTextEditor !== 'undefined') {
            init();
        } else {
            document.addEventListener('DOMContentLoaded', init);
        }
    })();

    window.updateMediaCount = function () {
        const input = document.getElementById('thought-media');
        const display = document.getElementById('media-count');
        if (input.files.length > 0) {
            display.textContent = `${input.files.length} file(s) selected`;
            display.style.display = 'block';
        } else {
            display.style.display = 'none';
        }
    }

    window.addLinkInput = function() {
        const container = document.getElementById('thought-links-container');
        const totalInputs = container.querySelectorAll('.thought-link-input').length;
        if (totalInputs >= 5) {
            alert('You can add up to 5 links.');
            return;
        }
        
        const newDiv = document.createElement('div');
        newDiv.className = 'thought-link-input-item';
        newDiv.style.display = 'flex';
        newDiv.style.gap = '0.5rem';
        newDiv.style.alignItems = 'center';
        newDiv.style.width = '100%';
        
        const newInput = document.createElement('input');
        newInput.type = 'text';
        newInput.className = 'thought-link-input';
        newInput.placeholder = 'Share another link (optional)';
        newInput.style.flex = '1';
        newInput.style.border = '1px solid #cbd5e1';
        newInput.style.borderRadius = '8px';
        newInput.style.padding = '0.5rem 0.75rem';
        newInput.style.fontFamily = 'inherit';
        newInput.style.fontSize = '0.95rem';
        newInput.style.outline = 'none';
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.textContent = '×';
        removeBtn.style.background = '#fee2e2';
        removeBtn.style.border = '1px solid #fca5a5';
        removeBtn.style.color = '#ef4444';
        removeBtn.style.width = '36px';
        removeBtn.style.height = '36px';
        removeBtn.style.borderRadius = '8px';
        removeBtn.style.display = 'flex';
        removeBtn.style.alignItems = 'center';
        removeBtn.style.justifyContent = 'center';
        removeBtn.style.fontWeight = 'bold';
        removeBtn.style.fontSize = '1.2rem';
        removeBtn.style.cursor = 'pointer';
        removeBtn.onclick = () => {
            newDiv.remove();
        };
        
        newDiv.appendChild(newInput);
        newDiv.appendChild(removeBtn);
        container.appendChild(newDiv);
        newInput.focus();
    };

    window.createThought = function () {
        const content = document.getElementById('thought-content').value.trim();
        
        const linkInputs = document.querySelectorAll('.thought-link-input');
        let link = '';
        if (linkInputs.length > 0) {
            const links = Array.from(linkInputs).map(inp => inp.value.trim()).filter(v => v !== '');
            if (links.length > 1) {
                link = JSON.stringify(links);
            } else if (links.length === 1) {
                link = links[0];
            }
        }
        const mediaFiles = document.getElementById('thought-media').files;

        for (let i = 0; i < mediaFiles.length; i++) {
            if (mediaFiles[i].size > 10 * 1024 * 1024) {
                alert('Each file must be 10MB or less.');
                return;
            }
        }

        if (!content && !link && mediaFiles.length === 0) {
            alert("Please enter some text, a link, or upload media.");
            return;
        }

        const btn = document.getElementById('btn-create-thought');
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Posting...';
        btn.disabled = true;

        const privacy = document.getElementById('thought-privacy').value;

        const formData = new FormData();
        formData.append('action', 'create_thought');
        formData.append('content', content);
        formData.append('link', link);
        formData.append('privacy', privacy);
        for (let i = 0; i < mediaFiles.length; i++) {
            formData.append('media[]', mediaFiles[i]);
        }

        // Initialize persistent widget if there are media files
        const widget = window.ensurePersistentWidget ? window.ensurePersistentWidget() : null;
        if (widget) {
            widget.classList.add('active');
            widget.classList.remove('minimized');
            const wTitle = widget.querySelector('#widget-title-text');
            if (wTitle) wTitle.textContent = `Uploading ${mediaFiles.length} file(s)...`;
            const wClose = widget.querySelector('#btn-close-widget');
            if (wClose) wClose.style.display = 'none';
            const wFileList = widget.querySelector('#widget-file-list');
            if (wFileList) {
                wFileList.innerHTML = '';
                for (let i = 0; i < mediaFiles.length; i++) {
                    wFileList.innerHTML += `
                        <div class="widget-file-item" id="thought-upload-file-${i}" style="display: flex; flex-direction: column; gap: 0.25rem; padding: 0.5rem; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.85rem; margin-bottom: 0.25rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; display: block; font-weight: 500;">${mediaFiles[i].name}</span>
                                <span class="pct" style="font-weight: 600; color: var(--green-600);">Pending</span>
                            </div>
                        </div>
                    `;
                }
            }
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/thoughts', true);

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                
                if (widget) {
                    const wPct = widget.querySelector('#widget-progress-pct');
                    const wBar = widget.querySelector('#widget-progress-bar');
                    if (wPct) wPct.textContent = percentComplete + '%';
                    if (wBar) wBar.style.width = percentComplete + '%';
                    
                    for (let i = 0; i < mediaFiles.length; i++) {
                        const fileItem = widget.querySelector(`#thought-upload-file-${i} .pct`);
                        if (fileItem) {
                            fileItem.textContent = percentComplete === 100 ? 'Finishing...' : `${percentComplete}%`;
                        }
                    }
                }
            }
        });

        xhr.addEventListener('load', () => {
            let data = {};
            try {
                data = JSON.parse(xhr.responseText);
            } catch (err) {}

            btn.innerHTML = originalText;
            btn.disabled = false;

            if (xhr.status === 200 && data.success) {
                if (widget) {
                    const wTitle = widget.querySelector('#widget-title-text');
                    if (wTitle) wTitle.textContent = 'Upload Complete';
                    const wPct = widget.querySelector('#widget-progress-pct');
                    const wBar = widget.querySelector('#widget-progress-bar');
                    if (wPct) wPct.textContent = '100%';
                    if (wBar) wBar.style.width = '100%';
                    const wClose = widget.querySelector('#btn-close-widget');
                    if (wClose) wClose.style.display = 'flex';
                    
                    for (let i = 0; i < mediaFiles.length; i++) {
                        const fileItem = widget.querySelector(`#thought-upload-file-${i} .pct`);
                        if (fileItem) {
                            fileItem.textContent = 'Uploaded';
                            fileItem.style.color = 'var(--green-600)';
                        }
                    }
                    
                    setTimeout(() => {
                        widget.classList.remove('active');
                        const wFileList = widget.querySelector('#widget-file-list');
                        if (wFileList) wFileList.innerHTML = '';
                    }, 4000);
                }
                
                document.getElementById('thought-content').value = '';
                const linkContainer = document.getElementById('thought-links-container');
                if (linkContainer) {
                    linkContainer.innerHTML = `
                        <div style="display: flex; gap: 0.5rem; align-items: center; width: 100%;">
                            <input type="text" class="thought-link-input" placeholder="Share a link (optional)"
                                style="flex: 1; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.5rem 0.75rem; font-family: inherit; font-size: 0.95rem; outline: none;">
                            <button type="button" onclick="addLinkInput()"
                                style="background: #f1f5f9; border: 1px solid #cbd5e1; color: var(--gray-700); width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; cursor: pointer; transition: background 0.2s;"
                                title="Add another link">
                                +
                            </button>
                        </div>
                    `;
                }
                document.getElementById('thought-media').value = '';
                updateMediaCount();
                loadFeedThoughts();
            } else {
                alert(data.message || 'Error creating post');
                if (widget) widget.classList.remove('active');
            }
        });

        xhr.addEventListener('error', () => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert('Failed to post thought.');
            if (widget) widget.classList.remove('active');
        });

        xhr.send(formData);
    }

    window.loadFeedThoughts = async function (searchQuery = '') {
        const container = document.getElementById('feed-thoughts-container');
        container.innerHTML = '<div style="text-align: center; color: var(--gray-500); font-size: 0.9rem; padding: 2rem 0;">Loading thoughts...</div>';
        try {
            const url = `/api/thoughts?action=get_feed_thoughts&search=${encodeURIComponent(searchQuery)}`;
            const res = await fetch(url);
            const html = await res.text();
            container.innerHTML = html;
            
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

    let searchTimeout = null;
    window.debounceSearchThoughts = function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(searchThoughts, 400);
    }

    window.searchThoughts = function () {
        const input = document.getElementById('thoughts-search-input');
        if (input) {
            loadFeedThoughts(input.value.trim());
        }
    }

    // Like functionality
    window.toggleThoughtLike = async function (thoughtId, btnElem) {
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

    // Comment functionality
    window.toggleComments = function (thoughtId) {
        const section = document.getElementById(`comments-section-${thoughtId}`);
        if (section.style.display === 'none') {
            section.style.display = 'block';
            loadComments(thoughtId);
        } else {
            section.style.display = 'none';
        }
    }

    window.loadComments = async function (thoughtId) {
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

    window.postComment = async function (thoughtId) {
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
                // Update comment count
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

    window.shareThought = async function (thoughtId) {
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
                // Update share count
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
        } catch (err) {
            console.error(err);
        }
    }

    window.deleteThought = async function (thoughtId) {
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
                    window.loadFeedThoughts();
                }
            } else {
                alert(data.message || 'Error deleting post.');
            }
        } catch (err) {
            alert('Failed to delete post.');
        }
    }

    window.editThought = function (thoughtId) {
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
        fileInput.accept = 'image/*,video/*,.pdf,.zip';
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
                    if (fileInput.files[i].size > 10 * 1024 * 1024) {
                        alert('Each file must be 10MB or less.');
                        saveBtn.textContent = 'Save';
                        saveBtn.disabled = false;
                        return;
                    }
                    formData.append('media[]', fileInput.files[i]);
                }

                const res = await fetch('/api/thoughts', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    if (typeof window.loadFeedThoughts === 'function') window.loadFeedThoughts();
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

    window.editComment = function (commentId, thoughtId) {
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

    window.deleteComment = async function (commentId, thoughtId) {
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

    window.showReplyForm = function (commentId) {
        const container = document.getElementById(`reply-container-${commentId}`);
        if (container) {
            container.style.display = 'block';
            document.getElementById(`reply-input-${commentId}`).focus();
        }
    };

    window.hideReplyForm = function (commentId) {
        const container = document.getElementById(`reply-container-${commentId}`);
        if (container) {
            container.style.display = 'none';
            document.getElementById(`reply-input-${commentId}`).value = '';
        }
    };

    window.postReply = async function (commentId, thoughtId) {
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

    // Initial load
    document.addEventListener('DOMContentLoaded', () => {
        loadFeedThoughts();
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>