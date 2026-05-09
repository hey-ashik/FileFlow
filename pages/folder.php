<?php
/**
 * FileFlow - Folder View Page
 */

if (!isset($folder)) {
    header('Location: /');
    exit;
}

$currentPage = 'folder';
$pageTitle = htmlspecialchars($folder['display_name']) . ' - ' . APP_NAME;
$pageDescription = 'View and download files from "' . htmlspecialchars($folder['display_name']) . '" on FileFlow.';

$files = getFilesByFolderId($folder['id']);
$folderUrl = APP_URL . '/' . $folder['slug'];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Folder Header -->
<section class="folder-header" id="folder-header">
    <div class="container">
        <div class="folder-breadcrumb">
            <a href="/" class="breadcrumb-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Home
            </a>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <span class="breadcrumb-current"><?php echo htmlspecialchars($folder['display_name']); ?></span>
        </div>
        
        <div class="folder-info">
            <div class="folder-info-left">
                <div class="folder-icon-large">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <div class="folder-details">
                    <h1 class="folder-title"><?php echo htmlspecialchars($folder['display_name']); ?></h1>
                    <div class="folder-meta">
                        <span class="meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            Created <?php echo timeAgo($folder['created_at']); ?>
                        </span>
                        <span class="meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <span id="file-count"><?php echo count($files); ?></span> file<?php echo count($files) !== 1 ? 's' : ''; ?>
                        </span>
                        <span class="meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                            <?php echo formatFileSize($folder['total_size']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="folder-actions">
                <button class="btn btn-outline btn-share" onclick="shareFolderUrl('<?php echo $folderUrl; ?>')" id="btn-share-folder">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    Copy Link
                </button>
                <button class="btn btn-outline" onclick="toggleQR()" id="btn-qr-toggle">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="3" height="3"/><path d="M20 14v3h-3"/><path d="M20 20h-3"/></svg>
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
<section class="upload-section" id="upload-section">
    <div class="container">
        <div class="upload-card" id="upload-card">
            <div class="upload-dropzone" id="upload-dropzone">
                <div class="dropzone-content">
                    <div class="dropzone-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                    </div>
                    <h3 class="dropzone-title">Drop files here or click to upload</h3>
                    <p class="dropzone-subtitle">Supports PDF, DOCX, PPTX, XLSX, MP3, ZIP, JPG, PNG, WEBP</p>
                    <p class="dropzone-limit">Max <?php echo formatFileSize(MAX_FILE_SIZE); ?> per file • Up to <?php echo MAX_FILES_PER_UPLOAD; ?> files at once</p>
                    <input type="file" id="file-input" class="file-input" multiple accept=".pdf,.docx,.doc,.ppt,.pptx,.xls,.xlsx,.mp3,.zip,.jpg,.jpeg,.png,.webp">
                </div>
                <div class="dropzone-active-overlay" id="dropzone-active">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
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
<section class="files-section" id="files-section">
    <div class="container">
        <div class="files-header">
            <h2 class="files-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Files
            </h2>
        </div>
        
        <div class="files-grid" id="files-grid">
            <?php if (empty($files)): ?>
            <div class="files-empty" id="files-empty">
                <div class="files-empty-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <h3>No files yet</h3>
                <p>Upload your first file using the drag & drop area above.</p>
            </div>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                <div class="file-card file-card-<?php echo getFileCategory($file['file_extension']); ?>" id="file-<?php echo $file['id']; ?>">
                    <div class="file-card-icon">
                        <span class="file-type-badge"><?php echo strtoupper($file['file_extension']); ?></span>
                    </div>
                    <div class="file-card-info">
                        <h4 class="file-name" title="<?php echo htmlspecialchars($file['original_name']); ?>"><?php echo htmlspecialchars($file['original_name']); ?></h4>
                        <div class="file-meta">
                            <span class="file-size"><?php echo formatFileSize($file['file_size']); ?></span>
                            <span class="file-date"><?php echo timeAgo($file['uploaded_at']); ?></span>
                        </div>
                    </div>
                    <div class="file-card-actions">
                        <a href="/api/download?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-download" title="Download" id="btn-download-<?php echo $file['id']; ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </a>
                        <?php if (isLoggedIn() && getCurrentUser()['id'] === $folder['user_id']): ?>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteFile(<?php echo $file['id']; ?>)" title="Delete File" style="padding: 0.5rem;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Visits Count -->
        <div style="text-align: center; margin-top: 40px; color: var(--gray-400); font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 6px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            This folder has been visited <strong style="color: var(--gray-600);"><?php echo number_format($folder['visits'] ?? 0); ?></strong> times
        </div>
    </div>
</section>

<input type="hidden" id="folder-id" value="<?php echo $folder['id']; ?>">
<input type="hidden" id="folder-slug" value="<?php echo htmlspecialchars($folder['slug']); ?>">
<input type="hidden" id="folder-url" value="<?php echo $folderUrl; ?>">

<script>
async function deleteFile(fileId) {
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
                if(data.success) {
                    showToast('File deleted successfully.');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.errors?.[0] || 'Delete failed', 'error');
                }
            } catch(e) {
                showToast('Network error', 'error');
            }
        }
    );
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
