<?php
$currentPage = 'register';
$pageTitle = 'Sign Up - ' . APP_NAME;
$pageDescription = 'Create a free FileFlow account to manage your folders and track uploads.';
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
            
            <h1>Create Account</h1>
            <p>Get started with your free FileFlow account</p>
        </div>
        <form id="register-form" class="auth-form" autocomplete="on">
            <div class="form-group">
                <label for="reg-name">Full Name</label>
                <div class="form-input-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <input type="text" id="reg-name" name="full_name" placeholder="" required minlength="2" maxlength="100" autocomplete="name">
                </div>
            </div>
            <div class="form-group">
                <label for="reg-email">Email Address</label>
                <div class="form-input-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <input type="email" id="reg-email" name="email" placeholder="" required autocomplete="email">
                </div>
            </div>
            <div class="form-group">
                <label for="reg-password">Password</label>
                <div class="form-input-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input type="password" id="reg-password" name="password" placeholder="" required minlength="6" autocomplete="new-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('reg-password', this)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="reg-confirm">Confirm Password</label>
                <div class="form-input-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <input type="password" id="reg-confirm" name="confirm_password" placeholder="" required minlength="6" autocomplete="new-password">
                </div>
            </div>
            <div class="form-error" id="register-error" style="display:none;"></div>
            <button type="submit" class="btn btn-primary btn-lg btn-full" id="btn-register">
                <span class="btn-text">Create Account</span>
                <span class="btn-loader" style="display:none;"><svg width="20" height="20" viewBox="0 0 24 24" class="spinner"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="30 70" stroke-linecap="round"/></svg></span>
            </button>
        </form>
        <div class="auth-footer">
            <p>Already have an account? <a href="/login" class="form-link-bold">Sign in</a></p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
