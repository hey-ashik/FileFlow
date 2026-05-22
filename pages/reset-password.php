<?php
$currentPage = 'reset-password';
$pageTitle = 'Reset Password - ' . APP_NAME;
$pageDescription = 'Set a new password for your FileFlow account.';
$token = $_GET['token'] ?? '';
if (empty($token)) { header('Location: /forgot-password'); exit; }
require_once __DIR__ . '/../includes/header.php';
?>

<div style="position: fixed; inset: 0; z-index: -1; background: linear-gradient(135deg, var(--green-50) 0%, var(--white) 50%, var(--green-50) 100%); overflow: hidden; pointer-events: none;">
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-orb hero-orb-3"></div>
</div>

<section class="auth-page" id="auth-page" style="position: relative; z-index: 1; background: transparent;">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon auth-icon-reset">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <h1>New Password</h1>
            <p>Enter your new password below</p>
        </div>
        <form id="reset-form" class="auth-form">
            <input type="hidden" id="reset-token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label for="reset-password">New Password</label>
                <div class="form-input-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input type="password" id="reset-password" name="password" placeholder="Min 6 characters" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('reset-password', this)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="reset-confirm">Confirm Password</label>
                <div class="form-input-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <input type="password" id="reset-confirm" name="confirm_password" placeholder="Confirm password" required minlength="6">
                </div>
            </div>
            <div class="form-error" id="reset-error" style="display:none;"></div>
            <div class="form-success" id="reset-success" style="display:none;"></div>
            <button type="submit" class="btn btn-primary btn-lg btn-full" id="btn-reset">
                <span class="btn-text">Reset Password</span>
                <span class="btn-loader" style="display:none;"><svg width="20" height="20" viewBox="0 0 24 24" class="spinner"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="30 70" stroke-linecap="round"/></svg></span>
            </button>
        </form>
        <div class="auth-footer">
            <p><a href="/login" class="form-link-bold">Back to Login</a></p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
