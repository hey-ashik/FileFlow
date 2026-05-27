<?php
require_once __DIR__ . '/../config/config.php';
$query = trim($_GET['q'] ?? '');
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

$db = getDB();
$results = [];

if (!empty($query)) {
    // Search by name, phone, or slug
    $likeQuery = "%{$query}%";
    $stmt = $db->prepare("SELECT id, full_name, profile_slug, avatar_path, avatar_color, work_experience, is_verified, is_admin FROM users WHERE is_public = 1 AND is_active = 1 AND (full_name LIKE ? OR phone LIKE ? OR profile_slug LIKE ?) ORDER BY full_name ASC LIMIT 50");
    $stmt->execute([$likeQuery, $likeQuery, $likeQuery]);
    $results = $stmt->fetchAll();
}

if (isset($_GET['json']) && $_GET['json'] == '1') {
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

if ($isAjax) {
    if (empty($query)) {
        echo '<div style="text-align: center; color: var(--gray-500); padding: 2rem 0;">Type to search profiles...</div>';
        exit;
    }
?>
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
                            <h4 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #0f172a; display: flex; align-items: center; max-width: 100%; min-width: 0;">
                                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; flex-shrink: 1;">
                                    <?php echo htmlspecialchars($profile['full_name']); ?>
                                </span>
                                <?php echo getVerifiedBadgeHtml($profile['is_verified'] ?? 0, $profile['is_admin'] ?? 0); ?>
                            </h4>
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
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="margin-bottom: 1rem; display: inline-block;">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <p style="color: #64748b; font-size: 1.1rem;">No profiles found matching your search.</p>
        </div>
    <?php endif; ?>
<?php
    exit;
}

$currentPage = 'search';
$pageTitle = 'Search Profiles - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    @media (max-width: 600px) {
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

<div style="position: fixed; inset: 0; z-index: -1; background: linear-gradient(135deg, var(--green-50) 0%, var(--white) 50%, var(--green-50) 100%); overflow: hidden; pointer-events: none;">
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-orb hero-orb-3"></div>
</div>

<div class="container" style="max-width: 800px; padding: 2rem 1rem; position: relative; z-index: 1;">
    <h1 style="margin-bottom: 1.5rem; font-size: 1.8rem; font-weight: 700; text-align: center;">Search Profiles</h1>

    <div style="background: white; border-radius: 9999px; padding: 0.75rem 1.5rem; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); display: flex; align-items: center; border: 1px solid #7e8286;">
        <div style="flex: 1; display: flex; align-items: center; gap: 0.75rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="flex-shrink:0;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <form method="GET" action="/search" style="flex: 1; display: flex; margin: 0; padding: 0;" id="search-form">
                <input type="text" id="profiles-search-input" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search by name, phone, or username (@slug)..." style="border: none; outline: none; width: 100%; font-family: inherit; font-size: 1rem; color: var(--gray-700); background: transparent; min-width:0;" oninput="debounceSearchProfiles()">
            </form>
        </div>
    </div>

    <div id="search-results-container">
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
                                    <h4 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #0f172a; display: flex; align-items: center; max-width: 100%; min-width: 0;">
                                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; flex-shrink: 1;">
                                            <?php echo htmlspecialchars($profile['full_name']); ?>
                                        </span>
                                        <?php echo getVerifiedBadgeHtml($profile['is_verified'] ?? 0, $profile['is_admin'] ?? 0); ?>
                                    </h4>
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
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="margin-bottom: 1rem; display: inline-block;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <p style="color: #64748b; font-size: 1.1rem;">No profiles found matching your search.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align: center; color: var(--gray-500); padding: 2rem 0;">Type to search profiles...</div>
        <?php endif; ?>
    </div>
</div>

<script>
let searchTimeout = null;
window.debounceSearchProfiles = function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(searchProfiles, 400);
}

window.searchProfiles = async function() {
    const input = document.getElementById('profiles-search-input');
    const container = document.getElementById('search-results-container');
    
    if (input && container) {
        const query = input.value.trim();
        
        // Update URL to match search query without reloading
        const urlParams = new URLSearchParams(window.location.search);
        if (query) {
            urlParams.set('q', query);
        } else {
            urlParams.delete('q');
        }
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, '', newUrl);

        if (!query) {
            container.innerHTML = '<div style="text-align: center; color: var(--gray-500); padding: 2rem 0;">Type to search profiles...</div>';
            return;
        }

        container.innerHTML = '<div style="text-align: center; color: var(--gray-500); padding: 2rem 0;">Loading...</div>';
        
        try {
            const url = `/search?ajax=1&q=${encodeURIComponent(query)}`;
            const res = await fetch(url);
            const html = await res.text();
            container.innerHTML = html;
        } catch (err) {
            container.innerHTML = '<div style="color: red; text-align: center;">Failed to load results.</div>';
        }
    }
}

// Intercept form submission to use AJAX if JS is enabled
document.getElementById('search-form').addEventListener('submit', function(e) {
    e.preventDefault();
    searchProfiles();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
