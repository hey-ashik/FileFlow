<?php
require_once __DIR__ . '/../config/config.php';
$currentPage = 'profile';
$pageTitle = 'Profile Card - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';

$user = getCurrentUser();
$publicProfileUrl = null;
if (!empty($user['profile_slug'])) {
    $publicProfileUrl = APP_URL . '/u/' . $user['profile_slug'];
}
?>

<div class="container" style="max-width: 800px; padding: 2rem 1rem;">
    <h1 style="margin-bottom: 2rem;">Profile Card</h1>

    <!-- Cover Photo Section -->
    <div class="profile-card"
        style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem;">
        <h2
            style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">
            Cover Photo</h2>
        <div style="margin-bottom: 1.5rem;">
            <div id="cover-preview"
                style="width: 100%; height: 180px; border-radius: 8px; background: <?php echo empty($user['cover_path']) ? 'linear-gradient(135deg, ' . htmlspecialchars($user['color']) . ', #0f172a)' : 'transparent'; ?>; overflow: hidden; position: relative; background-size: cover; background-position: center; border: 1px solid #e2e8f0;">
                <?php if (!empty($user['cover_path'])): ?>
                    <img src="<?php echo htmlspecialchars($user['cover_path']); ?>" alt="Cover"
                        style="width: 100%; height: 100%; object-fit: cover;">
                <?php endif; ?>
            </div>
        </div>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <input type="file" id="cover-input" accept="image/jpeg,image/png,image/gif,image/webp"
                style="display: none;">
            <button type="button" class="btn btn-primary"
                onclick="document.getElementById('cover-input').click()">Upload Cover Photo</button>
            <?php if (!empty($user['cover_path'])): ?>
                <button type="button" class="btn btn-danger" onclick="removeCover()">Remove Cover</button>
            <?php endif; ?>
        </div>
        <div style="font-size: 0.85rem; color: #64748b; margin-top: 0.75rem;">Recommended size: 1000x300 pixels. Max
            size: 5MB.</div>
    </div>

    <div class="profile-card"
        style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem;">
        <h2
            style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">
            Avatar</h2>
        <div style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
            <div id="avatar-preview"
                style="width: 100px; height: 100px; flex-shrink: 0; border-radius: 50%; background: <?php echo empty($user['avatar_path']) ? htmlspecialchars($user['color']) : 'transparent'; ?>; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: white; overflow: hidden;">
                <?php if (!empty($user['avatar_path'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar_path']); ?>" alt="Avatar"
                        style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <span><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div>
                <input type="file" id="avatar-input" accept="image/jpeg,image/png,image/gif,image/webp"
                    style="display: none;">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('avatar-input').click()"
                    style="margin-bottom: 0.5rem;">Upload New Avatar</button>
                <div style="font-size: 0.85rem; color: #64748b;">Allowed formats: JPG, PNG, GIF, WEBP. Max size: 5MB.
                </div>
                <?php if (!empty($user['avatar_path'])): ?>
                    <button type="button" class="btn btn-danger" onclick="removeAvatar()"
                        style="margin-top: 0.5rem; padding: 0.25rem 0.5rem; font-size: 0.8rem;">Remove Avatar</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <form id="profile-form" style="margin-bottom: 2rem;">
        <div class="profile-card"
            style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h2
                style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">
                Personal Information</h2>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Full
                    Name</label>
                <input type="text" id="full_name" name="full_name" required
                    value="<?php echo htmlspecialchars($user['name']); ?>"
                    style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem;">
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Email
                    Address</label>
                <input type="email" disabled value="<?php echo htmlspecialchars($user['email']); ?>"
                    style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; background: #f1f5f9; color: #64748b;">
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">Email address cannot be changed.
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Phone
                    Number</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                    style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem;">
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Work
                    Experience</label>
                <textarea id="work_experience" name="work_experience" rows="3"
                    style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; resize: vertical;"><?php echo htmlspecialchars($user['work_experience'] ?? ''); ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Social
                    Links</label>
                <div id="social-links-container" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <!-- dynamic social links will go here -->
                </div>
                <button type="button" onclick="addSocialLink()"
                    style="margin-top: 0.75rem; background: transparent; border: 1px dashed #cbd5e1; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; color: #475569; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14" />
                    </svg> Add Social Link
                </button>
                <input type="hidden" id="social_links" name="social_links"
                    value="<?php echo htmlspecialchars($user['social_links'] ?? '[]'); ?>">
            </div>
        </div>

        <div class="profile-card"
            style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h2
                style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">
                Career Portfolio</h2>

            <style>
                .cv-actions {
                    display: flex;
                    gap: 1rem;
                    align-items: center;
                    flex-wrap: wrap;
                }

                @media (max-width: 600px) {
                    .cv-actions {
                        flex-direction: column !important;
                        align-items: stretch !important;
                        gap: 0.75rem !important;
                    }

                    .cv-actions .btn {
                        width: 100% !important;
                        justify-content: center !important;
                        text-align: center !important;
                        margin: 0 !important;
                    }
                }
            </style>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Upload
                    Resume/CV (PDF or DOCX)</label>
                <div class="cv-actions">
                    <input type="file" id="cv-input" accept=".pdf,.doc,.docx" style="display: none;">
                    <button type="button" class="btn btn-primary"
                        onclick="document.getElementById('cv-input').click()">Select File</button>
                    <?php if (!empty($user['cv_path'])): ?>
                        <a id="view-cv-btn" href="<?php echo htmlspecialchars($user['cv_path']); ?>" target="_blank"
                            class="btn btn-primary" style="background-color: #0ea5e9; text-decoration: none;">View Current
                            CV</a>
                        <button type="button" id="remove-cv-btn" class="btn btn-danger" onclick="removeCV()">Remove</button>
                    <?php else: ?>
                        <a id="view-cv-btn" href="#" target="_blank" class="btn btn-primary"
                            style="background-color: #0ea5e9; text-decoration: none; display: none;">View Current CV</a>
                        <button type="button" id="remove-cv-btn" class="btn btn-danger" onclick="removeCV()"
                            style="display: none;">Remove</button>
                    <?php endif; ?>
                    <span id="cv-upload-status" style="font-size: 0.875rem; color: #64748b;"></span>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Description
                    (Optional)</label>
                <textarea id="cv_description" name="cv_description" rows="2"
                    style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; resize: vertical;"
                    placeholder=""><?php echo htmlspecialchars($user['cv_description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Resume / CV
                    Button Color</label>
                <input type="color" id="cv_button_color" name="cv_button_color"
                    value="<?php echo htmlspecialchars($user['cv_button_color'] ?? '#16a34a'); ?>"
                    style="height: 40px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; padding: 2px;">
            </div>
        </div>

        <div class="profile-card"
            style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem;">
            <h2
                style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">
                Public Profile Card</h2>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <div
                    style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0; flex-wrap: wrap; gap: 1rem;">
                    <div style="flex: 1; min-width: 200px;">
                        <label
                            style="display: block; font-weight: 600; font-size: 0.95rem; margin-bottom: 0.25rem;">Public
                            Visibility</label>
                        <div style="font-size: 0.8rem; color: #64748b;">If enabled, your profile can be searched by
                            others and people can connect and message you.</div>
                    </div>
                    <label class="toggle-switch"
                        style="position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0;">
                        <input type="checkbox" id="is_public" name="is_public" value="1" <?php echo ($user['is_public'] ?? 0) == 1 ? 'checked' : ''; ?> style="opacity: 0; width: 0; height: 0;">
                        <span class="slider round"
                            style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 24px;"></span>
                    </label>
                </div>
            </div>
            <style>
                .toggle-switch input:checked+.slider {
                    background-color: #16a34a !important;
                    /* Green matching standard */
                }

                .toggle-switch input:focus+.slider {
                    box-shadow: 0 0 1px #16a34a;
                }

                .toggle-switch .slider:before {
                    position: absolute;
                    content: "";
                    height: 18px;
                    width: 18px;
                    left: 3px;
                    bottom: 3px;
                    background-color: white;
                    transition: .4s;
                    border-radius: 50%;
                }

                .toggle-switch input:checked+.slider:before {
                    transform: translateX(20px);
                }
            </style>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Custom URL
                    Slug</label>
                <div
                    style="display: flex; align-items: center; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; max-width: 100%;">
                    <div
                        style="background: #f1f5f9; padding: 0.75rem; border-right: 1px solid #cbd5e1; color: #64748b; white-space: nowrap;">
                        /u/</div>
                    <input type="text" id="profile_slug" name="profile_slug"
                        value="<?php echo htmlspecialchars($user['profile_slug'] ?? ''); ?>"
                        placeholder="my-custom-name"
                        style="width: 100%; padding: 0.75rem; border: none; font-size: 1rem; outline: none; min-width: 0;">
                </div>
                <div id="slug-status" style="font-size: 0.85rem; margin-top: 0.5rem; font-weight: 500;"></div>
            </div>

            <?php if ($publicProfileUrl): ?>
                <style>
                    .live-url-text {
                        color: #0ea5e9;
                        font-weight: 600;
                        font-size: 1.1rem;
                        text-decoration: none;
                        word-break: break-word;
                    }

                    @media (max-width: 600px) {
                        .live-url-text {
                            font-size: 0.95rem;
                        }
                    }
                </style>
                <div
                    style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; text-align: center;">
                    <p style="margin-bottom: 1rem; font-weight: 500;">Your profile is live at:</p>
                    <a href="<?php echo htmlspecialchars($publicProfileUrl); ?>" target="_blank"
                        class="live-url-text"><?php echo htmlspecialchars($publicProfileUrl); ?></a>

                    <div
                        style="display: flex; justify-content: center; gap: 2rem; margin-top: 1.5rem; align-items: center; flex-wrap: wrap;">
                        <div id="qrcode"></div>
                        <div
                            style="text-align: center; background: white; padding: 1rem 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; min-width: 140px;">
                            <div
                                style="font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">
                                Profile Visits</div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: #0ea5e9;">
                                <?php echo number_format($user['profile_visits'] ?? 0); ?>
                            </div>
                        </div>
                    </div>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 1rem;">Scan or share this QR code for your
                        profile card.</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
            <button type="submit" id="save-btn" class="btn btn-primary"
                style="padding: 0.75rem 2rem; font-size: 1rem;">Save Profile Settings</button>
        </div>
    </form>
</div>

<script>

    // Handle Social Links dynamically
    const AVAILABLE_PLATFORMS = {
        facebook: 'Facebook',
        twitter: 'Twitter (X)',
        instagram: 'Instagram',
        linkedin: 'LinkedIn',
        whatsapp: 'WhatsApp',
        website: 'Website',
        youtube: 'YouTube',
        github: 'GitHub',
        other: 'Other'
    };

    function renderSocialLinks() {
        const container = document.getElementById('social-links-container');
        const inputObj = document.getElementById('social_links');
        let links = [];

        try {
            const parsed = JSON.parse(inputObj.value);
            if (Array.isArray(parsed)) links = parsed;
        } catch (e) {
            // If it was old plain text, clear it or convert it
            if (inputObj.value && typeof inputObj.value === 'string') {
                links = [{ platform: 'other', value: inputObj.value }];
            }
        }

        container.innerHTML = '';

        links.forEach((link, index) => {
            const row = document.createElement('div');
            row.style.cssText = 'display: flex; gap: 0.5rem; align-items: stretch;';

            // Select
            let selectHtml = `<select class="social-platform" data-index="${index}" style="padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; background: white; outline: none; flex-shrink: 0;">`;
            for (const [key, label] of Object.entries(AVAILABLE_PLATFORMS)) {
                selectHtml += `<option value="${key}" ${link.platform === key ? 'selected' : ''}>${label}</option>`;
            }
            selectHtml += `</select>`;

            // Input
            let placeholder = 'https://...';
            if (link.platform === 'whatsapp') placeholder = 'e.g. +1234567890';

            row.innerHTML = `
            ${selectHtml}
            <input type="text" class="social-value" data-index="${index}" value="${link.value ? link.value.replace(/"/g, '&quot;') : ''}" placeholder="${placeholder}" style="flex-grow: 1; min-width: 0; padding: 0.5rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px;">
            <button type="button" onclick="removeSocialLink(${index})" style="background: transparent; border: none; color: #ef4444; cursor: pointer; padding: 0 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        `;

            container.appendChild(row);
        });

        const selects = container.querySelectorAll('.social-platform');
        const inputs = container.querySelectorAll('.social-value');

        [...selects, ...inputs].forEach(el => {
            el.addEventListener('change', updateSocialLinksJson);
            if (el.tagName === 'INPUT') el.addEventListener('input', updateSocialLinksJson);
        });
    }

    window.addSocialLink = function () {
        const inputObj = document.getElementById('social_links');
        let links = [];
        try { links = JSON.parse(inputObj.value); if (!Array.isArray(links)) links = []; } catch (e) { }

        links.push({ platform: 'facebook', value: '' });
        inputObj.value = JSON.stringify(links);
        renderSocialLinks();
    };

    window.removeSocialLink = function (index) {
        const inputObj = document.getElementById('social_links');
        let links = [];
        try { links = JSON.parse(inputObj.value); if (!Array.isArray(links)) links = []; } catch (e) { }

        links.splice(index, 1);
        inputObj.value = JSON.stringify(links);
        renderSocialLinks();
    };

    function updateSocialLinksJson() {
        const container = document.getElementById('social-links-container');
        const selects = container.querySelectorAll('.social-platform');
        const inputs = container.querySelectorAll('.social-value');

        let links = [];
        for (let i = 0; i < selects.length; i++) {
            links.push({
                platform: selects[i].value,
                value: inputs[i].value
            });
        }
        document.getElementById('social_links').value = JSON.stringify(links);
    }


    (function initProfile() {
        // Render Social Links
        renderSocialLinks();

        // Generate QR Code if public URL exists
        <?php if ($publicProfileUrl): ?>
        if (typeof QRCode !== 'undefined') {
            new QRCode(document.getElementById("qrcode"), {
                text: "<?php echo addslashes($publicProfileUrl); ?>",
                width: 128,
                height: 128,
                colorDark: "#0f172a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }
        <?php endif; ?>

        // Check slug available
        let slugTimeout;
        const slugInput = document.getElementById('profile_slug');
        const slugStatus = document.getElementById('slug-status');

        slugInput.addEventListener('input', (e) => {
            clearTimeout(slugTimeout);
            const val = e.target.value.trim().toLowerCase().replace(/[^a-z0-9_-]/g, '');
            e.target.value = val;

            if (val === '<?php echo addslashes($user['profile_slug'] ?? ''); ?>') {
                slugStatus.textContent = '';
                slugStatus.style.color = '';
                return;
            }

            if (val.length < 3 && val.length > 0) {
                slugStatus.textContent = 'Slug must be at least 3 characters';
                slugStatus.style.color = '#ef4444';
                return;
            }

            if (val.length >= 3) {
                slugStatus.textContent = 'Checking availability...';
                slugStatus.style.color = '#64748b';

                slugTimeout = setTimeout(async () => {
                    try {
                        const response = await fetch(`/api/profile?action=check_slug&slug=${encodeURIComponent(val)}`);
                        const data = await response.json();

                        if (data.available) {
                            slugStatus.textContent = 'URL is available!';
                            slugStatus.style.color = '#10b981';
                        } else {
                            const msg = data.message ? ` (${data.message})` : '';
                            slugStatus.textContent = 'This URL already exists' + msg;
                            slugStatus.style.color = '#ef4444';
                        }
                    } catch (err) {
                        slugStatus.textContent = '';
                    }
                }, 500);
            } else {
                slugStatus.textContent = '';
            }
        });

        // Handle CV Upload
        const cvInput = document.getElementById('cv-input');
        const cvUploadStatus = document.getElementById('cv-upload-status');
        if (cvInput) {
            cvInput.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                if (file.size > 5 * 1024 * 1024) {
                    showToast('CV size must be less than 5MB', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'upload_cv');
                formData.append('cv_file', file);
                formData.append('csrf_token', document.getElementById('csrf-token').value);

                showToast('Uploading CV...', 'info');
                if (cvUploadStatus) cvUploadStatus.textContent = 'Uploading...';

                try {
                    const response = await fetch('/api/profile', { method: 'POST', body: formData });
                    const data = await response.json();

                    if (data.success) {
                        showToast('CV uploaded successfully', 'success');
                        if (cvUploadStatus) cvUploadStatus.textContent = 'Uploaded successfully!';
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                        if (cvUploadStatus) cvUploadStatus.textContent = '';
                    }
                } catch (error) {
                    showToast('An error occurred during upload', 'error');
                    if (cvUploadStatus) cvUploadStatus.textContent = '';
                }
            });
        }

        // Handle Cover Upload
        const coverInput = document.getElementById('cover-input');
        coverInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                showToast('Image size must be less than 5MB', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'upload_cover');
            formData.append('cover', file);
            formData.append('csrf_token', document.getElementById('csrf-token').value);

            showToast('Uploading cover photo...', 'info');

            try {
                const response = await fetch('/api/profile', { method: 'POST', body: formData });
                const data = await response.json();

                if (data.success) {
                    showToast('Cover photo uploaded successfully', 'success');
                    document.getElementById('cover-preview').innerHTML = `<img src="${data.cover_path}" alt="Cover" style="width: 100%; height: 100%; object-fit: cover;">`;
                    document.getElementById('cover-preview').style.background = 'transparent';
                    setTimeout(() => window.location.reload(), 1000); // Reload to show remove button easily
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred during upload', 'error');
            }
        });

        // Handle Form Submit
        const form = document.getElementById('profile-form');
        const saveBtn = document.getElementById('save-btn');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            saveBtn.disabled = true;
            saveBtn.innerText = 'Saving...';

            const formData = new FormData(form);
            formData.append('action', 'update_profile');
            formData.append('csrf_token', document.getElementById('csrf-token').value);

            try {
                const response = await fetch('/api/profile', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred while saving', 'error');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerText = 'Save Profile Settings';
            }
        });

        // Handle Avatar Upload
        const avatarInput = document.getElementById('avatar-input');
        avatarInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                showToast('Image size must be less than 5MB', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'upload_avatar');
            formData.append('avatar', file);
            formData.append('csrf_token', document.getElementById('csrf-token').value);

            showToast('Uploading avatar...', 'info');

            try {
                const response = await fetch('/api/profile', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast('Avatar uploaded successfully', 'success');
                    document.getElementById('avatar-preview').innerHTML = `<img src="${data.avatar_path}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">`;
                    document.querySelector('.nav-avatar').innerHTML = `<div style="display:flex; width:36px; height:36px; border-radius:50%; overflow:hidden; flex-shrink:0; align-items:center; justify-content:center;"><img src="${data.avatar_path}" alt="Avatar" style="width:100%; height:100%; object-fit:cover; display:block;"></div>`;
                    document.querySelector('.nav-dropdown-avatar').innerHTML = `<div style="display:flex; width:40px; height:40px; border-radius:50%; overflow:hidden; flex-shrink:0; align-items:center; justify-content:center;"><img src="${data.avatar_path}" alt="Avatar" style="width:100%; height:100%; object-fit:cover; display:block;"></div>`;
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred during upload', 'error');
            }
        });
    })();

    async function removeAvatar() {
        if (!confirm('Are you sure you want to remove your avatar?')) return;

        const formData = new FormData();
        formData.append('action', 'remove_avatar');
        formData.append('csrf_token', document.getElementById('csrf-token').value);

        try {
            const response = await fetch('/api/profile', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showToast('Avatar removed successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            showToast('An error occurred while removing', 'error');
        }
    }
    async function removeCover() {
        if (!confirm('Are you sure you want to remove your cover photo?')) return;

        const formData = new FormData();
        formData.append('action', 'remove_cover');
        formData.append('csrf_token', document.getElementById('csrf-token').value);

        try {
            const response = await fetch('/api/profile', { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
                showToast('Cover removed successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            showToast('An error occurred while removing', 'error');
        }
    }
    async function removeCV() {
        if (!confirm('Are you sure you want to remove your CV?')) return;

        const formData = new FormData();
        formData.append('action', 'remove_cv');
        formData.append('csrf_token', document.getElementById('csrf-token').value);

        try {
            const response = await fetch('/api/profile', { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
                showToast('CV removed successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            showToast('An error occurred while removing', 'error');
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>