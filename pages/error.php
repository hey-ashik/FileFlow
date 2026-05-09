<?php
/**
 * FileFlow - Error Page
 */

$errorType = $errorType ?? 'not_found';
$currentPage = 'error';

$errorMessages = [
    'not_found' => [
        'title' => 'Page Not Found',
        'message' => 'The page you\'re looking for doesn\'t exist or has been removed.',
        'icon' => '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="9" y1="12" x2="15" y2="12"/></svg>',
        'code' => '404'
    ],
    'invalid' => [
        'title' => 'Invalid Access',
        'message' => 'You don\'t have permission to access this resource.',
        'icon' => '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
        'code' => '403'
    ],
    'server_error' => [
        'title' => 'Server Error',
        'message' => 'Something went wrong on our end. Please try again later.',
        'icon' => '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>',
        'code' => '500'
    ]
];

$error = $errorMessages[$errorType] ?? $errorMessages['not_found'];
$pageTitle = $error['title'] . ' - ' . APP_NAME;
$pageDescription = $error['message'];

http_response_code(intval($error['code']));

require_once __DIR__ . '/../includes/header.php';
?>

<section class="error-page" id="error-page">
    <div class="error-content">
        <div class="error-code"><?php echo $error['code']; ?></div>
        <div class="error-icon">
            <?php echo $error['icon']; ?>
        </div>
        <h1 class="error-title"><?php echo $error['title']; ?></h1>
        <p class="error-message"><?php echo $error['message']; ?></p>
        <div class="error-actions">
            <a href="/" class="btn btn-primary btn-lg" id="error-home-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Go Home
            </a>
            <button class="btn btn-ghost btn-lg" onclick="history.back()" id="error-back-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Go Back
            </button>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
