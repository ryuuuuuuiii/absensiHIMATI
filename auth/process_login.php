<?php
/**
 * Login Processing (Admin Only)
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/auth/login.php');
}

// Validate CSRF
if (!validateCSRF($_POST['csrf_token'] ?? '')) {
    setFlash('danger', 'Token keamanan tidak valid. Silakan coba lagi.');
    redirect(BASE_URL . '/auth/login.php');
}

$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate inputs
if (empty($email) || empty($password)) {
    setFlash('danger', 'Email dan password harus diisi.');
    $_SESSION['login_email'] = $email;
    redirect(BASE_URL . '/auth/login.php');
}

try {
    $db = getDB();
    
    // Query ke tabel admin (bukan users)
    $stmt = $db->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if (!$admin || !password_verify($password, $admin['password'])) {
        setFlash('danger', 'Email atau password salah.');
        $_SESSION['login_email'] = $email;
        redirect(BASE_URL . '/auth/login.php');
    }
    
    // Set session variables (admin)
    $_SESSION['admin_id']    = $admin['id'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_nama']  = $admin['nama'];
    $_SESSION['is_admin']    = true;
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    setFlash('success', 'Selamat datang, ' . $admin['nama'] . '!');
    redirect(BASE_URL . '/dashboard/index.php');
    
} catch (PDOException $e) {
    if (APP_DEBUG) {
        setFlash('danger', 'Database error: ' . $e->getMessage());
    } else {
        setFlash('danger', 'Terjadi kesalahan. Silakan coba lagi.');
    }
    redirect(BASE_URL . '/auth/login.php');
}
