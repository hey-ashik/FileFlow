<?php
$currentPage = 'admin';
$pageTitle = 'Admin Dashboard - ' . APP_NAME;
$pageDescription = 'Admin panel for managing users and system resources.';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$view = $_GET['view'] ?? 'overview';

// Common Stats
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProfiles = $db->query("SELECT COUNT(*) FROM users WHERE profile_slug IS NOT NULL AND profile_slug != ''")->fetchColumn();
$totalFolders = $db->query("SELECT COUNT(*) FROM folders")->fetchColumn();
$totalFiles = $db->query("SELECT COUNT(*) FROM files")->fetchColumn();

$users = [];
$unassignedFolders = [];
$recentFolders = [];

if ($view === 'overview') {
    // Get recent folders instead of files as requested
    try {
        $recentFolders = $db->query("SELECT f.*, u.full_name, u.email FROM folders f LEFT JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT 10")->fetchAll();
    } catch (Exception $e) {
        $recentFolders = [];
    }
    
    // Chart data for last 7 days folders/files activity
    $chartDataFolders = [];
    $chartDataFiles = [];
    $chartLabels = [];
    for($i=6; $i>=0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chartLabels[] = date('M d', strtotime($date));
        
        try {
            $stmtF = $db->prepare("SELECT COUNT(*) FROM folders WHERE DATE(created_at) = ?");
            $stmtF->execute([$date]);
            $chartDataFolders[] = $stmtF->fetchColumn();
            
            $stmtFi = $db->prepare("SELECT COUNT(*) FROM files WHERE DATE(uploaded_at) = ?");
            $stmtFi->execute([$date]);
            $chartDataFiles[] = $stmtFi->fetchColumn();
        } catch (Exception $e) {
            $chartDataFolders[] = 0;
            $chartDataFiles[] = 0;
        }
    }
} elseif ($view === 'users' || $view === 'profiles') {
    $users = $db->query("
        SELECT u.*,
            (SELECT COUNT(*) FROM folders WHERE user_id = u.id) as folder_count,
            (SELECT COALESCE(SUM(total_size), 0) FROM folders WHERE user_id = u.id) as total_used_size
        FROM users u
        ORDER BY u.created_at DESC
    ")->fetchAll();
} elseif ($view === 'unassigned') {
    $unassignedFolders = $db->query("
        SELECT * FROM folders WHERE user_id IS NULL ORDER BY created_at DESC
    ")->fetchAll();
}

?>

<style>
/* Admin Layout Variables */
:root {
    --admin-sidebar-width: 280px;
    --admin-bg: #f1f5f9; /* Slate 100 like tailadmin */
    --admin-card-bg: #ffffff;
    --admin-border: #e2e8f0;
    --admin-text-main: #1c2434;
    --admin-text-muted: #64748b;
    --admin-primary: #3c50e0;
}

body.page-inner {
    background-color: var(--admin-bg) !important;
}

/* Main Layout */
.admin-layout {
    display: flex;
    min-height: calc(100vh - 70px);
    background: var(--admin-bg);
    position: relative;
}

/* Sidebar */
.admin-sidebar {
    width: var(--admin-sidebar-width);
    background: var(--admin-card-bg);
    border-right: 1px solid var(--admin-border);
    position: fixed;
    top: 0;
    padding-top: 80px; /* fills top gap behind header */
    bottom: 0;
    left: 0;
    overflow-y: auto;
    z-index: 50;
    transition: transform 0.3s ease-in-out;
    display: flex;
    flex-direction: column;
}

/* Hide Footer on Admin Page */
footer, .footer {
    display: none !important;
}

.admin-sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--admin-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.admin-sidebar-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--admin-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1.5rem 1.5rem 0.5rem;
}

.admin-nav {
    display: flex;
    flex-direction: column;
    padding: 0.5rem 1rem;
    gap: 0.25rem;
}

.admin-nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem 1rem;
    color: var(--admin-text-muted);
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.admin-nav-item:hover {
    background: var(--admin-bg);
    color: var(--admin-text-main);
}

.admin-nav-item.active {
    background: #e0e7ff; /* light indigo */
    color: var(--admin-primary);
}

.admin-nav-icon {
    width: 20px;
    height: 20px;
}

/* Main Content Area */
.admin-main-content {
    flex: 1;
    margin-left: var(--admin-sidebar-width);
    padding: 2rem;
    transition: margin-left 0.3s ease-in-out;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

/* Top Bar in Content */
.admin-top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.admin-page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--admin-text-main);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.mobile-sidebar-toggle {
    display: none;
    background: var(--admin-card-bg);
    border: 1px solid var(--admin-border);
    color: var(--admin-text-main);
    padding: 0.5rem;
    border-radius: 6px;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

/* Stat Cards (TailAdmin Style) */
.admin-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.admin-stat-card {
    background: var(--admin-card-bg);
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid var(--admin-border);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 1.25rem;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.admin-stat-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
    transform: translateY(-2px);
}

.admin-stat-icon-wrapper {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.admin-stat-content {
    flex: 1;
}

.admin-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--admin-text-main);
    line-height: 1.2;
    margin-bottom: 0.25rem;
}

.admin-stat-title {
    font-size: 0.875rem;
    color: var(--admin-text-muted);
    font-weight: 500;
}

/* Admin Sections (Tables) */
.admin-section {
    background: var(--admin-card-bg);
    border-radius: 10px;
    border: 1px solid var(--admin-border);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
    overflow: hidden;
}

.admin-section-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--admin-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.admin-section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--admin-text-main);
    margin: 0;
}

.admin-section-subtitle {
    font-size: 0.875rem;
    color: var(--admin-text-muted);
    margin-top: 0.25rem;
}

.admin-search-box {
    position: relative;
    width: 100%;
    max-width: 320px;
}

.admin-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--admin-text-muted);
}

.admin-search-input {
    width: 100%;
    padding: 0.5rem 1rem 0.5rem 2.5rem;
    border: 1px solid var(--admin-border);
    border-radius: 6px;
    outline: none;
    font-size: 0.875rem;
    transition: border-color 0.2s;
}

.admin-search-input:focus {
    border-color: var(--admin-primary);
}

/* Responsive Table container */
.admin-table-container {
    overflow-x: auto;
    overflow-y: auto;
    max-height: 500px;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.admin-table th {
    padding: 1rem 1.5rem;
    font-weight: 600;
    color: var(--admin-text-muted);
    font-size: 0.875rem;
    background: #f8fafc;
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 1px 0 var(--admin-border);
}

.admin-table td {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--admin-border);
    color: var(--admin-text-main);
}

.admin-table tr:hover {
    background: #f8fafc;
}

.admin-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    border: 1px solid transparent;
}

.admin-btn-primary {
    background: var(--admin-primary);
    color: white;
}
.admin-btn-primary:hover {
    background: #3141b8;
}

.admin-btn-outline {
    background: transparent;
    border-color: var(--admin-border);
    color: var(--admin-text-main);
}
.admin-btn-outline:hover {
    background: var(--admin-bg);
}

/* Sidebar Overlay */
.admin-sidebar-overlay {
    display: none;
    position: fixed;
    top: 70px;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.5);
    z-index: 40;
    backdrop-filter: blur(2px);
}

/* Responsive */
@media (max-width: 1024px) {
    .admin-sidebar {
        transform: translateX(-100%);
    }
    .admin-sidebar.sidebar-open {
        transform: translateX(0);
    }
    .admin-main-content {
        margin-left: 0;
        padding: 1.5rem;
    }
    .mobile-sidebar-toggle {
        display: inline-flex;
    }
    .admin-sidebar-overlay.overlay-open {
        display: block;
    }
}

@media (max-width: 640px) {
    .admin-main-content {
        padding: 1rem;
    }
    .admin-stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="admin-layout" id="admin-panel">
    <!-- Mobile Overlay -->
    <div class="admin-sidebar-overlay" id="admin-sidebar-overlay" onclick="toggleAdminSidebar()"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar-title">Menu</div>
        <nav class="admin-nav">
            <a href="/admin?view=overview" class="admin-nav-item <?= $view === 'overview' ? 'active' : '' ?>">
                <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Dashboard Overview
            </a>
            <a href="/admin?view=users" class="admin-nav-item <?= $view === 'users' ? 'active' : '' ?>">
                <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                User Management
            </a>
            <a href="/admin?view=profiles" class="admin-nav-item <?= $view === 'profiles' ? 'active' : '' ?>">
                <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                User Profile Cards
            </a>
            <a href="/admin?view=unassigned" class="admin-nav-item <?= $view === 'unassigned' ? 'active' : '' ?>">
                <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
                Unassigned Folders
            </a>
        </nav>
        
        <div class="admin-sidebar-title" style="margin-top: 1rem;">Support</div>
        <nav class="admin-nav">
            <a href="/messages" class="admin-nav-item">
                <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                Messages
            </a>
            <a href="javascript:void(0)" class="admin-nav-item" onclick="alert('Chat support coming soon!')">
                <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
                Support Chat
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main-content">
        
        <div class="admin-top-bar">
            <h1 class="admin-page-title">
                <button class="mobile-sidebar-toggle" onclick="toggleAdminSidebar()" aria-label="Toggle Sidebar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <?php
                if ($view === 'users') echo 'User Management';
                elseif ($view === 'profiles') echo 'User Profile Cards';
                elseif ($view === 'unassigned') echo 'Unassigned Folders';
                else echo 'Dashboard Overview';
                ?>
            </h1>
            <button onclick="location.reload()" class="admin-btn admin-btn-outline" style="background: var(--admin-card-bg);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                Refresh Data
            </button>
        </div>

        <?php if ($view === 'overview'): ?>
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon-wrapper" style="background: #eff6ff; color: #3b82f6;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-value"><?= number_format($totalUsers) ?></div>
                        <div class="admin-stat-title">Total Users</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon-wrapper" style="background: #f0fdf4; color: #22c55e;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-value"><?= number_format($totalProfiles) ?></div>
                        <div class="admin-stat-title">Profile Cards</div>
                    </div>
                </div>

                <div class="admin-stat-card">
                    <div class="admin-stat-icon-wrapper" style="background: #fdf2f8; color: #ec4899;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-value"><?= number_format($totalFolders) ?></div>
                        <div class="admin-stat-title">Total Folders</div>
                    </div>
                </div>

                <div class="admin-stat-card">
                    <div class="admin-stat-icon-wrapper" style="background: #fefce8; color: #eab308;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-value"><?= number_format($totalFiles) ?></div>
                        <div class="admin-stat-title">Total Files</div>
                    </div>
                </div>
            </div>

            <div class="admin-section" style="padding: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                    <h3 class="admin-section-title" id="chart-title">System Performance (Folders Created)</h3>
                    <select id="chart-selector" class="admin-search-input" style="width: auto; padding: 0.4rem 1rem; cursor: pointer; border-radius: 6px; border: 1px solid var(--admin-border);">
                        <option value="folders">Folders Created</option>
                        <option value="files">Files Uploaded</option>
                    </select>
                </div>
                <div style="width: 100%; height: 300px; position: relative;">
                    <canvas id="uploadsChart"></canvas>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                (function() {
                    const chartDataFolders = <?= json_encode($chartDataFolders) ?>;
                    const chartDataFiles = <?= json_encode($chartDataFiles) ?>;
                    const chartLabels = <?= json_encode($chartLabels) ?>;
                    
                    const ctx = document.getElementById('uploadsChart').getContext('2d');
                    let adminChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: chartLabels,
                            datasets: [{
                                label: 'Folders Created',
                                data: chartDataFolders,
                                borderColor: '#3c50e0',
                                backgroundColor: 'rgba(60, 80, 224, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#3c50e0',
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { 
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#1c2434',
                                    padding: 12,
                                    titleFont: { size: 13 },
                                    bodyFont: { size: 14, weight: 'bold' },
                                    displayColors: false
                                }
                            },
                            scales: { 
                                y: { 
                                    beginAtZero: true, 
                                    ticks: { precision: 0, color: '#64748b' },
                                    grid: { color: '#e2e8f0', drawBorder: false }
                                },
                                x: {
                                    ticks: { color: '#64748b' },
                                    grid: { display: false, drawBorder: false }
                                }
                            }
                        }
                    });

                    document.getElementById('chart-selector').addEventListener('change', function(e) {
                        const val = e.target.value;
                        if(val === 'folders') {
                            document.getElementById('chart-title').textContent = 'System Performance (Folders Created)';
                            adminChart.data.datasets[0].label = 'Folders Created';
                            adminChart.data.datasets[0].data = chartDataFolders;
                            adminChart.data.datasets[0].borderColor = '#3c50e0';
                            adminChart.data.datasets[0].backgroundColor = 'rgba(60, 80, 224, 0.1)';
                            adminChart.data.datasets[0].pointBackgroundColor = '#3c50e0';
                        } else {
                            document.getElementById('chart-title').textContent = 'System Performance (Files Uploaded)';
                            adminChart.data.datasets[0].label = 'Files Uploaded';
                            adminChart.data.datasets[0].data = chartDataFiles;
                            adminChart.data.datasets[0].borderColor = '#10b981'; // emerald-500
                            adminChart.data.datasets[0].backgroundColor = 'rgba(16, 185, 129, 0.1)';
                            adminChart.data.datasets[0].pointBackgroundColor = '#10b981';
                        }
                        adminChart.update();
                    });
                })();
                </script>
            </div>

            <!-- Recent Folders History Table -->
            <div class="admin-section">
                <div class="admin-section-header">
                    <div>
                        <h3 class="admin-section-title">Recent Folders Created History</h3>
                        <p class="admin-section-subtitle">Latest folders created across the system</p>
                    </div>
                </div>
                <?php if(empty($recentFolders)): ?>
                    <div style="padding: 3rem 1rem; text-align: center; color: var(--admin-text-muted);">
                        No recent folders found.
                    </div>
                <?php else: ?>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Folder Name</th>
                                    <th>Creator</th>
                                    <th>Storage & Files</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentFolders as $f): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div style="width: 32px; height: 32px; border-radius: 6px; background: #f1f5f9; color: var(--admin-text-muted); display: flex; align-items: center; justify-content: center;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                            </div>
                                            <div>
                                                <div style="font-weight: 500; color: var(--admin-text-main); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    <?= htmlspecialchars($f['display_name']) ?>
                                                </div>
                                                <a href="/<?= $f['slug'] ?>" target="_blank" style="color: #3b82f6; font-size: 0.75rem; text-decoration: none;">/<?= $f['slug'] ?></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if(!empty($f['full_name'])): ?>
                                            <div style="font-size: 0.875rem; color: var(--admin-text-main); font-weight: 500;"><?= htmlspecialchars($f['full_name']) ?></div>
                                            <div style="font-size: 0.75rem; color: var(--admin-text-muted);"><?= htmlspecialchars($f['email']) ?></div>
                                        <?php else: ?>
                                            <span style="color: var(--admin-text-muted); font-size: 0.875rem;">Anonymous</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.875rem; color: var(--admin-text-main); font-weight: 500;"><?= formatFileSize($f['total_size'] ?? 0) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--admin-text-muted);"><?= $f['total_files'] ?> files</div>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.875rem; color: var(--admin-text-muted);">
                                            <?= $f['created_at'] ? date('M d, Y h:i A', strtotime($f['created_at'])) : 'Unknown' ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($view === 'users'): ?>
            <div class="admin-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon-wrapper" style="background: #eff6ff; color: #3b82f6;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-value"><?= number_format($totalUsers) ?></div>
                        <div class="admin-stat-title">Total Users</div>
                    </div>
                </div>
                <div class="admin-stat-card">
                    <div class="admin-stat-icon-wrapper" style="background: #f0fdf4; color: #22c55e;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="admin-stat-content">
                        <?php $activeUsers = count(array_filter($users, fn($u) => strtotime($u['last_login'] ?? '') > strtotime('-30 days'))); ?>
                        <div class="admin-stat-value"><?= number_format($activeUsers) ?></div>
                        <div class="admin-stat-title">Active Users (30d)</div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="admin-section">
                <div class="admin-section-header">
                    <div>
                        <h3 class="admin-section-title">User Management</h3>
                        <p class="admin-section-subtitle">Manage system users and their storage limits</p>
                    </div>
                    <div class="admin-search-box">
                        <svg class="admin-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="user-search" class="admin-search-input" placeholder="Search users by name or email...">
                    </div>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User Info</th>
                                <th>Storage & Limit</th>
                                <th>Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="user-table-body">
                            <?php foreach($users as $u): ?>
                            <tr class="user-row" data-search="<?= strtolower(htmlspecialchars($u['full_name'] . ' ' . $u['email'])) ?>">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $u['avatar_color'] ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.125rem;">
                                            <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--admin-text-main); display: flex; align-items: center; gap: 0.5rem;">
                                                <?= htmlspecialchars($u['full_name']) ?>
                                                <?php if($u['is_admin']): ?><span style="font-size: 0.65rem; background: var(--green-600); color: white; padding: 2px 8px; border-radius: 99px; font-weight: 700; text-transform: uppercase;">Admin</span><?php endif; ?>
                                            </div>
                                            <div style="font-size: 0.875rem; color: var(--admin-text-muted);"><?= htmlspecialchars($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $usagePct = $u['space_limit_mb'] > 0 ? min(100, round(($u['total_used_size'] / ($u['space_limit_mb'] * 1024 * 1024)) * 100)) : 0;
                                    ?>
                                    <div style="font-weight: 600; color: var(--admin-text-main);"><?= formatFileSize($u['total_used_size']) ?> used</div>
                                    <div style="font-size: 0.875rem; color: var(--admin-text-muted); margin-top: 2px;">Limit: <?= $u['space_limit_mb'] ?> MB • <?= $u['folder_count'] ?> folders</div>
                                    <div style="width: 120px; height: 6px; background: #e2e8f0; border-radius: 4px; margin-top: 6px; overflow: hidden;">
                                        <div style="height: 100%; width: <?= $usagePct ?>%; background: <?= $usagePct > 90 ? '#ef4444' : '#3b82f6' ?>;"></div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem; color: var(--admin-text-main);">
                                        <span style="display: block; color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 2px;">Last Login</span>
                                        <?= $u['last_login'] ? date('M d, Y h:i A', strtotime($u['last_login'])) : '<span style="color:var(--admin-text-muted)">Never</span>' ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <button class="btn btn-sm btn-outline-primary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;" onclick="openEditUserModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['full_name'])) ?>', <?= $u['space_limit_mb'] ?>)">Edit Limits</button>
                                        <button class="btn btn-sm btn-outline-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;" onclick="toggleUserFolders(<?= $u['id'] ?>)">Folders</button>
                                        <button class="btn btn-sm btn-outline-danger" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;" onclick="deleteAllUserFolders(<?= $u['id'] ?>)">Clear All</button>
                                    </div>
                                </td>
                            </tr>
                            <tr id="user-folders-<?= $u['id'] ?>" style="display: none; background: #f8fafc;">
                                <td colspan="4" style="padding: 1.5rem; border-bottom: 1px solid var(--admin-border);">
                                    <h4 style="font-weight: 600; margin-bottom: 1rem; color: var(--admin-text-main); font-size: 1rem;">Folders for <?= htmlspecialchars($u['full_name']) ?></h4>
                                    <?php
                                    $uFolders = $db->prepare("SELECT * FROM folders WHERE user_id = ? ORDER BY created_at DESC");
                                    $uFolders->execute([$u['id']]);
                                    $foldersList = $uFolders->fetchAll();
                                    if(empty($foldersList)):
                                    ?>
                                        <div style="padding: 1rem; text-align: center; color: var(--admin-text-muted); background: white; border: 1px dashed var(--admin-border); border-radius: 8px;">No folders created yet.</div>
                                    <?php else: ?>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                                        <?php foreach($foldersList as $f): ?>
                                            <div style="background: white; border: 1px solid var(--admin-border); border-radius: 8px; padding: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: flex-start;">
                                                <div>
                                                    <div style="font-weight: 600; color: var(--admin-text-main); margin-bottom: 0.25rem;"><?= htmlspecialchars($f['display_name']) ?></div>
                                                    <a href="/<?= $f['slug'] ?>" target="_blank" style="color: #3b82f6; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 0.5rem; text-decoration: none;">
                                                        /<?= $f['slug'] ?>
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                                    </a>
                                                    <div style="font-size: 0.75rem; color: var(--admin-text-muted); display: flex; align-items: center; gap: 0.5rem;">
                                                        <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?= formatFileSize($f['total_size']) ?></span>
                                                        <span><?= $f['total_files'] ?> files</span>
                                                    </div>
                                                </div>
                                                <button class="btn-icon" style="color: #ef4444; border: none; background: #fef2f2; padding: 8px; border-radius: 6px; cursor: pointer; transition: all 0.2s;" onclick="deleteFolder(<?= $f['id'] ?>)" title="Delete Folder">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($view === 'profiles'): ?>
            <div class="admin-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon-wrapper" style="background: #f0fdf4; color: #22c55e;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-value"><?= number_format($totalProfiles) ?></div>
                        <div class="admin-stat-title">Total Profile Cards</div>
                    </div>
                </div>
            </div>

            <!-- Profile Cards Table -->
            <div class="admin-section">
                <div class="admin-section-header">
                    <div>
                        <h3 class="admin-section-title">User Profile Cards</h3>
                        <p class="admin-section-subtitle">Public profiles generated by users</p>
                    </div>
                    <div class="admin-search-box">
                        <svg class="admin-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="profile-search" class="admin-search-input" placeholder="Search profiles by name or slug...">
                    </div>
                </div>
                
                <?php 
                $profileUsers = array_filter($users, fn($u) => !empty($u['profile_slug']));
                if(empty($profileUsers)): 
                ?>
                    <div style="padding: 3rem 1rem; text-align: center;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.3; margin: 0 auto 1rem; color: var(--admin-text-muted);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                        <h4 style="font-weight: 600; color: var(--admin-text-main); margin-bottom: 0.5rem;">No Profile Cards Generated</h4>
                        <p style="color: var(--admin-text-muted); font-size: 0.875rem;">Users have not created any public profile cards yet.</p>
                    </div>
                <?php else: ?>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Profile Link</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="profile-table-body">
                                <?php foreach($profileUsers as $pu): ?>
                                <tr class="profile-row" data-search="<?= strtolower(htmlspecialchars($pu['full_name'] . ' ' . $pu['profile_slug'])) ?>">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <?php if(!empty($pu['avatar_path'])): ?>
                                                <div style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden;"><img src="<?= htmlspecialchars($pu['avatar_path']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;"></div>
                                            <?php else: ?>
                                                <div style="width: 36px; height: 36px; border-radius: 50%; background: <?= $pu['avatar_color'] ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1rem;">
                                                    <?= strtoupper(substr($pu['full_name'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div style="font-weight: 600; color: var(--admin-text-main);"><?= htmlspecialchars($pu['full_name']) ?></div>
                                                <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 2px;">@<?= htmlspecialchars($pu['profile_slug']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="/u/<?= htmlspecialchars($pu['profile_slug']) ?>" target="_blank" style="color: #3b82f6; font-size: 0.875rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                            /u/<?= htmlspecialchars($pu['profile_slug']) ?>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        </a>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <a href="/u/<?= htmlspecialchars($pu['profile_slug']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">View</a>
                                            <button class="btn btn-sm btn-outline-danger" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;" onclick="deleteProfileCard(<?= $pu['id'] ?>)">Delete Card</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($view === 'unassigned'): ?>
            <div class="admin-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon-wrapper" style="background: #fdf2f8; color: #ec4899;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-value"><?= number_format(count($unassignedFolders)) ?></div>
                        <div class="admin-stat-title">Unassigned Folders</div>
                    </div>
                </div>
            </div>

            <!-- Unassigned Folders Table -->
            <div class="admin-section">
                <div class="admin-section-header">
                    <div>
                        <h3 class="admin-section-title">Unassigned & Anonymous Folders</h3>
                        <p class="admin-section-subtitle">Folders created without an account</p>
                    </div>
                    <div class="admin-search-box">
                        <svg class="admin-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="folder-search" class="admin-search-input" placeholder="Search folders by name or URL...">
                    </div>
                </div>
                
                <?php if(empty($unassignedFolders)): ?>
                    <div style="padding: 3rem 1rem; text-align: center;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.3; margin: 0 auto 1rem; color: var(--admin-text-muted);"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                        <h4 style="font-weight: 600; color: var(--admin-text-main); margin-bottom: 0.5rem;">No unassigned folders found</h4>
                        <p style="color: var(--admin-text-muted); font-size: 0.875rem;">All folders currently belong to registered users.</p>
                    </div>
                <?php else: ?>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Folder Info</th>
                                    <th>Link</th>
                                    <th>Storage & Files</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="folder-table-body">
                                <?php foreach($unassignedFolders as $f): ?>
                                <tr class="folder-row" data-search="<?= strtolower(htmlspecialchars($f['display_name'] . ' ' . $f['slug'])) ?>">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div style="width: 36px; height: 36px; border-radius: 8px; background: #f1f5f9; color: var(--admin-text-muted); display: flex; align-items: center; justify-content: center;">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; color: var(--admin-text-main);"><?= htmlspecialchars($f['display_name']) ?></div>
                                                <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 2px;">Created <?= timeAgo($f['created_at']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="/<?= $f['slug'] ?>" target="_blank" style="color: #3b82f6; font-size: 0.875rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                            /<?= $f['slug'] ?>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        </a>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--admin-text-main);"><?= formatFileSize($f['total_size']) ?></div>
                                        <div style="font-size: 0.875rem; color: var(--admin-text-muted); margin-top: 2px;"><?= $f['total_files'] ?> files</div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;" onclick="deleteFolder(<?= $f['id'] ?>)">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Edit User Modal -->
<div class="modal-backdrop" id="edit-user-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
    <div class="modal-content" style="background: white; padding: 2rem; border-radius: 12px; width: 100%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; color: var(--admin-text-main);">Edit User</h3>
        <form id="edit-user-form">
            <input type="hidden" id="edit-user-id" name="user_id">
            <div class="form-group mb-4">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--admin-text-main);">User Name</label>
                <input type="text" id="edit-user-name" disabled style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; background: #f8fafc; color: var(--admin-text-muted);">
            </div>
            <div class="form-group mb-4">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--admin-text-main);">Space Limit (MB)</label>
                <input type="number" id="edit-space-limit" name="space_limit_mb" min="1" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
            </div>
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" class="admin-btn admin-btn-outline" onclick="closeEditUserModal()">Cancel</button>
                <button type="submit" class="admin-btn admin-btn-primary" id="btn-save-user">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAdminSidebar() {
    document.getElementById('admin-sidebar').classList.toggle('sidebar-open');
    document.getElementById('admin-sidebar-overlay').classList.toggle('overlay-open');
}

function toggleUserFolders(userId) {
    const el = document.getElementById('user-folders-' + userId);
    if (el) {
        el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
    }
}

function openEditUserModal(userId, name, limit) {
    document.getElementById('edit-user-id').value = userId;
    document.getElementById('edit-user-name').value = name;
    document.getElementById('edit-space-limit').value = limit;
    document.getElementById('edit-user-modal').style.display = 'flex';
}

function closeEditUserModal() {
    document.getElementById('edit-user-modal').style.display = 'none';
}

(function() {
    const editUserForm = document.getElementById('edit-user-form');
    if(editUserForm) {
        editUserForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btn-save-user');
        btn.disabled = true;
        btn.textContent = 'Saving...';
        
        const formData = new FormData(e.target);
        formData.append('csrf_token', getCSRF());
        
        try {
            const res = await fetch('/api/admin/update-user', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                showToast('User updated successfully!');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.errors?.[0] || 'Update failed', 'error');
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            }
        } catch (err) {
            showToast('Network error', 'error');
            btn.disabled = false;
            btn.textContent = 'Save Changes';
        }
    });
}
})();

async function deleteFolder(folderId) {
    customConfirm(
        'Delete Folder',
        'Are you sure you want to delete this folder and ALL its files? This cannot be undone.',
        async () => {
            const formData = new FormData();
            formData.append('folder_id', folderId);
            formData.append('csrf_token', getCSRF());
            
            try {
                const res = await fetch('/api/admin/delete-folder', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.success) {
                    showToast('Folder deleted.');
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

async function deleteAllUserFolders(userId) {
    customConfirm(
        'Clear All Folders',
        'Are you sure you want to delete ALL folders for this user? This cannot be undone.',
        async () => {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('csrf_token', getCSRF());
            
            try {
                const res = await fetch('/api/admin/delete-all-folders', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.success) {
                    showToast('All folders deleted.');
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

async function deleteProfileCard(userId) {
    customConfirm(
        'Delete Profile Card',
        'Are you sure you want to delete the profile card for this user? This cannot be undone.',
        async () => {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('csrf_token', getCSRF());
            
            try {
                const res = await fetch('/api/admin/delete-profile-card', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.success) {
                    showToast('Profile card deleted successfully.');
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

// Search Functionality
(function() {
    const userSearch = document.getElementById('user-search');
    if (userSearch) {
        userSearch.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.user-row').forEach(row => {
            const dataSearch = row.getAttribute('data-search') || '';
            if (dataSearch.includes(term)) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
                // Also hide their folders if open
                const foldersRow = row.nextElementSibling;
                if(foldersRow && foldersRow.id.startsWith('user-folders-')) {
                    foldersRow.style.display = 'none';
                }
            }
        });
    });
}

const folderSearch = document.getElementById('folder-search');
if (folderSearch) {
    folderSearch.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.folder-row').forEach(row => {
            const dataSearch = row.getAttribute('data-search') || '';
            if (dataSearch.includes(term)) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

const profileSearch = document.getElementById('profile-search');
if (profileSearch) {
    profileSearch.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.profile-row').forEach(row => {
            const dataSearch = row.getAttribute('data-search') || '';
            if (dataSearch.includes(term)) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    });
}
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
