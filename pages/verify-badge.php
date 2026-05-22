<?php
require_once __DIR__ . '/../config/config.php';
$currentPage = 'verify-badge';
$pageTitle = 'Verify Badge - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';

$user = getCurrentUser();

// Verification Status and Stats
$db = getDB();
$stmtViews = $db->prepare("SELECT COALESCE(SUM(views), 0) FROM thoughts WHERE user_id = ?");
$stmtViews->execute([$user['id']]);
$thoughtViews = (int)$stmtViews->fetchColumn();

$stmtVerify = $db->prepare("SELECT status, real_name, phone, email, nid_path FROM verification_requests WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmtVerify->execute([$user['id']]);
$verifyRequest = $stmtVerify->fetch();

$stmtFreshUser = $db->prepare("SELECT profile_visits, is_verified FROM users WHERE id = ?");
$stmtFreshUser->execute([$user['id']]);
$freshUser = $stmtFreshUser->fetch();
$isUserVerified = isset($freshUser['is_verified']) && $freshUser['is_verified'] == 1;

$profileVisits = (int)($freshUser['profile_visits'] ?? 0);
$meetsVisits = $profileVisits >= 500;
$meetsThoughts = $thoughtViews >= 1000;
$canApply = $meetsVisits && $meetsThoughts && !$isUserVerified && (!$verifyRequest || $verifyRequest['status'] !== 'pending');
?>

<style>
    @media (max-width: 600px) {
        .verify-badge-card {
            padding: 1.25rem !important;
        }
    }
</style>

<div style="position: fixed; inset: 0; z-index: -1; background: linear-gradient(135deg, var(--green-50) 0%, var(--white) 50%, var(--green-50) 100%); overflow: hidden; pointer-events: none;">
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-orb hero-orb-3"></div>
</div>

<div class="container" style="max-width: 800px; padding: 2rem 1rem; position: relative; z-index: 1;">
    <div class="verify-badge-card"
        style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem;">
        <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            Verified Badge Request
        </h2>

        <?php if ($isUserVerified): ?>
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 1.25rem; color: #065f46; display: flex; align-items: center; gap: 0.75rem;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color: #10b981; flex-shrink: 0;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <div>
                    <strong style="display: block; font-size: 1.05rem;">Verified Account</strong>
                    <span style="font-size: 0.9rem;">Your account is verified. A blue verified badge is displayed next to your name.</span>
                </div>
            </div>
        <?php elseif ($verifyRequest && $verifyRequest['status'] === 'pending'): ?>
            <div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; padding: 1.25rem; color: #92400e; display: flex; align-items: center; gap: 0.75rem;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #d97706; flex-shrink: 0;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <div>
                    <strong style="display: block; font-size: 1.05rem;">Request Pending</strong>
                    <span style="font-size: 0.9rem;">Your application for a verified badge is currently under review by our admin team.</span>
                </div>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 1.5rem;">
                <p style="font-size: 0.95rem; color: var(--gray-600); margin-bottom: 1rem;">
                    To apply for a verified badge on your profile card, you must meet the following metrics:
                </p>

                <!-- Metric 1: Profile Visits -->
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">
                        <span>Profile Card Views</span>
                        <span style="color: <?php echo $meetsVisits ? '#10b981' : '#ef4444'; ?>; font-weight: 600;">
                            <?php echo $profileVisits; ?> / 500
                        </span>
                    </div>
                    <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 9999px; overflow: hidden;">
                        <div style="width: <?php echo min(100, ($profileVisits / 100) * 100); ?>%; height: 100%; background: <?php echo $meetsVisits ? '#10b981' : '#f59e0b'; ?>; border-radius: 9999px; transition: width 0.3s;"></div>
                    </div>
                </div>

                <!-- Metric 2: Thoughts Views -->
                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">
                        <span>Thoughts Post Views</span>
                        <span style="color: <?php echo $meetsThoughts ? '#10b981' : '#ef4444'; ?>; font-weight: 600;">
                            <?php echo $thoughtViews; ?> / 1000
                        </span>
                    </div>
                    <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 9999px; overflow: hidden;">
                        <div style="width: <?php echo min(100, ($thoughtViews / 500) * 100); ?>%; height: 100%; background: <?php echo $meetsThoughts ? '#10b981' : '#f59e0b'; ?>; border-radius: 9999px; transition: width 0.3s;"></div>
                    </div>
                </div>
            </div>

            <?php if ($canApply): ?>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem;">
                    <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem; color: var(--gray-900);">Submit Application</h3>
                    <form id="verification-form" enctype="multipart/form-data">
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Real Name (matching NID)</label>
                            <input type="text" name="real_name" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; box-sizing: border-box;">
                        </div>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Contact Number</label>
                            <input type="text" name="phone" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; box-sizing: border-box;" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Gmail/Email Address</label>
                            <input type="email" name="email" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; box-sizing: border-box;" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem;">Upload NID (Image or PDF)</label>
                            <input type="file" name="nid_file" accept="image/*,application/pdf" required style="width: 100%; font-size: 0.95rem;">
                        </div>
                        <button type="submit" id="verify-submit-btn" class="btn btn-primary" style="width: 100%; justify-content: center; height: 44px; font-weight: 600;">Apply for Verified Badge</button>
                    </form>
                </div>
            <?php else: ?>
                <div style="background: #f1f5f9; border-radius: 8px; padding: 1rem; color: #64748b; font-size: 0.9rem; text-align: center; border: 1px dashed #cbd5e1;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 0.25rem;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    Application Locked. You must reach both metric requirements before you can submit a verification request.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    // Handle Verification Request Form Submission
    const verifyForm = document.getElementById('verification-form');
    if (verifyForm) {
        verifyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const verifyBtn = document.getElementById('verify-submit-btn');
            verifyBtn.disabled = true;
            const oldText = verifyBtn.innerText;
            verifyBtn.innerText = 'Submitting...';

            const formData = new FormData(verifyForm);
            formData.append('action', 'apply_verification');
            formData.append('csrf_token', document.getElementById('csrf-token').value);

            try {
                const response = await fetch('/api/apply-verify', {
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
                showToast('An error occurred during submission', 'error');
            } finally {
                verifyBtn.disabled = false;
                verifyBtn.innerText = oldText;
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
