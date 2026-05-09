/**
 * FileFlow - Main Application JavaScript
 */

let isUploading = false;

let isGlobalInitDone = false;

document.addEventListener('DOMContentLoaded', () => {
    initGlobal();
    initApp();
    initSpaNavigation();
});

function initGlobal() {
    if (isGlobalInitDone) return;
    initNavbar();
    initUserDropdown();

    // Prevent accidental navigation during uploads
    window.addEventListener('beforeunload', (e) => {
        if (isUploading) {
            const msg = 'An upload is currently in progress. If you leave this page, your upload will be cancelled.';
            e.preventDefault();
            e.returnValue = msg;
            return msg;
        }
    });

    isGlobalInitDone = true;
}

function initApp() {
    initCreateForm();
    initUpload();
    initHistory();
    initQR();
    initAuthForms();
}

/* ===== SPA NAVIGATION (Next.js Style) ===== */
const spaCache = new Map();

function initSpaNavigation() {
    const loader = document.getElementById('spa-loader-fill');

    // Intercept all internal link clicks
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (!link || !link.href) return;

        const url = new URL(link.href);
        const isInternal = url.origin === window.location.origin;
        const isSelf = link.getAttribute('target') === '_self' || !link.getAttribute('target');
        const isNotSpecial = !link.getAttribute('download') && !link.href.includes('#') && !link.href.startsWith('mailto:') && !link.href.startsWith('tel:') && !link.href.includes('/logout');

        if (isInternal && isSelf && isNotSpecial) {
            e.preventDefault();
            if (window.location.href === link.href) return;
            handleSpaLink(link.href);
        }
    });

    // Prefetch on hover
    document.addEventListener('mouseover', (e) => {
        const link = e.target.closest('a');
        if (!link || !link.href) return;

        const url = new URL(link.href);
        if (url.origin === window.location.origin && !spaCache.has(link.href)) {
            prefetchSpaLink(link.href);
        }
    });

    // Handle browser back/forward
    window.addEventListener('popstate', () => {
        handleSpaLink(window.location.href, false);
    });
}

async function prefetchSpaLink(url) {
    try {
        const response = await fetch(url);
        if (response.ok) {
            const html = await response.text();
            spaCache.set(url, html);
        }
    } catch (err) { }
}

async function handleSpaLink(url, push = true) {
    if (isUploading) {
        if (!confirm('An upload is in progress. Leaving will cancel it. Continue?')) return;
    }

    const loader = document.getElementById('spa-loader-fill');
    if (loader) {
        loader.style.width = '30%';
        loader.style.opacity = '1';
    }

    try {
        let html = spaCache.get(url);
        if (!html) {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Failed to load page');
            html = await response.text();
        }

        if (loader) loader.style.width = '70%';
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Update Title and Content
        document.title = doc.title;
        const newContent = doc.querySelector('.main-content');
        const currentContent = document.querySelector('.main-content');

        if (newContent && currentContent) {
            currentContent.innerHTML = newContent.innerHTML;

            // Execute scripts inside new content
            const scripts = currentContent.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            // Update Body Classes (Home vs Inner)
            document.body.className = doc.body.className;

            // Update Navbar Active States
            updateNavbarActive(url);

            // Update URL
            if (push) history.pushState({}, '', url);

            // Re-initialize scripts for new content
            initApp();

            // Scroll to top
            window.scrollTo(0, 0);
        }

        if (loader) {
            loader.style.width = '100%';
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.width = '0%', 300);
            }, 200);
        }
    } catch (err) {
        console.error('SPA Load Error:', err);
        window.location.href = url; // Fallback to normal load
    }
}

function updateNavbarActive(url) {
    const path = new URL(url).pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        const linkPath = new URL(link.href).pathname;
        if (linkPath === path) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

/* ===== TOAST NOTIFICATIONS ===== */
function showToast(message, type = 'success', duration = 4000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icons = {
        success: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        error: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        info: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>'
    };
    toast.innerHTML = `${icons[type] || icons.info}<span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(40px)';
        toast.style.transition = 'all .3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

/* ===== CSRF TOKEN ===== */
function getCSRF() {
    return document.getElementById('csrf-token')?.value || '';
}
function updateCSRF(newToken) {
    const el = document.getElementById('csrf-token');
    if (el && newToken) el.value = newToken;
    if (typeof CSRF_TOKEN !== 'undefined' && newToken) window.CSRF_TOKEN = newToken;
}

/* ===== NAVBAR ===== */
function initNavbar() {
    const toggle = document.getElementById('nav-toggle');
    const links = document.getElementById('nav-links');
    if (toggle && links) {
        toggle.addEventListener('click', () => {
            links.classList.toggle('open');
            toggle.classList.toggle('active');
        });
        document.addEventListener('click', (e) => {
            if (!toggle.contains(e.target) && !links.contains(e.target)) {
                links.classList.remove('open');
                toggle.classList.remove('active');
            }
        });
    }
    // Navbar scroll effect
    let lastScroll = 0;
    window.addEventListener('scroll', () => {
        const navbar = document.getElementById('navbar');
        if (!navbar) return;
        const scroll = window.scrollY;
        if (scroll > 50) {
            navbar.style.boxShadow = '0 1px 3px rgba(0,0,0,.1)';
        } else {
            navbar.style.boxShadow = 'none';
        }
        lastScroll = scroll;
    });
}

function scrollToCreate(e) {
    e.preventDefault();
    const section = document.getElementById('create-section');
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => document.getElementById('folder-name-input')?.focus(), 500);
    }
}

/* ===== CREATE FOLDER ===== */
function initCreateForm() {
    const form = document.getElementById('create-folder-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('folder-name-input');
        const btn = document.getElementById('btn-create-folder');
        const hint = document.getElementById('folder-hint');
        const folderName = input.value.trim();

        if (!folderName) {
            hint.textContent = 'Please enter a folder name.';
            hint.className = 'input-hint error';
            input.focus();
            return;
        }

        // Disable button
        btn.querySelector('.btn-text').style.display = 'none';
        btn.querySelector('.btn-loader').style.display = 'flex';
        btn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('folder_name', folderName);
            formData.append('csrf_token', getCSRF());

            const res = await fetch('/api/create-folder', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                updateCSRF(data.csrf_token);
                showCreateSuccess(data.folder);
                saveFolderToHistory(data.folder);
                showToast('Folder created successfully!');
            } else {
                hint.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> ${data.errors?.[0] || 'Failed to create folder.'}`;
                hint.className = 'input-hint error';
                showToast(data.errors?.[0] || 'Failed to create folder.', 'error');
            }
        } catch (err) {
            showToast('Network error. Please try again.', 'error');
        } finally {
            btn.querySelector('.btn-text').style.display = '';
            btn.querySelector('.btn-loader').style.display = 'none';
            btn.disabled = false;
        }
    });
}

function showCreateSuccess(folder) {
    const form = document.getElementById('create-folder-form');
    const icon = document.querySelector('.create-icon');
    const success = document.getElementById('create-success');
    const urlInput = document.getElementById('success-url');
    const gotoBtn = document.getElementById('btn-goto-folder');

    if (form) form.style.display = 'none';
    if (icon) icon.style.display = 'none';
    if (success) success.style.display = 'block';
    if (urlInput) urlInput.value = folder.url;
    if (gotoBtn) gotoBtn.href = '/' + folder.slug;

    // Generate QR
    const qrBox = document.getElementById('qr-code');
    if (qrBox && typeof QRCode !== 'undefined') {
        qrBox.innerHTML = '';
        new QRCode(qrBox, { text: folder.url, width: 128, height: 128, colorDark: '#166534', colorLight: '#ffffff' });
    }
}

function resetCreateForm() {
    const form = document.getElementById('create-folder-form');
    const icon = document.querySelector('.create-icon');
    const success = document.getElementById('create-success');
    const hint = document.getElementById('folder-hint');
    const input = document.getElementById('folder-name-input');

    if (form) { form.style.display = ''; form.reset(); }
    if (icon) icon.style.display = '';
    if (success) success.style.display = 'none';
    if (hint) {
        hint.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg> Use letters, numbers, hyphens, or underscores.`;
        hint.className = 'input-hint';
    }
    if (input) input.focus();
}

function copyFolderUrl() {
    const input = document.getElementById('success-url');
    if (!input) return;
    navigator.clipboard.writeText(input.value).then(() => {
        showToast('Link copied to clipboard!');
    }).catch(() => {
        input.select();
        document.execCommand('copy');
        showToast('Link copied!');
    });
}

/* ===== FOLDER SHARE ===== */
function shareFolderUrl(url) {
    navigator.clipboard.writeText(url).then(() => {
        showToast('Folder link copied to clipboard!');
    }).catch(() => {
        showToast('Failed to copy link.', 'error');
    });
}

function toggleQR() {
    const panel = document.getElementById('qr-panel');
    if (!panel) return;
    const isHidden = panel.style.display === 'none';
    panel.style.display = isHidden ? 'block' : 'none';
    if (isHidden) {
        const qrBox = document.getElementById('folder-qr-code');
        const folderUrl = document.getElementById('folder-url')?.value;
        if (qrBox && folderUrl && !qrBox.hasChildNodes() && typeof QRCode !== 'undefined') {
            new QRCode(qrBox, { text: folderUrl, width: 160, height: 160, colorDark: '#166534', colorLight: '#ffffff' });
        }
    }
}

/* ===== FILE UPLOAD ===== */
function initUpload() {
    const dropzone = document.getElementById('upload-dropzone');
    const fileInput = document.getElementById('file-input');
    if (!dropzone || !fileInput) return;

    // Click to upload
    dropzone.addEventListener('click', (e) => {
        if (e.target !== fileInput) fileInput.click();
    });

    // Drag and drop
    ['dragenter', 'dragover'].forEach(evt => {
        dropzone.addEventListener(evt, (e) => { e.preventDefault(); dropzone.classList.add('drag-over'); });
    });
    ['dragleave', 'drop'].forEach(evt => {
        dropzone.addEventListener(evt, (e) => { e.preventDefault(); dropzone.classList.remove('drag-over'); });
    });
    dropzone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length) handleFiles(files);
    });

    // File input change
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) handleFiles(fileInput.files);
    });
}

async function handleFiles(files) {
    const folderId = document.getElementById('folder-id')?.value;
    if (!folderId) return;

    // Validate on client side
    const validFiles = [];
    for (let i = 0; i < files.length && i < MAX_FILES_PER_UPLOAD; i++) {
        const file = files[i];
        const ext = file.name.split('.').pop().toLowerCase();
        if (!ALLOWED_EXTENSIONS.includes(ext)) {
            showToast(`"${file.name}" is not a supported file type.`, 'error');
            continue;
        }
        if (file.size > MAX_FILE_SIZE) {
            showToast(`"${file.name}" exceeds the size limit.`, 'error');
            continue;
        }
        validFiles.push(file);
    }

    if (!validFiles.length) return;

    // Show progress UI
    const progressArea = document.getElementById('upload-progress-area');
    const progressBar = document.getElementById('upload-progress-bar');
    const progressText = document.getElementById('upload-progress-text');
    const fileList = document.getElementById('upload-file-list');
    const progressHeader = progressArea?.querySelector('h4');

    if (progressArea) progressArea.style.display = 'block';
    if (fileList) fileList.innerHTML = '';
    if (progressBar) { progressBar.style.transition = 'none'; progressBar.style.width = '0%'; }
    if (progressText) progressText.textContent = '0%';

    // Force reflow then enable smooth transition
    progressBar?.offsetWidth;
    if (progressBar) progressBar.style.transition = 'width 0.15s linear';

    const totalFiles = validFiles.length;
    const totalBytes = validFiles.reduce((sum, f) => sum + f.size, 0);
    let successCount = 0;
    let failCount = 0;
    const startTime = Date.now();
    isUploading = true;

    if (progressHeader) progressHeader.textContent = `Uploading ${totalFiles} file(s)...`;

    // Create UI items for all files
    const fileItems = [];
    for (let i = 0; i < totalFiles; i++) {
        const item = document.createElement('div');
        item.className = 'upload-file-item';
        item.innerHTML = `<span>${validFiles[i].name}</span><span class="file-status">Uploading...</span>`;
        if (fileList) fileList.appendChild(item);
        fileItems.push(item);
    }

    try {
        const result = await uploadBatch(validFiles, folderId, {
            onProgress: (loaded, total) => {
                const overallPct = totalBytes > 0 ? Math.min(Math.round((loaded / total) * 100), 99) : 0;
                if (progressBar) progressBar.style.width = overallPct + '%';
                if (progressText) progressText.textContent = overallPct + '%';

                const timeElapsed = (Date.now() - startTime) / 1000;
                if (timeElapsed > 0.5 && loaded > 0) {
                    const speedBps = loaded / timeElapsed;
                    const bytesRemaining = total - loaded;
                    const timeRemainingSec = Math.max(0, bytesRemaining / speedBps);

                    let timeStr = "";
                    if (timeRemainingSec >= 3600) {
                        timeStr = Math.floor(timeRemainingSec / 3600) + "h " + Math.floor((timeRemainingSec % 3600) / 60) + "m";
                    } else if (timeRemainingSec >= 60) {
                        timeStr = Math.floor(timeRemainingSec / 60) + "m " + Math.floor(timeRemainingSec % 60) + "s";
                    } else {
                        timeStr = Math.floor(timeRemainingSec) + "s";
                    }

                    fileItems.forEach(item => {
                        if (item.className === 'upload-file-item') {
                            item.querySelector('.file-status').textContent = `Uploading... ${overallPct}% (${timeStr} remaining)`;
                        }
                    });
                } else {
                    fileItems.forEach(item => {
                        if (item.className === 'upload-file-item') {
                            item.querySelector('.file-status').textContent = `Uploading... ${overallPct}%`;
                        }
                    });
                }
            }
        });

        if (result.success && result.results) {
            result.results.forEach((r, idx) => {
                const item = fileItems[idx];
                if (r.success && r.file) {
                    addFileCard(r.file);
                    successCount++;
                    if (item) {
                        item.className = 'upload-file-item success';
                        item.querySelector('.file-status').textContent = 'Uploaded';
                    }
                } else {
                    failCount++;
                    if (item) {
                        item.className = 'upload-file-item error';
                        item.querySelector('.file-status').textContent = (r.errors?.[0] || 'Failed');
                    }
                }
            });
            if (result.csrf_token) updateCSRF(result.csrf_token);
        } else {
            // Whole batch failed (e.g. storage limit exceeded)
            fileItems.forEach(item => {
                failCount++;
                item.className = 'upload-file-item error';
                item.querySelector('.file-status').textContent = (result.errors?.[0] || 'Failed');
            });
            if (result.csrf_token) updateCSRF(result.csrf_token);
        }
    } catch (err) {
        fileItems.forEach(item => {
            failCount++;
            item.className = 'upload-file-item error';
            item.querySelector('.file-status').textContent = 'Network error';
        });
    }

    // Final state: 100%
    if (progressBar) progressBar.style.width = '100%';
    if (progressText) progressText.textContent = '100%';
    if (progressHeader) progressHeader.textContent = 'Upload Complete';

    if (successCount > 0) {
        showToast(`${successCount} file${successCount > 1 ? 's' : ''} uploaded successfully!`);
        const countEl = document.getElementById('file-count');
        if (countEl) countEl.textContent = parseInt(countEl.textContent) + successCount;
        const empty = document.getElementById('files-empty');
        if (empty) empty.remove();
    }
    if (failCount > 0) {
        showToast(`${failCount} file${failCount > 1 ? 's' : ''} failed to upload.`, 'error');
    }

    // Reset file input
    const fileInput = document.getElementById('file-input');
    if (fileInput) fileInput.value = '';

    isUploading = false;

    // Hide progress after delay
    setTimeout(() => {
        if (progressArea) progressArea.style.display = 'none';
    }, 4000);
}

/**
 * Upload a batch of files via XHR with real-time progress callback
 */
function uploadBatch(files, folderId, { onProgress }) {
    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('folder_id', folderId);
        formData.append('csrf_token', getCSRF());
        files.forEach(file => formData.append('files[]', file));

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/upload');

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable && onProgress) {
                onProgress(e.loaded, e.total);
            }
        });

        xhr.addEventListener('load', () => {
            try {
                const data = JSON.parse(xhr.responseText);
                resolve(data);
            } catch (e) {
                reject(new Error('Invalid response'));
            }
        });

        xhr.addEventListener('error', () => reject(new Error('Network error')));
        xhr.addEventListener('abort', () => reject(new Error('Upload aborted')));

        xhr.send(formData);
    });
}

function addFileCard(file) {
    const grid = document.getElementById('files-grid');
    if (!grid) return;

    const card = document.createElement('div');
    card.className = `file-card file-card-${file.category}`;
    card.id = `file-${file.id}`;
    card.innerHTML = `
        <div class="file-card-icon">
            <span class="file-type-badge">${file.extension.toUpperCase()}</span>
        </div>
        <div class="file-card-info">
            <h4 class="file-name" title="${file.name}">${file.name}</h4>
            <div class="file-meta">
                <span class="file-size">${file.size}</span>
                <span class="file-date">${file.uploaded_at}</span>
            </div>
        </div>
        <div class="file-card-actions">
            <a href="/api/download?id=${file.id}" class="btn btn-sm btn-download" title="Download">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </a>
        </div>`;
    grid.insertBefore(card, grid.firstChild);
}

/* ===== FOLDER HISTORY (localStorage) ===== */
function initHistory() {
    const grid = document.getElementById('history-grid');
    if (!grid) return;
    renderHistory();
}

function getHistory() {
    try {
        return JSON.parse(localStorage.getItem('fileflow_history') || '[]');
    } catch { return []; }
}

function saveFolderToHistory(folder) {
    let history = getHistory();
    // Remove if exists
    history = history.filter(h => h.slug !== folder.slug);
    // Add to front
    history.unshift({
        name: folder.name,
        slug: folder.slug,
        url: folder.url,
        created: new Date().toISOString()
    });
    // Keep max 20
    history = history.slice(0, 20);
    localStorage.setItem('fileflow_history', JSON.stringify(history));
    renderHistory();
}

function removeFromHistory(slug) {
    let history = getHistory();
    history = history.filter(h => h.slug !== slug);
    localStorage.setItem('fileflow_history', JSON.stringify(history));
    renderHistory();
    showToast('Removed from history.', 'info');
}

function renderHistory() {
    const grid = document.getElementById('history-grid');
    if (!grid) return;

    const history = getHistory();
    const empty = document.getElementById('history-empty');

    if (history.length === 0) {
        grid.innerHTML = '';
        if (empty) grid.appendChild(empty);
        return;
    }

    grid.innerHTML = history.map(h => `
        <div class="history-card" onclick="window.location.href='/${h.slug}'">
            <div class="history-card-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="history-card-info">
                <div class="history-card-name">${h.name}</div>
                <div class="history-card-url">${h.url}</div>
            </div>
            <button class="history-card-remove" onclick="event.stopPropagation();removeFromHistory('${h.slug}')" title="Remove">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    `).join('');
}

/* ===== QR CODE INIT ===== */
function initQR() {
    // Auto-generate QR on folder page if panel exists
    const folderUrl = document.getElementById('folder-url')?.value;
    if (!folderUrl) return;
    // QR will be generated on toggle click
}

/* ===== GLOBAL HELPERS ===== */
window.scrollToCreate = scrollToCreate;
window.copyFolderUrl = copyFolderUrl;
window.resetCreateForm = resetCreateForm;
window.shareFolderUrl = shareFolderUrl;
window.toggleQR = toggleQR;
window.removeFromHistory = removeFromHistory;
window.togglePasswordVisibility = togglePasswordVisibility;

/* ===== USER DROPDOWN ===== */
function initUserDropdown() {
    const avatarBtn = document.getElementById('nav-avatar-btn');
    const dropdown = document.getElementById('nav-dropdown');
    if (!avatarBtn || !dropdown) return;

    avatarBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('show');
    });
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target) && !avatarBtn.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
}

/* ===== AUTH FORMS ===== */
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.innerHTML = isPassword
        ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
        : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
}

function initAuthForms() {
    // Login
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-login');
            const errEl = document.getElementById('login-error');
            setBtnLoading(btn, true);
            errEl.style.display = 'none';

            const formData = new FormData(loginForm);
            formData.append('csrf_token', getCSRF());

            try {
                const res = await fetch('/api/auth/login', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.csrf_token) updateCSRF(data.csrf_token);
                if (data.success) {
                    showToast('Login successful! Redirecting...');
                    setTimeout(() => window.location.href = data.is_admin ? '/admin' : '/dashboard', 800);
                } else {
                    errEl.textContent = data.errors?.[0] || 'Login failed.';
                    errEl.style.display = 'flex';
                }
            } catch (err) {
                errEl.textContent = 'Network error. Please try again.';
                errEl.style.display = 'flex';
            } finally {
                setBtnLoading(btn, false);
            }
        });
    }

    // Register
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-register');
            const errEl = document.getElementById('register-error');
            setBtnLoading(btn, true);
            errEl.style.display = 'none';

            const pass = document.getElementById('reg-password').value;
            const confirm = document.getElementById('reg-confirm').value;
            if (pass !== confirm) {
                errEl.textContent = 'Passwords do not match.';
                errEl.style.display = 'flex';
                setBtnLoading(btn, false);
                return;
            }

            const formData = new FormData(registerForm);
            formData.append('csrf_token', getCSRF());

            try {
                const res = await fetch('/api/auth/register', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.csrf_token) updateCSRF(data.csrf_token);
                if (data.success) {
                    showToast('Account created! Redirecting...');
                    setTimeout(() => window.location.href = '/dashboard', 800);
                } else {
                    errEl.textContent = data.errors?.[0] || 'Registration failed.';
                    errEl.style.display = 'flex';
                }
            } catch (err) {
                errEl.textContent = 'Network error. Please try again.';
                errEl.style.display = 'flex';
            } finally {
                setBtnLoading(btn, false);
            }
        });
    }

    // Forgot Password
    const forgotForm = document.getElementById('forgot-form');
    if (forgotForm) {
        forgotForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-forgot');
            const errEl = document.getElementById('forgot-error');
            const successEl = document.getElementById('forgot-success');
            setBtnLoading(btn, true);
            errEl.style.display = 'none';
            successEl.style.display = 'none';

            const formData = new FormData(forgotForm);
            formData.append('csrf_token', getCSRF());

            try {
                const res = await fetch('/api/auth/forgot-password', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.csrf_token) updateCSRF(data.csrf_token);
                if (data.success) {
                    let msg = data.message || 'Reset link sent.';
                    if (data.reset_link) {
                        msg += '<br><br><strong>Reset Link:</strong><br><a href="' + data.reset_link + '" style="word-break:break-all;color:var(--green-600)">' + data.reset_link + '</a>';
                    }
                    successEl.innerHTML = msg;
                    successEl.style.display = 'block';
                    showToast('Reset link generated!');
                } else {
                    errEl.textContent = data.errors?.[0] || 'Failed.';
                    errEl.style.display = 'flex';
                }
            } catch (err) {
                errEl.textContent = 'Network error.';
                errEl.style.display = 'flex';
            } finally {
                setBtnLoading(btn, false);
            }
        });
    }

    // Reset Password
    const resetForm = document.getElementById('reset-form');
    if (resetForm) {
        resetForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-reset');
            const errEl = document.getElementById('reset-error');
            const successEl = document.getElementById('reset-success');
            setBtnLoading(btn, true);
            errEl.style.display = 'none';
            successEl.style.display = 'none';

            const pass = document.getElementById('reset-password').value;
            const confirm = document.getElementById('reset-confirm').value;
            if (pass !== confirm) {
                errEl.textContent = 'Passwords do not match.';
                errEl.style.display = 'flex';
                setBtnLoading(btn, false);
                return;
            }

            const formData = new FormData();
            formData.append('token', document.getElementById('reset-token').value);
            formData.append('password', pass);
            formData.append('confirm_password', confirm);
            formData.append('csrf_token', getCSRF());

            try {
                const res = await fetch('/api/auth/reset-password', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.csrf_token) updateCSRF(data.csrf_token);
                if (data.success) {
                    successEl.textContent = data.message || 'Password reset! Redirecting to login...';
                    successEl.style.display = 'block';
                    showToast('Password reset successfully!');
                    setTimeout(() => window.location.href = '/login', 2000);
                } else {
                    errEl.textContent = data.errors?.[0] || 'Reset failed.';
                    errEl.style.display = 'flex';
                }
            } catch (err) {
                errEl.textContent = 'Network error.';
                errEl.style.display = 'flex';
            } finally {
                setBtnLoading(btn, false);
            }
        });
    }
}

function setBtnLoading(btn, loading) {
    if (!btn) return;
    const text = btn.querySelector('.btn-text');
    const loader = btn.querySelector('.btn-loader');
    if (loading) {
        if (text) text.style.display = 'none';
        if (loader) loader.style.display = 'flex';
        btn.disabled = true;
    } else {
        if (text) text.style.display = '';
        if (loader) loader.style.display = 'none';
        btn.disabled = false;
    }
}
