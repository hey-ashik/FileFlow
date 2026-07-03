<?php
/**
 * FileFlow - Application Configuration
 */

// Set Default Timezone to Local Time
date_default_timezone_set('Asia/Dhaka');

// Application settings
define('APP_NAME', 'FileFlow');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://fileflow.ashikone.com');
define('APP_DESCRIPTION', 'Secure and simple file sharing platform. Create folders, upload files, and share with anyone.');

// File upload settings
define('UPLOAD_DIR', __DIR__ . '/../user_documents/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50 MB
define('MAX_FILES_PER_UPLOAD', 10);

// Allowed file extensions and their MIME types
define('ALLOWED_EXTENSIONS', [
    // Documents
    'pdf'  => ['application/pdf'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'doc'  => ['application/msword'],
    'ppt'  => ['application/vnd.ms-powerpoint'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
    'xls'  => ['application/vnd.ms-excel'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    'txt'  => ['text/plain'],
    // Audio
    'mp3'  => ['audio/mpeg', 'audio/mp3'],
    // Archives
    'zip'  => ['application/zip', 'application/x-zip-compressed'],
    // Images
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png'  => ['image/png'],
    'webp' => ['image/webp'],
    // Videos
    'mp4'  => ['video/mp4'],
]);

// Folder name constraints
define('FOLDER_NAME_MIN_LENGTH', 3);
define('FOLDER_NAME_MAX_LENGTH', 50);
define('FOLDER_NAME_PATTERN', '/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/');

// Security
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour
define('RATE_LIMIT_UPLOADS', 20); // per hour

// File type icon mapping
define('FILE_TYPE_ICONS', [
    'pdf'  => '📄',
    'doc'  => '📝',
    'docx' => '📝',
    'ppt'  => '📊',
    'pptx' => '📊',
    'xls'  => '📈',
    'xlsx' => '📈',
    'txt'  => '📝',
    'mp3'  => '🎵',
    'zip'  => '📦',
    'jpg'  => '🖼️',
    'jpeg' => '🖼️',
    'png'  => '🖼️',
    'webp' => '🖼️',
    'mp4'  => '🎥',
]);

// File type categories for CSS classes
define('FILE_TYPE_CATEGORIES', [
    'pdf'  => 'document',
    'doc'  => 'document',
    'docx' => 'document',
    'ppt'  => 'presentation',
    'pptx' => 'presentation',
    'xls'  => 'spreadsheet',
    'xlsx' => 'spreadsheet',
    'txt'  => 'document',
    'mp3'  => 'audio',
    'zip'  => 'archive',
    'jpg'  => 'image',
    'jpeg' => 'image',
    'png'  => 'image',
    'webp' => 'image',
    'mp4'  => 'video',
]);
