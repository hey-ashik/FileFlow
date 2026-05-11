<?php
/**
 * FileFlow - Authentication Functions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Register a new user
 */
function registerUser(string $fullName, string $email, string $password): array {
    $errors = [];
    $fullName = trim($fullName);
    $email = trim(strtolower($email));

    if (strlen($fullName) < 2 || strlen($fullName) > 100) {
        $errors[] = 'Name must be between 2 and 100 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if (!empty($errors)) return ['success' => false, 'errors' => $errors];

    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'errors' => ['An account with this email already exists.']];
    }

    $colors = ['#16a34a','#059669','#0d9488','#0891b2','#6366f1','#8b5cf6','#ec4899'];
    $avatarColor = $colors[array_rand($colors)];
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    try {
        $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, avatar_color) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fullName, $email, $hash, $avatarColor]);
        $userId = $db->lastInsertId();

        return ['success' => true, 'user_id' => $userId];
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
    }
}

/**
 * Authenticate user
 */
function loginUser(string $email, string $password): array {
    $email = trim(strtolower($email));
    if (empty($email) || empty($password)) {
        return ['success' => false, 'errors' => ['Please fill in all fields.']];
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'errors' => ['Invalid email or password.']];
    }

    // Update last login
    $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

    // Set session
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_color'] = $user['avatar_color'];
    $_SESSION['user_avatar'] = $user['avatar_path'] ?? null;
    $_SESSION['user_timezone'] = $user['timezone'] ?? 'UTC';
    $_SESSION['is_admin'] = (isset($user['is_admin']) && $user['is_admin'] == 1);

    return ['success' => true, 'is_admin' => $_SESSION['is_admin'], 'user' => [
        'id' => $user['id'],
        'name' => $user['full_name'],
        'email' => $user['email']
    ]];
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Get current user data
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'color' => $_SESSION['user_color'] ?? '#16a34a',
        'avatar_path' => $user['avatar_path'] ?? null,
        'cover_path' => $user['cover_path'] ?? null,
        'profile_slug' => $user['profile_slug'] ?? null,
        'phone' => $user['phone'] ?? null,
        'work_experience' => $user['work_experience'] ?? null,
        'social_links' => $user['social_links'] ?? null,
        'cv_path' => $user['cv_path'] ?? null,
        'cv_description' => $user['cv_description'] ?? null,
        'cv_button_color' => $user['cv_button_color'] ?? '#16a34a',
        'profile_visits' => $user['profile_visits'] ?? 0,
        'is_public' => $user['is_public'] ?? 0,
        'timezone' => $_SESSION['user_timezone'] ?? 'UTC',
        'is_admin' => $_SESSION['is_admin'] ?? false
    ];
}

/**
 * Logout user
 */
function logoutUser(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_unset();
    session_destroy();
}

/**
 * Create password reset token
 */
function createPasswordReset(string $email): array {
    $email = trim(strtolower($email));
    $db = getDB();
    $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Don't reveal if email exists
        return ['success' => true, 'message' => 'If that email is registered, you will receive a reset link.'];
    }

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    $db->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0")->execute([$user['id']]);
    $db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)")->execute([$user['id'], $token, $expires]);

    // In production, send email. For now, return the link.
    $resetLink = APP_URL . '/reset-password?token=' . $token;

    return [
        'success' => true,
        'message' => 'If that email is registered, you will receive a reset link.',
        'reset_link' => $resetLink, // Remove in production, use email instead
        'debug_token' => $token
    ];
}

/**
 * Reset password using token
 */
function resetPassword(string $token, string $newPassword): array {
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'errors' => ['Password must be at least 6 characters.']];
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT pr.*, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        return ['success' => false, 'errors' => ['Invalid or expired reset link.']];
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $reset['user_id']]);
    $db->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")->execute([$reset['id']]);

    return ['success' => true, 'message' => 'Password reset successfully. You can now login.'];
}

/**
 * Get dashboard stats for a user
 */
function getUserDashboardStats(int $userId): array {
    $db = getDB();

    $folderCount = $db->prepare("SELECT COUNT(*) FROM folders WHERE user_id = ? AND is_active = 1");
    $folderCount->execute([$userId]);
    $folders = $folderCount->fetchColumn();

    $fileStats = $db->prepare("SELECT COUNT(f.id) as total_files, COALESCE(SUM(f.file_size), 0) as total_size FROM files f JOIN folders fo ON f.folder_id = fo.id WHERE fo.user_id = ?");
    $fileStats->execute([$userId]);
    $stats = $fileStats->fetch();

    $downloads = $db->prepare("SELECT COALESCE(SUM(f.download_count), 0) FROM files f JOIN folders fo ON f.folder_id = fo.id WHERE fo.user_id = ?");
    $downloads->execute([$userId]);
    $totalDownloads = $downloads->fetchColumn();

    return [
        'folders' => (int)$folders,
        'files' => (int)$stats['total_files'],
        'total_size' => (int)$stats['total_size'],
        'total_size_formatted' => formatFileSize((int)$stats['total_size']),
        'downloads' => (int)$totalDownloads
    ];
}

/**
 * Get user folders
 */
function getUserFolders(int $userId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM folders WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Get upload stats by day for the last 7 days (for chart)
 */
function getUploadStats(int $userId, int $days = 7): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT DATE(f.uploaded_at) as upload_date, COUNT(f.id) as file_count, COALESCE(SUM(f.file_size), 0) as total_size
        FROM files f
        JOIN folders fo ON f.folder_id = fo.id
        WHERE fo.user_id = ? AND f.uploaded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(f.uploaded_at)
        ORDER BY upload_date ASC
    ");
    $stmt->execute([$userId, $days]);
    $results = $stmt->fetchAll();

    // Fill missing days
    $data = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayLabel = date('D', strtotime($date));
        $data[$date] = ['date' => $date, 'label' => $dayLabel, 'files' => 0, 'size' => 0];
    }
    foreach ($results as $row) {
        if (isset($data[$row['upload_date']])) {
            $data[$row['upload_date']]['files'] = (int)$row['file_count'];
            $data[$row['upload_date']]['size'] = (int)$row['total_size'];
        }
    }

    return array_values($data);
}

/**
 * Get file type distribution for user
 */
function getFileTypeStats(int $userId): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT f.file_extension, COUNT(f.id) as count, COALESCE(SUM(f.file_size), 0) as total_size
        FROM files f JOIN folders fo ON f.folder_id = fo.id
        WHERE fo.user_id = ?
        GROUP BY f.file_extension ORDER BY count DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
