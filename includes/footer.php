</main>

<!-- Footer -->
<footer class="footer">
    <!--<div class="footer-inner">-->
    <!--    <div class="footer-brand">-->
    <!--        <a href="/" class="footer-logo">-->
    <!--<svg width="24" height="24" viewBox="0 0 32 32" fill="none">-->
    <!--    <rect width="32" height="32" rx="8" fill="url(#grad2)" />-->
    <!--    <path d="M10 20L16 8L22 20" stroke="white" stroke-width="2.5" stroke-linecap="round"-->
    <!--        stroke-linejoin="round" />-->
    <!--    <path d="M12 16H20" stroke="white" stroke-width="2.5" stroke-linecap="round" />-->
    <!--    <path d="M16 20V24" stroke="white" stroke-width="2.5" stroke-linecap="round" />-->
    <!--    <defs>-->
    <!--        <linearGradient id="grad2" x1="0" y1="0" x2="32" y2="32">-->
    <!--            <stop stop-color="#16a34a" />-->
    <!--            <stop offset="1" stop-color="#059669" />-->
    <!--        </linearGradient>-->
    <!--    </defs>-->
    <!--</svg>-->
    <!--            <span>File<span class="brand-accent">Flow</span></span>-->
    <!--        </a>-->
    <!--        <p class="footer-desc">Secure and simple file sharing. No registration required.</p>-->
    <!--    </div>-->
    <!--    <div class="footer-links">-->
    <!--        <div class="footer-col">-->
    <!--            <h4>Platform</h4>-->
    <!--            <a href="/">Home</a>-->
    <!--            <a href="#create-section" onclick="scrollToCreate(event)">Create Folder</a>-->
    <!--        </div>-->
    <!--        <div class="footer-col">-->
    <!--            <h4>Supported Files</h4>-->
    <!--            <span>PDF, DOCX, PPTX, XLSX</span>-->
    <!--            <span>MP3, ZIP, JPG, PNG, WEBP</span>-->
    <!--        </div>-->
    <!--    </div>-->
    <!--</div>-->
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Built for seamless sharing. Developed by <a
                href="https://wa.me/8801792250709" target="_blank"
                style="color: var(--green-400); font-weight: 600;">Ashikul Islam</a></p>
    </div>
</footer>

<!-- Custom Confirm Modal -->
<div class="modal-backdrop" id="custom-confirm-modal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px); opacity: 0; transition: opacity 0.2s ease;">
    <div class="modal-content"
        style="background: var(--white); padding: 2rem; border-radius: var(--radius-lg); width: 90%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); transform: scale(0.95); transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
            <div
                style="width: 40px; height: 40px; border-radius: 50%; background: #fef2f2; color: #ef4444; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path
                        d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                    <line x1="12" y1="9" x2="12" y2="13" />
                    <line x1="12" y1="17" x2="12.01" y2="17" />
                </svg>
            </div>
            <h3 id="confirm-modal-title"
                style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0;">Confirm Action</h3>
        </div>
        <p id="confirm-modal-message"
            style="color: var(--gray-600); margin-bottom: 1.5rem; font-size: 0.95rem; line-height: 1.5;"></p>
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" class="btn btn-outline-secondary" id="confirm-modal-cancel"
                style="padding: 0.6rem 1.2rem; background: var(--gray-50); border: 1px solid var(--gray-200); color: var(--gray-700);">Cancel</button>
            <button type="button" class="btn" id="confirm-modal-ok"
                style="padding: 0.6rem 1.2rem; background: #dc2626; color: white; border: none; box-shadow: 0 2px 8px rgba(220,38,38,0.3);">Delete</button>
        </div>
    </div>
</div>

<script>
    const APP_URL = '<?php echo APP_URL; ?>';
    var CSRF_TOKEN = '<?php echo $csrfToken; ?>';
    const MAX_FILE_SIZE = <?php echo isset($customMaxFileSize) ? $customMaxFileSize : MAX_FILE_SIZE; ?>;
    const MAX_FILES_PER_UPLOAD = <?php echo MAX_FILES_PER_UPLOAD; ?>;
    const ALLOWED_EXTENSIONS = <?php echo json_encode(array_keys(ALLOWED_EXTENSIONS)); ?>;
    const UPLOAD_CHUNK_SIZE = <?php echo getUploadChunkSize(); ?>;

    // Custom Confirm Function
    window.customConfirm = function (title, message, onConfirm, cancelText = 'Cancel', okText = 'Delete', okColor = '#dc2626') {
        const modal = document.getElementById('custom-confirm-modal');
        const modalContent = modal.querySelector('.modal-content');
        document.getElementById('confirm-modal-title').textContent = title;
        document.getElementById('confirm-modal-message').textContent = message;

        modal.style.display = 'flex';
        // Trigger reflow
        void modal.offsetWidth;
        modal.style.opacity = '1';
        modalContent.style.transform = 'scale(1)';

        const btnCancel = document.getElementById('confirm-modal-cancel');
        const btnOk = document.getElementById('confirm-modal-ok');

        btnCancel.textContent = cancelText;
        btnOk.textContent = okText;
        btnOk.style.background = okColor;
        btnOk.style.boxShadow = `0 2px 8px ${okColor}4D`;

        const cleanup = () => {
            modal.style.opacity = '0';
            modalContent.style.transform = 'scale(0.95)';
            setTimeout(() => {
                modal.style.display = 'none';
                btnCancel.removeEventListener('click', handleCancel);
                btnOk.removeEventListener('click', handleOk);
            }, 200);
        };

        const handleCancel = () => { cleanup(); };
        const handleOk = () => { cleanup(); onConfirm(); };

        btnCancel.addEventListener('click', handleCancel);
        btnOk.addEventListener('click', handleOk);
    };
</script>
<script src="/assets/js/app.js?v=3.5.3"></script>
</body>

</html>