<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();
$userId = $user['id'];
$db = getDB();

if ($action === 'update_profile') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $workExperience = trim($_POST['work_experience'] ?? '');
    $socialLinks = trim($_POST['social_links'] ?? ''); // Expecting JSON string or plain text
    $profileSlug = trim($_POST['profile_slug'] ?? '');
    $cvDescription = trim($_POST['cv_description'] ?? '');
    $cvButtonColor = trim($_POST['cv_button_color'] ?? '#16a34a');

    if (empty($fullName)) {
        echo json_encode(['success' => false, 'message' => 'Name cannot be empty']);
        exit;
    }

    if (!empty($profileSlug)) {
        $profileSlug = strtolower(preg_replace('/[^a-z0-9_-]/', '', $profileSlug));
        $stmt = $db->prepare("SELECT id FROM users WHERE profile_slug = ? AND id != ?");
        $stmt->execute([$profileSlug, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This URL already exists.']);
            exit;
        }
    } else {
        $profileSlug = null;
    }

    $isPublic = isset($_POST['is_public']) && $_POST['is_public'] === '1' ? 1 : 0;

    try {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, work_experience = ?, social_links = ?, profile_slug = ?, cv_description = ?, cv_button_color = ?, is_public = ? WHERE id = ?");
        $stmt->execute([$fullName, $phone, $workExperience, $socialLinks, $profileSlug, $cvDescription, $cvButtonColor, $isPublic, $userId]);
        $_SESSION['user_name'] = $fullName; // update session
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } catch (PDOException $e) {
        // Fallback: column might not exist. Create columns and tables, then retry.
        try {
            $db->exec("ALTER TABLE users ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0");
        } catch (Exception $ex) {}
        try {
            $db->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0");
        } catch (Exception $ex) {}
        try {
            $db->exec("ALTER TABLE users ADD COLUMN space_limit_mb INT NOT NULL DEFAULT 100");
        } catch (Exception $ex) {}
        
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS `connections` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `requester_id` INT UNSIGNED NOT NULL,
                `receiver_id` INT UNSIGNED NOT NULL,
                `status` ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`requester_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                UNIQUE KEY `unique_connection` (`requester_id`, `receiver_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $db->exec("CREATE TABLE IF NOT EXISTS `messages` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `sender_id` INT UNSIGNED NOT NULL,
                `receiver_id` INT UNSIGNED NOT NULL,
                `message` TEXT NOT NULL,
                `is_read` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                INDEX `idx_sender` (`sender_id`),
                INDEX `idx_receiver` (`receiver_id`),
                INDEX `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } catch (Exception $ex) {}
        
        try {
            // Re-run the update
            $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, work_experience = ?, social_links = ?, profile_slug = ?, cv_description = ?, cv_button_color = ?, is_public = ? WHERE id = ?");
            if ($stmt->execute([$fullName, $phone, $workExperience, $socialLinks, $profileSlug, $cvDescription, $cvButtonColor, $isPublic, $userId])) {
                $_SESSION['user_name'] = $fullName;
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
            }
        } catch (PDOException $e2) {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile. Database error.']);
        }
    }
    exit;
}

if ($action === 'upload_avatar') {
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid image']);
        exit;
    }

    $file = $_FILES['avatar'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowedMimes)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, and WEBP images are allowed']);
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
    $targetDir = __DIR__ . '/../uploads/avatars/';
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . $filename;
    
    // Remove old avatar if exists
    if (!empty($user['avatar_path'])) {
        $oldPath = __DIR__ . '/..' . $user['avatar_path'];
        if (file_exists($oldPath)) unlink($oldPath);
    }

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $publicPath = '/uploads/avatars/' . $filename;
        $stmt = $db->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
        $stmt->execute([$publicPath, $userId]);
        $_SESSION['user_avatar'] = $publicPath;
        
        echo json_encode(['success' => true, 'message' => 'Valid picture uploaded', 'avatar_path' => $publicPath]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save image']);
    }
    exit;
}

if ($action === 'upload_cover') {
    if (!isset($_FILES['cover']) || $_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid image']);
        exit;
    }

    $file = $_FILES['cover'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowedMimes)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, and WEBP images are allowed']);
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'cover_' . $userId . '_' . time() . '.' . $ext;
    $targetDir = __DIR__ . '/../uploads/covers/';
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . $filename;
    
    if (!empty($user['cover_path'])) {
        $oldPath = __DIR__ . '/..' . $user['cover_path'];
        if (file_exists($oldPath)) unlink($oldPath);
    }

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $publicPath = '/uploads/covers/' . $filename;
        $stmt = $db->prepare("UPDATE users SET cover_path = ? WHERE id = ?");
        $stmt->execute([$publicPath, $userId]);
        
        echo json_encode(['success' => true, 'message' => 'Cover photo uploaded successfully', 'cover_path' => $publicPath]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save cover image']);
    }
    exit;
}

if ($action === 'remove_cover') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }

    if (!empty($user['cover_path'])) {
        $oldPath = __DIR__ . '/..' . $user['cover_path'];
        if (file_exists($oldPath)) unlink($oldPath);
    }
    
    $stmt = $db->prepare("UPDATE users SET cover_path = NULL WHERE id = ?");
    $stmt->execute([$userId]);

    echo json_encode(['success' => true, 'message' => 'Cover removed successfully']);
    exit;
}

if ($action === 'remove_avatar') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }

    if (!empty($user['avatar_path'])) {
        $oldPath = __DIR__ . '/..' . $user['avatar_path'];
        if (file_exists($oldPath)) unlink($oldPath);
    }
    
    $stmt = $db->prepare("UPDATE users SET avatar_path = NULL WHERE id = ?");
    $stmt->execute([$userId]);
    $_SESSION['user_avatar'] = null;

    echo json_encode(['success' => true, 'message' => 'Avatar removed successfully']);
    exit;
}

if ($action === 'upload_cv') {
    if (!isset($_FILES['cv_file']) || $_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid file']);
        exit;
    }

    $file = $_FILES['cv_file'];
    $allowedExts = ['pdf', 'doc', 'docx'];
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts)) {
        echo json_encode(['success' => false, 'message' => 'Only PDF and DOC/DOCX files are allowed']);
        exit;
    }

    $filename = 'cv_' . $userId . '_' . time() . '.' . $ext;
    $targetDir = __DIR__ . '/../uploads/cv/';
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . $filename;
    
    // Fetch current user details to check for old CV
    $stmt = $db->prepare("SELECT cv_path FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currUser = $stmt->fetch();

    if (!empty($currUser['cv_path'])) {
        $oldPath = __DIR__ . '/..' . $currUser['cv_path'];
        if (file_exists($oldPath)) unlink($oldPath);
    }

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $publicPath = '/uploads/cv/' . $filename;
        $stmt = $db->prepare("UPDATE users SET cv_path = ? WHERE id = ?");
        $stmt->execute([$publicPath, $userId]);
        
        echo json_encode(['success' => true, 'message' => 'CV uploaded successfully', 'cv_path' => $publicPath]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save CV']);
    }
    exit;
}

if ($action === 'remove_cv') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }

    $stmt = $db->prepare("SELECT cv_path FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currUser = $stmt->fetch();

    if (!empty($currUser['cv_path'])) {
        $oldPath = __DIR__ . '/..' . $currUser['cv_path'];
        if (file_exists($oldPath)) unlink($oldPath);
    }
    
    $stmt = $db->prepare("UPDATE users SET cv_path = NULL WHERE id = ?");
    $stmt->execute([$userId]);

    echo json_encode(['success' => true, 'message' => 'CV removed successfully']);
    exit;
}

if ($action === 'check_slug') {
    $slug = strtolower(preg_replace('/[^a-z0-9_-]/', '', trim($_GET['slug'] ?? '')));
    if (empty($slug)) {
        echo json_encode(['available' => false]);
        exit;
    }
    
    // Disallow reserved names
    $reserved = ['admin', 'api', 'assets', 'config', 'includes', 'pages', 'user_documents', 'uploads', 'index', 'login', 'register', 'dashboard', 'settings', 'logout', 'forgot-password', 'reset-password'];
    if (in_array($slug, $reserved)) {
        echo json_encode(['available' => false, 'message' => 'Reserved']);
        exit;
    }

    $stmt = $db->prepare("SELECT id FROM users WHERE profile_slug = ? AND id != ?");
    $stmt->execute([$slug, $userId]);
    
    if ($stmt->fetch()) {
        echo json_encode(['available' => false]);
    } else {
        echo json_encode(['available' => true]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
