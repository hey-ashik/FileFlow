<?php
$currentPage = 'login';
$pageTitle = 'Login - ' . APP_NAME;
$pageDescription = 'Sign in to your FileFlow account to manage your folders and files.';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="auth-page" id="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            
            <h1>Welcome Back</h1>
            <p>Sign in to access your dashboard</p>
        </div>
        <form id="login-form" class="auth-form" autocomplete="on">
            <div class="form-group">
                <label for="login-email">Email Address</label>
                <div class="form-input-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <input type="email" id="login-email" name="email" placeholder="you@example.com" required autocomplete="email">
                </div>
            </div>
            <div class="form-group">
                <label for="login-password">Password</label>
                <div class="form-input-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input type="password" id="login-password" name="password" placeholder="Your password" required minlength="6" autocomplete="current-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('login-password', this)" aria-label="Toggle password">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="form-row">
                <label class="form-checkbox"><input type="checkbox" name="remember"> Remember me</label>
                <a href="/forgot-password" class="form-link">Forgot password?</a>
            </div>
            <div class="form-error" id="login-error" style="display:none;"></div>
            <button type="submit" class="btn btn-primary btn-lg btn-full" id="btn-login">
                <span class="btn-text">Sign In</span>
                <span class="btn-loader" style="display:none;"><svg width="20" height="20" viewBox="0 0 24 24" class="spinner"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="30 70" stroke-linecap="round"/></svg></span>
            </button>
        </form>
        <div class="auth-footer">
            <p>Don't have an account? <a href="/register" class="form-link-bold">Create one</a></p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
