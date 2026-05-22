<?php
/**
 * FileFlow - Database Configuration
 * 
 * MySQL database connection using PDO.
 * Update credentials for your hosting environment.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'ashikone_fileflowdb');
define('DB_USER', 'ashikone_fileflowuser');
define('DB_PASS', 'Ashik@21032001');
define('DB_CHARSET', 'utf8mb4');

// Redis configuration
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 52499);
define('REDIS_PASS', 'GcKuKqim5daLCmh9ahM');
define('REDIS_DB', 0);
define('REDIS_ENABLED', true);

/**
 * Get PDO database connection
 * 
 * @return PDO
 * @throws PDOException
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci; SET time_zone = '+06:00';"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            header("HTTP/1.1 503 Service Unavailable");
            echo "A database connection error occurred. Please try again later.";
            exit;
        }
    }

    return $pdo;
}
