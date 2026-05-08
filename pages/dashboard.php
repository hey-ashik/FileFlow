<?php
$currentPage = 'dashboard';
$pageTitle = 'Dashboard - ' . APP_NAME;
$pageDescription = 'Manage your folders and files from your FileFlow dashboard.';
$user = getCurrentUser();
$stats = getUserDashboardStats($user['id']);
$folders = getUserFolders($user['id']);
$uploadStats = getUploadStats($user['id'], 7);
$fileTypeStats = getFileTypeStats($user['id']);

// Get user limit
$db = getDB();
$stmt = $db->prepare("SELECT space_limit_mb FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$uRow = $stmt->fetch();
$limitMb = $uRow ? (int) $uRow['space_limit_mb'] : 100;
$limitBytes = $limitMb * 1024 * 1024;
$usagePct = $limitBytes > 0 ? min(100, round(($stats['total_size'] / $limitBytes) * 100)) : 0;

require_once __DIR__ . '/../includes/header.php';
?>

<section class="dashboard" id="dashboard">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dash-header">
            <div class="dash-welcome">
                <h1>Welcome back, <span class="gradient-text"><?php echo htmlspecialchars($user['name']); ?></span></h1>
                <p>Here's your file sharing overview</p>
            </div>
            <div class="dash-clock" id="dash-clock">
                <div class="clock-time" id="clock-time">--:--:--</div>
                <div class="clock-date" id="clock-date">Loading...</div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="dash-stats">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background:var(--green-50);color:var(--green-600)">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                    </svg>
                </div>
                <div class="dash-stat-info">
                    <span class="dash-stat-value"><?php echo $stats['folders']; ?></span>
                    <span class="dash-stat-label">Total Folders</span>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background:#eff6ff;color:#3b82f6">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                    </svg>
                </div>
                <div class="dash-stat-info">
                    <span class="dash-stat-value"><?php echo $stats['files']; ?></span>
                    <span class="dash-stat-label">Total Files</span>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background:#f5f3ff;color:#8b5cf6">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                    </svg>
                </div>
                <div class="dash-stat-info" style="flex: 1; width: 100%;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 4px;">
                        <div>
                            <span class="dash-stat-value"
                                style="font-size: 1.25rem;"><?php echo $stats['total_size_formatted']; ?></span>
                            <span class="dash-stat-label" style="font-size: 0.75rem;">of <?php echo $limitMb; ?> MB
                                used</span>
                        </div>
                        <span
                            style="font-size: 0.75rem; font-weight: 600; color: <?php echo $usagePct > 90 ? '#ef4444' : '#8b5cf6'; ?>;"><?php echo $usagePct; ?>%</span>
                    </div>
                    <div
                        style="width: 100%; height: 6px; background: #ede9fe; border-radius: 99px; overflow: hidden; margin-bottom: 6px;">
                        <div
                            style="height: 100%; background: <?php echo $usagePct > 90 ? '#ef4444' : '#8b5cf6'; ?>; width: <?php echo $usagePct; ?>%; border-radius: 99px;">
                        </div>
                    </div>
                    <span style="font-size: 0.7rem; color: #6b7280;">Delete files to free up space</span>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background:#fdf2f8;color:#ec4899">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="7 10 12 15 17 10" />
                        <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                </div>
                <div class="dash-stat-info">
                    <span class="dash-stat-value"><?php echo $stats['downloads']; ?></span>
                    <span class="dash-stat-label">Downloads</span>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="dash-charts">
            <div class="dash-chart-card">
                <div class="dash-card-header">
                    <h3>Upload Activity (Last 7 Days)</h3>
                </div>
                <div class="chart-container" id="upload-chart-container">
                    <canvas id="upload-chart" width="600" height="260"></canvas>
                </div>
            </div>
            <div class="dash-chart-card dash-chart-small">
                <div class="dash-card-header">
                    <h3>File Types</h3>
                </div>
                <div class="filetype-stats" id="filetype-stats">
                    <?php if (empty($fileTypeStats)): ?>
                        <div class="filetype-empty">No files uploaded yet</div>
                    <?php else: ?>
                        <?php
                        $typeColors = ['pdf' => '#ef4444', 'docx' => '#3b82f6', 'doc' => '#3b82f6', 'pptx' => '#f97316', 'ppt' => '#f97316', 'xlsx' => '#16a34a', 'xls' => '#16a34a', 'mp3' => '#8b5cf6', 'zip' => '#eab308', 'jpg' => '#ec4899', 'jpeg' => '#ec4899', 'png' => '#06b6d4', 'webp' => '#14b8a6'];
                        $maxCount = max(array_column($fileTypeStats, 'count'));
                        foreach ($fileTypeStats as $ft):
                            $color = $typeColors[$ft['file_extension']] ?? '#6b7280';
                            $pct = $maxCount > 0 ? round(($ft['count'] / $maxCount) * 100) : 0;
                            ?>
                            <div class="filetype-stat-row">
                                <div class="filetype-stat-label">
                                    <span class="filetype-dot" style="background:<?php echo $color; ?>"></span>
                                    <span>.<?php echo strtoupper($ft['file_extension']); ?></span>
                                </div>
                                <div class="filetype-stat-bar-wrap">
                                    <div class="filetype-stat-bar"
                                        style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>"></div>
                                </div>
                                <span class="filetype-stat-count"><?php echo $ft['count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Create Folder Section -->
        <div class="dash-folders-section" style="margin-bottom: 28px;" id="create-section">
            <div class="dash-card-header">
                <h3>Create New Folder</h3>
            </div>
            <div style="padding: 24px;">
                <div class="create-card" id="create-card"
                    style="box-shadow: none; border: none; padding: 0; margin: 0; max-width: 100%;">
                    <div class="create-card-inner">
                        <form id="create-folder-form" class="create-form" autocomplete="off" style="margin-top: 0;">
                            <div class="input-group">
                                <div class="input-prefix">
                                    <span
                                        class="prefix-url"><?php echo str_replace(['https://', 'http://'], '', APP_URL); ?>/</span>
                                </div>
                                <input type="text" id="folder-name-input" name="folder_name" class="input-field"
                                    placeholder="folder-name" maxlength="<?php echo FOLDER_NAME_MAX_LENGTH; ?>"
                                    pattern="[a-zA-Z0-9][a-zA-Z0-9_-]*" required autocomplete="off">
                                <button type="submit" class="btn btn-primary btn-create" id="btn-create-folder">
                                    <span class="btn-text">Create</span>
                                    <span class="btn-loader" style="display:none;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" class="spinner">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"
                                                fill="none" stroke-dasharray="30 70" stroke-linecap="round" />
                                        </svg>
                                    </span>
                                </button>
                            </div>
                            <div class="input-hint" id="folder-hint"
                                style="text-align: left; justify-content: flex-start; margin-top: 8px;">
                                Use letters, numbers, hyphens, or underscores. Min <?php echo FOLDER_NAME_MIN_LENGTH; ?>
                                characters
                            </div>
                        </form>

                        <!-- Success State -->
                        <div class="create-success" id="create-success"
                            style="display:none; text-align: left; padding: 16px; background: var(--green-50); border: 1px solid var(--green-200); border-radius: var(--radius); margin-top: 16px;">
                            <div
                                style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 250px;">
                                    <h4
                                        style="color: var(--green-700); margin-bottom: 8px; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                            <polyline points="22 4 12 14.01 9 11.01" />
                                        </svg>
                                        Folder Created Successfully!
                                    </h4>
                                    <div class="success-url-box" style="margin-bottom: 0;">
                                        <input type="text" id="success-url" class="success-url-input"
                                            style="background: var(--white);" readonly>
                                        <button class="btn btn-sm btn-copy" onclick="copyFolderUrl()"
                                            id="btn-copy-url">Copy</button>
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 8px; min-width: 140px;">
                                    <a href="#" class="btn btn-primary btn-sm" style="justify-content: center;"
                                        id="btn-goto-folder">Open Folder</a>
                                    <button class="btn btn-ghost btn-sm" style="justify-content: center;"
                                        onclick="resetCreateForm()" id="btn-create-another">Create Another</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Folders List -->
        <div class="dash-folders-section">
            <div class="dash-card-header">
                <h3>Your Folders</h3>
                <button onclick="document.getElementById('folder-name-input').focus()" class="btn btn-sm btn-primary">+
                    New Folder</button>
            </div>
            <div class="dash-folders-grid" id="dash-folders">
                <?php if (empty($folders)): ?>
                    <div class="dash-empty">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                            style="opacity:.3">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                        </svg>
                        <h4>No folders yet</h4>
                        <p>Create your first folder to get started</p>
                        <button onclick="document.getElementById('folder-name-input').focus()"
                            class="btn btn-primary btn-sm">Create Folder</button>
                    </div>
                <?php else: ?>
                    <?php foreach ($folders as $f): ?>
                        <a href="/<?php echo htmlspecialchars($f['slug']); ?>/" class="dash-folder-card"
                            id="dash-folder-<?php echo $f['id']; ?>">
                            <div class="dash-folder-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                                </svg>
                            </div>
                            <div class="dash-folder-info" style="flex: 1;">
                                <div class="dash-folder-name"><?php echo htmlspecialchars($f['display_name']); ?></div>
                                <div class="dash-folder-meta">
                                    <span><?php echo $f['total_files']; ?> files</span>
                                    <span><?php echo formatFileSize($f['total_size']); ?></span>
                                    <span><?php echo timeAgo($f['created_at']); ?></span>
                                </div>
                            </div>
                            <button class="btn-icon"
                                style="color: var(--red-500); border: none; background: transparent; padding: 8px; cursor: pointer; transition: all 0.2s;"
                                onclick="event.preventDefault(); deleteMyFolder(<?php echo $f['id']; ?>)" title="Delete Folder">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                </svg>
                            </button>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
    // Chart data from PHP
    const uploadChartData = <?php echo json_encode($uploadStats); ?>;

    // Initialize dashboard clock
    function initDashClock() {
        function updateClock() {
            const now = new Date();
            const timeOpts = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            const dateOpts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const timeEl = document.getElementById('clock-time');
            const dateEl = document.getElementById('clock-date');
            if (timeEl) timeEl.textContent = now.toLocaleTimeString(undefined, timeOpts);
            if (dateEl) dateEl.textContent = now.toLocaleDateString(undefined, dateOpts);
        }
        updateClock();
        setInterval(updateClock, 1000);
    }

    // Draw bar chart on canvas
    function drawUploadChart() {
        const canvas = document.getElementById('upload-chart');
        if (!canvas || !uploadChartData.length) return;
        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.parentElement.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = 260 * dpr;
        canvas.style.width = rect.width + 'px';
        canvas.style.height = '260px';
        ctx.scale(dpr, dpr);

        const w = rect.width, h = 260;
        const padding = { top: 20, right: 20, bottom: 40, left: 50 };
        const chartW = w - padding.left - padding.right;
        const chartH = h - padding.top - padding.bottom;
        const maxVal = Math.max(...uploadChartData.map(d => d.files), 1);
        const barCount = uploadChartData.length;
        const gap = 12;
        const barW = Math.min((chartW - gap * (barCount + 1)) / barCount, 60);
        const totalBarArea = barCount * barW + (barCount + 1) * gap;
        const offsetX = padding.left + (chartW - totalBarArea) / 2 + gap;

        // Background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, w, h);

        // Grid lines
        ctx.strokeStyle = '#f3f4f6';
        ctx.lineWidth = 1;
        const gridLines = 4;
        for (let i = 0; i <= gridLines; i++) {
            const y = padding.top + (chartH / gridLines) * i;
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(w - padding.right, y);
            ctx.stroke();
            // Y labels
            ctx.fillStyle = '#9ca3af';
            ctx.font = '11px Inter, sans-serif';
            ctx.textAlign = 'right';
            const label = Math.round(maxVal - (maxVal / gridLines) * i);
            ctx.fillText(label, padding.left - 8, y + 4);
        }

        // Bars with animation-ready values
        const gradient = ctx.createLinearGradient(0, padding.top, 0, h - padding.bottom);
        gradient.addColorStop(0, '#16a34a');
        gradient.addColorStop(1, '#059669');

        uploadChartData.forEach((d, i) => {
            const x = offsetX + i * (barW + gap);
            const barH = maxVal > 0 ? (d.files / maxVal) * chartH : 0;
            const y = padding.top + chartH - barH;

            // Bar shadow
            ctx.fillStyle = 'rgba(22, 163, 74, 0.08)';
            ctx.beginPath();
            ctx.roundRect(x + 2, y + 2, barW, barH, [6, 6, 0, 0]);
            ctx.fill();

            // Bar
            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.roundRect(x, y, barW, barH, [6, 6, 0, 0]);
            ctx.fill();

            // Value on top
            if (d.files > 0) {
                ctx.fillStyle = '#16a34a';
                ctx.font = 'bold 12px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(d.files, x + barW / 2, y - 6);
            }

            // X label
            ctx.fillStyle = '#6b7280';
            ctx.font = '12px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(d.label, x + barW / 2, h - padding.bottom + 20);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initDashClock();
        drawUploadChart();
        window.addEventListener('resize', drawUploadChart);
    });

    async function deleteMyFolder(folderId) {
        customConfirm(
            'Delete Folder',
            'Are you sure you want to delete this folder and ALL its files? This will free up space.',
            async () => {
                const formData = new FormData();
                formData.append('folder_id', folderId);
                formData.append('csrf_token', getCSRF());

                try {
                    const res = await fetch('/api/user/delete-folder', { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) {
                        showToast('Folder deleted successfully.');
                        setTimeout(() => location.reload(), 1000);
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