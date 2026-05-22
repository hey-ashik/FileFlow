<?php
/**
 * FileFlow - Folder View Page
 */

if (!isset($folder)) {
    header('Location: /');
    exit;
}

// Check if folder has expired
if (!empty($folder['expires_at']) && strtotime($folder['expires_at']) < time()) {
    $errorType = 'expired';
    $folderName = $folder['display_name'];
    require __DIR__ . '/error.php';
    exit;
}

// Handle Password Protection
$isOwner = (isLoggedIn() && getCurrentUser()['id'] === $folder['user_id']);
$requiresPassword = !empty($folder['password_hash']) && !$isOwner;
$passwordError = '';

if ($requiresPassword) {
    if (session_status() === PHP_SESSION_NONE)
        session_start();

    // Check if password was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['folder_password'])) {
        if (password_verify($_POST['folder_password'], $folder['password_hash'])) {
            $_SESSION['folder_auth_' . $folder['id']] = true;
        } else {
            $passwordError = 'Incorrect password. Please try again.';
        }
    }

    // Check if already authenticated in session
    if (isset($_SESSION['folder_auth_' . $folder['id']]) && $_SESSION['folder_auth_' . $folder['id']] === true) {
        $requiresPassword = false;
    }
}

// If still requires password, show the password page
if ($requiresPassword) {
    $currentPage = 'folder_auth';
    $pageTitle = 'Password Required - ' . APP_NAME;
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div
        style="position: fixed; inset: 0; z-index: -1; background: linear-gradient(135deg, var(--green-50) 0%, var(--white) 50%, var(--green-50) 100%); overflow: hidden; pointer-events: none;">
        <div class="hero-orb hero-orb-1"></div>
        <div class="hero-orb hero-orb-2"></div>
        <div class="hero-orb hero-orb-3"></div>
    </div>
    <section class="auth-page" style="position: relative; z-index: 1; background: transparent;">
        <div class="auth-card" style="text-align: center;">
            <div class="auth-icon" style="margin-bottom: 24px; background: var(--gray-50); color: var(--gray-600);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
            </div>
            <h1 style="font-size: 1.5rem; margin-bottom: 12px;">Password Protected</h1>
            <p style="color: var(--gray-500); margin-bottom: 32px;">The folder
                <strong>"<?php echo htmlspecialchars($folder['display_name']); ?>"</strong> is protected. Please enter the
                password to view its contents.
            </p>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <div class="form-input-wrap <?php echo $passwordError ? 'error' : ''; ?>"
                        style="<?php echo $passwordError ? 'border-color: #ef4444;' : ''; ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <input type="password" name="folder_password" placeholder="Enter folder password" required
                            autofocus>
                    </div>
                    <?php if ($passwordError): ?>
                        <div class="input-hint error" style="margin-top: 8px; justify-content: flex-start;">
                            <?php echo $passwordError; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 12px;">
                    Unlock Folder
                </button>
            </form>

            <div class="auth-footer">
                <a href="/" class="form-link">Back to Home</a>
            </div>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$currentPage = 'folder';
$pageTitle = htmlspecialchars($folder['display_name']) . ' - ' . APP_NAME;
$pageDescription = 'View and download files from "' . htmlspecialchars($folder['display_name']) . '" on FileFlow.';

$files = getFilesByFolderId($folder['id']);
$folderUrl = APP_URL . '/' . $folder['slug'];

$customMaxFileSize = MAX_FILE_SIZE;
if (!empty($folder['user_id'])) {
    $db = getDB();
    $stmtUser = $db->prepare("SELECT file_upload_limit_mb FROM users WHERE id = ?");
    $stmtUser->execute([$folder['user_id']]);
    $uRow = $stmtUser->fetch();
    if ($uRow && isset($uRow['file_upload_limit_mb'])) {
        $customMaxFileSize = (int) $uRow['file_upload_limit_mb'] * 1024 * 1024;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div
    style="position: fixed; inset: 0; z-index: -1; background: linear-gradient(135deg, var(--green-50) 0%, var(--white) 50%, var(--green-50) 100%); overflow: hidden; pointer-events: none;">
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-orb hero-orb-3"></div>
</div>

<!-- Folder Header -->
<section class="folder-header" id="folder-header" style="position: relative; z-index: 1; background: transparent;">
    <div class="container">
        <div class="folder-breadcrumb">
            <a href="/" class="breadcrumb-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
                Home
            </a>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6" />
            </svg>
            <span class="breadcrumb-current"><?php echo htmlspecialchars($folder['display_name']); ?></span>
        </div>

        <div class="folder-info">
            <div class="folder-info-left">
                <div class="folder-icon-large">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                    </svg>
                </div>
                <div class="folder-details">
                    <h1 class="folder-title"><?php echo htmlspecialchars($folder['display_name']); ?></h1>
                    <div class="folder-meta">
                        <span class="meta-item">
                            <?php if (!empty($folder['password_hash'])): ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" style="color: var(--amber-500); margin-right: 4px;">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                            <?php endif; ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                            Created <?php echo timeAgo($folder['created_at']); ?>
                        </span>
                        <span class="meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <polyline points="14 2 14 8 20 8" />
                            </svg>
                            <span id="file-count"><?php echo count($files); ?></span>
                            file<?php echo count($files) !== 1 ? 's' : ''; ?>
                        </span>
                        <span class="meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                            </svg>
                            <?php echo formatFileSize($folder['total_size']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="folder-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <?php if ($isOwner): ?>
                    <?php if (empty($folder['password_hash'])): ?>
                        <button class="btn btn-outline" onclick="window.openProtectModal()"
                            style="border-color: var(--amber-500); color: var(--amber-600);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                            Protect
                        </button>
                    <?php else: ?>
                        <button class="btn btn-outline" onclick="window.openUnprotectModal()"
                            style="border-color: var(--amber-500); color: var(--amber-600);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                <path d="M12 15v2" stroke-width="2" />
                            </svg>
                            Remove Password
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                <button class="btn btn-outline btn-share" onclick="shareFolderUrl('<?php echo $folderUrl; ?>')"
                    id="btn-share-folder">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                    </svg>
                    Copy Link
                </button>
                <button class="btn btn-outline" onclick="toggleQR()" id="btn-qr-toggle">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" />
                        <rect x="14" y="3" width="7" height="7" />
                        <rect x="3" y="14" width="7" height="7" />
                        <rect x="14" y="14" width="3" height="3" />
                        <path d="M20 14v3h-3" />
                        <path d="M20 20h-3" />
                    </svg>
                    QR Code
                </button>
            </div>
        </div>

        <!-- QR Code Panel -->
        <div class="qr-panel" id="qr-panel" style="display:none;">
            <div class="qr-panel-inner">
                <div id="folder-qr-code" class="qr-code-box"></div>
                <p class="qr-panel-text">Scan to open this folder</p>
                <span class="qr-panel-url"><?php echo $folderUrl; ?></span>
            </div>
        </div>
    </div>
</section>

<!-- Upload Section -->
<section class="upload-section" id="upload-section" style="position: relative; z-index: 1; background: transparent;">
    <div class="container">
        <div class="upload-card" id="upload-card">
            <div class="upload-dropzone" id="upload-dropzone">
                <div class="dropzone-content">
                    <div class="dropzone-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="17 8 12 3 7 8" />
                            <line x1="12" y1="3" x2="12" y2="15" />
                        </svg>
                    </div>
                    <h3 class="dropzone-title">Drop files here or click to upload</h3>
                    <!-- <p class="dropzone-subtitle">Supports PDF, DOCX, PPTX, XLSX, MP3, ZIP, JPG, PNG, WEBP</p> -->
                    <p class="dropzone-limit">Max <?php echo formatFileSize($customMaxFileSize); ?> per file • Up to
                        <?php echo MAX_FILES_PER_UPLOAD; ?> files at once
                    </p>
                    <input type="file" id="file-input" class="file-input" multiple
                        accept=".pdf,.docx,.doc,.ppt,.pptx,.xls,.xlsx,.mp3,.zip,.jpg,.jpeg,.png,.webp">
                </div>
                <div class="dropzone-active-overlay" id="dropzone-active">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="17 8 12 3 7 8" />
                        <line x1="12" y1="3" x2="12" y2="15" />
                    </svg>
                    <span>Release to upload</span>
                </div>
            </div>

            <!-- Upload Progress -->
            <div class="upload-progress-area" id="upload-progress-area" style="display:none;">
                <div class="progress-header">
                    <h4>Uploading...</h4>
                    <span id="upload-progress-text">0%</span>
                </div>
                <div class="progress-bar-track">
                    <div class="progress-bar-fill" id="upload-progress-bar"></div>
                </div>
                <div class="upload-file-list" id="upload-file-list"></div>
            </div>
        </div>
    </div>
</section>

<!-- Files List Section -->
<section class="files-section" id="files-section" style="position: relative; z-index: 1; background: transparent;">
    <div class="container">
        <div class="files-header">
            <h2 class="files-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                </svg>
                Files
            </h2>
        </div>

        <div class="files-grid" id="files-grid">
            <?php if (empty($files)): ?>
                <div class="files-empty" id="files-empty">
                    <div class="files-empty-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                        </svg>
                    </div>
                    <h3>No files yet</h3>
                    <p>Upload your first file using the drag & drop area above.</p>
                </div>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                    <div class="file-card file-card-<?php echo getFileCategory($file['file_extension']); ?>"
                        id="file-<?php echo $file['id']; ?>">
                        <div class="file-card-icon">
                            <span class="file-type-badge"><?php echo strtoupper($file['file_extension']); ?></span>
                        </div>
                        <div class="file-card-info">
                            <h4 class="file-name" title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                <?php echo htmlspecialchars($file['original_name']); ?>
                            </h4>
                            <div class="file-meta">
                                <span class="file-size"><?php echo formatFileSize($file['file_size']); ?></span>
                                <span class="file-date"><?php echo timeAgo($file['uploaded_at']); ?></span>
                            </div>
                        </div>
                        <div class="file-card-actions" style="display:flex; gap:6px;">
                            <a href="/api/download?id=<?php echo $file['id']; ?>&preview=1" class="btn btn-sm btn-outline"
                                title="Preview" target="_blank"
                                style="padding: 0.5rem; background: var(--gray-50); border: 1px solid var(--gray-200); color: var(--gray-600); display:flex; align-items:center; justify-content:center;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </a>
                            <a href="/api/download?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-download"
                                title="Download" id="btn-download-<?php echo $file['id']; ?>" download>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="7 10 12 15 17 10" />
                                    <line x1="12" y1="15" x2="12" y2="3" />
                                </svg>
                            </a>
                            <?php if (isLoggedIn() && getCurrentUser()['id'] === $folder['user_id']): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteFile(<?php echo $file['id']; ?>)"
                                    title="Delete File" style="padding: 0.5rem;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path
                                            d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Visits Count -->
        <div
            style="text-align: center; margin-top: 40px; color: var(--gray-400); font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 6px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            This folder has been visited <strong
                style="color: var(--gray-600);"><?php echo number_format($folder['visits'] ?? 0); ?></strong> times
        </div>
    </div>
</section>

<input type="hidden" id="folder-id" value="<?php echo $folder['id']; ?>">
<input type="hidden" id="folder-slug" value="<?php echo htmlspecialchars($folder['slug']); ?>">
<input type="hidden" id="folder-url" value="<?php echo $folderUrl; ?>">
<input type="hidden" id="is-owner"
    value="<?php echo (isLoggedIn() && getCurrentUser()['id'] === $folder['user_id']) ? '1' : '0'; ?>">

<!-- Protect Modal -->
<div id="protect-modal" class="modal-overlay" onclick="if(event.target===this) window.closeProtectModal()"
    style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding: 1rem; opacity: 0; transition: opacity 0.3s ease;">
    <div class="modal-content"
        style="background:var(--white); padding:2rem; border-radius:12px; width:100%; max-width:400px; box-shadow:0 10px 25px rgba(0,0,0,0.1); transform: translateY(-20px); transition: transform 0.3s ease;">
        <h3 style="margin-top:0; margin-bottom:1rem; font-size:1.25rem;">Protect Folder</h3>
        <p style="color:var(--gray-500); margin-bottom:1.5rem; font-size:0.9rem;">Set a password to protect this folder.
            Anyone trying to access it will need to enter this password.</p>
        <div class="form-group" style="margin-bottom:1.5rem;">
            <label style="display:block; margin-bottom:0.5rem; font-weight:500;">New Password</label>
            <input type="password" id="protect-password"
                style="width:100%; padding:0.75rem; border:1px solid #cbd5e1; border-radius:6px; font-size:1rem;"
                placeholder="Min 4 characters">
        </div>
        <div style="display:flex; justify-content:flex-end; gap:1rem;">
            <button class="btn btn-ghost" onclick="window.closeProtectModal()">Cancel</button>
            <button class="btn btn-primary" onclick="window.submitProtectFolder()">Save Password</button>
        </div>
    </div>
</div>

<!-- Unprotect Modal -->
<div id="unprotect-modal" class="modal-overlay" onclick="if(event.target===this) window.closeUnprotectModal()"
    style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding: 1rem; opacity: 0; transition: opacity 0.3s ease;">
    <div class="modal-content"
        style="background:var(--white); padding:2rem; border-radius:12px; width:100%; max-width:400px; box-shadow:0 10px 25px rgba(0,0,0,0.1); transform: translateY(-20px); transition: transform 0.3s ease;">
        <h3 style="margin-top:0; margin-bottom:1rem; font-size:1.25rem;">Remove Password</h3>
        <p style="color:var(--gray-500); margin-bottom:1.5rem; font-size:0.9rem;">Enter the current password to convert
            this back to a normal folder.</p>
        <div class="form-group" style="margin-bottom:1.5rem;">
            <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Current Password</label>
            <input type="password" id="unprotect-password"
                style="width:100%; padding:0.75rem; border:1px solid #cbd5e1; border-radius:6px; font-size:1rem;"
                placeholder="Enter passcode">
        </div>
        <div style="display:flex; justify-content:flex-end; gap:1rem;">
            <button class="btn btn-ghost" onclick="window.closeUnprotectModal()">Cancel</button>
            <button class="btn btn-primary" style="background:#ef4444;" onclick="window.submitUnprotectFolder()">Remove
                Protection</button>
        </div>
    </div>
</div>

<script>
    window.openProtectModal = function () {
        const modal = document.getElementById('protect-modal');
        const content = modal.querySelector('.modal-content');
        modal.style.display = 'flex';
        setTimeout(() => { modal.style.opacity = '1'; content.style.transform = 'translateY(0)'; }, 10);
        document.getElementById('protect-password').value = '';
        document.getElementById('protect-password').focus();
    };

    window.closeProtectModal = function () {
        const modal = document.getElementById('protect-modal');
        const content = modal.querySelector('.modal-content');
        modal.style.opacity = '0';
        content.style.transform = 'translateY(-20px)';
        setTimeout(() => { modal.style.display = 'none'; }, 300);
    };

    window.openUnprotectModal = function () {
        const modal = document.getElementById('unprotect-modal');
        const content = modal.querySelector('.modal-content');
        modal.style.display = 'flex';
        setTimeout(() => { modal.style.opacity = '1'; content.style.transform = 'translateY(0)'; }, 10);
        document.getElementById('unprotect-password').value = '';
        document.getElementById('unprotect-password').focus();
    };

    window.closeUnprotectModal = function () {
        const modal = document.getElementById('unprotect-modal');
        const content = modal.querySelector('.modal-content');
        modal.style.opacity = '0';
        content.style.transform = 'translateY(-20px)';
        setTimeout(() => { modal.style.display = 'none'; }, 300);
    };

    window.submitProtectFolder = async function () {
        const pass = document.getElementById('protect-password').value;
        if (!pass || pass.length < 4) {
            showToast('Please enter a password with at least 4 characters', 'error');
            return;
        }
        const formData = new FormData();
        formData.append('folder_id', document.getElementById('folder-id').value);
        formData.append('action', 'protect');
        formData.append('new_password', pass);
        formData.append('csrf_token', getCSRF());

        try {
            const res = await fetch('/api/update-folder-security', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.csrf_token) updateCSRF(data.csrf_token);
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Error updating folder', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        }
    };

    window.submitUnprotectFolder = async function () {
        const pass = document.getElementById('unprotect-password').value;
        if (!pass) {
            showToast('Please enter current password', 'error');
            return;
        }
        const formData = new FormData();
        formData.append('folder_id', document.getElementById('folder-id').value);
        formData.append('action', 'unprotect');
        formData.append('current_password', pass);
        formData.append('csrf_token', getCSRF());

        try {
            const res = await fetch('/api/update-folder-security', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.csrf_token) updateCSRF(data.csrf_token);
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Error updating folder', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        }
    };

    window.deleteFile = async function (fileId) {
        customConfirm(
            'Delete File',
            'Are you sure you want to delete this file? This cannot be undone.',
            async () => {
                const formData = new FormData();
                formData.append('file_id', fileId);
                formData.append('csrf_token', getCSRF());

                try {
                    const res = await fetch('/api/delete-file', { method: 'POST', body: formData });
                    const data = await res.json();

                    if (data.csrf_token) updateCSRF(data.csrf_token);

                    if (data.success) {
                        showToast('File deleted successfully.');
                        const fileCard = document.getElementById(`file-${fileId}`);
                        if (fileCard) {
                            fileCard.style.opacity = '0';
                            fileCard.style.transform = 'scale(0.9)';
                            fileCard.style.transition = 'all 0.3s ease';
                            setTimeout(() => {
                                fileCard.remove();
                                // Update file count
                                const countEl = document.getElementById('file-count');
                                if (countEl) {
                                    const current = parseInt(countEl.textContent);
                                    countEl.textContent = Math.max(0, current - 1);
                                }
                            }, 300);
                        } else {
                            setTimeout(() => location.reload(), 1000);
                        }
                    } else {
                        showToast(data.errors?.[0] || 'Delete failed', 'error');
                    }
                } catch (e) {
                    showToast('Network error', 'error');
                }
            }
        );
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>