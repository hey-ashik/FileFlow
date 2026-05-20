<?php
/**
 * FileFlow - Utility Functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Setup database schema and admin user if they do not exist
 */
function setupAdminAndSchema(): void {
    static $setupDone = false;
    if ($setupDone) return;
    $setupDone = true;

    // Schema setup runs on every request for now to ensure all columns are present
    // especially during this transition phase.

    try {
        $db = getDB();
        
        // Add columns if not exist
        try {
            $db->query("SELECT is_admin FROM users LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0");
        }
        
        try {
            $db->query("SELECT space_limit_mb FROM users LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE users ADD COLUMN space_limit_mb INT NOT NULL DEFAULT 100");
        }

        try {
            $db->query("SELECT file_upload_limit_mb FROM users LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE users ADD COLUMN file_upload_limit_mb INT NOT NULL DEFAULT 50");
        }

        try {
            $db->query("SELECT folder_limit FROM users LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE users ADD COLUMN folder_limit INT NOT NULL DEFAULT 3");
        }

        try {
            $db->query("SELECT avatar_path FROM users LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE users 
                ADD COLUMN `avatar_path` VARCHAR(255) DEFAULT NULL,
                ADD COLUMN `phone` VARCHAR(30) DEFAULT NULL,
                ADD COLUMN `work_experience` TEXT DEFAULT NULL,
                ADD COLUMN `social_links` TEXT DEFAULT NULL,
                ADD COLUMN `profile_slug` VARCHAR(60) UNIQUE DEFAULT NULL;");
        }
        
        try {
            $db->query("SELECT cover_path FROM users LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE users ADD COLUMN `cover_path` VARCHAR(255) DEFAULT NULL");
        }
        
        try {
            $db->query("SELECT cv_path FROM users LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE users 
                ADD COLUMN `cv_path` VARCHAR(255) DEFAULT NULL,
                ADD COLUMN `cv_description` TEXT DEFAULT NULL,
                ADD COLUMN `cv_button_color` VARCHAR(20) NOT NULL DEFAULT '#16a34a'");
        }

        try {
            $db->query("SELECT password_hash FROM folders LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE folders ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL");
        }
        
        try {
            $db->query("SELECT expires_at FROM folders LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE folders ADD COLUMN expires_at DATETIME DEFAULT NULL");
        }
        
        try {
            $db->query("SELECT profile_visits FROM users LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE users ADD COLUMN profile_visits INT UNSIGNED NOT NULL DEFAULT 0");
        }

        try {
            $db->query("SELECT ip_address FROM folders LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE folders ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL");
        }

        try {
            $db->query("SELECT device_id FROM folders LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE folders ADD COLUMN device_id VARCHAR(64) DEFAULT NULL");
        }

        // Mark setup as complete to improve performance on next loads
        $email = 'ashikulislam2070@gmail.com';
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $hash = password_hash('Ashik@21032001', PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("INSERT INTO users (full_name, email, password_hash, avatar_color, is_admin, space_limit_mb) VALUES (?, ?, ?, ?, ?, ?)")
               ->execute(['Admin', $email, $hash, '#16a34a', 1, 1000]);
        } else {
            // Ensure they are admin
            $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?")->execute([$user['id']]);
        }

        // Mark setup as complete to improve performance on next loads
        @file_put_contents(UPLOAD_DIR . '.db_optimized', date('Y-m-d H:i:s'));

        // Run cleanup
        cleanupExpiredFolders();
    } catch (PDOException $e) {
        error_log("Setup error: " . $e->getMessage());
    }
}

/**
 * Get user IP address
 */
function getUserIP(): string {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $ips = explode(',', $ip);
        $ip = trim($ips[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    return $ip;
}

/**
 * Get unique device ID (stored in cookie)
 */
function getDeviceID(): string {
    if (isset($_COOKIE['ff_device_id'])) {
        return $_COOKIE['ff_device_id'];
    }
    
    // Generate a new unique device ID
    $deviceId = bin2hex(random_bytes(32)); // 64 chars
    // Set cookie for 10 years
    setcookie('ff_device_id', $deviceId, time() + (10 * 365 * 24 * 60 * 60), '/', '', false, true);
    $_COOKIE['ff_device_id'] = $deviceId; // Make available for current request
    return $deviceId;
}

/**
 * Sanitize folder name for URL slug
 */
function sanitizeSlug(string $name): string {
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9_-]/', '', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-_');
    return $slug;
}

/**
 * Validate folder name
 */
function validateFolderName(string $name): array {
    $errors = [];
    
    if (strlen($name) < FOLDER_NAME_MIN_LENGTH) {
        $errors[] = 'Folder name must be at least ' . FOLDER_NAME_MIN_LENGTH . ' characters.';
    }
    
    if (strlen($name) > FOLDER_NAME_MAX_LENGTH) {
        $errors[] = 'Folder name must be no more than ' . FOLDER_NAME_MAX_LENGTH . ' characters.';
    }
    
    if (!preg_match(FOLDER_NAME_PATTERN, $name)) {
        $errors[] = 'Folder name can only contain letters, numbers, hyphens, and underscores. Must start with a letter or number.';
    }
    
    // Reserved names
    $reserved = ['admin', 'api', 'assets', 'config', 'includes', 'pages', 'user_documents', 'uploads', 'index', 'login', 'register', 'dashboard', 'settings', 'logout', 'forgot-password', 'reset-password'];
    if (in_array(strtolower($name), $reserved)) {
        $errors[] = 'This folder name is reserved. Please choose another.';
    }
    
    return $errors;
}

/**
 * Check if folder name already exists
 */
function folderExists(string $slug): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM folders WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Create a new folder
 */
function createFolder(string $name, ?string $password = null, ?string $expiry = null): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['user_id'])) {
        $db = getDB();
        $userId = $_SESSION['user_id'];
        
        try {
            $db->query("SELECT folder_limit FROM users LIMIT 1");
        } catch (PDOException $e) {
            try {
                $db->exec("ALTER TABLE users ADD COLUMN folder_limit INT NOT NULL DEFAULT 3");
            } catch (PDOException $ex) {}
        }
        
        $stmt = $db->prepare("SELECT folder_limit FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userLimit = $stmt->fetchColumn();
        if ($userLimit === false) {
            $userLimit = 3;
        }
        
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM folders WHERE user_id = ?");
        $stmtCount->execute([$userId]);
        $currentFolders = $stmtCount->fetchColumn();
        
        if ($currentFolders >= $userLimit) {
            return [
                'success' => false,
                'errors' => ["You have reached your limit of {$userLimit} folders. Please delete an existing folder or Buy Premium Package to increase your limit."]
            ];
        }
    } else {
        // No-account (guest) users folder limit check (detected by Device ID and IP address)
        $db = getDB();
        $ip = getUserIP();
        $deviceId = getDeviceID();
        
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM folders WHERE user_id IS NULL AND (device_id = ? OR ip_address = ?)");
        $stmtCount->execute([$deviceId, $ip]);
        $guestFolders = $stmtCount->fetchColumn();
        
        if ($guestFolders >= 3) {
            return [
                'success' => false,
                'errors' => ["You have reached your limit of 3 folders. Please create an account to create more folders."]
            ];
        }
    }

    $slug = sanitizeSlug($name);
    $displayName = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
    
    // Validate
    $errors = validateFolderName($name);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Check duplicate
    if (folderExists($slug)) {
        return ['success' => false, 'errors' => ['A folder with this name already exists.']];
    }
    
    // Create physical directory
    $dirPath = UPLOAD_DIR . $slug;
    if (!is_dir($dirPath)) {
        if (!mkdir($dirPath, 0755, true)) {
            return ['success' => false, 'errors' => ['Failed to create folder on server.']];
        }
        // Create an index.html to prevent directory listing
        file_put_contents($dirPath . '/index.html', '<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>403 Forbidden</h1></body></html>');
    }
    
    // Insert into database
    try {
        $db = getDB();
        $userId = null;
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['user_id'])) $userId = $_SESSION['user_id'];
        
        // Get user IP address and Device ID
        $ip = getUserIP();
        $deviceId = getDeviceID();
        
        // Handle password hashing
        $passwordHash = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : null;
        
        // Handle expiration
        $expiresAt = null;
        if (!empty($expiry) && $expiry !== 'never') {
            $date = new DateTime();
            switch ($expiry) {
                case '1h': $date->modify('+1 hour'); break;
                case '24h': $date->modify('+24 hours'); break;
                case '7d': $date->modify('+7 days'); break;
                case '30d': $date->modify('+30 days'); break;
            }
            $expiresAt = $date->format('Y-m-d H:i:s');
        }

        // Try with new columns
        try {
            $stmt = $db->prepare("INSERT INTO folders (folder_name, slug, display_name, user_id, password_hash, expires_at, ip_address, device_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $displayName, $userId, $passwordHash, $expiresAt, $ip, $deviceId]);
        } catch (PDOException $colErr) {
            try {
                // Fallback for older schema if migration hasn't run yet
                $stmt = $db->prepare("INSERT INTO folders (folder_name, slug, display_name, user_id, password_hash, expires_at, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $displayName, $userId, $passwordHash, $expiresAt, $ip]);
            } catch (PDOException $colErr2) {
                // Older fallback
                $stmt = $db->prepare("INSERT INTO folders (folder_name, slug, display_name, user_id, ip_address) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $displayName, $userId, $ip]);
            }
        }
        
        return [
            'success' => true,
            'folder' => [
                'id' => $db->lastInsertId(),
                'name' => $name,
                'slug' => $slug,
                'display_name' => $displayName,
                'url' => APP_URL . '/' . $slug
            ]
        ];
    } catch (PDOException $e) {
        error_log("Folder creation failed: " . $e->getMessage());
        if ($e->getCode() == 23000) {
            return ['success' => false, 'errors' => ['A folder with this name already exists.']];
        }
        return ['success' => false, 'errors' => ['An error occurred while creating the folder.']];
    }
}

/**
 * Get folder info by slug
 */
function getFolderBySlug(string $slug): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM folders WHERE slug = ? AND is_active = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function incrementFolderVisits(int $folderId): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE folders SET visits = visits + 1 WHERE id = ?");
        $stmt->execute([$folderId]);
    } catch (PDOException $e) {
        error_log("Failed to increment visits: " . $e->getMessage());
    }
}

/**
 * Clean up expired folders
 */
function cleanupExpiredFolders(): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, slug FROM folders WHERE expires_at IS NOT NULL AND expires_at < NOW()");
        $stmt->execute();
        $expiredFolders = $stmt->fetchAll();

        foreach ($expiredFolders as $folder) {
            // Get all files in this folder
            $files = getFilesByFolderId($folder['id']);
            foreach ($files as $file) {
                deleteFile($file['id']);
            }

            // Delete the physical directory
            $dirPath = UPLOAD_DIR . $folder['slug'];
            if (is_dir($dirPath)) {
                // Delete everything inside
                $filesInDir = array_diff(scandir($dirPath), ['.', '..']);
                foreach ($filesInDir as $fileInDir) {
                    @unlink($dirPath . '/' . $fileInDir);
                }
                @rmdir($dirPath);
            }

            // Delete folder from database
            $db->prepare("DELETE FROM folders WHERE id = ?")->execute([$folder['id']]);
        }
    } catch (PDOException $e) {
        error_log("Cleanup error: " . $e->getMessage());
    }
}

/**
 * Increment profile visits
 */
function incrementProfileVisits(string $slug): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET profile_visits = profile_visits + 1 WHERE profile_slug = ?");
        $stmt->execute([$slug]);
    } catch (PDOException $e) {
        error_log("Failed to increment profile visits: " . $e->getMessage());
    }
}

/**
 * Get files in a folder
 */
function getFilesByFolderId(int $folderId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM files WHERE folder_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$folderId]);
    return $stmt->fetchAll();
}

/**
 * Get file by ID
 */
function getFileById(int $fileId): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT f.*, fo.slug as folder_slug FROM files f JOIN folders fo ON f.folder_id = fo.id WHERE f.id = ?");
    $stmt->execute([$fileId]);
    return $stmt->fetch() ?: null;
}

/**
 * Format file size
 */
function formatFileSize(int $bytes): string {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    }
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

/**
 * Get file type category
 */
function getFileCategory(string $ext): string {
    $ext = strtolower($ext);
    return FILE_TYPE_CATEGORIES[$ext] ?? 'other';
}

/**
 * Get file icon
 */
function getFileIcon(string $ext): string {
    $ext = strtolower($ext);
    return FILE_TYPE_ICONS[$ext] ?? '📎';
}

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY)) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate uploaded file
 */
function validateFile(array $file, int $maxSizeBytes = MAX_FILE_SIZE): array {
    $errors = [];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form upload limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'File upload was stopped by server extension.',
        ];
        $errors[] = $uploadErrors[$file['error']] ?? 'Unknown upload error.';
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $maxSizeBytes) {
        $errors[] = 'File size exceeds the maximum limit of ' . formatFileSize($maxSizeBytes) . '.';
    }
    
    if ($file['size'] === 0) {
        $errors[] = 'File is empty.';
    }
    
    // Get and validate extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!array_key_exists($ext, ALLOWED_EXTENSIONS)) {
        $errors[] = 'File type ".' . $ext . '" is not allowed.';
        return $errors;
    }
    
    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($file['tmp_name']);
    
    if (!in_array($detectedMime, ALLOWED_EXTENSIONS[$ext])) {
        // Allow some flexibility for Office documents
        $officeExts = ['docx', 'pptx', 'xlsx', 'doc', 'ppt', 'xls'];
        $officeMimes = [
            'application/octet-stream',
            'application/zip',
            'application/x-zip',
            'application/x-zip-compressed',
            'application/vnd.ms-office',
            'application/msword',
            'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint',
            'application/CDFV2',
            'application/cdfv2-corrupt',
            'application/x-ole-storage',
            'application/wps-office.xls',
            'application/wps-office.xlsx',
            'application/wps-office.doc',
            'application/wps-office.docx',
            'application/wps-office.ppt',
            'application/wps-office.pptx',
            'text/html',
            'text/plain',
            'text/csv',
            'text/xml',
            'application/xml'
        ];
        
        // Use case-insensitive matching for MIME types
        $detectedMimeLower = strtolower($detectedMime);
        $officeMimesLower = array_map('strtolower', $officeMimes);
        
        if (!(in_array($ext, $officeExts) && in_array($detectedMimeLower, $officeMimesLower))) {
            $errors[] = 'File content does not match the expected type for ".' . $ext . '".';
        }
    }
    
    // Check for dangerous content in the filename
    $dangerousPatterns = ['.php', '.phtml', '.php3', '.php4', '.php5', '.phps', '.phar', '.sh', '.bash', '.exe', '.bat', '.cmd', '.com', '.vbs', '.js', '.wsh', '.wsf'];
    $lowerName = strtolower($file['name']);
    foreach ($dangerousPatterns as $pattern) {
        if (strpos($lowerName, $pattern) !== false && $pattern !== '.' . $ext) {
            $errors[] = 'Potentially dangerous file detected.';
            break;
        }
    }
    
    return $errors;
}

/**
 * Handle file upload
 */
function uploadFile(array $file, int $folderId, string $folderSlug, int $maxSizeBytes = MAX_FILE_SIZE): array {
    $errors = validateFile($file, $maxSizeBytes);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
    $originalName = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '', $originalName);
    $originalName = substr($originalName, 0, 200);
    
    // Generate unique stored name
    $storedName = uniqid('ff_', true) . '_' . time() . '.' . $ext;
    
    $targetDir = UPLOAD_DIR . $folderSlug . '/';
    $targetPath = $targetDir . $storedName;
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $isMergedChunk = isset($file['is_merged_chunk']) && $file['is_merged_chunk'] === true;
    $saveSuccess = false;
    
    if ($isMergedChunk) {
        if (rename($file['tmp_name'], $targetPath)) {
            $saveSuccess = true;
        } else {
            if (copy($file['tmp_name'], $targetPath)) {
                $saveSuccess = true;
                @unlink($file['tmp_name']);
            }
        }
    } else {
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $saveSuccess = true;
        }
    }
    
    if (!$saveSuccess) {
        return ['success' => false, 'errors' => ['Failed to save the uploaded file.']];
    }
    
    // Insert into database
    try {
        $db = getDB();
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($targetPath);
        
        $stmt = $db->prepare("INSERT INTO files (folder_id, original_name, stored_name, file_extension, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $folderId,
            $originalName . '.' . $ext,
            $storedName,
            $ext,
            $file['size'],
            $mimeType
        ]);
        
        // Update folder stats
        $db->prepare("UPDATE folders SET total_files = total_files + 1, total_size = total_size + ? WHERE id = ?")->execute([$file['size'], $folderId]);
        
        return [
            'success' => true,
            'file' => [
                'id' => $db->lastInsertId(),
                'name' => $originalName . '.' . $ext,
                'size' => formatFileSize($file['size']),
                'extension' => $ext,
                'category' => getFileCategory($ext),
                'icon' => getFileIcon($ext),
                'uploaded_at' => date('M d, Y h:i A')
            ]
        ];
    } catch (PDOException $e) {
        error_log("File upload DB error: " . $e->getMessage());
        @unlink($targetPath);
        return ['success' => false, 'errors' => ['Database error while saving file info.']];
    }
}

/**
 * Get recent folders for stats
 */
function getRecentFolders(int $limit = 10): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM folders WHERE is_active = 1 ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get platform stats
 */
function getPlatformStats(): array {
    $db = getDB();
    
    $folderCount = $db->query("SELECT COUNT(*) FROM folders WHERE is_active = 1")->fetchColumn();
    $fileCount = $db->query("SELECT COUNT(*) FROM files")->fetchColumn();
    $totalSize = $db->query("SELECT COALESCE(SUM(total_size), 0) FROM folders")->fetchColumn();
    
    return [
        'folders' => $folderCount,
        'files' => $fileCount,
        'total_size' => formatFileSize($totalSize)
    ];
}

/**
 * JSON response helper
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Time ago helper
 */
function timeAgo(string $datetime): string {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/**
 * Delete a file
 */
function deleteFile(int $fileId): array {
    $file = getFileById($fileId);
    if (!$file) {
        return ['success' => false, 'errors' => ['File not found.']];
    }
    
    $filePath = UPLOAD_DIR . $file['folder_slug'] . '/' . $file['stored_name'];
    
    try {
        $db = getDB();
        
        // Delete from database
        $stmt = $db->prepare("DELETE FROM files WHERE id = ?");
        $stmt->execute([$fileId]);
        
        // Update folder stats
        $db->prepare("UPDATE folders SET total_files = GREATEST(total_files - 1, 0), total_size = GREATEST(total_size - ?, 0) WHERE id = ?")->execute([$file['file_size'], $file['folder_id']]);
        
        // Delete physical file
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("File deletion error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['Failed to delete file.']];
    }
}

/**
 * Determine dynamic chunk size based on server upload limits
 */
function getUploadChunkSize(): int {
    $max_upload = parsePhpSize(ini_get('upload_max_filesize'));
    $max_post = parsePhpSize(ini_get('post_max_size'));
    $memory_limit = parsePhpSize(ini_get('memory_limit'));
    
    // Find the smallest limit
    $limit = min($max_upload ?: 2 * 1024 * 1024, $max_post ?: 8 * 1024 * 1024);
    if ($memory_limit > 0) {
        $limit = min($limit, $memory_limit);
    }
    
    // Set chunk size to 90% of the smallest limit, bounded between 1MB and 5MB
    $chunkSize = floor($limit * 0.9);
    if ($chunkSize < 1024 * 1024) {
        $chunkSize = 1024 * 1024; // 1MB minimum
    }
    if ($chunkSize > 5 * 1024 * 1024) {
        $chunkSize = 5 * 1024 * 1024; // 5MB maximum
    }
    return (int)$chunkSize;
}

function parsePhpSize(string $size): int {
    $size = trim($size);
    if (empty($size)) return 0;
    $last = strtolower($size[strlen($size)-1]);
    $val = (int)$size;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}
