<?php
/**
 * Database Connection Configuration
 * Absensi HIMA TI Politeknik Hasnur
 * 
 * Gunakan kredensial dari panel hosting InfinityFree
 */

// ============================================
// DATABASE CREDENTIALS
// Ubah sesuai hosting kamu
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'absensi_himati');  // Nama database di hosting
define('DB_USER', 'root');            // Username database
define('DB_PASS', '');                // Password database
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO Database Connection
 * @return PDO
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Di production, jangan tampilkan detail error
            if (defined('APP_DEBUG') && APP_DEBUG) {
                die("Database Error: " . $e->getMessage());
            } else {
                die("Terjadi kesalahan koneksi database. Silakan hubungi admin.");
            }
        }
    }
    
    return $pdo;
}
