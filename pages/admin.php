<?php
$currentPage = 'admin';
$pageTitle = 'Admin Dashboard - ' . APP_NAME;
$pageDescription = 'Admin panel for managing users and system resources.';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Get overall stats
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProfiles = $db->query("SELECT COUNT(*) FROM users WHERE profile_slug IS NOT NULL AND profile_slug != ''")->fetchColumn();
$totalFolders = $db->query("SELECT COUNT(*) FROM folders")->fetchColumn();
$totalFiles = $db->query("SELECT COUNT(*) FROM files")->fetchColumn();

// Get users data
$users = $db->query("
    SELECT u.*,
        (SELECT COUNT(*) FROM folders WHERE user_id = u.id) as folder_count,
        (SELECT COALESCE(SUM(total_size), 0) FROM folders WHERE user_id = u.id) as total_used_size
    FROM users u
    ORDER BY u.created_at DESC
")->fetchAll();

// Get unassigned/anonymous folders
$unassignedFolders = $db->query("
    SELECT * FROM folders WHERE user_id IS NULL ORDER BY created_at DESC
")->fetchAll();

?>
<section class="dashboard" id="admin-panel">
    <div class="container">
        <div class="dash-header mb-8">
            <div class="dash-welcome">
                <div style="display:flex; align-items:center; gap: 16px; flex-wrap:wrap;">
                    <h1 style="margin:0;">Admin <span class="gradient-text">Dashboard</span></h1>
                    <button onclick="location.reload()" class="btn btn-outline" style="padding: 6px 14px; font-size: 0.85rem; border-radius:100px; display:inline-flex; align-items:center; gap:6px; height:auto; background:var(--white);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                        </svg>
                        Refresh Data
                    </button>
                </div>
                <p style="margin-top:8px;">Manage users, storage limits, and system resources.</p>
            </div>
        </div>

        <div class="dash-stats mb-10">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background:var(--green-50);color:var(--green-600)">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="dash-stat-info">
                    <span class="dash-stat-value"><?= number_format($totalUsers) ?></span>
                    <span class="dash-stat-label">Total Users</span>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background:#eff6ff;color:#3b82f6">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="dash-stat-info">
                    <span class="dash-stat-value"><?= number_format($totalProfiles) ?></span>
                    <span class="dash-stat-label">Profile Cards</span>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background:#f5f3ff;color:#8b5cf6">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                </div>
                <div class="dash-stat-info">
                    <span class="dash-stat-value"><?= number_format($totalFolders) ?></span>
                    <span class="dash-stat-label">Total Folders</span>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background:#fdf2f8;color:#ec4899">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div class="dash-stat-info">
                    <span class="dash-stat-value"><?= number_format($totalFiles) ?></span>
                    <span class="dash-stat-label">Total Files</span>
                </div>
            </div>
        </div>

        <div class="admin-users-list" style="background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--gray-200); margin-bottom: 2rem; overflow: hidden;">
            <div class="dash-card-header" style="border-bottom: 1px solid var(--gray-200); padding: 1.25rem 1.5rem; margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <h3 style="font-size: 1.25rem; font-weight: 600; margin: 0;">User Management</h3>
                <div style="position: relative; width: 100%; max-width: 300px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--gray-400);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="user-search" placeholder="Search users by name or email..." style="width: 100%; padding: 0.5rem 1rem 0.5rem 2.5rem; border: 1px solid var(--gray-300); border-radius: var(--radius); outline: none;">
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200);">
                        <tr>
                            <th style="padding: 1rem 1.5rem; font-weight: 600; color: var(--gray-600); font-size: 0.875rem;">User Info</th>
                            <th style="padding: 1rem 1.5rem; font-weight: 600; color: var(--gray-600); font-size: 0.875rem;">Storage & Limit</th>
                            <th style="padding: 1rem 1.5rem; font-weight: 600; color: var(--gray-600); font-size: 0.875rem;">Activity</th>
                            <th style="padding: 1rem 1.5rem; font-weight: 600; color: var(--gray-600); font-size: 0.875rem;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body">
                        <?php foreach($users as $u): ?>
                        <tr style="border-bottom: 1px solid var(--gray-200);" class="user-row" data-search="<?= strtolower(htmlspecialchars($u['full_name'] . ' ' . $u['email'])) ?>">
                            <td style="padding: 1.25rem 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $u['avatar_color'] ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.125rem;">
                                        <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--gray-900); display: flex; align-items: center; gap: 0.5rem;">
                                            <?= htmlspecialchars($u['full_name']) ?>
                                            <?php if($u['is_admin']): ?><span style="font-size: 0.65rem; background: var(--green-600); color: white; padding: 2px 8px; border-radius: 99px; font-weight: 700; text-transform: uppercase;">Admin</span><?php endif; ?>
                                        </div>
                                        <div style="font-size: 0.875rem; color: var(--gray-500);"><?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 1.25rem 1.5rem;">
                                <?php
                                $usagePct = $u['space_limit_mb'] > 0 ? min(100, round(($u['total_used_size'] / ($u['space_limit_mb'] * 1024 * 1024)) * 100)) : 0;
                                ?>
                                <div style="font-weight: 600; color: var(--gray-800);"><?= formatFileSize($u['total_used_size']) ?> used</div>
                                <div style="font-size: 0.875rem; color: var(--gray-500); margin-top: 2px;">Limit: <?= $u['space_limit_mb'] ?> MB • <?= $u['folder_count'] ?> folders</div>
                                <div style="width: 120px; height: 4px; background: var(--gray-200); border-radius: 4px; margin-top: 6px; overflow: hidden;">
                                    <div style="height: 100%; width: <?= $usagePct ?>%; background: <?= $usagePct > 90 ? 'var(--red-500)' : 'var(--blue-500)' ?>;"></div>
                                </div>
                            </td>
                            <td style="padding: 1.25rem 1.5rem;">
                                <div style="font-size: 0.875rem; color: var(--gray-600);">
                                    <span style="display: block; color: var(--gray-400); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 2px;">Last Login</span>
                                    <?= $u['last_login'] ? date('M d, Y h:i A', strtotime($u['last_login'])) : '<span style="color:var(--gray-400)">Never</span>' ?>
                                </div>
                            </td>
                            <td style="padding: 1.25rem 1.5rem;">
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button class="btn btn-sm btn-outline-primary" onclick="openEditUserModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['full_name'])) ?>', <?= $u['space_limit_mb'] ?>)">Edit Limits</button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleUserFolders(<?= $u['id'] ?>)">View Folders</button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAllUserFolders(<?= $u['id'] ?>)">Clear All</button>
                                </div>
                            </td>
                        </tr>
                        <tr id="user-folders-<?= $u['id'] ?>" style="display: none; background: var(--gray-50);">
                            <td colspan="4" style="padding: 1.5rem; border-bottom: 1px solid var(--gray-200);">
                                <h4 style="font-weight: 600; margin-bottom: 1rem; color: var(--gray-800); font-size: 1rem;">Folders for <?= htmlspecialchars($u['full_name']) ?></h4>
                                <?php
                                $uFolders = $db->prepare("SELECT * FROM folders WHERE user_id = ? ORDER BY created_at DESC");
                                $uFolders->execute([$u['id']]);
                                $foldersList = $uFolders->fetchAll();
                                if(empty($foldersList)):
                                ?>
                                    <div style="padding: 1rem; text-align: center; color: var(--gray-500); background: var(--white); border: 1px dashed var(--gray-300); border-radius: var(--radius);">No folders created yet.</div>
                                <?php else: ?>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                                    <?php foreach($foldersList as $f): ?>
                                        <div style="background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius); padding: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: flex-start;">
                                            <div>
                                                <div style="font-weight: 600; color: var(--gray-900); margin-bottom: 0.25rem;"><?= htmlspecialchars($f['display_name']) ?></div>
                                                <a href="/<?= $f['slug'] ?>/" target="_blank" style="color: var(--blue-600); font-size: 0.875rem; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 0.5rem; text-decoration: none;">
                                                    /<?= $f['slug'] ?>/
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                                </a>
                                                <div style="font-size: 0.75rem; color: var(--gray-500); display: flex; align-items: center; gap: 0.5rem;">
                                                    <span style="background: var(--gray-100); padding: 2px 6px; border-radius: 4px;"><?= formatFileSize($f['total_size']) ?></span>
                                                    <span><?= $f['total_files'] ?> files</span>
                                                </div>
                                            </div>
                                            <button class="btn-icon" style="color: var(--red-500); border: none; background: var(--red-50); padding: 8px; border-radius: 6px; cursor: pointer; transition: all 0.2s;" onclick="deleteFolder(<?= $f['id'] ?>)" title="Delete Folder">
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

        <div class="dash-folders-section mb-10" style="background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--gray-200); margin-bottom: 2rem; overflow: hidden;">
            <div class="dash-card-header" style="border-bottom: 1px solid var(--gray-200); padding: 1.25rem 1.5rem; margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin: 0;">User Profile Cards</h3>
                    <p style="font-size: 0.875rem; color: var(--gray-500); margin-top: 4px; margin-bottom: 0;">Public profiles generated by users</p>
                </div>
                <div style="position: relative; width: 100%; max-width: 300px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--gray-400);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="profile-search" placeholder="Search profiles by name or slug..." style="width: 100%; padding: 0.5rem 1rem 0.5rem 2.5rem; border: 1px solid var(--gray-300); border-radius: var(--radius); outline: none;">
                </div>
            </div>
            
            <?php 
            $profileUsers = array_filter($users, fn($u) => !empty($u['profile_slug']));
            if(empty($profileUsers)): 
            ?>
                <div class="dash-empty" style="padding: 3rem 1rem; text-align: center;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.3; margin: 0 auto 1rem; color: var(--gray-400);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                    <h4 style="font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">No Profile Cards Generated</h4>
                    <p style="color: var(--gray-500); font-size: 0.875rem;">Users have not created any public profile cards yet.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200);">
                            <tr>
                                <th style="padding: 1rem 1.5rem; font-weight: 600; color: var(--gray-600); font-size: 0.875rem;">User</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 600; color: var(--gray-600); font-size: 0.875rem;">Profile Link</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 600; color: var(--gray-600); font-size: 0.875rem;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="profile-table-body">
                            <?php foreach($profileUsers as $pu): ?>
                            <tr style="border-bottom: 1px solid var(--gray-200);" class="profile-row" data-search="<?= strtolower(htmlspecialchars($pu['full_name'] . ' ' . $pu['profile_slug'])) ?>">
                                <td style="padding: 1.25rem 1.5rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <?php if(!empty($pu['avatar_path'])): ?>
                                            <div style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden;"><img src="<?= htmlspecialchars($pu['avatar_path']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;"></div>
                                        <?php else: ?>
                                            <div style="width: 36px; height: 36px; border-radius: 50%; background: <?= $pu['avatar_color'] ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1rem;">
                                                <?= strtoupper(substr($pu['full_name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight: 600; color: var(--gray-900);"><?= htmlspecialchars($pu['full_name']) ?></div>
                                            <div style="font-size: 0.75rem; color: var(--gray-500); margin-top: 2px;">@<?= htmlspecialchars($pu['profile_slug']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 1.25rem 1.5rem;">
                                    <a href="/u/<?= htmlspecialchars($pu['profile_slug']) ?>" target="_blank" style="color: var(--blue-600); font-size: 0.875rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                        /u/<?= htmlspecialchars($pu['profile_slug']) ?>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    </a>
                                </td>
                                <td style="padding: 1.25rem 1.5rem;">
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <a href="/u/<?= htmlspecialchars($pu['profile_slug']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteProfileCard(<?= $pu['id'] ?>)">Delete Card</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="dash-folders-section mb-10" style="background: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--gray-200); margin-bottom: 2rem; overflow: hidden;">
            <div class="dash-card-header" style="border-bottom: 1px solid var(--gray-200); padding: 1.25rem 1.5rem; margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin: 0;">Unassigned & Anonymous Folders</h3>
                    <p style="font-size: 0.875rem; color: var(--gray-500); margin-top: 4px; margin-bottom: 0;">Folders created without an account</p>
                </div>
                <div style="position: relative; width: 100%; max-width: 300px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--gray-400);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="folder-search" placeholder="Search folders by name or URL..." style="width: 100%; padding: 0.5rem 1rem 0.5rem 2.5rem; border: 1px solid var(--gray-300); border-radius: var(--radius); outline: none;">
                </div>
            </div>
            
            <?php if(empty($unassignedFolders)): ?>
                <div class="dash-empty" style="padding: 3rem 1rem; text-align: center;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.3; margin: 0 auto 1rem; color: var(--gray-400);"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    <h4 style="font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">No unassigned folders found</h4>
                    <p style="color: var(--gray-500); font-size: 0.875rem;">All folders currently belong to registered users.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200);">
                            <tr>
                                <th style="padding: 1rem 1.5rem; font-weight: 600; color: var(--gray-600); font-size: 0.875rem;">Folder Info</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 600; color: var(--gray-600); font-size: 0.875rem;">Link</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 600; color: var(--gray-600); font-size: 0.875rem;">Storage & Files</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 600; color: var(--gray-600); font-size: 0.875rem;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="folder-table-body">
                            <?php foreach($unassignedFolders as $f): ?>
                            <tr style="border-bottom: 1px solid var(--gray-200);" class="folder-row" data-search="<?= strtolower(htmlspecialchars($f['display_name'] . ' ' . $f['slug'])) ?>">
                                <td style="padding: 1.25rem 1.5rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 36px; height: 36px; border-radius: 8px; background: var(--gray-100); color: var(--gray-500); display: flex; align-items: center; justify-content: center;">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--gray-900);"><?= htmlspecialchars($f['display_name']) ?></div>
                                            <div style="font-size: 0.75rem; color: var(--gray-500); margin-top: 2px;">Created <?= timeAgo($f['created_at']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 1.25rem 1.5rem;">
                                    <a href="/<?= $f['slug'] ?>/" target="_blank" style="color: var(--blue-600); font-size: 0.875rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                        /<?= $f['slug'] ?>/
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    </a>
                                </td>
                                <td style="padding: 1.25rem 1.5rem;">
                                    <div style="font-weight: 600; color: var(--gray-800);"><?= formatFileSize($f['total_size']) ?></div>
                                    <div style="font-size: 0.875rem; color: var(--gray-500); margin-top: 2px;"><?= $f['total_files'] ?> files</div>
                                </td>
                                <td style="padding: 1.25rem 1.5rem;">
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteFolder(<?= $f['id'] ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Edit User Modal -->
<div class="modal-backdrop" id="edit-user-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; padding: 2rem; border-radius: 12px; width: 100%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem;">Edit User</h3>
        <form id="edit-user-form">
            <input type="hidden" id="edit-user-id" name="user_id">
            <div class="form-group mb-4">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">User Name</label>
                <input type="text" id="edit-user-name" disabled style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; background: #f3f4f6;">
            </div>
            <div class="form-group mb-4">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Space Limit (MB)</label>
                <input type="number" id="edit-space-limit" name="space_limit_mb" min="1" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" class="btn btn-outline-secondary" onclick="closeEditUserModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btn-save-user">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
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

document.getElementById('edit-user-form').addEventListener('submit', async (e) => {
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
