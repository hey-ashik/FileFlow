<?php
/**
 * FileFlow - Home Page
 */

$currentPage = 'home';
$pageTitle = APP_NAME . ' - Secure & Simple File Sharing';
$pageDescription = 'Create folders, upload files, and share with anyone. No login required. Supports PDF, DOCX, PPTX, XLSX, MP3, ZIP, and images.';

try {
    $stats = getPlatformStats();
} catch (Exception $e) {
    $stats = ['folders' => 0, 'files' => 0, 'total_size' => '0 B'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero" id="hero-section">
    <div class="hero-bg">
        <div class="hero-orb hero-orb-1"></div>
        <div class="hero-orb hero-orb-2"></div>
        <div class="hero-orb hero-orb-3"></div>
    </div>
    <div class="container hero-split-container">
        <div class="hero-content-left">
            <div class="hero-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
                </svg>
                <span>Fast & Secure File Sharing</span>
            </div>
            <h1 class="hero-title">
                Share Files <span class="gradient-text">Instantly</span>,<br>
                No Login Required
            </h1>
            <p class="hero-subtitle">
                Create a unique custom folder, upload your files, and share the link. No accounts, no hassle.<br>
            </p>
            <div class="hero-actions">
                <a href="#create-section" class="btn btn-primary btn-lg" onclick="scrollToCreate(event)"
                    id="hero-cta-create">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                        <line x1="12" y1="11" x2="12" y2="17" />
                        <line x1="9" y1="14" x2="15" y2="14" />
                    </svg>
                    Create Your Folder
                </a>
                <a href="#features-section" class="btn btn-ghost btn-lg" id="hero-cta-features" onclick="scrollToFeatures(event)">
                    Learn More
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12" />
                        <polyline points="12 5 19 12 12 19" />
                    </svg>
                </a>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-value" id="stat-folders"><?php echo number_format($stats['folders']); ?></span>
                    <span class="stat-label">Folders Created</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-value" id="stat-files"><?php echo number_format($stats['files']); ?></span>
                    <span class="stat-label">Files Shared</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-value" id="stat-size"><?php echo $stats['total_size']; ?></span>
                    <span class="stat-label">Total Stored</span>
                </div>
            </div>
        </div>
        
        <div class="hero-content-right">
            <div class="video-frame-container">
                <div class="video-frame-header">
                    <div class="mac-dots">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="video-frame-title">Video Tutorial</div>
                </div>
                <div class="video-frame-body">
                    <iframe src="https://www.youtube.com/embed/dnK6af9gKqw?si=-ZZryoKQPR39e_XA" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Create Folder Section -->
<section class="section create-section" id="create-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Get Started</span>
            <h2 class="section-title">Create Your Folder</h2>
            <p class="section-subtitle">Choose a unique name for your folder. This will become your shareable URL.</p>
        </div>

        <div class="create-card" id="create-card">
            <div class="create-card-inner">
                <div class="create-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                        <line x1="12" y1="11" x2="12" y2="17" />
                        <line x1="9" y1="14" x2="15" y2="14" />
                    </svg>
                </div>
                <form id="create-folder-form" class="create-form" autocomplete="off">
                    <!-- Anti-Autofill Honeypot (Invisible to users) -->
                    <input type="text" name="email" style="display:none" aria-hidden="true">
                    <input type="password" name="password" style="display:none" aria-hidden="true">

                    <!-- Mode Selector -->
                    <div style="display: flex; justify-content: center; margin-bottom: 2.5rem;">
                        <div class="mode-selector">
                            <button type="button" class="mode-btn active" id="mode-normal"
                                onclick="setCreateMode('normal')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" style="margin-right: 8px;">
                                    <path
                                        d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                                </svg>
                                Normal
                            </button>
                            <button type="button" class="mode-btn" id="mode-advanced"
                                onclick="setCreateMode('advanced')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" style="margin-right: 8px;">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                </svg>
                                Advanced
                                <!--<span class="mode-badge">SECURE</span>-->
                            </button>
                        </div>
                    </div>

                    <div class="input-group">
                        <div class="input-prefix">
                            <span
                                class="prefix-url"><?php echo str_replace(['https://', 'http://'], '', APP_URL); ?>/</span>
                        </div>
                        <input type="text" id="fld_slug_box" name="fld_slug_val" class="input-field"
                            placeholder="folder-name" maxlength="<?php echo FOLDER_NAME_MAX_LENGTH; ?>"
                            pattern="[a-zA-Z0-9][a-zA-Z0-9\-_]*" required autocomplete="off" data-lpignore="true"
                            data-form-type="other">
                        <button type="submit" class="btn btn-primary btn-create" id="btn-create-folder">
                            <span class="btn-text">Create</span>
                            <span class="btn-loader" style="display:none;">
                                <svg width="20" height="20" viewBox="0 0 24 24" class="spinner">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none"
                                        stroke-dasharray="30 70" stroke-linecap="round" />
                                </svg>
                            </span>
                        </button>
                    </div>
                    <div class="input-hint" id="folder-hint" style="margin-bottom: 1rem;">
                        <!--<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"-->
                        <!--    stroke-width="2">-->
                        <!--    <circle cx="12" cy="12" r="10" />-->
                        <!--    <path d="M12 16v-4" />-->
                        <!--    <path d="M12 8h.01" />-->
                        <!--</svg>-->
                        Use letters, numbers, hyphens, or underscores. Min <?php echo FOLDER_NAME_MIN_LENGTH; ?>
                        characters
                    </div>

                    <div id="advanced-options-fields" class="advanced-options-container" style="display:none;">
                        <div class="advanced-options-header">
                            <!--<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>-->
                            <!--ADVANCED SETTINGS-->
                        </div>
                        <div class="advanced-fields-row">
                            <div class="advanced-field-group">
                                <label for="folder-password">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.5">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
                                    Set Password (Optional)
                                </label>
                                <input type="password" id="folder-password" name="password" class="advanced-field-input"
                                    placeholder="••••••••">
                            </div>
                            <div class="advanced-field-group">
                                <label for="folder-expiry">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.5">
                                        <circle cx="12" cy="12" r="10" />
                                        <polyline points="12 6 12 12 16 14" />
                                    </svg>
                                    Self-Destruct After
                                </label>
                                <select id="folder-expiry" name="expiry" class="advanced-field-input"
                                    style="appearance: auto; cursor: pointer;">
                                    <option value="never">Never (Default)</option>
                                    <option value="1h">1 Hour</option>
                                    <option value="24h">24 Hours</option>
                                    <option value="7d">7 Days</option>
                                    <option value="30d">30 Days</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Success State -->
                <div class="create-success" id="create-success" style="display:none;">
                    <div class="success-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                    </div>
                    <h3>Folder Created!</h3>
                    <div class="success-url-box">
                        <input type="text" id="success-url" class="success-url-input" readonly>
                        <button class="btn btn-sm btn-copy" onclick="copyFolderUrl()" id="btn-copy-url">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                            </svg>
                            Copy
                        </button>
                    </div>
                    <div class="success-actions">
                        <a href="#" class="btn btn-primary" id="btn-goto-folder" data-no-spa="true">Open Folder</a>
                        <button class="btn btn-ghost" onclick="resetCreateForm()" id="btn-create-another">Create
                            Another</button>
                    </div>
                    <div class="qr-section" id="qr-section">
                        <p class="qr-label">Scan to share</p>
                        <div id="qr-code" class="qr-code-box"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Folders / History Section -->
<section class="section history-section" id="history-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Quick Access</span>
            <h2 class="section-title">Your Recent Folders</h2>
            <p class="section-subtitle">Folders you've created are saved locally for easy access.</p>
        </div>

        <div class="history-grid" id="history-grid">
            <!-- Populated by JavaScript from localStorage -->
            <div class="history-empty" id="history-empty">
                <div class="history-empty-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                    </svg>
                </div>
                <h3>No folders yet</h3>
                <p>Create your first folder above and it will appear here.</p>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="section features-section" id="features-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Features</span>
            <h2 class="section-title">Everything You Need</h2>
            <p class="section-subtitle">Simple yet powerful file sharing with all the essentials built-in.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card" id="feature-1">
                <div class="feature-icon feature-icon-green">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                    </svg>
                </div>
                <h3>Custom Folders</h3>
                <p>Create folders with unique names that become your shareable URL instantly.</p>
            </div>

            <div class="feature-card" id="feature-2">
                <div class="feature-icon feature-icon-emerald">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="17 8 12 3 7 8" />
                        <line x1="12" y1="3" x2="12" y2="15" />
                    </svg>
                </div>
                <h3>Drag & Drop Upload</h3>
                <p>Simply drag files or click to upload. Support for multiple file types.</p>
            </div>

            <div class="feature-card" id="feature-3">
                <div class="feature-icon feature-icon-teal">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                </div>
                <h3>No Login Required</h3>
                <p>Start sharing immediately. No registration, no sign-ups, just create and share.</p>
            </div>

            <div class="feature-card" id="feature-4">
                <div class="feature-icon feature-icon-lime">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                    </svg>
                </div>
                <h3>Shareable Links</h3>
                <p>Get clean, memorable URLs. Share via link or QR code with anyone.</p>
            </div>

            <div class="feature-card" id="feature-5">
                <div class="feature-icon feature-icon-green">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                </div>
                <h3>Secure Uploads</h3>
                <p>File validation, CSRF protection, and secure handling for every upload.</p>
            </div>

            <div class="feature-card" id="feature-6">
                <div class="feature-icon feature-icon-emerald">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
                        <line x1="8" y1="21" x2="16" y2="21" />
                        <line x1="12" y1="17" x2="12" y2="21" />
                    </svg>
                </div>
                <h3>Fully Responsive</h3>
                <p>Beautiful on desktop, tablet, and mobile. Optimized for every screen size.</p>
            </div>
        </div>
    </div>
</section>

<!-- Supported Files Section -->
<section class="section filetypes-section" id="filetypes-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Compatibility</span>
            <h2 class="section-title">Supported File Types</h2>
            <p class="section-subtitle">Upload a wide range of document and media formats.</p>
        </div>

        <div class="filetype-grid">
            <div class="filetype-card">
                <div class="filetype-icon ft-pdf">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                        <polyline points="10 9 9 9 8 9" />
                    </svg>
                </div>
                <span class="filetype-name">PDF</span>
            </div>
            <div class="filetype-card">
                <div class="filetype-icon ft-doc">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                    </svg>
                </div>
                <span class="filetype-name">DOCX</span>
            </div>
            <div class="filetype-card">
                <div class="filetype-icon ft-ppt">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5">
                        <rect x="2" y="3" width="20" height="14" rx="2" />
                        <path d="M8 21h8" />
                        <path d="M12 17v4" />
                    </svg>
                </div>
                <span class="filetype-name">PPTX</span>
            </div>
            <div class="filetype-card">
                <div class="filetype-icon ft-xls">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <path d="M8 13h2v4H8z" />
                        <path d="M14 11h2v6h-2z" />
                    </svg>
                </div>
                <span class="filetype-name">XLSX</span>
            </div>
            <div class="filetype-card">
                <div class="filetype-icon ft-mp3">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5">
                        <path d="M9 18V5l12-2v13" />
                        <circle cx="6" cy="18" r="3" />
                        <circle cx="18" cy="16" r="3" />
                    </svg>
                </div>
                <span class="filetype-name">MP3</span>
            </div>
            <div class="filetype-card">
                <div class="filetype-icon ft-zip">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5">
                        <path d="M21 8v13H3V3h12l6 5z" />
                        <path d="M12 3v4h2V3" />
                        <path d="M12 9h2v2h-2z" />
                        <path d="M12 13h2v2h-2z" />
                    </svg>
                </div>
                <span class="filetype-name">ZIP</span>
            </div>
            <div class="filetype-card">
                <div class="filetype-icon ft-img">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                        <circle cx="8.5" cy="8.5" r="1.5" />
                        <polyline points="21 15 16 10 5 21" />
                    </svg>
                </div>
                <span class="filetype-name">Images</span>
            </div>
        </div>
    </div>
</section>



<!-- CTA Section -->
<section class="section cta-section" id="cta-section">
    <div class="container">
        <div class="cta-card">
            <div class="cta-content">
                <h2>Ready to Share?</h2>
                <p>Create your folder and start sharing files in seconds. No sign up needed.</p>
                <a href="#create-section" class="btn btn-white btn-lg" onclick="scrollToCreate(event)"
                    id="cta-create-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                        <line x1="12" y1="11" x2="12" y2="17" />
                        <line x1="9" y1="14" x2="15" y2="14" />
                    </svg>
                    Create Folder Now
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>