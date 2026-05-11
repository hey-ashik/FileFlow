<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    // Add columns
    try {
        $db->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0");
        echo "Added is_admin column.\n";
    } catch (PDOException $e) { echo $e->getMessage() . "\n"; }
    
    try {
        $db->exec("ALTER TABLE users ADD COLUMN space_limit_mb INT NOT NULL DEFAULT 100");
        echo "Added space_limit_mb column.\n";
    } catch (PDOException $e) { /* Ignore if exists */ }

    try {
        $db->exec("ALTER TABLE users ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0");
        echo "Added is_public column.\n";
    } catch (PDOException $e) { /* Ignore if exists */ }

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
        echo "Created connections table.\n";
    } catch (PDOException $e) { echo $e->getMessage() . "\n"; }

    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `messages` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `sender_id` INT UNSIGNED NOT NULL,
            `receiver_id` INT UNSIGNED NOT NULL,
            `message` TEXT NOT NULL,
            `is_read` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        echo "Created messages table.\n";
    } catch (PDOException $e) { echo $e->getMessage() . "\n"; }

    // Check if admin user exists
    $email = 'ashikulislam2070@gmail.com';
    $password = 'Ashik@21032001';
    
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, avatar_color, is_admin, space_limit_mb) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Ashik', $email, $hash, '#16a34a', 1, 1000]);
        echo "Admin user created.\n";
    } else {
        $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $stmt->execute([$user['id']]);
        echo "Admin user updated.\n";
    }
    
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
