<?php
require_once __DIR__ . '/../config/config.php';
$currentPage = 'search';
$pageTitle = 'Search Profiles - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';

$query = trim($_GET['q'] ?? '');
$db = getDB();
$results = [];

if (!empty($query)) {
    // Search by name, phone, or slug
    $likeQuery = "%{$query}%";
    $stmt = $db->prepare("SELECT id, full_name, profile_slug, avatar_path, avatar_color, work_experience FROM users WHERE is_public = 1 AND is_active = 1 AND (full_name LIKE ? OR phone LIKE ? OR profile_slug LIKE ?) ORDER BY full_name ASC LIMIT 50");
    $stmt->execute([$likeQuery, $likeQuery, $likeQuery]);
    $results = $stmt->fetchAll();
}
?>

<style>
    .search-form-container {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
    }
    .search-input-field {
        flex: 1;
        padding: 0.75rem 1rem;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 1rem;
        outline: none;
    }
    .search-btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        justify-content: center;
    }

    @media (max-width: 600px) {
        .search-form-container {
            flex-direction: column;
            gap: 0.75rem;
        }
        .search-btn {
            width: 100%;
        }
        .search-result-card {
            flex-direction: column;
            align-items: stretch !important;
            padding: 1rem !important;
            gap: 0.75rem !important;
        }
        .search-result-card > div:first-child {
            width: 100%;
        }
        .search-result-card .btn {
            width: 100%;
            text-align: center;
            justify-content: center;
        }
    }
</style>

<div class="container" style="max-width: 800px; padding: 2rem 1rem;">
    <h1 style="margin-bottom: 1.5rem; font-size: 1.8rem; font-weight: 700;">Search Profiles</h1>

    <form method="GET" action="/search" class="search-form-container">
        <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search by name, phone, or username (@slug)..." class="search-input-field">
        <button type="submit" class="btn btn-primary search-btn">Search</button>
    </form>

    <?php if (!empty($query)): ?>
        <h3 style="margin-bottom: 1.5rem; font-size: 1.1rem; color: #475569;">
            Found <?php echo count($results); ?> result(s) for "<?php echo htmlspecialchars($query); ?>"
        </h3>

        <?php if (count($results) > 0): ?>
            <div style="display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr);">
                <?php foreach ($results as $profile): ?>
                    <div class="search-result-card" style="display: flex; align-items: center; justify-content: space-between; background: white; padding: 1.25rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; flex-wrap: wrap; gap: 1rem; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 1rem; min-width: 0; flex: 1;">
                            <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; background: <?php echo htmlspecialchars($profile['avatar_color']); ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem; flex-shrink: 0;">
                                <?php if (!empty($profile['avatar_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($profile['avatar_path']); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($profile['full_name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div style="min-width: 0; flex: 1;">
                                <h4 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($profile['full_name']); ?></h4>
                                <?php if (!empty($profile['profile_slug'])): ?>
                                    <div style="font-size: 0.85rem; color: #64748b; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">@<?php echo htmlspecialchars($profile['profile_slug']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($profile['work_experience'])): ?>
                                    <div style="font-size: 0.85rem; color: #475569; margin-top: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;">
                                        <?php echo htmlspecialchars($profile['work_experience']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="/u/<?php echo htmlspecialchars($profile['profile_slug']); ?>" class="btn btn-primary" target="_blank" style="padding: 0.5rem 1rem; font-size: 0.9rem; flex-shrink: 0;">View Profile</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 12px; border: 1px dashed #cbd5e1;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="margin-bottom: 1rem;">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <p style="color: #64748b; font-size: 1.1rem;">No profiles found matching your search.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
