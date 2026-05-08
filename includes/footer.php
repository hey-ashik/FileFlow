    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <a href="/" class="footer-logo">
                    <svg width="24" height="24" viewBox="0 0 32 32" fill="none">
                        <rect width="32" height="32" rx="8" fill="url(#grad2)"/>
                        <path d="M10 20L16 8L22 20" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 16H20" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M16 20V24" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                        <defs>
                            <linearGradient id="grad2" x1="0" y1="0" x2="32" y2="32">
                                <stop stop-color="#16a34a"/>
                                <stop offset="1" stop-color="#059669"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <span>File<span class="brand-accent">Flow</span></span>
                </a>
                <p class="footer-desc">Secure and simple file sharing. No registration required.</p>
            </div>
            <div class="footer-links">
                <div class="footer-col">
                    <h4>Platform</h4>
                    <a href="/">Home</a>
                    <a href="#create-section" onclick="scrollToCreate(event)">Create Folder</a>
                </div>
                <div class="footer-col">
                    <h4>Supported Files</h4>
                    <span>PDF, DOCX, PPTX, XLSX</span>
                    <span>MP3, ZIP, JPG, PNG, WEBP</span>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Built for seamless sharing.</p>
        </div>
    </footer>

    <script>
        const APP_URL = '<?php echo APP_URL; ?>';
        const CSRF_TOKEN = '<?php echo $csrfToken; ?>';
        const MAX_FILE_SIZE = <?php echo MAX_FILE_SIZE; ?>;
        const MAX_FILES_PER_UPLOAD = <?php echo MAX_FILES_PER_UPLOAD; ?>;
        const ALLOWED_EXTENSIONS = <?php echo json_encode(array_keys(ALLOWED_EXTENSIONS)); ?>;
    </script>
    <script src="/assets/js/app.js"></script>
</body>
</html>
