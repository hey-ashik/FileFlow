<?php
$currentPage = 'forgot-password';
$pageTitle = 'Forgot Password - ' . APP_NAME;
$pageDescription = 'Reset your FileFlow account password.';
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
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/><polyline points="21 6 21 2 17 2"/></svg>
            </div>
            <h1>Reset Password</h1>
            <p>Enter your email and we'll generate a reset link</p>
        </div>
        <form id="forgot-form" class="auth-form">
            <div class="form-group">
                <label for="forgot-email">Email Address</label>
                <div class="form-input-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <input type="email" id="forgot-email" name="email" placeholder="you@example.com" required autocomplete="email">
                </div>
            </div>
            <div class="form-error" id="forgot-error" style="display:none;"></div>
            <div class="form-success" id="forgot-success" style="display:none;"></div>
            <button type="submit" class="btn btn-primary btn-lg btn-full" id="btn-forgot">
                <span class="btn-text">Send Reset Link</span>
                <span class="btn-loader" style="display:none;"><svg width="20" height="20" viewBox="0 0 24 24" class="spinner"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="30 70" stroke-linecap="round"/></svg></span>
            </button>
        </form>
        <div class="auth-footer">
            <p>Remember your password? <a href="/login" class="form-link-bold">Sign in</a></p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
